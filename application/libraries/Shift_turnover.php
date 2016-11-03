<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift_turnover extends Base_model
{
	protected $st_store_id;
	protected $st_from_date;
	protected $st_from_shift_id;
	protected $st_to_date;
	protected $st_to_shift_id;
	protected $st_remarks;
	protected $st_status;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	protected $items;
	protected $previousStatus;

	public function __construct()
	{
		$this->primary_table = 'shift_turnovers';
		$this->db_fields = array(
			'st_store_id' => array( 'type' => 'integer' ),
			'st_from_date' => array( 'type' => 'date' ),
			'st_from_shift_id' => array( 'type' => 'integer' ),
			'st_to_date' => array( 'type' => 'date' ),
			'st_to_shift_id' => array( 'type' => 'integer' ),
			'st_remarks' => array( 'type' => 'string' ),
			'st_status' => array( 'type' => 'integer' )
		);
		parent::__construct();
	}

	public function get_shift_turnovers( $params = array() )
	{
		$ci =& get_instance();
		$ci->load->library( 'shift_turnover' );

		$date = param( $params, 'date' );
		$shift = param( $params, 'shift' );

		$limit = param( $params, 'limit' );
		$page = param( $params, 'page', 1 );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'st_from_date DESC, st_from_shift_id DESC, id DESC' );

		$select = '*';

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}
		if( $order )
		{
			$ci->db->order_by( $order );
		}

		if( $date )
		{
			$ci->db->where( 'st_from_date', $date_sent );
		}

		if( $shift )
		{
			$ci->db->where( 'st_from_shift_id', $shift );
		}

		$ci->db->select( $select );
		$query = $ci->db->get( 'shift_turnovers' );

		if( $format == 'object')
		{
			return $query->result( 'Shift_turnover' );
		}
		elseif( $format == 'array' )
		{
			return $query->result_array();
		}

		return NULL;
	}

	public function get_by_store_date_shift( $store, $date, $shift )
	{
		$ci =& get_instance();

		$ci->db->where( 'st_store_id', $store );
		$ci->db->where( 'st_from_date', $date );
		$ci->db->where( 'st_from_shift_id', $shift );
		$ci->db->limit( 1 );
		$query = $ci->db->get( $this->primary_table );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class ( $this ) );
		}
		else
		{
			return NULL;
		}
	}

	public function count_turnovers( $params = array() )
	{
		$ci =& get_instance();
		$ci->load->library( 'Shift_turnover' );

		$date = param( $params, 'sent' );
		$shift = param( $params, 'shift' );

		if( $date )
		{
			$ci->db->where( 'st_from_date', $date );
		}

		if( $shift )
		{
			$ci->db->where( 'st_from_shift_id', $shift );
		}

		$count = $ci->db->count_all_results( 'shift_turnovers' );

		return $count;
	}


	public function count_pending_turnovers( $params = array() )
	{
		$date = param( $params, 'date' );
		$shift = param( $params, 'shift' );

		$ci =& get_instance();

		if( $date )
		{
			$ci->db->where( 'st_from_date', $date );
		}

		if( $shift )
		{
			$ci->db->where( 'st_from_shift_id', $shift );
		}

		$ci->db->where( 'st_status', SHIFT_TURNOVER_OPEN );

		$count = $ci->db->count_all_results( 'shift_turnovers' );

		return $count;
	}


	public function get_items( $force = FALSE )
	{
		$ci =& get_instance();

		if( !empty( $this->items ) && !$force )
		{
			return $this->items;
		}
		else
		{
			$ci->load->library( 'shift_turnover_item' );
			$ci->db->where( 'sti.sti_turnover_id', $this->id );
			$ci->db->join( 'items i', 'i.id = sti.sti_item_id', 'left' );
			$query = $ci->db->get( 'shift_turnover_items sti' );
			$items = $query->result( 'Shift_turnover_item' );

			$sql = 'SELECT
								sti.*,
								i.item_name, i.item_description, i.item_group, i.item_unit,
								ts.movement
							FROM shift_turnover_items AS sti
							LEFT JOIN items AS i
								ON i.id = sti.sti_item_id

							LEFT JOIN (
								SELECT
									t.store_inventory_id,
									SUM( transaction_quantity ) AS movement
								FROM transactions AS t
								LEFT JOIN store_inventory AS si
									ON si.id = t.store_inventory_id
								WHERE
									si.store_id = ?
									AND t.transaction_datetime >= ?
									AND t.transaction_datetime <= ?
									AND t.transaction_shift = ?
								GROUP BY t.store_inventory_id
							) AS ts
								ON ts.store_inventory_id = sti.sti_inventory_id
							WHERE
								sti.sti_turnover_id = ?';

			$query_params[] = $this->st_store_id;
			$query_params[] = $this->st_from_date.' 00:00:00';
			$query_params[] = $this->st_from_date.' 23:59:59';
			$query_params[] = $this->st_from_shift_id;
			$query_params[] = $this->id;

			$query = $ci->db->query( $sql, $query_params );
			$items = $query->result( 'Shift_turnover_item' );

			foreach( $items as $item )
			{
				$this->items[$item->get( 'sti_inventory_id' )] = $item;
			}
		}

		return $this->items;
	}

	public function get_previous_turnover()
	{
		$ci =& get_instance();

		$ci->db->where( 'st_store_id', $this->st_store_id );
		$ci->db->where( 'st_to_date', $this->st_from_date );
		$ci->db->where( 'st_to_shift_id', $this->st_from_shift_id );
		$ci->db->limit( 1 );
		$query = $ci->db->get( 'shift_turnovers' );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class ( $this ) );
		}
		else
		{
			return NULL;
		}
	}

	public function load_turnover_items()
	{
		// Load turnover items from database
		$this->get_items();

		$ci =& get_instance();
		$ci->load->library( 'store' );

		if( !isset( $this->id ) )
		{
			$Store = new Store();
			$store = $Store->get_by_id( $this->st_store_id );

			// Get possible additional inventory items
			$params = array(
				'date' => $this->st_from_date,
				'shift' => $this->st_from_shift_id );

			$store_items = $store->get_items( $params );
			foreach( $store_items as $inv_id => $store_item )
			{
				if( !isset( $this->items[$inv_id] ) )
				{
					$new_item = new Shift_turnover_item();
					$new_item->set( 'sti_item_id', $store_item->get( 'item_id' ) );
					$new_item->set( 'sti_inventory_id', $store_item->get( 'id' ) );
					$new_item->set( 'previous_balance', NULL );
					$new_item->set( 'sti_beginning_balance', NULL );
					$new_item->set( 'sti_ending_balance', NULL );

					$new_item->set( 'item_name', $store_item->get( 'item_name' ) );
					$new_item->set( 'item_description', $store_item->get( 'item_description' ) );
					$new_item->set( 'item_group', $store_item->get( 'item_group' ) );
					$new_item->set( 'item_unit', $store_item->get( 'item_unit' ) );
					$new_item->set( 'movement', $store_item->get( 'movement' ) );
					$this->items[$inv_id] = $new_item;
				}
			}
		}

		// Get previous turnover balances if available
		$prev_turnover = $this->get_previous_turnover();
		if( $prev_turnover )
		{
			$prev_items = $prev_turnover->get_items();
			foreach( $prev_items as $inv_id => $prev_item )
			{
				if( isset( $this->items[$inv_id] ) )
				{
					$this->items[$inv_id]->set( 'previous_balance', $prev_item->get( 'sti_ending_balance' ) );
					//TODO: Update movement
				}
				else
				{
					//$new_item = $prev_item;
					$new_item->set( 'sti_turnover_id', $this->id );
					$new_item->set( 'previous_balance', $prev_item->get( 'sti_ending_balance' ) );
					$new_item->set( 'sti_beginning_balance', $prev_item->get( 'sti_ending_balance' ) );
					$new_item->set( 'sti_ending_balance', NULL );
					$this->items[$inv_id] = $new_item;
				}
			}
		}

		if( !isset( $this->id ) )
		{ // new shift turnover
			foreach( $this->items as $inv_id => $item )
			{
				$previous_balance = $item->get( 'previous_balance' );
				$previous_balance = empty( $previous_balance ) ? 0 : $previous_balance;
				$item->set( 'sti_beginning_balance', $previous_balance );
				$item->set( 'sti_ending_balance', NULL );
			}
		}
		else
		{
			foreach( $this->items as $inv_id => $item )
			{
				$beginning_balance = $item->get( 'sti_beginning_balance' );
				$beginning_balance = empty( $beginning_balance ) ? 0 : $beginning_balance;
				$movement = $item->get( 'movement' );
				$movement = empty( $movement ) ? 0 : $movement;
				$ending_balance = $item->get( 'sti_ending_balance' );
				if( empty( $ending_balance ) )
				{
					$item->set( 'sti_ending_balance', $beginning_balance + $movement );
				}
			}
		}
	}

	public function db_save()
	{
		$ci =& get_instance();

		$result = NULL;
		$ci->db->trans_start();

		if( isset( $this->id ) )
		{ // Update transfer record
			if( $this->_check_data() )
			{
				foreach( $this->items as $item )
				{
					if( ! $item->get( 'id' ) )
					{
						$item->set( 'sti_turnover_id', $this->id );
					}

					if( ( $this->st_status != SHIFT_TURNOVER_CLOSE ) && ( $item->get( 'sti_ending_balance') != NULL ) )
					{
						$item->set( 'sti_ending_balance', NULL );
					}

					if( $item->db_changes )
					{
						if( !$item->db_save() )
						{
							$ci->db->trans_rollback();
							return FALSE;
						}
					}
				}

				// Set fields and updata record metadata
				$this->_update_timestamps( FALSE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_update();
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			if( $this->_check_data() )
			{
				// Set fields and updata record metadata
				if( !isset( $this->st_status ) )
				{
					$this->set( 'st_status', SHIFT_TURNOVER_OPEN );
				}

				$this->_update_timestamps( TRUE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_insert();

				// save turnover items

				foreach( $this->items as $item )
				{
					$item->set( 'sti_turnover_id', $this->id );
					if( !$item->db_save() )
					{
						$ci->db->trans_rollback();
						return FALSE;
					}
				}
			}
		}

		$ci->db->trans_complete();

		// Reset record changes
		$this->_reset_db_changes();
		$this->previousStatus = NULL;

		return $result;
	}

	public function end_shift()
	{
		$ci =& get_instance();

		$ci->db->trans_start();
		$this->set( 'st_status', SHIFT_TURNOVER_CLOSE );
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
				set_message( 'A database error has occurred while trying to close the shift.', 'error' );
				return FALSE;
			}
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
					//echo 'Setting '.$field.' from '.$this->$field.' to '.$value.'...<br />';
					$r->set( $field, $value );
				}
			}
			elseif( $field == 'items' )
			{ // load items
				$ci->load->library( 'shift_turnover_item' );

				$this->items = array();
				foreach( $value as $i )
				{
					$item = new Shift_turnover_item();
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