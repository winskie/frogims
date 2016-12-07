angular.module( 'coreModels' ).factory( 'Collection', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications', 'Conversion', 'CollectionItem',
	function( $http, $q, $filter, baseUrl, session, notifications, Conversion, CollectionItem )
	{
		var id;
		var store_id;
    var processing_datetime;
		var business_date;
		var shift_id;
    var cashier_shift_id;

    var items;

		var collectionSummary;

		/**
		 * Constructor
		 */
		function Collection( data )
		{
			this.loadData( data );
		};


		Collection.createFromData = function( data )
			{
				if( data.constructor == Array )
				{
					var n = data.length;
					var collectionsArray = [];

					for( var i = 0; i < n; i++ )
					{
						collectionsArray.push( new Collection( data[i] ) );
					}

					return collectionsArray;
				}
				else if( data.constructor == Object )
				{
					return new Collection( data );
				}
			};


		Collection.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.store_id = session.data.currentStore.id;
				me.processing_datetime = new Date();
				me.business_date = new Date();
				me.shift_id = null;
				me.cashier_shift_id = null;

				me.items = [];

				me.collectionSummary = [];

				if( data )
				{
					var items = [];
					if( data.items )
					{
						angular.copy( data.items, items );
						delete data.items;
					}

					angular.merge( me, data );

					if( me.processing_datetime )
					{
						me.processing_datetime = Date.parse( me.processing_datetime );
					}

					if( me.business_date )
					{
						me.business_date = Date.parse( me.business_date );
					}

					// Allocation items
					if( items )
					{
						var n = items.length;
						for( var i = 0; i < n; i++ )
						{
							me.items.push( new CollectionItem( items[i] ) );
						}
					}

					me.updateCollectionSummary();
				}
			};


		Collection.prototype.get = function( field )
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


		Collection.prototype.canEdit = function( showAction )
			{
				return session.checkPermissions( 'collections', 'edit' ) &&
							 this.store_id == session.data.currentStore.id;
			};


		Collection.prototype.addItem = function( item )
			{
				this.items.push( item );
				this.updateCollectionSummary();
			};


		Collection.prototype.removeItem = function( item )
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

				this.updateCollectionSummary();
			};


		Collection.prototype.updateCollectionSummary = function()
			{
				this.collectionSummary = [];
				var tempObj = {};
				var groupObj = {};
				var lastGroup = 1;
				var ignoredStatus = [ 2 ]; // MOPPED_ITEM_VOID

				var n = this.items.length;

				for( var i = 0; i < n; i++ )
				{
					if( ignoredStatus.indexOf( this.items[i].mopped_item_status) != -1 || this.items[i].markedVoid )
					{
						continue;
					}

					if( !this.items[i].converted_to )
					{
						if( tempObj[this.items[i].mopped_item_id] )
						{
							tempObj[this.items[i].mopped_item_id].quantity += this.items[i].mopped_quantity;
							tempObj[this.items[i].mopped_item_id].src_rows.push( this.items[i].id );
						}
						else
						{
							tempObj[this.items[i].mopped_item_id] = {
									item_id: this.items[i].mopped_item_id,
									item_name: this.items[i].mopped_item_name,
									item_description: this.items[i].mopped_item_description,
									quantity: this.items[i].mopped_quantity,
									src_rows: [ this.items[i].id ],
									valid_item: true
								};
						}

						this.items[i].valid_item = true;
					}
					else
					{
						var cFactor = Conversion.getConversionData( this.items[i].converted_to, this.items[i].mopped_item_id ).factor;
						var currentItem = groupObj[lastGroup + '_' + this.items[i].mopped_item_id + '_' + this.items[i].converted_to];

						if( currentItem )
						{
							currentItem['quantity'] += this.items[i].mopped_quantity;
							currentItem['src_rows'].push( i );
							currentItem['valid_item'] = currentItem.quantity == cFactor;
						}
						else
						{
							groupObj[lastGroup + '_' + this.items[i].mopped_item_id + '_' + this.items[i].converted_to] = {
									group_id: lastGroup,
									original_item_id: this.items[i].mopped_item_id,
									item_id: this.items[i].converted_to,
									item_name: this.items[i].converted_to_name,
									item_description: this.items[i].converted_to_description,
									quantity: this.items[i].mopped_quantity,
									src_rows: [ i ],
									valid_item: this.items[i].mopped_quantity == cFactor
								};

							currentItem = groupObj[lastGroup + '_' + this.items[i].mopped_item_id + '_' + this.items[i].converted_to];
						}

						this.items[i].group_id = lastGroup;
						if( currentItem.valid_item )
						{
							lastGroup++;
						}
					}
				}

				var m = groupObj.length;
				for( var key in groupObj )
				{
					cQuantity = Conversion.convert( groupObj[key].original_item_id, groupObj[key].item_id, groupObj[key].quantity );

					if( tempObj[groupObj[key].item_id] )
					{
						tempObj[groupObj[key].item_id].quantity += cQuantity;
						tempObj[groupObj[key].item_id].src_rows = tempObj[groupObj[key].item_id].src_rows.concat( groupObj[key].src_rows );
						tempObj[groupObj[key].item_id].valid_item = tempObj[groupObj[key].item_id].valid_item;
					}
					else
					{
						tempObj[groupObj[key].item_id] = {
								item_id: groupObj[key].item_id,
								item_name: groupObj[key].item_name,
								item_description: groupObj[key].item_description,
								quantity: cQuantity,
								src_rows: groupObj[key].src_rows,
								valid_item: groupObj[key].valid_item
							};
					}

					var j = groupObj[key].src_rows.length;
					for( var i = 0; i < j; i++ )
					{
						this.items[groupObj[key].src_rows[i]].valid_item = groupObj[key].valid_item;
					}
				}

				for( var key in tempObj )
				{
					this.collectionSummary.push( tempObj[key] );
				}

				return this.collectionSummary;
			};


		Collection.prototype.checkCollection = function( action )
			{
				if( !this.processing_datetime )
				{
					notifications.alert( 'Missing processing date/time', 'warning' );
					return false;
				}

				if( !this.business_date )
				{
					notifications.alert( 'Missing pullout business date', 'warning' );
					return false;
				}

				// Check for invalid packaging of items
				var n = this.items.length;
				for( var i = 0; i < n; i++ )
				{
					if( !this.items[i].valid_item )
					{
						notifications.alert( 'Record contains incomplete packed items', 'warning' );
						return false;
					}
				}

				return true;
			};


		Collection.prototype.prepareCollectionData = function()
			{
				var data = {
						id: this.id,
						store_id: this.store_id,
						processing_datetime: this.processing_datetime ? $filter( 'date' )( this.processing_datetime, 'yyyy-MM-dd HH:mm:ss' ) : null,
						business_date: this.business_date ? $filter( 'date' )( this.business_date, 'yyyy-MM-dd' ) : null,
						shift_id: this.shift_id,
						cashier_shift_id: this.cashier_shift_id,

						items: []
					};

				var items = this.items;
				var n = items.length;

				for( var i = 0; i < n; i++ )
				{
					var itemData = {
							id: items[i].id,
							mopping_id: items[i].mopping_id,
							mopped_station_id: items[i].mopped_station_id,
							mopped_item_id: items[i].mopped_item_id,
							mopped_quantity: items[i].mopped_quantity,
							mopped_base_quantity: items[i].mopped_base_quantity,
							converted_to: items[i].converted_to,
							group_id: items[i].group_id,
							mopping_item_status: items[i].mopping_item_status,
							processor_id: items[i].processor_id,
							delivery_person: items[i].delivery_person
						};

					if( items[i].markedVoid )
					{
						itemData.mopping_item_status = 2 // MOPPED_ITEM_VOID
					}

					data.items.push( itemData );
				}

				return data;
			};


		Collection.prototype.save = function( action )
			{
				var me = this;
				var deferred = $q.defer();

				if( this.checkCollection( action ) )
				{
					var collectionData = this.prepareCollectionData();

					var collectionUrl = baseUrl + 'index.php/api/v1/collections/';
					switch( action )
					{
						default:
							// do nothing
					}

					$http({
						method: 'POST',
						url: collectionUrl,
						data: collectionData
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
					deferred.reject( 'Failed collection data check' );
				}

				return deferred.promise;
			};



		return Collection;
	}
]);


angular.module( 'coreModels' ).factory( 'CollectionItem', [ '$http', '$q', '$filter', 'baseUrl', 'session', 'notifications',
	function( $http, $q, $filter, baseUrl, session, notifications )
	{
		var id;
		var mopping_id;
		var mopped_station_id;
		var mopped_item_id;
    var mopped_quantity;
    var mopped_base_quantity;
    var converted_to;
    var group_id;
    var mopping_item_status;
    var processor_id;
    var delivery_person;

		var valid_item;
		/**
		 * Constructor
		 */
		function CollectionItem( data )
		{
			this.loadData( data );
		}

		CollectionItem.prototype.loadData = function( data )
			{
				var me = this;

				me.id = null;
				me.mopping_id = null;
				me.mopped_station_id = null;
				me.mopped_item_id = null;
				me.mopped_quantity = null;
				me.mopped_base_quantity = null;
				me.converted_to = null;
				me.group_id = null;
				me.mopping_item_status = 1;
				me.processor_id = null;
				me.delivery_person = null;

				me.valid_item = false;

				if( data )
				{
					angular.merge( me, data );
				}
			};


		return CollectionItem;
	}
]);