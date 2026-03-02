<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\BridgeSyncService;
use App\Models\OpdMedicineModel;
use CodeIgniter\I18n\Time;

class Opd_prescription extends BaseController
{
    private string $lastAiProvider = 'none';

    private function canAccessPrescription(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'can') && $user->can('opd.doctor-panel.access')) {
            return true;
        }

        return false;
    }

    public function Prescription(int $opdId)
    {
        if (! $this->canAccessPrescription()) {
            return $this->response->setStatusCode(403)->setBody('Access denied: OPD Prescription is restricted to doctor-authorized users.');
        }

        $sql = "select * from opd_master where opd_id=" . (int) $opdId;
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        if (empty($opdMaster)) {
            return $this->response->setStatusCode(404)->setBody('OPD not found');
        }

        $opdRow = $opdMaster[0];
        $patientId = (int) ($opdRow->p_id ?? 0);

        $sql = "select *,if(gender=1,'Male','Female') as xgender
            from patient_master where id='" . (int) $opdRow->p_id . "'";
        $query = $this->db->query($sql);
        $patient = $query->getResult();

        if (!empty($patient)) {
            $patient[0]->str_age = get_age_1($patient[0]->dob ?? null, $patient[0]->age ?? '', $patient[0]->age_in_month ?? '', $patient[0]->estimate_dob ?? '', $opdRow->apointment_date ?? null);
        }

        $prescription = [];
        if ($this->db->tableExists('opd_prescription')) {
            $prescription = $this->db->table('opd_prescription')
                ->where('opd_id', $opdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getResult();

            if (!empty($prescription)) {
                $first = $prescription[0];
                $firstRow = (array) $first;
                $womenText = '';
                if (isset($first->women_related_problems)) {
                    $womenText = trim((string) $first->women_related_problems);
                }
                if ($womenText === '') {
                    $womenText = $this->extractWomenRelatedProblemsFromRemarks((string) ($first->Prescriber_Remarks ?? ''));
                }

                $womenStructured = $this->parseWomenStructuredDetails($womenText);
                $first->women_related_problems = (string) ($womenStructured['women_related_problems'] ?? '');
                $first->women_lmp = trim((string) (
                    $firstRow['women_lmp']
                    ?? $firstRow['lmp']
                    ?? $firstRow['lmp_date']
                    ?? ($womenStructured['women_lmp'] ?? '')
                ));
                $first->women_last_baby = trim((string) (
                    $firstRow['women_last_baby']
                    ?? $firstRow['last_baby']
                    ?? $firstRow['last_baby_details']
                    ?? ($womenStructured['women_last_baby'] ?? '')
                ));
                $first->women_pregnancy_related = trim((string) (
                    $firstRow['women_pregnancy_related']
                    ?? $firstRow['pregnancy_related']
                    ?? $firstRow['pregnancy_related_problem']
                    ?? ($womenStructured['women_pregnancy_related'] ?? '')
                ));

                $nabhInfo = $this->hydrateNabhPrescriptionFields(
                    $firstRow,
                    (string) ($first->Prescriber_Remarks ?? '')
                );
                $first->drug_allergy_status = (string) ($nabhInfo['drug_allergy_status'] ?? '');
                $first->drug_allergy_details = (string) ($nabhInfo['drug_allergy_details'] ?? '');
                $first->adr_history = (string) ($nabhInfo['adr_history'] ?? '');
                $first->current_medications = (string) ($nabhInfo['current_medications'] ?? '');
                $prescription[0] = $first;
            }
        }

        return view('billing/opd_prescription_basic', [
            'opd_id' => $opdId,
            'opd_master' => $opdMaster,
            'patient_master' => $patient,
            'opd_prescription' => $prescription,
            'addiction_flags' => $this->getPatientAddictionFlags($patientId),
            'co_morbidities' => $this->getCoMorbiditiesWithSelection($patientId),
            'co_morbidities_prefilled_from_history' => $this->hasHistoricalCoMorbiditySelection($patientId),
            'history_alerts' => $this->buildPatientHistoryAlerts((int) ($opdRow->p_id ?? 0), $opdId),
            'left_remarks' => $this->fetchPatientRemarks((int) ($opdRow->p_id ?? 0), 8),
        ]);
    }

    public function vitals_get(int $opdId)
    {
        if (! $this->canAccessPrescription()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        if ($opdId <= 0 || ! $this->db->tableExists('opd_prescription')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'OPD prescription table not found']);
        }

        $row = $this->db->table('opd_prescription')
            ->where('opd_id', (int) $opdId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray() ?? [];

        $vitals = [
            'pulse' => (string) ($row['pulse'] ?? ''),
            'spo2' => (string) ($row['spo2'] ?? ''),
            'bp' => (string) ($row['bp'] ?? ''),
            'diastolic' => (string) ($row['diastolic'] ?? ''),
            'temp' => (string) ($row['temp'] ?? ''),
            'rr_min' => (string) ($row['rr_min'] ?? ''),
            'height' => (string) ($row['height'] ?? ''),
            'weight' => (string) ($row['weight'] ?? ''),
            'waist' => (string) ($row['waist'] ?? ''),
        ];

        return $this->response->setJSON([
            'update' => 1,
            'opd_session_id' => (int) ($row['id'] ?? 0),
            'vitals' => $vitals,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function vitals_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canAccessPrescription()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $opdId = (int) $this->request->getPost('opd_id');
        $sessionId = (int) $this->request->getPost('opd_session_id');
        if ($opdId <= 0 || ! $this->db->tableExists('opd_prescription')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'OPD prescription table not found']);
        }

        $sessionId = $this->ensurePrescriptionSession($opdId, $sessionId);
        if ($sessionId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to create prescription session']);
        }

        $fields = $this->db->getFieldNames('opd_prescription');
        $input = [
            'pulse' => trim((string) $this->request->getPost('pulse')),
            'spo2' => trim((string) $this->request->getPost('spo2')),
            'bp' => trim((string) $this->request->getPost('bp')),
            'diastolic' => trim((string) $this->request->getPost('diastolic')),
            'temp' => trim((string) $this->request->getPost('temp')),
            'rr_min' => trim((string) $this->request->getPost('rr_min')),
            'height' => trim((string) $this->request->getPost('height')),
            'weight' => trim((string) $this->request->getPost('weight')),
            'waist' => trim((string) $this->request->getPost('waist')),
        ];

        $update = [];
        foreach ($input as $field => $value) {
            if (in_array($field, $fields, true)) {
                $update[$field] = $value;
            }
        }

        if (empty($update)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'No vital fields available']);
        }

        $this->db->table('opd_prescription')->where('id', $sessionId)->update($update);

        return $this->response->setJSON([
            'update' => 1,
            'opd_session_id' => $sessionId,
            'error_text' => 'Vitals saved successfully',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_prescription_save()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'error_text' => 'Invalid request',
            ]);
        }

        if (!$this->db->tableExists('opd_prescription')) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Table opd_prescription not found.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $opdId = (int) $this->request->getPost('opd_id');
        $sessionId = (int) $this->request->getPost('opd_session_id');

        $sql = "select * from opd_master where opd_id=" . $opdId;
        $query = $this->db->query($sql);
        $opdMaster = $query->getResult();

        if (empty($opdMaster)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'OPD not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $opdRow = $opdMaster[0];
        $fields = $this->db->getFieldNames('opd_prescription');
        $patientRow = $this->db->table('patient_master')->where('id', (int) ($opdRow->p_id ?? 0))->get(1)->getRowArray() ?? [];

        $abhaAddress = trim((string) $this->request->getPost('abha_address'));
        if ($abhaAddress !== '' && ! $this->isValidAbhaAddress($abhaAddress)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid ABHA Address format.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $abhaField = $this->resolvePatientAbhaField();
        if ($abhaField !== null && $abhaAddress !== '') {
            $currentAbha = trim((string) ($patientRow[$abhaField] ?? ''));
            if ($currentAbha !== $abhaAddress) {
                $this->db->table('patient_master')->where('id', (int) ($opdRow->p_id ?? 0))->update([$abhaField => $abhaAddress]);
                $this->auditClinicalUpdate('patient_master', $abhaField, (int) ($opdRow->p_id ?? 0), $currentAbha, $abhaAddress);
                $patientRow[$abhaField] = $abhaAddress;
            }
        }

        $payload = [
            'complaints' => trim((string) $this->request->getPost('complaints')),
            'diagnosis' => trim((string) $this->request->getPost('diagnosis')),
            'Provisional_diagnosis' => trim((string) $this->request->getPost('provisional_diagnosis')),
            'Finding_Examinations' => trim((string) $this->request->getPost('finding_examinations')),
            'Prescriber_Remarks' => trim((string) $this->request->getPost('prescriber_remarks')),
            'women_related_problems' => trim((string) $this->request->getPost('women_related_problems')),
            'women_lmp' => trim((string) $this->request->getPost('women_lmp')),
            'women_last_baby' => trim((string) $this->request->getPost('women_last_baby')),
            'women_pregnancy_related' => trim((string) $this->request->getPost('women_pregnancy_related')),
            'drug_allergy_status' => trim((string) $this->request->getPost('drug_allergy_status')),
            'drug_allergy_details' => trim((string) $this->request->getPost('drug_allergy_details')),
            'adr_history' => trim((string) $this->request->getPost('adr_history')),
            'current_medications' => trim((string) $this->request->getPost('current_medications')),
            'investigation' => trim((string) $this->request->getPost('investigation')),
            'advice' => trim((string) $this->request->getPost('advice')),
            'next_visit' => trim((string) $this->request->getPost('next_visit')),
            'refer_to' => trim((string) $this->request->getPost('refer_to')),
            'bp' => trim((string) $this->request->getPost('bp')),
            'diastolic' => trim((string) $this->request->getPost('diastolic')),
            'pulse' => trim((string) $this->request->getPost('pulse')),
            'temp' => trim((string) $this->request->getPost('temp')),
            'spo2' => trim((string) $this->request->getPost('spo2')),
            'rr_min' => trim((string) $this->request->getPost('rr_min')),
            'height' => trim((string) $this->request->getPost('height')),
            'weight' => trim((string) $this->request->getPost('weight')),
            'waist' => trim((string) $this->request->getPost('waist')),
            'pallor' => trim((string) $this->request->getPost('pallor')),
            'Icterus' => trim((string) $this->request->getPost('icterus')),
            'cyanosis' => trim((string) $this->request->getPost('cyanosis')),
            'clubbing' => trim((string) $this->request->getPost('clubbing')),
            'edema' => trim((string) $this->request->getPost('edema')),
            'pain_value' => trim((string) $this->request->getPost('pain_value')),
            'pregnancy' => (int) $this->request->getPost('pregnancy'),
            'lactation' => (int) $this->request->getPost('lactation'),
            'liver_insufficiency' => (int) $this->request->getPost('liver_insufficiency'),
            'renal_insufficiency' => (int) $this->request->getPost('renal_insufficiency'),
            'pulmonary_insufficiency' => (int) $this->request->getPost('pulmonary_insufficiency'),
            'corona_suspected' => (int) $this->request->getPost('corona_suspected'),
            'dengue' => (int) $this->request->getPost('dengue'),
        ];

        $allergyStatusRaw = trim((string) ($payload['drug_allergy_status'] ?? ''));
        $allergyStatusNorm = mb_strtolower(preg_replace('/\s+/', ' ', $allergyStatusRaw) ?? $allergyStatusRaw);
        $allergyDetails = trim((string) ($payload['drug_allergy_details'] ?? ''));

        if ($allergyStatusRaw === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Drug Allergy Status is required as per NABH documentation.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if (in_array($allergyStatusNorm, ['known', 'yes', 'present'], true) && $allergyDetails === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Drug Allergy Details are required when Drug Allergy Status is Known.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $womenStructuredInput = [
            'women_lmp' => (string) ($payload['women_lmp'] ?? ''),
            'women_last_baby' => (string) ($payload['women_last_baby'] ?? ''),
            'women_pregnancy_related' => (string) ($payload['women_pregnancy_related'] ?? ''),
        ];

        $payload['women_related_problems'] = $this->buildWomenCombinedText(
            (string) ($payload['women_related_problems'] ?? ''),
            $womenStructuredInput
        );

        $this->mapWomenStructuredToExistingColumns($payload, $fields, $womenStructuredInput);

        $nabhInput = [
            'drug_allergy_status' => (string) ($payload['drug_allergy_status'] ?? ''),
            'drug_allergy_details' => (string) ($payload['drug_allergy_details'] ?? ''),
            'adr_history' => (string) ($payload['adr_history'] ?? ''),
            'current_medications' => (string) ($payload['current_medications'] ?? ''),
        ];
        $unmappedNabh = $this->mapNabhFieldsToExistingColumns($payload, $fields, $nabhInput);

        if (!in_array('women_related_problems', $fields, true)) {
            $womenText = trim((string) ($payload['women_related_problems'] ?? ''));
            if ($womenText !== '') {
                $payload['Prescriber_Remarks'] = $this->upsertWomenRelatedProblemsIntoRemarks(
                    (string) ($payload['Prescriber_Remarks'] ?? ''),
                    $womenText
                );
            }
        }

        if (!empty(array_filter($unmappedNabh, static fn ($v) => trim((string) $v) !== ''))) {
            $payload['Prescriber_Remarks'] = $this->upsertNabhFieldsIntoRemarks(
                (string) ($payload['Prescriber_Remarks'] ?? ''),
                $unmappedNabh
            );
        }

        foreach ($payload as $value) {
            if (mb_strlen($value) > 4000) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Each section allows up to 4000 characters.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }
        }

        $insertBase = [
            'opd_id' => $opdId,
            'p_id' => (int) ($opdRow->p_id ?? 0),
            'doc_id' => (int) ($opdRow->doc_id ?? 0),
            'date_opd_visit' => date('Y-m-d'),
            'visit_status' => 0,
            'session_id' => 0,
        ];

        $allowedData = [];
        foreach (array_merge($insertBase, $payload) as $key => $value) {
            if (in_array($key, $fields, true)) {
                $allowedData[$key] = $value;
            }
        }

        if (in_array('queue_no', $fields, true) && !isset($allowedData['queue_no'])) {
            $maxQueue = $this->db->table('opd_prescription')
                ->selectMax('queue_no', 'max_queue')
                ->where('date_opd_visit', date('Y-m-d'))
                ->where('doc_id', (int) ($opdRow->doc_id ?? 0))
                ->get()
                ->getRow();
            $allowedData['queue_no'] = (int) ($maxQueue->max_queue ?? 0) + 1;
        }

        $table = $this->db->table('opd_prescription');
        $beforeRow = [];

        if ($sessionId > 0) {
            $beforeRow = $table->where('id', $sessionId)->where('opd_id', $opdId)->get(1)->getRowArray() ?? [];
            $table->where('id', $sessionId)->where('opd_id', $opdId)->update($allowedData);
            $recordId = $sessionId;
        } else {
            $table->insert($allowedData);
            $recordId = (int) $this->db->insertID();
        }

        $afterRow = $this->db->table('opd_prescription')->where('id', $recordId)->where('opd_id', $opdId)->get(1)->getRowArray() ?? [];
        if (!empty($afterRow)) {
            $this->clinicalAuditTrail->logChangedFields('opd_prescription', $recordId, $beforeRow, $afterRow, $this->getCurrentUserId());
        }

        $fhirStored = $this->storePrescriptionFhirBundle((int) $opdId, (int) $recordId, $patientRow, (array) $opdRow);

        $this->syncPatientAddictionFlags((int) ($opdRow->p_id ?? 0), [
            'is_smoking' => (int) $this->request->getPost('is_smoking'),
            'is_alcohol' => (int) $this->request->getPost('is_alcohol'),
            'is_drug_abuse' => (int) $this->request->getPost('is_drug_abuse'),
        ]);

        $morbidityIds = $this->parseNumericList((string) $this->request->getPost('morbidities_list'));
        $this->syncPatientMorbidities((int) ($opdRow->p_id ?? 0), $morbidityIds);

        if (in_array('Prescriber_Remarks', $fields, true)) {
            $coMorbiditiesText = trim((string) $this->request->getPost('morbidities_text'));
            $remarksNow = (string) ($allowedData['Prescriber_Remarks'] ?? ($afterRow['Prescriber_Remarks'] ?? ''));
            $updatedRemarks = $this->upsertCoMorbiditiesIntoRemarks($remarksNow, $coMorbiditiesText);
            if ($updatedRemarks !== $remarksNow) {
                $this->db->table('opd_prescription')
                    ->where('id', $recordId)
                    ->where('opd_id', $opdId)
                    ->update(['Prescriber_Remarks' => $updatedRemarks]);
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'opd_session_id' => $recordId,
            'error_text' => 'Prescription saved',
            'fhir_stored' => $fhirStored ? 1 : 0,
            'saved_at' => date('d-m-Y H:i:s'),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function clinical_autotype()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $text = trim((string) $this->request->getPost('text'));
        $mode = trim((string) $this->request->getPost('mode'));
        if ($mode === '') {
            $mode = 'autotype';
        }

        if ($text === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Please enter text first.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $prompt = $this->buildClinicalPrompt($mode, $text);
        $this->lastAiProvider = 'none';
        $draft = $this->generateClinicalTextWithGemini($prompt);

        if ($draft !== null && trim($draft) !== '') {
            $provider = $this->lastAiProvider === 'azure' ? 'azure' : 'gemini';
            $providerLabel = $provider === 'azure' ? 'Azure OpenAI' : 'Gemini';
            return $this->response->setJSON([
                'update' => 1,
                'draft_text' => trim($draft),
                'error_text' => 'AI text prepared using ' . $providerLabel,
                'ai_mode' => $provider,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $fallback = $text;
        $fallback = $this->buildClinicalAutotypeLocalFallback($mode, $text);

        $fallbackMessage = 'External AI unavailable. Local rewrite applied.';
        if (trim($fallback) === trim($text)) {
            $fallbackMessage = 'External AI unavailable. Original text kept.';
        }

        return $this->response->setJSON([
            'update' => 1,
            'draft_text' => $fallback,
            'error_text' => $fallbackMessage,
            'ai_mode' => 'fallback',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function ai_full_draft()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $input = [
            'complaints' => trim((string) $this->request->getPost('complaints')),
            'finding_examinations' => trim((string) $this->request->getPost('finding_examinations')),
            'diagnosis' => trim((string) $this->request->getPost('diagnosis')),
            'provisional_diagnosis' => trim((string) $this->request->getPost('provisional_diagnosis')),
            'prescriber_remarks' => trim((string) $this->request->getPost('prescriber_remarks')),
            'investigation' => trim((string) $this->request->getPost('investigation')),
            'advice' => trim((string) $this->request->getPost('advice')),
            'next_visit' => trim((string) $this->request->getPost('next_visit')),
            'refer_to' => trim((string) $this->request->getPost('refer_to')),
            'bp' => trim((string) $this->request->getPost('bp')),
            'diastolic' => trim((string) $this->request->getPost('diastolic')),
            'pulse' => trim((string) $this->request->getPost('pulse')),
            'temp' => trim((string) $this->request->getPost('temp')),
            'spo2' => trim((string) $this->request->getPost('spo2')),
            'rr_min' => trim((string) $this->request->getPost('rr_min')),
            'height' => trim((string) $this->request->getPost('height')),
            'weight' => trim((string) $this->request->getPost('weight')),
            'waist' => trim((string) $this->request->getPost('waist')),
            'pallor' => trim((string) $this->request->getPost('pallor')),
            'icterus' => trim((string) $this->request->getPost('icterus')),
            'cyanosis' => trim((string) $this->request->getPost('cyanosis')),
            'clubbing' => trim((string) $this->request->getPost('clubbing')),
            'edema' => trim((string) $this->request->getPost('edema')),
            'pain_value' => trim((string) $this->request->getPost('pain_value')),
            'pregnancy' => (int) $this->request->getPost('pregnancy'),
            'lactation' => (int) $this->request->getPost('lactation'),
            'liver_insufficiency' => (int) $this->request->getPost('liver_insufficiency'),
            'renal_insufficiency' => (int) $this->request->getPost('renal_insufficiency'),
            'pulmonary_insufficiency' => (int) $this->request->getPost('pulmonary_insufficiency'),
            'corona_suspected' => (int) $this->request->getPost('corona_suspected'),
            'dengue' => (int) $this->request->getPost('dengue'),
            'is_smoking' => (int) $this->request->getPost('is_smoking'),
            'is_alcohol' => (int) $this->request->getPost('is_alcohol'),
            'is_drug_abuse' => (int) $this->request->getPost('is_drug_abuse'),
            'women_related_problems' => trim((string) $this->request->getPost('women_related_problems')),
            'women_lmp' => trim((string) $this->request->getPost('women_lmp')),
            'women_last_baby' => trim((string) $this->request->getPost('women_last_baby')),
            'women_pregnancy_related' => trim((string) $this->request->getPost('women_pregnancy_related')),
        ];

        if ($input['complaints'] === '' && $input['finding_examinations'] === '' && $input['diagnosis'] === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Add complaints/finding/diagnosis first, then use AI Draft.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $prompt = $this->buildFullClinicalDraftPrompt($input);
        $this->lastAiProvider = 'none';
        $draftText = $this->generateClinicalTextWithGemini($prompt);
        $drafts = $this->extractFullDraftJson($draftText);

        if ($drafts === null) {
            $drafts = $this->buildFullDraftFallback($input);
            $aiMode = 'fallback';
            $message = 'AI unavailable. Template draft prepared from entered data.';
        } else {
            $aiMode = $this->lastAiProvider === 'azure' ? 'azure' : 'gemini';
            $message = 'Full clinical draft prepared using ' . ($aiMode === 'azure' ? 'Azure OpenAI' : 'Gemini') . '.';
        }

        return $this->response->setJSON([
            'update' => 1,
            'drafts' => $drafts,
            'ai_mode' => $aiMode,
            'error_text' => $message,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function local_clinical_assist()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $input = [
            'complaints' => trim((string) $this->request->getPost('complaints')),
            'finding_examinations' => trim((string) $this->request->getPost('finding_examinations')),
            'diagnosis' => trim((string) $this->request->getPost('diagnosis')),
            'investigation' => trim((string) $this->request->getPost('investigation')),
            'advice' => trim((string) $this->request->getPost('advice')),
            'bp' => trim((string) $this->request->getPost('bp')),
            'diastolic' => trim((string) $this->request->getPost('diastolic')),
            'pulse' => trim((string) $this->request->getPost('pulse')),
            'temp' => trim((string) $this->request->getPost('temp')),
            'spo2' => trim((string) $this->request->getPost('spo2')),
            'rr_min' => trim((string) $this->request->getPost('rr_min')),
            'pain_value' => trim((string) $this->request->getPost('pain_value')),
            'pregnancy' => (int) $this->request->getPost('pregnancy'),
            'lactation' => (int) $this->request->getPost('lactation'),
            'dengue' => (int) $this->request->getPost('dengue'),
            'corona_suspected' => (int) $this->request->getPost('corona_suspected'),
        ];

        if ($input['complaints'] === '' && $input['finding_examinations'] === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Enter complaints or findings first for clinical assist.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $assist = $this->buildLocalClinicalAssist($input);

        return $this->response->setJSON([
            'update' => 1,
            'assist' => $assist,
            'error_text' => 'Local clinical assist prepared. Final decision remains with doctor.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function section_template_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $section = trim((string) $this->request->getPost('section'));
        $templateName = trim((string) $this->request->getPost('template_name'));
        $templateText = trim((string) $this->request->getPost('template_text'));
        $templateScope = strtolower(trim((string) $this->request->getPost('template_scope')));
        $templateDocId = (int) $this->request->getPost('template_doc_id');

        if ($templateName === '' || $templateText === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template name and text are required']);
        }

        if (! $this->isTemplateSectionAllowed($section)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unsupported section']);
        }

        if (! $this->ensureClinicalTemplateTable()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to access template storage']);
        }

        $docId = $this->getCurrentUserId();
        if ($templateScope === 'master') {
            $docId = 0;
        } elseif ($templateDocId > 0) {
            $docId = $templateDocId;
        } elseif ($docId < 0) {
            $docId = 0;
        }

        $table = $this->db->table('opd_clinical_templates');
        $existing = $table
            ->where('doc_id', $docId)
            ->where('section_key', $section)
            ->where('template_name', $templateName)
            ->where('is_active', 1)
            ->get(1)
            ->getRowArray();

        $now = date('Y-m-d H:i:s');
        if (! empty($existing)) {
            $table->where('id', (int) ($existing['id'] ?? 0))->update([
                'template_text' => $templateText,
                'updated_at' => $now,
            ]);
        } else {
            $table->insert([
                'doc_id' => $docId,
                'section_key' => $section,
                'template_name' => $templateName,
                'template_text' => $templateText,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => ($docId === 0 ? 'Master template saved' : 'My template saved'),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function section_template_list()
    {
        $section = trim((string) $this->request->getGet('section'));
        if (! $this->isTemplateSectionAllowed($section)) {
            return $this->response->setJSON(['rows' => []]);
        }

        if (! $this->ensureClinicalTemplateTable()) {
            return $this->response->setJSON(['rows' => []]);
        }

        $docId = $this->getCurrentUserId();
        $rows = $this->db->table('opd_clinical_templates')
            ->select('id,template_name,template_text,doc_id,section_key')
            ->where('section_key', $section)
            ->where('is_active', 1)
            ->groupStart()
            ->where('doc_id', $docId)
            ->orWhere('doc_id', 0)
            ->groupEnd()
            ->orderBy('doc_id', 'DESC')
            ->orderBy('template_name', 'ASC')
            ->limit(100)
            ->get()
            ->getResultArray();

        $usageByTemplateId = [];
        if (! empty($rows) && $this->ensureClinicalTemplateUsageTable() && $docId !== null) {
            $templateIds = array_map(static fn($row) => (int) ($row['id'] ?? 0), $rows);
            $templateIds = array_values(array_filter($templateIds, static fn($id) => $id > 0));

            if (! empty($templateIds)) {
                $usageRows = $this->db->table('opd_clinical_template_usage')
                    ->select('template_id,use_count,last_used_at')
                    ->where('doc_id', (int) $docId)
                    ->where('section_key', $section)
                    ->whereIn('template_id', $templateIds)
                    ->get()
                    ->getResultArray();

                foreach ($usageRows as $usageRow) {
                    $templateId = (int) ($usageRow['template_id'] ?? 0);
                    if ($templateId <= 0) {
                        continue;
                    }

                    $usageByTemplateId[$templateId] = [
                        'use_count' => (int) ($usageRow['use_count'] ?? 0),
                        'last_used_at' => (string) ($usageRow['last_used_at'] ?? ''),
                    ];
                }
            }
        }

        foreach ($rows as &$row) {
            $templateId = (int) ($row['id'] ?? 0);
            $usage = $usageByTemplateId[$templateId] ?? ['use_count' => 0, 'last_used_at' => ''];
            $row['use_count'] = (int) ($usage['use_count'] ?? 0);
            $row['last_used_at'] = (string) ($usage['last_used_at'] ?? '');
        }
        unset($row);

        usort($rows, static function (array $a, array $b): int {
            $aUse = (int) ($a['use_count'] ?? 0);
            $bUse = (int) ($b['use_count'] ?? 0);
            if ($aUse !== $bUse) {
                return $bUse <=> $aUse;
            }

            $aDoc = (int) ($a['doc_id'] ?? 0);
            $bDoc = (int) ($b['doc_id'] ?? 0);
            if ($aDoc !== $bDoc) {
                return $bDoc <=> $aDoc;
            }

            $aLast = trim((string) ($a['last_used_at'] ?? ''));
            $bLast = trim((string) ($b['last_used_at'] ?? ''));
            if ($aLast !== $bLast) {
                return strcmp($bLast, $aLast);
            }

            return strcasecmp((string) ($a['template_name'] ?? ''), (string) ($b['template_name'] ?? ''));
        });

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function section_template_track_usage()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $section = trim((string) $this->request->getPost('section'));
        $templateId = (int) $this->request->getPost('template_id');
        if ($templateId <= 0 || ! $this->isTemplateSectionAllowed($section)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Invalid template usage payload',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if (! $this->ensureClinicalTemplateTable() || ! $this->ensureClinicalTemplateUsageTable()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Usage storage unavailable',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $docId = $this->getCurrentUserId();
        if ($docId === null) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'User not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $templateRow = $this->db->table('opd_clinical_templates')
            ->select('id,section_key,is_active')
            ->where('id', $templateId)
            ->where('is_active', 1)
            ->get(1)
            ->getRowArray();
        if (empty($templateRow)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Template not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
        if (trim((string) ($templateRow['section_key'] ?? '')) !== $section) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Section mismatch for template',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $usageTable = $this->db->table('opd_clinical_template_usage');
        $existing = $usageTable
            ->where('doc_id', (int) $docId)
            ->where('template_id', $templateId)
            ->where('section_key', $section)
            ->get(1)
            ->getRowArray();

        $now = date('Y-m-d H:i:s');
        if (! empty($existing)) {
            $usageTable->where('id', (int) ($existing['id'] ?? 0))->update([
                'use_count' => ((int) ($existing['use_count'] ?? 0)) + 1,
                'last_used_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $usageTable->insert([
                'doc_id' => (int) $docId,
                'template_id' => $templateId,
                'section_key' => $section,
                'use_count' => 1,
                'last_used_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Template usage tracked',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function section_past_data()
    {
        $section = trim((string) $this->request->getGet('section'));
        $opdId = (int) $this->request->getGet('opd_id');

        if ($opdId <= 0 || ! $this->isTemplateSectionAllowed($section) || ! $this->db->tableExists('opd_master') || ! $this->db->tableExists('opd_prescription')) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'Past data not found']);
        }

        $field = $this->resolveSectionColumn($section);
        if ($field === null) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'Unsupported section']);
        }

        $fields = $this->db->getFieldNames('opd_prescription');
        if (! in_array($field, $fields, true)) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'Past data column missing']);
        }

        $opdRow = $this->db->table('opd_master')->select('p_id')->where('opd_id', $opdId)->get(1)->getRowArray();
        $patientId = (int) ($opdRow['p_id'] ?? 0);
        if ($patientId <= 0) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'Patient not found']);
        }

        $row = $this->db->table('opd_prescription')
            ->select($field . ' as section_text,opd_id,id')
            ->where('p_id', $patientId)
            ->where('opd_id !=', $opdId)
            ->where($field . ' IS NOT NULL', null, false)
            ->where($field . ' !=', '')
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (empty($row)) {
            return $this->response->setJSON(['update' => 0, 'past_text' => '', 'error_text' => 'No past data found']);
        }

        return $this->response->setJSON([
            'update' => 1,
            'past_text' => (string) ($row['section_text'] ?? ''),
            'past_opd_id' => (int) ($row['opd_id'] ?? 0),
            'error_text' => 'Past data loaded',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function template_workspace()
    {
        return view('billing/opd_template_workspace');
    }

    public function clinical_template_monitor()
    {
        if (! $this->ensureClinicalTemplateTable()) {
            return $this->response->setJSON(['rows' => []]);
        }

        $section = trim((string) $this->request->getGet('section'));
        $scope = strtolower(trim((string) $this->request->getGet('scope')));
        $doctorId = (int) $this->request->getGet('doctor_id');
        $docId = $this->getCurrentUserId();

        $builder = $this->db->table('opd_clinical_templates')
            ->select('id,doc_id,section_key,template_name,template_text,is_active,created_at,updated_at')
            ->where('is_active', 1);

        if ($this->isTemplateSectionAllowed($section)) {
            $builder->where('section_key', $section);
        }

        if ($scope === 'master') {
            $builder->where('doc_id', 0);
        } elseif ($scope === 'doctor') {
            if ($doctorId > 0) {
                $builder->where('doc_id', $doctorId);
            } else {
                $builder->where('doc_id', $docId);
            }
        } else {
            $builder->groupStart()
                ->where('doc_id', 0)
                ->orWhere('doc_id', $docId)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(300)
            ->get()
            ->getResultArray();

        $doctorNameById = [];
        if ($this->db->tableExists('doctor_master')) {
            $doctorRows = $this->db->table('doctor_master')->select('id,p_fname')->get()->getResultArray();
            foreach ($doctorRows as $doctorRow) {
                $doctorNameById[(int) ($doctorRow['id'] ?? 0)] = (string) ($doctorRow['p_fname'] ?? '');
            }
        }

        foreach ($rows as &$row) {
            $row['scope_label'] = ((int) ($row['doc_id'] ?? -1) === 0) ? 'Master' : 'Doctor';
            $row['doctor_name'] = ((int) ($row['doc_id'] ?? 0) > 0) ? ($doctorNameById[(int) ($row['doc_id'] ?? 0)] ?? '') : '';
        }
        unset($row);

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function clinical_template_update()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->ensureClinicalTemplateTable()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template storage unavailable']);
        }

        $id = (int) $this->request->getPost('id');
        $section = trim((string) $this->request->getPost('section'));
        $templateName = trim((string) $this->request->getPost('template_name'));
        $templateText = trim((string) $this->request->getPost('template_text'));
        $templateScope = strtolower(trim((string) $this->request->getPost('template_scope')));
        $templateDocId = (int) $this->request->getPost('template_doc_id');

        if ($id <= 0 || ! $this->isTemplateSectionAllowed($section) || $templateName === '' || $templateText === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template id, section, name and text are required']);
        }

        $targetDocId = $templateScope === 'master' ? 0 : ($templateDocId > 0 ? $templateDocId : $this->getCurrentUserId());
        if ($targetDocId < 0) {
            $targetDocId = 0;
        }

        $current = $this->db->table('opd_clinical_templates')->where('id', $id)->where('is_active', 1)->get(1)->getRowArray();
        if (empty($current)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Template not found']);
        }

        $currentDocId = (int) ($current['doc_id'] ?? -1);
        $myDocId = $this->getCurrentUserId();
        if ($currentDocId !== 0 && $currentDocId !== $myDocId) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'You can edit only your own or master templates']);
        }

        $existing = $this->db->table('opd_clinical_templates')
            ->where('id !=', $id)
            ->where('doc_id', $targetDocId)
            ->where('section_key', $section)
            ->where('template_name', $templateName)
            ->where('is_active', 1)
            ->get(1)
            ->getRowArray();

        if (!empty($existing)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Another template with same name already exists in selected scope']);
        }

        $this->db->table('opd_clinical_templates')->where('id', $id)->update([
            'doc_id' => $targetDocId,
            'section_key' => $section,
            'template_name' => $templateName,
            'template_text' => $templateText,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Template updated',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function clinical_template_doctors()
    {
        if (! $this->db->tableExists('doctor_master')) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames('doctor_master');
        $idField = $this->resolveFirstField($fields, ['id', 'doc_id']);
        $nameField = $this->resolveFirstField($fields, ['p_fname', 'name', 'doc_name']);
        if ($idField === null || $nameField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('doctor_master')
            ->select($idField . ' as id,' . $nameField . ' as name');
        if (in_array('active', $fields, true)) {
            $builder->where('active', 1);
        }

        $rows = $builder->orderBy($nameField, 'ASC')->get()->getResultArray();
        return $this->response->setJSON(['rows' => $rows]);
    }

    public function clinical_template_usage_analytics()
    {
        if (! $this->ensureClinicalTemplateTable() || ! $this->ensureClinicalTemplateUsageTable()) {
            return $this->response->setJSON([
                'top_rows' => [],
                'section_rows' => [],
                'summary' => ['total_uses' => 0, 'used_templates' => 0],
            ]);
        }

        $section = trim((string) $this->request->getGet('section'));
        $scope = strtolower(trim((string) $this->request->getGet('scope')));
        $doctorId = (int) $this->request->getGet('doctor_id');
        $currentDocId = (int) ($this->getCurrentUserId() ?? 0);
        $targetDocId = $scope === 'doctor' && $doctorId > 0 ? $doctorId : $currentDocId;

        $builder = $this->db->table('opd_clinical_template_usage u')
            ->select('u.template_id,u.section_key,u.use_count,u.last_used_at,t.template_name,t.doc_id')
            ->join('opd_clinical_templates t', 't.id = u.template_id', 'inner')
            ->where('u.doc_id', $targetDocId)
            ->where('t.is_active', 1);

        if ($this->isTemplateSectionAllowed($section)) {
            $builder->where('u.section_key', $section);
        }

        if ($scope === 'master') {
            $builder->where('t.doc_id', 0);
        } elseif ($scope === 'doctor') {
            $builder->where('t.doc_id', $targetDocId);
        } else {
            $builder->groupStart()
                ->where('t.doc_id', 0)
                ->orWhere('t.doc_id', $targetDocId)
                ->groupEnd();
        }

        $usageRows = $builder->get()->getResultArray();
        if (empty($usageRows)) {
            return $this->response->setJSON([
                'top_rows' => [],
                'section_rows' => [],
                'summary' => ['total_uses' => 0, 'used_templates' => 0],
            ]);
        }

        usort($usageRows, static function (array $a, array $b): int {
            $aUse = (int) ($a['use_count'] ?? 0);
            $bUse = (int) ($b['use_count'] ?? 0);
            if ($aUse !== $bUse) {
                return $bUse <=> $aUse;
            }

            return strcmp((string) ($b['last_used_at'] ?? ''), (string) ($a['last_used_at'] ?? ''));
        });

        $totalUses = 0;
        $usedTemplates = [];
        $sectionAgg = [];
        foreach ($usageRows as $row) {
            $use = (int) ($row['use_count'] ?? 0);
            $sec = trim((string) ($row['section_key'] ?? ''));

            $totalUses += $use;
            $templateId = (int) ($row['template_id'] ?? 0);
            if ($templateId > 0) {
                $usedTemplates[$templateId] = true;
            }

            if ($sec === '') {
                continue;
            }

            if (! isset($sectionAgg[$sec])) {
                $sectionAgg[$sec] = ['section_key' => $sec, 'total_uses' => 0, 'template_count' => 0, 'template_ids' => []];
            }

            $sectionAgg[$sec]['total_uses'] += $use;
            if ($templateId > 0 && ! isset($sectionAgg[$sec]['template_ids'][$templateId])) {
                $sectionAgg[$sec]['template_ids'][$templateId] = true;
                $sectionAgg[$sec]['template_count']++;
            }
        }

        $sectionRows = array_values(array_map(static function (array $row): array {
            unset($row['template_ids']);
            return $row;
        }, $sectionAgg));

        usort($sectionRows, static function (array $a, array $b): int {
            $aUse = (int) ($a['total_uses'] ?? 0);
            $bUse = (int) ($b['total_uses'] ?? 0);
            if ($aUse !== $bUse) {
                return $bUse <=> $aUse;
            }
            return strcasecmp((string) ($a['section_key'] ?? ''), (string) ($b['section_key'] ?? ''));
        });

        return $this->response->setJSON([
            'top_rows' => array_slice($usageRows, 0, 15),
            'section_rows' => $sectionRows,
            'summary' => [
                'total_uses' => $totalUses,
                'used_templates' => count($usedTemplates),
            ],
        ]);
    }

    public function rx_group_panel()
    {
        return view('billing/opd_rx_group_workspace', ['initial_rx_id' => 0]);
    }

    public function save_rx_group_edit(int $rxId = 0)
    {
        return view('billing/opd_rx_group_workspace', ['initial_rx_id' => max(0, $rxId)]);
    }

    public function rx_group_data()
    {
        if (! $this->db->tableExists('opd_prescription_template')) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames('opd_prescription_template');
        $idField = $this->resolveFirstField($fields, ['id']);
        $nameField = $this->resolveFirstField($fields, ['rx_group_name']);
        if ($idField === null || $nameField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $userId = max(0, $this->getCurrentUserId());
        $hasDocId = in_array('doc_id', $fields, true);

        $builder = $this->db->table('opd_prescription_template')
            ->select('MAX(' . $idField . ') as id,' . $nameField . ' as rx_group_name,COUNT(*) as row_count')
            ->where($nameField . ' <>', '');

        if ($hasDocId) {
            $builder->select('SUM(CASE WHEN doc_id = 0 THEN 1 ELSE 0 END) as global_count', false);
            $builder->select('SUM(CASE WHEN doc_id = ' . (int) $userId . ' THEN 1 ELSE 0 END) as doctor_count', false);
        }

        if ($hasDocId) {
            $builder->groupStart()
                ->where('doc_id', $userId)
                ->orWhere('doc_id', 0)
                ->groupEnd();
        }

        $rows = $builder
            ->groupBy($nameField)
            ->orderBy($nameField, 'ASC')
            ->limit(500)
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            if (! $hasDocId) {
                $row['access_label'] = 'Global';
                continue;
            }

            $globalCount = (int) ($row['global_count'] ?? 0);
            $doctorCount = (int) ($row['doctor_count'] ?? 0);
            if ($globalCount > 0 && $doctorCount > 0) {
                $row['access_label'] = 'Global + Doctor';
            } elseif ($globalCount > 0) {
                $row['access_label'] = 'Global';
            } elseif ($doctorCount > 0) {
                $row['access_label'] = 'Doctor';
            } else {
                $row['access_label'] = 'Specific User';
            }
        }
        unset($row);

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function rx_group_apply_to_session()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $opdId = (int) $this->request->getPost('opd_id');
        $sessionId = (int) $this->request->getPost('opd_session_id');
        $rxGroupId = (int) $this->request->getPost('rx_group_id');

        if ($opdId <= 0 || $rxGroupId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid OPD or Rx-Group']);
        }

        $templateTable = $this->findExistingTable(['opd_prescrption_prescribed_template', 'opd_prescription_prescribed_template']);
        if ($templateTable === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx-Group medicine template not found']);
        }

        $sessionTable = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($sessionTable === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine table not found']);
        }

        $templateFields = $this->db->getFieldNames($templateTable);
        if (! in_array('rx_group_id', $templateFields, true)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid Rx-Group template structure']);
        }

        $templateRows = $this->db->table($templateTable)
            ->where('rx_group_id', $rxGroupId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($templateRows)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'No medicines found in selected Rx-Group']);
        }

        $sessionId = $this->ensurePrescriptionSession($opdId, $sessionId);
        if ($sessionId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to create prescription session']);
        }

        $sessionFields = $this->db->getFieldNames($sessionTable);
        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $updateByText = $userLabel . '[' . ($user->id ?? 0) . ']:' . date('d-m-Y H:i:s');

        $inserted = 0;
        foreach ($templateRows as $row) {
            $insert = [];

            if (in_array('opd_pre_id', $sessionFields, true)) {
                $insert['opd_pre_id'] = $sessionId;
            }
            if (in_array('opd_id', $sessionFields, true)) {
                $insert['opd_id'] = $opdId;
            }

            foreach (['med_id', 'med_name', 'med_type', 'dosage', 'dosage_when', 'dosage_freq', 'dosage_where', 'no_of_days', 'qty', 'remark', 'genericname'] as $field) {
                if (! in_array($field, $sessionFields, true) || ! array_key_exists($field, $row)) {
                    continue;
                }

                $value = $row[$field];
                if (in_array($field, ['med_name', 'remark'], true)) {
                    $value = strtoupper((string) $value);
                }
                $insert[$field] = $value;
            }

            if (in_array('update_by', $sessionFields, true)) {
                $insert['update_by'] = $updateByText;
            }

            if (! empty($insert)) {
                $this->db->table($sessionTable)->insert($insert);
                $inserted++;

                $medId = (int) ($insert['med_id'] ?? 0);
                if ($medId > 0) {
                    $this->trackMedicineUsage($medId);
                }
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'opd_session_id' => $sessionId,
            'inserted_count' => $inserted,
            'error_text' => $inserted > 0 ? ($inserted . ' medicine(s) added from Rx-Group.') : 'No medicine inserted from Rx-Group.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function rx_group_get(int $rxId)
    {
        if (! $this->db->tableExists('opd_prescription_template')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx group table not found']);
        }

        $fields = $this->db->getFieldNames('opd_prescription_template');
        $builder = $this->db->table('opd_prescription_template')->where('id', (int) $rxId);
        if (in_array('doc_id', $fields, true)) {
            $userId = max(0, $this->getCurrentUserId());
            $builder->groupStart()
                ->where('doc_id', $userId)
                ->orWhere('doc_id', 0)
                ->groupEnd();
        }

        $row = $builder->get(1)->getRowArray() ?? [];
        if (empty($row)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx group not found']);
        }

        return $this->response->setJSON(['update' => 1, 'row' => $row]);
    }

    public function rx_group_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }
        if (! $this->db->tableExists('opd_prescription_template')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx group table not found']);
        }

        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('rx_group_name'));
        if ($name === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx-Group Name is required']);
        }

        $fields = $this->db->getFieldNames('opd_prescription_template');
        $data = [];
        if (in_array('rx_group_name', $fields, true)) {
            $data['rx_group_name'] = $name;
        }
        if (in_array('complaints', $fields, true)) {
            $data['complaints'] = trim((string) $this->request->getPost('complaints'));
        }
        if (in_array('diagnosis', $fields, true)) {
            $data['diagnosis'] = trim((string) $this->request->getPost('diagnosis'));
        }
        if (in_array('Finding_Examinations', $fields, true)) {
            $data['Finding_Examinations'] = trim((string) $this->request->getPost('finding_examinations'));
        }
        if (in_array('investigation', $fields, true)) {
            $data['investigation'] = trim((string) $this->request->getPost('investigation'));
        }

        $userId = max(0, $this->getCurrentUserId());
        if (in_array('rx_group_name', $fields, true)) {
            $dupBuilder = $this->db->table('opd_prescription_template')
                ->select('id')
                ->where('rx_group_name', $name);

            if (in_array('doc_id', $fields, true)) {
                $dupBuilder->groupStart()
                    ->where('doc_id', $userId)
                    ->orWhere('doc_id', 0)
                    ->groupEnd();
            }

            if ($id > 0) {
                $dupBuilder->where('id <>', $id);
            }

            $exists = $dupBuilder->orderBy('id', 'DESC')->get(1)->getRowArray();
            if (! empty($exists)) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Rx-Group name already exists. Please use Edit for existing group.',
                    'existing_id' => (int) ($exists['id'] ?? 0),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }
        }

        if (in_array('doc_id', $fields, true) && $id <= 0) {
            $data['doc_id'] = $userId;
        }

        $user = auth()->user();
        if (in_array('update_by', $fields, true)) {
            $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
            $data['update_by'] = $userLabel . '[' . ($user->id ?? 0) . ']:' . date('d-m-Y H:i:s');
        }

        if ($id > 0) {
            $this->db->table('opd_prescription_template')->where('id', $id)->update($data);
            $savedId = $id;
            $message = 'Rx-Group updated';
        } else {
            $this->db->table('opd_prescription_template')->insert($data);
            $savedId = (int) $this->db->insertID();
            $message = 'Rx-Group saved';
        }

        return $this->response->setJSON([
            'update' => 1,
            'insertid' => $savedId,
            'error_text' => $message,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function rx_group_medicine(int $rxId)
    {
        $rxGroupId = max(0, $rxId);
        $rxGroupName = '';
        if ($rxGroupId > 0 && $this->db->tableExists('opd_prescription_template')) {
            $templateFields = $this->db->getFieldNames('opd_prescription_template');
            $nameField = $this->resolveFirstField($templateFields, ['rx_group_name']);
            if ($nameField !== null) {
                $row = $this->db->table('opd_prescription_template')
                    ->select($nameField . ' as rx_group_name')
                    ->where('id', $rxGroupId)
                    ->get(1)
                    ->getRowArray();
                $rxGroupName = trim((string) ($row['rx_group_name'] ?? ''));
            }
        }

        return view('billing/rx_group_medicine_workspace', [
            'rx_group_id' => $rxGroupId,
            'rx_group_name' => $rxGroupName,
        ]);
    }

    public function rx_group_medicine_list(int $rxId)
    {
        $table = $this->findExistingTable(['opd_prescrption_prescribed_template', 'opd_prescription_prescribed_template']);
        if ($table === null) {
            return $this->response->setJSON(['rows' => [], 'rx_group_name' => '']);
        }

        $fields = $this->db->getFieldNames($table);
        if (! in_array('rx_group_id', $fields, true)) {
            return $this->response->setJSON(['rows' => [], 'rx_group_name' => '']);
        }

        $rxGroupName = '';
        if ($this->db->tableExists('opd_prescription_template')) {
            $templateFields = $this->db->getFieldNames('opd_prescription_template');
            $nameField = $this->resolveFirstField($templateFields, ['rx_group_name']);
            if ($nameField !== null) {
                $groupRow = $this->db->table('opd_prescription_template')
                    ->select($nameField . ' as rx_group_name')
                    ->where('id', (int) $rxId)
                    ->get(1)
                    ->getRowArray();
                $rxGroupName = trim((string) ($groupRow['rx_group_name'] ?? ''));
            }
        }

        $builder = $this->db->table($table)->where('rx_group_id', (int) $rxId);
        if (in_array('id', $fields, true)) {
            $builder->orderBy('id', 'DESC');
        }

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return $this->response->setJSON(['rows' => [], 'rx_group_name' => $rxGroupName]);
        }

        $medGenericMap = [];
        $missingGenericMedIds = [];
        foreach ($rows as $row) {
            $existingGeneric = trim((string) ($row['genericname'] ?? ''));
            $medId = (int) ($row['med_id'] ?? 0);
            if ($existingGeneric === '' && $medId > 0) {
                $missingGenericMedIds[$medId] = true;
            }
        }

        if (! empty($missingGenericMedIds)) {
            $masterTable = $this->findExistingTable(['opd_med_master']);
            if ($masterTable !== null) {
                $masterFields = $this->db->getFieldNames($masterTable);
                $masterIdField = $this->resolveFirstField($masterFields, ['id']);
                $masterGenericField = $this->resolveFirstField($masterFields, ['genericname', 'generic_name']);
                $masterSaltField = $this->resolveFirstField($masterFields, ['salt_name', 'sal_name', 'salt', 'saltname']);
                $labelField = $masterSaltField ?? $masterGenericField;

                if ($masterIdField !== null && $labelField !== null) {
                    $medRows = $this->db->table($masterTable)
                        ->select($masterIdField . ' as med_id,' . $labelField . ' as generic_fallback')
                        ->whereIn($masterIdField, array_map('intval', array_keys($missingGenericMedIds)))
                        ->get()
                        ->getResultArray();

                    foreach ($medRows as $medRow) {
                        $id = (int) ($medRow['med_id'] ?? 0);
                        $label = trim((string) ($medRow['generic_fallback'] ?? ''));
                        if ($id > 0 && $label !== '') {
                            $medGenericMap[$id] = $label;
                        }
                    }
                }
            }
        }

        $doseMap = $this->getDoseMasterLabelMap('opd_dose_shed', ['dose_shed_id', 'id'], ['dose_show_sign', 'dose_sign', 'dose_sign_desc', 'name']);
        $whenMap = $this->getDoseMasterLabelMap('opd_dose_when', ['dose_when_id', 'id'], ['dose_sign', 'dose_sign_desc', 'name']);
        $freqMap = $this->getDoseMasterLabelMap('opd_dose_frequency', ['dose_freq_id', 'id'], ['dose_sign', 'dose_sign_desc', 'name']);
        $whereMap = $this->getDoseMasterLabelMap('opd_dose_where', ['dose_where_id', 'id'], ['dose_sign', 'dose_sign_desc', 'name']);

        foreach ($rows as &$row) {
            if (trim((string) ($row['genericname'] ?? '')) === '') {
                $inlineSalt = trim((string) ($row['salt_name'] ?? ''));
                if ($inlineSalt !== '') {
                    $row['genericname'] = $inlineSalt;
                }

                $fallback = $medGenericMap[(int) ($row['med_id'] ?? 0)] ?? '';
                if (($row['genericname'] ?? '') === '' && $fallback !== '') {
                    $row['genericname'] = $fallback;
                }
            }
            $row['dosage_label'] = $this->resolveDoseMasterLabel($row['dosage'] ?? '', $doseMap);
            $row['dosage_when_label'] = $this->resolveDoseMasterLabel($row['dosage_when'] ?? '', $whenMap);
            $row['dosage_freq_label'] = $this->resolveDoseMasterLabel($row['dosage_freq'] ?? '', $freqMap);
            $row['dosage_where_label'] = $this->resolveDoseMasterLabel($row['dosage_where'] ?? '', $whereMap);
        }
        unset($row);

        return $this->response->setJSON(['rows' => $rows, 'rx_group_name' => $rxGroupName]);
    }

    public function rx_group_medicine_get(int $itemId)
    {
        $table = $this->findExistingTable(['opd_prescrption_prescribed_template', 'opd_prescription_prescribed_template']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx medicine table not found']);
        }

        $row = $this->db->table($table)->where('id', (int) $itemId)->get(1)->getRowArray() ?? [];
        if (empty($row)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx medicine not found']);
        }

        return $this->response->setJSON(['update' => 1, 'row' => $row]);
    }

    public function rx_group_medicine_save(int $rxId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('opd_prescription_template')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx group table not found']);
        }

        $rxExists = $this->db->table('opd_prescription_template')->where('id', (int) $rxId)->get(1)->getRowArray();
        if (empty($rxExists)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx group not found']);
        }

        $table = $this->findExistingTable(['opd_prescrption_prescribed_template', 'opd_prescription_prescribed_template']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx medicine table not found']);
        }

        $itemId = (int) $this->request->getPost('item_id');
        $medId = (int) $this->request->getPost('med_id');
        $medName = trim((string) $this->request->getPost('med_name'));
        if ($medName === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine name is required']);
        }

        $genericName = trim((string) $this->request->getPost('genericname'));
        $medType = trim((string) $this->request->getPost('med_type'));

        $masterTable = $this->findExistingTable(['opd_med_master']);
        if ($masterTable !== null) {
            $masterFields = $this->db->getFieldNames($masterTable);
            $masterNameField = $this->resolveFirstField($masterFields, ['item_name', 'med_name']);
            $masterTypeField = $this->resolveFirstField($masterFields, ['formulation']);
            $masterGenericField = $this->resolveFirstField($masterFields, ['genericname', 'generic_name']);
            $masterSaltField = $this->resolveFirstField($masterFields, ['salt_name', 'sal_name', 'salt', 'saltname']);

            if ($medId <= 0 && $masterNameField !== null) {
                $existingMaster = $this->db->table($masterTable)->where($masterNameField, $medName)->get(1)->getRowArray();
                if (! empty($existingMaster)) {
                    $medId = (int) ($existingMaster['id'] ?? 0);
                }
            }

            if ($medId > 0) {
                $updateMaster = [];
                if ($masterTypeField !== null && $medType !== '') {
                    $updateMaster[$masterTypeField] = $medType;
                }
                if ($masterGenericField !== null && $genericName !== '') {
                    $updateMaster[$masterGenericField] = $genericName;
                }
                if ($masterSaltField !== null && $genericName !== '') {
                    $updateMaster[$masterSaltField] = $genericName;
                }
                if (! empty($updateMaster)) {
                    $this->db->table($masterTable)->where('id', $medId)->update($updateMaster);
                }
            } elseif ($masterNameField !== null) {
                $insertMaster = [$masterNameField => $medName];
                if ($masterTypeField !== null) {
                    $insertMaster[$masterTypeField] = $medType;
                }
                if ($masterGenericField !== null) {
                    $insertMaster[$masterGenericField] = $genericName;
                }
                if ($masterSaltField !== null) {
                    $insertMaster[$masterSaltField] = $genericName;
                }
                $this->db->table($masterTable)->insert($insertMaster);
                $medId = (int) $this->db->insertID();
            }
        }

        $fields = $this->db->getFieldNames($table);
        $data = [];
        if (in_array('rx_group_id', $fields, true)) {
            $data['rx_group_id'] = (int) $rxId;
        }
        if (in_array('med_id', $fields, true)) {
            $data['med_id'] = $medId;
        }
        if (in_array('med_name', $fields, true)) {
            $data['med_name'] = strtoupper($medName);
        }
        if (in_array('med_type', $fields, true)) {
            $data['med_type'] = $medType;
        }
        if (in_array('dosage', $fields, true)) {
            $data['dosage'] = trim((string) $this->request->getPost('dosage'));
        }
        if (in_array('dosage_when', $fields, true)) {
            $data['dosage_when'] = trim((string) $this->request->getPost('dosage_when'));
        }
        if (in_array('dosage_freq', $fields, true)) {
            $data['dosage_freq'] = trim((string) $this->request->getPost('dosage_freq'));
        }
        if (in_array('dosage_where', $fields, true)) {
            $data['dosage_where'] = trim((string) $this->request->getPost('dosage_where'));
        }
        if (in_array('no_of_days', $fields, true)) {
            $data['no_of_days'] = trim((string) $this->request->getPost('no_of_days'));
        }
        if (in_array('qty', $fields, true)) {
            $data['qty'] = trim((string) $this->request->getPost('qty'));
        }
        if (in_array('remark', $fields, true)) {
            $data['remark'] = strtoupper(trim((string) $this->request->getPost('remark')));
        }
        if (in_array('genericname', $fields, true)) {
            $data['genericname'] = $genericName;
        }

        $user = auth()->user();
        if (in_array('update_by', $fields, true)) {
            $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
            $data['update_by'] = $userLabel . '[' . ($user->id ?? 0) . ']:' . date('d-m-Y H:i:s');
        }

        if ($itemId > 0) {
            $this->db->table($table)->where('id', $itemId)->update($data);
            $savedId = $itemId;
            $message = 'Rx group medicine updated';
        } else {
            $this->db->table($table)->insert($data);
            $savedId = (int) $this->db->insertID();
            $message = 'Rx group medicine added';
        }

        return $this->response->setJSON([
            'update' => 1,
            'insertid' => $savedId,
            'error_text' => $message,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function rx_group_medicine_remove(int $itemId)
    {
        $table = $this->findExistingTable(['opd_prescrption_prescribed_template', 'opd_prescription_prescribed_template']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Rx medicine table not found']);
        }

        $this->db->table($table)->where('id', (int) $itemId)->delete();
        return $this->response->setJSON(['update' => 1, 'error_text' => 'Rx group medicine removed']);
    }

    public function rx_group_medicine_suggest()
    {
        $q = trim((string) $this->request->getGet('q'));
        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null || $q === '') {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames($table);
        $idField = $this->resolveFirstField($fields, ['id']);
        $nameField = $this->resolveFirstField($fields, ['item_name', 'med_name']);
        $typeField = $this->resolveFirstField($fields, ['formulation']);
        $genericField = $this->resolveFirstField($fields, ['genericname', 'generic_name']);
        $saltField = $this->resolveFirstField($fields, ['salt_name', 'sal_name', 'salt', 'saltname']);
        $companyField = $this->resolveFirstField($fields, ['company_name', 'company']);

        if ($idField === null || $nameField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $select = $idField . ' as med_id,' . $nameField . ' as med_name';
        if ($typeField !== null) {
            $select .= ',' . $typeField . ' as med_type';
        }
        if ($genericField !== null) {
            $select .= ',' . $genericField . ' as genericname';
        }
        if ($saltField !== null) {
            $select .= ',' . $saltField . ' as salt_name';
        }
        if ($companyField !== null) {
            $select .= ',' . $companyField . ' as company_name';
        }

        $builder = $this->db->table($table)->select($select)->groupStart()->like($nameField, $q);
        if ($genericField !== null) {
            $builder->orLike($genericField, $q);
        }
        if ($saltField !== null) {
            $builder->orLike($saltField, $q);
        }
        $rows = $builder->groupEnd()->orderBy($nameField, 'ASC')->limit(30)->get()->getResultArray();

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function rx_group_generic_suggest()
    {
        $q = trim((string) $this->request->getGet('q'));
        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames($table);
        $genericField = $this->resolveFirstField($fields, ['genericname', 'generic_name']);
        $saltField = $this->resolveFirstField($fields, ['salt_name', 'sal_name', 'salt', 'saltname']);
        if ($genericField === null && $saltField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $values = [];
        $builder = $this->db->table($table);
        if ($genericField !== null) {
            $gBuilder = clone $builder;
            $gBuilder->select($genericField . ' as value')->where($genericField . ' !=', '');
            if ($q !== '') {
                $gBuilder->like($genericField, $q);
            }
            foreach ($gBuilder->orderBy($genericField, 'ASC')->limit(40)->get()->getResultArray() as $row) {
                $value = trim((string) ($row['value'] ?? ''));
                if ($value !== '') {
                    $values[$value] = ['value' => $value];
                }
            }
        }

        if ($saltField !== null) {
            $sBuilder = clone $builder;
            $sBuilder->select($saltField . ' as value')->where($saltField . ' !=', '');
            if ($q !== '') {
                $sBuilder->like($saltField, $q);
            }
            foreach ($sBuilder->orderBy($saltField, 'ASC')->limit(40)->get()->getResultArray() as $row) {
                $value = trim((string) ($row['value'] ?? ''));
                if ($value !== '') {
                    $values[$value] = ['value' => $value];
                }
            }
        }

        return $this->response->setJSON(['rows' => array_values($values)]);
    }

    public function rx_group_dose_masters()
    {
        return $this->response->setJSON([
            'dose' => $this->getDoseMasterRows('opd_dose_shed', ['dose_shed_id', 'id'], ['dose_show_sign', 'dose_sign', 'dose_sign_desc', 'name']),
            'when' => $this->getDoseMasterRows('opd_dose_when', ['dose_when_id', 'id'], ['dose_sign', 'dose_sign_desc', 'name']),
            'freq' => $this->getDoseMasterRows('opd_dose_frequency', ['dose_freq_id', 'id'], ['dose_sign', 'dose_sign_desc', 'name']),
            'where' => $this->getDoseMasterRows('opd_dose_where', ['dose_where_id', 'id'], ['dose_sign', 'dose_sign_desc', 'name']),
        ]);
    }

    public function opd_medicince()
    {
        return view('billing/opd_medicince_workspace', ['initial_med_id' => 0]);
    }

    public function opd_medicince_edit(int $medId)
    {
        return view('billing/opd_medicince_workspace', ['initial_med_id' => max(0, $medId)]);
    }

    public function opd_medicince_data()
    {
        $filter = trim((string) $this->request->getGet('filter'));
        $scope = strtolower(trim((string) $this->request->getGet('scope')));
        $showAll = (int) $this->request->getGet('show_all') === 1;
        if (! in_array($scope, ['all', 'favorite', 'active'], true)) {
            $scope = 'all';
        }

        return $this->response->setJSON([
            'rows' => $this->getOpdMedicineRows($filter, $scope, $showAll),
            'active_filter' => $filter,
            'active_scope' => $scope,
            'show_all' => $showAll ? 1 : 0,
        ]);
    }

    public function opd_medicince_export()
    {
        $filter = trim((string) $this->request->getGet('filter'));
        $rows = $this->getOpdMedicineRows($filter);

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return $this->response->setStatusCode(500)->setBody('Unable to generate export file.');
        }

        fputcsv($output, ['ID', 'Medicine Name', 'Formulation', 'Generic Name', 'Salt Name', 'Company Name']);
        foreach ($rows as $row) {
            fputcsv($output, [
                (int) ($row['id'] ?? 0),
                (string) ($row['item_name'] ?? ''),
                (string) ($row['formulation'] ?? ''),
                (string) ($row['genericname'] ?? ''),
                (string) ($row['salt_name'] ?? ''),
                (string) ($row['company_name'] ?? ''),
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        if ($csv === false) {
            return $this->response->setStatusCode(500)->setBody('Unable to generate export file.');
        }

        $suffix = $filter !== '' ? preg_replace('/[^a-z0-9_\-]/i', '_', strtolower($filter)) : 'all';
        $filename = 'opd_medicine_' . $suffix . '_' . date('Ymd_His') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    private function getOpdMedicineRows(string $filter = '', string $scope = 'all', bool $showAll = false): array
    {
        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null) {
            return [];
        }

        $fields = $this->db->getFieldNames($table);
        $idField = $this->resolveFirstField($fields, ['id']);
        $nameField = $this->resolveFirstField($fields, ['item_name', 'med_name']);
        $genericField = $this->resolveFirstField($fields, ['genericname', 'generic_name']);
        $saltField = $this->resolveFirstField($fields, ['salt_name', 'sal_name', 'salt', 'saltname']);
        $companyField = $this->resolveFirstField($fields, ['company_name', 'company']);
        $restrictionField = $this->resolveFirstField($fields, ['dosage_restriction', 'dose_restriction', 'restriction_note', 'restriction']);
        if ($idField === null || $nameField === null) {
            return [];
        }

        $select = $idField . ' as id,' . $nameField . ' as item_name';
        if (in_array('formulation', $fields, true)) {
            $select .= ',formulation';
        }
        if ($genericField !== null) {
            $select .= ',' . $genericField . ' as genericname';
        }
        if ($saltField !== null) {
            $select .= ',' . $saltField . ' as salt_name';
        }
        if ($companyField !== null) {
            $select .= ',' . $companyField . ' as company_name';
        }
        if ($restrictionField !== null) {
            $select .= ',' . $restrictionField . ' as dosage_restriction';
        }

        $rows = $this->db->table($table)
            ->select($select)
            ->orderBy($nameField, 'ASC')
            ->limit(5000)
            ->get()
            ->getResultArray();

        $unclearTokens = ['na', 'n/a', 'same', '-', '--', 'unknown', '?'];
        $normalize = static function ($value): string {
            $text = trim((string) $value);
            if ($text === '') {
                return '';
            }
            $text = preg_replace('/\s+/', ' ', $text) ?? $text;
            return mb_strtolower($text);
        };

        if ($filter === 'generic_issue') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($normalize, $unclearTokens): bool {
                $name = $normalize($row['item_name'] ?? '');
                $generic = $normalize($row['genericname'] ?? '');
                $salt = $normalize($row['salt_name'] ?? '');

                $genericUnclear = ($generic === '' || in_array($generic, $unclearTokens, true) || ($name !== '' && $generic === $name));
                $saltUnclear = ($salt === '' || in_array($salt, $unclearTokens, true) || ($name !== '' && $salt === $name));

                return $genericUnclear && $saltUnclear;
            }));
        } elseif ($filter === 'generic_same_name') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($normalize): bool {
                $name = $normalize($row['item_name'] ?? '');
                $generic = $normalize($row['genericname'] ?? '');
                $salt = $normalize($row['salt_name'] ?? '');
                if ($name === '') {
                    return false;
                }
                return ($generic !== '' && $generic === $name) || ($salt !== '' && $salt === $name);
            }));
        } elseif ($filter === 'company_blank') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($normalize, $unclearTokens): bool {
                $company = $normalize($row['company_name'] ?? '');
                return $company === '' || in_array($company, $unclearTokens, true);
            }));
        }

        foreach ($rows as &$row) {
            $generic = trim((string) ($row['genericname'] ?? ''));
            if ($generic === '') {
                $salt = trim((string) ($row['salt_name'] ?? ''));
                if ($salt !== '') {
                    $row['genericname'] = $salt;
                }
            }
        }
        unset($row);

        $userId = (int) ($this->getCurrentUserId() ?? 0);
        $favoriteMap = $userId > 0 ? $this->getUserFavoriteMedicineIds($userId) : [];
        $usageMap = $userId > 0 ? $this->getUserMedicineUsageMap($userId) : [];

        foreach ($rows as &$row) {
            $medId = (int) ($row['id'] ?? 0);
            $row['is_favorite'] = isset($favoriteMap[$medId]) ? 1 : 0;
            $row['use_count'] = (int) ($usageMap[$medId]['use_count'] ?? 0);
            $row['last_used_at'] = (string) ($usageMap[$medId]['last_used_at'] ?? '');
        }
        unset($row);

        if (! $showAll) {
            if ($scope === 'favorite') {
                $rows = array_values(array_filter($rows, static fn(array $row): bool => (int) ($row['is_favorite'] ?? 0) === 1));
            } elseif ($scope === 'active') {
                $rows = array_values(array_filter($rows, static fn(array $row): bool => ((int) ($row['is_favorite'] ?? 0) === 1) || ((int) ($row['use_count'] ?? 0) > 0)));
            }
        }

        return $rows;
    }

    public function opd_medicince_company_suggest()
    {
        if (! $this->db->tableExists('med_company')) {
            return $this->response->setJSON(['rows' => []]);
        }

        $term = trim((string) $this->request->getGet('term'));
        $fields = $this->db->getFieldNames('med_company');
        $nameField = $this->resolveFirstField($fields, ['company_name', 'name', 'short_name']);
        if ($nameField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('med_company')->select($nameField . ' as company_name')->orderBy($nameField, 'ASC');
        if ($term !== '') {
            $builder->like($nameField, $term);
        }

        $rows = $builder->limit(200)->get()->getResultArray();
        $names = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['company_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $names[$name] = ['company_name' => $name];
        }

        return $this->response->setJSON(['rows' => array_values($names)]);
    }

    public function opd_medicince_get(int $medId)
    {
        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine table not found']);
        }
        $row = $this->db->table($table)->where('id', (int) $medId)->get(1)->getRowArray() ?? [];
        if (empty($row)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine not found']);
        }
        return $this->response->setJSON(['update' => 1, 'row' => $row]);
    }

    public function opd_medicince_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }
        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine table not found']);
        }

        $id = (int) $this->request->getPost('id');
        $itemName = trim((string) $this->request->getPost('item_name'));
        if ($itemName === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine name is required']);
        }

        $medicineModel = new OpdMedicineModel($this->db);
        $duplicate = $medicineModel->findDuplicateByName($itemName, $id);
        if (! empty($duplicate)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Duplicate medicine name found (ID: ' . (int) ($duplicate['id'] ?? 0) . '). Please use existing entry or merge duplicates.',
                'duplicate_id' => (int) ($duplicate['id'] ?? 0),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $fields = $this->db->getFieldNames($table);
        $genericField = $this->resolveFirstField($fields, ['genericname', 'generic_name']);
        $saltField = $this->resolveFirstField($fields, ['salt_name', 'sal_name', 'salt', 'saltname']);
        $companyField = $this->resolveFirstField($fields, ['company_name', 'company']);
        $restrictionField = $this->resolveFirstField($fields, ['dosage_restriction', 'dose_restriction', 'restriction_note', 'restriction']);

        $data = [];
        if (in_array('item_name', $fields, true)) {
            $data['item_name'] = $itemName;
        }
        if (in_array('formulation', $fields, true)) {
            $data['formulation'] = trim((string) $this->request->getPost('formulation'));
        }
        if ($genericField !== null) {
            $data[$genericField] = trim((string) $this->request->getPost('genericname'));
        }
        if ($saltField !== null) {
            $data[$saltField] = trim((string) $this->request->getPost('salt_name'));
        }
        if ($companyField !== null) {
            $data[$companyField] = trim((string) $this->request->getPost('company_name'));
        }
        if ($restrictionField !== null) {
            $data[$restrictionField] = trim((string) $this->request->getPost('dosage_restriction'));
        }

        if ($id > 0) {
            $this->db->table($table)->where('id', $id)->update($data);
            $savedId = $id;
            $message = 'Medicine updated';
        } else {
            $this->db->table($table)->insert($data);
            $savedId = (int) $this->db->insertID();
            $message = 'Medicine added';
        }

        return $this->response->setJSON([
            'update' => 1,
            'insertid' => $savedId,
            'error_text' => $message,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_medicince_ai_details()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $name = trim((string) $this->request->getPost('item_name'));
        $formulation = trim((string) $this->request->getPost('formulation'));
        if ($name === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine name is required']);
        }

        $prompt = "You are a medicine data enrichment assistant for OPD master data in India.\n"
            . "For this medicine, provide structured details based on public medical references and standard labeling guidance.\n"
            . "Medicine Name: {$name}\n"
            . "Formulation: {$formulation}\n"
            . "Return ONLY valid JSON object with keys: genericname, salt_name, dosage_restriction, caution_note.\n"
            . "Use short plain text values. If unsure for a field, keep empty string.";

        $this->lastAiProvider = 'none';
        $aiText = $this->generateClinicalTextWithGemini($prompt, 280);
        if ($aiText === null || trim($aiText) === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'AI unavailable. Check AI settings/API key.']);
        }

        $parsed = $this->parseJsonFromAiResponse($aiText);
        if ($parsed === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'AI response parse failed. Try again.']);
        }

        return $this->response->setJSON([
            'update' => 1,
            'provider' => $this->lastAiProvider,
            'details' => [
                'genericname' => trim((string) ($parsed['genericname'] ?? '')),
                'salt_name' => trim((string) ($parsed['salt_name'] ?? '')),
                'dosage_restriction' => trim((string) ($parsed['dosage_restriction'] ?? '')),
                'caution_note' => trim((string) ($parsed['caution_note'] ?? '')),
            ],
            'error_text' => 'AI details prepared',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_medicince_remove(int $medId)
    {
        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine table not found']);
        }
        $this->db->table($table)->where('id', (int) $medId)->delete();
        return $this->response->setJSON(['update' => 1, 'error_text' => 'Medicine removed']);
    }

    public function opd_advice()
    {
        return view('billing/opd_advice_workspace', ['initial_advice_id' => 0]);
    }

    public function opd_advice_edit(int $adviceId)
    {
        return view('billing/opd_advice_workspace', ['initial_advice_id' => max(0, $adviceId)]);
    }

    public function opd_advice_data()
    {
        if (! $this->db->tableExists('opd_advice')) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames('opd_advice');
        $idField = $this->resolveFirstField($fields, ['id', 'Code']);
        $adviceField = $this->resolveFirstField($fields, ['advice', 'Name', 'advice_txt']);
        $hindiField = $this->resolveFirstField($fields, ['advice_hindi', 'advice_local', 'hindi_advice', 'advice_hin']);

        if ($idField === null || $adviceField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $select = $idField . ' as id,' . $adviceField . ' as advice';
        if ($hindiField !== null) {
            $select .= ',' . $hindiField . ' as advice_hindi';
        }

        $rows = $this->db->table('opd_advice')
            ->select($select)
            ->orderBy($adviceField, 'ASC')
            ->limit(5000)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function opd_advice_get(int $adviceId)
    {
        if (! $this->db->tableExists('opd_advice')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice table not found']);
        }

        $fields = $this->db->getFieldNames('opd_advice');
        $idField = $this->resolveFirstField($fields, ['id', 'Code']);
        if ($idField === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice ID field not found']);
        }

        $row = $this->db->table('opd_advice')->where($idField, (int) $adviceId)->get(1)->getRowArray() ?? [];
        if (empty($row)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice not found']);
        }

        return $this->response->setJSON(['update' => 1, 'row' => $row]);
    }

    public function opd_advice_save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('opd_advice')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice table not found']);
        }

        $fields = $this->db->getFieldNames('opd_advice');
        $idField = $this->resolveFirstField($fields, ['id', 'Code']);
        $adviceField = $this->resolveFirstField($fields, ['advice', 'Name', 'advice_txt']);
        $hindiField = $this->resolveFirstField($fields, ['advice_hindi', 'advice_local', 'hindi_advice', 'advice_hin']);

        if ($idField === null || $adviceField === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice fields not found']);
        }

        $id = (int) $this->request->getPost('id');
        $adviceText = trim((string) $this->request->getPost('advice'));
        $adviceHindi = trim((string) $this->request->getPost('advice_hindi'));

        if ($adviceText === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice text is required']);
        }

        $data = [];
        $data[$adviceField] = $adviceText;
        if ($hindiField !== null) {
            $data[$hindiField] = $adviceHindi;
        }

        if ($id > 0) {
            $this->db->table('opd_advice')->where($idField, $id)->update($data);
            $savedId = $id;
            $message = 'Advice updated';
        } else {
            $this->db->table('opd_advice')->insert($data);
            $savedId = (int) $this->db->insertID();
            $message = 'Advice added';
        }

        return $this->response->setJSON([
            'update' => 1,
            'insertid' => $savedId,
            'error_text' => $message,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_advice_translate_ai()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $sourceText = trim((string) $this->request->getPost('advice'));
        $targetLang = strtolower(trim((string) $this->request->getPost('target_lang')));

        if ($sourceText === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'English advice text is required']);
        }

        $supportedLanguages = [
            'hi' => 'Hindi',
            'bn' => 'Bengali',
            'mr' => 'Marathi',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'gu' => 'Gujarati',
            'kn' => 'Kannada',
            'ml' => 'Malayalam',
            'pa' => 'Punjabi',
            'or' => 'Odia',
            'as' => 'Assamese',
            'ur' => 'Urdu',
        ];

        if (! isset($supportedLanguages[$targetLang])) {
            $targetLang = 'hi';
        }

        $targetLanguageName = $supportedLanguages[$targetLang];

        $prompt = "You are a clinical translation assistant for OPD patient advice in India.\n"
            . "Translate the following English patient advice into {$targetLanguageName}.\n"
            . "Keep meaning medically accurate, simple, and patient-friendly.\n"
            . "Return ONLY valid JSON object: {\"translated_text\":\"...\"}.\n"
            . "Do not include markdown, extra keys, or explanation.\n"
            . "English Advice: {$sourceText}";

        $this->lastAiProvider = 'none';
        $aiText = $this->generateClinicalTextWithGemini($prompt, 220);
        if ($aiText === null || trim($aiText) === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'AI unavailable. Check AI settings/API key.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $parsed = $this->parseJsonFromAiResponse($aiText);
        $translatedText = trim((string) ($parsed['translated_text'] ?? ''));

        if ($translatedText === '') {
            $translatedText = trim(strip_tags((string) $aiText));
            $translatedText = preg_replace('/```(?:json)?|```/i', '', $translatedText) ?? $translatedText;
            $translatedText = trim($translatedText);
        }

        if ($translatedText === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Translation failed. Try again.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'provider' => $this->lastAiProvider,
            'target_lang' => $targetLang,
            'target_language_name' => $targetLanguageName,
            'translated_text' => $translatedText,
            'error_text' => 'Advice translated',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_advice_remove(int $adviceId)
    {
        if (! $this->db->tableExists('opd_advice')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice table not found']);
        }

        $fields = $this->db->getFieldNames('opd_advice');
        $idField = $this->resolveFirstField($fields, ['id', 'Code']);
        if ($idField === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice ID field not found']);
        }

        $this->db->table('opd_advice')->where($idField, (int) $adviceId)->delete();
        return $this->response->setJSON(['update' => 1, 'error_text' => 'Advice removed']);
    }

    public function opd_medicince_duplicate_report()
    {
        $medicineModel = new OpdMedicineModel($this->db);
        if (! $medicineModel->tableExists()) {
            return $this->response->setJSON(['rows' => []]);
        }

        return $this->response->setJSON([
            'rows' => $medicineModel->getDuplicateGroups(),
            'update' => 1,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_medicince_merge_duplicates()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $keepId = (int) $this->request->getPost('keep_id');
        $mergeRaw = trim((string) $this->request->getPost('merge_ids'));
        $mergeIds = [];
        if ($mergeRaw !== '') {
            foreach (explode(',', $mergeRaw) as $token) {
                $value = (int) trim($token);
                if ($value > 0) {
                    $mergeIds[] = $value;
                }
            }
        }

        $medicineModel = new OpdMedicineModel($this->db);
        $merged = $medicineModel->mergeDuplicates($keepId, $mergeIds);
        if (! ($merged['ok'] ?? false)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => (string) ($merged['error'] ?? 'Unable to merge duplicates'),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Merged ' . (int) ($merged['merged_count'] ?? 0) . ' duplicate medicine(s).',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function opd_medicince_autofix_duplicates()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $medicineModel = new OpdMedicineModel($this->db);
        if (! $medicineModel->tableExists()) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Medicine table not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $groups = $medicineModel->getDuplicateGroups();
        if (empty($groups)) {
            return $this->response->setJSON([
                'update' => 1,
                'error_text' => 'No duplicate medicine names found.',
                'merged_groups' => 0,
                'merged_rows' => 0,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $mergedGroups = 0;
        $mergedRows = 0;
        foreach ($groups as $group) {
            $keepId = (int) ($group['keep_id'] ?? 0);
            $mergeIds = is_array($group['merge_ids'] ?? null) ? $group['merge_ids'] : [];
            if ($keepId <= 0 || empty($mergeIds)) {
                continue;
            }

            $result = $medicineModel->mergeDuplicates($keepId, $mergeIds);
            if (! ($result['ok'] ?? false)) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => (string) ($result['error'] ?? 'Auto-fix stopped due to merge failure'),
                    'merged_groups' => $mergedGroups,
                    'merged_rows' => $mergedRows,
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $mergedGroups++;
            $mergedRows += (int) ($result['merged_count'] ?? 0);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Auto-fix completed. Merged ' . $mergedRows . ' duplicate medicine row(s) in ' . $mergedGroups . ' group(s).',
            'merged_groups' => $mergedGroups,
            'merged_rows' => $mergedRows,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function scan_text_extract()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $filePath = trim((string) $this->request->getPost('file_path'));
        $scanType = trim((string) $this->request->getPost('scan_type'));
        if ($filePath === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Scan file path is required']);
        }

        $localPath = $this->resolveLocalFilePathFromStoredPath($filePath);
        if ($localPath === null || !is_file($localPath)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Scan file not found']);
        }

        $extracted = $this->extractClinicalTextFromScan($localPath, $scanType);
        if ($extracted === null || trim($extracted) === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to extract report text from this scan currently.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'extracted_text' => trim($extracted),
            'error_text' => 'Scan text extracted',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function patient_scan_history(int $opdId)
    {
        if (! $this->canAccessPrescription()) {
            return $this->response->setStatusCode(403)->setBody('<div class="text-danger small">Access denied.</div>');
        }

        if ($opdId <= 0 || ! $this->db->tableExists('opd_master')) {
            return $this->response->setBody('<div class="text-muted small">No scan history found.</div>');
        }

        $opdRow = $this->db->table('opd_master')->select('p_id')->where('opd_id', $opdId)->get(1)->getRowArray();
        $patientId = (int) ($opdRow['p_id'] ?? 0);
        if ($patientId <= 0) {
            return $this->response->setBody('<div class="text-muted small">No scan history found.</div>');
        }

        return view('billing/opd_scan_history_panel', [
            'scan_history_items' => $this->getPatientScanHistoryForConsult($patientId),
        ]);
    }

    public function fhir_bundle(int $opdId, int $sessionId = 0)
    {
        if (! $this->db->tableExists('opd_fhir_documents')) {
            return $this->response->setStatusCode(404)->setBody('FHIR bundle storage not available.');
        }

        $builder = $this->db->table('opd_fhir_documents')
            ->where('opd_id', (int) $opdId)
            ->where('bundle_type', 'MedicationRequestBundle');

        if ($sessionId > 0) {
            $builder->where('opd_session_id', (int) $sessionId);
        }

        $row = $builder->orderBy('id', 'DESC')->get(1)->getRowArray();
        if (empty($row)) {
            return $this->response->setStatusCode(404)->setBody('FHIR bundle not found.');
        }

        $bundleJson = (string) ($row['bundle_json'] ?? '{}');
        $fileSession = (int) ($row['opd_session_id'] ?? 0);
        $filename = 'opd_' . (int) $opdId . '_session_' . $fileSession . '_fhir.json';

        return $this->response
            ->setHeader('Content-Type', 'application/fhir+json; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($bundleJson);
    }

    public function fhir_bundle_history(int $opdId, int $sessionId = 0)
    {
        if (! $this->db->tableExists('opd_fhir_documents')) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('opd_fhir_documents')
            ->where('opd_id', (int) $opdId)
            ->where('bundle_type', 'MedicationRequestBundle');

        if ($sessionId > 0) {
            $builder->where('opd_session_id', (int) $sessionId);
        }

        $rows = $builder->orderBy('id', 'DESC')->limit(5)->get()->getResultArray();
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) ($row['id'] ?? 0),
                'opd_session_id' => (int) ($row['opd_session_id'] ?? 0),
                'generated_at' => (string) ($row['generated_at'] ?? ''),
                'generated_by' => (string) ($row['generated_by'] ?? ''),
                'download_url' => base_url('Opd_prescription/fhir_bundle/' . (int) $opdId . '/' . (int) ($row['opd_session_id'] ?? 0)),
            ];
        }

        return $this->response->setJSON(['rows' => $result]);
    }

    public function advice_search()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (! $this->db->tableExists('opd_advice')) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames('opd_advice');
        $idField = in_array('id', $fields, true) ? 'id' : (in_array('Code', $fields, true) ? 'Code' : null);
        $textField = in_array('advice', $fields, true) ? 'advice' : (in_array('Name', $fields, true) ? 'Name' : null);

        if ($idField === null || $textField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('opd_advice')
            ->select($idField . ' as id,' . $textField . ' as label');

        if ($q !== '') {
            $builder->like($textField, $q);
        }

        $rows = $builder
            ->orderBy($idField, 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function complaints_search()
    {
        $q = trim((string) $this->request->getGet('q'));
        if ($q === '' || ! $this->db->tableExists('complaints_master')) {
            return $this->response->setJSON(['rows' => []]);
        }

        $rows = $this->db->table('complaints_master')
            ->select('Code as code, Name as name, name_hinglish, show_in_short, ai_hint')
            ->where('is_active', 1)
            ->groupStart()
            ->like('Name', $q)
            ->orLike('name_hinglish', $q)
            ->orLike('keywords', $q)
            ->groupEnd()
            ->orderBy('show_in_short', 'DESC')
            ->orderBy('Name', 'ASC')
            ->limit(20)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function complaints_parse()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $text = trim((string) $this->request->getPost('text'));
        if ($text === '') {
            return $this->response->setJSON(['update' => 0, 'rows' => [], 'error_text' => 'Enter complaint text']);
        }

        $matched = $this->getComplaintMatchesFromText($text);

        return $this->response->setJSON([
            'update' => 1,
            'rows' => $matched,
            'error_text' => empty($matched) ? 'No standard complaints matched.' : 'Complaint terms matched',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function complaints_ai_draft()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $currentText = trim((string) $this->request->getPost('current_text'));
        $complaints = $this->request->getPost('complaints');
        $complaints = is_array($complaints) ? $complaints : [];

        $normalized = [];
        foreach ($complaints as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            $normalized[strtoupper($item)] = $item;
        }

        if ($currentText !== '') {
            foreach ($this->getComplaintMatchesFromText($currentText) as $item) {
                $normalized[strtoupper($item)] = $item;
            }
        }

        if (! empty($normalized) && $this->db->tableExists('complaints_master')) {
            $names = array_values($normalized);
            $hintRows = $this->db->table('complaints_master')
                ->select('Name, ai_hint')
                ->whereIn('Name', $names)
                ->get()
                ->getResultArray();

            foreach ($hintRows as $hintRow) {
                $name = trim((string) ($hintRow['Name'] ?? ''));
                $hint = trim((string) ($hintRow['ai_hint'] ?? ''));
                if ($name !== '' && $hint !== '') {
                    $normalized[strtoupper($name)] = $name . ' (' . $hint . ')';
                }
            }
        }

        $finalComplaints = array_values($normalized);

        $this->lastAiProvider = 'none';
        $geminiDraft = $this->generateComplaintDraftWithGemini($finalComplaints, $currentText);
        if ($geminiDraft !== null && trim($geminiDraft) !== '') {
            $provider = $this->lastAiProvider === 'azure' ? 'azure' : 'gemini';
            $providerLabel = $provider === 'azure' ? 'Azure OpenAI' : 'Gemini';
            return $this->response->setJSON([
                'update' => 1,
                'draft_text' => trim($geminiDraft),
                'error_text' => 'AI draft prepared using ' . $providerLabel,
                'ai_mode' => $provider,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $line = '';
        if (! empty($finalComplaints)) {
            $line = 'Chief complaints: ' . implode(', ', $finalComplaints) . '.';
        }

        $draft = trim($line . ' ' . $currentText);
        if ($draft === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Please add complaint terms first.']);
        }

        return $this->response->setJSON([
            'update' => 1,
            'draft_text' => $draft,
            'error_text' => 'Complaint draft prepared (template mode)',
            'ai_mode' => 'template',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function advice_list($opdId, $sessionId = 0)
    {
        $table = $this->findExistingTable(['opd_prescription_advice']);
        if ($table === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table($table);
        $fields = $this->db->getFieldNames($table);

        if (in_array('opd_pre_id', $fields, true)) {
            if ((int) $sessionId <= 0) {
                return $this->response->setJSON(['rows' => []]);
            }
            $builder->where('opd_pre_id', (int) $sessionId);
        } elseif (in_array('opd_id', $fields, true)) {
            $builder->where('opd_id', (int) $opdId);
        } else {
            return $this->response->setJSON(['rows' => []]);
        }

        if (in_array('id', $fields, true)) {
            $builder->orderBy('id', 'DESC');
        }

        $rows = $builder->get()->getResultArray();
        return $this->response->setJSON(['rows' => $rows]);
    }

    public function advice_add()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $opdId = (int) $this->request->getPost('opd_id');
        $sessionId = (int) $this->request->getPost('opd_session_id');
        $adviceText = trim((string) $this->request->getPost('advice_text'));
        $adviceId = (int) $this->request->getPost('advice_id');

        if ($opdId <= 0 || $adviceText === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice text is required']);
        }

        $table = $this->findExistingTable(['opd_prescription_advice']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice table not found']);
        }

        $sessionId = $this->ensurePrescriptionSession($opdId, $sessionId);
        if ($sessionId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to create prescription session']);
        }

        $fields = $this->db->getFieldNames($table);
        $insert = [];
        if (in_array('opd_id', $fields, true)) {
            $insert['opd_id'] = $opdId;
        }
        if (in_array('opd_pre_id', $fields, true)) {
            $insert['opd_pre_id'] = $sessionId;
        }
        if (in_array('advice_id', $fields, true)) {
            $insert['advice_id'] = $adviceId;
        }
        if (in_array('advice_txt', $fields, true)) {
            $insert['advice_txt'] = $adviceText;
        } elseif (in_array('advice', $fields, true)) {
            $insert['advice'] = $adviceText;
        }

        $this->db->table($table)->insert($insert);
        $insertId = (int) $this->db->insertID();
        $this->auditClinicalUpdate('opd_prescription_advice', 'added', $insertId, null, $insert);

        return $this->response->setJSON([
            'update' => 1,
            'opd_session_id' => $sessionId,
            'error_text' => 'Advice added',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function advice_remove($id)
    {
        $table = $this->findExistingTable(['opd_prescription_advice']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice table not found']);
        }

        $fields = $this->db->getFieldNames($table);
        if (!in_array('id', $fields, true)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Advice id field missing']);
        }

        $row = $this->db->table($table)->where('id', (int) $id)->get(1)->getRowArray() ?? [];
        $this->db->table($table)->where('id', (int) $id)->delete();
        $this->auditClinicalUpdate('opd_prescription_advice', 'removed', (int) $id, $row, null);
        return $this->response->setJSON(['update' => 1, 'error_text' => 'Advice removed']);
    }

    public function investigation_search()
    {
        $q = trim((string) $this->request->getGet('q'));
        if ($q === '' || !$this->db->tableExists('investigation')) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames('investigation');
        $codeField = in_array('Code', $fields, true) ? 'Code' : (in_array('code', $fields, true) ? 'code' : null);
        $nameField = in_array('Name', $fields, true) ? 'Name' : (in_array('name', $fields, true) ? 'name' : null);
        $shortField = in_array('short_name', $fields, true) ? 'short_name' : null;

        if ($codeField === null || $nameField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table('investigation')->select($codeField . ' as code,' . $nameField . ' as name');
        $builder->groupStart()->like($nameField, $q);
        if ($shortField !== null) {
            $builder->orLike($shortField, $q);
        }
        $builder->groupEnd();
        $builder->orderBy('LOWER(' . $this->db->protectIdentifiers($nameField) . ')', 'ASC', false);

        $rows = $builder->limit(20)->get()->getResultArray();
        return $this->response->setJSON(['rows' => $rows]);
    }

    public function investigation_shortcuts()
    {
        $profiles = [];
        $shortTests = [];

        if ($this->db->tableExists('investigation')) {
            $invFields = $this->db->getFieldNames('investigation');
            $codeField = $this->resolveFirstField($invFields, ['Code', 'code']);
            $nameField = $this->resolveFirstField($invFields, ['Name', 'name']);
            $shortField = $this->resolveFirstField($invFields, ['short_name', 'shortName', 'short']);
            $sortField = $this->resolveFirstField($invFields, ['sort_id', 'sort_order', 'id']);

            if ($codeField !== null && $nameField !== null && $shortField !== null) {
                $builder = $this->db->table('investigation')
                    ->select($codeField . ' as code,' . $nameField . ' as name,' . $shortField . ' as short_name')
                    ->where($shortField . ' !=', '')
                    ->where($shortField . ' is not null', null, false)
                    ->limit(200);

                if ($sortField !== null) {
                    $builder->orderBy($shortField, 'ASC')->orderBy($sortField, 'ASC');
                } else {
                    $builder->orderBy($shortField, 'ASC')->orderBy($nameField, 'ASC');
                }

                $shortTests = $builder->get()->getResultArray();
            }
        }

        if ($this->db->tableExists('invprofiles') && $this->db->tableExists('invtprofiles') && $this->db->tableExists('investigation')) {
            $pFields = $this->db->getFieldNames('invprofiles');
            $jFields = $this->db->getFieldNames('invtprofiles');
            $iFields = $this->db->getFieldNames('investigation');

            $pCode = $this->resolveFirstField($pFields, ['Code', 'code']);
            $pName = $this->resolveFirstField($pFields, ['Name', 'name']);
            $jProfileCode = $this->resolveFirstField($jFields, ['ProfileCode', 'profile_code']);
            $jInvestCode = $this->resolveFirstField($jFields, ['InvestigationCode', 'investigation_code']);
            $jPrintOrder = $this->resolveFirstField($jFields, ['printOrder', 'print_order', 'id']);
            $iCode = $this->resolveFirstField($iFields, ['Code', 'code']);
            $iName = $this->resolveFirstField($iFields, ['Name', 'name']);

            if ($pCode !== null && $pName !== null && $jProfileCode !== null && $jInvestCode !== null && $iCode !== null && $iName !== null) {
                $orderExpr = 'p.`' . $pName . '` ASC';
                if ($jPrintOrder !== null) {
                    $orderExpr .= ', j.`' . $jPrintOrder . '` ASC';
                }

                $sql = 'SELECT '
                    . 'p.`' . $pCode . '` AS profile_code, '
                    . 'p.`' . $pName . '` AS profile_name, '
                    . 'i.`' . $iCode . '` AS investigation_code, '
                    . 'i.`' . $iName . '` AS investigation_name '
                    . 'FROM `invprofiles` p '
                    . 'JOIN `invtprofiles` j ON p.`' . $pCode . '` = j.`' . $jProfileCode . '` '
                    . 'JOIN `investigation` i ON i.`' . $iCode . '` = j.`' . $jInvestCode . '` '
                    . 'ORDER BY ' . $orderExpr;

                $rows = $this->db->query($sql)->getResultArray();
                $profileMap = [];

                foreach ($rows as $row) {
                    $profileCode = trim((string) ($row['profile_code'] ?? ''));
                    $profileName = trim((string) ($row['profile_name'] ?? ''));
                    $testCode = trim((string) ($row['investigation_code'] ?? ''));
                    $testName = trim((string) ($row['investigation_name'] ?? ''));

                    if ($profileCode === '' || $profileName === '' || $testName === '') {
                        continue;
                    }

                    if (!isset($profileMap[$profileCode])) {
                        $profileMap[$profileCode] = [
                            'profile_code' => $profileCode,
                            'profile_name' => $profileName,
                            'tests' => [],
                            '_seen' => [],
                        ];
                    }

                    $key = strtolower($testCode . '|' . $testName);
                    if (isset($profileMap[$profileCode]['_seen'][$key])) {
                        continue;
                    }

                    $profileMap[$profileCode]['_seen'][$key] = true;
                    $profileMap[$profileCode]['tests'][] = [
                        'code' => $testCode,
                        'name' => $testName,
                    ];
                }

                foreach ($profileMap as $item) {
                    unset($item['_seen']);
                    $profiles[] = $item;
                }
            }
        }

        return $this->response->setJSON([
            'update' => 1,
            'profiles' => $profiles,
            'short_tests' => $shortTests,
        ]);
    }

    public function investigation_list($opdId, $sessionId = 0)
    {
        $table = $this->findExistingTable(['opd_prescription_investigation']);
        if ($table === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $builder = $this->db->table($table);
        $fields = $this->db->getFieldNames($table);
        if (in_array('opd_pre_id', $fields, true)) {
            if ((int) $sessionId <= 0) {
                return $this->response->setJSON(['rows' => []]);
            }
            $builder->where('opd_pre_id', (int) $sessionId);
        } elseif (in_array('opd_id', $fields, true)) {
            $builder->where('opd_id', (int) $opdId);
        } else {
            return $this->response->setJSON(['rows' => []]);
        }

        if (in_array('investigation_name', $fields, true)) {
            $builder->orderBy('investigation_name', 'ASC');
        } elseif (in_array('id', $fields, true)) {
            $builder->orderBy('id', 'DESC');
        }

        $rows = $builder->get()->getResultArray();
        return $this->response->setJSON(['rows' => $rows]);
    }

    public function investigation_add()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $opdId = (int) $this->request->getPost('opd_id');
        $sessionId = (int) $this->request->getPost('opd_session_id');
        $code = trim((string) $this->request->getPost('investigation_code'));
        $name = trim((string) $this->request->getPost('investigation_name'));

        if ($opdId <= 0 || ($code === '' && $name === '')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Investigation is required']);
        }

        $table = $this->findExistingTable(['opd_prescription_investigation']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Investigation table not found']);
        }

        $sessionId = $this->ensurePrescriptionSession($opdId, $sessionId);
        if ($sessionId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to create prescription session']);
        }

        $fields = $this->db->getFieldNames($table);
        $insert = [];
        if (in_array('opd_id', $fields, true)) {
            $insert['opd_id'] = $opdId;
        }
        if (in_array('opd_pre_id', $fields, true)) {
            $insert['opd_pre_id'] = $sessionId;
        }
        if (in_array('investigation_code', $fields, true)) {
            $insert['investigation_code'] = $code;
        }
        if (in_array('investigation_name', $fields, true)) {
            $insert['investigation_name'] = $name;
        }

        $this->db->table($table)->insert($insert);
        $insertId = (int) $this->db->insertID();
        $this->auditClinicalUpdate('opd_prescription_investigation', 'added', $insertId, null, $insert);
        return $this->response->setJSON([
            'update' => 1,
            'opd_session_id' => $sessionId,
            'error_text' => 'Investigation added',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function investigation_remove($id)
    {
        $table = $this->findExistingTable(['opd_prescription_investigation']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Investigation table not found']);
        }

        $fields = $this->db->getFieldNames($table);
        if (!in_array('id', $fields, true)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Investigation id field missing']);
        }

        $row = $this->db->table($table)->where('id', (int) $id)->get(1)->getRowArray() ?? [];
        $this->db->table($table)->where('id', (int) $id)->delete();
        $this->auditClinicalUpdate('opd_prescription_investigation', 'removed', (int) $id, $row, null);
        return $this->response->setJSON(['update' => 1, 'error_text' => 'Investigation removed']);
    }

    public function medicine_search()
    {
        $q = trim((string) $this->request->getGet('q'));
        $scope = strtolower(trim((string) $this->request->getGet('scope')));
        if (! in_array($scope, ['active', 'favorite', 'all'], true)) {
            $scope = 'active';
        }

        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames($table);
        $idField = in_array('id', $fields, true) ? 'id' : null;
        $nameField = in_array('item_name', $fields, true) ? 'item_name' : null;
        $formField = in_array('formulation', $fields, true) ? 'formulation' : null;

        if ($idField === null || $nameField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $select = $idField . ' as id,' . $nameField . ' as med_name';
        if ($formField !== null) {
            $select .= ',' . $formField . ' as med_type';
        }

        $builder = $this->db->table($table)->select($select);
        if ($q !== '') {
            $builder->groupStart()
                ->like($nameField, $q);
            if ($formField !== null) {
                $builder->orLike($formField, $q);
            }
            $builder->groupEnd();
        }

        $rows = $builder
            ->limit(500)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return $this->response->setJSON(['rows' => []]);
        }

        $userId = (int) ($this->getCurrentUserId() ?? 0);
        $favoriteMap = $userId > 0 ? $this->getUserFavoriteMedicineIds($userId) : [];
        $usageMap = $userId > 0 ? $this->getUserMedicineUsageMap($userId) : [];
        $qUpper = strtoupper($q);

        foreach ($rows as &$row) {
            $medId = (int) ($row['id'] ?? 0);
            $name = trim((string) ($row['med_name'] ?? ''));
            $nameUpper = strtoupper($name);

            $isFavorite = isset($favoriteMap[$medId]);
            $useCount = (int) ($usageMap[$medId]['use_count'] ?? 0);
            $lastUsedAt = (string) ($usageMap[$medId]['last_used_at'] ?? '');

            $score = 0;
            if ($isFavorite) {
                $score += 1000;
            }
            if ($useCount > 0) {
                $score += min(300, $useCount * 10);
            }
            if ($lastUsedAt !== '') {
                $daysAgo = (int) floor((time() - strtotime($lastUsedAt)) / 86400);
                if ($daysAgo <= 7) {
                    $score += 120;
                } elseif ($daysAgo <= 30) {
                    $score += 80;
                } elseif ($daysAgo <= 90) {
                    $score += 40;
                } elseif ($daysAgo <= 180) {
                    $score += 20;
                }
            }

            if ($qUpper !== '') {
                if (str_starts_with($nameUpper, $qUpper)) {
                    $score += 200;
                } elseif (str_contains($nameUpper, $qUpper)) {
                    $score += 100;
                }
            }

            $row['is_favorite'] = $isFavorite ? 1 : 0;
            $row['use_count'] = $useCount;
            $row['last_used_at'] = $lastUsedAt;
            $row['_score'] = $score;
        }
        unset($row);

        if ($scope === 'favorite') {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => (int) ($row['is_favorite'] ?? 0) === 1));
        } elseif ($scope === 'active') {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => ((int) ($row['is_favorite'] ?? 0) === 1) || ((int) ($row['use_count'] ?? 0) > 0)));
        }

        usort($rows, static function (array $a, array $b): int {
            $scoreA = (int) ($a['_score'] ?? 0);
            $scoreB = (int) ($b['_score'] ?? 0);
            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            $nameA = strtoupper(trim((string) ($a['med_name'] ?? '')));
            $nameB = strtoupper(trim((string) ($b['med_name'] ?? '')));
            return $nameA <=> $nameB;
        });

        $rows = array_slice($rows, 0, 40);
        foreach ($rows as &$row) {
            unset($row['_score']);
        }
        unset($row);

        return $this->response->setJSON(['rows' => $rows]);
    }

    public function medicine_substitutes()
    {
        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames($table);
        $idField = $this->resolveFirstField($fields, ['id']);
        $nameField = $this->resolveFirstField($fields, ['item_name', 'med_name']);
        $formField = $this->resolveFirstField($fields, ['formulation']);
        $genericField = $this->resolveFirstField($fields, ['genericname', 'generic_name']);
        $saltField = $this->resolveFirstField($fields, ['salt_name', 'sal_name', 'salt', 'saltname']);
        $companyField = $this->resolveFirstField($fields, ['company_name', 'company']);

        if ($idField === null || $nameField === null || ($genericField === null && $saltField === null)) {
            return $this->response->setJSON(['rows' => []]);
        }

        $medId = (int) $this->request->getGet('med_id');
        $medName = trim((string) $this->request->getGet('med_name'));
        $current = [];

        if ($medId > 0) {
            $current = $this->db->table($table)->where($idField, $medId)->get(1)->getRowArray() ?? [];
        }
        if (empty($current) && $medName !== '') {
            $current = $this->db->table($table)->where('UPPER(' . $nameField . ')', strtoupper($medName))->get(1)->getRowArray() ?? [];
            if (! empty($current)) {
                $medId = (int) ($current[$idField] ?? 0);
            }
        }

        if (empty($current)) {
            return $this->response->setJSON(['rows' => []]);
        }

        $generic = $genericField !== null ? trim((string) ($current[$genericField] ?? '')) : '';
        $salt = $saltField !== null ? trim((string) ($current[$saltField] ?? '')) : '';
        if ($generic === '' && $salt === '') {
            return $this->response->setJSON(['rows' => []]);
        }

        $select = $idField . ' as id,' . $nameField . ' as med_name';
        if ($formField !== null) {
            $select .= ',' . $formField . ' as med_type';
        }
        if ($genericField !== null) {
            $select .= ',' . $genericField . ' as genericname';
        }
        if ($saltField !== null) {
            $select .= ',' . $saltField . ' as salt_name';
        }
        if ($companyField !== null) {
            $select .= ',' . $companyField . ' as company_name';
        }

        $builder = $this->db->table($table)->select($select);
        $builder->groupStart();
        if ($genericField !== null && $generic !== '') {
            $builder->where($genericField, $generic);
        }
        if ($saltField !== null && $salt !== '') {
            if ($genericField !== null && $generic !== '') {
                $builder->orWhere($saltField, $salt);
            } else {
                $builder->where($saltField, $salt);
            }
        }
        $builder->groupEnd();

        if ($medId > 0) {
            $builder->where($idField . ' !=', $medId);
        }

        $rows = $builder->orderBy($nameField, 'ASC')->limit(25)->get()->getResultArray();
        return $this->response->setJSON(['rows' => $rows]);
    }

    public function medicine_favorite_toggle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $userId = (int) ($this->getCurrentUserId() ?? 0);
        $medId = (int) $this->request->getPost('med_id');
        if ($userId <= 0 || $medId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid user or medicine']);
        }

        if (! $this->db->tableExists('opd_medicine_favorites')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine favorites table not found. Run migration first.']);
        }

        $exists = $this->db->table('opd_medicine_favorites')
            ->where('user_id', $userId)
            ->where('med_id', $medId)
            ->get(1)
            ->getRowArray();

        if ($exists) {
            $this->db->table('opd_medicine_favorites')
                ->where('user_id', $userId)
                ->where('med_id', $medId)
                ->delete();

            return $this->response->setJSON([
                'update' => 1,
                'is_favorite' => 0,
                'error_text' => 'Removed from favorites',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $this->db->table('opd_medicine_favorites')->insert([
            'user_id' => $userId,
            'med_id' => $medId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'update' => 1,
            'is_favorite' => 1,
            'error_text' => 'Added to favorites',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function medicine_list($sessionId)
    {
        $table = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($table === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $fields = $this->db->getFieldNames($table);
        $builder = $this->db->table($table);
        if (in_array('opd_pre_id', $fields, true)) {
            if ((int) $sessionId <= 0) {
                return $this->response->setJSON(['rows' => []]);
            }
            $builder->where('opd_pre_id', (int) $sessionId);
        } else {
            return $this->response->setJSON(['rows' => []]);
        }
        if (in_array('id', $fields, true)) {
            $builder->orderBy('id', 'DESC');
        }

        $rows = $builder->get()->getResultArray();
        return $this->response->setJSON(['rows' => $rows]);
    }

    public function medicine_add()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $opdId = (int) $this->request->getPost('opd_id');
        $sessionId = (int) $this->request->getPost('opd_session_id');
        $medName = trim((string) $this->request->getPost('med_name'));
        $medType = trim((string) $this->request->getPost('med_type'));
        $medId = (int) $this->request->getPost('med_id');

        if ($opdId <= 0 || $medName === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine name is required']);
        }

        $table = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine table not found']);
        }

        $sessionId = $this->ensurePrescriptionSession($opdId, $sessionId);
        if ($sessionId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to create prescription session']);
        }

        if ($medId <= 0) {
            $medId = $this->resolveMedicineMasterIdByName($medName);
        }

        $fields = $this->db->getFieldNames($table);
        $insert = [];
        if (in_array('opd_pre_id', $fields, true)) {
            $insert['opd_pre_id'] = $sessionId;
        }
        if (in_array('med_id', $fields, true) && $medId > 0) {
            $insert['med_id'] = $medId;
        }
        if (in_array('med_name', $fields, true)) {
            $insert['med_name'] = strtoupper($medName);
        }
        if (in_array('med_type', $fields, true)) {
            $insert['med_type'] = $medType;
        }
        if (in_array('dosage', $fields, true)) {
            $insert['dosage'] = trim((string) $this->request->getPost('dosage'));
        }
        if (in_array('dosage_when', $fields, true)) {
            $insert['dosage_when'] = trim((string) $this->request->getPost('dosage_when'));
        }
        if (in_array('dosage_freq', $fields, true)) {
            $insert['dosage_freq'] = trim((string) $this->request->getPost('dosage_freq'));
        }
        if (in_array('no_of_days', $fields, true)) {
            $insert['no_of_days'] = trim((string) $this->request->getPost('no_of_days'));
        }
        if (in_array('qty', $fields, true)) {
            $insert['qty'] = trim((string) $this->request->getPost('qty'));
        }
        if (in_array('remark', $fields, true)) {
            $insert['remark'] = strtoupper(trim((string) $this->request->getPost('remark')));
        }

        $user = auth()->user();
        if (in_array('update_by', $fields, true)) {
            $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
            $insert['update_by'] = $userLabel . '[' . ($user->id ?? 0) . ']:' . date('d-m-Y H:i:s');
        }

        $this->db->table($table)->insert($insert);
        $insertId = (int) $this->db->insertID();
        $this->auditClinicalUpdate('opd_prescription_medicine', 'added', $insertId, null, $insert);
        if ($medId > 0) {
            $this->trackMedicineUsage($medId);
        }

        return $this->response->setJSON([
            'update' => 1,
            'opd_session_id' => $sessionId,
            'error_text' => 'Medicine added',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function resolveMedicineMasterIdByName(string $medName): int
    {
        $medName = trim($medName);
        if ($medName === '') {
            return 0;
        }

        $table = $this->findExistingTable(['opd_med_master']);
        if ($table === null) {
            return 0;
        }

        $fields = $this->db->getFieldNames($table);
        if (! in_array('id', $fields, true)) {
            return 0;
        }

        $nameField = in_array('item_name', $fields, true) ? 'item_name' : (in_array('med_name', $fields, true) ? 'med_name' : null);
        if ($nameField === null) {
            return 0;
        }

        $row = $this->db->table($table)
            ->select('id')
            ->where('UPPER(' . $nameField . ')', strtoupper($medName))
            ->get(1)
            ->getRowArray();

        return (int) ($row['id'] ?? 0);
    }

    private function trackMedicineUsage(int $medId): void
    {
        $userId = (int) ($this->getCurrentUserId() ?? 0);
        if ($userId <= 0 || $medId <= 0 || ! $this->db->tableExists('opd_medicine_usage')) {
            return;
        }

        $existing = $this->db->table('opd_medicine_usage')
            ->where('user_id', $userId)
            ->where('med_id', $medId)
            ->get(1)
            ->getRowArray();

        if ($existing) {
            $this->db->table('opd_medicine_usage')
                ->where('user_id', $userId)
                ->where('med_id', $medId)
                ->update([
                    'use_count' => (int) ($existing['use_count'] ?? 0) + 1,
                    'last_used_at' => date('Y-m-d H:i:s'),
                ]);
            return;
        }

        $this->db->table('opd_medicine_usage')->insert([
            'user_id' => $userId,
            'med_id' => $medId,
            'use_count' => 1,
            'last_used_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<int, bool>
     */
    private function getUserFavoriteMedicineIds(int $userId): array
    {
        if ($userId <= 0 || ! $this->db->tableExists('opd_medicine_favorites')) {
            return [];
        }

        $rows = $this->db->table('opd_medicine_favorites')
            ->select('med_id')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $medId = (int) ($row['med_id'] ?? 0);
            if ($medId > 0) {
                $out[$medId] = true;
            }
        }

        return $out;
    }

    /**
     * @return array<int, array{use_count:int,last_used_at:string}>
     */
    private function getUserMedicineUsageMap(int $userId): array
    {
        if ($userId <= 0 || ! $this->db->tableExists('opd_medicine_usage')) {
            return [];
        }

        $rows = $this->db->table('opd_medicine_usage')
            ->select('med_id,use_count,last_used_at')
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $medId = (int) ($row['med_id'] ?? 0);
            if ($medId <= 0) {
                continue;
            }

            $out[$medId] = [
                'use_count' => (int) ($row['use_count'] ?? 0),
                'last_used_at' => (string) ($row['last_used_at'] ?? ''),
            ];
        }

        return $out;
    }

    public function medicine_update($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid medicine id']);
        }

        $table = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine table not found']);
        }

        $fields = $this->db->getFieldNames($table);
        if (!in_array('id', $fields, true)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine id field missing']);
        }

        $current = $this->db->table($table)->where('id', $id)->get(1)->getRowArray();
        if (!$current) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine row not found']);
        }

        $update = [];
        $map = [
            'med_name' => strtoupper(trim((string) $this->request->getPost('med_name'))),
            'med_type' => trim((string) $this->request->getPost('med_type')),
            'dosage' => trim((string) $this->request->getPost('dosage')),
            'dosage_when' => trim((string) $this->request->getPost('dosage_when')),
            'dosage_freq' => trim((string) $this->request->getPost('dosage_freq')),
            'no_of_days' => trim((string) $this->request->getPost('no_of_days')),
            'qty' => trim((string) $this->request->getPost('qty')),
            'remark' => strtoupper(trim((string) $this->request->getPost('remark'))),
        ];

        foreach ($map as $field => $value) {
            if (in_array($field, $fields, true)) {
                $update[$field] = $value;
            }
        }

        if (empty($update)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'No editable fields found']);
        }

        if (isset($update['med_name']) && $update['med_name'] === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine name is required']);
        }

        $user = auth()->user();
        if (in_array('update_by', $fields, true)) {
            $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
            $update['update_by'] = $userLabel . '[' . ($user->id ?? 0) . ']:' . date('d-m-Y H:i:s');
        }

        $this->db->table($table)->where('id', $id)->update($update);
        $this->clinicalAuditTrail->logChangedFields('opd_prescription_medicine', $id, $current, array_replace($current, $update), $this->getCurrentUserId());

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Medicine updated',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function medicine_remove($id)
    {
        $table = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($table === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine table not found']);
        }

        $fields = $this->db->getFieldNames($table);
        if (!in_array('id', $fields, true)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Medicine id field missing']);
        }

        $row = $this->db->table($table)->where('id', (int) $id)->get(1)->getRowArray() ?? [];
        $this->db->table($table)->where('id', (int) $id)->delete();
        $this->auditClinicalUpdate('opd_prescription_medicine', 'removed', (int) $id, $row, null);
        return $this->response->setJSON(['update' => 1, 'error_text' => 'Medicine removed']);
    }

    public function vital_data(int $opdSessionId)
    {
        if (! $this->db->tableExists('opd_prescription')) {
            return $this->response->setBody('');
        }

        $row = $this->db->table('opd_prescription')->where('id', $opdSessionId)->get(1)->getRowArray();
        if (empty($row)) {
            return $this->response->setBody('');
        }

        $lines = [];
        if (!empty($row['bp'])) {
            $lines[] = '<span class="text-success">BP :</span> ' . esc($row['bp']) . ' / ' . esc((string) ($row['diastolic'] ?? ''));
        }
        if (!empty($row['pulse'])) {
            $lines[] = '<span class="text-success">Pulse :</span> ' . esc($row['pulse']);
        }
        if (!empty($row['temp'])) {
            $lines[] = '<span class="text-success">Temp :</span> ' . esc($row['temp']);
        }
        if (!empty($row['spo2'])) {
            $lines[] = '<span class="text-success">SpO2 :</span> ' . esc($row['spo2']) . ' %';
        }

        return $this->response->setBody('<p class="text-primary">' . implode('<br>', $lines) . '</p>');
    }

    public function get_remark(int $pId)
    {
        if (! $this->db->tableExists('patient_remark')) {
            return $this->response->setBody('');
        }

        $rows = $this->db->table('patient_remark')
            ->select('remark,insert_by,insert_datetime')
            ->where('p_id', $pId)
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return $this->response->setBody('<p class="text-muted">No patient remarks found.</p>');
        }

        $out = '';
        foreach ($rows as $row) {
            $stamp = !empty($row['insert_datetime']) ? date('d-m-Y H:i', strtotime((string) $row['insert_datetime'])) : '';
            $out .= '<p>' . nl2br(esc((string) ($row['remark'] ?? ''))) . '<br><small>Update By: ' . esc((string) ($row['insert_by'] ?? '')) . ' ' . esc($stamp) . '</small></p>';
        }

        return $this->response->setBody($out);
    }

    public function patient_remark(int $pId)
    {
        return $this->get_remark($pId);
    }

    public function show_profile_opd(int $pId, int $opdSessionId = 0)
    {
        return redirect()->to(base_url('patient/show_profile_opd/' . $pId . '/' . $opdSessionId));
    }

    public function show_medical_item(int $pId)
    {
        if (! $this->db->tableExists('invoice_med_master')) {
            return $this->response->setBody('<p class="text-muted">Medical purchase table not found.</p>');
        }

        $rows = $this->db->table('invoice_med_master')
            ->where('patient_id', $pId)
            ->orderBy('id', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return $this->response->setBody('<p class="text-muted">No medicine purchase found.</p>');
        }

        $html = '<table class="table table-bordered table-sm"><thead><tr><th>Date</th><th>Invoice</th><th>Amount</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>'
                . '<td>' . esc((string) ($row['date'] ?? $row['invoice_date'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['invoice_code'] ?? $row['invoice_no'] ?? $row['id'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['net_amount'] ?? $row['total_amount'] ?? '')) . '</td>'
                . '</tr>';
        }
        $html .= '</tbody></table>';

        return $this->response->setBody($html);
    }

    public function show_old_Prescribed(int $pId, int $currentOpd)
    {
        if (! $this->db->tableExists('opd_master') || ! $this->db->tableExists('opd_prescription')) {
            return $this->response->setBody('<p class="text-muted">Old prescription data not found.</p>');
        }

        $rows = $this->db->table('opd_master o')
            ->select('o.opd_id,o.opd_code,o.apointment_date,p.id as session_id,p.complaints,p.diagnosis')
            ->join('opd_prescription p', 'p.opd_id=o.opd_id', 'left')
            ->where('o.p_id', $pId)
            ->where('o.opd_id !=', $currentOpd)
            ->orderBy('o.opd_id', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return $this->response->setBody('<p class="text-muted">No old prescriptions found.</p>');
        }

        $medicineTable = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        $html = '<div class="accordion" id="oldRxAccordion">';
        foreach ($rows as $index => $row) {
            $rxLines = [];
            if ($medicineTable !== null && !empty($row['session_id'])) {
                $medRows = $this->db->table($medicineTable)
                    ->select('med_type,med_name,dosage,dosage_when,dosage_freq,no_of_days,qty,remark')
                    ->where('opd_pre_id', (int) $row['session_id'])
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();
                foreach ($medRows as $med) {
                    $rxLines[] = esc(trim((string) ($med['med_type'] ?? ''))) . ' ' . esc(trim((string) ($med['med_name'] ?? ''))) . ' | '
                        . esc(trim((string) ($med['dosage'] ?? ''))) . ' '
                        . esc(trim((string) ($med['dosage_when'] ?? ''))) . ' '
                        . esc(trim((string) ($med['dosage_freq'] ?? ''))) . ' '
                        . esc(trim((string) ($med['no_of_days'] ?? '')));
                }
            }

            $dateLabel = !empty($row['apointment_date']) ? date('d-m-Y', strtotime((string) $row['apointment_date'])) : '';
            $html .= '<div class="card mb-2">'
                . '<div class="card-header"><strong>' . esc((string) ($row['opd_code'] ?? $row['opd_id'])) . '</strong> <small class="text-muted ms-2">' . esc($dateLabel) . '</small></div>'
                . '<div class="card-body">'
                . '<div><strong>Complaints:</strong> ' . esc((string) ($row['complaints'] ?? '')) . '</div>'
                . '<div><strong>Diagnosis:</strong> ' . esc((string) ($row['diagnosis'] ?? '')) . '</div>'
                . '<div><strong>Rx:</strong><br>' . (empty($rxLines) ? '<span class="text-muted">No medicine rows</span>' : implode('<br>', $rxLines)) . '</div>'
                . '</div></div>';
        }
        $html .= '</div>';

        return $this->response->setBody($html);
    }

    public function save_rx_group_list(int $docId = 0)
    {
        if (! $this->db->tableExists('opd_prescription_template')) {
            return $this->response->setBody('<p class="text-muted">RX template table not found.</p>');
        }

        $fields = $this->db->getFieldNames('opd_prescription_template');
        $idField = $this->resolveFirstField($fields, ['id']);
        $nameField = $this->resolveFirstField($fields, ['rx_group_name']);
        if ($idField === null || $nameField === null) {
            return $this->response->setJSON(['rows' => []]);
        }

        $userId = $docId > 0 ? (int) $docId : max(0, $this->getCurrentUserId());
        $hasDocId = in_array('doc_id', $fields, true);

        $builder = $this->db->table('opd_prescription_template')
            ->select('MAX(' . $idField . ') as id,' . $nameField . ' as rx_group_name,COUNT(*) as row_count')
            ->where($nameField . ' <>', '');

        if ($hasDocId) {
            $builder->groupStart()
                ->where('doc_id', $userId)
                ->orWhere('doc_id', 0)
                ->groupEnd();
        }

        $rows = $builder
            ->groupBy($nameField)
            ->orderBy($nameField, 'ASC')
            ->limit(500)
            ->get()
            ->getResultArray();

        $medTable = $this->findExistingTable(['opd_prescrption_prescribed_template', 'opd_prescription_prescribed_template']);
        $medCountMap = [];
        if ($medTable !== null) {
            $medFields = $this->db->getFieldNames($medTable);
            if (in_array('rx_group_id', $medFields, true)) {
                $medRows = $this->db->table($medTable)
                    ->select('rx_group_id,COUNT(*) as med_count')
                    ->groupBy('rx_group_id')
                    ->get()
                    ->getResultArray();

                foreach ($medRows as $medRow) {
                    $rxId = (int) ($medRow['rx_group_id'] ?? 0);
                    if ($rxId > 0) {
                        $medCountMap[$rxId] = (int) ($medRow['med_count'] ?? 0);
                    }
                }
            }
        }

        foreach ($rows as &$row) {
            $row['med_count'] = (int) ($medCountMap[(int) ($row['id'] ?? 0)] ?? 0);
        }
        unset($row);

        if ($this->request->isAJAX() || strtolower((string) $this->request->getHeaderLine('Accept')) === 'application/json') {
            return $this->response->setJSON(['rows' => $rows]);
        }

        if (empty($rows)) {
            return $this->response->setBody('<p class="text-muted">No RX groups found.</p>');
        }

        $html = '<ul class="list-group">';
        foreach ($rows as $row) {
            $html .= '<li class="list-group-item d-flex justify-content-between align-items-center">'
                . '<span>' . esc((string) ($row['rx_group_name'] ?? 'RX Group')) . '</span>'
                . '<span class="badge bg-secondary">#' . (int) ($row['id'] ?? 0) . ' (' . (int) ($row['med_count'] ?? 0) . ')</span>'
                . '</li>';
        }
        $html .= '</ul>';

        return $this->response->setBody($html);
    }

    public function prescribed_dose(int $opdId, int $opdSessionId)
    {
        $medicineTable = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($medicineTable === null) {
            return $this->response->setBody('<p class="text-muted">No prescribed medicine table found.</p>');
        }

        $rows = $this->db->table($medicineTable)
            ->select('id,med_type,med_name,dosage,dosage_when,dosage_freq,no_of_days,qty,remark')
            ->where('opd_pre_id', $opdSessionId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return $this->response->setBody('<p class="text-muted">No prescribed medicine found for this OPD session.</p>');
        }

        $html = '<table class="table table-bordered table-sm"><thead><tr><th>Type</th><th>Medicine</th><th>Dose</th><th>When</th><th>Freq</th><th>Days</th><th>Qty</th><th>Remark</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>'
                . '<td>' . esc((string) ($row['med_type'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['med_name'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['dosage'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['dosage_when'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['dosage_freq'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['no_of_days'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['qty'] ?? '')) . '</td>'
                . '<td>' . esc((string) ($row['remark'] ?? '')) . '</td>'
                . '</tr>';
        }
        $html .= '</tbody></table>';

        return $this->response->setBody($html);
    }

    private function findExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $table) {
            if ($this->db->tableExists($table)) {
                return $table;
            }
        }
        return null;
    }

    private function getDoseMasterRows(string $table, array $idCandidates, array $labelCandidates): array
    {
        if (! $this->db->tableExists($table)) {
            return [];
        }

        $fields = $this->db->getFieldNames($table);
        $idField = $this->resolveFirstField($fields, $idCandidates);
        $labelField = $this->resolveFirstField($fields, $labelCandidates);
        if ($idField === null || $labelField === null) {
            return [];
        }

        return $this->db->table($table)
            ->select($idField . ' as id,' . $labelField . ' as label')
            ->where($labelField . ' !=', '')
            ->orderBy($labelField, 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getDoseMasterLabelMap(string $table, array $idCandidates, array $labelCandidates): array
    {
        $rows = $this->getDoseMasterRows($table, $idCandidates, $labelCandidates);
        if (empty($rows)) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $id = trim((string) ($row['id'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            if ($id === '' || $label === '') {
                continue;
            }
            $map[$id] = $label;
        }

        return $map;
    }

    private function resolveDoseMasterLabel($value, array $map): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        return $map[$raw] ?? $raw;
    }

    private function hydrateNabhPrescriptionFields(array $row, string $remarks): array
    {
        $pick = function (array $candidates) use ($row): string {
            foreach ($candidates as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
            return '';
        };

        $data = [
            'drug_allergy_status' => $pick(['drug_allergy_status', 'allergy_status', 'drug_allergy']),
            'drug_allergy_details' => $pick(['drug_allergy_details', 'allergy_details', 'drug_allergy_note', 'allergy_note']),
            'adr_history' => $pick(['adr_history', 'adverse_drug_reaction', 'adr_details', 'adverse_reaction_history']),
            'current_medications' => $pick(['current_medications', 'current_medication', 'current_medication_history', 'ongoing_medications']),
        ];

        $parsed = $this->extractNabhFieldsFromRemarks($remarks);
        foreach ($data as $key => $value) {
            if ($value === '' && !empty($parsed[$key])) {
                $data[$key] = (string) $parsed[$key];
            }
        }

        return $data;
    }

    private function mapNabhFieldsToExistingColumns(array &$payload, array $fields, array $input): array
    {
        $mapping = [
            'drug_allergy_status' => ['drug_allergy_status', 'allergy_status', 'drug_allergy'],
            'drug_allergy_details' => ['drug_allergy_details', 'allergy_details', 'drug_allergy_note', 'allergy_note'],
            'adr_history' => ['adr_history', 'adverse_drug_reaction', 'adr_details', 'adverse_reaction_history'],
            'current_medications' => ['current_medications', 'current_medication', 'current_medication_history', 'ongoing_medications'],
        ];

        $unmapped = [];
        foreach ($mapping as $logical => $candidates) {
            $value = trim((string) ($input[$logical] ?? ''));
            $target = null;
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $fields, true)) {
                    $target = $candidate;
                    break;
                }
            }

            if ($target !== null) {
                $payload[$target] = $value;
            } else {
                $unmapped[$logical] = $value;
            }
        }

        return $unmapped;
    }

    private function extractNabhFieldsFromRemarks(string $remarks): array
    {
        $extract = static function (string $pattern, string $source): string {
            if (preg_match($pattern, $source, $m) !== 1) {
                return '';
            }
            return trim((string) ($m[1] ?? ''));
        };

        return [
            'drug_allergy_status' => $extract('/^\s*Drug\s*Allergy\s*Status\s*:\s*(.+)$/im', $remarks),
            'drug_allergy_details' => $extract('/^\s*Drug\s*Allergy\s*Details\s*:\s*(.+)$/im', $remarks),
            'adr_history' => $extract('/^\s*ADR\s*History\s*:\s*(.+)$/im', $remarks),
            'current_medications' => $extract('/^\s*Current\s*Medications\s*:\s*(.+)$/im', $remarks),
        ];
    }

    private function upsertNabhFieldsIntoRemarks(string $remarks, array $input): string
    {
        $clean = trim($remarks);
        $clean = preg_replace('/\n?\s*Drug\s*Allergy\s*Status\s*:\s*.+$/im', '', $clean) ?? $clean;
        $clean = preg_replace('/\n?\s*Drug\s*Allergy\s*Details\s*:\s*.+$/im', '', $clean) ?? $clean;
        $clean = preg_replace('/\n?\s*ADR\s*History\s*:\s*.+$/im', '', $clean) ?? $clean;
        $clean = preg_replace('/\n?\s*Current\s*Medications\s*:\s*.+$/im', '', $clean) ?? $clean;
        $clean = trim($clean);

        $lines = [];
        if (trim((string) ($input['drug_allergy_status'] ?? '')) !== '') {
            $lines[] = 'Drug Allergy Status: ' . trim((string) ($input['drug_allergy_status'] ?? ''));
        }
        if (trim((string) ($input['drug_allergy_details'] ?? '')) !== '') {
            $lines[] = 'Drug Allergy Details: ' . trim((string) ($input['drug_allergy_details'] ?? ''));
        }
        if (trim((string) ($input['adr_history'] ?? '')) !== '') {
            $lines[] = 'ADR History: ' . trim((string) ($input['adr_history'] ?? ''));
        }
        if (trim((string) ($input['current_medications'] ?? '')) !== '') {
            $lines[] = 'Current Medications: ' . trim((string) ($input['current_medications'] ?? ''));
        }

        if (empty($lines)) {
            return $clean;
        }

        if ($clean !== '') {
            return $clean . "\n" . implode("\n", $lines);
        }

        return implode("\n", $lines);
    }

    private function ensureClinicalTemplateTable(): bool
    {
        if ($this->db->tableExists('opd_clinical_templates')) {
            return true;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS opd_clinical_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_id INT NOT NULL DEFAULT 0,
                section_key VARCHAR(80) NOT NULL,
                template_name VARCHAR(255) NOT NULL,
                template_text TEXT NOT NULL,
                is_active TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                INDEX idx_doc_section (doc_id, section_key),
                INDEX idx_section_active (section_key, is_active)
            )";
            $this->db->query($sql);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->db->tableExists('opd_clinical_templates');
    }

    private function ensureClinicalTemplateUsageTable(): bool
    {
        if ($this->db->tableExists('opd_clinical_template_usage')) {
            return true;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS opd_clinical_template_usage (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_id INT NOT NULL,
                template_id INT NOT NULL,
                section_key VARCHAR(80) NOT NULL,
                use_count INT NOT NULL DEFAULT 0,
                last_used_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY uniq_doc_template_section (doc_id, template_id, section_key),
                INDEX idx_template (template_id),
                INDEX idx_section_use (section_key, use_count)
            )";
            $this->db->query($sql);
        } catch (\Throwable $e) {
            return false;
        }

        return $this->db->tableExists('opd_clinical_template_usage');
    }

    private function isTemplateSectionAllowed(string $section): bool
    {
        return in_array($section, ['finding_examinations', 'diagnosis', 'provisional_diagnosis', 'prescriber_remarks', 'advice'], true);
    }

    private function resolveSectionColumn(string $section): ?string
    {
        $map = [
            'finding_examinations' => 'Finding_Examinations',
            'diagnosis' => 'diagnosis',
            'provisional_diagnosis' => 'Provisional_diagnosis',
            'prescriber_remarks' => 'Prescriber_Remarks',
            'advice' => 'advice',
        ];

        return $map[$section] ?? null;
    }

    private function ensurePrescriptionSession(int $opdId, int $sessionId = 0): int
    {
        if (!$this->db->tableExists('opd_prescription')) {
            return 0;
        }

        $fields = $this->db->getFieldNames('opd_prescription');
        if ($sessionId > 0) {
            $row = $this->db->table('opd_prescription')->where('id', $sessionId)->where('opd_id', $opdId)->get(1)->getRow();
            if ($row) {
                return $sessionId;
            }
        }

        $last = $this->db->table('opd_prescription')->where('opd_id', $opdId)->orderBy('id', 'DESC')->get(1)->getRow();
        if ($last && !empty($last->id)) {
            return (int) $last->id;
        }

        $opdRow = $this->db->table('opd_master')->where('opd_id', $opdId)->get(1)->getRow();
        if (!$opdRow) {
            return 0;
        }

        $insert = [];
        if (in_array('opd_id', $fields, true)) {
            $insert['opd_id'] = $opdId;
        }
        if (in_array('p_id', $fields, true)) {
            $insert['p_id'] = (int) ($opdRow->p_id ?? 0);
        }
        if (in_array('doc_id', $fields, true)) {
            $insert['doc_id'] = (int) ($opdRow->doc_id ?? 0);
        }
        if (in_array('date_opd_visit', $fields, true)) {
            $insert['date_opd_visit'] = date('Y-m-d');
        }
        if (in_array('visit_status', $fields, true)) {
            $insert['visit_status'] = 0;
        }
        if (in_array('session_id', $fields, true)) {
            $insert['session_id'] = 0;
        }
        if (in_array('queue_no', $fields, true)) {
            $maxQueue = $this->db->table('opd_prescription')
                ->selectMax('queue_no', 'max_queue')
                ->where('date_opd_visit', date('Y-m-d'))
                ->where('doc_id', (int) ($opdRow->doc_id ?? 0))
                ->get()
                ->getRow();
            $insert['queue_no'] = (int) ($maxQueue->max_queue ?? 0) + 1;
        }

        $this->db->table('opd_prescription')->insert($insert);
        return (int) $this->db->insertID();
    }

    private function isValidAbhaAddress(string $value): bool
    {
        $validation = service('validation');
        $validation->reset();

        return $validation->setRules([
            'abha_address' => 'valid_abha_address',
        ])->run([
            'abha_address' => $value,
        ]);
    }

    private function resolvePatientAbhaField(): ?string
    {
        if (! $this->db->tableExists('patient_master')) {
            return null;
        }

        $fields = $this->db->getFieldNames('patient_master');
        foreach (['abha_address', 'abha', 'abha_id', 'abha_no'] as $field) {
            if (in_array($field, $fields, true)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    private function getPatientAddictionFlags(int $patientId): array
    {
        $flags = [
            'is_smoking' => 0,
            'is_alcohol' => 0,
            'is_drug_abuse' => 0,
        ];

        if ($patientId <= 0 || ! $this->db->tableExists('patient_master')) {
            return $flags;
        }

        $patientFields = $this->db->getFieldNames('patient_master');
        $selectFields = [];
        foreach (array_keys($flags) as $field) {
            if (in_array($field, $patientFields, true)) {
                $selectFields[] = $field;
            }
        }

        if (empty($selectFields)) {
            return $flags;
        }

        $row = $this->db->table('patient_master')
            ->select(implode(',', $selectFields))
            ->where('id', $patientId)
            ->get(1)
            ->getRowArray() ?? [];

        foreach ($flags as $field => $_) {
            if (array_key_exists($field, $row)) {
                $flags[$field] = ((int) $row[$field]) > 0 ? 1 : 0;
            }
        }

        return $flags;
    }

    /**
     * @return array<int, array{id:string|int,name:string,selected:int}>
     */
    private function getCoMorbiditiesWithSelection(int $patientId): array
    {
        if (! $this->db->tableExists('morbidities_master')) {
            return $this->getDefaultCoMorbidityRows($patientId);
        }

        $masterFields = $this->db->getFieldNames('morbidities_master');
        $idField = $this->resolveFirstField($masterFields, ['mor_id', 'id']);
        $nameField = $this->resolveFirstField($masterFields, ['morbidities', 'name', 'title']);
        if ($idField === null || $nameField === null) {
            return [];
        }

        $builder = $this->db->table('morbidities_master')
            ->select($idField . ' as mor_id,' . $nameField . ' as morbidities')
            ->orderBy($nameField, 'ASC');

        if (in_array('active', $masterFields, true)) {
            $builder->where('active', 1);
        }

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return $this->getDefaultCoMorbidityRows($patientId);
        }

        $selectedMap = [];
        if ($patientId > 0 && $this->db->tableExists('patient_morbidities')) {
            $patientMorFields = $this->db->getFieldNames('patient_morbidities');
            $pIdField = $this->resolveFirstField($patientMorFields, ['p_id', 'patient_id']);
            $morIdField = $this->resolveFirstField($patientMorFields, ['morbidities', 'mor_id', 'morbidity_id']);
            if ($pIdField !== null && $morIdField !== null) {
                $selectedRows = $this->db->table('patient_morbidities')
                    ->select($morIdField . ' as mor_id')
                    ->where($pIdField, $patientId)
                    ->get()
                    ->getResultArray();
                foreach ($selectedRows as $selectedRow) {
                    $selectedMap[(int) ($selectedRow['mor_id'] ?? 0)] = true;
                }
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $morId = (int) ($row['mor_id'] ?? 0);
            if ($morId <= 0) {
                continue;
            }
            $result[] = [
                'id' => $morId,
                'name' => (string) ($row['morbidities'] ?? ''),
                'selected' => ! empty($selectedMap[$morId]) ? 1 : 0,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{id:string|int,name:string,selected:int}>
     */
    private function getDefaultCoMorbidityRows(int $patientId = 0): array
    {
        $historical = $this->getHistoricalCoMorbiditySelection($patientId);

        return [
            ['id' => 'DM', 'name' => 'Diabetes mellitus (DM)', 'selected' => !empty($historical['DM']) ? 1 : 0],
            ['id' => 'HTN', 'name' => 'high blood pressure (HTn)', 'selected' => !empty($historical['HTN']) ? 1 : 0],
            ['id' => 'CAD', 'name' => 'Coronary artery disease (CAD)', 'selected' => !empty($historical['CAD']) ? 1 : 0],
            ['id' => 'COPD', 'name' => 'Chronic Obstructive Pulmonary Disease', 'selected' => !empty($historical['COPD']) ? 1 : 0],
            ['id' => 'CVA', 'name' => 'cerebral vascular accident (CVA)', 'selected' => !empty($historical['CVA']) ? 1 : 0],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function getHistoricalCoMorbiditySelection(int $patientId): array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('opd_prescription')) {
            return [];
        }

        $fields = $this->db->getFieldNames('opd_prescription');
        if (! in_array('Prescriber_Remarks', $fields, true)) {
            return [];
        }

        $rows = $this->db->table('opd_prescription')
            ->select('Prescriber_Remarks')
            ->where('p_id', $patientId)
            ->orderBy('id', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $selected = [];
        foreach ($rows as $row) {
            $remarks = trim((string) ($row['Prescriber_Remarks'] ?? ''));
            if ($remarks === '') {
                continue;
            }

            if (preg_match('/Co-Morbidities\s*:\s*(.+)$/im', $remarks, $match) !== 1) {
                continue;
            }

            $list = (string) ($match[1] ?? '');
            $tokens = preg_split('/[,;|]+/', $list) ?: [];
            foreach ($tokens as $token) {
                $canonical = $this->mapCoMorbidityTokenToCanonical((string) $token);
                if ($canonical !== null) {
                    $selected[$canonical] = true;
                }
            }
        }

        return $selected;
    }

    private function hasHistoricalCoMorbiditySelection(int $patientId): bool
    {
        return !empty($this->getHistoricalCoMorbiditySelection($patientId));
    }

    private function mapCoMorbidityTokenToCanonical(string $token): ?string
    {
        $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/', '', trim($token)));
        if ($normalized === '') {
            return null;
        }

        $map = [
            'dm' => 'DM',
            'diabetes' => 'DM',
            'diabetesmellitus' => 'DM',

            'htn' => 'HTN',
            'highbloodpressure' => 'HTN',
            'hypertension' => 'HTN',

            'cad' => 'CAD',
            'coronaryarterydisease' => 'CAD',

            'copd' => 'COPD',
            'chronicobstructivepulmonarydisease' => 'COPD',

            'cva' => 'CVA',
            'cerebralvascularaccident' => 'CVA',
            'stroke' => 'CVA',
        ];

        return $map[$normalized] ?? null;
    }

    /**
     * @param array<string, int> $flags
     */
    private function syncPatientAddictionFlags(int $patientId, array $flags): void
    {
        if ($patientId <= 0 || ! $this->db->tableExists('patient_master')) {
            return;
        }

        $patientFields = $this->db->getFieldNames('patient_master');
        $update = [];
        foreach ($flags as $field => $value) {
            if (in_array($field, $patientFields, true)) {
                $update[$field] = ((int) $value) > 0 ? 1 : 0;
            }
        }

        if (! empty($update)) {
            $this->db->table('patient_master')->where('id', $patientId)->update($update);
        }
    }

    /**
     * @param array<int, int> $selectedMorbidityIds
     */
    private function syncPatientMorbidities(int $patientId, array $selectedMorbidityIds): void
    {
        if ($patientId <= 0 || ! $this->db->tableExists('patient_morbidities')) {
            return;
        }

        $patientMorFields = $this->db->getFieldNames('patient_morbidities');
        $pIdField = $this->resolveFirstField($patientMorFields, ['p_id', 'patient_id']);
        $morIdField = $this->resolveFirstField($patientMorFields, ['morbidities', 'mor_id', 'morbidity_id']);
        if ($pIdField === null || $morIdField === null) {
            return;
        }

        $selectedMap = [];
        foreach ($selectedMorbidityIds as $morId) {
            $morId = (int) $morId;
            if ($morId > 0) {
                $selectedMap[$morId] = true;
            }
        }

        $existingRows = $this->db->table('patient_morbidities')
            ->select('id,' . $morIdField . ' as mor_id')
            ->where($pIdField, $patientId)
            ->get()
            ->getResultArray();

        $existingMap = [];
        foreach ($existingRows as $existingRow) {
            $morId = (int) ($existingRow['mor_id'] ?? 0);
            if ($morId > 0) {
                $existingMap[$morId] = (int) ($existingRow['id'] ?? 0);
            }
        }

        if (! empty($existingMap)) {
            foreach ($existingMap as $morId => $rowId) {
                if (empty($selectedMap[$morId])) {
                    $this->db->table('patient_morbidities')->where('id', $rowId)->delete();
                }
            }
        }

        $insertFields = $patientMorFields;
        foreach (array_keys($selectedMap) as $morId) {
            if (! isset($existingMap[$morId])) {
                $insert = [
                    $pIdField => $patientId,
                    $morIdField => $morId,
                ];
                if (in_array('update_date', $insertFields, true)) {
                    $insert['update_date'] = date('Y-m-d H:i:s');
                }
                if (in_array('insert_date', $insertFields, true)) {
                    $insert['insert_date'] = date('Y-m-d H:i:s');
                }
                if (in_array('create_date', $insertFields, true)) {
                    $insert['create_date'] = date('Y-m-d H:i:s');
                }
                $this->db->table('patient_morbidities')->insert($insert);
            }
        }
    }

    /**
     * @return array<int, int>
     */
    private function parseNumericList(string $list): array
    {
        if ($list === '') {
            return [];
        }

        $tokens = preg_split('/[^0-9]+/', $list) ?: [];
        $result = [];
        foreach ($tokens as $token) {
            $id = (int) trim((string) $token);
            if ($id > 0) {
                $result[$id] = $id;
            }
        }

        return array_values($result);
    }

    private function buildLocalClinicalAssist(array $input): array
    {
        $text = mb_strtolower(trim((string) (($input['complaints'] ?? '') . ' ' . ($input['finding_examinations'] ?? ''))));
        $normalizedText = preg_replace('/\s+/', ' ', $text) ?: $text;

        $pulse = $this->parseNumericFromText((string) ($input['pulse'] ?? ''));
        $spo2 = $this->parseNumericFromText((string) ($input['spo2'] ?? ''));
        $temp = $this->parseNumericFromText((string) ($input['temp'] ?? ''));
        $rr = $this->parseNumericFromText((string) ($input['rr_min'] ?? ''));
        $bpSys = $this->parseNumericFromText((string) ($input['bp'] ?? ''));
        $bpDia = $this->parseNumericFromText((string) ($input['diastolic'] ?? ''));

        $vitalChecks = [];
        if ($temp !== null && $temp >= 100.0) {
            $vitalChecks[] = 'Fever range temperature noted (' . rtrim(rtrim(number_format($temp, 1, '.', ''), '0'), '.') . ').';
        }
        if ($pulse !== null && $pulse > 100) {
            $vitalChecks[] = 'Pulse is elevated (>100/min).';
        }
        if ($spo2 !== null && $spo2 < 94) {
            $vitalChecks[] = 'SpO2 is below 94%; respiratory risk check advised.';
        }
        if ($rr !== null && $rr > 22) {
            $vitalChecks[] = 'Respiratory rate is high (>22/min).';
        }
        if ($bpSys !== null && $bpDia !== null && ($bpSys >= 140 || $bpDia >= 90)) {
            $vitalChecks[] = 'Blood pressure is elevated (' . (int) round($bpSys) . '/' . (int) round($bpDia) . ').';
        }

        $diagnosis = [];
        $investigations = [];
        $advice = [];
        $redFlags = [];

        $has = static function (string $haystack, array $needles): bool {
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    return true;
                }
            }
            return false;
        };

        $hasFever = $has($normalizedText, ['fever', 'pyrexia', 'bukhar']);
        $hasCough = $has($normalizedText, ['cough', 'khansi']);
        $hasSoreThroat = $has($normalizedText, ['sore throat', 'throat pain', 'gale', 'pharyngitis']);
        $hasCold = $has($normalizedText, ['cold', 'coryza', 'running nose', 'dripping nose', 'rhinorrhoea']);
        $hasBreathless = $has($normalizedText, ['breathlessness', 'breathing difficulty', 'dyspnea', 'shortness of breath']);
        $hasChestPain = $has($normalizedText, ['chest pain', 'pain chest']);
        $hasAcidity = $has($normalizedText, ['acidity', 'burning epigastrium', 'heartburn', 'epigastric']);
        $hasLooseMotions = $has($normalizedText, ['diarrhoea', 'diarrhea', 'loose motion', 'motions']);
        $hasVomiting = $has($normalizedText, ['vomiting', 'emesis']);
        $hasDysuria = $has($normalizedText, ['burning micturition', 'dysuria', 'urinary frequency', 'urine burning']);
        $hasHeadache = $has($normalizedText, ['headache', 'migraine']);

        if ($hasFever && ($hasCough || $hasSoreThroat || $hasCold)) {
            $diagnosis[] = 'Acute upper respiratory infection (viral etiology likely).';
            $investigations[] = 'CBC';
            if ($hasFever) {
                $investigations[] = 'CRP (if persistent fever)';
            }
            $advice[] = 'Hydration, steam inhalation, and symptomatic care.';
        }

        if ($hasFever && !$hasCough && !$hasSoreThroat && !$hasLooseMotions) {
            $diagnosis[] = 'Acute febrile illness (source to be localized clinically).';
            $investigations[] = 'CBC';
            $investigations[] = 'Urine routine (if clinically indicated)';
            if ((int) ($input['dengue'] ?? 0) === 1 || ($temp !== null && $temp >= 101.0)) {
                $investigations[] = 'Dengue NS1 / IgM (as per day of fever)';
                $investigations[] = 'Platelet count trend';
            }
        }

        if ($hasAcidity) {
            $diagnosis[] = 'Acid peptic disease / gastritis.';
            $advice[] = 'Avoid spicy/oily meals, late-night meals, and prolonged fasting.';
        }

        if ($hasLooseMotions || $hasVomiting) {
            $diagnosis[] = 'Acute gastroenteritis.';
            $investigations[] = 'Serum electrolytes (if dehydration suspected)';
            $advice[] = 'ORS and oral hydration with warning signs explained.';
        }

        if ($hasDysuria) {
            $diagnosis[] = 'Probable urinary tract infection.';
            $investigations[] = 'Urine routine & microscopy';
            $investigations[] = 'Urine culture (if recurrent/complicated)';
        }

        if ($hasHeadache && $bpSys !== null && $bpDia !== null && ($bpSys >= 140 || $bpDia >= 90)) {
            $diagnosis[] = 'Headache with elevated blood pressure; evaluate hypertension status.';
            $investigations[] = 'RFT and urine albumin (hypertension workup baseline)';
        }

        if ($hasChestPain || $hasBreathless) {
            $redFlags[] = 'Chest pain/breathlessness present: rule out acute cardiopulmonary emergency.';
            $investigations[] = 'ECG';
            if ($spo2 !== null && $spo2 < 94) {
                $investigations[] = 'Chest X-ray / ABG as per severity';
            }
        }

        if ($spo2 !== null && $spo2 < 92) {
            $redFlags[] = 'SpO2 <92%: urgent escalation and oxygen assessment advised.';
        }
        if ($temp !== null && $temp >= 103.0) {
            $redFlags[] = 'High-grade fever (>=103): monitor for systemic complications.';
        }
        if ((int) ($input['pregnancy'] ?? 0) === 1) {
            $redFlags[] = 'Pregnancy noted: verify medicine safety before final prescription.';
        }

        if (empty($diagnosis)) {
            $diagnosis[] = 'Syndromic provisional diagnosis to be finalized clinically.';
        }
        if (empty($advice)) {
            $advice[] = 'Review warning signs and follow-up if symptoms persist/worsen.';
        }

        $diagnosis = array_values(array_unique($diagnosis));
        $investigations = array_values(array_unique($investigations));
        $advice = array_values(array_unique($advice));
        $redFlags = array_values(array_unique($redFlags));
        $vitalChecks = array_values(array_unique($vitalChecks));

        return [
            'disclaimer' => 'Local rule-based clinical assist only. Final diagnosis and treatment decision must be by treating doctor.',
            'vital_checks' => $vitalChecks,
            'red_flags' => $redFlags,
            'suggested_diagnosis' => implode("\n", array_map(static fn(string $v): string => '- ' . $v, $diagnosis)),
            'suggested_investigation' => implode("\n", array_map(static fn(string $v): string => '- ' . $v, $investigations)),
            'suggested_advice' => implode("\n", array_map(static fn(string $v): string => '- ' . $v, $advice)),
        ];
    }

    private function parseNumericFromText(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/-?\d+(?:\.\d+)?/', $value, $m) !== 1) {
            return null;
        }

        return (float) $m[0];
    }

    private function resolveFirstField(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractWomenRelatedProblemsFromRemarks(string $remarks): string
    {
        if ($remarks === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $remarks) ?: [];
        $capture = [];
        $started = false;
        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '') {
                if ($started) {
                    break;
                }
                continue;
            }

            if (preg_match('/^Women Related Problems\s*:/i', $trimmed) === 1) {
                $started = true;
                $capture[] = $trimmed;
                continue;
            }

            if ($started && preg_match('/^(LMP(?:\s*Days\s*Before)?|Last Baby|Pregnancy Related)\s*:/i', $trimmed) === 1) {
                $capture[] = $trimmed;
                continue;
            }

            if ($started) {
                break;
            }
        }

        if (!empty($capture)) {
            return trim(implode("\n", $capture));
        }

        return '';
    }

    private function upsertWomenRelatedProblemsIntoRemarks(string $remarks, string $womenText): string
    {
        $remarks = preg_replace('/\n?\s*Women Related Problems\s*:\s*.+$/im', '', $remarks) ?? $remarks;
        $remarks = preg_replace('/\n?\s*LMP(?:\s*Days\s*Before)?\s*:\s*.+$/im', '', $remarks) ?? $remarks;
        $remarks = preg_replace('/\n?\s*Last Baby\s*:\s*.+$/im', '', $remarks) ?? $remarks;
        $remarks = preg_replace('/\n?\s*Pregnancy Related\s*:\s*.+$/im', '', $remarks) ?? $remarks;
        $remarks = trim($remarks);
        $labelled = trim($womenText);

        if ($remarks === '') {
            return $labelled;
        }

        return $remarks . "\n" . $labelled;
    }

    private function upsertCoMorbiditiesIntoRemarks(string $remarks, string $coMorbiditiesText): string
    {
        $remarks = preg_replace('/\n?\s*Co-Morbidities\s*:\s*.+$/im', '', $remarks) ?? $remarks;
        $remarks = trim($remarks);
        $coMorbiditiesText = trim($coMorbiditiesText);

        if ($coMorbiditiesText === '') {
            return $remarks;
        }

        $line = 'Co-Morbidities: ' . $coMorbiditiesText;
        if ($remarks === '') {
            return $line;
        }

        return $remarks . "\n" . $line;
    }

    /**
     * @param array<string, string> $womenStructured
     */
    private function buildWomenCombinedText(string $womenProblems, array $womenStructured): string
    {
        $lines = [];
        $womenProblems = trim($womenProblems);
        if ($womenProblems !== '') {
            $lines[] = 'Women Related Problems: ' . $womenProblems;
        }

        $lmp = trim((string) ($womenStructured['women_lmp'] ?? ''));
        if ($lmp !== '') {
            $lines[] = 'LMP Days Before: ' . $lmp;
        }

        $lastBaby = trim((string) ($womenStructured['women_last_baby'] ?? ''));
        if ($lastBaby !== '') {
            $lines[] = 'Last Baby: ' . $lastBaby;
        }

        $pregnancyRelated = trim((string) ($womenStructured['women_pregnancy_related'] ?? ''));
        if ($pregnancyRelated !== '') {
            $lines[] = 'Pregnancy Related: ' . $pregnancyRelated;
        }

        return trim(implode("\n", $lines));
    }

    /**
     * @return array{women_related_problems:string,women_lmp:string,women_last_baby:string,women_pregnancy_related:string}
     */
    private function parseWomenStructuredDetails(string $text): array
    {
        $remaining = trim($text);

        $extract = static function (string $pattern, string &$haystack): string {
            $value = '';
            if (preg_match($pattern, $haystack, $match) === 1) {
                $value = trim((string) ($match[1] ?? ''));
            }
            $haystack = trim((string) (preg_replace($pattern, '', $haystack) ?? $haystack));
            return $value;
        };

        $womenProblems = $extract('/^\s*Women Related Problems\s*:\s*(.+)$/im', $remaining);
        $lmp = $extract('/^\s*LMP(?:\s*Days\s*Before)?\s*:\s*(.+)$/im', $remaining);
        $lastBaby = $extract('/^\s*Last Baby\s*:\s*(.+)$/im', $remaining);
        $pregnancyRelated = $extract('/^\s*Pregnancy Related\s*:\s*(.+)$/im', $remaining);

        if ($womenProblems === '') {
            $womenProblems = trim($remaining);
        }

        return [
            'women_related_problems' => $womenProblems,
            'women_lmp' => $lmp,
            'women_last_baby' => $lastBaby,
            'women_pregnancy_related' => $pregnancyRelated,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $fields
     * @param array<string, string> $womenStructured
     */
    private function mapWomenStructuredToExistingColumns(array &$payload, array $fields, array $womenStructured): void
    {
        $columnMap = [
            'women_lmp' => ['women_lmp', 'lmp', 'lmp_date'],
            'women_last_baby' => ['women_last_baby', 'last_baby', 'last_baby_details'],
            'women_pregnancy_related' => ['women_pregnancy_related', 'pregnancy_related', 'pregnancy_related_problem'],
        ];

        foreach ($columnMap as $key => $candidates) {
            $resolved = $this->resolveFirstField($fields, $candidates);
            if ($resolved !== null) {
                $payload[$resolved] = trim((string) ($womenStructured[$key] ?? ''));
            }
        }
    }

    /**
     * @param array<string, mixed> $patientRow
     * @param array<string, mixed> $opdRow
     */
    private function storePrescriptionFhirBundle(int $opdId, int $sessionId, array $patientRow, array $opdRow): bool
    {
        if (! $this->db->tableExists('opd_fhir_documents')) {
            return false;
        }

        $abhaField = $this->resolvePatientAbhaField();
        $abhaAddress = $abhaField !== null ? trim((string) ($patientRow[$abhaField] ?? '')) : '';

        $patient = [
            'id' => (string) ($patientRow['id'] ?? ($opdRow['p_id'] ?? 0)),
            'name' => (string) ($patientRow['p_fname'] ?? $opdRow['P_name'] ?? ''),
            'gender' => $this->normalizeGender((string) ($patientRow['gender'] ?? '')),
            'birthDate' => !empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
            'abhaAddress' => $abhaAddress,
        ];

        $encounter = [
            'id' => (string) ($opdRow['opd_id'] ?? $opdId),
            'status' => 'finished',
        ];

        $medications = $this->getPrescriptionMedicines($sessionId);
        $bundle = $this->fhirR4Builder->buildPrescriptionBundle($patient, $encounter, $medications);
        $bundleJson = (string) json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $user = auth()->user();
        $generatedBy = (string) ($user->id ?? 'system');

        $inserted = (bool) $this->db->table('opd_fhir_documents')->insert([
            'opd_id' => $opdId,
            'opd_session_id' => $sessionId,
            'bundle_type' => 'MedicationRequestBundle',
            'bundle_json' => $bundleJson,
            'generated_by' => $generatedBy,
            'generated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ]);

        if (! $inserted) {
            return false;
        }

        try {
            $documentId = (int) $this->db->insertID();
            $bridgeSync = new BridgeSyncService();
            $bridgeSync->enqueue(
                'opd.fhir.generated',
                [
                    'opd_fhir_document_id' => $documentId,
                    'opd_id' => $opdId,
                    'opd_session_id' => $sessionId,
                    'bundle_type' => 'MedicationRequestBundle',
                    'generated_by' => $generatedBy,
                    'generated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                    'bundle_json' => $bundle,
                ],
                'opd_fhir_document',
                (string) $documentId
            );
        } catch (\Throwable $e) {
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPrescriptionMedicines(int $sessionId): array
    {
        $table = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($table === null) {
            return [];
        }

        $rows = $this->db->table($table)->where('opd_pre_id', $sessionId)->get()->getResultArray();

        $medications = [];
        foreach ($rows as $row) {
            $dosageParts = array_filter([
                trim((string) ($row['dosage'] ?? '')),
                trim((string) ($row['dosage_when'] ?? '')),
                trim((string) ($row['dosage_freq'] ?? '')),
                trim((string) ($row['no_of_days'] ?? '')) !== '' ? ('for ' . trim((string) $row['no_of_days']) . ' days') : '',
            ]);

            $medications[] = [
                'drug_name' => (string) ($row['med_name'] ?? ''),
                'status' => 'active',
                'dosage' => implode(' | ', $dosageParts),
            ];
        }

        return $medications;
    }

    private function normalizeGender(string $value): string
    {
        $value = trim($value);
        if ($value === '1' || strtolower($value) === 'male') {
            return 'male';
        }

        if ($value === '2' || strtolower($value) === 'female') {
            return 'female';
        }

        return 'unknown';
    }

    /**
     * @return string|int|null
     */
    private function getCurrentUserId()
    {
        $user = auth()->user();
        if ($user === null) {
            return null;
        }

        return $user->id ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function getComplaintMatchesFromText(string $text): array
    {
        $pieces = preg_split('/[,;\n\r]+/', strtolower($text)) ?: [];
        $out = [];

        $directMap = [
            'kamar' => 'PAIN BACK',
            'back pain' => 'PAIN BACK',
            'pet dard' => 'PAIN IN ABDOMEN',
            'body pain' => 'BODYACHE',
            'kamzori' => 'WEAKNESS',
            'weakness' => 'WEAKNESS',
            'bukhar' => 'FEVER',
            'khansi' => 'COUGH DRY',
            'chakkar' => 'GIDDINESS',
            'ulti' => 'VOMITING',
            'thakan' => 'TIREDNESS',
        ];

        foreach ($pieces as $piece) {
            $token = trim((string) $piece);
            if ($token === '') {
                continue;
            }

            $matchedName = null;
            foreach ($directMap as $key => $value) {
                if (str_contains($token, $key)) {
                    $matchedName = $value;
                    break;
                }
            }

            if ($matchedName !== null) {
                $out[strtoupper($matchedName)] = $matchedName;
                continue;
            }

            if (! $this->db->tableExists('complaints_master')) {
                $out[strtoupper($token)] = strtoupper($token);
                continue;
            }

            $row = $this->db->table('complaints_master')
                ->select('Name')
                ->where('is_active', 1)
                ->groupStart()
                ->like('Name', $token)
                ->orLike('name_hinglish', $token)
                ->orLike('keywords', $token)
                ->groupEnd()
                ->orderBy('show_in_short', 'DESC')
                ->orderBy('Code', 'ASC')
                ->get(1)
                ->getRowArray();

            if (! empty($row['Name'])) {
                $name = trim((string) $row['Name']);
                $out[strtoupper($name)] = $name;
            }
        }

        return array_values($out);
    }

    private function getGeminiApiKey(): string
    {
        foreach (['GEMINI_API_KEY', 'AI_GEMINI_API_KEY', 'APP_GEMINI_API_KEY', 'H_GEMINI_API_KEY'] as $constName) {
            if (defined($constName)) {
                $value = trim((string) constant($constName));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        if ($this->db->tableExists('hospital_setting')) {
            $row = $this->db->table('hospital_setting')
                ->select('s_value')
                ->whereIn('s_name', ['GEMINI_API_KEY', 'AI_GEMINI_API_KEY', 'APP_GEMINI_API_KEY', 'H_GEMINI_API_KEY'])
                ->where('s_value !=', '')
                ->orderBy('s_name', 'ASC')
                ->get(1)
                ->getRowArray();

            if (! empty($row['s_value'])) {
                return trim((string) $row['s_value']);
            }
        }

        return '';
    }

    /**
     * @return array{endpoint:string,api_key:string,deployment:string,api_version:string}
     */
    private function getAzureOpenAiConfig(): array
    {
        $endpoint = $this->readAiSettingValue(
            ['AZURE_OPENAI_ENDPOINT', 'AI_AZURE_OPENAI_ENDPOINT', 'APP_AZURE_OPENAI_ENDPOINT', 'H_AZURE_OPENAI_ENDPOINT'],
            ['AZURE_OPENAI_ENDPOINT']
        );

        $apiKey = $this->readAiSettingValue(
            ['AZURE_OPENAI_API_KEY', 'AI_AZURE_OPENAI_API_KEY', 'APP_AZURE_OPENAI_API_KEY', 'H_AZURE_OPENAI_API_KEY'],
            ['AZURE_OPENAI_API_KEY']
        );

        $deployment = $this->readAiSettingValue(
            ['AZURE_OPENAI_DEPLOYMENT', 'AI_AZURE_OPENAI_DEPLOYMENT', 'APP_AZURE_OPENAI_DEPLOYMENT', 'H_AZURE_OPENAI_DEPLOYMENT'],
            ['AZURE_OPENAI_DEPLOYMENT']
        );

        $apiVersion = $this->readAiSettingValue(
            ['AZURE_OPENAI_API_VERSION', 'AI_AZURE_OPENAI_API_VERSION', 'APP_AZURE_OPENAI_API_VERSION', 'H_AZURE_OPENAI_API_VERSION'],
            ['AZURE_OPENAI_API_VERSION']
        );

        if ($apiVersion === '') {
            $apiVersion = '2024-10-21';
        }

        return [
            'endpoint' => rtrim($endpoint, '/'),
            'api_key' => $apiKey,
            'deployment' => $deployment,
            'api_version' => $apiVersion,
        ];
    }

    /**
     * @param array<int, string> $constCandidates
     * @param array<int, string> $settingCandidates
     */
    private function readAiSettingValue(array $constCandidates, array $settingCandidates): string
    {
        foreach ($constCandidates as $constName) {
            if (! defined($constName)) {
                continue;
            }

            $value = trim((string) constant($constName));
            if ($value !== '') {
                return $value;
            }
        }

        if ($this->db->tableExists('hospital_setting') && ! empty($settingCandidates)) {
            $row = $this->db->table('hospital_setting')
                ->select('s_value')
                ->whereIn('s_name', $settingCandidates)
                ->where('s_value !=', '')
                ->orderBy('s_name', 'ASC')
                ->get(1)
                ->getRowArray();

            if (! empty($row['s_value'])) {
                return trim((string) $row['s_value']);
            }
        }

        return '';
    }

    /**
     * @param array<int, string> $complaints
     */
    private function generateComplaintDraftWithGemini(array $complaints, string $currentText): ?string
    {
        if (empty($complaints)) {
            return null;
        }

        $prompt = "You are a clinical documentation assistant for Indian OPD.
Rewrite chief complaints in concise professional style.
Input complaints: " . implode(', ', $complaints) . "
Additional raw doctor notes: " . $currentText . "
Output only one plain text sentence starting with 'Chief complaints:' and no markdown.";

        return $this->generateClinicalTextWithGemini($prompt, 180);
    }

    private function generateClinicalTextWithAzureOpenAi(string $prompt, int $maxOutputTokens = 300): ?string
    {
        $cfg = $this->getAzureOpenAiConfig();
        if ($cfg['endpoint'] === '' || $cfg['api_key'] === '' || $cfg['deployment'] === '' || trim($prompt) === '') {
            return null;
        }

        try {
            $httpOptions = [
                'timeout' => 20,
                'http_errors' => false,
            ];
            if (defined('ENVIRONMENT') && in_array((string) ENVIRONMENT, ['development', 'testing'], true)) {
                $httpOptions['verify'] = false;
            }

            $client = service('curlrequest', $httpOptions);
            $url = $cfg['endpoint'] . '/openai/deployments/' . rawurlencode($cfg['deployment'])
                . '/chat/completions?api-version=' . rawurlencode($cfg['api_version']);

            $response = $client->post($url, [
                'headers' => [
                    'api-key' => $cfg['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a clinical OPD assistant. Return plain text only, no markdown.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                    'top_p' => 0.9,
                    'max_tokens' => max(64, $maxOutputTokens),
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return null;
            }

            $decoded = json_decode((string) $response->getBody(), true);
            $text = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));

            $text = trim($text);
            if ($text === '') {
                return null;
            }

            $this->lastAiProvider = 'azure';
            return preg_replace('/\s+/', ' ', $text) ?: $text;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildClinicalPrompt(string $mode, string $text): string
    {
        if ($mode === 'hinglish_to_english') {
            return "You are a clinical OPD assistant. Convert Hinglish medical text to clear professional English.
Keep all clinical meaning unchanged. Do not add new diagnosis.
Return plain text only, no markdown.
Input: " . $text;
        }

        if ($mode === 'diagnosis') {
            return "You are a clinical OPD assistant. Rewrite diagnosis text into concise professional medical English.
Preserve meaning, do not invent findings. Return plain text only.
Input: " . $text;
        }

        if ($mode === 'finding') {
            return "You are a clinical OPD assistant. Rewrite findings/examination notes into concise clinical English.
Preserve original meaning and sequence. Return plain text only.
Input: " . $text;
        }

        return "You are a clinical OPD assistant. Rewrite this OPD note into concise, professional English suitable for prescription documentation.
Preserve meaning, do not add new facts. Return plain text only.
Input: " . $text;
    }

    private function buildClinicalAutotypeLocalFallback(string $mode, string $text): string
    {
        $source = trim((string) $text);
        if ($source === '') {
            return '';
        }

        if ($mode === 'hinglish_to_english') {
            $dictionary = [
                'bukhar' => 'fever',
                'khansi' => 'cough',
                'pet dard' => 'abdominal pain',
                'sir dard' => 'headache',
                'ulti' => 'vomiting',
                'kamzori' => 'weakness',
                'saans' => 'breath',
                'saans phoolna' => 'breathlessness',
                'jukaam' => 'cold',
                'chakkar' => 'giddiness',
                'dast' => 'loose motions',
                'jalan' => 'burning sensation',
            ];

            $converted = mb_strtolower($source);
            foreach ($dictionary as $from => $to) {
                $converted = preg_replace('/\b' . preg_quote($from, '/') . '\b/u', $to, $converted) ?? $converted;
            }

            return $this->normalizeClinicalSentenceFlow($converted);
        }

        return $this->normalizeClinicalSentenceFlow($source);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonFromAiResponse(string $raw): ?array
    {
        $text = trim($raw);
        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $match) === 1) {
            $decoded = json_decode((string) ($match[0] ?? ''), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeClinicalSentenceFlow(string $text): string
    {
        $normalized = trim($text);
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $normalized = str_replace([' ,', ' .', ' ;', ' :'], [',', '.', ';', ':'], $normalized);
        $normalized = str_replace(["\r\n", "\r"], "\n", $normalized);

        $chunks = preg_split('/(?<=[\.!?;])\s+|\n+/', $normalized) ?: [];
        $sentences = [];

        foreach ($chunks as $chunk) {
            $chunk = trim((string) $chunk);
            if ($chunk === '') {
                continue;
            }

            $chunk = ucfirst($chunk);
            if (! preg_match('/[\.!?]$/', $chunk)) {
                $chunk .= '.';
            }

            $sentences[] = $chunk;
        }

        if (empty($sentences)) {
            return ucfirst($normalized);
        }

        return implode(' ', $sentences);
    }

    private function generateClinicalTextWithGemini(string $prompt, int $maxOutputTokens = 300): ?string
    {
        $azureText = $this->generateClinicalTextWithAzureOpenAi($prompt, $maxOutputTokens);
        if ($azureText !== null && trim($azureText) !== '') {
            return $azureText;
        }

        $apiKey = $this->getGeminiApiKey();
        if ($apiKey === '' || trim($prompt) === '') {
            return null;
        }

        try {
            $httpOptions = [
                'timeout' => 20,
                'http_errors' => false,
            ];
            if (defined('ENVIRONMENT') && in_array((string) ENVIRONMENT, ['development', 'testing'], true)) {
                $httpOptions['verify'] = false;
            }

            $client = service('curlrequest', $httpOptions);
            $payload = [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'topP' => 0.9,
                    'maxOutputTokens' => max(64, $maxOutputTokens),
                ],
            ];

            foreach (['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-flash-latest'] as $modelName) {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $modelName . ':generateContent?key=' . urlencode($apiKey);
                $response = $client->post($url, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => $payload,
                ]);

                if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                    continue;
                }

                $decoded = json_decode((string) $response->getBody(), true);
                $parts = $decoded['candidates'][0]['content']['parts'] ?? [];
                $text = '';
                foreach ($parts as $part) {
                    $text .= (string) ($part['text'] ?? '');
                }

                $text = trim($text);
                if ($text !== '') {
                    $this->lastAiProvider = 'gemini';
                    return preg_replace('/\s+/', ' ', $text) ?: $text;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function buildFullClinicalDraftPrompt(array $input): string
    {
        $payload = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '{}';
        }

        return "You are a senior OPD clinical documentation assistant.
Using the complete OPD snapshot JSON, produce concise professional drafts.
Do not invent facts. Use only the data provided.
Return ONLY valid minified JSON object with exactly these keys:
finding_examinations, diagnosis, provisional_diagnosis, prescriber_remarks, investigation, advice.
Each value must be plain text string (no markdown).
OPD SNAPSHOT JSON: " . $payload;
    }

    /**
     * @return array<string, string>|null
     */
    private function extractFullDraftJson(?string $aiText): ?array
    {
        if ($aiText === null || trim($aiText) === '') {
            return null;
        }

        $candidate = trim($aiText);
        if (!str_starts_with($candidate, '{') || !str_ends_with($candidate, '}')) {
            if (preg_match('/\{.*\}/s', $candidate, $match) === 1) {
                $candidate = (string) ($match[0] ?? '');
            }
        }

        $decoded = json_decode($candidate, true);
        if (!is_array($decoded)) {
            return null;
        }

        $keys = ['finding_examinations', 'diagnosis', 'provisional_diagnosis', 'prescriber_remarks', 'investigation', 'advice'];
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = trim((string) ($decoded[$key] ?? ''));
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function buildFullDraftFallback(array $input): array
    {
        $vitals = $this->joinParts([
            ($input['bp'] ?? '') !== '' || ($input['diastolic'] ?? '') !== '' ? 'BP ' . trim((string) ($input['bp'] ?? '')) . '/' . trim((string) ($input['diastolic'] ?? '')) : '',
            ($input['pulse'] ?? '') !== '' ? 'Pulse ' . trim((string) $input['pulse']) . '/min' : '',
            ($input['temp'] ?? '') !== '' ? 'Temp ' . trim((string) $input['temp']) : '',
            ($input['spo2'] ?? '') !== '' ? 'SpO2 ' . trim((string) $input['spo2']) . '%' : '',
            ($input['rr_min'] ?? '') !== '' ? 'RR ' . trim((string) $input['rr_min']) . '/min' : '',
        ], '; ');

        $exam = $this->joinParts([
            ($input['pallor'] ?? '') !== '' ? 'Pallor: ' . trim((string) $input['pallor']) : '',
            ($input['icterus'] ?? '') !== '' ? 'Icterus: ' . trim((string) $input['icterus']) : '',
            ($input['cyanosis'] ?? '') !== '' ? 'Cyanosis: ' . trim((string) $input['cyanosis']) : '',
            ($input['clubbing'] ?? '') !== '' ? 'Clubbing: ' . trim((string) $input['clubbing']) : '',
            ($input['edema'] ?? '') !== '' ? 'Edema: ' . trim((string) $input['edema']) : '',
            ($input['pain_value'] ?? '') !== '' ? 'Pain scale: ' . trim((string) $input['pain_value']) . '/4' : '',
        ], '; ');

        $complications = $this->joinParts([
            !empty($input['pregnancy']) ? 'Pregnancy' : '',
            !empty($input['lactation']) ? 'Lactation' : '',
            !empty($input['liver_insufficiency']) ? 'Liver insufficiency' : '',
            !empty($input['renal_insufficiency']) ? 'Renal insufficiency' : '',
            !empty($input['pulmonary_insufficiency']) ? 'Pulmonary insufficiency' : '',
            !empty($input['corona_suspected']) ? 'Corona suspected' : '',
            !empty($input['dengue']) ? 'Dengue' : '',
        ], ', ');

        $addiction = $this->joinParts([
            !empty($input['is_smoking']) ? 'Smoking' : '',
            !empty($input['is_alcohol']) ? 'Alcohol' : '',
            !empty($input['is_drug_abuse']) ? 'Drug abuse' : '',
        ], ', ');

        $women = $this->joinParts([
            ($input['women_related_problems'] ?? '') !== '' ? 'Women related: ' . trim((string) $input['women_related_problems']) : '',
            ($input['women_lmp'] ?? '') !== '' ? 'LMP days before: ' . trim((string) $input['women_lmp']) : '',
            ($input['women_last_baby'] ?? '') !== '' ? 'Last baby: ' . trim((string) $input['women_last_baby']) : '',
            ($input['women_pregnancy_related'] ?? '') !== '' ? 'Pregnancy related: ' . trim((string) $input['women_pregnancy_related']) : '',
        ], '; ');

        $finding = $this->joinParts([
            trim((string) ($input['finding_examinations'] ?? '')),
            $vitals !== '' ? 'Vitals: ' . $vitals : '',
            $exam !== '' ? 'General examination: ' . $exam : '',
        ], "\n");

        $remarks = $this->joinParts([
            trim((string) ($input['prescriber_remarks'] ?? '')),
            $complications !== '' ? 'Complication: ' . $complications : '',
            $addiction !== '' ? 'Addiction: ' . $addiction : '',
            $women,
        ], "\n");

        $diagnosis = trim((string) ($input['diagnosis'] ?? ''));
        if ($diagnosis === '' && trim((string) ($input['complaints'] ?? '')) !== '') {
            $diagnosis = 'Provisional diagnosis to correlate with complaints: ' . trim((string) ($input['complaints'] ?? ''));
        }

        $provisional = trim((string) ($input['provisional_diagnosis'] ?? ''));
        if ($provisional === '') {
            $provisional = $diagnosis;
        }

        $investigation = trim((string) ($input['investigation'] ?? ''));
        $advice = $this->joinParts([
            trim((string) ($input['advice'] ?? '')),
            trim((string) ($input['next_visit'] ?? '')) !== '' ? 'Next visit: ' . trim((string) ($input['next_visit'] ?? '')) : '',
            trim((string) ($input['refer_to'] ?? '')) !== '' ? 'Refer to: ' . trim((string) ($input['refer_to'] ?? '')) : '',
        ], "\n");

        return [
            'finding_examinations' => $finding,
            'diagnosis' => $diagnosis,
            'provisional_diagnosis' => $provisional,
            'prescriber_remarks' => $remarks,
            'investigation' => $investigation,
            'advice' => $advice,
        ];
    }

    /**
     * @param array<int, string> $parts
     */
    private function joinParts(array $parts, string $separator = ', '): string
    {
        $filtered = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $filtered[] = $part;
            }
        }

        return implode($separator, $filtered);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildPatientHistoryAlerts(int $patientId, int $currentOpdId): array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('opd_prescription') || ! $this->db->tableExists('opd_master')) {
            return [];
        }

        $alerts = [];

        $rows = $this->db->table('opd_prescription p')
            ->select('p.opd_id,p.complaints,p.diagnosis,p.bp,p.diastolic,p.pulse,p.temp,p.spo2,p.investigation,p.advice,o.apointment_date')
            ->join('opd_master o', 'o.opd_id=p.opd_id', 'left')
            ->where('p.p_id', $patientId)
            ->where('p.opd_id !=', $currentOpdId)
            ->orderBy('p.id', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $dateLabel = ! empty($row['apointment_date']) ? date('d-m-Y', strtotime((string) $row['apointment_date'])) : 'Previous OPD';

            if (trim((string) ($row['diagnosis'] ?? '')) !== '') {
                $alerts[] = [
                    'type' => 'Diagnosis History',
                    'message' => $dateLabel . ': ' . trim((string) $row['diagnosis']),
                ];
            }

            if (trim((string) ($row['complaints'] ?? '')) !== '') {
                $alerts[] = [
                    'type' => 'Complaint History',
                    'message' => $dateLabel . ': ' . trim((string) $row['complaints']),
                ];
            }

            $vitalLine = [];
            foreach ([
                'BP' => trim((string) ($row['bp'] ?? '')),
                'DIA' => trim((string) ($row['diastolic'] ?? '')),
                'Pulse' => trim((string) ($row['pulse'] ?? '')),
                'Temp' => trim((string) ($row['temp'] ?? '')),
                'SpO2' => trim((string) ($row['spo2'] ?? '')),
            ] as $k => $v) {
                if ($v !== '') {
                    $vitalLine[] = $k . ':' . $v;
                }
            }

            if (! empty($vitalLine)) {
                $alerts[] = [
                    'type' => 'Vitals Trend',
                    'message' => $dateLabel . ': ' . implode(' | ', $vitalLine),
                ];
            }
        }

        if ($this->db->tableExists('patient_remark')) {
            $remarks = $this->db->table('patient_remark')
                ->select('remark,insert_datetime')
                ->where('p_id', $patientId)
                ->orderBy('id', 'DESC')
                ->limit(3)
                ->get()
                ->getResultArray();

            foreach ($remarks as $remarkRow) {
                $msg = trim((string) ($remarkRow['remark'] ?? ''));
                if ($msg === '') {
                    continue;
                }
                $time = ! empty($remarkRow['insert_datetime']) ? date('d-m-Y H:i', strtotime((string) $remarkRow['insert_datetime'])) : 'Recent';
                $alerts[] = [
                    'type' => 'Patient Remark',
                    'message' => $time . ': ' . $msg,
                ];
            }
        }

        return array_slice($alerts, 0, 8);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function fetchPatientRemarks(int $patientId, int $limit = 8): array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('patient_remark')) {
            return [];
        }

        $rows = $this->db->table('patient_remark')
            ->select('remark,insert_by,insert_datetime')
            ->where('p_id', $patientId)
            ->orderBy('id', 'DESC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $msg = trim((string) ($row['remark'] ?? ''));
            if ($msg === '') {
                continue;
            }

            $time = ! empty($row['insert_datetime']) ? date('d-m-Y H:i', strtotime((string) $row['insert_datetime'])) : '';
            $out[] = [
                'remark' => $msg,
                'insert_by' => trim((string) ($row['insert_by'] ?? '')),
                'insert_time' => $time,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildOldPrescriptionFastView(int $patientId, int $currentOpdId): array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('opd_master')) {
            return [];
        }

        $opdRows = $this->db->table('opd_master o')
            ->select('o.opd_id,o.opd_code,o.apointment_date,p.complaints,p.diagnosis,p.id as session_id')
            ->join('opd_prescription p', 'p.opd_id=o.opd_id', 'left')
            ->where('o.p_id', $patientId)
            ->where('o.opd_id !=', $currentOpdId)
            ->orderBy('o.opd_id', 'DESC')
            ->limit(6)
            ->get()
            ->getResultArray();

        if (empty($opdRows)) {
            return [];
        }

        $medicineMap = [];
        $medicineTable = $this->findExistingTable(['opd_prescrption_prescribed', 'opd_prescription_prescribed']);
        if ($medicineTable !== null) {
            $sessionIds = [];
            foreach ($opdRows as $opdRow) {
                $sessionId = (int) ($opdRow['session_id'] ?? 0);
                if ($sessionId > 0) {
                    $sessionIds[$sessionId] = true;
                }
            }

            if (! empty($sessionIds)) {
                $medicineFields = $this->db->getFieldNames($medicineTable);
                if (in_array('opd_pre_id', $medicineFields, true)) {
                    $doseMap = $this->getDoseMasterLabelMap('opd_dose_shed', ['dose_shed_id', 'id'], ['dose_show_sign', 'dose_sign', 'dose_sign_desc', 'name']);
                    $whenMap = $this->getDoseMasterLabelMap('opd_dose_when', ['dose_when_id', 'id'], ['dose_sign', 'dose_sign_desc', 'name']);
                    $freqMap = $this->getDoseMasterLabelMap('opd_dose_frequency', ['dose_freq_id', 'id'], ['dose_sign', 'dose_sign_desc', 'name']);

                    $query = $this->db->table($medicineTable)
                        ->whereIn('opd_pre_id', array_map('intval', array_keys($sessionIds)));

                    if (in_array('id', $medicineFields, true)) {
                        $query->orderBy('id', 'ASC');
                    }

                    $medicineRows = $query->get()->getResultArray();
                    foreach ($medicineRows as $medicineRow) {
                        $sessionId = (int) ($medicineRow['opd_pre_id'] ?? 0);
                        if ($sessionId <= 0) {
                            continue;
                        }

                        if (! isset($medicineMap[$sessionId])) {
                            $medicineMap[$sessionId] = [];
                        }

                        $medicineMap[$sessionId][] = [
                            'id' => (int) ($medicineRow['id'] ?? 0),
                            'med_id' => (int) ($medicineRow['med_id'] ?? 0),
                            'med_name' => trim((string) ($medicineRow['med_name'] ?? '')),
                            'med_type' => trim((string) ($medicineRow['med_type'] ?? '')),
                            'dosage' => trim((string) ($medicineRow['dosage'] ?? '')),
                            'dosage_label' => $this->resolveDoseMasterLabel($medicineRow['dosage'] ?? '', $doseMap),
                            'dosage_when' => trim((string) ($medicineRow['dosage_when'] ?? '')),
                            'dosage_when_label' => $this->resolveDoseMasterLabel($medicineRow['dosage_when'] ?? '', $whenMap),
                            'dosage_freq' => trim((string) ($medicineRow['dosage_freq'] ?? '')),
                            'dosage_freq_label' => $this->resolveDoseMasterLabel($medicineRow['dosage_freq'] ?? '', $freqMap),
                            'no_of_days' => trim((string) ($medicineRow['no_of_days'] ?? '')),
                            'qty' => trim((string) ($medicineRow['qty'] ?? '')),
                            'remark' => trim((string) ($medicineRow['remark'] ?? '')),
                        ];
                    }
                }
            }
        }

        $scanMap = [];
        if ($this->db->tableExists('file_upload_data')) {
            $opdIds = array_map(static fn(array $r): int => (int) ($r['opd_id'] ?? 0), $opdRows);
            $opdIds = array_values(array_filter($opdIds));

            if (!empty($opdIds)) {
                $fields = $this->db->getFieldNames('file_upload_data');
                $builder = $this->db->table('file_upload_data')
                    ->select('opd_id,full_path,file_ext,id,show_type')
                    ->whereIn('opd_id', $opdIds)
                    ->orderBy('id', 'DESC');

                if (in_array('show_type', $fields, true)) {
                    $builder->where('show_type', 0);
                }

                $files = $builder->get()->getResultArray();
                foreach ($files as $file) {
                    $opdId = (int) ($file['opd_id'] ?? 0);
                    if ($opdId <= 0) {
                        continue;
                    }

                    if (!isset($scanMap[$opdId])) {
                        $scanMap[$opdId] = [];
                    }
                    if (count($scanMap[$opdId]) >= 2) {
                        continue;
                    }

                    $raw = (string) ($file['full_path'] ?? '');
                    $publicPath = $raw;
                    $pos = strpos($raw, '/uploads/', 1);
                    if ($pos !== false) {
                        $publicPath = substr($raw, $pos);
                    }

                    $ext = strtolower((string) ($file['file_ext'] ?? pathinfo($publicPath, PATHINFO_EXTENSION)));
                    $ext = $ext !== '' && $ext[0] !== '.' ? '.' . $ext : $ext;
                    $scanMap[$opdId][] = [
                        'path' => $publicPath,
                        'is_pdf' => $ext === '.pdf',
                        'scan_type' => $this->detectScanTypeFromPath($publicPath),
                    ];
                }
            }
        }

        $out = [];
        foreach ($opdRows as $row) {
            $opdId = (int) ($row['opd_id'] ?? 0);
            $sessionId = (int) ($row['session_id'] ?? 0);
            $out[] = [
                'opd_id' => $opdId,
                'opd_code' => (string) ($row['opd_code'] ?? $opdId),
                'opd_date' => !empty($row['apointment_date']) ? date('d-m-Y', strtotime((string) $row['apointment_date'])) : '',
                'complaints' => (string) ($row['complaints'] ?? ''),
                'diagnosis' => (string) ($row['diagnosis'] ?? ''),
                'session_id' => $sessionId,
                'medicines' => $medicineMap[$sessionId] ?? [],
                'scans' => $scanMap[$opdId] ?? [],
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCurrentOpdScansForConsult(int $opdId): array
    {
        if ($opdId <= 0 || ! $this->db->tableExists('file_upload_data')) {
            return [];
        }

        $fields = $this->db->getFieldNames('file_upload_data');
        $safeSelect = ['id', 'full_path', 'file_ext', 'insert_date'];
        foreach (['show_type', 'scan_type', 'document_type', 'content_description', 'ai_status', 'ai_alert_flag', 'ai_alert_text', 'extract_text', 'extracted_text'] as $optional) {
            if (in_array($optional, $fields, true)) {
                $safeSelect[] = $optional;
            }
        }

        $builder = $this->db->table('file_upload_data')
            ->select(implode(',', $safeSelect))
            ->where('opd_id', $opdId)
            ->orderBy('id', 'DESC')
            ->limit(6);

        if (in_array('show_type', $fields, true)) {
            $builder->where('show_type', 0);
        }

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $raw = str_replace('\\', '/', (string) ($row['full_path'] ?? ''));
            $publicPath = $raw;
            $pos = strpos($raw, '/uploads/');
            if ($pos !== false) {
                $publicPath = substr($raw, $pos);
            }

            $ext = strtolower((string) ($row['file_ext'] ?? pathinfo($publicPath, PATHINFO_EXTENSION)));
            if ($ext !== '' && $ext[0] !== '.') {
                $ext = '.' . $ext;
            }

            $reportText = trim((string) ($row['content_description'] ?? ''));
            if ($reportText === '') {
                $reportText = trim((string) ($row['extracted_text'] ?? $row['extract_text'] ?? ''));
            }

            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'path' => $publicPath,
                'is_pdf' => $ext === '.pdf',
                'scan_type' => (string) ($row['scan_type'] ?? ''),
                'document_type' => (string) ($row['document_type'] ?? ''),
                'ai_status' => (string) ($row['ai_status'] ?? ''),
                'ai_alert_flag' => (int) ($row['ai_alert_flag'] ?? 0),
                'ai_alert_text' => (string) ($row['ai_alert_text'] ?? ''),
                'report_text' => $reportText,
                'insert_date' => !empty($row['insert_date']) ? date('d/m/Y H:i', strtotime((string) $row['insert_date'])) : '',
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPatientScanHistoryForConsult(int $patientId): array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('file_upload_data') || ! $this->db->tableExists('opd_master')) {
            return [];
        }

        $fields = $this->db->getFieldNames('file_upload_data');
        $safeSelect = ['f.id', 'f.opd_id', 'f.full_path', 'f.file_ext', 'f.insert_date', 'o.opd_code', 'o.apointment_date'];
        foreach (['show_type', 'scan_type', 'document_type', 'content_description', 'ai_status', 'ai_alert_flag', 'ai_alert_text', 'extract_text', 'extracted_text'] as $optional) {
            if (in_array($optional, $fields, true)) {
                $safeSelect[] = 'f.' . $optional;
            }
        }

        $builder = $this->db->table('file_upload_data f')
            ->select(implode(',', $safeSelect))
            ->join('opd_master o', 'o.opd_id = f.opd_id', 'left')
            ->where('o.p_id', $patientId)
            ->orderBy('f.id', 'DESC')
            ->limit(120);

        if (in_array('show_type', $fields, true)) {
            $builder->groupStart()
                ->where('f.show_type', 0)
                ->orWhere('f.show_type IS NULL', null, false)
                ->groupEnd();
        }

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $raw = str_replace('\\', '/', (string) ($row['full_path'] ?? ''));
            $publicPath = $raw;
            $pos = strpos($raw, '/uploads/');
            if ($pos !== false) {
                $publicPath = substr($raw, $pos);
            }

            $ext = strtolower((string) ($row['file_ext'] ?? pathinfo($publicPath, PATHINFO_EXTENSION)));
            if ($ext !== '' && $ext[0] !== '.') {
                $ext = '.' . $ext;
            }

            $reportText = trim((string) ($row['content_description'] ?? ''));
            if ($reportText === '') {
                $reportText = trim((string) ($row['extracted_text'] ?? $row['extract_text'] ?? ''));
            }

            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'opd_id' => (int) ($row['opd_id'] ?? 0),
                'opd_code' => (string) ($row['opd_code'] ?? ''),
                'opd_date' => !empty($row['apointment_date']) ? date('d-m-Y', strtotime((string) $row['apointment_date'])) : '',
                'path' => $publicPath,
                'is_pdf' => $ext === '.pdf',
                'scan_type' => (string) ($row['scan_type'] ?? ''),
                'document_type' => (string) ($row['document_type'] ?? ''),
                'ai_status' => (string) ($row['ai_status'] ?? ''),
                'ai_alert_flag' => (int) ($row['ai_alert_flag'] ?? 0),
                'ai_alert_text' => (string) ($row['ai_alert_text'] ?? ''),
                'report_text' => $reportText,
                'insert_date' => !empty($row['insert_date']) ? date('d/m/Y H:i', strtotime((string) $row['insert_date'])) : '',
            ];
        }

        return $out;
    }

    private function detectScanTypeFromPath(string $path): string
    {
        $low = strtolower($path);
        if (str_contains($low, 'ecg')) {
            return 'ecg';
        }
        if (str_contains($low, 'lab') || str_contains($low, 'report')) {
            return 'lab';
        }
        return 'general';
    }

    private function resolveLocalFilePathFromStoredPath(string $storedPath): ?string
    {
        $normalized = str_replace('\\', '/', trim($storedPath));
        $uploadsPos = strpos($normalized, '/uploads/');
        if ($uploadsPos === false) {
            return null;
        }

        $relative = substr($normalized, $uploadsPos + 1);
        $relative = str_replace(['../', '..\\'], '', $relative);

        $candidates = [
            rtrim(FCPATH, '/\\') . '/' . $relative,
            rtrim(ROOTPATH, '/\\') . '/public/' . $relative,
            rtrim(ROOTPATH, '/\\') . '/' . $relative,
        ];

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real !== false && is_file($real)) {
                return $real;
            }
        }

        return null;
    }

    private function extractClinicalTextFromScan(string $filePath, string $scanType = ''): ?string
    {
        $apiKey = $this->getGeminiApiKey();
        if ($apiKey === '') {
            return null;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];
        $mime = $mimeMap[$ext] ?? '';
        if ($mime === '') {
            return null;
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false || $raw === '') {
            return null;
        }

        if (strlen($raw) > 8 * 1024 * 1024) {
            return null;
        }

        $typeHint = $scanType !== '' ? strtoupper($scanType) : 'GENERAL';
        $prompt = "You are a medical report transcription assistant.\n"
            . "Extract readable text from this " . $typeHint . " scan.\n"
            . "Return concise structured plain text with sections: Findings, Impression, Values(if present).\n"
            . "Do not invent data. If unreadable, mention unreadable fields.";

        try {
            $client = service('curlrequest', [
                'timeout' => 30,
                'http_errors' => false,
            ]);

            $payload = [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mime,
                                'data' => base64_encode($raw),
                            ],
                        ],
                    ],
                ]],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topP' => 0.8,
                    'maxOutputTokens' => 600,
                ],
            ];

            foreach (['gemini-2.0-flash', 'gemini-1.5-flash'] as $modelName) {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $modelName . ':generateContent?key=' . urlencode($apiKey);
                $response = $client->post($url, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => $payload,
                ]);

                if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                    continue;
                }

                $decoded = json_decode((string) $response->getBody(), true);
                $parts = $decoded['candidates'][0]['content']['parts'] ?? [];
                $text = '';
                foreach ($parts as $part) {
                    $text .= (string) ($part['text'] ?? '');
                }

                $text = trim($text);
                if ($text !== '') {
                    return preg_replace('/\s+/', ' ', $text) ?: $text;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public function opd_prescription_print(int $opdId, int $opdSessionId, int $printType = 0)
    {
        $printConfig = $this->resolvePrescriptionLayoutByDoctorField($opdId, $printType);
        $layoutMode = (string) ($printConfig['layout'] ?? 'content_only');
        $templateKey = (string) ($printConfig['template'] ?? '');

        $url = base_url('Opd/opd_lettre_pdf/' . (int) $opdId)
            . '?session_id=' . (int) $opdSessionId
            . '&layout=' . urlencode($layoutMode);

        if ($templateKey !== '') {
            $url .= '&template=' . urlencode($templateKey);
        }

        return redirect()->to($url);
    }

    /**
     * @return array{layout:string,template:string}
     */
    private function resolvePrescriptionLayoutByDoctorField(int $opdId, int $printType): array
    {
        $allowed = ['full', 'meta_content', 'content_only', 'header_meta', 'meta_only'];
        $defaultByType = [
            0 => 'content_only',
            1 => 'meta_content',
            2 => 'full',
        ];
        $fallback = $defaultByType[$printType] ?? 'content_only';

        $fieldByType = [
            0 => 'rx_plain_paper',
            1 => 'rx_blank_letter_head',
            2 => 'rx_pre_print_letter_head_format',
        ];
        $fieldName = $fieldByType[$printType] ?? '';
        if ($fieldName === '') {
            return ['layout' => $fallback, 'template' => ''];
        }

        $query = $this->db->query(
            'SELECT d.' . $fieldName . ' AS layout_field
             FROM opd_master o
             LEFT JOIN doctor_master d ON d.id = o.doc_id
             WHERE o.opd_id = ?
             LIMIT 1',
            [(int) $opdId]
        );
        $row = $query->getRowArray();
        $raw = strtolower(trim((string) ($row['layout_field'] ?? '')));

        if ($raw === '') {
            return ['layout' => $fallback, 'template' => ''];
        }

        if (in_array($raw, $allowed, true)) {
            return ['layout' => $raw, 'template' => ''];
        }

        if ($raw === '0') {
            return ['layout' => 'content_only', 'template' => ''];
        }
        if ($raw === '1') {
            return ['layout' => 'meta_content', 'template' => ''];
        }
        if ($raw === '2') {
            return ['layout' => 'full', 'template' => ''];
        }

        $templateKey = preg_replace('/[^a-z0-9_\-]/', '', $raw) ?? '';
        if ($templateKey !== '') {
            return ['layout' => $fallback, 'template' => $templateKey];
        }

        return ['layout' => $fallback, 'template' => ''];
    }
}
