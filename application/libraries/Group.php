<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Group extends Base_model {

	protected $group_name;
	protected $is_admin;

	protected $members;

	protected $group_perm_transaction; // none | view
	protected $group_perm_shift_turnover; // none | view | edit
	protected $group_perm_transfer_validation; // none | view | edit
	protected $group_perm_transfer_validation_complete; // true | false
	protected $group_perm_transfer; // none | view | edit
	protected $group_perm_transfer_approve; //  true | false
	protected $group_perm_adjustment; // none | view | edit
	protected $group_perm_adjustment_approve; // true | false
	protected $group_perm_conversion; // none | view | edit
	protected $group_perm_conversion_approve; // true | false
	protected $group_perm_collection; // none | view | edit
	protected $group_perm_allocation; // none | view | edit
	protected $group_perm_allocation_allocate; // true | false
	protected $group_perm_allocation_complete; // true | false
	protected $group_perm_dashboard; // comma delimited dashboard widget name

	//protected $group_perm_users; // none | view | edit
	//protected $group_perm_groups; // none | view | edit
	//protected $group_perm_stores; // none | view | edit
	//protected $group_perm_items; // none | view | edit

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'groups';
		$this->db_fields = array(
			'group_name' => array( 'type' => 'string' ),
			'is_admin' => array( 'type' => 'boolean' ),

			'group_perm_transaction' => array( 'type' => 'string' ),

			'group_perm_shift_turnover' => array( 'type' => 'string' ),

			'group_perm_transfer_validation' => array( 'type' => 'string' ),
			'group_perm_transfer_validation_complete' => array( 'type' => 'boolean' ),

			'group_perm_transfer' => array( 'type' => 'string' ),
			'group_perm_transfer_approve' => array( 'type' => 'boolean' ),

			'group_perm_adjustment' => array( 'type' => 'string' ),
			'group_perm_adjustment_approve' => array( 'type' => 'boolean' ),

			'group_perm_conversion' => array( 'type' => 'string' ),
			'group_perm_conversion_approve' => array( 'type' => 'boolean' ),

			'group_perm_collection' => array( 'type' => 'string' ),

			'group_perm_allocation' => array( 'type' => 'string' ),
			'group_perm_allocation_allocate' => array( 'type' => 'boolean' ),
			'group_perm_allocation_complete' => array( 'type' => 'boolean' ),

			'group_perm_dashboard' => array( 'type' => 'string' )

			//'group_perm_users' => array( 'type' => 'string' ),
			//'group_perm_groups' => array( 'type' => 'string' ),
			//'group_perm_stores' => array( 'type' => 'string' ),
			//'group_perm_items' => array( 'type' => 'string' )
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

	public function get_permissions()
	{
		$permissions = array(
			'transactions' => param_type( $this->group_perm_transaction, 'string' ),
			'transfers' => param_type( $this->group_perm_transfer, 'string' ),
			'shift_turnovers' => param_type( $this->group_perm_shift_turnover, 'string' ),
			'transfers_approve' => param_type( $this->group_perm_transfer_approve, 'boolean' ),
			'transfer_validations' => param_type( $this->group_perm_transfer_validation, 'string' ),
			'transfer_validations_complete' => param_type( $this->group_perm_transfer_validation_complete, 'boolean' ),
			'adjustments' => param_type( $this->group_perm_adjustment, 'string' ),
			'adjustments_approve' => param_type( $this->group_perm_adjustment_approve, 'boolean' ),
			'conversions' => param_type( $this->group_perm_conversion, 'string' ),
			'conversions_approve' => param_type( $this->group_perm_conversion_approve, 'boolean' ),
			'collections' => param_type( $this->group_perm_collection, 'string' ),
			'allocations' => param_type( $this->group_perm_allocation, 'string' ),
			'allocations_allocate' => param_type( $this->group_perm_allocation_allocate, 'boolean' ),
			'allocations_complete' => param_type( $this->group_perm_allocation_complete, 'boolean' ),
			'dashboard' => param_type( $this->group_perm_dashboard, 'string' )
		);

		return $permissions;
	}

	public function check_permissions( $permission_name, $action )
	{
		switch( $permission_name )
		{
			case 'transactions':
				switch( $action )
				{
					case 'view':
						$allowed_permissions = array( 'view' );
						$permission = $this->group_perm_transaction;
						break;

					default:
						return FALSE;
				}
				return in_array( $permission, $allowed_permissions );
				break;

			case 'shift_turnovers':
				switch( $action )
				{
					case 'view':
						$allowed_permissions = array( 'view', 'edit' );
						$permission = $this->group_perm_shift_turnover;
						break;

					case 'edit':
						$allowed_permissions = array( 'edit' );
						$permission = $this->group_perm_shift_turnover;
						break;

					default:
						return FALSE;

				}
				return in_array( $permission, $allowed_permissions );
				break;

			case 'transfers':
				switch( $action )
				{
					case 'view':
						$allowed_permissions = array( 'view', 'edit' );
						$permission = $this->group_perm_transfer;
						break;

					case 'edit':
						$allowed_permissions = array( 'edit' );
						$permission = $this->group_perm_transfer;
						break;

					case 'approve':
						$allowed_permissions = array( true );
						$permission = $this->group_perm_transfer_approve;
						break;

					default:
						return FALSE;

				}
				return in_array( $permission, $allowed_permissions );
				break;

			case 'transfer_validations':
				switch( $action )
				{
					case 'view':
						$allowed_permissions = array( 'view', 'edit' );
						$permission = $this->group_perm_transfer_validation;
						break;

					case 'edit':
						$allowed_permissions = array( 'edit' );
						$permission = $this->group_perm_transfer_validation;
						break;

					case 'complete':
						$allowed_permissions = array( true );
						$permission = $this->group_perm_transfer_validation_complete;
						break;

					default:
						return FALSE;

				}
				return in_array( $permission, $allowed_permissions );
				break;

			case 'adjustments':
				switch( $action )
				{
					case 'view':
						$allowed_permissions = array( 'view', 'edit' );
						$permission = $this->group_perm_adjustment;
						break;

					case 'edit':
						$allowed_permissions = array( 'edit' );
						$permission = $this->group_perm_adjustment;
						break;

					case 'approve':
						$allowed_permissions = array( true );
						$permission = $this->group_perm_adjustment_approve;
						break;

					default:
						return FALSE;

				}
				return in_array( $permission, $allowed_permissions );
				break;

			case 'conversions':
				switch( $action )
				{
					case 'view':
						$allowed_permissions = array( 'view', 'edit' );
						$permission = $this->group_perm_conversion;
						break;

					case 'edit':
						$allowed_permissions = array( 'edit' );
						$permission = $this->group_perm_conversion;
						break;

					case 'approve':
						$allowed_permissions = array( true );
						$permission = $this->group_perm_conversion_approve;
						break;

					default:
						return FALSE;

				}
				return in_array( $permission, $allowed_permissions );
				break;

			case 'collections':
				switch( $action )
				{
					case 'view':
						$allowed_permissions = array( 'view', 'edit' );
						$permission = $this->group_perm_collection;
						break;

					case 'edit':
						$allowed_permissions = array( 'edit' );
						$permission = $this->group_perm_collection;
						break;

					default:
						return FALSE;

				}
				return in_array( $permission, $allowed_permissions );
				break;

			case 'allocations':
				switch( $action )
				{
					case 'view':
						$allowed_permissions = array( 'view', 'edit' );
						$permission = $this->group_perm_allocation;
						break;

					case 'edit':
						$allowed_permissions = array( 'edit' );
						$permission = $this->group_perm_allocation;
						break;

					case 'allocate':
						$allowed_permissions = array( true );
						$permission = $this->group_perm_allocation_allocate;
						break;

					case 'complete':
						$allowed_permissions = array( true );
						$permission = $this->group_perm_allocation_complete;
						break;

					default:
						return FALSE;

				}
				return in_array( $permission, $allowed_permissions );
				break;

			case 'dashboard':
				$allowed_widgets = explode(',', $this->group_perm_dashboard );
				if( count( $allowed_widgets ) )
				{
					return in_array( $action, $allowed_widgets );
				}
				else
				{
					return FALSE;
				}
				break;

			default:
				return FALSE;
		}

		return FALSE;
	}
}