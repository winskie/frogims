angular.module( 'appServices' ).factory( 'sessionInterceptor' , [ '$window',
	function( $window )
	{
		var sessionInterceptor = {
			responseError: function( response )
				{
					if( response.status == 401 )
					{ // Unauthorized, session timeout
						console.info( 'Session expired' );
						$window.location.href = baseUrl + 'index.php/login/timeout';
					}

					return response;
				}
		}

		return sessionInterceptor;
	}
]);

angular.module( 'appServices' ).service( 'session', [ '$http', '$q', '$filter', 'baseUrl', 'notifications',
	function( $http, $q, $filter, baseUrl, notifications )
	{
		var me = this;

		me.data = {
				currentUser: null,
				currentStore: null,
				currentShift: null,

				userStores: [],
				storeShifts: [],
				shiftBalance: null,

				isAdmin: false,
				previousState: null,
				previousTab: null,
			};

		me.permissions = {
			transactions: 'none',
			shift_turnovers: 'none',
			transfers: 'none',
			transfers_approve: false,
			transfer_validations: 'none',
			transfer_validations_complete: false,
			adjustments: 'none',
			adjustments_approve: false,
			conversions: 'none',
			conversions_approve: false,
			collections: 'none',
			allocations: 'none',
			allocations_allocate: false,
			allocations_complete: false
		};

		me.checkPermissions = function( permissionName, action )
			{
				var permission;
				var allowedPermissions;

				switch( permissionName )
				{
					case 'transactions':

						switch( action )
						{
							case 'view':
								allowedPermissions = [ 'view' ];
								permission = me.permissions.transactions;
								break;

							default:
								return false;
						}
						break;

					case 'shiftTurnovers':
						switch( action )
						{
							case 'view':
								allowedPermissions = [ 'view', 'edit' ];
								permission = me.permissions.shift_turnovers;
								break;

							case 'edit':
								allowedPermissions = [ 'edit' ];
								permission = me.permissions.shift_turnovers;
								break;

							default:
								return false;
						}
						break;

					case 'transfers':
						switch( action )
						{
							case 'view':
								allowedPermissions = [ 'view', 'edit' ];
								permission = me.permissions.transfers;
								break;

							case 'edit':
								allowedPermissions = [ 'edit' ];
								permission = me.permissions.transfers;
								break;

							case 'approve':
								return me.permissions.transfers_approve;

							default:
								return false;
						}
						break;

					case 'transferValidations':
						switch( action )
						{
							case 'view':
								allowedPermissions = [ 'view', 'edit' ];
								permission = me.permissions.transfer_validations;
								break;

							case 'edit':
								allowedPermissions = [ 'edit' ];
								permission = me.permissions.transfer_validations;
								break;

							case 'complete':
								return me.permissions.transfer_validations_complete;

							default:
								return false;
						}
						break;

					case 'adjustments':
						switch( action )
						{
							case 'view':
								allowedPermissions = [ 'view', 'edit' ];
								permission = me.permissions.adjustments;
								break;

							case 'edit':
								allowedPermissions = [ 'edit' ];
								permission = me.permissions.adjustments;
								break;

							case 'approve':
								return me.permissions.adjustments_approve

							default:
								return false;
						}
						break;

					case 'conversions':
						switch( action )
						{
							case 'view':
								allowedPermissions = [ 'view', 'edit' ];
								permission = me.permissions.conversions;
								break;

							case 'edit':
								allowedPermissions = [ 'edit' ];
								permission = me.permissions.conversions;
								break;

							case 'approve':
								return me.permissions.conversions_approve;

							default:
								return false;
						}
						break;

					case 'collections':
						switch( action )
						{
							case 'view':
								allowedPermissions = [ 'view', 'edit' ];
								permission = me.permissions.collections;
								break;

							case 'edit':
								allowedPermissions = [ 'edit' ];
								permission = me.permissions.collections;
								break;

							default:
								return false;
						}
						break;

					case 'allocations':
						switch( action )
						{
							case 'view':
								allowedPermissions = [ 'view', 'edit' ];
								permission = me.permissions.allocations;
								break;

							case 'edit':
								allowedPermissions = [ 'edit' ];
								permission = me.permissions.allocations;
								break;

							case 'allocate':
								return me.permissions.allocations_allocate;

							case 'complete':
								return me.permissions.allocations_complete;

							default:
								return false;
						}
						break;

					case 'dashboard':
						if( me.permissions.dashboard )
						{
							var allowedPermissions = me.permissions.dashboard.split( ',' );
							if( ! allowedPermissions.length )
							{
								return false;
							}
							permission = action;
						}
						else
						{
							return false;
						}
						break;

					default:
						return false;
				}

				return allowedPermissions.indexOf( permission ) !== -1;
			};

		me.getSessionData = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/login_info'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data.data;

							me.data.currentUser = d.user;
							me.data.currentStore = d.store;
							me.data.currentShift = d.shift;
							me.data.userStores = d.stores;
							me.data.storeShifts = d.shifts;
							me.data.shiftBalance = d.shift_balance;
							me.data.isAdmin = d.is_admin;
							me.permissions = d.permissions;

							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg )
					});

				return deferred.promise;
			};

		me.updateCurrentStores = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/users/' + me.data.currentUser.id + '/stores'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data.data;

							me.data.userStores = d;
							var currentStore = $filter( 'filter' )( me.data.userStores, { id: me.data.currentStore.id }, true );
							if( currentStore.length == 0 && me.data.userStores.length )
							{ // no longer assigned to current store, let's update current to the first store
								me.data.currentStore = me.data.userStores[0];
							}

							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg )
					});

				return deferred.promise;
			};

		me.updateCurrentPermissions = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/users/' + me.data.currentUser.id + '/permissions'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data.data;
							me.permissions = d;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg )
					});

				return deferred.promise;
			};

		me.changeStore = function( newStore )
			{
				var deferred = $q.defer();
				$http({
					method: 'PATCH',
					url: baseUrl + 'index.php/api/v1/session/store/' + newStore.id,
					data: {
						store_id: newStore.id
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data.data;

							me.data.currentStore = d.store;
							me.data.items = d.inventory;
							me.data.storeShifts = d.shifts;
							me.data.currentShift = d.suggested_shift;
							me.data.shiftBalance = d.shift_balance;

							notifications.notify( 'onChangeStore', d );
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.changeShift = function( newShift )
			{
				var deferred = $q.defer();
				$http({
					method: 'PATCH',
					url: baseUrl + 'index.php/api/v1/session/shift/' + newShift.id,
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data.data;

							me.data.currentShift = d.shift;
							me.data.shiftBalance = d.shift_balance;

							notifications.notify( 'onChangeShift', d );
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getCurrentShift = function()
			{
				var d = new Date();
				var currentShift;
				for( var i = 0, n = me.data.storeShifts.length; i < n; i++ )
				{
					var startTime = $filter( 'date' )( d, 'yyyy-MM-dd' ) + ' ' + me.data.storeShifts[i].shift_start_time;
					var endTime = $filter( 'date' )( d, 'yyyy-MM-dd' ) + ' ' + me.data.storeShifts[i].shift_end_time;

					//if( Date.parse( startTime ) > Date.parse( endTime ) )
					if( startTime > endTime )
					{ // Case of Shift 3
						endTime = new Date( endTime );
						endTime = endTime.setDate( endTime.getDate() +  1 );
						endTime = $filter( 'date' )( endTime, 'yyyy-MM-dd' ) + ' ' + me.data.storeShifts[i].shift_end_time;
					}

					if( Date.now() >= Date.parse( startTime ) && Date.now() <= Date.parse( endTime ) )
					{
						return me.data.storeShifts[i];
					}
				}

				return null;
			};
	}
]);

angular.module( 'appServices' ).service( 'appData', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'utilities', 'lookup',
		'Transfer', 'Conversion', 'Allocation', 'Collection', 'Adjustment', 'ShiftTurnover', 'TVMReading', 'ShiftDetailCashReport',
	function( $http, $q, $filter, baseUrl, session, notifications, utilities, lookup,
			Transfer, Conversion, Allocation, Collection, Adjustment, ShiftTurnover, TVMReading, ShiftDetailCashReport )
	{
		var me = this;

		var currentDate = new Date();
		var firstDay = new Date( currentDate.getFullYear(), currentDate.getMonth(), 1 );

		me.data = {
				stations: [],
				stores: [],
				activeUsers: [],

				shifts: [],

				categories: [],

				cardProfiles: [
						{ id: 1, profileName: 'Standard SVC' },
						{ id: 2, profileName: 'Senior Citizens SVC' },
						{ id: 3, profileName: 'Person With Disabilities SVC' },
						{ id: 4, profileName: 'LRT1 Employee Card' },
						{ id: 5, profileName: 'LRT2 Employee Card' },
						{ id: 6, profileName: 'MRT3 Employee Card' },
						{ id: 7, profileName: 'AFCS Employee Card' },
						{ id: 8, profileName: 'LRT1 SJT' },
						{ id: 9, profileName: 'LRT2 SJT' },
						{ id: 10, profileName: 'MRT3 SJT' },
						{ id: 11, profileName: 'LRT1 SJT for Senior Citizens' },
						{ id: 12, profileName: 'LRT2 SJT for Senior Citizens' },
						{ id: 13, profileName: 'MRT3 SJT for Senior Citizens' },
						{ id: 14, profileName: 'LRT1 SJT for PWD' },
						{ id: 15, profileName: 'LRT2 SJT for PWD' },
						{ id: 16, profileName: 'MRT3 SJT for PWD' },
						{ id: 17, profileName: 'Student' },
						{ id: 18, profileName: 'Beep-Smart' },
						{ id: 19, profileName: 'Beep-Globe' },
						{ id: 20, profileName: 'Beep-BPI' },
						{ id: 21, profileName: 'Discount Card' },
					],

				transactionTypes: [
						{ id: 0, typeName: 'Initial Balance', module: null },
						{ id: 10, typeName: 'Transfer', module: 'Transfers' },
						{ id: 11, typeName: 'Receipt', module: 'Transfers' },
						{ id: 12, typeName: 'Transfer Cancellation', module: 'Transfers' },
						{ id: 13, typeName: 'Void Transfer', module: 'Transfers' },
						{ id: 20, typeName: 'Allocation', module: 'Allocations' },
						{ id: 21, typeName: 'Remittance', module: 'Allocations' },
						{ id: 22, typeName: 'Void Allocation', module: 'Allocations' },
						{ id: 23, typeName: 'Void Remittance', module: 'Allocations' },
						{ id: 30, typeName: 'Collection', module: 'Collections' },
						{ id: 31, typeName: 'Void Collection', module: 'Collections' },
						{ id: 32, typeName: 'Issuance to Production', module: 'Collections' },
						{ id: 32, typeName: 'Void Issuance', module: 'Collections' },
						{ id: 40, typeName: 'Adjustment', module: 'Adjustments' },
						{ id: 50, typeName: 'Conversion From', module: 'Conversions' },
						{ id: 51, typeName: 'Conversion To', module: 'Conversions' }
					],
				shiftTurnoverStatus: [
						{ id: 1, statusName: 'Open' },
						{ id: 2, statusName: 'Closed' },
					],
				transferValidationStatus: [
						{ id: 1, statusName: 'Ongoing' },
						{ id: 2, statusName: 'Completed' },
						{ id: 3, statusName: 'Not Required' },
					],
				transferCategories: [
						{ id: 1, categoryName: 'External Transfer', store_types: [1,2,3,4] },
						{ id: 2, categoryName: 'Internal Transfer', store_types: [1,2,3,4] },
						{ id: 3, categoryName: 'Ticket Turnover', store_types: [4] },
						{ id: 4, categoryName: 'Stock Replenishment', store_types: [3] },
						{ id: 5, categoryName: 'Blackbox Receipt', store_types: [4] },
						{ id: 6, categoryName: 'Bills to Coins Exchange', store_types: [4] },
						{ id: 7, categoryName: 'CSC Application', store_types: [4] },
						{ id: 8, categoryName: 'Bank Deposit', store_types: [4] },
						{ id: 9, categoryName: 'Add TVMIR Refund', store_types: [4] },
						{ id: 10, categoryName: 'Issue TVMIR Refund', store_types: [4] },
						{ id: 11, categoryName: 'Replenish TVM Change Fund', store_types: [4] },
					],
				transferStatus: [
						{ id: 1, statusName: 'Scheduled' },
						{ id: 2, statusName: 'Approved' },
						{ id: 3, statusName: 'Received' },
						{ id: 4, statusName: 'Cancelled - Scheduled' },
						{ id: 5, statusName: 'Cancelled - Approved' },
					],
				receiptStatus: [
						{ id: 1, statusName: 'Scheduled' },
						{ id: 2, statusName: 'Pending Receipt' },
						{ id: 3, statusName: 'Received' },
						{ id: 4, statusName: 'Cancelled - Scheduled' },
						{ id: 5, statusName: 'Cancelled - Approved' },
					],
				adjustmentStatus: [
						{ id: 1, statusName: 'Pending' },
						{ id: 2, statusName: 'Approved' },
						{ id: 3, statusName: 'Cancelled' }
					],

				allocationStatus: [
						{ id: 1, statusName: 'Scheduled' },
						{ id: 2, statusName: 'Allocated' },
						{ id: 3, statusName: 'Completed' },
						{ id: 4, statusName: 'Cancelled' }
					],

				assigneeTypes: [
						{ id: 1, typeName: 'Station Teller' },
						{ id: 2, typeName: 'Ticket Vending Machine' }
					],

				tvms: [
						{ id: 1, description: 'T01' },
						{ id: 2, description: 'T02' },
						{ id: 3, description: 'T03' },
						{ id: 4, description: 'T04' },
						{ id: 5, description: 'T05' },
						{ id: 6, description: 'T06' },
						{ id: 7, description: 'T07' },
						{ id: 8, description: 'T08' },
						{ id: 9, description: 'T00' },
						{ id: 10, description: 'T10' },
						{ id: 11, description: 'T11' },
						{ id: 12, description: 'T12' },
						{ id: 13, description: 'T13' },
						{ id: 14, description: 'T14' },
						{ id: 15, description: 'T15' },
						{ id: 16, description: 'T16' },
					],

				items: [],
				salesItems: [],
				transactions: [],
				shiftTurnovers: [],
				transfers: [],
				receipts: [],
				adjustments: [],
				collections: [],
				allocations: [],
				conversions: [],
				tvmReadings: [],
				shiftDetailCashReports: [],

				totals: {
						transactions: 0,
						shiftTurnovers: 0,
						transferValidations: 0,
						transfers: 0,
						receipts: 0,
						adjustments: 0,
						collections: 0,
						allocations: 0,
						conversions: 0,
						tvmReadings: 0,
						shiftDetailCashReports: 0,
					},

				pending: {
						shiftTurnovers: 0,
						transferValidations: 0,
						transfers: 0,
						receipts: 0,
						adjustments: 0,
						allocations: 0,
						conversions: 0
					}
			};

		me.defaultFilters = {
				dateFormat: 'yyyy-MM-dd',
				itemsPerPage: 10,

				inventory: {

				},
				transactions: {
					date: null,
					item: { id: null, item_name: 'All', item_description: 'All' },
					type: { id: null, typeName: 'All' },
					shift: { id: null, shift_num: 'All', description: 'All' },
					filtered: false
				},
				shiftTurnovers: {
					startDate: firstDay,
					endDate: currentDate,
					shift: { id: null, shift_num: 'All', description: 'All' },
					filtered: false
				},
				transferValidations: {
					dateSent: null,
					dateReceived: null,
					source: { id: null, store_name: 'All' },
					destination: { id: null, store_name: 'All' },
					category: { id: null, categoryName: 'All' },
					status: { id: null, statusName: 'All' },
					validationStatus: { id: null, statusName: 'All' },
					filtered: false
				},
				transfers: {
					date: null,
					category: { id: null, categoryName: 'All' },
					destination: { id: null, store_name: 'All' },
					status: { id: null, statusName: 'All' },
					filtered: false
				},
				receipts: {
					date: null,
					category: { id: null, categoryName: 'All' },
					source: { id: null, store_name: 'All' },
					status: { id: null, statusName: 'All' },
					filtered: false
				},
				adjustments: {
					date: null,
					item: { id: null, item_name: 'All', item_description: 'All' },
					status: { id: null, statusName: 'All' },
					filtered: false
				},
				collections: {
					processingDate: null,
					businessDate: null,
					filtered: false
				},
				allocations: {
					date: null,
					assigneeType: { id: null, typeName: 'All' },
					status: { id: null, statusName: 'All' },
					filtered: false
				},
				conversions: {
					date: null,
					inputItem: { id: null, item_name: 'All', item_description: 'All' },
					outputItem: { id: null, item_name: 'All', item_description: 'All' },
					filtered: false
				},
				tvmReadings: {
					date: null,
					shift: { id: null, item_name: 'All', item_description: 'All' },
					machine_id: null,
					filtered: false
				},
				shiftDetailCashReports: {
					date: null,
					shift: { id: null, item_name: 'All', item_description: 'All' },
					teller_id: null,
					pos_id: null,
					filtered: false
				},
			};

		me.filters = {}
		angular.copy( me.defaultFilters, me.filters );

		me.clearFilter = function( tab )
			{
				me.defaultFilters[tab].filtered = false;
				angular.copy( me.defaultFilters[tab], me.filters[tab] );

				return me.filters[tab];
			};

		me.pagination = {
				transactions: 1,
				shiftTurnovers: 1,
				transferValidations: 1,
				transfers: 1,
				receipts: 1,
				adjustments: 1,
				collections: 1,
				allocations: 1,
				conversions: 1,
				tvmReadings: 1,
				shiftDetailCashReports: 1,
			};

		me.get = function( data )
			{
				return angular.copy( me.data[data] );
			};

		me.getStations = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stations'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.stations = d.data;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getStores = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.stores = d.data;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getCategories = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/categories'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.categories = d.data;
							lookup.loadData( d.data, 'categories', 'id' );
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getShifts = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/shifts'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.shifts = d.data;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			}

		me.getInventory = function( storeId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/items',
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;

							me.data.items = d.data;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getSalesItems = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/sales_items'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;

							me.data.salesItems = d.data;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getTransactions = function( storeId )
			{
				if( !session.checkPermissions( 'transactions', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/transactions',
					params: {
						date: $filter( 'date' )( me.filters.transactions.date, 'yyyy-MM-dd' ),
						item: me.filters.transactions.item ? me.filters.transactions.item.id : null,
						type: me.filters.transactions.type ? me.filters.transactions.type.id : null,
						shift: me.filters.transactions.shift ? me.filters.transactions.shift.id : null,
						page: me.pagination.transactions ? me.pagination.transactions : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;

							me.data.transactions = d.data.transactions;
							me.data.totals.transactions = d.data.total;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getShiftTurnovers = function( storeId )
			{
				if( !session.checkPermissions( 'shiftTurnovers', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/shift_turnovers',
					params: {
						shift: me.filters.shiftTurnovers.shift ? me.filters.shiftTurnovers.shift.id : null,
						start: $filter( 'date' )( me.filters.shiftTurnovers.startDate, 'yyyy-MM-dd' ),
						end: $filter( 'date' )( me.filters.shiftTurnovers.endDate, 'yyyy-MM-dd' ),
						page: me.pagination.shiftTurnovers ? me.pagination.shiftTurnovers : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
						function( response )
						{
							if( response.data.status == 'ok' )
							{
								var d = response.data;

								me.data.shiftTurnovers = ShiftTurnover.createFromData( d.data.shift_turnovers );
								me.data.totals.shiftTurnovers = d.data.total;
								me.data.pending.shiftTurnovers = d.data.pending;
								deferred.resolve( d );
							}
							else
							{
								notifications.showMessages( response.data.errorMsg );
								deferred.reject( response.data.errorMsg );
							}
						},
						function( reason )
						{
							console.error( reason.data.errorMsg );
							deferred.reject( reason.data.errorMsg );
						});

					return deferred.promise;
			};

		me.getTransferValidations = function()
			{
				if( !session.checkPermissions( 'transferValidations', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/transfers',
					params: {
						sent: $filter( 'date' )( me.filters.transferValidations.dateSent, 'yyyy-MM-dd' ),
						received: $filter( 'date' )( me.filters.transferValidations.dateReceived, 'yyyy-MM-dd' ),
						src: me.filters.transferValidations.source ? me.filters.transferValidations.source.id : null,
						dst: me.filters.transferValidations.destination ? me.filters.transferValidations.destination.id : null,
						status: me.filters.transferValidations.status ? me.filters.transferValidations.status.id : null,
						category: me.filters.transferValidations.category ? me.filters.transferValidations.category.id : null,
						validation_status: me.filters.transferValidations.validationStatus ? me.filters.transferValidations.validationStatus.id : null,
						page: me.pagination.transferValidations ? me.pagination.transferValidations : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null,
						include: 'validation'
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;

							me.data.transferValidations = Transfer.createFromData( d.data.transfers );
							me.data.totals.transferValidations = d.data.total;
							me.data.pending.transferValidations = d.data.pending;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getTransfers = function( storeId )
			{
				if( !session.checkPermissions( 'transfers', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/transfers',
					params: {
						date: $filter( 'date' )( me.filters.transfers.date, 'yyyy-MM-dd' ),
						cat: me.filters.transfers.category ? me.filters.transfers.category.id : null,
						dst: me.filters.transfers.destination ? me.filters.transfers.destination.id : null,
						status: me.filters.transfers.status ? me.filters.transfers.status.id : null,
						page: me.pagination.transfers ? me.pagination.transfers : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null,
						include: 'validation'
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;

							me.data.transfers = Transfer.createFromData( d.data.transfers );
							me.data.totals.transfers = d.data.total;
							me.data.pending.transfers = d.data.pending;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getReceipts = function( storeId )
			{
				if( !session.checkPermissions( 'transfers', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/receipts',
					params: {
						date: $filter( 'date' )( me.filters.receipts.date, 'yyyy-MM-dd' ),
						cat: me.filters.receipts.category ? me.filters.receipts.category.id : null,
						src: me.filters.receipts.source ? me.filters.receipts.source.id : null,
						status: me.filters.receipts.status ? me.filters.receipts.status.id : null,
						page: me.pagination.receipts ? me.pagination.receipts : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null,
						include: 'validation'
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;

							me.data.receipts = Transfer.createFromData( d.data.receipts );
							me.data.totals.receipts = d.data.total;
							me.data.pending.receipts = d.data.pending;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getAdjustments = function( storeId )
			{
				if( !session.checkPermissions( 'adjustments', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/adjustments',
					params: {
						date: $filter( 'date' )( me.filters.adjustments.date, 'yyyy-MM-dd' ),
						item: me.filters.adjustments.item ? me.filters.adjustments.item.item_id : null,
						status: me.filters.adjustments.status ? me.filters.adjustments.status.id : null,
						page: me.pagination.adjustments ? me.pagination.adjustments : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.adjustments = Adjustment.createFromData( d.data.adjustments );
							me.data.totals.adjustments = d.data.total;
							me.data.pending.adjustments = d.data.pending;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getCollections = function( storeId )
			{
				if( !session.checkPermissions( 'collections', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/collections',
					params: {
						processing_date: me.filters.collections.processingDate ? $filter( 'date' )( me.filters.collections.processingDate, 'yyyy-MM-dd' ) : null,
						business_date: me.filters.collections.businessDate ? $filter( 'date' )( me.filters.collections.businessDate, 'yyyy-MM-dd' ) : null,
						page: me.pagination.collections ? me.pagination.collections : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.collections = Collection.createFromData( d.data.collections );
							me.data.totals.collections = d.data.total;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getAllocations = function( storeId )
			{
				if( !session.checkPermissions( 'allocations', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/allocations',
					params: {
						date: me.filters.allocations.date ? $filter( 'date' )( me.filters.allocations.date, 'yyyy-MM-dd' ) : null,
						assignee_type: me.filters.allocations.assigneeType ? me.filters.allocations.assigneeType.id : null,
						status: me.filters.allocations.status ? me.filters.allocations.status.id : null,
						page: me.pagination.allocations ? me.pagination.allocations : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;

							me.data.allocations = Allocation.createFromData( d.data.allocations );
							me.data.totals.allocations = d.data.total;
							me.data.pending.allocations = d.data.pending;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getConversions = function( storeId )
			{
				if( !session.checkPermissions( 'conversions', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/conversions',
					params: {
						date: me.filters.conversions.date ? $filter( 'date' )( me.filters.conversions.date, 'yyyy-MM-dd' ) : null,
						input: me.filters.conversions.inputItem ? me.filters.conversions.inputItem.item_id : null,
						output: me.filters.conversions.outputItem ? me.filters.conversions.outputItem.item_id : null,
						page: me.pagination.conversions ? me.pagination.conversions : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.conversions = Conversion.createFromData( d.data.conversions );
							me.data.totals.conversions = d.data.total;
							me.data.pending.conversions = d.data.pending;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getTVMReadings = function( storeId )
			{
				if( !session.checkPermissions( 'allocations', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/tvm_readings',
					params: {
						date: me.filters.tvmReadings.date ? $filter( 'date' )( me.filters.conversions.date, 'yyyy-MM-dd' ) : null,
						shift: me.filters.tvmReadings.shift ? me.filters.tvmReadings.shift.id : null,
						machine_id: me.filters.tvmReadings.machine_id ? me.filters.tvmReadings.machine_id : null,
						page: me.pagination.tvmReadings ? me.pagination.tvmReadings : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.tvmReadings = TVMReading.createFromData( d.data.tvm_readings );
							me.data.totals.tvmReadings = d.data.total;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getShiftDetailCashReports = function( storeId )
			{
				if( !session.checkPermissions( 'allocations', 'view' ) )
				{
					return;
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/shift_detail_cash_reports',
					params: {
						date: me.filters.shiftDetailCashReports.date ? $filter( 'date' )( me.filters.shiftDetailCashReports.date, 'yyyy-MM-dd' ) : null,
						shift: me.filters.shiftDetailCashReports ? me.filters.shiftDetailCashReports.shift.id : null,
						teller_id: me.filters.shiftDetailCashReports.teller_id ? me.filters.shiftDetailCashReports.teller_id : null,
						pos_id: me.filters.shiftDetailCashReports.pos_id ? me.filters.shiftDetailCashReports.pos_id : null,
						page: me.pagination.shiftDetailCashReports ? me.pagination.shiftDetailCashReports : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.shiftDetailCashReports = ShiftDetailCashReport.createFromData( d.data.shift_detail_cash_reports );
							me.data.totals.shiftDetailCashReports = d.data.total;
							deferred.resolve( d );
						}
						else
						{
						}

					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		// Shift Turnovers
		me.getShiftTurnover = function( shiftTurnoverId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/shift_turnovers/' + shiftTurnoverId,
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getShiftTurnoverByStoreDateShift = function( store, date, shiftId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/shift_turnovers/date_shift',
					params: {
						store: store,
						date: $filter( 'date')( date, 'yyyy-MM-dd'),
						shift: shiftId
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		// Transfer Validations
		me.suggestTransferCategory = function( transfer, currentCategory )
			{
				var category = 1; // None

				if( transfer.origin_id && transfer.destination_id )
				{

					var origin = $filter( 'filter' )( me.data.stores, { id: transfer.origin_id }, true )[0];
					var destination = $filter( 'filter' )( me.data.stores, { id: transfer.destination_id }, true )[0];

					if( origin.store_type == 4 && destination.store_type == 2 ) // Cashrooom to Production
					{
						category = 3; // Ticket Turnover
					}
					else if( origin.store_type == 3 && destination.store_type == 4 ) // TGM to Cashroom
					{
						category = 4; // Stock Replenishment
					}
					else if( origin.store_type == 4 && destination.store_type == 4 ) // Cashroom to Cashroom
					{
						category = 5; // Cashroom to Cashroom
					}
					else
					{
						category = 2; // Regular
					}
				}
				else
				{
					var origin, destination;

					if( transfer.origin_id )
					{
						origin = $filter( 'filter' )( me.data.stores, { id: transfer.origin_id }, true )[0];
					}
					else if( transfer.destination_id )
					{
						destination = $filter( 'filter' )( me.data.stores, { id: transfer.destination_id }, true )[0];
					}

					if( ( origin && origin.store_type == 4 ) || ( destination && destination.store_type == 4 ) )
					{
						if( currentCategory.id == 8 || currentCategory.id == 9 ) // Bills to Coins Exchange or CSC Application
						{
							category = currentCategory.id;
						}
						else
						{
							category = 1; // External
						}
					}
					else
					{
						category = 1; // External
					}
				}

				return category;
			};


		// Transfers
		me.getTransfer = function( transferId, includes )
			{
				if( includes )
				{
					if( typeof includes == 'string' )
					{
						includes = [ includes ];
					}
					includes = includes.join(',');
				}

				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/transfers/' + transferId,
					params: {
						include: includes
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		me.getTVMSalesCollectionItems = function( tvmId, storeId, date, shiftId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/tvm_sale_items/',
					params: {
						tvm: tvmId,
						date: $filter( 'date' )( date, 'yyyy-MM-dd' ),
						shift: shiftId
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		// Adjustments
		me.getAdjustment = function( adjustmentId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/adjustments/' + adjustmentId
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		// Mopping
		me.getPullOutShifts = function() // Note: temporarily not is use as the pullout shifts are currently hardcoded
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/shifts',
					params: {
						store_type: 4
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getPackingData = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/conversion_factors/packing'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getCollection = function( collectionId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/collections/' + collectionId
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		// Allocations
		me.getAssigneeShifts = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/shifts',
					params: {
						'store_type[]': [ 0, 1 ]
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		me.getAllocation = function( allocationId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/allocations/' + allocationId
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						notifications.showMessages( reason.data.errorMsg );
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );

					});

				return deferred.promise;
			};

		me.getTurnoverItems = function( storeId, date )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/turnover_items',
					params: {
						date: $filter( 'date' )( date, 'yyyy-MM-dd' )
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						notifications.showMessage( reason.data.errorMsg );
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getShiftSalesItems = function( storeId, date, shift )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/available_sales_collection',
					params: {
						date: $filter( 'date' )( date, 'yyyy-MM-dd' ),
						shift: shift
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						notifications.showMessage( reason.data.errorMsg );
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		// Conversions
		me.getConversion = function( conversionId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/conversions/' + conversionId
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getConversionFactors = function( sourceItemId, targetItemId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/conversion_factors',
					params: {
						source: sourceItemId,
						target: targetItemId
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		// TVM Readings
		me.getCashierShifts = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/shifts',
					params: {
						'store_type[]': [ 4 ]
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};
		me.getTVMReading = function( tvmReadingId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/tvm_readings/' + tvmReadingId
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getReadingByTVMShift = function( params )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					params: params,
					url: baseUrl + 'index.php/api/v1/tvm_readings/tvm_shift'
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							//notifications.showMessages( response.data.errorMsg );
							deferred.resolve( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		// Shift Detail Cash Reports
		me.getShiftDetailCashReport = function( reportId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/shift_detail_cash_report/' + reportId
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessage( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		// Refresh
		me.refresh = function( currentStoreId, group )
			{
				var deferred = $q.defer();
				switch( group )
				{
					case 'shiftTurnovers':
						me.getShiftTurnovers( currentStoreId );
						break;

					case 'transferValidations':
						me.getTransferValidations();
						break;

					case 'transfers':
						me.getInventory( currentStoreId );
						me.getTransferValidations();
						me.getTransactions( currentStoreId );
						me.getTransfers( currentStoreId );
						break;

					case 'receipts':
						me.getInventory( currentStoreId );
						me.getTransactions( currentStoreId );
						me.getReceipts( currentStoreId );
						break;

					case 'adjustments':
						me.getInventory( currentStoreId );
						me.getTransactions( currentStoreId );
						me.getAdjustments(currentStoreId );
						break;

					case 'collections':
						me.getInventory( currentStoreId );
						me.getTransactions( currentStoreId );
						me.getCollections( currentStoreId );
						me.getConversions( currentStoreId );
						break;

					case 'allocations':
						me.getInventory( currentStoreId );
						me.getTransactions( currentStoreId );
						me.getAllocations( currentStoreId );
						break

					case 'conversions':
						me.getInventory( currentStoreId );
						me.getTransactions( currentStoreId );
						me.getConversions( currentStoreId );
						break;

					case 'tvmReadings':
						me.getTVMReadings( currentStoreId );
						break;

					case 'shiftDetailCashReports':
						me.getShiftDetailCashReports( currentStoreId );
						break;

					case 'all':
					default:
						me.getInventory( currentStoreId );
						me.getTransactions( currentStoreId );
						me.getShiftTurnovers( currentStoreId );
						me.getTransferValidations();
						me.getTransfers( currentStoreId );
						me.getReceipts( currentStoreId );
						me.getAdjustments( currentStoreId );
						me.getCollections( currentStoreId );
						me.getAllocations( currentStoreId );
						me.getConversions( currentStoreId );
						me.getTVMReadings( currentStoreId );
						me.getShiftDetailCashReports( currentStoreId );
				}
				deferred.resolve();

				return deferred.promise;
			};

		me.getPreviousShift = function( date, shiftId )
			{
				var index = utilities.findWithAttr( me.data.shifts, 'shift_next_shift_id', shiftId );
				if( index != -1 )
				{
					var previousShift = me.data.shifts[index];
					var previousShiftData = {
						date: date,
						shift: previousShift
					};

					index = utilities.findWithAttr( me.data.shifts, 'id', shiftId );
					if( index != -1 )
					{
						var currentShift = me.data.shifts[index];
						var newDate = new Date();
						if( currentShift.shift_order === 1 )
						{ // previous date
							previousShiftData.date = newDate.setDate( date.getDate() - 1 );
						}

						return previousShiftData;
					}
				}

				return null;
			};

	}
]);

angular.module( 'appServices' ).service( 'adminData', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'appData', 'notifications',
	function( $http, $q, $filter, baseUrl, session, appData, notifications )
	{
		var me = this;
		me.data = {
				users: [],
				groups: [],
				stores: [],
				items: [],

				userRoles: [
						{ id: 1, roleName: 'Administrator' },
						{ id: 2, roleName: 'User' }
					],
				userStatus: [
						{ id: 1, statusName: 'Active' },
						{ id: 2, statusName: 'Locked' },
						{ id: 3, statusName: 'Disabled' }
					],

				totals: {
						users: 0,
						groups: 0,
						stores: 0,
						items: 0
					}
			};

		me.defaultFilters = {
				itemsPerPage: 15,
				users: {
					q: null,
					position: null,
					role: { id: null, roleName: 'All' },
					group: { id: null, group_name: 'All' },
					status: { id: null, statusName: 'All' },
					filtered: false
				},
				groups: {
					q: null,
					filtered: false
				},
				stores: {},
				items: {
					q: null,
					class: null,
					group: null,
					filtered: false
				}
			};

		me.filters = {};
		angular.copy( me.defaultFilters, me.filters );

		me.clearFilter = function( tab )
			{
				me.defaultFilters[tab].filtered = false;
				angular.copy( me.defaultFilters[tab], me.filters[tab] );

				return me.filters[tab];
			};

		me.pagination = {
				users: 1,
				groups: 1,
				items: 1,
				stores: 1
			};

		me.getUsers = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/users',
					params: {
						q: me.filters.users.q ? me.filters.users.q : null,
						position: me.filters.users.q ? me.filters.users.position : null,
						role: me.filters.users.role ? me.filters.users.role.id : null,
						group: me.filters.users.group ? me.filters.users.group.id : null,
						status: me.filters.users.status ? me.filters.users.status.id : null,
						page: me.pagination.users ? me.pagination.users : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.users = d.data.users;
							me.data.totals.users = d.data.total;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.getGroups = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/groups',
					params: {
						q: me.filters.users.q ? me.filters.users.q : null,
						page: me.pagination.groups ? me.pagination.groups : null,
						limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							var d = response.data;
							me.data.groups = d.data.groups;
							me.data.totals.groups = d.data.total;
							deferred.resolve( d );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.refresh = function( group )
			{
				switch( group )
				{
					case 'user':
						me.getUsers();
						break;

					case 'group':
						me.getGroups();
						break;

					case 'all':
					default:
						me.getUsers();
						me.getGroups();
				}
			}

		// Users
		me.getUser = function( userId, params )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/users/' + userId,
					params: params
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.saveUser = function( userData )
			{
				var deferred = $q.defer();
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/v1/users/',
					data: userData
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		// Groups
		me.getGroup = function( groupId, params )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/groups/' + groupId,
					params: params
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};

		me.saveGroup = function( groupData )
			{
				var deferred = $q.defer();
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/v1/groups/',
					data: groupData
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( response.data );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};
	}
]);

angular.module( 'appServices' ).service( 'lookup',
	function()
	{
		var me = this;
		me.data = {
				transactionTypes: {
					'0': 'Initial Balance',
					'10': 'Transfer',
					'11': 'Receipt',
					'12': 'Transfer Cancellation',
					'13': 'Void Transfer',

					'20': 'Allocation',
					'21': 'Remittance',
					'22': 'Void Allocation',
					'23': 'Void Remittance',

					'30': 'Mopping Collection',
					'31': 'Void Collection',
					'32': 'Issuance to Production',
					'33': 'Void Issuance',

					'40': 'Adjustment',

					'50': 'Conversion From',
					'51': 'Conversion To',
				},
				shiftTurnoverStatus: {
					'1': 'Open',
					'2': 'Closed'
				},
				storeTypes: {
					'1': 'General',
					'2': 'Production',
					'3': 'Logistics',
					'4': 'Cashroom'
				},
				userRoles: {
					'1': 'Administrator',
					'2': 'User'
				},
				userStatus: {
					'1': 'Active',
					'2': 'Locked',
					'3': 'Disabled'
				}
			};

		me.loadData = function( data, name, idField )
			{
				if( me.data[name] == undefined )
				{
					me.data[name] = {};
				}
				for( var i = 0, n = data.length; i < n; i++ )
				{
					var rowData = data[i];
					var rowId = rowData[idField];
					delete rowData[idField];
					me.data[name][rowId] = rowData;
				}
			};

		me.getX = function( set, value )
			{
				if( value )
				{
					return me.data[set][value];
				}
				else if( set )
				{
					return me.data[set];
				}
				else
				{
					return NULL;
				}
			};
	});

angular.module( 'appServices' ).service( 'notifications', [ '$rootScope',
	function( $rootScope )
	{
		var me = this;

		me.nextId = 1;

		me.subscribe = function( scope, eventName, callback )
			{
				var eventRegister = $rootScope.$on( eventName, callback );
				scope.$on( '$destroy', eventRegister );
			};

		me.notify = function( event, args )
			{
				$rootScope.$emit( event, args );
			};

		me.alert = function( message, type, duration )
			{
				var durationMultiplier = 1;
				var currentId = me.nextId++;
				if( ! duration )
				{
					duration = 2300;
				}

				if( message )
				{
					durationMultiplier = Math.ceil( message.length / 100 );
				}

				duration = duration * durationMultiplier;

				$rootScope.$emit( 'notificationSignal', {
						id: currentId,
						type: type,
						message: message,
						duration: duration
					});

				if( type == 'error' )
				{
					console.error( message );
				}

				return currentId;
			};

		me.closeNotification = function( notificationId )
			{
				$rootScope.$emit( 'notificationCloseSignal', { id: notificationId } );
			};

		me.showMessages = function( messages )
			{
				if( messages )
				{
					if( messages.constructor === Array )
					{
						var n = messages.length;
						for( var i = 0; i < n; i++ )
						{
							var duration;
							if( messages[i].type == 'error' )
							{
								duration = 5000;
							}

							me.alert( messages[i].msg, messages[i].type, duration );
						}
					}
					else if( messages.constructor === String )
					{
						me.alert( messages, 'error' );
					}
				}
				else
				{
					me.alert( 'Unknown error', 'error' );
				}

			};
	}
]);

angular.module( 'appServices' ).service( 'utilities',
	function()
	{
		var me = this;
		me.findWithAttr = function( array, attr, value )
			{
				for( var i = 0; i < array.length; i++ )
				{
					if( array[i][attr] === value )
					{
						return i;
					}
				}
				return -1;
			};
	});

angular.module( 'appServices' ).service( 'ReportServices', [ '$http', '$httpParamSerializer', '$q', '$window', 'baseUrl', 'notifications',
	function( $http, $httpParamSerializer, $q, $window, baseUrl, notifications )
	{
		var me = this;
		me.reportMode = undefined;

		me.getReportMode = function()
			{
				var deferred = $q.defer();
				if( me.reportMode )
				{
					deferred.resolve( me.reportMode );
				}
				else
				{
					$http({
						method: 'GET',
						url: baseUrl + 'index.php/report/get_report_mode'
					}).then(
						function( response )
						{
							if( response.data.status == 'ok' )
							{
								me.reportMode = response.data.report_mode;
								deferred.resolve( me.reportMode );
							}
							else
							{
								console.error( 'Error retrieving report mode' );
								deferred.reject( 'Error retrieving report mode' );
							}
						},
						function( reason )
						{
							console.error( reason );
							deferred.reject( reason );
						} );
				}

				return deferred.promise;
			};

		me.generateReport = function( report, params, mode )
			{
				var url = baseUrl + 'index.php/report/' + report;
				var noteId = notifications.alert( 'Generating report, please wait...', 'info', -1 );

				if( mode && mode == 'HTML' )
				{
					reportUrl = baseUrl + 'index.php/report/' + report + '?' + $.param( params );
					$window.open( reportUrl, '_blank', 'toolbar=no, menubar=no, location=no, titlebar=no' );
					notifications.closeNotification( noteId );
				}
				else
				{
					me.getReportMode().then(
						function( response )
						{
							switch( response )
							{
								case 'JasperReports':
									$http({
										method: 'GET',
										url: url,
										params: params,
										responseType: 'arraybuffer'
									}).then(
										function( response )
										{
											// Close notification
											notifications.closeNotification( noteId );

											var headers = response.headers();
											var file = new Blob( [response.data], { type: headers['content-type'] } );
											var fileURL = URL.createObjectURL( file );

											// open in new window
											$window.open( fileURL, '_blank', 'toolbar=no, menubar=no, location=no, titlebar=no' );
										},
										function( reason )
										{
											console.error( reason );
										} );
									break;

								default:
									reportUrl = baseUrl + 'index.php/report/' + report + '?' + $.param( params );
									$window.open( reportUrl, '_blank', 'toolbar=no, menubar=no, location=no, titlebar=no' );
									notifications.closeNotification( noteId );
									break;
							}
						} );
				}
			};

		me.viewReport = function( report, params )
			{

			};
	}
]);

angular.module( 'appServices' ).service( 'UserServices', [ '$http', '$q', 'baseUrl',
	function( $http, $q, baseUrl )
	{
		var me = this;

		me.findUser = function( query, group )
			{
				return $http.get( baseUrl + 'index.php/api/v1/users/search', {
					params: {
						q: query,
						group: group
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							return response.data.data;
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							return [];
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						return reason;
					}
				);
			};
	}
]);