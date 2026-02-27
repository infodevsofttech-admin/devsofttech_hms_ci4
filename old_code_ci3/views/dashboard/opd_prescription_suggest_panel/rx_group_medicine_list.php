<div class="col-md-12" >
            <table class="table " >
            <?php if(count($opd_prescrption_prescribed_template)>0) 
                { echo '<tr>
                            <th>Type</th>
                            <th>Prescribed</th>
                            <th>Dosage</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>'; } ?>
                <?php foreach($opd_prescrption_prescribed_template as $row) { ?>
                <tr>
                    <td><?=$row->med_type?></td>
                    <td><?=$row->med_name?></td>
                    <td><?=$row->dose_shed?></td>
                    <td><?=$row->dose_when?>
                    <?=$row->dose_frequency?> <?=$row->dose_where?>
                    <?=$row->qty?> <?=$row->no_of_days?> <?=$row->remark?></td>
                    <td>
                    <a href="javascript:medicalSelect('<?=$row->id?>')"><i class="fa fa-edit"></i></a>
                      <a href="javascript:medicalRemove('<?=$row->id?>','<?=$row->rx_group_id?>')"><i class="fa fa-remove"></i></a>
                    </td>
                </tr>
                <?php } ?>
            </table>
            </div>