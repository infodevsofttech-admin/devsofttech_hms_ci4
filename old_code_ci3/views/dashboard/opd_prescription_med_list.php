<div class="col-md-12" >
<table class="table " >
<?php if(count($opd_drug)>0) 
    { echo '<tr>
                <th>Type</th>
                <th>Prescribed</th>
                <th>Dosage</th>
                <th>Remarks</th>
                <th></th>
                <th></th>
            </tr>'; } ?>
    <?php foreach($opd_drug as $row) { ?>
    <tr>
        <td><?=$row->med_type?></td>
        <td><?=$row->med_name?></td>
        <td><?=$row->dose_shed?></td>
        <td><?=$row->dose_when?>
        <?=$row->dose_frequency?>  <?=$row->dose_where?>
        <?=$row->qty?><?=$row->no_of_days?><?=$row->remark?></td>
        <td>
            <a href="javascript:medicalSelect('<?=$row->id?>','<?=$row->opd_pre_id?>')"><i class="fa fa-edit"></i></a>
        </td>
        <td>
            <a href="javascript:medicalRemove('<?=$row->id?>','<?=$row->opd_pre_id?>')"><i class="fa fa-remove"></i></a>
        </td>
    </tr>
    <?php } ?>
</table>
</div>