<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
    function current_user()
    {
        if( isset( $_SESSION['current_user_id'] ) )
        {
            return $_SESSION['current_user_id'];
        }
        else
        {
            return NULL;
        }
    }
}

if( ! function_exists( 'current_store' ) )
{
    function current_store()
    {
        if( isset( $_SESSION['current_store_id'] ) )
        {
            return $_SESSION['current_store_id'];
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
        return isset( $_SESSION['current_userid'] ) && $_SESSION['is_admin'] === TRUE;
    }
}