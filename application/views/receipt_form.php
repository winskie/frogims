<div class="modal-header">
	<h3 class="modal-title">Transfer Receipt - {{ item.item_description }}</h3>
</div>
<div class="modal-body">
	<form>
		
		<!-- Receipt Date/Time -->
		<div>
			<div class="form-group col-sm-6">
				<label for="receipt_date">Date of Receipt</label>
				<div class="input-group">
					<input id="receipt_date" name="receipt_date" type="text" class="form-control" uib-datepicker-popup="{{format}}"
							ng-model="item.receipt_datetime" is-open="datepicker.opened"
							min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
							ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
					<span class="input-group-btn">
						<button type="button" class="btn btn-default" ng-click="showDatePicker()"><i class="glyphicon glyphicon-calendar"></i></button>
					</span>
				</div>
			</div>
			
			<div class="form-group col-sm-6" ng-if="externalSource">
				<label for="receipt_date">Date of Transfer</label>
				<div class="input-group">
					<input id="transfer_date" name="transfer_date" type="text" class="form-control" uib-datepicker-popup="{{format}}"
							ng-model="item.transfer_datetime" is-open="datepicker.opened"
							min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
							ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
					<span class="input-group-btn">
						<button type="button" class="btn btn-default" ng-click="showDatePicker()"><i class="glyphicon glyphicon-calendar"></i></button>
					</span>
				</div>
			</div>
		</div>
		<div class="clearfix"></div>

		<!-- Source -->
		<div ng-switch on="itemType">
			<div class="form-group col-sm-12" ng-switch-when="item">
				<label for="source">{{ sourceLabel }}</label>
				<div class="input-group">
					<div class="input-group-btn">
						<button type="button" class="btn btn-default" ng-click="toggleSource()"><i class="glyphicon glyphicon-refresh"></i></button>
					</div>
					<select id="source" name="stores" class="form-control"
							ng-model="sourceStore"
							ng-options="store.name for store in stores track by store.id"
							ng-show="!externalSource"
							ng-change="changeSource">
					</select>
					<input id="source" name="source" type="text" class="form-control" placeholder="Enter name of external source"
							ng-model="item.origin_name"
							ng-show="externalSource">
				</div>
			</div>
			
			<div class="form-group col-sm-12" ng-switch-default>
				<label for="source">{{ sourceLabel }}</label>
				<div class="input-group">
					<p class="form-control-static">{{ item.origin_name }}</p>
				</div>
			</div>
		</div>

		<!-- Quantities -->
		<div ng-switch on="itemType">
			<div class="row col-sm-12" ng-switch-when="item">
				<div class="form-group col-sm-4">
					<label for="quantity">Quantity</label>
					<input id="quantity" name="quantity" type="number" class="form-control" placeholder="Quantity" ng-model="item.transfer_quantity">
				</div>
			</div>
			
			<div class="row col-sm-12" ng-switch-default>
				<div class="form-group col-sm-4">
					<label for="quantity">Quantity</label>
					<p class="form-control-static">{{ item.transfer_quantity | number }}</p>
				</div>
			</div>
		</div>

		<!-- Other Information -->
		<div class="form-group col-sm-12">
			<label for="person">Recipient</label>
			<input id="person" name="person" type="text" class="form-control" placeholder="Name of recipient" ng-model="item.recipient_name">
		</div>
		<div class="clearfix"></div>
	</form>
</div>
<div class="modal-footer">
	<button class="btn btn-primary" type="button" ng-click="receive()"><i class="glyphicon glyphicon-download"></i> Receive</button>
	<button class="btn btn-default" type="button" ng-click="cancel()">Cancel</button>
</div>