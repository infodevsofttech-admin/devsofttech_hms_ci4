<div class="row">
    <div class="col-md-12">
        <div id="myCarousel" class="carousel slide" data-ride="carousel">
            <!-- Indicators -->
            <ol class="carousel-indicators">
                <?php
                for ($i = 0; $i < count($purchase_file_rec); ++$i) {
                    echo '<li data-target="#myCarousel" data-slide-to="' . $i . '" ';
                    if ($i < 0) {
                        echo ' class="active" ';
                    }
                    echo  '></li>';
                }
                ?>
            </ol>
            <!-- Wrapper for slides -->
            <div class="carousel-inner">
                <?php
                for ($i = 0; $i < count($purchase_file_rec); ++$i) {
                    $pos = strpos($purchase_file_rec[$i]->full_path, '/medical_uploads/purchase_invoice/', 1);

                    $file_path = substr($purchase_file_rec[$i]->full_path, $pos);

                    //$file_path=str_replace('hms_uploads','uploads',$opd_file_list[$i]->full_path);

                    if ($i < 1) {
                        echo '<div class="item active">';
                    } else {
                        echo '<div class="item">';
                    }

                    if ($purchase_file_rec[$i]->file_ext == '.pdf') {
                        echo '<embed src="' . $file_path . '" width="800px" height="1000px" type="application/pdf"  ></embed>';
                    } else {
                        echo ' <img src="' . $file_path . '"  width="800px" />';
                    }

                    echo '</div>';
                }

                for ($i = 0; $i < count($purchase_file_rec); ++$i) {
                    $pos = strpos($purchase_file_rec[$i]->full_path, '/medical_uploads/purchase_invoice/', 1);

                    $file_path = substr($purchase_file_rec[$i]->full_path, $pos);

                    //$file_path=str_replace('hms_uploads','uploads',$file_opd_rec[$i]->full_path);

                    echo '<div class="item">';

                    echo ' <video width="320" height="240" controls>
									<source src="/' . $file_path . '" type="video/webm">
					  				Your browser does not support the video tag.
					  			</video> ';

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
</div>