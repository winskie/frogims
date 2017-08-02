<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if( ! function_exists( 'param' ) )
{
	function param( $param_array, $param, $default = NULL, $type = NULL )
	{
		$r = NULL;

		if( is_array( $param_array ) && array_key_exists( $param, $param_array ) )
		{
			$r = $param_array[$param];
		}
		elseif( $default )
		{
			$r = $default;
		}

		if( $type )
		{
			$r = param_type( $r, $type );
		}

		return $r;
	}

	function param_type( $param, $type = 'raw', $default = NULL )
	{
        if( is_null( $param ) && is_null( $default ) )
        {
            return NULL;
        }

		switch( $type )
		{
			case 'boolean':
				if( $param === 1 )
				{
					$v = TRUE;
				}
				elseif( $param === 0 )
				{
					$v = FALSE;
				}
				elseif( strtolower( $param ) === 'true' )
				{
					$v = TRUE;
				}
				elseif( strtolower( $param ) === 'false' )
				{
					$v = FALSE;
				}
				else
				{
                    if( is_null( $param) )
                    {
                        $v = ( bool ) $default;
                    }
                    else
                    {
					    $v = ( bool ) $param;
                    }
				}
				break;

			case 'integer':
                if( is_null( $param ) )
                {
                    $v = intval( $default );
                }
                else
                {
                    $v = intval( $param );
                }
				break;

			case 'decimal':
                if( is_null( $param ) )
                {
                    $v = floatval( $default );
                }
                else
                {
                    $v = floatval( $param );
                }
				break;

			case 'string':
                if( is_null( $param ) )
                {
                    $v = ( string ) $default;
                }
                else
                {
				    $v = ( string ) $param;
                }
				break;

			case 'datetime': // this is a datetime string
				if( is_null( $param ) )
				{
					$v = date( TIMESTAMP_FORMAT, $default );
				}
				else
				{
					$v = date( TIMESTAMP_FORMAT, strtotime( $param ) );
				}
				break;

			case 'date': // this is a date string
				if( is_null( $param ) )
				{
					$v = date( DATE_FORMAT, $default );
				}
				else
				{
					$v = date( DATE_FORMAT, strtotime( $param ) );
				}
				break;

            case 'time':
                if( is_null( $param ) )
                {
                    $v = date( 'H:i:s', $default );
                }
                else
                {
                    $v = date( 'H:i:s', strtotime( $param ) );
                }
                break;

			default:
				$v = $param;
		}

		return $v;
	}
}