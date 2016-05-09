<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conversion extends Base_model {
	
	protected $store_id;
	protected $conversion_datetime;
    protected $conversion_shift;
	protected $source_inventory_id;
	protected $target_inventory_id;
	protected $source_quantity;
	protected $target_quantity;
	protected $remarks;
	
	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';
	
	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'conversions';
		$this->db_fields = array(
				'store_id' => array( 'type' => 'integer' ),
				'conversion_datetime' => array( 'type' => 'datetime' ),
                'conversion_shift' => array( 'type' => 'integer' ),
				'source_inventory_id' => array( 'type' => 'integer' ),
				'target_inventory_id' => array( 'type' => 'integer' ),
				'source_quantity' => array( 'type' => 'integer' ),
				'target_quantity' => array( 'type' => 'integer' ),
				'remarks' => array( 'type' => 'string' )
			);
	}
    
    public function db_save()
    {
        // There are no pending changes, just return the record
        if( ! $this->db_changes )
        {
            return $this;
        }
        
        $ci =& get_instance();
        
        // Convert source quantity to target quantity
        $ci->load->library( 'inventory' );
        $inventory = new Inventory();
        $source_inventory = $inventory->get_by_id( $this->source_inventory_id );
        $target_inventory = $inventory->get_by_id( $this->target_inventory_id );
        $target_quantity = $this->convert( $source_inventory->get( 'item_id' ), $target_inventory->get( 'item_id' ), $this->source_quantity );
        
        if( $target_quantity )
        {
            // Set converted target quantity
            $this->set( 'target_quantity', $target_quantity );

            $ci->db->trans_start();
            if( isset( $this->id ) )
            {
                // Conversions cannot be edited! Just return the record
                $result = $this->get_by_id( $this->id );
            }
            else
            {
                // Convert
                $ci->load->library( 'inventory' );
                $inventory = new Inventory();
                $source_inventory = $inventory->get_by_id( $this->source_inventory_id );
                $target_inventory = $inventory->get_by_id( $this->target_inventory_id );
                    
                if( $source_inventory && $target_inventory )
                {
                    $source_item_id = $source_inventory->get( 'item_id' );
                    $target_item_id = $target_inventory->get( 'item_id' );
                    
                    $output = $this->convert( $source_item_id, $target_item_id, $this->source_quantity );
                    
                    if( is_array( $output ) )
                    {
                        if( isset( $output[$target_item_id] ) )
                        {
                            $this->set( 'target_quantity', $output[$target_item_id] );
                        }
                        
                        if( isset( $output[$source_item_id] ) )
                        {
                            // Not all of the source/ requested quantity were converted
                            $requested_quantity = $this->source_quantity;
                            $this->set( 'source_quantity', $requested_quantity - $output[$source_item_id] );
                        }
                    }
                }
                else
                {
                    die( 'Inventory record not found.' );
                }
                
                // Always set default values
                $this->set( 'conversion_datetime', date( TIMESTAMP_FORMAT ) );
                $this->set( 'conversion_shift', $ci->session->current_shift_id );
                
                // Set fields and update record metadata
                $this->_update_timestamps( TRUE );
                $ci->db->set( $this->db_changes );
                
                $result = $this->_db_insert();
                
                // Transact conversion
                $this->_transact_conversion();
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
        else
        { // Unable to convert source item to target item
            die( 'Unable to convert source item to target item' );
        }
    }
    
    public function _transact_conversion()
    {
        $ci =& get_instance();
        
        $ci->load->library( 'inventory' );
        $inventory = new Inventory();
        $source_inventory = $inventory->get_by_id( $this->source_inventory_id );
        $target_inventory = $inventory->get_by_id( $this->target_inventory_id );
            
        if( $source_inventory && $target_inventory )
        {
            $ci->db->trans_start();
            $source_item_id = $source_inventory->get( 'item_id' );
            $target_item_id = $target_inventory->get( 'item_id' );
            
            $output = $this->convert( $source_item_id, $target_item_id, $this->source_quantity );
            
            if( is_array( $output ) )
            {
                if( isset( $output[$target_item_id] ) )
                {
                    $this->set( 'target_quantity', $output[$target_item_id] );
                }
                
                if( isset( $output[$source_item_id] ) )
                {
                    // Not all of the source/ requested quantity were converted
                    $requested_quantity = $this->source_quantity;
                    $this->set( 'source_quantity', $requested_quantity - $output[$source_item_id] );
                }
            }
            
            $source_inventory->transact( TRANSACTION_CONVERSION_FROM, ( $this->source_quantity * -1 ), $this->conversion_datetime, $this->id );
            $target_inventory->transact( TRANSACTION_CONVERSION_TO, $this->target_quantity, $this->conversion_datetime, $this->id );
            $ci->db->trans_complete();
        }
        else
        {
            die( 'Inventory record not found.' );
        }
    }
    
    public function get_conversion_factor( $source_item_id, $target_item_id )
    {
        $mode = 'pack';
        $ci =& get_instance();
        
        $ci->db->where( 'source_item_id', $source_item_id );
        $ci->db->where( 'target_item_id', $target_item_id );
        $ci->db->limit(1);
        
        $query = $ci->db->get( 'conversion_table' );
        
        if( ! $query->num_rows() )
        {
            $ci->db->where( 'source_item_id', $target_item_id );
            $ci->db->where( 'target_item_id', $source_item_id );
            $ci->db->limit(1);
            
            $query = $ci->db->get( 'conversion_table' );
            
            if( $query->num_rows() )
            {
                $mode = 'unpack';
            }
            else
            {
                return FALSE;
            }
        }
        
        $query = $query->row_array();
        $factor = (int) $query['conversion_factor'];
        
        if( $factor === 1 )
        {
            $mode = 'convert';
        }
        
        return array(
                'mode' => $mode,
                'factor' => $factor
            );
    }
    
    public function convert( $source_item_id, $target_item_id, $quantity )
    {
        $ci =& get_instance();
        
        // Get normal conversion
        $ci->db->where( 'source_item_id', $source_item_id );
        $ci->db->where( 'target_item_id', $target_item_id );
        $ci->db->limit(1);
        $query = $ci->db->get( 'conversion_table' );
        
        if( $query->num_rows() )
        {
            $row = $query->row_array();            
            $converted = (int)( $quantity / $row['conversion_factor'] );
            $remainder = $quantity - ( $converted * $row['conversion_factor'] );
            
            $result = array();
            if( $converted <> 0 ) $result[$target_item_id] = $converted;
            if( $remainder <> 0 ) $result[$source_item_id] = $remainder; 
            
            return $result;
        }
        else
        { // Try converting the other way around
            $ci->db->where( 'source_item_id', $target_item_id );
            $ci->db->where( 'target_item_id', $source_item_id );
            $ci->db->limit(1);
            $query = $ci->db->get( 'conversion_table' );
            
            if( $query->num_rows() )
            {
                $row = $query->row_array();
                return array(
                        $target_item_id => $quantity * $row['conversion_factor']
                    ); 
            }
            else
            { // No conversion entry found
                return FALSE;
            }
        }
    }
}