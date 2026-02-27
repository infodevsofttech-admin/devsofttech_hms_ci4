<div class="box">
    <div class="box-header">
        <h3 class="box-title">Visited List</h3>
    </div>
    <!-- /.box-header -->
    <div class="box-body no-padding">
        <table class="table table-condensed">
        <?php
        $srno=0;
            foreach($opd_list_2 as $row)
            {
        ?>
            <tr>
            <td style="width: 10px"><?=$row->opd_id?></td>
                <td style="width: 10px"><?=$row->opd_code?></td>
                <td><?=$row->P_name?> { <?=$row->p_rname?> }</td>
                <td style="width: 10px"><?=$row->queue_no?></td>
                <td style="width: 10px"><?=$row->p_code?></td>
                <td><?=$row->opd_fee_desc?>/ Amt.:<?=$row->opd_fee_amount?></td>
                <td><button  type="button" 	class="btn btn-default" 
                Onclick="Opd_Prescription(<?=$row->opd_id ?>)" ><img src="/assets/images/icon/prescription.png" class="img_icon"  /></button>
                <button  type="button" 	class="btn btn-default" data-toggle="modal"
                data-target="#tallModal" 
                data-opdid="<?=$row->opd_id ?>" data-etype="1" ><img src="/assets/images/icon/iball_scan.png" class="img_icon"  /> </button>
                <button  type="button" 	class="btn btn-default" data-toggle="modal"
                data-target="#tallModal" 
                data-opdid="<?=$row->opd_id ?>" data-etype="3" ><img src="/assets/images/icon/upload_scan_img.png" class="img_icon"  /></button>
                <button  type="button" 	class="btn btn-default" data-toggle="modal"
                data-target="#tallModal" 
                data-opdid="<?=$row->opd_id ?>" data-etype="2" ><img src="/assets/images/icon/medical_profile.png" class="img_icon"  /></button>
                </td>
            </tr>
        <?php
            }
        ?>
        </table>
    </div>
</div>