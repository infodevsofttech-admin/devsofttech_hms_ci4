<style>@page {
				margin-top: 0.5cm;
				margin-bottom: 0.5cm;
				margin-left: 0.5cm;
				margin-right: 0.5cm;
			
			}
			</style>

<table border="1" width="100%" cellpadding="1" cellspacing=0 autosize="2.4">
    <thead>
        <tr>
            <th>IPD Code</th>
            <th>Patient Name</th>
            <th>IPD Doc.</th>
            <th>Admit <br>Discharge Date</th>
            <th>Item Name</th>
            <th>Rate</th>
            <th>Qty</th>
            <th>Amt.</th>
            <th>After Bill Amt.</th>
            <th>Doc. Name</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($ipd_item_data as $row){ ?>
        <tr>
            <td><?=$row->ipd_code?></td>
            <td><?=$row->p_fname?></td>
            <td><?=$row->ipd_doc_name?></td>
            <td><?=$row->register_date_str?><br><?=$row->discharge_date_str?></td>
            <td><?=$row->item_name?></td>
            <td><?=$row->item_rate?></td>
            <td><?=$row->item_qty?></td>
            <td><?=$row->item_amount?></td>
            <td><?=$row->Aft_Amt?></td>
            <td><?=$row->doc_name?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>