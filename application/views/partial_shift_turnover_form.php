<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title pull-left"> Inventory</h3>
		<div class="pull-right">
			<button class="btn btn-default btn-sm" ng-click="updateBalance()">
				<i class="glyphicon glyphicon-refresh"></i>
			</button>
		</div>
		<div class="clearfix"></div>
	</div>
	<table class="table table-condensed">
		<thead>
			<tr>
				<th>Item</th>
				<th class="text-center">Group</th>
				<th>Description</th>
				<th class="text-center">Unit</th>
				<th class="text-right" style="width: 100px;">Turnover</th>
				<th class="text-right" style="width: 100px;">A.Beginning</th>
				<th class="text-right" style="width: 100px;">Movement</th>
				<th class="text-right" style="width: 100px;">C.Ending</th>
				<th class="text-right" style="width: 100px;">A.Endingl</th>
			</tr>
		</thead>
		<tbody>
			<tr ng-repeat="item in shiftTurnover.items"
				ng-class="{info: currentItem == item,
						'text-extra-muted': ( item.quantity === 0 && item.reserved === 0 && ( item.quantity - item.reserved ) === 0 ) }">
				<td>{{ item.item_name }}</td>
				<td class="text-center">{{ item.item_group }}</td>
				<td>{{ item.item_description }}</td>
				<td class="text-center">{{ item.item_unit }}</td>
				<td class="text-right">{{ item.previous_balance | number }}</td>
				<td class="text-right">
					<input class="form-control input-sm text-right" type="number" ng-model="item.sti_beginning_balance">
				</td>
				<td class="text-right">{{ item.movement | number }}</td>
				<td class="text-right">{{ item.computed_ending_balance | number }}</td>
				<td class="text-right">
					<input class="form-control input-sm text-right" type="number" ng-model="item.sti_ending_balance">
				</td>
			</tr>
			<tr ng-if="!shiftTurnover.items.length">
				<td colspan="7" class="text-center">No inventory items available</td>
			</tr>
		</tbody>
	</table>
</div>