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

app.config( function( $stateProvider, $urlRouterProvider ) 
{
	$urlRouterProvider.otherwise( '/store/front' );
	
	var store = {
			name: 'store',
			url: '/store',
			templateUrl: baseUrl + 'index.php/main/view/content',
			controller: 'StoreController',
			resolve: {
				session: [ 'UserServices',
					function( UserServices )
					{
						return UserServices.getLoginInfo();
					}],
				stations: [ 'MiscServices',
					function( MiscServices )
					{
						return MiscServices.getStations();
					}],
				stores: [ 'StoreServices',
					function( StoreServices )
					{
						return StoreServices.getStores();
					}],
				shifts: [ 'MiscServices',
					function( MiscServices )
					{
						return MiscServices.getShifts();
					}]
			}
		}
		
	var front = {
			name: 'store.front',
			parent: store,
			url: '/front',
			templateUrl: baseUrl + 'index.php/main/view/partial_store_view',
		}
	
	var transfer = {
			name: 'store.transfer',
			parent: store,
			params: { transferItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_transfer_form',
			controller: 'TransferController'
		}
	
	var adjust = {
			name: 'store.adjust',
			parent: store,
			params: { adjustmentItem: null },
			templateUrl: baseUrl + 'index.php/main/view/partial_adjustment_form',
			controller: 'AdjustmentController'
		}
	
	var convert = {
			name: 'store.convert',
			parent: store,
			params: { conversionItem: null },
			templateUrl: baseUrl + 'index.php/main/view/partial_conversion_form',
			controller: 'ConversionController'
		}
	
	var mopping = {
			name: 'store.mopping',
			parent: store,
			url: '/mopping',
			params: { moppingItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_mopping_form',
			controller: 'MoppingController'
		}
	
	var allocation = {
			name: 'store.allocation',
			parent: store,
			params: { allocationItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_allocation_form',
			controller: 'AllocationController'
		}
		
	$stateProvider
		.state( front )
		.state( store )
		.state( transfer )
		.state( adjust )
		.state( convert )
		.state( mopping )
		.state( allocation );
});