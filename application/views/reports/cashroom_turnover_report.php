<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>TICMS - Cashroom Turnover Report</title>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/report.css' );?>" />
	</head>
	<body class="container-fluid">
		<div class="report-header">
			<h1 class="report-title">Cashroom Turnover Report</h1>
			<div>{store_name} / {shift_name} / {business_date}</div>
		</div>
		<div class="report-body">
			<h3>Cash in Vault Turnover Report</h3>
			<div class="row">
				<!-- Balance per Book -->
				<div class="col-sm-6 col-md-4 col-lg-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Balance per Book</h3>
						</div>
						<table class="table table-condensed table-bordered">
							<thead>
								<tr>
									<th class="text-left">Particular</th>
									<th class="text-center">Amount</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Beginning Balance</td>
									<td class="text-money"><?php echo number_format( $cash_balance['beginning_balance'], 2 );?></td>
								</tr>
								<tr>
									<td>Add: For Deposit to Bank</td>
									<td></td>
								</tr>
								<?php foreach( $cash_balance['for_deposit'] as $shift => $data ):?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $shift;?></td>
									<td class="text-money"><?php echo number_format( $data['for_deposit'], 2 );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td>Add: Returned Change Fund</td>
									<td></td>
								</tr>
								<tr>
									<td style="padding-left: 3em;">Station Teller</td>
									<td class="text-money"><?php echo number_format( $cash_balance['returned_change_fund']['teller'], 2 );?></td>
								</tr>
								<tr>
									<td style="padding-left: 3em;">TVM</td>
									<td class="text-money"><?php echo number_format( $cash_balance['returned_change_fund']['TVM'], 2 );?></td>
								</tr>
								<tr>
									<td>Others Additions</td>
									<td class="text-money"></td>
								</tr>
								<?php foreach( $cash_balance['other_additions'] as $item => $amount ):?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $item;?></td>
									<td class="text-money"><?php echo number_format( $amount, 2 );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td colspan="2"></td>
								</tr>
								<tr>
									<td>Less: Deposit to Bank</td>
									<td class="text-money"></td>
								</tr>
								<?php foreach( $cash_balance['deposits'] as $shift => $deposit ):?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $shift;?></td>
									<td class="text-money"><?php echo number_format( $deposit, 2 );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td>TVM Hopper Replenishment</td>
									<td class="text-money"><?php echo number_format( $cash_balance['change_fund_allocations']['TVM'], 2 );?></td>
								</tr>
								<tr>
									<td>Teller Change Fund Allocation</td>
									<td class="text-money"><?php echo number_format( $cash_balance['change_fund_allocations']['teller'], 2 );?></td>
								</tr>
								<tr>
									<td>Other Deductions</td>
									<td class="text-money"></td>
								</tr>
								<?php foreach( $cash_balance['other_deductions'] as $item => $amount ):?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $item;?></td>
									<td class="text-money"><?php echo number_format( $amount, 2 );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td colspan="2"></td>
								</tr>
								<tr>
									<td>Balance per Book</td>
									<td class="text-money"><?php echo number_format( $cash_balance['ending_balance']['system'], 2 );?></td>
								</tr>
								<tr>
									<td>Less: Cash on Hand<?php echo $shift_turnover_status != SHIFT_TURNOVER_CLOSED ? ' <i class="glyphicon glyphicon-exclamation-sign text-danger"></i>' : ''; ?></td>
									<td class="text-money"><?php echo number_format( $cash_balance['ending_balance']['actual'], 2 );?></td>
								</tr>
								<tr>
									<td>Difference</td>
									<td class="text-money"><?php echo number_format( $cash_balance['ending_balance']['system'] - $cash_balance['ending_balance']['actual'], 2 );?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Cash Breakdown -->
				<div class="col-sm-6 col-md-4 col-lg-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Cash Breakdown</h3>
						</div>
						<table class="table table-condensed table-bordered">
							<thead>
								<tr>
									<th>Group</th>
									<th>Item</th>
									<th class="text-center">Quantity</th>
									<th class="text-center">Amount</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach( $cash_breakdown['change_fund'] as $item => $data ):?>
								<tr>
									<td><?php echo $data['group'];?></td>
									<td><?php echo $item;?></td>
									<td class="text-center"><?php echo number_format( $data['quantity'], 0 ).' '.$data['unit'];?></td>
									<td class="text-money"><?php echo number_format( $data['amount'], 2 ); ?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td colspan="4"></td>
								</tr>
								<tr>
									<td colspan="3">Add: For Deposit</td>
									<td class="text-money"></td>
								</tr>
								<?php foreach( $cash_breakdown['for_deposit'] as $item => $amount ):?>
								<tr>
									<td colspan="3" style="padding-left: 3em;"><?php echo $item; ?></td>
									<td class="text-money"><?php echo number_format( $amount, 2 ); ?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td colspan="4"></td>
								</tr>
								<tr>
									<td colspan="3">Cash on Hand<?php echo $shift_turnover_status != SHIFT_TURNOVER_CLOSED ? ' <i class="glyphicon glyphicon-exclamation-sign text-danger"></i>' : ''; ?></td>
									<td class="text-money"><?php echo number_format( $cash_breakdown['cash_on_hand'], 2 ); ?></td>
								</tr>

							</tbody>
						</table>
					</div>
				</div>

				<!-- TVMIR Breakdown -->
				<div class="col-sm-6 col-md-4 col-lg-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">TVMIR Breakdown</h3>
						</div>
						<table class="table table-condensed table-bordered">
							<tbody>
								<tr>
									<td colspan="4">Beginning Balance</td>
									<td class="text-money"><?php echo number_format( $tvmir_breakdown['beginning_balance'], 2); ?></td>
								</tr>
								<tr>
									<td colspan="5">Add: New TVMIR for Refund</td>
								</tr>
								<tr>
									<th class="text-center"></th>
									<th class="text-center">TVMIR #</th>
									<th class="text-center">Date</th>
									<th class="text-center">TVM #</th>
									<th class="text-center">Amount</th>
								</tr>
								<?php if( empty( $tvmir_breakdown['transactions']['additions'] ) ):?>
								<tr>
									<td colspan="5" class="text-center">No additional TVMIR transactions</td>
								</td>
								<?php else: ?>
								<?php foreach( $tvmir_breakdown['transactions']['additions'] as $index => $row ):?>
								<tr>
									<td class="text-center"><?php echo $index + 1; ?></td>
									<td class="text-center"><?php echo $row['transfer_reference_num'];?></td>
									<td class="text-center"><?php echo $row['business_date'];?></td>
									<td class="text-center"><?php echo $row['transfer_tvm_id'];?></td>
									<td class="text-money"><?php echo number_format( $row['amount'], 2); ?></td>
								</tr>
								<?php endforeach;?>
								<?php endif;?>
								<tr>
									<td colspan="5">Less: Refunded TVMIR</td>
								</tr>
								<tr>
									<th class="text-center"></th>
									<th class="text-center">TVMIR #</th>
									<th class="text-center">Date</th>
									<th class="text-center">TVM #</th>
									<th class="text-center">Amount</th>
								</tr>
								<?php if( empty( $tvmir_breakdown['transactions']['issuances'] ) ):?>
								<tr>
									<td colspan="5" class="text-center">No TVMIR refund transactions</td>
								</td>
								<?php else: ?>
								<?php foreach( $tvmir_breakdown['transactions']['issuances'] as $index => $row ):?>
								<tr>
									<td class="text-center"><?php echo $index + 1; ?></td>
									<td class="text-center"><?php echo $row['transfer_reference_num'];?></td>
									<td class="text-center"><?php echo $row['business_date'];?></td>
									<td class="text-center"><?php echo $row['transfer_tvm_id'];?></td>
									<td class="text-money"><?php echo number_format( $row['amount'], 2); ?></td>
								</tr>
								<?php endforeach;?>
								<?php endif;?>
								<tr>
									<td colspan="4">Less: Unclaimed/For Deposit</td>
									<td class="text-money"><?php echo number_format( $tvmir_breakdown['transactions']['returns'], 2 );?></td>
								</tr>
								<tr>
									<td colspan="4">End Balance</td>
									<td class="text-money"><?php echo number_format( $tvmir_breakdown['ending_balance']['system'], 2); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Concessionary Card Fee</h3>
						</div>
						<table class="table table-condensed table-bordered">
							<thead>
								<tr>
									<th>Particular</th>
									<th class="text-center">Amount</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Beginning Balance</td>
									<td class="text-money"><?php echo number_format( $csc_breakdown['beginning_balance'], 2 ); ?></td>
								</tr>
								<tr>
									<td>Add: New application</td>
									<td class="text-money"><?php echo number_format( $csc_breakdown['transactions']['new'], 2 ); ?></td>
								</tr>
								<tr>
									<td>Less: Issuance</td>
									<td class="text-money"><?php echo number_format( $csc_breakdown['transactions']['issuance'], 2 ); ?></td>
								</tr>
								<tr>
									<td>Ending Balance</td>
									<td class="text-money"><?php echo number_format( $csc_breakdown['ending_balance']['system'], 2 ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<h3>Ticket Turnover Report</h3>
			<div class="row">
				<!-- Balance per Book -->
				<div class="col-sm-6 col-md-4 col-lg-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Balance per Book</h3>
						</div>
						<table class="table table-condensed table-bordered">
							<thead>
								<tr>
									<th rowspan="2">Particulars</th>
									<th colspan="3" class="text-center">Pre-coded Tickets</th>
								</tr>
								<tr>
									<th class="text-center">SJT</th>
									<th class="text-center">SVC</th>
									<th class="text-center">CSC</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>Beginning Balance</td>
									<td class="text-right"><?php echo number_format( $ticket_balance['beginning_balance']['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $ticket_balance['beginning_balance']['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $ticket_balance['beginning_balance']['Concessionary'] );?></td>
								</tr>
								<tr>
									<td>Add: Delivery</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php foreach( $ticket_balance['ticket_deliveries'] as $unit => $data ): ?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $unit;?></td>
									<td class="text-right"><?php echo number_format( $data['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $data['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $data['Concessionary'] );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td>Add: Teller Remittance</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php foreach( $ticket_balance['teller_remittances'] as $package => $data ): ?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $package;?></td>
									<td class="text-right"><?php echo number_format( $data['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $data['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $data['Concessionary'] );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td>Add: Loose from TVM</td>
									<td class="text-right"><?php echo number_format( $ticket_balance['tvm_unsold_tickets']['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $ticket_balance['tvm_unsold_tickets']['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $ticket_balance['tvm_unsold_tickets']['Concessionary'] );?></td>
								</tr>
								<tr>
									<td>Add: Defective</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<td>Add: Ticket Transfer</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php foreach( $ticket_balance['ticket_receipts'] as $unit => $data ): ?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $unit;?></td>
									<td class="text-right"><?php echo number_format( $data['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $data['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $data['Concessionary'] );?></td>
								</tr>
								<?php endforeach;?>
								<?php if( ! empty( $ticket_balance['other_additions'] ) ):?>
								<tr>
									<td>Add: Others</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php foreach( $ticket_balance['other_additions'] as $item => $data ): ?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $item;?></td>
									<td class="text-right"><?php echo number_format( $data['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $data['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $data['Concessionary'] );?></td>
								</tr>
								<?php endforeach;?>
								<?php endif;?>
								<tr>
									<td colspan="4"></td>
								</tr>
								<tr>
									<td>Less: TVM Replenishments</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php foreach( $ticket_balance['ticket_allocations']['TVM'] as $shift => $data ): ?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $shift;?></td>
									<td class="text-right"><?php echo number_format( $data['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $data['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $data['Concessionary'] );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td>Less: Ticket Allocations</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php foreach( $ticket_balance['ticket_allocations']['teller'] as $shift => $data ): ?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $shift;?></td>
									<td class="text-right"><?php echo number_format( $data['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $data['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $data['Concessionary'] );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td>Less: Ticket Transfer</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php foreach( $ticket_balance['ticket_transfers'] as $unit => $data ): ?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $unit;?></td>
									<td class="text-right"><?php echo number_format( $data['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $data['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $data['Concessionary'] );?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td>Less: Returned to AFCS</td>
									<td class="text-right"><?php echo number_format( $ticket_balance['ticket_turnovers']['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $ticket_balance['ticket_turnovers']['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $ticket_balance['ticket_turnovers']['Concessionary'] );?></td>
								</tr>
								<tr>
									<td>Less: Issued to Passenger</td>
									<td class="text-right"></td>
									<td class="text-right"></td>
									<td class="text-right"></td>
								</tr>
								<?php if( ! empty( $ticket_balance['other_deductions'] ) ):?>
								<tr>
									<td>Less: Others</td>
									<td></td>
									<td></td>
									<td></td>
								</tr>
								<?php foreach( $ticket_balance['other_deductions'] as $item => $data ): ?>
								<tr>
									<td style="padding-left: 3em;"><?php echo $item;?></td>
									<td class="text-right"><?php echo number_format( $data['SJT'] );?></td>
									<td class="text-right"><?php echo number_format( $data['SVC'] );?></td>
									<td class="text-right"><?php echo number_format( $data['Concessionary'] );?></td>
								</tr>
								<?php endforeach;?>
								<?php endif;?>
								<tr>
									<td>Balance per Book</td>
									<td class="text-right"><?php echo number_format( $ticket_balance['ending_balance']['SJT']['system'] );?></td>
									<td class="text-right"><?php echo number_format( $ticket_balance['ending_balance']['SVC']['system'] );?></td>
									<td class="text-right"><?php echo number_format( $ticket_balance['ending_balance']['Concessionary']['system'] );?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Ticket Breakdown -->
				<div class="col-sm-6 col-md-4 col-lg-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">Ticket Breakdown</h3>
						</div>
						<table class="table table-condensed table-bordered">
							<thead>
								<tr>
									<th>Item</th>
									<th class="text-center">Quantity</th>
									<th class="text-center">Factor</th>
									<th class="text-center">Total</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="4" class="text-center">SJT Ticket</td>
								</tr>
								<?php foreach( $ticket_breakdown['SJT'] as $item => $data ):?>
								<tr>
									<td><?php echo $item;?></td>
									<td class="text-center"><?php echo number_format( $data['quantity'] ).' '.$data['unit'];?></td>
									<td class="text-center"><?php echo 'x'.number_format( $data['factor'] ); ?></td>
									<td class="text-right"><?php echo number_format( $data['base_quantity'] ); ?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td class="text-center">Total Count</td>
									<td class="text-center"></td>
									<td class="text-center"></td>
									<td class="text-right"><?php echo number_format( $ticket_breakdown['totals']['SJT'] ); ?></td>
								</tr>
								<tr>
									<td colspan="4"></td>
								</tr>
								<tr>
									<td colspan="4" class="text-center">SVC Ticket</td>
								</tr>
								<?php foreach( $ticket_breakdown['SVC'] as $item => $data ):?>
								<tr>
									<td><?php echo $item;?></td>
									<td class="text-center"><?php echo number_format( $data['quantity'] ).' '.$data['unit'];?></td>
									<td class="text-center"><?php echo 'x'.number_format( $data['factor'] ); ?></td>
									<td class="text-right"><?php echo number_format( $data['base_quantity'] ); ?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td class="text-center">Total Count</td>
									<td class="text-center"></td>
									<td class="text-center"></td>
									<td class="text-right"><?php echo number_format( $ticket_breakdown['totals']['SVC'] ); ?></td>
								</tr>
								<tr>
									<td colspan="4"></td>
								</tr>
								<tr>
									<td colspan="4" class="text-center">Concessionary Ticket</td>
								</tr>
								<?php foreach( $ticket_breakdown['Concessionary'] as $item => $data ):?>
								<tr>
									<td><?php echo $item;?></td>
									<td class="text-center"><?php echo number_format( $data['quantity'] ).' '.$data['unit'];?></td>
									<td class="text-center"><?php echo 'x'.number_format( $data['factor'] ); ?></td>
									<td class="text-right"><?php echo number_format( $data['base_quantity'] ); ?></td>
								</tr>
								<?php endforeach;?>
								<tr>
									<td class="text-center">Total Count</td>
									<td class="text-center"></td>
									<td class="text-center"></td>
									<td class="text-right"><?php echo number_format( $ticket_breakdown['totals']['Concessionary'] ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>