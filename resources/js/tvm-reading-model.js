angular.module( 'coreModels' ).factory( 'TVMReading', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var tvmr_store_id;
		var tvmr_machine_id;
		var tvmr_date;
		var tvmr_time;
		var tvmr_shift_id;
		var tvmr_cashier_id;
		var tvmr_cashier_name;
		var tvmr_type;
		var tvmr_reference_num;
		var tvmr_reading;
		var tvmr_previous_reading;

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

				me.id = null;
				me.tvmr_store_id = session.data.currentStore.id;
				me.tvmr_machine_id = null;
				me.tvmr_date = new Date();
				me.tvmr_time = new Date();
				me.tvmr_shift_id = null;
				me.tvmr_cashier_id = null;
				me.tvmr_cashier_name = null;
				me.tvmr_cashier_name = null;
				me.tvmr_type = null;
				me.tvmr_reference_num = null;
				me.tvmr_reading = 0;
				me.tvmr_previous_reading = null;

				me.previous_reading = null;

				if( data )
				{
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

					if( me.tvmr_date )
					{
						me.tvmr_date = new Date( me.tvmr_date );
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
						tvmr_date: this.tvmr_date ? $filter( 'date' )( this.tvmr_date, 'yyyy-MM-dd' ) : null,
						tvmr_time: this.tvmr_time ? $filter( 'date' )( this.tvmr_time, 'HH:mm:ss' ) : null,
						tvmr_shift_id: this.tvmr_shift_id,
						tvmr_cashier_id: this.tvmr_cashier_id,
						tvmr_cashier_name: this.tvmr_cashier_name,
						tvmr_type: this.tvmr_type,
						tvmr_reference_num: this.tvmr_reference_num,
						tvmr_reading: this.tvmr_reading,
						tvmr_previous_reading: this.tvmr_previous_reading
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