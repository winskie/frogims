var appServices = angular.module( 'appServices', [] );

appServices.service( 'StoreServices', [ '$http', '$q',
	function( $http, $q )
	{
		this.changeStore = function( store )
			{
				var deferred = $q.defer();
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/store',
					data: {
						store_id: store.id
					}
				}).then(
					function( response )
					{
						deferred.resolve( response.data.data );
					},
					function( reason )
					{
						deferred.reject( reason.data );
					});

				return deferred.promise;
			};
            
        this.changeShift = function( shift )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/store/change_shift',
                    data: {
                        'shift_id': shift.id
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason )
                    });
                
                return deferred.promise;
            };

		this.getStores = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/stores'
				}).then(
					function( response )
					{
						deferred.resolve( response.data );
					},
					function( reason )
					{
						deferred.reject( reason.data );
					});

				return deferred.promise;
			};
        
        
        this.getShifts = function( storeType )
            {
                var deferred = $q.defer();
                var params = null;

                if( storeType instanceof Array )
                {
                    params = { "store_type[]": storeType };
                }
                else
                {
                    params = { store_type: storeType };
                }
                
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/store/shifts',
                    params: params
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });

                return deferred.promise;
            };
                    
        this.getStoreShifts = function( store, showAll )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/store/store_shifts',
                    params: {
                        store_id: store.id,
                        show_all: showAll
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });

                return deferred.promise;
            };

		this.getInventory = function( storeId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/inventory',
					params: {
						'store_id': storeId
					}
				}).then(
					function( response )
					{
						deferred.resolve( response.data.data );
					},
					function( reason )
					{
						deferred.reject( reason.data );
					} );

				return deferred.promise;
			};

		this.getTransactions = function( storeId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/transactions',
					params: {
						'store_id': storeId
					}
				}).then(
					function( response )
					{
						deferred.resolve( response.data.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});

				return deferred.promise;
			};

		this.getTransfers = function( storeId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/transfer',
					params: {
						'store_id': storeId
					}
				}).then(
					function( response )
					{
						deferred.resolve( response.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});

				return deferred.promise;
			};

		this.getReceipts = function( storeId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/transfer/receipts',
					params: {
						'store_id': storeId
					}
				}).then(
					function( response )
					{
						deferred.resolve( response.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});

				return deferred.promise;
			};
			
		this.getAdjustments = function( storeId )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/adjustments',
					params: {
						'store_id': storeId
					}
				}).then(
					function( response )
					{
						deferred.resolve( response.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});
					
				return deferred.promise;
			};
            
        this.getCollections = function( storeId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/mopping/summary',
                    params: {
                        'store_id': storeId
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
        
        this.getAllocations = function( storeId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/allocations/summary',
                    params: {
                        'store_id': storeId
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.resolve( reason );
                    });
                    
                return deferred.promise;
            }
        
        this.getConversions = function( storeId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/conversions',
                    params: {
                        'store_id': storeId
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            }
	}
]);

appServices.service( 'TransferServices', [ '$http', '$q',
	function( $http, $q )
	{
        this.getTransfer = function( transferId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/transfer/item',
                    params: {
                        id: transferId
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
		this.getItems = function( transferItem )
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/transfer/items',
					params: {
						id: transferItem.id
					}
				}).then(
					function( response )
					{
						deferred.resolve( response.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});
					
				return deferred.promise;
			};
			
		this.create = function( transferItem )
			{
				var deferred = $q.defer(); 
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/transfer/create',
					data: transferItem,
				}).then(
					function( response )
					{
						deferred.resolve( response.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});

				return deferred.promise;
			};
			
		this.approve = function( transferItem )
			{
				var deferred = $q.defer();

				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/transfer/approve',
					data: transferItem
				}).then(
					function( response )
					{
						deferred.resolve( response.data.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});

				return deferred.promise;
			};
		
		this.receive = function( transferItem )
			{
				var deferred = $q.defer();
				
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/transfer/receive',
					data: transferItem
				}).then(
					function( response )
					{
						deferred.resolve( response.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});

				return deferred.promise;
			};
			
		this.cancel = function( transferItem )
			{
				var deferred = $q.defer();
				
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/transfer/cancel',
					data: transferItem
				}).then(
					function( response )
					{
						deferred.resolve( response.data.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});

				return deferred.promise;
			};
	}
]);

appServices.service( 'InventoryServices', [ '$http', '$q',
	function( $http, $q )
	{
		this.adjust = function( adjustmentItem )
			{
				var deferred = $q.defer();
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/inventory/adjust',
					data: adjustmentItem
				}).then(
					function( response )
					{
						deferred.resolve( response.data.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});
					
				return deferred.promise;
			};
		
		this.approveAdjustment = function( adjustmentItem )
			{
				var deferred = $q.defer();
				$http({
					method: 'POST',
					url: baseUrl + 'index.php/api/adjustment/approve',
					data: {
						id: adjustmentItem.id
					}
				}).then(
					function( response )
					{
						deferred.resolve( response.data );
					},
					function( reason )
					{
						deferred.reject( reason );
					});
					
				return deferred.promise;
			};
	}
]);

appServices.service( 'ConversionServices', [ '$http', '$q',
    function( $http, $q )
    {
        this.getConversionTable = function()
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/items/conversion_table'
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.getPackageConversion = function()
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/items/package_conversion'
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            }

        this.getConversionFactor = function( sourceItem, targetItem )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/conversion/conversion_factor',
                    params: {
                        source: sourceItem,
                        target: targetItem
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.convert = function( conversionItem )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/conversion/convert',
                    data: conversionItem
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
    }
]);

appServices.service( 'MoppingServices', [ '$http', '$q',
    function( $http, $q )
    {
       this.getCollection = function( moppingItemId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/mopping/item',
                    params: {
                        id: moppingItemId
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
       this.processCollection = function( moppingItem )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/mopping/process',
                    data: moppingItem
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
    }
]);

appServices.service( 'AllocationServices', [ '$http', '$q',
    function( $http, $q )
    {    
        this.getAllocation = function( allocationId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/allocations/item',
                    params: {
                        id: allocationId
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.processAllocation = function( allocationItem )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/allocations/process',
                    data: allocationItem
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.allocate = function( allocationItem )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/allocations/allocate',
                    data: allocationItem
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.remit = function( allocationItem )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/allocations/remit',
                    data: allocationItem
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
        
        this.cancel = function( allocationItem )
            {
                var deferred = $q.defer();
                $http({
                    method: 'POST',
                    url: baseUrl + 'index.php/api/allocations/cancel',
                    data: { id: allocationItem.id }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
    }
]);

appServices.service( 'UserServices', [ '$http', '$q',
	function( $http, $q )
	{
        this.getUsers = function()
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/users'
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
		this.getLoginInfo = function()
			{
				var deferred = $q.defer();
				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/login_info'
				}).then(
					function( response )
					{
						deferred.resolve( response.data.data );
					},
					function( reason )
					{
						deferred.reject( reason )
					});

				return deferred.promise;
			};
	}
]);

appServices.service( 'MiscServices', [ '$http', '$q',
    function( $http, $q )
    {
        this.shifts = undefined;
        
        this.transactionTypes = {
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
            };
            
        this.lookupTransactionType = function( transactionType ) {
                return this.transactionTypes[transactionType];
            };
            
        this.transferStatus = {
                '1': 'Pending',
                '2': 'Approved',
                '3': 'Received',
                '4': 'Cancelled'
            };
            
        this.lookupTransferStatus = function( transferStatus )
            {
                return this.transferStatus[transferStatus];
            };
            
        this.adjustmentStatus = {
                '1': 'Pending',
                '2': 'Approved',
                '3': 'Cancelled'
            };
            
        this.lookupAdjustmentStatus = function( adjustmentStatus )
            {
                return this.adjustmentStatus[adjustmentStatus];
            };
            
        this.allocationStatus = {
                '1': { status: 'Scheduled', className: 'allocation-scheduled' },
                '2': { status: 'Allocated', className: 'allocation-allocated' },
                '3': { status: 'Completed', className: 'allocation-completed' },
                '4': { status: 'Cancelled', className: 'allocation-cancelled' }
            };
            
        this.lookupAllocationStatus = function( allocationStatus, property )
            {
                return this.allocationStatus[allocationStatus][property];
            };
            
        this.allocationItemStatus = {
                '10': 'Scheduled',
                '11': 'Allocated',
                '12': 'Cancelled',
                '13': 'Voided',
                '20': 'Pending',
                '21': 'Remitted',
                '22': 'Voided'
            };
            
        this.lookupAllocationItemStatus = function( allocationItemStatus )
            {
                return this.allocationItemStatus[allocationItemStatus];
            };
        
        this.getStations = function()
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/stations'
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.getShifts = function()
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/shifts'
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.getItems = function()
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/items'
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.getInventoryItems = function( storeId )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/inventory',
                    params: {
                        store_id: storeId
                    }
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
            
        this.getItemCategories = function( mode )
            {
                var deferred = $q.defer();
                $http({
                    method: 'GET',
                    url: baseUrl + 'index.php/api/items/categories'
                }).then(
                    function( response )
                    {
                        deferred.resolve( response.data );
                    },
                    function( reason )
                    {
                        deferred.reject( reason );
                    });
                    
                return deferred.promise;
            };
    }
]);