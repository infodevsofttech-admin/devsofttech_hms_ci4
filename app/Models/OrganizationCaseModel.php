<?php

namespace App\Models;

use CodeIgniter\Model;

class OrganizationCaseModel extends Model
{
    protected $table = 'organization_case_master';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    public function insertCase(array $data): int
    {
        if (! $this->db->table($this->table)->insert($data)) {
            return 0;
        }

        $insertId = (int) $this->db->insertID();
        $pid = str_pad(substr((string) $insertId, -7, 7), 7, '0', STR_PAD_LEFT);
        $caseCode = 'C' . date('ym') . $pid;

        $this->db->table($this->table)
            ->where('id', $insertId)
            ->update(['case_id_code' => $caseCode]);

        return $insertId;
    }

    public function updateCase(array $data, int $id): bool
    {
        return (bool) $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }

    public function getInvoiceRowsAndTotal(int $caseId): array
    {
        $sql1 = "select c.id AS id,c.case_id_code AS case_id_code,c.p_id AS p_id,
            o.apointment_date AS s_Date,'OPD' AS Charge_type,0 AS Charge_type_id,
            o.opd_id AS item_id,0 AS master_item_id,o.apointment_date AS Adate,
            o.opd_fee_amount AS item_rate,1 AS item_qty,
            date_format(o.apointment_date,'%d-%m-%Y') AS str_date,
            concat('OPD Charge: Dr. ',o.doc_name) AS Description,
            o.opd_code AS Code,o.opd_fee_amount AS Amount,'1' AS orgcode
            from opd_master o
            join organization_case_master c on o.insurance_case_id = c.id
            where o.opd_status in (1,2) and c.id=" . $caseId .
            " order by Adate";
        $showinvoice1 = $this->db->query($sql1)->getResult();

        $sql2 = "select c.id AS id,c.case_id_code AS case_id_code,c.p_id AS p_id,
            i.inv_date AS s_Date,l.group_desc AS Charge_type,l.itype_id AS Charge_type_id,
            t.id AS item_id,t.item_id AS master_item_id,i.inv_date AS Adate,
            t.item_rate AS item_rate,t.item_qty AS item_qty,
            date_format(i.inv_date,'%d-%m-%Y') AS str_date,
            concat(t.item_name) AS Description,i.invoice_code AS Code,
            t.item_amount AS Amount,t.org_code AS orgcode
            from invoice_master i
            join organization_case_master c on i.insurance_case_id = c.id
            join invoice_item t on t.inv_master_id = i.id
            join hc_item_type l on t.item_type = l.itype_id
            left join hc_items_insurance it on t.item_id = it.hc_items_id and i.insurance_id = it.hc_insurance_id
            where i.ipd_include = 1 and i.invoice_status = 1 and c.id=" . $caseId .
            " order by Adate";
        $showinvoice2 = $this->db->query($sql2)->getResult();

        $rows = array_merge($showinvoice1, $showinvoice2);
        usort($rows, static function ($a, $b): int {
            $left = strtotime((string) ($a->s_Date ?? $a->Adate ?? ''));
            $right = strtotime((string) ($b->s_Date ?? $b->Adate ?? ''));

            return $left <=> $right;
        });

        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row->Amount ?? 0);
        }

        return [
            'rows' => $rows,
            'total' => $total,
        ];
    }
}
