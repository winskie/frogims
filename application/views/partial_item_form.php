<h3>{{ item.item_name }}</h3>
<div>
	<uib-tabset id="itemTabSet" active="activeTab">
		<!-- Item Information -->
		<uib-tab heading="Item Information">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">{{ item.id ? 'Item #' + item.id : 'New Item' }}</h3>
				</div>
				<div class="panel-body">
					<form class="form-horizontal">
						<!-- Item Name -->
						<div class="form-group">
							<label class="control-label col-sm-2">Name</label>
							<div class="col-sm-4" ng-switch on="data.viewMode">
								<div ng-switch-when="edit">
									<input name="item_name" type="text" class="form-control" ng-model="item.item_name">
								</div>
								<div ng-switch-default>
									<p class="form-control-static">{{ item.item_name }}</p>
								</div>
							</div>
						</div>

						<!-- Item Description -->
						<div class="form-group">
							<label class="control-label col-sm-2">Description</label>
							<div class="col-sm-8" ng-switch on="data.viewMode">
								<div ng-switch-when="edit">
									<input name="item_description" type="text" class="form-control" ng-model="item.item_description">
								</div>
								<div ng-switch-default>
									<p class="form-control-static">{{ item.item_description }}</p>
								</div>
							</div>
						</div>

						<!-- Item Class -->
						<div class="form-group">
							<label class="control-label col-sm-2">Class</label>
							<div class="col-sm-2" ng-switch on="data.viewMode">
								<div ng-switch-when="edit">
									<select class="form-control"
										ng-model="data.selectedItemClass" ng-change="onChangeClass()"
										ng-options="ic.class for ic in data.itemClasses track by ic.id">
									</select>
								</div>
								<div ng-switch-default>
									<p class="form-control-static">{{ item.item_class }}</p>
								</div>
							</div>
						</div>

						<!-- Item Group -->
						<div class="form-group">
							<label class="control-label col-sm-2">Group</label>
							<div class="col-sm-2" ng-switch on="data.viewMode">
								<div ng-switch-when="edit">
									<select class="form-control"
										ng-model="data.selectedItemGroup" ng-change="onChangeGroup()"
										ng-options="ig.group for ig in data.selectedItemClass.groups track by ig.id">
									</select>
								</div>
								<div ng-switch-default>
									<p class="form-control-static">{{ item.item_class }}</p>
								</div>
							</div>
						</div>

						<!-- Item Unit -->
						<div class="form-group">
							<label class="control-label col-sm-2">Unit</label>
							<div class="col-sm-2" ng-switch on="data.viewMode">
								<div ng-switch-when="edit">
									<input name="item_unit" type="text" class="form-control" ng-model="item.item_unit">
								</div>
								<div ng-switch-default>
									<p class="form-control-static">{{ item.item_unit }}</p>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</uib-tab>

		<!-- Store Inventory -->
		<uib-tab heading="Inventories">
		</uib-tab>

		<!-- Item Conversions -->
		<uib-tab heading="Conversions">
		</uib-tab>
	</uib-tabset>

	<div class="text-right">
		<div class="animate-switch-container" ng-switch on="data.viewMode">

			<div class="pull-right" ng-switch-when="edit">
				<button class="btn btn-primary" ng-click="saveItem()">Save</button>
				<button class="btn btn-default" ng-click="close()">Close</button>
			</div>

			<div class="pull-right" ng-switch-default>
				<button class="btn btn-primary" ng-click="close()">Close</button>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>