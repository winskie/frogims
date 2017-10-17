angular.module( 'coreModels' ).factory( 'TVMReading', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'TVMReadingItem',
	function( $http, $q, $filter, baseUrl, session, notifications, TVMReadingItem )
	{
		var id;
		var tvmr_store_id;
		var tvmr_machine_id;
		var tvmr_datetime;
		var tvmr_shift_id;
		var tvmr_cashier_id;
		var tvmr_cashier_name;
		var tvmr_last_reading;

		var magazine_sjt_reading;
		var magazine_svc_reading;
		var coin_box_reading;
		var note_box_reading;
		var hopper_php5_reading;
		var hopper_php1_reading;

		var other_readings;
		var previous_reading;


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
				var readingsMap = {
						'magazine_sjt': 'magazine_sjt_reading',
						'magazine_svc': 'magazine_svc_reading',
						'coin_box': 'coin_box_reading',
						'note_box': 'note_box_reading',
						'hopper_php5': 'hopper_php5_reading',
						'hopper_php1': 'hopper_php1_reading',
					};

				me.id = null;
				me.tvmr_store_id = session.data.currentStore.id;
				me.tvmr_machine_id = null;
				me.tvmr_datetime = new Date();
				me.tvmr_shift_id = null;
				me.tvmr_cashier_id = null;
				me.tvmr_cashier_name = null;
				me.tvmr_cashier_name = null;
				me.tvmr_last_reading = true;

				me.magazine_sjt_reading = new TVMReadingItem( {	tvmri_name: 'magazine_sjt' } );
				me.magazine_svc_reading = new TVMReadingItem( {	tvmri_name: 'magazine_svc' } );
				me.coin_box_reading = new TVMReadingItem( {	tvmri_name: 'coin_box' } );
				me.note_box_reading = new TVMReadingItem( {	tvmri_name: 'note_box' } );
				me.hopper_php5_reading = new TVMReadingItem( {	tvmri_name: 'hopper_php5' } );
				me.hopper_php1_reading = new TVMReadingItem( {	tvmri_name: 'hopper_php1' } );

				me.other_readings = [];
				me.previous_reading = null;

				if( data )
				{
					var readingItems = [];
					var previousReading;

					if( data.readings )
					{
						angular.copy( data.readings, readingItems );
						delete data.readings;
					}

					if( data.previous_reading )
					{ // Load previous reading
						previousReading = new TVMReading( data.previous_reading );
						delete data.previous_reading;
					}

					angular.merge( me, data );

					if( me.tvmr_datetime )
					{
						me.tvmr_datetime = Date.parse( me.tvmr_datetime );
					}

					if( readingItems )
					{
						var n = readingItems.length;
						for( var i = 0; i < n; i++ )
						{
							if( readingsMap[readingItems[i].tvmri_name] != undefined )
							{ // Specific readings
								me[readingsMap[readingItems[i].tvmri_name]] = new TVMReadingItem( readingItems[i] );
							}
							else
							{ // Other readings
								me.other_readings.push( new TVMReadingItem( readingItems[i] ) );
							}
						}
					}

					if( previousReading )
					{
						me.previous_reading = previousReading;
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


		TVMReading.prototype.set = function( field, value )
			{
				switch( field )
				{
					case 'tvmr_cashier_name':
						if( value.constructor == Object )
						{
							this.tvmr_cashier_id = ( value.id ? value.id : null );
							this.tvmr_cashier_name = ( value.full_name ? value.full_name : null );
						}
						else if( value.constructor == String )
						{
							this.tvmr_cashier_id = null;
							this.tvmr_cashier_name = value;
						}
						break;

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


		TVMReading.prototype.canEdit = function()
			{
				return session.data.currentStore.store_type == 4 && session.checkPermissions( 'allocations', 'edit' );
			};


		TVMReading.prototype.canRemove = function()
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
						tvmr_cashier_name: this.tvmr_cashier_name,
						tvmr_last_reading: this.tvmr_last_reading,

						readings: [],
					};

				data.readings.push( this.magazine_sjt_reading.toArray() );
				data.readings.push( this.magazine_svc_reading.toArray() );
				data.readings.push( this.coin_box_reading.toArray() );
				data.readings.push( this.note_box_reading.toArray() );
				data.readings.push( this.hopper_php5_reading.toArray() );
				data.readings.push( this.hopper_php1_reading.toArray() );

				var otherReadings = this.other_readings;
				var n = otherReadings.length;

				for( var i = 0; i < n; i++ )
				{
					data.readings.push( otherReadings[i].toArray() );
				}

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
						case 'remove':
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


		TVMReading.prototype.remove = function()
			{
				var me = this;
				var deferred = $q.defer();
				var url = baseUrl + 'index.php/api/v1/tvm_readings/';

				$http({
					method: 'DELETE',
					url: url + me.id
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


		return TVMReading;
	}
]);

angular.module( 'coreModels' ).factory( 'TVMReadingItem', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var tvmri_reading_id;
		var tvmri_name;
		var tvmri_reference_num;
    	var tvmri_quantity;


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
				me.tvmri_reference_num = null;
				me.tvmri_quantity = null;

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


		TVMReadingItem.prototype.toArray = function()
			{
				var me = this;
				var arrayData = {
					id: me.id,
					tvmri_reading_id: me.tvmri_reading_id,
					tvmri_name: me.tvmri_name,
					tvmri_reference_num: me.tvmri_reference_num,
					tvmri_quantity: me.tvmri_quantity,
				};

				return arrayData;
			};

		return TVMReadingItem;
	}
]);