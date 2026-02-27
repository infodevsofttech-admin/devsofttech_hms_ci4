<div class="box">
    <div class="box-header">
        <h3 class="box-title">On Booking List</h3>
    </div>
    <!-- /.box-header -->
    <div class="box-body no-padding">
        <table class="table table-condensed">
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>OPD No.</th>
                <th>Booking No.</th>
                <th>UHID</th>
                <th>OPD ID.</th>
                <th>OPD Type</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $srno=0;
            foreach($opd_list_0 as $row)
            {
        ?>
            <tr>
                <td><?=$row->P_name?></td>
                <td style="width: 10px"><?=$row->opd_id?></td>
                <td style="width: 10px"><?=$row->opd_no?></td>
                <td style="width: 10px"><?=$row->p_code?></td>
                <td style="width: 10px"><?=$row->opd_code?></td>
                <td><?=$row->Paymode?></td>
                <td>
                    <?php 
                    if($row->payment_status==1) { ?>
                    <button  type="button" 	class="btn btn-primary"  
                    onclick="Opd_create_queue(<?=$row->opd_id ?>)">Queue</button>
                    <button  type="button" 	class="btn btn-primary" data-toggle="modal"
                        data-target="#tallModal" 
                    data-opdid="<?=$row->opd_id ?>" data-etype="3" >Cancel OPD</button>
                    <?php }else{ ?>
                        <a href="javascript:load_form('/Opd/invoice/<?=$row->opd_id ?>/0');"><i class="fa fa-dashboard"></i> Go For Payment</a></p>
                    <?php } ?>
                </td>
            </tr>
        <?php
            }
        ?>
        </table>
    </div>
</div>