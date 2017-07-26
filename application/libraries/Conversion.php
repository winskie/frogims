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
    protected $conversion_status;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
    protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

    protected $status_log = array(
			'table' => 'conversion_status_log',
			'status_field' => 'conversion_status',
			'prefix' => 'convlog_',
			'foreign_key' => 'conversion_id'
		);

    protected $previous_status;
    protected $autoApproval = FALSE;

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
				'remarks' => array( 'type' => 'string' ),
                'conversion_status' => array( 'type' => 'integer' )
			);
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
			if( $property == 'conversion_status' )
			{
				if( ! isset( $this->previous_status ) )
				{
					$this->previous_status = $this->conversion_status;
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

    public function setAutoApproval( $value = TRUE )
    {
        // TODO: Additional checks for setting autoApproval
        $this->autoApproval = $value;
    }

    public function get_conversions( $params = array() )
    {
        $ci =& get_instance();

        $format = param( $params, 'format', 'object' );

        $conversions = $ci->db->get( $this->primary_table );
        $conversions = $conversions->result( get_class( $this ) );

        if( $format == 'array' )
        {
            $conversions_data = array();
            foreach( $conversions as $conversion )
            {
                $conversions_data[] = $conversion->as_array();
            }
            return $conversions_data;
        }

        return $conversions;
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
        $target_quantity = $target_quantity[$target_inventory->get( 'item_id' )];

        if( $target_quantity )
        {
            if( $this->_check_data() )
            {
                // Set converted target quantity
                $this->set( 'target_quantity', $target_quantity );

                $ci->db->trans_start();
                if( isset( $this->id ) )
                {
                    $pre_approved_status = array( CONVERSION_PENDING );

                    // If this conversion is going to be approved set the conversion timestamp and the
                    if( array_key_exists( 'conversion_status', $this->db_changes )
                            && $this->db_changes['conversion_status'] == CONVERSION_APPROVED
                            && isset( $this->previous_status )
                            && in_array( $this->previous_status, $pre_approved_status ) )
                    {
                        $this->set( 'conversion_datetime', date( TIMESTAMP_FORMAT ) );
                        $this->Set( 'conversion_shift', $ci->session->current_shift_id );
                    }

                    // Set fields and update record metadata
                    $this->_update_timestamps( FALSE );
                    $ci->db->set( $this->db_changes );

                    $result = $this->_db_update();

                    // Approved conversion, transact with inventory
                    // TODO: Check if there is sufficient inventory to convert, probably needs to refactor checking of item

                    if( array_key_exists( 'conversion_status', $this->db_changes )
                            && $this->db_changes['conversion_status'] == CONVERSION_APPROVED
                            && isset( $this->previous_status )
                            && in_array( $this->previous_status, $pre_approved_status ) )
                    {
                        $this->_transact_conversion();
                    }
                }
                else
                {
                    // Check for valid new conversion status
                    $valid_new_status = array( CONVERSION_PENDING, CONVERSION_APPROVED );
                    if( ! ( array_key_exists( 'conversion_status', $this->db_changes )
                            && in_array( $this->db_changes['conversion_status'], $valid_new_status ) ) )
                    {
                        die( 'Invalid conversion status for new record' );
                    }

                    // If this conversion is going to be approved convert
                    if( array_key_exists( 'conversion_status', $this->db_changes )
                            && $this->db_changes['conversion_status'] == CONVERSION_APPROVED )
                    {
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
                    }

                    // Always set default values
                    $this->set( 'conversion_datetime', date( TIMESTAMP_FORMAT ) );
                    $this->set( 'conversion_shift', $ci->session->current_shift_id );

                    // Set fields and update record metadata
                    $this->_update_timestamps( TRUE );
                    $ci->db->set( $this->db_changes );

                    $result = $this->_db_insert();

                    // Approved conversion, Transact with inventory
                    // TODO: Check if there is sufficient inventory
                    if( $this->conversion_status == CONVERSION_APPROVED )
                    {
                        $this->_transact_conversion();
                    }
                }
                $ci->db->trans_complete();

                if( $ci->db->trans_status() )
                {
                    // Reset record changes
                    $this->_reset_db_changes();
                    $this->previous_status = NULL;

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
        else
        { // Unable to convert source item to target item
            set_message( 'Unable to convert source item to target item', 'error' );
            return FALSE;
        }
    }

    public function approve()
    {
        $ci =& get_instance();

        $ci->db->trans_start();
        $this->set( 'conversion_status', CONVERSION_APPROVED );
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
                set_message( 'A database error has occurred while trying to approve the conversion.', 'error' );
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
        $allowed_prev_status = array( CONVERSION_PENDING );
        if( ! in_array( $this->conversion_status, $allowed_prev_status ) )
        {
            die( 'Cannot cancel conversion. Only pending conversions can be cancelled.' );
        }

        $ci->db->trans_start();
        $this->set( 'conversion_status', CONVERSION_CANCELLED );
        $this->db_save();
        $ci->db->trans_complete();

        if( $ci->db->trans_status() )
        {
            return $this;
        }

        return FALSE;
    }

    public function _check_data()
    {
        $ci =& get_instance();

        // Checks before approval
        if( array_key_exists( 'conversion_status', $this->db_changes )
            && $this->db_changes['conversion_status'] == CONVERSION_APPROVED )
        {

            // Only allow approval from the following previous status:
            $allowed_prev_status = array( CONVERSION_PENDING );
            if( ! in_array( $this->previous_status, $allowed_prev_status )
                && ! $this->autoApproval )
            {
                set_message( 'Cannot approve non-pending conversions', 'error', 200 );
                return FALSE;
            }

            // Check if there is sufficient source inventory for conversion
            $ci->load->library( 'inventory' );
            $Inventory = new Inventory();
            $source_inventory = $Inventory->get_by_id( $this->source_inventory_id );

            if( $source_inventory )
            {
                if( $source_inventory->get( 'quantity' ) < $this->source_quantity )
                {
                    set_message( 'Insufficient inventory for input item to convert', 'error', 200 );
                    return FALSE;
                }
            }
            else
            {
                set_message( 'Inventory record not found', 'error' );
                return FALSE;
            }
        }

        return TRUE;
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

            //$transaction_datetime = $this->conversion_datetime;
            $transaction_datetime = date( TIMESTAMP_FORMAT );

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

            $source_inventory->transact( TRANSACTION_CONVERSION_FROM, ( $this->source_quantity * -1 ), $transaction_datetime, $this->id );
            $target_inventory->transact( TRANSACTION_CONVERSION_TO, $this->target_quantity, $transaction_datetime, $this->id );
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