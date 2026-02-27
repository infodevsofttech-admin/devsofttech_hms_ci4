<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Employee List</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table class="datatable table">
                    <thead>
                        <tr>
                            <th>
                               Emp  ID
                            </th>
                            <th>
                                Employee Name
                            </th>
                            <th>
                                Employee Phone No.
                            </th>
                            <th>
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employee_master as $row) { ?>
                            <tr>
                                <td>
                                    <?= $row->emp_code  ?>
                                </td>
                                <td>
                                    <?= $row->emp_name  ?>
                                </td>
                                <td>
                                    <?= $row->emp_phone_no  ?>
                                </td>
                                <td>
                                    <a href="Javascript:load_form_div('/Master_data/Employee_edit/<?=$row->emp_id?>','maindiv','<?=$row->emp_name?>')" >Edit</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <button type="button" class="btn btn-danger" id="btn_add_tag" onclick="load_form_div('/Master_data/Employee_add','maindiv','New Employee');" >Add Employee</button>
            </div>
        </div>
    </div>
</div>