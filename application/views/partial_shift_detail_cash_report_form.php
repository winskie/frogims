<div ng-if="checkPermissions( 'allocations', 'view' )">
	<div class="panel panel-default">
		<div class="panel-heading clearfix">
			<span class="panel-title">{{ data.title }}</span>
		</div>

		<div class="panel-body">
			<form class="form">
				<div class="row">

					<div class="col-sm-4">

						<!-- Teller ID -->
						<div class="form-group">
							<label class="control-label">Teller ID</label>
							<div ng-switch on="data.editMode">
								<input type="text" class="form-control" ng-model="shiftDetailCashReport.sdcr_teller_id" ng-switch-when="edit">
								<p class="form-control-static" ng-switch-default>{{ shiftDetailCashReport.sdcr_teller_id }}</p>
							</div>
						</div>

						<!-- POS ID -->
						<div class="form-group">
							<label class="control-label">POS ID</label>
							<div ng-switch on="data.editMode">
								<input type="text" class="form-control" ng-model="shiftDetailCashReport.sdcr_pos_id" ng-switch-when="edit">
								<p class="form-control-static" ng-switch-default>{{ shiftDetailCashReport.sdcr_pos_id }}</p>
							</div>
						</div>
					</div>

					<div class="col-sm-4">
						<!-- Date -->
						<div class="form-group">
							<label class="control-label">Business Date</label>
							<div ng-switch on="data.editMode">
								<input type="date" class="form-control" ng-model="shiftDetailCashReport.sdcr_business_date" ng-required="true" ng-switch-when="edit">
								<p class="form-control-static" ng-switch-default>{{ shiftDetailCashReport.sdcr_business_date | parseDate | date: 'yyyy-MM-dd' }}</p>
							</div>
							<!--
							<div class="input-group" ng-if="data.editMode == 'edit'">
								<input type="text" class="form-control" uib-datepicker-popup="{{ data.datepicker.format }}" is-open="data.datepicker.opened"
										min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
										ng-model="shiftDetailCashReport.sdcr_business_date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats">
								<span class="input-group-btn">
									<button type="button" class="btn btn-default" ng-click="showDatePicker()"><i class="glyphicon glyphicon-calendar"></i></button>
								</span>

							</div>
							<div ng-if="data.editMode != 'edit'">
								<p class="form-control-static">{{ shiftDetailCashReport.sdcr_business_date | parseDate | date: 'yyyy-MM-dd' }}</p>
							</div>
							-->
						</div>
					</div>


					<div class="col-sm-4">
						<!-- Login Time -->
						<div class="form-group">
							<label class="control-label">Login Time</label>
							<div ng-switch on="data.editMode">
								<input type="datetime-local" class="form-control" ng-model="shiftDetailCashReport.sdcr_login_time" ng-switch-when="edit">
								<p class="form-control-static" ng-switch-default>{{ shiftDetailCashReport.sdcr_login_time | date : 'yyyy-MM-dd HH:mm' }}</p>
							</div>
						</div>

						<!-- Logout Time -->
						<div class="form-group">
							<label class="control-label">Logout Time</label>
							<div ng-switch on="data.editMode">
								<input type="datetime-local" class="form-control" ng-model="shiftDetailCashReport.sdcr_logout_time" ng-switch-when="edit">
								<p class="form-control-static" ng-switch-default>{{ shiftDetailCashReport.sdcr_logout_time | date : 'yyyy-MM-dd HH:mm' }}</p>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="panel panel-default">
		<table class="table table-condensed">
			<thead>
				<tr>
					<th class="text-center" rowspan="2">Row</th>
					<th class="text-left" rowspan="2">Card Profile</th>
					<th class="text-center" colspan="2">Issued</th>
					<th class="text-center" colspan="2">Add Value</th>
					<th class="text-center" colspan="2">Refund</th>
					<th class="text-center" colspan="2">Entry/Exit Mismatch</th>
					<th class="text-center" colspan="2">Excess Time</th>
					<th class="text-center" colspan="2">Product Sales</th>
					<th class="text-center" rowspan="2" ng-if="data.editMode != 'view'"></th>
				</tr>
				<tr>
					<th class="text-center">Quantity</th>
					<th class="text-center">Value</th>
					<th class="text-center">Quantity</th>
					<th class="text-center">Value</th>
					<th class="text-center">Quantity</th>
					<th class="text-center">Value</th>
					<th class="text-center">Quantity</th>
					<th class="text-center">Value</th>
					<th class="text-center">Quantity</th>
					<th class="text-center">Value</th>
					<th class="text-center">Quantity</th>
					<th class="text-center">Value</th>
				</tr>
			</thead>
			<tbody>
				<tr ng-repeat="( key, row ) in shiftDetailCashReport.items"
						ng-class="{
								danger: row.markedVoid || ( [<?php echo implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) );?>].indexOf( row.allocation_item_status ) != -1 ),
								deleted: ( [<?php echo implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) );?>].indexOf( row.allocation_item_status ) != -1 )
							}">
					<td class="text-center">{{ $index + 1 }}</td>
					<td class="text-left">{{ key + ' - ' + row.card_profile.profileName }}</td>
					<td class="text-center">{{ row.issued_property.sdcri_quantity == undefined ? '-' : ( row.issued_property.sdcri_quantity | number ) }}</td>
					<td class="text-right">{{ row.issued_property.sdcri_amount == undefined ? '-' : ( row.issued_property.sdcri_amount | number: 2 ) }}</td>
					<td class="text-center">{{ row.add_value_property.sdcri_quantity == undefined ? '-' : ( row.add_value_property.sdcri_quantity | number ) }}</td>
					<td class="text-right">{{ row.add_value_property.sdcri_amount == undefined ? '-' : ( row.add_value_property.sdcri_amount | number: 2 ) }}</td>
					<td class="text-center">{{ row.refund_property.sdcri_quantity == undefined ? '-' : ( row.refund_property.sdcri_quantity | number ) }}</td>
					<td class="text-right">{{ row.refund_property.sdcri_amount == undefined ? '-' : ( row.refund_property.sdcri_amount | number: 2 ) }}</td>
					<td class="text-center">{{ row.entry_exit_mismatch_property.sdcri_quantity == undefined ? '-' : ( row.entry_exit_mismatch_property.sdcri_quantity | number ) }}</td>
					<td class="text-right">{{ row.entry_exit_mismatch_property.sdcri_amount == undefined ? '-' : ( row.entry_exit_mismatch_property.sdcri_amount | number: 2 ) }}</td>
					<td class="text-center">{{ row.excess_time_property.sdcri_quantity == undefined ? '-' : ( row.excess_time_property.sdcri_quantity | number ) }}</td>
					<td class="text-right">{{ row.excess_time_property.sdcri_amount == undefined ? '-' : ( row.excess_time_property.sdcri_amount | number: 2 ) }}</td>
					<td class="text-center">{{ row.product_sales_property.sdcri_quantity == undefined ? '-' : ( row.product_sales_property.sdcri_quantity | number ) }}</td>
					<td class="text-right">{{ row.product_sales_property.sdcri_amount == undefined ? '-' : ( row.product_sales_property.sdcri_amount | number: 2 ) }}</td>
					<td class="text-center" ng-if="data.editMode != 'view'">
							<a href
									ng-if="shiftDetailCashReport.id == undefined"
									ng-click="removeReportItem( key )">
								<i class="glyphicon glyphicon-remove-circle"></i>
							</a>
							<input type="checkbox" value="{{ row.issued_property.id }}"
									ng-if="shiftDetailCashReport.id"
									ng-model="row.markedVoid">
					</td>
				</tr>
				<tr ng-if="emptyItems( shiftDetailCashReport.items )">
					<td colspan="15" class="text-center bg-warning">
						No Shift Detail Cash Report items
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	{{ angular.equals( shiftDetailCashReport.items, {} ) }}

	<div class="panel panel-default" ng-if="data.editMode != 'view'">
		<form>
			<div class="panel-body row">
				<!-- Card Profile -->
				<div class="form-group col-sm-12 col-md-6 col-lg-5">
					<label class="control-label">Card Profile</label>
					<select class="form-control"
							ng-model="input.card_profile"
							ng-options="card as card.id + ' - ' + card.profileName for card in data.cardProfiles track by card.id">
					</select>
				</div>
			</div>

			<div class="panel-body row">
				<!-- Issued -->
				<div class="form-group col-sm-12 col-md-3 col-lg-2">
					<label class="control-label">Issued</label>
					<input type="number" class="form-control" min="1" placeholder="Quantity" ng-model="input.issued_quantity" ng-keypress="addReportItem( $event )">
					<input type="number" class="form-control" min="1" placeholder="Value" ng-model="input.issued_amount" ng-keypress="addReportItem( $event )">
				</div>

				<!-- Add Value -->
				<div class="form-group col-sm-12 col-md-3 col-lg-2">
					<label class="control-label">Add Value</label>
					<input type="number" class="form-control" min="1" placeholder="Quantity" ng-model="input.add_value_quantity" ng-keypress="addReportItem( $event )">
					<input type="number" class="form-control" min="1" placeholder="Value" ng-model="input.add_value_amount" ng-keypress="addReportItem( $event )">
				</div>

				<!-- Refund -->
				<div class="form-group col-sm-12 col-md-3 col-lg-2">
					<label class="control-label">Refund</label>
					<input type="number" class="form-control" min="1" placeholder="Quantity" ng-model="input.refund_quantity" ng-keypress="addReportItem( $event )">
					<input type="number" class="form-control" min="1" placeholder="Value" ng-model="input.refund_amount" ng-keypress="addReportItem( $event )">
				</div>

				<!-- Entry/Exit Mismatch -->
				<div class="form-group col-sm-12 col-md-3 col-lg-2">
					<label class="control-label">En/Ex Mismatch</label>
					<input type="number" class="form-control" min="1" placeholder="Quantity" ng-model="input.entry_exit_mismatch_quantity" ng-keypress="addReportItem( $event )">
					<input type="number" class="form-control" min="1" placeholder="Value" ng-model="input.entry_exit_mismatch_amount" ng-keypress="addReportItem( $event )">
				</div>

				<!-- Excess Time -->
				<div class="form-group col-sm-12 col-md-3 col-lg-2">
					<label class="control-label">Excess Time</label>
					<input type="number" class="form-control" min="1" placeholder="Quantity" ng-model="input.excess_time_quantity" ng-keypress="addReportItem( $event )">
					<input type="number" class="form-control" min="1" placeholder="Value" ng-model="input.excess_time_amount" ng-keypress="addReportItem( $event )">
				</div>

				<!-- Product Sales -->
				<div class="form-group col-sm-12 col-md-3 col-lg-2">
					<label class="control-label">Product Sales</label>
					<input type="number" class="form-control" min="1" placeholder="Quantity" ng-model="input.product_sales_quantity" ng-keypress="addReportItem( $event )">
					<input type="number" class="form-control" min="1" placeholder="Value" ng-model="input.product_sales_amount" ng-keypress="addReportItem( $event )">
				</div>
			</div>
		</form>
	</div>

	<!-- Form buttons -->
	<div class="text-right">
		<button type="button" class="btn btn-primary" ng-click="saveReport()"
			ng-disabled="pendingAction"
			ng-if="shiftDetailCashReport.canEdit()">
			<i class="glyphicon" ng-class="{ 'glyphicon-time': data.saveButton.icon == 'time', 'glyphicon-floppy-disk': data.saveButton.icon == 'floppy-disk' }"> </i>
			Save Report
		</button>
		<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'shiftDetailCashReports' })">Close</button>
	</div>
</div>


<div ng-if="! checkPermissions( 'allocations', 'view' )">
	<h1>Access Denied</h1>
	<p>You are not authorized to view this page. If you believe that this is incorrect please contact your system administrator.</p>
</div>