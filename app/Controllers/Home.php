<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserModel;

class Home extends BaseController
{
    private function ensureAuthenticated()
    {
        if (function_exists('auth') && auth()->loggedIn()) {
            return null;
        }

        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(401)->setBody('Unauthorized');
        }

        return redirect()->to(base_url('login'));
    }

    public function index()
    {
        $authRedirect = $this->ensureAuthenticated();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        return view('welcome_message', [
            'initial_route' => base_url('dashboard'),
            'initial_title' => 'Dashboard',
        ]);
    }

    public function billing()
    {
        $authRedirect = $this->ensureAuthenticated();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        $user = service('auth')->user();

        return view('billing/main', ['user' => $user]);
    }

    public function dashboard()
    {
        $authRedirect = $this->ensureAuthenticated();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        if (! $this->request->isAJAX()) {
            return view('welcome_message', [
                'initial_route' => base_url('dashboard'),
                'initial_title' => 'Dashboard',
            ]);
        }

        $user = service('auth')->user();
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $opdToday = $this->db->table('opd_master')
            ->where('apointment_date', $today)
            ->countAllResults();

        $opdLast7Days = $this->db->table('opd_master')
            ->where('apointment_date >=', date('Y-m-d', strtotime('-7 days')))
            ->where('apointment_date <=', $today)
            ->countAllResults();

        $admitToday = $this->db->table('ipd_master')
            ->where('register_date', $today)
            ->countAllResults();

        $dischargeToday = $this->db->table('ipd_master')
            ->where('discharge_date', $today)
            ->where('ipd_status', 1)
            ->countAllResults();

        $currentIpd = $this->db->table('ipd_master')
            ->where('ipd_status', 0)
            ->countAllResults();

        $currentOrgIpd = $this->db->table('ipd_master i')
            ->join('organization_case_master o', 'o.id = i.case_id', 'inner')
            ->where('o.case_type', 1)
            ->where('i.ipd_status', 0)
            ->countAllResults();

        $opdOrgList = $this->db->table('opd_master o')
            ->select('ins.short_name as org_name')
            ->select('COUNT(o.opd_id) as total_cases', false)
            ->join('hc_insurance ins', 'ins.id = o.insurance_id', 'left')
            ->where('o.apointment_date >=', $monthStart)
            ->where('o.apointment_date <=', $today)
            ->groupBy('ins.short_name')
            ->orderBy('total_cases', 'DESC')
            ->limit(10)
            ->get()
            ->getResult();

        $opdDoctorList = $this->db->table('opd_master o')
            ->select('o.doc_name')
            ->select('COUNT(o.opd_id) as total_cases', false)
            ->select("SUM(CASE WHEN (o.insurance_id IS NULL OR o.insurance_id <= 1) THEN 1 ELSE 0 END) as direct_cases", false)
            ->select("SUM(CASE WHEN (o.insurance_id IS NULL OR o.insurance_id <= 1) THEN 0 ELSE 1 END) as org_cases", false)
            ->where('o.apointment_date', $today)
            ->where('o.doc_name IS NOT NULL', null, false)
            ->where("o.doc_name != ''", null, false)
            ->groupBy('o.doc_name')
            ->orderBy('total_cases', 'DESC')
            ->limit(12)
            ->get()
            ->getResult();

        $ipdDoctorList = $this->db->table('ipd_master_doc_list l')
            ->select('d.p_fname as doc_name')
            ->select('COUNT(DISTINCT l.ipd_id) as total_cases', false)
            ->join('doctor_master d', 'd.id = l.doc_id', 'inner')
            ->join('ipd_master i', 'i.id = l.ipd_id', 'inner')
            ->where('i.ipd_status', 0)
            ->groupBy('d.p_fname')
            ->orderBy('total_cases', 'DESC')
            ->limit(12)
            ->get()
            ->getResult();

        $ipdOrgList = $this->db->table('organization_case_master o')
            ->select('ins.short_name as org_name')
            ->select('COUNT(o.id) as total_cases', false)
            ->join('ipd_master i', 'o.id = i.case_id', 'inner')
            ->join('hc_insurance ins', 'ins.id = o.insurance_id', 'left')
            ->where('o.case_type', 1)
            ->where('i.ipd_status', 0)
            ->groupBy('ins.short_name')
            ->orderBy('total_cases', 'DESC')
            ->limit(10)
            ->get()
            ->getResult();

        $trendStart = $monthStart; // First day of current month
        $trendEnd = $today;
        $trendDays = [];
        
        // Generate all days from start of month to today
        $startTime = strtotime($monthStart);
        $endTime = strtotime($today);
        $currentTime = $startTime;
        
        while ($currentTime <= $endTime) {
            $trendDays[] = date('Y-m-d', $currentTime);
            $currentTime = strtotime('+1 day', $currentTime);
        }

        $opdTrendRaw = $this->db->table('opd_master')
            ->select("DATE_FORMAT(apointment_date, '%Y-%m-%d') as yd", false)
            ->select('COUNT(opd_id) as total', false)
            ->where('apointment_date >=', $trendStart)
            ->where('apointment_date <=', $trendEnd)
            ->groupBy('yd')
            ->get()
            ->getResult();

        $ipdTrendRaw = $this->db->table('ipd_master')
            ->select("DATE_FORMAT(register_date, '%Y-%m-%d') as yd", false)
            ->select('COUNT(id) as total', false)
            ->where('register_date >=', $trendStart)
            ->where('register_date <=', $trendEnd)
            ->groupBy('yd')
            ->get()
            ->getResult();

        $opdTrendMap = [];
        foreach ($opdTrendRaw as $row) {
            $opdTrendMap[$row->yd] = (int) $row->total;
        }
        $ipdTrendMap = [];
        foreach ($ipdTrendRaw as $row) {
            $ipdTrendMap[$row->yd] = (int) $row->total;
        }

        $opdTrend = [];
        $ipdTrend = [];
        $trendLabels = [];
        foreach ($trendDays as $dayKey) {
            $trendLabels[] = date('d M', strtotime($dayKey));
            $opdTrend[] = $opdTrendMap[$dayKey] ?? 0;
            $ipdTrend[] = $ipdTrendMap[$dayKey] ?? 0;
        }

        $data = [
            'user' => $user,
            'opd_today' => $opdToday,
            'opd_last_7_days' => $opdLast7Days,
            'admit_today' => $admitToday,
            'discharge_today' => $dischargeToday,
            'current_ipd' => $currentIpd,
            'current_org_ipd' => $currentOrgIpd,
            'opd_org_list' => $opdOrgList,
            'opd_doctor_list' => $opdDoctorList,
            'ipd_doctor_list' => $ipdDoctorList,
            'ipd_org_list' => $ipdOrgList,
            'trend_labels' => $trendLabels,
            'trend_opd' => $opdTrend,
            'trend_ipd' => $ipdTrend,
            'month_start' => $monthStart,
        ];

        return view('dashboard/index', $data);
    }

    public function myProfile()
    {
        $authRedirect = $this->ensureAuthenticated();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        if (! $this->request->isAJAX()) {
            return view('welcome_message', [
                'initial_route' => base_url('my-profile'),
                'initial_title' => 'My Profile',
            ]);
        }

        $user = service('auth')->user();
        $meta = $this->getCurrentUserProfileMeta((int) ($user->id ?? 0));

        return view('account/my_profile', [
            'user' => $user,
            'phone_no' => $meta['phone_no'] ?? '',
            'person_name' => $meta['full_name'] ?? '',
        ]);
    }

    public function speechTest()
    {
        $authRedirect = $this->ensureAuthenticated();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        return view('tools/speech_test');
    }

    public function myProfileSave()
    {
        $authRedirect = $this->ensureAuthenticated();
        if ($authRedirect !== null) {
            return $authRedirect;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'update' => 0,
                'error_text' => 'Invalid request',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $authUser = service('auth')->user();
        $userId = (int) ($authUser->id ?? 0);
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'update' => 0,
                'error_text' => 'Unauthorized',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $email = trim((string) $this->request->getPost('email'));
        $personName = trim((string) $this->request->getPost('person_name'));
        $phoneNo = trim((string) $this->request->getPost('phone_no'));
        $password = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        if ($personName !== '' && mb_strlen($personName) > 120) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Person name must be maximum 120 characters.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Valid email is required.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($phoneNo !== '' && preg_match('/^[0-9+\-()\s]{7,20}$/', $phoneNo) !== 1) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Phone format is invalid.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($password !== '' || $passwordConfirm !== '') {
            if (strlen($password) < 8) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Password must be at least 8 characters.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            if (! hash_equals($password, $passwordConfirm)) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Password and confirm password do not match.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }
        }

        $tables = config('Auth')->tables;
        $identitiesTable = (string) ($tables['identities'] ?? 'auth_identities');

        $duplicateEmail = $this->db->table($identitiesTable)
            ->select('id')
            ->where('type', 'email_password')
            ->where('secret', $email)
            ->where('user_id !=', $userId)
            ->get(1)
            ->getRowArray();


        if (! empty($duplicateEmail)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Email already used by another user.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $userModel = model(UserModel::class);
        $user = $userModel->find($userId);
        if ($user === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'update' => 0,
                'error_text' => 'User not found',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $user->email = $email;
        if ($password !== '') {
            $user->password = $password;
        }

        if (! $userModel->save($user)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Unable to update profile.',
                'errors' => $userModel->errors(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $this->setCurrentUserProfileMeta($userId, $phoneNo, $personName);

        $displayName = trim((string) ($user->username ?? ''));
        if ($personName !== '') {
            $displayName = $personName;
        } elseif ($displayName === '') {
            $displayName = trim((string) ($email !== '' ? $email : 'User'));
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Profile updated successfully.',
            'display_name' => $displayName,
            'person_name' => $personName,
            'login_id' => trim((string) ($user->username ?? '')),
            'user_id' => $userId,
            'email' => $email,
            'phone_no' => $phoneNo,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * @return array{phone_no:string, full_name:string}
     */
    private function getCurrentUserProfileMeta(int $userId): array
    {
        if ($userId <= 0) {
            return ['phone_no' => '', 'full_name' => ''];
        }

        $tables = config('Auth')->tables;
        $identitiesTable = (string) ($tables['identities'] ?? 'auth_identities');
        if (! $this->db->tableExists($identitiesTable)) {
            return ['phone_no' => '', 'full_name' => ''];
        }

        $row = $this->db->table($identitiesTable)
            ->select('extra')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->get(1)
            ->getRowArray();

        $extraRaw = trim((string) ($row['extra'] ?? ''));
        if ($extraRaw === '') {
            return ['phone_no' => '', 'full_name' => ''];
        }

        $decoded = json_decode($extraRaw, true);
        if (! is_array($decoded)) {
            return ['phone_no' => '', 'full_name' => ''];
        }

        return [
            'phone_no' => trim((string) ($decoded['phone_no'] ?? '')),
            'full_name' => trim((string) ($decoded['full_name'] ?? '')),
        ];
    }

    private function setCurrentUserProfileMeta(int $userId, string $phoneNo, string $fullName): void
    {
        if ($userId <= 0) {
            return;
        }

        $tables = config('Auth')->tables;
        $identitiesTable = (string) ($tables['identities'] ?? 'auth_identities');
        if (! $this->db->tableExists($identitiesTable)) {
            return;
        }

        $row = $this->db->table($identitiesTable)
            ->select('id,extra')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->get(1)
            ->getRowArray();

        if (empty($row) || empty($row['id'])) {
            return;
        }

        $payload = [];
        $raw = trim((string) ($row['extra'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if ($phoneNo === '') {
            unset($payload['phone_no']);
        } else {
            $payload['phone_no'] = $phoneNo;
        }

        if ($fullName === '') {
            unset($payload['full_name']);
        } else {
            $payload['full_name'] = $fullName;
        }

        $this->db->table($identitiesTable)
            ->where('id', (int) $row['id'])
            ->update([
                'extra' => empty($payload) ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
    }
}
