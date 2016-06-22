<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">{{ userItem.id ? 'User Account #' + userItem.id : 'New User Account' }}</h3>
	</div>
	<div class="panel-body">
		<form class="form-horizontal">
			<!-- Username -->
			<div class="form-group">
				<label class="control-label col-sm-2">Username</label>
				<div class="animate-switch-container" ng-switch on="data.viewMode">
					<div class="col-sm-6 col-md-3 col-lg-2" ng-switch-when="edit">
						<input name="username" type="text" class="form-control"
								ng-model="userItem.username">
					</div>

					<div class="col-sm-6 col-md-3 col-lg-2" ng-switch-default>
						<p class="form-control-static">{{ userItem.username }}</p>
					</div>
				</div>
			</div>

			<!-- Full name -->
			<div class="form-group">
				<label class="control-label col-sm-2">Full name</label>
				<div class="animate-switch-container" ng-switch on="data.viewMode">
					<div class="col-sm-10 col-md-8 col-lg-5" ng-switch-when="edit">
						<input name="full_name" type="text" class="form-control"
								ng-model="userItem.full_name">
					</div>

					<div class="col-sm-10 col-md-8 col-lg-5" ng-switch-default>
						<p class="form-control-static">{{ userItem.full_name }}</p>
					</div>
				</div>
			</div>

			<!-- Position -->
			<div class="form-group">
				<label class="control-label col-sm-2">Position</label>
				<div class="animate-switch-container" ng-switch on="data.viewMode">
					<div class="col-sm-10 col-md-8 col-lg-5" ng-switch-when="edit">
						<input name="position" type="text" class="form-control"
								ng-model="userItem.position">
					</div>

					<div class="col-sm-10 col-md-8 col-lg-5" ng-switch-default>
						<p class="form-control-static">{{ userItem.position }}</p>
					</div>
				</div>
			</div>

			<!-- Old password -->
			<div class="form-group" ng-if="data.viewMode == 'edit' && ! data.isNew">
				<label class="control-label col-sm-2">Old password</label>
				<div class="col-sm-6 col-md-3 col-lg-2">
					<input type="password" class="form-control"
							ng-model="userItem.old_password">
				</div>
			</div>

			<!-- Password -->
			<div class="form-group" ng-if="data.viewMode == 'edit'">
				<label class="control-label col-sm-2">Password</label>
				<div class="col-sm-6 col-md-3 col-lg-2">
					<input type="password" class="form-control"
							ng-model="userItem.password">
				</div>
			</div>

			<!-- Password Confirmation -->
			<div class="form-group" ng-if="data.viewMode == 'edit'">
				<label class="control-label col-sm-2">Confirm password</label>
				<div class="col-sm-6 col-md-3 col-lg-2">
					<input type="password" class="form-control"
							ng-model="data.passwordConfirmation">
				</div>
			</div>

			<!-- User role -->
			<div class="form-group">
				<label for="items" class="control-label col-sm-2">Role</label>
				<div class="animate-switch-container" ng-switch on="data.viewMode">

					<div class="col-sm-6 col-md-3 col-lg-2" ng-switch-when="edit">
						<select class="form-control"
							ng-model="data.selectedRole" ng-change="changeRole()"
							ng-options="r.roleName for r in data.userRoles track by r.id">
						</select>
					</div>

					<div class="col-sm-6 col-md-3 col-lg-2" ng-switch_default>
						<p class="form-control-static">{{ lookup( 'userRoles', userItem.user_role ) }}</p>
					</div>

				</div>
			</div>

			<!-- Group -->
			<div class="form-group">
				<label for="items" class="control-label col-sm-2">Group</label>
				<div class="animate-switch-container" ng-switch on="data.viewMode">

					<div class="col-sm-6 col-md-3 col-lg-2" ng-switch-when="edit">
						<select class="form-control"
							ng-model="data.selectedGroup" ng-change="changeGroup()"
							ng-options="g.group_name for g in data.groups track by g.id">
						</select>
					</div>

					<div class="col-sm-6 col-md-3 col-lg-2" ng-switch_default>
						<p class="form-control-static">{{ lookup( 'userRoles', userItem.user_role ) }}</p>
					</div>

				</div>
			</div>

			<!-- User status -->
			<div class="form-group">
				<label for="items" class="control-label col-sm-2">Status</label>
				<div class="animate-switch-container" ng-switch on="data.viewMode">

					<div class="col-sm-6 col-md-3 col-lg-2" ng-switch-when="edit">
						<select class="form-control"
							ng-model="data.selectedStatus" ng-change="changeStatus()"
							ng-options="s.statusName for s in data.userStatus track by s.id">
						</select>
					</div>

					<div class="col-sm-6 col-md-3 col-lg-2" ng-switch_default>
						<p class="form-control-static">{{ lookup( 'userStatus', userItem.user_status ) }}</p>
					</div>

				</div>
			</div>
		</form>
	</div>
	<div class="panel-footer">
		<div class="animate-switch-container" ng-switch on="data.viewMode">

			<div class="pull-right" ng-switch-when="edit">
				<button class="btn btn-primary" ng-click="saveUser()">Save</button>
				<button class="btn btn-default" ui-sref="main.admin({ activeTab: 'users' })">Close</button>
			</div>

			<div class="pull-right" ng-switch-default>
				<button class="btn btn-primary" ui-sref="main.admin({ activeTab: 'users' })">Close</button>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>