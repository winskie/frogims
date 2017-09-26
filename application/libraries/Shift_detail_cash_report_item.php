<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift_detail_cash_report_item extends Base_model
{
	protected $sdcri_sdcr_id;
	protected $sdcri_card_profile_id;
	protected $sdcri_property;
	protected $sdcri_quantity;
	protected $sdcri_amount;

	protected $parent;
	protected $marked_void;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';


	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'shift_detail_cash_report_items';
		$this->db_fields = array(
			'sdcri_sdcr_id' => array( 'type' => 'integer' ),
			'sdcri_card_profile_id' => array( 'type' => 'integer' ),
			'sdcri_property' => array( 'type' => 'string' ),
			'sdcri_quantity' => array( 'type' => 'integer' ),
			'sdcri_amount' => array( 'type' => 'decimal' )
		);
	}


	public function set_parent( &$parent )
	{
		$this->parent = $parent;
	}

}