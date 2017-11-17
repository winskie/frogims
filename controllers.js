app.controller( 'NotificationController', [ '$scope', '$timeout', 'appData', 'notifications',
	function( $scope, $timeout, appData, notifications )
	{
		$scope.data = {
				messages: {}
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
						visible: false,
						duration: data.duration
					};

				$scope.data.messages[data.id] = newMessage;

				if( newMessage.duration > 0 )
				{
					$timeout( function()
						{
							newMessage.visible = true;
							$timeout( function()
								{
									newMessage.visible = false;
									$timeout( function()
									{
										delete $scope.data.messages[data.id];
									}, 500 );
								}, ( data.duration ? data.duration : 2300 ) );

						}, 10 );
				}
				else
				{
					$timeout( function()
						{
							newMessage.visible = true;
						}, 10 );
				}
			};

		$scope.closeNotification = function( event, data )
			{
				$timeout( function()
					{
						$scope.data.messages[data.id].visible = false;
						$timeout( function()
							{
								delete $scope.data.messages[data.id];
							}, 500 );
					}, 10 );
			};

		notifications.subscribe( $scope, 'notificationSignal',  $scope.showNotification );
		notifications.subscribe( $scope, 'notificationCloseSignal', $scope.closeNotification );
	}
]);

app.controller( 'MainController', [ '$rootScope', '$scope', '$filter', '$state', 'session', 'appData', 'lookup', 'notifications',
	function( $rootScope, $scope, $filter, $state, session, appData, lookup, notifications )
	{
		var allowStoreChange = [ 'main.dashboard', 'main.store' ];

		$scope.currentDate = new Date();
		$scope.canChangeStore = allowStoreChange.indexOf( $state.current.name ) != -1;
		$scope.canSetShiftBalances = ( session.data.currentStore.store_type == 4 || session.data.currentStore.store_type == 2 );
		$scope.sessionData = session.data;
		$scope.checkPermissions = session.checkPermissions;
		$scope.changeStore = function( newStore )
				{
					session.changeStore( newStore ).then(
						function( response )
						{
							$scope.canSetShiftBalances = ( session.data.currentStore.store_type == 4 || session.data.currentStore.store_type == 2 );
							$scope.shiftBalanceStatus = session.data.shiftBalance ? session.data.shiftBalance.st_status : 0;
						});
				};

		$scope.changeShift = function( newShift )
				{
					session.changeShift( newShift ).then(
						function( response )
						{
							$scope.shiftBalanceStatus = session.data.shiftBalance ? session.data.shiftBalance.st_status : 0;
						});
				};

		$scope.editShiftBalances = function()
			{
				var currentDate = $filter( 'date' )( new Date(), 'yyyy-MM-dd' )
				appData.getShiftTurnoverByStoreDateShift( session.data.currentStore.id, currentDate, session.data.currentShift.id ).then(
					function( response )
					{
						if( response.status == 'ok' )
						{
							if( response.data )
							{
								$state.go( 'main.shiftTurnover', { editMode: 'edit', shiftTurnover: response.data });
							}
						}
					});
			};

		$scope.lookup = lookup.getX;
		$scope.viewRecord = function( type, id )
			{
				switch( type )
				{
					case 'transferValidations':
						appData.getTransfer( id, 'validation' ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									$state.go( 'main.transferValidation', { transferItem: response.data } );
								}
							});
						break;

					case 'transfers':
						appData.getTransfer( id, 'validation' ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									if( response.data.origin_id == session.data.currentStore.id )
									{
										$state.go( 'main.transfer', { transferItem: response.data, editMode: 'auto' } );
									}
									else
									{
										notifications.alert( 'Transfer record not found', 'error' );
									}
								}
							});
						break;

					case 'receipts':
						appData.getTransfer( id, 'validation' ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									if( response.data.destination_id == session.data.currentStore.id )
									{
										$state.go( 'main.transfer', { transferItem: response.data, editMode: 'auto' } );
									}
									else
									{
										notifications.alert( 'Transfer record not found', 'error' );
									}
								}
							});
						break;

					case 'adjustments':
						appData.getAdjustment( id ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									$state.go( 'main.adjust', { adjustmentItem: response.data, editMode: 'auto' } );
								}
							});
						break;

					case 'conversions':
						appData.getConversion( id ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									$state.go( 'main.convert', { conversionItem: response.data, editMode: 'auto' } );
								}
							});
						break;

					case 'collections':
						appData.getCollection( id ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									$state.go( 'main.mopping', { moppingItem: response.data, editMode: 'view' } );
								}
							});
						break;

					case 'allocations':
						appData.getAllocation( id ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									$state.go( 'main.allocation', { allocationItem: response.data, editMode: 'auto' } );
								}
							});
						break;

					case 'shiftTurnovers':
						appData.getShiftTurnover( id ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									$state.go( 'main.shiftTurnover', { shiftTurnover: response.data, editMode: 'auto' } );
								}
							});
						break;

					case 'tvmReadings':
						appData.getTvmReading( id ).then(
							function( response )
							{
								if( response.status == 'ok' )
								{
									$state.go( 'main.tvmReading', { tvmReading: response.data, editMode: 'auto' } );
								}
							});

					default:
						// do nothing
				}
			};

		$scope.notify = function( message )
			{
				notifications.alert( 'Hello!', 'error', 200 );
			};

		var clnStateChangeSuccess = $rootScope.$on( '$stateChangeSuccess',
			function( event, toState, toParams, fromState, fromParams )
			{
				$scope.canChangeStore = allowStoreChange.indexOf( $state.current.name ) != -1;
				$scope.shiftBalanceStatus = session.data.shiftBalance ? session.data.shiftBalance.st_status : 0;
			});

		$scope.shiftBalanceStatus = session.data.shiftBalance ? session.data.shiftBalance.st_status : 0;
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
				'Senior': 'brown',
				'PWD': 'lightbrown',
				'L1 SJT': 'cyan',
				'L2 Ticket Coupon': 'magenta',
				'MRT SJT': 'gray',
				'Staff Card': 'violet',
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
							},
							reversedStacks: false
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
								borderWidth: 0,
								stacking: 'normal'
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

							var defaultItems = [ 'L2 SJT - Rigid Box', 'L2 SJT - Ticket Magazine', 'SVC - Rigid Box',
									'L2 SJT - Rigid Box (transit)', 'L2 SJT - Ticket Magazine (transit)', 'SVC - Rigid Box (transit)' ];

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
										stack: series[j].stack,
										color: series[j].in_transit === 1 ? 'white' : ( itemColors[series[j].stack] || undefined ),
										borderColor: itemColors[series[j].stack] || undefined,
										borderWidth: 2,
										linkedTo: series[j].in_transit === 1 ? ':previous' : undefined,
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

app.controller( 'FrontController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'lookup', 'notifications', 'sessionData',
	function( $scope, $filter, $state, $stateParams, session, appData, lookup, notifications, sessionData )
	{
		$scope.data = {
				inventoryView: 'shift',
				inventoryViewLabel: 'Show system values'
			};

		$scope.appData = appData.data;
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
				conversions: { index: 8, title: 'Conversions' },
				shiftTurnovers: { index: 9, title: 'Shift Turnovers' },
				tvmReadings: { index: 10, title: 'TVM Readings' },
				shiftDetailCashReports: { index: 11, title: 'Shift Detail Cash Reports' },
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

		$scope.switchInventoryView = function()
			{
				if( $scope.data.inventoryView == 'shift' )
				{
					$scope.data.inventoryView = 'system';
					$scope.data.inventoryViewLabel = 'Show system values';
				}
				else
				{
					$scope.data.inventoryView = 'shift';
					$scope.data.inventoryViewLabel = 'Hide system values';
				}
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
				conversions: false,
				shiftTurnovers: false,
				tvmReadings: false,
				shiftDetailCashReports: false,
			};

		$scope.widgets = {
				transactionsDate: {
					opened: false
				},
				transactionsItems: angular.copy( appData.data.items ),
				transactionsTypes: angular.copy( appData.data.transactionTypes ),
				transactionsShifts: angular.copy( session.data.storeShifts ),

				shiftTurnoverStartDate: {
					opened: false
				},
				shiftTurnoverEndDate: {
					opened: false
				},
				shiftTurnoverShifts: angular.copy( session.data.storeShifts ),

				transferValidationsDateSent: {
					opened: false
				},
				transferValidationsDateReceived: {
					opened: false
				},
				transferValidationsSources: angular.copy( appData.data.stores ),
				transferValidationsDestinations: angular.copy( appData.data.stores ),
				transferValidationsCategories: angular.copy( appData.data.transferCategories ),
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
				receiptsStatus: angular.copy( appData.data.receiptStatus ),

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
				conversionsItems: angular.copy( appData.data.items ),

				tvmReadingsDate: {
					opened: false
				},
				tvmReadingsShifts: angular.copy( session.data.storeShifts ),

				shiftDetailCashReportsDate: {
					opened: false
				},
				shiftDetailCashReports: angular.copy( session.data.storeShifts ),
			};

		$scope.widgets.transactionsItems.unshift({ id: null, item_name: 'All', item_description: 'All' });
		$scope.widgets.transactionsTypes.unshift({ id: null, typeName: 'All' });
		$scope.widgets.transactionsShifts.unshift({ id: null, shift_num: 'All', description: 'All' });

		$scope.widgets.shiftTurnoverShifts.unshift({ id: null, shift_num: 'All', description: 'All' });

		$scope.widgets.transferValidationsSources.unshift({ id: null, store_name: 'All' });
		$scope.widgets.transferValidationsSources.push({ id: '_ext_', store_name: 'External Sources' });
		$scope.widgets.transferValidationsDestinations.unshift({ id: null, store_name: 'All' });
		$scope.widgets.transferValidationsDestinations.push({ id: '_ext_', store_name: 'External Destinations' });
		$scope.widgets.transferValidationsCategories.unshift({ id: null, categoryName: 'All' });
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

		$scope.widgets.tvmReadingsShifts.unshift({ id: null, shift_num: 'All', description: 'All' });

		$scope.widgets.shiftDetailCashReports.unshift({ id: null, shift_num: 'All', description: 'All' });

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
						break;

					case 'shiftTurnovers':
						$scope.updateShiftTurnovers( currentStoreId );
						break;

					case 'tvmReadings':
						$scope.updateTvmReadings( currentStoreId );
						break;

					case 'shiftDetailCashReports':
						$scope.updateShiftDetailCashReports( currentStoreId );
						break;

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

		$scope.quicksearch = {};
		$scope.loadRecord = function( event, type, mode )
			{
				if( ( event.type == 'keypress' ) && ( event.keyCode == 13 ) )
				{
					$scope.viewRecord( type, $scope.quicksearch[type], mode );
					$scope.quicksearch[type] = null;
				}
			};

		$scope.showDatePicker = function( dp )
			{
				$scope.widgets[dp].opened = true;
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
		$scope.updateShiftTurnovers = appData.getShiftTurnovers;
		$scope.updateTvmReadings = appData.getTVMReadings;
		$scope.updateShiftDetailCashReports = appData.getShiftDetailCashReports;

		// Transfer validation actions
		$scope.completeTransferValidation = function( validation )
			{
				validation.save( 'complete' ).then(
					function( response )
					{
						notifications.alert( 'Transfer validation completed', 'success' );
						appData.refresh( null, 'transferValidations' );
					});
			};

		$scope.transferValidationOngoing = function( validation )
			{
				validation.save( 'ongoing' ).then(
					function( response )
					{
						notifications.alert( 'Transfer validation marked as ongoing', 'success' );
						appData.refresh( null, 'transferValidations' );
					});
			};

		$scope.transferValidationNotRequired = function( transfer )
			{
				var validation = transfer.getValidation();
				validation.save( 'not_required' ).then(
					function( response )
					{
						notifications.alert( 'Transfer marked as validation not required', 'success' );
						appData.refresh( null, 'transferValidations' );
					});
			};

		// Transfers actions
		$scope.approveTransfer = function( transfer )
			{
				transfer.save( 'approve' ).then(
					function( response )
					{
						notifications.alert( 'Transfer approved', 'success' );
						appData.refresh( session.data.currentStore.id, 'transfers' );
					});
			};

		$scope.receiveTransfer = function( transfer )
			{
				transfer.save( 'quick_receive' ).then(
					function( response )
					{
						notifications.alert( 'Transfer received', 'success' );
						appData.refresh( session.data.currentStore.id, 'receipts' );
					});
			};

		$scope.cancelTransfer = function( transfer )
			{
				transfer.save( 'cancel' ).then(
					function( response )
					{
						notifications.alert( 'Transfer cancelled', 'success' );
						appData.refresh( session.data.currentStore.id, 'transfers' )
					});
			};

		// Adjustment actions
		$scope.approveAdjustment = function( adjustment )
			{
				adjustment.save( 'approve' ).then(
					function( response )
					{
						notifications.alert( 'Adjustment approved', 'success' );
						appData.refresh( session.data.currentStore.id, 'adjustments' );
					});
			};

		$scope.cancelAdjustment = function( adjustment )
			{
				adjustment.save( 'cancel' ).then(
					function( response )
					{
						notifications.alert( 'Adjustment cancelled', 'success' );
						appData.refresh( session.data.currentStore.id, 'adjustments' );
					});
			};

		// Allocation actions
		$scope.allocateAllocation = function( allocation )
			{
				allocation.save( 'allocate' ).then(
					function( response )
					{
						notifications.alert( 'Record marked as Allocated', 'success' );
						appData.refresh( session.data.currentStore.id, 'allocations' );
					});
			};

		$scope.completeAllocation = function( allocation )
			{
				allocation.save( 'complete' ).then(
					function( response )
					{
						notifications.alert( 'Record marked as Completed', 'success' );
						appData.refresh( session.data.currentStore.id, 'allocations' );
					});
			};

		$scope.cancelAllocation = function( allocation )
			{
				allocation.save( 'cancel' ).then(
					function( response )
					{
						notifications.alert( 'Allocation cancelled', 'success' );
						appData.refresh( session.data.currentStore.id, 'allocations' );
					});
			};

		// Conversion actions
		$scope.cancelConversion = function( conversion )
			{
				conversion.save( 'cancel' ).then(
					function( response )
					{
						notifications.alert( 'Conversion approved', 'success' );
						appData.refresh( session.data.currentStore.id, 'conversions' );
					});
			};

		$scope.approveConversion = function( conversion )
			{
				conversion.save( 'approve' ).then(
					function( response )
					{
						notifications.alert( 'Conversion approved', 'success' );
						appData.refresh( session.data.currentStore.id, 'conversions' );
					});
			};

		// TVM Readings actions
		$scope.removeReading = function( reading )
			{
				reading.remove().then(
					function( response )
					{
						notifications.alert( 'TVM reading removed', 'success' );
						appData.refresh( session.data.currentStore.id, 'tvmReadings' );
					});
			};

		// Shift Detail Cash Report actions
		$scope.removeShiftDetailCashReport = function( report )
			{
				report.remove().then(
					function( response )
					{
						notifications.alert( 'Cash report deleted', 'success' );
						appData.refresh( session.data.currentStore.id, 'shiftDetailCashReports' );
					});
			};

		// Subscribe to notifications
		notifications.subscribe( $scope, 'onChangeStore',  function( event, data )
			{
				$scope.widgets.transactionsShifts = angular.copy( session.data.storeShifts );
				$scope.widgets.transactionsShifts.unshift({ id: null, shift_num: 'All', description: 'All' });

				$scope.widgets.shiftTurnoverShifts = angular.copy( session.data.storeShifts );

				var currentShiftFilterId = $scope.filters.transactions.shift.id;
				if( !$filter( 'filter' )( session.data.storeShifts, { id: currentShiftFilterId }, true ).length )
				{
					$scope.filters.transactions.shift = { id: null, shift_num: 'All', description: 'All' };
					appData.filters.transactions.shift = { id: null, shift_num: 'All', description: 'All' };

					$scope.filters.shiftTurnovers.shift = { id: null, shift_num: 'All', description: 'All' };
					appData.filters.shiftTurnovers.shift = { id: null, shift_num: 'All', description: 'All' };
				}

				appData.refresh( session.data.currentStore.id, data );
			});

		// Init controller

		appData.refresh( session.data.currentStore.id, 'all' );
	}
]);

app.controller( 'ShiftTurnoverController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'ShiftTurnover', 'ReportServices',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, ShiftTurnover, ReportServices )
	{
		$scope.pendingAction = false;

		$scope.data = {
				currentDate: new Date(),
				editMode: $stateParams.editMode || 'view',
				businessDatepicker: { format: 'yyyy-MM-dd', opened: false },
				turnoverShifts: angular.copy( session.data.storeShifts ),
				currentShift: null,
				nextShift: null,
				turnoverFromDatepicker: { format: 'yyyy-MM-dd', opened: false },
				turnoverToDatepicker: { format: 'yyyy-MM-dd', opened: false },
			};

		$scope.showDatePicker = function( dp )
			{
				if( dp == 'fromDate' )
				{
					$scope.data.turnoverFromDatepicker.opened = true;
				}
				else if( dp == 'toDate' )
				{
					$scope.data.turnoverToDatepicker.opened = true;
				}
			};

		$scope.loadTurnover = function( storeId, date, shiftId )
			{
				appData.getShiftTurnoverByStoreDateShift( storeId, date, shiftId ).then(
					function( response )
					{
						if( response.status == 'ok' )
						{
							$scope.shiftTurnover = ShiftTurnover.createFromData( response.data );
							$scope.data.currentShift = $filter( 'filter' )( session.data.storeShifts, { id: $scope.shiftTurnover.st_from_shift_id }, true )[0];
							$scope.data.nextShift = $filter( 'filter' )( session.data.storeShifts, { id: $scope.shiftTurnover.st_to_shift_id }, true )[0];

							if( $scope.data.editMode == 'auto' )
							{
								switch( $scope.shiftTurnover )
								{
									case 1:
										$scope.data.editMode = $scope.checkPermissions( 'shiftTurnovers', 'edit' ) ? 'edit' : 'view';
										break;

									case 2:
										$scope.data.editMode = 'view';
										break;

									default:
										$scope.data.editMode = 'view';
								}
							}

						}
						else
						{
							notifications.alert( 'Unable to load shift turnover record', 'error' );
							$state.go( 'main.store', { activeTab: 'shiftTurnovers' });
						}
					}
				);

			};


		// Shift turnover form events
		$scope.onChangeShift = function()
			{
				$scope.loadTurnover( session.data.currentStore.id, $scope.shiftTurnover.st_from_date, $scope.data.currentShift.id );
			};

		// Shift turnover actions
		$scope.saveTurnover = function( action )
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.shiftTurnover.save( action ).then(
						function( response )
						{
							if( response.isCurrent() )
							{
								session.data.shiftBalance = response;
							}
							appData.refresh( session.data.currentStore.id, 'turnover' );
							notifications.alert( 'Turnover record saved', 'success' );
							$state.go( 'main.store', { activeTab: 'shiftTurnovers' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							console.error( reason );
							$scope.pendingAction = false;
						});
				}
			};

		$scope.printReport = function( report )
			{
				switch( report )
				{
					case 'shiftTurnoverSummary':
						var params = {
								store_id: $scope.shiftTurnover.st_store_id,
								business_date: $filter( 'date' )( $scope.shiftTurnover.st_from_date, 'yyyy-MM-dd' ),
								shift_id: $scope.shiftTurnover.st_from_shift_id
							};
						report = 'shift_turnover_summary';
						break;

					default:
						return;
				}

				ReportServices.generateReport( report, params );
			};

		// Initialize controller
		if( $stateParams.shiftTurnover )
		{
			var shiftTurnover = $stateParams.shiftTurnover;
			$scope.loadTurnover( shiftTurnover.st_store_id, shiftTurnover.st_from_date, shiftTurnover.st_from_shift_id );
		}
		else
		{
			notifications.alert( 'No shift runover record requested', 'error' );
			$state.go( 'main.store', { activeTab: 'shiftTurnovers' });
		}
	}
]);

app.controller( 'TransferValidationController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'UserServices', 'Transfer', 'TransferValidation',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, UserServices, Transfer, TransferValidation )
	{
		$scope.pendingAction = false;

		$scope.data = {
				editMode: $stateParams.editMode || 'view'
			};

		$scope.input = {};

		$scope.findUser = UserServices.findUser;

		// Transfer validation form events
		$scope.onRecipientChange = function()
			{
				$scope.transferItem.transfer_validation.set( 'transval_receipt_sweeper', $scope.transferItem.transfer_validation.transval_receipt_sweeper );
			};

		$scope.onTransfereeChange = function()
			{
				$scope.transferItem.transfer_validation.set( 'transval_transfer_sweeper', $scope.transferItem.transfer_validation.transval_transval_sweeper );
			};


		// Transfer validation actions
		$scope.validateReceipt = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.transferItem.transfer_validation.save( 'validate_receipt' ).then(
						function( response )
						{
							appData.refresh( null, 'transferValidations' );
							notifications.alert( 'Receipt of items from source validated', 'success' );
							$state.go( 'main.store', { activeTab: 'transferValidations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.markReturned = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.transferItem.transfer_validation.save( 'returned' ).then(
						function( response )
						{
							appData.refresh( null, 'transferValidations' );
							notifications.alert( 'Transfer marked as returned', 'success' );
							$state.go( 'main.store', { activeTab: 'transferValidations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.validateTransfer = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.transferItem.transfer_validation.save( 'validate_transfer' ).then(
						function( response )
						{
							appData.refresh( null, 'transferValidations' );
							notifications.alert( 'Receipt of items by recipient validated', 'success' );
							$state.go( 'main.store', { activeTab: 'transferValidations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.markDisputed = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.transferItem.transfer_validation.save( 'dispute' ).then(
						function( response )
						{
							appData.refresh( null, 'transferValidations' );
							notifications.alert( 'Receipt of items by recipient disputed', 'success' );
							$state.go( 'main.store', { activeTab: 'transferValidations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.markCompleted = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.transferItem.transfer_validation.save( 'complete' ).then(
						function( response )
						{
							appData.refresh( null, 'transferValidations' );
							notifications.alert( 'Transfer validation completed', 'success' );
							$state.go( 'main.store', { activeTab: 'transferValidations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.markOngoing = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.transferItem.transfer_validation.save( 'ongoing' ).then(
						function( response )
						{
							appData.refresh( null, 'transferValidations' );
							notifications.alert( 'Transfer validation marked as ongoing', 'success' );
							$state.go( 'main.store', { activeTab: 'transferValidations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.markNotRequired = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.transferItem.transfer_validation.save( 'not_required' ).then(
						function( response )
						{
							appData.refresh( null, 'transferValidations' );
							notifications.alert( 'Transfer marked as validation not required', 'success' );
							$state.go( 'main.store', { activeTab: 'transferValidations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};


		// Initialize form
		if( $stateParams.transferItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getTransfer( $stateParams.transferItem.id, [ 'validation' ] ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.transferItem = Transfer.createFromData( response.data );
						if( ! $scope.transferItem.transfer_validation )
						{
							$scope.transferItem.transfer_validation = new TransferValidation();
							$scope.transferItem.transfer_validation.transval_transfer_id = $scope.transferItem.id;
						}

						if( !$scope.checkPermissions( 'transferValidations', 'edit' ) || ( $scope.data.editMode != 'view' && $scope.transferItem.validation.transval_status != 1 ) )// TRANSFER_VALIDATION_ONGOING
						{
							$scope.data.editMode = 'view';
						}

						if( ( $scope.transferItem.transfer_validation.transval_status == null || $scope.transferItem.transfer_validation.transval_status == 1 )
								&& $scope.transferItem.transfer_validation.transval_receipt_sweeper == null
								&& $scope.transferItem.canValidateReceipt() )
						{
							$scope.transferItem.transfer_validation.set( 'transval_receipt_sweeper', session.data.currentUser );
						}

						if( $scope.transferItem.transfer_status == 3 // TRANSFER_RECEIVED
								&& $scope.transferItem.transfer_validation.transval_status == 1
								&& $scope.transferItem.transfer_validation.transval_transfer_sweeper == null
								&& $scope.transferItem.canValidateTransfer() )
						{
							$scope.transferItem.transfer_validation.set( 'transval_transfer_sweeper', session.data.currentUser );
						}
					}
				},
				function( reason )
				{
					notifications.alert( reason );
				});
		}
		else
		{
			notifications.alert( 'Record not found', 'error' );
		}
	}
]);

app.controller( 'TransferController', [ '$scope', '$filter', '$state', '$stateParams', '$uibModal', '$window', 'baseUrl', 'session', 'appData', 'notifications', 'Transfer', 'TransferItem', 'UserServices', 'ReportServices',
	function( $scope, $filter, $state, $stateParams, $uibModal, $window, baseUrl, session, appData, notifications, Transfer, TransferItem,  UserServices, ReportServices )
	{
		var users = [];

		function suggestTransferCategory()
		{
			var category = appData.suggestTransferCategory( $scope.transferItem, $scope.data.selectedCategory );
			$scope.data.selectedCategory = getCategoryById( category );
			return category; // General
		}

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

		function filterCashItems( items )
		{
			var n = items.length;
			var filteredItems = [];
			for( var i = 0; i < n; i++ )
			{
				if( items[i].item_class == 'cash' )
				{
					filteredItems.push( items[i] );
				}
			}

			return filteredItems;
		}

		function filterTransferCategories( category, index, array )
		{
			return category.store_types.indexOf( session.data.currentStore.store_type ) != -1;
		}

		$scope.pendingAction = false;
		$scope.data = {
			mode: null, // transfer | receipt
			editMode: $stateParams.editMode || 'auto', // view, externalReceipt, externalTransfer, receipt, transfer
			title: 'Transfer',
			sources: [],
			destinations: [],
			selectedSource: null,
			selectedDestination: null,
			isExternalSource: false,
			isExternalDestination: false,
			inventoryItems: angular.copy( appData.data.items ),
			categories: [],
			sweepers: [],
			transferCategories: $filter( 'filter' )( angular.copy( appData.data.transferCategories ), filterTransferCategories ),
			selectedCategory: null,
			autoCategory: true,
			transferDatepicker: { format: 'yyyy-MM-dd', opened: false },
			receiptDatepicker: { format: 'yyyy-MM-dd HH:mm:ss', opened: false },
			showCategory: ( session.data.currentStore.store_type == 4 ),
			showAllocationItemEntry: ( session.data.currentStore.store_type == 4 && $scope.checkPermissions( 'allocations', 'view' ) )
		};

		$scope.data.selectedCategory = getCategoryById( 2 );

		$scope.input = {
				inventoryItem: null,
				itemReservedQuantity: 0,
				category: null,
				quantity: 1,
				remarks: null,
				allocation: null
			};

		$scope.findUser = UserServices.findUser;

		$scope.changeEditMode = function( editMode )
			{
				var validEditModes = [ 'transfer', 'receipt', 'externalTransfer', 'externalReceipt' ];

				if( editMode && validEditModes.indexOf( editMode ) != -1 )
				{
					$scope.data.editMode = editMode;
				}

				switch( $scope.data.editMode )
				{
					case 'transfer':
						$scope.data.mode = 'transfer';
						$scope.data.title = 'Transfer';
						$scope.isExternalSource = false;
						$scope.isExternalDestination = false;

						/*
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

						// Set applicable transfer categories
						$scope.data.transferCategories = angular.copy( appData.data.transferCategories );
						*/
						break;

					case 'receipt':
						$scope.data.mode = 'receipt';
						$scope.data.title = 'Receipt';
						$scope.isExternalSource = false;
						$scope.isExternalDestination = false;
						/*
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

						// Set applicable transfer categories
						$scope.data.transferCategories = angular.copy( appData.data.transferCategories );
						*/
						break;

					case 'externalTransfer':
						$scope.data.mode = 'transfer';
						$scope.data.title = 'External Transfer';
						$scope.isExternalSource = false;
						$scope.isExternalDestination = true;
						/*
						$scope.data.sources = [ session.data.currentStore ];
						if( $scope.transferItem.origin_id )
						{
							$scope.data.selectedSource = $filter( 'filter' )( appData.data.stores, { id: $scope.transferItem.origin_id }, true )[0];
						}
						else
						{
							$scope.data.selectedSource = session.data.currentStore;
						}

						// Set applicable transfer categories
						$scope.data.transferCategories = angular.copy( appData.data.transferCategories );

						$scope.data.destinations = []; // why do we need to clear this?
						*/
						break;

					case 'externalReceipt':
						$scope.data.mode = 'receipt';
						$scope.data.title = 'External Receipt';
						$scope.isExternalSource = true;
						$scope.isExternalDestination = false;
						/*
						// $scope.data.sources = []; // why do we need to clear this?

						$scope.data.destinations = [ session.data.currentStore ];
						if( $scope.transferItem.destination_id )
						{
							$scope.data.selectedDestination = $filter( 'filter' )( appData.data.stores, { id: $scope.transferItem.destination_id }, true )[0];
						}
						else
						{
							$scope.data.selectedDestination = session.data.currentStore;
						}

						// Set applicable transfer categories
						$scope.data.transferCategories = $filter( 'filter' )( appData.data.transferCategories, { categoryName: '!Internal Transfer' }, true );
						*/
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
						console.error( 'Invalid entry mode - ' + $scope.data.editMode );
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

				// Check if current selected transfer category is valid
			};

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

		$scope.toggle = function( field, editMode )
			{
				if( field == 'source' )
				{
					if( editMode )
					{
						$scope.data.editMode = editMode;
					}
					else if( $scope.data.editMode == 'receipt' )
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
					if( editMode )
					{
						$scope.data.editMode = editMode;
					}
					else if( $scope.data.editMode == 'transfer' )
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
					else
					{
						$scope.transferItem.destination_id = $scope.data.selectedDestination.id;
						$scope.transferItem.destination_name = $scope.data.selectedDestination.destination_name;
					}
				}

				$scope.changeEditMode();
				if( $scope.data.autoCategory )
				{
					//$scope.transferItem.transfer_category = suggestTransferCategory( $scope.data.selectedCategory );
				}
			};

		$scope.updateItemCategories = function()
			{
				$scope.data.categories = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_module: 'Transfer' }, true );
			};

		$scope.getItemQuantities = function()
			{
				var items = $scope.transferItem.items;
				var n = items.length;
				var reservedQuantity = 0;
				$scope.input.itemReservedQuantity = 0;
				for( var i = 0; i < n; i++ )
				{
					if( items[i].item_id == $scope.input.inventoryItem.item_id
						&& items[i].transfer_item_status == 1
						&& !items[i].id ) // TRANSFER_ITEM_SCHEDULED
					{
						reservedQuantity += parseInt( items[i].quantity );
					}
					else if( items[i].item_id == $scope.input.inventoryItem.item_id
						&& items[i].markedVoid )
					{
						reservedQuantity -= parseInt( items[i].quantity );
					}
				}
				$scope.input.itemReservedQuantity = reservedQuantity;
			};


		// Transfer form events
		$scope.changeSource = function()
			{
				$scope.transferItem.setOrigin( $scope.data.selectedSource );
			};

		$scope.changeDestination = function()
			{
				$scope.transferItem.setDestination( $scope.data.selectedDestination );
			}

		$scope.changeTransferCategory = function( category )
			{
				$scope.data.selectedCategory = category;
				$scope.transferItem.transfer_category = $scope.data.selectedCategory.id;

				switch( category.categoryName )
				{
					case 'External Transfer':
						switch( $scope.data.editMode )
						{ // Switch to external
							case 'receipt':
								$scope.data.editMode = 'externalReceipt';
							case 'externalReceipt':
								$scope.data.selectedSource = null;
								$scope.data.selectedDestination = session.data.currentStore;
								break;

							case 'transfer':
								$scope.data.editMode = 'externalTransfer';
							case 'externalTransfer':
								$scope.data.selectedSource = session.data.currentStore;
								$scope.data.selectedDestination = null;
								break;
						}
						$scope.changeEditMode();

						var filteredItems;
						filteredItems = $filter( 'itemsWithProps' )( appData.data.items, 'ExtTrans' );
						if( filteredItems.length > 0 )
						{
							$scope.data.inventoryItems = filteredItems;
							$scope.input.inventoryItem = $scope.data.inventoryItems[0];
							$scope.onItemChange();
							$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_name: 'ExtTrans' }, true )[0];
						}
						break;

					case 'Internal Transfer':
						switch( $scope.data.editMode )
						{
							case 'externalReceipt':
								$scope.data.editMode = 'receipt';
								console.error( 'Should not be possible! Contact the system administrator.' );
							case 'receipt':
								$scope.selectedDestination = session.data.currentStore;
								break;

							case 'externalTransfer':
								$scope.data.editMode = 'transfer';
							case 'transfer':
								$scope.data.selectedSource = session.data.currentStore;
								$scope.data.destinations = $filter( 'filter' )( appData.data.stores, { id: '!' + session.data.currentStore.id }, function(a, e) { return angular.equals( parseInt(a), parseInt(e) ) } );
								$scope.data.selectedDestination = $scope.data.destinations[0];
								break;
						}
						$scope.changeEditMode();

						var filteredItems;
						filteredItems = $filter( 'itemsWithProps' )( appData.data.items, 'IntTrans' );
						if( filteredItems.length > 0 )
						{
							$scope.data.inventoryItems = filteredItems;
							$scope.input.inventoryItem = $scope.data.inventoryItems[0];
							$scope.onItemChange();
							$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_name: 'IntTrans' }, true )[0];
						}
						break;

					case 'Ticket Turnover':
						$scope.data.selectedSource = session.data.currentStore;
						// Filter destination to stores with production store type
						$scope.data.destinations = $filter( 'filter' )( appData.data.stores, { store_type: 2 }, true );
						$scope.data.selectedDestination = $scope.data.destinations[0];
						$scope.changeEditMode( 'transfer' );

						var filteredItems;
						filteredItems = $filter( 'itemsWithProps' )( appData.data.items, 'TktTurn' );
						if( filteredItems.length > 0 )
						{
							$scope.data.inventoryItems = filteredItems;
							$scope.input.inventoryItem = $scope.data.inventoryItems[0];
							$scope.onItemChange();
							$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_name: 'TktTurn' }, true )[0];
						}
						break;

					case 'Stock Replenishment':
						$scope.data.selectedSource = session.data.currentStore;
						// Filter destination to stores with cashroom store type
						$scope.data.destinations = $filter( 'filter' )( appData.data.stores, { store_type: 4 }, true );
						$scope.data.selectedDestination = $scope.data.destinations[0];
						$scope.changeEditMode( 'transfer' );

						var filteredItems;
						filteredItems = $filter( 'itemsWithProps' )( appData.data.items, 'StockRep' );
						if( filteredItems.length > 0 )
						{
							$scope.data.inventoryItems = filteredItems;
							$scope.input.inventoryItem = $scope.data.inventoryItems[0];
							$scope.onItemChange();
							$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_name: 'StockRep' }, true )[0];
						}
						break;

					case 'Blackbox Receipt':
						$scope.data.selectedSource = null;
						$scope.data.selectedDestination = session.data.currentStore;
						$scope.changeEditMode( 'externalReceipt' );

						var filteredItems;
						filteredItems = $filter( 'itemsWithProps' )( appData.data.items, 'Blackbox' );
						if( filteredItems.length > 0 )
						{
							$scope.data.inventoryItems = filteredItems;
							$scope.input.inventoryItem = $scope.data.inventoryItems[0];
							$scope.onItemChange();
							$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_name: 'Blackbox' }, true )[0];
						}
						break;

					case 'Bills to Coins Exchange':
						switch( $scope.data.editMode )
						{
							case 'receipt':
								$scope.data.editMode = 'externalReceipt';
							case 'externalReceipt':
								$scope.data.selectedSource = null;
								$scope.data.selectedDestination = session.data.currentStore;
								break;

							case 'transfer':
								$scope.data.editMode = 'externalTransfer';
							case 'externalTransfer':
								$scope.data.selectedSource = session.data.currentStore;
								$scope.data.selectedDestination = null;
								break;
						}
						$scope.changeEditMode();

						var filteredItems;
						filteredItems = $filter( 'itemsWithProps' )( appData.data.items, 'BillToCoin' );
						if( filteredItems.length > 0 )
						{
							$scope.data.inventoryItems = filteredItems;
							$scope.input.inventoryItem = $scope.data.inventoryItems[0];
							$scope.onItemChange();
							$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_name: 'BillToCoin' }, true )[0];
						}
						break;

					case 'CSC Application':
						switch( $scope.data.editMode )
						{
							case 'receipt':
								$scope.data.editMode = 'externalReceipt';
							case 'externalReceipt':
								$scope.data.selectedSource = null;
								$scope.data.selectedDestination = session.data.currentStore;
								break;

							case 'transfer':
								$scope.data.editMode = 'externalTransfer';
							case 'externalTransfer':
								$scope.data.selectedSource = session.data.currentStore;
								$scope.data.selectedDestination = null;
								break;
						}
						var filteredItems;
						filteredItems = $filter( 'itemsWithProps' )( appData.data.items, 'CSCApp' );
						if( filteredItems.length > 0 )
						{
							$scope.data.inventoryItems = filteredItems;
							$scope.input.inventoryItem = $scope.data.inventoryItems[0];
							$scope.onItemChange();
							$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_name: 'CSCApp' }, true )[0];
						}
						break;

					case 'Bank Deposit':
						$scope.data.selectedSource = session.data.currentStore;
						$scope.data.selectedDestination = null;
						$scope.changeEditMode( 'externalTransfer' );

						var filteredItems;
						filteredItems = $filter( 'itemsWithProps' )( appData.data.items, 'BankDep' );
						if( filteredItems.length > 0 )
						{
							$scope.data.inventoryItems = filteredItems;
							$scope.input.inventoryItem = $scope.data.inventoryItems[0];
							$scope.onItemChange();
							$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_name: 'BankDep' }, true )[0];
						}
						break;
				}

				// Apply changes to source and destination
				$scope.changeSource();
				$scope.changeDestination();
			};

		$scope.onItemChange = function()
			{
				$scope.updateItemCategories();
				$scope.getItemQuantities();
			};

		$scope.onDeliveryPersonChange = function()
			{
				if( $scope.data.mode == 'transfer' )
				{
					$scope.transferItem.set( 'recipient_name', $scope.transferItem.recipient_name );
				}
				else
				{
					$scope.transferItem.set( 'sender_name', $scope.transferItem.sender_name );
				}
			};

		$scope.onRecipientChange = function()
			{
				$scope.transferItem.set( 'recipient_name', $scope.transferItem.recipient_name );
			};


		// Modals
		$scope.showTurnoverItems = function()
			{
				var modalInstance = $uibModal.open({
						templateUrl: baseUrl + 'index.php/main/view/modal_turnover_items',
						controller: 'TurnoverItemModalController',
						controllerAs: '$ctrl',
						size: 'lg'
					});

				modalInstance.result.then(
					function( items )
					{
						$scope.addTurnoverItems( items );
					},
					function()
					{

					});
			};


		// Transfer item actions
		$scope.addTransferItem = function( event )
			{
				if( ( event.type == 'keypress' ) && ( event.keyCode == 13 )
						&& $scope.input.inventoryItem
						&& $scope.input.category
						&& $scope.input.quantity > 0 )
				{
					var data = {
							item_name: $scope.input.inventoryItem.item_name,
							cat_description: $scope.input.category.cat_description,

							item_id: $scope.input.inventoryItem.item_id,
							transfer_item_category_id: $scope.input.category.id,
							quantity: $scope.input.quantity,
							remarks: $scope.input.remarks,
							transfer_item_status: 1, // TRANSFER_ITEM_SCHEDULED
						};

					if( $scope.data.editMode == 'externalReceipt' )
					{
						data.quantity_received = $scope.input.quantity;
					}

					$scope.transferItem.addItem( new TransferItem( data ) );
					$scope.input.quantity = null;
					$scope.getItemQuantities();
				}
			};

		$scope.addTurnoverItems = function( items )
			{
				for( var i = 0; i < items.length; i++ )
				{
					var itemRemarks;
					switch( items[i].item_source )
					{
						case 'Blackbox':
							itemRemarks = 'Transfer #' + items[i].source_id + ' - ' + items[i].assignee;
							break;

						case 'Remittance':
							itemRemarks = 'Allocation #' + items[i].source_id + ' - ' + ( items[i].assignee_type == 2 ? 'TVM #' : '' ) + items[i].assignee;
							break;
					}

					var data = {
						item_name: items[i].item_name,
						category_name: items[i].category,

						item_id: items[i].item_id,
						transfer_item_category_id: items[i].transfer_item_category_id,
						quantity: items[i].quantity,
						remarks: itemRemarks,
						transfer_item_status: 1, // TRANSFER_ITEM_SCHEDULED
						transfer_item_allocation_item_id: items[i].allocation_item_id,
						transfer_item_transfer_item_id: items[i].transfer_item_id
					}

					$scope.transferItem.addItem( new TransferItem( data ) );
				}
				$scope.getItemQuantities();
			};

		$scope.removeTransferItem = function( itemRow )
			{
				if( itemRow.id == undefined ) // ALLOCATION_ITEM_SCHEDULED
				{ // remove only items not yet in database
					$scope.transferItem.removeItem( itemRow );
					$scope.getItemQuantities();
				}
			};


		// Transfer actions
		$scope.scheduleTransfer = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.transferItem.save().then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'transfers' );
							notifications.alert( 'Transfer record saved', 'success' );
							$state.go( 'main.store', { activeTab: 'transfers' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							console.error( reason );
							$scope.pendingAction = false;
						});
				}
			};

		$scope.approveTransfer = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.transferItem.save( 'approve' ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'transfers' );
							notifications.alert( 'Transfer approved', 'success' );
							$state.go( 'main.store', { activeTab: 'transfers' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							console.error( reason );
							$scope.pendingAction = false;
						});
				}
			};

		$scope.receiveTransfer = function( quick )
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.transferItem.save( quick ? 'quick_receive' : 'receive' ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'receipts' );
							notifications.alert( 'Transfer received', 'success' );
							$state.go( 'main.store', { activeTab: 'receipts' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							console.error( reason );
							$scope.pendingAction = false;
						});
				}
			};

		$scope.printReport = function( report )
			{
				switch( report )
				{
					case 'ticketTurnover':
						var params = {
								transfer_id: $scope.transferItem.id
							};
						report = 'ticket_turnover';
						break;

					case 'deliveryReceipt':
						var params = {
								transfer_id: $scope.transferItem.id
							};
						report = 'delivery_receipt';
						break;

					case 'receivingReport':
						var params = {
								transfer_id: $scope.transferItem.id
							};
						report = 'receiving_report';
						break;

					default:
						return;
				}

				ReportServices.generateReport( report, params );
			};


		// Initialize controller

		// Select initial inventory input item
		$scope.input.inventoryItem = $scope.data.inventoryItems[0];

		// Select initial category
		$scope.updateItemCategories();
		$scope.input.category = $filter( 'filter' )( $scope.input.inventoryItem.categories, { cat_module: 'Transfer' }, true )[0];

		if( $stateParams.transferItem )
		{ // Load transfer record
			$scope.data.autoCategory = false;
			$scope.data.editMode = $stateParams.editMode || 'auto';

			appData.getTransfer( $stateParams.transferItem.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						if( $scope.data.editMode == 'auto' || $scope.data.editMode == 'view' )
						{
							if( ! $stateParams.transferItem.origin_id && $stateParams.transferItem.origin_name )
							{
								if( $scope.data.editMode == 'auto' ) $scope.data.editMode = 'externalReceipt';
								$scope.data.mode = 'receipt';
							}
							else if( ! $stateParams.transferItem.destination_id && $stateParams.transferItem.destination_name )
							{
								if( $scope.data.editMode == 'auto' ) $scope.data.editMode = 'externalTransfer';
								$scope.data.mode = 'transfer';
							}
							else if( $stateParams.transferItem.origin_id == session.data.currentStore.id )
							{
								if( $scope.data.editMode == 'auto' ) $scope.data.editMode = 'transfer';
								$scope.data.mode = 'transfer';
							}
							else if( $stateParams.transferItem.destination_id == session.data.currentStore.id )
							{
								if( $scope.data.editMode == 'auto' ) $scope.data.editMode = 'receipt';
								$scope.data.mode = 'receipt';
							}
						}

						if( $scope.data.editMode == 'externalReceipt' || $scope.data.editMode == 'receipt' )
						{
							$scope.data.mode = 'receipt';
							switch( $stateParams.transferItem.transfer_status )
							{
								case 1: // TRANSFER_SCHEDULED
									$scope.data.editMode = 'view';
									break;

								case 2: // TRANSFER_APPROVED
									if( $scope.data.editMode != 'receipt' )
									{
										$scope.data.editMode = 'view';
									}
									break;

								case 3: // TRANSFER_RECEIVED
									$scope.data.editMode = 'view';
									break;

								case 4: // TRANSFER_CANCELLED_SCHEDULED
									$scope.data.editMode = 'view';
									break;

								case 5: // TRANSFER_CANCELLED_APPROVED
									$scope.data.editMode = 'view';
									break;

								default:
									$scope.data.editMode = 'view';
									// do nothing
							}
						}
						else if( $scope.data.editMode == 'externalTransfer' || $scope.data.editMode == 'transfer' )
						{
							$scope.data.mode = 'transfer';
							switch( $stateParams.transferItem.transfer_status )
							{
								case 1: // TRANSFER_SCHEDULED
									// do nothing
									break;

								case 2: // TRANSFER_APPROVED
									$scope.data.editMode = 'view';
									break;

								case 3: // TRANSFER_RECEIVED
									$scope.data.editMode = 'view';
									break;

								case 4: // TRANSFER_CANCELLED_SCHEDULED
									$scope.data.editMode = 'view';
									break;

								case 5: // TRANSFER_CANCELLED_APPROVED
									$scope.data.editMode = 'view';
									break;

								default:
									$scope.data.editMode = 'view';
									// do nothing
							}
						}

						if( $scope.data.editMode != 'view' && !$scope.checkPermissions( 'transfers', 'edit' ) )
						{
							$scope.data.editMode = 'view';
						}

						// Load transfer record
						$scope.transferItem = Transfer.createFromData( response.data );
						$scope.transferItem.setMode( $scope.data.editMode );

						// Set origin input
						if( $scope.transferItem.origin_id )
						{
							$scope.data.selectedSource = $filter( 'filter')( appData.data.stores, { id: $scope.transferItem.origin_id }, true )[0];
						}
						else
						{
							$scope.data.selectedSource = null;
							$scope.data.isExternalSource = true;
						}

						// Set destination input
						if( $scope.transferItem.destination_id )
						{
							$scope.data.selectedDestination = $filter( 'filter')( appData.data.stores, { id: $scope.transferItem.destination_id }, true )[0];
						}
						else
						{
							$scope.data.selectedDestination = null;
							$scope.data.isExternalDestination = true;
						}

						// Set recipient name
						if( ! $scope.transferItem.recipient_name && $scope.data.editMode == 'receipt' )
						{
							$scope.transferItem.recipient_id = session.data.currentUser.id;
							$scope.transferItem.recipient_name = session.data.currentUser.full_name;
						}

						// Set item quantity received values
						if( $scope.data.editMode == 'receipt' )
						{
							var itemCount = $scope.transferItem.items.length;
							for( var i = 0; i < itemCount; i++ )
							{
								if( ! $scope.transferItem.items[i].quantity_received && $scope.transferItem.items[i].transfer_item_status == 2 )
								{
									$scope.transferItem.items[i].quantity_received = $scope.transferItem.items[i].quantity;
								}
							}
						}

						// Set transfer category
						$scope.data.selectedCategory = getCategoryById( $scope.transferItem.transfer_category );

						$scope.changeEditMode();
						$scope.data.autoCategory = true;
					}
					else
					{
						console.error( 'Unable to load transfer record' );
					}
				},
				function( reason )
				{
					notifications.alert( reason, 'error' );
				});

		}
		else
		{
			var defaultCategory;

			$scope.transferItem = new Transfer();
			$scope.transferItem.setMode( $scope.data.editMode );
			$scope.changeEditMode();

			if( $stateParams.category )
			{
				var defaultCategories = $filter( 'filter' )( appData.data.transferCategories, { categoryName: $stateParams.category }, true );
				if( $defaultCategories.length )
				{
					$defaultCategory = $defaultCategories[0];
				}
				else
				{
					console.error( 'Invalid transfer category. Please contact the system administrator.' );
				}
			}
			else
			{
				// Change to default category per store type
				switch( session.data.currentStore.store_type )
				{
					case 3: // TGM
						defaultCategory = { id: 4, categoryName: 'Stock Replenishment', transfer: true, receipt: false };
						break;
					case 4: // Cashroom
						defaultCategory = { id: 6, categoryName: 'Bills to Coins Exchange', transfer: true, receipt: true };
						break;

					case 1: // Standard
					case 2: // Production
					default:
						switch( $scope.data.editMode )
						{
							case 'transfer':
								defaultCategory = { id: 2, categoryName: 'Internal Transfer', transfer: true, receipt: false };
								break;

							case 'externalTransfer':
								defaultCategory = { id: 1, categoryName: 'External Transfer', transfer: true, receipt: false };
								break;

							case 'receipt':
								defaultCategory = { id: 2, categoryName: 'Internal Transfer', transfer: true, receipt: false };
								break;

							case 'externalReceipt':
								defaultCategory = { id: 1, categoryName: 'External Transfer', transfer: true, receipt: false };
								break;
						}
				}
			}
			if( defaultCategory )
			{
				$scope.changeTransferCategory( defaultCategory );
			}
			//$scope.changeSource();
			//$scope.changeDestination();
		}
	}
]);

app.controller( 'AdjustmentController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'transactionTypes', 'Adjustment',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, transactionTypes, Adjustment )
	{
		$scope.pendingAction = false;

		$scope.data = {
				editMode: $stateParams.editMode || 'auto',
				inventoryItems: $filter( 'itemsWithProps' )( angular.copy( appData.data.items ), 'Adjust' ),
				selectedItem: appData.data.items[0],
				transactionTypes: angular.copy( transactionTypes )
			};

		$scope.data.transactionTypes.unshift( { id: null, typeName: 'None' }  );
		$scope.data.selectedTransactionType = $scope.data.transactionTypes[0];

		// Adjustment form events
		$scope.changeItem = function()
			{
				$scope.adjustmentItem.store_inventory_id = $scope.data.selectedItem.id;
			};

		$scope.changeTransactionType = function()
			{
				$scope.adjustmentItem.adj_transaction_type = $scope.data.selectedTransactionType.id;
			};


		// Adjustment actions
		$scope.saveAdjustment = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.adjustmentItem.save().then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'adjustments' );
							notifications.alert( 'Adjustment record saved', 'success' );
							$state.go( 'main.store', { activeTab: 'adjustments' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.approveAdjustment = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.adjustmentItem.save( 'approve' ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'adjustments' );
							notifications.alert( 'Adjustment approved', 'success' );
							$state.go( 'main.store', { activeTab: 'adjustments' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.cancelAdjustment = function()
			{
				if( ! $scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.adjustmentItem.save( 'cancel' ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'adjustments' );
							notifications.alert( 'Adjustment cancelled', 'success' );
							$state.go( 'main.store', { activeTab: 'adjustments' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
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
						$scope.adjustmentItem = Adjustment.createFromData( response.data );

						// Set inventory item
						$scope.data.selectedItem = $filter( 'filter' )( appData.data.items, { id: $stateParams.adjustmentItem.store_inventory_id }, true )[0];

						// Set transaction type
						$scope.data.selectedTransactionType = $filter( 'filter' )( $scope.data.transactionTypes, { id: $stateParams.adjustmentItem.adj_transaction_type }, true )[0];

						if( $scope.data.editMode == 'auto' )
						{
							switch( $scope.adjustmentItem.adjustment_status )
							{
								case 1: // ADJUSTMENT_PENDING
									$scope.data.editMode = 'edit';
									break;

								case 2: // ADJUSTMENT_APPROVED
									$scope.data.editMode = 'view';
									break;

								case 3: // ADJUSTMENT_CANCELLED
									$scope.data.editMode = 'view';
									break;

								default:
									$scope.data.editMode = 'view';
							}
						}

						if( !$scope.checkPermissions( 'adjustments', 'edit' ) || ( $scope.data.editMode != 'view' && $scope.adjustmentItem.adjustment_status != 1 ) ) // ADJUSTMENT_PENDING
						{
							$scope.data.editMode = 'view';
						}
					}
				},
				function( reason )
				{
					console.error( reason );
				});
		}
		else
		{
			$scope.adjustmentItem = new Adjustment();
			$scope.changeItem();
			$scope.changeTransactionType();
		}
	}
]);

app.controller( 'ConversionController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'conversionTable', 'Conversion',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, conversionTable, Conversion )
	{
		function outputItemFilter( value, index, array )
		{
			return convertibleItems.indexOf( value.item_id ) !== -1;
		}

		var items = angular.copy( appData.data.items );
		var convertibleItems = [];

		$scope.pendingAction = false;

		$scope.data = {
				editMode: $stateParams.editMode || 'auto',
				conversionDatepicker: { format: 'yyyy-MM-dd HH:mm:ss', opened: false },
				sourceItems: $filter( 'itemsWithProps' )( items, ['Pack','Unpack','Conversion'] ),
				targetItems: $filter( 'itemsWithProps' )( items, ['Pack','Unpack','Conversion'] ),
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

				/* We allow negative inventory
				if( $scope.conversionItem.source_quantity > $scope.data.sourceInventory.quantity )
				{
					$scope.data.valid_conversion = false;
					$scope.data.messages.push( 'Insufficient inventory for input item to convert.' );
				}
				*/

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

		$scope.calculateInput = function()
			{
				var factor = $scope.data.factor;

				switch( $scope.data.mode )
				{
					case 'pack':
						$scope.conversionItem.source_quantity = $scope.conversionItem.target_quantity * factor;
						break;

					case 'unpack':
						$scope.conversionItem.source_quantity = $scope.conversionItem.target_quantity / factor;
						break;

					case 'convert':
						$scope.conversionItem.source_quantity = $scope.conversionItem.target_quantity;
						break;
				}

				$scope.checkConversion();
			};


		// Conversion form events
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
						$scope.data.output.step = 1;
						$scope.data.output.min = 1;

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
							$scope.data.output.step = $scope.data.factor;
							$scope.data.output.min = $scope.data.factor;
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
				var prevOutputItem = $scope.data.targetInventory;

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
					var prevItem = $filter( 'filter' )( $scope.data.targetItems, { id: prevOutputItem.id }, true );
					if(  prevItem.length == 1 )
					{
						$scope.data.targetInventory = prevItem[0];
					}
					else
					{
						$scope.data.targetInventory = $scope.data.targetItems[0];
					}
					$scope.conversionItem.target_inventory_id = $scope.data.targetInventory.id;
				}
				else
				{
					$scope.data.targetInventory = null;
					$scope.conversionItem.target_inventory_id = null;
				}

				$scope.onOutputItemChange();
			};


		// Conversion actions
		$scope.saveConversion = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.conversionItem.save().then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'conversions' );
							notifications.alert( 'Conversion record saved', 'success' );
							$state.go( 'main.store', { activeTab: 'conversions' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							console.error( reason );
							$scope.pendingAction = false;
						});
				}
			};

		$scope.approveConversion = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.conversionItem.save( 'approve' ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'conversions' );
							notifications.alert( 'Item converted successfully', 'success' );
							$state.go( 'main.store', { activeTab: 'conversions' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							console.error( reason );
							$scope.pendingAction = false;
						});
				}
			};


		// Initialize conversion form
		if( $stateParams.conversionItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getConversion( $stateParams.conversionItem.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						// Load conversion record
						$scope.conversionItem = Conversion.createFromData( response.data );

						// Set source inventory
						$scope.data.sourceInventory = $filter( 'filter' )( appData.data.items, { id: $stateParams.conversionItem.source_inventory_id }, true )[0];

						// Set target inventory
						$scope.data.targetInventory = $filter( 'filter' )( appData.data.items, { id: $stateParams.conversionItem.target_inventory_id }, true )[0];

						if( $scope.data.editMode == 'auto' )
						{
							switch( $scope.conversionItem.conversion_status )
							{
								case 1: // CONVERSION_PENDING
									$scope.data.editMode = 'edit';
									break;

								case 2: // CONVERSION_APPROVED
									$scope.data.editMode = 'view';
									break;

								case 3: // CONVERSION_CANCELLED
									$scope.data.editMode = 'view';
									break;

								default:
									$scope.data.editMode = 'view';
							}
						}

						if( !$scope.checkPermissions( 'conversions', 'edit' ) || ( $scope.data.editMode != 'view' && $scope.conversionItem.conversion_status != 1 ) ) // CONVERSION_PENDING
						{
							$scope.data.editMode = 'view';
						}

						// Initiate conversion factor
						$scope.onInputItemChange();
					}
				}	);
		}
		else
		{
			$scope.conversionItem = new Conversion();
			$scope.onInputItemChange();
		}
	}
]);

app.controller( 'MoppingController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'packingData', 'UserServices', 'Collection', 'CollectionItem',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, packingData, UserServices, Collection, CollectionItem )
	{
		$scope.pendingAction = false;

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
				moppedItems: $filter( 'itemsWithProps' )( angular.copy( appData.data.items ), ['TktCollect','TktIssue'] ),
				packAsItems: packingData,
				editMode: $stateParams.editMode || 'new'
			};

		// Add Inventory from source of mopped item
		$scope.data.moppedSource.push({
				id: 0,
				station_name: 'Inventory',
				station_short_name: 'INV'
			});

		$scope.input = {
				rowId: null,
				moppedSource: $scope.data.moppedSource[0] || null,
				moppedItem: $scope.data.moppedItems[0],
				moppedQuantity: 0,
				packAs: null,
				processor: null,
				deliveryPerson: null
			};

		$scope.findUser = UserServices.findUser;

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

		// Collection form events
		$scope.onChangePullOutShift = function()
			{
				$scope.moppingItem.cashier_shift_id = $scope.data.selectedPullOutShift.id;

				var date = angular.copy( $scope.moppingItem.processing_datetime );

				// Only suggest if collection record is new
				if( !$scope.moppingItem.id )
				{
					if( $scope.data.selectedPullOutShift.id == 8 )
					{
						$scope.moppingItem.business_date = date;
						$scope.moppingItem.business_date.setDate( $scope.moppingItem.business_date.getDate() - 1 );
					}
					else
					{
						$scope.moppingItem.business_date = date;
					}
				}
			};


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
							items[i].markedVoid = item.markedVoid;
							//items[i].mopping_item_status = item.mopping_item_status;
						}
					}
				}
			};


		// Collection item actions
		$scope.addMoppingItem = function( event )
			{
				if( ( event.type == 'keypress' ) && ( event.keyCode == 13 ) )
				{
					if( ! $scope.input.deliveryPerson )
					{
						notifications.alert( 'Please specify delivery person', 'warning' );
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
								converted_to_name: ( $scope.input.packAs && $scope.input.packAs.id ) ? $scope.input.packAs.item_name : null,
								processor_name: $scope.input.processor ? $scope.input.processor.full_name : null,
								valid_item: false,

								mopped_station_id: parseInt( $scope.input.moppedSource.id ),
								mopped_item_id: parseInt( $scope.input.moppedItem.item_id ),
								mopped_quantity: parseInt( $scope.input.moppedQuantity ),
								converted_to: ( $scope.input.packAs && $scope.input.packAs.id ) ? $scope.input.packAs.target_item_id : null,
								group_id: null,
								processor_id: $scope.input.processor ? $scope.input.processor.id : null,
								delivery_person: deliveryPerson,
								mopped_item_status: 1 // MOPPING_ITEM_COLLECTED
							};

						$scope.moppingItem.addItem( new CollectionItem( data ) );
					}
				}
			};

		$scope.removeMoppingItem = function( item )
			{
				$scope.moppingItem.removeItem( item )
			};

		// Collection actions
		$scope.saveCollection = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.moppingItem.save().then(
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
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		// Initialize controller

		// Load moppingItem
		if( $stateParams.moppingItem )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getCollection( $stateParams.moppingItem.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.moppingItem = Collection.createFromData( response.data );

						if( $scope.data.editMode != 'view' && !$scope.checkPermissions( 'collections', 'edit' ) )
						{
							$scope.data.editMode = 'view';
						}

						// Set pullout shift
						$scope.data.selectedPullOutShift = $filter( 'filter')( $scope.data.pullOutShifts, { id: $scope.moppingItem.cashier_shift_id }, true )[0];

						$scope.onItemChange();
					}
					else
					{
						console.error( response.errorMsg );
					}
				},
				function( reason )
				{
					console.error( reason );
				});
		}
		else
		{
			$scope.moppingItem = new Collection();
			$scope.onItemChange();
			$scope.onChangePullOutShift();
		}
	}
]);

app.controller( 'AllocationController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'assigneeShifts', 'Allocation', 'AllocationItem', 'AllocationSalesItem',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, assigneeShifts, Allocation, AllocationItem, AllocationSalesItem )
	{
		/*
		function category_filter( value, index, array )
		{
			var result = true;
			var assigneeType = $scope.data.selectedAssigneeType;
			var phase = $scope.data.allocationPhase;
			var status = $scope.allocationItem ? $scope.allocationItem.allocation_status : 1; // ALLOCATION_SCHEDULED
			var preAllocationCategories = [ 'Initial Allocation', 'Magazine Load', 'Initial Change Fund', 'Coin Replenishment', 'Coin Acceptor Replenishment' ];
			var postAllocationCategories = [ 'Additional Allocation', 'Magazine Load', 'Additional Change Fund', 'Coin Replenishment', 'Coin Acceptor Replenishment' ];

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

				case 'ticket_sales':
					if( ! value.is_ticket_sales_category )
						return false;
					break;

				case 'sales':
					break;

				default:
					return false;
			}

			return true;
		}
		*/

		function category_filter( value, index, array )
		{
			var result = true;
			var assigneeType = $scope.data.selectedAssigneeType;
			var phase = $scope.data.allocationPhase;
			var status = $scope.allocationItem ? $scope.allocationItem.allocation_status : 1; // ALLOCATION_SCHEDULED
			var preAllocationCategories = [ 'InitAlloc', 'TVMAlloc', 'InitCFund', 'HopAlloc', 'CAAlloc' ];
			var postAllocationCategories = [ 'AddAlloc', 'TVMAlloc', 'AddCFund', 'HopAlloc', 'CAAlloc' ];
			/*
			var preAllocationCategories, postAllocationCategories;
			switch( assigneeType )
			{
				case 1: // Station Teller
					preAllocationCategories = [ 'InitAlloc', 'InitCFund' ];
					postAllocationCategories = [ 'AddAlloc', 'AddCFund' ];
					break;

				case 2: // TVM
					preAllocationCategories = [ 'TVMAlloc', 'HopAlloc', 'CAAlloc' ];
					postAllocationCategories = [ 'TVMAlloc', 'HopAlloc', 'CAAlloc' ];
					break;
			}
			*/

			switch( assigneeType.id )
			{
				case 1: // Station Teller
					if( value.cat_teller != true ) return false;
					break;

				case 2: // TVM
					if( value.cat_machine != true ) return false;
					break;

				default:
					return false;
			}

			switch( phase )
			{
				case 'allocation':
					if( value.cat_module != 'Allocation' ) return false;
					switch( status )
					{
						case 1: // ALLOCATION_SCHEDULED
							if( preAllocationCategories.indexOf( value.cat_name ) == -1 )	return false;
							break;

						default:
							if( postAllocationCategories.indexOf( value.cat_name ) == -1 ) return false;
					}
					break;

				case 'remittance':
					if( value.cat_module != 'Remittance' ) return false;
					break;

				case 'ticket_sales':
					if( value.cat_module != 'Sales' ) return false;
					break;

				case 'sales':
				default:
					return false;
			}

			return true;
		}

		$scope.pendingAction = false;

		$scope.data = {
				title: 'Allocation Information',
				editMode: $stateParams.editMode || 'auto',
				businessDatepicker: { format: 'yyyy-MM-dd', opened: false },
				assigneeShifts: angular.copy( assigneeShifts ),
				selectedAssigneeShift: null,
				assigneeTypes: angular.copy( appData.data.assigneeTypes ),
				selectedAssigneeType: { id: 1, typeName: 'Station Teller' },
				tvms: angular.copy( appData.data.tvms ),
				selectedTVM: angular.copy( appData.data.tvms[0] ),
				tempAssignee: null,
				inventoryItems: angular.copy( appData.data.items ),
				salesItems: angular.copy( appData.data.salesItems ),
				selectedItem: null,
				categories: angular.copy( appData.data.categories ),
				allocationPhase: 'allocation',
				activeTab: 0,
				saveButton: { icon: 'time', label: 'Schedule' },

				assigneeLabel: 'Teller Name',
				assigneeShiftLabel: 'Teller Shift',
				remittancesTabLabel: 'Remittances',
				remittancesEmptyText: 'No ticket remittance items',
				cashRemittancesEmptyText: 'No cash remittance items'
			};

		$scope.input = {
				category: null,
				item: $scope.data.inventoryItems[0] || null,
				salesItem: $scope.data.salesItems[0] || null,
				itemReservedQuantity: 0,
				quantity: null,
			};

		$scope.showDatePicker = function()
			{
				$scope.data.businessDatepicker.opened = true;
			};

		$scope.updateSaveButton = function()
			{
				if( $scope.allocationItem )
				{
					switch( $scope.allocationItem.allocation_status )
					{
						case 1: // ALLOCATION_SCHEDULED
							if( $scope.allocationItem.assignee_type == 1 ) // ALLOCATION_ASSIGNEE_TELLER
							{
								$scope.data.saveButton = { icon: 'time', label: 'Schedule' };
							}
							else if( $scope.allocationItem.assignee_type == 2 ) // ALLOCATION_ASSIGNEE_MACHINE
							{
								if( $scope.data.allocationPhase == 'remittance' )
								{
									$scope.data.saveButton = { icon: 'floppy-disk', label: 'Save' };
								}
								else
								{
									$scope.data.saveButton = { icon: 'time', label: 'Schedule' };
								}
							}
							break;

						case 2: // ALLOCATION_ALLOCATED
							$scope.data.saveButton = { icon: 'floppy-disk', label: 'Update' };
							break;

						case 3: // ALLOCATION_REMITTED
							$scope.data.saveButton = { icon: 'floppy-disk', label: 'Update' };
							break;

						case 4: // ALLOCATION_CANCELLED
							$scope.data.saveButton = { icon: 'floppy-disk', label: 'Update' };
							break;

						default:
							$scope.data.saveButton = { icon: 'time', label: 'Save' };
					}
				}
			}

		$scope.updatePhase = function( phase )
			{
				$scope.data.allocationPhase = phase;
				$scope.updateAllocatableItems();
				$scope.updateSaveButton();
			}

		$scope.updateCategories = function()
			{
				$scope.data.categories = $filter( 'filter' )( $scope.input.item.categories, category_filter, true );
				if( $scope.data.categories.length )
				{
					$scope.input.category = $scope.data.categories[0];
				}
			};

		$scope.updateAllocatableItems = function()
			{
				var filter = {};
				var categoryFilter, assigneeFilter = {};
				var assignee = $scope.data.selectedAssigneeType.id == 2 ? 'machine' : 'teller';
				var allocationStatus = $scope.allocationItem ? $scope.allocationItem.allocation_status : 1;

				if( $scope.data.allocationPhase == 'allocation' )
				{
					if( assignee == 'teller' )
					{
						if( allocationStatus == 1 )
						{
							categoryFilter = ['InitAlloc', 'InitCFund'];
						}
						else
						{
							categoryFilter = ['AddAlloc', 'AddCFund'];
						}
					}
					else
					{
						categoryFilter = ['TVMAlloc', 'HopAlloc', 'CAAlloc'];
					}
					assigneeFilter[assignee + '_allocatable'] = true;
				}
				else if( $scope.data.allocationPhase == 'remittance' )
				{
					if( assignee == 'teller' )
					{
						categoryFilter = ['Unsold', 'RemFreeExt', 'Expired', 'CodeRed', 'Unconfirmd', 'TCERF', 'SalesColl', 'CFundRet'];
					}
					else
					{
						categoryFilter = ['Unsold', 'RejectBin', 'SalesColl', 'HopPullout'];
					}
					assigneeFilter[assignee + '_remittable'] = true;
				}
				else if( $scope.data.allocationPhase == 'ticket_sales' )
				{
					if( assignee == 'teller' )
					{
						categoryFilter = ['TktSales', 'CSCIssue', 'SalePdExt', 'SaleFrExt', 'SaleUncnfrm'];
					}
					else
					{
						categoryFilter = [];
					}
					assigneeFilter[assignee + '_saleable'] = true;
				}
				else if( $scope.data.allocationPhase == 'sales' )
				{
					// do nothing
				}

				if( $scope.data.allocationPhase != 'sales' )
				{
					//$scope.data.inventoryItems = $filter( 'filter' )( appData.data.items, filter, true );
					$scope.data.inventoryItems = $filter( 'itemsWithProps' )( appData.data.items, categoryFilter );
					$scope.data.inventoryItems = $filter( 'filter' )( $scope.data.inventoryItems, assigneeFilter, true );

					if( $scope.data.inventoryItems.length )
					{
						$scope.input.item = $scope.data.inventoryItems[0];
					}

					$scope.updateCategories();
				}
			};

		$scope.getItemQuantities = function()
			{

				switch( $scope.data.allocationPhase )
				{
					case 'allocation':
						var items, n;
						switch( $scope.input.item.item_class )
						{
							case 'ticket':
								items = $scope.allocationItem.allocations;
								break;

							case 'cash':
								items = $scope.allocationItem.cash_allocations;
								break;
						}
						n = items.length;
						$scope.input.itemReservedQuantity = 0;

						for( var i = 0; i < n; i++ )
						{
							if( items[i].allocated_item_id == $scope.input.item.item_id
								&& items[i].allocation_item_status == 10 // ALLOCATION_ITEM_SCHEDULED
								&& !items[i].id )
							{
								$scope.input.itemReservedQuantity += parseInt( items[i].allocated_quantity );
							}
							else if( items[i].allocated_item_id == $scope.input.item.item_id
								&& items[i].markedVoid )
							{
								$scope.input.itemReservedQuantity -= parseInt( items[i].allocated_quantity );
							}
						}
						break;

					case 'remittance':
						$scope.input.itemReservedQuantity = 0;
						break;
				}
			};

		// Allocation form events
		$scope.onAssigneeTypeChange = function()
			{
				if( $scope.data.selectedAssigneeType.id == 1 )
				{ // Station teller
					$scope.data.assigneeShifts = $filter( 'filter' )( assigneeShifts, { store_type: 0 }, true );
					$scope.data.assigneeLabel = 'Teller Name';
					$scope.data.assigneeShiftLabel = 'Teller Shift';
					$scope.data.allocationsTabLabel = 'Allocations';
					$scope.data.remittancesTabLabel = 'Remittances';
					$scope.data.remittancesEmptyText = 'No remittance items';
					//$scope.data.activeTab = 0;
					if( $scope.allocationItem.allocation_status == 1 )
					{
						$scope.data.activeTab = 0;
					}
				}
				else if( $scope.data.selectedAssigneeType.id == 2 )
				{ // TVM
					$scope.data.assigneeShifts = $filter( 'filter' )( assigneeShifts, { store_type: 1 }, true );
					$scope.data.assigneeLabel = 'TVM #';
					$scope.data.assigneeShiftLabel = 'TVM Shift';
					$scope.data.allocationsTabLabel = 'Replenishments';
					$scope.data.remittancesTabLabel = 'Cash Collection/ Reject Bin';
					$scope.data.remittancesEmptyText = 'No reject or return items';

					$scope.allocationItem.assignee = $scope.data.selectedTVM.description;
				}
				else
				{
					$scope.data.assigneeShifts = assigneeShifts;
				}

				$scope.data.selectedAssignShift = $scope.data.assigneeShifts[0];

				if( $scope.data.assigneeShifts.length )
				{
					if( $scope.allocationItem.shift_id )
					{
						$scope.data.selectedAssigneeShift = $filter( 'filter')( assigneeShifts, { id: $scope.allocationItem.shift_id }, true )[0];
						if( ! $scope.data.selectedAssigneeShift )
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

				$scope.updateAllocatableItems();
			};

		$scope.onAssigneeShiftChange = function()
			{
				if( ! $scope.data.selectedAssigneeShift )
				{
					$scope.data.selectedAssigneeShift = $scope.data.assigneeShifts[0];
				}
				$scope.allocationItem.shift_id = $scope.data.selectedAssigneeShift.id;
			};

		$scope.onTVMChange = function()
			{
				$scope.allocationItem.assignee = $scope.data.selectedTVM.description;
			};

		$scope.onItemChange = function()
			{
				$scope.updateCategories();
				$scope.getItemQuantities();
			};


		// Allocation and remittance items actions
		$scope.addAllocationItem = function( event )
			{
				if( ( event.type == 'keypress' ) && ( event.keyCode == 13 ) )
				{
					if( $scope.data.allocationPhase == 'sales'
							&& $scope.input.salesItem
							&& $scope.input.quantity > 0 )
					{
						var data = {
								cashier_shift_num: session.data.currentShift.shift_num,
								slitem_name: $scope.input.salesItem.slitem_name,
								slitem_description: $scope.input.salesItem.slitem_description,
								slitem_group: $scope.input.salesItem.slitem_group,
								slitem_mode: $scope.input.salesItem.slitem_mode,

								alsale_allocation_id: $scope.allocationItem.id,
								alsale_shift_id: session.data.currentShift.id,
								alsale_sales_item_id: $scope.input.salesItem.id,
								alsale_remarks: $scope.input.remarks,
								alsale_amount: $scope.input.quantity
						}

						$scope.allocationItem.addSalesItem( new AllocationSalesItem( data ) );
					}
					else
					{
						if( $scope.input.category
								&& $scope.input.item
								&& $scope.input.quantity > 0 )
						{
							var data = {
									cashier_shift_num: session.data.currentShift.shift_num,
									cat_description: $scope.input.category.cat_description,
									item_name: $scope.input.item.item_name,
									item_class: $scope.input.item.item_class,

									iprice_currency: $scope.input.item.iprice_currency,
									iprice_unit_price: $scope.input.item.iprice_unit_price,

									cashier_shift_id: session.data.currentShift.id,
									allocated_item_id: $scope.input.item.item_id,
									allocated_quantity: $scope.input.quantity,
									allocation_category_id: $scope.input.category.id,
									allocation_datetime: new Date()
								};
							switch( $scope.data.allocationPhase )
							{
								case 'allocation':
									$scope.allocationItem.addAllocationItem( new AllocationItem( data, 'allocation' ) );
									break;

								case 'remittance':
									$scope.allocationItem.addRemittanceItem( new AllocationItem( data, 'remittance' ) );
									break;

								case 'ticket_sales':
									$scope.allocationItem.addTicketSaleItem( new AllocationItem( data, 'ticket_sale' ) );
									break;

								default:
									// do nothing
							}
						}
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
					case 'cash_allocation':
						if( itemRow.allocation_item_status == 10 ) // ALLOCATION_ITEM_SCHEDULED
						{ // remove only scheduled items
							$scope.allocationItem.removeAllocationItem( itemRow );
						}
						break;

					case 'remittance':
					case 'cash_remittance':
						if( itemRow.allocation_item_status == 20 ) // REMITTANCE_ITEM_PENDING
						{ // remove only pending items
							$scope.allocationItem.removeRemittanceItem( itemRow );
						}
						break;

					case 'ticket_sale':
						if( itemRow.allocation_item_status == 30 ) // TICKET_SALE_ITEM_PENDING
						{ // remove only pending items
							$scope.allocationItem.removeTicketSaleItem( itemRow );
						}
						break;
				}
				$scope.getItemQuantities();
			};

		$scope.removeCashReportItem = function( report )
			{
				$scope.allocationItem.removeCashReport( report )
				notifications.alert( 'Cash report deleted', 'success' );
			};

		$scope.removeSalesItem = function( itemRow )
			{
				$scope.allocationItem.removeSalesItem( itemRow );
			};


		// Allocation record actions
		$scope.saveAllocation = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.allocationItem.save( 'schedule' ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'allocations' );
							notifications.alert( 'Allocation record saved', 'success' );
							$state.go( 'main.store', { activeTab: 'allocations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.allocateAllocation = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.allocationItem.save( 'allocate' ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'allocations' );
							notifications.alert( 'Marked as allocated', 'success' );
							$state.go( 'main.store', { activeTab: 'allocations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};

		$scope.completeAllocation = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.allocationItem.save( 'complete' ).then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'allocations' );
							notifications.alert( 'Marked as remitted', 'success' );
							$state.go( 'main.store', { activeTab: 'allocations' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};


		// Initialize controller
		if( $stateParams.activeTab )
		{
			$scope.data.activeTab = $stateParams.activeTab;
		}

		// Load allocation item
		if( $stateParams.allocationItem || $stateParams.allocationId )
		{
			var allocationId;
			if( $stateParams.allocationId )
			{
				allocationId = $stateParams.allocationId;
			}
			else if( $stateParams.allocationItem )
			{
				allocationId = $stateParams.allocationItem.id;
			}

			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getAllocation( allocationId ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.allocationItem = Allocation.createFromData( response.data );

						// Set selected assignee shift
						$scope.data.selectedAssigneeShift = $filter( 'filter')( assigneeShifts, { id: $scope.allocationItem.shift_id }, true )[0];

						// Set selected assignee type
						$scope.data.selectedAssigneeType = $filter( 'filter')( $scope.data.assigneeTypes, { id: $scope.allocationItem.assignee_type }, true )[0];

						if( $scope.data.editMode == 'auto' )
						{
							switch( $scope.allocationItem.allocation_status )
							{
								case 1: // ALLOCATION_SCHEDULED
									$scope.data.editMode = 'edit';
									break;

								case 2: // ALLOCATION_ALLOCATED
									$scope.data.editMode = 'edit';
									break;

								case 3: // ALLOCATION_REMITTED
									$scope.data.editMode = 'view';
									break;

								case 4: // ALLOCATION_CANCELLED
									$scope.data.editMode = 'view';
									break;

								default:
									$scope.data.editMode = 'view';
							}
						}

						if( !$scope.checkPermissions( 'allocations', 'edit' ) || ( $scope.data.editMode != 'view' && $scope.allocationItem.allocation_status > 2 ) )
						{
							$scope.data.editMode = 'view';
						}

						$scope.onAssigneeTypeChange();
						$scope.onAssigneeShiftChange();
						$scope.updateSaveButton();
						$scope.getItemQuantities();
						$scope.updateCategories();
					}
					else
					{
						console.error( 'Unable to load mopping collection record' );
					}

					$scope.data.title = 'Allocation Information #' + $scope.allocationItem.id;
				},
				function( reason )
				{
					console.error( reason );
				});
		}
		else
		{
			$scope.allocationItem = new Allocation();
			$scope.onAssigneeTypeChange();
			$scope.onAssigneeShiftChange();
			$scope.updateSaveButton();
			$scope.getItemQuantities();
			$scope.updateCategories();
		}
	}
]);

app.controller( 'TVMReadingController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'notifications', 'cashierShifts', 'UserServices', 'TVMReading', 'TVMReadingItem',
	function( $scope, $filter, $state, $stateParams, session, appData, notifications, cashierShifts,  UserServices, TVMReading, TVMReadingItem )
	{
		$scope.pendingAction = false;

		$scope.data = {
				editMode: $stateParams.editMode || 'auto',
				cashierShifts: angular.copy( cashierShifts ),
				selectedCashierShift: angular.copy( session.data.currentShift ),
				datepicker: { format: 'yyyy-MM-dd', opened: false },
				title: 'TVM Reading',
				tvms: angular.copy( appData.data.tvms ),
				selectedTVM: angular.copy( appData.data.tvms[0] )
			};

		$scope.findUser = UserServices.findUser;

		$scope.showDatePicker = function()
			{
				$scope.data.datepicker.opened = true;
			};

		$scope.onCashierChange = function()
			{
				$scope.TVMReading.set( 'tvmr_cashier_name', $scope.TVMReading.tvmr_cashier_name );
			};

		$scope.onTVMChange = function()
			{
				$scope.TVMReading.set( 'tvmr_machine_id', $scope.data.selectedTVM.description );
				$scope.loadPreviousReading();
			};

		$scope.onShiftChange = function()
			{
				$scope.TVMReading.set( 'tvmr_shift_id', $scope.data.selectedCashierShift.id );
				$scope.loadPreviousReading();
			};

		$scope.loadPreviousReading = function()
			{
				/*
				if( $scope.TVMReading.tvmr_machine_id && $scope.TVMReading.tvmr_datetime && $scope.data.selectedCashierShift.id )
				{
					var previousShiftData = appData.getPreviousShift( $scope.TVMReading.tvmr_datetime, $scope.data.selectedCashierShift.id );
					appData.getTVMReadingLastReading( {
						machine: $scope.TVMReading.tvmr_machine_id,
						date: $filter( 'date' )( previousShiftData.date, 'yyyy-MM-dd' ),
						shift: previousShiftData.shift.id
					}).then(
						function( response )
						{
							$scope.TVMReading.previous_reading = new TVMReading( response.data );
						},
						function( reason )
						{
							console.error( reason );
						});
				}
				else
				{
					$scope.TVMReading.previous_reading = null;
				}
				*/
			};


		// TVM Reading record actions
		$scope.saveTVMReading = function()
			{
				if( !$scope.pendingAction )
				{
					$scope.pendingAction = true;
					$scope.TVMReading.save().then(
						function( response )
						{
							appData.refresh( session.data.currentStore.id, 'tvmReadings' );
							notifications.alert( 'TVM readings saved', 'success' );
							$state.go( 'main.store', { activeTab: 'tvmReadings' } );
							$scope.pendingAction = false;
						},
						function( reason )
						{
							$scope.pendingAction = false;
						});
				}
			};


		// Initialize controller

		// Load TVM reading item
		if( $stateParams.TVMReading )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getTVMReading( $stateParams.TVMReading.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.TVMReading = TVMReading.createFromData( response.data );
						$scope.data.selectedTVM = $filter( 'filter' )( $scope.data.tvms, { description: $scope.TVMReading.tvmr_machine_id }, true )[0];
						if( !$scope.checkPermissions( 'allocations', 'edit' ) && ( $scope.data.editMode != 'view'  ) )
						{
							$scope.data.editMode = 'view';
						}
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
		else
		{
			$scope.TVMReading = new TVMReading( { tmvr_machine_ID: $scope.data.tvms[0].id } );
			$scope.onTVMChange();
			$scope.onShiftChange();
		}
	}
]);

app.controller( 'ShiftDetailCashReportController', [ '$scope', '$filter', '$state', '$stateParams', 'session', 'appData', 'utilities', 'notifications', 'UserServices', 'ShiftDetailCashReport', 'ShiftDetailCashReportItem',
	function( $scope, $filter, $state, $stateParams, session, appData, utilities, notifications, UserServices, ShiftDetailCashReport, ShiftDetailCashReportItem )
	{
		$scope.pendingAction = false;

		$scope.data = {
				editMode: $stateParams.editMode || 'auto',
				datepicker: { format: 'yyyy-MM-dd', opened: false },
				title: 'Shift Detail Cash Report',
				cardProfiles: angular.copy( appData.data.cardProfiles ),
			};

		$scope.default_input = {
				card_profile: $scope.data.cardProfiles[0] || null,
				issued_quantity: null,
				issued_amount: null,
				add_value_quantity: null,
				add_value_amount: null,
				refund_quantity: null,
				refund_amount: null,
				entry_exit_mismatch_quantity: null,
				entry_exit_mismatch_amount: null,
				excess_time_quantity: null,
				excess_time_amount: null,
				product_sales_quantity: null,
				product_sales_amount: null
			};

		$scope.input = {};

		$scope.emptyItems = function( items )
			{
				return angular.equals( items, {} );
			};

		$scope.resetInput = function()
			{
				var selectedCardProfile = $scope.input.card_profile || null;

				$scope.input = angular.copy( $scope.default_input );
				if( selectedCardProfile )
				{
					$scope.input.card_profile = selectedCardProfile;
				}
				else
				{
					$scope.input.card_profile = $scope.data.cardProfiles[0] || null;
				}
			};

		$scope.resetInput();

		$scope.addReportItem = function( event )
			{
				if( ( event.type == 'keypress' ) && ( event.keyCode == 13 ) )
				{
					$scope.shiftDetailCashReport.addItem( $scope.input );
					$scope.resetInput();
				}
			};

		$scope.removeReportItem = function( row )
			{
				$scope.shiftDetailCashReport.removeItem( row );
			};

		$scope.saveReport = function()
			{
				if( ! $scope.pendingAction )
					{
						$scope.pendingAction = true;
						$scope.shiftDetailCashReport.save().then(
							function( response )
							{
								appData.refresh( session.data.currentStore.id, 'shiftDetailCashReports' );
								notifications.alert( 'Shift Detail Cash Report saved', 'success' );
								if( $scope.shiftDetailCashReport.sdcr_allocation_id )
								{
									$state.go( 'main.allocation', { allocationId: $scope.shiftDetailCashReport.sdcr_allocation_id, editMode: 'auto', activeTab: 4 } );
								}
								else
								{
									$state.go( 'main.store', { activeTab: 'shiftDetailCashReports' } );
								}
								$scope.pendingAction = false;
							},
							function( reason )
							{
								$scope.pendingAction = false;
							});
					}
			};

		// Load Shift Detail Cash Report
		if( $stateParams.shiftDetailCashReport )
		{
			$scope.data.editMode = $stateParams.editMode || 'view';
			appData.getShiftDetailCashReport( $stateParams.shiftDetailCashReport.id ).then(
				function( response )
				{
					if( response.status == 'ok' )
					{
						$scope.shiftDetailCashReport = ShiftDetailCashReport.createFromData( response.data );
						angular.forEach( $scope.shiftDetailCashReport.items, function( item, key ) {
								var index = utilities.findWithAttr( appData.data.cardProfiles, 'id', parseInt( key ) );
								if( index >= 0 )
								{
									$scope.shiftDetailCashReport.items[key].card_profile = appData.data.cardProfiles[index];
								}
							} );

						if( !$scope.checkPermissions( 'allocations', 'edit' ) && ( $scope.data.editMode != 'view'  ) )
						{
							$scope.data.editMode = 'view';
						}
					}
					else
					{
						console.error( 'Unable to load shift detail cash report record' );
					}
				},
				function( reason )
				{
					console.error( reason );
				});
		}
		else
		{
			var allocation;
			if( $stateParams.allocation )
			{
				allocation = {
						sdcr_allocation_id: $stateParams.allocation.id,
						sdcr_business_date: $stateParams.allocation.business_date,
						sdcr_login_time: $stateParams.allocation.business_date,
						sdcr_logout_time: $stateParams.allocation.business_date,
					};
			}
			$scope.shiftDetailCashReport = new ShiftDetailCashReport( allocation );
			console.log( $scope.shiftDetailCashReport );
		}
	}
]);