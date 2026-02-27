<section class="content-header">
    <h1>
        IPD 
        <small></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active" ><a href="javascript:load_form('/Ipd/IpdList');">IPD List</a></li>
    </ol>
</section>
<!-- Main content -->
    <section class="content">
	<div class="row">
		<div class="col-md-12">
			<p><strong>Name :</strong><?=$person_info[0]->p_fname?>    {<i><?=$person_info[0]->p_rname?></i>}
			<strong>/ Age :</strong><?=$person_info[0]->age?> 
			<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
			<strong>/ P Code :</strong><?=$person_info[0]->p_code?> 
			<strong>/ IPD Code :</strong><?=$ipd_info[0]->ipd_code?> 
			<strong>/ No of Days :</strong><?=$ipd_list[0]->no_days?> 
			</p>
			<p><strong>Admit Date :</strong><?=$ipd_list[0]->str_register_date?>
			<strong>/ Discharge Date  :</strong><?=$ipd_list[0]->str_discharge_date?> 
			</p>
			<input type="hidden" id="pid" value="<?=$person_info[0]->id?>" />
			<input type="hidden" id="pname" value="<?=$person_info[0]->p_fname?>" />
			<input type="hidden" id="Ipd_ID" value="<?=$ipd_info[0]->id?>" />
		</div>
	</div>
	<div class="row" id="account_status">
		<div class="col-md-2">
			<div class="callout callout-info">
				<h4>Charges Bill</h4>
				<p>Rs. <?=$inv_total['total_charges']?></p>
			</div>
		</div>
		<div class="col-md-2">
			<div class="callout callout-danger ">
						<h4>Medical Bill</h4>
						<p>Credit IPD : Rs. <?=$inv_total['total_med_credit']?></p>
						<p>Cash : Rs. <?=$inv_total['total_med_cash']?> / <?=$inv_total['total_med_cash_paid']?></p>
			</div>
		</div>
		<div class="col-md-2">
			<div class="callout callout-success">
						<h4>Paid Amount</h4>
						<p>Rs. <?=$inv_total['total_payment']?></p>
						
			</div>
		</div>
		<div class="col-md-2">
			<div class="callout callout-warning">
						<h4>Discount Amount</h4>
						<p>Rs. <?=$inv_total['discount']?></p>
						<h4>Charge Amount</h4>
						<p>Rs. <?=$inv_total['charge']?></p>
			</div>
		</div>
		<div class="col-md-2">
			<div class="callout callout-warning">
						<h4>Balance Amount</h4>
						<p>Rs. <?=$inv_total['total_balance_amount']?></p>
			</div>
		</div>
		<div class="col-md-2">
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<button type="button" class="btn btn-danger" id="btn_add_payment" data-toggle="modal" data-target="#payModal" data-invid="<?=$ipd_info[0]->id?>" data-invtype="1">Payment Add</button>
					</div>
				</div>
				<div class="col-md-12">
					<div class="form-group">
					<button type="button" class="btn btn-warning" id="btn_refund_payment" data-toggle="modal" data-target="#payModal_ded" data-invid="<?=$ipd_info[0]->id?>" data-invtype="1">Payment Refund</button>
					</div>
				</div>
			</div>
		</div>
	</div>
    <div class="row">
		<div class="col-md-12">
			<div id="tabs" class="nav-tabs-custom">
				<ul class="nav nav-tabs" id="prodTabs">
					<li><a aria-expanded="true" href="#tab_1" data-url="/ipd/ipd_main_panel/<?=$ipd_info[0]->id?>"  >Admission Info</a></li>
					<li><a aria-expanded="true" href="#tab_5" data-url="/ipd/ipd_bed_assign_list/<?=$ipd_info[0]->id?>">Bed Assign</a></li>
					<li><a aria-expanded="true" href="#tab_2" data-url="/ipd/ipd_panel_payment/<?=$ipd_info[0]->id?>">Payments</a></li>
					<li><a aria-expanded="true" href="#tab_6" data-url="/ipd/list_med_inv/<?=$ipd_info[0]->id?>">Medical Bills</a></li>
					<li><a aria-expanded="true" href="#tab_3" data-url="/ipd/ipd_complete_invoice/<?=$ipd_info[0]->id?>/0">Bill Details</a></li>
					<li><a aria-expanded="true" href="#tab_4" data-url="/ipd/discharge_ipd/<?=$ipd_info[0]->id?>/0">Discharge Process</a></li>
				</ul>
				<div class="tab-content">
					<div id="tab_1" class="tab-pane active"></div>
					<div id="tab_2" class="tab-pane active"></div>
					<div id="tab_3" class="tab-pane active"></div>
					<div id="tab_4" class="tab-pane active"></div>
					<div id="tab_5" class="tab-pane active"></div>
					<div id="tab_6" class="tab-pane active"></div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /.content -->
<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Invoice</h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="modal-bodyc">
			
			</div>
		</div>
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
  <div class="modal fade" id="payModal_ded" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="payModalLabel">Payment Deduction</h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="payModal_ded-bodyc" id="payModal_ded-bodyc">
					
				</div>
			</div>
		</div>
      </div>
      
    </div>
  </div>

  <div class="modal fade" id="payModal_refund" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="payModalLabel">Payment Refund</h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="payModal_refund-bodyc" id="payModal_refund-bodyc">
					
				</div>
			</div>
		</div>
      </div>
      
    </div>
  </div>
<script>

	$('#tabs').on('click','.tablink,#prodTabs a',function (e) {
    e.preventDefault();
    var url = $(this).attr("data-url");
	
    if (typeof url !== "undefined") {
        var pane = $(this), href = this.hash;

        // ajax load from data-url
        $(href).load(url,function(result){      
            pane.tab('show');
        });
    } else {
        $(this).tab('show');
    }
	});
		
	$('#myModal').on('shown.bs.modal', function (event) {
		$('.modal-bodyc').html('');
		var button = $(event.relatedTarget); // Button that triggered the modal
		var invid = button.data('invid');
		var invtype = button.data('invtype');
		
		$.post('/index.php/Ipd/ipd_panel_invoice_show',{ "invid": invid,"invtype": invtype }, function(data){
        	$('.modal-bodyc').html(data);
        });
		$('#myInput').focus();
	});
	
	$('#payModal').on('shown.bs.modal', function (event) {
		
		var button = $(event.relatedTarget); // Button that triggered the modal
		var invid = button.data('invid');
		var invtype = button.data('invtype');
		
		load_form_div('/ipd/load_model_box/'+invid,'payModal-bodyc');
	})
	
	$('#payModal_ded').on('shown.bs.modal', function () {
		load_form_div('/ipd/load_model_ded_box','payModal_ded-bodyc');
	})
	
	$('#payModal').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		load_form_div('/ipd/ipd_account_panel/'+ipd_id,'account_status');
	});
	
	$('#payModal_ded').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		load_form_div('/ipd/ipd_account_panel/'+ipd_id,'account_status');
	});
	
</script>