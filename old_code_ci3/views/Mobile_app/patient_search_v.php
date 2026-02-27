<style>
    .responsive {
      width: 100%;
      max-width: 400px;
      height: auto;
      padding: 2px;
    }
</style>
<?php foreach($data as $c){ ?>
    <div class="row">
        <div class="box box-primary">
            <div class="box-header with-border">
                <span class="text-danger"> <i class="fa fa-address-card "></i>
                     <?php echo $c->title.' '.$c->p_fname ; ?> </span> 
                     / <i class="fas fa-hourglass "></i>Age:<?php echo $c->str_age; ?>
                <br/>{<span class="text-success"><?=$c->p_relative?> </span> <?=$c->p_rname?>}
            </div>
            <div class="box-body box-profile">
                <div class="col-xs-6">
                    <p><strong><i class="fas fa-barcode "></i> <?php echo $c->p_code; ?></strong></p>
                    <p>
                        <strong ><i class="fas fa-phone"></i> 
                            <a href="tel:<?=$c->mphone1?>">
                                <?php echo $c->mphone1; ?>
                            </a>
                        </strong>
                    </p>
                    <a href="javascript:load_form_div('/Mobile_app/show_profile_opd/<?=$c->id?>','search_result')" class="btn btn-info btn-xs">
                        <span class="fa fa-eye"></span> View</a>
                    <?php if(!$c->opd_today=='0'){?>
                        <p><strong>Today Visit :</strong> <?php echo $c->opd_today; ?>
                        <br/> Dr. <?php echo $c->doc_name; ?>
                        </p>
                        <a href="javascript:load_form_div('/Mobile_app/take_opd_picture/<?=$c->opd_id?>/<?=$c->id?>','search_result')" class="btn btn-info btn-xs">
                        <span class="fa fa-eye"></span>Scan</a>
                    <?php } ?>    
                </div>
                <div class="col-xs-6">
                    <p><strong>Last Visit </strong><?php echo $c->last_date_visit; ?></p>
                    <?php
                        $pos=strpos($c->profile_picture,'/uploads/',1) ;
                        $profile_file_path=substr($c->profile_picture,$pos);
                    ?>
                    <img src="<?=$profile_file_path?>" class="responsive" />'
                </div>
            </div>
        </div>
    </div>
<?php } ?>