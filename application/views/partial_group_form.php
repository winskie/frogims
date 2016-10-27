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

	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Group Permissions</h3>
		</div>
		<div class="panel-body">
			<form class="form-horizontal">
				<!-- Transactions -->
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Transactions</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_transaction" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_transaction" uib-btn-radio="'view'">view</label>
						</div>
					</div>
				</div>

				<!-- Shift Turnovers -->
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Shift Turnovers</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_shift_turnover" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_shift_turnover" uib-btn-radio="'view'">view</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_shift_turnover" uib-btn-radio="'edit'">edit</label>
						</div>
					</div>
				</div>

				<!-- Transfers -->
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Transfers</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer" uib-btn-radio="'view'">view</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer" uib-btn-radio="'edit'">edit</label>
						</div>
					</div>

					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Can approve?</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer_approve" uib-btn-radio="false">no</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer_approve" uib-btn-radio="true">yes</label>
						</div>
					</div>
				</div>

				<!-- Transfer Validations -->
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Transfer Validations</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer_validation" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer_validation" uib-btn-radio="'view'">view</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer_validation" uib-btn-radio="'edit'">edit</label>
						</div>
					</div>

					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Can complete?</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer_validation_complete" uib-btn-radio="false">no</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_transfer_validation_complete" uib-btn-radio="true">yes</label>
						</div>
					</div>
				</div>

				<!-- Adjustments -->
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Adjustments</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_adjustment" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_adjustment" uib-btn-radio="'view'">view</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_adjustment" uib-btn-radio="'edit'">edit</label>
						</div>
					</div>

					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Can approve?</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_adjustment_approve" uib-btn-radio="false">no</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_adjustment_approve" uib-btn-radio="true">yes</label>
						</div>
					</div>
				</div>

				<!-- Conversions -->
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Conversions</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_conversion" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_conversion" uib-btn-radio="'view'">view</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_conversion" uib-btn-radio="'edit'">edit</label>
						</div>
					</div>

					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Can approve?</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_conversion_approve" uib-btn-radio="false">no</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_conversion_approve" uib-btn-radio="true">yes</label>
						</div>
					</div>
				</div>

				<!-- Collection -->
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Collections</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_collection" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_collection" uib-btn-radio="'view'">view</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_collection" uib-btn-radio="'edit'">edit</label>
						</div>
					</div>
				</div>

				<!-- Allocation -->
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Allocations</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_allocation" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_allocation" uib-btn-radio="'view'">view</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_allocation" uib-btn-radio="'edit'">edit</label>
						</div>
					</div>

					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-64">Can allocate?</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_allocation_allocate" uib-btn-radio="false">no</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_allocation_allocate" uib-btn-radio="true">yes</label>
						</div>
					</div>
					<div class="form-group col-sm-12 col-md-6 col-lg-4 hidden-sm visible-md-6 hidden-lg"></div>
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Can complete?</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_allocation_complete" uib-btn-radio="false">no</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_allocation_complete" uib-btn-radio="true">yes</label>
						</div>
					</div>
				</div>

				<!-- Manage Store Users -->
				<!--
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-4">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">Manage store users</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.group_perm_collection" uib-btn-radio="'none'">none</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_collection" uib-btn-radio="'view'">view</label>
							<label class="btn btn-default" ng-model="groupItem.group_perm_collection" uib-btn-radio="'edit'">edit</label>
						</div>
					</div>
				</div>
				-->
			</form>
		</div>
	</div>

	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Dashboard Widgets</h3>
		</div>
		<div class="panel-body">
			<form class="form-horizontal">
				<div class="row">
					<div class="form-group col-sm-12 col-md-6 col-lg-6" ng-repeat="widget in data.widgets">
						<label class="control-label col-sm-6 col-md-6 col-lg-6">{{ widget.label }}</label>
						<div class="btn-group">
							<label class="btn btn-default" ng-model="groupItem.widgets[widget.name]" uib-btn-radio="false">no</label>
							<label class="btn btn-default" ng-model="groupItem.widgets[widget.name]" uib-btn-radio="true">yes</label>
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