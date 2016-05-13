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
	$urlRouterProvider.otherwise( '/store' );
	
	$stateProvider
		.state( 'dashboard', {
			url: '/dashboard',
			templateUrl: baseUrl + 'index.php/main/view/partial_dashboard_view'
		})
		
		.state( 'store', {
			url: '/store',
			templateUrl: baseUrl + 'index.php/main/view/partial_store_view'
		})
		
		.state( 'transfer', {
			url: '/transfer',
			params: { transferItem: null, editMode: 'view' },
			templateUrl: baseUrl + 'index.php/main/view/partial_transfer_form',
			controller: 'TransferController'
		})
		
		.state( 'adjust', {
			url: '/adjust',
			params: { adjustmentItem: null },
			templateUrl: baseUrl + 'index.php/main/view/partial_adjustment_form',
			controller: 'AdjustmentController'
		})
        
        .state( 'convert', {
            url: '/convert',
            params: { conversionItem: null },
            templateUrl: baseUrl + 'index.php/main/view/partial_conversion_form',
            controller: 'ConversionController'
        })
        
        .state( 'mopping', {
            url: '/mopping',
            params: { moppingItem: null, editMode: 'view' },
            templateUrl: baseUrl + 'index.php/main/view/partial_mopping_form',
            controller: 'MoppingController'
        })
        
        .state( 'allocation', {
            url: '/allocation',
            params: { allocationItem: null, editMode: 'view' },
            templateUrl: baseUrl + 'index.php/main/view/partial_allocation_form',
            controller: 'AllocationController'
        })
		
		.state( 'logout', {
			url: '/logout'
		});
});