<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Patient Management</h5>
                    <ul class="nav nav-tabs" id="patientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="search-tab" data-bs-toggle="tab"
                                data-bs-target="#search" type="button" role="tab" aria-controls="search"
                                aria-selected="true">Search</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="nperson-tab" data-bs-toggle="tab"
                                data-bs-target="#nperson" type="button" role="tab" aria-controls="nperson"
                                aria-selected="false">New Person</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="lastopd-tab" data-bs-toggle="tab"
                                data-bs-target="#lastopd" type="button" role="tab" aria-controls="lastopd"
                                aria-selected="false" data-url="<?= base_url('billing/patient/search_opd') ?>">Last OPDs</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-2" id="patientTabsContent">
                        <div class="tab-pane fade" id="nperson" role="tabpanel" aria-labelledby="nperson-tab">
                            <div class="danger" id="jsError"></div>
                            <div class="row">
                                <div class="col-md-8">
                                    <form action="<?= base_url('billing/patient/create') ?>" method="post" role="form" class="form1 needs-validation" novalidate>
                                        <?= csrf_field() ?>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Aadhaar Number</label>
                                                    <input class="form-control" name="input_udai" id="input_udai"
                                                        placeholder="Aadhaar Number" type="text" autocomplete="off"
                                                        data-inputmask='"mask": "999999999999"' data-mask
                                                        onchange="onchange_aadhar()">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Phone Number</label>
                                                    <input class="form-control" name="input_mphone1" id="input_mphone1"
                                                        placeholder="Phone Number" type="text" autocomplete="off" required
                                                        data-inputmask='"mask": "9999999999"' data-mask
                                                        onchange="onchange_feild_number()">
                                                    <div class="invalid-feedback">Please enter phone number.</div>
                                                </div>
                                            </div>
                                            <hr/>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label">Title</label>
                                                    <select class="form-select" name="cbo_title" id="cbo_title"
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
                                                    <label class="form-label">Full Name</label>
                                                    <input class="form-control varchar" name="input_name" id="input_name"
                                                        placeholder="Full Name" type="text" autocomplete="off" required>
                                                    <div class="invalid-feedback">Please enter full name.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Gender</label>
                                                    <div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" name="optionsRadios_gender" id="options_gender1" value="1"
                                                                type="radio" checked>
                                                            <label class="form-check-label" for="options_gender1">Male</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" name="optionsRadios_gender" id="options_gender2" value="2"
                                                                type="radio">
                                                            <label class="form-check-label" for="options_gender2">Female</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <hr />
                                        <div class="row g-3">
                                            <?php
                                            $chk_age = 0;
                                            if ($chk_age == 1) {
                                                $checkbox_checked = "checked";
                                                $age_input_2 = 'style="display: none;"';
                                                $age_input_1 = '';
                                            } else {
                                                $checkbox_checked = "";
                                                $age_input_1 = 'style="display: none;"';
                                                $age_input_2 = '';
                                            }
                                            ?>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="chk_age" name="chk_age" <?= $checkbox_checked ?>>
                                                    <label class="form-check-label" for="chk_age">Estimate Age</label>
                                                </div>
                                            </div>

                                            <div class="col-md-6" id="age_input_1" <?= $age_input_1 ?>>
                                                <div class="form-group">
                                                    <label class="form-label">Age (in Year - Month)</label>
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <input class="form-control number"
                                                                name="input_age_year" id="input_age_year"
                                                                placeholder="Year" type="text" autocomplete="off">
                                                        </div>
                                                        <div class="col-6">
                                                            <input class="form-control number"
                                                                name="input_age_month" id="input_age_month"
                                                                placeholder="Month" type="text" autocomplete="off">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4" id="age_input_2" <?= $age_input_2 ?>>
                                                <label class="form-label">Date of Birth</label>
                                                <input class="form-control" name="datepicker_dob" id="datepicker_dob"
                                                    type="date" />
                                            </div>
                                        </div>
                                        <hr />

                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Relation</label>
                                                    <select class="form-select" name="cbo_relation" id="cbo_relation">
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
                                                    <label class="form-label">Relative Name</label>
                                                    <input class="form-control" name="input_relative_name"
                                                        id="input_relative_name" placeholder="Full Name" type="text"
                                                        onchange="onchange_relative_name()"
                                                        autocomplete="off" required>
                                                    <div class="invalid-feedback">Please enter relative name.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Blood Group</label>

                                                    <select class="form-select" name="input_blood_group" id="input_blood_group">
                                                        <?php foreach ($blood_group as $row) { ?>
                                                            <option value="<?= $row->blood_group ?>"><?= $row->blood_group ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Address</label>
                                                    <input class="form-control" name="input_address"
                                                        placeholder="Address" type="text" autocomplete="on">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">City</label>
                                                    <input class="form-control" name="input_city" id="input_city"
                                                        placeholder="City" type="text" autocomplete="on">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Pin/Zip Code</label>
                                                    <input class="form-control" name="input_zip" id="input_zip"
                                                        placeholder="Pin/Zip Code" type="text" autocomplete="on">
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">District</label>
                                                    <input class="form-control" name="input_district"
                                                        id="input_district" placeholder="District" type="text"
                                                        autocomplete="on">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">State</label>
                                                    <input class="form-control" name="input_state"
                                                        id="input_state" placeholder="State" type="text" autocomplete="on">
                                                </div>
                                            </div>
                                        </div>
                                        <hr />
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <button type="submit" id="RegisterPatient" class="btn btn-primary">Register Patient</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-4" id="search_result_update">

                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="search" role="tabpanel" aria-labelledby="search-tab">
                            <form action="<?= base_url('billing/patient/search') ?>" method="post" role="form" class="form2 needs-validation" novalidate>
                                <?= csrf_field() ?>
                                <div class="input-group">
                                    <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Search">
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-info btn-flat">Go!</button>
                                    </span>
                                </div>
                            </form>
                            <div class="searchresult"></div>
                        </div>
                        <div class="tab-pane fade" id="lastopd" role="tabpanel" aria-labelledby="lastopd-tab">

                        </div>
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

            $.post('<?= base_url('billing/patient/create') ?>', $('form.form1').serialize(), function(data) {
                if (data.insertid == 0) {
                    notify('error', 'Please Attention', data.error_text);
                    $("#RegisterPatient").prop("disabled", false);
                } else {
                    load_form('<?= base_url('billing/patient/person_record') ?>/' + data.insertid);
                }
            }, 'json');
        });

        $('form.form2').on('submit', function(form) {
            form.preventDefault();
            $.post('<?= base_url('billing/patient/search') ?>', $('form.form2').serialize(), function(data) {
                $('div.searchresult').html(data);
                $('#example1').DataTable();
            });
        });

        $("#input_city").autocomplete({
            source: "<?= base_url('Patient/city') ?>",
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

        $('[data-bs-toggle="tab"]').click(function(e) {
            e.preventDefault();
            var loadurl = $(this).data('url') || $(this).attr('href');
            if (loadurl && loadurl !== '#') {
                var targ = $(this).attr('data-bs-target');
                $.get(loadurl, function(data) {
                    $(targ).html(data);
                });
            }

            var tabTrigger = new bootstrap.Tab(this);
            tabTrigger.show();
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
                    $.getJSON("<?= base_url('Patient/get_name') ?>", {
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
                    $.getJSON("<?= base_url('Patient/get_name') ?>", {
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

        $('[data-bs-toggle="gpill"]').click(function(e) {
            e.preventDefault();
            var loadurl = $(this).data('url') || $(this).attr('href');
            if (loadurl && loadurl !== '#') {
                var targ = $(this).attr('data-bs-target');
                $.get(loadurl, function(data) {
                    $(targ).html(data);
                });
            }

            var tabTrigger = new bootstrap.Tab(this);
            tabTrigger.show();
        });

    });

    function getCsrfData() {
        var csrfName = '<?= csrf_token() ?>';
        var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        return {
            name: csrfName,
            value: csrfValue
        };
    }

    function onchange_feild_number(control_button) {
        var input_mphone1 = $('#input_mphone1').val();
        var csrf = getCsrfData();

        $.post('<?= base_url('billing/patient/search_adv') ?>', {
            "input_mphone1": input_mphone1,
            [csrf.name]: csrf.value
        }, function(data) {
            $('#search_result_update').html(data);
        });
    }

    function onchange_aadhar(control_button) {
        var input_udai = $('#input_udai').val();
        var csrf = getCsrfData();

        $.post('<?= base_url('billing/patient/search_adv') ?>', {
            "input_udai": input_udai,
            [csrf.name]: csrf.value
        }, function(data) {
            $('#search_result_update').html(data);
        });
    }

    function onchange_relative_name(control_button) {
        var input_relative_name = $('#input_relative_name').val();
        var input_name = $('#input_name').val();
        var csrf = getCsrfData();

        $.post('<?= base_url('billing/patient/search_adv') ?>', {
            "input_relative_name": input_relative_name,
            "input_name": input_name,
            [csrf.name]: csrf.value
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