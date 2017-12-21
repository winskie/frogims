<div class="modal-header">
	<h3 class="modal-title" id="modal-title">Select Sale Collection Items - {{ $ctrl.data.businessDate | date: 'longDate' }}</h3>
</div>
<div class="modal-body" id="modal-body">
	<table class="table table-condensed">
		<thead>
			<tr>
				<th class="text-center" style="width: 50px;">Row</th>
				<th>Item Description</th>
				<th class="text-right">Quantity
				<th class="text-right">Total Amount</th>
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
				<td>{{ row.item_name }}</td>
				<td class="text-right">{{ row.quantity | number }}</td>
				<td class="text-right">{{ row.total_amount | number: 2 }}</td>
				<td class="text-center">
					{{ ( row.assignee_type == 1 ? ''  : 'TVM ' ) + row.assignee }}
					&nbsp;<span class="label label-info">#{{ row.allocation_id }}</span>
				</td>
				<td class="text-center">{{ row.shift_num }}</td>
				<td>
					<input type="checkbox" ng-model="row.selected">
				</td>
			</tr>
			<tr ng-if="$ctrl.data.items.length">
				<th colspan="2" class="text-right">Total Amount</th>
				<td></td>
				<td class="text-right">{{ ( $ctrl.data.items | filter: { selected: true } : true ) | sumByColumn: 'total_amount' : 'float' | number : 2  }}</td>
				<td></td>
				<td></td>
			</tr>
			<tr ng-if="$ctrl.data.items.length == 0">
				<td colspan="7" class="text-center bg-warning">No remaining sales collection for deposit</td>
			</tr>
		</tbody>
	</table>
</div>
<div class="modal-footer">
	<button class="btn btn-primary" type="button" ng-click="$ctrl.submit()">Add</button>
	<button class="btn btn-default" type="button" ng-click="$ctrl.close()">Close</button>
</div>