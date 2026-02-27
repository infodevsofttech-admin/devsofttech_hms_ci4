<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Ledger Account : <?=$supplier_data[0]->name_supplier?> 
                    <small> Balance : <?=$supplier_data[0]->Tot_Balance?></small></h3>
            	<div class="box-tools">
                    <a href="javascript:load_form_div('/Medical_backpanel/SupplierAccount','maindiv')" class="btn btn-success btn-sm">Back To List</a>
                    <a href="javascript:load_form_div('/Medical_backpanel/add_entry/<?=$supplier_data[0]->sid?>','search_result')" class="btn btn-warning btn-sm">Add Entry</a>
                </div>
            </div>
            <div class="box-body">
                <?php 
                $attributes = array('id' => 'form_search');
                echo form_open('/Medical_backpanel/search_result_tran/'.$supplier_data[0]->sid,$attributes); ?>
                <div class="row">
                    <div class="col-md-3">
                        <label>Statement Date Range</label>
                        <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
                            <span></span> <b class="caret"></b>
                            </div>
                            <input type="hidden" name="led_date_range" id="led_date_range"  /> 
                    </div>
                    <div class="col-md-2">
                        <label> </label>
                        <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="showreport"  >Show</button>
                        </div>
                    </div>
                    
                </div>
                 <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<div class="row" >
    <div class="col-md-12">
        <div id="search_result"></div>
    </div>
</div>
<script type="text/javascript" language="javascript" >
$(document).ready(function() {
    var start = moment();
    var end = moment();
        
    function cb(start, end) {
        $('#reportrange span').html(start.format('D-MM-YYYY') + ' - ' + end.format('D-MM-YYYY'));
        $('#led_date_range').val(start.format('YYYY-MM-DD')+'S'+end.format('YYYY-MM-DD'));
    }

    $('#reportrange').daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb);

    cb(start, end);
        
    $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
        var date_first=picker.startDate.format('YYYY-MM-DD');
        var date_second=picker.endDate.format('YYYY-MM-DD');

        $('#led_date_range').val(date_first+'S'+date_second);
    
    });

    $('#form_search').on('submit', function(form){
        form.preventDefault();
        form_array=$('#form_search').serialize();
        $("#search_result").html('Data Posting....Please Wait');
        $.post('/Medical_backpanel/search_result_tran/<?=$supplier_data[0]->sid?>', 
            form_array, function(data){
             $("#search_result").html(data);
        });
    });

    
});



</script>