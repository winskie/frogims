<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Group extends Base_model {

	protected $group_name;
    protected $is_admin;

	protected $members;

	protected $grp_perm_transaction_view; // true | false
	protected $grp_perm_transfer; // none | view | edit
	protected $grp_perm_transfer_approve; //  true | false
	protected $grp_perm_adjustment; // none | view | edit
	protected $grp_perm_adjustment_approve; // true | false
	protected $grp_perm_conversion; // none | view | edit
	protected $grp_perm_conversion_approve; // true | false
	protected $grp_perm_collection; // none | view | edit
	protected $grp_perm_allocation; // none | view | edit
	protected $grp_perm_allocation_allocate; // true | false
	protected $grp_perm_allocation_complete; // true | false

	protected $grp_perm_users; // none | view | edit
	protected $grp_perm_groups; // none | view | edit
	protected $grp_perm_stores; // none | view | edit
	protected $grp_perm_items; // none | view | edit

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'groups';
		$this->db_fields = array(
			'group_name' => array( 'type' => 'string' ),
			'is_admin' => array( 'type' => 'boolean' ),

			'grp_perm_transaction_view' => array( 'type' => 'boolean' ),

			'grp_perm_transfer' => array( 'type' => 'string' ),
			'grp_perm_transfer_approve' => array( 'type' => 'boolean' ),

			'grp_perm_adjustment' => array( 'type' => 'string' ),
			'grp_perm_adjustment_approve' => array( 'type' => 'boolean' ),

			'grp_perm_conversion' => array( 'type' => 'string' ),
			'grp_perm_conversion_approve' => array( 'type' => 'boolean' ),

			'grp_perm_collection' => array( 'type' => 'string' ),

			'grp_perm_allocation' => array( 'type' => 'string' ),
			'grp_perm_allocation_allocate' => array( 'type' => 'boolean' ),
			'grp_perm_allocation_complete' => array( 'type' => 'boolean' ),

			'grp_perm_users' => array( 'type' => 'string' ),
			'grp_perm_groups' => array( 'type' => 'string' ),
			'grp_perm_stores' => array( 'type' => 'string' ),
			'grp_perm_items' => array( 'type' => 'string' )
		);
	}

	public function get_groups( $params = array() )
	{
		$query = param( $params, 'q' );
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page' );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'group_name ASC' );

		$ci =& get_instance();

		if( $query )
		{
			$ci->db->like( 'group_name', $query );
		}

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}

		if( $order )
		{
			$ci->db->order_by( $order );
		}
		$ci->db->select( 'g.id, g.group_name, COUNT( u.id ) AS member_count' );
		$ci->db->join( 'users AS u', 'u.group_id = g.id', 'left' );
		$ci->db->group_by( 'g.id', 'g.group_name' );
		$data = $ci->db->get( $this->primary_table.' AS g' );

		if( $format == 'array' )
		{
			return $data->result_array();
		}

		return $data->result( get_class( $this ) );
	}

	public function count_groups( $params = array() )
	{
		$query = param( $params, 'q' );

		$ci =& get_instance();

		if( $query )
		{
			$ci->db->like( 'full_name', $query );
		}

		$count = $ci->db->count_all_results( $this->primary_table );

		return $count;
	}

	public function get_members()
	{
		if( ! isset( $this->members ) )
		{
			$ci =& get_instance();
			$ci->load->library( 'user' );

			$ci->db->where( 'group_id', $this->id );
			$members = $ci->db->get( 'users' );
			$this->members = $members->result( 'User' );
		}

		return $this->members;
	}

	public function _check_data()
	{
		$ci =& get_instance();

		// Check if unique group name
		if( isset( $this->id ) )
		{
			$ci->db->where( 'id !=', $this->id );
		}
		$ci->db->where( 'group_name', $this->group_name );
		$count = $ci->db->count_all_results( $this->primary_table );
		if( $count )
		{
			set_message( sprintf( 'The group name %s already exists', $this->group_name ), 'error', 202 );
			return FALSE;
		}

		return TRUE;
	}


}