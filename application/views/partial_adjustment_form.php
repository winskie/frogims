<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Adjust Inventory</h3>
	</div>
	<div class="panel-body">
		<form class="form-horizontal">			
			<div class="form-group">
				<label for="items" class="control-label col-sm-2">Item to adjust</label>
				<div class="animate-switch-container" ng-switch on="adjustmentItem.adjustment_status">
					
					<div class="col-sm-5" ng-switch-when="<?php echo ADJUSTMENT_PENDING;?>">
						<select name="items" class="form-control"							
							ng-model="data.selectedItem" ng-change="changeItem()"
							ng-options="i.item_description for i in data.inventoryItems track by i.id">
						</select>
					</div>
					
					<div class="col-sm-5" ng-switch_default>
						<p class="form-control-static">{{ adjustmentItem.item_description }}</p>
					</div>
					
				</div>
			</div>
			
			<div class="form-group">
				<div class="animate-switch-container" ng-switch on="adjustmentItem.adjustment_status">
					
					<div ng-switch-when="<?php echo ADJUSTMENT_PENDING;?>">
						<label class="control-label col-sm-2">Current balance</label>
						<p class="form-control-static col-sm-2">{{ data.selectedItem.quantity | number }}</p>
					</div>
					
					<div ng-switch-default>
						<label class="control-label col-sm-2">Previous balance</label>
						<p class="form-control-static col-sm-2">{{ adjustmentItem.previous_quantity | number }}</p>
					</div>
					
				</div>
			</div>
			
			<div class="form-group required">
				<label for="quantity" class="control-label col-sm-2">Adjusted quantity</label>
				<div class="animate-switch-container" ng-switch on="adjustmentItem.adjustment_status">
					
					<div class="col-sm-2" ng-switch-when="<?php echo ADJUSTMENT_PENDING;?>">
						<input name="quantity" type="number" class="form-control text-right" ng-model="adjustmentItem.adjusted_quantity">
					</div>
					
					<div class="col-sm-2" ng-switch-default>
						<p class="form-control-static">{{ adjustmentItem.adjusted_quantity | number }}</p>
					</div>
					
				</div>
			</div>
			
			<div class="form-group required">
				<label for="reason" class="control-label col-sm-2">Reason</label>
				<div class="animate-switch-container" ng-switch on="adjustmentItem.adjustment_status">
					
					<div class="col-sm-8" ng-switch-when="<?php echo ADJUSTMENT_PENDING;?>">
						<input name="reason" type="text" class="form-control" placeholder="Enter reason for adjustment of inventory" ng-model="adjustmentItem.reason">
					</div>
					
					<div class="col-sm-8" ng-switch-default>
						<p class="form-control-static">{{ adjustmentItem.reason }}</p>
					</div>
					
				</div>
			</div>
            
            <div class="form-group" ng-show="adjustmentItem.adjustment_status == <?php echo ADJUSTMENT_APPROVED;?>">
                <label class="control-label col-sm-2">Approved by</label>
                <div class="col-sm-8">
                    <p class="form-control-static">{{ adjustmentItem.full_name }}</p>
                </div>
            </div>
		</form>
	</div>
	
	<div class="panel-footer">
		<div class="animate-switch-container" ng-switch on="adjustmentItem.adjustment_status">
			
			<div class="pull-right" ng-switch-when="<?php echo ADJUSTMENT_PENDING;?>">
				<button class="btn btn-primary" ng-click="saveAdjustment()">Save</button>
				<button class="btn btn-default" ng-click="approveAdjustment()">Approve</button>
				<button class="btn btn-default" ui-sref="main.store({ activeTab: 'adjustments' })">Cancel</button>
			</div>
			
			<div class="pull-right" ng-switch-default>
				<button class="btn btn-default" ui-sref="main.store({ activeTab: 'adjustments' })">Close</button>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>