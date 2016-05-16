<!DOCTYPE html>
<html lang="en" ng-app="FROGIMS">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>FROG Inventory Management System</title>
		
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/main.css' );?>" />

		<script>
			var baseUrl = '<?php echo base_url();?>';
		</script>
		<script src="<?php echo base_url( 'resources/js/angular.min.js' );?>"></script>
		<script src="<?php echo base_url( 'resources/js/angular-animate.min.js' );?>"></script>
		<script src="<?php echo base_url( 'resources/js/ui-bootstrap-tpls-1.1.2.min.js' );?>"></script>
		<script src="<?php echo base_url( 'resources/js/angular-ui-router.min.js');?>"></script>
		<script src="<?php echo base_url( 'app.js' );?>"></script>
		<script src="<?php echo base_url( 'controllers.js' );?>"></script>
		<script src="<?php echo base_url( 'services.js' );?>"></script>
	</head>
	<body ui-view>
		
	</body>
</html>