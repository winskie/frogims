<div class="modal-header">
	<h3 class="modal-title" id="modal-title">Select Turnover Items</h3>
</div>
<div class="modal-body" id="modal-body">
	<form class="form-inline" style="margin-bottom: 15px;">
		<div class="form-group">
			<label class="control-label">Business Date</label>
			<div class="input-group">
				<input type="text" class="form-control" uib-datepicker-popup="{{ $ctrl.input.datepicker.format }}" is-open="$ctrl.input.datepicker.opened"
					min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
					ng-model="$ctrl.input.datepicker.value" ng-change="$ctrl.changeDate()" close-text="Close" alt-input-formats="altInputFormats" />
				<span class="input-group-btn">
					<button type="button" class="btn btn-default" ng-click="$ctrl.showDatePicker()"><i class="glyphicon glyphicon-calendar"></i></button>
				</span>
			</div>
		</div>
	</form>
	<table class="table table-condensed">
		<thead>
			<tr>
				<th class="text-center" style="width: 50px;">Row</th>
				<th>Item Description</th>
				<th class="text-center">Category</th>
				<th class="text-center">Quantity</th>
				<th class="text-center">Source</th>
				<th class="text-center">Shift</th>
				<th>
					<input type="checkbox" ng-model="$ctrl.data.checkAllItems" ng-click="$ctrl.toggleCheckboxes()">
				</th>
			</tr>
		</thead>
		<tbody>
			<tr ng-repeat="row in $ctrl.data.items">
				<td class="text-center">{{ $index + 1 }}</td>
				<td>{{ row.item_description }}</td>
				<td class="text-center">{{ row.category }}</td>
				<td class="text-center">{{ row.quantity | number }}</td>
				<td class="text-center">{{ row.item_source + ( row.assignee ? ( ' - ' + row.assignee ) : '' ) }}</td>
				<td class="text-center">{{ row.shift_num }}</td>
				<td>
					<input type="checkbox" ng-model="row.selected" ng-if="!row.turnover_id">
				</td>
			</tr>
		</tbody>
	</table>
</div>
<div class="modal-footer">
	<button class="btn btn-primary" type="button" ng-click="$ctrl.submit()">Add</button>
	<button class="btn btn-default" type="button" ng-click="$ctrl.close()">Close</button>
</div>