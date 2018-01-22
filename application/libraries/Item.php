<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Item extends Base_model
{
	protected $item_name;
	protected $item_description;
	protected $item_class;
	protected $item_unit;
	protected $item_type;
	protected $item_group;
	protected $base_item_id;
	protected $teller_allocatable;
	protected $teller_remittable;
	protected $teller_saleable;
	protected $machine_allocatable;
	protected $machine_remittable;
	protected $machine_saleable;
	protected $turnover_item;

	protected $iprice_unit_price;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'items';
		$this->db_fields = array(
			'item_name' => array( 'type' => 'string' ),
			'item_description' => array( 'type' => 'string' ),
			'item_class' => array( 'type' => 'string' ),
			'item_unit' => array( 'type' => 'string' ),
			'item_type' => array( 'type' => 'integer' ),
			'item_group' => array( 'type' => 'string' ),
			'base_item_id' => array( 'type' => 'integer' ),
			'teller_allocatable' => array( 'type' => 'boolean' ),
			'teller_remittable' => array( 'type' => 'boolean' ),
			'teller_saleable' => array( 'type' => 'boolean' ),
			'machine_allocatable' => array( 'type' => 'boolean' ),
			'machine_remittable' => array( 'type' => 'boolean' ),
			'machine_saleable' => array( 'type' => 'boolean' ),
			'turnover_item' => array( 'type' => 'boolean' )
		);
	}

	public function get( $property )
	{
		if( $property == 'item_unit_price' )
		{
			if( is_null( $this->iprice_unit_price ) )
			{
				$ci =& get_instance();
				$ci->db->select( 'iprice_unit_price' );
				$ci->db->where( 'iprice_item_id', $this->id );
				$ci->db->limit( 1 );
				$query = $ci->db->get( 'item_prices' );
				$row = $query->row();
				if( isset( $row ) )
				{
					$this->iprice_unit_price = $row->iprice_unit_price;
				}
			}

			return $this->iprice_unit_price;
		}
		elseif( property_exists( $this, $property ) )
		{
			return $this->$property;
		}
		else
		{
			return NULL;
		}
	}

	public function get_items( $params = array() )
	{
		$query = param( $params, 'q' );
		$class = param( $params, 'class' );
		$group = param( $params, 'group' );
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page' );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'item_name ASC' );

		$ci =& get_instance();

		if( $query )
		{
			$ci->db->like( 'item_name', $query );
			$ci->db->or_like( 'item_description', $query );
		}

		if( $class )
		{
			$ci->db->where( 'item_class', $class );
		}

		if( $group )
		{
			$ci->db->where( 'item_group', $group );
		}

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}

		if( $order )
		{
			$ci->db->order_by( $order );
		}

		$ci->db->select( 'i.*' );
		$query = $ci->db->get( $this->primary_table.' AS i' );

		if( $format == 'array' )
		{
			return $query->result_array();
		}

		return $query->result( get_class( $this ) );
	}

	public function count_items( $params = array() )
	{
		$query = param( $params, 'q' );
		$class = param( $params, 'class' );
		$group = param( $params, 'group' );

		$ci =& get_instance();

		if( $query )
		{
			$ci->db->like( 'item_name', $query );
			$ci->db->or_like( 'item_description', $query );
		}

		if( $class )
		{
			$ci->db->where( 'item_class', $class );
		}

		if( $group )
		{
			$ci->db->where( 'item_group', $group );
		}

		$count = $ci->db->count_all_results( $this->primary_table );

		return $count;
	}

	public function get_by_name( $item_name )
	{
		$ci =& get_instance();

		$ci->db->where( 'item_name', $item_name );
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

	public function get_categories()
	{
		$ci =& get_instance();
		$ci->load->library( 'category' );

		$ci->select( 'c.*' );
		$ci->db->where( 'ic_item_id', $this->id );
		$ci->db->join( 'categories c', 'c.id = ic_category_id', 'left' );
		$query = $ci->db->get( 'item_categories' );

		return $query->results( 'Category' );
	}


	public function get_base_quantity()
	{
		if( ! isset( $this->base_item_id ) )
		{
			return FALSE;
		}

		$ci =& get_instance();

		$ci->db->select( 'i.*, ct.conversion_factor' );
		$ci->db->join( 'conversion_table AS ct', 'ct.source_item_id = i.id AND ct.target_item_id ='.$this->id, 'left' );
		$ci->db->where( 'i.id', $this->base_item_id );
		$query = $ci->db->get( 'items AS i' );
		$row = $query->row_array( 0 );
		return $row['conversion_factor'];
	}
}