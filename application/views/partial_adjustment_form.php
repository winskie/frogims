<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Adjust Inventory</h3>
	</div>
	<div class="panel-body">
		<form class="form-horizontal">
			<div class="form-group" ng-switch on="data.editMode">
				<label for="items" class="control-label col-sm-2">Item to adjust</label>

				<div class="col-sm-5" ng-switch-when="edit">
					<select name="items" class="form-control"
						ng-model="data.selectedItem" ng-change="changeItem()"
						ng-options="i.item_description + ( i.parent_item_name ? ' &laquo;' + i.parent_item_name + '&raquo;' : '' ) for i in data.inventoryItems track by i.id">
					</select>
				</div>

				<div class="col-sm-5" ng-switch-default>
					<p class="form-control-static">{{ adjustmentItem.item_description }}</p>
				</div>

			</div>

			<div class="form-group" ng-switch on="data.editMode">

				<div ng-switch-when="edit">
					<label class="control-label col-sm-2">Current balance</label>
					<p class="form-control-static col-sm-2">{{ data.selectedItem.quantity | number }}</p>
				</div>

				<div ng-switch-default>
					<label class="control-label col-sm-2">Previous balance</label>
					<p class="form-control-static col-sm-2">{{ !adjustmentItem.previous_quantity && adjustmentItem.adjustment_status == 1  ? '---' : ( adjustmentItem.previous_quantity | number ) }}</p>
				</div>
			</div>

			<div class="form-group required" ng-switch on="data.editMode">
				<label for="quantity" class="control-label col-sm-2">Adjusted quantity</label>

				<div class="col-sm-2" ng-switch-when="edit">
					<input name="quantity" type="number" class="form-control" ng-model="adjustmentItem.adjusted_quantity">
				</div>

				<div class="col-sm-2" ng-switch-default>
					<p class="form-control-static">{{ adjustmentItem.adjusted_quantity | number }}</p>
				</div>
			</div>

			<div class="form-group required" ng-switch on="data.editMode">
				<label for="reason" class="control-label col-sm-2">Reason</label>

				<div class="col-sm-8" ng-switch-when="edit">
					<input name="reason" type="text" class="form-control" placeholder="Enter reason for adjustment of inventory" ng-model="adjustmentItem.reason">
				</div>

				<div class="col-sm-8" ng-switch-default>
					<p class="form-control-static">{{ adjustmentItem.reason }}</p>
				</div>
			</div>

			<div class="form-group" ng-switch on="data.editMode">
				<label class="control-label col-sm-2">Transaction</label>
				<div class="col-sm-12 col-md-6 col-lg-3" ng-switch-when="edit">
					<select class="form-control"
						ng-model="data.selectedTransactionType"
						ng-options="type.typeName group by type.module for type in data.transactionTypes track by type.id"
						ng-change="changeTransactionType()">
					</select>
				</div>

				<div class="col-sm-12 col-md-6 col-lg-3" ng-switch-default>
					<p class="form-control-static">{{ lookup( 'transactionTypes', '' + adjustmentItem.adj_transaction_type ) }}</p>
				</div>
			</div>

			<div class="form-group" ng-switch on="data.editMode">
				<label class="control-label col-sm-2">Transaction ID</label>
				<div class="col-sm-6 col-md-4 col-lg-2" ng-switch-when="edit">
					<input type="number" class="form-control" ng-disabled="! data.selectedTransactionType.id" ng-model="adjustmentItem.adj_transaction_id">
				</div>

				<div class="col-sm-6 col-md-4 col-lg-2" ng-switch-default>
					<p class="form-control-static">{{ adjustmentItem.adj_transaction_id ? adjustmentItem.adj_transaction_id : '---' }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="control-label col-sm-2">Status</label>
				<div class="col-sm-8">
					<p class="form-control-static">{{ adjustmentItem.get( 'adjustmentStatus' ) }}</p>
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
		<div class="animate-switch-container" ng-switch on="data.editMode">

			<div class="pull-right" ng-switch-when="edit">
				<button class="btn btn-primary"
						ng-click="saveAdjustment()"
						ng-if="adjustmentItem.canEdit()"
						ng-disabled="pendingAction">{{ adjustmentItem.id ? 'Update' : 'Save' }}</button>
				<button class="btn btn-success"
						ng-click="approveAdjustment()"
						ng-if="adjustmentItem.canApprove( TRUE )"
						ng-disabled="pendingAction">
					<i class="glyphicon glyphicon-ok"></i> Approve
				</button>
				<button class="btn btn-default"
						ng-click="cancelAdjustment()"
						ng-if="adjustmentItem.canCancel( TRUE )"
						ng-disabled="pendingAction">Cancel</button>
				<button class="btn btn-default" ui-sref="main.store({ activeTab: 'adjustments' })">Close</button>
			</div>

			<div class="pull-right" ng-switch-default>
				<button class="btn btn-success"
						ng-click="approveAdjustment()"
						ng-if="adjustmentItem.canApprove( TRUE )"
						ng-disabled="pendingAction">
					<i class="glyphicon glyphicon-ok"></i> Approve
				</button>
				<button class="btn btn-default"
						ng-click="cancelAdjustment()"
						ng-if="adjustmentItem.canCancel( TRUE )"
						ng-disabled="pendingAction">Cancel</button>
				<button class="btn btn-primary" ui-sref="main.store({ activeTab: 'adjustments' })">Close</button>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>