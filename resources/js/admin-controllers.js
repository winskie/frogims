app.controller( 'AdminController', [ '$scope', '$state', '$stateParams', 'session', 'adminData', 'notifications',
	function( $scope, $state, $stateParams, session, adminData, notifications )
	{
		$scope.data = adminData.data;
		$scope.filters = adminData.filters;

		// Refresh/update functions
		$scope.updateUsers = adminData.getUsers;
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

			for( var i = 0; i < n; i++ )
			{
				var s = appData.data.stores[i];
				s['registered'] = registeredStoreIds.indexOf( s.id ) != -1;
				stores.push( s );
			}
			return stores;
		}

		$scope.data = {
			userRoles: angular.copy( adminData.data.userRoles ),
			selectedRole: { id: 2, roleName: 'User' },
			userStatus: angular.copy( adminData.data.userStatus),
			selectedStatus: { id: 1, statusName: 'Active' },
			groups: [],
			selectedGroup: { id: null, group_name: 'None' },
			viewMode: 'edit',
			isNew: true,
			passwordConfirmation: null
		}

		$scope.data.groups.unshift({ id: null, group_name: 'None' });

		$scope.userItem = {
				username: null,
				full_name: null,
				position: null,
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
							$state.go( 'main.admin', { activeTabe: 'users' } );
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