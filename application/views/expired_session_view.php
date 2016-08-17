<!DOCTYPE html>
<html lang="en" ng-app="FROGIMS">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE-edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>FROG Ticket Management Inventory System Login</title>

        <link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
    </head>
	<body>
		<div class="container">
			<h2>FROG Ticket Management Inventory System</h2>
			<div class="well well-lg">
				<h3 class="text-danger">Expired Session</h3>
				<p>Your session has already expired. Please <?php echo anchor( 'login', 'login' );?> again.</p>
				<p>If you have any further questions regarding this message, please contact your system administrator.</p>
			</div>
		</div>
	</body>
</html>
