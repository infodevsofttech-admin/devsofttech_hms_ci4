<?php

namespace App\Controllers;

use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\Abdm\EAtriaBridgeConnector;

/**
 * AbdmFhirInspector
 *
 * Dedicated standalone ABDM Page for "ABHA Filter & FHIR Inspector":
 *  - Global ABHA Search & Filter across all patient records and care contexts
 *  - Live ABDM Gateway Verification (Bridge / National Registry)
 *  - Full Patient FHIR R4 Bundle Inspector (OPConsult, DiagnosticReport, HealthDocument, Wellness, Invoice, Immunization, DischargeSummary)
 *  - One-click FHIR Preview with formatted JSON viewer and validation diagnostics
 *  - Direct Care Context Linking to ABHA
 */
class AbdmFhirInspector extends BaseController
{
    protected ?EAtriaBridgeConnector $connector = null;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        try {
            $this->connector = AbdmConnectorFactory::bridge();
        } catch (\Throwable $e) {
            $this->connector = null;
        }
    }

    /**
     * Main page view for ABHA Filter & FHIR Inspector
     */
    public function index()
    {
        $filterAbha = trim((string) ($this->request->getGet('abha_address') ?? $this->request->getGet('abha') ?? ''));
        $patientId = (int) ($this->request->getGet('patient_id') ?? $this->request->getGet('id') ?? 0);
        $uhid = trim((string) ($this->request->getGet('uhid') ?? ''));

        $abhaPatients = $this->getAbhaPatientsList();

        return view('abdm/fhir_inspector', [
            'filter_abha'   => $filterAbha,
            'patient_id'    => $patientId,
            'filter_uhid'   => $uhid,
            'abha_patients' => $abhaPatients,
            'recent_pills'  => array_slice($abhaPatients, 0, 8),
        ]);
    }

    /**
     * AJAX endpoint: Get patient demographics, care contexts, and link status
     * GET /AbdmFhirInspector/patient_data?abha_address=...&patient_id=...
     */
    public function patientData()
    {
        $patientId = (int) ($this->request->getGet('patient_id') ?? 0);
        $abhaAddress = trim((string) ($this->request->getGet('abha_address') ?? $this->request->getGet('abha') ?? ''));
        $pCode = trim((string) ($this->request->getGet('p_code') ?? $this->request->getGet('uhid') ?? ''));

        if ($patientId <= 0 && $abhaAddress === '' && $pCode === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA Address, Patient ID, or UHID is required']);
        }

        // Delegate to AbdmGateway for canonical patient discovery & care contexts
        try {
            $gateway = new \App\Controllers\AbdmGateway();
            $gateway->initController($this->request, $this->response, service('logger'));
            return $gateway->hipPatientCareContexts();
        } catch (\Throwable $e) {
            log_message('error', '[AbdmFhirInspector::patientData] {msg}', ['msg' => $e->getMessage()]);
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => 'Unable to load patient care contexts: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX endpoint: Live Gateway Link Check for an ABHA address
     * GET /AbdmFhirInspector/gateway_check?abha_address=...
     */
    public function gatewayCheck()
    {
        $abha = trim((string) ($this->request->getGet('abha_address') ?? $this->request->getGet('abha') ?? ''));
        if ($abha === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA address is required']);
        }

        try {
            if ($this->connector === null) {
                $this->connector = AbdmConnectorFactory::bridge();
            }
            $result = $this->connector->hipGetPatientLinks(['abha_address' => $abha]);
            $careContexts = [];
            if (! empty($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $patientRec) {
                    foreach ($patientRec['careContexts'] ?? [] as $cc) {
                        $careContexts[] = [
                            'reference' => trim((string) ($cc['referenceNumber'] ?? $cc['ref'] ?? '')),
                            'display'   => trim((string) ($cc['display'] ?? '')),
                            'status'    => trim((string) ($cc['status'] ?? 'linked')),
                        ];
                    }
                }
            }

            return $this->response->setJSON([
                'ok'            => 1,
                'abha_address'  => $abha,
                'count'         => count($careContexts),
                'care_contexts' => $careContexts,
                'raw_bridge'    => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'ok'         => 0,
                'error_text' => 'Bridge check failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX endpoint: Preview FHIR R4 Bundle for any care context reference
     * GET /AbdmFhirInspector/preview_bundle?ref=...&patient_id=...&abha=...
     */
    public function previewBundle()
    {
        $ref = trim((string) ($this->request->getGet('ref') ?? ''));
        $patientId = (int) ($this->request->getGet('patient_id') ?? 0);
        $abha = trim((string) ($this->request->getGet('abha') ?? ''));

        if ($ref === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Care Context Reference is required']);
        }

        // 1. Check health_records table for stored bundle
        if ($this->db->tableExists('health_records')) {
            $hr = $this->db->table('health_records')
                ->where('care_context_reference', $ref)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($hr)) {
                $rawBundle = $hr['fhir_bundle'] ?? $hr['bundle'] ?? null;
                $decoded = null;
                if (is_string($rawBundle) && $rawBundle !== '') {
                    $decoded = json_decode($rawBundle, true);
                } elseif (is_array($rawBundle)) {
                    $decoded = $rawBundle;
                }

                if (! empty($decoded)) {
                    return $this->response->setJSON([
                        'ok'                     => 1,
                        'source'                 => 'health_records_stored',
                        'care_context_reference' => $ref,
                        'care_context_display'   => $hr['care_context_display'] ?? $ref,
                        'hi_type'                => $hr['hi_type'] ?? 'HealthRecord',
                        'bundle'                 => $decoded,
                        'fhir_bundle'            => $decoded,
                        'resource_type'          => $decoded['resourceType'] ?? 'Bundle',
                        'total_entries'          => count($decoded['entry'] ?? []),
                        'entry_types'            => array_values(array_unique(array_filter(array_map(function ($e) {
                            return $e['resource']['resourceType'] ?? null;
                        }, $decoded['entry'] ?? [])))),
                    ]);
                }
            }
        }

        // 2. Dynamic generation based on prefix
        try {
            // OPD Consult
            if (preg_match('/^OPD-(\d+)(?:-S(\d+))?/i', $ref, $m)) {
                $opdId = (int) $m[1];
                $sessionId = ! empty($m[2]) ? (int) $m[2] : $opdId;
                $opdController = new \App\Controllers\Opd_prescription();
                $opdController->initController($this->request, $this->response, service('logger'));
                return $opdController->fhir_bundle_preview($opdId, $sessionId);
            }

            // Health Document: DOC-file-{id} or DOC-{id}
            if (preg_match('/^DOC-(?:file-)?(\d+)/i', $ref, $m)) {
                $docId = (int) $m[1];
                $docController = new \App\Controllers\DoctorDocument();
                $docController->initController($this->request, $this->response, service('logger'));
                return $docController->health_document_fhir_preview($docId);
            }

            // Diagnostic Report: LAB-{id} or RAD-{id}
            if (preg_match('/^(?:LAB|RAD)-(\d+)/i', $ref, $m)) {
                $labId = (int) $m[1];
                $gw = new \App\Controllers\AbdmGateway();
                $gw->initController($this->request, $this->response, service('logger'));
                // Simulate request param
                $_GET['request_id'] = $labId;
                return $gw->diagnosisReportFhirPreview();
            }

            // Invoices: INVOICE-OPD-{id}, INVOICE-CHG-{id}, INVOICE-IPD-{id}
            if (preg_match('/^INVOICE-([A-Za-z]+)-(\d+)/i', $ref, $m)) {
                $typeCode = strtoupper($m[1]);
                $billId = (int) $m[2];
                $source = match ($typeCode) {
                    'OPD' => 'opd_invoice',
                    'CHG' => 'charges_invoice',
                    'IPD' => 'ipd_invoice',
                    default => 'opd_invoice'
                };
                $gw = new \App\Controllers\AbdmGateway();
                $gw->initController($this->request, $this->response, service('logger'));
                $_GET['source'] = $source;
                $_GET['bill_id'] = $billId;
                $_GET['patient_id'] = $patientId;
                return $gw->invoiceFhirPreview();
            }

            // Immunization: IMM-{id}
            if (preg_match('/^IMM-(\d+)/i', $ref, $m)) {
                $immId = (int) $m[1];
                $gw = new \App\Controllers\AbdmGateway();
                $gw->initController($this->request, $this->response, service('logger'));
                $_GET['record_id'] = $immId;
                $_GET['patient_id'] = $patientId;
                return $gw->immunizationFhirPreview();
            }

            // Discharge Summary: DISCHARGE-{id}
            if (preg_match('/^DISCHARGE-(\d+)/i', $ref, $m)) {
                $ipdId = (int) $m[1];
                $gw = new \App\Controllers\AbdmGateway();
                $gw->initController($this->request, $this->response, service('logger'));
                $_GET['ipd_id'] = $ipdId;
                return $gw->ipdDischargeFhirPreview();
            }

            // Wellness Record: WELLNESS-{id}
            if (preg_match('/^WELLNESS-(?:(\d+)-W)?(\d+)/i', $ref, $m)) {
                $wId = ! empty($m[2]) ? (int) $m[2] : (int) $m[1];
                $pId = ! empty($m[1]) ? (int) $m[1] : $patientId;
                $docController = new \App\Controllers\DoctorDocument();
                $docController->initController($this->request, $this->response, service('logger'));
                return $docController->wellness_record_fhir_preview($pId, $wId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[AbdmFhirInspector::previewBundle] Generation failed: ' . $e->getMessage());
        }

        return $this->response->setStatusCode(404)->setJSON([
            'ok'         => 0,
            'error_text' => 'Unable to dynamically generate FHIR bundle for care context: ' . $ref,
        ]);
    }

    /**
     * Query patient_master for patients with ABHA data
     */
    private function getAbhaPatientsList(): array
    {
        if (! $this->db->tableExists('patient_master')) {
            return [];
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $hasAbhaAddress = in_array('abha_address', $fields, true);
        $hasAbhaId = in_array('abha_id', $fields, true);
        $hasAbhaNo = in_array('abha_no', $fields, true);

        if (! $hasAbhaAddress && ! $hasAbhaId && ! $hasAbhaNo) {
            return [];
        }

        $select = ['id', 'p_code', 'p_fname', 'gender', 'dob'];
        if (in_array('p_lname', $fields, true)) {
            $select[] = 'p_lname';
        }
        if (in_array('mphone1', $fields, true)) {
            $select[] = 'mphone1';
        }
        if ($hasAbhaAddress) {
            $select[] = 'abha_address';
        }
        if ($hasAbhaId) {
            $select[] = 'abha_id';
        }
        if ($hasAbhaNo) {
            $select[] = 'abha_no';
        }

        $builder = $this->db->table('patient_master')->select(implode(', ', $select));

        $whereOr = [];
        if ($hasAbhaAddress) {
            $whereOr[] = "NULLIF(TRIM(abha_address), '') IS NOT NULL";
        }
        if ($hasAbhaId) {
            $whereOr[] = "NULLIF(TRIM(abha_id), '') IS NOT NULL";
        }
        if ($hasAbhaNo) {
            $whereOr[] = "NULLIF(TRIM(abha_no), '') IS NOT NULL";
        }

        if ($whereOr !== []) {
            $builder->where('(' . implode(' OR ', $whereOr) . ')', null, false);
        }

        $rows = $builder->orderBy('id', 'DESC')->limit(300)->get()->getResultArray();
        $patients = [];

        foreach ($rows as $row) {
            $rawAddress = trim((string) ($row['abha_address'] ?? ''));
            $rawId = trim((string) ($row['abha_id'] ?? $row['abha_no'] ?? ''));

            $addr = '';
            $num = '';

            if (str_contains($rawAddress, '@')) {
                $addr = $rawAddress;
            } elseif (str_contains($rawId, '@')) {
                $addr = $rawId;
            }

            $d1 = preg_replace('/\D/', '', $rawId);
            $d2 = preg_replace('/\D/', '', $rawAddress);
            if (is_string($d1) && strlen($d1) === 14) {
                $num = $d1;
            } elseif (is_string($d2) && strlen($d2) === 14) {
                $num = $d2;
            }

            if ($addr === '' && $num === '') {
                continue;
            }

            $name = trim(($row['p_fname'] ?? '') . ' ' . ($row['p_lname'] ?? ''));
            if ($name === '') {
                $name = 'Patient #' . $row['id'];
            }

            $patients[] = [
                'id'           => (int) $row['id'],
                'p_code'       => (string) ($row['p_code'] ?? ('P-' . $row['id'])),
                'name'         => $name,
                'gender'       => (string) ($row['gender'] ?? 'M'),
                'dob'          => (string) ($row['dob'] ?? ''),
                'phone'        => (string) ($row['mphone1'] ?? ''),
                'abha_address' => $addr,
                'abha_number'  => $num,
            ];
        }

        return $patients;
    }
}
