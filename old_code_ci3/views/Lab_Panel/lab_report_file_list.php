<div class="row">
<p>Created Report History</p>

<?php
	foreach($lab_file_list as $row)
	{
		$pos=strpos($row->full_path,'/hms_uploads/',1) ;
		
		//$file_path=substr($row->full_path,$pos);

		$file_path=str_replace('hms_uploads','uploads',$row->full_path);
		
		echo '<p class="text-muted">';
		echo '<strong>'.$row->file_desc.'</strong>  ';
		echo " [<a href='".$file_path."' target=_blank>".$row->file_name."</a>]";
		echo '<i> / Created :'.$row->insert_time.'</i>  ';
		echo '</p>';
	}
?>
</div>