<div>
	<uib-tabset id="userTabSet" active="activeTab">
		<!-- User Information -->
		<uib-tab heading="User Information">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">{{ userItem.id ? 'User Account #' + userItem.id : 'New User Account' }}</h3>
				</div>
				<div class="panel-body">
					<form class="form-horizontal">
						<div class="row">
							<div class="col-sm-12 col-lg-6">
								<!-- Username -->
								<div class="form-group">
									<label class="control-label col-sm-4">Username</label>
									<div class="animate-switch-container" ng-switch on="data.viewMode">
										<div class="col-sm-8 col-md-6 col-lg-4" ng-switch-when="edit">
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
									<label class="control-label col-sm-4">Full name</label>
									<div class="animate-switch-container" ng-switch on="data.viewMode">
										<div class="col-sm-8 col-md-8 col-lg-6" ng-switch-when="edit">
											<input name="full_name" type="text" class="form-control"
													ng-model="userItem.full_name">
										</div>

										<div class="col-sm-8 col-md-8 col-lg-6" ng-switch-default>
											<p class="form-control-static">{{ userItem.full_name }}</p>
										</div>
									</div>
								</div>

								<!-- Position -->
								<div class="form-group">
									<label class="control-label col-sm-4">Position</label>
									<div class="animate-switch-container" ng-switch on="data.viewMode">
										<div class="col-sm-8 col-md-8 col-lg-6" ng-switch-when="edit">
											<input name="position" type="text" class="form-control"
													ng-model="userItem.position">
										</div>

										<div class="col-sm-8 col-md-8 col-lg-6" ng-switch-default>
											<p class="form-control-static">{{ userItem.position }}</p>
										</div>
									</div>
								</div>
							</div>

							<div class="col-sm-12 col-lg-6">

								<!-- Old password -->
								<div class="form-group" ng-if="data.viewMode == 'edit' && ! data.isNew">
									<label class="control-label col-sm-4">Old password</label>
									<div class="col-sm-8 col-md-6 col-lg-4">
										<input type="password" class="form-control"
												ng-model="userItem.old_password">
									</div>
								</div>

								<!-- Password -->
								<div class="form-group" ng-if="data.viewMode == 'edit'">
									<label class="control-label col-sm-4">New password</label>
									<div class="col-sm-8 col-md-6 col-lg-4">
										<input type="password" class="form-control"
												ng-model="userItem.password">
									</div>
								</div>

								<!-- Password Confirmation -->
								<div class="form-group" ng-if="data.viewMode == 'edit'">
									<label class="control-label col-sm-4">Confirm password</label>
									<div class="col-sm-8 col-md-6 col-lg-4">
										<input type="password" class="form-control"
												ng-model="data.passwordConfirmation">
									</div>
								</div>
							</div>
						</div>

						<?php
						if( is_admin() )
						{
						?>

						<div class="row">
							<div class="col-sm-12 col-lg-6">
								<!-- User role -->
								<div class="form-group">
									<label for="items" class="control-label col-sm-4">Role</label>
									<div class="animate-switch-container" ng-switch on="data.viewMode">

										<div class="col-sm-8 col-md-6 col-lg-4" ng-switch-when="edit">
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
									<label for="items" class="control-label col-sm-4">Group</label>
									<div class="animate-switch-container" ng-switch on="data.viewMode">

										<div class="col-sm-8 col-md-8 col-lg-6" ng-switch-when="edit">
											<select class="form-control"
												ng-model="data.selectedGroup" ng-change="changeGroup()"
												ng-options="g.group_name for g in data.groups track by g.id">
											</select>
										</div>

										<div class="col-sm-8 col-md-6 col-lg-4" ng-switch_default>
											<p class="form-control-static">{{ lookup( 'userRoles', userItem.user_role ) }}</p>
										</div>

									</div>
								</div>

								<!-- User status -->
								<div class="form-group">
									<label for="items" class="control-label col-sm-4">Status</label>
									<div class="animate-switch-container" ng-switch on="data.viewMode">

										<div class="col-sm-8 col-md-6 col-lg-4" ng-switch-when="edit">
											<select class="form-control"
												ng-model="data.selectedStatus" ng-change="changeStatus()"
												ng-options="s.statusName for s in data.userStatus track by s.id">
											</select>
										</div>

										<div class="col-sm-8 col-md-6 col-lg-4" ng-switch_default>
											<p class="form-control-static">{{ lookup( 'userStatus', userItem.user_status ) }}</p>
										</div>

									</div>
								</div>
							</div>
						</div>

						<?php
						}
						?>
					</form>
				</div>
			</div>
		</uib-tab>

		<uib-tab heading="Store Assignment">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Stores</h3>
				</div>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th class="text-center">
								<input type="checkbox" ng-model="data.checkAllStores" ng-if="sessionData.isAdmin" ng-click="toggleStores()">
							</th>
							<th>Store Name</th>
							<th>Type</th>
							<th>Location</th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="store in userItem.stores">
							<td class="text-center">
								<input type="checkbox" ng-model="store.registered" ng-if="sessionData.isAdmin">
							</td>
							<td>{{ store.store_name }}</td>
							<td>{{ lookup( 'storeTypes', store.store_type ) }}</td>
							<td>{{ store.store_location }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</uib-tab>
	</uib-tabset>

	<div class="text-right">
		<div class="animate-switch-container" ng-switch on="data.viewMode">

			<div class="pull-right" ng-switch-when="edit">
				<button class="btn btn-primary" ng-click="saveUser()">Save</button>
				<button class="btn btn-default" ng-click="close()">Close</button>
			</div>

			<div class="pull-right" ng-switch-default>
				<button class="btn btn-primary" ng-click="close()">Close</button>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
</div>