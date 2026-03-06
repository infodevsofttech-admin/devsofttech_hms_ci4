<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\IpdBillingModel;

class Ipd_discharge extends BaseController
{
    protected IpdBillingModel $ipdBillingModel;

    public function __construct()
    {
        $this->ipdBillingModel = new IpdBillingModel();
        helper(['form', 'age']);
    }

    private function dischargeTabUrl(int $ipdId): string
    {
        return site_url('billing/ipd/panel/' . $ipdId . '/tab/discharge-process');
    }

    private function requireAnyPermission(array $permissions)
    {
        if (! function_exists('auth')) {
            return null;
        }

        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return null;
            }
        }

        return $this->response->setStatusCode(403)->setBody('Access denied');
    }

    private function getDischargeContent(int $ipdId): string
    {
        if (! $this->db->tableExists('ipd_discharge')) {
            return '';
        }

        $row = $this->db->table('ipd_discharge')
            ->where('ipd_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return (string) ($row['content'] ?? '');
    }

    private function currentUserLabel(): string
    {
        if (! function_exists('auth')) {
            return 'system';
        }

        $user = auth()->user();
        if (! $user) {
            return 'system';
        }

        $name = (string) ($user->username ?? $user->email ?? '');
        if ($name !== '') {
            return $name;
        }

        return 'user-' . (string) ($user->id ?? 0);
    }

    private function saveDischargeContent(int $ipdId, string $content): bool
    {
        try {
            if ($this->db->tableExists('ipd_master') && $this->db->fieldExists('ipd_create', 'ipd_master')) {
                $this->db->table('ipd_master')
                    ->where('id', $ipdId)
                    ->update(['ipd_create' => 1]);
            }

            if (! $this->db->tableExists('ipd_discharge') || ! $this->db->fieldExists('content', 'ipd_discharge')) {
                return false;
            }

            $builder = $this->db->table('ipd_discharge');
            $existing = $builder
                ->where('ipd_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($existing['id'])) {
                return (bool) $builder
                    ->where('id', (int) $existing['id'])
                    ->update(['content' => $content]);
            }

            $insert = [
                'ipd_id' => $ipdId,
                'content' => $content,
            ];

            // Legacy schema has NOT NULL columns without defaults.
            $userLabel = substr($this->currentUserLabel(), 0, 50);
            if ($this->db->fieldExists('created_by', 'ipd_discharge')) {
                $insert['created_by'] = $userLabel;
            }
            if ($this->db->fieldExists('checked_by', 'ipd_discharge')) {
                $insert['checked_by'] = $userLabel;
            }
            if ($this->db->fieldExists('created_datetime', 'ipd_discharge')) {
                $insert['created_datetime'] = date('Y-m-d H:i:s');
            }
            if ($this->db->fieldExists('ipd_discharge_print', 'ipd_discharge')) {
                $insert['ipd_discharge_print'] = 0;
            }

            return (bool) $builder->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Discharge content save failed for IPD {ipd}: {msg}', [
                'ipd' => $ipdId,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! $this->db->fieldExists($column, $table)) {
                return false;
            }
        }

        return true;
    }

    private function byIpdRows(string $table, array $columns = ['*'], string $orderBy = 'id ASC', int $ipdId = 0): array
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns($table, ['ipd_id'])) {
            return [];
        }

        $builder = $this->db->table($table)->where('ipd_id', $ipdId);
        if ($columns !== ['*']) {
            $builder->select(implode(',', $columns));
        }

        if ($orderBy !== '') {
            $parts = explode(' ', trim($orderBy));
            $field = $parts[0] ?? 'id';
            $dir = strtoupper($parts[1] ?? 'ASC');
            $builder->orderBy($field, $dir === 'DESC' ? 'DESC' : 'ASC');
        }

        return $builder->get()->getResultArray();
    }

    private function safeDate(?string $dateValue): string
    {
        $value = trim((string) $dateValue);
        if ($value === '' || $value === '0000-00-00' || $value === '1901-01-01') {
            return '';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }

        return date('d-m-Y', $ts);
    }

    private function addListSection(array &$sections, string $title, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $html = '<h4 style="margin:16px 0 8px 0;">' . esc($title) . '</h4><ul style="margin:0 0 10px 20px;">';
        foreach ($rows as $row) {
            $report = trim((string) ($row['comp_report'] ?? ''));
            $remark = trim((string) ($row['comp_remark'] ?? ''));
            if ($report === '' && $remark === '') {
                continue;
            }

            $line = esc($report);
            if ($remark !== '') {
                $line .= ' <span style="color:#475569;">(' . esc($remark) . ')</span>';
            }

            $html .= '<li>' . $line . '</li>';
        }
        $html .= '</ul>';

        $sections[] = $html;
    }

    private function buildAutoDischargeContent(int $ipdId, array $panelData): string
    {
        $ipd = $panelData['ipd_info'] ?? null;
        $person = $panelData['person_info'] ?? null;

        $patientName = trim((string) ($person->p_fname ?? ''));
        $patientCode = trim((string) (
            $person->uhid
            ?? $person->UHID
            ?? $person->patient_code
            ?? $person->p_code
            ?? $person->reg_no
            ?? ''
        ));
        $patientId = (int) ($person->id ?? 0);

        $sections = [];

        $sections[] = '<h3 style="margin:0 0 10px 0;">IPD Discharge Summary</h3>';
        $sections[] = '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">'
            . '<tr>'
            . '<td><b>IPD</b>: ' . esc((string) ($ipd->ipd_code ?? $ipdId)) . '</td>'
            . '<td><b>UHID</b>: ' . esc($patientCode) . '</td>'
            . '<td><b>Date</b>: ' . esc(date('d-m-Y')) . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td><b>Patient</b>: ' . esc($patientName) . '</td>'
            . '<td><b>Admit Date</b>: ' . esc((string) ($ipd->str_register_date ?? '')) . '</td>'
            . '<td><b>Discharge Date</b>: ' . esc((string) ($ipd->str_discharge_date ?? '')) . '</td>'
            . '</tr>'
            . '</table>';

        $complaints = $this->byIpdRows('ipd_discharge_complaint', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $this->addListSection($sections, 'Presenting Complaints and Reason for Admission', $complaints);
        $complaintRemark = $this->firstRowByIpd('ipd_discharge_complaint_remark', $ipdId);
        $complaintRemarkText = trim((string) ($complaintRemark['comp_remark'] ?? ''));
        if ($complaintRemarkText !== '') {
            $sections[] = '<div>' . nl2br(esc($complaintRemarkText)) . '</div>';
        }

        $physicalExamRows = $this->getPhysicalExamRows($ipdId);
        $generalRows = array_merge(
            $physicalExamRows['general_group_1'] ?? [],
            $physicalExamRows['general_group_2'] ?? []
        );
        if (! empty($generalRows)) {
            $html = '<h4 style="margin:16px 0 8px 0;">General Examination on Admission</h4>'
                . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">';

            $col = 0;
            $perRow = 4;
            foreach ($generalRows as $row) {
                if ($col % $perRow === 0) {
                    $html .= '<tr>';
                }

                $label = (string) ($row['label'] ?? '');
                $value = trim((string) ($row['value'] ?? ''));
                $html .= '<td><b>' . esc($label) . ':</b> ' . esc($value) . '</td>';

                if ($col % $perRow === $perRow - 1) {
                    $html .= '</tr>';
                }
                $col++;
            }

            while ($col % $perRow !== 0) {
                $html .= '<td>&nbsp;</td>';
                if ($col % $perRow === $perRow - 1) {
                    $html .= '</tr>';
                }
                $col++;
            }

            $html .= '</table>';
            $sections[] = $html;
        }

        $sysRows = $physicalExamRows['systemic'] ?? [];
        $sysHtml = '';
        foreach ($sysRows as $row) {
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $name = trim((string) ($row['name'] ?? 'Systemic Examination'));
            $sysHtml .= '<div style="margin-bottom:6px;"><b>' . esc($name) . ':</b> ' . nl2br(esc($value)) . '</div>';
        }
        if ($sysHtml !== '') {
            $sections[] = '<h4 style="margin:16px 0 8px 0;">Other / Systemic Examinations</h4>' . $sysHtml;
        }

        $personalHistory = [];
        if ($patientId > 0 && $this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
            $historyMap = [
                'is_smoking' => 'Smoking',
                'is_alcohol' => 'Alcohol',
                'is_drug_abuse' => 'Drug abuse',
                'is_tobacoo' => 'Tobacco',
                'is_hypertesion' => 'Hypertension',
                'is_niddm' => 'Type 2 diabetes mellitus (DM)',
                'is_hbsag' => 'HBsAg',
                'is_hcv' => 'HCV',
                'is_hiv_I_II' => 'HIV I & II',
                'Others' => 'Others',
            ];

            foreach ($historyMap as $field => $label) {
                if ((int) ($patientRow[$field] ?? 0) === 1) {
                    $personalHistory[] = $label;
                }
            }
        }

        if (! empty($personalHistory)) {
            $sections[] = '<h4 style="margin:16px 0 8px 0;">Personal History</h4><div>' . esc(implode(', ', $personalHistory)) . '</div>';
        }

        $admitDate = $this->normalizeDateValue((string) ($ipd->register_date ?? '')) ?? '';
        $dischargeDate = $this->normalizeDateValue((string) ($ipd->discharge_date ?? '')) ?? date('Y-m-d');
        $savedClinicalDates = $this->getSavedClinicalLabDates($ipdId);
        $clinicalLabRows = $this->getClinicalInvestigationLabRows($patientId, $admitDate, $dischargeDate, $savedClinicalDates);

        $effectiveClinicalDates = $savedClinicalDates;
        if (empty($effectiveClinicalDates)) {
            foreach ($clinicalLabRows as $row) {
                $dt = $this->normalizeDateValue((string) ($row['inv_date'] ?? ''));
                if ($dt !== null) {
                    $effectiveClinicalDates[$dt] = $dt;
                }
            }
            $effectiveClinicalDates = array_values($effectiveClinicalDates);
        }

        $pathologyMatrix = $this->getClinicalPathologyMatrixRows($patientId, $effectiveClinicalDates);

        $selectedLabRows = [];
        foreach ($clinicalLabRows as $row) {
            if (! empty($savedClinicalDates)) {
                if (! empty($row['checked'])) {
                    $selectedLabRows[] = $row;
                }
                continue;
            }

            // No saved selection yet: include all available pathology dates as fallback.
            $selectedLabRows[] = $row;
        }

        $otherExamRow = [];
        if ($this->tableHasColumns('ipd_discharge_2', ['ipd_d_id'])) {
            $otherExamRow = $this->db->table('ipd_discharge_2')
                ->where('ipd_d_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray() ?? [];
        }
        $otherExamParsed = $this->parseClinicalOtherExamPayload((string) ($otherExamRow['rdata'] ?? ''));
        $nonPathRows = $this->getClinicalNonPathReportRows($patientId, $admitDate, $dischargeDate, $otherExamParsed['non_path_ids'] ?? []);
        $selectedNonPathRows = [];
        foreach ($nonPathRows as $row) {
            if (! empty($row['checked'])) {
                $selectedNonPathRows[] = $row;
            }
        }

        if (! empty($pathologyMatrix['rows']) || ! empty($selectedLabRows) || ! empty($selectedNonPathRows) || trim((string) ($otherExamParsed['text'] ?? '')) !== '') {
            $html = '<h4 style="margin:16px 0 8px 0;">Clinical Investigation Reports</h4>';

            if (! empty($pathologyMatrix['rows'])) {
                $html .= '<div><b>In-Hospital Lab:</b></div>'
                    . '<table style="width:100%;border-collapse:collapse;margin:4px 0 8px 0;" border="1" cellpadding="6">'
                    . '<tr>'
                    . '<th style="text-align:left;">Test</th>'
                    . '<th style="text-align:left;">Fixed Normals</th>';

                foreach (($pathologyMatrix['dates'] ?? []) as $dt) {
                    $html .= '<th style="text-align:left;">' . esc((string) ($pathologyMatrix['date_labels'][$dt] ?? $dt)) . '</th>';
                }

                $html .= '</tr>';

                foreach (($pathologyMatrix['rows'] ?? []) as $row) {
                    $html .= '<tr>'
                        . '<td>' . esc((string) ($row['test_name'] ?? '')) . '</td>'
                        . '<td>' . esc((string) ($row['fixed_normals'] ?? '')) . '</td>';

                    foreach (($pathologyMatrix['dates'] ?? []) as $dt) {
                        $html .= '<td>' . esc((string) ($row['values'][$dt] ?? '')) . '</td>';
                    }

                    $html .= '</tr>';
                }

                $html .= '</table>';
            } elseif (! empty($selectedLabRows)) {
                // Fallback when value-matrix cannot be built from current schema/data.
                $html .= '<div><b>In-Hospital Lab:</b></div><ul style="margin:4px 0 8px 20px;">';
                foreach ($selectedLabRows as $row) {
                    $html .= '<li>[' . esc((string) ($row['inv_date_label'] ?? '')) . '] ' . esc((string) ($row['test_list'] ?? '')) . '</li>';
                }
                $html .= '</ul>';
            }

            if (! empty($selectedNonPathRows)) {
                $html .= '<div><b>X-Ray / ECG / Sonography / CT / MRI:</b></div><ul style="margin:4px 0 8px 20px;">';
                foreach ($selectedNonPathRows as $row) {
                    $html .= '<li>[' . esc((string) ($row['report_date_label'] ?? '')) . '] '
                        . esc((string) ($row['modality'] ?? ''))
                        . ' - ' . esc((string) ($row['report_name'] ?? ''));

                    $impression = trim((string) ($row['impression'] ?? ''));
                    if ($impression !== '') {
                        $html .= '<br><span style="color:#475569;">Impression: ' . nl2br(esc($impression)) . '</span>';
                    }

                    $html .= '</li>';
                }
                $html .= '</ul>';
            }

            $otherExamText = trim((string) ($otherExamParsed['text'] ?? ''));
            if ($otherExamText !== '') {
                $html .= '<div><b>Other Examinations / Provisional Diagnosis:</b><br>' . nl2br(esc($otherExamText)) . '</div>';
            }

            $sections[] = $html;
        }

        $diagnosis = $this->byIpdRows('ipd_discharge_diagnosis', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $this->addListSection($sections, 'Final Diagnosis', $diagnosis);
        $diagnosisRemark = $this->firstRowByIpd('ipd_discharge_diagnosis_remark', $ipdId);
        $diagnosisRemarkText = trim((string) ($diagnosisRemark['comp_remark'] ?? ''));
        if ($diagnosisRemarkText !== '') {
            $sections[] = '<div>' . nl2br(esc($diagnosisRemarkText)) . '</div>';
        }

        $inhosRow = $this->firstRowByIpd('ipd_discharge_investigtions_inhos', $ipdId);
        $inhosRemark = trim((string) ($inhosRow['comp_remark'] ?? ''));
        if ($inhosRemark !== '') {
            $sections[] = '<h4 style="margin:16px 0 8px 0;">Summary of key investigations during Hospitalization</h4><div>'
                . nl2br(esc($inhosRemark))
                . '</div>';
        }

        $course = $this->byIpdRows('ipd_discharge_course', ['comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $this->addListSection($sections, 'Course in the Hospital', $course);
        $courseRemark = $this->firstRowByIpd('ipd_discharge_course_remark', $ipdId);
        $courseRemarkText = trim((string) ($courseRemark['comp_remark'] ?? ''));
        if ($courseRemarkText !== '') {
            $sections[] = '<div>' . nl2br(esc($courseRemarkText)) . '</div>';
        }

        $dischargeExamRows = $this->getMappedColRows('ipd_discharge_general_exam_col', 'ipd_discharge_1_b_final', $ipdId, 'Discharge Condition', null);
        if (! empty($dischargeExamRows)) {
            $html = '<h4 style="margin:16px 0 8px 0;">Examination on Discharge</h4>'
                . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">';

            $col = 0;
            $perRow = 4;
            foreach ($dischargeExamRows as $row) {
                if ($col % $perRow === 0) {
                    $html .= '<tr>';
                }

                $label = (string) ($row['label'] ?? '');
                $value = trim((string) ($row['value'] ?? ''));
                $html .= '<td><b>' . esc($label) . ':</b> ' . esc($value) . '</td>';

                if ($col % $perRow === $perRow - 1) {
                    $html .= '</tr>';
                }
                $col++;
            }

            while ($col % $perRow !== 0) {
                $html .= '<td>&nbsp;</td>';
                if ($col % $perRow === $perRow - 1) {
                    $html .= '</tr>';
                }
                $col++;
            }

            $html .= '</table>';
            $sections[] = $html;
        }

        $surgeryRows = $this->byIpdRows('ipd_discharge_surgery', ['surgery_name', 'surgery_date'], 'id ASC', $ipdId);
        if (! empty($surgeryRows)) {
            $html = '<h4 style="margin:16px 0 8px 0;">Surgery</h4><ul style="margin:0 0 10px 20px;">';
            foreach ($surgeryRows as $row) {
                $name = trim((string) ($row['surgery_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $dateText = $this->safeDate((string) ($row['surgery_date'] ?? ''));
                $html .= '<li>' . esc($name);
                if ($dateText !== '') {
                    $html .= ' <span style="color:#475569;">(' . esc($dateText) . ')</span>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
            $sections[] = $html;
        }

        $procedureRows = $this->byIpdRows('ipd_discharge_procedure', ['procedure_name', 'procedure_date'], 'id ASC', $ipdId);
        if (! empty($procedureRows)) {
            $html = '<h4 style="margin:16px 0 8px 0;">Procedure</h4><ul style="margin:0 0 10px 20px;">';
            foreach ($procedureRows as $row) {
                $name = trim((string) ($row['procedure_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $dateText = $this->safeDate((string) ($row['procedure_date'] ?? ''));
                $html .= '<li>' . esc($name);
                if ($dateText !== '') {
                    $html .= ' <span style="color:#475569;">(' . esc($dateText) . ')</span>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
            $sections[] = $html;
        }

        $drugRows = $this->byIpdRows('ipd_discharge_drug', ['drug_name', 'drug_dose', 'drug_day'], 'id ASC', $ipdId);
        if (! empty($drugRows)) {
            $html = '<h4 style="margin:16px 0 8px 0;">Discharge Medications</h4><ol style="margin:0 0 10px 20px;">';
            foreach ($drugRows as $row) {
                $drugName = trim((string) ($row['drug_name'] ?? ''));
                if ($drugName === '') {
                    continue;
                }

                $line = esc($drugName);
                $dose = trim((string) ($row['drug_dose'] ?? ''));
                $days = trim((string) ($row['drug_day'] ?? ''));

                if ($dose !== '') {
                    $line .= ' <span style="color:#475569;">' . esc($dose) . '</span>';
                }
                if ($days !== '') {
                    $line .= ' <span style="color:#475569;">[' . esc($days) . ' days]</span>';
                }

                $html .= '<li>' . $line . '</li>';
            }
            $html .= '</ol>';
            $sections[] = $html;
        } else {
            $medRows = $this->byIpdRows('ipd_discharge_prescrption_prescribed', ['med_name', 'med_type', 'qty', 'no_of_days', 'remark'], 'id ASC', $ipdId);
            if (! empty($medRows)) {
                $html = '<h4 style="margin:16px 0 8px 0;">Discharge Medications</h4>'
                    . '<table style="width:100%;border-collapse:collapse;margin-bottom:10px;" border="1" cellpadding="6">'
                    . '<tr><th style="width:40px;">#</th><th>Medicine</th><th style="width:90px;">Qty</th><th style="width:100px;">Days</th><th>Notes</th></tr>';
                $sr = 1;
                foreach ($medRows as $row) {
                    $medName = trim((string) ($row['med_name'] ?? ''));
                    if ($medName === '') {
                        continue;
                    }
                    $medType = trim((string) ($row['med_type'] ?? ''));
                    $label = trim($medType . ' ' . $medName);
                    $html .= '<tr>'
                        . '<td>' . $sr . '</td>'
                        . '<td>' . esc($label) . '</td>'
                        . '<td>' . esc((string) ($row['qty'] ?? '')) . '</td>'
                        . '<td>' . esc((string) ($row['no_of_days'] ?? '')) . '</td>'
                        . '<td>' . esc((string) ($row['remark'] ?? '')) . '</td>'
                        . '</tr>';
                    $sr++;
                }
                $html .= '</table>';
                $sections[] = $html;
            }
        }

        $instructions = $this->byIpdRows('ipd_discharge_instructions', ['comp_remark', 'review_after', 'footer_text'], 'id DESC', $ipdId);
        if (! empty($instructions)) {
            $first = $instructions[0];
            $html = '<h4 style="margin:16px 0 8px 0;">Discharge Advice/Instructions/Summary</h4>';

            $remark = trim((string) ($first['comp_remark'] ?? ''));
            if ($remark !== '') {
                $html .= '<div>' . nl2br(esc($remark)) . '</div>';
            }

            $reviewAfter = trim((string) ($first['review_after'] ?? ''));
            if ($reviewAfter !== '') {
                $html .= '<div style="margin-top:6px;">Review after ' . esc($reviewAfter) . ' days / as and when required.</div>';
            }

            $footerText = trim((string) ($first['footer_text'] ?? ''));
            if ($footerText !== '') {
                $html .= '<div style="margin-top:6px;">' . nl2br(esc($footerText)) . '</div>';
            }

            $sections[] = $html;
        }

        $sections[] = '<table style="width:100%;border-collapse:collapse;margin-top:24px;" border="1" cellpadding="10">'
            . '<tr>'
            . '<td style="width:33%;vertical-align:bottom;">____________________<br>Signature of Consultant</td>'
            . '<td style="width:33%;vertical-align:bottom;">____________________<br>Signature of Medical Officer</td>'
            . '<td style="width:34%;vertical-align:bottom;">____________________<br>Signature of Receiver / Date</td>'
            . '</tr>'
            . '</table>';

        return trim(implode("\n", $sections));
    }

    private function firstRowByIpd(string $table, int $ipdId): array
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns($table, ['ipd_id'])) {
            return [];
        }

        $row = $this->db->table($table)
            ->where('ipd_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return is_array($row) ? $row : [];
    }

    private function upsertByIpd(string $table, int $ipdId, array $data): bool
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns($table, ['ipd_id'])) {
            return false;
        }

        try {
            $builder = $this->db->table($table);
            $existing = $this->firstRowByIpd($table, $ipdId);

            if (! empty($existing['id'])) {
                return (bool) $builder->where('id', (int) $existing['id'])->update($data);
            }

            $insert = array_merge(['ipd_id' => $ipdId], $data);

            return (bool) $builder->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Discharge upsert failed in {table} for IPD {ipd}: {msg}', [
                'table' => $table,
                'ipd' => $ipdId,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function upsertByIpdField(string $table, string $ipdField, int $ipdId, array $data): bool
    {
        if ($ipdId <= 0 || ! $this->tableHasColumns($table, [$ipdField])) {
            return false;
        }

        try {
            $builder = $this->db->table($table);
            $existing = $builder
                ->where($ipdField, $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($existing['id'])) {
                return (bool) $builder->where('id', (int) $existing['id'])->update($data);
            }

            $insert = array_merge([$ipdField => $ipdId], $data);

            return (bool) $builder->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Discharge upsertByIpdField failed in {table}: {msg}', [
                'table' => $table,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function toDbDate(string $input): ?string
    {
        $value = trim($input);
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    private function upsertByComposite(
        string $table,
        string $ipdField,
        int $ipdId,
        string $keyField,
        int $keyValue,
        array $data
    ): bool {
        if ($ipdId <= 0 || $keyValue <= 0 || ! $this->tableHasColumns($table, [$ipdField, $keyField])) {
            return false;
        }

        try {
            $builder = $this->db->table($table);
            $existing = $builder
                ->where($ipdField, $ipdId)
                ->where($keyField, $keyValue)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($existing['id'])) {
                return (bool) $builder->where('id', (int) $existing['id'])->update($data);
            }

            $insert = array_merge($data, [
                $ipdField => $ipdId,
                $keyField => $keyValue,
            ]);

            return (bool) $builder->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Discharge composite upsert failed in {table}: {msg}', [
                'table' => $table,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function getPhysicalExamRows(int $ipdId): array
    {
        $group1 = [];
        $group2 = [];
        $sysRows = [];

        if ($this->tableHasColumns('ipd_discharge_general_exam_col', ['id', 'col_description'])) {
            $generalCols = $this->db->table('ipd_discharge_general_exam_col')
                ->select('id,col_description,col_name,col_pre_value,cat_group')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $rdataMap = [];
            if ($this->tableHasColumns('ipd_discharge_1_b', ['ipd_d_id', 'col_id', 'rdata'])) {
                $rRows = $this->db->table('ipd_discharge_1_b')
                    ->select('col_id,rdata')
                    ->where('ipd_d_id', $ipdId)
                    ->get()
                    ->getResultArray();
                foreach ($rRows as $r) {
                    $rdataMap[(int) ($r['col_id'] ?? 0)] = (string) ($r['rdata'] ?? '');
                }
            }

            foreach ($generalCols as $row) {
                $colId = (int) ($row['id'] ?? 0);
                if ($colId <= 0) {
                    continue;
                }
                $item = [
                    'id' => $colId,
                    'label' => (string) ($row['col_description'] ?? $row['col_name'] ?? ('Exam ' . $colId)),
                    'value' => (string) ($rdataMap[$colId] ?? (string) ($row['col_pre_value'] ?? '')),
                ];

                if ((int) ($row['cat_group'] ?? 1) === 2) {
                    $group2[] = $item;
                } else {
                    $group1[] = $item;
                }
            }
        }

        if ($this->tableHasColumns('ipd_discharge_sys_exam', ['id', 'sys_exam_name'])) {
            $sysMaster = $this->db->table('ipd_discharge_sys_exam')
                ->select('id,sys_exam_name')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $sysValueMap = [];
            if ($this->tableHasColumns('ipd_discharge_1_a', ['ipd_d_id', 'head_id', 'rdata'])) {
                $sysSaved = $this->db->table('ipd_discharge_1_a')
                    ->select('head_id,rdata')
                    ->where('ipd_d_id', $ipdId)
                    ->get()
                    ->getResultArray();
                foreach ($sysSaved as $row) {
                    $sysValueMap[(int) ($row['head_id'] ?? 0)] = (string) ($row['rdata'] ?? '');
                }
            }

            foreach ($sysMaster as $row) {
                $sid = (int) ($row['id'] ?? 0);
                if ($sid <= 0) {
                    continue;
                }
                $sysRows[] = [
                    'id' => $sid,
                    'name' => (string) ($row['sys_exam_name'] ?? ('Systemic Exam ' . $sid)),
                    'value' => (string) ($sysValueMap[$sid] ?? ''),
                ];
            }
        }

        return [
            'general_group_1' => $group1,
            'general_group_2' => $group2,
            'systemic' => $sysRows,
        ];
    }

    private function getMappedColRows(
        string $masterTable,
        string $valueTable,
        int $ipdId,
        string $prefix,
        ?int $catGroup = null
    ): array {
        if (! $this->tableHasColumns($masterTable, ['id', 'col_description'])) {
            return [];
        }

        $builder = $this->db->table($masterTable)
            ->select('id,col_name,col_description,col_pre_value');

        if ($catGroup !== null && $this->db->fieldExists('cat_group', $masterTable)) {
            $builder->where('cat_group', $catGroup);
        }

        $masterRows = $builder->orderBy('id', 'ASC')->get()->getResultArray();

        $valueMap = [];
        if ($this->tableHasColumns($valueTable, ['ipd_d_id', 'col_id', 'rdata'])) {
            $savedRows = $this->db->table($valueTable)
                ->select('col_id,rdata')
                ->where('ipd_d_id', $ipdId)
                ->get()
                ->getResultArray();

            foreach ($savedRows as $row) {
                $valueMap[(int) ($row['col_id'] ?? 0)] = (string) ($row['rdata'] ?? '');
            }
        }

        $rows = [];
        foreach ($masterRows as $row) {
            $colId = (int) ($row['id'] ?? 0);
            if ($colId <= 0) {
                continue;
            }

            $rows[] = [
                'id' => $colId,
                'name' => (string) ($row['col_name'] ?? ''),
                'label' => (string) ($row['col_description'] ?? $row['col_name'] ?? ($prefix . ' ' . $colId)),
                'value' => (string) ($valueMap[$colId] ?? (string) ($row['col_pre_value'] ?? '')),
            ];
        }

        return $rows;
    }

    private function normalizeDateValue(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $raw = trim($raw, "'\"");
        if ($raw === '') {
            return null;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    private function normalizeCsvDateList(string $raw): array
    {
        $out = [];
        foreach (explode(',', $raw) as $part) {
            $dt = $this->normalizeDateValue($part);
            if ($dt !== null) {
                $out[$dt] = $dt;
            }
        }

        return array_values($out);
    }

    private function normalizeCsvIdList(string $raw): array
    {
        $out = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim((string) $part);
            if ($id > 0) {
                $out[$id] = $id;
            }
        }

        return array_values($out);
    }

    private function parseClinicalOtherExamPayload(string $raw): array
    {
        $raw = (string) $raw;
        $metaIds = [];

        if (preg_match('/\[\[CLINICAL_META\]\](.*?)\[\[\/CLINICAL_META\]\]\s*$/s', $raw, $m)) {
            $json = trim((string) ($m[1] ?? ''));
            $decoded = json_decode($json, true);
            if (is_array($decoded) && isset($decoded['non_path_ids']) && is_array($decoded['non_path_ids'])) {
                foreach ($decoded['non_path_ids'] as $id) {
                    $id = (int) $id;
                    if ($id > 0) {
                        $metaIds[$id] = $id;
                    }
                }
            }

            $raw = preg_replace('/\s*\[\[CLINICAL_META\]\].*?\[\[\/CLINICAL_META\]\]\s*$/s', '', $raw) ?? $raw;
        }

        return [
            'text' => trim($raw),
            'non_path_ids' => array_values($metaIds),
        ];
    }

    private function buildClinicalOtherExamPayload(string $text, array $nonPathIds): string
    {
        $text = trim($text);
        $normIds = [];
        foreach ($nonPathIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normIds[$id] = $id;
            }
        }

        if (empty($normIds)) {
            return $text;
        }

        $metaJson = json_encode([
            'non_path_ids' => array_values($normIds),
        ], JSON_UNESCAPED_SLASHES);

        if (! is_string($metaJson) || $metaJson === '') {
            return $text;
        }

        return trim($text . "\n\n[[CLINICAL_META]]" . $metaJson . '[[/CLINICAL_META]]');
    }

    private function plainTextFromHtml(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        // Preserve basic line breaks from common block/line tags before stripping HTML.
        $raw = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $raw) ?? $raw;
        $raw = preg_replace('/<\s*\/\s*(p|div|li|h[1-6]|tr)\s*>/i', "\n", $raw) ?? $raw;

        $text = strip_tags($raw);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{2,}/', "\n", $text) ?? $text;

        return trim($text);
    }

    private function getSavedClinicalLabDates(int $ipdId): array
    {
        if (! $this->tableHasColumns('ipd_discharge_2', ['ipd_d_id', 'lab_investigation_list'])) {
            return [];
        }

        $row = $this->db->table('ipd_discharge_2')
            ->select('lab_investigation_list')
            ->where('ipd_d_id', $ipdId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $raw = (string) ($row['lab_investigation_list'] ?? '');

        return $this->normalizeCsvDateList($raw);
    }

    private function getClinicalInvestigationLabRows(int $patientId, string $admitDate, string $dischargeDate, array $selectedDates): array
    {
        if ($patientId <= 0) {
            return [];
        }

        if (! $this->tableHasColumns('invoice_master', ['id', 'attach_id', 'inv_date'])
            || ! $this->tableHasColumns('lab_request', ['id', 'charge_id'])
            || ! $this->tableHasColumns('lab_request_item', ['lab_request_id', 'lab_test_id'])
            || ! $this->tableHasColumns('lab_tests', ['mstTestKey', 'Test', 'TestID'])
            || ! $this->tableHasColumns('ipd_discharge_investigation_template', ['test_code'])) {
            return [];
        }

        $builder = $this->db->table('invoice_master m')
            ->select('DATE(m.inv_date) AS inv_date', false)
            ->select("DATE_FORMAT(m.inv_date,'%d-%m-%Y') AS inv_date_label", false)
            ->select('GROUP_CONCAT(DISTINCT t.Test ORDER BY t.Test SEPARATOR ",") AS test_list', false)
            ->join('lab_request l', 'm.id = l.charge_id', 'inner')
            ->join('lab_request_item i', 'l.id = i.lab_request_id', 'inner')
            ->join('lab_tests t', 'i.lab_test_id = t.mstTestKey', 'inner')
            ->join('ipd_discharge_investigation_template d', 't.TestID = d.test_code', 'inner')
            ->where('m.attach_id', $patientId)
            ->groupBy('DATE(m.inv_date)', false)
            ->orderBy('DATE(m.inv_date)', 'ASC', false);

        if ($admitDate !== '' && $dischargeDate !== '') {
            $builder->where('DATE(m.inv_date) >=', $admitDate)
                ->where('DATE(m.inv_date) <=', $dischargeDate);
        }

        $rows = $builder->get()->getResultArray();

        $selectedMap = [];
        foreach ($selectedDates as $dt) {
            $selectedMap[$dt] = true;
        }

        foreach ($rows as &$row) {
            $dateValue = (string) ($row['inv_date'] ?? '');
            $row['inv_date'] = $dateValue;
            $row['inv_date_label'] = (string) ($row['inv_date_label'] ?? $dateValue);
            $row['test_list'] = (string) ($row['test_list'] ?? '');
            $row['checked'] = isset($selectedMap[$dateValue]);
        }
        unset($row);

        return $rows;
    }

    private function getClinicalPathologyMatrixRows(int $patientId, array $selectedDates): array
    {
        if ($patientId <= 0 || empty($selectedDates)) {
            return [
                'dates' => [],
                'date_labels' => [],
                'rows' => [],
            ];
        }

        if (! $this->tableHasColumns('invoice_master', ['id', 'attach_id', 'inv_date'])
            || ! $this->tableHasColumns('lab_request', ['id', 'charge_id'])
            || ! $this->tableHasColumns('lab_request_item', ['lab_request_id', 'lab_test_id', 'lab_test_value'])
            || ! $this->tableHasColumns('lab_tests', ['mstTestKey', 'Test', 'TestID'])
            || ! $this->tableHasColumns('ipd_discharge_investigation_template', ['test_code'])) {
            return [
                'dates' => [],
                'date_labels' => [],
                'rows' => [],
            ];
        }

        $dateMap = [];
        foreach ($selectedDates as $dt) {
            $norm = $this->normalizeDateValue((string) $dt);
            if ($norm !== null) {
                $dateMap[$norm] = $norm;
            }
        }

        $dates = array_values($dateMap);
        sort($dates);
        if (empty($dates)) {
            return [
                'dates' => [],
                'date_labels' => [],
                'rows' => [],
            ];
        }

        $builder = $this->db->table('invoice_master m')
            ->select('DATE(m.inv_date) AS inv_date', false)
            ->select('t.TestID AS test_id, t.Test AS test_name, t.mstTestKey AS test_sort', false)
            ->select('i.lab_test_value', false)
            ->join('lab_request l', 'm.id = l.charge_id', 'inner')
            ->join('lab_request_item i', 'l.id = i.lab_request_id', 'inner')
            ->join('lab_tests t', 'i.lab_test_id = t.mstTestKey', 'inner')
            ->join('ipd_discharge_investigation_template d', 't.TestID = d.test_code', 'inner')
            ->where('m.attach_id', $patientId)
            ->orderBy('DATE(m.inv_date)', 'ASC', false)
            ->orderBy('t.mstTestKey', 'ASC');

        if ($this->db->fieldExists('FixedNormals', 'lab_tests')) {
            $builder->select('t.FixedNormals AS fixed_normals', false);
        } else {
            $builder->select("'' AS fixed_normals", false);
        }

        if ($this->tableHasColumns('ipd_discharge_investigation_template', ['group_test'])
            && $this->tableHasColumns('lab_rgroups', ['mstRGrpKey', 'sort_order'])) {
            $builder->join('lab_rgroups g', 'd.group_test = g.mstRGrpKey', 'left')
                ->select('COALESCE(g.sort_order, 9999) AS grp_sort', false)
                ->orderBy('grp_sort', 'ASC', false);
        } else {
            $builder->select('9999 AS grp_sort', false);
        }

        $escapedDates = [];
        foreach ($dates as $dt) {
            $escapedDates[] = $this->db->escape($dt);
        }
        $builder->where('DATE(m.inv_date) IN (' . implode(',', $escapedDates) . ')', null, false);

        $rawRows = $builder->get()->getResultArray();

        $rowsByTest = [];
        $orderByTest = [];
        foreach ($rawRows as $row) {
            $testId = trim((string) ($row['test_id'] ?? ''));
            if ($testId === '') {
                continue;
            }

            $date = (string) ($row['inv_date'] ?? '');
            if ($date === '') {
                continue;
            }

            if (! isset($rowsByTest[$testId])) {
                $rowsByTest[$testId] = [
                    'test_name' => (string) ($row['test_name'] ?? ''),
                    'fixed_normals' => (string) ($row['fixed_normals'] ?? ''),
                    'values' => [],
                ];

                $orderByTest[$testId] = [
                    'grp_sort' => (int) ($row['grp_sort'] ?? 9999),
                    'test_sort' => (int) ($row['test_sort'] ?? 0),
                    'test_name' => (string) ($row['test_name'] ?? ''),
                ];
            }

            $value = trim((string) ($row['lab_test_value'] ?? ''));
            if (! isset($rowsByTest[$testId]['values'][$date])) {
                $rowsByTest[$testId]['values'][$date] = [];
            }
            if ($value !== '' && ! in_array($value, $rowsByTest[$testId]['values'][$date], true)) {
                $rowsByTest[$testId]['values'][$date][] = $value;
            }
        }

        $orderedTestIds = array_keys($rowsByTest);
        usort($orderedTestIds, function ($a, $b) use ($orderByTest) {
            $oa = $orderByTest[$a] ?? ['grp_sort' => 9999, 'test_sort' => 0, 'test_name' => ''];
            $ob = $orderByTest[$b] ?? ['grp_sort' => 9999, 'test_sort' => 0, 'test_name' => ''];

            if ((int) $oa['grp_sort'] !== (int) $ob['grp_sort']) {
                return (int) $oa['grp_sort'] <=> (int) $ob['grp_sort'];
            }
            if ((int) $oa['test_sort'] !== (int) $ob['test_sort']) {
                return (int) $oa['test_sort'] <=> (int) $ob['test_sort'];
            }

            return strcmp((string) $oa['test_name'], (string) $ob['test_name']);
        });

        $matrixRows = [];
        foreach ($orderedTestIds as $testId) {
            $testRow = $rowsByTest[$testId];
            $values = [];
            foreach ($dates as $dt) {
                $vals = $testRow['values'][$dt] ?? [];
                $values[$dt] = empty($vals) ? '' : implode(', ', $vals);
            }

            $matrixRows[] = [
                'test_id' => $testId,
                'test_name' => (string) ($testRow['test_name'] ?? ''),
                'fixed_normals' => (string) ($testRow['fixed_normals'] ?? ''),
                'values' => $values,
            ];
        }

        $dateLabels = [];
        foreach ($dates as $dt) {
            $ts = strtotime($dt);
            $dateLabels[$dt] = $ts === false ? $dt : date('d-m-Y', $ts);
        }

        return [
            'dates' => $dates,
            'date_labels' => $dateLabels,
            'rows' => $matrixRows,
        ];
    }

    private function resolveClinicalNonPathModality(int $labType, string $reportName, string $impression): ?string
    {
        $byType = [
            1 => 'Sonography',
            2 => 'MRI',
            3 => 'X-Ray',
            4 => 'CT-Scan',
            6 => 'ECG',
        ];

        if (isset($byType[$labType])) {
            return $byType[$labType];
        }

        $haystack = strtolower($reportName . ' ' . $impression);
        if (strpos($haystack, 'x-ray') !== false || strpos($haystack, 'xray') !== false || strpos($haystack, 'radiology') !== false) {
            return 'X-Ray';
        }
        if (strpos($haystack, 'ecg') !== false || strpos($haystack, 'ekg') !== false || strpos($haystack, 'echo') !== false) {
            return 'ECG';
        }
        if (strpos($haystack, 'sonography') !== false || strpos($haystack, 'ultrasound') !== false || strpos($haystack, 'usg') !== false) {
            return 'Sonography';
        }
        if (strpos($haystack, 'ct') !== false || strpos($haystack, 'ct scan') !== false || strpos($haystack, 'ct-scan') !== false) {
            return 'CT-Scan';
        }
        if (strpos($haystack, 'mri') !== false) {
            return 'MRI';
        }

        return null;
    }

    private function getClinicalNonPathReportRows(int $patientId, string $admitDate, string $dischargeDate, array $selectedIds): array
    {
        if ($patientId <= 0) {
            return [];
        }

        if (! $this->tableHasColumns('invoice_master', ['id', 'attach_id', 'inv_date'])
            || ! $this->tableHasColumns('lab_request', ['id', 'charge_id', 'lab_type', 'Request_Date', 'report_name', 'report_data_Impression'])) {
            return [];
        }

        $rows = $this->db->table('invoice_master m')
            ->select('l.id AS lab_request_id, l.lab_type, l.report_name, l.report_data_Impression, l.Request_Date, m.inv_date', false)
            ->join('lab_request l', 'm.id = l.charge_id', 'inner')
            ->where('m.attach_id', $patientId)
            ->where('l.report_data_Impression IS NOT NULL', null, false)
            ->where('TRIM(l.report_data_Impression) <>', '')
            ->orderBy('m.inv_date', 'ASC')
            ->orderBy('l.id', 'ASC')
            ->get()
            ->getResultArray();

        $selectedMap = [];
        foreach ($selectedIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $selectedMap[$id] = true;
            }
        }

        $result = [];
        $seen = [];

        foreach ($rows as $row) {
            $requestId = (int) ($row['lab_request_id'] ?? 0);
            if ($requestId <= 0 || isset($seen[$requestId])) {
                continue;
            }

            $impression = $this->plainTextFromHtml((string) ($row['report_data_Impression'] ?? ''));
            if ($impression === '') {
                continue;
            }

            $requestDate = $this->normalizeDateValue((string) ($row['Request_Date'] ?? ''))
                ?? $this->normalizeDateValue((string) ($row['inv_date'] ?? ''));

            if ($requestDate === null) {
                continue;
            }

            if ($admitDate !== '' && strcmp($requestDate, $admitDate) < 0) {
                continue;
            }
            if ($dischargeDate !== '' && strcmp($requestDate, $dischargeDate) > 0) {
                continue;
            }

            $reportName = $this->plainTextFromHtml((string) ($row['report_name'] ?? ''));
            $modality = $this->resolveClinicalNonPathModality((int) ($row['lab_type'] ?? 0), $reportName, $impression);
            if ($modality === null) {
                continue;
            }

            if ($reportName === '') {
                $reportName = $modality . ' Report';
            }

            $result[] = [
                'lab_request_id' => $requestId,
                'report_date' => $requestDate,
                'report_date_label' => date('d-m-Y', strtotime($requestDate)),
                'modality' => $modality,
                'report_name' => $reportName,
                'impression' => $impression,
                'checked' => isset($selectedMap[$requestId]),
            ];
            $seen[$requestId] = true;
        }

        return $result;
    }

    private function parseInputDateToDb(string $rawDate): ?string
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $rawDate);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($rawDate);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    public function ipd_select(int $ipdId, int $reCreate = 0)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if ($ipdId <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Invalid IPD id');
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $ipdMasterRow = $this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [];
        $patientId = (int) ($ipdMasterRow['p_id'] ?? ($panelData['person_info']->id ?? 0));

        $notice = '';
        $noticeType = 'success';
        $userLabel = substr($this->currentUserLabel(), 0, 50);

        if (strtolower($this->request->getMethod()) === 'post') {
            $action = (string) ($this->request->getPost('action') ?? 'save_main');
            $savedAny = false;

            if ($action === 'add_complaint') {
                $complaintName = trim((string) ($this->request->getPost('new_complaint_name') ?? ''));
                $complaintRemarkRow = trim((string) ($this->request->getPost('new_complaint_remark') ?? ''));

                if ($complaintName !== '' && $this->tableHasColumns('ipd_discharge_complaint', ['ipd_id'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'comp_code' => 0,
                        'comp_report' => $complaintName,
                        'comp_remark' => $complaintRemarkRow,
                        'update_by' => $userLabel,
                    ];

                    if ($this->db->fieldExists('order_id', 'ipd_discharge_complaint')) {
                        $insert['order_id'] = 0;
                    }

                    $savedAny = (bool) $this->db->table('ipd_discharge_complaint')->insert($insert);
                    $notice = $savedAny ? 'Complaint row added.' : 'Unable to add complaint row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Enter complaint name before adding.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_complaint') {
                $removeId = (int) ($this->request->getPost('complaint_remove_id') ?? 0);
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_complaint', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_complaint')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Complaint row removed.' : 'Unable to remove complaint row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                }
            } elseif ($action === 'add_surgery') {
                $name = trim((string) ($this->request->getPost('new_surgery_name') ?? ''));
                $date = $this->parseInputDateToDb((string) ($this->request->getPost('new_surgery_date') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_surgery_remark') ?? ''));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_surgery', ['ipd_id', 'surgery_name'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'surgery_name' => $name,
                        'surgery_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('surgery_date', 'ipd_discharge_surgery')) {
                        $insert['surgery_date'] = $date;
                    }
                    if ($this->db->fieldExists('surgery_id', 'ipd_discharge_surgery')) {
                        $insert['surgery_id'] = 0;
                    }
                    if ($this->db->fieldExists('surgery_by_doc_id', 'ipd_discharge_surgery')) {
                        $insert['surgery_by_doc_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_surgery')->insert($insert);
                    $notice = $savedAny ? 'Surgery row added.' : 'Unable to add surgery row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = $name === ''
                        ? 'Enter surgery name before adding.'
                        : 'Surgery table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_surgery') {
                $removeId = (int) ($this->request->getPost('surgery_remove_id') ?? 0);
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_surgery', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_surgery')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Surgery row removed.' : 'Unable to remove surgery row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Select a valid surgery row to remove.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'add_procedure') {
                $name = trim((string) ($this->request->getPost('new_procedure_name') ?? ''));
                $date = $this->parseInputDateToDb((string) ($this->request->getPost('new_procedure_date') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_procedure_remark') ?? ''));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_procedure', ['ipd_id', 'procedure_name'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'procedure_name' => $name,
                        'procedure_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('procedure_date', 'ipd_discharge_procedure')) {
                        $insert['procedure_date'] = $date;
                    }
                    if ($this->db->fieldExists('procedure_id', 'ipd_discharge_procedure')) {
                        $insert['procedure_id'] = 0;
                    }
                    if ($this->db->fieldExists('procedure_by_doc_id', 'ipd_discharge_procedure')) {
                        $insert['procedure_by_doc_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_procedure')->insert($insert);
                    $notice = $savedAny ? 'Procedure row added.' : 'Unable to add procedure row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = $name === ''
                        ? 'Enter procedure name before adding.'
                        : 'Procedure table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_procedure') {
                $removeId = (int) ($this->request->getPost('procedure_remove_id') ?? 0);
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_procedure', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_procedure')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Procedure row removed.' : 'Unable to remove procedure row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Select a valid procedure row to remove.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'add_diagnosis') {
                $name = trim((string) ($this->request->getPost('new_diagnosis_name') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_diagnosis_remark') ?? ''));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_diagnosis', ['ipd_id', 'comp_report'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'comp_code' => 0,
                        'comp_report' => $name,
                        'comp_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('order_id', 'ipd_discharge_diagnosis')) {
                        $insert['order_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_diagnosis')->insert($insert);
                    $notice = $savedAny ? 'Diagnosis row added.' : 'Unable to add diagnosis row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = $name === ''
                        ? 'Enter diagnosis before adding.'
                        : 'Diagnosis table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_diagnosis') {
                $removeId = (int) ($this->request->getPost('diagnosis_remove_id') ?? 0);
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_diagnosis', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_diagnosis')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Diagnosis row removed.' : 'Unable to remove diagnosis row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Select a valid diagnosis row to remove.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'add_course') {
                $name = trim((string) ($this->request->getPost('new_course_name') ?? ''));
                $remark = trim((string) ($this->request->getPost('new_course_remark') ?? ''));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_course', ['ipd_id', 'comp_report'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'comp_code' => 0,
                        'comp_report' => $name,
                        'comp_remark' => $remark,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('order_id', 'ipd_discharge_course')) {
                        $insert['order_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_course')->insert($insert);
                    $notice = $savedAny ? 'Course row added.' : 'Unable to add course row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = $name === ''
                        ? 'Enter course/treatment text before adding.'
                        : 'Course table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_course') {
                $removeId = (int) ($this->request->getPost('course_remove_id') ?? 0);
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_course', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_course')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Course row removed.' : 'Unable to remove course row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Select a valid course row to remove.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'add_drug') {
                $name = trim((string) ($this->request->getPost('new_drug_name') ?? ''));
                $dose = trim((string) ($this->request->getPost('new_drug_dose') ?? ''));
                $day = trim((string) ($this->request->getPost('new_drug_day') ?? ''));
                if ($name !== '' && $this->tableHasColumns('ipd_discharge_drug', ['ipd_id', 'drug_name'])) {
                    $insert = [
                        'ipd_id' => $ipdId,
                        'drug_code' => 0,
                        'drug_name' => $name,
                        'drug_dose' => $dose,
                        'drug_day' => $day,
                        'update_by' => $userLabel,
                    ];
                    if ($this->db->fieldExists('order_id', 'ipd_discharge_drug')) {
                        $insert['order_id'] = 0;
                    }
                    $savedAny = (bool) $this->db->table('ipd_discharge_drug')->insert($insert);
                    $notice = $savedAny ? 'Drug row added.' : 'Unable to add drug row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = $name === ''
                        ? 'Enter drug name before adding.'
                        : 'Drug table/columns are missing in database.';
                    $noticeType = 'warning';
                }
            } elseif ($action === 'remove_drug') {
                $removeId = (int) ($this->request->getPost('drug_remove_id') ?? 0);
                if ($removeId > 0 && $this->tableHasColumns('ipd_discharge_drug', ['id', 'ipd_id'])) {
                    $savedAny = (bool) $this->db->table('ipd_discharge_drug')
                        ->where('id', $removeId)
                        ->where('ipd_id', $ipdId)
                        ->delete();
                    $notice = $savedAny ? 'Drug row removed.' : 'Unable to remove drug row.';
                    $noticeType = $savedAny ? 'success' : 'warning';
                } else {
                    $notice = 'Select a valid drug row to remove.';
                    $noticeType = 'warning';
                }
            } else {

                if ($this->db->tableExists('ipd_master')) {
                    $masterUpdate = [];

                    if ($this->db->fieldExists('discarge_patient_status', 'ipd_master')) {
                        $masterUpdate['discarge_patient_status'] = (int) ($this->request->getPost('discarge_patient_status') ?? 0);
                    }
                    if ($this->db->fieldExists('discharge_date', 'ipd_master')) {
                        $masterUpdate['discharge_date'] = $this->toDbDate((string) ($this->request->getPost('discharge_date') ?? ''));
                    }
                    if ($this->db->fieldExists('discharge_time', 'ipd_master')) {
                        $masterUpdate['discharge_time'] = (string) ($this->request->getPost('discharge_time') ?? '');
                    }
                    if ($this->db->fieldExists('dept_id', 'ipd_master')) {
                        $masterUpdate['dept_id'] = (int) ($this->request->getPost('dept_id') ?? 0);
                    }

                    if (! empty($masterUpdate)) {
                        $savedAny = (bool) $this->db->table('ipd_master')->where('id', $ipdId)->update($masterUpdate) || $savedAny;
                    }
                }

                $complaintRemark = trim((string) ($this->request->getPost('complaint_remark') ?? ''));
                $diagnosisRemark = trim((string) ($this->request->getPost('diagnosis_remark') ?? ''));
                $courseRemark = trim((string) ($this->request->getPost('course_remark') ?? ''));
                $instructionRemark = trim((string) ($this->request->getPost('instruction_remark') ?? ''));
                $reviewAfter = trim((string) ($this->request->getPost('review_after') ?? ''));

                if ($complaintRemark !== '' || $this->tableHasColumns('ipd_discharge_complaint_remark', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_complaint_remark', $ipdId, [
                        'comp_report' => '',
                        'comp_remark' => $complaintRemark,
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                if ($diagnosisRemark !== '' || $this->tableHasColumns('ipd_discharge_diagnosis_remark', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_diagnosis_remark', $ipdId, [
                        'comp_report' => '',
                        'comp_remark' => $diagnosisRemark,
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                if ($courseRemark !== '' || $this->tableHasColumns('ipd_discharge_course_remark', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_course_remark', $ipdId, [
                        'comp_report' => '',
                        'comp_remark' => $courseRemark,
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                if ($instructionRemark !== '' || $reviewAfter !== '' || $this->tableHasColumns('ipd_discharge_instructions', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_instructions', $ipdId, [
                        'comp_report' => '',
                        'comp_remark' => $instructionRemark,
                        'review_after' => $reviewAfter,
                        'footer_text' => '',
                        'footer_banner' => '0',
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                // Examination on Admission (General Examination values).
                if ($this->tableHasColumns('ipd_discharge_general_exam_col', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_b', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $generalCols = $this->db->table('ipd_discharge_general_exam_col')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    foreach ($generalCols as $col) {
                        $colId = (int) ($col['id'] ?? 0);
                        if ($colId <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('gen_exam_' . $colId);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_b',
                            'ipd_d_id',
                            $ipdId,
                            'col_id',
                            $colId,
                            [
                                'short_head' => (string) ($col['col_name'] ?? ('Exam ' . $colId)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Other/Systemic Examinations.
                if ($this->tableHasColumns('ipd_discharge_sys_exam', ['id', 'sys_exam_name'])
                    && $this->tableHasColumns('ipd_discharge_1_a', ['ipd_d_id', 'head_id', 'short_head', 'rdata'])) {
                    $sysMaster = $this->db->table('ipd_discharge_sys_exam')
                        ->select('id,sys_exam_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    foreach ($sysMaster as $sys) {
                        $sid = (int) ($sys['id'] ?? 0);
                        if ($sid <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('sys_exam_' . $sid);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_a',
                            'ipd_d_id',
                            $ipdId,
                            'head_id',
                            $sid,
                            [
                                'short_head' => (string) ($sys['sys_exam_name'] ?? ('Systemic Exam ' . $sid)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Investigation done during admit (manual entry): ipd_discharge_1_d
                if ($this->tableHasColumns('ipd_discharge_investigation_during_admit', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_d', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $manualCols = $this->db->table('ipd_discharge_investigation_during_admit')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    foreach ($manualCols as $col) {
                        $colId = (int) ($col['id'] ?? 0);
                        if ($colId <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('manual_exam_' . $colId);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_d',
                            'ipd_d_id',
                            $ipdId,
                            'col_id',
                            $colId,
                            [
                                'short_head' => (string) ($col['col_name'] ?? ('Investigation ' . $colId)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Special/manual radiology investigation: ipd_discharge_1_e
                if ($this->tableHasColumns('ipd_discharge_special_investigation', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_e', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $specialCols = $this->db->table('ipd_discharge_special_investigation')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    foreach ($specialCols as $col) {
                        $colId = (int) ($col['id'] ?? 0);
                        if ($colId <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('special_exam_' . $colId);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_e',
                            'ipd_d_id',
                            $ipdId,
                            'col_id',
                            $colId,
                            [
                                'short_head' => (string) ($col['col_name'] ?? ('Special Investigation ' . $colId)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Condition at discharge values: ipd_discharge_1_b_final
                if ($this->tableHasColumns('ipd_discharge_general_exam_col', ['id', 'col_name'])
                    && $this->tableHasColumns('ipd_discharge_1_b_final', ['ipd_d_id', 'col_id', 'short_head', 'rdata'])) {
                    $disCols = $this->db->table('ipd_discharge_general_exam_col')
                        ->select('id,col_name')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    foreach ($disCols as $col) {
                        $colId = (int) ($col['id'] ?? 0);
                        if ($colId <= 0) {
                            continue;
                        }
                        $posted = $this->request->getPost('dis_exam_' . $colId);
                        if ($posted === null) {
                            continue;
                        }
                        $savedAny = $this->upsertByComposite(
                            'ipd_discharge_1_b_final',
                            'ipd_d_id',
                            $ipdId,
                            'col_id',
                            $colId,
                            [
                                'short_head' => (string) ($col['col_name'] ?? ('Discharge Exam ' . $colId)),
                                'rdata' => trim((string) $posted),
                            ]
                        ) || $savedAny;
                    }
                }

                // Summary of key investigations during hospitalization.
                $inhosRemark = trim((string) ($this->request->getPost('inhos_remark') ?? ''));
                if ($inhosRemark !== '' || $this->tableHasColumns('ipd_discharge_investigtions_inhos', ['ipd_id'])) {
                    $savedAny = $this->upsertByIpd('ipd_discharge_investigtions_inhos', $ipdId, [
                        'comp_report' => '',
                        'comp_remark' => $inhosRemark,
                        'update_by' => $userLabel,
                    ]) || $savedAny;
                }

                // Other examinations narrative (legacy ipd_discharge_2.rdata).
                $otherExamText = trim((string) ($this->request->getPost('other_exam_text') ?? ''));
                $postedClinicalDates = $this->request->getPost('lab_investigation_dates');
                $postedNonPathIds = $this->request->getPost('non_path_investigation_ids');
                $normalizedClinicalDates = [];
                $normalizedNonPathIds = [];
                if (is_array($postedClinicalDates)) {
                    foreach ($postedClinicalDates as $dt) {
                        $parsed = $this->normalizeDateValue((string) $dt);
                        if ($parsed !== null) {
                            $normalizedClinicalDates[$parsed] = $parsed;
                        }
                    }
                } else {
                    $listFromText = (string) ($this->request->getPost('lab_investigation_list') ?? '');
                    foreach ($this->normalizeCsvDateList($listFromText) as $dt) {
                        $normalizedClinicalDates[$dt] = $dt;
                    }
                }

                if (is_array($postedNonPathIds)) {
                    foreach ($postedNonPathIds as $id) {
                        $id = (int) $id;
                        if ($id > 0) {
                            $normalizedNonPathIds[$id] = $id;
                        }
                    }
                } else {
                    $nonPathFromText = (string) ($this->request->getPost('non_path_investigation_list') ?? '');
                    foreach ($this->normalizeCsvIdList($nonPathFromText) as $id) {
                        $normalizedNonPathIds[$id] = $id;
                    }
                }

                $labInvestigationList = implode(',', array_values($normalizedClinicalDates));
                $nonPathIds = array_values($normalizedNonPathIds);
                $otherExamPayload = $this->buildClinicalOtherExamPayload($otherExamText, $nonPathIds);
                if ($otherExamText !== '' || $this->tableHasColumns('ipd_discharge_2', ['ipd_d_id'])) {
                    $savedAny = $this->upsertByIpdField(
                        'ipd_discharge_2',
                        'ipd_d_id',
                        $ipdId,
                        [
                            'lab_investigation_list' => $labInvestigationList,
                            'short_head' => 'Other Examination',
                            'rdata' => $otherExamPayload,
                        ]
                    ) || $savedAny;
                }

                // Legacy first-tab personal history checkboxes are stored in patient_master.
                if ($patientId > 0 && $this->db->tableExists('patient_master')) {
                    $patientUpdate = [];
                    $historyFields = [
                        'is_smoking',
                        'is_alcohol',
                        'is_drug_abuse',
                        'is_tobacoo',
                        'is_hypertesion',
                        'is_niddm',
                        'is_hbsag',
                        'is_hcv',
                        'is_hiv_I_II',
                        'Others',
                    ];

                    foreach ($historyFields as $field) {
                        if ($this->db->fieldExists($field, 'patient_master')) {
                            $patientUpdate[$field] = $this->request->getPost($field) ? 1 : 0;
                        }
                    }

                    if (! empty($patientUpdate)) {
                        $savedAny = (bool) $this->db->table('patient_master')->where('id', $patientId)->update($patientUpdate) || $savedAny;
                    }
                }

                if ($savedAny) {
                    $notice = 'Discharge form data saved. You can now preview or regenerate summary.';
                } else {
                    $notice = 'No data could be saved. Please verify discharge tables exist in this database.';
                    $noticeType = 'warning';
                }
            }

            if ($action !== 'save_main' && $notice === '') {
                $notice = 'Requested action could not be completed. Please verify database table/columns for this section.';
                $noticeType = 'warning';
            }
        }

        if ($reCreate > 0) {
            return redirect()->to(site_url('Ipd_discharge/preview_discharge_report/' . $ipdId . '?regen=1'));
        }

        $ipdMasterRow = $this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [];
        $statusRows = $this->db->tableExists('ipd_discharg_status')
            ? $this->db->table('ipd_discharg_status')->orderBy('id', 'ASC')->get()->getResultArray()
            : [];
        $departmentRows = $this->db->tableExists('hc_department')
            ? $this->db->table('hc_department')->orderBy('vName', 'ASC')->get()->getResultArray()
            : [];

        $complaintRemarkRow = $this->firstRowByIpd('ipd_discharge_complaint_remark', $ipdId);
        $complaintRows = $this->byIpdRows('ipd_discharge_complaint', ['id', 'comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $surgeryRows = $this->byIpdRows('ipd_discharge_surgery', ['id', 'surgery_name', 'surgery_date', 'surgery_remark'], 'id ASC', $ipdId);
        $procedureRows = $this->byIpdRows('ipd_discharge_procedure', ['id', 'procedure_name', 'procedure_date', 'procedure_remark'], 'id ASC', $ipdId);
        $diagnosisRows = $this->byIpdRows('ipd_discharge_diagnosis', ['id', 'comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $courseRows = $this->byIpdRows('ipd_discharge_course', ['id', 'comp_report', 'comp_remark'], 'id ASC', $ipdId);
        $drugRows = $this->byIpdRows('ipd_discharge_drug', ['id', 'drug_name', 'drug_dose', 'drug_day'], 'id ASC', $ipdId);
        $diagnosisRemarkRow = $this->firstRowByIpd('ipd_discharge_diagnosis_remark', $ipdId);
        $courseRemarkRow = $this->firstRowByIpd('ipd_discharge_course_remark', $ipdId);
        $instructionRow = $this->firstRowByIpd('ipd_discharge_instructions', $ipdId);
        $inhosRow = $this->firstRowByIpd('ipd_discharge_investigtions_inhos', $ipdId);
        $otherExamRow = [];
        if ($this->tableHasColumns('ipd_discharge_2', ['ipd_d_id'])) {
            $otherExamRow = $this->db->table('ipd_discharge_2')
                ->where('ipd_d_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray() ?? [];
        }
        $otherExamParsed = $this->parseClinicalOtherExamPayload((string) ($otherExamRow['rdata'] ?? ''));
        $savedNonPathIds = $otherExamParsed['non_path_ids'] ?? [];
        $patientHistoryRow = $patientId > 0 && $this->db->tableExists('patient_master')
            ? ($this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [])
            : [];
        $physicalExamRows = $this->getPhysicalExamRows($ipdId);
        $manualInvestRows = $this->getMappedColRows('ipd_discharge_investigation_during_admit', 'ipd_discharge_1_d', $ipdId, 'Manual Exam', 1);
        $specialInvestRows = $this->getMappedColRows('ipd_discharge_special_investigation', 'ipd_discharge_1_e', $ipdId, 'Special Exam', 1);
        $dischargeConditionRows = $this->getMappedColRows('ipd_discharge_general_exam_col', 'ipd_discharge_1_b_final', $ipdId, 'Discharge Condition', null);

        $ipdInfo = $panelData['ipd_info'] ?? null;
        $admitDate = $this->normalizeDateValue((string) ($ipdInfo->register_date ?? '')) ?? '';
        $dischargeDate = $this->normalizeDateValue((string) ($ipdInfo->discharge_date ?? ''))
            ?? $this->normalizeDateValue((string) ($ipdMasterRow['discharge_date'] ?? ''))
            ?? date('Y-m-d');
        $savedClinicalDates = $this->getSavedClinicalLabDates($ipdId);
        $clinicalLabRows = $this->getClinicalInvestigationLabRows($patientId, $admitDate, $dischargeDate, $savedClinicalDates);
        $clinicalNonPathRows = $this->getClinicalNonPathReportRows($patientId, $admitDate, $dischargeDate, $savedNonPathIds);
        $labInvestigationList = implode(',', $savedClinicalDates);
        $nonPathInvestigationList = implode(',', $savedNonPathIds);

        return view('billing/ipd/discharge_create', [
            'ipd_id' => $ipdId,
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $panelData['person_info'] ?? null,
            'notice' => $notice,
            'notice_type' => $noticeType,
            'status_rows' => $statusRows,
            'department_rows' => $departmentRows,
            'ipd_master_row' => $ipdMasterRow,
            'complaint_rows' => $complaintRows,
            'surgery_rows' => $surgeryRows,
            'procedure_rows' => $procedureRows,
            'diagnosis_rows' => $diagnosisRows,
            'course_rows' => $courseRows,
            'drug_rows' => $drugRows,
            'patient_history_row' => $patientHistoryRow,
            'physical_exam_rows' => $physicalExamRows,
            'manual_invest_rows' => $manualInvestRows,
            'special_invest_rows' => $specialInvestRows,
            'clinical_lab_rows' => $clinicalLabRows,
            'clinical_non_path_rows' => $clinicalNonPathRows,
            'lab_investigation_list' => $labInvestigationList,
            'non_path_investigation_list' => $nonPathInvestigationList,
            'discharge_condition_rows' => $dischargeConditionRows,
            'complaint_remark' => (string) ($complaintRemarkRow['comp_remark'] ?? ''),
            'diagnosis_remark' => (string) ($diagnosisRemarkRow['comp_remark'] ?? ''),
            'course_remark' => (string) ($courseRemarkRow['comp_remark'] ?? ''),
            'instruction_remark' => (string) ($instructionRow['comp_remark'] ?? ''),
            'review_after' => (string) ($instructionRow['review_after'] ?? ''),
            'inhos_remark' => (string) ($inhosRow['comp_remark'] ?? ''),
            'other_exam_text' => (string) ($otherExamParsed['text'] ?? ''),
        ]);
    }

    public function preview_discharge_report(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if ($ipdId <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Invalid IPD id');
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $notice = '';
        $noticeType = 'success';
        $content = $this->getDischargeContent($ipdId);
        $shouldRegenerate = (int) ($this->request->getGet('regen') ?? 0) === 1;

        if (strtolower($this->request->getMethod()) === 'post') {
            $content = (string) ($this->request->getPost('editor_Discharge_Preview') ?? '');
            if ($this->saveDischargeContent($ipdId, $content)) {
                $notice = 'Discharge summary saved.';
                $noticeType = 'success';
            } else {
                $notice = 'Unable to save discharge summary in ipd_discharge table. Please check schema/permissions.';
                $noticeType = 'danger';
            }
        } elseif ($shouldRegenerate || trim(strip_tags($content)) === '') {
            $generated = $this->buildAutoDischargeContent($ipdId, $panelData);
            if (trim(strip_tags($generated)) !== '') {
                $content = $generated;
                if ($this->saveDischargeContent($ipdId, $content)) {
                    $notice = 'Discharge summary auto-generated from IPD discharge data.';
                    $noticeType = 'success';
                } else {
                    $notice = 'Discharge summary generated in editor, but database save failed. Please click Save again after checking DB schema.';
                    $noticeType = 'warning';
                }
            }
        }

        return view('billing/ipd/discharge_preview', [
            'ipd_id' => $ipdId,
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $panelData['person_info'] ?? null,
            'content' => $content,
            'notice' => $notice,
            'notice_type' => $noticeType,
        ]);
    }

    public function show_discharge(int $ipdId, int $printType = 1)
    {
        $permission = $this->requireAnyPermission([
            'billing.access',
            'billing.ipd.invoice',
            'billing.ipd.current-admission',
        ]);
        if ($permission) {
            return $permission;
        }

        if ($ipdId <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Invalid IPD id');
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $content = $this->getDischargeContent($ipdId);
        if (trim(strip_tags($content)) === '') {
            $content = $this->buildAutoDischargeContent($ipdId, $panelData);
            if (trim(strip_tags($content)) !== '') {
                $this->saveDischargeContent($ipdId, $content);
            }
        }

        return view('billing/ipd/discharge_print', [
            'ipd_id' => $ipdId,
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $panelData['person_info'] ?? null,
            'content' => $content,
            'print_type' => $printType,
            'print_mode' => 'standard',
        ]);
    }

    public function show_file3(int $ipdId)
    {
        return $this->show_discharge($ipdId, 3);
    }
}
