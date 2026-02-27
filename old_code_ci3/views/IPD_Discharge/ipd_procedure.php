<div class="col-md-12">
    <table class="table table-condensed">
        <?php if(count($ipd_discharge_procedure)>0) { 
            echo '<tr>
                    <th>Procedure Name</th>
                    <th>Procedure Date</th>
                    <th>Remarks</th>
                    <th></th>
                    <th></th>
                </tr>'; 
        } ?>
        <?php foreach($ipd_discharge_procedure as $row) { ?>
        <tr>
            <td><input class="form-control input-sm"
                    name="input_procedure_name_<?=$row->id?>"
                    id="input_procedure_name_<?=$row->id?>" type="text"
                    value="<?=$row->procedure_name?>">
            </td>
            <td>
                <div class="input-group date">
                    <div class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <input id="procedure_date<?=$row->id?>" name="procedure_date<?=$row->id?>" class="form-control pull-right datepicker" 
                    type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" 
                    value="<?php echo ($row->procedure_date?MysqlDate_to_str($row->procedure_date):date('d/m/Y')); ?>"  />
                </div>
            </td>
            <td>
                <input class="form-control input-sm"
                    name="input_procedure_remark_<?=$row->id?>"
                    id="input_procedure_remark_<?=$row->id?>" type="text"
                    value="<?=$row->procedure_remark?>">
            </td>
            <td><a href="javascript:procedureUpdate('<?=$row->id?>','<?=$row->ipd_id?>')">Update</a>
            </td>
            <td><a href="javascript:procedureRemove('<?=$row->id?>','<?=$row->ipd_id?>')">Remove</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>