<?php

namespace App\Models;

use CodeIgniter\Model;

class HospitalStockItemModel extends Model
{
    protected $table = 'hsm_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'item_code',
        'name',
        'category_id',
        'item_type',
        'uom',
        'purchase_uom',
        'issue_uom',
        'issue_per_purchase',
        'is_daily_use',
        'store_location',
        'barcode',
        'qr_code',
        'current_stock',
        'min_stock_level',
        'reorder_level',
        'unit_cost',
        'expiry_date',
        'status',
    ];
    protected $useTimestamps = true;
}
