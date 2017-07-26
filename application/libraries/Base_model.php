<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_model
{
	protected $id;
	protected $primary_table;
	protected $db_changes = array();
	protected $db_fields = array();
	protected $children = array();

	protected $date_created_field;
	protected $date_modified_field;
	protected $created_by_field;
	protected $modified_by_field;

	public function __construct()
	{
		$ci =& get_instance();

		$this->db_metadata = array(
			$this->date_created_field => array(),
			$this->date_modified_field => array(),
			$this->created_by_field => array(),
			$this->modified_by_field => array()
		);

		if( isset( $this->date_created_field ) )
		{
			$date_created_field = $this->date_created_field;
			$this->$date_created_field = NULL;
		}
		if( isset( $this->date_modified_field ) )
		{
			$date_modified_field = $this->date_modified_field;
			$this->$date_modified_field = NULL;
		}
		if( isset( $this->created_by_field ) )
		{
			$created_by_field = $this->created_by_field;
			$this->$created_by_field = NULL;
		}
		if( isset( $this->modified_by_field ) )
		{
			$modified_by_field = $this->modified_by_field;
			$this->$modified_by_field = NULL;
		}
	}

	public function get( $property )
	{
		if( property_exists( $this, $property ) )
		{
			return $this->$property;
		}
		else
		{
			return NULL;
		}
	}

	public function set( $property, $value )
	{
		if( $property == 'id' )
		{
			return FALSE;
		}

		if( property_exists( $this, $property ) )
		{
			if( array_key_exists( $property, $this->db_fields ) )
			{
				//$this_value = param_type( $this->$property, $this->db_fields[$property]['type'] );
				$value = param_type( $value, $this->db_fields[$property]['type'] );
			}
			/*
			else
			{
				$this_value = $this->$property;
			}
			*/
			if( $this->$property !== $value )
			{
				$this->$property = $value;
				$this->_db_change( $property, $value );
			}
		}
		else
		{
			return FALSE;
		}

		return TRUE;
	}


	public function get_by_id( $id )
	{
		$ci =& get_instance();

        $select = array( $this->primary_table.'.*' );

		$ci->db->select( implode(', ', $select ) );
		$ci->db->where( $this->primary_table.'.id', $id );
		$ci->db->limit( 1 );
		$query = $ci->db->get( $this->primary_table );

		if( $query->num_rows() )
		{
			return $query->row( 0, get_class( $this ) );
		}

		return NULL;
	}


	public function _db_update()
	{
		$ci =& get_instance();

		if( $this->db_changes )
		{
			$this->_update_timestamps( FALSE );
			$ci->db->set( $this->db_changes );
			$ci->db->where( 'id', $this->id );
			$ci->db->update( $this->primary_table );

			$this->_log_status();
		}
		//$this->_reset_db_changes();

		return $this;
	}


	public function _db_insert()
	{
		$ci =& get_instance();

		if( $this->db_changes )
		{
			$this->_update_timestamps( TRUE );
			$ci->db->set( $this->db_changes );
			$ci->db->insert( $this->primary_table );

			$this->id = $ci->db->insert_id();

			$this->_log_status();
		}
		//$this->_reset_db_changes();

		return $this;
	}

	public function db_save()
	{
		// There are no pending changes, just return the record
		if( ! $this->db_changes )
		{
			return $this;
		}

		$ci =& get_instance();

		if( $this->_check_data() )
		{
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
		else
		{
			return FALSE;
		}
	}

	public function _check_data()
	{
		return TRUE;
	}


	public function _db_change( $property, $value )
	{

		if( array_key_exists( $property, $this->db_fields ) || array_key_exists( $property, $this->db_metadata ) )
		{
			$this->db_changes[$property] = $value;
		}
	}


	public function _reset_db_changes()
	{
		$this->db_changes = array();
	}


	public function _update_timestamps( $is_new = FALSE )
	{
		$ci =& get_instance();

		if( isset( $this->date_created_field ) && $is_new )
		{
			$this->set( $this->date_created_field, date( TIMESTAMP_FORMAT ) );
		}

		if( isset( $this->date_modified_field ) )
		{
			$this->set( $this->date_modified_field, date( TIMESTAMP_FORMAT ) );
		}

		if( isset( $this->created_by_field ) && current_user( TRUE ) )
		{
			$this->set( $this->created_by_field, current_user( TRUE ) );
		}

		if( isset( $this->modified_by_field ) && current_user( TRUE ) )
		{
			$this->set( $this->modified_by_field, current_user( TRUE ) );
		}
	}


	public function db_remove()
	{
		$ci =& get_instance();

		$ci->db->trans_start();
		$ci->db->where( 'id', $this->id );
		$ci->db->delete( $this->primary_table );
		$ci->db->trans_complete();
	}

	public function as_array( $additional_fields = array(), $include_children = FALSE )
	{
		// IDs are always integers!
		$data = array( 'id' => param_type( $this->id, 'integer' ) );

		foreach( $this->db_fields as $field => $value )
		{
			if( isset( $value['exclude'] ) && $value['exclude'] )
			{ // do not include this field
				continue;
			}

			// set value to proper type
			$v = param_type( $this->$field, $value['type'] );

			if( isset( $value['property'] ) )
			{
				$data[$value['property']] = $v;
			}
			else
			{
				$data[$field] = $v;
			}
		}

        // has children
        if( $include_children && isset( $this->children ) )
        {
            foreach( $this->children as $k => $v )
            {
                $func = 'get_'.$v['field'];
                $children = $this->$func();
                $child_data = array();
                foreach( $children as $child )
                {
                    $child_data[] = $child->as_array();
                }
                $data[$v['field']] = $child_data;
            }
        }

		if( $additional_fields )
		{
			foreach( $additional_fields as $k => $field )
			{
				if( is_array( $field ) )
				{
					if( property_exists( $this, $k ) )
					{
						if( isset( $field['type'] ) )
						{
							$data[$k] = param_type( $this->$k, $field['type'] );
						}
						else
						{
							$data[$k] = $this->$k;
						}
					}
				}
				else
				{
					if( property_exists( $this, $field ) )
					{
						$data[$field] = $this->$field;
					}
				}
			}
		}

		return $data;
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

	public function prune_children( $child )
	{
		$ci =& get_instance();

		if( isset( $this->children[$child] ) )
		{
			$ci->db->trans_start();
			$ci->db->where( $this->children[$child]['key'], $this->id );
			$ci->db->delete( $this->children[$child]['table'] );
			$ci->db->trans_complete();

			return $ci->db->trans_status();
		}

		return NULL;
	}

	public function _log_status()
	{
		if( isset( $this->status_log ) )
		{
			$prefix = $this->status_log['prefix'];
			$fk = $this->status_log['foreign_key'];
			$table = $this->status_log['table'];
			$status_field = $this->status_log['status_field'];

			if( isset( $this->db_changes[$status_field] ) )
			{
				$ci =& get_instance();

				$ci->db->trans_start();

				$ci->db->set( $prefix.$fk, $this->id );
				$ci->db->set( $prefix.'user_id', current_user( TRUE ) );
				$ci->db->set( $prefix.'status', $this->$status_field );
				$ci->db->set( $prefix.'timestamp', date( TIMESTAMP_FORMAT ) );
				$ci->db->insert( $table );

				$ci->db->trans_complete();
			}
		}
	}
}