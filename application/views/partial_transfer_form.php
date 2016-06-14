<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">{{ data.title }}</h3>
	</div>
	<div class="panel-body">
		<form class="form-horizontal">
			<div class="row">
				<div class="col-sm-5">
					<!-- Source -->
					<div class="form-group">
						<label class="control-label col-sm-3">Source</label>
						<div class="input-group col-sm-8" ng-if="data.editMode == 'externalReceipt'">
							<input type="text" class="form-control" placeholder="Enter name of external source"
									ng-model="transferItem.origin_name">
						</div>
						<div class="col-sm-9" ng-if="data.editMode != 'externalReceipt'">
							<p class="form-control-static">{{ transferItem.origin_name }}</p>
						</div>
					</div>

					<!-- Destination -->
					<div class="form-group">
						<label class="control-label col-sm-3">Destination</label>
						<div class="input-group col-sm-8" ng-if="[ 'transfer', 'externalTransfer' ].indexOf( data.editMode ) != -1">
							<div class="input-group-btn">
								<button type="button" class="btn btn-default" ng-click="toggle( 'destination' )">
									<i class="glyphicon glyphicon-refresh"></i>
								</button>
							</div>
							<select class="form-control"
									ng-model="data.selectedDestination"
									ng-options="store.store_name for store in data.destinations track by store.id"
									ng-if="data.editMode == 'transfer'"
									ng-change="changeDestination()">
							</select>
							<input type="text" class="form-control" placeholder="Enter name of external destination"
									ng-model="transferItem.destination_name"
									ng-if="data.editMode == 'externalTransfer'">
						</div>
						<div class="col-sm-8" ng-if="[ 'transfer', 'externalTransfer' ].indexOf( data.editMode ) == -1">
							<p class="form-control-static">{{ transferItem.destination_name }}</p>
						</div>
					</div>
				</div>

				<div class="col-sm-4">


					<!-- Sweeper -->
					<div class="form-group">
						<label class="control-label col-sm-4">{{ data.sweeperLabel }}</label>
						<div class="col-sm-7" ng-if="[ 'transfer', 'externalTransfer', 'externalReceipt' ].indexOf( data.editMode ) != -1">
							<input type="text" class="form-control"
									ng-model="transferItem.sender_name"
									ng-model-options="{ debounce: 500 }"
									typeahead-editable="true"
									uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
						</div>
						<div class="col-sm-7" ng-if="[ 'transfer', 'externalTransfer', 'externalReceipt' ].indexOf( data.editMode ) == -1">
							<p class="form-control-static">{{ transferItem.sender_name }}</p>
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
						<div class="input-group col-sm-7" ng-if="[ 'transfer', 'externalTransfer', 'externalReceipt' ].indexOf( data.editMode ) != -1">
							<input type="text" class="form-control" uib-datepicker-popup="{{ data.transferDatepicker.format }}" is-open="data.transferDatepicker.opened"
								min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
								ng-model="transferItem.transfer_datetime" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
							<span class="input-group-btn">
								<button type="button" class="btn btn-default" ng-click="showDatePicker( 'transfer' )"><i class="glyphicon glyphicon-calendar"></i></button>
							</span>
						</div>
						<div class="col-sm-7" ng-if="[ 'transfer', 'externalTransfer', 'externalReceipt' ].indexOf( data.editMode ) == -1">
							<p class="form-control-static">{{ transferItem.transfer_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

<div class="panel panel-default" style="height: 300px; overflow-y: auto;">
	<div class="panel-heading">
		<h3 class="panel-title pull-left">Transfer Items</h3>
		<div class="pull-right" ng-if="mode == 'transfer'">
			<button class="btn btn-default btn-sm" ng-click="addTransferItem()">
				<i class="glyphicon glyphicon-plus"></i> Add item
			</button>
		</div>
		<div class="clearfix"></div>
	</div>
	<table class="table table-condensed">
		<thead>
			<tr>
				<th class="text-center" style="width: 50px;">Row</th>
				<th class="text-left">Item</th>
				<th class="text-left">Category</th>
				<th class="text-center" style="width: 100px;">Quantity</th>
				<th class="text-center" style="width: 100px;"
						ng-if="['receipt', 'externalReceipt', 'view' ].indexOf( data.editMode ) != -1">Received</th>
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
				<td class="text-left">{{ row.category_name ? row.category_name : '- None -' }}</td>
				<td class="text-center">{{ row.quantity | number }}</td>
				<td class="text-center" ng-if="['receipt', 'externalReceipt', 'view' ].indexOf( data.editMode ) != -1">
					<input type="number" class="form-control"
						ng-model="row.quantity_received"
						ng-if="data.editMode != 'view'">
					<span ng-if="data.editMode == 'view'">{{ row.quantity_received == null ? '---' : ( row.quantity_received | number ) }}</span>
				</td>
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
		</tbody>
	</table>
</div>

<!-- Input panel -->
<div class="panel panel-default" ng-if="[ 'transfer', 'externalTransfer', 'externalReceipt' ].indexOf( data.editMode ) != -1">
	<div class="panel-body row">
		<div class="form-group col-sm-3">
			<label class="control-label">Item</label>
			<select class="form-control"
					ng-model="input.inventoryItem"
					ng-options="item as item.item_name for item in data.inventoryItems track by item.id">
			</select>
		</div>
		<div class="form-group col-sm-3" ng-if="data.showCategory">
			<label class="control-label">Category</label>
			<select class="form-control"
					ng-model="input.itemCategory"
					ng-options="category as category.category for category in data.itemCategories track by category.id">
			</select>
		</div>
		<div class="form-group col-sm-2">
			<label class="control-label">Quantity</label>
			<input type="number" class="form-control"
					ng-model="input.quantity"
					ng-keypress="addTransferItem( $event )">
		</div>
		<div class="form-group" ng-class="{ 'col-sm-4': data.showCategory, 'col-sm-7': !data.showCategory }">
			<label class="control-label">Remarks</label>
			<input type="text" class="form-control"
					ng-model="input.remarks"
					ng-keypress="addTransferItem( $event )">
		</div>
	</div>
</div>

<!-- Receipt information -->
<div class="panel panel-default" ng-if="['receipt', 'externalReceipt'].indexOf( data.editMode ) != -1">
	<div class="panel-heading">
		<h3 class="panel-title">Receipt Information</h3>
	</div>
	<div class="panel-body row">
		<form class="form-horizontal">
			<div class="form-group col-sm-5">
				<label class="control-label col-sm-5">Date of Receipt</label>
				<div class="input-group col-sm-6" ng-if="[ 'receipt', 'externalReceipt' ].indexOf( data.editMode ) != -1">
					<input type="text" class="form-control" uib-datepicker-popup="{{ data.receiptDatepicker.format }}" is-open="data.receiptDatepicker.opened"
						min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
						ng-model="transferItem.receipt_datetime" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
					<span class="input-group-btn">
						<button type="button" class="btn btn-default" ng-click="showDatePicker( 'receipt' )"><i class="glyphicon glyphicon-calendar"></i></button>
					</span>
				</div>
				<div ng-if="[ 'receipt', 'externalReceipt' ].indexOf( data.editMode ) == -1">
					<p class="form-control-static">{{ transferItem.transfer_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
				</div>
			</div>
			<div class="form-group col-sm-6">
				<label class="control-label col-sm-5">Recipient</label>
				<div class="col-sm-6">
					<div ng-if="[ 'receipt', 'externalReceipt' ].indexOf( data.editMode ) != -1">
						<input type="text" class="form-control"
								ng-model="transferItem.recipient_name"
								typeahead-editable="false"
								uib-typeahead="user as user.full_name for user in data.sweepers | filter: $viewValue">
					</div>
					<div ng-if="[ 'receipt', 'externalReceipt' ].indexOf( data.editMode ) == -1">
						<p class="form-control-static">{{ transferItem.recipient_name }}</p>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<!-- Form buttons -->
<div class="text-right">
	<div ng-if="[ 'transfer', 'externalTransfer' ].indexOf( data.editMode ) != -1">
		<button type="button" class="btn btn-primary" ng-click="scheduleTransfer()">Schedule</button>
		<button type="button" class="btn btn-default" ng-click="approveTransfer()">Approve</button>
		<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'transfers'})">Cancel</button>
	</div>
	<div ng-if="['receipt', 'externalReceipt'].indexOf( data.editMode ) != -1">
		<button type="button" class="btn btn-primary" ng-click="receiveTransfer()">Receive</button>
		<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'transfers'})">Cancel</button>
	</div>
	<div ng-if="data.editMode == 'view'">
		<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'transfers'})">Close</button>
	</div>
</div>