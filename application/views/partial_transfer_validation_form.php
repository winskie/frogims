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
							<div class="col-sm-6">

								<!-- Date of Transfer -->
								<div class="form-group">
									<label class="control-label col-sm-4">Date/Time</label>
									<div class="col-sm-8">
										<p class="form-control-static">{{ transferItem.transfer_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
									</div>
								</div>

								<!-- Source -->
								<div class="form-group">
									<label class="control-label col-sm-4">Source</label>
									<div class="col-sm-8">
										<p class="form-control-static">{{ transferItem.origin_name }}</p>
									</div>
								</div>

								<!-- Destination -->
								<div class="form-group">
									<label class="control-label col-sm-4">Destination</label>
									<div class="col-sm-8">
										<p class="form-control-static">{{ transferItem.destination_name }}</p>
									</div>
								</div>
							</div>

							<div class="col-sm-6">
								<!-- Transfer Status -->
								<div class="form-group">
									<label class="control-label col-sm-4">Transfer Status</label>
									<p class="form-control-static col-sm-8">{{ lookup( 'transferStatus', transferItem.transfer_status ) }}</p>
								</div>

								<!-- Receipt Status -->
								<div class="form-group">
									<label class="control-label col-sm-4">Receipt Status</label>
									<p class="form-control-static col-sm-8" ng-switch on="transferItem.validation.transval_receipt_status != null">
										<span ng-switch-when="true">
											{{ lookup( 'transferValidationReceiptStatus', transferItem.validation.transval_receipt_status ) }}, {{ transferItem.validation.transval_receipt_datetime }}
										</span>
										<span ng-switch-default>Pending</span>
									</p>
								</div>

								<!-- Sweeper -->
								<!--
								<div class="form-group">
									<label class="control-label col-sm-4">Delivered by</label>
									<div class="col-sm-8">
										<p class="form-control-static">{{ transferItem.sender_name }}</p>
									</div>
								</div>
								-->

								<!-- Sweeper -->
								<div class="form-group">
									<label class="control-label col-sm-4">Received by</label>
									<div class="col-sm-8" ng-switch on="( transferItem.validation.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_VALIDATED; ?> )
											&& ( transferItem.transfer_status == <?php echo TRANSFER_RECEIVED;?> )">
										<input type="text" class="form-control"
												ng-switch-when="false"
												ng-model="transferItem.validation.transval_receipt_sweeper"
												ng-model-options="{ debounce: 500 }"
												typeahead-editable="true"
												uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
										<p ng-switch-default class="form-control-static">{{ transferItem.validation.transval_receipt_sweeper }}</p>

									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-sm-12 col-md-3 col-lg-2">
						<button type="button" class="btn btn-block btn-success"
								ng-if="( transferItem.validation.transval_receipt_status != <?php echo TRANSFER_VALIDATION_RECEIPT_VALIDATED;?> )"
								ng-click="validateReceipt()">Validate Receipt
						</button>
						<button type="button" class="btn btn-block btn-default"
								ng-disabled=""
								ng-if="( transferItem.validation.transval_receipt_status != <?php echo TRANSFER_VALIDATION_RECEIPT_RETURNED;?> )
										&& ( transferItem.transfer_status != <?php echo TRANSFER_RECEIVED; ?> )"
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

							<div class="col-sm-6">
								<!-- Receipt Date -->
								<div class="form-group">
									<label class="control-label col-sm-4">Date/Time</label>
									<p class="form-control-static col-sm-8">{{ transferItem.receipt_datetime ? ( transferItem.receipt_datetime | date: 'yyyy-MM-dd HH:mm:ss' ) : 'Pending receipt' }}</p>
								</div>

								<!-- Recipient -->
								<div class="form-group">
									<label class="control-label col-sm-4">Recipient</label>
									<p class="form-control-static col-sm-8">{{ transferItem.recipient_name ? transferItem.recipient_name : 'Pending receipt' }}</p>
								</div>
							</div>

							<div class="col-sm-6">
								<!-- Delivery Status -->
								<div class="form-group">
									<label class="control-label col-sm-4">Delivery Status</label>
									<p class="form-control-static col-sm-8" ng-switch on="transferItem.validation.transval_transfer_status != null">
										<span ng-switch-when="true">
											{{ lookup( 'transferValidationTransferStatus', transferItem.validation.transval_transfer_status ) }}, {{ transferItem.validation.transval_transfer_datetime }}
										</span>
										<span ng-switch-default>Pending</span>
									</p>
								</div>

								<!-- Sweeper -->
								<div class="form-group">
									<label class="control-label col-sm-4">Delivered by</label>
									<div class="col-sm-8">
										<input type="text" class="form-control col-sm-8"
												ng-model="transferItem.validation.transval_transfer_sweeper"
												ng-model-options="{ debounce: 500 }"
												typeahead-editable="true"
												uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-sm-12 col-md-3 col-lg-2">
						<button type="button" class="btn btn-block btn-success"
								ng-disabled="transferItem.validation.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_VALIDATED; ?>"
								ng-click="validateTransfer()">Validate Transfer
						</button>
						<button type="button" class="btn btn-block btn-danger"
								ng-disabled="transferItem.validation.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_DISPUTED; ?>"
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
					<th class="text-left">Remarks</th>
					<th class="text-left">Category</th>
					<th class="text-center" style="width: 100px;">Quantity</th>
					<th class="text-center" style="width: 100px;">Received</th>
					<th class="text-center" ng-if="[ 'view', 'receipt' ].indexOf( data.editMode ) == -1">Void</th>
				</tr>
			</thead>
			<tbody>
				<tr ng-repeat="row in transferItem.items"
						ng-class="{ 'bg-success': row.checked	}">
					<td class="text-center">{{ $index + 1 }}</td>
					<td class="text-left">{{ row.item_name }}</td>
					<td class="text-left">{{ row.remarks ? row.remarks : '---' }}</td>
					<td class="text-left">{{ row.category_name ? row.category_name : '---' }}</td>
					<td class="text-center">{{ row.quantity | number }}</td>
					<td class="text-center">{{ row.quantity_received == null ? '---' : ( row.quantity_received | number ) }}</td>
					<td class="text-center"><input type="checkbox" ng-model="row.checked"></td>
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
			ng-if="checkPermissions( 'transferValidations', 'complete' )"
			ng-click="markNotApplicable()">Validation Not Required
		</button>
		<button type="button" class="btn btn-success"
			ng-if="checkPermissions( 'transferValidations', 'complete' )"
			ng-click="markCompleted()">Mark as Completed
		</button>
		<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'transferValidations' })">Close</button>
	</div>
</div>

<div ng-if="! checkPermissions( 'transferValidations', 'view' )">
	<h1>Access Denied</h1>
	<p>You are not authorized to view this page. If you believe that this is incorrect please contact your system administrator.</p>
</div>