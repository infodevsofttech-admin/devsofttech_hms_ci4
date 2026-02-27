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
						<h4>Extra Charge Amount</h4>
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