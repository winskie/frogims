<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// TODO: Extend MY_Controller instead after fixing session checking
class Report extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->config->load( 'report' );
	}

    public function get_report_mode()
    {
        $response = array(
                'status' => 'ok',
                'report_mode' => $this->config->item( 'report_mode' )
            );

        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }

    public function html( $report_path, $params )
    {
        $this->load->view( $report_path, $params );
    }

	private function _generate_report( $report_path, $format = NULL, $params = array() )
	{
        if( $this->config->item( 'report_mode' ) == 'JasperReports' )
        {
            // Get default format
            if( is_null( $format ) )
            {
                $format = 'pdf';
            }

            // Set format defaults
            switch( $format )
            {
                case 'html':
                    $format_ext = 'html';
                    $content_type = 'text/html';
                    break;

                case 'pdf':
                    $format_ext = 'pdf';
                    $content_type = 'application/pdf';
                    break;

                default:
                    return FALSE;
            }

            // Set default report server user credentials
            $params = array_merge( array(
                    'j_username' => $this->config->item( 'jasper_username' ),
                    'j_password' => $this->config->item( 'jasper_password' )
                ), $params );

            // temporary report file
            $tempfile_prefix = isset( $params['tempfile_prefix'] ) ? $params['tempfile_prefix'] : '_report_';
            $temp_file = tempnam( sys_get_temp_dir(), $tempfile_prefix ).'.'.$format_ext;

            $report_url = ( $this->config->item( 'jasper_use_ssl' ) ? 'https' : 'http' ).'://'
                    .$this->config->item( 'jasper_server' )
                    .( $this->config->item( 'jasper_port' ) ? ':'.$this->config->item( 'jasper_port') : '' ).'/'
                    .$this->config->item( 'jasper_reports_path' ).'/';

            $url = $report_url.$report_path.'.'.$format_ext.'?'.http_build_query( $params);

            try
            {
                $fp = fopen( $temp_file, 'w+' );
                $ch = curl_init( $url );
                curl_setopt_array($ch, array(
                        CURLOPT_URL => $url,
                        CURLOPT_BINARYTRANSFER => 1,
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_FILE => $fp,
                        CURLOPT_TIMEOUT => 50,
                        CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
                    ) );

                $results = curl_exec( $ch );

                if( curl_exec( $ch ) === false )
                {
                    echo 'error: '.curl_error( $ch );
                    return FALSE;
                }
                else
                {
                    $output_filename = 'report.pdf';
                    // Output the file
                    header( 'Content-type: '.$content_type );
                    //header( 'Content-Disposition: inline; filename="'.$output_filename.'"' );
                    header( 'Content-Disposition: attachment; filename="'.$output_filename.'"' );
                    header( 'Content-Transfer-Encoding: binary' );
                    header( 'Accept-Ranges: bytes' );
                    readfile( $temp_file );
                }

                fclose( $fp );
            }
            catch ( Exception $e )
            {
                echo 'exception: '.$e;
                return FALSE;
            }
        }
        else
        {
            $this->load->view( 'reports/'.$report_path );
        }
	}


	function delivery_receipt( $report_mode = 'JasperReports' )
	{
		$params = $this->input->get();

        $params = array_merge( array(
                'TRANSFER_ID' => NULL,
                'PREPARED_BY' => NULL,
                'PREPARED_BY_POSITION' => NULL,
                'CHECKED_BY' => NULL,
                'CHECKED_BY_POSITION' => NULL,
                'BEARER' => NULL,
                'BEARER_ID' => NULL,
                'ISSUED_BY' => NULL,
                'ISSUED_BY_POSITION' => NULL,
                'APPROVED_BY' => NULL,
                'APPROVED_BY_POSITION' => NULL
            ), $params );

        switch( $report_mode )
        {
            case 'JasperReports':
                $report_path = 'TMIS/delivery_receipt';

                // Get report format
                $format = NULL;
                if( isset( $params['format'] ) )
                {
                    $format = $params['format'];
                    unset( $params['format'] );
                }

                return $this->_generate_report( $report_path, $format, $params );

            default:
                $this->load->library( 'transfer' );
                $Transfer = new Transfer();

                $transfer_id = param( $params, 'TRANSFER_ID' );
                $transfer = $Transfer->get_by_id( $transfer_id );
                $params = array_merge( array(
                        'transfer_item' => $transfer->get_transfer_array()
                    ), $params );

                unset( $params['TRANSFER_ID'] );
                $this->load->view( 'reports/delivery_receipt', $params );
        }
	}

}
