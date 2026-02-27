
<section class="content-header">
    <h1>
        Diagnosis
        
    </h1>
    
</section>
<!-- Main content -->
<section class="content">
<div class="row">
	<?php if ($this->ion_auth->in_group('Diagnosis-PathLab')) { ?>
	  <div class="col-md-2">
		<div class="thumbnail">
		  <a href="javascript:load_form('/Lab_Admin/lab_path/5');">
			<img src="<?= base_url('assets/images/pathlab.jpeg');?>" alt="PathLab" style="width:100%" class="img-rounded">
			<div class="caption">
			  <H1>PathLab</H1>
			</div>
		  </a>
		</div>
	  </div>
	<?php }  ?>
	<?php if ($this->ion_auth->in_group('DiagnosisMRI')) { ?>
	  <div class="col-md-2">
		<div class="thumbnail">
		  <a href="javascript:load_form('/Lab_Admin/lab_path/2');">
			<img src="<?= base_url('assets/images/MRI-512.png');?>" alt="MRI" style="width:100%" class="img-rounded">
			<div class="caption">
			  <H1>MRI</H1>
			</div>
		  </a>
		</div>
	  </div>
	<?php }  ?>
	<?php if ($this->ion_auth->in_group('DiagnosisX-Ray')) { ?>
	  <div class="col-md-2">
		<div class="thumbnail">
		  <a href="javascript:load_form('/Lab_Admin/lab_path/3');">
			<img src="<?= base_url('assets/images/x-Ray.png');?>" alt="X-Ray" style="width:100%" class="img-rounded">
			<div class="caption">
			  <H1>X-Ray</H1>
			</div>
		  </a>
		</div>
	  </div>
	<?php }  ?>
	<?php if ($this->ion_auth->in_group('DiagnosisCTScan')) { ?>
	  <div class="col-md-2">
		<div class="thumbnail">
		  <a href="javascript:load_form('/Lab_Admin/lab_path/4');">
			<img src="<?= base_url('assets/images/ct-scan.png');?>" alt="CT-Scan" style="width:100%" class="img-rounded">
			<div class="caption">
			  <H1>CT-Scan</H1>
			</div>
		  </a>
		</div>
	  </div>
	<?php } ?>
	<?php if ($this->ion_auth->in_group('DiagnosisUltraSound')) { ?>
	  <div class="col-md-2">
		<div class="thumbnail">
		  <a href="javascript:load_form('/Lab_Admin/lab_path/1');">
			<img src="<?= base_url('assets/images/Ultrasound.png');?>" alt="Ultra Sound" style="width:100%" class="img-rounded">
			<div class="caption">
			  <H1>Ultra Sound.</H1>
			</div>
		  </a>
		</div>
	  </div>
	<?php }  ?>
</div>
</section>