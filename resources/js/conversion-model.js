angular.module( 'coreModels' ).factory( 'Conversion', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var store_id;
		var conversion_datetime;
    var conversion_shift;
		var source_inventory_id;
		var target_inventory_id;
		var source_quantity;
		var target_quantity;
		var remarks;
    var conversion_status;

		var conversionStatus = {
				'1': 'Pending',
				'2': 'Approved',
				'3': 'Cancelled'
			};

		var conversionData = false;


		/**
		 * Constructor
		 */
		function Conversion( data )
		{
			this.loadData( data );
		}


		Conversion.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var conversionsArray = [];

					for( var i = 0; i < n; i++ )
					{
						conversionsArray.push( new Conversion( data[i] ) );
					}

					return conversionsArray;
				}
				else if( data.constructor == Object )
				{
					return new Conversion( data );
				}
			};


		Conversion.loadConversionData = function( inputItemId, outputItemId )
			{
				var me = this;
				var deferred = $q.defer();

				if( me.conversionData )
				{
					if( inputItemId && outputItemId )
					{
						deferred.resolve( me.conversionData[inputItemId + '_' + outputItemId] );
					}
					else
					{
						deferred.resolve( me.conversionData );
					}
				}
				else
				{
					// Fetch conversion data
					$http({
						method: 'GET',
						url: baseUrl + 'index.php/api/v1/conversion_factors/',
					}).then(
						function( response )
						{
							if( response.data.status == 'ok' )
							{
								var cItems = response.data.data;
								var n = cItems.length;

								me.conversionData = [];
								for( var i = 0; i < n; i++ )
								{
									me.conversionData[cItems[i].source_item_id + '_' + cItems[i].target_item_id] = { mode: 'pack', factor: ( 1 / cItems[i].conversion_factor ) };
									me.conversionData[cItems[i].target_item_id + '_' + cItems[i].source_item_id] = { mode: 'unpack', factor: cItems[i].conversion_factor };
								}

								if( inputItemId && outputItemId )
								{
									deferred.resolve( me.conversionData[ inputItemId + '_' + outputItemId] );
								}
								else
								{
									deferred.resolve( me.conversionData );
								}
							}
							else
							{
								me.conversionData = false;
								deferred.reject( response.data.errorMsg );
							}
						},
						function( reason )
						{
							deferred.reject( reason );
						}
					);
				}

				return deferred.promise;
			};


		Conversion.getConversionData = function( inputItemId, outputItemId )
			{
				return this.conversionData[inputItemId + '_' + outputItemId];
			};


		Conversion.convert = function( inputItemId, outputItemId, inputQuantity )
			{
				var me = this;
				var conversionData = me.conversionData[inputItemId + '_' + outputItemId];
				if( conversionData )
				{
					return conversionData.factor * inputQuantity;
				}
				else
				{
					return false;
				}
			};


		Conversion.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.store_id = session.data.currentStore.id;
				me.conversion_datetime = new Date();
				me.conversion_shift = null;
				me.source_inventory_id = null;
				me.target_inventory_id = null;
				me.source_quantity = null;
				me.target_quantity = null;
				me.remarks = null;
				me.conversion_status = 1;

				if( data )
				{
					angular.merge( me, data );

					if( me.conversion_datetime )
					{
						me.conversion_datetime = Date.parse( me.conversion_datetime );
					}
				}
			};


		Conversion.prototype.get = function( field )
			{
				switch( field )
				{
					case 'conversionStatus':
						return conversionStatus[this.conversion_status.toString()];

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


		Conversion.prototype.canEdit = function()
			{
				return this.conversion_status == 1 && session.checkPermissions( 'conversions', 'edit' );
			};


		Conversion.prototype.canCancel = function( showAction )
			{
				return this.conversion_status == 1 && session.checkPermissions( 'conversions', 'edit' );
			};


		Conversion.prototype.canApprove = function( showAction )
			{
				return this.conversion_status == 1 && session.checkPermissions( 'conversions', 'approve' );
			};


		Conversion.prototype.checkConversion = function( action )
			{
				return true;
			};


		Conversion.prototype.prepareConversionData = function()
			{
				var data = {
						id: this.id,
						store_id: this.store_id,
						conversion_datetime: this.conversion_datetime ? $filter( 'date' )( this.conversion_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
						conversion_shift: this.conversion_shift,
						source_inventory_id: this.source_inventory_id,
						target_inventory_id: this.target_inventory_id,
						source_quantity: this.source_quantity,
						target_quantity: this.target_quantity,
						remarks: this.remarks,
						conversion_status: this.conversion_status
					};

				return data;
			};


		Conversion.prototype.save = function( status )
			{
				var me = this;
				var deferred = $q.defer();

				if( this.checkConversion( status ) )
				{
					var conversionData = this.prepareConversionData();

					var conversionUrl = baseUrl + 'index.php/api/v1/conversions/';
					switch( status )
					{
						case 'approve':
							conversionUrl += 'approve';
							break;

						case 'cancel':
							conversionUrl += 'cancel';
							break;

						default:
							// do nothing
					}

					$http({
						method: 'POST',
						url: conversionUrl,
						data: conversionData
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
					deferred.reject( 'Failed conversion data check' );
				}

				return deferred.promise;
			};


		return Conversion;
	}
]);