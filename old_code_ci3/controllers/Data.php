<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Data extends MY_Controller
{    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function index()
    {
		$sql="select * from payment_mode";
		$query = $this->db->query($sql);
		$data['pay_mode']= $query->result();
		
		$this->load->view('Report/opd_report_v',$data);
    }

	public function getTable()
	{
	
		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 =>'opd_code', 
			1 => 'P_name',
			2 => 'p_code',
			3=> 'App_Date',
			4=> 'doc_name',
			5=> 'Inv_Type',
			6=> 'PaymentMode',
			7=> 'opd_fee_amount'
			);

		// getting total number records without any search
		$sql_f_all = "select o.opd_code,o.P_name,o.p_id,Date_Format(o.apointment_date,'%d-%m-%Y') as App_Date,
		o.doc_name,if(o.insurance_id>1,'Org.','Direct') as Inv_Type,p_code,
		o.insurance_id,o.insurance_credit,
		m.mode_desc as PaymentMode,o.payment_mode,
		o.opd_fee_gross_amount,o.opd_discount,o.opd_fee_amount,o.apointment_date,o.payment_mode";
	
		$sql_count="Select count(*) as no_rec,sum(o.opd_fee_amount) as t_opd_fee_amount ";

		$sql_from=" from opd_master o join payment_mode m join patient_master p on o.payment_mode=m.id and p.id=o.p_id";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;
				
		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1 = 1";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //name
			$sql_where.=" AND o.opd_code LIKE '%".$requestData['columns'][0]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][1]['search']['value']) ){  //salary
			$sql_where.=" AND o.P_name LIKE '%".$requestData['columns'][1]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		  
		if( !empty($requestData['columns'][3]['search']['value']) ){  //salary
			$sql_where.=" AND o.doc_name LIKE '%".$requestData['columns'][3]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][2]['search']['value']) ){  //salary
			$sql_where.=" AND p_code LIKE '%".$requestData['columns'][2]['search']['value']."%' ";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][4]['search']['value']) ){ //age
			
			if($requestData['columns'][4]['search']['value']=='1')
			{
				$sql_where.=" AND o.insurance_id<=1 ";
			}else
			{
				$sql_where.=" AND o.insurance_id>1 ";
			}
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][5]['search']['value'])){ //age
			$sql_where.=" AND o.payment_mode=".$requestData['columns'][5]['search']['value'];
			$sql_where_flag=1;
		}elseif($requestData['columns'][5]['search']['value']=='0')
		{
			$sql_where.=" AND o.payment_mode=".$requestData['columns'][5]['search']['value'];
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][6]['search']['value'])  )
		{ //OPD Date Range
			$rangeArray = explode("/",$requestData['columns'][6]['search']['value']);
			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];
			$sql_where.=" AND ( Date(o.apointment_date) between '".$minRange."' AND '".$maxRange."' ) ";
			$sql_where_flag=1;
		}
		
		if($sql_where_flag==0)
		{
			$sql_where.=" AND Date(o.apointment_date) = curdate() ";
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;
		$foot_t_sum=$data['total_rec_filter'][0]->t_opd_fee_amount;

		$sql_order=" ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
		$Result_sql=$sql_f_all.$sql_from.$sql_where.$sql_order;
		
		$query = $this->db->query($Result_sql);
		$rdata= $query->result_array();

		$output = array(
				"draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
				"recordsTotal"    => intval( $totalData ),  // total number of records
				"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
				"data"            => array(),
				"foot_t_sum" => $foot_t_sum,
				"sql" => $Result_sql
				);

		foreach($rdata as $aRow)
		{
			$row = array();
			
			foreach($columns as $col)
			{
				$row[] = $aRow[$col];
			}

			$output['data'][] = $row;
		}
		
		echo json_encode($output);  // send data as json format

	}
	
}
?>