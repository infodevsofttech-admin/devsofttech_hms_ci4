<?php

if(count($data)>0)
{
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
                    <a href="javascript:load_form_div('/Mobile_app/person_record/<?=$c->id?>','search_result')" class="btn btn-info btn-xs">
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
<div class="row">
  <!-- The timeline -->
  <ul class="timeline timeline-inverse">
    <!-- timeline time label -->
    <li class="time-label">
          <span class="bg-red">
            10 Feb. 2014
          </span>
    </li>
    <!-- /.timeline-label -->
    <!-- timeline item -->
    <li>
      <i class="fa fa-envelope bg-blue"></i>

      <div class="timeline-item">
        <span class="time"><i class="fa fa-clock-o"></i> 12:05</span>

        <h3 class="timeline-header"><a href="#">Support Team</a> sent you an email</h3>

        <div class="timeline-body">
          Etsy doostang zoodles disqus groupon greplin oooj voxy zoodles,
          weebly ning heekya handango imeem plugg dopplr jibjab, movity
          jajah plickers sifteo edmodo ifttt zimbra. Babblely odeo kaboodle
          quora plaxo ideeli hulu weebly balihoo...
        </div>
        <div class="timeline-footer">
          <a class="btn btn-primary btn-xs">Read more</a>
          <a class="btn btn-danger btn-xs">Delete</a>
        </div>
      </div>
    </li>
    <!-- END timeline item -->
    <!-- timeline item -->
    <li>
      <i class="fa fa-user bg-aqua"></i>

      <div class="timeline-item">
        <span class="time"><i class="fa fa-clock-o"></i> 5 mins ago</span>

        <h3 class="timeline-header no-border"><a href="#">Sarah Young</a> accepted your friend request
        </h3>
      </div>
    </li>
    <!-- END timeline item -->
    <!-- timeline item -->
    <li>
      <i class="fa fa-comments bg-yellow"></i>

      <div class="timeline-item">
        <span class="time"><i class="fa fa-clock-o"></i> 27 mins ago</span>

        <h3 class="timeline-header"><a href="#">Jay White</a> commented on your post</h3>

        <div class="timeline-body">
          Take me to your leader!
          Switzerland is small and neutral!
          We are more like Germany, ambitious and misunderstood!
        </div>
        <div class="timeline-footer">
          <a class="btn btn-warning btn-flat btn-xs">View comment</a>
        </div>
      </div>
    </li>
    <!-- END timeline item -->
    <!-- timeline time label -->
    <li class="time-label">
          <span class="bg-green">
            3 Jan. 2014
          </span>
    </li>
    <!-- /.timeline-label -->
    <!-- timeline item -->
    <li>
      <i class="fa fa-camera bg-purple"></i>

      <div class="timeline-item">
        <span class="time"><i class="fa fa-clock-o"></i> 2 days ago</span>

        <h3 class="timeline-header"><a href="#">Mina Lee</a> uploaded new photos</h3>

        <div class="timeline-body">
          <img src="http://placehold.it/150x100" alt="..." class="margin">
          <img src="http://placehold.it/150x100" alt="..." class="margin">
          <img src="http://placehold.it/150x100" alt="..." class="margin">
          <img src="http://placehold.it/150x100" alt="..." class="margin">
        </div>
      </div>
    </li>
    <!-- END timeline item -->
    <li>
      <i class="fa fa-clock-o bg-gray"></i>
    </li>
  </ul>
</div>

<?php

}

?>