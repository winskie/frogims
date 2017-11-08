<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends Base_model
{
	protected $cat_name;
	protected $cat_description;
	protected $cat_module;
	protected $cat_cash;
	protected $cat_ticket;
	protected $cat_teller;
	protected $cat_machine;
	protected $cat_status;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'categories';
		$this->db_fields = array(
			'cat_name' => array( 'type' => 'string' ),
			'cat_description' => array( 'type' => 'string' ),
			'cat_module' => array( 'type' => 'string' ),
			'cat_cash' => array( 'type' => 'boolean' ),
			'cat_ticket' => array( 'type' => 'boolean' ),
			'cat_teller' => array( 'type' => 'boolean' ),
			'cat_machine' => array( 'type' => 'boolean' ),
			'cat_status' => array( 'type' => 'integer' )
		);
	}

	public function get_by_name( $category_name )
	{
		$ci =& get_instance();

		$ci->db->where( 'cat_name', $category_name );
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

	public function get_categories( $params = array() )
	{
		$ci =& get_instance();
		$format = param( $params, 'format', 'object' );
		$status = param( $params, 'status', 1 );

		if( ! is_null( $status ) )
		{
			$ci->db->where( 'cat_status', $status );
		}

		$categories = $ci->db->get( $this->primary_table )->result( get_class( $this ) );

		if( $format == 'array' )
		{
			$categories_array = array();
			foreach( $categories as $category )
			{
				$categories_array[] = $category->as_array();
			}

			return $categories_array;
		}
		else
		{
			return $categories;
		}
	}
}