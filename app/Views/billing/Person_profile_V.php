<div class="pagetitle">
    <h1>Profile</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[0]->id?>/0');">Home</a></li>
            <li class="breadcrumb-item">Patient</li>
            <li class="breadcrumb-item active">Profile</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="jsError"></div>
    <form role="form" class="form1">
        <?= csrf_field() ?>
        <?php
            $patientAbhaId = (string) ($data[0]->abha_id ?? $data[0]->abha_no ?? $data[0]->abha ?? $data[0]->abha_address ?? '');
        ?>
        <?php
            $user = auth()->user();
            $canEditOpd = is_object($user) && method_exists($user, 'can') ? $user->can('billing.opd.edit') : false;
            $canChargeEdit = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.edit') : false;
        ?>
        <input type="hidden" value="<?=$data[0]->id ?>" id="p_id" name="p_id" />
        <input type="hidden" id="ins_id" value="<?=$data[0]->insurance_id?>">
        <input type="hidden" id="ins_card_id" value="<?=$data[0]->insurance_card_id?>">

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                        <img src="<?=$profile_file_path?>" alt="Profile" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        <h2 class="mt-3 mb-1"><?=ucwords($data[0]->p_fname) ?></h2>
                        <h3 class="mb-2"><?=$data[0]->p_code; ?></h3>
                        <div class="d-flex gap-2 flex-wrap justify-content-center">
                            <button type="button" class="btn btn-danger btn-sm" onclick="load_form('<?= base_url('billing/patient/person_record') ?>/<?=$data[0]->id?>/1');">Profile Edit</button>
                            <button type="button" class="btn btn-success btn-sm" onclick="load_form('<?= base_url('billing/patient/show_profile_image') ?>/<?=$data[0]->id?>/1');">Edit Picture</button>
                            <button type="button" class="btn btn-info btn-sm" onclick="load_form('<?= base_url('billing/patient/show_profile_opd') ?>/<?=$data[0]->id?>/1');">OPD Scan</button>
                        </div>
                        <div class="mt-3 w-100">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btn_opd" accesskey="A"><u>A</u>ppointment For OPD</button>
                                <?php if ($canChargeEdit) : ?>
                                    <button type="button" class="btn btn-danger btn-sm" id="btn_lab">Add Charge</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-warning btn-sm" id="btn_ipd">IPD</button>
                            </div>
                        </div>
                        <div class="mt-3 w-100">
                            <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2">
                                <div>
                                    <div class="small text-muted">OPD Visits</div>
                                    <div class="fw-bold"><?= count($opd_List) ?></div>
                                </div>
                                <div>
                                    <div class="small text-muted">Invoices</div>
                                    <div class="fw-bold"><?= count($invoice_list) ?></div>
                                </div>
                                <div>
                                    <div class="small text-muted">Insurance</div>
                                    <div class="fw-bold"><?= ($data[0]->insurance_id ?? 0) > 0 ? 'Yes' : 'No' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body pt-3">
                        <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-overview" type="button" role="tab">Overview</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-insurance" type="button" role="tab">Insurance</button>
                            </li>
                            <?php if(count($opd_List)>0) { ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-opd" type="button" role="tab">OPD</button>
                            </li>
                            <?php } ?>
                            <?php if(count($invoice_list)>0) { ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-invoices" type="button" role="tab">Invoices</button>
                            </li>
                            <?php } ?>
                        </ul>

                        <div class="tab-content pt-2">
                            <div class="tab-pane fade show active profile-overview" id="profile-overview" role="tabpanel">
                                <h5 class="card-title">Profile Details</h5>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Full Name</div>
                                    <div class="col-lg-9 col-md-8"><?=ucwords($data[0]->p_fname) ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Gender / Age</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->xgender?> / <?= esc(get_age_1($data[0]->dob ?? null, $data[0]->age ?? '', $data[0]->age_in_month ?? '', $data[0]->estimate_dob ?? '')) ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Relation</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->p_relative?> <?=ucwords($data[0]->p_rname)?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Aadhar No.</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->udai?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">ABHA ID</div>
                                    <div class="col-lg-9 col-md-8"><?= esc($patientAbhaId) ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Phone</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->mphone1?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Email</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->email1?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Blood Group</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->blood_group?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Address</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->add1?> <?=$data[0]->city ?> <?=$data[0]->district ?> <?=$data[0]->state ?> <?=$data[0]->zip ?></div>
                                </div>

                                <h5 class="card-title">Quick Update</h5>
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-6">
                                        <label class="form-label">Aadhar No.</label>
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" type="text" name="input_Aadhar" id="input_Aadhar" value="<?=$data[0]->udai ?>">
                                            <button type="button" id="btn_update_aadhar" class="btn btn-info">Update</button>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">ABHA ID</label>
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" type="text" name="input_abha_id" id="input_abha_id" value="<?= esc($patientAbhaId) ?>" maxlength="14">
                                            <button type="button" id="btn_update_abha" class="btn btn-info">Update</button>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <button type="button" class="btn btn-warning btn-sm mt-4" onclick="load_form('<?= base_url('billing/patient/show_cards') ?>/<?=$data[0]->id?>/1');">Insurance Update</button>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade profile-edit pt-3" id="profile-insurance" role="tabpanel">
                                <h5 class="card-title">Insurance Details</h5>
                                <?php if($data[0]->insurance_id>0) {  ?>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Company</div>
                                    <div class="col-lg-9 col-md-8"><?=$data_insurance_card[0]->ins_company_name?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Card Holder</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->card_holder_name?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Relation</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->relation_patient_cardholder?></div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-3 col-md-4 label">Insurance ID</div>
                                    <div class="col-lg-9 col-md-8"><?=$data[0]->insurance_no?></div>
                                </div>

                                <div class="mt-3">
                                    <?php if(count($case_master_opd)>0){ ?>
                                        <div class="alert alert-light border">
                                            <div><strong>Case Code:</strong> <?=$case_master_opd[0]->case_id_code?></div>
                                            <div><strong>Visit Date Start:</strong> <?=$case_master_opd[0]->str_date_registration?></div>
                                            <div><strong>Claim/Case No.:</strong> <?=$case_master_opd[0]->insurance_no_1?></div>
                                            <div><strong>Other IDs:</strong> <?=$case_master_opd[0]->insurance_no_2?></div>
                                            <button type="button" class="btn btn-warning btn-sm mt-2" id="btn_case_opd">Update Case Information</button>
                                        </div>
                                    <?php }else if(count($case_master_ipd)>0){ ?>
                                        <input type="hidden" id="ins_org_id" value="<?=$case_master_ipd[0]->id?>">
                                        <div class="alert alert-light border">
                                            <div><strong>Case Code:</strong> <?=$case_master_ipd[0]->case_id_code?></div>
                                            <div><strong>Org. Date Start:</strong> <?=$case_master_ipd[0]->str_date_registration?></div>
                                            <div><strong>Claim/Case No.:</strong> <?=$case_master_ipd[0]->insurance_no_1?></div>
                                            <div><strong>Other IDs:</strong> <?=$case_master_ipd[0]->insurance_no_2?></div>
                                            <div><strong>IPD No:</strong> <?=$case_master_ipd[0]->ipd_code?></div>
                                            <div><strong>IPD Admit Date:</strong> <?=$case_master_ipd[0]->str_date_registration?></div>
                                            <button type="button" class="btn btn-warning btn-sm mt-2" id="btn_case_ipd_open">Update Case Information</button>
                                        </div>
                                    <?php }else{ ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-info btn-sm" id="btn_case_ipd">Create Case for Credit IPD Bill</button>
                                            <button type="button" class="btn btn-success btn-sm" id="btn_case_opd">Create Case for Credit OPD Bill</button>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <?php if($data_insurance_card[0]->opd_allowed==1) {  ?>
                                    <button type="button" class="btn btn-success btn-sm" id="btn_inc_opd">OPD Insurance Rates / CASH</button>
                                    <?php }  ?>
                                    <?php if($data_insurance_card[0]->charge_cash==1 && $canChargeEdit) {  ?>
                                    <button type="button" class="btn btn-success btn-sm" id="btn_inc_lab">Charge with Ins. Rate / CASH</button>
                                    <?php } ?>
                                </div>
                                <?php } else { ?>
                                    <div class="alert alert-info mb-0">No insurance details available.</div>
                                <?php } ?>
                            </div>

                            <?php if(count($opd_List)>0) { ?>
                            <div class="tab-pane fade pt-3" id="profile-opd" role="tabpanel">
                                <h5 class="card-title">OPD Registration</h5>
                                <?php
                                    foreach($opd_List as $row)
                                    {
                                        echo '<div class="mb-3">';
                                        echo '<strong>'.$row->opd_code.'</strong>'; 
                                        echo '<div class="text-muted">';
                                        echo '<span class="text-success">Dr.'.$row->doc_name.'</span> '; 
                                        echo '<span class="text-info">D:'.$row->str_apointment_date.'</span> '; 
                                        echo '<span class="text-warning">P:'.$row->p_fname.'</span><br/>';

                                        if($canEditOpd || $row->new_opd==1){
                                            echo '<a href="javascript:load_form(\'' . base_url('Opd/invoice') . '/' . $row->opd_id . '\');" class="btn btn-warning btn-sm me-2">Edit OPD</a>';
                                            echo '<a href="' . base_url('Opd_print/opd_day_care') . '/' . $row->opd_id . '/0" target="_blank" class="btn btn-success btn-sm">Print Day Care</a>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                ?>
                            </div>
                            <?php } ?>

                            <?php if(count($invoice_list)>0) { ?>
                            <div class="tab-pane fade pt-3" id="profile-invoices" role="tabpanel">
                                <h5 class="card-title">Charges Invoice</h5>
                                <?php
                                    foreach($invoice_list as $row)
                                    {
                                        echo '<div class="mb-3">';
                                        echo '<strong>'.$row->invoice_code.'</strong>';
                                        echo ' <span class="text-info">D:'.$row->str_inv_date.'</span>';
                                        echo ' <span class="text-warning">N:'.$row->inv_name.'</span>';
                                        echo '<div class="text-muted">';
                                        echo '<span class="text-success">'.$row->Item_List.'</span><br/>';
                                        if($row->invoice_status==0)
                                        {
                                            if ($canChargeEdit) {
                                                echo '<a href="javascript:load_form(\'' . base_url('billing/charges/edit') . '/' . $row->id . '\');" class="btn btn-warning btn-sm me-2">Edit Charges Invoice</a>';
                                                echo '<a href="javascript:delete_invoice(\''.$row->id.'\');" class="btn btn-danger btn-sm">Delete : Un-confirm Invoice</a>';
                                            }
                                        }else{
                                            echo '<a href="javascript:load_form(\'' . base_url('billing/charges/show') . '/' . $row->id . '\');" class="btn btn-info btn-sm">Show Charges Invoice</a>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
$(document).ready(function() {

    document.title = 'Pt.:<?=$data[0]->p_fname ?>/<?=$data[0]->id ?>';

    $('#btn_opd').click(function() {
        var p_id = $('#p_id').val();
        load_form('<?= base_url('Opd/addopd') ?>/' + p_id);
    });

    $('#btn_update_aadhar').click(function() {
        var p_id = $('#p_id').val();
        var udai = $('#input_Aadhar').val();
        var csrf_name = '<?= csrf_token() ?>';
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        if (confirm("Are you sure Update Aadhar No.")) {
            $.post('<?= base_url('billing/patient/update_aadhar') ?>', {
                "p_id": p_id,
                "udai": udai,
                [csrf_name]: csrf_value
            }, function(data) {
                load_form('<?= base_url('billing/patient/person_record') ?>/' + p_id);
            });
        }
    });

    $('#btn_update_abha').click(function() {
        var p_id = $('#p_id').val();
        var abha_id = ($('#input_abha_id').val() || '').trim();
        var csrf_name = '<?= csrf_token() ?>';
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        if (abha_id !== '' && !/^\d{14}$/.test(abha_id)) {
            alert('ABHA ID must be a 14-digit number.');
            return;
        }

        if (confirm("Are you sure Update ABHA ID.")) {
            $.post('<?= base_url('billing/patient/update_abha') ?>', {
                "p_id": p_id,
                "abha_id": abha_id,
                [csrf_name]: csrf_value
            }, function(data) {
                load_form('<?= base_url('billing/patient/person_record') ?>/' + p_id);
            });
        }
    });

    $('#btn_inc_opd').click(function() {
        var p_id = $('#p_id').val();
        load_form('<?= base_url('Opdcase/addopd') ?>/' + p_id);
    });

    $('#btn_ipd').click(function() {
        var p_id = $('#p_id').val();
        load_form('<?= base_url('IpdNew/addipd') ?>/' + p_id);
    });

    $('#btn_lab').click(function() {
        var p_id = $('#p_id').val();
        load_form('<?= base_url('billing/charges/add') ?>/' + p_id);
    });

    $('#btn_inc_lab').click(function() {
        var p_id = $('#p_id').val();
        var ins_card_id = $('#ins_card_id').val();
        load_form('<?= base_url('billing/charges/add') ?>/' + p_id + '/' + ins_card_id);
    });

    $('#btn_case_opd').click(function() {
        var p_id = $('#p_id').val();
        var ins_id = $('#ins_id').val();
        var ins_card_id = $('#ins_card_id').val();

        load_form('<?= base_url('billing/case/newcase') ?>/' + p_id + '/' + ins_id + '/0');
    });

    $('#btn_case_ipd_open').click(function() {
        var ins_org_id = $('#ins_org_id').val();
        load_form('<?= base_url('billing/case/open_case') ?>/' + ins_org_id + '/1');
    });

    $('#btn_case_ipd').click(function() {
        var p_id = $('#p_id').val();
        var ins_id = $('#ins_id').val();
        var ins_card_id = $('#ins_card_id').val();
        load_form('<?= base_url('billing/case/newcase') ?>/' + p_id + '/' + ins_id + '/1');
    });

    $('#btn_card').click(function() {
        var p_id = $('#p_id').val();
        load_form('<?= base_url('billing/patient/show_cards') ?>/' + p_id);
    });

    $('#btn_update_card').click(function() {
        var p_id = $('#p_id').val();
        var ins_id = $('#ins_id').val();
        load_form('<?= base_url('billing/patient/show_cards') ?>/' + p_id + '/' + ins_id);
    });
});

function delete_invoice(inv_id) {
    var pid = $('#p_id').val();
    var csrf_name = '<?= csrf_token() ?>';
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

    if (confirm("Are you sure delete this invoice")) {
        $.post('<?= base_url('billing/charges/delete') ?>', {
            "inv_id": inv_id,
            [csrf_name]: csrf_value
        }, function(data) {
            load_form('<?= base_url('billing/patient/person_record') ?>/' + pid);
        });
    }

}
</script>