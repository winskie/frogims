<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{
	public function index()
	{
        switch( $this->input->server( 'REQUEST_METHOD' ) )
        {
            case 'POST':
                $username = $this->input->post( 'username' );
                $password = $this->input->post( 'password' );

                $this->load->library( 'user' );
                $user = new User();
                $user = $user->get_by_username( $username );
                if( $user && $user->validate_password( $password ) )
                {
                    // Get store where user is member
                    $stores = $user->get_stores();
                    if( $stores )
                    {
                        $first_store = $stores[0];
                        
                        // Get store shifts
                        $shifts = $first_store->get_shifts();
                        
                        if( $shifts )
                        {
                            $suggested_shift = $first_store->get_suggested_shift();
                        }
                    }
                    
                    // Set session data
                    $this->session->current_user_id = $user->get( 'id' );
                    $this->session->current_store_id = $first_store->get( 'id' );
                    $this->session->current_shift_id = $suggested_shift->get( 'id' );
                    
                    redirect( site_url( '/main/#/main/store' ) );
                    //echo site_url( '/main/#/store' );
                }
                else
                {
                    // Destroy session data
                    $this->session->sess_destroy();
                    $this->session->set_flashdata( 'username', $username );
                    $this->session->set_flashdata( 'error', 'Invalid username or password' );
                    $this->load->view( 'login' );
                }
                break;
                
            default:
                $this->load->view( 'login' );
                break;
        }
	}
    
    public function logout()
    {
        $this->session->sess_destroy();
        redirect( '/login' );
    }
}