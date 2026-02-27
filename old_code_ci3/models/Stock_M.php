<?php
class Stock_M extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    public function insert_indent($data)
    {

        if ($this->db->insert("invoice_stock_master", $data)) {
            $insert_id = $this->db->insert_id();
            $pid = str_pad(substr($insert_id, -7, 7), 7, 0, STR_PAD_LEFT);

            $pid = 'S' . date('ym') . $pid;

            $data1 = array(
                'indent_code' => $pid
            );

            $this->db->set($data1);
            $this->db->where("id", $insert_id);
            $this->db->update("invoice_stock_master", $data1);
            return $insert_id;
        } else
            return 0;
    }


    public function add_indentitem($data)
    {
        if ($this->db->insert("inv_stock_item", $data)) {
            $insert_id = $this->db->insert_id();
            $store_stock_id = $data['store_stock_id'];
            $this->db->query("CALL p_stock_update_purchase_id_store(" . $store_stock_id . ")");
            $this->db->query("CALL p_update_med_GST_store(" . $data['indent_id'] . ")");
            return $insert_id;
        } else
            return 0;
    }

    public function update_indentitem($data, $old_id_no)
    {
        $this->db->set($data);
        $this->db->where("id", $old_id_no);

        if ($this->db->update("inv_stock_item", $data)) {
            $store_stock_id = $data['store_stock_id'];
            $this->db->query("CALL p_stock_update_purchase_id_store(" . $store_stock_id . ")");
            $this->db->query("CALL p_update_med_GST_store(" . $data['indent_id'] . ")");
            return 1;
        } else {
            return 0;
        }
    }

    public function delete_indentitem($itemid, $inv_items)
    {
        $this->db->delete("inv_stock_item", "id=" . $itemid);

        $update_emp_name = $this->session->userdata('username') . '[' . $this->session->userdata('agent_id') . ']' . date('Y-m-d H:i:s');

        $add_data = array(
            'delete_by' => $update_emp_name,
        );

        $data = array_merge($inv_items, $add_data);

        $store_stock_id = $data['store_stock_id'];

        //echo  "Purchase Id :".$store_stock_id;

        $this->db->insert("inv_stock_item_delete", $data);
        $this->db->query("CALL p_update_med_GST_store(" . $data['indent_id'] . ")");

        $this->db->query("CALL p_stock_update_purchase_id_store(" . $store_stock_id . ")");
    }

    public function update_invoice_final($inv_id)
    {
        $this->db->query("CALL p_update_med_GST_store(" . $inv_id . ")");
    }



    //Purchase Table
    public function insert_purchase($data)
    {

        $this->db->db_debug = false;

        if ($this->db->insert("purchase_invoice_stock", $data)) {
            $insert_id = $this->db->insert_id();
            return $insert_id;
        } else
            return 0;
    }

    public function update_purchase($data, $old_id_no)
    {
        $this->db->set($data);
        $this->db->where("id", $old_id_no);
        if ($this->db->update("purchase_invoice_stock", $data)) {
            //$this->db->query("CALL p_purchase_invoice(".$old_id_no.")");
            return 1;
        } else {
            return 0;
        }
    }

    public function delete_purchase($inv_id)
    {

        $this->db->where("id", $inv_id);
        $this->db->delete("purchase_invoice_stock");

        return 1;
    }

    //Purchase Item

    public function insert_purchase_item($data)
    {
        if ($this->db->insert("purchase_invoice_item_stock", $data)) {
            $insert_id = $this->db->insert_id();
            $this->db->query("CALL f_update_purchase_invoice_stock(" . $insert_id . ")");
            return $insert_id;
        } else
            return 0;
    }

    public function update_purchase_item($data, $old_id_no)
    {
        $this->db->set($data);
        $this->db->where("id", $old_id_no);
        if ($this->db->update("purchase_invoice_item_stock", $data)) {
            $this->db->query("CALL f_update_purchase_invoice_stock(" . $old_id_no . ")");
            return 1;
        } else {
            return 0;
        }
    }

    public function delete_purchase_item($inv_id, $del_inv_item_id)
    {

        $this->db->where("id", $del_inv_item_id);
        if ($this->db->delete("purchase_invoice_item_stock")) {

            $this->db->query("CALL p_purchase_invoice_stock(" . $inv_id . ")");

            return 1;
        } else {
            return 0;
        }
    }

    public function insert_drug_add($data)
    {
        if ($this->db->insert("drug_temp", $data)) {
            $insert_id = $this->db->insert_id();
            return $insert_id;
        } else
            return 0;
    }

    public function update_drug($data, $old_id_no)
    {

        $this->db->set($data);
        $this->db->where("item_id", $old_id_no);

        if ($this->db->update("drug_temp", $data)) {
            return 1;
        } else {
            return 0;
        }
    }

    // Store Supplier

    public function insert_supplier($data)
    {

        if ($this->db->insert("stock_supplier", $data)) {
            $insert_id = $this->db->insert_id();
            return $insert_id;
        } else
            return 0;
    }

    public function update_supplier($data, $old_id_no)
    {
        $this->db->set($data);
        $this->db->where("sid", $old_id_no);
        if ($this->db->update("stock_supplier", $data)) {
            return $old_id_no;
        } else {
            return 0;
        }
    }
}
