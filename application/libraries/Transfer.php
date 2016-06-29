<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transfer extends Base_model {

	protected $origin_id;
	protected $origin_name;
	protected $sender_id;
	protected $sender_name;
    protected $sender_shift;
	protected $transfer_datetime;
	protected $destination_id;
	protected $destination_name;
	protected $recipient_id;
	protected $recipient_name;
    protected $recipient_shift;
	protected $receipt_datetime;
	protected $transfer_status;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	protected $previousStatus;
	protected $externalTransfer = FALSE;
	protected $externalReceipt = FALSE;
	protected $items;
	protected $voided_items;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'transfers';
		$this->db_fields = array(
				'origin_id' => array( 'type' => 'integer' ),
				'origin_name' => array( 'type' => 'string' ),
				'sender_id' => array( 'type' => 'integer' ),
				'sender_name' => array( 'type' => 'string' ),
                'sender_shift' => array( 'type' => 'integer' ),
				'transfer_datetime' => array( 'type' => 'datetime' ),
				'destination_id' => array( 'type' => 'integer' ),
				'destination_name' => array( 'type' => 'string' ),
				'recipient_id' => array( 'type' => 'integer' ),
				'recipient_name' => array( 'type' => 'string' ),
                'recipient_shift' => array( 'type' => 'integer' ),
				'receipt_datetime' => array( 'type' => 'datetime' ),
				'transfer_status' => array( 'type' => 'integer' )
			);
		$this->children = array(
				'items' => array( 'table' => 'transfer_items', 'key' => 'transfer_id', 'field' => 'items', 'class' => 'Transfer_item' )
			);
	}


	public function get_items( $attach = FALSE )
	{
		$ci =& get_instance();

		if( isset( $this->items ) )
		{
			return $this->items;
		}
		else
		{
			$ci->load->library( 'transfer_item' );
			$ci->db->select( 'ti.*, i.item_name, i.item_description, ci.category as category_name, ci.is_transfer_category' );
			$ci->db->where( 'transfer_id', $this->id );
			$ci->db->join( 'items i', 'i.id = ti.item_id', 'left' );
			$ci->db->join( 'item_categories ci', 'ci.id = ti.item_category_id', 'left' );
			$query = $ci->db->get( 'transfer_items ti' );
			$items = $query->result( 'Transfer_item' );

			if( $attach )
			{
				$this->items = $items;
			}
		}

		return $items;
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
			if( $this->$property !== $value )
			{
				// If it's a change in transfer status let's take note of the previous one
				if( $property == 'transfer_status' )
				{
					if( ! isset( $this->previousStatus ) )
					{
						$this->previousStatus = $this->transfer_status;
					}
				}
				$this->$property = $value;
				$this->_db_change($property, $value);
			}
		}
		else
		{
			log_message( 'debug', 'Unable to set property '.$property.'. Property does not exist.' );
			return FALSE;
		}

		return TRUE;
	}


	public function _check_data()
	{
		$ci =& get_instance();
		$items = $this->get_items();

		$voided_items = array();

		if( ! $items )
		{
			set_message( 'Transfer does not contain any items', 'error' );
			return FALSE;
		}

		$valid_new_status = array( TRANSFER_PENDING, TRANSFER_APPROVED, TRANSFER_RECEIVED );
		if( is_null( $this->id ) && ! in_array( $this->transfer_status, $valid_new_status ) )
		{
			set_message( 'Invalid transfer status for new record', 'error' );
			return FALSE;
		}

		foreach( $items as $item )
		{
			if( array_key_exists( 'transfer_item_status', $item->db_changes )
				&& $item->db_changes['transfer_item_status'] == TRANSFER_ITEM_VOIDED
				&& $item->get( 'previousStatus' ) == TRANSFER_ITEM_APPROVED  )
			{
				$item_id = $item->get( 'item_id' );
				if( isset( $voided_items[$item_id] ) )
				{
					$voided_items[$item_id] += $item->get( 'quantity' );
				}
				else
				{
					$voided_items[$item_id] = $item->get( 'quantity' );
				}
			}
		}

		$this->voided_items = $voided_items;

		return TRUE;
	}

	public function db_save()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$Inventory = new Inventory();

		$result = NULL;
		$ci->db->trans_start();

		if( isset( $this->id ) )
		{ // Update transfer record
			if( $this->_check_data() )
			{
				foreach( $this->items as $item )
				{
					if( array_key_exists( 'transfer_item_status', $item->db_changes ) )
					{
						if( $item->db_changes['transfer_item_status']  == TRANSFER_ITEM_SCHEDULED )
						{
							$inventory = $Inventory->get_by_store_item( $this->origin_id, $item->get( 'item_id' ) );
							$inventory->reserve( $item->get( 'quantity' ) );
						}
						elseif( in_array( $item->db_changes['transfer_item_status'], array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ) )
						{
							if( $item->get( 'previousStatus' ) == TRANSFER_ITEM_SCHEDULED )
							{
								$inventory = $Inventory->get_by_store_item( $this->origin_id, $item->get( 'item_id' ) );
								$inventory->reserve( $item->get( 'quantity' ) * -1 );
							}
						}
					}

					if( array_key_exists( 'transfer_status', $this->db_changes )
						&& $this->db_changes['transfer_status'] == TRANSFER_CANCELLED
						&& $this->previousStatus == TRANSFER_PENDING )
					{
						$inventory = $Inventory->get_by_store_item( $this->origin_id, $item->get( 'item_id' ) );
						$inventory->reserve( $item->get( 'quantity' ) * -1 );
					}

					if( ! $item->get( 'id' ) )
					{
						$item->set( 'transfer_id', $this->id );
					}

					if( $item->db_changes )
					{
						$item->db_save();
					}
				}

				// Check for required default values
				$this->_set_transfer_defaults();

				// Set fields and updata record metadata
				$this->_update_timestamps( FALSE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_update();


				// Change in transfer status
				if( array_key_exists( 'transfer_status', $this->db_changes ) )
				{
					switch( $this->db_changes['transfer_status'] )
					{
						case TRANSFER_APPROVED:
							$pre_approved_status = array( TRANSFER_PENDING );
							if( isset( $this->previousStatus ) && in_array( $this->previousStatus, $pre_approved_status ) )
							{
								$this->_transact_approval();
							}
							break;

						case TRANSFER_CANCELLED:
							$pre_cancellation_status = array( TRANSFER_APPROVED );
							if( isset( $this->previousStatus ) && in_array( $this->previousStatus, $pre_cancellation_status ) )
							{
								$this->_transact_cancellation();
							}
							break;

						case TRANSFER_RECEIVED:
							$pre_receipt_status = array( TRANSFER_APPROVED );
							if( isset( $this->previousStatus ) && in_array( $this->previousStatus, $pre_receipt_status ) )
							{
								$this->_transact_receipt();
							}
							break;

						default:
							// Do nothing
					}
				}

				// Transact voided items
				$this->_transact_voided_items();
			}
			else
			{
				return FALSE;
			}
		}
		else
		{ // Check for valid new transfer status
			if( $this->_check_data() )
			{
				// Adjust inventory reservation level for new transfer request
				if( isset( $this->origin_id ) && ( $this->origin_id == $ci->session->current_store_id ) )
				{
					foreach( $this->items as $item )
					{
						$inventory = new Inventory();
						$inventory = $inventory->get_by_store_item( $this->origin_id, $item->get( 'item_id' ) );
						if( $inventory )
						{
							$inventory->reserve( $item->get( 'quantity' ) );
						}
					}
				}

				// Check for required default values
				$this->_set_transfer_defaults();

				// Set fields and updata record metadata
				$this->_update_timestamps( TRUE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_insert();

				// save transfer items
				foreach( $this->items as $item )
				{
					$item->set( 'transfer_id', $this->id );
					$item->db_save();
				}

				// Transact transfer
				switch( $this->transfer_status )
				{
					case TRANSFER_APPROVED:
						$this->_transact_approval();
						break;

					case TRANSFER_RECEIVED:
						$this->_transact_receipt();
						break;

					default:
						// Do nothing here
				}
			}
		}

		$ci->db->trans_complete();

		// Reset record changes
		$this->_reset_db_changes();
		$this->previousStatus = NULL;
		$this->voided_items = NULL;

		return $result;
	}


	public function _set_transfer_defaults()
	{
		$ci =& get_instance();

		if( array_key_exists( 'transfer_status', $this->db_changes ) )
		{
			$ci->load->library( 'store' );
			$ci->load->library( 'user' );

			$current_store = new Store();
			$current_store = $current_store->get_by_id( $ci->session->current_store_id );

			$current_user = new User();
			$current_user = $current_user->get_by_id( $ci->session->current_user_id );

            $current_shift = $ci->session->current_shift_id;

			switch( $this->db_changes['transfer_status'] )
			{
				case TRANSFER_PENDING:
                    // Sender's Shift
                    $this->set( 'sender_shift', $current_shift );
					break;

				case TRANSFER_APPROVED:
					// Origin ID - should be the current store ID
					if( ! isset( $this->origin_id ) )
					{
						$this->set( 'origin_id', $current_store->get( 'id' ) );
					}

					// Origin name - should be the current store name
					if( ! isset( $this->origin_name ) )
					{
						$this->set( 'origin_name', $current_store->get( 'store_name' ) );
					}

					// Sender ID - should be the currently logged in user's ID
					$this->set( 'sender_id', $current_user->get( 'id' ) );

					// Sender name - if not specified should be currently logged in user
					$sender_name = isset( $this->sender_name ) ? $this->sender_name : $current_user->get( 'full_name' );
					$this->set( 'sender_name', $sender_name );

                    // Update sender's Shift
                    $this->set( 'sender_shift', $current_shift );

					// Transfer date/time - if not specified should be now
					if( ! $this->transfer_datetime )
					{
						$this->set( 'transfer_datetime', $date( TIMESTAMP_FORMAT ) );
					}

					break;

				case TRANSFER_CANCELLED:
					break;

				case TRANSFER_RECEIVED:
					// Destination ID - should be the current store ID
					if( ! isset( $this->destination_id ) )
					{
						$this->set( 'destination_id', $current_store->get( 'id' ) );
					}

					// Destination name/ Receiving store - should be the current store name
					if( ! isset( $this->destination_name ) )
					{
						$this->set( 'destination_name', $current_store->get( 'store_name' ) );
					}

					// Recipient ID - should be the currently logged in user's ID
					$this->set( 'recipient_id', $current_user->get( 'id' ) );

					// Recipient name - if not specified should be currently logged in user's name
					$recipient_name = isset( $this->recipient_name ) ? $this->recipient_name : $current_user->get( 'full_name' );
					$this->set( 'recipient_name', $recipient_name );

                    // Recipient Shift
                    $this->set( 'recipient_shift', $current_shift );

					// Receipt date/time - if not specified should be now
					if( ! isset( $this->receipt_datetime ) )
					{
						$this->set( 'receipt_datetime', date( TIMESTAMP_FORMAT ) );
					}

					if( $this->is_external_receipt() && ! isset( $this->transfer_datetime ) )
					{
						$this->set( 'transfer_datetime', $this->receipt_datetime );
					}

					break;

				default:
					// Do nothing
			}
		}

		return TRUE;
	}


	public function _transact_approval()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );

		$items = $this->get_items();
		$ci->db->trans_start();
		foreach( $items as $item )
		{
			if( in_array( $item->get( 'transfer_item_status' ), array( TRANSFER_ITEM_SCHEDULED ) ) )
			{
				$inventory = new Inventory();
				$inventory = $inventory->get_by_store_item( $this->origin_id, $item->get( 'item_id' ) );
				if( $inventory )
				{
					$quantity = $item->get( 'quantity' ) * -1; // Item will be removed from inventory
					$inventory->reserve( $quantity );
					$inventory->transact( TRANSACTION_TRANSFER_OUT, $quantity, $this->transfer_datetime, $this->id );

					$item->set( 'transfer_item_status', TRANSFER_ITEM_APPROVED );
					$item->db_save();
				}
				else
				{
					die( sprintf( 'Inventory record not found for store %s and item %s.', $this->origin_id, $item->get( 'item_id' ) ) );
				}
			}
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}


	public function _transact_cancellation()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );

		$items = $this->get_items();
		$timestamp = date( TIMESTAMP_FORMAT );
		$ci->db->trans_start();
		foreach( $items as $item )
		{
			$inventory = new Inventory();
			$inventory = $inventory->get_by_store_item( $this->origin_id, $item->get( 'item_id' ) );
			if( ! $inventory )
			{ // Somehow the inventory record was removed prior to cancellation of transfer, let's create a new inventory record
				$ci->load->library( 'item' );

				$new_item = new Item();
				$new_item = $new_item->get_by_id( $item->get( 'item_id') );
				$inventory = $current_store->add_item( $new_item );
			}

			$quantity = $item->get( 'quantity' ); // Item will be returned to the inventory
			if( $item->get( 'transfer_item_status' ) == TRANSFER_ITEM_APPROVED )
			{
				$inventory->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $timestamp, $this->id );
			}

			$item->set( 'transfer_item_status', TRANSFER_ITEM_CANCELLED );
			$item->db_save();
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}

	public function _transact_receipt()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );

		$items = $this->get_items();
		$ci->db->trans_start();
		foreach( $items as $item )
		{
			if( in_array( $item->get( 'transfer_item_status' ), array( TRANSFER_ITEM_SCHEDULED, TRANSFER_ITEM_APPROVED ) ) )
			{
				$inventory = new Inventory();
				$inventory = $inventory->get_by_store_item( $this->destination_id, $item->get( 'item_id' ) );
				if( ! $inventory )
				{ // Current store does not carry item yet, let's create a new inventory record
					$ci->load->library( 'item' );

					$new_item = new Item();
					$new_item = $new_item->get_by_id( $item->get( 'item_id') );
					$inventory = $current_store->add_item( $new_item );
				}

				$quantity = $item->get( 'quantity_received' ); // Item will be added to the inventory
				if( is_null( $quantity ) )
				{
					$quantity = $item->get( 'quantity' );
					$item->set( 'quantity_received', $quantity );
				}
				$inventory->transact( TRANSACTION_TRANSFER_IN, $quantity, $this->receipt_datetime, $this->id );

				$item->set( 'transfer_item_status', TRANSFER_ITEM_RECEIVED );
				$item->db_save();
			}
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}

	public function _transact_voided_items()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$timestamp = date( TIMESTAMP_FORMAT );

		$ci->db->trans_start();
		if( isset( $this->voided_items ) && $this->voided_items )
		{
			foreach( $this->voided_items as $k => $v )
			{
				$inventory = new Inventory();
				$inventory = $inventory->get_by_store_item( $this->origin_id, $k );

				if( $inventory )
				{
					$quantity = $v;
					$inventory->transact( TRANSACTION_TRANSFER_VOID, $quantity, $timestamp, $this->id );
				}
			}
		}
		$ci->db->trans_complete();
	}


	public function approve()
	{
		$ci =& get_instance();

		// Only allow approval from the following previous status:
		$allowed_prev_status = array( TRANSFER_PENDING );
		if( ! in_array( $this->transfer_status, $allowed_prev_status ) )
		{
			die( 'Cannot approve non-pending transfers' );
		}

		// Only the originating store can approve the transfer
		if( $ci->session->current_store_id != $this->origin_id )
		{
			die( sprintf( 'Current store (%s) is not authorize to approve the transfer',
                    $ci->session->current_store_id ) );
		}

		$ci->db->trans_start();
		$this->set( 'transfer_status', TRANSFER_APPROVED );
		$this->set( 'transfer_datetime', date( TIMESTAMP_FORMAT ) );
		$result = $this->db_save();
		if( $result )
		{
			$ci->db->trans_complete();

			if( $ci->db->trans_status() )
			{
				return $this;
			}
			else
			{
				set_message( 'A database error has occurred while trying to approve the record.', 'error' );
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}


	public function cancel()
	{
		$ci =& get_instance();

		// Check for valid previous transfer status
		if( ! in_array( $this->transfer_status, array( TRANSFER_PENDING, TRANSFER_APPROVED ) ) )
		{
			die( 'Cannot cancel transfer. Only pending or approved transfers can be cancelled.');
		}

		// Only the originating store can cancel the transfer
		if( $ci->session->current_store_id != $this->origin_id )
		{
			die( 'Current store is not authorized to cancel the transfer.' );
		}

		//$ci->load->library( 'store' );
		//$current_store = new Store();
		//$current_store = $current_store->get_by_id( $ci->session->current_store_id );

		$ci->db->trans_start();
		$this->set( 'transfer_status', TRANSFER_CANCELLED );
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


	public function receive()
	{
		$ci =& get_instance();

		// Check for valid previous transfer status
		if( ( isset( $this->origin_id ) && $this->transfer_status != TRANSFER_APPROVED )
			|| ( ! isset( $this->origin_id ) && $this->transfer_status != TRANSFER_PENDING ) )
		{
			set_message( 'Cannot receive transfer. Only approved transfers or transfers from an external source can be received.');
			return FALSE;
		}

		// Only destination store can receive the transfer
		if ( $ci->session->current_store_id != $this->destination_id )
		{
			set_message( 'Current store is not authorized to receive the transfer.' );
			return FALSE;
		}

		$ci->db->trans_start();
		$this->set( 'transfer_status', TRANSFER_RECEIVED );
		$result = $this->db_save();
		if( $result )
		{
			$ci->db->trans_complete();
			if( $ci->db->trans_status() )
			{
				return $this;
			}
			else
			{
				set_message( 'A database error has occurred while trying to receive the record', 'error' );
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}

	}


	public function load_from_data( $data = array(), $overwrite = TRUE )
	{
		$ci =& get_instance();

		// Try to get existing value first if ID exists
		if( array_key_exists( 'id', $data ) && $data['id'] )
		{
			$r = $this->get_by_id( $data['id'] );
			$r->get_items( TRUE );
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
			elseif( $field == 'items' )
			{ // load items
				$ci->load->library( 'transfer_item' );

				$this->items = array();
				foreach( $value as $i )
				{
					$item = new Transfer_item();
					$item = $item->load_from_data( $i );
					$item->set_parent( $this );
					$item_id = $item->get( 'id' );
					if( $item_id )
					{ // Previous items are already loaded, find the appropriate item and replace it
						$index = array_value_search( 'id', $item_id, $r->items );
						if( ! is_null( $index ) )
						{
							$r->items[$index] = $item;
						}
						else
						{ // Cannot find previous item, consider as additional item
							$r->items[] = $item;
						}
					}
					else
					{
						$r->items[] = $item;
					}
				}
			}
		}

		return $r;
	}

	public function is_external_receipt()
	{
		return ! isset( $this->origin_id );
	}


	public function is_external_transfer()
	{
		return ! isset( $this->destination_id );
	}
}