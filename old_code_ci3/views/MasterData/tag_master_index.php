<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Remark</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table class="datatable table">
                    <thead>
                        <tr>
                            <th>
                                ID
                            </th>
                            <th>
                                Tag Name
                            </th>
                            <th>
                                Tag Description
                            </th>
                            <th>
                                Insert Date
                            </th>
                            <th>
                                Insert By
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tag_master as $row) { ?>
                            <tr>
                                <td>
                                    <?= $row->id  ?>
                                </td>
                                <td>
                                    <?= $row->tag_name  ?>
                                </td>
                                <td>
                                    <?= $row->tag_desc  ?>
                                </td>
                                <td>
                                    <?= $row->insert_dateimte  ?>
                                </td>
                                <td>
                                    <?= $row->insert_by  ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <button type="button" class="btn btn-danger" id="btn_add_tag" onclick="load_form_div('/Master_data/tag_add','common_model-bodyc','New Tag');" >Add Tag</button>
            </div>
        </div>
    </div>
</div>