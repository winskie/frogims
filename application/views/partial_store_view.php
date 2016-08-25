<?php
$current_user = current_user();
?>
<div>
	<uib-tabset id="mainTabSet" active="activeTab">
		<!-- Inventory -->
		<uib-tab heading="Inventory" index="0" select="onTabSelect('inventory')">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left"> Inventory</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm" ng-click="updateInventory( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th>Item</th>
							<th>Group</th>
							<th>Description</th>
							<th>Unit</th>
							<th class="text-right">Actual</th>
							<th class="text-right">Reserved</th>
							<th class="text-right">Available</th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="item in data.items" ng-class="{info: currentItem == item}">
							<td>{{ item.item_name }}</td>
							<td>{{ item.item_group }}</td>
							<td>{{ item.item_description }}</td>
							<td class="text-center">{{ item.item_unit }}</td>
							<td class="text-right">{{ item.quantity | number }}</td>
							<td class="text-right">{{ item.reserved | number }}</td>
							<td class="text-right">{{ ( item.quantity - item.reserved ) | number }}</td>
						</tr>
						<tr ng-if="!data.items.length">
							<td colspan="7" class="text-center">No inventory items available</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>

		<!-- Transactions -->
		<uib-tab heading="Transactions Summary" index="1" select="onTabSelect('transactions')" ng-if="checkPermissions( 'transactions', 'view')">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Transactions Summary <span class="label label-default" ng-if="filters.transactions.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'transactions' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.transactions ? 'Hide' : 'Show' }} filters
						</button>
						<button class="btn btn-default btn-sm" ng-click="updateTransactions( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<!-- Filter panel -->
					<div class="row filter_panel" ng-show="filterPanels.transactions">
						<div class="col-sm-3 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Business Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.transactionsDate.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.transactions.date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'transactionsDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4">
							<div class="form-group">
								<label class="control-label">Item</label>
								<select class="form-control"
										ng-model="filters.transactions.item"
										ng-options="item as item.item_name for item in widgets.transactionsItems track by item.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-3">
							<div class="form-group">
								<label class="control-label">Transaction Type</label>
								<select class="form-control"
										ng-model="filters.transactions.type"
										ng-options="type as type.typeName for type in widgets.transactionsTypes track by type.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'transactions' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'transactions' )">Clear</button>
							</div>
						</div>
					</div>
					<table class="table table-hover">
						<thead>
							<tr>
								<th class="text-left">Date / Time</th>
								<th class="text-left">Item</th>
								<th class="text-left">Transaction Type</th>
								<th class="text-center">Transaction ID</th>
								<th class="text-center">Shift</th>
								<th class="text-right">Quantity</th>
								<th class="text-right">Balance</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="transaction in data.transactions">
								<td>{{ transaction.transaction_datetime }}</td>
								<td>{{ transaction.item_name }}</td>
								<td>{{ lookup( 'transactionTypes', '' + transaction.transaction_type ) }}</td>
								<td class="text-center">{{ transaction.transaction_id }}</td>
								<td class="text-center">{{ transaction.shift_num }}</td>
								<td class="text-right">{{ transaction.transaction_quantity | number }}</td>
								<td class="text-right">{{ transaction.current_quantity | number }}</td>
							</tr>
							<tr ng-show="!data.transactions.length">
								<td colspan="6" class="text-center">No transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="data.totals.transactions > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.transactions"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.transactions"
								ng-change="updateTransactions( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Transfer Validations-->
		<uib-tab index="2" select="onTabSelect('transferValidations')" ng-if="sessionData.currentStore.store_type == 3 && checkPermissions( 'transferValidations', 'view')">
			<uib-tab-heading>
				Transfer Validations <span ng-show="data.pending.transferValidations > 0" class="label label-danger label-as-badge">{{ data.pending.transferValidations }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Transfer Validations <span class="label label-default" ng-if="filters.transferValidations.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'transferValidations' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.transferValidations ? 'Hide' : 'Show' }} filters
						</button>
						<button class="btn btn-default btn-sm" ng-click="updateTransferValidations()">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<!-- Quick Search -->
					<div class="text-right clearfix">
						<div class="input-group quicksearch pull-right">
							<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
							<input type="text" class="form-control"
									ng-model="quicksearch.transferValidations"
									ng-keypress="loadRecord( $event, 'transferValidations' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.transferValidations">
						<div class="col-sm-6 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Date Sent</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.transferValidationsDateSent.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.transferValidations.dateSent" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'transferValidationsDateSent' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-6 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Date Received</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.transferValidationsDateReceived.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.transferValidations.dateReceived" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'transferValidationsDateReceived' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-6 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Source</label>
								<select class="form-control"
										ng-model="filters.transferValidations.source"
										ng-options="store as store.store_name for store in widgets.transferValidationsSources track by store.id">
								</select>
							</div>
						</div>

						<div class="col-sm-6 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Destination</label>
								<select class="form-control"
										ng-model="filters.transferValidations.destination"
										ng-options="store as store.store_name for store in widgets.transferValidationsDestinations track by store.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Validation Status</label>
								<select class="form-control"
										ng-model="filters.transferValidations.validationStatus"
										ng-options="status as status.statusName for status in widgets.transferValidationsStatus track by status.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'transferValidations' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'transferValidations' )">Clear</button>
							</div>
						</div>
					</div>

					<table class="table table-hover">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th>Source</th>
								<th>Source Validation</th>
								<th>Destination</th>
								<th>Destination Validation</th>
								<th>Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="transfer in data.transferValidations">
								<td class="text-center">
									{{ transfer.id }}
								</td>
								<td>
									{{ transfer.origin_name }}<br/>
									{{ transfer.transfer_datetime }}
								</td>
								<td ng-switch on="transfer.transval_receipt_status == null || transfer.transval_status == <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
									<div ng-class="{ 'text-success': transfer.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_VALIDATED; ?> }" ng-switch-when="false">
										<i class="glyphicon glyphicon-ok text-success" ng-if="transfer.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_VALIDATED;?>"> </i>
										<i class="glyphicon glyphicon-repeat text-danger" ng-if="transfer.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_RETURNED;?>"> </i>
										{{ transfer.transval_receipt_status ? lookup( 'transferValidationReceiptStatus', transfer.transval_receipt_status ) : 'Not yet validated' }}<br/>
										{{ transfer.transval_receipt_datetime }}<br />
										{{ transfer.transval_receipt_sweeper }}<br />
									</div>
									<span class="text-muted" ng-switch-default>---</span>
								</td>
								<td>
									{{ transfer.destination_name }}<br/>
									{{ transfer.receipt_datetime ? transfer.receipt_datetime : 'For receipt' }}
								</td>
								<td ng-switch on="transfer.transval_transfer_status == null || transfer.transval_status == <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
									<div ng-class="{ 'text-success': transfer.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_VALIDATED; ?>,
											'text-danger': transfer.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_DISPUTED;?> }" ng-switch-when="false">
										<i class="glyphicon glyphicon-ok text-success" ng-if="transfer.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_VALIDATED;?>"> </i>
										<i class="glyphicon glyphicon-remove text-danger" ng-if="transfer.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_DISPUTED;?>"> </i>
										{{ transfer.transval_transfer_status ? lookup( 'transferValidationTransferStatus', transfer.transval_transfer_status ) : '' }}<br/>
										{{ transfer.transval_transfer_datetime }}<br />
										{{ transfer.transval_transfer_sweeper }}<br />
									</div>
									<span class="text-muted" ng-switch-default>---</span>
								</td>
								<td>
									<i class="glyphicon glyphicon-transfer"> </i>
									<span>
										{{ lookup( 'transferStatus', transfer.transfer_status ) }}
									</span><br />
									<i class="glyphicon glyphicon-certificate"
											ng-class="{ 'status-completed': transfer.transval_status == <?php echo TRANSFER_VALIDATION_COMPLETED;?>,
													'status-ongoing': transfer.transval_status == <?php echo TRANSFER_VALIDATION_ONGOING;?>,
													'status-cancelled': transfer.transval_status == <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?> }"> </i>
									<span class="text-muted" ng-if="transfer.transval_status == null">---</span>
									<span ng-if="transfer.transval_status != null"
											ng-class="{ 'status-completed': transfer.transval_status == <?php echo TRANSFER_VALIDATION_COMPLETED;?>,
													'status-ongoing': transfer.transval_status == <?php echo TRANSFER_VALIDATION_ONGOING;?>,
													'status-cancelled': transfer.transval_status == <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?> }">
										{{ lookup( 'transferValidationStatus', transfer.transval_status ) }}
									</span>
								</td>
								<td class="text-right vert-top">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.transferValidation({ transferItem: transfer, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="checkPermissions( 'transferValidations', 'complete' )">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu">
											<li role="menuitem" ng-if="checkPermissions( 'transferValidations', 'complete' )">
												<a href ng-click="completeTransferValidation( transfer )"
														ng-if="transfer.transval_status != null
																&& transfer.transval_status != <?php echo TRANSFER_VALIDATION_COMPLETED; ?>
																&& transfer.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED; ?>">Complete
												</a>
												<a href ng-click="transferValidationOngoing( transfer )"
														ng-if="transfer.transval_status != null && transfer.transval_status != <?php echo TRANSFER_VALIDATION_ONGOING; ?>">Mark as Ongoing
												</a>
												<a href ng-click="transferValidationNotRequired( transfer )"
														ng-if="transfer.transval_status != <?php echo TRANSFER_VALIDATION_NOTREQUIRED; ?>">Validation not Required
												</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!data.transferValidations.length">
								<td colspan="7" class="text-center">No transfer transaction data available</td>
							</tr>
						</tbody>
					</table>

					<div class="text-center" ng-if="data.totals.transferValidations > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.transferValidations"
								items-per-page="filters.itemsPerPage"
								ng-model="pagination.transferValidations"
								ng-change="updateTransferValidations()">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Outgoing -->
		<uib-tab index="3" select="onTabSelect('transfers')" ng-if="checkPermissions( 'transfers', 'view')">
			<uib-tab-heading>
				Outgoing <span ng-show="data.pending.transfers > 0" class="label label-danger label-as-badge">{{ data.pending.transfers }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Transfers <span class="label label-default" ng-if="filters.transfers.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'transfers' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.transfers ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<span ng-if="checkPermissions( 'transfers', 'edit' )">
							<button class="btn btn-primary btn-sm" ui-sref="main.transfer({ editMode: 'transfer' })">
								<i class="glyphicon glyphicon-plus"></i> New transfer
							</button>&nbsp;
						</span>
						<button class="btn btn-default btn-sm" ng-click="updateTransfers( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<!-- Quick Search -->
					<div class="text-right clearfix">
						<div class="input-group quicksearch pull-right">
							<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
							<input type="text" class="form-control"
									ng-model="quicksearch.transfers"
									ng-keypress="loadRecord( $event, 'transfers' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="filter_panel row" ng-show="filterPanels.transfers">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Business Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.transfersDate.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.transfers.date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'transfersDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4">
							<div class="form-group">
								<label class="control-label">Destination</label>
								<select class="form-control"
										ng-model="filters.transfers.destination"
										ng-options="store as store.store_name for store in widgets.transfersDestinations track by store.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Status</label>
								<select class="form-control"
										ng-model="filters.transfers.status"
										ng-options="status as status.statusName for status in widgets.transfersStatus track by status.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'transfers' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'transfers' )">Clear</button>
							</div>
						</div>

					</div>

					<table class="table table-hover">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th class="text-left" style="width: 175px;">Date / Time</th>
								<th class="text-left">Destination / Items</th>
								<th class="text-center">Status</th>
								<th class="text-center" style="width: 175px;"></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="transfer in data.transfers">
								<td class="text-center vert-top">{{ transfer.id }}</td>
								<td class="vert-top">{{ transfer.transfer_status == <?php echo TRANSFER_PENDING;?> ? ( transfer.transfer_datetime | parseDate | date : 'yyyy-MM-dd' ) : transfer.transfer_datetime }}</td>
								<td class="vert-top">
									<div>
										{{ transfer.destination_name }} {{ transfer.sender_name ? 'thru ' + transfer.sender_name : null }}
									</div>
									<div class="panel panel-default">
										<table class="table table-condensed table-bordered table-details">
											<thead>
												<tr class="active">
													<th class="text-center">Item</th>
													<th class="text-center" style="width: 70px;">Quantity</th>
													<th class="text-center">Remarks</th>
												</tr>
											</thead>
											<tbody>
												<tr ng-repeat="item in transfer.items"
													ng-class="{
														danger: ( [ <?php echo implode( ', ', array( TRANSFER_ITEM_VOIDED, TRANSFER_ITEM_CANCELLED ) );?> ].indexOf( item.transfer_item_status ) != -1 ),
														deleted: ( [ <?php echo implode( ', ', array( TRANSFER_ITEM_VOIDED, TRANSFER_ITEM_CANCELLED ) );?> ].indexOf( item.transfer_item_status ) != -1 )
													}">
													<td>{{ item.item_description }}</td>
													<td class="text-right">
														<i class="glyphicon glyphicon-exclamation-sign text-danger"
																ng-if="transfer.transfer_status == <?php echo TRANSFER_RECEIVED;?> && item.quantity != item.quantity_received"> </i>
														{{ item.quantity | number }}
													<td class="col-sm-5">{{ item.remarks }}</td>
												</tr>
											</tbody>
										</table>
									</div>
								</td>
								<td class="text-center vert-top">
									<i class="glyphicon glyphicon-ok text-success" ng-if="transfer.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_VALIDATED;?>"> </i>
									{{ lookup( 'transferStatus', transfer.transfer_status ) }}
								</td>
								<td class="text-right vert-top">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.transfer({ transferItem: transfer, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="showActionList( 'transfers', transfer )">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="showActionList( 'transfers', transfer )">
											<li role="menuitem" ng-if="transfer.transfer_status == <?php echo TRANSFER_PENDING;?> && checkPermissions( 'transfers', 'approve' )">
												<a href ng-click="approveTransfer( transfer )">Approve</a>
											</li>
											<li role="menuitem" ng-if="( transfer.transfer_status == <?php echo TRANSFER_PENDING;?> && checkPermissions( 'transfers', 'edit' ) ) || ( transfer.transfer_status == <?php echo TRANSFER_APPROVED;?> && checkPermissions( 'transfers', 'approve' ) ) ">
												<a href ng-click="cancelTransfer( transfer )">Cancel</a>
											</li>
											<li role="menuitem" ng-if="transfer.transfer_status == <?php echo TRANSFER_PENDING;?> && checkPermissions( 'transfers', 'edit' )">
												<a ui-sref="main.transfer({ transferItem: transfer, editMode: 'transfer' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!data.transfers.length">
								<td colspan="5" class="text-center">No transfer transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="data.totals.transfers > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.transfers"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.transfers"
								ng-change="updateTransfers( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Incoming -->
		<uib-tab index="4" select="onTabSelect('receipts')" ng-if="checkPermissions( 'transfers', 'view')">
			<uib-tab-heading>
				Incoming <span ng-show="data.pending.receipts > 0" class="label label-danger label-as-badge">{{ data.pending.receipts }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Receipts <span class="label label-default" ng-if="filters.receipts.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'receipts' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.receipts ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<span ng-if="checkPermissions( 'transfers', 'edit' )">
							<button class="btn btn-primary btn-sm" ui-sref="main.transfer({ editMode: 'externalReceipt' })">
								<i class="glyphicon glyphicon-plus"></i> New receipt
							</button>&nbsp;
						</span>
						<button class="btn btn-default btn-sm" ng-click="updateReceipts( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<!-- Quick Search -->
					<div class="text-right clearfix">
						<div class="input-group quicksearch pull-right">
							<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
							<input type="text" class="form-control"
									ng-model="quicksearch.receipts"
									ng-keypress="loadRecord( $event, 'receipts' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.receipts">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Receipt Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.receiptsDate.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.receipts.date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'receiptsDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4">
							<div class="form-group">
								<label class="control-label">Source</label>
								<select class="form-control"
										ng-model="filters.receipts.source"
										ng-options="store as store.store_name for store in widgets.receiptsSources track by store.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Status</label>
								<select class="form-control"
										ng-model="filters.receipts.status"
										ng-options="status as status.statusName for status in widgets.receiptsStatus track by status.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'receipts' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'receipts' )">Clear</button>
							</div>
						</div>

					</div>

					<table class="table">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th class="text-left" style="width: 175px;">Date / Time</th>
								<th class="text-left">Source / Items</th>
								<th class="text-center">Status</th>
								<th class="text-center" style="width: 175px;"></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="receipt in data.receipts">
								<td class="text-center vert-top">{{ receipt.id }}</td>
								<td class="vert-top">{{ receipt.receipt_datetime }}</td>
								<td class="vert-top">
									<div>
										{{ receipt.origin_name }} <span class="text-muted">- sent last {{ receipt.transfer_datetime }}</span> thru {{ receipt.sender_name }}
									</div>
									<div class="panel panel-default">
										<table class="table table-condensed table-bordered table-details">
											<thead>
												<tr class="active">
													<th class="text-center">Item</th>
													<th class="text-center" style="width: 70px;">Declared</th>
													<th class="text-center" style="width: 70px;">Actual</th>
													<th class="text-center">Remarks</th>
												</tr>
											</thead>
											<tbody>
												<tr ng-repeat="item in receipt.items"
													ng-class="{
														danger: ( [ <?php echo implode( ', ', array( TRANSFER_ITEM_VOIDED, TRANSFER_ITEM_CANCELLED ) );?> ].indexOf( item.transfer_item_status ) != -1 ),
														deleted: ( [ <?php echo implode( ', ', array( TRANSFER_ITEM_VOIDED, TRANSFER_ITEM_CANCELLED ) );?> ].indexOf( item.transfer_item_status ) != -1 )
													}">
													<td>{{ item.item_description }}</td>
													<td class="text-right">{{ item.quantity | number }}</td>
													<td class="text-right">{{ item.quantity_received | number }}</td>
													<td>{{ item.remarks }}</td>
												</tr>
											</tbody>
										</table>
									</div>
								</td>
								<td class="text-center vert-top">
									<i class="glyphicon glyphicon-ok text-success" ng-if="receipt.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_VALIDATED;?>"> </i>
									<i class="glyphicon glyphicon-remove text-danger" ng-if="receipt.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_DISPUTED;?>"> </i>
									{{ lookup( 'receiptStatus', receipt.transfer_status ) }}
								</td>
								<td class="text-right vert-top">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.transfer({ transferItem: receipt, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="showActionList( 'receipts', receipt )">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="showActionList( 'receipts', receipt )">
											<li role="menuitem" ng-if="receipt.transfer_status == <?php echo TRANSFER_APPROVED;?> && checkPermissions( 'transfers', 'edit' )">
												<a href ng-click="receiveTransfer( receipt )">Quick receipt</a>
											</li>
											<li role="menuitem" ng-if="receipt.transfer_status == <?php echo TRANSFER_APPROVED;?> && checkPermissions( 'transfers', 'edit' )">
												<a ui-sref="main.transfer({ transferItem: receipt, editMode: 'receipt' })">Edit receipt...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!data.receipts.length">
								<td colspan="5" class="text-center">No receipt transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="data.totals.receipts > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.receipts"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.receipts"
								ng-change="updateReceipts( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Adjustments -->
		<uib-tab index="5" select="onTabSelect('adjustments')" ng-if="checkPermissions( 'adjustments', 'view' )">
			<uib-tab-heading>
				Adjustments <span ng-show="data.pending.adjustments > 0" class="label label-danger label-as-badge">{{ data.pending.adjustments }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Adjustments <span class="label label-default" ng-if="filters.adjustments.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'adjustments' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.adjustments ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<span ng-if="checkPermissions( 'adjustments', 'edit' )">
							<button class="btn btn-primary btn-sm" ui-sref="main.adjust({ editMode: 'edit' })">
								<i class="glyphicon glyphicon-plus"></i> New adjustment
							</button>&nbsp;
						</span>
						<button class="btn btn-default btn-sm" ng-click="updateAdjustments( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<!-- Quick Search -->
					<div class="text-right clearfix">
						<div class="input-group quicksearch pull-right">
							<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
							<input type="text" class="form-control"
									ng-model="quicksearch.adjustments"
									ng-keypress="loadRecord( $event, 'adjustments' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.adjustments">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Date Adjusted</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.adjustmentsDate.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.adjustments.date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'adjustmentsDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4">
							<div class="form-group">
								<label class="control-label">Item</label>
								<select class="form-control"
										ng-model="filters.adjustments.item"
										ng-options="item as item.item_name for item in widgets.adjustmentsItems track by item.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Status</label>
								<select class="form-control"
										ng-model="filters.adjustments.status"
										ng-options="status as status.statusName for status in widgets.adjustmentsStatus track by status.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'adjustments' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'adjustments' )">Clear</button>
							</div>
						</div>

					</div>

					<table class="table">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th class="text-left">Date / Time</th>
								<th class="text-left">Item</th>
								<th class="text-right">Adjusted Balance</th>
								<th class="text-right">Previous Balance</th>
								<th class="text-left">Reason</th>
								<th class="text-center">Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="adjustment in data.adjustments">
								<td class="text-center">{{ adjustment.id }}</td>
								<td class="text_left">{{ adjustment.adjustment_timestamp }}</td>
								<td class="text_left">{{ adjustment.item_name }}</td>
								<td class="text-right">{{ adjustment.adjusted_quantity | number }}</td>
								<td class="text-right">{{ adjustment.adjustment_status == 1 ? '---' : ( adjustment.previous_quantity | number ) }}</td>
								<td class="text-left">{{ adjustment.reason }}</td>
								<td class="text-center">{{ lookup( 'adjustmentStatus', adjustment.adjustment_status ) }}</td>
								<td>
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.adjust({ adjustmentItem: adjustment })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="showActionList( 'adjustments', adjustment)">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="showActionList( 'adjustments', adjustment)">
											<li role="menuitem" ng-if="checkPermissions( 'adjustments', 'approve' )">
												<a href ng-click="approveAdjustment( adjustment )">Approve</a>
											</li>
											<li role="menuitem" ng-if="checkPermissions( 'adjustments', 'edit' )">
												<a ui-sref="main.adjust({ adjustmentItem: adjustment, editMode: 'edit' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!data.adjustments.length">
								<td colspan="8" class="text-center">No adjustment transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="data.totals.adjustments > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.adjustments"
								items-per-page="filters.itemsPerPage"
								ng-model="pagination.adjustments"
								ng-change="updateAdjustments( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Mopping -->
		<uib-tab index="6" select="onTabSelect('collections')" ng-if="sessionData.currentStore.store_type == 2 && checkPermissions( 'collections', 'view')"> <!-- Production only -->
			<uib-tab-heading>
				Mopping Collection
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Mopping Collection <span class="label label-default" ng-if="filters.collections.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'collections' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.collections ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<span ng-if="checkPermissions( 'collections', 'edit' )">
							<button class="btn btn-primary btn-sm" ui-sref="main.mopping({ editMode: 'new' })">
								<i class="glyphicon glyphicon-plus"></i> New collection
							</button>&nbsp;
						</span>
						<button class="btn btn-default btn-sm" ng-click="updateCollections( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<!-- Quick Search -->
					<div class="text-right clearfix">
						<div class="input-group quicksearch pull-right">
							<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
							<input type="text" class="form-control"
									ng-model="quicksearch.collections"
									ng-keypress="loadRecord( $event, 'collections' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.collections">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Date Processed</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.collectionsProcessingDate.opened"
											min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
											ng-model="filters.collections.processingDate" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'collectionsProcessingDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Business Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.collectionsBusinessDate.opened"
											min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
											ng-model="filters.collections.businessDate" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'collectionsBusinessDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'collections' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'collections' )">Clear</button>
							</div>
						</div>
					</div>

					<table class="table">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th class="text-left">Processing Date</th>
								<th class="text-center">Business Date</th>
								<th class="text-left">Processed Items</th>
								<th class="text-center"></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="collection in data.collections">
								<td class="text-center vert-top">{{ collection.id }}</td>
								<td class="text-left vert-top">{{ collection.processing_datetime }}<br />{{ collection.shift_num }}</td>
								<td class="text-center vert-top">{{ collection.business_date }}<br />{{ collection.cashier_shift_num }}</td>
								<td class="text-left">
									<div class="panel panel-default">
										<table class="table table-condensed table-bordered table-details">
											<thead>
												<tr class="active">
													<th>Item Description</th>
													<th style="width: 70px;">Quantity</th>
												</tr>
											</thead>
											<tbody>
												<tr ng-repeat="item in collection.items" ng-class="{ deleted: item.status == <?php echo MOPPING_ITEM_VOIDED;?> }">
													<td>{{ item.item_description }}</td>
													<td class="text-right">{{ item.quantity | number }}</td>
												</tr>
											</tbody>
										</table>
									</div>
								</td>
								<td class="vert-top">
									<div class="btn-group btn-block" uib-dropdown>
										<button id="split-button" type="button" class="btn btn-default" ui-sref="main.mopping({ moppingItem: collection, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="showActionList( 'collections', collection )">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="showActionList( 'collections', collection )">
											<li role="menuitem"><a ui-sref="main.mopping({ moppingItem: collection, editMode: 'edit' })">Edit Collection...</a></li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="data.collections.length == 0">
								<td colspan="5" class="text-center">No mopping collection data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="data.totals.collections > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.collections"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.collections"
								ng-change="updateCollections( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Allocation -->
		<uib-tab index="7" select="onTabSelect('allocations')" ng-if="sessionData.currentStore.store_type == 4 && checkPermissions( 'allocations', 'view')"> <!-- Cashroom only -->
			<uib-tab-heading>
				Allocations <span ng-show="data.pending.allocations > 0" class="label label-danger label-as-badge">{{ data.pending.allocations }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Allocations <span class="label label-default" ng-if="filters.allocations.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'allocations' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.allocations ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<span ng-if="checkPermissions( 'allocations', 'edit' )">
							<button class="btn btn-primary btn-sm" ui-sref="main.allocation({ editMode: 'new' })">
								<i class="glyphicon glyphicon-plus"></i> New allocation
							</button>&nbsp;
						</span>
						<button class="btn btn-default btn-sm" ng-click="updateAllocations( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<!-- Quick Search -->
					<div class="text-right clearfix">
						<div class="input-group quicksearch pull-right">
							<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
							<input type="text" class="form-control"
									ng-model="quicksearch.allocations"
									ng-keypress="loadRecord( $event, 'allocations' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.allocations">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.allocationsDate.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.allocations.date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'allocationsDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-3">
							<div class="form-group">
								<label class="control-label">Assignee Type</label>
								<select class="form-control"
										ng-model="filters.allocations.assigneeType"
										ng-options="type as type.typeName for type in widgets.allocationsAssigneeTypes track by type.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Status</label>
								<select class="form-control"
										ng-model="filters.allocations.status"
										ng-options="status as status.statusName for status in widgets.allocationsStatus track by status.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'allocations' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'allocations' )">Clear</button>
							</div>
						</div>

					</div>

					<table class="table">
						<thead>
							<tr>
								<th class="row-flag"></th>
								<th class="text-center">ID</th>
								<th class="text-left">Business Date</th>
								<th class="text-left">Allocated to</th>
								<th class="text-left">Allocation Details</th>
								<th class="text-center">Status</th>
								<th class="text-center"></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="row in data.allocations">
								<td class="row-flag" ng-class="lookup( 'allocationStatus', row.allocation_status).className"></td>
								<td class="text-center vert-top">{{ row.id }}</td>
								<td class="text-left vert-top">{{ row.business_date }}<br />{{ row.shift_num }}</td>
								<td class="text-left vert-top">{{ row.assignee ? ( row.assignee_type == 2 ? 'TVM# ' : '' ) + row.assignee : 'Not yet specified' }}<br />{{ row.assignee_type == 1 ? 'Station Teller' : 'Vending Machine' }}</td>
								<td class="text-left vert-top" ng-switch on="row.assignee_type">
									<div class="panel panel-default" ng-switch-when=1>
										<table class="table table-condensed table-bordered table-details">
											<thead>
												<tr class="active">
													<th>Item Description</th>
													<th style="width: 70px;">Initial</th>
													<th style="width: 70px;">Additional</th>
													<th style="width: 70px;">Remitted</th>
												</tr>
											</thead>
											<tbody>
												<tr ng-repeat="item in row.items">
													<td>{{ item.item_description }}</td>
													<td class="text-right">{{ item.allocation | number }}</td>
													<td class="text-right">{{ item.additional | number }}</td>
													<td class="text-right">{{ item.remitted | number }}</td>
												</tr>
											</tbody>
										</table>
									</div>
									<div class="panel panel-default" ng-switch-when=2>
										<table class="table table-condensed table-bordered table-details">
											<thead>
												<tr class="active">
													<th>Item Description</th>
													<th style="width: 70px;">Load</th>
													<th style="width: 70px;">Reject</th>
												</tr>
											</thead>
											<tbody>
												<tr ng-repeat="item in row.items">
													<td>{{ item.item_description }}</td>
													<td class="text-right">{{ item.additional | number }}</td>
													<td class="text-right">{{ item.remitted | number }}</td>
												</tr>
											</tbody>
										</table>
									</div>
								</td>
								<td class="text-center vert-top">{{ lookup( 'allocationStatus', row.allocation_status ).status }}</td>
								<td class="vert-top" ng-switch on="row.allocation_status">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.allocation({ allocationItem: row, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="showActionList( 'allocations', row )">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="showActionList( 'allocations', row )">
											<li role="menuitem" ng-if="row.allocation_status == <?php echo ALLOCATION_SCHEDULED;?> && checkPermissions( 'allocations', 'allocate' )">
												<a href="#" ng-click="allocateAllocation( row )">Allocate</a>
											</li>
											<li role="menuitem" ng-if="row.allocation_status == <?php echo ALLOCATION_ALLOCATED;?> && checkPermissions( 'allocations', 'complete' )">
												<a href="#" ng-click="completeAllocation( row )">Complete</a>
											</li>
											<li role="menuitem" ng-if="row.allocation_status == <?php echo ALLOCATION_SCHEDULED;?> && checkPermissions( 'allocations', 'edit' )">
												<a href="#" ng-click="cancelAllocation( row )">Cancel</a>
											</li>
											<li role="menuitem" ng-if="row.allocation_status != <?php echo ALLOCATION_REMITTED;?>
													&& row.allocation_status != <?php echo ALLOCATION_CANCELLED;?>
													&& checkPermissions( 'allocations', 'edit' )">
												<a ui-sref="main.allocation({ allocationItem: row, editMode: 'edit' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="data.allocations.length == 0">
								<td colspan="7" class="text-center">No allocation data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="data.totals.allocations > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.allocations"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.allocations"
								ng-change="updateAllocations( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Conversions -->
		<uib-tab index="8" select="onTabSelect('conversions')" ng-if="checkPermissions( 'conversions', 'view')">
			<uib-tab-heading>
					Conversions <span ng-show="data.pending.conversions > 0" class="label label-danger label-as-badge">{{ data.pending.conversions }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Conversions <span class="label label-default" ng-if="filters.conversions.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'conversions' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.conversions ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<span ng-if="checkPermissions( 'conversions', 'edit' )">
							<button class="btn btn-primary btn-sm" ui-sref="main.convert({ editMode: 'edit' })">
								<i class="glyphicon glyphicon-plus"></i> New conversion
							</button>&nbsp;
						</span>
						<button class="btn btn-default btn-sm" ng-click="updateConversions( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<!-- Quick Search -->
					<div class="text-right clearfix">
						<div class="input-group quicksearch pull-right">
							<span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
							<input type="text" class="form-control"
									ng-model="quicksearch.conversions"
									ng-keypress="loadRecord( $event, 'conversions' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.conversions">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Date Converted</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.conversionsDate.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.conversions.date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'conversionsDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-3">
							<div class="form-group">
								<label class="control-label">Input Item</label>
								<select class="form-control"
										ng-model="filters.conversions.inputItem"
										ng-options="item as item.item_name for item in widgets.conversionsItems track by item.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-3">
							<div class="form-group">
								<label class="control-label">Output Item</label>
								<select class="form-control"
										ng-model="filters.conversions.outputItem"
										ng-options="item as item.item_name for item in widgets.conversionsItems track by item.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'conversions' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'conversions' )">Clear</button>
							</div>
						</div>
					</div>

					<table class="table">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th class="text-left">Date / Time</th>
								<th class="text-left">Input Item</th>
								<th class="text-center">Input Qty</th>
								<th class="text-left">Output Item</th>
								<th class="text-center">Output Qty</th>
								<th class="text-left">Remarks</th>
								<th class="text-center">Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="conversion in data.conversions">
								<td class="text-center">{{ conversion.id }}</td>
								<td class="text-left">{{ conversion.conversion_datetime }}</td>
								<td class="text-left">{{ conversion.source_item_name }}</td>
								<td class="text-center">{{ conversion.source_quantity | number }}</td>
								<td class="text-left">{{ conversion.target_item_name }}</td>
								<td class="text-center">{{ conversion.target_quantity | number }}</td>
								<td class="text-left">{{ conversion.remarks }}</td>
								<td class="text-center">{{ lookup( 'conversionStatus', conversion.conversion_status ) }}</td>
								<td>
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.convert({ conversionItem: conversion, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="showActionList( 'conversions', conversion )">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="showActionList( 'conversions', conversion )">
											<li role="menuitem" ng-if="conversion.conversion_status == <?php echo CONVERSION_PENDING;?> && checkPermissions( 'conversions', 'approve' )">
												<a href ng-click="approveConversion( conversion )">Approve</a>
											</li>
											<li role="menuitem" ng-if="conversion.conversion_status == <?php echo CONVERSION_PENDING;?> && checkPermissions( 'conversions', 'edit' )">
												<a ui-sref="main.convert({ conversionItem: conversion, editMode: 'edit' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!data.conversions.length">
								<td colspan="8" class="text-center">No conversion transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="data.totals.conversions > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.conversions"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.conversions"
								ng-change="updateConversions( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

	</uib-tabset>
</div>