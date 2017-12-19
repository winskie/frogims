app.controller( 'DeliveryReceiptModalController', [ '$uibModalInstance', 'transferItem', 'UserServices',
	function( $uibModalInstance, transferItem, UserServices )
	{
		var $ctrl = this;
		$ctrl.params = {
				preparedBy: null,
				checkedBy: null,
				bearerName: null,
				bearerId: null,
				issuedBy: null,
				approvedBy: null
			};

		$ctrl.findUser = UserServices.findUser;

		$ctrl.ok = function()
			{
				reportParams = {
						TRANSFER_ID: transferItem.id,
						PREPARED_BY: $ctrl.params.preparedBy ? $ctrl.params.preparedBy.full_name : null,
						PREPARED_BY_POSITION: $ctrl.params.preparedBy ? $ctrl.params.preparedBy.position : null,
						CHECKED_BY: $ctrl.params.checkedBy ? $ctrl.params.checkedBy.full_name : null,
						CHECKED_BY_POSITION: $ctrl.params.checkedBy ? $ctrl.params.checkedBy.position : null,
						BEARER: $ctrl.params.bearerName ? $ctrl.params.bearerName : null,
						BEARER_ID: $ctrl.params.bearerId ? $ctrl.params.bearerId : null,
						ISSUED_BY: $ctrl.params.issuedBy ? $ctrl.params.issuedBy.full_name : null,
						ISSUED_BY_POSITION: $ctrl.params.issuedBy ? $ctrl.params.issuedBy.position : null,
						APPROVED_BY: $ctrl.params.approvedBy ? $ctrl.params.approvedBy.full_name : null,
						APPROVED_BY_POSITION: $ctrl.params.approvedBy ? $ctrl.params.approvedBy.position : null
					};

				$uibModalInstance.close( reportParams );
			};

		$ctrl.cancel = function()
			{
				$uibModalInstance.dismiss();
			};
	}
]);

app.controller( 'TurnoverItemModalController', [ '$filter', '$uibModalInstance', 'session', 'appData',
	function( $filter, $uibModalInstance, session, appData )
	{
		var $ctrl = this;
		$ctrl.input = {
				datepicker: { value: new Date(), format: 'yyyy-MM-dd', opened: false }
			};

		$ctrl.data = {
				checkAllItems: true,
				items: []
			};

		$ctrl.showDatePicker = function()
			{
				$ctrl.input.datepicker.opened = true;
			};

		$ctrl.changeDate = function()
			{
				var date = $ctrl.input.datepicker.value;
				appData.getTurnoverItems( session.data.currentStore.id, date ).then(
					function( response )
					{
						for( var i = 0; i < response.items.length; i++ )
						{
							response.items[i].selected = true;
						}
						$ctrl.data.items = response.items;
					},
					function( reason )
					{
						console.error( reason );
					} );
			};

		$ctrl.toggleCheckboxes = function()
			{
				for( var i = 0; i < $ctrl.data.items.length; i++ )
				{
					$ctrl.data.items[i].selected = $ctrl.data.checkAllItems && !$ctrl.data.items[i].turnover_id;
				}
			}

		$ctrl.submit = function()
			{
				var selected = [];
				for( var i = 0; i < $ctrl.data.items.length; i++ )
				{
					if( $ctrl.data.items[i].selected && !$ctrl.data.items[i].turnover_id )
					{
						selected.push( $ctrl.data.items[i] );
					}
				}
				$uibModalInstance.close( selected );
			};

		$ctrl.close = function()
			{
				$uibModalInstance.dismiss();
			};

		// Initialize controller
		$ctrl.changeDate();
	}
]);

app.controller( 'SalesDepositItemModalController', [ '$filter', '$uibModalInstance', 'session', 'appData', 'businessDate', 'currentShiftId',
	function( $filter, $uibModalInstance, session, appData, businessDate, currentShiftId )
	{
		var $ctrl = this;

		$ctrl.data = {
				businessDate: businessDate,
				checkAllItems: true,
				items: []
			};

		$ctrl.getSalesCollectionItems = function()
			{
				appData.getShiftSalesItems( session.data.currentStore.id, businessDate, currentShiftId ).then(
					function( response )
					{
						for( var i = 0; i < response.items.length; i++ )
						{
							response.items[i].selected = true;
						}
						$ctrl.data.items = response.items;
					},
					function( reason )
					{
						console.error( reason );
					} );
			};

		$ctrl.toggleCheckboxes = function()
			{
				for( var i = 0; i < $ctrl.data.items.length; i++ )
				{
					$ctrl.data.items[i].selected = $ctrl.data.checkAllItems && !$ctrl.data.items[i].turnover_id;
				}
			}

		$ctrl.submit = function()
			{
				var selected = [];
				for( var i = 0; i < $ctrl.data.items.length; i++ )
				{
					if( $ctrl.data.items[i].selected && !$ctrl.data.items[i].turnover_id )
					{
						selected.push( $ctrl.data.items[i] );
					}
				}
				$uibModalInstance.close( selected );
			};

		$ctrl.close = function()
			{
				$uibModalInstance.dismiss();
			};

		// Initialize controller
		$ctrl.getSalesCollectionItems();
	}
]);