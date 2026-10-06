<?php

namespace Tests\Unit\Abdm;

use App\Controllers\AbdmGateway;
use App\Libraries\Abdm\EAtriaBridgeConnector;
use App\Libraries\Abdm\Sync\AbdmTaskBoardSyncService;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;
use ReflectionMethod;

final class TestHipDemographicsCareContext extends CIUnitTestCase
{
    private AbdmGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = \Config\Database::connect();

        $this->db->query("CREATE TABLE IF NOT EXISTS " . $this->db->prefixTable('patient_master') . " (
            id INTEGER PRIMARY KEY,
            p_code TEXT,
            p_fname TEXT,
            dob TEXT,
            age TEXT,
            gender INTEGER,
            abha_id TEXT,
            abha_address TEXT,
            mphone1 TEXT
        )");

        $this->db->query("DELETE FROM " . $this->db->prefixTable('patient_master'));

        // Patient 12 (Keshav Singh): has full DOB and ABHA number & address
        $this->db->table('patient_master')->insert([
            'id'           => 12,
            'p_code'       => 'P12',
            'p_fname'      => 'Keshav Singh',
            'dob'          => '1976-05-14',
            'age'          => '48',
            'gender'       => 1,
            'abha_id'      => '91747451787143',
            'abha_address' => 'singhkeshav301976@sbx',
            'mphone1'      => '9876543210',
        ]);

        // Patient 19 (Ramesh Kumar): invalid '0000-00-00' DOB, age = 40
        $this->db->table('patient_master')->insert([
            'id'           => 19,
            'p_code'       => 'P19',
            'p_fname'      => 'Ramesh Kumar',
            'dob'          => '0000-00-00',
            'age'          => '40',
            'gender'       => 1,
            'abha_id'      => '12345678901234',
            'abha_address' => 'ramesh@sbx',
            'mphone1'      => '9876543211',
        ]);

        // Patient 25 (Priya Sharma): empty DOB, age = 25, abha_id contains address '@abdm'
        $this->db->table('patient_master')->insert([
            'id'           => 25,
            'p_code'       => 'P25',
            'p_fname'      => 'Priya Sharma',
            'dob'          => '',
            'age'          => '25',
            'gender'       => 2,
            'abha_id'      => 'priyasharma@abdm',
            'abha_address' => '',
            'mphone1'      => '9876543212',
        ]);

        $this->gateway = new AbdmGateway();
        $req = \Config\Services::request();
        $resp = \Config\Services::response();
        $logger = \Config\Services::logger();
        $this->gateway->initController($req, $resp, $logger);
    }

    public function testHipPatientCareContextsDemographicsForKeshav(): void
    {
        $_GET['patient_id'] = '12';
        $_REQUEST['patient_id'] = '12';

        $response = $this->gateway->hipPatientCareContexts();
        $body = json_decode($response->getBody(), true);

        $this->assertEquals(1, $body['ok'] ?? 0);
        $this->assertEquals(1976, $body['patient']['year_of_birth']);
        $this->assertEquals('91747451787143', $body['patient']['abha_number']);
        $this->assertEquals('singhkeshav301976@sbx', $body['patient']['abha_address']);
        $this->assertEquals('M', $body['patient']['gender']);
    }

    public function testHipPatientCareContextsDemographicsWithZeroDobAndAgeFallback(): void
    {
        $_GET['patient_id'] = '19';
        $_REQUEST['patient_id'] = '19';

        $response = $this->gateway->hipPatientCareContexts();
        $body = json_decode($response->getBody(), true);

        $expectedYob = (int) date('Y') - 40;
        $this->assertEquals(1, $body['ok'] ?? 0);
        $this->assertEquals($expectedYob, $body['patient']['year_of_birth']);
        $this->assertEquals('12345678901234', $body['patient']['abha_number']);
        $this->assertEquals('ramesh@sbx', $body['patient']['abha_address']);
    }

    public function testHipPatientCareContextsDemographicsWithAbhaAddressInAbhaIdColumn(): void
    {
        $_GET['patient_id'] = '25';
        $_REQUEST['patient_id'] = '25';

        $response = $this->gateway->hipPatientCareContexts();
        $body = json_decode($response->getBody(), true);

        $expectedYob = (int) date('Y') - 25;
        $this->assertEquals(1, $body['ok'] ?? 0);
        $this->assertEquals($expectedYob, $body['patient']['year_of_birth']);
        $this->assertEquals('priyasharma@abdm', $body['patient']['abha_address']);
        $this->assertEquals('F', $body['patient']['gender']);
    }

    public function testLookupByAbhaAddressFindsPatientDemographics(): void
    {
        unset($_GET['patient_id'], $_REQUEST['patient_id']);
        $_GET['abha_address'] = 'singhkeshav301976@sbx';
        $_REQUEST['abha_address'] = 'singhkeshav301976@sbx';

        $response = $this->gateway->hipPatientCareContexts();
        $body = json_decode($response->getBody(), true);

        $this->assertEquals(1, $body['ok'] ?? 0);
        $this->assertEquals(12, $body['patient']['id']);
        $this->assertEquals(12, $body['patient']['patient_id']);
        $this->assertEquals(1976, $body['patient']['year_of_birth']);
        $this->assertEquals('Keshav Singh', $body['patient']['name']);
    }

    public function testResolvePatientBirthYearHelper(): void
    {
        $refMethod = new ReflectionMethod($this->gateway, 'resolvePatientBirthYear');
        $refMethod->setAccessible(true);

        // Case 1: Valid DOB
        $yob1 = $refMethod->invoke($this->gateway, ['dob' => '1985-08-20']);
        $this->assertEquals(1985, $yob1);

        // Case 2: '0000-00-00' with age
        $yob2 = $refMethod->invoke($this->gateway, ['dob' => '0000-00-00', 'age' => 35]);
        $this->assertEquals((int) date('Y') - 35, $yob2);

        // Case 3: Empty DOB with age
        $yob3 = $refMethod->invoke($this->gateway, ['dob' => '', 'age' => '50']);
        $this->assertEquals((int) date('Y') - 50, $yob3);

        // Case 4: No DOB, no age, but ABHA contains year
        $yob4 = $refMethod->invoke($this->gateway, [], 'user1992@sbx');
        $this->assertEquals(1992, $yob4);
    }

    public function testResolvePatientAbhaIdentityHelper(): void
    {
        $refMethod = new ReflectionMethod($this->gateway, 'resolvePatientAbhaIdentity');
        $refMethod->setAccessible(true);

        // Patient 12: has both address and 14-digit number
        $id12 = $refMethod->invoke($this->gateway, 12);
        $this->assertEquals('singhkeshav301976@sbx', $id12['abha_address']);
        $this->assertEquals('91747451787143', $id12['abha_id']);

        // Patient 25: abha_id contains address '@abdm'
        $id25 = $refMethod->invoke($this->gateway, 25);
        $this->assertEquals('priyasharma@abdm', $id25['abha_address']);
        $this->assertEquals('', $id25['abha_id']);
    }

    public function testAbdmTaskBoardSyncServiceDemographicsResolution(): void
    {
        $syncService = new AbdmTaskBoardSyncService();
        $refMethod = new ReflectionMethod($syncService, 'resolvePatientMasterDemographics');
        $refMethod->setAccessible(true);

        // Patient 12
        $demo12 = $refMethod->invoke($syncService, 12);
        $this->assertEquals('Keshav Singh', $demo12['name']);
        $this->assertEquals('M', $demo12['gender']);
        $this->assertEquals('1976', $demo12['year_of_birth']);
        $this->assertEquals('singhkeshav301976@sbx', $demo12['abha_address']);
        $this->assertEquals('91747451787143', $demo12['abha_number']);

        // Patient 19: age fallback
        $demo19 = $refMethod->invoke($syncService, 19);
        $this->assertEquals('Ramesh Kumar', $demo19['name']);
        $this->assertEquals((string) ((int) date('Y') - 40), $demo19['year_of_birth']);
        $this->assertEquals('12345678901234', $demo19['abha_number']);

        // Patient 25: abha_id with @
        $demo25 = $refMethod->invoke($syncService, 25);
        $this->assertEquals('Priya Sharma', $demo25['name']);
        $this->assertEquals('F', $demo25['gender']);
        $this->assertEquals((string) ((int) date('Y') - 25), $demo25['year_of_birth']);
        $this->assertEquals('priyasharma@abdm', $demo25['abha_address']);
    }

    public function testHipLinkTokenAutoDemographicsResolution(): void
    {
        // Call hipLinkToken with ONLY patient_id (no name, no gender, no yob, no abha_address)
        $_POST = [
            'patient_id' => 12,
        ];
        $req = \Config\Services::request();
        $this->gateway->initController($req, \Config\Services::response(), \Config\Services::logger());

        $lastPayload = [];
        $connectorStub = $this->getMockBuilder(EAtriaBridgeConnector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hipLinkToken'])
            ->getMock();

        $connectorStub->expects($this->once())
            ->method('hipLinkToken')
            ->willReturnCallback(function ($body) use (&$lastPayload) {
                $lastPayload = $body;
                return ['ok' => 1, 'link_token' => 'dummy-token-123'];
            });

        $refClass = new ReflectionClass($this->gateway);
        $connProp = $refClass->getProperty('connector');
        $connProp->setAccessible(true);
        $connProp->setValue($this->gateway, $connectorStub);

        $response = $this->gateway->hipLinkToken();
        $body = json_decode($response->getBody(), true);

        $this->assertEquals(1, $body['ok'] ?? 0, 'Expected hipLinkToken to succeed with auto-resolved demographics');
        $this->assertEquals('Keshav Singh', $lastPayload['name'] ?? '');
        $this->assertEquals('M', $lastPayload['gender'] ?? '');
        $this->assertEquals(1976, $lastPayload['year_of_birth'] ?? 0);
        $this->assertEquals('singhkeshav301976@sbx', $lastPayload['abha_address'] ?? '');
        $this->assertEquals('91747451787143', $lastPayload['abha_number'] ?? '');
    }

    public function testHipLinkCareContextAutoDemographicsResolution(): void
    {
        // Call hipLinkCareContext with ONLY patient_id, link_token_id, and care_contexts
        $_POST = [
            'patient_id'    => 12,
            'link_token_id' => 'dummy-token-123',
            'care_contexts' => [
                ['referenceNumber' => 'OPD-12-30', 'display' => 'OPD Consultation']
            ],
        ];
        $req = \Config\Services::request();
        $this->gateway->initController($req, \Config\Services::response(), \Config\Services::logger());

        $lastPayload = [];
        $connectorStub = $this->getMockBuilder(EAtriaBridgeConnector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hipLinkCareContext'])
            ->getMock();

        $connectorStub->expects($this->once())
            ->method('hipLinkCareContext')
            ->willReturnCallback(function ($body) use (&$lastPayload) {
                $lastPayload = $body;
                return ['ok' => 1, 'linked' => true];
            });

        $refClass = new ReflectionClass($this->gateway);
        $connProp = $refClass->getProperty('connector');
        $connProp->setAccessible(true);
        $connProp->setValue($this->gateway, $connectorStub);

        $response = $this->gateway->hipLinkCareContext();
        $body = json_decode($response->getBody(), true);

        $this->assertEquals(1, $body['ok'] ?? 0, 'Expected hipLinkCareContext to succeed with auto-resolved demographics');
        $this->assertEquals('singhkeshav301976@sbx', $lastPayload['abha_address'] ?? '');
        $this->assertEquals('P12', $lastPayload['patient_ref'] ?? '');
        $this->assertEquals('Keshav Singh', $lastPayload['display'] ?? '');
        $this->assertEquals('91747451787143', $lastPayload['abha_number'] ?? '');
    }
}
