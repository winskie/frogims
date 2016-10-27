<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift_turnover_item extends Base_model
{
	protected $sti_turnover_id;
	protected $sti_item_id;
	protected $sti_beginning_balance;
	protected $sti_ending_balance;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	public function __construct()
	{
		$this->primary_table = 'stores';
		$this->db_fields = array(
			'sti_turnover_id' => array( 'type' => 'integer' ),
			'sti_item_id' => array( 'type' => 'integer' ),
			'sti_beginning_balance' => array( 'type' => 'integer' ),
			'sti_ending_balance' => array( 'type' => 'integer' )
		);
		parent::__construct();
	}
}