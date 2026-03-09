<?php

namespace App\Models;

use CodeIgniter\Model;

class PatientModel extends Model
{
    protected $table = 'patient_master';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public function insertPatient(array $data): int
    {
        $builder = $this->db->table($this->table);
        if (!$builder->insert($data)) {
            return 0;
        }

        $insertId = (int) $this->db->insertID();
        $pid = 1000000 + $insertId;
        $pid = 'P' . date('ym') . $pid;

        $builder->where('id', $insertId)->update(['p_code' => $pid]);

        return $insertId;
    }

    public function updatePatient(array $data, int $oldId): void
    {
        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $userId = $user->id ?? '';
        $updateEmpName = $userId . '[' . $userLabel . ']' . date('Y-m-d H:i:s');

        $existing = $this->db->table($this->table)
            ->where('id', $oldId)
            ->get()
            ->getRowArray();

        if ($existing) {
            $oldLog = $existing['log'] ?? '';
            $oldLog = $oldLog === '' ? ' ' : $oldLog;

            $changeData = $this->buildChangeLog($existing, $data);
            if ($changeData !== '') {
                $data['log'] = $oldLog . PHP_EOL . $changeData . 'Update By :' . $updateEmpName;
                $data['last_update'] = date('Y-m-d H:i:s');
            }
        }

        $this->db->table($this->table)
            ->where('id', $oldId)
            ->update($data);
    }

    public function updatePatientOnline(array $data, int $oldId): void
    {
        $userId = 0;
        $userLabel = 'online OPD';
        $updateEmpName = $userId . '[' . $userLabel . ']' . date('Y-m-d H:i:s');

        $existing = $this->db->table($this->table)
            ->where('id', $oldId)
            ->get()
            ->getRowArray();

        if ($existing) {
            $oldLog = $existing['log'] ?? '';
            $oldLog = $oldLog === '' ? ' ' : $oldLog;

            $changeData = $this->buildChangeLog($existing, $data);
            if ($changeData !== '') {
                $data['log'] = $oldLog . PHP_EOL . $changeData . 'Update By :' . $updateEmpName;
                $data['last_update'] = date('Y-m-d H:i:s');
            }
        }

        $this->db->table($this->table)
            ->where('id', $oldId)
            ->update($data);
    }

    public function insertCard(array $data): int
    {
        $builder = $this->db->table('hc_insurance_card');
        if (!$builder->insert($data)) {
            return 0;
        }

        return (int) $this->db->insertID();
    }

    public function insertDuplicateLog(array $data): int
    {
        $builder = $this->db->table('patient_duplicate_log');
        if (!$builder->insert($data)) {
            return 0;
        }

        return (int) $this->db->insertID();
    }

    public function updateCard(array $data, int $oldId): void
    {
        $this->db->table('hc_insurance_card')
            ->where('id', $oldId)
            ->update($data);
    }

    public function getCitySuggestions(string $q): array
    {
        $rows = $this->db->table('city_auto_u')
            ->like('city', $q)
            ->get()
            ->getResultArray();

        $rowSet = [];
        foreach ($rows as $row) {
            $rowSet[] = [
                'label' => trim($row['city']) . ' | ' . trim($row['district']) . ' | ' . trim($row['state']),
                'value' => trim($row['city']),
                'l_city' => trim($row['city']),
                'l_district' => trim($row['district']),
                'l_state' => trim($row['state']),
            ];
        }

        return $rowSet;
    }

    public function getNameSuggestions(string $q): array
    {
        $term = strtolower(trim($q));
        if ($term === '') {
            return [];
        }

        $rows = [];
        if ($this->db->tableExists('name_list')) {
            try {
                $rows = $this->db->table('name_list')
                    ->select('name')
                    ->like('name', $term, 'after')
                    ->orderBy('name', 'ASC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();
            } catch (\Throwable $e) {
                $rows = [];
            }
        }

        $rowSet = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $rowSet[] = [
                'label' => $name,
                'value' => $name,
            ];
        }

        if (!empty($rowSet)) {
            return $rowSet;
        }

        $fallback = [
            'Aarav', 'Aarush', 'Aditi', 'Akanksha', 'Akash', 'Akhil', 'Alok', 'Aman', 'Amar', 'Amit',
            'Amrita', 'Anand', 'Ananya', 'Anil', 'Anjali', 'Ankit', 'Ankur', 'Ansh', 'Anshika', 'Anuj',
            'Arjun', 'Arnav', 'Arun', 'Aryan', 'Asha', 'Ashish', 'Ayush', 'Bhavna', 'Bhavesh', 'Bhavya',
            'Chandni', 'Charu', 'Chetan', 'Darpan', 'Deepa', 'Deepak', 'Dev', 'Devansh', 'Devesh', 'Dhruv',
            'Diksha', 'Divya', 'Gaurav', 'Geeta', 'Gopal', 'Govind', 'Harish', 'Harsh', 'Harshita', 'Himanshu',
            'Isha', 'Ishaan', 'Jatin', 'Jaya', 'Jyoti', 'Kabir', 'Kajal', 'Karan', 'Kartik', 'Kavita',
            'Khushi', 'Kiran', 'Komal', 'Krishna', 'Kunal', 'Kusum', 'Lakshmi', 'Lokesh', 'Madhav', 'Madhuri',
            'Mahesh', 'Manav', 'Manish', 'Meera', 'Megha', 'Mohan', 'Mohit', 'Mukesh', 'Nandini', 'Naveen',
            'Neeraj', 'Neha', 'Nikhil', 'Nilesh', 'Niraj', 'Nisha', 'Nitin', 'Pankaj', 'Pooja', 'Pradeep',
            'Prakash', 'Pranav', 'Prashant', 'Pratik', 'Preeti', 'Priya', 'Rahul', 'Raj', 'Rajat', 'Rajesh',
            'Rakesh', 'Rani', 'Ravi', 'Rekha', 'Ritika', 'Rohan', 'Rohit', 'Sahil', 'Sakshi', 'Sameer',
            'Sandeep', 'Sanjay', 'Sanjana', 'Sarthak', 'Satish', 'Seema', 'Shalini', 'Shankar', 'Shivam', 'Shreya',
            'Shruti', 'Simran', 'Sonali', 'Sourabh', 'Suhani', 'Suman', 'Sunil', 'Suraj', 'Suresh', 'Swati',
            'Tanvi', 'Tarun', 'Uday', 'Vaibhav', 'Varun', 'Vikas', 'Vinay', 'Vineet', 'Vishal', 'Vivek',
            'Yash', 'Yashika', 'Yogesh', 'Zoya',
            'Aggarwal', 'Agarwal', 'Arora', 'Bansal', 'Bhat', 'Bhatt', 'Bhardwaj', 'Chauhan', 'Chawla', 'Das',
            'Dubey', 'Dwivedi', 'Garg', 'Goswami', 'Gupta', 'Jain', 'Joshi', 'Kapoor', 'Khanna', 'Khan',
            'Kumar', 'Mishra', 'Nair', 'Pandey', 'Patel', 'Rao', 'Rawat', 'Saha', 'Saxena', 'Shah',
            'Sharma', 'Shukla', 'Singh', 'Srivastava', 'Thakur', 'Tripathi', 'Tyagi', 'Taygi', 'Varma', 'Verma',
            'Yadav'
        ];

        $result = [];
        foreach ($fallback as $name) {
            if (stripos($name, $term) !== 0) {
                continue;
            }

            $result[] = [
                'label' => $name,
                'value' => $name,
            ];

            if (count($result) >= 20) {
                break;
            }
        }

        return $result;
    }

    public function insertRemark(array $data): int
    {
        $builder = $this->db->table('patient_remark');
        if (!$builder->insert($data)) {
            return 0;
        }

        return (int) $this->db->insertID();
    }

    private function buildChangeLog(array $old, array $new): string
    {
        $lines = [];
        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $old)) {
                continue;
            }

            $oldValue = (string) $old[$key];
            $newValue = (string) $value;
            if ($oldValue === $newValue) {
                continue;
            }

            $lines[] = $key . ': ' . $oldValue . ' => ' . $newValue;
        }

        return $lines ? implode(PHP_EOL, $lines) . PHP_EOL : '';
    }
}
