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

    /**
     * Searches both med_product_master and mst_items for purchase inward autocomplete.
     */
    public function searchMasterItems()
    {
        $q = trim($this->request->getGet('q') ?? '');

        $results = [];
        $seenNames = [];

        // 1. Query mst_items
        $builder = $this->db->table('mst_items i');
        $builder->select('i.item_id, i.product_master_id, i.item_name, i.generic_name, i.category, i.hsn_code, i.gst_rate, i.unit_pack, i.units_per_pack, i.drug_schedule, i.manufacturer_name');
        $builder->where('i.is_active', 1);

        if (!empty($q)) {
            $builder->groupStart()
                ->like('i.item_name', $q)
                ->orLike('i.generic_name', $q)
                ->groupEnd();
        }

        $mstItems = $builder->limit(40)->get()->getResultArray();

        foreach ($mstItems as $it) {
            $nameKey = strtolower(trim($it['item_name']));
            $seenNames[$nameKey] = true;
            $results[] = [
                'item_id'           => (int)$it['item_id'],
                'product_master_id' => !empty($it['product_master_id']) ? (int)$it['product_master_id'] : null,
                'item_name'         => $it['item_name'],
                'generic_name'      => $it['generic_name'] ?? '',
                'category'          => $it['category'] ?? 'Tablet',
                'hsn_code'          => $it['hsn_code'] ?? '3004',
                'gst_rate'          => (float)($it['gst_rate'] ?? 12.00),
                'unit_pack'         => $it['unit_pack'] ?? '',
                'units_per_pack'    => (int)($it['units_per_pack'] ?? 10),
                'drug_schedule'     => $it['drug_schedule'] ?? 'OTC',
                'manufacturer_name' => $it['manufacturer_name'] ?? '',
                'source'            => 'mst_items'
            ];
        }

        // 2. Query legacy/shared med_product_master
        if ($this->db->tableExists('med_product_master')) {
            $pmBuilder = $this->db->table('med_product_master p');
            $pmBuilder->select('p.id, p.item_name, p.formulation, p.genericname, p.packing, p.HSNCODE, p.CGST_per, p.SGST_per, p.company_name, p.mfgname, p.schedule_h, p.schedule_h1, p.schedule_x, p.narcotic');
            $pmBuilder->where('p.is_continue', 1);

            if (!empty($q)) {
                $pmBuilder->groupStart()
                    ->like('p.item_name', $q)
                    ->orLike('p.genericname', $q)
                    ->groupEnd();
            }

            $pmItems = $pmBuilder->limit(40)->get()->getResultArray();

            foreach ($pmItems as $pm) {
                $nameKey = strtolower(trim($pm['item_name']));
                if (isset($seenNames[$nameKey])) {
                    continue;
                }
                $seenNames[$nameKey] = true;

                $packNum = (int)preg_replace('/[^0-9]/', '', (string)($pm['packing'] ?? ''));
                if ($packNum <= 0) $packNum = 10;

                $gst = (float)($pm['CGST_per'] ?? 0) + (float)($pm['SGST_per'] ?? 0);
                if ($gst <= 0) $gst = 12.00;

                $schedule = 'OTC';
                if (!empty($pm['schedule_h1'])) $schedule = 'Schedule H1';
                else if (!empty($pm['schedule_h'])) $schedule = 'Schedule H';
                else if (!empty($pm['schedule_x'])) $schedule = 'Schedule X';
                else if (!empty($pm['narcotic'])) $schedule = 'Narcotic';

                $company = !empty($pm['company_name']) && $pm['company_name'] !== '0' ? $pm['company_name'] : (!empty($pm['mfgname']) && $pm['mfgname'] !== '0' ? $pm['mfgname'] : '');
                $formulation = !empty($pm['formulation']) && $pm['formulation'] !== '0' ? trim($pm['formulation']) : 'Tablet';

                // Ensure it exists in mst_items with linked product_master_id
                $existingMst = $this->db->table('mst_items')
                    ->where('product_master_id', (int)$pm['id'])
                    ->orWhere('LOWER(TRIM(item_name))', $nameKey)
                    ->get()
                    ->getRowArray();

                if ($existingMst) {
                    $itemId = (int)$existingMst['item_id'];
                    if (empty($existingMst['product_master_id'])) {
                        $this->db->table('mst_items')->where('item_id', $itemId)->update(['product_master_id' => (int)$pm['id']]);
                    }
                } else {
                    $newMstData = [
                        'product_master_id' => (int)$pm['id'],
                        'item_name'         => trim($pm['item_name']),
                        'generic_name'      => trim($pm['genericname'] ?? ''),
                        'category'          => $formulation,
                        'hsn_code'          => !empty($pm['HSNCODE']) && $pm['HSNCODE'] !== '0' ? trim($pm['HSNCODE']) : '3004',
                        'gst_rate'          => $gst,
                        'unit_pack'         => $packNum . ' ' . $formulation,
                        'units_per_pack'    => $packNum,
                        'drug_schedule'     => $schedule,
                        'manufacturer_name' => $company,
                        'is_active'         => 1,
                        'min_reorder_level' => 10
                    ];
                    $this->db->table('mst_items')->insert($newMstData);
                    $itemId = (int)$this->db->insertID();
                }

                $results[] = [
                    'item_id'           => $itemId,
                    'product_master_id' => (int)$pm['id'],
                    'item_name'         => trim($pm['item_name']),
                    'generic_name'      => trim($pm['genericname'] ?? ''),
                    'category'          => $formulation,
                    'hsn_code'          => !empty($pm['HSNCODE']) && $pm['HSNCODE'] !== '0' ? trim($pm['HSNCODE']) : '3004',
                    'gst_rate'          => $gst,
                    'unit_pack'         => $packNum . ' ' . $formulation,
                    'units_per_pack'    => $packNum,
                    'drug_schedule'     => $schedule,
                    'manufacturer_name' => $company,
                    'source'            => 'med_product_master'
                ];
            }
        }

        return $this->response->setJSON([
            'status' => 1,
            'items'  => $results
        ]);
    }

    /**
     * Returns support table data (formulations, companies, GST rates) for adding medicines to master.
     */
    public function getMasterSupportData()
    {
        $formulations = [];
        if ($this->db->tableExists('med_formulation')) {
            $rows = $this->db->table('med_formulation')
                ->select('formulation, formulation_length')
                ->where('formulation !=', '')
                ->where('formulation !=', '0')
                ->orderBy('formulation', 'ASC')
                ->limit(100)
                ->get()
                ->getResultArray();
            foreach ($rows as $r) {
                $f = trim($r['formulation']);
                if ($f !== '' && !in_array($f, $formulations, true)) {
                    $formulations[] = $f;
                }
            }
        }
        $defaults = ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Ointment', 'Cream', 'Gel', 'Drops', 'Suspension', 'Inhaler', 'IV Fluid', 'Powder', 'Lotion', 'Mouthwash', 'Spray', 'Soap'];
        foreach ($defaults as $d) {
            if (!in_array($d, $formulations, true)) {
                array_unshift($formulations, $d);
            }
        }

        $companies = [];
        if ($this->db->tableExists('med_company')) {
            $cRows = $this->db->table('med_company')
                ->select('id, company_name')
                ->where('company_name !=', '')
                ->where('company_name !=', '0')
                ->orderBy('company_name', 'ASC')
                ->limit(200)
                ->get()
                ->getResultArray();
            foreach ($cRows as $cr) {
                $cn = trim($cr['company_name']);
                if ($cn !== '' && !in_array($cn, $companies, true)) {
                    $companies[] = $cn;
                }
            }
        }

        // Current GST Slabs: 0% (Nil/Exempt), 5% (Common/Basic), 18% (Standard), 40% (Demerit/Sin), plus legacy 12% and 28%
        $gstRates = [0, 5, 18, 40, 12, 28];
        if ($this->db->tableExists('med_gst_per')) {
            $gRows = $this->db->table('med_gst_per')->select('gst_per')->orderBy('gst_per', 'ASC')->get()->getResultArray();
            if (!empty($gRows)) {
                $mappedRates = [];
                foreach ($gRows as $gr) {
                    $half = (float)$gr['gst_per'];
                    $tot = round($half * 2, 2); // med_gst_per stores CGST half-rate
                    if ($tot > 0 && !in_array($tot, $mappedRates)) {
                        $mappedRates[] = $tot;
                    }
                }
                foreach ([0, 5, 18, 40, 12, 28] as $reqRate) {
                    if (!in_array($reqRate, $mappedRates)) {
                        $mappedRates[] = $reqRate;
                    }
                }
                $mappedRates = array_values(array_unique($mappedRates));
                sort($mappedRates);
                $gstRates = $mappedRates;
            }
        }

        $schedules = ['OTC', 'Schedule H', 'Schedule H1', 'Schedule X', 'Schedule G', 'Narcotic'];

        return $this->response->setJSON([
            'status'       => 1,
            'formulations' => array_values($formulations),
            'companies'    => array_values($companies),
            'gst_rates'    => $gstRates,
            'schedules'    => $schedules
        ]);
    }

    /**
     * Saves a new or updated medicine into the shared med_product_master and mirrors into mst_items.
     */
    public function saveProductMaster()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $itemName = trim($json['item_name'] ?? '');

        if ($itemName === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 0,
                'message' => 'Medicine Name is required.'
            ]);
        }

        $genericName   = trim($json['generic_name'] ?? '');
        $formulation   = trim($json['formulation'] ?? $json['category'] ?? 'Tablet');
        if ($formulation === '') $formulation = 'Tablet';
        $unitsPerPack  = max(1, (int)($json['units_per_pack'] ?? 10));
        $packing       = trim($json['packing'] ?? (string)$unitsPerPack);
        $hsnCode       = trim($json['hsn_code'] ?? '3004');
        $gstRate       = (float)($json['gst_rate'] ?? 12.00);
        $drugSchedule  = trim($json['drug_schedule'] ?? 'OTC');
        $companyName   = trim($json['company_name'] ?? $json['manufacturer_name'] ?? '');
        $minReorder    = max(1, (int)($json['min_reorder_level'] ?? 10));

        $this->db->transStart();

        // 1. Sync support table med_formulation
        $formulationId = 0;
        if ($this->db->tableExists('med_formulation') && $formulation !== '') {
            $existingForm = $this->db->table('med_formulation')
                ->where('LOWER(TRIM(formulation))', strtolower($formulation))
                ->get()
                ->getRowArray();
            if ($existingForm) {
                $formulationId = (int)$existingForm['id'];
            } else {
                $this->db->table('med_formulation')->insert([
                    'formulation'        => $formulation,
                    'formulation_length' => $formulation
                ]);
                $formulationId = (int)$this->db->insertID();
            }
        }

        // 2. Sync support table med_company
        $companyId = 0;
        if ($this->db->tableExists('med_company') && $companyName !== '') {
            $existingComp = $this->db->table('med_company')
                ->where('LOWER(TRIM(company_name))', strtolower($companyName))
                ->get()
                ->getRowArray();
            if ($existingComp) {
                $companyId = (int)$existingComp['id'];
            } else {
                $this->db->table('med_company')->insert([
                    'company_name' => $companyName
                ]);
                $companyId = (int)$this->db->insertID();
            }
        }

        // 3. Save into med_product_master
        $productMasterId = 0;
        $halfGst = round($gstRate / 2, 2);
        if ($this->db->tableExists('med_product_master')) {
            $pFields = $this->db->getFieldNames('med_product_master') ?? [];

            $pmRow = $this->db->table('med_product_master')
                ->where('LOWER(TRIM(item_name))', strtolower($itemName))
                ->get()
                ->getRowArray();

            $pmData = [
                'item_name'            => $itemName,
                'formulation'          => $formulation,
                'formulation_id'       => $formulationId,
                'genericname'          => $genericName,
                'packing'              => $packing,
                'unit_1'               => '0',
                'unit_2'               => '0',
                'HSNCODE'              => $hsnCode,
                'CGST_per'             => $halfGst,
                'SGST_per'             => $halfGst,
                'company_name'         => $companyName,
                'company_id'           => $companyId,
                'mfgname'              => $companyName,
                're_order_qty'         => $minReorder,
                'is_continue'          => 1,
                'batch_applicable'     => 1,
                'exp_date_applicable'  => 1,
                'schedule_h'           => (stripos($drugSchedule, 'Schedule H1') === false && stripos($drugSchedule, 'Schedule H') !== false) ? 1 : 0,
                'schedule_h1'          => stripos($drugSchedule, 'Schedule H1') !== false ? 1 : 0,
                'schedule_x'           => stripos($drugSchedule, 'Schedule X') !== false ? 1 : 0,
                'schedule_g'           => stripos($drugSchedule, 'Schedule G') !== false ? 1 : 0,
                'narcotic'             => stripos($drugSchedule, 'Narcotic') !== false ? 1 : 0,
                'update_by'            => 'MedicalStore'
            ];

            $filteredPm = [];
            foreach ($pmData as $k => $v) {
                if (in_array($k, $pFields, true)) {
                    $filteredPm[$k] = $v;
                }
            }

            if ($pmRow) {
                $productMasterId = (int)$pmRow['id'];
                $this->db->table('med_product_master')->where('id', $productMasterId)->update($filteredPm);
            } else {
                if (in_array('insert_by', $pFields, true)) {
                    $filteredPm['insert_by'] = 'MedicalStore';
                }
                $this->db->table('med_product_master')->insert($filteredPm);
                $productMasterId = (int)$this->db->insertID();
            }
        }

        // 4. Save/mirror into mst_items
        $mstRow = $this->db->table('mst_items')
            ->where('LOWER(TRIM(item_name))', strtolower($itemName))
            ->get()
            ->getRowArray();

        $mstData = [
            'product_master_id' => $productMasterId > 0 ? $productMasterId : null,
            'item_name'         => $itemName,
            'generic_name'      => $genericName,
            'category'          => $formulation,
            'hsn_code'          => $hsnCode,
            'gst_rate'          => $gstRate,
            'unit_pack'         => $unitsPerPack . ' ' . $formulation,
            'units_per_pack'    => $unitsPerPack,
            'drug_schedule'     => $drugSchedule,
            'manufacturer_name' => $companyName,
            'min_reorder_level' => $minReorder,
            'is_active'         => 1,
            'updated_at'        => date('Y-m-d H:i:s')
        ];

        if ($mstRow) {
            $itemId = (int)$mstRow['item_id'];
            $this->db->table('mst_items')->where('item_id', $itemId)->update($mstData);
        } else {
            $mstData['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('mst_items')->insert($mstData);
            $itemId = (int)$this->db->insertID();
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 0,
                'message' => 'Database transaction failed while saving medicine to master.'
            ]);
        }

        return $this->response->setJSON([
            'status'  => 1,
            'message' => 'Medicine "' . $itemName . '" added to Master successfully!',
            'item'    => [
                'item_id'           => $itemId,
                'product_master_id' => $productMasterId,
                'item_name'         => $itemName,
                'generic_name'      => $genericName,
                'category'          => $formulation,
                'units_per_pack'    => $unitsPerPack,
                'packing'           => $packing,
                'hsn_code'          => $hsnCode,
                'gst_rate'          => $gstRate,
                'drug_schedule'     => $drugSchedule,
                'manufacturer_name' => $companyName
            ]
        ]);
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
        $returnItems = $json['return_items'] ?? [];

        if (empty($items) && empty($returnItems)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Cart is empty. Please add medicines to sell or return.']);
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

        // 1. Process New Sale Items
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

            // Record Stock Audit for Sale
            $this->db->table('mst_stock_audit')->insert([
                'store_id'      => $storeId,
                'item_id'       => $pi['itemId'],
                'batch_id'      => $pi['batchId'],
                'audit_type'    => 'SALE',
                'system_qty'    => (int)$batch['current_qty'],
                'physical_qty'  => max(0, (int)$batch['current_qty'] - $pi['totalUnits']),
                'variation_qty' => -$pi['totalUnits'],
                'rate'          => $pi['effectiveUnitPrice'],
                'total_value'   => $lineNet,
                'remarks'       => "Dispensed on Bill $invoiceNo",
                'conducted_by'  => (int)($json['user_id'] ?? 1),
                'created_at'    => date('Y-m-d H:i:s')
            ]);
        }

        // 2. Process Return / Exchange Items
        $grossReturnAmount = 0;
        $returnTaxableTotal = 0;
        $returnCgstTotal = 0;
        $returnSgstTotal = 0;
        $returnIgstTotal = 0;
        $validatedReturnItems = [];

        foreach ($returnItems as $rItem) {
            $rItemId = (int)($rItem['item_id'] ?? 0);
            $rBatchId = (int)($rItem['batch_id'] ?? 0);
            $rSellUnit = trim((string)($rItem['sell_unit'] ?? 'Tablet'));
            $rCondition = trim((string)($rItem['return_condition'] ?? 'RESTOCKED'));
            $rReason = trim((string)($rItem['return_reason'] ?? 'Customer Return / Exchange'));
            $refSaleId = !empty($rItem['ref_sale_id']) ? (int)$rItem['ref_sale_id'] : null;
            $refInvoiceNo = trim((string)($rItem['ref_invoice_no'] ?? ''));

            // Fetch batch & item details
            $rBatch = $this->db->table('mst_batches b')
                ->select('b.*, i.item_name, i.generic_name, i.drug_schedule, i.hsn_code, i.unit_pack, i.units_per_pack')
                ->join('mst_items i', 'i.item_id = b.item_id')
                ->where('b.batch_id', $rBatchId)
                ->get()
                ->getRowArray();

            if (!$rBatch && !empty($rItem['batch_no'])) {
                $rBatch = $this->db->table('mst_batches b')
                    ->select('b.*, i.item_name, i.generic_name, i.drug_schedule, i.hsn_code, i.unit_pack, i.units_per_pack')
                    ->join('mst_items i', 'i.item_id = b.item_id')
                    ->where('b.item_id', $rItemId)
                    ->where('b.batch_no', trim($rItem['batch_no']))
                    ->get()
                    ->getRowArray();
            }

            if (!$rBatch) {
                $this->db->transRollback();
                return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => "Return batch details not found for item #$rItemId."]);
            }

            $rUnitsPerPack = (int)($rBatch['units_per_pack'] ?? 1);
            if ($rUnitsPerPack <= 0) $rUnitsPerPack = 1;

            if ($rUnitsPerPack <= 1 || in_array($rSellUnit, ['Unit', 'Tablet', 'Loose'])) {
                $returnTotalUnits = max(1, (int)($rItem['qty'] ?? $rItem['loose_qty'] ?? 1));
                $dispQty = $returnTotalUnits;
                $rSellUnit = ($rUnitsPerPack > 1) ? 'Tablet' : ($rBatch['unit_pack'] ?: 'Unit');
            } else { // 'Strip'
                $sq = max(1, (int)($rItem['qty'] ?? 1));
                $returnTotalUnits = $sq * $rUnitsPerPack;
                $dispQty = $sq;
                $rSellUnit = 'Strip';
            }

            // Determine refund rate
            $unitMrp = (float)$rBatch['mrp'];
            $perUnitMrp = $rUnitsPerPack > 0 ? ($unitMrp / $rUnitsPerPack) : $unitMrp;

            if (isset($rItem['unit_price']) && (float)$rItem['unit_price'] > 0) {
                $effectiveRefundUnitRate = (float)$rItem['unit_price'];
            } elseif ($refSaleId) {
                $origSi = $this->db->table('mst_sales_items')
                    ->where('sale_id', $refSaleId)
                    ->where('item_id', $rItemId)
                    ->where('batch_id', (int)$rBatch['batch_id'])
                    ->get()->getRowArray();
                if ($origSi && (int)$origSi['total_units'] > 0) {
                    $effectiveRefundUnitRate = round((float)$origSi['total_amount'] / (int)$origSi['total_units'], 2);
                } else {
                    $effectiveRefundUnitRate = round($perUnitMrp, 2);
                }
            } else {
                $effectiveRefundUnitRate = round($perUnitMrp, 2);
            }

            $lineReturnGross = round($returnTotalUnits * $effectiveRefundUnitRate, 2);
            $grossReturnAmount += $lineReturnGross;

            // Back-Calculated GST on return line
            $rGstRate = (float)$rBatch['gst_rate'];
            if ($rGstRate > 0) {
                $rTaxable = round($lineReturnGross / (1 + ($rGstRate / 100)), 2);
                $rGstAmt = round($lineReturnGross - $rTaxable, 2);
            } else {
                $rTaxable = $lineReturnGross;
                $rGstAmt = 0;
            }

            if ($isInterState) {
                $rCgst = 0;
                $rSgst = 0;
                $rIgst = $rGstAmt;
            } else {
                $rCgst = round($rGstAmt / 2, 2);
                $rSgst = round($rGstAmt - $rCgst, 2);
                $rIgst = 0;
            }

            $returnTaxableTotal += $rTaxable;
            $returnCgstTotal += $rCgst;
            $returnSgstTotal += $rSgst;
            $returnIgstTotal += $rIgst;

            $validatedReturnItems[] = [
                'item_id'          => $rItemId,
                'item_name'        => $rBatch['item_name'],
                'batch_id'         => (int)$rBatch['batch_id'],
                'batch_no'         => $rBatch['batch_no'],
                'expiry_date'      => $rBatch['expiry_date'],
                'qty'              => $dispQty,
                'sell_unit'        => $rSellUnit,
                'units_per_pack'   => $rUnitsPerPack,
                'total_units'      => $returnTotalUnits,
                'unit_price'       => $effectiveRefundUnitRate,
                'unit_mrp'         => $unitMrp,
                'return_condition' => $rCondition,
                'return_reason'    => $rReason,
                'ref_sale_id'      => $refSaleId,
                'ref_invoice_no'   => $refInvoiceNo,
                'hsn_code'         => $rBatch['hsn_code'] ?: '3004',
                'gst_rate'         => $rGstRate,
                'taxable_value'    => $rTaxable,
                'cgst_amount'      => $rCgst,
                'sgst_amount'      => $rSgst,
                'igst_amount'      => $rIgst,
                'total_amount'     => -$lineReturnGross,
                'line_refund_val'  => $lineReturnGross
            ];

            // Replenish stock if RESTOCKED
            if ($rCondition === 'RESTOCKED') {
                $stkRow = $this->db->table('mst_stock')
                    ->where('store_id', $storeId)
                    ->where('batch_id', (int)$rBatch['batch_id'])
                    ->get()
                    ->getRowArray();

                if ($stkRow) {
                    $this->db->table('mst_stock')
                        ->where('stock_id', $stkRow['stock_id'])
                        ->set('current_qty', 'current_qty + ' . $returnTotalUnits, false)
                        ->update();
                } else {
                    $this->db->table('mst_stock')->insert([
                        'store_id'        => $storeId,
                        'item_id'         => $rItemId,
                        'batch_id'        => (int)$rBatch['batch_id'],
                        'current_qty'     => $returnTotalUnits,
                        'reserved_qty'    => 0,
                        'last_updated_at' => date('Y-m-d H:i:s')
                    ]);
                }

                $this->db->table('mst_stock_audit')->insert([
                    'store_id'      => $storeId,
                    'item_id'       => $rItemId,
                    'batch_id'      => (int)$rBatch['batch_id'],
                    'audit_type'    => 'SALES_RETURN',
                    'system_qty'    => $stkRow ? (int)$stkRow['current_qty'] : 0,
                    'physical_qty'  => ($stkRow ? (int)$stkRow['current_qty'] : 0) + $returnTotalUnits,
                    'variation_qty' => $returnTotalUnits,
                    'rate'          => $effectiveRefundUnitRate,
                    'total_value'   => $lineReturnGross,
                    'remarks'       => "Customer Return/Exchange Restock (Bill $invoiceNo, Ref: $refInvoiceNo)",
                    'conducted_by'  => (int)($json['user_id'] ?? 1),
                    'created_at'    => date('Y-m-d H:i:s')
                ]);
            } else {
                $this->db->table('mst_stock_audit')->insert([
                    'store_id'      => $storeId,
                    'item_id'       => $rItemId,
                    'batch_id'      => (int)$rBatch['batch_id'],
                    'audit_type'    => 'RETURN_DAMAGED_DISCARD',
                    'system_qty'    => 0,
                    'physical_qty'  => 0,
                    'variation_qty' => 0,
                    'rate'          => $effectiveRefundUnitRate,
                    'total_value'   => $lineReturnGross,
                    'remarks'       => "Customer returned damaged/discarded medicine (Bill $invoiceNo)",
                    'conducted_by'  => (int)($json['user_id'] ?? 1),
                    'created_at'    => date('Y-m-d H:i:s')
                ]);
            }
        }

        // 3. Net Calculation
        $netSalesBeforeRound = $grossAmount - $totalDiscount;
        $netBeforeRound = $netSalesBeforeRound - $grossReturnAmount;
        $netRounded = round($netBeforeRound);
        $roundOff = round($netRounded - $netBeforeRound, 2);

        $isExchangeBill = (!empty($validatedReturnItems) ? 1 : 0);
        $refundAmount = 0.00;
        $refundMode = null;
        $refundRefNo = null;

        if ($netRounded >= 0) {
            $finalNetPayable = $netRounded;
            $paymentMode = trim($json['payment_mode'] ?? 'Cash'); // Cash, UPI, Card, Mixed, IPD_Credit
            $cashPaid = (float)($json['cash_paid'] ?? ($paymentMode === 'Cash' ? $finalNetPayable : 0));
            $upiPaid = (float)($json['upi_paid'] ?? ($paymentMode === 'UPI' ? $finalNetPayable : 0));
            $cardPaid = (float)($json['card_paid'] ?? ($paymentMode === 'Card' ? $finalNetPayable : 0));
            $creditAmount = (float)($json['credit_amount'] ?? ($paymentMode === 'IPD_Credit' ? $finalNetPayable : 0));
        } else {
            // Net is negative: Pharmacy refunds difference to patient
            $finalNetPayable = 0.00;
            $refundAmount = abs($netRounded);
            $refundMode = trim($json['refund_mode'] ?? ($json['payment_mode'] ?? 'Cash'));
            $refundRefNo = trim($json['refund_ref_no'] ?? ($json['upi_ref_no'] ?? ''));
            $paymentMode = 'REFUND_' . strtoupper($refundMode);
            $cashPaid = 0;
            $upiPaid = 0;
            $cardPaid = 0;
            $creditAmount = 0;
        }

        $bankName = trim($json['bank_name'] ?? '');
        $upiRefNo = trim($json['upi_ref_no'] ?? '');
        $cardRefNo = trim($json['card_ref_no'] ?? '');

        // ABDM Integration: Extract ABHA ID and ABHA Address
        $patientId = !empty($json['patient_id']) ? (int)$json['patient_id'] : null;
        $abhaId = trim((string)($json['abha_id'] ?? ''));
        $abhaAddress = trim((string)($json['abha_address'] ?? ''));

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

        // Generate unique ABDM Care Context Reference
        $cleanPrefix = preg_replace('/[^A-Za-z0-9]/', '', $store['store_code'] ?: 'STORE');
        $cleanInv = preg_replace('/[^A-Za-z0-9]/', '', $invoiceNo);
        $careContextRef = "PHARM-{$cleanPrefix}-{$cleanInv}";
        $careContextDisplay = "Pharmacy Dispensation - {$invoiceNo}";

        // Build official ABDM FHIR R4 MedicationDispense Document Bundle (only for sale items if any)
        $fhirBundleJson = null;
        if (!empty($validatedItems)) {
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
        }

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
            'return_amount'              => $grossReturnAmount,
            'is_exchange_bill'           => $isExchangeBill,
            'discount_amount'            => $totalDiscount,
            'bill_discount_type'         => $wholeDiscType,
            'bill_discount_val'          => $wholeDiscVal,
            'taxable_amount'             => max(0, $taxableTotal - $returnTaxableTotal),
            'cgst_amount'                => max(0, $cgstTotal - $returnCgstTotal),
            'sgst_amount'                => max(0, $sgstTotal - $returnSgstTotal),
            'igst_amount'                => max(0, $igstTotal - $returnIgstTotal),
            'round_off'                  => $roundOff,
            'net_amount'                 => $finalNetPayable,
            'refund_amount'              => $refundAmount,
            'refund_mode'                => $refundMode,
            'refund_ref_no'              => $refundRefNo,
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

        // If IPD Credit, link into ipd_invoice_item so hospital discharge handles it
        if ($paymentMode === 'IPD_Credit' && !empty($json['ipd_id'])) {
            $ipdId = (int)$json['ipd_id'];
            if ($this->db->tableExists('ipd_invoice_item')) {
                $this->db->table('ipd_invoice_item')->insert([
                    'ipd_id'        => $ipdId,
                    'item_name'     => "Pharmacy Medicines ({$invoiceNo})",
                    'item_desc'     => "Medicines dispensed from {$store['store_name']}",
                    'item_qty'      => 1,
                    'item_price'    => $finalNetPayable,
                    'item_amount'   => $finalNetPayable,
                    'insert_date'   => date('Y-m-d H:i:s'),
                ]);
                $saleData['ipd_charge_id'] = $this->db->insertID();
            }
        }

        $this->db->table('mst_sales')->insert($saleData);
        $saleId = $this->db->insertID();

        // Queue in central ABDM Gateway Sync Outbox if applicable
        if ($fhirBundleJson && (!empty($abhaAddress) || !empty($patientId)) && $this->db->tableExists('abdm_sync_record')) {
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
                'item_type'       => 'SALE',
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

        // Insert Return Items
        foreach ($validatedReturnItems as $vri) {
            $this->db->table('mst_sales_items')->insert([
                'sale_id'          => $saleId,
                'item_id'          => $vri['item_id'],
                'item_type'        => 'RETURN',
                'return_condition' => $vri['return_condition'],
                'ref_sale_id'      => $vri['ref_sale_id'],
                'ref_invoice_no'   => $vri['ref_invoice_no'],
                'return_reason'    => $vri['return_reason'],
                'is_restocked'     => ($vri['return_condition'] === 'RESTOCKED' ? 1 : 0),
                'batch_id'         => $vri['batch_id'],
                'batch_no'         => $vri['batch_no'],
                'expiry_date'      => $vri['expiry_date'],
                'qty'              => $vri['qty'],
                'sell_unit'        => $vri['sell_unit'],
                'units_per_pack'   => $vri['units_per_pack'],
                'total_units'      => $vri['total_units'],
                'unit_price'       => $vri['unit_price'],
                'loose_qty'        => ($vri['sell_unit'] === 'Tablet' ? $vri['total_units'] : 0),
                'pack_qty'         => round($vri['total_units'] / $vri['units_per_pack'], 2),
                'unit_mrp'         => $vri['unit_mrp'],
                'discount_pct'     => 0,
                'discount_amount'  => 0,
                'hsn_code'         => $vri['hsn_code'],
                'gst_rate'         => $vri['gst_rate'],
                'taxable_value'    => -$vri['taxable_value'],
                'cgst_amount'      => -$vri['cgst_amount'],
                'sgst_amount'      => -$vri['sgst_amount'],
                'igst_amount'      => -$vri['igst_amount'],
                'total_amount'     => -$vri['line_refund_val']
            ]);
        }

        // Standalone / Formal Credit Note Record if returns exist
        $creditNoteNo = null;
        if ($isExchangeBill) {
            $cnPrefix = $store['credit_note_prefix'] ?: 'CRN/';
            $cnSeq = (int)($store['next_credit_note_no'] ?? 1);
            $creditNoteNo = $cnPrefix . str_pad((string)$cnSeq, 5, '0', STR_PAD_LEFT);
            $this->db->table('mst_stores')->where('store_id', $storeId)->update(['next_credit_note_no' => $cnSeq + 1]);

            $firstRefInvoice = $validatedReturnItems[0]['ref_invoice_no'] ?? $invoiceNo;
            $firstRefSaleId = $validatedReturnItems[0]['ref_sale_id'] ?? null;

            $this->db->table('mst_sale_returns')->insert([
                'store_id'                 => $storeId,
                'credit_note_no'           => $creditNoteNo,
                'return_date'              => date('Y-m-d H:i:s'),
                'original_sale_id'         => $firstRefSaleId,
                'original_invoice_no'      => $firstRefInvoice ?: 'EXCHANGE',
                'new_sale_id'              => $saleId,
                'patient_id'               => $patientId,
                'uhid'                     => $json['uhid'] ?? null,
                'patient_name'             => trim($json['patient_name'] ?? 'Customer'),
                'patient_mobile'           => trim($json['patient_mobile'] ?? ''),
                'return_reason'            => $validatedReturnItems[0]['return_reason'] ?? 'Exchange Adjustment on ' . $invoiceNo,
                'gross_refund_amount'      => $grossReturnAmount,
                'discount_reversed_amount' => 0.00,
                'taxable_refund_amount'    => $returnTaxableTotal,
                'cgst_refund_amount'       => $returnCgstTotal,
                'sgst_refund_amount'       => $returnSgstTotal,
                'igst_refund_amount'       => $returnIgstTotal,
                'round_off'                => 0.00,
                'net_refund_amount'        => $grossReturnAmount,
                'refund_mode'              => $refundAmount > 0 ? ($refundMode ?: 'Cash') : 'EXCHANGE_BILL_ADJUSTMENT',
                'refund_ref_no'            => $refundRefNo ?: $invoiceNo,
                'restock_condition'        => $validatedReturnItems[0]['return_condition'] ?? 'RESTOCKED',
                'remarks'                  => "Adjusted against invoice $invoiceNo",
                'created_by'               => (int)($json['user_id'] ?? 1),
                'created_at'               => date('Y-m-d H:i:s')
            ]);
            $returnRecordId = $this->db->insertID();

            foreach ($validatedReturnItems as $vri) {
                $this->db->table('mst_sale_return_items')->insert([
                    'return_id'          => $returnRecordId,
                    'sale_item_id'       => null,
                    'item_id'            => $vri['item_id'],
                    'item_name'          => $vri['item_name'],
                    'batch_id'           => $vri['batch_id'],
                    'batch_no'           => $vri['batch_no'],
                    'expiry_date'        => $vri['expiry_date'],
                    'return_sell_unit'   => $vri['sell_unit'],
                    'return_qty'         => $vri['qty'],
                    'units_per_pack'     => $vri['units_per_pack'],
                    'return_total_units' => $vri['total_units'],
                    'unit_price'         => $vri['unit_price'],
                    'refund_rate'        => $vri['unit_price'],
                    'discount_pct'       => 0.00,
                    'discount_amount'    => 0.00,
                    'hsn_code'           => $vri['hsn_code'],
                    'gst_rate'           => $vri['gst_rate'],
                    'taxable_value'      => $vri['taxable_value'],
                    'cgst_amount'        => $vri['cgst_amount'],
                    'sgst_amount'        => $vri['sgst_amount'],
                    'igst_amount'        => $vri['igst_amount'],
                    'refund_amount'      => $vri['line_refund_val'],
                    'is_restocked'       => ($vri['return_condition'] === 'RESTOCKED' ? 1 : 0)
                ]);
            }
        }

        // Double-Entry Journal Postings
        $salesHead  = $this->db->table('mst_account_heads')->where('head_code', '3001')->get()->getRowArray();
        $retHead    = $this->db->table('mst_account_heads')->where('head_code', '3005')->get()->getRowArray();
        $cgstHead   = $this->db->table('mst_account_heads')->where('head_code', '2002')->get()->getRowArray();
        $sgstHead   = $this->db->table('mst_account_heads')->where('head_code', '2003')->get()->getRowArray();
        $igstHead   = $this->db->table('mst_account_heads')->where('head_code', '2004')->get()->getRowArray();
        $discHead   = $this->db->table('mst_account_heads')->where('head_code', '4002')->get()->getRowArray();
        $cashHead   = $this->db->table('mst_account_heads')->where('head_code', '1001')->get()->getRowArray();
        $bankHead   = $this->db->table('mst_account_heads')->where('head_code', '1002')->get()->getRowArray();

        // 1. Debit Cash/Bank/Debtor for positive payments
        if ($cashPaid > 0 && $cashHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $cashHead['head_id'], 'debit_amount' => $cashPaid, 'credit_amount' => 0,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Cash received for bill $invoiceNo"
            ]);
        }
        if (($upiPaid + $cardPaid) > 0 && $bankHead) {
            $refDetails = array_filter([$bankName ? "Bank: $bankName" : null, $upiRefNo ? "UPI Ref: $upiRefNo" : null, $cardRefNo ? "Card Auth: $cardRefNo" : null]);
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
            if ($debtorHead) {
                $this->db->table('mst_ledger_entries')->insert([
                    'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                    'account_head_id' => $debtorHead['head_id'], 'debit_amount' => $creditAmount, 'credit_amount' => 0,
                    'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Credit/IPD charge for bill $invoiceNo"
                ]);
            }
        }

        // If net refund was paid to customer:
        if ($refundAmount > 0) {
            $refundAccountHead = ($refundMode === 'UPI' && $bankHead) ? $bankHead : $cashHead;
            if ($refundAccountHead) {
                $this->db->table('mst_ledger_entries')->insert([
                    'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALES_RETURN', 'voucher_date' => date('Y-m-d'),
                    'account_head_id' => $refundAccountHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $refundAmount,
                    'reference_type' => 'sales_return', 'reference_id' => $saleId, 'narration' => "Refund paid to customer ($refundMode) on bill $invoiceNo"
                ]);
            }
        }

        // Discount allowed
        if ($totalDiscount > 0 && $discHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $discHead['head_id'], 'debit_amount' => $totalDiscount, 'credit_amount' => 0,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Discount allowed on bill $invoiceNo"
            ]);
        }

        // Credit Sales & Output Tax for New Items
        if ($taxableTotal > 0 && $salesHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $salesHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $taxableTotal,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Taxable Pharmacy Sales $invoiceNo"
            ]);
        }

        // Debit Sales Returns for returned items
        if ($returnTaxableTotal > 0 && $retHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALES_RETURN', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $retHead['head_id'], 'debit_amount' => $returnTaxableTotal, 'credit_amount' => 0,
                'reference_type' => 'sales_return', 'reference_id' => $saleId, 'narration' => "Sales Return adjustment on bill $invoiceNo"
            ]);
        }

        // Net GST Output Liability
        $netCgst = max(0, $cgstTotal - $returnCgstTotal);
        $netSgst = max(0, $sgstTotal - $returnSgstTotal);
        $netIgst = max(0, $igstTotal - $returnIgstTotal);

        if ($netCgst > 0 && $cgstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $cgstHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $netCgst,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Output CGST on $invoiceNo"
            ]);
        }
        if ($netSgst > 0 && $sgstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $sgstHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $netSgst,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Output SGST on $invoiceNo"
            ]);
        }
        if ($netIgst > 0 && $igstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $invoiceNo, 'voucher_type' => 'SALE', 'voucher_date' => date('Y-m-d'),
                'account_head_id' => $igstHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $netIgst,
                'reference_type' => 'sales_invoice', 'reference_id' => $saleId, 'narration' => "Output IGST on $invoiceNo"
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to process sales/exchange transaction.']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => $isExchangeBill ? ($refundAmount > 0 ? 'Exchange/Return completed with patient refund.' : 'Exchange bill generated successfully.') : 'Invoice generated successfully.',
            'sale_id' => $saleId,
            'invoice_no' => $invoiceNo,
            'credit_note_no' => $creditNoteNo,
            'gross_sales' => round($grossAmount, 2),
            'return_amount' => round($grossReturnAmount, 2),
            'is_exchange_bill' => $isExchangeBill,
            'net_amount' => $finalNetPayable,
            'refund_amount' => $refundAmount,
            'refund_mode' => $refundMode,
            'abdm' => [
                'abha_id' => $abhaId,
                'abha_address' => $abhaAddress,
                'care_context_ref' => $careContextRef,
                'is_abdm_linked' => (!empty($abhaAddress) || !empty($abhaId))
            ]
        ]);
    }

    protected function getInvoiceData(int $saleId): ?array
    {
        $sale = $this->db->table('mst_sales s')
            ->select('s.*, st.store_name, st.building_name, st.floor_no, st.room_no, st.drug_license_no_20b, st.drug_license_no_21b, st.drug_license_no_20f_x, st.gstin, st.pan_no, st.fssai_no, st.state_code, st.state_name, st.registered_pharmacist_name, st.pharmacist_reg_no, st.contact_phone, st.contact_email, st.address as store_address, st.upi_id, st.terms_conditions, st.abdm_hfr_id, st.abdm_hip_id, st.pharmacist_hpr_id')
            ->join('mst_stores st', 'st.store_id = s.store_id')
            ->where('s.sale_id', $saleId)
            ->get()
            ->getRowArray();

        if (!$sale) {
            return null;
        }

        $items = $this->db->table('mst_sales_items si')
            ->select('si.*, i.item_name, i.generic_name, i.category, i.unit_pack, i.drug_schedule, i.snomed_ct_code, i.snomed_display')
            ->join('mst_items i', 'i.item_id = si.item_id')
            ->where('si.sale_id', $saleId)
            ->get()
            ->getResultArray();

        $saleItems = [];
        $returnItems = [];
        foreach ($items as $it) {
            if (($it['item_type'] ?? 'SALE') === 'RETURN') {
                $returnItems[] = $it;
            } else {
                $saleItems[] = $it;
            }
        }

        // Build HSN summary table for Indian GST invoice standard (for sale items)
        $hsnSummary = [];
        foreach ($saleItems as $it) {
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

        // Check if there is a linked credit note in mst_sale_returns
        $creditNote = null;
        if (!empty($sale['is_exchange_bill'])) {
            $creditNote = $this->db->table('mst_sale_returns')->where('new_sale_id', $saleId)->get()->getRowArray();
        }

        // Dynamic UPI QR string
        $upiQrString = '';
        if (!empty($sale['upi_id'])) {
            $upiQrString = "upi://pay?pa={$sale['upi_id']}&pn=" . urlencode($sale['store_name']) . "&am={$sale['net_amount']}&cu=INR&tn=" . urlencode("Bill " . $sale['invoice_no']);
        }

        return [
            'sale' => $sale,
            'items' => $saleItems,
            'return_items' => $returnItems,
            'all_items' => $items,
            'credit_note' => $creditNote,
            'hsn_summary' => array_values($hsnSummary),
            'upi_qr_string' => $upiQrString
        ];
    }

    public function getInvoice(int $saleId)
    {
        $data = $this->getInvoiceData($saleId);
        if (!$data) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Invoice not found.']);
        }

        return $this->response->setJSON(array_merge(['status' => 1], $data));
    }

    public function invoicePdf(int $saleId)
    {
        $data = $this->getInvoiceData($saleId);
        if (!$data) {
            return $this->response->setStatusCode(404)->setBody('Invoice not found.');
        }

        $format = strtolower($this->request->getGet('format') ?? 'a4');
        $download = (bool)($this->request->getGet('download') ?? false);
        $isA5 = ($format === 'a5');

        $tmpDir = WRITEPATH . 'cache/mpdf';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }

        $mpdfConfig = [
            'tempDir'       => $tmpDir,
            'mode'          => 'utf-8',
            'format'        => $isA5 ? 'A5-L' : 'A4',
            'orientation'   => $isA5 ? 'L' : 'P',
            'margin_left'   => $isA5 ? 6 : 8,
            'margin_right'  => $isA5 ? 6 : 8,
            'margin_top'    => $isA5 ? 5 : 8,
            'margin_bottom' => $isA5 ? 5 : 8,
            'default_font'  => 'dejavusans'
        ];

        try {
            $mpdf = new \Mpdf\Mpdf($mpdfConfig);
            $html = $this->renderInvoicePdfHtml($data, $isA5);
            $mpdf->WriteHTML($html);

            $safeInv = preg_replace('/[^A-Za-z0-9_-]/', '_', $data['sale']['invoice_no'] ?: ('INV_' . $saleId));
            $fileName = "Invoice_{$safeInv}_" . ($isA5 ? 'a5_landscape' : $format) . ".pdf";

            $pdfBinary = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename="' . $fileName . '"')
                ->setBody($pdfBinary);
        } catch (\Throwable $e) {
            log_message('error', 'MedicalStoreApi::invoicePdf failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('Failed to generate PDF: ' . $e->getMessage());
        }
    }

    protected function renderInvoicePdfHtml(array $data, bool $isA5): string
    {
        $sale = $data['sale'];
        $items = $data['items'];
        $returnItems = $data['return_items'];
        $creditNote = $data['credit_note'];
        $hsnSummary = $data['hsn_summary'];
        $upiQrString = $data['upi_qr_string'];

        $esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
        $curr = fn($v) => '&#8377;' . number_format((float)($v ?? 0), 2, '.', '');

        $fontSize = $isA5 ? '7pt' : '8pt';
        $thPadding = $isA5 ? '2.5px 3.5px' : '4px 5px';
        $tdPadding = $isA5 ? '2.5px 3.5px' : '3.5px 5px';

        $qrCodeValue = $upiQrString ?: $sale['invoice_no'];
        $qrHtml = '';
        if (!empty($qrCodeValue)) {
            $qrSize = $isA5 ? '0.55' : '0.65';
            $qrHtml = '<barcode code="' . $esc($qrCodeValue) . '" size="' . $qrSize . '" type="QR" error="M" class="barcode" />';
        }

        $html = '
        <html>
        <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: dejavusans, sans-serif;
                font-size: ' . $fontSize . ';
                color: #0f172a;
                line-height: 1.25;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            .header-table td {
                vertical-align: top;
            }
            .border-box {
                border: 0.3mm solid #94a3b8;
                border-radius: 1.5mm;
                padding: ' . ($isA5 ? '4px' : '6px') . ';
                background-color: #f8fafc;
                margin-bottom: ' . ($isA5 ? '4px' : '6px') . ';
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: ' . ($isA5 ? '4px' : '6px') . ';
            }
            .items-table th {
                background-color: #0f766e;
                color: #ffffff;
                font-weight: bold;
                font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . ';
                padding: ' . $thPadding . ';
                border: 0.2mm solid #0f766e;
                text-align: left;
            }
            .items-table td {
                padding: ' . $tdPadding . ';
                border: 0.2mm solid #cbd5e1;
                font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . ';
                vertical-align: top;
            }
            .items-table tr:nth-child(even) td {
                background-color: #f8fafc;
            }
            .return-table th {
                background-color: #be123c !important;
                border-color: #be123c !important;
            }
            .return-table td {
                border-color: #fecdd3 !important;
                background-color: #fff1f2 !important;
                color: #9f1239;
            }
            .hsn-table th {
                background-color: #334155;
                color: #ffffff;
                font-size: ' . ($isA5 ? '6pt' : '7pt') . ';
                padding: ' . ($isA5 ? '2px 3px' : '3px 4px') . ';
                border: 0.2mm solid #334155;
            }
            .hsn-table td {
                font-size: ' . ($isA5 ? '6pt' : '7pt') . ';
                padding: ' . ($isA5 ? '2px 3px' : '3px 4px') . ';
                border: 0.2mm solid #cbd5e1;
                text-align: center;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .fw-bold { font-weight: bold; }
            .text-muted { color: #64748b; }
            .text-primary { color: #0284c7; }
            .text-danger { color: #dc2626; }
            .text-success { color: #16a34a; }
            .badge {
                display: inline-block;
                padding: 1px 4px;
                border-radius: 1mm;
                font-size: 6pt;
                font-weight: bold;
            }
            .badge-teal { background-color: #ccfbf1; color: #0f766e; border: 0.2mm solid #14b8a6; }
            .badge-green { background-color: #dcfce7; color: #15803d; border: 0.2mm solid #22c55e; }
            .badge-blue { background-color: #e0f2fe; color: #0369a1; border: 0.2mm solid #0ea5e9; }
            .total-box {
                background-color: #f0fdf4;
                border: 0.4mm solid #16a34a;
                padding: ' . ($isA5 ? '3px 5px' : '5px 7px') . ';
                border-radius: 1.5mm;
                color: #14532d;
            }
        </style>
        </head>
        <body>';

        // 1. Header Table
        $html .= '
        <table class="header-table" style="margin-bottom: ' . ($isA5 ? '4px' : '8px') . ';">
            <tr>
                <td style="width: 66%;">
                    <div style="font-size: ' . ($isA5 ? '11pt' : '13pt') . '; font-weight: bold; color: #0f766e; margin-bottom: 2px;">
                        ' . $esc($sale['store_name'] ?: 'City Hospital Central Pharmacy') . '
                    </div>
                    <div style="color: #475569; font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . ';">
                        ' . $esc($sale['building_name'] ?: 'Main Hospital Block A') . ', ' . $esc($sale['floor_no'] ?: 'Ground Floor') . ($sale['store_address'] ? ', ' . $esc($sale['store_address']) : '') . '
                    </div>
                    <div style="font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . '; margin-top: 2px;">
                        <strong>DL 20-B:</strong> ' . $esc($sale['drug_license_no_20b'] ?: 'N/A') . ' | <strong>DL 21-B:</strong> ' . $esc($sale['drug_license_no_21b'] ?: 'N/A') . '
                        ' . ($sale['fssai_no'] ? ' | <strong>FSSAI:</strong> ' . $esc($sale['fssai_no']) : '') . '
                    </div>
                    <div style="font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . ';">
                        <strong>GSTIN:</strong> ' . $esc($sale['gstin'] ?: 'N/A') . ' | <strong>State:</strong> ' . $esc($sale['state_name'] ?: 'Delhi') . ' (Code: ' . $esc($sale['state_code'] ?: '07') . ')
                        ' . ($sale['abdm_hfr_id'] ? ' | <strong>ABDM HFR:</strong> ' . $esc($sale['abdm_hfr_id']) : '') . '
                    </div>
                </td>
                <td style="width: 34%; text-align: right;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="text-align: right; vertical-align: top;">
                                <div style="display: inline-block; background-color: #0f766e; color: #ffffff; padding: 2px 6px; font-weight: bold; font-size: ' . ($isA5 ? '7pt' : '8pt') . '; border-radius: 1mm; margin-bottom: 2px;">
                                    ' . ($isA5 ? 'RETAIL TAX INVOICE' : 'HOSPITAL GST TAX INVOICE') . '
                                </div>
                                <div style="font-size: ' . ($isA5 ? '9pt' : '11pt') . '; font-weight: bold; color: #0f172a;">
                                    ' . $esc($sale['invoice_no']) . '
                                </div>
                                <div style="color: #64748b; font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . ';">
                                    ' . $esc($sale['sale_date']) . '
                                </div>
                            </td>
                            ' . ($qrHtml ? '<td style="width: 45px; text-align: right; vertical-align: top; padding-left: 5px;">' . $qrHtml . '</td>' : '') . '
                        </tr>
                    </table>
                </td>
            </tr>
        </table>';

        // 2. Patient & Doctor Info Table
        $html .= '
        <div class="border-box">
            <table style="font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . ';">
                <tr>
                    <td style="width: 35%;">
                        <strong>Patient Name:</strong> ' . $esc($sale['patient_name'] ?: 'Walk-in Customer') . '<br>
                        <strong>UHID No:</strong> <span class="text-primary fw-bold">' . $esc($sale['uhid'] ?: 'Walk-in') . '</span>
                    </td>
                    <td style="width: 30%;">
                        <strong>Phone:</strong> ' . $esc($sale['patient_mobile'] ?: 'N/A') . '<br>
                        <strong>Age / Gender:</strong> ' . $esc($sale['age'] ?: 'N/A') . ' / ' . $esc($sale['gender'] ?: 'N/A') . '
                    </td>
                    <td style="width: 35%;">
                        <strong>Prescribing Doctor:</strong> ' . $esc($sale['doctor_name'] ?: 'Dr. Consultant') . '<br>
                        <strong>Doctor Reg No:</strong> ' . $esc($sale['doctor_reg_no'] ?: 'N/A') . '
                    </td>
                </tr>';

        if (!empty($sale['abha_id']) || !empty($sale['abha_address']) || !empty($sale['abdm_care_context_ref'])) {
            $html .= '
                <tr>
                    <td colspan="3" style="padding-top: 3px; border-top: 0.2mm solid #e2e8f0; color: #15803d; font-weight: bold;">
                        &#10003; ABDM Linked: ABHA No: ' . $esc($sale['abha_id'] ?: 'N/A') . ' | Address: ' . $esc($sale['abha_address'] ?: 'N/A') . ($sale['abdm_care_context_ref'] ? ' | Care Context: ' . $esc($sale['abdm_care_context_ref']) : '') . '
                    </td>
                </tr>';
        }

        if (!empty($sale['ipd_id'])) {
            $html .= '
                <tr>
                    <td colspan="3" style="padding-top: 2px; border-top: 0.2mm solid #e2e8f0; color: #b91c1c; font-weight: bold;">
                        &#127973; IPD Admission #' . $esc($sale['ipd_id']) . ' &bull; Ward: ' . $esc($sale['ward_name'] ?: 'General') . ' &bull; Bed: ' . $esc($sale['bed_no'] ?: 'Bed') . ' (Charged to IPD Running Bill)
                    </td>
                </tr>';
        }

        $html .= '
            </table>
        </div>';

        // 3. Dispensed Medicines Table
        $html .= '
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 3%; text-align: center;">#</th>
                    <th style="width: 27%;">Medicine &amp; Formulation</th>
                    <th style="width: 8%; text-align: center;">HSN</th>
                    <th style="width: 10%; text-align: center;">Batch</th>
                    <th style="width: 8%; text-align: center;">Exp</th>
                    <th style="width: 10%; text-align: center;">Qty &amp; Unit</th>
                    <th style="width: 8%; text-align: right;">Rate</th>
                    <th style="width: 8%; text-align: right;">Taxable</th>
                    <th style="width: 5%; text-align: right;">CGST</th>
                    <th style="width: 5%; text-align: right;">SGST</th>
                    <th style="width: 8%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>';

        if (empty($items)) {
            $html .= '
                <tr>
                    <td colspan="11" class="text-center text-muted" style="padding: 10px;">
                        - No new medicines dispensed (Sales Return &amp; Customer Refund Bill) -
                    </td>
                </tr>';
        } else {
            foreach ($items as $idx => $it) {
                $qtyUnitStr = '';
                if ($it['sell_unit'] === 'Tablet') {
                    $qtyUnitStr = $it['total_units'] . ' Tab';
                } elseif ($it['sell_unit'] === 'Combo') {
                    $qtyUnitStr = $it['qty'] . ' Str + ' . $it['loose_qty'] . ' Tab';
                } else {
                    $qtyUnitStr = ((int)$it['units_per_pack'] > 1) ? ($it['qty'] . ' Strip') : ($it['qty'] . ' ' . ($it['unit_pack'] ?: 'Unit'));
                }

                $rateStr = ($it['sell_unit'] === 'Tablet') ? ($curr($it['unit_price']) . '<span style="font-size:5.5pt;color:#64748b;">/tab</span>') : $curr($it['unit_mrp']);

                $html .= '
                <tr>
                    <td style="text-align: center;">' . ($idx + 1) . '</td>
                    <td>
                        <strong style="color: #0f172a;">' . $esc($it['item_name']) . '</strong>';
                if ((int)$it['units_per_pack'] > 1 && $it['sell_unit'] !== 'Tablet') {
                    $html .= '<div style="font-size: 6pt; color: #64748b;">Pack: 1 Strip of ' . $esc($it['units_per_pack']) . ' Tabs</div>';
                }
                if (!empty($it['snomed_ct_code'])) {
                    $html .= '<div style="font-size: 5.5pt; color: #0284c7;">SNOMED: ' . $esc($it['snomed_ct_code']) . '</div>';
                }
                $html .= '
                    </td>
                    <td style="text-align: center;">' . $esc($it['hsn_code'] ?: '3004') . '</td>
                    <td style="text-align: center; font-weight: bold;">' . $esc($it['batch_no']) . '</td>
                    <td style="text-align: center;">' . $esc(substr($it['expiry_date'] ?? '', 0, 7)) . '</td>
                    <td style="text-align: center; font-weight: bold;">' . $esc($qtyUnitStr) . '</td>
                    <td style="text-align: right;">' . $rateStr . '</td>
                    <td style="text-align: right;">' . $curr($it['taxable_value']) . '</td>
                    <td style="text-align: right;">' . $curr($it['cgst_amount']) . '</td>
                    <td style="text-align: right;">' . $curr($it['sgst_amount']) . '</td>
                    <td style="text-align: right; font-weight: bold;">' . $curr($it['total_amount']) . '</td>
                </tr>';
            }
        }

        $html .= '
            </tbody>
        </table>';

        // 4. Returned / Exchanged Medicines Table (if any)
        if (!empty($returnItems)) {
            $html .= '
            <div style="background-color: #ffe4e6; border: 0.2mm solid #e11d48; padding: 3px 5px; font-weight: bold; color: #9f1239; font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . ';">
                RETURNED / EXCHANGED MEDICINES ' . ($creditNote ? '(Credit Note: ' . $esc($creditNote['credit_note_no']) . ')' : '') . '
            </div>
            <table class="items-table return-table" style="margin-top: -0.2mm;">
                <thead>
                    <tr>
                        <th style="width: 3%; text-align: center;">#</th>
                        <th style="width: 27%;">Returned Medicine</th>
                        <th style="width: 8%; text-align: center;">HSN</th>
                        <th style="width: 10%; text-align: center;">Batch</th>
                        <th style="width: 10%; text-align: center;">Original Bill</th>
                        <th style="width: 8%; text-align: center;">Condition</th>
                        <th style="width: 10%; text-align: center;">Qty Returned</th>
                        <th style="width: 8%; text-align: right;">Rate</th>
                        <th style="width: 8%; text-align: right;">Taxable</th>
                        <th style="width: 8%; text-align: right; color: #9f1239;">Credit Total</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($returnItems as $rIdx => $rit) {
                $retQtyStr = '-' . (($rit['sell_unit'] === 'Tablet') ? ($rit['total_units'] . ' Tab') : ($rit['qty'] . ' ' . ($rit['unit_pack'] ?: 'Unit')));
                $html .= '
                    <tr>
                        <td style="text-align: center;">' . ($rIdx + 1) . '</td>
                        <td><strike>' . $esc($rit['item_name']) . '</strike></td>
                        <td style="text-align: center;">' . $esc($rit['hsn_code'] ?: '3004') . '</td>
                        <td style="text-align: center;">' . $esc($rit['batch_no']) . '</td>
                        <td style="text-align: center;">' . $esc($rit['ref_invoice_no'] ?: 'N/A') . '</td>
                        <td style="text-align: center;">' . $esc($rit['return_condition'] ?: 'GOOD') . '</td>
                        <td style="text-align: center; font-weight: bold; color: #b91c1c;">' . $esc($retQtyStr) . '</td>
                        <td style="text-align: right;">' . $curr($rit['unit_price']) . '</td>
                        <td style="text-align: right;">-' . $curr(abs((float)$rit['taxable_value'])) . '</td>
                        <td style="text-align: right; font-weight: bold; color: #b91c1c;">-' . $curr(abs((float)$rit['total_amount'])) . '</td>
                    </tr>';
            }

            $html .= '
                </tbody>
            </table>';
        }

        // 5. GST Tax Breakdown Table (HSN Summary)
        if (!empty($hsnSummary)) {
            $html .= '
            <div style="font-size: ' . ($isA5 ? '6pt' : '7pt') . '; font-weight: bold; color: #475569; margin-bottom: 2px;">
                GST Tax Breakdown (Indian GST Standard):
            </div>
            <table class="hsn-table" style="margin-bottom: ' . ($isA5 ? '4px' : '6px') . ';">
                <thead>
                    <tr>
                        <th style="width: 16%;">HSN Code</th>
                        <th style="width: 16%;">GST Rate</th>
                        <th style="width: 20%;">Taxable Value</th>
                        <th style="width: 16%;">CGST</th>
                        <th style="width: 16%;">SGST</th>
                        <th style="width: 16%;">Total Tax</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($hsnSummary as $h) {
                $html .= '
                    <tr>
                        <td>' . $esc($h['hsn_code']) . '</td>
                        <td>' . $esc($h['gst_rate']) . '%</td>
                        <td style="text-align: right;">' . $curr($h['taxable_value']) . '</td>
                        <td style="text-align: right;">' . $curr($h['cgst_amount']) . '</td>
                        <td style="text-align: right;">' . $curr($h['sgst_amount']) . '</td>
                        <td style="text-align: right; font-weight: bold;">' . $curr($h['total_tax']) . '</td>
                    </tr>';
            }

            $html .= '
                </tbody>
            </table>';
        }

        // 6. Summary Totals & Signatures
        $html .= '
        <table style="width: 100%; border-top: 0.3mm solid #94a3b8; padding-top: ' . ($isA5 ? '3px' : '5px') . ';">
            <tr>
                <td style="width: 58%; vertical-align: top; padding-right: 8px;">
                    <div style="font-size: ' . ($isA5 ? '6pt' : '7pt') . '; color: #334155; margin-bottom: 3px;">
                        <strong>Registered Pharmacist:</strong> ' . $esc($sale['registered_pharmacist_name'] ?: 'Pharmacist') . ' (Reg No: ' . $esc($sale['pharmacist_reg_no'] ?: 'N/A') . ')
                        ' . ($sale['pharmacist_hpr_id'] ? '<br><strong>HPR ID:</strong> ' . $esc($sale['pharmacist_hpr_id']) : '') . '
                    </div>

                    <div style="background-color: #f8fafc; border: 0.2mm solid #cbd5e1; border-radius: 1mm; padding: 4px; font-size: ' . ($isA5 ? '6pt' : '7pt') . '; margin-bottom: 4px;">
                        <strong>Payment Settlement:</strong> ' . $esc($sale['payment_mode']) . '<br>';

        if ((float)$sale['cash_paid'] > 0) {
            $html .= '&bull; Cash: ' . $curr($sale['cash_paid']) . ' ';
        }
        if ((float)$sale['upi_paid'] > 0) {
            $html .= '&bull; UPI/Bank: ' . $curr($sale['upi_paid']) . ($sale['upi_ref_no'] ? ' [UTR: ' . $esc($sale['upi_ref_no']) . ']' : '') . ($sale['bank_name'] ? ' (' . $esc($sale['bank_name']) . ')' : '') . ' ';
        }
        if ((float)$sale['card_paid'] > 0) {
            $html .= '&bull; Card: ' . $curr($sale['card_paid']) . ($sale['card_ref_no'] ? ' [Ref: ' . $esc($sale['card_ref_no']) . ']' : '') . ' ';
        }
        if ((float)$sale['credit_amount'] > 0) {
            $html .= '&bull; IPD Credit: ' . $curr($sale['credit_amount']) . ' ';
        }

        $html .= '<br>' . ($sale['is_bank_reconciled'] == 1 ? '<span style="color:#16a34a;font-weight:bold;">&#10003; Bank Statement Audited</span>' : '<span style="color:#d97706;">Bank Statement: Pending Audit</span>');

        $html .= '
                    </div>

                    <div style="font-size: 5.5pt; color: #64748b; line-height: 1.2;">
                        ' . nl2br($esc($sale['terms_conditions'] ?: "1. Goods once sold are returnable only as per Drug Rules within 7 days.\n2. Keep medicines stored in cool & dry place below 25°C away from direct sunlight.")) . '
                    </div>
                </td>

                <td style="width: 42%; vertical-align: top;">
                    <table style="font-size: ' . ($isA5 ? '6.5pt' : '7.5pt') . '; line-height: 1.35;">
                        <tr>
                            <td>Gross Items Total:</td>
                            <td class="text-right">' . $curr($sale['gross_amount']) . '</td>
                        </tr>';

        if ((float)$sale['discount_amount'] > 0) {
            $html .= '
                        <tr>
                            <td class="text-danger">Total Discount Allowed:</td>
                            <td class="text-right text-danger">-' . $curr($sale['discount_amount']) . '</td>
                        </tr>';
        }

        if ((float)$sale['return_amount'] > 0) {
            $html .= '
                        <tr>
                            <td class="text-danger fw-bold">Less Return Credit:</td>
                            <td class="text-right text-danger fw-bold">-' . $curr($sale['return_amount']) . '</td>
                        </tr>';
        }

        $html .= '
                        <tr>
                            <td>Taxable Amount:</td>
                            <td class="text-right">' . $curr($sale['taxable_amount']) . '</td>
                        </tr>
                        <tr>
                            <td>CGST Amount:</td>
                            <td class="text-right">' . $curr($sale['cgst_amount']) . '</td>
                        </tr>
                        <tr>
                            <td>SGST Amount:</td>
                            <td class="text-right">' . $curr($sale['sgst_amount']) . '</td>
                        </tr>';

        if ((float)$sale['round_off'] != 0) {
            $html .= '
                        <tr>
                            <td>Round Off:</td>
                            <td class="text-right">' . $curr($sale['round_off']) . '</td>
                        </tr>';
        }

        $html .= '
                        <tr>
                            <td colspan="2" style="padding-top: 3px;">
                                <div class="total-box">
                                    <table style="width: 100%; font-size: ' . ($isA5 ? '8pt' : '10pt') . '; font-weight: bold;">
                                        <tr>
                                            <td>NET PAYABLE:</td>
                                            <td style="text-align: right;">' . $curr($sale['net_amount']) . '</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>';

        if ((float)$sale['refund_amount'] > 0) {
            $html .= '
                        <tr>
                            <td colspan="2" style="padding-top: 3px;">
                                <div style="background-color: #ffe4e6; border: 0.3mm solid #e11d48; padding: 3px 5px; border-radius: 1mm; color: #9f1239; font-weight: bold; font-size: ' . ($isA5 ? '7pt' : '8pt') . ';">
                                    REFUND PAID TO CUSTOMER: ' . $curr($sale['refund_amount']) . ' (' . $esc($sale['refund_mode']) . ')
                                </div>
                            </td>
                        </tr>';
        }

        $html .= '
                    </table>
                </td>
            </tr>
        </table>

        </body>
        </html>';

        return $html;
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

    public function deleteSupplier()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = (int)($json['supplier_id'] ?? 0);
        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid supplier ID.']);
        }

        // Check if supplier is referenced in purchases
        $hasPurchases = $this->db->table('mst_purchases')->where('supplier_id', $id)->countAllResults();
        if ($hasPurchases > 0) {
            $this->db->table('mst_suppliers')->where('supplier_id', $id)->update(['is_active' => 0]);
            return $this->response->setJSON(['status' => 1, 'message' => 'Supplier deactivated (cannot delete because purchase bills are linked).']);
        }

        $this->db->table('mst_suppliers')->where('supplier_id', $id)->delete();
        return $this->response->setJSON(['status' => 1, 'message' => 'Supplier removed successfully.']);
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
            $itemId = (int)($it['item_id'] ?? 0);
            $itemName = trim($it['item_name'] ?? '');

            if ($itemId <= 0 && $itemName !== '') {
                $mst = $this->db->table('mst_items')
                    ->where('LOWER(TRIM(item_name))', strtolower($itemName))
                    ->get()
                    ->getRowArray();
                if ($mst) {
                    $itemId = (int)$mst['item_id'];
                } else {
                    $uPack = (int)($it['units_per_pack'] ?? 10);
                    $gRate = (float)($it['gst_rate'] ?? 12.00);
                    $hCode = trim($it['hsn_code'] ?? '3004');

                    $pmId = 0;
                    if ($this->db->tableExists('med_product_master')) {
                        $pmRow = $this->db->table('med_product_master')
                            ->where('LOWER(TRIM(item_name))', strtolower($itemName))
                            ->get()
                            ->getRowArray();
                        if ($pmRow) {
                            $pmId = (int)$pmRow['id'];
                        } else {
                            $this->db->table('med_product_master')->insert([
                                'item_name'    => $itemName,
                                'formulation'  => 'Tablet',
                                'genericname'  => '',
                                'packing'      => (string)$uPack,
                                'HSNCODE'      => $hCode,
                                'CGST_per'     => round($gRate / 2, 2),
                                'SGST_per'     => round($gRate / 2, 2),
                                'is_continue'  => 1,
                                'insert_by'    => 'Purchase Inward Auto'
                            ]);
                            $pmId = (int)$this->db->insertID();
                        }
                    }

                    $this->db->table('mst_items')->insert([
                        'product_master_id' => $pmId > 0 ? $pmId : null,
                        'item_name'         => $itemName,
                        'category'          => 'Tablet',
                        'hsn_code'          => $hCode,
                        'gst_rate'          => $gRate,
                        'units_per_pack'    => $uPack,
                        'unit_pack'         => $uPack . ' Units',
                        'drug_schedule'     => 'OTC',
                        'is_active'         => 1,
                        'min_reorder_level' => 10
                    ]);
                    $itemId = (int)$this->db->insertID();
                }
            }

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

            $schDiscPct = (float)($it['sch_disc_pct'] ?? 0);
            $schDiscAmt = (float)($it['sch_disc_amount'] ?? 0);
            $sellingPrice = (float)($it['selling_price'] ?? $mrp);
            $storageType = trim($it['storage_type'] ?? 'Normal');
            $shelfNo = trim($it['shelf_no'] ?? '');
            $rackNo = trim($it['rack_no'] ?? '');

            // Calculate after scheme discount if present
            $effectivePtr = $ptr;
            if ($schDiscPct > 0) {
                $effectivePtr = $effectivePtr * (1 - ($schDiscPct / 100));
            }
            $lineRawAmt = ($qtyPacks * $effectivePtr);
            if ($schDiscAmt > 0) {
                $lineRawAmt = max(0, $lineRawAmt - $schDiscAmt);
            }

            $lineNetPtr = $lineRawAmt * (1 - ($discPct / 100));
            $taxAmount = round($lineNetPtr * ($gstRate / 100), 2);

            $cgst = round($taxAmount / 2, 2);
            $sgst = round($taxAmount - $cgst, 2);
            $igst = 0;
            $lineTotal = round($lineNetPtr + $taxAmount, 2);
            $netUnitLanding = $totalUnits > 0 ? round($lineTotal / $totalUnits, 2) : 0;

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
                'sch_disc_pct'          => $schDiscPct,
                'sch_disc_amount'       => $schDiscAmt,
                'selling_price'         => $sellingPrice,
                'hsn_code'              => $it['hsn_code'] ?? '3004',
                'gst_rate'              => $gstRate,
                'taxable_value'         => $lineNetPtr,
                'cgst_amount'           => $cgst,
                'sgst_amount'           => $sgst,
                'igst_amount'           => $igst,
                'total_amount'          => $lineTotal,
                'net_unit_landing_cost' => $netUnitLanding,
                'storage_type'          => $storageType,
                'shelf_no'              => $shelfNo,
                'rack_no'               => $rackNo,
                'challan_item_ref_id'   => (int)($it['challan_item_ref_id'] ?? 0)
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
                    'gst_rate'          => $gstRate,
                    'shelf_no'          => $shelfNo ?: ($batchRow['shelf_no'] ?? null),
                    'rack_no'           => $rackNo ?: ($batchRow['rack_no'] ?? null),
                    'storage_type'      => $storageType ?: ($batchRow['storage_type'] ?? 'Normal')
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
                    'gst_rate'          => $gstRate,
                    'shelf_no'          => $shelfNo ?: null,
                    'rack_no'           => $rackNo ?: null,
                    'storage_type'      => $storageType ?: 'Normal'
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

        $isChallan = !empty($json['is_challan']) ? 1 : 0;
        $challanNo = trim($json['challan_no'] ?? '');

        // Insert Purchase Record
        $purchaseData = [
            'store_id'            => $storeId,
            'supplier_id'         => $supplierId,
            'supplier_invoice_no' => trim($json['supplier_invoice_no'] ?? (($isChallan ? 'CHL-' : 'PUR-') . time())),
            'challan_no'          => $isChallan ? trim($json['supplier_invoice_no']) : ($challanNo ?: null),
            'is_challan'          => $isChallan,
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

        // If converted from an existing challan, mark that challan as converted
        $convertedChallanId = (int)($json['converted_from_challan_id'] ?? 0);
        if ($convertedChallanId > 0) {
            $this->db->table('mst_purchases')
                ->where('purchase_id', $convertedChallanId)
                ->update(['converted_to_invoice_id' => $purchaseId]);

            // If legacy purchase_invoice
            if ($this->db->tableExists('purchase_invoice')) {
                $this->db->table('purchase_invoice')
                    ->where('id', $convertedChallanId)
                    ->update(['inv_status' => 1]);
            }
        }

        // If inward bill is created against a Purchase Order, mark PO as received
        $poId = (int)($json['po_id'] ?? 0);
        if ($poId > 0 && $this->db->tableExists('mst_purchase_orders')) {
            $this->db->table('mst_purchase_orders')
                ->where('po_id', $poId)
                ->update([
                    'status'     => 'received',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }

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

    public function getPurchases()
    {
        $storeId = (int)($this->request->getGet('store_id') ?: 0);
        $supplierId = (int)($this->request->getGet('supplier_id') ?: 0);
        $search = trim($this->request->getGet('search') ?: '');

        $builder = $this->db->table('mst_purchases p')
            ->select('p.*, s.supplier_name, s.phone, s.contact_person, s.gstin, COUNT(pi.purchase_item_id) as items_count')
            ->join('mst_suppliers s', 's.supplier_id = p.supplier_id', 'left')
            ->join('mst_purchase_items pi', 'pi.purchase_id = p.purchase_id', 'left')
            ->groupBy('p.purchase_id')
            ->orderBy('p.invoice_date', 'DESC')
            ->orderBy('p.purchase_id', 'DESC')
            ->limit(100);

        if ($storeId > 0) {
            $builder->where('p.store_id', $storeId);
        }
        if ($supplierId > 0) {
            $builder->where('p.supplier_id', $supplierId);
        }
        if (!empty($search)) {
            $builder->groupStart()
                ->like('p.supplier_invoice_no', $search)
                ->orLike('s.supplier_name', $search)
                ->groupEnd();
        }

        $purchases = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'purchases' => $purchases
        ]);
    }

    public function getPurchaseDetail(int $purchaseId)
    {
        $purchase = $this->db->table('mst_purchases p')
            ->select('p.*, s.supplier_name, s.contact_person, s.phone, s.email, s.address, s.gstin, s.dl_no_20b, s.dl_no_21b')
            ->join('mst_suppliers s', 's.supplier_id = p.supplier_id', 'left')
            ->where('p.purchase_id', $purchaseId)
            ->get()
            ->getRowArray();

        if (!$purchase) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Purchase invoice not found.']);
        }

        $items = $this->db->table('mst_purchase_items pi')
            ->select('pi.*, i.item_name, i.generic_name, i.category')
            ->join('mst_items i', 'i.item_id = pi.item_id', 'left')
            ->where('pi.purchase_id', $purchaseId)
            ->orderBy('pi.purchase_item_id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'purchase' => $purchase,
            'items' => $items
        ]);
    }

    public function updatePurchase()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $purchaseId = (int)($json['purchase_id'] ?? 0);
        $storeId = (int)($json['store_id'] ?? 1);
        $supplierId = (int)($json['supplier_id'] ?? 0);
        $items = $json['items'] ?? [];

        if ($purchaseId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Valid Purchase ID is required.']);
        }
        if ($supplierId <= 0 || empty($items)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Supplier and items are required.']);
        }

        $existingPurchase = $this->db->table('mst_purchases')->where('purchase_id', $purchaseId)->get()->getRowArray();
        if (!$existingPurchase) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Purchase invoice not found.']);
        }

        $paidAmount = (float)($existingPurchase['paid_amount'] ?? 0);
        if ($paidAmount > 0 && $supplierId !== (int)$existingPurchase['supplier_id']) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 0,
                'message' => "Cannot change the supplier on an invoice that already has payments recorded against it. Please reverse the payment first."
            ]);
        }

        // Fetch current purchase items to compute stock rollback
        $oldItems = $this->db->table('mst_purchase_items')->where('purchase_id', $purchaseId)->get()->getResultArray();

        $this->db->transStart();

        // Step 1: Revert previous stock quantities added by old purchase items
        foreach ($oldItems as $oldIt) {
            $oldBatch = $this->db->table('mst_batches')
                ->where('store_id', $existingPurchase['store_id'])
                ->where('item_id', $oldIt['item_id'])
                ->where('batch_no', $oldIt['batch_no'])
                ->get()
                ->getRowArray();

            if ($oldBatch) {
                $batchId = (int)$oldBatch['batch_id'];
                $stockRow = $this->db->table('mst_stock')
                    ->where('store_id', $existingPurchase['store_id'])
                    ->where('item_id', $oldIt['item_id'])
                    ->where('batch_id', $batchId)
                    ->get()
                    ->getRowArray();

                if ($stockRow) {
                    $newQty = max(0, (int)$stockRow['current_qty'] - (int)$oldIt['total_units']);
                    $this->db->table('mst_stock')
                        ->where('stock_id', $stockRow['stock_id'])
                        ->update(['current_qty' => $newQty]);
                }
            }
        }

        // Delete old purchase items
        $this->db->table('mst_purchase_items')->where('purchase_id', $purchaseId)->delete();

        // Step 2: Process new items, create/update items & batches, and add new stock
        $taxableTotal = 0;
        $cgstTotal = 0;
        $sgstTotal = 0;
        $igstTotal = 0;
        $discountTotal = (float)($json['discount_amount'] ?? 0);
        $purchaseItems = [];

        foreach ($items as $it) {
            $itemId = (int)($it['item_id'] ?? 0);
            $itemName = trim($it['item_name'] ?? '');

            if ($itemId <= 0 && $itemName !== '') {
                $mst = $this->db->table('mst_items')
                    ->where('LOWER(TRIM(item_name))', strtolower($itemName))
                    ->get()
                    ->getRowArray();
                if ($mst) {
                    $itemId = (int)$mst['item_id'];
                } else {
                    $uPack = (int)($it['units_per_pack'] ?? 10);
                    $gRate = (float)($it['gst_rate'] ?? 12.00);
                    $hCode = trim($it['hsn_code'] ?? '3004');

                    $pmId = 0;
                    if ($this->db->tableExists('med_product_master')) {
                        $pmRow = $this->db->table('med_product_master')
                            ->where('LOWER(TRIM(item_name))', strtolower($itemName))
                            ->get()
                            ->getRowArray();
                        if ($pmRow) {
                            $pmId = (int)$pmRow['id'];
                        } else {
                            $this->db->table('med_product_master')->insert([
                                'item_name'    => $itemName,
                                'formulation'  => 'Tablet',
                                'genericname'  => '',
                                'packing'      => (string)$uPack,
                                'HSNCODE'      => $hCode,
                                'CGST_per'     => round($gRate / 2, 2),
                                'SGST_per'     => round($gRate / 2, 2),
                                'is_continue'  => 1,
                                'insert_by'    => 'Purchase Update Auto'
                            ]);
                            $pmId = (int)$this->db->insertID();
                        }
                    }

                    $this->db->table('mst_items')->insert([
                        'product_master_id' => $pmId > 0 ? $pmId : null,
                        'item_name'         => $itemName,
                        'category'          => 'Tablet',
                        'hsn_code'          => $hCode,
                        'gst_rate'          => $gRate,
                        'units_per_pack'    => $uPack,
                        'unit_pack'         => $uPack . ' Units',
                        'drug_schedule'     => 'OTC',
                        'is_active'         => 1,
                        'min_reorder_level' => 10
                    ]);
                    $itemId = (int)$this->db->insertID();
                }
            }

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

            $schDiscPct = (float)($it['sch_disc_pct'] ?? 0);
            $schDiscAmt = (float)($it['sch_disc_amount'] ?? 0);
            $sellingPrice = (float)($it['selling_price'] ?? $mrp);
            $storageType = trim($it['storage_type'] ?? 'Normal');
            $shelfNo = trim($it['shelf_no'] ?? '');
            $rackNo = trim($it['rack_no'] ?? '');

            // Calculate after scheme discount if present
            $effectivePtr = $ptr;
            if ($schDiscPct > 0) {
                $effectivePtr = $effectivePtr * (1 - ($schDiscPct / 100));
            }
            $lineRawAmt = ($qtyPacks * $effectivePtr);
            if ($schDiscAmt > 0) {
                $lineRawAmt = max(0, $lineRawAmt - $schDiscAmt);
            }

            $lineNetPtr = $lineRawAmt * (1 - ($discPct / 100));
            $taxAmount = round($lineNetPtr * ($gstRate / 100), 2);

            $cgst = round($taxAmount / 2, 2);
            $sgst = round($taxAmount - $cgst, 2);
            $igst = 0;
            $lineTotal = round($lineNetPtr + $taxAmount, 2);
            $netUnitLanding = $totalUnits > 0 ? round($lineTotal / $totalUnits, 2) : 0;

            $taxableTotal += $lineNetPtr;
            $cgstTotal += $cgst;
            $sgstTotal += $sgst;

            $purchaseItems[] = [
                'purchase_id'           => $purchaseId,
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
                'sch_disc_pct'          => $schDiscPct,
                'sch_disc_amount'       => $schDiscAmt,
                'selling_price'         => $sellingPrice,
                'hsn_code'              => $it['hsn_code'] ?? '3004',
                'gst_rate'              => $gstRate,
                'taxable_value'         => $lineNetPtr,
                'cgst_amount'           => $cgst,
                'sgst_amount'           => $sgst,
                'igst_amount'           => $igst,
                'total_amount'          => $lineTotal,
                'net_unit_landing_cost' => $netUnitLanding,
                'storage_type'          => $storageType,
                'shelf_no'              => $shelfNo,
                'rack_no'               => $rackNo,
                'challan_item_ref_id'   => (int)($it['challan_item_ref_id'] ?? 0)
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
                    'gst_rate'          => $gstRate,
                    'shelf_no'          => $shelfNo ?: ($batchRow['shelf_no'] ?? null),
                    'rack_no'           => $rackNo ?: ($batchRow['rack_no'] ?? null),
                    'storage_type'      => $storageType ?: ($batchRow['storage_type'] ?? 'Normal')
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
                    'gst_rate'          => $gstRate,
                    'shelf_no'          => $shelfNo ?: null,
                    'rack_no'           => $rackNo ?: null,
                    'storage_type'      => $storageType ?: 'Normal'
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

        // Validation against paid_amount
        if ($paidAmount > 0 && $grandTotal < $paidAmount) {
            $this->db->transRollback();
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 0,
                'message' => "Cannot reduce invoice net amount to ₹$grandTotal because ₹$paidAmount has already been paid to the supplier. Adjust or reverse the payment first."
            ]);
        }

        // Determine updated payment_status
        $paymentStatus = 'unpaid';
        if ($paidAmount >= $grandTotal && $grandTotal > 0) {
            $paymentStatus = 'paid';
        } elseif ($paidAmount > 0) {
            $paymentStatus = 'partially_paid';
        }

        $isChallan = isset($json['is_challan']) ? (!empty($json['is_challan']) ? 1 : 0) : ($existingPurchase['is_challan'] ?? 0);

        // Update purchase record
        $updateData = [
            'store_id'            => $storeId,
            'supplier_id'         => $supplierId,
            'supplier_invoice_no' => trim($json['supplier_invoice_no'] ?: $existingPurchase['supplier_invoice_no']),
            'is_challan'          => $isChallan,
            'invoice_date'        => $json['invoice_date'] ?? $existingPurchase['invoice_date'],
            'taxable_amount'      => $taxableTotal,
            'cgst_amount'         => $cgstTotal,
            'sgst_amount'         => $sgstTotal,
            'igst_amount'         => $igstTotal,
            'discount_amount'     => $discountTotal,
            'net_amount'          => $grandTotal,
            'payment_status'      => $paymentStatus,
            'remarks'             => trim($json['remarks'] ?? $existingPurchase['remarks'])
        ];
        $this->db->table('mst_purchases')->where('purchase_id', $purchaseId)->update($updateData);

        // Insert new purchase items
        foreach ($purchaseItems as $pi) {
            $this->db->table('mst_purchase_items')->insert($pi);
        }

        // Step 3: Refresh double-entry ledger postings
        $this->db->table('mst_ledger_entries')
            ->where('voucher_type', 'PURCHASE')
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $purchaseId)
            ->delete();

        $purchaseHead = $this->db->table('mst_account_heads')->where('head_code', '4001')->get()->getRowArray();
        $inputCgstHead = $this->db->table('mst_account_heads')->where('head_code', '1006')->get()->getRowArray();
        $inputSgstHead = $this->db->table('mst_account_heads')->where('head_code', '1007')->get()->getRowArray();
        $creditorHead = $this->db->table('mst_account_heads')->where('head_code', '2001')->get()->getRowArray();

        $vNo = $updateData['supplier_invoice_no'];
        if ($purchaseHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PURCHASE', 'voucher_date' => $updateData['invoice_date'],
                'account_head_id' => $purchaseHead['head_id'], 'debit_amount' => $taxableTotal, 'credit_amount' => 0,
                'reference_type' => 'purchase_invoice', 'reference_id' => $purchaseId, 'narration' => "Updated Purchase from Supplier #$supplierId Bill $vNo"
            ]);
        }
        if ($cgstTotal > 0 && $inputCgstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PURCHASE', 'voucher_date' => $updateData['invoice_date'],
                'account_head_id' => $inputCgstHead['head_id'], 'debit_amount' => $cgstTotal, 'credit_amount' => 0,
                'reference_type' => 'purchase_invoice', 'reference_id' => $purchaseId, 'narration' => "Updated Input CGST credit on $vNo"
            ]);
        }
        if ($sgstTotal > 0 && $inputSgstHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PURCHASE', 'voucher_date' => $updateData['invoice_date'],
                'account_head_id' => $inputSgstHead['head_id'], 'debit_amount' => $sgstTotal, 'credit_amount' => 0,
                'reference_type' => 'purchase_invoice', 'reference_id' => $purchaseId, 'narration' => "Updated Input SGST credit on $vNo"
            ]);
        }
        if ($creditorHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id' => $storeId, 'voucher_no' => $vNo, 'voucher_type' => 'PURCHASE', 'voucher_date' => $updateData['invoice_date'],
                'account_head_id' => $creditorHead['head_id'], 'debit_amount' => 0, 'credit_amount' => $grandTotal,
                'reference_type' => 'purchase_invoice', 'reference_id' => $purchaseId, 'narration' => "Updated Credit payable to Supplier #$supplierId"
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to update purchase invoice.']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Purchase invoice #' . $vNo . ' updated successfully! Stock and ledgers adjusted.',
            'purchase_id' => $purchaseId,
            'net_amount' => $grandTotal
        ]);
    }

    /**
     * Retrieves pending Delivery Challans for a specific supplier so they can be included into a Tax Invoice.
     */
    public function getSupplierChallans($supplierId)
    {
        $supplierId = (int)$supplierId;
        if ($supplierId <= 0) {
            return $this->response->setJSON(['status' => 1, 'challans' => []]);
        }

        $challans = [];

        // 1. From mst_purchases where is_challan = 1 and not converted
        $rows = $this->db->table('mst_purchases p')
            ->select('p.purchase_id, p.supplier_invoice_no AS challan_no, p.invoice_date AS challan_date, p.net_amount, p.taxable_amount, p.cgst_amount, p.sgst_amount, p.supplier_id, s.supplier_name')
            ->join('mst_suppliers s', 's.supplier_id = p.supplier_id', 'left')
            ->where('p.supplier_id', $supplierId)
            ->where('p.is_challan', 1)
            ->where('(p.converted_to_invoice_id IS NULL OR p.converted_to_invoice_id = 0)')
            ->orderBy('p.purchase_id', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($rows as $r) {
            $items = $this->db->table('mst_purchase_items pi')
                ->select('pi.*, i.item_name, i.generic_name, i.category, i.hsn_code, i.units_per_pack')
                ->join('mst_items i', 'i.item_id = pi.item_id', 'left')
                ->where('pi.purchase_id', (int)$r['purchase_id'])
                ->get()
                ->getResultArray();

            $challans[] = [
                'id'             => (int)$r['purchase_id'],
                'challan_no'     => $r['challan_no'],
                'challan_date'   => $r['challan_date'],
                'str_date'       => date('d-m-Y', strtotime($r['challan_date'])),
                'net_amount'     => (float)$r['net_amount'],
                'taxable_amount' => (float)$r['taxable_amount'],
                'cgst_amount'    => (float)$r['cgst_amount'],
                'sgst_amount'    => (float)$r['sgst_amount'],
                'source'         => 'mst',
                'items'          => array_map(function($it) {
                    return [
                        'item_id'         => (int)$it['item_id'],
                        'item_name'       => $it['item_name'],
                        'generic_name'    => $it['generic_name'] ?? '',
                        'category'        => $it['category'] ?? 'Tablet',
                        'batch_no'        => $it['batch_no'],
                        'expiry_date'     => $it['expiry_date'],
                        'qty_packs'       => (int)$it['qty_packs'],
                        'free_qty_packs'  => (int)($it['free_qty_packs'] ?? 0),
                        'units_per_pack'  => (int)($it['units_per_pack'] ?? 10),
                        'ptr'             => (float)$it['ptr'],
                        'mrp'             => (float)$it['mrp'],
                        'discount_pct'    => (float)($it['discount_pct'] ?? 0),
                        'sch_disc_pct'    => (float)($it['sch_disc_pct'] ?? 0),
                        'sch_disc_amount' => (float)($it['sch_disc_amount'] ?? 0),
                        'gst_rate'        => (float)($it['gst_rate'] ?? 12.00),
                        'hsn_code'        => $it['hsn_code'] ?? '3004',
                        'selling_price'   => (float)($it['selling_price'] ?? $it['mrp']),
                        'storage_type'    => $it['storage_type'] ?? 'Normal',
                        'shelf_no'        => $it['shelf_no'] ?? '',
                        'rack_no'         => $it['rack_no'] ?? '',
                        'challan_ref_id'  => (int)$it['purchase_id'],
                        'ss_no'           => (int)$it['purchase_item_id']
                    ];
                }, $items)
            ];
        }

        // 2. Also check legacy purchase_invoice where ischallan = 1 and inv_status = 0
        if ($this->db->tableExists('purchase_invoice') && $this->db->tableExists('purchase_invoice_item')) {
            $legacyRows = $this->db->table('purchase_invoice p')
                ->select("p.id, p.Invoice_no, p.date_of_invoice, DATE_FORMAT(p.date_of_invoice,'%d-%m-%Y') AS str_date, p.T_Net_Amount, p.Taxable_Amt, p.CGST_Amt, p.SGST_Amt")
                ->where('p.sid', $supplierId)
                ->where('p.ischallan', 1)
                ->where('p.inv_status', 0)
                ->orderBy('p.id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($legacyRows as $lr) {
                $lItems = $this->db->table('purchase_invoice_item')
                    ->where('purchase_id', (int)$lr['id'])
                    ->where('remove_item', 0)
                    ->where('item_return', 0)
                    ->get()
                    ->getResultArray();

                if (!empty($lItems)) {
                    $challans[] = [
                        'id'             => (int)$lr['id'],
                        'challan_no'     => $lr['Invoice_no'],
                        'challan_date'   => $lr['date_of_invoice'],
                        'str_date'       => $lr['str_date'],
                        'net_amount'     => (float)$lr['T_Net_Amount'],
                        'taxable_amount' => (float)$lr['Taxable_Amt'],
                        'cgst_amount'    => (float)$lr['CGST_Amt'],
                        'sgst_amount'    => (float)$lr['SGST_Amt'],
                        'source'         => 'legacy',
                        'items'          => array_map(function($lit) {
                            return [
                                'item_id'         => (int)($lit['item_code'] ?? 0),
                                'item_name'       => $lit['Item_name'],
                                'batch_no'        => $lit['batch_no'],
                                'expiry_date'     => $lit['expiry_date'],
                                'qty_packs'       => (int)($lit['qty'] ?? 0),
                                'free_qty_packs'  => (int)($lit['qty_free'] ?? 0),
                                'units_per_pack'  => (int)($lit['packing'] ?? 10),
                                'ptr'             => (float)($lit['purchase_price'] ?? 0),
                                'mrp'             => (float)($lit['mrp'] ?? 0),
                                'discount_pct'    => (float)($lit['discount'] ?? 0),
                                'sch_disc_pct'    => (float)($lit['sch_disc_per'] ?? 0),
                                'sch_disc_amount' => (float)($lit['sch_disc_amt'] ?? 0),
                                'gst_rate'        => (float)(($lit['CGST_per'] ?? 6) + ($lit['SGST_per'] ?? 6)),
                                'hsn_code'        => $lit['HSNCODE'] ?? '3004',
                                'selling_price'   => (float)($lit['selling_price'] ?? $lit['mrp']),
                                'storage_type'    => $lit['cold_storage'] ?? 'Normal',
                                'shelf_no'        => $lit['shelf_no'] ?? '',
                                'rack_no'         => $lit['rack_no'] ?? '',
                                'challan_ref_id'  => (int)$lit['purchase_id'],
                                'ss_no'           => (int)$lit['id']
                            ];
                        }, $lItems)
                    ];
                }
            }
        }

        return $this->response->setJSON([
            'status'   => 1,
            'challans' => $challans
        ]);
    }

    /**
     * Retrieves the last 5 purchase history records for a selected product/medicine.
     */
    public function getProductPurchaseHistory($itemId)
    {
        $itemId = (int)$itemId;
        if ($itemId <= 0) {
            return $this->response->setJSON(['status' => 1, 'history' => []]);
        }

        $history = [];
        $rows = $this->db->table('mst_purchase_items pi')
            ->select('pi.batch_no, pi.expiry_date, pi.qty_packs, pi.free_qty_packs, pi.units_per_pack, pi.ptr, pi.mrp, pi.discount_pct, pi.gst_rate, pi.net_unit_landing_cost, p.supplier_invoice_no, p.invoice_date, s.supplier_name')
            ->join('mst_purchases p', 'p.purchase_id = pi.purchase_id', 'inner')
            ->join('mst_suppliers s', 's.supplier_id = p.supplier_id', 'left')
            ->where('pi.item_id', $itemId)
            ->orderBy('p.invoice_date', 'DESC')
            ->orderBy('p.purchase_id', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        foreach ($rows as $r) {
            $history[] = [
                'invoice_no'       => $r['supplier_invoice_no'],
                'invoice_date'     => $r['invoice_date'],
                'str_date'         => date('d-m-Y', strtotime($r['invoice_date'])),
                'supplier_name'    => $r['supplier_name'] ?: 'Distributor',
                'batch_no'         => $r['batch_no'],
                'qty_packs'        => (int)$r['qty_packs'],
                'free_packs'       => (int)$r['free_qty_packs'],
                'ptr'              => (float)$r['ptr'],
                'mrp'              => (float)$r['mrp'],
                'discount_pct'     => (float)$r['discount_pct'],
                'gst_rate'         => (float)$r['gst_rate'],
                'unit_landing'     => (float)$r['net_unit_landing_cost']
            ];
        }

        // If less than 5 rows, check legacy purchase_invoice_item
        if (count($history) < 5 && $this->db->tableExists('purchase_invoice_item')) {
            $mstItem = $this->db->table('mst_items')->where('item_id', $itemId)->get()->getRowArray();
            if ($mstItem) {
                $prodMasterId = (int)($mstItem['product_master_id'] ?? 0);
                $itemName = trim($mstItem['item_name']);

                $builder = $this->db->table('purchase_invoice_item pii')
                    ->select("pii.batch_no, pii.expiry_date, pii.qty, pii.qty_free, pii.packing, pii.purchase_price, pii.mrp, pii.discount, (pii.CGST_per + pii.SGST_per) AS gst_rate, pi.Invoice_no, pi.date_of_invoice, DATE_FORMAT(pi.date_of_invoice,'%d-%m-%Y') AS str_date, IFNULL(s.name_supplier,'-') AS supplier_name", false)
                    ->join('purchase_invoice pi', 'pi.id = pii.purchase_id', 'inner')
                    ->join('med_supplier s', 's.sid = pi.sid', 'left');

                if ($prodMasterId > 0) {
                    $builder->groupStart()
                        ->where('pii.item_code', $prodMasterId)
                        ->orWhere('pii.Item_name', $itemName)
                        ->groupEnd();
                } else {
                    $builder->where('pii.Item_name', $itemName);
                }

                $legacyRows = $builder
                    ->orderBy('pi.date_of_invoice', 'DESC')
                    ->limit(5 - count($history))
                    ->get()
                    ->getResultArray();

                foreach ($legacyRows as $lr) {
                    $history[] = [
                        'invoice_no'    => $lr['Invoice_no'],
                        'invoice_date'  => $lr['date_of_invoice'],
                        'str_date'      => $lr['str_date'],
                        'supplier_name' => $lr['supplier_name'],
                        'batch_no'      => $lr['batch_no'],
                        'qty_packs'     => (int)$lr['qty'],
                        'free_packs'    => (int)$lr['qty_free'],
                        'ptr'           => (float)$lr['purchase_price'],
                        'mrp'           => (float)$lr['mrp'],
                        'discount_pct'  => (float)$lr['discount'],
                        'gst_rate'      => (float)$lr['gst_rate'],
                        'unit_landing'  => round((float)$lr['purchase_price'] * (1 - ((float)$lr['discount'] / 100)), 2)
                    ];
                }
            }
        }

        // Determine latest batch, expiry, mrp, ptr, gst, hsn, shelf_no, rack_no, storage_type
        $latestBatchRow = $this->db->table('mst_batches')
            ->where('item_id', $itemId)
            ->orderBy('batch_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        $latestPiRow = !empty($rows) ? $rows[0] : null;

        $batchNo = $latestPiRow['batch_no'] ?? ($latestBatchRow['batch_no'] ?? ($history[0]['batch_no'] ?? ''));
        $expDate = $latestPiRow['expiry_date'] ?? ($latestBatchRow['expiry_date'] ?? ($history[0]['expiry_date'] ?? ''));
        $mrp = (float)($latestPiRow['mrp'] ?? ($latestBatchRow['mrp'] ?? ($history[0]['mrp'] ?? 0)));
        $ptr = (float)($latestPiRow['ptr'] ?? ($latestBatchRow['ptr'] ?? ($history[0]['ptr'] ?? 0)));
        $discPct = (float)($latestPiRow['discount_pct'] ?? ($history[0]['discount_pct'] ?? 0));
        $gstRate = (float)($latestPiRow['gst_rate'] ?? ($latestBatchRow['gst_rate'] ?? ($history[0]['gst_rate'] ?? ($mstItem['gst_rate'] ?? 12.00))));
        $hsnCode = $latestPiRow['hsn_code'] ?? ($latestBatchRow['hsn_code'] ?? ($history[0]['hsn_code'] ?? ($mstItem['hsn_code'] ?? '3004')));
        $shelfNo = $latestPiRow['shelf_no'] ?? ($latestBatchRow['shelf_no'] ?? ($history[0]['shelf_no'] ?? ''));
        $rackNo = $latestPiRow['rack_no'] ?? ($latestBatchRow['rack_no'] ?? ($history[0]['rack_no'] ?? ''));
        $storageType = $latestPiRow['storage_type'] ?? ($latestBatchRow['storage_type'] ?? ($history[0]['storage_type'] ?? 'Normal'));
        $unitsPerPack = (int)($latestPiRow['units_per_pack'] ?? ($mstItem['units_per_pack'] ?? 10));
        $sellingPrice = (float)($latestPiRow['selling_price'] ?? ($mrp > 0 ? $mrp : 0));

        $expMonth = '12';
        $expYear = '28';
        if (!empty($expDate)) {
            $parts = explode('-', $expDate);
            if (count($parts) >= 2) {
                $expYear = substr($parts[0], -2);
                $expMonth = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            }
        }

        $latestInfo = [
            'batch_no'       => $batchNo,
            'expiry_date'    => $expDate,
            'exp_month'      => $expMonth,
            'exp_year'       => $expYear,
            'mrp'            => $mrp > 0 ? $mrp : '',
            'ptr'            => $ptr > 0 ? $ptr : '',
            'discount_pct'   => $discPct,
            'gst_rate'       => $gstRate,
            'cgst_pct'       => round($gstRate / 2, 2),
            'sgst_pct'       => round($gstRate / 2, 2),
            'hsn_code'       => $hsnCode ?: '3004',
            'shelf_no'       => $shelfNo,
            'rack_no'        => $rackNo,
            'storage_type'   => $storageType ?: 'Normal',
            'units_per_pack' => $unitsPerPack > 0 ? $unitsPerPack : 10,
            'selling_price'  => $sellingPrice > 0 ? $sellingPrice : ''
        ];

        return $this->response->setJSON([
            'status'      => 1,
            'history'     => $history,
            'latest_info' => $latestInfo
        ]);
    }

    public function saveSupplierPayment()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $supplierId = (int)($json['supplier_id'] ?? 0);
        $purchaseId = (int)($json['purchase_id'] ?? 0);
        $amount = (float)($json['amount'] ?? 0);
        $paymentMode = trim($json['payment_mode'] ?? 'Bank'); // Cash, Bank, Cheque, NEFT, UPI
        $refNo = trim($json['reference_no'] ?? '');
        $bankName = trim($json['bank_name'] ?? '');
        $remarks = trim($json['remarks'] ?? '');
        $paymentDate = !empty($json['payment_date']) ? trim($json['payment_date']) : date('Y-m-d');
        $storeId = (int)($json['store_id'] ?? 1);

        if ($supplierId <= 0 || $amount <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Supplier and valid payment amount are required.']);
        }

        $vNo = 'PMT-' . date('Ymd') . '-' . rand(1000, 9999);

        $this->db->transStart();

        // 1. Insert into mst_supplier_payments
        $this->db->table('mst_supplier_payments')->insert([
            'store_id'     => $storeId,
            'supplier_id'  => $supplierId,
            'purchase_id'  => $purchaseId > 0 ? $purchaseId : null,
            'payment_date' => $paymentDate,
            'amount'       => $amount,
            'payment_mode' => $paymentMode,
            'reference_no' => $refNo,
            'bank_name'    => $bankName,
            'voucher_no'   => $vNo,
            'remarks'      => $remarks,
            'created_at'   => date('Y-m-d H:i:s')
        ]);
        $paymentId = $this->db->insertID();

        // 2. Adjust invoice(s) paid_amount and payment_status
        if ($purchaseId > 0) {
            $inv = $this->db->table('mst_purchases')->where('purchase_id', $purchaseId)->get()->getRowArray();
            if ($inv) {
                $newPaid = (float)$inv['paid_amount'] + $amount;
                $newStatus = ($newPaid >= (float)$inv['net_amount']) ? 'paid' : 'partially_paid';
                $this->db->table('mst_purchases')->where('purchase_id', $purchaseId)->update([
                    'paid_amount'    => $newPaid,
                    'payment_status' => $newStatus
                ]);
            }
        } else {
            // On-account payment: FIFO allocation against unpaid / partially paid invoices
            $remaining = $amount;
            $unpaidInvoices = $this->db->table('mst_purchases')
                ->where('supplier_id', $supplierId)
                ->whereIn('payment_status', ['unpaid', 'partially_paid'])
                ->orderBy('invoice_date', 'ASC')
                ->orderBy('purchase_id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($unpaidInvoices as $uInv) {
                if ($remaining <= 0) break;
                $dueOnInv = max(0, (float)$uInv['net_amount'] - (float)$uInv['paid_amount']);
                if ($dueOnInv <= 0) continue;

                $alloc = min($remaining, $dueOnInv);
                $newPaid = (float)$uInv['paid_amount'] + $alloc;
                $newStatus = ($newPaid >= (float)$uInv['net_amount']) ? 'paid' : 'partially_paid';

                $this->db->table('mst_purchases')->where('purchase_id', $uInv['purchase_id'])->update([
                    'paid_amount'    => $newPaid,
                    'payment_status' => $newStatus
                ]);

                $remaining -= $alloc;
            }
        }

        // 3. Double-entry ledger in mst_ledger_entries: Dr Sundry Creditor, Cr Cash/Bank
        $creditorHead = $this->db->table('mst_account_heads')->where('head_code', '2001')->get()->getRowArray();
        $sourceHeadCode = ($paymentMode === 'Cash') ? '1001' : '1002';
        $sourceHead = $this->db->table('mst_account_heads')->where('head_code', $sourceHeadCode)->get()->getRowArray();

        if ($creditorHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id'        => $storeId,
                'voucher_no'      => $vNo,
                'voucher_type'    => 'PAYMENT',
                'voucher_date'    => $paymentDate,
                'account_head_id' => $creditorHead['head_id'],
                'debit_amount'    => $amount,
                'credit_amount'   => 0,
                'reference_type'  => 'supplier_payment',
                'reference_id'    => $supplierId,
                'narration'       => "Payment to Supplier #$supplierId via $paymentMode " . ($refNo ? "Ref: $refNo" : "")
            ]);
        }

        if ($sourceHead) {
            $this->db->table('mst_ledger_entries')->insert([
                'store_id'        => $storeId,
                'voucher_no'      => $vNo,
                'voucher_type'    => 'PAYMENT',
                'voucher_date'    => $paymentDate,
                'account_head_id' => $sourceHead['head_id'],
                'debit_amount'    => 0,
                'credit_amount'   => $amount,
                'reference_type'  => 'supplier_payment',
                'reference_id'    => $supplierId,
                'narration'       => "Payment outflow via $paymentMode to Supplier #$supplierId"
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to record supplier payment.']);
        }

        return $this->response->setJSON([
            'status'     => 1,
            'message'    => 'Supplier payment recorded successfully.',
            'voucher_no' => $vNo,
            'payment_id' => $paymentId
        ]);
    }

    // =========================================================================
    // 5B. PURCHASE ORDERS (PO)
    // =========================================================================

    public function getPurchaseOrders()
    {
        $storeId = (int)($this->request->getGet('store_id') ?: 0);
        $status = trim($this->request->getGet('status') ?: '');

        $builder = $this->db->table('mst_purchase_orders po')
            ->select('po.*, s.supplier_name, s.phone, s.contact_person, s.address, s.gstin')
            ->join('mst_suppliers s', 's.supplier_id = po.supplier_id')
            ->orderBy('po.po_date', 'DESC')
            ->orderBy('po.po_id', 'DESC');

        if ($storeId > 0) {
            $builder->where('po.store_id', $storeId);
        }
        if (!empty($status) && $status !== 'all') {
            $builder->where('po.status', $status);
        }

        $orders = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'purchase_orders' => $orders
        ]);
    }

    public function getPurchaseOrderDetail(int $poId)
    {
        $po = $this->db->table('mst_purchase_orders po')
            ->select('po.*, s.supplier_name, s.contact_person, s.phone, s.email, s.address, s.gstin, s.dl_no_20b, s.dl_no_21b')
            ->join('mst_suppliers s', 's.supplier_id = po.supplier_id')
            ->where('po.po_id', $poId)
            ->get()
            ->getRowArray();

        if (!$po) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Purchase order not found.']);
        }

        $items = $this->db->table('mst_purchase_order_items')
            ->where('po_id', $poId)
            ->orderBy('po_item_id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'purchase_order' => $po,
            'items' => $items
        ]);
    }

    public function savePurchaseOrder()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $storeId = (int)($json['store_id'] ?? 1);
        $supplierId = (int)($json['supplier_id'] ?? 0);
        $items = $json['items'] ?? [];
        $expectedDate = !empty($json['expected_delivery_date']) ? trim($json['expected_delivery_date']) : date('Y-m-d', strtotime('+3 days'));
        $remarks = trim($json['remarks'] ?? '');

        if ($supplierId <= 0 || empty($items)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Supplier and order items are required.']);
        }

        $poNumber = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);

        $totalEstAmount = 0;
        $orderItems = [];

        foreach ($items as $it) {
            $itemId = (int)($it['item_id'] ?? 0);
            $itemName = trim($it['item_name'] ?? '');
            $orderPacks = max(1, (int)($it['order_packs'] ?? 1));
            $unitsPerPack = max(1, (int)($it['units_per_pack'] ?? 10));
            $estPtr = (float)($it['estimated_ptr'] ?? 0);
            $gstRate = (float)($it['gst_rate'] ?? 12.00);

            $lineNet = $orderPacks * $estPtr;
            $lineTotal = round($lineNet * (1 + ($gstRate / 100)), 2);
            $totalEstAmount += $lineTotal;

            $orderItems[] = [
                'item_id'          => $itemId > 0 ? $itemId : null,
                'item_name'        => $itemName,
                'generic_name'     => trim($it['generic_name'] ?? ''),
                'category'         => trim($it['category'] ?? 'Tablet'),
                'order_packs'      => $orderPacks,
                'units_per_pack'   => $unitsPerPack,
                'estimated_ptr'    => $estPtr,
                'gst_rate'         => $gstRate,
                'estimated_amount' => $lineTotal
            ];
        }

        $this->db->transStart();

        $this->db->table('mst_purchase_orders')->insert([
            'store_id'               => $storeId,
            'supplier_id'            => $supplierId,
            'po_number'              => $poNumber,
            'po_date'                => date('Y-m-d'),
            'expected_delivery_date' => $expectedDate,
            'total_items'            => count($orderItems),
            'total_estimated_amount' => round($totalEstAmount, 2),
            'status'                 => 'ordered',
            'remarks'                => $remarks,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s')
        ]);
        $poId = $this->db->insertID();

        foreach ($orderItems as &$oi) {
            $oi['po_id'] = $poId;
            $this->db->table('mst_purchase_order_items')->insert($oi);
        }
        unset($oi);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 0, 'message' => 'Failed to create purchase order.']);
        }

        return $this->response->setJSON([
            'status'                 => 1,
            'message'                => 'Purchase order created successfully.',
            'po_id'                  => $poId,
            'po_number'              => $poNumber,
            'total_estimated_amount' => round($totalEstAmount, 2)
        ]);
    }

    public function cancelPurchaseOrder()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $poId = (int)($json['po_id'] ?? 0);
        if ($poId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Valid PO ID required.']);
        }

        $this->db->table('mst_purchase_orders')
            ->where('po_id', $poId)
            ->update([
                'status'     => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $this->response->setJSON(['status' => 1, 'message' => 'Purchase order marked as cancelled.']);
    }

    // =========================================================================
    // 5C. SHORT ITEMS & DEFICIENCY BOOK
    // =========================================================================

    public function getShortItems()
    {
        $storeId = (int)($this->request->getGet('store_id') ?: 1);

        // Fetch items with current stock aggregated across batches for this store
        $items = $this->db->table('mst_items i')
            ->select('i.item_id, i.item_name, i.category, i.unit_pack, i.units_per_pack, i.min_reorder_level, i.drug_schedule, i.hsn_code, i.gst_rate, COALESCE(SUM(s.current_qty), 0) as current_qty')
            ->join('mst_stock s', "s.item_id = i.item_id AND s.store_id = {$storeId}", 'left')
            ->where('i.is_active', 1)
            ->groupBy('i.item_id')
            ->having('current_qty <= i.min_reorder_level OR current_qty = 0')
            ->orderBy('current_qty', 'ASC')
            ->orderBy('i.item_name', 'ASC')
            ->get()
            ->getResultArray();

        $shortList = [];
        foreach ($items as $it) {
            $itemId = (int)$it['item_id'];
            $currentQty = (int)$it['current_qty'];
            $minReorder = max(1, (int)$it['min_reorder_level']);
            $unitsPerPack = max(1, (int)$it['units_per_pack']);

            // Find last purchase details for supplier and PTR
            $lastPurchase = $this->db->table('mst_purchase_items pi')
                ->select('pi.ptr, pi.mrp, pi.gst_rate, p.supplier_id, p.received_date, s.supplier_name')
                ->join('mst_purchases p', 'p.purchase_id = pi.purchase_id')
                ->join('mst_suppliers s', 's.supplier_id = p.supplier_id')
                ->where('pi.item_id', $itemId)
                ->orderBy('p.received_date', 'DESC')
                ->orderBy('p.purchase_id', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $lastPtr = (float)($lastPurchase['ptr'] ?? 0);
            $lastMrp = (float)($lastPurchase['mrp'] ?? 0);
            $lastSupplierId = (int)($lastPurchase['supplier_id'] ?? 0);
            $lastSupplierName = $lastPurchase['supplier_name'] ?? 'Not Assigned';
            $gstRate = (float)($lastPurchase['gst_rate'] ?? $it['gst_rate'] ?? 12.00);

            // Suggested pack calculation: reorder up to (min_reorder_level * 2) units
            $deficitUnits = max($unitsPerPack, ($minReorder * 2) - $currentQty);
            $suggestedPacks = max(1, (int)ceil($deficitUnits / $unitsPerPack));

            $status = ($currentQty <= 0) ? 'OUT_OF_STOCK' : 'LOW_STOCK';

            $shortList[] = [
                'item_id'            => $itemId,
                'item_name'          => $it['item_name'],
                'category'           => $it['category'],
                'unit_pack'          => $it['unit_pack'],
                'units_per_pack'     => $unitsPerPack,
                'min_reorder_level'  => $minReorder,
                'current_qty'        => $currentQty,
                'current_packs'      => round($currentQty / $unitsPerPack, 1),
                'suggested_packs'    => $suggestedPacks,
                'status'             => $status,
                'drug_schedule'      => $it['drug_schedule'],
                'last_ptr'           => $lastPtr,
                'last_mrp'           => $lastMrp,
                'gst_rate'           => $gstRate,
                'last_supplier_id'   => $lastSupplierId,
                'last_supplier_name' => $lastSupplierName,
                'last_purchase_date' => $lastPurchase['received_date'] ?? null
            ];
        }

        return $this->response->setJSON([
            'status'      => 1,
            'store_id'    => $storeId,
            'count'       => count($shortList),
            'short_items' => $shortList
        ]);
    }

    public function flagShortItem()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $itemId = (int)($json['item_id'] ?? 0);
        $minReorder = (int)($json['min_reorder_level'] ?? 10);

        if ($itemId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Valid Item ID is required.']);
        }

        $this->db->table('mst_items')->where('item_id', $itemId)->update([
            'min_reorder_level' => $minReorder
        ]);

        return $this->response->setJSON([
            'status'            => 1,
            'message'           => 'Item reorder level updated and tracked in Short Book.',
            'item_id'           => $itemId,
            'min_reorder_level' => $minReorder
        ]);
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

    public function getSupplierLedgerSummary()
    {
        $suppliers = $this->db->table('mst_suppliers')
            ->where('is_active', 1)
            ->orderBy('supplier_name', 'ASC')
            ->get()
            ->getResultArray();

        $result = [];
        $totalAllPurchases = 0;
        $totalAllPaid = 0;
        $totalAllBalance = 0;
        $totalPendingBills = 0;

        foreach ($suppliers as $sup) {
            $supId = (int)$sup['supplier_id'];

            // Purchases total
            $purchases = $this->db->table('mst_purchases')
                ->select('SUM(net_amount) as total_purchases, SUM(paid_amount) as total_paid_in_invoices, COUNT(*) as invoice_count, MAX(invoice_date) as last_invoice_date')
                ->where('supplier_id', $supId)
                ->get()
                ->getRowArray();

            $totalPurchases = (float)($purchases['total_purchases'] ?? 0);

            // Payments total from mst_supplier_payments
            $pmts = $this->db->table('mst_supplier_payments')
                ->select('SUM(amount) as total_payments, COUNT(*) as payment_count, MAX(payment_date) as last_payment_date')
                ->where('supplier_id', $supId)
                ->get()
                ->getRowArray();

            $totalPayments = (float)($pmts['total_payments'] ?? 0);
            $effectivePaid = max($totalPayments, (float)($purchases['total_paid_in_invoices'] ?? 0));
            $balance = max(0, $totalPurchases - $effectivePaid);

            // Pending invoices count
            $pendingCount = $this->db->table('mst_purchases')
                ->where('supplier_id', $supId)
                ->whereIn('payment_status', ['unpaid', 'partially_paid'])
                ->countAllResults();

            $supData = [
                'supplier_id'         => $supId,
                'supplier_name'       => $sup['supplier_name'],
                'contact_person'      => $sup['contact_person'] ?? '',
                'phone'               => $sup['phone'] ?? '',
                'email'               => $sup['email'] ?? '',
                'gstin'               => $sup['gstin'] ?? '',
                'city'                => $sup['city'] ?? '',
                'total_purchases'     => round($totalPurchases, 2),
                'total_paid'          => round($effectivePaid, 2),
                'outstanding_balance' => round($balance, 2),
                'invoice_count'       => (int)($purchases['invoice_count'] ?? 0),
                'pending_invoices'    => $pendingCount,
                'last_purchase_date'  => $purchases['last_invoice_date'] ?? null,
                'last_payment_date'   => $pmts['last_payment_date'] ?? null,
            ];

            $totalAllPurchases += $totalPurchases;
            $totalAllPaid += $effectivePaid;
            $totalAllBalance += $balance;
            $totalPendingBills += $pendingCount;

            $result[] = $supData;
        }

        return $this->response->setJSON([
            'status' => 1,
            'suppliers' => $result,
            'summary' => [
                'total_purchases'     => round($totalAllPurchases, 2),
                'total_paid'          => round($totalAllPaid, 2),
                'outstanding_balance' => round($totalAllBalance, 2),
                'total_pending_bills' => $totalPendingBills,
                'total_suppliers'     => count($result)
            ]
        ]);
    }

    public function getSupplierLedger(int $supplierId)
    {
        $supplier = $this->db->table('mst_suppliers')->where('supplier_id', $supplierId)->get()->getRowArray();
        if (!$supplier) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Supplier not found.']);
        }

        // 1. All invoices
        $invoices = $this->db->table('mst_purchases')
            ->where('supplier_id', $supplierId)
            ->orderBy('invoice_date', 'DESC')
            ->orderBy('purchase_id', 'DESC')
            ->get()
            ->getResultArray();

        $totalPurchases = 0;
        $totalInvoicePaid = 0;
        $pendingInvoices = [];
        foreach ($invoices as &$inv) {
            $net = (float)$inv['net_amount'];
            $paid = (float)$inv['paid_amount'];
            $pending = max(0, $net - $paid);
            $inv['pending_amount'] = round($pending, 2);
            $totalPurchases += $net;
            $totalInvoicePaid += $paid;
            if ($pending > 0 || in_array($inv['payment_status'], ['unpaid', 'partially_paid'])) {
                $pendingInvoices[] = $inv;
            }
        }
        unset($inv);

        // 2. All payments
        $payments = $this->db->table('mst_supplier_payments')
            ->where('supplier_id', $supplierId)
            ->orderBy('payment_date', 'DESC')
            ->orderBy('payment_id', 'DESC')
            ->get()
            ->getResultArray();

        $totalPaymentsMade = 0;
        foreach ($payments as $pmt) {
            $totalPaymentsMade += (float)$pmt['amount'];
        }
        $effectivePaid = max($totalPaymentsMade, $totalInvoicePaid);
        $outstandingBalance = max(0, $totalPurchases - $effectivePaid);

        // 3. Chronological Statement (Dr / Cr)
        // Purchases are Credit (increases liability), Payments are Debit (reduces liability)
        $rawEvents = [];
        foreach ($invoices as $inv) {
            $rawEvents[] = [
                'date'        => $inv['invoice_date'],
                'sort_order'  => 1,
                'type'        => 'INVOICE',
                'voucher_no'  => $inv['supplier_invoice_no'],
                'particulars' => "Purchase Bill #" . $inv['supplier_invoice_no'] . " (" . $inv['payment_status'] . ")",
                'debit'       => 0,
                'credit'      => (float)$inv['net_amount'],
                'invoice_id'  => (int)$inv['purchase_id'],
                'payment_id'  => null,
                'payment_mode'=> null,
                'ref_no'      => null
            ];
        }

        foreach ($payments as $pmt) {
            $rawEvents[] = [
                'date'        => $pmt['payment_date'],
                'sort_order'  => 2,
                'type'        => 'PAYMENT',
                'voucher_no'  => $pmt['voucher_no'] ?: ('PMT-' . $pmt['payment_id']),
                'particulars' => "Payment via " . $pmt['payment_mode'] . ($pmt['reference_no'] ? " (Ref: " . $pmt['reference_no'] . ")" : "") . ($pmt['bank_name'] ? " (" . $pmt['bank_name'] . ")" : ""),
                'debit'       => (float)$pmt['amount'],
                'credit'      => 0,
                'invoice_id'  => (int)($pmt['purchase_id'] ?? 0),
                'payment_id'  => (int)$pmt['payment_id'],
                'payment_mode'=> $pmt['payment_mode'],
                'ref_no'      => $pmt['reference_no']
            ];
        }

        // Sort chronological (date ASC, invoices before payments on same date)
        usort($rawEvents, function ($a, $b) {
            $c = strcmp($a['date'], $b['date']);
            if ($c !== 0) return $c;
            return $a['sort_order'] - $b['sort_order'];
        });

        $runningBalance = 0;
        $statement = [];
        foreach ($rawEvents as $ev) {
            $runningBalance += ($ev['credit'] - $ev['debit']);
            $ev['balance'] = round($runningBalance, 2);
            $statement[] = $ev;
        }

        return $this->response->setJSON([
            'status' => 1,
            'supplier' => $supplier,
            'summary' => [
                'total_purchases'        => round($totalPurchases, 2),
                'total_paid'             => round($effectivePaid, 2),
                'outstanding_balance'    => round($outstandingBalance, 2),
                'total_invoices'         => count($invoices),
                'pending_invoices_count' => count($pendingInvoices),
                'pending_amount'         => round($outstandingBalance, 2)
            ],
            'pending_invoices' => $pendingInvoices,
            'invoices'         => $invoices,
            'payments'         => $payments,
            'statement'        => $statement
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
        $totalReturns = 0;
        $totalRefunds = 0;
        $cashTotal = 0;
        $upiTotal = 0;
        $cardTotal = 0;
        $creditTotal = 0;
        $cgstTotal = 0;
        $sgstTotal = 0;

        foreach ($sales as $s) {
            $totalSales += (float)$s['net_amount'];
            $retAmt = (float)($s['return_amount'] ?? 0);
            $totalReturns += $retAmt;
            $refAmt = (float)($s['refund_amount'] ?? 0);
            $totalRefunds += $refAmt;

            $cPaid = (float)$s['cash_paid'];
            if ($refAmt > 0 && ($s['refund_mode'] ?? 'Cash') === 'Cash') {
                $cPaid -= $refAmt;
            }
            $cashTotal += $cPaid;

            $uPaid = (float)$s['upi_paid'];
            if ($refAmt > 0 && ($s['refund_mode'] ?? '') === 'UPI') {
                $uPaid -= $refAmt;
            }
            $upiTotal += $uPaid;

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
                'total_returns'  => round($totalReturns, 2),
                'total_refunds'  => round($totalRefunds, 2),
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

    public function importExcel()
    {
        $admin = new \App\Controllers\Setting\MedicalStoreAdmin();
        return $admin->importExcel();
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

    public function lookupInvoiceForReturn()
    {
        $q = trim($this->request->getGet('q') ?? '');
        $storeId = (int)($this->request->getGet('store_id') ?? 1);

        if (empty($q)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Please provide an invoice number, UHID, or patient mobile.']);
        }

        $sales = $this->db->table('mst_sales')
            ->where('store_id', $storeId)
            ->groupStart()
                ->where('invoice_no', $q)
                ->orLike('invoice_no', $q)
                ->orWhere('uhid', $q)
                ->orWhere('patient_mobile', $q)
            ->groupEnd()
            ->orderBy('sale_id', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        if (empty($sales)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'No sales invoice found matching "' . $q . '".']);
        }

        $results = [];
        foreach ($sales as $sale) {
            $items = $this->db->table('mst_sales_items si')
                ->select('si.*, i.item_name, i.generic_name, i.category, i.unit_pack')
                ->join('mst_items i', 'i.item_id = si.item_id')
                ->where('si.sale_id', $sale['sale_id'])
                ->where('si.item_type', 'SALE')
                ->get()
                ->getResultArray();

            $processedItems = [];
            foreach ($items as $it) {
                // Check how many units have already been returned for this sale + item + batch
                $returnedUnits = (int)$this->db->table('mst_sales_items')
                    ->where('ref_sale_id', $sale['sale_id'])
                    ->where('item_id', $it['item_id'])
                    ->where('batch_id', $it['batch_id'])
                    ->where('item_type', 'RETURN')
                    ->selectSum('total_units', 'tot_ret')
                    ->get()
                    ->getRowArray()['tot_ret'] ?? 0;

                $soldUnits = (int)$it['total_units'];
                $remainingUnits = max(0, $soldUnits - $returnedUnits);
                $unitsPerPack = max(1, (int)$it['units_per_pack']);

                $processedItems[] = [
                    'sale_item_id'            => $it['sale_item_id'],
                    'item_id'                 => $it['item_id'],
                    'item_name'               => $it['item_name'],
                    'generic_name'            => $it['generic_name'] ?? '',
                    'category'                => $it['category'] ?? 'Tablet',
                    'batch_id'                => $it['batch_id'],
                    'batch_no'                => $it['batch_no'],
                    'expiry_date'             => $it['expiry_date'],
                    'unit_pack'               => $it['unit_pack'] ?? '10 Tablets',
                    'units_per_pack'          => $unitsPerPack,
                    'sold_sell_unit'          => $it['sell_unit'],
                    'sold_qty'                => $it['qty'],
                    'sold_total_units'        => $soldUnits,
                    'already_returned_units'  => $returnedUnits,
                    'remaining_units'         => $remainingUnits,
                    'remaining_strips'        => floor($remainingUnits / $unitsPerPack),
                    'remaining_loose'         => $remainingUnits % $unitsPerPack,
                    'effective_unit_price'    => round((float)$it['total_amount'] / max(1, $soldUnits), 2),
                    'unit_mrp'                => (float)$it['unit_mrp'],
                    'gst_rate'                => (float)$it['gst_rate'],
                    'hsn_code'                => $it['hsn_code']
                ];
            }

            $results[] = [
                'sale'  => $sale,
                'items' => $processedItems
            ];
        }

        return $this->response->setJSON([
            'status'  => 1,
            'results' => $results
        ]);
    }

    public function recentReturns()
    {
        $storeId = (int)($this->request->getGet('store_id') ?? 1);
        $returns = $this->db->table('mst_sale_returns')
            ->where('store_id', $storeId)
            ->orderBy('return_id', 'DESC')
            ->limit(30)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'  => 1,
            'returns' => $returns
        ]);
    }

    public function getCreditNoteInvoice(int $cnId)
    {
        $cn = $this->db->table('mst_sale_returns r')
            ->select('r.*, st.store_name, st.building_name, st.floor_no, st.drug_license_no_20b, st.drug_license_no_21b, st.gstin, st.state_code, st.state_name, st.registered_pharmacist_name, st.contact_phone, st.address as store_address, st.upi_id')
            ->join('mst_stores st', 'st.store_id = r.store_id')
            ->where('r.return_id', $cnId)
            ->get()
            ->getRowArray();

        if (!$cn) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Credit note not found.']);
        }

        $items = $this->db->table('mst_sale_return_items ri')
            ->select('ri.*, i.unit_pack, i.generic_name')
            ->join('mst_items i', 'i.item_id = ri.item_id')
            ->where('ri.return_id', $cnId)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'      => 1,
            'credit_note' => $cn,
            'items'       => $items
        ]);
    }
}

