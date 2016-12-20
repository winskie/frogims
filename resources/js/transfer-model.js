angular.module( 'coreModels' ).factory( 'Transfer', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'TransferItem', 'TransferValidation',
	function( $http, $q, $filter, baseUrl, session, notifications, TransferItem, TransferValidation )
	{
		var id;
		var transfer_reference_num;
		var transfer_category;
		var origin_id;
		var origin_name;
		var sender_id;
		var sender_name;
		var sender_shift;
		var transfer_datetime;
		var transfer_user_id;
		var destination_id;
		var destination_name;
		var recipient_id;
		var recipient_name;
		var recipient_shift;
		var receipt_datetime;
		var receipt_user_id;
		var transfer_status;

		var items;

		var transfer_validation;

		var transferStatus = {
				'1': 'Scheduled',
				'2': 'Approved',
				'3': 'Received',
				'4': 'Cancelled',
				'5': 'Cancelled'
			};

		var receiptStatus = {
				'1': 'Scheduled',
				'2': 'Pending Receipt',
				'3': 'Received',
				'4': 'Cancelled',
				'5': 'Cancelled'
			};

		var transferCategories = {
				'1': 'External',
				'2': 'Regular',
				'3': 'Ticket Turnover',
				'4': 'Stock Replenishment',
				'5': 'Cashroom to Cashroom',
				'6': 'Blackbox Receipt'
			};

		var transferValidationReceiptStatus = {
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
		function Transfer( data, mode )
		{
			this.loadData( data );
			if( mode )
			{
				this.setMode( mode );
			}
		};


		Transfer.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var transfersArray = [];

					for( var i = 0; i < n; i++ )
					{
						transfersArray.push( new Transfer( data[i] ) );
					}

					return transfersArray;
				}
				else if( data.constructor == Object )
				{
					return new Transfer( data );
				}
			};

		Transfer.getById = function( transferId, includes )
			{
				var me = this;
				var deferred = $q.defer();

				if( includes )
				{
					if( typeof includes == 'string' )
					{
						includes = [ includes ];
					}
					includes = includes.join(',');
				}

				$http({
					method: 'GET',
					url: baseUrl + 'index.php/api/v1/transfers/' + transferId,
					params: {
						include: includes
					}
				}).then(
					function( response )
					{
						if( response.data.status == 'ok' )
						{
							deferred.resolve( new Transfer( response.data ) );
						}
						else
						{
							notifications.showMessages( response.data.errorMsg );
							deferred.reject( response.data.errorMsg );
						}
					},
					function( reason )
					{
						console.error( reason.data.errorMsg );
						deferred.reject( reason.data.errorMsg );
					});

				return deferred.promise;
			};


		Transfer.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.transfer_reference_num = null;
				me.transfer_category = null;
				me.origin_id = null;
				me.origin_name = null;
				me.sender_id = null;
				me.sender_name = null;
				me.sender_shift = null;
				me.transfer_datetime = null;
				me.transfer_user_id = null;
				me.destination_id = null;
				me.destination_name = null;
				me.recipient_id = null;
				me.recipient_name = null;
				me.recipient_shift = null;
				me.receipt_datetime = null;
				me.receipt_user_id = null;
				me.transfer_status = 1;
				me.items = [];
				me.transfer_validation = null;

				if( data )
				{
					var transferItems = [];
					if( data.items )
					{
						angular.copy( data.items, transferItems );
						delete data.items;
					}

					// Load transfer validation
					if( data.transfer_validation )
					{
						me.transfer_validation = TransferValidation.createFromData( data.transfer_validation );
						delete data.transfer_validation;
					}

					angular.merge( me, data );

					if( me.transfer_datetime )
					{
						me.transfer_datetime = Date.parse( me.transfer_datetime );
					}

					if( me.receipt_datetime )
					{
						me.receipt_datetime = Date.parse( me.receipt_datetime );
					}

					// Transfer items
					if( transferItems )
					{
						var n = transferItems.length;
						for( var i = 0; i < n; i++ )
						{
							me.items.push( new TransferItem( transferItems[i] ) );
						}
					}
				}
			};


		Transfer.prototype.setMode = function( mode )
			{
				switch( mode )
				{
					case 'transfer':
					case 'externalTransfer':
						if( !this.transfer_datetime )
						{
							this.transfer_datetime = new Date();
						}

						if( !this.origin_id && !this.origin_name )
						{
							this.setOrigin( session.data.currentStore );
						}
						break;

					case 'receipt':
					case 'externalReceipt':
						if( mode == 'externalReceipt' && !this.transfer_datetime )
						{
							this.transfer_datetime = new Date();
						}

						if( !this.receipt_datetime )
						{
							this.receipt_datetime = new Date();
						}
						if( !this.destination_id && !this.destination_name )
						{
							this.setDestination( session.data.currentStore );
						}
						if( !this.recipient_id && !this.recipient_name )
						{
							this.setRecipient( session.data.currentUser );
						}
						break;
				}
			};


		Transfer.prototype.get = function( field )
			{
				switch( field )
				{
					case 'transferStatusName':
						return transferStatus[this.transfer_status.toString()];

					case 'receiptStatusName':
						return receiptStatus[this.transfer_status.toString()];

					case 'transferCategoryName':
						return transferCategories[this.transfer_category.toString()];

					case 'transferDate':
						if( this.transfer_status == 1 )
						{ // Pending
							return $filter( 'date' )( this.transfer_datetime, 'yyyy-MM-dd' );
						}
						else
						{
							return $filter( 'date' )( this.transfer_datetime, 'yyyy-MM-dd HH:mm:ss' );
						}

					case 'receiptDate':
						if( this.transfer_status == 3 )
						{ // Received
							return $filter( 'date' )( this.receipt_datetime, 'yyyy-MM-dd HH:mm:ss' );
						}
						else
						{
							return 'Pending receipt';
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


		Transfer.prototype.set = function( field, value )
			{
				switch( field )
				{
					case 'recipient_name':
						if( value.constructor == Object )
						{
							this.recipient_id = ( value.id ? value.id : null );
							this.recipient_name = ( value.full_name ? value.full_name : null );
						}
						else if( value.constructor == String )
						{
							this.recipient_id = null;
							this.recipient_name = value;
						}
						break;

					case 'sender_name':
						if( value.constructor == Object )
						{
							this.sender_id = ( value.id ? value.id : null );
							this.sender_name = ( value.full_name ? value.full_name : null );
						}
						else if( value.constructor == String )
						{
							this.sender_id = null;
							this.sender_name = value;
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


		Transfer.prototype.isExternal = function()
			{
				return ( this.origin_id == null && this.origin_name != null ) || ( this.destination_id == null && this.destination_name != null );
			};


		Transfer.prototype.canEdit = function()
			{
				return this.transfer_status == 1 && session.checkPermissions( 'transfers', 'edit' )
							 && this.origin_id == session.data.currentStore.id;
			};


		Transfer.prototype.canApprove = function( showAction )
			{
				return this.transfer_status == 1
							 && session.checkPermissions( 'transfers', 'approve' )
							 && ( showAction || this.destination_id || this.destination_name )
							 && ( showAction || this.items.length > 0 );
			};


		Transfer.prototype.canCancel = function()
			{
				return ( ( this.transfer_status == 1 && session.checkPermissions( 'transfers', 'edit' ) )
						   || ( this.transfer_status == 2 && session.checkPermissions( 'transfers', 'approve' ) ) )
							 && this.origin_id == session.data.currentStore.id;
			};


		Transfer.prototype.canReceive = function( showAction )
			{
				return ( ( ( this.origin_id == null && this.transfer_status == 1 ) || this.transfer_status == 2 ) && this.destination_id != null )
							 && session.checkPermissions( 'transfers', 'edit' )
							 && this.destination_id == session.data.currentStore.id
							 && ( showAction || this.items.length > 0 );
			};

		// Transfer validation actions
		Transfer.prototype.canValidateReceipt = function( showAction )
			{
				return session.checkPermissions( 'transferValidations', 'edit' )
							 && this.transfer_validation
							 && this.transfer_validation.transval_receipt_status != 1 // TRANSFER_VALIDATION_RECEIPT_VALIDATED
							 && this.transfer_validation.transval_status != 3 // TRANSFER_VALIDATION_NOT_REQUIRED
							 && this.transfer_status != 1 // TRANSFER_PENDING
							 && this.transfer_status != 4 // TRANSFER_PENDING_CANCELLED
							 && this.transfer_status != 5; // TRANSFER_APPROVED_CANCELLED
			};


		Transfer.prototype.canReturn = function( showAction )
			{
				return session.checkPermissions( 'transferValidations', 'edit' )
							 && this.transfer_validation
							 && this.transfer_validation.transval_receipt_status != 2 // TRANSFER_VALIDATION_RECEIPT_RETURNED
							 && this.transfer_validation.transval_transfer_status == null
							 && this.transfer_validation.transval_status != 3 // TRANSFER_VALIDATION_NOT_REQUIRED
							 && this.transfer_status != 1 // TRANSFER_PENDING
							 //&& this.transfer_status != 3 // TRANSFER_RECEIVED
							 && this.transfer_status != 4 // TRANSFER_PENDING_CANCELLED
							 && this.transfer_status != 5; // TRANSFER_APPROVED_CANCELLED
			};


		Transfer.prototype.canValidateTransfer = function( showAction )
			{
				return session.checkPermissions( 'transferValidations', 'edit' )
							 && this.transfer_validation
							 && this.transfer_validation.transval_transfer_status != 1 // TRANSFER_VALIDATION_TRANSFER_VALIDATED
							 && this.transfer_validation.transval_receipt_status == 1; // TRANSFER_VALIDATION_RECEIPT_VALIDATED
			};


		Transfer.prototype.canDispute = function( showAction )
			{
				return session.checkPermissions( 'transferValidations', 'edit' )
							 && this.transfer_validation
							 && this.transfer_validation.transval_transfer_status != 2 // TRANSFER_VALIDATION_TRANSFER_DISPUTED
							 && this.transfer_validation.transval_receipt_status == 1; // TRANSFER_VALIDATION_RECEIPT_VALIDATED
			};


		Transfer.prototype.canCompleteValidation = function( showAction )
			{
				return session.checkPermissions( 'transferValidations', 'complete' )
							 && this.transfer_validation
							 && this.transfer_status != 1 // TRANSFER_PENDING
							 && this.transfer_status != 4 // TRANSFER_PENDING_CANCELLED
							 && this.transfer_status != 5 // TRANSFER_APPROVED_CANCELLED
							 && this.transfer_validation.transval_status != 2 // TRANSFER_VALIDATION_COMPLETED
							 && this.transfer_validation.transval_status != 3 // TRANSFER_VALIDATION_NOT_REQUIRED
							 && ( showAction || ! ( this.transfer_validation.transval_receipt_status == 1 && this.transfer_validation.transval_transfer_status == 2 ) )
							 && ( showAction || this.transfer_validation.transval_status != null );
			};


		Transfer.prototype.canMarkValidationNotRequired = function( showAction )
			{
				return session.checkPermissions( 'transferValidations', 'complete' )
							 && ( ( this.transfer_validation && this.transfer_validation.transval_status != 3 ) // TRANSFER_VALIDATION_NOT_REQUIRED
							 || this.transfer_validation == null );
			};


		Transfer.prototype.canOpenValidation = function( showAction )
			{
				return session.checkPermissions( 'transferValidations', 'complete' )
							 && this.transfer_validation
							 && ( this.transfer_validation.transval_status != null && this.transfer_validation.transval_status != 1 );  // TRANSFER_VALIDATION_NOT_ONGOING
			};


		Transfer.prototype.getValidItems = function()
			{
				var n = this.items.length;
				var validItems = [];
				for( var i = 0; i < n; i++ )
				{
					var validItemStatus = [1, 2, 3];
					if( validItemStatus.indexOf( this.items[i].transfer_item_status ) != -1 && this.items[i].quantity > 0 && !this.items[i].markedVoid )
					{
						validItems.push( this.items[i] );
					}
				}

				return validItems;
			};


		Transfer.prototype.getValidation = function()
			{
				if( ! this.transfer_validation )
				{
					this.transfer_validation = new TransferValidation({
							transval_transfer_id: this.id
						});
				}

				return this.transfer_validation;
			};


		Transfer.prototype.setOrigin = function( origin )
			{
				this.origin_id = origin && origin.id ? origin.id : null;
				this.origin_name = origin && origin.store_name ? origin.store_name : null;
			};


		Transfer.prototype.setDestination = function( destination )
			{
				this.destination_id = ( destination && destination.id ? destination.id : null );
				this.destination_name = ( destination && destination.store_name ? destination.store_name : null );
			};

		Transfer.prototype.setRecipient = function( recipient )
			{
				if( recipient.constructor == Object )
				{
					this.recipient_id = ( recipient.id ? recipient.id : null );
					this.recipient_name = ( recipient.full_name ? recipient.full_name : null );
				}
				else if( recipient.constructor == String )
				{
					this.recipient_id = null;
					this.recipient_name = recipient;
				}
			};


		Transfer.prototype.addItem = function( item )
			{
				this.items.push( item );
			};


		Transfer.prototype.removeItem = function( item )
			{
				if( item.id == undefined )
				{
					var index = this.items.indexOf( item );
					this.items.splice( index, 1 );
				}
				else
				{
					item.void( !item.void() );
				}
			};


		Transfer.prototype.checkTransfer = function( action )
			{
				// Check if a destination is specified
				if( action == 'approve' && !this.destination_name )
				{
					notifications.alert( 'Please enter name of person to deliver the items', 'warning' );
					return false;
				}

				// Check if an origin is specified
				if( action == 'receive' && !this.origin_name )
				{
					notifications.alert( 'Please specify source name', 'warning' );
					return false;
				}

				// Check if a transfer datetime is specified
				if( !this.transfer_datetime )
				{
					notifications.alert( 'Please specify date/time', 'warning' );
					return false;
				}

				// Check if there are items for transfer
				if( this.items.length == 0 )
				{
					notifications.alert( 'Transfer does not contain any items', 'warning' );
					return false;
				}

				// Check if there valid items for transfer
				if( this.getValidItems().length == 0 )
				{
					notifications.alert( 'Transfer does not contain any valid items', 'warning' );
					return false;
				}

				return true;
			};


		Transfer.prototype.prepareTransferData = function()
			{
				var data = {
						id: this.id,
						transfer_reference_num: this.transfer_reference_num,
						transfer_category: this.transfer_category,
						origin_id: this.origin_id,
						origin_name: this.origin_name,
						sender_id: this.sender_id,
						sender_name: this.sender_name,
						sender_shift: this.sender_shift,
						transfer_datetime: this.transfer_datetime ? $filter( 'date' )( this.transfer_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
						transfer_user_id: this.transfer_user_id,
						destination_id: this.destination_id,
						destination_name: this.destination_name,
						recipient_id: this.recipient_id,
						recipient_name: this.recipient_name,
						receipt_datetime: this.receipt_datetime ? $filter( 'date' )( this.receipt_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
						receipt_user_id: this.receipt_user_id,
						transfer_status: this.transfer_status,
						items: []
					};

				var items = this.items;
				var n = items.length;
				for( var i = 0; i < n; i++ )
				{
					var itemData = {
							id: items[i].id,
							transfer_id: items[i].transfer_id,
							item_id: items[i].item_id,
							transfer_item_category_id: items[i].transfer_item_category_id,
							quantity: items[i].quantity,
							quantity_received: items[i].quantity_received,
							remarks: items[i].remarks,
							transfer_item_status: items[i].transfer_item_status,
							transfer_item_allocation_item_id: items[i].transfer_item_allocation_item_id,
							transfer_item_transfer_item_id: items[i].transfer_item_transfer_item_id
						};

					// Change item status of voided items
					if( items[i].markedVoid )
					{
						itemData.transfer_item_status = 5;
					}

					data.items.push( itemData );
				}

				return data;
			};


		Transfer.prototype.save = function( action )
			{
				var me = this;
				var deferred = $q.defer();
				if( this.checkTransfer( action ) )
				{
					var transferData = this.prepareTransferData();

					var transferUrl = baseUrl + 'index.php/api/v1/transfers/';
					switch( action )
					{
						case 'approve':
							transferUrl += 'approve';
							break;

						case 'cancel':
							transferUrl += 'cancel';
							break;

						case 'receive':
							transferUrl += 'receive';
							break;

						default:
							// do nothing
					}

					$http({
						method: 'POST',
						url: transferUrl,
						data: transferData
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


		return Transfer;
	}
]);

angular.module( 'coreModels' ).factory( 'TransferItem', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var transfer_id;
		var item_id;
		var transfer_item_category_id;
		var quantity;
		var quantity_received;
		var remarks;
		var transfer_item_status;
		var transfer_item_allocation_item_id;
		var transfer_item_transfer_item_id;

		var transferItemStatus = {
				'1': 'Scheduled',
				'2': 'Approved',
				'3': 'Received',
				'4': 'Cancelled',
				'5': 'Voided'
			};


		/**
		 * Constructor
		 */
		function TransferItem( data )
		{
			this.loadData( data );
		};


		TransferItem.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.transfer_id = null;
				me.item_id = null;
				me.transfer_item_category_id = null;
				me.quantity = 0;
				me.quantity_received = null;
				me.remarks = null;
				me.transfer_item_status = 1;
				me.transfer_item_allocation_item_id = null;
				me.transfer_item_transfer_item_id = null;

				if( data )
				{
					angular.merge( me, data );
				}
			};


		TransferItem.prototype.get = function( field )
			{
				switch( field )
				{
					case 'statusName':
						return transferItemStatus[this.transfer_item_status.toString()];

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


		TransferItem.prototype.void = function( status )
			{
				if( status == undefined)
				{
					return this.markedVoid;
				}
				else if( status == true || status == false )
				{
					this.markedVoid = status;
				}
			};


		TransferItem.prototype.canRemove = function()
			{
				return this.transfer_item_status == 1 && !this.id;
			};


		TransferItem.prototype.canVoid = function()
			{
				return ( this.transfer_item_status == 1 || this.transfer_item_status == 2 ) && this.id;
			};

		return TransferItem;
	}
]);