<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>TICMS - Shift Collection Report</title>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
	</head>
	<body class="container-fluid">
		<div class="report-header">
			<h1 class="report-title">Shift Collection Report</h1>
			<div>{store_name} / {shift_name} / {business_date}</div>
		</div>
		<div class="report-body">
			<div class="panel panel-default">
				<table class="table table-bordered table-condensed">
					<thead>
						<tr>
							<td colspan="21"><h4>Sales from TVM</h4></td>
						</tr>
					</thead>
					<thead>
						<tr>
							<th rowspan="3">TVM No.</th>
							<th colspan="6">No. of Tickets Sold</th>
							<th rowspan="3">BNA Box</th>
							<th rowspan="3">Coin Box</th>
							<th rowspan="3">CA</th>
							<th rowspan="3">Gross Sales</th>
							<th rowspan="3">CA Reading</th>
							<th colspan="5">Deductions</th>
							<th rowspan="3">Over/<br/>(Short)</th>
							<th rowspan="3">Net Sales</th>
							<th rowspan="3"></th>
							<th rowspan="3">Total Cash<br />Collection</th>
						</tr>
						<tr>
							<th rowspan="2">SJT</th>
							<th rowspan="2">SVC</th>
							<th colspan="4" rowspan="2"></th>
							<th colspan="3">Hopper Reading</th>
							<th rowspan="2">Hopper/<br/>Change Fund</th>
							<th rowspan="2">Refunded<br />TVMIR</th>
						</tr>
						<tr>
							<th>Previous</th>
							<th>Replenish</th>
							<th>Present</th>
						</tr>
					</thead>
					<tbody>
						{tvm_sales}
						<tr>
							<td>{tvm_num}</td>
							<td>{sjt_sold_ticket}</td>
							<td>{svc_sold_ticket}</td>
							<td colspan="4"></td>
							<td class="text-right">{actual_bill_sales}</td>
							<td class="text-right">{actual_coin_sales}</td>
							<td><!-- coin_acceptor_fund --></td>
							<td class="text-right">{gross_sales}</td>
							<td><!-- CA reading --></td>
							<td class="text-right">{previous_reading}</td>
							<td class="text-right">{total_replenishment}</td>
							<td class="text-right">{reading}</td>
							<td class="text-right">{hopper_change_fund}</td>
							<td class="text-right">{refunded_tvmir}</td>
							<td class="text-right">{short_over}</td>
							<td class="text-right">{net_sales}</td>
							<td></td>
							<td class="text-right">{cash_collection}</td>
						</tr>
						{/tvm_sales}
						<?php if( empty( $tvm_sales ) ):?>
						<tr>
							<td colspan="16">No TVM sales data</td>
						</tr>
						<?php endif;?>
					</tbody>
					<tr>
						<th>Total Ticket Sold</th>
						<td class="text-right"><?php echo number_format( $tvm_totals['sjt_sold'] ); ?></td>
						<td class="text-right"><?php echo number_format( $tvm_totals['svc_sold'] ); ?></td>
						<td colspan="4"></td>
						<td colspan="5"></td>
						<td class="text-right"><?php echo number_format( $tvm_totals['previous_reading'], 2 ); ?></td>
						<td class="text-right"><?php echo number_format( $tvm_totals['replenishment'], 2 ); ?></td>
						<td class="text-right"><?php echo number_format( $tvm_totals['reading'], 2 ); ?></td>
						<td colspan="3"></td>
						<td class="text-right"><?php echo number_format( $tvm_totals['net_sales'], 2 ); ?></td>
						<td></td>
						<td class="text-right"><?php echo number_format( $tvm_totals['cash_collection'], 2 ); ?></td>
					</tr>
					<tr>
						<th colspan="7">Total amount from TVM</th>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td colspan="4"></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td colspan="15"></td>
						<th colspan="3">Add: Unclaimed TVMIR/Overage</th>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
					<td colspan="15"></td>
						<th colspan="3">TOTAL</th>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<thead>
						<tr>
							<td colspan="21"><h4>Sales from Station Teller</h4></td>
						</tr>
					</thead>
					<thead>
						<tr>
							<th rowspan="2">Teller Name</th>
							<th colspan="6">Number of Tickets Sold</th>
							<th colspan="3" rowspan="2"></th>
							<th rowspan="2">Gross Sales</th>
							<th colspan="4">Deductions</th>
							<th colspan="2" rowspan="2"></th>
							<th rowspan="2">Over/<br/>(Short)</th>
							<th rowspan="2">Net Sales</th>
							<th rowspan="2">Add:Change Fund</th>
							<th rowspan="2">Total Cash Collection</th>
						</tr>
						<tr>
							<th>SJT</th>
							<th>SVC</th>
							<th>CSC</th>
							<th>Free Exit</th>
							<th>Paid Exit</th>
							<th>Unconfirmed</th>

							<th>TCERF</th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{teller_sales}
						<tr>
							<td>{assignee}</td>
							<td class="text-right">{sold_sjt}</td>
							<td class="text-right">{sold_svc}</td>
							<td class="text-right">{issued_csc}</td>
							<td class="text-right">{free_exit}</td>
							<td class="text-right">{paid_exit}</td>
							<td class="text-right">{unconfirmed}</td>
							<td colspan="3"></td>
							<td class="text-right">{gross_sales}</td>
							<td class="text-right">{tcerf}</td>
							<td></td>
							<td></td>
							<td></td>
							<td colspan="2"></td>
							<td class="text-right">{short_over}</td>
							<td class="text-right">{net_sales}</td>
							<td class="text-right">{change_fund}</td>
							<td class="text-right">{cash_collection}</td>
						</tr>
						{/teller_sales}
					</tbody>
					<tr>
						<th>Total tickets sold</th>
						<td class="text-right"><?php echo number_format( $teller_totals['sold_sjt'] ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['sold_svc'] ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['issued_csc'] ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['free_exit'] ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['paid_exit'] ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['unconfirmed'] ); ?></td>
						<td colspan="10"></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<th colspan="7">Total amount from teller</th>
						<td colspan="3">
						<td class="text-right"><?php echo number_format( $teller_totals['gross_sales'], 2 ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['tcerf'], 2 ); ?></td>
						<td></td>
						<td></td>
						<td></td>
						<td colspan="2"></td>
						<td class="text-right"><?php echo number_format( $teller_totals['short_over'], 2 ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['net_sales'], 2 ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['change_fund'], 2 ); ?></td>
						<td class="text-right"><?php echo number_format( $teller_totals['cash_collection'], 2 ); ?></td>
					</tr>
					<thead>
						<tr>
							<td colspan="21"><h4>Grand Total</h4></td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td></td>
							<td class="text-right"><?php echo number_format( $grand_totals['sold_sjt'] ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['sold_svc'] ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['issued_csc'] ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['free_exit'] ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['paid_exit'] ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['unconfirmed'] ); ?></td>
							<td colspan="3">
							<td class="text-right"><?php echo number_format( $grand_totals['gross_sales'], 2 ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['tcerf'], 2 ); ?></td>
							<td></td>
							<td></td>
							<td></td>
							<td class="text-right"><?php echo number_format( $grand_totals['hopper_change_fund'], 2 ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['refunded_tvmir'], 2 ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['short_over'], 2 ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['net_sales'], 2 ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['change_fund'], 2 ); ?></td>
							<td class="text-right"><?php echo number_format( $grand_totals['cash_collection'], 2 ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</body>
</html>