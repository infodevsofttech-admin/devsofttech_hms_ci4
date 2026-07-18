<?php

namespace App\Controllers;

use App\Libraries\ImmunizationMasterSyncService;
use App\Libraries\ImmunizationScheduleService;

class Immunization extends BaseController
{
    public function index()
    {
        return view('immunization/index', [
            'page_title' => 'Immunization Record',
            'patient_id' => (int) ($this->request->getGet('patient_id') ?? 0),
        ]);
    }

    public function schedule()
    {
        if (! $this->db->tableExists('immunization_schedule_master') || ! $this->db->tableExists('immunization_vaccine_master')) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error_text' => 'Immunization tables not found. Run migrations first.',
            ]);
        }

        $rows = $this->db->table('immunization_schedule_master s')
            ->select('s.*, v.vaccine_name, v.vaccine_code, v.vaccine_code_system, v.target_disease_name, v.route_name, v.site_name')
            ->join('immunization_vaccine_master v', 'v.id = s.vaccine_master_id', 'left')
            ->where('s.is_active', 1)
            ->orderBy('s.sort_order', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'ok' => 1,
            'count' => count($rows),
            'items' => $rows,
        ]);
    }

    public function scheduleMaster()
    {
        $syncService = new ImmunizationMasterSyncService($this->db);

        return view('immunization/schedule_master', [
            'page_title' => 'UIP Schedule Master',
            'items' => $this->loadScheduleRows(true),
            'sync_meta' => $syncService->latestSyncMeta(),
        ]);
    }

    public function syncUipMaster()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $force = (int) ($this->request->getPost('force') ?? 0) === 1;
        $result = (new ImmunizationMasterSyncService($this->db))->syncUipMaster($force);
        $httpCode = (int) ($result['ok'] ?? 0) === 1 ? 200 : 400;

        return $this->response->setStatusCode($httpCode)->setJSON($result + [
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function updateSchedule(int $scheduleId = 0)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        if ($scheduleId <= 0 || ! $this->db->tableExists('immunization_schedule_master') || ! $this->db->tableExists('immunization_vaccine_master')) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid schedule record']);
        }

        $schedule = $this->db->table('immunization_schedule_master')->where('id', $scheduleId)->get(1)->getRowArray();
        if (empty($schedule)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Schedule not found']);
        }

        $now = date('Y-m-d H:i:s');
        $ageLabel = $this->nullablePost('age_label') ?? (string) ($schedule['age_label'] ?? '');
        $doseNumber = $this->nullablePost('dose_number') ?? (string) ($schedule['dose_number'] ?? '');
        $notes = $this->nullablePost('notes');
        $scheduleUpdate = [
            'age_label' => $ageLabel,
            'age_value' => max(0, (int) ($this->request->getPost('age_value') ?? ($schedule['age_value'] ?? 0))),
            'age_unit' => $this->nullablePost('age_unit') ?? (string) ($schedule['age_unit'] ?? 'days'),
            'age_offset_days' => max(0, (int) ($this->request->getPost('age_offset_days') ?? ($schedule['age_offset_days'] ?? 0))),
            'dose_number' => $doseNumber,
            'series_doses' => $this->nullablePost('series_doses'),
            'notes' => $notes,
            'sort_order' => max(0, (int) ($this->request->getPost('sort_order') ?? ($schedule['sort_order'] ?? 0))),
            'is_active' => (int) ($this->request->getPost('is_active') ?? 0) === 1 ? 1 : 0,
            'updated_at' => $now,
        ];

        $vaccineUpdate = [
            'vaccine_name' => $this->nullablePost('vaccine_name') ?? '',
            'target_disease_name' => $this->nullablePost('target_disease_name'),
            'route_name' => $this->nullablePost('route_name'),
            'site_name' => $this->nullablePost('site_name'),
            'updated_at' => $now,
        ];
        if ($vaccineUpdate['vaccine_name'] === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Vaccine name is required']);
        }

        $this->db->table('immunization_schedule_master')->where('id', $scheduleId)->update($scheduleUpdate);
        $vaccineId = (int) ($schedule['vaccine_master_id'] ?? 0);
        if ($vaccineId > 0) {
            $this->db->table('immunization_vaccine_master')->where('id', $vaccineId)->update($vaccineUpdate);
        }

        if ($this->db->tableExists('immunization_records')) {
            $this->db->table('immunization_records')
                ->where('schedule_id', $scheduleId)
                ->where('status !=', 'completed')
                ->update([
                    'vaccine_name' => $vaccineUpdate['vaccine_name'],
                    'dose_number' => $doseNumber,
                    'route_name' => $vaccineUpdate['route_name'],
                    'site_name' => $vaccineUpdate['site_name'],
                    'notes' => $notes,
                    'updated_at' => $now,
                ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'item' => $this->loadScheduleRow($scheduleId),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function patient(int $patientId = 0)
    {
        if ($patientId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid patient_id']);
        }

        foreach (['patient_master', 'immunization_records'] as $table) {
            if (! $this->db->tableExists($table)) {
                return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $table . ' table not found']);
            }
        }

        $patient = $this->loadPatientHeader($patientId);
        if (empty($patient)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Patient not found']);
        }

        $rows = $this->db->table('immunization_records r')
            ->select('r.*, s.age_label, s.age_offset_days, s.series_name, s.series_doses, v.target_disease_name')
            ->join('immunization_schedule_master s', 's.id = r.schedule_id', 'left')
            ->join('immunization_vaccine_master v', 'v.id = r.vaccine_master_id', 'left')
            ->where('r.patient_id', $patientId)
            ->orderBy('COALESCE(r.due_date, r.given_date)', 'ASC', false)
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'ok' => 1,
            'patient' => $patient,
            'summary' => $this->buildSummary($rows),
            'count' => count($rows),
            'items' => $rows,
        ]);
    }

    public function generatePatientSchedule(int $patientId = 0)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $result = (new ImmunizationScheduleService($this->db))->generateUipDueRecordsForPatient($patientId, [
            'generation_mode' => (string) ($this->request->getPost('generation_mode') ?? 'full'),
        ]);
        $httpCode = (int) ($result['ok'] ?? 0) === 1 ? 200 : 400;

        return $this->response->setStatusCode($httpCode)->setJSON($result + [
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function complete(int $recordId = 0)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        if ($recordId <= 0 || ! $this->db->tableExists('immunization_records')) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid immunization record']);
        }

        $record = $this->db->table('immunization_records')->where('id', $recordId)->get(1)->getRowArray();
        if (empty($record)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Immunization record not found']);
        }

        $givenDate = $this->normalizeDateTime((string) ($this->request->getPost('given_date') ?? ''));
        if ($givenDate === null) {
            $givenDate = date('Y-m-d H:i:s');
        }

        $expiryDate = $this->normalizeDate((string) ($this->request->getPost('expiry_date') ?? ''));
        $user = function_exists('auth') ? auth()->user() : null;
        $userId = (int) ($user->id ?? 0);

        $update = [
            'given_date' => $givenDate,
            'status' => 'completed',
            'lot_number' => $this->nullablePost('lot_number'),
            'expiry_date' => $expiryDate,
            'manufacturer' => $this->nullablePost('manufacturer'),
            'performer_id' => (int) ($this->request->getPost('performer_id') ?? 0) ?: null,
            'location_name' => $this->nullablePost('location_name'),
            'route_code' => $this->nullablePost('route_code') ?? (string) ($record['route_code'] ?? ''),
            'route_name' => $this->nullablePost('route_name') ?? (string) ($record['route_name'] ?? ''),
            'site_code' => $this->nullablePost('site_code') ?? (string) ($record['site_code'] ?? ''),
            'site_name' => $this->nullablePost('site_name') ?? (string) ($record['site_name'] ?? ''),
            'reaction_notes' => $this->nullablePost('reaction_notes'),
            'notes' => $this->nullablePost('notes') ?? (string) ($record['notes'] ?? ''),
            'updated_by' => $userId > 0 ? $userId : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('immunization_records')->where('id', $recordId)->update($update);

        return $this->response->setJSON([
            'ok' => 1,
            'record_id' => $recordId,
            'status' => 'completed',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function loadPatientHeader(int $patientId): array
    {
        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $select = ['id'];
        foreach (['p_code', 'p_fname', 'p_lname', 'gender', 'dob', 'age', 'age_in_month', 'mphone1', 'abha_id', 'abha_no', 'abha_address', 'abha'] as $field) {
            if (in_array($field, $fields, true)) {
                $select[] = $field;
            }
        }

        return $this->db->table('patient_master')
            ->select(implode(',', array_unique($select)))
            ->where('id', $patientId)
            ->get(1)
            ->getRowArray() ?? [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function loadScheduleRows(bool $includeInactive = false): array
    {
        if (! $this->db->tableExists('immunization_schedule_master') || ! $this->db->tableExists('immunization_vaccine_master')) {
            return [];
        }

        $builder = $this->db->table('immunization_schedule_master s')
            ->select('s.*, v.vaccine_name, v.vaccine_code, v.vaccine_code_system, v.target_disease_name, v.route_name, v.site_name')
            ->join('immunization_vaccine_master v', 'v.id = s.vaccine_master_id', 'left')
            ->orderBy('s.sort_order', 'ASC')
            ->orderBy('s.id', 'ASC');

        if (! $includeInactive) {
            $builder->where('s.is_active', 1);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * @return array<string,mixed>
     */
    private function loadScheduleRow(int $scheduleId): array
    {
        if ($scheduleId <= 0) {
            return [];
        }

        return $this->db->table('immunization_schedule_master s')
            ->select('s.*, v.vaccine_name, v.vaccine_code, v.vaccine_code_system, v.target_disease_name, v.route_name, v.site_name')
            ->join('immunization_vaccine_master v', 'v.id = s.vaccine_master_id', 'left')
            ->where('s.id', $scheduleId)
            ->get(1)
            ->getRowArray() ?? [];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,int>
     */
    private function buildSummary(array $rows): array
    {
        $summary = [
            'total' => count($rows),
            'due' => 0,
            'overdue' => 0,
            'completed' => 0,
            'missed' => 0,
            'postponed' => 0,
        ];
        $today = date('Y-m-d');

        foreach ($rows as $row) {
            $status = strtolower(trim((string) ($row['status'] ?? 'due')));
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
            $dueDate = trim((string) ($row['due_date'] ?? ''));
            if ($status === 'due' && $dueDate !== '' && $dueDate < $today) {
                $summary['overdue']++;
            }
        }

        return $summary;
    }

    private function nullablePost(string $key): ?string
    {
        $value = trim((string) ($this->request->getPost($key) ?? ''));
        return $value !== '' ? $value : null;
    }

    private function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}