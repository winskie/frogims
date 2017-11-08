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
						<button class="btn btn-default btn-sm" ng-click="switchInventoryView()" ng-if="( sessionData.currentStore.store_type == 4 || sessionData.currentStore.store_type == 2 )  && checkPermissions( 'shiftTurnovers', 'edit')">
							{{ data.inventoryViewLabel }}
						</button>
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
							<th class="text-center">Unit</th>
							<th class="text-right">Actual</th>
							<th class="text-right">Reserved</th>
							<th class="text-right">Available</th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="item in appData.items"
							ng-class="{ info: currentItem == item }">
							<td>{{ item.item_name }}</td>
							<td>{{ item.item_group }}</td>
							<td>{{ item.item_description }}</td>
							<td class="text-center">{{ item.item_unit }}</td>
							<td class="text-right">
								<span>
									{{ item.sti_beginning_balance ? ( ( item.sti_beginning_balance + item.movement ) | number ) : ( item.quantity === 0 ? '---' : ( item.quantity | number ) ) }}
								</span>
								 <span ng-if="data.inventoryView == 'system'">
									({{ item.quantity === 0 ? '---' : ( item.quantity | number ) }})
								</span>
							</td>
							<td class="text-right">
								{{ item.reserved === 0 ? '---' : ( item.reserved | number ) }}
							</td>
							<td class="text-right">
								<span>
									{{ ( ( item.sti_beginning_balance ? ( item.sti_beginning_balance + item.movement ) : item.quantity ) - item.reserved ) === 0 ? '---' : ( ( ( item.sti_beginning_balance ? ( item.sti_beginning_balance + item.movement ) : item.quantity ) - item.reserved ) | number ) }}
								</span>
								 <span ng-if="data.inventoryView == 'system'">
									({{ ( item.quantity - item.reserved ) === 0 ? '---' : ( ( item.quantity - item.reserved ) | number ) }})
								</span>
							</td>
						</tr>
						<tr ng-if="!appData.items.length">
							<td colspan="7" class="text-center">No inventory items available</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>

		<!-- Transactions -->
		<uib-tab heading="Transactions" index="1" select="onTabSelect('transactions')" ng-if="checkPermissions( 'transactions', 'view')">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Transactions Log <span class="label label-default" ng-if="filters.transactions.filtered">Filtered</span>
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

						<div class="col-sm-3">
							<div class="form-group">
								<label class="control-label">Item</label>
								<select class="form-control"
										ng-model="filters.transactions.item"
										ng-options="item as item.item_name for item in widgets.transactionsItems track by item.id">
								</select>
							</div>
						</div>

						<div class="col-sm-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label class="control-label">Transaction Type</label>
								<select class="form-control"
										ng-model="filters.transactions.type"
										ng-options="type as type.typeName for type in widgets.transactionsTypes track by type.id">
								</select>
							</div>
						</div>

						<div class="col-sm-3 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Shift</label>
								<select class="form-control"
										ng-model="filters.transactions.shift"
										ng-options="shift as shift.shift_num for shift in widgets.transactionsShifts track by shift.id">
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
								<th class="text-left">Category</th>
								<th class="text-center">Shift</th>
								<th class="text-right">Quantity</th>
								<th class="text-right">Balance</th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="transaction in appData.transactions">
								<td>{{ transaction.transaction_datetime }}</td>
								<td>{{ transaction.item_name }}</td>
								<td>{{ lookup( 'transactionTypes', '' + transaction.transaction_type ) }}</td>
								<td class="text-left">{{ transaction.cat_description }}</td>
								<td class="text-center">{{ transaction.shift_num }}</td>
								<td class="text-right">{{ transaction.transaction_quantity | number }}</td>
								<td class="text-right">{{ transaction.current_quantity | number }}</td>
							</tr>
							<tr ng-show="!appData.transactions.length">
								<td colspan="6" class="text-center">No transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.transactions > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.transactions"
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
				Transfer Validations <span ng-show="appData.pending.transferValidations > 0" class="label label-danger label-as-badge">{{ appData.pending.transferValidations }}</span>
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
						<div class="col-sm-6 col-md-3 col-lg-3">
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

						<div class="col-sm-6 col-md-3 col-lg-4">
							<div class="form-group">
								<label class="control-label">Source</label>
								<select class="form-control"
										ng-model="filters.transferValidations.source"
										ng-options="store as store.store_name for store in widgets.transferValidationsSources track by store.id">
								</select>
							</div>
							<div class="form-group">
								<label class="control-label">Destination</label>
								<select class="form-control"
										ng-model="filters.transferValidations.destination"
										ng-options="store as store.store_name for store in widgets.transferValidationsDestinations track by store.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-3">
							<div class="form-group">
								<label class="control-label">Category</label>
								<select class="form-control"
										ng-model="filters.transferValidations.category"
										ng-options="category as category.categoryName for category in widgets.transferValidationsCategories track by category.id">
								</select>
							</div>
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
								<th>Category</th>
								<th>Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="transfer in appData.transferValidations">
								<td class="text-center">
									{{ transfer.id }}
								</td>
								<td>
									{{ transfer.origin_name }}<br/>
									{{ transfer.transfer_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}
								</td>
								<td ng-switch on="transfer.transfer_validation.transval_receipt_status == null || transfer.transfer_validation.transval_status == <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
									<div ng-class="{ 'text-success': transfer.transfer_validation.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_VALIDATED; ?> }" ng-switch-when="false">
										<i class="glyphicon glyphicon-ok text-success" ng-if="transfer.transfer_validation.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_VALIDATED;?>"> </i>
										<i class="glyphicon glyphicon-repeat text-danger" ng-if="transfer.transfer_validation.transval_receipt_status == <?php echo TRANSFER_VALIDATION_RECEIPT_RETURNED;?>"> </i>
										{{ transfer.transfer_validation.transval_receipt_status ? transfer.transfer_validation.get( 'receiptStatus' ) : 'Not yet validated' }}<br/>
										{{ transfer.transfer_validation.transval_receipt_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}<br />
										{{ transfer.transfer_validation.transval_receipt_sweeper }}<br />
									</div>
									<span class="text-muted" ng-switch-default>---</span>
								</td>
								<td>
									{{ transfer.destination_name }}<br/>
									{{ transfer.receipt_datetime ? ( transfer.receipt_datetime | date: 'yyyy-MM-dd HH:mm:ss' ) : 'For receipt' }}
								</td>
								<td ng-switch on="transfer.transfer_validation.transval_transfer_status == null || transfer.transfer_validation.transval_status == <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?>">
									<div ng-class="{ 'text-success': transfer.transfer_validation.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_VALIDATED; ?>,
											'text-danger': transfer.transfer_validation.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_DISPUTED;?> }" ng-switch-when="false">
										<i class="glyphicon glyphicon-ok text-success" ng-if="transfer.transfer_validation.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_VALIDATED;?>"> </i>
										<i class="glyphicon glyphicon-remove text-danger" ng-if="transfer.transfer_validation.transval_transfer_status == <?php echo TRANSFER_VALIDATION_TRANSFER_DISPUTED;?>"> </i>
										{{ transfer.transfer_validation.transval_transfer_status ? transfer.transfer_validation.get( 'transferStatus' ) : '' }}<br/>
										{{ transfer.transfer_validation.transval_transfer_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}<br />
										{{ transfer.transfer_validation.transval_transfer_sweeper }}<br />
									</div>
									<span class="text-muted" ng-switch-default>---</span>
								</td>
								<td>{{ transfer.get( 'transferCategoryName' ) }}</td>
								<td>
									<i class="glyphicon glyphicon-transfer"> </i>
									<span>
										{{ transfer.get( 'transferStatusName' ) }}
									</span><br />
									<i class="glyphicon glyphicon-certificate"
											ng-class="{ 'status-completed': transfer.transfer_validation.transval_status == <?php echo TRANSFER_VALIDATION_COMPLETED;?>,
													'status-ongoing': transfer.transfer_validation.transval_status == <?php echo TRANSFER_VALIDATION_ONGOING;?>,
													'status-cancelled': transfer.transfer_validation.transval_status == <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?> }"> </i>
									<span class="text-muted" ng-if="transfer.transfer_validation.transval_status == null">---</span>
									<span ng-if="transfer.transfer_validation.transval_status != null"
											ng-class="{ 'status-completed': transfer.transfer_validation.transval_status == <?php echo TRANSFER_VALIDATION_COMPLETED;?>,
													'status-ongoing': transfer.transfer_validation.transval_status == <?php echo TRANSFER_VALIDATION_ONGOING;?>,
													'status-cancelled': transfer.transfer_validation.transval_status == <?php echo TRANSFER_VALIDATION_NOTREQUIRED;?> }">
										{{ transfer.transfer_validation.get( 'validationStatus' ) }}
									</span>
								</td>
								<td class="text-right vert-top">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.transferValidation({ transferItem: transfer, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="transfer.canCompleteValidation() || transfer.canOpenValidation() || transfer.canMarkValidationNotRequired()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu">
											<li role="menuitem" ng-if="transfer.canCompleteValidation() || transfer.canOpenValidation() || transfer.canMarkValidationNotRequired()">
												<a href ng-click="completeTransferValidation( transfer.transfer_validation )"
														ng-if="transfer.canCompleteValidation()">Complete
												</a>
												<a href ng-click="transferValidationOngoing( transfer.transfer_validation )"
														ng-if="transfer.canOpenValidation()">Mark as Ongoing
												</a>
												<a href ng-click="transferValidationNotRequired( transfer )"
														ng-if="transfer.canMarkValidationNotRequired()">Validation not Required
												</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!appData.transferValidations.length">
								<td colspan="7" class="text-center">No transfer transaction data available</td>
							</tr>
						</tbody>
					</table>

					<div class="text-center" ng-if="appData.totals.transferValidations > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.transferValidations"
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
				Outgoing <span ng-show="appData.pending.transfers > 0" class="label label-danger label-as-badge">{{ appData.pending.transfers }}</span>
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
								<th class="text-left" style="width: 175px;">Date / Time / Category</th>
								<th class="text-left">Destination / Items</th>
								<th class="text-center">Status</th>
								<th class="text-center" style="width: 175px;"></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="transfer in appData.transfers">
								<td class="text-center vert-top">{{ transfer.id }}</td>
								<td class="vert-top">
									<span>{{ transfer.get( 'transferDate' ) }}</span><br/>
									<span>{{ transfer.get( 'transferCategoryName' ) }}</span>
								</td>
								<td class="vert-top">
									<div>
										{{ transfer.destination_name }} {{ transfer.sender_name ? 'thru ' + transfer.sender_name : '' }}
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
									{{ transfer.get( 'transferStatusName' ) }}
								</td>
								<td class="text-right vert-top">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.transfer({ transferItem: transfer, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="transfer.canApprove() || transfer.canCancel() || transfer.canEdit()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="transfer.canApprove() || transfer.canCancel() || transfer.canEdit()">
											<li role="menuitem" ng-if="transfer.canApprove()">
												<a href ng-click="approveTransfer( transfer )">Approve</a>
											</li>
											<li role="menuitem" ng-if="transfer.canCancel()">
												<a href ng-click="cancelTransfer( transfer )">Cancel</a>
											</li>
											<li role="menuitem" ng-if="transfer.canEdit()">
												<a ui-sref="main.transfer({ transferItem: transfer, editMode: 'transfer' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!appData.transfers.length">
								<td colspan="5" class="text-center">No transfer transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.transfers > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.transfers"
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
				Incoming <span ng-show="appData.pending.receipts > 0" class="label label-danger label-as-badge">{{ appData.pending.receipts }}</span>
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
								<th class="text-left" style="width: 175px;">Date / Time / Category</th>
								<th class="text-left">Source / Items</th>
								<th class="text-center">Status</th>
								<th class="text-center" style="width: 175px;"></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="receipt in appData.receipts">
								<td class="text-center vert-top">{{ receipt.id }}</td>
								<td class="vert-top">
									<span>{{ receipt.get( 'receiptDate' ) }}</span><br/>
									<span>{{ receipt.get( 'transferCategoryName' ) }}</span>
								</td>
								<td class="vert-top">
									<div>
										{{ receipt.origin_name }} <span class="text-muted">- sent last {{ receipt.get( 'transferDate' ) }}</span>{{ receipt.sender_name ? ' thru ' + receipt.sender_name : '' }}
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
									{{ receipt.get( 'receiptStatusName' ) }}
								</td>
								<td class="text-right vert-top">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.transfer({ transferItem: receipt, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="receipt.canReceive()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="receipt.canReceive()">
											<li role="menuitem" ng-if="receipt.canReceive()">
												<a href ng-click="receiveTransfer( receipt )">Quick receipt</a>
											</li>
											<li role="menuitem" ng-if="receipt.canReceive()">
												<a ui-sref="main.transfer({ transferItem: receipt, editMode: 'receipt' })">Edit receipt...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!appData.receipts.length">
								<td colspan="5" class="text-center">No receipt transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.receipts > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.receipts"
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
				Adjustments <span ng-show="appData.pending.adjustments > 0" class="label label-danger label-as-badge">{{ appData.pending.adjustments }}</span>
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
								<th class="text-center">Adjusted Balance</th>
								<th class="text-center">Previous Balance</th>
								<th class="text-left">Reason</th>
								<th class="text-center">Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="adjustment in appData.adjustments">
								<td class="text-center">{{ adjustment.id }}</td>
								<td class="text_left">{{ adjustment.adjustment_timestamp | date: 'yyyy-MM-dd HH:mm:ss' }}</td>
								<td class="text_left">{{ adjustment.item_name }}</td>
								<td class="text-center">{{ adjustment.adjusted_quantity | number }}</td>
								<td class="text-center">{{ adjustment.adjustment_status == 1 ? '---' : ( adjustment.previous_quantity | number ) }}</td>
								<td class="text-left">{{ adjustment.reason }}</td>
								<td class="text-center">{{ adjustment.get( 'adjustmentStatus' ) }}</td>
								<td class="text-right">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.adjust({ adjustmentItem: adjustment })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="adjustment.canEdit() || adjustment.canApprove() || adjustment.canCancel()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="adjustment.canEdit() || adjustment.canApprove() || adjustment.canCancel()">
											<li role="menuitem" ng-if="adjustment.canApprove()">
												<a href ng-click="approveAdjustment( adjustment )">Approve</a>
											</li>
											<li role="menuitem" ng-if="adjustment.canCancel()">
												<a href ng-click="cancelAdjustment( adjustment )">Cancel</a>
											</li>
											<li role="menuitem" ng-if="adjustment.canEdit()">
												<a ui-sref="main.adjust({ adjustmentItem: adjustment, editMode: 'edit' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!appData.adjustments.length">
								<td colspan="8" class="text-center">No adjustment transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.adjustments > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.adjustments"
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
							<tr ng-repeat="collection in appData.collections">
								<td class="text-center vert-top">{{ collection.id }}</td>
								<td class="text-left vert-top">{{ collection.processing_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}<br />{{ collection.shift_num }}</td>
								<td class="text-center vert-top">{{ collection.business_date | date: 'yyyy-MM-dd' }}<br />{{ collection.cashier_shift_num }}</td>
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
												<tr ng-repeat="item in collection.collectionSummary" ng-class="{ deleted: item.item_status == <?php echo MOPPING_ITEM_VOIDED;?> }">
													<td>{{ item.item_description }}</td>
													<td class="text-center">{{ item.quantity | number }}</td>
												</tr>
											</tbody>
										</table>
									</div>
								</td>
								<td class="vert-top text-right">
									<div class="btn-group" uib-dropdown>
										<button id="split-button" type="button" class="btn btn-default" ui-sref="main.mopping({ moppingItem: collection, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="collection.canEdit()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="collection.canEdit()">
											<li role="menuitem"><a ui-sref="main.mopping({ moppingItem: collection, editMode: 'edit' })">Edit Collection...</a></li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="appData.collections.length == 0">
								<td colspan="5" class="text-center">No mopping collection data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.collections > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.collections"
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
				Allocations <span ng-show="appData.pending.allocations > 0" class="label label-danger label-as-badge">{{ appData.pending.allocations }}</span>
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
							<tr ng-repeat="row in appData.allocations">
								<td class="row-flag" ng-class="{ 'allocation-scheduled': row.allocation_status == 1, 'allocation-allocated': row.allocation_status == 2, 'allocation-completed': row.allocation_status == 3, 'allocation-cancelled': row.allocation_status == 4 }"></td>
								<td class="text-center vert-top">{{ row.id }}</td>
								<td class="text-left vert-top">{{ row.business_date | date: 'yyyy-MM-dd' }}<br />{{ row.shift_num }}</td>
								<td class="text-left vert-top">{{ row.assignee ? ( row.assignee_type == 2 ? 'TVM #' : '' ) + row.assignee : 'Not yet specified' }}<br />{{ row.assignee_type == 1 ? 'Station Teller' : 'Vending Machine' }}</td>
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
												<tr ng-repeat="item in row.allocationSummary">
													<td>{{ item.item_description }}</td>
													<td class="text-right">{{ ( item.initial === 0 ? '---' : ( item.item_class == 'cash' ? ( item.initial | number: 2 ) : ( item.initial | number ) ) ) + ( item.scheduled !== 0 ? ' (' + item.scheduled + ')' : '' ) }}</td>
													<td class="text-right">{{ item.additional === 0 ? '---' : ( item.item_class == 'cash' ? ( item.additional | number: 2 ) : ( item.additional | number ) ) }}</td>
													<td class="text-right">{{ item.remitted === 0 ? '---' : ( item.item_class == 'cash' ? ( item.remitted | number : 2 ) : ( item.remitted | number ) ) }}</td>
												</tr>
											</tbody>
										</table>
									</div>
									<div class="panel panel-default" ng-switch-when=2>
										<table class="table table-condensed table-bordered table-details">
											<thead>
												<tr class="active">
													<th>Item Description</th>
													<th style="width: 70px;">Loaded</th>
													<th style="width: 70px;">Loose</th>
													<th style="width: 70px;">Reject</th>
												</tr>
											</thead>
											<tbody>
												<tr ng-repeat="item in row.allocationSummary">
													<td>{{ item.item_description }}</td>
													<td class="text-right">{{ ( item.loaded === 0 ? '---' : ( item.loaded | number ) ) + ( item.scheduled !== 0 ? ' (' + item.scheduled + ')' : '' ) }}</td>
													<td class="text-right">{{ item.unsold === 0 ? '---' : ( item.unsold | number ) }}</td>
													<td class="text-right">{{ item.rejected === 0 ? '---' : ( item.rejected | number ) }}</td>
												</tr>
											</tbody>
										</table>
									</div>
								</td>
								<td class="text-center vert-top">{{ row.get( 'allocationStatus' ) }}</td>
								<td class="vert-top text-right" ng-switch on="row.allocation_status">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.allocation({ allocationItem: row, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="row.canAllocate() || row.canComplete() || row.canCancel() || row.canEdit()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="row.canAllocate() || row.canComplete() || row.canCancel() || row.canEdit()">
											<li role="menuitem" ng-if="row.canAllocate()">
												<a href="#" ng-click="allocateAllocation( row )">Allocate</a>
											</li>
											<li role="menuitem" ng-if="row.canComplete()">
												<a href="#" ng-click="completeAllocation( row )">Complete</a>
											</li>
											<li role="menuitem" ng-if="row.canCancel()">
												<a href="#" ng-click="cancelAllocation( row )">Cancel</a>
											</li>
											<li role="menuitem" ng-if="row.canEdit()">
												<a ui-sref="main.allocation({ allocationItem: row, editMode: 'edit' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="appData.allocations.length == 0">
								<td colspan="7" class="text-center">No allocation data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.allocations > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.allocations"
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
					Conversions <span ng-show="appData.pending.conversions > 0" class="label label-danger label-as-badge">{{ appData.pending.conversions }}</span>
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
							<tr ng-repeat="conversion in appData.conversions">
								<td class="text-center">{{ conversion.id }}</td>
								<td class="text-left">{{ conversion.conversion_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</td>
								<td class="text-left">{{ conversion.source_item_name }}</td>
								<td class="text-center">{{ conversion.source_quantity | number }}</td>
								<td class="text-left">{{ conversion.target_item_name }}</td>
								<td class="text-center">{{ conversion.target_quantity | number }}</td>
								<td class="text-left">{{ conversion.remarks }}</td>
								<td class="text-center">{{ conversion.get( 'conversionStatus' ) }}</td>
								<td class="text-right">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.convert({ conversionItem: conversion, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="conversion.canEdit() || conversion.canCancel() || conversion.canApprove()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="conversion.canEdit() || conversion.canCancel() || conversion.canApprove()">
											<li role="menuitem" ng-if="conversion.canCancel()">
												<a href ng-click="cancelConversion( conversion )">Cancel</a>
											</li>
											<li role="menuitem" ng-if="conversion.canApprove()">
												<a href ng-click="approveConversion( conversion )">Approve</a>
											</li>
											<li role="menuitem" ng-if="conversion.canEdit()">
												<a ui-sref="main.convert({ conversionItem: conversion, editMode: 'edit' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!appData.conversions.length">
								<td colspan="8" class="text-center">No conversion transaction data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.conversions > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.conversions"
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

		<!-- Shift Turnovers -->
		<uib-tab index="9" select="onTabSelect('shiftTurnovers')" ng-if="( sessionData.currentStore.store_type == 4 || sessionData.currentStore.store_type == 2 ) && checkPermissions( 'shiftTurnovers', 'view')"> <!-- Cashroom only -->
			<uib-tab-heading>
				Shift Turnovers <span ng-show="appData.pending.shiftTurnovers > 0" class="label label-danger label-as-badge">{{ appData.pending.shiftTurnovers }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Shift Turnovers <span class="label label-default" ng-if="filters.shiftTurnovers.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'shiftTurnovers' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.shiftTurnovers ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<button class="btn btn-default btn-sm" ng-click="updateShiftTurnovers( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>

				<div class="panel-body">

					<!-- Filter panel -->
					<div class="row filter_panel" ng-show="filterPanels.shiftTurnovers">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Start Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.shiftTurnoverStartDate.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.shiftTurnovers.startDate" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'shiftTurnoverStartDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">End Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.shiftTurnoverEndDate.opened"
										min-date="minDate" max-date="new Date()" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="filters.shiftTurnovers.endDate" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'shiftTurnoverEndDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-3 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Shift</label>
								<select class="form-control"
										ng-model="filters.shiftTurnovers.shift"
										ng-options="shift as shift.shift_num for shift in widgets.shiftTurnoverShifts track by shift.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'shiftTurnovers' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'shiftTurnovers' )">Clear</button>
							</div>
						</div>

					</div>
					<table class="table">
						<thead>
							<tr>
								<th>Business Date</th>
								<th>Shift</th>
								<th>Started by</th>
								<th>Closed by</th>
								<th class="text-center">Status</th>
								<th class="text-center" style="width: 175px;"></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="turnover in appData.shiftTurnovers">
								<td>
									{{ turnover.st_from_date | date: 'fullDate' }}
									<span class="label label-info" ng-if="turnover.isCurrent()">current</span>
								</td>
								<td>{{ turnover.description }}</td>
								<td>{{ turnover.start_user ? turnover.start_user : '---' }}</td>
								<td>{{ turnover.end_user ? turnover.end_user : '---' }}</td>
								<td class="text-center">
									{{ turnover.get( 'shiftTurnoverStatus' ) }}
									<span class="label label-danger label-as-badge" ng-if="turnover.st_status == <?php echo SHIFT_TURNOVER_CLOSED;?> && turnover.has_issues > 0">
										{{ turnover.has_issues }}
									</span>
								</td>
								<td class="text-right">
									<button type="button" class="btn btn-default" ui-sref="main.shiftTurnover({ shiftTurnover: turnover, editMode: 'edit' })">View details...</button>
								</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.shiftTurnovers > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.shiftTurnovers"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.shiftTurnovers"
								ng-change="updateShiftTurnovers( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- TVM Readings -->
		<uib-tab index="10" select="onTabSelect('tvmReadings')" ng-if="sessionData.currentStore.store_type == 4">
			<uib-tab-heading>
				Readings
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						TVM Readings <span class="label label-default" ng-if="filters.tvmReadings.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'tvmReadings' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.tvmReadings ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<span ng-if="checkPermissions( 'allocations', 'edit' )">
							<button class="btn btn-primary btn-sm" ui-sref="main.tvmReading({ editMode: 'edit' })">
								<i class="glyphicon glyphicon-plus"></i> New reading
							</button>&nbsp;
						</span>
						<button class="btn btn-default btn-sm" ng-click="updateTvmReadings( sessionData.currentStore.id )">
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
									ng-model="quicksearch.tvmReadings"
									ng-keypress="loadRecord( $event, 'tvmReadings' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.tvmReadings">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.tvmReadingsDate.opened"
											min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
											ng-model="filters.tvmReadings.date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'tvmReadingsDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Shift</label>
								<select class="form-control"
										ng-model="filters.tvmReadings.shift"
										ng-options="shift as shift.shift_num for shift in widgets.tvmReadingsShifts track by shift.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">TVM ID</label>
								<input type="text" class="form-control"
										ng-model="filters.tvmReadings.machine_id">
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'tvmReadings' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'tvmReadings' )">Clear</button>
							</div>
						</div>
					</div>

					<table class="table">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th class="text-left">Date / Time</th>
								<th class="text-left">Shift</th>
								<th class="text-center">TVM</th>
								<th class="text-left">Cashier</th>
								<th class="text-center">Last Reading</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="reading in appData.tvmReadings">
								<td class="text-center">{{ reading.id }}</td>
								<td class="text-left">{{ reading.tvmr_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</td>
								<td class="text-left">{{ reading.shift_num }}</td>
								<td class="text-center">{{ reading.tvmr_machine_id }}</td>
								<td class="text-left">{{ reading.cashier_name }}</td>
								<td class="text-center">{{ reading.tvmr_last_reading }}</td>
								<td class="text-right">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.tvmReading({ TVMReading: reading, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="reading.canEdit() || reading.canRemove()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="reading.canEdit() || reading.canRemove()">
											<li role="menuitem" ng-if="reading.canRemove()">
												<a href ng-click="removeReading( reading )">Remove</a>
											</li>
											<li role="menuitem" ng-if="reading.canEdit()">
												<a ui-sref="main.tvmReading({ TVMReading: reading, editMode: 'edit' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!appData.tvmReadings.length">
								<td colspan="7" class="text-center">No TVM readings data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.tvmReadings > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.tvmReadings"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.tvmReadings"
								ng-change="updateTvmReadings( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Shift Detail Cash Report -->
		<uib-tab index="11" select="onTabSelect('shiftDetailCashReport')" ng-if="sessionData.currentStore.store_type == 4">
			<uib-tab-heading>
				Shift Detail Cash Report
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">
						Shift Detail Cash Reports <span class="label label-default" ng-if="filters.shiftDetailCashReports.filtered">Filtered</span>
					</h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'shiftDetailCashReports' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.shiftDetailCashReports ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<span ng-if="checkPermissions( 'allocations', 'edit' )">
							<button class="btn btn-primary btn-sm" ui-sref="main.shiftDetailCashReport({ editMode: 'edit' })">
								<i class="glyphicon glyphicon-plus"></i> New Cash Report
							</button>&nbsp;
						</span>
						<button class="btn btn-default btn-sm" ng-click="updateShiftDetailCashReports( sessionData.currentStore.id )">
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
									ng-model="quicksearch.shiftDetailCashReports"
									ng-keypress="loadRecord( $event, 'shiftDetailCashReports' )">
						</div>
					</div>

					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.shiftDetailCashReports">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Date</label>
								<div class="input-group">
									<input type="text" class="form-control" uib-datepicker-popup="{{ filters.dateFormat }}" is-open="widgets.shiftDetailCashReportDate.opened"
											min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
											ng-model="filters.shiftDetailCashReports.date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
									<span class="input-group-btn">
										<button type="button" class="btn btn-default" ng-click="showDatePicker( 'shiftDetailCashReportDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
									</span>
								</div>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Shift</label>
								<select class="form-control"
										ng-model="filters.shiftDetailCashReports.shift"
										ng-options="shift as shift.shift_num for shift in widgets.shiftDetailCashReportsShifts track by shift.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Teller ID</label>
								<input type="text" class="form-control"
										ng-model="filters.shiftDetailCashReports.teller_id">
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">POS ID</label>
								<input type="text" class="form-control"
										ng-model="filters.shiftDetailCashReports.pos_id">
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'shiftDetailCashReports' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'shiftDetailCashReports' )">Clear</button>
							</div>
						</div>
					</div>

					<table class="table">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th class="text-left">Date</th>
								<th class="text-left">Shift</th>
								<th class="text-center">POS</th>
								<th class="text-left">Teller</th>
								<th class="text-left">Login</th>
								<th class="text-left">Logout</th>
								<th class="text-center">Allocation</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="report in appData.shiftDetailCashReports">
								<td class="text-center">{{ report.id }}</td>
								<td class="text-left">{{ report.sdcr_business_date | date: 'yyyy-MM-dd' }}</td>
								<td class="text-left">{{ report.shift_num }}</td>
								<td class="text-center">{{ report.sdcr_pos_id }}</td>
								<td class="text-left">{{ report.sdcr_teller_id }}</td>
								<td class="text-left">{{ report.sdcr_login_time | date: 'yyyy-MM-dd HH:mm:ss' }}</td>
								<td class="text-left">{{ report.sdcr_logout_time | date: 'yyyy-MM-dd HH:mm:ss' }}</td>
								<td class="text-center">
									<a ui-sref="main.allocation({ allocationId: report.sdcr_allocation_id, editMode: 'view' })" ng-if="report.sdcr_allocation_id">View</a>
								</td>
								<td class="text-right">
									<div class="btn-group" uib-dropdown>
										<button type="button" class="btn btn-default" ui-sref="main.shiftDetailCashReport({ shiftDetailCashReport: report, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default btn-dropdown-caret" uib-dropdown-toggle ng-if="report.canEdit() || report.canRemove()">
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu" ng-if="report.canEdit() || report.canRemove()">
											<li role="menuitem" ng-if="report.canRemove()">
												<a href ng-click="removeShiftDetailCashReport( report )">Delete</a>
											</li>
											<li role="menuitem" ng-if="report.canEdit()">
												<a ui-sref="main.shiftDetailCashReport({ shiftDetailCashReport: report, editMode: 'edit' })">Edit...</a>
											</li>
										</ul>
									</div>
								</td>
							</tr>
							<tr ng-show="!appData.shiftDetailCashReports.length">
								<td colspan="9" class="text-center">No Shift Detail Cash Reports data available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="appData.totals.shiftDetailCashReports > filters.itemsPerPage">
						<uib-pagination
								total-items="appData.totals.shiftDetailCashReports"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.shiftDetailCashReports"
								ng-change="updateShiftDetailCashReports( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

	</uib-tabset>
</div>