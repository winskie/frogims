<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Group extends Base_model {

	protected $group_name;
    protected $is_admin;

	protected $grp_perm_transaction_view; // true | false
	protected $grp_perm_transfer; // none | view | edit
	protected $grp_perm_transfer_approve; //  true | false
	protected $grp_perm_adjustment; // none | view | edit
	protected $grp_perm_adjustment_approve; // true | false
	protected $grp_perm_conversion; // none | view | edit
	protected $grp_perm_conversion_approve; // true | false
	protected $grp_perm_collection; // none | view | edit
	protected $grp_perm_allocation; // none | view | edit
	protected $grp_perm_allocation_allocate; // true | false
	protected $grp_perm_allocation_complete; // true | false

	protected $grp_perm_users; // none | view | edit
	protected $grp_perm_groups; // none | view | edit
	protected $grp_perm_stores; // none | view | edit
	protected $grp_perm_items; // none | view | edit

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'groups';
		$this->db_fields = array(
			'group_name' => array( 'type' => 'string' ),
			'is_admin' => array( 'type' => 'boolean' ),

			'grp_perm_transaction_view' => array( 'type' => 'boolean' ),

			'grp_perm_transfer' => array( 'type' => 'string' ),
			'grp_perm_transfer_approve' => array( 'type' => 'boolean' ),

			'grp_perm_adjustment' => array( 'type' => 'string' ),
			'grp_perm_adjustment_approve' => array( 'type' => 'boolean' ),

			'grp_perm_conversion' => array( 'type' => 'string' ),
			'grp_perm_conversion_approve' => array( 'type' => 'boolean' ),

			'grp_perm_collection' => array( 'type' => 'string' ),

			'grp_perm_allocation' => array( 'type' => 'string' ),
			'grp_perm_adjustment_allocate' => array( 'type' => 'boolean' ),
			'grp_perm_adjustment_complete' => array( 'type' => 'boolean' ),

			'grp_perm_users' => array( 'type' => 'string' ),
			'grp_perm_groups' => array( 'type' => 'string' ),
			'grp_perm_stores' => array( 'type' => 'string' ),
			'grp_perm_items' => array( 'type' => 'string' )
		);
	}
}