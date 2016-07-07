<div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">{{ groupItem.id ? 'Group #' + groupItem.id : 'New Group' }}</h3>
		</div>
		<div class="panel-body">
			<form class="form-horizontal">
				<!-- Group Name -->
				<div class="form-group">
					<label class="control-label col-sm-2">Group Name</label>
					<div class="animate-switch-container" ng-switch on="data.viewMode">
						<div class="col-sm-8 col-md-6 col-lg-4" ng-switch-when="edit">
							<input type="text" class="form-control" ng-model="groupItem.group_name">
						</div>

						<div class="col-sm-8 col-md-6 col-log-4" ng-switch-default>
							<p class="form-control-static">{{ groupItem.group_name }}</p>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="text-right">
		<div class="animate-switch-container" ng-switch on="data.viewMode">

			<div class="pull-right" ng-switch-when="edit">
				<button class="btn btn-primary" ng-click="saveGroup()">Save</button>
				<button class="btn btn-default" ui-sref="main.admin({ activeTab: 'groups' })">Close</button>
			</div>

			<div class="pull-right" ng-switch-default>
				<button class="btn btn-primary" ui-sref="main.admin({ activeTab: 'groups' })">Close</button>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>