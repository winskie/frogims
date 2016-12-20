angular.module( 'coreModels' ).factory( 'TransferValidation', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var transval_transfer_id;
		var transval_receipt_status;
		var transval_receipt_datetime;
		var transval_receipt_sweeper;
		var transval_receipt_user_id;
		var transval_receipt_shift_id;
		var transval_transfer_status;
		var transval_transfer_datetime;
		var transval_transfer_sweeper;
		var transval_transfer_user_id;
		var transval_transfer_shift_id;
		var transval_status;

		var transferValidationStatus = {
				'1': 'Ongoing',
				'2': 'Completed',
				'3': 'Not Required'
			};
		var	transferValidationReceiptStatus = {
				'1': 'Validated',
				'2': 'Returned'
			};
		var	transferValidationTransferStatus = {
				'1': 'Validated',
				'2': 'Disputed'
			};


		/**
		 * Constructor
		 */
		function TransferValidation( data )
		{
			this.loadData( data );
		};


		TransferValidation.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var transferValidationsArray = [];

					for( var i = 0; i < n; i++ )
					{
						transferValidationsArray.push( new TransferValidation( data[i] ) );
					}

					return transferValidationsArray;
				}
				else if( data.constructor == Object )
				{
					return new TransferValidation( data );
				}
			};


		TransferValidation.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.transval_transfer_id = null;
				me.transval_receipt_status = null;
				me.transval_receipt_datetime = null;
				me.transval_receipt_sweeper = null;
				me.transval_receipt_user_id = null;
				me.transval_receipt_shift_id = null;
				me.transval_transfer_status = null;
				me.transval_transfer_datetime = null;
				me.transval_transfer_sweeper = null;
				me.transval_transfer_user_id = null;
				me.transval_transfer_shift_id = null;
				me.transval_status = null;

				if( data )
				{
					angular.merge( me, data );

					if( me.adjustment_timestamp )
					{
						me.adjustment_timestamp = Date.parse( me.adjustment_timestamp );
					}

					if( me.transval_receipt_datetime )
					{
						me.transval_receipt_datetime = Date.parse( me.transval_receipt_datetime );
					}

					if( me.transval_transfer_datetime )
					{
						me.transval_transfer_datetime = Date.parse( me.transval_transfer_datetime );
					}
				}
			};


		TransferValidation.prototype.get = function( field )
			{
				switch( field )
				{
					case 'validationStatus':
						return transferValidationStatus[this.transval_status.toString()];

					case 'receiptStatus':
						return transferValidationReceiptStatus[this.transval_receipt_status.toString()];

					case 'transferStatus':
						return transferValidationTransferStatus[this.transval_transfer_status.toString()];

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


		TransferValidation.prototype.set = function( field, value )
			{
				switch( field )
				{
					case 'transval_receipt_sweeper':
						if( value.constructor == Object )
						{
							this.transval_receipt_sweeper = value.full_name ? value.full_name : null;
						}
						else if( value.constructor == String )
						{
							this.transval_receipt_sweeper = value;
						}
						break;

					case 'transval_transfer_sweeper':
						if( value.constructor == Object )
						{
							this.transval_transfer_sweeper = value.full_name ? value.full_name : null;
						}
						else if( value.constructor == String )
						{
							this.transval_transfer_sweeper = value;
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
			}


		TransferValidation.prototype.checkValidation = function( action )
			{
				return true;
			};


		TransferValidation.prototype.prepareValidationData = function( action )
			{
				var me = this;
				var data = {
						id: me.id,
						transval_transfer_id: me.transval_transfer_id,
						transval_status: me.transval_status
					};

				switch( action )
				{
					case 'validate_receipt':
						data.transval_receipt_status = me.transval_receipt_status;
						data.transval_receipt_datetime = me.transval_receipt_datetime ? $filter( 'date' )( me.transval_receipt_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null;
						data.transval_receipt_sweeper = me.transval_receipt_sweeper;
						data.transval_receipt_user_id = me.transval_receipt_user_id;
						data.transval_receipt_shift_id = me.transval_receipt_shift_id;
						break;

					case 'validate_transfer':
						data.transval_transfer_status = me.transval_transfer_status;
						data.transval_transfer_datetime = me.transval_transfer_datetime ? $filter( 'date' )( me.transval_transfer_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null;
						data.transval_transfer_sweeper = me.transval_transfer_sweeper;
						data.transval_transfer_user_id = me.ransval_transfer_user_id;
						data.transval_transfer_shift_id = me.transval_transfer_shift_id;
				}

				return data;
			};


		TransferValidation.prototype.save = function( action )
			{
				var me = this;
				var deferred = $q.defer();
				if( this.checkValidation( action ) )
				{
					var validationData = this.prepareValidationData( action );

					var validationUrl = baseUrl + 'index.php/api/v1/transfer_validations/';
					switch( action )
					{
						case 'validate_receipt':
							validationUrl += 'validate_receipt';
							break;

						case 'returned':
							validationUrl += 'returned';
							break;

						case 'validate_transfer':
							validationUrl += 'validate_transfer';
							break;

						case 'dispute':
							validationUrl += 'dispute';
							break;

						case 'complete':
							validationUrl += 'complete';
							break;

						case 'ongoing':
							validationUrl += 'ongoing';
							break;

						case 'not_required':
							validationUrl += 'not_required';
							break;

						default:
							// do nothing
					}

					$http({
						method: 'POST',
						url: validationUrl,
						data: validationData
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
					deferred.reject( 'Failed transfer data check' );
				}

				return deferred.promise;
			};


		return TransferValidation;
	}
]);