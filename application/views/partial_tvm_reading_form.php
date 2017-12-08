<div ng-if="checkPermissions( 'allocations', 'view' )">
	<div class="panel panel-default">
		<div class="panel-heading clearfix">
			<span class="panel-title">TVM and Reading Information</span>
		</div>

		<div class="panel-body">
			<form class="form-horizontal">
				<div class="row">

					<!-- Machine ID -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-4">TVM #</label>
						<div class="col-sm-7" ng-switch on="data.editMode">
							<select class="form-control"
									ng-model="data.selectedTVM" ng-switch-when="edit" ng-change="onTVMChange()"
									ng-options="tvm as tvm.description for tvm in data.tvms track by tvm.description">
							</select>
							<p class="form-control-static" ng-switch-default>{{ data.selectedTVM.description}}</p>
						</div>
					</div>

					<!-- Cashier -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-4">Cashier</label>
						<div class="col-sm-8" ng-switch on="data.editMode">
							<input type="text" class="form-control"
									ng-switch-when="edit"
									ng-model="TVMReading.tvmr_cashier_name"
									ng-change="onCashierChange()"
									ng-model-options="{ debounce: 500 }"
									typeahead-editable="true"
									typeahead-on-select="onCashierChange()"
									uib-typeahead="user as user.full_name for user in findUser( $viewValue )">
							<p class="form-control-static" ng-switch-default>{{ TVMReading.tvmr_cashier_name }}</p>
						</div>
					</div>

					<!-- Date -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-4">Date</label>
						<div class="col-sm-8" ng-if="data.editMode == 'edit'">
							<input type="date" class="form-control" ng-model="TVMReading.tvmr_date" ng-required="true" ng-change="loadPreviousReading()">
						</div>
						<div class="col-sm-8" ng-if="data.editMode != 'edit'">
							<p class="form-control-static">{{ TVMReading.tvmr_date | date: 'yyyy-MM-dd' }}</p>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="form-group col-sm-4">
					</div>
					<!-- Shift ID -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-4">Shift</label>
						<div class="col-sm-8" ng-switch on="data.editMode">
							<select class="form-control"
									ng-switch-when="edit"
									ng-model="data.	selectedCashierShift"
									ng-options="shift.shift_num for shift in data.cashierShifts track by shift.id"
									ng-change="onShiftChange()">
							</select>
							<p class="form-control-static" ng-switch-default>{{ TVMReading.shift_num }}</p>
						</div>
					</div>

					<!-- Time -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-4">Time</label>
						<div class="col-sm-8" ng-if="data.editMode == 'edit'">
							<input type="time" class="form-control" ng-model="TVMReading.tvmr_time" ng-required="true">
						</div>
						<div class="col-sm-8" ng-if="data.editMode != 'edit'">
							<p class="form-control-static">{{ TVMReading.tvmr_time | date: 'HH:mm:ss' }}</p>
						</div>
					</div>
				</div>
			</form>
		</div>

	</div>

	<div class="panel panel-default">
		<div class="panel-heading clearfix">
			<span class="panel-title">Reading Data</span>
		</div>
		<div class="panel-body">
			<form class="form-horizontal">
				<div class="row">
					<!-- Type -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-4">Type</label>
						<div class="col-sm-7" ng-switch on="data.editMode">
							<select class="form-control"
									ng-switch-when="edit"
									ng-model="data.selectedType"
									ng-options="type.typeName for type in data.readingTypes track by type.id"
									ng-change="onTypeChange()">
							</select>
							<p class="form-control-static" ng-switch-default>{{ data.selectedType.typeName }}</p>
						</div>
					</div>
				</div>

				<div class="row">
					<!-- Previous Reading -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-4">Previous</label>
						<div class="col-sm-7" ng-if="data.editMode == 'edit'">
							<input type="number" class="form-control" placeholder="Previous Reading"
									ng-model="TVMReading.tvmr_previous_reading">
						</div>
						<div class="col-sm-7" ng-if="data.editMode != 'edit'">
							<p class="form-control-static">{{ TVMReading.tvmr_previous_reading | number: data.selectedType.decimalPlace }}</p>
						</div>
					</div>

					<!-- Current Reading -->
					<div class="form-group col-sm-4">
						<label class="control-label col-sm-4">Reading</label>
						<div class="col-sm-7" ng-if="data.editMode == 'edit'">
							<input type="number" class="form-control" placeholder="Current Reading"
									ng-model="TVMReading.tvmr_reading">
						</div>
						<div class="col-sm-4" ng-if="data.editMode != 'edit'">
							<p class="form-control-static">{{ TVMReading.tvmr_reading }}</p>
						</div>
					</div>

					<!-- Reference Number -->
					<div class="form-group col-sm-4" ng-hide="!data.selectedType.hasReference">
						<label class="control-label col-sm-4">Reference #</label>
						<div class="col-sm-7" ng-if="data.editMode == 'edit'">
							<input type="text" class="form-control" placeholder="Reference ID"
									ng-model="TVMReading.tvmr_reference_num">
						</div>
						<div class="col-sm-4" ng-if="data.editMode != 'edit'">
							<p class="form-control-static">{{ TVMReading.tvmr_reference_num | number: data.selectedType.decimalPlace }}</p>
						</div>
					</div>

				</div>
			</form>
		</div>
	</div>

	<div class="text-right">
		<button class="btn btn-primary" ng-click="saveTVMReading()"
				ng-if="data.editMode != 'view' && checkPermissions( 'allocations', 'edit' )">Save</button>
		<button class="btn btn-default" ui-sref="main.store({ activeTab: 'tvmReadings' })">Close</button>
	</div>
</div>

<div ng-if="! checkPermissions( 'allocations', 'view' )">
	<h1>Access Denied</h1>
	<p>You are not authorized to view this page. If you believe that this is incorrect please contact your system administrator.</p>
</div>