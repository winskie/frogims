<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tvm_reading extends Base_model
{
	protected $tvmr_store_id;
	protected $tvmr_machine_id;
	protected $tvmr_datetime;
	protected $tvmr_shift_id;
	protected $tvmr_cashier_id;
	protected $tvmr_cashier_name;
	protected $tvmr_last_reading;

	protected $shift;
	protected $previous_shift_reading;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	protected $readings;


	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'tvm_readings';
		$this->db_fields = array(
			'tvmr_store_id' => array( 'type' => 'integer' ),
			'tvmr_machine_id' => array( 'type' => 'string' ),
			'tvmr_datetime' => array( 'type' => 'datetime' ),
			'tvmr_shift_id' => array( 'type' => 'integer' ),
			'tvmr_cashier_id' => array( 'type' => 'integer' ),
			'tvmr_cashier_name' => array( 'type' => 'string' ),
			'tvmr_last_reading' => array( 'type' => 'boolean' ),
		);
	}


	public function get_readings( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->readings ) || $force )
		{
			$ci->load->library( 'tvm_reading_item' );
			$ci->db->where( 'tvmri_reading_id', $this->id );
			$query = $ci->db->get( 'tvm_reading_items AS tri' );
			$this->readings = $query->result( 'Tvm_reading_item' );
		}

		return $this->readings;
	}


	public function get_shift()
	{
		if( ! isset( $this->shift ) )
		{
			$ci =& get_instance();
			$ci->load->library( 'shift' );
			$Shift = new Shift();
			$this->shift = $Shift->get_by_id( $this->tvmr_shift_id );
		}

		return $this->shift;
	}


	public function get_by_id( $id )
	{
		$ci =& get_instance();

		$ci->db->select( 'tvmr.*, s.shift_num' );
		$ci->db->where( 'tvmr.id', $id );
		$ci->db->join( 'shifts s', 's.id = tvmr.tvmr_shift_id' );
		$ci->db->limit( 1 );
		$query = $ci->db->get( $this->primary_table.' tvmr' );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class( $this ) );
		}

		return NULL;
	}


	public function get_by_shift_last_reading( $params )
	{
		$ci =& get_instance();

		$machine_id = param( $params, 'machine', NULL, 'integer' );
		$business_date = param( $params, 'date', NULL, 'date' );
		$shift_id = param( $params, 'shift', NULL, 'integer' );
		$limit = param( $params, 'limit', 1, 'integer' );
		//$last = param( $params, 'last', 1, 'integer' );
		$order = 'tvmr_datetime DESC, id DESC';

		$select = array( $this->primary_table.'.*' );

		$ci->db->select( implode(', ', $select ) );
		if( isset( $machine_id ) )
		{
			$ci->db->where( 'tvmr_machine_id', $machine_id );
		}

		if( isset( $business_date ) )
		{
			$ci->db->where( 'DATE( tvmr_datetime ) =', $business_date );
		}

		if( isset( $shift_id ) )
		{
			$ci->db->where( 'tvmr_shift_id', $shift_id );
		}

		$ci->db->where( 'tvmr_store_id', current_store( TRUE ) );

		$ci->db->limit( $limit );
		$ci->db->order_by( $order );

		$query = $ci->db->get( $this->primary_table );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class( $this ) );
		}

		return NULL;
	}


	public function get_previous_shift_last_reading()
	{
		if( ! isset( $this->previous_shift_reading ) )
		{
			$ci =& get_instance();

			$current_shift = $this->get_shift();
			$previous_shift = $current_shift->get_previous_shift();

			if( $current_shift->get( 'shift_order' ) === 1 )
			{
				$previous_shift_date = date( DATE_FORMAT, strtotime( '-1 day', strtotime( $this->tvmr_datetime ) ) );
			}
			else
			{
				$previous_shift_date = date( DATE_FORMAT, strtotime( $this->tvmr_datetime ) );
			}

			$ci->db->where( 'tvmr_store_id', $this->tvmr_store_id );
			$ci->db->where( 'tvmr_machine_id', $this->tvmr_machine_id );
			$ci->db->where( 'tvmr_shift_id', $previous_shift->get( 'id' ) );
			$ci->db->where( 'DATE(tvmr_datetime)', $previous_shift_date );
			$ci->db->where( 'tvmr_last_reading', 1 );

			$query = $ci->db->get( $this->primary_table );
			//die( var_dump( $ci->db->last_query() ) );

			$this->previous_shift_reading = $query->custom_row_object( 0, get_class( $this ) );
		}

		return $this->previous_shift_reading;
	}


	public function db_save()
	{
		$ci =& get_instance();

		$result = NULL;
		$ci->db->trans_start();

		$this->_set_default_values();
		$this->_toggle_last_reading();

		if( isset( $this->id ) )
		{
			if( $this->_check_items() )
			{
				$this->_update_timestamps( FALSE );
				$ci->db->set( $this->db_changes );
				$result = $this->_db_update();

				// Update reading items
				foreach( $this->readings as $reading )
				{
					$reading->db_save();
				}
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
				// Set fields and update record metadata
				$this->_update_timestamps( TRUE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_insert();

				// Save TVM readings
				foreach( $this->readings as $reading )
				{
					$reading->set( 'tvmri_reading_id', $this->id );
					if( ! $reading->db_save() )
					{
						$ci->db->trans_rollback();
						die( 'Error saving reading' );
						return FALSE;
					}
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


	public function load_from_data( $data = array(), $overwrite = TRUE )
	{
		$ci =& get_instance();

		$ci->load->library( 'tvm_reading_item' );

		// Try to get existing value first if ID exists
		if( array_key_exists( 'id', $data ) && $data['id'] )
		{
			$r = $this->get_by_id( $data['id'] );
			$r->get_readings( TRUE );
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
			elseif( $field == 'readings' )
			{ // load items
				foreach( $value as $i )
				{
					$item = new TVM_reading_item();
					$item_id = param( $i, 'id' );

					if( is_null( $item_id ) )
					{
						$item = $item->load_from_data( $i );
						$r->readings[] = $item;
					}
					else
					{
						$index = array_value_search( 'id', $item_id, $r->readings, FALSE );
						if( ! is_null( $index ) )
						{
							$r->readings[$index] = $item->load_from_data( $i );
						}
						else
						{
							$item = $item->load_from_data( $i );
							$r->readings[] = $item;
						}
					}
				}
			}
		}
		return $r;
	}


	public function _check_items()
	{
		return TRUE;
	}


	public function _set_default_values()
	{
		$ci =& get_instance();

		if( empty( $this->tvmr_shift_id ) )
		{
			$this->set( 'tvmr_shift_id', current_shift( TRUE ) );
		}

		if( empty( $this->tvmr_store_id ) )
		{
			$this->set( 'tvmr_store_id', current_store( TRUE ) );
		}

		if( empty( $this->tvmr_cashier_id ) && empty( $this->tvmr_cashier_name ) )
		{
			$current_User = current_user();
			$this->set( 'tvmr_cashier_id', $current_User->get( 'id' ) );
			$this->set( 'tvmr_cashier_name', $current_User->get( 'full_name' ) );
		}

		if( empty( $this->tvmr_datetime ) )
		{
			$this->set( 'tvmr_datetime', date( DATE_FORMAT ) );
		}

		return TRUE;
	}


	public function _toggle_last_reading()
	{
		if( $this->tvmr_last_reading )
		{
			$ci =& get_instance();

			$ci->db->where( 'tvmr_machine_id', $this->tvmr_machine_id );
			$ci->db->where( 'DATE(tvmr_datetime)', date( DATE_FORMAT, strtotime( $this->tvmr_datetime ) ) );
			$ci->db->where( 'tvmr_shift_id', $this->tvmr_shift_id );
			$ci->db->where( 'id !=', $this->id );
			$ci->db->set( 'tvmr_last_reading', FALSE );
			$ci->db->update( $this->primary_table );
		}
	}
}