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
<?php echo form_open('Doctor/AddNew', array('role' => 'form', 'class' => 'form1')); ?>
<div class="box box-danger">
  <div class="box-header">
    <div class="box-title">
      <p>
        <strong>Name :</strong><?= $person_info[0]->p_fname ?>
        <strong><?= $person_info[0]->p_relative ?> </strong><?= $person_info[0]->p_rname ?>
        <strong>/ Age :</strong><?= $person_info[0]->age ?>
        <strong>/ Gender :</strong><?= $person_info[0]->xgender ?>
        <strong>/ P Code :</strong><?= $person_info[0]->p_code ?>
      </p>
      <input type="hidden" id="pid" name="pid" value="<?= $person_info[0]->id ?>" />
      <input type="hidden" id="pname" name="pname" value="<?= $person_info[0]->p_fname ?>" />
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
            <input type="text" name="phone1" id="phone1" class="form-control" data-inputmask='"mask": "9999999999"' data-mask value="<?= $person_info[0]->mphone1 ?>">
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
            <input id="res_date" name="res_date" class="form-control pull-right datepicker" id="datepicker_dob" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value=<?= date('d/m/Y') ?> />
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="bootstrap-timepicker">
          <div class="form-group">
            <label>Time: (24 Hour Format)</label>
            <div class="input-group">
              <input id="res_time" name="res_time" class="form-control" type="text" value="<?= date('H:i') ?>" required>
              <div class="input-group-addon">
                <i class="fa fa-clock-o"></i>
              </div>
            </div>
            <!-- /.input group -->
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
    <?php if (count($case_master) > 0) { ?>
      <div class="row">
        <div class="col-md-3">
          <div class="form-group">
            <div class="radio">
              <label>
                <input name="optionsRadios_org" id="options_org1" value="0" type="radio">
                Cash
              </label>
              <label>
                <input name="optionsRadios_org" id="options_morg2" value="<?= $case_master[0]->id ?>" type="radio" checked="checked">
                Org. Credit : <?= $case_master[0]->case_id_code ?>
              </label>
            </div>
          </div>
        </div>
      </div>
    <?php } else {
      echo '<input name="optionsRadios_org" id="options_morg2" value="0"  type="hidden" >';
    } ?>
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Department </label>
            <select class="form-control" name="dept_id" id="dept_id">
            <option value="0">Select Department</option>
            <?php
            foreach ($hc_department as $row) {
              echo '<option value="' . $row->iId . '">' . $row->vName . '</option>';
            }
            ?>
            </select>
        </div>
      </div>
    </div>
    <hr />
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label>Doctor </label>
          <br />
          <?php
          foreach ($doc_spec_l as $row) {
            echo '<label>';
            echo '<input type="checkbox" name="doc_id[]" class="flat-red" value=' . $row->id . '> ';
            echo $row->p_fname . ' [<i>' . $row->SpecName . '</i>]';
            echo '</label><br/>';
          }
          ?>
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
            <?php
            foreach ($ipd_bed_list as $row) {
              echo '<option value="' . $row->id . '">' . $row->Bed_Desc . '</option>';
            }
            ?>
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
            <?php
            foreach ($refer_master as $row) {
              echo '<option value="' . $row->id . '">' . $row->title . '' . $row->f_name . '</option>';
            }
            ?>
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
        <div>
        </div>

        <div class="row">
          <div class="col-md-12">
            <div class="box">
              <div class="box-header">
                <h3 class="box-title">Remarks
                  <small>Write some about the problem</small>
                </h3>
                <!-- tools box -->
                <div class="pull-right box-tools">
                  <button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                    <i class="fa fa-minus"></i></button>
                  <button type="button" class="btn btn-default btn-sm" data-widget="remove" data-toggle="tooltip" title="Remove">
                    <i class="fa fa-times"></i></button>
                </div>
                <!-- /. tools -->
              </div>
              <!-- /.box-header -->
              <div class="box-body pad">

                <textarea id='remark' name="remark" class="textarea" placeholder="Place some text here" style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"></textarea>

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
      <div class="box-footer">

      </div>
    </div>
    <?php echo form_close(); ?>
    <script>
      $(function() {
        $('#res_time').datetimepicker({
          format: 'HH:mm'
        });
      });

      $(document).ready(function() {
        $('form.form1').on('submit', function(form) {
          form.preventDefault();

          var room_list = $('#room_list').val();

          if (room_list == '' || room_list == '0') {
            alert('Please Select Room');
          } else {
            $.post('/index.php/IpdNew/AddNew', $('form.form1').serialize(), function(data) {
              if (data.insertid == 0) {
                $('div.jsError').html(data.error_text);
              } else {
                load_form('/IpdNew/ipd_panel/' + data.insertid);
              }
            }, 'json');
          }

        });


      });
    </script>