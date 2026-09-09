<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LinkMedicalStoreToPharmacyProductMaster extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Add product_master_id to mst_items if it doesn't exist
        if ($db->tableExists('mst_items')) {
            $fields = $db->getFieldNames('mst_items') ?? [];
            if (!in_array('product_master_id', $fields, true)) {
                $this->forge->addColumn('mst_items', [
                    'product_master_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'item_id'
                    ]
                ]);

                // Add index
                $db->query("ALTER TABLE `mst_items` ADD INDEX `idx_mst_items_product_master_id` (`product_master_id`)");
            }
        }

        // 2. Link existing mst_items to med_product_master where item_name matches
        if ($db->tableExists('mst_items') && $db->tableExists('med_product_master')) {
            $db->query("
                UPDATE `mst_items` i
                JOIN `med_product_master` p ON LOWER(TRIM(i.item_name)) = LOWER(TRIM(p.item_name))
                SET i.product_master_id = p.id
                WHERE i.product_master_id IS NULL OR i.product_master_id = 0
            ");

            // 3. For any mst_items not yet in med_product_master, sync them into med_product_master
            // so HMSPharmacy immediately has all MedicalStore medicines!
            $unlinkedItems = $db->table('mst_items')
                ->where('product_master_id IS NULL', null, false)
                ->orWhere('product_master_id', 0)
                ->get()
                ->getResultArray();

            $pFields = $db->getFieldNames('med_product_master') ?? [];

            foreach ($unlinkedItems as $item) {
                $itemName = trim($item['item_name']);
                if ($itemName === '') continue;

                // Check if already in med_product_master
                $existing = $db->table('med_product_master')
                    ->where('LOWER(TRIM(item_name))', strtolower($itemName))
                    ->get()
                    ->getRowArray();

                if ($existing) {
                    $db->table('mst_items')
                        ->where('item_id', $item['item_id'])
                        ->update(['product_master_id' => $existing['id']]);
                    continue;
                }

                $gst = (float)($item['gst_rate'] ?? 12.00);
                $halfGst = round($gst / 2, 2);

                $insertData = [
                    'item_name'            => $itemName,
                    'formulation'          => !empty($item['category']) ? trim($item['category']) : 'Tablet',
                    'formulation_id'       => 0,
                    'genericname'          => !empty($item['generic_name']) ? trim($item['generic_name']) : '',
                    'packing'              => !empty($item['units_per_pack']) ? (string)$item['units_per_pack'] : '10',
                    'unit_1'               => '0',
                    'unit_2'               => '0',
                    'HSNCODE'              => !empty($item['hsn_code']) ? trim($item['hsn_code']) : '3004',
                    'CGST_per'             => $halfGst,
                    'SGST_per'             => $halfGst,
                    'company_name'         => !empty($item['manufacturer_name']) ? trim($item['manufacturer_name']) : '',
                    'company_id'           => 0,
                    'mfgname'              => !empty($item['manufacturer_name']) ? trim($item['manufacturer_name']) : '',
                    're_order_qty'         => !empty($item['min_reorder_level']) ? (int)$item['min_reorder_level'] : 10,
                    'is_continue'          => 1,
                    'batch_applicable'     => 1,
                    'exp_date_applicable'  => 1,
                    'schedule_h'           => (stripos($item['drug_schedule'] ?? '', 'Schedule H1') === false && stripos($item['drug_schedule'] ?? '', 'Schedule H') !== false) ? 1 : 0,
                    'schedule_h1'          => stripos($item['drug_schedule'] ?? '', 'Schedule H1') !== false ? 1 : 0,
                    'schedule_x'           => stripos($item['drug_schedule'] ?? '', 'Schedule X') !== false ? 1 : 0,
                    'insert_by'            => 'MedicalStore Migration'
                ];

                // Filter only valid columns present in med_product_master
                $filtered = [];
                foreach ($insertData as $k => $v) {
                    if (in_array($k, $pFields, true)) {
                        $filtered[$k] = $v;
                    }
                }

                try {
                    $db->table('med_product_master')->insert($filtered);
                    $newMasterId = (int)$db->insertID();
                    if ($newMasterId > 0) {
                        $db->table('mst_items')
                            ->where('item_id', $item['item_id'])
                            ->update(['product_master_id' => $newMasterId]);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Migration med_product_master insert error: ' . $e->getMessage());
                }
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('mst_items')) {
            $fields = $db->getFieldNames('mst_items') ?? [];
            if (in_array('product_master_id', $fields, true)) {
                $this->forge->dropColumn('mst_items', 'product_master_id');
            }
        }
    }
}
