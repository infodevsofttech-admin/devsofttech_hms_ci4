<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;

class MedicalStoreAdmin extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $q = trim((string)($this->request->getGet('q') ?? ''));

        $builder = $this->db->table('mst_stores');
        if (!empty($q)) {
            $builder->groupStart()
                ->like('store_name', $q)
                ->orLike('store_code', $q)
                ->orLike('store_slug', $q)
                ->orLike('building_name', $q)
                ->orLike('drug_license_no_20b', $q)
                ->orLike('gstin', $q)
                ->groupEnd();
        }

        $stores = $builder->orderBy('is_main_store', 'DESC')->orderBy('store_id', 'ASC')->get()->getResultArray();

        // Enforce security keys and slugs
        foreach ($stores as &$st) {
            $needsUpdate = false;
            $updates = [];

            if (empty($st['store_slug'])) {
                $st['store_slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $st['store_code'] ?: ('store' . $st['store_id'])));
                $updates['store_slug'] = $st['store_slug'];
                $needsUpdate = true;
            }

            if (empty($st['security_key'])) {
                $st['security_key'] = 'HMS-' . strtoupper(bin2hex(random_bytes(3))) . '-' . rand(100, 999);
                $updates['security_key'] = $st['security_key'];
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $this->db->table('mst_stores')->where('store_id', $st['store_id'])->update($updates);
            }

            // Fetch authorized devices count
            $deviceCount = $this->db->table('mst_store_devices')
                ->where('store_id', $st['store_id'])
                ->where('status', 'authorized')
                ->countAllResults();
            $st['authorized_devices_count'] = $deviceCount;
        }

        // Fetch recent authorized devices across all stores
        $devices = $this->db->table('mst_store_devices d')
            ->select('d.*, s.store_name, s.store_code')
            ->join('mst_stores s', 's.store_id = d.store_id')
            ->orderBy('d.device_id', 'DESC')
            ->limit(30)
            ->get()
            ->getResultArray();

        return view('Setting/MedicalStore/medical_store_index', [
            'stores' => $stores,
            'devices' => $devices,
            'searchQuery' => $q
        ]);
    }

    public function save()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid request']);
        }

        $id = (int)($this->request->getPost('store_id') ?? 0);
        $storeCode = trim((string)($this->request->getPost('store_code') ?? ''));
        $storeSlug = strtolower(trim((string)($this->request->getPost('store_slug') ?? '')));
        $storeName = trim((string)($this->request->getPost('store_name') ?? ''));

        if (empty($storeName) || empty($storeCode)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Store Name and Store Code are required.']);
        }

        if (empty($storeSlug)) {
            $storeSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $storeCode));
        }

        // Check unique slug
        $checkSlug = $this->db->table('mst_stores')
            ->where('store_slug', $storeSlug)
            ->where('store_id !=', $id)
            ->get()
            ->getRowArray();

        if ($checkSlug) {
            return $this->response->setJSON(['ok' => false, 'error' => "URL Link Slug '{$storeSlug}' is already assigned to another store. Please choose a unique slug."]);
        }

        $secKey = trim((string)($this->request->getPost('security_key') ?? ''));
        if (empty($secKey)) {
            $secKey = 'HMS-' . strtoupper(bin2hex(random_bytes(3))) . '-' . rand(100, 999);
        }

        $data = [
            'store_code'                 => $storeCode,
            'store_slug'                 => $storeSlug,
            'store_name'                 => $storeName,
            'building_name'              => trim((string)($this->request->getPost('building_name') ?? '')),
            'floor_no'                   => trim((string)($this->request->getPost('floor_no') ?? '')),
            'room_no'                    => trim((string)($this->request->getPost('room_no') ?? '')),
            'is_main_store'              => !empty($this->request->getPost('is_main_store')) ? 1 : 0,
            'drug_license_no_20b'        => trim((string)($this->request->getPost('drug_license_no_20b') ?? '')),
            'drug_license_no_21b'        => trim((string)($this->request->getPost('drug_license_no_21b') ?? '')),
            'drug_license_no_20f_x'      => trim((string)($this->request->getPost('drug_license_no_20f_x') ?? '')),
            'gstin'                      => strtoupper(trim((string)($this->request->getPost('gstin') ?? ''))),
            'pan_no'                     => strtoupper(trim((string)($this->request->getPost('pan_no') ?? ''))),
            'fssai_no'                   => trim((string)($this->request->getPost('fssai_no') ?? '')),
            'state_code'                 => trim((string)($this->request->getPost('state_code') ?? '07')),
            'state_name'                 => trim((string)($this->request->getPost('state_name') ?? 'Delhi')),
            'registered_pharmacist_name' => trim((string)($this->request->getPost('registered_pharmacist_name') ?? '')),
            'pharmacist_reg_no'          => trim((string)($this->request->getPost('pharmacist_reg_no') ?? '')),
            'contact_phone'              => trim((string)($this->request->getPost('contact_phone') ?? '')),
            'contact_email'              => trim((string)($this->request->getPost('contact_email') ?? '')),
            'address'                    => trim((string)($this->request->getPost('address') ?? '')),
            'invoice_prefix'             => trim((string)($this->request->getPost('invoice_prefix') ?? 'INV/')),
            'next_invoice_no'            => !empty($this->request->getPost('next_invoice_no')) ? (int)$this->request->getPost('next_invoice_no') : 1001,
            'bank_name'                  => trim((string)($this->request->getPost('bank_name') ?? '')),
            'bank_account_no'            => trim((string)($this->request->getPost('bank_account_no') ?? '')),
            'bank_ifsc'                  => trim((string)($this->request->getPost('bank_ifsc') ?? '')),
            'upi_id'                     => trim((string)($this->request->getPost('upi_id') ?? '')),
            'abdm_hfr_id'                => trim((string)($this->request->getPost('abdm_hfr_id') ?? '')),
            'abdm_hip_id'                => trim((string)($this->request->getPost('abdm_hip_id') ?? '')),
            'pharmacist_hpr_id'          => trim((string)($this->request->getPost('pharmacist_hpr_id') ?? '')),
            'terms_conditions'           => trim((string)($this->request->getPost('terms_conditions') ?? '')),
            'security_key'               => $secKey,
            'is_active'                  => isset($_POST['is_active']) ? (int)$this->request->getPost('is_active') : 1,
        ];

        if ($id > 0) {
            $this->db->table('mst_stores')->where('store_id', $id)->update($data);
            $msg = 'Medical Store details updated successfully.';
        } else {
            $this->db->table('mst_stores')->insert($data);
            $id = $this->db->insertID();
            $msg = 'New Medical Store Counter created successfully.';
        }

        return $this->response->setJSON(['ok' => true, 'message' => $msg, 'store_id' => $id, 'store_slug' => $storeSlug]);
    }

    public function generateOtp()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid request']);
        }

        $storeId = (int)($this->request->getPost('store_id') ?? 0);
        $store = $this->db->table('mst_stores')->where('store_id', $storeId)->get()->getRowArray();
        if (!$store) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Store not found']);
        }

        $otp = (string)rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+60 minutes'));

        $this->db->table('mst_stores')->where('store_id', $storeId)->update([
            'current_otp' => $otp,
            'otp_expiry'  => $expiry
        ]);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Terminal Verification OTP generated successfully.',
            'otp' => $otp,
            'expiry' => date('h:i A (d-m-Y)', strtotime($expiry)),
            'store_name' => $store['store_name']
        ]);
    }

    public function regenerateSecurityKey()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid request']);
        }

        $storeId = (int)($this->request->getPost('store_id') ?? 0);
        $newKey = 'HMS-' . strtoupper(bin2hex(random_bytes(3))) . '-' . rand(100, 999);

        $this->db->table('mst_stores')->where('store_id', $storeId)->update([
            'security_key' => $newKey
        ]);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'New Security Key generated successfully.',
            'security_key' => $newKey
        ]);
    }

    public function revokeDevice()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid request']);
        }

        $deviceId = (int)($this->request->getPost('device_id') ?? 0);
        $this->db->table('mst_store_devices')->where('device_id', $deviceId)->update([
            'status' => 'revoked'
        ]);

        return $this->response->setJSON(['ok' => true, 'message' => 'Terminal computer access revoked successfully.']);
    }

    public function delete()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid request']);
        }

        $storeId = (int)($this->request->getPost('store_id') ?? 0);
        // Soft deactivate store
        $this->db->table('mst_stores')->where('store_id', $storeId)->update(['is_active' => 0]);

        return $this->response->setJSON(['ok' => true, 'message' => 'Store deactivated successfully.']);
    }

    /**
     * Marg Pharmacy Software / Excel CSV Inventory Import
     */
    public function importMarg()
    {
        $storeId = (int)($this->request->getPost('store_id') ?? 0);
        if ($storeId <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please select a destination Medical Store.']);
        }

        $file = $this->request->getFile('import_file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please upload a valid CSV or Excel file.']);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['csv', 'txt'])) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Please upload CSV formatted export from Marg ERP or Excel.']);
        }

        $handle = fopen($file->getTempName(), 'r');
        if (!$handle) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Unable to read uploaded file.']);
        }

        $header = fgetcsv($handle, 4096, ',');
        if (!$header) {
            fclose($handle);
            return $this->response->setJSON(['ok' => false, 'error' => 'CSV file is empty.']);
        }

        // Normalize header names
        $headerMap = [];
        foreach ($header as $idx => $h) {
            $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $h)));
            $headerMap[$cleaned] = $idx;
        }

        // Helper function to find column index
        $findCol = function(array $aliases) use ($headerMap) {
            foreach ($aliases as $a) {
                $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $a)));
                if (isset($headerMap[$cleaned])) return $headerMap[$cleaned];
            }
            return null;
        };

        $colName    = $findCol(['itemname', 'product', 'particulars', 'medicinename', 'name', 'item']);
        $colGeneric = $findCol(['generic', 'genericname', 'molecule', 'composition']);
        $colCat     = $findCol(['category', 'type', 'itemtype']);
        $colPack    = $findCol(['packing', 'pack', 'packsize']);
        $colBatch   = $findCol(['batch', 'batchno', 'batchnumber']);
        $colExpiry  = $findCol(['exp', 'expiry', 'expdate', 'expirydate']);
        $colMrp     = $findCol(['mrp', 'maxretailprice']);
        $colPtr     = $findCol(['ptr', 'purchaserate', 'costrate', 'rate', 'cost', 'purrate']);
        $colHsn     = $findCol(['hsn', 'hsncode', 'hsnno']);
        $colGst     = $findCol(['gst', 'gstpct', 'gstper', 'tax', 'taxpct']);
        $colQty     = $findCol(['qty', 'quantity', 'stock', 'openingstock', 'opqty']);
        $colBarcode = $findCol(['barcode', 'itemcode', 'code']);

        if ($colName === null) {
            fclose($handle);
            return $this->response->setJSON(['ok' => false, 'error' => "Could not locate 'Item Name' / 'Product' column in CSV header."]);
        }

        $importedRows = 0;
        $newItems = 0;
        $newBatches = 0;
        $totalValuation = 0;

        $this->db->transStart();

        while (($row = fgetcsv($handle, 4096, ',')) !== false) {
            $name = trim($row[$colName] ?? '');
            if (empty($name)) continue;

            $generic = ($colGeneric !== null) ? trim($row[$colGeneric] ?? '') : '';
            $cat     = ($colCat !== null) ? trim($row[$colCat] ?? 'Tablet') : 'Tablet';
            $pack    = ($colPack !== null) ? trim($row[$colPack] ?? '10 Tablets') : '10 Tablets';
            $batchNo = ($colBatch !== null) ? trim($row[$colBatch] ?? 'OPN-01') : 'OPN-01';
            $expRaw  = ($colExpiry !== null) ? trim($row[$colExpiry] ?? '') : '';
            $mrp     = ($colMrp !== null) ? (float)preg_replace('/[^0-9.]/', '', $row[$colMrp] ?? '0') : 0;
            $ptr     = ($colPtr !== null) ? (float)preg_replace('/[^0-9.]/', '', $row[$colPtr] ?? '0') : ($mrp * 0.75);
            $hsn     = ($colHsn !== null) ? trim($row[$colHsn] ?? '3004') : '3004';
            $gst     = ($colGst !== null) ? (float)preg_replace('/[^0-9.]/', '', $row[$colGst] ?? '12') : 12;
            $qty     = ($colQty !== null) ? (int)preg_replace('/[^0-9]/', '', $row[$colQty] ?? '10') : 10;
            $barcode = ($colBarcode !== null) ? trim($row[$colBarcode] ?? '') : '';

            // Parse Marg Expiry formats (MM/YY, MM/YYYY, DD-MM-YYYY, YYYY-MM-DD)
            $expiryDate = date('Y-12-31', strtotime('+1 year'));
            if (!empty($expRaw)) {
                if (preg_match('/^(\d{1,2})[\/\-](\d{2})$/', $expRaw, $m)) {
                    $expiryDate = '20' . $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-28';
                } elseif (preg_match('/^(\d{1,2})[\/\-](\d{4})$/', $expRaw, $m)) {
                    $expiryDate = $m[2] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-28';
                } elseif (strtotime($expRaw)) {
                    $expiryDate = date('Y-m-d', strtotime($expRaw));
                }
            }

            // 1. Find or insert into mst_items (shared drug catalog)
            $itemRow = $this->db->table('mst_items')->where('item_name', $name)->get()->getRowArray();
            if ($itemRow) {
                $itemId = (int)$itemRow['item_id'];
            } else {
                $this->db->table('mst_items')->insert([
                    'item_name'         => $name,
                    'generic_name'      => $generic,
                    'category'          => $cat ?: 'Tablet',
                    'hsn_code'          => $hsn ?: '3004',
                    'gst_rate'          => $gst ?: 12,
                    'unit_pack'         => $pack ?: '10 Tablets',
                    'barcode'           => $barcode,
                    'drug_schedule'     => 'Schedule H',
                    'manufacturer_name' => 'Imported Catalog'
                ]);
                $itemId = $this->db->insertID();
                $newItems++;
            }

            // 2. Find or insert into mst_batches for this store
            $batchRow = $this->db->table('mst_batches')
                ->where('store_id', $storeId)
                ->where('item_id', $itemId)
                ->where('batch_no', $batchNo)
                ->get()
                ->getRowArray();

            if ($batchRow) {
                $batchId = (int)$batchRow['batch_id'];
                $this->db->table('mst_batches')->where('batch_id', $batchId)->update([
                    'expiry_date'       => $expiryDate,
                    'mrp'               => $mrp,
                    'ptr'               => $ptr,
                    'purchase_rate_net' => $ptr * (1 + $gst / 100),
                    'gst_rate'          => $gst
                ]);
            } else {
                $this->db->table('mst_batches')->insert([
                    'store_id'          => $storeId,
                    'item_id'           => $itemId,
                    'batch_no'          => $batchNo,
                    'mfg_date'          => date('Y-01-01'),
                    'expiry_date'       => $expiryDate,
                    'mrp'               => $mrp,
                    'ptr'               => $ptr,
                    'purchase_rate_net' => $ptr * (1 + $gst / 100),
                    'gst_rate'          => $gst,
                    'barcode'           => $barcode
                ]);
                $batchId = $this->db->insertID();
                $newBatches++;
            }

            // 3. Update stock in mst_stock
            $stockRow = $this->db->table('mst_stock')
                ->where('store_id', $storeId)
                ->where('item_id', $itemId)
                ->where('batch_id', $batchId)
                ->get()
                ->getRowArray();

            if ($stockRow) {
                $this->db->table('mst_stock')->where('stock_id', $stockRow['stock_id'])->update([
                    'current_qty' => $stockRow['current_qty'] + $qty
                ]);
            } else {
                $this->db->table('mst_stock')->insert([
                    'store_id'    => $storeId,
                    'item_id'     => $itemId,
                    'batch_id'    => $batchId,
                    'current_qty' => $qty
                ]);
            }

            $lineVal = $qty * $ptr;
            $totalValuation += $lineVal;
            $importedRows++;
        }

        fclose($handle);

        // Record opening stock valuation journal voucher in double-entry ledgers
        if ($totalValuation > 0) {
            $voucherNo = 'MARG-IMP-' . $storeId . '-' . date('YmdHis');
            $stockHead = $this->db->table('mst_account_heads')->where('head_code', '1003')->get()->getRowArray();
            $equityHead = $this->db->table('mst_account_heads')->where('head_code', '3004')->get()->getRowArray();

            if ($stockHead && $equityHead) {
                $this->db->table('mst_ledger_entries')->insert([
                    'store_id' => $storeId, 'voucher_no' => $voucherNo, 'voucher_type' => 'OPENING_STOCK', 'voucher_date' => date('Y-m-d'),
                    'account_head_id' => $stockHead['head_id'], 'debit_amount' => $totalValuation, 'credit_amount' => 0,
                    'narration' => "Marg ERP Opening Stock Inward ($importedRows lines)"
                ]);
                $this->db->table('mst_ledger_entries')->insert([
                    'store_id' => $storeId, 'voucher_no' => $voucherNo, 'voucher_type' => 'OPENING_STOCK', 'voucher_date' => date('Y-m-d'),
                    'account_head_id' => $equityHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $totalValuation,
                    'narration' => "Marg ERP Opening Capital Credit"
                ]);
            }
        }

        $this->db->transComplete();

        return $this->response->setJSON([
            'ok' => true,
            'message' => "Successfully imported {$importedRows} inventory lines from Marg Pharmacy file.",
            'details' => [
                'imported_rows'    => $importedRows,
                'new_catalog_items'=> $newItems,
                'new_batches'      => $newBatches,
                'total_valuation'  => round($totalValuation, 2)
            ]
        ]);
    }
}
