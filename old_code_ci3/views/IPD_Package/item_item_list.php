<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?=H_Name?></title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  
  <link href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <!-- Font Awesome -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/css/font-awesome.min.css'); ?>">
  <!-- Ionicons -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/css/ionicons.min.css'); ?>">
    <!-- Theme style -->
  
  <link rel="stylesheet" href="<?php echo base_url('assets/dist/css/AdminLTE.min.css'); ?>">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body onload="window.print();" >
<div class="wrapper">
<section class="invoice" style="font-size:11px;">
    <table id="example1" class="table table-bordered table-striped TableData">
    <thead>
    <tr>
      <th>OPD or IPD</th>
      <th>Group</th>
      <th>Charge Name</th>
	  <th>Charge Details</th>
      <th>Amount</th>
      <th></th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
		<td><?=$data[$i]->isIPD_OPD ?></td>
		<td><?=$data[$i]->group_desc ?></td>
		<td><?=$data[$i]->idesc ?></td>
		<td><?=$data[$i]->idesc_detail ?></td>
		<td><?=$data[$i]->amount ?></td>
		<td></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <th>OPD or IPD</th>
      <th>Group</th>
      <th>Charge Name</th>
      <th>Amount</th>
      <th></th>
    </tr>
    </tfoot>
  </table> 
</section>
</div>
<!-- /.content -->
</body>
</html>