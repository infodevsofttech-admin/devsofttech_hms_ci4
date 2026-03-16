<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Medical_backpanel extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$this->load->view('Medical/Med_back_Panel');
	}

	public function Med_old_invoice()
	{
		$this->load->view('Medical/Invoice_med_list');
	}

	public function Stock()
	{
		$sql = "select * from med_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data'] = $query->result();

		$this->load->view('Medical/Stock/stock_search', $data);
	}

	public function store_stock()
	{
		$sql = "select * from med_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data'] = $query->result();

		$sql = "select * from med_formulation order by formulation_length";
		$query = $this->db->query($sql);
		$data['med_formulation'] = $query->result();

		$this->load->view('Medical/Store/store_stock_search', $data);
	}

	public function stock_details()
	{
		$sql = "select * from med_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data'] = $query->result();

		$this->load->view('Medical/Store/stock_store_statement', $data);
	}

	public function store_Stock_result()
	{
		$item_name = trim($this->input->post('txtsearch'));
		$chk_reorder = $this->input->post('chk_reorder');

		$HAVING = "";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING SUM(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)<=p.re_order_qty";
		}

		$where = " (s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)>=0  ";

		if ($item_name <> '') {
			$where .= " and (p.item_name like '%" . $item_name . "%' or p.genericname like '%" . $item_name . "%' )";
		}

		$schedule_id = $this->input->post('schedule_id');

		if ($schedule_id == '') {
		} else {
			$where .= " and (1<>1 ";
			foreach ($schedule_id as $row) {
				if ($row == 1) {
					$where .= " or p.schedule_h=1 ";
				}

				if ($row == 2) {
					$where .= " or p.schedule_h1=1 ";
				}

				if ($row == 3) {
					$where .= " or p.schedule_x=1 ";
				}

				if ($row == 4) {
					$where .= " or p.schedule_g=1 ";
				}

				if ($row == 5) {
					$where .= " or p.narcotic=1 ";
				}

				if ($row == 6) {
					$where .= " or p.high_risk=1 ";
				}
			}
			$where .= ")";
		}

		$where .= " and m.date_of_invoice>date_add(curdate(),interval -24 month) ";

		$sql = "SELECT p.id, p.item_name,p.genericname,
				IFNULL(s.item_code,0) AS item_found,
				ifnull(s.packing,p.packing) as packing,
				SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) as C_Unit_Stock_Qty,
				Concat(TRUNCATE(SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit)/s.packing,0),
				':',
				MOD(SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit),s.packing)) as C_Pak_Qty,
				sum(s.total_unit) as P_Unit_Qty,
				sum(s.total_unit)/s.packing as Pak_qty,
				sum(s.total_sale_unit) as sale_unit,
				sum(s.total_sale_unit)/s.packing as C_Pak_Sale_Qty,
				p.re_order_qty,
				s.total_lost_unit		
				FROM (med_product_master p 
				JOIN purchase_invoice_item s ON p.id=s.item_code  AND s.remove_item=0 AND s.item_return=0)
				JOIN purchase_invoice m ON s.purchase_id=m.id and p.is_continue=1
			WHERE " . $where . " group by p.id,s.packing " . $HAVING . " order by p.Item_name ";
		$query = $this->db->query($sql);
		$data['stock_list'] = $query->result();


		$this->load->view('Medical/Store/Stock_search_result', $data);
	}

	public function store_Stock_result_datewise()
	{
		$item_name = trim($this->input->post('txtsearch'));
		$chk_reorder = $this->input->post('chk_reorder');

		$supplier_id = $this->input->post('input_supplier');
		$opd_date_range = $this->input->post('opd_date_range');

		$rangeArray = explode("S", $opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$HAVING = "";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING SUM(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)<=p.re_order_qty";
		}

		$where = " 1=1  ";

		if ($item_name <> '') {
			$where .= " and p.item_name like '%" . $item_name . "%' ";
		}

		if ($opd_date_range <> '') {
			$where .= " and s.stock_date between  '" . $minRange . "' and '" . $maxRange . "'";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		$where .= " and m.date_of_invoice>date_add(curdate(),interval -24 month) ";

		$sql = "SELECT  p.id, p.item_name, IFNULL(s.item_code,0) AS item_found, 
				ifnull(s.packing,p.packing) as packing,
				SUM(s.tqty) AS  total_pak_qty,
				SUM(s.net_amount) AS purchase_cost,
				SUM(s.total_unit) AS  total_unit,
				SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) as C_Unit_Stock_Qty, 
				SUM((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit))/s.packing as C_Pak_Qty, 
				sum(s.total_unit) as P_Unit_Qty, sum(s.total_unit)/s.packing as Pak_qty, 
				sum(s.total_sale_unit) as sale_unit, sum(s.total_sale_unit)/s.packing as C_Pak_Sale_Qty, 
				p.re_order_qty, s.total_lost_unit 		
				FROM (med_product_master p JOIN purchase_invoice_item s ON p.id=s.item_code and s.item_return=0)
 				JOIN purchase_invoice m ON s.purchase_id=m.id 
			WHERE " . $where . " group by p.id,s.packing " . $HAVING . " order by p.Item_name";
		$query = $this->db->query($sql);
		$data['stock_list'] = $query->result();

		$data['date_range'] = $opd_date_range;
		$data['supplier_id'] = $supplier_id;

		$this->load->view('Medical/Store/stock_store_statement_data', $data);
	}

	public function store_Stock_result_datewise_excel($opd_date_range, $item_name='-', $supplier_id = '')
	{
		
		$rangeArray = explode("S", $opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
	

		$where = " 1=1  ";

		if ($item_name <> '') {
			$where .= " and p.item_name like '%" . $item_name . "%' ";
		}

		if ($opd_date_range <> '') {
			$where .= " and s.stock_date between  '" . $minRange . "' and '" . $maxRange . "'";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		$where .= " and m.date_of_invoice>date_add(curdate(),interval -24 month) ";

		$sql = "SELECT  p.*, IFNULL(s.item_code,0) AS item_found, 
				ifnull(s.packing,p.packing) as packing,
				SUM(s.tqty) AS  total_pak_qty,
				SUM(s.net_amount) AS purchase_cost,
				SUM(s.total_unit) AS  total_unit,
				SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) as C_Unit_Stock_Qty, 
				SUM((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit))/s.packing as C_Pak_Qty, 
				sum(s.total_unit) as P_Unit_Qty, sum(s.total_unit)/s.packing as Pak_qty, 
				sum(s.total_sale_unit) as sale_unit, sum(s.total_sale_unit)/s.packing as C_Pak_Sale_Qty, 
				s.total_lost_unit,s.expiry_date,
				SUM(TRUNCATE((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit),0)*s.purchase_unit_rate) AS Stock_Cost,
				s.purchase_unit_rate ,s.mrp,s.batch_no,s.CGST_per
				FROM (med_product_master p JOIN purchase_invoice_item s ON p.id=s.item_code and s.item_return=0)
 				JOIN purchase_invoice m ON s.purchase_id=m.id 
			WHERE " . $where . " group by p.id,s.packing,s.batch_no  order by p.Item_name";
		$query = $this->db->query($sql);
		$stock_list = $query->result();

		$content = "";
		$content .= '<table class="table table-bordered table-striped TableData" style="border: 1px solid black;">
					<thead>
						<tr>
							<th style="width:300px;">Item Name</th>
							<th >Generic Name</th>
							<th >MRP</th>
							<th >Batch</th>
							<th >Expiry</th>
							<th>Current Pak.</th>
							<th>Current Unit Qty</th>
							<th>Total Sale Pak.</th>
							<th>Total Sale Unit Qty</th>
							<th>Package/Re-Order Qty</th>
							<th>Purchase Unit Rate</th>
							<th>GST</th>
							<th>Stock Cost</th>
						</tr>
					</thead>
					<tbody>';

		$total_stock_value = 0;

		foreach ($stock_list as $row) {

			/* if ($row->C_Pak_Qty <= $row->re_order_qty) {
				$tr_color = 'style="color:rgb(243, 7, 7);"';
			} else {
				$tr_color = '';
			} */

			$tr_color = '';

			$content .= '<tr ' . $tr_color . ' >
						<td valign="top">' . $row->item_name . '</td>
						<td valign="top">' . $row->genericname . '</td>
						<td valign="top">' . $row->mrp . '</td>
						<td valign="top">' . $row->batch_no . '</td>
						<td valign="top">' . $row->expiry_date . '</td>
						<td valign="top">' . $row->C_Pak_Qty . '</td>
						<td valign="top">' . $row->C_Unit_Stock_Qty . '</td>
						<td valign="top">' . $row->C_Pak_Sale_Qty . '</td>
						<td valign="top">' . $row->sale_unit . '</td>
						<td valign="top">' . $row->packing . '</td>
						<td valign="top">' . $row->purchase_unit_rate . '</td>
						<td valign="top">' . $row->CGST_per*2 . '</td>
						<td valign="top">' . $row->Stock_Cost . '</td>
						</tr>';
			$total_stock_value += $row->Stock_Cost;
		}

		$content .= '<tr >
						<th colspan="7">Total Stock Cost</th>
						<th valign="top">' . $total_stock_value . '</th>
					</tr>';

		$content .= '</tbody></table>';

		//echo $content; 
		ExportExcel($content, 'Report_Medical_store_stock');
	}

	public function Stock_result_excel($chk_reorder, $item_name, $schedule_id)
	{
		$HAVING = "";
		$where = " 1=1  ";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING (SUM(TRUNCATE((p.total_unit-p.total_sale_unit-p.total_lost_unit-p.total_return_unit)/p.packing,0)))<=m.re_order_qty";
		}else{
			$where .=" and (p.total_unit-p.total_lost_unit-p.total_sale_unit-p.total_return_unit)>0 ";
		}

		

		if (strlen(urldecode($item_name))>2) {
			$where .= " and m.item_name like '%" . urldecode($item_name) . "%' ";
		}

		if ($schedule_id <>0 ) {

			$schedule_id = explode("S", $schedule_id);
			if (count($schedule_id) > 0) {
				$where .= " ( ";
			}
			foreach ($schedule_id as $row) {
				if ($row == 1) {
					$where .= " or m.schedule_h=1 ";
				}

				if ($row == 2) {
					$where .= " or m.schedule_h1=1 ";
				}

				if ($row == 3) {
					$where .= " or m.schedule_x=1 ";
				}

				if ($row == 4) {
					$where .= " or m.schedule_g=1 ";
				}

				if ($row == 5) {
					$where .= " or m.narcotic=1 ";
				}
			}
			if (count($schedule_id) > 0) {
				$where .= " ) ";
			}
		}



		$where .= " and i.date_of_invoice>date_add(curdate(),interval -24 month) ";

		$sql = "SELECT m.item_name, m.id,m.genericname,
			SUM(p.tqty) AS Pak_qty,
			SUM(p.total_unit) AS P_Unit_Qty,
			m.packing,
			SUM(TRUNCATE((p.total_unit-p.total_sale_unit)/p.packing,0)) AS C_Pak_Qty,
			SUM(p.total_sale_unit) AS sale_unit,
			SUM(TRUNCATE((p.total_sale_unit)/p.packing,0)) AS C_Pak_Sale_Qty,  
			m.re_order_qty, 
			SUM(p.total_unit-p.total_sale_unit) AS C_Unit_Stock_Qty,
			SUM(TRUNCATE((p.total_unit-p.total_sale_unit-p.total_lost_unit-p.total_return_unit),0)*p.purchase_unit_rate) AS Stock_Cost,
			p.purchase_unit_rate 
			FROM (med_product_master m JOIN purchase_invoice_item p 
			ON m.id=p.item_code and m.is_continue=1 and p.item_return=0 AND p.remove_item=0)
			JOIN purchase_invoice i ON p.purchase_id=i.id
			WHERE " . $where . " group by m.id,p.purchase_unit_rate,p.packing " . $HAVING . " order by m.Item_name";

		//echo $sql;

		$query = $this->db->query($sql);
		$stock_list = $query->result();

		$content = "";
		//$content=$sql;

		$content .= '<table class="table table-bordered table-striped TableData" style="border: 1px solid black;">
					<thead>
						<tr>
							<th style="width:300px;">Item Name</th>
							<th >Generic Name</th>
							<th>Current Pak.</th>
							<th>Current Unit Qty</th>
							<th>Total Sale Pak.</th>
							<th>Total Sale Unit Qty</th>
							<th>Package/Re-Order Qty</th>
							<th>Purchase Unit Rate</th>
							<th>Stock Cost</th>
						</tr>
					</thead>
					<tbody>';

		$total_stock_value = 0;

		foreach ($stock_list as $row) {

			if ($row->C_Pak_Qty <= $row->re_order_qty) {
				$tr_color = 'style="color:rgb(243, 7, 7);"';
			} else {
				$tr_color = '';
			}

			$content .= '<tr ' . $tr_color . ' >
						<td valign="top">' . $row->item_name . '</td>
						<td valign="top">' . $row->genericname . '</td>
						<td valign="top">' . $row->C_Pak_Qty . '</td>
						<td valign="top">' . $row->C_Unit_Stock_Qty . '</td>
						<td valign="top">' . $row->C_Pak_Sale_Qty . '</td>
						<td valign="top">' . $row->sale_unit . '</td>
						<td valign="top">' . $row->packing . '</td>
						<td valign="top">' . $row->purchase_unit_rate . '</td>
						<td valign="top">' . $row->Stock_Cost . '</td>
						</tr>';
			$total_stock_value += $row->Stock_Cost;
		}

		$content .= '<tr >
						<th colspan="7">Total Stock Cost</th>
						<th valign="top">' . $total_stock_value . '</th>
					</tr>';

		$content .= '</tbody></table>';

		//echo $content; 
		ExportExcel($content, 'Report_Medical_store_stock');
	}

	public function Stock_result_excel_2($supplier_id, $opd_date_range, $item_name, $chk_reorder, $schedule_id = '')
	{

		$HAVING = "";
		$where = " 1=1  ";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING SUM(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)<=p.re_order_qty";
		}else{
			$where .=" and (s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)>0 ";
		}

		

		if (($item_name <> '-') || strlen(trim($item_name)) > 0) {
			$where .= " and p.item_name like '%" . $item_name . "%' ";
		}

		if (!($opd_date_range == '' || $opd_date_range <> '0')) {
			$rangeArray = explode("S", $opd_date_range);
			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];

			$where .= " and s.stock_date between  '" . $minRange . "' and '" . $maxRange . "'";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		if ($schedule_id == '') {
		} else {

			$where .= " and (1<>1 ";
			$schedule_id = explode("S", $schedule_id);
			foreach ($schedule_id as $row) {
				if ($row == 1) {
					$where .= " or p.schedule_h=1 ";
				}

				if ($row == 2) {
					$where .= " or p.schedule_h1=1 ";
				}

				if ($row == 3) {
					$where .= " or p.schedule_x=1 ";
				}

				if ($row == 4) {
					$where .= " or p.schedule_g=1 ";
				}

				if ($row == 5) {
					$where .= " or p.narcotic=1 ";
				}
			}
			$where .= ")";
		}



		$sql = "SELECT p.item_name, p.id,p.genericname,
				SUM(s.tqty) AS Pak_qty,
				SUM(s.total_unit) AS P_Unit_Qty,
				s.packing,
				SUM(TRUNCATE((s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)/s.packing,0)) AS C_Pak_Qty,
				SUM(s.total_sale_unit) AS sale_unit,
				SUM(TRUNCATE((s.total_sale_unit)/s.packing,0)) AS C_Pak_Sale_Qty,  
				p.re_order_qty, 
				SUM(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit) AS C_Unit_Stock_Qty,
				SUM(TRUNCATE((s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit),0)*s.purchase_unit_rate) AS Stock_Cost 		
				FROM (med_product_master p JOIN purchase_invoice_item s ON p.id=s.item_code and s.item_return=0)
 				JOIN purchase_invoice m ON s.purchase_id=m.id 
			WHERE " . $where . " group by s.id " . $HAVING . " order by p.Item_name";
		$query = $this->db->query($sql);
		$stock_list = $query->result();

		echo $sql;


		$content = "";
		$content .= '<table class="table table-bordered table-striped TableData" style="border: 1px solid black;">
					<thead>
						<tr>
							<th style="width:300px;">Item Name</th>
							<th>Total Unit Qty</th>
							<th>Current Unit Qty</th>
							<th>Total Sale Unit Qty</th>
							<th>Package/Re-Order Qty</th>
							<th>Stock Cost</th>
						</tr>
					</thead>
					<tbody>';

		foreach ($stock_list as $row) {

			if ($row->C_Pak_Qty <= $row->re_order_qty) {
				$tr_color = 'style="background-color: #999999;"';
			} else {
				$tr_color = '';
			}

			$content .= '<tr ' . $tr_color . ' >
						<td valign="top">' . $row->item_name . '</td>
						<td valign="top">' . $row->P_Unit_Qty . '</td>
						<td valign="top">' . $row->C_Unit_Stock_Qty . '</td>
						<td valign="top">' . $row->sale_unit . '</td>
						<td valign="top">' . $row->packing . '</td>
						<td valign="top">' . $row->Stock_Cost . '</td>
						</tr>';
		}

		$content .= '</tbody></table>';

		//echo $content;
		ExportExcel($content, 'Report_Medical_store_stock');
	}

	public function Stock_result_excel_3($supplier_id, $opd_date_range, $item_name, $chk_reorder, $schedule_id = "")
	{

		$HAVING = "";
		$where = " 1=1  ";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING SUM(p.total_unit-p.total_lost_unit-p.total_sale_unit-p.total_return_unit)<=p.re_order_qty";
		}else{
			$where .=" and (p.total_unit-p.total_lost_unit-p.total_sale_unit-p.total_return_unit)>0 ";
		}

		if (strlen(urldecode($item_name))>2) {
			$where .= " and m.item_name like '%" . urldecode($item_name) . "%' ";
		}

		

		if (!($opd_date_range == '' || $opd_date_range == '0')) {
			$rangeArray = explode("S", $opd_date_range);
			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];

			$where .= " and s.stock_date between  '" . $minRange . "' and '" . $maxRange . "'";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		if ($schedule_id == '') {
		} else {

			$where .= " and (1<>1 ";
			$schedule_id = explode("S", $schedule_id);
			foreach ($schedule_id as $row) {
				if ($row == 1) {
					$where .= " or p.schedule_h=1 ";
				}

				if ($row == 2) {
					$where .= " or p.schedule_h1=1 ";
				}

				if ($row == 3) {
					$where .= " or p.schedule_x=1 ";
				}

				if ($row == 4) {
					$where .= " or p.schedule_g=1 ";
				}

				if ($row == 5) {
					$where .= " or p.narcotic=1 ";
				}
			}
			$where .= ")";
		}

		$sql = "SELECT m.item_name, m.id,m.genericname,m.formulation,p.expiry_date,p.mrp,p.CGST_per, SUM(p.tqty) AS Pak_qty, SUM(p.total_unit) AS P_Unit_Qty,  p.batch_no,p.selling_unit_rate,p.packing,
			SUM(TRUNCATE((p.total_unit-p.total_sale_unit-p.total_lost_unit-p.total_return_unit)/p.packing,0)) AS C_Pak_Qty, SUM(p.total_sale_unit) AS sale_unit, SUM(TRUNCATE((p.total_sale_unit)/p.packing,0)) AS C_Pak_Sale_Qty, m.re_order_qty, 
			SUM(p.total_unit-p.total_sale_unit-p.total_lost_unit-p.total_return_unit) AS C_Unit_Stock_Qty, SUM(TRUNCATE((p.total_unit-p.total_sale_unit-p.total_lost_unit-p.total_return_unit),0)*p.purchase_unit_rate) AS Stock_Cost, p.purchase_unit_rate ,
			max(p.HSNCODE) AS HSNCODE
			FROM (med_product_master m JOIN purchase_invoice_item p ON m.id=p.item_code and m.is_continue=1 and p.item_return=0 AND p.remove_item=0) 
			JOIN purchase_invoice i ON p.purchase_id=i.id
			WHERE " . $where . " group by m.id,p.purchase_unit_rate,p.packing,p.batch_no,p.expiry_date " . $HAVING . " order by p.Item_name";
		$query = $this->db->query($sql);
		$stock_list = $query->result();


		$content = "";
		//$content = $sql;
		$content .= '<table class="table table-bordered table-striped TableData" style="border: 1px solid black;">
					<thead>
						<tr>
							<th style="width:300px;">Item Name</th>
							<th>Compostion Name (Salt Name)</th>
							<th>Formulation</th>
							<th>Batch No.</th>
							<th>Expiry</th>
							<th>Package</th>
							<th>Purchase Unit Qty</th>
							<th>Purchase Unit Rate</th>
							<th>Sale Unit Qty</th>
							<th>Sale Unit Rate</th>
							<th>MRP</th>
							<th>Cur. Qty</th>
							<th>GST Per</th>
							<th>HSNCODE</th>
						</tr>
					</thead>
					<tbody>';

		foreach ($stock_list as $row) {

			if ($row->C_Pak_Qty <= $row->re_order_qty) {
				$tr_color = 'style="background-color: #999999;"';
			} else {
				$tr_color = '';
			}

			$content .= '<tr >
						<td valign="top">' . $row->item_name . '</td>
						<td valign="top">' . $row->genericname . '</td>
						<td valign="top">' . $row->formulation . '</td>
						<td valign="top">' . $row->batch_no . '</td>
						<td valign="top">' . $row->expiry_date . '</td>
						<td valign="top">' . $row->packing . '</td>
						<td valign="top">' . $row->P_Unit_Qty . '</td>
						<td valign="top">' . $row->purchase_unit_rate . '</td>
						<td valign="top">' . $row->sale_unit . '</td>
						<td valign="top">' . $row->selling_unit_rate . '</td>
						<td valign="top">' . $row->mrp . '</td>
						<td valign="top">' . $row->C_Unit_Stock_Qty . '</td>
						<td valign="top">' . $row->CGST_per . '</td>
						<td valign="top">' . $row->HSNCODE . '</td>
					</tr>';
		}

		$content .= '</tbody></table>';

		//echo $content;
		ExportExcel($content, 'Report_Medical_store_stock');
	}


	public function get_product_stock($product_id, $remove_item = 0)
	{
		$supplier_id = $this->input->post('input_supplier');
		$date_range = $this->input->post('date_range');

		$product_pak = $this->input->post('product_pak');

		$where = "1=1 ";

		if ($date_range <> '0') {
			$rangeArray = explode("S", $date_range);
			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];

			$where .= " and p.date_of_invoice between  '" . $minRange . "' and '" . $maxRange . "'";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		$where .= " and i.item_code=$product_id and i.packing=$product_pak ";

		$sql = "SELECT i.id,s.name_supplier,s.short_name,i.Item_name,
		p.Invoice_no,p.date_of_invoice,i.mrp,i.packing,
		i.batch_no,i.expiry_date,i.purchase_price,i.purchase_unit_rate,
		i.qty,i.qty_free,i.tqty,i.total_unit,i.total_sale_unit,
		i.total_return_unit,i.total_lost_unit,
		(i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit) as cur_unit
		FROM (purchase_invoice p JOIN purchase_invoice_item i ON p.id=i.purchase_id and i.item_return=0)
		JOIN med_supplier s ON p.sid=s.sid
		WHERE $where and remove_item=$remove_item
		Order by p.id";

		$query = $this->db->query($sql);
		$data['product_purchase_detail'] = $query->result();

		//echo $sql;

		$this->load->view('Medical/Store/Stock_Item_history', $data);
	}

	public function get_product_stock_removed($product_id, $remove_item = 0)
	{
		$supplier_id = $this->input->post('input_supplier');
		$date_range = $this->input->post('date_range');

		$where = "1=1 ";

		if ($date_range <> '0') {
			$rangeArray = explode("S", $date_range);

			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];

			$where .= " and p.date_of_invoice between  '" . $minRange . "' and '" . $maxRange . "'";
		} else {
			$where .= " and p.date_of_invoice>=date_add(curdate(),interval -1 year)";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		$where .= " and i.item_code=$product_id";

		$sql = "SELECT i.id,s.name_supplier,s.short_name,i.Item_name,
		p.Invoice_no,p.date_of_invoice,i.mrp,
		i.batch_no,i.expiry_date,i.purchase_price,i.purchase_unit_rate,
		i.qty,i.qty_free,i.tqty,i.total_unit,i.total_sale_unit,
		i.total_return_unit,i.total_lost_unit,
		(i.total_unit-i.total_sale_unit-i.total_lost_unit) as cur_unit
		FROM (purchase_invoice p JOIN purchase_invoice_item i ON p.id=i.purchase_id)
		JOIN med_supplier s ON p.sid=s.sid
		WHERE $where and remove_item=1 
		Order by p.id";

		$query = $this->db->query($sql);
		$data['product_purchase_detail'] = $query->result();

		$this->load->view('Medical/Store/Stock_Item_history_removed', $data);
	}

	public function get_product_stock_return($product_id)
	{
		$supplier_id = $this->input->post('input_supplier');
		$date_range = $this->input->post('date_range');

		$where = "1=1 ";

		if ($date_range <> '0') {
			$rangeArray = explode("S", $date_range);

			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];

			$where .= " and p.date_of_invoice between  '" . $minRange . "' and '" . $maxRange . "'";
		} else {
			$where .= " and p.date_of_invoice>=date_add(curdate(),interval -1 year)";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		$where .= " and i.item_code=$product_id";

		$sql = "SELECT m.p_r_invoice_no, m.date_of_invoice,m.insert_datetime,i.Item_name,i.qty,s.name_supplier
				FROM (purchase_return_invoice m JOIN purchase_return_invoice_item i ON m.id=i.purchase_inv_id)
				JOIN med_supplier s ON m.sid=s.sid
				WHERE i.item_code=$product_id";
		$query = $this->db->query($sql);
		$data['return_purchase'] = $query->result();

		$sql = "SELECT m.Invoice_no, m.date_of_invoice,m.insert_time,i.Item_name,i.qty,s.name_supplier,i.packing
				FROM (purchase_invoice m JOIN purchase_invoice_item i ON m.id=i.purchase_id)
				JOIN med_supplier s ON m.sid=s.sid
				WHERE i.item_return=1 and i.item_code=$product_id";
		$query = $this->db->query($sql);
		$data['return_invoice_purchase'] = $query->result();


		$this->load->view('Medical/Store/Stock_Item_history_return', $data);
	}


	public function Org_Bills()
	{
		$sql = "select * from hc_insurance where id>1 and active=1 ";
		$query = $this->db->query($sql);
		$data['data_insurance'] = $query->result();

		$this->load->view('Medical/Report/Report_org_invoice_list', $data);
	}

	public function Org_Bills_Report($date_range, $Insurance_id, $output = 0)
	{
		$rangeArray = explode("S", $date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$where = "  ";

		$where .= " and m.inv_date between '" . $minRange . "' and '" . $maxRange . "'";

		if ($Insurance_id > 0) {
			$where .= " and i.id = " . $Insurance_id;
		}

		$sql = "select m.inv_med_code,o.case_id_code,p.p_code,p.p_fname,m.net_amount,i.ins_company_name
				from ((invoice_med_master m join patient_master p on m.patient_id=p.id)
				join organization_case_master o on m.case_id=o.id)
				join hc_insurance i on o.insurance_id=i.id
				where m.case_id>1 and m.case_credit=1 " . $where . " ";
		$query = $this->db->query($sql);
		$data_insurance = $query->result();

		$content = ""; // $sql;

		$total_amt = 0;

		$content .= '<table border="1" cellpadding="5">
					<tr>
						<td>Inv. ID</td>
						<td>Org.Code</td>
						<td>P Code</td>
						<td>P Name</td>
						<td>Amount</td>
						<td>Org. Name</td>
					</tr>';

		foreach ($data_insurance as $row) {
			$content .= '<tr>
							<td>' . $row->inv_med_code . '</td>
							<td>' . $row->case_id_code . '</td>
							<td>' . $row->p_code . '</td>
							<td>' . $row->p_fname . '</td>
							<td align="right" >' . $row->net_amount . '</td>
							<td>' . $row->ins_company_name . '</td>
						</tr>';
			$total_amt = $total_amt + $row->net_amount;
		}

		$content .= '<tr>
						<td></td>
						<td></td>
						<td></td>
						<td>Total</td>
						<td  align="right">' . $total_amt . '</td>
						<td></td>
					</tr>';
		$content .= '</table>';

		if ($output == 0) {
			create_report_pdf_landscape($content, 'Med_Invoice_org');
		} else {
			ExportExcel($content, 'Med_Invoice_org');
		}
	}


	public function Stock_qty_result()
	{
		$supplier_id = $this->input->post('input_supplier');
		$item_name = $this->input->post('txtsearch');
		$store_id = $this->input->post('input_store_id');
		$chk_reorder = $this->input->post('chk_reorder');

		$HAVING = "";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING (sum(i.total_unit) - sum(if(ss.sale_qty is NULL,0,ss.sale_qty)))<=pm.re_order_qty";
		}

		$where = " remove_item=0 ";

		if ($supplier_id > 0) {
			$where .= " and s.sid = " . $supplier_id;
		}


		if ($item_name <> '') {
			$where .= " and i.Item_name like '%" . $item_name . "%' ";
		}

		$sql = "SELECT i.Item_name,i.item_code ,sum(distinct i.tqty), 
				pm.re_order_qty,
				sum(i.total_unit) as T_unit,
				sum(if(ss.sale_qty is NULL,0,ss.sale_qty)) sal_qty,
				GROUP_CONCAT(distinct s.name_supplier) AS supplier_names
				FROM (((purchase_invoice_item i join purchase_invoice p on i.purchase_id=p.id ) 
				join med_supplier s on s.sid=p.sid)
				JOIN med_product_master pm ON i.item_code=pm.id AND pm.is_continue=1)
				LEFT JOIN  
				(SELECT item_code,sum(qty) AS sale_qty FROM  inv_med_item GROUP BY item_code ) 
					AS ss ON  i.item_code= ss.item_code
				Where " . $where . " group by i.item_code  " . $HAVING . " order by i.Item_name";
		$query = $this->db->query($sql);
		$data['stock_list'] = $query->result();

		$this->load->view('Medical/Stock/Stock_search_qty_result', $data);
	}




	public function Report_Med_patient()
	{
		$this->load->view('Medical/Report/Report_Med_patient');
	}

	public function drug_patient_distribute($date_range, $item_name, $schedule_id, $output = 0)
	{
		$rangeArray = explode("S", $date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$item_name = urldecode($item_name);
		//$item_name=$this->input->post('txtsearch');

		$where = "  ";

		$where .= "  m.inv_date between '" . $minRange . "' and '" . $maxRange . "'";

		$schedule_id = explode("S", $schedule_id);

		if ($item_name <> '-') {
			$where .= " and (t.item_Name like '%" . $item_name . "%' or t.batch_no ='" . $item_name . "') ";
		} else if ($schedule_id > 0) {
			$where .= " and (1<>1 ";
			foreach ($schedule_id as $row) {
				if ($row == 1) {
					$where .= " or p.schedule_h=1 ";
				}

				if ($row == 2) {
					$where .= " or p.schedule_h1=1 ";
				}

				if ($row == 3) {
					$where .= " or p.schedule_x=1 ";
				}

				if ($row == 4) {
					$where .= " or p.schedule_g=1 ";
				}

				if ($row == 5) {
					$where .= " or p.narcotic=1 ";
				}

				if ($row == 6) {
					$where .= " or p.high_risk=1 ";
				}
			}
			$where .= ")";
		} else {
			$where .= " and 1<>1";
		}


		$sql = "select t.item_Name,m.inv_med_code,m.id as m_id,date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
			date_format(t.expiry,'%m-%Y') as exp_date,
			m.patient_id,m.patient_code,m.inv_name,m.ipd_id,m.ipd_code,	t.batch_no,sum(if(t.sale_return=0,t.qty,t.qty*-1)) as t_qty,
			TRIM(',' FROM Concat(if(p.schedule_h=1,'schedule_h,',''),
						if(p.schedule_h1=1,'schedule_h1,',''),
						if(p.narcotic=1,'narcotic,',''),
						if(p.schedule_x=1,'schedule_x,',''),
						if(p.schedule_g=1,'schedule_g,',''))) as shed_x_h
			from (invoice_med_master m join inv_med_item t on m.id=t.inv_med_id) 
				join med_product_master p on  p.id=t.item_code
			where " . $where . " group by t.item_Name,m.id WITH ROLLUP";
		$query = $this->db->query($sql);
		$stock_list = $query->result();

		//echo $sql;

		$content = '<table  border="1" width="100%" cellpadding="2" cellspacing="0"> 
						<tr>
							<th style="background-color: #F69454;width:150px;">Inv.No.</th>
							<th style="width:100px;">Inv.Date</th>
							<th style="background-color: #F69454;width:150px;">IPD No.</th>
							<th style="width:300px;">P Code/Name</th>
							<th style="background-color: #F69454;width:80px;">Exp. Date</th>
							<th style="width:100px;">Batch</th>
							<th style="background-color: #F69454;width:50px;">Qty</th>
						</tr>';

		$Head_Content = "";
		foreach ($stock_list as $row) {
			if ($row->m_id == '') {
				$content .=  '<tr>';
				$content .= '	<td colspan="6" style="background-color: yellow;color:black">Total Qty : </td>';
				$content .=  '	<td  style="background-color: #F69454; " align="right">' . $row->t_qty . '</td>';
				$content .=  '</tr>';
			} else {
				if ($Head_Content <> $row->item_Name) {
					$content .= '<tr>';
					$content .= '	<td colspan="7" style="background-color: Red;color:white">' . $row->item_Name . '[ ' . $row->shed_x_h . ' ]</td>';
					$content .= '</tr>';
					$Head_Content = $row->item_Name;
				}

				$content .=  '<tr>';
				$content .=  '	<td style="background-color: #F69454;">' . $row->inv_med_code . '</td>';
				$content .=  '	<td >' . $row->str_inv_date . '</td>';
				$content .=  '	<td style="background-color: #F69454; ">' . $row->ipd_code . '</td>';
				$content .=  '	<td  >' . $row->patient_code . ' / ' . $row->inv_name . '</td>';
				$content .=  '	<td style="background-color: #F69454; ">' . $row->exp_date . '</td>';
				$content .=  '	<td    align="right">' . $row->batch_no . '</td>';
				$content .=  '	<td  style="background-color: #F69454; " align="right">' . $row->t_qty . '</td>';
				$content .=  '</tr>';
			}
		}

		$content .= '</table>';

		if ($output == 0) {
			$this->load->library('m_pdf');

			$this->m_pdf->pdf->setBasePath(base_url('/'));
			$file_name = 'Report-MedicalBill-' . date('Ymdhis') . ".pdf";
			$filepath = $file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath, "I");
		} else {
			ExportExcel($content, 'Report_Medical');
		}
	}



	public function getInvoiceTable()
	{

		$requestData = $_REQUEST;

		$columns = array(
			// datatable column index  => database column name
			0 => 'inv_med_code',
			1 => 'inv_name',
			2 => 'Code',
			3 => 'inv_date_str',
			4 => 'net_amount',
			5 => 'payment_received',
			6 => 'payment_balance',
			7 => 'id',
			8 => 'ipd_status',
			9 => 'org_status',
			10 => 'ipd_credit_type',
			11 => 'org_credit_type'
		);

		// getting total number records without any search
		$sql_f_all = "select m.inv_med_code,m.inv_name,
		Concat(if(m.patient_code<>'',Concat(m.patient_code,'<br>'),''),
		if(m.ipd_code<>'',Concat('<a href=\'javascript:load_form_div(\"/Medical_backpanel/list_med_inv/',i.id,'\",\"maindiv\")\'>',m.ipd_code,'</a><br>'),''),
		if(m.case_id>0,Concat(o.case_id_code,'<br>'),'')) as Code ,
		m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,
		m.doc_name,m.net_amount,m.payment_received,m.payment_balance,m.id,i.ipd_status,
		o.status as org_status,
		m.ipd_credit as ipd_credit_type,m.case_credit as org_credit_type ";

		$sql_count = "Select count(*) as no_rec ";

		$sql_from = " from (invoice_med_master m left join ipd_master i on m.ipd_id=i.id) 
		left join organization_case_master o on m.case_id=o.id ";

		$total_sql = $sql_count . $sql_from;

		$query = $this->db->query($total_sql);
		$data['total_rec'] = $query->result();
		$totalData = $data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where = " WHERE 1 = 1";

		$sql_where_flag = 0;

		// getting records as per search parameters
		if (!empty($requestData['columns'][0]['search']['value'])) {   //inv_med_code
			$sql_where .= " AND inv_med_code LIKE '%" . $requestData['columns'][0]['search']['value'] . "' ";
			$sql_where_flag = 1;
		}

		if (!empty($requestData['columns'][1]['search']['value'])) {  //inv_name
			$sql_where .= " AND inv_name LIKE '%" . $requestData['columns'][1]['search']['value'] . "%' ";
			$sql_where_flag = 1;
		}

		if (!empty($requestData['columns'][2]['search']['value'])) {  //patient_code
			$sql_where .= " AND (patient_code LIKE '%" . $requestData['columns'][2]['search']['value'] . "' OR 
				m.ipd_code LIKE '%" . $requestData['columns'][2]['search']['value'] . "' OR 
				o.case_id_code LIKE '%" . $requestData['columns'][2]['search']['value'] . "' )";
			$sql_where_flag = 1;
		}

		$total_filer_sql = $sql_count . $sql_from . $sql_where;

		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter'] = $query->result(); // when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered = $data['total_rec_filter'][0]->no_rec;

		$sql_order = " ORDER BY id desc," . $columns[$requestData['order'][0]['column']] . "   " . $requestData['order'][0]['dir'] . "   LIMIT " . $requestData['start'] . " ," . $requestData['length'] . "   ";  // adding length

		$Result_sql = $sql_f_all . $sql_from . $sql_where . $sql_order;

		$query = $this->db->query($Result_sql);
		$rdata = $query->result_array();

		$output = array(
			"draw"            => intval($requestData['draw']),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
			"recordsTotal"    => intval($totalData),  // total number of records
			"recordsFiltered" => intval($totalFiltered), // total number of records after searching, if there is no searching then totalFiltered = totalData
			"data"            => array(),
			"sql" => $Result_sql
		);

		foreach ($rdata as $aRow) {
			$row = array();

			foreach ($columns as $col) {
				$row[] = $aRow[$col];
			}

			$output['data'][] = $row;
		}

		echo json_encode($output);  // send data as json format

	}

	public function Invoice_med_show($inv_id)
	{
		$this->load->model('Medical_M');

		$this->Medical_M->update_invoice_group($inv_id);
		$this->Medical_M->update_invoice_final($inv_id);

		$sql = "select *,(discount_amount+item_discount_amount) as inv_disc_total,(CGST_Tamount+SGST_Tamount) as TGST ,
		if(ipd_credit>0,'Credit to IPD','CASH') as credit_status,if(case_credit>0,'Credit to Org','CASH') as credit_org_status
		from invoice_med_master where id=" . $inv_id;

		$query = $this->db->query($sql);
		$data['invoiceMaster'] = $query->result();

		$sql = "select * from inv_med_item where inv_med_id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['inv_items'] = $query->result();

		$sql = "select * from doctor_master where active=1";
		$query = $this->db->query($sql);
		$data['doclist'] = $query->result();

		$sql = "select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='" . $data['invoiceMaster'][0]->patient_id . "' ";
		$query = $this->db->query($sql);
		$data['person_info'] = $query->result();

		$sql = "select * from  ipd_master  where ipd_status=0 and id=" . $data['invoiceMaster'][0]->ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_master'] = $query->result();

		if ($data['invoiceMaster'][0]->case_id > 0) {
			$sql = "select * from organization_case_master where id=" . $data['invoiceMaster'][0]->case_id;
			$query = $this->db->query($sql);
			$data['OCaseMaster'] = $query->result();
		}

		$sql = "select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history_medical where Medical_invoice_id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['payment_history_medical'] = $query->result();

		$this->load->view('Medical/Invoice_OLD_Master', $data);
	}

	public function update_rate()
	{
		$invoice_id = $this->input->post('med_invoice_id');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$dataupdate = array(
			'rate_update' => $this->input->post('input_dis_amt'),
			'rate_update_by' => $user_name . '[' . $user_id . ']'
		);

		$this->load->model('Medical_M');
		$this->Medical_M->update_in_upv_item($dataupdate, $invoice_id);

		$this->Medical_M->update_invoice_rate_final($invoice_id);
		#$this->Medical_M->update_invoice_final($invoice_id);

	}


	public function update_stock_adjust()
	{
		$item_id = $this->input->post('item_id');
		$qty = $this->input->post('qty');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$data_stock_adjuct = array(
			'item_id' => $item_id,
			'qty' => $qty,
			'date_of_adjust' => date('Y-m-d'),
			'update_by' => $user_name . '[' . $user_id . ']'
		);


		$this->load->model('Medical_M');

		$ins_id = $this->Medical_M->update_purchase_item_stock_adjust($data_stock_adjuct, $item_id);

		echo $ins_id;
	}

	public function remove_stock_item()
	{
		$item_id = $this->input->post('item_id');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$data_stock_adjuct = array(
			'item_id' => $item_id,
			'date_of_adjust' => date('Y-m-d'),
			'qty' => '0',
			'update_by' => $user_name . '[' . $user_id . ']'
		);

		$this->load->model('Medical_M');

		$ins_id = $this->Medical_M->update_remove_status_item($data_stock_adjuct, $item_id);

		echo $ins_id;
	}

	public function add_stock_item()
	{
		$item_id = $this->input->post('item_id');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$data_stock_adjuct = array(
			'item_id' => $item_id,
			'date_of_adjust' => date('Y-m-d'),
			'qty' => '0',
			'update_by' => $user_name . '[' . $user_id . ']'
		);

		$this->load->model('Medical_M');

		$ins_id = $this->Medical_M->add_removed_item($data_stock_adjuct, $item_id);

		echo $ins_id;
	}

	public function Report_IPD_CreditBills()
	{
		$this->load->view('Medical/Report/Med_Report_IPD_Credit');
	}

	//Supplier Ledger

	public function SupplierAccount()
	{
		$sql = "SELECT m.sid,m.name_supplier,m.short_name,
		SUM(if(l.credit_debit=0,l.amount,l.amount*-1)) AS Tot_Balance,
		date_format(MAX(if(l.credit_debit=0 AND l.purchase_id>0,l.tran_date,'')),'%d-%m-%Y') AS Last_InvDate,
		date_format(MAX(if(l.credit_debit=1 AND l.purchase_id=0,l.tran_date,'')),'%d-%m-%Y') AS Last_Payment
		FROM med_supplier m left JOIN med_supplier_ledger l ON m.sid=l.supplier_id
		GROUP BY m.sid
		ORDER BY m.name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data'] = $query->result();

		$this->load->view('Medical/Purchase/supplier_account', $data);
	}

	public function SupplierAccount_led($sid)
	{
		$sql = "SELECT m.sid,m.name_supplier,m.short_name,
		SUM(if(l.credit_debit=0,l.amount,l.amount*-1)) AS Tot_Balance,
		date_format(MAX(if(l.credit_debit=0 AND l.purchase_id>0,l.tran_date,'')),'%d-%m-%Y') AS Last_InvDate,
		date_format(MAX(if(l.credit_debit=1 AND l.purchase_id=0,l.tran_date,'')),'%d-%m-%Y') AS Last_Payment
		FROM med_supplier m left JOIN med_supplier_ledger l ON m.sid=l.supplier_id
		where m.sid=$sid";
		$query = $this->db->query($sql);
		$data['supplier_data'] = $query->result();

		$this->load->view('Medical/Purchase/supplier_account_detail', $data);
	}

	function search_result_tran($s_id, $date_range = '')
	{

		if ($date_range == '') {
			$date_range = $this->input->post('led_date_range');
		}

		$rangeArray = explode("S", $date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$where = " date(l.tran_date) BETWEEN '$minRange' AND '$maxRange' ";

		$sql = "  SELECT l.*,
                CONCAT_WS('/',b.bank_account_name,b.bank_name) AS mode_desc
                FROM (med_supplier_ledger l JOIN bank_account_master b ON l.bank_id=b.bank_id)
                Where l.supplier_id=$s_id and $where order by tran_date desc";
		$query = $this->db->query($sql);
		$data['med_supplier_ledger'] = $query->result();


		$sql = "  SELECT  sum(if(credit_debit=0,amount,amount*-1)) as Balance
                FROM med_supplier_ledger l 
                Where l.supplier_id=$s_id and date(l.tran_date) <'$minRange' ";
		$query = $this->db->query($sql);
		$supplier_balance_till_date = $query->result();

		$data['balance_till_date'] = $supplier_balance_till_date[0]->Balance;

		$sql = "  SELECT  sum(if(credit_debit=0,amount,amount*-1)) as Balance
				FROM med_supplier_ledger l 
                Where l.supplier_id=$s_id and date(l.tran_date) <='$maxRange' ";
		$query = $this->db->query($sql);
		$supplier_balance_till_date = $query->result();
		$data['balance_till_date_close'] = $supplier_balance_till_date[0]->Balance;

		$sql = "  SELECT  sum(if(credit_debit=0,amount,0)) as cr_total,
				sum(if(credit_debit=1,amount,0)) as dr_total
                FROM med_supplier_ledger l 
                Where l.supplier_id=$s_id and $where ";
		$query = $this->db->query($sql);
		$supplier_data_total_cr_dr = $query->result();

		$data['cr_total'] = $supplier_data_total_cr_dr[0]->cr_total;
		$data['dr_total'] = $supplier_data_total_cr_dr[0]->dr_total;

		$data['s_id'] = $s_id;
		$data['date_range'] = $date_range;

		$this->load->view('Medical/Purchase/ledger_data', $data);
	}

	function add_entry($s_id)
	{
		$this->load->model('Medical_M');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user_id . ']';

		if (isset($_POST) && count($_POST) > 0) {
			$params = array(
				'supplier_id' => $s_id,
				'credit_debit' => $this->input->post('cr_dr_type'),
				'tran_date' => str_to_MysqlDate($this->input->post('tran_date')),
				'amount' => $this->input->post('amount'),
				'bank_id' => $this->input->post('mode_type'),
				'tran_desc' => $this->input->post('ref_cust_id'),
				'tran_desc' => $this->input->post('tran_desc'),
				'insert_by_id' => $user_id,
				'insert_by' => $user_name,
			);

			$insert_id = $this->Medical_M->insert_med_supplier_ledger($params);
			redirect('Medical_backpanel/SupplierAccount_led/' . $s_id);
		} else {
			$data['s_id'] = $s_id;

			$sql = "select * from bank_account_master";
			$query = $this->db->query($sql);
			$data['bank_account_master'] = $query->result();



			$this->load->view('Medical/Purchase/ledger_add_entry', $data);
		}
	}

	function edit_entry($tran_id)
	{

		$this->load->model('Medical_M');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user_id . ']';

		$sql = "select * from med_supplier_ledger where id=$tran_id";
		$query = $this->db->query($sql);
		$data['med_supplier_ledger'] = $query->result();

		$s_id = $data['med_supplier_ledger'][0]->supplier_id;

		if (isset($_POST) && count($_POST) > 0) {
			$params = array(
				'credit_debit' => $this->input->post('cr_dr_type'),
				'tran_date' => str_to_MysqlDate($this->input->post('tran_date')),
				'amount' => $this->input->post('amount'),
				'bank_id' => $this->input->post('mode_type'),
				'tran_desc' => $this->input->post('ref_cust_id'),
				'tran_desc' => $this->input->post('tran_desc'),
				'insert_by_id' => $user_id,
				'insert_by' => $user_name,
			);

			$this->Medical_M->update_med_supplier_ledger($params, $tran_id);
			redirect('Medical_backpanel/SupplierAccount_led/' . $s_id);
		} else {

			$sql = "select * from bank_account_master";
			$query = $this->db->query($sql);
			$data['bank_account_master'] = $query->result();

			$data['tran_id'] = $tran_id;

			$this->load->view('Medical/Purchase/ledger_edit_entry', $data);
		}
	}

	// Return Invoice
	public function Purchase_return()
	{
		$this->load->view('Medical/Stock/purchase_return');
	}

	public function PurchaseReturnNew()
	{
		$sql = "select * from med_supplier  order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data'] = $query->result();

		$this->load->view('Medical/Stock/new_purchase_return_invoice', $data);
	}

	public function PurchaseReturnInvoice()
	{
		$sdata = trim($this->input->post('txtsearch'));

		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);

		if (trim($sdata) == '') {
			$sql = "select p.id,p.p_r_invoice_no,p.date_of_invoice,
			date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,p.sid,s.name_supplier,
			s.short_name
			from (purchase_return_invoice p join med_supplier s on p.sid=s.sid)
			
			group by p.id
			order by p.id desc limit 50";
		} else {

			$numric_condition = "";

			if (is_numeric($sdata)) {
				$numric_condition = " OR p.Invoice_no=" . $sdata;
			}

			$sql = "select p.id,p.p_r_invoice_no,p.date_of_invoice,
			date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,p.sid,s.name_supplier,
			s.short_name
			from (purchase_return_invoice p join med_supplier s on p.sid=s.sid)
			where (p.p_r_invoice_no LIKE '%" . $sdata . "'  " . $numric_condition . " )
			group by p.id
			order by p.id desc limit 50";
		}

		$query = $this->db->query($sql);
		$data['purchase_return_invoice'] = $query->result();

		$this->load->view('Medical/Stock/purchase_supp_list', $data);
	}


	public function CreatePurchaseReturn()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}


		$sid = $this->input->post('input_supplier');
		$d_invoice = $this->input->post('datepicker_invoice');

		$where = " where sid=$sid";

		$Udata = array(
			'sid' => $sid,
			'date_of_invoice' => str_to_MysqlDate($d_invoice),
		);

		$this->load->model('Medical_M');

		$inser_id = $this->Medical_M->add_purchase_return_invoice($Udata);
		$send_msg = "Added Successfully";

		if ($inser_id > 0) {
			$show_text = Show_Alert('success', 'Success', $send_msg);
		} else {
			$show_text = Show_Alert('danger', 'Error', $send_msg);
		}


		$rvar = array(
			'insertid' => $inser_id,
			'show_text' => $show_text
		);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function PurchaseReturnInvoiceEdit($inv_id)
	{
		$sql = "select p.id,p.p_r_invoice_no as Invoice_no,p.date_of_invoice,
			date_format(p.date_of_invoice,'%d/%m/%Y') as str_date_of_invoice,
			p.sid,s.name_supplier,s.short_name,s.gst_no,p.status as inv_status
			from purchase_return_invoice p join med_supplier s on p.sid=s.sid
			where p.id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['purchase_return_invoice'] = $query->result();

		$sql = "select p.*,r.purchase_inv_id,r.qty as r_qty,Round(r.qty/p.packing,2) AS qty_pak,r.id as r_id,if(r.batch_no_r='',p.batch_no,r.batch_no_r) as  batch_no_r_s, 
		date_format(p.expiry_date,'%m/%y') as exp_date_str ,p.purchase_unit_rate*r.qty AS r_amount,p.purchase_unit_rate,if(p.CGST_per_old IS NULL,CGST_per,CGST_per_old)*2 AS gst_per
		from purchase_return_invoice_item r join purchase_invoice_item p on r.purchase_item_id=p.id
		where purchase_inv_id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['purchase_return_invoice_item'] = $query->result();

		$data['inv_id'] = $inv_id;

		$data['content'] = $this->load->view('Medical/Stock/purchase_return_invoice_item', $data, true);

		$this->load->view('Medical/Stock/purchase_return_invoice_edit', $data);
	}

	public function PurchaseReturn_invoice_item_list($inv_id)
	{
		$sql = "select p.*,r.purchase_inv_id,r.qty as r_qty,Round(r.qty/p.packing,2) AS qty_pak,r.id as r_id,if(r.batch_no_r='',p.batch_no,r.batch_no_r) as  batch_no_r_s, 
		date_format(p.expiry_date,'%m/%y') as exp_date_str ,p.purchase_unit_rate*r.qty AS r_amount,p.purchase_unit_rate
		from purchase_return_invoice_item r join purchase_invoice_item p on r.purchase_item_id=p.id
		where purchase_inv_id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['purchase_return_invoice_item'] = $query->result();

		$sql = "select * from purchase_return_invoice where id='" . $inv_id . "' ";
		$query = $this->db->query($sql);
		$data['purchase_return_invoice'] = $query->result();

		$this->load->view('Medical/Stock/purchase_return_invoice_item', $data);
	}

	public function Purchase_Invoice_product($supp_id)
	{
		$data['supp_id'] = $supp_id;

		$this->load->view('Medical/Stock/purchase_product_search', $data);
	}

	public function Purchase_Invoice_old($supp_id)
	{
		$sdata = trim($this->input->post('txtsearch'));

		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);

		if (trim($sdata) == '') {
			$sql = "select p.id as pur_id,p.Invoice_no,p.date_of_invoice,
			if(i.expiry_date<date_add(curdate(),interval 3 month),1,0) as isExp,
			date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,
			date_format(i.expiry_date,'%m-%Y') as exp_date,
			p.sid,s.name_supplier,s.short_name,s.gst_no,
			p.T_Net_Amount as tamount,i.*,(i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit) as cur_qty
			from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			join purchase_invoice_item i on p.id=i.purchase_id
			Where s.sid=$supp_id and i.remove_item=0 and i.item_return=0
			And (i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit)>0
			and p.date_of_invoice>=date_add(curdate(),interval -1 year)
			order by p.id desc ";
		} else {

			$numric_condition = "";

			if (is_numeric($sdata)) {
				$numric_condition = " OR p.Invoice_no=" . $sdata;
			}

			$sql = "select p.id as pur_id,p.Invoice_no,p.date_of_invoice,
			if(i.expiry_date<date_add(curdate(),interval 3 month),1,0) as isExp,
			date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,
			date_format(i.expiry_date,'%m-%Y') as exp_date,
			p.sid,s.name_supplier,s.short_name,s.gst_no,
			p.T_Net_Amount as tamount,i.*,(i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit) as cur_qty
			from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			join purchase_invoice_item i on p.id=i.purchase_id
			where s.sid=$supp_id and i.remove_item=0 and i.item_return=0
			and (p.Invoice_no LIKE '%" . $sdata . "'  " . $numric_condition . " )
			And (i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit)>0
			and p.date_of_invoice>=date_add(curdate(),interval -1 year)
			order by p.id desc ";
		}

		$query = $this->db->query($sql);
		$data['purchase_list'] = $query->result();

		$this->load->view('Medical/Stock/purchase_invoice_list', $data);
	}

	public function add_remove_item()
	{
		$this->load->model('Medical_M');

		$inv_id = $this->input->post('inv_id');
		$item_id = $this->input->post('itemid');
		$rqty = $this->input->post('rqty');

		$rbatch_no = $this->input->post('rbatch_no');
		$rexpiry_dt = $this->input->post('rexpiry_dt');

		$user = $this->ion_auth->user()->row();
		$user_update_info = $user->first_name . '' . $user->last_name . '[' . $user->id . ']' . date('d-m-Y H:i:s');

		$sql = "select * from purchase_invoice_item where id='" . $item_id . "' ";
		$query = $this->db->query($sql);
		$purchase_invoice_item = $query->result();

		$update = 0;
		$msg_box = "";

		if (count($purchase_invoice_item) > 0) {
			$total_unit = $purchase_invoice_item[0]->total_unit;
			$total_sale_unit = $purchase_invoice_item[0]->total_sale_unit;
			$total_return_unit = $purchase_invoice_item[0]->total_return_unit;
			$total_lost_unit = $purchase_invoice_item[0]->total_lost_unit;

			$cur_unit_qty = $total_unit - $total_sale_unit - $total_return_unit - $total_lost_unit;

			if ($cur_unit_qty > 0) {
				$insert_data = array(
					'purchase_inv_id' => $inv_id,
					'purchase_item_id' => $item_id,
					'item_code' => $purchase_invoice_item[0]->item_code,
					'Item_name' => $purchase_invoice_item[0]->Item_name,
					'batch_no_r' => $rbatch_no,
					'expiry_date_r' => $rexpiry_dt,
					'qty' => $rqty
				);

				$this->Medical_M->add_purchase_return_invoiceitem($insert_data);
				$update = 1;
				$msg_box = "Item Added";
			} else {
				$update = 0;
				$msg_box = "Current Item Qty is " . $cur_unit_qty;
			}
		} else {
			$update = 0;
			$msg_box = "No item found";
		}

		$sql = "select p.*,r.purchase_inv_id,r.qty as r_qty,Round(r.qty/p.packing,2) AS qty_pak,r.id as r_id,if(r.batch_no_r='',p.batch_no,r.batch_no_r) as  batch_no_r_s, 
		date_format(p.expiry_date,'%m/%y') as exp_date_str ,p.purchase_unit_rate*r.qty AS r_amount,p.purchase_unit_rate,if(p.CGST_per_old IS NULL,CGST_per,CGST_per_old)*2 AS gst_per
		from purchase_return_invoice_item r join purchase_invoice_item p on r.purchase_item_id=p.id
		where purchase_inv_id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['purchase_return_invoice_item'] = $query->result();

		$content = $this->load->view('Medical/Stock/purchase_return_invoice_item', $data, true);

		$rvar = array(
			'update' => $update,
			'msg_text' => $msg_box,
			'content' => $content
		);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function get_drug()
	{
		if (isset($_GET['term'])) {
			$q = strtolower($_GET['term']);

			$sql = "SELECT p.item_name as item_name,p.formulation,t.batch_no,t.expiry_date as expiry_date_str,t.expiry_date,
				(t.total_unit-t.total_sale_unit-total_lost_unit-t.total_return_unit) AS c_qty,
				t.id,t.mrp,t.selling_unit_rate,p.id AS item_code,t.packing,t.purchase_id
				FROM med_product_master p 
				JOIN purchase_invoice_item t ON p.id=t.item_code 
				and (t.total_unit-t.total_sale_unit-t.total_lost_unit-t.total_return_unit)>0
				and t.remove_item=0 and t.item_return=0
				where	(p.item_name like '" . $q . "%' ";

			if (strlen($q) > 5) {
				$sql = $sql . " or p.item_name SOUNDS LIKE '" . $q . "'";
			}

			$sql = $sql . ") order by p.item_name,id  limit 100";

			$query = $this->db->query($sql);

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$new_row['label'] = htmlentities(stripslashes($row['item_name'] . ' ' . $row['formulation'])) . ' |B:' . htmlentities(stripslashes($row['batch_no'])) . ' |Pak:' . htmlentities(stripslashes($row['packing'])) . ' |Rs.' . htmlentities(stripslashes($row['mrp'])) . ' |Qty:' . htmlentities(stripslashes($row['c_qty']));
					$new_row['value'] = htmlentities(stripslashes($row['item_name']));
					$new_row['l_item_code'] = htmlentities(stripslashes($row['item_code']));
					$new_row['l_ss_no'] = htmlentities(stripslashes($row['id']));
					$new_row['l_Batch'] = htmlentities(stripslashes($row['batch_no']));
					$new_row['l_Expiry'] = htmlentities(stripslashes($row['expiry_date_str']));
					$new_row['l_mrp'] = htmlentities(stripslashes($row['mrp']));
					$new_row['l_unit_rate'] = htmlentities(stripslashes($row['selling_unit_rate']));
					$new_row['l_c_qty'] = htmlentities(stripslashes($row['c_qty']));
					$new_row['l_packing'] = htmlentities(stripslashes($row['packing']));
					$new_row['l_purchase_id'] = htmlentities(stripslashes($row['purchase_id']));
					//$new_row['sql']=$sql;
					$row_set[] = $new_row; //build an array
				}
				echo json_encode($row_set); //format the array into json data
			} else {
				$new_row['sql'] = $sql;
				$row_set[] = $new_row;
				echo json_encode($row_set);
			}
		}
	}

	public function get_batch($item_id)
	{
		if (isset($_GET['term'])) {
			$q = strtolower($_GET['term']);

			$sql = "SELECT t.batch_no
				FROM med_product_master p 
				JOIN purchase_invoice_item t 
				where t.item_code=$item_id and t.batch_no like '" . $q . "%' ";

			if (strlen($q) > 5) {
				$sql = $sql . " or p.item_name SOUNDS LIKE '" . $q . "'";
			}

			$sql = $sql . ") order by p.item_name,id  limit 100";

			$query = $this->db->query($sql);

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$new_row['label'] = htmlentities(stripslashes($row['batch_no']));
					$new_row['value'] = htmlentities(stripslashes($row['batch_no']));

					//$new_row['sql']=$sql;
					$row_set[] = $new_row; //build an array
				}
				echo json_encode($row_set); //format the array into json data
			} else {
				$new_row['sql'] = $sql;
				$row_set[] = $new_row;
				echo json_encode($row_set);
			}
		}
	}

	public function remove_item_invoice($itemid)
	{
		$this->load->model('Medical_M');

		$user = $this->ion_auth->user()->row();
		$user_update_info = $user->first_name . '' . $user->last_name . '[' . $user->id . ']' . date('d-m-Y H:i:s');

		$sql = "select * from purchase_return_invoice_item where id='" . $itemid . "' ";
		$query = $this->db->query($sql);
		$purchase_return_invoice_item = $query->result();

		$update = 0;
		$msg_box = "";

		$content = "";

		if (count($purchase_return_invoice_item) > 0) {
			$purchase_invoice_item_id = $purchase_return_invoice_item[0]->id;
			$r_qty = $purchase_return_invoice_item[0]->qty;
			$inv_id = $purchase_return_invoice_item[0]->purchase_inv_id;



			$this->Medical_M->delete_purchase_return_invoiceitem($itemid);

			$update = 1;
			$msg_box = "Item Removed";

			$sql = "select p.*,r.purchase_inv_id,r.qty as r_qty,Round(r.qty/p.packing,2) AS qty_pak,r.id as r_id,if(r.batch_no_r='',p.batch_no,r.batch_no_r) as  batch_no_r_s, 
			date_format(p.expiry_date,'%m/%y') as exp_date_str ,p.purchase_unit_rate*r.qty AS r_amount,p.purchase_unit_rate,if(p.CGST_per_old IS NULL,CGST_per,CGST_per_old)*2 AS gst_per
			from purchase_return_invoice_item r join purchase_invoice_item p on r.purchase_item_id=p.id
			where purchase_inv_id=" . $inv_id;
			$query = $this->db->query($sql);
			$data['purchase_return_invoice_item'] = $query->result();

			$content = $this->load->view('Medical/Stock/purchase_return_invoice_item', $data, true);
		} else {
			$update = 0;
			$msg_box = "No item found";
		}

		$rvar = array(
			'update' => $update,
			'msg_text' => $msg_box,
			'content' => $content
		);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	//Transer Sale Item Purchase ID to Other

	public function Expire_stock()
	{
		$sql = "select p.id as pur_id,p.Invoice_no,p.date_of_invoice,
			if(i.expiry_date<date_add(curdate(),interval 3 month),1,0) as isExp,
			date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,
			date_format(i.expiry_date,'%m-%Y') as exp_date,
			p.sid,s.name_supplier,s.short_name,s.gst_no,
			p.T_Net_Amount as tamount,i.*,(i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit) as cur_qty
			from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			join purchase_invoice_item i on p.id=i.purchase_id
			Where  i.remove_item=0 and i.item_return=0
			And (i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit)>0
			and p.date_of_invoice>=date_add(curdate(),interval -1 year)
			and i.expiry_date<date_add(curdate(),interval 3 month)
			order by p.id desc ";

		$query = $this->db->query($sql);
		$data['purchase_list'] = $query->result();

		//echo 'hello';
		$this->load->view('Medical/Stock/expiry_stock_list', $data);
	}

	public function stocktransfer()
	{
		$this->load->view('Medical/Stock/stock_transfer');
	}

	public function merge_product()
	{
		$this->load->view('Medical/Stock/merge_product');
	}

	public function product_info($product_id)
	{
		$sql = "select * from med_product_master where id=$product_id";
		$query = $this->db->query($sql);
		$product_info = $query->result();

		if (count($product_info) > 0) {

			$rvar = array(
				'product_id' => $product_info[0]->id,
				'product_name' => $product_info[0]->item_name,
				'formulation' => $product_info[0]->formulation,
				'genericname' => $product_info[0]->genericname,

			);
		} else {
			$rvar = array(
				'product_id' => 0,
			);
		}

		$encode_data = json_encode($rvar);

		echo $encode_data;
	}

	public function product_merged()
	{
		$from_prod_id = $this->input->post('from_product_id');
		$to_prod_id = $this->input->post('to_product_id');

		$this->load->model('Medical_M');
		$this->Medical_M->merge_product($from_prod_id, $to_prod_id);

		$rvar = array(
			'ssno' => 0,
		);

		$encode_data = json_encode($rvar);

		echo $encode_data;
	}

	public function ssno_info($ssno)
	{
		$sql = "select * from purchase_invoice_item where id=$ssno";
		$query = $this->db->query($sql);
		$product_info = $query->result();

		if (count($product_info) > 0) {
			$total_current_unit = $product_info[0]->total_unit - $product_info[0]->total_sale_unit - $product_info[0]->total_lost_unit + $product_info[0]->total_return_unit;

			$rvar = array(
				'ssno' => $ssno,
				'item_code' => $product_info[0]->item_code,
				'Item_name' => $product_info[0]->Item_name,
				'batch_no' => $product_info[0]->batch_no,
				'purchase_price' => $product_info[0]->purchase_price,
				'tqty' => $product_info[0]->tqty,
				'total_unit' => $product_info[0]->total_unit,
				'total_current_unit' => $total_current_unit,
				'total_sale_unit' => $product_info[0]->total_sale_unit,
			);
		} else {
			$rvar = array(
				'ssno' => 0,
			);
		}

		$encode_data = json_encode($rvar);

		echo $encode_data;
	}

	public function ssno_transfer()
	{
		$from_ssno = $this->input->post('from_ssno');
		$to_ssno = $this->input->post('to_ssno');
		$tqty = $this->input->post('tqty');

		$this->load->model('Medical_M');
		$this->Medical_M->transfer_ssno($from_ssno, $to_ssno, $tqty);

		$rvar = array(
			'ssno' => 0,
		);

		$encode_data = json_encode($rvar);

		echo $encode_data;
	}


	function search_result_tran_print($s_id, $date_range = '')
	{

		if ($date_range == '') {
			$date_range = $this->input->post('led_date_range');
		}

		$rangeArray = explode("S", $date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$where = " date(l.tran_date) BETWEEN '$minRange' AND '$maxRange' ";

		$sql = "Select * from med_supplier where sid=$s_id";
		$query = $this->db->query($sql);
		$data['med_supplier'] = $query->result();

		$data['Statement_between'] = MysqlDate_to_str($minRange) . " AND " . MysqlDate_to_str($maxRange);

		$sql = "  SELECT l.*,
                CONCAT_WS('/',b.bank_account_name,b.bank_name) AS mode_desc
                FROM (med_supplier_ledger l JOIN bank_account_master b ON l.bank_id=b.bank_id)
                Where l.supplier_id=$s_id and $where order by tran_date,l.id ";
		$query = $this->db->query($sql);
		$data['med_supplier_ledger'] = $query->result();


		$sql = "  SELECT  sum(if(credit_debit=0,amount,amount*-1)) as Balance
                FROM med_supplier_ledger l 
                Where l.supplier_id=$s_id and date(l.tran_date) <'$minRange' ";
		$query = $this->db->query($sql);
		$supplier_balance_till_date = $query->result();

		$data['balance_till_date'] = $supplier_balance_till_date[0]->Balance;

		$sql = "  SELECT  sum(if(credit_debit=0,amount,amount*-1)) as Balance,Max(l.tran_date) as Last_date_tran
				FROM med_supplier_ledger l 
                Where l.supplier_id=$s_id and date(l.tran_date) <='$maxRange' ";
		$query = $this->db->query($sql);
		$supplier_balance_till_date = $query->result();

		$data['balance_till_date_close'] = $supplier_balance_till_date[0]->Balance;
		$data['LAst_date_tran'] = $supplier_balance_till_date[0]->Last_date_tran;

		$sql = "  SELECT  sum(if(credit_debit=0,amount,0)) as cr_total,
				sum(if(credit_debit=1,amount,0)) as dr_total
                FROM med_supplier_ledger l 
                Where l.supplier_id=$s_id and $where ";
		$query = $this->db->query($sql);
		$supplier_data_total_cr_dr = $query->result();

		$data['cr_total'] = $supplier_data_total_cr_dr[0]->cr_total;
		$data['dr_total'] = $supplier_data_total_cr_dr[0]->dr_total;

		$data['s_id'] = $s_id;

		$content = $this->load->view('Medical/Purchase/ledger_data_print', $data, True);

		$this->load->library('m_pdf');

		$this->m_pdf->pdf->setBasePath(base_url('/'));
		$file_name = 'Report-Ledger-' . $s_id . '-' . date('Ymdhis') . ".pdf";
		$filepath = $file_name;
		$this->m_pdf->pdf->WriteHTML($content);
		$this->m_pdf->pdf->Output($filepath, "I");
	}

	function Print_bill_on_uhid()
	{
		$this->load->view('Medical/print_bill_on_uhid');
	}

	function uhid_report($date_range, $p_code)
	{
		$rangeArray = explode("S", $date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$sql = "select * from patient_master_exten 
            where  p_code='" . trim($p_code) . "'";
		$query = $this->db->query($sql);
		$data['patient_master'] = $query->result();

		if (count($data['patient_master']) > 0) {
			$pid = $data['patient_master'][0]->id;
		} else {
			$pid = 0;
		}

		if ($pid > 0) {
			$sql = "Select id from invoice_med_master where patient_id=$pid and ipd_credit=0 and case_credit=0 and inv_date between '$minRange' AND '$maxRange'";
			$query = $this->db->query($sql);
			$invoice_med_master = $query->result();

			$content = "";

			$net_amount = "0.0";
			$paid_amount = "0.0";
			$balance_amount = "0.0";

			foreach ($invoice_med_master as $row) {
				$inv_id = $row->id;

				$this->db->query("SELECT f_cash_opd_pre_data(" . $inv_id . ")");
				$this->db->query("CALL f_update_med_GST(" . $inv_id . ")");

				$sql = "select i.inv_med_id,i.id,i.item_Name,i.formulation,
				i.batch_no,Date_Format(i.expiry,'%m-%y')as expiry,
				i.price,i.qty,(i.amount) as amount,i.HSNCODE,
				m.inv_med_code,m.id as m_id,
				date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
				(twdisc_amount) as twdisc_amount,
				(i.disc_amount+i.disc_whole) as d_amt,
				(i.CGST+i.SGST) as gst,
				(i.CGST_per+i.SGST_per) as gst_per ,i.sale_return
				from inv_med_item i join invoice_med_master m
				on i.inv_med_id=m.id
				where  m.id=$inv_id order by i.sale_return,id";
				$query = $this->db->query($sql);
				$data['inv_items'] = $query->result();

				$sql = "select *,
				date_format(inv_date,'%d-%m-%Y') as str_inv_date,
				(discount_amount+item_discount_amount) as inv_disc_total,
				(CGST_Tamount+SGST_Tamount) as TGST 
				from invoice_med_master 
				where  id=$inv_id";
				$query = $this->db->query($sql);
				$data['invoice_med_master'] = $query->result();

				$data['Doc_name'] = "";

				if ($data['invoice_med_master'][0]->doc_id > 0) {
					$doc_id = $data['invoice_med_master'][0]->doc_id;

					$sql = "select * from doctor_master 
                where  id=$doc_id";
					$query = $this->db->query($sql);
					$doctor_master = $query->result();

					$data['Doc_name'] = $doctor_master[0]->p_title . ' ' . $doctor_master[0]->p_fname;
				} else {
					$data['Doc_name'] = $data['invoice_med_master'][0]->doc_name;
				}

				$sql = "select p.*,if(p.credit_debit=0,p.amount,p.amount*-1) as paid_amount,
				Concat((case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' else 'Other' end),if(p.credit_debit=0,'',' Return')) as Payment_type_str
				from payment_history_medical p 
				where p.Customerof_type in (1,3) and Medical_invoice_id=$inv_id";
				$query = $this->db->query($sql);
				$data['payment_history'] = $query->result();

				$net_amount += $data['invoice_med_master'][0]->net_amount;
				$paid_amount += $data['invoice_med_master'][0]->payment_received;
				$balance_amount += $data['invoice_med_master'][0]->payment_balance;

				$content .= $this->load->view('Medical/Print/medical_bill_print_format_single', $data, TRUE);

				$content .= "<hr/>";
			}

			$content .= "Total Net Amount : " . $net_amount . " / ";
			$content .= "Paid Amount : " . $paid_amount . " / ";
			$content .= "Balance Amount : " . $balance_amount;
		} else {
			$content = "No Record found";
		}



		//load mPDF library
		$this->load->library('m_pdf');

		//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

		$this->m_pdf->pdf->SetWatermarkText(M_store);
		$this->m_pdf->pdf->showWatermarkText = false;

		$file_name = 'Report-MedicalBill_' . $inv_id . '_' . date('Ymdhis') . ".pdf";

		$filepath = $file_name;

		$this->m_pdf->pdf->WriteHTML($content);

		//download it.
		$this->m_pdf->pdf->Output($filepath, "I");
	}

	function invoice_item_log()
	{
		$this->load->view('Medical/Stock/invoice_item_log');
	}

	function invoice_item_log_data(){
		$opd_date_range=$this->input->post('opd_date_range');

        $rangeArray = explode("S",$opd_date_range);
		$minRange = str_replace('T',' ',$rangeArray[0]);
		$maxRange = str_replace('T',' ',$rangeArray[1]);
		
		$sql="SELECT m.inv_med_code,m.inv_date,m.inv_name,m.log,
				GROUP_CONCAT(DISTINCT CONCAT_WS('#',d.item_Name,d.qty,d.price,d.twdisc_amount,d.delete_time,d.delete_by) SEPARATOR ';' ) AS del_item_list,
				GROUP_CONCAT(DISTINCT CONCAT_WS('#',p.credit_debit,p.amount,l.update_log) SEPARATOR ';') AS payment_log
			FROM ((invoice_med_master m JOIN inv_med_item_delete d ON d.inv_med_id=m.id)
			LEFT JOIN payment_history_medical p ON m.id=p.Medical_invoice_id)
			Left JOIN paymentmedical_history_log l ON p.id=l.pay_id
			WHERE DATE(d.delete_time) BETWEEN '".$minRange."' and '".$maxRange."' AND m.ipd_id=0
			GROUP BY m.id";

		$query = $this->db->query($sql);
        $data['Invoice_history_log']= $query->result();
        
        //echo $sql;
        $data['opd_date_range']=$opd_date_range;

        $this->load->view('Medical/Stock/invoice_item_log_data',$data);
	}
}
