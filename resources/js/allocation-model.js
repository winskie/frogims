angular.module( 'coreModels' ).factory( 'Allocation', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'AllocationItem', 'AllocationSalesItem', 'ShiftDetailCashReport',
	function( $http, $q, $filter, baseUrl, session, notifications, AllocationItem, AllocationSalesItem, ShiftDetailCashReport )
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
		var cash_allocations;
		var remittances;
		var cash_remittances;
		var ticket_sales;
		var sales;
		var cash_reports;

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
				me.cash_allocations = [];
				me.remittances = [];
				me.cash_remittances = [];
				me.ticket_sales = [];
				me.sales = [];
				me.cash_reports = [];

				me.allocationSummary = [];

				if( data )
				{
					var allocationItems = [];
					var allocationCashItems = [];
					var remittanceItems = [];
					var remittanceCashItems = [];
					var ticketSaleItems = [];
					var salesItems = [];
					var cashReports = [];

					if( data.allocations )
					{
						angular.copy( data.allocations, allocationItems );
						delete data.allocations;
					}
					if( data.cash_allocations )
					{
						angular.copy( data.cash_allocations, allocationCashItems );
						delete data.cash_allocations;
					}
					if( data.remittances )
					{
						angular.copy( data.remittances, remittanceItems );
						delete data.remittances;
					}
					if( data.cash_remittances )
					{
						angular.copy( data.cash_remittances, remittanceCashItems );
						delete data.cash_remittances;
					}

					if( data.ticket_sales )
					{
						angular.copy( data.ticket_sales, ticketSaleItems );
						delete data.ticket_sales;
					}

					if( data.sales )
					{
						angular.copy( data.sales, salesItems );
						delete data.sales;
					}

					if( data.cash_reports )
					{
						angular.copy( data.cash_reports, cashReports );
						delete data.cash_reports;
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

					// Cash Allocation items
					if( allocationCashItems )
					{
						var n = allocationCashItems.length;
						for( var i = 0; i < n; i++ )
						{
							me.cash_allocations.push( new AllocationItem( allocationCashItems[i] ) );
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

					// Remittance items
					if( remittanceCashItems )
					{
						var n = remittanceCashItems.length;
						for( var i = 0; i < n; i++ )
						{
							me.cash_remittances.push( new AllocationItem( remittanceCashItems[i] ) );
						}
					}

					// Ticket sale Items
					if( ticketSaleItems )
					{
						var n = ticketSaleItems.length;
						for( var i = 0; i < n; i++ )
						{
							me.ticket_sales.push( new AllocationItem( ticketSaleItems[i]) );
						}
					}

					// Sales Items
					if( salesItems )
					{
						var n = salesItems.length;
						for( var i = 0; i < n; i++ )
						{
							me.sales.push( new AllocationSalesItem( salesItems[i] ) );
						}
					}

					// Cash Reports
					if( cashReports )
					{
						var n = cashReports.length;
						for( var i = 0; i < n; i++ )
						{
							me.cash_reports.push( new ShiftDetailCashReport( cashReports[i] ) );
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
							 ( showAction || this.getValidAllocations().length > 0 || this.getValidCashAllocations().length > 0 ) &&
							 ( showAction || this.assignee );
			};


		Allocation.prototype.canComplete = function( showAction )
			{
				switch( this.assignee_type )
				{
					case 1: // Station Teller
						return this.allocation_status == 2
									 && session.checkPermissions( 'allocations', 'complete' )
									 && ( showAction || !this.hasPendingAllocation() )
									 && ( showAction || this.getValidAllocations().length > 0 || this.getValidCashAllocations().length > 0 )
									 && ( showAction || this.assignee );

					case 2: // TVM
						return ( this.allocation_status == 1 || this.allocation_status == 2 )
									 && session.checkPermissions( 'allocations', 'complete' )
									 && ( showAction || !this.hasPendingAllocation() )
									 && ( showAction || ( this.getValidAllocations().length > 0 || this.getValidCashAllocations().length > 0
											 || this.getValidRemittances().length > 0 || this.getValidCashRemittances().length > 0
											 || this.getValidTicketSales().length > 0 || this.getValidSales().length > 0 ) )
									 && ( showAction || this.assignee );
				}
			};


		Allocation.prototype.canCancel = function()
			{
				return this.allocation_status == 1 && ( this.remittances.length == 0 && this.cash_remittances.length == 0 )
						&& session.checkPermissions( 'allocations', 'edit' );
			};


		Allocation.prototype.hasPendingAllocation = function()
			{
				var n = this.allocations.length;
				for( var i = 0; i < n; i++ )
				{
					if( this.allocations[i].allocation_item_status == 10 ) // ALLOCATION_ITEM_SCHEDULED
					{
						return true;
					}
				}

				n = this.cash_allocations.length;
				for( var i = 0; i < n; i++ )
				{
					if( this.cash_allocations[i].allocation_item_status == 10 ) // ALLOCATION_ITEM_SCHEDULED
					{
						return true;
					}
				}

				return false;
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


		Allocation.prototype.getValidCashAllocations = function()
			{
				var n = this.cash_allocations.length;
				var validCashAllocations = [];
				for( var i = 0; i < n; i++ )
				{
					if( ( this.cash_allocations[i].allocation_item_status == 10 || this.cash_allocations[i].allocation_item_status == 11 ) &&
							this.cash_allocations[i].allocated_quantity > 0 &&
							!this.cash_allocations[i].markedVoid )
					{
						validCashAllocations.push( this.cash_allocations[i] );
					}
				}

				return validCashAllocations;
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


		Allocation.prototype.getValidCashRemittances = function()
			{
				var n = this.cash_remittances.length;
				var validCashRemittances = [];
				for( var i = 0; i < n; i++ )
				{
					if( ( this.cash_remittances[i].allocation_item_status == 20 || this.cash_remittances[i].allocation_item_status == 21 ) &&
							this.cash_remittances[i].allocated_quantity > 0 &&
							!this.cash_remittances[i].markedVoid )
					{
						validCashRemittances.push( this.cash_remittances[i] );
					}
				}

				return validCashRemittances;
			};


		Allocation.prototype.getValidTicketSales = function()
			{
				var n = this.ticket_sales.length;
				var validTicketSales = [];
				for( var i = 0; i < n; i++ )
				{
					if( ( this.ticket_sales[i].allocation_item_status == 30 || this.ticket_sales[i].allocation_item_status == 31 ) &&
							this.ticket_sales[i].allocated_quantity > 0 &&
							!this.ticket_sales[i].markedVoid )
					{
						validTicketSales.push( this.ticket_sales[i] );
					}
				}

				return validTicketSales;
			};


		Allocation.prototype.getValidSales = function()
			{
				var n = this.sales.length;
				var validSales = [];
				for( var i = 0; i < n; i++ )
				{
					if( ( this.sales[i].alsale_sales_item_status == 10 || this.sales[i].alsale_sales_item_status == 11 ) &&
							this.sales[i].alsale_amount > 0 &&
							!this.sales[i].markedVoid )
					{
						validSales.push( this.sales[i] );
					}
				}

				return validSales;
			};


		Allocation.prototype.addAllocationItem = function( item )
			{
				switch( item.item_class )
				{
					case 'ticket':
						this.allocations.push( item );
						break;

					case 'cash':
						this.cash_allocations.push( item );
						break;
				}

				this.updateAllocationSummary();
			};


		Allocation.prototype.addRemittanceItem = function( item )
			{
				switch( item.item_class )
				{
					case 'ticket':
						this.remittances.push( item );
						break;

					case 'cash':
						this.cash_remittances.push( item );
						break;
				}

				this.updateAllocationSummary();
			};


		Allocation.prototype.addTicketSaleItem = function( item )
			{
				this.ticket_sales.push( item );
			};


		Allocation.prototype.addSalesItem = function( item )
			{
				this.sales.push( item );
			};


		Allocation.prototype.updateAllocationSummary = function()
			{
				this.allocationSummary = [];
				var tempObj = {};
				var ignoredStatus = [ 12, 13, 22 ]; // ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED, REMITTANCE_ITEM_VOIDED

				var n = this.allocations.length;
				var cn = this.cash_allocations.length;
				var m = this.remittances.length;
				var cm = this.cash_remittances.length;

				switch( this.assignee_type )
				{
					case 1: // Station Teller
						// Ticket allocations
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
										item_class: this.allocations[i].item_class,
										scheduled: 0,
										initial: 0,
										additional: 0,
										remitted: 0
									};
							}

							if( this.allocations[i].allocation_item_status == 10 )
							{
								tempObj[this.allocations[i].allocated_item_id].scheduled += this.allocations[i].allocated_quantity;
							}
							else if( this.allocations[i].category_name == 'Initial Allocation' )
							{
								tempObj[this.allocations[i].allocated_item_id].initial += this.allocations[i].allocated_quantity;
							}
							else if( this.allocations[i].category_name == 'Additional Allocation' )
							{
								tempObj[this.allocations[i].allocated_item_id].additional += this.allocations[i].allocated_quantity;
							}
						}


						// Cash allocations
						for( var i = 0; i < cn; i++ )
						{
							if( ignoredStatus.indexOf( this.cash_allocations[i].allocation_item_status ) != -1 || this.cash_allocations[i].markedVoid )
							{
								continue;
							}

							if( !tempObj[this.cash_allocations[i].allocated_item_id] )
							{
								tempObj[this.cash_allocations[i].allocated_item_id] = {
										item_name: this.cash_allocations[i].item_name,
										item_description: this.cash_allocations[i].item_description,
										item_class: this.cash_allocations[i].item_class,
										scheduled: 0,
										initial: 0,
										additional: 0,
										remitted: 0
									};
							}

							if( this.cash_allocations[i].allocation_item_status == 10 )
							{
								tempObj[this.cash_allocations[i].allocated_item_id].scheduled += this.cash_allocations[i].allocated_quantity;
							}
							else if( this.cash_allocations[i].category_name == 'Initial Change Fund' )
							{
								tempObj[this.cash_allocations[i].allocated_item_id].initial += this.cash_allocations[i].allocated_quantity * this.cash_allocations[i].iprice_unit_price;
							}
							else if( this.cash_allocations[i].category_name == 'Additional Change Fund' )
							{
								tempObj[this.cash_allocations[i].allocated_item_id].additional += this.cash_allocations[i].allocated_quantity;
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
										scheduled: 0,
										initial: 0,
										additional: 0,
										remitted: 0
									};
							}

							tempObj[this.remittances[i].allocated_item_id].remitted += this.remittances[i].allocated_quantity;
						}

						for( var i = 0; i < cm; i ++ )
						{
							if( ignoredStatus.indexOf( this.cash_remittances[i].allocation_item_status ) != -1 )
							{
								continue;
							}

							if( !tempObj[this.cash_remittances[i].allocated_item_id] )
							{
								tempObj[this.cash_remittances[i].allocated_item_id] = {
										item_name: this.cash_remittances[i].item_name,
										item_description: this.cash_remittances[i].item_description,
										scheduled: 0,
										initial: 0,
										additional: 0,
										remitted: 0
									};
							}

							tempObj[this.cash_remittances[i].allocated_item_id].remitted += this.cash_remittances[i].allocated_quantity * this.cash_remittances[i].iprice_unit_price;
						}
						break;

					case 2: //
						// Ticket remittances
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
										scheduled: 0,
										loaded: 0,
										unsold: 0,
										rejected: 0
									};
							}

							if( this.allocations[i].allocation_item_status == 10 )
							{
								tempObj[this.allocations[i].allocated_item_id].scheduled += this.allocations[i].allocated_quantity;
							}
							else
							{
								tempObj[this.allocations[i].allocated_item_id].loaded += this.allocations[i].allocated_quantity;
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
										scheduled: 0,
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
					var index;
					switch( item.item_class )
					{
						case 'ticket':
							index = this.allocations.indexOf( item );
							this.allocations.splice( index, 1 );
							break;

						case 'cash':
							index = this.cash_allocations.indexOf( item );
							this.cash_allocations.splice( index, 1 );
							break;
					}
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
					var index;
					switch( item.item_class )
					{
						case 'ticket':
							index = this.remittances.indexOf( item );
							this.remittances.splice( index, 1 );
							break;

						case 'cash':
							index = this.cash_remittances.indexOf( item );
							this.cash_remittances.splice( index, 1 );
							break;
					}
				}
				else
				{
					item.void( !item.void() );
				}

				this.updateAllocationSummary();
			};


		Allocation.prototype.removeTicketSaleItem = function( item )
			{
				if( item.id == undefined )
				{
					var index = this.ticket_sales.indexOf( item );
					this.ticket_sales.splice( index, 1 );
				}
				else
				{
					item.void( !item.void() );
				}
			};


		Allocation.prototype.removeSalesItem = function( item )
			{
				if( item.id == undefined )
				{
					var index = this.sales.indexOf( item );
					this.sales.splice( index, 1 );
				}
				else
				{
					item.void( !item.void() );
				}
			};

		Allocation.prototype.removeCashReport = function( item )
			{
				var me = this;
				var index = this.cash_reports.indexOf( item );
				item.remove().then(
					function( response )
					{
						me.cash_reports.splice( index, 1 );
					} );
			};


		Allocation.prototype.checkAllocation = function( action )
			{
				var allocationCount = this.allocations.length;
				var cashAllocationCount = this.cash_allocations.length;
				var remittanceCount = this.remittances.length;
				var cashRemittanceCount = this.cash_remittances.length;
				var ticketSalesCount = this.ticket_sales.length;
				var salesCount = this.sales.length;

				var preAllocationCategories = [ 'Initial Allocation', 'Magazine Load', 'Change Fund' ];
				var postAllocationCategories = [ 'Additional Allocation', 'Magazine Load', 'Change Fund' ];

				var hasValidAllocationItem = this.getValidAllocations().length > 0 || this.getValidCashAllocations().length > 0;
				var hasValidRemittanceItem = this.getValidRemittances().length > 0 || this.getValidCashRemittances().length > 0;
				var hasValidTicketSaleItem = this.getValidTicketSales().length > 0;
				var hasValidSalesItem = this.getValidSales().length > 0;

				switch( action )
				{
					case 'schedule':
					case 'allocate':
						if( this.assignee_type == 1 && allocationCount == 0 && cashAllocationCount == 0 ) // ALLOCATION_ASSIGNEE_TELLER
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
						// TODO: Do we need to check if allocated item count - sold item count == remitted item count?
						if( !hasValidAllocationItem && !hasValidRemittanceItem && !hasValidTicketSaleItem )
						{
							notifications.alert( 'Record does not contain any valid allocation, remittance or ticket sale items', 'warning' );
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
						cash_allocations: [],
						remittances: [],
						cash_remittances: [],
						ticket_sales: [],
						sales: [],
					};

				var allocations = this.allocations;
				var remittances = this.remittances;
				var cash_allocations = this.cash_allocations;
				var cash_remittances = this.cash_remittances;
				var ticket_sales = this.ticket_sales;
				var sales = this.sales;
				var m = allocations.length;
				var n = remittances.length;
				var cm = cash_allocations.length;
				var cn = cash_remittances.length;
				var ts = ticket_sales.length;
				var s = sales.length;

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
							allocation_item_status: allocations[i].allocation_item_status,
							allocation_item_type: 1 // ALLOCATION_ITEM_TYPE_ALLOCATION
						};

					// Change item status of voided items
					if( allocations[i].markedVoid )
					{
						allocationData.allocation_item_status = 13 // ALLOCATION_ITEM_VOIDED
					}

					data.allocations.push( allocationData );
				}

				for( var i = 0; i < cm; i++ )
				{
					var allocationData = {
							id: cash_allocations[i].id,
							allocation_id: cash_allocations[i].allocation_id,
							cashier_id: cash_allocations[i].cashier_id,
							allocated_item_id: cash_allocations[i].allocated_item_id,
							allocated_quantity: cash_allocations[i].allocated_quantity,
							allocation_category_id: cash_allocations[i].allocation_category_id,
							allocation_datetime: cash_allocations[i].allocation_datetime ? $filter( 'date' )( cash_allocations[i].allocation_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
							allocation_item_status: cash_allocations[i].allocation_item_status,
							allocation_item_type: 1 // ALLOCATION_ITEM_TYPE_ALLOCATION
						};

					// Change item status of voided items
					if( cash_allocations[i].markedVoid )
					{
						allocationData.allocation_item_status = 13 // ALLOCATION_ITEM_VOIDED
					}

					data.cash_allocations.push( allocationData );
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
							allocation_item_status: remittances[i].allocation_item_status,
							allocation_item_type: 2 // ALLOCATION_ITEM_TYPE_REMITTANCE
						};

					// Change item status of voided items
					if( remittances[i].markedVoid )
					{
						remittanceData.allocation_item_status = 22 // REMITTANCE_ITEM_VOIDED
					}

					data.remittances.push( remittanceData );
				}

				for( var i = 0; i < cn; i++ )
				{
					var remittanceData = {
							id: cash_remittances[i].id,
							allocation_id: cash_remittances[i].allocation_id,
							cashier_id: cash_remittances[i].cashier_id,
							allocated_item_id: cash_remittances[i].allocated_item_id,
							allocated_quantity: cash_remittances[i].allocated_quantity,
							allocation_category_id: cash_remittances[i].allocation_category_id,
							allocation_datetime: cash_remittances[i].allocation_datetime ? $filter( 'date' )( cash_remittances[i].allocation_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
							allocation_item_status: cash_remittances[i].allocation_item_status,
							allocation_item_type: 2 // ALLOCATION_ITEM_TYPE_REMITTANCE
						};

					// Change item status of voided items
					if( cash_remittances[i].markedVoid )
					{
						remittanceData.allocation_item_status = 22 // REMITTANCE_ITEM_VOIDED
					}

					data.cash_remittances.push( remittanceData );
				}

				for( var i = 0; i < ts; i++ )
				{
					var ticketSaleData = {
							id: ticket_sales[i].id,
							allocation_id: ticket_sales[i].allocation_id,
							cashier_id: ticket_sales[i].cashier_id,
							allocated_item_id: ticket_sales[i].allocated_item_id,
							allocated_quantity: ticket_sales[i].allocated_quantity,
							allocation_category_id: ticket_sales[i].allocation_category_id,
							allocation_datetime: ticket_sales[i].allocation_datetime ? $filter( 'date' )( ticket_sales[i].allocation_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
							allocation_item_status: ticket_sales[i].allocation_item_status,
							allocation_item_type: 3 // ALLOCATION_ITEM_TYPE_SALES
						};

					// Change item status of voided items
					if( ticket_sales[i].markedVoid )
					{
						ticketSaleData.allocation_item_status = 32 // TICKET_SALE_ITEM_VOIDED
					}

					data.ticket_sales.push( ticketSaleData );
				}

				for( var i = 0; i < s; i++ )
				{
					var saleData = {
							id: sales[i].id,
							alsale_allocation_id: sales[i].alsale_allocation_id,
							alsale_cashier_id: sales[i].alsale_cashier_id,
							alsale_sales_item_id: sales[i].alsale_sales_item_id,
							alsale_amount: sales[i].alsale_amount,
							alsale_remarks: sales[i].alsale_remarks,
							alsale_sales_item_status: sales[i].alsale_sales_item_status
						};

					// Change item status of voided items
					if( sales[i].markedVoid )
					{
						saleData.alsale_sales_item_status = 12 // TICKET_SALE_ITEM_VOIDED
					}

					data.sales.push( saleData );
				}

				return data;
			};


		Allocation.prototype.save = function( action )
			{
				var me = this;
				var deferred = $q.defer();

				if( this.checkAllocation( action ) )
				{
					var allocationData = this.prepareAllocationData();

					var allocationUrl = baseUrl + 'index.php/api/v1/allocations/';
					switch( action )
					{
						case 'allocate':
							allocationUrl += 'allocate';
							break;

						case 'complete':
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
								me.updateAllocationSummary();
								deferred.resolve( me );
							}
							else
							{
								notifications.showMessages( response.data.errorMsg );
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
		var allocation_item_type;

		var item_class;

		var allocationItemStatus = {
				'10': 'Scheduled',
				'11': 'Allocated',
				'12': 'Cancelled',
				'13': 'Voided',
				'20': 'Pending',
				'21': 'Remitted',
				'22': 'Voided',
				'30': 'Pending',
				'31': 'Recorded',
				'32': 'Voided',
			};


		/**
		 * Constructor
		 */
		function AllocationItem( data, type )
		{
			this.loadData( data, type );
		}


		AllocationItem.prototype.loadData = function( data, type )
			{
				var me = this;

				me.id = null;
				me.allocation_id = null;
				me.cashier_id = null;
				me.allocated_item_id = null;
				me.allocated_quantity = null;
				me.allocation_category_id = null;
				me.allocation_datetime = new Date();

				me.iprice_currency = null;
				me.iprice_unit_price = null;
				me.item_class = null;

				switch( type )
				{
					case 'ticket_sale':
						me.allocation_item_type = 3;
						me.allocation_item_status = 30;
						break;

					case 'remittance':
					case 'cash_remittance':
						me.allocation_item_type = 2;
						me.allocation_item_status = 20;
						break;

					case 'allocation':
					case 'cash_allocation':
					default:
						me.allocation_item_type = 1;
						me.allocation_item_status = 10;
				}

				if( data )
				{
					angular.merge( me, data );

					if( me.allocation_datetime )
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

angular.module( 'coreModels' ).factory( 'AllocationSalesItem', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var alsale_allocation_id;
		var alsale_shift_id
		var alsale_cashier_id;
		var alsale_sales_item_id;
		var alsale_amount;
		var alsale_remarks;
		var alsale_sales_item_status;

		var allocationSalesItemStatus = {
				'10': 'Pending',
				'11': 'Recorded',
				'12': 'Voided',
			};


		/**
		 * Constructor
		 */
		function AllocationSalesItem( data )
		{
			this.loadData( data );
		}

		AllocationSalesItem.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.alsale_allocation_id = null;
				me.alsale_shift_id = null;
				me.alsale_cashier_id = null;
				me.alsale_sales_item_id = null;
				me.alsale_amount = null;
				me.alsale_remarks = null;
				me.alsale_sales_item_status = 10;

				if( data )
				{
					angular.merge( me, data );
				}
			};

		AllocationSalesItem.prototype.get = function( field )
			{
				switch( field )
				{
					case 'allocationSalesItemStatus':
						return allocationSalesItemStatus[this.alsale_sales_item_status.toString()];

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

		AllocationSalesItem.prototype.void = function( status )
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

		return AllocationSalesItem;
	}
]);
