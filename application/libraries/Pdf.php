<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once './resources/libs/tcpdf/tcpdf.php';

class Pdf extends TCPDF
{
    function __construct()
    {
        parent::__construct();
    }
}