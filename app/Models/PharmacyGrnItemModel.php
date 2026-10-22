<?php

namespace App\Models;

use CodeIgniter\Model;

class PharmacyGrnItemModel extends Model
{
    protected $table = 'mst_grn_items';
    protected $primaryKey = 'grn_item_id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'grn_id',
        'po_item_id',
        'item_code',
        'item_name',
        'batch_no',
        'expiry_date',
        'packing',
        'order_packs',
        'received_packs',
        'free_packs',
        'rejected_packs',
        'rejection_reason',
        'unit_purchase_rate',
        'gross_amount',
        'mrp',
        'selling_unit_rate',
        'discount_percent',
        'sch_disc_per',
        'sch_disc_amt',
        'taxable_amount',
        'cgst_percent',
        'cgst_amount',
        'sgst_percent',
        'sgst_amount',
        'igst_percent',
        'igst_amount',
        'hsn_code',
        'total_amount',
        'rack_no',
        'shelf_no',
        'cold_storage',
        'purchase_item_id',
    ];
    protected $useTimestamps = false;
}
