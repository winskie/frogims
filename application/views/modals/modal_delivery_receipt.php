<div class="modal-header">
	<h3 class="modal-title" id="modal-title">Delivery Receipt</h3>
</div>
<div class="modal-body" id="modal-body">
	<form class="form">
		<div class="panel panel-default">
			<div class="panel-heading">Delivery Receipt</div>
			<div class="panel-body">
				<div class="row">
					<div class="form-group col-sm-12 col-md-6">
						<label class="control-label">Prepared by</label>
						<input type="text" class="form-control"
							ng-model="$ctrl.params.preparedBy"
							ng-model-options="{ debounce: 500 }"
							typeahead-editable="false"
							uib-typeahead="user as user.full_name for user in $ctrl.findUser( $viewValue )">
					</div>
					<div class="form-group col-sm-12 col-md-6">
						<label class="control-label">Checked by</label>
						<input type="text" class="form-control"
							ng-model="$ctrl.params.checkedBy"
							ng-model-options="{ debounce: 500 }"
							typeahead-editable="false"
							uib-typeahead="user as user.full_name for user in $ctrl.findUser( $viewValue )">
					</div>
				</div>
			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading">Gate Pass</div>
			<div class="panel-body">
				<div class="row">
					<div class="form-group col-sm-12 col-md-6">
						<label class="control-label">Bearer</label>
						<input type="text" class="form-control" ng-model="$ctrl.params.bearerName">
					</div>
					<div class="form-group col-sm-12 col-md-6">
						<label class="control-label">ID Number</label>
						<input type="text" class="form-control" ng-model="$ctrl.params.bearerId">
					</div>
				</div>
				<div class="row">
					<div class="form-group col-sm-12 col-md-6">
						<label class="control-label">Issued by</label>
						<input type="text" class="form-control"
							ng-model="$ctrl.params.issuedBy"
							ng-model-options="{ debounce: 500 }"
							typeahead-editable="false"
							uib-typeahead="user as user.full_name for user in $ctrl.findUser( $viewValue )">
					</div>
					<div class="form-group col-sm-12 col-md-6">
						<label class="control-label">Approved for release</label>
						<input type="text" class="form-control"
							ng-model="$ctrl.params.approvedBy"
							ng-model-options="{ debounce: 500 }"
							typeahead-editable="false"
							uib-typeahead="user as user.full_name for user in $ctrl.findUser( $viewValue )">
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
<div class="modal-footer">
	<button class="btn btn-primary" type="button" ng-click="$ctrl.ok()">OK</button>
	<button class="btn btn-default" type="button" ng-click="$ctrl.cancel()">Cancel</button>
</div>