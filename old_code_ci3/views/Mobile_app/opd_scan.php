<style>
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
				<?php 
					$attributes = array('id' => 'opd_form');
					echo form_open_multipart('/Opd/save_image_mobile/'.$opd_id,$attributes );
				?>
				<div id="file_update" class="col-xs-12">
					<input type="hidden" name="opd_id"  id="opd_id" value="<?=$opd_id?>" />
					<div class="col-xl-12">
						<label for="userfile" class="control-label">Capture or Upload Photo</label>
						<input type="file" name="userfile" id="userfile" class="form-control" accept="image/*;capture=camera" />
					</div>
					<div class="col-xl-12">
						<input type="submit" value="upload" />
					</div>
				</div>   
				<?php
				   echo form_close();
				?>
            </div>
        </div>
    </div>
	
<script>
    $(document).ready(function(){
        
        $('#opd_form').on('submit', function(form){
            form.preventDefault();

            ourl='/Opd/save_image_mobile/<?=$opd_id?>';
            var opd_id=$('#opd_id').val();
			
            var file_data=$('#userfile').prop("files")[0];
            
            var csrf_name='<?php echo $this->security->get_csrf_token_name(); ?>';
            var csrf_value='<?php echo $this->security->get_csrf_hash(); ?>';
            var form_data=new FormData();

            form_data.append("userfile",file_data);
            form_data.append("opd_id",opd_id);
            form_data.append(csrf_name,csrf_value);

            $.ajax({
		        url:ourl,
		        dataType:"script",
		        cache:false,
		        contentType:false,
		        processData:false,
		        data:form_data,
		        type:"POST",
		        beforeSend: function()
		        {
		           $("#file_update").html('Uploading.... Wait');
		        }
		        }).done(function(html){

            		$("#file_update").html(html);
		         });
        });
	});
</script>