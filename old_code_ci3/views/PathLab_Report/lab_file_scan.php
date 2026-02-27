<?php echo form_open(); ?>
<div class="row">
	<div class="col-md-2">
		<div id="camera"></div>
		<br />
		<button id="capture_btn" type="button"  onClick="take_snapshot()">Capture</button>
		<input type="hidden" id="repo_id" value="<?=$repo_id ?>">
		<input type="hidden" id="test_id" value="<?=$test_id ?>">
	</div>
	<div class="col-md-6">
		<div id="results">Your captured image will appear here...</div>
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
	<!-- show captured image -->
<?php echo form_close(); ?>
	<script>
		$(function(){
				Webcam.set({
				width: 160,
				height: 120,
				dest_width: 640,
				dest_height: 480,
				image_format: 'jpeg',
				jpeg_quality: 90,
				enable_flash: false
			});
			Webcam.attach( '#camera' )
	
		});
		
		function take_snapshot() {
			// take snapshot and get image data
			Webcam.snap( function(data_uri) {
				// display results in page
				
				var repo_id=$('#repo_id').val();
				var test_id=$('#test_id').val();
				
				document.getElementById('results').innerHTML = '<img src="'+data_uri+'"/>';
					
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
				} );
				
				Webcam.upload( data_uri, 'Lab_Admin/save_image/'+repo_id+'/'+ test_id);
			
			} );
		}
	</script>	