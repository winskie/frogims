<div  ng-if="checkPermissions( 'transferValidations', 'view' )">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Transfer #{{ transferItem.id }}</h3>
		</div>
		<div class="panel-body">
			<form class="form-horizontal">
				<div class="row">
					<div class="col-sm-5">
						<!-- Source -->
						<div class="form-group">
							<label class="control-label col-sm-3">Source</label>
							<div class="col-sm-9">
								<p class="form-control-static">{{ transferItem.origin_name }}</p>
							</div>
						</div>

						<!-- Destination -->
						<div class="form-group">
							<label class="control-label col-sm-3">Destination</label>
							<div class="col-sm-8">
								<p class="form-control-static">{{ transferItem.destination_name }}</p>
							</div>
						</div>
					</div>

					<div class="col-sm-4">
						<!-- Sweeper -->
						<div class="form-group">
							<label class="control-label col-sm-4">Delivered by</label>
							<div class="col-sm-7">
								<p class="form-control-static">{{ transferItem.sender_name }}</p>
							</div>
						</div>
						<!-- Sweeper -->
						<div class="form-group">
							<label class="control-label col-sm-4">Received by</label>
							<div class="col-sm-7">
								<input type="text" class="form-control"
										ng-model="transferItem.validation.transval_receipt_sweeper"
										ng-model-options="{ debounce: 500 }"
										typeahead-editable="true"
										uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
							</div>
						</div>
					</div>

					<div class="col-sm-3">
						<!-- Transfer Status -->
						<div class="form-group">
							<label class="control-label col-sm-4">Status</label>
							<p class="form-control-static col-sm-7">{{ lookup( 'transferStatus', transferItem.transfer_status ) }}</p>
						</div>

						<!-- Date of Transfer -->
						<div class="form-group">
							<label class="control-label col-sm-4">Date</label>
							<div class="col-sm-7">
								<p class="form-control-static">{{ transferItem.transfer_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
							</div>
						</div>
					</div>
				</div>
				<div class="text-right">
					<button type="button" class="btn btn-success" ui-sref="main.store({ activeTab: 'transferValidations' })">Validate</button>
					<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'transferValidations' })">Returned</button>
				</div>
			</div>
		</form>
	</div>

	<div class="panel panel-default" style="max-height: 300px; overflow-y: auto;">
		<div class="panel-heading">
			<h3 class="panel-title pull-left">Transfer Items</h3>
			<div class="pull-right col-sm-12 col-md-3" ng-if="data.showAllocationItemEntry && ( transferItem.transfer_status == <?php echo TRANSFER_PENDING;?> )">
				<div class="input-group">
					<span class="input-group-addon">Allocation ID</span>
					<input type="text" class="form-control text-right"
						ng-model="input.allocation"
						ng-keypress="addAllocationItems()">
				</div>
			</div>
			<div class="clearfix"></div>
		</div>
		<table class="table table-condensed">
			<thead>
				<tr>
					<th class="text-center" style="width: 50px;">Row</th>
					<th class="text-left">Item</th>
					<th class="text-center" style="width: 100px;">Quantity</th>
					<th class="text-center" style="width: 100px;"
							ng-if="['receipt', 'externalReceipt', 'view' ].indexOf( data.editMode ) != -1">Received</th>
					<th class="text-left">Category</th>
					<th class="text-left">Remarks</th>
					<th class="text-center" ng-if="[ 'view', 'receipt' ].indexOf( data.editMode ) == -1">Void</th>
				</tr>
			</thead>
			<tbody>
				<tr ng-repeat="row in transferItem.items"
						ng-class="{
							danger: row.transferItemVoid || ( [ <?php echo implode( ', ', array( TRANSFER_ITEM_VOIDED, TRANSFER_ITEM_CANCELLED ) );?> ].indexOf( row.transfer_item_status ) != -1 ),
							deleted: ( [ <?php echo implode( ', ', array( TRANSFER_ITEM_VOIDED, TRANSFER_ITEM_CANCELLED ) );?> ].indexOf( row.transfer_item_status ) != -1 )
						}">
					<td class="text-center">{{ $index + 1 }}</td>
					<td class="text-left">{{ row.item_name }}</td>
					<td class="text-center">{{ row.quantity | number }}</td>
					<td class="text-center" ng-if="['receipt', 'externalReceipt', 'view' ].indexOf( data.editMode ) != -1">
						<input type="number" class="form-control"
							ng-model="row.quantity_received"
							ng-if="data.editMode != 'view'">
						<span ng-if="data.editMode == 'view'">{{ row.quantity_received == null ? '---' : ( row.quantity_received | number ) }}</span>
					</td>
					<td class="text-left">{{ row.category_name ? row.category_name : '- None -' }}</td>
					<td class="text-left">{{ row.remarks }}</td>
					<td class="text-center" ng-if="[ 'view', 'receipt' ].indexOf( data.editMode ) == -1">
						<a href
								ng-if="row.transfer_item_status == <?php echo TRANSFER_ITEM_SCHEDULED; ?> && row.id == undefined"
								ng-click="removeTransferItem( row )">
							<i class="glyphicon glyphicon-remove-circle"></i>
						</a>
						<input type="checkbox" value="{{ row.id }}"
								ng-if="[ <?php echo implode( ', ', array( TRANSFER_ITEM_SCHEDULED, TRANSFER_ITEM_APPROVED ) );?> ].indexOf( row.transfer_item_status ) != -1 && row.id"
								ng-model="row.transferItemVoid">
					</td>
				</tr>
				<tr ng-if="!transferItem.items.length">
					<td colspan="7" class="text-center bg-warning">
						No transfer item
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Receipt information -->
	<div class="panel panel-default" ng-if="['view', 'receipt', 'externalReceipt'].indexOf( data.editMode ) != -1">
		<div class="panel-heading">
			<h3 class="panel-title">Receipt Information</h3>
		</div>
		<div class="panel-body">
			<div class="row">
				<form class="form-horizontal">
					<div class="form-group col-sm-5">
						<label class="control-label col-sm-5">Date of Receipt</label>
						<p class="form-control-static">{{ transferItem.receipt_datetime ? ( transferItem.receipt_datetime | date: 'yyyy-MM-dd HH:mm:ss' ) : 'Pending receipt' }}</p>
					</div>
					<div class="form-group col-sm-6">
						<label class="control-label col-sm-5">Recipient</label>
						<div class="col-sm-6">
							<p class="form-control-static">{{ transferItem.recipient_name ? transferItem.recipient_name : 'Pending receipt' }}</p>
						</div>
					</div>
				</form>
			</div>
			<div class="text-right">
				<button type="button" class="btn btn-success" ui-sref="main.store({ activeTab: 'transferValidations' })">Validate</button>
				<button type="button" class="btn btn-danger" ui-sref="main.store({ activeTab: 'transferValidations' })">Mark as Disputed</button>
			</div>
		</div>

	</div>

	<!-- Form buttons -->
	<div class="text-right">
		<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'transferValidations' })">Close</button>
	</div>
</div>

<div ng-if="! checkPermissions( 'transferValidations', 'view' )">
	<h1>Access Denied</h1>
	<p>You are not authorized to view this page. If you believe that this is incorrect please contact your system administrator.</p>
</div>