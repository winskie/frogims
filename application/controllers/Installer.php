<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Installer extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper( 'html' );
	}

	public function index()
	{
		$this->create_default_data( $this->input->get() );
	}

	public function create_tables()
	{
		echo heading( 'Creating tables...', 3 );
		$this->db->query( "CREATE DATABASE IF NOT EXISTS frogims" );
		$this->db->query( "USE frogims" );

		echo 'Creating int table...<br />';
		$this->db->query( "CREATE TABLE ints ( i tinyint ) ENGINE=InnoDB" );

		echo 'Inserting int values...<br />';
		$this->db->query( "INSERT INTO ints VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9)" );

		echo 'Creating dates table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS dates (
					dt DATE NOT NULL PRIMARY KEY,
					y SMALLINT NULL,
					q tinyint NULL,
					m tinyint NULL,
					d tinyint NULL,
					dw tinyint NULL,
					monthName VARCHAR(9) NULL,
					dayName VARCHAR(9) NULL,
					w tinyint NULL,
					isWeekday BOOLEAN NULL DEFAULT NULL,
					isHoliday BOOLEAN NULL DEFAULT NULL,
					holidayDescr VARCHAR(32) NULL,
					locked BOOLEAN DEFAULT FALSE
				)
				ENGINE=InnoDB" );

		echo 'Inserting date values...<br />';
		$this->db->query( "
				INSERT INTO dates (dt)
				SELECT DATE('2015-01-01') + INTERVAL a.i*10000 + b.i*1000 + c.i*100 + d.i*10 + e.i DAY
				FROM ints a JOIN ints b JOIN ints c JOIN ints d JOIN ints e
				WHERE (a.i*10000 + b.i*1000 + c.i*100 + d.i*10 + e.i) <= 11322
				ORDER BY 1" );

		$this->db->query( "
				UPDATE dates
				SET isWeekday = CASE WHEN dayofweek(dt) IN (1,7) THEN 0 ELSE 1 END,
					isHoliday = 0,
					y = YEAR(dt),
					q = quarter(dt),
					m = MONTH(dt),
					d = dayofmonth(dt),
					dw = dayofweek(dt),
					monthname = monthname(dt),
					dayname = dayname(dt),
					w = week(dt)" );

		echo 'Creating stations table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS stations
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					station_name VARCHAR(50) NOT NULL,
					station_short_name VARCHAR(10) NOT NULL,
					PRIMARY KEY (id)
				)
				ENGINE=InnoDB" );

		echo 'Creating shifts table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS shifts
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					shift_num VARCHAR(15) NOT NULL,
					store_type INTEGER NOT NULL,
					shift_start_time TIME NOT NULL,
					shift_end_time TIME NOT NULL,
					description TEXT,
					shift_next_shift_id INTEGER NULL DEFAULT NULL,
					shift_order SMALLINT NOT NULL DEFAULT 1,
					PRIMARY KEY (id)
				)
				ENGINE=InnoDB" );

		echo 'Creating groups table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS groups
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					group_name VARCHAR(100) NOT NULL,
					group_perm_transaction VARCHAR(4) NOT NULL DEFAULT 'none',
					group_perm_shift_turnover VARCHAR(4) NOT NULL DEFAULT 'none',
					group_perm_transfer VARCHAR(4) NOT NULL DEFAULT 'none',
					group_perm_transfer_approve BOOLEAN NOT NULL DEFAULT 0,
					group_perm_transfer_validation VARCHAR(4) NOT NULL DEFAULT 'none',
					group_perm_transfer_validation_complete BOOLEAN NOT NULL DEFAULT 0,
					group_perm_adjustment VARCHAR(4) NOT NULL DEFAULT 'none',
					group_perm_adjustment_approve BOOLEAN NOT NULL DEFAULT 0,
					group_perm_conversion VARCHAR(4) NOT NULL DEFAULT 'none',
					group_perm_conversion_approve BOOLEAN NOT NULL DEFAULT 0,
					group_perm_collection VARCHAR(4) NOT NULL DEFAULT 'none',
					group_perm_allocation VARCHAR(4) NOT NULL DEFAULT 'none',
					group_perm_allocation_allocate BOOLEAN NOT NULL DEFAULT 0,
					group_perm_allocation_complete BOOLEAN NOT NULL DEFAULT 0,
					group_perm_dashboard VARCHAR(255) NULL DEFAULT NULL,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					UNIQUE groups_undx (group_name)
				)
				ENGINE=InnoDB" );

		echo 'Creating users table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS users
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					username VARCHAR(25) NOT NULL,
					full_name VARCHAR(100) NOT NULL,
					position VARCHAR(100) NULL,
					password_hash VARCHAR(50) NOT NULL,
					password_salt VARCHAR(10) NOT NULL,
					user_status SMALLINT NOT NULL DEFAULT 1,
					user_role SMALLINT NOT NULL DEFAULT 2,
					group_id INTEGER NULL,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					UNIQUE users_undx (username),
					FOREIGN KEY users_group_fk (group_id) REFERENCES groups (id)
						ON UPDATE CASCADE
						ON DELETE SET NULL
				)
				ENGINE=InnoDB" );

		echo 'Creating stores table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS stores
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_name VARCHAR(100) NOT NULL,
					store_code VARCHAR(6) NOT NULL,
					store_type INTEGER NOT NULL,
					store_station_id SMALLINT NULL,
					store_location VARCHAR(100) NOT NULL,
					store_contact_number VARCHAR(25) NULL,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id)
				)
				ENGINE=InnoDB" );

		echo 'Creating store users table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS store_users
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					date_joined DATETIME NOT NULL,
					PRIMARY KEY (id),
					UNIQUE (store_id, user_id),
					FOREIGN KEY store_users_store_fk (store_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY store_users_user_fk (user_id) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating sales items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS sales_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					slitem_name VARCHAR(100) NOT NULL,
					slitem_description VARCHAR(255) NOT NULL,
					slitem_group VARCHAR(100) NOT NULL,
					slitem_mode SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id)
				)
				ENGINE=InnoDB" );

		echo 'Creating items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					item_name VARCHAR(100) NOT NULL,
					item_description VARCHAR(255) NULL,
					item_class VARCHAR(20) NOT NULL DEFAULT 'ticket',
					item_unit VARCHAR(20) NULL,
					item_type SMALLINT NOT NULL DEFAULT 1,
					item_group VARCHAR(100) NULL,
					base_item_id INTEGER NULL DEFAULT NULL,
					teller_allocatable BOOLEAN NOT NULL DEFAULT 0,
					teller_remittable BOOLEAN NOT NULL DEFAULT 0,
					teller_saleable BOOLEAN NOT NULL DEFAULT 0,
					machine_allocatable BOOLEAN NOT NULL DEFAULT 0,
					machine_remittable BOOLEAN NOT NULL DEFAULT 0,
					machine_saleable BOOLEAN NOT NULL DEFAULT 0,
					turnover_item BOOLEAN NOT NULL DEFAULT 0,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id)
				)
				ENGINE=InnoDB" );

		echo 'Creating card profiles table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS card_profiles
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					cardp_description VARCHAR(100) NOT NULL,
					cardp_item_id INTEGER DEFAULT NULL,
					PRIMARY KEY( id ),
					FOREIGN KEY cardp_item_fk (cardp_item_id) REFERENCES items(id)
						ON UPDATE CASCADE
						ON DELETE SET NULL
				)
				ENGINE=InnoDB" );

		echo 'Creating store inventory table....<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS store_inventory
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_id INTEGER NOT NULL,
					item_id INTEGER NOT NULL,
					parent_item_id INTEGER NULL DEFAULT NULL,
					quantity DECIMAL(15,2) NOT NULL DEFAULT 0.00,
					quantity_timestamp DATETIME NOT NULL,
					buffer_level DECIMAL(15,2) NOT NULL DEFAULT 0.00,
					reserved DECIMAL(15,2) NOT NULL DEFAULT 0.00,
					PRIMARY KEY (id),
					UNIQUE store_inventory_undx (store_id, item_id, parent_item_id),
					FOREIGN KEY store_inventory_store_fk (store_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY store_inventory_item_fx (item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY store_inventory_parent_item_fx (parent_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating shift turnovers table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS shift_turnovers
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					st_store_id INTEGER NOT NULL,
					st_from_date DATE NOT NULL,
					st_from_shift_id INTEGER NOT NULL,
					st_to_date DATE NULL DEFAULT NULL,
					st_to_shift_id INTEGER NULL DEFAULT NULL,
					st_start_user_id INTEGER NULL DEFAULT NULL,
					st_end_user_id INTEGER NULL DEFAULT NULL,
					st_remarks TEXT,
					st_status SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY st_start_user_fk ( st_start_user_id ) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY st_end_user_fk ( st_end_user_id ) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					UNIQUE st_from_undx (st_store_id, st_from_date, st_from_shift_id),
					UNIQUE st_to_undx (st_store_id, st_to_date, st_to_shift_id)
				)
				ENGINE=InnoDB" );

		echo 'Creating shift turnover items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS shift_turnover_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					sti_turnover_id INTEGER NOT NULL,
					sti_item_id INTEGER NOT NULL,
					sti_inventory_id INTEGER NOT NULL,
					sti_beginning_balance DECIMAL(15,2) NULL DEFAULT NULL,
					sti_ending_balance DECIMAL(15,2) NULL DEFAULT NULL,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY sti_shift_turnover_fk (sti_turnover_id) REFERENCES shift_turnovers (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating transactions table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS transactions
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_inventory_id INTEGER NOT NULL,
					transaction_type SMALLINT NOT NULL,
					transaction_date DATE NOT NULL,
					transaction_datetime DATETIME NOT NULL,
					transaction_shift INTEGER NOT NULL,
					transaction_category_id INTEGER NULL DEFAULT NULL,
					transaction_quantity DECIMAL(15,2) NOT NULL DEFAULT 0,
					current_quantity DECIMAL(15,2) NOT NULL,
					transaction_id INTEGER NOT NULL,
					transaction_item_id INTEGER NULL DEFAULT NULL,
					transaction_timestamp DATETIME NOT NULL,
					PRIMARY KEY (id),
					INDEX transactions_main_ndx (transaction_datetime, transaction_type),
					INDEX transactions_shift_ndx (transaction_date, transaction_shift, transaction_type),
					FOREIGN KEY transactions_store_inventory_fk (store_inventory_id) REFERENCES store_inventory (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating adjustments table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS adjustments
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_inventory_id INTEGER NOT NULL,
					adjustment_shift INTEGER NOT NULL,
					adjustment_type SMALLINT NULL,
					adjusted_quantity INTEGER NOT NULL,
					previous_quantity INTEGER NULL,
					reason VARCHAR(255) NOT NULL,
					adjustment_status SMALLINT NOT NULL DEFAULT 1,
					adjustment_timestamp DATETIME NOT NULL,
					user_id INTEGER NOT NULL,
					adj_transaction_type SMALLINT NULL DEFAULT NULL,
					adj_transaction_id INTEGER NULL DEFAULT NULL,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY adjustments_store_inventory_fk (store_inventory_id) REFERENCES store_inventory (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY adjustments_user_fk (user_id) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating adjustment status log table... <br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS adjustment_status_log
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					adjlog_adjustment_id INTEGER NOT NULL,
					adjlog_user_id INTEGER NOT NULL,
					adjlog_status SMALLINT NOT NULL,
					adjlog_timestamp TIMESTAMP,
					PRIMARY KEY (id),
					FOREIGN KEY adjlog_adjustment_fk (adjlog_adjustment_id) REFERENCES adjustments (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating transfers table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS transfers
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					transfer_reference_num VARCHAR(100) NULL,
					transfer_category INTEGER NOT NULL DEFAULT 1,
					origin_id INTEGER NULL,
					origin_name VARCHAR(100) NULL,
					sender_id INTEGER NULL,
					sender_name VARCHAR(100) NULL,
					sender_shift INTEGER NULL,
					transfer_datetime DATETIME NOT NULL,
					transfer_user_id INTEGER NULL,
					destination_id INTEGER NULL,
					destination_name VARCHAR(100) NULL,
					recipient_id INTEGER NULL,
					recipient_name VARCHAR(100) NULL,
					recipient_shift INTEGER NULL,
					receipt_datetime DATETIME NULL,
					receipt_user_id INTEGER NULL,
					transfer_status SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY transfers_origin_fk (origin_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE SET NULL,
					FOREIGN KEY transfers_destination_fk (destination_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE SET NULL,
					FOREIGN KEY transfers_origin_user_fk (sender_id) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE SET NULL,
					FOREIGN KEY transfers_destination_user_fk (recipient_id) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE SET NULL,
					FOREIGN KEY transfers_transfer_user_fk (transfer_user_id) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE SET NULL,
					FOREIGN KEY transfers_receipt_user_fk (receipt_user_id) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE SET NULL,
					INDEX transfer_date_status_ndx ( transfer_datetime, transfer_status )
				)
				ENGINE=InnoDB" );

		echo 'Creating transfer items table... <br />';
		$this->db->query("
				CREATE TABLE IF NOT EXISTS transfer_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					transfer_id INTEGER NOT NULL,
					item_id INTEGER NOT NULL,
					transfer_item_category_id INTEGER NULL DEFAULT NULL,
					quantity INTEGER NOT NULL DEFAULT 0,
					quantity_received INTEGER NULL DEFAULT NULL,
					remarks TEXT NULL DEFAULT NULL,
					transfer_item_status SMALLINT NOT NULL DEFAULT 1,
					transfer_item_allocation_item_id INTEGER NULL DEFAULT NULL,
					transfer_item_transfer_item_id INTEGER NULL DEFAULT NULL,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY transfer_items_transfer_fk (transfer_id) REFERENCES transfers (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					INDEX transfer_items_allocation_item_ndx ( transfer_item_allocation_item_id ),
					INDEX transfer_items_transfer_item_ndx ( transfer_item_transfer_item_id )
				)
				ENGINE=InnoDB" );

		echo 'Creating transfer status log table... <br />';
		$this->db->query("
				CREATE TABLE IF NOT EXISTS transfer_status_log
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					tslog_transfer_id INTEGER NOT NULL,
					tslog_user_id INTEGER NOT NULL,
					tslog_status SMALLINT NOT NULL,
					tslog_timestamp TIMESTAMP,
					PRIMARY KEY (id),
					FOREIGN KEY tslog_transfer_fk (tslog_transfer_id) REFERENCES transfers (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating transfer validations table... <br />';
		$this->db->query("
				CREATE TABLE IF NOT EXISTS transfer_validations
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					transval_transfer_id INTEGER NOT NULL,
					transval_receipt_status SMALLINT NULL DEFAULT NULL,
					transval_receipt_datetime DATETIME NULL DEFAULT NULL,
					transval_receipt_sweeper VARCHAR(100) NULL DEFAULT NULL,
					transval_receipt_user_id INTEGER NULL DEFAULT NULL,
					transval_receipt_shift_id INTEGER NULL DEFAULT NULL,
					transval_transfer_status SMALLINT NULL DEFAULT NULL,
					transval_transfer_datetime DATETIME NULL DEFAULT NULL,
					transval_transfer_sweeper VARCHAR(100) NULL DEFAULT NULL,
					transval_transfer_user_id INTEGER NULL DEFAULT NULL,
					transval_transfer_shift_id INTEGER NULL DEFAULT NULL,
					transval_status SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY transval_items_transfer_fk (transval_transfer_id) REFERENCES transfers (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating conversion table table... <br />';
		$this->db->query("
				CREATE TABLE IF NOT EXISTS conversion_table
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					source_item_id INTEGER NOT NULL,
					target_item_id INTEGER NOT NULL,
					conversion_factor INTEGER NOT NULL,
					PRIMARY KEY (id),
					INDEX conversion_table_main_ndx (target_item_id, source_item_id),
					FOREIGN KEY conversion_table_source_fk (source_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY conversion_table_target_fk (target_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating conversions table... <br />';
		$this->db->query("
				CREATE TABLE IF NOT EXISTS conversions
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_id INTEGER NOT NULL,
					conversion_datetime DATETIME NOT NULL,
					conversion_shift INTEGER NOT NULL,
					source_inventory_id INTEGER NOT NULL,
					target_inventory_id INTEGER NOT NULL,
					source_quantity INTEGER NOT NULL,
					target_quantity INTEGER NOT NULL,
					remarks TEXT NULL DEFAULT NULL,
					conversion_status SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY conversions_store_fk (store_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY conversions_source_fk (source_inventory_id) REFERENCES store_inventory (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY conversions_target_fk (target_inventory_id) REFERENCES store_inventory (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating conversions table... <br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS conversion_status_log
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					convlog_conversion_id INTEGER NOT NULL,
					convlog_user_id INTEGER NOT NULL,
					convlog_status SMALLINT NOT NULL,
					convlog_timestamp TIMESTAMP,
					PRIMARY KEY (id),
					FOREIGN KEY convlog_conversion_fk (convlog_conversion_id) REFERENCES conversions (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating allocations table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS allocations
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_id INTEGER NOT NULL,
					business_date DATE NOT NULL,
					shift_id INTEGER NOT NULL,
					station_id SMALLINT NULL,
					assignee VARCHAR(50) NULL,
					assignee_type SMALLINT NOT NULL,
					allocation_status SMALLINT NOT NULL DEFAULT 1,
					cashier_id INTEGER NOT NULL,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY allocations_store_fk (store_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					INDEX allocations_main_ndx (business_date, shift_id, station_id)
				)
				ENGINE=InnoDB" );

		echo 'Creating allocation_items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS allocation_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					allocation_id INTEGER NOT NULL,
					cashier_shift_id INTEGER NOT NULL,
					cashier_id INTEGER NOT NULL,
					allocated_item_id INTEGER NOT NULL,
					allocated_quantity INTEGER NOT NULL,
					allocation_category_id INTEGER NOT NULL,
					allocation_datetime DATETIME NOT NULL,
					allocation_item_status SMALLINT NOT NULL DEFAULT 1,
					allocation_item_type SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY allocation_items_fk (allocation_id) REFERENCES allocations (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY allocation_items_item_fk (allocated_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					INDEX allocation_items_type_ndx (allocation_id, allocation_item_type)
				)
				ENGINE=InnoDB" );

		echo 'Creating allocation_sales_items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS allocation_sales_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					alsale_allocation_id INTEGER NOT NULL,
					alsale_shift_id INTEGER NOT NULL,
					alsale_cashier_id INTEGER NOT NULL,
					alsale_sales_item_id INTEGER NOT NULL,
					alsale_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
					alsale_remarks VARCHAR(255) NULL,
					alsale_sales_item_status SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY alsale_allocation_fk (alsale_allocation_id) REFERENCES allocations (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY alsale_sales_items_fk (alsale_sales_item_id) REFERENCES sales_items (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating allocation status log table... <br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS allocation_status_log
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					alloclog_allocation_id INTEGER NOT NULL,
					alloclog_user_id INTEGER NOT NULL,
					alloclog_status SMALLINT NOT NULL,
					alloclog_timestamp TIMESTAMP,
					PRIMARY KEY (id),
					FOREIGN KEY alloclog_allocation_fk (alloclog_allocation_id) REFERENCES allocations (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating mopping table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS mopping
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_id INTEGER NOT NULL,
					processing_datetime DATETIME NOT NULL,
					business_date DATE NOT NULL,
					shift_id INTEGER NOT NULL,
					cashier_shift_id INTEGER NOT NULL,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY mopping_store_fk (store_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					INDEX mopping_main_ndx (business_date, shift_id)
				)
				ENGINE=InnoDB" );

		echo 'Creating mopping_items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS mopping_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					mopping_id INTEGER NOT NULL,
					mopped_station_id SMALLINT NOT NULL,
					mopped_item_id INTEGER NOT NULL,
					mopped_quantity INTEGER NOT NULL DEFAULT 0,
					mopped_base_quantity INTEGER NOT NULL DEFAULT 0,
					converted_to INTEGER NULL,
					group_id INTEGER NULL DEFAULT NULL,
					mopping_item_status SMALLINT NOT NULL DEFAULT 1,
					processor_id INTEGER NOT NULL,
					delivery_person VARCHAR(100) NOT NULL,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY mopping_items_fk (mopping_id) REFERENCES mopping (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY mopping_items_item_fk (mopped_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating categories table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS categories
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					cat_name VARCHAR(10) NOT NULL,
					cat_description VARCHAR(100) NOT NULL,
					cat_module VARCHAR(100) NOT NULL,
					cat_cash BOOLEAN NOT NULL DEFAULT 0,
					cat_ticket BOOLEAN NOT NULL DEFAULT 0,
					cat_teller BOOLEAN NULL DEFAULT NULL,
					cat_machine BOOLEAN NULL DEFAULT NULL,
					cat_status SMALLINT NOT NULL DEFAULT 1,
					PRIMARY KEY (id),
					UNIQUE cat_name_udx (cat_name)
				)
				ENGINE=InnoDB;" );

		echo 'Creating item categories table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS item_categories
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					ic_item_id INTEGER NOT NULL,
					ic_category_id INTEGER NOT NULL,
					PRIMARY KEY (id),
					UNIQUE ic_udx (ic_item_id, ic_category_id),
					FOREIGN KEY ic_item_fk (ic_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY ic_category_fk (ic_category_id) REFERENCES categories (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
					)
					ENGINE=InnoDB;" );

		echo 'Creating item prices table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS item_prices
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					iprice_item_id INTEGER NOT NULL,
					iprice_currency VARCHAR(5) NOT NULL DEFAULT 'PHP',
					iprice_unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					UNIQUE iprice_currency_udx (iprice_item_id, iprice_currency),
					FOREIGN KEY iprice_item_fk (iprice_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating TVM readings table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS tvm_readings
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					tvmr_store_id INTEGER NOT NULL,
					tvmr_machine_id VARCHAR(50) NOT NULL,
					tvmr_datetime DATETIME NOT NULL,
					tvmr_shift_id INTEGER NOT NULL,
					tvmr_cashier_id INTEGER NOT NULL,
					tvmr_cashier_name VARCHAR(100) NOT NULL,
					tvmr_last_reading BOOLEAN NOT NULL DEFAULT 0,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY tvmr_store_fk (tvmr_store_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY tvmr_shift_fk (tvmr_shift_id) REFERENCES shifts (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY tvmr_cashier_fk (tvmr_cashier_id) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating TVM reading items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS tvm_reading_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					tvmri_reading_id INTEGER NOT NULL,
					tvmri_name VARCHAR(100) NOT NULL,
					tvmri_reference_num VARCHAR(15) NULL,
					tvmri_quantity DECIMAL(15,2) NOT NULL DEFAULT 0.00,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					UNIQUE tvmri_reading_udx (tvmri_reading_id, tvmri_name),
					FOREIGN KEY tvmri_reading_fk (tvmri_reading_id) REFERENCES tvm_readings (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Creating shift detail cash reports table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS shift_detail_cash_reports
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					sdcr_allocation_id INTEGER DEFAULT NULL,
					sdcr_store_id INTEGER NOT NULL,
					sdcr_shift_id INTEGER NOT NULL,
					sdcr_teller_id INTEGER NOT NULL,
					sdcr_pos_id SMALLINT NOT NULL,
					sdcr_business_date DATE NOT NULL,
					sdcr_login_time DATETIME NOT NULL,
					sdcr_logout_time DATETIME NOT NULL,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY sdcr_store_fk (sdcr_store_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY sdcr_shift_fk (sdcr_shift_id) REFERENCES shifts (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating shift detail cash report items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS shift_detail_cash_report_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					sdcri_sdcr_id INTEGER NOT NULL,
					sdcri_card_profile_id SMALLINT NOT NULL,
					sdcri_property VARCHAR(10) NOT NULL,
					sdcri_quantity INTEGER NOT NULL DEFAULT 0,
					sdcri_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					created_by INTEGER NOT NULL,
					modified_by INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY sdcri_report_fk (sdcri_sdcr_id) REFERENCES shift_detail_cash_reports (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE
				)
				ENGINE=InnoDB" );

		echo 'Done with creating database tables.';
	}

	public function create_default_data( $params = array() )
	{
		echo heading( 'Creating default data', 3 );
		$this->db->trans_start();
		$current_shift_id = $this->session->current_shift_id;

		// Temporary set current shift
		$this->session->current_shift_id = 1;

		if( array_key_exists( 'mode', $params ) && $params['mode'] == 'transactions' )
		{
			$this->load->library( 'store' );
			$this->load->library( 'item' );

			// Creating default inventory
			echo 'Creating store inventories...';
			$test_inventory = param( $params, 'test_inventory' );
			flush();
			$this->load->library( 'Inventory' );
			$Store = new Store();
			$stores = $Store->get_stores();
			$items = new Item();
			$items = $items->get_items();

			/*
			$store = $Store->get_by_id( 1 );
			foreach( $items as $item )
			{
				$inventory = $store->add_item( $item );
				$quantity = 0;
				switch( $item->get( 'item_name' ) )
				{
					case 'L2 SJT - Rigid Box':
						if( $test_inventory ) $quantity = rand(10, 50);
						break;

					case 'L2 SJT - Ticket Magazine':
						if( $test_inventory ) $quantity = rand(1, 8);
						break;

					case 'SVC - Rigid Box':
						if( $test_inventory ) $quantity = rand(1, 8);
						break;

					default:
						switch( $item->get( 'item_group' ) )
						{
							case 'SJT':
								if( $test_inventory ) $quantity = rand(5, 50);
								break;

							default:
								if( $test_inventory ) $quantity = rand(0, 5);
						}
				}
				$inventory->transact( TRANSACTION_INIT, $quantity, date( TIMESTAMP_FORMAT ), 0 );
			}
			*/

			foreach( $stores as $store )
			{
				foreach( $items as $item )
				{
					$inventory = $store->add_item( $item );
					$quantity = 0;
					switch( $item->get( 'item_name' ) )
					{
						case 'L2 SJT - Rigid Box':
							if( $test_inventory ) $quantity = rand(10, 50);
							break;

						case 'L2 SJT - Ticket Magazine':
							if( $test_inventory ) $quantity = rand(1, 8);
							break;

						case 'SVC - Rigid Box':
							if( $test_inventory ) $quantity = rand(1, 8);
							break;

						default:
							switch( $item->get( 'item_group' ) )
							{
								case 'SJT':
									if( $test_inventory ) $quantity = rand(5, 50);
									break;

								default:
									if( $test_inventory ) $quantity = rand(0, 5);
							}
					}
					$inventory->transact( TRANSACTION_INIT, $quantity, date( TIMESTAMP_FORMAT ), 0 );
				}
			}
			echo 'OK<br />';
			flush();
		}
		else
		{
			// Create system administrator group
			echo 'Creating default system administrator group...';
			flush();
			$this->load->library( 'group' );
			$admin_Group = new Group();
			$admin_Group->set( 'group_name', 'System Administrators' );

			$admin_Group->set( 'group_perm_transaction', 'view' );
			$admin_Group->set( 'group_perm_shift_turnover', 'edit' );
			$admin_Group->set( 'group_perm_transfer_validation', 'edit' );
			$admin_Group->set( 'group_perm_transfer_validation_complete', true );
			$admin_Group->set( 'group_perm_transfer', 'edit' );
			$admin_Group->set( 'group_perm_transfer_approve', true );
			$admin_Group->set( 'group_perm_adjustment', 'edit' );
			$admin_Group->set( 'group_perm_adjustment_approve', true );
			$admin_Group->set( 'group_perm_conversion', 'edit' );
			$admin_Group->set( 'group_perm_conversion_approve', true );
			$admin_Group->set( 'group_perm_collection', 'edit' );
			$admin_Group->set( 'group_perm_allocation', 'edit' );
			$admin_Group->set( 'group_perm_allocation_allocate', true );
			$admin_Group->set( 'group_perm_allocation_complete', true );
			$admin_Group->set( 'group_perm_dashboard', 'history,inventory,distribution' );
			$admin_Group = $admin_Group->db_save();
			echo 'OK<br />';
			flush();

			// Create admin user
			echo 'Creating default admin user...';
			flush();
			$this->load->library( 'user' );
			$admin_User = new User();
			$admin_User->set( 'username', 'admin' );
			$admin_User->set( 'full_name', 'System Administrator' );
			$admin_User->set( 'position', 'System Administrator' );
			$admin_User->set( 'user_status', 1 ); // active
			$admin_User->set( 'user_role', 1 ); // administrator
			$admin_User->set( 'group_id', $admin_Group->get( 'id' ) );
			$admin_User->set( 'created_by', 1 );
			$admin_User->set( 'modified_by', 1 );
			$admin_User->set_password( 'admin' );
			$admin_User->db_save();

			echo 'OK<br />';
			flush();

			// Create default stations
			echo 'Creating default stations...';
			flush();
			$this->db->query( 'INSERT INTO stations (id, station_name, station_short_name )
							VALUES
									( 1, "Recto", "RTO" ),
									( 2, "Legarda", "LGD" ),
									( 3, "Pureza", "PRZ" ),
									( 4, "V.Mapa", "VMP" ),
									( 5, "J.Ruiz", "JRZ" ),
									( 6, "Gilmore", "GLM" ),
									( 7, "Betty-Go - Belmonte", "BGB" ),
									( 8, "Araneta Center - Cubao", "ACC" ),
									( 9, "Anonas", "ANN" ),
									( 10, "Katipunan", "KTP" ),
									( 11, "Santolan", "STL" )' );
			echo 'OK<br />';
			flush();

			// Create default shifts
			echo 'Creating default shifts...';
			flush();
			$this->load->library( 'shift' );
			$shifts = array(
							array( 'Regular Shift', 1, 'Regular shift', '00:00:00', '23:59:59', NULL, 1 ), // id: 1
							array( 'Prod S1', 2, 'Production Shift 1', '07:00:00', '14:59:59', 3, 1  ), // id: 2
							array( 'Prod S2', 2, 'Production Shift 2', '13:00:00', '20:59:59', 2, 2 ), // id: 3
							array( 'TGM S1', 3, 'Transport Shift 1', '06:00:00', '13:59:59', 5, 1 ), // id: 4
							array( 'TGM S2', 3, 'Transport Shift 2', '14:00:00', '21:59:59', 4, 2 ), // id: 5
							array( 'Cashier S1', 4, 'Cashier Shift 1', '06:00:00', '13:59:59', 7, 1 ), // id: 6
							array( 'Cashier S2', 4, 'Cashier Shift 2', '14:00:00', '21:59:59', 8, 2 ), // id: 7
							array( 'Cashier S3', 4, 'Cashier Shift 3', '22:00:00', '05:59:59', 6, 3 ), // id: 8
							array( 'Teller S1', 0, 'Teller Shift 1', '06:00:00', '13:59:59', 10, 1 ), // id: 9
							array( 'Teller S2', 0, 'Teller Shift 2', '14:00:00', '21:59:59', 11, 2 ), // id: 10
							array( 'Teller S3', 0, 'Teller Shift 3', '22:00:00', '05:59:59', 9, 3 ) // id: 11
					);

			foreach( $shifts as $s )
			{
					$shift = new Shift();
					$shift->set( 'shift_num', $s[0] );
					$shift->set( 'store_type', $s[1] );
					$shift->set( 'description', $s[2] );
					$shift->set( 'shift_start_time', $s[3] );
					$shift->set( 'shift_end_time', $s[4] );
					$shift->set( 'shift_next_shift_id', $s[5] );
					$shift->set( 'shift_order', $s[6] );
					$shift->db_save();
					unset( $shift );
			}
			echo 'OK<br />';
			flush();

			// Create default stores
			echo 'Creating default stores...';
			flush();
			$this->load->library( 'Store' );
			$stores = array(
					array( 'Line 2 Depot', 'Line 2 Depot', 'TIMD', 1, NULL ),
					array( 'SASCU', 'Line 2 Depot', 'SASCU', 1, NULL ),
					array( 'TVM and Gates Management', 'Anonas Station', 'TGM', 3, 9 ),
					array( 'Ticket Production', 'J.Ruiz Station', 'TIMS', 2, 5 ),
					array( 'TASCU East', 'Anonas Station', 'TASCE', 1, 9 ),
					array( 'TASCU West', 'J.Ruiz Station', 'TASCW', 1, 5 ),
					array( 'Recto Cashroom', 'Recto Station', 'RCT', 4, 1 ),
					array( 'Legarda Cashroom', 'Legarda Station', 'LGRD', 4, 2 ),
					array( 'Pureza Cashroom', 'Pureza Station', 'PRZ', 4, 3 ),
					array( 'V.Mapa Cashroom', 'V.Mapa Station', 'VMP', 4, 4 ),
					array( 'J.Ruiz Cashroom', 'J.Ruiz Station', 'JRZ', 4, 5 ),
					array( 'Gilmore Cashroom', 'Gilmore Station', 'GLMR', 4, 6 ),
					array( 'Betty Go - Belmonte Cashroom', 'Betty Go - Belmonte Station', 'BTYG', 4, 7 ),
					array( 'Araneta Center - Cubao Cashroom', 'Araneta Center - Cubao Station', 'ACCB', 4, 8 ),
					array( 'Anonas Cashroom', 'Anonas Station', 'ANNS', 4, 9 ),
					array( 'Katipunan Cashroom', 'Katipunan  Station', 'KTPN', 4, 10 ),
					array( 'Santolan Cashroom', 'Santolan Station', 'STLN', 4, 11 )
				);

			foreach( $stores as $s )
			{
				$store = new Store();
				$store->set( 'store_name', $s[0] );
				$store->set( 'store_location', $s[1] );
				$store->set( 'store_code', $s[2] );
				$store->set( 'store_type', $s[3] );
				$store->set( 'store_station_id', $s[4] );
				$store->db_save();

				// Add admin user to store
				$store->add_member( $admin_User );
				unset( $store );
			}
			echo 'OK<br />';
			flush();

			// Adding admin user to first store
			/*
			echo 'Adding admin user to first store...';
			flush();
			$this->load->library( 'store' );
			$store = new Store();
			$st_depot = $store->get_by_id( 1 );
			$st_depot->add_member( $admin_User );
			echo 'OK<br />';
			*/
			flush();

			// Create default sales items
			echo 'Creating default sales items...';
			flush();
			$this->load->library( 'Sales_item' );
			$sales_items = array(
					array(
						'slitem_name' => 'Gross Sales',
						'slitem_description' => 'Gross Sales',
						'slitem_group' => '',
						'slitem_mode' => 1 ),
					array(
						'slitem_name' => 'Excess Time',
						'slitem_description' => 'Excess Time',
						'slitem_group' => 'Penalties',
						'slitem_mode' => 1 ),
					array(
						'slitem_name' => 'Mismatch',
						'slitem_description' => 'Mismatch',
						'slitem_group' => 'Penalties',
						'slitem_mode' => 1 ),
					array(
						'slitem_name' => 'Payment for Lost Ticket',
						'slitem_description' => 'Payment for Lost Ticket',
						'slitem_group' => 'Penalties',
						'slitem_mode' => 1 ),
					array(
						'slitem_name' => 'Other Penalties',
						'slitem_description' => 'Other Penalties',
						'slitem_group' => 'Penalties',
						'slitem_mode' => 1 ),
					array(
						'slitem_name' => 'TCERF',
						'slitem_description' => 'TCERF',
						'slitem_group' => 'Deductions',
						'slitem_mode' => 0 ),
					array(
						'slitem_name' => 'Other Deductions',
						'slitem_description' => 'Other Deductions',
						'slitem_group' => 'Deductions',
						'slitem_mode' => 0 ),
					array(
						'slitem_name' => 'Change Fund',
						'slitem_description' => 'Change Fund',
						'slitem_group' => 'Allocation',
						'slitem_mode' => 1 ),
					array(
						'slitem_name' => 'Shortage',
						'slitem_description' => 'Shortage',
						'slitem_group' => 'Short Over',
						'slitem_mode' => 0 ),
					array(
						'slitem_name' => 'Overage',
						'slitem_name' => 'Overage',
						'slitem_group' => 'Short Over',
						'slitem_mode' => 1 ),
				);

			foreach( $sales_items as $si )
			{
				$item = new Sales_item();
				$item->load_from_data( $si, TRUE );
				$item->db_save();
				unset( $item );
			}
			echo 'OK<br />';
			flush();

			// Create default items
			echo 'Creating default items...';
			flush();
			$this->load->library( 'Item' );
			$items = array(
					array( 'L2 SJT', 'Line 2 Single Journey Ticket', NULL, 0, 1, 0, 1, 'SJT', 'piece', 1, 1, 'ticket', TRUE, TRUE ), // ID: 1
					array( 'L2 SJT - Rigid Box', 'Line 2 Single Journey Ticket in Rigid Box in 50s', 1, 1, 1, 0, 0, 'SJT', 'box', 0, 1, 'ticket', FALSE, FALSE ),
					array( 'L2 SJT - Ticket Magazine', 'Line 2 Single Journey Ticket in Ticket Magazine in 800s', 1, 0, 0, 1, 0, 'SJT', 'magazine', 0, 1, 'ticket', FALSE, FALSE ),
					array( 'L2 SJT - Defective', 'Defective Line 2 Single Journey Ticket', 1, 0, 1, 0, 1, 'SJT', 'piece', 1, 0, 'ticket', FALSE, FALSE ),
					array( 'L2 SJT - Damaged', 'Damaged Line 2 Single Journey Ticket', 1, 0, 1, 0, 1, 'SJT', 'piece', 1, 0, 'ticket', FALSE, FALSE ),

					array( 'SVC', 'Stored Value Card', NULL, 0, 1, 0, 1, 'SVC', 'piece', 1, 1, 'ticket', TRUE, TRUE ), // ID: 6
					array( 'SVC - Rigid Box', 'Stored Value Ticket in Rigid Box in 10s', 6, 1, 1, 0, 0, 'SVC', 'box', 0, 1, 'ticket', FALSE, FALSE ),
					array( 'SVC - 25', 'Stored Value Ticket in 25s', 6, 1, 1, 0, 0, 'SVC', 'box', 0, 1, 'ticket', FALSE, FALSE ),
					array( 'SVC - 150', 'Stored Value Ticket in 150s', 6, 0, 0, 1, 0, 'SVC', 'box', 0, 1, 'ticket', FALSE, FALSE ),
					array( 'SVC - Defective', 'Defective Stored Value Card', 6, 0, 1, 0, 1, 'SVC', 'piece', 1, 0, 'ticket', FALSE, FALSE ),
					array( 'SVC - Damaged', 'Damaged Stored Value Card', 6, 0, 1, 0, 1, 'SVC', 'piece', 1, 0, 'ticket', FALSE, FALSE ),

					array( 'Senior', 'Senior Citizen Stored Value Card', NULL, 1, 0, 0, 0, 'Concessionary', 'piece', 1, 1, 'ticket', TRUE, FALSE ), // ID: 12
					array( 'PWD', 'Passenger with Disability Store Value Card', NULL, 1, 0, 0, 0, 'Concessionary', 'piece', 1, 1, 'ticket', TRUE, FALSE ), // ID: 13
					array( 'Senior - Defective', 'Defective Senior Citizen Stored Value Card', 12, 0, 0, 0, 0, 'Concessionary', 'piece', 1, 0, 'ticket', FALSE, FALSE ),
					array( 'PWD - Defective', 'Defective - Passenger with Disability Store Value Card', 13, 0, 0, 0, 0, 'Concessionary', 'piece', 1, 0, 'ticket', FALSE, FALSE ),

					array( 'L2 Ticket Coupon', 'Line 2 Ticket Coupon', NULL, 1, 1, 0, 0, 'Coupon', 'piece', 0, 0, 'ticket', TRUE, FALSE ),

					array( 'Others', 'Other Cards', NULL, 0, 1, 0, 0, 'Others', 'piece', 1, 0, 'ticket', FALSE, FALSE ), // ID: 17
					array( 'L1 SJT', 'Line 1 Single Journey Ticket', 17, 0, 1, 0, 0, 'Others', 'piece', 1, 0, 'ticket', FALSE, FALSE ),
					array( 'MRT SJT', 'Line 3 Single Journey Ticket', 17, 0, 1, 0, 0, 'Others', 'piece', 1, 0, 'ticket', FALSE, FALSE ),
					array( 'Staff Card', 'Staff Card', 17, 0, 0, 0, 0, 'Others', 'piece', 1, 0, 'ticket', FALSE, FALSE ),

					array( 'Php1 Coin', '1 peso coin', NULL, 1, 1, 1, 1, 'coin', 'piece', 0, 1, 'cash', FALSE, FALSE ), // ID: 21
					array( 'Php0.25 Coin', '25 centavos coin', 21, 1, 1, 0, 1, 'coin', 'piece', 0, 1, 'cash', FALSE, FALSE ),
					array( 'Php5 Coin', '5 pesos coin', 21, 1, 1, 1, 1, 'coin', 'piece', 0, 1, 'cash', FALSE, FALSE ), // ID: 23
					array( 'Php10 Coin', '10 pesos coin', 21, 1, 1, 0, 1, 'coin', 'piece', 0, 1, 'cash', FALSE, FALSE ),

					array( 'Php20 Bill', '20 pesos bill', 21, 1, 1, 0, 1, 'bill', 'piece', 0, 1, 'cash', FALSE, FALSE ),
					array( 'Php50 Bill', '50 pesos bill', 21, 1, 1, 0, 1, 'bill', 'piece', 0, 1, 'cash', FALSE, FALSE ),
					array( 'Php100 Bill', '100 pesos bill', 21, 1, 1, 0, 1, 'bill', 'piece', 0, 1, 'cash', FALSE, FALSE ),
					array( 'Php200 Bill', '200 pesos bill', 21, 1, 1, 0, 1, 'bill', 'piece', 0, 1, 'cash', FALSE, FALSE ),
					array( 'Php500 Bill', '500 pesos bill', 21, 1, 1, 0, 1, 'bill', 'piece', 0, 1, 'cash', FALSE, FALSE ),
					array( 'Php1000 Bill', '1000 pesos bill', 21, 1, 1, 0, 1, 'bill', 'piece', 0, 1, 'cash', FALSE, FALSE ),

					array( 'Bag Php5@100', 'Bag of Php5 coins worth Php100', 23, 1, 1, 1, 0, 'coin', 'bag', 0, 1, 'cash', FALSE, FALSE ),
					array( 'Bag Php1@100', 'Bag of Php1 coins worth Php100', 21, 1, 1, 1, 0, 'coin', 'bag', 0, 1, 'cash', FALSE, FALSE ),

					array( 'Change Fund', 'Change Fund', NULL, 0, 0, 0, 0, 'fund', 'lot', 0, 0, 'fund', FALSE, FALSE ),
					array( 'Sales', 'Sales', NULL, 0, 0, 0, 0, 'fund', 'lot', 0, 0, 'fund', FALSE, FALSE ),
					array( 'CA Fund', 'Coin Acceptor Fund', NULL, 0, 0, 0, 0, 'fund', 'lot', 0, 0, 'fund', FALSE, FALSE ),
					array( 'TVM Hopper', 'Coins in TVM', NULL, 0, 0, 0, 0, 'fund', 'lot', 0, 0, 'fund', FALSE, FALSE ),
					array( 'In Transit', 'In Transit Cash', NULL, 0, 0, 0, 0, 'fund', 'lot', 0, 0, 'fund', FALSE, FALSE ),
					array( 'CSC Card Fee', 'Concessionary Card Fee Fund', NULL, 0, 0, 0, 0, 'fund', 'lot', 0, 0, 'fund', FALSE, FALSE ),
					array( 'TVMIR', 'TVMIR Refund', NULL, 0, 0, 0, 0, 'fund', 'lot', 0, 0, 'fund', FALSE, FALSE ),
				);

			foreach( $items as $i )
			{
				$item = new Item();
				$item->set( 'item_name', $i[0] );
				$item->set( 'item_description', $i[1] );
				$item->set( 'base_item_id', $i[2] );
				$item->set( 'teller_allocatable', $i[3] );
				$item->set( 'teller_remittable', $i[4] );
				$item->set( 'machine_allocatable', $i[5] );
				$item->set( 'machine_remittable', $i[6] );
				$item->set( 'item_group', $i[7] );
				$item->set( 'item_unit', $i[8] );
				$item->set( 'turnover_item', $i[9] );
				$item->set( 'item_type', $i[10] );
				$item->set( 'item_class', $i[11] );
				$item->set( 'teller_saleable', $i[12] );
				$item->set( 'machine_saleable', $i[13] );
				$item->db_save();
				unset( $item );
			}
			echo 'OK<br />';
			flush();

			// Create default items
			echo 'Creating default card_profiles...';
			flush();
			$card_profiles = array(
					array( 'id' => 1, 'cardp_description' => 'Standard SVC', 'cardp_item_id' => 6 ),
					array( 'id' => 2, 'cardp_description' => 'Senior Citizens SVC', 'cardp_item_id' => 12 ),
					array( 'id' => 3, 'cardp_description' => 'Person with Disabilities SVC', 'cardp_item_id' => 13 ),
					array( 'id' => 4, 'cardp_description' => 'LRT1 Employee Card', 'cardp_item_id' => NULL ),
					array( 'id' => 5, 'cardp_description' => 'LRT2 Employee Card', 'cardp_item_id' => NULL ),
					array( 'id' => 6, 'cardp_description' => 'MRT3 Employee Card', 'cardp_item_id' => NULL ),
					array( 'id' => 7, 'cardp_description' => 'AFCS Employee Card', 'cardp_item_id' => NULL ),
					array( 'id' => 8, 'cardp_description' => 'LRT1 SJT', 'cardp_item_id' => NULL ),
					array( 'id' => 9, 'cardp_description' => 'LRT2 SJT', 'cardp_item_id' => 1 ),
					array( 'id' => 10, 'cardp_description' => 'MRT3 SJT', 'cardp_item_id' => NULL ),
					array( 'id' => 11, 'cardp_description' => 'LRT1 SJT for Senior Citizens', 'cardp_item_id' => NULL ),
					array( 'id' => 12, 'cardp_description' => 'LRT2 SJT for Senior Citizens', 'cardp_item_id' => 1 ),
					array( 'id' => 13, 'cardp_description' => 'MRT3 SJT for Senior Citizens', 'cardp_item_id' => NULL ),
					array( 'id' => 14, 'cardp_description' => 'LRT1 SJT for PWD', 'cardp_item_id' => NULL ),
					array( 'id' => 15, 'cardp_description' => 'LRT2 SJT for PWD', 'cardp_item_id' => 1 ),
					array( 'id' => 16, 'cardp_description' => 'MRT3 SJT for PWD', 'cardp_item_id' => NULL ),
					array( 'id' => 17, 'cardp_description' => 'Student', 'cardp_item_id' => NULL ),
					array( 'id' => 18, 'cardp_description' => 'Beep-Smart', 'cardp_item_id' => NULL ),
					array( 'id' => 19, 'cardp_description' => 'Beep-Globe', 'cardp_item_id' => NULL ),
					array( 'id' => 20, 'cardp_description' => 'Beep-BPI', 'cardp_item_id' => NULL ),
					array( 'id' => 21, 'cardp_description' => 'Discount Card', 'cardp_item_id' => NULL ),
				);

			$profile_values = array();
			foreach( $card_profiles as $profile )
			{
				$profile_values[] = '('.$profile['id'].", '".$profile['cardp_description']."', ".( is_null( $profile['cardp_item_id'] ) ? 'NULL' : $profile['cardp_item_id'] ).')';
			}
			$sql = 'INSERT INTO card_profiles (id, cardp_description, cardp_item_id) VALUES '.implode( ', ', $profile_values );
			$this->db->query( $sql );
			echo 'OK<br />';
			flush();

			// Creating default inventory
			echo 'Creating store inventories...';
			$test_inventory = param( $params, 'test_inventory' );
			flush();
			$this->load->library( 'Inventory' );
			$stores = new Store();
			$stores = $stores->get_stores();
			$items = new Item();
			$items = $items->get_items();
			foreach( $stores as $store )
			{
				foreach( $items as $item )
				{
					if( in_array( $item->get( 'item_class' ), array( 'cash', 'fund' ) ) && ( $store->get( 'store_type' ) != STORE_TYPE_CASHROOM ) )
					{
						continue;
					}

					$inventory = $store->add_item( $item );
					$quantity = 0;

					switch( $item->get( 'item_name' ) )
					{
						case 'L2 SJT - Rigid Box':
							if( $test_inventory ) $quantity = rand(10, 50);
							break;

						case 'L2 SJT - Ticket Magazine':
							if( $test_inventory ) $quantity = rand(1, 8);
							break;

						case 'SVC - Rigid Box':
							if( $test_inventory ) $quantity = rand(1, 8);
							break;

						default:
							switch( $item->get( 'item_group' ) )
							{
								case 'SJT':
									if( $test_inventory ) $quantity = rand(5, 50);
									break;

								case 'coin':
								case 'bill':
									if( $test_inventory ) $quantity = rand(10, 5000);
									break;

								default:
									if( $test_inventory ) $quantity = 0;
							}
					}
					$inventory->transact( TRANSACTION_INIT, $quantity, date( TIMESTAMP_FORMAT ), 0 );
				}
			}
			echo 'OK<br />';
			flush();

			// Create default conversion table
			echo 'Creating default conversion table...';
			flush();
			$this->load->library( 'item' );
			$values = array(
				// SJT
				array( 'L2 SJT', 'L2 SJT - Rigid Box', 50 ),
				array( 'L2 SJT', 'L2 SJT - Ticket Magazine', 800 ),
				array( 'L2 SJT', 'L2 SJT - Defective', 1 ),
				array( 'L2 SJT', 'L2 SJT - Damaged', 1 ),
				array( 'L2 SJT - Defective', 'L2 SJT - Damaged', 1 ),

				// SVC
				array( 'SVC', 'SVC - Rigid Box', 10 ),
				array( 'SVC', 'SVC - Defective', 1 ),
				array( 'SVC', 'SVC - Damaged', 1 ),
				array( 'SVC', 'SVC - 25', 25 ),
				array( 'SVC', 'SVC - 150', 150 ),
				array( 'SVC - Defective', 'SVC - Damaged', 1 ),

				array( 'Senior', 'Senior - Defective', 1 ),
				array( 'PWD', 'PWD - Defective', 1 ),

				// Other cards
				array( 'L1 SJT', 'Others', 1 ),
				array( 'MRT SJT', 'Others', 1 ),
				array( 'Staff Card', 'Others', 1 ),

				array( 'Php1 Coin', 'Bag Php1@100', 100 ),
				array( 'Php5 Coin', 'Bag Php5@100', 20 ),
			);

			$item = new Item();
			foreach( $values as $value )
			{
					$source = $item->get_by_name( $value[0] );
					$target = $item->get_by_name( $value[1] );

					$this->db->set( 'source_item_id', $source->get( 'id' ) );
					$this->db->set( 'target_item_id', $target->get( 'id' ) );
					$this->db->set( 'conversion_factor', $value[2] );
					$this->db->insert( 'conversion_table' );
			}
			echo 'OK<br />';
			flush();

			// Create default categories
			echo 'Creating default categories...';
			flush();

			$values = array(
					// Tranfer categories
					array(
						'cat_name'        => 'ExtTrans',
						'cat_description' => 'External Transfer',
						'cat_module'      => 'Transfer',
						'cat_ticket'      => 1,
						'cat_cash'        => 1,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'IntTrans',
						'cat_description' => 'Internal Transfer',
						'cat_module'      => 'Transfer',
						'cat_ticket'      => 1,
						'cat_cash'        => 1,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'StockRep',
						'cat_description' => 'Stock Replenishment',
						'cat_module'      => 'Transfer',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'TktTurn',
						'cat_description' => 'Ticket Turnover',
						'cat_module'      => 'Transfer',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'Blackbox',
						'cat_description' => 'Blackbox Receipt',
						'cat_module'      => 'Transfer',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'BillToCoin',
						'cat_description' => 'Bills to Coin Exchange',
						'cat_module'      => 'Transfer',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'CSCApp',
						'cat_description' => 'CSC Application',
						'cat_module'      => 'Transfer',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'BankDep',
						'cat_description' => 'Bank Deposit',
						'cat_module'      => 'Transfer',
						'cat_ticket'      => 1,
						'cat_cash'        => 1,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),

					// Adjustment categories
					array(
						'cat_name'        => 'Adjust',
						'cat_description' => 'Adjustment',
						'cat_module'      => 'Adjustment',
						'cat_ticket'      => 1,
						'cat_cash'        => 1,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),

					// Allocation categories
					array(
						'cat_name'        => 'InitAlloc',
						'cat_description' => 'Initial Allocation',
						'cat_module'      => 'Allocation',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'AddAlloc',
						'cat_description' => 'Additional Allocation',
						'cat_module'      => 'Allocation',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'TVMAlloc',
						'cat_description' => 'TVM Replenishment',
						'cat_module'      => 'Allocation',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 0,
						'cat_machine'     => 1,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'InitCFund',
						'cat_description' => 'Initial Change Fund',
						'cat_module'      => 'Allocation',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'AddCFund',
						'cat_description' => 'Additional Change Fund',
						'cat_module'      => 'Allocation',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'HopAlloc',
						'cat_description' => 'Hopper Replenishment',
						'cat_module'      => 'Allocation',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => 0,
						'cat_machine'     => 1,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'CAAlloc',
						'cat_description' => 'Coin Acceptor Replenishment',
						'cat_module'      => 'Allocation',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => 0,
						'cat_machine'     => 1,
						'cat_status'      => 1
					),

					// Remittance Categories
					array(
						'cat_name'        => 'Unsold',
						'cat_description' => 'Unsold / Loose',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 1,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'RemFreeExt',
						'cat_description' => 'Free Exit',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'Expired',
						'cat_description' => 'Expired',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'CodeRed',
						'cat_description' => 'Code Red',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'Unconfirmd',
						'cat_description' => 'Unconfirmed',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'TCERF',
						'cat_description' => 'TCERF',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'RejectBin',
						'cat_description' => 'Reject Bin',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 0,
						'cat_machine'     => 1,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'SalesColl',
						'cat_description' => 'Sales Collection',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => 1,
						'cat_machine'     => 1,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'CFundRet',
						'cat_description' => 'Change Fund Return',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'HopPullout',
						'cat_description' => 'Hopper Pullout',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => 0,
						'cat_machine'     => 1,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'CAPullout',
						'cat_description' => 'Coin Acceptor Pullout',
						'cat_module'      => 'Remittance',
						'cat_ticket'      => 0,
						'cat_cash'        => 1,
						'cat_teller'      => 0,
						'cat_machine'     => 1,
						'cat_status'      => 1
					),

					// Sales Categories
					array(
						'cat_name'        => 'TktSales',
						'cat_description' => 'Ticket Sale',
						'cat_module'      => 'Sales',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'CSCIssue',
						'cat_description' => 'CSC Issuance',
						'cat_module'      => 'Sales',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'SalePdExt',
						'cat_description' => 'Paid Exit',
						'cat_module'      => 'Sales',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'SaleFrExt',
						'cat_description' => 'Free Exit',
						'cat_module'      => 'Sales',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'SaleUncfrm',
						'cat_description' => 'Unconfirmed',
						'cat_module'      => 'Sales',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => 1,
						'cat_machine'     => 0,
						'cat_status'      => 1
					),

					// Conversion categories
					array(
						'cat_name'        => 'Pack',
						'cat_description' => 'Pack',
						'cat_module'      => 'Conversion',
						'cat_ticket'      => 1,
						'cat_cash'        => 1,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'Unpack',
						'cat_description' => 'Unpack',
						'cat_module'      => 'Conversion',
						'cat_ticket'      => 1,
						'cat_cash'        => 1,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'Conversion',
						'cat_description' => 'Item Conversion',
						'cat_module'      => 'Conversion',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),

					// Mopping collection categories
					array(
						'cat_name'        => 'TktCollect',
						'cat_description' => 'Mopping Collection',
						'cat_module'      => 'Collection',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
					array(
						'cat_name'        => 'TktIssue',
						'cat_description' => 'Card Issuance from Stock',
						'cat_module'      => 'Collection',
						'cat_ticket'      => 1,
						'cat_cash'        => 0,
						'cat_teller'      => NULL,
						'cat_machine'     => NULL,
						'cat_status'      => 1
					),
			);

			foreach( $values as $value )
			{
				$this->db->set( $value );
				$this->db->insert( 'categories' );
			}
			echo 'OK<br />';
			flush();

			// Create default item categories
			echo 'Creating default item categories...';
			flush();

			$values = array(
					array( 'L2 SJT', 'IntTrans' ),
					array( 'L2 SJT', 'ExtTrans' ),
					array( 'L2 SJT', 'TktTurn' ),
					array( 'L2 SJT', 'Adjust' ),
					array( 'L2 SJT', 'Unsold' ),
					array( 'L2 SJT', 'RemFreeExt' ),
					array( 'L2 SJT', 'Expired' ),
					array( 'L2 SJT', 'CodeRed' ),
					array( 'L2 SJT', 'Unconfirmd' ),
					array( 'L2 SJT', 'TCERF' ),
					array( 'L2 SJT', 'RejectBin' ),
					array( 'L2 SJT', 'TktSales' ),
					array( 'L2 SJT', 'SalePdExt' ),
					array( 'L2 SJT', 'SaleFrExt' ),
					array( 'L2 SJT', 'SaleUncfrm' ),
					array( 'L2 SJT', 'Pack' ),
					array( 'L2 SJT', 'Conversion' ),
					array( 'L2 SJT', 'TktCollect' ),
					array( 'L2 SJT', 'TktIssue' ),

					array( 'L2 SJT - Rigid Box', 'IntTrans' ),
					array( 'L2 SJT - Rigid Box', 'StockRep' ),
					array( 'L2 SJT - Rigid Box', 'Adjust' ),
					array( 'L2 SJT - Rigid Box', 'InitAlloc' ),
					array( 'L2 SJT - Rigid Box', 'AddAlloc' ),
					array( 'L2 SJT - Rigid Box', 'Unsold' ),
					array( 'L2 SJT - Rigid Box', 'Unpack' ),
					array( 'L2 SJT - Rigid Box', 'TktCollect' ),

					array( 'L2 SJT - Ticket Magazine', 'IntTrans' ),
					array( 'L2 SJT - Ticket Magazine', 'StockRep' ),
					array( 'L2 SJT - Ticket Magazine', 'Adjust' ),
					array( 'L2 SJT - Ticket Magazine', 'TVMAlloc' ),
					array( 'L2 SJT - Ticket Magazine', 'Unpack' ),
					array( 'L2 SJT - Ticket Magazine', 'TktCollect' ),

					array( 'L2 SJT - Defective', 'IntTrans' ),
					array( 'L2 SJT - Defective', 'ExtTrans' ),
					array( 'L2 SJT - Defective', 'TCERF' ),
					array( 'L2 SJT - Defective', 'RejectBin' ),
					array( 'L2 SJT - Defective', 'Blackbox' ),
					array( 'L2 SJT - Defective', 'Conversion' ),

					array( 'L2 SJT - Damaged', 'IntTrans' ),
					array( 'L2 SJT - Damaged', 'ExtTrans' ),
					array( 'L2 SJT - Damaged', 'TCERF' ),
					array( 'L2 SJT - Damaged', 'RejectBin' ),
					array( 'L2 SJT - Damaged', 'Blackbox' ),
					array( 'L2 SJT - Damaged', 'Conversion' ),

					array( 'SVC', 'IntTrans' ),
					array( 'SVC', 'ExtTrans' ),
					array( 'SVC', 'TktTurn' ),
					array( 'SVC', 'Adjust' ),
					array( 'SVC', 'Unsold' ),
					array( 'SVC', 'Expired' ),
					array( 'SVC', 'RejectBin' ),
					array( 'SVC', 'TktSales' ),
					array( 'SVC', 'Pack' ),
					array( 'SVC', 'Conversion' ),
					array( 'SVC', 'TktIssue' ),

					array( 'SVC - Rigid Box', 'IntTrans' ),
					array( 'SVC - Rigid Box', 'StockRep' ),
					array( 'SVC - Rigid Box', 'Adjust' ),
					array( 'SVC - Rigid Box', 'InitAlloc' ),
					array( 'SVC - Rigid Box', 'AddAlloc' ),
					array( 'SVC - Rigid Box', 'Unsold' ),
					array( 'SVC - Rigid Box', 'Unpack' ),

					array( 'SVC - 25', 'IntTrans' ),
					array( 'SVC - 25', 'StockRep' ),
					array( 'SVC - 25', 'Adjust' ),
					array( 'SVC - 25', 'InitAlloc' ),
					array( 'SVC - 25', 'AddAlloc' ),
					array( 'SVC - 25', 'Unsold' ),
					array( 'SVC - 25', 'Unpack' ),

					array( 'SVC - 150', 'IntTrans' ),
					array( 'SVC - 150', 'StockRep' ),
					array( 'SVC - 150', 'Adjust' ),
					array( 'SVC - 150', 'TVMAlloc' ),
					array( 'SVC - 150', 'Unpack' ),

					array( 'SVC - Defective', 'IntTrans' ),
					array( 'SVC - Defective', 'ExtTrans' ),
					array( 'SVC - Defective', 'Adjust' ),
					array( 'SVC - Defective', 'RejectBin' ),
					array( 'SVC - Defective', 'Blackbox' ),
					array( 'SVC - Defective', 'Conversion' ),

					array( 'SVC - Damaged', 'IntTrans' ),
					array( 'SVC - Damaged', 'ExtTrans' ),
					array( 'SVC - Damaged', 'Adjust' ),
					array( 'SVC - Damaged', 'RejectBin' ),
					array( 'SVC - Damaged', 'Blackbox' ),
					array( 'SVC - Damaged', 'Conversion' ),

					array( 'Senior', 'IntTrans' ),
					array( 'Senior', 'ExtTrans' ),
					array( 'Senior', 'Adjust' ),
					array( 'Senior', 'AddAlloc' ),
					array( 'Senior', 'CSCIssue' ),
					array( 'Senior', 'Blackbox' ),

					array( 'PWD', 'IntTrans' ),
					array( 'PWD', 'ExtTrans' ),
					array( 'PWD', 'Adjust' ),
					array( 'PWD', 'AddAlloc' ),
					array( 'PWD', 'CSCIssue' ),
					array( 'PWD', 'Blackbox' ),

					array( 'L2 Ticket Coupon', 'IntTrans' ),
					array( 'L2 Ticket Coupon', 'ExtTrans' ),
					array( 'L2 Ticket Coupon', 'StockRep' ),
					array( 'L2 Ticket Coupon', 'Adjust' ),
					array( 'L2 Ticket Coupon', 'InitAlloc' ),
					array( 'L2 Ticket Coupon', 'AddAlloc' ),
					array( 'L2 Ticket Coupon', 'Unsold' ),
					array( 'L2 Ticket Coupon', 'TktSales' ),

					array( 'Others', 'IntTrans' ),
					array( 'Others', 'ExtTrans' ),
					array( 'Others', 'Adjust' ),
					array( 'Others', 'Blackbox' ),
					array( 'Others', 'Conversion' ),

					array( 'L1 SJT', 'IntTrans' ),
					array( 'L1 SJT', 'ExtTrans' ),
					array( 'L1 SJT', 'Adjust' ),
					array( 'L1 SJT', 'Blackbox' ),
					array( 'L1 SJT', 'Conversion' ),

					array( 'MRT SJT', 'IntTrans' ),
					array( 'MRT SJT', 'ExtTrans' ),
					array( 'MRT SJT', 'Adjust' ),
					array( 'MRT SJT', 'Blackbox' ),
					array( 'MRT SJT', 'Conversion' ),

					array( 'Staff Card', 'IntTrans' ),
					array( 'Staff Card', 'ExtTrans' ),
					array( 'Staff Card', 'Adjust' ),
					array( 'Staff Card', 'Blackbox' ),
					array( 'Staff Card', 'Conversion' ),

					array( 'Php1 Coin', 'IntTrans' ),
					array( 'Php1 Coin', 'ExtTrans' ),
					array( 'Php1 Coin', 'BillToCoin' ),
					array( 'Php1 Coin', 'CSCApp' ),
					array( 'Php1 Coin', 'BankDep' ),
					array( 'Php1 Coin', 'Adjust' ),
					array( 'Php1 Coin', 'CAAlloc' ),
					array( 'Php1 Coin', 'SalesColl' ),
					array( 'Php1 Coin', 'CFundRet' ),
					array( 'Php1 Coin', 'HopPullout' ),
					array( 'Php1 Coin', 'CAPullout' ),
					array( 'Php1 Coin', 'Pack' ),

					array( 'Php0.25 Coin', 'IntTrans' ),
					array( 'Php0.25 Coin', 'ExtTrans' ),
					array( 'Php0.25 Coin', 'BillToCoin' ),
					array( 'Php0.25 Coin', 'CSCApp' ),
					array( 'Php0.25 Coin', 'BankDep' ),
					array( 'Php0.25 Coin', 'Adjust' ),
					array( 'Php0.25 Coin', 'SalesColl' ),
					array( 'Php0.25 Coin', 'CFundRet' ),

					array( 'Php5 Coin', 'IntTrans' ),
					array( 'Php5 Coin', 'ExtTrans' ),
					array( 'Php5 Coin', 'BillToCoin' ),
					array( 'Php5 Coin', 'CSCApp' ),
					array( 'Php5 Coin', 'BankDep' ),
					array( 'Php5 Coin', 'Adjust' ),
					array( 'Php5 Coin', 'CAAlloc' ),
					array( 'Php5 Coin', 'SalesColl' ),
					array( 'Php5 Coin', 'CFundRet' ),
					array( 'Php5 Coin', 'HopPullout' ),
					array( 'Php5 Coin', 'CAPullout' ),
					array( 'Php5 Coin', 'Pack' ),

					array( 'Php10 Coin', 'IntTrans' ),
					array( 'Php10 Coin', 'ExtTrans' ),
					array( 'Php10 Coin', 'BillToCoin' ),
					array( 'Php10 Coin', 'CSCApp' ),
					array( 'Php10 Coin', 'BankDep' ),
					array( 'Php10 Coin', 'Adjust' ),
					array( 'Php10 Coin', 'CAAlloc' ),
					array( 'Php10 Coin', 'SalesColl' ),
					array( 'Php10 Coin', 'CFundRet' ),
					array( 'Php10 Coin', 'HopPullout' ),
					array( 'Php10 Coin', 'CAPullout' ),

					array( 'Php20 Bill', 'IntTrans' ),
					array( 'Php20 Bill', 'ExtTrans' ),
					array( 'Php20 Bill', 'BillToCoin' ),
					array( 'Php20 Bill', 'BankDep' ),
					array( 'Php20 Bill', 'CSCApp' ),
					array( 'Php20 Bill', 'Adjust' ),
					array( 'Php20 Bill', 'SalesColl' ),
					array( 'Php20 Bill', 'CFundRet' ),

					array( 'Php50 Bill', 'IntTrans' ),
					array( 'Php50 Bill', 'ExtTrans' ),
					array( 'Php50 Bill', 'BillToCoin' ),
					array( 'Php50 Bill', 'BankDep' ),
					array( 'Php50 Bill', 'Adjust' ),
					array( 'Php50 Bill', 'SalesColl' ),
					array( 'Php50 Bill', 'CFundRet' ),

					array( 'Php100 Bill', 'IntTrans' ),
					array( 'Php100 Bill', 'ExtTrans' ),
					array( 'Php100 Bill', 'BillToCoin' ),
					array( 'Php100 Bill', 'BankDep' ),
					array( 'Php100 Bill', 'Adjust' ),
					array( 'Php100 Bill', 'SalesColl' ),
					array( 'Php100 Bill', 'CFundRet' ),

					array( 'Php200 Bill', 'IntTrans' ),
					array( 'Php200 Bill', 'ExtTrans' ),
					array( 'Php200 Bill', 'BillToCoin' ),
					array( 'Php200 Bill', 'BankDep' ),
					array( 'Php200 Bill', 'Adjust' ),
					array( 'Php200 Bill', 'SalesColl' ),
					array( 'Php200 Bill', 'CFundRet' ),

					array( 'Php500 Bill', 'IntTrans' ),
					array( 'Php500 Bill', 'ExtTrans' ),
					array( 'Php500 Bill', 'BillToCoin' ),
					array( 'Php500 Bill', 'BankDep' ),
					array( 'Php500 Bill', 'Adjust' ),
					array( 'Php500 Bill', 'SalesColl' ),
					array( 'Php500 Bill', 'CFundRet' ),

					array( 'Php1000 Bill', 'IntTrans' ),
					array( 'Php1000 Bill', 'ExtTrans' ),
					array( 'Php1000 Bill', 'BillToCoin' ),
					array( 'Php1000 Bill', 'BankDep' ),
					array( 'Php1000 Bill', 'Adjust' ),
					array( 'Php1000 Bill', 'SalesColl' ),
					array( 'Php1000 Bill', 'CFundRet' ),

					array( 'Bag Php1@100', 'IntTrans' ),
					array( 'Bag Php1@100', 'ExtTrans' ),
					array( 'Bag Php1@100', 'Adjust' ),
					array( 'Bag Php1@100', 'InitCFund' ),
					array( 'Bag Php1@100', 'AddCFund' ),
					array( 'Bag Php1@100', 'HopAlloc' ),
					array( 'Bag Php1@100', 'CFundRet' ),
					array( 'Bag Php1@100', 'Unpack' ),

					array( 'Bag Php5@100', 'IntTrans' ),
					array( 'Bag Php5@100', 'ExtTrans' ),
					array( 'Bag Php5@100', 'Adjust' ),
					array( 'Bag Php5@100', 'InitCFund' ),
					array( 'Bag Php5@100', 'AddCFund' ),
					array( 'Bag Php5@100', 'HopAlloc' ),
					array( 'Bag Php5@100', 'CFundRet' ),
					array( 'Bag Php5@100', 'Unpack' ),
				);

			$this->load->library( 'item' );
			$this->load->library( 'category' );
			$Item = new Item();
			$Category = new Category();
			foreach( $values as $value )
			{
				echo $value[0].' === '.$value[1].'<br/>';
				$item = $Item->get_by_name( $value[0] );
				$category = $Category->get_by_name( $value[1] );
				$this->db->set( 'ic_item_id', $item->get( 'id' ) );
				$this->db->set( 'ic_category_id', $category->get( 'id' ) );
				$this->db->insert( 'item_categories' );
			}

			echo 'OK<br />';
			flush();

			// Create default prices
			echo 'Creating default item prices...';
			flush();

			$values = array(
						array( 'Php1 Coin', 'PHP', 1.00 ),
						array( 'Php0.25 Coin', 'PHP', 0.25 ),
						array( 'Php5 Coin', 'PHP', 5.00 ),
						array( 'Php10 Coin', 'PHP', 10.00 ),
						array( 'Php20 Bill', 'PHP', 20.00 ),
						array( 'Php50 Bill', 'PHP', 50.00 ),
						array( 'Php100 Bill', 'PHP', 100.00 ),
						array( 'Php200 Bill', 'PHP', 200.00 ),
						array( 'Php500 Bill', 'PHP', 500.00 ),
						array( 'Php1000 Bill', 'PHP', 1000.00 ),
						array( 'Bag Php1@100', 'PHP', 100.00 ),
						array( 'Bag Php5@100', 'PHP', 100.00 ),
				);

			$this->load->library( 'item' );
			$this->load->library( 'category' );
			$Item = new Item();

			$now = date( TIMESTAMP_FORMAT );
			foreach( $values as $value )
			{
				$item = $Item->get_by_name( $value[0] );
				$this->db->set( 'iprice_item_id', $item->get( 'id' ) );
				$this->db->set( 'iprice_currency', $value[1] );
				$this->db->set( 'iprice_unit_price', $value[2] );
				$this->db->set( 'date_created', $now );
				$this->db->set( 'date_modified', $now );
				$this->db->set( 'created_by', 1 );
				$this->db->set( 'modified_by', 1 );
				$this->db->insert( 'item_prices' );
			}
		}

		// Restore shift
		$this->session->current_shift_id = $current_shift_id;

		$this->db->trans_complete();
	}

	public function create_test_data()
	{
		echo heading( 'Creating test data...', 3 );

		echo 'Adding test users...';
		flush();
		$this->load->library( 'user' );
		$user1 = new User();
		$user1->set( 'username', 'erhsatingin' );
		$user1->set( 'full_name', 'Erwin Rommel H. Satingin' );
		$user1->set( 'position', 'MIS Design Specialist B' );
		$user1->set( 'user_status', 1 ); // active
		$user1->set( 'user_role', 1 ); // administrator
		$user1->set_password( 'password123' );
		$user1->db_save();

				$user2 = new User();
		$user2->set( 'username', 'mmduron' );
		$user2->set( 'full_name', 'Marlon M. Duron' );
		$user2->set( 'position', 'Data Encoder-Controller' );
		$user2->set( 'user_status', 1 ); // active
		$user2->set( 'user_role', 2 ); // standard user
		$user2->set_password( 'password123' );
		$user2->db_save();

				echo 'OK<br />';
				flush();

		$this->load->library( 'store' );
		$store = new Store();
		$st_depot = $store->get_by_id( 1 );
		$st_depot->add_member( $user1 );

		$st_tgm = $store->get_by_id( 2 );
		$st_tgm->add_member( $user1 );

		$st_prod = $store->get_by_id( 3 );
		$st_prod->add_member( $user1 );

		for( $i = 4; $i < 17; $i++ )
		{
			$stn = $store->get_by_id( $i );
			$stn->add_member( $user2 );
		}
	}

	public function reset_database( $mode = NULL )
	{
		echo heading( 'Resetting database...', 3 );
		flush();
		$this->db->trans_start();

		$this->db->query( "SET FOREIGN_KEY_CHECKS = OFF" );

		if( $mode != 'transactions' )
		{
			$this->db->query( "TRUNCATE TABLE stations" );
			$this->db->query( "TRUNCATE TABLE shifts" );
			$this->db->query( "TRUNCATE TABLE groups" );
			$this->db->query( "TRUNCATE TABLE users" );
			$this->db->query( "TRUNCATE TABLE stores" );
			$this->db->query( "TRUNCATE TABLE store_users" );
			$this->db->query( "TRUNCATE TABLE sales_items" );
			$this->db->query( "TRUNCATE TABLE items" );
			$this->db->query( "TRUNCATE TABLE card_profiles" );
			$this->db->query( "TRUNCATE TABLE categories" );
			$this->db->query( "TRUNCATE TABLE item_categories" );
			$this->db->query( "TRUNCATE TABLE item_prices" );
			$this->db->query( "TRUNCATE TABLE conversion_table" );
		}

		$this->db->query( "TRUNCATE TABLE store_inventory" );
		$this->db->query( "TRUNCATE TABLE shift_turnovers" );
		$this->db->query( "TRUNCATE TABLE shift_turnover_items" );
		$this->db->query( "TRUNCATE TABLE transactions" );
		$this->db->query( "TRUNCATE TABLE adjustments" );
		$this->db->query( "TRUNCATE TABLE transfers" );
		$this->db->query( "TRUNCATE TABLE transfer_items" );
		$this->db->query( "TRUNCATE TABLE transfer_validations" );
		$this->db->query( "TRUNCATE TABLE conversions" );
		$this->db->query( "TRUNCATE TABLE allocations" );
		$this->db->query( "TRUNCATE TABLE allocation_items" );
		$this->db->query( "TRUNCATE TABLE allocation_sales_items" );
		$this->db->query( "TRUNCATE TABLE mopping" );
		$this->db->query( "TRUNCATE TABLE mopping_items" );
		$this->db->query( "TRUNCATE TABLE adjustment_status_log" );
		$this->db->query( "TRUNCATE TABLE transfer_status_log" );
		$this->db->query( "TRUNCATE TABLE conversion_status_log" );
		$this->db->query( "TRUNCATE TABLE allocation_status_log" );
		$this->db->query( "TRUNCATE TABLE tvm_readings" );
		$this->db->query( "TRUNCATE TABLE tvm_reading_items" );
		$this->db->query( "TRUNCATE TABLE shift_detail_cash_reports" );
		$this->db->query( "TRUNCATE TABLE shift_detail_cash_report_items" );


		$this->db->query( "SET FOREIGN_KEY_CHECKS = OFF" );

		$params = $this->input->get();
		if( $mode == 'transactions' )
		{
			$params = array_merge( $params, array(
				'mode' => $mode
			));
		}

		$this->create_default_data( $params );
		//$this->create_test_data();

		$this->db->trans_complete();
		echo heading( 'Database has been reset..', 3 );
		echo '<br />';
		echo 'Finished resetting the database. '.anchor('login', 'Login').' to the site.';
		flush();
	}

	public function new_database()
	{
		$db_name = 'frogims';
		$this->load->dbforge();
		echo heading( 'Dropping database...', 3 );
		echo '<br />';
		$this->dbforge->drop_database( 'frogims' );
		echo heading( 'Creating database...', 3 );
		echo '<br />';
		$this->dbforge->create_database( 'frogims' );
		$this->create_tables();
		$this->create_default_data();

		echo heading( 'Database has been recreated..', 3 );
		echo '<br />';
		echo anchor('login', 'Login').' to the site.';
		flush();
	}

	public function drop_database()
	{
		heading( 'Dropping database...', 3 );
		flush();
		$this->db->query( "DROP DATABASE frogims" );
	}

}