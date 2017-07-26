<!DOCTYPE html>
<html lang="en" ng-app="FROGIMS">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>FROG Ticket Management Inventory System</title>

		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/animate.css' );?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/main.css' );?>" />

		<script>
			var baseUrl = '<?php echo base_url(); ?>';
		</script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/angular.min.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/angular-animate.min.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/ui-bootstrap-tpls-1.3.3.min.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/angular-ui-router.min.js');?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/jquery-3.0.0.min.js');?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/highcharts.js');?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'directives-charts.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'app.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'controllers.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/transfer-validation-model.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/transfer-model.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/conversion-model.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/allocation-model.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/collection-model.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/adjustment-model.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/shift-turnover-model.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/tvm-reading-model.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/admin-controllers.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'resources/js/modal-controllers.js' );?>"></script>
		<script type="text/javascript" src="<?php echo base_url( 'services.js' );?>"></script>
	</head>
	<body>
		<div ui-view></div>
	</body>
</html>