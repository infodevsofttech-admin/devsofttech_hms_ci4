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
						<canvas id="canvas" width=100 height=100></canvas>
                        <input type="file" name="userfile" id="userfile" class="form-control" accept="image/*;capture=camera" onchange="onchange_file_upload()" />
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
    function onchange_file_upload () {
        
        var file=$('#userfile').prop("files")[0];
        alert('File Change');
        //var file = input.files[0];
        //upload(file);
        drawOnCanvas(file);   // see Example 6
        //displayAsImage(file); // see Example 7
    }

    function drawOnCanvas(file) {
        var reader = new FileReader();
        reader.onload = function (e) {
        var dataURL = e.target.result,
            c = document.querySelector('canvas'), // see Example 4
            ctx = c.getContext('2d'),
            img = new Image();
    
        
            img.onload = function() {
                //c.width = img.width;
                //c.height = img.height;
                //ctx.drawImage(img, 0, 0);
                
                c.width = 300;
                c.height = 300;
                ctx.drawImage(img,0,0,img.width,img.height,0,0,400,300);
            };
    
            img.src = dataURL;
        };
    
        reader.readAsDataURL(file);
    }
    
    function dataURItoBlob(dataURI) {
        // convert base64/URLEncoded data component to raw binary data held in a string
        var byteString;
        if (dataURI.split(',')[0].indexOf('base64') >= 0)
            byteString = atob(dataURI.split(',')[1]);
        else
            byteString = unescape(dataURI.split(',')[1]);

        // separate out the mime component
        var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

        // write the bytes of the string to a typed array
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        return new Blob([ia], {type:mimeString});
    }

    $(document).ready(function(){
        
        $('#opd_form').on('submit', function(form){
            form.preventDefault();

            ourl='/Opd/save_image_mobile/<?=$opd_id?>';
            var opd_id=$('#opd_id').val();
			
            var file_data=$('#userfile').prop("files")[0];

            // Generate the image data
            var dataURL  = document.getElementById("canvas").toDataURL('image/jpeg', 0.5);

            //dataURL = dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
            //var file = dataURItoBlob(dataURL);
            
            var csrf_name='<?php echo $this->security->get_csrf_token_name(); ?>';
            var csrf_value='<?php echo $this->security->get_csrf_hash(); ?>';
            var form_data=new FormData();

            form_data.append("userfile",dataURL);
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