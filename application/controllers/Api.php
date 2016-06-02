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
	
    
	public function _bad_request( $reason = NULL ) // implmented in API v1
	{
		$response = array(
			'status' => 'fail',
			'errorMsg' => isset( $reason ) ? $reason : 'Bad Request'
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
                case 'shifts': // implemented in API v1 - shifts/
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
                        $response = $this->_bad_request( 'Unable to create store instance' );
                    }
                    break;
                    
                case 'store_shifts': // implemented in API v1 - stores/:store_id/shifts
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
                case 'change_shift': // implemented in API v1 - session/shift/:shift_id
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
                                        'data' => array( 'shift' => $shift->as_array() )
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
                    
                default: // implemented in API v1 - session/store/:store_id
                    $store_id = $this->input->post( 'store_id' );

                    // TODO: Check if current user is allowed in store
                    $this->load->library( 'store' );
                    $store = new Store();
                    $store = $store->get_by_id( $store_id );

                    $this->session->current_store_id = $store_id;
                    
                    $store_shifts = array();
                    foreach( $store->get_store_shifts() as $shift )
                    {
                        $store_shifts[] = $shift->as_array();
                    }

                    $response = array(
                            'status' => 'ok',
                            'data' => array(
                                'store' => $store->as_array(),
                                'shifts' => $store_shifts )
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
        $shifts_data = NULL;
        
		if( $current_store )
		{
            $shifts = $current_store->get_store_shifts();
            $shifts_data = array();
            
            foreach( $shifts as $shift )
            {
                $shifts_data[] = $shift->as_array();
            }
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
                    'shift' => $shift_data,
                    'shifts' => $shifts_data
				)
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}


    public function stations() // implemented in API v1 - stations/
    {
        $query = $this->db->get( 'stations' );
        $stations = $query->result_array();
        
        $response = array(
                'status' => 'ok',
                'data' => array( 'stations' => $stations )
            );
            
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }


    public function items( $action = NULL)
    {
        $request_method = $this->input->method();
        
        switch( $request_method )
        {
            case 'get':
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
                        
                    case 'categories': // implemented in API v1 - categories/
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
                                'data' => array( 'categories' => $categories_data )
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
                break;
                
            default:
                $response = $this->_bad_request();
        }
            
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }


    public function users() // implemented in API v1 - users/
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
    
    public function user( $action = NULL ) // implemented in API v1
    {
        $request_method = $this->input->method();
        
        switch( $request_method )
        {
            case 'get': // implemented in API v1 - users/?q=:query
                switch( $action )
                {
                    case 'search':
                        $query = $this->input->get( 'q' );
                        $this->load->library( 'user' );
                        $user = new User();
                        $users = $user->search( $query );
                        $users_data = array();
                        foreach( $users as $user )
                        {
                            $users_data[] = $user->as_array();
                        }
                        $response = array(
                                'status' => 'ok',
                                'data' => array(
                                        'users' => $users_data
                                    )
                            );
                        break;
                        
                    default:
                        $response = $this->_bad_request();
                }
                break;
                
            default:
                $response = $this->_bad_request();
        }
        
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }


	public function stores() // implemented in API v1 - stores/
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
				'data' => array( 'stores' => $stores_data )
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

    public function shifts() // implemented in API v1 - shifts/
    {
        $shifts = $this->db->get( 'shifts' );
        $response = array(
                'status' => 'ok',
                'data' => $shifts->result_array()
            );
            
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }

	public function inventory( $action = NULL ) // implemented in API v1
	{
		$request_method = $this->input->method();
		
		switch( $request_method )
		{
            case 'get': // implemented in API v1 - stores/:store_id/items/
                $store_id = get_store_id( $this->input->get( 'store_id' ) );		
                if( $store_id )
                {
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
                            'data' => array(
                                    'inventory' => $inventory_data
                                )
                        );
                }
                else
                {
                    $response = $this->_bad_request( 'Unable to determine current store' );
                }
                break;
                
            case 'post': // implemented in API v1 - stores/:store_id/adjustments/
                switch( $action )
                {
                    case 'adjust':
                        $this->load->library( 'adjustment' );
                        $adjustment = new Adjustment();
                        $adjustment = $adjustment->load_from_data( $this->input->post() );
                        $r = $adjustment->db_save();
                            
                        $response = array(
                                'status' => 'ok',
                                'data' => $r->as_array()
                            );
                        break;
                        
                    default:
                        $response = $this->_bad_request();
                }
                break;
                
            default:
                $response = $this->_bad_request();
		}
		

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}


	public function transactions() // implemented in API v1
	{
        $request_method = $this->input->method();
        
        switch( $request_method )
        {
            case 'get': // implemented in API v1 - stores/:store_id/transactions/
                $store_id = get_store_id( $this->input->get( 'store_id' ) );
                if( $store_id )
                {
                    $this->load->library( 'store' );
                    $store = new Store();
                    $store = $store->get_by_id( $store_id );
                    $transactions = $store->get_transactions( array(
                            'format' => 'array',
                            'order' => 'transaction_datetime DESC, id DESC'
                        ) );

                    $response = array(
                            'status' => 'ok',
                            'data' => array(
                                    'transactions' => $transactions
                                ),
                            'sql' => $this->db->last_query()
                        );
                }
                else
                {
                    $response = $this->_bad_request( 'Unable to determine current store' );
                }
                break;
                
            default:
                $response = $this->_bad_request();
        }

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}


	/**
	 * @param string Type can be: 'out', 'in'
	 */
	public function transfer( $action = NULL ) // implemented in API v1
	{
        $request_method = $this->input->method();
        
        switch( $request_method )
        {
            case 'get':
                switch( $action )
                {
                    case 'receipts': // implemented in API v1 - stores/:store_id/receipts
                        $store_id = get_store_id( $this->input->get( 'store_id' ) );
                        if( $store_id )
                        {
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
                                    'data' => array(
                                            'receipts' => $receipts_data,
                                            'pending' => $pending_receipts
                                        )
                                );
                        }
                        else
                        {
                            $response = $this->_bad_request( 'Unable to determine current store' );
                        }
                        break;
                        
                    case 'item': // implemented in API v1 - transfers/:transfer_id
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
                        
                    case 'items': // not used
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
                        
                    default: // implemented in API v1 - store/:store_id/transfers
                        $store_id = get_store_id( $this->input->get( 'store_id' ) );
                        if( $store_id )
                        {
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
                                    'data' => array(
                                            'transfers' => $transfers_data,
                                            'pending' => $pending_transfers
                                        )
                                );
                        }
                        else
                        {
                            $response = $this->_bad_request( 'Unable to determine current store' );
                        }
                }
                break;
            
            case 'post':
                switch( $action )
                {
                    case 'create': // implemented in API v1 - POST:transfers/
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
                    
                    case 'schedule': // no longer in use
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
                        
                    case 'approve': // implemented in API v1 - POST:transfers/approve			
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
                        
                    case 'receive': // implemented in API v1 - POST:transfers/receive
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
                        
                    case 'cancel': // implemented in API v1 - POST:transfers/cancel
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
                break;
            default:
                $response = $this->_bad_request();
        }

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

	
	public function adjustment( $action = NULL )
	{
		$request_method = $this->input->method();
		
		switch( $request_method )
		{
			case 'get':
                switch( $action )
                {
                    case 'list': // implemented in API v1 - stores/:store_id/adjustments
                        $store_id = get_store_id( $this->input->get( 'store_id' ) );
                        if( $store_id )
                        {
                            $this->load->library( 'store' );
                            $store = new Store();
                            $store = $store->get_by_id( $store_id );		
                            $adjustments = $store->get_adjustments();
                            $adjustments_data = array();
                            foreach( $adjustments as $adjustment )
                            {
                                $adjustments_data[] = $adjustment->as_array();
                            }
                            $pending_adjustments = $store->count_pending_adjustments();

                            $response = array(
                                    'status' => 'ok',
                                    'data' => array(
                                            'adjustments' => $adjustments_data,
                                            'pending' => $pending_adjustments
                                        )
                                );
                        }
                        else
                        {
                            $response = $this->_bad_request( 'Unable to determine current store' );
                        }
                        break;
                        
                    default:
                        $response = $this->_bad_request();
                }
				break;
				
			case 'post':
				switch( $action )
				{
					case 'approve':
						$response = $this->_approve_adjustment( $this->input->post() );
						break;
                        
					default:
						$response = $this->_bad_request();
				}
				break;
				
			default:
				$response = $this->_bad_request();
		}
		
		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

	
    public function conversion( $action = NULL )
    {
        $request_method = $this->input->method();
        
        switch( $request_method )
        {
            case 'get':
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
                        
                    case 'list': //implemented in API v1 - stores/:store_id/conversions
                        $store_id = get_store_id( $this->input->get( 'store_id' ) );
                        
                        if( $store_id )
                        {
                            $this->load->library( 'store' );
                            $store = new Store();
                            $store = $store->get_by_id( $store_id );
                            $conversions = $store->get_conversions();
                            $conversions_data = array();
                            
                            foreach( $conversions as $conversion )
                            {
                                $conversions_data[] = $conversion->as_array();
                            }
                            
                            $response = array(
                                    'status' => 'ok',
                                    'data' => array( 'conversions' => $conversions_data )
                                );
                        }
                        else
                        {
                            $response = $this->_bad_request( 'Unable to determine current store' );
                        }
                        break;
                        
                    default:
                        $response = $this->_bad_request();
                }
                break;
                
            case 'post':
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
                break;
                
            default:
                $response = $this->_bad_request();
        }
        
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }
  
    
    public function mopping( $action = NULL )
    {
        $request_method = $this->input->method();
        
        switch( $request_method )
        {
            case 'get':
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
                        
                    case 'summary': // implemented in API v1 - 'stores/:store_id/collections_summary'
                        $store_id = get_store_id( $this->input->get( 'store_id' ) );
                        
                        if( $store_id )
                        {
                            $this->load->library( 'store' );
                            $store = new Store();
                            $store = $store->get_by_id( $store_id );
                            $collections = $store->get_collections_summary();
                            
                            $collections_data = array();

                            foreach( $collections as $row )
                            {
                                $item = array(
                                        'item_id' => $row['item_id'],
                                        'item_name' => $row['item_name'],
                                        'item_description' => $row['item_description'],
                                        'quantity' => $row['quantity']
                                    );
                                    
                                if( isset( $collections_data[$row['mopping_id']] ) )
                                {
                                    $collections_data[$row['mopping_id']]['items'][] = $item;
                                }
                                else
                                {
                                    $collections_data[$row['mopping_id']] = array(
                                            'id' => $row['id'],
                                            'processing_datetime' => $row['processing_datetime'],
                                            'business_date' => $row['business_date'],
                                            'shift_id' => $row['shift_id'],
                                            'shift_num' => $row['shift_num'],
                                            'cashier_shift_id' => $row['cashier_shift_id'],
                                            'cashier_shift_num' => $row['cashier_shift_num']
                                        );
                                    $collections_data[$row['mopping_id']]['items'] = array( $item );
                                }
                            }
                            
                            $response = array(
                                    'status' => 'ok',
                                    'data' => array( 'collections' => array_values($collections_data) )
                                );
                        }
                        else
                        {
                            $response = $this->_bad_request( 'Unable to determine current store' );
                        }
                        break;
                        
                    case 'list':
                        $store_id = get_store_id( $this->input->get( 'store_id' ) );
                        
                        if( $store_id )
                        {
                            $this->load->library( 'store' );
                            $store = new Store();
                            $store = $store->get_by_id( $store_id );
                            $collections = $store->get_collections();
                            $collections_data = array();
                            
                            foreach( $collections as $collection )
                            {
                                $collections_data = $collection->as_array();
                            }
                            
                            $response = array(
                                    'status' => 'ok',
                                    'data' => array( 'collections' => $collections_data )
                                );
                        }
                        else
                        {
                            $response = $this->_bad_request( 'Unable to determine current store' );
                        }
                        break;
                        
                    default:
                        $response = $this->_bad_request();
                }
                break;
                
            case 'post':
                switch( $action )
                {
                    case 'process':
                        $this->load->library( 'mopping' );
                        $mopping = new Mopping();
                        $mopping = $mopping->load_from_data( $this->input->post() );
                        $mopping->db_save();
                        
                        $response = array(
                                'status' => 'ok',
                                'data' => array( 'collections' => $mopping->as_array() )
                            );
                        break;
                        
                    default:
                        $response = $this->_bad_request();
                }
                break;

            default:
                $response = $this->_bad_request();
        }
        
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
    }
  
    
    public function allocations( $action = NULL )
    {
        $request_method = $this->input->method();
        
        switch( $request_method )
        {
            case 'get':
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
                        
                    case 'summary': // implemented in API v1 - stores/:store_id/allocations_summary
                        $store_id = get_store_id( $this->input->get( 'store_id' ) );
                        if( $store_id )
                        {
                            $this->load->library( 'store' );
                            $store = new Store();
                            $store = $store->get_by_id( $store_id );
                            
                            if( $store )
                            {
                                $allocations = $store->get_allocations_summary( array( 'format' => 'array' ) );
                                
                                $allocations_data = array();

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
                                        
                                    if( isset( $allocations_data[$row['id']] ) )
                                    {
                                        $allocations_data[$row['id']]['items'][] = $item;
                                    }
                                    else
                                    {
                                        $allocations_data[$row['id']] = array(
                                                'id' => $row['id'],
                                                'business_date' => $row['business_date'],
                                                'shift_id' => $row['shift_id'],
                                                'shift_num' => $row['shift_num'],
                                                'assignee' => $row['assignee'],
                                                'assignee_type' => $row['assignee_type'],
                                                'allocation_status' => $row['allocation_status'],
                                                'cashier_id' => $row['cashier_id']
                                            );
                                        $allocations_data[$row['id']]['items'] = array( $item );
                                    }
                                }
                                
                                $response = array(
                                        'status' => 'ok',
                                        'data' => array( 'allocations' => array_values( $allocations_data ) )
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
                            $response = $this->_bad_request( 'Unable to determine current store' );
                        }    
                        break;
                        
                    case 'list':
                        $store_id = get_store_id( $this->input->get( 'store_id' ) );
                        if( $store_id )
                        {
                            $this->load->library( 'store' );
                            $store = new Store();
                            $store = $store->get_by_id( $store_id );
                            
                            if( $store )
                            {
                                $allocations = $store->get_allocations();
                                $allocations_data = array();
                                
                                foreach( $allocations as $allocation )
                                {
                                    $allocations_data[] = $allocation->as_array();
                                }
                                
                                $response = array(
                                        'status' => 'ok',
                                        'data' => array( 'allocations' => $allocations_data )
                                    );
                            }
                            else
                            {
                                $response = $this->_bad_request( 'Unable to load store record' );
                            }
                        }
                        else
                        {
                            $response = $this->_bad_request( 'Unable to determine current store' );
                        }
                        break;
                        
                    default:
                        $response = $this->_bad_request();
                }
                break;
                
            case 'post':
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
                        $response = $this->_bad_request();
                }
                break;
                
            default:
                $response = $this->_bad_request();
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