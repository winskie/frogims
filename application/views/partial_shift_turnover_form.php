<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Shift Turnover</h3>
	</div>
	<div class="panel-body">
		<form class="form-inline">
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label">Current shift:</label>
					<select class="form-control"
							ng-model="data.currentShift"
							ng-change="onChangeShift()"
							ng-options="shift as shift.description for shift in data.turnoverShifts track by shift.id">
					</select>
				</div>
				<div class="form-group">
					<div class="input-group">
						<input type="text" class="form-control" uib-datepicker-popup="{{ data.turnoverFromDatepicker.format }}" is-open="data.turnoverFromDatepicker.opened"
							min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
							ng-model="shiftTurnover.st_from_date" ng-required="true" close-text="Close" alt-input-formats="altInputFormats",
							ng-change="onChangeShift()" />
						<span class="input-group-btn">
							<button type="button" class="btn btn-default" ng-click="showDatePicker( 'fromDate' )"><i class="glyphicon glyphicon-calendar"></i></button>
						</span>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="form-group" style="margin-right: 20px;">
					<label class="control-label">Status:</label>
					<p class="form-control-static">{{ shiftTurnover.st_status ? lookup( 'shiftTurnoverStatus', shiftTurnover.st_status ) : 'Pending' }}</p>
				</div>
				<div class="form-group">
					<label class="control-label">Turnover to:</label>
					<p class="form-control-static">{{ data.nextShift.description }} - {{ shiftTurnover.st_to_date | date: 'fullDate' }}</p>
				</div>
			</div>
		</form>

	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title pull-left">Inventory</h3>
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
				<th class="vert-middle" rowspan="2">Item</th>
				<th class="text-center vert-middle" rowspan="2">Group</th>
				<th class="vert-middle" rowspan="2">Description</th>
				<th rowspan="2" class="text-center vert-middle">Unit</th>
				<th colspan="2" class="text-center">Beginning Balance</th>
				<th rowspan="2" class="text-center vert-middle" style="width: 100px;">Movement</th>
				<th colspan="2" class="text-center">Ending Balance</th>
			</tr>
			<tr>
				<th class="text-center" style="width: 100px;">Turnover</th>
				<th class="text-center" style="width: 100px;">Actual</th>
				<th class="text-center" style="width: 100px;">System</th>
				<th class="text-center" style="width: 100px;">Actual</th>
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
				<td class="text-center">{{ item.previous_balance ? ( item.previous_balance | number ) : '---' }}</td>
				<td class="text-right" ng-switch on="shiftTurnover.st_status == 2 || data.editMode == 'view'">
					<input class="form-control input-sm text-right" type="number" tabindex="{{ $index + 1 }}"
						ng-switch-when="false"
						ng-model="item.sti_beginning_balance">
					<span ng-switch-default>{{ item.sti_beginning_balance }}</span>
				</td>
				<td class="text-center">{{ item.movement ? ( item.movement | number ) : '---' }}</td>
				<td class="text-center">{{ ( item.sti_beginning_balance ? item.sti_beginning_balance : 0 ) + ( item.movement ? item.movement : 0 ) | number }}</td>
				<td class="text-right" ng-switch on="shiftTurnover.st_status == 2 || data.editMode == 'view'">
					<input class="form-control input-sm text-right" type="number" tabindex="{{  ( shiftTurnover.items.length ) + $index + 1 }}"
						ng-switch-when="false"
						ng-model="item.sti_ending_balance"
						ng-disabled="shiftTurnover.st_status == null">
					<span ng-switch-default>{{ item.sti_ending_balance }}</span>
				</td>
			</tr>
			<tr ng-if="!shiftTurnover.items.length">
				<td colspan="7" class="text-center">No inventory items available</td>
			</tr>
		</tbody>
	</table>
</div>

<!-- Form buttons -->
<div class="text-right">
	<button type="button" class="btn"
			ng-if="shiftTurnover.st_status != 2 && data.editMode != 'view'"
			ng-class="{ 'btn-default': shiftTurnover.st_status == 1, 'btn-primary': shiftTurnover.st_status != 1 }"
			ng-click="saveTurnover()"
			<i class="glyphicon glyphicon-ok"></i> {{ shiftTurnover.st_status == null ? 'Start Shift' : 'Update Beginning Balances' }}
	</button>
	<button type="button" class="btn btn-primary"
			ng-if="shiftTurnover.st_status == 1 && data.editMode != 'view'"
			ng-click="saveTurnover( 'close' )"
			<i class="glyphicon glyphicon-ok"></i> End Shift
	</button>
	<button type="button" class="btn btn-success" ng-click="receiveTransfer()"
			ng-if="( data.editMode == 'externalReceipt' || ( transferItem.transfer_status == <?php echo TRANSFER_APPROVED;?> && !data.isExternalDestination ) )
					&& transferItem.destination_id == sessionData.currentStore.id
					&& checkPermissions( 'transfers', 'edit' )
					&& data.editMode != 'view'">
			<i class="glyphicon glyphicon-ok"></i> Receive
	</button>
	<button type="button" class="btn btn-default" ui-sref="main.store({ activeTab: 'inventory' })">Close</button>
</div>