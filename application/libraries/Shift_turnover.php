<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift_turnover extends Base_model
{
	protected $st_store_id;
	protected $st_from_date;
	protected $st_from_shift_id;
	protected $st_to_date;
	protected $st_to_shift_id;
	protected $st_start_user_id;
	protected $st_end_user_id;
	protected $st_remarks;
	protected $st_status;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

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
			'st_start_user_id' => array( 'type' => 'integer' ),
			'st_end_user_id' => array( 'type' => 'integer' ),
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
								i.item_name, i.item_description, i.item_class, i.item_group, i.item_type, i.item_unit,
								si.parent_item_id, pi.item_name AS parent_item_name,
								ts.movement,
								IF( ct.conversion_factor IS NULL, sti.sti_beginning_balance, sti.sti_beginning_balance * ct.conversion_factor ) AS base_beginning_balance,
								IF( ct.conversion_factor IS NULL, sti.sti_ending_balance, sti.sti_ending_balance * ct.conversion_factor ) AS base_ending_balance,
								IF( ct.conversion_factor IS NULL, ts.movement, ts.movement * ct.conversion_factor ) AS base_movement,
								ip.iprice_unit_price
							FROM shift_turnover_items AS sti
							LEFT JOIN items AS i
								ON i.id = sti.sti_item_id
							LEFT JOIN store_inventory AS si
								ON si.id = sti.sti_inventory_id
							LEFT JOIN items AS pi
								ON pi.id = si.parent_item_id
							LEFT JOIN conversion_table AS ct
								ON ct.source_item_id = i.base_item_id AND ct.target_item_id = i.id
							LEFT JOIN item_prices AS ip
								ON ip.iprice_item_id = i.id
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

			$store_items = $store->get_shift_turnover_items( $params );
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
					$new_item->set( 'parent_item_name', $store_item->get( 'parent_item_name' ) );


					$this->items[$inv_id] = $new_item;
				}
			}
		}

		// Get previous turnover balances if available
		$prev_turnover = $this->get_previous_turnover();
		if( $prev_turnover )
		{
			$prev_items = $prev_turnover->get_shift_turnovers();
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

					if( ( $this->st_status != SHIFT_TURNOVER_CLOSED ) && ( $item->get( 'sti_ending_balance') != NULL ) )
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

				// Set current user
				$this->set( 'st_start_user_id', current_user( TRUE ) );

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

		// Do checks before allowing end of shift

		// No unreplenished TVM change fund

		// TVM readings present

		$ci->db->trans_start();
		$this->set( 'st_end_user_id', current_user( TRUE ) );
		$this->set( 'st_status', SHIFT_TURNOVER_CLOSED );
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

	/**
	 * Change Fund Beginning Balance
	 */
	public function beginning_balance( $type = 'cash_in_vault' )
	{
		$ci =& get_instance();
		$items = $this->get_items();
		$beginning_balance = 0.00;

		switch( $type )
		{
			case 'cash_in_vault':
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'cash' && in_array( $item->get( 'parent_item_name' ), array( 'Change Fund', 'Sales' ) ) )
					{
						$beginning_balance += $item->get( 'base_beginning_balance' ) * $item->get( 'iprice_unit_price' );
					}
				}
				break;
		}

		return $beginning_balance;
	}


	/**
	 * Gross Sales
	 */
	public function total_gross_sales()
	{
		$ci =& get_instance();
		$ci->load->library( 'category' );
		$Category = new Category();
		$sales_collection_category = $Category->get_by_name( 'SalesColl' );

		$sql = "SELECT
							a.assignee_type, SUM( ai.allocated_quantity * ip.iprice_unit_price ) AS amount
						FROM allocations AS a
						LEFT JOIN allocation_items AS ai
							ON ai.allocation_id = a.id
						LEFT JOIN items AS i
							ON i.id = ai.allocated_item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						WHERE
							a.business_date = '".$this->st_from_date."'
							AND a.store_id = ".$this->st_store_id."
							AND ai.cashier_shift_id = ".$this->st_from_shift_id."
							AND ai.allocation_item_type = ".ALLOCATION_ITEM_TYPE_REMITTANCE."
							AND i.item_class = 'cash'
							AND ai.allocation_category_id = ".$sales_collection_category->get( 'id' )."
							AND NOT ai.allocation_item_status = ".REMITTANCE_ITEM_VOIDED."
						GROUP BY a.assignee_type";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		$gross_sales = array( 'teller' => 0.00, 'TVM' => 0.00 );
		foreach( $r as $row )
		{
			switch( $row['assignee_type'] )
			{
				case ALLOCATION_ASSIGNEE_TELLER:
					$gross_sales['teller'] += $row['amount'];
					break;

				case ALLOCATION_ASSIGNEE_MACHINE:
					$gross_sales['TVM'] += $row['amount'];
					break;
			}
		}

		return $gross_sales;
	}

	/**
	 * Hopper Readings
	 */
	public function hopper_readings()
	{
		$ci =& get_instance();

		$hopper_readings = array(
			'Php5' => array( 'previous' => 0.00, 'current' => 0.00 ),
			'Php1' => array( 'previous' => 0.00, 'current' => 0.00 ) );

		$sql = "SELECT
							tvmr_type,
							SUM(IF(tvmr_type = 'hopper_php5', tvmr_reading * 5, tvmr_reading)) AS current_reading,
							SUM(IF(tvmr_type = 'hopper_php5', tvmr_previous_reading * 5, tvmr_previous_reading)) AS previous_reading
						FROM tvm_readings AS tr
						WHERE
							tvmr_type IN ('hopper_php1', 'hopper_php5')
							AND tvmr_date = '".$this->st_from_date."'
							AND tvmr_store_id = ".$this->st_store_id."
							AND tvmr_shift_id = ".$this->st_from_shift_id."
						GROUP BY tvmr_type";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['tvmr_type'] )
			{
				case 'hopper_php1':
					$hopper_readings['Php1']['current'] += $row['current_reading'];
					$hopper_readings['Php1']['previous'] += $row['previous_reading'];
					break;

				case 'hopper_php5':
					$hopper_readings['Php5']['current'] += $row['current_reading'];
					$hopper_readings['Php5']['previous'] += $row['previous_reading'];
					break;
			}
		}

		return $hopper_readings;
	}

	/**
	 * Hopper Replenishments
	 */
	public function total_hopper_replenishment()
	{
		$ci =& get_instance();

		$ci->load->library( 'item' );
		$ci->load->library( 'category' );

		$Item = new Item();
		$Category = new Category();

		$php1_coin = $Item->get_by_name( 'Php1 Coin' );
		$php5_coin = $Item->get_by_name( 'Php5 Coin' );
		$php1_bag = $Item->get_by_name( 'Bag Php1@100' );
		$php5_bag = $Item->get_by_name( 'Bag Php5@100' );

		$hopper_replenishment_category = $Category->get_by_name( 'HopAlloc' );

		$valid_items = array( $php1_coin->get( 'id' ), $php5_coin->get( 'id' ), $php1_bag->get( 'id' ), $php5_bag->get( 'id' ) );
		$replenishment_amount = 0.00;

		$sql = "SELECT
							SUM( IF( ai.allocated_item_id IN (".implode( ', ', $valid_items ).") AND ai.allocation_item_type = ".ALLOCATION_ITEM_TYPE_ALLOCATION.", ip.iprice_unit_price * ai.allocated_quantity, 0 ) ) AS amount
						FROM allocations a
						LEFT JOIN allocation_items ai
							ON ai.allocation_id = a.id
						LEFT JOIN items i
							ON i.id = ai.allocated_item_id
						LEFT JOIN item_prices ip
							ON ip.iprice_item_id = i.id
						WHERE
							a.business_date ='".$this->st_from_date."'
							AND a.store_id = ".$this->st_store_id."
							AND a.assignee_type = ".ALLOCATION_ASSIGNEE_MACHINE."
							AND ai.cashier_shift_id = ".$this->st_from_shift_id."
							AND i.item_class = 'cash'
							AND ai.allocation_category_id = ".$hopper_replenishment_category->get( 'id' )."
							AND NOT ai.allocation_item_status IN (".implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) ).")";
		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		$replenishment_amount = $r['amount'];

		return floatval( $replenishment_amount );
	}

	/**
	 * Refunded TVMIR
	 */
	public function total_tvmir_refund()
	{
		$ci =& get_instance();

		$refunded_amount = 0.00;

		$sql = "SELECT
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount
						FROM transfers t
						LEFT JOIN transfer_items ti
							ON ti.transfer_id = t.id
						LEFT JOIN items i
							ON i.id = ti.item_id
						LEFT JOIN item_prices ip
							ON ip.iprice_item_id = i.id
						WHERE
							t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_category = ".TRANSFER_CATEGORY_ADD_TVMIR."
							AND NOT ti.transfer_item_status IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")";
		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		$refunded_amount = $r['amount'];

		return $refunded_amount;
	}

	/**
	 * Teller Sales additions
	 */
	public function teller_sales_additions()
	{
		$ci =& get_instance();

		$ci->load->library( 'sales_item' );
		$Sales_Item = new Sales_item();

		$additions = array(
			'excess_time' => 0.00,
			'mismatch' => 0.00,
			'lost_ticket' => 0.00,
			'others' => 0.00 );

		$excess_sitem = $Sales_Item->get_by_name( 'Excess Time' );
		$mismatch_sitem = $Sales_Item->get_by_name( 'Mismatch' );
		$lost_ticket_sitem = $Sales_Item->get_by_name( 'Payment for Lost Ticket' );
		$addition_ids = array( $excess_sitem->get( 'id' ), $mismatch_sitem->get( 'id' ), $lost_ticket_sitem->get( 'id' ) );

		$sql = "SELECT
							SUM(IF(asi.alsale_sales_item_id = ".$excess_sitem->get( 'id' ).", asi.alsale_amount, NULL)) AS excess_time,
							SUM(IF(asi.alsale_sales_item_id != ".$mismatch_sitem->get( 'id' ).", asi.alsale_amount, NULL)) AS mismatch,
							SUM(IF(asi.alsale_sales_item_id != ".$lost_ticket_sitem->get( 'id' ).", asi.alsale_amount, NULL)) AS lost_ticket,
							SUM(IF(asi.alsale_sales_item_id NOT IN (".implode( ',', $addition_ids )."), asi.alsale_amount, NULL)) AS others
						FROM allocations AS a
						LEFT JOIN allocation_sales_items AS asi
							ON asi.alsale_allocation_id = a.id
						LEFT JOIN sales_items AS si
							ON si.id = asi.alsale_sales_item_id
						WHERE
							a.business_date = '".$this->st_from_date."'
							AND a.store_id = ".$this->st_store_id."
							AND a.assignee_type = ".ALLOCATION_ASSIGNEE_TELLER."
							AND asi.alsale_shift_id = ".$this->st_from_shift_id."
							AND si.slitem_mode = 1
							AND NOT asi.alsale_sales_item_status = ".SALES_ITEM_VOIDED;

		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		$additions['excess_time'] += $r['excess_time'];
		$additions['mismatch'] += $r['mismatch'];
		$additions['lost_ticket'] += $r['lost_ticket'];
		$additions['others'] += $r['others'];

		return $additions;
	}

	/**
	 * Teller Sales deductions
	 */
	public function teller_sales_deductions()
	{
		$ci =& get_instance();

		$ci->load->library( 'sales_item' );
		$Sales_Item = new Sales_item();

		$deductions = array(
			'tcerf' => 0.00,
			'others' => 0.00 );

		$tcerf_sales_item = $Sales_Item->get_by_name( 'TCERF' );
		$sql = "SELECT
							SUM(IF(asi.alsale_sales_item_id = ".$tcerf_sales_item->get( 'id' ).", asi.alsale_amount, NULL)) AS tcerf,
							SUM(IF(asi.alsale_sales_item_id != ".$tcerf_sales_item->get( 'id' ).", asi.alsale_amount, NULL)) AS others
						FROM allocations AS a
						LEFT JOIN allocation_sales_items AS asi
							ON asi.alsale_allocation_id = a.id
						LEFT JOIN sales_items AS si
							ON si.id = asi.alsale_sales_item_id
						WHERE
							a.business_date = '".$this->st_from_date."'
							AND a.store_id = ".$this->st_store_id."
							AND a.assignee_type = ".ALLOCATION_ASSIGNEE_TELLER."
							AND asi.alsale_shift_id = ".$this->st_from_shift_id."
							AND si.slitem_mode = 0
							AND NOT asi.alsale_sales_item_status = ".SALES_ITEM_VOIDED;

		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		$deductions['tcerf'] += $r['tcerf'];
		$deductions['others'] += $r['others'];

		return $deductions;
	}

	/**
	 * Returned Change Fund
	 */
	public function returned_change_fund()
	{
		$ci =& get_instance();

		$ci->load->library( 'category' );
		$Category = new Category();

		$change_fund_return_cat = $Category->get_by_name( 'CFundRet' );

		$amount = array(
			'teller' => 0.00,
			'TVM' => 0.00 );

		// Station Teller
		$sql = "SELECT
							SUM(ai.allocated_quantity * ip.iprice_unit_price) AS amount
						FROM allocations AS a
						LEFT JOIN allocation_items AS ai
							ON ai.allocation_id = a.id
						LEFT JOIN items AS i
							ON i.id = ai.allocated_item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						WHERE
							a.business_date = '".$this->st_from_date."'
							AND a.store_id = ".$this->st_store_id."
							AND a.assignee_type = ".ALLOCATION_ASSIGNEE_TELLER."
							AND ai.cashier_shift_id = ".$this->st_from_shift_id."
							AND ai.allocation_item_type = ".ALLOCATION_ITEM_TYPE_REMITTANCE."
							AND i.item_class = 'cash'
							AND ai.allocation_category_id = ".$change_fund_return_cat->get( 'id' )."
							AND NOT ai.allocation_item_status = ".REMITTANCE_ITEM_VOIDED;
		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		$amount['teller'] += $r['amount'];

		// TVM
		$sql = "SELECT
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount
						FROM transfers t
						LEFT JOIN transfer_items ti
							ON ti.transfer_id = t.id
						LEFT JOIN items i
							ON i.id = ti.item_id
						LEFT JOIN item_prices ip
							ON ip.iprice_item_id = i.id
						WHERE
							t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_category = ".TRANSFER_CATEGORY_REPLENISH_TVM_CFUND."
							AND NOT ti.transfer_item_status IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")";
		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		$amount['TVM'] += $r['amount'];

		return $amount;
	}

	/**
	 * Deposit to Bank
	 */
	public function deposited_to_bank()
	{
		$ci =& get_instance();

		$sql = "SELECT
							t.transfer_init_shift_id, s.shift_num,
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount
						FROM transfers t
						LEFT JOIN transfer_items ti
							ON ti.transfer_id = t.id
						LEFT JOIN items i
							ON i.id = ti.item_id
						LEFT JOIN item_prices ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN shifts AS s
							ON s.id = t.transfer_init_shift_id
						WHERE
							t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_category = ".TRANSFER_CATEGORY_BANK_DEPOSIT."
							AND t.transfer_status = ".TRANSFER_APPROVED."
							AND NOT ti.transfer_item_status IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY t.transfer_init_shift_id, s.shift_num";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		$deposits = array();
		foreach( $r as $row )
		{
			$deposits[$row['shift_num']] = $row['amount'];
		}

		return $deposits;
	}

	/**
	 * Get amount for deposit to bank
	 *
	 * TVM: Gross Sales - Hopper/Change Fund - Refunded TVMIR
	 * Teller: Gross Sales - TCERF - Other deductions + Other additions
	 */
	public function gross_sales_deposit()
	{
		$ci =& get_instance();
		$ci->load->library( 'category' );
		$Category = new Category();
		$sales_collection_category = $Category->get_by_name( 'SalesColl' );

		$sql = "SELECT
							a.assignee_type,
							IF(ti.id IS NULL, false, true) AS for_deposit,
							SUM( ai.allocated_quantity * ip.iprice_unit_price ) AS amount
						FROM allocations AS a
						LEFT JOIN allocation_items AS ai
							ON ai.allocation_id = a.id
						LEFT JOIN items AS i
							ON i.id = ai.allocated_item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_item_allocation_item_id = ai.id
						WHERE
							a.business_date = '".$this->st_from_date."'
							AND a.store_id = ".$this->st_store_id."
							AND ai.cashier_shift_id = ".$this->st_from_shift_id."
							AND ai.allocation_item_type = ".ALLOCATION_ITEM_TYPE_REMITTANCE."
							AND i.item_class = 'cash'
							AND ai.allocation_category_id = ".$sales_collection_category->get( 'id' )."
							AND NOT ai.allocation_item_status = ".REMITTANCE_ITEM_VOIDED."
						GROUP BY for_deposit, a.assignee_type";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		$sales = array(
			'deposit' => array(
				'teller' => 0.00,
				'TVM' => 0.00 ),
			'sales' => array(
				'teller' => 0.00,
				'TVM' => 0.00 ) );

		foreach( $r as $row )
		{
			if( $row['for_deposit'] )
			{
				switch( $row['assignee_type'] )
				{
					case ALLOCATION_ASSIGNEE_TELLER:
						$sales['deposit']['teller'] += $row['amount'];
						break;

					case ALLOCATION_ASSIGNEE_MACHINE:
						$sales['deposit']['TVM'] += $row['amount'];
						break;
				}
			}
			else
			{
				switch( $row['assignee_type'] )
				{
					case ALLOCATION_ASSIGNEE_TELLER:
						$sales['sales']['teller'] += $row['amount'];
						break;

					case ALLOCATION_ASSIGNEE_MACHINE:
						$sales['sales']['TVM'] += $row['amount'];
						break;
				}
			}
		}

		// Deduct TVMIR and TVM Change Fund Return

		return $sales;
	}


	public function cash_in_vault()
	{
		$ci =& get_instance();
		$ci->load->library( 'item' );
		$ci->load->library( 'sales_item' );
		$ci->load->library( 'category' );

		$Item = new Item();
		$Category = new Category();
		$Sales_Item = new Sales_item();

		$change_fund = $Item->get_by_name( 'Change Fund' );
		$sales_collection = $Item->get_by_name( 'Sales' );
		$sales_collection_category = $Category->get_by_name( 'SalesColl' );

		$teller_gross_sales = 0.00;
		$tvm_gross_sales = 0.00;
		$hopper_change_fund = 0.00;

		$tcerf = 0.00;
		$returned_change_fund_tvm = 0.00;
		$returned_change_fund_teller = 0.00;

		$total_gross_sales = $this->total_gross_sales();

		$hopper_readings = $this->hopper_readings();
		$previous_hopper_reading = 0.00;
		$current_hopper_reading = 0.00;
		foreach( $hopper_readings as $row )
		{
			$previous_hopper_reading += $row['previous'];
			$current_hopper_reading += $row['current'];
		}

		$hopper_replenishment = $this->total_hopper_replenishment();

		$refunded_tvmir = $this->total_tvmir_refund();

		$deductions = $this->teller_sales_deductions();
		$additions = $this->teller_sales_additions();

		$returned_change_fund = $this->returned_change_fund();

		$deposits = $this->deposited_to_bank();

		// Add: For deposit to bank
		$sales = $this->gross_sales_deposit();
		$tvm_net_sales = $sales['deposit']['TVM'] + $sales['sales']['TVM'] - $returned_change_fund['TVM'] - $refunded_tvmir;

		/*
		return array(
				'balance_per_book' => array(
					'beginning_balance' => $this->beginning_balance(),
					'tvm_gross_sales' => $total_gross_sales['TVM'],
					'tvm_net_sales' => $tvm_net_sales,
					'teller_gross_sales' => $total_gross_sales['teller'],
					'hopper_replenishment' => $hopper_replenishment,
					'TVMIR_refund' => $refunded_tvmir,
					'returned_change_fund' => $returned_change_fund,
					'deposits' => $deposits,
					'sales' => $sales ) );
		*/
		$available_sales = $this->available_sales_collection();
		foreach( $available_sales as $k => $v )
		{
			echo $k.'<br />';
			var_dump( $v );
			echo '<br /><br />';
		}

		return NULL;
	}
}