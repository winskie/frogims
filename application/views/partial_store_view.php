<div>
	<uib-tabset id="mainTabSet" active="activeTab">
		<!-- Inventory -->
		<uib-tab heading="Inventory" index="0" select="onTabSelect(0)">
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
							<th class="text-right">Quantity</th>
							<th class="text-right">Buffer Level</th>
							<th class="text-right">Reserved</th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="item in data.items" ng-class="{info: currentItem == item}">
							<td>{{ item.item_name }}</td>
							<td>{{ item.item_group }}</td>
							<td>{{ item.item_description }}</td>
							<td class="text-right">{{ item.quantity | number }}</td>
							<td class="text-right">{{ item.buffer_level | number }}</td>
							<td class="text-right">{{ item.reserved | number }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>

		<!-- Transactions -->
		<uib-tab heading="Transactions Summary" index="1" select="onTabSelect(1)">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">Transactions Summary</h3>
					<button class="btn btn-default btn-sm pull-right" ng-click="updateTransactions( sessionData.currentStore.id )">
						<i class="glyphicon glyphicon-refresh"></i>
					</button>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<div class="row">
						<div class="col-sm-4 col-md-3 col-lg-2">
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
								<td>{{ lookup( 'transactionTypes', transaction.transaction_type ) }}</td>
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
								ng-model="filters.transactions.page"
								ng-change="updateTransactions( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Outgoing -->
		<uib-tab index="2" select="onTabSelect(2)">
			<uib-tab-heading>
				Outgoing <span ng-show="data.pending.transfers > 0" class="label label-danger label-as-badge">{{ data.pending.transfers }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">Transfers</h3>
					<div class="pull-right">
						<button class="btn btn-primary btn-sm" ui-sref="main.transfer({ editMode: 'transfer' })">
							<i class="glyphicon glyphicon-plus"></i> New transfer
						</button>&nbsp;
						<button class="btn btn-default btn-sm" ng-click="updateTransfers( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<div class="row">
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
								<td class="vert-top">{{ transfer.transfer_datetime }}</td>
								<td class="vert-top">
									<div>
										{{ transfer.destination_name }}
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
													<td class="text-right">{{ item.quantity | number }}</td>
													<td class="col-sm-5">{{ item.remarks }}</td>
												</tr>
											</tbody>
										</table>
									</div>
								</td>
								<td class="text-center vert-top">{{ lookup( 'transferStatus', transfer.transfer_status ) }}</td>
								<td class="text-right vert-top">
									<div class="animate-switch-container" ng-switch on="transfer.transfer_status">

										<div class="animate-switch" ng-switch-when="<?php echo TRANSFER_PENDING;?>">
											<div class="btn-group btn-block" uib-dropdown>
												<button id="split-button" type="button" class="btn btn-primary col-sm-9 col-md-10" ng-click="approveTransfer( transfer )">Approve</button>
												<button type="button" class="btn btn-primary col-sm-3 col-md-2" uib-dropdown-toggle>
													<span class="caret"></span>
												</button>
												<ul uib-dropdown-menu role="menu">
													<li role="menuitem"><a ui-sref="main.transfer({ transferItem: transfer, editMode: 'transfer' })">Edit...</a></li>
													<li role="menuitem"><a href="#" ng-click="cancelTransfer( transfer )">Cancel</a></li>
												</ul>
											</div>
										</div>

										<div class="animate-switch" ng-switch-when="<?php echo TRANSFER_APPROVED;?>">
											<div class="btn-group btn-block" uib-dropdown>
												<button id="split-button" type="button" class="btn btn-default col-sm-9 col-md-10" ui-sref="main.transfer({ transferItem: transfer, mode: 'view' })">View details...</button>
												<button type="button" class="btn btn-default col-sm-3 col-md-2" uib-dropdown-toggle>
													<span class="caret"></span>
												</button>
												<ul uib-dropdown-menu role="menu">
													<li role="menuitem"><a href="#" ng-click="cancelTransfer( transfer )">Cancel</a></li>
												</ul>
											</div>
										</div>

										<div class="animate-switch" ng-switch-default>
											<button type="button" class="btn btn-default btn-block" ui-sref="main.transfer({ transferItem: transfer, mode: 'view' })">View details...</button>
										</div>

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
								ng-model="filters.transfers.page"
								ng-change="updateTransfers( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Incoming -->
		<uib-tab index="3" select="onTabSelect(3)">
			<uib-tab-heading>
				Incoming <span ng-show="data.pending.receipts > 0" class="label label-danger label-as-badge">{{ data.pending.receipts }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">Receipts</h3>
					<div class="pull-right">
						<button class="btn btn-primary btn-sm" ui-sref="main.transfer({ editMode: 'externalReceipt' })">
							<i class="glyphicon glyphicon-plus"></i> New receipt
						</button>&nbsp;
						<button class="btn btn-default btn-sm" ng-click="updateReceipts( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<div class="row">
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
										{{ receipt.origin_name }} - sent last {{ receipt.transfer_datetime }}
									</div>
									<div class="panel panel-default">
										<table class="table table-condensed table-bordered table-details">
											<thead>
												<tr class="active">
													<th class="text-center">Item</th>
													<th class="text-center" style="width: 70px;">Sent</th>
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
								<td class="text-center vert-top">{{ lookup( 'transferStatus', receipt.transfer_status ) }}</td>
								<td class="text-right vert-top">
									<div class="animate-switch-container" ng-switch on="receipt.transfer_status">

										<div class="animate-switch" ng-switch-when="<?php echo TRANSFER_APPROVED;?>">
											<div class="btn-group btn-block" uib-dropdown>
												<button id="split-button" type="button" class="btn btn-primary col-sm-9 col-md-10"
														ui-sref="main.transfer({ transferItem: receipt, editMode: 'receipt' })">Receive...
												</button>
												<button type="button" class="btn btn-primary col-sm-3 col-md-2" uib-dropdown-toggle>
													<span class="caret"></span>
												</button>
												<ul uib-dropdown-menu role="menu">
													<li role="menuitem"><a href="#" ng-click="receiveTransfer( receipt )">Quick receipt</a></li>
												</ul>
											</div>
										</div>

										<div class="animate-switch" ng-switch-default>
											<button type="button" class="btn btn-default btn-block" ui-sref="main.transfer({ transferItem: receipt, editMode: 'view' })">View details...</button>
										</div>

									</div>
								</td>
							</tr>
							<tr ng-show="!data.receipts.length">
								<td colspan="5" class="text-center">No receipt transaction data available</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</uib-tab>

		<!-- Adjustments -->
		<uib-tab index="4" select="onTabSelect(4)">
			<uib-tab-heading>
				Adjustments <span ng-show="data.pending.adjustments > 0" class="label label-danger label-as-badge">{{ data.pending.adjustments }}</span>
			</uib-tab-heading>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">Adjustments</h3>
					<div class="pull-right">
						<button class="btn btn-primary btn-sm" ui-sref="main.adjust">
							<i class="glyphicon glyphicon-plus"></i> New adjustment
						</button>&nbsp;
						<button class="btn btn-default btn-sm" ng-click="updateAdjustments( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="panel-body">
					<div class="row">
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
								<td class="text-right">{{ adjustment.adjustment_status == 1 ? 'pending' : ( adjustment.previous_quantity | number ) }}</td>
								<td class="text-left">{{ adjustment.reason }}</td>
								<td class="text-center">{{ lookup( 'adjustmentStatus', adjustment.adjustment_status ) }}</td>
								<td>
									<div class="animate-switch-container" ng-switch on="adjustment.adjustment_status">

										<div class="animate-switch" ng-switch-when="<?php echo ADJUSTMENT_PENDING;?>">
											<div class="btn-group btn-block" uib-dropdown>
												<button id="split-button" type="button" class="btn btn-primary col-sm-9 col-md-10" ng-click="approveAdjustment( adjustment )">Approve</button>
												<button type="button" class="btn btn-primary col-sm-3 col-md-2" uib-dropdown-toggle>
													<span class="caret"></span>
												</button>
												<ul uib-dropdown-menu role="menu">
													<li role="menuitem"><a ui-sref="main.adjust({ adjustmentItem: adjustment })">Edit Adjustment...</a></li>
												</ul>
											</div>
										</div>

										<div class="animate-switch" ng-switch-default>
											<button type="button" class="btn btn-default btn-block" ui-sref="main.adjust({ adjustmentItem: adjustment })">View details...</button>
										</div>

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
								ng-model="filters.adjustments.page"
								ng-change="updateAdjustments( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

        <!-- Mopping -->
        <uib-tab index="5" select="onTabSelect(5)" ng-if="sessionData.currentStore.store_type == 2"> <!-- Production only -->
            <uib-tab-heading>
                Mopping Collection
            </uib-tab-heading>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title pull-left">Mopping Collection</h3>
                    <div class="pull-right">
                        <button class="btn btn-primary btn-sm" ui-sref="main.mopping({ editMode: 'new' })">
                            <i class="glyphicon glyphicon-plus"></i> New collection
                        </button>&nbsp;
                        <button class="btn btn-default btn-sm" ng-click="updateCollections( sessionData.currentStore.id )">
                            <i class="glyphicon glyphicon-refresh"></i>
                        </button>
                    </div>
                    <div class="clearfix"></div>
                </div>
				<div class="panel-body">
					<div class="row">
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
												<tr ng-repeat="item in collection.items">
													<td>{{ item.item_description }}</td>
													<td class="text-right">{{ item.quantity | number }}</td>
												</tr>
											</tbody>
										</table>
									</div>
								</td>
								<td class="vert-top">
									<div class="btn-group btn-block" uib-dropdown>
										<button id="split-button" type="button" class="btn btn-default col-sm-9 col-md-10" ui-sref="main.mopping({ moppingItem: collection, editMode: 'view' })">View details...</button>
										<button type="button" class="btn btn-default col-sm-3 col-md-2" uib-dropdown-toggle>
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu">
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
								ng-model="filters.collections.page"
								ng-change="updateCollections( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
            </div>
        </uib-tab>

        <!-- Allocation -->
        <uib-tab index="6" select="onTabSelect(6)" ng-if="sessionData.currentStore.store_type == 4"> <!-- Cashroom only -->
            <uib-tab-heading>
				Allocations <span ng-show="data.pending.allocations > 0" class="label label-danger label-as-badge">{{ data.pending.allocations }}</span>
			</uib-tab-heading>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title pull-left">Allocations</h3>
                    <div class="pull-right">
                        <button class="btn btn-primary btn-sm" ui-sref="main.allocation({ editMode: 'new' })">
                            <i class="glyphicon glyphicon-plus"></i> New allocation
                        </button>&nbsp;
                        <button class="btn btn-default btn-sm" ng-click="updateAllocations( sessionData.currentStore.id )">
                            <i class="glyphicon glyphicon-refresh"></i>
                        </button>
                    </div>
                    <div class="clearfix"></div>
                </div>
				<div class="panel-body">
					<div class="row">
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
									<div class="btn-group btn-block" uib-dropdown ng-switch-when="<?php echo ALLOCATION_SCHEDULED;?>">
										<button id="split-button" type="button" class="btn btn-default col-sm-9 col-md-10" ui-sref="main.allocation({ allocationItem: row, editMode: 'edit' })">Edit...</button>
										<button type="button" class="btn btn-default col-sm-3 col-md-2" uib-dropdown-toggle>
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu">
											<li role="menuitem"><a href="#" ng-click="cancelAllocation( row )">Cancel</a></li>
											<li role="menuitem"><a ui-sref="main.allocation({ allocationItem: row, editMode: 'view' })">View details...</a></li>
										</ul>
									</div>

									<div class="btn-group btn-block" uib-dropdown ng-switch-when="<?php echo ALLOCATION_ALLOCATED;?>">
										<button id="split-button" type="button" class="btn btn-default col-sm-9 col-md-10" ui-sref="main.allocation({ allocationItem: row, editMode: 'edit' })">Edit...</button>
										<button type="button" class="btn btn-default col-sm-3 col-md-2" uib-dropdown-toggle>
											<span class="caret"></span>
										</button>
										<ul uib-dropdown-menu role="menu">
											<li role="menuitem"><a ui-sref="main.allocation({ allocationItem: row, editMode: 'view' })">View details...</a></li>
										</ul>
									</div>

									<div class="animate-switch" ng-switch-default>
										<button type="button" class="btn btn-default btn-block" ui-sref="main.allocation({ allocationItem: row, editMode: 'view' })">View details...</button>
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
								ng-model="filters.allocations.page"
								ng-change="updateAllocations( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
            </div>
        </uib-tab>

        <!-- Conversions -->
        <uib-tab index="7" select="onTabSelect(7)">
            <uib-tab-heading>
                Conversions
            </uib-tab-heading>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title pull-left">Conversions</h3>
                    <div class="pull-right">
                        <button class="btn btn-primary btn-sm" ui-sref="main.convert">
                            <i class="glyphicon glyphicon-plus"></i> New conversion
                        </button>&nbsp;
                        <button class="btn btn-default btn-sm" ng-click="updateConversions( sessionData.currentStore.id )">
                            <i class="glyphicon glyphicon-refresh"></i>
                        </button>
                    </div>
                    <div class="clearfix"></div>
                </div>
				<div class="panel-body">
					<div class="row">
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
									<div class="animate-switch-container" ng-switch on="conversion.conversion_status">

										<div class="animate-switch" ng-switch-when="<?php echo CONVERSION_PENDING;?>">
											<div class="btn-group btn-block" uib-dropdown>
												<button id="split-button" type="button" class="btn btn-primary col-sm-9 col-md-10" ng-click="approveConversion( conversion )">Approve</button>
												<button type="button" class="btn btn-primary col-sm-3 col-md-2" uib-dropdown-toggle>
													<span class="caret"></span>
												</button>
												<ul uib-dropdown-menu role="menu">
													<li role="menuitem"><a ui-sref="main.convert({ conversionItem: conversion })">Edit Conversion...</a></li>
												</ul>
											</div>
										</div>

										<div class="animate-switch" ng-switch-default>
											<button type="button" class="btn btn-default btn-block" ui-sref="main.convert({ conversionItem: conversion })">View details...</button>
										</div>

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
								ng-model="filters.conversions.page"
								ng-change="updateConversions( sessionData.currentStore.id )">
						</uib-pagination>
					</div>
				</div>
        </uib-tab>

	</uib-tabset>
</div>