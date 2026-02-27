<div class="box">
    <div class="box-header">
        <h3 class="box-title">On Waiting List</h3>
    </div>
    <!-- /.box-header -->
    <div class="box-body no-padding">
        <table  class="table table-condensed dtable TableData w-auto text-xsmall">
        <thead>
            <tr>
                <th>OPD No.</th>
                <th>Current Patient</th>
                <th>Q No.</th>
                <th>UHID</th>
                <th>OPD Type</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $srno=0;
            foreach($opd_list_1 as $row)
            {
        ?>
            <tr>
                <td><?=$row->opd_id?></td>
                <td >                
                    <?=$row->P_name?> { <?=$row->p_rname?> }
                </td>
                <td style="width: 10px"><?=$row->queue_no?></td>
                <td style="width: 10px"><?=$row->p_code?></td>
                <td><?=$row->opd_fee_desc?>/ Amt.:<?=$row->opd_fee_amount?></td>
                <td>
                <button  type="button" 	class="btn btn-default btn-xs" 
                Onclick="Opd_Prescription(<?=$row->opd_id ?>)" ><img src="/assets/images/icon/prescription.png" class="img_icon"  /></button>
                <button  type="button" 	class="btn btn-default btn-xs" data-toggle="modal"
                data-target="#tallModal" 
                data-opdid="<?=$row->opd_id ?>" data-etype="1" ><img src="/assets/images/icon/iball_scan.png" class="img_icon"  /> </button>
                <button  type="button" 	class="btn btn-default btn-xs" data-toggle="modal"
                data-target="#tallModal" 
                data-opdid="<?=$row->opd_id ?>" data-etype="3" ><img src="/assets/images/icon/upload_scan_img.png" class="img_icon"  /></button>
                <button  type="button" 	class="btn btn-default btn-xs" data-toggle="modal"
                data-target="#tallModal" 
                data-opdid="<?=$row->opd_id ?>" data-etype="2" ><img src="/assets/images/icon/medical_profile.png" class="img_icon" /></button>
                <button  type="button" class="btn btn-default btn-xs" onclick="update_status(<?=$row->opd_id?>,2)" >
                    <img src="/assets/images/icon/update_status.png" class="img_icon"  /></button>
                </td>
            </tr>
        <?php
            }
        ?>
        </tbody>
        <tfoot>
            <tr>
                <th>OPD No.</th>
                <th>Current Patient</th>
                <th width="100px">Queue No.</th>
                <th>UHID</th>
                <th>OPD Type</th>
                <th>Action</th>
            </tr>
        </tfoot>
        </table>
    </div>
</div>
<script>
    $('.dtable').DataTable({
        "pageLength": 50,
        order: [[1, 'asc']],
        "bSort": false,
        });
</script>
