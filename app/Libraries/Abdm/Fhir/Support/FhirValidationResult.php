<?php

namespace App\Libraries\Abdm\Fhir\Support;

class FhirValidationResult
{
    public bool $valid;
    public int $score;

    /** @var array<int,array<string,string>> */
    public array $errors;

    /** @var array<int,array<string,string>> */
    public array $warnings;

    /** @var array<string,mixed> */
    public array $terminologySummary;

    /** @var array<string,mixed> */
    public array $audit;

    /**
     * @param array<int,array<string,string>> $errors
     * @param array<int,array<string,string>> $warnings
     * @param array<string,mixed> $terminologySummary
     * @param array<string,mixed> $audit
     */
    public function __construct(bool $valid, int $score, array $errors = [], array $warnings = [], array $terminologySummary = [], array $audit = [])
    {
        $this->valid = $valid;
        $this->score = $score;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->terminologySummary = $terminologySummary;
        $this->audit = $audit;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'score' => $this->score,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'terminology_summary' => $this->terminologySummary,
            'audit' => $this->audit,
        ];
    }
}
