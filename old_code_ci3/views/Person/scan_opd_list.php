<?php
for($i=0;$i<count($opd_file_list);++$i)
{
	echo '<div class="col-md-2">
				<div class="thumbnail">';
					$pos=strpos($opd_file_list[$i]->full_path,'/uploads',1) ;
					$file_path=substr($opd_file_list[$i]->full_path,$pos);

	//echo $file_path;
	//$pos=strpos($file_path,'/uploads/',1) ;
	//$file_path=str_replace('Hospital_DR_B_C_JOSHI/uploads','uploads',$opd_file_list[$i]->full_path);
	
	if($opd_file_list[$i]->file_ext=='.pdf')
	{
		echo '<embed src="'.$file_path.'" width="100px" height="100px" type="application/pdf"  ></embed>';
	}else
	{
		echo '<a href="'.$file_path.'" target=_blank>';
		echo ' <img src="'.$file_path.'" class="img-thumbnail" ></a>';
		echo ' <div class="caption">
				<p>'.$opd_file_list[$i]->p_fname.'/'.$opd_file_list[$i]->strinsert_date.'  
				<a class="fa fa-remove" href="javascript:remove_image('.$opd_file_list[$i]->id.')" ></a></p>
				</div>';
		echo '  </div>
			  </div>';

	}
}
?>