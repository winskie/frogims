<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Store extends Base_model
{
	protected $store_name;
    protected $store_type;
	protected $store_location;
	protected $store_contact_number;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'stores';
		$this->db_fields = array(
			'store_name' => array( 'type' => 'string' ),
            'store_type' => array( 'type' => 'integer' ),
			'store_location' => array( 'type' => 'string' ),
			'store_contact_number' => array( 'type' => 'string' )
		);
	}
    
    static function get_shifts( $store_type = NULL )
    {
        $ci =& get_instance();
        
        $ci->load->library( 'shift' );
        if( ! is_null( $store_type ) )
        {
            if( is_array( $store_type ) )
            {
                $ci->db->where_in( 'store_type', $store_type );
            }
            else
            {
                $ci->db->where( 'store_type', $store_type );
            }
        }
        $query = $ci->db->get( 'shifts' );
        
        return $query->result( 'Shift' );
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

    public function get_store_shifts( $show_all = FALSE )
    {
        $ci =& get_instance();
        
        $ci->load->library( 'shift' );
        if( ! $show_all )
        {
            $ci->db->where( 'store_type', $this->store_type );
        }
        $query = $ci->db->get( 'shifts' );
        
        return $query->result( 'Shift' );
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
		$limit = param( $params, 'limit' );
		$offset = param( $params, 'offset' );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'transaction_datetime DESC' );

		if( $limit )
		{
			$ci->db->limit( $limit, is_null( $offset ) ? 0 : $offset );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}
		
		$ci->db->select( 't.*, i.item_name, i.item_description' );
		$ci->db->join( 'store_inventory si', 'si.id = t.store_inventory_id' );
		$ci->db->join( 'items i', 'i.id = si.item_id' );
		$ci->db->where( 'store_id', intval( $this->id ) );
		$query = $ci->db->get( 'transactions t' );

		if( $format )
		{
			return $query->result_array();
		}

		return $query->result( 'Transaction' );
	}


	public function get_transfers( $params = array() )
	{
		$ci =& get_instance();

		$ci->load->library( 'Transfer' );
		$limit = param( $params, 'limit' );
		$offset = param( $params, 'offset' );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'transfer_datetime DESC' );


		//$ci->db->select( 't.*, i.item_name' );
		if( $limit )
		{
			$ci->db->limit( $limit, ( $offset ? $offset : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
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
		$ci =& get_instance();

		$ci->load->library( 'transfer' );
		$limit = param( $params, 'limit' );
		$offset = param( $params, 'offset' );
		$order = param( $params, 'order', 'transfer_datetime DESC' );
		$format = param( $params, 'format', 'object' );

		// Do not show pending or scheduled transfers
		$available_status = array( TRANSFER_APPROVED, TRANSFER_RECEIVED, TRANSFER_CANCELLED );

		$ci->db->select( 't.*' );
		if( $limit )
		{
			$ci->db->limit( $limit, ( $offset ? $offset : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}
		
		$ci->db->where_in( 'transfer_status', $available_status );
		$ci->db->where( 'destination_id', $this->id );
		//$ci->db->join( 'items i', 'i.id = t.item_id', 'left' );
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
		$ci =& get_instance();
		
		$ci->load->library( 'adjustment' );
		$limit = param( $params, 'limit' );
		$offset = param( $params, 'offset' );
		$order = param( $params, 'order', 'a.id DESC' );
		$format = param( $params, 'format', 'object' );
		
		if( $limit )
		{
			$ci->db->limit( $limit, ( $offset ? $offset : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}
		
		$ci->db->select( 'a.*, i.item_name, i.item_description, u.username, u.full_name' );
		$ci->db->where( 'si.store_id', $this->id );
		$ci->db->join( 'store_inventory si', 'si.id = a.store_inventory_id', 'left' );
		$ci->db->join( 'items i', 'i.id = si.item_id', 'left' );
        $ci->db->join( 'users u', 'u.id = a.user_id', 'left' );
		$query = $ci->db->get( 'adjustments a' );
		
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
    
    public function get_collections( $params =array () )
    {
        $ci =& get_instance();
        
        $ci->load->library( 'mopping' );
        $limit = param( $params, 'limit' );
        $offset = param( $params, 'offset' );
        $order = param( $params, 'order', 'm.id DESC' );
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
							m.store_id = ?
                        GROUP BY mopping_id, m.date_created, business_date, shift_id, cashier_shift_id, mopped_item_id, converted_to
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
                    ON cs.id = b.cashier_shift_id';
                
        $query = $ci->db->query( $sql, array( $this->id ) );
        
        return $query->result_array();
    }
    
    public function get_allocations( $params = array() )
    {
        $ci =& get_instance();
        
        $ci->load->library( 'allocation' );
        $limit = param( $params, 'limit' );
        $offset = param( $params, 'offset' );
        $order = param( $params, 'order', 'a.id DESC' );
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
					LEFT JOIN items AS i
						ON i.id = ai.allocated_item_id
					LEFT JOIN item_categories AS ic
						ON ic.id = ai.allocation_category_id
					GROUP BY allocation_id, allocated_item_id, item_name, item_description
				) AS x
				RIGHT JOIN allocations AS a
					ON x.allocation_id = a.id
				LEFT JOIN shifts AS s
					ON s.id = a.shift_id
				WHERE a.store_id = ?';
					
		$query = $ci->db->query( $sql, array( $this->id ) );
		
		return $query->result_array();
	}
	
    public function get_conversions( $params =array () )
    {
        $ci =& get_instance();
        
        $ci->load->library( 'conversion' );
        $limit = param( $params, 'limit' );
        $offset = param( $params, 'offset' );
        $order = param( $params, 'order', 'c.id DESC' );
        $format = param( $params, 'format', 'object' );
        
        if( $limit )
        {
            $ci->db->limit( $limit, ( $offset ? $offset : 0 ) );
        }
        if( $order )
        {
            $ci->db->order_by( $order );
        }
        
        $ci->db->select( 'c.*, src_item.item_name AS source_item_name, src_item.item_description AS source_item_description,
                tgt_item.item_name AS target_item_name, tgt_item.item_description AS target_item_description' );
        $ci->db->where( 'c.store_id', $this->id );
        $ci->db->join( 'store_inventory si', 'si.id = c.source_inventory_id', 'left' );
        $ci->db->join( 'store_inventory ti', 'ti.id = c.target_inventory_id', 'left' );
        $ci->db->join( 'items src_item', 'src_item.id = si.item_id', 'left' );
        $ci->db->join( 'items tgt_item', 'tgt_item.id = ti.item_id', 'left' );
        $query = $ci->db->get( 'conversions c' );
        
        if( $format == 'object' )
        {
            return $query->result( 'Conversion' );
        }
        elseif( $format == 'array' )
        {
            return $query->result_array();
        }
        
        return NULL;
    }
    
	public function count_pending_transfers()
	{
		$ci =& get_instance();
		$ci->db->where( 'transfer_status', TRANSFER_PENDING );
		$ci->db->where( 'origin_id', $this->id );
		$ci->db->join( 'stores s', 's.id = t.origin_id', 'left' );
		$count = $ci->db->count_all_results( 'transfers t' );
		
		return $count;
	}
	
	public function count_pending_receipts()
	{
		$ci =& get_instance();
		$ci->db->where( 'transfer_status', TRANSFER_APPROVED );
		$ci->db->where( 'destination_id', $this->id );
		$ci->db->join( 'stores s', 's.id = t.origin_id', 'left' );
		$count = $ci->db->count_all_results( 'transfers t' );
		
		return $count;
	}
	
	public function count_pending_adjustments()
	{
		$ci =& get_instance();
		$ci->db->where( 'si.store_id', $this->id );
		$ci->db->where( 'a.adjustment_status', ADJUSTMENT_PENDING );
		$ci->db->join( 'store_inventory si', 'si.id = a.store_inventory_id', 'left' );
		$count = $ci->db->count_all_results( 'adjustments a' );
		
		return $count;		
	}
}