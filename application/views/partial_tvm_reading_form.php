<div ng-if="checkPermissions( 'allocations', 'view' )">
	<div class="panel panel-default">
		<div class="panel-heading clearfix">
			<span class="panel-title">{{ data.title }}</span>
		</div>
		<div class="panel-body">
			<form class="form-horizontal">
				<div class="row">

					<!-- Machine ID -->
					<div class="form-group col-sm-3">
						<label class="control-label col-sm-4">TVM #</label>
						<div class="col-sm-7">
							<input type="text" class="form-control" ng-model="TVMReading.tvmr_machine_id">
						</div>
					</div>

					<!-- Cashier -->
					<div class="form-group col-sm-5">
						<label class="control-label col-sm-3">Cashier</label>
						<div class="col-sm-8">
							<input type="text" class="form-control"
									ng-model="TVMReading.tvmr_cashier_id"
									ng-model-options="{ debounce: 500 }"
									typeahead-editable="true"
									uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
						</div>
					</div>

					<!-- Date -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-5">Reading Date</label>
						<div class="input-group col-sm-6">
							<input type="text" class="form-control" uib-datepicker-popup="{{ data.datepicker.format }}" is-open="data.datepicker.opened"
									min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
									ng-model="TVMReading.tvmr_datetime" ng-required="true" close-text="Close" alt-input-formats="altInputFormats">
							<span class="input-group-btn">
								<button type="button" class="btn btn-default" ng-click="showDatePicker()"><i class="glyphicon glyphicon-calendar"></i></button>
							</span>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<div ng-if="! checkPermissions( 'allocations', 'view' )">
	<h1>Access Denied</h1>
	<p>You are not authorized to view this page. If you believe that this is incorrect please contact your system administrator.</p>
</div>