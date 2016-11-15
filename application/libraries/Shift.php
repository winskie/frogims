<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shift extends Base_model
{
    protected $store_type;
	protected $shift_num;
    protected $shift_start_time;
    protected $shift_end_time;
	protected $description;
	protected $shift_next_shift_id;
	protected $shift_order;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'shifts';
		$this->db_fields = array(
			'store_type' => array( 'type' => 'integer' ),
            'shift_num' => array( 'type' => 'string' ),
			'description' => array( 'type' => 'string' ),
            'shift_start_time' => array( 'type' => 'time' ),
            'shift_end_time' => array( 'type' => 'time' ),
			'shift_next_shift_id' => array( 'type' => 'integer' ),
			'shift_order' => array( 'type' => 'integer' )
		);
	}

	public function get_shifts( $params = array() )
	{
		$ci =& get_instance();
		$format = param( $params, 'format', 'object' );
		$store_type = param( $params, 'store_type' );

		if( $store_type )
		{
			if( is_array( $store_type ) )
			{
				$ci->db->where_in( 'store_type', $store_type );
			}
			else
			{
				$ci->db->where( 'store_type', $store_type );
			}
		}
		$shifts = $ci->db->get( $this->primary_table );
		$shifts = $shifts->result( get_class( $this ) );

		if( $format == 'array' )
		{
			$shifts_data = array();
			foreach( $shifts as $shift )
			{
				$shifts_data[] = $shift->as_array();
			}
			return $shifts_data;
		}

		return $shifts;
	}
}