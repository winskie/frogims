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
	protected $shift;
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


	public function get_by_store_date_shift( $store_id, $date, $shift_id )
	{
		$ci =& get_instance();

		$ci->db->where( 'st_store_id', $store_id );
		$ci->db->where( 'st_from_date', $date );
		$ci->db->where( 'st_from_shift_id', $shift_id );
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


	public function get_shift( $force = FALSE )
	{
		if( ! isset( $this->shift ) || $force )
		{
			$ci =& get_instance();
			$ci->load->library( 'shift' );
			$Shift = new Shift();

			$this->shift = $Shift->get_by_id( $this->st_from_shift_id );
		}

		return $this->shift;
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
								ip.iprice_unit_price,
								ct.conversion_factor
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

		if( ! isset( $this->id ) )
		{
			$Store = new Store();
			$store = $Store->get_by_id( $this->st_store_id );

			// Get possible additional inventory items
			$params = array(
				'date' => $this->st_from_date,
				'shift' => $this->st_from_shift_id );

			$store_items = $store->get_shift_turnover_items( $params ); // store inventory
			foreach( $store_items as $inv_id => $store_item )
			{
				if( ! isset( $this->items[$inv_id] ) )
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
					$new_item = new Shift_turnover_item();
					$new_item->set( 'sti_turnover_id', $this->id );
					$new_item->set( 'previous_balance', $prev_item->get( 'sti_ending_balance' ) );
					$new_item->set( 'sti_beginning_balance', $prev_item->get( 'sti_ending_balance' ) );
					$new_item->set( 'sti_ending_balance', NULL );
					$this->items[$inv_id] = $new_item;
				}
			}
		}

		if( ! isset( $this->id ) )
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

		// Check all remaining sales have scheduled bank deposit

		// Check for unreplenished TVM change fund

		// Check TVM readings present

		// Check for unrecorded shortage/overages

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
	 * Get TVM number of sold tickets
	 */
	public function get_tvm_tickets_sold()
	{
		$ci =& get_instance();

		$sql = "SELECT
							a.i * 10 + b.i AS tvm_id, CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) ) AS tvm_num,
							readings.sjt_reading, readings.sjt_previous_reading,
							allocations.sjt_replenishment, allocations.sjt_reject_bin, allocations.sjt_excess,
							readings.sjt_previous_reading + allocations.sjt_replenishment - allocations.sjt_reject_bin - allocations.sjt_excess - readings.sjt_reading AS sjt_sold,
							readings.svc_reading, readings.svc_previous_reading,
							allocations.svc_replenishment, allocations.svc_reject_bin, allocations.svc_excess,
							readings.svc_previous_reading + allocations.svc_replenishment - allocations.svc_reject_bin - allocations.svc_excess - readings.svc_reading AS svc_sold
						FROM ints a JOIN ints b
						LEFT JOIN
						(
							SELECT tvmr_machine_id,
								SUM(IF(tvmr_type = 'magazine_sjt', tvmr_reading, NULL)) AS sjt_reading,
								SUM(IF(tvmr_type = 'magazine_svc', tvmr_reading, NULL)) AS svc_reading,
								SUM(IF(tvmr_type = 'magazine_sjt', tvmr_previous_reading, NULL)) AS sjt_previous_reading,
								SUM(IF(tvmr_type = 'magazine_svc', tvmr_previous_reading, NULL)) AS svc_previous_reading
							FROM tvm_readings
							WHERE
								tvmr_type IN ('magazine_sjt', 'magazine_svc')
								AND tvmr_date = ?
								AND tvmr_store_id = ?
								AND tvmr_shift_id = ?
							GROUP BY tvmr_machine_id
						) AS readings
							ON readings.tvmr_machine_id = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )
						LEFT JOIN
						(
							SELECT a.assignee,
								SUM( IF( ai.allocation_item_type = 1 AND i.item_group = 'SJT', IF( ct.id IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor), 0 ) ) AS sjt_replenishment,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'SJT' AND i.item_type = 0, ai.allocated_quantity, 0 ) ) AS sjt_reject_bin,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'SJT' AND i.item_type = 1, ai.allocated_quantity, 0 ) ) AS sjt_excess,
								SUM( IF( ai.allocation_item_type = 1 AND i.item_group = 'SVC', IF( ct.id IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor), 0 ) ) AS svc_replenishment,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'SVC' AND i.item_type = 0, ai.allocated_quantity, 0 ) ) AS svc_reject_bin,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'SVC' AND i.item_type = 1, ai.allocated_quantity, 0 ) ) AS svc_excess
							FROM allocations a
							LEFT JOIN allocation_items ai ON ai.allocation_id = a.id
							LEFT JOIN items i ON i.id = ai.allocated_item_id
							LEFT JOIN item_prices ip ON ip.iprice_item_id = i.id
							LEFT JOIN conversion_table ct ON ct.target_item_id = ai.allocated_item_id
							WHERE
								business_date = ?
								AND a.store_id = ?
								AND a.assignee_type = ?
								AND ai.cashier_shift_id = ?
								AND i.item_class = 'ticket'
								AND i.item_group IN ('SJT', 'SVC')
								AND ai.allocation_item_status NOT IN (".implode( ',', array( ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED, REMITTANCE_ITEM_VOIDED ) ).")
							GROUP BY a.assignee
						) AS allocations
							ON allocations.assignee = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )
						WHERE (a.i * 10 + b.i) > 0 AND (a.i * 10 + b.i) < 17
						AND ( readings.tvmr_machine_id IS NOT NULL OR allocations.assignee IS NOT NULL )
						ORDER BY tvm_id";

		$sql_params = array(
				// readings
				$this->st_from_date,
				$this->st_store_id,
				$this->st_from_shift_id,
				// allocations
				$this->st_from_date,
				$this->st_store_id,
				ALLOCATION_ASSIGNEE_MACHINE,
				$this->st_from_shift_id,
			);

		$query = $ci->db->query( $sql, $sql_params );

		return $query->result_array();
	}

	/**
	 * Get TVM cash collection
	 */
	public function get_tvm_cash_collection()
	{
		$ci =& get_instance();

		$ci->load->library( 'category' );
		$Category = new Category();
		$sales_collection_cat = $Category->get_by_name( 'SalesColl' );

		$sql = "SELECT
							a.i * 10 + b.i AS tvm_id, CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) ) AS tvm_num,
							collections.coin_box, collections.note_box,
							COALESCE( collections.coin_box, 0 ) + COALESCE( collections.note_box, 0 ) AS gross_sales,
							readings.coin_box_reading, readings.note_box_reading,
							( COALESCE( readings.coin_box_reading, 0 ) + COALESCE( readings.note_box_reading, 0 ) ) - ( COALESCE( collections.coin_box, 0 ) + COALESCE( collections.note_box, 0 ) ) AS short_over
						FROM ints a JOIN ints b
						LEFT JOIN
						(
							SELECT a.assignee,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'coin' AND ai.allocation_category_id = ".$sales_collection_cat->get( 'id' ).", ai.allocated_quantity * ip.iprice_unit_price, NULL ) ) AS coin_box,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'bill' AND ai.allocation_category_id = ".$sales_collection_cat->get( 'id' ).", ai.allocated_quantity * ip.iprice_unit_price, NULL ) ) AS note_box
							FROM allocations a
							LEFT JOIN allocation_items ai ON ai.allocation_id = a.id
							LEFT JOIN items i ON i.id = ai.allocated_item_id
							LEFT JOIN item_prices ip ON ip.iprice_item_id = i.id
							LEFT JOIN conversion_table ct ON ct.target_item_id = ai.allocated_item_id
							WHERE
								business_date = ?
								AND a.store_id = ?
								AND a.assignee_type = ?
								AND ai.cashier_shift_id = ?
								AND i.item_class = 'cash'
								AND ai.allocation_item_status != ".REMITTANCE_ITEM_VOIDED."
							GROUP BY a.assignee
						) AS collections
							ON collections.assignee = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )

						LEFT JOIN
						(
							SELECT tvmr_machine_id,
								SUM(IF(tvmr_type = 'coin_box', tvmr_reading, 0)) AS coin_box_reading,
								SUM(IF(tvmr_type = 'note_box', tvmr_reading, 0)) AS note_box_reading
							FROM tvm_readings
							WHERE
								tvmr_type IN ('coin_box', 'note_box')
								AND tvmr_date = ?
								AND tvmr_store_id = ?
								AND tvmr_shift_id = ?
							GROUP BY tvmr_machine_id
						) AS readings
							ON readings.tvmr_machine_id = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )

						WHERE (a.i * 10 + b.i) > 0 AND (a.i * 10 + b.i) < 17
						AND collections.assignee IS NOT NULL
						ORDER BY tvm_id";

		$sql_params = array(
				// collections
				$this->st_from_date,
				$this->st_store_id,
				ALLOCATION_ASSIGNEE_MACHINE,
				$this->st_from_shift_id,
				// readings
				$this->st_from_date,
				$this->st_store_id,
				$this->st_from_shift_id,
			);

		$query = $ci->db->query( $sql, $sql_params );

		return $query->result_array();
	}

	/**
	 * Get TVM hopper change fund
	 */
	public function get_tvm_hopper_change_fund()
	{
		$ci =& get_instance();

		$ci->load->library( 'item' );
		$Item = new Item();

		//TODO: settings - tvm_hopper_php1_items, tvm_hopper_php5_items
		$php1_coin = $Item->get_by_name( 'Php1 Coin' );
		$php1_100 = $Item->get_by_name( 'Php1@100' );
		$php1_200 = $Item->get_by_name( 'Php1@200' );
		$php1_500 = $Item->get_by_name( 'Php1@500' );
		$php1_2000 = $Item->get_by_name( 'Php1@2000' );

		$php5_coin = $Item->get_by_name( 'Php5 Coin' );
		$php5_20 = $Item->get_by_name( 'Php5@20' );
		$php5_100 = $Item->get_by_name( 'Php5@100' );
		$php5_200 = $Item->get_by_name( 'Php5@200' );
		$php5_300 = $Item->get_by_name( 'Php5@300' );
		$php5_500 = $Item->get_by_name( 'Php5@500' );

		$php1_hopper_items = array();
		$php1_hopper_items[] = $php1_coin->get( 'id' );
		$php1_hopper_items[] = $php1_100->get( 'id' );
		$php1_hopper_items[] = $php1_200->get( 'id' );
		$php1_hopper_items[] = $php1_500->get( 'id' );
		$php1_hopper_items[] = $php1_2000->get( 'id' );

		$php5_hopper_items = array();
		$php5_hopper_items[] = $php5_coin->get( 'id' );
		$php5_hopper_items[] = $php5_100->get( 'id' );
		$php5_hopper_items[] = $php5_200->get( 'id' );
		$php5_hopper_items[] = $php5_300->get( 'id' );
		$php5_hopper_items[] = $php5_500->get( 'id' );

		$ci->load->library( 'category' );
		$Category = new Category();
		$hopper_category = $Category->get_by_name( 'HopAlloc' );

		$sql = "SELECT
							a.i * 10 + b.i AS tvm_id, CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) ) AS tvm_num,
							readings.php1_reading, readings.php1_previous_reading,
							readings.php5_reading, readings.php5_previous_reading,
							allocations.php1_replenishment,
							allocations.php5_replenishment,
							cfund_replenish.replenished_change_fund
						FROM ints a JOIN ints b

						LEFT JOIN
						(
							SELECT tvmr_machine_id,
								SUM(IF(tvmr_type = 'hopper_php1', tvmr_reading, 0)) AS php1_reading,
								SUM(IF(tvmr_type = 'hopper_php5', tvmr_reading, 0)) AS php5_reading,
								SUM(IF(tvmr_type = 'hopper_php1', tvmr_previous_reading, 0 )) AS php1_previous_reading,
								SUM(IF(tvmr_type = 'hopper_php5', tvmr_previous_reading, 0 )) AS php5_previous_reading
							FROM tvm_readings
							WHERE
								tvmr_type IN ('hopper_php1', 'hopper_php5')
								AND tvmr_date = ?
								AND tvmr_store_id = ?
								AND tvmr_shift_id = ?
							GROUP BY tvmr_machine_id
						) AS readings
							ON readings.tvmr_machine_id = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )

						LEFT JOIN
						(
							SELECT a.assignee,
								SUM( IF( ai.allocated_item_id IN (".implode( ',', $php1_hopper_items ).") AND ai.allocation_item_type = 1, ip.iprice_unit_price * ai.allocated_quantity, 0 ) ) AS php1_replenishment,
								SUM( IF( ai.allocated_item_id IN (".implode( ',', $php5_hopper_items ).") AND ai.allocation_item_type = 1, ip.iprice_unit_price * ai.allocated_quantity, 0 ) ) AS php5_replenishment
							FROM allocations a
							LEFT JOIN allocation_items ai
								ON ai.allocation_id = a.id
							LEFT JOIN items i
								ON i.id = ai.allocated_item_id
							LEFT JOIN item_prices ip
								ON ip.iprice_item_id = i.id
							WHERE
								business_date = ?
								AND a.store_id = ?
								AND a.assignee_type = ?
								AND ai.cashier_shift_id = ?
								AND i.item_class = 'cash'
								AND ai.allocation_category_id = ?
								AND ai.allocation_item_status NOT IN (".implode( ',', array( ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED ) ).")
							GROUP BY a.assignee
						) AS allocations
							ON allocations.assignee = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )

						LEFT JOIN
						(
							SELECT t.transfer_tvm_id AS tvm_num,
								SUM( ti.quantity * ip.iprice_unit_price ) AS replenished_change_fund
							FROM transfers t
							LEFT JOIN transfer_items ti
								ON ti.transfer_id = t.id
							LEFT JOIN items i
								ON i.id = ti.item_id
							LEFT JOIN item_prices ip
								ON ip.iprice_item_id = i.id
							WHERE
								t.transfer_datetime BETWEEN ? AND ?
								AND t.origin_id = ?
								AND t.sender_shift = ?
								AND t.transfer_category = ".TRANSFER_CATEGORY_REPLENISH_TVM_CFUND."
								AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
							GROUP BY t.transfer_tvm_id
						) AS cfund_replenish
							ON cfund_replenish.tvm_num = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )

					WHERE (a.i * 10 + b.i) > 0 AND (a.i * 10 + b.i) < 17
					AND ( readings.tvmr_machine_id IS NOT NULL OR allocations.assignee IS NOT NULL )
					ORDER BY tvm_id";

		$sql_params = array(
				// readings
				$this->st_from_date,
				$this->st_store_id,
				$this->st_from_shift_id,
				// allocations
				$this->st_from_date,
				$this->st_store_id,
				ALLOCATION_ASSIGNEE_MACHINE,
				$this->st_from_shift_id,
				$hopper_category->get( 'id' ),
				// cfund_replenish
				$this->st_from_date.' 00:00:00', $this->st_from_date.' 23:59:59',
				$this->st_store_id,
				$this->st_from_shift_id,
			);


		$query = $ci->db->query( $sql, $sql_params );

		return $query->result_array();
	}

	/**
	 * Get refunded TVMIR
	 */
	public function get_refunded_tvmir()
	{
		$ci =& get_instance();

		$sql = "SELECT
							a.i * 10 + b.i AS tvm_id, CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) ) AS tvm_num,
							tvmir.refunded_tvmir
						FROM ints a JOIN ints b

						LEFT JOIN
							(
								SELECT t.transfer_tvm_id AS tvm_num,
									SUM( ti.quantity * ip.iprice_unit_price ) AS refunded_tvmir
								FROM transfers t
								LEFT JOIN transfer_items ti
									ON ti.transfer_id = t.id
								LEFT JOIN items i
									ON i.id = ti.item_id
								LEFT JOIN item_prices ip
									ON ip.iprice_item_id = i.id
								WHERE
									t.transfer_datetime BETWEEN ? AND ?
									AND t.origin_id = ?
									AND t.sender_shift = ?
									AND t.transfer_category = ".TRANSFER_CATEGORY_ADD_TVMIR."
									AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
								GROUP BY t.transfer_tvm_id
							) AS tvmir
								ON tvmir.tvm_num = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )

						WHERE (a.i * 10 + b.i) > 0 AND (a.i * 10 + b.i) < 17
							AND tvmir.tvm_num IS NOT NULL
						ORDER BY tvm_id";

		$sql_params = array(
				// TVMIR
				$this->st_from_date.' 00:00:00', $this->st_from_date.' 23:59:59',
				$this->st_store_id,
				$this->st_from_shift_id,
			);

		$query = $ci->db->query( $sql, $sql_params );

		return $query->result_array();
	}

	/**
	 * Get TVM shortage/overage
	 */
	public function get_tvm_shortage_overage()
	{
		$ci =& get_instance();

		//TODO: settings - shortage_sales_items, overage_sales_items
		$ci->load->library( 'category' );
		$Category = new Category();

		$sql = "SELECT
							a.i * 10 + b.i AS tvm_id, CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) ) AS tvm_num,
							short_over.shortage, short_over.overage
						FROM ints a JOIN ints b
						LEFT JOIN
						(
							SELECT a.assignee,
								SUM( IF( si.slitem_name = 'Shortage', asi.alsale_amount, 0 ) ) AS shortage,
								SUM( IF( si.slitem_name = 'Overage', asi.alsale_amount, 0 ) ) AS overage
							FROM allocations a
							LEFT JOIN allocation_sales_items asi ON asi.alsale_allocation_id = a.id
							LEFT JOIN sales_items si ON si.id = asi.alsale_sales_item_id
							WHERE
								business_date = ?
								AND a.store_id = ?
								AND a.assignee_type = ?
								AND asi.alsale_shift_id = ?
								AND si.slitem_group = 'Short Over'
								AND asi.alsale_sales_item_status != ".SALES_ITEM_VOIDED."
							GROUP BY a.assignee
						) AS short_over
							ON short_over.assignee = CONCAT( 'T', LPAD( a.i * 10 + b.i, 2, '0' ) )

						WHERE (a.i * 10 + b.i) > 0 AND (a.i * 10 + b.i) < 17
						AND short_over.assignee IS NOT NULL
						ORDER BY tvm_id";

		$sql_params = array(
				// collections
				$this->st_from_date,
				$this->st_store_id,
				ALLOCATION_ASSIGNEE_MACHINE,
				$this->st_from_shift_id,
			);

		$query = $ci->db->query( $sql, $sql_params );

		return $query->result_array();
	}


	/**
	 * Get teller number of sold tickets
	 */
	public function get_teller_tickets_sold()
	{
		$ci =& get_instance();

		$ci->load->library( 'category' );
		$Category = new Category();
		$ticket_sale_cat = $Category->get_by_name( 'TktSales' );
		$csc_issue_cat = $Category->get_by_name( 'CSCIssue' );
		$paid_exit_cat = $Category->get_by_name( 'SalePdExt' );
		$free_exit_cat = $Category->get_by_name( 'SaleFrExt' );
		$unconfirmed_cat = $Category->get_by_name( 'SaleUncfrm' );

		$sql = "SELECT a.id, a.assignee,
							SUM( IF( ai.allocation_item_type = 3 AND ai.allocation_category_id = ".$ticket_sale_cat->get( 'id' )." AND i.item_group = 'SJT', ai.allocated_quantity, 0 ) ) AS sjt_sold,
							SUM( IF( ai.allocation_item_type = 3 AND ai.allocation_category_id = ".$ticket_sale_cat->get( 'id' )." AND i.item_group = 'SVC', ai.allocated_quantity, 0 ) ) AS svc_sold,
							SUM( IF( ai.allocation_item_type = 3 AND ai.allocation_category_id = ".$csc_issue_cat->get( 'id' )." AND i.item_group = 'Concessionary', ai.allocated_quantity, 0 ) ) AS csc_issued,
							SUM( IF( ai.allocation_item_type = 3 AND ai.allocation_category_id = ".$free_exit_cat->get( 'id' ).", ai.allocated_quantity, 0 ) ) AS free_exit,
							SUM( IF( ai.allocation_item_type = 3 AND ai.allocation_category_id = ".$paid_exit_cat->get( 'id' ).", ai.allocated_quantity, 0 ) ) AS paid_exit,
							SUM( IF( ai.allocation_item_type = 3 AND ai.allocation_category_id = ".$unconfirmed_cat->get( 'id' ).", ai.allocated_quantity, 0 ) ) AS unconfirmed
						FROM allocations a
						LEFT JOIN allocation_items ai ON ai.allocation_id = a.id
						LEFT JOIN items i ON i.id = ai.allocated_item_id
						WHERE
							business_date = ?
							AND a.store_id = ?
							AND a.assignee_type = ?
							AND ai.cashier_shift_id = ?
							AND i.item_class = 'ticket'
							AND i.item_group IN ('SJT', 'SVC', 'Concessionary')
							AND ai.allocation_item_status NOT IN (".implode( ',', array( ALLOCATION_ITEM_CANCELLED, ALLOCATION_ITEM_VOIDED, REMITTANCE_ITEM_VOIDED ) ).")
						GROUP BY a.id, a.assignee
						ORDER BY a.assignee";

		$sql_params = array(
				// allocations
				$this->st_from_date,
				$this->st_store_id,
				ALLOCATION_ASSIGNEE_TELLER,
				$this->st_from_shift_id,
			);

		$query = $ci->db->query( $sql, $sql_params );

		return $query->result_array();
	}

	/**
	 * Get teller cash collection
	 */
	public function get_teller_cash_collection()
	{
		$ci =& get_instance();

		$ci->load->library( 'category' );
		$Category = new Category();
		$sales_collection_cat = $Category->get_by_name( 'SalesColl' );
		$change_fund_return_cat = $Category->get_by_name( 'CFundRet' );

		// others.penalties and others.short_over are for reference only and should not be included in balance computations
		$sql = "SELECT
							a.id, a.assignee,
							collection.coins, collection.bills, collection.gross_sales, collection.change_fund_returned,
							cash_reports.cash_received,
							others.deductions,
							COALESCE( cash_reports.cash_received, 0 ) - COALESCE( collection.gross_sales, 0 ) + COALESCE( others.deductions, 0 ) AS short_over
						FROM allocations a

						LEFT JOIN
						(
							SELECT ai.allocation_id, ai.cashier_shift_id,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'coin' AND ai.allocation_category_id = ".$sales_collection_cat->get( 'id' ).", ai.allocated_quantity * ip.iprice_unit_price, NULL ) ) AS coins,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'bill' AND ai.allocation_category_id = ".$sales_collection_cat->get( 'id' ).", ai.allocated_quantity * ip.iprice_unit_price, NULL ) ) AS bills,
								SUM( IF( ai.allocation_item_type = 2 AND ai.allocation_category_id = ".$sales_collection_cat->get( 'id' ).", ai.allocated_quantity * ip.iprice_unit_price, NULL ) ) AS gross_sales,
								SUM( IF( ai.allocation_item_type = 2 AND ai.allocation_category_id = ".$change_fund_return_cat->get( 'id' ).", ai.allocated_quantity * ip.iprice_unit_price, NULL ) ) AS change_fund_returned
							FROM allocation_items ai
							LEFT JOIN items i ON i.id = ai.allocated_item_id
							LEFT JOIN item_prices ip ON ip.iprice_item_id = i.id
							LEFT JOIN conversion_table ct ON ct.target_item_id = ai.allocated_item_id
							WHERE
								i.item_class = 'cash'
								AND ai.allocation_item_status != ".REMITTANCE_ITEM_VOIDED."
							GROUP BY ai.allocation_id, ai.cashier_shift_id
						) AS collection
							ON collection.allocation_id = a.id AND collection.cashier_shift_id = ?

						LEFT JOIN
						(
							SELECT
								sdcr.sdcr_allocation_id, sdcr.sdcr_shift_id,
								SUM( sdcri.sdcri_amount ) AS cash_received
							FROM shift_detail_cash_reports sdcr
							LEFT JOIN shift_detail_cash_report_items sdcri
								ON sdcri.sdcri_sdcr_id = sdcr.id
							GROUP BY sdcr.sdcr_allocation_id, sdcr.sdcr_shift_id
						) AS cash_reports
							ON cash_reports.sdcr_allocation_id = a.id AND cash_reports.sdcr_shift_id = ?

						LEFT JOIN
						(
							SELECT
								asi.alsale_allocation_id, asi.alsale_shift_id,
								SUM( IF( si.slitem_group = 'Penalties', asi.alsale_amount, 0 ) ) AS penalties,
								SUM( IF( si.slitem_group = 'Deductions', asi.alsale_amount, 0 ) ) AS deductions,
								SUM( IF( si.slitem_group = 'Short Over', asi.alsale_amount, 0 ) ) AS short_over
							FROM allocation_sales_items asi
							LEFT JOIN sales_items si ON si.id = asi.alsale_sales_item_id
							WHERE
								asi.alsale_sales_item_status != ".SALES_ITEM_VOIDED."
							GROUP BY asi.alsale_allocation_id, asi.alsale_shift_id
						) AS others
							ON others.alsale_allocation_id = a.id AND others.alsale_shift_id = ?

						WHERE
							a.business_date = ?
							AND a.store_id = ?
							AND a.assignee_type = ?

						ORDER BY a.assignee";

		$sql_params = array(
				// collection
				$this->st_from_shift_id,

				// cash_reports
				$this->st_from_shift_id,

				// others
				$this->st_from_shift_id,

				// allocation
				$this->st_from_date,
				$this->st_store_id,
				ALLOCATION_ASSIGNEE_TELLER,
			);

		$query = $ci->db->query( $sql, $sql_params );
		//var_dump( $ci->db->last_query() );
		return $query->result_array();
	}

	/**
	 * Get teller additions and deductions
	 */
	public function get_teller_additions_deductions()
	{
		$ci =& get_instance();

		$ci->load->library( 'category' );
		$Category = new Category();
		$sales_collection_cat = $Category->get_by_name( 'SalesColl' );
		$change_fund_return_cat = $Category->get_by_name( 'CFundRet' );

		$sql = "SELECT a.id, a.assignee,
							SUM( IF( si.slitem_group = 'Penalties', asi.alsale_amount, 0 ) ) AS penalties,
							SUM( IF( si.slitem_group = 'Deductions', asi.alsale_amount, 0 ) ) AS deductions,
							SUM( IF( si.slitem_group = 'Short Over', asi.alsale_amount, 0 ) ) AS short_over
						FROM allocations a
						LEFT JOIN allocation_sales_items asi ON asi.alsale_allocation_id = a.id
						LEFT JOIN sales_items si ON si.id = asi.alsale_sales_item_id
						WHERE
							business_date = ?
							AND a.store_id = ?
							AND a.assignee_type = ?
							AND asi.alsale_shift_id = ?
							AND asi.alsale_sales_item_status != ".SALES_ITEM_VOIDED."
						GROUP BY a.id, a.assignee
						ORDER BY a.assignee";

		$sql_params = array(
				// allocations
				$this->st_from_date,
				$this->st_store_id,
				ALLOCATION_ASSIGNEE_TELLER,
				$this->st_from_shift_id,
			);

		$query = $ci->db->query( $sql, $sql_params );

		return $query->result_array();
	}





	/**
	 * Cash Beginning Balance
	 */
	public function beginning_balance( $type = 'cash_balance' )
	{
		$ci =& get_instance();
		$items = $this->get_items();
		$beginning_balance = 0.00;

		switch( $type )
		{
			case 'cash_balance':
				// Change Fund + Sales
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'cash' && in_array( $item->get( 'parent_item_name' ), array( 'Change Fund', 'Sales' ) ) )
					{
						$beginning_balance += $item->get( 'sti_beginning_balance' ) * $item->get( 'iprice_unit_price' );
					}
				}
				break;

			case 'tvmir':
				// TVMIR
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'cash' && $item->get( 'parent_item_name' ) == 'TVMIR' )
					{
						$beginning_balance += $item->get( 'sti_beginning_balance' ) * $item->get( 'iprice_unit_price' );
					}
				}
				break;

			case 'csc':
				// Concessionary Card Fee
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'cash' && $item->get( 'parent_item_name' ) == 'CSC Card Fee' )
					{
						$beginning_balance += $item->get( 'sti_beginning_balance' ) * $item->get( 'iprice_unit_price' );
					}
				}
				break;

			case 'ticket_balance':
				$beginning_balance = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'ticket' )
					{
						switch( $item->get( 'item_group' ) )
						{
							case 'SJT':
								$beginning_balance['SJT'] += $item->get( 'sti_beginning_balance' );
								break;

							case 'SVC':
								$beginning_balance['SVC'] += $item->get( 'sti_beginning_balance' );
								break;

							case 'Concessionary':
								$beginning_balance['Concessionary'] += $item->get( 'sti_beginning_balance' );
								break;
						}
					}
				}
				break;
		}

		return $beginning_balance;
	}

	/**
	 * Cash Ending Balance
	 */
	public function ending_balance( $type = 'cash_balance' )
	{
		$ci =& get_instance();
		$items = $this->get_items();
		$data = array(
			'actual' => 0.00,
			'system' => 0.00
		);

		switch( $type )
		{
			case 'cash_balance':
				// Change Fund + Sales
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'cash' && in_array( $item->get( 'parent_item_name' ), array( 'Change Fund', 'Sales' ) ) )
					{
						$data['system'] += ( $item->get( 'sti_beginning_balance' ) + $item->get( 'movement') ) * $item->get( 'iprice_unit_price' );
						$data['actual'] += $item->get( 'sti_ending_balance' ) * $item->get( 'iprice_unit_price' );
					}
				}
				break;

			case 'tvmir':
				// TVMIR
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'cash' && $item->get( 'parent_item_name' ) == 'TVMIR' )
					{
						$data['system'] += ( $item->get( 'sti_beginning_balance' ) + $item->get( 'movement') ) * $item->get( 'iprice_unit_price' );
						$data['actual'] += $item->get( 'sti_ending_balance' ) * $item->get( 'iprice_unit_price' );
					}
				}
				break;

			case 'csc':
				// Concessionary Card Fee
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'cash' && $item->get( 'parent_item_name' ) == 'CSC Card Fee' )
					{
						$data['system'] += ( $item->get( 'sti_beginning_balance' ) + $item->get( 'movement') ) * $item->get( 'iprice_unit_price' );
						$data['actual'] += $item->get( 'sti_ending_balance' ) * $item->get( 'iprice_unit_price' );
					}
				}
				break;

			case 'ticket_balance':
				$data = array(
					'SJT' => array( 'actual' => 0, 'system' => 0 ),
					'SVC' => array( 'actual' => 0, 'system' => 0 ),
					'Concessionary' => array( 'actual' => 0, 'system' => 0 ) );
				foreach( $items as $item )
				{
					if( $item->get( 'item_class' ) == 'ticket' )
					{
						//var_dump( $item->get( 'item_name' ), $item->get( 'sti_beginning_balance' ), $item->get( 'movement' ), $item->get( 'base_movement' ) );
						//echo '<br/>';
						switch( $item->get( 'item_group' ) )
						{
							case 'SJT':
								$data['SJT']['system'] += $item->get( 'sti_beginning_balance' ) + $item->get( 'base_movement' );
								$data['SJT']['actual'] += $item->get( 'sti_ending_balance' );
								break;

							case 'SVC':
								$data['SVC']['system'] += $item->get( 'sti_beginning_balance' ) + $item->get( 'base_movement' );
								$data['SVC']['actual'] += $item->get( 'sti_ending_balance' );
								break;

							case 'Concessionary':
								$data['Concessionary']['system'] += $item->get( 'sti_beginning_balance' ) + $item->get( 'base_movement' );
								$data['Concessionary']['actual'] += $item->get( 'sti_ending_balance' );
								break;
						}
					}
				}
				break;
		}

		return $data;
	}

	/**
	 * For deposit to bank
	 */
	public function for_deposit_to_bank()
	{
		$ci =& get_instance();

		$shift = $this->get_shift();
		$data = array();

		// Get remaining sales collection amount
		$items = $this->get_items();
		$sales_collection = 0.00;

		foreach( $items as $item )
		{
			if( $item->get( 'item_class' ) == 'cash' && $item->get( 'parent_item_name' ) == 'Sales' )
			{
				$sales_collection += ( $item->get( 'sti_beginning_balance') + $item->get( 'movement' ) ) * $item->get( 'iprice_unit_price' );
			}
		}

		$data[$shift->get('shift_num')] = array( 'sales_collection' => $sales_collection, 'for_deposit' => 0.00 );

		// Get scheduled deposit to bank
		$sql = "SELECT
							s.shift_num,
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN shifts AS s
							ON s.id = t.transfer_init_shift_id
						WHERE
							t.origin_id = ".$this->st_store_id."
							AND t.transfer_init_shift_id = ".$this->st_from_shift_id."
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category = ".TRANSFER_CATEGORY_BANK_DEPOSIT."
							AND i.item_class = 'cash'
							-- AND t.transfer_status = ".TRANSFER_PENDING."
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY shift_num";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			if( isset( $data[$row['shift_num']] ) )
			{
				$data[$row['shift_num']]['for_deposit'] = $row['amount'];
			}
			else
			{
				$data[$row['shift_num']] = array( 'sales_collection' => 0.00, 'for_deposit' => $row['amount'] );
			}
		}
		//var_dump( $data );
		//echo '<br/>';
		return $data;
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

		$data = array(
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

		$data['teller'] += $r['amount'];

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

		$data['TVM'] += $r['amount'];

		return $data;
	}

	/**
	 * Other Cash Additions
	 */
	public function other_cash_additions()
	{
		$ci =& get_instance();
		$data = array();

		// Returned Bills to Coins exchange
		$sql = "SELECT
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN shifts AS s
							ON s.id = t.transfer_init_shift_id
						WHERE
							t.destination_id = ".$this->st_store_id."
							AND t.recipient_shift = ".$this->st_from_shift_id."
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category = ".TRANSFER_CATEGORY_BILLS_TO_COINS."
							AND i.item_class = 'cash'
							AND t.transfer_status = ".TRANSFER_RECEIVED."
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")";
		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		if( ! empty($r['amount'] ) )
		{
			$data['Bills to Coins Exchange'] = $r['amount'];
		}

		// Adjustments
		$sql = "SELECT
							SUM((a.adjusted_quantity - a.previous_quantity) * ip.iprice_unit_price) AS amount
						FROM adjustments AS a
						LEFT JOIN store_inventory AS si
							ON si.id = a.store_inventory_id
						LEFT JOIN items AS i
							ON i.id = si.item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN items AS pi
							ON pi.id = si.parent_item_id
						WHERE
							a.adjustment_timestamp BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND a.adjustment_shift = ".$this->st_from_shift_id."
							AND a.adjustment_status = ".ADJUSTMENT_APPROVED."
							AND si.store_id = ".$this->st_store_id."
							AND i.item_class = 'cash'
							AND pi.item_name = 'Change Fund'";
		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		if( $r['amount'] > 0 )
		{
			$data['Adjustment'] = $r['amount'];
		}

		return $data;
	}

	/**
	 * Deposit to Bank
	 */
	public function deposit_to_bank()
	{
		$ci =& get_instance();

		$shift = $this->get_shift();
		$data = array();

		// Get scheduled deposit to bank
		$sql = "SELECT
							s.shift_num,
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN shifts AS s
							ON s.id = t.transfer_init_shift_id
						WHERE
							t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category = ".TRANSFER_CATEGORY_BANK_DEPOSIT."
							AND i.item_class = 'cash'
							AND t.transfer_status = ".TRANSFER_APPROVED."
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY t.transfer_init_shift_id, s.shift_num";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			$data[$row['shift_num']] = $row['amount'];
		}

		return $data;
	}

	/**
	 * Change Fund Allocations
	 */
	public function change_fund_allocations()
	{
		$ci =& get_instance();

		$valid_items = array( 'Php1 Coin', 'Php5 Coin', 'Bag Php1@100', 'Bag Php5@100', 'Php1@100', 'Php1@200', 'Php1@500', 'Php1@2000',
				'Php5@500', 'Php5@1000', 'Php5@1500', 'Php5@2500' );
		$data = array( 'teller' => 0.00, 'TVM' => 0.00 );

		$sql = "SELECT
							a.assignee_type,
							SUM( IF( ai.allocation_item_type = ".ALLOCATION_ITEM_TYPE_ALLOCATION.", ip.iprice_unit_price * ai.allocated_quantity, 0 ) ) AS amount
						FROM allocations a
						LEFT JOIN allocation_items ai
							ON ai.allocation_id = a.id
						LEFT JOIN items i
							ON i.id = ai.allocated_item_id
						LEFT JOIN item_prices ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN categories AS c
							ON c.id = ai.allocation_category_id
						WHERE
							a.store_id = ".$this->st_store_id."
							AND ai.allocation_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND ai.cashier_shift_id = ".$this->st_from_shift_id."
							AND i.item_class = 'cash'
							AND c.cat_name IN ( 'HopAlloc', 'InitCFund', 'AddCFund' )
							AND NOT ai.allocation_item_status IN (".implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) ).")
						GROUP BY assignee_type";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['assignee_type'] )
			{
				case 1:
					$assignee_type = 'teller';
					break;

				case 2:
					$assignee_type = 'TVM';
					break;

				default:
					die( 'Invalid assignee type detected. Contact your system administrator.' );
			}

			$data[$assignee_type] = $row['amount'];
		}

		return $data;
	}

	/**
	 * Other Cash Deductions
	 */
	public function other_cash_deductions()
	{
		$ci =& get_instance();
		$data = array();

		// Bills to Coins exchange
		$sql = "SELECT
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN shifts AS s
							ON s.id = t.transfer_init_shift_id
						WHERE
							t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category = ".TRANSFER_CATEGORY_BILLS_TO_COINS."
							AND i.item_class = 'cash'
							AND t.transfer_status = ".TRANSFER_APPROVED."
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")";
		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		if( ! empty($r['amount'] ) )
		{
			$data['Bills to Coins Exchange'] = $r['amount'];
		}

		// Adjustments
		$sql = "SELECT
							SUM((a.adjusted_quantity - a.previous_quantity) * ip.iprice_unit_price) AS amount
						FROM adjustments AS a
						LEFT JOIN store_inventory AS si
							ON si.id = a.store_inventory_id
						LEFT JOIN items AS i
							ON i.id = si.item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN items AS pi
							ON pi.id = si.parent_item_id
						WHERE
							a.adjustment_timestamp BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND a.adjustment_shift = ".$this->st_from_shift_id."
							AND a.adjustment_status = ".ADJUSTMENT_APPROVED."
							AND si.store_id = ".$this->st_store_id."
							AND i.item_class = 'cash'
							AND pi.item_name = 'Change Fund'";
		$query = $ci->db->query( $sql );
		$r = $query->row_array();

		if( $r['amount'] < 0 )
		{
			$data['Adjustment'] = abs( $r['amount'] );
		}

		return $data;
	}

	/**
	 * Get TVMIR transactions
	 */
	public function tvmir_transactions()
	{
		$ci =& get_instance();
		$data = array(
			'additions' => array(),
			'issuances' => array(),
			'returns' => 0.00
		);

		// Get TVMIR transactions
		$sql = "SELECT
							t.id,
							t.transfer_category,
							t.transfer_reference_num,
							DATE( t.transfer_datetime ) AS business_date,
							t.transfer_tvm_id,
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN shifts AS s
							ON s.id = t.transfer_init_shift_id
						WHERE
							t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category IN (".implode( ',', array( TRANSFER_CATEGORY_ADD_TVMIR, TRANSFER_CATEGORY_ISSUE_TVMIR ) ).")
							AND i.item_class = 'cash'
							AND t.transfer_status = ".TRANSFER_APPROVED."
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY t.id, t.transfer_category, t.transfer_reference_num, DATE( t.transfer_datetime ), t.transfer_tvm_id";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['transfer_category'] )
			{
				case TRANSFER_CATEGORY_ADD_TVMIR:
					$data['additions'][] = $row;
					break;

				case TRANSFER_CATEGORY_ISSUE_TVMIR:
					$data['issuances'][] = $row;
					break;
			}
		}

		return $data;
	}

	/**
	 * Get Concessionary Card Fee transactions
	 */
	public function csc_transactions()
	{
		$ci =& get_instance();
		$data = array(
			'additions' => array(),
			'issuances' => array(),
			'new' => 0.00,
			'issuance' => 0.00,
		);

		// Get TVMIR transactions
		$sql = "SELECT
							t.id,
							t.transfer_category,
							t.transfer_reference_num,
							DATE( t.transfer_datetime ) AS business_date,
							t.transfer_tvm_id,
							SUM( ti.quantity * ip.iprice_unit_price ) AS amount,
							CASE WHEN t.origin_id IS NULL AND t.destination_id = ".$this->st_store_id." THEN 'addition'
								WHEN t.origin_id = ".$this->st_store_id." AND t.destination_id IS NULL THEN 'issuance'
								ELSE 'unknown' END AS transaction_type
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN item_prices AS ip
							ON ip.iprice_item_id = i.id
						LEFT JOIN shifts AS s
							ON s.id = t.transfer_init_shift_id
						WHERE
							( t.origin_id = ".$this->st_store_id." OR t.destination_id = ".$this->st_store_id." )
							AND ( t.sender_shift = ".$this->st_from_shift_id." OR t.recipient_shift = ".$this->st_from_shift_id." )
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category = ".TRANSFER_CATEGORY_CSC_APPLICATION."
							AND i.item_class = 'cash'
							AND t.transfer_status IN (".implode( ',', array( TRANSFER_APPROVED, TRANSFER_RECEIVED ) ).")
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY t.id, t.transfer_category, t.transfer_reference_num, DATE( t.transfer_datetime ), t.transfer_tvm_id, t.origin_id, t.destination_id";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['transaction_type'] )
			{
				case 'addition':
					$data['additions'][] = $row;
					$data['new'] += $row['amount'];
					break;

				case 'issuance':
					$data['issuances'][] = $row;
					$data['issuance'] = $row['amount'];
					break;
			}
		}

		return $data;
	}

	/**
	 * Get ticket deliveries
	 */
	public function ticket_deliveries()
	{
		$ci =& get_instance();
		$data = array(
			'Magazine' => array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 ),
			'Rigid Box' => array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 ),
		);

		$sql = "SELECT
							i.item_group,
							i.item_unit,
							SUM( IF( i.base_item_id IS NULL, ti.quantity, ti.quantity * ct.conversion_factor ) ) AS quantity
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						WHERE
							t.destination_id = ".$this->st_store_id."
							AND t.recipient_shift = ".$this->st_from_shift_id."
							AND t.receipt_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category = ".TRANSFER_CATEGORY_REPLENISHMENT."
							AND i.item_class = 'ticket'
							AND t.transfer_status = ".TRANSFER_RECEIVED."
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY i.item_group, i.item_unit";
		$query = $ci->db->query( $sql );

		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['item_unit'] )
			{
				case 'magazine':
					$unit = 'Magazine';
					break;

				case 'box':
					$unit = 'Rigid Box';
					break;

				default:
					continue;
			}

			if( !isset( $data[$unit] ) )
			{
				$data[$unit] = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );
			}

			switch( $row['item_group'] )
			{
				case 'SJT':
					$data[$unit]['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$data[$unit]['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$data[$unit]['Concessionary'] += $row['quantity'];
					break;
			}
		}

		return $data;
	}

	/**
	 * Get ticket receipts
	 */
	public function ticket_receipts()
	{
		$ci =& get_instance();
		$data = array(
			'Magazine' => array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 ),
			'Rigid Box' => array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 ),
		);

		$sql = "SELECT
							i.item_group,
							i.item_unit,
							SUM( IF( i.base_item_id IS NULL, ti.quantity, ti.quantity * ct.conversion_factor ) ) AS quantity
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						WHERE
							t.destination_id = ".$this->st_store_id."
							AND t.recipient_shift = ".$this->st_from_shift_id."
							AND t.receipt_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category IN (".implode( ',', array( TRANSFER_CATEGORY_EXTERNAL, TRANSFER_CATEGORY_INTERNAL ) ).")
							AND i.item_class = 'ticket'
							AND t.transfer_status = ".TRANSFER_RECEIVED."
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY i.item_group, i.item_unit";
		$query = $ci->db->query( $sql );

		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['item_unit'] )
			{
				case 'magazine':
					$unit = 'Magazine';
					break;

				case 'box':
					$unit = 'Rigid Box';
					break;

				default:
					continue;
			}

			if( !isset( $data[$unit] ) )
			{
				$data[$unit] = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );
			}

			switch( $row['item_group'] )
			{
				case 'SJT':
					$data[$unit]['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$data[$unit]['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$data[$unit]['Concessionary'] += $row['quantity'];
					break;
			}
		}

		return $data;
	}

	/**
	 * Get ticket transfers
	 */
	public function ticket_transfers()
	{
		$ci =& get_instance();
		$data = array(
			'Magazine' => array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 ),
			'Rigid Box' => array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 ),
		);

		$sql = "SELECT
							i.item_group,
							i.item_unit,
							SUM( IF( i.base_item_id IS NULL, ti.quantity, ti.quantity * ct.conversion_factor ) ) AS quantity
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						WHERE
							t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category IN (".implode( ',', array( TRANSFER_CATEGORY_EXTERNAL, TRANSFER_CATEGORY_INTERNAL ) ).")
							AND i.item_class = 'ticket'
							AND t.transfer_status IN (".implode( ',', array( TRANSFER_APPROVED, TRANSFER_RECEIVED ) ).")
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY i.item_group, i.item_unit";
		$query = $ci->db->query( $sql );

		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['item_unit'] )
			{
				case 'magazine':
					$unit = 'Magazine';
					break;

				case 'box':
					$unit = 'Rigid Box';
					break;

				default:
					$unit = 'Others';
			}

			if( !isset( $data[$unit] ) )
			{
				$data[$unit] = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );
			}

			switch( $row['item_group'] )
			{
				case 'SJT':
					$data[$unit]['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$data[$unit]['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$data[$unit]['Concessionary'] += $row['quantity'];
					break;
			}
		}

		return $data;
	}

	/**
	 * Get Teller Ticket Remittance
	 */
	public function teller_ticket_remittance()
	{
		$ci =& get_instance();

		$data = array(
			'Sealed' => array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 ),
			'Loose' => array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 ),
		);

		$sql = "SELECT
							i.item_group,
							IF( i.base_item_id IS NULL, 'Loose', 'Sealed' ) AS package,
							SUM( IF( i.base_item_id IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor ) ) AS quantity
						FROM allocations AS a
						LEFT JOIN allocation_items AS ai
							ON ai.allocation_id = a.id
						LEFT JOIN categories AS c
							ON c.id = ai.allocation_category_id
						LEFT JOIN items AS i
							ON i.id = ai.allocated_item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						WHERE
							a.business_date = '".$this->st_from_date."'
							AND ai.allocation_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND a.store_id = ".$this->st_store_id."
							AND a.assignee_type = ".ALLOCATION_ASSIGNEE_TELLER."
							AND ai.cashier_shift_id = ".$this->st_from_shift_id."
							AND ai.allocation_item_type = ".ALLOCATION_ITEM_TYPE_REMITTANCE."
							AND i.item_class = 'ticket'
							AND c.cat_name = 'Unsold'
							AND NOT ai.allocation_item_status = ".REMITTANCE_ITEM_VOIDED."
						GROUP BY i.item_group, package";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			if( ! isset( $row['package'] ) )
			{
				$data[$row['package']] = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );
			}

			switch( $row['item_group'] )
			{
				case 'SJT':
					$data[$row['package']]['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$data[$row['package']]['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$data[$row['package']]['Concessionary'] += $row['quantity'];
					break;
			}
		}

		return $data;
	}

	/**
	 * Get TVM Unsold Tickets
	 */
	public function tvm_unsold_tickets()
	{
		$ci =& get_instance();

		$data = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );

		$sql = "SELECT
							i.item_group,
							SUM( IF( i.base_item_id IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor ) ) AS quantity
						FROM allocations AS a
						LEFT JOIN allocation_items AS ai
							ON ai.allocation_id = a.id
						LEFT JOIN categories AS c
							ON c.id = ai.allocation_category_id
						LEFT JOIN items AS i
							ON i.id = ai.allocated_item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						WHERE
							a.business_date = '".$this->st_from_date."'
							AND ai.allocation_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND a.store_id = ".$this->st_store_id."
							AND a.assignee_type = ".ALLOCATION_ASSIGNEE_MACHINE."
							AND ai.cashier_shift_id = ".$this->st_from_shift_id."
							AND ai.allocation_item_type = ".ALLOCATION_ITEM_TYPE_REMITTANCE."
							AND i.item_class = 'ticket'
							AND c.cat_name = 'Unsold'
							AND NOT ai.allocation_item_status = ".REMITTANCE_ITEM_VOIDED."
						GROUP BY i.item_group";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['item_group'] )
			{
				case 'SJT':
					$data['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$data['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$data['Concessionary'] += $row['quantity'];
					break;
			}
		}

		return $data;
	}

	/**
	 * Get Ticket Allocation
	 */
	public function ticket_allocations()
	{
		$ci =& get_instance();

		$data = array( 'teller' => array(), 'TVM' => array() );

		$sql = "SELECT
							a.assignee_type,
							s.shift_num,
							i.item_group,
							SUM( IF( i.base_item_id IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor ) ) AS quantity
						FROM allocations AS a
						LEFT JOIN allocation_items AS ai
							ON ai.allocation_id = a.id
						LEFT JOIN items i
							ON i.id = ai.allocated_item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						LEFT JOIN categories AS c
							ON c.id = ai.allocation_category_id
						LEFT JOIN shifts AS s
							ON s.id = a.shift_id
						WHERE
							a.store_id = ".$this->st_store_id."
							AND ai.allocation_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND ai.cashier_shift_id = ".$this->st_from_shift_id."
							AND ai.allocation_item_type = ".ALLOCATION_ITEM_TYPE_ALLOCATION."
							AND i.item_class = 'ticket'
							AND c.cat_name IN ( 'TVMAlloc', 'InitAlloc', 'AddAlloc' )
							AND NOT ai.allocation_item_status IN (".implode( ', ', array( ALLOCATION_ITEM_VOIDED, ALLOCATION_ITEM_CANCELLED ) ).")
						GROUP BY assignee_type, shift_num, item_group";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		foreach( $r as $row )
		{
			$shift_num = $row['shift_num'];
			switch( $row['assignee_type'] )
			{
				case 1:
					$assignee_type = 'teller';
					break;

				case 2:
					$assignee_type = 'TVM';
					break;

				default:
					die( 'Invalid assignee type detected. Contact your system administrator.' );
			}

			if( ! isset( $data[$assignee_type][$shift_num] ) )
			{
				$data[$assignee_type][$shift_num] = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );
			}

			switch( $row['item_group'] )
			{
				case 'SJT':
					$data[$assignee_type][$shift_num]['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$data[$assignee_type][$shift_num]['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$data[$assignee_type][$shift_num]['Concessionary'] += $row['quantity'];
					break;

				default:
					die( 'Invalid item group detected. Contact your system administrator.' );
			}
		}

		return $data;
	}

	/**
	 * Get ticket turnovers
	 */
	public function ticket_turnovers()
	{
		$ci =& get_instance();
		$data = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );

		$sql = "SELECT
							i.item_group,
							SUM( IF( i.base_item_id IS NULL, ti.quantity, ti.quantity * ct.conversion_factor ) ) AS quantity
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						WHERE
							t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category = ".TRANSFER_CATEGORY_TURNOVER."
							AND i.item_class = 'ticket'
							AND t.transfer_status IN (".implode( ',', array( TRANSFER_APPROVED, TRANSFER_RECEIVED ) ).")
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY i.item_group";
		$query = $ci->db->query( $sql );

		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['item_group'] )
			{
				case 'SJT':
					$data['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$data['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$data['Concessionary'] += $row['quantity'];
					break;
			}
		}

		return $data;
	}

	/**
	 * Get ticket issuances
	 */
	public function ticket_issuances()
	{
		$ci =& get_instance();
		$data = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );

		$sql = "SELECT
							i.item_group,
							SUM( IF( i.base_item_id IS NULL, ti.quantity, ti.quantity * ct.conversion_factor ) ) AS quantity
						FROM transfers AS t
						LEFT JOIN transfer_items AS ti
							ON ti.transfer_id = t.id
						LEFT JOIN items AS i
							ON i.id = ti.item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						WHERE
							t.origin_id = ".$this->st_store_id."
							AND t.sender_shift = ".$this->st_from_shift_id."
							AND t.transfer_datetime BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND t.transfer_category = ".TRANSFER_CATEGORY_TURNOVER."
							AND i.item_class = 'ticket'
							AND t.transfer_status IN (".implode( ',', array( TRANSFER_APPROVED, TRANSFER_RECEIVED ) ).")
							AND ti.transfer_item_status NOT IN (".implode( ',', array( TRANSFER_ITEM_CANCELLED, TRANSFER_ITEM_VOIDED ) ).")
						GROUP BY i.item_group";
		$query = $ci->db->query( $sql );

		$r = $query->result_array();

		foreach( $r as $row )
		{
			switch( $row['item_group'] )
			{
				case 'SJT':
					$data['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$data['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$data['Concessionary'] += $row['quantity'];
					break;
			}
		}

		return $data;
	}

	/**
	 * Other ticket additions
	 */
	public function other_ticket_additions()
	{
		$ci =& get_instance();

		$data = array();

		// Adjustments
		$sql = "SELECT
							i.item_group,
							SUM( IF( i.base_item_id IS NULL, a.adjusted_quantity - a.previous_quantity, (a.adjusted_quantity - a.previous_quantity) * ct.conversion_factor ) ) AS quantity
						FROM adjustments AS a
						LEFT JOIN store_inventory AS si
							ON si.id = a.store_inventory_id
						LEFT JOIN items AS i
							ON i.id = si.item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						LEFT JOIN items AS pi
							ON pi.id = si.parent_item_id
						WHERE
							a.adjustment_timestamp BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND a.adjustment_shift = ".$this->st_from_shift_id."
							AND a.adjustment_status = ".ADJUSTMENT_APPROVED."
							AND si.store_id = ".$this->st_store_id."
							AND i.item_class = 'ticket'
						GROUP BY item_group";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		$adjustment_data = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );
		foreach( $r as $row )
		{
			switch( $row['item_group'] )
			{
				case 'SJT':
					$adjustment_data['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$adjustment_data['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$adjustment_data['Concessionary'] += $row['quantity'];
					break;

				default:
					die( 'Invalid item group detected. Contact your system administrator' );
			}
		}

		if( $adjustment_data['SJT'] > 0 )
		{
			$add_adjustment = true;
		}
		else
		{
			$adjustment_data['SJT'] = NULL;
		}

		if( $adjustment_data['SVC'] > 0 )
		{
			$add_adjustment = true;
		}
		else
		{
			$adjustment_data['SVC'] = NULL;
		}

		if( $adjustment_data['Concessionary'] > 0 )
		{
			$add_adjustment = true;
		}
		else
		{
			$adjustment_data['Concessionary'] = NULL;
		}

		if( isset( $add_adjustment ) )
		{
			$data['Adjustment'] = $adjustment_data;
		}

		return $data;
	}

	/**
	 * Other ticket deductions
	 */
	public function other_ticket_deductions()
	{
		$ci =& get_instance();

		$data = array();

		// Adjustments
		$sql = "SELECT
							i.item_group,
							SUM( IF( i.base_item_id IS NULL, a.adjusted_quantity - a.previous_quantity, (a.adjusted_quantity - a.previous_quantity) * ct.conversion_factor ) ) AS quantity
						FROM adjustments AS a
						LEFT JOIN store_inventory AS si
							ON si.id = a.store_inventory_id
						LEFT JOIN items AS i
							ON i.id = si.item_id
						LEFT JOIN conversion_table AS ct
							ON ct.target_item_id = i.id AND ct.source_item_id = i.base_item_id
						LEFT JOIN items AS pi
							ON pi.id = si.parent_item_id
						WHERE
							a.adjustment_timestamp BETWEEN '".$this->st_from_date." 00:00:00' AND '".$this->st_from_date." 23:59:59'
							AND a.adjustment_shift = ".$this->st_from_shift_id."
							AND a.adjustment_status = ".ADJUSTMENT_APPROVED."
							AND si.store_id = ".$this->st_store_id."
							AND i.item_class = 'ticket'
						GROUP BY item_group";
		$query = $ci->db->query( $sql );
		$r = $query->result_array();

		$adjustment_data = array( 'SJT' => 0, 'SVC' => 0, 'Concessionary' => 0 );
		foreach( $r as $row )
		{
			switch( $row['item_group'] )
			{
				case 'SJT':
					$adjustment_data['SJT'] += $row['quantity'];
					break;

				case 'SVC':
					$adjustment_data['SVC'] += $row['quantity'];
					break;

				case 'Concessionary':
					$adjustment_data['Concessionary'] += $row['quantity'];
					break;

				default:
					die( 'Invalid item group detected. Contact your system administrator' );
			}
		}

		$adjustment_data['SJT'] = $adjustment_data['SJT'] * -1;
		$adjustment_data['SVC'] = $adjustment_data['SVC'] * -1;
		$adjustment_data['Concessionary'] = $adjustment_data['Concessionary'] * -1;

		if( $adjustment_data['SJT'] > 0 )
		{
			$add_adjustment = true;
		}
		else
		{
			$adjustment_data['SJT'] = NULL;
		}

		if( $adjustment_data['SVC'] > 0 )
		{
			$add_adjustment = true;
		}
		else
		{
			$adjustment_data['SVC'] = NULL;
		}

		if( $adjustment_data['Concessionary'] > 0 )
		{
			$add_adjustment = true;
		}
		else
		{
			$adjustment_data['Concessionary'] = NULL;
		}

		if( isset( $add_adjustment ) )
		{
			$data['Adjustment'] = $adjustment_data;
		}

		return $data;
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


	public function cash_balance()
	{
		return array(
			'beginning_balance' => $this->beginning_balance( 'cash_balance' ),
			'for_deposit' => $this->for_deposit_to_bank(),
			'returned_change_fund' => $this->returned_change_fund(),
			'other_additions' => $this->other_cash_additions(),
			'deposits' => $this->deposit_to_bank(),
			'change_fund_allocations' => $this->change_fund_allocations(),
			'other_deductions' => $this->other_cash_deductions(),
			'ending_balance' => $this->ending_balance( 'cash_balance' ),
		);
	}

	/**
	 * Get Change Fund and Sales Collection cash breakdown
	 */
	public function cash_breakdown()
	{
		$ci =& get_instance();
		$ci->load->helper( 'inflector' );
		$ci->load->library( 'shift_turnover' );
		$Shift_Turnover = new Shift_turnover();

		$shift = $this->get_shift();

		// Change Fund
		$items = $this->get_items();
		$change_fund = array();
		$sales_fund = 0.00;
		$cash_on_hand = 0.00;
		foreach( $items as $item )
		{
			if( ( $item->get( 'item_class' ) == 'cash' ) && ( $item->get( 'parent_item_name' ) == 'Change Fund' ) )
			{
				$change_fund[$item->get( 'item_name' )] = array(
					'group' => $item->get( 'item_group' ),
					'unit' => abs( $item->get( 'sti_beginning_balance' ) + $item->get( 'movement' ) ) == 1 ? $item->get( 'item_unit' ) : plural( $item->get( 'item_unit' ) ),
					'quantity' => $item->get( 'sti_beginning_balance' ) + $item->get( 'movement' ),
					'amount' => ( $item->get( 'sti_beginning_balance' ) + $item->get( 'movement' ) ) * $item->get( 'iprice_unit_price' )
				);
				$cash_on_hand += ( $item->get( 'sti_ending_balance' ) ) * $item->get( 'iprice_unit_price' );
			}

			if( ( $item->get( 'item_class' ) == 'cash' ) && ( $item->get( 'parent_item_name' ) == 'Sales' ) )
			{
				$sales_fund += ( $item->get( 'sti_beginning_balance' ) + $item->get( 'movement' ) ) * $item->get( 'iprice_unit_price' );
				$cash_on_hand += ( $item->get( 'sti_ending_balance' ) ) * $item->get( 'iprice_unit_price' );
			}
		}

		// Undeposited Sales Collection
		/*
		$remaining_sales_fund = $sales_fund;

		// Get previous shifts of the day
		$shift_counter = $shift->get( 'shift_order' );
		$c_shift = $shift;
		$c_turnover = $this;
		$for_deposit = array();
		for( $i = 0; $i < $shift_counter; $i++ )
		{
			$data = $c_turnover->for_deposit_to_bank();
			$c_shift = $c_turnover->get_shift();
			if( $c_turnover->get( 'st_from_date' ) == $this->get( 'st_from_date' ) )
			{
				$for_deposit[$c_shift->get( 'shift_num' )] = $data[$c_shift->get( 'shift_num' )]['for_deposit'];
			}
			if( $c_turnover == $this )
			{
				$remaining_sales_fund -= $data[$c_shift->get( 'shift_num' )]['sales_collection'];
			}
			$c_turnover = $c_turnover->get_previous_turnover();
		}

		$for_deposit = array_reverse( $for_deposit );

		if( $remaining_sales_fund <> 0 )
		{
			$for_deposit['Others'] = $remaining_sales_fund;
		}
		*/
		$for_deposit['Sales Collection'] = $sales_fund;

		return array(
				'change_fund' => $change_fund,
				'for_deposit' => $for_deposit,
				'cash_on_hand' => $cash_on_hand
			);
	}

	public function tvmir_breakdown()
	{
		return array(
				'beginning_balance' => $this->beginning_balance( 'tvmir' ),
				'transactions' => $this->tvmir_transactions(),
				'ending_balance' => $this->ending_balance( 'tvmir' ),
			);
	}

	public function csc_breakdown()
	{
		return array(
				'beginning_balance' => $this->beginning_balance( 'csc' ),
				'transactions' => $this->csc_transactions(),
				'ending_balance' => $this->ending_balance( 'csc' ),
			);
	}

	public function ticket_balance()
	{
		return array(
			'beginning_balance' => $this->beginning_balance( 'ticket_balance' ),
			'ticket_deliveries' => $this->ticket_deliveries(),
			'teller_remittances' => $this->teller_ticket_remittance(),
			'tvm_unsold_tickets' => $this->tvm_unsold_tickets(),
			'ticket_receipts' => $this->ticket_receipts(),
			'other_additions' => $this->other_ticket_additions(),
			'ticket_allocations' => $this->ticket_allocations(),
			'ticket_transfers' => $this->ticket_transfers(),
			'ticket_turnovers' => $this->ticket_turnovers(),
			'ticket_issuances' => $this->ticket_issuances(),
			'other_deductions' => $this->other_ticket_deductions(),
			'ending_balance' => $this->ending_balance( 'ticket_balance' ),
		);
	}

	public function ticket_breakdown()
	{
		$ci =& get_instance();
		$ci->load->helper( 'inflector' );

		$shift = $this->get_shift();

		$items = $this->get_items();
		$data = array();

		foreach( $items as $item )
		{
			if( ( $item->get( 'item_class' ) == 'ticket' )
					&& ( $item->get( 'item_type' ) == 1 )
					&& in_array( $item->get( 'item_group' ), array( 'SJT', 'SVC', 'Concessionary' ) ) )
			{
				$group = $item->get( 'item_group' );
				if( ! isset( $data[$group] ) )
				{
					$data[$group] = array();
					$data['totals'][$group] = 0;
				}

				$data[$group][$item->get( 'item_name' )] = array(
					'group' => $item->get( 'item_group' ),
					'unit' => abs( $item->get( 'sti_beginning_balance' ) + $item->get( 'movement' ) ) == 1 ? $item->get( 'item_unit' ) : plural( $item->get( 'item_unit' ) ),
					'quantity' => $item->get( 'sti_beginning_balance' ) + $item->get( 'movement' ),
					'factor' => $item->get( 'conversion_factor' ) == NULL ? 1 : $item->get( 'conversion_factor' ),
					'base_quantity' => $item->get( 'base_beginning_balance' ) + $item->get( 'base_movement' ) );

				$data['totals'][$group] += $item->get( 'base_beginning_balance' ) + $item->get( 'base_movement' );
			}
		}

		return $data;
	}
}