<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends MY_Controller
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
	
}