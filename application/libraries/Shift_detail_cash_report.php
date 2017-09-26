<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift_detail_cash_report extends Base_model
{
	protected $sdcr_allocation_id;
	protected $sdcr_store_id;
	protected $sdcr_shift_id;
	protected $sdcr_teller_id;
	protected $sdcr_pos_id;
	protected $sdcr_business_date;
	protected $sdcr_login_time;
	protected $sdcr_logout_time;

	protected $shift;
	protected $items;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';


	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'shift_detail_cash_reports';
		$this->db_fields = array(
			'sdcr_allocation_id' => array( 'type' => 'integer' ),
			'sdcr_store_id' => array( 'type' => 'integer' ),
			'sdcr_shift_id' => array( 'type' => 'integer' ),
			'sdcr_teller_id' => array( 'type' => 'integer' ),
			'sdcr_pos_id' => array( 'type' => 'integer' ),
			'sdcr_business_date' => array( 'type' => 'date' ),
			'sdcr_login_time' => array( 'type' => 'datetime' ),
			'sdcr_logout_time' => array( 'type' => 'datetime' )
		);
	}


	public function get_items( $force = FALSE )
	{
		$ci =& get_instance();

		if( !isset( $this->items ) || $force )
		{
			$ci->load->library( 'shift_detail_cash_report_item' );
			$ci->db->where( 'sdcri_sdcr_id', $this->id );
			$query = $ci->db->get( 'shift_detail_cash_report_items AS sdcri' );
			$this->items = $query->result( 'Shift_detail_cash_report_item' );
		}

		return $this->items;
	}


	public function get_shift()
	{
		if( ! isset( $this->shift ) )
		{
			$ci =& get_instance();
			$ci->load->library( 'shift' );
			$Shift = new Shift();
			$this->shift = $Shift->get_by_id( $this->sdcr_shift_id );
		}

		return $this->shift;
	}


	public function _check_items()
	{
		return TRUE;
	}


	public function _set_default_values()
	{
		$ci =& get_instance();

		if( empty( $this->sdcr_shift_id ) )
		{
			$this->set( 'sdcr_shift_id', current_shift( TRUE ) );
		}

		if( empty( $this->sdcr_store_id ) )
		{
			$this->set( 'sdcr_store_id', current_store( TRUE ) );
		}

		return TRUE;
	}


	public function db_save()
	{
		$ci =& get_instance();

		$result = NULL;
		$ci->db->trans_start();

		$this->_set_default_values();

		if( isset( $this->id ) )
		{
			if( $this->_check_items() )
			{
				$this->_update_timestamps( FALSE );
				$ci->db->set( $this->db_changes );
				$result = $this->_db_update();

				// Update reading items
				foreach( $this->items as $item )
				{
					if( $item->get( 'marked_void' ) )
					{
						$item->db_remove();
					}
					else
					{
						$item->db_save();
					}
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
				foreach( $this->items as $item )
				{
					$item->set( 'sdcri_sdcr_id', $this->id );
					if( ! $item->db_save() )
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
					$r->set( $field, $value );
				}
			}
			elseif( $field == 'items' )
			{ // load items
				$ci->load->library( 'shift_detail_cash_report_item' );

				$this->items = array();
				foreach( $value as $i )
				{
					$item = new Shift_detail_cash_report_item();
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
}