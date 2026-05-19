<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Spark command: php spark snomed:process-coding
 *
 * Runs from cron every minute:
 *   * * * * * cd /var/www/html && php spark snomed:process-coding >> /dev/null 2>&1
 *
 * Picks pending rows from opd_coding_queue, extracts clinical phrases from
 * free-text OPD fields, queries local SNOMED FULLTEXT index, saves
 * suggestions into opd_snomed_suggestions for staff review in ABDM Coding Panel.
 */
class ProcessSnomedCoding extends BaseCommand
{
    protected $group       = 'ABDM';
    protected $name        = 'snomed:process-coding';
    protected $description = 'Process pending OPD SNOMED coding queue — extract phrases, match SNOMED codes, save suggestions.';

    /** Max records per run (keep cron fast) */
    private const BATCH = 5;

    /** Source fields to process and their SNOMED semantic tag preferences */
    private const FIELD_TAGS = [
        'complaints'             => ['finding', 'disorder', 'symptom', 'observable entity'],
        'diagnosis'              => ['disorder', 'finding', 'disease'],
        'Provisional_diagnosis'  => ['disorder', 'finding', 'disease'],
        'Finding_Examinations'   => ['finding', 'observable entity', 'procedure'],
    ];

    public function run(array $params): void
    {
        $db = \Config\Database::connect();

        // Claim a batch atomically (status = pending → processing)
        $rows = $db->table('opd_coding_queue')
            ->where('status', 'pending')
            ->orderBy('queued_at', 'ASC')
            ->limit(self::BATCH)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            CLI::write('[snomed:process-coding] No pending items.', 'green');
            return;
        }

        foreach ($rows as $qRow) {
            $queueId    = (int) $qRow['id'];
            $opdId      = (int) $qRow['opd_id'];
            $sessionId  = (int) $qRow['opd_session_id'];

            // Mark processing to prevent parallel workers grabbing same row
            $db->table('opd_coding_queue')
                ->where('id', $queueId)
                ->where('status', 'pending')  // optimistic lock
                ->update(['status' => 'processing']);

            if ($db->affectedRows() === 0) {
                // Another worker claimed it — skip
                continue;
            }

            CLI::write("[snomed:process-coding] Processing queue #{$queueId} opd_id={$opdId} session={$sessionId}");

            try {
                $prescription = $db->table('opd_prescription')
                    ->where('id', $sessionId)
                    ->where('opd_id', $opdId)
                    ->get(1)
                    ->getRowArray();

                if (empty($prescription)) {
                    throw new \RuntimeException("opd_prescription row not found for session {$sessionId}");
                }

                $suggestionCount = $this->processPrescription($db, $opdId, $sessionId, $prescription);

                $db->table('opd_coding_queue')
                    ->where('id', $queueId)
                    ->update([
                        'status'          => 'done',
                        'has_suggestions' => $suggestionCount > 0 ? 1 : 0,
                        'processed_at'    => date('Y-m-d H:i:s'),
                        'error_message'   => null,
                    ]);

                CLI::write("  → {$suggestionCount} suggestions saved.", 'green');

            } catch (\Throwable $e) {
                $db->table('opd_coding_queue')
                    ->where('id', $queueId)
                    ->update([
                        'status'        => 'failed',
                        'processed_at'  => date('Y-m-d H:i:s'),
                        'error_message' => substr($e->getMessage(), 0, 500),
                    ]);

                CLI::write("  → FAILED: " . $e->getMessage(), 'red');
            }
        }

        CLI::write('[snomed:process-coding] Done.', 'green');
    }

    // -------------------------------------------------------------------------

    /**
     * Process one OPD prescription row — extract phrases from all relevant
     * fields and find SNOMED matches.
     *
     * @return int Number of suggestions inserted
     */
    private function processPrescription(\CodeIgniter\Database\BaseConnection $db, int $opdId, int $sessionId, array $prescription): int
    {
        $inserted = 0;

        // Delete any stale suggestions for this session (re-process is safe)
        $db->table('opd_snomed_suggestions')
            ->where('opd_session_id', $sessionId)
            ->where('status', 'pending_review')
            ->delete();

        foreach (self::FIELD_TAGS as $field => $preferredTags) {
            $text = trim((string) ($prescription[$field] ?? ''));
            if ($text === '') {
                continue;
            }

            $phrases = $this->extractPhrases($text);

            foreach ($phrases as $phrase) {
                $matches = $this->searchSnomed($db, $phrase, $preferredTags, 3);

                foreach ($matches as $idx => $match) {
                    // Skip very low confidence
                    if ($match['confidence'] < 0.15) {
                        continue;
                    }

                    $db->table('opd_snomed_suggestions')->insert([
                        'opd_id'         => $opdId,
                        'opd_session_id' => $sessionId,
                        'source_field'   => $field,
                        'source_phrase'  => substr($phrase, 0, 500),
                        'concept_id'     => $match['concept_id'],
                        'snomed_term'    => substr($match['term'], 0, 500),
                        'semantic_tag'   => substr($match['semantic_tag'], 0, 100),
                        'confidence'     => round($match['confidence'], 3),
                        'status'         => 'pending_review',
                        'created_at'     => date('Y-m-d H:i:s'),
                    ]);
                    $inserted++;
                }
            }
        }

        return $inserted;
    }

    /**
     * Extract meaningful clinical phrases from free-text.
     * Splits on delimiters, filters noise, deduplicates.
     *
     * @return string[]
     */
    private function extractPhrases(string $text): array
    {
        // Strip NABH structured lines if present (Drug Allergy Status etc.)
        $text = preg_replace('/^(Drug Allergy Status|Drug Allergy Details|ADR History|Current Medications)[^\n]*\n?/im', '', $text);

        // Normalise separators
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\s*[,;\/|]\s*/', "\n", $text);
        $text = preg_replace('/\s+and\s+|\s+with\s+/i', "\n", $text);

        $lines = explode("\n", $text);
        $phrases = [];

        foreach ($lines as $line) {
            // Also split on sentence end inside a line
            $parts = preg_split('/\.\s+/', $line);
            foreach ($parts as $part) {
                $part = trim(preg_replace('/\s+/', ' ', $part) ?? $part);
                // Filter by length
                if (mb_strlen($part) < 3 || mb_strlen($part) > 150) {
                    continue;
                }
                // Skip pure numeric or very short
                if (preg_match('/^\d+$/', $part)) {
                    continue;
                }
                $key = mb_strtolower($part);
                $phrases[$key] = $part;
            }
        }

        return array_values($phrases);
    }

    /**
     * Search local SNOMED FULLTEXT for a phrase.
     * Returns top $limit results with confidence scores.
     *
     * @return array<int,array{concept_id:string,term:string,semantic_tag:string,confidence:float}>
     */
    private function searchSnomed(\CodeIgniter\Database\BaseConnection $db, string $phrase, array $preferredTags, int $limit): array
    {
        if (trim($phrase) === '') {
            return [];
        }

        $safeTerm = str_replace(['"', "'", '\\'], ' ', $phrase);
        $safeTerm = trim($safeTerm);

        if ($safeTerm === '') {
            return [];
        }

        try {
            // Try FULLTEXT boolean mode first
            $boolTerm = '+' . implode(' +', array_filter(explode(' ', $safeTerm)));
            $sql = "SELECT
                        sd.conceptId AS concept_id,
                        sd.term,
                        MATCH(sd.term) AGAINST(? IN BOOLEAN MODE) AS ft_score
                    FROM snomed_description sd
                    WHERE sd.active = '1'
                      AND sd.typeId IN ('900000000000003001','900000000000013009')
                      AND MATCH(sd.term) AGAINST(? IN BOOLEAN MODE)
                    ORDER BY ft_score DESC
                    LIMIT ?";

            $results = $db->query($sql, [$boolTerm, $boolTerm, $limit * 4])->getResultArray();

            if (empty($results)) {
                // Fallback to natural language mode
                $sql2 = "SELECT
                            sd.conceptId AS concept_id,
                            sd.term,
                            MATCH(sd.term) AGAINST(? IN NATURAL LANGUAGE MODE) AS ft_score
                         FROM snomed_description sd
                         WHERE sd.active = '1'
                           AND sd.typeId IN ('900000000000003001','900000000000013009')
                           AND MATCH(sd.term) AGAINST(? IN NATURAL LANGUAGE MODE)
                         ORDER BY ft_score DESC
                         LIMIT ?";
                $results = $db->query($sql2, [$safeTerm, $safeTerm, $limit * 4])->getResultArray();
            }

        } catch (\Throwable $e) {
            return [];
        }

        if (empty($results)) {
            return [];
        }

        // Enrich with semantic tag and score
        $conceptIds = array_unique(array_column($results, 'concept_id'));
        $tagMap = $this->fetchSemanticTags($db, $conceptIds);

        $scored = [];
        $maxFt = (float) max(array_column($results, 'ft_score'));
        if ($maxFt <= 0) {
            $maxFt = 1;
        }

        foreach ($results as $row) {
            $conceptId   = (string) $row['concept_id'];
            $term        = (string) $row['term'];
            $ftScore     = (float) $row['ft_score'];
            $semanticTag = $tagMap[$conceptId] ?? '';

            // Normalised FT score
            $ftNorm = $ftScore / $maxFt;

            // Term similarity bonus (word overlap)
            $phraseWords = array_filter(explode(' ', mb_strtolower($phrase)));
            $termWords   = array_filter(explode(' ', mb_strtolower($term)));
            $overlap     = count(array_intersect($phraseWords, $termWords));
            $simBonus    = $overlap / max(count($phraseWords), 1) * 0.3;

            // Preferred semantic tag bonus
            $tagBonus = 0.0;
            $tagLower = mb_strtolower($semanticTag);
            foreach ($preferredTags as $preferred) {
                if (str_contains($tagLower, mb_strtolower($preferred))) {
                    $tagBonus = 0.2;
                    break;
                }
            }

            $confidence = min(1.0, $ftNorm * 0.5 + $simBonus + $tagBonus);

            $scored[] = [
                'concept_id'   => $conceptId,
                'term'         => $term,
                'semantic_tag' => $semanticTag,
                'confidence'   => $confidence,
            ];
        }

        // De-duplicate by concept_id, keeping highest confidence
        $best = [];
        foreach ($scored as $item) {
            $cid = $item['concept_id'];
            if (!isset($best[$cid]) || $item['confidence'] > $best[$cid]['confidence']) {
                $best[$cid] = $item;
            }
        }

        // Sort descending
        usort($best, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return array_slice(array_values($best), 0, $limit);
    }

    /**
     * Fetch semantic tags for an array of conceptIds.
     * Parses parenthesised tag from FSN term e.g. "Fever (finding)" → "finding"
     *
     * @param  string[] $conceptIds
     * @return array<string,string>
     */
    private function fetchSemanticTags(\CodeIgniter\Database\BaseConnection $db, array $conceptIds): array
    {
        if (empty($conceptIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($conceptIds), '?'));

        try {
            $rows = $db->query(
                "SELECT conceptId, term FROM snomed_description
                  WHERE conceptId IN ({$placeholders})
                    AND typeId = '900000000000003001'
                    AND active = '1'
                  LIMIT " . (count($conceptIds) * 2),
                $conceptIds
            )->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            if (preg_match('/\(([^)]+)\)\s*$/', (string) $row['term'], $m)) {
                $map[(string) $row['conceptId']] = $m[1];
            }
        }

        return $map;
    }
}
