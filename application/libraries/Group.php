<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Group extends Base_model {

	protected $group_name;
    protected $is_admin;
	protected $grp_perm_transfer;
	protected $grp_perm_transfer_approve;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'groups';
		$this->db_fields = array(
			'group_name' => array( 'type' => 'string' ),
			'is_admin' => array( 'type' => 'boolean' ),
			'grp_perm_transfer' => array( 'type' => 'string' ),
			'grp_perm_transfer_approve' => array( 'type' => 'boolean' )
		);
	}
}