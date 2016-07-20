<div class="panel panel-default" ng-if="checkPermissions( 'dashboard', 'history' )">
    <div class="panel-body">
        <highcharts chart="history"></highcharts>
    </div>
</div>

<div class="panel panel-default" ng-if="checkPermissions( 'dashboard', 'week_movement' )">
    <div class="panel-body">
        <highcharts chart="week_movement"></highcharts>
    </div>
</div>

<div class="panel panel-default" ng-if="checkPermissions( 'dashboard', 'inventory' )">
    <div class="panel-body">
        <highcharts chart="inventory"></highcharts>
    </div>
</div>

<div class="panel panel-default" ng-if="checkPermissions( 'dashboard', 'distribution' )">
    <div class="panel-body">
        <highcharts chart="distribution"></highcharts>
    </div>
</div>

<button type="button" ng-click="updateDashboard()">Refresh!</button>