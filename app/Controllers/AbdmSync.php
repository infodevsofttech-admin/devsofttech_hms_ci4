<?php

namespace App\Controllers;

use App\Libraries\Abdm\Sync\AbdmSyncOutboxService;

class AbdmSync extends BaseController
{
    public function enqueuePatient()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'AJAX only']);
        }

        $payload = [
            'local_patient_id' => (int) $this->request->getPost('local_patient_id'),
            'abha_id' => (string) ($this->request->getPost('abha_id') ?? ''),
            'abha_address' => (string) ($this->request->getPost('abha_address') ?? ''),
            'name' => (string) ($this->request->getPost('name') ?? ''),
            'gender' => (string) ($this->request->getPost('gender') ?? ''),
            'dob' => (string) ($this->request->getPost('dob') ?? ''),
            'mobile' => (string) ($this->request->getPost('mobile') ?? ''),
            'email' => (string) ($this->request->getPost('email') ?? ''),
            'source_updated_at' => (string) ($this->request->getPost('source_updated_at') ?? date('Y-m-d H:i:s')),
            'hfr_id' => (string) ($this->request->getPost('hfr_id') ?? ''),
        ];

        $service = new AbdmSyncOutboxService();
        $id = $service->enqueuePatientSync($payload);

        return $this->response->setJSON([
            'ok' => $id !== null ? 1 : 0,
            'outbox_id' => $id,
        ]);
    }

    public function enqueueRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'AJAX only']);
        }

        $bundleRaw = (string) ($this->request->getPost('fhir_bundle_json') ?? '{}');
        $bundle = json_decode($bundleRaw, true);
        if (! is_array($bundle)) {
            $bundle = [];
        }

        $payload = [
            'local_record_id' => (string) ($this->request->getPost('local_record_id') ?? ''),
            'local_patient_id' => (int) $this->request->getPost('local_patient_id'),
            'hi_type' => (string) ($this->request->getPost('hi_type') ?? ''),
            'care_context_reference' => (string) ($this->request->getPost('care_context_reference') ?? ''),
            'care_context_display' => (string) ($this->request->getPost('care_context_display') ?? ''),
            'visit_date' => (string) ($this->request->getPost('visit_date') ?? ''),
            'department' => (string) ($this->request->getPost('department') ?? ''),
            'doctor_name' => (string) ($this->request->getPost('doctor_name') ?? ''),
            'consent_id' => (string) ($this->request->getPost('consent_id') ?? ''),
            'hfr_id' => (string) ($this->request->getPost('hfr_id') ?? ''),
            'source_updated_at' => (string) ($this->request->getPost('source_updated_at') ?? date('Y-m-d H:i:s')),
            'patient_name' => (string) ($this->request->getPost('patient_name') ?? ''),
            'abha_id' => (string) ($this->request->getPost('abha_id') ?? ''),
            'abha_address' => (string) ($this->request->getPost('abha_address') ?? ''),
            'fhir_bundle' => $bundle,
        ];

        $service = new AbdmSyncOutboxService();
        $id = $service->enqueueRecordSync($payload);

        return $this->response->setJSON([
            'ok' => $id !== null ? 1 : 0,
            'outbox_id' => $id,
        ]);
    }

    public function summary()
    {
        $service = new AbdmSyncOutboxService();
        return $this->response->setJSON([
            'ok' => 1,
            'data' => $service->getCounters(),
        ]);
    }

    public function replayDead(int $id = 0)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'AJAX only']);
        }

        if ($id <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid outbox id']);
        }

        $service = new AbdmSyncOutboxService();
        $ok = $service->replayDeadRow($id);

        return $this->response->setJSON([
            'ok' => $ok ? 1 : 0,
            'message' => $ok ? 'Dead row moved to pending.' : 'Outbox row not found.',
        ]);
    }
}
