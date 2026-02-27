<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Medicince : <?= $opd_med_master[0]->item_name ?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-remove"></i></button>
        </div>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <?php
    $attributes = array('id' => 'formtfnedit');
    echo form_open('OPD_prescription/opd_medicince_edit/' . $opd_med_master[0]->id, $attributes); ?>
    <div class="box-body">
     
        <div class="form-group">
            <label for="item_name">TFN Name</label>
            <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Enter Medicince Name" value="<?= $opd_med_master[0]->item_name ?>">
        </div>
        <div class="form-group">
            <label>Formulation</label>
            <select class="form-control select2" id="formulation" name="formulation" >
                <option value="0">No Assign</option>
                <?php foreach ($med_formulation as $row) { ?>
                    <option value="<?= $row->formulation ?>" <?= combo_checked($row->formulation, $opd_med_master[0]->formulation) ?>>
                        <?= $row->formulation_length ?> [<?= $row->formulation ?>]</option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group">
            <label for="genericname">Generic Name</label>
            <input type="text"  class="form-control" id="genericname" name="genericname" placeholder="Generic Name" value="<?= $opd_med_master[0]->genericname ?>">
        </div>
        <div class="form-group">
            <label>Company Name</label>
            <select class="form-control select2" id="company_id" name="company_id" >
                <option value="0">No Assign</option>
                <?php foreach ($med_company as $row) { ?>
                    <option value="<?= $row->id ?>" <?= combo_checked($row->id, $opd_med_master[0]->company_id) ?>>
                    <?= $row->company_name ?></option>
                <?php } ?>
            </select>
            
        </div>
    </div>
    <!-- /.box-body -->
    <div class="box-footer">
        <button type="submit" class="btn btn-primary">Update</button>
    </div>
    <?php echo form_close(); ?>
</div>
<!-- /.box -->
<script>
    
    $(document).ready(function() {

        $('#formtfnedit').on('submit', function(form) {
            form.preventDefault();
            form_array = $('#formtfnedit').serialize();
                $("#medicinceedit").html('Data Posting....Please Wait');
            $.post('/Opd_prescription/opd_medicince_edit/<?= $opd_med_master[0]->id ?>', form_array, function(data) {
                $("#medicinceedit").html(data);
                load_form_div('/Opd_prescription/opd_medicince_show/<?=$opd_med_master[0]->id?>','med_id_<?=$opd_med_master[0]->id?>');
            });
        });
    });
</script>