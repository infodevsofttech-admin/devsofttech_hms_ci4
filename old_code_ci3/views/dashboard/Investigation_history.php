<!-- Post -->
<?php 
                foreach($invoice_master as $row)
                {
					$sql="select * from  lab_request l 
							where l.charge_id=".$row->id;
					$query = $this->db->query($sql);
					$lab_request= $query->result();

					$sql="select * from file_upload_data 
						where show_type=0 and  pid=$row->attach_id and charge_id=$row->id order by id";
					$query = $this->db->query($sql);
					$opd_file_list= $query->result();

                ?>
<div class="post">
    <div class="user-block">
        <img class="img-circle img-bordered-sm" src="/assets/images/lab_test_icon.png" alt="User Image">
        <span class="username">
            <a href="#">Report On <?=$row->inv_date_str?> / Amount : <?=$row->net_amount?></a>
        </span>
        <span class="description" style="color:black;">Test Name : <?=$row->Item_list?></span>
    </div>

    <div class="row margin-bottom">
        <?php 
								$i=0;
								//Files Show
								foreach($lab_request as $lab_request_row)
								{
									echo '<div class="col-sm-12 investigation_show">';
									echo $lab_request_row->Report_Data;	
									echo '</div>';
								}
						?>
    </div>
    <!-- /.user-block -->
    <div class="row margin-bottom">
        <?php 
			$i=0;
			//Files Show
			foreach($opd_file_list as $opd_file_row)
			{
				$i=$i+1;
				echo '<div class="col-sm-12">';
				$pos=strpos($opd_file_row->full_path,'/uploads/',1) ;
				$file_path=substr($opd_file_row->full_path,$pos);

				//$file_path=str_replace('hms_uploads','uploads',$opd_file_row->full_path);
			
				if($opd_file_row->file_ext=='.pdf')
				{
					echo '<embed class="img-responsive" src="'.$file_path.'" width="800px" height="1000px" type="application/pdf"  ></embed>';
				}else
				{
					echo '<img class="img-responsive" id="img_opd_'.$i.'" class="responsive"  src="'.$file_path.'"   
						alt="Photo" >';
				}
			
				echo '</div>';
			}                  
			
			?>
    </div>
    <!-- /.row -->
</div>

<?php } ?>
<!-- /.post -->