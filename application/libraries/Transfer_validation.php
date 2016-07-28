<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transfer_validation extends Base_model {

	protected $transval_transfer_id;
	protected $transval_receipt_status;
	protected $transval_receipt_datetime;
	protected $transval_receipt_sweeper;
    protected $transval_receipt_user_id;
	protected $transval_receipt_shift_id;
	protected $transval_items_checked;
	protected $transval_transfer_status;
	protected $transval_transfer_datetime;
	protected $transval_transfer_sweeper;
    protected $transval_transfer_user_id;
	protected $transval_transfer_shift_id;
	protected $transval_status;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	protected $previous_receipt_status;
	protected $previous_transfer_status;
	protected $previous_status;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'transfers';
		$this->db_fields = array(
				'transval_transfer_id' => array( 'type' => 'integer' ),
				'transval_receipt_status' => array( 'type' => 'integer' ),
				'transval_receipt_datetime' => array( 'type' => 'datetime' ),
				'transval_receipt_sweeper' => array( 'type' => 'string' ),
                'transval_receipt_user_id' => array( 'type' => 'integer' ),
				'transval_receipt_shift_id' => array( 'type' => 'integer' ),
				'transval_items_checked' => array( 'type' => 'string' ),
				'transval_transfer_status' => array( 'type' => 'integer' ),
				'transval_transfer_datetime' => array( 'type' => 'datetime' ),
				'transval_transfer_sweeper' => array( 'type' => 'string' ),
                'transval_transfer_user_id' => array( 'type' => 'integer' ),
				'transval_transfer_shift_id' => array( 'type' => 'integer' ),
				'transval_status' => array( 'type' => 'integer' )
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
			if( $this->$property !== $value )
			{
				// If it's a change in transfer status let's take note of the previous one
				if( $property == 'transval_status' )
				{
					if( ! isset( $this->previous_status ) )
					{
						$this->previous_status = $this->transval_status;
					}
				}
				elseif( $property == 'transval_receipt_status' )
				{
					if( ! isset( $this->previous_receipt_status ) )
					{
						$this->previous_receipt_status = $this->transval_receipt_status;
					}
				}
				elseif( $property == 'transval_transfer_status' )
				{
					if( ! isset( $this->previous_transfer_status ) )
					{
						$this->previous_transfer_status = $this->transval_transfer_status;
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
}