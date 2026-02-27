<div class="col-md-12">
										<table class="table table-condensed">
											<?php if(count($ipd_discharge_surgery)>0) { 
												echo '<tr>
														<th>Surgery Name</th>
														<th width="300px">Surgery Date</th>
														<th>Remarks</th>
														<th></th>
														<th></th>
													</tr>'; 
											} ?>
											<?php foreach($ipd_discharge_surgery as $row) { ?>
											<tr>
												<td><input class="form-control input-sm"
														name="input_surgery_name_<?=$row->id?>"
														id="input_surgery_name_<?=$row->id?>" type="text"
														value="<?=$row->surgery_name?>">
												</td>
												<td>
													<div class="input-group date">
														<div class="input-group-addon">
															<i class="fa fa-calendar"></i>
														</div>
														<input id="surgery_date_<?=$row->id?>" name="surgery_date_<?=$row->id?>" class="form-control pull-right datepicker" 
														type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" 
														value="<?php echo ($row->surgery_date?MysqlDate_to_str($row->surgery_date):date('d/m/Y')); ?>"  />
													</div>
												</td>
												<td>
													<input class="form-control input-sm"
														name="input_surgery_remark_<?=$row->id?>"
														id="input_surgery_remark_<?=$row->id?>" type="text"
														value="<?=$row->surgery_remark?>">
												</td>
												<td><a href="javascript:surgeryUpdate('<?=$row->id?>','<?=$row->ipd_id?>')">Update</a>
												</td>
												<td><a href="javascript:surgeryRemove('<?=$row->id?>','<?=$row->ipd_id?>')">Remove</a>
												</td>
											</tr>
											<?php } ?>
										</table>
									</div>