<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Testcont extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
    }
  
    public function bar1($bar_content) {
		
		$this->load->library("Barcode");

		$bar_content_bar=rawurldecode($bar_content); 
		
		$barcodeobj = new TCPDFBarcode($bar_content_bar, 'C128');
		$barcode = $barcodeobj->getBarcodePNG(2, 30, array(0,128,0));
		echo $barcode;
	
	}
	
	public function bar2($bar_content) {
		
		$this->load->library("Barcode2");

		$bar_content_bar=rawurldecode($bar_content); 
		
		$barcodeobj = new TCPDF2DBarcode($bar_content_bar, 'QRCODE,L');
		$barcode = $barcodeobj->getBarcodePNG();
		echo $barcode;
	}

	public function test_upload()
	{
		 $this->load->view('testcode/test_1');
	}
	
	public function save_image($user_id)
	{
		$filename =  $user_id.time() . '.jpg';
		$filepath = '/uploads/';
		
		$config['upload_path'] = 'uploads';
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		
		$new_name = time().$_FILES["webcam"]['name'];
		$config['file_name'] = $new_name;
		 
		$this->load->library('upload', $config);
		if (!$this->upload->do_upload('webcam')) {
			$error = array('error' => $this->upload->display_errors());
			echo $error['error'];
		}
	}


	public function testdir($file_v_no)
	{
		$sql="select * from file_opd_rec where v_no=$file_v_no";
        $query = $this->db->query($sql);
        $chk_file= $query->result();

        if(count($chk_file)>0)
        {
            $full_path=$chk_file[0]->full_path;
            $start_no=stripos($full_path,$file_v_no);
            $end_no=strlen($full_path);

            $folder_name=substr($full_path,0,$start_no);

            $files_info = get_dir_file_info($folder_name);

			Echo '<pre>';
			print_r($files_info);
			Echo '</pre>';

			foreach($files_info as $key => $value) {
				$find_file=number_format(substr_count($value['name'],$file_v_no),0);
				if($find_file==1)
				{
					echo ' File Name :'.$value['name'].' / Find File '.$file_v_no.'<br/>';
					$index_key=str_replace($file_v_no.'_','',$value['name']);
					$index_key=str_replace('.webm','',$index_key);

					$file_list[$index_key] = $value['name'];
				}
			}

			ksort($file_list);

			echo 'No. of Files :'. count($file_list);

			$prev_file='';
			$current_file='';
			foreach($file_list as $key => $value) {
				$current_file=$folder_name.'/'.$value;
				if(strlen($prev_file)>0)
				{
					//First File
					$objFH = fopen( $prev_file, "rb" );
					$strBuffer1 = fread( $objFH, filesize( $prev_file) );
					fclose( $objFH );
		
					//Second File
					$objFH = fopen( $current_file, "rb" );
					$strBuffer2 = fread( $objFH, filesize( $current_file) );
					fclose( $objFH );

					// manipulate buffers here...
					$strBuffer3 = $strBuffer1 . $strBuffer2;

					// open for write/binary-safe
					$objFH = fopen( $current_file, "wb" );
					fwrite( $objFH, $strBuffer3 );
					fclose( $objFH );

					//Delete prev File
					unlink($prev_file);
				}

				$prev_file=$current_file;
			}

			Echo '<pre>';
			print_r($file_list);
			Echo '</pre>';


        }
		
	}

	public function test_math()
	{
		Test_exp("2+5*3");
	}
	
}