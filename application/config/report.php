<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['report_mode'] = 'JasperReports';

// JasperServer
$config['jasper_use_ssl'] = FALSE;
$config['jasper_server'] = 'db.afcs.lan';
$config['jasper_port'] = 8080;
$config['jasper_reports_path'] = 'jasperserver/rest_v2/reports/Reports';
$config['jasper_username'] = 'jasperadmin';
$config['jasper_password'] = 'jasperadmin';