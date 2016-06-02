<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Item_category extends Base_model
{
	protected $category;
	protected $category_type;
	protected $is_allocation_category;
	protected $is_remittance_category;
	protected $is_transfer_category;
    protected $is_teller;
    protected $is_machine;
    protected $category_status;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'item_categories';
		$this->db_fields = array(
			'category' => array( 'type' => 'string' ),
			'category_type' => array( 'type' => 'integer' ),
			'is_allocation_category' => array( 'type' => 'boolean' ),
			'is_remittance_category' => array( 'type' => 'boolean' ),
			'is_transfer_category' => array( 'type' => 'boolean' ),
            'is_teller' => array( 'type' => 'boolean' ),
            'is_machine' => array( 'type' => 'boolean' ),
            'category_status' => array( 'type' => 'integer' )
		);
	}
    
	public function get_categories( $params = array() )
	{
		$ci =& get_instance();
		$format = param( $params, 'format', 'object' );
		$status = param( $params, 'status', 1 );
		
		if( ! is_null( $status ) )
		{
			$ci->db->where( 'category_status', $status );
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