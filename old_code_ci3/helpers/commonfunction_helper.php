<?php defined('BASEPATH') OR exit('No direct script access allowed');

function str_to_MysqlDate($strDate)
{
    if($strDate!="")
	{
		$date = explode('/',$strDate);
		$mysqlDate = $date[2].'-'.$date[1].'-'.$date[0];
	}
	else{
		$mysqlDate='1900-01-01';
	}
    return $mysqlDate;
}

function MysqlDate_to_str($strDate)
{
    $date = explode('-',$strDate);
    $mysqlDate = $date[2].'/'.$date[1].'/'.$date[0];
    return $mysqlDate;
}   

function radio_checked($rvalue,$dvalue)
{
	if($rvalue==$dvalue)
	{
		return "Checked";
	}
	else{
		return "";
	}
}

function combo_checked($rvalue,$dvalue)
{
	if(strtoupper($rvalue)==strtoupper($dvalue))
	{
		return "Selected";
	}
	else{
		return "";
	}
}

function checkbox_checked($value)
{
	if($value>0)
	{
		return "checked";
	}
	else{
		return "";
	}
}

function Doc_Value($doc_id)
{
    return 'Hello';
}  

function Show_Alert($alert_type,$Head_title,$Body_content)
{
	$showAlert='<div class="alert alert-'.$alert_type.'" id="alert_show">
							<button type="button" class="close" data-dismiss="alert">x</button>
							<strong> '.$Head_title.'</strong> '.$Body_content.' </div>';
	
	return $showAlert;
}

function number_to_word($number)
{
	$no = round($number);
   $point = round($number - $no, 2) * 100;
   $hundred = null;
   $digits_1 = strlen($no);
   $i = 0;
   $str = array();
   $words = array('0' => '', '1' => 'one', '2' => 'two',
    '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six',
    '7' => 'seven', '8' => 'eight', '9' => 'nine',
    '10' => 'ten', '11' => 'eleven', '12' => 'twelve',
    '13' => 'thirteen', '14' => 'fourteen',
    '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen',
    '18' => 'eighteen', '19' =>'nineteen', '20' => 'twenty',
    '30' => 'thirty', '40' => 'forty', '50' => 'fifty',
    '60' => 'sixty', '70' => 'seventy',
    '80' => 'eighty', '90' => 'ninety');
   $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
   while ($i < $digits_1) {
     $divider = ($i == 2) ? 10 : 100;
     $number = floor($no % $divider);
     $no = floor($no / $divider);
     $i += ($divider == 10) ? 1 : 2;
     if ($number) {
        $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
        $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
        $str [] = ($number < 21) ? $words[$number] .
            " " . $digits[$counter] . $plural . " " . $hundred
            :
            $words[floor($number / 10) * 10]
            . " " . $words[$number % 10] . " "
            . $digits[$counter] . $plural . " " . $hundred;
     } else $str[] = null;
  }
  $str = array_reverse($str);
  $result = implode('', $str);
  $points = ($point) ?
    "." . $words[$point / 10] . " " . 
          $words[$point = $point % 10] : '';
		  
		  
	$rvalue="".ucwords($result)." Only";
	return $rvalue;
	
}

function ExportExcel($table,$filename)
{
		
		//header("Content-type: application/x-msdownload"); 
		//header('Content-Disposition: attachment; filename="filename.xls"');

        // Sending headers to force the user to download the file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'_'.date('dMy').'.xls"');
        header('Cache-Control: max-age=0');
		header("Pragma: no-cache");
		header("Expires: 0");
 
        echo $table;
}

function objectToArray($d) {
	if (is_object($d)) {
		// Gets the properties of the given object
		// with get_object_vars function
		$d = get_object_vars($d);
	}

	if (is_array($d)) {
		/*
		* Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return array_map(__FUNCTION__, $d);
	}
	else {
		// Return array
		return $d;
	}
}

function compare_arrays($data1,$data2)
   {
   		$data_change="";

   		foreach ($data2 as $key => $value)
   		{
   			
   			if((isset($data1[$key])) && (strtoupper($key)!='LOG'))
   			{
   				if($value!=$data1[$key])
				{
					$data_change.='Field :'.$key.' Old Value:{'.$data1[$key].'} => New Value:{'.$value.'}'.PHP_EOL;
				}
   			}
   			
   		}

   		return $data_change;
   }

   function cal_exp($exp)
    {
        //$string = "(11+10)*3";
        $string = $exp;
        $result="Not Valid";
        $pattern = '~^[0-9()+\-*\/.]+$~';
        if(preg_match($pattern, $string)){
            $math_string ="return (".$string.");";
			$result = floatval(@eval($math_string));
        }

		if(is_numeric($result)){
			round($result,2);
		}
                
        return $result;
    }
	
	
	 
	

	
	


   
	
	
	


    


	





	
	
?>