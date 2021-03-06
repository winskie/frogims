var app = angular.module( 'FROGIMS', [ 'ngAnimate', 'ui.router', 'ui.bootstrap', 'appServices', 'coreModels' ], function( $httpProvider )
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

angular.module( 'appServices', [] );
angular.module( 'coreModels', [] );

app.constant( 'baseUrl', baseUrl );

app.filter('itemsWithProps', function()
{
	return function( items, categoryName )
	{
		var filteredItems = [];

		for( var i = 0, m = items.length; i < m; i++ )
		{
			if( items[i].categories )
			{
				var item = items[i];
				for( var j = 0, n = item.categories.length; j < n; j++ )
				{
					var category = item.categories[j];
					if( categoryName && categoryName.constructor == Array )
					{ // Array of categories
						if( categoryName.indexOf( category.cat_name ) != -1 )
						{
							filteredItems.push( item );
							break;
						}
					}
					else if( categoryName && categoryName.constructor == String )
					{ // Single category
						if( category.cat_name == categoryName )
						{
							filteredItems.push( item );
							break;
						}
					}
				}
			}
		}

		return filteredItems;
	}
});

app.filter('cashFilter', function()
{
	return function( items, parentItem )
	{
		var filteredItems = [];

		if( parentItem && parentItem.constructor == Array )
		{
			for( var i =0, n = items.length; i < n; i++ )
			{
				if( items[i].item_class == 'cash' && parentItem.indexOf( items[i].parent_item_name ) == -1 )
				{
					continue;
				}

				filteredItems.push( items[i] );
			}
		}
		else
		{
			for( var i =0, n = items.length; i < n; i++ )
			{
				if( items[i].item_class == 'cash' && items[i].parent_item_name != parentItem )
				{
					continue;
				}

				filteredItems.push( items[i] );
			}
		}

		return filteredItems;
	}
})

app.filter('parseDate', function()
{
	return function( input )
		{
			return new Date( input );
		};
});

app.filter('sumByColumn', function()
{
	return function( collection, col, type )
		{
			var total = 0;

			switch( type )
			{
				case 'float':
					collection.forEach( function( item ) {
						total += parseFloat( item[col] );
					});
					break;

				default:
					collection.forEach( function( item ) {
						total += parseInt( item[col] );
					});
			}

			return total;
		};
});

app.filter('sumItemPrice', function()
{
	return function( collection, priceCol, quantityCol, type )
		{
			var total = 0.00;

			collection.forEach( function( item ) {
				total += parseFloat( item[priceCol] * item[quantityCol] );
			});

			return total;
		};
});

app.config( function( baseUrl, $stateProvider, $urlRouterProvider, $httpProvider, $animateProvider )
{
	$animateProvider.classNameFilter( /^(?:(?!ng-animate-disabled).)*$/ ); // disable animation for elements with .ng-animate-disabled class
	$httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'; // necessary to make Codeigniter's is_ajax_request() function work
	$httpProvider.interceptors.push( 'sessionInterceptor' );
	$urlRouterProvider.otherwise( '/main/store' );

	var main = {
			name: 'main',
			url: '/main',
			templateUrl: baseUrl + 'index.php/main/view/main_view',
			controller: 'MainController',
			resolve: {
				sessionData: function( $q, session, appData, Conversion )
					{
						console.log( 'Initializing session data...' );
						return session.getSessionData().then(
							function( response )
							{
								var currentStoreId = response.store.id;
								var sessionData = response;

								// Load session dependent data
								console.log( 'Loading sales items...' );
								var initSalesItems = appData.getSalesItems();

								console.log( 'Loading current inventory...' );
								var initInventory = appData.getInventory( currentStoreId );

								console.log( 'Loading store transactions...' );
								var initTransactions = appData.getTransactions( currentStoreId );

								console.log( 'Loading transfers...' );
								var initTransferValidations = appData.getTransferValidations();

								console.log( 'Loading transfers...' );
								var initTransfers = appData.getTransfers( currentStoreId );

								console.log( 'Loading receipts...' );
								var initReceipts = appData.getReceipts( currentStoreId );

								console.log( 'Loading adjustments...' );
								var initAdjustments = appData.getAdjustments( currentStoreId );

								console.log( 'Loading allocations...' );
								var initAllocations = appData.getAllocations( currentStoreId );

								console.log( 'Loading conversions...' );
								var initConversions = appData.getConversions( currentStoreId );

								console.log( 'Loading conversion data...' );
								var initConversionData = Conversion.loadConversionData().then(
									function( response )
									{
										console.log( 'Loading collections...' );
										appData.getCollections( currentStoreId )
									}
								);

								$q.all( [ initSalesItems, initInventory, initTransactions, initTransferValidations, initTransfers, initReceipts,
										initAdjustments, initAllocations, initConversions, initConversionData ] ).then(
									function( promises )
									{
										console.log( 'Finished loading session data' );
										return sessionData;
									});
							},
							function( reject )
							{
								console.error( reject );
							});
					}
			}
		};

	var dashboard = {
			name: 'main.dashboard',
			parent: main,
			url: '/dashboard',
			templateUrl: baseUrl + 'index.php/main/view/partial_dashboard_view',
			controller: 'DashboardController'
		};

	var store = {
			name: 'main.store',
			parent: main,
			url: '/store',
			templateUrl: baseUrl + 'index.php/main/view/partial_store_view',
			controller: 'FrontController',
			params: { activeTab: 'inventory' }
		};

	var admin = {
			name: 'main.admin',
			parent: main,
			url: '/admin',
			templateUrl: baseUrl + 'index.php/main/view/partial_admin_view',
			controller: 'AdminController',
			params: { activeTab: 'general' },
			resolve: {
				adminDataLoaded: function( $q, session, adminData )
					{
						var initUsers = adminData.getUsers();
						var initGroups = adminData.getGroups();
						$q.all( [ initUsers, initGroups ] ).then(
							function( promises )
							{
								return true;
							});
					}
			}
		};

	var shiftTurnover = {
			name: 'main.shiftTurnover',
			parent: main,
			params: { shiftTurnover: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_shift_turnover_form',
			controller: 'ShiftTurnoverController'
		};

	var transferValidation = {
			name: 'main.transferValidation',
			parent: main,
			params: { transferItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_transfer_validation_form',
			controller: 'TransferValidationController'
		};

	var transfer = {
			name: 'main.transfer',
			parent: main,
			url: '/transfer',
			params: { transferItem: null, editMode: 'view', category: null },
			templateUrl: baseUrl + 'index.php/main/view/partial_transfer_form',
			controller: 'TransferController'
		};

	var adjust = {
			name: 'main.adjust',
			parent: main,
			url: '/adjust',
			params: { adjustmentItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_adjustment_form',
			controller: 'AdjustmentController',
			resolve: {
				transactionTypes: function( appData )
					{
						return appData.data.transactionTypes;
					}
			}
		};

	var convert = {
			name: 'main.convert',
			parent: main,
			url: '/convert',
			params: { conversionItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_conversion_form',
			controller: 'ConversionController',
			resolve: {
				conversionTable: function( appData )
					{
						return appData.getConversionFactors();
					}
			}
		};

	var mopping = {
			name: 'main.mopping',
			parent: main,
			url: '/mopping',
			params: { moppingItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_mopping_form',
			controller: 'MoppingController',
			resolve: {
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
			params: { allocationId: null, allocationItem: null, editMode: 'view', activeTab: 0 },
			templateUrl: baseUrl + 'index.php/main/view/partial_allocation_form',
			controller: 'AllocationController',
			resolve: {
				assigneeShifts: function( appData )
					{
						return appData.getAssigneeShifts();
					}
			}
		};

	var tvmReading = {
			name: 'main.tvmReading',
			parent: main,
			url: '/tvm_reading',
			params: { TVMReading: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_tvm_reading_form',
			controller: 'TVMReadingController',
			resolve: {
				cashierShifts: function( appData )
					{
						return appData.getCashierShifts();
					}
			}
		};

	var shiftDetailCashReport = {
			name: 'main.shiftDetailCashReport',
			parent: main,
			url: '/shift_detail_cash_report',
			params: { shiftDetailCashReport: null, editMode: 'view', allocation: null },
			templateUrl: baseUrl + 'index.php/main/view/partial_shift_detail_cash_report_form',
			controller: 'ShiftDetailCashReportController',
		};

	var user = {
			name: 'main.user',
			parent: main,
			url: '/user',
			params: { userItem: null, viewMode: 'edit' },
			templateUrl: baseUrl + 'index.php/main/view/partial_user_form.php',
			controller: 'UserController',
			resolve: {
				groups: function( adminData )
					{
						return adminData.getGroups().then(
							function( response )
							{
								return response.data.groups;
							});
					}
			}
		};

	var group = {
			name: 'main.group',
			parent: 'main',
			url: '/group',
			params: { groupItem: null, viewMode: 'edit' },
			templateUrl: baseUrl + 'index.php/main/view/partial_group_form.php',
			controller: 'GroupController'
		};

	$stateProvider
		.state( dashboard )
		.state( main )
		.state( store )
		.state( shiftTurnover )
		.state( transferValidation )
		.state( transfer )
		.state( adjust )
		.state( convert )
		.state( mopping )
		.state( allocation )
		.state( tvmReading )
		.state( shiftDetailCashReport )

		.state( admin )
		.state( user )
		.state( group );
});

app.directive( 'highcharts', chartDirective );

app.run( [ '$rootScope', 'session', 'appData',
	function( $rootScope, session, appData )
	{
		$rootScope.$on( '$stateChangeSuccess', function( event, toState, toParams, fromState, fromParams )
			{
				session.data.previousState = fromState.name;
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
		appData.getCategories().then(
			function( response )
			{
				// do nothing
			},
			function( reason )
			{
				console.error( reason );
			});

		console.log( 'Loading shifts data...' );
		appData.getShifts().then(
			function( response )
			{
				// do nothing
			},
			function( reason )
			{
				console.error( reason );
			});

		Highcharts.setOptions({
				global: {
					useUTC: false
				},
				lang: {
					thousandsSep: ','
				}
			});
	}
]);