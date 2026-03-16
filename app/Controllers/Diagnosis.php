<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\I18n\Time;
use Exception;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

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
            'print_templates' => $this->getDiagnosisPrintTemplates($labType),
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
                'print_templates' => $this->getDiagnosisPrintTemplates($labType),
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

    public function openReportEditorPage($labReqId)
    {
        $labReqId = (int) $labReqId;
        $editReason = trim((string) ($this->request->getGet('edit_reason') ?? ''));

        $reportRow = $this->db->table('lab_request')->where('id', $labReqId)->get()->getRow();
        if (! $reportRow) {
            return '<div class="alert alert-danger">Report not found</div>';
        }

        $patientRow = $this->db->table('patient_master')
            ->select('gender, age, age_in, age_in_month, dob')
            ->where('id', (int) ($reportRow->patient_id ?? 0))
            ->get()
            ->getRow();

        $genderRaw = (int) ($patientRow->gender ?? 0);
        $reportRow->gender_text = $genderRaw === 1 ? 'Male' : ($genderRaw === 2 ? 'Female' : '-');

        $ageVal = (int) ($patientRow->age ?? 0);
        $ageIn = trim((string) ($patientRow->age_in ?? ''));
        $ageInMonth = (int) ($patientRow->age_in_month ?? 0);

        if ($ageVal > 0) {
            $ageUnit = $ageIn !== '' ? $ageIn : 'Years';
            $reportRow->age_text = $ageVal . ' ' . $ageUnit;
        } elseif ($ageInMonth > 0) {
            $reportRow->age_text = $ageInMonth . ' Months';
        } else {
            $dob = (string) ($patientRow->dob ?? '');
            if ($dob !== '' && $dob !== '0000-00-00') {
                try {
                    $dobDate = new \DateTime($dob);
                    $today = new \DateTime('today');
                    $diff = $dobDate->diff($today);
                    $reportRow->age_text = $diff->y > 0 ? ($diff->y . ' Years') : ($diff->m . ' Months');
                } catch (\Throwable $e) {
                    $reportRow->age_text = '-';
                }
            } else {
                $reportRow->age_text = '-';
            }
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

        return view('diagnosis/lab_final_report_editor', [
            'report_format' => [$reportRow],
            'radiology_ultrasound_template' => $templates,
            'edit_reason' => $editReason,
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
        $htmlData = (string) ($this->request->getPost('HTMLData') ?? $this->request->getPost('report_data') ?? '');
        $impression = (string) ($this->request->getPost('report_data_Impression') ?? $this->request->getPost('report_data_impression') ?? '');
        $editReason = trim((string) ($this->request->getPost('edit_reason') ?? ''));

        if ($labReqId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request id']);
        }

        try {
            $before = $this->db->table('lab_request')
                ->select('id, status, report_edit_req_no, Report_Data, report_data_Impression')
                ->where('id', $labReqId)
                ->get(1)
                ->getRowArray() ?? [];

            if ((int) ($before['status'] ?? 0) === 2 && $editReason === '') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Edit reason is required for verified report changes (NABH audit).',
                ]);
            }

            $this->db->table('lab_request')
                ->where('id', $labReqId)
                ->update([
                    'Report_Data' => $htmlData,
                    'report_data_Impression' => $impression,
                ]);

            $fields = $this->db->getFieldNames('lab_request');
            if (in_array('report_edit_req_no', $fields, true) && (int) ($before['status'] ?? 0) === 2) {
                $nextEditNo = (int) ($before['report_edit_req_no'] ?? 0) + 1;
                $this->db->table('lab_request')
                    ->where('id', $labReqId)
                    ->update(['report_edit_req_no' => $nextEditNo]);
            }

            if ($this->db->tableExists('lab_log')) {
                $user = service('auth')->user();
                $userId = (int) ($user->id ?? 0);
                $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';

                $beforeHash = md5((string) ($before['Report_Data'] ?? '') . '|' . (string) ($before['report_data_Impression'] ?? ''));
                $afterHash = md5($htmlData . '|' . $impression);
                $reasonLog = $editReason !== '' ? $editReason : 'NA';

                $this->db->table('lab_log')->insert([
                    'lab_repo_id' => $labReqId,
                    'log_by_id' => $userId,
                    'log_by' => $userName,
                    'log_type_id' => 0,
                    'log_type' => 'Report Edit',
                    'log_Faults_id' => 0,
                    'log_Faults' => 'Radiology',
                    'comments' => 'Editor save [reason:' . substr($reasonLog, 0, 350) . '] [before:' . substr($beforeHash, 0, 8) . ' after:' . substr($afterHash, 0, 8) . ']',
                ]);
            }

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
        // Accept both legacy and current POST keys for backward compatibility
        $htmlData = (string) ($this->request->getPost('HTMLData') ?? $this->request->getPost('report_data') ?? '');
        $impression = (string) ($this->request->getPost('report_data_Impression') ?? $this->request->getPost('report_data_impression') ?? '');
        $editReason = trim((string) ($this->request->getPost('edit_reason') ?? ''));

        if ($labReqId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request id']);
        }

        try {
            $before = $this->db->table('lab_request')
                ->select('id, status, report_edit_req_no, Report_Data, report_data_Impression')
                ->where('id', $labReqId)
                ->get(1)
                ->getRowArray() ?? [];

            // NABH audit requirement: edit reason required for verified reports (status = 2)
            if ((int) ($before['status'] ?? 0) === 2 && $editReason === '') {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Edit reason is required for verified report changes (NABH audit).',
                ]);
            }

            $this->db->table('lab_request')
                ->where('id', $labReqId)
                ->update([
                    'Report_Data' => $htmlData,
                    'report_data_Impression' => $impression,
                ]);

            // Track edit version for verified reports
            $fields = $this->db->getFieldNames('lab_request');
            if (in_array('report_edit_req_no', $fields, true) && (int) ($before['status'] ?? 0) === 2) {
                $nextEditNo = (int) ($before['report_edit_req_no'] ?? 0) + 1;
                $this->db->table('lab_request')
                    ->where('id', $labReqId)
                    ->update(['report_edit_req_no' => $nextEditNo]);
            }

            // NABH compliance: log all changes to verified reports
            if ($this->db->tableExists('lab_log')) {
                $user = service('auth')->user();
                $userId = (int) ($user->id ?? 0);
                $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';

                $beforeHash = md5((string) ($before['Report_Data'] ?? '') . '|' . (string) ($before['report_data_Impression'] ?? ''));
                $afterHash = md5($htmlData . '|' . $impression);
                $reasonLog = $editReason !== '' ? $editReason : 'NA';

                $this->db->table('lab_log')->insert([
                    'lab_repo_id' => $labReqId,
                    'log_by_id' => $userId,
                    'log_by' => $userName,
                    'log_type_id' => 0,
                    'log_type' => 'Report Edit',
                    'log_Faults_id' => 0,
                    'log_Faults' => 'Pathology',
                    'comments' => 'Editor save [reason:' . substr($reasonLog, 0, 350) . '] [before:' . substr($beforeHash, 0, 8) . ' after:' . substr($afterHash, 0, 8) . ']',
                ]);
            }

            return $this->response->setJSON(['status' => 'success', 'message' => 'Saved']);
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
        $templateId = (int) ($this->request->getGet('template_id') ?? 0);

           set_time_limit(300);

        if ($labReqId <= 0) {
            return '<h3>Invalid request id</h3>';
        }

        $sql = "SELECT l.*, m.invoice_code, m.attach_id,
                    p.p_fname, p.p_rname, p.p_relative, p.p_code, p.gender, p.dob, p.age, p.age_in, p.age_in_month, p.estimate_dob,
                    r.Title as repo_title, g.RepoGrp
                FROM lab_request l
                LEFT JOIN invoice_master m ON m.id = l.charge_id
                LEFT JOIN patient_master p ON p.id = m.attach_id
                LEFT JOIN lab_repo r ON r.mstRepoKey = l.lab_repo_id
                LEFT JOIN lab_rgroups g ON g.mstRGrpKey = r.GrpKey
                WHERE l.id = ?";

        $row = $this->db->query($sql, [$labReqId])->getRow();

        if (! $row) {
            return '<h3>Report not found</h3>';
        }

        $reportData = trim((string) ($row->Report_Data ?? ''));
        $impression = trim((string) ($row->report_data_Impression ?? ''));

        $reportHtml = '';
        if ($reportData !== '') {
            $reportHtml .= $reportData;
        }

        if ($impression !== '') {
            $reportHtml .= '<div style="margin-top:10px;"><b>Impression :</b><br/>' . nl2br($impression) . '</div>';
        }

        if ($reportHtml === '') {
            $reportHtml = $this->resolveFallbackReportHtml((int) ($row->lab_repo_id ?? 0), (int) ($row->charge_item_id ?? 0), (int) ($row->lab_type ?? 0));
        }

        if ($reportHtml === '') {
            $reportHtml = '<p>No report data available for this request. Please use Edit and save report content.</p>';
        }

        $genderValue = (int) ($row->gender ?? 0);
        $genderText = $genderValue === 1 ? 'Male' : ($genderValue === 2 ? 'Female' : '-');

        $ageText = '-';
        if (function_exists('get_age_1')) {
            $ageText = (string) get_age_1(
                $row->dob ?? null,
                $row->age ?? '',
                $row->age_in_month ?? '',
                $row->estimate_dob ?? ''
            );
        } elseif (! empty($row->age)) {
            $ageText = (string) $row->age;
        }

        $printSetting = $this->getDiagnosisTemplateSetting((int) ($row->lab_type ?? 0), $templateId);
        $isPlainPaper = false;

        // Compute display values here so they are available for token substitution.
        $patientName   = trim((string) ($row->p_fname ?? ''));
        $relativeName  = trim((string) ($row->p_relative ?? ''));
        $relativeText  = trim((string) ($row->p_rname ?? ''));
        $repoTitle     = trim((string) ($row->report_name ?? $row->repo_title ?? $row->RepoGrp ?? 'Report'));
        $headRow       = $this->db->table('diagnosis_head_name')
            ->where('d_type', (int) ($row->lab_type ?? 0))
            ->get(1)
            ->getRow();

        $tokens = $this->buildPdfTokens([
            'invoice_code'   => $row->invoice_code ?? '',
            'patient_name'   => $patientName,
            'relative_type'  => $relativeName,
            'relative_name'  => $relativeText,
            'age'            => $ageText,
            'gender'         => $genderText,
            'uhid'           => $row->p_code ?? '',
            'collected_time' => $row->collected_time ?? '',
            'reported_time'  => $row->reported_time ?? '',
            'report_title'   => $repoTitle,
            'doctor_name'    => $headRow->doc_name ?? '',
            'doctor_education' => $headRow->doc_edu ?? '',
            'technician_name' => $headRow->tech_name ?? '',
            'signature_image_url' => (string) ($printSetting['signature_image'] ?? ''),
        ]);

        // Render the optional patient info template (tokens resolved).
        $rawPatientInfoHtml = trim((string) ($printSetting['patient_info_html'] ?? ''));
           if ($rawPatientInfoHtml !== '') {
               $patientInfoHtml = $this->applyPdfTokens($rawPatientInfoHtml, $tokens);
           } elseif (($printSetting['id'] ?? 0) <= 0) {
               $patientInfoHtml = $this->buildDefaultPatientInfoHtml([
                   'invoice_code'   => $row->invoice_code ?? '',
                   'patient_name'   => $patientName,
                   'relative_name'  => $relativeName,
                   'relative_text'  => $relativeText,
                   'age_text'       => $ageText,
                   'gender_text'    => $genderText,
                   'uhid'           => $row->p_code ?? '',
                   'collected_time' => $row->collected_time ?? '',
                   'reported_time'  => $row->reported_time ?? '',
               ]);
           } else {
               $patientInfoHtml = '';
           }

        $reportHtml = trim((string) ($printSetting['content_prefix_html'] ?? '')) . $reportHtml . trim((string) ($printSetting['content_suffix_html'] ?? ''));

        $pdfHtml = view('diagnosis/pdf_single_report', [
            'report'         => $row,
            'reportHtml'     => $reportHtml,
            'isPlainPaper'   => $isPlainPaper,
            'ageText'        => $ageText,
            'genderText'     => $genderText,
            'printSetting'   => $printSetting,
            'patientName'    => $patientName,
            'relativeName'   => $relativeName,
            'relativeText'   => $relativeText,
            'repoTitle'      => $repoTitle,
            'patientInfoHtml' => $patientInfoHtml,
        ]);

        $rawHeaderHtml = trim((string) ($printSetting['header_html'] ?? ''));
        $rawFirstPageHeaderHtml = trim((string) ($printSetting['first_page_header_html'] ?? ''));
        $rawFooterHtml = trim((string) ($printSetting['footer_html'] ?? ''));
        $rawLastPageFooterHtml = trim((string) ($printSetting['last_page_footer_html'] ?? ''));

        $autoHeaderHtml = $this->buildAutoMpdfHeaderBlock(
            $rawHeaderHtml,
            $rawFirstPageHeaderHtml,
            'diag_auto_header_single'
        );
        $autoFooterHtml = $this->buildAutoMpdfFooterBlock(
            $rawFooterHtml,
            $rawLastPageFooterHtml,
            'diag_auto_footer_single'
        );

        $prefixHtml = $this->applyPdfTokens(trim((string) ($printSetting['mpdf_prefix_html'] ?? ''))
            . $autoHeaderHtml
            . $autoFooterHtml, $tokens);
        $suffixHtml = $this->applyPdfTokens(trim((string) ($printSetting['mpdf_suffix_html'] ?? '')), $tokens);

        $marginTop = $this->cmToMm($printSetting['page_margin_top_cm'] ?? 1.2, 1.2);
        $marginBottom = $this->cmToMm($printSetting['page_margin_bottom_cm'] ?? 1.2, 1.2);
        $marginLeft = $this->cmToMm($printSetting['page_margin_left_cm'] ?? 1.0, 1.0);
        $marginRight = $this->cmToMm($printSetting['page_margin_right_cm'] ?? 1.0, 1.0);
        $marginHeader = $this->cmToMm($printSetting['margin_header_cm'] ?? 0.5, 0.5);
        $marginFooter = $this->cmToMm($printSetting['margin_footer_cm'] ?? 1.5, 1.5);

        $mpdfTempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
        if (! is_dir($mpdfTempDir)) {
            mkdir($mpdfTempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'format'                => (string) ($printSetting['page_size'] ?? 'A4'),
            'margin_top'            => $marginTop,
            'margin_bottom'         => $marginBottom,
            'margin_left'           => $marginLeft,
            'margin_right'          => $marginRight,
            'margin_header'         => $marginHeader,
            'margin_footer'         => $marginFooter,
            'tempDir'               => $mpdfTempDir,
            'autoScriptToLang'      => false,
            'autoLanguageDetection' => false,
            'autoArabic'            => false,
            'autoVietnamese'        => false,
        ]);

        // For plain HTML (without explicit mPDF tags), set header/footer via API.
        $headerHasTags = preg_match('/<\s*(htmlpageheader|sethtmlpageheader)\b/i', $rawHeaderHtml . $rawFirstPageHeaderHtml) === 1;
        $footerHasTags = preg_match('/<\s*(htmlpagefooter|sethtmlpagefooter)\b/i', $rawFooterHtml . $rawLastPageFooterHtml) === 1;

        if (! $headerHasTags) {
            $plainHeader = $this->applyPdfTokens($rawHeaderHtml . $rawFirstPageHeaderHtml, $tokens);
            if ($plainHeader !== '') {
                $mpdf->SetHTMLHeader($plainHeader, 'O');
                $mpdf->SetHTMLHeader($plainHeader, 'E');
            }
        }

        if (! $footerHasTags) {
            $plainFooter = $this->applyPdfTokens($rawFooterHtml, $tokens);
            if ($plainFooter === '') {
                $plainFooter = $this->applyPdfTokens($rawLastPageFooterHtml, $tokens);
            }

            if ($plainFooter !== '') {
                $mpdf->SetHTMLFooter($plainFooter, 'O');
                $mpdf->SetHTMLFooter($plainFooter, 'E');
            }
        }

        $backgroundImage = trim((string) ($printSetting['page_background_image'] ?? ''));
        if ($backgroundImage !== '') {
            $absolutePath = FCPATH . ltrim(str_replace('\\', '/', $backgroundImage), '/');
            if (is_file($absolutePath)) {
                $mpdf->SetWatermarkImage($absolutePath, 1, [210, 297], 'F');
                $mpdf->showWatermarkImage = true;
            }
        }

        $watermarkType = (string) ($printSetting['watermark_type'] ?? 'none');
        $watermarkAlpha = (float) ($printSetting['watermark_alpha'] ?? 0.12);
        if ($watermarkType === 'text') {
            $text = trim((string) ($printSetting['watermark_text'] ?? ''));
            if ($text !== '') {
                $mpdf->SetWatermarkText($text, $watermarkAlpha);
                $mpdf->showWatermarkText = true;
            }
        } elseif ($watermarkType === 'image') {
            $wmImage = trim((string) ($printSetting['watermark_image'] ?? ''));
            if ($wmImage !== '') {
                $absolutePath = FCPATH . ltrim(str_replace('\\', '/', $wmImage), '/');
                if (is_file($absolutePath)) {
                    $mpdf->SetWatermarkImage($absolutePath, $watermarkAlpha);
                    $mpdf->showWatermarkImage = true;
                }
            }
        }

        if ($prefixHtml !== '') {
            $mpdf->WriteHTML($prefixHtml, HTMLParserMode::HTML_HEADER_BUFFER);
        }

        $mpdf->WriteHTML($pdfHtml);

        if ($suffixHtml !== '') {
            $mpdf->WriteHTML($suffixHtml, HTMLParserMode::HTML_HEADER_BUFFER, false, false);
        }

        $invoiceCode = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) ($row->invoice_code ?? 'invoice'));
        $fileName = 'Report_' . $invoiceCode . '_' . $labReqId . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($mpdf->Output($fileName, 'S'));
    }

    private function getDiagnosisPrintTemplates(int $labType): array
    {
        if ($labType <= 0 || ! $this->db->tableExists('diagnosis_print_templates')) {
            return [];
        }

        return $this->db->table('diagnosis_print_templates')
            ->select('id, template_name, is_default')
            ->where('modality', $labType)
            ->where('status', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('template_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getDiagnosisTemplateSetting(int $labType, int $templateId = 0): array
    {
        $legacy = $this->getDiagnosisPrintSetting($labType);

        $defaults = array_merge($legacy, [
            'page_size' => 'A4',
            'page_margin_top_cm' => 6.1,
               'id' => 0,
            'page_margin_bottom_cm' => 2.5,
            'page_margin_left_cm' => 0.7,
            'page_margin_right_cm' => 0.7,
            'margin_header_cm' => 0.5,
            'margin_footer_cm' => 1.5,
            'page_background_image' => '',
            'watermark_type' => 'none',
            'watermark_text' => '',
            'watermark_image' => '',
            'signature_image' => '',
            'watermark_alpha' => 0.12,
            'header_html' => '',
            'first_page_header_html' => '',
            'content_prefix_html' => '',
            'content_suffix_html' => '',
            'footer_html' => '',
            'last_page_footer_html' => '',
        ]);

        if ($labType <= 0 || ! $this->db->tableExists('diagnosis_print_templates')) {
            return $defaults;
        }

        $builder = $this->db->table('diagnosis_print_templates')
            ->where('modality', $labType)
            ->where('status', 1);

        if ($templateId > 0) {
            $builder->where('id', $templateId);
        } else {
            $builder->orderBy('is_default', 'DESC')->orderBy('id', 'ASC');
        }

        $row = $builder->get(1)->getRowArray();
        if (! is_array($row)) {
            return $defaults;
        }

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                if (is_float($value)) {
                    $defaults[$key] = (float) $row[$key];
                } else {
                    $defaults[$key] = (string) $row[$key];
                }
            }
        }

        $pageSize = strtoupper(trim((string) ($defaults['page_size'] ?? 'A4')));

           $defaults['id'] = (int) ($row['id'] ?? 0);
        if (! in_array($pageSize, ['A4', 'A4-L', 'LETTER', 'LEGAL'], true)) {
            $pageSize = 'A4';
        }
        $defaults['page_size'] = $pageSize;

        return $defaults;
    }

    private function getDiagnosisPrintSetting(int $labType): array
    {
        $defaults = [
            'letter_margin_top' => 12.0,
            'letter_margin_left' => 10.0,
            'letter_margin_right' => 10.0,
            'letter_margin_bottom' => 12.0,
            'plain_header_html' => '',
            'plain_background_image' => '',
            'mpdf_prefix_html' => '',
            'mpdf_suffix_html' => '',
            'patient_info_html' => '',
        ];

        if ($labType <= 0 || ! $this->db->tableExists('diagnosis_head_name')) {
            return $defaults;
        }

        $fields = $this->db->getFieldNames('diagnosis_head_name');
        $select = ['d_type'];
        foreach (array_keys($defaults) as $field) {
            if (in_array($field, $fields, true)) {
                $select[] = $field;
            }
        }

        $row = $this->db->table('diagnosis_head_name')
            ->select(implode(',', $select))
            ->where('d_type', $labType)
            ->get(1)
            ->getRowArray();

        if (! is_array($row)) {
            return $defaults;
        }

        foreach ($defaults as $key => $defaultValue) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                $defaults[$key] = is_float($defaultValue) ? (float) $row[$key] : (string) $row[$key];
            }
        }

        return $defaults;
    }

    /**
     * Build an associative array of {{token}} => value pairs for PDF template substitution.
     * All string values are HTML-escaped so they are safe to embed inside HTML attributes and text.
     */
    private function buildPdfTokens(array $data): array
    {
        $e = static function (string $v): string {
            return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        $age    = (string) ($data['age'] ?? '-');
        $gender = (string) ($data['gender'] ?? '-');
        $hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
        $hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
        $hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
        $hospitalLogoName = defined('H_logo') ? trim((string) constant('H_logo')) : '';
        $hospitalLogoUrl = $this->resolvePdfAssetPath([
            $hospitalLogoName !== '' ? 'assets/images/' . ltrim($hospitalLogoName, '/\\') : '',
            'assets/img/logo.png',
            'assets/images/no_image.svg',
        ]);
        $signatureImageUrl = $this->resolvePdfAssetPath([
            (string) ($data['signature_image_url'] ?? ''),
            'assets/images/drPreetiSingh.jpg',
        ]);

        return [
            '{{invoice_code}}'   => $e((string) ($data['invoice_code'] ?? '')),
            '{{patient_name}}'   => $e((string) ($data['patient_name'] ?? '')),
            '{{relative_type}}'  => $e((string) ($data['relative_type'] ?? '')),
            '{{relative_name}}'  => $e((string) ($data['relative_name'] ?? '')),
            '{{relative}}'       => $e(trim((string) ($data['relative_type'] ?? '') . ' ' . (string) ($data['relative_name'] ?? ''))),
            '{{age}}'            => $e($age),
            '{{gender}}'         => $e($gender),
            '{{age_sex}}'        => $e($age . ' / ' . $gender),
            '{{uhid}}'           => $e((string) ($data['uhid'] ?? '')),
            '{{collected_time}}' => $e((string) ($data['collected_time'] ?? '')),
            '{{reported_time}}'  => $e((string) ($data['reported_time'] ?? '')),
            '{{printed_time}}'   => date('d-m-Y h:i A'),
            '{{report_title}}'   => $e((string) ($data['report_title'] ?? '')),
            '{{doctor_name}}'    => $e((string) ($data['doctor_name'] ?? '')),
            '{{doctor_education}}' => $e((string) ($data['doctor_education'] ?? '')),
            '{{technician_name}}' => $e((string) ($data['technician_name'] ?? '')),
            '{{hospital_name}}'  => $e($hospitalName),
            '{{hospital_address_1}}' => $e($hospitalAddress1),
            '{{hospital_phone}}' => $e($hospitalPhone),
            '{{hospital_logo_name}}' => $e($hospitalLogoName),
            '{{hospital_logo_url}}' => $e($hospitalLogoUrl),
            '{{hospital_logo_path}}' => $e($hospitalLogoUrl),
            '{{signature_image_url}}' => $e($signatureImageUrl),
            '{{signature_image_path}}' => $e($signatureImageUrl),
        ];
    }

    private function resolvePdfAssetPath(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate === '') {
                continue;
            }

            if (preg_match('/^https?:\/\//i', $candidate) === 1 || str_starts_with($candidate, 'data:')) {
                continue;
            }

            if (preg_match('/^[A-Za-z]:[\\\\\/]/', $candidate) === 1) {
                $absolutePath = $candidate;
            } else {
                $relativePath = ltrim(str_replace('\\', '/', $candidate), '/');
                $absolutePath = str_starts_with($relativePath, 'public/')
                    ? ROOTPATH . $relativePath
                    : FCPATH . $relativePath;
            }

            $absolutePath = str_replace('\\', '/', $absolutePath);

            if (is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return '';
    }

    /**
     * Replace all {{token}} placeholders in $html using the token map returned by buildPdfTokens().
     */
    private function applyPdfTokens(string $html, array $tokens): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $html);
    }

    private function buildDefaultPatientInfoHtml(array $data): string
    {
        $e = static function (string $v): string {
            return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $ageSex   = $e((string) ($data['age_text'] ?? '-')) . ' / ' . $e((string) ($data['gender_text'] ?? '-'));
        $relative = $e(trim((string) ($data['relative_name'] ?? '') . ' ' . (string) ($data['relative_text'] ?? '')));

        return '<table width="100%" style="border-collapse:collapse;margin-bottom:8px;font-size:11px;">'
            . '<tr>'
            . '<td width="50%" style="vertical-align:top;">'
            . '<b>Invoice ID:</b> ' . $e((string) ($data['invoice_code'] ?? '')) . '<br>'
            . '<b>Patient:</b> ' . $e((string) ($data['patient_name'] ?? '')) . '<br>'
            . '<b>Relative:</b> ' . $relative . '<br>'
            . '<b>Age/Sex:</b> ' . $ageSex
            . '</td>'
            . '<td width="50%" style="vertical-align:top;text-align:right;">'
            . '<b>UHID:</b> ' . $e((string) ($data['uhid'] ?? '')) . '<br>'
            . '<b>Collected:</b> ' . $e((string) ($data['collected_time'] ?? '')) . '<br>'
            . '<b>Reported:</b> ' . $e((string) ($data['reported_time'] ?? '')) . '<br>'
            . '<b>Printed:</b> ' . $e(date('d-m-Y h:i A'))
            . '</td>'
            . '</tr></table>';
    }

    private function buildAutoMpdfHeaderBlock(string $headerHtml, string $firstPageHeaderHtml, string $name): string
    {
        $headerHtml = trim($headerHtml);
        $firstPageHeaderHtml = trim($firstPageHeaderHtml);
        $combined = $headerHtml . $firstPageHeaderHtml;

        if ($combined === '') {
            return '';
        }

        if (preg_match('/<\s*(htmlpageheader|sethtmlpageheader)\b/i', $combined) === 1) {
            return $combined;
        }

        return '';
    }

        private function buildAutoMpdfFooterBlock(string $footerHtml, string $lastPageFooterHtml, string $name): string
    {
        $footerHtml = trim($footerHtml);
        $lastPageFooterHtml = trim($lastPageFooterHtml);
        $combined = $footerHtml . $lastPageFooterHtml;

        if ($combined === '') {
            return '';
        }

        if (preg_match('/<\s*(htmlpagefooter|sethtmlpagefooter)\b/i', $combined) === 1) {
            return $combined;
        }

        return '';
    }

    private function normalizeMarginMm($rawValue, float $default): float
    {
        if ($rawValue === null || $rawValue === '') {
            return $default;
        }

        $value = (float) $rawValue;
        if (! is_finite($value)) {
            $value = $default;
        }

        $value = max(0.0, min(60.0, $value));

        return round($value, 2);
    }

    private function cmToMm($rawCmValue, float $defaultCm): float
    {
        if ($rawCmValue === null || $rawCmValue === '') {
            return round($defaultCm * 10.0, 2);
        }

        $cm = (float) $rawCmValue;
        if (! is_finite($cm)) {
            $cm = $defaultCm;
        }

        $cm = max(0.0, min(25.0, $cm));

        return round($cm * 10.0, 2);
    }

    private function resolveFallbackReportHtml(int $labRepoId, int $chargeItemId, int $labType): string
    {
        $html = '';

        if ($labRepoId > 0) {
            $repoRow = $this->db->table('lab_repo')
                ->select('HTMLData')
                ->where('mstRepoKey', $labRepoId)
                ->get(1)
                ->getRowArray();
            $html = trim((string) ($repoRow['HTMLData'] ?? ''));
        }

        if ($html !== '') {
            return $html;
        }

        if (! $this->db->tableExists('radiology_ultrasound_template') || $chargeItemId <= 0) {
            return '';
        }

        $invoiceItem = $this->db->table('invoice_item')
            ->select('item_id')
            ->where('id', $chargeItemId)
            ->get(1)
            ->getRowArray();

        $chargeId = (int) ($invoiceItem['item_id'] ?? 0);

        $builder = $this->db->table('radiology_ultrasound_template')
            ->select('Findings, Impression')
            ->where('Modality', $labType);

        if ($chargeId > 0) {
            $builder->groupStart()
                ->where('charge_id', $chargeId)
                ->orWhere('charge_id', 0)
                ->groupEnd();
        }

        $template = $builder->orderBy('id', 'ASC')->get(1)->getRowArray();
        if (! is_array($template)) {
            return '';
        }

        $findings = trim((string) ($template['Findings'] ?? ''));
        $impression = trim((string) ($template['Impression'] ?? ''));

        if ($findings === '' && $impression === '') {
            return '';
        }

        $html = $findings;
        if ($impression !== '') {
            $html .= '<div style="margin-top:10px;"><b>Impression :</b><br/>' . nl2br($impression) . '</div>';
        }

        return $html;
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
        $templateId = (int) ($this->request->getGet('template_id') ?? 0);

           set_time_limit(300);

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

        $isPlainPaper = false;

        $genderValue = (int) ($patient->gender ?? 0);
        $genderText = $genderValue === 1 ? 'Male' : ($genderValue === 2 ? 'Female' : '-');

        $ageText = '-';
        if (function_exists('get_age_1')) {
            $ageText = (string) get_age_1(
                $patient->dob ?? null,
                $patient->age ?? '',
                $patient->age_in_month ?? '',
                $patient->estimate_dob ?? ''
            );
        } elseif (! empty($patient->age)) {
            $ageText = (string) $patient->age;
        }

        $reportHtml = trim((string) ($invoiceRequest->report_data ?? ''));
        if ($reportHtml === '') {
            $reportHtml = '<p>No compiled report data available for this request.</p>';
        }

        $printSetting = $this->getDiagnosisTemplateSetting($labType, $templateId);

        $patientName  = trim((string) ($patient->p_fname ?? ''));
        $relativeName = trim((string) ($patient->p_relative ?? ''));
        $relativeText = trim((string) ($patient->p_rname ?? ''));
        $repoTitle    = trim((string) ($itemType->group_desc ?? 'Lab Report'));

        $tokens = $this->buildPdfTokens([
            'invoice_code'   => $invoice->invoice_code ?? '',
            'patient_name'   => $patientName,
            'relative_type'  => $relativeName,
            'relative_name'  => $relativeText,
            'age'            => $ageText,
            'gender'         => $genderText,
            'uhid'           => $patient->p_code ?? '',
            'collected_time' => $invoiceRequest->collected_time ?? '',
            'reported_time'  => $invoiceRequest->reported_time ?? '',
            'report_title'   => $repoTitle,
            'doctor_name'    => $head->doc_name ?? '',
            'doctor_education' => $head->doc_edu ?? '',
            'technician_name' => $head->tech_name ?? '',
            'signature_image_url' => (string) ($printSetting['signature_image'] ?? ''),
        ]);

        $rawPatientInfoHtml = trim((string) ($printSetting['patient_info_html'] ?? ''));
           if ($rawPatientInfoHtml !== '') {
               $patientInfoHtml = $this->applyPdfTokens($rawPatientInfoHtml, $tokens);
           } elseif (($printSetting['id'] ?? 0) <= 0) {
               $patientInfoHtml = $this->buildDefaultPatientInfoHtml([
                   'invoice_code'   => $invoice->invoice_code ?? '',
                   'patient_name'   => $patientName,
                   'relative_name'  => $relativeName,
                   'relative_text'  => $relativeText,
                   'age_text'       => $ageText,
                   'gender_text'    => $genderText,
                   'uhid'           => $patient->p_code ?? '',
                   'collected_time' => $invoiceRequest->collected_time ?? '',
                   'reported_time'  => $invoiceRequest->reported_time ?? '',
               ]);
           } else {
               $patientInfoHtml = '';
           }

        $reportHtml = trim((string) ($printSetting['content_prefix_html'] ?? '')) . $reportHtml . trim((string) ($printSetting['content_suffix_html'] ?? ''));

        $marginTop    = $this->cmToMm($printSetting['page_margin_top_cm'] ?? 1.2, 1.2);
        $marginBottom = $this->cmToMm($printSetting['page_margin_bottom_cm'] ?? 1.2, 1.2);
        $marginLeft   = $this->cmToMm($printSetting['page_margin_left_cm'] ?? 1.0, 1.0);
        $marginRight  = $this->cmToMm($printSetting['page_margin_right_cm'] ?? 1.0, 1.0);
        $marginHeader = $this->cmToMm($printSetting['margin_header_cm'] ?? 0.5, 0.5);
        $marginFooter = $this->cmToMm($printSetting['margin_footer_cm'] ?? 1.5, 1.5);

        $pdfHtml = view('diagnosis/pdf_compiled_report', [
            'invoiceRequest'  => $invoiceRequest,
            'invoice'         => $invoice,
            'patient'         => $patient,
            'reportHead'      => $reportHead,
            'reportHtml'      => $reportHtml,
            'isPlainPaper'    => $isPlainPaper,
            'ageText'         => $ageText,
            'genderText'      => $genderText,
            'printSetting'    => $printSetting,
            'labType'         => $labType,
            'invoiceId'       => $invoiceId,
            'itemType'        => $itemType,
            'patientName'     => $patientName,
            'relativeName'    => $relativeName,
            'relativeText'    => $relativeText,
            'repoTitle'       => $repoTitle,
            'patientInfoHtml' => $patientInfoHtml,
        ]);

        $rawHeaderHtml = trim((string) ($printSetting['header_html'] ?? ''));
        $rawFirstPageHeaderHtml = trim((string) ($printSetting['first_page_header_html'] ?? ''));
        $rawFooterHtml = trim((string) ($printSetting['footer_html'] ?? ''));
        $rawLastPageFooterHtml = trim((string) ($printSetting['last_page_footer_html'] ?? ''));

        $autoHeaderHtml = $this->buildAutoMpdfHeaderBlock(
            $rawHeaderHtml,
            $rawFirstPageHeaderHtml,
            'diag_auto_header_compiled'
        );
        $autoFooterHtml = $this->buildAutoMpdfFooterBlock(
            $rawFooterHtml,
            $rawLastPageFooterHtml,
            'diag_auto_footer_compiled'
        );

        $prefixHtml = $this->applyPdfTokens(trim((string) ($printSetting['mpdf_prefix_html'] ?? ''))
            . $autoHeaderHtml
            . $autoFooterHtml, $tokens);
        $suffixHtml = $this->applyPdfTokens(trim((string) ($printSetting['mpdf_suffix_html'] ?? '')), $tokens);

        $mpdfTempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
        if (! is_dir($mpdfTempDir)) {
            mkdir($mpdfTempDir, 0755, true);
        }

        $t_start = microtime(true);
        $mpdf = new Mpdf([
            'format'                => (string) ($printSetting['page_size'] ?? 'A4'),
            'margin_top'            => $marginTop,
            'margin_bottom'         => $marginBottom,
            'margin_left'           => $marginLeft,
            'margin_right'          => $marginRight,
            'margin_header'         => $marginHeader,
            'margin_footer'         => $marginFooter,
            'tempDir'               => $mpdfTempDir,
            'autoScriptToLang'      => false,
            'autoLanguageDetection' => false,
            'autoArabic'            => false,
            'autoVietnamese'        => false,
        ]);
        $t_mpdf_init = microtime(true);

        // For plain HTML (without explicit mPDF tags), set header/footer via API.
        $headerHasTags = preg_match('/<\s*(htmlpageheader|sethtmlpageheader)\b/i', $rawHeaderHtml . $rawFirstPageHeaderHtml) === 1;
        $footerHasTags = preg_match('/<\s*(htmlpagefooter|sethtmlpagefooter)\b/i', $rawFooterHtml . $rawLastPageFooterHtml) === 1;

        if (! $headerHasTags) {
            $plainHeader = $this->applyPdfTokens($rawHeaderHtml . $rawFirstPageHeaderHtml, $tokens);
            if ($plainHeader !== '') {
                $mpdf->SetHTMLHeader($plainHeader, 'O');
                $mpdf->SetHTMLHeader($plainHeader, 'E');
            }
        }

        if (! $footerHasTags) {
            $plainFooter = $this->applyPdfTokens($rawFooterHtml, $tokens);
            if ($plainFooter === '') {
                $plainFooter = $this->applyPdfTokens($rawLastPageFooterHtml, $tokens);
            }

            if ($plainFooter !== '') {
                $mpdf->SetHTMLFooter($plainFooter, 'O');
                $mpdf->SetHTMLFooter($plainFooter, 'E');
            }
        }

        $backgroundImage = trim((string) ($printSetting['page_background_image'] ?? ''));
        if ($backgroundImage !== '') {
            $absolutePath = FCPATH . ltrim(str_replace('\\', '/', $backgroundImage), '/');
            if (is_file($absolutePath)) {
                $mpdf->SetWatermarkImage($absolutePath, 1, [210, 297], 'F');
                $mpdf->showWatermarkImage = true;
            }
        }

        $watermarkType = (string) ($printSetting['watermark_type'] ?? 'none');
        $watermarkAlpha = (float) ($printSetting['watermark_alpha'] ?? 0.12);
        if ($watermarkType === 'text') {
            $text = trim((string) ($printSetting['watermark_text'] ?? ''));
            if ($text !== '') {
                $mpdf->SetWatermarkText($text, $watermarkAlpha);
                $mpdf->showWatermarkText = true;
            }
        } elseif ($watermarkType === 'image') {
            $wmImage = trim((string) ($printSetting['watermark_image'] ?? ''));
            if ($wmImage !== '') {
                $absolutePath = FCPATH . ltrim(str_replace('\\', '/', $wmImage), '/');
                if (is_file($absolutePath)) {
                    $mpdf->SetWatermarkImage($absolutePath, $watermarkAlpha);
                    $mpdf->showWatermarkImage = true;
                }
            }
        }

        if ($prefixHtml !== '') {
            $mpdf->WriteHTML($prefixHtml, HTMLParserMode::HTML_HEADER_BUFFER);
        }
        $t_prefix = microtime(true);

        $mpdf->WriteHTML($pdfHtml);
        $t_body = microtime(true);

        if ($suffixHtml !== '') {
            $mpdf->WriteHTML($suffixHtml, HTMLParserMode::HTML_HEADER_BUFFER, false, false);
        }
        $t_suffix = microtime(true);

        $invoiceCode = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) ($invoice->invoice_code ?? 'invoice'));
        $fileName = 'Compiled_Report_' . $invoiceCode . '_' . $labType . '.pdf';

        $pdfOutput = $mpdf->Output($fileName, 'S');
        $t_output = microtime(true);

        log_message('debug', sprintf(
            '[PDF_TIMING printPdfCreate] invoice=%s lab=%s | new_Mpdf()=%.2fs | WriteHTML(prefix)=%.2fs | WriteHTML(body)=%.2fs | WriteHTML(suffix)=%.2fs | Output()=%.2fs | TOTAL=%.2fs | body_bytes=%d',
            $invoiceId, $labType,
            $t_mpdf_init - $t_start,
            $t_prefix  - $t_mpdf_init,
            $t_body    - $t_prefix,
            $t_suffix  - $t_body,
            $t_output  - $t_suffix,
            $t_output  - $t_start,
            strlen($pdfHtml)
        ));

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setHeader('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->setBody($pdfOutput);
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

    public function imagingUploadGallery($invoiceId, $labType, $labReqId = 0)
    {
        $invoiceId = (int) $invoiceId;
        $labType = (int) $labType;
        $labReqId = (int) $labReqId;

        if ($invoiceId <= 0 || $labType <= 0 || ! $this->db->tableExists('file_upload_data')) {
            return view('diagnosis/imaging_upload_gallery', [
                'files' => [],
                'study_name' => '',
            ]);
        }

        $studyName = '';
        if ($labReqId > 0) {
            $requestRow = $this->db->table('lab_request')
                ->select('id, report_name')
                ->where('id', $labReqId)
                ->get(1)
                ->getRowArray();
            $studyName = trim((string) ($requestRow['report_name'] ?? ''));
        }

        $builder = $this->db->table('file_upload_data')
            ->where('isdelete', 0)
            ->where('charge_id', $invoiceId)
            ->where('charge_type', $labType);

        $fields = $this->db->getFieldNames('file_upload_data');
        if ($labReqId > 0 && in_array('repo_id', $fields, true)) {
            $builder->where('repo_id', $labReqId);
        }

        $rows = $builder->orderBy('id', 'DESC')->get()->getResultArray();
        $files = [];
        foreach ($rows as $row) {
            $fullPath = trim((string) ($row['full_path'] ?? ''));
            $url = $this->buildUploadPublicUrl($fullPath);
            $mime = trim((string) ($row['file_type'] ?? ''));
            $ext = strtolower(pathinfo((string) ($row['file_name'] ?? ''), PATHINFO_EXTENSION));
            $isImage = (int) ($row['is_image'] ?? 0) === 1 || str_starts_with($mime, 'image/');
            $isPdf = $ext === 'pdf' || $mime === 'application/pdf';
            $isDicom = $this->isDicomMimeOrExtension($mime, $ext);

            $files[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['orig_name'] ?? $row['file_name'] ?? 'Uploaded file'),
                'desc' => (string) ($row['file_desc'] ?? ''),
                'url' => $url,
                'is_image' => $isImage,
                'is_pdf' => $isPdf,
                'is_dicom' => $isDicom,
                'dicom_preview_url' => $isDicom ? base_url('diagnosis/imaging-dicom-preview/' . (int) ($row['id'] ?? 0)) : '',
                'insert_time' => (string) ($row['insert_time'] ?? $row['insert_date'] ?? ''),
                'ai_status' => (string) ($row['ai_status'] ?? ''),
                'ai_alert_text' => (string) ($row['ai_alert_text'] ?? ''),
                'scan_type' => (string) ($row['scan_type'] ?? ''),
            ];
        }

        return view('diagnosis/imaging_upload_gallery', [
            'files' => $files,
            'study_name' => $studyName,
        ]);
    }

    public function imagingDicomPreview($fileId)
    {
        $fileId = (int) $fileId;
        if ($fileId <= 0 || ! $this->db->tableExists('file_upload_data')) {
            return $this->response->setStatusCode(404)->setBody('DICOM file not found');
        }

        $fileRow = $this->db->table('file_upload_data')
            ->where('id', $fileId)
            ->where('isdelete', 0)
            ->get(1)
            ->getRowArray();

        if (empty($fileRow)) {
            return $this->response->setStatusCode(404)->setBody('DICOM file not found');
        }

        $absolutePath = $this->resolveUploadAbsolutePath((string) ($fileRow['full_path'] ?? ''));
        if ($absolutePath === '' || ! is_file($absolutePath)) {
            return $this->response->setStatusCode(404)->setBody('DICOM file missing on server');
        }

        $mime = $this->detectMimeType($absolutePath);
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (! $this->isDicomMimeOrExtension($mime, $ext)) {
            return $this->response->setStatusCode(415)->setBody('File is not a DICOM');
        }

        $preview = $this->callAiServerDicomPreview($absolutePath, $mime);
        if (($preview['error'] ?? '') !== '') {
            return $this->response->setStatusCode(422)->setBody((string) $preview['error']);
        }

        $bytes = (string) ($preview['bytes'] ?? '');
        if ($bytes === '') {
            return $this->response->setStatusCode(422)->setBody('Unable to render DICOM preview');
        }

        return $this->response
            ->setHeader('Content-Type', (string) ($preview['mime'] ?? 'image/jpeg'))
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setBody($bytes);
    }

    public function uploadReportFile()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('file_upload_data')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'file_upload_data table not found']);
        }

        $invoiceId = (int) $this->request->getPost('invoice_id');
        $labType = (int) $this->request->getPost('lab_type');
        $reqId = (int) $this->request->getPost('req_id');
        $fileDesc = trim((string) $this->request->getPost('file_desc'));
        $scanType = trim((string) $this->request->getPost('scan_type'));

        if ($invoiceId <= 0 || $labType <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Missing invoice/lab context']);
        }

        $upload = $this->request->getFile('report_file');
        if (! $upload || ! $upload->isValid()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'No valid file uploaded']);
        }

        $ext = strtolower((string) $upload->getExtension());
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif', 'pdf', 'dcm', 'dicom'];
        if (! in_array($ext, $allowed, true)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unsupported file type. Allowed: image, PDF, DICOM (.dcm)']);
        }

        $targetDir = ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'diagnosis' . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0775, true) && ! is_dir($targetDir)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to create upload directory']);
        }

        $storedName = 'diag_' . date('Ymd_His') . '_' . substr(md5((string) mt_rand()), 0, 8) . '.' . $ext;

        try {
            $upload->move($targetDir, $storedName, true);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Upload failed: ' . $e->getMessage()]);
        }

        $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $storedName;
        $relativePath = 'uploads/diagnosis/' . date('Y') . '/' . date('m') . '/' . $storedName;
        $publicUrl = base_url($relativePath);

        $user = function_exists('auth') ? auth()->user() : null;
        $uploadBy = trim((string) ($user->username ?? $user->email ?? 'system'));
        $uploadById = (int) ($user->id ?? 0);

        $fields = $this->db->getFieldNames('file_upload_data');
        $insert = [];
        $fieldMap = [
            'name' => $storedName,
            'file_name' => $storedName,
            'orig_name' => (string) $upload->getClientName(),
            'client_name' => (string) $upload->getClientName(),
            'file_ext' => '.' . $ext,
            'file_type' => (string) $upload->getClientMimeType(),
            'full_path' => str_replace('\\', '/', $absolutePath),
            'file_path' => str_replace('\\', '/', $targetDir) . '/',
            'raw_name' => pathinfo($storedName, PATHINFO_FILENAME),
            'store_file_name' => $storedName,
            'file_size' => round(((float) $upload->getSize()) / 1024, 2),
            'is_image' => in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif'], true) ? 1 : 0,
            'image_type' => $ext,
            'insert_date' => Time::now('Asia/Kolkata')->toDateTimeString(),
            'insert_time' => Time::now('Asia/Kolkata')->toDateTimeString(),
            'file_desc' => $fileDesc !== '' ? $fileDesc : 'Diagnosis Report Upload',
            'repo_id' => $reqId,
            'charge_id' => $invoiceId,
            'charge_type' => $labType,
            'upload_by' => $uploadBy,
            'upload_by_id' => $uploadById,
            'scan_type' => $scanType !== '' ? $scanType : 'upload',
            'show_type' => 0,
            'isdelete' => 0,
            'document_type' => 'diagnosis-report',
            'content_description' => 'Uploaded from diagnosis editor',
            'ai_status' => 'pending',
            'ai_alert_flag' => 0,
            'ai_alert_text' => null,
        ];

        foreach ($fieldMap as $key => $value) {
            if (in_array($key, $fields, true)) {
                $insert[$key] = $value;
            }
        }

        $ok = $this->db->table('file_upload_data')->insert($insert);
        if (! $ok) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to save upload metadata']);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'File uploaded successfully',
            'file_id' => (int) $this->db->insertID(),
            'file_name' => $storedName,
            'file_url' => $publicUrl,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
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
            return $this->response->setJSON(['update' => 0, 'error_text' => 'OCR failed. Configure DIAGNOSIS_AI_OCR_ENDPOINT on your AI server.']);
        }

        $parsed = $this->parseLabValuesWithAi($ocrText, $panelName);
        if (empty($parsed['values']) || ! is_array($parsed['values'])) {
            $errorText = trim((string) ($parsed['error'] ?? ''));
            if ($errorText === '') {
                $errorText = 'AI parsing failed. Check AI endpoint/settings and try retry.';
            }

            return $this->response->setJSON(['update' => 0, 'error_text' => $errorText]);
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
            $rawResponse,
            (string) ($parsed['provider'] ?? 'ai-server')
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
            'ai_provider' => (string) ($parsed['provider'] ?? 'ai-server'),
            'ai_model' => $modelName,
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

    public function imagingAiDiagnosis($labReqId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $labReqId = (int) $labReqId;
        if ($labReqId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request id']);
        }

        $labRequest = $this->db->table('lab_request')
            ->where('id', $labReqId)
            ->get(1)
            ->getRowArray();

        if (empty($labRequest)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Imaging request not found']);
        }

        $labType = (int) ($labRequest['lab_type'] ?? 0);
        if (! $this->isRadiologyType($labType)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'AI diagnosis is available for imaging only']);
        }

        if (! $this->db->tableExists('file_upload_data')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'file_upload_data table not found']);
        }

        if (! $this->db->tableExists('lab_ai_extraction_batches')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'AI batch table not found. Run migrations first.']);
        }

        $invoiceId = (int) ($labRequest['charge_id'] ?? 0);
        $studyName = trim((string) ($labRequest['report_name'] ?? 'Imaging Study'));
        $files = $this->findImagingFilesForRequest($invoiceId, $labType, $labReqId);

        if (empty($files)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'No uploaded image/PDF found for this study']);
        }

        $fileIds = array_values(array_filter(array_map(static fn($row) => (int) ($row['id'] ?? 0), $files)));
        $this->updateImagingFileAiState($fileIds, 'processing', 'AI diagnosis is running');

        $ocrChunks = [];
        $visionImages = [];
        $sourceFiles = [];

        foreach ($files as $fileRow) {
            $absolutePath = $this->resolveUploadAbsolutePath((string) ($fileRow['full_path'] ?? ''));
            if ($absolutePath === '' || ! is_file($absolutePath)) {
                continue;
            }

            $fileName = (string) ($fileRow['orig_name'] ?? $fileRow['file_name'] ?? basename($absolutePath));
            $mime = $this->detectMimeType($absolutePath);
            $sourceFiles[] = [
                'id' => (int) ($fileRow['id'] ?? 0),
                'name' => $fileName,
                'scan_type' => (string) ($fileRow['scan_type'] ?? ''),
            ];

            $ocrText = trim($this->extractTextFromLabFile($absolutePath));
            if ($ocrText !== '') {
                $ocrChunks[] = 'FILE: ' . $fileName . "\n" . $ocrText;
            }

            $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            if ((str_starts_with($mime, 'image/') || $this->isDicomMimeOrExtension($mime, $ext)) && count($visionImages) < 4) {
                $visionImages[] = [
                    'abs_path' => $absolutePath,
                    'mime' => $mime,
                ];
            }
        }

        $patientName = trim((string) ($labRequest['patient_name'] ?? 'Unknown'));
        $patientAge = 0;
        $patientGender = 'Unknown';
        $patientId = (int) ($labRequest['patient_id'] ?? 0);
        if ($patientId > 0 && $this->db->tableExists('patient_master')) {
            $patient = $this->db->table('patient_master')
                ->select('gender, age, age_in_month, dob')
                ->where('id', $patientId)
                ->get(1)
                ->getRowArray();

            $genderRaw = (int) ($patient['gender'] ?? 0);
            $patientGender = $genderRaw === 1 ? 'Male' : ($genderRaw === 2 ? 'Female' : 'Unknown');

            $patientAge = (int) ($patient['age'] ?? 0);
            if ($patientAge <= 0) {
                $months = (int) ($patient['age_in_month'] ?? 0);
                if ($months > 0) {
                    $patientAge = max(1, (int) floor($months / 12));
                }
            }
            if ($patientAge <= 0) {
                $dob = trim((string) ($patient['dob'] ?? ''));
                if ($dob !== '' && $dob !== '0000-00-00') {
                    try {
                        $patientAge = (int) ((new \DateTime($dob))->diff(new \DateTime('today'))->y ?? 0);
                    } catch (\Throwable $e) {
                        $patientAge = 0;
                    }
                }
            }
        }

        $analysis = $this->generateImagingDiagnosisDraft($studyName, $ocrChunks, $visionImages, $patientName, $patientAge, $patientGender);
        if (($analysis['error'] ?? '') !== '') {
            $this->updateImagingFileAiState($fileIds, 'failed', (string) $analysis['error'], 1);
            return $this->response->setJSON(['update' => 0, 'error_text' => (string) $analysis['error']]);
        }

        $promptUsed = $this->buildImagingDiagnosisPrompt($studyName);

        $rawPayload = [
            'type' => 'imaging-ai-diagnosis',
            'request_id' => $labReqId,
            'invoice_id' => $invoiceId,
            'lab_type' => $labType,
            'study_name' => $studyName,
            'title' => (string) ($analysis['title'] ?? $studyName),
            'findings_html' => (string) ($analysis['findings_html'] ?? ''),
            'impression_html' => (string) ($analysis['impression_html'] ?? ''),
            'summary_text' => (string) ($analysis['summary_text'] ?? ''),
            'provider' => (string) ($analysis['provider'] ?? 'ai-server'),
            'model' => (string) ($analysis['model'] ?? ''),
            'prompt_used' => $promptUsed,
            'source_files' => $sourceFiles,
            'raw' => $analysis['raw'] ?? [],
        ];

        $ocrText = trim(implode("\n\n", $ocrChunks));
        $batchId = $this->insertLabAiBatch(
            $invoiceId,
            $labType,
            (int) ($fileIds[0] ?? 0),
            $studyName,
            (string) ($analysis['model'] ?? ''),
            $ocrText,
            (string) json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            (string) ($analysis['provider'] ?? 'ai-server')
        );

        if ($batchId <= 0) {
            $this->updateImagingFileAiState($fileIds, 'failed', 'Unable to store AI diagnosis result', 1);
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to store AI diagnosis result']);
        }

        $this->updateImagingFileAiState($fileIds, 'completed', (string) ($analysis['summary_text'] ?? 'AI draft ready'));

        $this->auditClinicalUpdate('lab_ai_extraction_batches', 'created', $batchId, null, [
            'invoice_id' => $invoiceId,
            'lab_type' => $labType,
            'request_id' => $labReqId,
            'source_file_ids' => $fileIds,
            'mode' => 'imaging-ai-diagnosis',
        ]);

        $html = view('diagnosis/imaging_ai_result_modal', [
            'batch_id' => $batchId,
            'title' => (string) ($analysis['title'] ?? $studyName),
            'study_name' => $studyName,
            'findings_html' => (string) ($analysis['findings_html'] ?? ''),
            'impression_html' => (string) ($analysis['impression_html'] ?? ''),
            'summary_text' => (string) ($analysis['summary_text'] ?? ''),
            'provider' => (string) ($analysis['provider'] ?? 'ai-server'),
            'model' => (string) ($analysis['model'] ?? ''),
        ]);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'AI diagnosis draft is ready and stored.',
            'batch_id' => $batchId,
            'html' => $html,
            'findings_html' => (string) ($analysis['findings_html'] ?? ''),
            'impression_html' => (string) ($analysis['impression_html'] ?? ''),
            'summary_text' => (string) ($analysis['summary_text'] ?? ''),
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
        string $rawResponse,
        string $provider = 'ai-server'
    ): int {
        $ok = $this->db->table('lab_ai_extraction_batches')->insert([
            'invoice_id' => $invoiceId,
            'lab_type' => $labType,
            'file_upload_id' => $fileId,
            'panel_name' => $panelName,
            'ai_provider' => $provider,
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function findImagingFilesForRequest(int $invoiceId, int $labType, int $labReqId): array
    {
        if ($invoiceId <= 0 || $labType <= 0 || ! $this->db->tableExists('file_upload_data')) {
            return [];
        }

        $builder = $this->db->table('file_upload_data')
            ->where('isdelete', 0)
            ->where('charge_id', $invoiceId)
            ->where('charge_type', $labType);

        $fields = $this->db->getFieldNames('file_upload_data');
        if ($labReqId > 0 && in_array('repo_id', $fields, true)) {
            $builder->where('repo_id', $labReqId);
        }

        return $builder->orderBy('id', 'DESC')->get()->getResultArray();
    }

    private function updateImagingFileAiState(array $fileIds, string $status, string $message = '', int $alertFlag = 0): void
    {
        $fileIds = array_values(array_filter(array_map('intval', $fileIds)));
        if (empty($fileIds) || ! $this->db->tableExists('file_upload_data')) {
            return;
        }

        $fields = $this->db->getFieldNames('file_upload_data');
        $update = [];
        if (in_array('ai_status', $fields, true)) {
            $update['ai_status'] = $status;
        }
        if (in_array('ai_alert_text', $fields, true)) {
            $update['ai_alert_text'] = $message !== '' ? mb_substr($message, 0, 255) : null;
        }
        if (in_array('ai_alert_flag', $fields, true)) {
            $update['ai_alert_flag'] = $alertFlag > 0 ? 1 : 0;
        }

        if ($update === []) {
            return;
        }

        $this->db->table('file_upload_data')->whereIn('id', $fileIds)->update($update);
    }

    private function buildUploadPublicUrl(string $fullPath): string
    {
        $normalized = str_replace('\\', '/', trim($fullPath));
        if ($normalized === '') {
            return '';
        }

        $publicPos = stripos($normalized, '/public/');
        if ($publicPos !== false) {
            return base_url(substr($normalized, $publicPos + 8));
        }

        $uploadsPos = stripos($normalized, 'uploads/');
        if ($uploadsPos !== false) {
            return base_url(substr($normalized, $uploadsPos));
        }

        return '';
    }

    private function buildVisionImagePayload(string $absolutePath, string $mime): string
    {
        $bytes = @file_get_contents($absolutePath);
        if ($bytes === false || $bytes === '') {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }

    /**
     * @param array<int, string> $ocrChunks
     * @param array<int, array<string, mixed>> $visionImages
     * @return array<string, mixed>
     */
    private function generateImagingDiagnosisDraft(string $studyName, array $ocrChunks, array $visionImages, string $patientName = 'Unknown', int $patientAge = 0, string $patientGender = 'Unknown'): array
    {
        if ($visionImages === []) {
            return [
                'error' => 'No image available for AI diagnosis.',
            ];
        }

        $firstImagePath = (string) ($visionImages[0]['abs_path'] ?? '');
        $firstImageMime = (string) ($visionImages[0]['mime'] ?? 'image/jpeg');
        if ($firstImagePath === '' || ! is_file($firstImagePath)) {
            return [
                'error' => 'Image file not found on server for AI diagnosis.',
            ];
        }

        $response = $this->callAiServerDiagnosis($studyName, $firstImagePath, $firstImageMime, $patientName, $patientAge, $patientGender);
        if (($response['error'] ?? '') !== '') {
            return $response;
        }

        $report = $response['report'] ?? [];
        $technique = $this->normalizeAiTextValue($report['Technique'] ?? $studyName, $studyName);
        $impression = $this->normalizeAiTextValue($report['Impression'] ?? 'AI diagnosis draft prepared.', 'AI diagnosis draft prepared.');

        $findings = $report['Findings'] ?? [];
        $narrative = '';
        $labels = [];
        $ocrText = [];
        if (is_array($findings)) {
            $narrative = $this->normalizeAiTextValue($findings['Narrative'] ?? '', '');
            $labels = $this->normalizeAiListValue($findings['Labels'] ?? []);
            $ocrText = $this->normalizeAiListValue($findings['OCR Text'] ?? []);
        } else {
            $narrative = $this->normalizeAiTextValue($findings, '');
        }

        $labelHtml = '';
        if ($labels !== []) {
            $safeLabels = array_slice(array_map(static fn($x) => trim((string) $x), $labels), 0, 8);
            $safeLabels = array_values(array_filter($safeLabels, static fn($x) => $x !== ''));
            if ($safeLabels !== []) {
                $labelHtml = '<p><strong>Detected labels:</strong> ' . esc(implode(', ', $safeLabels)) . '</p>';
            }
        }

        $ocrHtml = '';
        if ($ocrText !== []) {
            $ocrFirst = trim((string) ($ocrText[0] ?? ''));
            if ($ocrFirst !== '') {
                $ocrHtml = '<p><strong>OCR notes:</strong> ' . nl2br(esc(mb_substr($ocrFirst, 0, 1500))) . '</p>';
            }
        }

        $findingsHtml = '<p><strong>Technique:</strong> ' . esc($technique) . '</p>';
        if ($narrative !== '') {
            $paragraphs = preg_split('/\R{2,}|\n/u', $narrative) ?: [];
            $paragraphs = array_values(array_filter(array_map(static fn($item) => trim((string) $item), $paragraphs), static fn($item) => $item !== ''));
            if ($paragraphs === []) {
                $findingsHtml .= '<p>' . nl2br(esc($narrative)) . '</p>';
            } else {
                foreach ($paragraphs as $paragraph) {
                    $findingsHtml .= '<p>' . esc($paragraph) . '</p>';
                }
            }
        } else {
            $findingsHtml .= $labelHtml . $ocrHtml;
        }

        return [
            'provider' => (string) ($response['provider'] ?? 'ai-server'),
            'model' => (string) ($response['model'] ?? 'ai-server-diagnosis'),
            'title' => trim((string) ($studyName !== '' ? $studyName : ($technique !== '' ? $technique : 'Imaging Study'))),
            'findings_html' => $findingsHtml,
            'impression_html' => '<p>' . esc($impression) . '</p>',
            'summary_text' => mb_substr($impression !== '' ? $impression : 'AI diagnosis draft prepared.', 0, 220),
            'raw' => $response['raw'] ?? [],
            'error' => '',
        ];
    }

    /**
     * @param mixed $value
     */
    private function normalizeAiTextValue($value, string $fallback = ''): string
    {
        if (is_string($value)) {
            $text = trim($value);
            return $text !== '' ? $text : trim($fallback);
        }

        if (is_scalar($value)) {
            $text = trim((string) $value);
            return $text !== '' ? $text : trim($fallback);
        }

        if (is_array($value)) {
            $chunks = [];
            array_walk_recursive($value, static function ($item) use (&$chunks): void {
                if (is_scalar($item)) {
                    $text = trim((string) $item);
                    if ($text !== '') {
                        $chunks[] = $text;
                    }
                }
            });

            if ($chunks !== []) {
                return trim(implode(' ', array_slice($chunks, 0, 8)));
            }
        }

        return trim($fallback);
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeAiListValue($value): array
    {
        if (is_string($value)) {
            $text = trim($value);
            return $text !== '' ? [$text] : [];
        }

        if (is_scalar($value)) {
            $text = trim((string) $value);
            return $text !== '' ? [$text] : [];
        }

        if (! is_array($value)) {
            return [];
        }

        $items = [];
        array_walk_recursive($value, static function ($item) use (&$items): void {
            if (is_scalar($item)) {
                $text = trim((string) $item);
                if ($text !== '') {
                    $items[] = $text;
                }
            }
        });

        return array_slice($items, 0, 20);
    }

    private function extractTextFromLabFile(string $absolutePath): string
    {
        return $this->extractTextWithAiServerOcr($absolutePath);
    }

    private function extractTextWithAiServerOcr(string $absolutePath): string
    {
        $endpoint = rtrim($this->readSetting('DIAGNOSIS_AI_OCR_ENDPOINT'), '/');
        if ($endpoint === '') {
            return '';
        }

        $mime = $this->detectMimeType($absolutePath);
        if (! str_starts_with($mime, 'image/')) {
            return '';
        }

        try {
            $client = service('curlrequest', $this->httpOptions());
            $headers = [
                'Accept' => 'application/json',
            ];

            $token = trim($this->readSetting('DIAGNOSIS_AI_PARSE_TOKEN'));
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $curlFile = new \CURLFile($absolutePath, $mime, basename($absolutePath));
            $response = $client->post($endpoint, [
                'headers' => $headers,
                'multipart' => [
                    'file' => $curlFile,
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return '';
            }

            $decoded = json_decode((string) $response->getBody(), true);
            if (! is_array($decoded)) {
                return '';
            }

            foreach (['ocr_text', 'text', 'extracted_text'] as $key) {
                $value = trim((string) ($decoded[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
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
    private function parseLabValuesWithAi(string $ocrText, string $panelName): array
    {
        $external = $this->parseLabValuesWithExternalService($ocrText, $panelName);
        if (! empty($external['values']) && is_array($external['values'])) {
            return $external;
        }
        $externalError = trim((string) ($external['error'] ?? ''));
        return [
            'provider' => 'none',
            'model' => '',
            'values' => [],
            'raw' => [],
            'error' => $externalError !== '' ? $externalError : 'AI parse failed. Configure DIAGNOSIS_AI_PARSE_ENDPOINT on your AI server.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function callAiServerDiagnosis(string $studyName, string $filePath, string $mime = 'image/jpeg', string $patientName = 'Unknown', int $patientAge = 0, string $patientGender = 'Unknown'): array
    {
        $base = $this->resolveAiServerBaseUrl();
        if ($base === '') {
            return [
                'error' => 'AI server URL is missing. Configure DIAGNOSIS_AI_SERVER_URL or set parse/OCR endpoint in AI Settings.',
            ];
        }

        if (! is_file($filePath)) {
            return [
                'error' => 'Image file not found: ' . basename($filePath),
            ];
        }

        $isDicom = $this->isDicomMimeOrExtension($mime, strtolower(pathinfo($filePath, PATHINFO_EXTENSION)));
        if ($isDicom) {
            $ext = 'dcm';
        } else {
            $ext = str_contains($mime, 'png') ? 'png' : (str_contains($mime, 'webp') ? 'webp' : 'jpg');
        }
        $token = trim($this->readSetting('DIAGNOSIS_AI_PARSE_TOKEN'));
        $headers = ['Accept' => 'application/json'];
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $url = $base . '/diagnosis';
        $prompt = $this->buildImagingDiagnosisPrompt($studyName);
        $attempts = $this->aiMaxRetries();
        $lastError = 'AI server diagnosis request failed.';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $client = service('curlrequest', $this->httpOptions());
                $uploadMime = $isDicom ? 'application/dicom' : $mime;
                $curlFile = new \CURLFile($filePath, $uploadMime, 'study.' . $ext);
                $response = $client->post($url, [
                    'headers' => $headers,
                    'multipart' => [
                        'file'               => $curlFile,
                        'patient_name'       => $patientName !== '' ? $patientName : 'Unknown',
                        'patient_age'        => (string) max(0, $patientAge),
                        'patient_gender'     => $patientGender !== '' ? $patientGender : 'Unknown',
                        'clinical_indication' => $studyName !== '' ? $studyName : 'Imaging study',
                        'report_prompt'      => $prompt,
                    ],
                ]);

                $status = $response->getStatusCode();
                $body = (string) $response->getBody();
                if ($status < 200 || $status >= 300) {
                    $lastError = 'AI server HTTP ' . $status . ': ' . mb_substr($body, 0, 180);
                    continue;
                }

                $decoded = json_decode($body, true);
                if (! is_array($decoded)) {
                    $lastError = 'AI server returned non-JSON response.';
                    continue;
                }

                $report = $decoded['diagnosis_report'] ?? null;
                if (! is_array($report)) {
                    $lastError = trim((string) ($decoded['detail'] ?? 'AI server response missing diagnosis_report.'));
                    continue;
                }

                return [
                    'provider' => 'ai-server',
                    'model' => 'python-fastapi',
                    'report' => $report,
                    'raw' => $decoded,
                    'error' => '',
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage() !== '' ? $e->getMessage() : 'AI server request timeout/error.';
            }
        }

        return [
            'error' => $lastError,
        ];
    }

    private function buildImagingDiagnosisPrompt(string $studyName): string
    {
        $template = trim($this->readSetting('DIAGNOSIS_AI_IMAGING_PROMPT'));
        if ($template === '') {
            $template = "Generate a radiology report in concise clinical style.\n"
                . "Use this structure exactly:\n"
                . "1) Findings Draft\n"
                . "2) Technique\n"
                . "3) Impression Draft\n\n"
                . "Include checks for lungs/pleura, mediastinum/heart size, diaphragm/costophrenic angles, bones/soft tissue, and lines/tubes/devices if present.\n"
                . "If a structure is normal, explicitly state it as normal.\n"
                . "Do not mention AI. Keep output clinically readable for doctors.\n"
                . "Study Name: {study_name}";
        }

        $name = trim($studyName) !== '' ? trim($studyName) : 'Imaging study';
        $prompt = str_replace(['{study_name}', '{{study_name}}'], $name, $template);

        return trim($prompt);
    }

    private function resolveAiServerBaseUrl(): string
    {
        $base = rtrim($this->readSetting('DIAGNOSIS_AI_SERVER_URL'), '/');
        if ($base !== '') {
            return $base;
        }

        $candidateUrls = [
            trim((string) $this->readSetting('DIAGNOSIS_AI_PARSE_ENDPOINT')),
            trim((string) $this->readSetting('DIAGNOSIS_AI_OCR_ENDPOINT')),
        ];

        foreach ($candidateUrls as $url) {
            if ($url === '') {
                continue;
            }

            $parts = @parse_url($url);
            if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
                continue;
            }

            $base = (string) $parts['scheme'] . '://' . (string) $parts['host'];
            if (! empty($parts['port'])) {
                $base .= ':' . (int) $parts['port'];
            }

            return rtrim($base, '/');
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function parseLabValuesWithExternalService(string $ocrText, string $panelName): array
    {
        $endpoint = rtrim($this->readSetting('DIAGNOSIS_AI_PARSE_ENDPOINT'), '/');
        if ($endpoint === '') {
            return [
                'provider' => 'external',
                'model' => '',
                'values' => [],
                'raw' => [],
                'error' => '',
            ];
        }

        $token = trim($this->readSetting('DIAGNOSIS_AI_PARSE_TOKEN'));
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $attempts = $this->aiMaxRetries();
        $lastError = 'External AI service request failed.';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $client = service('curlrequest', $this->httpOptions());
                $response = $client->post($endpoint, [
                    'headers' => $headers,
                    'json' => [
                        'ocr_text' => mb_substr($ocrText, 0, 20000),
                        'panel_name' => $panelName,
                    ],
                ]);

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $decoded = json_decode((string) $response->getBody(), true);
                    if (! is_array($decoded)) {
                        $lastError = 'External AI response is not valid JSON.';
                        continue;
                    }

                    $values = $decoded['values'] ?? null;
                    if (! is_array($values)) {
                        $lastError = trim((string) ($decoded['error'] ?? 'External AI response missing values array.'));
                        continue;
                    }

                    return [
                        'provider' => (string) ($decoded['provider'] ?? 'external'),
                        'model' => (string) ($decoded['model'] ?? 'external-ai'),
                        'values' => $values,
                        'raw' => $decoded,
                        'error' => '',
                    ];
                }

                $lastError = 'External AI HTTP ' . $response->getStatusCode();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage() !== '' ? $e->getMessage() : 'External AI request timeout/error.';
            }
        }

        return [
            'provider' => 'external',
            'model' => '',
            'values' => [],
            'raw' => [],
            'error' => $lastError,
        ];
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
            return [
                'provider' => 'azure-openai',
                'model' => '',
                'values' => [],
                'raw' => [],
                'error' => 'Azure OpenAI settings are missing.',
            ];
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

        $attempts = $this->aiMaxRetries();
        $lastError = 'Azure AI parsing failed.';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
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
                    $lastError = 'Azure OpenAI HTTP ' . $response->getStatusCode();
                    continue;
                }

                $decoded = json_decode((string) $response->getBody(), true);
                $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
                if ($content === '') {
                    $lastError = 'Azure OpenAI returned empty content.';
                    continue;
                }

                $json = json_decode($content, true);
                if (! is_array($json)) {
                    $lastError = 'Azure OpenAI returned invalid JSON content.';
                    continue;
                }

                return [
                    'provider' => 'azure-openai',
                    'model' => (string) ($decoded['model'] ?? $deployment),
                    'values' => is_array($json['values'] ?? null) ? $json['values'] : [],
                    'raw' => $decoded,
                    'error' => '',
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage() !== '' ? $e->getMessage() : 'Azure OpenAI request timeout/error.';
            }
        }

        return [
            'provider' => 'azure-openai',
            'model' => '',
            'values' => [],
            'raw' => [],
            'error' => $lastError,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function httpOptions(): array
    {
        $timeout = $this->aiTimeoutSeconds();
        $options = [
            'timeout' => $timeout,
            'http_errors' => false,
        ];

        if (defined('ENVIRONMENT') && in_array((string) ENVIRONMENT, ['development', 'testing'], true)) {
            $options['verify'] = false;
        }

        return $options;
    }

    private function aiTimeoutSeconds(): int
    {
        $timeout = (int) $this->readSetting('DIAGNOSIS_AI_TIMEOUT_SECONDS');
        if ($timeout <= 0) {
            $timeout = 45;
        }

        if ($timeout > 180) {
            $timeout = 180;
        }

        return $timeout;
    }

    private function aiMaxRetries(): int
    {
        $retries = (int) $this->readSetting('DIAGNOSIS_AI_RETRY_ATTEMPTS');
        if ($retries <= 0) {
            $retries = 2;
        }

        if ($retries > 5) {
            $retries = 5;
        }

        return $retries;
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
                'dcm' => 'application/dicom',
                'dicom' => 'application/dicom',
            ];
            $mime = $map[$ext] ?? 'application/octet-stream';
        }

        if ($mime === 'application/octet-stream') {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['dcm', 'dicom'], true)) {
                return 'application/dicom';
            }
        }

        return $mime;
    }

    private function isDicomMimeOrExtension(string $mime, string $ext): bool
    {
        $mime = strtolower(trim($mime));
        $ext = strtolower(trim($ext));

        if (in_array($ext, ['dcm', 'dicom'], true)) {
            return true;
        }

        return str_contains($mime, 'dicom');
    }

    /**
     * @return array<string, mixed>
     */
    private function callAiServerDicomPreview(string $filePath, string $mime = 'application/dicom'): array
    {
        $base = $this->resolveAiServerBaseUrl();
        if ($base === '') {
            return [
                'error' => 'AI server URL is missing for DICOM preview.',
            ];
        }

        if (! is_file($filePath)) {
            return [
                'error' => 'DICOM file not found on server.',
            ];
        }

        $token = trim($this->readSetting('DIAGNOSIS_AI_PARSE_TOKEN'));
        $headers = ['Accept' => 'image/*'];
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $url = $base . '/dicom-preview';
        $attempts = $this->aiMaxRetries();
        $lastError = 'DICOM preview request failed.';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $client = service('curlrequest', $this->httpOptions());
                $curlFile = new \CURLFile($filePath, 'application/dicom', basename($filePath));
                $response = $client->post($url, [
                    'headers' => $headers,
                    'multipart' => [
                        'file' => $curlFile,
                    ],
                ]);

                $status = $response->getStatusCode();
                $body = (string) $response->getBody();
                if ($status < 200 || $status >= 300) {
                    $lastError = 'AI server HTTP ' . $status . ': ' . mb_substr($body, 0, 180);
                    continue;
                }

                $contentType = strtolower(trim((string) $response->getHeaderLine('Content-Type')));
                if (! str_starts_with($contentType, 'image/')) {
                    $lastError = 'AI server did not return image data for DICOM preview.';
                    continue;
                }

                return [
                    'bytes' => $body,
                    'mime' => $contentType,
                    'error' => '',
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage() !== '' ? $e->getMessage() : 'DICOM preview service timeout/error.';
            }
        }

        return [
            'error' => $lastError,
        ];
    }

    public function selectLabRadiology($invoiceId, $labType)
    {
        return $this->selectLabInvoicePath((int) $invoiceId, (int) $labType);
    }
}