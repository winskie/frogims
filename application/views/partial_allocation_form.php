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
                    <p class="form-control-static">{{ lookup( 'allocationStatus', allocationItem.allocation_status ).status }}</p>
                </div>
            </div>
        </form>
    </div>
</div>

<div>
    <uib-tabset justified="false">
        <uib-tab heading="Allocations" select="updatePhase( 'allocation' )">
            <div class="panel panel-default" style="margin: 20px 0; height: 300px; overflow-y: auto;">
                <table class="table table-condensed">
                    <thead>
                        <tr>
                            <th class="text-center">Row</th>
                            <th class="text-left">Time</th>
                            <th class="text-left">Cashier Shift</th>
                            <th class="text-left">Category</th>
                            <th class="text-left">Item Description</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" ng-if="data.editMode != 'view'">Void</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="row in allocationItem.allocations"
                                ng-class="{
                                        danger: row.allocationItemVoid || ( [<?php echo implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) );?>].indexOf( row.allocation_item_status ) != -1 ),
                                        deleted: ( [<?php echo implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) );?>].indexOf( row.allocation_item_status ) != -1 )
                                    }">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-left">{{ row.allocation_datetime | date : 'HH:mm:ss' }}</td>
                            <td class="text-left">{{ row.cashier_shift_num }}</td>
                            <td class="text-left">{{ row.category_name }}</td>
                            <td class="text-left">{{ row.item_name }}</td>
                            <td class="text-center">{{ row.allocated_quantity | number }}</td>
                            <td class="text-center">{{ lookup( 'allocationItemStatus', row.allocation_item_status ) }}</td>
                            <td class="text-center" ng-if="data.editMode != 'view'" ng-switch on="row.allocation_item_status">
                                <a href
                                        ng-if="row.allocation_item_status == <?php echo ALLOCATION_ITEM_SCHEDULED;?> && row.id == undefined"
                                        ng-click="removeAllocationItem( 'allocation', row )">
                                    <i class="glyphicon glyphicon-remove-circle"></i>
                                </a>
                                <input type="checkbox" value="{{ row.id }}"
                                        ng-if="row.allocation_item_status == <?php echo ALLOCATION_ITEM_ALLOCATED;?> || row.allocation_item_status == <?php echo ALLOCATION_ITEM_SCHEDULED;?> && row.id"
                                        ng-model="row.allocationItemVoid">
                            </td>
                        </tr>
                        <tr ng-if="!allocationItem.allocations.length">
                            <td colspan="8" class="text-center bg-warning">
                                No allocation items
                            </td>"
                        </tr>
                    </tbody>
                </table>
            </div>
        </uib-tab>

        <uib-tab select="updatePhase( 'remittance' )" disable="allocationItem.allocation_status == 1">
            <uib-tab-heading>
                {{ data.remittancesTabLabel }}
            </uib-tab-heading>
            <div class="panel panel-default" style="margin: 20px 0; height: 300px; overflow-y: auto;">
                <table class="table table-condensed">
                    <thead>
                        <tr>
                            <th class="text-center">Row</th>
                            <th class="text-left">Time</th>
                            <th class="text-left">Cashier Shift</th>
                            <th class="text-left">Item Description</th>
                            <th class="text-left">Category</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" ng-if="data.editMode != 'view'">Void</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="row in allocationItem.remittances"
                                ng-class="{
                                        danger: row.allocationItemVoid || row.allocation_item_status == <?php echo REMITTANCE_ITEM_VOIDED;?>,
                                        deleted: row.allocation_item_status == <?php echo REMITTANCE_ITEM_VOIDED;?>
                                    }">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-left">{{ row.allocation_datetime | date : 'HH:mm:ss' }}</td>
                            <td class="text-left">{{ row.cashier_shift_num }}</td>
                            <td class="text-left">{{ row.item_name }}</td>
                            <td class="text-left">{{ row.category_name }}</td>
                            <td class="text-center">{{ row.allocated_quantity | number }}</td>
                            <td class="text-center">{{ lookup( 'allocationItemStatus', row.allocation_item_status ) }}</td>
                            <td class="text-center" ng-if="data.editMode != 'view'" ng-switch on="row.allocation_item_status">
                                <a href
                                        ng-if="row.allocation_item_status == <?php echo REMITTANCE_ITEM_PENDING;?> && row.id == undefined"
                                        ng-click="removeAllocationItem( 'remittance', row )">
                                    <i class="glyphicon glyphicon-remove-circle"></i>
                                </a>
                                <input type="checkbox" value="{{ row.id }}"
                                        ng-if="row.allocation_item_status == <?php echo REMITTANCE_ITEM_REMITTED;?> || row.allocation_item_status == <?php echo REMITTANCE_ITEM_PENDING;?> && row.id"
                                        ng-model="row.allocationItemVoid">
                            </td>
                        </tr>
                        <tr ng-if="!allocationItem.remittances.length">
                            <td colspan="8" class="text-center bg-warning">
                                {{ data.remittancesEmptyText }}
                            </td>"
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
                <select class="form-control"
                        ng-model="input.item"
                        ng-change="getItemQuantities()"
                        ng-options="item as item.item_name for item in data.inventoryItems track by item.id">
                </select>
            </div>

            <!-- Category -->
            <div class="form-group col-sm-12 col-md-6 col-lg-4">
                <label class="control-label">Category</label>
                <select class="form-control"
                        ng-model="input.category"
                        ng-options="category as category.category for category in data.categories track by category.id">
                </select>
            </div>

            <!-- Balance -->
            <div class="form-group col-sm-6 col-md-3 col-lg-1">
                <label class="control-label">Balance</label>
                <p class="form-control-static text-center">{{ ( input.item.quantity - input.itemReservedQuantity ) | number }}</p>
            </div>

            <!-- Quantity-->
            <div class="form-group col-sm-6 col-md-3 col-lg-2">
                <label class="control-label">Quantity</label>
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
        ng-if="data.editMode != 'view' && checkPermissions( 'allocations', 'edit' )
                && ( allocationItem.allocation_status == <?php echo ALLOCATION_SCHEDULED;?> || allocationItem.allocation_status == <?php echo ALLOCATION_ALLOCATED;?> )">
        <i class="glyphicon" ng-class="{ 'glyphicon-time': allocationItem.allocation_status == 1, 'glyphicon-floppy-disk': allocationItem.allocation_status != 1 }"> </i>
        {{ allocationItem.allocation_status == 1 ? 'Schedule' : 'Update' }}
    </button>
    <button type="button" class="btn btn-success"
        ng-disabled="allocationItem.allocations.length == 0 || ! allocationItem.assignee"
        ng-if="allocationItem.allocation_status == <?php echo ALLOCATION_SCHEDULED;?> && checkPermissions( 'allocations', 'allocate' )"
        ng-click="allocateAllocation()">Mark as allocated
    </button>
    <button type="button" class="btn btn-success"
        ng-if="allocationItem.allocation_status == <?php echo ALLOCATION_ALLOCATED;?> && checkPermissions( 'allocations', 'complete' )"
        ng-click="completeAllocation()">Mark as completed
    </button>
    <button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'allocations' })">Close</button>
</div>