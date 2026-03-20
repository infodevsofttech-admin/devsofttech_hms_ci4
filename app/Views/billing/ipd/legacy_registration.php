<style>
  .ipd-reg-shell .ipd-modern-box {
    border: 0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
  }

  .ipd-reg-shell .ipd-modern-box > .box-header {
    background: linear-gradient(120deg, #0f766e 0%, #0ea5a3 55%, #22c55e 100%);
    color: #ffffff;
    padding: 14px 18px;
    border-bottom: 0;
  }

  .ipd-reg-shell .ipd-modern-box > .box-header .box-title p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
  }

  .ipd-reg-shell .box-body {
    background: #f8fafc;
    padding: 18px;
  }

  .ipd-reg-shell .form-group > label {
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 6px;
  }

  .ipd-reg-shell .form-control {
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    box-shadow: none;
    transition: border-color .2s ease, box-shadow .2s ease;
  }

  .ipd-reg-shell .form-control:focus {
    border-color: #0891b2;
    box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.15);
  }

  .ipd-reg-shell .input-group-addon {
    border-radius: 10px 0 0 10px;
    border-color: #cbd5e1;
    background: #f1f5f9;
    color: #334155;
  }

  .ipd-reg-shell .doctor-pick-list {
    max-height: 220px;
    overflow: auto;
    padding: 10px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
  }

  .ipd-reg-shell .doctor-pick-list label {
    display: block;
    padding: 3px 0;
    margin: 0;
    font-weight: 500;
  }

  .ipd-reg-shell .box .box-header {
    background: #e2e8f0;
    border-bottom: 1px solid #cbd5e1;
  }

  .ipd-reg-shell #btnnextconfirm {
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 600;
    background: linear-gradient(120deg, #0c4a6e 0%, #0369a1 100%);
    border: 0;
  }
</style>

<section class="content-header">
  <h1>
    IPD
    <small>Registration</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">OPD</li>
  </ol>
</section>

<?php if (empty($person_info)): ?>
  <div class="alert alert-danger">Patient not found.</div>
<?php else: ?>
<form action="<?= base_url('IpdNew/AddNew') ?>" role="form" class="form1 ipd-reg-shell" method="post" accept-charset="utf-8">
  <?= csrf_field() ?>
  <div class="box box-danger ipd-modern-box">
    <div class="box-header">
      <div class="box-title">
        <p>
          <strong>Name :</strong><?= esc($person_info->p_fname ?? '') ?>
          <strong><?= esc($person_info->p_relative ?? '') ?> </strong><?= esc($person_info->p_rname ?? '') ?>
          <strong>/ Age :</strong><?= esc($person_info->age ?? '') ?>
          <strong>/ Gender :</strong><?= esc($person_info->xgender ?? '') ?>
          <strong>/ P Code :</strong><?= esc($person_info->p_code ?? '') ?>
        </p>
        <input type="hidden" id="pid" name="pid" value="<?= esc($person_info->id ?? 0) ?>" />
        <input type="hidden" id="pname" name="pname" value="<?= esc($person_info->p_fname ?? '') ?>" />
      </div>
    </div>
    <div class="box-body">
      <div class="jsError"></div>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label>Relative or Responsible Person Name</label>
            <input type="text" class="form-control" id="rp_name" name="rp_name" placeholder="Name" value="">
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Relation</label>
            <input type="text" class="form-control" id="r_relation" name="r_relation" placeholder="Relation" value="">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label>Phone No. 1</label>
            <div class="input-group">
              <div class="input-group-addon">
                <i class="fa fa-phone"></i>
              </div>
              <input type="text" name="phone1" id="phone1" class="form-control" data-inputmask='"mask": "9999999999"' data-mask value="<?= esc($person_info->mphone1 ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="form-group">
            <label>Phone No. 2</label>
            <div class="input-group">
              <div class="input-group-addon">
                <i class="fa fa-phone"></i>
              </div>
              <input type="text" name="phone2" id="phone2" class="form-control" data-inputmask='"mask": "9999999999"' data-mask>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <label>Date</label>
            <div class="input-group date">
              <div class="input-group-addon">
                <i class="fa fa-calendar"></i>
              </div>
              <input id="res_date_display" class="form-control pull-right" type="date" value="<?= date('Y-m-d') ?>" required>
              <input id="res_date" name="res_date" type="hidden" value="<?= date('d/m/Y') ?>" />
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div>
            <div class="form-group">
              <label>Time: (24 Hour Format)</label>
              <div class="input-group">
                <input id="res_time" name="res_time" class="form-control" type="time" value="<?= date('H:i') ?>" step="60" required>
                <div class="input-group-addon">
                  <i class="fa fa-clock-o"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <div class="radio">
              <label>
                <input name="optionsRadios_mlc" id="options_mlc1" value="0" type="radio" checked="checked">
                NON MLC
              </label>
              <label>
                <input name="optionsRadios_mlc" id="options_mlc2" value="1" type="radio">
                MLC
              </label>
            </div>
          </div>
        </div>
      </div>
      <?php if (!empty($case_master)): ?>
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <div class="radio">
                <label>
                  <input name="optionsRadios_org" id="options_org1" value="0" type="radio">
                  Cash
                </label>
                <label>
                  <input name="optionsRadios_org" id="options_morg2" value="<?= esc($case_master[0]->id ?? 0) ?>" type="radio" checked="checked">
                  Org. Credit : <?= esc($case_master[0]->case_id_code ?? '') ?>
                </label>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <input name="optionsRadios_org" id="options_morg2" value="0" type="hidden">
      <?php endif; ?>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Department </label>
            <select class="form-control" name="dept_id" id="dept_id">
              <option value="0">Select Department</option>
              <?php foreach ($hc_department as $row): ?>
                <option value="<?= esc($row->iId ?? 0) ?>"><?= esc($row->vName ?? '') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <hr />
      <div class="row">
        <div class="col-md-6">
          <div class="form-group doctor-pick-list">
            <label>Doctor </label>
            <br />
            <?php foreach ($doc_spec_l as $row): ?>
              <label><input type="checkbox" name="doc_id[]" class="flat-red" value="<?= esc($row->id ?? 0) ?>"> <?= esc($row->p_fname ?? '') ?> [<i><?= esc($row->SpecName ?? '') ?></i>]</label><br/>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <hr />
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Request Room Type</label>
            <select class="form-control" name="room_list" id="room_list">
              <option value="0">Select BED</option>
              <?php foreach ($ipd_bed_list as $row): ?>
                <option value="<?= esc($row->id ?? 0) ?>"><?= esc($row->Bed_Desc ?? '') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <hr />
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Refer By</label>
            <select class="form-control" name="refer_by_list" id="refer_by_list">
              <option value="0">Select Refer By</option>
              <?php foreach ($refer_master as $row): ?>
                <option value="<?= esc($row->id ?? 0) ?>"><?= esc(trim(($row->title ?? '') . ' ' . ($row->f_name ?? ''))) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <hr />
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label>Reason of visit / Problem / Diagnosis</label>
            <input class="form-control" type="text" id="problem" name="problem" />
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="box">
                <div class="box-header">
                  <h3 class="box-title">Remarks
                    <small>Write some about the problem</small>
                  </h3>
                  <div class="pull-right box-tools">
                    <button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                      <i class="fa fa-minus"></i></button>
                    <button type="button" class="btn btn-default btn-sm" data-widget="remove" data-toggle="tooltip" title="Remove">
                      <i class="fa fa-times"></i></button>
                  </div>
                </div>
                <div class="box-body pad">
                  <textarea id="remark" name="remark" class="textarea" placeholder="Place some text here" style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <button type="submit" class="btn btn-primary" id="btnnextconfirm">Confirm And Go for Admit</button>
              </div>
            </div>
          </div>
        </div>
        <div class="box-footer"></div>
      </div>
    </div>
  </div>
</form>

<script>
  $(function() {
    function formatLegacyDate(dateValue) {
      if (!dateValue) {
        return '';
      }

      var parts = String(dateValue).split('-');
      if (parts.length !== 3) {
        return dateValue;
      }

      return [parts[2], parts[1], parts[0]].join('/');
    }

    function syncLegacyDateField() {
      $('#res_date').val(formatLegacyDate($('#res_date_display').val()));
    }

    syncLegacyDateField();
    $('#res_date_display').on('change', syncLegacyDateField);

    $('#res_date_display, #res_time').on('focus click', function() {
      if (typeof this.showPicker === 'function') {
        this.showPicker();
      }
    });

    if ($.fn && typeof $.fn.datepicker === 'function') {
      $('#res_date_display').datepicker('destroy');
    }
  });

  $(document).ready(function() {
    $('form.form1').on('submit', function(form) {
      form.preventDefault();

      $('#res_date').val((function(dateValue) {
        if (!dateValue) {
          return '';
        }

        var parts = String(dateValue).split('-');
        return parts.length === 3 ? [parts[2], parts[1], parts[0]].join('/') : dateValue;
      })($('#res_date_display').val()));

      var room_list = $('#room_list').val();

      if (room_list === '' || room_list === '0') {
        alert('Please Select Room');
        return;
      }

      $.post('<?= base_url('IpdNew/AddNew') ?>', $('form.form1').serialize(), function(data) {
        if (Number(data.insertid || 0) === 0) {
          $('div.jsError').html(data.error_text || 'Unable to admit patient.');
        } else {
          load_form('<?= base_url('IpdNew/ipd_panel') ?>/' + data.insertid);
        }
      }, 'json');
    });
  });
</script>
<?php endif; ?>
