<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct( $public_methods = array() )
    {
        parent::__construct();
        if( ! in_array( $this->uri->rsegment( 2 ), $public_methods ) )
        {
            if( ! is_logged_in() )
            {
                if( $this->input->is_ajax_request() )
                {
                    $response = array(
                            'status' => 'session_expired',
                            'errorMsg' => 'Your session has already expired. You are no longer allowed to access this resource.'
                        );

                    $this->output->set_status_header( 401 );
                    $this->output->set_content_type( 'application/json' );
                    $this->output->set_output( json_encode( $response ) );
                    $this->output->_display();
                    exit;
                }
                else
                {
                    if( ! $this->session->current_user_id )
                    {
                        // TODO: redirect window, currently only redirects view
                        redirect( site_url( '/login' ) );
                    }
                }
            }
        }
    }
}