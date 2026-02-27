<section class="content-header">
  <h1>
	Dashboard
	<small>panel</small>
  </h1>
  <ol class="breadcrumb">
	<li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
	<li class="active">Dashboard</li>
  </ol>
</section>
<section class="content">
<div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="ion ion-person-add"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">OPD</span>
              <span class="info-box-number" style="font-size:12px"><?=$opd_count_total[0]->T_opd ?></span>
			  <span class="info-box-number" style="font-size:12px"><a href="javascript:load_form('/Data/index');" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a></span>
            </div>
           </div>
        </div>
        <!-- ./col -->
        <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="ion ion-ios-people-outline"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Admit & Discharge Status</span>
              <span class="info-box-number" style="font-size:12px">Discharge Today : <?=$no_admit_ipd_discharge ?></span>
			  <span class="info-box-number" style="font-size:12px">Admit Today : <?=$no_admit_ipd_admit ?></span>
            </div>
           </div>
        </div>
		<div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-green"><i class="ion ion-ios-cart-outline"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Current IPD Status</span>
              <span class="info-box-number" style="font-size:12px">Total Admit : <?=$no_admit_ipd ?> </span>
			  <span class="info-box-number" style="font-size:12px">Org. Admit : <?=$no_admit_ipd_org ?></span>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="ion ion-ios-gear-outline"></i></span>

            <div class="info-box-content">
              <span class="info-box-text">CPU & Memory Load</span>
			  <span class="info-box-number" style="font-size:12px"><?=date('Y-m-d H:i:s') ?></span>
              <span class="info-box-number" style="font-size:12px">CPU <?=$load ?><small>%</small></span>
			  <span class="info-box-number" style="font-size:12px">Memory <?=$mem ?><small>%</small></span>
			  <span class="info-box-number" style="font-size:12px">Free Disk <?=$bytesPer ?><small>%</small></span>
			  <span class="info-box-number" style="font-size:12px">Total Disk <?=$dsizetot?><small></small></span>
			</div>
            <!-- /.info-box-content -->
          </div>
          <!-- /.info-box -->
        </div>
      </div>
	<div class="row">
		<div class="col-md-4 col-sm-4 col-xs-12">
			<div class="row">
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="box">
						<div class="box-header">
						  <h3 class="box-title">OPD</h3>
						</div>
						<div class="box-body table-responsive no-padding">
							<table class="table table-hover">
								<tr>
								  <th>Organization</th>
								  <th>No of OPD</th>
								</tr>
								<?php foreach($opd_count as $row) {  ?>
									<tr>
									  <td><?=$row->Ins_name ?></td>
									  <td><?=$row->No_OPD ?></td>
									</tr>
								<?php } ?>
							</table>
						</div>
					</div>
				</div>
				<div class="col-md-12 col-sm-12 col-xs-12">
					<div class="box">
						<div class="box-header">
						  <h3 class="box-title">OPD</h3>
						</div>
						<div class="box-body table-responsive no-padding">
							<table class="table table-hover">
								<tr>
								  <th>Doctor Name</th>
								  <th>No of OPD</th>
								  <th>Direct</th>
								  <th>Org.</th>
								</tr>
								<?php foreach($opd_doc_wise as $row) {  ?>
									<tr>
									  <td><?=$row->p_fname ?></td>
									  <td><?=$row->No_opd ?></td>
									  <td><?=$row->Direct_OPD ?></td>
									  <td><?=$row->Org_OPD ?></td>
									</tr>
								<?php } ?>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-sm-4 col-xs-12">
			<div class="box">
				<div class="box-header">
				  <h3 class="box-title">IPD Doctors</h3>
				</div>
				<div class="box-body table-responsive no-padding">
					<table class="table table-hover">
						<tr>
						  <th>Doctor Name</th>
						  <th>No of IPD</th>
						</tr>
						<?php foreach($ipd_doc_total as $row) {  ?>
							<tr>
							  <td><?=$row->doc_name ?></td>
							  <td><?=$row->No_patient ?></td>
							</tr>
						<?php } ?>
					</table>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-sm-4 col-xs-12">
			<div class="box">
				<div class="box-header">
				  <h3 class="box-title">IPD</h3>
				</div>
				<div class="box-body table-responsive no-padding">
					<table class="table table-hover">
						<tr>
						  <th>Organization</th>
						  <th>No of IPD</th>
						</tr>
						<?php foreach($ipd_org_list as $row) {  ?>
							<tr>
							  <td><?=$row->ins_company_name ?></td>
							  <td><?=$row->No_IPD ?></td>
							</tr>
						<?php } ?>
					</table>
				</div>
			</div>
		</div>
		
	</div>

</section>
