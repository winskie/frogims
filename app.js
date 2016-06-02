var app = angular.module( 'FROGIMS', [ 'ui.router', 'ui.bootstrap', 'appServices' ], function( $httpProvider )
{
	// Use x-www-form-urlencoded Content-Type
  	$httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';

	/**
	 * The workhorse; converts an object to x-www-form-urlencoded serialization.
	 * @param {Object} obj
	 * @return {String}
	 */ 
  	var param = function(obj)
	  	{
    		var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

    		for( name in obj )
			{
      			value = obj[name];

      			if( value instanceof Array )
				{
        			for( i = 0; i < value.length; ++i )
					{
          				subValue = value[i];
          				fullSubName = name + '[' + i + ']';
          				innerObj = {};
          				innerObj[fullSubName] = subValue;
          				query += param( innerObj ) + '&';
        			}
      			}
      			else if( value instanceof Object )
				{
        			for( subName in value )
					{
          				subValue = value[subName];
          				fullSubName = name + '[' + subName + ']';
          				innerObj = {};
          				innerObj[fullSubName] = subValue;
          				query += param( innerObj ) + '&';
        			}
      			}
      			else if( value !== undefined && value !== null )
        			query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
    		}

    		return query.length ? query.substr(0, query.length - 1) : query;
  		};

	// Override $http service's default transformRequest
	$httpProvider.defaults.transformRequest = [ function( data )
	{
		return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
	}];
});

app.constant( 'baseUrl', baseUrl );

app.config( function( baseUrl, $stateProvider, $urlRouterProvider ) 
{
	$urlRouterProvider.otherwise( '/main/store' );
	
	var dashboard = {
			name: 'dashboard',
			url: '/dashboard',
			templateUrl: baseUrl + 'index.php/main/view/content'
		};
	
	var main = {
			name: 'main',
			url: '/main',
			templateUrl: baseUrl + 'index.php/main/view/main_view',
			controller: 'MainController'
		};
		
	var store = {
			name: 'main.store',
			parent: main,
			url: '/store',
			templateUrl: baseUrl + 'index.php/main/view/partial_store_view',
			controller: 'FrontController',
			params: { activeTab: 'inventory' }
		};
	
	var transfer = {
			name: 'main.transfer',
			parent: main,
			url: '/transfer',
			params: { transferItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_transfer_form',
			controller: 'TransferController'
		};
	
	var adjust = {
			name: 'main.adjust',
			parent: main,
			url: '/adjust',
			params: { adjustmentItem: null },
			templateUrl: baseUrl + 'index.php/main/view/partial_adjustment_form',
			controller: 'AdjustmentController'
		};
	
	var convert = {
			name: 'main.convert',
			parent: main,
			url: '/convert',
			params: { conversionItem: null },
			templateUrl: baseUrl + 'index.php/main/view/partial_conversion_form',
			controller: 'ConversionController'
		};
	
	var mopping = {
			name: 'main.mopping',
			parent: main,
			url: '/mopping',
			params: { moppingItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_mopping_form',
			controller: 'MoppingController',
			resolve: {
				cashierShifts: function( appData )
					{
						return appData.getCashierShifts();
					},
				packingData: function( appData )
					{
						return appData.getPackingData();
					}
			}
		};
	
	var allocation = {
			name: 'main.allocation',
			parent: main,
			url: '/allocation',
			params: { allocationItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_allocation_form',
			controller: 'AllocationController',
			resolve: {
				assigneeShifts: function( appData )
					{
						return appData.getAssigneeShifts();
					}
			}
		};
		
	$stateProvider
		.state( dashboard )
		.state( main )
		.state( store )
		.state( transfer )
		.state( adjust )
		.state( convert )
		.state( mopping )
		.state( allocation );
});

app.run( [ 'session', 'appData',
	function( session, appData )
	{
		console.log( 'Initializing session data...' );
		session.getSessionData().then(
			function( response )
			{
				console.log( 'Session data loaded' );
				
				// Load session dependent data
				console.log( 'Loading current inventory...' );
				appData.getInventory().then(
					function( response )
					{
						// do nothing
					},
					function( reason )
					{
						console.error( reason );
					});
					
				console.log( 'Loading store transactions...' );
				appData.getTransactions().then(
					function( response )
					{
						// do nothing
					},
					function( reason )
					{
						console.error( reason );
					});
					
				console.log( 'Loading transfers...' );
				appData.getTransfers().then(
					function( response )
					{
						// do nothing
					},
					function( reason )
					{
						console.error( reason );
					});
					
				console.log( 'Loading receipts...' );
				appData.getReceipts().then(
					function( response )
					{
						// do nothing
					},
					function( reason )
					{
						console.error( reason );
					});
					
				console.log( 'Loading adjustments...' );
				appData.getAdjustments().then(
					function( response )
					{
						// do nothing
					},
					function( reason )
					{
						console.error( reason );
					});
					
				console.log( 'Loading collections...' );
				appData.getCollections().then(
					function( response )
					{
						// do nothing
					},
					function( reason )
					{
						console.error( reason );
					});
					
				console.log( 'Loading allocations...' );
				appData.getAllocations().then(
					function( response )
					{
						// do nothing
					},
					function( reason )
					{
						console.error( reason );
					});
					
				console.log( 'Loading conversions...' );
				appData.getConversions().then(
					function( response )
					{
						// do nothing
					},
					function( reason )
					{
						console.error( reason );
					});
			},
			function( reject )
			{
				console.error( reject );
			});
			
		console.log( 'Loading stations data...' );
		appData.getStations().then(
			function( response )
			{
				// do nothing
			},
			function( reason )
			{
				console.error( reason );
			});
			
		console.log( 'Loading available stores...' );
		appData.getStores().then(
			function( response )
			{
				// do nothing
			},
			function( reason )
			{
				console.error( reason );
			});
			
		console.log( 'Loading item categories...' );
		appData.getItemCategories().then(
			function( response )
			{
				// do nothing
			},
			function( reason )
			{
				console.error( reason );
			});
			
				
	}
]);