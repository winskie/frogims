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

appServices.service( 'appData', [ '$http', '$q', 'baseUrl', 'session',
    function( $http, $q, baseUrl, session )
    {
        var me = this;
        me.data = {
                stations: [],
                stores: [],
                activeUsers: [],
                
                itemCategories: [],
                
                items: [],
                transactions: [],
                transfers: [],
                receipts: [],
                adjustments: [],
                collections: [],
                allocations: [],
                conversions: [],
                
                pendingTransfers: 0,
                pendingReceipts: 0,
                pendingAdjustments: 0,
                pendingAllocations: 0
            };
            
        me.states = {
            inventory: {
                
            },
            transactions: {
                businessDate: new Date(),
                item: null,
                type: null
            },
            transferOut: {
                date: new Date(),
                destination: [],
                external: null,
                status: null
            },
            transferIn: {
                date: new Date(),
                source: [],
                external: null,
                status: null
            },
            adjustments: {
                page: 1,
                date: null,
                item: null,
                status: null
            },
            collections: {
                processingDate: new Date(),
                businessDate: null
            },
            allocations: {
                businessDate: new Date(),
                assigneeType: null,
                status: null
            },
            conversions: {
                businessDate: new Date(),
                inputItem: null,
                outputItem: null,
            }
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
				}).then(
					function( response )
					{
                        if( response.data.status == 'ok' )
                        {
                            var d = response.data;
                            
                            me.data.transactions = d.data;
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
					url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/transfers'
				}).then(
					function( response )
					{
                        if( response.data.status == 'ok' )
                        {
                            var d = response.data.data;
                            
                            me.data.transfers = d.transfers;
                            me.data.pendingTransfers = d.pending;
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
				}).then(
					function( response )
					{
                        if( response.data.status == 'ok' )
                        {
						    var d = response.data;
                            
                            me.data.receipts = d.data.receipts;
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
				}).then(
					function( response )
					{
                        if( response.data.status == 'ok' )
                        {
                            var d = response.data;
                            me.data.adjustments = d.data.adjustments;
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
                }).then(
                    function( response )
                    {
                        if( response.data.status == 'ok' )
                        {
                            var d = response.data;
                            me.data.collections = d.data;
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
                }).then(
                    function( response )
                    {
                        if( response.data.status == 'ok' )
                        {
                            var d = response.data;
                            me.data.allocations = d.data;
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
                    url: baseUrl + 'index.php/api/v1/stores/' + storeId + '/conversions'
                }).then(
                    function( response )
                    {
                        if( response.data.status == 'ok' )
                        {
                            var d = response.data;
                            me.data.conversions = d.data; 
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