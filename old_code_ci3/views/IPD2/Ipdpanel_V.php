<section class="content-header">
    <h1>
        IPD : <a href="javascript:load_form('/IpdNew/ipd_panel/<?=$ipd_info[0]->id?>/');"><?=$ipd_info[0]->ipd_code?> </a>
        <small><?=$ipd_info[0]->ins_company_name?></small>
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
				<strong>/ UHID :</strong><a href="javascript:load_form('/Patient/person_record/<?=$person_info[0]->id?>');"><?=$person_info[0]->p_code?></a>
				<strong>/ No of Days :</strong><?=$ipd_info[0]->no_days?> 
				</p>
				<p><strong>Admit Date :</strong><?=$ipd_info[0]->str_register_date?>
				<strong>/ Discharge Date  :</strong><?=$ipd_info[0]->str_discharge_date?> 
				</p>
				<input type="hidden" id="pid" value="<?=$person_info[0]->id?>" />
				<input type="hidden" id="pname" value="<?=$person_info[0]->p_fname?>" />
				<input type="hidden" id="Ipd_ID" value="<?=$ipd_info[0]->id?>" />

				<?php 
				$pdata=0;
				if(count($case_master)>0) {
					$pdata=$case_master[0]->insurance_id;
				}
				?>
				<input type="hidden" id="ins_comp_id" name="ins_comp_id" value="<?=$pdata?>" />
			</div>
		</div>
		<div class="row" >
			<div id="account_status">
				<div class="col-md-3">
					<div class="callout callout-warning">
					<strong>Charges :</strong><?=$ipd_master[0]->charge_amount?><br/>
					<strong>Pharmacy Cr. IPD :</strong><?=$ipd_master[0]->med_amount?><br/>
					<strong>Net Amount :</strong><?=$ipd_master[0]->net_amount?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="callout callout-success">
					<strong>Total Paid :</strong><?=$ipd_master[0]->total_paid_amount?><br/>
					<strong>Balance :</strong><?=$ipd_master[0]->balance_amount?>
					</div>
				</div>
				<div class="col-md-3">
					<div class="callout callout-info">
						<strong>Pharmacy Bill :</strong><?=$ipd_master[0]->cash_med_amount?><br/>
						<strong>Paid Amount :</strong><?=$ipd_master[0]->med_paid?>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<button type="button" class="btn btn-danger" id="btn_add_payment" data-toggle="modal" data-target="#payModal" data-invid="<?=$ipd_info[0]->id?>" data-invtype="1">Payment Add</button>
							<button type="button" class="btn btn-warning" id="btn_refund_payment" data-toggle="modal" data-target="#payModal_ded" data-invid="<?=$ipd_info[0]->id?>" data-invtype="1">Payment Refund</button>
						</div>
					</div>
					<div class="col-md-12">
						<div class="form-group">
						<button type="button" class="btn btn-info" id="btn_tpa_payment" data-toggle="modal" data-target="#payModal_TPA" data-invid="<?=$ipd_info[0]->id?>" data-invtype="1">Payment TPA And Others</button>
						</div>
					</div>
				</div>
			</div>	
		</div>
	    <div class="row">
			<div class="col-md-12">
			<div id="tabs" class="nav-tabs-custom">
				<ul class="nav nav-tabs" id="prodTabs">
					<li><a aria-expanded="true" href="#tab_1" data-url="/IpdNew/ipd_main_panel/<?=$ipd_info[0]->id?>"  >Admission Info</a></li>
					<li><a aria-expanded="true" href="#tab_5" data-url="/IpdNew/ipd_bed_assign_list/<?=$ipd_info[0]->id?>">Bed Assign</a></li>
					<li><a aria-expanded="true" href="#tab_7" data-url="/IpdNew/IPD_Invoice_Edit/<?=$ipd_info[0]->id?>">IPD Charges</a></li>
					<li><a aria-expanded="true" href="#tab_8" data-url="/IpdNew/ipd_package/<?=$ipd_info[0]->id?>">Package</a></li>
					<li><a aria-expanded="true" href="#tab_8" data-url="/IpdNew/ipd_Diagnosis/<?=$ipd_info[0]->id?>">Diagnosis Charges</a></li>
					<li><a aria-expanded="true" href="#tab_2" data-url="/IpdNew/ipd_panel_payment/<?=$ipd_info[0]->id?>">Payments</a></li>
					<li><a aria-expanded="true" href="#tab_6" data-url="/IpdNew/list_med_inv/<?=$ipd_info[0]->id?>">Medical Bills</a></li>
					<li><a aria-expanded="true" href="#tab_3" data-url="/IpdNew/ipd_complete_invoice/<?=$ipd_info[0]->id?>/0">Bill Details</a></li>
					<li><a aria-expanded="true" href="#tab_4" data-url="/IpdNew/discharge_ipd/<?=$ipd_info[0]->id?>/0">Discharge Process</a></li>
				</ul>
				<div class="tab-content">
					<div id="tab_1" class="tab-pane active"></div>
					<div id="tab_2" class="tab-pane active"></div>
					<div id="tab_3" class="tab-pane active"></div>
					<div id="tab_4" class="tab-pane active"></div>
					<div id="tab_5" class="tab-pane active"></div>
					<div id="tab_6" class="tab-pane active"></div>
					<div id="tab_7" class="tab-pane active"></div>
					<div id="tab_8" class="tab-pane active"></div>
				</div>
			</div>
		</div>
	</section>
<!-- /.content -->
<!-- Modal -->
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

  <div class="modal fade" id="payModal_TPA" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="payModalLabel">Payment TPA and Others</h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="payModal_TPA-bodyc" id="payModal_TPA-bodyc">
					
				</div>
			</div>
		</div>
      </div>
      
    </div>
  </div>
<script>

	$(document).ready(function(){

		document.title='IPD:<?=$person_info[0]->p_fname ?>/<?=$ipd_info[0]->id ?>';
	});

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
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		var title='IPD:<?=$person_info[0]->p_fname ?>/<?=$ipd_info[0]->id ?>';
		
		load_form_div('/IpdNew/ipd_panel_invoice_show/'+invid+'/'+invtype,'modal-bodyc',title);
		
		$('#myInput').focus();
	});


	
	$('#payModal').on('shown.bs.modal', function (event) {
		
		var button = $(event.relatedTarget); // Button that triggered the modal
		var invid = button.data('invid');
		var invtype = button.data('invtype');
		
		var title='IPD:<?=$person_info[0]->p_fname ?>/<?=$ipd_info[0]->id ?>';

		load_form_div('/IpdNew/load_model_box/'+invid,'payModal-bodyc',title);
	})

	$('#payModal').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		var title='IPD:<?=$person_info[0]->p_fname ?>/<?=$ipd_info[0]->id ?>';

		load_form_div('/IpdNew/ipd_account_panel/'+ipd_id,'account_status',title);
	});

	//Payment Ded.

	$('#payModal_ded').on('shown.bs.modal', function () {
		var title='IPD:<?=$person_info[0]->p_fname ?>/<?=$ipd_info[0]->id ?>';

		load_form_div('/IpdNew/load_model_ded_box','payModal_ded-bodyc',title);
	})
	
	$('#payModal_ded').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		var title='IPD:<?=$person_info[0]->p_fname ?>/<?=$ipd_info[0]->id ?>';

		load_form_div('/IpdNew/ipd_account_panel/'+ipd_id,'account_status',title);
	});

	//Payment TPA & Others

	$('#payModal_TPA').on('shown.bs.modal', function () {
		var title='IPD:<?=$person_info[0]->p_fname ?>/<?=$ipd_info[0]->id ?>';
		var ipd_id=$('#Ipd_ID').val();
		load_form_div('/IpdNew/load_model_TPA_Payment/'+ipd_id,'payModal_TPA-bodyc',title);
	})
	
	$('#payModal_TPA').on('hidden.bs.modal', function () {
		var ipd_id=$('#Ipd_ID').val();
		//load_form_div('/ipd/ipd_panel_payment/'+ipd_id,'tab_2');
		var title='IPD:<?=$person_info[0]->p_fname ?>/<?=$ipd_info[0]->id ?>';

		load_form_div('/IpdNew/ipd_account_panel/'+ipd_id,'account_status',title);
	});
	
</script>