<section class="content">
    <div class="row">
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Glucose Chart</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i
                                class="fa fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-box-tool" data-widget="add"><i
                                class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart">
                        <canvas id="lineChart" style="height:250px"></canvas>
                    </div>
                </div>
                <!-- /.box-body -->
            </div>
        </div>
        <div class="col-md-6">
            <!-- LINE CHART -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Glucose Value</h3>
                </div>
                <div class="box-body">
                <?php echo form_open('Opd_prescription/add_glucose_value', array('role'=>'form','class'=>'form_g')); ?>
                    <div class="row">
                        <div class="col-md-4" style="padding: 0px;">
                            <div class="form-group">
                                <label> Date of Test</label>
                                <div class="input-group date input-sm" style="padding: 0px;">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input class="form-control pull-right datepicker input-sm" name="datepicker_dot"
                                        id="datepicker_dot" type="text" data-inputmask="'alias': 'dd/mm/yyyy'"
                                        data-mask value="<?=date('d/m/Y')?>" />
                                </div>
                                <!-- /.input group -->
                            </div>
                        </div>
                        <div class="col-md-3" style="padding: 0px;">
                            <div class="form-group input-sm">
                                <label>Glucose Test</label>
                                <select class="form-control input-sm"  id="input_test_type" name="input_test_type">
                                    <option value="1">Fasting</option>
                                    <option value="2">After Food</option>
                                    <option value="0" selected>Random</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2" style="padding: 0px;">
                            <div class="form-group input-sm">
                                <label for="input_glucose_value">Value</label>
                                <input type="number" class="form-control input-sm"  id="input_glucose_value" name="input_glucose_value"
                                    placeholder="Glucose Value" value="95">
                            </div>
                        </div>
                        <div class="col-md-3" style="padding: 0px;">
                            <button type="button" onclick="save_glucose_value()" class="btn btn-block btn-danger input-sm" id="btn_add">Add Data</button>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                    <div class="row">
                        <table class="table">
                            <tr>
                                <th style="text-align:center ;">Date</th>
                                <th style="text-align:center ;">Fast Glucose</th>
                                <th style="text-align:center ;">After Food Glucose</th>
                                <th style="text-align:center ;">Random Glucose</th>
                                <th style="text-align:center ;">Remove</th>
                            </tr>
                        <?php foreach($patient_glucose_data as $row){ ?>
                            <tr>
                                <td style="text-align:center ;"><?=$row->test_date?></td>
                                <td style="text-align:center ;"><?=$row->r_sugar?></td>
                                <td style="text-align:center ;"><?=$row->f_sugar?></td>
                                <td style="text-align:center ;"><?=$row->a_sugar?></td>
                                <td style="text-align:center ;"><button type="button" onclick="delete_glucose_value(<?=$row->id?>)" class="btn btn-block btn-warning input-sm" id="btn_add">Delete</button></td>
                            </tr>
                        <?php } ?>
                        </table>
                    </div>
                </div>
                <!-- /.box-body -->
            </div>
        </div>
    </div>
</section>
<script>
$(function() {

    $('#datepicker_dot').datepicker({
        autoclose: true,
        format: 'dd/mm/yyyy'
    })

    //Timepicker
    $('#test_time').timepicker({
        showInputs: false
    })

    var areaChartData = {
        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
        datasets: [{
                label: 'Fasting blood sugar',
                fillColor: 'rgba(210, 214, 222, 1)',
                strokeColor: 'rgba(210, 214, 222, 1)',
                pointColor: 'rgba(210, 214, 222, 1)',
                pointStrokeColor: '#c1c7d1',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(220,220,220,1)',
                data: [65, 59, 80, 81, 56, 55, 40]
            },
            {
                label: 'After Food Blood Sugar',
                fillColor: 'rgba(60,141,188,0.9)',
                strokeColor: 'rgba(60,141,188,0.8)',
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: [28, 48, 40, 19, 86, 27, 90]
            },
            {
                label: 'Random Blood Sugar',
                fillColor: 'rgba(80,141,188,0.9)',
                strokeColor: 'rgba(80,141,188,0.8)',
                pointColor: '#3b8fba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: [30, 48, 86, 27, 90, 40, 19]
            }
        ]
    }

    var areaChartOptions = {
        //Boolean - If we should show the scale at all
        showScale: true,
        //Boolean - Whether grid lines are shown across the chart
        scaleShowGridLines: false,
        //String - Colour of the grid lines
        scaleGridLineColor: 'rgba(0,0,0,.05)',
        //Number - Width of the grid lines
        scaleGridLineWidth: 1,
        //Boolean - Whether to show horizontal lines (except X axis)
        scaleShowHorizontalLines: true,
        //Boolean - Whether to show vertical lines (except Y axis)
        scaleShowVerticalLines: true,
        //Boolean - Whether the line is curved between points
        bezierCurve: true,
        //Number - Tension of the bezier curve between points
        bezierCurveTension: 0.3,
        //Boolean - Whether to show a dot for each point
        pointDot: false,
        //Number - Radius of each point dot in pixels
        pointDotRadius: 4,
        //Number - Pixel width of point dot stroke
        pointDotStrokeWidth: 1,
        //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
        pointHitDetectionRadius: 20,
        //Boolean - Whether to show a stroke for datasets
        datasetStroke: true,
        //Number - Pixel width of dataset stroke
        datasetStrokeWidth: 2,
        //Boolean - Whether to fill the dataset with a color
        datasetFill: true,
        //String - A legend template
        legendTemplate: '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].lineColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
        //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
        maintainAspectRatio: true,
        //Boolean - whether to make the chart responsive to window resizing
        responsive: true
    }
    //Create the line chart
    //areaChart.Line(areaChartData, areaChartOptions)

    //-------------
    //- LINE CHART -
    //--------------
    var lineChartCanvas = $('#lineChart').get(0).getContext('2d')
    var lineChart = new Chart(lineChartCanvas)
    var lineChartOptions = areaChartOptions
    lineChartOptions.datasetFill = false
    lineChart.Line(areaChartData, lineChartOptions)


});

function save_glucose_value()
{
    $("#btn_add").prop("disabled", true);

    $.post('/Opd_prescription/add_glucose_value/<?=$p_id?>', $('form.form_g').serialize(), function(data) {
            if (data.insertid == 0) {
                notify('msg', 'Please Attention', data.msg);
                $("#btn_add").prop("disabled", false);
            } else {
                notify('success', 'Data Added', data.msg);
                load_form_div('/Opd_prescription/patient_glucose_data/<?=$p_id ?>','glucosechart');
            }
        }, 'json');
}

function delete_glucose_value(g_id)
{
    if(confirm('Are you sure to delete this entry'))
    {
        load_form_div('/Opd_prescription/patient_glucose_data_del/<?=$p_id ?>/'+g_id,'glucosechart');
        
    }
}



</script>