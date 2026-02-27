<div class="row">
<?php

	if($lab_file_details[0]->file_ext=='.pdf')
	{
		$pos=strpos($lab_file_details[0]->full_path,'/uploads/',1) ;
		
		$file_path=substr($lab_file_details[0]->full_path,$pos);
		
		echo '<embed src="'.$file_path.'" width="800px" height="2100px" />';
	}else
	{
		$pos=strpos($lab_file_details[0]->full_path,'/uploads/',1) ;
		
		$file_path=substr($lab_file_details[0]->full_path,$pos);
		
		echo '<img src="'.$file_path.'" '.$lab_file_details[0]->image_size_str.' ></img>';
	}
?>
</div>