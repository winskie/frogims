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

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

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

	public function get_items()
	{
		$ci =& get_instance();

		$query = $ci->db->get( $this->primary_table );

		return $query->result( get_class( $this ) );
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

    public function get_packed_items()
    {
        $ci =& get_instance();

        $ci->select( 'i.*, ct.conversion_factor' );
        $ci->db->where( 'ct.source_item_id', $this->id );
        $ci->db->join( 'items i', 'i.id = ct.target_item_id', 'left' );
        $query = $ci->db->get( $this->primary_table );

        return $query->result( get_class( $this ) );
    }

    public function get_unpacked_items()
    {
        $ci =& get_instance();

        $ci->select( 'i.*, ct.conversion_factor' );
        $ci->db->where( 'ct.target_item_id', $this->id );
        $ci->db->join( 'items i', 'i.id = ct.source_item_id', 'left' );
        $query = $ci->db->get( $this->primary_table );

        return $query->result( get_class( $this ) );
    }
}