<section class="content-header">
    <h1>
        Person
        <small>Registration</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Person</li>
    </ol>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a aria-expanded="false" href="#" data-toggle="tab" data-target="#search">Search</a></li>
                    <li class=""><a aria-expanded="true" href="#" data-toggle="tab" data-target="#nperson">New Person</a></li>
                    <li class=""><a aria-expanded="true" href="/Patient/search_opd" data-toggle="tab" data-target="#lastopd">Last OPDs</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane " id="nperson">
                        <div class="danger" id="jsError"></div>
                        <div class="row">
                            <div class="col-md-8">
                                <?php echo form_open('Patient/create', array('role'=>'form','class'=>'form1')); ?>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Title</label>
                                            <select class="form-control" name="cbo_title" id="cbo_title"
                                                onchange="onchange_title()">
                                                <option value="Mr.">Mr.</option>
                                                <option value="Mrs.">Mrs.</option>
                                                <option value="Ms.">Ms.</option>
                                                <option value="Master" selected="selected">Master</option>
                                                <option value="Baby">Baby</option>
                                                <option value="Baby Girl">Baby Girl</option>
                                                <option value="Baby Boy">Baby Boy</option>
                                                <option value="Mohd.">Mohd.</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Full Name</label>
                                            <input class="form-control input-sm varchar" name="input_name" id=input_name
                                                placeholder="Full Name" type="text" autocomplete="off" required="true">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Gender</label>
                                            <div class="radio">
                                                <label>
                                                    <input name="optionsRadios_gender" id="options_gender1" value="1"
                                                        type="radio" checked="">
                                                    Male
                                                </label>
                                                <label>
                                                    <input name="optionsRadios_gender" id="options_gender2" value="2"
                                                        type="radio">
                                                    Female
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Aadhaar Number</label>
                                            <input class="form-control input-sm" name="input_udai" id="input_udai"
                                                placeholder="Aadhaar Number" type="text" autocomplete="off"
                                                data-inputmask='"mask": "999999999999"' data-mask
                                                onchange="onchange_aadhar()">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Phone Number</label>
                                            <input class="form-control input-sm" name="input_mphone1" id="input_mphone1"
                                                placeholder="Phone Number" type="text" autocomplete="off" required=true
                                                data-inputmask='"mask": "9999999999"' data-mask
                                                onchange="onchange_feild_number()">
                                        </div>
                                    </div>
                                </div>
                                <div class="row well well-sm">
                                    <?php
									$chk_age=0;
									if($chk_age==1)
									{
										$checkbox_checked="checked";
										$age_input_2='style="display: none;"';
										$age_input_1='';
									}else{
										$checkbox_checked="";
										$age_input_1='style="display: none;"';
										$age_input_2='';
									}
								?>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label><input id="chk_age" name="chk_age" type="checkbox" > Estimate
                                                Age</label>
                                        </div>
                                    </div>

                                    <div id="age_input_1" <?=$age_input_1?>>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label> Age (in Year - Month) </label>
                                                <table>
                                                    <tr>
                                                        <td>Year :</td>
                                                        <td><input class="form-control number input-sm"
                                                                name="input_age_year" id="input_age_year"
                                                                placeholder="Year" type="text" autocomplete="off"
                                                                style=" width:100px;"></td>
                                                        <td style=" width:50px;"></td>
                                                        <td>Month : </td>
                                                        <td><input class="form-control number input-sm"
                                                                name="input_age_month" id="input_age_month"
                                                                placeholder="Month" type="text" autocomplete="off"
                                                                style=" width:100px;"></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="age_input_2" <?=$age_input_2?>>
                                        <div class="col-md-4">
                                            <label> Date of Birth</label>
                                            <div class="input-group date input-sm">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar"></i>
                                                </div>
                                                <input class="form-control pull-right datepicker input-sm"
                                                    name="datepicker_dob" id="datepicker_dob" type="text"
                                                    data-inputmask="'alias': 'dd/mm/yyyy'" data-mask />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Relation</label>
                                            <select class="form-control input-sm" name="cbo_relation" id="cbo_relation">
                                                <option value="W/o">W/o</option>
                                                <option value="S/o">S/o</option>
                                                <option value="D/o">D/o</option>
                                                <option value="C/o">C/o</option>
                                                <option value="M/o">M/o</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Relative Name</label>
                                            <input class="form-control input-sm" name="input_relative_name"
                                                id="input_relative_name" placeholder="Full Name" type="text"
                                                onchange="onchange_relative_name()"
                                                autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Blood Group</label>
											<select class="form-control input-sm" name="input_blood_group" id="input_blood_group">
												<?php foreach($blood_group as $row) { ?>
													<option value="<?=$row->blood_group?>"><?=$row->blood_group?></option>
												<?php } ?>
											</select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Address</label>
                                            <input class="form-control input-sm" name="input_address"
                                                placeholder="Address" type="text" autocomplete="on">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>City</label>
                                            <input class="form-control input-sm" name="input_city" id="input_city"
                                                placeholder="City" type="text" autocomplete="on">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Pin/Zip Code </label>
                                            <input class="form-control input-sm" name="input_zip" id="input_zip"
                                                placeholder="Pin/Zip Code" type="text" autocomplete="on">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>District</label>
                                            <input class="form-control form-control input-sm" name="input_district"
                                                id="input_district" placeholder="District" type="text"
                                                autocomplete="on">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>State</label>
                                            <input class="form-control form-control input-sm" name="input_state"
                                                id="input_state" placeholder="State" type="text" autocomplete="on">
                                        </div>
                                    </div>
                                    <div class="col-md-3">

                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" id="RegisterPatient" name="RegisterPatient"
                                            class="btn btn-primary" accesskey="r">Register Patient</button>
                                    </div>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                            <div class="col-md-4" id="search_result_update">

                            </div>
                        </div>
                    </div>
                    <div class="tab-pane active " id="search">
                        <?php echo form_open('Patient/search', array('role'=>'form','class'=>'form2')); ?>
                        <div class="input-group input-group-sm">
                            <input class="form-control" type="text" id="txtsearch" name="txtsearch">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-info btn-flat">Go!</button>
                            </span>
                        </div>
                        <?php echo form_close(); ?>
                        <div class="searchresult"></div>
                    </div>
                    <div class="tab-pane " id="lastopd">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
$(document).ready(function() {
    $('form.form1').on('submit', function(form) {
        $("#RegisterPatient").prop("disabled", true);
        form.preventDefault();

        $.post('/index.php/Patient/create', $('form.form1').serialize(), function(data) {
            if (data.insertid == 0) {
                notify('error', 'Please Attention', data.error_text);
                $("#RegisterPatient").prop("disabled", false);
            } else {
                load_form('/Patient/person_record/' + data.insertid);
            }
        }, 'json');
    });

    $('form.form2').on('submit', function(form) {
        form.preventDefault();
        $.post('/index.php/Patient/search', $('form.form2').serialize(), function(data) {
            $('div.searchresult').html(data);
            $('#example1').DataTable();
        });
    });

    $("#input_city").autocomplete({
        source: "Patient/city",
        minLength: 3,
        autofocus: true,
        select: function(event, ui) {
            $("#input_district").val(ui.item.l_district);
            $("#input_state").val(ui.item.l_state);
        }
    });

    $("#chk_age").on("click", function() {
        if (this.checked) {
            $('#age_input_1').show();
            $('#age_input_2').hide();
        } else {
            $('#age_input_1').hide();
            $('#age_input_2').show();
        }
    });

    
    function split(val) {
        return val.split(/ \s*/);
    }

    function extractLast(term) {
        return split(term).pop();
    }

    $('[data-toggle="tab"]').click(function(e) {
        e.preventDefault()
        var loadurl = $(this).attr('href')
        if (loadurl != '#') {
            var targ = $(this).attr('data-target')
            $.get(loadurl, function(data) {
                $(targ).html(data)
            });
        }

        $(this).tab('show')
    });

    $("#input_name")
        // don't navigate away from the field on tab when selecting an item
        .on("keydown", function(event) {
            if (event.keyCode === $.ui.keyCode.TAB &&
                $(this).autocomplete("instance").menu.active) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function(request, response) {
                $.getJSON("Patient/get_name", {
                    term: extractLast(request.term)
                }, response);
            },
            search: function() {
                // custom minLength
                var term = extractLast(this.value);
                if (term.length < 2) {
                    return false;
                }
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function(event, ui) {
                var terms = split(this.value);
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push(ui.item.value);
                // add placeholder to get the comma-and-space at the end
                terms.push("");
                this.value = terms.join(" ");
                return false;
            }
        });

    $("#input_relative_name")
        // don't navigate away from the field on tab when selecting an item
        .on("keydown", function(event) {
            if (event.keyCode === $.ui.keyCode.TAB &&
                $(this).autocomplete("instance").menu.active) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function(request, response) {
                $.getJSON("Patient/get_name", {
                    term: extractLast(request.term)
                }, response);
            },
            search: function() {
                // custom minLength
                var term = extractLast(this.value);
                if (term.length < 2) {
                    return false;
                }
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function(event, ui) {
                var terms = split(this.value);
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push(ui.item.value);
                // add placeholder to get the comma-and-space at the end
                terms.push("");
                this.value = terms.join(" ");
                return false;
            }
        });

        $('[data-toggle="gpill"]').click(function(e) {
            e.preventDefault()
            var loadurl = $(this).attr('href')
            if (loadurl != '#') {
                var targ = $(this).attr('data-target')
                $.get(loadurl, function(data) {
                    $(targ).html(data)
                });
            }

            $(this).tab('show')
        });

});

function onchange_feild_number(control_button) {
    var input_mphone1 = $('#input_mphone1').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/index.php/Patient/search_adv', {
        "input_mphone1": input_mphone1,
        '<?=$this->security->get_csrf_token_name()?>': csrf_value
    }, function(data) {
        $('#search_result_update').html(data);
    });
}

function onchange_aadhar(control_button) {
    var input_udai = $('#input_udai').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/index.php/Patient/search_adv', {
        "input_udai": input_udai,
        '<?=$this->security->get_csrf_token_name()?>': csrf_value
    }, function(data) {
        $('#search_result_update').html(data);
    });
}

function onchange_relative_name(control_button) {
    var input_relative_name = $('#input_relative_name').val();
    var input_name = $('#input_name').val();
    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    $.post('/index.php/Patient/search_adv', {
        "input_relative_name": input_relative_name,
        "input_name": input_name,
        '<?=$this->security->get_csrf_token_name()?>': csrf_value
    }, function(data) {
        $('#search_result_update').html(data);
    });

}

function onchange_title() {
    var d = $('#cbo_title').val();

    if (d == "Mr." || d == "Master" || d == "Baby Boy") {
        $("#options_gender1").prop("checked", true);
    } else {
        $("#options_gender2").prop("checked", true);
    }
}
</script>