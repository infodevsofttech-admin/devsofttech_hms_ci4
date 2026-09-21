<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDaycareEmergencyModule extends Migration
{
    public function up()
    {
        // 1. Extend ipd_master table with Day Care, Emergency, Triage, MLC, and IPD Escalation fields
        if ($this->db->tableExists('ipd_master')) {
            $fields = [];

            if (! $this->db->fieldExists('admission_type', 'ipd_master')) {
                $fields['admission_type'] = [
                    'type'       => 'ENUM',
                    'constraint' => ['ipd', 'daycare', 'emergency'],
                    'default'    => 'ipd',
                    'null'       => false,
                    'after'      => 'ipd_code',
                ];
            }

            if (! $this->db->fieldExists('triage_level', 'ipd_master')) {
                $fields['triage_level'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => true,
                    'comment'    => 'ESI Triage 1: Red, 2: Orange, 3: Yellow, 4: Green, 5: Blue',
                    'after'      => 'admission_type',
                ];
            }

            if (! $this->db->fieldExists('triage_time', 'ipd_master')) {
                $fields['triage_time'] = [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'after'   => 'triage_level',
                ];
            }

            if (! $this->db->fieldExists('is_mlc', 'ipd_master')) {
                $fields['is_mlc'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'triage_time',
                ];
            }

            if (! $this->db->fieldExists('mlc_number', 'ipd_master')) {
                $fields['mlc_number'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 60,
                    'null'       => true,
                    'after'      => 'is_mlc',
                ];
            }

            if (! $this->db->fieldExists('police_station', 'ipd_master')) {
                $fields['police_station'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                    'after'      => 'mlc_number',
                ];
            }

            if (! $this->db->fieldExists('police_constable_details', 'ipd_master')) {
                $fields['police_constable_details'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                    'after'      => 'police_station',
                ];
            }

            if (! $this->db->fieldExists('brought_by', 'ipd_master')) {
                $fields['brought_by'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                    'after'      => 'police_constable_details',
                ];
            }

            if (! $this->db->fieldExists('daycare_procedure_name', 'ipd_master')) {
                $fields['daycare_procedure_name'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'brought_by',
                ];
            }

            if (! $this->db->fieldExists('converted_from_type', 'ipd_master')) {
                $fields['converted_from_type'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'daycare_procedure_name',
                ];
            }

            if (! $this->db->fieldExists('converted_from_code', 'ipd_master')) {
                $fields['converted_from_code'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'converted_from_type',
                ];
            }

            if (! $this->db->fieldExists('converted_at', 'ipd_master')) {
                $fields['converted_at'] = [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'after'   => 'converted_from_code',
                ];
            }

            if (! $this->db->fieldExists('conversion_reason', 'ipd_master')) {
                $fields['conversion_reason'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'converted_at',
                ];
            }

            if (! $this->db->fieldExists('converted_by', 'ipd_master')) {
                $fields['converted_by'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'conversion_reason',
                ];
            }

            if (! $this->db->fieldExists('sbar_handover', 'ipd_master')) {
                $fields['sbar_handover'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'converted_by',
                ];
            }

            if (! $this->db->fieldExists('discharge_score', 'ipd_master')) {
                $fields['discharge_score'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'comment'    => 'PADSS or Modified Aldrete Score',
                    'after'      => 'sbar_handover',
                ];
            }

            if (! empty($fields)) {
                $this->forge->addColumn('ipd_master', $fields);
            }
        }

        // 2. Modify ward_master.ward_type to support 'Day Care'
        if ($this->db->tableExists('ward_master')) {
            $this->db->query("
                ALTER TABLE `ward_master`
                MODIFY COLUMN `ward_type` ENUM(
                    'General', 'Private', 'Semi-Private', 'Deluxe',
                    'ICU', 'NICU', 'PICU', 'HDU', 'ICCU', 'CCU',
                    'Emergency', 'Day Care'
                ) NOT NULL DEFAULT 'General'
            ");

            // Seed default Emergency and Day Care wards if none exist
            $erWardCount = $this->db->table('ward_master')->where('ward_type', 'Emergency')->countAllResults();
            if ($erWardCount === 0) {
                $this->db->table('ward_master')->insert([
                    'ward_code'              => 'ER-01',
                    'ward_name'              => 'Casualty & Emergency Ward',
                    'ward_type'              => 'Emergency',
                    'gender_type'            => 'unisex',
                    'ward_category'          => 'adult',
                    'total_capacity'         => 6,
                    'floor_number'           => 0,
                    'has_oxygen'             => 1,
                    'has_suction'            => 1,
                    'has_monitor'            => 1,
                    'status'                 => 'active',
                    'remarks'                => 'Default Casualty / Emergency triage and observation unit',
                    'created_at'             => date('Y-m-d H:i:s'),
                ]);
            }

            $dcWardCount = $this->db->table('ward_master')->where('ward_type', 'Day Care')->countAllResults();
            if ($dcWardCount === 0) {
                $this->db->table('ward_master')->insert([
                    'ward_code'              => 'DC-01',
                    'ward_name'              => 'Day Care Unit',
                    'ward_type'              => 'Day Care',
                    'gender_type'            => 'unisex',
                    'ward_category'          => 'adult',
                    'total_capacity'         => 6,
                    'floor_number'           => 1,
                    'has_oxygen'             => 1,
                    'has_suction'            => 1,
                    'has_monitor'            => 1,
                    'status'                 => 'active',
                    'remarks'                => 'Day Care short stay unit for minor surgeries, dialysis, and chemotherapy',
                    'created_at'             => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 3. Ensure dedicated item types in ipd_item_type for separate billing headers
        if ($this->db->tableExists('ipd_item_type')) {
            $existingEr = $this->db->table('ipd_item_type')
                ->groupStart()
                    ->like('desc', 'EMERGENCY')
                    ->orLike('group_desc', 'Emergency')
                ->groupEnd()
                ->countAllResults();

            if ($existingEr === 0) {
                $this->db->table('ipd_item_type')->insert([
                    'desc'       => 'EMERGENCY / CASUALTY CHARGES',
                    'group_desc' => 'Emergency / Casualty Charges',
                ]);
            }

            $existingDc = $this->db->table('ipd_item_type')
                ->groupStart()
                    ->like('desc', 'DAY CARE')
                    ->orLike('group_desc', 'Day Care')
                ->groupEnd()
                ->countAllResults();

            if ($existingDc === 0) {
                $this->db->table('ipd_item_type')->insert([
                    'desc'       => 'DAY CARE CHARGES',
                    'group_desc' => 'Day Care Charges',
                ]);
            }
        }

        // 4. Seed default Day Care Discharge Summary Template
        if ($this->db->tableExists('ipd_discharge_templates')) {
            $dcTemplateExists = $this->db->table('ipd_discharge_templates')
                ->where('template_name', 'Day Care Discharge Summary')
                ->countAllResults();

            if ($dcTemplateExists === 0) {
                $dcHtml = '{{DAYCARE_HEADER}}'
                    . '<table class="discharge-info-table" border="1" cellpadding="6" style="width:100%; border-collapse:collapse; margin-bottom:12px;">'
                    . '<tr>'
                    . '<td><b>Patient Name</b>: {{PATIENT_NAME}}</td>'
                    . '<td><b>UHID</b>: {{UHID}}</td>'
                    . '<td><b>Day Care No.</b>: {{IPD_CODE}}</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td><b>Age / Gender</b>: {{AGE_GENDER}}</td>'
                    . '<td><b>Guardian</b>: {{GUARDIAN_RELATION}}{{GUARDIAN_NAME}}</td>'
                    . '<td><b>Phone</b>: {{PATIENT_MOBILE}}</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td><b>Admission</b>: {{ADMIT_DATE}}</td>'
                    . '<td><b>Discharge</b>: {{DISCHARGE_DATE}}</td>'
                    . '<td><b>Attending Doctor</b>: {{DOCTOR_NAMES}}</td>'
                    . '</tr>'
                    . '</table>'
                    . '<div>{{PRESENTING_COMPLAINTS}}</div>'
                    . '<div>{{FINAL_DIAGNOSIS}}</div>'
                    . '<div>{{PROCEDURE}}</div>'
                    . '<div>{{SURGERY}}</div>'
                    . '<div>{{PADSS_SCORE}}</div>'
                    . '<div>{{COURSE_IN_HOSPITAL}}</div>'
                    . '<div>{{DISCHARGE_MEDICATIONS}}</div>'
                    . '<div>{{RED_FLAGS_WARNINGS}}</div>'
                    . '<div>{{DIETARY_ADVICE}}</div>'
                    . '<div>{{REVIEW_AFTER}}</div>';

                $this->db->table('ipd_discharge_templates')->insert([
                    'template_name'         => 'Day Care Discharge Summary',
                    'page_size'             => 'A4',
                    'custom_width_mm'       => 210,
                    'custom_height_mm'      => 297,
                    'page_margin_top_cm'    => 0.80,
                    'page_margin_bottom_cm' => 0.80,
                    'page_margin_left_cm'   => 0.80,
                    'page_margin_right_cm'  => 0.80,
                    'margin_header_cm'      => 0.50,
                    'margin_footer_cm'      => 0.50,
                    'template_html'         => $dcHtml,
                    'is_default'            => 0,
                    'status'                => 1,
                    'created_at'            => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('ipd_master')) {
            $cols = [
                'discharge_score', 'sbar_handover', 'converted_by',
                'conversion_reason', 'converted_at', 'converted_from_code',
                'converted_from_type', 'daycare_procedure_name',
                'brought_by', 'police_constable_details', 'police_station',
                'mlc_number', 'is_mlc', 'triage_time', 'triage_level',
                'admission_type'
            ];
            foreach ($cols as $col) {
                if ($this->db->fieldExists($col, 'ipd_master')) {
                    $this->forge->dropColumn('ipd_master', $col);
                }
            }
        }
    }
}
