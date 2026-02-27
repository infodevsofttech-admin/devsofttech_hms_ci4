<?php
class FileMedical_M extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }

    public function insert_purchase_scan($data)
    {
        if ($this->db->insert("purchase_invoice_scan_file", $data)) {
            $insert_id = $this->db->insert_id();

            return $insert_id;
        } else {
            echo $this->db->_error_message();
            return  0;
        }
    }

    public function update_purchase_scan($data, $old_id_no)
    {
        $this->db->set($data);
        $this->db->where("id", $old_id_no);
        $this->db->update("purchase_invoice_scan_file", $data);
    }

    function get_purchase_scan_filedetail($id)
    {
        return $this->db->get_where('purchase_invoice_scan_file', array('id' => $id))->row_array();
    }

    function get_filedetail_file_url($id)
    {
        if ($id == '' || $id == 0) {
            $file_url = "No File";
        } else {
            $file_url = $this->db->get_where('purchase_invoice_scan_file', array('id' => $id))->row()->full_path;
        }

        return $file_url;
    }

    function delete_purchase_scan_fileupload($id)
    {
        $this->db->where('id', $id);
        $q = $this->db->get('purchase_invoice_scan_file');
        // if id is unique, we want to return just one row
        $data = $q->row_array();

        $user_name = $this->session->userdata('username') . '[' . $this->session->userdata('agent_id') . ']' . date('d-m-Y H:i:s');
        $data['delete_by'] = $user_name;

        if ($this->db->insert("purchase_invoice_scan_file_delete", $data)) {
            return $this->db->delete('purchase_invoice_scan_file', array('id' => $id));
        } else {
            return 0;
        }
    }
}
