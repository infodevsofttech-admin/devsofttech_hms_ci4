<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\I18n\Time;
use Exception;

class Diagnosis extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Check if it's an AJAX request
        $isAjax = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return view('welcome_message');
        }

        $user = service('auth')->user();
        
        return view('diagnosis/index', [
            'user' => $user
        ]);
    }

    public function pathology()
    {
        return $this->labPath(5);
    }

    public function biopsy()
    {
        return $this->labPath(30);
    }

    public function mri()
    {
        return $this->labPath(2);
    }

    public function xray()
    {
        return $this->labPath(3);
    }

    public function ultrasound()
    {
        return $this->labPath(1);
    }

    public function ctscan()
    {
        return $this->labPath(4);
    }

    public function labMaster()
    {
        return $this->index();
    }

    public function labPathLegacy($labType)
    {
        return $this->labPath((int) $labType);
    }

    private function labPath(int $labType)
    {
        // Check if it's an AJAX request
        $isAjax = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        if (!$isAjax) {
            return view('welcome_message');
        }

        $user = service('auth')->user();

        $labTypeName = $this->getLabTypeName($labType);

        return view('diagnosis/request_list', [
            'user' => $user,
            'lab_type' => $labType,
            'lab_type_name' => $labTypeName
        ]);
    }

    public function searchLab($labType = null)
    {
        $labType = $labType !== null ? (int) $labType : (int) $this->request->getPost('lab_type');
        $searchText = $this->request->getPost('txtsearch');

        // Sanitize input
        $searchText = preg_replace('/[^A-Za-z0-9_ \-]/', '', $searchText);

        if (trim($searchText) == '') {
            // Get recent 200 records
            $sql = "SELECT m.id as inv_id, m.invoice_code, m.inv_date,
                CONCAT(p.p_fname, '/', IF(p.gender=1,'M','F'), '/', p.p_rname) as inv_name,
                p.dob, p.age, p.age_in_month, p.estimate_dob,
                GROUP_CONCAT(CONCAT_WS(';', i.item_name, i.item_name, i.id, 
                    check_item_request(m.id, i.id)) SEPARATOR '#') as data_array,
                r.daily_sr_no, r.lab_test_no
                FROM invoice_item i
                INNER JOIN invoice_master m ON m.id = i.inv_master_id
                INNER JOIN hc_item_type y ON i.item_type = y.itype_id
                INNER JOIN patient_master p ON p.id = m.attach_id
                LEFT JOIN lab_invoice_request r ON m.id = r.invoice_id
                WHERE m.payment_status = 1 AND y.itype_id = ?
                GROUP BY m.id
                ORDER BY m.id DESC
                LIMIT 200";

            $query = $this->db->query($sql, [$labType]);
        } else {
            // Search with criteria
            $sql = "SELECT m.id as inv_id, m.invoice_code, m.inv_date,
                CONCAT(p.p_fname, '/', IF(p.gender=1,'M','F'), '/', p.p_rname) as inv_name,
                p.dob, p.age, p.age_in_month, p.estimate_dob,
                GROUP_CONCAT(CONCAT_WS(';', i.item_name, i.item_name, i.id, 
                    check_item_request(m.id, i.id)) SEPARATOR '#') as data_array,
                r.daily_sr_no, r.lab_test_no
                FROM invoice_item i
                INNER JOIN invoice_master m ON m.id = i.inv_master_id
                INNER JOIN hc_item_type y ON i.item_type = y.itype_id
                INNER JOIN patient_master p ON p.id = m.attach_id
                LEFT JOIN lab_invoice_request r ON m.id = r.invoice_id
                WHERE m.payment_status = 1 AND y.itype_id = ?
                AND (m.invoice_code LIKE ? OR p.p_fname LIKE ? OR p.p_code LIKE ?)
                GROUP BY m.id
                ORDER BY m.id DESC
                LIMIT 50";

            $searchPattern = '%' . $searchText . '%';
            $query = $this->db->query($sql, [$labType, $searchPattern, $searchPattern, $searchPattern]);
        }

        $results = $query->getResult();
        // Calculate age using PHP helper
        foreach ($results as $row) {
            $row->age = get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '');
        }

        $data = [
            'labreport_preprocess' => $results,
            'lab_type' => $labType
        ];

        // Return different view based on lab type
        if ($labType == 5 || $labType == 30) { // Pathology or Biopsy
            return view('diagnosis/lab_report_tab_path', $data);
        } else {
            return view('diagnosis/lab_report_tab', $data);
        }
    }

    public function searchLabBySrno($labType = null)
    {
        $labType = $labType !== null ? (int) $labType : (int) $this->request->getPost('lab_type');
        $srNo = $this->request->getPost('txtsearch_srno');

        // Sanitize input
        $srNo = preg_replace('/[^A-Za-z0-9_ \-]/', '', $srNo);

        if (trim($srNo) == '') {
            // Get recent 3 days records with serial number
            $sql = "SELECT m.id as inv_id, m.invoice_code, m.inv_date,
                CONCAT(p.p_fname, '/', IF(p.gender=1,'M','F'), '/', p.p_rname) as inv_name,
                p.dob, p.age, p.age_in_month, p.estimate_dob,
                GROUP_CONCAT(CONCAT_WS(';', i.item_name, i.item_name, i.id, 
                    check_item_request(m.id, i.id)) SEPARATOR '#') as data_array,
                r.daily_sr_no, r.lab_test_no
                FROM invoice_item i
                INNER JOIN invoice_master m ON m.id = i.inv_master_id
                INNER JOIN hc_item_type y ON i.item_type = y.itype_id
                INNER JOIN patient_master p ON p.id = m.attach_id
                INNER JOIN lab_invoice_request r ON m.id = r.invoice_id AND r.daily_sr_no > 0
                WHERE m.payment_status = 1 AND y.itype_id = ? 
                AND m.inv_date >= DATE_ADD(CURDATE(), INTERVAL -3 DAY)
                GROUP BY m.id
                ORDER BY m.id DESC
                LIMIT 50";

            $query = $this->db->query($sql, [$labType]);
        } else {
            // Search by specific serial number
            $sql = "SELECT m.id as inv_id, m.invoice_code, m.inv_date,
                CONCAT(p.p_fname, '/', IF(p.gender=1,'M','F'), '/', p.p_rname) as inv_name,
                p.dob, p.age, p.age_in_month, p.estimate_dob,
                GROUP_CONCAT(CONCAT_WS(';', i.item_name, i.item_name, i.id, 
                    check_item_request(m.id, i.id)) SEPARATOR '#') as data_array,
                r.daily_sr_no, r.lab_test_no
                FROM invoice_item i
                INNER JOIN invoice_master m ON m.id = i.inv_master_id
                INNER JOIN hc_item_type y ON i.item_type = y.itype_id
                INNER JOIN patient_master p ON p.id = m.attach_id
                INNER JOIN lab_invoice_request r ON m.id = r.invoice_id AND r.daily_sr_no > 0
                WHERE m.payment_status = 1 AND y.itype_id = ? AND r.daily_sr_no = ?
                GROUP BY m.id
                ORDER BY m.id DESC
                LIMIT 50";

            $query = $this->db->query($sql, [$labType, $srNo]);
        }

        $results = $query->getResult();
        // Calculate age using PHP helper
        foreach ($results as $row) {
            $row->age = get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '');
        }

        $data = [
            'labreport_preprocess' => $results,
            'lab_type' => $labType
        ];

        return view('diagnosis/lab_report_tab', $data);
    }

    public function searchLabByLabno($labType = null)
    {
        $labType = $labType !== null ? (int) $labType : (int) $this->request->getPost('lab_type');
        $labNo = $this->request->getPost('txtsearch_labno');

        // Sanitize input
        $labNo = preg_replace('/[^A-Za-z0-9_ \-]/', '', $labNo);

        if (trim($labNo) == '') {
            // Get recent 3 days records with lab number
            $sql = "SELECT m.id as inv_id, m.invoice_code, m.inv_date,
                CONCAT(p.p_fname, '/', IF(p.gender=1,'M','F'), '/', p.p_rname) as inv_name,
                p.dob, p.age, p.age_in_month, p.estimate_dob,
                GROUP_CONCAT(CONCAT_WS(';', i.item_name, i.item_name, i.id, 
                    check_item_request(m.id, i.id)) SEPARATOR '#') as data_array,
                r.daily_sr_no, r.lab_test_no
                FROM invoice_item i
                INNER JOIN invoice_master m ON m.id = i.inv_master_id
                INNER JOIN hc_item_type y ON i.item_type = y.itype_id
                INNER JOIN patient_master p ON p.id = m.attach_id
                INNER JOIN lab_invoice_request r ON m.id = r.invoice_id AND r.lab_test_no > 0
                WHERE m.payment_status = 1 AND y.itype_id = ? 
                AND m.inv_date >= DATE_ADD(CURDATE(), INTERVAL -3 DAY)
                GROUP BY m.id
                ORDER BY m.id DESC
                LIMIT 50";

            $query = $this->db->query($sql, [$labType]);
        } else {
            // Search by specific lab number
            $sql = "SELECT m.id as inv_id, m.invoice_code, m.inv_date,
                CONCAT(p.p_fname, '/', IF(p.gender=1,'M','F'), '/', p.p_rname) as inv_name,
                p.dob, p.age, p.age_in_month, p.estimate_dob,
                GROUP_CONCAT(CONCAT_WS(';', i.item_name, i.item_name, i.id, 
                    check_item_request(m.id, i.id)) SEPARATOR '#') as data_array,
                r.daily_sr_no, r.lab_test_no
                FROM invoice_item i
                INNER JOIN invoice_master m ON m.id = i.inv_master_id
                INNER JOIN hc_item_type y ON i.item_type = y.itype_id
                INNER JOIN patient_master p ON p.id = m.attach_id
                INNER JOIN lab_invoice_request r ON m.id = r.invoice_id AND r.lab_test_no > 0
                WHERE m.payment_status = 1 AND y.itype_id = ? AND r.lab_test_no = ?
                GROUP BY m.id
                ORDER BY m.id DESC
                LIMIT 50";

            $query = $this->db->query($sql, [$labType, $labNo]);
        }

        $results = $query->getResult();
        // Calculate age using PHP helper
        foreach ($results as $row) {
            $row->age = get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '');
        }

        $data = [
            'labreport_preprocess' => $results,
            'lab_type' => $labType
        ];

        return view('diagnosis/lab_report_tab', $data);
    }

    public function selectLabInvoicePath($invoiceId, $labType)
    {
        // Allow both AJAX and regular GET requests
        $isAjax = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        // Get invoice and patient information
        $sql = "SELECT m.id as inv_id, m.invoice_code, m.inv_date, m.attach_id,
                    p.id, p.p_fname, p.p_relative, p.p_rname, p.gender, p.dob, p.age, p.age_in_month, 
                    p.estimate_dob, p.p_code, p.udai, p.relation_patient_cardholder, p.p_relative as relative_name,
                    p.mphone1 as phone_number, p.email1 as email, p.add1 as address_line1, p.city, p.state
                FROM invoice_master m
                INNER JOIN patient_master p ON p.id = m.attach_id
                WHERE m.id = ? AND m.payment_status = 1";

        $query = $this->db->query($sql, [$invoiceId]);
        $invoice = $query->getRow();

        if (!$invoice) {
            return view('welcome_message');
        }

        // Calculate age
        $invoice->age = get_age_1($invoice->dob ?? null, $invoice->age ?? '', $invoice->age_in_month ?? '', $invoice->estimate_dob ?? '');

        // Get invoice items (tests) for this invoice
        $testSql = "SELECT i.id, i.item_name, i.item_id, m.inv_date
                    FROM invoice_item i
                    INNER JOIN invoice_master m ON m.id = i.inv_master_id
                    WHERE i.inv_master_id = ? AND i.item_type = ?
                    ORDER BY i.item_name";

        $testQuery = $this->db->query($testSql, [$invoiceId, $labType]);
        $tests = $testQuery->getResult();

        // Get invoice items to correlate with tests
        $itemSql = "SELECT i.id, i.item_name, i.item_id 
                    FROM invoice_item i
                    WHERE i.inv_master_id = ? AND i.item_type = ?";
        
        $itemQuery = $this->db->query($itemSql, [$invoiceId, $labType]);
        $items = $itemQuery->getResult();

        // Get lab invoice request for timing information
        $labInvoiceSql = "SELECT * FROM lab_invoice_request WHERE invoice_id = ? AND lab_type = ?";
        $labInvoiceQuery = $this->db->query($labInvoiceSql, [$invoiceId, $labType]);
        $labInvoice = $labInvoiceQuery->getRow();

        $data = [
            'invoice' => $invoice,
            'tests' => $tests ?? [],
            'items' => $items ?? [],
            'lab_invoice' => $labInvoice,
            'lab_type' => $labType,
            'lab_type_name' => $this->getLabTypeName($labType),
            'lab_type_route' => $this->getLabTypeRoute($labType),
        ];

        return view('diagnosis/pathology_detail', $data);
    }

    public function labDateShow($invoiceId, $labType)
    {
        // AJAX endpoint to return lab date/time information
        try {
            log_message('debug', "labDateShow called: invoiceId={$invoiceId}, labType={$labType}");
            
            $isAjax = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

            $sql = "SELECT * FROM lab_invoice_request WHERE invoice_id = ? AND lab_type = ?";
            $query = $this->db->query($sql, [$invoiceId, $labType]);
            $labInvoice = $query->getRow();
            
            log_message('debug', "Lab invoice request: " . ($labInvoice ? "found" : "not found"));

            $data = [
                'lab_invoice_request' => $labInvoice ? [$labInvoice] : [],
                'invoiceId' => $invoiceId,
                'labType' => $labType
            ];

            $view = view('diagnosis/lab_date_show', $data);
            log_message('debug', "labDateShow view rendered, length: " . strlen($view));
            return $view;
        } catch (\Throwable $e) {
            log_message('error', "labDateShow exception: " . $e->getMessage());
            return '<div class="alert alert-danger">Error loading lab timing: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    public function testList($invoiceId, $labType)
    {
        // AJAX endpoint to return test list
        try {
            log_message('debug', "testList called: invoiceId={$invoiceId}, labType={$labType}");
            
            $isAjax = $this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

            // Get test list with all details - matching old code structure
            $sql = "SELECT m.id as inv_id, m.invoice_code, m.inv_date,
                    CONCAT(p.p_fname, '/', IF(p.gender=1,'M','F'), '/', p.p_rname) as inv_name,
                    i.item_name, i.id as test_id, i.item_id,
                    r.lab_repo_id, y.group_desc as item_type_desc, r.status,
                    r.id as req_id, r.charge_item_id, r.print_combine
                    FROM invoice_item i
                    INNER JOIN invoice_master m ON m.id = i.inv_master_id
                    INNER JOIN hc_item_type y ON i.item_type = y.itype_id
                    INNER JOIN patient_master p ON p.id = m.attach_id
                    LEFT JOIN lab_request r ON i.id = r.charge_item_id
                    LEFT JOIN lab_repo l ON i.item_id = l.charge_id
                    WHERE m.payment_status = 1 AND y.itype_id = ? AND m.id = ?
                    ORDER BY i.item_name";

            $query = $this->db->query($sql, [$labType, $invoiceId]);
            $testList = $query->getResult();
            
            log_message('debug', "testList query returned " . count($testList) . " records");

            // Get lab invoice request for timing and lab test number
            $labInvoiceSql = "SELECT * FROM lab_invoice_request WHERE invoice_id = ? AND lab_type = ?";
            $labInvoiceQuery = $this->db->query($labInvoiceSql, [$invoiceId, $labType]);
            $labInvoice = $labInvoiceQuery->getRow();
            
            log_message('debug', "Lab invoice request: " . ($labInvoice ? "found" : "not found"));

            // Check sample collection status for each test (has lab_request record)
            foreach ($testList as $test) {
                $test->check_sample = (!empty($test->req_id)) ? 1 : 0;
            }

            $data = [
                'testlist' => $testList ?? [],
                'lab_invoice_request' => $labInvoice ? [$labInvoice] : [],
                'invoiceId' => $invoiceId,
                'labType' => $labType,
                'lab_type' => $labType,
            ];

            $view = view('diagnosis/lab_invoice_main_test_list', $data);
            log_message('debug', "testList view rendered, length: " . strlen($view));
            return $view;
        } catch (\Throwable $e) {
            log_message('error', "testList exception: " . $e->getMessage() . " - " . $e->getTraceAsString());
            return '<div class="alert alert-danger">Error loading test list: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    private function processSampleCollection(int $testId, int $labType, ?int $invoiceId = null): array
    {
        if ($testId <= 0 || $labType <= 0) {
            return ['status' => 'error', 'message' => 'Invalid parameters'];
        }

        try {
            if (($invoiceId ?? 0) <= 0) {
                $invoiceRow = $this->db->table('invoice_item')
                    ->select('inv_master_id')
                    ->where('id', $testId)
                    ->where('item_type', $labType)
                    ->get()
                    ->getRow();

                if ($invoiceRow) {
                    $invoiceId = (int) ($invoiceRow->inv_master_id ?? 0);
                }
            }

            if (($invoiceId ?? 0) <= 0) {
                return ['status' => 'error', 'message' => 'Invalid invoice ID'];
            }

            $sql = "SELECT m.invoice_code, p.p_fname as inv_name, t.item_name, t.id as test_id,
                    if(l.mstRepoKey is null, 0, l.mstRepoKey) as mstRepoKey, 
                    if(l.mstRepoKey is null, t.item_name, l.Title) as Title, m.inv_date,
                    r.lab_repo_id, y.group_desc as item_type_desc, y.itype_id, 
                    m.insurance_case_id, m.ipd_id, m.id as inv_id, m.attach_id, m.net_amount
                    FROM invoice_item t
                    INNER JOIN invoice_master m ON m.id = t.inv_master_id
                    INNER JOIN hc_item_type y ON t.item_type = y.itype_id
                    INNER JOIN patient_master p ON p.id = m.attach_id
                    LEFT JOIN lab_request r ON t.id = r.charge_item_id
                    LEFT JOIN lab_repo l ON t.item_id = l.charge_id
                    WHERE m.payment_status = 1 AND y.itype_id = ? AND r.id IS NULL AND t.id = ?";

            $query = $this->db->query($sql, [$labType, $testId]);
            $testData = $query->getRow();

            if (! $testData) {
                return ['status' => 'error', 'message' => 'Test not found'];
            }

            $user = service('auth')->user();
            $userName = ($user) ? ($user->first_name . ' ' . $user->last_name) : 'System';

            $labRequestData = [
                'lab_repo_id' => $testData->mstRepoKey ?? 0,
                'charge_id' => $testData->inv_id,
                'lab_type' => $testData->itype_id,
                'charge_item_id' => $testData->test_id,
                'invoice_code' => $testData->invoice_code,
                'patient_name' => $testData->inv_name,
                'patient_id' => $testData->attach_id,
                'ipd_id' => $testData->ipd_id ?? 0,
                'org_id' => $testData->insurance_case_id ?? 0,
                'Request_Date' => $testData->inv_date,
                'report_name' => $testData->Title,
                'insert_by' => $userName,
                'collected_time' => date('Y-m-d H:i:s')
            ];

            $this->db->table('lab_request')->insert($labRequestData);
            $insertId = (int) $this->db->insertID();

            $labInvoiceCheckSql = "SELECT id FROM lab_invoice_request WHERE lab_type = ? AND invoice_id = ?";
            $labInvoiceCheckQuery = $this->db->query($labInvoiceCheckSql, [$labType, $testData->inv_id]);
            $labInvoiceRequest = $labInvoiceCheckQuery->getRow();

            if (! $labInvoiceRequest) {
                $countSql = "SELECT COUNT(*) as no_rec FROM lab_invoice_request WHERE lab_type = ? AND DATE(report_insert) = CURDATE()";
                $countQuery = $this->db->query($countSql, [$labType]);
                $countData = $countQuery->getRow();
                $countRec = ((int) ($countData->no_rec ?? 0)) + 1;

                $labInvoiceData = [
                    'invoice_id' => $testData->inv_id,
                    'invoice_code' => $testData->invoice_code,
                    'lab_type' => $testData->itype_id,
                    'collected_time' => date('Y-m-d H:i:s'),
                    'daily_sr_no' => $countRec
                ];
                $this->db->table('lab_invoice_request')->insert($labInvoiceData);
            } else {
                $this->db->table('lab_invoice_request')
                    ->where('id', $labInvoiceRequest->id)
                    ->update(['collected_time' => date('Y-m-d H:i:s')]);
            }

            return [
                'status' => 'success',
                'message' => 'Sample collection recorded successfully',
                'request_id' => $insertId,
                'invoice_id' => (int) $testData->inv_id,
                'lab_type' => $labType,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    private function getLabTypeName(int $labType): string
    {
        $types = [
            1 => 'Ultrasound',
            2 => 'MRI',
            3 => 'X-Ray',
            4 => 'CT-Scan',
            5 => 'Pathology',
            6 => 'Echo',
            30 => 'Biopsy'
        ];

        return $types[$labType] ?? 'Diagnosis';
    }

    private function getLabTypeRoute(int $labType): string
    {
        $routes = [
            1 => 'ultrasound',
            2 => 'mri',
            3 => 'xray',
            4 => 'ctscan',
            5 => 'pathology',
            6 => 'echo',
            30 => 'biopsy',
        ];

        return $routes[$labType] ?? 'diagnosis';
    }

    private function isRadiologyType(int $labType): bool
    {
        return in_array($labType, [1, 2, 3, 4, 6], true);
    }

    public function testData()
    {
        // Diagnostic endpoint to check for test data
        // Find an invoice with pathology tests (lab_type=5)
        $sql = "SELECT m.id, m.invoice_code, COUNT(i.id) as test_count
                FROM invoice_master m
                INNER JOIN invoice_item i ON m.id = i.inv_master_id
                WHERE m.payment_status = 1 AND i.item_type = 5
                GROUP BY m.id
                LIMIT 10";
        
        $query = $this->db->query($sql);
        $results = $query->getResult();
        
        return $this->response->setJSON([
            'message' => 'Sample pathology invoices with test data',
            'invoices' => $results,
            'instruction' => 'Use an invoice ID from the results above for testing'
        ]);
    }

    public function sampleCollection($testId, $labType)
    {
        $invoiceId = (int) ($this->request->getPost('invoice_id') ?? 0);
        $result = $this->processSampleCollection((int) $testId, (int) $labType, $invoiceId > 0 ? $invoiceId : null);

        if (($result['status'] ?? 'error') === 'success') {
            return $this->response->setJSON($result);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => $result['message'] ?? 'Unknown error',
        ]);
    }

    public function labTab1Process($testId, $labType)
    {
        $result = $this->processSampleCollection((int) $testId, (int) $labType, null);
        if (($result['status'] ?? 'error') === 'success') {
            return (string) ($result['request_id'] ?? 0);
        }

        return '0';
    }

    public function updateLabTiming()
    {
        // AJAX endpoint to update lab collection and report times
        $invoiceId = $this->request->getPost('invoice_id');
        $labType = $this->request->getPost('lab_type');
        $collectedTime = $this->request->getPost('collected_time');
        $reportedTime = $this->request->getPost('reported_time');

        if (!$invoiceId || !$labType) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid parameters']);
        }

        try {
            $updateData = [];
            
            if (!empty($collectedTime)) {
                $updateData['collected_time'] = $collectedTime;
            }
            
            if (!empty($reportedTime)) {
                $updateData['reported_time'] = $reportedTime;
            }

            if (empty($updateData)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'No data to update']);
            }

            $builder = $this->db->table('lab_invoice_request');
            $builder->where('invoice_id', $invoiceId);
            $builder->where('lab_type', $labType);
            $result = $builder->update($updateData);

            if ($result) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Lab timing updated successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No records updated'
                ]);
            }

        } catch (Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function updateLabNo($labReqId)
    {
        // AJAX endpoint to update lab test number
        $labTestNo = $this->request->getPost('lab_test_no');

        if (!$labReqId || $labTestNo === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid parameters']);
        }

        try {
            $builder = $this->db->table('lab_invoice_request');
            $builder->where('id', $labReqId);
            $result = $builder->update(['lab_test_no' => $labTestNo]);

            if ($result || $this->db->affectedRows() > 0) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Lab test number updated successfully'
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No records updated'
                ]);
            }

        } catch (Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function editTestData($labReqId)
    {
        // AJAX endpoint to load old HMS-style detailed test-entry table
        try {
            $labReqId = (int) $labReqId;
            log_message('debug', "editTestData called: labReqId={$labReqId}");

            $masterQuery = $this->db->query('SELECT * FROM lab_request WHERE id = ?', [$labReqId]);
            $labRequestMaster = $masterQuery->getRow();

            if (! $labRequestMaster) {
                return '<div class="alert alert-danger">Test request not found</div>';
            }

            $repoTestsSql = "SELECT r.mstRepoKey, t.mstTestKey
                FROM lab_repo r
                JOIN lab_repotests j ON r.mstRepoKey = j.mstRepoKey
                JOIN lab_tests t ON j.mstTestKey = t.mstTestKey
                WHERE r.mstRepoKey = ?
                ORDER BY j.EOrder";
            $repoTests = $this->db->query($repoTestsSql, [$labRequestMaster->lab_repo_id])->getResult();

            foreach ($repoTests as $repoTest) {
                $existsSql = "SELECT id FROM lab_request_item
                    WHERE lab_request_id = ? AND lab_repo_id = ? AND lab_test_id = ?
                    LIMIT 1";
                $exists = $this->db->query($existsSql, [
                    $labReqId,
                    $repoTest->mstRepoKey,
                    $repoTest->mstTestKey,
                ])->getRow();

                if (! $exists) {
                    $this->db->table('lab_request_item')->insert([
                        'lab_request_id' => $labReqId,
                        'lab_repo_id'    => $repoTest->mstRepoKey,
                        'lab_test_id'    => $repoTest->mstTestKey,
                    ]);
                }
            }

            $itemsSql = "SELECT
                    d.mstTestKey,
                    d.Test,
                    d.TestID,
                    d.Result,
                    d.Formula,
                    d.VRule,
                    d.VMsg,
                    d.Unit,
                    d.FixedNormals,
                    i.lab_test_value,
                    i.lab_test_remark,
                    i.id,
                    s.EOrder,
                    GROUP_CONCAT(CONCAT(o.id, ':', o.option_text, ':', o.option_value) ORDER BY o.sort_id) AS option_value
                FROM lab_request_item i
                JOIN lab_tests d ON i.lab_test_id = d.mstTestKey
                JOIN lab_repotests s ON d.mstTestKey = s.mstTestKey AND i.lab_repo_id = s.mstRepoKey
                LEFT JOIN lab_tests_option o ON d.mstTestKey = o.mstTestKey
                WHERE i.lab_request_id = ?
                GROUP BY d.mstTestKey
                ORDER BY s.EOrder";

            $labRequestItems = $this->db->query($itemsSql, [$labReqId])->getResult();

            return view('diagnosis/test_data_entry_table', [
                'lab_request_master'     => [$labRequestMaster],
                'lab_request_item_entry' => $labRequestItems,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'editTestData exception: ' . $e->getMessage());
            return '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    public function updateTestValue()
    {
        // AJAX endpoint to update individual lab test value in modal table
        $itemId = (int) $this->request->getPost('test_id');
        $testValue = (string) $this->request->getPost('test_value');

        if ($itemId <= 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid test id',
            ]);
        }

        try {
            $this->db->table('lab_request_item')
                ->where('id', $itemId)
                ->update(['lab_test_value' => $testValue]);

            return $this->response->setJSON([
                'status' => 'success',
                'value' => $testValue,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function createReport($labReqId)
    {
        // Old HMS flow equivalent: generate Report_Data, update status, then show final report editor
        $labReqId = (int) $labReqId;

        if ($labReqId <= 0) {
            return '<div class="alert alert-danger">Invalid request id</div>';
        }

        try {
            $labRequest = $this->db->table('lab_request')->where('id', $labReqId)->get()->getRow();
            if (! $labRequest) {
                return '<div class="alert alert-danger">Request not found</div>';
            }

            if ($this->isRadiologyType((int) ($labRequest->lab_type ?? 0))) {
                return $this->createReportXray($labReqId);
            }

            $gender = 1;
            if (! empty($labRequest->patient_id)) {
                $patient = $this->db->table('patient_master')
                    ->where('id', (int) $labRequest->patient_id)
                    ->get()
                    ->getRow();
                if ($patient && isset($patient->gender)) {
                    $gender = (int) $patient->gender;
                }
            }

            $itemSql = "SELECT
                    d.Test,
                    d.TestID,
                    d.Formula,
                    d.FixedNormals,
                    d.isGenderSpecific,
                    d.FixedNormalsWomen,
                    i.lab_test_value
                FROM lab_request_item i
                JOIN lab_tests d ON i.lab_test_id = d.mstTestKey
                JOIN lab_repotests s ON d.mstTestKey = s.mstTestKey AND i.lab_repo_id = s.mstRepoKey
                WHERE i.lab_request_id = ?
                ORDER BY s.EOrder";
            $dataLabTestList = $this->db->query($itemSql, [$labReqId])->getResult();

            $repoFormat = $this->db->table('lab_repo')
                ->select('mstRepoKey, Title, HTMLData')
                ->where('mstRepoKey', (int) $labRequest->lab_repo_id)
                ->get()
                ->getRow();

            $reportTitle = $repoFormat && ! empty($repoFormat->Title)
                ? $repoFormat->Title
                : ($labRequest->report_name ?? 'Lab Report');

            $reportString = $repoFormat->HTMLData ?? '';
            $reportHeader = '<table border="0" cellpadding="1" cellspacing="1" style="width:100%"><tbody><tr><td><h3>' .
                htmlspecialchars((string) $reportTitle, ENT_QUOTES, 'UTF-8') .
                '</h3></td></tr></tbody></table>';
            $reportFooter = $labRequest->Remark ?? '';

            $labTestArray = [];
            foreach ($dataLabTestList as $row) {
                $labTestArray[$row->TestID] = $row->lab_test_value;
            }

            foreach ($dataLabTestList as $row) {
                $fixedNormals = (string) ($row->FixedNormals ?? '');
                $fixedNormalsWomen = (string) ($row->FixedNormalsWomen ?? '');
                $formula = trim((string) ($row->Formula ?? ''));

                if ($formula !== '') {
                    foreach ($labTestArray as $key => $value) {
                        $replaceValue = is_numeric($value) ? $value : '0';
                        $formula = str_replace('{' . $key . '}', (string) $replaceValue, $formula);
                    }
                    $labTestValue = function_exists('cal_exp') ? round((float) cal_exp($formula), 2) : ($row->lab_test_value ?? '');
                } else {
                    $labTestValue = $row->lab_test_value ?? '';
                }

                $normalRange = ((int) ($row->isGenderSpecific ?? 0) === 1 && $gender !== 1)
                    ? $fixedNormalsWomen
                    : $fixedNormals;

                if ($normalRange !== '' && is_numeric($labTestValue)) {
                    $parts = explode('-', $normalRange);
                    if (count($parts) >= 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                        $min = (float) $parts[0];
                        $max = (float) $parts[1];
                        $numValue = (float) $labTestValue;
                        if (! ($numValue >= $min && $numValue <= $max)) {
                            $labTestValue = '<b>' . $labTestValue . '</b>';
                        }
                    }
                }

                $reportString = str_replace('{' . $row->TestID . '}', (string) $labTestValue, $reportString);
            }

            $completeReport = $reportHeader . $reportString . $reportFooter;
            $now = date('Y-m-d H:i:s');

            $this->db->table('lab_request')
                ->where('id', $labReqId)
                ->update([
                    'Report_Data' => $completeReport,
                    'status' => 1,
                    'reported_time' => $now,
                ]);

            $this->db->table('lab_invoice_request')
                ->where('invoice_id', (int) $labRequest->charge_id)
                ->where('lab_type', (int) $labRequest->lab_type)
                ->update([
                    'reported_time' => $now,
                ]);

            return $this->showReportFinal($labReqId);
        } catch (\Throwable $e) {
            return '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    public function showReportFinal($labReqId)
    {
        $labReqId = (int) $labReqId;

        $row = $this->db->table('lab_request')->where('id', $labReqId)->get()->getRow();
        if (! $row) {
            return '<div class="alert alert-danger">Report not found</div>';
        }

        if ($this->isRadiologyType((int) ($row->lab_type ?? 0))) {
            return $this->showReportFinalXray($labReqId);
        }

        return view('diagnosis/lab_final_report_show', [
            'report_format' => [$row],
        ]);
    }

    public function createReportXray($labReqId)
    {
        $labReqId = (int) $labReqId;

        if ($labReqId <= 0) {
            return '<div class="alert alert-danger">Invalid request id</div>';
        }

        try {
            $labRequest = $this->db->table('lab_request')->where('id', $labReqId)->get()->getRow();
            if (! $labRequest) {
                return '<div class="alert alert-danger">Request not found</div>';
            }

            $repoFormat = $this->db->table('lab_repo')
                ->select('Title, HTMLData')
                ->where('mstRepoKey', (int) ($labRequest->lab_repo_id ?? 0))
                ->get()
                ->getRow();

            $reportTitle = $repoFormat && ! empty($repoFormat->Title)
                ? (string) $repoFormat->Title
                : (string) ($labRequest->report_name ?? 'Radiology Report');

            $reportString = (string) ($repoFormat->HTMLData ?? '');
            $reportHeader = '<table border="0" cellpadding="1" cellspacing="1" style="width:100%"><tbody><tr><td><h3>' .
                htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') .
                '</h3></td></tr></tbody></table>';
            $reportFooter = (string) ($labRequest->Remark ?? '');

            $completeReport = $reportHeader . $reportString . $reportFooter;
            $now = date('Y-m-d H:i:s');

            $this->db->table('lab_request')
                ->where('id', $labReqId)
                ->update([
                    'Report_Data' => $completeReport,
                    'status' => 1,
                    'reported_time' => $now,
                ]);

            $this->db->table('lab_invoice_request')
                ->where('invoice_id', (int) ($labRequest->charge_id ?? 0))
                ->where('lab_type', (int) ($labRequest->lab_type ?? 0))
                ->update([
                    'reported_time' => $now,
                ]);

            return $this->showReportFinalXray($labReqId);
        } catch (\Throwable $e) {
            return '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    public function showReportFinalXray($labReqId)
    {
        $labReqId = (int) $labReqId;

        $reportRow = $this->db->table('lab_request')->where('id', $labReqId)->get()->getRow();
        if (! $reportRow) {
            return '<div class="alert alert-danger">Report not found</div>';
        }

        $invoiceItem = $this->db->table('invoice_item')
            ->select('item_id')
            ->where('id', (int) ($reportRow->charge_item_id ?? 0))
            ->get()
            ->getRow();

        $templateBuilder = $this->db->table('radiology_ultrasound_template')
            ->select('id, template_name')
            ->where('Modality', (int) ($reportRow->lab_type ?? 0));

        if (! empty($invoiceItem->item_id)) {
            $templateBuilder->groupStart()
                ->where('charge_id', (int) $invoiceItem->item_id)
                ->orWhere('charge_id', 0)
                ->groupEnd();
        }

        $templates = $templateBuilder
            ->orderBy('template_name', 'ASC')
            ->get()
            ->getResult();

        return view('diagnosis/lab_final_report_show_xray', [
            'report_format' => [$reportRow],
            'radiology_ultrasound_template' => $templates,
        ]);
    }

    public function getTemplateXray($templateId)
    {
        $templateId = (int) $templateId;
        if ($templateId <= 0) {
            return $this->response->setJSON([
                'Findings' => '',
                'Impression' => '',
            ]);
        }

        $row = $this->db->table('radiology_ultrasound_template')
            ->select('Findings, Impression')
            ->where('id', $templateId)
            ->get()
            ->getRow();

        return $this->response->setJSON([
            'Findings' => (string) ($row->Findings ?? ''),
            'Impression' => (string) ($row->Impression ?? ''),
        ]);
    }

    public function finalUpdateXray($labReqId)
    {
        $labReqId = (int) $labReqId;
        $htmlData = (string) $this->request->getPost('HTMLData');
        $impression = (string) $this->request->getPost('report_data_Impression');

        if ($labReqId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request id']);
        }

        try {
            $this->db->table('lab_request')
                ->where('id', $labReqId)
                ->update([
                    'Report_Data' => $htmlData,
                    'report_data_Impression' => $impression,
                ]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Saved']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function confirmReportXray($labReqId)
    {
        return $this->confirmReport($labReqId);
    }

    public function finalUpdate($labReqId)
    {
        $labReqId = (int) $labReqId;
        $htmlData = (string) $this->request->getPost('HTMLData');

        if ($labReqId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request id']);
        }

        try {
            $this->db->table('lab_request')
                ->where('id', $labReqId)
                ->update(['Report_Data' => $htmlData]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Report updated']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function confirmReport($labReqId)
    {
        $labReqId = (int) $labReqId;
        if ($labReqId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request id']);
        }

        try {
            $user = service('auth')->user();
            $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';

            $this->db->table('lab_request')
                ->where('id', $labReqId)
                ->update([
                    'confirm_by' => $userName,
                    'status' => 2,
                ]);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Verified and Ready for Print']);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function printSingleReport($labReqId)
    {
        $labReqId = (int) $labReqId;

        if ($labReqId <= 0) {
            return '<h3>Invalid request id</h3>';
        }

        $row = $this->db->table('lab_request')
            ->where('id', $labReqId)
            ->get()
            ->getRow();

        if (! $row) {
            return '<h3>Report not found</h3>';
        }

        return view('diagnosis/print_single_report', [
            'report' => $row,
        ]);
    }

    public function removeTest($labReqId)
    {
        // Old HMS equivalent of Lab_Admin/report_remove
        $labReqId = (int) $labReqId;
        if ($labReqId <= 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid request id',
            ]);
        }

        try {
            $requestRow = $this->db->table('lab_request')
                ->where('id', $labReqId)
                ->get()
                ->getRowArray();

            if (! $requestRow) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Test request not found',
                ]);
            }

            $user = service('auth')->user();
            $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';
            $requestRow['remove_update_by'] = trim($userName . ' [' . date('d-m-Y H:i') . ']');

            $this->db->transStart();

            if ($this->db->tableExists('lab_request_delete')) {
                $this->db->table('lab_request_delete')->insert($requestRow);
            }

            $this->db->table('lab_request_item')->where('lab_request_id', $labReqId)->delete();
            $this->db->table('lab_request')->where('id', $labReqId)->delete();

            $this->db->transComplete();

            if (! $this->db->transStatus()) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to remove test request',
                ]);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Item Removed',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updateCombineReport()
    {
        $itemId = (int) $this->request->getPost('item_id');
        $checked = (int) $this->request->getPost('checked');

        if ($itemId <= 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid item id',
            ]);
        }

        try {
            $this->db->table('lab_request')
                ->where('id', $itemId)
                ->update(['print_combine' => $checked > 0 ? 1 : 0]);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Value Update',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function reportCompile($invoiceId, $labType)
    {
        $invoiceId = (int) $invoiceId;
        $labType = (int) $labType;

        if ($invoiceId <= 0 || $labType <= 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid parameters',
            ]);
        }

        try {
            $sql = "SELECT l.*, g.RepoGrp
                FROM (lab_request l JOIN lab_repo r ON l.lab_repo_id = r.mstRepoKey)
                LEFT JOIN lab_rgroups g ON r.GrpKey = g.mstRGrpKey
                WHERE l.print_combine = 1 AND l.status = 2 AND l.charge_id = ? AND l.lab_type = ?
                ORDER BY g.sort_order";
            $compiledRows = $this->db->query($sql, [$invoiceId, $labType])->getResult();

            if (empty($compiledRows)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No completed report selected for compile',
                ]);
            }

            $invoice = $this->db->table('invoice_master')->where('id', $invoiceId)->get()->getRow();
            $patient = null;
            if ($invoice && ! empty($invoice->attach_id)) {
                $patient = $this->db->table('patient_master')->where('id', (int) $invoice->attach_id)->get()->getRow();
            }

            $header = '<table border="0" cellpadding="2" cellspacing="1" style="width:100%"><tr>';
            $header .= '<td width="50%" style="vertical-align:top">';
            $header .= '<b>Invoice ID :</b> ' . htmlspecialchars((string) ($invoice->invoice_code ?? ''), ENT_QUOTES, 'UTF-8') . '<br/>';
            $header .= '<b>Patient Name :</b> ' . htmlspecialchars((string) ($patient->p_fname ?? ''), ENT_QUOTES, 'UTF-8') . '<br/>';
            $header .= '<b>UHID :</b> ' . htmlspecialchars((string) ($patient->p_code ?? ''), ENT_QUOTES, 'UTF-8') . '<br/>';
            $header .= '</td>';
            $header .= '<td width="50%" style="vertical-align:top">';
            $header .= '<b>Print Date :</b> ' . date('d-m-Y h:i:s A') . '<br/>';
            $header .= '</td>';
            $header .= '</tr></table><hr/>';

            $rawData = '';
            $groupName = '';
            foreach ($compiledRows as $row) {
                if (($row->RepoGrp ?? '') !== $groupName) {
                    $rawData .= '<h1 style="text-align:center; vertical-align:middle">' .
                        htmlspecialchars((string) ($row->RepoGrp ?? ''), ENT_QUOTES, 'UTF-8') .
                        '</h1>';
                }
                $rawData .= (string) ($row->Report_Data ?? '');
                $groupName = (string) ($row->RepoGrp ?? '');
            }

            $invoiceReq = $this->db->table('lab_invoice_request')
                ->where('invoice_id', $invoiceId)
                ->where('lab_type', $labType)
                ->get()
                ->getRow();

            if (! $invoiceReq) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invoice report record not found',
                ]);
            }

            $user = service('auth')->user();
            $userId = $user->id ?? '';
            $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';
            $compiledBy = $userName . '[' . $userId . '][' . date('d-m-Y h:i:s') . ']';

            $this->db->table('lab_invoice_request')
                ->where('id', (int) $invoiceReq->id)
                ->update([
                    'report_data' => $rawData,
                    'report_header' => $header,
                    'report_compile' => $compiledBy,
                ]);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Data Compile',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function printPdfCreate($invoiceId, $labType, $print = 0, $printOnType = 0)
    {
        // CI4 equivalent of old Lab_Admin/print_pdf_create/{inv}/{lab}/{print}/{print_on_type}
        $invoiceId = (int) $invoiceId;
        $labType = (int) $labType;
        $print = (int) $print;
        $printOnType = (int) $printOnType;

        if ($invoiceId <= 0 || $labType <= 0) {
            return '<h3>Invalid parameters</h3>';
        }

        $invoiceRequest = $this->db->table('lab_invoice_request')
            ->where('invoice_id', $invoiceId)
            ->where('lab_type', $labType)
            ->get()
            ->getRow();

        if (! $invoiceRequest) {
            return '<h3>Error : Record not Exist in lab_invoice_request</h3>';
        }

        $invoice = $this->db->table('invoice_master')->where('id', $invoiceId)->get()->getRow();
        $patient = null;
        if ($invoice && ! empty($invoice->attach_id)) {
            $patient = $this->db->table('patient_master')->where('id', (int) $invoice->attach_id)->get()->getRow();
        }

        $head = $this->db->table('diagnosis_head_name')
            ->where('d_type', $labType)
            ->get()
            ->getRow();

        $itemType = $this->db->table('hc_item_type')->where('itype_id', $labType)->get()->getRow();
        $reportHead = ($itemType->group_desc ?? 'Lab Report')
            . ' / Invoice ID :' . ($invoice->invoice_code ?? '')
            . ' / Person Name :' . ($patient->p_fname ?? '');

        $isPlainPaper = ($printOnType === 1);

        if ($print > 0) {
            return view('diagnosis/print_compiled_report', [
                'invoiceRequest' => $invoiceRequest,
                'invoice' => $invoice,
                'patient' => $patient,
                'head' => $head,
                'reportHead' => $reportHead,
                'isPlainPaper' => $isPlainPaper,
                'labType' => $labType,
                'invoiceId' => $invoiceId,
            ]);
        }

        return view('diagnosis/print_compiled_report', [
            'invoiceRequest' => $invoiceRequest,
            'invoice' => $invoice,
            'patient' => $patient,
            'head' => $head,
            'reportHead' => $reportHead,
            'isPlainPaper' => $isPlainPaper,
            'labType' => $labType,
            'invoiceId' => $invoiceId,
        ]);
    }

    public function reportFileList($invoiceId, $labType, $delete = 0)
    {
        $invoiceId = (int) $invoiceId;
        $labType = (int) $labType;
        $delete = (int) $delete;

        if ($invoiceId <= 0 || $labType <= 0) {
            return view('diagnosis/lab_report_file_list', ['lab_file_list' => []]);
        }

        $files = [];

        if ($this->db->tableExists('file_upload_data')) {
            $files = $this->db->table('file_upload_data')
                ->where('isdelete', $delete)
                ->where('charge_id', $invoiceId)
                ->where('charge_type', $labType)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResult();
        }

        return view('diagnosis/lab_report_file_list', [
            'lab_file_list' => $files,
            'invoice_id' => $invoiceId,
            'lab_type' => $labType,
        ]);
    }

    public function aiExtractLabReportValues()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('file_upload_data')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'file_upload_data table not found']);
        }

        if (! $this->db->tableExists('lab_ai_extraction_batches') || ! $this->db->tableExists('lab_ai_extraction_values')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'AI extraction tables not found. Run migrations first.']);
        }

        $fileId = (int) $this->request->getPost('file_upload_id');
        $invoiceId = (int) $this->request->getPost('invoice_id');
        $labType = (int) $this->request->getPost('lab_type');
        $panelName = trim((string) $this->request->getPost('panel_name'));

        if ($fileId <= 0 || $invoiceId <= 0 || $labType <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Required fields missing']);
        }

        $fileRow = $this->db->table('file_upload_data')
            ->where('id', $fileId)
            ->where('charge_id', $invoiceId)
            ->where('charge_type', $labType)
            ->get(1)
            ->getRowArray();

        if (empty($fileRow)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Report file not found']);
        }

        $fullPath = trim((string) ($fileRow['full_path'] ?? ''));
        $absolutePath = $this->resolveUploadAbsolutePath($fullPath);
        if ($absolutePath === '' || ! is_file($absolutePath)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'File not accessible on server']);
        }

        $ocrText = $this->extractTextFromLabFile($absolutePath);
        if ($ocrText === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'OCR failed. Configure AZURE_DOCINTEL_ENDPOINT/KEY or use readable image.']);
        }

        $parsed = $this->parseLabValuesWithAzureOpenAi($ocrText, $panelName);
        if (empty($parsed['values']) || ! is_array($parsed['values'])) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'AI parsing failed. Check Azure OpenAI settings and deployment.']);
        }

        $modelName = trim((string) ($parsed['model'] ?? ''));
        $rawResponse = (string) json_encode($parsed['raw'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $batchId = $this->insertLabAiBatch(
            $invoiceId,
            $labType,
            $fileId,
            $panelName,
            $modelName,
            $ocrText,
            $rawResponse
        );

        if ($batchId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to store AI extraction batch']);
        }

        $storedCount = 0;
        foreach ($parsed['values'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $testName = trim((string) ($item['test_name'] ?? ''));
            if ($testName === '') {
                continue;
            }

            $ok = $this->db->table('lab_ai_extraction_values')->insert([
                'batch_id' => $batchId,
                'test_name' => $testName,
                'test_value' => trim((string) ($item['value'] ?? '')),
                'unit' => trim((string) ($item['unit'] ?? '')),
                'reference_range' => trim((string) ($item['reference_range'] ?? '')),
                'abnormal_flag' => trim((string) ($item['abnormal_flag'] ?? '')),
                'raw_line' => trim((string) ($item['raw_line'] ?? '')),
                'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
            ]);

            if ($ok) {
                $storedCount++;
            }
        }

        $this->auditClinicalUpdate('lab_ai_extraction_batches', 'created', $batchId, null, [
            'invoice_id' => $invoiceId,
            'lab_type' => $labType,
            'file_upload_id' => $fileId,
            'stored_values' => $storedCount,
        ]);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'AI extracted ' . $storedCount . ' values. Doctor verification pending.',
            'batch_id' => $batchId,
            'stored_values' => $storedCount,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function aiExtractedValues($invoiceId, $labType)
    {
        $invoiceId = (int) $invoiceId;
        $labType = (int) $labType;

        if ($invoiceId <= 0 || $labType <= 0 || ! $this->db->tableExists('lab_ai_extraction_batches')) {
            return view('diagnosis/lab_ai_extracted_values', [
                'batch' => null,
                'values' => [],
            ]);
        }

        $batch = $this->db->table('lab_ai_extraction_batches')
            ->where('invoice_id', $invoiceId)
            ->where('lab_type', $labType)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $values = [];
        if (! empty($batch['id']) && $this->db->tableExists('lab_ai_extraction_values')) {
            $values = $this->db->table('lab_ai_extraction_values')
                ->where('batch_id', (int) $batch['id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('diagnosis/lab_ai_extracted_values', [
            'batch' => $batch,
            'values' => $values,
        ]);
    }

    public function aiVerifyExtractedValues($batchId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $batchId = (int) $batchId;
        if ($batchId <= 0 || ! $this->db->tableExists('lab_ai_extraction_batches')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid batch id']);
        }

        $user = function_exists('auth') ? auth()->user() : null;
        $verifiedBy = (string) ($user->id ?? 'system');

        $updated = $this->db->table('lab_ai_extraction_batches')
            ->where('id', $batchId)
            ->update([
                'doctor_verified' => 1,
                'verified_by' => $verifiedBy,
                'verified_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
            ]);

        if (! $updated) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to verify batch']);
        }

        $this->auditClinicalUpdate('lab_ai_extraction_batches', 'doctor_verified', $batchId, 0, 1, $verifiedBy);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Marked as doctor-verified',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function insertLabAiBatch(
        int $invoiceId,
        int $labType,
        int $fileId,
        string $panelName,
        string $modelName,
        string $ocrText,
        string $rawResponse
    ): int {
        $ok = $this->db->table('lab_ai_extraction_batches')->insert([
            'invoice_id' => $invoiceId,
            'lab_type' => $labType,
            'file_upload_id' => $fileId,
            'panel_name' => $panelName,
            'ai_provider' => 'azure-openai',
            'model_name' => $modelName,
            'ocr_text' => $ocrText,
            'raw_response_json' => $rawResponse,
            'status' => 'completed',
            'doctor_verified' => 0,
            'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
            'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ]);

        if (! $ok) {
            return 0;
        }

        return (int) $this->db->insertID();
    }

    private function resolveUploadAbsolutePath(string $fullPath): string
    {
        $fullPath = trim($fullPath);
        if ($fullPath === '') {
            return '';
        }

        if (is_file($fullPath)) {
            return $fullPath;
        }

        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
        if (is_file($normalized)) {
            return $normalized;
        }

        $fileName = basename($normalized);
        $candidates = [
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $fileName,
            WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $fileName,
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function extractTextFromLabFile(string $absolutePath): string
    {
        $docEndpoint = rtrim($this->readSetting('AZURE_DOCINTEL_ENDPOINT'), '/');
        $docKey = trim($this->readSetting('AZURE_DOCINTEL_KEY'));

        if ($docEndpoint !== '' && $docKey !== '') {
            $text = $this->extractTextWithDocumentIntelligence($absolutePath, $docEndpoint, $docKey);
            if ($text !== '') {
                return $text;
            }
        }

        return $this->extractTextWithAzureVisionLlm($absolutePath);
    }

    private function extractTextWithDocumentIntelligence(string $absolutePath, string $endpoint, string $key): string
    {
        try {
            $client = service('curlrequest', $this->httpOptions());
            $bytes = @file_get_contents($absolutePath);
            if ($bytes === false) {
                return '';
            }

            $analyzeUrl = $endpoint . '/documentintelligence/documentModels/prebuilt-read:analyze?api-version=2024-02-29-preview';
            $submit = $client->post($analyzeUrl, [
                'headers' => [
                    'Content-Type' => 'application/octet-stream',
                    'Ocp-Apim-Subscription-Key' => $key,
                ],
                'body' => $bytes,
            ]);

            if ($submit->getStatusCode() < 200 || $submit->getStatusCode() >= 300) {
                return '';
            }

            $operationLocation = trim((string) $submit->getHeaderLine('Operation-Location'));
            if ($operationLocation === '') {
                return '';
            }

            for ($i = 0; $i < 12; $i++) {
                usleep(800000);
                $poll = $client->get($operationLocation, [
                    'headers' => [
                        'Ocp-Apim-Subscription-Key' => $key,
                    ],
                ]);

                if ($poll->getStatusCode() < 200 || $poll->getStatusCode() >= 300) {
                    continue;
                }

                $decoded = json_decode((string) $poll->getBody(), true);
                if (! is_array($decoded)) {
                    continue;
                }

                $status = strtolower((string) ($decoded['status'] ?? ''));
                if ($status === 'succeeded') {
                    $lines = [];
                    $pages = $decoded['analyzeResult']['pages'] ?? [];
                    foreach ($pages as $page) {
                        $pageLines = $page['lines'] ?? [];
                        foreach ($pageLines as $line) {
                            $content = trim((string) ($line['content'] ?? ''));
                            if ($content !== '') {
                                $lines[] = $content;
                            }
                        }
                    }

                    return trim(implode("\n", $lines));
                }

                if ($status === 'failed') {
                    return '';
                }
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }

    private function extractTextWithAzureVisionLlm(string $absolutePath): string
    {
        $endpoint = rtrim($this->readSetting('AZURE_OPENAI_ENDPOINT'), '/');
        $apiKey = trim($this->readSetting('AZURE_OPENAI_API_KEY'));
        $deployment = trim($this->readSetting('AZURE_OPENAI_DEPLOYMENT'));
        $apiVersion = trim($this->readSetting('AZURE_OPENAI_API_VERSION'));

        if ($endpoint === '' || $apiKey === '' || $deployment === '') {
            return '';
        }
        if ($apiVersion === '') {
            $apiVersion = '2024-10-21';
        }

        $mime = $this->detectMimeType($absolutePath);
        if (! str_starts_with($mime, 'image/')) {
            return '';
        }

        $bytes = @file_get_contents($absolutePath);
        if ($bytes === false) {
            return '';
        }

        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        $url = $endpoint . '/openai/deployments/' . rawurlencode($deployment) . '/chat/completions?api-version=' . rawurlencode($apiVersion);

        try {
            $client = service('curlrequest', $this->httpOptions());
            $response = $client->post($url, [
                'headers' => [
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Read and return all text from this medical lab report image exactly as plain text.'],
                            ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                        ],
                    ]],
                    'temperature' => 0,
                    'max_tokens' => 3000,
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return '';
            }

            $decoded = json_decode((string) $response->getBody(), true);
            return trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseLabValuesWithAzureOpenAi(string $ocrText, string $panelName): array
    {
        $endpoint = rtrim($this->readSetting('AZURE_OPENAI_ENDPOINT'), '/');
        $apiKey = trim($this->readSetting('AZURE_OPENAI_API_KEY'));
        $deployment = trim($this->readSetting('AZURE_OPENAI_DEPLOYMENT'));
        $apiVersion = trim($this->readSetting('AZURE_OPENAI_API_VERSION'));

        if ($endpoint === '' || $apiKey === '' || $deployment === '') {
            return [];
        }
        if ($apiVersion === '') {
            $apiVersion = '2024-10-21';
        }

        $schemaHint = '{"panel_name":"string","values":[{"test_name":"string","value":"string","unit":"string","reference_range":"string","abnormal_flag":"H|L|N|","raw_line":"string"}] }';
        $prompt = "You are an expert lab report parser for Indian hospitals.\n" .
            "Extract only measurable pathology values from OCR text.\n" .
            "Return strict JSON only, no markdown, no comments, with schema: " . $schemaHint . "\n" .
            "If uncertain, keep empty string. panel_name input: " . $panelName . "\n\n" .
            "OCR_TEXT:\n" . mb_substr($ocrText, 0, 14000);

        $url = $endpoint . '/openai/deployments/' . rawurlencode($deployment) . '/chat/completions?api-version=' . rawurlencode($apiVersion);

        try {
            $client = service('curlrequest', $this->httpOptions());
            $response = $client->post($url, [
                'headers' => [
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messages' => [[
                        'role' => 'user',
                        'content' => $prompt,
                    ]],
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 2200,
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return [];
            }

            $decoded = json_decode((string) $response->getBody(), true);
            $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
            if ($content === '') {
                return [];
            }

            $json = json_decode($content, true);
            if (! is_array($json)) {
                return [];
            }

            return [
                'model' => (string) ($decoded['model'] ?? $deployment),
                'values' => is_array($json['values'] ?? null) ? $json['values'] : [],
                'raw' => $decoded,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function httpOptions(): array
    {
        $options = [
            'timeout' => 45,
            'http_errors' => false,
        ];

        if (defined('ENVIRONMENT') && in_array((string) ENVIRONMENT, ['development', 'testing'], true)) {
            $options['verify'] = false;
        }

        return $options;
    }

    private function readSetting(string $name): string
    {
        if (defined($name)) {
            $value = trim((string) constant($name));
            if ($value !== '') {
                return $value;
            }
        }

        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    private function detectMimeType(string $path): string
    {
        $mime = '';
        if (function_exists('mime_content_type')) {
            $mime = (string) @mime_content_type($path);
        }

        if ($mime === '') {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $map = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
            ];
            $mime = $map[$ext] ?? 'application/octet-stream';
        }

        return $mime;
    }

    public function selectLabRadiology($invoiceId, $labType)
    {
        return $this->selectLabInvoicePath((int) $invoiceId, (int) $labType);
    }
}