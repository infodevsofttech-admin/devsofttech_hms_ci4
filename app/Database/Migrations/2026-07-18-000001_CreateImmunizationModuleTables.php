<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateImmunizationModuleTables extends Migration
{
    public function up(): void
    {
        $this->createVaccineMasterTable();
        $this->createScheduleMasterTable();
        $this->createRecordsTable();
        $this->seedUipSchedule();
    }

    public function down(): void
    {
        $this->forge->dropTable('immunization_records', true);
        $this->forge->dropTable('immunization_schedule_master', true);
        $this->forge->dropTable('immunization_vaccine_master', true);
    }

    private function createVaccineMasterTable(): void
    {
        if ($this->tableExists('immunization_vaccine_master')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'vaccine_name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => false,
            ],
            'vaccine_code' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'vaccine_code_system' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'vaccine_display' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'target_disease_code' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'target_disease_name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'route_code' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'route_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'site_code' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'site_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('vaccine_name');
        $this->forge->addKey('vaccine_code');
        $this->forge->addKey('is_active');
        $this->forge->createTable('immunization_vaccine_master', true);
    }

    private function createScheduleMasterTable(): void
    {
        if ($this->tableExists('immunization_schedule_master')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'schedule_name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'default' => 'UIP',
            ],
            'age_label' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'age_value' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'age_unit' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'days',
            ],
            'age_offset_days' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'vaccine_master_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'dose_number' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'series_name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'default' => 'Indian Universal Immunization Programme',
            ],
            'series_doses' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'is_uip' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('vaccine_master_id');
        $this->forge->addKey('age_offset_days');
        $this->forge->addKey('is_uip');
        $this->forge->addKey('is_active');
        $this->forge->createTable('immunization_schedule_master', true);
    }

    private function createRecordsTable(): void
    {
        if ($this->tableExists('immunization_records')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'opd_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'ipd_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'schedule_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'vaccine_master_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'vaccine_name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => false,
            ],
            'vaccine_code' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'vaccine_code_system' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'dose_number' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'given_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'due',
            ],
            'lot_number' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'expiry_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'manufacturer' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'performer_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'location_name' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'route_code' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'route_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'site_code' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'site_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'reaction_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'abdm_care_context_reference' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'abdm_health_record_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'abdm_push_status' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'created_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'updated_by' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('patient_id');
        $this->forge->addKey('schedule_id');
        $this->forge->addKey('vaccine_master_id');
        $this->forge->addKey('due_date');
        $this->forge->addKey('given_date');
        $this->forge->addKey('status');
        $this->forge->addKey('abdm_care_context_reference');
        $this->forge->createTable('immunization_records', true);
    }

    private function seedUipSchedule(): void
    {
        if (! $this->tableExists('immunization_vaccine_master') || ! $this->tableExists('immunization_schedule_master')) {
            return;
        }

        $existing = $this->db->table('immunization_schedule_master')->where('is_uip', 1)->countAllResults();
        if ($existing > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $codeSystem = 'https://hms.local/immunization/uip';
        $vaccines = [
            'UIP-BCG' => ['BCG', 'Tuberculosis', 'Intradermal'],
            'UIP-OPV' => ['Oral Polio Vaccine', 'Poliomyelitis', 'Oral'],
            'UIP-HEPB' => ['Hepatitis B Vaccine', 'Hepatitis B', 'Intramuscular'],
            'UIP-PENTA' => ['Pentavalent Vaccine', 'Diphtheria, Pertussis, Tetanus, Hepatitis B and Hib', 'Intramuscular'],
            'UIP-ROTA' => ['Rotavirus Vaccine', 'Rotavirus gastroenteritis', 'Oral'],
            'UIP-FIPV' => ['Fractional IPV', 'Poliomyelitis', 'Intradermal'],
            'UIP-PCV' => ['Pneumococcal Conjugate Vaccine', 'Pneumococcal disease', 'Intramuscular'],
            'UIP-MR' => ['Measles Rubella Vaccine', 'Measles and rubella', 'Subcutaneous'],
            'UIP-JE' => ['Japanese Encephalitis Vaccine', 'Japanese encephalitis', 'Subcutaneous'],
            'UIP-DPT' => ['DPT Vaccine', 'Diphtheria, Pertussis and Tetanus', 'Intramuscular'],
            'UIP-TD' => ['Td Vaccine', 'Tetanus and diphtheria', 'Intramuscular'],
        ];

        $vaccineRows = [];
        foreach ($vaccines as $code => $vaccine) {
            $vaccineRows[] = [
                'vaccine_name' => $vaccine[0],
                'vaccine_code' => $code,
                'vaccine_code_system' => $codeSystem,
                'vaccine_display' => $vaccine[0],
                'target_disease_name' => $vaccine[1],
                'route_name' => $vaccine[2],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->db->table('immunization_vaccine_master')->insertBatch($vaccineRows);

        $vaccineIds = [];
        $rows = $this->db->table('immunization_vaccine_master')->select('id,vaccine_code')->whereIn('vaccine_code', array_keys($vaccines))->get()->getResultArray();
        foreach ($rows as $row) {
            $vaccineIds[(string) $row['vaccine_code']] = (int) $row['id'];
        }

        $schedule = [
            ['At Birth', 0, 'days', 0, 'UIP-BCG', '1', '1', 'Preferably at birth or as early as possible.'],
            ['At Birth', 0, 'days', 0, 'UIP-OPV', '0', '4', 'OPV birth dose.'],
            ['At Birth', 0, 'days', 0, 'UIP-HEPB', '0', '4', 'Within 24 hours of birth where possible.'],
            ['6 Weeks', 6, 'weeks', 42, 'UIP-OPV', '1', '4', ''],
            ['6 Weeks', 6, 'weeks', 42, 'UIP-PENTA', '1', '3', ''],
            ['6 Weeks', 6, 'weeks', 42, 'UIP-ROTA', '1', '3', ''],
            ['6 Weeks', 6, 'weeks', 42, 'UIP-FIPV', '1', '2', ''],
            ['6 Weeks', 6, 'weeks', 42, 'UIP-PCV', '1', '3', 'Where applicable as per local programme.'],
            ['10 Weeks', 10, 'weeks', 70, 'UIP-OPV', '2', '4', ''],
            ['10 Weeks', 10, 'weeks', 70, 'UIP-PENTA', '2', '3', ''],
            ['10 Weeks', 10, 'weeks', 70, 'UIP-ROTA', '2', '3', ''],
            ['14 Weeks', 14, 'weeks', 98, 'UIP-OPV', '3', '4', ''],
            ['14 Weeks', 14, 'weeks', 98, 'UIP-PENTA', '3', '3', ''],
            ['14 Weeks', 14, 'weeks', 98, 'UIP-ROTA', '3', '3', ''],
            ['14 Weeks', 14, 'weeks', 98, 'UIP-FIPV', '2', '2', ''],
            ['14 Weeks', 14, 'weeks', 98, 'UIP-PCV', '2', '3', 'Where applicable as per local programme.'],
            ['9-12 Months', 9, 'months', 274, 'UIP-MR', '1', '2', ''],
            ['9-12 Months', 9, 'months', 274, 'UIP-JE', '1', '2', 'In JE endemic districts.'],
            ['9-12 Months', 9, 'months', 274, 'UIP-PCV', 'Booster', '3', 'Where applicable as per local programme.'],
            ['16-24 Months', 16, 'months', 487, 'UIP-DPT', 'Booster 1', '3', ''],
            ['16-24 Months', 16, 'months', 487, 'UIP-OPV', 'Booster', '4', ''],
            ['16-24 Months', 16, 'months', 487, 'UIP-MR', '2', '2', ''],
            ['16-24 Months', 16, 'months', 487, 'UIP-JE', '2', '2', 'In JE endemic districts.'],
            ['5-6 Years', 5, 'years', 1826, 'UIP-DPT', 'Booster 2', '3', ''],
            ['10 Years', 10, 'years', 3652, 'UIP-TD', '1', '2', ''],
            ['16 Years', 16, 'years', 5844, 'UIP-TD', '2', '2', ''],
        ];

        $scheduleRows = [];
        foreach ($schedule as $index => $item) {
            $vaccineId = $vaccineIds[$item[4]] ?? 0;
            if ($vaccineId <= 0) {
                continue;
            }

            $scheduleRows[] = [
                'schedule_name' => 'UIP',
                'age_label' => $item[0],
                'age_value' => $item[1],
                'age_unit' => $item[2],
                'age_offset_days' => $item[3],
                'vaccine_master_id' => $vaccineId,
                'dose_number' => $item[5],
                'series_name' => 'Indian Universal Immunization Programme',
                'series_doses' => $item[6],
                'notes' => $item[7] !== '' ? $item[7] : null,
                'sort_order' => $index + 1,
                'is_uip' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($scheduleRows)) {
            $this->db->table('immunization_schedule_master')->insertBatch($scheduleRows);
        }
    }

    private function tableExists(string $table): bool
    {
        $rows = $this->db->query('SHOW TABLES LIKE ?', [$table])->getResultArray();
        return ! empty($rows);
    }
}