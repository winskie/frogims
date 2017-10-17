angular.module( 'coreModels' ).factory( 'ShiftDetailCashReport', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'ShiftDetailCashReportItem',
	function( $http, $q, $filter, baseUrl, session, notifications, ShiftDetailCashReportItem )
	{
		var id;
		var sdcr_allocation_id;
		var sdcr_store_id;
		var sdcr_shift_id;
		var sdcr_teller_id;
		var sdcr_pos_id;
		var sdcr_business_date;
		var sdcr_login_time;
		var sdcr_logout_time;

		var issued_property;
		var add_value_property;
		var refund_property;
		var entry_exit_mismatch_property;
		var excess_time_property;
		var product_sales_property;

		var items;

		/**
		 * Constructor
		 */
		function ShiftDetailCashReport( data )
		{
			this.loadData( data );
		}

		ShiftDetailCashReport.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var reportsArray = [];

					for( var i = 0; i < n; i++ )
					{
						reportsArray.push( new ShiftDetailCashReport( data[i] ) );
					}

					return reportsArray;
				}
				else if( data.constructor == Object )
				{
					return new ShiftDetailCashReport( data );
				}
			};


		ShiftDetailCashReport.prototype.loadData = function( data )
			{
				var me = this;
				var itemProperties = {
					'issued': 'issued',
					'add_value': 'add_value',
					'refund': 'refund',
					'nxmismatch': 'entry_exit_mismatch',
					'xcess_time': 'excess_time',
					'prod_sales': 'product_sales'
				};

				me.sdcr_allocation_id = null;
				me.sdcr_store_id = session.data.currentStore.id;
				me.sdcr_shift_id = session.data.currentShift.id;
				me.sdcr_teller_id = null;
				me.sdcr_pos_id = null;
				me.sdcr_business_date = new Date();
				me.sdcr_login_time = new Date();
				me.sdcr_logout_time = new Date();

				me.items = {};

				if( data )
				{
					var items = [];

					if( data.report_items )
					{
						angular.copy( data.report_items, items );
						delete data.report_items;
					}

					angular.merge( me, data );

					if( me.sdcr_business_date )
					{
						me.sdcr_business_date = new Date( me.sdcr_business_date );
					}

					if( me.sdcr_login_time )
					{
						me.sdcr_login_time = new Date( me.sdcr_login_time );
					}

					if( me.sdcr_logout_time )
					{
						me.sdcr_logout_time = new Date( me.sdcr_logout_time );
					}

					if( items )
					{
						var n = items.length;
						for( var i = 0; i < n; i++ )
						{
							var card_profile_id = items[i].sdcri_card_profile_id;
							if( ! me.items[card_profile_id] )
							{
								me.items[card_profile_id] = {};
								for( var prop in itemProperties )
								{
									me.items[card_profile_id].card_profile_id = card_profile_id;
									if( ! me.items[card_profile_id][itemProperties[prop] + '_property'] )
									{
										me.items[card_profile_id][itemProperties[prop] + '_property'] = new ShiftDetailCashReportItem( { 'sdcri_property': prop, 'sdcri_card_profile_id': card_profile_id } );
									}
								}
							}
							switch( items[i].sdcri_property )
							{
								case 'issued':
									me.items[card_profile_id].issued_property = new ShiftDetailCashReportItem( items[i] );
									break;

								case 'add_value':
									me.items[card_profile_id].add_value_property = new ShiftDetailCashReportItem( items[i] );
									break;

								case 'refund':
									me.items[card_profile_id].refund_property = new ShiftDetailCashReportItem( items[i] );
									break;

								case 'nxmismatch':
									me.items[card_profile_id].entry_exit_mismatch_property = new ShiftDetailCashReportItem( items[i] );
									break;

								case 'xcess_time':
									me.items[card_profile_id].excess_time_property = new ShiftDetailCashReportItem( items[i] );
									break;

								case 'prod_sales':
									me.items[card_profile_id].product_sales_property = new ShiftDetailCashReportItem( items[i] );
									break;

								default:
									// discard
							}
						}
					}
				}
			};


		ShiftDetailCashReport.prototype.get = function( field )
			{
				switch( field )
				{
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

		ShiftDetailCashReport.prototype.set = function( field, value )
			{
				switch( field )
				{
					default:
						if( this.hasOwnProperty( field ) )
						{
							this[field] = value;
						}
						else
						{
							console.error( 'The property [' + field + '] does not exist!' );
							return false;
						}
				}

				return true;
			};


		ShiftDetailCashReport.prototype.canEdit = function()
			{
				return session.data.currentStore.store_type == 4 && session.checkPermissions( 'allocations', 'edit' );
			};


		ShiftDetailCashReport.prototype.canRemove = function( showAction )
			{
				return session.data.currentStore.store_type == 4 && session.checkPermissions( 'allocations', 'edit' );
			};


		ShiftDetailCashReport.prototype.addItem = function( item )
			{
				var me = this;

				var itemProperties = {
					'issued': 'issued',
					'add_value': 'add_value',
					'refund': 'refund',
					'nxmismatch': 'entry_exit_mismatch',
					'xcess_time': 'excess_time',
					'prod_sales': 'product_sales'
				};

				for( var prop in itemProperties )
				{
					var card_profile_id = item.card_profile.id;
					if( ! me.items[card_profile_id] )
					{
						me.items[card_profile_id] = {};
					}

					me.items[card_profile_id].card_profile = item.card_profile;

					if( ! me.items[card_profile_id][itemProperties[prop] + '_property'] )
					{
						me.items[card_profile_id][itemProperties[prop] + '_property'] = new ShiftDetailCashReportItem( { 'sdcri_property': prop, 'sdcri_card_profile_id': card_profile_id } );
					}

					me.items[card_profile_id][itemProperties[prop] + '_property'].sdcri_quantity = item[itemProperties[prop] + '_quantity'];
					me.items[card_profile_id][itemProperties[prop] + '_property'].sdcri_amount = item[itemProperties[prop] + '_amount'];
				}
			};


		ShiftDetailCashReport.prototype.removeItem = function( itemKey )
			{
				var me = this;
				var item = me.items[itemKey];

				if( item.id == undefined )
				{
					delete me.items[itemKey];
				}
				else
				{
					me.items[itemKey].markedVoid = true;
				}
			};


		ShiftDetailCashReport.prototype.checkCashReport = function( action )
			{
				return true;
			};


		ShiftDetailCashReport.prototype.prepareCashReport = function()
			{
				var me = this;
				var data = {
						id: this.id,
						sdcr_allocation_id: this.sdcr_allocation_id,
						sdcr_store_id: this.sdcr_store_id,
						sdcr_shift_id: this.sdcr_shift_id,
						sdcr_teller_id: this.sdcr_teller_id,
						sdcr_pos_id: this.sdcr_pos_id,
						sdcr_business_date: this.sdcr_business_date ? $filter( 'date' )( this.sdcr_business_date, 'yyyy-MM-dd' ) : null,
						sdcr_login_time: this.sdcr_login_time ? $filter( 'date' )( this.sdcr_login_time, 'yyyy-MM-dd HH:mm:ss' ) : null,
						sdcr_logout_time: this.sdcr_logout_time ? $filter( 'date' )( this.sdcr_logout_time, 'yyyy-MM-dd HH:mm:ss' ) : null,

						items: [],
					}

				var items = me.items;
				var n = items.length;
				var properties = ['issued', 'add_value', 'refund', 'entry_exit_mismatch', 'excess_time', 'product_sales'];
				angular.forEach( items, function( item, key )
					{
						for( var i = 0; i < properties.length; i++ )
						{
							var property = properties[i] + '_property';
							if( item[property].sdcri_quantity || item[property].sdcri_amount )
							{
								if( item.markedVoid )
								{
									item[property].markedVoid = true;
								}
								data.items.push( item[property].toArray() );
							}
						}
					} );


				return data;
			};


		ShiftDetailCashReport.prototype.save = function()
			{
				var me = this;
				var deferred = $q.defer();

				if( this.checkCashReport() )
				{
					var cashReportData = this.prepareCashReport();
					var url = baseUrl + 'index.php/api/v1/shift_detail_cash_report/';

					$http({
						method: 'POST',
						url: url,
						data: cashReportData
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
					deferred.reject( 'Failed shift detail cash report data check' );
				}

				return deferred.promise;
			};


			ShiftDetailCashReport.prototype.remove = function()
			{
				var me = this;
				var deferred = $q.defer();
				var url = baseUrl + 'index.php/api/v1/shift_detail_cash_report/';

				$http({
					method: 'DELETE',
					url: url + me.id,
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
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						deferred.reject( reason );
					}	);

				return deferred.promise;
			};


		return ShiftDetailCashReport;
	}
]);

angular.module( 'coreModels' ).factory( 'ShiftDetailCashReportItem', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var sdcri_sdcr_id;
		var sdcri_card_profile_id;
		var sdcri_property;
		var sdcri_quantity;
		var sdcri_amount;


		/**
		 * Constructor
		 */
		function ShiftDetailCashReportItem( data )
		{
			this.loadData( data );
		}


		ShiftDetailCashReportItem.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.sdcri_sdcr_id = null;
				me.sdcri_card_profile_id = null;
				me.sdcri_property = null;
				me.sdcri_quantity = null;
				me.sdcri_amount = null;

				if( data )
				{
					angular.merge( me, data );
				}
			};


		ShiftDetailCashReportItem.prototype.get = function( field )
			{
				switch( field )
				{
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


		ShiftDetailCashReportItem.prototype.void = function( status )
			{
				if( status == undefined )
				{
					return this.markedVoid;
				}
				else if( status == true || status == false )
				{
					this.markedVoid = status;
				}
			};


		ShiftDetailCashReportItem.prototype.toArray = function()
			{
				var me = this;
				var arrayData = {
					id: me.id,
					sdcri_sdcr_id: me.sdcri_sdcr_id,
					sdcri_card_profile_id: me.sdcri_card_profile_id,
					sdcri_property: me.sdcri_property,
					sdcri_quantity: me.sdcri_quantity,
					sdcri_amount: me.sdcri_amount,
				};

				if( me.markedVoid )
				{
					arrayData.marked_void = true;
				}

				return arrayData;
			};

		return ShiftDetailCashReportItem;
	}
]);