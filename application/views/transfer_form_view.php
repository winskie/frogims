<div class="modal-body">	
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">Transfer Details</h3>
		</div>
		<div class="panel-body">
			<div class="form-group col-lg-3">
				<label for="source">Source</label>
				<p class="form-control-static">{{ transfer.origin_name }}</p>
			</div>
			<div class="form-group col-lg-3">
				<label for="source">Destination</label>
				<p class="form-control-static">{{ transfer.destination_name }}</p>
			</div>
			<div class="form-group col-lg-3">
				<label for="transfer_datetime">Date of Transfer</label>
				<p class="form-control-static">{{ transfer.transfer_datetime }}</p>
			</div>
			<div class="form-group col-lg-3">
				<label for="transfer_datetime">Status</label>
				<p class="form-control-static">{{ transfer.transfer_status }}</p>
			</div>
		</div>
		<table class="table table-bordered">
			<thead>
				<tr class="info">
					<th class="text-center vert-middle" rowspan="2">Item</th>
					<th class="text-right">Quantity</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="col-lg-6">{{ transfer.item_name }}</td>
					<td class="text-right col-lg-2">{{ transfer.transfer_quantity | number }}</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="panel panel-default" ng-show="showReceiptDetails">
		<div class="panel-heading">
			<h3 class="panel-title">Receipt Details</h3>
		</div>
		<div class="panel-body">
			<div class="form-group col-sm-4">
				<label class="control-label">Date of Receipt</label>
				<p class="form-control-static">{{ transfer.receipt_datetime }}</p>
			</div>
			
			<div class="form-group col-sm-4">
				<label class="control-label">Bearer</label>
				<p class="form-control-static">{{ transfer.sender_name }}</p>
			</div>
			
			<div class="form-group col-sm-4">
				<label class="control-label">Received by</label>
				<p class="form-control-static">{{ transfer.recipient_name }}</p>
			</div>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button class="btn btn-default" type="button" ng-click="close()">Close</button>
</div>