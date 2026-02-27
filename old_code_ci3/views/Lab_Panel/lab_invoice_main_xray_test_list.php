<?php
if (count($lab_invoice_request) > 0) {
?>
	<div class="row">
		<div Class="col-md-2">
			<p class="text-muted">Sr.No.: <?= $lab_invoice_request[0]->daily_sr_no ?>
		</div>
		<div Class="col-md-4">
			<div class="input-group input-group-sm">
				<input type="hidden" id="lab_req_id" name="lab_req_id" value="<?= $lab_invoice_request[0]->id ?>">
				<input type="Text" class="form-control" id="inputLabNo" name="inputLabNo" placeholder="Lab Test No." value="<?= $lab_invoice_request[0]->lab_test_no ?>">
				<span class="input-group-btn">
					<button type="button" class="btn btn-info btn-flat" onclick="update_lab_no()">Update Lab Test No.</button>
				</span>
			</div>
		</div>
	</div>
<?php
}

foreach ($testlist as $row) {
	echo '<strong>' . $row->item_name . '</strong>';
	if ($row->check_sample < 1) {
		echo '<p class="text-muted">';
		echo '<a href="javascript:update_request(' . $row->test_id . ')" class="btn btn-danger btn-xs"  >Accept Request</a> ';
	} else {
		echo '<p class="text-muted">';

		if ($row->status > 0) {
			if ($row->status == 2) {
				echo '<button  type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#tallModal"';
				echo 'data-testid="' . $row->test_id . '" data-testname="' . $row->item_name . '" data-repoid="' . $row->req_id . '" data-etype="6">Print Report</button> ';
				
				echo '<button  type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#tallModal"';
				echo 'data-testid="' . $row->test_id . '" data-testname="' . $row->item_name . '" data-repoid="' . $row->req_id . '" data-etype="8">Open for Edit</button> ';

			} else {
				echo '<button  type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#tallModal"';
				echo 'data-testid="' . $row->req_id . '" data-testname="' . $row->item_name . '" data-etype="1">Report Create</button> ';

				echo '<button  type="button" class="btn btn-danger btn-xs" data-toggle="modal" data-target="#tallModal"';
					echo 'data-testid="'.$row->test_id.'" data-testname="'.$row->item_name.'" data-repoid="'.$row->req_id.'" data-etype="7">Report Edit</button> ';

				echo '<button  type="button" class="btn btn-info btn-xs" onclick="report_item_remove(' . $row->req_id . ')" > Remove</button> ';
			}
		} else {
			echo '<button  type="button" class="btn btn-warning btn-xs" data-toggle="modal" data-target="#tallModal"';
			echo 'data-testid="' . $row->req_id . '" data-testname="' . $row->item_name . '" data-etype="1">Report Pending</button> ';
		}

		echo '	</div>
				</div>';

		echo '</p>';
	}

	echo '<hr />';
}
?>