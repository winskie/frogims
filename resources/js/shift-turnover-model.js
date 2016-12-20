angular.module( 'coreModels' ).factory( 'ShiftTurnover', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'ShiftTurnoverItem',
	function( $http, $q, $filter, baseUrl, session, notifications, ShiftTurnoverItem )
	{
		var id;
		var st_store_id;
		var st_from_date;
		var st_from_shift_id;
		var st_to_date;
		var st_to_shift_id;
		var st_start_user_id;
		var st_end_user_id;
		var st_remarks;
		var st_status;

    var items;

		var shiftTurnoverStatus = {
				'1': 'Open',
				'2': 'Closed'
			};

		/**
		 * Constructor
		 */
		function ShiftTurnover( data )
		{
			this.loadData( data );
		};


		ShiftTurnover.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var shiftTurnoversArray = [];

					for( var i = 0; i < n; i++ )
					{
						shiftTurnoversArray.push( new ShiftTurnover( data[i] ) );
					}

					return shiftTurnoversArray;
				}
				else if( data.constructor == Object )
				{
					return new ShiftTurnover( data );
				}
			};


		ShiftTurnover.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.st_store_id = session.data.currentStore.id;
				me.st_from_date = new Date();
				me.st_from_shift_id = session.data.currentShift.id;
				me.st_to_date = new Date();
				me.st_to_shift_id = session.data.currentShift.id;
				me.st_start_user_id = session.data.currentUser.id;
				me.st_end_user_id = null;
				me.st_remarks = null;
				me.st_status = null;

				me.items = [];

				if( data )
				{
					var items = [];
					if( data.items )
					{
						angular.copy( data.items, items );
						delete data.items;
					}

					angular.merge( me, data );

					if( me.st_from_date )
					{
						me.st_from_date = Date.parse( me.st_from_date );
					}

					if( me.st_to_date )
					{
						me.st_to_date = Date.parse( me.st_to_date );
					}

					// Shift turnover items
					if( items )
					{
						var n = items.length;
						for( var i = 0; i < n; i++ )
						{
							me.items.push( new ShiftTurnoverItem( items[i] ) );
						}
					}
				}
			};


		ShiftTurnover.prototype.get = function( field )
			{
				switch( field )
				{
					case 'shiftTurnoverStatus':
						if( this.st_status == null )
						{
							return '---';
						}
						else
						{
							return shiftTurnoverStatus[this.st_status.toString()];
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


		ShiftTurnover.prototype.canOpen = function( showAction )
			{
				return session.checkPermissions( 'shiftTurnovers', 'edit' )
								&& this.st_store_id == session.data.currentStore.id
								&& ( showAction || this.st_status == null || this.st_status == 1 );
			};


		ShiftTurnover.prototype.isCurrent = function()
			{
				return $filter( 'date' )( this.st_from_date, 'yyyy-MM-dd' ) == $filter( 'date' )( new Date(), 'yyyy-MM-dd' )
								&& this.st_from_shift_id == session.data.currentShift.id
								&& this.st_store_id == session.data.currentStore.id;
			};


		ShiftTurnover.prototype.canEdit = function( showAction )
			{
				return session.checkPermissions( 'shiftTurnovers', 'edit' )
								&& this.st_store_id == session.data.currentStore.id
								&& ( showAction || this.st_status == 1 );
			};


		ShiftTurnover.prototype.canClose = function( showAction )
			{
				return session.checkPermissions( 'shiftTurnovers', 'edit' )
								&& this.st_store_id == session.data.currentStore.id
								&& ( showAction || this.st_status == 1 );
			};


		ShiftTurnover.prototype.checkTurnover = function( action )
			{
				return true;
			};


		ShiftTurnover.prototype.prepareTurnoverData = function()
			{
				var data = {
						id: this.id,
						st_store_id: this.st_store_id,
						st_from_date: this.st_from_date ? $filter( 'date' )( this.st_from_date, 'yyyy-MM-dd' ) : null,
						st_from_shift_id: this.st_from_shift_id,
						st_to_date: this.st_to_date ? $filter( 'date' )( this.st_to_date, 'yyyy-MM-dd' ) : null,
						st_to_shift_id: this.st_to_shift_id,
						st_start_user_id: this.st_start_user_id,
						st_end_user_id: this.st_end_user_id,
						st_remarks: this.st_remarks,
						st_status: this.st_status,

						items: []
					};

				var items = this.items;
				var n = items.length;

				for( var i = 0; i < n; i++ )
				{
					var itemData = {
							id: items[i].id,
							sti_turnover_id: items[i].sti_turnover_id,
							sti_item_id: items[i].sti_item_id,
							sti_inventory_id: items[i].sti_inventory_id,
							sti_beginning_balance: items[i].sti_beginning_balance,
							sti_ending_balance: items[i].sti_ending_balance
						};

					data.items.push( itemData );
				}

				return data;
			};


		ShiftTurnover.prototype.save = function( action )
			{
				var me = this;
				var deferred = $q.defer();

				if( this.checkTurnover( action ) )
				{
					var turnoverData = this.prepareTurnoverData();
					var turnoverUrl = baseUrl + 'index.php/api/v1/shift_turnovers/' + action;

					$http({
						method: 'POST',
						url: turnoverUrl,
						data: turnoverData
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
					deferred.reject( 'Failed shift turnover data check' );
				}

				return deferred.promise;
			};


		return ShiftTurnover;
	}
]);

angular.module( 'coreModels' ).factory( 'ShiftTurnoverItem', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;

		var sti_turnover_id;
		var sti_item_id;
		var sti_inventory_id;
		var sti_beginning_balance;
		var sti_ending_balance;

		var movement;
		var previous_balance;

		/**
		 * Constructor
		 */
		function ShiftTurnoverItem( data )
		{
			this.loadData( data );
		}


		ShiftTurnoverItem.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.sti_turnover_id = null;
				me.sti_item_id = null;
				me.sti_inventory_id = null;
				me.sti_beginning_balance = null;
				me.sti_ending_balance = null;

				if( data )
				{
					angular.merge( me, data );
				}
			};


		return ShiftTurnoverItem;
	}
]);