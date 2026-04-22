<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Mpdf\Mpdf;

class Storestock extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // -------------------------------------------------------------------------
    // Auth helper
    // -------------------------------------------------------------------------

    private function ensureStoreAccess()
    {
        $user = service('auth')->user();

        $allowed = false;
        if ($user && method_exists($user, 'can')) {
            $allowed = $user->can('hospital_stock.access') || $user->can('pharmacy.access');
        }

        if (! $allowed && $user && method_exists($user, 'inGroup')) {
            $allowed = $user->inGroup(
                'superadmin', 'admin', 'developer',
                'stock_manager', 'stock_requester', 'stock_issuer', 'storekeeper'
            );
        }

        if ($allowed) {
            return null;
        }

        return $this->response
            ->setStatusCode(403)
            ->setBody('<div class="alert alert-danger m-3">Access denied for Hospital Stock module.</div>');
    }

    private function actorLabel(): string
    {
        $user = service('auth')->user();
        if (! $user) {
            return 'System[' . date('Y-m-d H:i:s') . ']';
        }
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        if ($name === '' || $name === ' ') {
            $name = (string) ($user->username ?? ($user->email ?? 'User'));
        }
        $userId = (int) ($user->id ?? 0);
        return $name . '[' . $userId . '][' . date('d-m-Y H:i:s') . ']';
    }

    private function safeScalar(string $sql, array $bindings = [], $default = 0)
    {
        try {
            $row = $this->db->query($sql, $bindings)->getRowArray();
            if (! is_array($row) || empty($row)) {
                return $default;
            }
            $val = reset($row);
            return $val !== null ? $val : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function safeRows(string $sql, array $bindings = []): array
    {
        try {
            return $this->db->query($sql, $bindings)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function buildDashboardInsights(): array
    {
        $todayIndents = (int) $this->safeScalar(
            "SELECT COUNT(*) AS c FROM invoice_stock_master WHERE DATE(indent_date) = CURDATE()",
            [],
            0
        );

        $monthIndents = (int) $this->safeScalar(
            "SELECT COUNT(*) AS c FROM invoice_stock_master WHERE DATE_FORMAT(indent_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')",
            [],
            0
        );

        $pendingPurchase = (int) $this->safeScalar(
            "SELECT COUNT(*) AS c FROM purchase_invoice_stock WHERE IFNULL(inv_status, 0) = 0",
            [],
            0
        );

        $lowStockCount = (int) $this->safeScalar(
            "SELECT COUNT(*) AS c FROM (
                SELECT p.id,
                       COALESCE(SUM(s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit), 0) AS cur_units,
                       COALESCE(MAX(p.re_order_qty), 0) AS re_order_qty,
                       COALESCE(MAX(NULLIF(s.packing, 0)), 1) AS packing
                FROM med_store_product_master p
                LEFT JOIN purchase_invoice_item_stock s
                    ON p.id = s.item_code
                   AND s.remove_item = 0
                   AND s.item_return = 0
                WHERE IFNULL(p.is_continue, 1) = 1
                GROUP BY p.id
                HAVING cur_units <= (re_order_qty * packing)
            ) x",
            [],
            0
        );

        $nearExpiryCount = (int) $this->safeScalar(
            "SELECT COUNT(*) AS c
             FROM purchase_invoice_item_stock s
             WHERE s.remove_item = 0
               AND s.item_return = 0
               AND (s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit) > 0
               AND s.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)",
            [],
            0
        );

        $stockValue = (float) $this->safeScalar(
            "SELECT ROUND(SUM(
                (s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit)
                * IFNULL(s.purchase_unit_rate, s.purchase_price)
            ), 2) AS v
            FROM purchase_invoice_item_stock s
            WHERE s.remove_item = 0
              AND s.item_return = 0
              AND (s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit) > 0",
            [],
            0
        );

        $lowStockItems = $this->safeRows(
            "SELECT p.item_name,
                    COALESCE(MAX(p.re_order_qty), 0) AS re_order_qty,
                    COALESCE(MAX(NULLIF(s.packing, 0)), 1) AS packing,
                    ROUND(COALESCE(SUM(s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit), 0), 2) AS cur_units
             FROM med_store_product_master p
             LEFT JOIN purchase_invoice_item_stock s
                ON p.id = s.item_code
               AND s.remove_item = 0
               AND s.item_return = 0
             WHERE IFNULL(p.is_continue, 1) = 1
             GROUP BY p.id, p.item_name
             HAVING cur_units <= (re_order_qty * packing)
             ORDER BY cur_units ASC
             LIMIT 8"
        );

        $nearExpiryItems = $this->safeRows(
            "SELECT s.item_name,
                    s.batch_no,
                    DATE_FORMAT(s.expiry_date, '%d-%m-%Y') AS expiry_str,
                    DATEDIFF(s.expiry_date, CURDATE()) AS days_left,
                    ROUND((s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit) / NULLIF(s.packing, 0), 2) AS cur_packs
             FROM purchase_invoice_item_stock s
             WHERE s.remove_item = 0
               AND s.item_return = 0
               AND (s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit) > 0
               AND s.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
             ORDER BY s.expiry_date ASC
             LIMIT 8"
        );

        return [
            'stats' => [
                'today_indents'     => $todayIndents,
                'month_indents'     => $monthIndents,
                'pending_purchase'  => $pendingPurchase,
                'low_stock_count'   => $lowStockCount,
                'near_expiry_count' => $nearExpiryCount,
                'stock_value'       => $stockValue,
            ],
            'lists' => [
                'low_stock_items'   => $lowStockItems,
                'near_expiry_items' => $nearExpiryItems,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function index()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $data = $this->buildDashboardInsights();
        return view('storestock/dashboard', $data);
    }

    // -------------------------------------------------------------------------
    // Indent List
    // -------------------------------------------------------------------------

    public function indent_list()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }
        return view('storestock/indent_list');
    }

    // -------------------------------------------------------------------------
    // Indent DataTable (server-side)
    // -------------------------------------------------------------------------

    public function getIndentTable()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $columns = [
            0 => 'indent_code',
            1 => 'issued_name',
            2 => 'indent_date_str',
            3 => 'id',
        ];

        $draw   = (int) ($this->request->getPost('draw') ?? $this->request->getGet('draw') ?? 1);
        $start  = (int) ($this->request->getPost('start') ?? $this->request->getGet('start') ?? 0);
        $length = (int) ($this->request->getPost('length') ?? $this->request->getGet('length') ?? 10);

        $orderColIdx = (int) ($this->request->getPost('order[0][column]') ?? 0);
        $orderDir    = $this->request->getPost('order[0][dir]') === 'asc' ? 'asc' : 'desc';
        $orderCol    = $columns[$orderColIdx] ?? 'id';

        // Column search values
        $searchIndentCode  = (string) ($this->request->getPost('columns[0][search][value]') ?? '');
        $searchIssuedName  = (string) ($this->request->getPost('columns[1][search][value]') ?? '');
        $searchIndentDate  = (string) ($this->request->getPost('columns[2][search][value]') ?? '');

        // Total records
        $totalData = (int) $this->db->query("SELECT COUNT(*) AS no_rec FROM invoice_stock_master")->getRow()->no_rec;
        $totalFiltered = $totalData;

        // Build parameterised WHERE
        $whereSQL    = ' WHERE 1=1';
        $whereParams = [];

        if ($searchIndentCode !== '') {
            $esc = $this->db->escapeLikeString($searchIndentCode);
            $whereSQL .= " AND indent_code LIKE '%{$esc}%'";
        }

        if ($searchIssuedName !== '') {
            $esc = $this->db->escapeLikeString($searchIssuedName);
            $whereSQL .= " AND issued_name LIKE '%{$esc}%'";
        }

        if ($searchIndentDate !== '') {
            // Date column — use parameterised bind
            $whereSQL .= ' AND DATE(indent_date) = ?';
            $whereParams[] = $searchIndentDate;
        }

        if ($searchIndentCode !== '' || $searchIssuedName !== '' || $searchIndentDate !== '') {
            $countSql = "SELECT COUNT(*) AS no_rec FROM invoice_stock_master{$whereSQL}";
            $totalFiltered = (int) $this->db->query($countSql, $whereParams)->getRow()->no_rec;
        }

        $orderClause = " ORDER BY {$orderCol} {$orderDir}, id desc";
        $limitClause = ' LIMIT ' . $start . ', ' . $length;

        $selectSQL = "SELECT m.id, m.indent_code, m.issued_name, m.indent_date,
                        DATE_FORMAT(m.indent_date,'%d-%m-%Y') AS indent_date_str
                      FROM invoice_stock_master m{$whereSQL}{$orderClause}{$limitClause}";

        $rdata = $this->db->query($selectSQL, $whereParams)->getResultArray();

        $rows = [];
        foreach ($rdata as $aRow) {
            $rows[] = [
                $aRow['indent_code'],
                $aRow['issued_name'],
                $aRow['indent_date_str'],
                $aRow['id'],
            ];
        }

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data'            => $rows,
        ]);
    }

    // -------------------------------------------------------------------------
    // New Indent form
    // -------------------------------------------------------------------------

    public function new_indent()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $data['location_master'] = $this->db->query("SELECT * FROM location_master ORDER BY loc_name")->getResult();
        $data['employee_master'] = $this->db->query("SELECT * FROM employee_master ORDER BY emp_name")->getResult();

        return view('storestock/new_indent', $data);
    }

    // -------------------------------------------------------------------------
    // Create Indent
    // -------------------------------------------------------------------------

    public function indent_create(int $location_type)
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $date_indent = $this->request->getPost('date_indent');
        $loc_id      = (int) $this->request->getPost('loc_id');

        // Convert dd/mm/yyyy → yyyy-mm-dd
        $mysql_date = date('Y-m-d');
        if ($date_indent) {
            $parts = explode('/', trim($date_indent));
            if (count($parts) === 3) {
                $mysql_date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            } else {
                $mysql_date = date('Y-m-d', strtotime($date_indent));
            }
        }

        $issued_name = '';

        if ($location_type == 1) {
            $row = $this->db->query("SELECT * FROM location_master WHERE l_id = ?", [$loc_id])->getRow();
            if ($row) {
                $issued_name = $row->loc_name;
            }
        } elseif ($location_type == 2) {
            $row = $this->db->query("SELECT * FROM employee_master WHERE emp_id = ?", [$loc_id])->getRow();
            if ($row) {
                $issued_name = $row->emp_name . ' [' . $row->emp_code . ']';
            }
        }

        $this->db->table('invoice_stock_master')->insert([
            'indent_date'   => $mysql_date,
            'location_type' => $location_type,
            'location_id'   => $loc_id,
            'issued_name'   => $issued_name,
        ]);

        $insert_id = $this->db->insertID();

        // Generate indent_code like S{ym}{padded_id}
        $ym          = date('ym');
        $indent_code = 'S' . $ym . str_pad($insert_id, 5, '0', STR_PAD_LEFT);
        $this->db->table('invoice_stock_master')->where('id', $insert_id)->update(['indent_code' => $indent_code]);

        return redirect()->to(site_url('Storestock/Indent_show/' . $insert_id));
    }

    // -------------------------------------------------------------------------
    // Show / Edit Indent
    // -------------------------------------------------------------------------

    public function indent_show(int $indent_id)
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $data['invoice_stock_master'] = $this->db->query(
            "SELECT * FROM invoice_stock_master WHERE id = ?",
            [$indent_id]
        )->getResult();

        $data['inv_stock_item'] = $this->db->query(
            "SELECT i.*, IFNULL(DATEDIFF(expiry, CURDATE()), 1000) AS no_day,
             SUM(p.total_unit - p.total_sale_unit - p.total_return_unit - p.total_lost_unit) / p.packing AS cur_qty
             FROM inv_stock_item i
             JOIN purchase_invoice_item_stock p ON i.item_code = p.item_code AND p.remove_item = 0
             WHERE i.indent_id = ?
             GROUP BY i.id",
            [$indent_id]
        )->getResult();

        $data['invoiceGtotal'] = $this->db->query(
            "SELECT SUM(amount) AS Gtotal, SUM(tamount) AS tamt, SUM(CGST) AS TCGST, SUM(SGST) AS TSGST
             FROM inv_stock_item WHERE indent_id = ?",
            [$indent_id]
        )->getResult();

        $data['content'] = view('storestock/indent_item_list', $data);

        return view('storestock/indent_edit', $data);
    }

    // -------------------------------------------------------------------------
    // Indent Items List (AJAX partial refresh)
    // -------------------------------------------------------------------------

    public function indent_items_list(int $indent_id)
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $data['inv_stock_item'] = $this->db->query(
            "SELECT * FROM inv_stock_item WHERE indent_id = ? ORDER BY id",
            [$indent_id]
        )->getResult();

        $data['invoiceGtotal'] = $this->db->query(
            "SELECT SUM(amount) AS Gtotal, SUM(tamount) AS tamt, SUM(CGST) AS TCGST, SUM(SGST) AS TSGST
             FROM inv_stock_item WHERE indent_id = ?",
            [$indent_id]
        )->getResult();

        $html = view('storestock/indent_item_list', $data);
        return $this->response->setBody($html);
    }

    // -------------------------------------------------------------------------
    // Add / Update / Delete item from indent
    // -------------------------------------------------------------------------

    public function add_item(int $type)
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $insert_id   = 1;
        $error_show  = '';
        $exist       = 0;
        $input_id    = 0;
        $inv_id      = 0;

        $user    = service('auth')->user();
        $user_id = $user ? (int) ($user->id ?? 0) : 0;
        $user_name_info = $this->actorLabel();

        $store_stock_id = (int) $this->request->getPost('l_ssno');

        if ($type == 1) {
            // ---- Add new item ----
            $inv_id          = (int) $this->request->getPost('inv_id');
            $qty             = (float) $this->request->getPost('qty');
            $disc            = (float) $this->request->getPost('disc');
            $product_unit_rate = (float) $this->request->getPost('product_unit_rate');

            $item = $this->db->query(
                "SELECT p.item_name, t.batch_no, t.expiry_date,
                 (t.total_unit - t.total_sale_unit) AS S_qty,
                 t.mrp, t.selling_unit_rate, p.id AS master_product_id,
                 t.id AS purchase_item_id, p.formulation,
                 t.HSNCODE, t.CGST_per, t.SGST_per, t.packing
                 FROM med_store_product_master p
                 JOIN purchase_invoice_item_stock t ON p.id = t.item_code
                 WHERE t.id = ?",
                [$store_stock_id]
            )->getRow();

            if (! $item) {
                $insert_id  = 0;
                $error_show = 'Item not found in stock';
            } else {
                $item_exists = $this->db->query(
                    "SELECT * FROM inv_stock_item WHERE indent_id = ? AND store_stock_id = ?",
                    [$inv_id, $store_stock_id]
                )->getResult();

                $stock_qty     = (float) $item->S_qty;
                $amount_value  = $qty * $product_unit_rate;
                $disc_amount   = $amount_value * $disc / 100;
                $tamount_value = $amount_value - $disc_amount;

                if ($qty > $stock_qty) {
                    $insert_id  = 0;
                    $error_show = 'Stock Qty. is less than Required Qty : Current Qty :' . $stock_qty;
                } elseif ($stock_qty == 0) {
                    $insert_id  = 0;
                    $error_show = 'Stock is Empty';
                }

                if ($insert_id > 0) {
                    if (count($item_exists) > 0) {
                        $exist    = count($item_exists);
                        $input_id = 0;
                        $error_show = 'Please Check Also Same Item List';
                    }

                    $this->db->table('inv_stock_item')->insert([
                        'indent_id'        => $inv_id,
                        'item_code'        => $item->master_product_id,
                        'item_Name'        => $item->item_name,
                        'formulation'      => $item->formulation,
                        'qty'              => $qty,
                        'batch_no'         => $item->batch_no,
                        'expiry'           => $item->expiry_date,
                        'price'            => $product_unit_rate,
                        'price2'           => $product_unit_rate,
                        'mrp'              => $item->mrp,
                        'disc_per'         => $disc,
                        'disc_amount'      => $disc_amount,
                        'amount'           => $amount_value,
                        'tamount'          => $tamount_value,
                        'CGST_per'         => $item->CGST_per,
                        'SGST_per'         => $item->SGST_per,
                        'HSNCODE'          => $item->HSNCODE,
                        'store_stock_id'   => $item->purchase_item_id,
                        'update_by_id'     => $user_id,
                        'update_by_remark' => $user_name_info,
                        'packing'          => $item->packing,
                    ]);

                    $insert_id = $this->db->insertID();
                }
            }

        } elseif ($type == 2) {
            // ---- Update qty ----
            $update_qty = (float) $this->request->getPost('u_qty');
            $item_id    = (int) $this->request->getPost('itemid');

            $inv_item = $this->db->query(
                "SELECT * FROM inv_stock_item WHERE id = ?",
                [$item_id]
            )->getRow();

            if (! $inv_item) {
                $insert_id  = 0;
                $error_show = 'Item not found';
            } else {
                $inv_id         = (int) $inv_item->indent_id;
                $store_stock_id = (int) $inv_item->store_stock_id;
                $disc           = (float) $inv_item->disc_per;
                $old_qty        = (float) $inv_item->qty;
                $diff_qty       = $update_qty - $old_qty;

                if ($diff_qty > 0) {
                    $stock_item = $this->db->query(
                        "SELECT (t.total_unit - t.total_sale_unit) AS S_qty
                         FROM purchase_invoice_item_stock t WHERE t.id = ?",
                        [$store_stock_id]
                    )->getRow();

                    $stock_qty = $stock_item ? (float) $stock_item->S_qty : 0;

                    if ($diff_qty > $stock_qty) {
                        $insert_id  = 0;
                        $error_show = 'Stock Qty. is less than Required Qty : Current Qty :' . $stock_qty;
                    }
                }

                if ($insert_id > 0) {
                    $item_rate    = (float) $inv_item->price;
                    $amount_value = $update_qty * $item_rate;
                    $disc_amount  = $amount_value * $disc / 100;
                    $tamount      = $amount_value - $disc_amount;

                    $this->db->table('inv_stock_item')->where('id', $item_id)->update([
                        'qty'           => $update_qty,
                        'disc_amount'   => $disc_amount,
                        'amount'        => $amount_value,
                        'tamount'       => $tamount,
                        'store_stock_id'=> $store_stock_id,
                        'packing'       => $inv_item->packing,
                        'indent_id'     => $inv_id,
                    ]);

                    $insert_id = $item_id;
                }
            }

        } else {
            // ---- Delete item ----
            $item_id  = (int) $this->request->getPost('itemid');
            $inv_item = $this->db->query(
                "SELECT * FROM inv_stock_item WHERE id = ?",
                [$item_id]
            )->getRowArray();

            if ($inv_item) {
                $inv_id = (int) $inv_item['indent_id'];

                // Archive to delete table
                $del_data             = $inv_item;
                $del_data['del_by']   = $user_name_info;
                unset($del_data['id']);
                $this->db->table('inv_stock_item_delete')->insert($del_data);

                $this->db->table('inv_stock_item')->where('id', $item_id)->delete();
                $insert_id = 1;
            } else {
                $insert_id = 0;
                $error_show = 'Item not found';
            }
        }

        $content = '';

        if ($insert_id > 0) {
            // Call stored procs
            $this->db->query("CALL p_stock_update_purchase_id_store({$inv_id})");
            $this->db->query("CALL p_update_med_GST_store({$inv_id})");

            $data['inv_stock_item'] = $this->db->query(
                "SELECT i.*, IFNULL(DATEDIFF(expiry, CURDATE()), 1000) AS no_day,
                 SUM(p.total_unit - p.total_sale_unit - p.total_return_unit - p.total_lost_unit) / p.packing AS cur_qty
                 FROM inv_stock_item i
                 JOIN purchase_invoice_item_stock p ON i.item_code = p.item_code AND p.remove_item = 0
                 WHERE i.indent_id = ?
                 GROUP BY i.id",
                [$inv_id]
            )->getResult();

            $data['invoiceGtotal'] = $this->db->query(
                "SELECT SUM(amount) AS Gtotal, SUM(tamount) AS tamt,
                 SUM(CGST) AS TCGST, SUM(SGST) AS TSGST, SUM(disc_amount) AS t_dis_amt
                 FROM inv_stock_item WHERE indent_id = ?",
                [$inv_id]
            )->getResult();

            $content = view('storestock/indent_item_list', $data);
        }

        return $this->response->setJSON([
            'exist'               => $exist,
            'insertid'            => $insert_id,
            'content'             => $content,
            'error'               => $error_show,
            'input_id'            => $input_id,
            'csrf_token_value'    => csrf_hash(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Drug autocomplete
    // -------------------------------------------------------------------------

    public function get_drug()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $term = trim((string) $this->request->getGet('term'));
        if ($term === '') {
            return $this->response->setJSON([]);
        }

        $q   = strtolower($term);
        $esc = $this->db->escapeLikeString($q);

        $sql = "SELECT p.item_name, p.formulation, t.batch_no,
                 t.expiry_date AS expiry_date_str, t.expiry_date,
                 (t.total_unit - t.total_sale_unit - t.total_lost_unit - t.total_return_unit) AS c_qty,
                 t.id, t.mrp, t.selling_unit_rate, p.id AS item_code, t.packing
                 FROM med_store_product_master p
                 JOIN purchase_invoice_item_stock t ON p.id = t.item_code
                   AND (t.total_unit - t.total_sale_unit - t.total_lost_unit - t.total_return_unit) > 0
                   AND (expiry_date > DATE_ADD(SYSDATE(), INTERVAL 1 DAY) OR p.exp_date_applicable = 0)
                   AND t.remove_item = 0 AND t.item_return = 0
                 WHERE p.item_name LIKE '{$esc}%'
                 ORDER BY p.item_name, t.id
                 LIMIT 100";

        $rows    = $this->db->query($sql)->getResultArray();
        $row_set = [];

        foreach ($rows as $row) {
            $row_set[] = [
                'label'        => htmlentities($row['item_name'] . ' ' . $row['formulation'])
                                  . ' |B:' . htmlentities($row['batch_no'])
                                  . ' |Pak:' . htmlentities($row['packing'])
                                  . ' |Rs.' . htmlentities($row['mrp'])
                                  . ' |Qty:' . htmlentities($row['c_qty']),
                'value'        => htmlentities($row['item_name']),
                'l_item_code'  => htmlentities($row['item_code']),
                'l_ss_no'      => htmlentities($row['id']),
                'l_Batch'      => htmlentities($row['batch_no']),
                'l_Expiry'     => htmlentities($row['expiry_date_str']),
                'l_mrp'        => htmlentities($row['mrp']),
                'l_unit_rate'  => htmlentities($row['selling_unit_rate']),
                'l_c_qty'      => htmlentities($row['c_qty']),
                'l_packing'    => htmlentities($row['packing']),
            ];
        }

        return $this->response->setJSON($row_set);
    }

    // -------------------------------------------------------------------------
    // Final Invoice
    // -------------------------------------------------------------------------

    public function final_invoice(int $inv_id)
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        // Call stored procedure to recalculate GST
        $this->db->query("CALL p_update_med_GST_store({$inv_id})");

        $data['invoice_stock_master'] = $this->db->query(
            "SELECT *, (discount_amount + item_discount_amount) AS inv_disc_total,
             (CGST_Tamount + SGST_Tamount) AS TGST
             FROM invoice_stock_master WHERE id = ?",
            [$inv_id]
        )->getResult();

        $data['inv_items'] = $this->db->query(
            "SELECT * FROM inv_stock_item WHERE indent_id = ?",
            [$inv_id]
        )->getResult();

        $data['invoiceGtotal'] = $this->db->query(
            "SELECT SUM(amount) AS Gtotal, SUM(tamount) AS tamt,
             SUM(CGST) AS TCGST, SUM(SGST) AS TSGST, SUM(disc_amount) AS t_dis_amt
             FROM inv_stock_item WHERE indent_id = ?",
            [$inv_id]
        )->getResult();

        return view('storestock/final_invoice', $data);
    }

    // -------------------------------------------------------------------------
    // Print single indent (mPDF)
    // -------------------------------------------------------------------------

    public function print_single_indent(int $inv_id)
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $this->db->query("CALL p_update_med_GST_store({$inv_id})");

        $data['inv_items'] = $this->db->query(
            "SELECT i.indent_id, i.id, i.item_Name, i.formulation,
             i.batch_no, DATE_FORMAT(i.expiry, '%m-%y') AS expiry,
             i.price, i.qty, (i.amount) AS amount,
             i.HSNCODE, m.indent_code, m.id AS m_id,
             DATE_FORMAT(m.indent_date, '%d-%m-%Y') AS str_inv_date,
             (i.disc_amount) AS d_amt,
             (i.CGST + i.SGST) AS gst,
             (i.CGST_per + i.SGST_per) AS gst_per, i.sale_return
             FROM inv_stock_item i
             JOIN invoice_stock_master m ON i.indent_id = m.id
             WHERE m.id = ?
             ORDER BY i.sale_return, i.id",
            [$inv_id]
        )->getResult();

        $data['invoice_stock_master'] = $this->db->query(
            "SELECT *, DATE_FORMAT(indent_date,'%d-%m-%Y') AS str_inv_date,
             (discount_amount + item_discount_amount) AS inv_disc_total,
             (CGST_Tamount + SGST_Tamount) AS TGST
             FROM invoice_stock_master WHERE id = ?",
            [$inv_id]
        )->getResult();

        $html = view('storestock/store_print', $data);

        $mpdf = new Mpdf([
            'margin_top'    => 40,
            'margin_bottom' => 12,
            'margin_left'   => 10,
            'margin_right'  => 5,
        ]);
        $mpdf->showWatermarkText = false;
        $mpdf->WriteHTML($html);

        $filename = 'Indent-' . $inv_id . '-' . date('Ymdhis') . '.pdf';
        $mpdf->Output($filename, 'I');
    }

    // -------------------------------------------------------------------------
    // Store Stock Search
    // -------------------------------------------------------------------------

    public function store_stock()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $data['supplier_data']   = $this->db->query("SELECT * FROM stock_supplier ORDER BY name_supplier")->getResult();
        $data['med_formulation'] = $this->db->query("SELECT * FROM med_formulation ORDER BY formulation_length")->getResult();

        return view('storestock/store_stock_search', $data);
    }

    // -------------------------------------------------------------------------
    // Store Stock Result (POST)
    // -------------------------------------------------------------------------

    public function store_stock_result()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $supplier_id  = (int) $this->request->getPost('input_supplier');
        $item_name    = (string) $this->request->getPost('txtsearch');
        $chk_reorder  = (string) $this->request->getPost('chk_reorder');

        $having = '';
        if ($chk_reorder === 'on') {
            $having = ' HAVING SUM(s.total_unit - s.total_lost_unit - s.total_sale_unit - s.total_return_unit) <= p.re_order_qty';
        }

        $where       = ' s.remove_item = 0';
        $whereParams = [];

        if ($supplier_id > 0) {
            $where .= ' AND m.sid = ?';
            $whereParams[] = $supplier_id;
        }

        if ($item_name !== '') {
            $esc    = $this->db->escapeLikeString($item_name);
            $where .= " AND p.item_name LIKE '%{$esc}%'";
        }

        $sql = "SELECT p.id, p.item_name,
                IFNULL(s.item_code, 0) AS item_found,
                IFNULL(s.packing, p.packing) AS packing,
                SUM(s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit) AS C_Unit_Stock_Qty,
                CONCAT(
                    TRUNCATE(SUM(s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit) / s.packing, 0),
                    ':', MOD(SUM(s.total_unit - s.total_sale_unit - s.total_lost_unit - s.total_return_unit), s.packing)
                ) AS C_Pak_Qty,
                SUM(s.total_unit) AS P_Unit_Qty,
                SUM(s.total_unit) / s.packing AS Pak_qty,
                SUM(s.total_sale_unit) AS sale_unit,
                SUM(s.total_sale_unit) / s.packing AS C_Pak_Sale_Qty,
                p.re_order_qty,
                s.total_lost_unit
                FROM med_store_product_master p
                JOIN purchase_invoice_item_stock s ON p.id = s.item_code AND s.remove_item = 0 AND s.item_return = 0
                JOIN purchase_invoice_stock m ON s.purchase_id = m.id AND p.is_continue = 1
                WHERE {$where}
                GROUP BY p.id, s.packing{$having}
                ORDER BY p.item_name";

        $data['stock_list'] = $this->db->query($sql, $whereParams)->getResult();

        $html = view('storestock/stock_search_result', $data);
        return $this->response->setBody($html);
    }

    // -------------------------------------------------------------------------
    // Get product stock detail (batch-wise)
    // -------------------------------------------------------------------------

    public function get_product_stock(int $product_id)
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }

        $supplier_id = (int) $this->request->getPost('input_supplier');
        $date_range  = (string) $this->request->getPost('date_range');
        $product_pak = (float) $this->request->getPost('product_pak');

        $where       = '1 = 1';
        $whereParams = [];

        if ($date_range !== '' && $date_range !== '0') {
            $rangeArray = explode('S', $date_range);
            if (count($rangeArray) === 2) {
                $where       .= ' AND p.date_of_invoice BETWEEN ? AND ?';
                $whereParams[] = $rangeArray[0];
                $whereParams[] = $rangeArray[1];
            }
        }

        if ($supplier_id > 0) {
            $where       .= ' AND m.sid = ?';
            $whereParams[] = $supplier_id;
        }

        $where       .= ' AND i.item_code = ?';
        $whereParams[] = $product_id;

        if ($product_pak > 0) {
            $where       .= ' AND i.packing = ?';
            $whereParams[] = $product_pak;
        }

        $sql = "SELECT i.id, s.name_supplier, s.short_name, i.Item_name,
                 p.Invoice_no, p.date_of_invoice, i.mrp, i.packing,
                 i.batch_no, i.expiry_date, i.purchase_price, i.purchase_unit_rate,
                 i.qty, i.qty_free, i.tqty, i.total_unit, i.total_sale_unit,
                 i.total_return_unit, i.total_lost_unit,
                 (i.total_unit - i.total_sale_unit - i.total_lost_unit - i.total_return_unit) AS cur_unit
                 FROM purchase_invoice_stock p
                 JOIN purchase_invoice_item_stock i ON p.id = i.purchase_id AND i.item_return = 0
                 JOIN stock_supplier s ON p.sid = s.sid
                 WHERE {$where} AND i.remove_item = 0
                 ORDER BY p.id";

        $data['product_purchase_detail'] = $this->db->query($sql, $whereParams)->getResult();

        $html = view('storestock/stock_item_history', $data);
        return $this->response->setBody($html);
    }

    // -------------------------------------------------------------------------
    // Day Report (stub)
    // -------------------------------------------------------------------------

    public function report_2()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }
        return view('storestock/day_report');
    }

    // -------------------------------------------------------------------------
    // Main Store Dashboard
    // -------------------------------------------------------------------------

    public function main_store()
    {
        if ($err = $this->ensureStoreAccess()) {
            return $err;
        }
        return view('storestock/main_store_dashboard');
    }
}
