<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends CI_Controller
{
    public function __construct()
    {
        $public_methods = array( 'segment', 'session_data' );
        parent::__construct( $public_methods );
    }

	public function index()
	{
		$this->load->library( 'BaseModel' );
		$this->load->library( 'Item' );


		$i = new Item();
		//$x = $i->get_by_id( 1 );

		var_dump( $i );
	}

    public function segment()
    {
        echo $this->uri->segment(2);
    }

	public function session_data()
	{
		var_dump( $this->session->userdata() );
	}

    public function is_logged_in()
    {
        if( ! $this->session->current_user_id )
        {
            echo 'Not logged in!';
        }
        else
        {
            echo 'Logged in';
        }
    }

    public function test_conversion()
    {
        $src = 'L2 SJT';
        $tgt = 'L2 SJT - Rigid Box';
        $this->_convert( $src, $tgt, 10, 5000 );

        $src = 'L2 SJT - Rigid Box';
        $tgt = 'L2 SJT';
        $this->_convert( $src, $tgt );

        $src = 'L2 SJT';
        $tgt = 'L2 SJT - Ticket Magazine';
        $this->_convert( $src, $tgt, 10, 5000 );

        $src = 'L2 SJT - Ticket Magazine';
        $tgt = 'L2 SJT';
        $this->_convert( $src, $tgt, 10, 20 );
    }

    public function _convert( $src, $tgt, $n = 10, $qty_max = 100 )
    {
        $this->load->library( 'conversion' );
        $cv = new Conversion();

        $this->load->library( 'item' );
        $item = new Item();

        echo 'Converting '.$src.' to '.$tgt.'...<br />';
        $src_item = $item->get_by_name( $src );
        $tgt_item = $item->get_by_name( $tgt );

        for( $i = 0; $i < $n; $i++ )
        {
            $qty = rand( 1, $qty_max );
            $result = $cv->convert( $src_item->get( 'id' ), $tgt_item->get( 'id' ), $qty );
            $r_str = '';
            if( $result )
            {
                $r_str = $qty.' '.$src.' => ';
            }
            else
            {
                $r_str = 'No conversion possible';
            }

            $r_array = array();
            foreach( $result as $k => $v )
            {
                $r_item = $item->get_by_id( $k );
                $r_array[] = $v.' '.$r_item->get( 'item_name' );
            }

            $r_str .= implode( ' + ', $r_array );

            echo $r_str.'<br />';
        }

        echo '<br />';
    }

    function test_report( $report, $format = 'pdf', $params = array() )
    {
        // Set format defaults
        switch( $format )
        {
            case 'pdf':
                $format_ext = 'pdf';
                $content_type = 'application/pdf';
                break;

            default:
                return FALSE;
        }

        // Set report defaults
        switch( $report )
        {
            case 'delivery_receipt':
                $prefix = 'dr_';
                $report_path = '/TMIS/delivery_receipt';
                $params = array_merge( array(
                        'TRANSFER_ID' => 1,
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
                break;

            default:
                return FALSE;
        }

        // Set report server user credentials
        $params = array_merge( array(
                'j_username' => 'jasperadmin',
                'j_password' => 'jasperadmin',
            ), $params );

        $temp_file = tempnam(sys_get_temp_dir(), 'delivery_receipt_').'.'.$format_ext;
        $url = 'http://db.afcs.lan:8080/jasperserver/rest_v2/reports/Reports/'.$report_path.'.'.$format;
        $url = $url.'?'.http_build_query( $params);

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
                // Output the file
                header( 'Content-type: '.$content_type );
                header( 'Content-Disposition: inline; filename="' . $filename . '"' );
                header( 'Content-Transfer-Encoding: binary' );
                header( 'Accept-Ranges: bytes' );
                readfile( $temp_file );
            }
        }
        catch ( Exception $e )
        {
            echo 'exception: '.$e;
            return FALSE;
        }
        finally
        {
            fclose( $fp );
        }
    }

}