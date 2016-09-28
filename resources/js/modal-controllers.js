app.controller( 'DeliveryReceiptModalController', [ '$uibModalInstance', 'transferItem', 'UserServices',
	function( $uibModalInstance, transferItem, UserServices )
	{
		console.log( 'Modal', transferItem );
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