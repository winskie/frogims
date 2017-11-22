<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift_turnover_item extends Base_model
{
	protected $sti_turnover_id;
	protected $sti_item_id;
	protected $sti_inventory_id;
	protected $sti_beginning_balance;
	protected $sti_ending_balance;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	protected $item_name;
	protected $item_description;
	protected $item_group;
	protected $item_unit;
	protected $previous_balance;
	protected $movement;
	protected $quantity;
	protected $parent_item_name;

	protected $parentTurnover;

	public function __construct()
	{
		$this->primary_table = 'shift_turnover_items';
		$this->db_fields = array(
			'sti_turnover_id' => array( 'type' => 'integer' ),
			'sti_item_id' => array( 'type' => 'integer' ),
			'sti_inventory_id' => array( 'type' => 'integer' ),
			'sti_beginning_balance' => array( 'type' => 'decimal' ),
			'sti_ending_balance' => array( 'type' => 'decimal' )
		);
		parent::__construct();
	}

	public function set_parent( &$parent )
	{
		$this->parentTurnover = $parent;
	}
}