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
    protected $remittances;
    protected $previousStatus;
    protected $voided_allocations;
    protected $voided_remittances;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

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

    public function get_allocations( $attach = FALSE )
    {
        $ci =& get_instance();

        if( isset( $this->allocations ) )
        {
            return $this->allocations;
        }
        else
        {
            $ci->load->library( 'allocation_item' );
            $ci->db->select( 'ai.*, ic.category AS category_name, ic.category_type,
                    i.item_name, i.item_description, i.teller_allocatable, i.machine_allocatable,
                    s.shift_num AS cashier_shift_num' );
            $ci->db->where( 'allocation_id', $this->id );
            $ci->db->where( 'ic.category_type', 1 );
            $ci->db->join( 'items i', 'i.id = ai.allocated_item_id', 'left' );
            $ci->db->join( 'item_categories ic', 'ic.id = ai.allocation_category_id', 'left' );
            $ci->db->join( 'shifts s', 's.id = ai.cashier_shift_id', 'left' );
            $query = $ci->db->get( 'allocation_items AS ai' );
            $allocations = $query->result( 'Allocation_item' );

            if( $attach )
            {
                $this->allocations = $allocations;
            }
        }

        return $allocations;
    }

    public function get_remittances( $attach = FALSE )
    {
        $ci =& get_instance();

        if( isset( $this->remittances ) )
        {
            return $this->remittances;
        }
        else
        {
            $ci->load->library( 'allocation_item' );
            $ci->db->select( 'ai.*, ic.category AS category_name, ic.category_type,
                    i.item_name, i.item_description, i.teller_remittable, i.machine_remittable,
                    s.shift_num AS cashier_shift_num' );
            $ci->db->where( 'allocation_id', $this->id );
            $ci->db->where( 'ic.category_type', 2 );
            $ci->db->join( 'items i', 'i.id = ai.allocated_item_id', 'left' );
            $ci->db->join( 'item_categories ic', 'ic.id = ai.allocation_category_id', 'left' );
            $ci->db->join( 'shifts s', 's.id = ai.cashier_shift_id', 'left' );
            $query = $ci->db->get( 'allocation_items AS ai' );
            $remittances = $query->result( 'Allocation_item' );

            if( $attach )
            {
                $this->remittances = $remittances;
            }
        }

        return $remittances;
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

            foreach( $this->remittances as $remittance )
            {
                if( ! $remittance->get( 'id' ) )
                {
                    $remittance->set( 'allocation_id', $this->id );
                }
                $remittance->db_save();
            }

            // check for required default values
            $this->_set_allocation_defaults();

            // Set fields and update record metadata
            $this->_update_timestamps( FALSE );
            $ci->db->set( $this->db_changes );

            $result = $this->_db_update();

            // Transact allocation
            $this->_transact_allocation();

            // Transact voided items
            $this->_transact_voided_items();
        }
        else
        { // insert new record
            if( $this->_check_items() )
            {
                // Check for valid new allocation status
                $valid_new_status = array( ALLOCATION_SCHEDULED, ALLOCATION_ALLOCATED );
                if( ! in_array( $this->allocation_status, $valid_new_status ) )
                {
                    die( 'Invalid allocation status for new record' );
                }

                // Check for valid assignee
                if( $this->allocation_status == ALLOCATION_ALLOCATED && !$this->assignee )
                {
                    die( 'Allocation has no assignee defined' );
                }

                // Adjust inventory reservation level for new allocation request, if scheduled
                if( isset( $this->store_id )
                        && ( $this->store_id == $ci->session->current_store_id )
                        && ( $this->allocation_status == ALLOCATION_SCHEDULED ) )
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
                            die( 'Cannot find inventory record' );
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
                foreach( $this->allocations as $allocation )
                {
                    $allocation->set( 'allocation_id', $this->id );
                    $allocation->db_save();
                }

                // Transact allocation
                if( $this->allocation_status == ALLOCATION_ALLOCATED )
                {
                    $this->_transact_allocation();
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
            die( 'Cannot allocate non-scheduled allocations' );
        }

        // Only the originating store can allocat
		if( $ci->session->current_store_id != $this->store_id )
		{
			die( sprintf( 'Current store (%s) is not authorize to allocate items in this record',
                    $ci->session->current_store_id ) );
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
        if( ! in_array( $this->allocation_status, $allowed_prev_status ) )
        {
            die( 'Cannot remit non-allocated allocations' );
        }

        // Only the originating store can allocat
		if( $ci->session->current_store_id != $this->store_id )
		{
			die( sprintf( 'Current store (%s) is not authorize to remit items in this record',
                    $ci->session->current_store_id ) );
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
            die( 'Cannot cancel allocation. Only scheduled allocations can be transferred.' );
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
        $remittances = $this->get_remittances();
        $item_categories_cache = array();

        $ci->load->library( 'item_category' );
        $ci->load->library( 'item' );

        $pre_allocation_categories = array( 'Initial Allocation', 'Magazine Load' );
        $post_allocation_categories = array( 'Additional Allocation', 'Magazine Load' );

        $item_categories_cache = array();
        $items_cache = array();

        $voided_allocations = array();
        $voided_remittances = array();

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
                    if( ! $category->get( 'is_allocation_category' ) )
                    {
                        set_message( 'Not an allocation category', 'error' );
                        return FALSE;
                    }

                    // Not included in pre-allocation categories
                    if( ! in_array( $category->get( 'category' ), $pre_allocation_categories ) )
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
                    if( ! $category->get( 'is_allocation_category' ) )
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
                if( isset( $voided_allocations[$item_id] ) )
                {
                    $voided_allocations[$item_id] += $allocation->get( 'allocated_quantity' );
                }
                else
                {
                    $voided_allocations[$item_id] = $allocation->get( 'allocated_quantity' );
                }
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
                    // There should not be any remittances during this allocation status
                    set_message( 'There should not be any remittances during this allocation status', 'error' );
                    return FALSE;
                    break;

                case ALLOCATION_ALLOCATED:
                case ALLOCATION_REMITTED:
                case ALLOCATION_CANCELLED:
                    // Not a remittance category
                    if( ! $category->get( 'is_remittance_category' ) )
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
                if( isset( $voided_remittances[$item_id] ) )
                {
                    $voided_remittances[$item_id] += $remittance->get( 'allocated_quantity' );
                }
                else
                {
                    $voided_remittances[$item_id] = $remittance->get( 'allocated_quantity' );
                }
            }
        }

        $this->voided_allocations = $voided_allocations;
        $this->voided_remittances = $voided_remittances;

        return TRUE;
    }

    public function _transact_allocation()
    {
        $ci =& get_instance();

        $ci->load->library( 'inventory' );
        $allocations = $this->get_allocations();
        $remittances = $this->get_remittances();
        $timestamp = date( TIMESTAMP_FORMAT );

        $ci->db->trans_start();
        foreach( $allocations as $allocation )
        {
            if( $allocation->get( 'allocation_item_status' ) == ALLOCATION_ITEM_SCHEDULED
                && $this->allocation_status == ALLOCATION_ALLOCATED )
            {
                $inventory = new Inventory();
                $inventory = $inventory->get_by_store_item( $this->store_id, $allocation->get( 'allocated_item_id' ) );
                if ( $inventory )
                {
                    $quantity = $allocation->get( 'allocated_quantity' ) * -1; // Item will be removed from inventory
                    $inventory->transact( TRANSACTION_ALLOCATION, $quantity, $timestamp, $this->id );

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

        foreach( $remittances as $remittance )
        {
            if( $remittance->get( 'allocation_item_status' ) == REMITTANCE_ITEM_PENDING )
            {
                $inventory = new Inventory();
                $inventory = $inventory->get_by_store_item( $this->store_id, $remittance->get( 'allocated_item_id' ) );
                if( $inventory )
                {
                    $quantity = $remittance->get( 'allocated_quantity' );
                    $inventory->transact( TRANSACTION_REMITTANCE, $quantity, $timestamp, $this->id );

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

    public function _transact_voided_items()
    {
        $ci =& get_instance();

        $ci->load->library( 'inventory' );
        $timestamp = date( TIMESTAMP_FORMAT );

        $ci->db->trans_start();
        if( isset( $this->voided_allocations ) && $this->voided_allocations )
        {
            foreach( $this->voided_allocations as $k => $v )
            {
                $inventory = new Inventory();
                $inventory = $inventory->get_by_store_item( $this->store_id, $k );

                if( $inventory )
                {
                    $quantity = $v;
                    $inventory->transact( TRANSACTION_ALLOCATION_VOID, $quantity, $timestamp, $this->id );
                }
            }
        }

        if( isset( $this->voided_remittances ) && $this->voided_remittances )
        {
            foreach( $this->voided_remittances as $k => $v )
            {
                $inventory = new Inventory();
                $inventory = $inventory->get_by_store_item( $this->store_id, $k );

                if( $inventory )
                {
                    $quantity = $v * -1;
                    $inventory->transact( TRANSACTION_REMITTANCE_VOID, $quantity, $timestamp, $this->id );
                }
            }
        }

        $ci->db->trans_complete();

        $this->voided_allocations = NULL;
        $this->voided_remittances = NULL;

        return $ci->db->trans_status();
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
			elseif( in_array( $field, array( 'allocations', 'remittances' ) ) )
			{ // load items
				$ci->load->library( 'allocation_item' );

                if( ! isset( $r->$field ) )
                {
                    $r->$field = array();
                }

				foreach( $value as $i )
				{
					$item = new Allocation_item();
                    $item_id = param( $i, 'id' );

                    if( is_null( $item_id ) )
                    {
                        $item = $item->load_from_data( $i );

                        $x =& $r->$field;
                        $x[] = $item;
                    }
                    else
                    {
                        $index = array_value_search( 'id', $item_id, $r->$field, FALSE );
                        if( ! is_null( $index ) )
                        {
                            $x =& $r->$field;
                            $x[$index] = $item->load_from_data( $i );
                        }
                        else
                        {
                            $item = $item->load_from_data( $i );
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