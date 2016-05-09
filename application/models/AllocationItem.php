<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AllocationItem extends MY_Model {

	protected $allocation_id;
	protected $item_id;
	protected $allocation_type;
	protected $allocation_quantity;
	protected $reserved;
	
	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified = 'last_modified';
	
	protected $allocation;
	
	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'allocation_items';
	}
	
	public function get_allocation()
	{
		if( ! isset( $this->allocation ) )
		{
			$this->load->model( 'Allocation' );
			$allocation = $this->Allocation->get_by_id( $this->allocation_id );
			$this->allocation = $allocation;
		}
		
		return $this->allocation;
	}
	
	public function db_save()
	{
		if( ! isset( $this->allocation_id ) )
		{
			die( 'Parent allocation has not been set.');
		}
		
		if( isset( $this->id ) )
		{ // Update
			
		}
		else
		{ // Insert
			$allocation = $this->get_allocation();
			
			// Adjust inventory reservation
			if( $allocation->get( 'allocation_status')  )
			
			// 
		}
	}
		
	
}