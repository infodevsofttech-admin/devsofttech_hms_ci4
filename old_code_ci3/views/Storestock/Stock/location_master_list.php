<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Location</h3>
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
                                Location Name
                            </th>
                            <th>
                                Location Description
                            </th>
                            <th>
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($location_master as $row) { ?>
                            <tr>
                                <td>
                                    <?= $row->l_id  ?>
                                </td>
                                <td>
                                    <?= $row->loc_name  ?>
                                </td>
                                <td>
                                    <?= $row->loc_desc  ?>
                                </td>
                                <td>
                                    <a href="Javascript:load_form_div('/Storestock/Location_edit/<?=$row->l_id?>','maindiv','<?=$row->loc_name?>')" >Edit</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <button type="button" class="btn btn-danger" id="btn_add_tag" onclick="load_form_div('/Storestock/Location_add','maindiv','New Location');" >Add Location</button>
            </div>
        </div>
    </div>
</div>