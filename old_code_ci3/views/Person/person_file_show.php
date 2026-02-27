<section class="content-header">
   <h1>
        <?=ucwords($data[0]->p_fname); ?> 
        <small><a href="javascript:load_form('/Patient/person_record/<?=$data[0]->id?>/0');"><?=$data[0]->p_code; ?></a></small>
    </h1>
</section>
<!-- Main content -->
    <section class="content">
<style>
	.rotateimg180 {
	  -webkit-transform:rotate(90deg);
	  -moz-transform: rotate(90deg);
	  -ms-transform: rotate(90deg);
	  -o-transform: rotate(90deg);
	  transform: rotate(90deg);
	 
	}
	.responsive {
  width: 100%;
  max-width: 400px;
  height: auto;
  padding: 2px;
}
	
</style>

<?php
		$c=$data[0];
	?>
	<div class="row">
        <div class="box box-primary">
            <div class="box-header with-border">
                <span class="text-danger"> <i class="fa fa-address-card "></i>
                     <?php echo $c->title.' '.$c->p_fname ; ?> </span> <br/>{<span class="text-success"><?=$c->p_relative?> </span> <?=$c->p_rname?>}
            </div>
            <div class="box-body box-profile">
                <div class="col-xs-8">
                    <p><strong><i class="fas fa-hourglass "></i> <?php echo $c->str_age; ?></strong></p>
                    <p><strong><i class="fas fa-barcode "></i> <?php echo $c->p_code; ?></strong></p>
                    <p>
                        <strong ><i class="fas fa-phone"></i> 
                            <a href="tel:<?=$c->mphone1?>">
                                <?php echo $c->mphone1; ?>
                            </a>
                        </strong>
                        <strong class="text-info"><i class="fa fa-whatsapp margin-r-5"></i> <?php echo $c->mphone1; ?> </strong>
                    </p>
                    <a href="javascript:load_form_div('/Mobile_app/show_profile_opd/<?=$c->id?>','search_result')" class="btn btn-info btn-xs">
                        <span class="fa fa-eye"></span> View</a>
                </div>
                <div class="col-xs-4">
                    <?php
                        $pos=strpos($c->profile_picture,'/uploads/',1) ;
                        $profile_file_path=substr($c->profile_picture,$pos);
                    ?>
                    <img src="<?=$profile_file_path?>"  width="100px" />'
                </div>
            </div>
        </div>
    </div>
	<?php 
			$opd_id="";

            for($i=0;$i<count($opd_file_list);++$i)
			{
				$opd_id_new=$opd_file_list[$i]->opd_id;

                if($opd_id!=$opd_id_new)
                {
                    $sql="select p.*,o.opd_code,o.doc_name from opd_prescription p 
                    join opd_master o on o.opd_id=p.opd_id 
                    where p.opd_id=$opd_id_new";
                    $query = $this->db->query($sql);
                    $opd_data= $query->result();

                    if(count($opd_data)>0)
                    {
                        echo '<div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-8 ">
                                    <hr/>
                                </div>
                            </div>'; 

                        echo '<div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-8 ">';
                        
                        echo '<b>OPD ID :</b>'.$opd_data[0]->opd_code.' /<b>Doctor Name :</b>Dr. '.$opd_data[0]->doc_name;
                        echo '/<b>OPD Date :</b>'.$opd_data[0]->date_opd_visit.' /<b>Queue No.:</b>'.$opd_data[0]->queue_no.'<br/>';

                        if($opd_data[0]->bp!='')
                        {
                            echo '<b>BP :</b> '.$opd_data[0]->bp.' ';
                        }

                        if($opd_data[0]->diastolic!='')
                        {
                            echo '<b>Diastolic :</b> '.$opd_data[0]->diastolic.' ';
                        }

                        if($opd_data[0]->pulse!='')
                        {
                            echo '<b>Pulse :</b> '.$opd_data[0]->pulse.' ';
                        }

                        if($opd_data[0]->temp!='')
                        {
                            echo '<b>Temp. :</b> '.$opd_data[0]->temp.' ';
                        }

                        echo '</div>
                            </div>'; 
                    }
                }

                $opd_id=$opd_id_new;

                echo '<div class="row">
						<div class="col-md-4 ">';

					$pos=strpos($opd_file_list[$i]->full_path,'/uploads/',1) ;
	
					$file_path=substr($opd_file_list[$i]->full_path,$pos);

					//$file_path=str_replace('hms_uploads','uploads',$opd_file_list[$i]->full_path);
					
					if($opd_file_list[$i]->file_ext=='.pdf')
					{
						echo '<embed src="'.$file_path.'" width="800px" height="2100px" type="application/pdf"  ></embed>';
					}else
					{
						echo '<img id="img_opd_'.$i.'" class="responsive"  src="'.$file_path.'"   alt="Photo" data-toggle="modal" data-target="#myModal"
                            data-src="'.$file_path.'"
                            data-img_id="'.$i.'" >';
					}
				
				echo '</div>
					</div>';
			}
	?>
  </section>
  <!-- The Modal -->
<div id="myModal" class="modal modal-wide fade" >
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" >OPD Scan Page</h4>
      </div>
      <div class="modal-body">
        <div style="width:100%;overflow:auto;">
            <img class="modal-content" id="img01" >
        </div>
    </div>
  </div>
</div>
<script>
    

    $('#myModal').on('shown.bs.modal', function (event) {
                   
            var button = $(event.relatedTarget);
            // Button that triggered the modal
            
            var src = button.data('src');
            var img_id = button.data('img_id');
            
            var img = document.getElementById('img_opd_'+img_id);
            var modalImg = document.getElementById("img01");
            
            modalImg.src = img.src;           
                        
        
        });
    
        $('#tallModal_3').on('hidden.bs.modal', function () {
            $('#tallModal_3-bodyc').html('');
            $('#tallModal_3Label').html('');
        });


</script>
