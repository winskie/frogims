<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mopping extends Base_model {

	protected $store_id;
    protected $processing_datetime;
	protected $business_date;
	protected $shift_id;
    protected $cashier_shift_id;

    protected $items;
    protected $packed_items;
    protected $void_items;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'mopping';
		$this->db_fields = array(
				'store_id' => array( 'type' => 'integer' ),
                'processing_datetime' => array( 'type' => 'datetime' ),
				'business_date' => array( 'type' => 'date' ),
				'shift_id' => array( 'type' => 'integer' ),
                'cashier_shift_id' => array( 'type' => 'integer' )
			);
        $this->children = array(
                'items' => array( 'table' => 'mopping_items', 'key' => 'mopping_id', 'field' => 'items', 'class' => 'Mopping_item' )
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
            $ci->load->library( 'mopping_item' );
            $ci->db->select( 'mi.*, IF( mi.mopped_station_id = 0, "Inventory",  s.station_name ) AS mopped_station_name, i.item_name AS mopped_item_name, i2.item_name AS convert_to_name, u.full_name AS processor_name' );
            $ci->db->where( 'mopping_id', $this->id );
            $ci->db->join( 'items i', 'i.id = mi.mopped_item_id', 'left' );
            $ci->db->join( 'items i2', 'i2.id = mi.converted_to', 'left' );
            $ci->db->join( 'stations s', 's.id = mi.mopped_station_id', 'left' );
            $ci->db->join( 'users u', 'u.id = mi.processor_id', 'left' );
            $query = $ci->db->get( 'mopping_items mi' );
            $items = $query->result( 'Mopping_item' );

            foreach( $items as $item )
            {
                $item->set( 'parentMopping', $this );
            }

            if( $attach )
            {
                $this->items = $items;
            }
        }

        return $items;
    }

    public function db_save()
    {
        // There are no pending changes, just return the record
        $ci =& get_instance();
        $ci->load->library( 'inventory' );

        $result = NULL;
        $ci->db->trans_start();

        if( isset( $this->id ) )
        {
            if( $this->_check_items() )
            {
                // Only mopping data can be edited but not mopping items?
                $ci->db->set( $this->db_changes );
                $result = $this->_db_update();

                // Update mopping items
                foreach( $this->items as $item )
                {
                    $item->db_save();
                }

                // Transact voiding of items
                $this->_transact_void_items();
            }
            else
            {
                return FALSE;
            }
        }
        else
        {
            if( $this->_check_items() )
            {
                // Check for required default values
                $this->_set_mopping_defaults();

                // Set fields and update record metadata
                $this->_update_timestamps( TRUE );
                $ci->db->set( $this->db_changes );

                $result = $this->_db_insert();

                // Save mopping items
                foreach( $this->items as $item )
                {
                    $item->set( 'mopping_id', $this->id );
                    $item->db_save();
                }

                // Transact mopping collection
                if( ! $this->_transact_collection() )
                {
                    return FALSE;
                }

                // Pack items
                if( ! $this->_transact_packing() )
                {
                    return FALSE;
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

            return $result;
        }
        else
        {
            return FALSE;
        }
    }

    public function _set_mopping_defaults()
    {
        $ci =& get_instance();

        if( ! isset( $this->store_id ) )
        {
            $this->set( 'store_id', $ci->session->current_store_id );
        }

        if( ! isset( $this->processing_datetime ) )
        {
            $this->set( 'processing_datetime', date( TIMESTAMP_FORMAT ) );
        }

        if( ! isset( $this->business_date ) )
        {
            $this->set( 'business_date', date( DATE_FORMAT ) );
        }

        return TRUE;
    }

    public function _check_items()
    {
        $ci =& get_instance();

        $ci->load->library( 'conversion' );
        $conversion = new Conversion();
        $items = $this->get_items();
        $packed_items = array();
        $void_items = array();
        $valid_packing = true;
        $last_group = 1;

        if( ! $items )
        {
            set_message( 'No items to collect', 'error' );
            return FALSE;
        }

        foreach( $items as $item )
        {
            $source_item_id = $item->get( 'mopped_item_id' );
            if( $item->get( 'converted_to' ) )
            { // packed item
                $target_item_id = $item->get( 'converted_to' );
                if( $cf = $conversion->get_conversion_factor( $source_item_id, $target_item_id ) )
                {
                    $current_group = $source_item_id.'_'.$target_item_id.'_'.$last_group;

                    if( isset( $packed_items[$current_group] ) )
                    { // existing group, just update quantity and validity

                        // Check if status is still the same
                        if( $item->get( 'mopping_item_status' ) !== $packed_items[$current_group]['status'] )
                        {
                            $this->packed_items = array();
                            $this->void_items = array();
                            return FALSE;
                        }

                        $packed_items[$current_group]['quantity'] += $item->get( 'mopped_quantity' );
                        $packed_items[$current_group]['valid'] = ( $packed_items[$current_group]['quantity'] === $packed_items[$current_group]['conversion_factor'] );
                    }
                    else
                    { // new group
                        $packed_items[$current_group] = array(
                            'source_item_id' => $source_item_id,
                            'target_item_id' => $target_item_id,
                            'conversion_factor' => $cf['factor'],
                            'quantity' => intval( $item->get( 'mopped_quantity' ) ),
                            'valid' => ( intval( $item->get( 'mopped_quantity' ) ) === intval( $cf['factor'] ) ),
                            'group_id' => $last_group,
                            'status' => $item->get( 'mopping_item_status' )
                        );
                    }

                    if( $packed_items[$current_group]['valid'] )
                    {
                        $last_group++;
                    }
                }
            }
            elseif( array_key_exists( 'mopping_item_status', $item->db_changes )
                    && $item->db_changes['mopping_item_status'] == MOPPING_ITEM_VOIDED )
            {
                $source_item_id = $item->get( 'mopped_item_id' );
                if( isset( $void_items[$source_item_id] ) )
                {
                    $void_items[$source_item_id] += $item->get( 'mopped_quantity' );
                }
                else
                {
                    $void_items[$source_item_id] = $item->get( 'mopped_quantity' );
                }
            }
        }

        // Check if all items are valid
        foreach( $packed_items as $item )
        {
            if( ! $item['valid'] )
            {
                $valid_packing = false;
                break;
            }

            // Void packed item
            if( intval( $item['status'] ) == 2 )
            {
                if( isset( $void_items[$item['target_item_id']] ) )
                {
                    $void_items[$item['target_item_id']] += ( $item['quantity'] / $item['conversion_factor'] );
                }
                else
                {
                    $void_items[$item['target_item_id']] = ( $item['quantity'] / $item['conversion_factor'] );
                }
            }
        }

        // Only set packed_items if items are valid
        if( $valid_packing )
        {
            $this->packed_items = $packed_items;
            $this->void_items = $void_items;
            return TRUE;
        }
        else
        {
            $this->packed_items = NULL;
            $this->void_items = NULL;
            set_message( 'Failed item checking', 'error', 202 );
            return FALSE;
        }
    }

    public function _transact_collection()
    {
        $ci =& get_instance();

        $ci->load->library( 'inventory' );

        $items = $this->get_items();
        $timestamp = date( TIMESTAMP_FORMAT );

        $ci->db->trans_start();
        foreach( $items as $item )
        {
            $inventory = new Inventory();
            $inventory = $inventory->get_by_store_item( $this->store_id, $item->get( 'mopped_item_id' ) );

            if( $inventory )
            {
                $quantity = $item->get( 'mopped_quantity' );
                if( intval( $item->get( 'mopped_station_id' ) ) === 0 )
                { // issuance from stock, deduct same quantity from inventory
                    $inventory->transact( TRANSACTION_MOPPING_ISSUANCE, ( $quantity * -1 ), $timestamp, $this->id, $item->get( 'id' ) );
                }
                $inventory->transact( TRANSACTION_MOPPING_COLLECTION, $quantity, $timestamp, $this->id, $item->get( 'id' ) );
            }
            else
            {
                set_message( sprintf( 'Inventory record not found for store %s and item %s.', $this->store_id, $item->get( 'mopped_item_id' ) ), 'error' );
                return FALSE;
            }
        }
        $ci->db->trans_complete();

        return $ci->db->trans_status();
    }

    public function _transact_packing()
    {
        $ci =& get_instance();

        $ci->load->library( 'conversion' );
        $ci->load->library( 'inventory' );

        $timestamp = date( TIMESTAMP_FORMAT );

        if( isset( $this->packed_items ) && $this->packed_items )
        {
            $ci->db->trans_start();

            foreach( $this->packed_items as $item )
            {
                $source_inventory = new Inventory();
                $source_inventory = $source_inventory->get_by_store_item( $this->store_id, $item['source_item_id'] );

                $target_inventory = new Inventory();
                $target_inventory = $target_inventory->get_by_store_item( $this->store_id, $item['target_item_id'] );

                if( $source_inventory && $target_inventory )
                {
                    $conversion = new Conversion();
                    $conversion->set( 'store_id', $ci->session->current_store_id );
                    $conversion->set( 'conversion_datetime', $timestamp );
                    $conversion->set( 'conversion_shift', $ci->session->current_shift_id );
                    $conversion->set( 'source_inventory_id', $source_inventory->get( 'id' ) );
                    $conversion->set( 'target_inventory_id', $target_inventory->get( 'id' ) );
                    $conversion->set( 'source_quantity', $item['quantity'] );
                    $conversion->set( 'target_quantity', $item['quantity'] / $item['conversion_factor'] );
                    $conversion->set( 'remarks', sprintf( 'Auto packaging from mopping collection # %s', $this->id ) );
                    $conversion->set( 'conversion_status', CONVERSION_APPROVED );

                    $conversion->setAutoApproval( TRUE );
                    $result = $conversion->db_save();
                }
                else
                {
                    // Unable to load source/target inventory records
                    set_message( 'Unable to load source/target inventory records' );
                    return FALSE;
                }
            }

            if( $result )
            {
                $ci->db->trans_complete();
                return $ci->db->trans_status();
            }
            else
            {
                $ci->db->trans_rollback();
                return FALSE;
            }
        }

        return true;
    }

    public function _transact_void_items()
    {
        $ci =& get_instance();

        $ci->load->library( 'inventory' );

        $timestamp = date( TIMESTAMP_FORMAT );

        if( isset( $this->void_items ) && $this->void_items )
        {
            $ci->db->trans_start();
            foreach( $this->void_items as $k => $v )
            {
                $inventory = new Inventory();
                $inventory = $inventory->get_by_store_item( $this->store_id, $k );

                if( $inventory )
                {
                    $quantity = $v * -1;
                    $inventory->transact( TRANSACTION_MOPPING_VOID, $quantity, $timestamp, $this->id, $k );
                }
            }
            $ci->db->trans_complete();
        }

        $this->void_items = NULL;

        return $ci->db->trans_status();
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
				$ci->load->library( 'mopping_item' );

				foreach( $value as $i )
				{
                    $item = new Mopping_item();
                    $item_id = param( $i, 'id' );

                    if( is_null( $item_id ) )
                    {
					    $item = $item->load_from_data( $i );
                        $r->items[] = $item;
                    }
                    else
                    {
                        $index = array_value_search( 'id', $item_id, $r->items, FALSE );
                        if( ! is_null( $index ) )
                        {
                            $r->items[$index] = $item->load_from_data( $i );
                        }
                        else
                        {
					        $item = $item->load_from_data( $i );
                            $r->items[] = $item;
                        }
                    }
				}
			}
		}
		return $r;
    }
}