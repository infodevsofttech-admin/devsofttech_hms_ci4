<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$admissionDoctors = $admission_doctors ?? [];
$availableDoctors = $available_doctors ?? [];
$selectedDoctorIds = $selected_doctor_ids ?? [];
$departments = $departments ?? [];
$referMaster = $refer_master ?? [];
$registerDateInput = $register_date_input ?? date('Y-m-d');
$regTimeInput = $reg_time_input ?? date('H:i');
$age = '';
if ($person) {
    $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
}

$departmentName = 'Not set';
foreach ($departments as $department) {
    if ((int) ($department->iId ?? 0) === (int) ($ipd->dept_id ?? 0)) {
        $departmentName = (string) ($department->vName ?? 'Not set');
        break;
    }
}

$referByName = 'Not set';
foreach ($referMaster as $refer) {
    if ((int) ($refer->id ?? 0) === (int) ($ipd->refer_by ?? 0)) {
        $referByName = trim((string) (($refer->title ?? '') . ' ' . ($refer->f_name ?? '')));
        if ($referByName === '') {
            $referByName = 'Not set';
        }
        break;
    }
}

$doctorSummary = [];
foreach ($admissionDoctors as $doctor) {
    $doctorName = trim((string) ($doctor->doctor_name ?? ''));
    if ($doctorName !== '') {
        $doctorSummary[] = $doctorName;
    }
}

$admissionMode = (int) ($ipd->case_type ?? 0) === 1 ? 'MLC' : 'NON MLC';
$contactPersonName = (string) ($ipd->contact_person_Name ?? '');
$contactRelation   = (string) ($ipd->relation ?? '');
$contactMobile1    = (string) ($ipd->P_mobile1 ?? '');
$contactMobile2    = (string) ($ipd->P_mobile2 ?? '');
$problemText       = (string) ($ipd->problem ?? '');
$remarkText        = (string) ($ipd->remark ?? '');
$canEditAdmission = (bool) ($can_edit_admission ?? false);
$formId = 'ipd-admission-form-' . (int) ($ipd->id ?? 0);
$alertId = 'ipd-admission-alert-' . (int) ($ipd->id ?? 0);
?>
<div class="row g-3" id="ipd-admission-wrap-<?= (int) ($ipd->id ?? 0) ?>">
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Patient</h5>
                <p class="mb-1"><strong>Name :</strong> <?= esc($person->p_fname ?? '') ?> {<?= esc($person->p_rname ?? '') ?>}</p>
                <p class="mb-1"><strong>UHID :</strong> <?= esc($person->p_code ?? '') ?></p>
                <p class="mb-1"><strong>Gender :</strong> <?= esc($person->xgender ?? '') ?></p>
                <p class="mb-1"><strong>Age :</strong> <?= esc($age) ?></p>
                <p class="mb-0"><strong>Phone :</strong> <?= esc($person->mphone1 ?? '') ?></p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Admission</h5>
                <p class="mb-1"><strong>IPD Code :</strong> <?= esc($ipd->ipd_code ?? '') ?></p>
                <p class="mb-1"><strong>Admit Date :</strong> <?= esc($ipd->str_register_date ?? '') ?></p>
                <p class="mb-1"><strong>Admit Time :</strong> <?= esc($regTimeInput) ?></p>
                <p class="mb-1"><strong>Discharge Date :</strong> <?= esc($ipd->str_discharge_date ?? '') ?></p>
                <p class="mb-1"><strong>No. of Days :</strong> <?= esc($ipd->no_days ?? '') ?></p>
                <p class="mb-0"><strong>Insurance :</strong> <?= esc($ipd->ins_company_name ?? 'Direct') ?></p>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-12">
        <div class="card h-100 border-primary-subtle">
            <div class="card-body">
                <h5 class="card-title">Editable Details</h5>
                <p class="mb-1"><strong>Mode :</strong> <?= esc($admissionMode) ?></p>
                <p class="mb-1"><strong>Department :</strong> <?= esc($departmentName) ?></p>
                <p class="mb-1"><strong>Refer By :</strong> <?= esc($referByName) ?></p>
                <?php if ($contactPersonName !== '') : ?>
                <p class="mb-1"><strong>Contact Person :</strong> <?= esc($contactPersonName) ?><?= $contactRelation !== '' ? ' (' . esc($contactRelation) . ')' : '' ?></p>
                <?php if ($contactMobile1 !== '') : ?><p class="mb-1"><strong>Contact Mobile :</strong> <?= esc($contactMobile1) ?><?= $contactMobile2 !== '' ? ' / ' . esc($contactMobile2) : '' ?></p><?php endif; ?>
                <?php endif; ?>
                <?php if ($problemText !== '') : ?>
                <p class="mb-1"><strong>Problem :</strong> <?= esc($problemText) ?></p>
                <?php endif; ?>
                <p class="mb-2"><strong>Assigned Doctors :</strong></p>
                <?php if (! empty($doctorSummary)) : ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($doctorSummary as $doctorName) : ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= esc($doctorName) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="mb-0 text-muted">No doctor assigned.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canEditAdmission) : ?>
<div class="card mt-3 border-warning-subtle">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h5 class="card-title mb-1">Edit Admission Info</h5>
                <p class="text-muted mb-0">Update the admission timing, mode, department, referral source, and assigned doctors from this tab.</p>
            </div>
        </div>

        <div id="<?= esc($alertId) ?>"></div>

        <form id="<?= esc($formId) ?>" action="<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/admission') ?>" method="post">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Admission Date</label>
                    <input type="date" name="register_date" class="form-control" value="<?= esc($registerDateInput) ?>" required>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Admission Time</label>
                    <input type="time" name="reg_time" class="form-control" value="<?= esc($regTimeInput) ?>" step="60" required>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label d-block">Admission Mode</label>
                    <div class="form-check form-check-inline mt-2">
                        <input class="form-check-input" type="radio" name="case_type" id="case_type_non_mlc_<?= (int) ($ipd->id ?? 0) ?>" value="0" <?= (int) ($ipd->case_type ?? 0) !== 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="case_type_non_mlc_<?= (int) ($ipd->id ?? 0) ?>">NON MLC</label>
                    </div>
                    <div class="form-check form-check-inline mt-2">
                        <input class="form-check-input" type="radio" name="case_type" id="case_type_mlc_<?= (int) ($ipd->id ?? 0) ?>" value="1" <?= (int) ($ipd->case_type ?? 0) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="case_type_mlc_<?= (int) ($ipd->id ?? 0) ?>">MLC</label>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Department</label>
                    <select name="dept_id" class="form-select">
                        <option value="0">Select Department</option>
                        <?php foreach ($departments as $department) : ?>
                            <option value="<?= (int) ($department->iId ?? 0) ?>" <?= (int) ($department->iId ?? 0) === (int) ($ipd->dept_id ?? 0) ? 'selected' : '' ?>><?= esc($department->vName ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-6 col-md-12">
                    <label class="form-label">Refer By</label>
                    <select name="refer_by" class="form-select">
                        <option value="0">Select Refer By</option>
                        <?php foreach ($referMaster as $refer) : ?>
                            <option value="<?= (int) ($refer->id ?? 0) ?>" <?= (int) ($refer->id ?? 0) === (int) ($ipd->refer_by ?? 0) ? 'selected' : '' ?>><?= esc(trim((string) (($refer->title ?? '') . ' ' . ($refer->f_name ?? '')))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Assigned Doctors</label>
                    <div class="border rounded p-3 bg-light-subtle" style="max-height: 240px; overflow-y: auto;">
                        <div class="row g-2">
                            <?php foreach ($availableDoctors as $doctor) : ?>
                                <div class="col-xl-4 col-lg-6 col-md-6">
                                    <label class="form-check d-flex gap-2 align-items-start">
                                        <input class="form-check-input mt-1" type="checkbox" name="doc_id[]" value="<?= (int) ($doctor->id ?? 0) ?>" <?= in_array((int) ($doctor->id ?? 0), $selectedDoctorIds, true) ? 'checked' : '' ?>>
                                        <span class="form-check-label small"><?= esc($doctor->DocSpecName ?? $doctor->p_fname ?? '') ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($availableDoctors)) : ?>
                                <div class="col-12 text-muted small">No active doctors available.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="text-muted mb-2">Contact Person</h6></div>
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Contact Name</label>
                    <input type="text" name="contact_person_Name" class="form-control" maxlength="50" value="<?= esc($contactPersonName) ?>" placeholder="Name of contact person">
                </div>
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Relation</label>
                    <input type="text" name="relation" class="form-control" maxlength="50" value="<?= esc($contactRelation) ?>" placeholder="e.g. Son, Wife">
                </div>
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Mobile 1</label>
                    <input type="text" name="P_mobile1" class="form-control" maxlength="50" value="<?= esc($contactMobile1) ?>" placeholder="Primary contact number">
                </div>
                <div class="col-lg-4 col-md-6">
                    <label class="form-label">Mobile 2</label>
                    <input type="text" name="P_mobile2" class="form-control" maxlength="50" value="<?= esc($contactMobile2) ?>" placeholder="Secondary contact number">
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="text-muted mb-2">Clinical Notes</h6></div>
                <div class="col-lg-6 col-md-12">
                    <label class="form-label">Problem / Chief Complaint</label>
                    <input type="text" name="problem" class="form-control" maxlength="200" value="<?= esc($problemText) ?>" placeholder="Brief description of presenting problem">
                </div>
                <div class="col-lg-6 col-md-12">
                    <label class="form-label">Remark</label>
                    <textarea name="remark" class="form-control" rows="2" placeholder="Additional remarks"><?= esc($remarkText) ?></textarea>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary">Save Admission Changes</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="if (typeof load_form_div === 'function') { load_form_div('<?= site_url('billing/ipd/panel/' . (int) ($ipd->id ?? 0) . '/tab/admission') ?>', 'tab_admission_content'); }">Reset</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('<?= esc($formId) ?>');
        var alertBox = document.getElementById('<?= esc($alertId) ?>');
        if (!form || !window.jQuery) {
            return;
        }

        function renderAlert(type, message) {
            if (!alertBox) {
                return;
            }

            alertBox.innerHTML = '<div class="alert alert-' + type + ' mb-3">' + message + '</div>';
        }

        function setSavingState(isSaving) {
            var submitButton = form.querySelector('button[type="submit"]');
            if (!submitButton) {
                return;
            }

            submitButton.disabled = isSaving;
            submitButton.textContent = isSaving ? 'Saving...' : 'Save Admission Changes';
        }

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            setSavingState(true);
            if (alertBox) {
                alertBox.innerHTML = '';
            }

            $.ajax({
                url: form.getAttribute('action'),
                type: 'POST',
                dataType: 'json',
                data: $(form).serialize()
            }).done(function(response) {
                if (!response || Number(response.update || 0) !== 1) {
                    renderAlert('danger', response && response.message ? response.message : 'Unable to update admission details.');
                    return;
                }

                if (response.html) {
                    $('#tab_admission_content').html(response.html);
                }

                if (response.header) {
                    $(document).trigger('ipd:admission-updated', [response.header]);
                }

                if (typeof window.notify === 'function') {
                    window.notify('success', 'Admission Updated', response.message || 'Admission details updated.');
                }
            }).fail(function(xhr) {
                var message = 'Unable to update admission details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                renderAlert('danger', message);
            }).always(function() {
                setSavingState(false);
            });
        });
    })();
</script>
<?php else : ?>
<div class="alert alert-secondary mt-3 mb-0 d-flex align-items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock-fill flex-shrink-0" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
    You do not have permission to edit admission details. Contact your administrator to request the <strong>billing.ipd.admission.edit</strong> permission.
</div>
<?php endif; ?>
