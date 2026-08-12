<?php

namespace App\Controllers;

use App\Libraries\ClinicalAuditTrail;
use App\Models\IpdBillingModel;
use App\Models\IpdOtCaseModel;
use App\Models\IpdPreopExaminationModel;
use Config\IpdOtForms;

class IpdOt extends BaseController
{
    private IpdBillingModel $ipdBillingModel;
    private IpdPreopExaminationModel $examinationModel;
    private IpdOtCaseModel $caseModel;
    private IpdOtForms $otConfig;

    public function __construct()
    {
        $this->ipdBillingModel = new IpdBillingModel();
        $this->examinationModel = new IpdPreopExaminationModel();
        $this->caseModel = new IpdOtCaseModel();
        $this->otConfig = config('IpdOtForms');
        helper(['form']);
    }

    public function panel(int $ipdId)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.view', 'ipd_ot.examination.manage'])) {
            return $response;
        }

        $context = $this->getAdmissionContext($ipdId);
        if ($context === []) {
            return $this->response->setStatusCode(404)->setBody('IPD admission not found.');
        }

        $forms = $this->otConfig->formsForDepartment((string) $context['department_name']);
        foreach ($forms as $formKey => &$form) {
            $form['key'] = $formKey;
            $form['record'] = $this->examinationModel->findForAdmission($ipdId, $formKey);
        }
        unset($form);

        $cases = $this->caseModel->where('ipd_id', $ipdId)->orderBy('id', 'DESC')->findAll();

        return view('ipd_ot/panel', [
            'ipd_id' => $ipdId,
            'department_name' => $context['department_name'],
            'forms' => $forms,
            'can_manage' => $this->userCan('ipd_ot.examination.manage'),
            'cases' => $cases,
            'can_request' => $this->userCan('ipd_ot.request.manage'),
            'can_status' => $this->userCan('ipd_ot.status.manage'),
        ]);
    }

    public function queue()
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.view', 'ipd_ot.schedule.manage', 'ipd_ot.status.manage', 'ipd_ot.postop.manage'])) {
            return $response;
        }
        $date = trim((string) ($this->request->getGet('date') ?? date('Y-m-d')));
        $departmentId = max(0, (int) ($this->request->getGet('department_id') ?? 0));
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $builder = $this->db->table('ipd_ot_cases c')
            ->select('c.*, i.ipd_code, p.p_code, p.p_fname, p.mphone1')
            ->join('ipd_master i', 'i.id = c.ipd_id')
            ->join('patient_master p', 'p.id = c.patient_id', 'left');
        if ($date !== '') {
            $builder->where('COALESCE(DATE(c.scheduled_start_at), c.requested_date)', $date, false);
        }
        if ($departmentId > 0) {
            $builder->where('c.department_id', $departmentId);
        }
        if ($status !== '') {
            $builder->where('c.status', $status);
        }
        return view('ipd_ot/queue', [
            'rows' => $builder->orderBy('COALESCE(c.scheduled_start_at, CONCAT(c.requested_date, " ", COALESCE(c.requested_time, "00:00:00")))', 'ASC', false)->get()->getResultArray(),
            'departments' => $this->db->table('hc_department')->select('iId, vName')->where('iId >', 0)->orderBy('vName')->get()->getResultArray(),
            'filter_date' => $date,
            'filter_department_id' => $departmentId,
            'filter_status' => $status,
            'can_schedule' => $this->userCan('ipd_ot.schedule.manage'),
            'can_status' => $this->userCan('ipd_ot.status.manage'),
            'can_postop' => $this->userCan('ipd_ot.postop.manage'),
        ]);
    }

    public function requestForm(int $ipdId)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.request.manage'])) {
            return $response;
        }
        $context = $this->getAdmissionContext($ipdId);
        if ($context === []) {
            return $this->response->setStatusCode(404)->setBody('IPD admission not found.');
        }
        return view('ipd_ot/request_form', ['ipd_id' => $ipdId, 'department_name' => $context['department_name'], 'doctors' => $this->ipdBillingModel->getIpdDoctorList()]);
    }

    public function createCase(int $ipdId)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.request.manage'])) {
            return $response;
        }
        $context = $this->getAdmissionContext($ipdId);
        if ($context === []) {
            return $this->jsonResult(false, 'IPD admission not found.', [], 404);
        }
        $procedureName = trim((string) ($this->request->getPost('procedure_name') ?? ''));
        $requestedDate = trim((string) ($this->request->getPost('requested_date') ?? ''));
        if ($procedureName === '' || ! $this->validDate($requestedDate)) {
            return $this->jsonResult(false, 'Procedure and valid requested date are required.', [], 422);
        }
        $surgeonId = max(0, (int) ($this->request->getPost('surgeon_id') ?? 0));
        $userId = (int) (auth()->user()->id ?? 0);
        $data = [
            'request_no' => 'OT' . date('ymdHis') . str_pad((string) $ipdId, 4, '0', STR_PAD_LEFT),
            'ipd_id' => $ipdId, 'patient_id' => (int) $context['patient_id'],
            'department_id' => (int) $context['department_id'], 'department_name_snapshot' => (string) $context['department_name'],
            'procedure_name' => mb_substr($procedureName, 0, 255),
            'procedure_side' => $this->allowedValue((string) $this->request->getPost('procedure_side'), ['not_applicable', 'left', 'right', 'bilateral'], 'not_applicable'),
            'priority' => $this->allowedValue((string) $this->request->getPost('priority'), ['routine', 'urgent', 'emergency'], 'routine'),
            'requested_date' => $requestedDate,
            'requested_time' => $this->validTime((string) $this->request->getPost('requested_time')) ? (string) $this->request->getPost('requested_time') : null,
            'requested_notes' => mb_substr(trim((string) $this->request->getPost('requested_notes')), 0, 5000),
            'surgeon_id' => $surgeonId, 'surgeon_name_snapshot' => $this->doctorName($surgeonId),
            'status' => 'requested', 'call_status' => 'not_called', 'created_by' => $userId, 'updated_by' => $userId,
        ];
        $this->db->transBegin();
        $this->db->query('SELECT id FROM ipd_master WHERE id = ? FOR UPDATE', [$ipdId]);
        if ($this->caseModel->where('ipd_id', $ipdId)->whereNotIn('status', ['completed', 'cancelled'])->countAllResults() > 0) {
            $this->db->transRollback();
            return $this->jsonResult(false, 'This admission already has an open surgery request.', [], 422);
        }
        if ($this->caseModel->insert($data) === false) {
            $this->db->transRollback();
            return $this->jsonResult(false, 'Unable to create surgery request.', [], 500);
        }
        $caseId = (int) $this->caseModel->getInsertID();
        if (! $this->appendEvent($caseId, 'requested', '', 'requested', null, null, '', (string) $data['requested_notes'])) {
            $this->db->transRollback();
            return $this->jsonResult(false, 'Unable to record surgery request history.', [], 500);
        }
        $this->db->transCommit();
        return $this->jsonResult(true, 'Surgery request created.', ['id' => $caseId]);
    }

    public function caseForm(int $caseId)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.view', 'ipd_ot.schedule.manage', 'ipd_ot.status.manage', 'ipd_ot.postop.manage'])) {
            return $response;
        }
        $case = $this->caseModel->find($caseId);
        if (! is_array($case)) {
            return $this->response->setStatusCode(404)->setBody('OT case not found.');
        }
        $status = (string) $case['status'];
        return view('ipd_ot/case_form', [
            'case' => $case,
            'doctors' => $this->ipdBillingModel->getIpdDoctorList(),
            'can_schedule' => $this->userCan('ipd_ot.schedule.manage') && in_array($status, ['requested', 'scheduled', 'called'], true),
            'can_status' => $this->userCan('ipd_ot.status.manage') && in_array($status, ['requested', 'scheduled', 'called', 'in_progress'], true),
            'can_postop' => $this->userCan('ipd_ot.postop.manage') && in_array($status, ['in_progress', 'completed'], true),
        ]);
    }

    public function updateCase(int $caseId)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.schedule.manage', 'ipd_ot.status.manage'])) {
            return $response;
        }
        $case = $this->caseModel->find($caseId);
        if (! is_array($case)) {
            return $this->jsonResult(false, 'OT case not found.', [], 404);
        }
        $action = trim((string) ($this->request->getPost('action') ?? ''));
        $reason = trim((string) ($this->request->getPost('reason') ?? ''));
        $userId = (int) (auth()->user()->id ?? 0);
        $now = date('Y-m-d H:i:s');
        $updates = ['updated_by' => $userId];
        $fromStatus = (string) $case['status'];
        $toStatus = $fromStatus;
        $oldSchedule = $case['scheduled_start_at'] ?? null;
        $newSchedule = $oldSchedule;

        if (in_array($action, ['schedule', 'reschedule'], true)) {
            if (! $this->userCan('ipd_ot.schedule.manage')) {
                return $this->jsonResult(false, 'Scheduling permission is required.', [], 403);
            }
            $scheduledAt = str_replace('T', ' ', trim((string) $this->request->getPost('scheduled_start_at')));
            $canInitialSchedule = $action === 'schedule' && $fromStatus === 'requested' && empty($oldSchedule);
            $canReschedule = $action === 'reschedule' && in_array($fromStatus, ['scheduled', 'called'], true) && ! empty($oldSchedule);
            if (! $canInitialSchedule && ! $canReschedule) {
                return $this->jsonResult(false, 'This OT case cannot be scheduled from its current status.', [], 422);
            }
            if (! $this->validDateTime($scheduledAt) || ($action === 'reschedule' && $reason === '')) {
                return $this->jsonResult(false, $action === 'reschedule' ? 'Valid date/time and reschedule reason are required.' : 'Valid surgery date and time are required.', [], 422);
            }
            $surgeonId = max(0, (int) ($this->request->getPost('surgeon_id') ?? $case['surgeon_id']));
            $anesthetistId = max(0, (int) ($this->request->getPost('anesthetist_id') ?? $case['anesthetist_id']));
            $updates += ['scheduled_start_at' => $scheduledAt, 'surgeon_id' => $surgeonId, 'surgeon_name_snapshot' => $this->doctorName($surgeonId), 'anesthetist_id' => $anesthetistId, 'anesthetist_name_snapshot' => $this->doctorName($anesthetistId), 'status' => 'scheduled', 'call_status' => 'not_called', 'called_at' => null, 'called_by' => 0, 'confirmed_at' => null, 'confirmed_by' => 0];
            $toStatus = 'scheduled';
            $newSchedule = $scheduledAt;
        } elseif ($action === 'call') {
            if (! $this->userCan('ipd_ot.status.manage')) {
                return $this->jsonResult(false, 'Status permission is required.', [], 403);
            }
            $callStatus = $this->allowedValue((string) $this->request->getPost('call_status'), ['called', 'confirmed', 'not_reachable', 'declined'], 'called');
            if ($callStatus === 'declined' && $reason === '') {
                return $this->jsonResult(false, 'Reason is required when the patient declines.', [], 422);
            }
            $updates += ['call_status' => $callStatus, 'called_at' => $now, 'called_by' => $userId];
            if ($callStatus === 'confirmed') {
                $updates += ['confirmed_at' => $now, 'confirmed_by' => $userId];
            }
        } elseif ($action === 'status') {
            if (! $this->userCan('ipd_ot.status.manage')) {
                return $this->jsonResult(false, 'Status permission is required.', [], 403);
            }
            $nextStatus = $this->allowedValue((string) $this->request->getPost('status'), ['requested', 'scheduled', 'called', 'in_progress', 'completed', 'cancelled'], '');
            $allowedTransitions = ['requested' => ['cancelled'], 'scheduled' => ['called', 'in_progress', 'cancelled'], 'called' => ['scheduled', 'in_progress', 'cancelled'], 'in_progress' => ['completed']];
            if (! in_array($nextStatus, $allowedTransitions[$fromStatus] ?? [], true)) {
                return $this->jsonResult(false, 'Invalid OT status transition.', [], 422);
            }
            if ($nextStatus === 'cancelled' && $reason === '') {
                return $this->jsonResult(false, 'Cancellation reason is required.', [], 422);
            }
            $safetyFields = ['consent_verified', 'site_side_verified', 'allergy_verified', 'npo_verified', 'investigations_verified', 'anesthesia_clearance', 'who_sign_in', 'who_time_out', 'who_sign_out'];
            foreach ($safetyFields as $field) {
                $updates[$field] = (int) ($this->request->getPost($field) ?? 0) === 1 ? 1 : 0;
            }
            $updates['blood_availability'] = $this->allowedValue((string) ($this->request->getPost('blood_availability') ?? $case['blood_availability']), ['not_required', 'arranged', 'available'], 'not_required');
            if ($nextStatus === 'in_progress') {
                foreach (['consent_verified', 'site_side_verified', 'allergy_verified', 'npo_verified', 'investigations_verified', 'anesthesia_clearance', 'who_sign_in'] as $required) {
                    if ((int) $updates[$required] !== 1) {
                        return $this->jsonResult(false, 'Complete all pre-operative safety checks and WHO sign-in first.', [], 422);
                    }
                }
                $updates['actual_start_at'] = $now;
                $updates['who_time_out'] = 0;
                $updates['who_sign_out'] = 0;
            }
            if ($nextStatus === 'completed') {
                if ((int) $updates['who_time_out'] !== 1 || (int) $updates['who_sign_out'] !== 1) {
                    return $this->jsonResult(false, 'WHO time-out and sign-out are required before completion.', [], 422);
                }
                $updates['actual_end_at'] = $now;
            }
            $updates['status'] = $nextStatus;
            $toStatus = $nextStatus;
        } else {
            return $this->jsonResult(false, 'Unsupported OT action.', [], 422);
        }
        $this->db->transBegin();
        $caseUpdated = $this->db->table('ipd_ot_cases')
            ->where('id', $caseId)
            ->where('status', $fromStatus)
            ->where('lock_version', (int) ($case['lock_version'] ?? 0))
            ->update($updates + ['lock_version' => (int) ($case['lock_version'] ?? 0) + 1, 'updated_at' => $now]);
        if (! $caseUpdated) {
            $this->db->transRollback();
            return $this->jsonResult(false, 'Unable to update OT case and history.', [], 500);
        }
        if ($this->db->affectedRows() === 0) {
            $currentCase = $this->caseModel->select('status, lock_version')->find($caseId);
            if ((string) ($currentCase['status'] ?? '') !== $fromStatus || (int) ($currentCase['lock_version'] ?? -1) !== (int) ($case['lock_version'] ?? 0)) {
                $this->db->transRollback();
                return $this->jsonResult(false, 'The OT case changed while you were editing it. Reload and try again.', [], 409);
            }
        }
        if (! $this->appendEvent($caseId, $action, $fromStatus, $toStatus, $oldSchedule, $newSchedule, $reason, trim((string) $this->request->getPost('notes')))) {
            $this->db->transRollback();
            return $this->jsonResult(false, 'Unable to update OT case history.', [], 500);
        }
        $this->db->transCommit();
        return $this->jsonResult(true, 'OT case updated.', ['id' => $caseId]);
    }

    public function postopForm(int $caseId)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.view', 'ipd_ot.postop.manage'])) {
            return $response;
        }
        $case = $this->caseModel->find($caseId);
        if (! is_array($case)) {
            return $this->response->setStatusCode(404)->setBody('OT case not found.');
        }
        return view('ipd_ot/postop_form', ['case' => $case, 'assessments' => $this->db->table('ipd_ot_postop_assessments')->where('case_id', $caseId)->orderBy('observed_at', 'DESC')->get()->getResultArray(), 'can_manage' => $this->userCan('ipd_ot.postop.manage')]);
    }

    public function savePostop(int $caseId)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.postop.manage'])) {
            return $response;
        }
        $case = $this->caseModel->find($caseId);
        if (! is_array($case) || ! in_array((string) $case['status'], ['in_progress', 'completed'], true)) {
            return $this->jsonResult(false, 'Post-operative assessment is available after surgery starts.', [], 422);
        }
        $observedAt = str_replace('T', ' ', trim((string) $this->request->getPost('observed_at')));
        if (! $this->validDateTime($observedAt)) {
            return $this->jsonResult(false, 'Valid observation date and time are required.', [], 422);
        }
        $now = date('Y-m-d H:i:s');
        $consciousness = trim((string) $this->request->getPost('consciousness'));
        $airwayBreathing = trim((string) $this->request->getPost('airway_breathing'));
        $bp = trim((string) $this->request->getPost('bp'));
        $pulse = trim((string) $this->request->getPost('pulse'));
        $spo2 = trim((string) $this->request->getPost('spo2'));
        $disposition = trim((string) $this->request->getPost('disposition'));
        $handoverNotes = trim((string) $this->request->getPost('handover_notes'));
        if ($consciousness === '' || $airwayBreathing === '' || $bp === '' || $pulse === '' || $spo2 === '' || $disposition === '' || $handoverNotes === '') {
            return $this->jsonResult(false, 'Consciousness, airway/breathing, BP, pulse, SpO2, disposition, and handover notes are required.', [], 422);
        }
        if ($observedAt > $now || (! empty($case['actual_start_at']) && $observedAt < (string) $case['actual_start_at'])) {
            return $this->jsonResult(false, 'Observation time must be between surgery start and the current time.', [], 422);
        }
        $this->db->transBegin();
        $assessmentSaved = $this->db->table('ipd_ot_postop_assessments')->insert([
            'case_id' => $caseId, 'observed_at' => $observedAt,
            'consciousness' => mb_substr($consciousness, 0, 80), 'airway_breathing' => mb_substr($airwayBreathing, 0, 255),
            'bp' => mb_substr($bp, 0, 30), 'pulse' => mb_substr($pulse, 0, 20), 'spo2' => mb_substr($spo2, 0, 20), 'temperature' => mb_substr(trim((string) $this->request->getPost('temperature')), 0, 20),
            'pain_score' => max(0, min(10, (int) $this->request->getPost('pain_score'))), 'bleeding_wound_drain' => mb_substr(trim((string) $this->request->getPost('bleeding_wound_drain')), 0, 5000), 'nausea_vomiting' => mb_substr(trim((string) $this->request->getPost('nausea_vomiting')), 0, 100),
            'complications' => mb_substr(trim((string) $this->request->getPost('complications')), 0, 5000), 'interventions' => mb_substr(trim((string) $this->request->getPost('interventions')), 0, 5000), 'disposition' => mb_substr($disposition, 0, 100), 'handover_notes' => mb_substr($handoverNotes, 0, 5000),
            'recorded_by' => (int) (auth()->user()->id ?? 0), 'signed_at' => $now, 'created_at' => $now,
        ]);
        if (! $assessmentSaved
            || ! $this->appendEvent($caseId, 'postop_assessment', (string) $case['status'], (string) $case['status'], $case['scheduled_start_at'] ?? null, $case['scheduled_start_at'] ?? null, '', 'Post-operative assessment signed.')) {
            $this->db->transRollback();
            return $this->jsonResult(false, 'Unable to sign post-operative assessment and history.', [], 500);
        }
        $this->db->transCommit();
        return $this->jsonResult(true, 'Post-operative assessment signed.');
    }

    public function examination(int $ipdId, string $formKey)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.view', 'ipd_ot.examination.manage'])) {
            return $response;
        }

        $resolved = $this->resolveRequestedForm($ipdId, $formKey);
        if (isset($resolved['error'])) {
            return $this->response->setStatusCode((int) $resolved['status'])->setBody((string) $resolved['error']);
        }

        $record = $this->examinationModel->findForAdmission($ipdId, $formKey);
        $values = json_decode((string) ($record['payload_json'] ?? ''), true);
        if (! is_array($values)) {
            $values = [];
        }

        return view('ipd_ot/ophthalmology_preop', [
            'ipd_id' => $ipdId,
            'form_key' => $formKey,
            'form' => $resolved['form'],
            'department_name' => $resolved['context']['department_name'],
            'record' => $record,
            'values' => $values,
            'can_manage' => $this->userCan('ipd_ot.examination.manage'),
        ]);
    }

    public function saveExamination(int $ipdId, string $formKey)
    {
        if ($response = $this->requireAnyPermission(['ipd_ot.examination.manage'])) {
            return $response;
        }

        $resolved = $this->resolveRequestedForm($ipdId, $formKey);
        if (isset($resolved['error'])) {
            return $this->jsonResult(false, (string) $resolved['error'], [], (int) $resolved['status']);
        }

        $form = $resolved['form'];
        $context = $resolved['context'];
        $postedValues = $this->request->getPost('values');
        $postedValues = is_array($postedValues) ? $postedValues : [];
        $values = $this->sanitizeValues($postedValues, $form);
        $status = strtolower(trim((string) ($this->request->getPost('status') ?? 'draft')));
        $status = $status === 'completed' ? 'completed' : 'draft';
        $editReason = trim((string) ($this->request->getPost('edit_reason') ?? ''));
        $existing = $this->examinationModel->findForAdmission($ipdId, $formKey);

        if ($status === 'completed' && ! $this->hasAnyFinding($values)) {
            return $this->jsonResult(false, 'Enter at least one examination finding before completing the form.', [], 422);
        }

        $payloadJson = (string) json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        $hasChanged = $existing === []
            || (string) ($existing['payload_json'] ?? '') !== $payloadJson
            || (string) ($existing['status'] ?? 'draft') !== $status;

        if (! $hasChanged) {
            return $this->jsonResult(true, 'No changes to save.', ['id' => (int) ($existing['id'] ?? 0)]);
        }
        if ((string) ($existing['status'] ?? '') === 'completed' && $editReason === '') {
            return $this->jsonResult(false, 'Edit reason is required when changing a completed examination.', [], 422);
        }

        $userId = (int) (auth()->user()->id ?? 0);
        $now = date('Y-m-d H:i:s');
        $saveData = [
            'ipd_id' => $ipdId,
            'patient_id' => (int) $context['patient_id'],
            'department_id' => (int) $context['department_id'],
            'department_name_snapshot' => (string) $context['department_name'],
            'form_key' => $formKey,
            'schema_version' => (int) ($form['schema_version'] ?? 1),
            'episode_no' => 1,
            'payload_json' => $payloadJson,
            'status' => $status,
            'examined_by' => $status === 'completed' ? $userId : (int) ($existing['examined_by'] ?? 0),
            'examined_at' => $status === 'completed' ? $now : ($existing['examined_at'] ?? null),
            'updated_by' => $userId,
        ];

        $this->db->transStart();
        if ($existing !== []) {
            $revisionNo = (int) ($this->db->table('ipd_preop_examination_updates')
                ->selectMax('revision_no', 'revision_no')
                ->where('examination_id', (int) $existing['id'])
                ->get()
                ->getRowArray()['revision_no'] ?? 0) + 1;
            $this->db->table('ipd_preop_examination_updates')->insert([
                'examination_id' => (int) $existing['id'],
                'revision_no' => $revisionNo,
                'payload_json' => (string) ($existing['payload_json'] ?? ''),
                'status' => (string) ($existing['status'] ?? 'draft'),
                'examined_by' => (int) ($existing['examined_by'] ?? 0),
                'examined_at' => $existing['examined_at'] ?? null,
                'edit_reason' => $editReason,
                'updated_by' => $userId,
                'updated_at' => $now,
            ]);
            $this->examinationModel->update((int) $existing['id'], $saveData);
            $recordId = (int) $existing['id'];
        } else {
            $saveData['created_by'] = $userId;
            $this->examinationModel->insert($saveData);
            $recordId = (int) $this->examinationModel->getInsertID();
        }
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return $this->jsonResult(false, 'Unable to save the examination.', [], 500);
        }

        try {
            (new ClinicalAuditTrail())->logChangedFields(
                'ipd_preop_examination',
                $recordId,
                ['payload' => json_decode((string) ($existing['payload_json'] ?? ''), true), 'status' => $existing['status'] ?? null],
                ['payload' => $values, 'status' => $status],
                $userId
            );
        } catch (\Throwable $exception) {
            log_message('error', 'Unable to write pre-operative examination audit: ' . $exception->getMessage());
        }

        return $this->jsonResult(true, 'Pre-operative examination saved.', ['id' => $recordId]);
    }

    private function resolveRequestedForm(int $ipdId, string $formKey): array
    {
        $context = $this->getAdmissionContext($ipdId);
        if ($context === []) {
            return ['error' => 'IPD admission not found.', 'status' => 404];
        }

        $forms = $this->otConfig->formsForDepartment((string) $context['department_name']);
        if (! isset($forms[$formKey])) {
            return ['error' => 'This examination is not configured for the admission department.', 'status' => 403];
        }

        return ['context' => $context, 'form' => $forms[$formKey]];
    }

    private function appendEvent(int $caseId, string $eventType, string $fromStatus, string $toStatus, $oldSchedule, $newSchedule, string $reason, string $notes): bool
    {
        return $this->db->table('ipd_ot_case_events')->insert(['case_id' => $caseId, 'event_type' => $eventType, 'from_status' => $fromStatus, 'to_status' => $toStatus, 'old_scheduled_at' => $oldSchedule ?: null, 'new_scheduled_at' => $newSchedule ?: null, 'reason' => mb_substr($reason, 0, 255), 'notes' => mb_substr($notes, 0, 5000), 'actor_id' => (int) (auth()->user()->id ?? 0), 'created_at' => date('Y-m-d H:i:s')]);
    }

    private function doctorName(int $doctorId): string
    {
        if ($doctorId <= 0) {
            return '';
        }
        $row = $this->db->table('doctor_master')->select("TRIM(CONCAT_WS(' ', p_fname, p_mname, p_lname)) AS name", false)->where('id', $doctorId)->get(1)->getRowArray();
        return trim((string) ($row['name'] ?? ''));
    }

    private function allowedValue(string $value, array $allowed, string $default): string
    {
        $value = strtolower(trim($value));
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function validDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function validTime(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', trim($value)) === 1;
    }

    private function validDateTime(string $value): bool
    {
        $value = trim($value);
        foreach (['Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return true;
            }
        }
        return false;
    }

    private function getAdmissionContext(int $ipdId): array
    {
        $row = $this->db->table('ipd_master i')
            ->select('i.id, i.p_id, i.dept_id, d.vName AS department_name')
            ->join('hc_department d', 'd.iId = i.dept_id', 'left')
            ->where('i.id', $ipdId)
            ->get(1)
            ->getRowArray();

        if ($row === null) {
            return [];
        }

        return [
            'patient_id' => (int) ($row['p_id'] ?? 0),
            'department_id' => (int) ($row['dept_id'] ?? 0),
            'department_name' => trim((string) ($row['department_name'] ?? '')),
        ];
    }

    private function sanitizeValues(array $postedValues, array $form): array
    {
        $values = [];
        foreach (($form['rows'] ?? []) as $rowKey => $unusedLabel) {
            foreach (($form['columns'] ?? []) as $columnKey => $unusedColumnLabel) {
                $value = trim((string) ($postedValues[$rowKey][$columnKey] ?? ''));
                $values[(string) $rowKey][(string) $columnKey] = mb_substr($value, 0, 5000);
            }
        }
        return $values;
    }

    private function hasAnyFinding(array $values): bool
    {
        foreach ($values as $rowValues) {
            foreach ($rowValues as $value) {
                if (trim((string) $value) !== '') {
                    return true;
                }
            }
        }
        return false;
    }

    private function requireAnyPermission(array $permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->userCan($permission)) {
                return null;
            }
        }
        return $this->response->setStatusCode(403)->setBody('Access denied');
    }

    private function userCan(string $permission): bool
    {
        if (! function_exists('auth')) {
            return false;
        }
        $user = auth()->user();
        return $user !== null && method_exists($user, 'can') && $user->can($permission);
    }

    private function jsonResult(bool $success, string $message, array $extra = [], int $statusCode = 200)
    {
        return $this->response->setStatusCode($statusCode)->setJSON(array_merge([
            'update' => $success ? 1 : 0,
            'error_text' => $message,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ], $extra));
    }
}