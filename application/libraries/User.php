<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends Base_model {

	protected $username;
	protected $full_name;
	protected $position;
	protected $password_hash;
	protected $password_salt;
	protected $user_status;
	protected $user_role;
	protected $group_id;
	protected $date_created;
	protected $date_modified;
	protected $last_modified;

	protected $stores;
	protected $group;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $last_modified_field = 'last_modified';

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'users';
		$this->db_fields = array(
			'username' => array( 'type' => 'string' ),
			'full_name' => array( 'type' => 'string' ),
			'position' => array( 'type' => 'string' ),
			'password_hash' => array( 'type' => 'string', 'exclude' => TRUE ),
			'password_salt' => array( 'type' => 'string', 'exclude' => TRUE ),
			'user_status' => array( 'type' => 'integer' ),
			'user_role' => array( 'type' => 'integer' ),
			'group_id' => array( 'type' => 'integer' )
		);
	}

	public function get_users( $params = array() )
	{
		$query = param( $params, 'q' );
		$role = param( $params, 'role' );
		$group_id = param( $params, 'group' );
		$status = param( $params, 'status' );
		$limit = param( $params, 'limit' );
		$page = param( $params, 'page' );
		$format = param( $params, 'format', 'object' );
		$order = param( $params, 'order', 'full_name ASC, username ASC' );

		$ci =& get_instance();

		if( $query )
		{
			$ci->db->like( 'full_name', $query );
		}

		if( $role )
		{
			$ci->db->where( 'user_role', $role );
		}

		if( $group_id )
		{
			$ci->db->where( 'group_id', $group_id );
		}

		if( $status )
		{
			$ci->db->where( 'status', $status );
		}

		if( $limit )
		{
			$ci->db->limit( $limit, ( $page ? ( ( $page - 1 ) * $limit ) : 0 ) );
		}

		if( $order )
		{
			$ci->db->order_by( $order );
		}

		$ci->db->select( 'u.*, g.group_name' );
		$ci->db->join( 'groups AS g', 'g.id = u.group_id', 'left' );
		$data = $ci->db->get( $this->primary_table. ' AS u' );

		if( $format == 'array' )
		{
			return $data->result_array();
		}

		return $data->result( get_class( $this ) );
	}

	public function count_users( $params = array() )
	{
		$query = param( $params, 'q' );
		$role = param( $params, 'role' );
		$group_id = param( $params, 'group' );
		$status = param( $params, 'status' );

		$ci =& get_instance();

		if( $query )
		{
			$ci->db->like( 'full_name', $query );
		}

		if( $role )
		{
			$ci->db->where( 'user_role', $role );
		}

		if( $group_id )
		{
			$ci->db->where( 'group_id', $group_id );
		}

		if( $status )
		{
			$ci->db->where( 'status', $status );
		}


		$count = $ci->db->count_all_results( $this->primary_table );

		return $count;
	}

	public function set( $property, $value )
	{
		if( property_exists( $this, $property ) )
		{
			switch( $property )
			{
				case 'password_hash':
					// Generate a new password salt first!
					$this->set_password( $value );
					break;
				case 'password_salt':
					// Don't do anything
					break;
				default:
					if( $this->$property !== $value )
					{
						$this->$property = $value;
						$this->_db_change($property, $value);
					}
			}
		}
		else
		{
			log_message('debug', 'Unable to set property '.$property. '. Property does not exist.');
			return FALSE;
		}

		return TRUE;
	}

	public function set_password( $password )
	{
		$ci =& get_instance();
		$ci->load->helper('string');
		$new_salt = random_string('alnum', 10);
		$this->password_salt = $new_salt;

		$password = sha1($new_salt.$password.$new_salt);
		$this->password_hash = $password;
		$this->_db_change('password_hash', $password);
		$this->_db_change('password_salt', $new_salt);

		return $password;
	}

	public function validate_password( $password )
	{
		$password_hash = sha1( $this->password_salt.$password.$this->password_salt );
		return $this->password_hash === $password_hash;
	}

	public function get_by_username( $username )
	{
		$ci =& get_instance();

		$ci->db->where( 'username', $username );
		$ci->db->limit( 1 );
		$query = $ci->db->get( $this->primary_table );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class ( $this ) );
		}
		else
		{
			return NULL;
		}
	}

	public function get_stores()
	{
		if( ! isset( $this->stores ) )
		{
			$ci =& get_instance();
			$ci->load->library( 'store' );

			$ci->db->select( 's.*' );
			$ci->db->where( 'user_id', $this->id );
			$ci->db->join( 'stores AS s', 's.id = su.store_id', 'inner' );
			$ci->db->order_by( 's.id' );
			$stores = $ci->db->get( 'store_users AS su' );
			$this->stores = $stores->result( 'Store' );
		}

		return $this->stores;
	}

	public function get_group()
	{
		if( ! isset( $this->group ) )
		{
			$ci =& get_instance();
			$ci->load->library( 'group' );
			$Group = new Group();

			$this->group = $Group->get_by_id( $this->group_id );
		}

		return $this->group;
	}

	public function check_permissions( $permission, $action )
	{
		$user_Group = $this->get_group();
		if( $user_Group )
		{
			return $user_Group->check_permissions( $permission, $action );
		}
		else
		{
			return FALSE;
		}
	}

	public function assign_store( $stores )
	{
		$ci =& get_instance();

		if( $stores )
		{
			if( ! is_array( $stores ) )
			{
				$stores = array( $stores );
			}

			$ci->db->trans_start();

			$ci->db->where( 'user_id', $this->id );
			if( $stores )
			{
				$ci->db->where_not_in( 'store_id', $stores );
			}
			$ci->db->delete( 'store_users' );

			if( $stores )
			{
				$timestamp = date( TIMESTAMP_FORMAT );
				$values = array();
				foreach( $stores as $store )
				{
					$values[] = sprintf("(%d, %d, '%s')", $store, $this->id, $timestamp );
				}


				$sql = 'INSERT INTO store_users( store_id, user_id, date_joined ) VALUES '.implode(', ', $values)
						.' ON DUPLICATE KEY UPDATE date_joined = date_joined';
				$ci->db->query( $sql );
			}

			$ci->db->trans_complete();

			return $ci->db->trans_status();
		}
		else
		{
			set_message( 'No stores defined' );
			return FALSE;
		}
	}

	public function clear_stores()
	{
		$ci =& get_instance();

		$ci->db->trans_start();
		$ci->db->where( 'user_id', $this->id );
		$ci->db->delete( 'store_users' );
		$ci->db->trans_complete();

		return $ci->db->trans_status();
	}

	public function search( $query )
	{
		$ci =& get_instance();

		$ci->db->like( 'full_name', $query );
		$ci->db->limit( 10 );
		$users = $ci->db->get( $this->primary_table );

		return $users->result( get_class( $this ) );
	}

	public function load_from_data( $data = array(), $overwrite = TRUE )
	{
		// Try to get existing value first if ID exists
		if( array_key_exists( 'id', $data ) && $data['id'] )
		{
			$r = $this->get_by_id( $data['id'] );
		}
		else
		{
			$r = $this;
		}

		foreach( $data as $field => $value )
		{
			if( $field == 'id' )
			{
				continue;
			}
			elseif( $field == 'password' )
			{
				$r->set_password( $value );
			}
			elseif( array_key_exists( $field, $this->db_fields ) )
			{
				if( ! isset( $r->$field ) || $overwrite )
				{
					//echo 'Setting '.$field.' from '.$this->$field.' to '.$value.'...<br />';
					$r->set( $field, $value );
				}
			}
			elseif( property_exists( $this, $field ) )
			{
				$r->$field = $value;
			}
		}

		return $r;
	}

	public function db_save()
	{
		// There are no pending changes, just return the record
		if( ! $this->db_changes )
		{
			return $this;
		}

		$ci =& get_instance();

		$result = NULL;
		$ci->db->trans_start();
		if( isset( $this->id ) )
		{
			$ci->db->set( $this->db_changes );
			$result = $this->_db_update();
		}
		else
		{
			$ci->db->set( $this->db_changes );
			$result = $this->_db_insert();
		}
		$ci->db->trans_complete();

		if( $ci->db->trans_status() )
		{
			$this->_reset_db_changes();
			return $result;
		}
		else
		{
			return FALSE;
		}
	}
}