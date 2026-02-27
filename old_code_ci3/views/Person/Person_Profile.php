<?php
        $c=$data[0];
        $opd_current=0;
        if(count($opd_master_current)>0)
        {
            $opd_current=$opd_master_current[0]->opd_id;
        }
	?>
<section class="content-header">
    <h1>
        <?=ucwords($data[0]->p_fname); ?> 
        <small><a href="javascript:load_form('/Patient/person_record/<?=$data[0]->id?>/0');"><?=$data[0]->p_code; ?></a></small>
    </h1>
</section>
<section class="content">
      <div class="row">
        <div class="col-md-3">
          <!-- Profile Image -->
          <div class="box box-primary">
            <div class="box-body box-profile">
            <?php
                $pos=strpos($c->profile_picture,'/uploads/',1) ;
                $profile_file_path=substr($c->profile_picture,$pos);
            ?>
            <img class="profile-user-img img-responsive img-circle" src="<?=$profile_file_path?>" alt="User profile picture">
              <h3 class="profile-username text-center"><?php echo $c->title.' '.$c->p_fname ; ?></h3>
              <p class="text-muted text-center"><span class="text-success"><?=$c->p_relative?> </span> <?=$c->p_rname?></p>
              <ul class="list-group list-group-unbordered">
                <li class="list-group-item">
                  <b>Age</b> <a class="pull-right"><?php echo $c->str_age; ?></a>
                </li>
                <li class="list-group-item">
                  <b>UHID /Patient Code</b> <a class="pull-right"><?php echo $c->p_code; ?></a>
                </li>
                <li class="list-group-item">
                  <b>Phone No.</b> <a class="pull-right"><?php echo $c->mphone1; ?></a>
                </li>
              </ul>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
          <!-- About Me Box -->
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">About</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <strong><i class="fa fa-book margin-r-5"></i> Address</strong>
              <p class="text-muted">
                <?php echo $c->p_address ?>
              </p>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
        <div class="col-md-9">
          <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
              <li class="active"><a href="#activity" data-toggle="tab">Activity</a></li>
              
            </ul>
            <div class="tab-content">
              <div class="active tab-pane" id="activity">
                <!-- Post -->
                <?php 
                  foreach($opd_master as $row)
                  {
                      $sql="select p.*,o.opd_code,o.doc_name,
                      if(o.apointment_date=curdate(),1,0) as opd_today from opd_prescription p 
                      join opd_master o on o.opd_id=p.opd_id 
                      where p.opd_id=$row->opd_id";
                      $query = $this->db->query($sql);
                      $opd_data= $query->result();

                      $sql="select * from file_upload_data where show_type=0 and  opd_id=$row->opd_id order by id";
                      $query = $this->db->query($sql);
                      $opd_file_list= $query->result();

                      $sql="select * from file_opd_rec where  opd_id=$row->opd_id order by id";
                      $query = $this->db->query($sql);
                      $file_opd_rec= $query->result();
                ?>

                <div class="post">
                  <?php

                    $opd_details_str="";
                    if(count($opd_data)>0)
                    {
                      $opd_details_str.= '<b>OPD ID :</b>'.$opd_data[0]->opd_code;
                      $opd_details_str.= '/<b> OPD Date :</b>'.MysqlDate_to_str($opd_data[0]->date_opd_visit).' / <b>Queue No.:</b>'.$opd_data[0]->queue_no.'<br/>';
    
                        if($opd_data[0]->bp!='')
                        {
                          $opd_details_str.= '<b>BP :</b> '.$opd_data[0]->bp.' ';
                        }
    
                        if($opd_data[0]->diastolic!='')
                        {
                          $opd_details_str.= '<b>Diastolic :</b> '.$opd_data[0]->diastolic.' ';
                        }
    
                        if($opd_data[0]->pulse!='')
                        {
                          $opd_details_str.= '<b>Pulse :</b> '.$opd_data[0]->pulse.' ';
                        }
    
                        if($opd_data[0]->temp!='')
                        {
                          $opd_details_str.= '<b>Temp. :</b> '.$opd_data[0]->temp.' ';
                        }
                    }
                  ?>
                  
                  <div class="user-block">
                    <img class="img-circle img-bordered-sm" src="/assets/images/Doctor_img_icon.png" alt="User Image">
                        <span class="username">
                          <a href="#">Dr. <?=$opd_data[0]->doc_name?></a>
                        </span>
                    <span class="description" style="color:black;"><?=$opd_details_str?></span>
                  </div>
                  <!-- /.user-block -->
                  <div class="row margin-bottom">
                  <?php 
                    $i=0;
                    //Files Show
                    foreach($opd_file_list as $opd_file_row)
                    {
                        $i=$i+1;
                        echo '<div class="col-sm-12">';
                        $pos=strpos($opd_file_row->full_path,'/uploads/',1) ;
                        $file_path=substr($opd_file_row->full_path,$pos);
    
                        //$file_path=str_replace('hms_uploads','uploads',$opd_file_row->full_path);
                    
                        if($opd_file_row->file_ext=='.pdf')
                        {
                            echo '<embed class="img-responsive" src="'.$file_path.'" width="800px" height="1000px" type="application/pdf"  ></embed>';
                        }else
                        {
                            echo '<img class="img-responsive" id="img_opd_'.$i.'" class="responsive"  src="'.$file_path.'"   
                                alt="Photo" >';
                        }
                    
                        echo '</div>';
                    }
    
                  
                  
                  ?>  
                   
                  </div>
                  <!-- /.row -->

                </div>

                <?php } ?>
                <!-- /.post -->
              </div>
              <!-- /.tab-pane -->
              
              <!-- /.tab-pane -->
            </div>
            <!-- /.tab-content -->
          </div>
          <!-- /.nav-tabs-custom -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

    </section>
    <!-- /.content -->
