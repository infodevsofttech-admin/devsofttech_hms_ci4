<?php

namespace App\Controllers\Api\v1;

use App\Controllers\BaseController;

class MedicalStoreApi extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Serves the React PWA App entry point at /MedicalStore/{slug} or /app/medical-store
     */
    public function pwaIndex($slug = null)
    {
        $pwaPath = FCPATH . 'App/MedicalStore/index.html';
        if (file_exists($pwaPath)) {
            $html = file_get_contents($pwaPath);
            if ($slug !== null) {
                $cleanSlug = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
                $html = str_replace(
                    '<head>',
                    "<head>\n    <script>window.INITIAL_STORE_SLUG = \"{$cleanSlug}\";</script>",
                    $html
                );
            }
            return $this->response->setBody($html);
        }

        return $this->response->setJSON([
            'app_name' => 'Medical Store & Multi-Building Pharmacy System',
            'status' => 'ready',
            'api_base_url' => base_url('api/v1/medical-store/'),
            'message' => 'Medical Store PWA directory active. REST API ready.'
        ]);
    }

    // =========================================================================
    // 1. STORE & COUNTER MANAGEMENT
    // =========================================================================

    public function stores()
    {
        $slug = trim($this->request->getGet('slug') ?? '');
        $builder = $this->db->table('mst_stores');
        $builder->where('is_active', 1);

        if (!empty($slug)) {
            $builder->groupStart()
                ->where('store_slug', $slug)
                ->orWhere('store_code', $slug)
                ->groupEnd();
        }

        $stores = $builder->orderBy('is_main_store', 'DESC')->get()->getResultArray();
        return $this->response->setJSON(['status' => 1, 'stores' => $stores]);
    }


    public function saveStore()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        if (empty($json['store_name']) || empty($json['store_code'])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Store Name and Store Code are required.']);
        }

        $storeId = !empty($json['store_id']) ? (int)$json['store_id'] : 0;
        $data = [
            'store_code'                 => trim($json['store_code']),
            'store_name'                 => trim($json['store_name']),
            'building_name'              => trim($json['building_name'] ?? ''),
            'floor_no'                   => trim($json['floor_no'] ?? ''),
            'room_no'                    => trim($json['room_no'] ?? ''),
            'is_main_store'              => !empty($json['is_main_store']) ? 1 : 0,
            'drug_license_no_20b'        => trim($json['drug_license_no_20b'] ?? ''),
            'drug_license_no_21b'        => trim($json['drug_license_no_21b'] ?? ''),
            'drug_license_no_20f_x'      => trim($json['drug_license_no_20f_x'] ?? ''),
            'gstin'                      => strtoupper(trim($json['gstin'] ?? '')),
            'pan_no'                     => strtoupper(trim($json['pan_no'] ?? '')),
            'fssai_no'                   => trim($json['fssai_no'] ?? ''),
            'state_code'                 => trim($json['state_code'] ?? '07'),
            'state_name'                 => trim($json['state_name'] ?? 'Delhi'),
            'registered_pharmacist_name' => trim($json['registered_pharmacist_name'] ?? ''),
            'pharmacist_reg_no'          => trim($json['pharmacist_reg_no'] ?? ''),
            'contact_phone'              => trim($json['contact_phone'] ?? ''),
            'contact_email'              => trim($json['contact_email'] ?? ''),
            'address'                    => trim($json['address'] ?? ''),
            'invoice_prefix'             => trim($json['invoice_prefix'] ?? 'INV/'),
            'next_invoice_no'            => !empty($json['next_invoice_no']) ? (int)$json['next_invoice_no'] : 1001,
            'bank_name'                  => trim($json['bank_name'] ?? ''),
            'bank_account_no'            => trim($json['bank_account_no'] ?? ''),
            'bank_ifsc'                  => trim($json['bank_ifsc'] ?? ''),
            'upi_id'                     => trim($json['upi_id'] ?? ''),
            'abdm_hfr_id'                => trim($json['abdm_hfr_id'] ?? ''),
            'abdm_hip_id'                => trim($json['abdm_hip_id'] ?? ''),
            'pharmacist_hpr_id'          => trim($json['pharmacist_hpr_id'] ?? ''),
            'terms_conditions'           => trim($json['terms_conditions'] ?? ''),
            'is_active'                  => isset($json['is_active']) ? (int)$json['is_active'] : 1,
        ];

        $table = $this->db->table('mst_stores');
        if ($storeId > 0) {
            $table->where('store_id', $storeId)->update($data);
            $msg = 'Store details updated successfully.';
        } else {
            $table->insert($data);
            $storeId = $this->db->insertID();
            $msg = 'New pharmacy store created successfully.';
        }

        return $this->response->setJSON(['status' => 1, 'message' => $msg, 'store_id' => $storeId]);
    }

    // =========================================================================
    // 2. CORE HMS PATIENT INTEGRATION (UHID, IPD, OPD, DOCTOR PRESCRIPTIONS)
    // =========================================================================

    public function searchPatient()
    {
        $q = trim($this->request->getGet('q') ?? '');
        if (strlen($q) < 2) {
            return $this->response->setJSON(['status' => 1, 'patients' => []]);
        }

        // Search patient_master by UHID, Mobile, Name, ABHA ID, ABHA Address
        $builder = $this->db->table('patient_master p');
        $builder->select('p.id as patient_id, p.p_code as uhid, p.p_fname, p.p_lname, p.title, p.mphone1, p.gender, p.age, p.dob, p.add1, p.city, p.abha_id, p.abha_address, p.abha_verified_status, p.abha_kyc_verified');
        $builder->groupStart()
            ->like('p.p_code', $q)
            ->orLike('p.mphone1', $q)
            ->orLike('p.p_fname', $q)
            ->orLike('p.p_lname', $q)
            ->orLike("CONCAT(p.p_fname, ' ', p.p_lname)", $q)
            ->orLike('p.abha_id', $q)
            ->orLike('p.abha_address', $q)
            ->groupEnd();
        $patients = $builder->limit(15)->get()->getResultArray();

        $enriched = [];
        foreach ($patients as $pt) {
            $pId = (int)$pt['patient_id'];
            $genderStr = ((int)$pt['gender'] === 1) ? 'Male' : (((int)$pt['gender'] === 2) ? 'Female' : 'Other');
            $fullName = trim(($pt['title'] ? $pt['title'] . ' ' : '') . $pt['p_fname'] . ' ' . ($pt['p_lname'] ?? ''));

            // Check Active OPD for today
            $opdRow = $this->db->table('opd_master')
                ->where('p_id', $pId)
                ->orderBy('opd_id', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            // Check Active IPD Admission
            $ipdRow = $this->db->table('ipd_master')
                ->where('p_id', $pId)
                ->groupStart()
                    ->where('discharge_date IS NULL')
                    ->orWhere('discharge_date', '0000-00-00')
                ->groupEnd()
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $bedInfo = null;
            if ($ipdRow) {
                if (!empty($ipdRow['bed_no'])) {
                    $bedInfo = [
                        'bed_id' => $ipdRow['bed_no'],
                        'bed_no' => $ipdRow['bed_no'],
                        'ward_name' => 'Hospital Ward'
                    ];
                }
            }

            $enriched[] = [
                'patient_id'    => $pId,
                'uhid'          => $pt['uhid'] ?? '',
                'full_name'     => $fullName,
                'mobile'        => $pt['mphone1'] ?? '',
                'gender'        => $genderStr,
                'age'           => $pt['age'] ? $pt['age'] . ' Y' : 'N/A',
                'abha_id'              => $pt['abha_id'] ?? '',
                'abha_address'         => $pt['abha_address'] ?? '',
                'is_abha_linked'       => (!empty($pt['abha_id']) || !empty($pt['abha_address'])),
                'abha_verified_status' => $pt['abha_verified_status'] ?? '',
                'abha_kyc_verified'    => (int)($pt['abha_kyc_verified'] ?? 0),
                'has_active_opd'=> !empty($opdRow),
                'active_opd'    => $opdRow ? [
                    'opd_id'      => (int)$opdRow['opd_id'],
                    'opd_code'    => $opdRow['opd_code'] ?? '',
                    'doctor_id'   => (int)($opdRow['doc_id'] ?? 0),
                    'doctor_name' => $opdRow['doc_name'] ?? '',
                    'visit_date'  => $opdRow['opd_book_date'] ?? ''
                ] : null,
                'has_active_ipd'=> !empty($ipdRow),
                'active_ipd'    => $ipdRow ? [
                    'ipd_id'      => (int)$ipdRow['id'],
                    'ipd_code'    => $ipdRow['ipd_code'] ?? '',
                    'doctor_id'   => (int)($ipdRow['r_doc_id'] ?? 0),
                    'doctor_name' => $ipdRow['r_doc_name'] ?? '',
                    'admit_date'  => $ipdRow['register_date'] ?? '',
                    'bed_info'    => $bedInfo
                ] : null,
            ];
        }

        return $this->response->setJSON(['status' => 1, 'patients' => $enriched]);
    }

    public function getPrescription(string $encounterType, int $encounterId)
    {
        $medicines = [];
        $encounterType = strtolower($encounterType);

        if ($encounterType === 'opd') {
            // Fetch medicines from opd_prescription or related tables
            $prescRows = $this->db->table('opd_prescription')
                ->where('opd_id', $encounterId)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($prescRows as $pr) {
                $medName = $pr['medicine_name'] ?? ($pr['item_name'] ?? '');
                if (!empty($medName)) {
                    $medicines[] = [
                        'medicine_name' => $medName,
                        'dosage'        => $pr['dosage'] ?? ($pr['dose'] ?? '1 Tab'),
                        'frequency'     => $pr['dosage_freq_str'] ?? ($pr['frequency'] ?? '1-0-1'),
                        'duration'      => $pr['no_of_days'] ?? ($pr['duration'] ?? '5 Days'),
                        'instructions'  => $pr['remark'] ?? ($pr['instructions'] ?? 'After meals'),
                        'prescribed_qty'=> (int)($pr['qty'] ?? 10)
                    ];
                }
            }
        } elseif ($encounterType === 'ipd') {
            // Check IPD treatment chart / nursing entries
            $nursingMeds = $this->db->table('ipd_nursing_entries')
                ->where('ipd_id', $encounterId)
                ->where('entry_type', 'medication')
                ->orderBy('id', 'DESC')
                ->limit(20)
                ->get()
                ->getResultArray();

            foreach ($nursingMeds as $nm) {
                $data = json_decode($nm['entry_data'] ?? '{}', true);
                if (!empty($data['medicine_name'])) {
                    $medicines[] = [
                        'medicine_name' => $data['medicine_name'],
                        'dosage'        => $data['dosage'] ?? '1 Tab',
                        'frequency'     => $data['frequency'] ?? 'Stat',
                        'duration'      => 'As directed',
                        'instructions'  => $data['notes'] ?? '',
                        'prescribed_qty'=> (int)($data['qty'] ?? 5)
                    ];
                }
            }
        }

        return $this->response->setJSON([
            'status' => 1,
            'encounter_type' => $encounterType,
            'encounter_id' => $encounterId,
            'medicines' => $medicines
        ]);
    }

    // =========================================================================
    // 3. ITEM MASTER, BATCHES & STOCK MANAGEMENT
    // =========================================================================

    public function searchItems()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $q = trim($this->request->getGet('q') ?? '');

        $builder = $this->db->table('mst_items i');
        $builder->select('i.item_id, i.item_name, i.generic_name, i.category, i.hsn_code, i.gst_rate, i.unit_pack, i.units_per_pack, i.drug_schedule, i.manufacturer_name, i.barcode');
        $builder->where('i.is_active', 1);

        if (!empty($q)) {
            $builder->groupStart()
                ->like('i.item_name', $q)
                ->orLike('i.generic_name', $q)
                ->orLike('i.barcode', $q)
                ->groupEnd();
        }

        $items = $builder->limit(30)->get()->getResultArray();
        $today = date('Y-m-d');
        $nearExpiryThreshold = date('Y-m-d', strtotime('+90 days'));

        $enriched = [];
        foreach ($items as $it) {
            $itemId = (int)$it['item_id'];

            // Fetch batches in stock for this store, ordered by FEFO (expiry_date ASC)
            $batches = $this->db->table('mst_batches b')
                ->select('b.batch_id, b.batch_no, b.expiry_date, b.mrp, b.ptr, b.purchase_rate_net, b.gst_rate, s.current_qty')
                ->join('mst_stock s', 's.batch_id = b.batch_id AND s.store_id = ' . $storeId, 'inner')
                ->where('b.item_id', $itemId)
                ->where('b.store_id', $storeId)
                ->where('s.current_qty >', 0)
                ->orderBy('b.expiry_date', 'ASC')
                ->get()
                ->getResultArray();

            $batchList = [];
            $totalStock = 0;
            foreach ($batches as $b) {
                $isExpired = ($b['expiry_date'] <= $today);
                $isNearExpiry = (!$isExpired && $b['expiry_date'] <= $nearExpiryThreshold);
                $totalStock += (int)$b['current_qty'];

                $batchList[] = [
                    'batch_id'         => (int)$b['batch_id'],
                    'batch_no'         => $b['batch_no'],
                    'expiry_date'      => $b['expiry_date'],
                    'expiry_display'   => date('m/Y', strtotime($b['expiry_date'])),
                    'mrp'              => (float)$b['mrp'],
                    'ptr'              => (float)$b['ptr'],
                    'gst_rate'         => (float)$b['gst_rate'],
                    'current_qty'      => (int)$b['current_qty'],
                    'is_expired'       => $isExpired,
                    'is_near_expiry'   => $isNearExpiry
                ];
            }

            $it['batches'] = $batchList;
            $it['total_stock'] = $totalStock;
            // Best FEFO batch: first non-expired batch with stock
            $fefoBatch = null;
            foreach ($batchList as $b) {
                if (!$b['is_expired']) {
                    $fefoBatch = $b;
                    break;
                }
            }
            $it['fefo_batch'] = $fefoBatch;
            $enriched[] = $it;
        }

        return $this->response->setJSON(['status' => 1, 'items' => $enriched]);
    }

    public function saveOpeningStock()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $storeId = (int)($json['store_id'] ?? 1);
        $items = $json['items'] ?? [];

        if (empty($items)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'No items provided for opening stock.']);
        }

        $this->db->transStart();
        $totalOpeningValue = 0;

        foreach ($items as $row) {
            $itemId = (int)($row['item_id'] ?? 0);
            $batchNo = trim($row['batch_no'] ?? 'OP-01');
            $expiryDate = trim($row['expiry_date'] ?? date('Y-12-31', strtotime('+1 year')));
            $qty = (int)($row['qty'] ?? 0);
            $mrp = (float)($row['mrp'] ?? 0);
            $ptr = (float)($row['ptr'] ?? ($mrp * 0.75));
            $gstRate = (float)($row['gst_rate'] ?? 12.00);

            if ($itemId <= 0 || $qty <= 0) continue;

            // Check or insert batch
            $batchRow = $this->db->table('mst_batches')
                ->where('store_id', $storeId)
                ->where('item_id', $itemId)
                ->where('batch_no', $batchNo)
                ->get()
                ->getRowArray();

            if ($batchRow) {
                $batchId = (int)$batchRow['batch_id'];
            } else {
                $this->db->table('mst_batches')->insert([
                    'store_id'          => $storeId,
                    'item_id'           => $itemId,
                    'batch_no'          => $batchNo,
                    'mfg_date'          => date('Y-01-01'),
                    'expiry_date'       => $expiryDate,
                    'mrp'               => $mrp,
                    'ptr'               => $ptr,
                    'purchase_rate_net' => $ptr * (1 + $gstRate / 100),
                    'gst_rate'          => $gstRate,
                ]);
                $batchId = $this->db->insertID();
            }

            // Update or insert stock
            $stockRow = $this->db->table('mst_stock')
                ->where('store_id', $storeId)
                ->where('item_id', $itemId)
                ->where('batch_id', $batchId)
                ->get()
                ->getRowArray();

            if ($stockRow) {
                $this->db->table('mst_stock')
                    ->where('stock_id', $stockRow['stock_id'])
                    ->update(['current_qty' => $stockRow['current_qty'] + $qty]);
            } else {
                $this->db->table('mst_stock')->insert([
                    'store_id'    => $storeId,
                    'item_id'     => $itemId,
                    'batch_id'    => $batchId,
                    'current_qty' => $qty
                ]);
            }

            $lineVal = $qty * $ptr;
            $totalOpeningValue += $lineVal;

            // Log in stock audit
            $this->db->table('mst_stock_audit')->insert([
                'store_id'       => $storeId,
                'item_id'        => $itemId,
                'batch_id'       => $batchId,
                'audit_type'     => 'OPENING_STOCK',
                'system_qty'     => 0,
                'physical_qty'   => $qty,
                'variation_qty'  => $qty,
                'rate'           => $ptr,
                'total_value'    => $lineVal,
                'remarks'        => 'Opening Stock Onboarding'
            ]);
        }

        // Post Journal Ledger Entry: Dr Stock in Hand, Cr Opening Stock Capital
        if ($totalOpeningValue > 0) {
            $voucherNo = 'OPN-' . $storeId . '-' . date('YmdHis');
            $stockHead = $this->db->table('mst_account_heads')->where('head_code', '1003')->get()->getRowArray();
            $equityHead = $this->db->table('mst_account_heads')->where('head_code', '3004')->get()->getRowArray();

            if ($stockHead && $equityHead) {
                $this->db->table('mst_ledger_entries')->insert([
                    'store_id'        => $storeId,
                    'voucher_no'      => $voucherNo,
                    'voucher_type'    => 'OPENING_STOCK',
                    'voucher_date'    => date('Y-m-d'),
                    'account_head_id' => $stockHead['head_id'],
                    'debit_amount'    => $totalOpeningValue,
                    'credit_amount'   => 0,
                    'narration'       => 'Opening Stock Inward Valuation'
                ]);
                $this->db->table('mst_ledger_entries')->insert([
                    'store_id'        => $storeId,
                    'voucher_no'      => $voucherNo,
                    'voucher_type'    => 'OPENING_STOCK',
                    'voucher_date'    => date('Y-m-d'),
                    'account_head_id' => $equityHead['head_id'],
                    'debit_amount'    => 0,
                    'credit_amount'   => $totalOpeningValue,
                    'narration'       => 'Opening Stock Capital Credit'
                ]);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to save opening stock.']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Opening stock saved successfully.',
            'total_valuation' => $totalOpeningValue
        ]);
    }

    public function stockList()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $filter = trim($this->request->getGet('filter') ?? 'all'); // all, low, near_expiry, expired

        $builder = $this->db->table('mst_stock s');
        $builder->select('s.stock_id, s.current_qty, s.last_updated_at, i.item_id, i.item_name, i.generic_name, i.category, i.hsn_code, i.drug_schedule, i.min_reorder_level, b.batch_id, b.batch_no, b.expiry_date, b.mrp, b.ptr, b.gst_rate');
        $builder->join('mst_items i', 'i.item_id = s.item_id', 'inner');
        $builder->join('mst_batches b', 'b.batch_id = s.batch_id', 'inner');
        $builder->where('s.store_id', $storeId);

        $today = date('Y-m-d');
        $nearThreshold = date('Y-m-d', strtotime('+90 days'));

        if ($filter === 'expired') {
            $builder->where('b.expiry_date <=', $today);
        } elseif ($filter === 'near_expiry') {
            $builder->where('b.expiry_date >', $today)->where('b.expiry_date <=', $nearThreshold);
        } elseif ($filter === 'low') {
            $builder->where('s.current_qty <= i.min_reorder_level');
        }

        $stockRows = $builder->orderBy('i.item_name', 'ASC')->orderBy('b.expiry_date', 'ASC')->get()->getResultArray();

        $totalCostValuation = 0;
        $totalMrpValuation = 0;
        $totalItems = count($stockRows);

        foreach ($stockRows as &$row) {
            $qty = (int)$row['current_qty'];
            $ptr = (float)$row['ptr'];
            $mrp = (float)$row['mrp'];
            $costVal = $qty * $ptr;
            $mrpVal = $qty * $mrp;

            $totalCostValuation += $costVal;
            $totalMrpValuation += $mrpVal;

            $row['cost_valuation'] = round($costVal, 2);
            $row['mrp_valuation'] = round($mrpVal, 2);
            $row['is_expired'] = ($row['expiry_date'] <= $today);
            $row['is_near_expiry'] = (!$row['is_expired'] && $row['expiry_date'] <= $nearThreshold);
            $row['expiry_display'] = date('m/Y', strtotime($row['expiry_date']));
        }

        return $this->response->setJSON([
            'status' => 1,
            'store_id' => $storeId,
            'filter' => $filter,
            'summary' => [
                'total_rows' => $totalItems,
                'total_cost_valuation' => round($totalCostValuation, 2),
                'total_mrp_valuation' => round($totalMrpValuation, 2)
            ],
            'stock' => $stockRows
        ]);
    }

    public function stockAlerts()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $today = date('Y-m-d');
        $near30 = date('Y-m-d', strtotime('+30 days'));
        $near60 = date('Y-m-d', strtotime('+60 days'));
        $near90 = date('Y-m-d', strtotime('+90 days'));

        $expired = $this->db->table('mst_stock s')
            ->select('s.current_qty, i.item_name, b.batch_no, b.expiry_date, b.mrp')
            ->join('mst_items i', 'i.item_id = s.item_id')
            ->join('mst_batches b', 'b.batch_id = s.batch_id')
            ->where('s.store_id', $storeId)
            ->where('s.current_qty >', 0)
            ->where('b.expiry_date <=', $today)
            ->get()->getResultArray();

        $nearExpiry = $this->db->table('mst_stock s')
            ->select('s.current_qty, i.item_name, b.batch_no, b.expiry_date, b.mrp')
            ->join('mst_items i', 'i.item_id = s.item_id')
            ->join('mst_batches b', 'b.batch_id = s.batch_id')
            ->where('s.store_id', $storeId)
            ->where('s.current_qty >', 0)
            ->where('b.expiry_date >', $today)
            ->where('b.expiry_date <=', $near90)
            ->orderBy('b.expiry_date', 'ASC')
            ->get()->getResultArray();

        $lowStock = $this->db->table('mst_stock s')
            ->select('s.current_qty, i.item_name, i.min_reorder_level')
            ->join('mst_items i', 'i.item_id = s.item_id')
            ->where('s.store_id', $storeId)
            ->where('s.current_qty <= i.min_reorder_level')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'alerts' => [
                'expired_count' => count($expired),
                'expired_items' => $expired,
                'near_expiry_count' => count($nearExpiry),
                'near_expiry_items' => $nearExpiry,
                'low_stock_count' => count($lowStock),
                'low_stock_items' => $lowStock
            ]
        ]);
    }

    // =========================================================================
    // 4. POS BILLING & DISPENSING
    // =========================================================================

    public function saveSale()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $storeId = (int)($json['store_id'] ?? 1);
        $items = $json['items'] ?? [];

        if (empty($items)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Cart is empty.']);
        }

        // Fetch Store Config
        $store = $this->db->table('mst_stores')->where('store_id', $storeId)->get()->getRowArray();
        if (!$store) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Pharmacy store not found.']);
        }

        $this->db->transStart();

        // Generate sequential invoice number
        $prefix = $store['invoice_prefix'] ?: 'INV/';
        $seq = (int)$store['next_invoice_no'];
        $invoiceNo = $prefix . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);

        // Increment sequence in store
        $this->db->table('mst_stores')->where('store_id', $storeId)->update(['next_invoice_no' => $seq + 1]);

        $wholeDiscType = trim((string)($json['whole_discount_type'] ?? 'pct')); // 'pct' or 'val'
        $wholeDiscVal = (float)($json['whole_discount_val'] ?? 0);

        $grossAmount = 0;
        $itemDiscountTotal = 0;
        $hasScheduleH1 = 0;
        $today = date('Y-m-d');
        $preItems = [];

        foreach ($items as $item) {
            $itemId = (int)$item['item_id'];
            $batchId = (int)$item['batch_id'];
            $rawQty = (int)($item['qty'] ?? 1);

            // Fetch batch & check expiry and stock
            $batch = $this->db->table('mst_batches b')
                ->select('b.*, i.item_name, i.drug_schedule, i.hsn_code, i.unit_pack, i.units_per_pack, i.snomed_ct_code, i.snomed_display, s.current_qty')
                ->join('mst_items i', 'i.item_id = b.item_id')
                ->join('mst_stock s', 's.batch_id = b.batch_id AND s.store_id = ' . $storeId)
                ->where('b.batch_id', $batchId)
                ->where('b.store_id', $storeId)
                ->get()
                ->getRowArray();

            if (!$batch) {
                $this->db->transRollback();
                return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => "Batch not found for item #$itemId."]);
            }

            // Strictly prevent dispensing expired drugs (Indian Drugs & Cosmetics Act)
            if ($batch['expiry_date'] <= $today) {
                $this->db->transRollback();
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 0,
                    'message' => "STATUTORY BLOCK: Medicine '{$batch['item_name']}' (Batch {$batch['batch_no']}) expired on " . date('d-m-Y', strtotime($batch['expiry_date'])) . ". Cannot be sold!"
                ]);
            }

            $unitsPerPack = (int)($batch['units_per_pack'] ?? 1);
            if ($unitsPerPack <= 0) $unitsPerPack = 1;

            $sellUnit = trim((string)($item['sell_unit'] ?? 'Strip'));
            $mrp = (float)$batch['mrp']; // Strip MRP
            $perUnitMrp = $unitsPerPack > 0 ? ($mrp / $unitsPerPack) : $mrp;

            if ($unitsPerPack <= 1) {
                $totalUnits = max(1, (int)($item['qty'] ?? 1));
                $dispQty = $totalUnits;
                $lineGross = round($totalUnits * $mrp, 2);
                $effectiveUnitPrice = $mrp;
                $packQty = (float)$totalUnits;
                $looseQty = 0;
                $sellUnit = $batch['unit_pack'] ?: 'Unit';
            } elseif ($sellUnit === 'Unit' || $sellUnit === 'Tablet' || $sellUnit === 'Loose') {
                $totalUnits = max(1, (int)($item['loose_qty'] ?? $item['qty'] ?? 1));
                $dispQty = $totalUnits;
                $lineGross = round($totalUnits * $perUnitMrp, 2);
                $effectiveUnitPrice = round($perUnitMrp, 2);
                $packQty = round($totalUnits / $unitsPerPack, 2);
                $looseQty = $totalUnits;
                $sellUnit = 'Tablet';
            } elseif ($sellUnit === 'Combo') {
                $sq = max(0, (int)($item['strip_qty'] ?? 0));
                $lq = max(0, (int)($item['loose_qty'] ?? 0));
                $totalUnits = ($sq * $unitsPerPack) + $lq;
                if ($totalUnits <= 0) $totalUnits = 1;
                $dispQty = $sq;
                $lineGross = round(($sq * $mrp) + ($lq * $perUnitMrp), 2);
                $effectiveUnitPrice = $mrp;
                $packQty = round($totalUnits / $unitsPerPack, 2);
                $looseQty = $lq;
            } else { // 'Strip'
                $sq = max(1, (int)($item['strip_qty'] ?? $item['qty'] ?? 1));
                $totalUnits = $sq * $unitsPerPack;
                $dispQty = $sq;
                $lineGross = round($sq * $mrp, 2);
                $effectiveUnitPrice = $mrp;
                $packQty = (float)$sq;
                $looseQty = 0;
                $sellUnit = 'Strip';
            }

            // Check stock availability (current_qty is in total units/tablets)
            if ((int)$batch['current_qty'] < $totalUnits) {
                $this->db->transRollback();
                $availStrips = floor((int)$batch['current_qty'] / $unitsPerPack);
                $availLoose = (int)$batch['current_qty'] % $unitsPerPack;
                $stockDetail = $unitsPerPack > 1 ? " ({$availStrips} strips, {$availLoose} tabs)" : "";
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 0,
                    'message' => "Insufficient stock for '{$batch['item_name']}'. In stock: {$batch['current_qty']} units{$stockDetail}, Requested: $totalUnits units."
                ]);
            }

            if ($batch['drug_schedule'] === 'Schedule H1') {
                $hasScheduleH1 = 1;
            }

            $itemDiscType = trim((string)($item['discount_type'] ?? 'pct')); // 'pct' or 'val'
            $itemDiscVal = (float)($item['discount_val'] ?? ($item['discount_pct'] ?? 0));

            if ($itemDiscType === 'val') {
                $lineItemDisc = min($lineGross, max(0, $itemDiscVal));
                $discPct = $lineGross > 0 ? round(($lineItemDisc / $lineGross) * 100, 2) : 0;
            } else {
                $discPct = min(100, max(0, $itemDiscVal));
                $lineItemDisc = round(($lineGross * $discPct) / 100, 2);
            }

            $grossAmount += $lineGross;
            $itemDiscountTotal += $lineItemDisc;

            $preItems[] = [
                'itemId'             => $itemId,
                'batchId'            => $batchId,
                'batch'              => $batch,
                'qty'                => $dispQty,
                'sellUnit'           => $sellUnit,
                'unitsPerPack'       => $unitsPerPack,
                'totalUnits'         => $totalUnits,
                'effectiveUnitPrice' => $effectiveUnitPrice,
                'packQty'            => $packQty,
                'looseQty'           => $looseQty,
                'mrp'                => $mrp,
                'lineGross'          => $lineGross,
                'itemDiscType'       => $itemDiscType,
                'itemDiscVal'        => $itemDiscVal,
                'itemDiscPct'        => $discPct,
                'lineItemDisc'       => $lineItemDisc,
                'netAfterItem'       => $lineGross - $lineItemDisc
            ];
        }

        // Calculate whole bill discount (by percentage or by value)
        $balanceAfterItemDisc = max(0, $grossAmount - $itemDiscountTotal);
        $wholeDiscountAmount = 0;
        if ($wholeDiscVal > 0) {
            if ($wholeDiscType === 'val') {
                $wholeDiscountAmount = min($balanceAfterItemDisc, $wholeDiscVal);
            } else {
                $wholeDiscountAmount = round(($balanceAfterItemDisc * min(100, $wholeDiscVal)) / 100, 2);
            }
        }

        $totalDiscount = $itemDiscountTotal + $wholeDiscountAmount;

        // Second pass: distribute whole-bill discount proportionally across lines and compute GST
        $isInterState = (!empty($json['patient_state_code']) && $json['patient_state_code'] !== $store['state_code']);
        $validatedItems = [];
        $taxableTotal = 0;
        $cgstTotal = 0;
        $sgstTotal = 0;
        $igstTotal = 0;

        foreach ($preItems as $pi) {
            $batch = $pi['batch'];
            $lineGross = $pi['lineGross'];
            $itemDisc = $pi['lineItemDisc'];

            // Proportional share of whole bill discount
            $allocatedWholeDisc = 0;
            if ($wholeDiscountAmount > 0 && $balanceAfterItemDisc > 0) {
                $lineWeight = $pi['netAfterItem'] / $balanceAfterItemDisc;
                $allocatedWholeDisc = round($wholeDiscountAmount * $lineWeight, 2);
            }

            $totalLineDisc = min($lineGross, $itemDisc + $allocatedWholeDisc);
            $lineNet = round($lineGross - $totalLineDisc, 2);
            $effectiveDiscPct = $lineGross > 0 ? round(($totalLineDisc / $lineGross) * 100, 2) : 0;

            // Compute Back-Calculated GST from line net
            $gstRate = (float)$batch['gst_rate'];
            if ($gstRate > 0) {
                $taxableVal = round($lineNet / (1 + ($gstRate / 100)), 2);
                $gstAmt = round($lineNet - $taxableVal, 2);
            } else {
                $taxableVal = $lineNet;
                $gstAmt = 0;
            }

            if ($isInterState) {
                $cgst = 0;
                $sgst = 0;
                $igst = $gstAmt;
            } else {
                $cgst = round($gstAmt / 2, 2);
                $sgst = round($gstAmt - $cgst, 2);
                $igst = 0;
            }

            $taxableTotal += $taxableVal;
            $cgstTotal += $cgst;
            $sgstTotal += $sgst;
            $igstTotal += $igst;

            $validatedItems[] = [
                'item_id'         => $pi['itemId'],
                'item_name'       => $batch['item_name'],
                'unit_pack'       => $batch['unit_pack'] ?? 'Units',
                'snomed_ct_code'  => $batch['snomed_ct_code'] ?? '',
                'snomed_display'  => $batch['snomed_display'] ?? '',
                'batch_id'        => $pi['batchId'],
                'batch_no'        => $batch['batch_no'],
                'expiry_date'     => $batch['expiry_date'],
                'qty'             => $pi['qty'],
                'sell_unit'       => $pi['sellUnit'],
                'units_per_pack'  => $pi['unitsPerPack'],
                'total_units'     => $pi['totalUnits'],
                'unit_price'      => $pi['effectiveUnitPrice'],
                'loose_qty'       => $pi['looseQty'],
                'pack_qty'        => $pi['packQty'],
                'unit_mrp'        => $pi['mrp'],
                'discount_type'   => $pi['itemDiscType'],
                'discount_val'    => $pi['itemDiscVal'],
                'discount_pct'    => $effectiveDiscPct,
                'discount_amount' => $totalLineDisc,
                'hsn_code'        => $batch['hsn_code'] ?: '3004',
                'gst_rate'        => $gstRate,
                'taxable_value'   => $taxableVal,
                'cgst_amount'     => $cgst,
                'sgst_amount'     => $sgst,
                'igst_amount'     => $igst,
                'total_amount'    => $lineNet
            ];

            // Decrement Stock by exact totalUnits (tablets/units)
            $this->db->table('mst_stock')
                ->where('store_id', $storeId)
                ->where('batch_id', $pi['batchId'])
                ->set('current_qty', 'current_qty - ' . $pi['totalUnits'], false)
                ->update();
        }

        $netBeforeRound = $grossAmount - $totalDiscount;
        $netRounded = round($netBeforeRound);
        $roundOff = round($netRounded - $netBeforeRound, 2);

        $paymentMode = trim($json['payment_mode'] ?? 'Cash'); // Cash, UPI, Card, Mixed, IPD_Credit
        $cashPaid = (float)($json['cash_paid'] ?? ($paymentMode === 'Cash' ? $netRounded : 0));
        $upiPaid = (float)($json['upi_paid'] ?? ($paymentMode === 'UPI' ? $netRounded : 0));
        $cardPaid = (float)($json['card_paid'] ?? ($paymentMode === 'Card' ? $netRounded : 0));
        $creditAmount = (float)($json['credit_amount'] ?? ($paymentMode === 'IPD_Credit' ? $netRounded : 0));
        $bankName = trim($json['bank_name'] ?? '');
        $upiRefNo = trim($json['upi_ref_no'] ?? '');
        $cardRefNo = trim($json['card_ref_no'] ?? '');

        // ABDM Integration: Extract ABHA ID and ABHA Address
        $patientId = !empty($json['patient_id']) ? (int)$json['patient_id'] : null;
        $abhaId = trim((string)($json['abha_id'] ?? ''));
        $abhaAddress = trim((string)($json['abha_address'] ?? ''));

        // If not in payload but patient_id exists, look up patient_master
        if (($abhaId === '' || $abhaAddress === '') && $patientId > 0) {
            $pRow = $this->db->table('patient_master')
                ->select('abha_id, abha_address')
                ->where('id', $patientId)
                ->get()
                ->getRowArray();
            if ($pRow) {
                if ($abhaId === '' && !empty($pRow['abha_id'])) $abhaId = $pRow['abha_id'];
                if ($abhaAddress === '' && !empty($pRow['abha_address'])) $abhaAddress = $pRow['abha_address'];
            }
        }

        // Generate unique ABDM Care Context Reference (e.g. PHARM-STMAIN-INV262700001)
        $cleanPrefix = preg_replace('/[^A-Za-z0-9]/', '', $store['store_code'] ?: 'STORE');
        $cleanInv = preg_replace('/[^A-Za-z0-9]/', '', $invoiceNo);
        $careContextRef = "PHARM-{$cleanPrefix}-{$cleanInv}";
        $careContextDisplay = "Pharmacy Dispensation - {$invoiceNo}";

        // Build official ABDM FHIR R4 MedicationDispense Document Bundle
        $fhirBuilder = new \App\Libraries\FhirR4Builder();
        $patientData = [
            'id'           => $patientId ?: 0,
            'uhid'         => $json['uhid'] ?? '',
            'p_code'       => $json['uhid'] ?? '',
            'name'         => trim($json['patient_name'] ?? 'Patient'),
            'p_fname'      => trim($json['patient_name'] ?? 'Patient'),
            'phone'        => trim($json['patient_mobile'] ?? ''),
            'mphone1'      => trim($json['patient_mobile'] ?? ''),
            'gender'       => trim($json['gender'] ?? 'Male'),
            'age'          => trim($json['age'] ?? '30'),
            'abha_id'      => $abhaId,
            'abha_address' => $abhaAddress
        ];

        $fhirBundle = $fhirBuilder->buildPharmacyDispenseBundle($patientData, $store, [
            'doctor_name'            => trim($json['doctor_name'] ?? ''),
            'doctor_reg_no'          => trim($json['doctor_reg_no'] ?? ''),
            'patient_name'           => trim($json['patient_name'] ?? 'Patient'),
            'abha_id'                => $abhaId,
            'abha_address'           => $abhaAddress,
            'abdm_care_context_ref'  => $careContextRef,
            'invoice_no'             => $invoiceNo
        ], $validatedItems);

        $fhirBundleJson = json_encode($fhirBundle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Create Sale Record
        $saleData = [
            'store_id'                   => $storeId,
            'invoice_no'                 => $invoiceNo,
            'sale_date'                  => date('Y-m-d H:i:s'),
            'patient_type'               => $json['patient_type'] ?? 'Walk-in',
            'uhid'                       => $json['uhid'] ?? null,
            'patient_id'                 => $patientId,
            'opd_id'                     => !empty($json['opd_id']) ? (int)$json['opd_id'] : null,
            'ipd_id'                     => !empty($json['ipd_id']) ? (int)$json['ipd_id'] : null,
            'patient_name'               => trim($json['patient_name'] ?? 'Walk-in Customer'),
            'patient_mobile'             => trim($json['patient_mobile'] ?? ''),
            'patient_address'            => trim($json['patient_address'] ?? ''),
            'age'                        => trim($json['age'] ?? ''),
            'gender'                     => trim($json['gender'] ?? ''),
            'doctor_id'                  => !empty($json['doctor_id']) ? (int)$json['doctor_id'] : null,
            'doctor_name'                => trim($json['doctor_name'] ?? ''),
            'doctor_reg_no'              => trim($json['doctor_reg_no'] ?? ''),
            'ward_name'                  => trim($json['ward_name'] ?? ''),
            'bed_no'                     => trim($json['bed_no'] ?? ''),
            'gross_amount'               => $grossAmount,
            'discount_amount'            => $totalDiscount,
            'bill_discount_type'         => $wholeDiscType,
            'bill_discount_val'          => $wholeDiscVal,
            'taxable_amount'             => $taxableTotal,
            'cgst_amount'                => $cgstTotal,
            'sgst_amount'                => $sgstTotal,
            'igst_amount'                => $igstTotal,
            'round_off'                  => $roundOff,
            'net_amount'                 => $netRounded,
            'payment_mode'               => $paymentMode,
            'cash_paid'                  => $cashPaid,
            'upi_paid'                   => $upiPaid,
            'card_paid'                  => $cardPaid,
            'credit_amount'              => $creditAmount,
            'payment_reference'          => trim($json['payment_reference'] ?? ''),
            'bank_name'                  => $bankName,
            'upi_ref_no'                 => $upiRefNo,
            'card_ref_no'                => $cardRefNo,
            'is_bank_reconciled'         => 0,
            'schedule_h1_flag'           => $hasScheduleH1,
            'abha_id'                    => $abhaId ?: null,
            'abha_address'               => $abhaAddress ?: null,
            'abdm_care_context_ref'      => $careContextRef,
            'abdm_care_context_display'  => $careContextDisplay,
            'abdm_fhir_bundle_json'      => $fhirBundleJson,
            'abdm_sync_status'           => (!empty($abhaAddress) || !empty($abhaId)) ? 'PENDING' : 'NOT_APPLICABLE',
            'status'                     => 'completed',
            'created_by'                 => (int)($json['user_id'] ?? 1)
        ];

        // If IPD Credit, optionally link into ipd_invoice_item so hospital discharge handles it
        if ($paymentMode === 'IPD_Credit' && !empty($json['ipd_id'])) {
            $ipdId = (int)$json['ipd_id'];
            if ($this->db->tableExists('ipd_invoice_item')) {
                $this->db->table('ipd_invoice_item')->insert([
                    'ipd_id'        => $ipdId,
                    'item_name'     => "Pharmacy Medicines ({$invoiceNo})",
                    'item_desc'     => "Medicines dispensed from {$store['store_name']}",
                    'item_qty'      => 1,
                    'item_price'    => $netRounded,
                    'item_amount'   => $netRounded,
                    'insert_date'   => date('Y-m-d H:i:s'),
                ]);
                $saleData['ipd_charge_id'] = $this->db->insertID();
            }
        }

        $this->db->table('mst_sales')->insert($saleData);
        $saleId = $this->db->insertID();

        // Queue in hospital central ABDM Gateway Sync Outbox if table exists
        if ((!empty($abhaAddress) || !empty($patientId)) && $this->db->tableExists('abdm_sync_record')) {
            $this->db->table('abdm_sync_record')->insert([
                'local_record_id'        => 'mst_sale_' . $saleId,
                'local_patient_id'       => $patientId ?: 0,
                'hi_type'                => 'MedicationDispense',
                'care_context_reference' => $careContextRef,
                'care_context_display'   => $careContextDisplay,
                'visit_date'             => date('Y-m-d'),
                'department'             => 'Pharmacy (' . ($store['store_name'] ?? 'Medical Store') . ')',
                'doctor_name'            => trim($json['doctor_name'] ?? ($store['registered_pharmacist_name'] ?? 'Pharmacist')),
                'fhir_bundle_json'       => $fhirBundleJson,
                'hfr_id'                 => $store['abdm_hfr_id'] ?: 'IN0710001234',
                'sync_status'            => 'PENDING',
                'source_updated_at'      => date('Y-m-d H:i:s'),
                'created_at'             => date('Y-m-d H:i:s')
            ]);
        }

        // Insert Sale Items
        foreach ($validatedItems as $vItem) {
            $this->db->table('mst_sales_items')->insert([
                'sale_id'         => $saleId,
                'item_id'         => $vItem['item_id'],
                'batch_id'        => $vItem['batch_id'],
                'batch_no'        => $vItem['batch_no'],
                'expiry_date'     => $vItem['expiry_date'],
                'qty'             => $vItem['qty'],
                'sell_unit'       => $vItem['sell_unit'] ?? 'Strip',
                'units_per_pack'  => $vItem['units_per_pack'] ?? 1,
                'total_units'     => $vItem['total_units'] ?? $vItem['qty'],
                'unit_price'      => $vItem['unit_price'] ?? 0.00,
                'loose_qty'       => $vItem['loose_qty'] ?? 0,
                'pack_qty'        => $vItem['pack_qty'] ?? 1.00,
                'unit_mrp'        => $vItem['unit_mrp'],
                'discount_type'   => $vItem['discount_type'] ?? 'pct',
                'discount_val'    => $vItem['discount_val'] ?? 0,
                'discount_pct'    => $vItem['discount_pct'],
                'discount_amount' => $vItem['discount_amount'],
                'hsn_code'        => $vItem['hsn_code'],
                'gst_rate'        => $vItem['gst_rate'],
                'taxable_value'   => $vItem['taxable_value'],
                'cgst_amount'     => $vItem['cgst_amount'],
                'sgst_amount'     => $vItem['sgst_amount'],
                'igst_amount'     => $vItem['igst_amount'],
                'total_amount'    => $vItem['total_amount']
            ]);
        }

        // Post Double-Entry Journal / Ledgers
        $salesHead = $this->db->table('mst_account_heads')->where('head_code', '3001')->get()->getRowArray();
        $cgstHead  = $this->db->table('mst_account_heads')->where('head_code', '2002')->get()->getRowArray();
        $sgstHead  = $this->db->table('mst_account_heads')->where('head_code', '2003')->get()->getRowArray();
        $igstHead  = $this->db->table('mst_account_heads')->where('head_code', '2004')->get()->getRowArray();
        $discHead  = $this->db->table('mst_account_heads')->where('head_code', '4002')->get()->getRowArray();

        // 1. Debit Cash/Bank/Patient
        if ($cashPaid > 0) {
            $cashHead = $this->db->table('mst_account_heads')->where('head_code', '1001')->get()->getRowArray();
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $cashHead['head_id'], 'debit_amount' => $cashPaid, 'credit_amount' => 0,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Cash received for bill $invoiceNo"
            ]);
        }
        if ($upiPaid > 0 || $cardPaid > 0) {
            $bankHead = $this->db->table('mst_account_heads')->where('head_code', '1002')->get()->getRowArray();
            $refDetails = [];
            if (!empty($upiRefNo)) $refDetails[] = "UTR: {$upiRefNo}";
            if (!empty($cardRefNo)) $refDetails[] = "Card Auth: {$cardRefNo}";
            if (!empty($bankName)) $refDetails[] = "Bank: {$bankName}";
            $narrationExtra = !empty($refDetails) ? " (" . implode(', ', $refDetails) . ")" : "";
            $auditRef = !empty($upiRefNo) ? $upiRefNo : $cardRefNo;

            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $bankHead['head_id'], 'debit_amount' => ($upiPaid + $cardPaid), 'credit_amount' => 0,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "UPI/Bank received for bill $invoiceNo" . $narrationExtra,
                'reconciled_flag' => 0,
                'reconciled_ref' => $auditRef
            ]);
        }
        if ($creditAmount > 0) {
            $debtorHeadCode = ($paymentMode === 'IPD_Credit') ? '1005' : '1004';
            $debtorHead = $this->db->table('mst_account_heads')->where('head_code', $debtorHeadCode)->get()->getRowArray();
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $debtorHead['head_id'], 'debit_amount' => $creditAmount, 'credit_amount' => 0,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Credit/IPD charge for bill $invoiceNo"
            ]);
        }
        if ($totalDiscount > 0 && $discHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $discHead['head_id'], 'debit_amount' => $totalDiscount, 'credit_amount' => 0,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Discount allowed on bill $invoiceNo"
            ]);
        }

        // 2. Credit Sales & Output Tax
        if ($salesHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $salesHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $taxableTotal,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Taxable Pharmacy Sales $invoiceNo"
            ]);
        }
        if ($cgstTotal > 0 && $cgstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $cgstHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $cgstTotal,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Output CGST on $invoiceNo"
            ]);
        }
        if ($sgstTotal > 0 && $sgstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $sgstHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $sgstTotal,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Output SGST on $invoiceNo"
            ]);
        }
        if ($igstTotal > 0 && $igstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $igstHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $igstTotal,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Output IGST on $invoiceNo"
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to process sales invoice.']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Invoice generated successfully.',
            'sale_id' => $saleId,
            'invoice_no' => $invoiceNo,
            'net_amount' => $netRounded,
            'abdm' => [
                'abha_id' => $abhaId,
                'abha_address' => $abhaAddress,
                'care_context_ref' => $careContextRef,
                'is_abdm_linked' => (!empty($abhaAddress) || !empty($abhaId))
            ]
        ]);
    }

    public function getInvoice(int $saleId)
    {
        $sale = $this->db->table('mst_sales s')
            ->select('s.*, st.store_name, st.building_name, st.floor_no, st.room_no, st.drug_license_no_20b, st.drug_license_no_21b, st.drug_license_no_20f_x, st.gstin, st.pan_no, st.fssai_no, st.state_code, st.state_name, st.registered_pharmacist_name, st.pharmacist_reg_no, st.contact_phone, st.contact_email, st.address as store_address, st.upi_id, st.terms_conditions, st.abdm_hfr_id, st.abdm_hip_id, st.pharmacist_hpr_id')
            ->join('mst_stores st', 'st.store_id = s.store_id')
            ->where('s.sale_id', $saleId)
            ->get()
            ->getRowArray();

        if (!$sale) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Invoice not found.']);
        }

        $items = $this->db->table('mst_sales_items si')
            ->select('si.*, i.item_name, i.generic_name, i.category, i.unit_pack, i.drug_schedule, i.snomed_ct_code, i.snomed_display')
            ->join('mst_items i', 'i.item_id = si.item_id')
            ->where('si.sale_id', $saleId)
            ->get()
            ->getResultArray();

        // Build HSN summary table for Indian GST invoice standard
        $hsnSummary = [];
        foreach ($items as $it) {
            $hsn = $it['hsn_code'] ?: '3004';
            $rate = (string)$it['gst_rate'];
            $key = $hsn . '_' . $rate;
            if (!isset($hsnSummary[$key])) {
                $hsnSummary[$key] = [
                    'hsn_code'      => $hsn,
                    'gst_rate'      => (float)$rate,
                    'taxable_value' => 0,
                    'cgst_amount'   => 0,
                    'sgst_amount'   => 0,
                    'igst_amount'   => 0,
                    'total_tax'     => 0
                ];
            }
            $hsnSummary[$key]['taxable_value'] += (float)$it['taxable_value'];
            $hsnSummary[$key]['cgst_amount']   += (float)$it['cgst_amount'];
            $hsnSummary[$key]['sgst_amount']   += (float)$it['sgst_amount'];
            $hsnSummary[$key]['igst_amount']   += (float)$it['igst_amount'];
            $hsnSummary[$key]['total_tax']     += ((float)$it['cgst_amount'] + (float)$it['sgst_amount'] + (float)$it['igst_amount']);
        }

        // Dynamic UPI QR string
        $upiQrString = '';
        if (!empty($sale['upi_id'])) {
            $upiQrString = "upi://pay?pa={$sale['upi_id']}&pn=" . urlencode($sale['store_name']) . "&am={$sale['net_amount']}&cu=INR&tn=" . urlencode("Bill " . $sale['invoice_no']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'sale' => $sale,
            'items' => $items,
            'hsn_summary' => array_values($hsnSummary),
            'upi_qr_string' => $upiQrString
        ]);
    }

    public function recentSales()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $sales = $this->db->table('mst_sales')
            ->where('store_id', $storeId)
            ->orderBy('sale_id', 'DESC')
            ->limit(25)
            ->get()
            ->getResultArray();

        return $this->response->setJSON(['status' => 1, 'sales' => $sales]);
    }

    // =========================================================================
    // 5. SUPPLIERS & PURCHASES
    // =========================================================================

    public function suppliers()
    {
        $suppliers = $this->db->table('mst_suppliers')->where('is_active', 1)->orderBy('supplier_name', 'ASC')->get()->getResultArray();
        return $this->response->setJSON(['status' => 1, 'suppliers' => $suppliers]);
    }

    public function saveSupplier()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        if (empty($json['supplier_name'])) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Supplier name is required.']);
        }

        $id = !empty($json['supplier_id']) ? (int)$json['supplier_id'] : 0;
        $data = [
            'supplier_name'  => trim($json['supplier_name']),
            'dl_no_20b'      => trim($json['dl_no_20b'] ?? ''),
            'dl_no_21b'      => trim($json['dl_no_21b'] ?? ''),
            'gstin'          => strtoupper(trim($json['gstin'] ?? '')),
            'pan_no'         => strtoupper(trim($json['pan_no'] ?? '')),
            'contact_person' => trim($json['contact_person'] ?? ''),
            'phone'          => trim($json['phone'] ?? ''),
            'email'          => trim($json['email'] ?? ''),
            'address'        => trim($json['address'] ?? ''),
            'state_code'     => trim($json['state_code'] ?? '07'),
            'credit_days'    => !empty($json['credit_days']) ? (int)$json['credit_days'] : 30,
            'bank_details'   => trim($json['bank_details'] ?? ''),
        ];

        if ($id > 0) {
            $this->db->table('mst_suppliers')->where('supplier_id', $id)->update($data);
        } else {
            $this->db->table('mst_suppliers')->insert($data);
            $id = $this->db->insertID();
        }

        return $this->response->setJSON(['status' => 1, 'message' => 'Supplier saved successfully.', 'supplier_id' => $id]);
    }

    public function savePurchase()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $storeId = (int)($json['store_id'] ?? 1);
        $supplierId = (int)($json['supplier_id'] ?? 0);
        $items = $json['items'] ?? [];

        if ($supplierId <= 0 || empty($items)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Supplier and items are required.']);
        }

        $this->db->transStart();

        $taxableTotal = 0;
        $cgstTotal = 0;
        $sgstTotal = 0;
        $igstTotal = 0;
        $discountTotal = (float)($json['discount_amount'] ?? 0);

        $purchaseItems = [];

        foreach ($items as $it) {
            $itemId = (int)$it['item_id'];
            $batchNo = trim($it['batch_no']);
            $expiryDate = trim($it['expiry_date']);
            $qtyPacks = (int)$it['qty_packs'];
            $freePacks = (int)($it['free_qty_packs'] ?? 0);
            $unitsPerPack = (int)($it['units_per_pack'] ?? 10);
            $totalUnits = ($qtyPacks + $freePacks) * $unitsPerPack;

            $mrp = (float)$it['mrp'];
            $ptr = (float)$it['ptr'];
            $discPct = (float)($it['discount_pct'] ?? 0);
            $gstRate = (float)($it['gst_rate'] ?? 12.00);

            $lineNetPtr = ($qtyPacks * $ptr) * (1 - ($discPct / 100));
            $taxAmount = round($lineNetPtr * ($gstRate / 100), 2);

            $cgst = round($taxAmount / 2, 2);
            $sgst = round($taxAmount - $cgst, 2);
            $igst = 0;
            $lineTotal = round($lineNetPtr + $taxAmount, 2);
            $netUnitLanding = round($lineTotal / $totalUnits, 2);

            $taxableTotal += $lineNetPtr;
            $cgstTotal += $cgst;
            $sgstTotal += $sgst;

            $purchaseItems[] = [
                'item_id'               => $itemId,
                'batch_no'              => $batchNo,
                'expiry_date'           => $expiryDate,
                'qty_packs'             => $qtyPacks,
                'free_qty_packs'        => $freePacks,
                'units_per_pack'        => $unitsPerPack,
                'total_units'           => $totalUnits,
                'mrp'                   => $mrp,
                'ptr'                   => $ptr,
                'discount_pct'          => $discPct,
                'hsn_code'              => $it['hsn_code'] ?? '3004',
                'gst_rate'              => $gstRate,
                'taxable_value'         => $lineNetPtr,
                'cgst_amount'           => $cgst,
                'sgst_amount'           => $sgst,
                'igst_amount'           => $igst,
                'total_amount'          => $lineTotal,
                'net_unit_landing_cost' => $netUnitLanding
            ];

            // Update/create batch in mst_batches
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
                    'purchase_rate_net' => $netUnitLanding,
                    'gst_rate'          => $gstRate
                ]);
            } else {
                $this->db->table('mst_batches')->insert([
                    'store_id'          => $storeId,
                    'item_id'           => $itemId,
                    'batch_no'          => $batchNo,
                    'expiry_date'       => $expiryDate,
                    'mrp'               => $mrp,
                    'ptr'               => $ptr,
                    'purchase_rate_net' => $netUnitLanding,
                    'gst_rate'          => $gstRate
                ]);
                $batchId = $this->db->insertID();
            }

            // Increase stock in mst_stock
            $stockRow = $this->db->table('mst_stock')
                ->where('store_id', $storeId)
                ->where('item_id', $itemId)
                ->where('batch_id', $batchId)
                ->get()
                ->getRowArray();

            if ($stockRow) {
                $this->db->table('mst_stock')
                    ->where('stock_id', $stockRow['stock_id'])
                    ->update(['current_qty' => $stockRow['current_qty'] + $totalUnits]);
            } else {
                $this->db->table('mst_stock')->insert([
                    'store_id'    => $storeId,
                    'item_id'     => $itemId,
                    'batch_id'    => $batchId,
                    'current_qty' => $totalUnits
                ]);
            }
        }

        $grandTotal = round($taxableTotal + $cgstTotal + $sgstTotal + $igstTotal - $discountTotal);

        // Insert Purchase Record
        $purchaseData = [
            'store_id'            => $storeId,
            'supplier_id'         => $supplierId,
            'supplier_invoice_no' => trim($json['supplier_invoice_no'] ?? ('PUR-' . time())),
            'invoice_date'        => $json['invoice_date'] ?? date('Y-m-d'),
            'received_date'       => date('Y-m-d'),
            'due_date'            => date('Y-m-d', strtotime('+30 days')),
            'taxable_amount'      => $taxableTotal,
            'cgst_amount'         => $cgstTotal,
            'sgst_amount'         => $sgstTotal,
            'igst_amount'         => $igstTotal,
            'discount_amount'     => $discountTotal,
            'net_amount'          => $grandTotal,
            'payment_status'      => 'unpaid',
            'remarks'             => trim($json['remarks'] ?? '')
        ];
        $this->db->table('mst_purchases')->insert($purchaseData);
        $purchaseId = $this->db->insertID();

        foreach ($purchaseItems as $pi) {
            $pi['purchase_id'] = $purchaseId;
            $this->db->table('mst_purchase_items')->insert($pi);
        }

        // Double-entry ledger: Dr Purchases, Dr Input GST, Cr Sundry Creditors (Supplier)
        $purchaseHead = $this->db->table('mst_account_heads')->where('head_code', '4001')->get()->getRowArray();
        $inputCgstHead = $this->db->table('mst_account_heads')->where('head_code', '1006')->get()->getRowArray();
        $inputSgstHead = $this->db->table('mst_account_heads')->where('head_code', '1007')->get()->getRowArray();
        $creditorHead = $this->db->table('mst_account_heads')->where('head_code', '2001')->get()->getRowArray();

        $vNo = $purchaseData['supplier_invoice_no'];
        if ($purchaseHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PURCHASE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $purchaseHead['head_id'], 'debit_amount' => $taxableTotal, 'credit_amount' => 0,
                'reference_type' => 'purchase_invoice', 'reference_id' => $purchaseId, 'narration' => "Purchase from Supplier #$supplierId Bill $vNo"
            ]);
        }
        if ($cgstTotal > 0 && $inputCgstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PURCHASE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $inputCgstHead['head_id'], 'debit_amount' => $cgstTotal, 'credit_amount' => 0,
                'reference_type' => 'purchase_invoice', 'reference_id' => $purchaseId, 'narration' => "Input CGST credit on $vNo"
            ]);
        }
        if ($sgstTotal > 0 && $inputSgstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PURCHASE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $inputSgstHead['head_id'], 'debit_amount' => $sgstTotal, 'credit_amount' => 0,
                'reference_type' => 'purchase_invoice', 'reference_id' => $purchaseId, 'narration' => "Input SGST credit on $vNo"
            ]);
        }
        if ($creditorHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PURCHASE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $creditorHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $grandTotal,
                'reference_type' => 'purchase_invoice', 'reference_id' => $purchaseId, 'narration' => "Credit payable to Supplier #$supplierId"
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to save purchase bill.']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Purchase inward bill recorded and stock updated.',
            'purchase_id' => $purchaseId,
            'net_amount' => $grandTotal
        ]);
    }

    public function saveSupplierPayment()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $supplierId = (int)($json['supplier_id'] ?? 0);
        $amount = (float)($json['amount'] ?? 0);
        $paymentMode = trim($json['payment_mode'] ?? 'Bank'); // Cash, Bank, Cheque, NEFT
        $refNo = trim($json['reference_no'] ?? '');
        $storeId = (int)($json['store_id'] ?? 1);

        if ($supplierId <= 0 || $amount <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Supplier and valid amount required.']);
        }

        $vNo = 'PMT-' . time();
        $creditorHead = $this->db->table('mst_account_heads')->where('head_code', '2001')->get()->getRowArray();
        $sourceHeadCode = ($paymentMode === 'Cash') ? '1001' : '1002';
        $sourceHead = $this->db->table('mst_account_heads')->where('head_code', $sourceHeadCode)->get()->getRowArray();

        $this->db->transStart();

        // Dr Creditor (reducing liability)
        $this->db->table('mst_ledger_entries')->insert([
            'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PAYMENT', 'voucher_date' => date('Y-m-d'),
            'account_head_id' => $creditorHead['head_id'], 'debit_amount' => $amount, 'credit_amount' => 0,
            'reference_type' => 'supplier_payment', 'reference_id' => $supplierId,
            'narration' => "Payment made to Supplier #$supplierId via $paymentMode Ref: $refNo"
        ]);

        // Cr Cash / Bank
        $this->db->table('mst_ledger_entries')->insert([
            'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PAYMENT', 'voucher_date' => date('Y-m-d'),
            'account_head_id' => $sourceHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $amount,
            'reference_type' => 'supplier_payment', 'reference_id' => $supplierId,
            'narration' => "Payment outflow via $paymentMode to Supplier #$supplierId"
        ]);

        $this->db->transComplete();

        return $this->response->setJSON(['status' => 1, 'message' => 'Supplier payment recorded successfully.', 'voucher_no' => $vNo]);
    }

    // =========================================================================
    // 6. MULTI-STORE TRANSFERS & INDENTS
    // =========================================================================

    public function requestTransfer()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $fromStoreId = (int)($json['from_store_id'] ?? 1); // Source (e.g. Main Store)
        $toStoreId = (int)($json['to_store_id'] ?? 0);     // Destination (e.g. Building Counter)
        $items = $json['items'] ?? [];

        if ($toStoreId <= 0 || empty($items)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Destination store and items are required.']);
        }

        $transferNo = 'IND-' . date('Ymd') . '-' . rand(100, 999);

        $this->db->transStart();
        $this->db->table('mst_transfers')->insert([
            'transfer_no'   => $transferNo,
            'from_store_id' => $fromStoreId,
            'to_store_id'   => $toStoreId,
            'status'        => 'requested',
            'request_date'  => date('Y-m-d H:i:s'),
            'remarks'       => trim($json['remarks'] ?? '')
        ]);
        $transferId = $this->db->insertID();

        foreach ($items as $it) {
            $this->db->table('mst_transfer_items')->insert([
                'transfer_id'   => $transferId,
                'item_id'       => (int)$it['item_id'],
                'requested_qty' => (int)$it['qty']
            ]);
        }

        $this->db->transComplete();

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Store Indent Requisition submitted successfully.',
            'transfer_no' => $transferNo,
            'transfer_id' => $transferId
        ]);
    }

    public function dispatchTransfer()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $transferId = (int)($json['transfer_id'] ?? 0);
        $items = $json['items'] ?? []; // [{item_id, batch_id, dispatched_qty}]

        $transfer = $this->db->table('mst_transfers')->where('transfer_id', $transferId)->get()->getRowArray();
        if (!$transfer || $transfer['status'] !== 'requested') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid transfer request.']);
        }

        $this->db->transStart();

        foreach ($items as $it) {
            $itemId = (int)$it['item_id'];
            $batchId = (int)$it['batch_id'];
            $qty = (int)$it['dispatched_qty'];

            // Deduct stock from source store
            $this->db->table('mst_stock')
                ->where('store_id', $transfer['from_store_id'])
                ->where('batch_id', $batchId)
                ->set('current_qty', 'current_qty - ' . $qty, false)
                ->update();

            $this->db->table('mst_transfer_items')
                ->where('transfer_id', $transferId)
                ->where('item_id', $itemId)
                ->update(['batch_id' => $batchId, 'dispatched_qty' => $qty]);
        }

        $this->db->table('mst_transfers')->where('transfer_id', $transferId)->update([
            'status' => 'dispatched',
            'dispatch_date' => date('Y-m-d H:i:s')
        ]);

        $this->db->transComplete();

        return $this->response->setJSON(['status' => 1, 'message' => 'Stock dispatched from source store.']);
    }

    public function receiveTransfer()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $transferId = (int)($json['transfer_id'] ?? 0);

        $transfer = $this->db->table('mst_transfers')->where('transfer_id', $transferId)->get()->getRowArray();
        if (!$transfer || $transfer['status'] !== 'dispatched') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Transfer is not in dispatched state.']);
        }

        $items = $this->db->table('mst_transfer_items')->where('transfer_id', $transferId)->get()->getResultArray();

        $this->db->transStart();

        foreach ($items as $it) {
            $itemId = (int)$it['item_id'];
            $sourceBatchId = (int)$it['batch_id'];
            $qty = (int)$it['dispatched_qty'];

            // Fetch source batch info
            $srcBatch = $this->db->table('mst_batches')->where('batch_id', $sourceBatchId)->get()->getRowArray();
            if (!$srcBatch) continue;

            // Check or create batch in destination store
            $destBatch = $this->db->table('mst_batches')
                ->where('store_id', $transfer['to_store_id'])
                ->where('item_id', $itemId)
                ->where('batch_no', $srcBatch['batch_no'])
                ->get()
                ->getRowArray();

            if ($destBatch) {
                $destBatchId = (int)$destBatch['batch_id'];
            } else {
                $this->db->table('mst_batches')->insert([
                    'store_id'          => $transfer['to_store_id'],
                    'item_id'           => $itemId,
                    'batch_no'          => $srcBatch['batch_no'],
                    'mfg_date'          => $srcBatch['mfg_date'],
                    'expiry_date'       => $srcBatch['expiry_date'],
                    'mrp'               => $srcBatch['mrp'],
                    'ptr'               => $srcBatch['ptr'],
                    'purchase_rate_net' => $srcBatch['purchase_rate_net'],
                    'gst_rate'          => $srcBatch['gst_rate'],
                ]);
                $destBatchId = $this->db->insertID();
            }

            // Increase stock in destination store
            $destStock = $this->db->table('mst_stock')
                ->where('store_id', $transfer['to_store_id'])
                ->where('item_id', $itemId)
                ->where('batch_id', $destBatchId)
                ->get()
                ->getRowArray();

            if ($destStock) {
                $this->db->table('mst_stock')
                    ->where('stock_id', $destStock['stock_id'])
                    ->update(['current_qty' => $destStock['current_qty'] + $qty]);
            } else {
                $this->db->table('mst_stock')->insert([
                    'store_id'    => $transfer['to_store_id'],
                    'item_id'     => $itemId,
                    'batch_id'    => $destBatchId,
                    'current_qty' => $qty
                ]);
            }

            $this->db->table('mst_transfer_items')
                ->where('transfer_item_id', $it['transfer_item_id'])
                ->update(['received_qty' => $qty]);
        }

        $this->db->table('mst_transfers')->where('transfer_id', $transferId)->update([
            'status' => 'received',
            'receive_date' => date('Y-m-d H:i:s')
        ]);

        $this->db->transComplete();

        return $this->response->setJSON(['status' => 1, 'message' => 'Stock received and credited to store inventory.']);
    }

    // =========================================================================
    // 7. INDIAN ACCOUNTING, LEDGERS & GST REPORTS
    // =========================================================================

    public function getSupplierLedger(int $supplierId)
    {
        $supplier = $this->db->table('mst_suppliers')->where('supplier_id', $supplierId)->get()->getRowArray();
        if (!$supplier) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Supplier not found.']);
        }

        // Ledger entries for this supplier
        $entries = $this->db->table('mst_ledger_entries le')
            ->select('le.*, ah.head_name')
            ->join('mst_account_heads ah', 'ah.head_id = le.account_head_id')
            ->groupStart()
                ->where('le.reference_type', 'purchase_invoice')
                ->orWhere('le.reference_type', 'supplier_payment')
            ->groupEnd()
            ->orderBy('le.voucher_date', 'ASC')
            ->orderBy('le.entry_id', 'ASC')
            ->get()
            ->getResultArray();

        $runningBalance = 0; // Credit positive (Payable)
        $statement = [];
        foreach ($entries as $e) {
            $debit = (float)$e['debit_amount'];
            $credit = (float)$e['credit_amount'];
            $runningBalance += ($credit - $debit);

            $e['balance'] = round($runningBalance, 2);
            $statement[] = $e;
        }

        return $this->response->setJSON([
            'status' => 1,
            'supplier' => $supplier,
            'current_balance' => round($runningBalance, 2),
            'statement' => $statement
        ]);
    }

    public function getPatientLedger(string $uhid)
    {
        $sales = $this->db->table('mst_sales')
            ->where('uhid', $uhid)
            ->orderBy('sale_date', 'DESC')
            ->get()
            ->getResultArray();

        $totalBilled = 0;
        $totalPaid = 0;
        $totalCredit = 0;

        foreach ($sales as $s) {
            $totalBilled += (float)$s['net_amount'];
            $totalPaid += ((float)$s['cash_paid'] + (float)$s['upi_paid'] + (float)$s['card_paid']);
            $totalCredit += (float)$s['credit_amount'];
        }

        return $this->response->setJSON([
            'status' => 1,
            'uhid' => $uhid,
            'summary' => [
                'total_billed' => round($totalBilled, 2),
                'total_paid' => round($totalPaid, 2),
                'outstanding_credit' => round($totalCredit, 2),
            ],
            'invoices' => $sales
        ]);
    }

    public function getDaybook()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $date = trim($this->request->getGet('date') ?? date('Y-m-d'));

        $sales = $this->db->table('mst_sales')
            ->where('store_id', $storeId)
            ->where("DATE(sale_date)", $date)
            ->orderBy('sale_id', 'ASC')
            ->get()
            ->getResultArray();

        $totalSales = 0;
        $cashTotal = 0;
        $upiTotal = 0;
        $cardTotal = 0;
        $creditTotal = 0;
        $cgstTotal = 0;
        $sgstTotal = 0;

        foreach ($sales as $s) {
            $totalSales += (float)$s['net_amount'];
            $cashTotal += (float)$s['cash_paid'];
            $upiTotal += (float)$s['upi_paid'];
            $cardTotal += (float)$s['card_paid'];
            $creditTotal += (float)$s['credit_amount'];
            $cgstTotal += (float)$s['cgst_amount'];
            $sgstTotal += (float)$s['sgst_amount'];
        }

        return $this->response->setJSON([
            'status' => 1,
            'store_id' => $storeId,
            'date' => $date,
            'summary' => [
                'total_invoices' => count($sales),
                'total_sales'    => round($totalSales, 2),
                'cash_in_drawer' => round($cashTotal, 2),
                'upi_collection' => round($upiTotal, 2),
                'card_swipes'    => round($cardTotal, 2),
                'ipd_credit'     => round($creditTotal, 2),
                'cgst_collected' => round($cgstTotal, 2),
                'sgst_collected' => round($sgstTotal, 2),
            ],
            'transactions' => $sales
        ]);
    }

    public function getGstReport()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $from = trim($this->request->getGet('from') ?? date('Y-m-01'));
        $to = trim($this->request->getGet('to') ?? date('Y-m-t'));

        // GSTR-1 Sales Breakdown
        $salesItems = $this->db->table('mst_sales_items si')
            ->select('si.hsn_code, si.gst_rate, SUM(si.taxable_value) as taxable_val, SUM(si.cgst_amount) as cgst_val, SUM(si.sgst_amount) as sgst_val, SUM(si.igst_amount) as igst_val, SUM(si.total_amount) as total_val')
            ->join('mst_sales s', 's.sale_id = si.sale_id')
            ->where('s.store_id', $storeId)
            ->where("DATE(s.sale_date) >=", $from)
            ->where("DATE(s.sale_date) <=", $to)
            ->groupBy('si.hsn_code, si.gst_rate')
            ->get()
            ->getResultArray();

        // GSTR-2 Purchase Breakdown (Input Tax Credit)
        $purchaseItems = $this->db->table('mst_purchase_items pi')
            ->select('pi.hsn_code, pi.gst_rate, SUM(pi.taxable_value) as taxable_val, SUM(pi.cgst_amount) as cgst_val, SUM(pi.sgst_amount) as sgst_val, SUM(pi.igst_amount) as igst_val, SUM(pi.total_amount) as total_val')
            ->join('mst_purchases p', 'p.purchase_id = pi.purchase_id')
            ->where('p.store_id', $storeId)
            ->where("p.invoice_date >=", $from)
            ->where("p.invoice_date <=", $to)
            ->groupBy('pi.hsn_code, pi.gst_rate')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'period' => ['from' => $from, 'to' => $to],
            'gstr1_sales' => $salesItems,
            'gstr2_purchases' => $purchaseItems
        ]);
    }

    public function getBankReconciliation()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $status = trim($this->request->getGet('status') ?? 'all'); // 'all', 'pending', 'reconciled'
        $from = trim($this->request->getGet('from') ?? '');
        $to = trim($this->request->getGet('to') ?? '');
        $search = trim($this->request->getGet('search') ?? '');

        $builder = $this->db->table('mst_sales')
            ->where('store_id', $storeId)
            ->groupStart()
                ->where('upi_paid >', 0)
                ->orWhere('card_paid >', 0)
                ->orWhereIn('payment_mode', ['UPI', 'Card', 'Mixed'])
            ->groupEnd();

        if (!empty($from)) {
            $builder->where('DATE(sale_date) >=', $from);
        }
        if (!empty($to)) {
            $builder->where('DATE(sale_date) <=', $to);
        }

        if ($status === 'pending') {
            $builder->where('is_bank_reconciled', 0);
        } elseif ($status === 'reconciled') {
            $builder->where('is_bank_reconciled', 1);
        }

        if (!empty($search)) {
            $builder->groupStart()
                ->like('invoice_no', $search)
                ->orLike('patient_name', $search)
                ->orLike('uhid', $search)
                ->orLike('upi_ref_no', $search)
                ->orLike('card_ref_no', $search)
                ->orLike('bank_name', $search)
            ->groupEnd();
        }

        $records = $builder->orderBy('sale_id', 'DESC')->get()->getResultArray();

        // Compute summary metrics for Bank/UPI transactions
        $summaryBuilder = $this->db->table('mst_sales')
            ->where('store_id', $storeId)
            ->groupStart()
                ->where('upi_paid >', 0)
                ->orWhere('card_paid >', 0)
                ->orWhereIn('payment_mode', ['UPI', 'Card', 'Mixed'])
            ->groupEnd();

        if (!empty($from)) {
            $summaryBuilder->where('DATE(sale_date) >=', $from);
        }
        if (!empty($to)) {
            $summaryBuilder->where('DATE(sale_date) <=', $to);
        }

        $allBankSales = $summaryBuilder->get()->getResultArray();

        $totalCount = count($allBankSales);
        $totalBankUpiAmount = 0;
        $reconciledCount = 0;
        $reconciledAmount = 0;
        $pendingCount = 0;
        $pendingAmount = 0;

        foreach ($allBankSales as $s) {
            $bankPart = (float)$s['upi_paid'] + (float)$s['card_paid'];
            $totalBankUpiAmount += $bankPart;
            if (!empty($s['is_bank_reconciled'])) {
                $reconciledCount++;
                $reconciledAmount += $bankPart;
            } else {
                $pendingCount++;
                $pendingAmount += $bankPart;
            }
        }

        return $this->response->setJSON([
            'status' => 1,
            'summary' => [
                'total_count'       => $totalCount,
                'total_amount'      => round($totalBankUpiAmount, 2),
                'reconciled_count'  => $reconciledCount,
                'reconciled_amount' => round($reconciledAmount, 2),
                'pending_count'     => $pendingCount,
                'pending_amount'    => round($pendingAmount, 2),
            ],
            'records' => $records
        ]);
    }

    public function reconcileBank()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $saleId = (int)($json['sale_id'] ?? 0);
        $isReconciled = !empty($json['is_reconciled']) ? 1 : 0;
        $notes = trim($json['reconciled_notes'] ?? '');
        $reconciledBy = trim($json['reconciled_by'] ?? 'Accountant');

        if ($saleId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Sale ID is required.']);
        }

        $sale = $this->db->table('mst_sales')->where('sale_id', $saleId)->get()->getRowArray();
        if (!$sale) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Sales invoice not found.']);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('mst_sales')->where('sale_id', $saleId)->update([
            'is_bank_reconciled' => $isReconciled,
            'reconciled_at'      => $isReconciled ? $now : null,
            'reconciled_by'      => $isReconciled ? $reconciledBy : '',
            'reconciled_notes'   => $isReconciled ? $notes : null
        ]);

        // Update ledger entry for head 1002 (Bank)
        $bankHead = $this->db->table('mst_account_heads')->where('head_code', '1002')->get()->getRowArray();
        if ($bankHead) {
            $this->db->table('mst_ledger_entries')
                ->where('reference_type', 'sales_invoice')
                ->where('reference_id', $saleId)
                ->where('account_head_id', $bankHead['head_id'])
                ->update([
                    'reconciled_flag' => $isReconciled,
                    'reconciled_at'   => $isReconciled ? $now : null
                ]);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => $isReconciled ? 'Invoice marked as Bank Statement Reconciled.' : 'Reconciliation status cleared.'
        ]);
    }

    public function getScheduleH1Register()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $from = trim($this->request->getGet('from') ?? date('Y-m-01'));
        $to = trim($this->request->getGet('to') ?? date('Y-m-d'));

        // Indian CDSCO Schedule H1 Register
        $h1Rows = $this->db->table('mst_sales_items si')
            ->select('s.sale_date, s.invoice_no, s.patient_name, s.patient_address, s.doctor_name, s.doctor_reg_no, i.item_name, si.batch_no, si.qty, i.manufacturer_name')
            ->join('mst_sales s', 's.sale_id = si.sale_id')
            ->join('mst_items i', 'i.item_id = si.item_id')
            ->where('s.store_id', $storeId)
            ->where('i.drug_schedule', 'Schedule H1')
            ->where("DATE(s.sale_date) >=", $from)
            ->where("DATE(s.sale_date) <=", $to)
            ->orderBy('s.sale_date', 'DESC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'compliance_note' => 'Statutory Schedule H1 Register maintained as per Drugs and Cosmetics Rules (4th Amendment 2013). Preserve record for minimum 3 years.',
            'records' => $h1Rows
        ]);
    }

    // =========================================================================
    // 8. COMPUTER TERMINAL / MACHINE VERIFICATION (SECURITY KEY & OTP)
    // =========================================================================

    public function verifyDevice()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $storeId = (int)($json['store_id'] ?? 0);
        $storeSlug = trim($json['store_slug'] ?? '');
        $secKey = trim($json['security_key'] ?? '');
        $otp = trim($json['otp'] ?? '');
        $machineName = trim($json['machine_name'] ?? 'Pharmacy Counter PC');
        $deviceFingerprint = trim($json['device_fingerprint'] ?? '');

        // Resolve store
        $store = null;
        if ($storeId > 0) {
            $store = $this->db->table('mst_stores')->where('store_id', $storeId)->get()->getRowArray();
        } elseif (!empty($storeSlug)) {
            $store = $this->db->table('mst_stores')
                ->groupStart()
                    ->where('store_slug', $storeSlug)
                    ->orWhere('store_code', $storeSlug)
                ->groupEnd()
                ->get()
                ->getRowArray();
        }

        if (!$store) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Medical Store counter not found.']);
        }

        $isVerified = false;
        $method = 'SECURITY_KEY';

        // Check permanent Security Key
        if (!empty($secKey) && !empty($store['security_key']) && hash_equals($store['security_key'], $secKey)) {
            $isVerified = true;
            $method = 'SECURITY_KEY';
        }

        // Check 60-minute OTP
        if (!$isVerified && !empty($otp) && !empty($store['current_otp'])) {
            $now = date('Y-m-d H:i:s');
            if ($store['current_otp'] === $otp && $store['otp_expiry'] >= $now) {
                $isVerified = true;
                $method = 'OTP';
            }
        }

        if (!$isVerified) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 0,
                'message' => 'Invalid Security Key or Expired OTP. Please ask HMS Admin for verification key.'
            ]);
        }

        // Generate high-entropy 64-character device token
        $deviceToken = bin2hex(random_bytes(32));
        $ip = $this->request->getIPAddress();
        $ua = $this->request->getUserAgent()->getAgentString();

        $this->db->table('mst_store_devices')->insert([
            'store_id'           => $store['store_id'],
            'machine_name'       => $machineName,
            'device_token'       => $deviceToken,
            'device_fingerprint' => $deviceFingerprint,
            'ip_address'         => $ip,
            'user_agent'         => $ua,
            'verified_by_method' => $method,
            'status'             => 'authorized'
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => "Machine '{$machineName}' successfully verified and authorized for {$store['store_name']}.",
            'device_token' => $deviceToken,
            'store' => [
                'store_id'   => (int)$store['store_id'],
                'store_name' => $store['store_name'],
                'store_slug' => $store['store_slug']
            ]
        ]);
    }

    public function checkDeviceStatus()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $storeId = (int)($json['store_id'] ?? 0);
        $deviceToken = trim($json['device_token'] ?? '');

        if (empty($deviceToken)) {
            return $this->response->setJSON(['status' => 0, 'authorized' => false, 'message' => 'No device token.']);
        }

        $builder = $this->db->table('mst_store_devices')
            ->where('device_token', $deviceToken)
            ->where('status', 'authorized');

        if ($storeId > 0) {
            $builder->where('store_id', $storeId);
        }

        $row = $builder->get()->getRowArray();
        if ($row) {
            // Update last active timestamp
            $this->db->table('mst_store_devices')->where('device_id', $row['device_id'])->update([
                'last_active_at' => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON([
                'status' => 1,
                'authorized' => true,
                'machine_name' => $row['machine_name'],
                'store_id' => (int)$row['store_id']
            ]);
        }

        return $this->response->setJSON(['status' => 0, 'authorized' => false, 'message' => 'Device authorization revoked or not found.']);
    }

    public function importMarg()
    {
        $admin = new \App\Controllers\Setting\MedicalStoreAdmin();
        return $admin->importMarg();
    }

    // =========================================================================
    // 9. ABDM (AYUSHMAN BHARAT DIGITAL MISSION) COMPLIANCE ENDPOINTS
    // =========================================================================

    public function getAbdmBundle(int $saleId)
    {
        $sale = $this->db->table('mst_sales')
            ->select('sale_id, invoice_no, abha_id, abha_address, abdm_care_context_ref, abdm_care_context_display, abdm_sync_status, abdm_fhir_bundle_json')
            ->where('sale_id', $saleId)
            ->get()
            ->getRowArray();

        if (!$sale) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Sale record not found.']);
        }

        $bundle = json_decode($sale['abdm_fhir_bundle_json'] ?? '{}', true);

        return $this->response->setJSON([
            'status'                => 1,
            'sale_id'               => $saleId,
            'invoice_no'            => $sale['invoice_no'],
            'abha_id'               => $sale['abha_id'],
            'abha_address'          => $sale['abha_address'],
            'care_context_ref'      => $sale['abdm_care_context_ref'],
            'care_context_display'  => $sale['abdm_care_context_display'],
            'sync_status'           => $sale['abdm_sync_status'],
            'bundle'                => $bundle,
            'fhir_bundle'           => $bundle
        ]);
    }

    public function linkAbha()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $saleId = (int)($json['sale_id'] ?? 0);
        $abhaId = trim($json['abha_id'] ?? '');
        $abhaAddress = trim($json['abha_address'] ?? '');

        if ($saleId <= 0 || (empty($abhaId) && empty($abhaAddress))) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Sale ID and valid ABHA ID or Address are required.']);
        }

        $this->db->table('mst_sales')->where('sale_id', $saleId)->update([
            'abha_id'          => $abhaId ?: null,
            'abha_address'     => $abhaAddress ?: null,
            'abdm_sync_status' => 'PENDING'
        ]);

        return $this->response->setJSON([
            'status'  => 1,
            'message' => 'ABHA details linked to invoice successfully. ABDM synchronization scheduled.'
        ]);
    }
}

