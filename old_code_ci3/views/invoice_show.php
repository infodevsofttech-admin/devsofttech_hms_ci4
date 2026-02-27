<section class="content-header">
      <h1>OPD Invoice</h1>
</section>
<section class="invoice">
      <!-- title row -->
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
            <i class="fa fa-globe"></i> <?=H_Name?>
            <small class="pull-right">Date: <?=date('d-m-Y')?></small>
          </h2>
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
          From
          <address>
            <strong><?=H_Name?> </strong><br>
            <?=H_address_1?><br>
            <?=H_address_2?><br>
            Phone: <?=H_phone_No?><br>
            Email: <?=H_Email?>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
          To
          <address>
            <strong><?=$opd_master[0]->P_name ?></strong><br>
            Date : <?=MysqlDate_to_str($opd_master[0]->apointment_date) ?><br>
            Gender : <?=($patient_master[0]->gender==1)?'Male':'Female' ?><br>
            Age : <?=$patient_master[0]->age ?><br>
            Phone No : <?=$patient_master[0]->mphone1 ?>
          </address>
        </div>
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
          <b>OPD ID:</b> <?=$opd_master[0]->opd_code ?><br>
          <b>Patient ID :</b> <?=$patient_master[0]->p_code ?>
		  
		  <?php
			if($opd_master[0]->insurance_id>1)
			{
				echo '<strong> Ins. Comp. :</strong>'.$insurance[0]->ins_company_name.'<br>';
			}
		
			if($opd_master[0]->payment_id>0 )
			{
					echo '<strong>Payment No. :</strong>'.$opd_master[0]->payment_id.'<br>';
			}
			?>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
      <!-- Table row -->
      <div class="row">
        <div class="col-xs-12 table-responsive">
          <table class="table table-striped">
            <thead>
            <tr>
			  <th>Type</th>
              <th>Doctor Name</th>
			  <th>Fee Amount</th>
              <th>Description</th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td>Dr. <?=$opd_master[0]->doc_name ?></td>
              <td><?=$opd_master[0]->doc_spec ?></td>
              <td><?=$opd_master[0]->opd_fee_amount ?></td>
              <td><?=$opd_master[0]->opd_fee_desc ?></td>
            </tr>
            </tbody>
          </table>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
	  <div class="row">
	  <div class="col-xs-3">
			<div class="form-group">
				<label>Type</label>
			</div>
		</div>
		<div class="col-xs-6">
			<div class="form-group">
				<label>Search</label>
				<input class="form-control" name="input_item" id="input_item" placeholder="Type PROCEDURES,INVESTIGATION"  type="text"  autocomplete="off">
			</div>
		</div>
		<div class="col-xs-3">
			
		</div>
	  </div>

      <div class="row">
        <!-- accepted payments column -->
        <div class="col-xs-6">
          <p class="lead">Payment Methods:</p>
          <p></p>
        </div>
        <!-- /.col -->
        <div class="col-xs-6">
          <p class="lead">Amount Due 2/22/2014</p>

          <div class="table-responsive">
            <table class="table">
              <tr>
                <th style="width:50%">Subtotal:</th>
                <td>$250.30</td>
              </tr>
              <tr>
                <th>Tax (9.3%)</th>
                <td>$10.34</td>
              </tr>
              <tr>
                <th>Shipping:</th>
                <td>$5.80</td>
              </tr>
              <tr>
                <th>Total:</th>
                <td>$265.24</td>
              </tr>
            </table>
          </div>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
</section>
<!-- /.content -->
<div class="clearfix"></div>

<script>
$(function() {
    $('#btn_lab').click(function(){
            var p_id = $('#p_id').val();
            load_form('/PathLab/addPathTest/'+p_id);
        });
});
</script>