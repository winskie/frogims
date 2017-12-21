<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transfer extends Base_model {

	protected $transfer_reference_num;
	protected $transfer_category;
	protected $origin_id;
	protected $origin_name;
	protected $sender_id;
	protected $sender_name;
	protected $sender_shift;
	protected $transfer_datetime;
	protected $transfer_user_id;
	protected $destination_id;
	protected $destination_name;
	protected $recipient_id;
	protected $recipient_name;
	protected $recipient_shift;
	protected $receipt_datetime;
	protected $receipt_user_id;
	protected $transfer_tvm_id;
	protected $transfer_init_shift_id;
	protected $transfer_status;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	protected $previousStatus;
	protected $externalTransfer = FALSE;
	protected $externalReceipt = FALSE;
	protected $items;
	protected $voided_items;
	protected $transfer_validation;

	protected $_quick_receipt = FALSE;
	protected $_save_action = NULL;

	protected $status_log = array(
			'table' => 'transfer_status_log',
			'status_field' => 'transfer_status',
			'prefix' => 'tslog_',
			'foreign_key' => 'transfer_id'
		);

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'transfers';
		$this->db_fields = array(
				'transfer_reference_num' => array( 'type' => 'string' ),
				'transfer_category' => array( 'type' => 'integer' ),
				'origin_id' => array( 'type' => 'integer' ),
				'origin_name' => array( 'type' => 'string' ),
				'sender_id' => array( 'type' => 'integer' ),
				'sender_name' => array( 'type' => 'string' ),
				'sender_shift' => array( 'type' => 'integer' ),
				'transfer_datetime' => array( 'type' => 'datetime' ),
				'transfer_user_id' => array( 'type' => 'integer' ),
				'destination_id' => array( 'type' => 'integer' ),
				'destination_name' => array( 'type' => 'string' ),
				'recipient_id' => array( 'type' => 'integer' ),
				'recipient_name' => array( 'type' => 'string' ),
				'recipient_shift' => array( 'type' => 'integer' ),
				'receipt_datetime' => array( 'type' => 'datetime' ),
				'receipt_user_id' => array( 'type' => 'integer' ),
				'transfer_tvm_id' => array( 'type' => 'string' ),
				'transfer_init_shift_id' => array( 'type' => 'integer' ),
				'transfer_status' => array( 'type' => 'integer' )
			);
		$this->children = array(
				'items' => array( 'table' => 'transfer_items', 'key' => 'transfer_id', 'field' => 'items', 'class' => 'Transfer_item' )
			);
	}


	public function get_transfers( $params = array() )
	{
		$ci =& get_instance();
		$ci->load->library( 'Transfer' );

		$includes = param( $params, 'includes' );

		$date_sent = param( $params, 'sent' );
		$date_received = param( $params, 'received' );
		$source = param( $params, 'src' );
		$destination = param( $params, 'dst' );
		$status = param( $params, 'status' );
		$category = param( $params, 'category' );
		$validation_status = param( $params, 'validation_status' );

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'transfer_datetime DESC, t.id DESC' );

		$select = 't.*';

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}

		if( $date_sent )
		{
			$ci->db->where( 'DATE(transfer_datetime)', $date_sent );
		}

		if( $date_received )
		{
			$ci->db->where( 'DATE(receipt_datetime)', $date_received );
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

		if( $category )
		{
			$ci->db->where( 'transfer_category', $category );
		}

		if( $validation_status )
		{
			if( $validation_status == '_null_' )
			{
				$ci->db->where( 'transval_status IS NULL' );
			}
			else
			{
				$ci->db->where( 'transval_status', $validation_status );
			}
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


	public function get_transfer_array()
	{
		$transfer_data = $this->as_array();
		$items = $this->get_items();
		$items_data = array();
		foreach( $items as $item )
		{
			$items_data[] = $item->as_array( array(
					'item_name' => array( 'type', 'string' ),
					'item_description' => array( 'type', 'string' ),
					'item_unit' => array( 'type', 'string' ),
					'cat_description' => array( 'type', 'string' ) ) );
		}
		$transfer_data['items'] = $items_data;

		return $transfer_data;
	}


	public function count_transfers( $params = array() )
	{
		$ci =& get_instance();
		$ci->load->library( 'Transfer' );

		$includes = param( $params, 'includes' );
		$date_sent = param( $params, 'sent' );
		$date_received = param( $params, 'received' );
		$source = param( $params, 'src' );
		$destination = param( $params, 'dst' );
		$status = param( $params, 'status' );
		$validation_status = param( $params, 'validation_status' );

		if( $date_sent )
		{
			$ci->db->where( 'DATE(transfer_datetime)', $date_sent );
		}

		if( $date_received )
		{
			$ci->db->where( 'DATE(receipt_datetime)', $date_received );
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

		if( $validation_status )
		{
			if( $validation_status == '_null_' )
			{
				$ci->db->where( 'transval_status IS NULL' );
			}
			else
			{
				$ci->db->where( 'transval_status', $validation_status );
			}
		}

		if( $includes )
		{
			if( in_array( 'validation', $includes ) )
			{
				$ci->db->join( 'transfer_validations AS tv', 'tv.transval_transfer_id = t.id', 'left' );
			}
		}

		$count = $ci->db->count_all_results( 'transfers t' );

		return $count;
	}


	public function count_pending_transfers( $params = array() )
	{
		$includes = param( $params, 'includes' );
		$date_sent = param( $params, 'sent' );
		$date_received = param( $params, 'received' );
		$source = param( $params, 'src' );
		$destination = param( $params, 'dst' );

		$ci =& get_instance();

		if( $date_sent )
		{
			$ci->db->where( 'DATE(transfer_datetime)', $date_sent );
		}

		if( $date_received )
		{
			$ci->db->where( 'DATE(receipt_datetime)', $date_received );
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

		if( $includes )
		{
			if( in_array( 'validation', $includes ) )
			{
				$ci->db->join( 'transfer_validations AS tv', 'tv.transval_transfer_id = t.id', 'left' );
				$ci->db->where( 'tv.transval_status', TRANSFER_VALIDATION_ONGOING );
			}
			else
			{
				$ci->db->where( 'transfer_status < -1' );
			}
		}

		$count = $ci->db->count_all_results( 'transfers t' );

		return $count;
	}


	public function get_items( $force = FALSE )
	{
		$ci =& get_instance();

		if( isset( $this->items ) && !$force )
		{
			return $this->items;
		}
		else
		{
			$ci->load->library( 'transfer_item' );
			$select = 'ti.*, i.item_name, i.item_description, i.item_unit, i.item_class, c.cat_description, c.cat_module, ip.iprice_unit_price,
					IF(i.item_class = "cash", ti.quantity * ip.iprice_unit_price, IF( i.base_item_id IS NULL, ti.quantity, ti.quantity * ct.conversion_factor ) ) AS total_amount';
			if( $this->transfer_category == TRANSFER_CATEGORY_TURNOVER )
			{
				$select .= ', ac.cat_description AS allocation_cat_description, tc.cat_description AS transfer_cat_description';
			}

			$ci->db->select( $select, false );
			$ci->db->where( 'ti.transfer_id', $this->id );
			$ci->db->join( 'items i', 'i.id = ti.item_id', 'left' );
			$ci->db->join( 'categories c', 'c.id = ti.transfer_item_category_id', 'left' );
			$ci->db->join( 'item_prices ip', 'ip.iprice_item_id = ti.item_id', 'left' );
			$ci->db->join( 'conversion_table ct', 'ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id', 'left' );

			if( $this->transfer_category == TRANSFER_CATEGORY_TURNOVER )
			{
				$ci->db->join( 'allocation_items AS ai', 'ai.id = ti.transfer_item_allocation_item_id', 'left' );
				$ci->db->join( 'categories AS ac', 'ac.id = ai.allocation_category_id', 'left' );
				$ci->db->join( 'transfer_items AS ti2', 'ti2.id = ti.transfer_item_transfer_item_id', 'left' );
				$ci->db->join( 'categories AS tc', 'tc.id = ti2.transfer_item_category_id', 'left' );
			}
			$query = $ci->db->get( 'transfer_items ti' );
			$this->items = $query->result( 'Transfer_item' );
		}

		return $this->items;
	}


	public function get_validation( $force = FALSE )
	{
		$ci =& get_instance();

		if( ! isset( $this->transfer_validation ) || $force )
		{
			$ci->load->library( 'transfer_validation' );
			$ci->db->where( 'transval_transfer_id', $this->id );
			$ci->db->limit( 1 );
			$query = $ci->db->get( 'transfer_validations' );
			$this->transfer_validation = $query->row( 0, 'Transfer_validation' );
		}

		return $this->transfer_validation;
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

		// Check if status is valid for new records
		$valid_new_status = array( TRANSFER_PENDING, TRANSFER_APPROVED, TRANSFER_RECEIVED );
		if( is_null( $this->id ) && ! in_array( $this->transfer_status, $valid_new_status ) )
		{
			set_message( 'Invalid transfer status for new record', 'error' );
			return FALSE;
		}

		// Check if transfer has items to transfer
		if( ! $items )
		{
			set_message( 'Transfer does not contain any items', 'error' );
			return FALSE;
		}

		// Check if transfer has valid items to transfer
		$has_valid_transfer_item = false;
		$voided_items = array();
		foreach( $items as $item )
		{
			if( ! $has_valid_transfer_item
				&& ! in_array( $item->get( 'transfer_item_status' ), array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) )
				&& $item->get( 'quantity' ) > 0 )
			{
				$has_valid_transfer_item = true;
			}

			if( array_key_exists( 'transfer_item_status', $item->db_changes )
				&& $item->db_changes['transfer_item_status'] == TRANSFER_ITEM_VOIDED
				&& $item->get( 'previousStatus' ) == TRANSFER_ITEM_APPROVED  )
			{
				$item_id = $item->get( 'item_id' );
				$voided_items[] = $item;
			}
		}
		if( ! $has_valid_transfer_item )
		{
			set_message( 'Transfer does not contain any valid items', 'error' );
			return FALSE;
		}

		$this->voided_items = $voided_items;

		// Add TVMIR Refund and Issue TMVIR Refund must have transfer_trm_id value
		$tvmir_categories = array( TRANSFER_CATEGORY_ADD_TVMIR, TRANSFER_CATEGORY_ISSUE_TVMIR );
		if( in_array( $this->transfer_category, $tvmir_categories ) && empty( $this->transfer_tvm_id ) )
		{
			set_message( 'Transfer category requires TVM ID to be specified', 'error' );
			return FALSE;
		}

		return TRUE;
	}


	public function db_save()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$Inventory = new Inventory();

		$result = NULL;
		$ci->db->trans_start();

		$no_reservation_categories = array();

		$parent_item_id = NULL; // Item ID of fund that will be reserved
		$current_store = current_store();

		if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM )
		{
			switch( $this->transfer_category )
			{
				case TRANSFER_CATEGORY_BILLS_TO_COINS:
					// Deduct from Change Fund
					if( $this->_save_action == 'transfer_approve' )
					{
						$change_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CHANGE_FUND );
					}
					elseif( $this->_save_action == 'receipt' )
					{
						$change_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_CHANGE_FUND );
					}
					else
					{ // Scheduled
						if( $this->origin_id == current_store( TRUE ) )
						{
							$change_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CHANGE_FUND );
						}
						elseif( $this->destination_id == current_store( TRUE ) )
						{
							$change_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_CHANGE_FUND );
						}
					}
					$parent_item_id = $change_fund->get( 'item_id' );
					break;

				case TRANSFER_CATEGORY_CSC_APPLICATION:
					// Deduct from CSC Card Fee Fund
					if( $this->_save_action == 'transfer_approve' )
					{
						$csc_card_fee_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CSC_CARD_FEE );
					}
					elseif( $this->_save_action == 'receipt' )
					{
						$csc_card_fee_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_CSC_CARD_FEE );
					}
					else
					{ // Scheduled
						if( $this->origin_id == current_store( TRUE ) )
						{
							$csc_card_fee_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CSC_CARD_FEE );
						}
						elseif( $this->destination_id == current_store( TRUE ) )
						{
							$csc_card_fee_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_CSC_CARD_FEE );
						}
					}
					$parent_item_id =  $csc_card_fee_fund->get( 'item_id' );
					break;

				case TRANSFER_CATEGORY_BANK_DEPOSIT:
					// Deduct from Sales Collection Fund
					$sales_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_SALES );
					$parent_item_id = $sales_fund->get( 'item_id' );
					break;

				case TRANSFER_CATEGORY_ADD_TVMIR:
					// Deduct from Sales Collection Fund
					$sales_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_SALES );
					$parent_item_id = $sales_fund->get( 'item_id' );
					break;

				case TRANSFER_CATEGORY_ISSUE_TVMIR:
					// Deduct from TVMIR Fund
					$tvmir_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_TVMIR );
					$parent_item_id = $tvmir_fund->get( 'item_id' );
					break;

				case TRANSFER_CATEGORY_REPLENISH_TVM_CFUND:
					// Deduct from TVMIR Fund
					$sales_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_SALES );
					$parent_item_id = $sales_fund->get( 'item_id' );
					break;

				default:
					// Deduct from Change Fund
					if( $this->_save_action == 'transfer_approve' )
					{
						$change_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CHANGE_FUND );
					}
					elseif( $this->_save_action == 'receipt' || $this->_save_action == 'quick_receipt' )
					{
						$change_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_CHANGE_FUND );
					}
					else
					{
						if( $this->origin_id == current_store( TRUE ) )
						{
							$change_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CHANGE_FUND );
						}
						elseif( $this->destination_id == current_store( TRUE ) )
						{
							$change_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_CHANGE_FUND );
						}
					}
					$parent_item_id = $change_fund->get( 'item_id' );
			}
		}

		if( isset( $this->id ) )
		{ // Update transfer record
			if( $this->_check_data() )
			{
				foreach( $this->items as $transfer_item )
				{
					$item = $transfer_item->get_item();
					$quantity = $transfer_item->get( 'quantity' );
					$skip_inventory = false;

					// Update reservation
					if( array_key_exists( 'transfer_item_status', $transfer_item->db_changes ) )
					{ // Transfer item status updated
						if( in_array( $this->transfer_category, $no_reservation_categories ) )
						{
							continue;
						}

						if( $transfer_item->db_changes['transfer_item_status']  == TRANSFER_ITEM_SCHEDULED )
						{ // New scheduled item, reserve
							if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM && $item->get( 'item_class' ) == 'cash' && ! empty( $parent_item_id ) )
							{
								$item_unit_price = $item->get( 'iprice_unit_price' );
								$amount = $quantity * $item_unit_price;
								$skip_inventory = true;

								$inventory = $Inventory->get_by_store_item( $this->origin_id, $parent_item_id );
								$inventory->reserve( $amount ); // Assumes inventory is a fund!
								$sub_inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $parent_item_id );
								$sub_inventory->reserve( $quantity );
							}

							if( ! $skip_inventory )
							{
								$inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ) );
								$inventory->reserve( $quantity );
							}
						}
						elseif( in_array( $transfer_item->db_changes['transfer_item_status'], array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ) )
						{ // Cancelled or voided item
							if( $transfer_item->get( 'previousStatus' ) == TRANSFER_ITEM_SCHEDULED )
							{ // Previously scheduled, cancel reservation
								if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM && $transfer_item->get( 'item_class' ) == 'cash' && ! empty( $parent_item_id ) )
								{
									$item_unit_price = $item->get( 'iprice_unit_price' );
									$amount = $quantity * $item_unit_price;
									$skip_inventory = true;

									$inventory = $Inventory->get_by_store_item( $this->origin_id, $parent_item_id );
									$inventory->reserve( $amount * -1 ); // Assumes inventory is a fund!
									$sub_inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $parent_item_id );
									$sub_inventory->reserve( $quantity * -1 );
								}

								if( ! $skip_inventory )
								{
									$inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ) );
									$inventory->reserve( $quantity * -1 );
								}
							}
						}
					}

					if( array_key_exists( 'transfer_status', $this->db_changes )
						&& $this->db_changes['transfer_status'] == TRANSFER_PENDING_CANCELLED
						&& $this->previousStatus == TRANSFER_PENDING )
					{ // Scheduled transfer cancelled, cancel reserved items
						if( !in_array( $transfer_item->get( 'transfer_item_status' ), array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ) )
						{ // do not unreserved items that are already cancelled or voided
							if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM && $item->get( 'item_class' ) == 'cash' && ! empty( $parent_item_id ) )
							{
								$item_unit_price = $item->get( 'iprice_unit_price' );
								$amount = $quantity * $item_unit_price;
								$skip_inventory = true;

								$inventory = $Inventory->get_by_store_item( $this->origin_id, $parent_item_id );
								$inventory->reserve( $amount * -1 );
								$sub_inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $parent_item_id );
								$sub_inventory->reserve( $quantity * -1 );
							}

							if( ! $skip_inventory )
							{
								$inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ) );
								$inventory->reserve( $quantity * -1 );
							}
						}
					}

					if( ! $transfer_item->get( 'id' ) )
					{
						$transfer_item->set( 'transfer_id', $this->id );
					}

					if( $transfer_item->db_changes )
					{
						if( !$transfer_item->db_save() )
						{
							$ci->db->trans_rollback();
							return FALSE;
						}
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

						case TRANSFER_PENDING_CANCELLED:
						case TRANSFER_APPROVED_CANCELLED:
							$pre_cancellation_status = array( TRANSFER_PENDING, TRANSFER_APPROVED );
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
					foreach( $this->items as $transfer_item )
					{
						$item = $transfer_item->get_item();
						$quantity = $transfer_item->get( 'quantity' );
						$skip_inventory = false;

						if( in_array( $this->transfer_category, $no_reservation_categories ) )
						{
							continue;
						}

						if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM && $item->get( 'item_class' ) == 'cash' && ! empty( $parent_item_id ) )
						{
							$item_unit_price = $item->get( 'iprice_unit_price' );
							$amount = $quantity * $item_unit_price;
							$skip_inventory = true;

							$inventory = $Inventory->get_by_store_item( $this->origin_id, $parent_item_id );
							$inventory->reserve( $amount ); // Assumes inventory is a fund!
							$sub_inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $parent_item_id );
							$sub_inventory->reserve( $quantity );
						}

						if( ! $skip_inventory )
						{
							$inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $parent_item_id, TRUE );
							$inventory->reserve( $quantity );
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
					if( !$item->db_save() )
					{
						$ci->db->trans_rollback();
						return FALSE;
					}
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

			$current_store = current_store();

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

					// transfer user ID - should be the currently logged in user's ID
					$this->set( 'transfer_user_id', $current_user->get( 'id' ) );

					// Sender name - if not specified should be currently logged in user
					//$sender_name = isset( $this->sender_name ) ? $this->sender_name : $current_user->get( 'full_name' );
					//$this->set( 'sender_name', $sender_name );

					// Update sender's Shift
					$this->set( 'sender_shift', $current_shift );

					// Transfer date/time - if not specified should be now
					if( ! $this->transfer_datetime )
					{
						$this->set( 'transfer_datetime', $date( TIMESTAMP_FORMAT ) );
					}

					// Make sure TVM ID is only used in TVMIR related transfers
					$tvmir_categories = array( TRANSFER_CATEGORY_ADD_TVMIR, TRANSFER_CATEGORY_ISSUE_TVMIR, TRANSFER_CATEGORY_REPLENISH_TVM_CFUND );
					if( ! in_array( $this->transfer_category, $tvmir_categories ) )
					{
						$this->set( 'transfer_tvm_id', NULL );
					}

					break;

				case TRANSFER_PENDING_CANCELLED:
				case TRANSFER_APPROVED_CANCELLED:
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

					// Receipt user ID - should be the currently logged in user's ID
					$this->set( 'receipt_user_id', $current_user->get( 'id' ) );

					// Recipient name - if not specified should be currently logged in user's name
					if( empty( $this->recipient_name ) )
					{
						$this->set( 'recipient_id', $current_user->get( 'id' ) );
						$this->set( 'recipient_name', $current_user->get( 'full_name' ) );
					}

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

		if( empty( $this->transfer_init_shift_id ) )
		{
			$this->set( 'transfer_init_shift_id', current_shift( TRUE ) );
		}

		return TRUE;
	}


	public function _transact_approval()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$Inventory = new Inventory();
		$transfer_items = $this->get_items();

		$current_store = current_store();

		$change_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CHANGE_FUND );
		$in_transit_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_IN_TRANSIT );
		$csc_card_fee_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CSC_CARD_FEE );
		$sales_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_SALES );
		$tvmir_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_TVMIR );

		$transaction_datetime = $this->transfer_datetime;
		//$transaction_datetime = date( TIMESTAMP_FORMAT );

		$ci->db->trans_start();
		foreach( $transfer_items as $transfer_item )
		{
			if( in_array( $transfer_item->get( 'transfer_item_status' ), array( TRANSFER_ITEM_SCHEDULED ) ) )
			{
				$item = $transfer_item->get_item();
				$quantity = $transfer_item->get( 'quantity' );
				$skip_inventory = false;

				if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM && $item->get( 'item_class' ) == 'cash' )
				{
					$item_unit_price = $item->get( 'iprice_unit_price' );
					if( empty( $item_unit_price ) )
					{
						die( 'Empty item unit price. Please contact the system administrator' );
					}
					$amount = $quantity * $item_unit_price;

					// Skip inventory for cashroom
					$skip_inventory = true;

					switch( intval( $this->transfer_category ) )
					{
						case TRANSFER_CATEGORY_BILLS_TO_COINS:
							// Add to In Transit Fund
							$in_transit_fund->transact( TRANSACTION_TRANSFER_IN, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$in_transit_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $in_transit_fund->get( 'item_id' ), TRUE );
							$in_transit_fund_sub->transact( TRANSACTION_TRANSFER_IN, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );

							// Deduct from Change Fund
							$change_fund->transact( TRANSACTION_TRANSFER_OUT, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$change_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
							$change_fund_sub->transact( TRANSACTION_TRANSFER_OUT, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sub_parent_id = $change_fund->get( 'item_id' );
							break;

						case TRANSFER_CATEGORY_CSC_APPLICATION:
							// Deduct from CSC Card Fee Fund
							$csc_card_fee_fund->transact( TRANSACTION_TRANSFER_OUT, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$csc_card_fee_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $csc_card_fee_fund->get( 'item_id' ), TRUE );
							$csc_card_fee_fund_sub->transact( TRANSACTION_TRANSFER_OUT, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sub_parent_id = $csc_card_fee_fund->get( 'item_id' );
							break;

						case TRANSFER_CATEGORY_BANK_DEPOSIT:
							// Deduct from Sales Collection Fund
							$sales_fund->transact( TRANSACTION_TRANSFER_OUT, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sales_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $sales_fund->get( 'item_id' ), TRUE );
							$sales_fund_sub->transact( TRANSACTION_TRANSFER_OUT, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sub_parent_id = $sales_fund->get( 'item_id' );
							break;

						case TRANSFER_CATEGORY_ADD_TVMIR:
							// Deduct from Sales Collection Fund
							$sales_fund->transact( TRANSACTION_TRANSFER_OUT, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sales_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $sales_fund->get( 'item_id' ), TRUE );
							$sales_fund_sub->transact( TRANSACTION_TRANSFER_OUT, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sub_parent_id = $sales_fund->get( 'item_id' );

							// Transfer to TVMIR Fund
							$tvmir_fund->transact( TRANSACTION_TRANSFER_IN, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$tvmir_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $tvmir_fund->get( 'item_id' ), TRUE );
							$tvmir_fund_sub->transact( TRANSACTION_TRANSFER_IN, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							break;

						case TRANSFER_CATEGORY_ISSUE_TVMIR:
							// Deduct from TVMIR Fund
							$tvmir_fund->transact( TRANSACTION_TRANSFER_OUT, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$tvmir_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $tvmir_fund->get( 'item_id' ), TRUE );
							$tvmir_fund_sub->transact( TRANSACTION_TRANSFER_OUT, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sub_parent_id = $tvmir_fund->get( 'item_id' );
							break;

						case TRANSFER_CATEGORY_REPLENISH_TVM_CFUND:
							// Deduct from Sales Collection Fund
							$sales_fund->transact( TRANSACTION_TRANSFER_OUT, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sales_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $sales_fund->get( 'item_id' ), TRUE );
							$sales_fund_sub->transact( TRANSACTION_TRANSFER_OUT, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sub_parent_id = $sales_fund->get( 'item_id' );

							// Transfer to Change Fund
							$change_fund->transact( TRANSACTION_TRANSFER_IN, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$change_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
							$change_fund_sub->transact( TRANSACTION_TRANSFER_IN, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							break;


						default:
							// Deduct from Change Fund
							$change_fund->transact( TRANSACTION_TRANSFER_OUT, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$change_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
							$change_fund_sub->transact( TRANSACTION_TRANSFER_OUT, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sub_parent_id = $change_fund->get( 'item_id' );
					}
				}

				if( ! $skip_inventory )
				{ // Deduct from inventory
					$inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), NULL, TRUE );
					$inventory->reserve( $quantity * -1 );
					$inventory->transact( TRANSACTION_TRANSFER_OUT, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
					if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM && $item->get( 'item_class' ) == 'cash' && ! empty( $sub_parent_item_id ) )
					{
						$sub_inventory = $Inventory->get_by_store_item( $this->origin_id, $item->get( 'item_id' ), $sub_parent_item_id );
						$sub_inventory->reserve( $quantity * -1 );
					}
				}

				$transfer_item->set( 'transfer_item_status', TRANSFER_ITEM_APPROVED );
				$transfer_item->db_save();
			}
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}


	public function _transact_cancellation()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$Inventory = new Inventory();
		$transfer_items = $this->get_items();

		$current_store = current_store();

		$change_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CHANGE_FUND );
		$in_transit_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_IN_TRANSIT );
		$csc_card_fee_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_CSC_CARD_FEE );
		$sales_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_SALES );
		$tvmir_fund = $Inventory->get_by_store_item_name( $this->origin_id, FUND_TVMIR );

		// TODO: Should this be on the day of the transfer or on the date it was cancelled?
		$transaction_datetime = $this->transfer_datetime;
		//$transaction_datetime = date( TIMESTAMP_FORMAT );

		$ci->db->trans_start();
		foreach( $transfer_items as $transfer_item )
		{
			$item = $transfer_item->get_item();
			$quantity = $transfer_item->get( 'quantity' );
			$skip_inventory = false;

			if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM
					&& $item->get( 'item_class' ) == 'cash' )
			{ // Skip inventory for cashroom
				$skip_inventory = true;
			}

			if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM
					&& $item->get( 'item_class' ) == 'cash'
					&& $transfer_item->get( 'transfer_item_status' ) == TRANSFER_ITEM_APPROVED )
			{
				$item_unit_price = $item->get( 'iprice_unit_price' );
				if( empty( $item_unit_price ) )
				{
					die( 'Empty item unit price. Please contact the system administrator' );
				}
				$amount = $quantity * $item_unit_price;

				switch( intval( $this->transfer_category ) )
				{
					case TRANSFER_CATEGORY_BILLS_TO_COINS:
						// Deduct from In Transit Fund
						$in_transit_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$in_transit_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $in_transit_fund->get( 'item_id' ), TRUE );
						$in_transit_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );

						// Return to Change Fund
						$change_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$change_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
						$change_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						break;

					case TRANSFER_CATEGORY_CSC_APPLICATION:
						// Return to CSC Card Fee Fund
						$csc_card_fee_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$csc_card_fee_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $csc_card_fee_fund->get( 'item_id' ), TRUE );
						$csc_card_fee_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						break;

					case TRANSFER_CATEGORY_BANK_DEPOSIT:
						// Return to Sales Collection Fund
						$sales_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$sales_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $sales_fund->get( 'item_id' ), TRUE );
						$sales_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						break;

					case TRANSFER_CATEGORY_ADD_TVMIR:
						// Deduct from TVMIR Fund
						$tvmir_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$tvmir_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $tvmir_fund->get( 'item_id' ), TRUE );
						$tvmir_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );

						// Return to Sales Collection Fund
						$sales_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$sales_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $sales_fund->get( 'item_id' ), TRUE );
						$sales_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						break;

					case TRANSFER_CATEGORY_ISSUE_TVMIR:
						// Return to TVMIR Fund
						$tvmir_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$tvmir_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $tvmir_fund->get( 'item_id' ), TRUE );
						$tvmir_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						break;

					case TRANSFER_CATEGORY_REPLENISH_TVM_CFUND:
						// Deduct from Change Fund
						$change_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$change_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
						$change_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );

						// Return to Sales Collection Fund
						$sales_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$sales_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $sales_fund->get( 'item_id' ), TRUE );
						$sales_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						break;

					default:
						// Return to Change Fund
						$change_fund->transact( TRANSACTION_TRANSFER_CANCEL, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$change_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
						$change_fund_sub->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
				}
			}

			if( ! $skip_inventory )
			{
				$inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), NULL, TRUE );
				if( $transfer_item->get( 'transfer_item_status' ) == TRANSFER_ITEM_APPROVED )
				{
					$inventory->transact( TRANSACTION_TRANSFER_CANCEL, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
				}
			}

			if( in_array( $transfer_item->get( 'transfer_item_status' ), array( TRANSFER_ITEM_SCHEDULED, TRANSFER_ITEM_APPROVED ) ) )
			{
				$transfer_item->set( 'transfer_item_status', TRANSFER_ITEM_CANCELLED );
			}
			$transfer_item->db_save();
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}


	public function _transact_receipt()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$Inventory = new Inventory();
		$transfer_items = $this->get_items();

		$current_store = current_store();

		$change_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_CHANGE_FUND );
		$in_transit_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_IN_TRANSIT );
		$csc_card_fee_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_CSC_CARD_FEE );
		$sales_fund = $Inventory->get_by_store_item_name( $this->destination_id, FUND_SALES );

		$transaction_datetime = $this->receipt_datetime;
		//$transaction_datetime = date( TIMESTAMP_FORMAT );
		$ci->db->trans_start();

		foreach( $transfer_items as $transfer_item )
		{
			if( in_array( $transfer_item->get( 'transfer_item_status' ), array( TRANSFER_ITEM_SCHEDULED, TRANSFER_ITEM_APPROVED ) ) )
			{
				$item = $transfer_item->get_item();
				if( $this->_quick_receipt )
				{ // Quantity received is always equal to quantity sent
					$quantity = $transfer_item->get( 'quantity' );
					$transfer_item->set( 'quantity_received', $quantity );
				}
				else
				{
					$quantity = $transfer_item->get( 'quantity_received' ); // Item will be added to the inventory
				}
				$skip_inventory = false;

				if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM && $item->get( 'item_class' ) == 'cash' )
				{
					$item_unit_price = $item->get( 'iprice_unit_price' );
					if( empty( $item_unit_price ) )
					{
						die( 'Empty item unit price. Please contact the system administrator' );
					}
					$amount = $quantity * $item_unit_price;

					// Skip inventory for cashroom
					$skip_inventory = true;

					switch( intval( $this->transfer_category ) )
					{
						case TRANSFER_CATEGORY_BILLS_TO_COINS:
						// Deduct from In Transit Fund
						$in_transit_fund->transact( TRANSACTION_TRANSFER_IN, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$in_transit_fund_sub = $Inventory->get_by_store_item( $this->destination_id, $transfer_item->get( 'item_id' ), $in_transit_fund->get( 'item_id' ), TRUE );
						$in_transit_fund_sub->transact( TRANSACTION_TRANSFER_IN, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );

						// Add to Change Fund
						$change_fund->transact( TRANSACTION_TRANSFER_IN, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$change_fund_sub = $Inventory->get_by_store_item( $this->destination_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
						$change_fund_sub->transact( TRANSACTION_TRANSFER_IN, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						break;

					case TRANSFER_CATEGORY_CSC_APPLICATION:
						// Add to CSC Card Fee Fund
						$csc_card_fee_fund->transact( TRANSACTION_TRANSFER_IN, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$csc_card_fee_fund_sub = $Inventory->get_by_store_item( $this->destination_id, $transfer_item->get( 'item_id' ), $csc_card_fee_fund->get( 'item_id' ), TRUE );
						$csc_card_fee_fund_sub->transact( TRANSACTION_TRANSFER_IN, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						break;

					default:
						// Add to Change Fund
						$change_fund->transact( TRANSACTION_TRANSFER_IN, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
						$change_fund_sub = $Inventory->get_by_store_item( $this->destination_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
						$change_fund_sub->transact( TRANSACTION_TRANSFER_IN, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
					}
				}

				if( ! $skip_inventory )
				{
					$inventory = $Inventory->get_by_store_item( $this->destination_id, $transfer_item->get( 'item_id' ), NULL, TRUE );
					if( $inventory )
					{
						$inventory->transact( TRANSACTION_TRANSFER_IN, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
					}
				}

				$transfer_item->set( 'transfer_item_status', TRANSFER_ITEM_RECEIVED );
				$transfer_item->db_save();
			}
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}


	public function _transact_voided_items()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$ci->load->library( 'transfer_item' );
		$Inventory = new Inventory();

		// TODO: Should this be the same as the trasfer date or the date when the transfer is voided?
		$transaction_datetime = $this->transfer_datetime;
		// $transaction_datetime = date( TIMESTAMP_FORMAT );

		$ci->db->trans_start();
		if( isset( $this->voided_items ) && $this->voided_items )
		{
			foreach( $this->voided_items as $transfer_item )
			{
				$item = $transfer_item->get_item();
				$quantity = $transfer_item->get( 'quantity' );
				$skip_inventory = false;

				if( $current_store->get( 'store_type' ) == STORE_TYPE_CASHROOM
						&& $item->get( 'item_class' ) == 'cash'
						&& $transfer_item->get( 'transfer_item_status' ) == TRANSFER_ITEM_APPROVED )
				{
					$item_unit_price = $item->get( 'iprice_unit_price' );
					if( empty( $item_unit_price ) )
					{
						die( 'Empty item unit price. Please contact the system administrator' );
					}
					$amount = $quantity * $item_unit_price;

					// Skip inventory for cashroom
					$skip_inventory = true;

					switch( intval( $this->transfer_category ) )
					{
						case TRANSFER_CATEGORY_BILLS_TO_COINS:
							// Deduct from In Transit Fund
							$in_transit_fund->transact( TRANSACTION_TRANSFER_VOID, $amount * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$in_transit_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $in_transit_fund->get( 'item_id' ), TRUE );
							$in_transit_fund_sub->transact( TRANSACTION_TRANSFER_VOID, $quantity * -1, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );

							// Return to Change Fund
							$change_fund->transact( TRANSACTION_TRANSFER_VOID, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$change_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
							$change_fund_sub->transact( TRANSACTION_TRANSFER_VOID, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							break;

						case TRANSFER_CATEGORY_CSC_APPLICATION:
							// Return to CSC Card Fee Fund
							$csc_card_fee_fund->transact( TRANSACTION_TRANSFER_VOID, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$csc_card_fee_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $csc_card_fee_fund->get( 'item_id' ), TRUE );
							$csc_card_fee_fund_sub->transact( TRANSACTION_TRANSFER_VOID, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							break;

						case TRANSFER_CATEGORY_BANK_DEPOSIT:
							// Return to Sales Collection Fund
							$sales_fund->transact( TRANSACTION_TRANSFER_VOID, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$sales_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $sales_fund->get( 'item_id' ), TRUE );
							$sales_fund_sub->transact( TRANSACTION_TRANSFER_VOID, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							break;

						default:
							// Return to Change Fund
							$change_fund->transact( TRANSACTION_TRANSFER_VOID, $amount, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
							$change_fund_sub = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), $change_fund->get( 'item_id' ), TRUE );
							$change_fund_sub->transact( TRANSACTION_TRANSFER_VOID, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
					}
				}

				if( ! $skip_inventory )
				{
					$inventory = $Inventory->get_by_store_item( $this->origin_id, $transfer_item->get( 'item_id' ), NULL, TRUE );
					if( $transfer_item->get( 'transfer_item_status' ) == TRANSFER_ITEM_APPROVED )
					{
						$inventory->transact( TRANSACTION_TRANSFER_VOID, $quantity, $transaction_datetime, $this->id, $transfer_item->get( 'id' ), $transfer_item->get( 'transfer_item_category_id' ) );
					}
				}

				if( in_array( $transfer_item->get( 'transfer_item_status' ), array( TRANSFER_ITEM_SCHEDULED, TRANSFER_ITEM_APPROVED ) ) )
				{
					$transfer_item->set( 'transfer_item_status', TRANSFER_ITEM_CANCELLED );
				}
				$transfer_item->db_save();
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
			set_message( 'Cannot approve non-pending transfers' );
			return FALSE;
		}

		// Only the originating store can approve the transfer
		if( $ci->session->current_store_id != $this->origin_id )
		{
			set_message( sprintf( 'Current store (%s) is not authorize to approve the transfer', $ci->session->current_store_id ) );
			return FALSE;
		}

		$ci->db->trans_start();
		$this->set( 'transfer_status', TRANSFER_APPROVED );
		$this->set( 'transfer_datetime', date( TIMESTAMP_FORMAT ) );
		$this->_save_action = 'transfer_approve';
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
			set_message( 'Cannot cancel transfer. Only pending or approved transfers can be cancelled.' );
			return FALSE;
		}

		// Only the originating store can cancel the transfer
		if( $ci->session->current_store_id != $this->origin_id )
		{
			set_message( 'Current store is not authorized to cancel the transfer.' );
			return FALSE;
		}

		// If this is a validated transfer, only returned transfers can be cancelled
		if( $this->get_validation() )
		{
			if( $this->transfer_validation->get( 'transval_status' ) != TRANSFER_VALIDATION_NOTREQUIRED
					&& $this->transfer_validation->get( 'transval_receipt_status' ) != TRANSFER_VALIDATION_RECEIPT_RETURNED )
			{
				set_message( 'Cannot cancel transfer - Receipt already validated' );
				return FALSE;
			}
		}

		//$ci->load->library( 'store' );
		//$current_store = new Store();
		//$current_store = $current_store->get_by_id( $ci->session->current_store_id );

		$ci->db->trans_start();
		if( $this->transfer_status == TRANSFER_PENDING )
		{
			$this->set( 'transfer_status', TRANSFER_PENDING_CANCELLED );
		}
		elseif( $this->transfer_status == TRANSFER_APPROVED )
		{
			$this->set( 'transfer_status', TRANSFER_APPROVED_CANCELLED );
		}
		$this->_save_action = 'cancel';
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


	public function receive( $quick_receipt = FALSE )
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
		$this->_quick_receipt = $quick_receipt;
		$this->_save_action = $quick_receipt ? 'quick_receipt' : 'receipt';
		$result = $this->db_save();
		$this->_quick_receipt = FALSE;
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