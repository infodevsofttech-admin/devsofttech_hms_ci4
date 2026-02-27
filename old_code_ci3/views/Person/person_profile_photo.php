<section class="content-header">
    <h1>
        Profile Picture : <?=$data[0]->p_fname; ?> 
        <small>Scan</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form('/Patient/person_record/<?=$data[0]->id?>/0');"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Person Profile Picture</li>
    </ol>
</section>
<section class="content">
	<div class="jsError"></div>
<div class="row">
	<div class="col-md-6">
		<div class="box box-danger">
			<div class="box-header">
				<div class="box-title">
					Scan
				</div>
			</div>
			<div class="box-body">
				
				<div class="row">
					<div class="col-md-4">
						<div id="camera"></div>
						<br />
						<button id="capture_btn" onClick="take_snapshot()">Capture</button>
						<input type="hidden" id="p_id" name="p_id" value="<?=$p_id?>">
					</div>
					<div class="col-md-8">
						<div id="results">Your captured image will appear here...</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
					  <div id="load_div"></div>
					</div>
					<div class="col-md-6">
					  <div id="file_upload_list"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</section>
	<!-- show captured image -->
	<script>
			
		$(function(){
				Webcam.set({
				width: 200,
				height: 160,
				dest_width: 1280,
				dest_height: 720,
				image_format: 'jpeg',
				jpeg_quality: 90,
				enable_flash: false
			});
			Webcam.attach( '#camera' );
			
		});
		
		function take_snapshot() {
			// take snapshot and get image data
			Webcam.snap( function(data_uri) {
				// display results in page
				
				var p_id=$('#p_id').val();
				
				if(p_id>0)
				{
					document.getElementById('results').innerHTML = 
					'<h2>Here is your image:</h2>' + 
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
					} );
					
					Webcam.upload( data_uri, 'Patient/save_profile_image/'+p_id);
			
				}
			
			} );
		}
	
	
	 
	 
 </script>