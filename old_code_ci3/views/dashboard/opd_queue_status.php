<!-- Main content -->
<section class="content">
	<div class="row">
		<div class="col-md-3">
			<div class="box box-primary">
				<div class="box-body box-profile">
					<h3 class="profile-username text-center">Dr. <?=$doc_master[0]->p_fname?></h3>
					<p class="text-muted text-center"><?=$doc_master[0]->Spec?></p>
					
					<p><a href="javascript:refresh_panel();"><i class="fa fa-dashboard"></i>Refresh</a></p>
					<div id="opd_status">
						
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-9">
			<div class="nav-tabs-custom">
				<ul class="nav nav-tabs">
					<li ><a href="#tab0" data-toggle="tab">On Booking</a></li>
				  	<li class="active" ><a href="#tab1" data-toggle="tab">On Waiting</a></li>
				  	<li><a href="#tab2" data-toggle="tab">Visited</a></li>
				  	<li><a href="#tab3" data-toggle="tab">Canceled</a></li>
				</ul>
				<div class="tab-content">
					<div class=" tab-pane" id="tab0">
						
					</div>
					<div class="tab-pane active" id="tab1">
						
					</div>
					<div class="tab-pane" id="tab2">
						
					</div>
					<div class="tab-pane" id="tab3">
						
					</div>
				</div>
			</div>
		</div>
</section>
