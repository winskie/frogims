<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tvm_reading extends Base_model
{
	protected $tvmr_machine_id;
	protected $tvmr_datetime;
	protected $tvmr_shift_id;
	protected $tvmr_cashier_id;
	protected $tvmr_last_reading;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';


	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'tvm_readings';
		$this->db_fields = array(
			'tvmr_machine_id' => array( 'type' => 'string' ),
			'tvmr_datetime' => array( 'type' => 'datetime' ),
			'tvmr_shift_id' => array( 'type' => 'integer' ),
			'tvmr_cashier_id' => array( 'type' => 'integer' ),
			'tvmr_last_reading' => array( 'type' => 'boolean' ),
		);
	}
}