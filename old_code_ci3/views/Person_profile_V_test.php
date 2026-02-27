<?php
/**
 * Person Profile View - Test Version
 * Enhanced version with improvements for testing
 * Date: <?= date('Y-m-d H:i:s') ?>
 */

// Data validation and sanitization helper
function safe_output($data, $default = '') {
    return !empty($data) ? htmlspecialchars($data, ENT_QUOTES, 'UTF-8') : $default;
}
?>

<section class="content-header">
    <div class="row">
        <div class="col-xs-12">
            <h1>
                <?= safe_output(ucwords($data[0]->p_fname ?? ''), 'Unknown Patient'); ?>
                <small>
                    <a href="javascript:load_form('/Patient/person_record/<?= safe_output($data[0]->id ?? ''); ?>/0');" 
                       class="text-muted">
                        <?= safe_output($data[0]->p_code ?? '', 'No Code'); ?>
                    </a>
                </small>
            </h1>
            <ol class="breadcrumb">
                <li><a href="#"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                <li><a href="#">Patients</a></li>
                <li class="active">Profile</li>
            </ol>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <!-- Loading overlay -->
    <div id="loading-overlay" class="overlay" style="display: none;">
        <i class="fa fa-refresh fa-spin"></i>
    </div>
    
    <!-- Error/Success messages -->
    <div id="alert-container"></div>
    
    <?php echo form_open('', array('role'=>'form','class'=>'form1', 'id'=>'person-profile-form')); ?>
    
    <div class="row">
        <!-- Main Profile Section -->
        <div class="col-md-8 col-sm-12">
            <!-- Person Profile Box -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <div class="row">
                        <div class="col-md-6 col-sm-6">
                            <h3 class="box-title">
                                <i class="fa fa-user"></i> Person Profile
                            </h3>
                        </div>
                        <div class="col-md-6 col-sm-6 text-right">
                            <div class="btn-group" role="group">
                                <button type="button" 
                                        class="btn btn-danger btn-sm" 
                                        onclick="editProfile(<?= safe_output($data[0]->id ?? ''); ?>)">
                                    <i class="fa fa-edit"></i> Edit Profile
                                </button>
                                <button type="button" 
                                        class="btn btn-success btn-sm" 
                                        onclick="editProfileImage(<?= safe_output($data[0]->id ?? ''); ?>)">
                                    <i class="fa fa-camera"></i> Edit Photo
                                </button>
                                <button type="button" 
                                        class="btn btn-info btn-sm" 
                                        onclick="opdScan(<?= safe_output($data[0]->id ?? ''); ?>)">
                                    <i class="fa fa-qrcode"></i> OPD Scan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="box-body">
                    <input type="hidden" value="<?= safe_output($data[0]->id ?? ''); ?>" id="p_id" name="p_id" />
                    
                    <!-- Patient Basic Information -->
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="info-box bg-blue">
                                <span class="info-box-icon"><i class="fa fa-user"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Full Name</span>
                                    <span class="info-box-number">
                                        <?= safe_output(ucwords($data[0]->p_fname ?? ''), 'Not provided'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box bg-green">
                                <span class="info-box-icon"><i class="fa fa-venus-mars"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Gender</span>
                                    <span class="info-box-number">
                                        <?= safe_output($data[0]->xgender ?? '', 'Not specified'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6">
                            <div class="info-box bg-yellow">
                                <span class="info-box-icon"><i class="fa fa-birthday-cake"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Age</span>
                                    <span class="info-box-number">
                                        <?= safe_output($data[0]->str_age ?? '', 'Unknown'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <p class="text-primary">
                                <i class="fa fa-phone"></i> Phone Number: 
                                <span class="text-success">
                                    <?= safe_output($data[0]->mphone1 ?? '', 'Not provided'); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <p class="text-primary">
                                <i class="fa fa-envelope"></i> Email: 
                                <span class="text-success">
                                    <?= safe_output($data[0]->email1 ?? '', 'Not provided'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="row">
                        <div class="col-md-9 col-sm-12">
                            <p class="text-primary">
                                <i class="fa fa-users"></i> Relation: 
                                <span class="text-success">
                                    <?= safe_output($data[0]->p_relative ?? '', 'Not specified'); ?>
                                </span>
                                <?php if (!empty($data[0]->p_rname)): ?>
                                    | Relative Name: 
                                    <span class="text-success">
                                        <?= safe_output(ucwords($data[0]->p_rname)); ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-3 col-sm-12">
                            <p class="text-primary">
                                <i class="fa fa-tint"></i> Blood Group: 
                                <span class="text-success">
                                    <?= safe_output($data[0]->blood_group ?? '', 'Not specified'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Address -->
                    <div class="row">
                        <div class="col-md-12">
                            <p class="text-primary">
                                <i class="fa fa-map-marker"></i> Address: 
                                <span class="text-success">
                                    <?php
                                    $address_parts = array_filter([
                                        $data[0]->add1 ?? '',
                                        $data[0]->city ?? '',
                                        $data[0]->district ?? '',
                                        $data[0]->state ?? '',
                                        $data[0]->zip ?? ''
                                    ]);
                                    echo !empty($address_parts) ? safe_output(implode(', ', $address_parts)) : 'Not provided';
                                    ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Photo and Aadhar Update -->
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="text-center">
                                <img src="<?= safe_output($profile_file_path ?? '/assets/no_image.jpg'); ?>" 
                                     class="img-responsive img-thumbnail" 
                                     style="max-width: 200px; max-height: 200px;"
                                     alt="Patient Photo"
                                     onerror="this.src='/assets/no_image.jpg';" />
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label for="input_Aadhar">
                                    <i class="fa fa-id-card"></i> Aadhar Number
                                </label>
                                <div class="input-group">
                                    <input class="form-control" 
                                           type="text" 
                                           name="input_Aadhar" 
                                           id="input_Aadhar"
                                           value="<?= safe_output($data[0]->udai ?? ''); ?>"
                                           placeholder="Enter Aadhar Number"
                                           maxlength="12"
                                           pattern="[0-9]{12}">
                                    <span class="input-group-btn">
                                        <button type="button" 
                                                id="btn_update_aadhar" 
                                                class="btn btn-info">
                                            <i class="fa fa-save"></i> Update
                                        </button>
                                    </span>
                                </div>
                                <small class="help-block">12-digit Aadhar number</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Insurance Details Box -->
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-shield"></i> Insurance Details
                        <small>
                            <button type="button" 
                                    class="btn btn-warning btn-xs" 
                                    onclick="updateInsurance(<?= safe_output($data[0]->id ?? ''); ?>)">
                                <i class="fa fa-edit"></i> Update Insurance
                            </button>
                        </small>
                    </h3>
                </div>
                
                <input type="hidden" id="ins_id" value="<?= safe_output($data[0]->insurance_id ?? ''); ?>">
                <input type="hidden" id="ins_card_id" value="<?= safe_output($data[0]->insurance_card_id ?? ''); ?>">
                
                <div class="box-body">
                    <?php if (isset($data[0]->insurance_id) && $data[0]->insurance_id > 0): ?>
                        <!-- Insurance Information Display -->
                        <div class="row">
                            <div class="col-md-8 col-sm-12">
                                <p class="text-primary">
                                    <i class="fa fa-building"></i> Company Name: 
                                    <span class="text-success">
                                        <?= safe_output($data_insurance_card[0]->ins_company_name ?? '', 'Not specified'); ?>
                                    </span>
                                </p>
                                <p class="text-primary">
                                    <i class="fa fa-user"></i> Card Holder Name: 
                                    <span class="text-success">
                                        <?= safe_output($data[0]->card_holder_name ?? '', 'Not specified'); ?>
                                    </span>
                                </p>
                                <p class="text-primary">
                                    <i class="fa fa-users"></i> Relation: 
                                    <span class="text-success">
                                        <?= safe_output($data[0]->relation_patient_cardholder ?? '', 'Not specified'); ?>
                                    </span>
                                </p>
                                <p class="text-primary">
                                    <i class="fa fa-id-badge"></i> Insurance ID: 
                                    <span class="text-success">
                                        <?= safe_output($data[0]->insurance_no ?? '', 'Not specified'); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="col-md-4 col-sm-12">
                                <!-- Case Management Section -->
                                <?php if (isset($case_master_opd) && count($case_master_opd) > 0): ?>
                                    <div class="alert alert-info">
                                        <h5><i class="fa fa-info-circle"></i> OPD Case Active</h5>
                                        <p><strong>Case Code:</strong> <?= safe_output($case_master_opd[0]->case_id_code ?? ''); ?></p>
                                        <p><strong>Visit Date:</strong> <?= safe_output($case_master_opd[0]->str_date_registration ?? ''); ?></p>
                                        <button type="button" class="btn btn-warning btn-sm" id="btn_case_opd">
                                            <i class="fa fa-edit"></i> Update Case
                                        </button>
                                    </div>
                                <?php elseif (isset($case_master_ipd) && count($case_master_ipd) > 0): ?>
                                    <input type="hidden" id="ins_org_id" value="<?= safe_output($case_master_ipd[0]->id ?? ''); ?>">
                                    <div class="alert alert-warning">
                                        <h5><i class="fa fa-bed"></i> IPD Case Active</h5>
                                        <p><strong>Case Code:</strong> <?= safe_output($case_master_ipd[0]->case_id_code ?? ''); ?></p>
                                        <p><strong>IPD No:</strong> <?= safe_output($case_master_ipd[0]->ipd_code ?? ''); ?></p>
                                        <button type="button" class="btn btn-warning btn-sm" id="btn_case_ipd_open">
                                            <i class="fa fa-edit"></i> Update Case
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <h5><i class="fa fa-plus"></i> Create New Case</h5>
                                        <button type="button" class="btn btn-info btn-sm btn-block" id="btn_case_ipd">
                                            <i class="fa fa-bed"></i> Create IPD Case
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm btn-block" id="btn_case_opd">
                                            <i class="fa fa-user-md"></i> Create OPD Case
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Insurance Options -->
                        <div class="row">
                            <div class="col-md-12">
                                <?php if (isset($data_insurance_card[0]->opd_allowed) && $data_insurance_card[0]->opd_allowed == 1): ?>
                                    <button type="button" class="btn btn-success btn-sm" id="btn_inc_opd">
                                        <i class="fa fa-money"></i> OPD Insurance Rates / CASH
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (isset($data_insurance_card[0]->charge_cash) && $data_insurance_card[0]->charge_cash == 1): ?>
                                    <button type="button" class="btn btn-success btn-sm" id="btn_inc_lab">
                                        <i class="fa fa-credit-card"></i> Charge with Ins. Rate / CASH
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle fa-2x"></i>
                            <h4>No Insurance Information</h4>
                            <p>No insurance details found for this patient.</p>
                            <button type="button" 
                                    class="btn btn-primary" 
                                    onclick="updateInsurance(<?= safe_output($data[0]->id ?? ''); ?>)">
                                <i class="fa fa-plus"></i> Add Insurance
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-group btn-group-justified" role="group">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary" id="btn_opd" accesskey="A">
                                <i class="fa fa-calendar"></i> <u>A</u>ppointment OPD
                            </button>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-danger" id="btn_lab">
                                <i class="fa fa-flask"></i> Add Charge
                            </button>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-warning" id="btn_ipd">
                                <i class="fa fa-bed"></i> IPD
                            </button>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-success" id="btn_doc">
                                <i class="fa fa-file"></i> Documents
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar - OPD and Invoice Lists -->
        <div class="col-md-4 col-sm-12">
            <!-- OPD Registration List -->
            <?php if (isset($opd_List) && count($opd_List) > 0): ?>
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-user-md"></i> OPD Registration
                            <span class="badge bg-green"><?= count($opd_List); ?></span>
                        </h3>
                    </div>
                    <div class="box-body" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($opd_List as $row): ?>
                            <div class="callout callout-success">
                                <h5><?= safe_output($row->opd_code ?? ''); ?></h5>
                                <p class="text-muted">
                                    <i class="fa fa-user-md"></i> Dr. <?= safe_output($row->doc_name ?? ''); ?><br/>
                                    <i class="fa fa-calendar"></i> <?= safe_output($row->str_apointment_date ?? ''); ?><br/>
                                    <i class="fa fa-user"></i> <?= safe_output($row->p_fname ?? ''); ?>
                                </p>
                                <?php if ($this->ion_auth->in_group('OPDEdit') || ($row->new_opd ?? 0) == 1): ?>
                                    <div class="btn-group btn-group-xs">
                                        <a href="javascript:load_form('/Opd/invoice/<?= safe_output($row->opd_id ?? ''); ?>/0');" 
                                           class="btn btn-warning">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <a href="/Opd_print/opd_day_care/<?= safe_output($row->opd_id ?? ''); ?>/0" 
                                           target="_blank" 
                                           class="btn btn-success">
                                            <i class="fa fa-print"></i> Print
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Charges Invoice List -->
            <?php if (isset($invoice_list) && count($invoice_list) > 0): ?>
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-file-text"></i> Charges Invoice
                            <span class="badge bg-yellow"><?= count($invoice_list); ?></span>
                        </h3>
                    </div>
                    <div class="box-body" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($invoice_list as $row): ?>
                            <div class="callout callout-warning">
                                <h5><?= safe_output($row->invoice_code ?? ''); ?></h5>
                                <p class="text-muted">
                                    <i class="fa fa-calendar"></i> <?= safe_output($row->str_inv_date ?? ''); ?><br/>
                                    <i class="fa fa-user"></i> <?= safe_output($row->inv_name ?? ''); ?><br/>
                                    <i class="fa fa-list"></i> <?= safe_output($row->Item_List ?? ''); ?>
                                </p>
                                
                                <div class="btn-group btn-group-xs">
                                    <?php if (($row->invoice_status ?? 1) == 0): ?>
                                        <a href="javascript:load_form('/PathLab/IPD_Invoice_Edit/<?= safe_output($row->id ?? ''); ?>');" 
                                           class="btn btn-warning">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <a href="javascript:deleteInvoice('<?= safe_output($row->id ?? ''); ?>');" 
                                           class="btn btn-danger">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                    <?php else: ?>
                                        <a href="javascript:load_form('/PathLab/showinvoice/<?= safe_output($row->id ?? ''); ?>');" 
                                           class="btn btn-info">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php echo form_close(); ?>
</section>

<!-- Custom CSS for this page -->
<style>
#loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    font-size: 24px;
}

.info-box {
    margin-bottom: 15px;
}

.callout {
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .btn-group-justified > .btn-group {
        display: block;
        width: 100%;
        margin-bottom: 5px;
    }
    
    .btn-group-justified > .btn-group > .btn {
        width: 100%;
    }
}
</style>

<!-- Enhanced JavaScript -->
<script>
$(document).ready(function() {
    // Set page title
    document.title = 'Patient: <?= safe_output($data[0]->p_fname ?? 'Unknown'); ?> (<?= safe_output($data[0]->id ?? ''); ?>)';
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Button click handlers with improved error handling
    $('#btn_opd').click(function() {
        const p_id = $('#p_id').val();
        if (p_id) {
            showLoading();
            load_form('/Opd/addopd/' + p_id);
        } else {
            showAlert('Error', 'Patient ID not found', 'error');
        }
    });
    
    $('#btn_update_aadhar').click(function() {
        const p_id = $('#p_id').val();
        const udai = $('#input_Aadhar').val().trim();
        
        // Validate Aadhar number
        if (!udai) {
            showAlert('Validation Error', 'Please enter Aadhar number', 'warning');
            return;
        }
        
        if (!/^\d{12}$/.test(udai)) {
            showAlert('Validation Error', 'Aadhar number must be 12 digits', 'warning');
            return;
        }
        
        const csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
        
        if (confirm("Are you sure you want to update the Aadhar number?")) {
            showLoading();
            $.post('/index.php/Patient/update_aadhar', {
                "p_id": p_id,
                "udai": udai,
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            })
            .done(function(data) {
                hideLoading();
                showAlert('Success', 'Aadhar number updated successfully', 'success');
                load_form('/Patient/person_record/' + p_id);
            })
            .fail(function() {
                hideLoading();
                showAlert('Error', 'Failed to update Aadhar number', 'error');
            });
        }
    });
    
    // Other button handlers
    $('#btn_inc_opd').click(function() {
        const p_id = $('#p_id').val();
        if (p_id) load_form('/Opdcase/addopd/' + p_id);
    });
    
    $('#btn_ipd').click(function() {
        const p_id = $('#p_id').val();
        if (p_id) load_form('/IpdNew/addipd/' + p_id);
    });
    
    $('#btn_lab').click(function() {
        const p_id = $('#p_id').val();
        if (p_id) load_form('/PathLab/addPathTest/' + p_id);
    });
    
    $('#btn_doc').click(function() {
        const p_id = $('#p_id').val();
        if (p_id) load_form('/Document_Patient/p_doc_record/' + p_id);
    });
    
    $('#btn_inc_lab').click(function() {
        const p_id = $('#p_id').val();
        const ins_card_id = $('#ins_card_id').val();
        if (p_id) load_form('/PathLab/addPathTest/' + p_id + '/' + ins_card_id);
    });
    
    $('#btn_case_opd').click(function() {
        const p_id = $('#p_id').val();
        const ins_id = $('#ins_id').val();
        const ins_card_id = $('#ins_card_id').val();
        if (p_id) load_form('/Ocasemaster/newcase/' + p_id + '/' + ins_id + '/0');
    });
    
    $('#btn_case_ipd_open').click(function() {
        const ins_org_id = $('#ins_org_id').val();
        if (ins_org_id) load_form('/Ocasemaster/open_case/' + ins_org_id + '/1');
    });
    
    $('#btn_case_ipd').click(function() {
        const p_id = $('#p_id').val();
        const ins_id = $('#ins_id').val();
        const ins_card_id = $('#ins_card_id').val();
        if (p_id) load_form('/Ocasemaster/newcase/' + p_id + '/' + ins_id + '/1');
    });
});

// Helper functions
function editProfile(patientId) {
    if (patientId) {
        load_form('/Patient/person_record/' + patientId + '/1');
    }
}

function editProfileImage(patientId) {
    if (patientId) {
        load_form('/Patient/show_profile_image/' + patientId + '/1');
    }
}

function opdScan(patientId) {
    if (patientId) {
        load_form('/Patient/show_profile_opd/' + patientId + '/1');
    }
}

function updateInsurance(patientId) {
    if (patientId) {
        load_form('/Patient/show_cards/' + patientId + '/1');
    }
}

function deleteInvoice(invId) {
    if (!invId) return;
    
    const pid = $('#p_id').val();
    const csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
    
    if (confirm("Are you sure you want to delete this invoice? This action cannot be undone.")) {
        showLoading();
        $.post('/index.php/PathLab/deleteinvoice', {
            "inv_id": invId,
            "<?=$this->security->get_csrf_token_name()?>": csrf_value
        })
        .done(function(data) {
            hideLoading();
            showAlert('Success', 'Invoice deleted successfully', 'success');
            load_form('/Patient/person_record/' + pid);
        })
        .fail(function() {
            hideLoading();
            showAlert('Error', 'Failed to delete invoice', 'error');
        });
    }
}

function showLoading() {
    $('#loading-overlay').show();
}

function hideLoading() {
    $('#loading-overlay').hide();
}

function showAlert(title, message, type) {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4><i class="icon fa fa-${type === 'success' ? 'check' : type === 'error' ? 'ban' : 'info'}"></i> ${title}</h4>
            ${message}
        </div>
    `;
    
    $('#alert-container').html(alertHtml);
    
    // Auto-hide success messages
    if (type === 'success') {
        setTimeout(function() {
            $('#alert-container .alert').fadeOut();
        }, 3000);
    }
}
</script>