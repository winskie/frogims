angular.module( 'coreModels' ).factory( 'Allocation', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'AllocationItem',
	function( $http, $q, $filter, baseUrl, session, notifications, AllocationItem )
	{
		var id;
		var store_id;
    var business_date;
		var shift_id;
    var station_id;
		var assignee;
    var assignee_type;
    var cashier_id;
    var allocation_status;

    var allocations;
    var remittances;

		var allocationSummary;

		var allocationStatus = {
				'1': 'Scheduled',
				'2': 'Allocated',
				'3': 'Completed',
				'4': 'Cancelled'
			};


		/**
		 * Constructor
		 */
		function Allocation( data )
		{
			this.loadData( data );
		};


		Allocation.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var allocationsArray = [];

					for( var i = 0; i < n; i++ )
					{
						allocationsArray.push( new Allocation( data[i] ) );
					}

					return allocationsArray;
				}
				else if( data.constructor == Object )
				{
					return new Allocation( data );
				}
			};


		Allocation.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.store_id = session.data.currentStore.id;
				me.business_date = new Date();
				me.shift_id = null;
				me.station_id = null;
				me.assignee = null;
				me.assignee_type = null;
				me.cashier_id = null;
				me.allocation_status = 1;

				me.allocations = [];
				me.remittances = [];

				me.allocationSummary = [];

				if( data )
				{
					var allocationItems = [];
					var remittanceItems = [];
					if( data.allocations )
					{
						angular.copy( data.allocations, allocationItems );
						delete data.allocations;
					}
					if( data.remittances )
					{
						angular.copy( data.remittances, remittanceItems );
						delete data.remittances;
					}

					angular.merge( me, data );

					if( me.business_date )
					{
						me.business_date = Date.parse( me.business_date );
					}

					// Allocation items
					if( allocationItems )
					{
						var n = allocationItems.length;
						for( var i = 0; i < n; i++ )
						{
							me.allocations.push( new AllocationItem( allocationItems[i] ) );
						}
					}

					// Remittance items
					if( remittanceItems )
					{
						var n = remittanceItems.length;
						for( var i = 0; i < n; i++ )
						{
							me.remittances.push( new AllocationItem( remittanceItems[i] ) );
						}
					}

					me.updateAllocationSummary();
				}
			};


		Allocation.prototype.get = function( field )
			{
				switch( field )
				{
					case 'allocationStatus':
						if( this.assignee_type == 2 && this.allocation_status == 1 && this.remittances.length > 0 )
						{
							return 'Remitted';
						}
						else
						{
							return allocationStatus[this.allocation_status.toString()];
						}

					default:
						if( this.hasOwnProperty( field ) )
						{
							return this[field];
						}
						else
						{
							console.error( 'The property [' + field + '] does not exist!' );
							return undefined;
						}
				}
			};


		Allocation.prototype.canEdit = function()
			{
				return ( this.allocation_status == 1 || this.allocation_status == 2 ) &&
							 session.checkPermissions( 'allocations', 'edit' ) &&
							 this.store_id == session.data.currentStore.id;
			};


		Allocation.prototype.canAllocate = function( showAction )
			{
				return this.allocation_status == 1 &&
							 session.checkPermissions( 'allocations', 'edit' ) &&
							 ( showAction || this.getValidAllocations().length > 0 ) &&
							 ( showAction || this.assignee );
			};


		Allocation.prototype.canComplete = function( showAction )
			{
				switch( this.assignee_type )
				{
					case 1: // Station Teller
						return this.allocation_status == 2 &&
									 session.checkPermissions( 'allocations', 'complete' ) &&
									 ( showAction || this.getValidAllocations().length > 0 ) &&
									 ( showAction || this.assignee );

					case 2: // TVM
						return ( this.allocation_status == 1 || this.allocation_status == 2 ) &&
									 session.checkPermissions( 'allocations', 'complete' ) &&
									 ( showAction || ( this.getValidAllocations().length > 0 || this.getValidRemittances().length > 0 ) ) &&
									 ( showAction || this.assignee );
				}
			};


		Allocation.prototype.canCancel = function()
			{
				return this.allocation_status == 1 && this.remittances.length == 0 && session.checkPermissions( 'allocations', 'edit' );
			};


		Allocation.prototype.getValidAllocations = function()
			{
				var n = this.allocations.length;
				var validAllocations = [];
				for( var i = 0; i < n; i++ )
				{
					if( ( this.allocations[i].allocation_item_status == 10 || this.allocations[i].allocation_item_status == 11 ) &&
							this.allocations[i].allocated_quantity > 0 &&
							!this.allocations[i].markedVoid )
					{
						validAllocations.push( this.allocations[i] );
					}
				}

				return validAllocations;
			};


		Allocation.prototype.getValidRemittances = function()
			{
				var n = this.remittances.length;
				var validRemittances = [];
				for( var i = 0; i < n; i++ )
				{
					if( ( this.remittances[i].allocation_item_status == 20 || this.remittances[i].allocation_item_status == 21 ) &&
							this.remittances[i].allocated_quantity > 0 &&
							!this.remittances[i].markedVoid )
					{
						validRemittances.push( this.remittances[i] );
					}
				}

				return validRemittances;
			};


		Allocation.prototype.addAllocationItem = function( item )
			{
				this.allocations.push( item );
				this.updateAllocationSummary();
			};


		Allocation.prototype.addRemittanceItem = function( item )
			{
				this.remittances.push( item );
				this.updateAllocationSummary();
			};


		Allocation.prototype.updateAllocationSummary = function()
			{
				this.allocationSummary = [];
				var tempObj = {};
				var ignoredStatus = [ 12, 13, 22 ]; // ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED, REMITTANCE_ITEM_VOIDED

				var n = this.allocations.length;
				var m = this.remittances.length;

				switch( this.assignee_type )
				{
					case 1: // Station Teller
						for( var i = 0; i < n; i++ )
						{
							if( ignoredStatus.indexOf( this.allocations[i].allocation_item_status ) != -1 || this.allocations[i].markedVoid )
							{
								continue;
							}

							if( !tempObj[this.allocations[i].allocated_item_id] )
							{
								tempObj[this.allocations[i].allocated_item_id] = {
										item_name: this.allocations[i].item_name,
										item_description: this.allocations[i].item_description,
										initial: 0,
										additional: 0,
										remitted: 0
									};
							}

							if( this.allocations[i].category_name == 'Initial Allocation' )
							{
								tempObj[this.allocations[i].allocated_item_id].initial += this.allocations[i].allocated_quantity;
							}
							else if( this.allocations[i].category_name == 'Additional Allocation' )
							{
								tempObj[this.allocations[i].allocated_item_id].additional += this.allocations[i].allocated_quantity;
							}
						}

						for( var i = 0; i < m; i ++ )
						{
							if( ignoredStatus.indexOf( this.remittances[i].allocation_item_status ) != -1 )
							{
								continue;
							}

							if( !tempObj[this.remittances[i].allocated_item_id] )
							{
								tempObj[this.remittances[i].allocated_item_id] = {
										item_name: this.remittances[i].item_name,
										item_description: this.remittances[i].item_description,
										initial: 0,
										additional: 0,
										remitted: 0
									};
							}

							tempObj[this.remittances[i].allocated_item_id].remitted += this.remittances[i].allocated_quantity;
						}
						break;

					case 2: // TVM
						for( var i = 0; i < n; i++ )
						{
							if( ignoredStatus.indexOf( this.allocations[i].allocation_item_status ) != -1 )
							{
								continue;
							}

							if( !tempObj[this.allocations[i].allocated_item_id] )
							{
								tempObj[this.allocations[i].allocated_item_id] = {
										item_name: this.allocations[i].item_name,
										item_description: this.allocations[i].item_description,
										loaded: 0,
										loose: 0,
										rejected: 0
									};
							}

							tempObj[this.allocations[i].allocated_item_id].loaded += this.allocations[i].allocated_quantity;
						}

						for( var i = 0; i < m; i ++ )
						{
							if( ignoredStatus.indexOf( this.remittances[i].allocation_item_status ) != -1 )
							{
								continue;
							}

							if( !tempObj[this.remittances[i].allocated_item_id] )
							{
								tempObj[this.remittances[i].allocated_item_id] = {
										item_name: this.remittances[i].item_name,
										item_description: this.remittances[i].item_description,
										loaded: 0,
										unsold: 0,
										rejected: 0
									};
							}

							if( this.remittances[i].category_name == 'Unsold / Loose' )
							{
								tempObj[this.remittances[i].allocated_item_id].unsold += this.remittances[i].allocated_quantity;
							}
							else if( this.remittances[i].category_name == 'Reject Bin' )
							{
								tempObj[this.remittances[i].allocated_item_id].rejected += this.remittances[i].allocated_quantity;
							}
						}
						break;

					default:
						// Do nothing
				}

				// Populate the allocation summary removing the keys
				for( var key in tempObj )
				{
					this.allocationSummary.push( tempObj[key] );
				}

				return this.allocationSummary;
			};


		Allocation.prototype.removeAllocationItem = function( item )
			{
				if( item.id == undefined )
				{
					var index = this.allocations.indexOf( item );
					this.allocations.splice( index, 1 );
				}
				else
				{
					item.void( !item.void() );
				}

				this.updateAllocationSummary();
			};


		Allocation.prototype.removeRemittanceItem = function( item )
			{
				if( item.id == undefined )
				{
					var index = this.remittances.indexOf( item );
					this.remittances.splice( index, 1 );
				}
				else
				{
					item.void( !item.void() );
				}

				this.updateAllocationSummary();
			};


		Allocation.prototype.checkAllocation = function( action )
			{
				var allocationCount = this.allocations.length;
				var remittanceCount = this.remittances.length;

				var preAllocationCategories = [ 'Initial Allocation', 'Magazine Load' ];
				var postAllocationCategories = [ 'Additional Allocation', 'Magazine Load' ];

				var hasValidAllocationItem = this.getValidAllocations().length > 0;
				var hasValidRemittanceItem = this.getValidRemittances().length > 0;

				switch( action )
				{
					case 'schedule':
					case 'allocate':
						if( this.assignee_type == 1 && allocationCount == 0 ) // ALLOCATION_ASSIGNEE_TELLER
						{
							notifications.alert( 'Allocation does not contain any items', 'warning' );
							return false;
						}

						if( action == 'schedule' && ! hasValidAllocationItem && ! hasValidRemittanceItem  )
						{
							notifications.alert( 'Record does not contain any valid allocation or remittance items', 'warning' );
							return false;
						}

						if( action == 'allocate' && ! hasValidAllocationItem )
						{
							notifications.alert( 'Allocation does not contain any valid items', 'warning' );
							return false;
						}

						if( action == 'allocate' && ! this.assignee )
						{
							notifications.alert( 'Please enter ' + $scope.data.assigneeLabel, 'warning' );
							return false;
						}

						if( action == 'schedule' && !this.assignee
							&& this.assignee_type == 2 // ALLOCATION_ASSIGNEE_MACHINE
							&& hasValidRemittanceItem )
						{
							notifications.alert( 'Please enter assignee', 'warning' );
							return false;
						}
						break;

					case 'complete':
						if( !hasValidAllocationItem && !hasValidRemittanceItem )
						{
							notifications.alert( 'Record does not contain any valid allocation or remittance items', 'warning' );
							return false;
						}

						if( !this.assignee )
						{
							notifications.alert( 'Please enter assignee', 'warning' );
							return false;
						}
						break;

					default:
						// do nothing
				}

				if( this.assignee_type == 1 && !hasValidAllocationItem ) // ALLOCATION_ASSIGNEE_TELLER
				{ // Tellers must have a valid allocation
					notifications.alert( 'Allocation does not contain any valid items', 'warning' );
					return false;
				}

				switch( this.allocation_status )
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
						notifications.alert( 'Invalid action', 'error' );
						return false;
				}

				return true;
			};


		Allocation.prototype.prepareAllocationData = function()
			{
				var data = {
						id: this.id,
						store_id: this.store_id,
						business_date: this.business_date ? $filter( 'date' )( this.business_date, 'yyyy-MM-dd' ) : null,
						shift_id: this.shift_id,
						station_id: this.station_id,
						assignee: this.assignee,
						assignee_type: this.assignee_type,
						cashier_id: this.cashier_id,
						allocation_status: this.allocation_status,

						allocations: [],
						remittances: []
					};

				var allocations = this.allocations;
				var remittances = this.remittances;
				var m = allocations.length;
				var n = remittances.length;

				for( var i = 0; i < m; i++ )
				{
					var allocationData = {
							id: allocations[i].id,
							allocation_id: allocations[i].allocation_id,
							cashier_id: allocations[i].cashier_id,
							allocated_item_id: allocations[i].allocated_item_id,
							allocated_quantity: allocations[i].allocated_quantity,
							allocation_category_id: allocations[i].allocation_category_id,
							allocation_datetime: allocations[i].allocation_datetime ? $filter( 'date' )( allocations[i].allocation_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
							allocation_item_status: allocations[i].allocation_item_status
						};

					// Change item status of voided items
					if( allocations[i].markedVoid )
					{
						allocationData.allocation_item_status = 13 // ALLOCATION_ITEM_VOIDED
					}

					data.allocations.push( allocationData );
				}

				for( var i = 0; i < n; i++ )
				{
					var remittanceData = {
							id: remittances[i].id,
							allocation_id: remittances[i].allocation_id,
							cashier_id: remittances[i].cashier_id,
							allocated_item_id: remittances[i].allocated_item_id,
							allocated_quantity: remittances[i].allocated_quantity,
							allocation_category_id: remittances[i].allocation_category_id,
							allocation_datetime: remittances[i].allocation_datetime ? $filter( 'date' )( remittances[i].allocation_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
							allocation_item_status: remittances[i].allocation_item_status
						};

					// Change item status of voided items
					if( remittances[i].markedVoid )
					{
						remittanceData.allocation_item_status = 22 // REMITTANCE_ITEM_VOIDED
					}

					data.remittances.push( remittanceData );
				}

				return data;
			};


		Allocation.prototype.save = function( status )
			{
				var me = this;
				var deferred = $q.defer();

				if( this.checkAllocation( status ) )
				{
					var allocationData = this.prepareAllocationData();

					var allocationUrl = baseUrl + 'index.php/api/v1/allocations/';
					switch( status )
					{
						case 'allocate':
							allocationUrl += 'allocate';
							break;

						case 'remit':
							allocationUrl += 'remit';
							break;

						case 'cancel':
							allocationUrl += 'cancel';
							break;

						default:
							// do nothing
					}

					$http({
						method: 'POST',
						url: allocationUrl,
						data: allocationData
					}).then(
						function( response )
						{
							if( response.data.status == 'ok' )
							{
								me.loadData( response.data.data );
								deferred.resolve( me );
							}
							else
							{
								deferred.reject( response.data.errorMsg );
							}
						},
						function( reason )
						{
							deferred.reject( reason );
						}
					);
				}
				else
				{
					deferred.reject( 'Failed allocation data check' );
				}

				return deferred.promise;
			};

		return Allocation;
	}
]);


angular.module( 'coreModels' ).factory( 'AllocationItem', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var allocation_id;
		var cashier_id;
		var allocated_item_id;
		var allocated_quantity;
		var allocation_category_id;
		var allocation_datetime;
		var allocation_item_status;

		var allocationItemStatus = {
				'10': 'Scheduled',
				'11': 'Allocated',
				'12': 'Cancelled',
				'13': 'Voided',
				'20': 'Pending',
				'21': 'Remitted',
				'22': 'Voided'
			};


		/**
		 * Constructor
		 */
		function AllocationItem( data )
		{
			this.loadData( data );
		}


		AllocationItem.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.allocation_id = null;
				me.cashier_id = null;
    		me.allocated_item_id = null;
    		me.allocated_quantity = null;
    		me.allocation_category_id = null;
    		me.allocation_datetime = new Date();
    		me.allocation_item_status = 1;

				if( data )
				{
					angular.merge( me, data );

					if( me.business_date )
					{
						me.allocation_datetime = Date.parse( me.allocation_datetime );
					}
				}
			};


		AllocationItem.prototype.get = function( field )
			{
				switch( field )
				{
					case 'allocationItemStatus':
						return allocationItemStatus[this.allocation_item_status.toString()];

					default:
						if( this.hasOwnProperty( field ) )
						{
							return this[field];
						}
						else
						{
							console.error( 'The property [' + field + '] does not exist!' );
							return undefined;
						}
				}
			};

		AllocationItem.prototype.void = function( status )
			{
				if( status == undefined)
				{
					return this.markedVoid;
				}
				else if( status == true || status == false )
				{
					this.markedVoid = status;
				}
			};

		return AllocationItem;
	}
]);
