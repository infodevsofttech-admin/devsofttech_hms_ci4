<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');

function send_sms($phoneno,$data_sms)
{
    $apiKey = urlencode('JSZTJ26/aN0-FDJmACa99KbaoAFHSHlUu9IucXy2py');
	// Message details
	$numbers = $phoneno;
	$sender = urlencode('DSTHMS');
	$message = rawurlencode($data_sms);
	//$numbers = implode(',', $numbers);
	// Prepare data for POST request
	$data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);
	// Send the POST request with cURL
	$ch = curl_init('https://api.textlocal.in/send/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);

}

$conn_main=mysqli_connect("localhost","root","Bisht@9720958717","main_website") or die("Unable to connect to Main Server");

if (mysqli_connect_errno())
{
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// New Patient Create in HMS

$sql = "select  * from patient_master p where  p.p_id=0  order by id limit 1";

$result = mysqli_query($conn_main,$sql) or die(mysqli_error($conn_main));

$flag=0;

while($row = mysqli_fetch_array($result))
	{
		$hospital_id=$row['hospital_id'];
        $p_fname =$row['p_fname'];
        $p_relative = $row['p_relative'];
		$p_rname= $row['p_rname'];
        $gender= $row['gender'];
        $dob= $row['dob'];
        $mphone1= $row['mphone1'];
        

        $sql="select * from hospital_client where hospital_id=".$hospital_id;
        $hospital_data = mysqli_query($conn_main,$sql) or die(mysqli_error($conn_main));
        
        while($row_hospital = mysqli_fetch_array($hospital_data))
        {
            $hospital_name=$row_hospital['hospital_name'];
            $online_url=$row_hospital['online_url'];
            $webservice_url=$row_hospital['webservice_url'];
            $vpn_server_id=$row_hospital['vpn_server_id'];

            $data = array( 
                'mphone1' => $mphone1, 
                'p_fname' => strtoupper($p_fname),
                'gender' => $gender,
                'p_relative' => $p_relative,
                'p_rname' => strtoupper($p_rname),
                'dob' => $dob,
                'estimate_dob' => 1
            );

            $data_json = json_encode($data);

            $post_url=$webservice_url.'/Opd_online_api/patient_create';
            echo $post_url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $post_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            curl_close($ch);

            $retrun_data=json_decode(utf8_encode($response));

            var_dump($retrun_data);
        }

        $update_sql="update patient_master 
                    set p_id=".$retrun_data->New_UHID.",
                    p_code='".$retrun_data->p_code."'
                    where id=".$row['id'];

        echo $update_sql;

        mysqli_query($conn_main,$update_sql);

    }

// New Patient Created End

//Update Patient_Id in OPd_BOOK Table
$update_sql="UPDATE patient_master p JOIN opd_book b ON p.id=b.online_patient_id
SET b.hospital_patient_id=p.p_id
WHERE p.p_id>0 AND b.hospital_patient_id=0";

mysqli_query($conn_main,$update_sql);

//Update Payment Info in OPD_BOOK Table

$update_sql="UPDATE  opd_book b JOIN payments p ON p.product_id=b.id 
SET b.pg_id=p.id
WHERE b.pg_id IS null";

mysqli_query($conn_main,$update_sql);

// OPD Registering Process Start here

$sql="select * from opd_book 
where  pg_id>0 and (hospital_opd_id=0 or hospital_opd_id is null)";

$opd_book_data = mysqli_query($conn_main,$sql) or die(mysqli_error($conn_main));

while($row_opd_book = mysqli_fetch_array($opd_book_data))
{
    $hospital_patient_id=$row_opd_book['hospital_patient_id'];
    $hospital_doc_id=$row_opd_book['hospital_doc_id'];
    $appointment_date=$row_opd_book['appointment_date'];
    $opd_fee=$row_opd_book['opd_fee'];
    $hospital_id=$row_opd_book['hospital_id'];

    $sql="select * from hospital_client where hospital_id=".$hospital_id;
    $hospital_data = mysqli_query($conn_main,$sql) or die(mysqli_error($conn_main));

    while($row_hospital = mysqli_fetch_array($hospital_data))
    {
        $hospital_name=$row_hospital['hospital_name'];
        $online_url=$row_hospital['online_url'];
        $webservice_url=$row_hospital['webservice_url'];
        $vpn_server_id=$row_hospital['vpn_server_id'];

        $data = array( 
            'hospital_patient_id' => $hospital_patient_id, 
            'hospital_doc_id' => $hospital_doc_id,
            'appointment_date' => $appointment_date,
            'opd_fee'=> $opd_fee
        );
    
        $data_json = json_encode($data);
    
        $post_url=$webservice_url.'/Opd_online_api/opd_register';
        echo $post_url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        curl_close($ch);
    
        $retrun_data=json_decode(utf8_encode($response));
    
        var_dump($retrun_data);
    
        $update_sql="update opd_book 
                        set hospital_opd_id=".$retrun_data->OPD_ID."
                        where id=".$row_opd_book['id'];
        
        mysqli_query($conn_main,$update_sql);

        //Send SMS

        $sms_template="Your OPD Appointment with Dr. #DrName# has been Booked.
                        <br/>Booked No : #BookNo#.
                        <br/>Date: #date# 
                        <br/>Time Between #time#
                        <br/>Place: #HospitalName#
                        <br/>Thanks DevSoft Tech";

        $sql="Select * from opd_slot where slot_id=".$row_opd_book['slot_id'];
        $opd_slot = mysqli_query($conn_main,$sql) or die(mysqli_error($conn_main));

        if($row_opd_slot = mysqli_fetch_array($opd_slot))
        {
            $str_time=$row_opd_slot['slot_desc'];
        }else{
            $str_time=' ';
        }

        $sms_template=str_replace('#DrName#',$retrun_data->DOC_NAME,$sms_template);
        $sms_template=str_replace('#BookNo#',$retrun_data->BOOKNO,$sms_template);
        $sms_template=str_replace('#date#',$retrun_data->BOOKDATE,$sms_template);
        $sms_template=str_replace('#time#',$str_time,$sms_template);
        $sms_template=str_replace('#HospitalName#',$hospital_name,$sms_template);

        $mphone1=$retrun_data->PHONENO;
        
        $sms_send="insert into sms_outbox (hospital_id,phone_number,content)
                    values ($hospital_id,$mphone1,'$sms_template')";

        $write_web_sms="insert into web_msg_content (web_content,web_content_key,msg_insert_date,hospital_id,hospital_name,phone_no) 
        values ('".$data_sms."','Medicine',sysdate(),1,'Krishna Pharmcy','".$sender_number."')";

        if ($conn_main->query($sms_send) === TRUE) {
            $last_id = $conn_main->insert_id;
            echo "New record created successfully. Last inserted ID is: " . $last_id;
    
            $url_link="https://m.dhms.in/msg/show/".$last_id;
            $from_hospital=$hospital_name;
    
            //Send SMS End
        
            $insert_sms_sql="INSERT INTO sms_fast2sms (hospital_id, sender_id, message, variables_values, route, numbers) 
                    VALUES (".$hospital_id.", 'DSTWEB', 160545, '".$hospital_name."|".$url_link."', 'dlt', '".substr($mphone1,-10)."')";
    
            echo $insert_sms_sql;
        
            mysqli_query($conn_main,$insert_sms_sql);
            
        } else {
            echo "Error: " . $sms_send . "<br>" . $conn->error;
        }
              

        

        

        
    

        echo $sms_send;

        mysqli_query($conn_main,$sms_send);
    }
    
}


// OPD Registered , Process End Here
mysqli_close($conn_main);
//echo date("l jS \of F Y h:i:s A");
