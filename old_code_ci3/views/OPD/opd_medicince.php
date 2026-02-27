<section class="content-header">
    <h1>
        Medicine
        <small>OPD List</small>
        <div class="box-tools pull-right">
            <a class="btn  btn-warning" href="javascript:load_form_div('/Opd_prescription/opd_medicince_add', 'medicinceedit');">Add Medicine</a>
            <a class="btn  btn-warning" href="javascript:load_form('/Opd_prescription/opd_medicince','Medicine')">Medicince List</a>
        </div>
    </h1>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">List of Medicince </h3>
                    <div class="box-tools pull-right">

                    </div>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <table class="table table-bordered table-striped TableData">
                        <thead>
                            <tr>
                                <th>Med ID</th>
                                <th>Name</th>
                                <th>Generic Name</th>
                                <th>Company Name</th>
                                <th>Edit</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($opd_med_master as $row) {
                        ?>
                            <tr id="med_id_<?=$row->id?>" name="med_id_<?=$row->id?>">
                                <td><?= $row->id ?></td>
                                <td><?= $row->formulation . ' ' . $row->item_name ?></td>
                                <td><?= $row->genericname ?></td>
                                <td><?= $row->company_name ?></td>
                                <td><a href="javascript:edit_medicince(<?= $row->id ?>)">Edit</a></td>
                                <td><a href="javascript:remove_medicince(<?= $row->id ?>)">Remove</a></td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                    <script>
                        $('.TableData').DataTable({
                            "pageLength": 50,
                            order: [
                                [1, 'asc']
                            ],
                        });
                    </script>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div id="medicinceedit"></div>
        </div>
    </div>
</section>
<script>
    function edit_medicince(med_id) {
        $('#medicinceedit').html('');
        load_form_div('/Opd_prescription/opd_medicince_edit/' + med_id, 'medicinceedit');

    }

    function remove_medicince(med_id) {

        if(confirm('Are you sure delete this Item')){
            $('#medicinceedit').html('');
            load_form_div('/Opd_prescription/opd_medicince_remove/' + med_id, 'medicinceedit');
            load_form_div('/Opd_prescription/opd_medicince_shy w/'+med_id,'med_id_'+med_id);
        }
        
    }
</script>