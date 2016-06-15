app.controller( 'AdminController', [ '$scope', '$state', '$stateParams', 'session', 'adminData', 'notifications',
	function( $scope, $state, $stateParams, session, adminData, notifications )
	{
		$scope.adminData = adminData.data;
	}
]);

app.controller( 'UserController', [ '$scope', '$state', '$stateParams', 'session', 'appData', 'notifications',
	function( $scope, $state, $stateParams, session, appData, notifications )
	{

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