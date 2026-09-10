<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateGstSlabsAcrossMasters extends Migration
{
    public function up()
    {
        // 1. Ensure med_gst_per exists and contains the current GST slabs (half-rates for CGST/SGST)
        // Slabs: 0% -> 0.00, 5% -> 2.50, 18% -> 9.00, 40% -> 20.00, 12% (legacy) -> 6.00, 28% (legacy) -> 14.00
        if ($this->db->tableExists('med_gst_per')) {
            $requiredRates = [
                0.00,
                2.50,
                6.00,
                9.00,
                14.00,
                20.00
            ];

            foreach ($requiredRates as $rate) {
                $row = $this->db->query("SELECT id FROM `med_gst_per` WHERE ROUND(`gst_per`, 2) = ROUND(?, 2) LIMIT 1", [$rate])->getRowArray();

                if (!$row) {
                    $this->db->table('med_gst_per')->insert([
                        'gst_per' => $rate
                    ]);
                }
            }
        }
    }

    public function down()
    {
        // Keep rates for safety of historical data
    }
}
