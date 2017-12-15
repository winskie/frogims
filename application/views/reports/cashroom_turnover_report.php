<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>TICMS - Cashroom Turnover Report</title>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
	</head>
	<body class="container-fluid">
		<div class="report-header">
			<h1 class="report-title">Cashroom Turnover Report</h1>
			<div>{store_name} / {shift_name} / {business_date}</div>
		</div>
		<div class="report-body">
		<?php
		var_dump( $cash_vault );
		?>
		</div>
	</body>
</html>