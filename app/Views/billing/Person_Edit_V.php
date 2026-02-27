<div class="pagetitle">
    <h1>Person Profile - <?=$data[0]->p_fname; ?> <?=$data[0]->p_code; ?></h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[0]->id?>/0');">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[0]->id?>/0');">Profile</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>
</div>
<?php
    $user = auth()->user();
    $isAdmin = is_object($user) && method_exists($user, 'inGroup') ? $user->inGroup('admin') : false;
    if ($data[0]->p_edit == 1 || $isAdmin) {
        $readonly = '';
    } else {
        $readonly = 'readonly';
    }
?>
<!-- Main content -->
<section class="content">
    <div class="jsError"></div>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Person Profile</h3>
                </div>
                <div class="box-body">
                    <form role="form" class="form1" method="post" action="<?= base_url('billing/patient/update') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" value="<?=$data[0]->id ?>" id="p_id" name="p_id" />
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Title</label>
                                <select class="form-control" name="cbo_title" id="cbo_title"
                                    onchange="onchange_title()">
                                    <option value="Mr." <?=combo_checked("Mr.",$data[0]->title)?>>Mr.</option>
                                    <option value="Mrs." <?=combo_checked("Mrs.",$data[0]->title)?>>Mrs.</option>
                                    <option value="Ms." <?=combo_checked("Ms.",$data[0]->title)?>>Ms.</option>
                                    <option value="Master" <?=combo_checked("Master",$data[0]->title)?>>Master</option>
                                    <option value="Baby" <?=combo_checked("Baby",$data[0]->title)?>>Baby</option>
                                    <option value="Baby Girl" <?=combo_checked("Baby Girl",$data[0]->title)?>>Baby Girl
                                    </option>
                                    <option value="Baby Boy" <?=combo_checked("Baby Boy",$data[0]->title)?>>Baby Boy
                                    </option>
                                    <option value="Mohd." <?=combo_checked("Mohd.",$data[0]->title)?>>Mohd.
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input class="form-control input-sm" name="input_name" placeholder="Full Name"
                                    value="<?=$data[0]->p_fname ?>" type="text" autocomplete="off" <?=$readonly?>>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Gender</label>
                                <div class="radio">
                                    <label>
                                        <input name="optionsRadios_gender" id="options_gender1" value="1"
                                            <?=radio_checked("1",$data[0]->gender)?> type="radio">
                                        Male
                                    </label>
                                    <label>
                                        <input name="optionsRadios_gender" id="options_gender2" value="2"
                                            <?=radio_checked("2",$data[0]->gender)?> type="radio">
                                        Female
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Aadhar No.</label>
                                <input class="form-control input-sm" name="input_Aadhar" id="input_Aadhar"
                                    value="<?=$data[0]->udai ?>" placeholder="Aadhar Number" type="text"
                                    autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input class="form-control input-sm" name="input_mphone1" placeholder="Phone Number"
                                    value="<?=$data[0]->mphone1 ?>" type="text" autocomplete="off"
                                    data-inputmask='"mask": "9999999999"' data-mask>
                            </div>
                        </div>
                    </div>
                    <div class="row well well-sm">
                        <?php
							$chk_age=$data[0]->estimate_dob;
							if($chk_age==1)
							{
								$checkbox_checked="checked";
								$age_input_2='style="display: none;"';
								$age_input_1='';
								$DateofBirth='';
							}else{
								$checkbox_checked="";
								$age_input_1='style="display: none;"';
								$age_input_2='';
								$DateofBirth=MysqlDate_to_str($data[0]->dob);
							}
						?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><input id="chk_age" name="chk_age" type="checkbox" <?=$checkbox_checked?>>
                                    Estimate Age</label>
                            </div>
                        </div>
                        <div id="age_input_1" <?=$age_input_1?>>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label> Age (in Year - Month) </label>
                                    <table>
                                        <tr>
                                            <td>Year :</td>
                                            <td><input class="form-control number input-sm" name="input_age_year"
                                                    id="input_age_year" placeholder="Year" type="text"
                                                    autocomplete="off" style=" width:100px;" value="<?=$data[0]->age?>">
                                            </td>
                                            <td style=" width:50px;"></td>
                                            <td>Month : </td>
                                            <td><input class="form-control number input-sm" name="input_age_month"
                                                    id="input_age_month" placeholder="Month" type="text"
                                                    autocomplete="off" style=" width:100px;"
                                                    value="<?=$data[0]->age_in_month?>"></td>
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
                                    <input class="form-control pull-right datepicker input-sm" name="datepicker_dob"
                                        id="datepicker_dob" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask
                                        value="<?=$DateofBirth ?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Relation</label>
                                <select class="form-control input-sm" name="cbo_relation" id="cbo_relation">
                                    <option value="W/o" <?=combo_checked("W/o",$data[0]->p_relative)?>>W/o
                                    </option>
                                    <option value="S/o" <?=combo_checked("S/o",$data[0]->p_relative)?>>S/o
                                    </option>
                                    <option value="D/o" <?=combo_checked("D/o",$data[0]->p_relative)?>>
                                        D/o</option>
                                    <option value="C/o" <?=combo_checked("C/o",$data[0]->p_relative)?>>C/o
                                    </option>
                                    <option value="M/o" <?=combo_checked("M/o",$data[0]->p_relative)?>>M/o
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Relative Name</label>
                                <input class="form-control input-sm" name="input_relative_name" placeholder="Full Name"
                                    value="<?=$data[0]->p_rname ?>" type="text" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Email</label>
                                <input class="form-control input-sm" name="input_email" value="<?=$data[0]->email1 ?>"
                                    placeholder="Email" type="text">
                            </div>
                        </div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Blood Group</label>
								<select class="form-control input-sm" name="input_blood_group" id="input_blood_group">
									<?php foreach($blood_group as $row) { ?>
										<option value="<?=$row->blood_group?>" <?=combo_checked($row->blood_group,$data[0]->blood_group)?>><?=$row->blood_group?></option>
									<?php } ?>
								</select>
							</div>
						</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Address</label>
                                <input class="form-control input-sm" name="input_address" placeholder="Address"
                                    value="<?=$data[0]->add1 ?>" type="text" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>City</label>
                                <input class="form-control input-sm" name="input_city" placeholder="City"
                                    value="<?=$data[0]->city ?>" type="text">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Pin/Zip Code </label>
                                <input class="form-control input-sm" name="input_zip" placeholder="Pin/Zip Code"
                                    type="text" value="<?=$data[0]->zip ?>" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>District</label>
                                <input class="form-control input-sm" name="input_district" placeholder="District"
                                    type="text" autocomplete="on" value="<?=$data[0]->district ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>State</label>
                                <input class="form-control input-sm" name="input_state" placeholder="State" type="text"
                                    autocomplete="on" value="<?=$data[0]->state ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" id="btn_update">Update Record</button>
                        </div>
                    </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <!-- ./row -->
</section>
<!-- /.content -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container"></div>
<div class="modal modal-wide fade" id="tallModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="testentryLabel">Test Name</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="testentry-bodyc" id="testentry-bodyc">

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script>
if (typeof window.notify !== 'function') {
    window.notify = function(type, title, message) {
        var container = document.getElementById('toast-container');
        if (!container) {
            return;
        }

        var toast = document.createElement('div');
        var variant = type === 'success' ? 'success' : 'danger';
        toast.className = 'toast align-items-center text-bg-' + variant + ' border-0';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML =
            '<div class="d-flex">'
            + '<div class="toast-body">'
            + '<strong class="me-2">' + (title || '') + '</strong>' + (message || '')
            + '</div>'
            + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
            + '</div>';

        container.appendChild(toast);

        if (window.bootstrap && bootstrap.Toast) {
            var bsToast = bootstrap.Toast.getOrCreateInstance(toast, { delay: 3000 });
            bsToast.show();
        } else {
            toast.classList.add('show');
            setTimeout(function() {
                toast.classList.remove('show');
                toast.remove();
            }, 3000);
        }
    };
}

function onchange_title() {
    var d = $('#cbo_title').val();

    if (d == "Mr." || d == "Master" || d == "Baby Boy" || d == "Mohd.") {
        $("#options_gender1").prop("checked", true);
    } else {
        $("#options_gender2").prop("checked", true);
    }
}

$(document).ready(function() {

    document.title = 'Pt.:<?=$data[0]->p_fname ?>/<?=$data[0]->id ?>';

    $('#btn_update').click(function() {
        $.post('<?= base_url('billing/patient/update') ?>', $('form.form1').serialize(), function(data) {
            var resp = data;
            if (typeof data === 'string') {
                try {
                    resp = JSON.parse(data);
                } catch (e) {
                    resp = { update: 0, error_text: 'Unexpected response.' };
                }
            }

            var isOk = String(resp.update) === '1';
            var title = 'Please Attention';
            var msg = isOk ? (resp.showcontent || 'Updated.') : (resp.error_text || 'Update failed.');

            if (typeof notify === 'function') {
                notify(isOk ? 'success' : 'error', title, msg);
            } else {
                alert(msg);
            }
        });
    })

    $("#chk_age").on("click", function() {
        if (this.checked) {
            $('#age_input_1').show();
            $('#age_input_2').hide();
        } else {
            $('#age_input_1').hide();
            $('#age_input_2').show();
        }
    });


});

$('#tallModal').on('shown.bs.modal', function(event) {
    $('.testentry-bodyc').html('');

    var height = $(window).height() - 50;
    $(this).find(".modal-body").css("max-height", height);

    var button = $(event.relatedTarget);
    // Button that triggered the modal
    var pid = $('#p_id').val();
    var opdid = button.data('opdid');
    var doc_type = opdid;
    var etype = button.data('etype');

    if (etype == '1') {

        $.post('<?= base_url('billing/patient/patient_file_upload') ?>/' + pid, {
            "pid": pid,
            "doc_type": doc_type
        }, function(data) {
            $('#testentry-bodyc').html(data);
        });
    }
    if (etype == '2') {

        $.post('<?= base_url('Opd/opd_file_list') ?>/' + opdid, {
            "opdid": opdid
        }, function(data) {
            $('#testentry-bodyc').html(data);
        });
    }

    if (etype == '3') {
        var repoid = button.data('repoid');
        $.post('<?= base_url('Opd/opd_file_upload') ?>/' + opdid, {
            "opdid": opdid
        }, function(data) {
            $('#testentry-bodyc').html(data);
        });
    }

});

$('#tallModal').on('hidden.bs.modal', function() {
    $('.testentry-bodyc').html('');
    $('#testentryLabel').html('');
    Webcam.reset();

});
</script>