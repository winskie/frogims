<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Allocation_sales_item extends Base_model {

	protected $alsale_allocation_id;
	protected $alsale_shift_id;
	protected $alsale_cashier_id;
	protected $alsale_sales_item_id;
	protected $alsale_amount;
	protected $alsale_remarks;
	protected $alsale_sales_item_status;

	protected $sales_item = NULL;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	protected $previousStatus;
	protected $parentAllocation;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'allocation_sales_items';
		$this->db_fields = array(
				'alsale_allocation_id' => array( 'type' => 'integer' ),
				'alsale_shift_id' => array( 'type' => 'integer' ),
				'alsale_cashier_id' => array( 'type' => 'integer' ),
				'alsale_sales_item_id' => array( 'type' => 'integer' ),
				'alsale_amount' => array( 'type' => 'decimal' ),
				'alsale_remarks' => array( 'type' => 'string' ),
				'alsale_sales_item_status' => array( 'type' => 'integer' )
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
			$allocation = $allocation->get_by_id( $this->alsale_allocation_id );
			$this->parentAllocation = $allocation;
		}

		return $this->parentAllocation;
	}

	public function get_sales_item()
	{
		if( ! isset( $this->sales_item ) && isset( $this->alsale_sales_item_id ) )
		{
			$ci =& get_instance();
			$ci->load->library( 'sales_item' );
			$Sales_item = new Sales_item();
			$sales_item = $Sales_item->get_by_id( $this->alsale_sales_item_id );
			$this->sales_item = $sales_item;
		}

		return $this->sales_item;
	}

	public function set( $property, $value )
	{
		if( $property == 'id' )
		{
			return FALSE;
		}

		if( property_exists( $this, $property ) )
		{
			if( $property == 'alsale_sales_item_status' )
			{
				if( ! isset( $this->previousStatus ) )
				{
					$this->previousStatus = $this->alsale_sales_item_status;
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

		if( ! isset( $this->alsale_cashier_id ) )
		{
			$this->set( 'alsale_cashier_id', $ci->session->current_user_id );
		}

		if( ! isset( $this->alsale_sales_item_status ) )
		{
			$this->set( 'alsale_sales_item_status', SALES_ITEM_PENDING );
		}

		if( ! isset( $this->alsale_shift_id ) )
		{
			$this->set( 'alsale_shift_id', current_shift( TRUE ) );
		}

		return TRUE;
	}
}