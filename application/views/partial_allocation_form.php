<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Allocation Information</h3>
	</div>
	<div class="panel-body">
		<form class="form-horizontal row" ng-switch on="allocationItem.allocation_status == <?php echo ALLOCATION_SCHEDULED;?> && data.editMode != 'view' ">
			<div class="col-sm-4">
				<!-- Business Date -->
				<div class="form-group">
					<label class="control-label col-sm-5">Business Date</label>
					<div class="input-group col-sm-7" ng-switch-when="true">
						<input type="text" class="form-control" uib-datepicker-popup="{{ data.businessDatepicker.format }}" is-open="data.businessDatepicker.opened"
							min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
							ng-model="allocationItem.business_date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
						<span class="input-group-btn">
							<button type="button" class="btn btn-default" ng-click="showDatePicker()"><i class="glyphicon glyphicon-calendar"></i></button>
						</span>
					</div>
					<div class="input-group col-sm-7" ng-switch-default>
						<p class="form-control-static">{{ allocationItem.business_date | date : 'yyyy-MM-dd' }}</p>
					</div>
				</div>

				<!-- Assignee Shift -->
				<div class="form-group" ng-hide="data.assigneeShiftLabel != 'Teller Shift'">
					<label class="control-label col-sm-5">{{ data.assigneeShiftLabel }}</label>
					<div class="input-group col-sm-7" ng-switch-when="true">
						<select class="form-control"
								ng-model="data.selectedAssigneeShift"
								ng-options="shift.shift_num for shift in data.assigneeShifts track by shift.id"
								ng-change="onAssigneeShiftChange()">
						</select>
					</div>
					<div class="input-group col-sm-7" ng-switch-default>
						<p class="form-control-static">{{ data.selectedAssigneeShift.shift_num }}</p>
					</div>
				</div>
			</div>

			<div class="col-sm-4">
				<!-- Assignee Type -->
				<div class="form-group">
					<label class="control-label col-sm-5">Type</label>
					<div class="input-group col-sm-7" ng-switch-when="true">
						<select class="form-control"
								ng-model="data.selectedAssigneeType"
								ng-options="type.typeName for type in data.assigneeTypes track by type.id"
								ng-disabled="( allocationItem.allocations.length > 0 ) || ( allocationItem.remittances.length > 0 )"
								ng-change="onAssigneeTypeChange()">
						</select>
					</div>
					<div class="input-group col-sm-7" ng-switch-default>
						<p class="form-control-static">{{ data.selectedAssigneeType.typeName }}</p>
					</div>
				</div>

				<!-- Assignee -->
				<div class="form-group">
					<label class="control-label col-sm-5">{{ data.assigneeLabel }}</label>
					<div class="input-group col-sm-7" ng-switch-when="true">
						<input class="form-control" ng-model="allocationItem.assignee" >
					</div>
					<div class="input-group col-sm-7" ng-switch-default>
						<p class="form-control-static">{{ allocationItem.assignee }}</p>
					</div>
				</div>
			</div>

			<div class="col-sm-4">
				<!-- Allocation Status -->
				<div class="form-group">
					<label class="control-label col-sm-5">Status</label>
					<p class="form-control-static">{{ allocationItem.get( 'allocationStatus' ) }}</p>
				</div>
			</div>
		</form>
	</div>
</div>

<div>
	<uib-tabset justified="false" active="data.activeTab">
		<!-- Allocations -->
		<uib-tab select="updatePhase( 'allocation' )" index="0">
			<uib-tab-heading>
				{{ data.allocationsTabLabel }}
			</uib-tab-heading>
			<div class="panel panel-default" style="margin: 20px 0; height: 300px; overflow-y: auto;">
				<table class="table table-condensed">
					<thead>
						<tr>
							<th class="text-center">Row</th>
							<th class="text-left">Cashier Shift</th>
							<th class="text-left">Category</th>
							<th class="text-left">Item Description</th>
							<th class="text-center">Quantity</th>
							<th class="text-center">Total Amount</th>
							<th class="text-center">Status</th>
							<th class="text-center" ng-if="data.editMode != 'view'">Void</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="8"><h5>Ticket Items</h5></td>
						</tr>
						<tr ng-repeat="row in allocationItem.allocations"
								ng-class="{
										danger: row.markedVoid || ( [<?php echo implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) );?>].indexOf( row.allocation_item_status ) != -1 ),
										deleted: ( [<?php echo implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) );?>].indexOf( row.allocation_item_status ) != -1 )
									}">
							<td class="text-center">{{ $index + 1 }}</td>
							<td class="text-left">{{ row.cashier_shift_num }}</td>
							<td class="text-left">{{ row.category_name }}</td>
							<td class="text-left">{{ row.item_name }}</td>
							<td class="text-center">{{ row.allocated_quantity | number }}</td>
							<td class="text-right"></td>
							<td class="text-center">{{ row.get( 'allocationItemStatus' ) }}</td>
							<td class="text-center" ng-if="data.editMode != 'view'" ng-switch on="row.allocation_item_status">
								<a href
										ng-if="row.allocation_item_status == <?php echo ALLOCATION_ITEM_SCHEDULED;?> && row.id == undefined"
										ng-click="removeAllocationItem( 'allocation', row )">
									<i class="glyphicon glyphicon-remove-circle"></i>
								</a>
								<input type="checkbox" value="{{ row.id }}"
										ng-if="row.allocation_item_status == <?php echo ALLOCATION_ITEM_ALLOCATED;?> || row.allocation_item_status == <?php echo ALLOCATION_ITEM_SCHEDULED;?> && row.id"
										ng-click="getItemQuantities()"
										ng-model="row.markedVoid">
							</td>
						</tr>
						<tr ng-if="!allocationItem.allocations.length">
							<td colspan="8" class="text-center bg-warning">
								No allocated ticket items
							</td>
						</tr>
					</tbody>
					<tbody>
						<tr>
							<td colspan="7"><h5>Cash Items</h5></td>
						</tr>
						<tr ng-repeat="row in allocationItem.cash_allocations"
								ng-class="{
										danger: row.markedVoid || ( [<?php echo implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) );?>].indexOf( row.allocation_item_status ) != -1 ),
										deleted: ( [<?php echo implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) );?>].indexOf( row.allocation_item_status ) != -1 )
									}">
							<td class="text-center">{{ $index + 1 }}</td>
							<td class="text-left">{{ row.cashier_shift_num }}</td>
							<td class="text-left">{{ row.category_name }}</td>
							<td class="text-left">{{ row.item_name }}</td>
							<td class="text-center">{{ row.allocated_quantity | number }}</td>
							<td class="text-right">{{ ( row.iprice_unit_price * row.allocated_quantity ) | number: 2 }}</td>
							<td class="text-center">{{ row.get( 'allocationItemStatus' ) }}</td>
							<td class="text-center" ng-if="data.editMode != 'view'" ng-switch on="row.allocation_item_status">
								<a href
										ng-if="row.allocation_item_status == <?php echo ALLOCATION_ITEM_SCHEDULED;?> && row.id == undefined"
										ng-click="removeAllocationItem( 'cash_allocation', row )">
									<i class="glyphicon glyphicon-remove-circle"></i>
								</a>
								<input type="checkbox" value="{{ row.id }}"
										ng-if="row.allocation_item_status == <?php echo ALLOCATION_ITEM_ALLOCATED;?> || row.allocation_item_status == <?php echo ALLOCATION_ITEM_SCHEDULED;?> && row.id"
										ng-click="getItemQuantities()"
										ng-model="row.markedVoid">
							</td>
						</tr>
						<tr ng-if="!allocationItem.cash_allocations.length">
							<td colspan="8" class="text-center bg-warning">
								No allocated cash items
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>
		<!-- Remittances -->
		<uib-tab select="updatePhase( 'remittance' )" index="1" disable="allocationItem.allocation_status == 1 && allocationItem.assignee_type == 1">
			<uib-tab-heading>
				{{ data.remittancesTabLabel }}
			</uib-tab-heading>
			<div class="panel panel-default" style="margin: 20px 0; height: 300px; overflow-y: auto;">
				<table class="table table-condensed">
					<thead>
						<tr>
							<th class="text-center">Row</th>
							<th class="text-left">Cashier Shift</th>
							<th class="text-left">Category</th>
							<th class="text-left">Item Description</th>
							<th class="text-center">Quantity</th>
							<th class="text-center">Total Amount</th>
							<th class="text-center">Status</th>
							<th class="text-center" ng-if="data.editMode != 'view'">Void</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="8"><h5>Ticket Items</td>
						</tr>
						<tr ng-repeat="row in allocationItem.remittances"
								ng-class="{
										danger: row.markedVoid || row.allocation_item_status == <?php echo REMITTANCE_ITEM_VOIDED;?>,
										deleted: row.allocation_item_status == <?php echo REMITTANCE_ITEM_VOIDED;?>
									}">
							<td class="text-center">{{ $index + 1 }}</td>
							<td class="text-left">{{ row.cashier_shift_num }}</td>
							<td class="text-left">{{ row.category_name }}</td>
							<td class="text-left">{{ row.item_name }}</td>
							<td class="text-center">{{ row.allocated_quantity | number }}</td>
							<td class="text-right"></td>
							<td class="text-center">{{ row.get( 'allocationItemStatus' ) }}</td>
							<td class="text-center" ng-if="data.editMode != 'view'" ng-switch on="row.allocation_item_status">
								<a href
										ng-if="row.allocation_item_status == <?php echo REMITTANCE_ITEM_PENDING;?> && row.id == undefined"
										ng-click="removeAllocationItem( 'remittance', row )">
									<i class="glyphicon glyphicon-remove-circle"></i>
								</a>
								<input type="checkbox" value="{{ row.id }}"
										ng-if="row.allocation_item_status == <?php echo REMITTANCE_ITEM_REMITTED;?> || row.allocation_item_status == <?php echo REMITTANCE_ITEM_PENDING;?> && row.id"
										ng-click="getItemQuantities()"
										ng-model="row.markedVoid">
							</td>
						</tr>
						<tr ng-if="!allocationItem.remittances.length">
							<td colspan="8" class="text-center bg-warning">
								{{ data.remittancesEmptyText }}
							</td>
						</tr>
					</tbody>
					<tbody>
						<tr>
							<td colspan="8"><h5>Cash Items</td>
						</tr>
						<tr ng-repeat="row in allocationItem.cash_remittances"
								ng-class="{
										danger: row.markedVoid || row.allocation_item_status == <?php echo REMITTANCE_ITEM_VOIDED;?>,
										deleted: row.allocation_item_status == <?php echo REMITTANCE_ITEM_VOIDED;?>
									}">
							<td class="text-center">{{ $index + 1 }}</td>
							<td class="text-left">{{ row.cashier_shift_num }}</td>
							<td class="text-left">{{ row.category_name }}</td>
							<td class="text-left">{{ row.item_name }}</td>
							<td class="text-center">{{ row.allocated_quantity | number }}</td>
							<td class="text-right">{{ ( row.iprice_unit_price * row.allocated_quantity ) | number: 2  }}</td>
							<td class="text-center">{{ row.get( 'allocationItemStatus' ) }}</td>
							<td class="text-center" ng-if="data.editMode != 'view'" ng-switch on="row.allocation_item_status">
								<a href
										ng-if="row.allocation_item_status == <?php echo REMITTANCE_ITEM_PENDING;?> && row.id == undefined"
										ng-click="removeAllocationItem( 'remittance', row )">
									<i class="glyphicon glyphicon-remove-circle"></i>
								</a>
								<input type="checkbox" value="{{ row.id }}"
										ng-if="row.allocation_item_status == <?php echo REMITTANCE_ITEM_REMITTED;?> || row.allocation_item_status == <?php echo REMITTANCE_ITEM_PENDING;?> && row.id"
										ng-click="getItemQuantities()"
										ng-model="row.markedVoid">
							</td>
						</tr>
						<tr ng-if="!allocationItem.cash_remittances.length">
							<td colspan="8" class="text-center bg-warning">
								{{ data.cashRemittancesEmptyText }}
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>
		<!-- Ticket Sales -->
		<uib-tab heading="Ticket Sales" select="updatePhase( 'ticket_sales' )" index="2" disable="allocationItem.allocation_status == 1 && allocationItem.assignee_type == 1">
			<div class="panel panel-default" style="margin: 20px 0; height: 300px; overflow-y: auto;">
				<table class="table table-condensed">
					<thead>
						<tr>
							<th class="text-center">Row</th>
							<th class="text-left">Cashier Shift</th>
							<th class="text-left">Category</th>
							<th class="text-left">Item Description</th>
							<th class="text-center">Quantity</th>
							<th class="text-center">Status</th>
							<th class="text-center" ng-if="data.editMode != 'view'">Void</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="8"><h5>Ticket Items</h5></td>
						</tr>
						<tr ng-repeat="row in allocationItem.ticket_sales"
								ng-class="{
										danger: row.markedVoid || row.allocation_item_status == <?php echo TICKET_SALE_ITEM_VOIDED; ?>,
										deleted: row.allocation_item_status == <?php echo TICKET_SALE_ITEM_VOIDED; ?>
									}">
							<td class="text-center">{{ $index + 1 }}</td>
							<td class="text-left">{{ row.cashier_shift_num }}</td>
							<td class="text-left">{{ row.category_name }}</td>
							<td class="text-left">{{ row.item_name }}</td>
							<td class="text-center">{{ row.allocated_quantity | number }}</td>
							<td class="text-center">{{ row.get( 'allocationItemStatus' ) }}</td>
							<td class="text-center" ng-if="data.editMode != 'view'" ng-switch on="row.allocation_item_status">
								<a href
										ng-if="row.allocation_item_status == <?php echo TICKET_SALE_ITEM_PENDING;?> && row.id == undefined"
										ng-click="removeAllocationItem( 'ticket_sale', row )">
									<i class="glyphicon glyphicon-remove-circle"></i>
								</a>
								<input type="checkbox" value="{{ row.id }}"
										ng-if="row.allocation_item_status == <?php echo TICKET_SALE_ITEM_RECORDED;?> || row.allocation_item_status == <?php echo TICKET_SALE_ITEM_PENDING;?> && row.id"
										ng-model="row.markedVoid">
							</td>
						</tr>
						<tr ng-if="!allocationItem.ticket_sales.length">
							<td colspan="7" class="text-center bg-warning">
								No ticket sales items
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>
		<!-- Sale Items -->
		<uib-tab heading="Sales" select="updatePhase( 'sales' )" index="3" ng-if="allocationItem.assignee_type == 1"
				disable="allocationItem.allocation_status == 1 && allocationItem.assignee_type == 1">
			<div class="panel panel-default" style="margin: 20px 0; height: 300px; overflow-y: auto;">
				<table class="table table-condensed">
					<thead>
						<tr>
							<th class="text-center">Row</th>
							<th class="text-left">Cashier Shift</th>
							<th class="text-left">Item Description</th>
							<th class="text-center">Amount</th>
							<th class="text-center">Status</th>
							<th class="text-center" ng-if="data.editMode != 'view'">Void</th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="row in allocationItem.sales"
								ng-class="{
										danger: row.markedVoid || row.alsale_sales_item_status == <?php echo SALES_ITEM_VOIDED; ?>,
										deleted: row.alsale_sales_item_status == <?php echo SALES_ITEM_VOIDED; ?>
									}">
							<td class="text-center">{{ $index + 1 }}</td>
							<td class="text-left">{{ row.cashier_shift_num }}</td>
							<td class="text-left">{{ row.slitem_name }}</td>
							<td class="text-right">{{ ( row.slitem_mode === 1 ? row.alsale_amount : row.alsale_amount * -1 ) | number: 2 }}</td>
							<td class="text-center">{{ row.get( 'allocationSalesItemStatus' ) }}</td>
							<td class="text-center" ng-if="data.editMode != 'view'" ng-switch on="row.alsale_sales_item_status">
								<a href
										ng-if="row.alsale_sales_item_status == <?php echo SALES_ITEM_PENDING;?> && row.id == undefined"
										ng-click="removeSalesItem( row )">
									<i class="glyphicon glyphicon-remove-circle"></i>
								</a>
								<input type="checkbox" value="{{ row.id }}"
										ng-if="row.alsale_sales_item_status == <?php echo SALES_ITEM_RECORDED;?> || row.alsale_sales_item_status == <?php echo SALES_ITEM_PENDING;?> && row.id"
										ng-model="row.markedVoid">
							</td>
						</tr>
						<tr ng-if="!allocationItem.sales.length">
							<td colspan="6" class="text-center bg-warning">
								No sales items
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>
	</uib-tabset>
</div>

<!-- Input form -->
<div class="panel panel-default" ng-if="data.editMode != 'view'">
	<form>
		<div class="panel-body row">
			<!-- Item -->
			<div class="form-group col-sm-12 col-md-6 col-lg-5">
				<label class="control-label">Item</label>
				<select class="form-control ng-animate-disabled"
						ng-model="input.item"
						ng-change="onItemChange()"
						ng-options="item as item.item_name for item in data.inventoryItems track by item.id"
						ng-hide="data.allocationPhase == 'sales'">
				</select>
				<select class="form-control ng-animate-disabled"
						ng-model="input.salesItem"
						ng-options="item as item.slitem_name for item in data.salesItems track by item.id"
						ng-show="data.allocationPhase == 'sales'">
				</select>
			</div>

			<!-- Category -->
			<div class="form-group col-sm-12 col-md-6 col-lg-4" ng-if="data.allocationPhase != 'sales'">
				<label class="control-label">Category</label>
				<select class="form-control"
						ng-model="input.category"
						ng-options="category as category.category for category in data.categories track by category.id">
				</select>
			</div>

			<!-- Available Balance -->
			<div class="form-group col-sm-6 col-md-3 col-lg-1" ng-if="data.allocationPhase == 'allocation'">
				<label class="control-label">Available</label>
				<p class="form-control-static text-center">{{ ( input.item.quantity - input.item.reserved - input.itemReservedQuantity ) | number }}</p>
			</div>

			<!-- Quantity-->
			<div class="form-group col-sm-6 col-md-3 col-lg-2">
				<label class="control-label">{{ data.allocationPhase == 'sales' ? 'Amount' : 'Quantity' }}</label>
				<input type="number" class="form-control" min="1"
						ng-model="input.quantity"
						ng-keypress="addAllocationItem()">
			</div>
		</div>
	</form>
</div>

<!-- Form buttons -->
<div class="text-right">
	<button type="button" class="btn btn-primary" ng-click="saveAllocation()"
		ng-disabled="pendingAction"
		ng-if="allocationItem.canEdit()">
		<i class="glyphicon" ng-class="{ 'glyphicon-time': data.saveButton.icon == 'time', 'glyphicon-floppy-disk': data.saveButton.icon == 'floppy-disk' }"> </i>
		{{ data.saveButton.label }}
	</button>
	<button type="button" class="btn btn-success"
		ng-disabled="pendingAction || !allocationItem.canAllocate()"
		ng-if="allocationItem.canAllocate( true )"
		ng-click="allocateAllocation()">Mark as allocated
	</button>
	<button type="button" class="btn btn-success"
		ng-disabled="pendingAction || !allocationItem.canComplete()"
		ng-if="allocationItem.canComplete( true )"
		ng-click="completeAllocation()">Mark as completed
	</button>
	<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'allocations' })">Close</button>
</div>