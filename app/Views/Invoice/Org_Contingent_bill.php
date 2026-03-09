<div id="org_CONTINGENT">
<form role="form" class="form1">
<?= csrf_field() ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			<p>
			<strong>Name :</strong><?=$person_info[0]->p_fname?>  
			<strong>/ Age :</strong><?=$person_info[0]->age?> 
			<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
			<strong>/ P Code :</strong><?=$person_info[0]->p_code?>
			<strong>/ Ins. Comp. :</strong><?=$insurance[0]->ins_company_name?>
			</p>
			<input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id?>" />
			<input type="hidden" id="caseid" name="caseid" value="<?=$orgcase[0]->id?>" />
			<input type="hidden" id="insurance_id" name="insurance_id" value="<?=$orgcase[0]->insurance_id?>" />
        </div>
    </div>
	<div class="box-body">
		<div id="showfile">
		<textarea id='HTMLData' name="HTMLData"  placeholder="Place some text here">
		<?=$orgcase[0]->contingent_bill ?>
		</textarea>
		<script>
			CKEDITOR.replace( 'HTMLData' );
		</script>
		</div>
	</div>
	<div class="box-footer">
		<button id="updatereport"  type="button" class="btn btn-primary">Update</button>
		<button id="createreport"  type="button" class="btn btn-primary">Create</button>
		<button id="editreport"  type="button" class="btn btn-primary">Edit</button>
		<button id="showreport"  type="button" class="btn btn-primary">Show Final</button>
	</div>
</div>
<div id="msgshow"></div>
</form>
</div>
<script>
$('#updatereport').click(function(){
			var caseid = $('#caseid').val();
            var HTMLData=CKEDITOR.instances.HTMLData.getData();
			var csrf_name = '<?= csrf_token() ?>';
			var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
			
			$.post('<?= base_url('billing/case/org_contingent_update') ?>',{ 
					"case_id": caseid, 
					"HTMLData":HTMLData,
					[csrf_name]: csrf_value,
					}, function(data){
					$('#msgshow').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(5000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});
				}, 'json');
        });

		$('#createreport').click(function(){
			var caseid = $('#caseid').val();
           
			if(confirm("Are you sure Create the new Contingent Bill "))
			{
				var csrf_name = '<?= csrf_token() ?>';
				var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
				$.get('<?= base_url('Orgcase/create_contingent_bill') ?>/'+caseid, function(data){
					notify('success','Please Attention',data);
					load_form_div('<?= base_url('Orgcase/contingent_bill') ?>/'+caseid,'org_CONTINGENT');
				});
			}
			
        });

		$('#showreport').click(function(){
			var caseid = $('#caseid').val();
			var finalUrl = '<?= base_url('Orgcase/contingent_bill') ?>/' + caseid + '/1';
			window.open(finalUrl, '_blank');
		});
		
		$('#editreport').click(function(){
			var caseid = $('#caseid').val();
	           	load_form_div('<?= base_url('Orgcase/contingent_bill') ?>/'+caseid+'/0','org_CONTINGENT');
		});
		
</script>
