<?php
	if(count($lab_invoice_request)>0){
		echo '<div class="row">';		
		echo '<div Class="col-xs-3"><p class="text-muted">Sr.No.: '.$lab_invoice_request[0]->daily_sr_no;
		echo ' </div> ';
		echo '<div Class="col-xs-6">';
		echo '<div class="input-group input-group-sm">';
		echo '<input type="hidden" id="lab_req_id" name="lab_req_id" value="'.$lab_invoice_request[0]->id.'">';
		echo '<input type="Text" class="form-control" id="inputLabNo" name="inputLabNo" placeholder="Lab Test No." value="'.$lab_invoice_request[0]->lab_test_no.'" >
				<span class="input-group-btn">
                      <button type="button" class="btn btn-info btn-flat" onclick="update_lab_no()">Update Lab Test No.</button>
                    </span>
				</div>';
		echo '</div>';
		echo '</div> ';
	}

	foreach($testlist as $row)
	{
		echo '<strong>'.$row->item_name.'</strong>';
		if($row->check_sample<1)
		{
			echo '<p class="text-muted">';
			echo '<a href="javascript:update_request('.$row->test_id.')" class="btn btn-danger btn-xs"  >Sample Collection</a> ';
		}else{
			$check='';
			if ($row->print_combine>0)
			{
				$check='Checked';
			}
			echo '<p class="text-muted">';

			if($row->status>0)
			{
				if($row->status==2)
				{
					echo '<button  type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#tallModal"';
					echo 'data-testid="'.$row->test_id.'" data-testname="'.$row->item_name.'" data-repoid="'.$row->req_id.'" data-etype="6">Print Single Report</button> ';

					echo '<button  type="button" class="btn btn-info btn-xs" onclick="report_item_remove('.$row->req_id.')" > Remove</button> ';
				}else{
					echo '<button  type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#tallModal"';
					echo 'data-testid="'.$row->req_id.'" data-testname="'.$row->item_name.'" data-etype="1">Data Update</button> ';

					echo '<button  type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#tallModal"';
					echo 'data-testid="'.$row->test_id.'" data-testname="'.$row->item_name.'" data-repoid="'.$row->req_id.'" data-etype="7">Edit</button> ';

					echo '<button  type="button" class="btn btn-info btn-xs" onclick="report_item_remove('.$row->req_id.')" > Remove</button> ';

				}
			}else{
				echo '<button  type="button" class="btn btn-warning btn-xs" data-toggle="modal" data-target="#tallModal"';
				echo 'data-testid="'.$row->req_id.'" data-testname="'.$row->item_name.'" data-etype="1">Data Pending</button> ';
			}
			
			echo '<button class="btn btn-primary btn-xs" type="button" data-toggle="collapse" 
				data-target="#collapse'.$row->req_id.'" aria-expanded="false" aria-controls="collapse'.$row->req_id.'">
				:::::
				</button>';
			echo '&nbsp;&nbsp;&nbsp;';
			echo '<label><input type="checkbox"  id="CHK_'.$row->req_id.'" onchange="onChangeUpdate(this,'.$row->req_id.')" '.$check.'> Print Combine </label>';
			echo '<div class="collapse" id="collapse'.$row->req_id.'">
						<div class="card card-body">';
			echo '</p>';
			echo '<p class="text-muted">';

			echo '<button  type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#tallModal"';
			echo 'data-testid="'.$row->test_id.'" data-testname="'.$row->item_name.'" data-repoid="'.$row->req_id.'" data-etype="3">Upload Files</button> ';
			
			echo '<button  type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#tallModal"';
			echo 'data-testid="'.$row->test_id.'" data-testname="'.$row->item_name.'" data-repoid="'.$row->req_id.'" data-etype="2">Scan</button> ';
			
			echo '<button  type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#tallModal"';
			echo 'data-testid="'.$row->test_id.'" data-testname="'.$row->item_name.'" data-repoid="'.$row->req_id.'" data-etype="4">Show Files</button> ';
			
			
			if($row->status==2)
			{
				echo '<button  type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#tallModal"';
				echo 'data-testid="'.$row->test_id.'" data-testname="'.$row->item_name.'" data-repoid="'.$row->req_id.'" data-etype="8">Open for Edit</button> ';
			}

			echo '	</div>
				</div>';
			
			echo '</p>';
			
		}
		
		echo '<hr />';
	}
?>
	