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
            <th>Refer Name</th>
            <th>Net Amount</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($ipd_refer_data as $row){ ?>
        <tr>
            <td><?=$row->ipd_code?></td>
            <td><?=$row->p_fname?></td>
            <td><?=$row->ipd_doc_name?></td>
            <td><?=$row->register_date_str?><br><?=$row->discharge_date_str?></td>
            <td><?=$row->title?> <?=$row->f_name?> [<?=$row->type_desc?>] </td>
            <td><?=$row->net_amount?></td>
            <td><?=$row->balance_amount?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>