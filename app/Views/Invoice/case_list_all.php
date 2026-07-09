<style>
    .admin-hero {
        background: linear-gradient(120deg, #f4f7fb 0%, #eef3ff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    .admin-hero h3 {
        font-family: "Poppins", "Nunito", sans-serif;
        font-size: 22px;
        margin: 0;
        color: #0f172a;
    }
    .admin-card {
        border-radius: 12px;
        border: 1px solid #e8edf3;
        overflow: hidden;
    }
</style>

<div class="col-md-12">
		<div class="admin-hero">
			<h3>Org. Case List</h3>
		</div>
		<div class="card admin-card">
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover align-middle TableData" id="employee-grid" width="100%">
					<thead>
					<tr>
						<th>Case No.</th>
						<th>Claim/Insurance Card</th>
						<th>Patient Name/P-Code/IPD</th>
						<th>Case Date</th>
						<th>Card Holder Name</th>
						<th>Insurance</th>
						<th>Status</th>
					</tr>
					</thead>
					<thead>
					<tr>
						<td><input class="form-control" type="text" data-column="0" ></td>
						<td><input class="form-control" type="text" data-column="4" ></td>
						<td><input class="form-control" type="text" data-column="1" ></td>
						<td></td>
						<td><input class="form-control" type="text" data-column="2" ></td>
						<td><select class="form-select search-input-select" id="org_comp" name="org_comp" data-column="3" >
								<option value='0' >All</option>
								<option value='53' >Aries</option>
								<option value='2' >ECHS</option>
								<option value='63' >ESIS</option>
								<option value='-1' >Others</option>
							</select>
						</td>
						<td>
							<select class="form-select search-input-select" id="org_status" name="org_status" data-column="5" >
								<option value='0' >Pending</option>
								<option value='1' >Invoice Complete</option>
								<option value='2' >Submitted</option>
								<option value='3' >Payment Done</option>
							</select>
						</td>
					</tr>
					</thead>
					<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
 <div class="modal fade" id="payModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="payModalLabel">Payment</h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="payModal-bodyc" id="payModal-bodyc">
					
				</div>
			</div>
		</div>
      </div>
      
    </div>
  </div>
<!-- /.content -->
<script type="text/javascript" language="javascript" >
			(function() {
				var $table = $('#employee-grid');
				if ($table.length === 0) {
					return;
				}

				if ($table.data('case-list-init') === 1) {
					return;
				}
				$table.data('case-list-init', 1);

				function escHtml(value) {
					return String(value == null ? '' : value)
						.replace(/&/g, '&amp;')
						.replace(/</g, '&lt;')
						.replace(/>/g, '&gt;')
						.replace(/"/g, '&quot;')
						.replace(/'/g, '&#39;');
				}

				function renderFallbackRows(rows) {
					var $tbody = $('#employee-grid tbody');
					$tbody.empty();

					if (!rows || rows.length === 0) {
						$tbody.append('<tr><td colspan="7" class="text-center text-muted">No records found</td></tr>');
						return;
					}

					rows.forEach(function(res) {
						var caseNo = res[0] || '';
						var urlLink = "javascript:load_form('<?= base_url('Orgcase/case_invoice') ?>/" + encodeURIComponent(caseNo) + "');";
						var statusHtml = escHtml(res[6] || '');

						if ((res[6] || '') === 'submitted') {
							statusHtml = '<a data-toggle="modal" data-target="#payModal" data-caseid="' + encodeURIComponent(caseNo) + '" href="#">' + statusHtml + '</a>';
						}

						$tbody.append(
							'<tr>' +
							'<td><a href="' + urlLink + '">' + escHtml(caseNo) + '</a></td>' +
							'<td>' + escHtml(res[1] || '') + '</td>' +
							'<td>' + (res[2] || '') + '</td>' +
							'<td>' + escHtml(res[3] || '') + '</td>' +
							'<td>' + escHtml(res[4] || '') + '</td>' +
							'<td>' + escHtml(res[5] || '') + '</td>' +
							'<td>' + statusHtml + '</td>' +
							'</tr>'
						);
					});
				}

				function loadFallbackData() {
					$.ajax({
						url: "<?= base_url('Orgcase/getCaseTable') ?>",
						type: 'post',
						dataType: 'json',
						data: {
							'<?= csrf_token() ?>': '<?= csrf_hash() ?>',
							draw: 1,
							start: 0,
							length: 200,
							'order[0][column]': 0,
							'order[0][dir]': 'desc',
							'columns[0][search][value]': $('input[data-column="0"]').val() || '',
							'columns[1][search][value]': $('input[data-column="1"]').val() || '',
							'columns[2][search][value]': $('input[data-column="2"]').val() || '',
							'columns[3][search][value]': $('#org_comp').val() || 0,
							'columns[4][search][value]': $('input[data-column="4"]').val() || '',
							'columns[5][search][value]': $('#org_status').val() || 0
						}
					})
					.done(function(resp) {
						renderFallbackRows(resp && Array.isArray(resp.data) ? resp.data : []);
					})
					.fail(function() {
						renderFallbackRows([]);
					});
				}

				if (!$.fn || !$.fn.DataTable) {
					$(".search-input-select").off('change.caseListAllFallback').on('change.caseListAllFallback', loadFallbackData);
					$('input[type=text]').off('input.caseListAllFallback').on('input.caseListAllFallback', loadFallbackData);
					loadFallbackData();
					return;
				}

				if ($.fn.DataTable.isDataTable($table)) {
					$table.DataTable().destroy();
				}

				var dataTable = $table.DataTable( {
					"order": [[ 0, "desc" ]],
					"processing": true,
					"serverSide": true,
					"ajax":{
						url :"<?= base_url('Orgcase/getCaseTable') ?>", // json datasource
						type: "post",  // method  , by default get
						data: {
						'<?= csrf_token() ?>' : '<?= csrf_hash() ?>',
						},
						error: function(){  // error handling
							$(".employee-grid-error").html("");
							$("#employee-grid").append('<tbody class="employee-grid-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
							$("#employee-grid_processing").css("display","none");
						}
					},
					columnDefs: [
						{
							targets: 0,
							render: function ( data, type, row, meta ) {
								if(type === 'display'){
									url_link = "javascript:load_form('<?= base_url('Orgcase/case_invoice') ?>/"+ encodeURIComponent(data) + "');" ;
									udata = '<a href="' + url_link + '">' + data + '</a>';
									
								}
								return udata;
							}
						},
						{
							data:null,
							render: function ( data, type, row, meta ) {
								udata="";
								
								var res = data;
								if(type === 'display'){
									if(res[6]=='submitted')
									{
										udata = '<a data-toggle="modal" data-target="#payModal" data-caseid="'+encodeURIComponent(res[0])+'" href="#" >' + res[6] + ' </a>';
									}else{
										udata = res[6];
									}
								}
								return udata;
							},
							targets: 6
						}
					]        
				} );
				
				$("#employee-grid_filter").css("display","none");  // hiding global search box
				
				//$('.search-input-text').on( 'keyup click', function () {   // for text boxes
				//	var i =$(this).attr('data-column');  // getting column index
				//	var v =$(this).val();  // getting search input value
				//	dataTable.columns(i).search(v).draw();
				//} );
								
				$( ".search-input-select" ).off('change.caseListAll').on('change.caseListAll', function() {
				  var i =$(this).attr('data-column');  
					var v =$(this).val();
					dataTable.columns(i).search(v).draw();
				});
				
				$('input[type=text').off('input.caseListAll').on('input.caseListAll', function(){
					var i =$(this).attr('data-column');  
					var v =$(this).val(); 
					dataTable.columns(i).search(v).draw();
					
				});
				
				$('#payModal').off('shown.bs.modal.caseListAll').on('shown.bs.modal.caseListAll', function (event) {
						
						var button = $(event.relatedTarget); // Button that triggered the modal
						var invid = button.data('caseid');
												
						load_form_div('<?= base_url('Orgcase/load_model_box') ?>/'+invid,'payModal-bodyc');
					})
							
				})();
				
				
		
		</script>