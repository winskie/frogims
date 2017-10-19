<div ng-if="checkPermissions( 'allocations', 'view' )">
	<div class="panel panel-default">
		<div class="panel-heading clearfix">
			<span class="panel-title">{{ data.title }}</span>
		</div>

		<div class="panel-body">
			<form class="form-horizontal">
				<div class="row">

					<!-- Machine ID -->
					<div class="form-group col-sm-6">
						<label class="control-label col-sm-2">TVM #</label>
						<div class="col-sm-6" ng-switch on="data.editMode">
							<select class="form-control"
									ng-model="data.selectedTVM" ng-switch-when="edit" ng-change="onTVMChange()"
									ng-options="tvm as tvm.description for tvm in data.tvms track by tvm.id">
							</select>
							<p class="form-control-static" ng-switch-default>{{ data.selectedTVM.description}}</p>
						</div>
					</div>

					<!-- Date -->
					<div class="form-group col-sm-6">
						<label class="control-label col-sm-4">Reading Date</label>
						<div class="col-sm-7" ng-switch on="data.editMode">
							<input type="datetime-local" class="form-control" ng-model="TVMReading.tvmr_datetime" ng-switch-when="edit"
									ng-required="true" ng-change="loadPreviousReading()">
							<p class="form-control-static" ng-switch-default>{{ TVMReading.tvmr_datetime | parseDate | date: 'yyyy-MM-dd HH:mm:ss' }}</p>
						</div>
					</div>
				</div>

				<div class="row">
					<!-- Shift ID -->
					<div class="form-group col-sm-6">
						<label class="control-label col-sm-2">Shift</label>
						<div class="col-sm-6" ng-switch on="data.editMode">
							<select class="form-control"
									ng-switch-when="edit"
									ng-model="data.selectedCashierShift"
									ng-options="shift.shift_num for shift in data.cashierShifts track by shift.id"
									ng-change="onShiftChange()">
							</select>
							<p class="form-control-static" ng-switch-default>{{ TVMReading.shift_num }}</p>
						</div>
					</div>

					<!-- Cashier -->
					<div class="form-group col-sm-6">
						<label class="control-label col-sm-4">Cashier</label>
						<div class="col-sm-7" ng-switch on="data.editMode">
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
				</div>
			</form>
		</div>

	</div>

	<div class="row">
		<div class="col-sm-6">
			<div class="panel panel-default">
				<div class="panel-heading clearfix">
					<span class="panel-title">Current Reading</span>
				</div>
				<div class="panel-body">
					<form class="form-horizontal">
						<!-- SJT in Magazine -->
						<div class="form-group row" ng-switch on="data.editMode">
							<label class="control-label col-sm-4">SJT in Magazine</label>
							<div class="col-sm-4">
								<input type="number" class="form-control" placeholder="Ticket Count"
										ng-switch-when="edit"
										ng-model="TVMReading.magazine_sjt_reading.tvmri_quantity">
								<p class="form-control-static" ng-switch-default>{{ TVMReading.magazine_sjt_reading.tvmri_quantity |  number }}</p>
							</div>
						</div>

						<!-- SVC in Magazine -->
						<div class="form-group row" ng-switch on="data.editMode">
							<label class="control-label col-sm-4">SVC in Magazine</label>
							<div class="col-sm-4" >
								<input type="number" class="form-control" placeholder="Ticket Count"
										ng-switch-when="edit"
										ng-model="TVMReading.magazine_svc_reading.tvmri_quantity">
								<p class="form-control-static" ng-switch-default>{{ TVMReading.magazine_svc_reading.tvmri_quantity |  number }}</p>
							</div>
						</div>

						<!-- Coin Box -->
						<div class="form-group row">
							<label class="control-label col-sm-4">Coin Box</label>
							<div class="col-sm-4" ng-if="data.editMode == 'edit'">
								<input type="number" class="form-control" placeholder="Total Value"
										ng-model="TVMReading.coin_box_reading.tvmri_quantity">
							</div>
							<div class="col-sm-4" ng-if="data.editMode == 'edit'">
								<input type="text" class="form-control" placeholder="Coin Box ID"
										ng-model="TVMReading.coin_box_reading.tvmri_reference_num">
							</div>
							<div class="col-sm-4" ng-if="data.editMode != 'edit'">
								<p class="form-control-static">{{ ( TVMReading.coin_box_reading.tvmri_quantity ? ( TVMReading.coin_box_reading.tvmri_quantity |  number: 2 ) : '' )
										+ ( TVMReading.coin_box_reading.tvmri_reference_num ? ' #' + TVMReading.coin_box_reading.tvmri_reference_num : '' ) }}
								</p>
							</div>
						</div>

						<!-- BNA Box -->
						<div class="form-group row">
							<label class="control-label col-sm-4">BNA Box</label>
							<div class="col-sm-4" ng-if="data.editMode == 'edit'">
								<input type="number" class="form-control" placeholder="Total Value"
										ng-model="TVMReading.note_box_reading.tvmri_quantity">
							</div>
							<div class="col-sm-4" ng-if="data.editMode == 'edit'">
								<input type="text" class="form-control" placeholder="Note Box ID"
										ng-model="TVMReading.note_box_reading.tvmri_reference_num">
							</div>
							<div class="col-sm-4" ng-if="data.editMode != 'edit'">
								<p class="form-control-static">{{ ( TVMReading.note_box_reading.tvmri_quantity ? ( TVMReading.note_box_reading.tvmri_quantity |  number: 2 ) : '' )
										+ ( TVMReading.note_box_reading.tvmri_reference_num ? ' #' + TVMReading.note_box_reading.tvmri_reference_num : '' ) }}
								</p>
							</div>
						</div>

						<!-- Hopper -->
						<div class="form-group row" ng-switch on="data.editMode">
							<label class="control-label col-sm-4">Php5 Hopper</label>
							<div class="col-sm-4">
								<input type="number" class="form-control" placeholder="Coin Count"
										ng-switch-when="edit"
										ng-model="TVMReading.hopper_php5_reading.tvmri_quantity">
								<p class="form-control-static" ng-switch-default>{{ TVMReading.hopper_php5_reading.tvmri_quantity |  number }}</p>
							</div>
						</div>

						<!-- Hopper -->
						<div class="form-group row" ng-switch on="data.editMode">
							<label class="control-label col-sm-4">Php1 Hopper</label>
							<div class="col-sm-4">
								<input type="number" class="form-control" placeholder="Coin Count"
										ng-switch-when="edit"
										ng-model="TVMReading.hopper_php1_reading.tvmri_quantity">
								<p class="form-control-static" ng-switch-default>{{ TVMReading.hopper_php1_reading.tvmri_quantity |  number }}</p>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="col-sm-6">
			<div class="panel panel-default">
				<div class="panel-heading clearfix">
					<span class="panel-title">Previous Reading </span>&nbsp;
					<span ng-if="TVMReading.previous_reading.id">{{ TVMReading.previous_reading.tvmr_datetime | date: 'yyyy-MM-dd HH:mm:ss' }} - {{ TVMReading.previous_reading.shift_num }}</span>
				</div>
				<div class="panel-body">
					<form class="form-horizontal">
						<!-- SJT in Magazine -->
						<div class="form-group row">
							<label class="control-label col-sm-4">SJT in Magazine</label>
							<div class="col-sm-4">
								<p class="form-control-static">{{ TVMReading.previous_reading.magazine_sjt_reading.tvmri_quantity | number }}</p>
							</div>
						</div>

						<!-- SVC in Magazine -->
						<div class="form-group row">
							<label class="control-label col-sm-4">SVC in Magazine</label>
							<div class="col-sm-4">
								<p class="form-control-static">{{ TVMReading.previous_reading.magazine_svc_reading.tvmri_quantity | number }}</p>
							</div>
						</div>

						<!-- Coin Box -->
						<div class="form-group row">
							<label class="control-label col-sm-4">Coin Box</label>
							<div class="col-sm-4">
								<p class="form-control-static">{{ ( TVMReading.previous_reading.coin_box_reading.tvmri_quantity ? ( TVMReading.previous_reading.coin_box_reading.tvmri_quantity |  number: 2 ) : '' )
										+ ( TVMReading.previous_reading.coin_box_reading.tvmri_reference_num ? ' #' + TVMReading.previous_reading.coin_box_reading.tvmri_reference_num : '' ) }}
								</p>
							</div>
						</div>

						<!-- BNA Box -->
						<div class="form-group row">
							<label class="control-label col-sm-4">BNA Box</label>
							<div class="col-sm-4">
								<p class="form-control-static">{{ ( TVMReading.previous_reading.note_box_reading.tvmri_quantity ? ( TVMReading.previous_reading.note_box_reading.tvmri_quantity |  number: 2 ) : '' )
										+ ( TVMReading.previous_reading.note_box_reading.tvmri_reference_num ? ' #' + TVMReading.previous_reading.note_box_reading.tvmri_reference_num : '' ) }}
								</p>
							</div>
						</div>

						<!-- Hopper -->
						<div class="form-group row">
							<label class="control-label col-sm-4">Php5 Hopper</label>
							<div class="col-sm-4">
								<p class="form-control-static">{{ TVMReading.previous_reading.hopper_php5_reading.tvmri_quantity | number }}</p>
							</div>
						</div>

						<!-- Hopper -->
						<div class="form-group row">
							<label class="control-label col-sm-4">Php1 Hopper</label>
							<div class="col-sm-4">
								<p class="form-control-static">{{ TVMReading.previous_reading.hopper_php1_reading.tvmri_quantity | number }}</p>
							</div>
						</div>
					</form>
				</div>
			</div>
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