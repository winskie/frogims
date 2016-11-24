<!DOCTYPE html>
<html lang="en">
<head>
	<title>Delivery Receipt</title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
</head>
<body>
	<div class="container-fluid" style="width: 8.5in;">
		<div style="text-align: center;">
			<span>Light Rail Transit Authority</span><br>
			<span>FARE REVENUE OPERATIONS GROUP</span><br>
			<span>Ticket Management and Sales Collection Unit</span><br>
			<span>Ticket Inventory Management Team</span><br>
		</div>
		<div class="text-center">
			<h1>DELIVERY RECEIPT</h1>
		</div>
		<table style="width: 100%;">
			<tr>
				<td>Delivered to:</td>
				<td><?php echo $transfer_item['recipient_name'];?></td>
				<td>DR No.:</td>
				<td><?php echo $transfer_item['id'];?></td>
			</tr>
			<tr>
				<td>Address:</td>
				<td><?php echo $transfer_item['destination_name'];?></td>
				<td>Date:</td>
				<td><?php echo date( 'M d, Y', strtotime( $transfer_item['transfer_datetime'] ) );?></td>
			</tr>
		</table>
		<table class="table table-bordered table-condensed">
			<tr>
				<th class="text-center">Item No.</th>
				<th class="text-center">Quantity</th>
				<th class="text-center">Unit</th>
				<th>Articles</th>
				<th>Remarks</th>
			</tr>
			<?php
			$counter = 1;
			foreach( $transfer_item['items'] as $item ):
			?>
			<tr>
				<td class="text-center"><?php echo $counter; $counter++; ?></td>
				<td class="text-center"><?php echo number_format( $item['quantity'] );?></td>
				<td class="text-center"><?php echo $item['item_unit'];?></td>
				<td><?php echo $item['item_description'];?></td>
				<td><?php echo $item['remarks'];?></td>
			</tr>
			<?php endforeach;?>
		</table>
		<div class="text-center">
			<span style="font-style:italic;">********** nothing follows **********</span>
		</div>

		<div class="row">
			<div class="col-sm-5">
				<span>Prepared by:</span><br>
				<div class="text-center" style="vertical-align: bottom; font-weight: bold; min-height: 2em;"><?php echo $PREPARED_BY;?></div>
				<div class="text-center" style="border-top: 1px solid black;">
					<?php echo $PREPARED_BY_POSITION ? $PREPARED_BY_POSITION : 'Name and Position'; ?>
				</div>
			</div>
			<div class="col-sm-5 col-sm-offset-2">
				<span>Received by:</span><br>
				<div class="text-center" style="font-weight: bold; min-height: 2em;"></div>
				<div class="text-center" style="border-top: 1px solid black;">
					Name / Date / Time
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-5">
				<span>Checked by:</span><br>
				<div class="text-center" style="font-weight: bold; min-height: 2em;"><?php echo $CHECKED_BY;?></div>
				<div class="text-center" style="border-top: 1px solid black;">
					<?php echo $CHECKED_BY_POSITION ? $CHECKED_BY_POSITION : 'Name and Position'; ?>
				</div>
			</div>
		</div>
		<hr>
		<div class="text-center">
			<h1>GATE PASS</h1>
		</div>
		<div class="row">
			<div class="col-sm-5">
				<span>Issued by:</span><br>
				<div class="text-center" style="vertical-align: bottom; font-weight: bold; min-height: 2em;"><?php echo $ISSUED_BY;?></div>
				<div class="text-center" style="border-top: 1px solid black;">
					<?php echo $ISSUED_BY_POSITION ? $ISSUED_BY_POSITION : 'Name and Position'; ?>
				</div>
			</div>
			<div class="col-sm-5 col-sm-offset-2">
				<span>Approved for release::</span><br>
				<div class="text-center" style="font-weight: bold; min-height: 2em;"><?php echo $APPROVED_BY;?></div>
				<div class="text-center" style="border-top: 1px solid black;">
					<?php echo $APPROVED_BY_POSITION ? $APPROVED_BY_POSITION : 'Name and Position'; ?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-5">
				<span>Noted by:</span><br>
				<div class="text-center" style="font-weight: bold; min-height: 2em;"></div>
				<div class="text-center" style="border-top: 1px solid black;">
					Civil Security Officer
				</div>
			</div>
			<div class="col-sm-5 col-sm-offset-2">
				<span>Guard on duty:</span><br>
				<div class="text-center" style="font-weight: bold; min-height: 2em;"></div>
				<div class="text-center" style="border-top: 1px solid black;">
					Name / Date / Time
				</div>
			</div>
		</div>
	</div>
</body>
</html>
