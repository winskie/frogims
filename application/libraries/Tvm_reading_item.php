<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tvm_reading_item extends Base_model
{
	protected $tvmri_reading_id;
	protected $tvmri_name;
	protected $tvmri_reference_num;
	protected $tvmri_quantity;

	protected $date_created_field = 'date_created';
	protected $date_modified_field = 'date_modified';
	protected $created_by_field = 'created_by';
	protected $modified_by_field = 'modified_by';


	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'tvm_reading_items';
		$this->db_fields = array(
			'tvmri_reading_id' => array( 'type' => 'integer' ),
			'tvmri_name' => array( 'type' => 'string' ),
			'tvmri_reference_num' => array( 'type' => 'string' ),
			'tvmri_quantity' => array( 'type' => 'decimal' ),
		);
	}

	public function db_save()
	{
		// There are no pending changes, just return the record
		if( ! $this->db_changes )
		{
			return $this;
		}

		$result = NULL;
		$ci =& get_instance();

		if( $this->_check_data() )
		{
			$ci->db->trans_start();
			$this->_set_default_values();

			if( isset( $this->id ) )
			{
				$this->_update_timestamps( FALSE );
				$ci->db->set( $this->db_changes );

				$result = $this->_db_update();
			}
			else
			{
				$this->_update_timestamps( TRUE );
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
}