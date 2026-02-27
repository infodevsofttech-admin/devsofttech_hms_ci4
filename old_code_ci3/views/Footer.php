<!-- /.content-wrapper -->
<footer class="main-footer">
    <div class="pull-right hidden-xs">
        <b>Version</b>
        <?H_Name?>: Last Update : 04-07-2020
    </div>
    <strong>Copyright &copy; 2014-<?=date('Y')?> <a href="http://www.devsofttech.co.in/">DevSoft Tech</a>.</strong> All
    rights
    reserved.
</footer>
</div>
<!-- ./wrapper -->
<div id="wait"
    style="display:none;width:69px;height:89px;border:0px solid black;position:absolute;top:50%;left:50%;padding:2px;">
    <img style="width:200px" src="<?php echo base_url('assets/images/loading.gif'); ?>" />
</div>

<!-- Morris.js charts
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script> -->
<script src="<?= base_url('assets/js/raphael.min.js');?>"></script>

<script src="<?= base_url('assets/plugins/morris/morris.min.js');?>"></script>
<!-- Sparkline -->
<script src="<?= base_url('assets/plugins/sparkline/jquery.sparkline.min.js');?>"></script>
<!-- jvectormap -->
<script src="<?= base_url('assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js');?>"></script>
<script src="<?= base_url('assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js');?>"></script>
<!-- jQuery Knob Chart -->
<script src="<?= base_url('assets/plugins/knob/jquery.knob.js');?>"></script>
<!-- Select2 -->
<script src="<?= base_url('assets/plugins/select2/select2.full.min.js');?>"></script>
<!-- InputMask -->
<script src="<?= base_url('assets/plugins/input-mask/jquery.inputmask.js');?>"></script>
<script src="<?= base_url('assets/plugins/input-mask/jquery.inputmask.date.extensions.js');?>"></script>
<script src="<?= base_url('assets/plugins/input-mask/jquery.inputmask.extensions.js');?>"></script>
<!-- ChartJS -->
<script src="<?php echo base_url('assets')?>/bower_components/Chart.js/Chart.js"></script>
<!-- date-range-picker -->
<script src="<?php echo base_url('assets')?>/bower_components/moment/min/moment.min.js"></script>
<script src="<?php echo base_url('assets')?>/bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>
<!-- bootstrap datepicker -->
<script src="<?php echo base_url('assets')?>/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js">
</script>

<!-- bootstrap time picker -->
<script src="<?php echo base_url('assets')?>/plugins/timepicker/bootstrap-timepicker.min.js"></script>

<!-- SlimScroll 1.3.0 -->
<script src="<?= base_url('assets/plugins/slimScroll/jquery.slimscroll.min.js');?>"></script>
<!-- iCheck 1.0.1 -->
<script src="<?= base_url('assets/plugins/iCheck/icheck.min.js');?>"></script>

<!-- AdminLTE App -->
<script src="<?= base_url('assets/dist/js/app.min.js');?>"></script>

<!-- DataTables -->
<script src="<?php echo base_url('assets')?>/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js');?>"></script>
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.delay.min.js');?>"></script>
<script src="<?= base_url('assets/plugins/datatables/extensions/TableTools/js/dataTables.tableTools.js');?>"></script>

<!-- SlimScroll -->
<script src="<?php echo base_url('assets')?>/bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>

<!-- FastClick -->
<script src="<?php echo base_url('assets')?>/bower_components/fastclick/lib/fastclick.js"></script>

<!-- CK Editor -->
<script src="<?php echo base_url('assets')?>/bower_components/ckeditor/ckeditor.js"></script>

<!-- Bootstrap WYSIHTML5 -->
<script src="<?php echo base_url('assets')?>/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>

<!-- Page script -->
<script>
//var ResInterval = window.setInterval('myAjaxCall()', 60000); // 60 seconds

var myAjaxCall1 = function() {
    $.post('/index.php/Welcome/check_login', {
        "dtime": ""
    }, function(data) {
        if (data.login == 1) {
            window.location.href = "/Welcome/logout";
        } else {
            alert('OK');
        }
    }, 'json');
};

function myAjaxCall() {
    $.post('/index.php/Welcome/check_login', {
        "dtime": ""
    }, function(data) {
        if (data.login == 1) {
            window.location.href = "/Welcome/logout";
        }
    }, 'json');
}

function validateNumber(event) {
    var key = event.keyCode || event.which;;

    if (key == 8 || key == 46 || key == 190 ||
        key == 37 || key == 39 || key == 9) {
        return true;
    } else if (key < 48 || key > 57) {
        return false;
    } else return true;
};

function validateVarChar(event) {
    var key = event.keyCode || event.which;;

    if (key == 8 || key == 46 ||
        key == 37 || key == 39 ||
        key == 9 || key == 32 || key == 188 ||
        key == 189 || key == 190 || key == 219 || key == 221 ||
        key == 44 || key == 45 || key == 47 || key == 13) {
        return true;
    } else if (key >= 48 && key <= 57) {
        return true;
    } else if (key >= 65 && key <= 90) {
        return true;
    } else if (key >= 97 && key <= 122) {
        return true;
    } else {
        return false;
    }
};

function notify(style, var_title, var_text) {
    $.notify({
        title: var_title,
        text: var_text
    }, {
        style: 'metro',
        className: style,
        autoHide: true,
        clickToHide: true
    });
}

function enable_button(control_button) {
    control_button.disabled = false;
}

//------------------------------------------------------
function initfunc() {

    $(document).ajaxStart(function() {
        //$("#wait").css("display", "none");
    });

    $(document).ajaxComplete(function() {
        //$("#wait").css("display", "none");
    });



    $("#alert_show").hide();
    //Initialize Select2 Elements
    $(".select2").select2();

    //Datemask dd/mm/yyyy
    $("#datemask").inputmask("dd/mm/yyyy", {
        "placeholder": "dd/mm/yyyy"
    });
    //Datemask2 mm/dd/yyyy
    $("#datemask2").inputmask("mm/dd/yyyy", {
        "placeholder": "mm/dd/yyyy"
    });
    //Money Euro
    $("[data-mask]").inputmask();

    //Date range picker
    $('#reservation').inputmask("dd/mm/yyyy");
    $('#reservation').daterangepicker({
        format: 'DD/MM/YYYY'
    });

    //Date range picker with time picker
    $('.reservationtime').daterangepicker({
        timePicker: true,
        timePickerIncrement: 1,
        format: 'MM/DD/YYYY h:mm A'
    });
    //Date range as a button
    $('#daterange-btn').daterangepicker({
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf(
                    'month')]
            },
            startDate: moment().subtract(29, 'days'),
            endDate: moment()
        },
        function(start, end) {
            $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        }
    );

    //Date picker
    $('.datepicker').datepicker({
        format: "dd/mm/yyyy",
        autoclose: true
    });

    //iCheck for checkbox and radio inputs
    $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass: 'iradio_minimal-blue'
    });
    //Red color scheme for iCheck
    $('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red').iCheck({
        checkboxClass: 'icheckbox_minimal-red',
        radioClass: 'iradio_minimal-red'
    });
    //Flat red color scheme for iCheck
    $('input[type="checkbox"].flat-red, input[type="radio"].flat-red').iCheck({
        checkboxClass: 'icheckbox_flat-green',
        radioClass: 'iradio_flat-green'
    });

    //Timepicker
    $(".timepicker").timepicker({
        use24hours: true,
        format: 'HH:mm',
        showInputs: false,
        minuteStep: 1
    });

    $(".textarea").wysihtml5();


    $(".number").keypress(validateNumber);
    $(".varchar").keypress(validateVarChar);

}

$(".btn").click(function(e) {
    alert('hello');
    $(".btn").prop("disabled", true);
    setTimeout(() => {
        $(".btn").prop("disabled", false);
    }, 500)
})

function delete_varible() {
    if (typeof ResInterval_opd_list !== 'undefined') {
        clearInterval(ResInterval_opd_list);
    }
    if (typeof Webcam !== 'undefined') {
        Webcam.reset();
    }

    if (typeof player == 'object') {
        player.record().destroy();
        player = 'nothing';
    }

}

function load_form(ourl, top_title = '') {
    myAjaxCall();
    $.ajax({
        url: ourl,
        dataType: "html",
        beforeSend: function() {
            $('.content').html('loading...');
            $("#wait").css("display", "block");
        }
    }).done(function(html) {
        delete_varible();
        $("#wait").css("display", "none");
        $("#Content1").html(html);
        delete html;
        initfunc();

        if (top_title != '') {
            document.title = top_title;
        }
    });
}

function load_form_div(ourl, xdiv, top_title = '') {
    myAjaxCall();
    $.ajax({
        url: ourl,
        dataType: "html",
        beforeSend: function() {
            //$('.content').html('loading...');
            //$("#wait").css("display", "block");
        }
    }).done(function(html) {
        //$("#wait").css("display", "none");
        $("#" + xdiv).html(html);
        initfunc();

        if (top_title != '') {
            document.title = top_title;
        }
    });
}

function load_report(ourl) {
    $("#Content1").html('<embed src="' + ourl + '" width="800px" height="600px" />');

}

function load_report_div(ourl, xdiv) {
    $("#" + xdiv).html('<embed src="' + ourl + '" width="800px" height="600px" />');

}



$(document).ready(function() {
    $("#LoadForm").click(function() {
        load_form('loademail2');
    });

    $("#LoadForm2").click(function() {
        load_form('loademail');
    });

    $("#LoadForm3").click(function() {
        load_form('/website');
    });
    $("#LoadPerson").click(function() {
        load_form('Patient');
    });


});

$(function() {
    initfunc();
});
</script>
</body>