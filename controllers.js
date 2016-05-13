app.controller( 'StoreController', [ '$scope', '$state', '$sce', '$q', 'UserServices', 'StoreServices', 'TransferServices', 'InventoryServices', 'AllocationServices', 'MiscServices',
	function( $scope, $state, $sce, $q, UserServices, StoreServices, TransferServices, InventoryServices, AllocationServices, MiscServices )
	{
		$scope.user = null;
        $scope.stations = [];
		$scope.stores = [];
        $scope.shifts = [];
		$scope.currentStore = $scope.stores[0] || null;
        $scope.currentShift = $scope.shifts[0] || null;
		$scope.items = [];
		$scope.transactions = [];
		$scope.transfers = [];
		$scope.receipts = [];
		$scope.adjustments = [];
        $scope.colls = [];
        $scope.allocations = [];
        $scope.conversions = [];
        
        $scope.initialized = false;
		
		// badges
		$scope.pendingTransfers = 0;
		$scope.pendingReceipts = 0;
		$scope.pendingAdjustments = 0;
        
        
        // lookups
        $scope.lookupTransactionType = function( type )
            {
                return MiscServices.lookupTransactionType( type );
            };
            
        $scope.lookupTransferStatus = function( status )
            {
                return MiscServices.lookupTransferStatus( status );
            };
            
        $scope.lookupAdjustmentStatus = function( status )
            {
                return MiscServices.lookupAdjustmentStatus( status );
            };
            
        $scope.lookupAllocationStatus = function( status, property )
            {
                return MiscServices.lookupAllocationStatus( status, property );
            };
            
        $scope.lookupAllocationItemStatus = function ( status )
            {
                return MiscServices.lookupAllocationItemStatus( status );
            };


		$scope.changeStore = function( newStore )
			{
				// Inform server that we changed the current store
				StoreServices.changeStore( newStore ).then(
					function( response )
					{
						$scope.currentStore = newStore;
                        $scope.getStoreShifts( newStore );
						$scope.updateInventory();
						$scope.updateTransactions();
						$scope.updateTransfers();
						$scope.updateReceipts();
                        $scope.updateAdjustments();
                        $scope.updateCollections();
                        $scope.updateAllocations();
                        $scope.updateCollections();
                        $scope.updateConversions();
					},
					function( reason )
					{
						console.error( reason );
					});
			};
            
        $scope.changeShift = function( shift )
            {
                if( shift )
                {
                    var oldShift = $scope.currentShift;
                    
                    StoreServices.changeShift( shift ).then(
                        function( response )
                        {
                            if( response.status == 'ok' && response.data )
                            {
                                $scope.currentShift = response.data;
                            }
                        },
                        function( reason )
                        {
                            $scope.currentShift = oldShift;
                            console.error( reason );
                        });
                }
            };
            
        $scope.getStoreShifts = function( store )
            {
                StoreServices.getStoreShifts( store ).then(
                    function( response )
                    {
                        if( response.status == 'ok' && response.data.length )
                        {
                            $scope.shifts = response.data;
                            $scope.changeShift( response.data[0] );
                        }
                    },
                    function( reason )
                    {
                        console.error( reason );
                    });
            };

        // Refresh/update functions
		$scope.updateInventory = function()
			{
				if( $scope.currentStore )
				{
					StoreServices.getInventory( $scope.currentStore.id ).then(
						function( response )
						{
							$scope.items = response;
						},
						function( reason )
						{
							console.error( reason );
						});
				}
				else
				{
					console.error( 'No store currently selected.' );
				}
			};

		$scope.updateTransactions = function()
			{
				if( $scope.currentStore )
				{
					StoreServices.getTransactions( $scope.currentStore.id ).then(
						function( response )
						{
							$scope.transactions = response;
						},
						function( reason )
						{
							console.error( reason );
						});
				}
				else
				{
					console.error( 'No inventory item currently selected.' );
				}
			};

		$scope.updateTransfers = function()
			{
				if( $scope.currentStore )
				{
					StoreServices.getTransfers( $scope.currentStore.id ).then(
						function( response )
						{
							$scope.transfers = response.data;
							$scope.pendingTransfers = response.pending;
						},
						function( reason )
						{
							console.error( reason );
						});
				}
				else
				{
					console.error( 'No store currently selected' );
				}
			};

		$scope.updateReceipts = function()
			{
				if( $scope.currentStore )
				{
					StoreServices.getReceipts( $scope.currentStore.id ).then(
						function( response )
						{
							$scope.receipts = response.data;
							$scope.pendingReceipts = response.pending;
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};
			
		$scope.updateAdjustments = function()
			{
				if( $scope.currentStore )
				{
					StoreServices.getAdjustments( $scope.currentStore.id ).then(
						function( response )
						{
							$scope.adjustments = response.data;
							$scope.pendingAdjustments = response.pending;
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};
            
		$scope.updateCollections = function()
            {
                if( $scope.currentStore )
                {
                    StoreServices.getCollections( $scope.currentStore.id ).then(
                        function( response )
                        {
                            $scope.colls = response.data;
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
            };
        $scope.updateAllocations = function()
            {
                if( $scope.currentStore )
                {
                    StoreServices.getAllocations( $scope.currentStore.id ).then(
                        function( response )
                        {
                            $scope.allocations = response.data;
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
            };
            
        $scope.updateConversions = function()
            {
                if( $scope.currentStore )
                {
                    StoreServices.getConversions( $scope.currentStore.id ).then(
                        function( response )
                        {
                            $scope.conversions = response.data;
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
            };
        
		// Approve adjustment
        $scope.approveAdjustment = function( adjustmentItem )
			{
				InventoryServices.approveAdjustment( adjustmentItem ).then(
					function( response )
					{
						if( response.status == 'ok' )
						{
							$scope.updateInventory();
							$scope.updateTransactions();
							$scope.updateAdjustments();
						}
					},
					function( reason )
					{
						console.error( reason );
					});			
			};

		// Approve transfer
		$scope.approveTransfer = function( transferItem )
			{
				TransferServices.approve( transferItem ).then(
					function( response )
					{
						$scope.updateInventory();
						$scope.updateTransactions();
						$scope.updateTransfers();
					},
					function( reject )
					{
						console.error( reject );
					});
			};
			
		// Receive transfer
		$scope.receiveTransfer = function( transferItem )
			{
				TransferServices.receive( transferItem ).then(
					function( response )
					{
						//$scope.debug = $sce.trustAsHtml(response.debug);
						$scope.updateInventory();
						$scope.updateTransactions();
						$scope.updateReceipts();
					},
					function( reject )
					{
						console.error( reject );
					});
			};
			
		// Cancel transfer
		$scope.cancelTransfer = function( transferItem )
			{
				TransferServices.cancel( transferItem ).then(
					function( response )
					{
						$scope.updateInventory();
						$scope.updateTransactions();
						$scope.updateTransfers();
					},
					function( reject )
					{
						console.error( reject );
					});
			};
            
        // Cancel scheduled allocation
        $scope.cancelAllocation = function( allocationItem )
            {
                AllocationServices.cancel( allocationItem ).then(
                    function( response )
                    {
                        $scope.updateInventory();
                        $scope.updateAllocations();
                    },
                    function( reject )
                    {
                        console.error( reject );
                    });
            };
            
		// Get login info
		UserServices.getLoginInfo().then(
			function( response )
			{
				$scope.user = response.user;
				$scope.currentStore = response.store;
			},
			function( reason )
			{
				console.error( reason );
			});

		// Initialize main controller
        $scope.init = function()
        {
            if( $scope.initialized )
            {
                return true;
            }
            else
            {
                // Get stations
                var initStations = MiscServices.getStations().then(
                    function( response )
                    {
                        if( response.status == 'ok' )
                        {
                            $scope.stations = response.data
                        }
                        else
                        {
                            console.error( response.error );
                        }
                    },
                    function( reason )
                    {
                        console.error( reason );
                    });
                
                // Get stores
                var initStores = StoreServices.getStores().then(
                    function( response )
                    {
                        $scope.stores = response.data;
                        if( ! $scope.currentStore )
                        {
                            $scope.currentStore = response[0];
                        }
                        
                        $scope.getStoreShifts( $scope.currentStore );
                        $scope.updateInventory();
                        $scope.updateTransactions();
                        $scope.updateTransfers();
                        $scope.updateReceipts();
                        $scope.updateCollections();
                        $scope.updateAllocations();
                        $scope.updateAdjustments();
                        $scope.updateConversions();
                    },
                    function( reason )
                    {
                        console.error( reason );
                    });
            }
        }
        
        $scope.init();      
	}
]);

app.controller( 'TransferController', [ '$scope', '$filter', '$state', '$stateParams', '$q', 'TransferServices', 'StoreServices', 'UserServices', 'MiscServices',
	function( $scope, $filter, $state, $stateParams, $q, TransferServices, StoreServices, UserServices, MiscServices )
	{
        var stores = [],
            inventoryItems = [],
            itemCategories = [],
            users = [];
            
        $scope.data = {
                editMode: $stateParams.editMode || 'transfer',
                title: 'New Transfer',
                sources: [],
                destinations: [],
                selectedSource: null,
                selectedDestination: null,
                isExternalSource: false,
                isExternalDestination: false,
                inventoryItems: [],
                itemCategories: [],
                sweepers: [],
                transferDatepicker: { format: 'yyyy-MM-dd', opened: false },
                receiptDatepicker: { format: 'yyyy-MM-dd HH:mm:ss', opened: false }
            };
            
        $scope.input = {
                inventoryItem: null,
                itemCategory: null,
                quantity: 0,
                remarks: null
            };
            
        $scope.transferItem = {
                id: null,
				origin_id: [ 'transfer', 'externalTransfer' ].indexOf( $scope.data.editMode ) != -1 ? $scope.currentStore.id : null,
				origin_name: [ 'transfer', 'externalTransfer' ].indexOf( $scope.data.editMode ) != -1 ? $scope.currentStore.store_name : null,
				sender_id: [ 'transfer', 'externalTransfer' ].indexOf( $scope.data.editMode ) != -1 ? $scope.user.id : null,
				sender_name: [ 'transfer', 'externalTransfer' ].indexOf( $scope.data.editMode ) != -1 ? $scope.user.full_name : null,
				transfer_datetime: new Date(),
				destination_id: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? $scope.currentStore.id : null,
				destination_name: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? $scope.currentStore.store_name : null,
				recipient_id: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? $scope.user.id : null,
				recipient_name: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? $scope.user.full_name : null,
				receipt_datetime: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? new Date() : null,
				transfer_status: 1, // TRANSFER_PENDING
                items: []
			};
            
        $scope.toggle = function( field )
            {
                if( field == 'source' )
                {
                    if( $scope.data.editMode == 'receipt' )
                    {
                        $scope.data.editMode = 'externalReceipt';
                    }
                    else
                    {
                        $scope.data.editMode = 'receipt';
                    }
                    
                    if( $scope.data.editMode == 'externalReceipt' )
                    {
                        $scope.transferItem.origin_id = null;
                        $scope.transferItem.origin_name = null;
                    }
                }
                else if( field == 'destination' )
                {
                    if( $scope.data.editMode == 'transfer' )
                    {
                        $scope.data.editMode = 'externalTransfer';
                    }
                    else
                    {
                        $scope.data.editMode = 'transfer';
                    } 
                    
                    
                    if( $scope.data.editMode == 'externalTransfer' )
                    {
                        $scope.transferItem.destination_id = null;
                        $scope.transferItem.destination_name = null;
                    }
                }
                
                $scope.changeEditMode();
            };
            
        $scope.changeSource = function()
            {
                $scope.transferItem.origin_id = $scope.data.selectedSource.id;
                $scope.transferItem.origin_name = $scope.data.selectedSource.store_name;
            };
            
        $scope.changeDestination = function()
            {
                $scope.transferItem.destination_id = $scope.data.selectedDestination.id;
                $scope.transferItem.destination_name = $scope.data.selectedDestination.store_name;
            }
            
        $scope.showDatePicker = function( dp )
			{
				if( dp == 'transfer' )
				{
					$scope.data.transferDatepicker.opened = true;
				}
				else if( dp == 'receipt' )
				{
					$scope.data.receiptDatepicker.opened = true;
				}
			};
            
        $scope.addTransferItem = function( event )
            {
                if( ( event.type == 'keypress' ) && ( event.charCode == 13 )
                        && $scope.input.inventoryItem
                        && $scope.input.itemCategory
                        && $scope.input.quantity > 0 )
                {
                    var data = {
                            item_name: $scope.input.inventoryItem.item_name,
                            category_name: $scope.input.itemCategory.category,
                            
                            item_id: $scope.input.inventoryItem.item_id,
                            item_category_id: $scope.input.itemCategory.id,
                            quantity: $scope.input.quantity,
                            remarks: $scope.input.remarks,
                            transfer_item_status: 1 // TRANSFER_ITEM_SCHEDULED
                        };
                        
                    if( $scope.data.editMode == 'externalReceipt' )
                    {
                        data.quantity_received = $scope.input.quantity;
                    }
                        
                    var index = $scope.input.rowId;
                    if( index )
                    {
                        $scope.transferItem.items[index] = data;
                    }
                    else
                    {
                        $scope.transferItem.items.push( data );
                    }
                    
                    //$scope.checkItems();
                }
            };
            
        $scope.removeTransferItem = function( itemRow )
            {
                if( itemRow.id == undefined ) // ALLOCATION_ITEM_SCHEDULED
                { // remove only items not yet in databaes
                    var index = $scope.transferItem.items.indexOf( itemRow );
                    $scope.transferItem.items.splice( index, 1 );
                }
            };
            
        $scope.changeEditMode = function()
            {
                switch( $scope.data.editMode )
                {
                    case 'transfer':
                        $scope.data.title = 'Transfer';
                        $scope.isExternalSource = false;
                        $scope.isExternalDestination = false;
                        $scope.data.sources = [ $scope.currentStore ];
                        if( $scope.transferItem.origin_id )
                        {
                            $scope.data.selectedSource = $filter( 'filter' )( stores, { id: $scope.transferItem.origin_id }, true )[0];
                        }
                        else
                        {
                            $scope.data.selectedSource = $scope.currentStore;
                        }
                        
                        $scope.data.destinations = $filter( 'filter' )( stores, { id: '!' + $scope.currentStore.id }, function(a, e) { return angular.equals( parseInt(a), parseInt(e) ) } );
                        if( $scope.transferItem.destination_id )
                        {
                            $scope.data.selectedDestination = $filter( 'filter' )( stores, { id: $scope.transferItem.destination_id }, true )[0];
                        }
                        else if( $scope.data.destinations.length )
                        {
                            $scope.data.selectedDestination = $scope.data.destinations[0];
                        }
                        else
                        {
                            console.error( 'Unable to load destination stores' );
                        }
                        break;
                        
                    case 'receipt':
                        $scope.data.title = 'Receipt';
                        $scope.isExternalSource = false;
                        $scope.isExternalDestination = false;
                        $scope.data.sources = $filter( 'filter' )( stores, { id: '!' + $scope.currentStore.id }, function(a, e) { return angular.equals( parseInt(a), parseInt(e) ) } );
                        if( $scope.transferItem.origin_id )
                        {
                            $scope.data.selectedSource = $filter( 'filter' )( stores, { id: $scope.transferItem.origin_id }, true )[0];
                        }
                        else if( $scope.data.sources.length )
                        {
                            $scope.data.selectedSource = $scope.data.sources[0];
                        }
                        else
                        {
                            console.error( 'Unable to load source stores' );
                        }
                        
                        $scope.data.destinations = [ $scope.currentStore ];
                        if( $scope.transferItem.destination_id )
                        {
                            $scope.data.selectedDestination = $filter( 'filter' )( stores, { id: $scope.transferItem.destination_id }, true )[0];
                        }
                        else
                        {
                            $scope.data.selectedDestination = $scope.currentStore;
                        }
                        break;
                        
                    case 'externalTransfer':
                        $scope.data.title = 'External Transfer';
                        $scope.isExternalSource = false;
                        $scope.isExternalDestination = true;
                        $scope.data.sources = [ $scope.currentStore ];
                        if( $scope.transferItem.origin_id )
                        {
                            $scope.data.selectedSource = $filter( 'filter' )( stores, { id: $scope.transferItem.origin_id }, true )[0];
                        }
                        else
                        {
                            $scope.data.selectedSource = $scope.currentStore;
                        }
                        
                        $scope.data.destinations = [];
                        break;
                        
                    case 'externalReceipt':
                        $scope.data.title = 'External Receipt';
                        $scope.isExternalSource = true;
                        $scope.isExternalDestination = false;
                        $scope.data.sources = [];
                        
                        $scope.data.destinations = [ $scope.currentStore ];
                        if( $scope.transferItem.destination_id )
                        {
                            $scope.data.selectedDestination = $filter( 'filter' )( stores, { id: $scope.transferItem.destination_id }, true )[0];
                        }
                        else
                        {
                            $scope.data.selectedDestination = $scope.currentStore;
                        }
                        break;
                        
                    case 'view':
                        $scope.data.title = 'Transfer';
                        break;
                        
                    default:
                        console.error( 'Invalid entry mode' );
                        // do nothing
                }
                
                if( $scope.transferItem.id )
                {
                    $scope.data.title += ( ' #' + $scope.transferItem.id );  
                }
                else
                {
                    $scope.data.title = 'New ' + $scope.data.title;
                }
            }
            
        $scope.checkItems = function()
            {
                if( $scope.transferItem.items.length == 0 )
                {
                    alert( 'This transfer does not contain any items.' );
                    return false;
                }
                
                return true;
            };
            
        $scope.prepareTransfer = function()
			{
				// Make a deep copy to create a disconnected copy of the data from the scope model
                var data = angular.copy( $scope.transferItem );
                
				if( $scope.data.editMode == 'externalTransfer' )
				{
					data.destination_id = null;
				}
				else
				{
					data.destination_id = $scope.data.selectedDestination.id;
					data.destination_name = $scope.data.selectedDestination.store_name;	
				}
				
				if( $scope.data.editMode == 'externalReceipt' )
				{
					data.origin_id = null;
				}
				else
				{
					data.origin_id = $scope.data.selectedSource.id;
					data.origin_name = $scope.data.selectedSource.store_name;
				}
				
				// Clean transfer items
				var itemCount = data.items.length;
				for( var i = 0; i < itemCount; i++ )
				{
                    if( data.items[i].transferItemVoid )
                    {
                        data.items[i].transfer_item_status = 5; // TRANSFER_ITEM_VOIDED
                    }
					delete data.items[i].item_name;
                    delete data.items[i].category_name;
                    delete data.items[i].transferItemVoid;
				}
				
                if( data.transfer_datetime )
                {
				    data.transfer_datetime = $filter( 'date' )( $scope.transferItem.transfer_datetime, 'yyyy-MM-dd HH:mm:ss' );
                }
                
                if( data.receipt_datetime )
                {
                    data.receipt_datetime = $filter( 'date' )( $scope.transferItem.receipt_datetime, 'yyyy-MM-dd HH:mm:ss' );
                }
                
                if( typeof data.sender_name === 'object' )
                {
                    if( data.sender_name.full_name )
                    {
                        data.sender_name = data.sender_name.full_name;
                    }
                    else
                    {
                        data.sender_name = 'Unknown';
                        console.error( 'Unable to find sweeper record' );
                    }
                }
                
                if( $scope.data.editMode == 'externalReceipt' )
                {
                    data.externalReceipt = true;
                }
				
				return data;
			};
		
		$scope.scheduleTransfer = function()
			{
                if( $scope.checkItems() )
                {
                    // Prepare transfer
                    var data = $scope.prepareTransfer();

                    TransferServices.create( data ).then(
                        function( response )
                        {
                            $scope.updateInventory();
                            $scope.updateTransfers();
                            $state.go( 'store' );
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
			};
		
		$scope.approveTransfer = function()
			{
                if( $scope.checkItems() )
                {
                    var data = $scope.prepareTransfer();
                    
                    TransferServices.approve( data ).then(
                        function( response )
                        {
                            $scope.updateInventory();
                            $scope.updateTransactions();
                            $scope.updateTransfers();
                            $state.go( 'store' );
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
				
			};
			
		$scope.receiveTransfer = function()
			{
                if( $scope.checkItems() )
                {
                    var data = $scope.prepareTransfer();
                    
                    TransferServices.receive( data ).then(
                        function( response )
                        {
                            $scope.updateInventory();
                            $scope.updateTransactions();
                            $scope.updateReceipts();
                            $state.go( 'store' );
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
			};
        
        // Initialize controller
        var initStores = StoreServices.getStores().then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    stores = response.data;
                    $scope.changeEditMode();
                }
            },
            function( reason )
            {
                console.error( reason );
            });
            
        var initInventoryItems = MiscServices.getInventoryItems( $scope.currentStore.id ).then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    inventoryItems = response.data;
                    $scope.data.inventoryItems = inventoryItems;
                    if( response.data.length )
                    {
                        $scope.input.inventoryItem = response.data[0];
                    }
                }
                else
                {
                    console.debug( response.error );
                }
            },
            function( reason )
            {
                console.debug( reason );
            });
            
        var initCategories = MiscServices.getItemCategories().then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    itemCategories = response.data;
                    $scope.data.itemCategories = $filter( 'filter' )( itemCategories, { is_transfer_category: true }, true );
                    $scope.data.itemCategories.unshift({
                        id: null,
                        category: '- None -'
                    });
                    if( response.data.length )
                    {
                        //$scope.updateCategories();
                        $scope.input.itemCategory = $scope.data.itemCategories[0];
                    }
                }
            },
            function( reason )
            {
                console.debug( reason );
            });
            
        var initUsers = UserServices.getUsers().then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    users = response.data;
                    $scope.data.sweepers = users;
                }
                else
                {
                    console.error( response.error );
                }
            },
            function( reason )
            {
                console.error( reason );
            });
            
        if( $stateParams.transferItem )
        {
            $q.all( [ initStores, initInventoryItems, initCategories, initUsers ] ).then(
                function( responses )
                {
                    $scope.data.editMode = $stateParams.editMode || 'view';
                    TransferServices.getTransfer( $stateParams.transferItem.id ).then(
                        function( response )
                        {
                            if( response.status == 'ok' )
                            {
                                $scope.transferItem = response.data;
                                
                                if( ! $scope.transferItem.origin_id && $scope.transferItem.origin_name )
                                {
                                    $scope.data.editMode = 'externalReceipt';
                                }
                                else if( ! $scope.transferItem.destination_id && $scope.transferItem.destination_name )
                                {
                                    $scope.data.editMode = 'externalTransfer';
                                }
                                
                                if( $scope.transferItem.transfer_datetime )
                                {
                                    $scope.transferItem.transfer_datetime = Date.parse( $stateParams.transferItem.transfer_datetime );
                                }
                                
                                if( $scope.transferItem.receipt_datetime )
                                {
                                    $scope.transferItem.receipt_datetime = Date.parse( $stateParams.transferItem.receipt_datetime );
                                }
                                else if( $scope.data.editMode == 'receipt' )
                                {
                                    $scope.transferItem.receipt_datetime = new Date();
                                }
                                
                                if( $scope.transferItem.origin_id )
                                {
                                    $scope.data.selectedSource = $filter( 'filter')( stores, { id: $scope.transferItem.origin_id }, true )[0];
                                }
                                else
                                {
                                    $scope.data.selectedSource = null;
                                    $scope.data.isExternalSource = true;
                                }
                                
                                if( $scope.transferItem.destination_id )
                                {
                                    $scope.data.selectedDestination = $filter( 'filter')( stores, { id: $scope.transferItem.destination_id }, true )[0];
                                }
                                else
                                {
                                    $scope.data.selectedDestination = null;
                                    $scope.data.isExternalDestination = true;
                                }
                                
                                if( ! $scope.transferItem.recipient_name && $scope.data.editMode == 'receipt' )
                                {
                                    $scope.transferItem.recipient_name = $scope.user.full_name;
                                }
                                
                                
                                if( $scope.data.editMode == 'receipt' )
                                {
                                    var itemCount = $scope.transferItem.items.length;
                                    for( var i = 0; i < itemCount; i++ )
                                    {
                                        if( ! $scope.transferItem.items[i].quantity_received )
                                        {
                                            $scope.transferItem.items[i].quantity_received = $scope.transferItem.items[i].quantity;
                                        }
                                    }
                                }
                                
                                $scope.changeEditMode();

                            }
                            else
                            {
                                console.error( 'Unable to load mopping collection record' );
                            }
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                        
                });
        }
        
        
        /*
        
		// These needs to be in an object so they can still be modified within child scopes implicitly created by
		// ng-if, ng-switch, ng-repeat, and ng-include
		$scope.data = {
                sourceStore: null,
                destinationStore: null,
                externalSource: false,
                externalDestination: false,
                transferDatepicker: { opened: false },
                receiptDatepicker: { opened: false }
            };
		
		$scope.mode = $stateParams.mode || 'transfer';
		if( $scope.mode == 'transfer' )
		{
			$scope.originStores = $scope.currentStore;
			$scope.destinationStores = $filter( 'filter' )( $scope.stores, { id: '!' + $scope.currentStore.id }, function(a, e) { return angular.equals( parseInt(a), parseInt(e) ) } );
			$scope.data.destinationStore = $scope.destinationStores[0];
			$scope.data.sourceStore = $scope.currentStore;
		}
		else if( $scope.mode == 'receipt' )
		{
			$scope.originStores = $filter( 'filter' )( $scope.stores, { id: '!' + $scope.currentStore.id }, function(a, e) { return angular.equals( parseInt(a), parseInt(e) ) } );
			$scope.destinationStores = $scope.currentStore;
			$scope.data.sourceStore = $scope.originStores[0];
			$scope.data.destinationStore = $scope.currentStore;
		}
		else
		{
			$scope.originStores = null;
			$scope.destinationStores = null;
		}
		
		if( $stateParams.transferItem )
		{
			$stateParams.transferItem.transfer_quantity = parseInt( $stateParams.transferItem.transfer_quantity );
			$stateParams.transferItem.transfer_datetime = Date.parse( $stateParams.transferItem.transfer_datetime );
			
			// Set origin and destination
			$scope.data.sourceStore = $filter( 'filter' )( $scope.stores, { id: $stateParams.transferItem.origin_id }, true )[0];
			$scope.data.destinationStore = $filter( 'filter' )( $scope.stores, { id: $stateParams.transferItem.destination_id }, true )[0];
		}
        
		$scope.transferItem = $stateParams.transferItem || {
				id: null,
				origin_id: $scope.currentStore.id,
				origin_name: $scope.currentStore.store_name,
				sender_id: $scope.user.id,
				sender_name: $scope.user.full_name,
				transfer_datetime: new Date(),
				destination_id: $scope.destinationStores[0].id,
				destination_name: $scope.destinationStores[0].store_name,
				recipient_id: null,
				recipient_name: null,
				receipt_datetime: null,
				transfer_status: 1 // TRANSFER_PENDING
			};
			
		if( $scope.mode == 'receipt' )
		{
			$scope.transferItem.receipt_datetime = new Date();
		}
			
		// Load transfer items
		if( $scope.transferItem.id )
		{
			TransferServices.getItems( $stateParams.transferItem ).then(
				function( response )
				{
					$scope.transferItem.items = response.data;
				},
				function( reason )
				{
					console.error( reason );
				}); 
		}
		
		$scope.destinationLabel = 'Destination Store';
		$scope.format = 'yyyy-MM-dd HH:mm:ss';
		
		$scope.showDatePicker = function( dp )
			{
				if( dp == 'transfer' )
				{
					$scope.data.transferDatepicker.opened = true;
				}
				else if( dp == 'receipt' )
				{
					$scope.data.receiptDatepicker.opened = true;
				}
			};
			
		$scope.changeItem = function( item )
			{
				$scope.selectedItem = item;
				//$scope.transferItem.item_id = item.item_id;
			};
			
		$scope.toggleSource = function()
			{
				$scope.data.externalSource = ! $scope.data.externalSource;
				$scope.transferItem.origin_id = null;
				$scope.transferItem.origin_name = null;
			};
			
		$scope.toggleDestination = function()
			{
				$scope.data.externalDestination = ! $scope.data.externalDestination;
				$scope.destinationLabel = ( $scope.externalDestination ? 'External Destination' : 'Destination Store' );
				$scope.transferItem.destination_id = null;
				$scope.transferItem.destination_name = null;
			};
			
		$scope.changeSource = function()
			{
				$scope.transferItem.origin_id = $scope.sourceStore.id;
				$scope.transferItem.origin_name = $scope.sourceStore.store_name;
			};
			
		$scope.changeDestination = function()
			{
				$scope.transferItem.destination_id = $scope.data.destinationStore.id;
				$scope.transferItem.destination_name = $scope.data.destinationStore.store_name;
			};

		$scope.addTransferItem = function()
			{
				
				if( ! $scope.transferItem )
				{
					
					$scope.transferItem = {
							id: null,
							origin_id: 1,
							origin_name: "Line 2 Depot",
							items: []
						};
				}
				
				if( ! $scope.transferItem.items )
				{
					$scope.transferItem.items = [];
				}

				$scope.transferItem.items.push({
						id: null,
						transfer_id: $scope.transferItem.id,
						selectedItem: $scope.items[0],
						item_id: $scope.items[0].item_id,
						quantity: 0
					});
			}
			
		$scope.removeItem = function( itemRow )
			{
				if( itemRow.id )
				{
					itemRow.deleted = ! itemRow.deleted;
				}
				else
				{
					var index = $scope.transferItem.items.indexOf( itemRow );
					$scope.transferItem.items.splice( index, 1 );
				}
			};
			
		$scope.changeItem = function( itemRow )
			{
				itemRow.item_id = itemRow.selectedItem.item_id;
			};
			
		$scope.prepareTransfer = function()
			{
				// Make a deep copy to create a disconnected copy of the data from the scope model
				var data = angular.copy( $scope.transferItem );

				if( $scope.externalTransfer )
				{
					data.destination_id = null;
				}
				else
				{
					data.destination_id = $scope.data.destinationStore.id;
					data.destination_name = $scope.data.destinationStore.store_name;	
				}
				
				if( $scope.externalReceipt )
				{
					data.origin_id = null;
				}
				else
				{
					data.origin_id = $scope.data.sourceStore.id;
					data.origin_name = $scope.data.sourceStore.store_name;
				}
				
				// Clean transfer items
				var itemCount = data.items.length;
				for( var i = 0; i < itemCount; i++ )
				{
					delete data.items[i].selectedItem;
				}
				
				var dateString = $filter( 'date' )( $scope.transferItem.transfer_datetime, 'yyyy-MM-dd HH:mm:ss' );
				data.transfer_datetime = dateString;
				
				return data;
			};
		
		$scope.scheduleTransfer = function()
			{
				// Prepare transfer
				var data = $scope.prepareTransfer();

				TransferServices.create( data ).then(
					function( response )
					{
						$scope.updateInventory();
						$scope.updateTransfers();
						$state.go( 'store' );
					},
					function( reason )
					{
						console.error( reason );
					});
			};
		
		$scope.approveTransfer = function()
			{
				var data = $scope.prepareTransfer();
				
				TransferServices.approve( data ).then(
					function( response )
					{
						$scope.updateInventory();
						$scope.updateTransactions();
						$scope.updateTransfers();
						$state.go( 'store' );
					},
					function( reason )
					{
						console.error( reason );
					});
				
			};
			
		$scope.receiveTransfer = function()
			{
				var data = $scope.prepareTransfer();
				
				TransferServices.receive( data ).then(
					function( response )
					{
						$scope.updateInventory();
						$scope.updateTransactions();
						$scope.updateReceipts();
						$state.go( 'store' );
					},
					function( reason )
					{
						console.error( reason );
					});
			};
            
        */
	}
]);

app.controller( 'AdjustmentController', [ '$scope', '$filter', '$state', '$stateParams', 'InventoryServices',
	function( $scope, $filter, $state, $stateParams, InventoryServices )
	{
		$scope.selectedItem = null;
		if( $stateParams.adjustmentItem )
		{
			$stateParams.adjustmentItem.previous_quantity = parseInt( $stateParams.adjustmentItem.previous_quantity );
			$stateParams.adjustmentItem.adjusted_quantity = parseInt( $stateParams.adjustmentItem.adjusted_quantity );
			
			var inventoryItem = $filter( 'filter' )( $scope.items, { id: $stateParams.adjustmentItem.store_inventory_id }, true );
			if( inventoryItem.length )
			{
				$scope.selectedItem = inventoryItem[0];
			}
			else
			{
				$scope.selectedItem = $scope.items[0];
			}
		}
		else
		{
			$scope.selectedItem = $scope.items[0];
		}
		
		$scope.adjustmentItem = $stateParams.adjustmentItem || {
				id: null,
				store_inventory_id: $scope.selectedItem.id,
				adjusted_quantity: null,
				reason: null ,
				adjustment_status: 1 // ADJUSTMENT_PENDING
			};
			
		$scope.changeItem = function( item )
			{
				$scope.selectedItem = item;
				$scope.adjustmentItem.store_inventory_id = item.id;
			}
			
		$scope.saveAdjustment = function()
			{
				if( ! $scope.adjustmentItem.reason )
				{
					// TODO: Notifications
					alert( 'You must specify a reason' );
					return false;	
				}
                
                //$scope.adjustmentItem.store_inventory_id = $scope.selectedIted.id;
				
				InventoryServices.adjust( $scope.adjustmentItem ).then(
					function( response )
					{
                        $scope.updateInventory();
                        $scope.updateTransactions();
						$scope.updateAdjustments();
						$state.go( 'store' );
					},
					function( reason )
					{
						console.error( reason );
					});
			};
            
		$scope.approveAdjustment = function()
			{
				$scope.adjustmentItem.adjustment_status = 2; // ADJUSTMENT_APPROVED
				$scope.saveAdjustment();
			}
			
		$scope.cancel = function()
			{
				$state.go( 'store' );
			}
            
        console.debug( $scope.adjustmentItem );
	}
]);

app.controller( 'ConversionController', [ '$scope', '$filter', '$state', '$stateParams', 'ConversionServices',
    function( $scope, $filter, $state, $stateParams, ConversionServices )
    {
        var inputQuantity = angular.element( document.querySelector( "input#inputQuantity" ) );
        var outputQuantity = angular.element( document.querySelector( "input#outputQuantity" ) );
        
        $scope.data = {
            sourceInventory: null,
            targetInventory: null
        }
        
        $scope.factor;
        $scope.mode;
        $scope.valid_conversion = false;
        $scope.messages = [];
        $scope.sourceInventory = $scope.items;
        $scope.targetInventory = $scope.items;
        
        $scope.data.sourceInventory = $scope.sourceInventory[0] || null;
        $scope.data.targetInventory = $scope.targetInventory[1] || null;
        
        $scope.conversionItem = {
                store_id: $scope.currentStore.id,
                source_inventory_id: $scope.data.sourceInventory.id || null,
                target_inventory_id: $scope.data.targetInventory.id || null,
                source_quantity: 1,
                target_quantity: null,
                remarks: null
            };
            
        $scope.checkConversion = function()
            {
                $scope.valid_conversion = true;
                $scope.messages = [];
                
                if( $scope.conversionItem.source_quantity === 0 || $scope.conversionItem.target_quantity === 0 )
                {
                    $scope.valid_conversion = false;
                    $scope.messages.push( 'Input quantity and output quantity cannot be 0.' );
                }
                
                if( $scope.conversionItem.source_quantity % 1 !== 0 || $scope.conversionItem.target_quantity % 1 !== 0 )
                {
                    $scope.valid_conversion = false;
                    $scope.messages.push( 'Input quantity and output quantity cannot be non-integer values.' );
                }
                
                if( $scope.data.sourceInventory.item_id == $scope.data.targetInventory.item_id )
                {
                    $scope.valid_conversion = false;
                    $scope.messages.push( 'Input item and output item cannot be the same.' );
                }
                
                if( $scope.conversionItem.source_quantity > $scope.data.sourceInventory.quantity )
                {
                    $scope.valid_conversion = false;
                    $scope.messages.push( 'Insufficient inventory for input item to convert.' );
                }
                
                if( ! $scope.factor )
                {
                    $scope.valid_conversion = false;
                    $scope.messages.push( 'Cannot convert input item to output item.' );
                }
                
                return $scope.valid_conversion;
            };
        
        $scope.updateConversionFactor = function()
            {
                $scope.conversionItem.source_inventory_id = $scope.data.sourceInventory.id;
                $scope.conversionItem.target_inventory_id = $scope.data.targetInventory.id;
                
                ConversionServices.getConversionFactor( $scope.data.sourceInventory.item_id, $scope.data.targetInventory.item_id ).then(
                    function( response )
                    {
                        if( response.status == 'ok' )
                        {
                            $scope.valid_conversion = true;
                            $scope.factor = response.factor;
                            $scope.mode = response.mode;
                            
                            if( $scope.mode == 'pack' )
                            {
                                inputQuantity.attr( 'step', $scope.factor );
                                inputQuantity.attr( 'min', $scope.factor );
                                
                                outputQuantity.attr( 'step', 1 );
                                outputQuantity.attr( 'min', 0 );
                                
                                $scope.conversionItem.target_quantity = 1;
                                $scope.calculateOutput( 'output' );
                            }
                            else if( $scope.mode == 'unpack' )
                            {
                                inputQuantity.attr( 'step', 1 );
                                inputQuantity.attr( 'min', 0 );
                                
                                outputQuantity.attr( 'step', $scope.factor );
                                outputQuantity.attr( 'min', $scope.factor );
                                
                                $scope.conversionItem.source_quantity = 1;
                                $scope.calculateOutput( 'input' );
                            }
                            else
                            {
                                inputQuantity.attr( 'step', 1 );
                                inputQuantity.attr( 'min', 1 );
                                
                                outputQuantity.attr( 'step', 1 );
                                outputQuantity.attr( 'min', 1 );
                                
                                $scope.calculateOutput( 'input' );
                            }
                        }
                        else if( response.status == 'fail' )
                        {
                            $scope.factor = undefined;
                            $scope.mode =
                            $scope.valid_conversion = false;
                        }
                        
                        $scope.checkConversion();
                },
                function( reason )
                {
                    console.error( reason );
                });
            };
        
        $scope.calculateOutput = function( input )
            {
                if( $scope.mode == 'pack' )
                {
                    if( input == 'input' )
                    {
                        $scope.conversionItem.target_quantity = $scope.conversionItem.source_quantity / $scope.factor;
                    }
                    else
                    {
                        $scope.conversionItem.source_quantity = $scope.conversionItem.target_quantity * $scope.factor;
                    }
                }
                else if( $scope.mode == 'unpack' )
                {
                    if( input == 'input' )
                    {
                        $scope.conversionItem.target_quantity = $scope.conversionItem.source_quantity * $scope.factor;
                    }
                    else
                    {
                        $scope.conversionItem.source_quantity = $scope.conversionItem.target_quantity / $scope.factor;
                    }
                }
                else if( $scope.mode == 'convert' )
                {
                    if( input == 'input' )
                    {
                        $scope.conversionItem.target_quantity = $scope.conversionItem.source_quantity;
                    }
                    else
                    {
                        $scope.conversionItem.source_quantity = $scope.conversionItem.target_quantity;
                    }
                }
                
                $scope.checkConversion();
            }
            
        $scope.convert = function()
            {
                ConversionServices.convert( $scope.conversionItem ).then(
                    function( response )
                    {
                        $scope.updateConversions();
                        $scope.updateTransactions();
                        $scope.updateInventory();
                        $state.go( 'store' );
                    },
                    function( reason )
                    {
                        console.error( reason );
                    });
            };
            
        // Initialize
        $scope.updateConversionFactor();
    }
]);

app.controller( 'MoppingController', [ '$scope', '$q', '$filter', '$state', '$stateParams', 'MoppingServices', 'UserServices', 'StoreServices', 'ConversionServices', 'MiscServices',
    function( $scope, $q, $filter, $state, $stateParams, MoppingServices, UserServices, StoreServices, ConversionServices, MiscServices )
    {
        if( ! $scope.initialized )
        {
            $scope.init();
        }
        
        var conversionTable = [];
        var items = [];
        var packItems = [];
        
        $scope.data = {
                processingDatepicker: { format: 'yyyy-MM-dd HH:mm:ss', opened: false },
                businessDatepicker: { format: 'yyyy-MM-dd', opened: false },
                cashierShifts: [],
                selectedCashierShift: null,
                moppedSource: $scope.stations,
                moppedItems: [],
                packAsItems: [],
                processors: [],
                editMode: $stateParams.editMode || 'new'
            };
            
        // Add Inventory from source of mopped item
        $scope.data.moppedSource.push({
                id: 0,
                station_name: 'Inventory',
                station_short_name: 'INV'
            });
        
                       
        $scope.moppingItem = {
                store_id: $scope.currentStore.id,
                processing_datetime: new Date(),
                business_date: new Date(),
                shift_id: $scope.currentShift.id,
                cashier_shift_id: null,
                items: []
            };
        
        $scope.input = {
                rowId: null,
                moppedSource: $scope.data.moppedSource[0] || null,
                moppedItem: null,
                moppedQuantity: 0,
                packAs: null,
                processor: null
            };
            
        $scope.showDatePicker = function( dp )
            {
                if( dp == 'business' )
                {
                    $scope.data.businessDatepicker.opened = true;
                }
                else if( dp == 'processing' )
                {
                    $scope.data.processingDatepicker.opened = true;
                }
            };
            
        $scope.onChangeCashierShift = function()
            {
                $scope.moppingItem.cashier_shift_id = $scope.data.selectedCashierShift.id;
            };
            
        $scope.addMoppingItem = function( event )
            {
                if( ( event.type == 'keypress' ) && ( event.charCode == 13 )
                        && $scope.input.moppedSource
                        && $scope.input.moppedItem && typeof $scope.input.moppedItem === 'object'
                        && ( ! $scope.input.packAs || ( $scope.input.packAs != null && typeof $scope.input.packAs === 'object' ) ) 
                        && $scope.input.moppedQuantity > 0
                        && $scope.input.processor && typeof $scope.input.processor === 'object' )
                {
                    var data = {
                            mopped_station_name: $scope.input.moppedSource.station_name,
                            mopped_item_name: $scope.input.moppedItem.item_name,
                            convert_to_name: ( $scope.input.packAs && $scope.input.packAs.id ) ? $scope.input.packAs.item_name : null,
                            processor_name: $scope.input.processor ? $scope.input.processor.full_name : null,
                            valid: true,
                            
                            mopped_station_id: parseInt( $scope.input.moppedSource.id ),
                            mopped_item_id: parseInt( $scope.input.moppedItem.id ),
                            mopped_quantity: parseInt( $scope.input.moppedQuantity ),
                            converted_to: ( $scope.input.packAs && $scope.input.packAs.id ) ? $scope.input.packAs.target_item_id : null,
                            group_id: null,
                            processor_id: $scope.input.processor.id,
                            mopped_item_status: 1 // MOPPING_ITEM_COLLECTED
                        };
                        
                    var index = $scope.input.rowId;
                    if( index )
                    {
                        $scope.moppingItem.items[index] = data;
                    }
                    else
                    {
                        $scope.moppingItem.items.push( data );
                    }
                    
                    $scope.checkItems();
                }
            };
            
        $scope.removeMoppingItem = function( itemRow )
            {
                var index = $scope.moppingItem.items.indexOf( itemRow );
                $scope.moppingItem.items.splice( index, 1 );
                $scope.checkItems();
            };
            
        $scope.onItemChange = function()
            {
                var item = $scope.input.moppedItem;
                var packItem = $scope.input.packAs;

                if( item && typeof item === 'object' )
                {
                    $scope.data.packAsItems = $filter( 'filter' )( packItems, { source_item_id: item.id }, true );
                    if( $scope.data.packAsItems.length )
                    {
                        $scope.data.packAsItems.unshift({
                            id: null,
                            source_item_id: item.id,
                            target_item_id: null,
                            conversion_factor: 0,
                            item_name: 'Do not pack',
                            item_description: 'Do not pack'
                        });
                        $scope.input.packAs = $scope.data.packAsItems[0];
                    }
                }
                else
                {
                    $scope.data.packAsItems = null;
                }
            };
            
        $scope.onVoidChange = function( item )
            {
                // Set mopped_item_status
                //item.mopping_item_status = item.moppedItemVoid ? 2 : 1;
                
                // Is part of a group, update all members of the group
                if( item.group_id )
                {
                    var items = $scope.moppingItem.items,
                        itemCount = items.length;
                    
                    for( var i = 0; i < itemCount; i++ )
                    {
                        if( items[i].id == item.id )
                        {
                            continue;
                        }
                        if( items[i].group_id == item.group_id )
                        {
                            items[i].moppedItemVoid = item.moppedItemVoid;
                            //items[i].mopping_item_status = item.mopping_item_status;
                        }
                    }
                }
            };
            
        $scope.checkItems = function()
            {
                var packedItems = {},
                    items = $scope.moppingItem.items,
                    itemCount = items.length,
                    validPacking = true;
                    
                var lastGroup = 1;

                for( var i = 0; i < itemCount; i++ )
                {
                    if( items[i].converted_to )
                    { // packed item
                        var conversionItem = $filter( 'filter' )( packItems, { source_item_id: items[i].mopped_item_id, target_item_id: items[i].converted_to }, true )[0] || null;
                        if( conversionItem )
                        { // has valid conversion
                            var currentItem = packedItems[conversionItem.source_item_id + '_' + conversionItem.target_item_id + '_' + lastGroup];
                             
                            if( currentItem )
                            { // existing group, just update quantity and validity
                                currentItem['quantity'] += items[i].mopped_quantity;
                                currentItem['valid'] = ( currentItem.quantity == currentItem.conversion_factor );
                                currentItem['items'].push(i);
                            }
                            else
                            { // new group
                                packedItems[conversionItem.source_item_id + '_' + conversionItem.target_item_id + '_' + lastGroup] = {
                                    source_item_id: conversionItem.source_item_id,
                                    target_item_id: conversionItem.target_item_id,
                                    conversion_factor: conversionItem.conversion_factor,
                                    quantity: items[i].mopped_quantity,
                                    valid: items[i].mopped_quantity == conversionItem.conversion_factor,
                                    group_id: lastGroup,
                                    items: [ i ]
                                }
                                
                                currentItem = packedItems[conversionItem.source_item_id + '_' + conversionItem.target_item_id + '_' + lastGroup];
                            }
                            
                            items[i].group_id = lastGroup;
                            if( currentItem.valid )
                            {
                                lastGroup++;
                            }
                        }
                    }
                }

                for( var prop in packedItems )
                {
                    if( packedItems.hasOwnProperty(prop) )
                    {
                        for( var j = 0; j < packedItems[prop].items.length; j++ )
                        {
                            items[packedItems[prop].items[j]].valid = packedItems[prop].valid;
                        }
                        
                        if( ! packedItems[prop].valid )
                        {
                            validPacking = false;
                        }
                    }
                }            
                
                return validPacking;
            };
            
        $scope.prepareCollection = function()
            {
                // Make a deep copy to create a disconnected copy of the data from the scope model
                var data = angular.copy( $scope.moppingItem );
                
                var itemCount = data.items.length;
                for( var i = 0; i < itemCount; i++ )
                {
                    if( data.items[i].moppedItemVoid )
                    {
                        data.items[i].mopping_item_status = 2; // MOPPING_ITEM_VOIDED
                    }
                    
                    delete data.items[i].mopped_station_name;
                    delete data.items[i].mopped_item_name;
                    delete data.items[i].convert_to_name;
                    delete data.items[i].processor_name;
                    delete data.items[i].valid;
                    delete data.items[i].moppedItemVoid;
                }
                
                data.processing_datetime = $filter( 'date' )( $scope.moppingItem.processing_datetime, 'yyyy-MM-dd HH:mm:ss' );
                data.business_date = $filter( 'date' )( $scope.moppingItem.business_date, 'yyyy-MM-dd' );

                return data;
            };
            
        $scope.saveCollection = function()
            {
                if( $scope.checkItems() )
                {
                    var data = $scope.prepareCollection();
                    MoppingServices.processCollection( data ).then(
                        function( response )
                        {
                            $scope.updateInventory();
                            $scope.updateTransactions();
                            $scope.updateCollections();
                            if( $scope.data.editMode == 'new' )
                            {
                                $scope.moppingItem.items = [];
                            }
                            else
                            {
                                $state.go( 'store' );
                            }
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
                else
                {
                    alert( 'There are invalid item entries!' );
                    console.error( 'There are invalid item entries.' );
                }
            };
        
        
        // Initialize controller
        
        // Items
        var initItems = MiscServices.getItems().then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    items = response.data;
                    $scope.data.moppedItems = items;
                    $scope.input.moppedItem = items[0];
                }
                else
                {
                    console.error( response.error );
                }
            },
            function( reason )
            {
                console.error( reason );
            });
            
            
        // Packed Items
        var initPackageItems = ConversionServices.getPackageConversion().then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    packItems = response.data;
                    $scope.data.packAsItems = packItems;
                }
                else
                {
                    console.error( response.error );
                }
            },
            function( reason )
            {
                console.error( reason );
            });
            
        $q.all( [ initItems, initPackageItems ] ).then(
            function( responses )
            {
                $scope.onItemChange();
            });
        
        // Cashier shifts
        var initCashierShifts = StoreServices.getShifts( 4 ).then( // Store type Cashroom
            function( response )
            {
                if( response.status == 'ok' )
                {
                    $scope.data.cashierShifts = response.data;
                    $scope.data.selectedCashierShift = $scope.data.cashierShifts[0] || null;
                    $scope.onChangeCashierShift();
                }
            },
            function ( reason )
            {
                console.error( reason );
            });
            
        // Processors
        var initProcessors = UserServices.getUsers().then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    $scope.data.processors = response.data;
                }
                else
                {
                    console.error( response.error );
                }
            },
            function( reason )
            {
                console.error( reason );
            });
            
        // Load moppingItem
        if( $stateParams.moppingItem )
        {
            $q.all( [ initItems, initPackageItems, initCashierShifts, initProcessors ] ).then(
                function( responses )
                {
                    $scope.data.editMode = $stateParams.editMode || 'view';
                    MoppingServices.getCollection( $stateParams.moppingItem.id ).then(
                        function( response )
                        {
                            if( response.status == 'ok' )
                            {
                                $scope.moppingItem = response.data;
                                $scope.moppingItem.processing_datetime = Date.parse( $stateParams.moppingItem.processing_datetime );
                                $scope.moppingItem.business_date = Date.parse( $stateParams.moppingItem.business_date );
                                $scope.data.selectedCashierShift = $filter( 'filter')( $scope.data.cashierShifts, { id: $scope.moppingItem.cashier_shift_id }, true )[0];
                                $scope.checkItems();
                            }
                            else
                            {
                                console.error( 'Unable to load mopping collection record' );
                            }
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                });
        }
    }
]);

app.controller( 'AllocationController', [ '$scope', '$q', '$filter', '$state', '$stateParams', 'AllocationServices', 'StoreServices', 'ConversionServices', 'MiscServices',
    function( $scope, $q, $filter, $state, $stateParams, AllocationServices, StoreServices, ConversionServices, MiscServices )
    {
        var assigneeShifts = [];
        var categories = [];
        var items = [];
        
        function category_filter( value, index, array )
        {
            var result = true;
            var assigneeType = $scope.data.selectedAssigneeType;
            var phase = $scope.data.allocationPhase;
            var status = $scope.allocationItem.allocation_status || 1; // ALLOCATION_SCHEDULED
            var preAllocationCategories = [ 'Initial Allocation', 'Magazine Load' ];
            var postAllocationCategories = [ 'Additional Allocation', 'Magazine Load' ];
            
            switch( assigneeType.id )
            {
                case 1: // teller
                    if( value.is_teller != true )
                        return false;
                    break;
                
                case 2: // machine
                    if( value.is_machine != true )
                        return false;
                    break;
                    
                default:
                    return false;
            }
            
            switch( phase )
            {
                case 'allocation':
                    if( ! value.is_allocation_category )
                        return false;
                        
                    switch( status )
                    {
                        case 1: // ALLOCATION_SCHEDULED
                            if( preAllocationCategories.indexOf( value.category ) == -1 )
                                return false;
                            break;
                            
                        default:
                            if( postAllocationCategories.indexOf( value.category ) == -1 )
                                return false;
                            // do nothing
                    }
                    break;
                    
                case 'remittance':
                    if( ! value.is_remittance_category )
                        return false;
                    break;
                    
                default:
                    return false;
            }
            
            return true;
        }
        
        $scope.data = {
            editMode: $stateParams.editMode || 'new',
            businessDatepicker: { format: 'yyyy-MM-dd', opened: false },
            assigneeShifts: [],
            selectedAssigneeShift: null,
            assigneeTypes: [
                    { id: 1, typeName: 'Station Teller' },
                    { id: 2, typeName: 'Ticket Vending Machine' }
                ],
            selectedAssigneeType: { id: 1, typeName: 'Station Teller' },
            inventoryItems: [],
            selectedItem: null,
            categories: [],
            allocationPhase: 'allocation',
            
            assigneeLabel: 'Teller Name',
            assigneeShiftLabel: 'Teller Shift',
            remittancesTabLabel: 'Remittances'    
        };
        
        $scope.input = {
            category: null,
            item: null,
            quantity: null,
        };
        
        $scope.allocationItem = {
                store_id: $scope.currentStore.id,
                business_date: new Date(),
                shift_id: null,
                station_id: null,
                assignee: null,
                assignee_type: 1,
                allocation_status: 1, // ALLOCATION_SCHEDULED
                cashier_id: $scope.user.id || null,
                allocations: [],
                remittances: []
            };
            
        $scope.showDatePicker = function()
            {
                $scope.data.businessDatepicker.opened = true;
            };
            
        $scope.onItemChange = function() {};
        
        $scope.onTabChange = function() {};
        
        $scope.updatePhase = function( phase )
            {
                $scope.data.allocationPhase = phase;
                $scope.updateCategories();
                $scope.updateAllocatableItems();
            }
        
        $scope.updateCategories = function()
            {
                $scope.data.categories = $filter( 'filter' )( categories, category_filter, true );
                if( $scope.data.categories.length )
                {
                    $scope.input.category = $scope.data.categories[0];
                }
            };
            
        $scope.updateAllocatableItems = function()
            {
                var filter = {};
                
                if( $scope.data.selectedAssigneeType.id == 1 )
                { // Teller
                    if( $scope.data.allocationPhase == 'allocation' )
                    {
                        filter['teller_allocatable'] = true;
                    }
                    else if( $scope.data.allocationPhase == 'remittance' )
                    {
                        filter['teller_remittable'] = true;
                    }
                }
                else if( $scope.data.selectedAssigneeType.id == 2 )
                { // Ticket Vending Machine
                    if( $scope.data.allocationPhase == 'allocation' )
                    {
                        filter['machine_allocatable'] = true;
                    }
                    else if( $scope.data.allocationPhase == 'remittance' )
                    {
                        filter['machine_remittable'] = true;
                    }
                }
                
                $scope.data.inventoryItems = $filter( 'filter' )( items, filter, true );
                if( $scope.data.inventoryItems.length )
                {
                    $scope.input.item = $scope.data.inventoryItems[0];
                }
            };
        
        $scope.onAssigneeTypeChange = function()
            {
                if( $scope.data.selectedAssigneeType.id == 1 )
                { // Station teller
                    $scope.data.assigneeShifts = $filter( 'filter' )( assigneeShifts, { store_type: 0 }, true );
                    $scope.data.assigneeLabel = 'Teller Name';
                    $scope.data.assigneeShiftLabel = 'Teller Shift';
                    $scope.data.remittancesTabLabel = 'Remittances';
                }
                else if( $scope.data.selectedAssigneeType.id == 2 )
                {
                    $scope.data.assigneeShifts = $filter( 'filter' )( assigneeShifts, { store_type: 1 }, true );
                    $scope.data.assigneeLabel = 'TVM Number';
                    $scope.data.assigneeShiftLabel = 'TVM Shift';
                    $scope.data.remittancesTabLabel = 'Reject Bin';
                }
                else
                {
                    $scope.data.assigneeShifts = assigneeShifts;
                }
                
                if( $scope.data.assigneeShifts.length )
                {
                    if( $scope.allocationItem.shift_id )
                    {
                        $scope.data.selectedAssigneeShift = $filter( 'filter')( $scope.data.assigneeShifts, { id: $scope.allocationItem.shift_id }, true )[0];
                    }
                    else
                    {
                        $scope.data.selectedAssigneeShift = $scope.data.assigneeShifts[0];
                    }                    
                    $scope.allocationItem.shift_id = $scope.data.selectedAssigneeShift.id;
                }
                $scope.allocationItem.assignee_type = $scope.data.selectedAssigneeType.id;
                
                $scope.updateCategories();
                $scope.updateAllocatableItems();
                
            };
        
        $scope.onAssigneeShiftChange = function()
            {
                $scope.allocationItem.shift_id = $scope.data.selectedAssigneeShift.id;
            }
        
        $scope.addAllocationItem = function()
            {
                if( ( event.type == 'keypress' ) && ( event.charCode == 13 )
                        && $scope.input.category
                        && $scope.input.item
                        && $scope.input.quantity > 0 )
                {
                    var data = {
                            cashier_shift_num: $scope.currentShift.shift_num,
                            category_name: $scope.input.category.category,
                            item_name: $scope.input.item.item_name,
                                
                            cashier_shift_id: $scope.currentShift.id,
                            allocated_item_id: $scope.input.item.item_id,
                            allocated_quantity: $scope.input.quantity,
                            allocation_category_id: $scope.input.category.id,
                            allocation_datetime: new Date(),
                            allocation_item_status: 1
                        };
                    switch( $scope.data.allocationPhase )
                    {
                        case 'allocation':
                            data.allocation_item_status = 10; // ALLOCATION_ITEM_SCHEDULED
                            $scope.allocationItem.allocations.push( data );
                            break;
                            
                        case 'remittance':
                            data.allocation_item_status = 20; // REMITTANCE_ITEM_PENDING
                            $scope.allocationItem.remittances.push( data );
                            break;
                            
                        default:
                            // do nothing
                    }
                    
                    // Clear quantity
                    $scope.input.quantity = null;
                }
            };
            
        $scope.removeAllocationItem = function( phase, itemRow )
            {
                switch( phase )
                {
                    case 'allocation':
                        if( itemRow.allocation_item_status == 10 ) // ALLOCATION_ITEM_SCHEDULED
                        { // remove only scheduled items
                            var index = $scope.allocationItem.allocations.indexOf( itemRow );
                            $scope.allocationItem.allocations.splice( index, 1 );
                        }
                        break;
                    case 'remittance':
                        if( itemRow.allocation_item_status == 20 ) // REMITTANCE_ITEM_PENDING
                        { // remove only scheduled items
                            var index = $scope.allocationItem.remittances.indexOf( itemRow );
                            $scope.allocationItem.remittances.splice( index, 1 );
                        }
                        break;
                }
            };
            
        $scope.checkItems = function()
            {
                var allocations = $scope.allocationItem.allocations;
                var remittances = $scope.allocationItem.remittances;
                var allocationCount = allocations.length;
                var remittanceCount = remittances.length;
                
                var preAllocationCategories = [ 'Initial Allocation', 'Magazine Load' ];
                var postAllocationCategories = [ 'Additional Allocation', 'Magazine Load' ];
                
                if( $scope.allocationItem.allocations.length == 0 )
                {
                    alert( 'This allocation does not contain any items.' );
                    return false;
                }
                
                switch( $scope.allocationItem.allocation_status )
                {
                    case 1: // scheduled
                        break;
                        
                    case 2: // allocated
                        break;
                        
                    case 3: // remitted
                        break;
                        
                    case 4: // cancelled
                        break;
                        
                    default:
                        return false;
                }
                
                return true;
            };
            
        $scope.prepareAllocation = function()
            {
                // Make a deep copy to create a disconnected copy of the data from the scope model
                var data = angular.copy( $scope.allocationItem );
                
                var allocationCount = data.allocations.length;
                var remittanceCount = data.remittances.length;
                
                for( var i = 0; i < allocationCount; i++ )
                { 
                    if( data.allocations[i].allocationItemVoid )
                    {
                        if( data.allocations[i].allocation_item_status == 10 ) // ALLOCATION_ITEM_SCHEDULED
                        {
                            data.allocations[i].allocation_item_status = 12; // ALLOCATION_ITEM_CANCELLED
                        }
                        else if( data.allocations[i].allocation_item_status == 11 ) // ALLOCATION_ITEM_ALLOCATED
                        {
                            data.allocations[i].allocation_item_status = 13; // ALLOCATION_ITEM_VOID
                        }
                    }
                    delete data.allocations[i].cashier_shift_num;
                    delete data.allocations[i].category_name;
                    delete data.allocations[i].item_name;
                    delete data.allocations[i].allocationItemVoid;
                    
                    data.allocations[i].allocation_datetime = $filter( 'date' )( data.allocations[i].allocation_datetime, 'yyyy-MM-dd HH:mm:ss' );  
                }
                
                for( var i = 0; i < remittanceCount; i++ )
                {
                    if( data.remittances[i].allocationItemVoid )
                    {
                        data.remittances[i].allocation_item_status = 22; // REMITTANCE_ITEM_VOID
                    }
                    delete data.remittances[i].cashier_shift_num;
                    delete data.remittances[i].category_name;
                    delete data.remittances[i].item_name;
                    delete data.remittances[i].allocationItemVoid;
                    
                    data.remittances[i].allocation_datetime = $filter( 'date' )( data.remittances[i].allocation_datetime, 'yyyy-MM-dd HH:mm:ss' );
                }
                
                data.business_date = $filter( 'date' )( $scope.allocationItem.business_date, 'yyyy-MM-dd' );
                
                return data;
            };
            
        $scope.saveAllocation = function()
            {
                if( $scope.checkItems() )
                {
                    var data = $scope.prepareAllocation();
                    AllocationServices.processAllocation( data ).then(
                        function( response )
                        {
                            $scope.updateInventory();
                            $scope.updateTransactions();
                            $scope.updateAllocations();
                            $state.go( 'store' );
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
            };
            
        $scope.allocateAllocation = function()
            {
                if( $scope.checkItems() )
                {
                    if( ! $scope.allocationItem.assignee )
                    {
                        alert( 'Missing assignee value' );
                        return false;
                    }
                    var data = $scope.prepareAllocation();
                    AllocationServices.allocate( data ).then(
                        function( response )
                        {
                            $scope.updateInventory();
                            $scope.updateTransactions();
                            $scope.updateAllocations();
                            $state.go( 'store' );
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
            }
            
        $scope.remitAllocation = function()
            {
                if( $scope.checkItems() )
                {
                    var data = $scope.prepareAllocation();
                    AllocationServices.remit( data ).then(
                        function( response )
                        {
                            $scope.updateInventory();
                            $scope.updateTransactions();
                            $scope.updateAllocations();
                            $state.go( 'store' );
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                }
            }
        
        // Initialize controller
        var initTellerShifts = StoreServices.getShifts( [ 1, 0 ] ).then(  // Store type None
            function( response )
            {
                if( response.status == 'ok' )
                {
                    assigneeShifts = response.data;
                    $scope.onAssigneeTypeChange();
                    $scope.onAssigneeShiftChange();
                }
            },
            function( reason )
            {
                console.debug( reason );
            });
            
        var initCategories = MiscServices.getItemCategories().then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    categories = response.data;
                    if( response.data.length )
                    {
                        $scope.updateCategories();
                    }
                }
            },
            function( reason )
            {
                console.debug( reason );
            });
        
        var initInventoryItems = MiscServices.getInventoryItems( $scope.currentStore.id ).then(
            function( response )
            {
                if( response.status == 'ok' )
                {
                    items = response.data;
                    if( response.data.length )
                    {
                        $scope.input.item = response.data[0];
                    }
                }
                else
                {
                    console.debug( response.error );
                }
            },
            function( reason )
            {
                console.debug( reason );
            });
            
        $q.all( [ initTellerShifts, initCategories, initInventoryItems ] ).then(
            function( responses )
            {
                $scope.updateAllocatableItems();
            });
            
        // Load allocation item
        if( $stateParams.allocationItem )
        {
            $q.all( [ initTellerShifts, initCategories, initInventoryItems ] ).then(
                function( responses )
                {
                    $scope.data.editMode = $stateParams.editMode || 'view';
                    AllocationServices.getAllocation( $stateParams.allocationItem.id ).then(
                        function( response )
                        {
                            if( response.status == 'ok' )
                            {
                                $scope.allocationItem = response.data;
                                $scope.allocationItem.business_date = Date.parse( $stateParams.allocationItem.business_date );
                                var allocationsCount = $scope.allocationItem.allocations.length;
                                for( var i = 0; i < allocationsCount; i++ )
                                {
                                    $scope.allocationItem.allocations[i].allocation_datetime =  Date.parse( $scope.allocationItem.allocations[i].allocation_datetime );
                                }
                                
                                var remittancesCount = $scope.allocationItem.remittances.length;
                                for( var i = 0; i < remittancesCount; i++ )
                                {
                                    $scope.allocationItem.remittances[i].allocation_datetime =  Date.parse( $scope.allocationItem.remittances[i].allocation_datetime );
                                }
                                
                                $scope.data.selectedAssigneeShift = $filter( 'filter')( assigneeShifts, { id: $scope.allocationItem.shift_id }, true )[0];
                                $scope.data.selectedAssigneeType = $filter( 'filter')( $scope.data.assigneeTypes, { id: $scope.allocationItem.assignee_type }, true )[0];
                                
                                $scope.onAssigneeTypeChange();
                                $scope.checkItems();
                            }
                            else
                            {
                                console.error( 'Unable to load mopping collection record' );
                            }
                        },
                        function( reason )
                        {
                            console.error( reason );
                        });
                });
        }
        
    }
]);