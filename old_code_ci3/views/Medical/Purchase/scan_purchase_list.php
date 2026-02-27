<?php
for($i=0;$i<count($purchase_invoice_scan_file);++$i)
{
	echo '<div class="col-md-2">
				<div class="thumbnail">';
					$pos=strpos($purchase_invoice_scan_file[$i]->full_path,'/medical_uploads/purchase_invoice',1) ;
					$file_path=substr($purchase_invoice_scan_file[$i]->full_path,$pos);

                  

	//echo $file_path;
	//$pos=strpos($file_path,'/uploads/',1) ;
	//$file_path=str_replace('Hospital_DR_B_C_JOSHI/uploads','uploads',$purchase_invoice_scan_file[$i]->full_path);

	if($purchase_invoice_scan_file[$i]->file_ext=='.pdf')
	{
		echo '<embed src="'.$file_path.'" width="100px" height="100px" type="application/pdf"  ></embed>';
	}else
	{
		echo '<a href="'.$file_path.'" target=_blank>';
		echo ' <img src="'.$file_path.'" class="img-thumbnail" ></a>';
		echo ' <div class="caption">
				<p>'.$purchase_invoice_scan_file[$i]->file_name.'/'.$purchase_invoice_scan_file[$i]->insert_time.'  
				<a class="fa fa-remove" href="javascript:remove_image('.$purchase_invoice_scan_file[$i]->id.')" ></a></p>
				</div>';
		echo '  </div>
			  </div>';

	}
}
?>