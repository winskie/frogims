<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }

	public function index()
	{
		// index
	}
	
    
	public function _bad_request( $reason = NULL )
	{
		$response = array(
			'status' => 'fail',
			'error' => isset( $reason ) ? $reason : 'Bad Request'
		);
		
		return $response;
	}


	public function login()
	{
        $request_method = $this->input->server( 'REQUEST_METHOD' );
        
        if( $request_method == 'POST' )
        {
            $username = $this->input->post( 'username' );
            $password = $this->input->post( 'password' );

            $this->load->library( 'user' );
            $user = new User();
            $user = $user->get_by_username( $username );
            if( $user->validate_password( $password ) )
            {
                // Set session data
                $this->session->current_user_id = $user->get( 'id' );
                $this->session->current_store_id = 1;
                $this->session->current_shift_id = 1;
                
                redirect( 'main/index' );
            }
            else
            {
                // Destroy session data
                $this->session->sess_destroy();
                
                $response = array(
                        'status' => 'fail',
                        'errorMsg' => 'Invalid username or password'
                    );
            }
        }
        else
        {
            $response = $this->_bad_request( 'Bad request' );
        }
        
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );

        /*   
		$username = $this->input->post( 'username' );
		$password = $this->input->post( 'password' );
        $store = $this->input->post( 'store' );
        $shift = $this->input->post( 'shift' );

		$this->load->library( 'User' );
		$user = new User();

		$user = $user->get_by_username( $username );
		if( $user )
		{
			$this->session->current_user_id = $user->get( 'id' );
		}
        
		$this->session->current_store_id = $store;
        $this->session->current_shift_id = $shift;
        */
	}


	public function store( $action = NULL )
	{
        $request_method = $this->input->server( 'REQUEST_METHOD' );
        
        if( $request_method == 'GET' )
        {
            switch( $action )
            {
                case 'shifts':
                    $store_type = $this->input->get( 'store_type' );
                    
                    $this->load->library( 'store' );
                    $store = new Store();
                    
                    if( $store )
                    {
                        $shifts = $store->get_shifts( $store_type );
                        $shifts_data = array();
                        
                        foreach( $shifts as $shift )
                        {
                            $shifts_data[] = $shift->as_array();
                        }
                        
                        $response = array(
                                'status' => 'ok',
                                'data' => $shifts_data
                            );
                    }
                    else
                    {
                        $response = array(
                                'status' => 'fail',
                                'error' => 'Unable to create store instance'
                            );
                    }
                    break;
                    
                case 'store_shifts':
                    $store_id = $this->input->get( 'store_id' );
                    $show_all = $this->input->get( 'show_all' );
                    
                    if( $store_id )
                    {
                        $this->load->library( 'store' );
                        $store = new Store();
                        $store = $store->get_by_id( $store_id );
                        
                        if( $store )
                        {
                            $shifts = $store->get_store_shifts( $show_all );
                            $shifts_data = array();
                            
                            foreach( $shifts as $shift )
                            {
                                $shifts_data[] = $shift->as_array();
                            }
                            
                            $response = array(
                                    'status' => 'ok',
                                    'data' => $shifts_data
                                );
                        }
                        else
                        {
                            $response = array(
                                    'status' => 'fail',
                                    'error' => 'Store not found.'
                                );
                        }
                    }
                    else
                    {
                        $response = $this->_bad_request();
                    }
                    break;
                    
                default:
            }
        }
        elseif( $request_method == 'POST' )
        {
            switch( $action )
            {
                case 'change_shift':
                    $shift_id = $this->input->post( 'shift_id' );
                    
                    if( $shift_id )
                    {
                    
                        $this->load->library( 'shift' );
                        $shift = new Shift();
                        $shift = $shift->get_by_id( $shift_id );
                        
                        if( $shift )
                        {
                            $this->load->library( 'store' );
                            $store = new Store();
                            $store = $store->get_by_id( $this->session->current_store_id );
                            if( $store )
                            {
                                if( $shift->get( 'store_type' ) == $store->get( 'store_type' ) )
                                {
                                    $this->session->current_shift_id = $shift->get( 'id' );
                                    $response = array(
                                            'status' => 'ok',
                                            'data' => $shift->as_array()
                                        );
                                }
                                else
                                {
                                    $response = array(
                                            'status' => 'fail',
                                            'error' => 'Invalid shift for current store.',
                                            'current_store' => $store->as_array(),
                                            'shift' => $shift->as_array(),
                                            'shift_store_type' => $shift->get( 'store_type' ),
                                            'store_store_type' => $store->get( 'store_type' )
                                        );
                                }
                            }
                            else
                            {
                                $response = array(
                                        'status' => 'fail',
                                        'error' => 'Unable to verify shift validity for current store'
                                    );
                            }
                        }
                        else
                        {
                            $response = $this->_bad_request( 'Shift record not found.' );
                        }
                    }
                    else
                    {
                        $response = $this->_bad_request( 'Missing shift information.' );
                    }
                    break;
                    
                default:
                    $store_id = $this->input->post( 'store_id' );

                    // TODO: Check if current user is allowed in store
                    $this->load->library( 'store' );
                    $store = new Store();
                    $store = $store->get_by_id( $store_id );

                    $this->session->current_store_id = $store_id;

                    $response = array(
                            'status' => 'ok',
                            'data' => $store->as_array()
                        );
            }
        }
        else
        {
            $response = $this->_bad_request();
        }

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );

	}

	
    public function login_info()
	{
		$store_data = NULL;
		$user_data = NULL;
        $shift_data = NULL;

		$this->load->library( 'store' );
		$this->load->library( 'user' );
        $this->load->library( 'shift' );

		$current_store = new Store();
		$current_user = new User();
        $current_shift = new Shift();

		$current_store = $current_store->get_by_id( $this->session->current_store_id );
		$current_user = $current_user->get_by_id( $this->session->current_user_id );
        $current_shift = $current_shift->get_by_id( $this->session->current_shift_id );

        $user_data = NULL;
        $store_data = NULL;
        $stores_data = NULL;
        $shift_data = NULL;
        
		if( $current_store )
		{
			$store_data = $current_store->as_array();
		}

		if( $current_user )
		{
            $stores = $current_user->get_stores();
            $stores_data = array();
            
            foreach( $stores as $store )
            {
                $stores_data[] = $store->as_array();
            }
			$user_data = $current_user->as_array();
		}
        
        if( $current_shift )
        {
            $shift_data = $current_shift->as_array();
        }

		$response = array(
				'status' => 'ok',
				'data' => array(
					'user' => $user_data,
					'store' => $store_data,
                    'stores' => $stores_data,
                    'shift' => $shift_data
				)
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}


    public function stations()
    {
        $query = $this->db->get( 'stations' );
        $stations = $query->result_array();
        
        $response = array(
                'status' => 'ok',
                'data' => $stations
            );
            
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }


    public function items( $action = NULL)
    {
        $request_method = $this->input->server( 'REQUEST_METHOD' );
        
        if( $request_method == 'GET' )
        {
            switch( $action )
            {
                case 'conversion_table':
                    $query = $this->db->get( 'conversion_table' );
                    $conversion_data = array();
                    foreach( $query->result_array() as $row )
                    {
                        $conversion_data[] = array(
                            'id' => intval( $row['id'] ),
                            'source_item_id' => intval( $row['source_item_id'] ),
                            'target_item_id' => intval( $row['target_item_id'] ),
                            'conversion_factor' => intval( $row['conversion_factor'] )
                        );
                    }
                    $response = array(
                            'status' => 'ok',
                            'data' => $conversion_data
                        );
                    break;
                
                case 'package_conversion':
                    $this->db->select( 'ct.*, i.item_name, i.item_description' );
                    $this->db->where( 'conversion_factor >', 1 );
                    $this->db->join( 'items i', 'i.id = ct.target_item_id', 'left' );
                    $query = $this->db->get( 'conversion_table ct' );
                    $conversion_data = array();
                    foreach( $query->result_array() as $row )
                    {
                        $conversion_data[] = array(
                            'id' => intval( $row['id'] ),
                            'source_item_id' => intval( $row['source_item_id'] ),
                            'target_item_id' => intval( $row['target_item_id'] ),
                            'conversion_factor' => intval( $row['conversion_factor'] ),
                            'item_name' => $row['item_name'],
                            'item_description' => $row['item_description']
                        );
                    }
                    $response = array(
                            'status' => 'ok',
                            'data' => $conversion_data
                        );
                    break;
                    
                case 'categories':
                    $this->db->where( 'category_status', 1 );
                    $query = $this->db->get( 'item_categories' );
                    $categories = $query->result_array();
                    $categories_data = array();
                    
                    foreach( $categories as $category )
                    {
                        $categories_data[] = array(
                                'id' => ( int ) $category['id'],
                                'category' => $category['category'],
                                'category_type' => ( int ) $category['category_type'],
                                'is_allocation_category' => ( bool ) $category['is_allocation_category'],
                                'is_remittance_category' => ( bool ) $category['is_remittance_category'],
                                'is_transfer_category' => ( bool ) $category['is_transfer_category'],
                                'is_teller' => ( bool ) $category['is_teller'],
                                'is_machine' => ( bool ) $category['is_machine'],
                                'category_status' => ( int ) $category['category_status']
                            );
                    }
                    
                    $response = array(
                            'status' => 'ok',
                            'data' => $categories_data
                        );
                    break;
                    
                default:
                    $this->load->library( 'item' );
                    $item = new Item();
                    $items = $item->get_items();
                    
                    $items_data = array();
                    
                    foreach( $items as $i )
                    {
                        $items_data[] = $i->as_array();
                    }
                    
                    $response = array(
                            'status' => 'ok',
                            'data' => $items_data
                        );
            }
        }
        else
        {
            $response = $this->_bad_request( 'Invalid request parameters' );
        }
            
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }


    public function users()
    {
        $this->load->library( 'user' );
        $user = new User();
        $users = $user->get_users();
        
        $users_data = array();
        
        foreach( $users as $u )
        {
            $users_data[] = $u->as_array();
        }
        
        $response = array(
                'status' => 'ok',
                'data' => $users_data
            );
            
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }


	public function stores()
	{
		$this->load->library( 'store' );
		$stores = new Store();
		$stores = $stores->get_stores();
		
		$stores_data = array();
		foreach( $stores as $store )
		{
			$stores_data[] = $store->as_array();
		}
		
		$response = array(
				'status' => 'ok',
				'data' => $stores_data
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

    public function shifts()
    {
        $shifts = $this->db->get( 'shifts' );
        $response = array(
                'status' => 'ok',
                'data' => $shifts->result_array()
            );
            
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }

	public function inventory( $action = NULL )
	{
		$request_method = $this->input->server( 'REQUEST_METHOD' );
		
		if( $request_method == 'GET' )
		{
			$store_id = $this->input->get( 'store_id' );
		
			$this->load->library( 'Store' );
			$store = new Store();
			$store = $store->get_by_id( $store_id );
			$inventory = $store->get_items();
            
            $inventory_data = array();
            $additional_fields = array(
                    'item_name' => array( 'type' => 'string' ),
                    'item_description' => array( 'type' => 'string' ),
                    'teller_allocatable' => array( 'type' => 'boolean' ),
                    'teller_remittable' => array( 'type' => 'boolean' ),
                    'machine_allocatable' => array( 'type' => 'boolean' ),
                    'machine_remittable' => array( 'type' => 'boolean' )
                );
            foreach( $inventory as $item )
            {
                
                $inventory_data[] = $item->as_array( $additional_fields );
            }
	
			$response = array(
					'status' => 'ok',
					'data' => $inventory_data
				);
		}
		elseif( $request_method == 'POST' )
		{
			if( $action == 'adjust' )
			{
				$this->load->library( 'adjustment' );
				$adjustment = new Adjustment();
				$adjustment = $adjustment->load_from_data( $this->input->post() );
				$r = $adjustment->db_save();
					
				$response = array(
						'status' => 'ok',
						'data' => $r->as_array()
					);
			}
			else
			{
				$response = $this->_bad_request();
			}
		}

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}


	public function transactions( $store_id = NULL )
	{
		if( is_null( $store_id ) )
		{
			$store_id = $this->input->get( 'store_id' );
		}

		$this->load->library( 'store' );
		$store = new Store();
		$store = $store->get_by_id( $store_id );
		$transactions = $store->get_transactions( array( 'format' => 'array', 'order' => 'transaction_datetime DESC, id DESC' ) );

		$response = array(
				'status' => 'ok',
				'data' => $transactions
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}


	/**
	 * @param string Type can be: 'out', 'in'
	 */
	public function transfer( $action = NULL )
	{
		if( $this->input->server( 'REQUEST_METHOD' ) == 'GET' )
		{ // Get receipts
			switch( $action )
			{
				case 'receipts':
					$store_id = $this->input->get( 'store_id' );

					$this->load->library( 'store' );
					$store = new Store();
					$store = $store->get_by_id( $store_id );
					$receipts = $store->get_receipts();
					
					$pending_receipts = $store->count_pending_receipts();

					$receipts_data = array();
					foreach( $receipts as $receipt )
					{
						$items = $receipt->get_items( FALSE );
						$r = $receipt->as_array();
						foreach( $items as $item )
						{
							$r['items'][] = $item->as_array( array( 'item_name', 'item_description' ) );
						}
						$receipts_data[] = $r;
					}

					$response = array(
							'status' => 'ok',
							'data' => $receipts_data,
							'pending' => $pending_receipts
						);
					break;
                    
                case 'item':
                    $transfer_id = $this->input->get( 'id' );
                    
                    if( $transfer_id )
                    {
                        $this->load->library( 'transfer' );
                        $transfer = new Transfer();
                        $transfer = $transfer->get_by_id( $transfer_id );
                        $transfer_data = $transfer->as_array();
                        
                        $transfer_items = $transfer->get_items();
                        $tramsfer_items_data = array();
                        
                        foreach( $transfer_items as $item )
                        {
                            $transfer_items_data[] = $item->as_array( array(
                                    'item_name' => array( 'type' => 'string' ),
                                    'item_description' => array( 'type' => 'string' ),
                                    'category_name' => array( 'type' => 'string' ),
                                    'is_transfer_category' => array( 'type' => 'boolean' ) ) );
                        }
                        $transfer_data['items'] = $transfer_items_data;
                        
                        $response = array(
                                'status' => 'ok',
                                'data' => $transfer_data
                            );
                    }
                    else
                    {
                        $response = $this->_bad_request( 'Missing allocation ID parameter' );
                    }
                    break;
					
				case 'items':
					$id = $this->input->get( 'id' );
					
					$this->load->library( 'transfer_item' );
					$this->db->select( 'ti.*, i.item_name, i.item_description' );
					$this->db->where( 'transfer_id', $id );
					$this->db->join( 'items i', 'i.id = ti.item_id', 'left' );
					$query = $this->db->get( 'transfer_items ti' );
					$items = $query->result( 'Transfer_item' );
					
					$data = array();
					$additional_fields = array( 'item_name', 'item_description'	);
					foreach( $items as $item )
					{
						$data[] = $item->as_array( $additional_fields );
					}
					
					
					$response = array(
							'status' => 'ok',
							'data' => $data
						);
					break;
					
				default:
					$store_id = $this->input->get( 'store_id' );
	
					$this->load->library( 'store' );
					$store = new Store();
					$store = $store->get_by_id( $store_id );
					$transfers = $store->get_transfers();
					$pending_transfers = $store->count_pending_transfers();
					
					$transfers_data = array();
					foreach( $transfers as $transfer )
					{
						$items = $transfer->get_items( FALSE );
						$r = $transfer->as_array();
						foreach( $items as $item )
						{
							$r['items'][] = $item->as_array( array( 
                                'item_name' => array( 'type' => 'string' ),
                                'item_description' => array( 'type' => 'string' ) ) );
						}
						$transfers_data[] = $r;
					}
	
					$response = array(
							'status' => 'ok',
							'data' => $transfers_data,
							'pending' => $pending_transfers
						);
			}
		}
		elseif( $this->input->server( 'REQUEST_METHOD' ) == 'POST' )
		{
			switch( $action )
			{
				case 'create':
					$this->load->library( 'transfer' );
					$transfer = new Transfer();
					$transfer = $transfer->load_from_data( $this->input->post() );
					$transfer->db_save();
					
					$transfer_items = $transfer->get_items();					
					$transfer_data = $transfer->as_array();
					foreach( $transfer_items as $item )
					{
						$transfer_data['items'][$item->get( 'id' )] = $item->as_array();
					}
					
					$response = array(
							'status' => 'ok',
							'data' => $transfer_data
						);
					
					break;
				
				case 'schedule': // Save transfer
					$datetime = $this->input->post( 'date' );
					$destination_id = $this->input->post( 'dest_id' );
					$destination_name = $this->input->post( 'dest' );
					$item_id = $this->input->post( 'item_id' );
					$quantity = $this->input->post( 'qty' );
					$person = $this->input->post( 'p' );
					$status = $this->input->post( 'status' );
	
					$this->load->library( 'store' );
					$current_store = new Store();
					$current_store = $current_store->get_by_id( $this->session->current_store_id );
	
					$this->load->library( 'user' );
					$current_user = new User();
					$current_user = $current_user->get_by_id( $this->session->current_user_id );
	
					if( $destination_id )
					{
						$destination_store = new Store();
						$destination_store = $destination_store->get_by_id( $destination_id );
						$destination_name = $destination_store->get( 'store_name' );
					}
					else
					{ // Transferring to external store
						$destination_id = NULL;
					}
	
					if( ! $person )
					{ // No responsible person defined, current user's name is used
						$person = $current_user->get( 'full_name' );
					}
	
					switch( $status )
					{
						case 'scheduled':
							$status = TRANSFER_PENDING;
							break;
						case 'approved':
							$status = TRANSFER_APPROVED;
							break;
						case 'cancelled':
							$status = TRANSFER_CANCELLED;
							break;
						default:
							// Return a bad request later
							die( 'Invalid status for transfer.' );
					}
	
					$this->load->library( 'Transfer' );
					$transfer = new Transfer();
					// Current store and user information
					$transfer->set( 'origin_id', $current_store->get( 'id' ) );
					$transfer->set( 'origin_name', $current_store->get( 'store_name' ) );
					$transfer->set( 'sender_id', $current_user->get( 'id' ) );
					$transfer->set( 'sender_name', $person );
	
					// Destination and transfer details
					$transfer->set( 'destination_id', $destination_id );
					$transfer->set( 'destination_name', $destination_name );
					$transfer->set( 'transfer_datetime', date( TIMESTAMP_FORMAT, strtotime( $datetime ) ) );
					$transfer->set( 'transfer_status', $status );
					$result = $transfer->db_save();
	
					$response = array(
							'status' => 'ok',
							'data' => $transfer->as_array()
						);
					break;
					
				case 'approve': // Approve transfer			
					$this->load->library( 'transfer' );
					$transfer = new Transfer();
					$transfer = $transfer->load_from_data( $this->input->post() );
					
					$this->db->trans_start();
					$transfer->approve();
					$transfer_items = $transfer->get_items();					
					$transfer_data = $transfer->as_array();
					foreach( $transfer_items as $item )
					{
						$transfer_data['items'][$item->get( 'id' )] = $item->as_array();
					}
					$this->db->trans_complete();
					
					if( $this->db->trans_status() )
					{
						$response = array(
								'status' => 'ok',
								'data' => $transfer_data
							);
					}
					else
					{
						$response = array(
								'status' => 'fail',
								'error' => 'A database error has occurred while trying to approve the transfer'
							);
					}
					
					break;
					
				case 'receive': // Receive transfer
					$this->load->library( 'transfer' );
					$this->load->library( 'profiler' );
					$transfer = new Transfer();
					$transfer = $transfer->load_from_data( $this->input->post() );

					$this->db->trans_start();
					$transfer->receive();
					$transfer_items = $transfer->get_items();					
					$transfer_data = $transfer->as_array();
					foreach( $transfer_items as $item )
					{
						$transfer_data['items'][$item->get( 'id' )] = $item->as_array();
					}
					$this->db->trans_complete();
					
					$this->profiler->run();
					
					if( $this->db->trans_status() )
					{
						$response = array(
								'status' => 'ok',
								'data' => $transfer_data,
								'debug' => $this->profiler->output
							);
					}
					else
					{
						$response = array(
								'status' => 'fail',
								'error' => 'A database error has occurred while trying to receive the transfer'
							);
					}
					break;
					
				case 'cancel': // Cancel transfer
					$this->load->library( 'transfer' );
					$transfer = new Transfer();
					$transfer = $transfer->load_from_data( $this->input->post() );
					
					$this->db->trans_start();
					$transfer->cancel();
					$transfer_items = $transfer->get_items();					
					$transfer_data = $transfer->as_array();
					foreach( $transfer_items as $item )
					{
						$transfer_data['items'][$item->get( 'id' )] = $item->as_array();
					}
					$this->db->trans_complete();
					
					if( $this->db->trans_status() )
					{
						$response = array(
								'status' => 'ok',
								'data' => $transfer_data
							);
					}
					else
					{
						$response = array(
								'status' => 'fail',
								'error' => 'A database error has occurred while trying to cancel the transfer'
							);
					}	
					break;
			
				default:
					$response = array(
							'status' => 'fail',
							'error' => 'Bad request'
						);
			}
		}

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}


	public function adjustments( $store_id = NULL )
	{
		if( is_null( $store_id ) )
		{
			$store_id = $this->input->get( 'store_id' );
		}

		$this->load->library( 'store' );
		$store = new Store();
		$store = $store->get_by_id( $store_id );		
		$adjustments = $store->get_adjustments( array( 'format' => 'array' ) );
		$pending_adjustments = $store->count_pending_adjustments();

		$response = array(
				'status' => 'ok',
				'data' => $adjustments,
				'pending' => $pending_adjustments,
                'debug' => $this->db->last_query()
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

	
	public function adjustment( $action = NULL )
	{
		$request_method = $this->input->server( 'REQUEST_METHOD' );
		
		switch( $request_method )
		{
			case 'GET':
				break;
				
			case 'POST':
				switch( $action )
				{
					case 'approve':
						$response = $this->_approve_adjustment( $this->input->post() );
						break;
					default:
						// bad request
				}
				break;
				
			default:
				// bad request
		}
		
		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

	
    public function conversions( $store_id = NULL )
    {
        if( is_null( $store_id ) )
        {
            $store_id = $this->input->get( 'store_id' );
        }
        
        $this->load->library( 'store' );
        $store = new Store();
        $store = $store->get_by_id( $store_id );
        $conversions = $store->get_conversions( array( 'format' => 'array' ) );
        
        $response = array(
                'status' => 'ok',
                'data' => $conversions
            );
            
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }


    public function conversion( $action = NULL )
    {
        if( $this->input->server( 'REQUEST_METHOD' ) == 'GET' )
        {
            switch( $action )
            {
                case 'conversion_factor':
                    $source = $this->input->get( 'source' );
                    $target = $this->input->get( 'target' );
                    
                    if( ! $source || ! $target )
                    {
                        $response = $this->_bad_request();
                    }
                    else
                    {
                        $this->load->library( 'conversion' );
                        $conversion = new Conversion();
                        $result = $conversion->get_conversion_factor( $source, $target );
                        
                        if( $result )
                        {
                            $response = array(
                                    'status' => 'ok',
                                    'factor' => $result['factor'],
                                    'mode' => $result['mode']
                                );
                        }
                        else
                        {
                            $response = array(
                                    'status' => 'fail',
                                    'error' => 'No conversion possible',
                                    'sql' => $this->db->last_query()
                                );
                        }
                    }
                    
                    break;
                default:
                    $response = $this->_bad_request();
            }
        }
        elseif( $this->input->server( 'REQUEST_METHOD' ) == 'POST' )
        {
            switch( $action )
            {
                case 'convert':
                    $this->load->library( 'conversion' );
                    $conversion = new Conversion();
                    $conversion = $conversion->load_from_data( $this->input->post() );
                    $conversion->db_save();
                    
                    $response = array(
                            'status' => 'ok',
                            'data' => $conversion->as_array()
                        );
                    break;
                    
                default:
                    $response = $this->_bad_request();
            }
        }
        
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }
  
    
    public function mopping( $action = NULL )
    {
        $request_method = $this->input->server( 'REQUEST_METHOD' );
        
        if( $request_method == 'GET' )
        {
            switch( $action )
            {
                case 'item':
                    $mopping_item_id = $this->input->get( 'id' );
                    
                    if( $mopping_item_id )
                    {
                        $this->load->library( 'mopping' );
                        $mopping = new Mopping();
                        $mopping = $mopping->get_by_id( $mopping_item_id );
                        $mopping_data = $mopping->as_array();
                        $items = $mopping->get_items();
                        $items_data = array();
                        foreach( $items as $item )
                        {
                            $items_data[] = $item->as_array( array( 'mopped_station_name', 'mopped_item_name', 'convert_to_name', 'processor_name' ) );
                        }
                        $mopping_data['items'] = $items_data;
                        
                        $response = array(
                                'status' => 'ok',
                                'data' => $mopping_data
                            );
                    }
                    else
                    {
                        $response = $this->_bad_request( 'Missing mopping collection ID parameter' );
                    }
                    break;
                    
                case 'summary':
                    $store_id = $this->input->get( 'store_id' );
                    
                    $this->load->library( 'store' );
                    $store = new Store();
                    $store = $store->get_by_id( $store_id );
                    $collections = $store->get_collections_summary();
                    
                    $data = array();

                    foreach( $collections as $row )
                    {
                        $item = array(
                                'item_id' => $row['item_id'],
                                'item_name' => $row['item_name'],
                                'item_description' => $row['item_description'],
                                'quantity' => $row['quantity']
                            );
                            
                        if( isset( $data[$row['mopping_id']] ) )
                        {
                            $data[$row['mopping_id']]['items'][] = $item;
                        }
                        else
                        {
                            $data[$row['mopping_id']] = array(
                                    'id' => $row['id'],
                                    'processing_datetime' => $row['processing_datetime'],
                                    'business_date' => $row['business_date'],
                                    'shift_id' => $row['shift_id'],
                                    'shift_num' => $row['shift_num'],
                                    'cashier_shift_id' => $row['cashier_shift_id'],
                                    'cashier_shift_num' => $row['cashier_shift_num']
                                );
                            $data[$row['mopping_id']]['items'] = array( $item );
                        }
                    }
                    
                    $response = array(
                            'status' => 'ok',
                            'data' => array_values($data)
                        );
                    break;
                    
                default:
                    $store_id = $this->input->get( 'store_id' );
                    
                    $this->load->library( 'store' );
                    $store = new Store();
                    $store = $store->get_by_id( $store_id );
                    $collections = $store->get_collections( array( 'format' => 'array' ) );
                    
                    $response = array(
                            'status' => 'ok',
                            'data' => $collections
                        );
                    break;
            }
        }
        elseif( $request_method == 'POST' )
        {
            switch( $action )
            {
                case 'process':
                    $this->load->library( 'mopping' );
                    $mopping = new Mopping();
                    $mopping = $mopping->load_from_data( $this->input->post() );
                    $mopping->db_save();
                    
                    $response = array(
                            'status' => 'ok',
                            'data' => $mopping->as_array()
                        );
                    
                    break;
                    
                default:
                    $response = $this->_bad_request( 'Bad request' );
            }
        }
        else
        {
            $response = $this->_bad_request( 'Bad request' );
        }
        
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }
  
    
    public function allocations( $action = NULL )
    {
        $request_method = $this->input->server( 'REQUEST_METHOD' );
        
        if( $request_method == 'GET' )
        {
            switch( $action )
            {
                case 'item':
                    $allocation_id = $this->input->get( 'id' );
                    
                    if( $allocation_id )
                    {
                        $this->load->library( 'allocation' );
                        $allocation = new Allocation();
                        $allocation = $allocation->get_by_id( $allocation_id );
                        $allocation_data = $allocation->as_array();
                        $allocation_items = $allocation->get_allocations();
                        $remittance_items = $allocation->get_remittances();
                        $allocations_data = array();
                        $remittances_data = array();
                        
                        foreach( $allocation_items as $item )
                        {
                            $allocations_data[] = $item->as_array( array(
                                    'category_name' => array( 'type' => 'string' ),
                                    'category_type' => array( 'type' => 'integer' ),
                                    'item_name' => array( 'type' => 'string' ),
                                    'item_description' => array( 'type' => 'string' ),
                                    'teller_allocatable' => array( 'type' => 'boolean' ),
                                    'machine_allocatable' => array( 'type' => 'boolean' ),
                                    'cashier_shift_num' => array( 'type' => 'string' ) ) );
                        }
                        $allocation_data['allocations'] = $allocations_data;
                        
                        foreach( $remittance_items as $item )
                        {
                            $remittances_data[] = $item->as_array( array(
                                    'category_name' => array( 'type' => 'string' ),
                                    'category_type' => array( 'type' => 'integer' ),
                                    'item_name' => array( 'type' => 'string' ),
                                    'item_description' => array( 'type' => 'string' ),
                                    'teller_remittable' => array( 'type' => 'boolean' ),
                                    'machine_remittable' => array( 'type' => 'boolean' ),
                                    'cashier_shift_num' => array( 'type' => 'string' ) ) );
                        }
                        $allocation_data['remittances'] = $remittances_data;
                        
                        $response = array(
                                'status' => 'ok',
                                'data' => $allocation_data
                            );
                    }
                    else
                    {
                        $response = $this->_bad_request( 'Missing allocation ID parameter' );
                    }
                    break;
                    
                case 'summary':
                    $store_id = $this->input->get( 'store_id' );
                    if( $store_id )
                    {
                        $this->load->library( 'store' );
                        $store = new Store();
                        $store = $store->get_by_id( $store_id );
                        
                        if( $store )
                        {
                            $allocations = $store->get_allocations_summary( array( 'format' => 'array' ) );
                            
                            $data = array();

                            foreach( $allocations as $row )
                            {
                                $item = array(
                                        'allocated_item_id' => $row['allocated_item_id'],
                                        'item_name' => $row['item_name'],
                                        'item_description' => $row['item_description'],
                                        'allocation' => $row['allocation'],
                                        'additional' => $row['additional'],
                                        'remitted' => $row['remitted']
                                    );
                                    
                                if( isset( $data[$row['id']] ) )
                                {
                                    $data[$row['id']]['items'][] = $item;
                                }
                                else
                                {
                                    $data[$row['id']] = array(
                                            'id' => $row['id'],
                                            'business_date' => $row['business_date'],
                                            'shift_id' => $row['shift_id'],
                                            'shift_num' => $row['shift_num'],
                                            'assignee' => $row['assignee'],
                                            'assignee_type' => $row['assignee_type'],
                                            'allocation_status' => $row['allocation_status'],
                                            'cashier_id' => $row['cashier_id']
                                        );
                                    $data[$row['id']]['items'] = array( $item );
                                }
                            }
                            
                            $response = array(
                                    'status' => 'ok',
                                    'data' => $data
                                );
                        }
                        else
                        {
                            $response = array(
                                    'status' => 'fail',
                                    'error' => 'Unable to load store record'
                                );
                        }
                    }
                    else
                    {
                        $response = $this->_bad_request( 'Missing store ID parameter' );
                    }    
                    break;
                    
                default:
                    $store_id = $this->input->get( 'store_id' );
                    if( $store_id )
                    {
                        $this->load->library( 'store' );
                        $store = new Store();
                        $store = $store->get_by_id( $store_id );
                        
                        if( $store )
                        {
                            $allocations = $store->get_allocations( array( 'format' => 'array' ) );
                            $response = array(
                                    'status' => 'ok',
                                    'data' => $allocations
                                );
                        }
                        else
                        {
                            $response = array(
                                    'status' => 'fail',
                                    'error' => 'Unable to load store record'
                                );
                        }
                    }
                    else
                    {
                        $response = $this->_bad_request( 'Missing store ID parameter' );
                    }
            }
        }
        elseif( $request_method == 'POST' )
        {
            switch( $action )
            {
                case 'process':
                    $this->load->library( 'allocation' );
                    $allocation = new Allocation();
                    $allocation = $allocation->load_from_data( $this->input->post() );
                    $this->db->trans_start();
                    $allocation->db_save();
                    $this->db->trans_complete();
                    
                    if( $this->db->trans_status() )
                    {
                        $response = array(
                                'status' => 'ok',
                                'data' => $allocation->as_array()
                            );
                    }
                    else
                    {
                        $response = array(
                                'status' => 'fail',
                                'errorMsg' => 'Failed to save allocation record'
                            );
                    }
                    break;
                    
                case 'allocate':
                case 'remit':
                    $this->load->library( 'allocation' );
                    $allocation = new Allocation();
                    $allocation = $allocation->load_from_data( $this->input->post() );
                    
                    $this->db->trans_start();
                    if( $action == 'allocate' )
                    {
                        $allocation->allocate();
                    }
                    elseif( $action == 'remit' )
                    {
                        $allocation->remit();
                    }
                    $this->db->trans_complete();
                    
                    if( $this->db->trans_status() )
                    {
                        $response = array(
                                'status' => 'ok',
                                'data' => $allocation->as_array()
                            );
                    }
                    else
                    {
                        $response = array(
                                'status' => 'fail',
                                'errorMsg' => 'Failed to allocate record'
                            );
                    }
                    break;
                    
                case 'cancel':                    
                    $this->load->library( 'allocation' );
                    $allocation = new Allocation();
                    $allocation = $allocation->load_from_data( $this->input->post() );
                    
                    if( $allocation )
                    {
                        $allocation = $allocation->cancel();
                        if( $allocation )
                        {
                            $response = array(
                                    'status' => 'ok',
                                    'data' => $allocation->as_array()
                                );
                        }
                        else
                        {
                            $response = array(
                                    'status' => 'fail',
                                    'errorMsg' => 'Unable to cancel allocation'
                                );
                        }
                    }
                    else
                    {
                        $response = array(
                                'status' => 'fail',
                                'errorMsg' => 'Unable to locate requested allocation'
                            );
                    }
                    break;
                    
                default:
                    $response = $this->_bad_request( 'Invalid action' );
            }
        }
        else
        {
            $response = $this->_bad_request( 'Invalid request' );
        }
        
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }
    
    
	private function _approve_adjustment( $params )
	{
		$adjustment_id = param( $params, 'id' );
		$this->load->library( 'adjustment' );
		$adjustment = new Adjustment();
		$adjustment = $adjustment->get_by_id( $adjustment_id );
		
		if( $adjustment )
		{
			$r = $adjustment->approve();
			if( $r )
			{
				$response = array(
						'status' => 'ok',
						'data' => $r->as_array()
					);
			}
			else
			{
				$response = array(
						'status' => 'fail',
						'error' => 'Unable to approve adjustment'
					);
			}
		}
		else
		{
			$response = array(
					'status' => 'fail',
					'error' => 'Cannot find adjustment record'
				);
		}
		
		return $response;
	}
}