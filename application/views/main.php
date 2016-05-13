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
	<body ng-controller="StoreController">
		<!-- Navigation -->
		<nav class="navbar navbar-inverse navbar-fixed-top">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
						<span class="sr-only">Toggle Navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="#">FROG Inventory Management System</a>
				</div>
				<div id="navbar" class="collapse navbar-collapse">
					<ul class="nav navbar-nav">
						<li><a ui-sref="dashboard">Dashboard</a></li>
						<li class="active"><a ui-sref="store">Store</a></li>
						<li><a ui-sref="cashroom">Cashroom</a></li>
						<li><a ui-sref="satellite">Satellite</a></li>
					</ul>
                    <form class="navbar-form navbar-right">
                        <select class="form-control"
                                ng-model="currentShift"
                                ng-options="shift.shift_num for shift in shifts track by shift.id"
                                ng-change="changeShift( currentShift )">
                        </select>
                    </form>
					<ul class="nav navbar-nav navbar-right">
						<li>
							<a href>
								{{ user.username }}
							</a>
						</li>
						<li>
							<a href="<?php echo site_url( '/login/logout');?>">Log out</a>
						</li>
					</ul>
				</div>
			</div>
		</nav>
		
		<!-- Main Content -->
		<div class="container">
			<div id="content" ui-view></div>
		</div>
		
		<!-- Debug -->
		<div ng-bind-html="debug"></div>
	</body>
</html>