<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureEchoChargeGroup extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('hc_item_type')) {
            return;
        }

        $builder = $this->db->table('hc_item_type');

        $rowById = $builder->where('itype_id', 6)->get(1)->getRowArray();
        if (! empty($rowById)) {
            $groupDesc = strtolower(trim((string) ($rowById['group_desc'] ?? '')));
            if ($groupDesc !== 'echo') {
                $builder->where('itype_id', 6)->update(['group_desc' => 'Echo']);
            }

            return;
        }

        $echoByName = $this->db->query("SELECT itype_id FROM hc_item_type WHERE LOWER(TRIM(group_desc)) = 'echo' LIMIT 1")
            ->getRowArray();

        if (! empty($echoByName)) {
            return;
        }

        $insert = [
            'itype_id' => 6,
            'group_desc' => 'Echo',
        ];

        // Keep consistency with existing item type visibility flags when available.
        $columns = array_map(
            static fn (array $col): string => (string) ($col['Field'] ?? ''),
            $this->db->query('SHOW COLUMNS FROM hc_item_type')->getResultArray()
        );

        if (in_array('is_ipd_opd', $columns, true)) {
            $insert['is_ipd_opd'] = 0;
        }

        $this->db->table('hc_item_type')->insert($insert);
    }

    public function down()
    {
        if (! $this->db->tableExists('hc_item_type')) {
            return;
        }

        $this->db->query("DELETE FROM hc_item_type WHERE itype_id = 6 AND LOWER(TRIM(group_desc)) = 'echo'");
    }
}
