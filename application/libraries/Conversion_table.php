<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conversion_table extends Base_model {

    protected $source_item_id;
    protected $target_item_id;
    protected $conversion_factor;

    public function __construct()
    {
        parent::__construct();
        $this->primary_table = 'conversion_table';
        $this->db_fields = array(
                'source_item_id' => array( 'type' => 'integer' ),
                'target_item_id' => array( 'type' => 'integer' ),
                'conversion_factor' => array( 'type' => 'integer' )
            );
    }

    public function get_packing_data( $params = array() )
    {
        $ci =& get_instance();

        $format = param( $params, 'format', 'object' );

        $ci->db->select( 'ct.*, i.item_name, i.item_description' );
        $ci->db->where( 'conversion_factor >', 1 );
        $ci->db->join( 'items i', 'i.id = ct.target_item_id', 'left' );
        $conversions = $ci->db->get( $this->primary_table.' ct' );
        $conversions = $conversions->result( get_class( $this ) );

        if( $format == 'array' )
        {
            $conversions_data = array();
            foreach( $conversions as $conversion )
            {
                $conversions_data[] = $conversion->as_array( array(
                    'item_name' => array( 'type' => 'string' ),
                    'item_description' => array( 'type' => 'string' ) ) );
            }
            return $conversions_data;
        }

        return $conversions;
    }

    public function get_conversion_data( $params = array() )
    {
        $ci =& get_instance();

        $format = param( $params, 'format', 'object' );


        $conversions = $ci->db->get( $this->primary_table.' ct' );
        $conversions = $conversions->result( get_class( $this ) );

        if( $format == 'array' )
        {
            $conversions_data = array();
            foreach( $conversions as $conversion )
            {
                $conversions_data[] = $conversion->as_array();
            }
            return $conversions_data;
        }

        return $conversions;
    }

    public function convert( $input_item_id, $output_item_id, $input_quantity )
    {
        $ci =& get_instance();

        $ci->db->where( 'source_item_id', $input_item_id );
        $ci->db->where( 'target_item_id', $output_item_id );
        $ci->db->limit( 1 );
        $result = $ci->db->get( 'conversion_table' );

        if( $result->num_rows() )
        {
            $conversion_factor = $result->row()->conversion_factor;
            return $input_quantity / $conversion_factor;
        }
        else
        {
            $ci->db->where( 'target_item_id', $input_item_id );
            $ci->db->where( 'source_item_id', $output_item_id );
            $ci->db->limit( 1 );
            $result = $ci->db->get( 'conversion_table' );

            if( $result->num_rows() )
            {
                $conversion_factor = $result->row()->conversion_factor;
                return $input_quantity * $conversion_factor;
            }
            else
            {
                return FALSE;
            }
        }
    }
}