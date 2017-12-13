<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// TODO: Extend MY_Controller instead after fixing session checking
class Report extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->config->load( 'report' );
	}

	public function get_report_mode()
	{
		$response = array(
				'status' => 'ok',
				'report_mode' => $this->config->item( 'report_mode' )
			);

		$this->output->set_content_type( 'application/json' );
		$this->output->set_output( json_encode( $response ) );
	}

	public function html( $report_path, $params )
	{
		$this->load->view( $report_path, $params );
	}

	private function _generate_jasper_report( $report_path, $format = NULL, $params = array() )
	{
		// Get default format
		if( is_null( $format ) )
		{
			$format = 'pdf';
		}

		// Set format defaults
		switch( $format )
		{
			case 'html':
				$format_ext = 'html';
				$content_type = 'text/html';
				break;

			case 'pdf':
				$format_ext = 'pdf';
				$content_type = 'application/pdf';
				break;

			default:
				return FALSE;
		}

		// Set default report server user credentials
		$params = array_merge( array(
				'j_username' => $this->config->item( 'jasper_username' ),
				'j_password' => $this->config->item( 'jasper_password' )
			), $params );

		// temporary report file
		$tempfile_prefix = isset( $params['tempfile_prefix'] ) ? $params['tempfile_prefix'] : '_report_';
		$temp_file = tempnam( sys_get_temp_dir(), $tempfile_prefix ).'.'.$format_ext;

		$report_url = ( $this->config->item( 'jasper_use_ssl' ) ? 'https' : 'http' ).'://'
				.$this->config->item( 'jasper_server' )
				.( $this->config->item( 'jasper_port' ) ? ':'.$this->config->item( 'jasper_port') : '' ).'/'
				.$this->config->item( 'jasper_reports_path' ).'/';

		$url = $report_url.$report_path.'.'.$format_ext.'?'.http_build_query( $params);

		try
		{
			$fp = fopen( $temp_file, 'w+' );
			$ch = curl_init( $url );
			curl_setopt_array($ch, array(
					CURLOPT_URL => $url,
					CURLOPT_BINARYTRANSFER => 1,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_FILE => $fp,
					CURLOPT_TIMEOUT => 50,
					CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
				) );

			$results = curl_exec( $ch );

			if( curl_exec( $ch ) === false )
			{
				echo 'error: '.curl_error( $ch );
				return FALSE;
			}
			else
			{
				$output_filename = 'report.pdf';
				// Output the file
				header( 'Content-type: '.$content_type );
				//header( 'Content-Disposition: inline; filename="'.$output_filename.'"' );
				header( 'Content-Disposition: attachment; filename="'.$output_filename.'"' );
				header( 'Content-Transfer-Encoding: binary' );
				header( 'Accept-Ranges: bytes' );
				readfile( $temp_file );
			}

			fclose( $fp );
			return TRUE;
		}
		catch ( Exception $e )
		{
			echo 'exception: '.$e;
			return FALSE;
		}
	}


	function delivery_receipt( $report_mode = 'JasperReports' )
	{
		$current_user = current_user();
		$params = $this->input->get();

		$params = array_merge( array(
				'transer_id' => NULL,
				'prepared_by' => $current_user->get( 'full_name' ),
				'prepared_by_position' => $current_user->get( 'position' ),
				'checked_by' => NULL,
				'checked_by_position' => NULL,
				'bearer' => NULL,
				'bearer_id' => NULL,
				'issued_by' => NULL,
				'issued_by_position' => NULL,
				'approved_by' => NULL,
				'approved_by_position' => NULL
			), $params );

		$report_mode = $this->config->item( 'report_mode' );
		switch( $report_mode )
		{
			case 'JasperReports':
				$report_path = 'TMIS/delivery_receipt';

				// Get report format
				$format = NULL;
				if( isset( $params['format'] ) )
				{
					$format = $params['format'];
					unset( $params['format'] );
				}

				return $this->_generate_jasper_report( $report_path, $format, $params );

			case 'TCPDF':
				$this->load->library( 'pdf' );
				$this->load->library( 'transfer' );
				$Transfer = new Transfer();

				$transfer_id = param( $params, 'transfer_id' );
				$transfer = $Transfer->get_by_id( $transfer_id );
				$params = array_merge( array(
						'transfer_item' => $transfer->get_transfer_array()
					), $params );

				unset( $params['transfer_id'] );
				$html = $this->load->view( 'reports/delivery_receipt', $params, TRUE );
				//$pdf = new Pdf( 'p', 'in', 'A4', TRUE, 'utf-8', false );
				//$pdf->writeHTML( $html, TRUE, FALSE, TRUE, FALSE, '' );
				//$pdf->Output();
				var_dump( $html );
				break;

			default:
				$this->load->library( 'transfer' );
				$Transfer = new Transfer();

				$transfer_id = param( $params, 'transfer_id' );
				$transfer = $Transfer->get_by_id( $transfer_id );
				$params = array_merge( array(
						'transfer_item' => $transfer->get_transfer_array()
					), $params );

				unset( $params['transfer_id'] );
				$this->load->view( 'reports/delivery_receipt', $params );
		}
	}

	function receiving_report( $report_mode = 'JasperReports' )
	{
		$params = $this->input->get();

		$params = array_merge( array(
				'transfer_id' => NULL,
				'received_from' => NULL,
				'received_from_position' => NULL,
				'received_by' => NULL,
				'received_by_position' => NULL,
				'checked_by' => NULL,
				'checked_by_position' => NULL
			), $params );

		$report_mode = $this->config->item( 'report_mode' );
		switch( $report_mode )
		{
			case 'JasperReports':
				$report_path = 'TMIS/receiving_report';

				// Get report format
				$format = NULL;
				if( isset( $params['format'] ) )
				{
					$format = $params['format'];
					unset( $params['format'] );
				}

				return $this->_generate_jasper_report( $report_path, $format, $params );

			default:
				// do nothing for now
		}
	}

	function ticket_turnover()
	{
		$current_user = current_user();
		$params = $this->input->get();

		$params = array_merge( $params, array(
				'transfer_id' => NULL,
				'turnover_by' => $current_user->get( 'full_name' )
			), $params );

		$report_mode = $this->config->item( 'report_mode' );
		switch( $report_mode )
		{
			case 'JasperReports':
				$report_path = 'TMIS/ticket_turnover';

				// Get report format
				$format = NULL;
				if( isset( $params['format'] ) )
				{
					$format = $params['format'];
					unset( $params['format'] );
				}

				return $this->_generate_jasper_report( $report_path, $format, $params );

			default:
				// do nothing for now
		}
	}

	function shift_turnover_summary()
	{
		$current_user = current_user();
		$params = $this->input->get();

		$params = array_merge( $params, array(
				'transfer_id' => NULL,
				'business_date' => date( DATE_FORMAT ),
				'shift_id' => NULL
			), $params );

		$report_mode = $this->config->item( 'report_mode' );
		switch( $report_mode )
		{
			case 'JasperReports':
				$report_path = 'TMIS/shift_turnover_summary';

				// Get report format
				$format = NULL;
				if( isset( $params['format'] ) )
				{
					$format = $params['format'];
					unset( $params['format'] );
				}

				return $this->_generate_jasper_report( $report_path, $format, $params );

			default:
				// do nothing for now
		}
	}

	function container_replacement()
	{
		$this->load->library( 'parser' );
		$this->load->library( 'store' );
		$this->load->library( 'shift' );
		$this->load->library( 'category' );
		$Store = new Store();
		$Shift = new Shift();
		$Category = new Category();

		$business_date = param_type( $this->input->get( 'date' ), 'date', time() );
		$store_id = param_type( $this->input->get( 'store' ), 'integer', current_store( TRUE ) );
		$shift_id = param_type( $this->input->get( 'shift' ), 'integer', current_shift( TRUE ) );

		if( empty( $business_date ) ) $business_date = '2017-07-23';
		if( empty( $store_id ) ) $store_id = 11;
		if( empty( $shift_id ) ) $shift_id = 6;

		$store = $Store->get_by_id( $store_id );
		$shift = $Shift->get_by_id( $shift_id );

		$reject_bin_category = $Category->get_by_name( 'RejectBin' )->get( 'id' );
		$unsold_category = $Category->get_by_name( 'Unsold' )->get( 'id' );

		// SJT and SVC Replenishment / Pullout
		$sql = "SELECT
					CONCAT( 'T', LPAD( ints.i, 2, '0' ) ) AS tvm_num,
					reading.reading, reading.previous_reading, reading.reading_time,
					allocation.replenishment, allocation.reject_bin, allocation.excess, allocation.allocation_sold_ticket,
					(reading.previous_reading + COALESCE(allocation.replenishment, 0) - COALESCE(allocation.reject_bin, 0) - COALESCE(allocation.excess, 0) - reading.reading) AS sold_ticket
				FROM ints
				LEFT JOIN
				(
					SELECT
						id, tvmr_machine_id, tvmr_reading AS reading, tvmr_previous_reading AS previous_reading, tvmr_time AS reading_time
					FROM tvm_readings
					WHERE
						tvmr_type = ?
						AND tvmr_date = ?
						AND tvmr_shift_id = ?
						AND tvmr_store_id = ?
				) AS reading
					ON reading.tvmr_machine_id = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )
				LEFT JOIN
				(
					SELECT a.assignee,
						SUM( IF( ai.allocation_item_type = 1, IF( ct.id IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor), 0 ) ) AS replenishment,
						SUM( IF( ai.allocation_item_type = 2 AND i.item_type = 0 AND ai.allocation_category_id = ".$reject_bin_category.", ai.allocated_quantity, 0 ) ) AS reject_bin,
						SUM( IF( ai.allocation_item_type = 2 AND i.item_type = 1 AND ai.allocation_category_id = ".$unsold_category.", ai.allocated_quantity, 0 ) ) AS excess,
						SUM( IF( ai.allocation_item_type = 3, ai.allocated_quantity, 0 ) ) AS allocation_sold_ticket
					FROM allocations a
					LEFT JOIN allocation_items ai
						ON ai.allocation_id = a.id
					LEFT JOIN items i
						ON i.id = ai.allocated_item_id
					LEFT JOIN conversion_table ct
						ON ct.target_item_id = ai.allocated_item_id
					WHERE
						business_date = ?
						AND ai.cashier_shift_id = ?
						AND i.item_class = 'ticket'
						AND i.item_group = ?
					GROUP BY a.assignee
				) AS allocation
					ON allocation.assignee = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )
				WHERE
					ints.i < 17
					AND ( reading.id IS NOT NULL OR allocation.assignee IS NOT NULL )";

		$query = $this->db->query( $sql, array( 'magazine_sjt', $business_date, $shift_id, $store_id, $business_date, $shift_id, 'SJT' ) );
		$sjt_data = $query->result_array();
		$sjt_entries = array();
		foreach( $sjt_data as $row )
		{
			$row['reading'] = number_format( $row['reading'] );
			$row['previous_reading'] = number_format( $row['previous_reading'] );
			$row['sold_ticket'] = number_format( $row['sold_ticket'] );
			$sjt_entries[] = $row;
		}

		$query = $this->db->query( $sql, array( 'magazine_svc', $business_date, $shift_id, $store_id, $business_date, $shift_id, 'SVC' ) );
		$svc_data = $query->result_array();
		$svc_entries = array();
		foreach( $svc_data as $row )
		{
			$row['reading'] = number_format( $row['reading'] );
			$row['previous_reading'] = number_format( $row['previous_reading'] );
			$row['sold_ticket'] = number_format( $row['sold_ticket'] );
			$svc_entries[] = $row;
		}

		// Coin and BNA Box
		$sql = "SELECT
							CONCAT( 'T', LPAD( ints.i, 2, '0' ) ) AS tvm_num,
							reading.reading, reading.box_num, reading.reading_time
						FROM ints
						LEFT JOIN
						(
							SELECT
								id, tvmr_machine_id, tvmr_reading AS reading, tvmr_reference_num AS box_num, tvmr_time AS reading_time
							FROM tvm_readings
							WHERE
								tvmr_type = ?
								AND tvmr_date = ?
								AND tvmr_shift_id = ?
								AND tvmr_store_id = ?
						) AS reading
							ON reading.tvmr_machine_id = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )
						WHERE ints.i < 17 AND ( reading.id IS NOT NULL )
						ORDER BY tvm_num, reading.reading_time ASC";

		$query = $this->db->query( $sql, array( 'coin_box', $business_date, $shift_id, $store_id ) );
		$coin_box_readings = $query->result_array();
		$coin_box_data = array();
		foreach( $coin_box_readings as $row )
		{
			$row['reading'] = number_format( $row['reading'], 2 );
			if( isset( $coin_box_data[$row['tvm_num']] ) )
			{
				$coin_box_data[$row['tvm_num']]['others'][] = $row;
				$coin_box_data[$row['tvm_num']]['rows']++;
			}
			else
			{
				$coin_box_data[$row['tvm_num']] = $row;
				$coin_box_data[$row['tvm_num']]['rows'] = 1;
				$coin_box_data[$row['tvm_num']]['others'] = array();
			}
		}
		$coin_box_entries = array_values( $coin_box_data );

		$query = $this->db->query( $sql, array( 'note_box', $business_date, $shift_id, $store_id ) );
		$note_box_readings = $query->result_array();
		$note_box_data = array();
		foreach( $note_box_readings as $row )
		{
			$row['reading'] = number_format( $row['reading'], 2 );
			if( isset( $note_box_data[$row['tvm_num']] ) )
			{
				$note_box_data[$row['tvm_num']]['others'][] = $row;
				$note_box_data[$row['tvm_num']]['rows']++;
			}
			else
			{
				$note_box_data[$row['tvm_num']] = $row;
				$note_box_data[$row['tvm_num']]['rows'] = 1;
				$note_box_data[$row['tvm_num']]['others'] = array();
			}
		}
		$note_box_entries = array_values( $note_box_data );

		// Hopper Pullout
		$hopper_category = $Category->get_by_name( 'HopAlloc' );

		$sql = "SELECT
					CONCAT( 'T', LPAD( ints.i, 2, '0' ) ) AS tvm_num,
					allocation.php1_amount, allocation.php5_amount, allocation.allocation_time, allocation.total_replenishment
				FROM ints
				LEFT JOIN
				(
					SELECT a.id, a.assignee,
						SUM( IF( ai.allocated_item_id IN (21, 32) AND ai.allocation_item_type = 1, ip.iprice_unit_price * ai.allocated_quantity, NULL ) ) AS php1_amount,
						SUM( IF( ai.allocated_item_id IN (23, 31) AND ai.allocation_item_type = 1, ip.iprice_unit_price * ai.allocated_quantity, NULL ) ) AS php5_amount,
						SUM( IF( ai.allocated_item_id IN (21, 23, 31, 32) AND ai.allocation_item_type = 1, ip.iprice_unit_price * ai.allocated_quantity, 0 ) ) AS total_replenishment,
						MIN( TIME( ai.allocation_datetime ) ) AS allocation_time
					FROM allocations a
					LEFT JOIN allocation_items ai
						ON ai.allocation_id = a.id
					LEFT JOIN items i
						ON i.id = ai.allocated_item_id
					LEFT JOIN item_prices ip
						ON ip.iprice_item_id = i.id
					WHERE
						business_date = ?
						AND a.store_id = ?
						AND ai.cashier_shift_id = ?
						AND i.item_class = 'cash'
						AND ai.allocation_category_id = ?
					GROUP BY a.id, a.assignee
					ORDER BY ai.allocation_datetime
				) AS allocation
					ON allocation.assignee = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )
				WHERE
					ints.i < 17
					AND allocation.assignee IS NOT NULL
				ORDER BY allocation.assignee ASC, allocation.allocation_time ASC";

		$query = $this->db->query( $sql, array( $business_date, $store_id, $shift_id, $hopper_category->get( 'id' ) ) );
		$hopper_replenishments = $query->result_array();

		$hopper_data = array();
		foreach( $hopper_replenishments as $row )
		{
			if( isset( $hopper_data[$row['tvm_num']] ) )
			{
				$hopper_data[$row['tvm_num']]['others'][] = $row;
				$hopper_data[$row['tvm_num']]['rows']++;
				$hopper_data[$row['tvm_num']]['total_replenishment'] += $row['total_replenishment'];
				$hopper_data[$row['tvm_num']]['php1_reading'] = NULL;
				$hopper_data[$row['tvm_num']]['php5_reading'] = NULL;
			}
			else
			{
				$hopper_data[$row['tvm_num']] = $row;
				$hopper_data[$row['tvm_num']]['rows'] = 1;
				$hopper_data[$row['tvm_num']]['others'] = array();
				$hopper_data[$row['tvm_num']]['php1_reading'] = NULL;
				$hopper_data[$row['tvm_num']]['php5_reading'] = NULL;
			}
		}

		$sql = "SELECT a.tvmr_machine_id,
							SUM(IF(a.tvmr_type = 'hopper_php1', a.tvmr_reading, NULL)) AS php1_reading,
							SUM(IF(a.tvmr_type = 'hopper_php5', a.tvmr_reading, NULL)) AS php5_reading
						FROM tvm_readings a
						LEFT JOIN tvm_readings b
							ON b.tvmr_machine_id = a.tvmr_machine_id
								AND b.tvmr_date = a.tvmr_date
								AND b.tvmr_store_id = a.tvmr_store_id
								AND b.tvmr_shift_id = a.tvmr_shift_id
								AND b.tvmr_type = a.tvmr_type
								AND b.tvmr_time > a.tvmr_time
						WHERE
							a.tvmr_type IN ( 'hopper_php1', 'hopper_php5' )
							AND a.tvmr_date = ?
							AND a.tvmr_store_id = ?
							AND a.tvmr_shift_id = ?
							AND b.id IS NULL
						GROUP BY a.tvmr_machine_id";

		$query = $this->db->query( $sql, array( $business_date, $store_id, $shift_id ) );
		$hopper_readings = $query->result_array();

		foreach( $hopper_readings as $row )
		{
			if( isset( $hopper_data[$row['tvmr_machine_id']] ) )
			{
				$hopper_data[$row['tvmr_machine_id']]['php1_reading'] = number_format( $row['php1_reading'] );
				$hopper_data[$row['tvmr_machine_id']]['php5_reading'] = number_format( $row['php5_reading'] );
			}
			else
			{
				$hopper_data[$row['tvmr_machine_id']]['tvm_num'] = $row['tvmr_machine_id'];
				$hopper_data[$row['tvmr_machine_id']]['php1_amount'] = NULL;
				$hopper_data[$row['tvmr_machine_id']]['php5_amount'] = NULL;
				$hopper_data[$row['tvmr_machine_id']]['allocation_time'] = NULL;
				$hopper_data[$row['tvmr_machine_id']]['total_replenishment'] = NULL;
				$hopper_data[$row['tvmr_machine_id']]['php1_reading'] = number_format( $row['php1_reading'] );
				$hopper_data[$row['tvmr_machine_id']]['php5_reading'] = number_format( $row['php5_reading'] );
				$hopper_data[$row['tvmr_machine_id']]['rows'] = 1;
				$hopper_data[$row['tvmr_machine_id']]['others'] = array();
			}
		}

		$hopper_entries = array_values( $hopper_data );
		$data = array(
			'business_date' => date( 'l, d F Y', strtotime( $business_date ) ),
			'store_name' => $store->get( 'store_name' ),
			'shift_name' => $shift->get( 'description' ),
			'sjt' => $sjt_entries,
			'svc' => $svc_entries,
			'coin_box' => $coin_box_entries,
			'note_box' => $note_box_entries,
			'hopper' => $hopper_entries
		);

		$this->parser->parse( 'reports/container_replacement_report', $data );
	}

	function shift_collection()
	{
		$this->load->library( 'parser' );
		$this->load->library( 'store' );
		$this->load->library( 'shift' );
		$this->load->library( 'category' );
		$Store = new Store();
		$Shift = new Shift();
		$Category = new Category();

		$business_date = param_type( $this->input->get( 'date' ), 'date', time() );
		$store_id = param_type( $this->input->get( 'store' ), 'integer', current_store( TRUE ) );
		$shift_id = param_type( $this->input->get( 'shift' ), 'integer', current_shift( TRUE ) );

		if( empty( $business_date ) ) $business_date = '2017-07-23';
		if( empty( $store_id ) ) $store_id = 11;
		if( empty( $shift_id ) ) $shift_id = 6;

		$store = $Store->get_by_id( $store_id );
		$shift = $Shift->get_by_id( $shift_id );

		// Hopper Pullout
		$hopper_category = $Category->get_by_name( 'HopAlloc' );

		// Sales from TVM
		$sql = "SELECT CONCAT( 'T', LPAD( ints.i, 2, '0' ) ) AS tvm_num,
							(reading.sjt_previous_reading + COALESCE(tkt_sales.sjt_replenishment, 0) - COALESCE(tkt_sales.sjt_reject_bin, 0) - COALESCE(tkt_sales.sjt_excess, 0) - reading.sjt_reading) AS sjt_sold_ticket,
							(reading.svc_previous_reading + COALESCE(tkt_sales.svc_replenishment, 0) - COALESCE(tkt_sales.svc_reject_bin, 0) - COALESCE(tkt_sales.svc_excess, 0) - reading.svc_reading) AS svc_sold_ticket,
							sales.coin_box_sales, sales.note_box_sales,
							(COALESCE(sales.coin_box_sales, 0) + COALESCE(sales.note_box_sales, 0)) AS gross_sales,
							hopper.previous_reading, hopper_alloc.total_replenishment, hopper.reading,
							(COALESCE(hopper.previous_reading, 0) + COALESCE(hopper_alloc.total_replenishment, 0) - COALESCE(hopper.reading, 0)) AS change_fund
						FROM ints
						LEFT JOIN
						(
							SELECT tvmr_machine_id,
								SUM(IF(tvmr_type = 'magazine_sjt', tvmr_reading, NULL)) AS sjt_reading,
								SUM(IF(tvmr_type = 'magazine_svc', tvmr_reading, NULL)) AS svc_reading,
								SUM(IF(tvmr_type = 'magazine_sjt', tvmr_previous_reading, NULL)) AS sjt_previous_reading,
								SUM(IF(tvmr_type = 'magazine_svc', tvmr_previous_reading, NULL)) AS svc_previous_reading
							FROM tvm_readings
							WHERE
								tvmr_type IN ('magazine_sjt', 'magazine_svc')
								AND tvmr_date = ?
								AND tvmr_store_id = ?
								AND tvmr_shift_id = ?
							GROUP BY tvmr_machine_id
						) AS reading
							ON reading.tvmr_machine_id = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )
						LEFT JOIN
						(
							SELECT a.assignee,
								SUM( IF( ai.allocation_item_type = 1 AND i.item_group = 'SJT', IF( ct.id IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor), 0 ) ) AS sjt_replenishment,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'SJT' AND i.item_type = 0, ai.allocated_quantity, 0 ) ) AS sjt_reject_bin,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'SJT' AND i.item_type = 1, ai.allocated_quantity, 0 ) ) AS sjt_excess,
								SUM( IF( ai.allocation_item_type = 3 AND i.item_group = 'SJT', ai.allocated_quantity, 0 ) ) AS sjt_allocation_sold_ticket,
								SUM( IF( ai.allocation_item_type = 1 AND i.item_group = 'SVC', IF( ct.id IS NULL, ai.allocated_quantity, ai.allocated_quantity * ct.conversion_factor), 0 ) ) AS svc_replenishment,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'SVC' AND i.item_type = 0, ai.allocated_quantity, 0 ) ) AS svc_reject_bin,
								SUM( IF( ai.allocation_item_type = 2 AND i.item_group = 'SVC' AND i.item_type = 1, ai.allocated_quantity, 0 ) ) AS svc_excess,
								SUM( IF( ai.allocation_item_type = 3 AND i.item_group = 'SVC', ai.allocated_quantity, 0 ) ) AS svc_allocation_sold_ticket
							FROM allocations a
							LEFT JOIN allocation_items ai ON ai.allocation_id = a.id
							LEFT JOIN items i ON i.id = ai.allocated_item_id
							LEFT JOIN conversion_table ct ON ct.target_item_id = ai.allocated_item_id
							WHERE
								business_date = ?
								AND a.store_id = ?
								AND ai.cashier_shift_id = ?
								AND i.item_class = 'ticket'
								AND i.item_group IN ('SJT', 'SVC')
							GROUP BY a.assignee
						) AS tkt_sales
							ON tkt_sales.assignee = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )

						LEFT JOIN
						(
							SELECT
								tvmr_machine_id,
								SUM(IF(tvmr_type = 'coin_box', tvmr_reading, 0)) AS coin_box_sales,
								SUM(IF(tvmr_type = 'note_box', tvmr_reading, 0)) AS note_box_sales
							FROM tvm_readings
							WHERE
								tvmr_type IN ('coin_box', 'note_box')
								AND tvmr_date = ?
								AND tvmr_store_id = ?
								AND tvmr_shift_id = ?
							GROUP BY tvmr_machine_id
						) AS sales
							ON sales.tvmr_machine_id = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )

						LEFT JOIN
						(
							SELECT tvmr_machine_id,
								SUM(IF(tvmr_type = 'hopper_php5', tvmr_reading * 5, tvmr_reading)) AS reading,
								SUM(IF(tvmr_type = 'hopper_php5', tvmr_previous_reading * 5, tvmr_previous_reading)) AS previous_reading
							FROM tvm_readings
							WHERE
								tvmr_type IN ('hopper_php1', 'hopper_php5')
								AND tvmr_date = ?
								AND tvmr_store_id = ?
								AND tvmr_shift_id = ?
							GROUP BY tvmr_machine_id
						) AS hopper
							ON hopper.tvmr_machine_id = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )

						LEFT JOIN
						(
							SELECT a.assignee,
								SUM( IF( ai.allocated_item_id IN (21, 23, 31, 32) AND ai.allocation_item_type = 1, ip.iprice_unit_price * ai.allocated_quantity, 0 ) ) AS total_replenishment
							FROM allocations a
							LEFT JOIN allocation_items ai
								ON ai.allocation_id = a.id
							LEFT JOIN items i
								ON i.id = ai.allocated_item_id
							LEFT JOIN item_prices ip
								ON ip.iprice_item_id = i.id
							WHERE
								business_date = ?
								AND a.store_id = ?
								AND ai.cashier_shift_id = ?
								AND i.item_class = 'cash'
								AND ai.allocation_category_id = ?
							GROUP BY a.assignee
						) AS hopper_alloc
							ON hopper_alloc.assignee = CONCAT( 'T', LPAD( ints.i, 2, '0' ) )
						WHERE ints.i < 17";

		$query = $this->db->query( $sql, array( $business_date, $store_id, $shift_id,
				$business_date, $store_id, $shift_id,
				$business_date, $store_id, $shift_id,
				$business_date, $store_id, $shift_id,
				$business_date, $store_id, $shift_id, $hopper_category->get( 'id' ) ) );
		$tvm_sales = $query->result_array();
		$tvm_sales_entries = array();
		foreach( $tvm_sales as $row )
		{
			$tvm_sales_entries[] = $row;
		}

		$sql = "SELECT
							a.id AS allocation_id,
							a.assignee,
							alloc_items.sold_sjt,
							alloc_items.sold_svc,
							alloc_items.issued_csc,
							alloc_items.free_exit,
							alloc_items.paid_exit,
							alloc_items.unconfirmed,
							alloc_items.change_fund,
							alloc_sales.gross_sales, alloc_sales.excess_time, alloc_sales.mismatch, alloc_sales.lost_ticket_payment, alloc_sales.other_penalties,
							alloc_sales.tcerf, alloc_sales.other_deductions, alloc_sales.change_fund, alloc_sales.shortage, alloc_sales.overage,
							alloc_sales.short_over,
							COALESCE(alloc_sales.gross_sales, 0) - (COALESCE(alloc_sales.tcerf, 0)) AS net_sales,
							COALESCE(alloc_sales.gross_sales, 0) - (COALESCE(alloc_sales.tcerf, 0)) + COALESCE(alloc_items.change_fund,0) AS total_cash_collection,
							afcs.afcs_total_sales
						FROM allocations AS a

						LEFT JOIN
						(
							SELECT a.id AS allocation_id,
								SUM(IF(ai.allocated_item_id = 1 AND ai.allocation_category_id = 30, ai.allocated_quantity, NULL)) AS sold_sjt,
								SUM(IF(ai.allocated_item_id = 6 AND ai.allocation_category_id = 30, ai.allocated_quantity, NULL)) AS sold_svc,
								SUM(IF(ai.allocated_item_id IN (12,13) AND ai.allocation_category_id = 31, ai.allocated_quantity, NULL)) AS issued_csc,
								SUM(IF(ai.allocated_item_id = 1 AND ai.allocation_category_id = 33, ai.allocated_quantity, NULL)) AS free_exit,
								SUM(IF(ai.allocated_item_id = 1 AND ai.allocation_category_id = 32, ai.allocated_quantity, NULL)) AS paid_exit,
								SUM(IF(ai.allocated_item_id = 1 AND ai.allocation_category_id = 34, ai.allocated_quantity, NULL)) AS unconfirmed,
								SUM(IF(i.item_class = 'cash' AND ai.allocation_category_id = 27, ai.allocated_quantity * ip.iprice_unit_price, NULL )) AS change_fund
							FROM allocations AS a
							LEFT JOIN allocation_items AS ai
								ON ai.allocation_id = a.id
							LEFT JOIN items AS i
								ON i.id = ai.allocated_item_id
							LEFT JOIN item_prices AS ip
								ON ip.iprice_item_id = i.id
							WHERE
								a.business_date = ?
								AND a.store_id = ?
								AND a.assignee_type = 1
								AND ai.cashier_shift_id = ?
								AND ai.allocation_item_type IN (2,3)
							GROUP BY a.id
						) AS alloc_items
							ON alloc_items.allocation_id = a.id

						LEFT JOIN
						(
							SELECT a.id AS allocation_id,
								SUM(IF(asi.alsale_sales_item_id = 1, asi.alsale_amount, NULL)) AS gross_sales,
								SUM(IF(asi.alsale_sales_item_id = 2, asi.alsale_amount, NULL)) AS excess_time,
								SUM(IF(asi.alsale_sales_item_id = 3, asi.alsale_amount, NULL)) AS mismatch,
								SUM(IF(asi.alsale_sales_item_id = 4, asi.alsale_amount, NULL)) AS lost_ticket_payment,
								SUM(IF(asi.alsale_sales_item_id = 5, asi.alsale_amount, NULL)) AS other_penalties,
								SUM(IF(asi.alsale_sales_item_id = 6, asi.alsale_amount, NULL)) AS tcerf,
								SUM(IF(asi.alsale_sales_item_id = 7, asi.alsale_amount, NULL)) AS other_deductions,
								SUM(IF(asi.alsale_sales_item_id = 8, asi.alsale_amount, NULL)) AS change_fund,
								SUM(IF(asi.alsale_sales_item_id = 9, asi.alsale_amount, NULL)) AS shortage,
								SUM(IF(asi.alsale_sales_item_id = 10, asi.alsale_amount, NULL)) AS overage,
								SUM(CASE asi.alsale_sales_item_id WHEN 9 THEN asi.alsale_amount*-1 WHEN 10 THEN asi.alsale_amount ELSE NULL END) AS short_over
							FROM allocations AS a
							LEFT JOIN allocation_sales_items AS asi
								ON asi.alsale_allocation_id = a.id
							WHERE
								a.business_date = ?
								AND a.store_id = ?
								AND a.assignee_type = 1
								AND asi.alsale_shift_id = ?
							GROUP BY a.id
						) AS alloc_sales
							ON alloc_sales.allocation_id = a.id

						LEFT JOIN
						(
							SELECT sdcr.sdcr_allocation_id AS allocation_id,
								SUM( sdcri.sdcri_amount) AS afcs_total_sales
							FROM shift_detail_cash_reports AS sdcr
							LEFT JOIN shift_detail_cash_report_items AS sdcri
								ON sdcri.sdcri_sdcr_id = sdcr.id
							GROUP BY sdcr.sdcr_allocation_id
						) AS afcs
							ON afcs.allocation_id = a.id
						WHERE a.business_date = ?
							AND a.store_id = ?
							AND a.assignee_type = 1
						GROUP BY
							a.id, a.assignee";

		$query = $this->db->query( $sql, array(
				$business_date, $store_id, $shift_id,
				$business_date, $store_id, $shift_id,
				$business_date, $store_id ) );

		$teller_sales = $query->result_array();
		$teller_sales_entries = array();
		foreach( $teller_sales as $row )
		{
			$teller_sales_entries[] = $row;
		}

		$data = array(
			'business_date' => date( 'l, d F Y', strtotime( $business_date ) ),
			'store_name'   => $store->get( 'store_name' ),
			'shift_name'   => $shift->get( 'description' ),
			'tvm_sales'    => $tvm_sales_entries,
			'teller_sales' => $teller_sales_entries
		);

		$this->parser->parse( 'reports/shift_collection_report', $data );
	}
}
