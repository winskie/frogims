<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title capitalize">{{ mode ? mode : 'Convert' }} Item</h3>
	</div>
	<div class="panel-body">
        <form>
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label class="control-label">Input item</label>
                        <select class="form-control"
                                ng-model="data.sourceInventory"
                                ng-options="inventory.item_name for inventory in sourceInventory track by inventory.id"
                                ng-change="updateConversionFactor()">
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Current balance</label>
                        <p class="form-control-static">{{ data.sourceInventory.quantity | number }}</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Input quantity</label>
                        <input type="number" class="form-control" name="inputQuantity" id="inputQuantity"
                                ng-model="conversionItem.source_quantity"
                                ng-change="calculateOutput( 'input' )">
                    </div>
                </div>
                <div class="col-sm-1">
                    
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label class="control-label">Output item</label>
                        <select class="form-control"
                                ng-model="data.targetInventory"
                                ng-options="inventory.item_name for inventory in items track by inventory.id"
                                ng-change="updateConversionFactor()">
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Current balance</label>
                        <p class="form-control-static">{{ data.targetInventory.quantity | number }}</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Output quantity</label>
                        <input type="number" class="form-control" name="outputQuantity" id="outputQuantity"
                                ng-model="conversionItem.target_quantity"
                                ng-change="calculateOutput( 'output' )">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-9">
                    <label class="control-label">Remarks</label>
                    <input type="text" class="form-control" placeholder="Enter conversion remarks"
                            ng-model="conversionItem.remarks">
                </div>
            </div>
            
            <div ng-show="messages.length" class="alert alert-danger" role="alert">
                <ul>
                    <li ng-repeat="message in messages">{{ message }}</li>
                </ul>
            </div>
        </form>
    </div>
    <div class="panel-footer">
        <div class="text-right">
            <button class="btn btn-primary capitalize" ng-disabled="!valid_conversion" ng-click="convert()">{{ mode ? mode : 'Convert' }}</button>
            <button class="btn btn-default">Close</button>
        </div>
    </div>
</div>