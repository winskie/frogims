<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Allocation extends Base_model {

	protected $store_id;
	protected $business_date;
	protected $shift_id;
	protected $station_id;
	protected $assignee;
	protected $assignee_type;
	protected $cashier_id;
	protected $allocation_status;

	protected $allocations;
	protected $cash_allocations;
	protected $remittances;
	protected $cash_remittances;
	protected $ticket_sales;
	protected $sales;
	protected $cash_reports;

	protected $previousStatus;
	protected $voided_allocations;
	protected $voided_remittances;
	protected $voided_cash_allocations;
	protected $voided_cash_remittances;
	protected $voided_ticket_sales;
	protected $voided_sales;

	protected $has_valid_allocation_item;
	protected $has_valid_remittance_item;
	protected $has_valid_ticket_sale_item;
	protected $has_valid_sales_item;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	protected $status_log = array(
			'table' => 'allocation_status_log',
			'status_field' => 'allocation_status',
			'prefix' => 'alloclog_',
			'foreign_key' => 'allocation_id'
		);

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'allocations';
		$this->db_fields = array(
				'store_id' => array( 'type' => 'integer' ),
				'business_date' => array( 'type' => 'date' ),
				'shift_id' => array( 'type' => 'integer' ),
				'station_id' => array( 'type' => 'integer' ),
				'assignee' => array( 'type' => 'string' ),
				'assignee_type' => array( 'type' => 'integer' ),
				'cashier_id' => array( 'type' => 'integer' ),
				'allocation_status' => array( 'type' => 'integer' )
			);
		$this->children = array(
				'allocations' => array( 'table' => 'allocation_items', 'key' => 'allocation_id', 'field' => 'allocations', 'class' => 'Allocation_item' )
			);
	}

	public function get_allocations( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->allocations ) || $force )
		{
			$ci->load->library( 'allocation_item' );
			$ci->db->select( 'ai.*, c.cat_description, c.cat_module,
					i.item_name, i.item_description, i.item_class, i.teller_allocatable, i.machine_allocatable,
					s.shift_num AS cashier_shift_num,
					( ai.allocated_quantity * ct.conversion_factor ) AS base_quantity' );
			$ci->db->where( 'allocation_id', $this->id );
			$ci->db->where( 'ai.allocation_item_type', 1 );
			$ci->db->where( 'i.item_class', 'ticket' );
			$ci->db->join( 'items i', 'i.id = ai.allocated_item_id', 'left' );
			$ci->db->join( 'categories c', 'c.id = ai.allocation_category_id', 'left' );
			$ci->db->join( 'shifts s', 's.id = ai.cashier_shift_id', 'left' );
			$ci->db->join( 'conversion_table ct', 'ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id' );
			$query = $ci->db->get( 'allocation_items AS ai' );
			$this->allocations = $query->result( 'Allocation_item' );
		}

		return $this->allocations;
	}

	public function get_cash_allocations( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->cash_allocations ) || $force )
		{
			$ci->load->library( 'allocation_item' );
			$ci->db->select( 'ai.*, c.cat_description, c.cat_module,
					i.item_name, i.item_description, i.item_class, i.teller_allocatable, i.machine_allocatable,
					ip.iprice_currency, ip.iprice_unit_price,
					s.shift_num AS cashier_shift_num' );
			$ci->db->where( 'allocation_id', $this->id );
			$ci->db->where( 'ai.allocation_item_type', 1 );
			$ci->db->where( 'i.item_class', 'cash' );
			$ci->db->join( 'items i', 'i.id = ai.allocated_item_id', 'left' );
			$ci->db->join( 'item_prices ip', 'ip.iprice_item_id = i.id', 'left' );
			$ci->db->join( 'categories c', 'c.id = ai.allocation_category_id', 'left' );
			$ci->db->join( 'shifts s', 's.id = ai.cashier_shift_id', 'left' );
			$query = $ci->db->get( 'allocation_items AS ai' );
			$this->cash_allocations = $query->result( 'Allocation_item' );
		}

		return $this->cash_allocations;
	}

	public function get_remittances( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->remittances ) || $force )
		{
			$ci->load->library( 'allocation_item' );
			$ci->db->select( 'ai.*, c.cat_description, c.cat_module,
					i.item_name, i.item_description,i.item_class, i.teller_remittable, i.machine_remittable,
					s.shift_num AS cashier_shift_num' );
			$ci->db->where( 'allocation_id', $this->id );
			$ci->db->where( 'ai.allocation_item_type', 2 );
			$ci->db->where( 'i.item_class', 'ticket' );
			$ci->db->join( 'items i', 'i.id = ai.allocated_item_id', 'left' );
			$ci->db->join( 'categories c', 'c.id = ai.allocation_category_id', 'left' );
			$ci->db->join( 'shifts s', 's.id = ai.cashier_shift_id', 'left' );
			$query = $ci->db->get( 'allocation_items AS ai' );
			$this->remittances = $query->result( 'Allocation_item' );
		}

		return $this->remittances;
	}

	public function get_cash_remittances( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->cash_remittances ) || $force )
		{
			$ci->load->library( 'allocation_item' );
			$ci->db->select( 'ai.*, c.cat_description, c.cat_module,
					i.item_name, i.item_description,i.item_class, i.teller_remittable, i.machine_remittable,
					ip.iprice_currency, ip.iprice_unit_price,
					s.shift_num AS cashier_shift_num' );
			$ci->db->where( 'allocation_id', $this->id );
			$ci->db->where( 'ai.allocation_item_type', 2 );
			$ci->db->where( 'i.item_class', 'cash' );
			$ci->db->join( 'items i', 'i.id = ai.allocated_item_id', 'left' );
			$ci->db->join( 'item_prices ip', 'ip.iprice_item_id = i.id', 'left' );
			$ci->db->join( 'categories c', 'c.id = ai.allocation_category_id', 'left' );
			$ci->db->join( 'shifts s', 's.id = ai.cashier_shift_id', 'left' );
			$query = $ci->db->get( 'allocation_items AS ai' );
			$this->cash_remittances = $query->result( 'Allocation_item' );
		}

		return $this->cash_remittances;
	}

	public function get_ticket_sales( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->ticket_sales ) || $force )
		{
			$ci->load->library( 'allocation_item' );
			$ci->db->select( 'ai.*, c.cat_description, c.cat_module,
					i.item_name, i.item_description,i.item_class, i.teller_remittable, i.machine_remittable,
					s.shift_num AS cashier_shift_num' );
			$ci->db->where( 'allocation_id', $this->id );
			$ci->db->where( 'ai.allocation_item_type', 3 );
			$ci->db->where( 'i.item_class', 'ticket' );
			$ci->db->join( 'items i', 'i.id = ai.allocated_item_id', 'left' );
			$ci->db->join( 'categories c', 'c.id = ai.allocation_category_id', 'left' );
			$ci->db->join( 'shifts s', 's.id = ai.cashier_shift_id', 'left' );
			$query = $ci->db->get( 'allocation_items AS ai' );
			$this->ticket_sales = $query->result( 'Allocation_item' );
		}

		return $this->ticket_sales;
	}

	public function get_sales( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->sales ) || $force )
		{
			$ci->load->library( 'allocation_sales_item' );
			$ci->db->select( 'asi.*,
					si.slitem_name, si.slitem_description, si.slitem_group, si.slitem_mode,
					s.shift_num AS cashier_shift_num' );
			$ci->db->where( 'asi.alsale_allocation_id', $this->id );
			$ci->db->join( 'sales_items si', 'si.id = asi.alsale_sales_item_id', 'left' );
			$ci->db->join( 'shifts s', 's.id = asi.alsale_shift_id', 'left' );
			$query = $ci->db->get( 'allocation_sales_items AS asi' );
			$this->sales = $query->result( 'Allocation_sales_item' );
		}

		return $this->sales;
	}

	public function get_cash_reports( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->cash_reports ) || $force )
		{
			$ci->load->library( 'shift_detail_cash_report' );
			$ci->db->select( 'sdcr.*' );
			$ci->db->where( 'sdcr.sdcr_allocation_id', $this->id );
			$query = $ci->db->get( 'shift_detail_cash_reports AS sdcr' );
			$this->cash_reports = $query->result( 'Shift_detail_cash_report' );
		}

		return $this->cash_reports;
	}

	public function set( $property, $value )
	{
		if( $property == 'id' )
		{
			return FALSE;
		}

		if( property_exists( $this, $property ) )
		{
			if( $property == 'allocation_status' )
			{
				if( ! isset( $this->previousStatus ) )
				{
					$this->previousStatus = (int) $this->allocation_status;
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
		$ci =& get_instance();
		$ci->load->library( 'inventory' );

		$result = NULL;
		$ci->db->trans_start();

		if( $this->assignee_type == ALLOCATION_ASSIGNEE_TELLER && ! $this->_check_for_valid_allocation_item() )
		{ // Teller allocation must have a valid allocation item for all actions
			set_message( 'Allocation does not contain any valid items' );
			return FALSE;
		}

		if( isset( $this->id ) )
		{
			if( $this->_check_items() )
			{
				// Cancel reservations
				$cancel_reservations = FALSE;

				// Check if we need to cancel reservations such as...
				if( array_key_exists( 'allocation_status', $this->db_changes )
					&& in_array( $this->db_changes['allocation_status'], array( ALLOCATION_ALLOCATED, ALLOCATION_CANCELLED ) )
					&& isset( $this->previousStatus )
					&& $this->previousStatus == ALLOCATION_SCHEDULED )
				{ // ...when allocation was already allocated
					$cancel_reservations = TRUE;
				}

				// Cancel allocation reservations
				if( $cancel_reservations )
				{
					foreach( $this->allocations AS $allocation )
					{
						if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED // cancel if has already reserved
							&& $allocation->get( 'id' ) // ...and if existing allocation
							&& isset( $this->db_changes['allocation_status'] )
							&& in_array( $this->db_changes['allocation_status'], array( ALLOCATION_ALLOCATED, ALLOCATION_CANCELLED ) ) )
						{
							$inventory = new Inventory();
							$inventory = $inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );

							if( $inventory )
							{
								$inventory->reserve( $allocation->get( 'allocated_quantity' ) * -1 );
							}
							else
							{
								set_message( 'Inventory record not found' );
								return FALSE;
							}
						}
					}

					foreach( $this->cash_allocations AS $allocation )
					{
						if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED // cancel if has already reserved
							&& $allocation->get( 'id' ) // ...and if existing allocation
							&& isset( $this->db_changes['allocation_status'] )
							&& in_array( $this->db_changes['allocation_status'], array( ALLOCATION_ALLOCATED, ALLOCATION_CANCELLED ) ) )
						{
							$inventory = new Inventory();
							$inventory = $inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );

							if( $inventory )
							{
								$inventory->reserve( $allocation->get( 'allocated_quantity' ) * -1 );
							}
							else
							{
								set_message( 'Inventory record not found' );
								return FALSE;
							}
						}
					}
				}
			}
			else
			{
				return FALSE;
			}

			foreach( $this->allocations as $allocation )
			{
				if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED
					&& $allocation->get( 'allocation_category_name' ) == 'Initial Allocation'
					&& ! $allocation->get( 'id' ) )
				{
					$inventory = new Inventory();
					$inventory = $inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );
					if( $inventory )
					{
						$inventory->reserve( $allocation->get( 'allocated_quantity' ) );
					}
					else
					{
						die( 'Cannot find inventory record' );
					}
				}

				if( ! $allocation->get( 'id' ) )
				{ // New allocation item
					$allocation->set( 'allocation_id', $this->id );
				}

				// Allocation was cancelled, cancel individual allocation item. This will also prevent it from being transacted later on.
				if( $this->allocation_status == ALLOCATION_CANCELLED )
				{
					$allocation->set( 'allocation_item_status', ALLOCATION_ITEM_CANCELLED );
				}


				$allocation->db_save();
			}

			foreach( $this->cash_allocations as $allocation )
			{
				if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED
					&& $allocation->get( 'allocation_category_name' ) == 'Initial Change Fund'
					&& ! $allocation->get( 'id' ) )
				{
					$inventory = new Inventory();
					$inventory = $inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );
					if( $inventory )
					{
						$inventory->reserve( $allocation->get( 'allocated_quantity' ) );
					}
					else
					{
						die( 'Cannot find inventory record' );
					}
				}

				if( ! $allocation->get( 'id' ) )
				{ // New allocation item
					$allocation->set( 'allocation_id', $this->id );
				}

				// Allocation was cancelled, cancel individual allocation item. This will also prevent it from being transacted later on.
				if( $this->allocation_status == ALLOCATION_CANCELLED )
				{
					$allocation->set( 'allocation_item_status', ALLOCATION_ITEM_CANCELLED );
				}


				$allocation->db_save();
			}

			foreach( $this->remittances as $remittance )
			{
				if( ! $remittance->get( 'id' ) )
				{
					$remittance->set( 'allocation_id', $this->id );
				}
				$remittance->db_save();
			}

			foreach( $this->cash_remittances as $remittance )
			{
				if( ! $remittance->get( 'id' ) )
				{
					$remittance->set( 'allocation_id', $this->id );
				}
				$remittance->db_save();
			}

			foreach( $this->ticket_sales as $ticket_sale )
			{
				if( ! $ticket_sale->get( 'id' ) )
				{
					$ticket_sale->set( 'allocation_id', $this->id );
				}
				$ticket_sale->db_save();
			}

			foreach( $this->sales as $sale )
			{
				if( ! $sale->get( 'id' ) )
				{
					$sale->set( 'alsale_allocation_id', $this->id );
				}
				$sale->db_save();
			}

			// check for required default values
			$this->_set_allocation_defaults();

			// Set fields and update record metadata
			$this->_update_timestamps( FALSE );
			$ci->db->set( $this->db_changes );

			$result = $this->_db_update();

			// Transact allocation
			if( $this->allocations )
			{
				$this->_transact_allocation();
			}

			// Transact remittance
			if( $this->remittances )
			{
				$this->_transact_remittance();
			}

			// Transact ticket sales
			if( $this->ticket_sales )
			{
				$this->_transact_ticket_sales();
			}

			// Transact sales
			if( $this->sales )
			{
				$this->_transact_sales();
			}

			// Transact voided items
			$this->_transact_voided_items();
		}
		else
		{ // insert new record
			if( $this->_check_items() )
			{
				// Check for valid new allocation status
				if( $this->assignee_type == ALLOCATION_ASSIGNEE_TELLER )
				{
					$valid_new_status = array( ALLOCATION_SCHEDULED, ALLOCATION_ALLOCATED );
					if( ! in_array( $this->allocation_status, $valid_new_status ) )
					{
						set_message( 'Invalid allocation status for new record' );
						return FALSE;
					}
				}

				// Check for valid assignee
				if( $this->allocation_status == ALLOCATION_ALLOCATED && !$this->assignee )
				{
					set_message( 'Allocation has no assignee defined' );
					return FALSE;
				}

				// Adjust inventory reservation level for new allocation request, if scheduled
				if( isset( $this->store_id )
						&& ( $this->store_id == $ci->session->current_store_id )
						&& ( $this->allocation_status == ALLOCATION_SCHEDULED )
						&& ( ! empty( $this->allocations ) || ! empty( $this->cash_allocations ) ) )
				{
					foreach( $this->allocations as $allocation )
					{
						$inventory = new Inventory();
						$inventory = $inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );
						if( $inventory )
						{
							$inventory->reserve( $allocation->get( 'allocated_quantity' ) );
						}
						else
						{
							set_message( 'Cannot find inventory record' );
							return FALSE;
						}
					}

					foreach( $this->cash_allocations as $allocation )
					{
						$inventory = new Inventory();
						$inventory = $inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );
						if( $inventory )
						{
							$inventory->reserve( $allocation->get( 'allocated_quantity' ) );
						}
						else
						{
							set_message( 'Cannot find inventory record' );
							return FALSE;
						}
					}
				}

				// Check for required default values
				$this->_set_allocation_defaults();

				// Set fields and update record metadata
				$this->_update_timestamps( TRUE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_insert();

				// Save allocation items
				if( isset( $this->allocations ) )
				{
					foreach( $this->allocations as $allocation )
					{
						$allocation->set( 'allocation_id', $this->id );
						$allocation->db_save();
					}
				}

				// Save cash allocation items
				if( isset( $this->cash_allocations ) )
				{
					foreach( $this->cash_allocations as $allocation )
					{
						$allocation->set( 'allocation_id', $this->id );
						$allocation->db_save();
					}
				}

				// Save remittance items
				if( isset( $this->remittances ) )
				{
					foreach( $this->remittances as $remittance )
					{
						$remittance->set( 'allocation_id', $this->id );
						$remittance->db_save();
					}
				}

				// Save cash remittance items
				if( isset( $this->cash_remittances ) )
				{
					foreach( $this->cash_remittances as $remittance )
					{
						$remittance->set( 'allocation_id', $this->id );
						$remittance->db_save();
					}
				}

				// Save ticket sales
				if( isset( $this->ticket_sales ) )
				{
					foreach( $this->ticket_sales as $ticket_sale )
					{
						$ticket_sale->set( 'allocation_id', $this->id );
						$ticket_sale->db_save();
					}
				}

				// Save sales
				if( isset( $this->sales ) )
				{
					foreach( $this->sales as $sale )
					{
						$sale->set( 'alsale_allocation_id', $this->id );
						$sale->db_save();
					}
				}

				$transact_status = array( ALLOCATION_ALLOCATED, ALLOCATION_REMITTED );

				// Transact allocation
				if( in_array( $this->allocation_status, $transact_status ) )
				{
					$this->_transact_allocation();
				}

				// Transact remittances
				if( ( $this->remittances || $this->cash_remittances ) && in_array( $this->allocation_status, $transact_status ) )
				{
					$this->_transact_remittance();
				}

				// Transact ticket sales
				if( $this->ticket_sales && in_array( $this->allocation_status, $transact_status ) )
				{
					$this->_transact_ticket_sales();
				}

				// Transact sales
				if( $this->sales  && in_array( $this->allocation_status, $transact_status ) )
				{
					$this->_transact_sales();
				}
			}
			else
			{
				return FALSE;
			}
		}

		$ci->db->trans_complete();

		if( $ci->db->trans_status() )
		{
			// Reset record changes
			$this->_reset_db_changes();
			$this->previousStatus = NULL;
			$this->voided_allocations = NULL;
			$this->voided_remittances = NULL;
			$this->voided_cash_allocations = NULL;
			$this->voided_cash_remittances = NULL;
			$this->voided_ticket_sales = NULL;
			$this->voided_sales = NULL;

			return $result;
		}
		else
		{
			return FALSE;
		}
	}

	public function allocate()
	{
		$ci =& get_instance();

		// Only allow allocation from the following previous status:
		$allowed_prev_status = array( ALLOCATION_SCHEDULED );
		if( ! in_array( $this->allocation_status, $allowed_prev_status ) )
		{
			set_message( 'Cannot allocate non-scheduled allocations' );
			return FALSE;
		}

		// Only the originating store can allocate
		if( $ci->session->current_store_id != $this->store_id )
		{
			set_message( sprintf( 'Current store (%s) is not authorize to allocate items in this record',
					$ci->session->current_store_id ) );
			return FALSE;
		}

		// There must be a valid allocation item
		if( ! $this->_check_for_valid_allocation_item() )
		{
			set_message( 'Allocation does not contain any valid items' );
			return FALSE;
		}

		// Assignee must be specified
		if( ! isset( $this->assignee ) )
		{
			set_message( sprintf( 'Allocation requires %s to be specified', $this->assignee_type == 1 ? 'teller name' : 'TVM number' ) );
			return FALSE;
		}

		$ci->db->trans_start();
		$this->set( 'allocation_status', ALLOCATION_ALLOCATED );
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

	public function remit()
	{
		$ci =& get_instance();

		// Only allow allocation from the following previous status:
		$allowed_prev_status = array( ALLOCATION_ALLOCATED );
		if( $this->assignee_type == ALLOCATION_ASSIGNEE_TELLER && ! in_array( $this->allocation_status, $allowed_prev_status ) )
		{
			set_message( 'Cannot remit non-allocated allocations' );
			return FALSE;
		}

		// Only the originating store can allocat
		if( $ci->session->current_store_id != $this->store_id )
		{
			set_message( sprintf( 'Current store (%s) is not authorize to remit items in this record', $ci->session->current_store_id ) );
			return FALSE;
		}

		// Assignee must be specified
		if( ! isset( $this->assignee ) )
		{
			set_message( sprintf( 'Remittance requires %s to be specified', $this->assignee_type == 1 ? 'teller name' : 'TVM number' ) );
			return FALSE;
		}

		// All pending allocations must be allocated or cancelled
		if( ! empty( $this->allocations ) )
		{
			foreach( $this->allocations as $allocation )
			{
				if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED )
				{
					set_message( 'Pending allocations must be allocated or cancelled' );
					return FALSE;
				}
			}
		}

		if( ! empty( $this->cash_allocations ) )
		{
			foreach( $this->cash_allocations as $allocation )
			{
				if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED )
				{
					set_message( 'Pending allocations must be allocated or cancelled' );
					return FALSE;
				}
			}
		}

		$ci->db->trans_start();
		$this->set( 'allocation_status', ALLOCATION_REMITTED );
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
		$ci =& get_instance();

		// check for valid previous allocation status
		if( ! in_array( $this->allocation_status, array( ALLOCATION_SCHEDULED ) ) )
		{
			die( 'Cannot cancel allocation. Only scheduled allocations can be cancelled.' );
		}

		// only the store owner can cancel the allocation
		if( $ci->session->current_store_id != $this->store_id )
		{
			die( 'You are not authorized to cancel the allocation.' );
		}

		$ci->db->trans_start();
		$this->set( 'allocation_status', ALLOCATION_CANCELLED );
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

	public function _set_allocation_defaults()
	{
		$ci =& get_instance();

		if( ! isset( $this->store_id ) )
		{
			$this->set( 'store_id', $ci->session->current_store_id );
		}

		if( ! isset( $this->business_date ) )
		{
			$this->set( 'business_date', date( DATE_FORMAT ) );
		}

		if( ! isset( $this->cashier_id ) )
		{
			$this->set( 'cashier_id', $ci->session->current_user_id );
		}

		if( ! isset( $this->allocation_status ) )
		{
			$this->set( 'allocation_status', ALLOCATION_SCHEDULED );
		}

		return TRUE;
	}

	public function _check_items()
	{
		$ci =& get_instance();

		$allocations = $this->get_allocations();
		$cash_allocations = $this->get_cash_allocations();
		$remittances = $this->get_remittances();
		$cash_remittances = $this->get_cash_remittances();
		$ticket_sales = $this->get_ticket_sales();
		$sales = $this->get_sales();

		$categories_cache = array();

		$ci->load->library( 'category' );
		$ci->load->library( 'item' );

		$pre_allocation_categories = array( 'InitAlloc', 'TVMAlloc', 'InitCFund' );
		$post_allocation_categories = array( 'AddAlloc', 'TVMAlloc', 'AddCFund' );

		$categories_cache = array();
		$items_cache = array();

		$voided_allocations = array();
		$voided_remittances = array();
		$voided_cash_allocations = array();
		$voided_cash_remittances = array();
		$voided_ticket_sales = array();
		$voided_sales = array();

		foreach( $allocations as $allocation )
		{
			$category = $allocation->get_category();
			$item = $allocation->get_item();

			// Fill up $category_type and $category if it is not yet set
			if( ! $category )
			{
				set_message( 'Missing allocation category', 'error' );
				return FALSE;
			}

			// Fill up $teller_allocatable and $machine_allocatable fields if it is not set
			if( ! $item )
			{
				set_message( 'Missing allocation item', 'error' );
				return FALSE;
			}

			// Check if category is allocatable by current assignee type
			switch( $this->assignee_type )
			{
				case 1: // teller
					if( $item->get( 'teller_allocatable' ) == FALSE )
					{
						set_message( 'Not teller allocatable', 'error' );
						return FALSE;
					}
					break;

				case 2: // machine
					if( $item->get( 'machine_allocatable' ) == FALSE )
					{
						set_message( 'Not machine allocatable', 'error' );
						return FALSE;
					}
					break;

				default:
					// Invalid assignee type!
					set_message( 'Invalid assignee type', 'error' );
					return FALSE;
			}

			switch( $this->allocation_status )
			{
				case ALLOCATION_SCHEDULED:
					// Not an allocation category
					if( ! $category->get( 'cat_module' ) == 'Allocation' )
					{
						set_message( 'Not an allocation category', 'error' );
						return FALSE;
					}

					// Not included in pre-allocation categories
					if( ! in_array( $category->get( 'cat_name' ), $pre_allocation_categories ) )
					{
						set_message( 'Not included in pre-allocation categories', 'error' );
						return FALSE;
					}

					// Not allowed allocation item status
					if( ! in_array( $allocation->get( 'allocation_item_status' ), array( ALLOCATION_ITEM_SCHEDULED, ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED ) ) )
					{
						set_message( 'Not allowed allocation item status', 'error' );
						return FALSE;
					}

					// Empty or negative allocated quantity
					if( $allocation->get( 'allocated_quantity' ) <= 0 )
					{
						set_message( 'Empty or negative allocated quantity', 'error' );
						return FALSE;
					}
					break;

				case ALLOCATION_ALLOCATED:
				case ALLOCATION_REMITTED:
				case ALLOCATION_CANCELLED:
					// Not an allocation category
					if( ! $category->get( 'cat_module' ) == 'Allocation' )
					{
						set_message( 'Not an allocation category', 'error' );
						return FALSE;
					}

					// Empty or negative allocated quantity
					if( $allocation->get( 'allocated_quantity' ) <= 0 )
					{
						set_message( 'Empty or negative allocated quantity', 'error' );
						return FALSE;
					}
					break;

				default:
					// do nothing
			}

			// Check for voided allocations
			if( array_key_exists( 'allocation_item_status', $allocation->db_changes )
				&& $allocation->db_changes['allocation_item_status'] == ALLOCATION_ITEM_VOIDED )
			{
				$item_id = $allocation->get( 'allocated_item_id' );
				$voided_allocations[] = $allocation;
			}
		}

		foreach( $cash_allocations as $allocation )
		{
			$category = $allocation->get_category();
			$item = $allocation->get_item();

			// Fill up $category_type and $category if it is not yet set
			if( ! $category )
			{
				set_message( 'Missing allocation category', 'error' );
				return FALSE;
			}

			// Fill up $teller_allocatable and $machine_allocatable fields if it is not set
			if( ! $item )
			{
				set_message( 'Missing allocation item', 'error' );
				return FALSE;
			}

			// Check if category is allocatable by current assignee type
			switch( $this->assignee_type )
			{
				case 1: // teller
					if( $item->get( 'teller_allocatable' ) == FALSE )
					{
						set_message( 'Not teller allocatable', 'error' );
						return FALSE;
					}
					break;

				case 2: // machine
					if( $item->get( 'machine_allocatable' ) == FALSE )
					{
						set_message( 'Not machine allocatable', 'error' );
						return FALSE;
					}
					break;

				default:
					// Invalid assignee type!
					set_message( 'Invalid assignee type', 'error' );
					return FALSE;
			}

			switch( $this->allocation_status )
			{
				case ALLOCATION_SCHEDULED:
					// Not an allocation category
					if( ! $category->get( 'cat_module' ) == 'Allocation' )
					{
						set_message( 'Not an allocation category', 'error' );
						return FALSE;
					}

					// Not included in pre-allocation categories
					if( ! in_array( $category->get( 'cat_name' ), $pre_allocation_categories ) )
					{
						set_message( 'Not included in pre-allocation categories', 'error' );
						return FALSE;
					}

					// Not allowed allocation item status
					if( ! in_array( $allocation->get( 'allocation_item_status' ), array( ALLOCATION_ITEM_SCHEDULED, ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED ) ) )
					{
						set_message( 'Not allowed allocation item status', 'error' );
						return FALSE;
					}

					// Empty or negative allocated quantity
					if( $allocation->get( 'allocated_quantity' ) <= 0 )
					{
						set_message( 'Empty or negative allocated quantity', 'error' );
						return FALSE;
					}
					break;

				case ALLOCATION_ALLOCATED:
				case ALLOCATION_REMITTED:
				case ALLOCATION_CANCELLED:
					// Not an allocation category
					if( ! $category->get( 'cat_module' ) == 'Allocation' )
					{
						set_message( 'Not an allocation category', 'error' );
						return FALSE;
					}

					// Empty or negative allocated quantity
					if( $allocation->get( 'allocated_quantity' ) <= 0 )
					{
						set_message( 'Empty or negative allocated quantity', 'error' );
						return FALSE;
					}
					break;

				default:
					// do nothing
			}

			// Check for voided allocations
			if( array_key_exists( 'allocation_item_status', $allocation->db_changes )
				&& $allocation->db_changes['allocation_item_status'] == ALLOCATION_ITEM_VOIDED )
			{
				$item_id = $allocation->get( 'allocated_item_id' );
				$voided_cash_allocations[] = $allocation;
			}
		}

		foreach( $remittances as $remittance )
		{
			$category = $remittance->get_category();
			$item = $remittance->get_item();

			if( ! $category )
			{
				set_message( 'Missing remittance category', 'error' );
				return FALSE;
			}

			if( ! $item )
			{
				set_message( 'Missing remittance item', 'error' );
				return FALSE;
			}

			// Check if category is allocatable by current assignee type
			switch( $this->assignee_type )
			{
				case 1: // teller
					if( $item->get( 'teller_remittable' ) == FALSE )
					{
						set_message( 'Not teller remittable', 'error' );
						return FALSE;
					}
					break;

				case 2: // machine
					if( $item->get( 'machine_remittable' ) == FALSE )
					{
						set_message( 'Not machine remittable', 'error' );
						return FALSE;
					}
					break;

				default:
					// Invalid assignee type!
					set_message( 'Invalid assignee type', 'error' );
					return FALSE;
			}

			switch( $this->allocation_status )
			{
				case ALLOCATION_SCHEDULED:
					if( $this->assignee_type == ALLOCATION_ASSIGNEE_TELLER )
					{
						// There should not be any remittances during this allocation status
						set_message( 'There should not be any remittances during this allocation status', 'error' );
						return FALSE;
					}
					break;

				case ALLOCATION_ALLOCATED:
				case ALLOCATION_REMITTED:
				case ALLOCATION_CANCELLED:
					// Not a remittance category
					if( $category->get( 'cat_module' ) != 'Remittance' )
					{
						set_message( 'Not a remittance category', 'error' );
						return FALSE;
					}

					// Empty or negative allocated quantity
					if( $remittance->get( 'allocated_quantity' ) <= 0 )
					{
						set_message( 'Empty or negative allocated quantity', 'error' );
						return FALSE;
					}
					break;
			}

			// Check for voided remittances
			if( array_key_exists( 'allocation_item_status', $remittance->db_changes )
				&& $remittance->db_changes['allocation_item_status'] == REMITTANCE_ITEM_VOIDED )
			{
				$item_id = $remittance->get( 'allocated_item_id' );
				$voided_remittances[] = $remittance;
			}
		}

		foreach( $cash_remittances as $remittance )
		{
			$category = $remittance->get_category();
			$item = $remittance->get_item();

			if( ! $category )
			{
				set_message( 'Missing remittance category', 'error' );
				return FALSE;
			}

			if( ! $item )
			{
				set_message( 'Missing remittance item', 'error' );
				return FALSE;
			}

			// Check if category is allocatable by current assignee type
			switch( $this->assignee_type )
			{
				case 1: // teller
					if( $item->get( 'teller_remittable' ) == FALSE )
					{
						set_message( 'Not teller remittable', 'error' );
						return FALSE;
					}
					break;

				case 2: // machine
					if( $item->get( 'machine_remittable' ) == FALSE )
					{
						set_message( 'Not machine remittable', 'error' );
						return FALSE;
					}
					break;

				default:
					// Invalid assignee type!
					set_message( 'Invalid assignee type', 'error' );
					return FALSE;
			}

			switch( $this->allocation_status )
			{
				case ALLOCATION_SCHEDULED:
					if( $this->assignee_type == ALLOCATION_ASSIGNEE_TELLER )
					{
						// There should not be any remittances during this allocation status
						set_message( 'There should not be any remittances during this allocation status', 'error' );
						return FALSE;
					}
					break;

				case ALLOCATION_ALLOCATED:
				case ALLOCATION_REMITTED:
				case ALLOCATION_CANCELLED:
					// Not a remittance category
					if( $category->get( 'cat_module' ) != 'Remittance' )
					{
						set_message( 'Not a remittance category', 'error' );
						return FALSE;
					}

					// Empty or negative allocated quantity
					if( $remittance->get( 'allocated_quantity' ) <= 0 )
					{
						set_message( 'Empty or negative allocated quantity', 'error' );
						return FALSE;
					}
					break;
			}

			// Check for voided remittances
			if( array_key_exists( 'allocation_item_status', $remittance->db_changes )
				&& $remittance->db_changes['allocation_item_status'] == REMITTANCE_ITEM_VOIDED )
			{
				$item_id = $remittance->get( 'allocated_item_id' );
				$voided_cash_remittances[] = $remittance;
			}
		}

		foreach( $ticket_sales as $ticket_sale )
		{
			// Check for voided ticket sales
			if( array_key_exists( 'allocation_item_status', $ticket_sale->db_changes )
				&& $ticket_sale->db_changes['allocation_item_status'] == TICKET_SALE_ITEM_VOIDED )
			{
				$item_id = $ticket_sale->get( 'allocated_item_id' );
				$voided_ticket_sales[] = $ticket_sale;
			}
		}

		foreach( $sales as $sale )
		{
			// Check for voided ticket sales
			if( array_key_exists( 'alsale_sales_item_status', $sale->db_changes )
				&& $sale->db_changes['alsale_sales_item_status'] == SALES_ITEM_VOIDED )
			{
				$sales_item_id = $sale->get( 'alsale_sales_item_id' );
				$voided_sales[] = $sale;
			}
		}

		$this->voided_allocations = $voided_allocations;
		$this->voided_remittances = $voided_remittances;
		$this->voided_cash_allocations = $voided_cash_allocations;
		$this->voided_cash_remittances = $voided_cash_remittances;
		$this->voided_ticket_sales = $voided_ticket_sales;
		$this->voided_sales = $voided_sales;

		return TRUE;
	}

	public function _transact_allocation()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$ci->load->library( 'category' );

		$Inventory = new Inventory();
		$Category = new Category();

		$allocations = $this->get_allocations();
		$cash_allocations = $this->get_cash_allocations();
		//$transaction_datetime = $this->business_date.' '.date( 'H:i:s' );
		$transaction_datetime = date( TIMESTAMP_FORMAT );

		$ci->db->trans_start();
		// Ticket items
		foreach( $allocations as $allocation )
		{
			if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED
				&& in_array( $this->allocation_status, array( ALLOCATION_ALLOCATED, ALLOCATION_REMITTED ) ) )
			{
				$inventory = $Inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );
				if ( $inventory )
				{
					$quantity = $allocation->get( 'allocated_quantity' ) * -1; // Item will be removed from inventory
					$inventory->transact( TRANSACTION_ALLOCATION, $quantity, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );

					$allocation->set( 'cashier_shift_id', $ci->session->current_shift_id );
					$allocation->set( 'allocation_item_status', ALLOCATION_ITEM_ALLOCATED );
					$allocation->db_save();
				}
				else
				{
					die( sprintf( 'Inventory record not found for store %s and item %s.', $this->store_id, $allocation->get( 'allocated_item_id' ) ) );
				}
			}
		}

		// Cash items
		$change_fund = $Inventory->get_by_store_item_name( $this->store_id, FUND_CHANGE_FUND );
		$ca_fund = $Inventory->get_by_store_item_name( $this->store_id, FUND_COIN_ACCEPTOR );
		//$change_fund_categories = array( 'Initial Change Fund', 'Additional Change Fund', 'Coin Replenishment', 'Coin Acceptor Replenishment' );
		$change_fund_categories = array( 'InitCFund', 'AddCFund', 'HopAlloc', 'CAAlloc');

		foreach( $cash_allocations as $allocation )
		{
			$quantity = $allocation->get( 'allocated_quantity' ) * -1; // Item will be removed from inventory
			if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED
					&& in_array( $this->allocation_status, array( ALLOCATION_ALLOCATED, ALLOCATION_REMITTED ) ) )
			{
				$item = $allocation->get_item();
				$allocation_category = $allocation->get_category();

				if( $allocation_category )
				{
					if( $item && ! empty( $item->get( 'iprice_unit_price' ) ) )
					{
						$amount = $allocation->get( 'allocated_quantity' ) * $item->get( 'iprice_unit_price' );

						if( $ca_fund && $allocation_category->get( 'cat_name' ) == 'CAAlloc' )
						{ // Add to Coin Acceptor Fund
							$ca_fund->transact( TRANSACTION_ALLOCATION, $amount, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
							$ca_fund_sub = $Inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ), $ca_fund->get( 'item_id' ), TRUE );
							$ca_fund_sub->transact( TRANSACTION_ALLOCATION, $quantity, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
						}

						if( $change_fund && in_array( $allocation_category->get( 'cat_name' ), $change_fund_categories ) )
						{ // Deduct from Change Fund
							$change_fund->transact( TRANSACTION_ALLOCATION, $amount * -1, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
							$change_fund_sub = $Inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ), $change_fund->get( 'item_id' ), TRUE );
							$change_fund_sub->transact( TRANSACTION_ALLOCATION, $quantity, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
						}
					}
				}

				$inventory = $Inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );
				if ( $inventory )
				{
					$inventory->transact( TRANSACTION_ALLOCATION, $quantity, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );

					$allocation->set( 'cashier_shift_id', $ci->session->current_shift_id );
					$allocation->set( 'allocation_item_status', ALLOCATION_ITEM_ALLOCATED );
					$allocation->db_save();
				}
				else
				{
					die( sprintf( 'Inventory record not found for store %s and item %s.', $this->store_id, $allocation->get( 'allocated_item_id' ) ) );
				}
			}
		}

		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}

	public function _transact_remittance()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );

		$Inventory = new Inventory();
		$Category = new Category();

		$remittances = $this->get_remittances();
		$cash_remittances = $this->get_cash_remittances();

		//$transaction_datetime = $this->business_date.' '.date( 'H:i:s' );
		$transaction_datetime = date( TIMESTAMP_FORMAT );

		$ci->db->trans_start();
		// Ticket items
		foreach( $remittances as $remittance )
		{
			if( $remittance->get( 'allocation_item_status' ) == REMITTANCE_ITEM_PENDING )
			{
				$inventory = $Inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ), NULL, TRUE );
				if( $inventory )
				{
					$quantity = $remittance->get( 'allocated_quantity' );
					$inventory->transact( TRANSACTION_REMITTANCE, $quantity, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );

					$remittance->set( 'cashier_shift_id', $ci->session->current_shift_id );
					$remittance->set( 'allocation_item_status', REMITTANCE_ITEM_REMITTED );
					$remittance->db_save();
				}
				else
				{
					die( sprintf( 'Inventory record not found for store %s and item %s.', $this->store_id, $remittance->get( 'allocated_item_id' ) ) );
				}
			}
		}

		// Cash items
		$change_fund = $Inventory->get_by_store_item_name( $this->store_id, FUND_CHANGE_FUND );
		$sales_fund = $Inventory->get_by_store_item_name( $this->store_id, FUND_SALES );
		$change_fund_categories = array( 'CFundRet', 'HopPullout', 'CAPullout' );
		$sales_fund_categories = array( 'SalesColl' );

		foreach( $cash_remittances as $remittance )
		{
			$quantity = $remittance->get( 'allocated_quantity' );
			if( $remittance->get( 'allocation_item_status' ) == REMITTANCE_ITEM_PENDING )
			{
				// TODO: Should this be here or in the _transact_sales()?
				$item = $remittance->get_item();
				if( $item && ! empty( $item->get( 'iprice_unit_price' ) ) )
				{
					$amount = $remittance->get( 'allocated_quantity' ) * $item->get( 'iprice_unit_price' );
					$remittance_category = $remittance->get_category();

					if( $remittance_category )
					{
						if( $change_fund && in_array( $remittance_category->get( 'cat_name' ), $change_fund_categories ) )
						{
							$change_fund->transact( TRANSACTION_REMITTANCE, $amount, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
							$change_fund_sub = $Inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ), $change_fund->get( 'item_id' ), TRUE );
							$change_fund_sub->transact( TRANSACTION_REMITTANCE, $quantity, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
						}
						elseif( $sales_fund && in_array( $remittance_category->get( 'cat_name' ), $sales_fund_categories ) )
						{
							$sales_fund->transact( TRANSACTION_REMITTANCE, $amount, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
							$sales_fund_sub = $Inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ), $sales_fund->get( 'item_id' ), TRUE );
							$sales_fund_sub->transact( TRANSACTION_REMITTANCE, $quantity, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
						}
					}
				}

				$inventory = $Inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ), NULL, TRUE );
				if( $inventory )
				{
					$inventory->transact( TRANSACTION_REMITTANCE, $quantity, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );

					$remittance->set( 'cashier_shift_id', $ci->session->current_shift_id );
					$remittance->set( 'allocation_item_status', REMITTANCE_ITEM_REMITTED );
					$remittance->db_save();
				}
				else
				{
					die( sprintf( 'Inventory record not found for store %s and item %s.', $this->store_id, $remittance->get( 'allocated_item_id' ) ) );
				}
			}
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}

	public function _transact_ticket_sales()
	{
		$ci =& get_instance();

		$ticket_sales = $this->get_ticket_sales();

		$ci->db->trans_start();
		// Ticket items
		foreach( $ticket_sales as $ticket_sale )
		{
			if( $ticket_sale->get( 'allocation_item_status' ) == TICKET_SALE_ITEM_PENDING )
			{
				$ticket_sale->set( 'cashier_shift_id', $ci->session->current_shift_id );
				$ticket_sale->set( 'allocation_item_status', TICKET_SALE_ITEM_RECORDED );
				$ticket_sale->db_save();
			}
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}

	public function _transact_sales()
	{
		$ci =& get_instance();

		$sales = $this->get_sales();

		$ci->db->trans_start();
		foreach( $sales as $sale )
		{
			if( $sale->get( 'alsale_sales_item_status' ) == SALES_ITEM_PENDING )
			{
				$sale->set( 'alsale_shift_id', $ci->session->current_shift_id );
				$sale->set( 'alsale_sales_item_status', SALES_ITEM_RECORDED );
				$sale->db_save();
			}
		}
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}

	public function _transact_voided_items()
	{
		$ci =& get_instance();

		$ci->load->library( 'inventory' );
		$Inventory = new Inventory();

		//$transaction_datetime = $this->business_date.' '.date( 'H:i:s' );
		$transaction_datetime = date( TIMESTAMP_FORMAT );

		$ci->db->trans_start();
		// Allocations
		if( isset( $this->voided_allocations ) && $this->voided_allocations )
		{
			foreach( $this->voided_allocations as $allocation )
			{
				$quantity = $allocation->get( 'allocated_quantity' );
				$inventory = $Inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ), NULL, TRUE );

				if( $inventory )
				{
					$inventory->transact( TRANSACTION_ALLOCATION_VOID, $quantity, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
				}
			}
		}

		// Cash Allocations
		$change_fund = $Inventory->get_by_store_item_name( $this->store_id, FUND_CHANGE_FUND );
		$ca_fund = $Inventory->get_by_store_item_name( $this->store_id, FUND_COIN_ACCEPTOR );
		$change_fund_categories = array( 'InitCFund', 'AddCFund', 'HopAlloc', 'CAAlloc' );

		if( isset( $this->voided_cash_allocations ) && $this->voided_cash_allocations )
		{
			foreach( $this->voided_cash_allocations as $allocation )
			{
				$quantity = $allocation->get( 'allocated_quantity' );
				$inventory = $Inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ), NULL, TRUE );

				if( $inventory )
				{
					$inventory->transact( TRANSACTION_ALLOCATION_VOID, $quantity, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
				}

				$item = $allocation->get_item();
				$allocation_category = $allocation->get_category();

				if( $allocation_category )
				{
					if( $item && ! empty( $item->get( 'iprice_unit_price' ) ) )
					{
						$amount = $quantity * $item->get( 'iprice_unit_price' );

						if( $ca_fund && $allocation_category->get( 'cat_name' ) == 'CAAlloc' )
						{ // Deduct from Coin Acceptor Fund
							$ca_fund->transact( TRANSACTION_ALLOCATION_VOID, $amount * -1, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
							$ca_fund_sub = $Inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ), $ca_fund->get( 'item_id' ), TRUE );
							$ca_fund_sub->transact( TRANSACTION_ALLOCATION_VOID, $quantity * -1, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
						}

						if( $change_fund && in_array( $allocation_category->get( 'cat_name' ), $change_fund_categories ) )
						{ // Return to Change Fund
							$change_fund->transact( TRANSACTION_ALLOCATION_VOID, $amount, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
							$change_fund_sub = $Inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ), $change_fund->get( 'item_id' ), TRUE );
							$change_fund_sub->transact( TRANSACTION_ALLOCATION_VOID, $quantity, $transaction_datetime, $this->id, $allocation->get( 'id' ), $allocation->get( 'allocation_category_id' ) );
						}
					}
				}
			}
		}

		// Remittances
		if( isset( $this->voided_remittances ) && $this->voided_remittances )
		{
			foreach( $this->voided_remittances as $remittance )
			{
				$quantity = $remittance->get( 'allocated_quantity' ) * -1;
				$inventory = $Inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ), NULL, TRUE );

				if( $inventory )
				{
					$inventory->transact( TRANSACTION_REMITTANCE_VOID, $quantity, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
				}
			}
		}


		// Cash Remittances
		if( isset( $this->voided_cash_remittances ) && $this->voided_cash_remittances )
		{
			$change_fund = $Inventory->get_by_store_item_name( $this->store_id, FUND_CHANGE_FUND );
			$sales_fund = $Inventory->get_by_store_item_name( $this->store_id, FUND_SALES );
			$change_fund_categories = array( 'CFundRet', 'HopPullout', 'CAPullout' );
			$sales_fund_categories = array( 'SalesColl' );

			foreach( $this->voided_cash_remittances as $remittance )
			{
				$quantity = $remittance->get( 'allocated_quantity' ) * -1;
				$inventory = $Inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ), NULL, TRUE );

				if( $inventory )
				{ // Deduct from Inventory
					$inventory->transact( TRANSACTION_REMITTANCE_VOID, $quantity, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
				}

				$item = $remittance->get_item();
				$remittance_category = $remittance->get_category();

				if( $remittance_category )
				{
					if( $item && ! empty( $item->get( 'iprice_unit_price' ) ) )
					{
						$amount = $quantity * $item->get( 'iprice_unit_price' );

						if( $remittance_category )
						{
							if( $change_fund && in_array( $remittance_category->get( 'cat_name' ), $change_fund_categories ) )
							{ // Deduct from Change Fund
								$change_fund->transact( TRANSACTION_REMITTANCE, $amount, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
								$change_fund_sub = $Inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ), $change_fund->get( 'item_id' ), TRUE );
								$change_fund_sub->transact( TRANSACTION_REMITTANCE, $quantity, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
							}

							if( $sales_fund && in_array( $remittance_category->get( 'cat_name' ), $sales_fund_categories ) )
							{ // Deduct from Sales
								$sales_fund->transact( TRANSACTION_REMITTANCE, $amount, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
								$sales_fund_sub = $Inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ), $sales_fund->get( 'item_id' ), TRUE );
								$sales_fund_sub->transact( TRANSACTION_REMITTANCE, $quantity, $transaction_datetime, $this->id, $remittance->get( 'id' ), $remittance->get( 'allocation_category_id' ) );
							}
						}
					}
				}
			}
		}

		$ci->db->trans_complete();

		$this->voided_allocations = NULL;
		$this->voided_remittances = NULL;
		$this->voided_cash_allocations = NULL;
		$this->voided_cash_remittances = NULL;
		$this->voided_ticket_sales = NULL;
		$this->voided_sales = NULL;

		return $ci->db->trans_status();
	}

	private function _check_for_valid_allocation_item( $force = FALSE )
	{
		if( ! isset( $this->has_valid_allocation_item ) || $force )
		{
			$this->has_valid_allocation_item = false;
			$allocations = $this->get_allocations();
			$cash_allocations = $this->get_cash_allocations();

			foreach( $allocations as $allocation )
			{
				if( in_array( $allocation->get( 'allocation_item_status' ), array( ALLOCATION_ITEM_SCHEDULED, ALLOCATION_ITEM_ALLOCATED ) )
					&& $allocation->get( 'allocated_quantity' ) > 0 )
				{
					$this->has_valid_allocation_item = true;
					break;
				}
			}

			if( ! $this->has_valid_allocation_item )
			{
				foreach( $cash_allocations as $allocation )
				{
					if( in_array( $allocation->get( 'allocation_item_status' ), array( ALLOCATION_ITEM_SCHEDULED, ALLOCATION_ITEM_ALLOCATED ) )
						&& $allocation->get( 'allocated_quantity' ) > 0 )
					{
						$this->has_valid_allocation_item = true;
						break;
					}
				}
			}
		}

		return $this->has_valid_allocation_item;
	}

	private function _check_for_valid_remittance_item( $force = FALSE )
	{
		if( ! isset( $this->has_valid_remittance_item ) || $force )
		{
			$this->has_valid_remittance_item = false;
			$remittances = $this->get_remittances();
			$cash_remittances = $this->get_cash_remittances();

			foreach( $remittances as $remittance )
			{
				if( in_array( $remittance->get( 'allocation_item_status' ), array( REMITTANCE_ITEM_PENDING, REMITTANCE_ITEM_REMITTED ) )
					&& $remittance->get( 'allocated_quantity' ) > 0 )
				{
					$this->has_valid_remittance_item = true;
					break;
				}
			}

			if( ! $this->has_valid_remittance_item )
			{
				foreach( $remittances as $remittance )
				{
					if( in_array( $remittance->get( 'allocation_item_status' ), array( REMITTANCE_ITEM_PENDING, REMITTANCE_ITEM_REMITTED ) )
						&& $remittance->get( 'allocated_quantity' ) > 0 )
					{
						$this->has_valid_remittance_item = true;
						break;
					}
				}
			}
		}

		return $this->has_valid_remittance_item;
	}

	private function _check_for_valid_ticket_sale_item( $force = FALSE )
	{
		if( ! isset( $this->had_valid_ticket_sale_item ) || $force )
		{
			$this->has_valid_ticket_sale_item = false;
			$ticket_sales = $this->get_ticket_sales();

			foreach( $ticket_sales as $ticket_sale )
			{
				if( in_array( $ticket_sale->get( 'allocation_item_status' ), array( TICKET_SALE_ITEM_PENDING, TICKET_SALE_ITEM_RECORDED ) )
					&& $ticket_sale->get( 'allocated_quantity' ) > 0 )
				{
					$this->has_valid_ticket_sale_item = true;
					break;
				}
			}
		}

		return $this->has_valid_ticket_sale_item;
	}

	public function load_from_data( $data = array(), $overwrite = TRUE )
	{
		$ci =& get_instance();

		// Try to get existing value first if ID exists
		if( array_key_exists( 'id', $data ) && $data['id'] )
		{
			$r = $this->get_by_id( $data['id'] );
			$r->get_allocations( TRUE );
			$r->get_remittances( TRUE );
			$r->get_cash_allocations( TRUE );
			$r->get_cash_remittances( TRUE );
			$r->get_sales( TRUE );
			$r->get_cash_reports( TRUE );
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
			elseif( in_array( $field, array( 'allocations', 'remittances', 'cash_allocations', 'cash_remittances', 'ticket_sales' ) ) )
			{ // load items
				$ci->load->library( 'allocation_item' );

				if( ! isset( $r->$field ) )
				{
					$r->$field = array();
				}

				foreach( $value as $i )
				{
					$Item = new Allocation_item();
					$item_id = param( $i, 'id' );

					if( is_null( $item_id ) )
					{
						$item = $Item->load_from_data( $i );

						$x =& $r->$field;
						$x[] = $item;
					}
					else
					{
						$index = array_value_search( 'id', $item_id, $r->$field, FALSE );
						if( ! is_null( $index ) )
						{
							$x =& $r->$field;
							$x[$index] = $Item->load_from_data( $i );
						}
						else
						{
							$item = $Item->load_from_data( $i );
							$x =& $r->$field;
							$x[] = $item;
						}
					}
				}
			}
			elseif( $field == 'sales' )
			{
				$ci->load->library( 'allocation_sales_item' );
				if( ! isset( $r->$field ) )
				{
					$r->$field = array();
				}

				foreach( $value as $i )
				{
					$Item = new Allocation_sales_item();
					$item_id = param( $i, 'id' );

					if( is_null( $item_id ) )
					{
						$item = $Item->load_from_data( $i );

						$x =& $r->$field;
						$x[] = $item;
					}
					else
					{
						$index = array_value_search( 'id', $item_id, $r->$field, FALSE );
						if( ! is_null( $index ) )
						{
							$x =& $r->$field;
							$x[$index] = $Item->load_from_data( $i );
						}
						else
						{
							$item = $Item->load_from_data( $i );
							$x =& $r->$field;
							$x[] = $item;
						}
					}
				}
			}
			elseif( $field == 'cash_reports' )
			{
				$ci->load->library( 'shift_detail_cash_report' );
				if( ! isset( $r->$field ) )
				{
					$r->$field = array();
				}

				foreach( $value as $i )
				{
					$Item = new Shift_detail_cash_report();
					$item_id = param( $i, 'id' );

					if( is_null( $item_id ) )
					{
						$item = $Item->load_from_data( $i );

						$x =& $r->$field;
						$x[] = $item;
					}
					else
					{
						$index = array_value_search( 'id', $item_id, $r->$field, FALSE );
						if( ! is_null( $index ) )
						{
							$x =& $r->$field;
							$x[$index] = $Item->load_from_data( $i );
						}
						else
						{
							$item = $Item->load_from_data( $i );
							$x =& $r->$field;
							$x[] = $item;
						}
					}
				}
			}

		}

		return $r;
	}
}