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

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	public function __construct()
	{
		$this->primary_table = 'stores';
		$this->db_fields = array(
			'st_store_id' => array( 'type' => 'integer' ),
			'st_from_date' => array( 'type' => 'date' ),
			'st_from_shift_id' => array( 'type' => 'integer' ),
			'st_to_date' => array( 'type' => 'date' ),
			'st_to_shift_id' => array( 'type' => 'integer' ),
			'st_remarks' => array( 'type' => 'string' )
		);
		parent::__construct();
	}
}