app.controller( 'AdminController', [ '$scope', '$state', '$stateParams', 'session', 'adminData', 'notifications',
	function( $scope, $state, $stateParams, session, adminData, notifications )
	{
		$scope.data = adminData.data;

		$scope.filters = angular.copy( adminData.filters );
		$scope.tabs = {
				general: { index: 0, title: 'General' },
				users: { index: 1, title: 'Users' },
				groups: { index: 2, title: 'Groups' },
				stores: { index: 3, title: 'Stores' },
				items: { index: 4, title: 'Items' },
				testing: { index: 5, title: 'Testing' }
			};

		if( $stateParams.activeTab )
		{
			$scope.activeTab = $scope.tabs[$stateParams.activeTab].index;
		}
		else
		{
			$scope.activeTab = 0;
		}

		$scope.widget = {
				usersRole: angular.copy( adminData.data.userRoles ),
				usersGroup: angular.copy( adminData.data.groups ),
				usersStatus: angular.copy( adminData.data.userStatus ),
			};

		$scope.widget.usersRole.unshift({ id: null, roleName: 'All' });
		$scope.widget.usersGroup.unshift({ id: null, group_name: 'All' });
		$scope.widget.usersStatus.unshift({ id: null, statusName: 'All' });

		$scope.pagination = adminData.pagination;

		$scope.filterPanels = {
				users: false,
				groups: false,
				items: false,
				stores: false,
			};

		$scope.toggleFilters = function( tab )
			{
				$scope.filterPanels[tab] = !$scope.filterPanels[tab];
			};

		$scope.applyFilter = function( tab )
			{
				$scope.pagination[tab] = 1;
				$scope.filters[tab].filtered = true;
				angular.copy( $scope.filters[tab], adminData.filters[tab] );

				switch( tab )
				{
					case 'users':
						$scope.updateUsers();
						break;

					case 'groups':
						$scope.updateGroups();
						break;

					case 'items':
						$scope.updateItems();
						break;

					default:
						error.console( 'Unknown filter group' );
				}
			};

		$scope.clearFilter = function( tab )
			{
				$scope.pagination[tab] = 1;
				angular.copy( adminData.clearFilter( tab ), $scope.filters[tab] );

				$scope.applyFilter( tab );
				$scope.filters[tab].filtered = false;
				adminData.filters[tab].filtered = false;
			};

		// Refresh/update functions
		$scope.updateUsers = adminData.getUsers;
		$scope.updateGroups = adminData.getGroups;

		$scope.onTabSelect = function( tab )
			{
				session.data.previousTab = tab;
			};

		$scope.resetDatabase = function( mode )
			{
				if( ! mode ) mode = '';
				window.location = "/frogims/index.php/installer/reset_database/" + mode;
			};

		$scope.newDatabase = function()
			{
				window.location = "/frogims/index.php/installer/new_database";
			};
	}
]);

app.controller( 'UserController', [ '$scope', '$state', '$stateParams', '$filter', 'session', 'appData', 'adminData', 'notifications',
	function( $scope, $state, $stateParams, $filter, session, appData, adminData, notifications )
	{
		function generateStoreList( registeredStores )
		{
			var n = appData.data.stores.length;
			var m = 0;
			if( registeredStores )
			{
				m = registeredStores.length;
			}

			var registeredStoreIds = [];
			var stores = [];

			for( var i = 0; i < m; i++ )
			{
				registeredStoreIds.push( registeredStores[i].id );
			}

			if( session.data.isAdmin )
			{
				for( var i = 0; i < n; i++ )
				{
					var s = appData.data.stores[i];
					s['registered'] = registeredStoreIds.indexOf( s.id ) != -1;
					stores.push( s );
				}
			}
			else
			{
				for( var i = 0; i < n; i++ )
				{
					var s = appData.data.stores[i];
					if( registeredStoreIds.indexOf( s.id ) != -1 )
					{
						stores.push( appData.data.stores[i] );
					}
				}
			}

			return stores;
		}

		$scope.data = {
			userRoles: angular.copy( adminData.data.userRoles ),
			selectedRole: { id: 2, roleName: 'User' },
			userStatus: angular.copy( adminData.data.userStatus),
			selectedStatus: { id: 1, statusName: 'Active' },
			groups: angular.copy( adminData.data.groups ),
			selectedGroup: { id: null, group_name: 'None' },
			viewMode: 'edit',
			isNew: true,
			oldPasswordLabel: session.data.isAdmin ? 'Admin password' : 'Old password',
			passwordConfirmation: null,
			checkAllStores: false }

		$scope.data.groups.unshift({ id: null, group_name: 'None' });

		$scope.userItem = {
				username: null,
				full_name: null,
				position: null,
				old_password: null,
				password: null,
				user_status: 1, // USER_ACTIVE
				user_role: 2, // USER_ROLE_USER
				group_id: null
			};

		$scope.changeStatus = function()
			{
				$scope.userItem.user_status = $scope.data.selectedStatus.id;
			};

		$scope.changeRole = function()
			{
				$scope.userItem.user_role = $scope.data.selectedRole.id;
			};

		$scope.changeGroup = function()
			{
				$scope.userItem.group_id = $scope.data.selectedGroup.id;
			};

		$scope.toggleStores = function()
			{
				var n = $scope.userItem.stores.length;

				for( var i = 0; i < n; i++ )
				{
					$scope.userItem.stores[i].registered = $scope.data.checkAllStores;
				}
			};

		$scope.close = function()
			{
				var params = {};
				if( session.data.previousTab )
				{
					params['activeTab'] = session.data.previousTab;
				}
				$state.go( session.data.previousState, params );
			};

		$scope.checkData = function()
			{
				if( ! $scope.userItem.username )
				{
					notifications.alert( 'Missing username', 'error' );
					return false;
				}

				if( ! $scope.userItem.full_name )
				{
					notifications.alert( 'Missing full name', 'error' );
					return false;
				}

				if( ! $scope.userItem.id && ! $scope.userItem.password )
				{
					notifications.alert( 'Password cannot be empty', 'error' );
					return false;
				}

				if( $scope.userItem.old_password && ( ! $scope.userItem.password || ! $scope.data.passwordConfirmation ) )
				{
					notifications.alert( 'Password cannot be empty', 'error' );
					return false;
				}

				if( $scope.userItem.password != $scope.data.passwordConfirmation )
				{
					notifications.alert( 'Passwords do not match', 'error' );
					return false;
				}

				return true;
			};

		$scope.prepareData = function()
			{
				var stores = $scope.userItem.stores;
				var n = stores.length;
				var data = angular.copy( $scope.userItem );
				var storesAssigned = [];
				for( var i = 0; i < n; i++ )
				{
					if( stores[i].registered )
					{
						storesAssigned.push( parseInt( stores[i].id ) );
					}
				}

				data.stores = storesAssigned;

				// TODO: even though use has admin right does the admin have users edit privilege?
				data.assign_stores = session.data.isAdmin;

				return data;
			};

		$scope.saveUser = function()
			{
				if( $scope.checkData() )
				{
					var data = $scope.prepareData();

					adminData.saveUser( data ).then(
						function( response )
						{
							if( data.id == session.data.currentUser.id )
							{
								session.updateCurrentStores();
							}
							adminData.refresh( 'user' );
							notifications.alert( 'User record saved', 'success' );

							var params = {};
							if( session.data.previousTab )
							{
								params['activeTab'] = session.data.previousTab;
							}
							$state.go( session.data.previousState, params );
						},
						function( reason )
						{
							notifications.alert( reason, 'error' );
							console.error( reason );
						});
				}
			};

		if( $stateParams.userItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'edit';
			adminData.getUser( $stateParams.userItem.id, { include: 'stores' } ).then(
				function( response )
				{
					var selectedGroup = $filter( 'filter' )( adminData.data.groups, { id: $stateParams.userItem.group_id }, true );

					$scope.userItem = response.data;

					$scope.data.selectedRole = $filter( 'filter' )( adminData.data.userRoles, { id: $stateParams.userItem.user_role }, true )[0];
					$scope.data.selectedGroup = ( selectedGroup && selectedGroup.length > 0 ) ? selectedGroup[0] : { id: null, group_name: 'None' };
					$scope.data.selectedStatus = $filter( 'filter' )( adminData.data.userStatus, { id: $stateParams.userItem.user_status }, true )[0];
					$scope.data.isNew = false;

					$scope.userItem.stores = generateStoreList( $scope.userItem.stores );
				},
				function( reason )
				{
					console.error( reason );
				} );
		}
		else
		{
			$scope.userItem.stores = generateStoreList();
		}
	}
]);

app.controller( 'GroupController', [ '$scope', '$state', '$stateParams', '$filter', 'session', 'adminData', 'notifications',
	function( $scope, $state, $stateParams, $filter, session, adminData, notifications )
	{
		$scope.data = {
			viewMode: 'edit',
			widgets: [
				{ name: 'history', label: 'Inventory History' },
				{ name: 'week_movement', label: 'Average SJT Movement' },
				{ name: 'inventory', label: 'Store Inventory Levels' },
				{ name: 'distribution', label: 'Card Distribution'}
			],
			widgetPermissions: [] };

		$scope.groupItem = {
			group_name: null,
			group_perm_transaction: 'none',
			group_perm_shift_turnover: 'none',
			group_perm_transfer: 'none',
			group_perm_transfer_approve: false,
			group_perm_transfer_validation: 'none',
			group_perm_transfer_validation_complete: false,
			group_perm_adjustment: 'none',
			group_perm_adjustment_approve: false,
			group_perm_conversion: 'none',
			group_perm_conversion_approve: false,
			group_perm_collection: 'none',
			group_perm_allocation: 'none',
			group_perm_allocation_allocate: false,
			group_perm_allocation_complete: false,

			widgets: {} };

		for( var i = 0; i < $scope.data.widgets.length; i ++ )
		{
			$scope.groupItem.widgets[$scope.data.widgets[i].name] = false;
		}

		$scope.checkData = function()
			{
				return true;
			};

		$scope.prepareData = function()
			{
				var data = angular.copy( $scope.groupItem );
				var widgets = $scope.data.widgets;
				var n = widgets.length;
				var widgetPermissions = [];
				for( var i = 0; i < n; i++ )
				{
					if( data.widgets[widgets[i].name] )
					{
						widgetPermissions.push( widgets[i].name );
					}
				}
				delete data.widgets;
				data.group_perm_dashboard = widgetPermissions.join( ',' );

				return data;
			};

		$scope.saveGroup = function()
			{
				if( $scope.checkData() )
				{
					var data = $scope.prepareData();
					adminData.saveGroup( data ).then(
						function( response )
						{
							if( data.id == session.data.currentUser.group_id )
							{
								session.updateCurrentPermissions();
							}
							adminData.refresh( 'group' );
							notifications.alert( 'Group record saved', 'success' );
							$state.go( 'main.admin', { activeTab: 'groups' } );
						},
						function( reason )
						{
							//notifications.alert( reason, 'error' );
							console.error( reason );
						});
				}
			};

		if( $stateParams.groupItem )
		{
			$scope.data.viewMode = $stateParams.viewMode || 'edit';
			adminData.getGroup( $stateParams.groupItem.id ).then(
				function( response )
				{
					$scope.groupItem = response.data;

					// Widgets
					$scope.groupItem.widgets = {};

					var widgets = $scope.data.widgets;
					var allowedWidgets = $scope.groupItem.group_perm_dashboard ? $scope.groupItem.group_perm_dashboard.split(',') : [];
					for( var i = 0; i < widgets.length; i++ )
					{
						$scope.groupItem.widgets[widgets[i].name] = ( allowedWidgets.indexOf( widgets[i].name ) !== -1 );
					}
				},
				function( reason )
				{
					console.error( reason );
				} );

		}
	}
]);

app.controller( 'StoreController', [ '$scope', '$state', '$stateParams', 'session', 'appData', 'notifications',
	function( $scope, $state, $stateParams, session, appData, notifications )
	{

	}
]);

app.controller( 'ItemController', [ '$scope', '$state', '$stateParams', 'session', 'appData', 'notifications',
	function( $scope, $state, $stateParams, session, appData, notifications )
	{

	}
]);