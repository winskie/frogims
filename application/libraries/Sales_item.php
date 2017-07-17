<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sales_item extends Base_model
{
	protected $slitem_name;
	protected $slitem_description;
	protected $slitem_group;
	protected $slitem_mode;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'sales_items';
		$this->db_fields = array(
			'slitem_name' => array( 'type' => 'string' ),
			'slitem_description' => array( 'type' => 'string' ),
			'slitem_group' => array( 'type' => 'string' ),
			'slitem_mode' => array( 'type' => 'integer' )
		);
	}

	public function get_sale_items()
	{
		$ci =& get_instance();

		$query = $ci->db->get( $this->primary_table );

		return $query->result( get_class( $this ) );
	}

	public function get_by_name( $item_name )
	{
		$ci =& get_instance();

		$ci->db->where( 'slitem_name', $item_name );
		$ci->db->limit(1);
		$query = $ci->db->get( $this->primary_table );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class( $this ) );
		}
		else
		{
			return NULL;
		}
	}
}