<?php

namespace App\Controllers;

use App\Libraries\ClinicalAuditTrail;
use App\Libraries\FhirR4Builder;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    protected $helpers = ['age', 'common'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;
    protected $db;
    protected ClinicalAuditTrail $clinicalAuditTrail;
    protected FhirR4Builder $fhirR4Builder;
    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        $this->db = db_connect();
        $this->clinicalAuditTrail = new ClinicalAuditTrail();
        $this->fhirR4Builder = new FhirR4Builder();
        $this->loadLegacyHospitalConstants();
        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * @param string|int|null $recordId
     * @param string|int|null $userId
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    protected function auditClinicalUpdate(
        string $module,
        string $fieldName,
        $recordId,
        $oldValue,
        $newValue,
        $userId = null
    ): void {
        if ($userId === null && function_exists('auth')) {
            $user = auth()->user();
            $userId = $user->id ?? null;
        }

        $this->clinicalAuditTrail->logFieldUpdate($module, $recordId, $fieldName, $oldValue, $newValue, $userId);
    }

    protected function loadLegacyHospitalConstants(): void
    {
        if (! $this->db || ! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('hospital_setting')) {
            return;
        }

        try {
            $rows = $this->db->table('hospital_setting')
                ->select('s_name, s_value')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $key = trim((string) ($row['s_name'] ?? ''));
                if ($key === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                    continue;
                }

                if (! defined($key)) {
                    define($key, (string) ($row['s_value'] ?? ''));
                }
            }
        } catch (\Throwable $e) {
        }
    }
}
