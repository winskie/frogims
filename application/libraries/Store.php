<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Store extends Base_model
{
	protected $store_name;
	protected $store_code;
    protected $store_type;
	protected $store_location;
	protected $store_contact_number;

	protected $members;
	protected $shifts;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	public function __construct()
	{
		$this->primary_table = 'stores';
		$this->db_fields = array(
			'store_name' => array( 'type' => 'string' ),
			'store_code' => array( 'type' => 'string' ),
            'store_type' => array( 'type' => 'integer' ),
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

		$ci->db->where( 'store_id', $this->id );
		$ci->db->where( 'user_id', $user_id );
		$query = $ci->db->get( 'store_users' );

		return $query->num_rows > 0;
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


	public function get_items( $format = 'object' )
	{
		$ci =& get_instance();
		$ci->load->library( 'Inventory' );

		$ci->db->select( 'si.*, i.item_name, i.item_description, i.teller_allocatable, i.teller_remittable, i.machine_allocatable, i.machine_remittable' );
		$ci->db->where( 'store_id', $this->id );
		$ci->db->join( 'items i', 'i.id = si.item_id' );
		$query = $ci->db->get( 'store_inventory si' );

		if( $format == 'object')
		{
			return $query->result( 'Inventory' );
		}
		elseif( $format == 'array' )
		{
			return $query->result_array();
		}

		return NULL;
	}


	public function add_item( $item, $buffer_level = 0 )
	{
		$ci =& get_instance();

		$item_id = $item->get( 'id' );
		$data = array(
			'store_id' => $this->id,
			'item_id' => $item_id,
			'quantity' => 0,
			'quantity_timestamp' => date( TIMESTAMP_FORMAT ),
			'buffer_level' => $buffer_level,
			'reserved' => 0
		);

		$ci->db->trans_start();
		$ci->db->insert( 'store_inventory', $data );
		$ci->db->trans_complete();

		$ci->load->library( 'Inventory' );
		$inventory = $ci->inventory->get_by_store_item( $this->id, $item_id );

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
		$item_id = param( $params, 'item' );
		$transaction_type = param( $params, 'type' );

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'transaction_datetime DESC' );

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}

		$ci->db->select( 't.*, i.id AS item_id, i.item_name, i.item_description, s.shift_num' );
		$ci->db->join( 'store_inventory si', 'si.id = t.store_inventory_id' );
		$ci->db->join( 'items i', 'i.id = si.item_id' );
		$ci->db->join( 'shifts s', 's.id = t.transaction_shift' );
		$ci->db->where( 'si.store_id', intval( $this->id ) );

		if( $business_date )
		{
			$ci->db->where( 'DATE(transaction_datetime)', $business_date );
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

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'transfer_datetime DESC, id DESC' );

		//$ci->db->select( 't.*, i.item_name' );
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

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$order = param( $params, 'order', 'receipt_datetime DESC, id DESC' );
		$format = param( $params, 'format', 'object' );

		$ci =& get_instance();
		$ci->load->library( 'transfer' );

		// Do not show pending or scheduled transfers
		$available_status = array( TRANSFER_APPROVED, TRANSFER_RECEIVED, TRANSFER_CANCELLED );

		$ci->db->select( 't.*' );
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
		$sql .= ' GROUP BY mopping_id, m.date_created, business_date, shift_id, cashier_shift_id, mopped_item_id, converted_to
					) AS a
					LEFT JOIN conversion_table AS ct
						ON ct.source_item_id = a.mopped_item_id AND ct.target_item_id = a.converted_to
					GROUP BY mopping_id, processing_datetime, business_date, shift_id, cashier_shift_id,item_id
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

		$ci->db->select( 'a.*, s.shift_num, s.description' );
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
					x.allocated_item_id, x.item_name, x.item_description, x.allocation, x.additional, x.remitted
				FROM (
					SELECT
						allocation_id,
						allocated_item_id,
						item_name,
						item_description,
						SUM( IF( ic.is_allocation_category = TRUE AND category = "Initial Allocation" AND NOT allocation_item_status IN ('.implode( ', ', array( ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED ) ).'), allocated_quantity, 0 ) ) AS allocation,
						SUM( IF( ic.is_allocation_category = TRUE AND category IN ( "Additional Allocation", "Magazine Load" ) AND NOT allocation_item_status = '.ALLOCATION_ITEM_VOIDED.', allocated_quantity, 0 ) ) AS additional,
						SUM( IF( ic.is_remittance_category = TRUE AND NOT allocation_item_status = '.REMITTANCE_ITEM_VOIDED.', allocated_quantity, 0 ) ) AS remitted
					FROM allocation_items AS ai
					LEFT JOIN allocations AS a
						ON a.id = ai.allocation_id
					LEFT JOIN items AS i
						ON i.id = ai.allocated_item_id
					LEFT JOIN item_categories AS ic
						ON ic.id = ai.allocation_category_id';

		if( $allocation_date || $assignee_type || $status )
		{
			$sql .= ' WHERE a.id IS NOT NULL';
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

	public function count_transactions( $params = array() )
	{
		$ci =& get_instance();
		$ci->load->library( 'transaction' );

		$business_date = param( $params, 'date' );
		$item_id = param( $params, 'item' );
		$transaction_type = param( $params, 'type' );

		$ci->db->select( 't.id' );
		$ci->db->join( 'store_inventory si', 'si.id = t.store_inventory_id' );
		$ci->db->join( 'items i', 'i.id = si.item_id' );
		$ci->db->join( 'shifts s', 's.id = t.transaction_shift' );
		$ci->db->where( 'si.store_id', intval( $this->id ) );

		if( $business_date )
		{
			$ci->db->where( 'DATE(transaction_datetime)', $business_date );
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
		$available_status = array( TRANSFER_APPROVED, TRANSFER_RECEIVED, TRANSFER_CANCELLED );

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
		$count = $ci->db->count_all_results( 'allocations' );

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

	public function get_inventory_balances( $date = NULL, $params = array() )
	{
		$date = param_type( $date, 'datetime', date( TIMESTAMP_FORMAT ) );
		$items = param( $params, 'item' );

		$ci =& get_instance();

		$params = array();
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
			$params[] = $date;
		}

		$sql .= ' LEFT JOIN store_inventory si
					ON si.id = t.store_inventory_id
				WHERE t0.id IS NULL';

		if( $date )
		{
				$sql .= ' AND t.transaction_timestamp <= ?';
				$params[] = $date;
		}

		$sql .= '	AND si.store_id = ?
						) AS x
							ON x.id = si.id
						WHERE si.store_id = ?';
		$params[] = $this->id;
		$params[] = $this->id;

		if( $items )
		{
				$sql .= ' AND i.id in ('.implode( ', ', $items ).')';
		}

		$data = $ci->db->query( $sql, $params );

		return $data->result_array();
	}
}