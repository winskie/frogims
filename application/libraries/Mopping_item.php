<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mopping_item extends Base_model {

	protected $mopping_id;
	protected $mopped_station_id;
	protected $mopped_item_id;
    protected $mopped_quantity;
    protected $mopped_base_quantity;
    protected $converted_to;
    protected $group_id;
    protected $mopping_item_status;
    protected $processor_id;
    protected $delivery_person;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

    protected $previousStatus;
    protected $parentMopping;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'mopping_items';
		$this->db_fields = array(
				'mopping_id' => array( 'type' => 'integer' ),
				'mopped_station_id' => array( 'type' => 'integer' ),
				'mopped_item_id' => array( 'type' => 'integer' ),
                'mopped_quantity' => array( 'type' => 'integer' ),
                'mopped_base_quantity' => array( 'type' => 'integer' ),
                'converted_to' => array( 'type' => 'integer' ),
                'group_id' => array( 'type' => 'integer' ),
                'mopping_item_status' => array( 'type' => 'integer' ),
                'processor_id' => array( 'type' => 'integer' ),
                'delivery_person' => array( 'type' => 'string' )
			);
	}

    public function set_parent( &$parent )
	{
		$this->parentMopping = $parent;
	}

	public function get_parent()
	{
		$ci =& get_instance();

		if( ! $this->parentMopping )
		{
			$ci->load->library( 'mopping' );
			$mopping = new Mopping();
			$mopping = $mopping->get_by_id( $this->mopping_id );
			$this->parentMopping = $mopping;
		}

		return $this->parentMopping;
	}

    public function set( $property, $value )
    {
        if( $property == 'id' )
		{
			return FALSE;
		}

		if( property_exists( $this, $property ) )
		{

			if( $this->$property !== $value )
			{
                if( $property == 'mopping_item_status' )
                {
                    if( ! isset( $this->previousStatus ) )
                    {
                        $this->previousStatus = $this->mopping_item_status;
                    }
                }
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
		if( isset( $this->id ) )
		{
			$ci->db->set( $this->db_changes );
			$result = $this->_db_update();
		}
		else
		{
            if( $this->_check_data() )
            {
                // Check for required default values
                $this->_set_default_values();

                // Set base quantity
                $this->set( 'mopped_base_quantity', $this->_get_base_quantity( $this->mopped_item_id, $this->mopped_quantity ) );

                $ci->db->set( $this->db_changes );
                $result = $this->_db_insert();
            }
            else
            {
                return FALSE;
            }
		}
		$ci->db->trans_complete();

		if( $ci->db->trans_status() )
		{
			$this->_reset_db_changes();
			return $result;
		}
		else
		{
			return FALSE;
		}
	}

    public function _set_default_values()
    {
        // Set current user as processor of collection
        if( !isset( $this->processor_id ) )
        {
            $this->set( 'processor_id', current_user( TRUE ) );
        }
    }

    public function _get_base_quantity( $item_id, $quantity )
    {
        $ci =& get_instance();

        $ci->load->library( 'item' );
        $item = new Item();
        $item = $item->get_by_id( $item_id );

        if( $item )
        {
            $base_item_id = $item->get( 'base_item_id' );
            if( $base_item_id )
            { // item has base item
                $ci->load->library( 'conversion' );
                $conversion = new Conversion();
                $cf = $conversion->get_conversion_factor( $base_item_id, $this->mopped_item_id );
                if( $cf && $cf['factor'] > 1 )
                {
                    return $quantity * $cf['factor'];
                }
                else
                {
                    print_r( $item->as_array() );
                    die( 'Unable to retrieve conversion table record '.$base_item_id );
                }
            }
            else
            { // No base item, just return the same quantity
                return $quantity;
            }
        }
        else
        {
            die( 'Unable to retrieve item record' );
        }
    }

    public function _transact_void()
    {
        $ci =& get_instance();

        $ci->load->library( 'inventory' );
        $inventory = new Inventory();
        $parent = $this->get_parent();
        $inventory = $inventory->get_by_store_item( $parent->get( 'store_id' ), $this->mopped_item_id );
        if( $inventory )
        {
            $quantity = $this->mopped_quantity * -1; // Quantity will be deducted from the inventory
            $inventory->transact( TRANSACTION_MOPPING_VOID, $quantity, date( TIMESTAMP_FORMAT ), $this->mopping_id, $this->id );
        }
        else
        {
            die( sprintf( 'Inventory record not found for store %s and item %s.', $parent->get( 'store_id' ), $this->mopped_item_id ) );
        }
    }

    public function _reset_db_changes()
	{
		$this->db_changes = array();
        $this->previousStatus = NULL;
	}
}