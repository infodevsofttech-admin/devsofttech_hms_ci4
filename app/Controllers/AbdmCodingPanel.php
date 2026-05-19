<?php

namespace App\Controllers;

/**
 * ABDM SNOMED Coding Panel
 *
 * Provides a staff task-board to review, correct, confirm, or reject
 * AI-generated SNOMED coding suggestions for OPD consultations before
 * they are marked FHIR-ready and pushed to ABDM.
 *
 * Routes (all behind $abdmPermFilter):
 *   GET  AbdmCodingPanel               → index()
 *   GET  AbdmCodingPanel/review/{id}   → review($opdSessionId)
 *   POST AbdmCodingPanel/confirm       → confirm()
 *   POST AbdmCodingPanel/reject        → reject()
 *   POST AbdmCodingPanel/correct       → correct()
 *   POST AbdmCodingPanel/mark_fhir_ready → markFhirReady()
 *   GET  AbdmCodingPanel/tip_check     → tipCheck()  (called from consult form)
 */
class AbdmCodingPanel extends BaseController
{
    // -------------------------------------------------------------------------
    // Index — list of OPD sessions needing review
    // -------------------------------------------------------------------------

    public function index()
    {
        $db = \Config\Database::connect();

        // Sessions with suggestions, ordered by most recent first
        $rows = $db->query("
            SELECT
                q.id              AS queue_id,
                q.opd_id,
                q.opd_session_id,
                q.status          AS queue_status,
                q.queued_at,
                q.processed_at,
                om.opd_date,
                om.opd_no,
                pm.p_name         AS patient_name,
                pm.p_mobile       AS patient_mobile,
                (SELECT COUNT(*) FROM opd_snomed_suggestions s
                 WHERE s.opd_session_id = q.opd_session_id) AS total_suggestions,
                (SELECT COUNT(*) FROM opd_snomed_suggestions s
                 WHERE s.opd_session_id = q.opd_session_id
                   AND s.status = 'pending_review')          AS pending_count,
                (SELECT COUNT(*) FROM opd_snomed_suggestions s
                 WHERE s.opd_session_id = q.opd_session_id
                   AND s.status = 'confirmed')               AS confirmed_count
            FROM opd_coding_queue q
            LEFT JOIN opd_master om ON om.opd_id = q.opd_id
            LEFT JOIN patient_master pm ON pm.id = om.p_id
            WHERE q.status IN ('done','failed')
              AND q.has_suggestions = 1
            ORDER BY q.queued_at DESC
            LIMIT 200
        ")->getResultArray();

        return view('abdm/coding_panel', [
            'sessions' => $rows,
            'page_title' => 'SNOMED Coding Panel',
        ]);
    }

    // -------------------------------------------------------------------------
    // Review — show all suggestions for one OPD session
    // -------------------------------------------------------------------------

    public function review(int $sessionId = 0)
    {
        if ($sessionId <= 0) {
            return redirect()->to('AbdmCodingPanel');
        }

        $db = \Config\Database::connect();

        $suggestions = $db->table('opd_snomed_suggestions')
            ->where('opd_session_id', $sessionId)
            ->orderBy('source_field', 'ASC')
            ->orderBy('confidence', 'DESC')
            ->get()
            ->getResultArray();

        if (empty($suggestions)) {
            return redirect()->to('AbdmCodingPanel')->with('message', 'No suggestions found for this session.');
        }

        // Fetch consult header info
        $consult = $db->query("
            SELECT op.*, om.opd_date, om.opd_no, pm.p_name AS patient_name
            FROM opd_prescription op
            JOIN opd_master om ON om.opd_id = op.opd_id
            JOIN patient_master pm ON pm.id = om.p_id
            WHERE op.id = ?
            LIMIT 1
        ", [$sessionId])->getRowArray();

        $queueRow = $db->table('opd_coding_queue')
            ->where('opd_session_id', $sessionId)
            ->get(1)
            ->getRowArray();

        return view('abdm/coding_panel_review', [
            'session_id'  => $sessionId,
            'consult'     => $consult,
            'suggestions' => $suggestions,
            'queue_row'   => $queueRow,
            'page_title'  => 'Review SNOMED Codes',
        ]);
    }

    // -------------------------------------------------------------------------
    // Confirm a suggestion
    // -------------------------------------------------------------------------

    public function confirm()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0]);
        }

        $id     = (int) $this->request->getPost('suggestion_id');
        $userId = (int) session()->get('user_id');

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid suggestion_id']);
        }

        $db = \Config\Database::connect();
        $db->table('opd_snomed_suggestions')
            ->where('id', $id)
            ->update([
                'status'      => 'confirmed',
                'reviewed_by' => $userId ?: null,
                'reviewed_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON(['ok' => 1, 'suggestion_id' => $id, 'status' => 'confirmed']);
    }

    // -------------------------------------------------------------------------
    // Reject a suggestion
    // -------------------------------------------------------------------------

    public function reject()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0]);
        }

        $id     = (int) $this->request->getPost('suggestion_id');
        $userId = (int) session()->get('user_id');

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid suggestion_id']);
        }

        $db = \Config\Database::connect();
        $db->table('opd_snomed_suggestions')
            ->where('id', $id)
            ->update([
                'status'      => 'rejected',
                'reviewed_by' => $userId ?: null,
                'reviewed_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON(['ok' => 1, 'suggestion_id' => $id, 'status' => 'rejected']);
    }

    // -------------------------------------------------------------------------
    // Correct — reviewer overrides with a different SNOMED code
    // -------------------------------------------------------------------------

    public function correct()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0]);
        }

        $id          = (int)    $this->request->getPost('suggestion_id');
        $conceptId   = trim((string) $this->request->getPost('corrected_concept_id'));
        $term        = trim((string) $this->request->getPost('corrected_term'));
        $userId      = (int)    session()->get('user_id');

        if ($id <= 0 || $conceptId === '' || $term === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'suggestion_id, corrected_concept_id and corrected_term are required']);
        }

        // Validate concept_id is numeric (18-digit SNOMED CT)
        if (! preg_match('/^\d{6,18}$/', $conceptId)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid SNOMED concept_id format']);
        }

        $db = \Config\Database::connect();
        $db->table('opd_snomed_suggestions')
            ->where('id', $id)
            ->update([
                'status'               => 'corrected',
                'corrected_concept_id' => $conceptId,
                'corrected_term'       => substr($term, 0, 500),
                'reviewed_by'          => $userId ?: null,
                'reviewed_at'          => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON(['ok' => 1, 'suggestion_id' => $id, 'status' => 'corrected']);
    }

    // -------------------------------------------------------------------------
    // Mark all confirmed/corrected suggestions for this session as FHIR-ready
    // (updates opd_prescription SNOMED fields with the final codes)
    // -------------------------------------------------------------------------

    public function markFhirReady()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0]);
        }

        $sessionId = (int) $this->request->getPost('session_id');
        if ($sessionId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'session_id required']);
        }

        $db = \Config\Database::connect();

        // Load finalised suggestions for this session
        $suggestions = $db->table('opd_snomed_suggestions')
            ->where('opd_session_id', $sessionId)
            ->whereIn('status', ['confirmed', 'corrected'])
            ->get()
            ->getResultArray();

        if (empty($suggestions)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'No confirmed/corrected suggestions found']);
        }

        // Build field → best-confidence suggestion map
        $byField = [];
        foreach ($suggestions as $s) {
            $field = $s['source_field'];
            if (!isset($byField[$field]) || (float) $s['confidence'] > (float) $byField[$field]['confidence']) {
                $byField[$field] = $s;
            }
        }

        // Map our source_field names to opd_prescription column names
        $fieldColumnMap = [
            'complaints'            => ['snomed_id' => null, 'snomed_term' => null],  // stored in complaint_snomed_json
            'diagnosis'             => ['snomed_id' => 'diagnosis_snomed_id',          'snomed_term' => 'diagnosis_snomed_term'],
            'Provisional_diagnosis' => ['snomed_id' => 'provisional_diagnosis_snomed_id', 'snomed_term' => 'provisional_diagnosis_snomed_term'],
        ];

        $updates = [];
        foreach ($byField as $field => $s) {
            $finalConceptId = $s['status'] === 'corrected' ? $s['corrected_concept_id'] : $s['concept_id'];
            $finalTerm      = $s['status'] === 'corrected' ? $s['corrected_term']       : $s['snomed_term'];

            if (! isset($fieldColumnMap[$field])) {
                continue;
            }
            $cols = $fieldColumnMap[$field];
            if ($cols['snomed_id'] !== null) {
                $updates[$cols['snomed_id']]   = $finalConceptId;
                $updates[$cols['snomed_term']] = $finalTerm;
                $updates[str_replace('_id', '_source', $cols['snomed_id'])] = 'coding-panel';
            }
        }

        if (! empty($updates)) {
            $db->table('opd_prescription')
                ->where('id', $sessionId)
                ->update($updates);
        }

        // Mark queue row as fhir-ready (reuse 'done' status, set has_suggestions = 2 as flag)
        $db->table('opd_coding_queue')
            ->where('opd_session_id', $sessionId)
            ->update(['has_suggestions' => 2]);

        return $this->response->setJSON([
            'ok'         => 1,
            'session_id' => $sessionId,
            'updated'    => count($updates) / 2,
        ]);
    }

    // -------------------------------------------------------------------------
    // Tip check — lightweight poll from the consult form after save
    // Returns whether fast coding suggestions are available for this session
    // -------------------------------------------------------------------------

    public function tipCheck()
    {
        $sessionId = (int) $this->request->getGet('session_id');
        if ($sessionId <= 0) {
            return $this->response->setJSON(['has_suggestions' => 0]);
        }

        $db = \Config\Database::connect();

        $count = $db->table('opd_snomed_suggestions')
            ->where('opd_session_id', $sessionId)
            ->where('status', 'pending_review')
            ->countAllResults();

        return $this->response->setJSON([
            'has_suggestions' => $count > 0 ? 1 : 0,
            'count'           => $count,
            'review_url'      => base_url('AbdmCodingPanel/review/' . $sessionId),
        ]);
    }
}
