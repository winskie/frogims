<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_v1 extends CI_Controller {

    public $response = NULL;

    private function _response( $data, $status_code = 200 )
    {
        $this->response = array(
            'status' => 'ok',
            'data' => $data
        );

        $this->output->set_status_header( $status_code );
    }


    private function _error( $status_code = NULL, $description = NULL )
    {
        $this->response = array(
            'status' => 'fail',
            'errorMsg' => isset( $description ) ? $description : 'Unknown error'
        );

        $this->output->set_status_header( $status_code );
    }


    private function _send_response()
    {
        $this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $this->response ) );
    }


    public function test( $v )
    {
        var_dump( $this->uri->rsegment( 3 ) );
    }

    public function adjustments()
    {
        $request_method = $this->input->method();

        $this->load->library( 'adjustment' );
        $Adjustment = new Adjustment();

        switch( $request_method )
        {
            case 'get':
                $adjustment_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

                if( $adjustment_id )
                {
                    $adjustment = $Adjustment->get_by_id( $adjustment_id );
                    if( $adjustment )
                    {
                        $adjustment_data = $adjustment->as_array( array(
                            'item_name' => array( 'type' => 'string' ),
                            'item_description' => array( 'type' => 'string' ),
                            'full_name' => array( 'type' => 'string' ) ) );

                        $this->_response( $adjustment_data );
                    }
                    else
                    {
                        $this->_error( 404, 'Adjustment record not found' );
                    }
                }
                else
                {
                    $this->_error( 400, 'Missing required adjustment ID' );
                }
                break;

            case 'post':
                $action = param_type( $this->uri->rsegment( 3 ), 'string' );
                $adjustment_id = param( $this->input->post(), 'id' );
                $adjustment = $Adjustment->load_from_data( $this->input->post() );

                $this->db->trans_start();
                switch( $action )
                {
                    case 'approve':
                        $result = $adjustment->approve();
                        break;

                    case 'cancel':
                        $result = $adjustment->cancel();
                        break;

                    default:
                        $result = $adjustment->db_save();
                }

                if( $result )
                {
                    $adjustment_data = $adjustment->as_array();
                    $this->db->trans_complete();

                    if( $this->db->trans_status() )
                    {
                        $this->_response( $adjustment_data, $adjustment_id ? 200 : 201 );
                    }
                    else
                    {
                        $this->_error( 500, 'A database error has occurred while trying to save adjustment record' );
                    }
                }
                else
                {
                    $messages = get_messages();
                    $this->_error( 200, $messages );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function allocations()
    {
        $request_method = $this->input->method();

        $this->load->library( 'allocation' );
        $Allocation = new Allocation();

        switch( $request_method )
        {
            case 'get':
                $allocation_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

                if( $allocation_id )
                {
                    $allocation = $Allocation->get_by_id( $allocation_id );
                    if( $allocation )
                    {
                        $allocation_data = $allocation->as_array();
                        $allocation_items = $allocation->get_allocations();
                        $remittance_items = $allocation->get_remittances();
                        $allocation_items_data = array();
                        $remittance_items_data = array();

                        foreach( $allocation_items as $item )
                        {
                            $allocation_items_data[] = $item->as_array( array(
                                'category_name' => array( 'type' => 'string' ),
                                'category_type' => array( 'type' => 'integer' ),
                                'item_name' => array( 'type' => 'string' ),
                                'item_description' => array( 'type' => 'string' ),
                                'teller_allocatable' => array( 'type' => 'boolean' ),
                                'machine_allocatable' => array( 'type' => 'boolean' ),
                                'cashier_shift_num' => array( 'type' => 'string' ) ) );
                        }
                        $allocation_data['allocations'] = $allocation_items_data;

                        foreach( $remittance_items as $item )
                        {
                            $remittance_items_data[] = $item->as_array( array(
                                'category_name' => array( 'type' => 'string' ),
                                'category_type' => array( 'type' => 'integer' ),
                                'item_name' => array( 'type' => 'string' ),
                                'item_description' => array( 'type' => 'string' ),
                                'teller_remittable' => array( 'type' => 'boolean' ),
                                'machine_remittable' => array( 'type' => 'boolean' ),
                                'cashier_shift_num' => array( 'type' => 'string' ) ) );
                        }
                        $allocation_data['remittances'] = $remittance_items_data;

                        $this->_response( $allocation_data );
                    }
                    else
                    {
                        $this->_error( 404, 'Allocation record not found' );
                    }
                }
                else
                {
                    $this->_error( 400, 'Missing required allocation ID' );
                }
                break;

            case 'post':
                $action = param_type( $this->uri->rsegment( 3 ), 'string' );
                $allocation_id = param( $this->input->post(), 'id' );
                $allocation = $Allocation->load_from_data( $this->input->post() );

                $this->db->trans_start();
                switch( $action )
                {
                    case 'allocate':
                        $result = $allocation->allocate();
                        break;

                    case 'remit':
                        $result = $allocation->remit();
                        break;

                    case 'cancel':
                        $result = $allocation->cancel();
                        break;

                    default:
                        $result = $allocation->db_save();
                }

                if( $result )
                {
                    $this->db->trans_complete();

                    if( $this->db->trans_status() )
                    {
                        $this->_response( $allocation->as_array(), $allocation_id ? 200 : 201 );
                    }
                    else
                    {
                        $this->_error( 500, 'A database error has occurred while trying to save transfer record' );
                    }
                }
                else
                {
                    $messages = get_messages();
                    $this->_error( 200, $messages );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function categories()
    {
        $request_method = $this->input->method();

        $category_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

        $this->load->library( 'item_category' );
        $Category = new Item_category();

        switch( $request_method )
        {
            case 'get':
                if( $category_id )
                {

                }
                else
                {
                    $categories = $Category->get_categories( array( 'format' => 'array' ) );
                    $this->_response( $categories );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function collections()
    {
        $request_method = $this->input->method();

        $this->load->library( 'mopping' );
        $Collection = new Mopping();

        switch( $request_method )
        {
            case 'get':
                $collection_id = param_type( $this->uri->rsegment( 3 ), 'integer' );
                $relation = param_type( $this->uri->rsegment( 4 ), 'string' );

                if( $collection_id )
                {
                    $collection = $Collection->get_by_id( $collection_id );
                    if( $collection )
                    {
                        $collection_data = $collection->as_array();
                        $collection_items = $collection->get_items();
                        $collection_items_data = array();
                        foreach( $collection_items as $item )
                        {
                            $collection_items_data[] = $item->as_array( array(
                                'station_name' => array( 'type' => 'string' ),
                                'mopped_item_name' => array( 'type' => 'string' ),
                                'convert_to_name' => array( 'type' => 'string' ),
                                'mopped_station_name' => array( 'type' => 'string' ),
                                'processor_name' => array( 'type' => 'string' ) ) );
                        }
                        $collection_data['items'] = $collection_items_data;
                        $this->_response( $collection_data );
                    }
                    else
                    {
                        $this->_error( 404, 'Collection record not found' );
                    }
                }
                else
                {
                    $this->_error( 400, 'Missing required collection ID' );
                }
                break;

            case 'post':
                $action = param_type( $this->uri->rsegment( 3 ), 'string' );
                $collection_id = param( $this->input->post(), 'id' );
                $collection = $Collection->load_from_data( $this->input->post() );

                $this->db->trans_start();
                switch( $action )
                {
                    default:
                        $result = $collection->db_save();
                }
                if( $result )
                {
                    $this->db->trans_complete();

                    if( $this->db->trans_status() )
                    {
                        $this->_response( $collection->as_array(), $collection_id ? 200 : 201 );
                    }
                    else
                    {
                        $this->_error( 500, 'A database error has occurred while trying to save transfer record' );
                    }
                }
                else
                {
                    $messages = get_messages();
                    $this->_error( 200, $messages );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function conversion_factors()
    {
        $request_method = $this->input->method();
        $sub_resource = $this->uri->rsegment( 3 );

        $this->load->library( 'conversion_table' );
        $Table = new Conversion_table();

        switch( $request_method )
        {
            case 'get':
                switch( $sub_resource )
                {
                    case 'packing':
                        $data = $Table->get_packing_data( array( 'format' => 'array' ) );
                        $this->_response( $data );
                        break;

                    default:
                        $conversion_factor_id = param_type( $this->uri->rsegment( 3 ), 'integer' );
                        if( $conversion_factor_id )
                        {
                            $data = $Table->get_by_id( $conversion_table_id );
                            if( $data )
                            {
                                $this->_response( $data->as_array() );
                            }
                            else
                            {
                                $this->_error( 404, 'Conversion table record not found' );
                            }
                        }
                        else
                        {
                            $data = $Table->get_conversion_data( array( 'format' => 'array' ) );
                            $this->_response( $data );
                        }
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function conversions()
    {
        $request_method = $this->input->method();

        $this->load->library( 'conversion' );
        $Conversion = new Conversion();

        switch( $request_method )
        {
            case 'get':
                $action = $this->uri->rsegment( 3 );
                switch( $action )
                {
                    case 'factor':
                        $source_item_id = $this->input->get( 'source' );
                        $target_item_id = $this->input->get( 'target' );

                        if( ! $source_item_id || ! $target_item_id )
                        {
                            $this->_error( 400, 'Missing required item ID' );
                        }
                        else
                        {
                            $conversion = $Conversion->get_conversion_factor( $source_item_id, $target_item_id );
                            if( $conversion )
                            {
                                $this->_response( array(
                                    'factor' => $conversion['factor'],
                                    'mode' => $conversion['mode'] ) );
                            }
                            else
                            {
                                $this->_error( 404, 'Unable to locate conversion factor data' );
                            }
                        }
                        break;

                    default:
                        $conversion_id = param_type( $this->uri->rsegment( 3 ), 'integer' );
                        if( $conversion_id )
                        {
                            $conversion = $Conversion->get_by_id( $conversion_id );
                            if( $conversion )
                            {
                                $this->_response( $conversion->as_array() );
                            }
                            else
                            {
                                $this->_error( 404, 'Conversion record not found' );
                            }
                        }
                        else
                        {
                            $conversions = $Conversion->get_conversions( array( 'format' => 'array' ) );
                            $this->_response( $conversions );
                        }
                }
                break;

            case 'post':
                $action = param_type( $this->uri->rsegment( 3 ), 'string' );
                $conversion_id = param( $this->input->post(), 'id' );
                $conversion = $Conversion->load_from_data( $this->input->post() );

                $this->db->trans_start();
                switch( $action )
                {
                    case 'approve':
                        $result = $conversion->approve();
                        break;

                    case 'cancel':
                        $result = $conversion->cancel();
                        break;

                    default:
                        $result = $conversion->db_save();
                }

                if( $result )
                {
                    $conversion_data = $conversion->as_array();
                    $this->db->trans_complete();

                    if( $this->db->trans_status() )
                    {
                        $this->_response( $conversion_data, $conversion_id ? 200 : 201 );
                    }
                    else
                    {
                        $this->_error( 500, 'A database error has occurred while trying to save adjustment record' );
                    }
                }
                else
                {
                    $messages = get_messages();
                    $this->_error( 200, $messages );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function groups()
    {
        $request_method = $this->input->method();
        $action = $this->uri->rsegment( 3 );

        $this->load->library( 'group' );
        $Group = new Group();

        switch( $request_method )
        {
            case 'get':
                switch( $action )
                {
                    case 'search':
                        $q = param_type( $this->input->get( 'q' ), 'string' );
                        $groups = $Group->search( $q );
                        $groups_array = array();
                        foreach( $groups as $group )
                        {
                            $groups_array[] = $group->as_array();
                        }

                        $this->_response( $groups_array );
                        break;

                    default:
                        $group_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

                        if( $group_id )
                        { // get specific group
                            $group = $Group->get_by_id( $group_id );
                            if( $group )
                            {
                                $group_data = $group->as_array();

                                $this->_response( $group_data );
                            }
                            else
                            {
                                $this->_error( 404, 'Group record not found' );
                            }
                        }
                        else
                        { // get list of groups
                            $params = array(
                                'q' => param( $this->input->get(), 'q' ),
                                'page' => param( $this->input->get(), 'page' ),
                                'limit' => param( $this->input->get(), 'limit' ),
                                'format' => param( $this->input->get(), 'format' ),
                                'order' => param( $this->input->get(), 'order' ) );

                            $groups = $Group->get_groups( $params );
                            $sql = $this->db->last_query();
                            $total_groups = $Group->count_groups( $params );
                            $groups_array = array();
                            foreach( $groups as $group )
                            {
                                $groups_array[] = $group->as_array( array(
                                    'member_count' => array( 'type' => 'integer' ) ) );
                            }

                            $this->_response( array(
                                'groups' => $groups_array,
                                'total' => $total_groups,
                                'sql' => $sql ) );
                        }
                }
                break;

            case 'post':
                $action = param_type( $this->uri->rsegment( 3 ), 'string' );
                $group_id = param( $this->input->post(), 'id' );

                $group = $Group->load_from_data( $this->input->post() );

                $this->db->trans_start();
                switch( $action )
                {
                    default:
                        $result = $group->db_save();
                }
                if( $result )
                {
                    $group_data = $group->as_array();
                    $this->db->trans_complete();

                    $this->_response( $group_data, $group_id ? 200 : 201 );
                }
                else
                {
                    $messages = get_messages();
                    $this->_error( 202, $messages );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function inventory()
    {
        $request_method = $this->input->method();
        $relation = param_type( $this->uri->rsegment( 3 ), 'string' );

        $this->load->library( 'inventory' );
        $Inventory = new Inventory();

        switch( $request_method )
        {
            case 'get':
                switch( $relation )
                    {
                        case 'system':
                            $this->db->select( 's.store_name, s.store_code, i.item_name, si.quantity' );
                            $this->db->join( 'stores s', 's.id = si.store_id', 'left' );
                            $this->db->join( 'items i', 'i.id = si.item_id', 'left' );
                            $this->db->order_by( 'si.store_id ASC, si.item_id ASC' );
                            $data = $this->db->get( 'store_inventory si');
                            $data = $data->result_array();

                            $stores = array();
                            $series = array();

                            foreach( $data as $row )
                            {
                                $index = array_search( $row['store_code'], $stores );
                                if( $index !== FALSE )
                                {
                                    $series[$row['item_name']]['item'] = $row['item_name'];
                                    $series[$row['item_name']]['data'][] = (int) $row['quantity'];
                                }
                                else
                                {
                                    $stores[] = $row['store_code'];
                                    $series[$row['item_name']]['item'] = $row['item_name'];
                                    $series[$row['item_name']]['data'][] = (int) $row['quantity'];
                                }
                            }

                            $data_array = array(
                                    'stores' => $stores,
                                    'series' => array_values( $series )
                                );

                            $this->_response( $data_array );
                            break;

                        case 'distribution':
                            $this->db->select( 'i.item_group, s.store_code, s.id,
                                    SUM( IF( ct.conversion_factor IS NULL, si.quantity, si.quantity * ct.conversion_factor ) ) AS quantity' );
                            $this->db->join( 'items i', 'i.id = si.item_id', 'left' );
                            $this->db->join( 'conversion_table ct', 'ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id', 'left' );
                            $this->db->join( 'stores s', 's.id = si.store_id', 'left' );
                            $this->db->where( 'i.item_group IS NOT NULL' );
                            $this->db->group_by( 'i.item_group, s.store_code, s.id' );
                            $this->db->order_by( 'i.item_group DESC, s.id DESC' );
                            $data = $this->db->get( 'store_inventory si' );
                            $data = $data->result_array();


                            $groups = array();
                            $series = array();

                            foreach( $data as $row )
                            {
                                $index = array_search( $row['item_group'], $groups );
                                if( $index !== FALSE )
                                {
                                    $series[$row['store_code']]['store'] = $row['store_code'];
                                    $series[$row['store_code']]['data'][] = (int) $row['quantity'];
                                }
                                else
                                {
                                    $groups[] = $row['item_group'];
                                    $series[$row['store_code']]['store'] = $row['store_code'];
                                    $series[$row['store_code']]['data'][] = (int) $row['quantity'];
                                }
                            }

                            $data_array = array(
                                    'groups' => $groups,
                                    'series' => array_values( $series )
                                );

                            $this->_response( $data_array );
                            break;

                        case 'movement_week':
                            break;

                        default:
                            $this->_error( 404, sprintf( '%s resource not found', $relation ) );
                    }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function items()
    {
        $request_method = $this->input->method();

        switch( $request_method )
        {
            case 'get':
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function login_info()
	{
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
        $permissions_data = NULL;

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

            $group = $current_user->get_group();
            if( $group )
            {
                $permissions_data = $group->get_permissions();
            }
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
                    'shifts' => $shifts_data,
                    'is_admin' => is_admin(),
                    'permissions' => $permissions_data
				)
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

    public function reports()
    {
        $request_method = $this->input->method();
        $report_name = param_type( $this->uri->rsegment( 3 ), 'string' );

        $this->load->library( 'report' );
        $Report = new Report();

        switch( $request_method )
        {
            case 'get':
                switch( $report_name )
                {
                    case 'history':
                        $params = array(
                                'date' => param( $this->input->get(), 'date' ),
                                'store' => param( $this->input->get(), 'store' )
                            );
                        $data = $Report->history( $params );

                        $data_array = array();

                        foreach( $data as $row )
                        {
                            if( isset( $data_array[$row['item_id']] ) )
                            {
                                $data_array[$row['item_id']]['data'][$row['timestamp']] = $row['balance'];
                            }
                            else
                            {
                                $data_array[$row['item_id']] = array(
                                    'data' => array( $row['timestamp'] => $row['balance'] ),
                                    'init_balance' => $row['balance'] - $row['quantity'],
                                    'name' => $row['item_name'],
                                    'id' => $row['item_id'] );
                            }
                        }

                        $start_time = round( strtotime( 'now - 1 day' ) / 60 ) * 60 ;
                        $end_time = round( strtotime( 'now' ) / 60 ) * 60;

                        $this->_response( array(
                            'series' => array_values( $data_array ),
                            'start_time' => $start_time,
                            'end_time' => $end_time ) );
                        //$this->_response( $data );
                        break;

                    default:
                        $this->_error( 404, 'Report not found' );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function session()
    {
        /*
        change shift: /session/shift/:shift_id
        change store: /session/store/:store_id
        */
        $request_method = $this->input->method();

        $action = param_type( $this->uri->rsegment( 3 ), 'string' );

        switch( $request_method )
        {
            case 'get': // retrieves
                break;

            case 'patch': // partial updates
                switch( $action )
                {
                    case 'shift': // change current shift
                        $shift_id = param_type( $this->uri->rsegment( 4 ), 'integer' );

                        if( $shift_id )
                        {
                            $this->load->library( 'shift' );
                            $Shift = new Shift();
                            $new_shift = $Shift->get_by_id( $shift_id );

                            $this->load->library( 'store' );
                            $Store = new Store();
                            $current_store = $Store->get_by_id( $this->session->current_store_id );

                            if( $current_store )
                            {
                                if( $new_shift->get( 'store_type' ) == $current_store->get( 'store_type') )
                                {
                                    $this->session->current_shift_id = $new_shift->get( 'id' );
                                    $this->_response( $new_shift->as_array() );
                                }
                                else
                                {
                                    $this->_error( 403, 'Invalid shift for current store' );
                                }
                            }
                            else
                            {
                                $this->_error( 404, 'Shift record not found' );
                            }
                        }
                        else
                        {
                            $this->_error( 400, 'Unspecified shift ID to switch to' );
                        }

                        break;

                    case 'store': // change current store
                        $store_id = param_type( $this->uri->rsegment( 4 ), 'integer' );
                        $previous_store_id = current_store();

                        if( $store_id )
                        {
                            $this->load->library( 'store' );
                            $Store = new Store();
                            $new_store = $Store->get_by_id( $store_id );

                            if( $new_store )
                            {
                                if( $new_store->is_member( current_user( TRUE ) ) || TRUE )
                                {
                                    $this->session->current_store_id = $new_store->get( 'id' );
                                    $shifts = $new_store->get_shifts();
                                    $shifts_data = array();
                                    foreach( $shifts as $shift )
                                    {
                                        $shifts_data[] = $shift->as_array();
                                    }
                                    $suggested_shift = $new_store->get_suggested_shift();
                                    $this->session->current_shift_id = $suggested_shift ? $suggested_shift->get( 'id' ) : $shifts[0]->get( 'id' );
                                    $this->_response( array(
                                        'store' => $new_store->as_array(),
                                        'shifts' => $shifts_data,
                                        'suggested_shift' => $suggested_shift ? $suggested_shift->as_array() : NULL
                                    ) );
                                }
                                else
                                {
                                    $this->_error( 403, 'Not allowed in the specified store' );
                                }
                            }
                            else
                            {
                                $this->_error( 404, 'Store record not found' );
                            }
                        }
                        else
                        {
                            $this->_error( 400, 'Unspecified store ID to switch to' );
                        }
                        break;
                }
                break;

            default:
        }

        $this->_send_response();
    }

    public function shifts()
    {
        $request_method = $this->input->method();
        $shift_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

        $this->load->library( 'shift' );
        $Shift = new Shift();

        switch( $request_method )
        {
            case 'get':
                if( $shift_id )
                {

                }
                else
                {
                    $params = array_merge( array(
                        'format' => 'array'
                    ), $this->input->get() );

                    $shifts = $Shift->get_shifts( $params );
                    $this->_response( $shifts );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method) );
        }

        $this->_send_response();
    }

    public function stations()
    {
        $request_method = $this->input->method();

        switch( $request_method )
        {
            case 'get':
                $query = $this->db->get( 'stations' );
                $stations = $query->result_array();

                $this->_response( $stations );
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function stores()
    {
        $request_method = $this->input->method();

        $store_id = param_type( $this->uri->rsegment( 3 ), 'integer' );
        $relation = param_type( $this->uri->rsegment( 4 ), 'string' );

        $this->load->library( 'store' );
        $Store = new Store();

        switch( $request_method )
        {
            case 'get': // retrieves
                if( $store_id )
                { // Get specific store
                    $store = $Store->get_by_id( $store_id );
                    if( $store )
                    {
                        switch( $relation )
                        {
                            case NULL: // store data
                                $this->_response( $store->as_array() );
                                break;

                            case 'adjustments': // adjustments
                                $params = array(
                                        'date' => param( $this->input->get(), 'date' ),
                                        'item' => param( $this->input->get(), 'item' ),
                                        'status' => param( $this->input->get(), 'status' ),
                                        'page' => param( $this->input->get(), 'page' ),
                                        'limit' => param( $this->input->get(), 'limit' ),
                                        'format' => 'array'
                                    );
                                $adjustments = $store->get_adjustments( $params );
                                $total_adjustments = $store->count_adjustments( $params );
                                $pending_adjustments = $store->count_pending_adjustments( $params );

                                $this->_response( array(
                                    'adjustments' => $adjustments,
                                    'total' => $total_adjustments,
                                    'pending' => $pending_adjustments ) );
                                break;

                            case 'allocations_summary': // allocations
                                $params = array(
                                    'date' => param( $this->input->get(), 'date' ),
                                    'assignee_type' => param( $this->input->get(), 'assignee_type' ),
                                    'status' => param( $this->input->get(), 'status' ),
                                    'page' => param( $this->input->get(), 'page' ),
                                    'limit' => param( $this->input->get(), 'limit' ),
                                );
                                $allocations = $store->get_allocations_summary( $params );
                                $total_allocations = $store->count_allocations( $params );
                                $pending_allocations = $store->count_pending_allocations( $params );

                                $allocations_data = array();
                                foreach( $allocations as $allocation )
                                {
                                    $item = array(
                                        'allocated_item_id' => $allocation['allocated_item_id'],
                                        'item_name' => $allocation['item_name'],
                                        'item_description' => $allocation['item_description'],
                                        'allocation' => $allocation['allocation'],
                                        'additional' => $allocation['additional'],
                                        'remitted' => $allocation['remitted']
                                    );

                                    if( isset( $allocations_data[$allocation['id']] ) )
                                    {
                                        $allocations_data[$allocation['id']]['items'][] = $item;
                                    }
                                    else
                                    {
                                        $allocations_data[$allocation['id']] = array(
                                            'id' => $allocation['id'],
                                            'business_date' => $allocation['business_date'],
                                            'shift_id' => $allocation['shift_id'],
                                            'shift_num' => $allocation['shift_num'],
                                            'assignee' => $allocation['assignee'],
                                            'assignee_type' => $allocation['assignee_type'],
                                            'allocation_status' => $allocation['allocation_status'],
                                            'cashier_id' => $allocation['cashier_id']
                                        );
                                        $allocations_data[$allocation['id']]['items'] = array( $item );
                                    }
                                }

                                $this->_response( array(
                                    'allocations' => array_values( $allocations_data ),
                                    'total' => $total_allocations,
                                    'pending' => $pending_allocations ) );
                                break;

                            case 'collections_summary': // mopping collections
                                $params = array(
                                        'processing_date' => param( $this->input->get(), 'processing_date' ),
                                        'business_date' => param( $this->input->get(), 'business_date' ),
                                        'page' => param( $this->input->get(), 'page' ),
                                        'limit' => param( $this->input->get(), 'limit' ),
                                    );
                                $collections = $store->get_collections_summary( $params );
                                $total_collections = $store->count_collections( $params );
                                $collections_data = array();
                                foreach( $collections as $collection )
                                {
                                    $item = array(
                                        'item_id' => $collection['item_id'],
                                        'item_name' => $collection['item_name'],
                                        'item_description' => $collection['item_description'],
                                        'quantity' => $collection['quantity'],
                                        'status' => $collection['mopping_item_status']
                                    );

                                    if( isset( $collections_data[$collection['mopping_id']] ) )
                                    {
                                        $collections_data[$collection['mopping_id']]['items'][] = $item;
                                    }
                                    else
                                    {
                                        $collections_data[$collection['mopping_id']] = array(
                                            'id' => $collection['id'],
                                            'processing_datetime' => $collection['processing_datetime'],
                                            'business_date' => $collection['business_date'],
                                            'shift_id' => $collection['shift_id'],
                                            'shift_num' => $collection['shift_num'],
                                            'cashier_shift_id' => $collection['cashier_shift_id'],
                                            'cashier_shift_num' => $collection['cashier_shift_num']
                                        );
                                        $collections_data[$collection['mopping_id']]['items'] = array( $item );
                                    }
                                }
                                $this->_response( array(
                                    'collections' => array_values( $collections_data ),
                                    'total' => $total_collections ) );
                                break;
                            case 'conversions': // item conversions
                                $params = array(
                                        'date' => param( $this->input->get(), 'date' ),
                                        'input' => param( $this->input->get(), 'input' ),
                                        'output' => param( $this->input->get(), 'output' ),
                                        'page' => param( $this->input->get(), 'page' ),
                                        'limit' => param( $this->input->get(), 'limit' ),
                                        'format' => 'array'
                                    );
                                $conversions = $store->get_conversions( $params );
                                $total_conversions = $store->count_conversions( $params );
                                $pending_conversions = $store->count_pending_conversions( $params );

                                $this->_response( array(
                                    'conversions' => $conversions,
                                    'total' => $total_conversions,
                                    'pending' => $pending_conversions ) );
                                break;

                            case 'inventory_history':
                                $start_time = param( $this->input->get(), 'start', date( TIMESTAMP_FORMAT, strtotime( 'now - 1 day' ) ) );
                                $end_time =  param( $this->input->get(), 'end', date( TIMESTAMP_FORMAT ) );

                                // TODO: Check if $start < $end

                                $data = $store->get_transactions_date_range( $start_time, $end_time );
                                $starting_balances = $store->get_inventory_balances( $start_time );

                                $data_array = array();

                                foreach( $starting_balances as $row )
                                {
                                    $data_array[$row['item_id']] = array(
                                        'data' => array(),
                                        'init_balance' => is_null( $row['balance'] ) ? 0 : $row['balance'],
                                        'name' => $row['item_name'],
                                        'id' => $row['item_id'] );
                                }

                                foreach( $data as $row )
                                {
                                    if( isset( $data_array[$row['item_id']] ) )
                                    {
                                        $data_array[$row['item_id']]['data'][$row['timestamp']] = $row['balance'];
                                    }
                                    else
                                    {
                                        $data_array[$row['item_id']] = array(
                                            'data' => array( $row['timestamp'] => $row['balance'] ),
                                            'init_balance' => $row['balance'] - $row['quantity'],
                                            'name' => $row['item_name'],
                                            'id' => $row['item_id'] );
                                    }
                                }

                                $start_time = round( strtotime( $start_time ) / 60 ) * 60 ;
                                $end_time = round( strtotime( $end_time ) / 60 ) * 60;

                                $this->_response( array(
                                    'series' => array_values( $data_array ),
                                    'start_time' => $start_time ,
                                    'end_time' => $end_time ) );
                                break;

                            case 'items': // inventory items
                                $items = $store->get_items();
                                $items_data = array();
                                $additional_fields = array(
                                    'item_name' => array( 'type' => 'string' ),
                                    'item_group' => array( 'type' => 'string' ),
                                    'item_description' => array( 'type' => 'string' ),
                                    'teller_allocatable' => array( 'type' => 'boolean' ),
                                    'teller_remittable' => array( 'type' => 'boolean' ),
                                    'machine_allocatable' => array( 'type' => 'boolean' ),
                                    'machine_remittable' => array( 'type' => 'boolean' )
                                );
                                foreach( $items as $item )
                                {
                                    $items_data[] = $item->as_array( $additional_fields );
                                }

                                $this->_response( $items_data );
                                break;

                            case 'receipts': // receipts
                                $includes = param( $this->input->get(), 'include' );
                                $includes = explode( ',', $includes );
                                $params = array(
                                        'date' => param( $this->input->get(), 'date' ),
                                        'src' => param( $this->input->get(), 'src' ),
                                        'status' => param( $this->input->get(), 'status' ),
                                        'page' => param( $this->input->get(), 'page' ),
                                        'limit' => param( $this->input->get(), 'limit' ),
                                        'includes' => $includes
                                    );
                                $receipts = $store->get_receipts( $params );
                                $total_receipts = $store->count_receipts( $params );
                                $pending_receipts = $store->count_pending_receipts( $params );

                                $receipts_data = array();
                                $array_params = array();

                                if( $params['includes'] && in_array( 'validation', $params['includes'] ) )
                                {
                                    $array_params = array(
                                        'transval_receipt_status' => array( 'type' => 'integer' ),
                                        'transval_receipt_datetime' => array( 'type' => 'datetime' ),
                                        'transval_receipt_sweeper' => array( 'type' => 'string' ),
                                        'transval_receipt_user_id' => array( 'type' => 'integer' ),
                                        'transval_receipt_shift_id' => array( 'type' => 'integer' ),
                                        'transval_transfer_status' => array( 'type' => 'integer' ),
                                        'transval_transfer_datetime' => array( 'type' => 'datetime' ),
                                        'transval_transfer_sweeper' => array( 'type' => 'string' ),
                                        'transval_transfer_user_id' => array( 'type' => 'integer' ),
                                        'transval_transfer_shift_id' => array( 'type' => 'integer' ) );
                                }

                                foreach( $receipts as $receipt )
                                {
                                    $items = $receipt->get_items( FALSE );
                                    $r = $receipt->as_array( $array_params );
                                    foreach( $items as $item )
                                    {
                                        $r['items'][] = $item->as_array( array(
                                            'item_name' => array( 'type' => 'string' ),
                                            'item_description' => array( 'type' => 'stirng' ) ) );
                                    }
                                    $receipts_data[] = $r;
                                }

                                $this->_response( array(
                                    'receipts' => $receipts_data,
                                    'total' => $total_receipts,
                                    'pending' => $pending_receipts
                                ) );
                                break;

                            case 'shifts': // store shifts
                                $shifts = $store->get_shifts();
                                $shifts_data = array();
                                foreach( $shifts as $shift )
                                {
                                    $shifts_data[] = $shift->as_array();
                                }

                                $this->_response( $shifts_data );
                                break;

                            case 'transfers': // transfers
                                $transfer_id = param_type( $this->uri->rsegment( 5 ), 'integer' );
                                $includes = param( $this->input->get(), 'include' );
                                $includes = explode( ',', $includes );
                                if( $transfer_id )
                                {
                                    $this->load->library( 'transfer' );
                                    $Transfer = new Transfer();
                                    $transfer = $Transfer->get_by_id( $transfer_id );
                                    if( $transfer )
                                    {
                                        $transfer_data = $transfer->as_array();

                                        $transfer_items = $transfer->get_items();
                                        $transfer_items_data = array();


                                        foreach( $transfer_items as $item )
                                        {
                                            $transfer_items_data[] = $item->as_array( array(
                                                'item_name' => array( 'type' => 'string' ),
                                                'item_description' => array( 'type' => 'string' ),
                                                'category_name' => array( 'type' => 'string' ),
                                                'is_transfer_category' => array( 'type' => 'boolean' ) ) );
                                        }
                                        $transfer_data['items'] = $transfer_items_data;

                                        $this->_response( $transfer_data );
                                    }
                                    else
                                    {
                                        $this->_error( 404, 'Transfer record not found' );
                                    }
                                }
                                else
                                {
                                    $params = array(
                                        'date' => param( $this->input->get(), 'date' ),
                                        'dst' => param( $this->input->get(), 'dst' ),
                                        'status' => param( $this->input->get(), 'status' ),
                                        'page' => param( $this->input->get(), 'page' ),
                                        'limit' => param( $this->input->get(), 'limit' ),
                                        'includes' => $includes
                                    );
                                    $transfers = $store->get_transfers( $params );
                                    $total_transfers = $store->count_transfers( $params );
                                    $pending_transfers = $store->count_pending_transfers( $params );

                                    $transfers_data = array();
                                    $array_params = array();

                                    if( $params['includes'] && in_array( 'validation', $params['includes'] ) )
                                    {
                                        $array_params = array(
                                            'transval_id' => array( 'type' => 'integer' ),
                                            'transval_receipt_status' => array( 'type' => 'integer' ),
                                            'transval_receipt_datetime' => array( 'type' => 'datetime' ),
                                            'transval_receipt_sweeper' => array( 'type' => 'string' ),
                                            'transval_receipt_user_id' => array( 'type' => 'integer' ),
                                            'transval_receipt_shift_id' => array( 'type' => 'integer' ),
                                            'transval_transfer_status' => array( 'type' => 'integer' ),
                                            'transval_transfer_datetime' => array( 'type' => 'datetime' ),
                                            'transval_transfer_sweeper' => array( 'type' => 'string' ),
                                            'transval_transfer_user_id' => array( 'type' => 'integer' ),
                                            'transval_transfer_shift_id' => array( 'type' => 'integer' ),
                                            'transval_status' => array( 'type' => 'integer' ) );
                                    }

                                    foreach( $transfers as $transfer )
                                    {
                                        $items = $transfer->get_items( FALSE );
                                        $r = $transfer->as_array( $array_params );
                                        foreach( $items as $item )
                                        {
                                            $r['items'][] = $item->as_array( array(
                                                'item_name' => array( 'type' => 'string' ),
                                                'item_description' => array( 'type' => 'stirng' ) ) );
                                        }
                                        $transfers_data[] = $r;
                                    }

                                    $this->_response( array(
                                        'transfers' => $transfers_data,
                                        'total' => $total_transfers,
                                        'pending' => $pending_transfers
                                    ) );
                                }
                                break;

                            case 'transactions': // transactions
                                $params = array(
                                    'item' => param( $this->input->get(), 'item' ),
                                    'type' => param( $this->input->get(), 'type' ),
                                    'date' => param( $this->input->get(), 'date' ),
                                    'page' => param( $this->input->get(), 'page' ),
                                    'limit' => param( $this->input->get(), 'limit' ),
                                    'order' => 'transaction_datetime DESC, id DESC'
                                );
                                $transactions = $store->get_transactions( $params );
                                $total_transactions = $store->count_transactions( $params );
                                $transactions_data = array();

                                $additional_fields = array(
                                    'item_name' => array( 'type' => 'string' ),
                                    'item_description' => array( 'type' => 'string' ),
                                    'shift_num' => array( 'type' => 'string' )
                                );

                                foreach( $transactions as $transaction )
                                {
                                    $transactions_data[] = $transaction->as_array( $additional_fields );
                                }

                                $this->_response( array(
                                    'transactions' => $transactions_data,
                                    'total' => $total_transactions ) );
                                break;

                            default:
                                $this->_error( 404, sprintf( '%s resource not found', $relation ) );
                        }
                    }
                    else
                    {
                        $this->_error( 404, 'Store record not found' );
                    }
                }
                else
                { // Get list of stores
                    $stores = $Store->get_stores();
                    $stores_data = array();
                    foreach( $stores as $store )
                    {
                        $stores_data[] = $store->as_array();
                    }

                    $this->_response( $stores_data );
                }

                break;

            case 'post': // creates
                if( $store_id )
                {
                    $store = $Store->get_by_id( $store_id );
                    if( $store )
                    {
                        switch( $relation )
                        {
                            case 'adjustments':
                                $this->load->library( 'adjustment' );
                                $Adjustment = new Adjustment();
                                $adjustment = $Adjustment->load_from_data( $this->input->post() );
                                $r = $adjustment->db_save();

                                $this->_response( $r->as_array(), 201 );
                                break;

                            default:
                                $this->_error( 404, sprintf( '%s resource not found', $relation ) );
                        }
                    }
                    else
                    {
                        $this->_error( 404, 'Store record not found' );
                    }
                }
                else
                {
                    $this->_error( 400, 'Missing required store ID' );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function transfers()
    {
        $request_method = $this->input->method();

        $this->load->library( 'transfer' );
        $Transfer = new Transfer();

        switch( $request_method )
        {
            case 'get':
                $transfer_id = param_type( $this->uri->rsegment( 3 ), 'integer' );
                $relation = param_type( $this->uri->rsegment( 4 ), 'string' );
                $includes = param( $this->input->get(), 'include' );
                $includes = explode( ',', $includes );
                if( $transfer_id )
                {
                    $transfer = $Transfer->get_by_id( $transfer_id );
                    if( $transfer )
                    {
                        switch( $relation )
                        {
                            case NULL:
                                $transfer_data = $transfer->as_array();
                                $items = $transfer->get_items();
                                $items_data = array();
                                foreach( $items as $item )
                                {
                                    $items_data[] = $item->as_array( array(
                                        'item_name' => array( 'type', 'string' ),
                                        'item_description' => array( 'type', 'string' ),
                                        'category_name' => array( 'type', 'string' ) ) );
                                }
                                $transfer_data['items'] = $items_data;
                                if( in_array( 'validation', $includes ) )
                                {
                                    $validation = $transfer->get_transfer_validation();
                                    if( $validation )
                                    {
                                        $transfer_data['validation'] = $validation->as_array();
                                    }
                                    else
                                    {
                                        $transfer_data['validation'] = NULL;
                                    }
                                }

                                $this->_response( $transfer_data );
                                break;

                            case 'items':
                                break;

                            default:
                                $this->_error( 404, sprintf( '%s resource not found', $relation ) );
                        }
                    }
                    else
                    {
                        $this->_error( 404, 'Transfer record not found' );
                    }
                }
                else
                {
                    $includes = param( $this->input->get(), 'include' );
                    $includes = explode( ',', $includes );
                    $params = array(
                        'includes' => $includes,
                        'sent' => param( $this->input->get(), 'sent' ),
                        'received' => param( $this->input->get(), 'received' ),
                        'src' => param( $this->input->get(), 'src' ),
                        'dst' => param( $this->input->get(), 'dst' ),
                        'status' => param( $this->input->get(), 'status' ),
                        'page' => param( $this->input->get(), 'page' ),
                        'limit' => param( $this->input->get(), 'limit' ),
                    );
                    $transfers = $Transfer->get_transfers( $params );
                    $total_transfers = $Transfer->count_transfers( $params );
                    //$pending_transfers = $Transfer->count_pending_transfers( $params );
                    $pending_transfers = 0;
                    $transfers_data = array();
                    $array_params = array();

                    if( $params['includes'] && in_array( 'validation', $params['includes'] ) )
                    {
                        $array_params = array(
                            'transval_id' => array( 'type' => 'integer' ),
                            'transval_receipt_status' => array( 'type' => 'integer' ),
                            'transval_receipt_datetime' => array( 'type' => 'datetime' ),
                            'transval_receipt_sweeper' => array( 'type' => 'string' ),
                            'transval_receipt_user_id' => array( 'type' => 'integer' ),
                            'transval_receipt_shift_id' => array( 'type' => 'integer' ),
                            'transval_transfer_status' => array( 'type' => 'integer' ),
                            'transval_transfer_datetime' => array( 'type' => 'datetime' ),
                            'transval_transfer_sweeper' => array( 'type' => 'string' ),
                            'transval_transfer_user_id' => array( 'type' => 'integer' ),
                            'transval_transfer_shift_id' => array( 'type' => 'integer' ),
                            'transval_status' => array( 'type' => 'integer' ) );
                    }
                    foreach( $transfers as $transfer )
                    {
                        $transfers_data[] = $transfer->as_array( $array_params );
                    }

                    $this->_response( array(
                        'transfers' => $transfers_data,
                        'total' => $total_transfers,
                        'pending' => $pending_transfers ) );
                }
                break;

            case 'post':
                $action = param_type( $this->uri->rsegment( 3 ), 'string' );
                $transfer_id = param( $this->input->post(), 'id' );
                $transfer = $Transfer->load_from_data( $this->input->post() );

                $this->db->trans_start();
                switch( $action )
                {
                    case 'approve':
                        $result = $transfer->approve();
                        break;

                    case 'cancel':
                        $result = $transfer->cancel();
                        break;

                    case 'receive':
                        $result = $transfer->receive();
                        break;

                    default:
                        $result = $transfer->db_save();
                }

                if( $result )
                {
                    $transfer_items = $transfer->get_items();
                    $transfer_data = $transfer->as_array();
                    foreach( $transfer_items as $item )
                    {
                        $transfer_data['items'][$item->get( 'id' )] = $item->as_array();
                    }
                    $this->db->trans_complete();

                    if( $this->db->trans_status() )
                    {
                        $this->_response( $transfer_data, $transfer_id ? 200 : 201 );
                    }
                    else
                    {
                        $this->_error( 500, 'A database error has occurred while trying to save transfer record' );
                    }
                }
                else
                {
                    $messages = get_messages();
                    $this->_error( 200, $messages );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }

    public function transfer_validations()
    {
        $request_method = $this->input->method();

        $this->load->library( 'transfer_validation' );
        $Validation = new Transfer_validation();

        switch( $request_method )
        {
            case 'post':
                $action = param_type( $this->uri->rsegment( 3 ), 'string' );
                $validation_id = param( $this->input->post(), 'id' );
                $validation = $Validation->load_from_data( $this->input->post() );

                $this->db->trans_start();
                switch( $action )
                {
                    case 'validate_receipt':
                        $result = $validation->validate_receipt();
                        break;

                    case 'returned':
                        $result = $validation->return_transfer();
                        break;

                    case 'validate_transfer':
                        $result = $validation->validate_transfer();
                        break;

                    case 'dispute':
                        $result = $validation->dispute();
                        break;

                    case 'complete':
                        $result = $validation->complete();
                        break;

                    case 'ongoing':
                        $result = $validation->ongoing();
                        break;

                    case 'not_required':
                        $result = $validation->not_required();
                        break;

                    default:
                        //$result = $validation->db_save();
                        die( 'error' );
                }

                if( $result )
                {
                    $validation_data = $validation->as_array();
                    $this->db->trans_complete();

                    if( $this->db->trans_status() )
                    {
                        $this->_response( $validation_data, $validation_id ? 200 : 201 );
                    }
                    else
                    {
                        $this->_error( 500, 'A database error has occurred while trying to save transfer record' );
                    }
                }
                else
                {
                    $messages = get_messages();
                    $this->_error( 200, $messages );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );

        }

        $this->_send_response();
    }

    public function users()
    {
        $request_method = $this->input->method();
        $action = $this->uri->rsegment( 3 );

        $this->load->library( 'user' );
        $User = new User();

        switch( $request_method )
        {
            case 'get':
                $includes = explode( ',', param( $this->input->get(), 'include' ) );

                switch( $action )
                {
                    case 'search':
                        $q = param_type( $this->input->get( 'q' ), 'string' );
                        $users = $User->search( $q );
                        $users_data = array();
                        foreach( $users as $user )
                        {
                            $users_data[] = $user->as_array();
                        }

                        $this->_response( $users_data );
                        break;

                    default:
                        $user_id = param_type( $this->uri->rsegment( 3 ), 'integer' );
                        $relation = param_type( $this->uri->rsegment( 4 ), 'string' );

                        if( $user_id )
                        { // get specific user
                            $user = $User->get_by_id( $user_id );
                            if( $user )
                            {
                                switch( $relation )
                                {
                                    case 'stores':
                                        $user_stores = $user->get_stores();
                                        $user_stores_array = array();
                                        foreach( $user_stores as $store )
                                        {
                                            $user_stores_array[] = $store->as_array();
                                        }

                                        $this->_response( $user_stores_array );
                                        break;

                                    case 'permissions':
                                        $user_group = $user->get_group();
                                        $user_permissions = $user_group->get_permissions();

                                        $this->_response( $user_permissions );
                                        break;

                                    case NULL:
                                        $user_data = $user->as_array();

                                        if( in_array( 'stores', $includes ) )
                                        {
                                            $user_stores = $user->get_stores();
                                            $user_stores_array = array();
                                            foreach( $user_stores as $store )
                                            {
                                                $user_stores_array[] = $store->as_array();
                                            }
                                            $user_data['stores'] = $user_stores_array;
                                        }

                                        $this->_response( $user_data );
                                        break;

                                    default:
                                        $this->_error( 404, sprintf( '%s resource not found', $relation ) );
                                }
                            }
                            else
                            {
                                $this->_error( 404, 'User record not found' );
                            }
                        }
                        else
                        { // get list of users
                            $params = array(
                                'q' => param( $this->input->get(), 'q' ),
                                'role' => param( $this->input->get(), 'role' ),
                                'group' => param( $this->input->get(), 'group' ),
                                'status' => param( $this->input->get(), 'status' ),
                                'limit' => param( $this->input->get(), 'limit'),
                                'page' => param( $this->input->get(), 'page' ),
                                'format' => param( $this->input->get(), 'format' ),
                                'order' => param( $this->input->get(), 'order' ) );

                            $users = $User->get_users( $params );
                            $total_users = $User->count_users( $params );
                            $users_data = array();
                            foreach( $users as $user )
                            {
                                $users_data[] = $user->as_array( array(
                                    'group_name' => array( 'type' => 'string' ) ) );
                            }

                            $this->_response( array(
                                'users' => $users_data,
                                'total' => $total_users ) );
                        }
                }
                break;

            case 'post':
                $action = param_type( $this->uri->rsegment( 3 ), 'string' );
                $password = param( $this->input->post(), 'password' );
                $user_id = param( $this->input->post(), 'id' );

                if( $user_id && $password )
                {
                    // old password must be specified
                    $old_password = param( $this->input->post(), 'old_password' );
                    if( $old_password )
                    {
                        if( $user_id == current_user( TRUE ) )
                        {
                            $user = $User->get_by_id( $user_id );
                            if( ! $user->validate_password( $old_password ) )
                            {
                                $this->_error( 403, 'Username or password is invalid' );
                                break;
                            }
                        }
                        elseif( is_admin() ) // TODO: additional check if admin has privilege to edit users
                        {
                            $admin_user = current_user();
                            if( ! $admin_user->validate_password( $old_password ) )
                            {
                                $this->_error( 403, 'Username or password is invalid' );
                                break;
                            }
                        }
                    }
                    else
                    {
                        $this->_error( 403, 'Missing required previous password' );
                        break;
                    }
                }

                $user = $User->load_from_data( $this->input->post() );
                $stores = param( $this->input->post(), 'stores' );
                $assign_stores = param_type( param( $this->input->post(), 'assign_stores' ), 'boolean' );

                $this->db->trans_start();
                switch( $action )
                {
                    case 'lock':
                        $result = $user->lock();
                        break;

                    case 'disable':
                        $result = $user->disable();
                        break;

                    default:
                        $result = $user->db_save();
                }
                if( $result )
                {
                    if( $assign_stores )
                    {
                        if( $stores )
                        {
                            $result = $user->assign_store( $stores );
                        }
                        else
                        {
                            $result = $user->clear_stores();
                        }
                    }

                    $user_data = $user->as_array();
                    $this->db->trans_complete();

                    if( $result )
                    {
                        if( $this->db->trans_status() )
                        {
                            $this->_response( $user_data, $user_id ? 200 : 201 );
                        }
                        else
                        {
                            $this->_error( 500, 'A database error has occurred while trying to save user record' );
                        }
                    }
                    else
                    {
                        $messages = get_message();
                        $this->_error( 200, $messages );
                    }
                }
                else
                {
                    $messages = get_messages();
                    $this->_error( 200, $messages );
                }
                break;

            default:
                $this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
        }

        $this->_send_response();
    }
}