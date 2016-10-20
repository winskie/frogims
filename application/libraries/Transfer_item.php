<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transfer_item extends Base_model {

	protected $transfer_id;
	protected $item_id;
	protected $item_category_id;
	protected $quantity;
	protected $quantity_received;
	protected $remarks;
	protected $transfer_item_status;
	protected $transfer_item_allocation_item_id;
	protected $transfer_item_transfer_item_id;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	protected $prevItem;
	protected $prevQuantity;
	protected $previousStatus;
	protected $parentTransfer;
	protected $category;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'transfer_items';
		$this->db_fields = array(
			'transfer_id' => array( 'type' => 'integer' ),
			'item_id' => array( 'type' => 'integer' ),
			'item_category_id' => array( 'type' => 'integer' ),
			'quantity' => array( 'type' => 'integer' ),
			'quantity_received' => array( 'type' => 'integer' ),
			'remarks' => array( 'type' => 'string' ),
			'transfer_item_status' => array( 'type' => 'integer' ),
			'transfer_item_allocation_item_id' => array( 'type' => 'integer' ),
			'transfer_item_transfer_item_id' => array( 'type' => 'integer' )
		);
	}

	public function set( $property, $value )
	{
		if( $property == 'id' )
		{
			return FALSE;
		}

		if( property_exists( $this, $property ) )
		{
			if( $property == 'item_id' && $this->item_id !== $value )
			{
				$this->prevItem = $this->item_id;
			}
			elseif( $property == 'quantity' && $this->quantity !== $value )
			{
				$this->prevQuantity = $this->quantity;
			}
			elseif( $property == 'transfer_item_status' && $this->transfer_item_status !== $value )
			{
				$this->previousStatus = $this->transfer_item_status;
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

	public function set_parent( &$parent )
	{
		$this->parentTransfer = $parent;
	}

	public function get_parent()
	{
		$ci =& get_instance();

		if( ! $this->parentTransfer )
		{
			$ci->load->library( 'transfer' );
			$transfer = new Transfer();
			$transfer = $transfer->get_by_id( $this->transfer_id );
			$this->parentTransfer = $transfer;
		}

		return $this->parentTransfer;
	}

	public function get_category()
	{
		if( ! isset( $this->category ) && isset( $this->item_id ) )
		{
			$ci =& get_instance();
			$ci->load->library( 'item_category' );
			$item_category = new Item_category();
			$item_category = $item_category->get_by_id( $this->item_id );
			$this->category = $item_category;
		}

		return $this->category;
	}

	public function db_save()
	{
		// There are no pending changes, just return the record
		if( ! $this->db_changes )
		{
			return $this;
		}

		$result = NULL;
		$ci =& get_instance();

		if( $this->_check_data() )
		{
			$ci->db->trans_start();
			$this->_set_default_values();

			if( isset( $this->id ) )
			{
				$this->_update_timestamps( FALSE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_update();
			}
			else
			{
				// Check if item is already turned over
				if( $this->is_item_turned_over() )
				{
					set_message( 'Item already turned over' );
					return FALSE;
				}

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
		else
		{
			return FALSE;
		}
	}

	private function _set_default_values()
	{

	}

	public function load_from_data( $data = array(), $overwrite = TRUE )
	{
		// Try to get existing value first if ID exists
		if( array_key_exists( 'id', $data ) && $data['id'] )
		{
			$r = $this->get_by_id( $data['id'] );
		}
		else
		{
			$r = $this;
		}

		foreach( $data as $field => $value )
		{
			if( $field == 'id' )
			{
				continue;
			}
			elseif( array_key_exists( $field, $this->db_fields ) )
			{
				if( ! isset( $r->$field ) || $overwrite )
				{
					//echo 'Setting '.$field.' from '.$this->$field.' to '.$value.'...<br />';
					$r->set( $field, $value );
				}
			}
		}

		return $r;
	}

	public function is_item_turned_over()
	{
		$ci =& get_instance();

		if( isset( $this->transfer_item_allocation_item_id ) )
		{
			$ci->db->where( 'transfer_item_allocation_item_id', $this->transfer_item_allocation_item_id );
			$ci->db->where_not_in( 'transfer_item_status', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) );
			$ci->db->from( 'transfer_items' );

			return $ci->db->count_all_results() > 0;
		}

		if( isset( $this->transfer_item_transfer_item_id ) )
		{
			$ci->db->where( 'transfer_item_transfer_item_id', $this->transfer_item_transfer_item_id );
			$ci->db->where_not_in( 'transfer_item_status', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) );
			$ci->db->from( 'transfer_items' );

			return $ci->db->count_all_results() > 0;
		}

		return FALSE;
	}
}