<?php

namespace App\Models;

use CodeIgniter\Model;

class PharmacyPurchaseOrderItemModel extends Model
{
    protected $table = 'mst_purchase_order_items';
    protected $primaryKey = 'po_item_id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'po_id',
        'item_id',
        'item_name',
        'generic_name',
        'category',
        'order_packs',
        'units_per_pack',
        'received_packs',
        'remaining_packs',
        'estimated_ptr',
        'discount_percent',
        'sch_disc_per',
        'scheme_info',
        'gst_rate',
        'estimated_amount',
        'status',
    ];
    protected $useTimestamps = false;
}
