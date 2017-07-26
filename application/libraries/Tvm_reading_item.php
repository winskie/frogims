<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tvm_reading_item extends Base_model
{
	protected $tvmri_reading_id;
	protected $tvmri_name;
	protected $tvmri_quantity;
	protected $tvmri_amount;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';


	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'tvm_reading_items';
		$this->db_fields = array(
			'tvmri_reading_id' => array( 'type' => 'integer' ),
			'tvmri_name' => array( 'type' => 'string' ),
			'tvmri_quantity' => array( 'type' => 'integer' ),
			'tvmri_amount' => array( 'type' => 'decimal' ),
		);
	}
}