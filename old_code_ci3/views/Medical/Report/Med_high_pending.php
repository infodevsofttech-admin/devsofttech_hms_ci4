<section class="content-header">
    <h1>
        IPD High Pending Balance Patient  
    </h1>
</section>
<section class="content">
    <div class="row">
		<div class="col-md-12">
			<table class="table">
                <tr>
                    <th>
                        Sr.
                    </th>
                    <th>
                        IPD No.
                    </th>
                    <th>
                        UHID No.
                    </th>
                    <th>
                        Patient Name
                    </th>
                    <th>
                        Phone No.
                    </th>
                    <th>
                        Total Bill
                    </th>
                    <th>
                        Paid Amount
                    </th>
                    <th>
                        Balance
                    </th>
                </tr>
                <?php 
                $sr_no=1;
                foreach($high_balance as $row){ ?>
                    <tr>
                        <th>
                            <?=$sr_no?>
                        </th>
                        <th>
                            <?=$row->ipd_code?>
                        </th>
                        <th>
                            <?=$row->p_code?>
                        </th>
                        <th>
                            <?=$row->p_fname?>
                        </th>
                        <th>
                            <?=$row->mphone1?>
                        </th>
                        <th>
                            <?=$row->net_amount?>
                        </th>
                        <th>
                            <?=$row->payment_received?>
                        </th>
                        <th>
                            <?=$row->payment_balance?>
                        </th>
                    </tr>
                <?php } ?>
            </table>
		</div>
	</div>
</section>
