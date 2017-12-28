<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>TICMS - Container Replacement Report</title>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/report.css' );?>" />
	</head>
	<body class="container-fluid">
		<div class="report-header">
			<h1 class="report-title">Container Replacement Report</h1>
			<div>{store_name} / {shift_name} / {business_date}</div>
		</div>
		<div class="report-body">
			<div class="row">
				<!-- SJT Replenishment and Current Reading (Pullout) -->
				<div class="col-sm-6">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">SJT Replenishment and Current Reading (Pullout)</h3>
						</div>
						<table class="table table-bordered table-condensed">
							<thead>
								<tr>
									<th rowspan="2">TVM</th>
									<th rowspan="2">Previous Reading</th>
									<th colspan="4">SJT Replenishment</th>
									<th rowspan="2">Current Reading</th>
									<th rowspan="2">Sold Ticket</th>
								</tr>
								<tr>
									<th>In</th>
									<th>Reject Bin</th>
									<th>Excess</th>
									<th>Time</th>
								</tr>
							</thead>
							<tbody>
								{sjt}
								<tr>
									<td>{tvm_num}</td>
									<td>{previous_reading}</td>
									<td>{replenishment}</td>
									<td>{reject_bin}</td>
									<td>{excess}</td>
									<td>{reading_time}</td>
									<td>{reading}</td>
									<td>{sold_ticket}</td>
								</tr>
								{/sjt}
								<?php if( empty( $sjt ) ):?>
								<tr>
									<td colspan="8">No SJT replenishment and reading data</td>
								</tr>
								<?php endif;?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- SVC Replenishment and Current Reading (Pullout) -->
				<div class="col-sm-6">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">SVC Replenishment and Current Reading (Pullout)</h3>
						</div>
						<table class="table table-bordered table-condensed">
							<thead>
								<tr>
									<th rowspan="2">TVM</th>
									<th rowspan="2">Previous Reading</th>
									<th colspan="4">SVC Replenishment</th>
									<th rowspan="2">Current Reading</th>
									<th rowspan="2">Sold Ticket</th>
								</tr>
								<tr>
									<th>In</th>
									<th>Reject Bin</th>
									<th>Excess</th>
									<th>Time</th>
								</tr>
							</thead>
							<tbody>
								{svc}
								<tr>
									<td>{tvm_num}</td>
									<td>{previous_reading}</td>
									<td>{replenishment}</td>
									<td>{reject_bin}</td>
									<td>{excess}</td>
									<td>{reading_time}</td>
									<td>{reading}</td>
									<td>{sold_ticket}</td>
								</tr>
								{/svc}
								<?php if( empty( $svc ) ):?>
								<tr>
									<td colspan="8">No SVC replenishment and reading data</td>
								</tr>
								<?php endif;?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="row">
				<!-- Coin Box -->
				<div class="col-sm-6">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Coin Box</h3>
						</div>
						<table class="table table-bordered table-condensed">
							<thead>
								<tr>
									<th rowspan="2">TVM</th>
									<th colspan="2">Coin Box Replacement</th>
									<th rowspan="2">Time</th>
								</tr>
								<tr>
									<th>Box #</th>
									<th>Reading</th>
								</tr>
							</thead>
							<tbody>
								{coin_box}
								<tr>
									<td rowspan="{rows}">{tvm_num}</td>
									<td>{box_num}</td>
									<td>{reading}</td>
									<td>{reading_time}</td>
								</tr>
								{others}
								<tr>
									<td>{box_num}</td>
									<td>{reading}</td>
									<td>{reading_time}</td>
								</tr>
								{/others}
								{/coin_box}
								<?php if( empty( $coin_box ) ):?>
								<tr>
									<td colspan="4">No coin box reading data</td>
								</tr>
								<?php endif;?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- BNA Box -->
				<div class="col-sm-6">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">BNA Box</h3>
						</div>
						<table class="table table-bordered table-condensed">
							<thead>
								<tr>
									<th rowspan="2">TVM</th>
									<th colspan="2">BNA Box Replacement</th>
									<th rowspan="2">Time</th>
								</tr>
								<tr>
									<th>Box #</th>
									<th>Reading</th>
								</tr>
							</thead>
							<tbody>
								{note_box}
								<tr>
									<td rowspan="{rows}">{tvm_num}</td>
									<td>{box_num}</td>
									<td>{reading}</td>
									<td>{reading_time}</td>
								</tr>
								{others}
								<tr>
									<td>{box_num}</td>
									<td>{reading}</td>
									<td>{reading_time}</td>
								</tr>
								{/others}
								{/note_box}
								<?php if( empty( $note_box ) ):?>
								<tr>
									<td colspan="4">No BNA box reading data</td>
								</tr>
								<?php endif;?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="row">
				<!-- Hopper Replacement -->
				<div class="col-sm-6">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Hopper Replenishment</h3>
						</div>
						<table class="table table-bordered table-condensed">
							<thead>
								<tr>
									<th rowspan="2">TVM</th>
									<th colspan="2">Hopper In #1</th>
									<th rowspan="2">Time</th>
									<th rowspan="2">Total Replenishment</th>
									<th colspan="2">Current Reading</th>
								</tr>
								<tr>
									<th>Php5.00</th>
									<th>Php1.00</th>
									<th>Php5.00</th>
									<th>Php1.00</th>
								</tr>
							</thead>
							<tbody>
								{hopper}
								<tr>
									<td rowspan="{rows}">{tvm_num}</td>
									<td>{php5_amount}</td>
									<td>{php1_amount}</td>
									<td>{allocation_time}</td>
									<td rowspan="{rows}">{total_replenishment}</td>
									<td rowspan="{rows}">{php5_reading}</td>
									<td rowspan="{rows}">{php1_reading}</td>
								</tr>
								{others}
								<tr>
									<td>{php5_amount}</td>
									<td>{php1_amount}</td>
									<td>{allocation_time}</td>
								</tr>
								{/others}
								{/hopper}
								<?php if( empty( $hopper ) ):?>
								<tr>
									<td colspan="4">No hopper replenishment and reading data</td>
								</tr>
								<?php endif;?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>