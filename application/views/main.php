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
		<nav class="navbar navbar-inverse navbar-fixed-top navbar-main" role="navigation">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
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
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<li uib-dropdown>
							<a href class="navbar-link" uib-dropdown-toggle>
								{{ user.username }}
							</a>
							<ul class="dropdown-menu" uib-dropdown-menu>
								<li>
									<a class="navbar-link" href="<?php echo site_url( '/login/logout');?>">Log out</a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</nav>
		<nav class="navbar navbar-default navbar-fixed-top navbar-secondary" role="navigation">
			<div class="container">
				<ul class="nav navbar-nav">
					<li uib-dropdown>
						<a href uib-dropdown-toggle>
							{{ currentStore.store_name }} <span class="caret"></span>
						</a>
						<ul class="dropdown-menu" uib-dropdown-menu>
							<li ng-repeat="store in stores">
								<a href ng-click="changeStore( store )">{{ store.store_name }}</a>
							</li>
						</ul>
					</li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<li uib-dropdown>
						<a href uib-dropdown-toggle>
							{{ currentShift.description }} <span class="caret"></span>
						</a>
						<ul class="dropdown-menu" uib-dropdown-menu>
							<li ng-repeat="shift in shifts">
								<a href ng-click="changeShift( shift )">{{ shift.description }}</a>
							</li>
						</ul>
					</li>
				</ul>
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