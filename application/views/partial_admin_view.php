<div>
	<uib-tabset id="adminTabSet" active="activeTab">
		<!-- General Settings -->
		<uib-tab heading="General" index="0" select="onTabSelect('general')">
			<div class="alert alert-warning">
				<i class="glyphicon glyphicon-alert"></i> This section is still under development
			</div>
		</uib-tab>

		<!-- Users -->
		<uib-tab heading="Users" index="1" select="onTabSelect('users')">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">Users <span class="label label-default" ng-if="filters.users.filtered">Filtered</span></h3>
					<div class="pull-right">
						<button class="btn btn-default btn-sm btn-filter" ng-click="toggleFilters( 'users' )">
							<i class="glyphicon glyphicon-filter"></i> {{ filterPanels.users ? 'Hide' : 'Show' }} filters
						</button>&nbsp;
						<button class="btn btn-primary btn-sm" ui-sref="main.user({ referrer: 'main.admin' })">
							<i class="glyphicon glyphicon-plus"></i> New user
						</button>&nbsp;
						<button class="btn btn-default btn-sm" ng-click="updateUsers( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>

				<div class="panel-body">
					<!-- Filter Panel -->
					<div class="row filter_panel" ng-show="filterPanels.users">
						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Name</label>
								<input type="text" class="form-control" ng-model="filters.users.q">
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Position</label>
								<input type="text" class="form-control" ng-model="filters.users.position">
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Role</label>
								<select class="form-control"
										ng-model="filters.users.role"
										ng-options="role as role.roleName for role in widget.usersRole track by role.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Group</label>
								<select class="form-control"
										ng-model="filters.users.group"
										ng-options="group as group.group_name for group in widget.usersGroup track by group.id">
								</select>
							</div>
						</div>

						<div class="col-sm-4 col-md-3 col-lg-2">
							<div class="form-group">
								<label class="control-label">Status</label>
								<select class="form-control"
										ng-model="filters.users.status"
										ng-options="status as status.statusName for status in widget.usersStatus track by status.id">
								</select>
							</div>
						</div>

						<div>
							<div class="form-group">
								<button style="margin-top: 25px" class="btn btn-primary" ng-click="applyFilter( 'users' )">Apply</button>
								<button style="margin-top: 25px" class="btn btn-default" ng-click="clearFilter( 'users' )">Clear</button>
							</div>
						</div>

					</div>
					<table class="table table-condensed">
						<thead>
							<tr>
								<th class="text-center">ID</th>
								<th>Username</th>
								<th>Full Name</th>
								<th>Position</th>
								<th>Role</th>
								<th>Group</th>
								<th>Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<tr ng-repeat="user in data.users">
								<td class="text-center">{{ user.id }}</td>
								<td>{{ user.username }}</td>
								<td>{{ user.full_name }}</td>
								<td>{{ user.position }}</td>
								<td>{{ lookup( 'userRoles', user.user_role ) }}</td>
								<td>{{ user.group_name }}</td>
								<td>{{ lookup( 'userStatus', user.user_status ) }}</td>
								<td>
									<button id="split-button" type="button" class="btn btn-default btn-block" ui-sref="main.user({ userItem: user })">Edit</button>
								</td>
							</tr>
							<tr ng-show="data.users.length == 0">
								<td colspan="8" class="text-center">No user record available</td>
							</tr>
						</tbody>
					</table>
					<div class="text-center" ng-if="data.totals.users > filters.itemsPerPage">
						<uib-pagination
								total-items="data.totals.users"
								items-per-page="filters.itemsPerPage"
								max-size="5"
								boundary-link-numbers="true"
								ng-model="pagination.users"
								ng-change="updateUsers()">
						</uib-pagination>
					</div>
				</div>
			</div>
		</uib-tab>

		<!-- Groups -->
		<uib-tab heading="Groups" index="2" select="onTabSelect('groups')">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">Groups</h3>
					<div class="pull-right">
						<button class="btn btn-primary btn-sm" ui-sref="main.group()">
							<i class="glyphicon glyphicon-plus"></i> New group
						</button>&nbsp;
						<button class="btn btn-default btn-sm" ng-click="updateGroups( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
				</div>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th class="text-center">ID</th>
							<th>Group Name</th>
							<th class="text-center">Member Count</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr ng-repeat="group in data.groups">
							<td class="text-center">{{ group.id }}</td>
							<td>{{ group.group_name }}</td>
							<td class="text-center">{{ group.member_count }} {{ group.member_count == 1 ? 'member' : 'members' }}</td>
							<td>
								<button id="split-button" type="button" class="btn btn-default btn-block" ui-sref="main.group({ groupItem: group })">Edit</button>
							</td>
						</tr>
						<tr ng-if="!data.groups.length">
							<td colspan="4" class="text-center">No groups defined</td>
						</tr>
					</tbody>
				</table>
				<div class="text-center" ng-if="data.totals.groups > filters.itemsPerPage">
					<uib-pagination
							total-items="data.totals.groups"
							items-per-page="filters.itemsPerPage"
							max-size="5"
							boundary-link-numbers="true"
							ng-model="pagination.groups"
							ng-change="updateGroups()">
					</uib-pagination>
				</div>
			</div>
		</uib-tab>

		<!-- Stores -->
		<uib-tab heading="Stores" index="3" select="onTabSelect('stores')">
			<div class="alert alert-warning">
				<i class="glyphicon glyphicon-alert"></i> This section is still under development
			</div>
		</uib-tab>

		<!-- Items -->
		<uib-tab heading="Items" index="4" select="onTabSelect('items')">
			<div class="alert alert-warning">
				<i class="glyphicon glyphicon-alert"></i> This section is still under development
			</div>
		</uib-tab>

		<!-- Test -->
		<uib-tab heading="Testing" index="5" select="onTabSelect('testing')">
			<div class="panel panel-danger">
				<div class="panel-heading">
					<h3 class="panel-title">Database</h3>
				</div>
				<div class="panel-body">
					<button class="btn btn-danger" ng-click="resetDatabase( 'transactions' )">Reset Transactions</button>
					<button class="btn btn-danger" ng-click="resetDatabase()">Reset All</button>
					<button class="btn btn-danger" ng-click="newDatabase()">Recreate Database</button>
				</div>
			</div>
		</uib-tab>
	</uib-tabset>
</div>