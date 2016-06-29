<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">{{ data.editMode ? 'Mopping Collection #' + moppingItem.id : 'New Mopping Collection' }}</h3>
	</div>
	<div class="panel-body">
        <form class="form-horizontal row">
            <!-- Processing Date/Time -->
            <div class="form-group col-sm-4" ng-switch on="data.editMode">
                <label class="control-label col-sm-5">Processing Date</label>
                <div class="input-group col-sm-7" ng-switch-when="view">
                    <p class="form-control-static">{{ moppingItem.processing_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
                </div>
                <div class="input-group col-sm-7" ng-switch-default>
                    <input type="text" class="form-control" uib-datepicker-popup="{{ data.processingDatepicker.format }}" is-open="data.processingDatepicker.opened"
                        min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
                        ng-model="moppingItem.processing_datetime" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default" ng-click="showDatePicker( 'processing' )"><i class="glyphicon glyphicon-calendar"></i></button>
                    </span>
                </div>

            </div>

            <!-- Business Date -->
            <div class="form-group col-sm-4" ng-switch on="data.editMode">
                <label class="control-label col-sm-5">Business Date</label>
                <div class="input-group col-sm-7" ng-switch-when="view">
                    <p class="form-control-static">{{ moppingItem.business_date | date: 'yyyy-MM-dd' }}</p>
                </div>
                <div class="input-group col-sm-7" ng-switch-default>
                    <input type="text" class="form-control" uib-datepicker-popup="{{ data.businessDatepicker.format }}" is-open="data.businessDatepicker.opened"
                        min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
                        ng-model="moppingItem.business_date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default" ng-click="showDatePicker( 'business' )"><i class="glyphicon glyphicon-calendar"></i></button>
                    </span>
                </div>
            </div>

            <!-- Cashier Shift -->
            <div class="form-group col-sm-4" ng-switch on="data.editMode">
                <label class="control-label col-sm-5">Pullout Shift</label>
                <div class="input-group col-sm-7" ng-switch-when="view">
                    <p class="form-control-static">{{ data.selectedCashierShift.shift_num }}</p>
                </div>
                <div class="input-group col-sm-7" ng-switch-default>
                    <select class="form-control"
                            ng-model="data.selectedCashierShift"
                            ng-options="shift.shift_num for shift in data.cashierShifts track by shift.id"
                            ng-change="onChangeCashierShift()">
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Processed Items</h3>
    </div>
    <div class="panel-body" style="height: 400px; overflow-y: scroll">
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th class="text-center">Row</th>
                    <th class="text-left">Processed by</th>
                    <th class="text-left">Processed Item</th>
                    <th class="text-left">Package into</th>
                    <th class="text-center">Group</th>
                    <th class="text-left">Source</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center" ng-if="data.editMode != 'view'">{{ data.editMode ? 'Void' : '' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr ng-repeat="row in moppingItem.items" ng-class="{ danger:( !row.valid && row.group_id ) || row.moppedItemVoid, deleted: ( row.mopping_item_status == 2 ) }">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">{{ row.processor_name }}</td>
                    <td class="text-left">{{ row.mopped_item_name }}</td>
                    <td class="text-left">{{ row.convert_to_name || '---' }}</td>
                    <td class="text-center">{{ row.group_id ? 'G' + row.group_id : '---' }}</td>
                    <td class="text-left">{{ row.mopped_station_name }}</td>
                    <td class="text-center">{{ row.mopped_quantity | number }}</td>
                    <td class="text-center"  ng-if="data.editMode != 'view'" ng-switch on="data.editMode">
                        <div ng-switch-when="new">
                            <a href ng-click="removeMoppingItem( row )">
                                <i class="glyphicon glyphicon-remove-circle"></i>
                            </a>
                        </div>
                        <div ng-switch-when="edit" ng-if="row.mopping_item_status == 1">
                            <input type="checkbox" name="voidItem" value="{{ row.id }}"
                                    ng-change="onVoidChange( row )" ng-model="row.moppedItemVoid">
                        </div>
                    </td>
                </tr>
                <tr ng-if="moppingItem.items.length == 0">
                    <td colspan="{{ data.editMode == 'view' ? 7 : 8 }}">No mopping collection item to display</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="panel panel-default" ng-if="data.editMode == 'new'">
    <form>
        <div class="panel-body row">
            <div class="form-group col-sm-2">
                <label class="control-label">Processed by</label>
                <input type="text" class="form-control"
                        ng-model="input.processor"
                        ng-model-options="{ debounce: 500 }"
                        typeahead-editable="false"
                        uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
            </div>

            <div class="form-group col-sm-3">
                <label class="control-label">Processed Item</label>
                <select class="form-control"
                        ng-model="input.moppedItem"
                        ng-change="onItemChange()"
                        ng-options="item as item.item_name for item in data.moppedItems track by item.id">
                </select>
                <!--
                <input type="text" class="form-control"
                        ng-model="input.moppedItem"
                        ng-keypress="addMoppingItem( $event )"
                        typeahead-on-select="onItemChange()"
                        uib-typeahead="item as item.item_name for item in data.moppedItems | filter: $viewValue">
                -->
            </div>

            <div class="form-group col-sm-3">
                <label class="control-label">Package into</label>
                <select class="form-control"
                        ng-model="input.packAs"
                        ng-disabled="!data.packAsItems.length"
                        ng-options="item as item.item_name for item in data.packAsItems track by item.target_item_id">
                </select>
                <!--
                <input type="text" class="form-control" placeholder="Leave blank if not packed"
                        ng-model="input.packAs"
                        ng-keypress="addMoppingItem( $event )"
                        ng-enabled="data.packAsItems"
                        uib-typeahead="item as item.item_name for item in data.packAsItems | filter: $viewValue">
                -->
            </div>

            <div class="form-group col-sm-2">
                <label class="control-label">Source</label>
                <!--
                <input type="text" class="form-control"
                        ng-model="input.moppedSource"
                        ng-keypress="addMoppingItem( $event )"
                        uib-typeahead="station as station.station_name for station in data.moppedSource | filter: $viewValue">
                -->
                <select class="form-control"
                        ng-model="input.moppedSource"
                        ng-options="station.station_name for station in data.moppedSource track by station.id">
                </select>
            </div>

            <div class="form-group col-sm-2">
                <label class="control-label">Quantity</label>
                <input type="number" class="form-control" ng-keypress="addMoppingItem( $event )" ng-model="input.moppedQuantity">
            </div>
        </div>
    </form>
</div>

<div class="text-right">
    <button type="button" class="btn btn-primary" ng-click="saveCollection()" ng-if="data.editMode != 'view'">Save</button>
    <button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'collections' })">Close</button>
</div>