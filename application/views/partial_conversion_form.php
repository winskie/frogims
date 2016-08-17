<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title capitalize">{{ mode ? mode : 'Convert' }} Item</h3>
	</div>
	<div class="panel-body">
        <form>
            <!-- Status -->
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label class="control-label">Status</label>
                        <p class="form-control-static">{{ lookup( 'conversionStatus', conversionItem.conversion_status ) }}</p>
                    </div>
                </div>
            </div>

            <!-- Date -->
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group" ng-switch on="data.editMode">
                        <label class="control-label">Date</label>

                        <div class="input-group" ng-switch-when="edit">
                            <input type="text" class="form-control" uib-datepicker-popup="{{ data.conversionDatepicker.format }}" is-open="data.conversionDatepicker.opened"
                                min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
                                ng-model="conversionItem.conversion_datetime" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" ng-click="showDatePicker( 'conversion' )"><i class="glyphicon glyphicon-calendar"></i></button>
                            </span>
                        </div>

                        <p class="form-control-static" ng-switch-default>{{ conversionItem.conversion_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4">
                    <!-- Input item-->
                    <div class="form-group" ng-switch on="data.editMode">
                        <label class="control-label">Input item</label>

                        <select class="form-control"
                                ng-model="data.sourceInventory"
                                ng-options="inventory.item_name for inventory in data.sourceItems track by inventory.id"
                                ng-change="onInputItemChange()"
                                ng-switch-when="edit">
                        </select>

                        <p class="form-control-static" ng-switch-default>{{ data.sourceInventory.item_description }}</p>
                    </div>

                    <!-- Output item -->
                    <div class="form-group" ng-switch on="data.editMode">
                        <label class="control-label">Output item</label>
                        <select class="form-control"
                                ng-model="data.targetInventory"
                                ng-options="inventory.item_name for inventory in data.targetItems track by inventory.id"
                                ng-change="onOutputItemChange()"
                                ng-switch-when="edit">
                        </select>

                        <p class="form-control-static" ng-switch-default>{{ data.targetInventory.item_description }}</p>
                    </div>
                </div>

                <div class="col-sm-2">
                    <!-- Input balance -->
                    <div class="form-group text-center">
                        <label class="control-label">Current balance</label>
                        <p class="form-control-static">{{ data.sourceInventory.quantity | number }}</p>
                    </div>

                    <!-- Output balance -->
                    <div class="form-group text-center">
                        <label class="control-label">Current balance</label>
                        <p class="form-control-static">{{ data.targetInventory.quantity | number }}</p>
                    </div>
                </div>

                <div class="col-sm-2">
                    <!-- Input quantity -->
                    <div class="form-group text-center" ng-switch on="data.editMode">
                        <label class="control-label">Input quantity</label>
                        <input type="number" class="form-control" name="inputQuantity" id="inputQuantity"
                                step="{{ data.input.step }}" min="{{ data.input.min }}"
                                ng-model="conversionItem.source_quantity"
                                ng-change="calculateOutput()"
                                ng-switch-when="edit">

                        <p class="form-control-static" ng-switch-default>{{ conversionItem.source_quantity | number }}</p>
                    </div>

                    <!-- Output quantity -->
                    <div class="form-group text-center">
                        <label class="control-label">Output quantity</label>
                        <p class="form-control-static text-center">{{ conversionItem.target_quantity | number }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Remarks -->
                <div class="form-group col-sm-9" ng-switch on="data.editMode">
                    <label class="control-label">Remarks</label>
                    <input type="text" class="form-control" placeholder="Enter conversion remarks"
                            ng-model="conversionItem.remarks"
                            ng-switch-when="edit">

                    <p class="form-control-static" ng-switch-default>{{ conversionItem.remarks ? conversionItem.remarks : '---' }}</p>
                </div>
            </div>

            <div ng-show="data.messages.length" class="alert alert-danger" role="alert">
                <ul>
                    <li ng-repeat="message in data.messages">{{ message }}</li>
                </ul>
            </div>
        </form>
    </div>
</div>
<div class="text-right">
    <button class="btn btn-primary" ng-click="saveConversion()"
            ng-if="data.editMode != 'view' && checkPermissions( 'conversions', 'edit' )">Save</button>
    <button class="btn btn-success capitalize"
            ng-disabled="!data.valid_conversion"
            ng-if="conversionItem.conversion_status == <?php echo CONVERSION_PENDING;?> && checkPermissions( 'conversions', 'approve' )"
            ng-click="approveConversion()">
        {{ mode ? mode : 'Approve' }}
    </button>
    <button class="btn btn-default" ui-sref="main.store({ activeTab: 'conversions' })">Close</button>
</div>