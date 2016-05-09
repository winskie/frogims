<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift extends Base_model
{
    protected $store_type;
	protected $shift_num;
    protected $shift_start_time;
    protected $shift_end_time;
	protected $description;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'shifts';
		$this->db_fields = array(
			'store_type' => array( 'type' => 'integer' ),
            'shift_num' => array( 'type' => 'string' ),
			'description' => array( 'type' => 'string' ),
            'shift_start_time' => array( 'type' => 'time' ),
            'shift_end_time' => array( 'type' => 'time' )
		);
	}
}