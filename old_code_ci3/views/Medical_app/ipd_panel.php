<style>
    .responsive {
      width: 100%;
      max-width: 400px;
      height: auto;
      padding: 2px;
    }
</style>
   <div class="row">
        <div class="box box-primary">
            <div class="box-header with-border">
                Current IPD List 
                <div class="box-tools pull-right">
                    <a class="btn  btn-success" href="javascript:load_form('/Medical_app/ipd_panel_search')">Search Other IPD</a>
                    <a class="btn  btn-warning" href="javascript:load_form('/Medical_app/search_ipd')">Search Bill</a>
                </div>
            </div>
            <div class="box-body box-profile">
                <table id="ipd_panel" class="table table-striped table-bordered ">
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
            </div>
            <div class="box-footer ">
                <div id="show_msg"></div>
            </div>
        </div>
    </div>

<script>
    $(document).ready(function() {
        $('#ipd_panel').DataTable();
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