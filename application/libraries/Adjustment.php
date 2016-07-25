<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Adjustment extends Base_model {

	protected $store_inventory_id;
	protected $adjustment_shift;
	protected $adjustment_type;
	protected $adjusted_quantity;
	protected $previous_quantity;
	protected $reason;
	protected $adjustment_timestamp;
	protected $adjustment_status;
	protected $user_id;
	protected $adj_transaction_type;
	protected $adj_transaction_id;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	protected $previousStatus;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'adjustments';
		$this->db_fields = array(
				'store_inventory_id' => array( 'type' => 'integer' ),
                'adjustment_shift' => array( 'type' => 'integer' ),
				'adjustment_type' => array( 'type' => 'integer' ),
				'adjusted_quantity' => array( 'type' => 'integer' ),
				'previous_quantity' => array( 'type' => 'integer' ),
				'reason' => array( 'type' => 'string' ),
				'adjustment_timestamp' => array( 'type' => 'datetime' ),
				'adjustment_status' => array( 'type' => 'integer' ),
				'user_id' => array( 'type' => 'integer' ),
				'adj_transaction_type' => array( 'type' => 'integer' ),
				'adj_transaction_id' => array( 'type' => 'integer' )
			);
	}


	public function get_by_id( $id )
	{
		$ci =& get_instance();

		$ci->db->select( 'a.*, i.item_name, i.item_description, u.full_name' );
		$ci->db->join( 'store_inventory si', 'si.id = a.store_inventory_id', 'left' );
		$ci->db->join( 'items i', 'i.id = si.item_id', 'left' );
		$ci->db->join( 'users u', 'u.id = a.user_id', 'left' );
		$ci->db->where( 'a.id', $id );
		$ci->db->limit( 1 );
		$query = $ci->db->get( 'adjustments a' );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class( $this ) );
		}

		return NULL;
	}


	public function set( $property, $value )
	{
		if( $property == 'id' )
		{
			log_message( 'error', 'Setting of id property not allowed.' );
			return FALSE;
		}

		if( property_exists( $this, $property ) )
		{
			if( $property == 'adjustment_status' )
			{
				if( ! isset( $this->previousStatus ) )
				{
					$this->previousStatus = $this->adjustment_status;
				}
			}

			if( $this->$property != $value )
			{
				$this->$property = $value;
				$this->_db_change( $property, $value );
			}
		}
		else
		{
			log_message( 'debug', 'Unable to set property '.$property.'. Property does not exist.' );
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

		if( $this->_check_data() )
		{
			$ci =& get_instance();

			$result = NULL;
			$ci->db->trans_start();

			if( isset( $this->id ) )
			{
				$pre_approved_status = array( ADJUSTMENT_PENDING );

				// If this adjustment is going be approved set the adjustment timestamp and the current inventory balance first!
				if( array_key_exists( 'adjustment_status', $this->db_changes )
						&& $this->db_changes['adjustment_status'] == ADJUSTMENT_APPROVED
						&& isset( $this->previousStatus )
						&& in_array( $this->previousStatus, $pre_approved_status ) )
				{
					$this->set( 'adjustment_timestamp', date( TIMESTAMP_FORMAT ) );
					$inventory = $this->get_inventory();
					if( $inventory )
					{
						$this->set( 'previous_quantity', $inventory->get( 'quantity' ) );
					}

					// Also set shift information
					$this->set( 'adjustment_shift', $ci->session->current_shift_id );
				}

				// Set fields and updata record metadata
				$this->_update_timestamps( FALSE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_update();

				// Approved adjustment, transact with inventory
				if( array_key_exists( 'adjustment_status', $this->db_changes )
						&& $this->db_changes['adjustment_status'] == ADJUSTMENT_APPROVED
						&& isset( $this->previousStatus )
						&& in_array( $this->previousStatus, $pre_approved_status ) )
				{
					$this->_transact_adjustment();
				}
			}
			else
			{
				// Always set adjustment_timestamp on record creation
				$this->set( 'adjustment_timestamp', date( TIMESTAMP_FORMAT ) );
				$this->set( 'user_id', $ci->session->current_user_id );

				// Also set shift information
				$this->set( 'adjustment_shift', $ci->session->current_shift_id );

				// If this adjustment is going be approved set the current inventory balance first!
				if( array_key_exists( 'adjustment_status', $this->db_changes )
						&& $this->db_changes['adjustment_status'] == ADJUSTMENT_APPROVED )
				{
					$inventory = $this->get_inventory();
					if( $inventory )
					{
						$this->set( 'previous_quantity', $inventory->get( 'quantity' ) );
					}
				}

				// Set fields and updata record metadata
				$this->_update_timestamps( TRUE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_insert();

				// Approved adjustment, transact with inventory
				// TODO: Check if user is allowed to approved adjustments
				if( $this->adjustment_status == ADJUSTMENT_APPROVED )
				{
					$this->_transact_adjustment();
				}
			}
			$ci->db->trans_complete();

			if( $ci->db->trans_status() )
			{
				// Reset record changes
				$this->_reset_db_changes();
				$this->previousStatus = NULL;

				return $result;
			}
			else
			{
				set_message( 'An error has occurred while trying to save the adjustment record', 'error' );
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}


	public function approve()
	{
		$ci =& get_instance();

		// Only allow approval from the following previous status:
		$allowed_prev_status = array( ADJUSTMENT_PENDING );
		if( ! in_array( $this->adjustment_status, $allowed_prev_status ) )
		{
			set_message( 'Cannot approve non-pending adjustments', 'error' );
			return FALSE;
		}

		$ci->db->trans_start();

		$this->set( 'adjustment_timestamp', date( TIMESTAMP_FORMAT ) );
		$this->set( 'adjustment_status', ADJUSTMENT_APPROVED );
		$this->set( 'user_id', $ci->session->current_user_id );
		$result = $this->db_save();

		if( $result )
		{
			$ci->db->trans_complete();

			if( $ci->db->trans_status() )
			{
				return $this;
			}
		}
		else
		{
			return FALSE;
		}
	}


	public function cancel()
	{
		$ci &= get_instance();

		// Check for valid previous adjustment status
		if( ! in_array( $this->adjustment_status, array( ADJUSTMENT_PENDING ) ) )
		{
			set_message( 'Cannot cancel adjustment. Only pending adjustments can be cancelled.', 'error' );
			return FALSE;
		}

		$ci->db->trans_start();

		$this->set( 'adjustment_status', ADJUSTMENT_CANCELLED );
		$result = $this->db_save();

		if( $result )
		{
			$ci->db->trans_complete();

			if( $ci->db->trans_status() )
			{
				return $this;
			}
		}
		else
		{
			return FALSE;
		}
	}


	public function _check_data()
	{
		// Set default values
		// Set user_id
		if( ! array_key_exists( 'user_id', $this->db_changes ) && is_null( $this->user_id ) )
		{
			$this->set( 'user_id', current_user( TRUE ) );
		}


		// Check for valid new adjustment status
		if( ! isset( $this->id ) )
		{
			$valid_new_status = array( ADJUSTMENT_PENDING, ADJUSTMENT_APPROVED );
			if( ! ( array_key_exists( 'adjustment_status', $this->db_changes )
					&& in_array( $this->db_changes['adjustment_status'], $valid_new_status ) ) )
			{
				set_message( 'Invalid adjustment status for new record', 'error' );
				return FALSE;
			}
		}

		// Ensure that a reason was specified for the adjustment
		if( ! ( array_key_exists( 'reason', $this->db_changes ) && $this->db_changes['reason'] )
			&& ! ( isset( $this->reason ) && $this->reason ) )
		{
			set_message( 'You must specify a reason for the adjustment', 'error' );
			return FALSE;
		}

		return TRUE;
	}

	public function _transact_adjustment()
	{
		$ci =& get_instance();

		$timestamp = date( TIMESTAMP_FORMAT );
		$ci->load->library( 'inventory' );
		$inventory = new Inventory();
		$inventory = $inventory->get_by_id( $this->store_inventory_id );

		if( $inventory )
		{
			$quantity = $this->adjusted_quantity - $inventory->get( 'quantity' );
			$inventory->transact( TRANSACTION_ADJUSTMENT, $quantity, $timestamp, $this->id );
		}
		else
		{
			die( 'Inventory record not found.' );
		}
	}


	public function get_inventory()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$inventory = new Inventory();
		$inventory = $inventory->get_by_id( $this->store_inventory_id, array(
			array(
				'table' => 'items',
				'fields' => array( 'item_name', 'item_description' ),
				'relation' => 'items.id = store_inventory.item_id',
				'join_type' => 'left'
			)
		) );

		return $inventory;
	}


	public function get_user()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$inventory = new Inventory();
		$inventory = $inventory->get_by_id( $this->store_inventory_id );

		return $inventory;
	}
}