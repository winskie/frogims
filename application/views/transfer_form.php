<div class="modal-header">
	<h3 class="modal-title">Transfer Out - {{ item.item_description }}</h3>
</div>
<div class="modal-body">
	<form>
		<div class="form-group col-sm-6">
			<label for="transfer_date">Date of Transfer</label>
			<div class="input-group">
				<input id="transfer_date" name="transfer_date" type="text" class="form-control" uib-datepicker-popup="{{ format }}" ng-model="date" is-open="datepicker.opened"
						min-date="minDate" max-date="maxDate" datepicker-options="dateOptions" date-disabled="disabled(date, mode)"
						ng-required="true" close-text="Close" alt-input-formats="altInputFormats" />
				<span class="input-group-btn">
					<button type="button" class="btn btn-default" ng-click="showDatePicker()"><i class="glyphicon glyphicon-calendar"></i></button>
				</span>
			</div>
		</div>
		<div class="clearfix"></div>

		<!-- Destination -->
		<div class="form-group col-sm-12">
			<label for="destination">{{ destinationLabel }}</label>
			<div class="input-group">
				<div class="input-group-btn">
					<button type="button" class="btn btn-default" ng-click="toggleDestination()"><i class="glyphicon glyphicon-refresh"></i></button>
				</div>
				<select id="destination" name="stores" class="form-control"
						ng-model="destinationStore"
						ng-options="store.name for store in stores track by store.id"
						ng-show="!externalDestination">
				</select>
				<input id="destination" name="destination" type="text" class="form-control" placeholder="Enter name of external destination"
						ng-model="destinationName"
						ng-show="externalDestination">
			</div>
		</div>

		<!-- Quantities -->
		<div class="row col-sm-12" >
			<div class="form-group col-sm-4">
				<label for="quantity">Regular</label>
				<input id="quantity" name="quantity" type="number" class="form-control" placeholder="Quantity" ng-model="quantity">
			</div>
		</div>

		<!-- Other Information -->
		<div class="form-group col-sm-12">
			<label for="person">Person-in-Charge</label>
			<input id="person" name="person" type="text" class="form-control" placeholder="Name of person-in-charge of transfer" ng-model="person">
		</div>
		<div class="clearfix"></div>
	</form>
</div>
<div class="modal-footer">
	<button class="btn btn-primary" type="button" ng-click="save( 'scheduled' )"><i class="glyphicon glyphicon-time"></i> Schedule</button>
	<button class="btn btn-default" type="button" ng-click="save( 'approved' )"><i class="glyphicon glyphicon-ok"></i> Approve</button>
	<button class="btn btn-default" type="button" ng-click="cancel()">Cancel</button>
</div>