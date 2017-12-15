<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Allocation_item extends Base_model {

	protected $allocation_id;
	protected $cashier_shift_id;
	protected $cashier_id;
	protected $allocated_item_id;
	protected $allocated_quantity;
	protected $allocation_category_id;
	protected $allocation_datetime;
	protected $allocation_item_status;
	protected $allocation_item_type;

	protected $category = NULL;
	protected $item = NULL;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	protected $previousStatus;
	protected $parentAllocation;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'allocation_items';
		$this->db_fields = array(
				'allocation_id' => array( 'type' => 'integer' ),
				'cashier_shift_id' => array( 'type' => 'integer' ),
				'cashier_id' => array( 'type' => 'integer' ),
				'allocated_item_id' => array( 'type' => 'integer' ),
				'allocated_quantity' => array( 'type' => 'integer' ),
				'allocation_category_id' => array( 'type' => 'integer' ),
				'allocation_datetime' => array( 'type' => 'datetime' ),
				'allocation_item_status' => array( 'type' => 'integer' ),
				'allocation_item_type' => array( 'type' => 'integer' )
			);
	}

		public function set_parent( &$parent )
	{
		$this->parentAllocation = $parent;
	}

	public function get_parent()
	{
		$ci =& get_instance();

		if( ! $this->parentAllocation )
		{
			$ci->load->library( 'allocation' );
			$allocation = new Allocation();
			$allocation = $allocation->get_by_id( $this->allocation_id );
			$this->parentAllocation = $allocation;
		}

		return $this->parentAllocation;
	}

	public function get_category()
	{
		if( ! isset( $this->category ) && isset( $this->allocation_category_id ) )
		{
			$ci =& get_instance();
			$ci->load->library( 'category' );
			$category = new Category();
			$category = $category->get_by_id( $this->allocation_category_id );
			$this->category = $category;
		}

		return $this->category;
	}

	public function get_item()
	{
		if( ! isset( $this->item ) && isset( $this->allocated_item_id ) )
		{
			$ci =& get_instance();

			$ci->load->library( 'item' );

			$ci->db->select( 'i.*, ip.iprice_unit_price, ct.conversion_factor' );
			$ci->db->where( 'i.id', $this->allocated_item_id );
			$ci->db->join( 'item_prices ip', 'ip.iprice_item_id = i.id', 'left' );
			$ci->db->join( 'conversion_table ct', 'ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id', 'left' );
			$query = $ci->db->get( 'items i' );
			$row = $query->row( 0, 'Item' );

			if( isset( $row ) )
			{
				$this->item = $row;
			}
		}

		return $this->item;
	}

	public function set( $property, $value )
	{
		if( $property == 'id' )
		{
			return FALSE;
		}

		if( property_exists( $this, $property ) )
		{
			if( $property == 'allocation_item_status' )
			{
				if( ! isset( $this->previousStatus ) )
				{
					$this->previousStatus = $this->allocation_item_status;
				}
			}

			if( $this->$property !== $value )
			{
				$this->$property = $value;
				$this->_db_change( $property, $value );
			}
		}
		else
		{
			return FALSE;
		}

		return TRUE;
	}

	public function db_save()
	{
		// There are no pending changes, just return the record
		if( ! $this->db_changes )
		{
			return $this;
		}

		$ci =& get_instance();

		$result = NULL;
		$ci->db->trans_start();

		// Check for required default values
		$this->_set_defaults();

		if( isset( $this->id ) )
		{
			// Set fields and update record metadata
			$this->_update_timestamps( FALSE );

			$ci->db->set( $this->db_changes );
			$result = $this->_db_update();
		}
		else
		{
			// Set fields and update record metadata
			$this->_update_timestamps( TRUE );
			$ci->db->set( $this->db_changes );
			$result = $this->_db_insert();
		}
		$ci->db->trans_complete();

		if( $ci->db->trans_status() )
		{
			$this->_reset_db_changes();
			$this->previousStatus = NULL;

			return $result;
		}
		else
		{
			return FALSE;
		}
	}

	public function _set_defaults()
	{
		$ci =& get_instance();

		if( ! isset( $this->allocation_datetime ) )
		{
			$this->set( 'allocation_datetime', date( TIMESTAMP_FORMAT ) );
		}

		if( ! isset( $this->cashier_id ) )
		{
			$this->set( 'cashier_id', $ci->session->current_user_id );
		}

		if( ! isset( $this->allocation_item_status ) )
		{
			$category = $this->get_category();
			switch( $category->get( 'cat_module' ) )
			{
				case 'Allocation':
					$this->set( 'allocation_item_status', ALLOCATION_ITEM_SCHEDULED );
					break;

				case 'Remittance':
					$this->set( 'allocation_item_status',  REMITTANCE_ITEM_PENDING );
					break;

				default:
					die( 'Invalid allocation category type' );
			}
		}

		if( ! isset( $this->cashier_shift_id ) )
		{
			$this->set( 'cashier_shift_id', current_shift( TRUE ) );
		}

		return TRUE;
	}
}