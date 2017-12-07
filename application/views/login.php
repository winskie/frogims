<!DOCTYPE html>
<html lang="en" ng-app="FROGIMS">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE-edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>FROG Ticket Management Inventory System Login</title>

        <link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/bootstrap.min.css' );?>" />
        <link rel="stylesheet" type="text/css" href="<?php echo base_url( 'resources/css/login.css' );?>" />
    </head>
    <body>
        <div class="container">
            <div class="jumbotron app-title">
                <h2>FROG Ticket Inventory and Cash Management System</h2>
            </div>
            <div class="panel panel-default form-sign-in">
                <div class="panel-body">
                    <form name="loginForm" method="POST">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="glyphicon glyphicon-user"></i>
                                </span>
                                <?php
                                $username = $this->session->flashdata( 'username' );
                                ?>
                                <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo $username;?>" <?php echo ( $username ? '' : 'autofocus' );?>>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="glyphicon glyphicon-lock"></i>
                                </span>
                                <input type="password" name="password" class="form-control" placeholder="Password" <?php echo ( $username ? 'autofocus' : '' );?>>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">Sign In</button>
                        </div>
                        <?php
                        if( $this->session->flashdata( 'error' ) )
                        {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $this->session->flashdata( 'error' );?>
                        </div>
                        <?php
                        }
                        ?>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>