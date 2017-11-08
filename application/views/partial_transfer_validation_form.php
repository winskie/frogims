<div  ng-if="checkPermissions( 'transferValidations', 'view' )">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Transfer #{{ transferItem.id }}</h3>
		</div>
		<div class="panel-body">
			<form class="form-horizontal">
				<div class="row">
					<div class="col-sm-12 col-md-9 col-lg-10">
						<div class="row">
							<div class="col-sm-5">

								<!-- Date of Transfer -->
								<div class="form-group">
									<label class="control-label col-sm-5">Transfer Date/Time</label>
									<div class="col-sm-7">
										<p class="form-control-static">{{ transferItem.transfer_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
									</div>
								</div>

								<!-- Source -->
								<div class="form-group">
									<label class="control-label col-sm-5">Source</label>
									<div class="col-sm-7">
										<p class="form-control-static">{{ transferItem.origin_name }}</p>
									</div>
								</div>

								<!-- Destination -->
								<div class="form-group">
									<label class="control-label col-sm-5">Destination</label>
									<div class="col-sm-7">
										<p class="form-control-static">{{ transferItem.destination_name }}</p>
									</div>
								</div>

								<!-- Transfer Status -->
								<div class="form-group">
									<label class="control-label col-sm-5">Transfer Status</label>
									<p class="form-control-static col-sm-7">{{ transferItem.get( 'transferStatusName' ) }}</p>
								</div>
							</div>

							<div class="col-sm-7">
								<!-- Receipt Validation Status -->
								<div class="form-group" ng-if="transferItem.transfer_status != <?php echo TRANSFER_PENDING;?>
										&& transferItem.transfer_validation.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
									<label class="control-label col-sm-5">Receipt Validation Status</label>
									<p class="form-control-static col-sm-7" ng-switch on="transferItem.transfer_validation.transval_receipt_status != null">
										<span ng-switch-when="true">
											<i class="glyphicon glyphicon-ok text-success" ng-if="transferItem.transfer_validation.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_VALIDATED;?>"> </i>
											<i class="glyphicon glyphicon-repeat text-danger" ng-if="transferItem.transfer_validation.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_RETURNED;?>"> </i>
											{{ transferItem.transfer_validation.get( 'receiptStatus' ) }} on {{ transferItem.transfer_validation.transval_receipt_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}
										</span>
										<span ng-switch-default>Pending</span>
									</p>
								</div>

								<!-- Sweeper -->
								<div class="form-group" ng-if="transferItem.transfer_status != <?php echo TRANSFER_PENDING;?>
										&& transferItem.transfer_validation.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
									<label class="control-label col-sm-5">Validated by</label>
									<div class="col-sm-7" ng-switch on="transferItem.canValidateReceipt()">
										<input type="text" class="form-control"
												ng-switch-when="true"
												ng-model="transferItem.transfer_validation.transval_receipt_sweeper"
												ng-model-options="{ debounce: 500 }"
												ng-change="onRecipientChange()"
												typeahead-on-select="onRecipientChange()"
												typeahead-editable="true"
												uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
										<p ng-switch-default class="form-control-static">{{ transferItem.transfer_validation.transval_receipt_sweeper }}</p>
									</div>
								</div>

								<!-- Validation Status -->
								<div class="form-group">
									<label class="control-label col-sm-5">Validation Status</label>
									<p class="form-control-static col-sm-7" ng-switch on="transferItem.transfer_validation.transval_status != null">
										<span ng-switch-when="true">{{ transferItem.transfer_validation.get( 'validationStatus' ) }}</span>
										<span ng-switch-default>---</span>
									</p>
								</div>
							</div>
						</div>
					</div>

					<div class="col-sm-12 col-md-3 col-lg-2">
						<button type="button" class="btn btn-block btn-success"
								ng-if="transferItem.canValidateReceipt()"
								ng-click="validateReceipt()">Validate Receipt
						</button>
						<button type="button" class="btn btn-block btn-default"
								ng-disabled=""
								ng-if="transferItem.canReturn()"
								ng-click="markReturned()">Mark as Returned
						</button>
					</div>
				</div>
			</div>
		</form>
	</div>

	<!-- Receipt information -->
	<div class="panel panel-default" ng-if="['view', 'receipt', 'externalReceipt'].indexOf( data.editMode ) != -1">
		<div class="panel-heading">
			<h3 class="panel-title">Receipt Information</h3>
		</div>
		<div class="panel-body" ng-switch on="transferItem.transfer_status">
			<form class="form-horizontal" ng-switch-when="<?php echo TRANSFER_RECEIVED;?>">
				<div class="row">
					<div class="col-sm-12 col-md-9 col-lg-10">
						<div class="row">

							<div class="col-sm-5">
								<!-- Receipt Date -->
								<div class="form-group">
									<label class="control-label col-sm-5">Receipt Date/Time</label>
									<p class="form-control-static col-sm-7">{{ transferItem.receipt_datetime ? ( transferItem.receipt_datetime | date: 'yyyy-MM-dd HH:mm:ss' ) : 'Pending receipt' }}</p>
								</div>

								<!-- Recipient -->
								<div class="form-group">
									<label class="control-label col-sm-5">Recipient</label>
									<p class="form-control-static col-sm-7">{{ transferItem.recipient_name ? transferItem.recipient_name : 'Pending receipt' }}</p>
								</div>
							</div>

							<div class="col-sm-7" ng-if="transferItem.transfer_validation.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
								<!-- Delivery Validation Status -->
								<div class="form-group" ng-if="transferItem.transfer_validation.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
									<label class="control-label col-sm-5">Delivery Validation Status</label>
									<p class="form-control-static col-sm-7" ng-switch on="transferItem.transfer_validation.transval_transfer_status != null">
										<span ng-switch-when="true">
											<i class="glyphicon glyphicon-ok text-success" ng-if="transferItem.transfer_validation.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_VALIDATED;?>"> </i>
											<i class="glyphicon glyphicon-remove text-danger" ng-if="transferItem.transfer_validation.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_DISPUTED;?>"> </i>
											{{ transferItem.transfer_validation.get( 'transferStatus' ) }} on {{ transferItem.transfer_validation.transval_transfer_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}
										</span>
										<span ng-switch-default>Pending</span>
									</p>
								</div>

								<!-- Sweeper -->
								<div class="form-group" ng-if="transferItem.transfer_validation.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
									<label class="control-label col-sm-5">Validated by</label>
									<div class="col-sm-7" ng-switch on="transferItem.canValidateTransfer()">
										<input type="text" class="form-control"
												ng-switch-when="true"
												ng-model="transferItem.transfer_validation.transval_transfer_sweeper"
												ng-model-options="{ debounce: 500 }"
												ng-change="onTransfereeChange()"
												typeahead-on-select="onTransfereeChange()"
												typeahead-editable="true"
												uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
										<p class="form-control-static" ng-switch-default>{{ transferItem.transfer_validation.transval_transfer_sweeper }}</p>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-sm-12 col-md-3 col-lg-2" ng-if="( transferItem.transfer_validation.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?> )
							&& ( transferItem.transfer_validation.transval_status != <?php echo TRANSFER_VALIDATION_COMPLETED;?> )">

						<button type="button" class="btn btn-block btn-success"
								ng-if="transferItem.canValidateTransfer()"
								ng-click="validateTransfer()">Validate Transfer
						</button>
						<button type="button" class="btn btn-block btn-danger"
								ng-if="transferItem.canDispute()"
								ng-click="markDisputed()">Mark as Disputed
						</button>
					</div>
				</div>
			</form>
			<div class="text-center" ng-switch-default>Pending receipt</div>
		</div>
	</div>

	<!-- Transfer Items -->
	<div class="panel panel-default" style="max-height: 300px; overflow-y: auto;">
		<div class="panel-heading">
			<h3 class="panel-title">Transfer Items</h3>
		</div>
		<table class="table table-condensed">
			<thead>
				<tr>
					<th class="text-center" style="width: 50px;">Row</th>
					<th class="text-left">Item</th>
					<th class="text-left">Remarks</th>
					<th class="text-left">Category</th>
					<th class="text-center" style="width: 100px;">Quantity</th>
					<th class="text-center" style="width: 100px;">Received</th>
					<th class="text-center" ng-if="[ 'view', 'receipt' ].indexOf( data.editMode ) == -1">Void</th>
				</tr>
			</thead>
			<tbody>
				<tr ng-repeat="row in transferItem.items"
						ng-class="{ 'bg-success': row.checked,
								'text-danger': ( ( transferItem.transfer_status == <?php echo TRANSFER_RECEIVED;?> ) && ( row.quantity != row.quantity_received ) ),
								danger: ( [ <?php echo implode( ', ', array( TRANSFER_ITEM_VOIDED, TRANSFER_ITEM_CANCELLED ) );?> ].indexOf( row.transfer_item_status ) != -1 ),
								deleted: ( [ <?php echo implode( ', ', array( TRANSFER_ITEM_VOIDED, TRANSFER_ITEM_CANCELLED ) );?> ].indexOf( row.transfer_item_status ) != -1 ) }">
					<td class="text-center">{{ $index + 1 }}</td>
					<td class="text-left">{{ row.item_name }}</td>
					<td class="text-left">{{ row.remarks ? row.remarks : '---' }}</td>
					<td class="text-left">{{ row.cat_description ? row.cat_description : '---' }}</td>
					<td class="text-center">{{ row.quantity | number }}</td>
					<td class="text-center">
						<i class="glyphicon glyphicon-exclamation-sign text-danger"
								ng-if="transferItem.transfer_status == <?php echo TRANSFER_RECEIVED;?> && row.quantity != row.quantity_received"> </i>
						{{ row.quantity_received == null ? '---' : ( row.quantity_received | number ) }}</td>
					<td class="text-center">
						<input type="checkbox" ng-model="row.checked" ng-if="row.transfer_item_status != 4 && row.transfer_item_status != 5">
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

	<!-- Form buttons -->
	<div class="text-right">
		<button type="button" class="btn btn-default"
			ng-if="checkPermissions( 'transferValidations', 'complete' )
					&& transferItem.transfer_validation.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED; ?>"
			ng-click="markNotRequired()">Validation Not Required
		</button>
		<button type="button" class="btn btn-success"
			ng-if="transferItem.canCompleteValidation( true )"
			ng-disabled="!transferItem.canCompleteValidation()"
			ng-click="markCompleted()">Mark as Completed
		</button>
		<button type="button" class="btn btn-default"
			ng-if="transfer.canOpenValidation()"
			ng-click="markOngoing()">Mark as Ongoing
		</button>
		<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'transferValidations' })">Close</button>
	</div>
</div>

<div ng-if="! checkPermissions( 'transferValidations', 'view' )">
	<h1>Access Denied</h1>
	<p>You are not authorized to view this page. If you believe that this is incorrect please contact your system administrator.</p>
</div>