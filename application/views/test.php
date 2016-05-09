<!DOCTYPE html>
<html lang="en" ng-app="FROGIMS">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
    	<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>FROG Inventory Management System</title>

		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/main.css' );?>" />

		<script src="<?php echo base_url( 'resources/js/angular.min.js' );?>"></script>
		<script src="<?php echo base_url( 'resources/js/angular-animate.min.js' );?>"></script>
		<script src="<?php echo base_url( 'resources/js/ui-bootstrap-tpls-1.1.2.min.js' );?>"></script>
		<script src="<?php echo base_url( 'app.js' );?>"></script>
		<script src="<?php echo base_url( 'controllers.js' );?>"></script>
		<script src="<?php echo base_url( 'services.js' );?>"></script>
	</head>
	<body ng-controller="StoreController">
		<nav class="navbar navbar-inverse navbar-fixed-top">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
						<span class="sr-only">Toggle Navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="#">FROG Inventory Management System</a>
				</div>
				<div id="navbar" class="collapse navbar-collapse">
					<ul class="nav navbar-nav">
						<li><a href="#">Dashboard</a></li>
						<li class="active"><a href="#">Store</a></li>
						<li><a href="#">Cashroom</a></li>
						<li><a href="#">Satellite</a></li>
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<li><a href="#">{{ user.username }}</a></li>
					</ul>
				</div>
			</div>
		</nav>
		<div class="container">
			<div id="content">
				<div class="panel panel-default">
					<div class="panel-body">
						<form class="form-inline">
							<div class="form-group">
								<label for="stores">Select store:</label>
								<select name="stores" class="form-control"
										ng-model="currentStore"
										ng-options="store.name for store in stores track by store.id"
										ng-change="changeStore()"></select>
							</div>
						</form>
					</div>
				</div>
				<div>
					<div class="panel panel-default">
						<div class="panel-heading"
							<h3 class="panel-title"><i class="glyphicon glyphicon-book"></i> Inventory</h3>
						</div>
						<table class="table">
							<thead>
								<tr>
									<th>Item</th>
									<th>Description</th>
									<th class="text-right">Quantity</th>
									<th class="text-right">Buffer Level</th>
									<th class="text-right">Reserved</th>
									<th class="text-right"></th>
								</tr>
							</thead>
							<tbody>
								<tr ng-repeat="item in items" ng-class="{info: currentItem == item}">
									<td>{{ item.item_name }}</td>
									<td>{{ item.item_description }}</td>
									<td class="text-right">{{ item.quantity | number }}</td>
									<td class="text-right">{{ item.buffer_level | number }}</td>
									<td class="text-right">{{ item.reserved | number }}</td>
									<td class="text-right">
										<div class="btn-group" uib-dropdown>
											<button id="split-button" type="button" class="btn btn-primary" ng-click="showTransferForm( item )">Transfer Out</button>
											<button type="button" class="btn btn-primary" uib-dropdown-toggle>
												<span class="caret"></span>
											</button>
											<ul uib-dropdown-menu role="menu">
												<li role="menuitem"><a href="#" ng-click="showReceiptForm( item, 'item' )">Receive</a></li>
												<li class="divider"></li>
												<li role="menuitem"><a href="#">Adjust</a></li>
											</ul>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<uib-tabset>
						<uib-tab>
							<uib-tab-heading>
								<i class="glyphicon glyphicon-transfer"></i> Transactions Summary
							</uib-tab-heading>
							<div class="panel panel-default">
								<div class="panel-heading">
									<h3 class="panel-title pull-left">Transactions Summary</h3>
									<button class="btn btn-default btn-sm pull-right" ng-click="updateTransactions()">
										<i class="glyphicon glyphicon-refresh"></i>
									</button>
									<div class="clearfix"></div>
								</div>
								<table class="table table-hover">
									<thead>
										<tr>
											<th class="text-left">Date / Time</th>
											<th class="text-left">Item</th>
											<th class="text-left">Transaction Type</th>
											<th class="text-right">Quantity</th>
											<th class="text-right">Balance</th>
										</tr>
									</thead>
									<tbody>
										<tr ng-repeat="transaction in transactions">
											<td>{{ transaction.transaction_datetime }}</td>
											<td>{{ transaction.item_name }}</td>
											<td>{{ transaction.transaction_type }}</td>
											<td class="text-right">{{ transaction.transaction_quantity | number }}</td>
											<td class="text-right">{{ transaction.current_quantity | number }}</td>
										</tr>
										<tr ng-show="!transactions.length">
											<td colspan="5" class="text-center">No transaction data available</td>
										</tr>
									</tbody>
								</table>
							</div>
						</uib-tab>

						<uib-tab heading="Transfers">
							<div class="panel panel-default">
								<div class="panel-heading">
									<h3 class="panel-title pull-left">Transfers</h3>
									<button class="btn btn-default btn-sm pull-right" ng-click="updateTransfers()">
										<i class="glyphicon glyphicon-refresh"></i>
									</button>
									<div class="clearfix"></div>
								</div>
								<table class="table table-hover">
									<thead>
										<tr>
											<th class="text-left">Date / Time</th>
											<th class="text-left">Item</th>
											<th class="text-left">Destination</th>
											<th class="text-right">Quantity</th>
											<th class="text-center">Status</th>
											<th class="text-center"></th>
										</tr>
									</thead>
									<tbody>
										<tr ng-repeat="transfer in transfers">
											<td>{{ transfer.transfer_datetime }}</td>
											<td>{{ transfer.item_name }}</td>
											<td>{{ transfer.destination_name }}</td>
											<td class="text-right">{{ transfer.transfer_quantity | number }}</td>
											<td class="text-center">{{ transfer.transfer_status }}</td>
											<td class="text-right">
												<div class="animate-switch-container" ng-switch on="transfer.transfer_status">

													<div class="animate-switch" ng-switch-when="<?php echo TRANSFER_PENDING;?>">
														<div class="btn-group btn-block" uib-dropdown>
															<button id="split-button" type="button" class="btn btn-primary col-sm-9 col-md-10" ng-click="approve( transfer )">Approve</button>
															<button type="button" class="btn btn-primary col-sm-3 col-md-2" uib-dropdown-toggle>
																<span class="caret"></span>
															</button>
															<ul uib-dropdown-menu role="menu">
																<li role="menuitem"><a href="#">Edit...</a></li>
																<li role="menuitem"><a href="#" ng-click="cancel( transfer )">Cancel</a></li>
															</ul>
														</div>
													</div>

													<div class="animate-switch" ng-switch-when="<?php echo TRANSFER_APPROVED;?>">
														<div class="btn-group btn-block" uib-dropdown>
															<button id="split-button" type="button" class="btn btn-default col-sm-9 col-md-10" ng-click="showTransferDetails( transfer )">View details...</button>
															<button type="button" class="btn btn-default col-sm-3 col-md-2" uib-dropdown-toggle>
																<span class="caret"></span>
															</button>
															<ul uib-dropdown-menu role="menu">
																<li role="menuitem"><a href="#" ng-click="cancel( transfer )">Cancel</a></li>
															</ul>
														</div>
													</div>

													<div class="animate-switch" ng-switch-default>
														<button type="button" class="btn btn-default btn-block" ng-click="showTransferDetails( transfer )">View details...</button>
													</div>

												</div>
											</td>
										</tr>
										<tr ng-show="!transfers.length">
											<td colspan="6" class="text-center">No transfer transaction data available</td>
										</tr>
									</tbody>
								</table>
							</div>
						</uib-tab>

						<uib-tab heading="Receipts">
							<div class="panel panel-default">
								<div class="panel-heading">
									<h3 class="panel-title pull-left">Receipts</h3>
									<button class="btn btn-default btn-sm pull-right" ng-click="updateReceipts()">
										<i class="glyphicon glyphicon-refresh"></i>
									</button>
									<div class="clearfix"></div>
								</div>
								<table class="table">
									<thead>
										<tr>
											<tr>
											<th class="text-left">Date / Time</th>
											<th class="text-left">Item</th>
											<th class="text-left">Source</th>
											<th class="text-right">Quantity</th>
											<th class="text-center">Status</th>
											<th class="text-center"></th>
										</tr>
									</thead>
									<tbody>
										<tr ng-repeat="receipt in receipts">
											<td>{{ receipt.transfer_datetime }}</td>
											<td>{{ receipt.item_name }}</td>
											<td>{{ receipt.origin_name }}</td>
											<td class="text-right">{{ receipt.transfer_quantity | number }}</td>
											<td class="text-center">{{ receipt.transfer_status }}</td>
											<td class="text-right">
												<div class="animate-switch-container" ng-switch on="receipt.transfer_status">

													<div class="animate-switch" ng-switch-when="<?php echo TRANSFER_APPROVED;?>">
														<div class="btn-group btn-block" uib-dropdown>
															<button id="split-button" type="button" class="btn btn-primary col-sm-9 col-md-10" ng-click="receive( receipt )">Receive</button>
															<button type="button" class="btn btn-primary col-sm-3 col-md-2" uib-dropdown-toggle>
																<span class="caret"></span>
															</button>
															<ul uib-dropdown-menu role="menu">
																<li role="menuitem"><a href="#" ng-click="showReceiptForm( receipt, 'transfer' )">Edit Receipt...</a></li>
															</ul>
														</div>
													</div>

													<div class="animate-switch" ng-switch-default>
														<button type="button" class="btn btn-default btn-block" ng-click="showTransferDetails( receipt )">View details...</button>
													</div>

												</div>
											</td>
										</tr>
										<tr ng-show="!receipts.length">
											<td colspan="6" class="text-center">No receipt transaction data available</td>
										</tr>
									</tbody>
								</table>
							</div>
						</uib-tab>

						<uib-tab heading="Adjustments">

						</uib-tab>
					</uib-tabset>
				</div>
			</div>
		</div>
	</body>
</html>