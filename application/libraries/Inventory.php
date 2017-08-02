<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory extends Base_model
{
	protected $store_id;
	protected $item_id;
	protected $quantity;
	protected $quantity_timestamp;
	protected $buffer_level;
	protected $reserved;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'store_inventory';
		$this->db_fields = array(
			'store_id' => array( 'type' => 'integer' ),
			'item_id' => array( 'type' => 'integer' ),
			'quantity' => array( 'type' => 'decimal' ),
			'quantity_timestamp' => array( 'type' => 'datetime' ),
			'buffer_level' => array( 'type' => 'decimal' ),
			'reserved' => array( 'type' => 'decimal' ),
		);
	}

	public function get_categories()
    {
        $ci =& get_instance();
        $ci->load->library( 'category' );

        $ci->db->select( 'c.*' );
        $ci->db->where( 'ic_item_id', $this->item_id );
        $ci->db->join( 'categories c', 'c.id = ic_category_id', 'left' );
        $query = $ci->db->get( 'item_categories' );

        return $query->custom_result_object( 'Category' );
    }


	public function get_by_store_item( $store_id, $item_id )
	{
		$ci =& get_instance();
		$ci->db->where( 'store_id', $store_id );
		$ci->db->where( 'item_id', $item_id );
		$ci->db->limit( 1 );
		$query = $ci->db->get( $this->primary_table );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class( $this ) );
		}

		return NULL;
	}


	public function get_by_store_item_name( $store_id, $item_name )
	{
		$ci =& get_instance();
		$ci->db->select( 'a.*' );
		$ci->db->join( 'items i', 'i.id = a.item_id', 'left' );
		$ci->db->where( 'a.store_id', $store_id );
		$ci->db->where( 'i.item_name', $item_name );
		$ci->db->limit( 1 );
		$query = $ci->db->get( $this->primary_table.' a' );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class( $this ) );
		}

		return NULL;
	}


	public function get_transactions( $params = array() )
	{
		$ci =& get_instance();

		$ci->load->library( 'transaction' );
		$limit = param( $params, 'limit' );
		$offset = param( $params, 'offset' );
		$format = param( $params, 'format' );
		$order = param( $params, 'order', 'transaction_datetime DESC' );

		if( $limit )
		{
			$ci->db->limit( $limit, is_null( $offset ) ? 0 : $offset );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}
		$ci->db->where( 'store_inventory_id', intval( $this->id ) );
		$query = $ci->db->get( 'transactions' );

		if( $format )
		{
			return $query->result_array();
		}

		return $query->result( 'Transaction' );
	}


	public function get_transactions_by_date( $start_date, $end_date )
	{
		$ci &= get_instance();

		$ci->db->where( 'store_id', $this->store_id );
		$ci->db->where( 'item_id', $this->item_id );
		$ci->db->where( 'transaction_datetime >=', $start_date );
		$ci->db->where( 'transaction_datetime <=', $end_date );
		$query = $this->db->get( $this->primary_table );

		return $query->result( get_class( $this ) );
	}


	public function transact( $transaction_type, $quantity, $datetime, $reference_id, $reference_item_id = NULL )
	{
		$ci =& get_instance();

		// Update current inventory levels
		$new_quantity = $this->quantity + $quantity;
		$timestamp = date( TIMESTAMP_FORMAT );
        $current_shift = $ci->session->current_shift_id;

		$ci->db->trans_start();

		$this->set( 'quantity', $new_quantity );
		$this->set( 'quantity_timestamp', $timestamp );

		$this->db_save();

		// Generate inventory transaction record
		$ci->db->set( 'store_inventory_id', $this->id );
		$ci->db->set( 'transaction_type', $transaction_type );
		$ci->db->set( 'transaction_datetime', date( TIMESTAMP_FORMAT, strtotime( $datetime ) ) );
		$ci->db->set( 'transaction_quantity', ( double ) $quantity );
		$ci->db->set( 'current_quantity', ( double ) $new_quantity );
		$ci->db->set( 'transaction_id', $reference_id);
		$ci->db->set( 'transaction_item_id', $reference_item_id );
		$ci->db->set( 'transaction_timestamp', $timestamp );
		$ci->db->set( 'transaction_shift', $current_shift );
		$ci->db->insert( 'transactions' );

		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}


	public function reserve( $quantity )
	{
		$ci =& get_instance();

		$new_reserved_quantity = $this->reserved + $quantity;

		$ci->db->trans_start();
		$this->set( 'reserved', ( double ) $new_reserved_quantity );
		$this->db_save();
		$ci->db->trans_complete();

		if( $ci->db->trans_status() )
		{
			return $new_reserved_quantity;
		}

		return FALSE;
	}


	public function adjust( $quantity, $reason, $status )
	{
		$ci =& get_instance();

		$ci->load->library( 'adjustment' );
		$adjustment = new Adjustment();

		$ci->db->trans_start();

		$adjustment->set( 'store_inventory_id', $this->id );
		$adjustment->set( 'adjustment_type', ADJUSTMENT_TYPE_ACTUAL );
		$adjustment->set( 'adjusted_quantity', ( double ) $quantity );
		$adjustment->set( 'previous_quantity', ( double ) $this->quantity );
		$adjustment->set( 'reason', $reason );
		$adjustment->set( 'adjustment_status', ADJUSTMENT_PENDING );
		$adjustment->set( 'user_id', $ci->session->current_user_id );
		$adjustment->db_save();

		$ci->db->trans_complete();

		if( $ci->db->trans_status() )
		{
			return TRUE;
		}

		return FALSE;
	}
}