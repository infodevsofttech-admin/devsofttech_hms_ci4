<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class IndianNameListSeeder extends Seeder
{
    public function run()
    {
        if (! $this->db->tableExists('name_list')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                ],
            ]);
            $forge->addKey('id', true);
            $forge->addKey('name');
            $forge->createTable('name_list', true);
        }

        $names = [
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

        $existingRows = $this->db->table('name_list')->select('name')->get()->getResultArray();
        $existing = [];
        foreach ($existingRows as $row) {
            $existing[strtolower(trim((string) ($row['name'] ?? '')))] = true;
        }

        $batch = [];
        foreach ($names as $name) {
            $key = strtolower($name);
            if (isset($existing[$key])) {
                continue;
            }

            $batch[] = ['name' => $name];
        }

        if (! empty($batch)) {
            $this->db->table('name_list')->insertBatch($batch);
        }
    }
}
