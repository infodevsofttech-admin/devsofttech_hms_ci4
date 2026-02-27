<div id="camera_wrapper">
	<div id="camera"></div>
	<br />
	<button id="capture_btn" onClick="take_snapshot()">Capture</button>
	</div>
	<!-- show captured image -->
	<div id="show_saved_img" ></div>
	
	<div id="load_div"></div>
	<div id="results">Your captured image will appear here...</div>
	<script>
		$(function(){
				
			Webcam.set({
			width: 320,
			height: 240,
			dest_width: 1600,
			dest_height: 1200,
			
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
				document.getElementById('results').innerHTML = 
					'<h2>Here is your image:</h2>' + 
					'<img src="'+data_uri+'" width="320" />';
	
				
				Webcam.on( 'uploadProgress', function(progress) {
					document.getElementById('load_div').innerHTML = "Loading........."+progress;
				} );
				
				Webcam.on( 'uploadComplete', function(code, text) {
					document.getElementById('load_div').innerHTML = "Completed"
					// Upload complete!
					// 'code' will be the HTTP response code from the server, e.g. 200
					// 'text' will be the raw response content
				} );
				
				Webcam.upload( data_uri, 'Testcont/save_image/uid' );
			
			} );
		}
	</script>	