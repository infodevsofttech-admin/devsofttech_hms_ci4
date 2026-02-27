<table id="ipd_panel_sub" class="table table-striped table-bordered ">
<thead>
    <tr>
        <th>IPD No. Info</th>
        <th>Admit /Discharge Dt</th>
    </tr>
</thead>
<tbody>
<?php foreach($ipd_data as $c){ ?>
    <tr>
        <td>
            <?=$c->ipd_code?> <br>
            <?=$c->p_fname?><br>
            <?=$c->str_age?> / <?=$c->xgender?><br/>
            <?=$c->pay_type?><br/><br/>
            <?php if($c->discount_group_2>0){ ?>
                <button onclick="open_ipd_bill(<?=$c->id?>)">Open</button>
            <?php } ?>
            <button onclick="open_ipd_purchase(<?=$c->id?>)">Get Purchase Rate</button>
        </td>
        <td>
            Admit Dt :<?=$c->admit_dt?><br>
            Discharge Dt :<?=$c->discharge_dt?><br/>
            Net Amount : <?=$c->net_amount?><br/>
            Paid Amt. : <?=$c->payment_received?><br/>
            Balance. : <?=$c->payment_balance?>
        </td>
    </tr>
<?php } ?>
</tbody>
<tfoot>
    <tr>
        <th>IPD No. Info</th>
        <th>Admit /Discharge Dt</th>
    </tr>
</tfoot>
</table>
<div id="show_msg"></div>
<script>
    $(document).ready(function() {
        $('#ipd_panel_sub').DataTable();
    });

    function open_ipd_bill(ipd_id)
    {
       load_report_div('/Medical_app/open_ipd_bill/'+ipd_id,'show_msg');
    }

    function open_ipd_purchase(ipd_id)
    {
        load_report_div('/Medical_app/get_ipd_bill_purchase/'+ipd_id,'show_msg');
    }
</script>