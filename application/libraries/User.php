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
	protected $date_created;
	protected $date_modified;
	protected $last_modified;
	
	protected $stores;

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
			'user_role' => array( 'type' => 'integer' )
		);
	}
    
    public function get_users()
	{
		$ci =& get_instance();

		$query = $ci->db->get( $this->primary_table );

		return $query->result( get_class( $this ) );
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
			return $query->row( 0, get_class ($this ) );
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
			$stores = $ci->db->get( 'store_users AS su' );
			$this->stores = $stores->result( 'Store' );
		}
		
		return $this->stores;
	}
	
	public function search( $query )
	{
		$ci =& get_instance();
		
		$ci->db->like( 'full_name', $query );
		$ci->db->limit( 10 );
		$users = $ci->db->get( $this->primary_table );
		
		return $users->result( get_class( $this ) );
	}
}