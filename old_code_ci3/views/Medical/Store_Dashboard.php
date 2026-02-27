<input type="hidden" id="store_id" name="store_id" value="<?=$store_master[0]->store_id?>" />
<section class="content-header">
    <h1>
        Medical Store : <?=$store_master[0]->store_name?>
        <small>Dashboard</small>
    </h1>
</section>
<!-- Main content -->
<section class="content">
  <div class="row">
	<div class="col-md-12">
		<a class="btn btn-app" href="javascript:load_form_div('/Medical/Invoice_Med_Draft/<?=$store_master[0]->store_id?>','maindiv');">
			<i class="fa fa-shopping-cart"></i>Counter Sale
		</a>
		<a class="btn btn-app" href="javascript:load_form_div('/Medical/list_org_ipd/<?=$store_master[0]->store_id?>','maindiv');" >
			<i class="fa fa-h-square"></i> IPD or Credit Invoice
		</a>
		<a class="btn btn-app" href="javascript:load_form_div('/Medical/list_org/<?=$store_master[0]->store_id?>','maindiv');" >
			<i class="fa fa-hospital-o"></i> Org. Credit Invoice
		</a>
		<a class="btn btn-app" href="javascript:load_form_div('/Medical_Report/Report_2/<?=$store_master[0]->store_id?>','maindiv');">
			<i class="fa fa-credit-card"></i> Day Report
		</a>
		<a class="btn btn-app" href="javascript:load_form_div('/Medical_Report/Report_1/<?=$store_master[0]->store_id?>','maindiv');">
			<i class="fa fa-rupee"></i> Sale Day Report
		</a>
		<a class="btn btn-app" href="javascript:load_form_div('/Medical_Report/Report_Payment_Recieved/<?=$store_master[0]->store_id?>','maindiv');">
			<i class="fa fa-rupee"></i> Payment Report
		</a>
		<a class="btn btn-app" href="javascript:load_form_div('/Product_master/Indent_Request_List/<?=$store_master[0]->store_id?>','maindiv');">
			<i class="fa fa-cart-plus"></i> Indent Request
		</a>
		<a class="btn btn-app" href="javascript:load_form_div('/Medical_backpanel/store_stock/<?=$store_master[0]->store_id?>','maindiv');">
			<i class="fa  fa-barcode"></i> Store Stock
		</a>
	</div>
  </div>
  <div id="maindiv" class="row">
	
  </div>
 </section>
 <script>
 
 
 </script>