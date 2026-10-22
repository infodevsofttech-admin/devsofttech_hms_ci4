<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MedicalModel;
use App\Models\PharmacyPurchaseOrderModel;
use App\Models\PharmacyPurchaseOrderItemModel;
use App\Models\PharmacyGrnModel;
use App\Models\PharmacyGrnItemModel;
use Mpdf\Mpdf;

class MedicalProcurement extends BaseController
{
    protected $db;
    protected $poModel;
    protected $poItemModel;
    protected $grnModel;
    protected $grnItemModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->poModel = new PharmacyPurchaseOrderModel();
        $this->poItemModel = new PharmacyPurchaseOrderItemModel();
        $this->grnModel = new PharmacyGrnModel();
        $this->grnItemModel = new PharmacyGrnItemModel();
    }

    private function ensurePharmacyAccess()
    {
        $user = service('auth')->user();

        $allowed = false;
        if ($user && method_exists($user, 'can')) {
            $allowed = $user->can('pharmacy.access')
                || $user->can('billing.access')
                || $user->can('pharmacy.po.create');
        }

        if (! $allowed && $user && method_exists($user, 'inGroup')) {
            $allowed = $user->inGroup('superadmin', 'admin', 'developer');
        }

        if (! $allowed) {
            $sess = session();
            if ($sess->get('logged_in') || $sess->get('user_id') || $sess->get('username') || $sess->get('agent_id')) {
                $allowed = true;
            }
        }

        if ($allowed) {
            return null;
        }

        return $this->response
            ->setStatusCode(403)
            ->setBody('<div class="alert alert-danger m-3">Access denied for Pharmacy Procurement module.</div>');
    }

    private function getCurrentUserId(): int
    {
        $user = service('auth')->user();
        if ($user && isset($user->id)) {
            return (int) $user->id;
        }
        return (int) (session()->get('user_id') ?? 1);
    }

    private function getCurrentUserLabel(): string
    {
        $user = service('auth')->user();
        if ($user) {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            return ($name !== '' ? $name : ($user->username ?? 'User')) . '[' . ($user->id ?? 0) . ']';
        }
        $legacyName = session()->get('username') ?? 'Admin';
        return $legacyName . '[' . $this->getCurrentUserId() . ']';
    }

    // =========================================================================
    // 1. PURCHASE ORDERS (PO)
    // =========================================================================

    public function po_list()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $status = trim((string)($this->request->getGet('status') ?? ''));
        $supplierId = (int)($this->request->getGet('supplier_id') ?? 0);
        $fromDate = trim((string)($this->request->getGet('from') ?? ''));
        $toDate = trim((string)($this->request->getGet('to') ?? ''));
        $search = trim((string)($this->request->getGet('q') ?? ''));

        $builder = $this->db->table('mst_purchase_orders po')
            ->select('po.*, s.name_supplier, s.contact_no AS supplier_phone')
            ->join('med_supplier s', 's.sid = po.supplier_id', 'left');

        if (!empty($status) && $status !== 'all') {
            $builder->where('po.status', $status);
        }
        if ($supplierId > 0) {
            $builder->where('po.supplier_id', $supplierId);
        }
        if (!empty($fromDate)) {
            $builder->where('po.po_date >=', $fromDate);
        }
        if (!empty($toDate)) {
            $builder->where('po.po_date <=', $toDate);
        }
        if (!empty($search)) {
            $builder->groupStart()
                ->like('po.po_number', $search)
                ->orLike('s.name_supplier', $search)
                ->groupEnd();
        }

        $orders = $builder->orderBy('po.po_id', 'DESC')->get(150)->getResultArray();

        // Get suppliers list for filter dropdown
        $suppliers = $this->db->table('med_supplier')
            ->select('sid, name_supplier')
            ->orderBy('name_supplier', 'ASC')
            ->get()
            ->getResultArray();

        return view('medical/po_list', [
            'orders'     => $orders,
            'suppliers'  => $suppliers,
            'status'     => $status,
            'supplierId' => $supplierId,
            'fromDate'   => $fromDate,
            'toDate'     => $toDate,
            'search'     => $search,
        ]);
    }

    public function po_new()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $suppliers = $this->db->table('med_supplier')
            ->select('sid, name_supplier, contact_no, city, state, gst_no')
            ->orderBy('name_supplier', 'ASC')
            ->get()
            ->getResultArray();

        $stores = [];
        if ($this->db->tableExists('mst_stores')) {
            $stores = $this->db->table('mst_stores')
                ->where('is_active', 1)
                ->orderBy('is_main_store', 'DESC')
                ->get()
                ->getResultArray();
        }

        $nextPoNumber = $this->poModel->generatePoNumber();

        return view('medical/po_form', [
            'suppliers'    => $suppliers,
            'stores'       => $stores,
            'nextPoNumber' => $nextPoNumber,
            'po'           => null,
            'poItems'      => [],
        ]);
    }

    public function po_create()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierId = (int)$this->request->getPost('supplier_id');
        $storeId = (int)($this->request->getPost('store_id') ?: 1);
        $poDate = trim((string)$this->request->getPost('po_date') ?: date('Y-m-d'));
        $expectedDelivery = trim((string)$this->request->getPost('expected_delivery_date') ?: null);
        $paymentTerms = trim((string)$this->request->getPost('payment_terms') ?: 'Net 30 Days');
        $remarks = trim((string)$this->request->getPost('remarks') ?: '');
        $status = trim((string)$this->request->getPost('status') ?: 'ordered');

        $itemsRaw = $this->request->getPost('items');
        $items = is_array($itemsRaw) ? $itemsRaw : [];

        if ($supplierId <= 0) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Please select a valid supplier.']);
        }
        if (empty($items)) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Please add at least one item to the purchase order.']);
        }

        $poNumber = $this->poModel->generatePoNumber();
        $userId = $this->getCurrentUserId();

        $totalEstimatedAmount = 0.0;
        $totalTaxAmount = 0.0;
        $validItems = [];

        foreach ($items as $row) {
            $itemId = (int)($row['item_id'] ?? 0);
            $itemName = trim((string)($row['item_name'] ?? ''));
            $genericName = trim((string)($row['generic_name'] ?? ''));
            $category = trim((string)($row['category'] ?? 'Tablet'));
            $orderPacks = (float)($row['order_packs'] ?? 0);
            $unitsPerPack = (int)($row['units_per_pack'] ?? 10);
            $estimatedPtr = (float)($row['estimated_ptr'] ?? 0);
            $gstRate = (float)($row['gst_rate'] ?? 12.0);

            if ($itemId <= 0 || $itemName === '' || $orderPacks <= 0) {
                continue;
            }

            $lineSubtotal = round($orderPacks * $estimatedPtr, 2);
            $lineTax = round($lineSubtotal * ($gstRate / 100), 2);
            $lineTotal = $lineSubtotal + $lineTax;

            $totalEstimatedAmount += $lineSubtotal;
            $totalTaxAmount += $lineTax;

            $validItems[] = [
                'item_id'          => $itemId,
                'item_name'        => $itemName,
                'generic_name'     => $genericName,
                'category'         => $category,
                'order_packs'      => $orderPacks,
                'units_per_pack'   => $unitsPerPack > 0 ? $unitsPerPack : 1,
                'received_packs'   => 0,
                'remaining_packs'  => $orderPacks,
                'estimated_ptr'    => $estimatedPtr,
                'gst_rate'         => $gstRate,
                'estimated_amount' => $lineTotal,
                'status'           => 'pending',
            ];
        }

        if (empty($validItems)) {
            return $this->response->setJSON(['status' => 0, 'message' => 'No valid item rows were submitted.']);
        }

        $this->db->transStart();

        $poHeader = [
            'store_id'               => $storeId,
            'supplier_id'            => $supplierId,
            'po_number'              => $poNumber,
            'po_date'                => $poDate,
            'expected_delivery_date' => $expectedDelivery ?: null,
            'payment_terms'          => $paymentTerms,
            'total_items'            => count($validItems),
            'total_estimated_amount' => $totalEstimatedAmount,
            'tax_amount'             => $totalTaxAmount,
            'grand_total'            => $totalEstimatedAmount + $totalTaxAmount,
            'status'                 => $status,
            'remarks'                => $remarks,
            'created_by'             => $userId,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ];

        if ($status === 'approved' || $status === 'ordered') {
            $poHeader['approved_by'] = $userId;
            $poHeader['approved_at'] = date('Y-m-d H:i:s');
        }

        $this->poModel->insert($poHeader);
        $poId = (int)$this->db->insertID();

        foreach ($validItems as &$item) {
            $item['po_id'] = $poId;
            $this->poItemModel->insert($item);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Failed to save purchase order. Database error.']);
        }

        return $this->response->setJSON([
            'status'    => 1,
            'message'   => "Purchase Order {$poNumber} created successfully!",
            'po_id'     => $poId,
            'po_number' => $poNumber,
        ]);
    }

    public function po_edit(int $poId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $po = $this->db->table('mst_purchase_orders po')
            ->select('po.*, s.name_supplier')
            ->join('med_supplier s', 's.sid = po.supplier_id', 'left')
            ->where('po.po_id', $poId)
            ->get()
            ->getRowArray();

        if (!$po) {
            return view('medical/placeholder', ['title' => 'Purchase Order Not Found']);
        }

        $items = $this->poItemModel->where('po_id', $poId)->findAll();

        $suppliers = $this->db->table('med_supplier')
            ->select('sid, name_supplier, contact_no, city, state, gst_no')
            ->orderBy('name_supplier', 'ASC')
            ->get()
            ->getResultArray();

        $stores = $this->db->tableExists('mst_stores')
            ? $this->db->table('mst_stores')->where('is_active', 1)->get()->getResultArray()
            : [];

        return view('medical/po_form', [
            'suppliers'    => $suppliers,
            'stores'       => $stores,
            'nextPoNumber' => $po['po_number'],
            'po'           => $po,
            'poItems'      => $items,
        ]);
    }

    public function po_status(int $poId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $newStatus = trim((string)$this->request->getPost('status'));
        $cancelReason = trim((string)$this->request->getPost('reason') ?: '');

        if (!in_array($newStatus, ['approved', 'ordered', 'cancelled'], true)) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Invalid status transition.']);
        }

        $po = $this->poModel->find($poId);
        if (!$po) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Purchase order not found.']);
        }

        $userId = $this->getCurrentUserId();
        $updates = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];

        if ($newStatus === 'approved' || $newStatus === 'ordered') {
            $updates['approved_by'] = $userId;
            $updates['approved_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'cancelled') {
            $updates['cancelled_by'] = $userId;
            $updates['cancelled_at'] = date('Y-m-d H:i:s');
            $updates['cancelled_reason'] = $cancelReason;
        }

        $this->poModel->update($poId, $updates);

        return $this->response->setJSON([
            'status'  => 1,
            'message' => "Purchase Order status updated to {$newStatus}.",
        ]);
    }

    public function po_print(int $poId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $po = $this->db->table('mst_purchase_orders po')
            ->select('po.*, s.name_supplier, s.contact_no, s.city, s.state, s.gst_no')
            ->join('med_supplier s', 's.sid = po.supplier_id', 'left')
            ->where('po.po_id', $poId)
            ->get()
            ->getRowArray();

        if (!$po) {
            return view('medical/placeholder', ['title' => 'Purchase Order Not Found']);
        }

        $items = $this->poItemModel->where('po_id', $poId)->findAll();

        $html = view('medical/po_print_pdf', [
            'po'    => $po,
            'items' => $items,
        ]);

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'tempDir'       => WRITEPATH . 'cache',
        ]);

        $mpdf->SetTitle('Purchase Order - ' . $po['po_number']);
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('Purchase_Order_' . $po['po_number'] . '.pdf', 'S'));
    }

    public function po_reorder_items(int $supplierId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if ($supplierId <= 0) {
            return $this->response->setJSON(['status' => 0, 'items' => []]);
        }

        // Fetch drugs supplied by this vendor or general low stock items
        $sql = "SELECT p.id AS item_id, p.item_name, p.genericname AS generic_name, 
                       p.formulation AS category, 10 AS units_per_pack,
                       IFNULL(t.purchase_price, 0) AS estimated_ptr,
                       IFNULL(t.CGST_per + t.SGST_per, 12) AS gst_rate,
                       (IFNULL(t.total_unit, 0) - IFNULL(t.total_sale_unit, 0) - IFNULL(t.total_return_unit, 0) - IFNULL(t.total_lost_unit, 0)) AS current_stock
                FROM med_product_master p
                LEFT JOIN purchase_invoice_item t ON p.id = t.item_code
                LEFT JOIN purchase_invoice m ON t.purchase_id = m.id
                WHERE (m.sid = ? OR m.sid IS NULL)
                  AND p.is_continue = 1
                GROUP BY p.id
                HAVING current_stock < 50
                ORDER BY current_stock ASC
                LIMIT 50";

        $rows = $this->db->query($sql, [$supplierId])->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'count'  => count($rows),
            'items'  => $rows,
        ]);
    }

    // =========================================================================
    // 2. GOODS RECEIVED NOTE (GRN)
    // =========================================================================

    public function grn_list()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $status = trim((string)($this->request->getGet('status') ?? ''));
        $supplierId = (int)($this->request->getGet('supplier_id') ?? 0);
        $fromDate = trim((string)($this->request->getGet('from') ?? ''));
        $toDate = trim((string)($this->request->getGet('to') ?? ''));
        $search = trim((string)($this->request->getGet('q') ?? ''));

        $builder = $this->db->table('mst_grns grn')
            ->select('grn.*, s.name_supplier, po.po_number')
            ->join('med_supplier s', 's.sid = grn.supplier_id', 'left')
            ->join('mst_purchase_orders po', 'po.po_id = grn.po_id', 'left');

        if (!empty($status) && $status !== 'all') {
            $builder->where('grn.status', $status);
        }
        if ($supplierId > 0) {
            $builder->where('grn.supplier_id', $supplierId);
        }
        if (!empty($fromDate)) {
            $builder->where('grn.grn_date >=', $fromDate);
        }
        if (!empty($toDate)) {
            $builder->where('grn.grn_date <=', $toDate);
        }
        if (!empty($search)) {
            $builder->groupStart()
                ->like('grn.grn_number', $search)
                ->orLike('grn.supplier_invoice_no', $search)
                ->orLike('po.po_number', $search)
                ->orLike('s.name_supplier', $search)
                ->groupEnd();
        }

        $grnList = $builder->orderBy('grn.grn_id', 'DESC')->get(150)->getResultArray();

        $suppliers = $this->db->table('med_supplier')
            ->select('sid, name_supplier')
            ->orderBy('name_supplier', 'ASC')
            ->get()
            ->getResultArray();

        return view('medical/grn_list', [
            'grnList'    => $grnList,
            'suppliers'  => $suppliers,
            'status'     => $status,
            'supplierId' => $supplierId,
            'fromDate'   => $fromDate,
            'toDate'     => $toDate,
            'search'     => $search,
        ]);
    }

    public function grn_new($poId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $poId = (int)$poId;
        $po = null;
        $poItems = [];

        if ($poId > 0) {
            $po = $this->db->table('mst_purchase_orders po')
                ->select('po.*, s.name_supplier')
                ->join('med_supplier s', 's.sid = po.supplier_id', 'left')
                ->where('po.po_id', $poId)
                ->get()
                ->getRowArray();

            if ($po) {
                $poItems = $this->poItemModel
                    ->where('po_id', $poId)
                    ->where('remaining_packs >', 0)
                    ->findAll();
            }
        }

        $suppliers = $this->db->table('med_supplier')
            ->select('sid, name_supplier')
            ->orderBy('name_supplier', 'ASC')
            ->get()
            ->getResultArray();

        $approvedPos = $this->db->table('mst_purchase_orders')
            ->select('po_id, po_number, po_date, supplier_id, total_estimated_amount')
            ->whereIn('status', ['approved', 'ordered', 'partially_received'])
            ->orderBy('po_id', 'DESC')
            ->get()
            ->getResultArray();

        $nextGrnNumber = $this->grnModel->generateGrnNumber();

        return view('medical/grn_form', [
            'suppliers'     => $suppliers,
            'approvedPos'   => $approvedPos,
            'selectedPo'    => $po,
            'poItems'       => $poItems,
            'nextGrnNumber' => $nextGrnNumber,
            'grn'           => null,
            'grnItems'      => [],
        ]);
    }

    public function grn_create()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierId = (int)$this->request->getPost('supplier_id');
        $poId = (int)($this->request->getPost('po_id') ?: 0);
        $challanNo = trim((string)$this->request->getPost('challan_no') ?: '');
        $supplierInvoiceNo = trim((string)$this->request->getPost('supplier_invoice_no') ?: '');
        $invoiceDate = trim((string)$this->request->getPost('invoice_date') ?: date('Y-m-d'));
        $grnDate = trim((string)$this->request->getPost('grn_date') ?: date('Y-m-d'));
        $remarks = trim((string)$this->request->getPost('remarks') ?: '');
        $postToInventory = (int)($this->request->getPost('post_to_inventory') ?: 0);
        $gstType = trim((string)$this->request->getPost('gst_type') ?: 'intra_state');
        $roundOff = (float)($this->request->getPost('round_off') ?: 0.0);

        $itemsRaw = $this->request->getPost('items');
        $items = is_array($itemsRaw) ? $itemsRaw : [];

        if ($supplierId <= 0) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Please select a supplier.']);
        }
        if (empty($supplierInvoiceNo)) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Supplier invoice / delivery bill number is required.']);
        }
        if (empty($items)) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Please add at least one received item.']);
        }

        $grnNumber = $this->grnModel->generateGrnNumber();
        $userId = $this->getCurrentUserId();

        $totalGross = 0.0;
        $totalDisc = 0.0;
        $totalTaxable = 0.0;
        $totalCgst = 0.0;
        $totalSgst = 0.0;
        $totalIgst = 0.0;
        $totalTax = 0.0;
        $validItems = [];

        foreach ($items as $row) {
            $itemCode = (int)($row['item_code'] ?? 0);
            $itemName = trim((string)($row['item_name'] ?? ''));
            $batchNo = trim((string)($row['batch_no'] ?? ''));
            $expiryDate = trim((string)($row['expiry_date'] ?? ''));
            $packing = (int)($row['packing'] ?? 1);
            if ($packing <= 0) {
                $packing = 1;
            }

            $orderPacks = (float)($row['order_packs'] ?? 0);
            $receivedPacks = (float)($row['received_packs'] ?? 0);
            $freePacks = (float)($row['free_packs'] ?? 0);
            $rejectedPacks = (float)($row['rejected_packs'] ?? 0);
            $rejectionReason = trim((string)($row['rejection_reason'] ?? ''));

            $unitPurchaseRate = (float)($row['unit_purchase_rate'] ?? 0);
            $mrp = (float)($row['mrp'] ?? 0);
            $sellingUnitRate = (float)($row['selling_unit_rate'] ?? 0);
            if ($sellingUnitRate <= 0 && $mrp > 0) {
                $sellingUnitRate = round($mrp / $packing, 4);
            }

            $discPercent = (float)($row['discount_percent'] ?? 0);
            $schDiscPer = (float)($row['sch_disc_per'] ?? 0);
            $schDiscAmt = (float)($row['sch_disc_amt'] ?? 0);

            // Determine GST total rate: check gst_rate, then fallback to cgst_percent * 2
            $gstRate = isset($row['gst_rate']) ? (float)$row['gst_rate'] : ((float)($row['cgst_percent'] ?? 2.5) * 2);

            $hsnCode = trim((string)($row['hsn_code'] ?? ''));
            $rackNo = trim((string)($row['rack_no'] ?? ''));
            $shelfNo = trim((string)($row['shelf_no'] ?? ''));
            $coldStorage = trim((string)($row['cold_storage'] ?? 'No'));
            $poItemId = (int)($row['po_item_id'] ?? 0);

            if ($itemCode <= 0 || $itemName === '' || $batchNo === '' || empty($expiryDate)) {
                continue;
            }
            if ($receivedPacks <= 0 && $freePacks <= 0) {
                continue;
            }

            // Calculations strictly aligned with Indian Pharma Invoicing:
            $grossAmount = round($receivedPacks * $unitPurchaseRate, 2);

            // 1. Scheme discount (percent or amount)
            $schPerAmt = ($schDiscPer > 0) ? round($grossAmount * ($schDiscPer / 100), 2) : 0.0;
            $lineSchDisc = $schPerAmt + $schDiscAmt;
            $afterSch = max(0.0, $grossAmount - $lineSchDisc);

            // 2. Trade discount
            $tradeDiscAmt = ($discPercent > 0) ? round($afterSch * ($discPercent / 100), 2) : 0.0;
            $lineTaxable = max(0.0, $afterSch - $tradeDiscAmt);

            // 3. GST Calculation (Equal CGST/SGST split or 100% IGST)
            if ($gstType === 'inter_state') {
                $cgstPer = 0.0;
                $sgstPer = 0.0;
                $igstPer = $gstRate;
                $cgstAmt = 0.0;
                $sgstAmt = 0.0;
                $igstAmt = round($lineTaxable * ($igstPer / 100), 2);
                $lineTax = $igstAmt;
            } else {
                $cgstPer = round($gstRate / 2, 2);
                $sgstPer = round($gstRate / 2, 2);
                $igstPer = 0.0;
                $cgstAmt = round($lineTaxable * ($cgstPer / 100), 2);
                $sgstAmt = round($lineTaxable * ($sgstPer / 100), 2);
                $igstAmt = 0.0;
                $lineTax = $cgstAmt + $sgstAmt;
            }

            $lineTotal = round($lineTaxable + $lineTax, 2);

            $totalGross += $grossAmount;
            $totalDisc += ($lineSchDisc + $tradeDiscAmt);
            $totalTaxable += $lineTaxable;
            $totalCgst += $cgstAmt;
            $totalSgst += $sgstAmt;
            $totalIgst += $igstAmt;
            $totalTax += $lineTax;

            $validItems[] = [
                'po_item_id'         => $poItemId > 0 ? $poItemId : null,
                'item_code'          => $itemCode,
                'item_name'          => $itemName,
                'batch_no'           => $batchNo,
                'expiry_date'        => $expiryDate,
                'packing'            => $packing,
                'order_packs'        => $orderPacks,
                'received_packs'     => $receivedPacks,
                'free_packs'         => $freePacks,
                'rejected_packs'     => $rejectedPacks,
                'rejection_reason'   => $rejectionReason ?: null,
                'unit_purchase_rate' => $unitPurchaseRate,
                'gross_amount'       => $grossAmount,
                'mrp'                => $mrp,
                'selling_unit_rate'  => $sellingUnitRate,
                'discount_percent'   => $discPercent,
                'sch_disc_per'       => $schDiscPer,
                'sch_disc_amt'       => $schDiscAmt,
                'taxable_amount'     => $lineTaxable,
                'cgst_percent'       => $cgstPer,
                'cgst_amount'        => $cgstAmt,
                'sgst_percent'       => $sgstPer,
                'sgst_amount'        => $sgstAmt,
                'igst_percent'       => $igstPer,
                'igst_amount'        => $igstAmt,
                'hsn_code'           => $hsnCode ?: null,
                'total_amount'       => $lineTotal,
                'rack_no'            => $rackNo ?: null,
                'shelf_no'           => $shelfNo ?: null,
                'cold_storage'       => $coldStorage ?: null,
            ];
        }

        if (empty($validItems)) {
            return $this->response->setJSON(['status' => 0, 'message' => 'No valid item rows with batch and expiry were found.']);
        }

        $netTotal = round($totalTaxable + $totalTax + $roundOff, 2);

        $this->db->transStart();

        $grnHeader = [
            'grn_number'            => $grnNumber,
            'store_id'              => 1,
            'po_id'                 => $poId > 0 ? $poId : null,
            'supplier_id'           => $supplierId,
            'challan_no'            => $challanNo ?: null,
            'supplier_invoice_no'   => $supplierInvoiceNo,
            'invoice_date'          => $invoiceDate,
            'grn_date'              => $grnDate,
            'total_received_amount' => $totalTaxable,
            'total_tax_amount'      => $totalTax,
            'cgst_amount'           => $totalCgst,
            'sgst_amount'           => $totalSgst,
            'igst_amount'           => $totalIgst,
            'round_off'             => $roundOff,
            'gst_type'              => $gstType,
            'net_amount'            => $netTotal,
            'status'                => 'verified',
            'received_by'           => $userId,
            'verified_by'           => $userId,
            'verified_at'           => date('Y-m-d H:i:s'),
            'remarks'               => $remarks,
            'created_at'            => date('Y-m-d H:i:s'),
        ];

        $this->grnModel->insert($grnHeader);
        $grnId = (int)$this->db->insertID();

        foreach ($validItems as &$gItem) {
            $gItem['grn_id'] = $grnId;
            $this->grnItemModel->insert($gItem);
        }

        // If requested to post directly to inventory
        if ($postToInventory === 1) {
            $this->executeGrnStockPosting($grnId);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            $err = $this->db->error();
            $errMsg = !empty($err['message']) ? ': ' . $err['message'] : '.';
            log_message('error', 'grn_create transaction failed' . $errMsg);
            return $this->response->setJSON(['status' => 0, 'message' => 'Failed to save Goods Received Note' . $errMsg]);
        }

        return $this->response->setJSON([
            'status'     => 1,
            'message'    => "GRN {$grnNumber} created successfully!",
            'grn_id'     => $grnId,
            'grn_number' => $grnNumber,
        ]);
    }

    public function grn_edit(int $grnId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $grn = $this->db->table('mst_grns grn')
            ->select('grn.*, s.name_supplier, po.po_number')
            ->join('med_supplier s', 's.sid = grn.supplier_id', 'left')
            ->join('mst_purchase_orders po', 'po.po_id = grn.po_id', 'left')
            ->where('grn.grn_id', $grnId)
            ->get()
            ->getRowArray();

        if (!$grn) {
            return view('medical/placeholder', ['title' => 'Goods Received Note Not Found']);
        }

        $items = $this->grnItemModel->where('grn_id', $grnId)->findAll();

        return view('medical/grn_detail', [
            'grn'      => $grn,
            'grnItems' => $items,
        ]);
    }

    public function grn_post_inventory(int $grnId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $grn = $this->grnModel->find($grnId);
        if (!$grn) {
            return $this->response->setJSON(['status' => 0, 'message' => 'GRN not found.']);
        }
        if ($grn['status'] === 'posted_to_inventory') {
            return $this->response->setJSON(['status' => 0, 'message' => 'This GRN has already been posted to live inventory.']);
        }

        $this->db->transStart();
        $ok = $this->executeGrnStockPosting($grnId);
        $this->db->transComplete();

        if ($this->db->transStatus() === false || !$ok) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Failed to post GRN to inventory.']);
        }

        return $this->response->setJSON([
            'status'  => 1,
            'message' => 'GRN successfully posted to live store inventory & purchase registers!',
        ]);
    }

    /**
     * Core bridge method that converts a verified GRN into live purchase_invoice and purchase_invoice_item
     */
    private function executeGrnStockPosting(int $grnId): bool
    {
        $grn = $this->grnModel->find($grnId);
        if (!$grn) {
            return false;
        }

        $supplier = $this->db->table('med_supplier')->where('sid', $grn['supplier_id'])->get()->getRowArray();
        $suppName = $supplier['name_supplier'] ?? 'Supplier';
        $userId = $this->getCurrentUserId();
        $userLabel = $this->getCurrentUserLabel();

        // 1. Insert into purchase_invoice
        $purchaseInvoiceData = [
            'sid'                  => $grn['supplier_id'],
            'ischallan'            => !empty($grn['challan_no']) ? 1 : 0,
            'monthyear'            => date('m/Y', strtotime($grn['invoice_date'])),
            'supp_name'            => $suppName,
            'date_of_invoice'      => $grn['invoice_date'],
            'Invoice_no'           => $grn['supplier_invoice_no'],
            'Taxable_Amt'          => $grn['total_received_amount'],
            'CGST_Amt'             => (float)($grn['cgst_amount'] ?? round($grn['total_tax_amount'] / 2, 2)),
            'SGST_Amt'             => (float)($grn['sgst_amount'] ?? round($grn['total_tax_amount'] / 2, 2)),
            'T_Net_Amount'         => $grn['net_amount'],
            'inv_status'           => 1,
            'inv_status_update_by' => $userId,
        ];

        $this->db->table('purchase_invoice')->insert($purchaseInvoiceData);
        $purchaseId = (int)$this->db->insertID();

        if ($purchaseId <= 0) {
            return false;
        }

        $medModel = new MedicalModel();
        $grnItems = $this->grnItemModel->where('grn_id', $grnId)->findAll();

        // 2. Insert into purchase_invoice_item & trigger p_stock_update_purchase_id
        foreach ($grnItems as $gItem) {
            $packing = (int)($gItem['packing'] ?: 1);
            $receivedPacks = (float)$gItem['received_packs'];
            $freePacks = (float)$gItem['free_packs'];
            $totalUnits = (int)round(($receivedPacks + $freePacks) * $packing);

            $purchasePricePerPack = (float)$gItem['unit_purchase_rate'];
            $purchaseUnitRate = $packing > 0 ? round($purchasePricePerPack / $packing, 4) : $purchasePricePerPack;

            $piItemData = [
                'purchase_id'        => $purchaseId,
                'item_code'          => $gItem['item_code'],
                'Item_name'          => $gItem['item_name'],
                'packing'            => $packing,
                'batch_no'           => $gItem['batch_no'],
                'purchase_price'     => $purchasePricePerPack,
                'expiry_date'        => $gItem['expiry_date'],
                'mrp'                => $gItem['mrp'],
                'qty'                => $receivedPacks,
                'qty_free'           => $freePacks,
                'tqty'               => $receivedPacks + $freePacks,
                'amount'             => (float)($gItem['gross_amount'] ?: round($receivedPacks * $purchasePricePerPack, 2)),
                'discount'           => (float)($gItem['discount_percent'] ?? 0),
                'sch_disc_amt'       => (float)($gItem['sch_disc_amt'] ?? 0),
                'sch_disc_per'       => (float)($gItem['sch_disc_per'] ?? 0),
                'taxable_amount'     => (float)$gItem['taxable_amount'],
                'CGST_per'           => (float)$gItem['cgst_percent'],
                'CGST'               => (float)$gItem['cgst_amount'],
                'SGST_per'           => (float)$gItem['sgst_percent'],
                'SGST'               => (float)$gItem['sgst_amount'],
                'net_amount'         => (float)$gItem['total_amount'],
                'HSNCODE'            => $gItem['hsn_code'] ?: '',
                'selling_price'      => $gItem['selling_unit_rate'] * $packing,
                'total_unit'         => $totalUnits,
                'total_sale_unit'    => 0,
                'total_return_unit'  => 0,
                'total_lost_unit'    => 0,
                'selling_unit_rate'  => $gItem['selling_unit_rate'],
                'purchase_unit_rate' => $purchaseUnitRate,
                'item_return'        => 0,
                'remove_item'        => 0,
                'stock_date'         => $grn['invoice_date'],
                'rack_no'            => $gItem['rack_no'] ?: '',
                'shelf_no'           => $gItem['shelf_no'] ?: '',
                'cold_storage'       => $gItem['cold_storage'] ?: 'No',
                'insert_by'          => $userLabel,
            ];

            $this->db->table('purchase_invoice_item')->insert($piItemData);
            $purchaseItemId = (int)$this->db->insertID();

            // Link purchase_item_id on GRN item row
            $this->grnItemModel->update($gItem['grn_item_id'], ['purchase_item_id' => $purchaseItemId]);

            // Call stored procedure to sync live inventory
            $medModel->updatePurchaseStockById($purchaseItemId);

            // Reconcile with PO item if linked
            if (!empty($gItem['po_item_id'])) {
                $poItem = $this->poItemModel->find($gItem['po_item_id']);
                if ($poItem) {
                    $newReceived = (float)$poItem['received_packs'] + $receivedPacks;
                    $orderPacks = (float)$poItem['order_packs'];
                    $remaining = max(0, $orderPacks - $newReceived);
                    $itemStatus = ($remaining <= 0) ? 'fulfilled' : 'partial';

                    $this->poItemModel->update($gItem['po_item_id'], [
                        'received_packs'  => $newReceived,
                        'remaining_packs' => $remaining,
                        'status'          => $itemStatus,
                    ]);
                }
            }
        }

        // 3. Update PO Header status if linked
        if (!empty($grn['po_id'])) {
            $unfulfilled = $this->poItemModel
                ->where('po_id', $grn['po_id'])
                ->where('remaining_packs >', 0)
                ->countAllResults();

            $newPoStatus = ($unfulfilled === 0) ? 'received' : 'partially_received';
            $this->poModel->update($grn['po_id'], [
                'status'     => $newPoStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // 4. Update GRN status
        $this->grnModel->update($grnId, [
            'status'      => 'posted_to_inventory',
            'purchase_id' => $purchaseId,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function grn_print(int $grnId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $grn = $this->db->table('mst_grns grn')
            ->select('grn.*, s.name_supplier, s.contact_no, s.city, s.state, s.gst_no, po.po_number')
            ->join('med_supplier s', 's.sid = grn.supplier_id', 'left')
            ->join('mst_purchase_orders po', 'po.po_id = grn.po_id', 'left')
            ->where('grn.grn_id', $grnId)
            ->get()
            ->getRowArray();

        if (!$grn) {
            return view('medical/placeholder', ['title' => 'Goods Received Note Not Found']);
        }

        $items = $this->grnItemModel->where('grn_id', $grnId)->findAll();

        $html = view('medical/grn_print_pdf', [
            'grn'   => $grn,
            'items' => $items,
        ]);

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'L',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'tempDir'       => WRITEPATH . 'cache',
        ]);

        $mpdf->SetTitle('GRN Inspection Slip - ' . $grn['grn_number']);
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('GRN_' . $grn['grn_number'] . '.pdf', 'S'));
    }
}
