app.controller( 'NotificationController', [ '$scope', '$timeout', 'appData', 'notifications',
	function( $scope, $timeout, appData, notifications )
	{
		$scope.data = {
				messages: []
			};

		$scope.visible = true;
		$scope.alertType = 'success';
		$scope.title = 'Notification';
		$scope.message = 'Hello! Welcome to the Fare Revenue Operations Group (FROG) Inventory Management System!';
		$scope.showNotification = function( event, data )
			{
				var newMessage = {
						message: data.message,
						type: data.type || 'info',
						visible: false
					};

				$scope.data.messages.unshift( newMessage );
				$timeout( function()
					{
						newMessage.visible = true;
						$timeout( function()
							{
								newMessage.visible = false;
								$timeout( function()
								{
									var index = $scope.data.messages.indexOf( newMessage );
									if( index !== -1 )
									{
										$scope.data.messages.splice( index, 1 );
									}
								}, 500 );
							}, ( data.duration ? data.duration : 2300 ) );

					}, 10 );
			};

		notifications.subscribe( $scope, 'notificationSignal',  $scope.showNotification );
	}
]);

app.controller( 'MainController', [ '$rootScope', '$scope', '$state', 'session', 'lookup', 'notifications',
	function( $rootScope, $scope, $state, session, lookup, notifications )
	{
		var allowStoreChange = [ 'main.dashboard', 'main.store' ];

		$scope.canChangeStore = allowStoreChange.indexOf( $state.current.name ) != -1;
		$scope.sessionData = session.data;
		$scope.checkPermissions = session.checkPermissions;
		$scope.changeStore = session.changeStore;
		$scope.changeShift = session.changeShift;
		$scope.lookup = lookup.getX;
		$scope.notify = function( message )
			{
				notifications.alert( 'Hello!', 'error', 200 );
			};

		var clnStateChangeSuccess = $rootScope.$on( '$stateChangeSuccess',
			function( event, toState, toParams, fromState, fromParams )
			{
				$scope.canChangeStore = allowStoreChange.indexOf( $state.current.name ) != -1;
			});

		$scope.$on( '$destroy', clnStateChangeSuccess );
	}
]);

app.controller( 'DashboardController', [ '$scope', '$filter', '$http', '$state', '$stateParams', 'baseUrl', 'session', 'notifications', 'utilities',
	function( $scope, $filter, $http, $state, $stateParams, baseUrl, session, notifications, utilities )
	{
		var itemColors = {
				'L2 SJT': 'red',
				'SVC': 'blue',
				'L2 SJT - Rigid Box': 'green',
				'L2 SJT - Ticket Magazine': 'gold',
				'SVC - Rigid Box': 'indigo',
				'L2 SJT - Defective': 'orange',
				'L2 SJT - Damaged': 'pink',
				'SVC - Defective': 'gray',
				'SVC - Damaged': 'black',
				'Senior SVC': 'brown',
				'PWD SVC': 'lightbrown',
				'L1 SJT': 'cyan',
				'L2 Ticket Coupon': 'magenta',
				'Others': 'teal'
			};

		var testData = function( max, neg )
			{
				var data = [];
				if( ! max ) max = 1000;
				for( var i = 0; i < 7; i++ )
				{
					data.push( Math.floor( ( Math.random() * max ) + 1 ) * ( neg ? -1 : 1 ) );
				}

				return data;
			};

		if( $scope.checkPermissions( 'dashboard', 'history' ) )
		{
			$scope.history = {
					chart: null,
					config: {
							title: { text: 'Inventory History' },
							xAxis: {
									type: 'datetime',
									title: { text: 'time' },
									minorTickInterval: 1000 * 60 * 24, // every hour
									tickInterval: 1000 * 60 * 6 * 24
								},
							yAxis: { title: { text: 'inventory level' } },
							legend: { align: 'center', verticalAlign: 'bottom', borderWidth: 0 },
							series: null
						},
					processData: function( data )
						{
							var me = this;
							var series = [];

							var startTime = parseInt(data.start_time) * 1000;
							var endTime = parseInt(data.end_time) * 1000;
							var seriesData = data.series;
							var currentSeries = me.chart.series;

							var defaultItems = [ 'L2 SJT - Rigid Box', 'L2 SJT - Ticket Magazine', 'SVC - Rigid Box' ];

							for( var i = 0; i < seriesData.length; i++ )
							{
								series.push({
									type: 'line',
									name: seriesData[i].name,
									color: itemColors[seriesData[i].name] || undefined,
									data: [],
									visible: defaultItems.indexOf( seriesData[i].name ) == -1 ?  false : true });
							}

							for( var t = startTime; t <= endTime; t += ( 60 * 1000 ) )
							{
								for( var s = 0; s < seriesData.length; s++ )
								{
									if( seriesData[s].data[t/1000] )
									{
										series[s].data.push( [t, parseInt( seriesData[s].data[t/1000] )] );
										seriesData[s].init_balance = seriesData[s].data[t/1000];
									}
									else
									{
										series[s].data.push( [t, parseInt( seriesData[s].init_balance )]  );
									}
								}
							}

							// Remove old series first
							for( var i = currentSeries.length - 1; i >= 0; i-- )
							{
								currentSeries[i].remove( false );
							}

							// Add new series
							for( var j = 0; j < series.length; j++ )
							{
								me.chart.addSeries( series[j], false );
							}

							me.chart.redraw();
						},
					updateChart: function()
						{
							var me = this;
							$http({
								method: 'GET',
								url: baseUrl + 'index.php/api/v1/stores/' + session.data.currentStore.id +  '/inventory_history'
							}).then(
								function( response )
								{
									me.processData( response.data.data );
								},
								function( reason )
								{
									console.error( 'Something went wrong' );
								});
						}
				};
		}

		if( $scope.checkPermissions( 'dashboard', 'week_movement' ) )
		{
			$scope.week_movement = {
					chart: null,
					config: {
						chart: {
							type: 'column'
						},
						title: { text: 'Average SJT Movement' },
						subtitle: { text: 'Under Development - Random test data only' },
						xAxis: {
							categories: [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ]
						},
						yAxis: {
							title: {
								text: null
							},
							labels: {
							formatter: function () {
									return Math.abs(this.value);
								}
							}
						},
						tooltip: {
							headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
							pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
								'<td style="padding:0"><b>{point.y}</b></td></tr>',
							footerFormat: '</table>',
							shared: true,
							useHTML: true
						},
						plotOptions: {
							series: {
								stacking: 'normal'
							},
							column: {
								borderWidth: 0
							}
						},
						series: [{
							name: 'Remittance',
							data: testData( 500, false ),
							color: 'greenyellow'
						}, {
							name: 'Receipts',
							data: testData( 5000, false ),
							color: 'green'
						}, {
							name: 'Transfer',
							data: testData( 300, true ),
							color: 'orangered'
						}, {
							name: 'Allocation',
							data: testData( 5000, true ),
							color: 'darkred'
						}]
					},
					processData: function( data )
						{
							var me = this;
							me.chart.xAxis[0].setCategories( data.stores, false );

							var currentSeries = me.chart.series;
							var series = data.series;

							var defaultItems = [ 'L2 SJT - Rigid Box', 'L2 SJT - Ticket Magazine', 'SVC - Rigid Box' ];

							// Remove old series first
							for( var i = currentSeries.length - 1; i >= 0; i-- )
							{
								currentSeries[i].remove( false );
							}

							// Add new series
							for( var j = 0; j < series.length; j++ )
							{
								me.chart.addSeries({
										name: series[j].item,
										data: series[j].data,
										color: itemColors[series[j].item] || undefined,
										visible: defaultItems.indexOf( series[j].item ) != -1
									}, false );
							}

							me.chart.redraw()
						},
					updateChart: function()
						{
							var me = this;
							$http({
								method: 'GET',
								url: baseUrl + 'index.php/api/v1/inventory/movement_week'
							}).then(
								function( response )
								{
									//me.processData( response.data.data );
								},
								function( reason )
								{
									console.error( 'Something went wrong' );
								});
						}
				};
		}

		if( $scope.checkPermissions( 'dashboard', 'inventory' ) )
		{
			$scope.inventory = {
					chart: null,
					config: {
						chart: {
							type: 'column'
						},
						title: { text: 'Store Inventory Levels' },
						xAxis: {
							categories: null,
							crosshair: true
						},
						yAxis: {
							title: {
								text: 'Inventory Levels'
							}
						},
						tooltip: {
							headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
							pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
								'<td style="padding:0"><b>{point.y}</b></td></tr>',
							footerFormat: '</table>',
							shared: true,
							useHTML: true
						},
						plotOptions: {
							column: {
								pointPadding: 0.2,
								borderWidth: 0
							}
						},
						series: null
					},
					processData: function( data )
						{
							var me = this;
							me.chart.xAxis[0].setCategories( data.stores, false );

							var currentSeries = me.chart.series;
							var series = data.series;

							var defaultItems = [ 'L2 SJT - Rigid Box', 'L2 SJT - Ticket Magazine', 'SVC - Rigid Box' ];

							// Remove old series first
							for( var i = currentSeries.length - 1; i >= 0; i-- )
							{
								currentSeries[i].remove( false );
							}

							// Add new series
							for( var j = 0; j < series.length; j++ )
							{
								me.chart.addSeries({
										name: series[j].item,
										data: series[j].data,
										color: itemColors[series[j].item] || undefined,
										visible: defaultItems.indexOf( series[j].item ) != -1
									}, false );
							}

							me.chart.redraw()
						},
					updateChart: function()
						{
							var me = this;
							$http({
								method: 'GET',
								url: baseUrl + 'index.php/api/v1/inventory/system'
							}).then(
								function( response )
								{
									me.processData( response.data.data );
								},
								function( reason )
								{
									console.error( 'Something went wrong' );
								});
						}
				};
		}

		if( $scope.checkPermissions( 'dashboard', 'distribution' ) )
		{
			$scope.distribution = {
					chart: null,
					config: {
						chart: { type: 'bar' },
						title: { text: 'Card Distribution' },
						xAxis: {
								categories: null,
								title: { text: 'Group' },
								labels: {
									rotation: -90
								}
							},
						yAxis: {
								min: 0,
								title: { text: 'Percent' }
							},
						legend: { reversed: true },
						plotOptions: {
								series: {
										dataLabels: { enabled: true },
										stacking: 'percent'
									}
							},
						tooltip: {
							headerFormat: '<b>{series.name}</b>: ',
							pointFormat: '{point.y:,.0f} of {point.total:,.0f} ({point.percentage:,.2f}%)'
						},
						series: null
					},
					processData: function( data )
						{
							var me = this;
							me.chart.xAxis[0].setCategories( data.groups, false );

							var currentSeries = me.chart.series;
							var series = data.series;

							for( var i = currentSeries.length - 1; i >= 0; i-- )
							{
								currentSeries[i].remove( false );
							}

							// Add new series
							for( var j = 0; j < series.length; j++ )
							{
								me.chart.addSeries({
										name: series[j].store,
										data: series[j].data,
										dataLabels: {
											inside: true
										}
									}, false );
							}

							me.chart.redraw()
						},
					updateChart: function()
						{
							var me = this;
							$http({
								method: 'GET',
								url: baseUrl + 'index.php/api/v1/inventory/distribution'
							}).then(
								function( response )
								{
									me.processData( response.data.data );
								},
								function( reason )
								{
									console.error( 'Something went wrong ' );
								});
						}
				};
		}

		$scope.updateDashboard = function()
			{
				if( $scope.checkPermissions( 'dashboard', 'history' ) ) $scope.history.updateChart();
				if( $scope.checkPermissions( 'dashboard', 'week_movement' ) ) $scope.week_movement.updateChart();
				if( $scope.checkPermissions( 'dashboard', 'inventory' ) ) $scope.inventory.updateChart();
				if( $scope.checkPermissions( 'dashboard', 'distribution' ) ) $scope.distribution.updateChart();
			};

		// Subscribe to notifications
		notifications.subscribe( $scope, 'onChangeStore',  function( event, data )
			{
				$scope.updateDashboard();
			});

		$scope.updateDashboard();
	}
]);

app.controller( 'FrontController', [ '$scope', '$state', '$stateParams', 'session', 'appData', 'lookup', 'notifications', 'sessionData',
	function( $scope, $state, $stateParams, session, appData, lookup, notifications, sessionData )
	{
		$scope.data = appData.data;
		$scope.filters = angular.copy( appData.filters );
		$scope.tabs = {
				inventory: { index: 0, title: 'Inventory' },
				transactions: { index: 1, title: 'Transactions' },
				transferValidations: { index: 2, title: 'Transfers' },
				transfers: { index: 3, title: 'Outgoing' },
				receipts: { index: 4, title: 'Incoming' },
				adjustments: { index: 5, title: 'Adjustments' },
				collections: { index: 6, title: 'Mopping Collections' },
				allocations: { index: 7, title: 'Allocations' },
				conversions: { index: 8, title: 'Conversions' }
			};

		if( $stateParams.activeTab )
		{
			$scope.activeTab = $scope.tabs[$stateParams.activeTab].index;
		}
		else
		{
			$scope.activeTab = 0;
		}

		$scope.onTabSelect = function( tab )
			{
				session.data.previousTab = tab;
			};

		// ResultsPage
		$scope.pagination = appData.pagination;

		// Filters
		$scope.filterPanels = {
				transactions: false,
				transferValidations: false,
				transfers: false,
				receipts: false,
				allocations: false,
				adjustments: false,
				collections: false,
				conversions: false
			};

		$scope.widgets = {
				transactionsDate: {
					opened: false
				},
				transactionsItems: angular.copy( appData.data.items ),
				transactionsTypes: angular.copy( appData.data.transactionTypes ),

				transferValidationsDateSent: {
					opened: false
				},
				transferValidationsDateReceived: {
					opened: false
				},
				transferValidationsSources: angular.copy( appData.data.stores ),
				transferValidationsDestinations: angular.copy( appData.data.stores ),
				transferValidationsStatus: angular.copy( appData.data.transferValidationStatus ),

				transfersDate: {
					opened: false
				},
				transfersDestinations: angular.copy( appData.data.stores ),
				transfersStatus: angular.copy( appData.data.transferStatus ),

				receiptsDate: {
					opened: false
				},
				receiptsSources: angular.copy( appData.data.stores ),
				receiptsStatus: angular.copy( appData.data.transferStatus ),

				adjustmentsDate: {
					opened: false
				},
				adjustmentsItems: angular.copy( appData.data.items ),
				adjustmentsStatus: angular.copy( appData.data.adjustmentStatus ),
				adjustmentsPagination: {
					currentPage: appData.filters.adjustments.page,
					totalItems: appData.data.totals.adjustments,
					itemsPerPage: appData.filters.adjustments.limit
				},

				collectionsProcessingDate: {
					opened: false
				},
				collectionsBusinessDate: {
					opened: false
				},

				allocationsDate: {
					opened: false
				},
				allocationsAssigneeTypes: angular.copy( appData.data.assigneeTypes ),
				allocationsStatus: angular.copy( appData.data.allocationStatus ),

				conversionsDate: {
					opened: false
				},
				conversionsItems: angular.copy( appData.data.items )
			};

		$scope.widgets.transactionsItems.unshift({ id: null, item_name: 'All', item_description: 'All' });
		$scope.widgets.transactionsTypes.unshift({ id: null, typeName: 'All' });

		$scope.widgets.transferValidationsSources.unshift({ id: null, store_name: 'All' });
		$scope.widgets.transferValidationsSources.push({ id: '_ext_', store_name: 'External Sources' });
		$scope.widgets.transferValidationsDestinations.unshift({ id: null, store_name: 'All' });
		$scope.widgets.transferValidationsDestinations.push({ id: '_ext_', store_name: 'External Destinations' });
		$scope.widgets.transferValidationsStatus.unshift({ id: '_null_', statusName: 'No validation' });
		$scope.widgets.transferValidationsStatus.unshift({ id: null, statusName: 'All' });

		$scope.widgets.transfersDestinations.unshift({ id: null, store_name: 'All' });
		$scope.widgets.transfersDestinations.push({ id: '_ext_', store_name: 'External Destinations' });
		$scope.widgets.transfersStatus.unshift({ id: null, statusName: 'All' });

		$scope.widgets.receiptsSources.unshift({ id: null, store_name: 'All' });
		$scope.widgets.receiptsSources.push({ id: '_ext_', store_name: 'External Sources' });
		$scope.widgets.receiptsStatus.unshift({ id: null, statusName: 'All' });

		$scope.widgets.adjustmentsItems.unshift({ id: null, item_name: 'All', item_description: 'All' });
		$scope.widgets.adjustmentsStatus.unshift({ id: null, statusName: 'All' });

		$scope.widgets.allocationsAssigneeTypes.unshift({ id: null, typeName: 'All' });
		$scope.widgets.allocationsStatus.unshift({ id: null, statusName: 'All' });

		$scope.widgets.conversionsItems.unshift({ id: null, item_name: 'All', item_description: 'All' });

		$scope.toggleFilters = function( tab )
			{
				$scope.filterPanels[tab] = !$scope.filterPanels[tab];
			};

		$scope.applyFilter = function( tab )
			{
				var currentStoreId = session.data.currentStore.id;
				$scope.pagination[tab] = 1;
				$scope.filters[tab].filtered = true;
				angular.copy( $scope.filters[tab], appData.filters[tab] );

				switch( tab )
				{
					case 'transactions':
						$scope.updateTransactions( currentStoreId );
						break;

					case 'transferValidations':
						$scope.updateTransferValidations();
						break;

					case 'transfers':
						$scope.updateTransfers( currentStoreId );
						break;

					case 'receipts':
						$scope.updateReceipts( currentStoreId );
						break;

					case 'adjustments':
						$scope.updateAdjustments( currentStoreId );
						break;

					case 'collections':
						$scope.updateCollections( currentStoreId );
						break;

					case 'allocations':
						$scope.updateAllocations( currentStoreId );
						break;

					case 'conversions':
						$scope.updateConversions( currentStoreId );

					default:
						// none
				}
			};

		$scope.clearFilter = function( tab )
			{
				var currentStoreId = session.data.currentStore.id;
				$scope.pagination[tab] = 1;
				angular.copy( appData.clearFilter( tab ), $scope.filters[tab] );

				$scope.applyFilter( tab );
				$scope.filters[tab].filtered = false;
				appData.filters[tab].filtered = false;
			};

		$scope.showDatePicker = function( dp )
			{
				$scope.widgets[dp].opened = true;
			};

		$scope.showActionList = function( module, record )
			{
				switch( module )
				{
					case 'transferValidations':
						// TRANSFER_VALIDATION_RECEIPT_VALIDATED, TRANSFER_VALIDATION_RECEIPT_RETURNED
						// TRANSFER_VALIDATION_TRANSFER_VALIDATED, TRANSFER_VALIDATION_TRANSFER_DISPUTED
						// TRANSFER_VALIDATION_ONGOING
						if( !record )
						{
							return false;
						}
						return ( ( record.transval_receipt_status == 1 && record.transval_transfer_status == 1 )
							|| record.transval_receipt_status == 2 )
							&& $scope.checkPermissions( 'transferValidations', 'complete' );

					case 'transfers':
						// TRANSFER_PENDING, TRANSFER_APPROVED
						return ( ( record.transfer_status == 1 && ( $scope.checkPermissions( 'transfers', 'edit' ) || $scope.checkPermissions( 'transfers', 'approve' ) ) )
							|| ( record.transfer_status == 2 && $scope.checkPermissions( 'transfers', 'approve' ) ) );

					case 'receipts':
						// TRANSFER_APPROVED
						return record.transfer_status == 2 && $scope.checkPermissions( 'transfers', 'edit' );

					case 'adjustments':
						// ADJUSTMENT_PENDING, ADJUSTMENT_APPROVED
						return ( ( record.adjustment_status == 1 && ( $scope.checkPermissions( 'adjustments', 'edit' ) || $scope.checkPermissions( 'adjustments', 'approve' ) ) ) );

					case 'collections':
						return ( $scope.checkPermissions( 'collections', 'edit' ) );

					case 'allocations':
						// ALLOCATION_SCHEDULED, ALLOCATION_REMITTED, ALLOCATION_CANCELLED
						return ( ( record.allocation_status != 3 && record.allocation_status != 4 && $scope.checkPermissions( 'allocations', 'edit' ) )
							|| ( record.allocation_status == 1 && $scope.checkPermissions( 'allocations', 'edit' ) ) );

					case 'conversions':
						return record.conversion_status == 1 && ( $scope.checkPermissions( 'conversions', 'edit' ) || $scope.checkPermissions( 'conversions', 'approve' ) );

					default:
						return false;
				}
			};

		// Refresh/update functions
		$scope.updateInventory = appData.getInventory;
		$scope.updateTransactions = appData.getTransactions;
		$scope.updateTransferValidations = appData.getTransferValidations;
		$scope.updateTransfers = appData.getTransfers;
		$scope.updateReceipts = appData.getReceipts;
		$scope.updateAdjustments = appData.getAdjustments;
		$scope.updateCollections = appData.getCollections;
		$scope.updateAllocations = appData.getAllocations;
		$scope.updateConversions = appData.getConversions;

		// Transfer Validations
		$scope.completeTransferValidation = function( validation )
			{
				appData.saveTransferValidation( { id: validation.transval_id }, 'complete' ).then(
					function( response )
					{
						notifications.alert( 'Transfer validation completed', 'success' );
						appData.refresh( null, 'transferValidations' );
					});
			};

		$scope.transferValidationOngoing = function( validation )
			{
				appData.saveTransferValidation( { id: validation.transval_id }, 'ongoing' ).then(
					function( response )
					{
						notifications.alert( 'Transfer validation marked as ongoing', 'success' );
						appData.refresh( null, 'transferValidations' );
					});
			};

		$scope.transferValidationNotRequired = function( validation )
			{
				var data = {
						id: validation.transval_id,
						transval_transfer_id: validation.id,
						transval_category: appData.suggestTransferCategory( validation )
					};
				appData.saveTransferValidation( data, 'not_required' ).then(
					function( response )
					{
						notifications.alert( 'Transfer marked as validation not required', 'success' );
						appData.refresh( null, 'transferValidations' );
					});
			};

		// Transfers
		$scope.approveTransfer = function( transfer )
			{
				appData.approveTransfer( transfer ).then(
					function( response )
					{
						notifications.alert( 'Transfer approved', 'success' );
						appData.refresh( session.data.currentStore.id, 'transfers' );
					});
			};

		$scope.receiveTransfer = function( transfer )
			{
				appData.receiveTransfer( transfer ).then(
					function( response )
					{
						notifications.alert( 'Transfer received', 'success' );
						appData.refresh( session.data.currentStore.id, 'receipts' );
					});
			};

		$scope.cancelTransfer = function( transfer )
			{
				appData.cancelTransfer( transfer ).then(
					function( response )
					{
						notifications.alert( 'Transfer cancelled', 'success' );
						appData.refresh( session.data.currentStore.id, 'transfers' )
					});
			};

		// Adjustments
		$scope.approveAdjustment = function( adjustmentData )
			{
				appData.approveAdjustment( adjustmentData ).then(
					function( response )
					{
						notifications.alert( 'Adjustment approved', 'success' );
						appData.refresh( session.data.currentStore.id, 'adjustments' );
					});
			};

		// Allocations
		$scope.allocateAllocation = function( allocationData )
			{
				appData.allocateAllocation( allocationData ).then(
					function( response )
					{
						notifications.alert( 'Record marked as Allocated', 'success' );
						appData.refresh( session.data.currentStore.id, 'allocations' );
					});
			};

		$scope.completeAllocation = function( allocationData )
			{
				appData.completeAllocation( allocationData ).then(
					function( response )
					{
						notifications.alert( 'Record marked as Completed', 'success' );
						appData.refresh( session.data.currentStore.id, 'allocations' );
					});
			};

		// Conversions
		$scope.approveConversion = function( conversionData )
			{
				appData.approveConversion( conversionData ).then(
					function( response )
					{
						notifications.alert( 'Conversion approved', 'success' );
						appData.refresh( session.data.currentStore.id, 'conversions' );
					});
			};

		// Cancel scheduled allocation
		$scope.cancelAllocation = function( allocationData )
			{
				appData.cancelAllocation( allocationData ).then(
					function( response )
					{
						notifications.alert( 'Allocation cancelled', 'success' );
						appData.refresh( session.data.currentStore.id, 'allocations' );
					});
			};

		// Subscribe to notifications
		notifications.subscribe( $scope, 'onChangeStore',  function( event, data )
			{
				appData.refresh( session.data.currentStore.id, data );
			});

		// Init controller
		appData.refresh( session.data.currentStore.id, 'all' );
	}
]);

app.controller( 'TransferValidationController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'UserServices',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, UserServices )
	{
		function getCategoryById( categoryId )
		{
			var categories = $scope.data.transferCategories;
			var n = categories.length;

			for( var i = 0; i < n; i++ )
			{
				if( categories[i].id == categoryId )
				{
					return categories[i];
				}
			}

			return null;
		}

		function suggestTransferCategory()
		{
			var category = appData.suggestTransferCategory( $scope.transferItem );
			$scope.data.selectedCategory = getCategoryById( category );
			return category; // General
		}

		$scope.data = {
				transferCategories: angular.copy( appData.data.transferCategories ),
				selectedCategory: { id: 2, categoryName: 'Return Loose' }
			};

		$scope.input = {};

		$scope.transferItem = {};

		$scope.findUser = UserServices.findUser;

		$scope.changeTransferCategory = function()
			{
				$scope.transferItem.validation.transval_category = $scope.data.selectedCategory.id;
			};

		$scope.validateReceipt = function()
			{
				var validation = $scope.transferItem.validation;
				appData.saveTransferValidation( validation, 'validate_receipt' ).then(
					function( response )
					{
						appData.refresh( null, 'transferValidations' );
						notifications.alert( 'Receipt of items from source validated', 'success' );
						$state.go( 'main.store', { activeTab: 'transferValidations' } );
					},
					function( reason )
					{
						notifications.alert( reason );
					});
			};

		$scope.markReturned = function()
			{
				var validation = $scope.transferItem.validation;
				appData.saveTransferValidation( validation, 'returned' ).then(
					function( response )
					{
						appData.refresh( null, 'transferValidations' );
						notifications.alert( 'Transfer marked as returned', 'success' );
						$state.go( 'main.store', { activeTab: 'transferValidations' } );
					},
					function( reason )
					{
						notifications.alert( reason );
					});
			};

		$scope.validateTransfer = function()
			{
				var validation = $scope.transferItem.validation;
				appData.saveTransferValidation( validation, 'validate_transfer' ).then(
					function( response )
					{
						appData.refresh( null, 'transferValidations' );
						notifications.alert( 'Receipt of items by recipient validated', 'success' );
						$state.go( 'main.store', { activeTab: 'transferValidations' } );
					},
					function( reason )
					{
						notifications.alert( reason );
					});
			};

		$scope.markDisputed = function()
			{
				var validation = $scope.transferItem.validation;
				appData.saveTransferValidation( validation, 'dispute' ).then(
					function( response )
					{
						appData.refresh( null, 'transferValidations' );
						notifications.alert( 'Receipt of items by recipient disputed', 'success' );
						$state.go( 'main.store', { activeTab: 'transferValidations' } );
					},
					function( reason )
					{
						notifications.alert( reason );
					});
			};

		$scope.markCompleted = function()
			{
				var validation = $scope.transferItem.validation || null;
				appData.saveTransferValidation( validation, 'complete' ).then(
					function( response )
					{
						appData.refresh( null, 'transferValidations' );
						notifications.alert( 'Transfer validation completed', 'success' );
						$state.go( 'main.store', { activeTab: 'transferValidations' } );
					},
					function( reason )
					{
						notifications.alert( reason );
					});
			};

		$scope.markOngoing = function()
			{
				var validation = $scope.transferItem.validation || null;
				appData.saveTransferValidation( validation, 'ongoing' ).then(
					function( response )
					{
						appData.refresh( null, 'transferValidations' );
						notifications.alert( 'Transfer validation marked as ongoing', 'success' );
						$state.go( 'main.store', { activeTab: 'transferValidations' } );
					},
					function( reason )
					{
						notifications.alert( reason );
					});
			};

		$scope.markNotRequired = function()
			{
				var validation = $scope.transferItem.validation || null;
				appData.saveTransferValidation( validation, 'not_required' ).then(
					function( response )
					{
						appData.refresh( null, 'transferValidations' );
						notifications.alert( 'Transfer marked as validation not required', 'success' );
						$state.go( 'main.store', { activeTab: 'transferValidations' } );
					},
					function( reason )
					{
						notifications.alert( reason );
					});
			};

		if( $stateParams.transferItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getTransfer( $stateParams.transferItem.id, [ 'validation' ] ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.transferItem = response.data;
						if( $scope.transferItem.validation == null )
						{
							$scope.transferItem.validation = {};
							$scope.transferItem.validation.transval_transfer_id = $scope.transferItem.id;
							$scope.transferItem.validation.transval_receipt_sweeper = session.data.currentUser.full_name;
							if( ! $scope.transferItem.validation.transval_transfer_sweeper )
							{
								$scope.transferItem.validation.transval_transfer_sweeper = session.data.currentUser.full_name;
							}
							$scope.transferItem.validation.transval_category = suggestTransferCategory();
						}

						$scope.data.selectedCategory = $filter( 'filter' )( $scope.data.transferCategories, { id: $scope.transferItem.validation.transval_category }, true )[0];
					}
				},
				function( reason )
				{
					notifications.alert( reason );
				});
		}
	}
]);

app.controller( 'TransferController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'UserServices',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, UserServices )
	{
		var users = [];

		$scope.data = {
			mode: 'transfer', // transfer | receipt
			editMode: $stateParams.editMode || 'transfer', // view, externalReceipt, externalTransfer, receipt, transfer
			title: 'New Transfer',
			sources: [],
			destinations: [],
			selectedSource: null,
			selectedDestination: null,
			isExternalSource: false,
			isExternalDestination: false,
			inventoryItems: angular.copy( appData.data.items ),
			itemCategories: [],
			sweepers: [],
			transferDatepicker: { format: 'yyyy-MM-dd', opened: false },
			receiptDatepicker: { format: 'yyyy-MM-dd HH:mm:ss', opened: false },
			showCategory: ( session.data.currentStore.store_type == 4 ),
			showAllocationItemEntry: ( session.data.currentStore.store_type == 4 && $scope.checkPermissions( 'allocations', 'view' ) )
		};

		$scope.input = {
				inventoryItem: null,
				itemReservedQuantity: 0,
				itemCategory: null,
				quantity: 0,
				remarks: null,
				allocation: null
			};

		$scope.transferItem = {
				id: null,
				origin_id: [ 'transfer', 'externalTransfer' ].indexOf( $scope.data.editMode ) != -1 ? session.data.currentStore.id : null,
				origin_name: [ 'transfer', 'externalTransfer' ].indexOf( $scope.data.editMode ) != -1 ? session.data.currentStore.store_name : null,
				sender_id: [ 'transfer', 'externalTransfer' ].indexOf( $scope.data.editMode ) != -1 ? session.data.currentUser.id : null,
				sender_name: null,
				transfer_datetime: new Date(),
				destination_id: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? session.data.currentStore.id : null,
				destination_name: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? session.data.currentStore.store_name : null,
				recipient_id: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? session.data.currentUser.id : null,
				recipient_name: [ 'receipt', 'externalReceipt' ].indexOf( $scope.data.editMode ) != -1 ? session.data.currentUser.full_name : null,
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
					$scope.getItemQuantities();
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
						$scope.data.mode = 'transfer';
						$scope.data.title = 'Transfer';
						$scope.isExternalSource = false;
						$scope.isExternalDestination = false;
						$scope.data.sources = [ $scope.currentStore ];
						if( $scope.transferItem.origin_id )
						{
							$scope.data.selectedSource = $filter( 'filter' )( appData.data.stores, { id: $scope.transferItem.origin_id }, true )[0];
						}
						else
						{
							$scope.data.selectedSource = session.data.currentStore;
						}

						$scope.data.destinations = $filter( 'filter' )( appData.data.stores, { id: '!' + session.data.currentStore.id }, function(a, e) { return angular.equals( parseInt(a), parseInt(e) ) } );
						if( $scope.transferItem.destination_id )
						{
							$scope.data.selectedDestination = $filter( 'filter' )( appData.data.stores, { id: $scope.transferItem.destination_id }, true )[0];
						}
						else if( $scope.transferItem.destination_name )
						{ // External transfer
							$scope.isExternalDestination = true;
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
						$scope.data.mode = 'receipt';
						$scope.data.title = 'Receipt';
						$scope.isExternalSource = false;
						$scope.isExternalDestination = false;
						$scope.data.sources = $filter( 'filter' )( appData.data.stores, { id: '!' + session.data.currentStore.id }, function(a, e) { return angular.equals( parseInt( a ), parseInt( e ) ) } );
						if( $scope.transferItem.origin_id )
						{
							$scope.data.selectedSource = $filter( 'filter' )( appData.data.stores, { id: $scope.transferItem.origin_id }, true )[0];
						}
						else if( $scope.data.sources.length )
						{
							$scope.data.selectedSource = $scope.data.sources[0];
						}
						else
						{
							console.error( 'Unable to load source stores' );
						}

						$scope.data.destinations = [ session.data.currentStore ];
						if( $scope.transferItem.destination_id )
						{
							$scope.data.selectedDestination = $filter( 'filter' )( appData.data.stores, { id: $scope.transferItem.destination_id }, true )[0];
						}
						else
						{
							$scope.data.selectedDestination = session.data.currentStore;
						}
						break;

					case 'externalTransfer':
						$scope.data.mode = 'transfer';
						$scope.data.title = 'External Transfer';
						$scope.isExternalSource = false;
						$scope.isExternalDestination = true;
						$scope.data.sources = [ session.data.currentStore ];
						if( $scope.transferItem.origin_id )
						{
							$scope.data.selectedSource = $filter( 'filter' )( appData.data.stores, { id: $scope.transferItem.origin_id }, true )[0];
						}
						else
						{
							$scope.data.selectedSource = session.data.currentStore;
						}

						$scope.data.destinations = [];
						break;

					case 'externalReceipt':
						$scope.data.mode = 'receipt';
						$scope.data.title = 'External Receipt';
						$scope.isExternalSource = true;
						$scope.isExternalDestination = false;
						$scope.data.sources = [];

						$scope.data.destinations = [ session.data.currentStore ];
						if( $scope.transferItem.destination_id )
						{
							$scope.data.selectedDestination = $filter( 'filter' )( appData.data.stores, { id: $scope.transferItem.destination_id }, true )[0];
						}
						else
						{
							$scope.data.selectedDestination = session.data.currentStore;
						}
						break;

					case 'view':
						if( $scope.transferItem.origin_id == session.data.currentStore.id )
						{
							$scope.data.mode = 'transfer';
							if( ! $scope.transferItem.destination_id )
							{
								$scope.data.title = 'External Transfer';
							}
							else
							{
								$scope.data.title = 'Transfer';
							}
						}
						else if( $scope.transferItem.destination_id == session.data.currentStore.id )
						{
							$scope.data.mode = 'receipt';
							if( ! $scope.transferItem.origin_id )
							{
								$scope.data.title = 'External Receipt';
							}
							else
							{
								$scope.data.title = 'Receipt';
							}
						}

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

		$scope.checkItems = function( action )
			{
				var transferItems = $scope.transferItem.items
				var transferItemCount = transferItems.length;

				// In case of transfer approval check if delivery person is specified
				if( action == 'approve' && ! $scope.transferItem.sender_name )
				{
					notifications.alert( 'Please enter name of delivery person', 'warning' );
					return false;
				}

				// In case of external receipt check if source is specified
				if( $scope.data.editMode == 'externalReceipt' && ! $scope.transferItem.origin_name )
				{
					notifications.alert( 'Please specify source name', 'warning' );
					return false;
				}

				// Check if transfer has items
				if( transferItemCount == 0 )
				{
					notifications.alert( 'Transfer does not contain any items', 'warning' );
					return false;
				}

				// Check if transfer has valid items
				var hasValidTransferItem = false;
				var validItemStatus = [ 1, 2, 3 ] // TRANSFER_ITEM_SCHEDULED, TRANSFER_ITEM_APPROVED, TRANSFER_ITEM_RECEIVED
				for( var i = 0; i < transferItemCount; i++ )
				{
					if( validItemStatus.indexOf( transferItems[i].transfer_item_status ) != -1 && transferItems[i].quantity > 0 && !transferItems[i].transferItemVoid )
					{
						hasValidTransferItem = true;
						break;
					}
				}
				if( hasValidTransferItem == false )
				{
					notifications.alert( 'Transfer does not contain any valid items', 'warning' );
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

				if( typeof data.sender_name === 'object' && data.sender_name )
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
				if( $scope.checkItems( 'schedule' ) )
				{
					// Prepare transfer
					var data = $scope.prepareTransfer();

					appData.saveTransfer( data ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'transfers' );
							notifications.alert( 'Transfer record saved', 'success' );
							$state.go( 'main.store', { activeTab: 'transfers' } );
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};

		$scope.approveTransfer = function()
			{
				if( $scope.checkItems( 'approve' ) )
				{
					var data = $scope.prepareTransfer();

					appData.approveTransfer( data ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'transfers' );
							notifications.alert( 'Transfer approved', 'success' );
							$state.go( 'main.store', { activeTab: 'transfers' } );
						},
						function( reason )
						{
							console.error( reason );
						});
				}

			};

		$scope.receiveTransfer = function()
			{
				if( $scope.checkItems( 'receive' ) )
				{
					var data = $scope.prepareTransfer();

					appData.receiveTransfer( data ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'receipts' );
							notifications.alert( 'Transfer received', 'success' );
							$state.go( 'main.store', { activeTab: 'receipts' } );
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};

		$scope.findUser = UserServices.findUser;
		$scope.getItemQuantities = function()
			{
				var items = $scope.transferItem.items;
				var n = items.length;
				$scope.input.itemReservedQuantity = 0;
				for( var i = 0; i < n; i++ )
				{
					if( items[i].item_id == $scope.input.inventoryItem.item_id
						&& items[i].transfer_item_status == 1 ) // TRANSFER_ITEM_SCHEDULED
					{
						$scope.input.itemReservedQuantity += items[i].quantity;
					}
				}
			};

		$scope.addAllocationItems = function()
			{
				if( ( event.type == 'keypress' ) && ( event.charCode == 13 ) && $scope.input.allocation )
				{
					appData.getAllocation( $scope.input.allocation ).then(
						function( response )
						{
							var allocationItem = response.data;
							var remittances = allocationItem.remittances;

							if( allocationItem.allocation_status == 3 ) // ALLOCATION_REMITTED
							{
								for( var i = 0; i < remittances.length; i++ )
								{
									var data = {
										item_name: remittances[i].item_name,
										category_name: remittances[i].category_name,

										item_id: remittances[i].allocated_item_id,
										item_category_id: remittances[i].allocation_category_id,
										quantity: remittances[i].allocated_quantity,
										remarks: 'From allocation #' + allocationItem.id + ' - ' + ( allocationItem.assignee_type == 2 ? 'TVM #' : '' ) + allocationItem.assignee,
										transfer_item_status: 1 // TRANSFER_ITEM_SCHEDULED
									};

									$scope.transferItem.items.push( data );
								}
								$scope.getItemQuantities();
							}
							else
							{
								notifications.alert( 'Allocation not yet marked as completed', 'warning' );
							}
						},
						function( reason )
						{
							notifications.alert( reason, 'warning' );
						}
					)
					$scope.input.allocation = null;
				}
			}

		// Initialize controller
		$scope.input.inventoryItem = $scope.data.inventoryItems[0];
		$scope.data.itemCategories = $filter( 'filter' )( appData.data.itemCategories, { is_transfer_category: true }, true );
		$scope.data.itemCategories.unshift( { id: null, category: '- None -' });
		$scope.input.itemCategory = $scope.data.itemCategories[0];

		if( $stateParams.transferItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getTransfer( $stateParams.transferItem.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.transferItem = response.data;

						if( $stateParams.editMode != 'view' )
						{
							if( ! $scope.transferItem.origin_id && $scope.transferItem.origin_name )
							{
								$scope.data.editMode = 'externalReceipt';
							}
							else if( ! $scope.transferItem.destination_id && $scope.transferItem.destination_name )
							{
								$scope.data.editMode = 'externalTransfer';
							}
							else if( $scope.transferItem.origin_id == session.data.currentStore.id )
							{
								$scope.data.editMode = 'transfer';
							}
							else if( $scope.transferItem.destination_id == session.data.currentStore.id )
							{
								$scope.data.editMode = 'receipt';
							}
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
							$scope.data.selectedSource = $filter( 'filter')( appData.data.stores, { id: $scope.transferItem.origin_id }, true )[0];
						}
						else
						{
							$scope.data.selectedSource = null;
							$scope.data.isExternalSource = true;
						}

						if( $scope.transferItem.destination_id )
						{
							$scope.data.selectedDestination = $filter( 'filter')( appData.data.stores, { id: $scope.transferItem.destination_id }, true )[0];
						}
						else
						{
							$scope.data.selectedDestination = null;
							$scope.data.isExternalDestination = true;
						}

						if( ! $scope.transferItem.recipient_name && $scope.data.editMode == 'receipt' )
						{
							$scope.transferItem.recipient_name = session.data.currentUser.full_name;
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
					notifications.alert( reason, 'error' );
				});

		};

		$scope.changeEditMode();
	}
]);

app.controller( 'AdjustmentController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'transactionTypes',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, transactionTypes )
	{
		$scope.data = {
				editMode: $stateParams.editMode || 'view',
				inventoryItems: angular.copy( appData.data.items ),
				selectedItem: appData.data.items[0],
				transactionTypes: angular.copy( transactionTypes )
			};

		$scope.data.transactionTypes.unshift( { id: null, typeName: 'None' }  );
		$scope.data.selectedTransactionType = $scope.data.transactionTypes[0];

		$scope.adjustmentItem = {
				id: null,
				store_inventory_id: $scope.data.selectedItem.id,
				adjusted_quantity: null,
				reason: null ,
				adjustment_status: 1, // ADJUSTMENT_PENDING
				adj_transaction_type: null,
				adj_transaction_id: null
			};

		$scope.changeItem = function()
			{
				$scope.adjustmentItem.store_inventory_id = $scope.data.selectedItem.id;
			};

		$scope.changeTransactionType = function()
			{
				$scope.adjustmentItem.adj_transaction_type = $scope.data.selectedTransactionType.id;
			};

		$scope.checkAdjustmentItem = function()
			{
				if( ! $scope.adjustmentItem.reason )
				{
					notifications.alert( 'Please specify reason for adjustment', 'warning' );
					return false;
				}

				return true;
			};

		$scope.prepareAdjustment = function()
			{
				var data = angular.copy( $scope.adjustmentItem );

				return data;
			};

		$scope.saveAdjustment = function()
			{
				if( $scope.checkAdjustmentItem() )
				{
					var data = $scope.prepareAdjustment();

					appData.saveAdjustment( data ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'adjustments' );
							notifications.alert( 'Adjustment record saved', 'success' );
							$state.go( 'main.store', { activeTab: 'adjustments' } );
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};

		$scope.approveAdjustment = function()
			{
				if( $scope.checkAdjustmentItem() )
				{
					var data = $scope.prepareAdjustment();

					appData.approveAdjustment( data ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'adjustments' );
							notifications.alert( 'Adjustment approved', 'success' );
							$state.go( 'main.store', { activeTab: 'adjustments' } );
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};

		// Initialize controller
		if( $stateParams.adjustmentItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getAdjustment( $stateParams.adjustmentItem.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.adjustmentItem = response.data;
						$stateParams.adjustmentItem.previous_quantity = parseInt( $stateParams.adjustmentItem.previous_quantity );
						$stateParams.adjustmentItem.adjusted_quantity = parseInt( $stateParams.adjustmentItem.adjusted_quantity );
						$scope.data.selectedItem = $filter( 'filter' )( appData.data.items, { id: $stateParams.adjustmentItem.store_inventory_id }, true )[0];

						$scope.data.selectedTransactionType = $filter( 'filter' )( $scope.data.transactionTypes, { id: $stateParams.adjustmentItem.adj_transaction_type }, true )[0];
					}
				},
				function( reason )
				{
					console.error( reason );
				});
		}
	}
]);

app.controller( 'ConversionController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'conversionTable',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, conversionTable )
	{
		function outputItemFilter( value, index, array )
		{
			return convertibleItems.indexOf( value.item_id ) !== -1;
		}

		var items = angular.copy( appData.data.items );
		var convertibleItems = [];

		$scope.data = {
				editMode: $stateParams.editMode || 'view',
				conversionDatepicker: { format: 'yyyy-MM-dd HH:mm:ss', opened: false },
				sourceItems: items,
				targetItems: items,
				sourceInventory: items[0],
				targetInventory: items[1],
				input: { min: 1, step: 1 },
				output: { min: 1, step: 1 },
				messages: [],
				factor: null,
				mode: null,
				valid_conversion: false,
				conversionFactors: []
			};

		$scope.conversionItem = {
				store_id: session.data.currentStore.id,
				conversion_datetime: new Date(),
				source_inventory_id: $scope.data.sourceInventory.id || null,
				target_inventory_id: $scope.data.targetInventory.id || null,
				source_quantity: 1,
				target_quantity: null,
				remarks: null,
				conversion_status: 1 // CONVERSION_PENDING
			};

		$scope.showDatePicker = function()
			{
				$scope.data.conversionDatepicker.opened = true;
			};

		$scope.checkConversion = function()
			{
				$scope.data.valid_conversion = true;
				$scope.data.messages = [];

				if( ! $scope.data.targetInventory )
				{
					$scope.valid_conversion = false;
					$scope.data.messages.push( 'Item cannot be converted' );
				}

				if( $scope.conversionItem.source_quantity === 0 || $scope.conversionItem.target_quantity === 0 )
				{
					$scope.valid_conversion = false;
					$scope.data.messages.push( 'Input quantity and output quantity cannot be 0.' );
				}

				if( $scope.conversionItem.source_quantity && $scope.conversionItem.source_quantity % 1 !== 0 )
				{
					$scope.data.valid_conversion = false;
					$scope.data.messages.push( 'Input quantity and output quantity cannot be non-integer values.' );
				}

				if( $scope.conversionItem.source_quantity > $scope.data.sourceInventory.quantity )
				{
					$scope.data.valid_conversion = false;
					$scope.data.messages.push( 'Insufficient inventory for input item to convert.' );
				}

				if( $scope.conversionItem.target_quantity % 1 !== 0 )
				{
					$scope.data.valid_conversion = false;
					$scope.data.messages.push( 'Conversion requires input quantity to be in multiples of ' + $scope.data.input.min );
				}

				if( ! $scope.data.factor )
				{
					$scope.data.valid_conversion = false;
					$scope.data.messages.push( 'Cannot convert input item to output item.' );
				}

				return $scope.data.valid_conversion;
			};



		$scope.onOutputItemChange = function()
			{
				var cf = [];
				$scope.data.mode = null;
				if( $scope.data.targetInventory )
				{
					$scope.conversionItem.target_inventory_id = $scope.data.targetInventory.id;
					cf = $filter( 'filter' )( conversionTable.data, { source_item_id: $scope.data.sourceInventory.item_id, target_item_id: $scope.data.targetInventory.item_id }, true );
					if( cf.length )
					{
						$scope.data.factor = cf[0].conversion_factor;
						$scope.data.mode = ( $scope.data.factor == 1 ? 'convert' : 'pack' );
						$scope.data.input.step = $scope.data.factor;
						$scope.data.input.min = $scope.data.factor;

						if( $scope.conversionItem.source_quantity < $scope.data.input.min )
						{
							$scope.conversionItem.source_quantity = $scope.data.input.min;
						}
					}
					else
					{
						cf = $filter( 'filter' )( conversionTable.data, { target_item_id: $scope.data.sourceInventory.item_id, source_item_id: $scope.data.targetInventory.item_id }, true );
						if( cf.length )
						{
							$scope.data.factor = cf[0].conversion_factor;
							$scope.data.mode = ( $scope.data.factor == 1 ? 'convert' : 'unpack' );
							$scope.data.input.step = 1;
							$scope.data.input.min = 1;
						}
					}
				}
				else
				{
					$scope.data.valid_conversion = false;
					$scope.conversionItem.target_quantity = null;
					$scope.data.input.step = 1;
					$scope.data.input.min = 1;
				}

				$scope.calculateOutput();
			};

		$scope.onInputItemChange = function()
			{
				var cfData = conversionTable.data;
				$scope.conversionItem.source_inventory_id = $scope.data.sourceInventory.id;

				// Get list of items where source item is convertible to
				convertibleItems = [];
				for( var i = 0; i < cfData.length; i++ )
				{
					if( cfData[i].source_item_id == $scope.data.sourceInventory.item_id
							&& convertibleItems.indexOf( cfData[i].target_item_id ) == -1 )
					{
						convertibleItems.push( cfData[i].target_item_id );
					}
					else if( cfData[i].target_item_id == $scope.data.sourceInventory.item_id
							&& convertibleItems.indexOf( cfData[i].source_item_id ) == -1)
					{
						convertibleItems.push( cfData[i].source_item_id );
					}
				}

				$scope.data.targetItems = $filter( 'filter' )( items, outputItemFilter, true );

				if( $scope.data.targetItems.length )
				{
					$scope.data.targetInventory = $scope.data.targetItems[0];
					$scope.conversionItem.target_inventory_id = $scope.data.targetInventory.id;
				}
				else
				{
					$scope.data.targetInventory = null;
					$scope.conversionItem.target_inventory_id = null;
				}

				$scope.onOutputItemChange();
			};

		$scope.calculateOutput = function()
			{
				var factor = $scope.data.factor;

				if( $scope.data.mode == 'pack' )
				{
					$scope.conversionItem.target_quantity = $scope.conversionItem.source_quantity / factor;
				}
				else if( $scope.data.mode == 'unpack' )
				{
					$scope.conversionItem.target_quantity = $scope.conversionItem.source_quantity * factor;
				}
				else if( $scope.data.mode == 'convert' )
				{
					$scope.conversionItem.target_quantity = $scope.conversionItem.source_quantity;
				}

				$scope.checkConversion();
			};

		$scope.saveConversion = function()
			{
				appData.saveConversion( $scope.conversionItem ).then(
					function( response )
					{
						appData.refresh( session.data.currentStore.id, 'conversions' );
						notifications.alert( 'Conversion record saved', 'success' );
						$state.go( 'main.store', { activeTab: 'conversions' } );
					},
					function( reason )
					{
						console.error( reason );
					});
			}

		$scope.approveConversion = function()
			{
				appData.approveConversion( $scope.conversionItem ).then(
					function( response )
					{
						appData.refresh( session.data.currentStore.id, 'conversions' );
						notifications.alert( 'Item converted successfully', 'success' );
						$state.go( 'main.store', { activeTab: 'conversions' } );
					},
					function( reason )
					{
						console.error( reason );
					});
			};

		// Initialize
		if( $stateParams.conversionItem )
		{
			appData.getConversion( $stateParams.conversionItem.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.conversionItem = response.data;
						$scope.conversionItem.conversion_datetime = Date.parse( $stateParams.conversionItem.conversion_datetime );
						$scope.conversionItem.source_quantity = parseInt( $stateParams.conversionItem.source_quantity );
						$scope.conversionItem.target_quantity = parseInt( $stateParams.conversionItem.target_quantity );
						$scope.data.sourceInventory = $filter( 'filter' )( appData.data.items, { id: $stateParams.conversionItem.source_inventory_id }, true )[0];
						$scope.data.targetInventory = $filter( 'filter' )( appData.data.items, { id: $stateParams.conversionItem.target_inventory_id }, true )[0];

						$scope.onInputItemChange();
					}
				}
			)
		}
		else
		{
			$scope.onInputItemChange();
		}

	}
]);

app.controller( 'MoppingController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'packingData', 'UserServices',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, packingData, UserServices )
	{
		$scope.data = {
				processingDatepicker: { format: 'yyyy-MM-dd HH:mm:ss', opened: false },
				businessDatepicker: { format: 'yyyy-MM-dd', opened: false },
				pullOutShifts: [
					{
						id: 4, // Check with installer when changing this value
						shift_num: 'TGM S1',
						description: 'TGM Shift 1'
					},
					{
						id: 5, // Check with installer when changing this value
						shift_num: 'TGM S2',
						description: 'TGM Shift 2'
					},
					{
						id: 8, // Check with installer when changing this value
						shift_num: 'Cashier S3',
						description: 'Cashier Shift 3'
					},
				],
				selectedPullOutShift: {
						id: 4, // Check with installer when changing this value
						shift_num: 'TGM S1',
						description: 'TGM Shift 1'
					},
				moppedSource: angular.copy( appData.data.stations ),
				moppedItems: angular.copy( appData.data.items ),
				packAsItems: packingData,
				editMode: $stateParams.editMode || 'new'
			};

		// Add Inventory from source of mopped item
		$scope.data.moppedSource.push({
				id: 0,
				station_name: 'Inventory',
				station_short_name: 'INV'
			});

		$scope.moppingItem = {
				store_id: session.data.currentStore.id,
				processing_datetime: new Date(),
				business_date: new Date(),
				shift_id: session.data.currentShift.id,
				cashier_shift_id: null,
				items: []
			};

		$scope.input = {
				rowId: null,
				moppedSource: $scope.data.moppedSource[0] || null,
				moppedItem: $scope.data.moppedItems[0],
				moppedQuantity: 0,
				packAs: null,
				processor: null,
				deliveryPerson: null
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

		$scope.onChangePullOutShift = function()
			{
				$scope.moppingItem.cashier_shift_id = $scope.data.selectedPullOutShift.id;
			};

		$scope.addMoppingItem = function( event )
			{
				if( ( event.type == 'keypress' ) && ( event.charCode == 13 ) )
				{
					if( ! $scope.input.deliveryPerson )
					{
						notifications.alert( 'Please specify delivery person', 'warning' );
					}
					else if( ! $scope.input.processor )
					{
						notifications.alert( 'Please specify collection processor', 'warning' );
					}
					else if( $scope.input.moppedQuantity <= 0 )
					{
						notifications.alert( 'Quantity must have a value greater than 0', 'warning' );
					}
					else if( $scope.input.moppedSource
						&& $scope.input.moppedItem && typeof $scope.input.moppedItem === 'object'
						&& ( ! $scope.input.packAs || ( $scope.input.packAs != null && typeof $scope.input.packAs === 'object' ) ) )
					{
						var deliveryPerson;
						if( typeof $scope.input.deliveryPerson == 'string' )
						{
							deliveryPerson = $scope.input.deliveryPerson;
						}
						else if( typeof $scope.input.deliveryPerson == 'object' && $scope.input.deliveryPerson.full_name )
						{
							deliveryPerson = $scope.input.deliveryPerson.full_name;
						}
						else
						{
							notifications.alert( 'Invalid value for delivery person', 'error' );
							return false;
						}

						var data = {
								mopped_station_name: $scope.input.moppedSource.station_name,
								mopped_item_name: $scope.input.moppedItem.item_name,
								convert_to_name: ( $scope.input.packAs && $scope.input.packAs.id ) ? $scope.input.packAs.item_name : null,
								processor_name: $scope.input.processor ? $scope.input.processor.full_name : null,
								valid: true,

								mopped_station_id: parseInt( $scope.input.moppedSource.id ),
								mopped_item_id: parseInt( $scope.input.moppedItem.item_id ),
								mopped_quantity: parseInt( $scope.input.moppedQuantity ),
								converted_to: ( $scope.input.packAs && $scope.input.packAs.id ) ? $scope.input.packAs.target_item_id : null,
								group_id: null,
								processor_id: $scope.input.processor.id,
								delivery_person: deliveryPerson,
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
				}
			};

		$scope.removeMoppingItem = function( itemRow )
			{
				var index = $scope.moppingItem.items.indexOf( itemRow );
				$scope.moppingItem.items.splice( index, 1 );
				$scope.checkItems();
			};

		$scope.findUser = UserServices.findUser;

		$scope.onItemChange = function()
			{
				var item = $scope.input.moppedItem;
				var packItem = $scope.input.packAs;

				if( item && typeof item === 'object' )
				{
					$scope.data.packAsItems = $filter( 'filter' )( packingData, { source_item_id: item.item_id }, true );
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

				if( itemCount == 0 )
				{
					notifications.alert( 'Collection does not contain any items', 'warning' );
					return false;
				}

				for( var i = 0; i < itemCount; i++ )
				{
					if( items[i].converted_to )
					{ // packed item
						var conversionItem = $filter( 'filter' )( packingData, { source_item_id: items[i].mopped_item_id, target_item_id: items[i].converted_to }, true )[0] || null;
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
					appData.processCollection( data ).then(
						function( response )
						{
							if( $scope.data.editMode == 'new' )
							{
								appData.refresh( session.data.currentStore.id, 'collections' );
								notifications.alert( 'Collection record saved', 'success' );
								$scope.moppingItem.items = [];
							}
							else
							{
								appData.refresh( session.data.currentStore.id, 'collections' );
								notifications.alert( 'Collection record saved', 'success' );
								$state.go( 'main.store', { activeTab: 'collections' } );
							}
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};

		// Initialize controller
		$scope.onItemChange();
		$scope.onChangePullOutShift();

		// Load moppingItem
		if( $stateParams.moppingItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getCollection( $stateParams.moppingItem.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.moppingItem = response.data;
						$scope.moppingItem.processing_datetime = Date.parse( $stateParams.moppingItem.processing_datetime );
						$scope.moppingItem.business_date = Date.parse( $stateParams.moppingItem.business_date );
						$scope.data.selectedPullOutShift = $filter( 'filter')( $scope.data.pullOutShifts, { id: $scope.moppingItem.cashier_shift_id }, true )[0];
						$scope.checkItems();
					}
				},
				function( reason )
				{
					console.error( reason );
				});
		}
	}
]);

app.controller( 'AllocationController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'assigneeShifts',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, assigneeShifts )
	{
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
			assigneeShifts: angular.copy( assigneeShifts ),
			selectedAssigneeShift: null,
			assigneeTypes: angular.copy( appData.data.assigneeTypes ),
			selectedAssigneeType: { id: 1, typeName: 'Station Teller' },
			inventoryItems: angular.copy( appData.data.items ),
			selectedItem: null,
			categories: angular.copy( appData.data.itemCategories ),
			allocationPhase: 'allocation',

			assigneeLabel: 'Teller Name',
			assigneeShiftLabel: 'Teller Shift',
			remittancesTabLabel: 'Remittances',
			remittancesEmptyText: 'No remittance items'
		};

		$scope.input = {
			category: null,
			item: $scope.data.inventoryItems[0] || null,
			itemReservedQuantity: 0,
			quantity: null,
		};

		$scope.allocationItem = {
				store_id: session.data.currentStore.id,
				business_date: new Date(),
				shift_id: null,
				station_id: null,
				assignee: null,
				assignee_type: 1,
				allocation_status: 1, // ALLOCATION_SCHEDULED
				cashier_id: session.data.currentUser.id || null,
				allocations: [],
				remittances: []
			};

		$scope.showDatePicker = function()
			{
				$scope.data.businessDatepicker.opened = true;
			};

		$scope.updatePhase = function( phase )
			{
				$scope.data.allocationPhase = phase;
				$scope.updateCategories();
				$scope.updateAllocatableItems();
			}

		$scope.updateCategories = function()
			{
				$scope.data.categories = $filter( 'filter' )( appData.data.itemCategories, category_filter, true );
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

				$scope.data.inventoryItems = $filter( 'filter' )( appData.data.items, filter, true );
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
					$scope.data.remittancesEmptyText = 'No remittance items';
				}
				else if( $scope.data.selectedAssigneeType.id == 2 )
				{
					$scope.data.assigneeShifts = $filter( 'filter' )( assigneeShifts, { store_type: 1 }, true );
					$scope.data.assigneeLabel = 'TVM Number';
					$scope.data.assigneeShiftLabel = 'TVM Shift';
					$scope.data.remittancesTabLabel = 'Reject Bin';
					$scope.data.remittancesEmptyText = 'No reject items';
				}
				else
				{
					$scope.data.assigneeShifts = assigneeShifts;
				}

				if( $scope.data.assigneeShifts.length )
				{
					if( $scope.allocationItem.shift_id )
					{
						$scope.data.selectedAssigneeShift = $filter( 'filter')( assigneeShifts, { id: $scope.allocationItem.shift_id }, true )[0];
						if( $scope.data.selectedAssigneeShift )
						{
							$scope.data.selectedAssigneeShift = $scope.data.assigneeShifts[0];
						}
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
			};

		$scope.addAllocationItem = function()
			{
				if( ( event.type == 'keypress' ) && ( event.charCode == 13 )
						&& $scope.input.category
						&& $scope.input.item
						&& $scope.input.quantity > 0 )
				{
					var data = {
							cashier_shift_num: session.data.currentShift.shift_num,
							category_name: $scope.input.category.category,
							item_name: $scope.input.item.item_name,

							cashier_shift_id: session.data.currentShift.id,
							allocated_item_id: $scope.input.item.item_id,
							allocated_quantity: $scope.input.quantity,
							allocation_category_id: $scope.input.category.id,
							allocation_datetime: new Date(),
							allocation_item_status: null
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
					$scope.getItemQuantities();
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
				$scope.getItemQuantities();
			};

		$scope.checkItems = function( action )
			{
				var allocations = $scope.allocationItem.allocations;
				var remittances = $scope.allocationItem.remittances;
				var allocationCount = allocations.length;
				var remittanceCount = remittances.length;

				var preAllocationCategories = [ 'Initial Allocation', 'Magazine Load' ];
				var postAllocationCategories = [ 'Additional Allocation', 'Magazine Load' ];

				if( $scope.allocationItem.allocations.length == 0 )
				{
					notifications.alert( 'Allocation does not contain any items', 'warning' );
					return false;
				}

				switch( action )
				{
					case 'schedule':
					case 'allocate':
						var hasValidAllocationItem = false;
						for( var i = 0; i < allocationCount; i++ )
						{
							if( ( allocations[i].allocation_item_status == 10
								|| allocations[i].allocation_item_status == 11 )
								&& allocations[i].allocated_quantity > 0
								&& !allocations[i].allocationItemVoid )
							{
								hasValidAllocationItem = true;
								break;
							}
						}
						if( hasValidAllocationItem == false )
						{
							notifications.alert( 'Allocation does not contain any valid items', 'warning' );
							return false;
						}
						break;

					default:
						// do nothing
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
				if( $scope.checkItems( 'schedule' ) )
				{
					var data = $scope.prepareAllocation();
					appData.saveAllocation( data ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'allocations' );
							notifications.alert( 'Allocation record saved', 'success' );
							$state.go( 'main.store', { activeTab: 'allocations' } );
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};

		$scope.allocateAllocation = function()
			{
				if( $scope.checkItems( 'allocate' ) )
				{
					if( ! $scope.allocationItem.assignee )
					{
						notifications.alert( 'Please enter ' + $scope.data.assigneeLabel, 'warning' );
						return false;
					}
					var data = $scope.prepareAllocation();
					appData.allocateAllocation( data ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'allocations' );
							notifications.alert( 'Marked as Allocated', 'success' );
							$state.go( 'main.store', { activeTab: 'allocations' } );
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			}

		$scope.completeAllocation = function()
			{
				if( $scope.checkItems() )
				{
					var data = $scope.prepareAllocation();
					appData.completeAllocation( data ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'allocations' );
							notifications.alert( 'Marked as Remitted', 'success' );
							$state.go( 'main.store', { activeTab: 'allocations' } );
						},
						function( reason )
						{
							console.error( reason );
						});
				}
			};

		$scope.getItemQuantities = function()
			{
				switch( $scope.data.allocationPhase )
				{
					case 'allocation':
						var items = $scope.allocationItem.allocations;
						var n = items.length;
						$scope.input.itemReservedQuantity = 0;
						for( var i = 0; i < n; i++ )
						{
							if( items[i].allocated_item_id == $scope.input.item.item_id
								&& items[i].allocation_item_status == 10 )// ALLOCATION_ITEM_SCHEDULED
							{
								$scope.input.itemReservedQuantity += items[i].allocated_quantity;
							}
						}
						break;

					case 'remittance':
						$scope.input.itemReservedQuantity = 0;
						break;
				}

			};

		// Initialize controller
		$scope.onAssigneeTypeChange();
		$scope.onAssigneeShiftChange();
		$scope.updateCategories();
		$scope.updateAllocatableItems();

		// Load allocation item
		if( $stateParams.allocationItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getAllocation( $stateParams.allocationItem.id ).then(
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
		}
	}
]);