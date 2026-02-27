<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OpdDemoCleanupSeeder extends Seeder
{
    public function run()
    {
        if (! $this->db->tableExists('ai_demo_seed_log')) {
            return;
        }

        $seedNames = [
            'App\\Database\\Seeds\\OpdDemoMasterSeeder',
            'App\\Database\\Seeds\\OpdDemoLargeSeeder',
        ];

        $logs = $this->db->table('ai_demo_seed_log')
            ->select('id,table_name,row_id')
            ->whereIn('seed_name', $seedNames)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        if (empty($logs)) {
            return;
        }

        $deletedLogIds = [];
        foreach ($logs as $log) {
            $logId = (int) ($log['id'] ?? 0);
            $table = trim((string) ($log['table_name'] ?? ''));
            $rowId = (int) ($log['row_id'] ?? 0);
            if ($logId <= 0 || $table === '' || $rowId <= 0) {
                continue;
            }

            if (! $this->db->tableExists($table)) {
                $deletedLogIds[] = $logId;
                continue;
            }

            $fields = $this->db->getFieldNames($table);
            if (! in_array('id', $fields, true)) {
                continue;
            }

            $this->db->table($table)->where('id', $rowId)->delete();
            $deletedLogIds[] = $logId;
        }

        if (! empty($deletedLogIds)) {
            $this->db->table('ai_demo_seed_log')->whereIn('id', $deletedLogIds)->delete();
        }
    }
}
