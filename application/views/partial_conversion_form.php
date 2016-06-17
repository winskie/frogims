<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title capitalize">{{ mode ? mode : 'Convert' }} Item</h3>
	</div>
	<div class="panel-body">
        <form>
            <!-- Date -->
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label class="control-label">Date</label>
                        <div class="animate-switch-container" ng-switch on="conversionItem.conversion_status">
                            <div class="input-group" ng-switch-when="<?php echo CONVERSION_PENDING;?>">
                                <input type="text" class="form-control" uib-datepicker-popup="{{ data.conversionDatepicker.format }}" is-open="data.conversionDatepicker.opened"
                                    min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
                                    ng-model="conversionItem.conversion_datetime" ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" ng-click="showDatePicker( 'conversion' )"><i class="glyphicon glyphicon-calendar"></i></button>
                                </span>
                            </div>
                            <div ng-switch-default>
                                <p class="form-control-static">{{ conversionItem.conversion_datetime | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-4">
                    <!-- Input item-->
                    <div class="form-group">
                        <label class="control-label">Input item</label>
                        <div class="animate-switch-container" ng-switch on="conversionItem.conversion_status">
                            <select class="form-control"
                                    ng-model="data.sourceInventory"
                                    ng-options="inventory.item_name for inventory in data.sourceItems track by inventory.id"
                                    ng-change="onInputItemChange()"
                                    ng-switch-when="<?php echo CONVERSION_PENDING;?>">
                            </select>
                            <p class="form-control-static" ng-switch_default>{{ data.sourceInventory.item_description }}</p>
                        </div>
                    </div>

                    <!-- Output item -->
                    <div class="form-group">
                        <label class="control-label">Output item</label>
                        <div class="animate-switch-container" ng-switch on="conversionItem.conversion_status">
                            <select class="form-control"
                                    ng-model="data.targetInventory"
                                    ng-options="inventory.item_name for inventory in data.targetItems track by inventory.id"
                                    ng-change="onOutputItemChange()"
                                    ng-switch-when="<?php echo CONVERSION_PENDING;?>">
                            </select>
                            <p class="form-control-static" ng-switch_default>{{ data.targetInventory.item_description }}</p>
                        </div>
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
                    <div class="form-group">
                        <label class="control-label">Input quantity</label>
                        <div class="animate-switch-container" ng-switch on="conversionItem.conversion_status">
                            <input type="number" class="form-control" name="inputQuantity" id="inputQuantity"
                                    step="{{ data.input.step }}" min="{{ data.input.min }}"
                                    ng-model="conversionItem.source_quantity"
                                    ng-change="calculateOutput()"
                                    ng-switch-when="<?php echo CONVERSION_PENDING;?>">
                            <p class="form-control-static" ng-switch_default>{{ conversionItem.source_quantity | number }}</p>
                        </div>
                    </div>

                    <!-- Output quantity -->
                    <div class="form-group">
                        <label class="control-label">Output quantity</label>
                        <p class="form-control-static text-center">{{ conversionItem.target_quantity | number }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <!-- Remarks -->
                <div class="form-group col-sm-9">
                    <label class="control-label">Remarks</label>
                    <div class="animate-switch-container" ng-switch on="conversionItem.conversion_status">
                        <input type="text" class="form-control" placeholder="Enter conversion remarks"
                                ng-model="conversionItem.remarks"
                                ng-switch-when="<?php echo CONVERSION_PENDING;?>">
                        <p class="form-control-static" ng-switch_default>{{ conversionItem.remarks }}</p>
                    </div>
                </div>
            </div>

            <div ng-show="data.messages.length" class="alert alert-danger" role="alert">
                <ul>
                    <li ng-repeat="message in data.messages">{{ message }}</li>
                </ul>
            </div>
        </form>
    </div>
    <div class="panel-footer">
        <div class="animate-switch-container" ng-switch on="conversionItem.conversion_status">

            <div class="text-right" ng-switch-when="<?php echo CONVERSION_PENDING;?>">
                <button class="btn btn-default" ng-click="saveConversion()">Save</button>
                <button class="btn btn-primary capitalize" ng-disabled="!data.valid_conversion" ng-click="approveConversion()">{{ mode ? mode : 'Convert' }}</button>
                <button class="btn btn-default" ui-sref="main.store({ activeTab: 'conversions' })">Close</button>
            </div>

            <div class="pull-right" ng-switch-default>
                <button class="btn btn-primary" ui-sref="main.store({ activeTab: 'conversions' })">Close</button>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
</div>