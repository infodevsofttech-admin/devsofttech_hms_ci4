<div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Medicine </h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Prescribed: </label>
                            <input class="form-control input-sm" name="input_med_name" id="input_med_name" placeholder="Like ACIVIR IV INJ 10ML , ACTIBILE 600TAB" type="text">
                            <input name="hid_med_id" id="hid_med_id" type="hidden" value="0">
                            <input name="hid_p_med_id" id="hid_p_med_id" type="hidden" value="0">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Type </label>
                            <input class="form-control input-sm" name="input_med_type" id="input_med_type" placeholder="TAB,CAP,SYR,INJ" type="text">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Dose: </label>
                            <select class="form-control input-sm" id="input_dosage" name="input_dosage" data-placeholder="Select a dosage timing">
                                <option value='0'>All</option>
                                <?php
                                foreach ($opd_dose_shed as $row) {
                                    echo '<option value="' . $row->dose_shed_id . '"  >' . $row->dose_show_sign . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">When: </label>
                            <select class="form-control input-sm" id="input_dosage_when" name="input_dosage_when" data-placeholder="Select a dosage">
                                <option value='0'></option>
                                <?php
                                foreach ($opd_dose_when as $row) {
                                    echo '<option value="' . $row->dose_when_id . '"  >' . $row->dose_sign . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Frequency: </label>
                            <select class="form-control input-sm" id="input_dosage_freq" name="input_dosage_freq" data-placeholder="Select a Frequency">
                                <option value='0'></option>
                                <?php
                                foreach ($opd_dose_frequency as $row) {
                                    echo '<option value="' . $row->dose_freq_id . '"  >' . $row->dose_sign . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Where: </label>
                            <select class="form-control input-sm" id="input_dose_where" name="input_dose_where" data-placeholder="Place in body">
                                <option value='0'></option>
                                <?php
                                foreach ($opd_dose_where as $row) {
                                    echo '<option value="' . $row->dose_where_id . '"  >' . $row->dose_sign . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Duration: </label>
                            <input class="form-control input-sm" name="input_no_of_days" id="input_no_of_days" type="text">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Qty: </label>
                            <input class="form-control input-sm" name="input_qty" id="input_qty" type="text">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Remark: </label>
                            <input class="form-control input-sm" name="input_remark" id="input_remark" placeholder="" type="text">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags">Salt Name: </label>
                            <input class="form-control input-sm" name="input_genericname" id="input_genericname" placeholder="" type="text">
                        </div>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <div class="ui-widget">
                            <label for="tags"> </label>
                            <button type="button" id="btn_medical" class="btn btn-primary">+ADD / Update</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>