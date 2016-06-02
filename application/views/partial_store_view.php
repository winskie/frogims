<!-- Inventory -->
<div>
	<uib-tabset active="activeTab">
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
							<th>Description</th>
							<th class="text-right">Quantity</th>
							<th class="text-right">Buffer Level</th>
							<th class="text-right">Reserved</th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="item in data.items" ng-class="{info: currentItem == item}">
							<td>{{ item.item_name }}</td>
							<td>{{ item.item_description }}</td>
							<td class="text-right">{{ item.quantity | number }}</td>
							<td class="text-right">{{ item.buffer_level | number }}</td>
							<td class="text-right">{{ item.reserved | number }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>
		
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
					<div class="well">
						Filter here
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
				</div>
			</div>
		</uib-tab>

		<!-- Outgoing -->
		<uib-tab index="2" select="onTabSelect(2)">
			<uib-tab-heading>
				Outgoing <span ng-show="data.pendingTransfers > 0" class="label label-danger label-as-badge">{{ data.pendingTransfers }}</span>
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
				<table class="table table-hover">
					<thead>
						<tr>
							<th class="text-left" style="width: 175px;">Date / Time</th>
							<th class="text-left">Destination / Items</th>
							<th class="text-center">Status</th>
							<th class="text-center" style="width: 175px;"></th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="transfer in data.transfers">
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
							<td colspan="4" class="text-center">No transfer transaction data available</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>

		<!-- Incoming -->
		<uib-tab index="3" select="onTabSelect(3)">
			<uib-tab-heading>
				Incoming <span ng-show="data.pendingReceipts > 0" class="label label-danger label-as-badge">{{ data.pendingReceipts }}</span>
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
				<table class="table">
					<thead>
						<tr>
							<th class="text-left" style="width: 175px;">Date / Time</th>
							<th class="text-left">Source / Items</th>
							<th class="text-center">Status</th>
							<th class="text-center" style="width: 175px;"></th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="receipt in data.receipts">
							<td class="vert-top">{{ receipt.transfer_datetime }}</td>
							<td class="vert-top">
								<div>
									{{ receipt.origin_name }}
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
										<button type="button" class="btn btn-default btn-block" ui-sref="main.transfer({ transferItem: receipt, mode: 'view' })">View details...</button>
									</div>

								</div>
							</td>
						</tr>
						<tr ng-show="!data.receipts.length">
							<td colspan="4" class="text-center">No receipt transaction data available</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>

		<!-- Adjustments -->
		<uib-tab index="4" select="onTabSelect(4)">
			<uib-tab-heading>
				Adjustments <span ng-show="data.pendingAdjustments > 0" class="label label-danger label-as-badge">{{ data.pendingAdjustments }}</span>
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
				<table class="table">
					<thead>
						<tr>
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
						<tr ng-show="!adjustments.length">
							<td colspan="7" class="text-center">No adjustment transaction data available</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>
        
        <!-- Mopping -->
        <uib-tab index="5" select="onTabSelect(5)">
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
                <table class="table">
                    <thead>
                        <tr>
							<th class="text-left">Processing Date</th>
                            <th class="text-center">Business Date</th>
							<th class="text-left">Processed Items</th>
                            <th class="text-center"></th>
						</tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="collection in data.collections">
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
                            <td colspan="4" class="text-center">No mopping collection data available</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </uib-tab>
        
        <!-- Allocation -->
        <uib-tab index="6" select="onTabSelect(6)">
            <uib-tab-heading>
                Allocations
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
                <table class="table">
                    <thead>
                        <tr>
							<th class="row-flag"></th>
                            <th class="text-left">Business Date</th>
							<th class="text-left">Allocated to</th>
							<th class="text-left">Allocation Details</th>
							<th class="text-center">Status</th>
							<th class="text-center"></th>
						</tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="row in data.allocations">
							<td class="row-flag" ng-class="lookupAllocationStatus( row.allocation_status, 'className' )"></td>
                            <td class="text-left vert-top">{{ row.business_date }}<br />{{ row.shift_num }}</td>
                            <td class="text-left vert-top">{{ row.assignee ? row.assignee : 'Not yet specified' }}<br />{{ row.assignee_type == 1 ? 'Station Teller' : 'Vending Machine' }}</td>
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
                            <td colspan="5" class="text-center">No allocation data available</td>
                        </tr>
                    </tbody>
                </table>
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
                <table class="table">
                    <thead>
                        <tr>
							<th class="text-left">Date / Time</th>
							<th class="text-left">Input Item</th>
							<th class="text-center">Input Quantity</th>
							<th class="text-left">Output Item</th>
							<th class="text-center">Output Quantity</th>
							<th class="text-left">Remarks</th>
						</tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="conversion in data.conversions">
                            <td class="text-left">{{ conversion.conversion_datetime }}</td>
                            <td class="text-left">{{ conversion.source_item_name }}</td>
                            <td class="text-center">{{ conversion.source_quantity | number }}</td>
                            <td class="text-left">{{ conversion.target_item_name }}</td>
                            <td class="text-center">{{ conversion.target_quantity | number }}</td>
                            <td class="text-left">{{ conversion.remarks }}</td>
                        </tr>
                        <tr ng-show="!data.conversions.length">
                            <td colspan="6" class="text-center">No conversion transaction data available</td>
                        </tr>
                    </tbody>
                </table>
        </uib-tab>
        
	</uib-tabset>
</div>