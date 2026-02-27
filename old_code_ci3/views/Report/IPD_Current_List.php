<section class="content-header">
    <h1>
        Current Admit IPD List   
        <small>Panel</small>
    </h1>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-3">
			<label>Doctor Name</label>
			<select class="form-control" id="doc_name_id" name="doc_name_id"   >
				<option value='0'  >All Doctors</option>
				<?php 
				foreach($doclist as $row)
				{ 
					echo '<option value='.$row->id.'  >'.$row->p_fname.'</option>';
				}
				?>
			</select>
		</div>
		<div class="col-md-2">
			<label>Type</label>
			<select class="form-control" id="ipd_type" name="ipd_type" >
				<option value='0'  >All</option>
				<?php 
				foreach($ins_group as $row)
				{ 
					echo '<option value='.$row->id.'  >'.$row->tpa_group.'</option>';
				}
				echo '<option value="0" disabled >--------------------</option>';
				foreach($hc_insurance as $row)
				{ 
					echo '<option value='.($row->id*-1).'  >'.$row->short_name.'</option>';
				}

				?>
			</select>
		</div>
		<div class="col-md-2">
			<label>Order By</label>
			<select class="form-control" id="order_by" name="order_by" >
				<option value='0' >ID</option>
				<option value='1' >Date</option>
				<option value='2' >Name</option>
				<option value='3' >Bed No.</option>
			</select>
		</div>
		<div class="col-md-3">
			<label> </label>
			<div class="form-group">
				<button type="button" class="btn btn-primary" id="showreport"  >Show</button>
				<button type="button" class="btn btn-primary" id="showreportexcel"  >Excel</button>
			</div>
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-12">
			<div id="show_report"></div>
		</div>
	</div>
</section>
<script type="text/javascript" language="javascript" >
			$(document).ready(function() {
				
				$('#showreport').click( function()
				{
					var doc_name_id=$('#doc_name_id').val();
					
					if(doc_name_id==null)
					{
						doc_name_id="0";
					}
										
					var ipd_type=$('#ipd_type').val();
					var ipd_date=$('#ipd_date').val();
					var order_by=$('#order_by').val();
					
					var Get_Query="/Report3/ipd_current"+
					"/"+doc_name_id+ "/"+ipd_type+
					"/"+order_by;
					load_form_div(Get_Query,'show_report');
					
				});
				
				$('#showreportexcel').click( function()
				{
					var doc_name_id=$('#doc_name_id').val();
					
					if(doc_name_id==null)
					{
						doc_name_id="0";
					}
										
					var ipd_type=$('#ipd_type').val();
					var ipd_date=$('#ipd_date').val();
					var order_by=$('#order_by').val();
					
					var Get_Query="/Report3/ipd_current"+
					"/"+doc_name_id+ "/"+ipd_type+
					"/"+order_by+"/1";
					
					window.open(Get_Query, "_blank");
					
					
				});
	
			
				
		});
</script>