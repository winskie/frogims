var appServices = angular.module( 'appServices', [] );

appServices.service( 'session', [ '$http', '$q', 'baseUrl', 'notifications',
    function( $http, $q, baseUrl, notifications )
    {
        var me = this;
        
        me.data = {
                currentUser: null,
                currentStore: null,
                currentShift: null,
                
                userStores: [],
                storeShifts: [],
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

                            deferred.resolve( d );
                        }
                        else
                        {
                            console.error( response.data.errorMsg );
                            deferred.reject( response.data.errorMsg );
                        }
					},
					function( reason )
					{
                        console.error( reason.data.errorMsg );
						deferred.reject( reason )
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
                            console.error( response.data.errorMsg );
                            deferred.reject( response.data.errorMsg );
                        }
						
					},
					function( reason )
					{
                        console.error( reason.data.errorMsg );
						deferred.reject( reason.data );
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
                            console.error( response.data.errorMsg );
                            deferred.reject( response.data.errorMsg );
                        }
                    },
                    function( reason )
                    {
                        console.error( reason.data.errorMsg );
                        deferred.reject( reason )
                    });
                
                return deferred.promise;
            };
    }
]);

appServices.service( 'appData', [ '$http', '$q', '$filter', 'baseUrl', 'session',
    function( $http, $q, $filter, baseUrl, session )
    {
        var me = this;
        me.data = {
                stations: [],
                stores: [],
                activeUsers: [],
                
                itemCategories: [],
                transactionTypes: [
                        { id: 10, typeName: 'Transfer Out' },
                        { id: 11, typeName: 'Transfer In' },
                        { id: 12, typeName: 'Transfer Cancellation' },
                        { id: 13, typeName: 'Void Transfer' },
                        { id: 20, typeName: 'Allocation' },
                        { id: 21, typeName: 'Remittance' },
                        { id: 22, typeName: 'Void Allocation' },
                        { id: 23, typeName: 'Void Remittance' },
                        { id: 30, typeName: 'Collection' },
                        { id: 31, typeName: 'Void Collection' },
                        { id: 40, typeName: 'Adjustment' },
                        { id: 50, typeName: 'Conversion From' },
                        { id: 51, typeName: 'Conversion To' }
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
                        transfers: 0,
                        receipts: 0,
                        adjustments: 0,
                        collections: 0,
                        allocations: 0,
                        conversions: 0                        
                    },
                
                pendingTransfers: 0,
                pendingReceipts: 0,
                pendingAdjustments: 0,
                pendingAllocations: 0
            };
            
        me.filters = {
            dateFormat: 'yyyy-MM-dd',
            itemsPerPage: 10,
            
            inventory: {
                
            },
            transactions: {
                date: new Date(),
                item: { id: null, item_name: 'All', item_description: 'All' },
                type: { id: null, typeName: 'All' },
                page: 1
            },
            transfers: {
                date: new Date(),
                destination: { id: null, store_name: 'All' },
                status: { id: null, statusName: 'All' },
                page: 1
            },
            receipts: {
                date: new Date(),
                source: { id: null, store_name: 'All' },
                status: { id: null, statusName: 'All' },
                page: 1
            },
            adjustments: {
                date: null,
                item: { id: null, item_name: 'All', item_description: 'All' },
                status: { id: null, statusName: 'All' },
                page: 1
            },
            collections: {
                processingDate: new Date(),
                businessDate: null,
                page: 1
            },
            allocations: {
                date: new Date(),
                assigneeType: { id: null, typeName: 'All' },
                status: { id: null, statusName: 'All' },
                page: 1
            },
            conversions: {
                date: new Date(),
                inputItem: { id: null, item_name: 'All', item_description: 'All' },
                outputItem: { id: null, item_name: 'All', item_description: 'All' },
                page: 1
            }
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
                            console.error( response.data.errorMsg );
                            deferred.reject( response.data.errorMsg );
                        }
                        
                    },
                    function( reason )
                    {
                        console.error( reason.data.errorMsg );
                        deferred.reject( reason );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
                            deferred.reject( response.data.errorMsg );
                        }
					},
					function( reason )
					{
                        console.error( response.data.errorMsg );
						deferred.reject( reason.data );
					} );

				return deferred.promise;
			};
            
        me.getTransactions = function( storeId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/transactions',
                    params: {
                        date: $filter( 'date' )( me.filters.transactions.date, 'yyyy-MM-dd' ),
                        item: me.filters.transactions.item ? me.filters.transactions.item.item_id : null,
                        type: me.filters.transactions.type ? me.filters.transactions.type.id : null,
                        page: me.filters.transactions.page ? me.filters.transactions.page : null,
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
                            console.error( response.data.errorMsg );
                            deferred.reject( response.data.errorMsg );
                        }
					},
					function( reason )
					{
                        console.error( reason.data.errorMsg );
						deferred.reject( reason );
					});

				return deferred.promise;
			};
            
        me.getTransfers = function( storeId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/transfers',
                    params: {
                        date: $filter( 'date' )( me.filters.transfers.date, 'yyyy-MM-dd' ),
                        dst: me.filters.transfers.destination ? me.filters.transfers.destination.id : null,
                        status: me.filters.transfers.status ? me.filters.transfers.status.id : null,
                        page: me.filters.transfers.page ? me.filters.transfers.page : null,
                        limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
                    }
				}).then(
					function( response )
					{
                        if( response.data.status == 'ok' )
                        {
                            var d = response.data;
                            
                            me.data.transfers = d.data.transfers;
                            me.data.totals.transfers = d.data.total;
                            me.data.pendingTransfers = d.data.pending;
                            deferred.resolve( d );
                        }
                        else
                        {
                            console.error( response.data.errorMsg );
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
                var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/receipts',
                    params: {
                        date: $filter( 'date' )( me.filters.receipts.date, 'yyyy-MM-dd' ),
                        src: me.filters.receipts.source ? me.filters.receipts.source.id : null,
                        status: me.filters.receipts.status ? me.filters.receipts.status.id : null,
                        page: me.filters.receipts.page ? me.filters.receipts.page : null,
                        limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
                    }
				}).then(
					function( response )
					{
                        if( response.data.status == 'ok' )
                        {
						    var d = response.data;
                            
                            me.data.receipts = d.data.receipts;
                            me.data.totals.receipts = d.data.total;
                            me.data.pendingReceipts = d.data.pending;
                            deferred.resolve( d );
                        }
                        else
                        {
                            console.error( response.data.errorMsg );
                            deferred.reject( response.data.errorMsg );
                        }
					},
					function( reason )
					{
                        consol.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
            };
            
        me.getAdjustments = function( storeId )
            {
                var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/adjustments',
                    params: {
                        date: $filter( 'date' )( me.filters.adjustments.date, 'yyyy-MM-dd' ),
                        item: me.filters.adjustments.item ? me.filters.adjustments.item.item_id : null,
                        status: me.filters.adjustments.status ? me.filters.adjustments.status.id : null,
                        page: me.filters.adjustments.page ? me.filters.adjustments.page : null,
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
                            me.data.pendingAdjustments = d.data.pending;
                            deferred.resolve( d );
                        }
                        else
                        {
                            console.error( response.data.errorMsg );
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
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/collections_summary',
                    params: {
                        processing_date: me.filters.collections.processingDate ? $filter( 'date' )( me.filters.collections.processingDate, 'yyyy-MM-dd' ) : null,
                        business_date: me.filters.collections.businessDate ? $filter( 'date' )( me.filters.collections.businessDate, 'yyyy-MM-dd' ) : null,
                        page: me.filters.collections.page ? me.filters.collections.page : null,
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
                            console.error( response.data.errorMsg );
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
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/allocations_summary',
                    params: {
                        date: me.filters.allocations.date ? $filter( 'date' )( me.filters.allocations.date, 'yyyy-MM-dd' ) : null,
                        assignee_type: me.filters.allocations.assigneeType ? me.filters.allocations.assigneeType.id : null,
                        status: me.filters.allocations.status ? me.filters.allocations.status.id : null,
                        page: me.filters.allocations.page ? me.filters.allocations.page : null,
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
                            deferred.resolve( d );
                        }
                        else
                        {
                            console.error( response.data.errorMsg );
                            deferred.reject( response.data.errorMsg );
                        }
                    },
                    function( reason )
                    {
                        console.error( reason.data.errorMsg );
                        deferred.resolve( reason.data.errorMsg );
                    });
                    
                return deferred.promise;
            };
            
        me.getConversions = function( storeId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/conversions',
                    params: {
                        date: me.filters.conversions.date ? $filter( 'date' )( me.filters.conversions.date, 'yyyy-MM-dd' ) : null,
                        input: me.filters.conversions.inputItem ? me.filters.conversions.inputItem.item_id : null,
                        output: me.filters.conversions.outputItem ? me.filters.conversions.outputItem.item_id : null,
                        page: me.filters.conversions.page ? me.filters.conversions.page : null,
                        limit: me.filters.itemsPerPage ? me.filters.itemsPerPage : null
                    }
                }).then(
                    function( response )
                    {
                        if( response.data.status == 'ok' )
                        {
                            var d = response.data;
                            me.data.conversions = d.data.conversions;
                            me.data.totals.conversions = d.data.total
                            deferred.resolve( d );
                        }
                        else
                        {
                            console.error( response.data.errorMsg );
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
        me.getTransfer = function( transferId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/v1/transfers/' + transferId
                }).then(
                    function( response )
                    {
                        if( response.data.status == 'ok' )
                        {
                            deferred.resolve( response.data );
                        }
                        else
                        {
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
        me.getCashierShifts = function()
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
            
        me.remitAllocation = function( allocationData )
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
                            console.error( response.data.errorMsg );
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
                            console.error( response.data.errorMsg );
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
        me.getConversionFactor = function( sourceItemId, targetItemId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/conversion/conversion_factor',
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
                            console.error( response.data.errorMsg );
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
            
        me.convertItems = function( conversionData )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/v1/conversions/convert',
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
                            console.error( response.data.errorMsg );
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
                    case 'transfer':
                        me.getInventory( currentStoreId );
                        me.getTransactions( currentStoreId );
                        me.getTransfers( currentStoreId );
                        break;
                        
                    case 'receipt':
                        me.getInventory( currentStoreId );
                        me.getTransactions( currentStoreId );
                        me.getReceipts( currentStoreId );
                        break;
                        
                    case 'adjustment':
                        me.getInventory( currentStoreId );
                        me.getTransactions( currentStoreId );
                        me.getAdjustments(currentStoreId );
                        break;
                        
                    case 'collection':
                        me.getInventory( currentStoreId );
                        me.getTransactions( currentStoreId );
                        me.getCollections( currentStoreId );
                        break;
                        
                    case 'allocation':
                        me.getInventory( currentStoreId );
                        me.getTransactions( currentStoreId );
                        me.getAllocations( currentStoreId );
                        break
                        
                    case 'conversion':
                        me.getInventory( currentStoreId );
                        me.getTransactions( currentStoreId );
                        me.getConversions( currentStoreId );
                        break;
                        
                    case 'all':
                    default:
                        me.getInventory( currentStoreId );
                        me.getTransactions( currentStoreId );
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

appServices.service( 'lookup',
    function()
    {
        var me = this;
        me.data = {
            transactionTypes: {
                '10': 'Transfer Out',
                '11': 'Transfer In',
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
    }
]);

appServices.service( 'UserServices', [ '$http', '$q', 'baseUrl',
	function( $http, $q, baseUrl )
	{
        var me = this;
        
        me.findUser = function( q )
            {
                return $http.get( baseUrl + 'index.php/api/user/search', {
                    params: {
                        q: q
                    }
                }).then(
                    function( response )
                    {
                        if( response.data.status == 'ok' )
                        {
                            return response.data.data.users;
                        }
                        else
                        {
                            console.error( response.data.errorMsg );
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