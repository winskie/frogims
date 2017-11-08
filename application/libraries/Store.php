<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Store extends Base_model
{
	protected $store_name;
	protected $store_code;
	protected $store_type;
	protected $store_station_id;
	protected $store_location;
	protected $store_contact_number;

	protected $members;
	protected $shifts;
	protected $items;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	public function __construct()
	{
		$this->primary_table = 'stores';
		$this->db_fields = array(
			'store_name' => array( 'type' => 'string' ),
			'store_code' => array( 'type' => 'string' ),
			'store_type' => array( 'type' => 'integer' ),
			'store_station_id' => array( 'type' => 'integer' ),
			'store_location' => array( 'type' => 'string' ),
			'store_contact_number' => array( 'type' => 'string' )
		);
		parent::__construct();
	}

	public function get_stores( $params = array() )
	{
		$ci =& get_instance();
		$format = param( $params, 'format', 'object' );

		//$ci->db->select( 'id, store_name AS name, store_location AS location, store_contact_number AS contact_number' );
		$query = $ci->db->get( $this->primary_table );

		if( $format == 'object' )
		{
			return $query->result( get_class( $this ) );
		}
		elseif( $format == 'array' )
		{
			return $query->result_array();
		}

		return NULL;
	}


	public function get( $property, $params = array() )
	{
		if( $property == 'items' )
		{
			return get_items( $params );
		}
		elseif( $property == 'shifts' )
		{
			return get_shifts();
		}
		elseif( $property == 'members' )
		{
			return get_members();
		}
		if( property_exists( $this, $property ) )
		{
			return $this->$property;
		}
		else
		{
			return NULL;
		}
	}


	public function get_shifts()
	{
		if( ! isset( $this->shifts ) )
		{
			$ci =& get_instance();

			$ci->load->library( 'shift' );
			$ci->db->where( 'store_type', $this->store_type );
			$query = $ci->db->get( 'shifts' );

			$this->shifts = $query->result( 'Shift' );
		}

		return $this->shifts;
	}


	public function get_store_shifts( $params = array() )
	{
		$ci =& get_instance();
		$ci->load->library( 'shift' );

		$order = param( $params, 'order' );

		if( $order )
		{
			$ci->db->order_by( $order );
		}

		$ci->db->where( 'store_type', $this->store_type );
		$query = $ci->db->get( 'shifts' );

		return $query->result( 'Shift' );
	}


	public function get_suggested_shift( $time = 'now' )
	{
		$suggested_shift = NULL;
		$store_shifts = $this->get_store_shifts( array( 'order' => 'shift_start_time ASC, shift_end_time ASC') );
		$time = strtotime( $time );
		$time_seconds = ( (int) date( 'H', $time ) * 3600 ) + ( (int) date( 'M', $time ) * 60 ) + ( (int) date( 's', $time ) );

		foreach( $store_shifts as $shift )
		{
			$start = strtotime( $shift->get( 'shift_start_time' ) );
			$start_seconds = ( (int) date( 'H', $start ) * 3600 ) + ( (int) date( 'M', $start ) * 60 ) + ( (int) date( 's', $start ) );
			$end = strtotime( $shift->get( 'shift_end_time' ) );
			$end_seconds = ( (int) date( 'H', $end ) * 3600 ) + ( (int) date( 'M', $end ) * 60 ) + ( (int) date( 's', $end ) );

			if( $start <= $end )
			{
				if( $time_seconds >= $start_seconds && $time_seconds <= $end_seconds )
				{
					$suggested_shift = $shift;
					break;
				}
			}
			else
			{
				if( $time_seconds >= $start_seconds || $time_seconds <= $end_seconds )
				{
					$suggested_shift = $shift;
					break;
				}
			}
		}

		if( $suggested_shift )
		{
			return $suggested_shift;
		}
		elseif( $store_shifts )
		{
			return $store_shifts[0];
		}
		else
		{
			return NULL;
		}
	}


	public function get_members()
	{
		$ci =& get_instance();

		$ci->load->library( 'user' );
		$ci->db->where( 'store_id', $this->id );
		$ci->db->join( 'users', 'users.id = store_users.user_id', 'left' );
		$query = $ci->db->get( 'store_users' );

		return $query->result( 'User' );
	}


	public function is_member( $user_id )
	{
		$ci =& get_instance();

		$ci->db->where( 'store_id', param_type( $this->id, 'integer' ) );
		$ci->db->where( 'user_id', param_type( $user_id, 'integer' ) );
		$query = $ci->db->get( 'store_users' );

		return $query->num_rows() > 0;
	}


	public function add_member( $user )
	{
		$ci =& get_instance();
		$data = array(
			'store_id' => $this->id,
			'user_id' => $user->get( 'id' ),
			'date_joined' => date( TIMESTAMP_FORMAT )
		);
		$ci->db->trans_start();
		$ci->db->insert( 'store_users', $data );
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}


	public function remove_member( $user )
	{
		$ci =& get_instance();

		$ci->db->trans_start();
		$ci->db->where( 'user_id', $user->id );
		$ci->db->where( 'store_id', $this->id );
		$ci->db->delete( 'store_users' );
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}


	public function get_items( $params = array() )
	{
		$business_date = param( $params, 'date', date( DATE_FORMAT ) );
		$shift = param( $params, 'shift', current_shift( TRUE ) );
		$format = param( $params, 'format', 'object' );

		$ci =& get_instance();
		$ci->load->library( 'inventory' );

		$query_params = array();
		$sql = 'SELECT
					si.*,
					i.item_name, i.item_description, i.item_class, i.item_group, i.item_unit,
					i.teller_allocatable, i.teller_remittable, i.teller_saleable,
					i.machine_allocatable, i.machine_remittable, i.machine_saleable,
					ip.iprice_currency, ip.iprice_unit_price,
					ts.movement, sts.sti_beginning_balance, sts.sti_ending_balance
				FROM store_inventory AS si
				LEFT JOIN items AS i
					ON i.id = si.item_id
				LEFT JOIN item_prices AS ip
					ON ip.iprice_item_id = i.id
				LEFT JOIN (';

		if( $shift )
		{
			$sql .= ' SELECT
						sti.sti_item_id,
						sti.sti_beginning_balance,
						sti.sti_ending_balance
					FROM shift_turnover_items AS sti
					LEFT JOIN shift_turnovers AS st
						ON st.id = sti.sti_turnover_id
					WHERE
						st.st_store_id = ?
						AND st.st_from_date = ?
						AND st.st_from_shift_id = ?
				) AS sts
					ON sts.sti_item_id = i.id';

			// TODO: Add index for st_store_id, st_from_date, st_from_shift_id
			$query_params[] = $this->id;
			$query_params[] = $business_date;
			$query_params[] = $shift;
		}

		$sql .= ' LEFT JOIN (
					SELECT
						t.store_inventory_id,
						SUM( transaction_quantity ) AS movement
					FROM transactions AS t
					LEFT JOIN store_inventory AS si
						ON si.id = t.store_inventory_id
					WHERE
						si.store_id = ?
						AND t.transaction_datetime >= ?
						AND t.transaction_datetime <= ?';
		$query_params[] = $this->id;
		$query_params[] = $business_date.' 00:00:00';
		$query_params[] = $business_date.' 23:59:59';

		if( $shift )
		{
			$sql .= ' AND t.transaction_shift = ?';
			$query_params[] = $shift;
		}

		$sql .= ' GROUP BY
					t.store_inventory_id
				) AS ts
					ON ts.store_inventory_id = si.id
				WHERE
					store_id = ?';

		// Do not show subinventories
		$sql .= ' AND parent_item_id IS NULL';

		$query_params[] = $this->id;

		$query = $ci->db->query( $sql, $query_params );

		if( $format == 'object')
		{
			$items = array();
			$store_items = $query->result( 'Inventory' );
			foreach( $store_items as $store_item )
			{
				$items[$store_item->get( 'id' )] = $store_item;
			}

			return $items;
		}
		elseif( $format == 'array' )
		{
			return $query->result_array();
		}

		return NULL;
	}


	public function add_item( $item, $buffer_level = 0, $parent_item_id = NULL )
	{
		$ci =& get_instance();

		$item_id = $item->get( 'id' );
		$data = array(
			'store_id' => $this->id,
			'item_id' => $item_id,
			'parent_item_id' => $parent_item_id,
			'quantity' => 0,
			'quantity_timestamp' => date( TIMESTAMP_FORMAT ),
			'buffer_level' => $buffer_level,
			'reserved' => 0
		);

		$ci->db->trans_start();
		$ci->db->insert( 'store_inventory', $data );
		$ci->db->trans_complete();

		$ci->load->library( 'Inventory' );
		$inventory = $ci->inventory->get_by_store_item( $this->id, $item_id, $parent_item_id );

		if ( $ci->db->trans_status() )
		{
			return $inventory;
		}
		else
		{
			return FALSE;
		}
	}


	public function remove_item( $item )
	{
		$ci =& get_instance();

		$ci->db->trans_start();
		$ci->db->where( 'store_id', $this->id );
		$ci->db->where( 'item_id', $item_id->get( 'id' ) );
		$ci->db->delete( 'store_inventory' );
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}


	public function get_transactions( $params = array() )
	{
		$ci =& get_instance();

		$ci->load->library( 'transaction' );
		$business_date = param( $params, 'date' );
		$shift = param( $params, 'shift' );
		$item_id = param( $params, 'item' );
		$transaction_type = param( $params, 'type' );

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'transaction_datetime DESC, id DESC' );

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}

		$ci->db->select( 't.*, i.id AS item_id, i.item_name, i.item_description, s.shift_num, c.cat_description' );
		$ci->db->join( 'store_inventory si', 'si.id = t.store_inventory_id' );
		$ci->db->join( 'items i', 'i.id = si.item_id' );
		$ci->db->join( 'shifts s', 's.id = t.transaction_shift' );
		$ci->db->join( 'categories c', 'c.id = t.transaction_category_id' );
		$ci->db->where( 'si.store_id', intval( $this->id ) );

		// Do not include transactions of subinventories
		$ci->db->where( 'si.parent_item_id IS NULL' );

		if( $business_date )
		{
			$ci->db->where( 'transaction_datetime >=', $business_date. ' 00:00:00' );
			$ci->db->where( 'transaction_datetime <=', $business_date. ' 23:59:59' );
		}

		if( $shift )
		{
			$ci->db->where( 'transaction_shift', $shift );
		}

		if( $item_id )
		{
			$ci->db->where( 'i.id', $item_id );
		}

		if( $transaction_type )
		{
			$ci->db->where( 'transaction_type', $transaction_type );
		}

		$query = $ci->db->get( 'transactions t' );

		if( $format == 'array' )
		{
			return $query->result_array();
		}

		return $query->result( 'Transaction' );
	}


	public function get_transfers( $params = array() )
	{
		$ci =& get_instance();

		$ci->load->library( 'Transfer' );
		$business_date = param( $params, 'date' );
		$destination = param( $params, 'dst' );
		$status = param( $params, 'status' );
		$includes = param( $params, 'includes' );

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'transfer_datetime DESC, id DESC' );

		$select = 't.*';

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}

		if( $business_date )
		{
			$ci->db->where( 'DATE(transfer_datetime)', $business_date );
		}

		if( $destination )
		{
			if( $destination == '_ext_' )
			{
				$ci->db->where( 'destination_id IS NULL' );
				$ci->db->where( 'destination_name IS NOT NULL' );
			}
			else
			{
				$ci->db->where( 'destination_id', $destination );
			}
		}

		if( $status )
		{
			$ci->db->where( 'transfer_status', $status );
		}

		if( $includes )
		{
			if( in_array( 'validation', $includes ) )
			{
				$ci->db->join( 'transfer_validations AS tv', 'tv.transval_transfer_id = t.id', 'left' );
				$select .= ', tv.id AS transval_id, tv.transval_receipt_status, tv.transval_receipt_datetime, tv.transval_receipt_sweeper, tv.transval_receipt_user_id, tv.transval_receipt_shift_id,
						tv.transval_transfer_status, tv.transval_transfer_datetime, tv.transval_transfer_sweeper, tv.transval_transfer_user_id, tv.transval_transfer_shift_id, tv.transval_status';
			}
		}

		$ci->db->select( $select );
		$ci->db->where( 'origin_id', $this->id );
		//$ci->db->join( 'items i', 'i.id = t.item_id' );
		$query = $ci->db->get( 'transfers t' );

		if( $format == 'object')
		{
			return $query->result( 'Transfer' );
		}
		elseif( $format == 'array' )
		{
			return $query->result_array();
		}

		return NULL;
	}


	public function get_receipts( $params = array() )
	{
		$receipt_date = param( $params, 'date' );
		$source = param( $params, 'src' );
		$status = param( $params, 'status' );
		$includes = param( $params, 'includes' );

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$order = param( $params, 'order', 'receipt_datetime DESC, id DESC' );
		$format = param( $params, 'format', 'object' );

		$ci =& get_instance();
		$ci->load->library( 'transfer' );

		// Do not show pending or scheduled transfers
		$available_status = array( TRANSFER_APPROVED, TRANSFER_RECEIVED, TRANSFER_APPROVED_CANCELLED );

		$select = 't.*';

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}

		if( $receipt_date )
		{
			$ci->db->where( "(DATE(receipt_datetime) = '${receipt_date}' OR receipt_datetime IS NULL )");
		}

		if( $source )
		{
			if( $source == '_ext_' )
			{
				$ci->db->where( 'origin_id IS NULL' );
				$ci->db->where( 'origin_name IS NOT NULL' );
			}
			else
			{
				$ci->db->where( 'origin_id', $source );
			}
		}

		if( $status )
		{
			$ci->db->where( 'transfer_status', $status );
		}

		$ci->db->where_in( 'transfer_status', $available_status );
		$ci->db->where( 'destination_id', $this->id );
		$ci->db->join( 'stores s', 's.id = t.origin_id', 'left' );

		if( $includes )
		{
			if( in_array( 'validation', $includes ) )
			{
				$ci->db->join( 'transfer_validations AS tv', 'tv.transval_transfer_id = t.id', 'left' );
				$select .= ', tv.transval_receipt_status, tv.transval_receipt_datetime, tv.transval_receipt_sweeper, tv.transval_receipt_user_id, tv.transval_receipt_shift_id,
						tv.transval_transfer_status, tv.transval_transfer_datetime, tv.transval_transfer_sweeper, tv.transval_transfer_user_id, tv.transval_transfer_shift_id';
			}
		}
		$ci->db->select( $select );
		$query = $ci->db->get( 'transfers t' );

		if( $format == 'object' )
		{
			return $query->result( 'Transfer' );
		}
		elseif( $format == 'array' )
		{
			return $query->result_array();
		}

		return NULL;
	}


	public function get_adjustments( $params = array() )
	{
		$adjustment_date = param( $params, 'date' );
		$item_id = param( $params, 'item' );
		$status = param( $params, 'status' );

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$order = param( $params, 'order', 'a.adjustment_timestamp DESC, a.id DESC' );
		$format = param( $params, 'format', 'object' );

		$ci =& get_instance();
		$ci->load->library( 'adjustment' );

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}

		if( $adjustment_date )
		{
			$ci->db->where( 'DATE(adjustment_timestamp)', $adjustment_date );
		}

		if( $item_id )
		{
			$ci->db->where( 'si.item_id', $item_id );
		}

		if( $status )
		{
			$ci->db->where( 'adjustment_status', $status );
		}

		$ci->db->select( 'a.*, i.item_name, i.item_description, u.username, u.full_name' );
		$ci->db->where( 'si.store_id', $this->id );
		$ci->db->join( 'store_inventory si', 'si.id = a.store_inventory_id', 'left' );
		$ci->db->join( 'items i', 'i.id = si.item_id', 'left' );
		$ci->db->join( 'users u', 'u.id = a.user_id', 'left' );
		$adjustments = $ci->db->get( 'adjustments a' );
		$adjustments = $adjustments->result( 'Adjustment' );
		if( $format == 'array' )
		{
			$adjustments_data = array();
			foreach( $adjustments as $adjustment )
			{
				$adjustments_data[] = $adjustment->as_array( array(
					'item_name' => array( 'type' => 'string' ),
					'item_description' => array( 'type' => 'string' ),
					'username' => array( 'type' => 'string' ),
					'full_name' => array( 'type' => 'string' ) ) );
			}
			return $adjustments_data;
		}

		return $adjustments;
	}


	public function get_collections( $params =array () )
	{
			$ci =& get_instance();

			$ci->load->library( 'mopping' );
			$limit = param( $params, 'limit' );
			$offset = param( $params, 'offset' );
			$order = param( $params, 'order', 'm.processing_datetime DESC, m.id DESC' );
			$format = param( $params, 'format', 'object' );

			if( $limit )
			{
					$ci->db->limit( $limit, ( $offset ? $offset : 0 ) );
			}
			if( $order )
			{
					$ci->db->order_by( $order );
			}

			$ci->db->select( 'm.*, s.shift_num AS shift_num, cs.shift_num AS cashier_shift_num' );
			$ci->db->join( 'shifts s', 's.id = m.shift_id', 'left' );
			$ci->db->join( 'shifts cs', 'cs.id = m.cashier_shift_id', 'left' );
			$ci->db->where( 'm.store_id', $this->id );
			$query = $ci->db->get( 'mopping m' );

			if( $format == 'object' )
			{
					return $query->result( 'Mopping' );
			}
			elseif( $format == 'array' )
			{
					return $query->result_array();
			}

			return NULL;
	}


	public function get_collections_summary( $params = array() )
	{
		$processing_date = param( $params, 'processing_date' );
		$business_date = param( $params, 'business_date' );
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );

		$ci =& get_instance();

		$sql = 'SELECT
					b.mopping_id AS id, b.*, i.item_name, i.item_description, s.shift_num AS shift_num, cs.shift_num AS cashier_shift_num
				FROM (
					SELECT
						mopping_id,
						processing_datetime,
						business_date,
						shift_id,
						cashier_shift_id,
						mopping_item_status,
						IF( converted_to IS NULL, mopped_item_id, converted_to ) AS item_id,
						SUM( IF( converted_to IS NULL, quantity, quantity DIV conversion_factor ) ) AS quantity
					FROM (
						SELECT
							mopping_id,
							processing_datetime,
							business_date,
							shift_id,
							cashier_shift_id,
							mopped_item_id,
							converted_to,
							mopping_item_status,
							SUM( mopped_quantity ) AS quantity
						FROM mopping_items AS mi
						RIGHT JOIN mopping AS m
							ON m.id = mi.mopping_id
						WHERE
							m.store_id = ?';

		if( $processing_date )
		{
			$sql .= " AND DATE(processing_datetime) = '${processing_date}'";
		}

		if( $business_date )
		{
			$sql .= " AND business_date = '${business_date}'";
		}
		$sql .= ' GROUP BY mopping_id, processing_datetime, business_date, shift_id, cashier_shift_id, mopped_item_id, converted_to, mopping_item_status
					) AS a
					LEFT JOIN conversion_table AS ct
						ON ct.source_item_id = a.mopped_item_id AND ct.target_item_id = a.converted_to
					GROUP BY mopping_id, processing_datetime, business_date, shift_id, cashier_shift_id, item_id, mopping_item_status
				) AS b
				LEFT JOIN items AS i
					ON i.id = b.item_id
				LEFT JOIN shifts AS s
					ON s.id = b.shift_id
				LEFT JOIN shifts AS cs
					ON cs.id = b.cashier_shift_id
				ORDER BY b.processing_datetime DESC';

		if( $limit )
		{
			$sql .= ' LIMIT '.( $page ? ( ( $page - 1 )  * $limit ) : 0 ).', '.$limit;
		}

		$query = $ci->db->query( $sql, array( $this->id ) );

		return $query->result_array();
	}


	public function get_allocations( $params = array() )
	{
		$ci =& get_instance();

		$ci->load->library( 'allocation' );
		$limit = param( $params, 'limit' );
		$offset = param( $params, 'offset' );
		$order = param( $params, 'order', 'a.business_date DESC, a.id DESC' );
		$format = param( $params, 'format', 'object' );

		if( $limit )
		{
				$ci->db->limit( $limit, ( $offset ? $offset : 0 ) );
		}
		if( $order )
		{
				$ci->db->order_by( $order );
		}

		$ci->db->select( 'a.*, s.shift_num, s.description AS shift_description' );
		$ci->db->where( 'a.store_id', $this->id );
		$ci->db->join( 'shifts s', 's.id = a.shift_id', 'left' );
		$query = $ci->db->get( 'allocations a' );

		if( $format == 'object' )
		{
				return $query->result( 'Allocation' );
		}
		elseif( $format == 'array' )
		{
				return $query->result_array();
		}

		return NULL;
	}


	public function get_allocations_summary( $params = array() )
	{
		$allocation_date = param( $params, 'date' );
		$assignee_type = param( $params, 'assignee_type' );
		$status = param( $params, 'status' );
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );

		$ci =& get_instance();

		$sql = 'SELECT
					a.id, a.store_id, a.business_date, a.shift_id, a.station_id, a.assignee, a.assignee_type,	a.allocation_status, a.cashier_id,
					s.shift_num,
					x.allocated_item_id, x.item_name, x.item_description, x.item_class, x.allocation, x.additional, x.remitted, x.unsold, x.rejected, x.valid_allocation, x.valid_remittance
				FROM (
					SELECT
						allocation_id,
						allocated_item_id,
						item_name,
						item_description,
						item_class,
						SUM( IF( c.is_allocation_category = TRUE AND category = "Initial Allocation" AND NOT allocation_item_status IN ('.implode( ', ', array( ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED ) ).'), allocated_quantity, 0 ) ) AS allocation,
						SUM( IF( c.is_allocation_category = TRUE AND category IN ( "Additional Allocation", "Magazine Load" ) AND NOT allocation_item_status = '.ALLOCATION_ITEM_VOIDED.', allocated_quantity, 0 ) ) AS additional,
						SUM( IF( c.is_remittance_category = TRUE AND NOT allocation_item_status = '.REMITTANCE_ITEM_VOIDED.', allocated_quantity, 0 ) ) AS remitted,
						SUM( IF( c.is_remittance_category = TRUE AND category = "Unsold / Loose" AND NOT allocation_item_status = '.REMITTANCE_ITEM_VOIDED.', allocated_quantity, 0 ) ) AS unsold,
						SUM( IF( c.is_remittance_category = TRUE AND category = "Reject Bin" AND NOT allocation_item_status = '.REMITTANCE_ITEM_VOIDED.', allocated_quantity, 0 ) ) AS rejected,
						SUM( IF( ai.allocation_item_status IN ( '.implode( ',', array( ALLOCATION_ITEM_SCHEDULED, ALLOCATION_ITEM_ALLOCATED ) ).' ) AND ai.allocated_quantity > 0, 1, 0 ) ) AS valid_allocation,
						SUM( IF( ai.allocation_item_status IN ( '.implode( ',', array( REMITTANCE_ITEM_PENDING, REMITTANCE_ITEM_REMITTED ) ).' ) AND ai.allocated_quantity > 0, 1, 0 ) ) AS valid_remittance
					FROM allocation_items AS ai
					LEFT JOIN allocations AS a
						ON a.id = ai.allocation_id
					LEFT JOIN items AS i
						ON i.id = ai.allocated_item_id
					LEFT JOIN categories AS c
						ON c.id = ai.allocation_category_id
					WHERE ai.allocation_item_type IN (1, 2)';

		if( $allocation_date || $assignee_type || $status )
		{
			$sql .= ' AND a.id IS NOT NULL';
		}

		if( $allocation_date )
		{
			$sql .= " AND a.business_date = '${allocation_date}'";
		}

		if( $assignee_type )
		{
			$sql .= " AND a.assignee_type = ${assignee_type}";
		}

		if( $status )
		{
			$sql .= " AND a.allocation_status = ${status}";
		}

		$sql .= ' GROUP BY allocation_id, allocated_item_id, item_name, item_description
				) AS x
				RIGHT JOIN allocations AS a
					ON x.allocation_id = a.id
				LEFT JOIN shifts AS s
					ON s.id = a.shift_id
				WHERE a.store_id = ?';

		if( $allocation_date )
		{
			$sql .= " AND a.business_date = '${allocation_date}'";
		}

		if( $assignee_type )
		{
			$sql .= " AND a.assignee_type = ${assignee_type}";
		}

		if( $status )
		{
			$sql .= " AND a.allocation_status = ${status}";
		}

		$sql .= ' ORDER BY a.business_date DESC, a.id DESC';

		if( $limit )
		{
			$sql .= ' LIMIT '.( $page ? ( ( $page - 1 )  * $limit ) : 0 ).', '.$limit;
		}

		$query = $ci->db->query( $sql, array( $this->id ) );

		return $query->result_array();
	}


	public function get_turnover_items( $params = array() )
	{
		$business_date = param( $params, 'date' );
		$status = param( $params, 'status' );
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$order = param( $params, 'order', 'order_col ASC, shift_num ASC' );

		$params = array();

		$ci =& get_instance();

		$transfer_item_statuses = array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED );

		$sql = 'SELECT 1 AS order_col, "Remittance" AS item_source,
					s.shift_num,
					a.id AS source_id, a.assignee_type, a.assignee,
					i.id AS item_id, i.item_name, i.item_description,
					c.id AS transfer_item_category_id, c.cat_description,
					ai.allocated_quantity AS quantity,
					ai.id AS allocation_item_id,
					NULL AS transfer_item_id,
					ti.id AS turnover_id
				FROM allocations AS a
				LEFT JOIN allocation_items AS ai
					ON ai.allocation_id = a.id
				LEFT JOIN shifts AS s
					ON s.id = ai.cashier_shift_id
				LEFT JOIN items AS i
					ON i.id = ai.allocated_item_id
				LEFT JOIN categories AS c
					ON c.id = ai.allocation_category_id
				LEFT JOIN transfer_items AS ti
					ON ti.transfer_item_allocation_item_id = ai.id AND ti.transfer_item_status NOT IN ( '.implode( ', ', $transfer_item_statuses).' )

				WHERE
					c.cat_module = "Remittance"
					AND i.turnover_item = 1';

		if( $business_date )
		{ // TODO: secure this parameter
			$sql .= " AND a.business_date = ?";
			$params[] = $business_date;
		}

		$sql .= ' UNION ALL

				SELECT 2, "Blackbox",
					s.shift_num,
					t.id, NULL, t.origin_name,
					i.id, i.item_name, i.item_description,
					c.id, c.cat_description,
					ti.quantity_received,
					NULL,
					ti.id,
					ti2.id AS turnover_id
				FROM transfer_items AS ti
				LEFT JOIN transfers AS t
					ON t.id = ti.transfer_id
				LEFT JOIN shifts AS s
					ON s.id = t.recipient_shift
				LEFT JOIN items AS i
					ON i.id = ti.item_id
				LEFT JOIN categories AS c
					ON c.id = ti.transfer_item_category_id
				LEFT JOIN transfer_items AS ti2
					ON ti2.transfer_item_transfer_item_id = ti.id AND ti2.transfer_item_status NOT IN ( '.implode( ', ', $transfer_item_statuses).' )

				WHERE
					t.transfer_category = 6
					AND i.turnover_item = 1';

		if( $business_date )
		{ // TODO: secure this parameter
			$sql .= " AND DATE(t.receipt_datetime) = ?";
			$params[] = $business_date;
		}

		$sql .= ' ORDER BY '.$order;

		$remittances = $ci->db->query( $sql, $params );

		return $remittances->result_array();
	}

	public function get_conversions( $params =array () )
	{
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$order = param( $params, 'order', 'c.conversion_datetime DESC, c.id DESC' );
		$format = param( $params, 'format', 'object' );

		$conversion_date = param( $params, 'date' );
		$input_item_id = param( $params, 'input' );
		$output_item_id = param( $params, 'output' );

		$ci =& get_instance();
		$ci->load->library( 'conversion' );

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}

		if( $order )
		{
				$ci->db->order_by( $order );
		}

		if( $conversion_date )
		{
			$ci->db->where( 'DATE(c.conversion_datetime)', $conversion_date );
		}

		if( $input_item_id )
		{
			$ci->db->where( 'si.item_id', $input_item_id );
		}

		if( $output_item_id )
		{
			$ci->db->where( 'ti.item_id', $output_item_id );
		}

		$ci->db->select( 'c.*, src_item.item_name AS source_item_name, src_item.item_description AS source_item_description,
						tgt_item.item_name AS target_item_name, tgt_item.item_description AS target_item_description' );
		$ci->db->where( 'c.store_id', $this->id );
		$ci->db->join( 'store_inventory si', 'si.id = c.source_inventory_id', 'left' );
		$ci->db->join( 'store_inventory ti', 'ti.id = c.target_inventory_id', 'left' );
		$ci->db->join( 'items src_item', 'src_item.id = si.item_id', 'left' );
		$ci->db->join( 'items tgt_item', 'tgt_item.id = ti.item_id', 'left' );
		$conversions = $ci->db->get( 'conversions c' );
		$conversions = $conversions->result( 'Conversion' );

		if( $format == 'array' )
		{
			$conversions_array = array();
			foreach( $conversions as $conversion )
			{
				$conversions_array[] = $conversion->as_array( array(
					'source_item_name' => array( 'type' => 'string' ),
					'source_item_description' => array( 'type' => 'string' ),
					'target_item_name' => array( 'type' => 'string' ),
					'target_item_description' => array( 'type' => 'string' ) ) );
			}

			return $conversions_array;
		}

		return $conversions;
	}

	public function get_tvm_readings( $params = array() )
	{
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$order = param( $params, 'order', 'tvmr.tvmr_datetime DESC, tvmr.tvmr_shift_id DESC' );
		$format = param( $params, 'format', 'object' );

		$business_date = param( $params, 'date' );
		$shift = param( $params, 'shift' );
		$machine_id = param( $params, 'machine_id' );

		$ci =& get_instance();
		$ci->load->library( 'tvm_reading' );

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}

		if( $order )
		{
				$ci->db->order_by( $order );
		}

		if( $business_date )
		{
			$ci->db->where( 'DATE(tvmr.tvmr_datetime)', $business_date );
		}

		if( $shift )
		{
			$ci->db->where( 'tvmr.tvmr_shift_id', $shift );
		}

		if( $machine_id )
		{
			$ci->db->where( 'tvmr.tvmr_machine_id', $machine_id );
		}

		$ci->db->select( 'tvmr.*, s.shift_num, u.full_name as cashier_name' );
		$ci->db->where( 'tvmr.tvmr_store_id', $this->id );
		$ci->db->join( 'shifts s', 's.id = tvmr.tvmr_shift_id', 'left' );
		$ci->db->join( 'users u', 'u.id = tvmr.tvmr_cashier_id', 'left' );
		$readings = $ci->db->get( 'tvm_readings tvmr' );
		$readings = $readings->result( 'Tvm_reading' );

		if( $format == 'array' )
		{
			$readings_array = array();
			foreach( $readings as $reading )
			{
				$readings_array[] = $reading->as_array();
			}

			return $readings_array;
		}

		return $readings;
	}


	public function get_shift_detail_cash_reports( $params = array() )
	{
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$order = param( $params, 'order', 'sdcr.sdcr_business_date DESC, sdcr.sdcr_login_time DESC, sdcr.id DESC' );
		$format = param( $params, 'format', 'object' );

		$business_date = param( $params, 'date' );
		$shift = param( $params, 'shift' );
		$teller_id = param( $params, 'teller_id' );
		$pos_id = param( $params, 'pos_id' );

		$ci =& get_instance();
		$ci->load->library( 'shift_detail_cash_report' );

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}

		if( $order )
		{
				$ci->db->order_by( $order );
		}

		if( $business_date )
		{
			$ci->db->where( 'sdcr.sdcr_business_date', $business_date );
		}

		if( $shift )
		{
			$ci->db->where( 'sdcr.sdcr_shift_id', $shift );
		}

		if( $teller_id )
		{
			$ci->db->where( 'sdcr.sdcr_teller_id', $machine_id );
		}

		if( $pos_id )
		{
			$ci->db->where( 'sdcr.sdcr_pos_id', $pos_id );
		}

		$ci->db->select( 'sdcr.*, s.shift_num' );
		$ci->db->where( 'sdcr.sdcr_store_id', $this->id );
		$ci->db->join( 'shifts s', 's.id = sdcr.sdcr_shift_id', 'left' );
		$reports = $ci->db->get( 'shift_detail_cash_reports sdcr' );
		$reports = $reports->result( 'Shift_detail_cash_report' );

		if( $format == 'array' )
		{
			$reports_array = array();
			foreach( $reports as $report )
			{
				$reports_array[] = $report->as_array();
			}

			return $reports_array;
		}

		return $reports;
	}


	public function count_transactions( $params = array() )
	{
		$ci =& get_instance();
		$ci->load->library( 'transaction' );

		$business_date = param( $params, 'date' );
		$shift = param( $params, 'shift' );
		$item_id = param( $params, 'item' );
		$transaction_type = param( $params, 'type' );

		$ci->db->select( 't.id' );
		$ci->db->join( 'store_inventory si', 'si.id = t.store_inventory_id' );
		$ci->db->join( 'items i', 'i.id = si.item_id' );
		$ci->db->join( 'shifts s', 's.id = t.transaction_shift' );
		$ci->db->where( 'si.store_id', intval( $this->id ) );

		if( $business_date )
		{
			$ci->db->where( 'transaction_datetime >=', $business_date.' 00:00:00' );
			$ci->db->where( 'transaction_datetime <=', $business_date.' 23:59:59' );
		}

		if( $shift )
		{
			$ci->db->where( 'transaction_shift', $shift );
		}

		if( $item_id )
		{
			$ci->db->where( 'i.id', $item_id );
		}

		if( $transaction_type )
		{
			$ci->db->where( 'transaction_type', $transaction_type );
		}

		$count = $ci->db->count_all_results( 'transactions t' );

		return $count;
	}

	public function count_transfers( $params = array() )
	{
		$ci =& get_instance();
		$ci->load->library( 'Transfer' );

		$business_date = param( $params, 'date' );
		$destination = param( $params, 'dst' );
		$status = param( $params, 'status' );

		if( $business_date )
		{
			$ci->db->where( 'DATE(transfer_datetime)', $business_date );
		}

		if( $destination )
		{
			if( $destination == '_ext_' )
			{
				$ci->db->where( 'destination_id IS NULL' );
				$ci->db->where( 'destination_name IS NOT NULL' );
			}
			else
			{
				$ci->db->where( 'destination_id', $destination );
			}
		}

		if( $status )
		{
			$ci->db->where( 'transfer_status', $status );
		}

		$ci->db->where( 'origin_id', $this->id );
		$count = $ci->db->count_all_results( 'transfers t' );

		return $count;
	}

	public function count_receipts( $params = array() )
	{
		$receipt_date = param( $params, 'date' );
		$source = param( $params, 'src' );
		$status = param( $params, 'status' );

		$ci =& get_instance();
		$ci->load->library( 'transfer' );

		// Do not show pending or scheduled transfers
		$available_status = array( TRANSFER_APPROVED, TRANSFER_RECEIVED, TRANSFER_PENDING_CANCELLED );

		$ci->db->select( 't.*' );
		if( $receipt_date )
		{
			$ci->db->where( "(DATE(receipt_datetime) = '${receipt_date}' OR receipt_datetime IS NULL )");
		}

		if( $source )
		{
			if( $source == '_ext_' )
			{
				$ci->db->where( 'origin_id IS NULL' );
				$ci->db->where( 'origin_name IS NOT NULL' );
			}
			else
			{
				$ci->db->where( 'origin_id', $source );
			}
		}

		if( $status )
		{
			$ci->db->where( 'transfer_status', $status );
		}

		$ci->db->where_in( 'transfer_status', $available_status );
		$ci->db->where( 'destination_id', $this->id );
		$ci->db->join( 'stores s', 's.id = t.origin_id', 'left' );

		$count = $ci->db->count_all_results( 'transfers t' );

		return $count;
	}

	public function count_adjustments( $params = array() )
	{
		$adjustment_date = param( $params, 'date' );
		$item_id = param( $params, 'item' );
		$status = param( $params, 'status' );

		$ci =& get_instance();

		if( $adjustment_date )
		{
			$ci->db->where( 'DATE(adjustment_timestamp)', $adjustment_date );
		}

		if( $item_id )
		{
			$ci->db->where( 'si.item_id', $item_id );
		}

		if( $status )
		{
			$ci->db->where( 'adjustment_status', $status );
		}

		$ci->db->where( 'si.store_id', $this->id );
		$ci->db->join( 'store_inventory si', 'si.id = a.store_inventory_id', 'left' );
		$count = $ci->db->count_all_results( 'adjustments a' );

		return $count;
	}

	public function count_collections( $params = array() )
	{
		$processing_date = param( $params, 'processing_date' );
		$business_date = param( $params, 'business_date' );

		$ci =& get_instance();

		if( $processing_date )
		{
			$ci->db->where( 'DATE(processing_datetime)', $processing_date );
		}

		if( $business_date )
		{
			$ci->db->where( 'business_date', $business_date );
		}

		$ci->db->where( 'store_id', $this->id );

		$count = $ci->db->count_all_results( 'mopping' );

		return $count;
	}

	public function count_allocations( $params = array() )
	{
		$allocation_date = param( $params, 'date' );
		$assignee_type = param( $params, 'assignee_type' );
		$status = param( $params, 'status' );

		$ci =& get_instance();

		if( $allocation_date )
		{
			$ci->db->where( 'business_date', $allocation_date );
		}

		if( $assignee_type )
		{
			$ci->db->where( 'assignee_type', $assignee_type );
		}

		if( $status )
		{
			$ci->db->where( 'allocation_status', $status );
		}

		$ci->db->where( 'store_id', $this->id );

		$count = $ci->db->count_all_results( 'allocations' );

		return $count;
	}

	public function count_conversions( $params = array() )
	{
		$conversion_date = param( $params, 'date' );
		$input_item_id = param( $params, 'input' );
		$output_item_id = param( $params, 'output' );

		$ci =& get_instance();
		$ci->load->library( 'conversion' );

		if( $conversion_date )
		{
			$ci->db->where( 'DATE(c.conversion_datetime)', $conversion_date );
		}

		if( $input_item_id )
		{
			$ci->db->where( 'si.item_id', $input_item_id );
		}

		if( $output_item_id )
		{
			$ci->db->where( 'ti.item_id', $output_item_id );
		}

		$ci->db->select( 'c.*, src_item.item_name AS source_item_name, src_item.item_description AS source_item_description,
						tgt_item.item_name AS target_item_name, tgt_item.item_description AS target_item_description' );
		$ci->db->where( 'c.store_id', $this->id );
		$ci->db->join( 'store_inventory si', 'si.id = c.source_inventory_id', 'left' );
		$ci->db->join( 'store_inventory ti', 'ti.id = c.target_inventory_id', 'left' );
		$ci->db->join( 'items src_item', 'src_item.id = si.item_id', 'left' );
		$ci->db->join( 'items tgt_item', 'tgt_item.id = ti.item_id', 'left' );
		$count = $ci->db->count_all_results( 'conversions c' );

		return $count;
	}

	public function count_pending_transfers( $params = array() )
	{
		$business_date = param( $params, 'date' );
		$destination = param( $params, 'dst' );

		$ci =& get_instance();

		if( $business_date )
		{
			$ci->db->where( 'DATE(transfer_datetime)', $business_date );
		}

		if( $destination )
		{
			if( $destination == '_ext_' )
			{
				$ci->db->where( 'destination_id IS NULL' );
				$ci->db->where( 'destination_name IS NOT NULL' );
			}
			else
			{
				$ci->db->where( 'destination_id', $destination );
			}
		}

		$ci->db->where( 'transfer_status', TRANSFER_PENDING );
		$ci->db->where( 'origin_id', $this->id );
		$ci->db->join( 'stores s', 's.id = t.origin_id', 'left' );
		$count = $ci->db->count_all_results( 'transfers t' );

		return $count;
	}

	public function count_pending_receipts( $params = array() )
	{
		$receipt_date = param( $params, 'date' );
		$source = param( $params, 'src' );
		$status = param( $params, 'status' );

		$ci =& get_instance();

		if( $receipt_date )
		{
			$ci->db->where( "(DATE(receipt_datetime) = '${receipt_date}' OR receipt_datetime IS NULL )");
		}

		if( $source )
		{
			if( $source == '_ext_' )
			{
				$ci->db->where( 'origin_id IS NULL' );
				$ci->db->where( 'origin_name IS NOT NULL' );
			}
			else
			{
				$ci->db->where( 'origin_id', $source );
			}
		}

		if( $status )
		{
			$ci->db->where( 'transfer_status', $status );
		}

		$ci->db->where( 'transfer_status', TRANSFER_APPROVED );
		$ci->db->where( 'destination_id', $this->id );
		$ci->db->join( 'stores s', 's.id = t.origin_id', 'left' );
		$count = $ci->db->count_all_results( 'transfers t' );

		return $count;
	}

	public function count_pending_adjustments( $params = array() )
	{
		$adjustment_date = param( $params, 'date' );
		$item_id = param( $params, 'item' );
		$status = param( $params, 'status' );

		$ci =& get_instance();

		if( $adjustment_date )
		{
			$ci->db->where( 'DATE(adjustment_timestamp)', $adjustment_date );
		}

		if( $item_id )
		{
			$ci->db->where( 'si.item_id', $item_id );
		}

		$ci->db->where( 'si.store_id', $this->id );
		$ci->db->where( 'a.adjustment_status', ADJUSTMENT_PENDING );
		$ci->db->join( 'store_inventory si', 'si.id = a.store_inventory_id', 'left' );
		$count = $ci->db->count_all_results( 'adjustments a' );

		return $count;
	}

	public function count_pending_allocations( $params = array() )
	{
		$allocation_date = param( $params, 'date' );
		$assignee_type = param( $params, 'assignee_type' );

		$ci =& get_instance();

		if( $allocation_date )
		{
			$ci->db->where( 'business_date', $allocation_date );
		}

		if( $assignee_type )
		{
			$ci->db->where( 'assignee_type', $assignee_type );
		}

		$ci->db->where( 'allocation_status', ALLOCATION_SCHEDULED );
		$ci->db->where( 'store_id', $this->id );
		$count = $ci->db->count_all_results( 'allocations' );

		return $count;
	}

	public function count_pending_conversions( $params = array() )
	{
		$conversion_date = param( $params, 'date' );
		$input_item_id = param( $params, 'input' );
		$output_item_id = param( $params, 'output' );

		$ci =& get_instance();
		$ci->load->library( 'conversion' );


		if( $conversion_date )
		{
			$ci->db->where( 'DATE(c.conversion_datetime)', $conversion_date );
		}

		if( $input_item_id )
		{
			$ci->db->where( 'si.item_id', $input_item_id );
		}

		if( $output_item_id )
		{
			$ci->db->where( 'ti.item_id', $output_item_id );
		}

		$ci->db->join( 'store_inventory si', 'si.id = c.source_inventory_id', 'left' );
		$ci->db->join( 'store_inventory ti', 'ti.id = c.target_inventory_id', 'left' );
		$ci->db->join( 'items src_item', 'src_item.id = si.item_id', 'left' );
		$ci->db->join( 'items tgt_item', 'tgt_item.id = ti.item_id', 'left' );

		$ci->db->where( 'conversion_status', CONVERSION_PENDING );
		$ci->db->where( 'c.store_id', $this->id );
		$count = $ci->db->count_all_results( 'conversions c' );

		return $count;
	}

	public function count_tvm_readings( $params = array() )
	{
		$business_date = param( $params, 'date' );
		$shift = param( $params, 'shift' );
		$machine_id = param( $params, 'machine_id' );

		$ci =& get_instance();
		$ci->load->library( 'tvm_reading' );

		if( $business_date )
		{
			$ci->db->where( 'DATE(tvmr.tvmr_datetime)', $business_date );
		}

		if( $shift )
		{
			$ci->db->where( 'tvmr.tvmr_shift_id', $shift );
		}

		if( $machine_id )
		{
			$ci->db->where( 'tvmr.tvmr_machine_id', $machine_id );
		}

		$ci->db->where( 'tvmr.tvmr_store_id', $this->id );

		$count = $ci->db->count_all_results( 'tvm_readings tvmr' );

		return $count;
	}


	public function count_shift_detail_cash_reports( $params = array() )
	{
		$business_date = param( $params, 'date' );
		$shift = param( $params, 'shift' );
		$teller_id = param( $params, 'teller_id' );
		$pos_id = param( $params, 'pos_id' );

		$ci =& get_instance();
		$ci->load->library( 'shift_detail_cash_report' );

		if( $business_date )
		{
			$ci->db->where( 'sdcr.sdcr_business_date', $business_date );
		}

		if( $shift )
		{
			$ci->db->where( 'sdcr.sdcr_shift_id', $shift );
		}

		if( $teller_id )
		{
			$ci->db->where( 'sdcr.sdcr_teller_id', $teller_id );
		}

		if( $pos_id )
		{
			$ci->db->where( 'sdcr.sdcr_pos_id', $pos_id );
		}

		$ci->db->where( 'sdcr.sdcr_store_id', $this->id );

		$count = $ci->db->count_all_results( 'shift_detail_cash_reports sdcr' );

		return $count;
	}


	public function get_transactions_date_range( $start_time = NULL, $end_time = NULL, $params = array() )
	{
		$start_time = param_type( $start_time, 'datetime', date( TIMESTAMP_FORMAT, strtotime( 'now - 1 day' ) ) );
		$end_time = param_type( $end_time, 'datetime', date( TIMESTAMP_FORMAT ) );
		$items = param( $params, 'item' ); // future filter

		$ci =& get_instance();

		$params = array();
		$sql = 'SELECT
					i.id AS item_id,
					i.item_name AS item_name,
					UNIX_TIMESTAMP( t.transaction_timestamp - INTERVAL SECOND(t.transaction_timestamp) SECOND ) AS timestamp,
					t.transaction_quantity AS quantity,
					t.current_quantity AS balance
				FROM transactions t
				LEFT JOIN transactions t0
				ON t0.store_inventory_id = t.store_inventory_id
					AND ( t0.transaction_timestamp - INTERVAL SECOND(t0.transaction_timestamp) SECOND ) = ( t.transaction_timestamp - INTERVAL SECOND(t.transaction_timestamp) SECOND )
					AND t0.id > t.id
				LEFT JOIN store_inventory si
					ON si.id = t.store_inventory_id
				LEFT JOIN items i
					ON i.id = si.item_id
				WHERE t0.id IS NULL';

		if( $start_time )
		{
				$sql .= " AND t.transaction_timestamp >= ?";
				$params[] = $start_time;
		}

		if( $start_time )
		{
				$sql .= " AND t.transaction_timestamp <= ?";
				$params[] = $end_time;
		}

		$sql .= " AND si.store_id = ?";
		$params[] = $this->id;

		$sql .= ' ORDER BY t.id ASC';

		$data = $ci->db->query( $sql, $params );

		return $data->result_array();
	}

	public function get_shift_balance( $date = NULL, $shift_id = NULL )
	{
		$ci =& get_instance();

		$ci->load->library( 'shift_turnover' );
		if( empty( $date ) )
		{
			$date = date( DATE_FORMAT );
		}

		if( empty( $shift_id ) )
		{
			$shift_id = current_shift( TRUE );
		}

		if( $date && $shift_id )
		{
			$ci =& get_instance();

			$ci->db->where( 'st_store_id', $this->id );
			$ci->db->where( 'st_from_date', $date );
			$ci->db->where( 'st_from_shift_id', $shift_id );
			$query = $ci->db->get( 'shift_turnovers' );

			return $query->row( 0, 'Shift_turnover' );
		}
		else
		{
			return NULL;
		}
	}

	public function get_inventory_movement( $date = NULL, $shift = NULL )
	{
		if( empty( $date ) )
		{
			$date = date( DATE_FORMAT );
		}

		$ci =& get_instance();

		$ci->db->select( 't.store_inventory_id' );
		$ci->db->select_sum( 't.transaction_quantity', 'movement' );
		$ci->db->join( 'store_inventory AS si', 'si.id = t.store_inventory_id', 'left' );
		$ci->db->where( 'si.store_id', $this->id );

		if( $date )
		{
			$ci->db->where( 't.transaction_datetime >=', $date.' 00:00:00' );
			$ci->db->where( 't.transaction_datetime <=', $date.' 23:59:59' );
		}

		if( $shift )
		{
			$ci->db->where( 't.transaction_shift', $shift );
		}

		$ci->db->group_by( 't.store_inventory_id' );

		$query = $ci->db->get( 'transactions AS t' );

		$items = $query->result_array();

		$inventory_movement = array();
		foreach( $items as $item )
		{
			$inventory_movement[$item['store_inventory_id']] = param_type( $item['movement'], 'integer' );
		}

		return $inventory_movement;
	}

	public function get_shift_turnovers( $params = array() )
	{
		$ci =& get_instance();

		$start_date = param( $params, 'start' );
		$end_date = param( $params, 'end' );
		$shift = param( $params, 'shift' );

		if( empty( $start_date ) )
		{
			$start_date = date( DATE_FORMAT, strtotime( 'first day of this month' ) );
		}
		if( empty( $end_date ) )
		{
			$end_date = date( DATE_FORMAT );
		}

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );

		if( $shift )
		{
			$store_shifts = array( $shift );
		}
		else
		{
			$shifts = $this->get_shifts();
			$store_shifts = array();
			foreach( $shifts as $loop_shift )
			{
				$store_shifts[] = param_type( $loop_shift->get( 'id' ), 'integer' );
			}
		}


		$sql = "SELECT
					? AS st_store_id,
					x.dt AS st_from_date,
					fs.description,
					x.shift_id AS st_from_shift_id,
					x.st_to_date,
					x.st_to_shift_id,
					x.st_status,
					x.st_start_user_id,
					su.full_name AS start_user,
					eu.full_name AS end_user,
					x.shift_order,
					SUM( issue_count ) AS has_issues
				FROM (
					SELECT
						dt,
						s.id AS shift_id,
						s.shift_order,
						st.st_to_date,
						st.st_to_shift_id,
						st.st_status,
						st.st_start_user_id,
						st.st_end_user_id,
						sti.sti_inventory_id,
						sti.sti_beginning_balance,
						m.movement,
						sti.sti_ending_balance,
						IF( ( sti.sti_beginning_balance + COALESCE( m.movement, 0 ) ) = sti.sti_ending_balance, 0, 1 ) AS issue_count
					FROM dates AS d
					CROSS JOIN shifts AS s
					LEFT JOIN shift_turnovers AS st
						ON st.st_from_date = d.dt AND st.st_from_shift_id = s.id AND st.st_store_id = ?
					LEFT JOIN shift_turnover_items AS sti
						ON sti.sti_turnover_id = st.id
					LEFT JOIN (
						SELECT
							store_inventory_id,
							DATE( transaction_datetime ) AS business_date,
							transaction_shift,
							SUM( transaction_quantity ) AS movement
						FROM transactions
						LEFT JOIN store_inventory
							ON store_inventory.id = transactions.store_inventory_id
						WHERE
							store_inventory.store_id = ?
							AND transaction_datetime >= ?
							AND transaction_datetime <= ?
						GROUP BY
							store_inventory_id, DATE( transaction_datetime ), transaction_shift
					) AS m
						ON m.store_inventory_id = sti.sti_inventory_id AND m.business_date = st.st_from_date AND m.transaction_shift = st.st_from_shift_id
					WHERE dt BETWEEN ? AND ?
						AND s.id IN ?
				) AS x
				LEFT JOIN shifts AS fs
					ON fs.id = x.shift_id
				LEFT JOIN shifts AS ts
					ON ts.id = x.st_to_shift_id
				LEFT JOIN users AS su
					ON su.id = x.st_start_user_id
				LEFT JOIN users AS eu
					ON eu.id = x.st_end_user_id
				GROUP BY x.dt, x.shift_id, x.st_to_date, x.st_to_shift_id, x.st_status, x.st_start_user_id, x.st_end_user_id,  x.shift_order
				ORDER BY x.dt DESC, x.shift_order DESC";

		$sql_params = array( $this->id, $this->id, $this->id, $start_date.' 00:00:00', $end_date.' 23:59:59', $start_date, $end_date, $store_shifts );

		if( $limit )
		{
			$sql .= ' LIMIT ?, ?';
			$sql_params[] = ( $page ? ( ( $page - 1 ) * $limit ) : 0 );
			$sql_params[] = $limit;
		}

		$query = $ci->db->query( $sql, $sql_params );

		return $query->result_array();
	}

	public function count_shift_turnovers( $params = array() )
	{
		$ci =& get_instance();

		$start_date = param( $params, 'start' );
		$end_date = param( $params, 'end' );

		if( empty( $start_date ) )
		{
			$start_date = date( DATE_FORMAT, strtotime( 'first day of this month' ) );
		}
		if( empty( $end_date ) )
		{
			$end_date = date( DATE_FORMAT );
		}

		$shift = param( $params, 'shift' );

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );

		$shifts = $this->get_shifts();
		$store_shifts = array();
		foreach( $shifts as $loop_shift )
		{
			$store_shifts[] = param_type( $loop_shift->get( 'id' ), 'integer' );
		}

		$sql = 'SELECT COUNT(*) AS numrows
				FROM dates AS d
				CROSS JOIN shifts AS s
				WHERE
					d.dt BETWEEN ? AND ?
					AND s.id IN ?';

		$sql_params = array( $start_date, $end_date, $store_shifts );
		$query = $ci->db->query( $sql, $sql_params );
		$count = intval( $query->row( 0 )->numrows );

		return $count;
	}

	public function get_inventory_balances( $date = NULL, $params = array() )
	{
		$date = param_type( $date, 'datetime', date( TIMESTAMP_FORMAT ) );
		$items = param( $params, 'item' );

		$ci =& get_instance();

		$sql_params = array();
		$sql = 'SELECT
					i.id AS item_id,
					i.item_name,
					x.last_update,
					x.balance
				FROM store_inventory si
				LEFT JOIN items i
					ON i.id = si.item_id
				LEFT JOIN (
					SELECT
						si.id,
						t.store_inventory_id,
						UNIX_TIMESTAMP( t.transaction_timestamp ) AS last_update,
						t.current_quantity AS balance
					FROM transactions t
					LEFT JOIN transactions t0
						ON t0.store_inventory_id = t.store_inventory_id
							AND t0.id > t.id';
		if( $date )
		{
			$sql .= ' AND t0.transaction_timestamp <= ?';
			$sql_params[] = $date;
		}

		$sql .= ' LEFT JOIN store_inventory si
					ON si.id = t.store_inventory_id
				WHERE t0.id IS NULL';

		if( $date )
		{
				$sql .= ' AND t.transaction_timestamp <= ?';
				$sql_params[] = $date;
		}

		$sql .= '	AND si.store_id = ?
						) AS x
							ON x.id = si.id
						WHERE si.store_id = ?';
		$sql_params[] = $this->id;
		$sql_params[] = $this->id;

		if( $items )
		{
				$sql_params .= ' AND i.id in ('.implode( ', ', $items ).')';
		}

		$data = $ci->db->query( $sql, $sql_params );

		return $data->result_array();
	}


	// Stock Replenishment Receipts and Transfer Ins
	public function get_delivery_summary( $date = NULL, $shift = NULL )
	{
		$ci =& get_instance();

		$ci->db->select( 'ti.item_id, i.item_name, i.item_description, i.item_group, i.item_type, i.item_unit, i.base_item_id,
				ti.transfer_item_category_id AS category_id, ti.quantity_received, t.transfer_category,
				IF( ct.conversion_factor IS NULL, ti.quantity_received, ti.quantity_received * ct.conversion_factor ) AS base_quantity' );
		$ci->db->join( 'transfers t', 't.id = ti.transfer_id', 'left' );
		$ci->db->join( 'items i', 'i.id = ti.item_id', 'left' );
		$ci->db->join( 'items bi', 'bi.id = i.base_item_id', 'left' );
		$ci->db->join( 'conversion_table ct', 'ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id', 'left' );
		$ci->db->where( 't.destination_id', $this->id );
		$ci->db->where( 't.recipient_shift', $shift );
		$ci->db->where( 'DATE( t.receipt_datetime ) =', $date );
		$ci->db->where( 'ti.transfer_item_status', TRANSFER_ITEM_RECEIVED );

		$query = $ci->db->get( 'transfer_items ti' );

		return $query->result_array();
	}

	// Transfer Outs and Ticket Turnovers
	public function get_transfer_summary( $date = NULL, $shift = NULL )
	{
		$ci =& get_instance();

		$ci->db->select( 'ti.item_id, i.item_name, i.item_description, i.item_group, i.item_type, i.item_unit, i.base_item_id,
				ti.transfer_item_category_id AS category_id, ti.quantity_received, t.transfer_category,
				IF( ct.conversion_factor IS NULL, ti.quantity_received, ti.quantity_received * ct.conversion_factor ) AS base_quantity' );
		$ci->db->join( 'transfers t', 't.id = ti.transfer_id', 'left' );
		$ci->db->join( 'items i', 'i.id = ti.item_id', 'left' );
		$ci->db->join( 'items bi', 'bi.id = i.base_item_id', 'left' );
		$ci->db->join( 'conversion_table ct', 'ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id', 'left' );
		$ci->db->where( 't.origin_id', $this->id );
		$ci->db->where( 't.sender_shift', $shift );
		$ci->db->where( 'DATE( t.transfer_datetime ) =', $date );
		$ci->db->where_in( 'ti.transfer_item_status', array( TRANSFER_ITEM_APPROVED, TRANSFER_ITEM_RECEIVED ) );

		$query = $ci->db->get( 'transfer_items ti' );

		return $query->result_array();
	}

	// Allocations and Remittances
	public function get_remittance_summary( $date = NULL, $shift = NULL )
	{
		$ci =& get_instance();

		$ci->db->select( 'ai.allocated_item_id AS item_id, i.item_name, i.item_description, i.item_type, i.item_group, i.item_unit, i.base_item_id,
				ai.allocation_item_type, ai.cashier_shift_id, ai.allocation_category_id AS category_id, ai.allocated_quantity, a.assignee_type, a.shift_id,
				IF( ct.conversion_factor IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor ) AS base_quantity' );
		$ci->db->join( 'allocations a', 'a.id = ai.allocation_id', 'left' );
		$ci->db->join( 'items i', 'i.id = ai.allocated_item_id', 'left' );
		$ci->db->join( 'items bi', 'bi.id = i.base_item_id', 'left' );
		$ci->db->join( 'conversion_table ct', 'ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id', 'left' );
		$ci->db->where( 'a.store_id', $this->id );
		$ci->db->where( 'a.business_date', $date );
		$ci->db->where( 'ai.cashier_shift_id', $shift );
		$query = $ci->db->get( 'allocation_items ai' );

		return $query->result_array();
	}

	public function get_ticket_breakdown( $date = NULL, $shift_id = NULL )
	{
		if( is_null( $date ) )
		{
			$date = date( DATE_FORMAT );
		}

		if( is_null( $shift_id ) )
		{
			return FALSE;
		}

		$ci =& get_instance();

		$ci->load->library( 'shift_turnover' );
		$ci->load->library( 'item' );

		$Shift_turnover = new Shift_turnover();
		$Item = new Item();

		// Shift turnover record
		$shift_turnover = $Shift_turnover->get_by_store_date_shift( $this->id, $date, $shift_id );
		if( $shift_turnover )
		{
			$turnover_items = $shift_turnover->get_items( TRUE );

			$data = array(
					'beginning' => array(),
					'ending' => array()
				);
			foreach( $turnover_items as $turnover_item )
			{
				if( intval( $turnover_item->get( 'sti_ending_balance' ) ) === 0 )
				{
					continue;
				}

				$item = $Item->get_by_id( $turnover_item->get( 'id' ) );
				$data['beginning'][] = array(
					'name' => $turnover_item->get( 'item_name' ),
					'description' => $turnover_item->get( 'item_description' ),
					'group' => $turnover_item->get( 'item_group' ),
					'type' => $turnover_item->get( 'item_type' ),
					'quantity' => $turnover_item->get( 'sti_beginning_balance' ),
					'base_quantity' => $turnover_item->get( 'base_beginning_balance' ) );

				$data['ending'][] = array(
					'name' => $turnover_item->get( 'item_name' ),
					'description' => $turnover_item->get( 'item_description' ),
					'group' => $turnover_item->get( 'item_group' ),
					'type' => $turnover_item->get( 'item_type' ),
					'quantity' => $turnover_item->get( 'sti_ending_balance' ),
					'base_quantity' => $turnover_item->get( 'base_ending_balance' ) );
			}

			return $data;
		}
		else
		{
			return FALSE;
		}
	}

	public function get_turnover_summary( $date = NULL, $shift_id = NULL )
	{
		$ci =& get_instance();

		$store_id = $this->id;

		// Beginning Balance
		$sql = "SELECT *
						FROM (
							SELECT
								CAST( 1 AS UNSIGNED ) AS row_num,
								'Beginning Balance' AS trans_group,
								'Beginning Balance' AS description,
								SUM( IF( item_group = 'sjt' AND item_type = 1, base_quantity, 0 ) ) AS sjt,
								SUM( IF( item_group = 'sjt' AND item_type = 0, base_quantity, 0 ) ) AS sjt_defect,
								SUM( IF( item_group = 'svc' AND item_type = 1, base_quantity, 0 ) ) AS svc,
								SUM( IF( item_group = 'svc' AND item_type = 0, base_quantity, 0 ) ) AS svc_defect,
								SUM( IF( item_group = 'concessionary' AND item_type = 1, base_quantity, 0 ) ) AS concessionary,
								SUM( IF( item_group = 'concessionary' AND item_type = 0, base_quantity, 0 ) ) AS concessionary_defect
							FROM (
								SELECT
									i.item_group, i.item_type, i.item_unit,
									IF( ct.conversion_factor IS NULL, sti.sti_beginning_balance, sti.sti_beginning_balance * ct.conversion_factor ) AS base_quantity
								FROM shift_turnover_items AS sti
								LEFT JOIN shift_turnovers AS st
									ON st.id = sti.sti_turnover_id
								LEFT JOIN items AS i
									ON i.id = sti.sti_item_id
								LEFT JOIN items AS bi
									ON bi.id = i.base_item_id
								LEFT JOIN conversion_table AS ct
									ON ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id
								WHERE
									st.st_store_id = ${store_id}
									AND st.st_from_date = '${date}'
									AND st.st_from_shift_id = ${shift_id}
							) AS a

							UNION ALL

							SELECT
								CAST( 100 AS UNSIGNED ),
								'Ending Balance' AS trans_group,
								'Balance per Book' AS description,
								SUM( IF( item_group = 'sjt' AND item_type = 1, base_quantity, 0 ) ) AS sjt,
								SUM( IF( item_group = 'sjt' AND item_type = 0, base_quantity, 0 ) ) AS sjt_defect,
								SUM( IF( item_group = 'svc' AND item_type = 1, base_quantity, 0 ) ) AS svc,
								SUM( IF( item_group = 'svc' AND item_type = 0, base_quantity, 0 ) ) AS svc_defect,
								SUM( IF( item_group = 'concessionary' AND item_type = 1, base_quantity, 0 ) ) AS concessionary,
								SUM( IF( item_group = 'concessionary' AND item_type = 0, base_quantity, 0 ) ) AS concessionary_defect
							FROM (
								SELECT
									i.item_group, i.item_type, i.item_unit,
									IF( ct.conversion_factor IS NULL, sti.sti_ending_balance, sti.sti_ending_balance * ct.conversion_factor ) AS base_quantity
								FROM shift_turnover_items AS sti
								LEFT JOIN shift_turnovers AS st
									ON st.id = sti.sti_turnover_id
								LEFT JOIN items AS i
									ON i.id = sti.sti_item_id
								LEFT JOIN items AS bi
									ON bi.id = i.base_item_id
								LEFT JOIN conversion_table AS ct
									ON ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id
								WHERE
									st.st_store_id = ${store_id}
									AND st.st_from_date = '${date}'
									AND st.st_from_shift_id = ${shift_id}
							) AS a

							UNION ALL

							SELECT
								CAST( row_num AS UNSIGNED ),
								trans_group,
								description,
								SUM( IF( item_group = 'sjt' AND item_type = 1, base_quantity, 0 ) ) AS sjt,
								SUM( IF( item_group = 'sjt' AND item_type = 0, base_quantity, 0 ) ) AS sjt_defect,
								SUM( IF( item_group = 'svc' AND item_type = 1, base_quantity, 0 ) ) AS svc,
								SUM( IF( item_group = 'svc' AND item_type = 0, base_quantity, 0 ) ) AS svc_defect,
								SUM( IF( item_group = 'concessionary' AND item_type = 1, base_quantity, 0 ) ) AS concessionary,
								SUM( IF( item_group = 'concessionary' AND item_type = 0, base_quantity, 0 ) ) AS concessionary_defect
							FROM (
								SELECT
									CASE
										WHEN i.item_unit IN ( 'magazine' ) THEN IF( t.transfer_category = 4, 10, 40 )
										WHEN i.item_unit IN ( 'box' ) THEN IF( t.transfer_category = 4, 11, 41 )
										ELSE IF( t.transfer_category = 4, 12, 42 )
									END AS row_num,
									IF( t.transfer_category = 4, 'Add: Delivery', 'Add: Ticket Transfer' ) AS trans_group,
									CASE
										WHEN i.item_unit IN ( 'magazine' ) THEN 'Magazine'
										WHEN i.item_unit IN ( 'box' ) THEN 'Rigid Box'
										ELSE 'Piece'
									END AS description,
									ti.item_id, i.item_name, i.item_group, i.item_type, i.item_unit,
									t.transfer_category, ti.quantity_received,
									IF( ct.conversion_factor IS NULL, ti.quantity_received, ti.quantity_received * ct.conversion_factor ) AS base_quantity
								FROM transfer_items AS ti
								LEFT JOIN transfers AS t
									ON t.id = ti.transfer_id
								LEFT JOIN items AS i
									ON i.id = ti.item_id
								LEFT JOIN conversion_table AS ct
									ON ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id
								WHERE
									t.destination_id = ${store_id}
									AND t.recipient_shift = ${shift_id}
									AND DATE( t.receipt_datetime) = '${date}'
									AND ti.transfer_item_status = 3
							) AS a
							GROUP BY trans_group, description

							UNION ALL

							SELECT
									CAST( row_num AS UNSIGNED ),
									trans_group,
									description,
									SUM( IF( item_group = 'sjt' AND item_type = 1, base_quantity, 0 ) ) AS sjt,
									SUM( IF( item_group = 'sjt' AND item_type = 0, base_quantity, 0 ) ) AS sjt_defect,
									SUM( IF( item_group = 'svc' AND item_type = 1, base_quantity, 0 ) ) AS svc,
									SUM( IF( item_group = 'svc' AND item_type = 0, base_quantity, 0 ) ) AS svc_defect,
									SUM( IF( item_group = 'concessionary' AND item_type = 1, base_quantity, 0 ) ) AS concessionary,
									SUM( IF( item_group = 'concessionary' AND item_type = 0, base_quantity, 0 ) ) AS concessionary_defect
								FROM (
									SELECT
										CASE
											WHEN t.transfer_category = 3 THEN 80
											WHEN i.item_unit IN ( 'magazine' ) THEN 70
											WHEN i.item_unit IN ( 'box' ) THEN 71
											ELSE 72
										END AS row_num,
										IF( t.transfer_category = 3, 'Less: Returned to AFCS', 'Less: Ticket Transfer' ) AS trans_group,
										CASE
											WHEN t.transfer_category = 3 THEN 'Less: Returned to AFCS'
											WHEN i.item_unit IN ( 'magazine' ) THEN 'Magazine'
											WHEN i.item_unit IN ( 'box' ) THEN 'Rigid Box'
											ELSE 'Loose'
										END AS description,
										ti.item_id, i.item_name, i.item_group, i.item_type, i.item_unit,
										t.transfer_category, ti.quantity_received,
										IF( ct.conversion_factor IS NULL, ti.quantity, ti.quantity * ct.conversion_factor ) AS base_quantity
									FROM transfer_items AS ti
									LEFT JOIN transfers AS t
										ON t.id = ti.transfer_id
									LEFT JOIN items AS i
										ON i.id = ti.item_id
									LEFT JOIN conversion_table AS ct
										ON ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id
									WHERE
										t.origin_id = ${store_id}
										AND t.sender_shift = ${shift_id}
										AND DATE( t.transfer_datetime) = '${date}'
										AND ti.transfer_item_status IN ( 2, 3 )
								) AS a
								GROUP BY trans_group, description

								UNION ALL

								SELECT
									CAST( row_num AS UNSIGNED ),
									trans_group,
									description,
									SUM( IF( item_group = 'sjt' AND item_type = 1, base_quantity, 0 ) ) AS sjt,
									SUM( IF( item_group = 'sjt' AND item_type = 0, base_quantity, 0 ) ) AS sjt_defect,
									SUM( IF( item_group = 'svc' AND item_type = 1, base_quantity, 0 ) ) AS svc,
									SUM( IF( item_group = 'svc' AND item_type = 0, base_quantity, 0 ) ) AS svc_defect,
									SUM( IF( item_group = 'concessionary' AND item_type = 1, base_quantity, 0 ) ) AS concessionary,
									SUM( IF( item_group = 'concessionary' AND item_type = 0, base_quantity, 0 ) ) AS concessionary_defect
								FROM (
									SELECT
										CASE
											WHEN a.assignee_type = 1 AND ai.allocation_item_type = 1 THEN 60
											WHEN a.assignee_type = 1 AND ai.allocation_item_type = 2 THEN IF( i.item_unit = 'piece', 31, 30 )
											WHEN a.assignee_type = 2 AND ai.allocation_item_type = 1 THEN 50
											WHEN a.assignee_type = 2 AND ai.allocation_item_type = 2 THEN 32
											ELSE 'Unknown'
										END AS row_num,
										CASE
											WHEN a.assignee_type = 1 AND ai.allocation_item_type = 1 THEN 'Less: Teller Allocation'
											WHEN a.assignee_type = 1 AND ai.allocation_item_type = 2 THEN 'Add: Teller Remittance'
											WHEN a.assignee_type = 2 AND ai.allocation_item_type = 1 THEN 'Less: TVM Replenishment'
											WHEN a.assignee_type = 2 AND ai.allocation_item_type = 2 THEN 'Add: Loose from TVM'
											ELSE 'Unknown'
										END AS trans_group,
										CASE
											WHEN a.assignee_type = 1 AND ai.allocation_item_type = 1 THEN ts.shift_num
											WHEN a.assignee_type = 1 AND ai.allocation_item_type = 2 THEN IF( i.item_unit = 'piece', 'Loose', 'Sealed' )
											WHEN a.assignee_type = 2 AND ai.allocation_item_type = 1 THEN cs.shift_num
											WHEN a.assignee_type = 2 AND ai.allocation_item_type = 2 THEN 'Return Loose / Reject from TVM'
											ELSE 'Unknown'
										END AS description,
										ai.allocated_item_id, i.item_name, i.item_group, i.item_type, i.item_unit,
										a.assignee_type, a.shift_id,
										ai.allocation_item_type, ai.cashier_shift_id, ai.allocated_quantity,
										IF( ct.conversion_factor IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor ) AS base_quantity
									FROM allocation_items AS ai
									LEFT JOIN allocations AS a
										ON a.id = ai.allocation_id
									LEFT JOIN items AS i
										ON i.id = ai.allocated_item_id
									LEFT JOIN conversion_table AS ct
										ON ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id
									LEFT JOIN shifts AS cs
										ON cs.id = ai.cashier_shift_id
									LEFT JOIN shifts AS ts
										ON ts.id = a.shift_id
									WHERE
										a.store_id = ${store_id}
										AND a.business_date = '${date}'
										AND ai.cashier_shift_id = ${shift_id}
										AND ai.allocation_item_status IN ( 11, 21 )
										AND ai.allocation_item_type IN ( 1, 2 )
								) AS a
								GROUP BY trans_group, description

								UNION ALL

								SELECT
									CAST( 90 AS UNSIGNED ),
									trans_group,
									description,
									SUM( IF( item_group = 'SJT' AND item_type = 1, base_quantity, 0 ) ) AS sjt,
									SUM( IF( item_group = 'SJT' AND item_type = 0, base_quantity, 0 ) ) AS sjt_defect,
									SUM( IF( item_group = 'SVC' AND item_type = 1, base_quantity, 0 ) ) AS svc,
									SUM( IF( item_group = 'SVC' AND item_type = 0, base_quantity, 0 ) ) AS svc_defect,
									SUM( IF( item_group = 'Concessionary' AND item_type = 1, base_quantity, 0 ) ) AS concessionary,
									SUM( IF( item_group = 'Concessionary' AND item_type = 0, base_quantity, 0 ) ) AS concessionary_defect
								FROM (
									SELECT
										IF( adj.adjusted_quantity > adj.previous_quantity, 'Add: Adjustments', 'Less: Adjustments' ) AS trans_group,
										reason AS description,
										i.item_name, i.item_group, i.item_type, i.item_unit,
										IF( ct.conversion_factor IS NULL, ABS( adj.adjusted_quantity - adj.previous_quantity ), ABS( adj.adjusted_quantity - adj.previous_quantity ) * ct.conversion_factor ) AS base_quantity
									FROM adjustments AS adj
									LEFT JOIN store_inventory AS si
										ON si.id = adj.store_inventory_id
									LEFT JOIN items AS i
										ON i.id = si.item_id
									LEFT JOIN conversion_table AS ct
										ON ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id
									WHERE
										si.store_id = ${store_id}
										AND adj.adjustment_shift = ${shift_id}
										AND DATE( adj.adjustment_timestamp ) = '${date}'
										AND adj.adjustment_status = 2
								) AS a
								GROUP BY trans_group, description
							) AS b
							ORDER BY row_num ASC";

			$balance_data = $ci->db->query( $sql );
			$balance_data = $balance_data->result_array();

			$sql = "SELECT
								a.item_group,
								a.item_name,
								a.item_unit,
								SUM( IF( item_type = 1, a.sti_ending_balance, 0 ) ) AS quantity,
								SUM( IF( item_type = 0, a.sti_ending_balance, 0 ) ) AS defective_quantity,
								a.conversion_factor,
								SUM( IF( item_type = 1, a.base_quantity, 0 ) ) AS base_quantity,
								SUM( IF( item_type = 0, a.base_quantity, 0 ) ) AS base_defective_quantity
							FROM (
								SELECT
									i.id AS item_id, i.item_name, i.item_description, i.item_group, i.item_type, i.item_unit,
									sti.sti_ending_balance, ct.conversion_factor,
									IF( ct.conversion_factor IS NULL, sti.sti_ending_balance, sti.sti_ending_balance * ct.conversion_factor ) AS base_quantity
								FROM shift_turnover_items AS sti
								LEFT JOIN shift_turnovers AS st
									ON st.id = sti.sti_turnover_id
								LEFT JOIN items AS i
									ON i.id = sti.sti_item_id
								LEFT JOIN items AS bi
									ON bi.id = i.base_item_id
								LEFT JOIN conversion_table AS ct
									ON ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id
								WHERE
									st.st_store_id = ${store_id}
									AND st.st_from_date = '${date}'
									AND st.st_from_shift_id = ${shift_id}
							) AS a
							GROUP BY a.item_group, a.item_name, a.item_unit, a.conversion_factor
							HAVING quantity > 0 OR defective_quantity > 0
							ORDER BY a.item_id";

			$breakdown_data = $ci->db->query( $sql );
			$breakdown_data = $breakdown_data->result_array();

			return array( 'balance' => $balance_data, 'breakdown' => $breakdown_data );
	}
}