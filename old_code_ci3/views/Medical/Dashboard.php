<section class="content-header">
    <h1>
        Medical Store 
        <small>Dashboard</small>
    </h1>
</section>
<!-- Main content -->
<section class="content">
<?php echo form_open('MedicalDashboard', array('role'=>'form','class'=>'form1')); ?>
  	<div class="row">
		<div class="col-md-12">
			<a class="btn btn-app" href="javascript:load_form_div('/Medical/search_customer','maindiv','OPD Search Panel :Pharmacy');">
				<i class="fa fa-shopping-cart"></i>OPD Sale
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical/Invoice_Med_Draft','maindiv','Invoice List :Pharmacy');">
				<i class="fa fa-shopping-cart"></i>Invoice
			</a>
			<a class="btn btn-app"  href="javascript:load_form_div('/Medical/list_org_ipd','maindiv','IPD List :Pharmacy');" >
				<span class="badge bg-yellow" id="ipd_notification"></span>
				<i class="fa fa-h-square"></i> IPD or Credit Invoice
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical/list_org','maindiv','OrgCr. List :Pharmacy');" >
				<i class="fa fa-hospital-o"></i> Org. Credit Invoice
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical/Invoice_Med_Return','maindiv','Return Invoice :Pharmacy');">
				<i class="fa fa-shopping-cart"></i>Return Counter Sale
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical_Report/Report_2','maindiv','Day Report :Pharmacy');">
				<i class="fa fa-credit-card"></i> Day Report
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical_Report/Report_1','maindiv','Sale Report :Pharmacy');">
				<i class="fa fa-rupee"></i> Sale Day Report
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical_Report/Report_Payment_Recieved','maindiv','Payment Report :Pharmacy');">
				<i class="fa fa-rupee"></i> Payment Report
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical_backpanel/store_stock','maindiv','Store Stock : Pharmacy');">
				<i class="fa  fa-barcode"></i> Store Stock
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical/main_store','maindiv','Store Main : Pharmacy');">
				<i class="fa fa-desktop"></i> Store Main
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Medical_backpanel','maindiv','Master : Pharmacy');">
				<i class="fa fa-desktop"></i> Master
			</a>
		</div>
  	</div>
	  <?php echo form_close(); ?>
  <div id="maindiv" class="row">
	
  </div>
 </section>
 <script>
	 $(document).ready(function() {
		//setInterval(call_notification, 15000); 
		function call_notification()
		{
			$.post('/index.php/Medical/get_medical_bill_pending_higher', $('form.form1').serialize(), function(data){
				$('#ipd_notification').html(data); 
            });
		}

	 });
 </script>