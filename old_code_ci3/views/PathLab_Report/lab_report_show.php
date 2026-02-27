<div class="row">
	<div id="myCarousel" class="carousel slide" data-ride="carousel">
		<!-- Indicators -->
		<ol class="carousel-indicators">
			<?php 
					for($i=0;$i<count($lab_file_list);++$i)
					{
						echo '<li data-target="#myCarousel" data-slide-to="'.$i.'" ';
						if($i<0)
						{
							echo ' class="active" ';
						}
						echo  '></li>';
					}
			?>
		</ol>
		<!-- Wrapper for slides -->
		<div class="carousel-inner">
			<?php 
					for($i=0;$i<count($lab_file_list);++$i)
					{
						
						$pos=strpos($lab_file_list[$i]->full_path,'/uploads/',1) ;
		
						$file_path=substr($lab_file_list[$i]->full_path,$pos);

						if($i<1){
							echo '<div class="item active">';
						}else{
							echo '<div class="item">';
						}

						if($lab_file_list[$i]->file_ext=='.pdf')
						{
							echo '<embed src="'.$file_path.'" width="800px" height="2100px" type="application/pdf"  ></embed>';
						}else
						{
							echo ' <img src="'.$file_path.'"  width="800px" />';
						}
						
						echo '</div>';
					}
			?>
		</div>
		<!-- Left and right controls -->
		<a class="left carousel-control" href="#myCarousel" data-slide="prev">
		  <span class="glyphicon glyphicon-chevron-left"></span>
		  <span class="sr-only">Previous</span>
		</a>
		<a class="right carousel-control" href="#myCarousel" data-slide="next">
		  <span class="glyphicon glyphicon-chevron-right"></span>
		  <span class="sr-only">Next</span>
		</a>
  </div>
</div>
