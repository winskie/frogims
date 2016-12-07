angular.module( 'coreModels' ).factory( 'Adjustment', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var store_inventory_id;
		var adjustment_shift;
		var adjustment_type;
		var adjusted_quantity;
		var previous_quantity;
		var reason;
		var adjustment_timestamp;
		var adjustment_status;
		var user_id;
		var adj_transaction_type;
		var adj_transaction_id;

		var adjustmentStatus = {
				'1': 'Pending',
				'2': 'Approved',
				'3': 'Cancelled'
			};


		/**
		 * Constructor
		 */
		function Adjustment( data )
		{
			this.loadData( data );
		};


		Adjustment.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var adjustmentsArray = [];

					for( var i = 0; i < n; i++ )
					{
						adjustmentsArray.push( new Adjustment( data[i] ) );
					}

					return adjustmentsArray;
				}
				else if( data.constructor == Object )
				{
					return new Adjustment( data );
				}
			};


		Adjustment.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.store_inventory_id = null;
				me.adjustment_shift = null;
				me.adjustment_type = 1;
				me.adjusted_quantity = null;
				me.previous_quantity = null;
				me.reason = null;
				me.adjustment_timestamp = new Date();
				me.adjustment_status = 1;
				me.user_id = session.data.currentUser.id;
				me.adj_transaction_type = null;
				me.adj_transaction_id = null;

				if( data )
				{
					angular.merge( me, data );

					if( me.adjustment_timestamp )
					{
						me.adjustment_timestamp = Date.parse( me.adjustment_timestamp );
					}
				}
			};


		Adjustment.prototype.get = function( field )
			{
				switch( field )
				{
					case 'adjustmentStatus':
						return adjustmentStatus[this.adjustment_status.toString()];

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


		Adjustment.prototype.canEdit = function()
			{
				return this.adjustment_status == 1 && session.checkPermissions( 'adjustments', 'edit' );
			};


		Adjustment.prototype.canCancel = function( showAction )
			{
				return this.id && this.adjustment_status == 1 && session.checkPermissions( 'adjustments', 'edit' );
			};


		Adjustment.prototype.canApprove = function( showAction )
			{
				return this.adjustment_status == 1 && session.checkPermissions( 'adjustments', 'approve' );
			};


		Adjustment.prototype.checkAdjustment = function( action )
			{
				if( ! this.reason )
				{
					notifications.alert( 'Please specify reason for adjustment', 'warning' );
					return false;
				}

				return true;
			};


		Adjustment.prototype.prepareAdjustmentData = function()
			{
				var data = {
						id: this.id,
						store_inventory_id: this.store_inventory_id,
						adjustment_shift: this.adjustment_shift,
						adjustment_type: this.adjustment_type,
						adjusted_quantity: this.adjusted_quantity,
						previous_quantity: this.previous_quantity,
						reason: this.reason,
						adjustment_timestamp: this.adjustment_timestamp ? $filter( 'date' )( this.adjustment_timestamp, 'yyyy-MM-dd HH:mm:ss' ) : null,
						adjustment_status: this.adjustment_status,
						user_id: session.data.currentUser.id,
						adj_transaction_type: this.adj_transaction_type,
						adj_transaction_id: this.adj_transaction_id
					};

				return data;
			};


		Adjustment.prototype.save = function( status )
			{
				var me = this;
				var deferred = $q.defer();

				if( this.checkAdjustment( status ) )
				{
					var adjustmentData = this.prepareAdjustmentData();

					var adjustmentUrl = baseUrl + 'index.php/api/v1/adjustments/';
					switch( status )
					{
						case 'approve':
							adjustmentUrl += 'approve';
							break;

						case 'cancel':
							adjustmentUrl += 'cancel';
							break;

						default:
							// do nothing
					}

					$http({
						method: 'POST',
						url: adjustmentUrl,
						data: adjustmentData
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
					deferred.reject( 'Failed adjustment data check' );
				}

				return deferred.promise;
			};


		return Adjustment;
	}
]);