<section class="content-header">
    <h1>
        Store Stock 
        <small>Dashboard</small>
    </h1>
</section>
<!-- Main content -->
<section class="content">
<?php echo form_open('MedicalDashboard', array('role'=>'form','class'=>'form1')); ?>
  	<div class="row">
		<div class="col-md-12">
			<a class="btn btn-app" href="javascript:load_form_div('/Storestock/Indent_List','maindiv','Store : Indent');">
				<i class="fa fa-shopping-cart"></i>Indent
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Storestock/Report_2','maindiv','Day Report :Store');">
				<i class="fa fa-credit-card"></i> Day Report
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Storestock/store_stock','maindiv','Store Stock : Store');">
				<i class="fa  fa-barcode"></i> Store Stock
			</a>
			<a class="btn btn-app" href="javascript:load_form_div('/Storestock/main_store','maindiv','Store Main : Store');">
				<i class="fa fa-desktop"></i> Store Main
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
		

	 });
 </script>