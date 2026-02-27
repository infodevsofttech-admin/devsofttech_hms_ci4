<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class PaymentModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function insertPayment(array $data): int
    {
        $this->db->table('payment_history')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updatePayment(array $data, int $id): bool
    {
        return (bool) $this->db->table('payment_history')->where('id', $id)->update($data);
    }
}
