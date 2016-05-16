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
                    // Set session data
                    $this->session->current_user_id = $user->get( 'id' );
                    $this->session->current_store_id = 1;
                    $this->session->current_shift_id = 1;
                    
                    redirect( site_url( '/main/#/store/front' ) );
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