<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if( ! function_exists( 'array_value_search' ) )
{
	function array_value_search( $field, $value, $array, $strict_mode = TRUE )
	{
		foreach( $array as $k => $v )
		{
			if( $strict_mode )
			{
				if( $v->get( $field ) === $value )
				{
					return $k;
				}
			}
			else
			{
				if( $v->get( $field ) == $value )
				{
					return $k;
				}
			}
		}
		
		return NULL;
	}
}