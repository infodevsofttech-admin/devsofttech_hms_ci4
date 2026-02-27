<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiScanColumnsToFileUploadData extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('file_upload_data')) {
            return;
        }

        $fields = $this->db->getFieldNames('file_upload_data');
        $toAdd = [];

        if (! in_array('document_type', $fields, true)) {
            $toAdd['document_type'] = [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
                'after' => in_array('scan_type', $fields, true) ? 'scan_type' : null,
            ];
        }

        if (! in_array('content_description', $fields, true)) {
            $toAdd['content_description'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => in_array('document_type', $fields, true) ? 'document_type' : null,
            ];
        }

        if (! in_array('ai_status', $fields, true)) {
            $toAdd['ai_status'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ];
        }

        if (! in_array('ai_alert_flag', $fields, true)) {
            $toAdd['ai_alert_flag'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ];
        }

        if (! in_array('ai_alert_text', $fields, true)) {
            $toAdd['ai_alert_text'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ];
        }

        if (! empty($toAdd)) {
            $this->forge->addColumn('file_upload_data', $toAdd);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('file_upload_data')) {
            return;
        }

        $fields = $this->db->getFieldNames('file_upload_data');
        foreach (['ai_alert_text', 'ai_alert_flag', 'ai_status', 'content_description', 'document_type'] as $column) {
            if (in_array($column, $fields, true)) {
                $this->forge->dropColumn('file_upload_data', $column);
            }
        }
    }
}
