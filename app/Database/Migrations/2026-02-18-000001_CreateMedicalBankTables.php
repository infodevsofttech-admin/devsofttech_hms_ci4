<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMedicalBankTables extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('medical_bank')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'bank_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('medical_bank', true);
        }

        if (! $this->db->tableExists('medical_bank_payment_source')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'bank_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'pay_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('bank_id');
            $this->forge->createTable('medical_bank_payment_source', true);
        }

        if ($this->db->tableExists('hospital_bank') && $this->db->tableExists('medical_bank')) {
            $countMedicalBank = (int) $this->db->table('medical_bank')->countAllResults();
            if ($countMedicalBank === 0) {
                $this->db->query('INSERT INTO medical_bank (bank_name) SELECT bank_name FROM hospital_bank');
            }
        }

        if (
            $this->db->tableExists('hospital_bank_payment_source')
            && $this->db->tableExists('hospital_bank')
            && $this->db->tableExists('medical_bank')
            && $this->db->tableExists('medical_bank_payment_source')
        ) {
            $countMedicalSource = (int) $this->db->table('medical_bank_payment_source')->countAllResults();
            if ($countMedicalSource === 0) {
                $this->db->query(
                    'INSERT INTO medical_bank_payment_source (bank_id, pay_type)
                     SELECT mb.id, hps.pay_type
                     FROM hospital_bank_payment_source hps
                     INNER JOIN hospital_bank hb ON hb.id = hps.bank_id
                     INNER JOIN medical_bank mb ON mb.bank_name = hb.bank_name'
                );
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('medical_bank_payment_source', true);
        $this->forge->dropTable('medical_bank', true);
    }
}
