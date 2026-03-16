<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MedicalModel;
use Mpdf\Mpdf;

class Medical extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    private function ensurePharmacyAccess()
    {
        $user = service('auth')->user();

        $allowed = false;
        if ($user && method_exists($user, 'can')) {
            $allowed = $user->can('pharmacy.access')
                || $user->can('billing.access');
        }

        if (! $allowed && $user && method_exists($user, 'inGroup')) {
            $allowed = $user->inGroup('superadmin', 'admin', 'developer');
        }

        if ($allowed) {
            return null;
        }

        return $this->response
            ->setStatusCode(403)
            ->setBody('<div class="alert alert-danger m-3">Access denied for Pharmacy module.</div>');
    }

    private function canPharmacyPermission(string $permission): bool
    {
        $user = service('auth')->user();
        if ($user && method_exists($user, 'can') && $user->can($permission)) {
            return true;
        }

        if ($user && method_exists($user, 'inGroup') && $user->inGroup('superadmin', 'admin', 'developer')) {
            return true;
        }

        return false;
    }

    private function ensureMedicalAdminActionLogTable(): void
    {
        if ($this->db->tableExists('medical_admin_action_log')) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS medical_admin_action_log (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    action_type VARCHAR(50) NOT NULL,
                    action_summary VARCHAR(255) NOT NULL,
                    payload_json TEXT NULL,
                    created_by_id INT NULL,
                    created_by_name VARCHAR(160) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    INDEX idx_action_type (action_type),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->query($sql);
    }

    private function writeMedicalAdminActionLog(string $actionType, string $summary, array $payload = []): void
    {
        $this->ensureMedicalAdminActionLogTable();

        if (! $this->db->tableExists('medical_admin_action_log')) {
            return;
        }

        $user = service('auth')->user();
        $userId = null;
        $userName = null;

        if ($user) {
            $userId = isset($user->id) ? (int) $user->id : null;
            if (isset($user->username) && (string) $user->username !== '') {
                $userName = (string) $user->username;
            } elseif (isset($user->email) && (string) $user->email !== '') {
                $userName = (string) $user->email;
            } elseif (isset($user->name) && (string) $user->name !== '') {
                $userName = (string) $user->name;
            }
        }

        $this->db->table('medical_admin_action_log')->insert([
            'action_type' => $actionType,
            'action_summary' => $summary,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_by_id' => $userId,
            'created_by_name' => $userName,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function pharmacyActorLabel(): string
    {
        $user = service('auth')->user();
        if (! $user) {
            return 'System[' . date('Y-m-d H:i:s') . ']';
        }

        $name = trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
        if ($name === '') {
            $name = (string) ($user->username ?? ($user->email ?? 'User'));
        }

        $userId = (int) ($user->id ?? 0);
        return $name . '[' . $userId . '][' . date('Y-m-d H:i:s') . ']';
    }

    private function appendInvoiceMasterLog(int $invoiceId, string $message): void
    {
        if ($invoiceId <= 0 || trim($message) === '') {
            return;
        }

        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->fieldExists('log', 'invoice_med_master')) {
            return;
        }

        $invoice = $this->db->table('invoice_med_master')->select('id,log')->where('id', $invoiceId)->get()->getRowArray();
        if (! $invoice) {
            return;
        }

        $oldLog = trim((string) ($invoice['log'] ?? ''));
        $newLine = trim($message) . ' | By ' . $this->pharmacyActorLabel();
        $merged = $oldLog === '' ? $newLine : ($oldLog . PHP_EOL . $newLine);

        $this->db->table('invoice_med_master')->where('id', $invoiceId)->update([
            'log' => $merged,
        ]);
    }

    private function archiveDeletedInvoiceItem(array $itemRow): void
    {
        if (! $this->db->tableExists('inv_med_item_delete')) {
            return;
        }

        $fields = $this->db->getFieldNames('inv_med_item_delete') ?? [];
        if ($fields === []) {
            return;
        }

        $insert = [];
        foreach ($fields as $field) {
            if ($field === 'id') {
                continue;
            }
            if (array_key_exists($field, $itemRow)) {
                $insert[$field] = $itemRow[$field];
            }
        }

        if (in_array('delete_by', $fields, true)) {
            $insert['delete_by'] = $this->pharmacyActorLabel();
        }
        if (in_array('delete_time', $fields, true)) {
            $insert['delete_time'] = date('Y-m-d H:i:s');
        }

        if ($insert !== []) {
            $this->db->table('inv_med_item_delete')->insert($insert);
        }
    }

    public function admin_action_logs(string $actionType = '')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $this->ensureMedicalAdminActionLogTable();
        if (! $this->db->tableExists('medical_admin_action_log')) {
            return $this->response->setJSON([]);
        }

        $builder = $this->db->table('medical_admin_action_log')
            ->select('id, action_type, action_summary, payload_json, created_by_name, created_at')
            ->orderBy('id', 'DESC')
            ->limit(20);

        if ($actionType !== '') {
            $builder->where('action_type', $actionType);
        }

        $rows = $builder->get()->getResultArray();
        return $this->response->setJSON($rows);
    }

    public function index()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/dashboard');
    }

    public function search_customer()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/search_customer');
    }

    public function counter_sale_form()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $docList = [];
        if ($this->db->tableExists('doctor_master')) {
            $docBuilder = $this->db->table('doctor_master');
            $docFields = $this->db->getFieldNames('doctor_master') ?? [];
            if (in_array('active', $docFields, true)) {
                $docBuilder->where('active', 1);
            }
            $docList = $docBuilder->orderBy('p_fname', 'ASC')->get()->getResult();
        }

        return view('medical/counter_sale_form', [
            'docList' => $docList,
        ]);
    }

    public function counter_sale_create()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return $this->response->setJSON([
                'status' => 0,
                'message' => 'invoice_med_master table not found',
            ]);
        }

        $customerName = trim((string) ($this->request->getPost('customer_name') ?? ''));
        $phone = trim((string) ($this->request->getPost('customer_phone') ?? ''));
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        $doctorKey = trim((string) ($this->request->getPost('doctor_id') ?? ''));
        $doctorOther = trim((string) ($this->request->getPost('doctor_other') ?? ''));

        if ($customerName === '') {
            return $this->response->setJSON([
                'status' => 0,
                'message' => 'Customer name is required.',
            ]);
        }

        if ($phone === '' || strlen($phone) !== 10) {
            return $this->response->setJSON([
                'status' => 0,
                'message' => 'Phone number must be exactly 10 digits.',
            ]);
        }

        $docId = 0;
        $docName = '';

        if ($doctorKey === 'other') {
            if ($doctorOther === '') {
                return $this->response->setJSON([
                    'status' => 0,
                    'message' => 'Doctor name is required when Other is selected.',
                ]);
            }
            $docName = $doctorOther;
        } elseif (ctype_digit($doctorKey) && (int) $doctorKey > 0) {
            $docId = (int) $doctorKey;
            if ($this->db->tableExists('doctor_master')) {
                $doc = $this->db->table('doctor_master')->where('id', $docId)->get()->getRow();
                if (! $doc) {
                    return $this->response->setJSON([
                        'status' => 0,
                        'message' => 'Selected doctor not found.',
                    ]);
                }
                $docName = trim((string) ($doc->p_fname ?? ''));
            }
        } else {
            return $this->response->setJSON([
                'status' => 0,
                'message' => 'Please select doctor name or choose Other.',
            ]);
        }

        $invoiceFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $insert = [];

        $setIfExists = static function (array &$target, array $fields, string $key, $value): void {
            if (in_array($key, $fields, true)) {
                $target[$key] = $value;
            }
        };

        $setIfExists($insert, $invoiceFields, 'patient_id', 0);
        $setIfExists($insert, $invoiceFields, 'ipd_id', 0);
        $setIfExists($insert, $invoiceFields, 'ipd_code', '');
        $setIfExists($insert, $invoiceFields, 'case_id', 0);
        $setIfExists($insert, $invoiceFields, 'inv_date', date('Y-m-d'));
        $setIfExists($insert, $invoiceFields, 'inv_name', $customerName);
        $setIfExists($insert, $invoiceFields, 'patient_code', '');
        $setIfExists($insert, $invoiceFields, 'inv_phone_number', $phone);
        $setIfExists($insert, $invoiceFields, 'doc_id', $docId);
        $setIfExists($insert, $invoiceFields, 'doc_name', $docName);
        $setIfExists($insert, $invoiceFields, 'sale_return', 0);
        $setIfExists($insert, $invoiceFields, 'case_credit', 0);
        $setIfExists($insert, $invoiceFields, 'customer_type', 0);
        $setIfExists($insert, $invoiceFields, 'group_invoice_id', 0);
        $setIfExists($insert, $invoiceFields, 'ipd_credit', 0);

        $this->db->table('invoice_med_master')->insert($insert);
        $invoiceId = (int) $this->db->insertID();

        if ($invoiceId <= 0) {
            return $this->response->setJSON([
                'status' => 0,
                'message' => 'Unable to create invoice',
            ]);
        }

        if (in_array('inv_med_code', $invoiceFields, true)) {
            $pid = str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT);
            $invoiceCode = 'M' . date('ym') . $pid;
            $this->db->table('invoice_med_master')
                ->where('id', $invoiceId)
                ->update(['inv_med_code' => $invoiceCode]);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Invoice created',
            'redirect_url' => base_url('Medical/invoice_edit/' . $invoiceId . '?from=counter'),
        ]);
    }

    public function search()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $searchRaw = (string) $this->request->getPost('txtsearch');
        if ($searchRaw === '') {
            $searchRaw = (string) $this->request->getGet('txtsearch');
        }
        $search = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', trim($searchRaw));

        $table = $this->db->tableExists('patient_master_exten') ? 'patient_master_exten' : 'patient_master';
        if (! $this->db->tableExists($table)) {
            return view('medical/patient_search_result', [
                'patients' => [],
                'codeField' => 'id',
                'nameField' => null,
                'phoneField' => null,
                'ageField' => null,
                'lastVisitField' => null,
            ]);
        }

        $fields = $this->db->getFieldNames($table) ?? [];
        $codeField = in_array('p_code', $fields, true) ? 'p_code' : (in_array('id', $fields, true) ? 'id' : null);
        $nameField = in_array('p_fname', $fields, true) ? 'p_fname' : null;
        $phoneField = in_array('mphone1', $fields, true) ? 'mphone1' : null;
        $ageField = in_array('str_age', $fields, true) ? 'str_age' : (in_array('age', $fields, true) ? 'age' : null);

        $select = ['id'];
        foreach ([$codeField, $nameField, $phoneField, $ageField] as $field) {
            if ($field && ! in_array($field, $select, true)) {
                $select[] = $field;
            }
        }

        $builder = $this->db->table($table)->select(implode(',', $select));

        if ($search !== '') {
            $tokens = preg_split('/\s+/', $search) ?: [];
            $searchableFields = [];
            foreach (['p_code', 'p_fname', 'mphone1', 'udai', 'email1'] as $field) {
                if (in_array($field, $fields, true)) {
                    $searchableFields[] = $field;
                }
            }

            foreach ($tokens as $token) {
                $token = trim($token);
                if ($token === '') {
                    continue;
                }
                if (empty($searchableFields)) {
                    continue;
                }

                $builder->groupStart();
                foreach ($searchableFields as $index => $field) {
                    if ($index === 0) {
                        $builder->like($field, $token);
                    } else {
                        $builder->orLike($field, $token);
                    }
                }
                $builder->groupEnd();
            }

            $builder->orderBy('id', 'DESC')->limit(200);
        } else {
            $builder->orderBy('id', 'DESC')->limit(100);
        }

        $patients = $builder->get()->getResult();

        return view('medical/patient_search_result', [
            'patients' => $patients,
            'codeField' => $codeField,
            'nameField' => $nameField,
            'phoneField' => $phoneField,
            'ageField' => $ageField,
            'lastVisitField' => null,
        ]);
    }

    public function get_drug()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $term = strtolower(trim((string) $this->request->getGet('term')));
        if ($term === '') {
            return $this->response->setJSON([]);
        }

        if (! $this->db->tableExists('purchase_invoice_item')) {
            return $this->response->setJSON([]);
        }

        $hasProductMaster = $this->db->tableExists('med_product_master');
        $tFields = $this->db->getFieldNames('purchase_invoice_item') ?? [];
        $stockQtySql = '(IFNULL(t.total_unit,0)-IFNULL(t.total_sale_unit,0)-IFNULL(t.total_return_unit,0)-IFNULL(t.total_lost_unit,0))';

        if ($hasProductMaster) {
            $pFields = $this->db->getFieldNames('med_product_master') ?? [];
            $hasGeneric = in_array('genericname', $pFields, true);
            $whereSearch = '(p.item_name LIKE ?' . ($hasGeneric ? ' OR p.genericname LIKE ?' : '') . ')';
            $params = ['%' . $term . '%'];
            if ($hasGeneric) {
                $params[] = '%' . $term . '%';
            }

            $sql = "SELECT t.id, p.id AS item_code, p.item_name AS item_name,
                        " . (in_array('formulation', $pFields, true) ? 'p.formulation' : "''") . " AS formulation,
                        " . (in_array('batch_no', $tFields, true) ? 't.batch_no' : "''") . " AS batch_no,
                        " . (in_array('expiry_date', $tFields, true) ? 't.expiry_date' : 'NULL') . " AS expiry_date,
                        " . (in_array('expiry_date', $tFields, true) ? "DATE_FORMAT(t.expiry_date, '%Y-%m-%d')" : "''") . " AS expiry_date_str,
                        " . (in_array('mrp', $tFields, true) ? 't.mrp' : '0') . " AS mrp,
                        " . (in_array('selling_unit_rate', $tFields, true) ? 't.selling_unit_rate' : '0') . " AS selling_unit_rate,
                        " . (in_array('packing', $tFields, true) ? 't.packing' : '1') . " AS packing,
                        " . $stockQtySql . " AS c_qty,
                        " . (in_array('expiry_date', $tFields, true) ? 'DATEDIFF(LAST_DAY(t.expiry_date), CURDATE())' : '9999') . " AS expiry_month_pending,
                        " . (in_array('stock_date', $tFields, true) ? "IF(t.stock_date>'2025-09-21',1,0)" : '0') . " AS new_stock
                    FROM med_product_master p
                    JOIN purchase_invoice_item t ON p.id=t.item_code
                    WHERE " . $whereSearch . "
                        AND " . $stockQtySql . " > 0
                    ORDER BY item_name, t.id DESC
                    LIMIT 100";

            $rows = $this->db->query($sql, $params)->getResultArray();
        } else {
            $itemNameCol = in_array('item_name', $tFields, true) ? 't.item_name' : (in_array('Item_name', $tFields, true) ? 't.Item_name' : "''");
            $itemCodeCol = in_array('item_code', $tFields, true) ? 't.item_code' : '0';

            $sql = "SELECT t.id, " . $itemCodeCol . " AS item_code, " . $itemNameCol . " AS item_name,
                        " . (in_array('formulation', $tFields, true) ? 't.formulation' : "''") . " AS formulation,
                        " . (in_array('batch_no', $tFields, true) ? 't.batch_no' : "''") . " AS batch_no,
                        " . (in_array('expiry_date', $tFields, true) ? 't.expiry_date' : 'NULL') . " AS expiry_date,
                        " . (in_array('expiry_date', $tFields, true) ? "DATE_FORMAT(t.expiry_date, '%Y-%m-%d')" : "''") . " AS expiry_date_str,
                        " . (in_array('mrp', $tFields, true) ? 't.mrp' : '0') . " AS mrp,
                        " . (in_array('selling_unit_rate', $tFields, true) ? 't.selling_unit_rate' : '0') . " AS selling_unit_rate,
                        " . (in_array('packing', $tFields, true) ? 't.packing' : '1') . " AS packing,
                        " . $stockQtySql . " AS c_qty,
                        " . (in_array('expiry_date', $tFields, true) ? 'DATEDIFF(LAST_DAY(t.expiry_date), CURDATE())' : '9999') . " AS expiry_month_pending,
                        " . (in_array('stock_date', $tFields, true) ? "IF(t.stock_date>'2025-09-21',1,0)" : '0') . " AS new_stock
                    FROM purchase_invoice_item t
                    WHERE (" . $itemNameCol . " LIKE ?)
                        AND " . $stockQtySql . " > 0
                    ORDER BY item_name, t.id DESC
                    LIMIT 100";

            $rows = $this->db->query($sql, ['%' . $term . '%'])->getResultArray();
        }
        $result = [];

        foreach ($rows as $row) {
            $pendingDays = (float) ($row['expiry_month_pending'] ?? 9999);
            $noExpMonth = round($pendingDays / 30, 2);

            $msgDesc = '';
            if ($noExpMonth <= 4 && $noExpMonth >= 2) {
                $msgDesc = ' <span style="color: blue;">Expire Within : ' . $noExpMonth . ' Month </span>';
            } elseif ($noExpMonth == 1.0) {
                $msgDesc = ' <span style="color: Orange;">Expired : ' . $noExpMonth . ' Month </span>';
            } elseif ($noExpMonth < 1) {
                $msgDesc = ' <span style="color: red;">Expired : ' . $noExpMonth . ' Month </span>';
            }

            $result[] = [
                'label' => trim((string) ($row['item_name'] ?? '') . ' ' . (string) ($row['formulation'] ?? ''))
                    . ' |B:' . (string) ($row['batch_no'] ?? '')
                    . ' |Pak:' . (string) ($row['packing'] ?? 1)
                    . ' |Rs.' . (string) ($row['mrp'] ?? 0)
                    . ' |Qty:' . (string) ($row['c_qty'] ?? 0),
                'value' => (string) ($row['item_name'] ?? ''),
                'expiry_alert' => (string) $noExpMonth,
                'desc' => $msgDesc,
                'l_item_code' => (string) ($row['item_code'] ?? ''),
                'l_ss_no' => (string) ($row['id'] ?? ''),
                'l_Batch' => (string) ($row['batch_no'] ?? ''),
                'l_Expiry' => (string) ($row['expiry_date_str'] ?? ''),
                'l_mrp' => (string) ($row['mrp'] ?? ''),
                'l_unit_rate' => (string) ($row['selling_unit_rate'] ?? ''),
                'l_c_qty' => (string) ($row['c_qty'] ?? ''),
                'l_packing' => (string) ($row['packing'] ?? ''),
                'l_new_stock' => (string) ($row['new_stock'] ?? 0),
                'item_code' => (string) ($row['item_code'] ?? ''),
            ];
        }

        return $this->response->setJSON($result);
    }

    public function get_batch($itemId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $itemId = (int) $itemId;
        $term = trim((string) $this->request->getGet('term'));

        if ($itemId <= 0 || ! $this->db->tableExists('purchase_invoice_item')) {
            return $this->response->setJSON([]);
        }

        $itemFields = $this->db->getFieldNames('purchase_invoice_item') ?? [];
        if (! in_array('batch_no', $itemFields, true) || ! in_array('item_code', $itemFields, true)) {
            return $this->response->setJSON([]);
        }

        $builder = $this->db->table('purchase_invoice_item')
            ->select('batch_no')
            ->where('item_code', $itemId)
            ->where('batch_no !=', '');

        if ($term !== '') {
            $builder->like('batch_no', $term, 'after');
        }

        $rows = $builder
            ->groupBy('batch_no')
            ->orderBy('batch_no', 'ASC')
            ->limit(100)
            ->get()
            ->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $batch = (string) ($row['batch_no'] ?? '');
            if ($batch === '') {
                continue;
            }
            $result[] = [
                'label' => $batch,
                'value' => $batch,
            ];
        }

        return $this->response->setJSON($result);
    }

    public function get_drug_master()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $term = strtolower(trim((string) $this->request->getGet('term')));
        if ($term === '' || ! $this->db->tableExists('med_product_master')) {
            return $this->response->setJSON([]);
        }

        $hasPurchaseItem = $this->db->tableExists('purchase_invoice_item');
        $pFields = $this->db->getFieldNames('med_product_master') ?? [];

        $isContinueWhere = in_array('is_continue', $pFields, true) ? ' AND m.is_continue=1 ' : '';

        if ($hasPurchaseItem) {
            $sql = "SELECT m.*,
                        p.id AS ss_no,
                        p.packing AS p_packing,
                        p.mrp,
                        p.purchase_price,
                        p.discount,
                        p.batch_no,
                        DATE_FORMAT(p.expiry_date,'%m') AS str_expiry_month,
                        DATE_FORMAT(p.expiry_date,'%y') AS str_expiry_year,
                        IF(p.id IS NULL,m.CGST_per,p.CGST_per) AS p_CGST_per,
                        IF(p.id IS NULL,m.SGST_per,p.SGST_per) AS p_SGST_per,
                        IF(p.id IS NULL,m.rack_no,p.rack_no) AS p_rack_no,
                        IF(p.id IS NULL,m.shelf_no,p.shelf_no) AS p_shelf_no,
                        IF(p.id IS NULL,m.cold_storage,p.cold_storage) AS p_cold_storage
                    FROM med_product_master m
                    LEFT JOIN (
                        SELECT pi.*
                        FROM purchase_invoice_item pi
                        JOIN (
                            SELECT item_code, MAX(id) AS max_id
                            FROM purchase_invoice_item
                            GROUP BY item_code
                        ) x ON x.max_id=pi.id
                    ) p ON m.id=p.item_code
                    WHERE m.item_name LIKE ? {$isContinueWhere}
                    ORDER BY m.item_name
                    LIMIT 20";

            $rows = $this->db->query($sql, [$term . '%'])->getResultArray();
        } else {
            $sql = "SELECT m.*,
                        NULL AS ss_no,
                        m.packing AS p_packing,
                        0 AS mrp,
                        0 AS purchase_price,
                        0 AS discount,
                        '' AS batch_no,
                        '' AS str_expiry_month,
                        '' AS str_expiry_year,
                        m.CGST_per AS p_CGST_per,
                        m.SGST_per AS p_SGST_per,
                        m.rack_no AS p_rack_no,
                        m.shelf_no AS p_shelf_no,
                        m.cold_storage AS p_cold_storage
                    FROM med_product_master m
                    WHERE m.item_name LIKE ? {$isContinueWhere}
                    ORDER BY m.item_name
                    LIMIT 20";

            $rows = $this->db->query($sql, [$term . '%'])->getResultArray();
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'label' => (string) ($row['item_name'] ?? '') . ' | ' . (string) ($row['p_packing'] ?? '') . ' | ' . (string) ($row['mrp'] ?? ''),
                'value' => (string) ($row['item_name'] ?? ''),
                'l_item_code' => (string) ($row['id'] ?? ''),
                'l_mrp' => (string) ($row['mrp'] ?? ''),
                'l_packing' => (string) ($row['packing'] ?? ''),
                'l_CGST_per' => (string) ($row['p_CGST_per'] ?? 0),
                'l_SGST_per' => (string) ($row['p_SGST_per'] ?? 0),
                'l_HSNCODE' => (string) ($row['HSNCODE'] ?? ''),
                'l_batch_no' => (string) ($row['batch_no'] ?? ''),
                'l_purchase_price' => (string) ($row['purchase_price'] ?? ''),
                'l_package' => (string) ($row['p_packing'] ?? ''),
                'l_disc_price' => (string) ($row['discount'] ?? ''),
                'l_rack_no' => (string) ($row['p_rack_no'] ?? ''),
                'l_shelf_no' => (string) ($row['p_shelf_no'] ?? ''),
                'l_cold_storage' => (string) ($row['p_cold_storage'] ?? ''),
                'batch_applicable' => (string) ($row['batch_applicable'] ?? 0),
                'exp_date_applicable' => (string) ($row['exp_date_applicable'] ?? 0),
                'datepicker_doe_month' => (string) ($row['str_expiry_month'] ?? ''),
                'datepicker_doe_year' => (string) ($row['str_expiry_year'] ?? ''),
            ];
        }

        return $this->response->setJSON($result);
    }

    public function check_invoice($pno = 0, $ipdId = 0, $caseId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $pno = (int) $pno;
        $ipdId = (int) $ipdId;
        $caseId = (int) $caseId;

        if (! $this->db->tableExists('invoice_med_master')) {
            return view('medical/placeholder', ['title' => 'invoice_med_master table not found']);
        }

        if ($pno <= 0) {
            return redirect()->to(base_url('Medical/Invoice_counter_new/0/' . $ipdId . '/' . $caseId));
        }

        $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];

        $builder = $this->db->table('invoice_med_master m')
            ->where('m.patient_id', $pno);

        if (in_array('inv_date', $masterFields, true)) {
            $builder->where('DATE(m.inv_date)=CURDATE()', null, false);
        }
        if (in_array('sale_return', $masterFields, true)) {
            $builder->where('m.sale_return', 0);
        }

        $todayInvoices = $builder
            ->orderBy('m.id', 'DESC')
            ->get()
            ->getResult();

        if (empty($todayInvoices)) {
            return redirect()->to(base_url('Medical/Invoice_counter_new/' . $pno . '/' . $ipdId . '/' . $caseId));
        }

        $invoiceIds = array_map(static fn($r) => (int) ($r->id ?? 0), $todayInvoices);
        $itemsByInvoice = [];

        if (! empty($invoiceIds) && $this->db->tableExists('inv_med_item')) {
            $items = $this->db->table('inv_med_item')
                ->whereIn('inv_med_id', $invoiceIds)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResult();

            foreach ($items as $item) {
                $invId = (int) ($item->inv_med_id ?? 0);
                if (! isset($itemsByInvoice[$invId])) {
                    $itemsByInvoice[$invId] = [];
                }
                $itemsByInvoice[$invId][] = $item;
            }
        }

        return view('medical/mini_invoice', [
            'invoices' => $todayInvoices,
            'itemsByInvoice' => $itemsByInvoice,
            'invoiceEditMeta' => array_reduce($todayInvoices, function (array $carry, $inv): array {
                $invoiceId = (int) ($inv->id ?? 0);
                if ($invoiceId <= 0) {
                    return $carry;
                }

                $reason = null;
                $canEdit = $this->canEditInvoiceRecord((array) $inv, $reason);
                $carry[$invoiceId] = [
                    'can_edit' => $canEdit,
                    'reason' => (string) ($reason ?? ''),
                ];

                return $carry;
            }, []),
            'pno' => $pno,
            'ipd_id' => $ipdId,
            'case_id' => $caseId,
        ]);
    }

    public function Invoice_counter_new($pno = 0, $ipdId = 0, $caseId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return view('medical/placeholder', ['title' => 'invoice_med_master table not found']);
        }

        $pno = (int) $pno;
        $ipdId = (int) $ipdId;
        $caseId = (int) $caseId;

        $invoiceFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $insert = [];

        $setIfExists = static function (array &$target, array $fields, string $key, $value): void {
            if (in_array($key, $fields, true)) {
                $target[$key] = $value;
            }
        };

        $patientName = 'Walk-in Customer';
        $patientCode = '';
        $phone = '';
        $docId = 0;

        if ($pno > 0) {
            $patientTable = $this->db->tableExists('patient_master_exten') ? 'patient_master_exten' : 'patient_master';
            if ($this->db->tableExists($patientTable)) {
                $patient = $this->db->table($patientTable)->where('id', $pno)->get()->getRow();
                if ($patient) {
                    $patientName = (string) ($patient->p_fname ?? $patientName);
                    $patientCode = (string) ($patient->p_code ?? '');
                    $phone = (string) ($patient->mphone1 ?? '');
                }
            }

            if ($this->db->tableExists('opd_master')) {
                $opd = $this->db->table('opd_master')->select('doc_id')->where('p_id', $pno)->orderBy('opd_id', 'DESC')->get()->getRow();
                $docId = (int) ($opd->doc_id ?? 0);
            }
        }

        $ipdCode = '';
        if ($ipdId > 0 && $this->db->tableExists('ipd_master')) {
            $ipd = $this->db->table('ipd_master')->select('ipd_code')->where('id', $ipdId)->get()->getRow();
            $ipdCode = (string) ($ipd->ipd_code ?? '');
        }

        $setIfExists($insert, $invoiceFields, 'patient_id', $pno);
        $setIfExists($insert, $invoiceFields, 'ipd_id', $ipdId);
        $setIfExists($insert, $invoiceFields, 'ipd_code', $ipdCode);
        $setIfExists($insert, $invoiceFields, 'case_id', $caseId);
        $setIfExists($insert, $invoiceFields, 'inv_date', date('Y-m-d'));
        $setIfExists($insert, $invoiceFields, 'inv_name', $patientName);
        $setIfExists($insert, $invoiceFields, 'patient_code', $patientCode);
        $setIfExists($insert, $invoiceFields, 'inv_phone_number', $phone);
        $setIfExists($insert, $invoiceFields, 'doc_id', $docId);
        $setIfExists($insert, $invoiceFields, 'sale_return', 0);
        $setIfExists($insert, $invoiceFields, 'case_credit', $caseId > 0 ? 1 : 0);
        $setIfExists($insert, $invoiceFields, 'customer_type', $pno > 0 ? 1 : 0);
        $setIfExists($insert, $invoiceFields, 'group_invoice_id', $ipdId > 0 ? 1 : 0);
        $setIfExists($insert, $invoiceFields, 'ipd_credit', $ipdId > 0 ? 1 : 0);

        $this->db->table('invoice_med_master')->insert($insert);
        $invoiceId = (int) $this->db->insertID();

        if ($invoiceId <= 0) {
            return view('medical/placeholder', ['title' => 'Unable to create invoice']);
        }

        if (in_array('inv_med_code', $invoiceFields, true)) {
            $pid = str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT);
            $invoiceCode = 'M' . date('ym') . $pid;
            $this->db->table('invoice_med_master')
                ->where('id', $invoiceId)
                ->update(['inv_med_code' => $invoiceCode]);
        }

        return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?from=counter'));
    }

    public function invoice_new($patientId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return view('medical/placeholder', ['title' => 'invoice_med_master table not found']);
        }

        $patientId = (int) $patientId;
        if ($patientId <= 0) {
            return view('medical/placeholder', ['title' => 'Invalid patient']);
        }

        $patientTable = $this->db->tableExists('patient_master_exten') ? 'patient_master_exten' : 'patient_master';
        $patient = $this->db->table($patientTable)->where('id', $patientId)->get()->getRow();
        if (! $patient) {
            return view('medical/placeholder', ['title' => 'Patient not found']);
        }

        $invoiceFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $insert = [];

        $setIfExists = static function (array &$target, array $fields, string $key, $value): void {
            if (in_array($key, $fields, true)) {
                $target[$key] = $value;
            }
        };

        $setIfExists($insert, $invoiceFields, 'patient_id', $patientId);
        $setIfExists($insert, $invoiceFields, 'inv_date', date('Y-m-d'));
        $setIfExists($insert, $invoiceFields, 'inv_name', (string) ($patient->p_fname ?? ''));
        $setIfExists($insert, $invoiceFields, 'patient_code', (string) ($patient->p_code ?? ''));
        $setIfExists($insert, $invoiceFields, 'inv_phone_number', (string) ($patient->mphone1 ?? ''));
        $setIfExists($insert, $invoiceFields, 'sale_return', 0);
        $setIfExists($insert, $invoiceFields, 'case_credit', 0);
        $setIfExists($insert, $invoiceFields, 'ipd_credit', 0);
        $setIfExists($insert, $invoiceFields, 'customer_type', 1);
        $setIfExists($insert, $invoiceFields, 'group_invoice_id', 0);
        $setIfExists($insert, $invoiceFields, 'ipd_id', 0);
        $setIfExists($insert, $invoiceFields, 'case_id', 0);

        if (in_array('doc_id', $invoiceFields, true) && $this->db->tableExists('opd_master')) {
            $opd = $this->db->table('opd_master')->select('doc_id')->where('p_id', $patientId)->orderBy('opd_id', 'DESC')->get()->getRow();
            $insert['doc_id'] = (int) ($opd->doc_id ?? 0);
        }

        $this->db->table('invoice_med_master')->insert($insert);
        $invoiceId = (int) $this->db->insertID();

        if ($invoiceId <= 0) {
            return view('medical/placeholder', ['title' => 'Unable to create draft invoice']);
        }

        if (in_array('inv_med_code', $invoiceFields, true)) {
            $pid = str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT);
            $invoiceCode = 'M' . date('ym') . $pid;
            $this->db->table('invoice_med_master')
                ->where('id', $invoiceId)
                ->update(['inv_med_code' => $invoiceCode]);
        }

        return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId));
    }

    public function invoice_edit($invoiceId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $invoiceId;
        if ($invoiceId > 0 && $this->db->tableExists('invoice_med_master')) {
            $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
            if ($invoice && $this->isInvoiceFinalized($invoice)) {
                $reason = null;
                if (! $this->canEditInvoiceRecord((array) $invoice, $reason)) {
                    return redirect()->to(base_url('Medical/final_invoice/' . $invoiceId . '?msg=' . urlencode((string) $reason)));
                }

                $this->reopenInvoiceForEdit($invoiceId);
            }
        }

        $query = trim((string) $this->request->getGet('q'));

        $data = $this->buildInvoiceEditData($invoiceId, $query);
        if (! $data) {
            return view('medical/placeholder', ['title' => 'Invoice not found']);
        }

        return view('medical/invoice_edit', $data);
    }

    public function edit_invoice_edit($invoiceId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return view('medical/placeholder', ['title' => 'Invalid invoice']);
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return view('medical/placeholder', ['title' => 'Invoice not found']);
        }

        $reason = null;
        if (! $this->canEditInvoiceRecord((array) $invoice, $reason)) {
            return redirect()->to(base_url('Medical/final_invoice/' . $invoiceId . '?msg=' . urlencode((string) $reason)));
        }

        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $message = '';

        if (strtolower((string) $this->request->getMethod()) === 'post') {
            $update = [];
            $setIfExists = static function (array &$target, array $fieldList, string $key, $value): void {
                if (in_array($key, $fieldList, true)) {
                    $target[$key] = $value;
                }
            };

            $inputDate = trim((string) $this->request->getPost('inv_date'));
            if ($inputDate !== '') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputDate)) {
                    $setIfExists($update, $fields, 'inv_date', $inputDate);
                } else {
                    $ts = strtotime(str_replace('/', '-', $inputDate));
                    if ($ts) {
                        $setIfExists($update, $fields, 'inv_date', date('Y-m-d', $ts));
                    }
                }
            }

            $setIfExists($update, $fields, 'inv_name', trim((string) $this->request->getPost('inv_name')));
            $setIfExists($update, $fields, 'inv_phone_number', trim((string) $this->request->getPost('inv_phone_number')));
            $setIfExists($update, $fields, 'patient_code', trim((string) $this->request->getPost('patient_code')));

            $docId = (int) $this->request->getPost('doc_id');
            $docName = trim((string) $this->request->getPost('doc_name'));

            if ($docId > 0 && $this->db->tableExists('doctor_master')) {
                $doc = $this->db->table('doctor_master')->where('id', $docId)->get()->getRow();
                if ($doc) {
                    $docName = (string) ($doc->p_fname ?? $docName);
                }
            }

            $setIfExists($update, $fields, 'doc_id', $docId);
            $setIfExists($update, $fields, 'doc_name', $docName);

            $setIfExists($update, $fields, 'ipd_credit', (int) $this->request->getPost('ipd_credit'));
            $setIfExists($update, $fields, 'case_credit', (int) $this->request->getPost('case_credit'));

            if (! empty($update)) {
                $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($update);
                $message = 'Invoice header updated.';
                $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
            }
        }

        $docList = [];
        if ($this->db->tableExists('doctor_master')) {
            $docBuilder = $this->db->table('doctor_master');
            $docFields = $this->db->getFieldNames('doctor_master') ?? [];
            if (in_array('active', $docFields, true)) {
                $docBuilder->where('active', 1);
            }
            $docList = $docBuilder->orderBy('p_fname', 'ASC')->get()->getResult();
        }

        return view('medical/invoice_master_edit', [
            'invoice' => $invoice,
            'docList' => $docList,
            'message' => $message,
        ]);
    }

    private function invoiceUpdateResponse(bool $status, string $remark, array $extra = [])
    {
        return $this->response->setJSON(array_merge([
            'status' => $status ? 1 : 0,
            'remark' => $remark,
            'csrfToken' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ], $extra));
    }

    private function updateInvoiceMasterByFields(int $invoiceId, array $candidateData): bool
    {
        if ($invoiceId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return false;
        }

        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];
        if (empty($fields)) {
            return false;
        }

        $update = [];
        foreach ($candidateData as $key => $value) {
            if (in_array($key, $fields, true)) {
                $update[$key] = $value;
            }
        }

        if (empty($update)) {
            return false;
        }

        $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($update);

        return true;
    }

    private function findPatientByIdOrCode($patientId, string $patientCode = ''): ?object
    {
        $patientTable = $this->db->tableExists('patient_master_exten') ? 'patient_master_exten' : 'patient_master';
        if (! $this->db->tableExists($patientTable)) {
            return null;
        }

        $fields = $this->db->getFieldNames($patientTable) ?? [];
        $builder = $this->db->table($patientTable);

        $pid = (int) $patientId;
        if ($pid > 0) {
            $builder->where('id', $pid);
        } elseif ($patientCode !== '') {
            if (in_array('p_code', $fields, true)) {
                $builder->where('p_code', $patientCode);
            } else {
                return null;
            }
        } else {
            return null;
        }

        return $builder->get()->getRow();
    }

    private function buildPatientInfoText(?object $person): string
    {
        if (! $person) {
            return '';
        }

        $code = (string) ($person->p_code ?? $person->id ?? '');
        $name = trim((string) ($person->p_fname ?? ''));
        $relative = trim((string) ($person->p_relative ?? ''));
        $rname = trim((string) ($person->p_rname ?? ''));
        $age = trim((string) ($person->str_age ?? $person->age ?? ''));

        $parts = array_filter([$code, $name, trim($relative . ' ' . $rname)]);
        $text = implode(' / ', $parts);
        if ($age !== '') {
            $text .= ($text !== '' ? ' / ' : '') . 'Age : ' . $age;
        }

        return $text;
    }

    public function patient_info()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $inputUhid = trim((string) $this->request->getPost('input_uhid'));
        $person = $this->findPatientByIdOrCode(0, $inputUhid);

        return $this->invoiceUpdateResponse(true, 'OK', [
            'Patient_id' => (int) ($person->id ?? 0),
            'Patient_info' => $this->buildPatientInfoText($person),
        ]);
    }

    public function ipd_info()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('ipd_master')) {
            return $this->invoiceUpdateResponse(false, 'IPD table not found', [
                'IPD_id' => 0,
                'ipd_info' => '',
            ]);
        }

        $inputIpdNo = trim((string) $this->request->getPost('input_ipd_no'));
        $ipdBuilder = $this->db->table('ipd_master');
        $ipdFields = $this->db->getFieldNames('ipd_master') ?? [];

        if ($inputIpdNo !== '' && in_array('ipd_code', $ipdFields, true)) {
            $ipdBuilder->where('ipd_code', $inputIpdNo);
        } elseif ((int) $inputIpdNo > 0) {
            $ipdBuilder->where('id', (int) $inputIpdNo);
        } else {
            return $this->invoiceUpdateResponse(true, 'OK', [
                'IPD_id' => 0,
                'ipd_info' => '',
            ]);
        }

        $ipd = $ipdBuilder->get()->getRow();
        if (! $ipd) {
            return $this->invoiceUpdateResponse(true, 'OK', [
                'IPD_id' => 0,
                'ipd_info' => '',
            ]);
        }

        $patientId = (int) ($ipd->p_id ?? $ipd->patient_id ?? 0);
        $person = $this->findPatientByIdOrCode($patientId);
        $ipdInfo = trim(($ipd->ipd_code ?? ('IPD#' . ($ipd->id ?? ''))) . ' / ' . $this->buildPatientInfoText($person));

        return $this->invoiceUpdateResponse(true, 'OK', [
            'IPD_id' => (int) ($ipd->id ?? 0),
            'ipd_info' => $ipdInfo,
        ]);
    }

    public function org_info()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('organization_case_master')) {
            return $this->invoiceUpdateResponse(false, 'Organization case table not found', [
                'org_id' => 0,
                'org_info' => '',
            ]);
        }

        $inputOrgNo = trim((string) $this->request->getPost('input_org_no'));
        $builder = $this->db->table('organization_case_master');
        $orgFields = $this->db->getFieldNames('organization_case_master') ?? [];
        if (in_array('case_type', $orgFields, true)) {
            $builder->where('case_type', 0);
        }

        if ($inputOrgNo !== '' && in_array('case_id_code', $orgFields, true)) {
            $builder->where('case_id_code', $inputOrgNo);
        } elseif ((int) $inputOrgNo > 0) {
            $builder->where('id', (int) $inputOrgNo);
        } else {
            return $this->invoiceUpdateResponse(true, 'OK', [
                'org_id' => 0,
                'org_info' => '',
            ]);
        }

        $orgCase = $builder->get()->getRow();
        if (! $orgCase) {
            return $this->invoiceUpdateResponse(true, 'OK', [
                'org_id' => 0,
                'org_info' => '',
            ]);
        }

        $person = $this->findPatientByIdOrCode((int) ($orgCase->p_id ?? 0));
        $patientInfo = $this->buildPatientInfoText($person);
        $insurer = (string) ($orgCase->insurance_company_name ?? '');
        $regNo = (string) ($orgCase->insurance_no_1 ?? '');
        $regDate = (string) ($orgCase->date_registration ?? '');

        $orgInfo = $patientInfo;
        if ($insurer !== '') {
            $orgInfo .= ($orgInfo !== '' ? ' / ' : '') . $insurer;
        }
        if ($regNo !== '') {
            $orgInfo .= ' / Reg.:' . $regNo;
        }
        if ($regDate !== '') {
            $orgInfo .= ' / Reg.Dt:' . $regDate;
        }

        return $this->invoiceUpdateResponse(true, 'OK', [
            'org_id' => (int) ($orgCase->id ?? 0),
            'org_info' => $orgInfo,
        ]);
    }

    public function update_uhid()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        $patientId = (int) $this->request->getPost('pid');
        $inputUhid = trim((string) $this->request->getPost('input_uhid'));

        if ($invoiceId <= 0) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $person = $this->findPatientByIdOrCode($patientId, $inputUhid);
        if (! $person) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $ok = $this->updateInvoiceMasterByFields($invoiceId, [
            'customer_type' => 1,
            'patient_id' => (int) ($person->id ?? 0),
            'patient_code' => (string) ($person->p_code ?? $person->id ?? ''),
            'inv_name' => (string) ($person->p_fname ?? ''),
            'ipd_credit' => 0,
            'case_credit' => 0,
            'ipd_id' => 0,
            'ipd_code' => '',
            'case_id' => 0,
        ]);

        return $this->invoiceUpdateResponse($ok, $ok ? 'Done' : 'Not Done');
    }

    public function update_ipd()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        $ipdId = (int) $this->request->getPost('ipd_id');
        $inputIpdNo = trim((string) $this->request->getPost('input_ipd_no'));

        if ($invoiceId <= 0 || ! $this->db->tableExists('ipd_master')) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $builder = $this->db->table('ipd_master');
        if ($ipdId > 0) {
            $builder->where('id', $ipdId);
        } elseif ($inputIpdNo !== '') {
            $fields = $this->db->getFieldNames('ipd_master') ?? [];
            if (in_array('ipd_code', $fields, true)) {
                $builder->where('ipd_code', $inputIpdNo);
            } elseif ((int) $inputIpdNo > 0) {
                $builder->where('id', (int) $inputIpdNo);
            } else {
                return $this->invoiceUpdateResponse(false, 'Not Done');
            }
        } else {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $ipd = $builder->get()->getRow();
        if (! $ipd) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $person = $this->findPatientByIdOrCode((int) ($ipd->p_id ?? $ipd->patient_id ?? 0));
        if (! $person) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $ok = $this->updateInvoiceMasterByFields($invoiceId, [
            'customer_type' => 1,
            'patient_id' => (int) ($person->id ?? 0),
            'patient_code' => (string) ($person->p_code ?? $person->id ?? ''),
            'inv_name' => (string) ($person->p_fname ?? ''),
            'ipd_credit' => 0,
            'case_credit' => 0,
            'ipd_id' => (int) ($ipd->id ?? 0),
            'ipd_code' => (string) ($ipd->ipd_code ?? ''),
            'case_id' => 0,
        ]);

        return $this->invoiceUpdateResponse($ok, $ok ? 'Done' : 'Not Done');
    }

    public function update_org()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        $orgId = (int) $this->request->getPost('org_id');
        $inputOrgNo = trim((string) $this->request->getPost('input_org_no'));

        if ($invoiceId <= 0 || ! $this->db->tableExists('organization_case_master')) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $builder = $this->db->table('organization_case_master');
        $orgFields = $this->db->getFieldNames('organization_case_master') ?? [];
        if (in_array('case_type', $orgFields, true)) {
            $builder->where('case_type', 0);
        }

        if ($orgId > 0) {
            $builder->where('id', $orgId);
        } elseif ($inputOrgNo !== '') {
            if (in_array('case_id_code', $orgFields, true)) {
                $builder->where('case_id_code', $inputOrgNo);
            } elseif ((int) $inputOrgNo > 0) {
                $builder->where('id', (int) $inputOrgNo);
            } else {
                return $this->invoiceUpdateResponse(false, 'Not Done');
            }
        } else {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $orgCase = $builder->get()->getRow();
        if (! $orgCase) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $person = $this->findPatientByIdOrCode((int) ($orgCase->p_id ?? 0));
        if (! $person) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $ok = $this->updateInvoiceMasterByFields($invoiceId, [
            'customer_type' => 1,
            'patient_id' => (int) ($person->id ?? 0),
            'patient_code' => (string) ($person->p_code ?? $person->id ?? ''),
            'inv_name' => (string) ($person->p_fname ?? ''),
            'ipd_credit' => 0,
            'case_credit' => 1,
            'ipd_id' => 0,
            'ipd_code' => '',
            'case_id' => (int) ($orgCase->id ?? 0),
        ]);

        return $this->invoiceUpdateResponse($ok, $ok ? 'Done' : 'Not Done');
    }

    public function update_invdate()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        $invDate = trim((string) $this->request->getPost('inv_date'));

        if ($invoiceId <= 0 || $invDate === '') {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $invDate)) {
            $parts = explode('/', $invDate);
            $invDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $invDate)) {
            $timestamp = strtotime(str_replace('/', '-', $invDate));
            if (! $timestamp) {
                return $this->invoiceUpdateResponse(false, 'Not Done');
            }
            $invDate = date('Y-m-d', $timestamp);
        }

        $ok = $this->updateInvoiceMasterByFields($invoiceId, [
            'inv_date' => $invDate,
        ]);

        return $this->invoiceUpdateResponse($ok, $ok ? 'Done' : 'Not Done');
    }

    public function update_name_phone()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        $customerType = (int) $this->request->getPost('customer_type');
        $patientName = trim((string) $this->request->getPost('P_Name'));
        $patientPhone = trim((string) $this->request->getPost('P_Phone'));
        $patientPhone = preg_replace('/\D+/', '', $patientPhone) ?? '';
        $docId = (int) $this->request->getPost('doc_id');
        $docName = trim((string) $this->request->getPost('doc_name'));

        if ($invoiceId <= 0) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        if ($patientPhone !== '' && strlen($patientPhone) !== 10) {
            return $this->invoiceUpdateResponse(false, 'Phone number must be exactly 10 digits.');
        }

        if ($docId > 0 && $this->db->tableExists('doctor_master')) {
            $doc = $this->db->table('doctor_master')->where('id', $docId)->get()->getRow();
            if ($doc) {
                $docName = (string) ($doc->p_fname ?? $docName);
            }
        }

        $ok = $this->updateInvoiceMasterByFields($invoiceId, [
            'customer_type' => $customerType,
            'patient_id' => 0,
            'patient_code' => '',
            'inv_name' => $patientName,
            'inv_phone_number' => $patientPhone,
            'ipd_credit' => 0,
            'case_credit' => 0,
            'ipd_id' => 0,
            'ipd_code' => '',
            'case_id' => 0,
            'doc_id' => $docId,
            'doc_name' => $docName,
        ]);

        return $this->invoiceUpdateResponse($ok, $ok ? 'Done' : 'Not Done');
    }

    public function update_doctor()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        $docId = (int) $this->request->getPost('doc_id');
        $docName = trim((string) $this->request->getPost('doc_name'));

        if ($invoiceId <= 0) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        if ($docId > 0 && $this->db->tableExists('doctor_master')) {
            $doc = $this->db->table('doctor_master')->where('id', $docId)->get()->getRow();
            if ($doc) {
                $docName = (string) ($doc->p_fname ?? $docName);
            }
        }

        $ok = $this->updateInvoiceMasterByFields($invoiceId, [
            'doc_id' => $docId,
            'doc_name' => $docName,
        ]);

        return $this->invoiceUpdateResponse($ok, $ok ? 'Done' : 'Not Done');
    }

    public function update_cr_status_ipd()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        $creditIpd = (int) $this->request->getPost('credit_ipd');

        if ($invoiceId <= 0) {
            return $this->invoiceUpdateResponse(false, 'Not Done');
        }

        $ok = $this->updateInvoiceMasterByFields($invoiceId, [
            'ipd_credit' => $creditIpd,
        ]);

        return $this->invoiceUpdateResponse($ok, $ok ? 'Done' : 'Not Done');
    }

    public function add_item()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('inv_id');
        $stockId = (int) $this->request->getPost('stock_id');
        $qty = (float) $this->request->getPost('qty');
        $discPer = (float) $this->request->getPost('disc_per');

        if ($invoiceId <= 0 || $stockId <= 0 || $qty <= 0) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invalid item input')));
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invoice table missing')));
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=draft&msg=' . urlencode('Invoice not found')));
        }
        if ($this->isInvoiceFinalized($invoice)) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invoice is finalized and cannot be edited')));
        }

        if (! $this->db->tableExists('purchase_invoice_item') || ! $this->db->tableExists('med_product_master') || ! $this->db->tableExists('inv_med_item')) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Required stock tables are missing')));
        }

        $stockSql = "SELECT p.id AS item_code,p.item_name,p.formulation,t.id AS stock_id,t.batch_no,t.expiry_date,t.mrp,t.selling_unit_rate,
                    t.HSNCODE,t.CGST_per,t.SGST_per,t.packing,
                    (ifnull(t.total_unit,0)-ifnull(t.total_sale_unit,0)-ifnull(t.total_return_unit,0)-ifnull(t.total_lost_unit,0)) AS stock_qty
                FROM med_product_master p
                JOIN purchase_invoice_item t ON p.id=t.item_code
                WHERE t.id=?";
        $stock = $this->db->query($stockSql, [$stockId])->getRow();

        if (! $stock) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Stock item not found')));
        }

        $stockQty = (float) ($stock->stock_qty ?? 0);
        if ($qty > $stockQty) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Stock qty is less than required qty')));
        }

        $price = (float) ($stock->selling_unit_rate ?? 0);
        $amount = $qty * $price;
        $discAmount = ($discPer > 0) ? ($amount * $discPer / 100) : 0;
        $netAmount = $amount - $discAmount;

        $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
        $insert = [];

        $setIfExists = static function (array &$target, array $fields, string $key, $value): void {
            if (in_array($key, $fields, true)) {
                $target[$key] = $value;
            }
        };

        $user = service('auth')->user();
        $userId = (int) ($user->id ?? 0);
        $userInfo = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';

        $setIfExists($insert, $itemFields, 'inv_med_id', $invoiceId);
        $setIfExists($insert, $itemFields, 'item_code', (int) ($stock->item_code ?? 0));
        $setIfExists($insert, $itemFields, 'item_Name', (string) ($stock->item_name ?? ''));
        $setIfExists($insert, $itemFields, 'formulation', (string) ($stock->formulation ?? ''));
        $setIfExists($insert, $itemFields, 'qty', $qty);
        $setIfExists($insert, $itemFields, 'batch_no', (string) ($stock->batch_no ?? ''));
        $setIfExists($insert, $itemFields, 'expiry', $stock->expiry_date ?? null);
        $setIfExists($insert, $itemFields, 'price', $price);
        $setIfExists($insert, $itemFields, 'price2', $price);
        $setIfExists($insert, $itemFields, 'mrp', (float) ($stock->mrp ?? 0));
        $setIfExists($insert, $itemFields, 'disc_per', $discPer);
        $setIfExists($insert, $itemFields, 'disc_amount', $discAmount);
        $setIfExists($insert, $itemFields, 'disc_whole', 0);
        $setIfExists($insert, $itemFields, 'amount', $amount);
        $setIfExists($insert, $itemFields, 'tamount', $netAmount);
        $setIfExists($insert, $itemFields, 'twdisc_amount', $netAmount);
        $setIfExists($insert, $itemFields, 'CGST_per', (float) ($stock->CGST_per ?? 0));
        $setIfExists($insert, $itemFields, 'SGST_per', (float) ($stock->SGST_per ?? 0));
        $setIfExists($insert, $itemFields, 'HSNCODE', (string) ($stock->HSNCODE ?? ''));
        $setIfExists($insert, $itemFields, 'store_stock_id', (int) ($stock->stock_id ?? 0));
        $setIfExists($insert, $itemFields, 'packing', (int) ($stock->packing ?? 1));
        $setIfExists($insert, $itemFields, 'update_by_id', $userId);
        $setIfExists($insert, $itemFields, 'update_by_remark', $userInfo . '[' . $userId . '][' . date('d-m-Y H:i:s') . ']');

        $this->db->table('inv_med_item')->insert($insert);

        $this->recalculateInvoiceTotals($invoiceId);

        return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Item added')));
    }

    public function remove_item()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('inv_id');
        $itemId = (int) $this->request->getPost('item_id');

        if ($invoiceId <= 0 || $itemId <= 0 || ! $this->db->tableExists('inv_med_item')) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invalid remove request')));
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invoice table missing')));
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=draft&msg=' . urlencode('Invoice not found')));
        }
        if ($this->isInvoiceFinalized($invoice)) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invoice is finalized and cannot be edited')));
        }

        $itemRow = $this->db->table('inv_med_item')
            ->where('id', $itemId)
            ->where('inv_med_id', $invoiceId)
            ->get()
            ->getRowArray();

        $this->db->table('inv_med_item')
            ->where('id', $itemId)
            ->where('inv_med_id', $invoiceId)
            ->delete();

        if ($itemRow) {
            $this->archiveDeletedInvoiceItem($itemRow);

            $itemName = (string) ($itemRow['item_Name'] ?? 'Item');
            $batchNo = (string) ($itemRow['batch_no'] ?? '');
            $qty = (float) ($itemRow['qty'] ?? 0);
            $amount = (float) ($itemRow['twdisc_amount'] ?? ($itemRow['amount'] ?? 0));

            $summary = 'Item Deleted: ' . $itemName
                . ($batchNo !== '' ? (' [Batch:' . $batchNo . ']') : '')
                . ' Qty:' . number_format($qty, 2, '.', '')
                . ' Amount:' . number_format($amount, 2, '.', '');

            $this->appendInvoiceMasterLog($invoiceId, $summary);
            $this->writeMedicalAdminActionLog('pharmacy_invoice_delete', $summary, [
                'invoice_id' => $invoiceId,
                'item_id' => $itemId,
                'item_name' => $itemName,
                'batch_no' => $batchNo,
                'qty' => $qty,
                'amount' => $amount,
            ]);
        }

        $this->recalculateInvoiceTotals($invoiceId);

        return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Item removed')));
    }

    public function update_item_qty()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('inv_id');
        $itemId = (int) $this->request->getPost('item_id');
        $updateQty = (float) $this->request->getPost('u_qty');

        $respond = static function (int $update, string $msgText) {
            return service('response')->setJSON([
                'update' => $update,
                'msg_text' => $msgText,
                'content' => '',
            ]);
        };

        if ($invoiceId <= 0 || $itemId <= 0 || $updateQty <= 0 || ! $this->db->tableExists('inv_med_item')) {
            return $respond(0, 'Invalid qty input');
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return $respond(0, 'Invoice table missing');
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return $respond(0, 'Invoice not found');
        }
        if ($this->isInvoiceFinalized($invoice)) {
            return $respond(0, 'Invoice is finalized and cannot be edited');
        }

        $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
        $itemBuilder = $this->db->table('inv_med_item')
            ->where('id', $itemId)
            ->where('inv_med_id', $invoiceId);
        if (in_array('sale_return', $itemFields, true)) {
            $itemBuilder->where('sale_return', 0);
        }

        $item = $itemBuilder->get()->getRow();
        if (! $item) {
            return $respond(0, 'No item found');
        }

        $oldQty = (float) ($item->qty ?? 0);
        $diffQty = $updateQty - $oldQty;

        if ($diffQty > 0) {
            if (! $this->db->tableExists('purchase_invoice_item')) {
                return $respond(0, 'Stock is Empty');
            }

            $storeStockId = (int) ($item->store_stock_id ?? 0);
            if ($storeStockId <= 0) {
                return $respond(0, 'Stock is Empty');
            }

            $stockRow = $this->db->table('purchase_invoice_item')
                ->select('(ifnull(total_unit,0)-ifnull(total_sale_unit,0)-ifnull(total_return_unit,0)-ifnull(total_lost_unit,0)) AS stock_qty', false)
                ->where('id', $storeStockId)
                ->get()
                ->getRow();

            $stockQty = (float) ($stockRow->stock_qty ?? 0);
            if ($diffQty > $stockQty) {
                return $respond(0, 'Stock Qty. is less than Required Qty : Current Qty :' . $stockQty);
            }
        }

        $discPer = (float) ($item->disc_per ?? 0);
        $itemRate = (float) ($item->price ?? 0);
        $amountValue = $updateQty * $itemRate;
        $discAmount = $amountValue * $discPer / 100;
        $tamountValue = $amountValue - $discAmount;

        $update = [
            'qty' => $updateQty,
            'disc_amount' => $discAmount,
            'amount' => $amountValue,
            'tamount' => $tamountValue,
        ];
        if (in_array('twdisc_amount', $itemFields, true)) {
            $update['twdisc_amount'] = $tamountValue;
        }

        $this->db->table('inv_med_item')->where('id', $itemId)->update($update);

        $this->recalculateInvoiceTotals($invoiceId);

        $itemName = (string) ($item->item_Name ?? 'Item');
        $batchNo = (string) ($item->batch_no ?? '');
        $summary = 'Qty Change: ' . $itemName
            . ($batchNo !== '' ? (' [Batch:' . $batchNo . ']') : '')
            . ' ' . number_format($oldQty, 2, '.', '')
            . ' to ' . number_format($updateQty, 2, '.', '');

        $this->appendInvoiceMasterLog($invoiceId, $summary);
        $this->writeMedicalAdminActionLog('pharmacy_invoice_qty_update', $summary, [
            'invoice_id' => $invoiceId,
            'item_id' => $itemId,
            'item_name' => $itemName,
            'batch_no' => $batchNo,
            'old_qty' => $oldQty,
            'new_qty' => $updateQty,
        ]);

        return $respond($itemId, 'Qty updated');
    }

    public function add_remove_item()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('inv_id');
        $itemId = (int) $this->request->getPost('itemid');
        $rqty = (float) $this->request->getPost('rqty');

        $respond = static function (int $update, string $msgText) {
            return service('response')->setJSON([
                'update' => $update,
                'msg_text' => $msgText,
                'content' => '',
            ]);
        };

        if ($invoiceId <= 0 || $itemId <= 0 || $rqty <= 0 || ! $this->db->tableExists('inv_med_item')) {
            return $respond(0, 'Invalid return input');
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return $respond(0, 'Invoice table missing');
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return $respond(0, 'Invoice not found');
        }

        if ($this->isInvoiceFinalized($invoice)) {
            return $respond(0, 'Invoice is finalized and cannot be edited');
        }

        $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
        if (empty($itemFields)) {
            return $respond(0, 'Item table structure missing');
        }

        $sourceBuilder = $this->db->table('inv_med_item')->where('id', $itemId);
        if (in_array('sale_return', $itemFields, true)) {
            $sourceBuilder->where('sale_return', 0);
        }
        $source = $sourceBuilder->get()->getRowArray();

        if (! $source) {
            return $respond(0, 'No item found');
        }

        $oldQty = (float) ($source['qty'] ?? 0);
        $totalReturned = 0.0;
        if (in_array('sale_return', $itemFields, true) && in_array('return_item_id', $itemFields, true)) {
            $totalReturnedRow = $this->db->table('inv_med_item')
                ->select('ifnull(sum(qty),0) as t_r_qty')
                ->where('sale_return', 1)
                ->where('return_item_id', $itemId)
                ->get()
                ->getRow();
            $totalReturned = (float) ($totalReturnedRow->t_r_qty ?? 0);
        }

        if (($oldQty - $totalReturned) < $rqty) {
            return $respond(0, 'Item Already Returned');
        }

        $itemRate = (float) ($source['price2'] ?? ($source['price'] ?? 0));
        $amountValue = $rqty * $itemRate * -1;

        $amount = (float) ($source['amount'] ?? 0);
        $twdiscAmount = (float) ($source['twdisc_amount'] ?? $amount);
        $margin = $amount - $twdiscAmount;
        $discPer = ($amount != 0.0) ? ($margin * 100 / $amount) : (float) ($source['disc_per'] ?? 0);

        $discAmount = $amountValue * $discPer / 100;
        $tamountValue = $amountValue - $discAmount;

        $payload = [];
        foreach ($itemFields as $field) {
            if (array_key_exists($field, $source)) {
                $payload[$field] = $source[$field];
            }
        }

        $setIfExists = static function (array &$target, array $fields, string $key, $value): void {
            if (in_array($key, $fields, true)) {
                $target[$key] = $value;
            }
        };

        $user = service('auth')->user();
        $userId = (int) ($user->id ?? 0);
        $userInfo = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';

        unset($payload['id']);
        $setIfExists($payload, $itemFields, 'inv_med_id', $invoiceId);
        $setIfExists($payload, $itemFields, 'qty', $rqty);
        $setIfExists($payload, $itemFields, 'return_item_id', $itemId);
        $setIfExists($payload, $itemFields, 'sale_return', 1);
        $setIfExists($payload, $itemFields, 'amount', $amountValue);
        $setIfExists($payload, $itemFields, 'tamount', $tamountValue);
        $setIfExists($payload, $itemFields, 'disc_amount', $discAmount);
        $setIfExists($payload, $itemFields, 'disc_per', $discPer);
        $setIfExists($payload, $itemFields, 'disc_whole', 0);
        $setIfExists($payload, $itemFields, 'twdisc_amount', $tamountValue);
        $setIfExists($payload, $itemFields, 'update_by_id', $userId);
        $setIfExists($payload, $itemFields, 'update_by_remark', $userInfo . '[' . $userId . '][' . date('d-m-Y H:i:s') . ']');

        $existingReturnId = 0;
        if (in_array('sale_return', $itemFields, true) && in_array('return_item_id', $itemFields, true)) {
            $existingReturn = $this->db->table('inv_med_item')
                ->select('id')
                ->where('inv_med_id', $invoiceId)
                ->where('sale_return', 1)
                ->where('return_item_id', $itemId)
                ->get()
                ->getRow();
            $existingReturnId = (int) ($existingReturn->id ?? 0);
        }

        if ($existingReturnId > 0) {
            $this->db->table('inv_med_item')->where('id', $existingReturnId)->update($payload);
            $updatedId = $existingReturnId;
            $msgText = 'Update Return';
        } else {
            $this->db->table('inv_med_item')->insert($payload);
            $updatedId = (int) $this->db->insertID();
            $msgText = 'Added Return list';
        }

        if ($updatedId <= 0) {
            return $respond(0, 'Not Done');
        }

        $this->recalculateInvoiceTotals($invoiceId);

        return $respond($updatedId, $msgText);
    }

    public function go_final()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return view('medical/placeholder', ['title' => 'invoice_med_master table not found']);
        }

        $invoiceId = (int) $this->request->getPost('inv_id');
        if ($invoiceId <= 0) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft'));
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft'));
        }

        if ($this->isInvoiceFinalized($invoice)) {
            return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invoice already finalized')));
        }

        $this->recalculateInvoiceTotals($invoiceId);

        $this->finalizeInvoiceRecord($invoiceId, $invoice, [
            'doc_id' => $this->request->getPost('doc_id'),
            'doc_name' => $this->request->getPost('doc_name'),
            'input_remark_ipd' => $this->request->getPost('input_remark_ipd'),
            'patient_code' => $this->request->getPost('patient_code'),
            'custmer_Name' => $this->request->getPost('custmer_Name'),
            'ipd_credit' => $this->request->getPost('ipd_credit'),
            'org_credit' => $this->request->getPost('org_credit'),
            'inv_date' => $this->request->getPost('inv_date'),
        ]);

        return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invoice finalized')));
    }

    public function final_invoice($invoiceId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=draft'));
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=draft'));
        }

        if (! $this->isInvoiceFinalized($invoice)) {
            $this->finalizeInvoiceRecord($invoiceId, $invoice, []);
        }

        $this->recalculateInvoiceTotals($invoiceId);

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=draft'));
        }

        $ipdId = (int) ($invoice->ipd_id ?? 0);
        if ($ipdId > 0) {
            return redirect()->to(base_url('Medical/list_med_inv/' . $ipdId));
        }

        $caseId = (int) ($invoice->case_id ?? 0);
        $caseCredit = (int) ($invoice->case_credit ?? 0);
        if ($caseId > 0 && $caseCredit > 0) {
            return redirect()->to(base_url('Medical/list_med_orginv/' . $caseId));
        }

        $items = [];
        if ($this->db->tableExists('inv_med_item')) {
            $items = $this->db->table('inv_med_item')
                ->where('inv_med_id', $invoiceId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResult();
        }

        $patient = null;
        if (! empty($invoice->patient_id) && $this->db->tableExists('patient_master_exten')) {
            $patient = $this->db->table('patient_master_exten')->where('id', (int) $invoice->patient_id)->get()->getRow();
        } elseif (! empty($invoice->patient_id) && $this->db->tableExists('patient_master')) {
            $patient = $this->db->table('patient_master')->where('id', (int) $invoice->patient_id)->get()->getRow();
        }

        $ipd = null;
        if ($ipdId > 0 && $this->db->tableExists('ipd_master')) {
            $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get()->getRow();
        }

        $orgCase = null;
        if ($caseId > 0 && $this->db->tableExists('organization_case_master')) {
            $orgCase = $this->db->table('organization_case_master')->where('id', $caseId)->get()->getRow();
        }

        $this->refreshInvoicePaymentFields($invoiceId, true);
        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();

        $paymentHistory = $this->getInvoicePaymentHistory($invoiceId);
        $bankSources = $this->getMedicalBankSources();
        $paymentSummary = $this->getInvoicePaymentSummary($invoice);
        $message = trim((string) $this->request->getGet('msg'));
        $editBlockReason = null;
        $canEditInvoice = $this->canEditInvoiceRecord((array) $invoice, $editBlockReason);

        return view('medical/medical_final_invoice', [
            'invoice' => $invoice,
            'items' => $items,
            'patient' => $patient,
            'ipd' => $ipd,
            'orgCase' => $orgCase,
            'paymentHistory' => $paymentHistory,
            'bankSources' => $bankSources,
            'paymentSummary' => $paymentSummary,
            'message' => $message,
            'canEditInvoice' => $canEditInvoice,
            'editBlockReason' => (string) ($editBlockReason ?? ''),
        ]);
    }

    public function list_med_inv($ipdId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ipdId = (int) $ipdId;
        if ($ipdId <= 0 || ! $this->db->tableExists('ipd_master') || ! $this->db->tableExists('invoice_med_master')) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=all'));
        }

        $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get()->getRow();
        if (! $ipd) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=all'));
        }

        $patientId = (int) ($ipd->p_id ?? ($ipd->patient_id ?? 0));
        $patientTable = $this->db->tableExists('patient_master_exten')
            ? 'patient_master_exten'
            : ($this->db->tableExists('patient_master') ? 'patient_master' : null);

        $patient = null;
        if ($patientId > 0 && $patientTable) {
            $patient = $this->db->table($patientTable)->where('id', $patientId)->get()->getRow();
        }

        $invoiceFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $invoiceBuilder = $this->db->table('invoice_med_master')->where('ipd_id', $ipdId);
        if (in_array('sale_return', $invoiceFields, true)) {
            $invoiceBuilder->where('sale_return', 0);
        }
        $invoices = $invoiceBuilder->orderBy('id', 'DESC')->get()->getResult();

        $cashTotal = 0.0;
        $creditTotal = 0.0;
        $packageTotal = 0.0;
        $amountTotal = 0.0;
        $balanceTotal = 0.0;
        $legacyBalanceTotal = 0.0;
        $discountTotal = 0.0;
        $currentDiscount = 0.0;

        if (! empty($invoices)) {
            $currentDiscount = (float) ($invoices[0]->discount_amount ?? 0);
        }

        foreach ($invoices as $row) {
            $net = (float) ($row->net_amount ?? 0);
            $balance = (float) ($row->payment_balance ?? 0);
            $discountTotal += (float) ($row->discount_amount ?? 0);
            $amountTotal += $net;
            $legacyBalanceTotal += $balance;

            $ipdCredit = (int) ($row->ipd_credit ?? 0);
            $groupInvoiceId = (int) ($row->group_invoice_id ?? 0);
            if ($ipdCredit > 0 && $groupInvoiceId > 0) {
                $packageTotal += $net;
            } elseif ($ipdCredit > 0) {
                $creditTotal += $net;
            } else {
                $cashTotal += $net;
            }
        }

        $paidTotal = null;
        if ($this->db->tableExists('payment_history_medical')) {
            $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
            $payBuilder = $this->db->table('payment_history_medical');

            if (in_array('ipd_id', $payFields, true)) {
                $payBuilder->where('ipd_id', $ipdId);
            } elseif (in_array('Medical_invoice_id', $payFields, true)) {
                $invoiceIds = array_values(array_filter(array_map(static fn($r) => (int) ($r->id ?? 0), $invoices)));
                if (! empty($invoiceIds)) {
                    $payBuilder->whereIn('Medical_invoice_id', $invoiceIds);
                } else {
                    $payBuilder->where('1=0', null, false);
                }
            } else {
                $payBuilder->where('1=0', null, false);
            }

            if (in_array('credit_debit', $payFields, true)) {
                $payBuilder->select('ifnull(sum(case when credit_debit>0 then amount*-1 else amount end),0) as paid_total', false);
            } else {
                $payBuilder->select('ifnull(sum(amount),0) as paid_total', false);
            }

            $paidRow = $payBuilder->get()->getRow();
            $paidTotal = (float) ($paidRow->paid_total ?? 0);
        }

        if ($paidTotal !== null) {
            $balanceTotal = $amountTotal - $paidTotal;
        } else {
            $balanceTotal = $legacyBalanceTotal;
        }

        $invoiceEditMeta = [];
        foreach ($invoices as $row) {
            $invoiceId = (int) ($row->id ?? 0);
            if ($invoiceId <= 0) {
                continue;
            }

            $reason = null;
            $canEdit = $this->canEditInvoiceRecord((array) $row, $reason);
            $invoiceEditMeta[$invoiceId] = [
                'can_edit' => $canEdit,
                'reason' => (string) ($reason ?? ''),
            ];
        }

        return view('medical/medical_invoice_list', [
            'ipd' => $ipd,
            'patient' => $patient,
            'invoices' => $invoices,
            'cashTotal' => $cashTotal,
            'creditTotal' => $creditTotal,
            'packageTotal' => $packageTotal,
            'discountTotal' => $discountTotal,
            'currentDiscount' => $currentDiscount,
            'amountTotal' => $amountTotal,
            'balanceTotal' => $balanceTotal,
            'invoiceEditMeta' => $invoiceEditMeta,
        ]);
    }

    public function list_med_orginv($orgId = 0, $storeId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $orgId = (int) $orgId;
        if ($orgId <= 0) {
            return redirect()->to(base_url('Medical/list_org'));
        }

        if (! $this->db->tableExists('organization_case_master')) {
            return redirect()->to(base_url('Medical/list_org'));
        }

        $orgFields = $this->db->getFieldNames('organization_case_master') ?? [];
        $orgBuilder = $this->db->table('organization_case_master o');
        $orgBuilder->where('o.id', $orgId);

        $patientTable = $this->db->tableExists('patient_master_exten')
            ? 'patient_master_exten'
            : ($this->db->tableExists('patient_master') ? 'patient_master' : null);

        $patientFields = $patientTable ? ($this->db->getFieldNames($patientTable) ?? []) : [];

        $select = [
            'o.id',
            in_array('case_id_code', $orgFields, true) ? 'o.case_id_code' : "CONCAT('ORG-',o.id) AS case_id_code",
            in_array('insurance_company_name', $orgFields, true) ? 'o.insurance_company_name' : "'' AS insurance_company_name",
            in_array('p_id', $orgFields, true) ? 'o.p_id' : '0 AS p_id',
        ];

        if ($patientTable && in_array('p_id', $orgFields, true) && in_array('id', $patientFields, true)) {
            $orgBuilder->join($patientTable . ' p', 'p.id=o.p_id', 'left');
            $select[] = in_array('p_code', $patientFields, true) ? 'p.p_code' : "'' AS p_code";
            $select[] = in_array('p_fname', $patientFields, true) ? 'p.p_fname' : "'' AS p_fname";
            $select[] = in_array('p_rname', $patientFields, true) ? 'p.p_rname' : "'' AS p_rname";
            if (in_array('mphone1', $patientFields, true)) {
                $select[] = 'p.mphone1';
            }
        } else {
            $select[] = "'' AS p_code";
            $select[] = "'' AS p_fname";
            $select[] = "'' AS p_rname";
            $select[] = "'' AS mphone1";
        }

        $orgRow = $orgBuilder->select(implode(',', $select), false)->get()->getRow();
        if (! $orgRow) {
            return redirect()->to(base_url('Medical/list_org'));
        }

        $invoiceRows = [];
        if ($this->db->tableExists('invoice_med_master')) {
            $invFields = $this->db->getFieldNames('invoice_med_master') ?? [];
            $resolveField = static function (array $fields, array $candidates): ?string {
                foreach ($candidates as $candidate) {
                    foreach ($fields as $field) {
                        if (strcasecmp((string) $field, (string) $candidate) === 0) {
                            return (string) $field;
                        }
                    }
                }
                return null;
            };

            $idField = $resolveField($invFields, ['id']);
            $caseIdField = $resolveField($invFields, ['case_id']);
            $invCodeField = $resolveField($invFields, ['inv_med_code', 'inv_no']);
            $invDateField = $resolveField($invFields, ['inv_date']);
            $remarkField = $resolveField($invFields, ['remark_ipd', 'remark']);
            $caseCreditField = $resolveField($invFields, ['case_credit']);
            $netAmountField = $resolveField($invFields, ['net_amount']);
            $paymentBalanceField = $resolveField($invFields, ['payment_balance']);

            if ($idField !== null && $caseIdField !== null) {
                $invoiceRows = $this->db->table('invoice_med_master m')
                    ->select(implode(',', [
                        'm.' . $idField . ' AS id',
                        ($invCodeField !== null ? ('m.' . $invCodeField) : ('CAST(m.' . $idField . ' AS CHAR)')) . ' AS inv_med_code',
                        ($invDateField !== null ? ('m.' . $invDateField) : 'NULL') . ' AS inv_date',
                        ($remarkField !== null ? ('m.' . $remarkField) : "''") . ' AS remark_ipd',
                        ($caseCreditField !== null ? ('m.' . $caseCreditField) : '0') . ' AS case_credit',
                        ($netAmountField !== null ? ('IFNULL(m.' . $netAmountField . ',0)') : '0') . ' AS net_amount',
                        ($paymentBalanceField !== null ? ('IFNULL(m.' . $paymentBalanceField . ',0)') : '0') . ' AS payment_balance',
                    ]), false)
                    ->where('m.' . $caseIdField, $orgId)
                    ->orderBy($invDateField !== null ? ('m.' . $invDateField) : ('m.' . $idField), 'ASC')
                    ->orderBy('m.' . $idField, 'ASC')
                    ->get()
                    ->getResult();
            }
        }

        $totals = [
            'amount_total' => 0.0,
            'balance_total' => 0.0,
        ];
        foreach ($invoiceRows as $row) {
            $totals['amount_total'] += (float) ($row->net_amount ?? 0);
            $totals['balance_total'] += (float) ($row->payment_balance ?? 0);
        }

        return view('medical/medical_org_invoicelist', [
            'orgcase' => $orgRow,
            'invoices' => $invoiceRows,
            'totals' => $totals,
        ]);
    }

    public function lock_ipd($ipdId = 0, $lock = 1)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ipdId = (int) $ipdId;
        $lock = ((int) $lock) > 0 ? 1 : 0;
        if ($ipdId <= 0 || ! $this->db->tableExists('ipd_master')) {
            return service('response')->setJSON(['update' => 0, 'msg_text' => 'Invalid IPD']);
        }

        $fields = $this->db->getFieldNames('ipd_master') ?? [];
        if (! in_array('lock_medical', $fields, true)) {
            return service('response')->setJSON(['update' => 0, 'msg_text' => 'lock_medical field missing']);
        }

        $this->db->table('ipd_master')->where('id', $ipdId)->update(['lock_medical' => $lock]);

        return service('response')->setJSON(['update' => 1, 'msg_text' => 'Update Success']);
    }

    public function med_return($ipdId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $data = $this->buildIpdReturnPageData((int) $ipdId);
        if (! $data) {
            return redirect()->to(base_url('Medical/list_med_inv/' . (int) $ipdId));
        }

        $data['modeLabel'] = 'Medicine Reurn';

        return view('medical/ipd_return_medicine', $data);
    }

    public function med_return_new($ipdId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $data = $this->buildIpdReturnPageData((int) $ipdId);
        if (! $data) {
            return redirect()->to(base_url('Medical/list_med_inv/' . (int) $ipdId));
        }

        $data['modeLabel'] = 'Medicine Reurn New';

        return view('medical/ipd_return_medicine', $data);
    }

    public function med_cash_payment($ipdId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ipdId = (int) $ipdId;
        if ($ipdId <= 0 || ! $this->db->tableExists('ipd_master') || ! $this->db->tableExists('invoice_med_master')) {
            return redirect()->to(base_url('Medical/list_med_inv/' . $ipdId));
        }

        $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get()->getRow();
        if (! $ipd) {
            return redirect()->to(base_url('Medical/list_med_inv/' . $ipdId));
        }

        $patientId = (int) ($ipd->p_id ?? ($ipd->patient_id ?? 0));
        $patientTable = $this->db->tableExists('patient_master_exten')
            ? 'patient_master_exten'
            : ($this->db->tableExists('patient_master') ? 'patient_master' : null);

        $patient = null;
        if ($patientId > 0 && $patientTable) {
            $patient = $this->db->table($patientTable)->where('id', $patientId)->get()->getRow();
        }

        $group = null;
        if ($this->db->tableExists('inv_med_group')) {
            $groupBuilder = $this->db->table('inv_med_group')->where('ipd_id', $ipdId);
            $groupFields = $this->db->getFieldNames('inv_med_group') ?? [];
            if (in_array('med_type', $groupFields, true)) {
                $groupBuilder->where('med_type', 1);
            }
            if (in_array('med_group_id', $groupFields, true)) {
                $groupBuilder->orderBy('med_group_id', 'DESC');
            } else {
                $groupBuilder->orderBy('id', 'DESC');
            }
            $group = $groupBuilder->get()->getRow();
        }

        if ($group) {
            $this->refreshIpdGroupPaymentFields($ipdId, (int) ($group->med_group_id ?? 0));
            if ($this->db->tableExists('inv_med_group')) {
                $group = $this->db->table('inv_med_group')
                    ->where('med_group_id', (int) ($group->med_group_id ?? 0))
                    ->get()
                    ->getRow() ?? $group;
            }
        }

        $paymentHistory = [];
        if ($this->db->tableExists('payment_history_medical')) {
            $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
            $payBuilder = $this->db->table('payment_history_medical');

            $medGroupId = (int) ($group->med_group_id ?? 0);
            if ($medGroupId > 0 && in_array('group_id', $payFields, true)) {
                $payBuilder->where('group_id', $medGroupId);
            } elseif (in_array('ipd_id', $payFields, true)) {
                $payBuilder->where('ipd_id', $ipdId);
            }

            if (in_array('credit_debit', $payFields, true)) {
                $payBuilder->select('*, if(credit_debit>0, amount*-1, amount) as paid_amount', false);
            } else {
                $payBuilder->select('*, amount as paid_amount', false);
            }

            if (in_array('id', $payFields, true)) {
                $payBuilder->orderBy('id', 'DESC');
            } else {
                $payBuilder->orderBy('payment_date', 'DESC');
            }

            $paymentHistory = $payBuilder->get()->getResult();

            foreach ($paymentHistory as $row) {
                $mode = (int) ($row->payment_mode ?? 0);
                $modeText = match ($mode) {
                    1 => 'Cash',
                    2 => 'Bank Card',
                    3 => 'Return Cash',
                    4 => 'Bank Return',
                    5 => 'Cash Return',
                    default => 'Other',
                };

                if ((int) ($row->credit_debit ?? 0) > 0) {
                    $modeText .= ' Return';
                }

                $row->Payment_type_str = $modeText;
            }
        }

        $bankSources = $this->getMedicalBankSources();

        $phoneNumbers = array_values(array_unique(array_filter([
            (string) ($patient->mphone1 ?? ''),
            (string) ($ipd->P_mobile1 ?? ''),
            (string) ($ipd->P_mobile2 ?? ''),
        ])));

        $paymentSummary = [
            'net_amount' => 0.0,
            'paid_amount' => 0.0,
            'balance_amount' => 0.0,
        ];

        if ($group) {
            $paymentSummary['net_amount'] = (float) ($group->net_amount ?? 0);
            $paymentSummary['paid_amount'] = (float) ($group->payment_received ?? 0);
            $paymentSummary['balance_amount'] = (float) ($group->payment_balance ?? ($paymentSummary['net_amount'] - $paymentSummary['paid_amount']));
        }

        if ($paymentSummary['net_amount'] <= 0.0 && $this->db->tableExists('invoice_med_master')) {
            $invFields = $this->db->getFieldNames('invoice_med_master') ?? [];
            $invBuilder = $this->db->table('invoice_med_master')
                ->select('ifnull(sum(net_amount),0) as net_amount', false)
                ->where('ipd_id', $ipdId);

            if (in_array('sale_return', $invFields, true)) {
                $invBuilder->where('sale_return', 0);
            }

            $invSum = $invBuilder->get()->getRow();
            $paymentSummary['net_amount'] = (float) ($invSum->net_amount ?? 0);
        }

        if ($this->db->tableExists('payment_history_medical')) {
            $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
            $sumBuilder = $this->db->table('payment_history_medical');

            $medGroupId = (int) ($group->med_group_id ?? 0);
            if ($medGroupId > 0 && in_array('group_id', $payFields, true)) {
                $sumBuilder->where('group_id', $medGroupId);
            } elseif (in_array('ipd_id', $payFields, true)) {
                $sumBuilder->where('ipd_id', $ipdId);
            }

            if (in_array('credit_debit', $payFields, true)) {
                $sumBuilder->select('ifnull(sum(case when credit_debit>0 then amount*-1 else amount end),0) as paid_amount', false);
            } else {
                $sumBuilder->select('ifnull(sum(amount),0) as paid_amount', false);
            }

            $paidSum = $sumBuilder->get()->getRow();
            $paymentSummary['paid_amount'] = (float) ($paidSum->paid_amount ?? 0);
        }

        $paymentSummary['balance_amount'] = $paymentSummary['net_amount'] - $paymentSummary['paid_amount'];

        return view('medical/med_model_payment', [
            'ipd' => $ipd,
            'patient' => $patient,
            'group' => $group,
            'paymentSummary' => $paymentSummary,
            'paymentHistory' => $paymentHistory,
            'bankSources' => $bankSources,
            'phoneNumbers' => $phoneNumbers,
        ]);
    }

    public function group_confirm_payment()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ipdId = (int) $this->request->getPost('ipd_id');
        $amount = (float) $this->request->getPost('amount');
        $medGroupId = (int) $this->request->getPost('Med_Group_id');
        $mode = (int) $this->request->getPost('mode');
        $creditDebit = ((int) $this->request->getPost('cr_dr')) > 0 ? 1 : 0;
        $payTypeId = (int) $this->request->getPost('cbo_pay_type');
        $cardTranId = trim((string) $this->request->getPost('input_card_tran'));
        $cashRemark = trim((string) $this->request->getPost('cash_remark'));
        $datePayment = trim((string) $this->request->getPost('date_payment'));

        if ($ipdId <= 0 || $amount <= 0 || ! in_array($mode, [1, 2], true)) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Invalid payment request']);
        }

        if ($mode === 2 && $cardTranId === '') {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Card Transaction ID is required']);
        }

        if (! $this->db->tableExists('ipd_master') || ! $this->db->tableExists('payment_history_medical')) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Payment tables not found']);
        }

        $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get()->getRow();
        if (! $ipd) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'IPD not found']);
        }

        if ($medGroupId <= 0 && $this->db->tableExists('inv_med_group')) {
            $groupBuilder = $this->db->table('inv_med_group')->where('ipd_id', $ipdId);
            $groupFields = $this->db->getFieldNames('inv_med_group') ?? [];
            if (in_array('med_type', $groupFields, true)) {
                $groupBuilder->where('med_type', 1);
            }
            $group = $groupBuilder->orderBy('med_group_id', 'DESC')->get()->getRow();
            $medGroupId = (int) ($group->med_group_id ?? 0);
        }

        $paymentDate = date('Y-m-d H:i:s');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePayment)) {
            $paymentDate = $datePayment . ' ' . date('H:i:s');
        }

        $user = service('auth')->user();
        $userId = (int) ($user->id ?? 0);
        $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';
        $userLabel = trim($userName . '[' . $userId . ']');

        $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
        $payData = [];
        $setIfExists = static function (array &$target, array $fields, string $key, $value): void {
            if (in_array($key, $fields, true)) {
                $target[$key] = $value;
            }
        };

        $setIfExists($payData, $payFields, 'payment_mode', $mode);
        $setIfExists($payData, $payFields, 'Customerof_type', 2);
        $setIfExists($payData, $payFields, 'Customerof_id', (int) ($ipd->p_id ?? 0));
        $setIfExists($payData, $payFields, 'group_id', $medGroupId);
        $setIfExists($payData, $payFields, 'ipd_id', $ipdId);
        $setIfExists($payData, $payFields, 'med_type', 0);
        $setIfExists($payData, $payFields, 'credit_debit', $creditDebit);
        $setIfExists($payData, $payFields, 'amount', $amount);
        $setIfExists($payData, $payFields, 'payment_date', $paymentDate);
        $setIfExists($payData, $payFields, 'remark', $cashRemark);
        $setIfExists($payData, $payFields, 'update_by', $userLabel);
        $setIfExists($payData, $payFields, 'update_by_id', $userId);
        $setIfExists($payData, $payFields, 'pay_bank_id', $payTypeId);
        $setIfExists($payData, $payFields, 'card_tran_id', $cardTranId);

        $this->db->table('payment_history_medical')->insert($payData);
        $payId = (int) $this->db->insertID();

        if ($payId <= 0) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Unable to save payment']);
        }

        $this->refreshIpdGroupPaymentFields($ipdId, $medGroupId);

        return service('response')->setJSON([
            'update' => 1,
            'ipd_id' => $ipdId,
            'payid' => $payId,
            'pay_date' => $paymentDate,
        ]);

    }

    public function update_group_discount($ipdId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ipdId = (int) $ipdId;
        $discount = (float) $this->request->getPost('input_discount');

        if ($ipdId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return service('response')->setJSON(['update' => 0, 'msg_text' => 'Invalid IPD']);
        }

        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];

        $invoiceBuilder = $this->db->table('invoice_med_master')
            ->where('ipd_id', $ipdId);

        if (in_array('sale_return', $fields, true)) {
            $invoiceBuilder->where('sale_return', 0);
        }

        $invoice = $invoiceBuilder
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();

        if (! $invoice) {
            $invoice = $this->db->table('invoice_med_master')
                ->where('ipd_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRow();
        }

        if (! $invoice) {
            return service('response')->setJSON(['update' => 0, 'msg_text' => 'No invoice found']);
        }

        $invoiceId = (int) ($invoice->id ?? 0);
        $update = [];
        if (in_array('discount_amount', $fields, true)) {
            $update['discount_amount'] = $discount;
        }
        if (in_array('discount_remark', $fields, true)) {
            $update['discount_remark'] = 'IPD Group Discount';
        }

        if (empty($update)) {
            return service('response')->setJSON(['update' => 0, 'msg_text' => 'Discount fields not found']);
        }

        $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($update);

        $this->refreshInvoicePaymentFields($invoiceId, true);

        return service('response')->setJSON(['update' => 1, 'msg_text' => 'Update Discount Success']);
    }

    public function ipd_print($ipdId = 0, $mode = 'cash')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ipdId = (int) $ipdId;
        $mode = strtolower(trim((string) $mode));
        if ($ipdId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return view('medical/placeholder', ['title' => 'Invalid IPD']);
        }

        $allowedModes = ['cash', 'cash-return', 'credit', 'package', 'med-list', 'med-list-date', 'pagewise', 'return-list'];
        if (! in_array($mode, $allowedModes, true)) {
            $mode = 'cash';
        }

        $invoiceFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $builder = $this->db->table('invoice_med_master')->where('ipd_id', $ipdId);

        if ($mode === 'cash') {
            if (in_array('ipd_credit', $invoiceFields, true)) {
                $builder->where('ipd_credit', 0);
            }
            if (in_array('sale_return', $invoiceFields, true)) {
                $builder->where('sale_return', 0);
            }
        } elseif ($mode === 'credit') {
            if (in_array('ipd_credit', $invoiceFields, true)) {
                $builder->where('ipd_credit', 1);
            }
            if (in_array('ipd_credit_type', $invoiceFields, true)) {
                $builder->where('ipd_credit_type', 1);
            }
        } elseif ($mode === 'package') {
            if (in_array('ipd_credit', $invoiceFields, true)) {
                $builder->where('ipd_credit', 1);
            }
            if (in_array('ipd_credit_type', $invoiceFields, true)) {
                $builder->where('ipd_credit_type', 0);
            }
        } elseif ($mode === 'cash-return') {
            if (in_array('ipd_credit', $invoiceFields, true)) {
                $builder->where('ipd_credit', 0);
            }
        }

        $invoices = $builder->orderBy('id', 'ASC')->get()->getResult();
        $invoiceIds = array_values(array_filter(array_map(static fn($r) => (int) ($r->id ?? 0), $invoices)));

        $items = [];
        if (! empty($invoiceIds) && $this->db->tableExists('inv_med_item')) {
            $itemBuilder = $this->db->table('inv_med_item')->whereIn('inv_med_id', $invoiceIds);
            if ($mode === 'return-list') {
                $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
                $itemBuilder->groupStart();
                if (in_array('sale_return', $itemFields, true)) {
                    $itemBuilder->orWhere('sale_return', 1);
                }
                $itemBuilder->orWhere('amount <', 0);
                $itemBuilder->groupEnd();
            } elseif ($mode === 'med-list' || $mode === 'med-list-date') {
                $itemBuilder->where('amount >=', 0);
            }
            $items = $itemBuilder->orderBy('id', 'ASC')->get()->getResult();
        }

        $itemsByInvoice = [];
        $invoiceItemTotals = [];
        foreach ($items as $itemRow) {
            $invId = (int) ($itemRow->inv_med_id ?? 0);
            if (! isset($itemsByInvoice[$invId])) {
                $itemsByInvoice[$invId] = [];
            }
            $itemsByInvoice[$invId][] = $itemRow;

            if (! isset($invoiceItemTotals[$invId])) {
                $invoiceItemTotals[$invId] = [
                    'qty' => 0.0,
                    'gross' => 0.0,
                    'discount' => 0.0,
                    'gst' => 0.0,
                    'net' => 0.0,
                ];
            }

            $gross = (float) ($itemRow->amount ?? 0);
            $discount = (float) (($itemRow->disc_amount ?? 0) + ($itemRow->disc_whole ?? 0));
            if ($discount == 0.0) {
                $discount = (float) ($itemRow->twdisc_amount ?? 0) > 0
                    ? ($gross - (float) ($itemRow->twdisc_amount ?? 0))
                    : 0.0;
            }
            $net = (float) ($itemRow->twdisc_amount ?? ($itemRow->tamount ?? $gross));

            $cgstPer = (float) ($itemRow->CGST_per ?? 0);
            $sgstPer = (float) ($itemRow->SGST_per ?? 0);
            $gstPer = $cgstPer + $sgstPer;
            $cgst = (float) ($itemRow->CGST ?? 0);
            $sgst = (float) ($itemRow->SGST ?? 0);

            if (($cgst + $sgst) <= 0.0 && $gstPer > 0.0 && $net > 0.0) {
                $taxableFromInclusive = $net * 100 / (100 + $gstPer);
                $taxTotal = $net - $taxableFromInclusive;
                $cgst = $taxTotal * ($cgstPer / $gstPer);
                $sgst = $taxTotal * ($sgstPer / $gstPer);
            }

            $invoiceItemTotals[$invId]['qty'] += (float) ($itemRow->qty ?? 0);
            $invoiceItemTotals[$invId]['gross'] += $gross;
            $invoiceItemTotals[$invId]['discount'] += $discount;
            $invoiceItemTotals[$invId]['gst'] += ($cgst + $sgst);
            $invoiceItemTotals[$invId]['net'] += $net;
        }

        $invoiceTotals = [
            'gross' => 0.0,
            'discount' => 0.0,
            'net' => 0.0,
            'balance' => 0.0,
        ];
        foreach ($invoices as $invoiceRow) {
            $invoiceTotals['gross'] += (float) ($invoiceRow->gross_amount ?? 0);
            $invoiceTotals['discount'] += (float) (($invoiceRow->disc_amount ?? 0) + ($invoiceRow->discount_amount ?? 0));
            $invoiceTotals['net'] += (float) ($invoiceRow->net_amount ?? 0);
            $invoiceTotals['balance'] += (float) ($invoiceRow->payment_balance ?? 0);
        }

        $itemTotals = [
            'qty' => 0.0,
            'amount' => 0.0,
        ];
        foreach ($items as $itemRow) {
            $itemTotals['qty'] += (float) ($itemRow->qty ?? 0);
            $itemTotals['amount'] += (float) ($itemRow->tamount ?? 0);
        }

        $itemsByDate = [];
        if ($mode === 'med-list-date' && ! empty($items)) {
            $invoiceDateMap = [];
            foreach ($invoices as $invoiceRow) {
                $invId = (int) ($invoiceRow->id ?? 0);
                $invoiceDateMap[$invId] = !empty($invoiceRow->inv_date)
                    ? date('Y-m-d', strtotime((string) $invoiceRow->inv_date))
                    : '-';
            }

            foreach ($items as $itemRow) {
                $invId = (int) ($itemRow->inv_med_id ?? 0);
                $dateKey = $invoiceDateMap[$invId] ?? '-';
                if (! isset($itemsByDate[$dateKey])) {
                    $itemsByDate[$dateKey] = [];
                }
                $itemsByDate[$dateKey][] = $itemRow;
            }
            ksort($itemsByDate);
        }

        $ipd = null;
        if ($this->db->tableExists('ipd_master')) {
            $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get()->getRow();
        }

        $patient = null;
        $patientId = (int) ($ipd->p_id ?? ($ipd->patient_id ?? 0));
        $patientTable = $this->db->tableExists('patient_master_exten')
            ? 'patient_master_exten'
            : ($this->db->tableExists('patient_master') ? 'patient_master' : null);
        if ($patientId > 0 && $patientTable) {
            $patient = $this->db->table($patientTable)->where('id', $patientId)->get()->getRow();
        }

        $orgCase = null;
        $orgId = (int) ($ipd->org_id ?? 0);
        if ($orgId > 0 && $this->db->tableExists('organization_case_master')) {
            $orgCase = $this->db->table('organization_case_master')->where('id', $orgId)->get()->getRow();
        }

        $paymentHistory = [];
        if ($this->db->tableExists('payment_history_medical') && ! empty($invoiceIds)) {
            $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
            $payBuilder = $this->db->table('payment_history_medical');

            if (in_array('ipd_id', $payFields, true)) {
                $payBuilder->where('ipd_id', $ipdId);
            } elseif (in_array('Medical_invoice_id', $payFields, true)) {
                $payBuilder->whereIn('Medical_invoice_id', $invoiceIds);
            } else {
                $payBuilder->where('1=0', null, false);
            }

            if (in_array('credit_debit', $payFields, true)) {
                $payBuilder->select('*, if(credit_debit>0, amount*-1, amount) as paid_amount', false);
            } else {
                $payBuilder->select('*, amount as paid_amount', false);
            }

            $paymentHistory = $payBuilder->orderBy('id', 'ASC')->get()->getResult();
        }

        $paymentSummary = [
            'total_received' => 0.0,
            'balance' => (float) ($invoiceTotals['net'] ?? 0),
        ];
        if (! empty($paymentHistory)) {
            foreach ($paymentHistory as $paymentRow) {
                $paymentSummary['total_received'] += (float) ($paymentRow->paid_amount ?? $paymentRow->amount ?? 0);
            }
            $paymentSummary['balance'] = (float) ($invoiceTotals['net'] ?? 0) - (float) $paymentSummary['total_received'];
            $invoiceTotals['balance'] = (float) $paymentSummary['balance'];
        }

        $viewData = [
            'ipdId' => $ipdId,
            'ipd' => $ipd,
            'patient' => $patient,
            'orgCase' => $orgCase,
            'mode' => $mode,
            'invoices' => $invoices,
            'items' => $items,
            'itemsByInvoice' => $itemsByInvoice,
            'invoiceItemTotals' => $invoiceItemTotals,
            'invoiceTotals' => $invoiceTotals,
            'itemTotals' => $itemTotals,
            'itemsByDate' => $itemsByDate,
            'paymentHistory' => $paymentHistory,
            'paymentSummary' => $paymentSummary,
            'isPdf' => true,
        ];

        $html = view('medical/ipd_print_report', $viewData);

        $modeTitleMap = [
            'cash' => 'Cash',
            'cash-return' => 'Cash Return',
            'credit' => 'IPD Credit',
            'package' => 'IPD Package',
            'med-list' => 'Medicine List',
            'med-list-date' => 'Medicine List Datewise',
            'pagewise' => 'Page Wise',
            'return-list' => 'Return List',
        ];

        $isLandscape = in_array($mode, ['med-list', 'med-list-date', 'pagewise'], true);
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => $isLandscape ? 'L' : 'P',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $ipdCode = (string) ($ipd->ipd_code ?? ('IPD-' . $ipdId));
        $modeTitle = $modeTitleMap[$mode] ?? 'IPD Print';
        $mpdf->SetTitle('Medical ' . $modeTitle . ' ' . $ipdCode);
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('Medical_' . str_replace(' ', '_', $modeTitle) . '_' . $ipdCode . '.pdf', 'S'));
    }

    public function open_invoice_edit($invoiceId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=all'));
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=all'));
        }

        $reason = null;
        if (! $this->canEditInvoiceRecord((array) $invoice, $reason)) {
            return redirect()->to(base_url('Medical/final_invoice/' . $invoiceId . '?msg=' . urlencode((string) $reason)));
        }

        $this->reopenInvoiceForEdit($invoiceId);

        return redirect()->to(base_url('Medical/invoice_edit/' . $invoiceId . '?msg=' . urlencode('Invoice opened for edit')));
    }

    public function confirm_payment()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        $mode = (int) $this->request->getPost('mode');
        $inputAmountPaid = (float) $this->request->getPost('input_amount_paid');
        $cardTranId = trim((string) $this->request->getPost('input_card_tran'));
        $payTypeId = (int) $this->request->getPost('cbo_pay_type');

        if ($invoiceId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Invalid invoice']);
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Invoice not found']);
        }

        if (! in_array($mode, [1, 2, 5], true)) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Unsupported payment mode']);
        }

        if ($mode === 2 && strlen($cardTranId) < 3) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Card Transaction ID is required']);
        }

        if ($mode === 5 && $inputAmountPaid < 0) {
            $inputAmountPaid *= -1;
        }

        if ($inputAmountPaid <= 0) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Received amount must be greater than 0']);
        }

        $historyPaymentMode = ($mode === 5) ? 1 : $mode;
        $creditDebit = ($mode === 5) ? 1 : 0;
        $paymentDesc = $mode === 2 ? 'Bank Card' : ($mode === 5 ? 'Cash Return' : 'Cash');

        $user = service('auth')->user();
        $userId = (int) ($user->id ?? 0);
        $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';
        $userLabel = trim($userName . '[' . $userId . ']');

        $paymentId = 0;
        if ($this->db->tableExists('payment_history_medical')) {
            $historyFields = $this->db->getFieldNames('payment_history_medical') ?? [];
            $paymentData = [];

            $setIfExists = static function (array &$target, array $fields, string $key, $value): void {
                if (in_array($key, $fields, true)) {
                    $target[$key] = $value;
                }
            };

            $setIfExists($paymentData, $historyFields, 'payment_mode', $historyPaymentMode);
            $setIfExists($paymentData, $historyFields, 'Customerof_type', !empty($invoice->patient_id) ? 1 : 3);
            $setIfExists($paymentData, $historyFields, 'Customerof_id', (int) ($invoice->patient_id ?? 0));
            $setIfExists($paymentData, $historyFields, 'Customerof_code', (string) ($invoice->patient_code ?? ''));
            $setIfExists($paymentData, $historyFields, 'Medical_invoice_id', $invoiceId);
            $setIfExists($paymentData, $historyFields, 'Medical_invoice_code', (string) ($invoice->inv_med_code ?? ''));
            $setIfExists($paymentData, $historyFields, 'credit_debit', $creditDebit);
            $setIfExists($paymentData, $historyFields, 'amount', $inputAmountPaid);
            $setIfExists($paymentData, $historyFields, 'payment_date', date('Y-m-d H:i:s'));
            $setIfExists($paymentData, $historyFields, 'remark', '');
            $setIfExists($paymentData, $historyFields, 'update_by', $userLabel);
            $setIfExists($paymentData, $historyFields, 'update_by_id', $userId);
            $setIfExists($paymentData, $historyFields, 'pay_bank_id', $payTypeId);
            $setIfExists($paymentData, $historyFields, 'card_tran_id', $cardTranId);

            if (! empty($paymentData)) {
                $this->db->table('payment_history_medical')->insert($paymentData);
                $paymentId = (int) $this->db->insertID();
            }
        }

        $invoiceFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $invoiceUpdate = [];

        $setIfExists = static function (array &$target, array $fields, string $key, $value): void {
            if (in_array($key, $fields, true)) {
                $target[$key] = $value;
            }
        };

        $setIfExists($invoiceUpdate, $invoiceFields, 'payment_mode', $historyPaymentMode);
        $setIfExists($invoiceUpdate, $invoiceFields, 'payment_status', 1);
        $setIfExists($invoiceUpdate, $invoiceFields, 'invoice_status', 1);
        if ($paymentId > 0) {
            $setIfExists($invoiceUpdate, $invoiceFields, 'payment_id', $paymentId);
        }
        $setIfExists($invoiceUpdate, $invoiceFields, 'payment_mode_desc', $paymentDesc);
        $setIfExists($invoiceUpdate, $invoiceFields, 'confirm_invoice', date('Y-m-d H:i:s'));
        $setIfExists($invoiceUpdate, $invoiceFields, 'prepared_by', $userLabel);
        $setIfExists($invoiceUpdate, $invoiceFields, 'prepared_by_id', $userId);
        $setIfExists($invoiceUpdate, $invoiceFields, 'card_tran_id', $cardTranId);

        if (! empty($invoiceUpdate)) {
            $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($invoiceUpdate);
        }

        $this->refreshInvoicePaymentFields($invoiceId, true);

        return service('response')->setJSON([
            'update' => 1,
            'msg_text' => 'Payment updated',
        ]);
    }

    public function update_discount()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $this->request->getPost('med_invoice_id');
        if ($invoiceId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Invalid invoice']);
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return service('response')->setJSON(['update' => 0, 'error_text' => 'Invoice not found']);
        }

        $discountAmount = (float) $this->request->getPost('input_dis_amt');
        $discountRemark = trim((string) $this->request->getPost('input_dis_desc'));

        $user = service('auth')->user();
        $userId = (int) ($user->id ?? 0);
        $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';
        $userLabel = trim($userName . '[' . $userId . ']');

        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $update = [];

        $setIfExists = static function (array &$target, array $fieldList, string $key, $value): void {
            if (in_array($key, $fieldList, true)) {
                $target[$key] = $value;
            }
        };

        $setIfExists($update, $fields, 'discount_amount', $discountAmount);
        $setIfExists($update, $fields, 'discount_remark', $discountRemark);
        $setIfExists($update, $fields, 'discount_by', $userLabel);

        if (! empty($update)) {
            $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($update);
        }

        $this->refreshInvoicePaymentFields($invoiceId, true);

        return service('response')->setJSON([
            'update' => 1,
            'msg_text' => 'Deduction updated',
        ]);
    }

    public function invoice_print($invoiceId, $printFormat = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0 || ! $this->db->tableExists('invoice_med_master')) {
            return view('medical/placeholder', ['title' => 'Invalid invoice']);
        }

        $this->recalculateInvoiceTotals($invoiceId);

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return view('medical/placeholder', ['title' => 'Invoice not found']);
        }

        $items = [];
        if ($this->db->tableExists('inv_med_item')) {
            $items = $this->db->table('inv_med_item')
                ->where('inv_med_id', $invoiceId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResult();
        }

        $patient = null;
        if (! empty($invoice->patient_id) && $this->db->tableExists('patient_master_exten')) {
            $patient = $this->db->table('patient_master_exten')->where('id', (int) $invoice->patient_id)->get()->getRow();
        } elseif (! empty($invoice->patient_id) && $this->db->tableExists('patient_master')) {
            $patient = $this->db->table('patient_master')->where('id', (int) $invoice->patient_id)->get()->getRow();
        }

        $format = (int) $printFormat;
        $mpdfConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => WRITEPATH . 'cache',
        ];

        if ($format === 1) {
            $mpdfConfig['format'] = 'A6';
            $mpdfConfig['orientation'] = 'P';
            $mpdfConfig['margin_left'] = 5;
            $mpdfConfig['margin_right'] = 5;
            $mpdfConfig['margin_top'] = 5;
            $mpdfConfig['margin_bottom'] = 5;
        } elseif ($format === 2) {
            $mpdfConfig['format'] = 'A5';
            $mpdfConfig['orientation'] = 'P';
            $mpdfConfig['margin_left'] = 6;
            $mpdfConfig['margin_right'] = 6;
            $mpdfConfig['margin_top'] = 6;
            $mpdfConfig['margin_bottom'] = 6;
        } elseif ($format === 3) {
            $mpdfConfig['format'] = 'A5';
            $mpdfConfig['orientation'] = 'L';
            $mpdfConfig['margin_left'] = 6;
            $mpdfConfig['margin_right'] = 6;
            $mpdfConfig['margin_top'] = 6;
            $mpdfConfig['margin_bottom'] = 6;
        }

        $html = view('medical/invoice_print_pdf', [
            'invoice' => $invoice,
            'items' => $items,
            'patient' => $patient,
            'printFormat' => $format,
        ]);

        $invoiceCode = (string) ($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT)));
        $mpdf = new Mpdf($mpdfConfig);
        $mpdf->SetTitle('Medical Invoice ' . $invoiceCode);
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('Medical_Invoice_' . $invoiceCode . '.pdf', 'S'));
    }

    private function recalculateInvoiceTotals(int $invoiceId): void
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('inv_med_item')) {
            return;
        }

        $sum = $this->db->table('inv_med_item')
            ->select('ifnull(sum(amount),0) as gross, ifnull(sum(disc_amount),0) as discount, ifnull(sum(tamount),0) as net')
            ->where('inv_med_id', $invoiceId)
            ->get()
            ->getRow();

        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $update = [];

        if (in_array('gross_amount', $fields, true)) {
            $update['gross_amount'] = (float) ($sum->gross ?? 0);
        }
        if (in_array('disc_amount', $fields, true)) {
            $update['disc_amount'] = (float) ($sum->discount ?? 0);
        }
        if (in_array('net_amount', $fields, true)) {
            $update['net_amount'] = (float) ($sum->net ?? 0);
        }

        if (! empty($update)) {
            $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($update);
        }
    }

    private function refreshInvoicePaymentFields(int $invoiceId, bool $rebuildFromItems = false): void
    {
        if (! $this->db->tableExists('invoice_med_master')) {
            return;
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return;
        }

        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $update = [];

        if ($rebuildFromItems && $this->db->tableExists('inv_med_item')) {
            $sum = $this->db->table('inv_med_item')
                ->select('ifnull(sum(amount),0) as gross, ifnull(sum(disc_amount),0) as item_disc, ifnull(sum(tamount),0) as net_items')
                ->where('inv_med_id', $invoiceId)
                ->get()
                ->getRow();

            $gross = (float) ($sum->gross ?? 0);
            $itemDisc = (float) ($sum->item_disc ?? 0);
            $netItems = (float) ($sum->net_items ?? 0);
            $extraDisc = (float) ($invoice->discount_amount ?? 0);
            $net = $netItems - $extraDisc;

            if (in_array('gross_amount', $fields, true)) {
                $update['gross_amount'] = $gross;
            }
            if (in_array('item_discount_amount', $fields, true)) {
                $update['item_discount_amount'] = $itemDisc;
            }
            if (in_array('net_amount', $fields, true)) {
                $update['net_amount'] = $net;
            }
        }

        $paid = 0.0;
        if ($this->db->tableExists('payment_history_medical')) {
            $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
            $builder = $this->db->table('payment_history_medical');
            if (in_array('Medical_invoice_id', $payFields, true)) {
                $builder->where('Medical_invoice_id', $invoiceId);
            } else {
                $builder->where('1=0', null, false);
            }

            if (in_array('credit_debit', $payFields, true)) {
                $builder->select('ifnull(sum(case when credit_debit>0 then amount*-1 else amount end),0) as paid', false);
            } else {
                $builder->select('ifnull(sum(amount),0) as paid', false);
            }
            $row = $builder->get()->getRow();
            $paid = (float) ($row->paid ?? 0);
        }

        $netAmount = array_key_exists('net_amount', $update)
            ? (float) $update['net_amount']
            : (float) ($invoice->net_amount ?? 0);
        $balance = $netAmount - $paid;

        if (in_array('payment_received', $fields, true)) {
            $update['payment_received'] = $paid;
        }
        if (in_array('payment_balance', $fields, true)) {
            $update['payment_balance'] = $balance;
        }

        if (! empty($update)) {
            $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($update);
        }
    }

    private function getInvoicePaymentSummary(object $invoice): array
    {
        $net = (float) ($invoice->net_amount ?? 0);
        $paid = (float) ($invoice->payment_received ?? 0);
        $balance = (float) ($invoice->payment_balance ?? ($net - $paid));

        return [
            'net' => $net,
            'paid' => $paid,
            'balance' => $balance,
            'extra_discount' => (float) ($invoice->discount_amount ?? 0),
            'discount_remark' => (string) ($invoice->discount_remark ?? ''),
        ];
    }

    private function getInvoicePaymentHistory(int $invoiceId): array
    {
        if (! $this->db->tableExists('payment_history_medical')) {
            return [];
        }

        $fields = $this->db->getFieldNames('payment_history_medical') ?? [];
        if (! in_array('Medical_invoice_id', $fields, true)) {
            return [];
        }

        $builder = $this->db->table('payment_history_medical')
            ->where('Medical_invoice_id', $invoiceId)
            ->orderBy(in_array('id', $fields, true) ? 'id' : 'payment_date', 'DESC');

        if (in_array('credit_debit', $fields, true)) {
            $builder->select('*, if(credit_debit>0, amount*-1, amount) as paid_amount', false);
        } else {
            $builder->select('*, amount as paid_amount', false);
        }

        $rows = $builder->get()->getResult();
        foreach ($rows as $row) {
            $mode = (int) ($row->payment_mode ?? 0);
            $modeText = match ($mode) {
                1 => 'Cash',
                2 => 'Bank Card',
                3 => 'Return Cash',
                4 => 'Bank Return',
                5 => 'Cash Return',
                default => 'Other',
            };
            $row->Payment_type_str = $modeText;
        }

        return $rows;
    }

    private function isIpdDischargedInvoice(array $invoiceRow): bool
    {
        $ipdId = (int) ($invoiceRow['ipd_id'] ?? 0);
        if ($ipdId <= 0 || ! $this->db->tableExists('ipd_master')) {
            return false;
        }

        $ipdFields = $this->db->getFieldNames('ipd_master') ?? [];
        if ($ipdFields === []) {
            return false;
        }

        $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get()->getRowArray();
        if (! $ipd) {
            return false;
        }

        foreach (['ipd_status', 'discharge_status', 'discharg_status', 'is_discharged'] as $statusCol) {
            if (in_array($statusCol, $ipdFields, true) && (int) ($ipd[$statusCol] ?? 0) !== 0) {
                return true;
            }
        }

        foreach (['discharge_date', 'discharge_datetime', 'discharged_at'] as $dateCol) {
            if (! in_array($dateCol, $ipdFields, true)) {
                continue;
            }

            $value = trim((string) ($ipd[$dateCol] ?? ''));
            if ($value !== '' && $value !== '0000-00-00' && $value !== '0000-00-00 00:00:00') {
                return true;
            }
        }

        return false;
    }

    private function canEditInvoiceRecord(array $invoiceRow, ?string &$reason = null): bool
    {
        $reason = null;

        if ($this->isIpdDischargedInvoice($invoiceRow)) {
            $reason = 'IPD patient discharged: invoice is view-only.';
            return false;
        }

        $invDateRaw = trim((string) ($invoiceRow['inv_date'] ?? ''));
        $invDate = $invDateRaw !== '' ? date('Y-m-d', strtotime($invDateRaw)) : '';
        $isPastDate = $invDate !== '' && $invDate < date('Y-m-d');

        $customerType = array_key_exists('customer_type', $invoiceRow)
            ? (int) ($invoiceRow['customer_type'] ?? 0)
            : (((int) ($invoiceRow['patient_id'] ?? 0) > 0 || trim((string) ($invoiceRow['patient_code'] ?? '')) !== '') ? 1 : 0);

        $ipdId = (int) ($invoiceRow['ipd_id'] ?? 0);
        $caseId = (int) ($invoiceRow['case_id'] ?? 0);
        $isWalkInOrRegistered = $ipdId === 0 && $caseId === 0 && in_array($customerType, [0, 1], true);

        if ($isPastDate && $isWalkInOrRegistered && ! $this->canPharmacyPermission('pharmacy.invoice.edit-old')) {
            $reason = 'No permission to edit past Walk-in/Registered pharmacy invoice.';
            return false;
        }

        return true;
    }

    private function getMedicalBankSources(): array
    {
        if (! $this->db->tableExists('medical_bank_payment_source')) {
            return [];
        }

        $builder = $this->db->table('medical_bank_payment_source s')
            ->select('s.*');

        if ($this->db->tableExists('medical_bank')) {
            $builder
                ->select('ifnull(m.bank_name, "") as bank_name', false)
                ->join('medical_bank m', 'm.id=s.bank_id', 'left');
        } else {
            $builder->select('"" as bank_name', false);
        }

        return $builder->orderBy('s.id', 'ASC')->get()->getResult();
    }

    private function finalizeInvoiceRecord(int $invoiceId, object $invoice, array $input): void
    {
        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];
        if (empty($fields)) {
            return;
        }

        $setIfExists = static function (array &$target, array $fieldList, string $key, $value): void {
            if (in_array($key, $fieldList, true)) {
                $target[$key] = $value;
            }
        };

        $docId = (int) ($input['doc_id'] ?? ($invoice->doc_id ?? 0));
        $docName = trim((string) ($input['doc_name'] ?? ($invoice->doc_name ?? '')));
        $remarkIpd = trim((string) ($input['input_remark_ipd'] ?? ($invoice->remark_ipd ?? '')));
        $patientCode = trim((string) ($input['patient_code'] ?? ($invoice->patient_code ?? '')));
        $customerName = trim((string) ($input['custmer_Name'] ?? ($invoice->inv_name ?? '')));
        $ipdCredit = (int) ($input['ipd_credit'] ?? ($invoice->ipd_credit ?? 0));
        $orgCredit = (int) ($input['org_credit'] ?? ($invoice->case_credit ?? 0));
        $invDate = trim((string) ($input['inv_date'] ?? ($invoice->inv_date ?? '')));

        $update = [];

        if ($patientCode !== '' && $this->db->tableExists('patient_master')) {
            $personInfo = $this->db->table('patient_master')->where('p_code', $patientCode)->get()->getRow();
            if ($personInfo) {
                $setIfExists($update, $fields, 'patient_id', (int) ($personInfo->id ?? 0));
                $setIfExists($update, $fields, 'patient_code', (string) ($personInfo->p_code ?? $patientCode));
                if ($customerName === '') {
                    $customerName = (string) ($personInfo->p_fname ?? '');
                }
            }
        }

        if ($docId > 0 && $this->db->tableExists('doctor_master')) {
            $docInfo = $this->db->table('doctor_master')->where('id', $docId)->get()->getRow();
            if ($docInfo) {
                $docName = (string) ($docInfo->p_fname ?? $docName);
            }
        }

        $setIfExists($update, $fields, 'doc_id', $docId);
        $setIfExists($update, $fields, 'doc_name', $docName);
        $setIfExists($update, $fields, 'remark_ipd', $remarkIpd);
        $setIfExists($update, $fields, 'inv_name', $customerName);
        $setIfExists($update, $fields, 'ipd_credit', $ipdCredit);
        $setIfExists($update, $fields, 'case_credit', $orgCredit);

        if ($invDate !== '') {
            $setIfExists($update, $fields, 'inv_date', $invDate);
        }

        $setIfExists($update, $fields, 'confirm_invoice', date('Y-m-d H:i:s'));
        $setIfExists($update, $fields, 'invoice_status', 1);
        $setIfExists($update, $fields, 'status', 1);
        $setIfExists($update, $fields, 'is_final', 1);
        $setIfExists($update, $fields, 'final_status', 1);
        $setIfExists($update, $fields, 'is_draft', 0);
        $setIfExists($update, $fields, 'draft_status', 0);
        $setIfExists($update, $fields, 'bill_final', 1);

        if (! empty($update)) {
            $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($update);
        }
    }

    private function reopenInvoiceForEdit(int $invoiceId): void
    {
        if (! $this->db->tableExists('invoice_med_master')) {
            return;
        }

        $invoiceRow = $this->db->table('invoice_med_master')
            ->select('id,inv_med_code,inv_date,inv_name,patient_id,patient_code,customer_type')
            ->where('id', $invoiceId)
            ->get()
            ->getRowArray();

        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];
        if (empty($fields)) {
            return;
        }

        $update = [];
        $setIfExists = static function (array &$target, array $fieldList, string $key, $value): void {
            if (in_array($key, $fieldList, true)) {
                $target[$key] = $value;
            }
        };

        $setIfExists($update, $fields, 'final_status', 0);
        $setIfExists($update, $fields, 'is_final', 0);
        $setIfExists($update, $fields, 'bill_final', 0);
        $setIfExists($update, $fields, 'is_draft', 1);
        $setIfExists($update, $fields, 'draft_status', 1);
        $setIfExists($update, $fields, 'invoice_status', 0);
        $setIfExists($update, $fields, 'status', 0);
        $setIfExists($update, $fields, 'payment_status', 0);
        $setIfExists($update, $fields, 'confirm_invoice', null);

        if (! empty($update)) {
            $this->db->table('invoice_med_master')->where('id', $invoiceId)->update($update);
        }

        if ($invoiceRow) {
            $invDateRaw = (string) ($invoiceRow['inv_date'] ?? '');
            $invDate = $invDateRaw !== '' ? date('Y-m-d', strtotime($invDateRaw)) : '';
            $isPastDate = $invDate !== '' && $invDate < date('Y-m-d');

            $customerType = array_key_exists('customer_type', $invoiceRow)
                ? (int) ($invoiceRow['customer_type'] ?? 0)
                : (((int) ($invoiceRow['patient_id'] ?? 0) > 0 || trim((string) ($invoiceRow['patient_code'] ?? '')) !== '') ? 1 : 0);

            $isWalkInOrRegistered = in_array($customerType, [0, 1], true);

            if ($isPastDate && $isWalkInOrRegistered) {
                $invoiceCode = (string) ($invoiceRow['inv_med_code'] ?? ('M' . $invoiceId));
                $invoiceName = trim((string) ($invoiceRow['inv_name'] ?? ''));
                $typeLabel = $customerType === 0 ? 'Walk-in' : 'Registered';

                $summary = 'Invoice Opened for Edit: ' . $typeLabel
                    . ' invoice ' . $invoiceCode
                    . ' dated ' . $invDate
                    . ($invoiceName !== '' ? (' [' . $invoiceName . ']') : '');

                $this->appendInvoiceMasterLog($invoiceId, $summary);
                $this->writeMedicalAdminActionLog('pharmacy_invoice_open_edit', $summary, [
                    'invoice_id' => $invoiceId,
                    'invoice_code' => $invoiceCode,
                    'invoice_date' => $invDate,
                    'invoice_type' => $typeLabel,
                    'invoice_name' => $invoiceName,
                ]);
            }
        }
    }

    private function redirectIpdToDraftList(int $ipdId, string $message = '')
    {
        if ($ipdId <= 0) {
            return redirect()->to(base_url('Medical/Invoice_Med_Draft?status=all'));
        }

        $ipdCode = '';
        if ($this->db->tableExists('ipd_master')) {
            $ipd = $this->db->table('ipd_master')->select('ipd_code')->where('id', $ipdId)->get()->getRow();
            $ipdCode = trim((string) ($ipd->ipd_code ?? ''));
        }

        $url = 'Medical/Invoice_Med_Draft?status=all';
        if ($ipdCode !== '') {
            $url .= '&q=' . rawurlencode($ipdCode);
        }
        if ($message !== '') {
            $url .= '&msg=' . urlencode($message);
        }

        return redirect()->to(base_url($url));
    }

    private function buildIpdReturnPageData(int $ipdId): ?array
    {
        if ($ipdId <= 0 || ! $this->db->tableExists('ipd_master') || ! $this->db->tableExists('invoice_med_master')) {
            return null;
        }

        $ipd = $this->db->table('ipd_master')->where('id', $ipdId)->get()->getRow();
        if (! $ipd) {
            return null;
        }

        $patientId = (int) ($ipd->p_id ?? ($ipd->patient_id ?? 0));
        $patientTable = $this->db->tableExists('patient_master_exten')
            ? 'patient_master_exten'
            : ($this->db->tableExists('patient_master') ? 'patient_master' : null);

        $patient = null;
        if ($patientId > 0 && $patientTable) {
            $patient = $this->db->table($patientTable)->where('id', $patientId)->get()->getRow();
        }

        $invoiceFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $builder = $this->db->table('invoice_med_master')->where('ipd_id', $ipdId);
        if (in_array('sale_return', $invoiceFields, true)) {
            $builder->where('sale_return', 0);
        }

        $invoices = $builder->orderBy('id', 'DESC')->get()->getResult();

        $invoiceIds = array_values(array_filter(array_map(static fn($row) => (int) ($row->id ?? 0), $invoices)));
        $itemCountMap = [];
        $returnCountMap = [];

        if (! empty($invoiceIds) && $this->db->tableExists('inv_med_item')) {
            $itemRows = $this->db->table('inv_med_item')
                ->select('inv_med_id, count(*) as c')
                ->whereIn('inv_med_id', $invoiceIds)
                ->groupBy('inv_med_id')
                ->get()
                ->getResult();

            foreach ($itemRows as $r) {
                $itemCountMap[(int) ($r->inv_med_id ?? 0)] = (int) ($r->c ?? 0);
            }

            $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
            $returnBuilder = $this->db->table('inv_med_item')
                ->select('inv_med_id, count(*) as c')
                ->whereIn('inv_med_id', $invoiceIds);

            $returnBuilder->groupStart();
            if (in_array('sale_return', $itemFields, true)) {
                $returnBuilder->orWhere('sale_return', 1);
            }
            $returnBuilder->orWhere('amount <', 0);
            $returnBuilder->groupEnd();

            $returnRows = $returnBuilder->groupBy('inv_med_id')->get()->getResult();
            foreach ($returnRows as $r) {
                $returnCountMap[(int) ($r->inv_med_id ?? 0)] = (int) ($r->c ?? 0);
            }
        }

        return [
            'ipd' => $ipd,
            'patient' => $patient,
            'invoices' => $invoices,
            'itemCountMap' => $itemCountMap,
            'returnCountMap' => $returnCountMap,
        ];
    }

    private function isInvoiceFinalized(object $invoice): bool
    {
        $isTrue = static function (object $row, string $key): bool {
            return property_exists($row, $key) && (int) ($row->{$key} ?? 0) === 1;
        };

        if ($isTrue($invoice, 'final_status') || $isTrue($invoice, 'is_final') || $isTrue($invoice, 'bill_final')) {
            return true;
        }

        if (property_exists($invoice, 'is_draft') && (int) ($invoice->is_draft ?? 1) === 0) {
            return true;
        }
        if (property_exists($invoice, 'draft_status') && (int) ($invoice->draft_status ?? 1) === 0) {
            return true;
        }

        if (property_exists($invoice, 'confirm_invoice')) {
            $confirm = trim((string) ($invoice->confirm_invoice ?? ''));
            if ($confirm !== '' && $confirm !== '0000-00-00 00:00:00' && $confirm !== '0000-00-00') {
                return true;
            }
        }

        return false;
    }

    private function buildInvoiceEditData(int $invoiceId, string $query = ''): ?array
    {
        if (! $this->db->tableExists('invoice_med_master')) {
            return null;
        }

        $invoice = $this->db->table('invoice_med_master')->where('id', $invoiceId)->get()->getRow();
        if (! $invoice) {
            return null;
        }

        $items = [];
        if ($this->db->tableExists('inv_med_item')) {
            $itemBuilder = $this->db->table('inv_med_item i')
                ->select('i.*')
                ->where('i.inv_med_id', $invoiceId)
                ->orderBy('i.id', 'DESC');

            if ($this->db->tableExists('purchase_invoice_item')) {
                $itemBuilder->select("(
                    ifnull(
                        (
                            select (ifnull(t.total_unit,0)-ifnull(t.total_sale_unit,0))
                            from purchase_invoice_item t
                            where t.id = i.store_stock_id
                            limit 1
                        ),
                        ifnull(
                            (
                                select (ifnull(t2.total_unit,0)-ifnull(t2.total_sale_unit,0))
                                from purchase_invoice_item t2
                                where t2.item_code = i.item_code
                                  and t2.batch_no = i.batch_no
                                order by t2.id desc
                                limit 1
                            ),
                            0
                        )
                    )
                ) AS stock_qty", false);
            } else {
                $itemBuilder->select('0 AS stock_qty', false);
            }

            $items = $itemBuilder->get()->getResult();
        }

        $stockRows = [];
        if ($query !== '' && $this->db->tableExists('med_product_master') && $this->db->tableExists('purchase_invoice_item')) {
            $stockWhere = "(p.item_name like ? or p.genericname like ?)";
            $params = ['%' . $query . '%', '%' . $query . '%'];

            $stockSql = "SELECT t.id AS stock_id,p.id AS item_code,p.item_name,p.formulation,t.batch_no,t.expiry_date,t.mrp,t.selling_unit_rate,t.packing,
                            (ifnull(t.total_unit,0)-ifnull(t.total_sale_unit,0)-ifnull(t.total_return_unit,0)-ifnull(t.total_lost_unit,0)) AS current_qty
                        FROM med_product_master p
                        JOIN purchase_invoice_item t ON p.id=t.item_code
                        WHERE {$stockWhere} AND (ifnull(t.total_unit,0)-ifnull(t.total_sale_unit,0)-ifnull(t.total_return_unit,0)-ifnull(t.total_lost_unit,0)) > 0
                        ORDER BY p.item_name, t.id DESC
                        LIMIT 50";
            $stockRows = $this->db->query($stockSql, $params)->getResult();
        }

        $totals = (object) [
            'gross' => 0,
            'discount' => 0,
            'net' => 0,
        ];

        if ($this->db->tableExists('inv_med_item')) {
            $row = $this->db->table('inv_med_item')
                ->select('ifnull(sum(amount),0) as gross, ifnull(sum(disc_amount),0) as discount, ifnull(sum(tamount),0) as net')
                ->where('inv_med_id', $invoiceId)
                ->get()
                ->getRow();
            if ($row) {
                $totals = $row;
            }
        }

        $oldInvoices = [];
        if ($this->db->tableExists('invoice_med_master') && !empty($invoice->patient_id) && (int)($invoice->patient_id ?? 0) > 0) {
            $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];
            $oldBuilder = $this->db->table('invoice_med_master')
                ->where('patient_id', (int) $invoice->patient_id)
                ->where('id <>', (int) $invoiceId);

            if (in_array('sale_return', $masterFields, true)) {
                $oldBuilder->where('sale_return', 0);
            }

            $oldInvoices = $oldBuilder
                ->orderBy('id', 'DESC')
                ->limit(15)
                ->get()
                ->getResult();

            if (! empty($oldInvoices) && $this->db->tableExists('inv_med_item')) {
                $oldIds = array_map(static fn($r) => (int) ($r->id ?? 0), $oldInvoices);
                if (! empty($oldIds)) {
                    $oldItems = $this->db->table('inv_med_item')
                        ->whereIn('inv_med_id', $oldIds)
                        ->orderBy('id', 'DESC')
                        ->get()
                        ->getResult();

                    $itemsGrouped = [];
                    foreach ($oldItems as $oldItem) {
                        $invRef = (int) ($oldItem->inv_med_id ?? 0);
                        if (! isset($itemsGrouped[$invRef])) {
                            $itemsGrouped[$invRef] = [];
                        }
                        if (count($itemsGrouped[$invRef]) < 8) {
                            $itemsGrouped[$invRef][] = $oldItem;
                        }
                    }

                    foreach ($oldInvoices as $oldInvoice) {
                        $oid = (int) ($oldInvoice->id ?? 0);
                        $oldInvoice->_items = $itemsGrouped[$oid] ?? [];
                    }
                }
            }
        }

        return [
            'invoice' => $invoice,
            'items' => $items,
            'stockRows' => $stockRows,
            'query' => $query,
            'totals' => $totals,
            'oldInvoices' => $oldInvoices,
            'isFinalized' => $this->isInvoiceFinalized($invoice),
            'message' => (string) $this->request->getGet('msg'),
        ];
    }

    public function Invoice_Med_Draft(string $defaultStatus = 'draft')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $status = strtolower(trim((string) $this->request->getGet('status')));
        if (! in_array($status, ['draft', 'final', 'all'], true)) {
            $status = in_array($defaultStatus, ['draft', 'final', 'all'], true) ? $defaultStatus : 'draft';
        }

        $fromDate = $this->normalizeDate((string) $this->request->getGet('from'));
        $toDate = $this->normalizeDate((string) $this->request->getGet('to'));
        $search = trim((string) $this->request->getGet('q'));
        $caseId = (int) $this->request->getGet('case_id');

        $message = (string) $this->request->getGet('msg');

        return view('medical/invoice_med_draft', [
            'status' => $status,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'search' => $search,
            'caseId' => $caseId,
            'message' => $message,
        ]);
    }

    public function getInvoiceTable()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return $this->response->setJSON([
                'draw' => (int) ($this->request->getVar('draw') ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $fields = $this->db->getFieldNames('invoice_med_master') ?? [];

        $status = strtolower(trim((string) ($this->request->getVar('status') ?? 'draft')));
        if (! in_array($status, ['draft', 'final', 'all'], true)) {
            $status = 'draft';
        }

        $fromDate = $this->normalizeDate((string) ($this->request->getVar('from') ?? ''));
        $toDate = $this->normalizeDate((string) ($this->request->getVar('to') ?? ''));
        $quickSearch = trim((string) ($this->request->getVar('q') ?? ''));
        $caseId = (int) ($this->request->getVar('case_id') ?? 0);

        $columnsMap = [
            0 => 'm.inv_med_code',
            1 => 'm.inv_name',
            2 => 'm.patient_code',
            3 => (in_array('ipd_code', $fields, true) ? 'm.ipd_code' : (in_array('ipd_id', $fields, true) ? 'm.ipd_id' : 'm.id')),
            4 => (in_array('inv_date', $fields, true) ? 'm.inv_date' : 'm.id'),
            5 => (in_array('net_amount', $fields, true) ? 'm.net_amount' : 'm.id'),
            6 => (in_array('payment_received', $fields, true) ? 'm.payment_received' : 'm.id'),
            7 => (in_array('payment_balance', $fields, true) ? 'm.payment_balance' : 'm.id'),
        ];

        $baseBuilder = $this->db->table('invoice_med_master m');

        if (in_array('sale_return', $fields, true)) {
            $baseBuilder->where('m.sale_return', 0);
        }

        $this->applyInvoiceStatusFilter($baseBuilder, $fields, $status, 'm.');

        if ($caseId > 0 && in_array('case_id', $fields, true)) {
            $baseBuilder->where('m.case_id', $caseId);
        }

        if ($fromDate !== '' && in_array('inv_date', $fields, true)) {
            $baseBuilder->where('DATE(m.inv_date) >=', $fromDate, false);
        }
        if ($toDate !== '' && in_array('inv_date', $fields, true)) {
            $baseBuilder->where('DATE(m.inv_date) <=', $toDate, false);
        }

        if ($quickSearch !== '') {
            $quickCols = [];
            foreach (['inv_med_code', 'patient_code', 'inv_name', 'ipd_code'] as $col) {
                if (in_array($col, $fields, true)) {
                    $quickCols[] = 'm.' . $col;
                }
            }
            if (! empty($quickCols)) {
                $baseBuilder->groupStart();
                foreach ($quickCols as $index => $col) {
                    if ($index === 0) {
                        $baseBuilder->like($col, $quickSearch);
                    } else {
                        $baseBuilder->orLike($col, $quickSearch);
                    }
                }
                $baseBuilder->groupEnd();
            }
        }

        $totalBuilder = clone $baseBuilder;
        $totalRow = $totalBuilder->select('COUNT(*) AS c', false)->get()->getRow();
        $recordsTotal = (int) ($totalRow->c ?? 0);

        $requestColumns = $this->request->getVar('columns');
        if (is_array($requestColumns)) {
            $columnFilterMap = [
                0 => (in_array('inv_med_code', $fields, true) ? 'm.inv_med_code' : null),
                1 => (in_array('inv_name', $fields, true) ? 'm.inv_name' : null),
                2 => (in_array('patient_code', $fields, true) ? 'm.patient_code' : null),
                3 => (in_array('ipd_code', $fields, true) ? 'm.ipd_code' : (in_array('ipd_id', $fields, true) ? 'm.ipd_id' : null)),
            ];

            foreach ($columnFilterMap as $index => $dbCol) {
                if (! $dbCol || ! isset($requestColumns[$index]['search']['value'])) {
                    continue;
                }

                $value = trim((string) $requestColumns[$index]['search']['value']);
                if ($value === '') {
                    continue;
                }

                $baseBuilder->like($dbCol, $value);
            }
        }

        $filteredBuilder = clone $baseBuilder;
        $filteredRow = $filteredBuilder->select('COUNT(*) AS c', false)->get()->getRow();
        $recordsFiltered = (int) ($filteredRow->c ?? 0);

        $order = $this->request->getVar('order');
        $orderCol = 0;
        $orderDir = 'desc';
        if (is_array($order) && isset($order[0])) {
            $orderCol = (int) ($order[0]['column'] ?? 0);
            $dir = strtolower((string) ($order[0]['dir'] ?? 'desc'));
            if (in_array($dir, ['asc', 'desc'], true)) {
                $orderDir = $dir;
            }
        }
        if (! isset($columnsMap[$orderCol])) {
            $orderCol = 0;
        }

        $length = (int) ($this->request->getVar('length') ?? 10);
        $start = (int) ($this->request->getVar('start') ?? 0);
        if ($length <= 0) {
            $length = 10;
        }
        if ($length > 500) {
            $length = 500;
        }
        if ($start < 0) {
            $start = 0;
        }

        $selectParts = ['m.id'];
        foreach (['inv_med_code', 'inv_name', 'patient_code', 'inv_date', 'net_amount', 'payment_received', 'payment_balance'] as $col) {
            if (in_array($col, $fields, true)) {
                $selectParts[] = 'm.' . $col;
            }
        }
        if (in_array('ipd_code', $fields, true)) {
            $selectParts[] = 'm.ipd_code';
        } elseif (in_array('ipd_id', $fields, true)) {
            $selectParts[] = 'm.ipd_id';
        }

        foreach (['final_status', 'is_final', 'bill_final', 'is_draft', 'draft_status', 'confirm_invoice'] as $col) {
            if (in_array($col, $fields, true)) {
                $selectParts[] = 'm.' . $col;
            }
        }

        $dataBuilder = clone $baseBuilder;
        $rows = $dataBuilder
            ->select(implode(',', array_unique($selectParts)))
            ->orderBy($columnsMap[$orderCol], $orderDir)
            ->orderBy('m.id', 'DESC')
            ->limit($length, $start)
            ->get()
            ->getResult();

        $data = [];
        $sumNet = 0.0;

        foreach ($rows as $row) {
            $isFinalized = $this->isInvoiceFinalized($row);
            $invoiceId = (int) ($row->id ?? 0);
            $invoiceCode = (string) ($row->inv_med_code ?? ('M' . $invoiceId));
            $invoiceLink = '<a href="javascript:load_form_div(\'' . base_url('Medical/final_invoice/' . $invoiceId) . '\',\'medical-main\',\'Medical Invoice\');">' . esc($invoiceCode) . '</a>';

            $ipdCode = '-';
            if (property_exists($row, 'ipd_code') && (string) ($row->ipd_code ?? '') !== '') {
                $ipdCode = (string) $row->ipd_code;
            } elseif (property_exists($row, 'ipd_id') && (int) ($row->ipd_id ?? 0) > 0) {
                $ipdCode = 'IPD-' . (int) $row->ipd_id;
            }

            $invDate = (string) ($row->inv_date ?? '');
            if ($invDate !== '') {
                $ts = strtotime($invDate);
                if ($ts) {
                    $invDate = date('d-m-Y', $ts);
                }
            }

            $net = (float) ($row->net_amount ?? 0);
            $paid = (float) ($row->payment_received ?? 0);
            $balance = (float) ($row->payment_balance ?? ($net - $paid));
            $sumNet += $net;

            $statusBadge = $isFinalized
                ? '<span class="badge bg-success">Finalized</span>'
                : '<span class="badge bg-warning text-dark">Draft</span>';

            $data[] = [
                $invoiceLink,
                esc((string) ($row->inv_name ?? '-')),
                esc((string) ($row->patient_code ?? '-')),
                esc($ipdCode),
                esc($invDate !== '' ? $invDate : '-'),
                number_format($net, 2),
                number_format($paid, 2),
                number_format($balance, 2) . ' ' . $statusBadge,
            ];
        }

        return $this->response->setJSON([
            'draw' => (int) ($this->request->getVar('draw') ?? 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'foot_t_sum' => number_format($sumNet, 2),
        ]);
    }

    private function applyInvoiceStatusFilter($builder, array $fields, string $status, string $prefix = ''): void
    {
        if ($status === 'all' || $status === 'draft') {
            return;
        }

        $col = static function (string $name) use ($prefix): string {
            return $prefix . $name;
        };

        if ($status === 'final') {
            if (in_array('final_status', $fields, true)) {
                $builder->where($col('final_status'), 1);
                return;
            }
            if (in_array('is_final', $fields, true)) {
                $builder->where($col('is_final'), 1);
                return;
            }
            if (in_array('bill_final', $fields, true)) {
                $builder->where($col('bill_final'), 1);
                return;
            }
            if (in_array('is_draft', $fields, true)) {
                $builder->where($col('is_draft'), 0);
                return;
            }
            if (in_array('draft_status', $fields, true)) {
                $builder->where($col('draft_status'), 0);
                return;
            }
            if (in_array('confirm_invoice', $fields, true)) {
                $builder->where($col('confirm_invoice') . ' IS NOT NULL', null, false);
                $builder->where($col('confirm_invoice') . " <> ''", null, false);
                $builder->where($col('confirm_invoice') . " <> '0000-00-00 00:00:00'", null, false);
            }
            return;
        }

        if (in_array('final_status', $fields, true)) {
            $builder->where($col('final_status'), 0);
            return;
        }
        if (in_array('is_final', $fields, true)) {
            $builder->where($col('is_final'), 0);
            return;
        }
        if (in_array('bill_final', $fields, true)) {
            $builder->where($col('bill_final'), 0);
            return;
        }
        if (in_array('is_draft', $fields, true)) {
            $builder->where($col('is_draft'), 1);
            return;
        }
        if (in_array('draft_status', $fields, true)) {
            $builder->where($col('draft_status'), 1);
            return;
        }
        if (in_array('confirm_invoice', $fields, true)) {
            $builder->groupStart();
            $builder->where($col('confirm_invoice') . ' IS NULL', null, false);
            $builder->orWhere($col('confirm_invoice'), '');
            $builder->orWhere($col('confirm_invoice'), '0000-00-00 00:00:00');
            $builder->groupEnd();
        }
    }

    private function normalizeDate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
    }

    public function list_org_ipd()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $rows = $this->getCurrentIpdRowsFromBaseTables();

        $highBalanceCount = 0;
        if ($this->db->tableExists('inv_med_group') && $this->db->tableExists('ipd_master')) {
            $sql = "SELECT COUNT(*) AS c
                FROM inv_med_group m
                JOIN ipd_master i ON m.ipd_id=i.id
                WHERE i.ipd_status=0 AND m.med_type=1 AND ifnull(m.net_amount,0)>0 AND ifnull(m.payment_balance,0)>=50000";
            $high = $this->db->query($sql)->getRow();
            $highBalanceCount = (int) ($high->c ?? 0);
        }

        return view('medical/ipd_org_list', [
            'rows' => $rows,
            'highBalanceCount' => $highBalanceCount,
        ]);
    }

    private function getCurrentIpdRowsFromBaseTables(): array
    {
        if (! $this->db->tableExists('ipd_master')) {
            return [];
        }

        $patientTable = $this->db->tableExists('patient_master_exten')
            ? 'patient_master_exten'
            : ($this->db->tableExists('patient_master') ? 'patient_master' : null);

        if (! $patientTable) {
            return [];
        }

        $ipdFields = $this->db->getFieldNames('ipd_master') ?? [];
        $patientFields = $this->db->getFieldNames($patientTable) ?? [];

        $hasDocJoin = $this->db->tableExists('doctor_master') && in_array('doc_id', $ipdFields, true);
        $hasMedGroup = $this->db->tableExists('inv_med_group');
        $hasOrgCase = $this->db->tableExists('organization_case_master');
        $hasInsurance = $this->db->tableExists('hc_insurance');
        $hasApproved = $this->db->tableExists('org_approved_status');
        $hasBedMaster = $this->db->tableExists('hc_bed_master');

        $select = [
            'i.id',
            in_array('ipd_code', $ipdFields, true) ? 'i.ipd_code' : "CONCAT('IPD-',i.id) AS ipd_code",
            in_array('p_code', $patientFields, true) ? 'p.p_code' : "CAST(p.id AS CHAR) AS p_code",
            in_array('p_fname', $patientFields, true) ? 'p.p_fname' : "'' AS p_fname",
            in_array('p_rname', $patientFields, true) ? 'p.p_rname' : "'' AS p_rname",
            in_array('register_date', $ipdFields, true) ? "DATE_FORMAT(i.register_date,'%d-%m-%Y') AS str_register_date" : "'' AS str_register_date",
            in_array('register_date', $ipdFields, true) ? 'DATEDIFF(CURDATE(), i.register_date) AS no_days' : '0 AS no_days',
        ];

        if ($hasDocJoin) {
            $select[] = 'ifnull(d.p_fname, \'-\') AS doc_name';
        } elseif (in_array('doc_name', $ipdFields, true)) {
            $select[] = 'i.doc_name';
        } else {
            $select[] = "'-' AS doc_name";
        }

        if (in_array('admit_type', $ipdFields, true)) {
            $select[] = 'i.admit_type';
        } elseif ($hasOrgCase && in_array('case_id', $ipdFields, true) && $hasInsurance) {
            $select[] = "IF(o.id IS NOT NULL, ifnull(ins.short_name,'Credit'),'Direct') AS admit_type";
        } elseif (in_array('org_id', $ipdFields, true)) {
            $select[] = "IF(ifnull(i.org_id,0)>0,'Credit','Cash') AS admit_type";
        } else {
            $select[] = "'-' AS admit_type";
        }

        if ($hasOrgCase && in_array('case_id', $ipdFields, true) && $hasApproved) {
            $select[] = "IF(o.id IS NOT NULL, CONCAT(ifnull(aps.app_status,''),'/',ifnull(o.org_approved_amount,0)), '') AS Org_Status";
        } elseif (in_array('org_id', $ipdFields, true)) {
            $select[] = "IF(ifnull(i.org_id,0)>0,'ORG','SELF') AS Org_Status";
        } else {
            $select[] = "'' AS Org_Status";
        }

        if ($hasBedMaster && in_array('bed_used_p_id', $this->db->getFieldNames('hc_bed_master') ?? [], true)) {
            $select[] = "CONCAT('Bed No :', ifnull(b.bed_no,''), '[', ifnull(b.room_name,''), ']') AS Bed_Desc";
        } elseif (in_array('Bed_Desc', $ipdFields, true)) {
            $select[] = 'i.Bed_Desc';
        } elseif (in_array('bed_desc', $ipdFields, true)) {
            $select[] = 'i.bed_desc AS Bed_Desc';
        } elseif (in_array('bed_no', $ipdFields, true)) {
            $select[] = "CONCAT('Bed ', ifnull(i.bed_no,'')) AS Bed_Desc";
        } else {
            $select[] = "'-' AS Bed_Desc";
        }

        if ($hasMedGroup) {
            $groupFields = $this->db->getFieldNames('inv_med_group') ?? [];
            if (in_array('ipd_id', $groupFields, true) && in_array('net_amount', $groupFields, true) && in_array('med_type', $groupFields, true)) {
                $select[] = 'ifnull(g.med_amount,0) AS med_amount';
            } else {
                $select[] = '0 AS med_amount';
            }
        } else {
            $select[] = '0 AS med_amount';
        }

        $builder = $this->db->table('ipd_master i')
            ->select(implode(',', $select), false)
            ->join($patientTable . ' p', 'p.id=i.p_id', 'left');

        if ($hasDocJoin) {
            $builder->join('doctor_master d', 'd.id=i.doc_id', 'left');
        }

        if ($hasOrgCase && in_array('case_id', $ipdFields, true)) {
            $builder->join('organization_case_master o', 'o.id=i.case_id', 'left');
            if ($hasInsurance && in_array('insurance_id', $this->db->getFieldNames('organization_case_master') ?? [], true)) {
                $builder->join('hc_insurance ins', 'ins.id=o.insurance_id', 'left');
            }
            if ($hasApproved && in_array('org_approved_status_id', $this->db->getFieldNames('organization_case_master') ?? [], true)) {
                $builder->join('org_approved_status aps', 'aps.id=o.org_approved_status_id', 'left');
            }
        }

        if ($hasBedMaster) {
            $builder->join('hc_bed_master b', 'b.bed_used_p_id=i.id', 'left');
        }

        if ($hasMedGroup) {
            $groupFields = $this->db->getFieldNames('inv_med_group') ?? [];
            if (in_array('ipd_id', $groupFields, true) && in_array('net_amount', $groupFields, true) && in_array('med_type', $groupFields, true)) {
                $builder->join(
                    '(SELECT ipd_id, SUM(net_amount) AS med_amount FROM inv_med_group WHERE med_type=1 GROUP BY ipd_id) g',
                    'g.ipd_id=i.id',
                    'left',
                    false
                );
            }
        }

        if (in_array('ipd_status', $ipdFields, true)) {
            $builder->where('i.ipd_status', 0);
        }

        if (in_array('register_date', $ipdFields, true)) {
            $builder->orderBy('i.register_date', 'DESC');
        } else {
            $builder->orderBy('i.id', 'DESC');
        }

        return $builder->get()->getResult();
    }

    private function refreshIpdGroupPaymentFields(int $ipdId, int $medGroupId = 0): void
    {
        if ($ipdId <= 0 || ! $this->db->tableExists('inv_med_group')) {
            return;
        }

        $groupFields = $this->db->getFieldNames('inv_med_group') ?? [];
        if (! in_array('ipd_id', $groupFields, true)) {
            return;
        }

        $groupBuilder = $this->db->table('inv_med_group')->where('ipd_id', $ipdId);
        if (in_array('med_type', $groupFields, true)) {
            $groupBuilder->where('med_type', 1);
        }
        if ($medGroupId > 0 && in_array('med_group_id', $groupFields, true)) {
            $groupBuilder->where('med_group_id', $medGroupId);
        }
        if (in_array('med_group_id', $groupFields, true)) {
            $groupBuilder->orderBy('med_group_id', 'DESC');
        }

        $group = $groupBuilder->get()->getRow();
        if (! $group) {
            return;
        }

        $resolvedGroupId = (int) ($group->med_group_id ?? 0);

        $netAmount = 0.0;
        if ($this->db->tableExists('invoice_med_master')) {
            $invFields = $this->db->getFieldNames('invoice_med_master') ?? [];
            $invBuilder = $this->db->table('invoice_med_master')
                ->select('ifnull(sum(net_amount),0) as net', false)
                ->where('ipd_id', $ipdId);
            if (in_array('sale_return', $invFields, true)) {
                $invBuilder->where('sale_return', 0);
            }
            $invRow = $invBuilder->get()->getRow();
            $netAmount = (float) ($invRow->net ?? 0);
        }

        $paidAmount = 0.0;
        if ($this->db->tableExists('payment_history_medical')) {
            $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
            $payBuilder = $this->db->table('payment_history_medical');

            if ($resolvedGroupId > 0 && in_array('group_id', $payFields, true)) {
                $payBuilder->where('group_id', $resolvedGroupId);
            } elseif (in_array('ipd_id', $payFields, true)) {
                $payBuilder->where('ipd_id', $ipdId);
            }

            if (in_array('credit_debit', $payFields, true)) {
                $payBuilder->select('ifnull(sum(case when credit_debit>0 then amount*-1 else amount end),0) as paid', false);
            } else {
                $payBuilder->select('ifnull(sum(amount),0) as paid', false);
            }

            $payRow = $payBuilder->get()->getRow();
            $paidAmount = (float) ($payRow->paid ?? 0);
        }

        $update = [];
        if (in_array('net_amount', $groupFields, true)) {
            $update['net_amount'] = $netAmount;
        }
        if (in_array('payment_received', $groupFields, true)) {
            $update['payment_received'] = $paidAmount;
        }
        if (in_array('payment_balance', $groupFields, true)) {
            $update['payment_balance'] = $netAmount - $paidAmount;
        }

        if (! empty($update)) {
            $this->db->table('inv_med_group')
                ->where('ipd_id', $ipdId)
                ->where('med_group_id', $resolvedGroupId)
                ->update($update);
        }
    }

    public function list_org()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $rows = $this->getCurrentOrgCreditRowsFromBaseTables();

        return view('medical/org_list', [
            'rows' => $rows,
        ]);
    }

    private function getCurrentOrgCreditRowsFromBaseTables(): array
    {
        if (! $this->db->tableExists('organization_case_master')) {
            return [];
        }

        $patientTable = $this->db->tableExists('patient_master_exten')
            ? 'patient_master_exten'
            : ($this->db->tableExists('patient_master') ? 'patient_master' : null);

        if (! $patientTable) {
            return [];
        }

        $orgFields = $this->db->getFieldNames('organization_case_master') ?? [];
        $patientFields = $this->db->getFieldNames($patientTable) ?? [];

        $hasInsuranceName = in_array('insurance_company_name', $orgFields, true);
        $hasInsuranceJoin = $this->db->tableExists('hc_insurance') && in_array('insurance_id', $orgFields, true);

        $select = [
            'o.id AS org_id',
            in_array('case_id_code', $orgFields, true) ? 'o.case_id_code' : "CONCAT('ORG-',o.id) AS case_id_code",
            in_array('date_registration', $orgFields, true) ? "DATE_FORMAT(o.date_registration,'%d-%m-%Y') AS str_register_date" : "'' AS str_register_date",
            in_array('p_code', $patientFields, true) ? 'p.p_code' : "CAST(p.id AS CHAR) AS p_code",
            in_array('p_fname', $patientFields, true) ? 'p.p_fname' : "'' AS p_fname",
            in_array('p_rname', $patientFields, true) ? 'p.p_rname' : "'' AS p_rname",
        ];

        if ($hasInsuranceName) {
            $select[] = 'o.insurance_company_name';
        } elseif ($hasInsuranceJoin) {
            $select[] = 'ifnull(ins.ins_company_name, ins.short_name) AS insurance_company_name';
        } else {
            $select[] = "'Insurance' AS insurance_company_name";
        }

        $builder = $this->db->table('organization_case_master o')
            ->select(implode(',', $select), false)
            ->join($patientTable . ' p', 'p.id=o.p_id', 'left');

        if ($hasInsuranceJoin && ! $hasInsuranceName) {
            $builder->join('hc_insurance ins', 'ins.id=o.insurance_id', 'left');
        }

        if (in_array('insurance_id', $orgFields, true)) {
            $builder->where('o.insurance_id >', 1);
        }
        if (in_array('status', $orgFields, true)) {
            $builder->where('o.status', 0);
        }
        if (in_array('case_type', $orgFields, true)) {
            $builder->where('o.case_type', 0);
        }

        if (in_array('date_registration', $orgFields, true)) {
            $builder->orderBy('o.date_registration', 'DESC');
        } else {
            $builder->orderBy('o.id', 'DESC');
        }

        return $builder->get()->getResult();
    }

    public function main_store()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/main_store_dashboard');
    }

    public function supplier_list()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('med_supplier')) {
            return view('medical/placeholder', ['title' => 'Supplier Master']);
        }

        $supplierData = $this->db->table('med_supplier')
            ->orderBy('name_supplier', 'ASC')
            ->get()
            ->getResult();

        return view('medical/supplier_master', [
            'supplier_data' => $supplierData,
        ]);
    }

    public function supplier_list_sub()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('med_supplier')) {
            return $this->response->setBody('');
        }

        $supplierData = $this->db->table('med_supplier')
            ->orderBy('name_supplier', 'ASC')
            ->get()
            ->getResult();

        return view('medical/supplier_master_sub', [
            'supplier_data' => $supplierData,
        ]);
    }

    public function supplier_edit($sid = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $sid = (int) $sid;
        $supplierData = [];

        if ($sid > 0 && $this->db->tableExists('med_supplier')) {
            $row = $this->db->table('med_supplier')->where('sid', $sid)->get()->getRow();
            if ($row) {
                $supplierData = [$row];
            }
        }

        $indiaState = [];
        if ($this->db->tableExists('india_state')) {
            $indiaState = $this->db->table('india_state')->orderBy('state_name', 'ASC')->get()->getResult();
        }

        return view('medical/supplier_edit', [
            'supplier_data' => $supplierData,
            'india_state' => $indiaState,
        ]);
    }

    public function supplier_update()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->request->isAJAX() || ! $this->db->tableExists('med_supplier')) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Invalid request.</div>',
            ]);
        }

        $sid = (int) ($this->request->getPost('hid_sid') ?? 0);
        $nameSupplier = trim((string) ($this->request->getPost('input_name_supplier') ?? ''));
        $shortName = trim((string) ($this->request->getPost('input_short_name') ?? ''));
        $contactNo = trim((string) ($this->request->getPost('input_contact_no') ?? ''));
        $gstNo = trim((string) ($this->request->getPost('input_gst_no') ?? ''));
        $city = trim((string) ($this->request->getPost('input_city') ?? ''));
        $state = trim((string) ($this->request->getPost('input_state') ?? ''));
        $active = ((string) ($this->request->getPost('chk_active') ?? '') === 'on') ? 1 : 0;

        $errors = [];
        if ($nameSupplier === '' || mb_strlen($nameSupplier) < 3) {
            $errors[] = 'Supplier Name is required (min 3 chars).';
        }
        if ($shortName === '') {
            $errors[] = 'Short Name is required.';
        }
        if ($gstNo === '' || mb_strlen($gstNo) < 5) {
            $errors[] = 'GST No. is required (min 5 chars).';
        }
        if ($state === '') {
            $errors[] = 'State is required.';
        }

        if ($errors !== []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">' . implode('<br>', array_map('esc', $errors)) . '</div>',
            ]);
        }

        $fields = $this->db->getFieldNames('med_supplier') ?? [];
        $payload = [];

        if (in_array('name_supplier', $fields, true)) {
            $payload['name_supplier'] = $nameSupplier;
        }
        if (in_array('short_name', $fields, true)) {
            $payload['short_name'] = $shortName;
        }
        if (in_array('contact_no', $fields, true)) {
            $payload['contact_no'] = $contactNo;
        }
        if (in_array('gst_no', $fields, true)) {
            $payload['gst_no'] = $gstNo;
        }
        if (in_array('city', $fields, true)) {
            $payload['city'] = $city;
        }
        if (in_array('state', $fields, true)) {
            $payload['state'] = $state;
        }
        if (in_array('active', $fields, true)) {
            $payload['active'] = $active;
        }

        if ($payload === []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Supplier schema mismatch.</div>',
            ]);
        }

        if ($sid > 0) {
            $this->db->table('med_supplier')->where('sid', $sid)->update($payload);
            $insertId = $sid;
            $message = 'Saved Successfully';
        } else {
            $this->db->table('med_supplier')->insert($payload);
            $insertId = (int) $this->db->insertID();
            $message = 'Added Successfully';
        }

        if ($insertId <= 0) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Unable to save supplier.</div>',
            ]);
        }

        return $this->response->setJSON([
            'insertid' => $insertId,
            'show_text' => '<div class="alert alert-success mb-0">' . esc($message) . '</div>',
        ]);
    }

    public function company_master_list()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('med_company')) {
            return view('medical/placeholder', ['title' => 'Company Master']);
        }

        $companyData = $this->db->table('med_company')
            ->orderBy('company_name', 'ASC')
            ->get()
            ->getResult();

        return view('medical/company_master', [
            'med_company' => $companyData,
        ]);
    }

    public function company_list_sub()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('med_company')) {
            return $this->response->setBody('');
        }

        $companyData = $this->db->table('med_company')
            ->orderBy('company_name', 'ASC')
            ->get()
            ->getResult();

        return view('medical/company_master_sub', [
            'med_company' => $companyData,
        ]);
    }

    public function company_edit($cid = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $cid = (int) $cid;
        $companyData = [];

        if ($cid > 0 && $this->db->tableExists('med_company')) {
            $row = $this->db->table('med_company')->where('id', $cid)->get()->getRow();
            if ($row) {
                $companyData = [$row];
            }
        }

        return view('medical/company_edit', [
            'med_company' => $companyData,
        ]);
    }

    public function company_update()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->request->isAJAX() || ! $this->db->tableExists('med_company')) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Invalid request.</div>',
            ]);
        }

        $cid = (int) ($this->request->getPost('hid_cid') ?? 0);
        $companyName = trim((string) ($this->request->getPost('input_company_name') ?? ''));
        $contactPersonName = trim((string) ($this->request->getPost('input_contact_person_name') ?? ''));
        $contactPhoneNo = trim((string) ($this->request->getPost('input_contact_phone_no') ?? ''));

        $errors = [];
        if ($companyName === '' || mb_strlen($companyName) < 3) {
            $errors[] = 'Company Name is required (min 3 chars).';
        }
        if (mb_strlen($companyName) > 100) {
            $errors[] = 'Company Name should be max 100 chars.';
        }

        if ($errors !== []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">' . implode('<br>', array_map('esc', $errors)) . '</div>',
            ]);
        }

        $fields = $this->db->getFieldNames('med_company') ?? [];
        $payload = [];

        if (in_array('company_name', $fields, true)) {
            $payload['company_name'] = $companyName;
        }
        if (in_array('contact_person_name', $fields, true)) {
            $payload['contact_person_name'] = $contactPersonName;
        }
        if (in_array('contact_phone_no', $fields, true)) {
            $payload['contact_phone_no'] = $contactPhoneNo;
        }

        if ($payload === []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Company schema mismatch.</div>',
            ]);
        }

        if ($cid > 0) {
            $this->db->table('med_company')->where('id', $cid)->update($payload);
            $insertId = $cid;
            $message = 'Saved Successfully';
        } else {
            $this->db->table('med_company')->insert($payload);
            $insertId = (int) $this->db->insertID();
            $message = 'Added Successfully';
        }

        if ($insertId <= 0) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Unable to save company.</div>',
            ]);
        }

        return $this->response->setJSON([
            'insertid' => $insertId,
            'show_text' => '<div class="alert alert-success mb-0">' . esc($message) . '</div>',
        ]);
    }

    public function medicine_category()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('med_product_cat_master')) {
            return view('medical/placeholder', ['title' => 'Medicine Category']);
        }

        $categoryData = $this->db->table('med_product_cat_master')
            ->orderBy('med_cat_desc', 'ASC')
            ->get()
            ->getResult();

        return view('medical/medicine_category_master', [
            'med_product_cat_master' => $categoryData,
        ]);
    }

    public function medicine_category_sub()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('med_product_cat_master')) {
            return $this->response->setBody('');
        }

        $categoryData = $this->db->table('med_product_cat_master')
            ->orderBy('med_cat_desc', 'ASC')
            ->get()
            ->getResult();

        return view('medical/medicine_category_sub', [
            'med_product_cat_master' => $categoryData,
        ]);
    }

    public function medicine_category_edit($cid = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $cid = (int) $cid;
        $categoryData = [];

        if ($cid > 0 && $this->db->tableExists('med_product_cat_master')) {
            $row = $this->db->table('med_product_cat_master')->where('id', $cid)->get()->getRow();
            if ($row) {
                $categoryData = [$row];
            }
        }

        return view('medical/medicine_category_edit', [
            'med_product_cat_master' => $categoryData,
        ]);
    }

    public function medicine_category_update()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->request->isAJAX() || ! $this->db->tableExists('med_product_cat_master')) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Invalid request.</div>',
            ]);
        }

        $cid = (int) ($this->request->getPost('hid_cid') ?? 0);
        $medCatDesc = trim((string) ($this->request->getPost('input_med_cat_desc') ?? ''));

        $errors = [];
        if ($medCatDesc === '' || mb_strlen($medCatDesc) < 3) {
            $errors[] = 'Medicine Category Name is required (min 3 chars).';
        }
        if (mb_strlen($medCatDesc) > 100) {
            $errors[] = 'Medicine Category Name should be max 100 chars.';
        }

        if ($errors !== []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">' . implode('<br>', array_map('esc', $errors)) . '</div>',
            ]);
        }

        $fields = $this->db->getFieldNames('med_product_cat_master') ?? [];
        $payload = [];

        if (in_array('med_cat_desc', $fields, true)) {
            $payload['med_cat_desc'] = $medCatDesc;
        }

        if ($payload === []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Medicine category schema mismatch.</div>',
            ]);
        }

        if ($cid > 0) {
            $this->db->table('med_product_cat_master')->where('id', $cid)->update($payload);
            $insertId = $cid;
            $message = 'Saved Successfully';
        } else {
            $this->db->table('med_product_cat_master')->insert($payload);
            $insertId = (int) $this->db->insertID();
            $message = 'Added Successfully';
        }

        if ($insertId <= 0) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Unable to save medicine category.</div>',
            ]);
        }

        return $this->response->setJSON([
            'insertid' => $insertId,
            'show_text' => '<div class="alert alert-success mb-0">' . esc($message) . '</div>',
        ]);
    }

    public function drug_master_list()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/drug_master_list');
    }

    public function product_search()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('med_product_master')) {
            return view('medical/product_search_result', [
                'product_list' => [],
            ]);
        }

        $search = trim((string) ($this->request->getPost('txtsearch') ?? ''));
        $search = preg_replace('/[^A-Za-z0-9_ \-]/', '', $search);

        $builder = $this->db->table('med_product_master');
        if ($search === '') {
            $builder->orderBy('id', 'DESC')->limit(100);
        } else {
            $fields = $this->db->getFieldNames('med_product_master') ?? [];
            $builder->groupStart();
            if (in_array('item_name', $fields, true)) {
                $builder->like('item_name', $search);
            }
            if (in_array('genericname', $fields, true)) {
                $builder->orLike('genericname', $search);
            }
            if (ctype_digit($search) && in_array('id', $fields, true)) {
                $builder->orWhere('id', (int) $search);
            }
            $builder->groupEnd();
        }

        $productList = $builder->get()->getResult();

        return view('medical/product_search_result', [
            'product_list' => $productList,
        ]);
    }

    public function product_edit($productId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $productId = (int) $productId;
        $productData = [];

        if ($this->db->tableExists('med_product_master') && $productId > 0) {
            $row = $this->db->table('med_product_master')->where('id', $productId)->get()->getRow();
            if ($row) {
                $productData = [$row];
            }
        }

        $formulations = [];
        if ($this->db->tableExists('med_formulation')) {
            $formulations = $this->db->table('med_formulation')->orderBy('id', 'ASC')->get()->getResult();
        }

        $companies = [];
        if ($this->db->tableExists('med_company')) {
            $companies = $this->db->table('med_company')->orderBy('company_name', 'ASC')->get()->getResult();
        }

        $categories = [];
        if ($this->db->tableExists('med_product_cat_master')) {
            $categories = $this->db->table('med_product_cat_master')->orderBy('med_cat_desc', 'ASC')->get()->getResult();
        }

        $selectedCatIds = [];
        if ($productId > 0 && $this->db->tableExists('med_product_cat_assign')) {
            $catRows = $this->db->table('med_product_cat_assign')
                ->select('med_cat_id')
                ->where('med_product_id', $productId)
                ->get()
                ->getResultArray();
            $selectedCatIds = array_values(array_filter(array_map(static fn ($r) => (int) ($r['med_cat_id'] ?? 0), $catRows)));
        }

        return view('medical/product_edit', [
            'product_data' => $productData,
            'med_formulation' => $formulations,
            'med_company' => $companies,
            'med_product_cat_master' => $categories,
            'selected_cat_ids' => $selectedCatIds,
        ]);
    }

    public function product_master_update($productId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->request->isAJAX() || ! $this->db->tableExists('med_product_master')) {
            return $this->response->setJSON([
                'is_update_stock' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Invalid request.</div>',
            ]);
        }

        $productId = (int) $productId;

        $itemName = trim((string) ($this->request->getPost('input_item_name') ?? ''));
        $formulation = trim((string) ($this->request->getPost('input_formulation') ?? ''));
        $genericname = trim((string) ($this->request->getPost('input_genericname') ?? ''));
        $packing = trim((string) ($this->request->getPost('input_packing_type') ?? ''));
        $reOrderQty = trim((string) ($this->request->getPost('input_re_order_qty') ?? ''));
        $hsnCode = trim((string) ($this->request->getPost('input_HSNCODE') ?? ''));
        $cgst = trim((string) ($this->request->getPost('input_CGST') ?? ''));
        $sgst = trim((string) ($this->request->getPost('input_SGST') ?? ''));
        $rackNo = trim((string) ($this->request->getPost('input_rack_no') ?? ''));
        $shelfNo = trim((string) ($this->request->getPost('input_shelf_no') ?? ''));
        $coldStorage = trim((string) ($this->request->getPost('input_cold_storage') ?? ''));
        $companyId = (int) ($this->request->getPost('input_company_name') ?? 0);
        $relatedDrugId = (int) ($this->request->getPost('related_drug_id') ?? 0);

        $errors = [];
        if ($itemName === '' || mb_strlen($itemName) < 3) {
            $errors[] = 'Product Name is required (min 3 chars).';
        }
        if ($formulation === '') {
            $errors[] = 'Formulation is required.';
        }
        if ($packing === '') {
            $errors[] = 'Packing is required.';
        }
        if ($reOrderQty === '' || ! is_numeric($reOrderQty)) {
            $errors[] = 'Re-Order Qty must be numeric.';
        }
        if ($hsnCode === '') {
            $errors[] = 'HSNCODE is required.';
        }

        if ($errors !== []) {
            return $this->response->setJSON([
                'is_update_stock' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">' . implode('<br>', array_map('esc', $errors)) . '</div>',
            ]);
        }

        $fields = $this->db->getFieldNames('med_product_master') ?? [];
        $payload = [];

        $flag = static fn (string $k): int => ((string) (service('request')->getPost($k) ?? '') === 'on') ? 1 : 0;

        $map = [
            'related_drug_id' => $relatedDrugId,
            'item_name' => $itemName,
            'formulation' => $formulation,
            'genericname' => $genericname,
            'packing' => $packing,
            're_order_qty' => (float) $reOrderQty,
            'CGST_per' => (float) ($cgst === '' ? 0 : $cgst),
            'SGST_per' => (float) ($sgst === '' ? 0 : $sgst),
            'HSNCODE' => $hsnCode,
            'rack_no' => $rackNo,
            'shelf_no' => $shelfNo,
            'cold_storage' => $coldStorage,
            'company_id' => $companyId,
            'ban_flag_id' => $flag('chk_ban_flag_id'),
            'batch_applicable' => $flag('chk_batch_applicable'),
            'is_continue' => $flag('chk_is_continue'),
            'exp_date_applicable' => $flag('chk_exp_date_applicable'),
            'narcotic' => $flag('chk_narcotic'),
            'schedule_h' => $flag('chk_schedule_h'),
            'schedule_h1' => $flag('chk_schedule_h1'),
            'schedule_x' => $flag('chk_schedule_x'),
            'schedule_g' => $flag('chk_schedule_g'),
            'high_risk' => $flag('chk_high_risk'),
        ];

        foreach ($map as $column => $value) {
            if (in_array($column, $fields, true)) {
                $payload[$column] = $value;
            }
        }

        if ($payload === []) {
            return $this->response->setJSON([
                'is_update_stock' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Product schema mismatch.</div>',
            ]);
        }

        $builder = $this->db->table('med_product_master');
        if ($productId > 0) {
            $builder->where('id', $productId)->update($payload);
            $savedId = $productId;
            $message = 'Updated';
        } else {
            $builder->insert($payload);
            $savedId = (int) $this->db->insertID();
            $message = 'Added';
        }

        if ($savedId <= 0) {
            return $this->response->setJSON([
                'is_update_stock' => 0,
                'show_text' => '<div class="alert alert-danger mb-0">Unable to save product.</div>',
            ]);
        }

        if ($this->db->tableExists('med_product_cat_assign')) {
            $catIds = $this->request->getPost('med_cat_id');
            if (! is_array($catIds)) {
                $catIds = [];
            }
            $cleanCatIds = array_values(array_unique(array_filter(array_map(static fn ($v) => (int) $v, $catIds), static fn ($v) => $v > 0)));

            $this->db->table('med_product_cat_assign')->where('med_product_id', $savedId)->delete();
            foreach ($cleanCatIds as $catId) {
                $this->db->table('med_product_cat_assign')->insert([
                    'med_product_id' => $savedId,
                    'med_cat_id' => $catId,
                ]);
            }
        }

        return $this->response->setJSON([
            'is_update_stock' => $savedId,
            'show_text' => '<div class="alert alert-success mb-0">' . esc($message) . ' Successfully</div>',
        ]);
    }

    public function purchase()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/purchase_supplier_list');
    }

    public function purchase_invoice()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->db->tableExists('purchase_invoice')) {
            return view('medical/purchase_supp_list', ['purchase_list' => []]);
        }

        $search = trim((string) ($this->request->getPost('txtsearch') ?? ''));

        $builder = $this->db->table('purchase_invoice p')
            ->select("p.id, p.Invoice_no, p.date_of_invoice, DATE_FORMAT(p.date_of_invoice,'%d-%m-%Y') AS str_date_of_invoice, p.sid, p.T_Net_Amount AS tamount, p.ischallan, IFNULL(s.name_supplier,'-') AS name_supplier", false)
            ->join('med_supplier s', 'p.sid=s.sid', 'left');

        if ($search === '') {
            $builder->orderBy('p.id', 'DESC')->limit(50);
        } else {
            $builder->groupStart()
                ->like('p.Invoice_no', $search);
            if (ctype_digit($search)) {
                $builder->orWhere('p.id', (int) $search);
            }
            $builder->groupEnd();
            $builder->orderBy('p.id', 'DESC')->limit(100);
        }

        $purchaseList = $builder->get()->getResult();

        return view('medical/purchase_supp_list', [
            'purchase_list' => $purchaseList,
        ]);
    }

    public function purchase_new()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierData = [];
        if ($this->db->tableExists('med_supplier')) {
            $fields = $this->db->getFieldNames('med_supplier') ?? [];
            $builder = $this->db->table('med_supplier');
            if (in_array('active', $fields, true)) {
                $builder->where('active', 1);
            }
            $supplierData = $builder->orderBy('name_supplier', 'ASC')->get()->getResult();
        }

        return view('medical/new_purchase_invoice', [
            'supplier_data' => $supplierData,
        ]);
    }

    public function create_purchase()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->request->isAJAX() || ! $this->db->tableExists('purchase_invoice')) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => 'Invalid request',
            ]);
        }

        $supplierId = (int) ($this->request->getPost('input_supplier') ?? 0);
        $invoiceCode = trim((string) ($this->request->getPost('input_invoicecode') ?? ''));
        $invoiceDate = $this->normalizeUiDate((string) ($this->request->getPost('datepicker_invoice') ?? ''));
        $isChallan = (int) ($this->request->getPost('cbo_billtype') ?? 0);

        $errors = [];
        if ($supplierId <= 0) {
            $errors[] = 'Supplier is required.';
        }
        if ($invoiceCode === '' || mb_strlen($invoiceCode) < 2) {
            $errors[] = 'Invoice No is required.';
        }
        if ($invoiceDate === '') {
            $errors[] = 'Invoice date is invalid.';
        }

        if ($errors !== []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => implode('<br>', array_map('esc', $errors)),
            ]);
        }

        $exists = $this->db->table('purchase_invoice')
            ->where('sid', $supplierId)
            ->where('Invoice_no', $invoiceCode)
            ->countAllResults();

        if ($exists > 0) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => 'Record Already Exist',
            ]);
        }

        $fields = $this->db->getFieldNames('purchase_invoice') ?? [];
        $insert = [];
        if (in_array('sid', $fields, true)) {
            $insert['sid'] = $supplierId;
        }
        if (in_array('date_of_invoice', $fields, true)) {
            $insert['date_of_invoice'] = $invoiceDate;
        }
        if (in_array('Invoice_no', $fields, true)) {
            $insert['Invoice_no'] = $invoiceCode;
        }
        if (in_array('ischallan', $fields, true)) {
            $insert['ischallan'] = $isChallan;
        }
        if (in_array('monthyear', $fields, true)) {
            $insert['monthyear'] = date('my');
        }
        if (in_array('supp_name', $fields, true)) {
            $insert['supp_name'] = 0;
        }
        if (in_array('Taxable_Amt', $fields, true)) {
            $insert['Taxable_Amt'] = 0;
        }
        if (in_array('CGST_Amt', $fields, true)) {
            $insert['CGST_Amt'] = 0;
        }
        if (in_array('SGST_Amt', $fields, true)) {
            $insert['SGST_Amt'] = 0;
        }
        if (in_array('T_Net_Amount', $fields, true)) {
            $insert['T_Net_Amount'] = 0;
        }
        if (in_array('inv_status', $fields, true)) {
            $insert['inv_status'] = 0;
        }
        if (in_array('insert_time', $fields, true)) {
            $insert['insert_time'] = date('Y-m-d H:i:s');
        }

        $this->db->table('purchase_invoice')->insert($insert);
        $insertId = (int) $this->db->insertID();

        return $this->response->setJSON([
            'insertid' => $insertId,
            'show_text' => $insertId > 0 ? 'Added Successfully' : 'Unable to create invoice',
        ]);
    }

    public function purchase_invoice_edit($invId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        if ($invId <= 0 || ! $this->db->tableExists('purchase_invoice')) {
            return view('medical/placeholder', ['title' => 'Purchase Invoice Edit']);
        }

        $invoice = $this->db->table('purchase_invoice p')
            ->select("p.*, DATE_FORMAT(p.date_of_invoice,'%d/%m/%Y') AS str_date_of_invoice, IFNULL(s.name_supplier,'-') AS name_supplier, IFNULL(s.short_name,'') AS short_name, IFNULL(s.gst_no,'') AS gst_no", false)
            ->join('med_supplier s', 'p.sid=s.sid', 'left')
            ->where('p.id', $invId)
            ->get()
            ->getRow();

        if (! $invoice) {
            return view('medical/placeholder', ['title' => 'Purchase Invoice Not Found']);
        }

        $medGstPer = [];
        if ($this->db->tableExists('med_gst_per')) {
            $medGstPer = $this->db->table('med_gst_per')
                ->orderBy('gst_per', 'ASC')
                ->get()
                ->getResult();
        }

        return view('medical/purchase_invoice_edit', [
            'purchase_invoice' => [$invoice],
            'med_gst_per' => $medGstPer,
        ]);
    }

    public function purchase_master_edit($invId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        if ($invId <= 0 || ! $this->db->tableExists('purchase_invoice')) {
            return view('medical/placeholder', ['title' => 'Purchase Master Edit']);
        }

        $supplierData = [];
        if ($this->db->tableExists('med_supplier')) {
            $supplierData = $this->db->table('med_supplier')->orderBy('name_supplier', 'ASC')->get()->getResult();
        }

        $invMasterRow = $this->db->table('purchase_invoice p')
            ->select('p.*, IFNULL(s.name_supplier,\'-\') AS name_supplier', false)
            ->join('med_supplier s', 'p.sid=s.sid', 'left')
            ->where('p.id', $invId)
            ->get()
            ->getRow();

        if (! $invMasterRow) {
            return view('medical/placeholder', ['title' => 'Purchase Invoice Not Found']);
        }

        $purchaseItems = [];
        if ($this->db->tableExists('purchase_invoice_item')) {
            $purchaseItems = $this->db->table('purchase_invoice_item')
                ->where('purchase_id', $invId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResult();
        }

        return view('medical/purchase_invoice_master_edit', [
            'supplier_data' => $supplierData,
            'inv_master_data' => [$invMasterRow],
            'purchase_item' => $purchaseItems,
            'can_update_purchase_status' => $this->canPharmacyPermission('pharmacy.purchase.status-update'),
        ]);
    }

    public function update_purchase()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->request->isAJAX() || ! $this->db->tableExists('purchase_invoice')) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => 'Invalid request',
            ]);
        }

        $recId = (int) ($this->request->getPost('hid_purchaseid') ?? 0);
        $sid = (int) ($this->request->getPost('input_supplier') ?? 0);
        $invoiceCode = trim((string) ($this->request->getPost('input_invoicecode') ?? ''));
        $invoiceDate = $this->normalizeUiDate((string) ($this->request->getPost('datepicker_invoice') ?? ''));
        $isChallan = (int) ($this->request->getPost('cbo_billtype') ?? 0);

        $errors = [];
        if ($recId <= 0) {
            $errors[] = 'Invalid purchase id';
        }
        if ($sid <= 0) {
            $errors[] = 'Supplier is required';
        }
        if ($invoiceCode === '') {
            $errors[] = 'Invoice code is required';
        }
        if ($invoiceDate === '') {
            $errors[] = 'Invoice date is invalid';
        }

        if ($errors !== []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => implode('<br>', array_map('esc', $errors)),
            ]);
        }

        $fields = $this->db->getFieldNames('purchase_invoice') ?? [];
        $update = [];
        if (in_array('sid', $fields, true)) {
            $update['sid'] = $sid;
        }
        if (in_array('date_of_invoice', $fields, true)) {
            $update['date_of_invoice'] = $invoiceDate;
        }
        if (in_array('Invoice_no', $fields, true)) {
            $update['Invoice_no'] = $invoiceCode;
        }
        if (in_array('ischallan', $fields, true)) {
            $update['ischallan'] = $isChallan;
        }

        $this->db->table('purchase_invoice')->where('id', $recId)->update($update);

        return $this->response->setJSON([
            'insertid' => 1,
            'show_text' => 'Data Update',
        ]);
    }

    public function update_purchase_invoice_status($purchaseInvId, $invStatus)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->canPharmacyPermission('pharmacy.purchase.status-update')) {
            return $this->response
                ->setStatusCode(403)
                ->setBody('<div class="alert alert-danger m-3">Permission denied: cannot update purchase invoice status.</div>');
        }

        if (! $this->db->tableExists('purchase_invoice')) {
            return view('medical/placeholder', ['title' => 'Purchase Invoice Status']);
        }

        $purchaseInvId = (int) $purchaseInvId;
        $invStatus = (int) $invStatus;

        $user = auth()->user();
        $userId = (int) ($user->id ?? 0);
        $userName = trim(((string) ($user->first_name ?? '')) . ' ' . ((string) ($user->last_name ?? '')));
        $userInfo = trim($userName) . '[' . $userId . ']' . date('d-m-Y H:i:s');

        $fields = $this->db->getFieldNames('purchase_invoice') ?? [];
        $update = [];
        if (in_array('inv_status', $fields, true)) {
            $update['inv_status'] = $invStatus;
        }
        if (in_array('inv_status_update_by', $fields, true)) {
            $update['inv_status_update_by'] = $userInfo;
        }

        if ($update !== []) {
            $this->db->table('purchase_invoice')->where('id', $purchaseInvId)->update($update);
        }

        return $this->purchase_master_edit($purchaseInvId);
    }

    public function purchase_invoice_delete($invId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        if ($invId <= 0 || ! $this->db->tableExists('purchase_invoice')) {
            return $this->response->setJSON(['is_delete' => 0, 'show_text' => 'Invalid invoice']);
        }

        $invoice = $this->db->table('purchase_invoice')->where('id', $invId)->get()->getRow();
        if (! $invoice) {
            return $this->response->setJSON(['is_delete' => 0, 'show_text' => 'Invoice not found']);
        }

        $net = (float) ($invoice->T_Net_Amount ?? 0);
        if ($net > 0) {
            return $this->response->setJSON(['is_delete' => 0, 'show_text' => 'Cannot delete invoice with amount']);
        }

        if ($this->db->tableExists('purchase_invoice_item')) {
            $this->db->table('purchase_invoice_item')->where('purchase_id', $invId)->delete();
        }
        $this->db->table('purchase_invoice')->where('id', $invId)->delete();

        return $this->response->setJSON(['is_delete' => 1, 'show_text' => 'Deleted']);
    }

    public function purchase_invoice_item_list($invId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        if ($invId <= 0 || ! $this->db->tableExists('purchase_invoice_item')) {
            return view('medical/purchase_invoice_item', [
                'purchase_item' => [],
                'purchase_invoice' => [],
            ]);
        }

        $purchaseItem = $this->db->table('purchase_invoice_item')
            ->select("*, DATE_FORMAT(expiry_date,'%m/%y') AS exp_date_str", false)
            ->where('purchase_id', $invId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();

        $purchaseInvoice = $this->db->table('purchase_invoice')->where('id', $invId)->get()->getResult();

        return view('medical/purchase_invoice_item', [
            'purchase_item' => $purchaseItem,
            'purchase_invoice' => $purchaseInvoice,
        ]);
    }

    public function challan_invoice($supplierId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierId = (int) $supplierId;
        $currentPurchaseId = (int) ($this->request->getGet('current_purchase_id') ?? 0);
        if ($supplierId <= 0 || ! $this->db->tableExists('purchase_invoice') || ! $this->db->tableExists('purchase_invoice_item')) {
            return view('medical/purchase_challan_list', ['purchase_challan_item' => []]);
        }

        $invoiceFields = $this->db->getFieldNames('purchase_invoice') ?? [];
        $itemFields = $this->db->getFieldNames('purchase_invoice_item') ?? [];

        $builder = $this->db->table('purchase_invoice p')
            ->select("p.id, i.id AS ss_no, p.Invoice_no, p.date_of_invoice, DATE_FORMAT(p.date_of_invoice,'%d-%m-%Y') AS str_date_of_invoice, p.sid, IFNULL(s.name_supplier,'-') AS name_supplier, p.T_Net_Amount AS tamount, p.ischallan, i.Item_name, i.qty, i.qty_free, i.mrp", false)
            ->join('med_supplier s', 'p.sid=s.sid', 'left')
            ->join('purchase_invoice_item i', 'p.id=i.purchase_id', 'inner')
            ->where('p.sid', $supplierId)
            ->where('p.ischallan', 1);

        if ($currentPurchaseId > 0) {
            $builder->where('p.id !=', $currentPurchaseId);
        }

        if (in_array('inv_status', $invoiceFields, true)) {
            $builder->where('p.inv_status', 0);
        }
        if (in_array('remove_item', $itemFields, true)) {
            $builder->where('i.remove_item', 0);
        }
        if (in_array('item_return', $itemFields, true)) {
            $builder->where('i.item_return', 0);
        }

        $rows = $builder
            ->orderBy('p.id', 'DESC')
            ->orderBy('i.id', 'ASC')
            ->get()
            ->getResult();

        return view('medical/purchase_challan_list', [
            'purchase_challan_item' => $rows,
        ]);
    }

    public function challan_item_to_purchase($ssNo, $purchaseId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ssNo = (int) $ssNo;
        $purchaseId = (int) $purchaseId;

        if ($ssNo <= 0 || $purchaseId <= 0 || ! $this->db->tableExists('purchase_invoice_item')) {
            return $this->response->setJSON(['is_transfer' => 0, 'show_text' => 'Invalid request']);
        }

        $item = $this->db->table('purchase_invoice_item')->where('id', $ssNo)->get()->getRow();
        if (! $item) {
            return $this->response->setJSON(['is_transfer' => 0, 'show_text' => 'Item Not Found']);
        }

        $oldPurchaseId = (int) ($item->purchase_id ?? 0);
        $this->db->table('purchase_invoice_item')->where('id', $ssNo)->update([
            'purchase_id' => $purchaseId,
            'old_purchase_id' => $oldPurchaseId,
        ]);

        $this->recomputePurchaseInvoiceTotals($oldPurchaseId);
        $this->recomputePurchaseInvoiceTotals($purchaseId);

        return $this->response->setJSON(['is_transfer' => 1, 'show_text' => 'Item Added Successfully']);
    }

    public function challan_item_return($ssNo)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ssNo = (int) $ssNo;
        if ($ssNo <= 0 || ! $this->db->tableExists('purchase_invoice_item')) {
            return $this->response->setJSON(['is_transfer' => 0, 'show_text' => 'Invalid request']);
        }

        $item = $this->db->table('purchase_invoice_item')->where('id', $ssNo)->get()->getRow();
        if (! $item) {
            return $this->response->setJSON(['is_transfer' => 0, 'show_text' => 'Item Not Found']);
        }

        $oldPurchaseId = (int) ($item->old_purchase_id ?? 0);
        if ($oldPurchaseId <= 0) {
            return $this->response->setJSON(['is_transfer' => 0, 'show_text' => 'Original challan not available']);
        }

        $currentPurchaseId = (int) ($item->purchase_id ?? 0);
        $this->db->table('purchase_invoice_item')->where('id', $ssNo)->update([
            'purchase_id' => $oldPurchaseId,
            'old_purchase_id' => 0,
        ]);

        $this->recomputePurchaseInvoiceTotals($currentPurchaseId);
        $this->recomputePurchaseInvoiceTotals($oldPurchaseId);

        return $this->response->setJSON(['is_transfer' => 1, 'show_text' => 'Item Return Successfully']);
    }

    public function purchase_update_stock($invId, $returnStock = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->request->isAJAX() || ! $this->db->tableExists('purchase_invoice_item') || ! $this->db->tableExists('purchase_invoice')) {
            return $this->response->setJSON(['is_update_stock' => 0, 'show_text' => 'Invalid request']);
        }

        $invId = (int) $invId;
        $returnStock = (int) $returnStock;
        $itemId = (int) ($this->request->getPost('invoice_item_id') ?? 0);

        $invoice = $this->db->table('purchase_invoice')->where('id', $invId)->get()->getRow();
        if (! $invoice) {
            return $this->response->setJSON(['is_update_stock' => 0, 'show_text' => 'Purchase invoice not found']);
        }

        $itemCode = trim((string) ($this->request->getPost('input_product_code') ?? ''));
        $itemName = trim((string) ($this->request->getPost('input_drug_hid') ?? ''));
        $mrp = (float) ($this->request->getPost('input_product_mrp') ?? 0);
        $sellingPrice = (float) ($this->request->getPost('input_selling_price') ?? 0);
        $qty = (float) ($this->request->getPost('input_Qty') ?? 0);
        $qtyFree = (float) ($this->request->getPost('input_Qty_Free') ?? 0);
        $packing = (float) ($this->request->getPost('input_package') ?? 0);
        $purchasePrice = (float) ($this->request->getPost('input_purchase_price') ?? 0);
        $disc = (float) ($this->request->getPost('input_disc_price') ?? 0);
        $schAmt = (float) ($this->request->getPost('input_sch_amount') ?? 0);
        $schDisc = (float) ($this->request->getPost('input_sch_disc') ?? 0);
        $cgstPer = (float) ($this->request->getPost('input_CGST') ?? 0);
        $sgstPer = (float) ($this->request->getPost('input_SGST') ?? 0);
        $hsnCode = trim((string) ($this->request->getPost('input_HSNCODE') ?? ''));
        $batchNo = trim((string) ($this->request->getPost('input_batch_code') ?? ''));
        $rackNo = trim((string) ($this->request->getPost('input_rack_no') ?? ''));
        $shelfNo = trim((string) ($this->request->getPost('input_shelf_no') ?? ''));
        $coldStorage = trim((string) ($this->request->getPost('input_storage') ?? ''));

        $month = str_pad((string) ($this->request->getPost('datepicker_doe_month') ?? ''), 2, '0', STR_PAD_LEFT);
        $year = str_pad((string) ($this->request->getPost('datepicker_doe_year') ?? ''), 2, '0', STR_PAD_LEFT);
        $expiryDate = (ctype_digit($month) && ctype_digit($year)) ? ('20' . $year . '-' . $month . '-01') : null;

        $errors = [];
        if ($itemCode === '') {
            $errors[] = 'Not Found in Database';
        }
        if ($itemName === '' || mb_strlen($itemName) < 3) {
            $errors[] = 'Product Name is required';
        }
        if ($mrp <= 0) {
            $errors[] = 'Product MRP is required';
        }
        if ($sellingPrice <= 0) {
            $errors[] = 'Selling Price is required';
        }
        if ($qty <= 0) {
            $errors[] = 'Product Qty is required';
        }
        if ($packing <= 0) {
            $errors[] = 'Package is required';
        }
        if ($purchasePrice <= 0) {
            $errors[] = 'Purchase Price is required';
        }
        if ($hsnCode === '') {
            $errors[] = 'HSNCODE is required';
        }

        if ($errors !== []) {
            return $this->response->setJSON([
                'is_update_stock' => 0,
                'show_text' => implode('<br>', array_map('esc', $errors)),
            ]);
        }

        $qtyTotal = $qty + $qtyFree;
        $amount = (float) ($this->request->getPost('amount_price') ?? ($purchasePrice * $qty));
        $taxableAmount = (float) ($this->request->getPost('Tamount_price') ?? 0);
        if ($taxableAmount <= 0) {
            $taxableAmount = $amount - ($amount * $disc / 100) - $schAmt;
            if ($taxableAmount < 0) {
                $taxableAmount = 0;
            }
        }
        $cgstAmt = round($taxableAmount * $cgstPer / 100, 2);
        $sgstAmt = round($taxableAmount * $sgstPer / 100, 2);
        $netAmount = (float) ($this->request->getPost('Net_amount') ?? ($taxableAmount + $cgstAmt + $sgstAmt));

        $payload = [
            'purchase_id' => $invId,
            'item_code' => $itemCode,
            'Item_name' => $itemName,
            'packing' => $packing,
            'batch_no' => $batchNo,
            'purchase_price' => $purchasePrice,
            'expiry_date' => $expiryDate,
            'mrp' => $mrp,
            'qty' => $qty,
            'qty_free' => $qtyFree,
            'tqty' => $qtyTotal,
            'amount' => $amount,
            'discount' => $disc,
            'sch_disc_amt' => $schAmt,
            'sch_disc_per' => $schDisc,
            'taxable_amount' => $taxableAmount,
            'CGST_per' => $cgstPer,
            'CGST' => $cgstAmt,
            'SGST_per' => $sgstPer,
            'SGST' => $sgstAmt,
            'net_amount' => $netAmount,
            'HSNCODE' => $hsnCode,
            'selling_price' => $sellingPrice,
            'total_unit' => (int) round($qtyTotal * $packing),
            'purchase_unit_rate' => $qty > 0 ? round($purchasePrice / $qty, 4) : $purchasePrice,
            'selling_unit_rate' => $qty > 0 ? round($sellingPrice / $qty, 4) : $sellingPrice,
            'item_return' => $returnStock,
            'rack_no' => $rackNo,
            'shelf_no' => $shelfNo,
            'cold_storage' => $coldStorage,
            'stock_date' => (string) ($invoice->date_of_invoice ?? date('Y-m-d')),
            'insert_time' => date('Y-m-d H:i:s'),
        ];

        $itemFields = $this->db->getFieldNames('purchase_invoice_item') ?? [];
        $safePayload = [];
        foreach ($payload as $column => $value) {
            if (in_array($column, $itemFields, true)) {
                $safePayload[$column] = $value;
            }
        }

        if ($itemId > 0) {
            $oldRow = $this->db->table('purchase_invoice_item')->where('id', $itemId)->where('purchase_id', $invId)->get()->getRow();
            if (! $oldRow) {
                return $this->response->setJSON(['is_update_stock' => 0, 'show_text' => 'Record Not Found in Stock Table']);
            }

            $oldSold = (float) ($oldRow->total_sale_unit ?? 0);
            $newTotalUnit = (float) ($safePayload['total_unit'] ?? 0);
            if ($oldSold > $newTotalUnit && $returnStock === 0) {
                return $this->response->setJSON(['is_update_stock' => 0, 'show_text' => 'Error : Update Qty is Less then saled Qty : ' . $newTotalUnit . ' / Saled : ' . $oldSold]);
            }

            $this->db->table('purchase_invoice_item')->where('id', $itemId)->update($safePayload);
            $isUpdateStock = $itemId;
            $sendMsg = 'Updated';
        } else {
            if (in_array('total_sale_unit', $itemFields, true) && ! array_key_exists('total_sale_unit', $safePayload)) {
                $safePayload['total_sale_unit'] = 0;
            }
            if (in_array('total_return_unit', $itemFields, true) && ! array_key_exists('total_return_unit', $safePayload)) {
                $safePayload['total_return_unit'] = 0;
            }
            if (in_array('total_lost_unit', $itemFields, true) && ! array_key_exists('total_lost_unit', $safePayload)) {
                $safePayload['total_lost_unit'] = 0;
            }

            $this->db->table('purchase_invoice_item')->insert($safePayload);
            $isUpdateStock = (int) $this->db->insertID();
            $sendMsg = 'Added';
        }

        if ($isUpdateStock > 0) {
            $this->recomputePurchaseInvoiceTotals($invId);
        }

        return $this->response->setJSON([
            'is_update_stock' => $isUpdateStock,
            'product_code' => $itemCode,
            'show_text' => $isUpdateStock > 0 ? $sendMsg : 'Error : Update in Stock Table',
        ]);
    }

    public function purchase_invoice_item_edit($invId, $invItemId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        $invItemId = (int) $invItemId;

        $row = $this->db->table('purchase_invoice_item')
            ->select("*, DATE_FORMAT(expiry_date,'%m') AS str_expiry_month, DATE_FORMAT(expiry_date,'%y') AS str_expiry_year", false)
            ->where('id', $invItemId)
            ->where('purchase_id', $invId)
            ->get()
            ->getRow();

        if (! $row) {
            return $this->response->setJSON([
                'is_update_stock' => '0',
                'show_text' => 'Record not found',
            ]);
        }

        return $this->response->setJSON([
            'is_update_stock' => '1',
            'item_id' => (int) ($row->id ?? 0),
            'product_code' => (string) ($row->item_code ?? ''),
            'product_mrp' => (string) ($row->mrp ?? ''),
            'selling_price' => (string) ($row->selling_price ?? ''),
            'batch_code' => (string) ($row->batch_no ?? ''),
            'disc_price' => (string) ($row->discount ?? ''),
            'qty' => (string) ($row->qty ?? ''),
            'qty_free' => (string) ($row->qty_free ?? ''),
            'package' => (string) ($row->packing ?? ''),
            'purchase_price' => (string) ($row->purchase_price ?? ''),
            'datepicker_doe_month' => (string) ($row->str_expiry_month ?? ''),
            'datepicker_doe_year' => (string) ($row->str_expiry_year ?? ''),
            'drug' => (string) ($row->Item_name ?? ''),
            'sch_disc_amt' => (string) ($row->sch_disc_amt ?? ''),
            'sch_disc_per' => (string) ($row->sch_disc_per ?? ''),
            'HSNCODE' => (string) ($row->HSNCODE ?? ''),
            'CGST_per' => (string) ($row->CGST_per ?? ''),
            'SGST_per' => (string) ($row->SGST_per ?? ''),
            'cold_storage' => (string) ($row->cold_storage ?? ''),
            'shelf_no' => (string) ($row->shelf_no ?? ''),
            'rack_no' => (string) ($row->rack_no ?? ''),
        ]);
    }

    public function purchase_invoice_item_list_old($itemCode)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $itemCode = (int) $itemCode;
        $rows = [];

        if ($itemCode > 0 && $this->db->tableExists('purchase_invoice') && $this->db->tableExists('purchase_invoice_item')) {
            $rows = $this->db->table('purchase_invoice p')
                ->select("IFNULL(s.name_supplier,'-') AS name_supplier, DATE_FORMAT(p.date_of_invoice,'%d-%m-%Y') AS date_of_invoice_str, i.Item_name, i.packing, i.purchase_price, i.qty, i.qty_free, i.mrp, i.discount, i.sch_disc_per, i.purchase_unit_rate, p.Invoice_no", false)
                ->join('purchase_invoice_item i', 'p.id=i.purchase_id', 'inner')
                ->join('med_supplier s', 'p.sid=s.sid', 'left')
                ->where('i.item_code', $itemCode)
                ->orderBy('i.id', 'DESC')
                ->limit(5)
                ->get()
                ->getResult();
        }

        return view('medical/purchase_item_old', [
            'purchase_item_old' => $rows,
        ]);
    }

    public function purchase_invoice_item_delete($invId, $invItemDel)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        $invItemDel = (int) $invItemDel;

        if ($invId <= 0 || $invItemDel <= 0 || ! $this->db->tableExists('purchase_invoice_item')) {
            return $this->response->setJSON(['is_update_stock' => 0, 'show_text' => 'Invalid request']);
        }

        $drugItem = $this->db->table('purchase_invoice_item')
            ->where('id', $invItemDel)
            ->where('purchase_id', $invId)
            ->get()
            ->getRow();

        if (! $drugItem) {
            return $this->response->setJSON(['is_update_stock' => 0, 'show_text' => 'Item not found']);
        }

        $saleQty = (float) ($drugItem->total_sale_unit ?? 0);
        if ($saleQty > 0) {
            return $this->response->setJSON([
                'is_update_stock' => 0,
                'show_text' => 'Sold some Quantity of this Item',
            ]);
        }

        $this->db->table('purchase_invoice_item')->where('id', $invItemDel)->delete();
        $this->recomputePurchaseInvoiceTotals($invId);

        return $this->response->setJSON([
            'is_update_stock' => 1,
            'show_text' => 'Removed Successfully',
        ]);
    }

    private function recomputePurchaseInvoiceTotals(int $purchaseId): void
    {
        if ($purchaseId <= 0 || ! $this->db->tableExists('purchase_invoice') || ! $this->db->tableExists('purchase_invoice_item')) {
            return;
        }

        $fields = $this->db->getFieldNames('purchase_invoice_item') ?? [];
        $builder = $this->db->table('purchase_invoice_item')
            ->select('ROUND(SUM(IFNULL(taxable_amount,0)),2) AS taxable_amount, ROUND(SUM(IFNULL(CGST,0)),2) AS cgst_amount, ROUND(SUM(IFNULL(SGST,0)),2) AS sgst_amount, ROUND(SUM(IFNULL(net_amount,0)),2) AS net_amount', false)
            ->where('purchase_id', $purchaseId);

        if (in_array('remove_item', $fields, true)) {
            $builder->where('remove_item', 0);
        }
        if (in_array('item_return', $fields, true)) {
            $builder->where('item_return', 0);
        }

        $totals = $builder->get()->getRowArray() ?? [];

        $pFields = $this->db->getFieldNames('purchase_invoice') ?? [];
        $update = [];
        if (in_array('Taxable_Amt', $pFields, true)) {
            $update['Taxable_Amt'] = (float) ($totals['taxable_amount'] ?? 0);
        }
        if (in_array('CGST_Amt', $pFields, true)) {
            $update['CGST_Amt'] = (float) ($totals['cgst_amount'] ?? 0);
        }
        if (in_array('SGST_Amt', $pFields, true)) {
            $update['SGST_Amt'] = (float) ($totals['sgst_amount'] ?? 0);
        }
        if (in_array('T_Net_Amount', $pFields, true)) {
            $update['T_Net_Amount'] = (float) ($totals['net_amount'] ?? 0);
        }

        if ($update !== []) {
            $this->db->table('purchase_invoice')->where('id', $purchaseId)->update($update);
        }
    }

    private function normalizeUiDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $dt = \DateTime::createFromFormat('d/m/Y', $value);
        if ($dt instanceof \DateTime) {
            return $dt->format('Y-m-d');
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt instanceof \DateTime ? $dt->format('Y-m-d') : '';
    }

    public function purchase_return()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/purchase_return');
    }

    public function purchase_return_new()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierData = [];
        if ($this->db->tableExists('med_supplier')) {
            $supplierData = $this->db->table('med_supplier')
                ->orderBy('name_supplier', 'ASC')
                ->get()
                ->getResult();
        }

        return view('medical/new_purchase_return_invoice', [
            'supplier_data' => $supplierData,
        ]);
    }

    public function purchase_return_invoice()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $rows = [];
        if ($this->db->tableExists('purchase_return_invoice')) {
            $searchRaw = (string) ($this->request->getPost('txtsearch') ?? $this->request->getGet('txtsearch'));
            $search = preg_replace('/[^A-Za-z0-9_ \-]/', '', trim($searchRaw));
            $invNoCol = $this->purchaseReturnInvoiceNoColumn();

            $builder = $this->db->table('purchase_return_invoice p')
                ->select("p.id, p.{$invNoCol} AS p_r_invoice_no, p.date_of_invoice, DATE_FORMAT(p.date_of_invoice,'%d-%m-%Y') AS str_date_of_invoice, p.sid, IFNULL(s.name_supplier,'-') AS name_supplier, IFNULL(s.short_name,'-') AS short_name", false)
                ->join('med_supplier s', 'p.sid=s.sid', 'left');

            if ($search !== '') {
                $builder->groupStart();
                $builder->like("p.{$invNoCol}", $search);
                if (ctype_digit($search)) {
                    $builder->orWhere('p.id', (int) $search);
                }
                $builder->groupEnd();
            }

            $rows = $builder
                ->orderBy('p.id', 'DESC')
                ->limit(50)
                ->get()
                ->getResult();
        }

        return view('medical/purchase_return_supplier_list', [
            'purchase_return_invoice' => $rows,
        ]);
    }

    public function create_purchase_return()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if (! $this->request->isAJAX() || ! $this->db->tableExists('purchase_return_invoice')) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => 'Invalid request',
            ]);
        }

        $sid = (int) ($this->request->getPost('input_supplier') ?? 0);
        $invoiceDate = $this->normalizeUiDate((string) ($this->request->getPost('datepicker_invoice') ?? ''));

        $errors = [];
        if ($sid <= 0) {
            $errors[] = 'Supplier is required';
        }
        if ($invoiceDate === '') {
            $errors[] = 'Invoice date is invalid';
        }

        if ($errors !== []) {
            return $this->response->setJSON([
                'insertid' => 0,
                'show_text' => implode('<br>', array_map('esc', $errors)),
            ]);
        }

        $fields = $this->db->getFieldNames('purchase_return_invoice') ?? [];
        $insert = [];
        if (in_array('sid', $fields, true)) {
            $insert['sid'] = $sid;
        }
        if (in_array('date_of_invoice', $fields, true)) {
            $insert['date_of_invoice'] = $invoiceDate;
        }
        if (in_array('status', $fields, true)) {
            $insert['status'] = 0;
        }

        $this->db->table('purchase_return_invoice')->insert($insert);
        $insertId = (int) $this->db->insertID();

        if ($insertId > 0 && in_array('p_r_invoice_no', $fields, true)) {
            $invoiceNo = 'PR' . date('ym') . str_pad((string) ($insertId % 1000), 3, '0', STR_PAD_LEFT);
            $this->db->table('purchase_return_invoice')
                ->where('id', $insertId)
                ->update(['p_r_invoice_no' => $invoiceNo]);
        }

        return $this->response->setJSON([
            'insertid' => $insertId,
            'show_text' => $insertId > 0 ? 'Added Successfully' : 'Unable to create purchase return invoice',
        ]);
    }

    public function purchase_return_invoice_edit($invId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        if ($invId <= 0 || ! $this->db->tableExists('purchase_return_invoice')) {
            return '<div class="alert alert-warning m-2">Purchase return invoice not found.</div>';
        }

        $invNoCol = $this->purchaseReturnInvoiceNoColumn();
        $invoice = $this->db->table('purchase_return_invoice p')
            ->select("p.id, p.{$invNoCol} AS Invoice_no, p.date_of_invoice, DATE_FORMAT(p.date_of_invoice,'%d/%m/%Y') AS str_date_of_invoice, p.sid, p.status AS inv_status, IFNULL(s.name_supplier,'-') AS name_supplier, IFNULL(s.short_name,'-') AS short_name, IFNULL(s.gst_no,'-') AS gst_no", false)
            ->join('med_supplier s', 'p.sid=s.sid', 'left')
            ->where('p.id', $invId)
            ->get()
            ->getRow();

        if (! $invoice) {
            return '<div class="alert alert-warning m-2">Purchase return invoice not found.</div>';
        }

        $items = $this->fetchPurchaseReturnItems($invId);
        $content = view('medical/purchase_return_invoice_item', [
            'purchase_return_invoice_item' => $items,
        ]);

        return view('medical/purchase_return_invoice_edit', [
            'purchase_return_invoice' => $invoice,
            'purchase_return_invoice_item' => $items,
            'content' => $content,
        ]);
    }

    public function purchase_return_invoice_item_list($invId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        $items = $this->fetchPurchaseReturnItems($invId);
        $invoice = null;
        if ($invId > 0 && $this->db->tableExists('purchase_return_invoice')) {
            $invoice = $this->db->table('purchase_return_invoice')->where('id', $invId)->get()->getRow();
        }

        return view('medical/purchase_return_invoice_item', [
            'purchase_return_invoice_item' => $items,
            'purchase_return_invoice' => $invoice ? [$invoice] : [],
        ]);
    }

    public function purchase_invoice_product($suppId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/purchase_return_product_search', [
            'supp_id' => (int) $suppId,
        ]);
    }

    public function purchase_invoice_old($suppId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $suppId = (int) $suppId;
        $searchRaw = (string) ($this->request->getPost('txtsearch') ?? $this->request->getGet('txtsearch'));
        $search = preg_replace('/[^A-Za-z0-9_ \-]/', '', trim($searchRaw));

        $rows = [];
        if (
            $suppId > 0
            && $this->db->tableExists('purchase_invoice')
            && $this->db->tableExists('purchase_invoice_item')
            && $this->db->tableExists('med_supplier')
        ) {
            $itemFields = $this->db->getFieldNames('purchase_invoice_item') ?? [];

            $curQtyExpr = '(IFNULL(i.total_unit,0)-IFNULL(i.total_sale_unit,0)-IFNULL(i.total_lost_unit,0)-IFNULL(i.total_return_unit,0))';
            $builder = $this->db->table('purchase_invoice p')
                ->select("p.id AS pur_id, p.Invoice_no, p.date_of_invoice, IF(i.expiry_date<DATE_ADD(CURDATE(),INTERVAL 3 MONTH),1,0) AS isExp, DATE_FORMAT(p.date_of_invoice,'%d-%m-%Y') AS str_date_of_invoice, DATE_FORMAT(i.expiry_date,'%m-%Y') AS exp_date, p.sid, IFNULL(s.name_supplier,'-') AS name_supplier, IFNULL(s.short_name,'-') AS short_name, IFNULL(s.gst_no,'-') AS gst_no, IFNULL(p.T_Net_Amount,0) AS tamount, i.*, {$curQtyExpr} AS cur_qty", false)
                ->join('med_supplier s', 'p.sid=s.sid', 'inner')
                ->join('purchase_invoice_item i', 'p.id=i.purchase_id', 'inner')
                ->where('s.sid', $suppId)
                ->where('p.date_of_invoice >= DATE_ADD(CURDATE(), INTERVAL -1 YEAR)', null, false)
                ->where("{$curQtyExpr} > 0", null, false);

            if (in_array('remove_item', $itemFields, true)) {
                $builder->where('i.remove_item', 0);
            }
            if (in_array('item_return', $itemFields, true)) {
                $builder->where('i.item_return', 0);
            }

            if ($search !== '') {
                $builder->groupStart();
                $builder->like('p.Invoice_no', $search);
                if (ctype_digit($search)) {
                    $builder->orWhere('p.id', (int) $search);
                }
                $builder->groupEnd();
            }

            $rows = $builder
                ->orderBy('p.id', 'DESC')
                ->get()
                ->getResult();
        }

        return view('medical/purchase_return_old_invoice_list', [
            'purchase_list' => $rows,
        ]);
    }

    public function purchase_return_add_remove_item()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) ($this->request->getPost('inv_id') ?? 0);
        $itemId = (int) ($this->request->getPost('itemid') ?? 0);
        $rqty = (float) ($this->request->getPost('rqty') ?? 0);
        $rbatchNo = trim((string) ($this->request->getPost('rbatch_no') ?? ''));
        $rexpiryDt = trim((string) ($this->request->getPost('rexpiry_dt') ?? ''));

        $respond = function (int $update, string $msgText) use ($invId) {
            return $this->response->setJSON([
                'update' => $update,
                'msg_text' => $msgText,
                'content' => view('medical/purchase_return_invoice_item', [
                    'purchase_return_invoice_item' => $this->fetchPurchaseReturnItems($invId),
                ]),
            ]);
        };

        if (
            $invId <= 0
            || $itemId <= 0
            || $rqty <= 0
            || ! $this->db->tableExists('purchase_return_invoice')
            || ! $this->db->tableExists('purchase_return_invoice_item')
            || ! $this->db->tableExists('purchase_invoice_item')
        ) {
            return $respond(0, 'Invalid return input');
        }

        $invoice = $this->db->table('purchase_return_invoice')->where('id', $invId)->get()->getRow();
        if (! $invoice) {
            return $respond(0, 'Purchase return invoice not found');
        }

        $sourceItem = $this->db->table('purchase_invoice_item')->where('id', $itemId)->get()->getRow();
        if (! $sourceItem) {
            return $respond(0, 'No item found');
        }

        $totalUnit = (float) ($sourceItem->total_unit ?? 0);
        $totalSaleUnit = (float) ($sourceItem->total_sale_unit ?? 0);
        $totalReturnUnit = (float) ($sourceItem->total_return_unit ?? 0);
        $totalLostUnit = (float) ($sourceItem->total_lost_unit ?? 0);
        $curQty = $totalUnit - $totalSaleUnit - $totalReturnUnit - $totalLostUnit;

        if ($curQty <= 0) {
            return $respond(0, 'Current Item Qty is 0');
        }
        if ($rqty > $curQty) {
            return $respond(0, 'Current Item Qty is ' . rtrim(rtrim((string) $curQty, '0'), '.'));
        }

        $fields = $this->db->getFieldNames('purchase_return_invoice_item') ?? [];
        $insert = [];
        if (in_array('purchase_inv_id', $fields, true)) {
            $insert['purchase_inv_id'] = $invId;
        }
        if (in_array('purchase_item_id', $fields, true)) {
            $insert['purchase_item_id'] = $itemId;
        }
        if (in_array('item_code', $fields, true)) {
            $insert['item_code'] = $sourceItem->item_code ?? 0;
        }
        if (in_array('Item_name', $fields, true)) {
            $insert['Item_name'] = $sourceItem->Item_name ?? ($sourceItem->item_name ?? '');
        }
        if (in_array('batch_no_r', $fields, true)) {
            $insert['batch_no_r'] = $rbatchNo;
        }
        if (in_array('expiry_date_r', $fields, true)) {
            $insert['expiry_date_r'] = $rexpiryDt !== '' ? $rexpiryDt : null;
        }
        if (in_array('qty', $fields, true)) {
            $insert['qty'] = $rqty;
        }

        $this->db->table('purchase_return_invoice_item')->insert($insert);
        $newId = (int) $this->db->insertID();
        if ($newId <= 0) {
            return $respond(0, 'Item not added');
        }

        $this->adjustPurchaseItemReturnUnit($itemId, $rqty);
        return $respond(1, 'Item Added');
    }

    public function purchase_return_remove_item_invoice($itemId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $itemId = (int) $itemId;
        if ($itemId <= 0 || ! $this->db->tableExists('purchase_return_invoice_item')) {
            return $this->response->setJSON([
                'update' => 0,
                'msg_text' => 'Invalid return item',
                'content' => '',
            ]);
        }

        $row = $this->db->table('purchase_return_invoice_item')->where('id', $itemId)->get()->getRow();
        if (! $row) {
            return $this->response->setJSON([
                'update' => 0,
                'msg_text' => 'No item found',
                'content' => '',
            ]);
        }

        $invId = (int) ($row->purchase_inv_id ?? 0);
        $sourceItemId = (int) ($row->purchase_item_id ?? 0);
        $qty = (float) ($row->qty ?? 0);

        $this->db->table('purchase_return_invoice_item')->where('id', $itemId)->delete();
        if ($sourceItemId > 0 && $qty > 0) {
            $this->adjustPurchaseItemReturnUnit($sourceItemId, -1 * $qty);
        }

        return $this->response->setJSON([
            'update' => 1,
            'msg_text' => 'Item Removed',
            'content' => view('medical/purchase_return_invoice_item', [
                'purchase_return_invoice_item' => $this->fetchPurchaseReturnItems($invId),
            ]),
        ]);
    }

    public function print_purchase_return($invId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $invId = (int) $invId;
        if ($invId <= 0 || ! $this->db->tableExists('purchase_return_invoice')) {
            return $this->response->setStatusCode(404)->setBody('Purchase return invoice not found');
        }

        $invNoCol = $this->purchaseReturnInvoiceNoColumn();
        $invoice = $this->db->table('purchase_return_invoice p')
            ->select("p.id, p.{$invNoCol} AS Invoice_no, p.date_of_invoice, DATE_FORMAT(p.date_of_invoice,'%d/%m/%Y') AS str_date_of_invoice, p.sid, p.status AS inv_status, IFNULL(s.name_supplier,'-') AS name_supplier, IFNULL(s.short_name,'-') AS short_name, IFNULL(s.gst_no,'-') AS gst_no", false)
            ->join('med_supplier s', 'p.sid=s.sid', 'left')
            ->where('p.id', $invId)
            ->get()
            ->getRow();

        if (! $invoice) {
            return $this->response->setStatusCode(404)->setBody('Purchase return invoice not found');
        }

        $items = $this->fetchPurchaseReturnItems($invId);
        $content = view('medical/purchase_return_invoice_item_print', [
            'purchase_return_invoice' => [$invoice],
            'purchase_return_invoice_item' => $items,
        ]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => WRITEPATH . 'cache/mpdf',
        ]);
        $mpdf->showWatermarkText = false;
        $mpdf->WriteHTML($content);

        $filename = 'Return_Invoice-' . $invId . '-' . date('YmdHis') . '.pdf';
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output($filename, 'S'));
    }

    private function purchaseReturnInvoiceNoColumn(): string
    {
        $fields = $this->db->getFieldNames('purchase_return_invoice') ?? [];
        if (in_array('p_r_invoice_no', $fields, true)) {
            return 'p_r_invoice_no';
        }

        return in_array('Invoice_no', $fields, true) ? 'Invoice_no' : 'id';
    }

    private function fetchPurchaseReturnItems(int $invId): array
    {
        if (
            $invId <= 0
            || ! $this->db->tableExists('purchase_return_invoice_item')
            || ! $this->db->tableExists('purchase_invoice_item')
        ) {
            return [];
        }

        $itemFields = $this->db->getFieldNames('purchase_invoice_item') ?? [];
        $gstPerExpr = '0';
        if (in_array('CGST_per_old', $itemFields, true) && in_array('CGST_per', $itemFields, true)) {
            $gstPerExpr = 'IF(p.CGST_per_old IS NULL, p.CGST_per, p.CGST_per_old) * 2';
        } elseif (in_array('CGST_per', $itemFields, true)) {
            $gstPerExpr = 'p.CGST_per * 2';
        }

        return $this->db->table('purchase_return_invoice_item r')
            ->select("p.*, r.purchase_inv_id, r.qty AS r_qty, ROUND(r.qty/IFNULL(NULLIF(p.packing,0),1),2) AS qty_pak, r.id AS r_id, IF(IFNULL(r.batch_no_r,'')='', p.batch_no, r.batch_no_r) AS batch_no_r_s, DATE_FORMAT(p.expiry_date,'%m/%y') AS exp_date_str, p.purchase_unit_rate*r.qty AS r_amount, p.purchase_unit_rate, {$gstPerExpr} AS gst_per", false)
            ->join('purchase_invoice_item p', 'r.purchase_item_id=p.id', 'inner')
            ->where('r.purchase_inv_id', $invId)
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResult();
    }

    private function adjustPurchaseItemReturnUnit(int $purchaseItemId, float $qtyDelta): void
    {
        if ($purchaseItemId <= 0 || ! $this->db->tableExists('purchase_invoice_item')) {
            return;
        }

        $fields = $this->db->getFieldNames('purchase_invoice_item') ?? [];
        if (! in_array('total_return_unit', $fields, true)) {
            return;
        }

        $row = $this->db->table('purchase_invoice_item')
            ->select('id,total_return_unit')
            ->where('id', $purchaseItemId)
            ->get()
            ->getRow();
        if (! $row) {
            return;
        }

        $nextQty = (float) ($row->total_return_unit ?? 0) + $qtyDelta;
        if ($nextQty < 0) {
            $nextQty = 0;
        }

        $this->db->table('purchase_invoice_item')
            ->where('id', $purchaseItemId)
            ->update(['total_return_unit' => $nextQty]);
    }

    public function main_store_placeholder($slug = '')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $slug = strtolower(trim((string) $slug));
        if ($slug === 'drug-sale-customer-wise') {
            return $this->report_med_patient();
        }
        if ($slug === 'print-bill-uhid') {
            return $this->print_bill_on_uhid();
        }

        $titles = [
            'drug-master' => 'Drug Master',
            'drug-company' => 'Drug Company',
            'medicine-category' => 'Medicine Category',
            'drug-sale-customer-wise' => 'Drug Sale Customer Wise Report',
            'print-bill-uhid' => 'Print Bill on UHID',
        ];

        $title = $titles[$slug] ?? 'Store Main Module';
        return view('medical/placeholder', ['title' => $title]);
    }

    public function print_bill_on_uhid()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/print_bill_on_uhid', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function uhid_report($dateRange, $pCode)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $pCode = trim(urldecode((string) $pCode));

        if ($pCode === '') {
            return $this->response->setStatusCode(400)->setBody('UHID is required.');
        }

        $patientTable = $this->db->tableExists('patient_master_exten')
            ? 'patient_master_exten'
            : ($this->db->tableExists('patient_master') ? 'patient_master' : null);

        if ($patientTable === null) {
            return $this->response->setStatusCode(404)->setBody('Patient table not found.');
        }

        $patientFields = $this->db->getFieldNames($patientTable) ?? [];
        $patient = null;

        if (in_array('p_code', $patientFields, true)) {
            $patient = $this->db->table($patientTable)
                ->where('TRIM(p_code)', $pCode)
                ->get()
                ->getRow();

            if (! $patient) {
                $patient = $this->db->table($patientTable)
                    ->where("LOWER(TRIM(p_code)) = " . $this->db->escape(strtolower($pCode)), null, false)
                    ->get()
                    ->getRow();
            }
        }

        if (! $patient && ctype_digit($pCode)) {
            $patient = $this->db->table($patientTable)->where('id', (int) $pCode)->get()->getRow();
        }

        if (! $this->db->tableExists('invoice_med_master')) {
            return $this->response->setStatusCode(404)->setBody('Invoice table not found.');
        }

        $invoiceFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $invoiceBuilder = $this->db->table('invoice_med_master')
            ->where("DATE(inv_date) >= '{$dateFrom}'", null, false)
            ->where("DATE(inv_date) <= '{$dateTo}'", null, false);

        $hasPatientId = in_array('patient_id', $invoiceFields, true);
        $hasPatientCode = in_array('patient_code', $invoiceFields, true);

        $patientId = (int) ($patient->id ?? 0);
        $patientCode = trim((string) ($patient->p_code ?? $pCode));

        if ($hasPatientId && $hasPatientCode) {
            $invoiceBuilder->groupStart();
            if ($patientId > 0) {
                $invoiceBuilder->orWhere('patient_id', $patientId);
            }
            if ($patientCode !== '') {
                $invoiceBuilder->orWhere('TRIM(patient_code)', $patientCode);
                $invoiceBuilder->orWhere("LOWER(TRIM(patient_code)) = " . $this->db->escape(strtolower($patientCode)), null, false);
            }
            $invoiceBuilder->groupEnd();
        } elseif ($hasPatientId) {
            if ($patientId <= 0) {
                return $this->response->setStatusCode(404)->setBody('No patient found for UHID: ' . esc($pCode));
            }
            $invoiceBuilder->where('patient_id', $patientId);
        } elseif ($hasPatientCode) {
            $invoiceBuilder->groupStart()
                ->where('TRIM(patient_code)', $patientCode)
                ->orWhere("LOWER(TRIM(patient_code)) = " . $this->db->escape(strtolower($patientCode)), null, false)
                ->groupEnd();
        } else {
            return $this->response->setStatusCode(404)->setBody('Patient reference columns not found in invoice table.');
        }

        if (in_array('sale_return', $invoiceFields, true)) {
            $invoiceBuilder->where('IFNULL(sale_return,0)=0', null, false);
        }

        $invoices = $invoiceBuilder->orderBy('inv_date', 'ASC')->orderBy('id', 'ASC')->get()->getResult();

        if (empty($invoices)) {
            return $this->response->setStatusCode(404)->setBody('No invoices found for UHID ' . esc($pCode) . ' in selected date range.');
        }

        $invoiceIds = array_values(array_filter(array_map(static fn($row) => (int) ($row->id ?? 0), $invoices)));
        $itemsByInvoice = [];
        $paymentsByInvoice = [];

        if (! empty($invoiceIds) && $this->db->tableExists('inv_med_item')) {
            $itemBuilder = $this->db->table('inv_med_item')->whereIn('inv_med_id', $invoiceIds);
            $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
            if (in_array('id', $itemFields, true)) {
                $itemBuilder->orderBy('id', 'ASC');
            }

            $itemRows = $itemBuilder->get()->getResult();
            foreach ($itemRows as $itemRow) {
                $invId = (int) ($itemRow->inv_med_id ?? 0);
                if (! isset($itemsByInvoice[$invId])) {
                    $itemsByInvoice[$invId] = [];
                }
                $itemsByInvoice[$invId][] = $itemRow;
            }
        }

        if (! empty($invoiceIds) && $this->db->tableExists('payment_history_medical')) {
            $payFields = $this->db->getFieldNames('payment_history_medical') ?? [];
            if (in_array('Medical_invoice_id', $payFields, true)) {
                $payBuilder = $this->db->table('payment_history_medical')
                    ->whereIn('Medical_invoice_id', $invoiceIds)
                    ->orderBy('id', 'ASC');

                $paymentRows = $payBuilder->get()->getResult();
                foreach ($paymentRows as $paymentRow) {
                    $invId = (int) ($paymentRow->Medical_invoice_id ?? 0);
                    if (! isset($paymentsByInvoice[$invId])) {
                        $paymentsByInvoice[$invId] = [];
                    }
                    $paymentsByInvoice[$invId][] = $paymentRow;
                }
            }
        }

        $grand = [
            'net' => 0.0,
            'paid' => 0.0,
            'balance' => 0.0,
        ];

        foreach ($invoices as $invoice) {
            $grand['net'] += (float) ($invoice->net_amount ?? 0);
            $grand['paid'] += (float) ($invoice->payment_received ?? 0);
            $grand['balance'] += (float) ($invoice->payment_balance ?? 0);
        }

        $html = view('medical/uhid_report_pdf', [
            'patient' => $patient,
            'invoices' => $invoices,
            'itemsByInvoice' => $itemsByInvoice,
            'paymentsByInvoice' => $paymentsByInvoice,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'grand' => $grand,
            'searchUhid' => $pCode,
        ]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $mpdf->SetTitle('UHID Bill Report ' . $pCode);
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('UHID_Bill_Report_' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $pCode) . '_' . date('Ymd_His') . '.pdf', 'S'));
    }

    public function report_med_patient()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/report_med_patient', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function report_4()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/report_ipd_sale', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function report_4_data($dateRange, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $output = (int) $output;

        $rows = $this->getIpdSaleReportRows($dateFrom, $dateTo);

        if ($output === 1) {
            $content = view('medical/report_ipd_sale_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report_IPD_Sale_' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_ipd_sale_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('IPD Sale Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report_IPD_Sale_' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_ipd_sale_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function report_ipd_credit_bills()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/report_ipd_credit', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function report_ipd_credit_data($dateRange, $billType = 0, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $billType = (int) $billType;
        $output = (int) $output;

        $rows = $this->getIpdCreditBillTypeRows($dateFrom, $dateTo, $billType);

        if ($output === 1) {
            $content = view('medical/report_ipd_credit_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report_IPD_Credit_Bills_' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_ipd_credit_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('IPD Invoice Bill Type Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report_IPD_Credit_Bills_' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_ipd_credit_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function org_bills()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $insuranceRows = [];
        if ($this->db->tableExists('hc_insurance')) {
            $fields = $this->db->getFieldNames('hc_insurance') ?? [];
            $builder = $this->db->table('hc_insurance');
            if (in_array('id', $fields, true)) {
                $builder->where('id >', 1);
            }
            if (in_array('active', $fields, true)) {
                $builder->where('active', 1);
            }
            $orderCol = in_array('ins_company_name', $fields, true) ? 'ins_company_name' : 'id';
            $insuranceRows = $builder->orderBy($orderCol, 'ASC')->get()->getResult();
        }

        return view('medical/report_org_bills', [
            'today' => date('Y-m-d'),
            'data_insurance' => $insuranceRows,
        ]);
    }

    public function org_bills_report($dateRange, $insuranceId = -1, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $insuranceId = (int) $insuranceId;
        $output = (int) $output;

        $rows = $this->getOrgBillsRows($dateFrom, $dateTo, $insuranceId);

        if ($output === 1) {
            $content = view('medical/report_org_bills_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Med_Invoice_org_' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_org_bills_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('OPD Org Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Med_Invoice_org_' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_org_bills_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function report_5()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/report_gst_invoice_list', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function report_3()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $gstr1Gstin = defined('H_Med_GST') ? strtoupper(trim((string) H_Med_GST)) : '';

        return view('medical/report_gst_sale', [
            'today' => date('Y-m-d'),
            'gstr1_gstin' => $gstr1Gstin,
        ]);
    }

    public function report_3_data($dateRange, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $output = (int) $output;

        $rows = $this->getGstSaleSummaryRows($dateFrom, $dateTo);

        if ($output === 1) {
            $content = view('medical/report_gst_sale_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report_Medical_gst_sale_' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_gst_sale_data_pdf', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('GST Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report_Medical_gst_sale_' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_gst_sale_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function report_3_gstr1($dateRange)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $gstin = defined('H_Med_GST')
            ? strtoupper(trim((string) H_Med_GST))
            : '';

        if ($gstin === '') {
            return $this->response
                ->setStatusCode(400)
                ->setBody('GSTIN not found. Please set H_Med_GST constant.');
        }

        $payload = $this->buildGstr1Payload($dateFrom, $dateTo, $gstin);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="GSTR1_' . $payload['fp'] . '_' . date('Ymd_His') . '.json"')
            ->setBody($json === false ? '{}' : $json);
    }

    public function report_5_data($dateRange, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $output = (int) $output;

        $rows = $this->getGstInvoiceListRows($dateFrom, $dateTo);

        if ($output === 1) {
            $content = view('medical/report_gst_invoice_list_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report_Medical_gst_invoice_' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_gst_invoice_list_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('GST Invoice List');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report_Medical_gst_invoice_' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_gst_invoice_list_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function report_5_hsndata($dateRange, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $output = (int) $output;

        $rows = $this->getGstInvoiceHsnRows($dateFrom, $dateTo);

        if ($output === 1) {
            $content = view('medical/report_gst_invoice_hsn_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report_Medical_gst_hsn_' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_gst_invoice_hsn_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('GST HSN Wise Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report_Medical_gst_hsn_' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_gst_invoice_hsn_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function report_daily_med_sale()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/report_daily_med_sale', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function report_daily_med_sale_data($dateRange, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $output = (int) $output;

        $rows = $this->getDailyMedicineSaleRows($dateFrom, $dateTo);

        if ($output === 1) {
            $content = view('medical/report_daily_med_sale_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report-MedicalSale-' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_daily_med_sale_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('Day wise Medicine Sale Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report-MedicalSale-' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_daily_med_sale_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function report_daily_med_sale_doc()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $docList = [];
        if ($this->db->tableExists('doctor_master')) {
            $docFields = $this->db->getFieldNames('doctor_master') ?? [];
            $docBuilder = $this->db->table('doctor_master');
            if (in_array('active', $docFields, true)) {
                $docBuilder->where('active', 1);
            }
            if (in_array('p_fname', $docFields, true)) {
                $docBuilder->orderBy('p_fname', 'ASC');
            } else {
                $docBuilder->orderBy('id', 'ASC');
            }
            $docList = $docBuilder->get()->getResult();
        }

        return view('medical/report_daily_med_sale_doc', [
            'today' => date('Y-m-d'),
            'doclist' => $docList,
        ]);
    }

    public function report_daily_med_sale_doc_data($dateRange, $docId = '0', $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $docId = trim((string) $docId);
        $output = (int) $output;

        $rows = $this->getDailyMedicineSaleDocRows($dateFrom, $dateTo, $docId);

        if ($output === 1) {
            $content = view('medical/report_daily_med_sale_doc_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report-MedicalSale-Doc-' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_daily_med_sale_doc_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('Day wise & Doc Wise Medicine Sale Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report-MedicalSale-Doc-' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_daily_med_sale_doc_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function report_company_med_sale()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $companyList = [];
        if ($this->db->tableExists('med_company')) {
            $companyFields = $this->db->getFieldNames('med_company') ?? [];
            $nameCol = in_array('company_name', $companyFields, true) ? 'company_name' : 'id';
            $companyList = $this->db->table('med_company')
                ->orderBy($nameCol, 'ASC')
                ->get()
                ->getResult();
        }

        return view('medical/report_company_med_sale', [
            'today' => date('Y-m-d'),
            'med_company' => $companyList,
        ]);
    }

    public function report_company_med_sale_data($dateRange, $companyId = '0', $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $companyId = (int) $companyId;
        $output = (int) $output;

        $rows = $this->getCompanyWiseMedicineSaleRows($dateFrom, $dateTo, $companyId);

        if ($output === 1) {
            $content = view('medical/report_company_med_sale_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report-MedicalCompanyWiseSale-' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_company_med_sale_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('Company Wise Medicine Sale Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report-MedicalCompanyWiseSale-' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_company_med_sale_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    public function short_medicine()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $formulations = [];
        if ($this->db->tableExists('med_formulation')) {
            $fFields = $this->db->getFieldNames('med_formulation') ?? [];
            $orderCol = in_array('formulation_length', $fFields, true) ? 'formulation_length' : 'id';
            $formulations = $this->db->table('med_formulation')->orderBy($orderCol, 'ASC')->get()->getResult();
        }

        return view('medical/report_short_medicine', [
            'today' => date('Y-m-d'),
            'med_formulation' => $formulations,
        ]);
    }

    public function short_medicine_data($dateRange, $formulation = '0', $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $formulation = trim((string) $formulation);
        $output = (int) $output;

        $rows = $this->getShortMedicineRows($dateFrom, $dateTo, $formulation);

        if ($output === 1) {
            $content = view('medical/report_short_medicine_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);
            ExportExcel($content, 'Report_Medicine_Short_' . date('YmdHis'));
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_short_medicine_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'showHeader' => true,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('Short Medicine Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Report_Medicine_Short_' . date('YmdHis') . '.pdf', 'S'));
        }

        return view('medical/report_short_medicine_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'showHeader' => false,
        ]);
    }

    private function getDailyMedicineSaleRows(string $dateFrom, string $dateTo): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('inv_med_item')) {
            return [];
        }

        $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
        $stockFields = $this->db->tableExists('purchase_invoice_item')
            ? ($this->db->getFieldNames('purchase_invoice_item') ?? [])
            : [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $invDateField = $resolveField($masterFields, ['inv_date']);
        if ($invDateField === null) {
            return [];
        }

        $saleReturnMasterField = $resolveField($masterFields, ['sale_return']);
        $saleReturnItemField = $resolveField($itemFields, ['sale_return']);
        $itemCodeField = $resolveField($itemFields, ['item_code']);
        $itemNameField = $resolveField($itemFields, ['item_Name', 'item_name', 'Item_name']);
        $qtyField = $resolveField($itemFields, ['qty', 'unit']);
        $amountField = $resolveField($itemFields, ['twdisc_amount', 'tamount', 'amount']);
        $storeStockField = $resolveField($itemFields, ['store_stock_id']);
        $cgstField = $resolveField($itemFields, ['CGST', 'c_gst_amt', 'cgst', 'cgst_amt']);
        $sgstField = $resolveField($itemFields, ['SGST', 's_gst_amt', 'sgst', 'sgst_amt']);
        $cgstPerField = $resolveField($itemFields, ['CGST_per', 'cgst_per']);
        $sgstPerField = $resolveField($itemFields, ['SGST_per', 'sgst_per']);

        if ($itemCodeField === null || $qtyField === null) {
            return [];
        }

        $saleReturnExpr = $saleReturnMasterField !== null
            ? ('IFNULL(m.' . $saleReturnMasterField . ',0)')
            : ($saleReturnItemField !== null ? ('IFNULL(i.' . $saleReturnItemField . ',0)') : '0');

        $itemNameExpr = $itemNameField !== null ? ('i.' . $itemNameField) : 'CONCAT("Item-", i.' . $itemCodeField . ')';
        $amountExpr = $amountField !== null ? ('IFNULL(i.' . $amountField . ',0)') : '0';
        $qtyExpr = 'IFNULL(i.' . $qtyField . ',0)';
        $cgstExpr = $cgstField !== null ? ('IFNULL(i.' . $cgstField . ',0)') : '0';
        $sgstExpr = $sgstField !== null ? ('IFNULL(i.' . $sgstField . ',0)') : '0';
        $cgstPerExpr = $cgstPerField !== null ? ('IFNULL(i.' . $cgstPerField . ',0)') : '0';
        $sgstPerExpr = $sgstPerField !== null ? ('IFNULL(i.' . $sgstPerField . ',0)') : '0';
        $gstAmountExpr = 'CASE
                WHEN ((' . $cgstExpr . ' + ' . $sgstExpr . ') > 0) THEN (' . $cgstExpr . ' + ' . $sgstExpr . ')
                WHEN ((' . $cgstPerExpr . ' + ' . $sgstPerExpr . ') > 0) THEN ((' . $amountExpr . ' * (' . $cgstPerExpr . ' + ' . $sgstPerExpr . ')) / 100)
                ELSE 0
            END';

        $purchaseJoin = '';
        $purchaseRateExpr = '0';
        if ($storeStockField !== null && ! empty($stockFields)) {
            $rateField = $resolveField($stockFields, ['purchase_unit_rate']);
            $stockIdField = $resolveField($stockFields, ['id']);
            if ($rateField !== null && $stockIdField !== null) {
                $purchaseJoin = ' LEFT JOIN purchase_invoice_item p ON i.' . $storeStockField . '=p.' . $stockIdField;
                $purchaseRateExpr = 'IFNULL(p.' . $rateField . ',0)';
            }
        }

        $curQtyJoin = '';
        $curQtyExpr = '0';
        if (! empty($stockFields)) {
            $stockItemCodeField = $resolveField($stockFields, ['item_code']);
            $totalUnitField = $resolveField($stockFields, ['total_unit']);
            $totalSaleUnitField = $resolveField($stockFields, ['total_sale_unit']);
            $totalReturnUnitField = $resolveField($stockFields, ['total_return_unit']);
            $totalLostUnitField = $resolveField($stockFields, ['total_lost_unit']);

            if ($stockItemCodeField !== null && $totalUnitField !== null && $totalSaleUnitField !== null && $totalReturnUnitField !== null && $totalLostUnitField !== null) {
                $curQtyJoin = ' LEFT JOIN (
                        SELECT t.' . $stockItemCodeField . ' AS item_code,
                               SUM(IFNULL(t.' . $totalUnitField . ',0)-IFNULL(t.' . $totalSaleUnitField . ',0)-IFNULL(t.' . $totalReturnUnitField . ',0)-IFNULL(t.' . $totalLostUnitField . ',0)) AS cur_qty
                        FROM purchase_invoice_item t
                        GROUP BY t.' . $stockItemCodeField . '
                    ) q ON q.item_code=i.' . $itemCodeField;
                $curQtyExpr = 'IFNULL(q.cur_qty,0)';
            }
        }

        $sql = "SELECT
                i.{$itemCodeField} AS item_code,
                {$itemNameExpr} AS item_name,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN {$qtyExpr} ELSE 0 END) AS sale_qty,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN {$amountExpr} ELSE 0 END) AS sale_amount,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN ({$qtyExpr}*{$purchaseRateExpr}) ELSE 0 END) AS purchase_amount,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN ({$gstAmountExpr}) ELSE 0 END) AS sale_gst,
                SUM(CASE WHEN {$saleReturnExpr}=1 THEN {$qtyExpr} ELSE 0 END) AS return_qty,
                SUM(CASE WHEN {$saleReturnExpr}=1 THEN {$amountExpr} ELSE 0 END) AS return_amount,
                SUM(CASE WHEN {$saleReturnExpr}=1 THEN ({$gstAmountExpr}) ELSE 0 END) AS return_gst,
                SUM(CASE WHEN {$saleReturnExpr}=1 THEN ({$qtyExpr}*{$purchaseRateExpr}) ELSE 0 END) AS return_purchase_amount,
                {$curQtyExpr} AS cur_qty
            FROM invoice_med_master m
            JOIN inv_med_item i ON m.id=i.inv_med_id
            {$purchaseJoin}
            {$curQtyJoin}
            WHERE DATE(m.{$invDateField}) BETWEEN ? AND ?
            GROUP BY i.{$itemCodeField}, {$itemNameExpr}
            ORDER BY {$itemNameExpr}";

        return $this->db->query($sql, [$dateFrom, $dateTo])->getResultArray();
    }

    private function getIpdSaleReportRows(string $dateFrom, string $dateTo): array
    {
        if (! $this->db->tableExists('invoice_med_master')) {
            return [];
        }

        $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $ipdViewFields = $this->db->tableExists('v_ipd_list') ? ($this->db->getFieldNames('v_ipd_list') ?? []) : [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $idField = $resolveField($masterFields, ['id']);
        $ipdIdField = $resolveField($masterFields, ['ipd_id']);
        $ipdCodeField = $resolveField($masterFields, ['ipd_code']);
        $patientCodeField = $resolveField($masterFields, ['patient_code', 'p_code']);
        $patientNameField = $resolveField($masterFields, ['inv_name', 'patient_name']);
        $invCodeField = $resolveField($masterFields, ['inv_med_code', 'inv_no']);
        $invIdField = $resolveField($masterFields, ['id']);
        $invDateField = $resolveField($masterFields, ['inv_date']);
        $netAmountField = $resolveField($masterFields, ['net_amount']);
        $ipdCreditField = $resolveField($masterFields, ['ipd_credit']);
        $ipdCreditTypeField = $resolveField($masterFields, ['ipd_credit_type']);

        if ($idField === null || $ipdIdField === null || $invDateField === null || $netAmountField === null) {
            return [];
        }

        $joinSql = '';
        $where = [
            'IFNULL(m.' . $ipdIdField . ',0) > 0',
        ];
        $params = [];

        if (! empty($ipdViewFields)) {
            $vIdField = $resolveField($ipdViewFields, ['id']);
            $vStatusField = $resolveField($ipdViewFields, ['ipd_status']);
            $vDischargeField = $resolveField($ipdViewFields, ['discharge_date']);
            if ($vIdField !== null) {
                $joinSql = ' JOIN v_ipd_list v ON m.' . $ipdIdField . '=v.' . $vIdField;
                if ($vStatusField !== null) {
                    $where[] = 'IFNULL(v.' . $vStatusField . ',0)=1';
                }
                if ($vDischargeField !== null) {
                    $where[] = 'DATE(v.' . $vDischargeField . ') BETWEEN ? AND ?';
                    $params[] = $dateFrom;
                    $params[] = $dateTo;
                }
            }
        }

        if (empty($params)) {
            $where[] = 'DATE(m.' . $invDateField . ') BETWEEN ? AND ?';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }

        $ipdCodeExpr = $ipdCodeField !== null
            ? 'NULLIF(TRIM(m.' . $ipdCodeField . '),"")'
            : ('CONCAT("IPD-", m.' . $ipdIdField . ')');
        $patientCodeExpr = $patientCodeField !== null ? ('NULLIF(TRIM(m.' . $patientCodeField . '),"")') : "'-'";
        $patientNameExpr = $patientNameField !== null ? ('NULLIF(TRIM(m.' . $patientNameField . '),"")') : "'-'";
        $invCodeExpr = $invCodeField !== null ? ('NULLIF(TRIM(m.' . $invCodeField . '),"")') : ('CAST(m.' . $idField . ' AS CHAR)');
        $invDateExpr = 'DATE_FORMAT(m.' . $invDateField . ', "%d-%m-%Y")';
        $netExpr = 'IFNULL(m.' . $netAmountField . ',0)';
        $ipdCreditExpr = $ipdCreditField !== null ? ('IFNULL(m.' . $ipdCreditField . ',0)') : '0';
        $ipdCreditTypeExpr = $ipdCreditTypeField !== null ? ('IFNULL(m.' . $ipdCreditTypeField . ',0)') : '0';

        $tpaExpr = "'-'";
        if (! empty($ipdViewFields)) {
            $tpaFields = ['ins_company_name', 'insurance_company_name', 'short_name'];
            $parts = [];
            foreach ($tpaFields as $field) {
                $vf = $resolveField($ipdViewFields, [$field]);
                if ($vf !== null) {
                    $parts[] = 'NULLIF(TRIM(v.' . $vf . '),"")';
                }
            }
            if (! empty($parts)) {
                $tpaExpr = 'COALESCE(' . implode(',', $parts) . ', "-")';
            }
        }

        $sql = "SELECT
                m.{$idField} AS id,
                m.{$ipdIdField} AS ipd_id,
                COALESCE({$ipdCodeExpr}, CONCAT('IPD-',m.{$ipdIdField})) AS ipd_code,
                COALESCE({$patientCodeExpr}, '-') AS patient_code,
                COALESCE({$patientNameExpr}, '-') AS patient_name,
                {$tpaExpr} AS tpa_name,
                COALESCE({$invCodeExpr}, CAST(m.{$idField} AS CHAR)) AS inv_med_code,
                {$invDateExpr} AS inv_date_str,
                CASE WHEN {$ipdCreditExpr}=1 AND {$ipdCreditTypeExpr}=1 THEN {$netExpr} ELSE 0 END AS ipd_credit_amount,
                CASE WHEN {$ipdCreditExpr}=1 AND {$ipdCreditTypeExpr}=0 THEN {$netExpr} ELSE 0 END AS ipd_package_amount,
                CASE WHEN {$ipdCreditExpr}=0 THEN {$netExpr} ELSE 0 END AS ipd_cash_amount,
                {$netExpr} AS ipd_total_amount
            FROM invoice_med_master m
            {$joinSql}
            WHERE " . implode(' AND ', $where) . "
            ORDER BY m.{$ipdIdField} ASC, m.{$idField} ASC";

        return $this->db->query($sql, $params)->getResultArray();
    }

    private function getIpdCreditBillTypeRows(string $dateFrom, string $dateTo, int $billType): array
    {
        if (! $this->db->tableExists('invoice_med_master')) {
            return [];
        }

        $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $ipdViewFields = $this->db->tableExists('v_ipd_list') ? ($this->db->getFieldNames('v_ipd_list') ?? []) : [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $idField = $resolveField($masterFields, ['id']);
        $ipdIdField = $resolveField($masterFields, ['ipd_id']);
        $ipdCodeField = $resolveField($masterFields, ['ipd_code']);
        $patientCodeField = $resolveField($masterFields, ['patient_code', 'p_code']);
        $patientNameField = $resolveField($masterFields, ['inv_name', 'patient_name']);
        $invCodeField = $resolveField($masterFields, ['inv_med_code', 'inv_no']);
        $invDateField = $resolveField($masterFields, ['inv_date']);
        $netAmountField = $resolveField($masterFields, ['net_amount']);
        $ipdCreditField = $resolveField($masterFields, ['ipd_credit']);
        $ipdCreditTypeField = $resolveField($masterFields, ['ipd_credit_type']);

        if ($idField === null || $ipdIdField === null || $invDateField === null || $netAmountField === null) {
            return [];
        }

        $joinSql = '';
        $where = [
            'IFNULL(m.' . $ipdIdField . ',0) > 0',
        ];
        $params = [];

        if (! empty($ipdViewFields)) {
            $vIdField = $resolveField($ipdViewFields, ['id']);
            $vStatusField = $resolveField($ipdViewFields, ['ipd_status']);
            $vDischargeField = $resolveField($ipdViewFields, ['discharge_date']);
            if ($vIdField !== null) {
                $joinSql = ' JOIN v_ipd_list v ON m.' . $ipdIdField . '=v.' . $vIdField;
                if ($vStatusField !== null) {
                    $where[] = 'IFNULL(v.' . $vStatusField . ',0)=1';
                }
                if ($vDischargeField !== null) {
                    $where[] = 'DATE(v.' . $vDischargeField . ') BETWEEN ? AND ?';
                    $params[] = $dateFrom;
                    $params[] = $dateTo;
                }
            }
        }

        if (empty($params)) {
            $where[] = 'DATE(m.' . $invDateField . ') BETWEEN ? AND ?';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }

        $ipdCreditExpr = $ipdCreditField !== null ? ('IFNULL(m.' . $ipdCreditField . ',0)') : '0';
        $ipdCreditTypeExpr = $ipdCreditTypeField !== null ? ('IFNULL(m.' . $ipdCreditTypeField . ',0)') : '0';

        if ($billType === 1) {
            $where[] = $ipdCreditExpr . '=0';
        } elseif ($billType === 2) {
            $where[] = $ipdCreditExpr . '=1 AND ' . $ipdCreditTypeExpr . '=1';
        } elseif ($billType === 3) {
            $where[] = $ipdCreditExpr . '=1 AND ' . $ipdCreditTypeExpr . '=0';
        } elseif ($billType !== 0) {
            $where[] = '1<>1';
        }

        $ipdCodeExpr = $ipdCodeField !== null
            ? 'NULLIF(TRIM(m.' . $ipdCodeField . '),"")'
            : ('CONCAT("IPD-", m.' . $ipdIdField . ')');
        $patientCodeExpr = $patientCodeField !== null ? ('NULLIF(TRIM(m.' . $patientCodeField . '),"")') : "'-'";
        $patientNameExpr = $patientNameField !== null ? ('NULLIF(TRIM(m.' . $patientNameField . '),"")') : "'-'";
        $invCodeExpr = $invCodeField !== null ? ('NULLIF(TRIM(m.' . $invCodeField . '),"")') : ('CAST(m.' . $idField . ' AS CHAR)');
        $invDateExpr = 'DATE_FORMAT(m.' . $invDateField . ', "%d-%m-%Y")';
        $amountExpr = 'IFNULL(m.' . $netAmountField . ',0)';

        $tpaExpr = "'-'";
        if (! empty($ipdViewFields)) {
            $tpaFields = ['ins_company_name', 'insurance_company_name', 'short_name'];
            $parts = [];
            foreach ($tpaFields as $field) {
                $vf = $resolveField($ipdViewFields, [$field]);
                if ($vf !== null) {
                    $parts[] = 'NULLIF(TRIM(v.' . $vf . '),"")';
                }
            }
            if (! empty($parts)) {
                $tpaExpr = 'COALESCE(' . implode(',', $parts) . ', "-")';
            }
        }

        $billTypeExpr = 'CASE WHEN ' . $ipdCreditExpr . '=0 THEN "CASH" WHEN ' . $ipdCreditTypeExpr . '=1 THEN "Credit" ELSE "Package" END';

        $sql = "SELECT
                m.{$idField} AS id,
                m.{$ipdIdField} AS ipd_id,
                COALESCE({$ipdCodeExpr}, CONCAT('IPD-',m.{$ipdIdField})) AS ipd_code,
                COALESCE({$patientCodeExpr}, '-') AS patient_code,
                COALESCE({$patientNameExpr}, '-') AS patient_name,
                {$tpaExpr} AS tpa_name,
                COALESCE({$invCodeExpr}, CAST(m.{$idField} AS CHAR)) AS inv_med_code,
                {$invDateExpr} AS inv_date_str,
                {$billTypeExpr} AS bill_type,
                {$amountExpr} AS ipd_total_amount
            FROM invoice_med_master m
            {$joinSql}
            WHERE " . implode(' AND ', $where) . "
            ORDER BY m.{$ipdIdField} ASC, m.{$idField} ASC";

        return $this->db->query($sql, $params)->getResultArray();
    }

    private function getOrgBillsRows(string $dateFrom, string $dateTo, int $insuranceId): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('organization_case_master')) {
            return [];
        }

        $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $orgFields = $this->db->getFieldNames('organization_case_master') ?? [];
        $insFields = $this->db->tableExists('hc_insurance') ? ($this->db->getFieldNames('hc_insurance') ?? []) : [];
        $patientFields = $this->db->tableExists('patient_master') ? ($this->db->getFieldNames('patient_master') ?? []) : [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $invCodeField = $resolveField($masterFields, ['inv_med_code', 'inv_no']);
        $invDateField = $resolveField($masterFields, ['inv_date']);
        $caseIdField = $resolveField($masterFields, ['case_id']);
        $caseCreditField = $resolveField($masterFields, ['case_credit']);
        $netAmountField = $resolveField($masterFields, ['net_amount']);
        $patientIdField = $resolveField($masterFields, ['patient_id']);
        $invNameField = $resolveField($masterFields, ['inv_name']);
        $patientCodeField = $resolveField($masterFields, ['patient_code']);

        $orgIdField = $resolveField($orgFields, ['id']);
        $orgCodeField = $resolveField($orgFields, ['case_id_code', 'case_code']);
        $orgInsuranceField = $resolveField($orgFields, ['insurance_id']);

        if ($invDateField === null || $caseIdField === null || $caseCreditField === null || $netAmountField === null || $orgIdField === null) {
            return [];
        }

        $joins = [
            'JOIN organization_case_master o ON m.' . $caseIdField . '=o.' . $orgIdField,
        ];

        $patientCodeExpr = $patientCodeField !== null ? ('NULLIF(TRIM(m.' . $patientCodeField . '),"")') : "NULL";
        $patientNameExpr = $invNameField !== null ? ('NULLIF(TRIM(m.' . $invNameField . '),"")') : "NULL";
        if (! empty($patientFields) && $patientIdField !== null) {
            $pIdField = $resolveField($patientFields, ['id']);
            if ($pIdField !== null) {
                $joins[] = 'LEFT JOIN patient_master p ON m.' . $patientIdField . '=p.' . $pIdField;
                $pCodeField = $resolveField($patientFields, ['p_code', 'patient_code']);
                $pNameField = $resolveField($patientFields, ['p_fname', 'name']);
                if ($pCodeField !== null) {
                    $patientCodeExpr = 'COALESCE(NULLIF(TRIM(p.' . $pCodeField . '),""), ' . $patientCodeExpr . ', "-")';
                }
                if ($pNameField !== null) {
                    $patientNameExpr = 'COALESCE(NULLIF(TRIM(p.' . $pNameField . '),""), ' . $patientNameExpr . ', "-")';
                }
            }
        }
        if (strpos($patientCodeExpr, 'COALESCE(') !== 0) {
            $patientCodeExpr = 'COALESCE(' . $patientCodeExpr . ', "-")';
        }
        if (strpos($patientNameExpr, 'COALESCE(') !== 0) {
            $patientNameExpr = 'COALESCE(' . $patientNameExpr . ', "-")';
        }

        $insuranceNameExpr = "'-'";
        if (! empty($insFields) && $orgInsuranceField !== null) {
            $insIdField = $resolveField($insFields, ['id']);
            if ($insIdField !== null) {
                $joins[] = 'LEFT JOIN hc_insurance i ON o.' . $orgInsuranceField . '=i.' . $insIdField;
                $insNameField = $resolveField($insFields, ['ins_company_name', 'short_name']);
                if ($insNameField !== null) {
                    $insuranceNameExpr = 'COALESCE(NULLIF(TRIM(i.' . $insNameField . '),""), "-")';
                }
            }
        }

        $where = [
            'IFNULL(m.' . $caseIdField . ',0)>1',
            'IFNULL(m.' . $caseCreditField . ',0)=1',
            'DATE(m.' . $invDateField . ') BETWEEN ? AND ?',
        ];
        $params = [$dateFrom, $dateTo];

        if ($insuranceId > 0 && $orgInsuranceField !== null) {
            $where[] = 'o.' . $orgInsuranceField . '=?';
            $params[] = $insuranceId;
        }

        $invCodeExpr = $invCodeField !== null ? ('m.' . $invCodeField) : "''";
        $orgCodeExpr = $orgCodeField !== null ? ('o.' . $orgCodeField) : "''";

        $sql = 'SELECT '
            . $invCodeExpr . ' AS inv_med_code,'
            . $orgCodeExpr . ' AS case_id_code,'
            . $patientCodeExpr . ' AS p_code,'
            . $patientNameExpr . ' AS p_fname,'
            . 'IFNULL(m.' . $netAmountField . ',0) AS net_amount,'
            . $insuranceNameExpr . ' AS ins_company_name '
            . 'FROM invoice_med_master m '
            . implode(' ', $joins)
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY m.' . $invDateField . ' ASC'
            . ($invIdField !== null ? (', m.' . $invIdField . ' ASC') : '');

        return $this->db->query($sql, $params)->getResultArray();
    }

    private function getGstInvoiceListRows(string $dateFrom, string $dateTo): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('inv_med_item')) {
            return [];
        }

        $mFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $iFields = $this->db->getFieldNames('inv_med_item') ?? [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $mid = $resolveField($mFields, ['id']);
        $invDate = $resolveField($mFields, ['inv_date']);
        $invCode = $resolveField($mFields, ['inv_med_code', 'inv_no']);
        $invName = $resolveField($mFields, ['inv_name']);
        $netAmount = $resolveField($mFields, ['net_amount']);
        $ipdId = $resolveField($mFields, ['ipd_id']);
        $ipdCredit = $resolveField($mFields, ['ipd_credit']);
        $ipdCreditType = $resolveField($mFields, ['ipd_credit_type']);
        $caseId = $resolveField($mFields, ['case_id']);
        $caseCredit = $resolveField($mFields, ['case_credit']);

        $invMedId = $resolveField($iFields, ['inv_med_id']);
        $itemId = $resolveField($iFields, ['id']);
        $qty = $resolveField($iFields, ['qty', 'unit']);
        $lineAmount = $resolveField($iFields, ['twdisc_amount', 'tamount', 'amount']);
        $taxable = $resolveField($iFields, ['TaxableAmount', 'taxableamount']);
        $cgstPer = $resolveField($iFields, ['CGST_per', 'cgst_per']);
        $cgst = $resolveField($iFields, ['CGST', 'c_gst_amt', 'cgst', 'cgst_amt']);
        $sgst = $resolveField($iFields, ['SGST', 's_gst_amt', 'sgst', 'sgst_amt']);

        if ($mid === null || $invDate === null || $invMedId === null) {
            return [];
        }

        $qtyExpr = $qty !== null ? ('IFNULL(i.' . $qty . ',0)') : '0';
        $lineAmountExpr = $lineAmount !== null ? ('IFNULL(i.' . $lineAmount . ',0)') : '0';
        $taxableExpr = $taxable !== null ? ('IFNULL(i.' . $taxable . ',0)') : $lineAmountExpr;
        $cgstPerExpr = $cgstPer !== null ? ('IFNULL(i.' . $cgstPer . ',0)') : '0';
        $cgstExpr = $cgst !== null ? ('IFNULL(i.' . $cgst . ',0)') : '((' . $taxableExpr . ' * ' . $cgstPerExpr . ')/100)';
        $sgstExpr = $sgst !== null ? ('IFNULL(i.' . $sgst . ',0)') : '((' . $taxableExpr . ' * ' . $cgstPerExpr . ')/100)';

        $invTypeExpr = "'CASH'";
        if ($ipdId !== null && $ipdCredit !== null) {
            $invTypeExpr = 'CASE '
                . 'WHEN IFNULL(m.' . $ipdId . ',0)>0 AND IFNULL(m.' . $ipdCredit . ',0)=0 THEN "IPD CASH" '
                . 'WHEN IFNULL(m.' . $ipdId . ',0)>0 AND IFNULL(m.' . $ipdCredit . ',0)=1 AND IFNULL(m.' . ($ipdCreditType ?? $ipdCredit) . ',0)=1 THEN "IPD CREDIT" '
                . 'WHEN IFNULL(m.' . $ipdId . ',0)>0 AND IFNULL(m.' . $ipdCredit . ',0)=1 THEN "IPD PACKAGE" '
                . 'WHEN ' . ($caseId !== null ? ('IFNULL(m.' . $caseId . ',0)>1') : '0') . ' AND ' . ($caseCredit !== null ? ('IFNULL(m.' . $caseCredit . ',0)=1') : '0') . ' THEN "OPD ORG" '
                . 'ELSE "OPD CASH" END';
        }

        $sql = 'SELECT '
            . ($invCode !== null ? 'm.' . $invCode : ('CAST(m.' . $mid . ' AS CHAR)')) . ' AS inv_med_code,'
            . 'DATE_FORMAT(m.' . $invDate . ', "%d-%m-%Y") AS inv_date,'
            . ($invName !== null ? 'm.' . $invName : "'-'") . ' AS inv_name,'
            . $invTypeExpr . ' AS invoice_type,'
            . 'COUNT(i.' . ($itemId ?? $invMedId) . ') AS no_of_items,'
            . 'SUM(' . $qtyExpr . ') AS no_of_qty,'
            . 'ROUND(SUM(' . $lineAmountExpr . '),0) AS total_amount,'
            . 'ROUND(SUM(' . $taxableExpr . '),0) AS total_taxable_amount,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=2.5 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_5_amount,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=2.5 THEN ' . $cgstExpr . ' ELSE 0 END) AS cgst_2_5,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=2.5 THEN ' . $sgstExpr . ' ELSE 0 END) AS sgst_2_5,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=6 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_12_amount,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=6 THEN ' . $cgstExpr . ' ELSE 0 END) AS cgst_6,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=6 THEN ' . $sgstExpr . ' ELSE 0 END) AS sgst_6,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=9 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_18_amount,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=9 THEN ' . $cgstExpr . ' ELSE 0 END) AS cgst_9,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=9 THEN ' . $sgstExpr . ' ELSE 0 END) AS sgst_9,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=14 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_28_amount,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=14 THEN ' . $cgstExpr . ' ELSE 0 END) AS cgst_14,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=14 THEN ' . $sgstExpr . ' ELSE 0 END) AS sgst_14,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=0 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_0_amount '
            . 'FROM invoice_med_master m '
            . 'JOIN inv_med_item i ON m.' . $mid . '=i.' . $invMedId . ' '
            . 'WHERE DATE(m.' . $invDate . ') BETWEEN ? AND ? AND IFNULL(' . ($netAmount !== null ? ('m.' . $netAmount) : '0') . ',0)>0 '
            . 'GROUP BY m.' . $mid . ' '
            . 'ORDER BY m.' . $invDate . ' ASC, m.' . $mid . ' ASC';

        return $this->db->query($sql, [$dateFrom, $dateTo])->getResultArray();
    }

    private function getGstSaleSummaryRows(string $dateFrom, string $dateTo): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('inv_med_item')) {
            return [];
        }

        $mFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $iFields = $this->db->getFieldNames('inv_med_item') ?? [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $mid = $resolveField($mFields, ['id']);
        $invDate = $resolveField($mFields, ['inv_date']);
        $invMedId = $resolveField($iFields, ['inv_med_id']);

        $qty = $resolveField($iFields, ['qty', 'unit']);
        $lineAmount = $resolveField($iFields, ['twdisc_amount', 'tamount', 'amount']);
        $taxable = $resolveField($iFields, ['TaxableAmount', 'taxableamount']);
        $cgstPer = $resolveField($iFields, ['CGST_per', 'cgst_per']);
        $sgstPer = $resolveField($iFields, ['SGST_per', 'sgst_per']);
        $cgst = $resolveField($iFields, ['CGST', 'c_gst_amt', 'cgst', 'cgst_amt']);
        $sgst = $resolveField($iFields, ['SGST', 's_gst_amt', 'sgst', 'sgst_amt']);

        if ($mid === null || $invDate === null || $invMedId === null) {
            return [];
        }

        $qtyExpr = $qty !== null ? ('IFNULL(t.' . $qty . ',0)') : '0';
        $lineAmountExpr = $lineAmount !== null ? ('IFNULL(t.' . $lineAmount . ',0)') : '0';
        $taxableExpr = $taxable !== null ? ('IFNULL(t.' . $taxable . ',0)') : $lineAmountExpr;
        $cgstPerExpr = $cgstPer !== null ? ('IFNULL(t.' . $cgstPer . ',0)') : '0';
        $sgstPerExpr = $sgstPer !== null ? ('IFNULL(t.' . $sgstPer . ',0)') : $cgstPerExpr;
        $cgstExpr = $cgst !== null ? ('IFNULL(t.' . $cgst . ',0)') : '((' . $taxableExpr . ' * ' . $cgstPerExpr . ')/100)';
        $sgstExpr = $sgst !== null ? ('IFNULL(t.' . $sgst . ',0)') : '((' . $taxableExpr . ' * ' . $sgstPerExpr . ')/100)';

        $where = [
            'DATE(m.' . $invDate . ') BETWEEN ? AND ?',
        ];
        $params = [$dateFrom, $dateTo];

        $sql = 'SELECT '
            . $cgstPerExpr . ' AS cgst_per,'
            . $sgstPerExpr . ' AS sgst_per,'
            . 'SUM(' . $qtyExpr . ') AS t_qty,'
            . 'SUM(' . $cgstExpr . ') AS tcgst,'
            . 'SUM(' . $sgstExpr . ') AS tsgst,'
            . 'SUM(' . $cgstExpr . ' + ' . $sgstExpr . ') AS tgst,'
            . 'SUM(' . $taxableExpr . ') AS taxable_amount,'
            . 'SUM(' . $lineAmountExpr . ') AS amount '
            . 'FROM invoice_med_master m '
            . 'JOIN inv_med_item t ON m.' . $mid . '=t.' . $invMedId . ' '
            . 'WHERE ' . implode(' AND ', $where) . ' '
            . 'GROUP BY ' . $cgstPerExpr . ', ' . $sgstPerExpr . ' '
            . 'ORDER BY ' . $cgstPerExpr . ' ASC';

        $rows = $this->db->query($sql, $params)->getResultArray();
        if ($rows === []) {
            return [];
        }

        $totQty = 0.0;
        $totCgst = 0.0;
        $totSgst = 0.0;
        $totGst = 0.0;
        $totTaxable = 0.0;
        $totAmount = 0.0;

        foreach ($rows as $row) {
            $totQty += (float) ($row['t_qty'] ?? 0);
            $totCgst += (float) ($row['tcgst'] ?? 0);
            $totSgst += (float) ($row['tsgst'] ?? 0);
            $totGst += (float) ($row['tgst'] ?? 0);
            $totTaxable += (float) ($row['taxable_amount'] ?? 0);
            $totAmount += (float) ($row['amount'] ?? 0);
        }

        $rows[] = [
            'cgst_per' => null,
            'sgst_per' => null,
            't_qty' => $totQty,
            'tcgst' => $totCgst,
            'tsgst' => $totSgst,
            'tgst' => $totGst,
            'taxable_amount' => $totTaxable,
            'amount' => $totAmount,
            'is_total' => 1,
        ];

        return $rows;
    }

    private function buildGstr1Payload(string $dateFrom, string $dateTo, string $gstin): array
    {
        $saleRows = $this->getGstSaleSummaryRows($dateFrom, $dateTo);
        $hsnRows = $this->getGstInvoiceHsnRows($dateFrom, $dateTo);
        $docs = $this->getGstr1DocsSummary($dateFrom, $dateTo);

        $fp = date('mY', strtotime($dateTo));
        $pos = preg_match('/^\d{2}/', $gstin, $m) ? $m[0] : '00';

        $b2cs = [];
        $grandAmount = 0.0;
        foreach ($saleRows as $row) {
            if (!empty($row['is_total'])) {
                continue;
            }

            $txval = (float) ($row['taxable_amount'] ?? 0);
            $camt = (float) ($row['tcgst'] ?? 0);
            $samt = (float) ($row['tsgst'] ?? 0);
            $rate = round(((float) ($row['cgst_per'] ?? 0)) * 2, 2);

            if ($txval <= 0 && ($camt + $samt) <= 0) {
                continue;
            }

            $b2cs[] = [
                'sply_ty' => 'INTRA',
                'typ' => 'OE',
                'pos' => $pos,
                'rt' => $rate,
                'txval' => round($txval, 2),
                'iamt' => 0,
                'camt' => round($camt, 2),
                'samt' => round($samt, 2),
                'csamt' => 0,
            ];

            $grandAmount += (float) ($row['amount'] ?? 0);
        }

        $hsnData = [];
        $num = 1;
        foreach ($hsnRows as $row) {
            $txval = (float) ($row['total_taxable_amount'] ?? 0);
            $camt = (float) ($row['cgst_2_5'] ?? 0)
                + (float) ($row['cgst_6'] ?? 0)
                + (float) ($row['cgst_9'] ?? 0)
                + (float) ($row['cgst_14'] ?? 0);
            $samt = (float) ($row['sgst_2_5'] ?? 0)
                + (float) ($row['sgst_6'] ?? 0)
                + (float) ($row['sgst_9'] ?? 0)
                + (float) ($row['sgst_14'] ?? 0);

            $hsnData[] = [
                'num' => $num++,
                'hsn_sc' => (string) ($row['hsn_code'] ?? ''),
                'desc' => 'MEDICINE',
                'uqc' => 'NOS',
                'qty' => round((float) ($row['no_of_qty'] ?? 0), 3),
                'val' => round((float) ($row['total_amount'] ?? 0), 2),
                'txval' => round($txval, 2),
                'iamt' => 0,
                'camt' => round($camt, 2),
                'samt' => round($samt, 2),
                'csamt' => 0,
            ];
        }

        $totDocs = (int) ($docs['totnum'] ?? 0);
        $cancelDocs = (int) ($docs['cancel'] ?? 0);

        return [
            'gstin' => $gstin,
            'fp' => $fp,
            'version' => 'GST3.0',
            'gt' => round($grandAmount, 2),
            'cur_gt' => round($grandAmount, 2),
            'b2b' => [],
            'b2cs' => $b2cs,
            'cdnr' => [],
            'cdnur' => [],
            'exp' => [],
            'at' => [],
            'atadj' => [],
            'exemp' => [],
            'hsn' => [
                'data' => $hsnData,
            ],
            'docs' => [
                'doc_det' => [
                    [
                        'doc_num' => 1,
                        'doc_typ' => 'Invoices for outward supply',
                        'docs' => [
                            [
                                'num' => 1,
                                'from' => (string) ($docs['from_no'] ?? ''),
                                'to' => (string) ($docs['to_no'] ?? ''),
                                'totnum' => $totDocs,
                                'cancel' => $cancelDocs,
                                'net_issue' => max(0, $totDocs - $cancelDocs),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getGstr1DocsSummary(string $dateFrom, string $dateTo): array
    {
        if (! $this->db->tableExists('invoice_med_master')) {
            return [
                'from_no' => '',
                'to_no' => '',
                'totnum' => 0,
                'cancel' => 0,
            ];
        }

        $mFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $idField = $resolveField($mFields, ['id']);
        $invDate = $resolveField($mFields, ['inv_date']);
        $invCode = $resolveField($mFields, ['inv_med_code', 'inv_no']);
        $netAmount = $resolveField($mFields, ['net_amount']);
        $cancelField = $resolveField($mFields, ['is_cancel', 'is_cancelled', 'cancelled', 'is_deleted']);

        if ($idField === null || $invDate === null) {
            return [
                'from_no' => '',
                'to_no' => '',
                'totnum' => 0,
                'cancel' => 0,
            ];
        }

        $invoiceExpr = $invCode !== null ? ('m.' . $invCode) : ('CAST(m.' . $idField . ' AS CHAR)');
        $where = 'DATE(m.' . $invDate . ') BETWEEN ? AND ?';
        if ($netAmount !== null) {
            $where .= ' AND IFNULL(m.' . $netAmount . ',0) > 0';
        }

        $cancelExpr = $cancelField !== null ? ('SUM(CASE WHEN IFNULL(m.' . $cancelField . ',0)=1 THEN 1 ELSE 0 END)') : '0';

        $sql = 'SELECT '
            . 'MIN(' . $invoiceExpr . ') AS from_no,'
            . 'MAX(' . $invoiceExpr . ') AS to_no,'
            . 'COUNT(m.' . $idField . ') AS totnum,'
            . $cancelExpr . ' AS cancel '
            . 'FROM invoice_med_master m '
            . 'WHERE ' . $where;

        $row = $this->db->query($sql, [$dateFrom, $dateTo])->getRowArray() ?? [];

        return [
            'from_no' => (string) ($row['from_no'] ?? ''),
            'to_no' => (string) ($row['to_no'] ?? ''),
            'totnum' => (int) ($row['totnum'] ?? 0),
            'cancel' => (int) ($row['cancel'] ?? 0),
        ];
    }

    private function getGstInvoiceHsnRows(string $dateFrom, string $dateTo): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('inv_med_item')) {
            return [];
        }

        $mFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $iFields = $this->db->getFieldNames('inv_med_item') ?? [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $mid = $resolveField($mFields, ['id']);
        $invDate = $resolveField($mFields, ['inv_date']);
        $invMedId = $resolveField($iFields, ['inv_med_id']);
        $qty = $resolveField($iFields, ['qty', 'unit']);
        $lineAmount = $resolveField($iFields, ['twdisc_amount', 'tamount', 'amount']);
        $taxable = $resolveField($iFields, ['TaxableAmount', 'taxableamount']);
        $cgstPer = $resolveField($iFields, ['CGST_per', 'cgst_per']);
        $cgst = $resolveField($iFields, ['CGST', 'c_gst_amt', 'cgst', 'cgst_amt']);
        $sgst = $resolveField($iFields, ['SGST', 's_gst_amt', 'sgst', 'sgst_amt']);
        $hsn = $resolveField($iFields, ['HSNCODE', 'hsncode', 'hsn_code']);

        if ($mid === null || $invDate === null || $invMedId === null) {
            return [];
        }

        $qtyExpr = $qty !== null ? ('IFNULL(i.' . $qty . ',0)') : '0';
        $lineAmountExpr = $lineAmount !== null ? ('IFNULL(i.' . $lineAmount . ',0)') : '0';
        $taxableExpr = $taxable !== null ? ('IFNULL(i.' . $taxable . ',0)') : $lineAmountExpr;
        $cgstPerExpr = $cgstPer !== null ? ('IFNULL(i.' . $cgstPer . ',0)') : '0';
        $cgstExpr = $cgst !== null ? ('IFNULL(i.' . $cgst . ',0)') : '((' . $taxableExpr . ' * ' . $cgstPerExpr . ')/100)';
        $sgstExpr = $sgst !== null ? ('IFNULL(i.' . $sgst . ',0)') : '((' . $taxableExpr . ' * ' . $cgstPerExpr . ')/100)';
        $hsnExpr = $hsn !== null ? ('LEFT(TRIM(i.' . $hsn . '), 8)') : "'-'";

        $sql = 'SELECT '
            . $hsnExpr . ' AS hsn_code,'
            . 'SUM(' . $qtyExpr . ') AS no_of_qty,'
            . $cgstPerExpr . ' AS cgst_per,'
            . 'SUM(' . $lineAmountExpr . ') AS total_amount,'
            . 'SUM(' . $taxableExpr . ') AS total_taxable_amount,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=2.5 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_5_amount,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=2.5 THEN ' . $cgstExpr . ' ELSE 0 END) AS cgst_2_5,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=2.5 THEN ' . $sgstExpr . ' ELSE 0 END) AS sgst_2_5,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=6 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_12_amount,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=6 THEN ' . $cgstExpr . ' ELSE 0 END) AS cgst_6,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=6 THEN ' . $sgstExpr . ' ELSE 0 END) AS sgst_6,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=9 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_18_amount,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=9 THEN ' . $cgstExpr . ' ELSE 0 END) AS cgst_9,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=9 THEN ' . $sgstExpr . ' ELSE 0 END) AS sgst_9,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=14 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_28_amount,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=14 THEN ' . $cgstExpr . ' ELSE 0 END) AS cgst_14,'
            . 'SUM(CASE WHEN ' . $cgstPerExpr . '=14 THEN ' . $sgstExpr . ' ELSE 0 END) AS sgst_14,'
            . 'ROUND(SUM(CASE WHEN ' . $cgstPerExpr . '=0 THEN ' . $taxableExpr . ' ELSE 0 END),0) AS sale_0_amount '
            . 'FROM invoice_med_master m '
            . 'JOIN inv_med_item i ON m.' . $mid . '=i.' . $invMedId . ' '
            . 'WHERE DATE(m.' . $invDate . ') BETWEEN ? AND ? '
            . 'GROUP BY ' . $hsnExpr . ', ' . $cgstPerExpr . ' '
            . 'ORDER BY ' . $hsnExpr . ' ASC, ' . $cgstPerExpr . ' ASC';

        return $this->db->query($sql, [$dateFrom, $dateTo])->getResultArray();
    }

    private function getShortMedicineRows(string $dateFrom, string $dateTo, string $formulation): array
    {
        if (! $this->db->tableExists('med_short_list') || ! $this->db->tableExists('med_product_master')) {
            return [];
        }

        $shortFields = $this->db->getFieldNames('med_short_list') ?? [];
        $productFields = $this->db->getFieldNames('med_product_master') ?? [];
        $formFields = $this->db->tableExists('med_formulation')
            ? ($this->db->getFieldNames('med_formulation') ?? [])
            : [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $shortIdField = $resolveField($shortFields, ['id']);
        $shortDateField = $resolveField($shortFields, ['short_date', 'date']);
        $shortItemNameField = $resolveField($shortFields, ['item_Name', 'item_name', 'Item_name']);
        $shortCurQtyField = $resolveField($shortFields, ['curQty', 'cur_qty']);
        $shortSupplierField = $resolveField($shortFields, ['supplier_name', 'supplier']);
        $shortItemCodeField = $resolveField($shortFields, ['item_code']);

        $productIdField = $resolveField($productFields, ['id']);
        $productFormField = $resolveField($productFields, ['formulation', 'formulation_name']);
        $productFormIdField = $resolveField($productFields, ['formulation_id', 'formulationid']);

        if ($shortIdField === null || $shortDateField === null || $shortItemCodeField === null || $productIdField === null) {
            return [];
        }

        $selects = [
            's.' . $shortIdField . ' AS id',
            'DATE_FORMAT(s.' . $shortDateField . ', "%d-%m-%Y") AS str_short_date',
            ($shortItemNameField !== null ? ('s.' . $shortItemNameField) : 'CONCAT("Item-",s.' . $shortItemCodeField . ')') . ' AS item_name',
            ($shortCurQtyField !== null ? ('s.' . $shortCurQtyField) : '0') . ' AS cur_qty',
            ($shortSupplierField !== null ? ('s.' . $shortSupplierField) : "'-'") . ' AS supplier_name',
        ];

        $joins = [
            'JOIN med_product_master m ON s.' . $shortItemCodeField . '=m.' . $productIdField,
        ];

        if (! empty($formFields) && $productFormIdField !== null) {
            $formIdField = $resolveField($formFields, ['id']);
            $formNameField = $resolveField($formFields, ['formulation_length', 'formulation']);
            if ($formIdField !== null && $formNameField !== null) {
                $joins[] = 'LEFT JOIN med_formulation f ON m.' . $productFormIdField . '=f.' . $formIdField;
                if ($productFormField !== null) {
                    $selects[] = 'COALESCE(NULLIF(TRIM(f.' . $formNameField . '),""), NULLIF(TRIM(m.' . $productFormField . '),""), "-") AS formulation';
                } else {
                    $selects[] = 'COALESCE(NULLIF(TRIM(f.' . $formNameField . '),""), "-") AS formulation';
                }
            } else {
                $selects[] = ($productFormField !== null ? ('m.' . $productFormField) : "'-'") . ' AS formulation';
            }
        } else {
            $selects[] = ($productFormField !== null ? ('m.' . $productFormField) : "'-'") . ' AS formulation';
        }

        $where = [
            'DATE(s.' . $shortDateField . ') BETWEEN ? AND ?',
        ];
        $params = [$dateFrom, $dateTo];

        $formulationIds = array_values(array_filter(array_map('trim', explode('S', $formulation)), static fn($v) => $v !== '' && $v !== '0'));
        if (! empty($formulationIds) && $productFormIdField !== null) {
            $placeholders = implode(',', array_fill(0, count($formulationIds), '?'));
            $where[] = 'm.' . $productFormIdField . ' IN (' . $placeholders . ')';
            foreach ($formulationIds as $fid) {
                $params[] = (int) $fid;
            }
        }

        $sql = 'SELECT ' . implode(', ', $selects)
            . ' FROM med_short_list s '
            . implode(' ', $joins)
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY s.' . $shortDateField . ' DESC, s.' . $shortIdField . ' DESC';

        return $this->db->query($sql, $params)->getResultArray();
    }

    private function getDailyMedicineSaleDocRows(string $dateFrom, string $dateTo, string $docId): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('inv_med_item')) {
            return [];
        }

        $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
        $stockFields = $this->db->tableExists('purchase_invoice_item')
            ? ($this->db->getFieldNames('purchase_invoice_item') ?? [])
            : [];
        $doctorFields = $this->db->tableExists('doctor_master')
            ? ($this->db->getFieldNames('doctor_master') ?? [])
            : [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $invDateField = $resolveField($masterFields, ['inv_date']);
        $docIdField = $resolveField($masterFields, ['doc_id']);
        if ($invDateField === null || $docIdField === null) {
            return [];
        }

        $saleReturnMasterField = $resolveField($masterFields, ['sale_return']);
        $itemCodeField = $resolveField($itemFields, ['item_code']);
        $itemNameField = $resolveField($itemFields, ['item_Name', 'item_name', 'Item_name']);
        $qtyField = $resolveField($itemFields, ['qty', 'unit']);
        $amountField = $resolveField($itemFields, ['twdisc_amount', 'tamount', 'amount']);
        $storeStockField = $resolveField($itemFields, ['store_stock_id']);
        $cgstField = $resolveField($itemFields, ['CGST', 'c_gst_amt', 'cgst', 'cgst_amt']);
        $sgstField = $resolveField($itemFields, ['SGST', 's_gst_amt', 'sgst', 'sgst_amt']);
        $cgstPerField = $resolveField($itemFields, ['CGST_per', 'cgst_per']);
        $sgstPerField = $resolveField($itemFields, ['SGST_per', 'sgst_per']);

        if ($itemCodeField === null || $qtyField === null) {
            return [];
        }

        $saleReturnExpr = $saleReturnMasterField !== null ? ('IFNULL(m.' . $saleReturnMasterField . ',0)') : '0';
        $itemNameExpr = $itemNameField !== null ? ('i.' . $itemNameField) : 'CONCAT("Item-", i.' . $itemCodeField . ')';
        $amountExpr = $amountField !== null ? ('IFNULL(i.' . $amountField . ',0)') : '0';
        $qtyExpr = 'IFNULL(i.' . $qtyField . ',0)';
        $cgstExpr = $cgstField !== null ? ('IFNULL(i.' . $cgstField . ',0)') : '0';
        $sgstExpr = $sgstField !== null ? ('IFNULL(i.' . $sgstField . ',0)') : '0';
        $cgstPerExpr = $cgstPerField !== null ? ('IFNULL(i.' . $cgstPerField . ',0)') : '0';
        $sgstPerExpr = $sgstPerField !== null ? ('IFNULL(i.' . $sgstPerField . ',0)') : '0';
        $gstAmountExpr = 'CASE
                WHEN ((' . $cgstExpr . ' + ' . $sgstExpr . ') > 0) THEN (' . $cgstExpr . ' + ' . $sgstExpr . ')
                WHEN ((' . $cgstPerExpr . ' + ' . $sgstPerExpr . ') > 0) THEN ((' . $amountExpr . ' * (' . $cgstPerExpr . ' + ' . $sgstPerExpr . ')) / 100)
                ELSE 0
            END';

        $purchaseJoin = '';
        $purchaseRateExpr = '0';
        if ($storeStockField !== null && ! empty($stockFields)) {
            $rateField = $resolveField($stockFields, ['purchase_unit_rate']);
            $stockIdField = $resolveField($stockFields, ['id']);
            if ($rateField !== null && $stockIdField !== null) {
                $purchaseJoin = ' LEFT JOIN purchase_invoice_item p ON i.' . $storeStockField . '=p.' . $stockIdField;
                $purchaseRateExpr = 'IFNULL(p.' . $rateField . ',0)';
            }
        }

        $curQtyJoin = '';
        $curQtyExpr = '0';
        if (! empty($stockFields)) {
            $stockItemCodeField = $resolveField($stockFields, ['item_code']);
            $totalUnitField = $resolveField($stockFields, ['total_unit']);
            $totalSaleUnitField = $resolveField($stockFields, ['total_sale_unit']);
            $totalReturnUnitField = $resolveField($stockFields, ['total_return_unit']);
            $totalLostUnitField = $resolveField($stockFields, ['total_lost_unit']);

            if ($stockItemCodeField !== null && $totalUnitField !== null && $totalSaleUnitField !== null && $totalReturnUnitField !== null && $totalLostUnitField !== null) {
                $curQtyJoin = ' LEFT JOIN (
                        SELECT t.' . $stockItemCodeField . ' AS item_code,
                               SUM(IFNULL(t.' . $totalUnitField . ',0)-IFNULL(t.' . $totalSaleUnitField . ',0)-IFNULL(t.' . $totalReturnUnitField . ',0)-IFNULL(t.' . $totalLostUnitField . ',0)) AS cur_qty
                        FROM purchase_invoice_item t
                        GROUP BY t.' . $stockItemCodeField . '
                    ) q ON q.item_code=i.' . $itemCodeField;
                $curQtyExpr = 'IFNULL(q.cur_qty,0)';
            }
        }

        $docJoin = '';
        $docNameExpr = 'CAST(m.' . $docIdField . ' AS CHAR)';
        if (! empty($doctorFields)) {
            $doctorIdField = $resolveField($doctorFields, ['id']);
            $doctorNameField = $resolveField($doctorFields, ['p_fname', 'name']);
            if ($doctorIdField !== null && $doctorNameField !== null) {
                $docJoin = ' LEFT JOIN doctor_master d ON m.' . $docIdField . '=d.' . $doctorIdField;
                $docNameExpr = 'IFNULL(d.' . $doctorNameField . ', "-")';
            }
        }

        $where = [
            'DATE(m.' . $invDateField . ') BETWEEN ? AND ?',
            'IFNULL(m.ipd_id,0)=0',
        ];
        $params = [$dateFrom, $dateTo];

        $docIds = array_values(array_filter(array_map('trim', explode('S', $docId)), static fn($v) => $v !== '' && $v !== '0'));
        if (! empty($docIds)) {
            $placeholders = implode(',', array_fill(0, count($docIds), '?'));
            $where[] = 'm.' . $docIdField . ' IN (' . $placeholders . ')';
            foreach ($docIds as $value) {
                $params[] = (int) $value;
            }
        }

        $sql = "SELECT
                {$docNameExpr} AS doc_name,
                i.{$itemCodeField} AS item_code,
                {$itemNameExpr} AS item_name,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN {$qtyExpr} ELSE {$qtyExpr}*-1 END) AS sale_qty,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN {$amountExpr} ELSE {$amountExpr}*-1 END) AS sale_amount,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN ({$qtyExpr}*{$purchaseRateExpr}) ELSE ({$qtyExpr}*{$purchaseRateExpr})*-1 END) AS purchase_amount,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN ({$gstAmountExpr}) ELSE ({$gstAmountExpr})*-1 END) AS sale_gst,
                {$curQtyExpr} AS cur_qty
            FROM invoice_med_master m
            JOIN inv_med_item i ON m.id=i.inv_med_id
            {$purchaseJoin}
            {$curQtyJoin}
            {$docJoin}
            WHERE " . implode(' AND ', $where) . "
            GROUP BY {$docNameExpr}, i.{$itemCodeField}, {$itemNameExpr}
            ORDER BY {$itemNameExpr}";

        return $this->db->query($sql, $params)->getResultArray();
    }

    private function getCompanyWiseMedicineSaleRows(string $dateFrom, string $dateTo, int $companyId): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('inv_med_item') || ! $this->db->tableExists('med_product_master')) {
            return [];
        }

        $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
        $productFields = $this->db->getFieldNames('med_product_master') ?? [];
        $stockFields = $this->db->tableExists('purchase_invoice_item')
            ? ($this->db->getFieldNames('purchase_invoice_item') ?? [])
            : [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            foreach ($candidates as $candidate) {
                foreach ($fields as $field) {
                    if (strcasecmp((string) $field, (string) $candidate) === 0) {
                        return (string) $field;
                    }
                }
            }
            return null;
        };

        $invDateField = $resolveField($masterFields, ['inv_date']);
        $saleReturnField = $resolveField($masterFields, ['sale_return']);
        $itemCodeField = $resolveField($itemFields, ['item_code']);
        $itemNameField = $resolveField($itemFields, ['item_Name', 'item_name', 'Item_name']);
        $qtyField = $resolveField($itemFields, ['qty', 'unit']);
        $amountField = $resolveField($itemFields, ['twdisc_amount', 'tamount', 'amount']);
        $storeStockField = $resolveField($itemFields, ['store_stock_id']);
        $cgstField = $resolveField($itemFields, ['CGST', 'c_gst_amt', 'cgst', 'cgst_amt']);
        $sgstField = $resolveField($itemFields, ['SGST', 's_gst_amt', 'sgst', 'sgst_amt']);
        $cgstPerField = $resolveField($itemFields, ['CGST_per', 'cgst_per']);
        $sgstPerField = $resolveField($itemFields, ['SGST_per', 'sgst_per']);
        $productIdField = $resolveField($productFields, ['id']);
        $productCompanyField = $resolveField($productFields, ['company_id']);

        if ($invDateField === null || $itemCodeField === null || $qtyField === null || $productIdField === null || $productCompanyField === null) {
            return [];
        }

        $itemNameExpr = $itemNameField !== null ? ('i.' . $itemNameField) : 'CONCAT("Item-", i.' . $itemCodeField . ')';
        $saleReturnExpr = $saleReturnField !== null ? ('IFNULL(m.' . $saleReturnField . ',0)') : '0';
        $qtyExpr = 'IFNULL(i.' . $qtyField . ',0)';
        $amountExpr = $amountField !== null ? ('IFNULL(i.' . $amountField . ',0)') : '0';
        $cgstExpr = $cgstField !== null ? ('IFNULL(i.' . $cgstField . ',0)') : '0';
        $sgstExpr = $sgstField !== null ? ('IFNULL(i.' . $sgstField . ',0)') : '0';
        $cgstPerExpr = $cgstPerField !== null ? ('IFNULL(i.' . $cgstPerField . ',0)') : '0';
        $sgstPerExpr = $sgstPerField !== null ? ('IFNULL(i.' . $sgstPerField . ',0)') : '0';
        $gstAmountExpr = 'CASE
                WHEN ((' . $cgstExpr . ' + ' . $sgstExpr . ') > 0) THEN (' . $cgstExpr . ' + ' . $sgstExpr . ')
                WHEN ((' . $cgstPerExpr . ' + ' . $sgstPerExpr . ') > 0) THEN ((' . $amountExpr . ' * (' . $cgstPerExpr . ' + ' . $sgstPerExpr . ')) / 100)
                ELSE 0
            END';

        $purchaseJoin = '';
        $purchaseRateExpr = '0';
        if ($storeStockField !== null && ! empty($stockFields)) {
            $rateField = $resolveField($stockFields, ['purchase_unit_rate']);
            $stockIdField = $resolveField($stockFields, ['id']);
            if ($rateField !== null && $stockIdField !== null) {
                $purchaseJoin = ' LEFT JOIN purchase_invoice_item p ON i.' . $storeStockField . '=p.' . $stockIdField;
                $purchaseRateExpr = 'IFNULL(p.' . $rateField . ',0)';
            }
        }

        $where = [
            'DATE(m.' . $invDateField . ') BETWEEN ? AND ?',
            'pr.' . $productCompanyField . ' = ?',
        ];
        $params = [$dateFrom, $dateTo, $companyId];

        $sql = "SELECT
                i.{$itemCodeField} AS item_code,
                {$itemNameExpr} AS item_name,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN {$qtyExpr} ELSE 0 END) AS sale_qty,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN {$amountExpr} ELSE 0 END) AS sale_amount,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN ({$qtyExpr}*{$purchaseRateExpr}) ELSE 0 END) AS purchase_amount,
                SUM(CASE WHEN {$saleReturnExpr}=0 THEN ({$gstAmountExpr}) ELSE 0 END) AS sale_gst,
                SUM(CASE WHEN {$saleReturnExpr}=1 THEN {$qtyExpr} ELSE 0 END) AS return_qty,
                SUM(CASE WHEN {$saleReturnExpr}=1 THEN {$amountExpr} ELSE 0 END) AS return_amount,
                SUM(CASE WHEN {$saleReturnExpr}=1 THEN ({$gstAmountExpr}) ELSE 0 END) AS return_gst,
                SUM(CASE WHEN {$saleReturnExpr}=1 THEN ({$qtyExpr}*{$purchaseRateExpr}) ELSE 0 END) AS return_purchase_amount
            FROM invoice_med_master m
            JOIN inv_med_item i ON m.id=i.inv_med_id
            {$purchaseJoin}
            JOIN med_product_master pr ON pr.{$productIdField}=i.{$itemCodeField}
            WHERE " . implode(' AND ', $where) . "
            GROUP BY i.{$itemCodeField}, {$itemNameExpr}
            ORDER BY {$itemNameExpr}";

        return $this->db->query($sql, $params)->getResultArray();
    }

    public function ipd_discharge()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/report_ipd_discharge', [
            'today' => date('Y-m-d'),
        ]);
    }

    public function report_6_data($dateRange, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $output = (int) $output;

        $rows = $this->getIpdDischargeReportRows($dateFrom, $dateTo);

        if ($output === 1) {
            $content = view('medical/report_ipd_discharge_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
            ExportExcel($content, 'Report_Medical_IPD_Discharge');
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_ipd_discharge_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'orientation' => 'L',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('IPD Discharge Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('IPD_Discharge_Report_' . date('Ymd_His') . '.pdf', 'S'));
        }

        return view('medical/report_ipd_discharge_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    private function getIpdDischargeReportRows(string $dateFrom, string $dateTo): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('ipd_master')) {
            return [];
        }
        $invFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $ipdFields = $this->db->getFieldNames('ipd_master') ?? [];

        $ipdIdCol = 'i.id';
        $ipdCodeCol = in_array('ipd_code', $ipdFields, true) ? 'i.ipd_code' : 'CONCAT("IPD-", i.id)';
        $ipdPatientRefCol = in_array('p_id', $ipdFields, true)
            ? 'i.p_id'
            : (in_array('patient_id', $ipdFields, true) ? 'i.patient_id' : 'null');

        $patientCodeExprParts = [];
        $patientNameExprParts = [];
        $patientJoinSql = '';

        if ($this->db->tableExists('patient_master_exten')) {
            $pExtFields = $this->db->getFieldNames('patient_master_exten') ?? [];
            $patientJoinSql .= " LEFT JOIN patient_master_exten pe ON pe.id={$ipdPatientRefCol} ";

            if (in_array('p_code', $pExtFields, true)) {
                $patientCodeExprParts[] = 'NULLIF(TRIM(pe.p_code), "")';
            }
            if (in_array('p_fname', $pExtFields, true)) {
                $patientNameExprParts[] = 'NULLIF(TRIM(pe.p_fname), "")';
            }
        }

        if ($this->db->tableExists('patient_master')) {
            $pFields = $this->db->getFieldNames('patient_master') ?? [];
            $patientJoinSql .= " LEFT JOIN patient_master pm ON pm.id={$ipdPatientRefCol} ";

            if (in_array('p_code', $pFields, true)) {
                $patientCodeExprParts[] = 'NULLIF(TRIM(pm.p_code), "")';
            }
            if (in_array('p_fname', $pFields, true)) {
                $patientNameExprParts[] = 'NULLIF(TRIM(pm.p_fname), "")';
            }
        }

        $patientCodeExpr = ! empty($patientCodeExprParts)
            ? ('COALESCE(' . implode(',', $patientCodeExprParts) . ', "-")')
            : '"-"';
        $patientNameExpr = ! empty($patientNameExprParts)
            ? ('COALESCE(' . implode(',', $patientNameExprParts) . ', "-")')
            : '"-"';

        $tpaExpr = '"Direct"';
        $insuranceJoinSql = '';
        if ($this->db->tableExists('hc_insurance') && in_array('insurance_id', $ipdFields, true)) {
            $insFields = $this->db->getFieldNames('hc_insurance') ?? [];
            $insuranceJoinSql = ' LEFT JOIN hc_insurance ins ON ins.id=i.insurance_id ';
            $tpaExprParts = [];

            if (in_array('short_name', $insFields, true)) {
                $tpaExprParts[] = 'NULLIF(TRIM(ins.short_name), "")';
            }
            if (in_array('ins_company_name', $insFields, true)) {
                $tpaExprParts[] = 'NULLIF(TRIM(ins.ins_company_name), "")';
            }

            if (! empty($tpaExprParts)) {
                $tpaExpr = 'COALESCE(' . implode(',', $tpaExprParts) . ', "Direct")';
            }
        }

        $amountExpr = in_array('net_amount', $invFields, true) ? 'IFNULL(m.net_amount,0)' : '0';
        if ($this->db->tableExists('inv_med_group') && in_array('med_group_id', $invFields, true)) {
            $groupFields = $this->db->getFieldNames('inv_med_group') ?? [];
            if (in_array('med_group_id', $groupFields, true) && in_array('net_amount', $groupFields, true)) {
                $insuranceJoinSql .= ' LEFT JOIN inv_med_group g ON g.med_group_id=m.med_group_id ';
                $amountExpr = 'IFNULL(g.net_amount, IFNULL(m.net_amount,0))';
            }
        }

        $payExists = $this->db->tableExists('payment_history_medical');
        $payFields = $payExists ? ($this->db->getFieldNames('payment_history_medical') ?? []) : [];

        $registerDateCol = in_array('register_date', $ipdFields, true) ? 'i.register_date' : 'null';
        $dischargeDateCol = in_array('discharge_date', $ipdFields, true) ? 'i.discharge_date' : (in_array('discharged_at', $ipdFields, true) ? 'i.discharged_at' : 'null');

        $ipdCreditCol = in_array('ipd_credit', $invFields, true) ? 'IFNULL(m.ipd_credit,0)' : '0';
        $ipdCreditTypeCol = in_array('ipd_credit_type', $invFields, true) ? 'IFNULL(m.ipd_credit_type,0)' : '0';

        $saleReturnFilter = in_array('sale_return', $invFields, true)
            ? 'AND IFNULL(m.sale_return,0)=0'
            : '';

        $dischargeStatusFilter = in_array('ipd_status', $ipdFields, true)
            ? 'AND IFNULL(i.ipd_status,0)<>0'
            : '';

        $paySelect = '0 AS paid_amount';
        $payJoin = '';
        if ($payExists && in_array('ipd_id', $payFields, true) && in_array('amount', $payFields, true)) {
            $creditDebitExpr = in_array('credit_debit', $payFields, true)
                ? 'SUM(CASE WHEN IFNULL(h.credit_debit,0)=0 THEN IFNULL(h.amount,0) ELSE IFNULL(h.amount,0)*-1 END)'
                : 'SUM(IFNULL(h.amount,0))';

            $paySelect = 'IFNULL(pay_his.paid_amount,0) AS paid_amount';
            $payJoin = "LEFT JOIN (
                    SELECT h.ipd_id, {$creditDebitExpr} AS paid_amount
                    FROM payment_history_medical h
                    GROUP BY h.ipd_id
                                ) pay_his ON pay_his.ipd_id = i.id";
        }

        $sql = "SELECT
                                {$ipdIdCol} AS ipd_id,
                {$ipdCodeCol} AS ipd_code,
                                {$patientCodeExpr} AS p_code,
                                {$patientNameExpr} AS p_name,
                DATE_FORMAT({$registerDateCol}, '%d-%m-%Y') AS admit_date,
                DATE_FORMAT({$dischargeDateCol}, '%d-%m-%Y') AS discharge_date,
                                {$tpaExpr} AS tpa_name,
                                SUM(CASE WHEN {$ipdCreditCol}=0 THEN {$amountExpr} ELSE 0 END) AS ipd_cash_amount,
                                SUM(CASE WHEN {$ipdCreditCol}=1 AND {$ipdCreditTypeCol}=1 THEN {$amountExpr} ELSE 0 END) AS ipd_credit_amount,
                                SUM(CASE WHEN {$ipdCreditCol}=1 AND {$ipdCreditTypeCol}=0 THEN {$amountExpr} ELSE 0 END) AS ipd_package_amount,
                {$paySelect}
            FROM invoice_med_master m
                        JOIN ipd_master i ON m.ipd_id=i.id
                        {$patientJoinSql}
                        {$insuranceJoinSql}
            {$payJoin}
            WHERE IFNULL(m.ipd_id,0)>0
              {$saleReturnFilter}
              {$dischargeStatusFilter}
              AND DATE({$dischargeDateCol}) BETWEEN ? AND ?
                        GROUP BY {$ipdIdCol}, {$ipdCodeCol}, {$patientCodeExpr}, {$patientNameExpr}, {$registerDateCol}, {$dischargeDateCol}, {$tpaExpr}, paid_amount
                        ORDER BY DATE({$dischargeDateCol}) ASC, {$ipdIdCol} ASC";

        $rows = $this->db->query($sql, [$dateFrom, $dateTo])->getResultArray();

        foreach ($rows as &$row) {
            $cash = (float) ($row['ipd_cash_amount'] ?? 0);
            $paid = (float) ($row['paid_amount'] ?? 0);
            $balance = (int) round($cash - $paid, 0);
            if ($balance === 0) {
                $balance = 0;
            }
            $row['cash_balance'] = $balance;
        }
        unset($row);

        return $rows;
    }

    public function drug_patient_distribute($dateRange, $itemName = '-', $scheduleId = '0', $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $dateRange);
        $itemName = trim(urldecode((string) $itemName));
        $scheduleId = trim((string) $scheduleId);
        $output = (int) $output;

        $rows = $this->getDrugPatientDistributeRows($dateFrom, $dateTo, $itemName, $scheduleId);

        if ($output === 1) {
            $content = view('medical/report_med_patient_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
            ExportExcel($content, 'Report_Medical_Drug_Sale_Customer_Wise');
            return;
        }

        if ($output === 2) {
            $html = view('medical/report_med_patient_pdf', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'searchText' => ($itemName === '-' ? '' : $itemName),
                'scheduleId' => $scheduleId,
            ]);

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => WRITEPATH . 'cache',
            ]);

            $mpdf->SetTitle('Drug Sale Customer Wise Report');
            $mpdf->WriteHTML($html);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setBody($mpdf->Output('Drug_Sale_Customer_Wise_' . date('Ymd_His') . '.pdf', 'S'));
        }

        return view('medical/report_med_patient_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    private function getDrugPatientDistributeRows(string $dateFrom, string $dateTo, string $itemName, string $scheduleId): array
    {
        if (! $this->db->tableExists('invoice_med_master') || ! $this->db->tableExists('inv_med_item')) {
            return [];
        }

        $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];
        $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
        $hasProduct = $this->db->tableExists('med_product_master');
        $productFields = $hasProduct ? ($this->db->getFieldNames('med_product_master') ?? []) : [];

        $resolveField = static function (array $fields, array $candidates): ?string {
            $fieldMap = [];
            foreach ($fields as $field) {
                $fieldMap[strtolower((string) $field)] = (string) $field;
            }

            foreach ($candidates as $candidate) {
                $key = strtolower((string) $candidate);
                if (isset($fieldMap[$key])) {
                    return $fieldMap[$key];
                }
            }

            return null;
        };

        $invDateField = $resolveField($masterFields, ['inv_date']);
        if ($invDateField === null) {
            return [];
        }

        $itemNameField = $resolveField($itemFields, ['item_Name', 'item_name', 'Item_name']);
        $batchField = $resolveField($itemFields, ['batch_no', 'Batch_no']);
        $expiryField = $resolveField($itemFields, ['expiry', 'expiry_date']);
        $invCodeField = $resolveField($masterFields, ['inv_med_code']);
        $patientCodeField = $resolveField($masterFields, ['patient_code']);
        $invNameField = $resolveField($masterFields, ['inv_name', 'patient_name']);
        $ipdCodeField = $resolveField($masterFields, ['ipd_code']);

        $itemNameCol = $itemNameField !== null ? ('t.' . $itemNameField) : "'-'";
        $batchCol = $batchField !== null ? ('t.' . $batchField) : "'-'";
        $expiryCol = $expiryField !== null ? ('t.' . $expiryField) : 'null';
        $invDateCol = 'm.' . $invDateField;
        $invCodeCol = $invCodeField !== null ? ('m.' . $invCodeField) : 'concat(\'M\', m.id)';
        $patientCodeCol = $patientCodeField !== null ? ('m.' . $patientCodeField) : "'-'";
        $invNameCol = $invNameField !== null ? ('m.' . $invNameField) : "'-'";
        $ipdCodeCol = $ipdCodeField !== null ? ('m.' . $ipdCodeField) : "'-'";

        $itemNameFilterCol = $itemNameField !== null ? ('t.' . $itemNameField) : '';
        $batchFilterCol = $batchField !== null ? ('t.' . $batchField) : '';

        $qtyExpr = in_array('sale_return', $itemFields, true)
            ? 'SUM(CASE WHEN ifnull(t.sale_return,0)=0 THEN ifnull(t.qty,0) ELSE ifnull(t.qty,0)*-1 END)'
            : 'SUM(ifnull(t.qty,0))';

        $select = [
            'ifnull(' . $itemNameCol . ', \'-\') as item_name',
            'ifnull(' . $invCodeCol . ', concat(\'M\', m.id)) as inv_med_code',
            'm.id as m_id',
            "DATE_FORMAT(" . $invDateCol . ",'%d-%m-%Y') as str_inv_date",
            "DATE_FORMAT(" . $expiryCol . ",'%m-%Y') as exp_date",
            'ifnull(' . $patientCodeCol . ', \'-\') as patient_code',
            'ifnull(' . $invNameCol . ', \'-\') as inv_name',
            'ifnull(' . $ipdCodeCol . ', \'-\') as ipd_code',
            'ifnull(' . $batchCol . ', \'-\') as batch_no',
            $qtyExpr . ' as t_qty',
        ];

        if ($hasProduct && in_array('schedule_h', $productFields, true)) {
            $select[] = 'ifnull(p.schedule_h,0) as schedule_h';
        } else {
            $select[] = '0 as schedule_h';
        }
        if ($hasProduct && in_array('schedule_h1', $productFields, true)) {
            $select[] = 'ifnull(p.schedule_h1,0) as schedule_h1';
        } else {
            $select[] = '0 as schedule_h1';
        }
        if ($hasProduct && in_array('narcotic', $productFields, true)) {
            $select[] = 'ifnull(p.narcotic,0) as narcotic';
        } else {
            $select[] = '0 as narcotic';
        }
        if ($hasProduct && in_array('schedule_x', $productFields, true)) {
            $select[] = 'ifnull(p.schedule_x,0) as schedule_x';
        } else {
            $select[] = '0 as schedule_x';
        }
        if ($hasProduct && in_array('schedule_g', $productFields, true)) {
            $select[] = 'ifnull(p.schedule_g,0) as schedule_g';
        } else {
            $select[] = '0 as schedule_g';
        }
        if ($hasProduct && in_array('high_risk', $productFields, true)) {
            $select[] = 'ifnull(p.high_risk,0) as high_risk';
        } else {
            $select[] = '0 as high_risk';
        }

        $builder = $this->db->table('invoice_med_master m')
            ->select(implode(',', $select), false)
            ->join('inv_med_item t', 'm.id=t.inv_med_id', 'inner');

        if ($hasProduct) {
            $builder->join('med_product_master p', 'p.id=t.item_code', 'left');
        }

        $builder->where("DATE({$invDateCol}) >= '{$dateFrom}'", null, false)
            ->where("DATE({$invDateCol}) <= '{$dateTo}'", null, false);

        if (in_array('sale_return', $masterFields, true)) {
            $builder->where('IFNULL(m.sale_return,0)=0', null, false);
        }

        $itemName = trim($itemName);
        if ($itemName !== '' && $itemName !== '-') {
            if ($itemNameFilterCol === '' && $batchFilterCol === '') {
                return [];
            }
            $itemNameEsc = strtolower(trim($itemName));
            $itemNameLike = $this->db->escapeLikeString($itemNameEsc);
            $itemNameExact = $this->db->escape($itemName);

            if ($itemNameFilterCol !== '' && $batchFilterCol !== '') {
                $builder->where("(LOWER({$itemNameFilterCol}) LIKE '%{$itemNameLike}%' ESCAPE '!' OR {$batchFilterCol} = {$itemNameExact})", null, false);
            } elseif ($itemNameFilterCol !== '') {
                $builder->where("LOWER({$itemNameFilterCol}) LIKE '%{$itemNameLike}%' ESCAPE '!'", null, false);
            } else {
                $builder->where("{$batchFilterCol} = {$itemNameExact}", null, false);
            }
        } else {
            $scheduleTokens = array_values(array_filter(explode('S', $scheduleId), static fn($value) => trim((string) $value) !== '' && trim((string) $value) !== '0'));

            $scheduleWhereMap = [
                '1' => 'ifnull(p.schedule_h,0)=1',
                '2' => 'ifnull(p.schedule_h1,0)=1',
                '3' => 'ifnull(p.schedule_x,0)=1',
                '4' => 'ifnull(p.schedule_g,0)=1',
                '5' => 'ifnull(p.narcotic,0)=1',
                '6' => 'ifnull(p.high_risk,0)=1',
            ];

            $scheduleWhere = [];
            if ($hasProduct) {
                foreach ($scheduleTokens as $token) {
                    if (isset($scheduleWhereMap[$token])) {
                        $scheduleWhere[] = $scheduleWhereMap[$token];
                    }
                }
            }

            if ($scheduleWhere !== []) {
                $builder->where('(' . implode(' OR ', $scheduleWhere) . ')', null, false);
            } elseif ($scheduleTokens !== [] && ! $hasProduct) {
                return [];
            }
        }

        $builder->groupBy('item_name')
            ->groupBy('m.id')
            ->groupBy('batch_no')
            ->groupBy('exp_date')
            ->orderBy('item_name', 'ASC')
            ->orderBy($invDateCol, 'ASC')
            ->orderBy('m.id', 'ASC');

        $result = $builder->get()->getResultArray();
        if ($result === []) {
            return [];
        }

        $rows = [];
        foreach ($result as $row) {
            $flags = [];
            if ((int) ($row['schedule_h'] ?? 0) === 1) {
                $flags[] = 'schedule_h';
            }
            if ((int) ($row['schedule_h1'] ?? 0) === 1) {
                $flags[] = 'schedule_h1';
            }
            if ((int) ($row['narcotic'] ?? 0) === 1) {
                $flags[] = 'narcotic';
            }
            if ((int) ($row['schedule_x'] ?? 0) === 1) {
                $flags[] = 'schedule_x';
            }
            if ((int) ($row['schedule_g'] ?? 0) === 1) {
                $flags[] = 'schedule_g';
            }
            if ((int) ($row['high_risk'] ?? 0) === 1) {
                $flags[] = 'high_risk';
            }

            $row['shed_x_h'] = implode(',', $flags);
            $row['t_qty'] = (float) ($row['t_qty'] ?? 0);
            $rows[] = $row;
        }

        return $rows;
    }

    public function store_stock()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierData = $this->db->tableExists('med_supplier')
            ? $this->db->query('SELECT sid, name_supplier FROM med_supplier ORDER BY name_supplier')->getResult()
            : [];

        return view('medical/store_stock_search', [
            'supplier_data' => $supplierData,
        ]);
    }

    public function store_stock_result()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierId = (int) ($this->request->getPost('input_supplier') ?? 0);
        $itemName = trim((string) ($this->request->getPost('txtsearch') ?? ''));
        $chkReorder = (string) ($this->request->getPost('chk_reorder') ?? '');
        $isReorder = in_array(strtolower($chkReorder), ['on', '1', 'true'], true);

        $scheduleIds = $this->request->getPost('schedule_id');
        if (! is_array($scheduleIds)) {
            $scheduleIds = [];
        }

        $scheduleMap = [
            '1' => 'p.schedule_h=1',
            '2' => 'p.schedule_h1=1',
            '3' => 'p.schedule_x=1',
            '4' => 'p.schedule_g=1',
            '5' => 'p.narcotic=1',
            '6' => 'p.high_risk=1',
        ];

        $conditions = [
            '(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)>=0',
            's.remove_item=0',
            's.item_return=0',
            'p.is_continue=1',
            'm.date_of_invoice>DATE_ADD(CURDATE(), INTERVAL -24 MONTH)',
        ];
        $params = [];

        if ($supplierId > 0) {
            $conditions[] = 'm.sid = ?';
            $params[] = $supplierId;
        }

        if ($itemName !== '') {
            $conditions[] = '(p.item_name LIKE ? OR p.genericname LIKE ? OR ms.name_supplier LIKE ?)';
            $params[] = '%' . $itemName . '%';
            $params[] = '%' . $itemName . '%';
            $params[] = '%' . $itemName . '%';
        }

        $scheduleParts = [];
        foreach ($scheduleIds as $scheduleId) {
            $scheduleKey = (string) $scheduleId;
            if (isset($scheduleMap[$scheduleKey])) {
                $scheduleParts[] = $scheduleMap[$scheduleKey];
            }
        }

        if ($scheduleParts !== []) {
            $conditions[] = '(' . implode(' OR ', $scheduleParts) . ')';
        }

        $whereSql = implode(' AND ', $conditions);
        $havingSql = $isReorder
            ? ' HAVING SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) <= (MAX(p.re_order_qty) * MAX(IFNULL(NULLIF(s.packing,0),1))) '
            : '';

        $sql = "SELECT p.id, p.item_name, p.genericname,
                    IFNULL(s.item_code,0) AS item_found,
                    IFNULL(s.packing,p.packing) AS packing,
                    SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) AS C_Unit_Stock_Qty,
                    CONCAT(
                        TRUNCATE(
                            SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit)/NULLIF(IFNULL(s.packing,p.packing),0),
                            0
                        ),
                        ':',
                        MOD(
                            SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit),
                            NULLIF(IFNULL(s.packing,p.packing),0)
                        )
                    ) AS C_Pak_Qty,
                    SUM(s.total_unit) AS P_Unit_Qty,
                    SUM(s.total_sale_unit) AS sale_unit,
                    SUM(s.total_sale_unit)/NULLIF(IFNULL(s.packing,p.packing),0) AS C_Pak_Sale_Qty,
                    MAX(p.re_order_qty) AS re_order_qty,
                    SUM(s.total_lost_unit) AS total_lost_unit
                FROM med_product_master p
                JOIN purchase_invoice_item s ON p.id=s.item_code
                JOIN purchase_invoice m ON s.purchase_id=m.id
                LEFT JOIN med_supplier ms ON ms.sid=m.sid
                WHERE {$whereSql}
                GROUP BY p.id, p.item_name, p.genericname, IFNULL(s.packing,p.packing)
                {$havingSql}
                ORDER BY p.item_name";

        $stockList = $this->db->query($sql, $params)->getResult();

        $graphRows = $stockList;
        usort($graphRows, static function ($a, $b) {
            return (float) ($b->C_Unit_Stock_Qty ?? 0) <=> (float) ($a->C_Unit_Stock_Qty ?? 0);
        });
        $graphRows = array_slice($graphRows, 0, 10);

        $graphLabels = [];
        $graphValues = [];
        $lowStockCount = 0;

        foreach ($stockList as $row) {
            $packing = (float) ($row->packing ?? 0);
            $reOrder = (float) ($row->re_order_qty ?? 0);
            $currentUnit = (float) ($row->C_Unit_Stock_Qty ?? 0);
            $reOrderUnit = $reOrder * ($packing > 0 ? $packing : 1);
            if ($reOrderUnit > 0 && $currentUnit <= $reOrderUnit) {
                $lowStockCount++;
            }
        }

        foreach ($graphRows as $row) {
            $name = (string) ($row->item_name ?? '');
            if (mb_strlen($name) > 24) {
                $name = mb_substr($name, 0, 24) . '…';
            }
            $graphLabels[] = $name;
            $graphValues[] = round((float) ($row->C_Unit_Stock_Qty ?? 0), 2);
        }

        $expiryConditions = [
            's.remove_item=0',
            's.item_return=0',
            '(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) > 0',
            's.expiry_date IS NOT NULL',
            "s.expiry_date <> '0000-00-00'",
            's.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)',
            'm.date_of_invoice>DATE_ADD(CURDATE(), INTERVAL -24 MONTH)',
        ];
        $expiryParams = [];

        if ($itemName !== '') {
            $expiryConditions[] = '(p.item_name LIKE ? OR p.genericname LIKE ? OR ms.name_supplier LIKE ?)';
            $expiryParams[] = '%' . $itemName . '%';
            $expiryParams[] = '%' . $itemName . '%';
            $expiryParams[] = '%' . $itemName . '%';
        }
        if ($scheduleParts !== []) {
            $expiryConditions[] = '(' . implode(' OR ', $scheduleParts) . ')';
        }

        $expiryWhereSql = implode(' AND ', $expiryConditions);
        $expirySql = "SELECT
                SUM(CASE WHEN s.expiry_date < CURDATE() THEN (s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) ELSE 0 END) AS expired_units,
                SUM(CASE WHEN s.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN (s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) ELSE 0 END) AS due_0_30,
                SUM(CASE WHEN s.expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN (s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) ELSE 0 END) AS due_31_60,
                SUM(CASE WHEN s.expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 61 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN (s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) ELSE 0 END) AS due_61_90
            FROM med_product_master p
            JOIN purchase_invoice_item s ON p.id=s.item_code
            JOIN purchase_invoice m ON s.purchase_id=m.id
            LEFT JOIN med_supplier ms ON ms.sid=m.sid
            WHERE {$expiryWhereSql}";

        $expiryRow = $this->db->query($expirySql, $expiryParams)->getRowArray() ?? [];
        $expirySummary = [
            'expired' => round((float) ($expiryRow['expired_units'] ?? 0), 2),
            'd0_30' => round((float) ($expiryRow['due_0_30'] ?? 0), 2),
            'd31_60' => round((float) ($expiryRow['due_31_60'] ?? 0), 2),
            'd61_90' => round((float) ($expiryRow['due_61_90'] ?? 0), 2),
        ];

        $topSaleRows = [];
        if ($this->db->tableExists('inv_med_item') && $this->db->tableExists('invoice_med_master')) {
            $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
            $masterFields = $this->db->getFieldNames('invoice_med_master') ?? [];

            $qtyExpr = in_array('qty', $itemFields, true)
                ? 'SUM(IFNULL(i.qty,0))'
                : (in_array('unit', $itemFields, true) ? 'SUM(IFNULL(i.unit,0))' : 'COUNT(*)');

            $itemNameExpr = in_array('item_name', $itemFields, true)
                ? 'MAX(i.item_name)'
                : (in_array('Item_name', $itemFields, true)
                    ? 'MAX(i.Item_name)'
                    : (in_array('item_Name', $itemFields, true) ? 'MAX(i.item_Name)' : "CONCAT('Item-', i.item_code)"));

            $saleConds = [
                'm.inv_date >= DATE_ADD(CURDATE(), INTERVAL -30 DAY)',
            ];
            $saleParams = [];

            if (in_array('sale_return', $masterFields, true)) {
                $saleConds[] = 'IFNULL(m.sale_return,0)=0';
            }
            if (in_array('sale_return', $itemFields, true)) {
                $saleConds[] = 'IFNULL(i.sale_return,0)=0';
            }
            if (in_array('item_return', $itemFields, true)) {
                $saleConds[] = 'IFNULL(i.item_return,0)=0';
            }
            if ($itemName !== '') {
                $nameSearchParts = [];
                if (in_array('item_name', $itemFields, true)) {
                    $nameSearchParts[] = 'i.item_name LIKE ?';
                    $saleParams[] = '%' . $itemName . '%';
                }
                if (in_array('Item_name', $itemFields, true)) {
                    $nameSearchParts[] = 'i.Item_name LIKE ?';
                    $saleParams[] = '%' . $itemName . '%';
                }
                if ($nameSearchParts !== []) {
                    $saleConds[] = '(' . implode(' OR ', $nameSearchParts) . ')';
                }
            }

            $invJoinCol = null;
            if (in_array('inv_med_id', $itemFields, true)) {
                $invJoinCol = 'i.inv_med_id';
            } elseif (in_array('invoice_id', $itemFields, true)) {
                $invJoinCol = 'i.invoice_id';
            }

            if ($invJoinCol !== null) {
                $saleSql = "SELECT i.item_code, {$itemNameExpr} AS item_name, {$qtyExpr} AS sale_qty_30
                            FROM inv_med_item i
                            JOIN invoice_med_master m ON m.id={$invJoinCol}
                            WHERE " . implode(' AND ', $saleConds) . "
                            GROUP BY i.item_code
                            ORDER BY sale_qty_30 DESC
                            LIMIT 10";

                $topSaleRows = $this->db->query($saleSql, $saleParams)->getResultArray();
            }
        }

        $ratioRows = [];
        if ($this->db->tableExists('purchase_invoice_item') && $this->db->tableExists('med_product_master')) {
            $ratioConds = [
                's.remove_item=0',
                's.item_return=0',
                '(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) > 0',
            ];
            $ratioParams = [];

            if ($itemName !== '') {
                $ratioConds[] = '(p.item_name LIKE ? OR p.genericname LIKE ?)';
                $ratioParams[] = '%' . $itemName . '%';
                $ratioParams[] = '%' . $itemName . '%';
            }
            if ($scheduleParts !== []) {
                $ratioConds[] = '(' . implode(' OR ', $scheduleParts) . ')';
            }

            $ratioSql = "SELECT p.id AS item_code,
                    p.item_name,
                    SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) AS current_unit_qty,
                    SUM(CASE WHEN s.stock_date >= DATE_ADD(CURDATE(), INTERVAL -30 DAY) THEN IFNULL(s.total_sale_unit,0) ELSE 0 END) AS sale_qty_30
                FROM med_product_master p
                JOIN purchase_invoice_item s ON p.id=s.item_code
                WHERE " . implode(' AND ', $ratioConds) . "
                GROUP BY p.id, p.item_name
                HAVING SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) > 0
                ORDER BY (
                    SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit)
                    / GREATEST(SUM(CASE WHEN s.stock_date >= DATE_ADD(CURDATE(), INTERVAL -30 DAY) THEN IFNULL(s.total_sale_unit,0) ELSE 0 END),1)
                ) DESC,
                SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) DESC
                LIMIT 10";

            $ratioRows = $this->db->query($ratioSql, $ratioParams)->getResultArray();
        }

        $topSaleLabels = [];
        $topSaleValues = [];
        foreach ($topSaleRows as $row) {
            $name = (string) ($row['item_name'] ?? '');
            if (mb_strlen($name) > 24) {
                $name = mb_substr($name, 0, 24) . '…';
            }
            $topSaleLabels[] = $name;
            $topSaleValues[] = round((float) ($row['sale_qty_30'] ?? 0), 2);
        }

        $ratioLabels = [];
        $ratioValues = [];
        foreach ($ratioRows as &$row) {
            $sale = (float) ($row['sale_qty_30'] ?? 0);
            $stock = (float) ($row['current_unit_qty'] ?? 0);
            $row['stock_sale_ratio'] = round($stock / max($sale, 1.0), 2);

            $name = (string) ($row['item_name'] ?? '');
            if (mb_strlen($name) > 24) {
                $name = mb_substr($name, 0, 24) . '…';
            }
            $ratioLabels[] = $name;
            $ratioValues[] = $row['stock_sale_ratio'];
        }
        unset($row);

        return view('medical/store_stock_result', [
            'stock_list' => $stockList,
            'graph_labels' => $graphLabels,
            'graph_values' => $graphValues,
            'total_items' => count($stockList),
            'low_stock_items' => $lowStockCount,
            'expiry_summary' => $expirySummary,
            'top_sale_rows' => $topSaleRows,
            'top_sale_labels' => $topSaleLabels,
            'top_sale_values' => $topSaleValues,
            'ratio_rows' => $ratioRows,
            'ratio_labels' => $ratioLabels,
            'ratio_values' => $ratioValues,
        ]);
    }

    public function stock_result_excel($reOrder = '0', $itemName = '-', $scheduleId = '0')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $isReorder = in_array((string) $reOrder, ['1', 'on', 'true'], true);
        $itemName = urldecode((string) $itemName);
        if ($itemName === '-') {
            $itemName = '';
        }

        $scheduleIds = [];
        if ((string) $scheduleId !== '0' && (string) $scheduleId !== '') {
            $scheduleIds = explode('S', (string) $scheduleId);
        }

        $scheduleMap = [
            '1' => 'p.schedule_h=1',
            '2' => 'p.schedule_h1=1',
            '3' => 'p.schedule_x=1',
            '4' => 'p.schedule_g=1',
            '5' => 'p.narcotic=1',
            '6' => 'p.high_risk=1',
        ];

        $conditions = [
            '(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)>=0',
            's.remove_item=0',
            's.item_return=0',
            'p.is_continue=1',
            'm.date_of_invoice>DATE_ADD(CURDATE(), INTERVAL -24 MONTH)',
        ];
        $params = [];

        if ($itemName !== '') {
            $conditions[] = '(p.item_name LIKE ? OR p.genericname LIKE ? OR ms.name_supplier LIKE ?)';
            $params[] = '%' . $itemName . '%';
            $params[] = '%' . $itemName . '%';
            $params[] = '%' . $itemName . '%';
        }

        $scheduleParts = [];
        foreach ($scheduleIds as $scheduleIdItem) {
            $scheduleKey = (string) $scheduleIdItem;
            if (isset($scheduleMap[$scheduleKey])) {
                $scheduleParts[] = $scheduleMap[$scheduleKey];
            }
        }
        if ($scheduleParts !== []) {
            $conditions[] = '(' . implode(' OR ', $scheduleParts) . ')';
        }

        $havingSql = $isReorder
            ? ' HAVING SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) <= (MAX(p.re_order_qty) * MAX(IFNULL(NULLIF(s.packing,0),1))) '
            : '';

        $whereSql = implode(' AND ', $conditions);

        $sql = "SELECT p.item_name, p.genericname,
                    IFNULL(s.packing,p.packing) AS packing,
                    SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) AS C_Unit_Stock_Qty,
                    CONCAT(
                        TRUNCATE(
                            SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit)/NULLIF(IFNULL(s.packing,p.packing),0),
                            0
                        ),
                        ':',
                        MOD(
                            SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit),
                            NULLIF(IFNULL(s.packing,p.packing),0)
                        )
                    ) AS C_Pak_Qty,
                    SUM(s.total_sale_unit)/NULLIF(IFNULL(s.packing,p.packing),0) AS C_Pak_Sale_Qty,
                    SUM(s.total_sale_unit) AS sale_unit,
                    SUM(s.total_lost_unit) AS total_lost_unit,
                    MAX(p.re_order_qty) AS re_order_qty
                FROM med_product_master p
                JOIN purchase_invoice_item s ON p.id=s.item_code
                JOIN purchase_invoice m ON s.purchase_id=m.id
                LEFT JOIN med_supplier ms ON ms.sid=m.sid
                WHERE {$whereSql}
                GROUP BY p.item_name, p.genericname, IFNULL(s.packing,p.packing)
                {$havingSql}
                ORDER BY p.item_name";

        $rows = $this->db->query($sql, $params)->getResult();

        $content = '<table border="1"><thead><tr>'
            . '<th>Item Name</th><th>Generic</th><th>Current Pak.</th><th>Current Unit Qty</th>'
            . '<th>Total Sale Pak.</th><th>Total Sale Unit Qty</th><th>Lost Unit</th><th>Package/Re-Order Qty</th>'
            . '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $content .= '<tr>'
                . '<td>' . esc((string) ($row->item_name ?? '')) . '</td>'
                . '<td>' . esc((string) ($row->genericname ?? '')) . '</td>'
                . '<td>' . esc((string) ($row->C_Pak_Qty ?? '0')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->C_Unit_Stock_Qty ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->C_Pak_Sale_Qty ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->sale_unit ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->total_lost_unit ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc((string) ($row->packing ?? '')) . '/' . esc((string) ($row->re_order_qty ?? '')) . '</td>'
                . '</tr>';
        }

        $content .= '</tbody></table>';

        ExportExcel($content, 'Report_Medical_store_stock');
        return;
    }

    public function stock_result_excel_3($supplierId = '0', $opdDateRange = '0', $itemName = '-', $reOrder = '0')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierId = (int) $supplierId;
        $itemName = urldecode((string) $itemName);
        if ($itemName === '-') {
            $itemName = '';
        }

        $dateFrom = '';
        $dateTo = '';
        if ($opdDateRange !== '0' && strpos((string) $opdDateRange, 'S') !== false) {
            [$dateFrom, $dateTo] = explode('S', (string) $opdDateRange);
        }

        $conditions = [
            's.item_return=0',
            's.remove_item=0',
            'm.date_of_invoice>DATE_ADD(CURDATE(), INTERVAL -24 MONTH)',
        ];
        $params = [];

        if ($itemName !== '') {
            $conditions[] = 'p.item_name LIKE ?';
            $params[] = '%' . $itemName . '%';
        }

        if ($supplierId > 0) {
            $conditions[] = 'm.sid = ?';
            $params[] = $supplierId;
        }

        if ($dateFrom !== '' && $dateTo !== '') {
            $conditions[] = 'DATE(s.stock_date) BETWEEN ? AND ?';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }

        $havingSql = in_array((string) $reOrder, ['1', 'on', 'true'], true)
            ? ' HAVING SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) <= (MAX(p.re_order_qty) * MAX(IFNULL(NULLIF(s.packing,0),1))) '
            : '';

        $whereSql = implode(' AND ', $conditions);
        $sql = "SELECT p.item_name, p.genericname,
                    IFNULL(s.packing,p.packing) AS packing,
                    s.batch_no,
                    s.expiry_date,
                    s.mrp,
                    s.purchase_unit_rate,
                    (IFNULL(s.CGST_per,0) * 2) AS gst_per,
                    SUM(s.total_sale_unit)/NULLIF(IFNULL(s.packing,p.packing),0) AS C_Pak_Sale_Qty,
                    SUM(s.total_sale_unit) AS sale_unit,
                    SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) AS C_Unit_Stock_Qty,
                    SUM((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit))/NULLIF(IFNULL(s.packing,p.packing),0) AS C_Pak_Qty,
                    SUM(TRUNCATE((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit),0)*s.purchase_unit_rate) AS stock_cost
                FROM med_product_master p
                JOIN purchase_invoice_item s ON p.id=s.item_code
                JOIN purchase_invoice m ON s.purchase_id=m.id
                WHERE {$whereSql}
                GROUP BY p.item_name, p.genericname, IFNULL(s.packing,p.packing), s.batch_no, s.expiry_date, s.mrp, s.purchase_unit_rate, s.CGST_per
                {$havingSql}
                ORDER BY p.item_name, s.batch_no";

        $stockList = $this->db->query($sql, $params)->getResult();

        $totalStockValue = 0.0;
        $content = '<table border="1"><thead><tr>'
            . '<th>Item Name</th><th>Generic Name</th><th>MRP</th><th>Batch</th><th>Expiry</th>'
            . '<th>Current Pak.</th><th>Current Unit Qty</th><th>Total Sale Pak.</th><th>Total Sale Unit Qty</th>'
            . '<th>Package/Re-Order Qty</th><th>Purchase Unit Rate</th><th>GST</th><th>Stock Cost</th>'
            . '</tr></thead><tbody>';

        foreach ($stockList as $row) {
            $stockCost = (float) ($row->stock_cost ?? 0);
            $totalStockValue += $stockCost;

            $content .= '<tr>'
                . '<td>' . esc((string) ($row->item_name ?? '')) . '</td>'
                . '<td>' . esc((string) ($row->genericname ?? '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->mrp ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc((string) ($row->batch_no ?? '')) . '</td>'
                . '<td>' . esc((string) ($row->expiry_date ?? '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->C_Pak_Qty ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->C_Unit_Stock_Qty ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->C_Pak_Sale_Qty ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->sale_unit ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc((string) ($row->packing ?? '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->purchase_unit_rate ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->gst_per ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format($stockCost, 2, '.', '')) . '</td>'
                . '</tr>';
        }

        $content .= '<tr><th colspan="12">Total Stock Cost</th><th>' . esc(number_format($totalStockValue, 2, '.', '')) . '</th></tr>';
        $content .= '</tbody></table>';

        ExportExcel($content, 'Report_Medical_store_stock_batchwise');
        return;
    }

    public function stock_details()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $db = \Config\Database::connect();
        $supplierData = $db->query('SELECT sid, name_supplier FROM med_supplier ORDER BY name_supplier')->getResult();

        return view('medical/stock_statement', [
            'supplier_data' => $supplierData,
        ]);
    }

    public function store_stock_result_datewise()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $itemName = trim((string) ($this->request->getPost('txtsearch') ?? ''));
        $supplierId = (int) ($this->request->getPost('input_supplier') ?? 0);
        $dateFrom = (string) ($this->request->getPost('date_from') ?? '');
        $dateTo = (string) ($this->request->getPost('date_to') ?? '');

        $db = \Config\Database::connect();

        $conditions = [
            'm.date_of_invoice > DATE_ADD(CURDATE(), INTERVAL -24 MONTH)',
            's.item_return=0',
        ];
        $params = [];

        if ($itemName !== '') {
            $conditions[] = 'p.item_name LIKE ?';
            $params[] = '%' . $itemName . '%';
        }

        if ($dateFrom !== '' && $dateTo !== '') {
            $conditions[] = 'DATE(s.stock_date) BETWEEN ? AND ?';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }

        if ($supplierId > 0) {
            $conditions[] = 'm.sid=?';
            $params[] = $supplierId;
        }

        $whereSql = implode(' AND ', $conditions);

        $sql = "SELECT p.id, p.item_name, IFNULL(s.item_code,0) AS item_found,
                    IFNULL(s.packing,p.packing) AS packing,
                    SUM(s.tqty) AS total_pak_qty,
                    SUM(s.net_amount) AS purchase_cost,
                    SUM(s.total_unit) AS total_unit,
                    SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) AS C_Unit_Stock_Qty,
                    SUM((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit))/NULLIF(s.packing,0) AS C_Pak_Qty,
                    SUM(s.total_sale_unit) AS sale_unit,
                    SUM(s.total_sale_unit)/NULLIF(s.packing,0) AS C_Pak_Sale_Qty,
                    p.re_order_qty,
                    SUM(s.total_lost_unit) AS total_lost_unit
                FROM med_product_master p
                JOIN purchase_invoice_item s ON p.id=s.item_code
                JOIN purchase_invoice m ON s.purchase_id=m.id
                WHERE {$whereSql}
                GROUP BY p.id, s.packing
                ORDER BY p.item_name";

        $stockList = $db->query($sql, $params)->getResult();

        return view('medical/stock_statement_data', [
            'stock_list' => $stockList,
        ]);
    }

    public function store_stock_result_datewise_excel($opdDateRange, $itemName = '-', $supplierId = '0')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $itemName = urldecode((string) $itemName);
        if ($itemName === '-') {
            $itemName = '';
        }
        $supplierId = (int) $supplierId;

        $dateFrom = '';
        $dateTo = '';
        if (strpos((string) $opdDateRange, 'S') !== false) {
            [$dateFrom, $dateTo] = explode('S', (string) $opdDateRange);
        }

        $conditions = [
            'm.date_of_invoice > DATE_ADD(CURDATE(), INTERVAL -24 MONTH)',
            's.item_return=0',
            's.remove_item=0',
        ];
        $params = [];

        if ($itemName !== '') {
            $conditions[] = 'p.item_name LIKE ?';
            $params[] = '%' . $itemName . '%';
        }

        if ($dateFrom !== '' && $dateTo !== '') {
            $conditions[] = 'DATE(s.stock_date) BETWEEN ? AND ?';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }

        if ($supplierId > 0) {
            $conditions[] = 'm.sid=?';
            $params[] = $supplierId;
        }

        $whereSql = implode(' AND ', $conditions);

        $sql = "SELECT p.item_name, p.genericname,
                    IFNULL(s.packing,p.packing) AS packing,
                    SUM(s.tqty) AS total_pak_qty,
                    SUM(s.net_amount) AS purchase_cost,
                    SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) AS current_unit_qty,
                    SUM((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit))/NULLIF(IFNULL(s.packing,p.packing),0) AS current_pak_qty,
                    SUM(s.total_sale_unit)/NULLIF(IFNULL(s.packing,p.packing),0) AS total_sale_pak,
                    SUM(s.total_sale_unit) AS total_sale_unit,
                    SUM(s.total_lost_unit) AS total_lost_unit,
                    MAX(p.re_order_qty) AS re_order_qty
                FROM med_product_master p
                JOIN purchase_invoice_item s ON p.id=s.item_code
                JOIN purchase_invoice m ON s.purchase_id=m.id
                WHERE {$whereSql}
                GROUP BY p.item_name, p.genericname, IFNULL(s.packing,p.packing)
                ORDER BY p.item_name";

        $rows = $this->db->query($sql, $params)->getResult();

        $content = '<table border="1"><thead><tr>'
            . '<th>Item Name</th><th>Generic Name</th><th>Pur Pak.</th><th>Pur Cost</th>'
            . '<th>Current Pak.</th><th>Current Unit Qty</th><th>Total Sale Pak.</th><th>Total Sale Unit Qty</th>'
            . '<th>Lost Unit</th><th>Package/Re-Order Qty</th>'
            . '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $content .= '<tr>'
                . '<td>' . esc((string) ($row->item_name ?? '')) . '</td>'
                . '<td>' . esc((string) ($row->genericname ?? '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->total_pak_qty ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->purchase_cost ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->current_pak_qty ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->current_unit_qty ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->total_sale_pak ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->total_sale_unit ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc(number_format((float) ($row->total_lost_unit ?? 0), 2, '.', '')) . '</td>'
                . '<td>' . esc((string) ($row->packing ?? '')) . '/' . esc((string) ($row->re_order_qty ?? '')) . '</td>'
                . '</tr>';
        }

        $content .= '</tbody></table>';
        ExportExcel($content, 'Report_Medical_store_stock_datewise');
        return;
    }

    private function getExpiryStockRows(): array
    {
        $sql = "SELECT p.id AS pur_id, p.invoice_no, p.date_of_invoice,
                    DATE_FORMAT(p.date_of_invoice,'%d-%m-%Y') AS str_date_of_invoice,
                    DATE_FORMAT(i.expiry_date,'%m-%Y') AS exp_date,
                    p.sid, s.name_supplier, s.short_name,
                    i.*, (i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit) AS cur_qty
                FROM purchase_invoice p
                JOIN med_supplier s ON p.sid=s.sid
                JOIN purchase_invoice_item i ON p.id=i.purchase_id
                WHERE i.remove_item=0
                  AND i.item_return=0
                  AND (i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit)>0
                  AND p.date_of_invoice>=DATE_ADD(CURDATE(), INTERVAL -1 YEAR)
                  AND i.expiry_date<DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
                ORDER BY p.id DESC";

        return $this->db->query($sql)->getResult();
    }

    public function expire_stock()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/expiry_stock_list', [
            'purchase_list' => $this->getExpiryStockRows(),
        ]);
    }

    public function expire_stock_pdf()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $html = view('medical/expiry_stock_pdf', [
            'purchase_list' => $this->getExpiryStockRows(),
            'generated_at' => date('d-m-Y H:i'),
        ]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $mpdf->SetTitle('Expiry Medicine Report');
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('Expiry_Medicine_Report_' . date('Ymd_His') . '.pdf', 'S'));
    }

    public function stocktransfer()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/stock_transfer');
    }

    public function merge_product()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/merge_product');
    }

    public function product_info($productId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $productId = (int) $productId;
        if ($productId <= 0 || ! $this->db->tableExists('med_product_master')) {
            return $this->response->setJSON(['product_id' => 0]);
        }

        $row = $this->db->table('med_product_master')->where('id', $productId)->get()->getRow();
        if (! $row) {
            return $this->response->setJSON(['product_id' => 0]);
        }

        return $this->response->setJSON([
            'product_id' => (int) ($row->id ?? 0),
            'product_name' => (string) ($row->item_name ?? ''),
            'formulation' => (string) ($row->formulation ?? ''),
            'genericname' => (string) ($row->genericname ?? ''),
        ]);
    }

    public function product_merged()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $fromProdId = (int) ($this->request->getPost('from_product_id') ?? 0);
        $toProdId = (int) ($this->request->getPost('to_product_id') ?? 0);

        if ($fromProdId <= 0 || $toProdId <= 0 || $fromProdId === $toProdId) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'Invalid product ids']);
        }

        if (! $this->db->tableExists('med_product_master')) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'med_product_master missing']);
        }

        $fromRow = $this->db->table('med_product_master')->where('id', $fromProdId)->get()->getRow();
        $toRow = $this->db->table('med_product_master')->where('id', $toProdId)->get()->getRow();
        if (! $fromRow || ! $toRow) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'Product not found']);
        }

        $this->db->transStart();

        $tablesWithItemCode = ['purchase_invoice_item', 'inv_med_item', 'inv_med_item_delete'];
        foreach ($tablesWithItemCode as $table) {
            if (! $this->db->tableExists($table)) {
                continue;
            }
            $fields = $this->db->getFieldNames($table) ?? [];
            if (in_array('item_code', $fields, true)) {
                $this->db->table($table)->where('item_code', $fromProdId)->update(['item_code' => $toProdId]);
            }
        }

        $masterFields = $this->db->getFieldNames('med_product_master') ?? [];
        $update = [];
        if (in_array('is_continue', $masterFields, true)) {
            $update['is_continue'] = 0;
        }
        if (in_array('merge_to', $masterFields, true)) {
            $update['merge_to'] = $toProdId;
        }
        if (in_array('merge_with', $masterFields, true)) {
            $update['merge_with'] = $toProdId;
        }
        if (! empty($update)) {
            $this->db->table('med_product_master')->where('id', $fromProdId)->update($update);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'Merge failed']);
        }

        $this->writeMedicalAdminActionLog('product_merge', 'Merged product ' . $fromProdId . ' into ' . $toProdId, [
            'from_product_id' => $fromProdId,
            'to_product_id' => $toProdId,
        ]);

        return $this->response->setJSON(['update' => 1, 'msg' => 'Merge done']);
    }

    public function ssno_info($ssno = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $ssno = (int) $ssno;
        if ($ssno <= 0 || ! $this->db->tableExists('purchase_invoice_item')) {
            return $this->response->setJSON(['ssno' => 0]);
        }

        $row = $this->db->table('purchase_invoice_item')->where('id', $ssno)->get()->getRow();
        if (! $row) {
            return $this->response->setJSON(['ssno' => 0]);
        }

        $totalUnit = (float) ($row->total_unit ?? 0);
        $totalSale = (float) ($row->total_sale_unit ?? 0);
        $totalLost = (float) ($row->total_lost_unit ?? 0);
        $totalReturn = (float) ($row->total_return_unit ?? 0);
        $currentUnit = $totalUnit - $totalSale - $totalLost + $totalReturn;

        return $this->response->setJSON([
            'ssno' => $ssno,
            'item_code' => (int) ($row->item_code ?? 0),
            'Item_name' => (string) ($row->item_name ?? $row->Item_name ?? ''),
            'batch_no' => (string) ($row->batch_no ?? ''),
            'purchase_price' => (float) ($row->purchase_price ?? 0),
            'tqty' => (float) ($row->tqty ?? 0),
            'total_unit' => $totalUnit,
            'total_current_unit' => $currentUnit,
            'total_sale_unit' => $totalSale,
        ]);
    }

    public function ssno_transfer()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $fromSsno = (int) ($this->request->getPost('from_ssno') ?? 0);
        $toSsno = (int) ($this->request->getPost('to_ssno') ?? 0);
        $qty = (float) ($this->request->getPost('tqty') ?? 0);

        if ($fromSsno <= 0 || $toSsno <= 0 || $qty <= 0 || $fromSsno === $toSsno) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'Invalid transfer data']);
        }
        if (! $this->db->tableExists('purchase_invoice_item')) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'purchase_invoice_item missing']);
        }

        $fromRow = $this->db->table('purchase_invoice_item')->where('id', $fromSsno)->get()->getRow();
        $toRow = $this->db->table('purchase_invoice_item')->where('id', $toSsno)->get()->getRow();
        if (! $fromRow || ! $toRow) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'SSNO not found']);
        }

        if ((int) ($fromRow->item_code ?? 0) !== (int) ($toRow->item_code ?? 0)) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'Product should be same']);
        }

        $fromSale = (float) ($fromRow->total_sale_unit ?? 0);
        $toCurrent = (float) ($toRow->total_unit ?? 0)
            - (float) ($toRow->total_sale_unit ?? 0)
            - (float) ($toRow->total_lost_unit ?? 0)
            + (float) ($toRow->total_return_unit ?? 0);

        if ($qty > $fromSale) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'Transfer qty exceeds from SSNO sale qty']);
        }
        if ($qty > $toCurrent) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'Transfer qty exceeds to SSNO current qty']);
        }

        $medicalModel = new MedicalModel();
        if (! $medicalModel->transferSaleSsno($fromSsno, $toSsno, (int) round($qty))) {
            return $this->response->setJSON(['update' => 0, 'msg' => 'Transfer failed']);
        }

        $this->writeMedicalAdminActionLog('stock_transfer', 'Transferred sale qty between SSNO', [
            'from_ssno' => $fromSsno,
            'to_ssno' => $toSsno,
            'qty' => $qty,
            'item_code' => (int) ($fromRow->item_code ?? 0),
        ]);

        return $this->response->setJSON(['update' => 1, 'msg' => 'Transfer done']);
    }

    public function supplier_account()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $db = \Config\Database::connect();
        $sql = "SELECT m.sid,m.name_supplier,m.short_name,
                    COALESCE(SUM(IF(l.credit_debit=0,l.amount,l.amount*-1)),0) AS Tot_Balance,
                    DATE_FORMAT(MAX(IF(l.credit_debit=0 AND l.purchase_id>0,l.tran_date,NULL)),'%d-%m-%Y') AS Last_InvDate,
                    DATE_FORMAT(MAX(IF(l.credit_debit=1 AND l.purchase_id=0,l.tran_date,NULL)),'%d-%m-%Y') AS Last_Payment
                FROM med_supplier m
                LEFT JOIN med_supplier_ledger l ON m.sid=l.supplier_id
                GROUP BY m.sid,m.name_supplier,m.short_name
                ORDER BY m.name_supplier";

        $supplierData = $db->query($sql)->getResult();

        return view('medical/supplier_account', [
            'supplier_data' => $supplierData,
        ]);
    }

    public function supplier_account_led($sid = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $sid = (int) $sid;
        if ($sid <= 0) {
            return view('medical/placeholder', ['title' => 'Supplier Account']);
        }

        $db = \Config\Database::connect();
        $sql = "SELECT m.sid,m.name_supplier,m.short_name,
                    COALESCE(SUM(IF(l.credit_debit=0,l.amount,l.amount*-1)),0) AS Tot_Balance,
                    DATE_FORMAT(MAX(IF(l.credit_debit=0 AND l.purchase_id>0,l.tran_date,NULL)),'%d-%m-%Y') AS Last_InvDate,
                    DATE_FORMAT(MAX(IF(l.credit_debit=1 AND l.purchase_id=0,l.tran_date,NULL)),'%d-%m-%Y') AS Last_Payment
                FROM med_supplier m
                LEFT JOIN med_supplier_ledger l ON m.sid=l.supplier_id
                WHERE m.sid={$sid}
                GROUP BY m.sid,m.name_supplier,m.short_name";

        $supplierData = $db->query($sql)->getRow();

        if (! $supplierData) {
            return view('medical/placeholder', ['title' => 'Supplier Not Found']);
        }

        $today = date('Y-m-d');
        $defaultDateFrom = $today;
        $defaultDateTo = $today;

        $rangeSql = "SELECT MIN(DATE(t.tran_date)) AS min_date, MAX(DATE(t.tran_date)) AS max_date
                     FROM (
                         SELECT l.tran_date
                         FROM med_supplier_ledger l
                         WHERE l.supplier_id = ?
                         ORDER BY l.tran_date DESC, l.id DESC
                         LIMIT 10
                     ) t";
        $rangeRow = $db->query($rangeSql, [$sid])->getRow();

        if ($rangeRow) {
            if (! empty($rangeRow->min_date)) {
                $defaultDateFrom = (string) $rangeRow->min_date;
            }
            if (! empty($rangeRow->max_date)) {
                $defaultDateTo = (string) $rangeRow->max_date;
            }
        }

        return view('medical/supplier_account_detail', [
            'supplier' => $supplierData,
            'default_date_from' => $defaultDateFrom,
            'default_date_to' => $defaultDateTo,
        ]);
    }

    public function supplier_account_ledger_data($sid = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $sid = (int) $sid;
        $dateFrom = (string) ($this->request->getPost('date_from') ?? '');
        $dateTo = (string) ($this->request->getPost('date_to') ?? '');

        if ($sid <= 0 || $dateFrom === '' || $dateTo === '') {
            return view('medical/supplier_account_ledger_data', [
                'med_supplier_ledger' => [],
                'balance_till_date' => 0,
                'balance_till_date_close' => 0,
                'cr_total' => 0,
                'dr_total' => 0,
            ]);
        }

        $db = \Config\Database::connect();

        $ledgerSql = "SELECT l.*,
                            (
                                SELECT CONCAT_WS('/', b.bank_account_name, b.bank_name)
                                FROM bank_account_master b
                                WHERE b.bank_id = l.bank_id
                                ORDER BY b.bank_id ASC
                                LIMIT 1
                            ) AS mode_desc
                      FROM med_supplier_ledger l
                      WHERE l.supplier_id=? AND DATE(l.tran_date) BETWEEN ? AND ?
                      ORDER BY l.tran_date DESC, l.id DESC";
        $ledgerRows = $db->query($ledgerSql, [$sid, $dateFrom, $dateTo])->getResult();

        $openingSql = "SELECT SUM(IF(credit_debit=0,amount,amount*-1)) AS Balance
                       FROM med_supplier_ledger
                       WHERE supplier_id=? AND DATE(tran_date) < ?";
        $openingBalance = (float) ($db->query($openingSql, [$sid, $dateFrom])->getRow('Balance') ?? 0);

        $closingSql = "SELECT SUM(IF(credit_debit=0,amount,amount*-1)) AS Balance
                       FROM med_supplier_ledger
                       WHERE supplier_id=? AND DATE(tran_date) <= ?";
        $closingBalance = (float) ($db->query($closingSql, [$sid, $dateTo])->getRow('Balance') ?? 0);

        $totalsSql = "SELECT SUM(IF(credit_debit=0,amount,0)) AS cr_total,
                             SUM(IF(credit_debit=1,amount,0)) AS dr_total
                      FROM med_supplier_ledger
                      WHERE supplier_id=? AND DATE(tran_date) BETWEEN ? AND ?";
        $totalsRow = $db->query($totalsSql, [$sid, $dateFrom, $dateTo])->getRow();

        return view('medical/supplier_account_ledger_data', [
            'med_supplier_ledger' => $ledgerRows,
            'balance_till_date' => $openingBalance,
            'balance_till_date_close' => $closingBalance,
            'cr_total' => (float) ($totalsRow->cr_total ?? 0),
            'dr_total' => (float) ($totalsRow->dr_total ?? 0),
        ]);
    }

    public function supplier_account_ledger_pdf($sid = 0, $dateFrom = '', $dateTo = '')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $sid = (int) $sid;
        $dateFrom = $this->normalizeDate((string) $dateFrom);
        $dateTo = $this->normalizeDate((string) $dateTo);

        if ($sid <= 0 || $dateFrom === '' || $dateTo === '') {
            return $this->response
                ->setStatusCode(400)
                ->setBody('Invalid supplier or date range.');
        }

        $supplierFields = $this->db->getFieldNames('med_supplier');
        $selectFields = ['sid', 'name_supplier'];
        foreach (['gst_no', 'contact_no', 'city', 'state', 'address', 'address1', 'add1', 'add2'] as $f) {
            if (in_array($f, $supplierFields, true)) {
                $selectFields[] = $f;
            }
        }

        $supplier = $this->db->table('med_supplier')
            ->select(implode(',', $selectFields))
            ->where('sid', $sid)
            ->get()
            ->getRow();

        if (! $supplier) {
            return $this->response
                ->setStatusCode(404)
                ->setBody('Supplier not found.');
        }

        $ledgerSql = "SELECT l.*,
                            (
                                SELECT CONCAT_WS('/', b.bank_account_name, b.bank_name)
                                FROM bank_account_master b
                                WHERE b.bank_id = l.bank_id
                                ORDER BY b.bank_id ASC
                                LIMIT 1
                            ) AS mode_desc
                      FROM med_supplier_ledger l
                      WHERE l.supplier_id=? AND DATE(l.tran_date) BETWEEN ? AND ?
                      ORDER BY l.tran_date DESC, l.id DESC";
        $ledgerRows = $this->db->query($ledgerSql, [$sid, $dateFrom, $dateTo])->getResult();

        $openingSql = "SELECT SUM(IF(credit_debit=0,amount,amount*-1)) AS Balance
                       FROM med_supplier_ledger
                       WHERE supplier_id=? AND DATE(tran_date) < ?";
        $openingBalance = (float) ($this->db->query($openingSql, [$sid, $dateFrom])->getRow('Balance') ?? 0);

        $closingSql = "SELECT SUM(IF(credit_debit=0,amount,amount*-1)) AS Balance
                       FROM med_supplier_ledger
                       WHERE supplier_id=? AND DATE(tran_date) <= ?";
        $closingBalance = (float) ($this->db->query($closingSql, [$sid, $dateTo])->getRow('Balance') ?? 0);

        $totalsSql = "SELECT SUM(IF(credit_debit=0,amount,0)) AS cr_total,
                             SUM(IF(credit_debit=1,amount,0)) AS dr_total
                      FROM med_supplier_ledger
                      WHERE supplier_id=? AND DATE(tran_date) BETWEEN ? AND ?";
        $totalsRow = $this->db->query($totalsSql, [$sid, $dateFrom, $dateTo])->getRow();

        $html = view('medical/supplier_account_ledger_pdf', [
            'supplier' => $supplier,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'med_supplier_ledger' => $ledgerRows,
            'balance_till_date' => $openingBalance,
            'balance_till_date_close' => $closingBalance,
            'cr_total' => (float) ($totalsRow->cr_total ?? 0),
            'dr_total' => (float) ($totalsRow->dr_total ?? 0),
        ]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $mpdf->SetTitle('Supplier Ledger');
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('Supplier_Ledger_' . $sid . '_' . date('Ymd_His') . '.pdf', 'S'));
    }

    public function supplier_account_add_entry($sid = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $sid = (int) $sid;
        if ($sid <= 0) {
            return view('medical/placeholder', ['title' => 'Supplier Not Found']);
        }

        $supplier = $this->db->table('med_supplier')
            ->select('sid, name_supplier')
            ->where('sid', $sid)
            ->get()
            ->getRow();

        if (! $supplier) {
            return view('medical/placeholder', ['title' => 'Supplier Not Found']);
        }

        $bankAccounts = $this->db->table('bank_account_master')
            ->orderBy('bank_account_name', 'ASC')
            ->get()
            ->getResult();

        $session = session();

        if (strtolower($this->request->getMethod()) === 'post') {
            $requestKey = trim((string) ($this->request->getPost('request_key') ?? ''));
            $processedKeys = (array) ($session->get('supplier_add_entry_keys') ?? []);

            if ($requestKey !== '' && in_array($requestKey, $processedKeys, true)) {
                return $this->response->setJSON([
                    'status' => 1,
                    'msg' => 'Entry already saved. Duplicate request ignored.',
                ]);
            }

            $creditDebit = (int) ($this->request->getPost('cr_dr_type') ?? 0);
            $bankId = (int) ($this->request->getPost('mode_type') ?? 0);
            $tranDate = $this->normalizeDate((string) ($this->request->getPost('tran_date') ?? ''));
            $amount = (float) ($this->request->getPost('amount') ?? 0);
            $tranDesc = trim((string) ($this->request->getPost('tran_desc') ?? ''));

            if ($tranDate === '' || $amount <= 0 || ! in_array($creditDebit, [0, 1], true)) {
                $html = view('medical/supplier_account_add_entry', [
                    's_id' => $sid,
                    'supplier' => $supplier,
                    'bank_account_master' => $bankAccounts,
                    'error' => 'Please enter valid date, amount and Cr/Dr type.',
                    'request_key' => bin2hex(random_bytes(16)),
                    'old' => [
                        'cr_dr_type' => $creditDebit,
                        'mode_type' => $bankId,
                        'tran_date' => $tranDate,
                        'amount' => (string) ($this->request->getPost('amount') ?? ''),
                        'tran_desc' => $tranDesc,
                    ],
                ]);

                return $this->response->setJSON([
                    'status' => 0,
                    'msg' => 'Validation failed',
                    'html' => $html,
                ]);
            }

            $user = service('auth')->user();
            $userId = null;
            $userName = null;

            if ($user) {
                $userId = isset($user->id) ? (int) $user->id : null;
                if (isset($user->username) && (string) $user->username !== '') {
                    $userName = (string) $user->username;
                } elseif (isset($user->email) && (string) $user->email !== '') {
                    $userName = (string) $user->email;
                } elseif (isset($user->name) && (string) $user->name !== '') {
                    $userName = (string) $user->name;
                }
            }

            $this->db->table('med_supplier_ledger')->insert([
                'supplier_id' => $sid,
                'credit_debit' => $creditDebit,
                'tran_date' => $tranDate,
                'amount' => $amount,
                'bank_id' => $bankId,
                'tran_desc' => $tranDesc,
                'insert_by_id' => $userId,
                'insert_by' => $userName,
            ]);

            if ($requestKey !== '') {
                $processedKeys[] = $requestKey;
                $processedKeys = array_values(array_slice(array_unique($processedKeys), -100));
                $session->set('supplier_add_entry_keys', $processedKeys);
            }

            $this->writeMedicalAdminActionLog('supplier_ledger_add', 'Added supplier ledger entry', [
                'supplier_id' => $sid,
                'credit_debit' => $creditDebit,
                'amount' => $amount,
                'tran_date' => $tranDate,
            ]);

            return $this->response->setJSON([
                'status' => 1,
                'msg' => 'Ledger entry added successfully.',
            ]);
        }

        return view('medical/supplier_account_add_entry', [
            's_id' => $sid,
            'supplier' => $supplier,
            'bank_account_master' => $bankAccounts,
            'request_key' => bin2hex(random_bytes(16)),
            'old' => [],
        ]);
    }

    public function supplier_account_edit_entry($tranId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $tranId = (int) $tranId;
        if ($tranId <= 0) {
            return view('medical/placeholder', ['title' => 'Ledger Entry Not Found']);
        }

        $entry = $this->db->table('med_supplier_ledger')
            ->select('id, supplier_id, credit_debit, tran_date, amount, bank_id, tran_desc')
            ->where('id', $tranId)
            ->get()
            ->getRow();

        if (! $entry) {
            return view('medical/placeholder', ['title' => 'Ledger Entry Not Found']);
        }

        $supplier = $this->db->table('med_supplier')
            ->select('sid, name_supplier')
            ->where('sid', (int) ($entry->supplier_id ?? 0))
            ->get()
            ->getRow();

        if (! $supplier) {
            return view('medical/placeholder', ['title' => 'Supplier Not Found']);
        }

        $bankAccounts = $this->db->table('bank_account_master')
            ->orderBy('bank_account_name', 'ASC')
            ->get()
            ->getResult();

        $session = session();

        if (strtolower($this->request->getMethod()) === 'post') {
            $requestKey = trim((string) ($this->request->getPost('request_key') ?? ''));
            $processedKeys = (array) ($session->get('supplier_edit_entry_keys') ?? []);

            if ($requestKey !== '' && in_array($requestKey, $processedKeys, true)) {
                return $this->response->setJSON([
                    'status' => 1,
                    'msg' => 'Entry already updated. Duplicate request ignored.',
                ]);
            }

            $creditDebit = (int) ($this->request->getPost('cr_dr_type') ?? 0);
            $bankId = (int) ($this->request->getPost('mode_type') ?? 0);
            $tranDate = $this->normalizeDate((string) ($this->request->getPost('tran_date') ?? ''));
            $amount = (float) ($this->request->getPost('amount') ?? 0);
            $tranDesc = trim((string) ($this->request->getPost('tran_desc') ?? ''));

            if ($tranDate === '' || $amount <= 0 || ! in_array($creditDebit, [0, 1], true)) {
                $html = view('medical/supplier_account_edit_entry', [
                    'tran_id' => $tranId,
                    's_id' => (int) ($entry->supplier_id ?? 0),
                    'supplier' => $supplier,
                    'bank_account_master' => $bankAccounts,
                    'error' => 'Please enter valid date, amount and Cr/Dr type.',
                    'request_key' => bin2hex(random_bytes(16)),
                    'old' => [
                        'cr_dr_type' => $creditDebit,
                        'mode_type' => $bankId,
                        'tran_date' => $tranDate,
                        'amount' => (string) ($this->request->getPost('amount') ?? ''),
                        'tran_desc' => $tranDesc,
                    ],
                ]);

                return $this->response->setJSON([
                    'status' => 0,
                    'msg' => 'Validation failed',
                    'html' => $html,
                ]);
            }

            $this->db->table('med_supplier_ledger')
                ->where('id', $tranId)
                ->update([
                    'credit_debit' => $creditDebit,
                    'tran_date' => $tranDate,
                    'amount' => $amount,
                    'bank_id' => $bankId,
                    'tran_desc' => $tranDesc,
                ]);

            if ($requestKey !== '') {
                $processedKeys[] = $requestKey;
                $processedKeys = array_values(array_slice(array_unique($processedKeys), -100));
                $session->set('supplier_edit_entry_keys', $processedKeys);
            }

            $this->writeMedicalAdminActionLog('supplier_ledger_edit', 'Updated supplier ledger entry', [
                'tran_id' => $tranId,
                'supplier_id' => (int) ($entry->supplier_id ?? 0),
                'credit_debit' => $creditDebit,
                'amount' => $amount,
                'tran_date' => $tranDate,
            ]);

            return $this->response->setJSON([
                'status' => 1,
                'msg' => 'Ledger entry updated successfully.',
            ]);
        }

        return view('medical/supplier_account_edit_entry', [
            'tran_id' => $tranId,
            's_id' => (int) ($entry->supplier_id ?? 0),
            'supplier' => $supplier,
            'bank_account_master' => $bankAccounts,
            'request_key' => bin2hex(random_bytes(16)),
            'old' => [
                'cr_dr_type' => (int) ($entry->credit_debit ?? 0),
                'mode_type' => (int) ($entry->bank_id ?? 0),
                'tran_date' => ! empty($entry->tran_date) ? date('Y-m-d', strtotime((string) $entry->tran_date)) : date('Y-m-d'),
                'amount' => (string) ($entry->amount ?? ''),
                'tran_desc' => (string) ($entry->tran_desc ?? ''),
            ],
        ]);
    }

    public function invoice_item_log()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/invoice_item_log');
    }

    public function invoice_item_log_data()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $dateFrom = (string) ($this->request->getPost('date_from') ?? $this->request->getGet('date_from') ?? '');
        $dateTo = (string) ($this->request->getPost('date_to') ?? $this->request->getGet('date_to') ?? '');

        if ($dateFrom === '' || $dateTo === '') {
            $opdDateRange = (string) ($this->request->getPost('opd_date_range') ?? $this->request->getGet('opd_date_range') ?? '');
            if ($opdDateRange !== '') {
                [$dateFrom, $dateTo] = $this->parseLegacyDateRange($opdDateRange);
            }
        }

        if ($dateFrom === '' || $dateTo === '') {
            return view('medical/invoice_item_log_data', [
                'Invoice_history_log' => [],
            ]);
        }

        $paymentLogTable = null;
        if ($this->db->tableExists('paymentmedical_history_log')) {
            $paymentLogTable = 'paymentmedical_history_log';
        } elseif ($this->db->tableExists('payment_history_log')) {
            $paymentLogTable = 'payment_history_log';
        }

        $paymentLogDateField = null;
        if ($paymentLogTable !== null) {
            $logFields = $this->db->getFieldNames($paymentLogTable) ?? [];
            foreach (['insert_datetime', 'insert_time', 'created_at'] as $candidate) {
                if (in_array($candidate, $logFields, true)) {
                    $paymentLogDateField = $candidate;
                    break;
                }
            }
        }

        $db = \Config\Database::connect();
        $joinLogTable = $paymentLogTable !== null
            ? " LEFT JOIN {$paymentLogTable} l ON p.id=l.pay_id"
            : '';

        $logExpr = "''";
        $paymentLogConcatExpr = 'NULL';
        if ($paymentLogTable !== null) {
            $hasUpdateLog = $this->db->fieldExists('update_log', $paymentLogTable);
            $hasUpdateRemark = $this->db->fieldExists('update_remark', $paymentLogTable);

            if ($hasUpdateLog && $hasUpdateRemark) {
                $logExpr = "IFNULL(NULLIF(l.update_log,''), IFNULL(l.update_remark,''))";
            } elseif ($hasUpdateLog) {
                $logExpr = "IFNULL(l.update_log,'')";
            } elseif ($hasUpdateRemark) {
                $logExpr = "IFNULL(l.update_remark,'')";
            }

            $paymentLogConcatExpr = "CASE WHEN l.pay_id IS NULL THEN NULL ELSE CONCAT_WS('#',p.credit_debit,p.amount,{$logExpr}) END";
        }

                $paymentDateExistsSql = '';
                $binds = [$dateFrom, $dateTo, $dateFrom, $dateTo];

                if ($paymentLogTable !== null && $paymentLogDateField !== null) {
                        $paymentDateExistsSql = " OR EXISTS (
                                        SELECT 1
                                        FROM payment_history_medical px
                                        JOIN {$paymentLogTable} lx ON lx.pay_id=px.id
                                        WHERE px.Medical_invoice_id=m.id
                                            AND DATE(lx.{$paymentLogDateField}) BETWEEN ? AND ?
                                )";
                        $binds[] = $dateFrom;
                        $binds[] = $dateTo;
                }

                $sql = "SELECT m.inv_med_code,m.inv_date,m.inv_name,m.log,
                    GROUP_CONCAT(DISTINCT CONCAT_WS('#',d.item_Name,d.qty,d.price,d.twdisc_amount,d.delete_time,d.delete_by) SEPARATOR ';') AS del_item_list,
                    GROUP_CONCAT(DISTINCT {$paymentLogConcatExpr} SEPARATOR ';') AS payment_log
                                FROM ((invoice_med_master m LEFT JOIN inv_med_item_delete d ON d.inv_med_id=m.id)
                LEFT JOIN payment_history_medical p ON m.id=p.Medical_invoice_id)"
                . $joinLogTable . "
                                WHERE m.ipd_id=0
                                    AND (
                                        DATE(d.delete_time) BETWEEN ? AND ?
                                        OR (DATE(m.inv_date) BETWEEN ? AND ? AND ifnull(m.log,'')<>'')"
                                . $paymentDateExistsSql . "
                                    )
                GROUP BY m.id
                ORDER BY m.id DESC";

                $invoiceHistory = $db->query($sql, $binds)->getResult();

        return view('medical/invoice_item_log_data', [
            'Invoice_history_log' => $invoiceHistory,
        ]);
    }

    public function purchase_invoice_report()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierData = [];
        if ($this->db->tableExists('med_supplier')) {
            $supplierData = $this->db->table('med_supplier')
                ->select('sid,name_supplier')
                ->orderBy('name_supplier', 'ASC')
                ->get()
                ->getResult();
        }

        $medGstin = defined('H_Med_GST') ? strtoupper(trim((string) H_Med_GST)) : '';

        return view('medical/report_purchase_invoice', [
            'supplier_data' => $supplierData,
            'med_gstin' => $medGstin,
        ]);
    }

    public function report_gst_1()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $supplierData = [];
        if ($this->db->tableExists('med_supplier')) {
            $supplierData = $this->db->table('med_supplier')
                ->select('sid,name_supplier')
                ->orderBy('name_supplier', 'ASC')
                ->get()
                ->getResult();
        }

        $medGstin = defined('H_Med_GST') ? strtoupper(trim((string) H_Med_GST)) : '';

        return view('medical/report_purchase_invoice', [
            'supplier_data' => $supplierData,
            'med_gstin' => $medGstin,
        ]);
    }

    public function purchase_invoice_data_pdf($saleDateRange, $supplierId, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $saleDateRange);
        $supplierId = (int) $supplierId;
        $output = (int) $output;

        $rows = $this->getPurchaseInvoiceSummaryRows($dateFrom, $dateTo, $supplierId, false);

        if ($output === 1) {
            $content = view('medical/report_purchase_invoice_data', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
            ExportExcel($content, 'Purchase_Invoice_Report');
            return;
        }

        return view('medical/report_purchase_invoice_data', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function purchase_invoice_data($saleDateRange, $supplierId, $output = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $saleDateRange);
        $supplierId = (int) $supplierId;
        $output = (int) $output;

        $rows = $this->getPurchaseInvoiceSummaryRows($dateFrom, $dateTo, $supplierId, true);

        if ($output === 1) {
            $content = view('medical/report_purchase_invoice_data_gst', [
                'rows' => $rows,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
            ExportExcel($content, 'Purchase_Invoice_GST_Rate_Report');
            return;
        }

        return view('medical/report_purchase_invoice_data_gst', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function purchase_invoice_pdf($saleDateRange, $supplierId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $saleDateRange);
        $supplierId = (int) $supplierId;
        $rows = $this->getPurchaseInvoiceSummaryRows($dateFrom, $dateTo, $supplierId, false);

        $html = view('medical/report_purchase_invoice_data_pdf', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $mpdf->SetTitle('Purchase Invoice Report');
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('Purchase_Invoice_Report_' . date('Ymd_His') . '.pdf', 'S'));
    }

    public function purchase_invoice_pdf_gst($saleDateRange, $supplierId)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $saleDateRange);
        $supplierId = (int) $supplierId;
        $rows = $this->getPurchaseInvoiceSummaryRows($dateFrom, $dateTo, $supplierId, true);

        $html = view('medical/report_purchase_invoice_data_gst_pdf', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => WRITEPATH . 'cache',
        ]);

        $mpdf->SetTitle('Purchase Invoice GST Rate Report');
        $mpdf->WriteHTML($html);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($mpdf->Output('Purchase_Invoice_GST_Rate_Report_' . date('Ymd_His') . '.pdf', 'S'));
    }

    public function purchase_gstr2b($saleDateRange, $supplierId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $gstin = defined('H_Med_GST') ? strtoupper(trim((string) H_Med_GST)) : '';
        if ($gstin === '') {
            return $this->response
                ->setStatusCode(400)
                ->setBody('GSTIN not found. Please set H_Med_GST constant.');
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $saleDateRange);
        $supplierId = (int) $supplierId;
        $rows = $this->getPurchaseInvoiceSummaryRows($dateFrom, $dateTo, $supplierId, true);

        $payload = $this->buildPurchaseGstr2bPayload($dateFrom, $dateTo, $gstin, $rows);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="GSTR2B_' . $payload['fp'] . '_' . date('Ymd_His') . '.json"')
            ->setBody($json === false ? '{}' : $json);
    }

    public function purchase_gstr3b($saleDateRange, $supplierId = 0)
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $gstin = defined('H_Med_GST') ? strtoupper(trim((string) H_Med_GST)) : '';
        if ($gstin === '') {
            return $this->response
                ->setStatusCode(400)
                ->setBody('GSTIN not found. Please set H_Med_GST constant.');
        }

        [$dateFrom, $dateTo] = $this->parseLegacyDateRange((string) $saleDateRange);
        $supplierId = (int) $supplierId;
        $rows = $this->getPurchaseInvoiceSummaryRows($dateFrom, $dateTo, $supplierId, true);

        $payload = $this->buildPurchaseGstr3bPayload($dateFrom, $dateTo, $gstin, $rows);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="GSTR3B_' . $payload['fp'] . '_' . date('Ymd_His') . '.json"')
            ->setBody($json === false ? '{}' : $json);
    }

    private function parseLegacyDateRange(string $range): array
    {
        $parts = explode('S', $range);
        $from = $this->normalizeDate((string) ($parts[0] ?? ''));
        $to = $this->normalizeDate((string) ($parts[1] ?? ''));

        if ($from === '' || $to === '') {
            $today = date('Y-m-d');
            return [$today, $today];
        }

        if ($from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    private function getPurchaseInvoiceSummaryRows(string $dateFrom, string $dateTo, int $supplierId, bool $includeRateBreakup): array
    {
        if (! $this->db->tableExists('purchase_invoice')) {
            return [];
        }

        $pFields = $this->db->getFieldNames('purchase_invoice') ?? [];
        $hasSupplier = $this->db->tableExists('med_supplier');
        $hasItems = $this->db->tableExists('purchase_invoice_item');
        $sFields = $hasSupplier ? ($this->db->getFieldNames('med_supplier') ?? []) : [];
        $tFields = $hasItems ? ($this->db->getFieldNames('purchase_invoice_item') ?? []) : [];

        $invoiceNoCol = in_array('Invoice_no', $pFields, true) ? 'p.Invoice_no' : (in_array('invoice_no', $pFields, true) ? 'p.invoice_no' : 'p.id');
        $tNetCol = in_array('T_Net_Amount', $pFields, true) ? 'p.T_Net_Amount' : (in_array('t_net_amount', $pFields, true) ? 'p.t_net_amount' : '0');
        $taxableCol = in_array('Taxable_Amt', $pFields, true) ? 'p.Taxable_Amt' : (in_array('taxable_amt', $pFields, true) ? 'p.taxable_amt' : '0');
        $cgstCol = in_array('CGST_Amt', $pFields, true) ? 'p.CGST_Amt' : (in_array('cgst_amt', $pFields, true) ? 'p.cgst_amt' : '0');
        $sgstCol = in_array('SGST_Amt', $pFields, true) ? 'p.SGST_Amt' : (in_array('sgst_amt', $pFields, true) ? 'p.sgst_amt' : '0');

        $select = [
            'p.id AS id',
            $invoiceNoCol . ' AS invoice_no',
            "DATE_FORMAT(p.date_of_invoice,'%d-%m-%Y') AS str_date_of_invoice",
            ($hasSupplier ? 'TRIM(IFNULL(s.name_supplier,\'-\'))' : "'-'") . ' AS name_supplier',
            ($hasSupplier && in_array('short_name', $sFields, true) ? 'IFNULL(s.short_name,\'-\')' : "'-'") . ' AS short_name',
            ($hasSupplier && in_array('gst_no', $sFields, true) ? 'IFNULL(s.gst_no,\'-\')' : "'-'") . ' AS gst_no',
            ($hasSupplier && in_array('state', $sFields, true) ? 'IFNULL(s.state,\'-\')' : "'-'") . ' AS state',
            'ROUND(IFNULL(' . $tNetCol . ',0),2) AS tamount',
            'ROUND(IFNULL(' . $taxableCol . ',0),2) AS taxable_amount',
            'ROUND(IFNULL(' . $cgstCol . ',0) + IFNULL(' . $sgstCol . ',0),2) AS gst_amount',
        ];

        if ($includeRateBreakup && $hasItems) {
            $rateCol = in_array('CGST_per', $tFields, true) ? 'IFNULL(t.CGST_per,0)' : '0';
            $itemTaxable = in_array('taxable_amount', $tFields, true) ? 'IFNULL(t.taxable_amount,0)' : '0';
            $itemCGST = in_array('CGST', $tFields, true) ? 'IFNULL(t.CGST,0)' : '0';
            $itemSGST = in_array('SGST', $tFields, true) ? 'IFNULL(t.SGST,0)' : '0';
            $select[] = 'ROUND(SUM(CASE WHEN ' . $rateCol . '=0 THEN ' . $itemTaxable . ' ELSE 0 END),2) AS tot_gst_0';
            $select[] = 'ROUND(SUM(CASE WHEN ' . $rateCol . '=2.5 THEN ' . $itemTaxable . ' ELSE 0 END),2) AS tot_gst_5';
            $select[] = 'ROUND(SUM(CASE WHEN ' . $rateCol . '=6 THEN ' . $itemTaxable . ' ELSE 0 END),2) AS tot_gst_12';
            $select[] = 'ROUND(SUM(CASE WHEN ' . $rateCol . '=9 THEN ' . $itemTaxable . ' ELSE 0 END),2) AS tot_gst_18';
            $select[] = 'ROUND(SUM(CASE WHEN ' . $rateCol . '=14 THEN ' . $itemTaxable . ' ELSE 0 END),2) AS tot_gst_28';
            $select[] = 'ROUND(SUM(' . $itemCGST . '),2) AS total_cgst';
            $select[] = 'ROUND(SUM(' . $itemSGST . '),2) AS total_sgst';
        }

        $builder = $this->db->table('purchase_invoice p')
            ->select(implode(',', $select), false)
            ->where('DATE(p.date_of_invoice) >=', $dateFrom)
            ->where('DATE(p.date_of_invoice) <=', $dateTo);

        if ($hasSupplier) {
            $builder->join('med_supplier s', 'p.sid=s.sid', 'left');
        }
        if ($includeRateBreakup && $hasItems) {
            $builder->join('purchase_invoice_item t', 'p.id=t.purchase_id', 'left');
        }
        if ($supplierId > 0 && $hasSupplier) {
            $builder->where('s.sid', $supplierId);
        }

        $builder->groupBy('p.id')
            ->orderBy('name_supplier', 'ASC')
            ->orderBy('p.date_of_invoice', 'ASC')
            ->orderBy('p.id', 'ASC');

        return $builder->get()->getResultArray();
    }

    private function buildPurchaseGstr2bPayload(string $dateFrom, string $dateTo, string $gstin, array $rows): array
    {
        $fp = date('mY', strtotime($dateTo));

        $summary = [];
        $totTaxable = 0.0;
        $totGst = 0.0;
        $totAmount = 0.0;

        foreach ($rows as $row) {
            $supplierGstin = strtoupper(trim((string) ($row['gst_no'] ?? '')));
            if ($supplierGstin === '' || $supplierGstin === '-') {
                $supplierGstin = 'UNREGISTERED';
            }

            if (! isset($summary[$supplierGstin])) {
                $summary[$supplierGstin] = [
                    'ctin' => $supplierGstin,
                    'supplier_name' => (string) ($row['name_supplier'] ?? '-'),
                    'inv_count' => 0,
                    'taxable' => 0.0,
                    'gst' => 0.0,
                    'invoice_value' => 0.0,
                ];
            }

            $taxable = (float) ($row['taxable_amount'] ?? 0);
            $gst = (float) ($row['gst_amount'] ?? 0);
            $invoiceValue = (float) ($row['tamount'] ?? 0);

            $summary[$supplierGstin]['inv_count']++;
            $summary[$supplierGstin]['taxable'] += $taxable;
            $summary[$supplierGstin]['gst'] += $gst;
            $summary[$supplierGstin]['invoice_value'] += $invoiceValue;

            $totTaxable += $taxable;
            $totGst += $gst;
            $totAmount += $invoiceValue;
        }

        $suppliers = array_values(array_map(static function (array $item): array {
            $item['taxable'] = round((float) $item['taxable'], 2);
            $item['gst'] = round((float) $item['gst'], 2);
            $item['invoice_value'] = round((float) $item['invoice_value'], 2);
            return $item;
        }, $summary));

        return [
            'gstin' => $gstin,
            'fp' => $fp,
            'type' => 'GSTR2B_PURCHASE_SUMMARY',
            'gen_date' => date('Y-m-d H:i:s'),
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'totals' => [
                'invoice_count' => count($rows),
                'taxable_value' => round($totTaxable, 2),
                'gst_value' => round($totGst, 2),
                'invoice_value' => round($totAmount, 2),
            ],
            'suppliers' => $suppliers,
        ];
    }

    private function buildPurchaseGstr3bPayload(string $dateFrom, string $dateTo, string $gstin, array $rows): array
    {
        $fp = date('mY', strtotime($dateTo));

        $taxable = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;

        foreach ($rows as $row) {
            $taxable += (float) ($row['taxable_amount'] ?? 0);
            $cgst += (float) ($row['total_cgst'] ?? 0);
            $sgst += (float) ($row['total_sgst'] ?? 0);
        }

        return [
            'gstin' => $gstin,
            'fp' => $fp,
            'type' => 'GSTR3B_ITC_SUMMARY',
            'gen_date' => date('Y-m-d H:i:s'),
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'inward_supplies' => [
                'taxable_value' => round($taxable, 2),
                'igst' => round($igst, 2),
                'cgst' => round($cgst, 2),
                'sgst' => round($sgst, 2),
                'cess' => 0,
            ],
            'itc_eligible' => [
                'igst' => round($igst, 2),
                'cgst' => round($cgst, 2),
                'sgst' => round($sgst, 2),
                'cess' => 0,
            ],
        ];
    }

    public function master()
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        return view('medical/master');
    }

    public function master_report(string $slug = '')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        $slug = trim($slug);
        $report = $this->buildMasterReport($slug);

        return view('medical/master_report', $report);
    }

    private function buildMasterReport(string $slug): array
    {
        $titleMap = [
            'purchase-invoice' => 'Purchase Invoice',
            'med-payment-edit' => 'Med. Payment Edit',
            'med-payment-logs' => 'Med. Payment Edit Logs',
            'ipd-discharge-report' => 'IPD Discharge Report',
            'daily-med-sale' => 'Day wise Medicine Sale',
            'daily-doc-med-sale' => 'Day wise & Doc Wise Medicine Sale',
            'company-med-sale' => 'Company Wise Medicine Sale',
            'lost-medicine' => 'Lost Medicine',
            'short-medicine' => 'Short Medicine List',
            'stock-transfer' => 'Stock Transfer',
            'product-merge' => 'Product Merge',
            'ipd-sale-report' => 'IPD Sale Report',
            'ipd-invoice-bill-type' => 'IPD Invoice Bill Type',
            'opd-org-report' => 'OPD Org Report',
            'opd-cash-pending' => 'OPD CASH Pending Report',
            'opd-old-balance-paid' => 'OPD Old Balance Paid Report',
            'invoice-list-gst' => 'Invoice List for GST',
            'gst-report' => 'GST Report',
            'bank-ledger' => 'Bank Ledger',
            'purchase-gst-report' => 'Purchase GST Report',
        ];

        $title = $titleMap[$slug] ?? 'Pharmacy Master Report';
        $rows = [];
        $columns = [];
        $note = '';

        switch ($slug) {
            case 'purchase-invoice':
                if ($this->db->tableExists('purchase_invoice')) {
                    $pFields = $this->db->getFieldNames('purchase_invoice') ?? [];
                    $sJoin = $this->db->tableExists('med_supplier');
                    $invNoCol = in_array('Invoice_no', $pFields, true) ? 'p.Invoice_no' : (in_array('invoice_no', $pFields, true) ? 'p.invoice_no' : 'p.id');
                    $amtCol = in_array('T_Net_Amount', $pFields, true) ? 'p.T_Net_Amount' : (in_array('t_net_amount', $pFields, true) ? 'p.t_net_amount' : '0');

                    $sql = "SELECT p.id, {$invNoCol} AS invoice_no, DATE(p.date_of_invoice) AS invoice_date, "
                        . ($sJoin ? "IFNULL(s.name_supplier,'-')" : "'-'") . " AS supplier_name, {$amtCol} AS net_amount
                           FROM purchase_invoice p "
                        . ($sJoin ? "LEFT JOIN med_supplier s ON s.sid=p.sid " : "")
                        . " ORDER BY p.id DESC LIMIT 300";

                    $rows = $this->db->query($sql)->getResultArray();
                    $columns = ['id', 'invoice_no', 'invoice_date', 'supplier_name', 'net_amount'];
                }
                break;

            case 'med-payment-edit':
                if ($this->db->tableExists('payment_history_medical')) {
                    $rows = $this->db->table('payment_history_medical')->orderBy('id', 'DESC')->limit(300)->get()->getResultArray();
                    $columns = ! empty($rows) ? array_keys($rows[0]) : [];
                }
                break;

            case 'med-payment-logs':
                if ($this->db->tableExists('paymentmedical_history_log')) {
                    $rows = $this->db->table('paymentmedical_history_log')->orderBy('id', 'DESC')->limit(300)->get()->getResultArray();
                    $columns = ! empty($rows) ? array_keys($rows[0]) : [];
                }
                break;

            case 'ipd-discharge-report':
                if ($this->db->tableExists('ipd_master')) {
                    $rows = $this->db->query("SELECT * FROM ipd_master WHERE ifnull(ipd_status,0)<>0 ORDER BY id DESC LIMIT 300")->getResultArray();
                    $columns = ! empty($rows) ? array_keys($rows[0]) : [];
                }
                break;

            case 'daily-med-sale':
                if ($this->db->tableExists('invoice_med_master')) {
                    $rows = $this->db->query("SELECT DATE(inv_date) AS sale_date, COUNT(*) AS invoice_count, SUM(IFNULL(net_amount,0)) AS net_amount
                        FROM invoice_med_master
                        WHERE IFNULL(sale_return,0)=0
                        GROUP BY DATE(inv_date)
                        ORDER BY sale_date DESC
                        LIMIT 120")->getResultArray();
                    $columns = ['sale_date', 'invoice_count', 'net_amount'];
                }
                break;

            case 'daily-doc-med-sale':
                if ($this->db->tableExists('invoice_med_master')) {
                    $mFields = $this->db->getFieldNames('invoice_med_master') ?? [];
                    $docCol = in_array('doc_name', $mFields, true) ? 'doc_name' : (in_array('doc_id', $mFields, true) ? 'doc_id' : "'-'");
                    $rows = $this->db->query("SELECT DATE(inv_date) AS sale_date, {$docCol} AS doctor, COUNT(*) AS invoice_count, SUM(IFNULL(net_amount,0)) AS net_amount
                        FROM invoice_med_master
                        WHERE IFNULL(sale_return,0)=0
                        GROUP BY DATE(inv_date), {$docCol}
                        ORDER BY sale_date DESC
                        LIMIT 300")->getResultArray();
                    $columns = ['sale_date', 'doctor', 'invoice_count', 'net_amount'];
                }
                break;

            case 'company-med-sale':
                if ($this->db->tableExists('inv_med_item') && $this->db->tableExists('med_product_master')) {
                    $pFields = $this->db->getFieldNames('med_product_master') ?? [];
                    $companyCol = in_array('company_name', $pFields, true) ? 'p.company_name' : (in_array('company', $pFields, true) ? 'p.company' : "'-'");
                    $rows = $this->db->query("SELECT {$companyCol} AS company, COUNT(*) AS item_rows, SUM(IFNULL(i.amount,0)) AS amount
                        FROM inv_med_item i
                        JOIN med_product_master p ON p.id=i.item_code
                        GROUP BY {$companyCol}
                        ORDER BY amount DESC
                        LIMIT 200")->getResultArray();
                    $columns = ['company', 'item_rows', 'amount'];
                }
                break;

            case 'lost-medicine':
                if ($this->db->tableExists('purchase_invoice_item') && $this->db->tableExists('med_product_master')) {
                    $rows = $this->db->query("SELECT p.item_name, SUM(IFNULL(s.total_lost_unit,0)) AS lost_unit
                        FROM purchase_invoice_item s
                        JOIN med_product_master p ON p.id=s.item_code
                        WHERE IFNULL(s.total_lost_unit,0)>0
                        GROUP BY p.item_name
                        ORDER BY lost_unit DESC
                        LIMIT 200")->getResultArray();
                    $columns = ['item_name', 'lost_unit'];
                }
                break;

            case 'short-medicine':
                if ($this->db->tableExists('purchase_invoice_item') && $this->db->tableExists('med_product_master')) {
                    $rows = $this->db->query("SELECT p.item_name,
                        SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) AS current_unit_qty,
                        IFNULL(p.re_order_qty,0) AS re_order_qty
                        FROM purchase_invoice_item s
                        JOIN med_product_master p ON p.id=s.item_code
                        WHERE s.item_return=0
                        GROUP BY p.id,p.item_name,p.re_order_qty
                        HAVING current_unit_qty<=IFNULL(p.re_order_qty,0)
                        ORDER BY current_unit_qty ASC
                        LIMIT 200")->getResultArray();
                    $columns = ['item_name', 'current_unit_qty', 're_order_qty'];
                }
                break;

            case 'stock-transfer':
                $note = 'Stock Transfer interactive tool is queued for full CI4 migration; this report page confirms the module link is active.';
                break;

            case 'product-merge':
                $note = 'Product Merge interactive tool is queued for full CI4 migration; this report page confirms the module link is active.';
                break;

            case 'ipd-sale-report':
                if ($this->db->tableExists('invoice_med_master')) {
                    $rows = $this->db->query("SELECT DATE(inv_date) AS sale_date, COUNT(*) AS invoice_count, SUM(IFNULL(net_amount,0)) AS net_amount
                        FROM invoice_med_master
                        WHERE IFNULL(sale_return,0)=0 AND IFNULL(ipd_id,0)>0
                        GROUP BY DATE(inv_date)
                        ORDER BY sale_date DESC
                        LIMIT 120")->getResultArray();
                    $columns = ['sale_date', 'invoice_count', 'net_amount'];
                }
                break;

            case 'ipd-invoice-bill-type':
                if ($this->db->tableExists('invoice_med_master')) {
                    $rows = $this->db->query("SELECT
                        CASE
                            WHEN IFNULL(ipd_credit,0)>0 AND IFNULL(group_invoice_id,0)>0 THEN 'PACKAGE'
                            WHEN IFNULL(ipd_credit,0)>0 THEN 'CREDIT'
                            ELSE 'CASH'
                        END AS bill_type,
                        COUNT(*) AS invoice_count,
                        SUM(IFNULL(net_amount,0)) AS net_amount
                        FROM invoice_med_master
                        WHERE IFNULL(sale_return,0)=0 AND IFNULL(ipd_id,0)>0
                        GROUP BY bill_type
                        ORDER BY bill_type")->getResultArray();
                    $columns = ['bill_type', 'invoice_count', 'net_amount'];
                }
                break;

            case 'opd-org-report':
                if ($this->db->tableExists('invoice_med_master')) {
                    $rows = $this->db->query("SELECT IFNULL(case_id,0) AS case_id, COUNT(*) AS invoice_count, SUM(IFNULL(net_amount,0)) AS net_amount
                        FROM invoice_med_master
                        WHERE IFNULL(sale_return,0)=0 AND IFNULL(ipd_id,0)=0 AND IFNULL(case_id,0)>0
                        GROUP BY IFNULL(case_id,0)
                        ORDER BY net_amount DESC
                        LIMIT 200")->getResultArray();
                    $columns = ['case_id', 'invoice_count', 'net_amount'];
                }
                break;

            case 'opd-cash-pending':
                if ($this->db->tableExists('invoice_med_master')) {
                    $rows = $this->db->query("SELECT id, inv_med_code, inv_date, inv_name, IFNULL(net_amount,0) AS net_amount,
                        IFNULL(payment_received,0) AS payment_received, IFNULL(payment_balance,0) AS payment_balance
                        FROM invoice_med_master
                        WHERE IFNULL(sale_return,0)=0 AND IFNULL(ipd_id,0)=0 AND IFNULL(case_credit,0)=0 AND IFNULL(payment_balance,0)>0
                        ORDER BY id DESC
                        LIMIT 300")->getResultArray();
                    $columns = ['id', 'inv_med_code', 'inv_date', 'inv_name', 'net_amount', 'payment_received', 'payment_balance'];
                }
                break;

            case 'opd-old-balance-paid':
                if ($this->db->tableExists('payment_history_medical') && $this->db->tableExists('invoice_med_master')) {
                    $rows = $this->db->query("SELECT p.id, p.payment_date, p.Medical_invoice_id, m.inv_med_code, m.inv_name,
                        IFNULL(p.amount,0) AS amount, IFNULL(p.credit_debit,0) AS credit_debit
                        FROM payment_history_medical p
                        LEFT JOIN invoice_med_master m ON m.id=p.Medical_invoice_id
                        WHERE IFNULL(m.ipd_id,0)=0
                        ORDER BY p.id DESC
                        LIMIT 300")->getResultArray();
                    $columns = ['id', 'payment_date', 'Medical_invoice_id', 'inv_med_code', 'inv_name', 'amount', 'credit_debit'];
                }
                break;

            case 'invoice-list-gst':
                if ($this->db->tableExists('invoice_med_master')) {
                    $rows = $this->db->query("SELECT id, inv_med_code, inv_date, inv_name,
                        IFNULL(net_amount,0) AS net_amount,
                        IFNULL(CGST_Tamount,0) AS cgst_amount,
                        IFNULL(SGST_Tamount,0) AS sgst_amount
                        FROM invoice_med_master
                        WHERE IFNULL(sale_return,0)=0
                        ORDER BY id DESC
                        LIMIT 300")->getResultArray();
                    $columns = ['id', 'inv_med_code', 'inv_date', 'inv_name', 'net_amount', 'cgst_amount', 'sgst_amount'];
                }
                break;

            case 'gst-report':
                if ($this->db->tableExists('invoice_med_master')) {
                    $rows = $this->db->query("SELECT DATE_FORMAT(inv_date,'%Y-%m') AS bill_month,
                        SUM(IFNULL(CGST_Tamount,0)) AS cgst_amount,
                        SUM(IFNULL(SGST_Tamount,0)) AS sgst_amount,
                        SUM(IFNULL(CGST_Tamount,0)+IFNULL(SGST_Tamount,0)) AS total_gst
                        FROM invoice_med_master
                        WHERE IFNULL(sale_return,0)=0
                        GROUP BY DATE_FORMAT(inv_date,'%Y-%m')
                        ORDER BY bill_month DESC
                        LIMIT 24")->getResultArray();
                    $columns = ['bill_month', 'cgst_amount', 'sgst_amount', 'total_gst'];
                }
                break;

            case 'bank-ledger':
                if ($this->db->tableExists('payment_history_medical')) {
                    $rows = $this->db->query("SELECT IFNULL(bank_id,0) AS bank_id,
                        COUNT(*) AS tran_count,
                        SUM(IFNULL(amount,0)) AS amount_total
                        FROM payment_history_medical
                        GROUP BY IFNULL(bank_id,0)
                        ORDER BY amount_total DESC")->getResultArray();
                    $columns = ['bank_id', 'tran_count', 'amount_total'];
                }
                break;

            case 'purchase-gst-report':
                if ($this->db->tableExists('purchase_invoice_item')) {
                    $rows = $this->db->query("SELECT IFNULL(CGST_per,0) AS cgst_per,
                        COUNT(*) AS row_count,
                        SUM(IFNULL(net_amount,0)) AS taxable_value,
                        SUM(IFNULL(cgst_amount,0)) AS cgst_amount,
                        SUM(IFNULL(sgst_amount,0)) AS sgst_amount
                        FROM purchase_invoice_item
                        GROUP BY IFNULL(CGST_per,0)
                        ORDER BY cgst_per")->getResultArray();
                    $columns = ['cgst_per', 'row_count', 'taxable_value', 'cgst_amount', 'sgst_amount'];
                }
                break;

            default:
                $note = 'Report mapping is not available for this slug.';
                break;
        }

        if (empty($rows) && $note === '') {
            $note = 'No records found for this report in current dataset.';
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'columns' => $columns,
            'rows' => $rows,
            'note' => $note,
        ];
    }

    public function master_link(string $slug = '')
    {
        if ($deny = $this->ensurePharmacyAccess()) {
            return $deny;
        }

        if ($slug === 'med-payment-edit') {
            return redirect()->to(base_url('Payment_Medical'));
        }

        return redirect()->to(base_url('Medical/master_report/' . $slug));
    }
}
