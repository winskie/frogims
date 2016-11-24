<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transaction extends Base_model
{

	protected $store_inventory_id;
	protected $transaction_type;
	protected $transaction_datetime;
	protected $transaction_quantity;
	protected $current_quantity;
	protected $transaction_id;
	protected $transaction_item_id;
	protected $transaction_timestamp;
    protected $transaction_shift;

	public function __construct()
	{
		parent::__construct();
		$this->primary_table = 'transactions';
		$this->db_fields = array(
				'store_inventory_id' => array( 'type' => 'integer' ),
				'transaction_type' => array( 'type' => 'integer' ),
				'transaction_datetime' => array( 'type' => 'datetime' ),
				'transaction_quantity' => array( 'type' => 'integer' ),
				'current_quantity' => array( 'type' => 'integer' ),
				'transaction_id' => array( 'type' => 'integer' ),
				'transaction_item_id' => array( 'type' => 'integer' ),
				'transaction_timestamp' => array( 'type' => 'datetime' ),
                'transaction_shift' => array( 'type' => 'integer' )
			);
	}

    public function db_save()
	{
        die( 'You are not allowed to use this function. Use @link Inventory::transact instead.' );
	}
}