angular.module( 'coreModels' ).factory( 'TVMReading', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var tvmr_store_id;
		var tvmr_machine_id;
    var tvmr_datetime;
		var tvmr_shift_id;
		var tvmr_cashier_id;
		var tvmr_last_reading;


		/**
		 * Constructor
		 */
		function TVMReading( data )
		{
			this.loadData( data );
		}


		TVMReading.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var tvmReadingsArray = [];

					for( var i = 0; i < n; i++ )
					{
						tvmReadingsArray.push( new TVMReading( data[i] ) );
					}

					return tvmReadingsArray;
				}
				else if( data.constructor == Object )
				{
					return new TVMReading( data );
				}
			};


		TVMReading.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.tvmr_store_id = session.data.currentStore.id;
				me.tvmr_machine_id = null;
				me.tvmr_datetime = new Date();
				me.tvmr_shift_id = null;
				me.tvmr_cashier_id = null;
				me.tvmr_last_reading = true;

				if( data )
				{
					angular.merge( me, data );

					if( me.tvmr_datetime )
					{
						me.tvmr_datetime = Date.parse( me.tvmr_datetime );
					}
				}
			};


		TVMReading.prototype.get = function( field )
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


		TVMReading.prototype.canEdit = function()
			{
				return session.data.currentStore.store_type == 4 && session.checkPermissions( 'allocations', 'edit' );
			};


		TVMReading.prototype.canCancel = function( showAction )
			{
				return session.data.currentStore.store_type == 4 && session.checkPermissions( 'allocations', 'edit' );
			};


		TVMReading.prototype.checkTVMReading = function( action )
			{
				return true;
			};


		TVMReading.prototype.prepareTVMReadingData = function()
			{
				var data = {
						id: this.id,
						tvmr_store_id: this.tvmr_store_id,
						tvmr_machine_id: this.tvmr_machine_id,
						tvmr_datetime: this.tvmr_datetime ? $filter( 'date' )( this.tvmr_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
						tvmr_shift_id: this.tvmr_shift_id,
						tvmr_cashier_id: this.tvmr_cashier_id,
						tvmr_last_reading: this.tvmr_last_reading,
					};

				return data;
			};


		TVMReading.prototype.save = function( status )
			{
				var me = this;
				var deferred = $q.defer();

				if( this.checkTVMReading( status ) )
				{
					var tvmReadingData = this.prepareTVMReadingData();

					var url = baseUrl + 'index.php/api/v1/tvm_readings/';
					switch( status )
					{
						case 'cancel':
							url += 'cancel';
							break;

						default:
							// do nothing
					}

					$http({
						method: 'POST',
						url: url,
						data: tvmReadingData
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
					deferred.reject( 'Failed TVM reading data check' );
				}

				return deferred.promise;
			};


		return TVMReading;
	}
]);

angular.module( 'coreModels' ).factory( 'TVMReadingItem', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'TVMReading',
	function( $http, $q, $filter, baseUrl, session, notifications, TVMReading )
	{
		var id;
		var tvmri_reading_id;
		var tvmri_name;
    var tvmri_quantity;
		var tvmri_amount;


		/**
		 * Constructor
		 */
		function TVMReadingItem( data )
		{
			this.loadData( data );
		}


		TVMReadingItem.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.tvmri_reading_id = null;
				me.tvmri_name = null;
				me.tvmri_quantity = null;
				me.tvmri_amount = null;

				if( data )
				{
					angular.merge( me, data );
				}
			};


		TVMReadingItem.prototype.get = function( field )
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

		return TVMReadingItem;
	}
]);