<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Allocation extends MY_Model {

	protected $store_id;
	protected $business_date;
	protected $shift_id;
	protected $station_id;
	protected $assignee;
	protected $assignee_type;
	protected $allocation_status;
	protected $cashier;
	
	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified = 'last_modified';
	
	protected $allocation_items = array();

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'transfers';
	}
	
	public function get_items( $reload = FALSE )
	{
		if( ! isset( $this->allocation_items ) || $reload )
		{
			$this->load->model( 'AllocationItem' );
			$this->db->where( 'allocation_id', $this->id );
			$query = $this->db->get( 'allocation_items' );
			$this->allocation_items = $query->result( 'AllocationItem' );
		}
		
		return $this->allocation_items;
	}
	
	public function add_item( $allocation_item )
	{
		$
	}
	
	public function db_save()
	{
		if( isset( $this->id ) )
		{ //Update
			
		}
		else
		{ // Insert
			// Valid new status
			$valid_new_status = array( ALLOCATION_SCHEDULED, ALLOCATION_ALLOCATED );
			if( in_array( 'allocation_status', $this->db_changes ) && ! in_array( $this->db_changes['allocation_status'], $valid_new_status ) )
			{
				die( 'Invalid allocation status for new record.');
			}
			
			$this->db->trans_start();
			
			// Update record timestamp data
			$this->db->set( $this->db_changes );
			$this->_update_timestamps( TRUE );
			
			$this->_db_insert();
			
			// Update allocation items
			$this->load->model( 'Inventory' );
			$valid_reserve_types = array( ALLOCATION_TYPE_INITIAL );
			
			foreach( $allocation_items as $item )
			{
				$this->set( 'allocation_id', $this->id );
				
				if( $this->allocation_status == ALLOCATION_SCHEDULED )
				{ // Reserve item from inventory
					if( in_array( $item->get( 'allocation_type' ), $valid_reserve_types ) && ! $item->get( 'reserved' ) )
					{ 
						$inventory = $this->Inventory->get_by_store_item( $this->store_id, $item->get( 'id' ) );
						$inventory->reserve( $item->get( 'allocation_quantity' ) );
						$item->set( 'reserved', TRUE );
					}
				}
				
				$this->db_save();
			}
			
			$this->db->trans_complete();
		}
	}
}