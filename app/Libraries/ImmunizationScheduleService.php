<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTimeImmutable;
use DateTimeZone;

class ImmunizationScheduleService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
    * @return array{ok:int,created:int,skipped:int,error?:string,patient_id:int}
     */
    public function generateUipDueRecordsForPatient(int $patientId, array $options = []): array
    {
        if ($patientId <= 0) {
            return ['ok' => 0, 'created' => 0, 'skipped' => 0, 'patient_id' => $patientId, 'error' => 'Invalid patient_id'];
        }

        foreach (['patient_master', 'immunization_schedule_master', 'immunization_vaccine_master', 'immunization_records'] as $table) {
            if (! $this->db->tableExists($table)) {
                return ['ok' => 0, 'created' => 0, 'skipped' => 0, 'patient_id' => $patientId, 'error' => $table . ' table not found'];
            }
        }

        $patient = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray();
        if (empty($patient)) {
            return ['ok' => 0, 'created' => 0, 'skipped' => 0, 'patient_id' => $patientId, 'error' => 'Patient not found'];
        }

        $dob = $this->resolvePatientDob($patient);
        if ($dob === null) {
            return ['ok' => 0, 'created' => 0, 'skipped' => 0, 'patient_id' => $patientId, 'error' => 'Patient DOB is required'];
        }

        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Kolkata'));
        $diff = $dob->diff($today);
        $ageDays = $diff->invert === 0 ? (int) $diff->days : 0;
        $generationMode = strtolower(trim((string) ($options['generation_mode'] ?? 'full')));
        if (! in_array($generationMode, ['eligible', 'full'], true)) {
            $generationMode = 'full';
        }

        $scheduleRows = $this->db->table('immunization_schedule_master s')
            ->select('s.*, v.vaccine_name, v.vaccine_code, v.vaccine_code_system, v.route_code, v.route_name, v.site_code, v.site_name')
            ->join('immunization_vaccine_master v', 'v.id = s.vaccine_master_id', 'inner')
            ->where('s.is_uip', 1)
            ->where('s.is_active', 1)
            ->where('v.is_active', 1)
            ->orderBy('s.sort_order', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($scheduleRows)) {
            return ['ok' => 1, 'created' => 0, 'skipped' => 0, 'patient_id' => $patientId];
        }

        $existingRows = $this->db->table('immunization_records')
            ->select('schedule_id')
            ->where('patient_id', $patientId)
            ->where('schedule_id IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        $existingScheduleIds = [];
        foreach ($existingRows as $row) {
            $existingScheduleIds[(int) ($row['schedule_id'] ?? 0)] = true;
        }

        $now = date('Y-m-d H:i:s');
        $insertRows = [];
        $skipped = 0;
        $futureSkipped = 0;
        foreach ($scheduleRows as $schedule) {
            $scheduleId = (int) ($schedule['id'] ?? 0);
            if ($scheduleId <= 0 || isset($existingScheduleIds[$scheduleId])) {
                $skipped++;
                continue;
            }

            $offsetDays = max(0, (int) ($schedule['age_offset_days'] ?? 0));
            if ($generationMode === 'eligible' && $offsetDays > $ageDays) {
                $futureSkipped++;
                continue;
            }

            $dueDate = $dob->modify('+' . $offsetDays . ' days')->format('Y-m-d');
            $insertRows[] = [
                'patient_id' => $patientId,
                'schedule_id' => $scheduleId,
                'vaccine_master_id' => (int) ($schedule['vaccine_master_id'] ?? 0),
                'vaccine_name' => (string) ($schedule['vaccine_name'] ?? ''),
                'vaccine_code' => (string) ($schedule['vaccine_code'] ?? ''),
                'vaccine_code_system' => (string) ($schedule['vaccine_code_system'] ?? ''),
                'dose_number' => (string) ($schedule['dose_number'] ?? ''),
                'due_date' => $dueDate,
                'status' => 'due',
                'route_code' => (string) ($schedule['route_code'] ?? ''),
                'route_name' => (string) ($schedule['route_name'] ?? ''),
                'site_code' => (string) ($schedule['site_code'] ?? ''),
                'site_name' => (string) ($schedule['site_name'] ?? ''),
                'notes' => (string) ($schedule['notes'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($insertRows)) {
            $this->db->table('immunization_records')->insertBatch($insertRows);
        }

        return [
            'ok' => 1,
            'created' => count($insertRows),
            'skipped' => $skipped,
            'future_skipped' => $futureSkipped,
            'patient_id' => $patientId,
            'generation_mode' => $generationMode,
            'patient_age_days' => $ageDays,
            'patient_gender' => (string) ($patient['gender'] ?? $patient['xgender'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $patient
     */
    private function resolvePatientDob(array $patient): ?DateTimeImmutable
    {
        foreach (['dob', 'birth_date', 'date_of_birth', 'p_dob'] as $field) {
            $raw = trim((string) ($patient[$field] ?? ''));
            if ($raw === '' || $raw === '0000-00-00') {
                continue;
            }

            $timestamp = strtotime($raw);
            if ($timestamp === false) {
                continue;
            }

            return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone('Asia/Kolkata'));
        }

        return null;
    }
}