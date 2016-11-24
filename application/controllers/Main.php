<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		//$this->login( 'admin', 1, 1 );
		$this->load->view( 'index' );
	}

	public function test()
	{
		echo phpinfo();
	}

	public function view( $view )
	{
		$data['app_version'] = $this->config->item( 'app_version' );

		// check if valid session
		if( is_logged_in() )
		{
			if( substr( $view, 0, strlen( 'modal_' ) ) === 'modal_' )
			{
				$this->load->view( 'modals/'.$view, $data );
			}
			else
			{
				$this->load->view( $view, $data );
			}
		}
		else
		{
			echo 'Unauthorized access';
		}
	}

	public function login( $username, $store_id, $shift_id )
	{
		$this->load->library( 'User' );
		$this->load->library( 'Store' );

		$user = new User();
		$store = new Store();

		$user = $user->get_by_username( $username );
		$store = $store->get_by_id( $store_id );

		$this->session->current_user_id = $user->get( 'id' );
		$this->session->current_store_id = $store_id;
        $this->session->current_shift_id = $shift_id;

		return array( $user, $store );
	}


	public function test_create_transfer()
	{
		$this->load->model( 'User' );
		$current_user = $this->User->get_by_username( 'erhsatingin' );

		$this->load->model( 'Store' );
		$current_store = $this->Store->get_by_id( 1 ); // Line 2 Depot
		$destination_store = $this->Store->get_by_id( 2 ); // East Satellite

		// Create transfer
		$this->load->model( 'Transfer' );
		$transfer = new Transfer();
		$transfer->set( 'origin_id', $current_store->get( 'id' ) );
		$transfer->set( 'origin_name', $current_store->get( 'store_name' ) );
		$transfer->set( 'sender_id', $current_user->get( 'id' ) );
		$transfer->set( 'sender_name', $current_user->get( 'full_name' ) );
		$transfer->set( 'destination_id', $destination_store->get( 'id' ) ); // East Satellite
		$transfer->set( 'destination_name', $destination_store->get( 'store_name' ) );
		$transfer->set( 'item_id', 1 );
		$transfer->set( 'transfer_item_count', 13000 );
		$transfer->set( 'transfer_datetime', date( TIMESTAMP_FORMAT ) );
		$transfer->set( 'transfer_status', TRANSFER_PENDING );
		$transfer->db_save();
	}

	public function test_approve_transfer( $transfer_id )
	{
		$this->load->model( 'Transfer' );
		$transfer = $this->Transfer->get_by_id( $transfer_id );
		$transfer->approve();
	}

	public function test_receive_transfer( $transfer_id )
	{
		$this->login( 'mmduron', 2);

		$recipient = 'Manong Guard';
		$this->load->model( 'Transfer' );
		$transfer = $this->Transfer->get_by_id( $transfer_id );
		$transfer->receive( $recipient );
	}

	public function test_external_receipt()
	{
		$login = $this->login( 'erhsatingin', 1 );
		$current_user = $login[0];
		$current_store = $login[1];

		// Create transfer
		$this->load->model( 'Transfer' );
		$receipt = new Transfer();
		$receipt->set( 'origin_name', 'AFPi' );
		$receipt->set( 'sender_name', 'Jojo Feliciano' );
		$receipt->set( 'destination_id', $current_store->get( 'id' ) ); // Line 2 Depot
		$receipt->set( 'destination_name', $current_store->get( 'store_name' ) );
		$receipt->set( 'item_id', 1 );
		$receipt->set( 'transfer_item_count', 50000 );
		$receipt->set( 'transfer_datetime', date( TIMESTAMP_FORMAT ) );
		$receipt->set( 'transfer_status', TRANSFER_APPROVED );
		$receipt->db_save();

		$receipt->receive();
	}
}