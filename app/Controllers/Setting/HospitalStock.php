<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\HospitalStockCategoryModel;
use App\Models\HospitalStockItemModel;
use App\Models\HospitalStockSupplierModel;

class HospitalStock extends BaseController
{
    private const PERM_ACCESS = 'hospital_stock.access';
    private const PERM_MASTER_MANAGE = 'hospital_stock.master.manage';
    private const PERM_INDENT_CREATE = 'hospital_stock.indent.create';
    private const PERM_INDENT_APPROVE = 'hospital_stock.indent.approve';
    private const PERM_ISSUE = 'hospital_stock.issue';
    private const PERM_PURCHASE_MANAGE = 'hospital_stock.purchase.manage';
    private const PERM_REPORT_VIEW = 'hospital_stock.report.view';

    public function index(): string
    {
        if (! $this->hasAnyPermission([
            self::PERM_ACCESS,
            self::PERM_MASTER_MANAGE,
            self::PERM_INDENT_CREATE,
            self::PERM_INDENT_APPROVE,
            self::PERM_ISSUE,
            self::PERM_PURCHASE_MANAGE,
            self::PERM_REPORT_VIEW,
        ])) {
            return '<div class="alert alert-danger m-3">Access denied.</div>';
        }

        if (! $this->isModuleReady()) {
            return '<div class="alert alert-warning m-3">Hospital stock tables are missing. Please run <strong>php spark migrate</strong>.</div>';
        }

        return view('Setting/Stock/index', [
            'canMasterManage' => $this->userCan(self::PERM_MASTER_MANAGE),
            'canIndentCreate' => $this->userCan(self::PERM_INDENT_CREATE),
            'canIndentApprove' => $this->userCan(self::PERM_INDENT_APPROVE),
            'canIssue' => $this->userCan(self::PERM_ISSUE),
            'canPurchaseManage' => $this->userCan(self::PERM_PURCHASE_MANAGE),
            'canReportView' => $this->userCan(self::PERM_REPORT_VIEW),
        ]);
    }

    public function dashboard(): string
    {
        if (! $this->userCan(self::PERM_REPORT_VIEW)) {
            return '<div class="alert alert-danger">Access denied.</div>';
        }

        $stats = $this->getStats();

        $newRequests = $this->db->table('hsm_indents')
            ->where('status', 'pending')
            ->orderBy('id', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $nearExpiryFrequent = $this->db->query(
            "SELECT i.item_code, i.name, i.current_stock, i.expiry_date, IFNULL(SUM(ii.qty),0) AS used_qty
             FROM hsm_items i
             LEFT JOIN hsm_issue_items ii ON ii.item_id = i.id
             LEFT JOIN hsm_issues hs ON hs.id = ii.issue_id AND DATE(hs.created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
             WHERE i.expiry_date IS NOT NULL
               AND DATE(i.expiry_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
             GROUP BY i.id, i.item_code, i.name, i.current_stock, i.expiry_date
             HAVING used_qty > 0
             ORDER BY i.expiry_date ASC, used_qty DESC
             LIMIT 25"
        )->getResultArray();

        $lowStockNoRequest = $this->db->query(
            "SELECT i.item_code, i.name, i.current_stock, i.reorder_level
             FROM hsm_items i
             WHERE i.current_stock <= i.reorder_level
               AND NOT EXISTS (
                    SELECT 1
                    FROM hsm_indent_items hi
                    JOIN hsm_indents h ON h.id = hi.indent_id
                    WHERE hi.item_id = i.id
                      AND h.status IN ('pending','approved','partial_issued')
               )
               AND NOT EXISTS (
                    SELECT 1
                    FROM hsm_purchase_order_items pi
                    JOIN hsm_purchase_orders p ON p.id = pi.purchase_order_id
                    WHERE pi.item_id = i.id
                      AND p.status IN ('ordered','partial_received')
               )
             ORDER BY i.current_stock ASC
             LIMIT 25"
        )->getResultArray();

        $dailyUseStatus = $this->db->query(
            "SELECT i.item_code, i.name, i.current_stock,
                    IFNULL(SUM(CASE WHEN DATE(hs.created_at)=CURDATE() THEN ii.qty ELSE 0 END),0) AS issued_today,
                    IFNULL(SUM(CASE WHEN DATE(hs.created_at)>=DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN ii.qty ELSE 0 END),0) AS issued_7d
             FROM hsm_items i
             LEFT JOIN hsm_issue_items ii ON ii.item_id = i.id
             LEFT JOIN hsm_issues hs ON hs.id = ii.issue_id
             WHERE IFNULL(i.is_daily_use,0) = 1
             GROUP BY i.id, i.item_code, i.name, i.current_stock
             ORDER BY i.name ASC
             LIMIT 30"
        )->getResultArray();

        return view('Setting/Stock/dashboard', [
            'stats' => $stats,
            'newRequests' => $newRequests,
            'nearExpiryFrequent' => $nearExpiryFrequent,
            'lowStockNoRequest' => $lowStockNoRequest,
            'dailyUseStatus' => $dailyUseStatus,
        ]);
    }

    public function masters(): string
    {
        if (! $this->userCan(self::PERM_MASTER_MANAGE)) {
            return '<div class="alert alert-danger">Access denied.</div>';
        }

        $categories = (new HospitalStockCategoryModel())->orderBy('name', 'ASC')->findAll();
        $items = $this->db->table('hsm_items i')
            ->select('i.*, c.name AS category_name')
            ->join('hsm_categories c', 'c.id = i.category_id', 'left')
            ->orderBy('i.id', 'DESC')
            ->get()
            ->getResultArray();
        $suppliers = (new HospitalStockSupplierModel())->orderBy('id', 'DESC')->findAll();

        return view('Setting/Stock/masters', [
            'categories' => $categories,
            'items' => $items,
            'suppliers' => $suppliers,
        ]);
    }

    public function indents(): string
    {
        if (! $this->hasAnyPermission([self::PERM_INDENT_CREATE, self::PERM_INDENT_APPROVE, self::PERM_ISSUE])) {
            return '<div class="alert alert-danger">Access denied.</div>';
        }

        $items = $this->db->table('hsm_items')->select('id, item_code, name')->where('status', 'active')->orderBy('name', 'ASC')->get()->getResultArray();
        $indents = $this->db->table('hsm_indents')->orderBy('id', 'DESC')->limit(100)->get()->getResultArray();

        $departments = [];
        if ($this->db->tableExists('hc_department')) {
            $departments = $this->db->table('hc_department')
                ->select('iId, vName')
                ->orderBy('vName', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('Setting/Stock/indents', [
            'items' => $items,
            'indents' => $indents,
            'departments' => $departments,
            'canIndentCreate' => $this->userCan(self::PERM_INDENT_CREATE),
            'canIndentApprove' => $this->userCan(self::PERM_INDENT_APPROVE),
            'canIssue' => $this->userCan(self::PERM_ISSUE),
        ]);
    }

    public function purchase(): string
    {
        if (! $this->userCan(self::PERM_PURCHASE_MANAGE)) {
            return '<div class="alert alert-danger">Access denied.</div>';
        }

        $items = $this->db->table('hsm_items')->select('id, item_code, name')->where('status', 'active')->orderBy('name', 'ASC')->get()->getResultArray();
        $suppliers = (new HospitalStockSupplierModel())->where('status', 'active')->orderBy('name', 'ASC')->findAll();
        $purchaseOrders = $this->db->table('hsm_purchase_orders p')
            ->select('p.*, s.name AS supplier_name')
            ->join('hsm_suppliers s', 's.id = p.supplier_id', 'left')
            ->orderBy('p.id', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return view('Setting/Stock/purchase', [
            'items' => $items,
            'suppliers' => $suppliers,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function reports(): string
    {
        if (! $this->userCan(self::PERM_REPORT_VIEW)) {
            return '<div class="alert alert-danger">Access denied.</div>';
        }

        $alerts = $this->db->table('hsm_items i')
            ->select('i.item_code, i.name, i.current_stock, i.min_stock_level, i.reorder_level, i.expiry_date')
            ->groupStart()
                ->where('i.current_stock <= i.min_stock_level', null, false)
                ->orWhere('i.current_stock <= i.reorder_level', null, false)
            ->groupEnd()
            ->orderBy('i.current_stock', 'ASC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return view('Setting/Stock/reports', [
            'alerts' => $alerts,
        ]);
    }

    public function saveCategory()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $name = trim((string) $this->request->getPost('name'));
        $status = trim((string) ($this->request->getPost('status') ?? 'active'));

        if ($name === '') {
            return $this->jsonError('Category name is required.');
        }

        $exists = $this->db->query('SELECT id FROM hsm_categories WHERE LOWER(name) = ? LIMIT 1', [strtolower($name)])->getNumRows();
        if ($exists > 0) {
            return $this->jsonError('Category already exists.');
        }

        $id = (new HospitalStockCategoryModel())->insert([
            'name' => $name,
            'description' => $this->request->getPost('description'),
            'status' => in_array($status, ['active', 'inactive'], true) ? $status : 'active',
        ]);

        $this->auditLog('category', 'create', 'hsm_categories', (int) $id, 'Created category: ' . $name, ['name' => $name]);

        return $this->jsonSuccess('Category saved successfully.', ['insertid' => $id]);
    }

    public function updateCategory()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        if ($id <= 0 || $name === '') {
            return $this->jsonError('Invalid category data.');
        }

        $exists = $this->db->query('SELECT id FROM hsm_categories WHERE LOWER(name)=? AND id!=? LIMIT 1', [strtolower($name), $id])->getNumRows();
        if ($exists > 0) {
            return $this->jsonError('Category already exists.');
        }

        (new HospitalStockCategoryModel())->update($id, [
            'name' => $name,
            'description' => $this->request->getPost('description'),
            'status' => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ]);

        $this->auditLog('category', 'update', 'hsm_categories', $id, 'Updated category: ' . $name);

        return $this->jsonSuccess('Category updated successfully.');
    }

    public function deleteCategory()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->jsonError('Invalid category id.');
        }

        $itemCount = $this->db->table('hsm_items')->where('category_id', $id)->countAllResults();
        if ($itemCount > 0) {
            return $this->jsonError('Cannot delete category with mapped items.');
        }

        (new HospitalStockCategoryModel())->delete($id);
        $this->auditLog('category', 'delete', 'hsm_categories', $id, 'Deleted category id: ' . $id);

        return $this->jsonSuccess('Category deleted successfully.');
    }

    public function saveItem()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $itemCode = trim((string) $this->request->getPost('item_code'));
        $name = trim((string) $this->request->getPost('name'));
        $categoryId = (int) $this->request->getPost('category_id');

        if ($itemCode === '' || $name === '' || $categoryId <= 0) {
            return $this->jsonError('Item code, name and category are required.');
        }

        $exists = $this->db->table('hsm_items')->where('item_code', $itemCode)->countAllResults();
        if ($exists > 0) {
            return $this->jsonError('Item code already exists.');
        }

        $model = new HospitalStockItemModel();
        $id = $model->insert([
            'item_code' => $itemCode,
            'name' => $name,
            'category_id' => $categoryId,
            'item_type' => trim((string) ($this->request->getPost('item_type') ?? '')),
            'uom' => trim((string) ($this->request->getPost('uom') ?? 'Unit')),
            'purchase_uom' => trim((string) ($this->request->getPost('purchase_uom') ?? 'Unit')),
            'issue_uom' => trim((string) ($this->request->getPost('issue_uom') ?? 'Unit')),
            'issue_per_purchase' => (float) ($this->request->getPost('issue_per_purchase') ?? 1),
            'is_daily_use' => $this->request->getPost('is_daily_use') ? 1 : 0,
            'store_location' => trim((string) ($this->request->getPost('store_location') ?? '')),
            'barcode' => trim((string) ($this->request->getPost('barcode') ?? '')),
            'qr_code' => trim((string) ($this->request->getPost('qr_code') ?? '')),
            'current_stock' => (float) ($this->request->getPost('current_stock') ?? 0),
            'min_stock_level' => (float) ($this->request->getPost('min_stock_level') ?? 0),
            'reorder_level' => (float) ($this->request->getPost('reorder_level') ?? 0),
            'unit_cost' => (float) ($this->request->getPost('unit_cost') ?? 0),
            'expiry_date' => $this->request->getPost('expiry_date') ?: null,
            'status' => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ]);

        $openingStock = (float) ($this->request->getPost('current_stock') ?? 0);
        if ($openingStock > 0) {
            $this->appendStockLedger((int) $id, 'adjustment_in', 'hsm_items', (int) $id, $openingStock, 0, 'Opening stock');
        }

        $this->auditLog('item', 'create', 'hsm_items', (int) $id, 'Created item: ' . $name, ['item_code' => $itemCode]);

        return $this->jsonSuccess('Item saved successfully.', ['insertid' => $id]);
    }

    public function updateItem()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $itemCode = trim((string) $this->request->getPost('item_code'));
        $name = trim((string) $this->request->getPost('name'));
        $categoryId = (int) $this->request->getPost('category_id');

        if ($id <= 0 || $itemCode === '' || $name === '' || $categoryId <= 0) {
            return $this->jsonError('Invalid item data.');
        }

        $exists = $this->db->table('hsm_items')->where('item_code', $itemCode)->where('id !=', $id)->countAllResults();
        if ($exists > 0) {
            return $this->jsonError('Item code already exists.');
        }

        $current = $this->db->table('hsm_items')->where('id', $id)->get()->getRowArray();
        if (! $current) {
            return $this->jsonError('Item not found.');
        }

        $newStock = (float) ($this->request->getPost('current_stock') ?? 0);
        $oldStock = (float) ($current['current_stock'] ?? 0);

        (new HospitalStockItemModel())->update($id, [
            'item_code' => $itemCode,
            'name' => $name,
            'category_id' => $categoryId,
            'item_type' => trim((string) ($this->request->getPost('item_type') ?? '')),
            'uom' => trim((string) ($this->request->getPost('uom') ?? 'Unit')),
            'purchase_uom' => trim((string) ($this->request->getPost('purchase_uom') ?? 'Unit')),
            'issue_uom' => trim((string) ($this->request->getPost('issue_uom') ?? 'Unit')),
            'issue_per_purchase' => (float) ($this->request->getPost('issue_per_purchase') ?? 1),
            'is_daily_use' => $this->request->getPost('is_daily_use') ? 1 : 0,
            'store_location' => trim((string) ($this->request->getPost('store_location') ?? '')),
            'barcode' => trim((string) ($this->request->getPost('barcode') ?? '')),
            'qr_code' => trim((string) ($this->request->getPost('qr_code') ?? '')),
            'current_stock' => $newStock,
            'min_stock_level' => (float) ($this->request->getPost('min_stock_level') ?? 0),
            'reorder_level' => (float) ($this->request->getPost('reorder_level') ?? 0),
            'unit_cost' => (float) ($this->request->getPost('unit_cost') ?? 0),
            'expiry_date' => $this->request->getPost('expiry_date') ?: null,
            'status' => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ]);

        $diff = $newStock - $oldStock;
        if ($diff > 0) {
            $this->appendStockLedger($id, 'adjustment_in', 'hsm_items', $id, $diff, 0, 'Manual stock correction (+)');
        } elseif ($diff < 0) {
            $this->appendStockLedger($id, 'adjustment_out', 'hsm_items', $id, 0, abs($diff), 'Manual stock correction (-)');
        }

        $this->auditLog('item', 'update', 'hsm_items', $id, 'Updated item: ' . $name, ['item_code' => $itemCode]);

        return $this->jsonSuccess('Item updated successfully.');
    }

    public function deleteItem()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->jsonError('Invalid item id.');
        }

        $indentRows = $this->db->table('hsm_indent_items')->where('item_id', $id)->countAllResults();
        $poRows = $this->db->table('hsm_purchase_order_items')->where('item_id', $id)->countAllResults();
        $issueRows = $this->db->table('hsm_issue_items')->where('item_id', $id)->countAllResults();

        if ($indentRows > 0 || $poRows > 0 || $issueRows > 0) {
            return $this->jsonError('Cannot delete item linked with transactions.');
        }

        (new HospitalStockItemModel())->delete($id);
        $this->auditLog('item', 'delete', 'hsm_items', $id, 'Deleted item id: ' . $id);

        return $this->jsonSuccess('Item deleted successfully.');
    }

    public function saveSupplier()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return $this->jsonError('Supplier name is required.');
        }

        $id = (new HospitalStockSupplierModel())->insert([
            'name' => $name,
            'contact_person' => trim((string) ($this->request->getPost('contact_person') ?? '')),
            'phone' => trim((string) ($this->request->getPost('phone') ?? '')),
            'email' => trim((string) ($this->request->getPost('email') ?? '')),
            'address' => $this->request->getPost('address'),
            'gst_no' => trim((string) ($this->request->getPost('gst_no') ?? '')),
            'status' => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ]);

        $this->auditLog('supplier', 'create', 'hsm_suppliers', (int) $id, 'Created supplier: ' . $name, ['name' => $name]);

        return $this->jsonSuccess('Supplier saved successfully.', ['insertid' => $id]);
    }

    public function updateSupplier()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        if ($id <= 0 || $name === '') {
            return $this->jsonError('Invalid supplier data.');
        }

        (new HospitalStockSupplierModel())->update($id, [
            'name' => $name,
            'contact_person' => trim((string) ($this->request->getPost('contact_person') ?? '')),
            'phone' => trim((string) ($this->request->getPost('phone') ?? '')),
            'email' => trim((string) ($this->request->getPost('email') ?? '')),
            'address' => $this->request->getPost('address'),
            'gst_no' => trim((string) ($this->request->getPost('gst_no') ?? '')),
            'status' => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
        ]);

        $this->auditLog('supplier', 'update', 'hsm_suppliers', $id, 'Updated supplier: ' . $name);

        return $this->jsonSuccess('Supplier updated successfully.');
    }

    public function deleteSupplier()
    {
        if ($deny = $this->denyUnless(self::PERM_MASTER_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->jsonError('Invalid supplier id.');
        }

        $poCount = $this->db->table('hsm_purchase_orders')->where('supplier_id', $id)->countAllResults();
        if ($poCount > 0) {
            return $this->jsonError('Cannot delete supplier linked with purchase orders.');
        }

        (new HospitalStockSupplierModel())->delete($id);
        $this->auditLog('supplier', 'delete', 'hsm_suppliers', $id, 'Deleted supplier id: ' . $id);

        return $this->jsonSuccess('Supplier deleted successfully.');
    }

    public function createIndent()
    {
        if ($deny = $this->denyUnless(self::PERM_INDENT_CREATE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $department = trim((string) $this->request->getPost('department_name'));
        $itemIds = $this->request->getPost('item_id');
        $qtys = $this->request->getPost('qty');

        if ($department === '') {
            return $this->jsonError('Department name is required.');
        }

        if (! is_array($itemIds) || ! is_array($qtys) || count($itemIds) === 0) {
            return $this->jsonError('At least one item is required for indent.');
        }

        $lines = [];
        foreach ($itemIds as $k => $itemIdRaw) {
            $itemId = (int) $itemIdRaw;
            $qty = (float) ($qtys[$k] ?? 0);
            if ($itemId > 0 && $qty > 0) {
                $lines[] = ['item_id' => $itemId, 'qty' => $qty];
            }
        }

        if ($lines === []) {
            return $this->jsonError('No valid indent rows found.');
        }

        $this->db->transStart();

        $indentCode = $this->nextCode('HIND');
        $userId = $this->currentUserId();

        $this->db->table('hsm_indents')->insert([
            'indent_code' => $indentCode,
            'department_name' => $department,
            'requested_by' => $userId,
            'remarks' => $this->request->getPost('remarks'),
            'status' => 'pending',
        ]);

        $indentId = (int) $this->db->insertID();

        foreach ($lines as $line) {
            $this->db->table('hsm_indent_items')->insert([
                'indent_id' => $indentId,
                'item_id' => $line['item_id'],
                'requested_qty' => $line['qty'],
                'approved_qty' => 0,
                'issued_qty' => 0,
            ]);
        }

        $this->auditLog('indent', 'create', 'hsm_indents', $indentId, 'Indent created: ' . $indentCode, ['department' => $department]);

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return $this->jsonError('Failed to create indent.');
        }

        return $this->jsonSuccess('Indent created successfully.', ['indent_id' => $indentId]);
    }

    public function approveIndent()
    {
        if ($deny = $this->denyUnless(self::PERM_INDENT_APPROVE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $indentId = (int) $this->request->getPost('indent_id');
        if ($indentId <= 0) {
            return $this->jsonError('Invalid indent ID.');
        }

        $indent = $this->db->table('hsm_indents')->where('id', $indentId)->get()->getRowArray();
        if (! $indent) {
            return $this->jsonError('Indent not found.');
        }

        if (! in_array((string) $indent['status'], ['pending'], true)) {
            return $this->jsonError('Only pending indents can be approved.');
        }

        $this->db->transStart();

        $items = $this->db->table('hsm_indent_items')->where('indent_id', $indentId)->get()->getResultArray();
        foreach ($items as $row) {
            $this->db->table('hsm_indent_items')
                ->where('id', (int) $row['id'])
                ->update(['approved_qty' => (float) ($row['requested_qty'] ?? 0)]);
        }

        $this->db->table('hsm_indents')->where('id', $indentId)->update([
            'status' => 'approved',
            'approved_by' => $this->currentUserId(),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        $this->auditLog('indent', 'approve', 'hsm_indents', $indentId, 'Indent approved: ' . ($indent['indent_code'] ?? ''));

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return $this->jsonError('Failed to approve indent.');
        }

        return $this->jsonSuccess('Indent approved successfully.');
    }

    public function issueIndent()
    {
        if ($deny = $this->denyUnless(self::PERM_ISSUE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $indentId = (int) $this->request->getPost('indent_id');
        if ($indentId <= 0) {
            return $this->jsonError('Invalid indent ID.');
        }

        $indent = $this->db->table('hsm_indents')->where('id', $indentId)->get()->getRowArray();
        if (! $indent) {
            return $this->jsonError('Indent not found.');
        }

        if (! in_array((string) $indent['status'], ['approved', 'partial_issued'], true)) {
            return $this->jsonError('Indent must be approved before issue.');
        }

        $this->db->transStart();

        $issueCode = $this->nextCode('HISS');
        $this->db->table('hsm_issues')->insert([
            'issue_code' => $issueCode,
            'indent_id' => $indentId,
            'department_name' => (string) ($indent['department_name'] ?? ''),
            'issued_by' => $this->currentUserId(),
            'remarks' => $this->request->getPost('remarks'),
        ]);
        $issueId = (int) $this->db->insertID();

        $rows = $this->db->table('hsm_indent_items')->where('indent_id', $indentId)->get()->getResultArray();
        $hasIssued = false;
        $allFulfilled = true;

        foreach ($rows as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $approvedQty = (float) ($row['approved_qty'] ?? 0);
            $issuedQty = (float) ($row['issued_qty'] ?? 0);
            $remaining = $approvedQty - $issuedQty;
            if ($itemId <= 0 || $remaining <= 0) {
                continue;
            }

            $item = $this->db->table('hsm_items')->where('id', $itemId)->get()->getRowArray();
            $stock = (float) ($item['current_stock'] ?? 0);
            $issueNow = min($stock, $remaining);

            if ($issueNow <= 0) {
                $allFulfilled = false;
                continue;
            }

            $updated = $this->db->table('hsm_items')
                ->set('current_stock', 'current_stock - ' . $issueNow, false)
                ->where('id', $itemId)
                ->where('current_stock >=', $issueNow)
                ->update();

            if (! $updated || $this->db->affectedRows() <= 0) {
                $allFulfilled = false;
                continue;
            }

            $this->db->table('hsm_indent_items')
                ->set('issued_qty', 'issued_qty + ' . $issueNow, false)
                ->where('id', (int) $row['id'])
                ->update();

            $this->db->table('hsm_issue_items')->insert([
                'issue_id' => $issueId,
                'item_id' => $itemId,
                'qty' => $issueNow,
            ]);

            $latest = $this->db->table('hsm_items')->select('current_stock')->where('id', $itemId)->get()->getRowArray();
            $balance = (float) ($latest['current_stock'] ?? 0);

            $this->appendStockLedger($itemId, 'issue', 'hsm_issues', $issueId, 0, $issueNow, 'Issued against indent: ' . ($indent['indent_code'] ?? ''));

            if ($balance < ($remaining - $issueNow)) {
                $allFulfilled = false;
            }

            $hasIssued = true;
        }

        if (! $hasIssued) {
            $this->db->transRollback();
            return $this->jsonError('No item could be issued (stock unavailable).');
        }

        $this->db->table('hsm_indents')->where('id', $indentId)->update([
            'status' => $allFulfilled ? 'issued' : 'partial_issued',
        ]);

        $this->auditLog('issue', 'create', 'hsm_issues', $issueId, 'Issue created: ' . $issueCode, ['indent_id' => $indentId]);

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return $this->jsonError('Failed to issue stock.');
        }

        return $this->jsonSuccess($allFulfilled ? 'Indent fully issued.' : 'Indent partially issued due to stock shortage.');
    }

    public function createPurchaseOrder()
    {
        if ($deny = $this->denyUnless(self::PERM_PURCHASE_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $supplierId = (int) $this->request->getPost('supplier_id');
        $itemIds = $this->request->getPost('item_id');
        $qtys = $this->request->getPost('qty');
        $rates = $this->request->getPost('unit_cost');

        if ($supplierId <= 0) {
            return $this->jsonError('Supplier is required.');
        }

        if (! is_array($itemIds) || ! is_array($qtys) || count($itemIds) === 0) {
            return $this->jsonError('At least one purchase item is required.');
        }

        $lines = [];
        foreach ($itemIds as $k => $itemIdRaw) {
            $itemId = (int) $itemIdRaw;
            $qty = (float) ($qtys[$k] ?? 0);
            $rate = (float) ($rates[$k] ?? 0);
            if ($itemId > 0 && $qty > 0) {
                $lines[] = ['item_id' => $itemId, 'qty' => $qty, 'unit_cost' => $rate];
            }
        }

        if ($lines === []) {
            return $this->jsonError('No valid purchase lines found.');
        }

        $this->db->transStart();

        $poCode = $this->nextCode('HPO');
        $this->db->table('hsm_purchase_orders')->insert([
            'po_code' => $poCode,
            'supplier_id' => $supplierId,
            'order_date' => $this->request->getPost('order_date') ?: date('Y-m-d'),
            'expected_date' => $this->request->getPost('expected_date') ?: null,
            'ordered_by' => $this->currentUserId(),
            'status' => 'ordered',
            'remarks' => $this->request->getPost('remarks'),
        ]);

        $poId = (int) $this->db->insertID();

        foreach ($lines as $line) {
            $this->db->table('hsm_purchase_order_items')->insert([
                'purchase_order_id' => $poId,
                'item_id' => $line['item_id'],
                'ordered_qty' => $line['qty'],
                'received_qty' => 0,
                'unit_cost' => $line['unit_cost'],
            ]);
        }

        $this->auditLog('purchase', 'create', 'hsm_purchase_orders', $poId, 'PO created: ' . $poCode);

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return $this->jsonError('Failed to create purchase order.');
        }

        return $this->jsonSuccess('Purchase order created successfully.');
    }

    public function receivePurchaseOrder()
    {
        if ($deny = $this->denyUnless(self::PERM_PURCHASE_MANAGE)) {
            return $deny;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $poId = (int) $this->request->getPost('purchase_order_id');
        if ($poId <= 0) {
            return $this->jsonError('Invalid purchase order ID.');
        }

        $po = $this->db->table('hsm_purchase_orders')->where('id', $poId)->get()->getRowArray();
        if (! $po) {
            return $this->jsonError('Purchase order not found.');
        }

        if (in_array((string) $po['status'], ['completed', 'cancelled'], true)) {
            return $this->jsonError('This purchase order cannot be received.');
        }

        $this->db->transStart();

        $rows = $this->db->table('hsm_purchase_order_items')->where('purchase_order_id', $poId)->get()->getResultArray();
        $allDone = true;

        foreach ($rows as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $ordered = (float) ($row['ordered_qty'] ?? 0);
            $received = (float) ($row['received_qty'] ?? 0);
            $remaining = $ordered - $received;
            if ($itemId <= 0 || $remaining <= 0) {
                continue;
            }

            $this->db->table('hsm_items')
                ->set('current_stock', 'current_stock + ' . $remaining, false)
                ->where('id', $itemId)
                ->update();

            $this->db->table('hsm_purchase_order_items')
                ->set('received_qty', 'received_qty + ' . $remaining, false)
                ->where('id', (int) $row['id'])
                ->update();

            if ((float) ($row['unit_cost'] ?? 0) > 0) {
                $this->db->table('hsm_items')->where('id', $itemId)->update([
                    'unit_cost' => (float) ($row['unit_cost'] ?? 0),
                ]);
            }

            $this->appendStockLedger($itemId, 'purchase', 'hsm_purchase_orders', $poId, $remaining, 0, 'PO receive: ' . ($po['po_code'] ?? ''));
        }

        $recheck = $this->db->table('hsm_purchase_order_items')->where('purchase_order_id', $poId)->get()->getResultArray();
        foreach ($recheck as $line) {
            if ((float) ($line['received_qty'] ?? 0) < (float) ($line['ordered_qty'] ?? 0)) {
                $allDone = false;
                break;
            }
        }

        $this->db->table('hsm_purchase_orders')->where('id', $poId)->update([
            'status' => $allDone ? 'completed' : 'partial_received',
        ]);

        $this->auditLog('purchase', 'receive', 'hsm_purchase_orders', $poId, 'PO stock received: ' . ($po['po_code'] ?? ''));

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return $this->jsonError('Failed to receive purchase order.');
        }

        return $this->jsonSuccess($allDone ? 'PO completed and stock updated.' : 'PO partially received.');
    }

    public function reportDepartmentConsumption(): string
    {
        if (! $this->userCan(self::PERM_REPORT_VIEW)) {
            return 'Access denied.';
        }

        $from = trim((string) $this->request->getGet('from'));
        $to = trim((string) $this->request->getGet('to'));
        if ($from === '') {
            $from = date('Y-m-01');
        }
        if ($to === '') {
            $to = date('Y-m-d');
        }

        $rows = $this->db->table('hsm_issue_items ii')
            ->select('i.department_name, m.item_code, m.name AS item_name, SUM(ii.qty) AS total_qty', false)
            ->join('hsm_issues i', 'i.id = ii.issue_id', 'inner')
            ->join('hsm_items m', 'm.id = ii.item_id', 'inner')
            ->where('DATE(i.created_at) >=', $from)
            ->where('DATE(i.created_at) <=', $to)
            ->groupBy('i.department_name, m.item_code, m.name')
            ->orderBy('i.department_name', 'ASC')
            ->orderBy('m.name', 'ASC')
            ->get()
            ->getResultArray();

        return view('Setting/Stock/report_print', [
            'title' => 'Department-wise Consumption Report',
            'subtitle' => 'Period: ' . $from . ' to ' . $to,
            'columns' => ['Department', 'Item Code', 'Item Name', 'Total Issued Qty'],
            'rows' => array_map(static fn (array $r): array => [
                (string) ($r['department_name'] ?? ''),
                (string) ($r['item_code'] ?? ''),
                (string) ($r['item_name'] ?? ''),
                (string) ($r['total_qty'] ?? '0'),
            ], $rows),
        ]);
    }

    public function reportMonthlyIssue(): string
    {
        if (! $this->userCan(self::PERM_REPORT_VIEW)) {
            return 'Access denied.';
        }

        $year = (int) ($this->request->getGet('year') ?: date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $rows = $this->db->table('hsm_issue_items ii')
            ->select('DATE_FORMAT(i.created_at, "%Y-%m") AS issue_month, SUM(ii.qty) AS total_qty, COUNT(DISTINCT i.id) AS issue_count', false)
            ->join('hsm_issues i', 'i.id = ii.issue_id', 'inner')
            ->where('YEAR(i.created_at)', $year)
            ->groupBy('DATE_FORMAT(i.created_at, "%Y-%m")', false)
            ->orderBy('issue_month', 'ASC')
            ->get()
            ->getResultArray();

        return view('Setting/Stock/report_print', [
            'title' => 'Monthly Issue Report',
            'subtitle' => 'Year: ' . $year,
            'columns' => ['Month', 'Issue Transactions', 'Total Issued Qty'],
            'rows' => array_map(static fn (array $r): array => [
                (string) ($r['issue_month'] ?? ''),
                (string) ($r['issue_count'] ?? '0'),
                (string) ($r['total_qty'] ?? '0'),
            ], $rows),
        ]);
    }

    public function reportNearExpiry(): string
    {
        if (! $this->userCan(self::PERM_REPORT_VIEW)) {
            return 'Access denied.';
        }

        $days = (int) ($this->request->getGet('days') ?: 60);
        if ($days <= 0 || $days > 3650) {
            $days = 60;
        }

        $today = date('Y-m-d');
        $toDate = date('Y-m-d', strtotime('+' . $days . ' days'));

        $rows = $this->db->table('hsm_items i')
            ->select('i.item_code, i.name, c.name AS category_name, i.current_stock, i.expiry_date, DATEDIFF(i.expiry_date, CURDATE()) AS days_left', false)
            ->join('hsm_categories c', 'c.id = i.category_id', 'left')
            ->where('i.current_stock >', 0)
            ->where('i.expiry_date IS NOT NULL', null, false)
            ->where('DATE(i.expiry_date) >=', $today)
            ->where('DATE(i.expiry_date) <=', $toDate)
            ->orderBy('i.expiry_date', 'ASC')
            ->get()
            ->getResultArray();

        return view('Setting/Stock/report_print', [
            'title' => 'Near Expiry Report',
            'subtitle' => 'Next ' . $days . ' day(s)',
            'columns' => ['Item Code', 'Item Name', 'Category', 'Current Stock', 'Expiry Date', 'Days Left'],
            'rows' => array_map(static fn (array $r): array => [
                (string) ($r['item_code'] ?? ''),
                (string) ($r['name'] ?? ''),
                (string) ($r['category_name'] ?? ''),
                (string) ($r['current_stock'] ?? '0'),
                (string) ($r['expiry_date'] ?? ''),
                (string) ($r['days_left'] ?? ''),
            ], $rows),
        ]);
    }

    private function appendStockLedger(int $itemId, string $txnType, string $refTable, int $refId, float $qtyIn, float $qtyOut, string $remarks = ''): void
    {
        $row = $this->db->table('hsm_items')->select('current_stock')->where('id', $itemId)->get()->getRowArray();
        $balance = (float) ($row['current_stock'] ?? 0);

        $this->db->table('hsm_stock_ledger')->insert([
            'item_id' => $itemId,
            'txn_type' => $txnType,
            'ref_table' => $refTable,
            'ref_id' => $refId,
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'balance_after' => $balance,
            'remarks' => $remarks,
            'created_by' => $this->currentUserId(),
        ]);
    }

    private function getStats(): array
    {
        $totalItems = $this->db->table('hsm_items')->where('status', 'active')->countAllResults();
        $totalCategories = $this->db->table('hsm_categories')->where('status', 'active')->countAllResults();
        $totalSuppliers = $this->db->table('hsm_suppliers')->where('status', 'active')->countAllResults();
        $pendingIndents = $this->db->table('hsm_indents')->whereIn('status', ['pending', 'approved', 'partial_issued'])->countAllResults();
        $openPO = $this->db->table('hsm_purchase_orders')->whereIn('status', ['ordered', 'partial_received'])->countAllResults();

        $stockValueRow = $this->db->table('hsm_items')
            ->select('SUM(current_stock * unit_cost) AS stock_value', false)
            ->get()
            ->getRowArray();
        $stockValue = (float) ($stockValueRow['stock_value'] ?? 0);

        $lowStock = $this->db->table('hsm_items')
            ->where('current_stock <= min_stock_level', null, false)
            ->countAllResults();

        return [
            'total_items' => $totalItems,
            'total_categories' => $totalCategories,
            'total_suppliers' => $totalSuppliers,
            'pending_indents' => $pendingIndents,
            'open_po' => $openPO,
            'low_stock' => $lowStock,
            'stock_value' => $stockValue,
        ];
    }

    private function nextCode(string $prefix): string
    {
        $date = date('Ymd');
        $like = $prefix . '-' . $date . '-';
        $tableMap = [
            'HIND' => ['table' => 'hsm_indents', 'column' => 'indent_code'],
            'HISS' => ['table' => 'hsm_issues', 'column' => 'issue_code'],
            'HPO'  => ['table' => 'hsm_purchase_orders', 'column' => 'po_code'],
        ];

        $map = $tableMap[$prefix] ?? null;
        $seq = 1;

        if ($map !== null && $this->db->tableExists($map['table'])) {
            $row = $this->db->table($map['table'])
                ->select($map['column'])
                ->like($map['column'], $like, 'after')
                ->orderBy($map['column'], 'DESC')
                ->get(1)
                ->getRowArray();

            $lastCode = (string) ($row[$map['column']] ?? '');
            if (preg_match('/-(\d{4})$/', $lastCode, $m) === 1) {
                $seq = ((int) $m[1]) + 1;
            }
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    }

    private function auditLog(string $module, string $action, string $entityTable, ?int $entityId, string $description, array $meta = []): void
    {
        $this->db->table('hsm_audit_log')->insert([
            'module' => $module,
            'action' => $action,
            'entity_table' => $entityTable,
            'entity_id' => $entityId,
            'description' => $description,
            'meta_json' => $meta ? json_encode($meta) : null,
            'created_by' => $this->currentUserId(),
        ]);
    }

    private function currentUserId(): ?int
    {
        if (function_exists('auth')) {
            $user = auth()->user();
            if ($user && isset($user->id)) {
                return (int) $user->id;
            }
        }

        return null;
    }

    private function denyUnless(string $permission)
    {
        if ($this->userCan($permission)) {
            return null;
        }

        return $this->response->setStatusCode(403)->setJSON([
            'update' => 0,
            'error_text' => 'Access denied.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->userCan($permission)) {
                return true;
            }
        }

        return false;
    }

    private function userCan(string $permission): bool
    {
        if (! function_exists('auth')) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'can') && $user->can($permission)) {
            return true;
        }

        if (method_exists($user, 'inGroup') && $user->inGroup('superadmin', 'admin', 'developer')) {
            return true;
        }

        return false;
    }

    private function jsonError(string $message)
    {
        return $this->response->setJSON([
            'update' => 0,
            'error_text' => $message,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function jsonSuccess(string $message, array $extra = [])
    {
        return $this->response->setJSON(array_merge([
            'update' => 1,
            'showcontent' => $message,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ], $extra));
    }

    private function isModuleReady(): bool
    {
        return $this->db->tableExists('hsm_items')
            && $this->db->tableExists('hsm_categories')
            && $this->db->tableExists('hsm_indents')
            && $this->db->tableExists('hsm_purchase_orders');
    }
}
