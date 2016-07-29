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

	protected $transfer;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	protected $previous_receipt_status;
	protected $previous_transfer_status;
	protected $previous_status;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'transfer_validations';
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

	public function get_transfer()
	{
		if( ! isset( $this->transval_transfer_id ) )
		{
			return FALSE;
		}

		if( ! isset( $this->transfer ) )
		{
			$ci =& get_instance();

			$ci->load->library( 'transfer' );
			$Transfer = new Transfer();
			$this->transfer = $Transfer->get_by_id( $this->transval_transfer_id );
		}

		return $this->transfer;
	}

	public function validate_receipt()
	{
		$ci =& get_instance();

		// Check if transfer is approved
		$ci->load->library( 'transfer' );
		$transfer = $this->get_transfer();
		if( $transfer->get( 'transfer_status') != TRANSFER_APPROVED )
		{
			set_message( 'Cannot validate receipt of non-approved transfer' );
			return FALSE;
		}

		// Check if validation is already completed..
		if( $this->transval_status == TRANSFER_VALIDATION_COMPLETED )
		{
			set_message( 'Cannot validate receipt. Transfer validation is already completed.' );
			return FALSE;
		}

		// ...or transfer does not require validation
		if( $this->transval_status == TRANSFER_VALIDATION_NOTAPPLICABLE )
		{
			set_messgae( 'Cannot validate receipt. Transfer does not require validation.' );
			return FALSE;
		}

		// Receipt sweeper is empty
		if( ! $this->transval_receipt_sweeper )
		{
			set_message( 'Missing receipt sweeper information' );
			return FALSE;
		}

		$ci->db->trans_start();
		$this->set( 'transval_receipt_status', TRANSFER_VALIDATION_RECEIPT_VALIDATED );
		$this->set( 'transval_receipt_datetime', date( TIMESTAMP_FORMAT ) );
		$this->set( 'transval_receipt_user_id', current_user( TRUE ) );
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

	public function return_transfer()
	{
		$ci =& get_instance();

		// Check if transfer is approved
		$ci->load->library( 'transfer' );
		$transfer = $this->get_transfer();
		if( $transfer->get( 'transfer_status') != TRANSFER_APPROVED )
		{
			set_message( 'Cannot validate receipt of non-approved transfer' );
			return FALSE;
		}

		// Check if validation is already completed..
		if( $this->transval_status == TRANSFER_VALIDATION_COMPLETED )
		{
			set_message( 'Cannot validate receipt. Transfer validation is already completed.' );
			return FALSE;
		}

		// ...or transfer does not require validation
		if( $this->transval_status == TRANSFER_VALIDATION_NOTAPPLICABLE )
		{
			set_messgae( 'Cannot validate receipt. Transfer does not require validation.' );
			return FALSE;
		}

		// Receipt sweeper is empty
		if( ! $this->transval_receipt_sweeper )
		{
			set_message( 'Missing receipt sweeper information' );
			return FALSE;
		}

		$ci->db->trans_start();
		$this->set( 'transval_receipt_status', TRANSFER_VALIDATION_RECEIPT_RETURNED );
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

	public function validate_transfer()
	{
		$ci =& get_instance();

		// Check if transfer is received
		$ci->load->library( 'transfer' );
		$transfer = $this->get_transfer();
		if( $transfer->get( 'transfer_status') != TRANSFER_RECEIVED )
		{
			set_message( 'Cannot validate receipt - Transfer is not yet received' );
			return FALSE;
		}

		// Check if validation is already completed..
		if( $this->transval_status == TRANSFER_VALIDATION_COMPLETED )
		{
			set_message( 'Cannot validate receipt - Transfer validation is already completed' );
			return FALSE;
		}

		// ...or transfer does not require validation
		if( $this->transval_status == TRANSFER_VALIDATION_NOTAPPLICABLE )
		{
			set_messgae( 'Cannot validate receipt - Transfer does not require validation' );
			return FALSE;
		}

		// Receipt sweeper is empty
		if( ! $this->transval_transfer_sweeper )
		{
			set_message( 'Missing transfer sweeper information' );
			return FALSE;
		}

		$ci->db->trans_start();
		$this->set( 'transval_transfer_status', TRANSFER_VALIDATION_TRANSFER_VALIDATED );
		$this->set( 'transval_transfer_datetime', date( TIMESTAMP_FORMAT ) );
		$this->set( 'transval_transfer_user_id', current_user( TRUE ) );
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

	public function dispute()
	{
		$ci =& get_instance();

		// Check if transfer is received
		$ci->load->library( 'transfer' );
		$transfer = $this->get_transfer();
		if( $transfer->get( 'transfer_status') != TRANSFER_RECEIVED )
		{
			set_message( 'Cannot validate receipt - Transfer is not yet received' );
			return FALSE;
		}

		// Check if validation is already completed..
		if( $this->transval_status == TRANSFER_VALIDATION_COMPLETED )
		{
			set_message( 'Cannot validate receipt - Transfer validation is already completed' );
			return FALSE;
		}

		// ...or transfer does not require validation
		if( $this->transval_status == TRANSFER_VALIDATION_NOTAPPLICABLE )
		{
			set_messgae( 'Cannot validate receipt - Transfer does not require validation' );
			return FALSE;
		}

		// Receipt sweeper is empty
		if( ! $this->transval_transfer_sweeper )
		{
			set_message( 'Missing transfer sweeper information' );
			return FALSE;
		}

		$ci->db->trans_start();
		$this->set( 'transval_transfer_status', TRANSFER_VALIDATION_TRANSFER_DISPUTED );
		$this->set( 'transval_transfer_datetime', date( TIMESTAMP_FORMAT ) );
		$this->set( 'transval_transfer_user_id', current_user( TRUE ) );
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

	public function complete()
	{
		$ci =& get_instance();

		// Check if transfer is approved

		$ci->db->trans_start();
		$this->set( 'transval_receipt_status', TRANSFER_VALIDATION_RECEIPT_VALIDATED );
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


}