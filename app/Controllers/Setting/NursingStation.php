<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\NurseModel;
use App\Models\NursingStationModel;

class NursingStation extends BaseController
{
    protected NursingStationModel $stationModel;
    protected NurseModel $nurseModel;

    public function __construct()
    {
        $this->stationModel = new NursingStationModel();
        $this->nurseModel = new NurseModel();
    }

    public function index()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $stations = $this->stationModel->getStations($q);
        $nurses = $this->nurseModel->getActiveNurses();

        return view('Setting/NursingStation/nursing_station_index', [
            'stations' => $stations,
            'nurses' => $nurses,
            'searchQuery' => $q,
        ]);
    }

    public function save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid request']);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $stationCode = trim((string) ($this->request->getPost('station_code') ?? ''));
        $stationName = trim((string) ($this->request->getPost('station_name') ?? ''));
        $floorNumber = trim((string) ($this->request->getPost('floor_number') ?? ''));
        $inchargeNurseId = (int) ($this->request->getPost('incharge_nurse_id') ?? 0);
        $contactNo = trim((string) ($this->request->getPost('contact_no') ?? ''));
        $status = trim((string) ($this->request->getPost('status') ?? 'active'));
        $remarks = trim((string) ($this->request->getPost('remarks') ?? ''));

        if ($stationName === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Station Name is required.']);
        }

        if ($stationCode === '') {
            $stationCode = 'NS-' . str_pad((string) (rand(100, 999)), 3, '0', STR_PAD_LEFT);
        }

        $inchargeNurseName = '';
        if ($inchargeNurseId > 0) {
            $nurseRow = $this->nurseModel->getNurseById($inchargeNurseId);
            if ($nurseRow) {
                $inchargeNurseName = trim(($nurseRow['nurse_code'] ? '[' . $nurseRow['nurse_code'] . '] ' : '') . $nurseRow['name']);
            }
        }

        $payload = [
            'station_code' => $stationCode,
            'station_name' => $stationName,
            'floor_number' => $floorNumber,
            'incharge_nurse_id' => $inchargeNurseId,
            'incharge_nurse_name' => $inchargeNurseName,
            'contact_no' => $contactNo,
            'status' => $status,
            'remarks' => $remarks,
        ];

        if ($id > 0) {
            $ok = $this->stationModel->updateStation($id, $payload);
            return $this->response->setJSON(['ok' => $ok, 'error' => $ok ? '' : 'Failed to update nursing station']);
        }

        $newId = $this->stationModel->insertStation($payload);
        return $this->response->setJSON(['ok' => $newId > 0, 'id' => $newId, 'error' => $newId > 0 ? '' : 'Failed to insert nursing station']);
    }

    public function delete()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid request']);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Invalid Station ID']);
        }

        $ok = $this->stationModel->deleteStation($id);
        return $this->response->setJSON(['ok' => $ok, 'error' => $ok ? '' : 'Failed to delete station']);
    }

    public function list_json()
    {
        $stations = $this->stationModel->getActiveStations();
        return $this->response->setJSON(['ok' => true, 'stations' => $stations]);
    }
}
