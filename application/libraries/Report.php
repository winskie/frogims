<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report
{
    function history( $params = array() )
    {
        $start_time = param( $params, 'start_date', date( TIMESTAMP_FORMAT, strtotime( 'now - 1 day' ) ) );
        $end_time = param( $params, 'end_date', date( TIMESTAMP_FORMAT ) );
        $store_id = param( $params, 'store' );
        $items = param( $params, 'item' );

        $ci =& get_instance();

        $params = array();
        $sql = 'SELECT
                    i.id AS item_id,
                    i.item_name AS item_name,
                    UNIX_TIMESTAMP( t.transaction_timestamp - INTERVAL SECOND(t.transaction_timestamp) SECOND ) AS timestamp,
                    t.transaction_quantity AS quantity,
                    t.current_quantity AS balance
                FROM transactions t
                LEFT JOIN transactions t0
                ON t0.store_inventory_id = t.store_inventory_id
                    AND ( t0.transaction_timestamp - INTERVAL SECOND(t0.transaction_timestamp) SECOND ) = ( t.transaction_timestamp - INTERVAL SECOND(t.transaction_timestamp) SECOND )
                    AND t0.id > t.id
                LEFT JOIN store_inventory si
                    ON si.id = t.store_inventory_id
                LEFT JOIN items i
                    ON i.id = si.item_id
                WHERE t0.id IS NULL';

        if( $start_time )
        {
            $sql .= " AND t.transaction_timestamp >= ?";
            $params[] = $start_time;
        }

        if( $start_time )
        {
            $sql .= " AND t.transaction_timestamp <= ?";
            $params[] = $end_time;
        }

        if( $store_id )
        {
            $sql .= " AND si.store_id = ?";
            $params[] = $store_id;
        }

        $sql .= ' ORDER BY t.id ASC';

        $data = $ci->db->query( $sql, $params );

        return $data->result_array();
    }

    function delivery_receipt( $params = array() )
    {
        $ci =& get_instance();

        $temp_file = tempnam(sys_get_temp_dir(), 'delivery_receipt_');
        $url = 'http://db.afcs.lan:8080/jasperserver/rest_v2/reports/Reports/TMIS/delivery_receipt.pdf';
        $params = array_merge( array(
                'j_username' => 'jasperadmin',
                'j_password' => 'jasperadmin',
                'TRANSFER_ID' => 1,
                'PREPARED_BY' => NULL,
                'PREPARED_BY_POSITION' => NULL,
                'CHECKED_BY' => NULL,
                'CHECKED_BY_POSITION' => NULL,
                'BEARER' => NULL,
                'BEARER_ID' => NULL,
                'ISSUED_BY' => NULL,
                'ISSUED_BY_POSITION' => NULL,
                'APPROVED_BY' => NULL,
                'APPROVED_BY_POSITION' => NULL
            ), $params );

        try
        {
            $fp = fopen( $temp_file, 'w+' );

            $ch = curl_init( $url.'?'.http_build_query( $params) );
            curl_setopt_array($ch, array(
                    CURLOPT_URL => $url,
                    CURLOPT_BINARYTRANSFER => 1,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_FILE => $fp,
                    CURLOPT_TIMEOUT => 50,
                    CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
                ) );

            $results = curl_exec( $ch );
        }
        catch ( Exception $e )
        {
            var_dump( $e );
        }
        finally
        {
            fclose( $fp );
        }

        if( curl_exec( $ch ) === false)
        {
            return FALSE;
        }
        else
        {
            fclose( $temp_file );
            return $temp_file;
        }
    }
}