<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// TODO: Extend MY_Controller instead after fixing session checking
class Api_v1 extends MY_Controller {

	public $response = NULL;

	public function __construct()
	{
		parent::__construct( array( 'login_info' ) );
	}

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

	public function adjustments()
	{
		$request_method = $this->input->method();
		$current_user = current_user();

		$this->load->library( 'adjustment' );
		$Adjustment = new Adjustment();

		switch( $request_method )
		{
			case 'get':
				// Check permissions
				if( !$current_user->check_permissions( 'adjustments', 'view' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource.' );
				}
				else
				{
					$adjustment_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

					if( $adjustment_id )
					{
						$adjustment = $Adjustment->get_by_id( $adjustment_id );
						if( $adjustment )
						{
							$adjustment_store_id = $adjustment->get_inventory()->get( 'store_id' );
							if( $adjustment_store_id != current_store( TRUE )
								|| ! is_store_member( $adjustment_store_id, current_user( TRUE ) ) )
							{
								$this->_error( 403, 'You are not allowed to access this resource.
										The resource you are trying to access belongs to another store or you are not a member of the owner store.' );
							}
							else
							{
								$adjustment_data = $adjustment->as_array( array(
									'item_name' => array( 'type' => 'string' ),
									'item_description' => array( 'type' => 'string' ),
									'full_name' => array( 'type' => 'string' ) ) );

								$this->_response( $adjustment_data );
							}
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
				}
				break;

			case 'post':
				$action = param_type( $this->uri->rsegment( 3 ), 'string' );
				$adjustment_id = param( $this->input->post(), 'id' );
				$adjustment = $Adjustment->load_from_data( $this->input->post() );
				$current_user = current_user();

				$this->db->trans_start();
				$result = FALSE;
				switch( $action )
				{
					case 'approve':
						if( $current_user->check_permissions( 'adjustments', 'approve' ) )
						{
							$result = $adjustment->approve();
						}
						else
						{
							set_message( 'You do not have the necessary permission to approve adjustments', 'error', 403 );
						}
						break;

					case 'cancel':
						if( $current_user->check_permissions( 'adjustments', 'approve' ) )
						{
							$result = $adjustment->cancel();
						}
						else
						{
							set_message( 'You do not have the necessary permission to cancel adjustments', 'error', 403 );
						}
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
		$current_user = current_user();

		$this->load->library( 'allocation' );
		$Allocation = new Allocation();

		switch( $request_method )
		{
			case 'get':
				// Check permissions
				if( !$current_user->check_permissions( 'allocations', 'view' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
					$allocation_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

					if( $allocation_id )
					{
						$allocation = $Allocation->get_by_id( $allocation_id );
						if( $allocation )
						{
							if( $allocation->get( 'store_id' ) != current_store( TRUE )
								|| ! is_store_member( $allocation->get( 'store_id' ), current_user( TRUE ) ) )
							{
								$this->_error( 403, 'You are not allowed to access this resource.
										The resource you are trying to access belongs to another store or you are not a member of the owner store.' );
							}
							else
							{
								$allocation_data = $allocation->as_array();

								$allocation_items = $allocation->get_allocations();
								$cash_allocation_items = $allocation->get_cash_allocations();
								$remittance_items = $allocation->get_remittances();
								$cash_remittance_items = $allocation->get_cash_remittances();
								$ticket_sale_items = $allocation->get_ticket_sales();
								$sales_items = $allocation->get_sales();
								$cash_reports = $allocation->get_cash_reports();

								$allocation_items_data = array();
								$cash_allocation_items_data = array();
								$remittance_items_data = array();
								$cash_remittance_items_data = array();
								$ticket_sale_items_data = array();
								$sales_items_data = array();
								$cash_reports_data = array();

								// Allocation items
								foreach( $allocation_items as $item )
								{
									$allocation_items_data[] = $item->as_array( array(
										'cat_description' => array( 'type' => 'string' ),
										'cat_module' => array( 'type' => 'string' ),
										'item_name' => array( 'type' => 'string' ),
										'item_description' => array( 'type' => 'string' ),
										'item_class' => array( 'type' => 'string' ),
										'teller_allocatable' => array( 'type' => 'boolean' ),
										'machine_allocatable' => array( 'type' => 'boolean' ),
										'cashier_shift_num' => array( 'type' => 'string' ),
										'base_quantity' => array( 'type' => 'integer' ) ) );
								}
								$allocation_data['allocations'] = $allocation_items_data;

								// Cash allocation items
								foreach( $cash_allocation_items as $item )
								{
									$cash_allocation_items_data[] = $item->as_array( array(
										'cat_description' => array( 'type' => 'string' ),
										'cat_module' => array( 'type' => 'string' ),
										'item_name' => array( 'type' => 'string' ),
										'item_description' => array( 'type' => 'string' ),
										'item_class' => array( 'type' => 'string' ),
										'iprice_currency' => array( 'type' => 'string' ),
										'iprice_unit_price' => array( 'type' => 'decimal' ),
										'teller_allocatable' => array( 'type' => 'boolean' ),
										'machine_allocatable' => array( 'type' => 'boolean' ),
										'cashier_shift_num' => array( 'type' => 'string' ) ) );
								}
								$allocation_data['cash_allocations'] = $cash_allocation_items_data;

								// Remittance items
								foreach( $remittance_items as $item )
								{
									$remittance_items_data[] = $item->as_array( array(
										'cat_description' => array( 'type' => 'string' ),
										'cat_module' => array( 'type' => 'string' ),
										'item_name' => array( 'type' => 'string' ),
										'item_description' => array( 'type' => 'string' ),
										'item_class' => array( 'type' => 'string' ),
										'teller_remittable' => array( 'type' => 'boolean' ),
										'machine_remittable' => array( 'type' => 'boolean' ),
										'cashier_shift_num' => array( 'type' => 'string' ) ) );
								}
								$allocation_data['remittances'] = $remittance_items_data;

								// Cash remittance items
								foreach( $cash_remittance_items as $item )
								{
									$cash_remittance_items_data[] = $item->as_array( array(
										'cat_description' => array( 'type' => 'string' ),
										'cat_module' => array( 'type' => 'string' ),
										'item_name' => array( 'type' => 'string' ),
										'item_description' => array( 'type' => 'string' ),
										'item_class' => array( 'type' => 'string' ),
										'iprice_currency' => array( 'type' => 'string' ),
										'iprice_unit_price' => array( 'type' => 'decimal' ),
										'teller_remittable' => array( 'type' => 'boolean' ),
										'machine_remittable' => array( 'type' => 'boolean' ),
										'cashier_shift_num' => array( 'type' => 'string' ) ) );
								}
								$allocation_data['cash_remittances'] = $cash_remittance_items_data;

								// Ticket sale items
								foreach( $ticket_sale_items as $item )
								{
									$ticket_sale_items_data[] = $item->as_array( array(
										'cat_description' => array( 'type' => 'string' ),
										'cat_module' => array( 'type' => 'string' ),
										'item_name' => array( 'type' => 'string' ),
										'item_description' => array( 'type' => 'string' ),
										'item_class' => array( 'type' => 'string' ),
										'teller_saleable' => array( 'type' => 'boolean' ),
										'machine_saleable' => array( 'type' => 'boolean' ),
										'cashier_shift_num' => array( 'type' => 'string' ) ) );
								}
								$allocation_data['ticket_sales'] = $ticket_sale_items_data;

								// Sales items
								foreach( $sales_items as $item )
								{
									$sales_items_data[] = $item->as_array( array(
										'slitem_name' => array( 'type' => 'string' ),
										'slitem_description' => array( 'type' => 'string' ),
										'slitem_group' => array( 'type' => 'string' ),
										'slitem_mode' => array( 'type' => 'integer'),
										'cashier_shift_num' => array( 'type', 'string' ) ) );
								}
								$allocation_data['sales'] = $sales_items_data;

								// Cash reports
								foreach( $cash_reports as $report )
								{
									$cash_reports_data[] = $report->as_array();
								}
								$allocation_data['cash_reports'] = $cash_reports_data;

								$this->_response( $allocation_data );
							}
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
				}
				break;

			case 'post':
				$action = param_type( $this->uri->rsegment( 3 ), 'string' );
				$allocation_id = param( $this->input->post(), 'id' );
				$allocation = $Allocation->load_from_data( $this->input->post() );
				$current_user = current_user();
				$result = FALSE;

				$this->db->trans_start();
				switch( $action )
				{
					case 'allocate':
						if( $current_user->check_permissions( 'allocations', 'allocate' ) )
						{
							$result = $allocation->allocate();
						}
						else
						{
							set_message( 'You do not have the necessary permission to allocate', 'error', 403 );
						}
						break;

					case 'remit':
						if( $current_user->check_permissions( 'allocations', 'complete' ) )
						{
							$result = $allocation->remit();
						}
						else
						{
							set_message( 'You do not have the necessary permission to mark allocations as completed', 'error', 403 );
						}
						break;

					case 'cancel':
						if( $current_user->check_permissions( 'allocations', 'allocate' ) )
						{
							$result = $allocation->cancel();
						}
						else
						{
							set_message( 'You do not have the necessary permission to cancel allocations', 'error', 403 );
						}
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

		$this->load->library( 'category' );
		$Category = new Category();

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
		$current_user = current_user();

		$this->load->library( 'mopping' );
		$Collection = new Mopping();

		switch( $request_method )
		{
			case 'get':
				// Check permissions
				if( !$current_user->check_permissions( 'collections', 'view' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
					$collection_id = param_type( $this->uri->rsegment( 3 ), 'integer' );
					$relation = param_type( $this->uri->rsegment( 4 ), 'string' );

					if( $collection_id )
					{
						$collection = $Collection->get_by_id( $collection_id );
						if( $collection )
						{
							if( $collection->get( 'store_id') != current_store( TRUE )
								|| ! is_store_member( $collection->get( 'store_id' ), current_user( TRUE ) ) )
							{
								$this->_error( 403, 'You are not allowed to access this resource.
										The resource you are trying to access belongs to another store or you are not a member of the owner store.' );
							}
							else
							{
								$collection_data = $collection->as_array();
								$collection_items = $collection->get_items();
								$collection_items_data = array();
								foreach( $collection_items as $item )
								{
									$collection_items_data[] = $item->as_array( array(
										'station_name' => array( 'type' => 'string' ),
										'mopped_item_name' => array( 'type' => 'string' ),
										'converted_to_name' => array( 'type' => 'string' ),
										'mopped_station_name' => array( 'type' => 'string' ),
										'processor_name' => array( 'type' => 'string' ) ) );
								}
								$collection_data['items'] = $collection_items_data;
								$this->_response( $collection_data );
							}
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
						$this->_error( 500, 'A database error has occurred while trying to save collection record' );
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
		$current_user = current_user();

		$this->load->library( 'conversion' );
		$Conversion = new Conversion();

		switch( $request_method )
		{
			case 'get':
				// Check permissions
				if( !$current_user->check_permissions( 'conversions', 'view' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
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
									if( $conversion->get( 'store_id' ) != current_store( TRUE )
										|| ! is_store_member( $conversion->get( 'store_id' ), current_user( TRUE ) ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource.
												The resource you are trying to access belongs to another store or you are not a member of the owner store.' );
									}
									else
									{
										$this->_response( $conversion->as_array() );
									}
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
				}
				break;

			case 'post':
				$action = param_type( $this->uri->rsegment( 3 ), 'string' );
				$conversion_id = param( $this->input->post(), 'id' );
				$conversion = $Conversion->load_from_data( $this->input->post() );
				$current_user = current_user();
				$result = FALSE;

				$this->db->trans_start();
				switch( $action )
				{
					case 'approve':
						if( $current_user->check_permissions( 'conversions', 'approve' ) )
						{
							$result = $conversion->approve();
						}
						else
						{
							set_message( 'You do not have the necessary permission to approve conversions', 'error', 403 );
						}
						break;

					case 'cancel':
						if( $current_user->check_permissions( 'conversions', 'approve' ) )
						{
							$result = $conversion->cancel();
						}
						else
						{
							set_message( 'You do not have the necessary permission to cancel conversions', 'error', 403 );
						}
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
							// TODO: Add transfer_datetime in the WHERE clause of the subquery, probably limit to transfers within a month or week
							$sql = "SELECT s.store_name, s.store_code, i.item_name, si.quantity, COALESCE( it.in_transit_quantity, 0 ) AS in_transit_quantity
											FROM store_inventory si
											LEFT JOIN stores s
												ON s.id = si.store_id
											LEFT JOIN items i
												ON i.id = si.item_id
											LEFT JOIN (
												SELECT t.destination_id, ti.item_id, SUM( ti.quantity ) AS in_transit_quantity
												FROM transfers t
												LEFT JOIN transfer_items ti
													ON ti.transfer_id = t.id
												WHERE
													t.transfer_status = 2
												GROUP BY t.destination_id, ti.item_id
											) AS it
												ON it.destination_id = si.store_id AND it.item_id = si.item_id
											ORDER BY si.store_id ASC, si.item_id ASC";

							$data = $this->db->query( $sql );
							$data = $data->result_array();

							$stores = array();
							$series = array();

							foreach( $data as $row )
							{
								$index = array_search( $row['store_code'], $stores );
								if( $index === FALSE )
								{
									$stores[] = $row['store_code'];
								}

								$series[$row['item_name'].'_1']['item'] = $row['item_name'];
								$series[$row['item_name'].'_1']['stack'] = $row['item_name'];
								$series[$row['item_name'].'_1']['data'][] = (int) $row['quantity'];
								$series[$row['item_name'].'_1']['in_transit'] = 0;

								$series[$row['item_name'].'_2']['item'] = $row['item_name'].' (transit)';
								$series[$row['item_name'].'_2']['stack'] = $row['item_name'];
								$series[$row['item_name'].'_2']['data'][] = (int) $row['in_transit_quantity'];
								$series[$row['item_name'].'_2']['in_transit'] = 1;
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
		$this->load->library( 'shift_turnover' );

		$current_store = new Store();
		$current_user = new User();
		$current_shift = new Shift();
		$shift_balance = NULL;

		$current_store = $current_store->get_by_id( $this->session->current_store_id );
		$current_user = $current_user->get_by_id( $this->session->current_user_id );
		$current_shift = $current_shift->get_by_id( $this->session->current_shift_id );
		if( $current_store )
		{
			$shift_balance = $current_store->get_shift_balance( date( DATE_FORMAT ), $current_shift->get( 'id' ) );
		}

		$user_data = NULL;
		$store_data = NULL;
		$stores_data = NULL;
		$shift_data = NULL;
		$shifts_data = NULL;
		$shift_balance_data = NULL;
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

		if( $shift_balance )
		{
			$shift_balance_data = $shift_balance->as_array();
		}

		$response = array(
				'status' => 'ok',
				'data' => array(
					'user' => $user_data,
					'store' => $store_data,
					'stores' => $stores_data,
					'shift' => $shift_data,
					'shifts' => $shifts_data,
					'shift_balance' => $shift_balance_data,
					'is_admin' => is_admin(),
					'permissions' => $permissions_data
				)
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

	public function sales_items()
	{
		$request_method = $this->input->method();

		switch( $request_method )
		{
			case 'get':
				$this->load->library( 'sales_item' );
				$SalesItem = new Sales_item();
				$items = $SalesItem->get_sale_items();
				$items_data = array();
				$additional_fields = array(
					'slitem_name' => array( 'type' => 'string' ),
					'slitem_description' => array( 'type' => 'string' ),
					'slitem_group' => array( 'type' => 'string' ),
					'slitem_mode' => array( 'type' => 'integer' )
				);

				foreach( $items as $item )
				{
					$item_data = $item->as_array( $additional_fields );
					$items_data[] = $item_data;
				}

				$this->_response( $items_data );
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
									$shift_balance = $current_store->get_shift_balance( date( DATE_FORMAT ), $this->session->current_shift_id );
									$this->_response( array(
										'shift' => $new_shift->as_array(),
										'shift_balance' => $shift_balance ? $shift_balance->as_array() : NULL ) );
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
						$previous_store_id = current_store( TRUE );

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
									$shift_balance = $new_store->get_shift_balance( date( DATE_FORMAT ), $this->session->current_shift_id );

									$this->_response( array(
										'store' => $new_store->as_array(),
										'shifts' => $shifts_data,
										'shift_balance' => $shift_balance ? $shift_balance->as_array() : NULL,
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

	public function shift_detail_cash_report()
	{
		$request_method = $this->input->method();
		$current_user = current_user();

		$this->load->library( 'shift_detail_cash_report' );
		$Shift_Detail_Cash_Report = new Shift_detail_cash_report();

		switch( $request_method )
		{
			case 'get':
				// Check permissions
				if( ! $current_user->check_permissions( 'allocations', 'view' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
					$shift_detail_cash_report_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

					if( $shift_detail_cash_report_id )
					{
						$shift_detail_cash_report = $Shift_Detail_Cash_Report->get_by_id( $shift_detail_cash_report_id );
						if( $shift_detail_cash_report )
						{
							if( $shift_detail_cash_report->get( 'sdcr_store_id') != current_store( TRUE )
								|| ! is_store_member( $shift_detail_cash_report->get( 'sdcr_store_id' ), current_user( TRUE ) ) )
							{
								$this->_error( 403, 'You are not allowed to access this resource.
										The resource you are trying to access belongs to another store or you are not a member of the owner store.' );
							}
							else
							{
								$shift_detail_cash_report_data = $shift_detail_cash_report->as_array();
								$shift_detail_cash_report_items = $shift_detail_cash_report->get_items();
								$shift_detail_cash_report_items_data = array();
								foreach( $shift_detail_cash_report_items as $item )
								{
									$shift_detail_cash_report_items_data[] = $item->as_array();
								}
								$shift_detail_cash_report_data['report_items'] = $shift_detail_cash_report_items_data;

								$this->_response( $shift_detail_cash_report_data );
							}
						}
						else
						{
							$this->_error( 404, 'Shift detail cash report record not found' );
						}
					}
					else
					{
						$this->_error( 400, 'Missing required Shift detail cash report ID' );
					}
				}
				break;

			case 'post':
				$action = param_type( $this->uri->rsegment( 3 ), 'string' );
				$shift_detail_cash_report_id = param( $this->input->post(), 'id' );
				$shift_detail_cash_report = $Shift_Detail_Cash_Report->load_from_data( $this->input->post() );
				$this->db->trans_start();
				switch( $action )
				{
					default:
						$result = $shift_detail_cash_report->db_save();
				}
				if( $result )
				{
					$this->db->trans_complete();

					if( $this->db->trans_status() )
					{
						$this->_response( $shift_detail_cash_report->as_array(), $shift_detail_cash_report_id ? 200 : 201 );
					}
					else
					{
						$this->_error( 500, 'A database error has occurred while trying to save TVM reading record' );
					}
				}
				else
				{
					$messages = get_messages();
					$this->_error( 200, $messages );
				}
				break;

			case 'delete':
				if( !$current_user->check_permissions( 'allocations', 'edit' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
					$shift_detail_cash_report_id = param_type( $this->uri->rsegment( 3 ), 'string' );
					$shift_detail_cash_report = $Shift_Detail_Cash_Report->get_by_id( $shift_detail_cash_report_id );

					if( $shift_detail_cash_report )
					{
						$shift_detail_cash_report->db_remove();
						$this->_response( array( 'id' => $shift_detail_cash_report_id ) );
					}
					else
					{
						$this->_error( 404, 'Shift detail cash report record not found' );
					}
				}
				break;

			default:
				$this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
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
					$shift = $Shift->get_by_id( $shift_id );
					$this->_response( $shift->as_array() );
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

	public function shift_turnovers()
	{
		$request_method = $this->input->method();
		$current_user = current_user();
		$current_store = current_store();

		$this->load->library( 'shift_turnover' );
		$Turnover = new Shift_turnover();

		switch( $request_method )
		{
			case 'get':
				// Check permissions
				if( !$current_user->check_permissions( 'shift_turnovers', 'view' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
					$action = $this->uri->rsegment( 3);

					switch( $action )
					{
						case 'date_shift':
							$store = param_type( $this->input->get( 'store' ), 'integer' );
							$date = param_type( $this->input->get( 'date' ), 'string' );
							$shift = param_type( $this->input->get( 'shift' ), 'integer' );

							if( !$current_user->check_permissions( 'shift_turnovers', 'view' ) )
							{
								$this->_error( 403, 'You are not allowed to access this resource' );
							}
							else
							{
								$turnover = $Turnover->get_by_store_date_shift( $store, $date, $shift );
								if( $turnover )
								{
									$turnover->load_turnover_items();
									$turnover_data = $turnover->as_array();
									$items = $turnover->get_items();
									$items_data = array();
									foreach( $items as $key => $item )
									{
										$items_data[] = $item->as_array(
											array(
												'item_name' => array( 'type' => 'string' ),
												'item_description' => array( 'type' => 'string' ),
												'item_group' => array( 'type' => 'string' ),
												'item_unit' => array( 'type' => 'string' ),
												'previous_balance' => array( 'type' => 'integer' ),
												'movement' => array( 'type' => 'integer' )
											)
										);
									}
									$turnover_data['items'] = $items_data;
									$this->_response( $turnover_data );
								}
								else
								{
									$this->load->library( 'shift' );
									$Shift = new Shift();

									$current_shift = $Shift->get_by_id( $shift );
									$current_shift_order = $current_shift->get( 'shift_order' );

									$turnover = new Shift_turnover();
									$turnover->set( 'st_store_id', $store );
									$turnover->set( 'st_from_date', $date );
									$turnover->set( 'st_from_shift_id', $shift );

									$next_shift_id = $current_shift->get( 'shift_next_shift_id' );
									if( $next_shift_id )
									{
										$next_shift = $Shift->get_by_id( $next_shift_id );
										$next_shift_order = $next_shift->get( 'shift_order' );

										if( $current_shift_order < $next_shift_order )
										{

											$turnover->set( 'st_to_date', $date );
											$turnover->set( 'st_to_shift_id', $next_shift_id );
										}
										else
										{
											$turnover->set( 'st_to_date', date( DATE_FORMAT, strtotime( $date.' + 1 day' ) ) );
											$turnover->set( 'st_to_shift_id', $next_shift_id );
										}
									}
									else
									{
										$turnover->set( 'st_to_date', date( DATE_FORMAT, strtotime( $date.' + 1 day' ) ) );
										$turnover->set( 'st_to_shift_id', $shift );
									}

									$turnover->load_turnover_items();
									$items = $turnover->get_items();
									$items_data = array();
									if( $items )
									{
										foreach( $items as $key => $item )
										{
											$items_data[] = $item->as_array(
												array(
													'item_name' => array( 'type' => 'string' ),
													'item_description' => array( 'type' => 'string' ),
													'item_group' => array( 'type' => 'string' ),
													'item_unit' => array( 'type' => 'string' ),
													'parent_item_name' => array( 'type' => 'string' ),
													'previous_balance' => array( 'type' => 'integer' ),
													'movement' => array( 'type' => 'integer' )
												)
											);
										}
									}

									$turnover_data = $turnover->as_array();
									$turnover_data['items'] = $items_data;
									$this->_response( $turnover_data );
								}
							}
							break;

						default:
							$turnover_id = param_type( $this->uri->rsegment( 3 ), 'integer' );

							if( $turnover_id )
							{
								$turnover = $Turnover->get_by_id( $turnover_id );
								if( $turnover )
								{
									if( !$current_user->check_permissions( 'shift_turnover', 'view' )
										|| ( $current_store->get( 'id' ) !=  $turnover->get( 'store_id' ) ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
										$turnover_data = $turnover->as_array();
										$items = $turnover->get_items();
										$items_data = array();
										foreach( $items as $item )
										{
											$items_data[] = $item->as_array();
										}
										$turnover_data['items'] = $items_data;
										$this->_response( $turnover_data );
									}
								}
								else
								{
									$this->_error( 404, 'Transfer record not found' );
								}
							}
							else
							{
								$params = array(
									'shift' => param( $this->input->get(), 'shift' ),
									'date' => param( $this->input->get(), 'date' ),
									'page' => param( $this->input->get(), 'page' ),
									'limit' => param( $this->input->get(), 'limit' ),
								);
								$turnovers = $Turnover->get_shift_turnovers( $params );
								$total_turnovers = $Turnover->count_turnovers( $params );
								$pending_turnovers = $Turnover->count_pending_turnovers( $params );
								$turnovers_data = array();

								foreach( $turnovers as $turnover )
								{
									$turnovers_data[] = $turnover->as_array();
								}

								$this->_response( array(
									'shift_turnovers' => $turnovers_data,
									'total' => $total_turnovers,
									'pending' => $pending_turnovers ) );
							}
					}

				}
				break;

			case 'post':
				$action = param_type( $this->uri->rsegment( 3 ), 'string' );
				$turnover_id = param( $this->input->post(), 'id' );
				$turnover = $Turnover->load_from_data( $this->input->post() );
				$current_user = current_user();
				$result = FALSE;

				if( !$current_user->check_permissions( 'shift_turnovers', 'edit' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else{
					$this->db->trans_start();
					switch( $action )
					{
						case 'close':
							$result = $turnover->end_shift();
							break;

						default:
							$result = $turnover->db_save();
					}

					if( $result )
					{
						$turnover_items = $turnover->get_items();
						$turnover_data = $turnover->as_array();
						foreach( $turnover_items as $item )
						{
							$turnover_data['items'][$item->get( 'id' )] = $item->as_array();
						}
						$this->db->trans_complete();

						if( $this->db->trans_status() )
						{
							$this->_response( $turnover_data, $turnover_id ? 200 : 201 );
						}
						else
						{
							$this->_error( 500, 'A database error has occurred while trying to save shift turnover record' );
						}
					}
					else
					{
						$messages = get_messages();
						$this->_error( 200, $messages );
					}
				}
				break;

			default:
				$this->_error( 405, sprintf( '%s request not allowed', $request_method ) );
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
					$current_user = current_user();

					if( $store )
					{
						// Check permissions
						if( ! $store->is_member( $current_user->get( 'id' ) ) )
						{ // requester is not a member of the store
							$this->_error( 403, 'You are not allowed to access this resource. You are not a member of this store.' );
						}
						else
						{
							switch( $relation )
							{
								case NULL: // store data
									$this->_response( $store->as_array() );
									break;

								case 'adjustments': // adjustments
									// Check permissions
									if( !$current_user->check_permissions( 'adjustments', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
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
									}
									break;

								case 'allocations': // allocations
									// Check permissions
									if( !$current_user->check_permissions( 'allocations', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
										$params = array(
											'date' => param( $this->input->get(), 'date' ),
											'assignee_type' => param( $this->input->get(), 'assignee_type' ),
											'status' => param( $this->input->get(), 'status' ),
											'page' => param( $this->input->get(), 'page' ),
											'limit' => param( $this->input->get(), 'limit' ),
										);
										$allocations = $store->get_allocations( $params );
										$total_allocations = $store->count_allocations( $params );
										$pending_allocations = $store->count_pending_allocations( $params );

										$allocations_data = array();
										foreach( $allocations as $allocation )
										{
											$allocation_items = $allocation->get_allocations( TRUE );
											$allocation_cash_items = $allocation->get_cash_allocations( TRUE );
											$remittance_items = $allocation->get_remittances( TRUE );
											$remittance_cash_items = $allocation->get_cash_remittances( TRUE );
											$ticket_sale_items = $allocation->get_ticket_sales( TRUE );
											$sales_items = $allocation->get_sales( TRUE );
											$cash_reports = $allocation->get_cash_reports( TRUE );

											$allocation_items_data = array();
											$allocation_cash_items_data = array();
											$remittance_items_data = array();
											$remittance_cash_items_data = array();
											$ticket_sale_items_data = array();
											$sales_items_data = array();
											$cash_reports_data = array();

											foreach( $allocation_items as $item )
											{
												$allocation_items_data[] = $item->as_array( array(
														'cat_description' => array( 'type' => 'string' ),
														'cat_module' => array( 'type' => 'string' ),
														'item_name' => array( 'type' => 'string' ),
														'item_description' => array( 'type' => 'string' ),
														'item_class' => array( 'type' => 'string' ),
														'teller_allocatable' => array( 'type' => 'boolean' ),
														'machine_allocatable' => array( 'type' => 'boolean' ),
														'cashier_shift_num' => array( 'type' => 'string' ),
														'base_quantity' => array( 'type' => 'integer' ) ) );
											}

											foreach( $allocation_cash_items as $item )
											{
												$allocation_cash_items_data[] = $item->as_array( array(
														'cat_description' => array( 'type' => 'string' ),
														'cat_module' => array( 'type' => 'string' ),
														'item_name' => array( 'type' => 'string' ),
														'item_description' => array( 'type' => 'string' ),
														'item_class' => array( 'type' => 'string' ),
														'iprice_currency' => array( 'type' => 'string' ),
														'iprice_unit_price' => array( 'type' => 'decimal' ),
														'teller_allocatable' => array( 'type' => 'boolean' ),
														'machine_allocatable' => array( 'type' => 'boolean' ),
														'cashier_shift_num' => array( 'type' => 'string' ) ) );
											}

											foreach( $remittance_items as $item )
											{
												$remittance_items_data[] = $item->as_array( array(
														'cat_description' => array( 'type' => 'string' ),
														'cat_module' => array( 'type' => 'string' ),
														'item_name' => array( 'type' => 'string' ),
														'item_description' => array( 'type' => 'string' ),
														'item_class' => array( 'type' => 'string' ),
														'teller_remittable' => array( 'type' => 'boolean' ),
														'machine_remittable' => array( 'type' => 'boolean' ),
														'cashier_shift_num' => array( 'type' => 'string' ) ) );
											}

											foreach( $remittance_cash_items as $item )
											{
												$remittance_cash_items_data[] = $item->as_array( array(
														'cat_description' => array( 'type' => 'string' ),
														'cat_module' => array( 'type' => 'string' ),
														'item_name' => array( 'type' => 'string' ),
														'item_description' => array( 'type' => 'string' ),
														'item_class' => array( 'type' => 'string' ),
														'iprice_currency' => array( 'type' => 'string' ),
														'iprice_unit_price' => array( 'type' => 'decimal' ),
														'teller_remittable' => array( 'type' => 'boolean' ),
														'machine_remittable' => array( 'type' => 'boolean' ),
														'cashier_shift_num' => array( 'type' => 'string' ) ) );
											}

											foreach( $ticket_sale_items as $item )
											{
												$ticket_sale_items_data[] = $item->as_array( array(
														'cat_description' => array( 'type' => 'string' ),
														'cat_module' => array( 'type' => 'string' ),
														'item_name' => array( 'type' => 'string' ),
														'item_description' => array( 'type' => 'string' ),
														'item_class' => array( 'type' => 'string' ),
														'teller_saleable' => array( 'type' => 'boolean' ),
														'machine_saleable' => array( 'type' => 'boolean' ),
														'cashier_shift_num' => array( 'type' => 'string' ) ) );
											}

											foreach( $sales_items as $item )
											{
												$sales_items_data[] = $item->as_array( array(
														'slitem_name' => array( 'type' => 'string' ),
														'slitem_description' => array( 'type' => 'string' ),
														'slitem_group' => array( 'type' => 'string' ),
														'slitem_mode' => array( 'type' => 'integer'),
														'cashier_shift_num' => array( 'type', 'string' ) ) );
											}

											foreach( $cash_reports as $report )
											{
												$cash_reports_data[] = $report->as_array();
											}

											$allocation_data = $allocation->as_array( array(
													'shift_num' => array( 'type' => 'string' ),
													'shift_description' => array( 'type' => 'string' ) ) );

											$allocation_data['allocations'] = $allocation_items_data;
											$allocation_data['remittances'] = $remittance_items_data;
											$allocation_data['cash_allocations'] = $allocation_cash_items_data;
											$allocation_data['cash_remittances'] = $remittance_cash_items_data;
											$allocation_data['ticket_sales'] = $ticket_sale_items_data;
											$allocation_data['sales'] = $sales_items_data;
											$allocation_data['cash_reports'] = $cash_reports_data;

											$allocations_data[] = $allocation_data;
										}

										$this->_response( array(
											'allocations' => array_values( $allocations_data ),
											'total' => $total_allocations,
											'pending' => $pending_allocations ) );
									}
									break;

								case 'allocations_summary': // allocations
									// Check permissions
									if( !$current_user->check_permissions( 'allocations', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
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
												'allocation' => intval( $allocation['allocation'] ),
												'additional' => intval( $allocation['additional'] ),
												'remitted' => intval( $allocation['remitted'] ),
												'unsold' => intval( $allocation['unsold'] ),
												'rejected' => intval( $allocation['rejected'] )
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
													'cashier_id' => $allocation['cashier_id'],
													'valid_allocation' => 0,
													'valid_remittance' => 0
												);
												$allocations_data[$allocation['id']]['items'] = array( $item );
											}

											$allocations_data[$allocation['id']]['valid_allocation'] += intval( $allocation['valid_allocation'] );
											$allocations_data[$allocation['id']]['valid_remittance'] += intval( $allocation['valid_remittance'] );
										}

										$this->_response( array(
											'allocations' => array_values( $allocations_data ),
											'total' => $total_allocations,
											'pending' => $pending_allocations ) );
									}
									break;

								case 'collections':
									if( !$current_user->check_permissions( 'collections', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
										$params = array(
												'processing_date' => param( $this->input->get(), 'processing_date' ),
												'business_date' => param( $this->input->get(), 'business_date' ),
												'page' => param( $this->input->get(), 'page' ),
												'limit' => param( $this->input->get(), 'limit' ),
											);
										$collections = $store->get_collections( $params );
										$total_collections = $store->count_collections( $params );
										$collections_data = array();
										foreach( $collections as $collection )
										{
											$collection_items = $collection->get_items( TRUE );

											$collection_items_data = array();

											foreach( $collection_items as $item )
											{
												$collection_items_data[] = $item->as_array( array(
														'mopped_station_name' => array( 'type' => 'string' ),
														'mopped_item_name' => array( 'type' => 'string' ),
														'mopped_item_description' => array( 'type' => 'string' ),
														'converted_to_name' => array( 'type' => 'string' ),
														'converted_to_description' => array( 'type' => 'string' ),
														'processor_name' => array( 'type' => 'string' ) ) );
											}

											$collection_data = $collection->as_array( array(
													'shift_num' => array( 'type' => 'string' ),
													'cashier_shift_num' => array( 'type' => 'string' ) ) );

											$collection_data['items'] = $collection_items_data;

											$collections_data[] = $collection_data;
										}
										$this->_response( array(
											'collections' => array_values( $collections_data ),
											'total' => $total_collections ) );
									}
									break;

								case 'collections_summary': // mopping collections
									// Check permissions
									if( !$current_user->check_permissions( 'collections', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
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
									}
									break;
								case 'conversions': // item conversions
									// Check permissions
									if( !$current_user->check_permissions( 'conversions', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
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
									}
									break;

								case 'inventory_history':
									// Check permissions
									if( !$current_user->check_permissions( 'dashboard', 'history' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
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
									}
									break;

								case 'items': // inventory items
									$items = $store->get_items();
									$items_data = array();
									$additional_fields = array(
										'item_name' => array( 'type' => 'string' ),
										'item_class' => array( 'type' => 'string' ),
										'item_unit' => array( 'type' => 'string' ),
										'item_group' => array( 'type' => 'string' ),
										'item_description' => array( 'type' => 'string' ),
										'iprice_currency' => array( 'type' => 'string' ),
										'iprice_unit_price' => array( 'type' => 'decimal' ),
										'teller_allocatable' => array( 'type' => 'boolean' ),
										'teller_remittable' => array( 'type' => 'boolean' ),
										'teller_saleable' => array( 'type' => 'boolean' ),
										'machine_allocatable' => array( 'type' => 'boolean' ),
										'machine_remittable' => array( 'type' => 'boolean' ),
										'machine_saleable' => array( 'type' => 'boolean' ),
										'turnover_item' => array( 'type' => 'boolean' ),
										'movement' => array( 'type' => 'integer' ),
										'sti_beginning_balance' => array( 'type' => 'integer' ),
										'sti_ending_balance' => array( 'type' => 'integer' ),
										'parent_item_name' => array( 'type' => 'string' ),
									);
									foreach( $items as $item )
									{
										$item_data = $item->as_array( $additional_fields );
										$item_data['categories'] = array();
										$categories = $item->get_categories();
										foreach( $categories as $category )
										{
											$item_data['categories'][] = $category->as_array();
										}

										$items_data[] = $item_data;
									}

									$this->_response( $items_data );
									break;

								case 'receipts': // receipts
									// Check permissions
									if( !$current_user->check_permissions( 'transfers', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
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
											$items = $receipt->get_items( TRUE );
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
									}
									break;

								case 'turnover_items':
									// Check permissions
									if( !$current_user->check_permissions( 'allocations', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
										$params = array(
												'date' => param( $this->input->get(), 'date' ),
												'status' => param( $this->input->get(), 'status' ),
												'page' => param( $this->input->get(), 'page' ),
												'limit' => param( $this->input->get(), 'limit' ),
											);

										$items = $store->get_turnover_items( $params );

										$this->_response( array(
											'items' => $items,
											'query' => $this->db->last_query() ) );
									}
									break;

								case 'shift_detail_cash_reports': //
									// Check permissions
									if( ! $current_user->check_permissions( 'allocations', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
										$params = array(
											'date' => param( $this->input->get(), 'date' ),
											'shift' => param( $this->input->get(), 'shift' ),
											'teller_id' => param( $this->input->get(), 'teller_id' ),
											'pos_id' => param( $this->input->get(), 'pos_id' ),
											'page' => param( $this->input->get(), 'page' ),
											'limit' => param( $this->input->get(), 'limit' ),
											'order' => 'sdcr_business_date DESC, sdcr_login_time DESC, id DESC'
										);
										$reports = $store->get_shift_detail_cash_reports( $params );
										$total_shift_detail_cash_reports = $store->count_shift_detail_cash_reports( $params );
										$reports_data = array();

										$additional_fields = array(
											'shift_num' => array( 'type' => 'string' )
										);

										foreach( $reports as $report )
										{
											$reports_data[] = $report->as_array( $additional_fields );
										}

										$this->_response( array(
											'shift_detail_cash_reports' => $reports_data,
											'total' => $total_shift_detail_cash_reports	) );
									}
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

								case 'shift_turnovers': // shift turnovers
									// Check permissions
									if( !$current_user->check_permissions( 'shift_turnovers', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
										$params = array(
												'start' => param( $this->input->get(), 'start' ),
												'end' => param( $this->input->get(), 'end' ),
												'shift' => param( $this->input->get(), 'shift' ),
												'page' => param( $this->input->get(), 'page' ),
												'limit' => param( $this->input->get(), 'limit', NULL, 'integer' ),
											);

										$turnovers = $store->get_shift_turnovers( $params );
										$total_turnovers = $store->count_shift_turnovers( $params );
										// TODO: Add number of pending shift turnovers
										$pending_turnovers = 0;
										$this->_response( array(
											'shift_turnovers' => $turnovers,
											'total' => $total_turnovers,
											'pending' => $pending_turnovers
										) );
									}
									break;

								case 'transfers': // transfers
									// Check permissions
									if( !$current_user->check_permissions( 'transfers', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
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

												$transfer_items = $transfer->get_items( TRUE );
												$transfer_items_data = array();


												foreach( $transfer_items as $item )
												{
													$transfer_items_data[] = $item->as_array( array(
														'item_name' => array( 'type' => 'string' ),
														'item_description' => array( 'type' => 'string' ),
														'cat_description' => array( 'type' => 'string' ),
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
												$items = $transfer->get_items( TRUE );
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
									}
									break;

								case 'transactions': // transactions
									// Check permissions
									if( !$current_user->check_permissions( 'transactions', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
										$params = array(
											'item' => param( $this->input->get(), 'item' ),
											'type' => param( $this->input->get(), 'type' ),
											'date' => param( $this->input->get(), 'date' ),
											'shift' => param( $this->input->get(), 'shift' ),
											'page' => param( $this->input->get(), 'page' ),
											'limit' => param( $this->input->get(), 'limit' ),
											'order' => 'id DESC, transaction_datetime DESC'
										);
										$transactions = $store->get_transactions( $params );
										$total_transactions = $store->count_transactions( $params );
										$transactions_data = array();

										$additional_fields = array(
											'item_name' => array( 'type' => 'string' ),
											'item_description' => array( 'type' => 'string' ),
											'shift_num' => array( 'type' => 'string' ),
											'cat_description' => array( 'type' => 'string' ),
											'parent_item_name' => array( 'type' => 'string' )
										);

										foreach( $transactions as $transaction )
										{
											$transactions_data[] = $transaction->as_array( $additional_fields );
										}

										$this->_response( array(
											'transactions' => $transactions_data,
											'total' => $total_transactions ) );
									}
									break;

								case 'tvm_readings': // TVM readings
									// Check permissions
									if( !$current_user->check_permissions( 'allocations', 'view' ) )
									{
										$this->_error( 403, 'You are not allowed to access this resource' );
									}
									else
									{
										$params = array(
											'date' => param( $this->input->get(), 'date' ),
											'shift' => param( $this->input->get(), 'shift' ),
											'machine_id' => param( $this->input->get(), 'machine_id' ),
											'page' => param( $this->input->get(), 'page' ),
											'limit' => param( $this->input->get(), 'limit' ),
											'order' => 'tvmr_date DESC, tvmr_time DESC, id DESC'
										);
										$readings = $store->get_tvm_readings( $params );
										$total_tvm_readings = $store->count_tvm_readings( $params );
										$readings_data = array();

										$additional_fields = array(
											'shift_num' => array( 'type' => 'string' ),
											'cashier_name' => array( 'type' => 'string' ),
										);

										foreach( $readings as $reading )
										{
											$readings_data[] = $reading->as_array( $additional_fields );
										}

										$this->_response( array(
											'tvm_readings' => $readings_data,
											'total' => $total_tvm_readings ) );
									}
									break;

								default:
									$this->_error( 404, sprintf( '%s resource not found', $relation ) );
							}
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
		$current_user = current_user();
		$current_store = current_store();

		$this->load->library( 'transfer' );
		$Transfer = new Transfer();

		switch( $request_method )
		{
			case 'get':
				// Check permissions
				if( !$current_user->check_permissions( 'transfers', 'view' ) && !$current_user->check_permissions( 'transfer_validations', 'view' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
					$transfer_id = param_type( $this->uri->rsegment( 3 ), 'integer' );
					$relation = param_type( $this->uri->rsegment( 4 ), 'string' );
					$includes = param( $this->input->get(), 'include' );
					$includes = explode( ',', $includes );
					if( $transfer_id )
					{
						$transfer = $Transfer->get_by_id( $transfer_id );
						if( $transfer )
						{
							if( !$current_user->check_permissions( 'transfer_validations', 'view' ) // Allow group with Transfer Validation view privileges
								&& ( !in_array( $current_store->get( 'id' ), array( $transfer->get( 'origin_id' ), $transfer->get( 'destination_id' ) ) )
								|| ( ( ! $transfer->get( 'origin_id' ) || ! is_store_member( $transfer->get( 'origin_id' ), current_user( TRUE ) ) )
								&& ( ! $transfer->get( 'destination_id' ) || ! is_store_member( $transfer->get( 'destination_id' ), current_user( TRUE ) ) ) ) ) )
							{ // current user is not a member of the originating store OR the destination store
								$this->_error( 403, 'You are not allowed to access this resource' );
							}
							else
							{
								switch( $relation )
								{
									case NULL:
										$transfer_data = $transfer->as_array();
										$items = $transfer->get_items( TRUE );
										$items_data = array();
										foreach( $items as $item )
										{
											$items_data[] = $item->as_array( array(
												'item_name' => array( 'type', 'string' ),
												'item_description' => array( 'type', 'string' ),
												'cat_description' => array( 'type', 'string' ) ) );
										}
										$transfer_data['items'] = $items_data;

										if( in_array( 'validation', $includes ) )
										{
											$transfer_validation = $transfer->get_validation();
											if( $transfer_validation )
											{
												$transfer_data['transfer_validation'] = $transfer_validation->as_array();
											}
											else
											{
												$transfer_data['transfer_validation'] = NULL;
											}
										}

										$this->_response( $transfer_data );
										break;

									default:
										$this->_error( 404, sprintf( '%s resource not found', $relation ) );
								}
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
							'category' => param( $this->input->get(), 'category' ),
							'validation_status' => param( $this->input->get(), 'validation_status' ),
							'page' => param( $this->input->get(), 'page' ),
							'limit' => param( $this->input->get(), 'limit' ),
						);
						$transfers = $Transfer->get_transfers( $params );
						$total_transfers = $Transfer->count_transfers( $params );
						$pending_transfers = $Transfer->count_pending_transfers( $params );
						$transfers_data = array();
						$array_params = array();

						foreach( $transfers as $transfer )
						{
							$transfer_data = $transfer->as_array( $array_params );

							if( $params['includes'] && in_array( 'validation', $params['includes'] ) )
							{
								$transfer_validation = $transfer->get_validation();
								if( $transfer_validation )
								{
									$transfer_data['transfer_validation'] = $transfer_validation->as_array();
								}
								else
								{
									$transfer_data['transfer_validation'] = NULL;
								}
							}

							$transfers_data[] = $transfer_data;
						}

						$this->_response( array(
							'transfers' => $transfers_data,
							'total' => $total_transfers,
							'pending' => $pending_transfers ) );
					}
				}
				break;

			case 'post':
				$action = param_type( $this->uri->rsegment( 3 ), 'string' );
				$transfer_id = param( $this->input->post(), 'id' );
				$transfer = $Transfer->load_from_data( $this->input->post() );
				$current_user = current_user();
				$result = FALSE;

				$this->db->trans_start();
				switch( $action )
				{
					case 'approve':
						if( $current_user->check_permissions( 'transfers', 'approve' ) )
						{
							$result = $transfer->approve();
						}
						else
						{
							set_message( 'You do not have the necessary permission to approve transfers', 'error', 403 );
						}
						break;

					case 'cancel':
						switch( $transfer->get( 'transfer_status' ) )
						{
							case TRANSFER_PENDING:
								$allowed = $current_user->check_permissions( 'transfers', 'edit' );
								break;

							case TRANSFER_APPROVED:
								$allowed = $current_user->check_permissions( 'transfers', 'approve' );
								break;

							default:
								$allowed = FALSE;
						}
						if( $allowed )
						{
							$result = $transfer->cancel();
						}
						else
						{
							set_message( 'You do not have the necessary permission to cancel transfers', 'error', 403 );
						}
						break;

					case 'quick_receive':
						$quick_receipt = TRUE;
					case 'receive':
						if( $current_user->check_permissions( 'transfers', 'edit' ) )
						{
							$result = $transfer->receive( isset( $quick_receipt ) && $quick_receipt );
						}
						else
						{
							set_message( 'You do not have the necessary permission to edit or receive transfers', 'error', 403 );
						}
						break;

					default:
						$result = $transfer->db_save();
				}

				if( $result )
				{
					$transfer_items = $transfer->get_items( TRUE );
					$transfer_data = $transfer->as_array();
					foreach( $transfer_items as $item )
					{
						$transfer_data['items'][] = $item->as_array( array(
								'item_name' => array( 'type' => 'string' ),
								'item_description' => array( 'type' => 'string' ),
								'cat_description' => array( 'type' => 'string' ),
								'is_transfer_category' => array( 'type' => 'boolean' ) ) );
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
		$current_user = current_user();

		$this->load->library( 'transfer_validation' );
		$Validation = new Transfer_validation();

		switch( $request_method )
		{
			case 'post':
				$action = param_type( $this->uri->rsegment( 3 ), 'string' );
				$validation_id = param( $this->input->post(), 'id' );
				$validation = $Validation->load_from_data( $this->input->post() );
				$current_user = current_user();
				$result = FALSE;

				$this->db->trans_start();
				switch( $action )
				{
					case 'validate_receipt':
						if( $current_user->check_permissions( 'transfer_validations', 'edit' ) )
						{
							$result = $validation->validate_receipt();
						}
						else
						{
							set_message( 'You do not have the necessary permission to edit transfer validations', 'error', 403 );
						}
						break;

					case 'returned':
						if( $current_user->check_permissions( 'transfer_validations', 'edit' ) )
						{
							$result = $validation->return_transfer();
						}
						else
						{
							set_message( 'You do not have the necessary permission to edit transfer validations', 'error', 403 );
						}

						break;

					case 'validate_transfer':
						if( $current_user->check_permissions( 'transfer_validations', 'edit' ) )
						{
							$result = $validation->validate_transfer();
						}
						else
						{
							set_message( 'You do not have the necessary permission to edit transfer validations', 'error', 403 );
						}
						break;

					case 'dispute':
						if( $current_user->check_permissions( 'transfer_validations', 'edit' ) )
						{
							$result = $validation->dispute();
						}
						else
						{
							set_message( 'You do not have the necessary permission to edit transfer validations', 'error', 403 );
						}
						break;

					case 'complete':
						if( $current_user->check_permissions( 'transfer_validations', 'complete' ) )
						{
							$result = $validation->complete();
						}
						else
						{
							set_message( 'You do not have the necessary permission to mark transfer validations as completed', 'error', 403 );
						}
						break;

					case 'ongoing':
						if( $current_user->check_permissions( 'transfer_validations', 'complete' ) )
						{
							$result = $validation->ongoing();
						}
						else
						{
							set_message( 'You do not have the necessary permission to mark transfer validations as ongoing', 'error', 403 );
						}
						break;

					case 'not_required':
						if( $current_user->check_permissions( 'transfer_validations', 'complete' ) )
						{
							$result = $validation->not_required();
						}
						else
						{
							set_message( 'You do not have the necessary permission to mark transfer as not requiring validation ', 'error', 403 );
						}
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

	public function tvm_readings()
	{
		$request_method = $this->input->method();
		$current_user = current_user();

		$this->load->library( 'tvm_reading' );
		$TVM_Reading = new Tvm_reading();

		switch( $request_method )
		{
			case 'get':
				// Check permissions
				if( !$current_user->check_permissions( 'allocations', 'view' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
					$tvm_reading_id = param_type( $this->uri->rsegment( 3 ), 'string' );

					if( $tvm_reading_id == 'last_reading' )
					{
						$params = array();
						$params['machine'] = param_type( $this->input->get( 'machine' ), 'integer' );
						$params['date'] = param_type( $this->input->get( 'date' ), 'date' );
						$params['shift'] = param_type( $this->input->get( 'shift' ), 'integer' );
						$tvm_reading = $TVM_Reading->get_by_shift_last_reading( $params );
						if( $tvm_reading )
						{
							if( $tvm_reading->get( 'tvmr_store_id') != current_store( TRUE )
								|| ! is_store_member( $tvm_reading->get( 'tvmr_store_id' ), current_user( TRUE ) ) )
							{
								$this->_error( 403, 'You are not allowed to access this resource.
										The resource you are trying to access belongs to another store or you are not a member of the owner store.' );
							}
							else
							{
								$tvm_reading_data = $tvm_reading->as_array();
								$tvm_reading_items = $tvm_reading->get_readings();
								$tvm_reading_items_data = array();
								foreach( $tvm_reading_items as $item )
								{
									$tvm_reading_items_data[] = $item->as_array();
								}
								$tvm_reading_data['readings'] = $tvm_reading_items_data;

								$previous_tvm_reading = $tvm_reading->get_previous_shift_last_reading();
								$previous_tvm_reading_data = array();
								if( $previous_tvm_reading )
								{
									$previous_tvm_reading_data = $previous_tvm_reading->as_array();
									$previous_tvm_reading_items = $previous_tvm_reading->get_readings();
									$previous_tvm_reading_items_data = array();
									foreach( $previous_tvm_reading_items as $item )
									{
										$previous_tvm_reading_items_data[] = $item->as_array();
									}
									$previous_tvm_reading_data['readings'] = $previous_tvm_reading_items_data;
								}
								$tvm_reading_data['previous_reading'] = $previous_tvm_reading_data;
								$this->_response( $tvm_reading_data );
							}
						}
						else
						{
							$this->_error( 200, 'TVM reading record not found' );
						}
					}
					elseif( intval( $tvm_reading_id ) )
					{
						$tvm_reading = $TVM_Reading->get_by_id( $tvm_reading_id );
						if( $tvm_reading )
						{
							if( $tvm_reading->get( 'tvmr_store_id') != current_store( TRUE )
								|| ! is_store_member( $tvm_reading->get( 'tvmr_store_id' ), current_user( TRUE ) ) )
							{
								$this->_error( 403, 'You are not allowed to access this resource.
										The resource you are trying to access belongs to another store or you are not a member of the owner store.' );
							}
							else
							{
								$tvm_reading_data = $tvm_reading->as_array( array(
									'shift_num' => array( 'type' => 'string' ) ) );

								$previous_tvm_reading = $tvm_reading->get_previous_shift_last_reading();
								$previous_tvm_reading_data = array();
								if( $previous_tvm_reading )
								{
									$previous_tvm_reading_data = $previous_tvm_reading->as_array();
									$previous_tvm_reading_items = $previous_tvm_reading->get_readings();
									$previous_tvm_reading_items_data = array();
									foreach( $previous_tvm_reading_items as $item )
									{
										$previous_tvm_reading_items_data[] = $item->as_array();
									}
									$previous_tvm_reading_data['readings'] = $previous_tvm_reading_items_data;
								}
								$tvm_reading_data['previous_reading'] = $previous_tvm_reading_data;
								$this->_response( $tvm_reading_data );
							}
						}
						else
						{
							$this->_error( 404, 'TVM reading record not found' );
						}
					}
					else
					{
						$this->_error( 400, 'Missing required TVM reading ID' );
					}
				}
				break;

			case 'post':
				$action = param_type( $this->uri->rsegment( 3 ), 'string' );
				$tvm_reading_id = param( $this->input->post(), 'id' );
				$tvm_reading = $TVM_Reading->load_from_data( $this->input->post() );

				$this->db->trans_start();
				switch( $action )
				{
					default:
						$result = $tvm_reading->db_save();
				}
				if( $result )
				{
					$this->db->trans_complete();

					if( $this->db->trans_status() )
					{
						$this->_response( $tvm_reading->as_array(), $tvm_reading_id ? 200 : 201 );
					}
					else
					{
						$this->_error( 500, 'A database error has occurred while trying to save TVM reading record' );
					}
				}
				else
				{
					$messages = get_messages();
					$this->_error( 200, $messages );
				}
				break;

			case 'delete':
				// Check permissions
				if( !$current_user->check_permissions( 'allocations', 'edit' ) )
				{
					$this->_error( 403, 'You are not allowed to access this resource' );
				}
				else
				{
					$tvm_reading_id = param_type( $this->uri->rsegment( 3 ), 'string' );
					$tvm_reading = $TVM_Reading->get_by_id( $tvm_reading_id );

					if( $tvm_reading )
					{
						$tvm_reading->db_remove();
						$this->_response( array( 'id' => $tvm_reading_id ) );
					}
					else
					{
						$this->_error( 404, 'TVM reading record not found' );
					}
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
						$group = param_type( $this->input->get( 'group' ), 'integer' );
						$users = $User->search( $q, $group );
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