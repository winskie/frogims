<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if( ! function_exists( 'is_logged_in' ) )
{
	function is_logged_in()
	{
		return isset( $_SESSION['current_user_id'] );
	}
}


if( ! function_exists( 'get_store_id' ) )
{
	function get_store_id( $value, $use_current = TRUE )
	{
		$store_id = param_type( $value, 'integer' );

		if( ! $store_id )
		{
			if( $use_current && $_SESSION['current_store_id'] )
			{
				$store_id = $_SESSION['current_store_id'];
			}
			else
			{
				$store_id = NULL;
			}
		}

		return $store_id;
	}
}

if( ! function_exists( 'current_user' ) )
{
	function current_user( $id_only = FALSE )
	{
		$ci =& get_instance();
		$ci->load->library( 'user' );

		if( isset( $_SESSION['current_user_id'] ) )
		{
			if( $id_only )
			{
				return $_SESSION['current_user_id'];
			}
			else
			{
				$User = new User();
				$current_User = $User->get_by_id( $_SESSION['current_user_id'] );
				//$_SESSION['current_user'] = $current_User;
				return $current_User;
			}
		}
		else
		{
			return NULL;
		}
	}
}

if( ! function_exists( 'current_shift' ) )
{
	function current_shift( $id_only = FALSE )
	{
		if( isset( $_SESSION['current_shift_id'] ) )
		{
			if( $id_only )
			{
				return $_SESSION['current_shift_id'];
			}
			else
			{
				$ci =& get_instance();
				$ci->load->library( 'shift' );
				$Shift = new Shift();
				$current_Shift = $Shift->get_by_id( $_SESSION['current_shift_id'] );

				return $current_Shift;
			}
		}
		else
		{
			return NULL;
		}
	}
}

if( ! function_exists( 'current_store' ) )
{
	function current_store( $id_only = FALSE )
	{
		$ci =& get_instance();
		$ci->load->library( 'store' );

		if( isset( $_SESSION['current_store_id'] ) )
		{
			if( $id_only )
			{
				return $_SESSION['current_store_id'];
			}
			else
			{
				$Store = new Store();
				$current_Store = $Store->get_by_id( $_SESSION['current_store_id'] );
				return $current_Store;
			}
		}
		else
		{
			return NULL;
		}
	}
}

if( ! function_exists( 'is_admin') )
{
	function is_admin()
	{
		return isset( $_SESSION['current_user_id'] ) && $_SESSION['is_admin'] === TRUE;
	}
}

if( ! function_exists( 'set_message' ) )
{
	function set_message( $msg, $msg_type = 'error', $response_code = 202 )
	{
		if( ! isset( $_SESSION['messages'] ) )
		{
			$_SESSION['messages'] = array();
		}

		$_SESSION['messages'][] = array(
			'msg' => $msg,
			'type' => $msg_type,
			'code' => $response_code );
	}
}

if( ! function_exists( 'get_messages' ) )
{
	function get_messages( $clear = TRUE )
	{
		if( isset( $_SESSION['messages'] ) )
		{
			$messages = $_SESSION['messages'];
			if( $clear )
			{
				unset( $_SESSION['messages'] );
			}
			return $messages;
		}
		else
		{
			return NULL;
		}
	}
}

if( ! function_exists( 'is_store_member' ) )
{
	function is_store_member( $store_id, $user_id )
	{
		$ci =& get_instance();

		$ci->db->where( 'store_id', $store_id );
		$ci->db->where( 'user_id', $user_id );
		$ci->db->where( 'user_status', USER_STATUS_ACTIVE );
		$ci->db->join( 'users u', 'u.id = su.user_id' );
		$ci->db->limit( 1 );
		$query = $ci->db->get( 'store_users su' );
		$count = $query->num_rows();

		return $count == 1;
	}
}