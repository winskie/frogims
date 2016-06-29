<div>
    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top navbar-main" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
                    <span class="sr-only">Toggle Navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">FROG Ticket Management Inventory System</a>
            </div>
            <div id="navbar" class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
                    <li><a ui-sref="main.dashboard">Dashboard</a></li>
                    <li><a ui-sref="main.store">Store</a></li>
                    <?php
                    if( is_admin() )
                    {
                        echo '<li><a ui-sref="main.admin">Admin</a></li>';
                    }
                    ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li uib-dropdown>
                        <a href class="navbar-link" uib-dropdown-toggle>
                            <span class="navbar-username">{{ sessionData.currentUser.username }} <span ng-if="sessionData.isAdmin" class="label label-warning">ADMIN</span></span>
                        </a>
                        <ul class="dropdown-menu" uib-dropdown-menu>
                            <li>
                                <a class="navbar-link" ui-sref="main.user({ userItem: sessionData.currentUser })">User account</a>
                                <a class="navbar-link" href="<?php echo site_url( '/login/logout');?>">Log out</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <nav class="navbar navbar-default navbar-fixed-top navbar-secondary" role="navigation">
        <div class="container">
            <ul class="nav navbar-nav" ng-switch on="canChangeStore">
                <li uib-dropdown ng-switch-when="true">
                    <a href uib-dropdown-toggle>
                        {{ sessionData.currentStore.store_name }} <span class="label label-info">{{ sessionData.currentStore.store_code}}</span><span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" uib-dropdown-menu>
                        <li ng-repeat="store in sessionData.userStores">
                            <a href ng-click="changeStore( store )">{{ store.store_name }} <span class="label label-info">{{ store.store_code }}</span></a>
                        </li>
                    </ul>
                </li>
                <li ng-switch-when="false">
                    <a href>{{ sessionData.currentStore.store_name }} <span class="label label-info">{{ sessionData.currentStore.store_code}}</span></a>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li uib-dropdown>
                    <a href uib-dropdown-toggle>
                        {{ sessionData.currentShift.description }} <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" uib-dropdown-menu>
                        <li ng-repeat="shift in sessionData.storeShifts">
                            <a href ng-click="changeShift( shift )">{{ shift.description }}</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div ng-controller="NotificationController" class="notification_wrapper">
            <div ng-repeat="message in data.messages" class="notification {{ message.type }}" ng-class="{ 'notice-visible': message.visible }">
                <p>{{ message.message }}</p>
            </div>
        </div>
        <div id="content" ui-view></div>
    </div>

    <!-- Debug -->
    <div ng-bind-html="debug"></div>
</div>