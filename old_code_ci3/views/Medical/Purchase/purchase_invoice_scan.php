<div class="row">
	<div class="col-md-12">
		<p><strong>Inv ID :</strong><?=$purchase_invoice[0]->id?>  
        <strong>/ Inv Code :</strong><?=$purchase_invoice[0]->Invoice_no?> 
        
	</div>
</div>
<div class="row">
	<div class="col-md-2">
		<div id="camera"></div>
		<br />
		<button id="capture_btn" name="capture_btn" type="button"  class="btn btn-warning" onClick="take_snapshot()">Capture</button>
		<input type="hidden" id="purchase_inv_id" value="<?=$purchase_inv_id ?>">
	</div>
	<div class="col-md-6">
		<div id="results"></div>
	</div>
	<div class="col-md-4">
          <div class="box box-danger">
            <div class="box-header">
              <h3 class="box-title">Loading state</h3>
            </div>
            <div class="box-body" id="file_upload_list">
            </div>
            <!-- /.box-body -->
            <!-- Loading (remove the following to stop the loading)-->
            <div id="load_div"></div>
			
            <!-- end loading -->
          </div>
          <!-- /.box -->
    </div>
</div>
<div class="row">
	<hr/>
	<div id="last_opd_pic" ></div>
</div>
	<!-- show captured image -->
	
	<script>
		function enable_btn()
		{
			$('#capture_btn').attr('disabled', false);
		}

		$(function(){
				Webcam.set({
				width: 160,
				height: 120,
				dest_width: 1280,
				dest_height: 720,
				image_format: 'jpeg',
				jpeg_quality: 90,
				enable_flash: false,
				minWidth:600,
			});
			Webcam.attach('#camera');
	
		});


		function take_snapshot() {
			// take snapshot and get image data
			$('#capture_btn').attr('disabled', true);
			Webcam.snap( function(data_uri) {
				// display results in page
				
				var purchase_inv_id=$('#purchase_inv_id').val();

				document.getElementById('results').innerHTML = 
					'Captured Image<br/>' + 
					'<img src="'+data_uri+'" width="320" />';

				Webcam.on( 'uploadProgress', function(progress) {
					document.getElementById('load_div').innerHTML = "<div class='overlay'><i class='fa fa-refresh fa-spin'></i></div>";
				} );
				
				Webcam.on( 'uploadComplete', function(code, text) {
					document.getElementById('load_div').innerHTML = ""
					// Upload complete!
					// 'code' will be the HTTP response code from the server, e.g. 200
					// 'text' will be the raw response content
					var theDiv = document.getElementById("file_upload_list");
					theDiv.innerHTML ='<br/>'+text; 
					//load_image();
				} );
				
				Webcam.upload( data_uri, 'Medical/save_image/'+purchase_inv_id);
				
			} );
			setTimeout(load_image,2000);
			setTimeout(enable_btn,2000);
			
		}

		function load_image()
		{
			var purchase_inv_id=$('#purchase_inv_id').val();
			load_form_div('/Medical/purchase_file_last_list/'+purchase_inv_id,'last_opd_pic');
		}
	
		function remove_image(file_id)
		{
            var purchase_inv_id=$('#purchase_inv_id').val();
			if(confirm("Are you Delete this File"))
			{
				load_form_div('/index.php/Medical/purchase_file_hide/'+file_id+'/'+purchase_inv_id,'last_opd_pic');
			}
		}

		load_image();
	</script>	