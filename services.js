var appServices = angular.module( 'appServices', [] );

appServices.service( 'session', [ '$http', '$q', '$filter', 'baseUrl', 'notifications',
    function( $http, $q, $filter, baseUrl, notifications )
    {
        var me = this;

        me.data = {
                currentUser: null,
                currentStore: null,
                currentShift: null,

                userStores: [],
                storeShifts: [],

                isAdmin: false,
                previousState: null,
                previousTab: null
            };

        me.permissions = {
            transactions: 'none',
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
                            me.data.storeShifts = d.shifts;
                            me.data.currentShift = d.suggested_shift;

                            notifications.notify( 'onChangeStore' );
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

                            me.data.currentShift = d;
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
    }
]);

appServices.service( 'appData', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
    function( $http, $q, $filter, baseUrl, session, notifications )
    {
        var me = this;
        me.data = {
                stations: [],
                stores: [],
                activeUsers: [],

                itemCategories: [],
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
                        { id: 40, typeName: 'Adjustment', module: 'Adjustments' },
                        { id: 50, typeName: 'Conversion From', module: 'Conversions' },
                        { id: 51, typeName: 'Conversion To', module: 'Conversions' }
                    ],
                transferValidationStatus: [
                        { id: 1, statusName: 'Ongoing' },
                        { id: 2, statusName: 'Completed' },
                        { id: 3, statusName: 'Not Required' },
                    ],
                transferCategories: [
                        { id: 1, categoryName: 'None' },
                        { id: 2, categoryName: 'General' },
                        { id: 3, categoryName: 'Return Loose' },
                        { id: 4, categoryName: 'Stock Replenishment' },
                        { id: 5, categoryName: 'External' }
                    ],
                transferStatus: [
                        { id: 1, statusName: 'Pending' },
                        { id: 2, statusName: 'Approved' },
                        { id: 3, statusName: 'Received' },
                        { id: 4, statusName: 'Cancelled' },
                    ],
                adjustmentStatus: [
                        { id: 1, statusName: 'Pending' },
                        { id: 2, statusName: 'Approved' },
                        { id: 3, statusName: 'Cancelled' }
                    ],

                allocationStatus: [
                        { id: 1, statusName: 'Scheduled' },
                        { id: 2, statusName: 'Allocated' },
                        { id: 3, statusName: 'Remitted' },
                        { id: 4, statusName: 'Cancelled' }
                    ],

                assigneeTypes: [
                        { id: 1, typeName: 'Station Teller' },
                        { id: 2, typeName: 'Ticket Vending Machine' }
                    ],

                items: [],
                transactions: [],
                transfers: [],
                receipts: [],
                adjustments: [],
                collections: [],
                allocations: [],
                conversions: [],

                totals: {
                        transactions: 0,
                        transferValidations: 0,
                        transfers: 0,
                        receipts: 0,
                        adjustments: 0,
                        collections: 0,
                        allocations: 0,
                        conversions: 0
                    },

                pending: {
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
                    filtered: false
                },
                transferValidations: {
                    dateSent: null,
                    dateReceived: null,
                    source: { id: null, store_name: 'All' },
                    destination: { id: null, store_name: 'All' },
                    status: { id: null, statusName: 'All' },
                    validationStatus: { id: null, statusName: 'All' },
                    filtered: false
                },
                transfers: {
                    date: null,
                    destination: { id: null, store_name: 'All' },
                    status: { id: null, statusName: 'All' },
                    filtered: false
                },
                receipts: {
                    date: null,
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
                }
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
                transferValidations: 1,
                transfers: 1,
                receipts: 1,
                adjustments: 1,
                collections: 1,
                allocations: 1,
                conversions: 1
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

        me.getItemCategories = function()
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
                            me.data.itemCategories = d.data;
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
                        item: me.filters.transactions.item ? me.filters.transactions.item.item_id : null,
                        type: me.filters.transactions.type ? me.filters.transactions.type.id : null,
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

                            me.data.transferValidations = d.data.transfers;
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

                            me.data.transfers = d.data.transfers;
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

                            me.data.receipts = d.data.receipts;
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
                            me.data.adjustments = d.data.adjustments;
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
                    url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/collections_summary',
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
                            me.data.collections = d.data.collections;
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
                    url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/allocations_summary',
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
                            me.data.allocations = d.data.allocations;
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
                            me.data.conversions = d.data.conversions;
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

        // Transfer Validations
        me.suggestTransferCategory = function( transfer )
            {
                var category = 1; // None

                if( transfer.origin_id && transfer.destination_id )
                {

                    var origin = $filter( 'filter' )( me.data.stores, { id: transfer.origin_id }, true )[0];
                    var destination = $filter( 'filter' )( me.data.stores, { id: transfer.destination_id }, true )[0];

                    if( origin.store_type == 4 && destination.store_type == 2 ) // Cashrooom to Production
                    {
                        category = 3; // Return Loose
                    }
                    else if( origin.store_type == 3 && destination.store_type == 4 ) // TGM to Cashroom
                    {
                        category = 4; // Stock Replenishment
                    }
                    else
                    {
                        category = 2; // General
                    }
                }
                else
                {
                    category = 5; // External
                }

                return category;
            };

        me.saveTransferValidation = function( validation, action )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/transfer_validations/' + action,
                    data: validation
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

        // Transfers
        me.getTransfer = function( transferId, includes )
            {
                if( includes )
                {
                    if( typeof includes == 'String' )
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

        me.saveTransfer = function( transfer )
            {
                var deferred = $q.defer();
				$http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/transfers',
					data: transfer,
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

        me.approveTransfer = function( transfer )
            {
                var deferred = $q.defer();
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/v1/transfers/approve',
					data: transfer
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

        me.receiveTransfer = function( transfer )
            {
                var deferred = $q.defer();
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/v1/transfers/receive',
					data: transfer
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

        me.cancelTransfer = function( transfer )
            {
                var deferred = $q.defer();
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/v1/transfers/cancel',
					data: transfer
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

        me.saveAdjustment = function( adjustmentData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/adjustments',
                    data: adjustmentData
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

        me.approveAdjustment = function( adjustmentData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/adjustments/approve',
                    data: adjustmentData
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

        me.processCollection = function( collectionData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/collections',
                    data: collectionData
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

        me.saveAllocation = function( allocationData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/allocations',
                    data: allocationData
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

        me.allocateAllocation = function( allocationData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/allocations/allocate',
                    data: allocationData
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

        me.completeAllocation = function( allocationData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/allocations/remit',
                    data: allocationData
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

        me.cancelAllocation = function( allocationData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/allocations/cancel',
                    data: allocationData
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

        me.saveConversion = function( conversionData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/conversions/save',
                    data: conversionData
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
            }

        me.approveConversion = function( conversionData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/conversions/approve',
                    data: conversionData
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

        // Refresh
        me.refresh = function( currentStoreId, group )
            {
                switch( group )
                {
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

                    case 'all':
                    default:
                        me.getInventory( currentStoreId );
                        me.getTransactions( currentStoreId );
                        me.getTransferValidations();
                        me.getTransfers( currentStoreId );
                        me.getReceipts( currentStoreId );
                        me.getAdjustments( currentStoreId );
                        me.getCollections( currentStoreId );
                        me.getAllocations( currentStoreId );
                        me.getConversions( currentStoreId );
                }
            };

    }
]);

appServices.service( 'adminData', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'appData', 'notifications',
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

        me.filters = {
                itemsPerPage: 15,
                users: {
                    q: null,
                    role: null,
                    group: null,
                    status: null,
                    page: 1
                },
                groups: {
                    q: null,
                    page: 1
                },
                stores: {},
                items: {}
            };

        me.getUsers = function()
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/v1/users',
                    params: {
                        q: me.filters.users.q ? me.filters.users.q : null,
                        role: me.filters.users.role ? me.filters.users.role : null,
                        group: me.filters.users.group ? me.filters.users.group.id : null,
                        status: me.filters.users.status ? me.filters.users.status.id : null,
                        page: me.filters.users.page ? me.filters.users.page : null,
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
                        page: me.filters.users.page ? me.filters.users.page : null,
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

appServices.service( 'lookup',
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

                '40': 'Adjustment',

                '50': 'Conversion From',
                '51': 'Conversion To'
            },
            transferValidationStatus: {
                '1': 'Ongoing',
                '2': 'Completed',
				'3': 'Not Required'
            },
            transferValidationReceiptStatus: {
                '1': 'Validated',
                '2': 'Returned'
            },
            transferValidationTransferStatus: {
                '1': 'Validated',
                '2': 'Disputed'
            },
            transferCategories: {
                '1': 'None',
                '2': 'General',
                '3': 'Return Loose',
                '4': 'Stock Replenishment',
                '5': 'External'
            },
            transferStatus: {
                '1': 'Pending',
                '2': 'Approved',
                '3': 'Received',
                '4': 'Cancelled'
            },
            adjustmentStatus: {
                '1': 'Pending',
                '2': 'Approved',
                '3': 'Cancelled'
            },
            conversionStatus: {
                '1': 'Pending',
                '2': 'Approved',
                '3': 'Cancelled'
            },
            allocationStatus: {
                '1': { status: 'Scheduled', className: 'allocation-scheduled' },
                '2': { status: 'Allocated', className: 'allocation-allocated' },
                '3': { status: 'Completed', className: 'allocation-completed' },
                '4': { status: 'Cancelled', className: 'allocation-cancelled' }
            },
            allocationItemStatus: {
                '10': 'Scheduled',
                '11': 'Allocated',
                '12': 'Cancelled',
                '13': 'Voided',
                '20': 'Pending',
                '21': 'Remitted',
                '22': 'Voided'
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

appServices.service( 'notifications', [ '$rootScope',
    function( $rootScope )
    {
        var me = this;
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
                $rootScope.$emit( 'notificationSignal', {
                        type: type,
                        message: message,
                        duration: duration
                    });
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

appServices.service( 'utilities',
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
appServices.service( 'UserServices', [ '$http', '$q', 'baseUrl',
	function( $http, $q, baseUrl )
	{
        var me = this;

        me.findUser = function( q )
            {
                return $http.get( baseUrl + 'index.php/api/v1/users/search', {
                    params: {
                        q: q
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