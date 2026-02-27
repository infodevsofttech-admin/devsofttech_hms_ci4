<section class="content-header">
    <h1>
        OPD 
        <small>Scan</small>
    </h1>
</section>
<div class="row">
	<div class="col-md-6">
	<div class="box box-danger">
		<div class="box-header">
			<div class="box-title">
				OPD Search
			</div>
		</div>
		<div class="box-body">
		<?php echo form_open('Opd/search_scan_opd', array('role'=>'form','class'=>'form2')); ?>
			<div class="row">
				<div class="col-md-12">
					<div class="input-group input-group-sm">
						<input class="form-control" type="text" id="txtsearch" name="txtsearch">
							<span class="input-group-btn">
							<button type="submit" class="btn btn-info btn-flat">Search OPD</button>
						</span>
					</div>
				</div>
			</div>
		<?php echo form_close(); ?>
			<div class="row">
				<hr />
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="searchresult" id="searchresult"></div>
				</div>
			</div>
		</div>
	</div>
	</div>
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
					<input type="hidden" id="opdid" value="0">
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
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="row">
	<div id="last_opd_pic" ></div>
</div>
<?php echo form_close(); ?>
	<!-- show captured image -->
	<script>
		function load_image()
		{
			load_form_div('/Opd/opd_file_last_list','last_opd_pic');
		}
	
		function remove_image(file_id)
		{
			if(confirm("Are you Delete this File"))
			{
				load_form_div('/index.php/Opd/opd_file_hide/'+file_id,'last_opd_pic');
			}
		}
		
		$(function(){
				Webcam.set({
				width: 320,
				height: 240,
				dest_width: 1280,
				dest_height: 720,
				image_format: 'jpeg',
				jpeg_quality: 90,
				enable_flash: false
			});
			Webcam.attach( '#camera' );
			load_image();
		});
		
		function take_snapshot() {
			// take snapshot and get image data
			Webcam.snap( function(data_uri) {
				// display results in page
				
				var opdid=$('#opdid').val();
				
				if(opdid>0)
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
					
					Webcam.upload( data_uri, 'Opd/save_image/'+opdid);
					setTimeout(load_image,5000);
					
				}
			
			} );
		}
	
	 $(document).ready(function(){
			$('form.form2').on('submit', function(form){
				form.preventDefault();
				$.post('/index.php/Opd/search_scan_opd', $('form.form2').serialize(), function(data){
					$('div.searchresult').html(data);
					$('#example1').DataTable();
				});
			});
	 });
	 
	 
 </script>