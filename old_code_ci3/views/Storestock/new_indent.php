<div class="col-md-12">
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">New Indent</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date</label>
                        <div class="input-group input-group-sm date">
                            <div class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </div>
                            <input class="form-control pull-right datepicker" id="date_indent" name="date_indent" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value="<?=date('d/m/Y')?>"  />
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
				    <label>Select Location</label>
					    <select class="form-control Select2" id="location_id" name="location_id"  >					
						    <?php foreach($location_master as $row){ ?>
                            <option value='<?=$row->l_id?>'><?=$row->loc_name?></option>
                            <?php } ?>
					    </select>
				    </div>
                </div>
                <div class="col-md-3">
                <div class="form-group">
					<button type="button" class="btn btn-primary" id="additem" onclick='add_indent(1)'  >Add Indent</button>
				</div>
                </div>
            </div>
            <hr />
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
				    <label>Select Employee</label>
					    <select class="form-control Select2" id="emp_id" name="emp_id"  >					
						    <?php foreach($employee_master as $row){ ?>
                            <option value='<?=$row->emp_id?>'><?=$row->emp_code?> [<?=$row->emp_name?>]</option>
                            <?php } ?>
					    </select>
				    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <button type="button" class="btn btn-danger" id="additem" onclick='add_indent(2)'  >Add Indent</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            
        </div>
    </div>
</div>
<script>
    $('.select2').select2();

    function add_indent(location_type)
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        var loc_id=0;

        if(location_type==1)
        {
            loc_id=$('#location_id').val();
        }else if(location_type==2){
            loc_id=$('#emp_id').val();
        }

		if(loc_id>0)
		{
			$.post('/Storestock/Indent_create/'+location_type,
            { 
                "loc_id": loc_id, 
			    "date_indent": $('#date_indent').val(), 
			    '<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
			    $('#maindiv').html(data);
			});
		}
	}
</script>