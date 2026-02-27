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