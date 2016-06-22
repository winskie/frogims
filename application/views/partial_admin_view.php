<div>
	<uib-tabset id="adminTabSet" active="activeTab">
		<!-- General Settings -->
		<uib-tab heading="General" index="0" select="onTabSelect(0)">

		</uib-tab>

		<!-- Users -->
		<uib-tab heading="Users" index="1" select="onTabSelect(1)">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title pull-left">Users</h3>
					<div class="pull-right">
						<button class="btn btn-primary btn-sm" ui-sref="main.user()">
							<i class="glyphicon glyphicon-plus"></i> New user
						</button>&nbsp;
						<button class="btn btn-default btn-sm" ng-click="updateUsers( sessionData.currentStore.id )">
							<i class="glyphicon glyphicon-refresh"></i>
						</button>
					</div>
					<div class="clearfix"></div>
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
							<td>{{ user.group_id }}</td>
							<td>{{ lookup( 'userStatus', user.user_status ) }}</td>
							<td>
								<div class="btn-group btn-block" uib-dropdown>
									<button id="split-button" type="button" class="btn btn-primary col-sm-8 col-md-9" ui-sref="main.user({ userItem: user })">Edit</button>
									<button type="button" class="btn btn-primary col-sm-3 col-md-3" uib-dropdown-toggle>
										<span class="caret"></span>
									</button>
									<ul uib-dropdown-menu role="menu">
										<li role="menuitem"><a ui-sref="main.adjust({ adjustmentItem: adjustment })">Edit Adjustment...</a></li>
									</ul>
								</div>

							</td>
						</tr>
					</tbody>
				</table>
				<div class="text-center" ng-if="data.totals.users > filters.itemsPerPage">
					<uib-pagination
							total-items="data.totals.users"
							items-per-page="filters.itemsPerPage"
							max-size="5"
							boundary-link-numbers="true"
							ng-model="filters.users.page"
							ng-change="updateUsers()">
					</uib-pagination>
				</div>
			</div>
		</uib-tab>

		<!-- Groups -->
		<uib-tab heading="Groups" index="2" select="onTabSelect(2)">
		</uib-tab>

		<!-- Stores -->
		<uib-tab heading="Stores" index="3" select="onTabSelect(3)">
		</uib-tab>

		<!-- Items -->
		<uib-tab heading="Items" index="4" select="onTabSelect(4)">
		</uib-tab>
	</uib-tabset>
</div>