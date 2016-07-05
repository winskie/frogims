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
		heading( 'Creating database...', 3 );
		$this->db->query( "CREATE DATABASE IF NOT EXISTS frogims" );
		$this->db->query( "USE frogims" );

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
                    shift_num VARCHAR(10) NOT NULL,
                    store_type INTEGER NOT NULL,
                    shift_start_time TIME NOT NULL,
                    shift_end_time TIME NOT NULL,
                    description TEXT,
                    PRIMARY KEY (id)
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
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					last_modified INTEGER NOT NULL,
					PRIMARY KEY (id),
					UNIQUE users_undx (username)
				)
				ENGINE=InnoDB" );

		echo 'Creating groups table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS groups
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					group_name VARCHAR(100) NOT NULL,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					last_modified INTEGER NOT NULL,
					PRIMARY KEY( id ),
					UNIQUE groups_undx ( group_name )
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
					store_location VARCHAR(100) NOT NULL,
					store_contact_number VARCHAR(25) NULL,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					last_modified INTEGER NOT NULL,
					PRIMARY KEY (id)
				)
				ENGINE=InnoDB" );

		echo 'Creating store_users table...<br />';
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

		echo 'Creating items table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					item_name VARCHAR(100) NOT NULL,
					item_description VARCHAR(255) NULL,
					item_group VARCHAR(100) NULL,
                    base_item_id INTEGER NULL DEFAULT NULL,
					teller_allocatable BOOLEAN NOT NULL DEFAULT 0,
					teller_remittable BOOLEAN NOT NULL DEFAULT 0,
					machine_allocatable BOOLEAN NOT NULL DEFAULT 0,
					machine_remittable BOOLEAN NOT NULL DEFAULT 0,
					date_created DATETIME NOT NULL,
					date_modified DATETIME NOT NULL,
					last_modified INTEGER NOT NULL,
					PRIMARY KEY (id)
				)
				ENGINE=InnoDB" );

		echo 'Creating store_inventory table....<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS store_inventory
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_id INTEGER NOT NULL,
					item_id INTEGER NOT NULL,
					quantity INTEGER NOT NULL DEFAULT 0,
					quantity_timestamp DATETIME NOT NULL,
					buffer_level INTEGER NOT NULL DEFAULT 0,
					reserved INTEGER NOT NULL DEFAULT 0,
					PRIMARY KEY (id),
					UNIQUE store_inventory_undx (store_id, item_id),
					FOREIGN KEY store_inventory_store_fk (store_id) REFERENCES stores (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY store_inventory_item_fx (item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating transactions table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS transactions
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_inventory_id INTEGER NOT NULL,
					transaction_type SMALLINT NOT NULL,
					transaction_datetime DATETIME NOT NULL,
					transaction_quantity INTEGER NOT NULL DEFAULT 0,
					current_quantity INTEGER NOT NULL,
					transaction_id INTEGER NOT NULL,
					transaction_timestamp DATETIME NOT NULL,
                    transaction_shift INTEGER NOT NULL,
					PRIMARY KEY (id),
					INDEX transactions_main_ndx (transaction_datetime, transaction_type),
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
					previous_quantity INTEGER NOT NULL,
					reason VARCHAR(255) NOT NULL,
					adjustment_status SMALLINT NOT NULL DEFAULT 1,
					adjustment_timestamp DATETIME NOT NULL,
					user_id INTEGER NOT NULL,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					last_modified INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY adjustments_store_inventory_fk (store_inventory_id) REFERENCES store_inventory (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT,
					FOREIGN KEY adjustments_user_fk (user_id) REFERENCES users (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating transfers table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS transfers
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					origin_id INTEGER NULL,
					origin_name VARCHAR(100) NULL,
					sender_id INTEGER NULL,
					sender_name VARCHAR(100) NULL,
                    sender_shift INTEGER NULL,
					transfer_datetime DATETIME NOT NULL,
					destination_id INTEGER NULL,
					destination_name VARCHAR(100) NULL,
					recipient_id INTEGER NULL,
					recipient_name VARCHAR(100) NULL,
                    recipient_shift INTEGER NULL,
					receipt_datetime DATETIME NULL,
					transfer_status SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					last_modified INTEGER NOT NULL,
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
						ON DELETE SET NULL
				)
				ENGINE=InnoDB" );

		echo 'Creating transfer items table... <br />';
		$this->db->query("
				CREATE TABLE IF NOT EXISTS transfer_items
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					transfer_id INTEGER NOT NULL,
					item_id INTEGER NOT NULL,
					item_category_id INTEGER NULL DEFAULT NULL,
					quantity INTEGER NOT NULL DEFAULT 0,
					quantity_received INTEGER NULL DEFAULT NULL,
					remarks TEXT NULL DEFAULT NULL,
					transfer_item_status SMALLINT NOT NULL DEFAULT 1,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					last_modified INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY transfer_items_transfer_fk (transfer_id) REFERENCES transfers (id)
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
					created_by INTEGER NOT NULL,
                    date_created DATETIME NOT NULL,
                    date_modified TIMESTAMP NOT NULL,
                    last_modified INTEGER NOT NULL,
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

		echo 'Creating allocations table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS allocations
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					store_id INTEGER NOT NULL,
					business_date DATE NOT NULL,
					shift_id INTEGER NOT NULL,
					station_id SMALLINT NULL,
					assignee VARCHAR(50) NOT NULL,
					assignee_type SMALLINT NOT NULL,
					allocation_status SMALLINT NOT NULL DEFAULT 1,
					cashier_id INTEGER NOT NULL,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					last_modified INTEGER NOT NULL,
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
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					last_modified INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY allocation_items_fk (allocation_id) REFERENCES allocations (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY allocation_items_item_fk (allocated_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
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
					last_modified INTEGER NOT NULL,
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
                    processor_id INTEGER NOT NULL DEFAULT 0,
					date_created DATETIME NOT NULL,
					date_modified TIMESTAMP NOT NULL,
					last_modified INTEGER NOT NULL,
					PRIMARY KEY (id),
					FOREIGN KEY mopping_items_fk (mopping_id) REFERENCES mopping (id)
						ON UPDATE CASCADE
						ON DELETE CASCADE,
					FOREIGN KEY mopping_items_item_fk (mopped_item_id) REFERENCES items (id)
						ON UPDATE CASCADE
						ON DELETE RESTRICT
				)
				ENGINE=InnoDB" );

		echo 'Creating item_categories table...<br />';
		$this->db->query( "
				CREATE TABLE IF NOT EXISTS item_categories
				(
					id INTEGER AUTO_INCREMENT NOT NULL,
					category VARCHAR(100) NOT NULL,
					category_type SMALLINT NOT NULL,
					is_allocation_category BOOLEAN NOT NULL DEFAULT 0,
					is_remittance_category BOOLEAN NOT NULL DEFAULT 0,
					is_transfer_category BOOLEAN NOT NULL DEFAULT 0,
					is_teller BOOLEAN NOT NULL,
					is_machine BOOLEAN NOT NULL,
					category_status SMALLINT NOT NULL DEFAULT 1,
					PRIMARY KEY (id)
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

		// Create admin user
		echo 'Creating default admin user...';
        flush();
		$this->load->library( 'User' );
		$admin_User = new User();
		$admin_User->set( 'username', 'admin' );
		$admin_User->set( 'full_name', 'System Administrator' );
		$admin_User->set( 'position', 'System Administrator' );
		$admin_User->set( 'user_status', 1 ); // active
		$admin_User->set( 'user_role', 1 ); // administrator
		$admin_User->set( 'last_modified', 1 );
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
                array( 'No Shift', 1, 'No shift', '00:00:00', '23:59:59' ),
                array( 'Prod S1', 2, 'Production Shift 1', '07:00:00', '14:59:59' ),
                array( 'Prod S2', 2, 'Production Shift 2', '13:00:00', '20:59:59' ),
                array( 'TGM S1', 3, 'Transport Shift 1', '06:00:00', '13:59:59' ),
                array( 'TGM S2', 3, 'Transport Shift 2', '14:00:00', '21:59:59' ),
                array( 'Cashier S1', 4, 'Cashier Shift 1', '06:00:00', '13:59:59' ),
                array( 'Cashier S2', 4, 'Cashier Shift 2', '14:00:00', '21:59:59' ),
                array( 'Cashier S3', 4, 'Cashier Shift 3', '22:00:00', '05:59:59' ),
                array( 'Teller S1', 0, 'Teller Shift 1', '06:00:00', '13:59:59' ),
                array( 'Teller S2', 0, 'Teller Shift 2', '14:00:00', '21:59:59' ),
                array( 'Teller S3', 0, 'Teller Shift 3', '22:00:00', '05:59:59' )
            );

        foreach( $shifts as $s )
        {
            $shift = new Shift();
            $shift->set( 'shift_num', $s[0] );
            $shift->set( 'store_type', $s[1] );
            $shift->set( 'description', $s[2] );
            $shift->set( 'shift_start_time', $s[3] );
            $shift->set( 'shift_end_time', $s[4] );
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
				array( 'Line 2 Depot', 'Line 2 Depot', 'TIMD', 1 ),
				array( 'TVM and Gates Management', 'Anonas Station', 'TGM', 3 ),
				array( 'Ticket Production', 'J.Ruiz Station', 'TIMS', 2 ),
				array( 'TASCU East', 'Anonas Station', 'TASCE', 3),
				array( 'TASCU West', 'J.Ruiz Station', 'TASCW', 3),
				array( 'Recto Cashroom', 'Recto Station', 'RCT', 4 ),
				array( 'Legarda Cashroom', 'Legarda Station', 'LGRD', 4 ),
				array( 'Pureza Cashroom', 'Pureza Station', 'PRZ', 4 ),
				array( 'V.Mapa Cashroom', 'V.Mapa Station', 'VMP', 4 ),
				array( 'J.Ruiz Cashroom', 'J.Ruiz Station', 'JRZ', 4 ),
				array( 'Gilmore Cashroom', 'Gilmore Station', 'GLMR', 4 ),
				array( 'Betty Go - Belmonte Cashroom', 'Betty Go - Belmonte Station', 'BTYG', 4 ),
				array( 'Araneta Center - Cubao Cashroom', 'Araneta Center - Cubao Station', 'ACCB', 4 ),
				array( 'Anonas Cashroom', 'Anonas Station', 'ANNS', 4 ),
				array( 'Katipunan Cashroom', 'Katipunan  Station', 'KTPN', 4 ),
				array( 'Santolan Cashroom', 'Santolan Station', 'STLN', 4 )
			);

		foreach( $stores as $s )
		{
			$store = new Store();
			$store->set( 'store_name', $s[0] );
			$store->set( 'store_location', $s[1] );
			$store->set( 'store_code', $s[2] );
            $store->set( 'store_type', $s[3] );
			$store->db_save();
			unset( $store );
		}
		echo 'OK<br />';
        flush();

		// Adding admin user to first store
		echo 'Adding admin user to first store...';
		flush();
		$this->load->library( 'store' );
		$store = new Store();
		$st_depot = $store->get_by_id( 1 );
		$st_depot->add_member( $admin_User );
		echo 'OK<br />';
		flush();

		// Create default items
		echo 'Creating default items...';
        flush();
		$this->load->library( 'Item' );
		$items = array(
                array( 'L2 SJT', 'Line 2 Single Journey Ticket', NULL, 0, 1, 0, 0, 'SJT' ), // ID: 1
                array( 'L2 SJT - Rigid Box', 'Line 2 Single Journey Ticket in Rigid Box', 1, 1, 1, 0, 0, 'SJT' ),
				array( 'L2 SJT - Ticket Magazine', 'Line 2 Single Journey Ticket in Ticket Magazine', 1, 0, 0, 1, 0, 'SJT' ),
				array( 'L2 SJT - Defective', 'Defective Line 2 Single Journey Ticket', NULL, 0, 1, 0, 1, NULL ),
				array( 'L2 SJT - Damaged', 'Damaged Line 2 Single Journey Ticket', NULL, 0, 1, 0, 1, NULL ),

				array( 'SVC', 'Stored Value Card', NULL, 0, 1, 0, 0, 'SVC' ), // ID: 6
				array( 'SVC - Rigid Box', 'Stored Value Ticket in Rigid Box', 6, 1, 1, 0, 0, 'SVC' ),
                array( 'SVC - Defective', 'Defective Stored Value Card', NULL, 0, 1, 0, 1, NULL ),
                array( 'SVC - Damaged', 'Damaged Stored Value Card', NULL, 0, 1, 0, 1, NULL ),

				array( 'Senior SVC', 'Senior Citizen Stored Value Card', NULL, 0, 0, 0, 0, 'Concessionary' ),
				array( 'PWD SVC', 'Passenger with Disability Store Value Card', NULL, 0, 0, 0, 0, 'Concessionary' ),

				array( 'L2 Ticket Coupon', 'Line 2 Ticket Coupon', NULL, 1, 1, 0, 0, NULL ),

				array( 'Others', 'Other Cards', NULL, 0, 1, 0, 0, NULL ), // ID: 13
                array( 'L1 SJT', 'Line 1 Single Journey Ticket', 13, 0, 1, 0, 0, NULL )
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
			$item->db_save();
			unset( $item );
		}
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
            array( 'SVC - Defective', 'SVC - Damaged', 1 ),

			// Other cards
			array( 'L1 SJT', 'Others', 1 )
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

		// Create default item categories
		echo 'Creating default item categories...';
		flush();

		$values = array(
				array( 'Initial Allocation', 1, TRUE, FALSE, FALSE, TRUE, FALSE, 1 ),
				array( 'Additional Allocation', 1, TRUE, FALSE, FALSE, TRUE, FALSE, 1 ),
				array( 'Magazine Load', 1, TRUE, FALSE, FALSE, FALSE, TRUE, 1 ),
				array( 'Unsold / Loose', 2, FALSE, TRUE, TRUE, TRUE, FALSE, 1 ),
				array( 'Defective', 2, FALSE, TRUE, TRUE, TRUE, FALSE, 1 ),
				array( 'Reject Bin', 2, FALSE, TRUE, TRUE, FALSE, TRUE, 1 ),
				array( 'TIR', 2, FALSE, TRUE, TRUE, TRUE, FALSE, 1 ),
				array( 'Free Exit', 2, FALSE, TRUE, TRUE, TRUE, FALSE, 1 ),
				array( 'Expired', 2, FALSE, TRUE, TRUE, TRUE, FALSE, 1 ),
				array( 'Black Box', 2, FALSE, TRUE, TRUE, TRUE, FALSE, 1 )
			);

		foreach( $values as $value )
		{
			$this->db->set( 'category', $value[0] );
			$this->db->set( 'category_type', $value[1] );
			$this->db->set( 'is_allocation_category', $value[2] );
			$this->db->set( 'is_remittance_category', $value[3] );
			$this->db->set( 'is_transfer_category', $value[4] );
			$this->db->set( 'is_teller', $value[5] );
			$this->db->set( 'is_machine', $value[6] );
			$this->db->set( 'category_status', $value[7] );
			$this->db->insert( 'item_categories' );
		}
		echo 'OK<br />';
		flush();


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

	public function reset_database()
	{
		echo heading( 'Resetting database...', 3 );
        flush();
		$this->db->trans_start();

		$this->db->query( "SET FOREIGN_KEY_CHECKS = OFF" );

        $this->db->query( "TRUNCATE TABLE stations" );
        $this->db->query( "TRUNCATE TABLE shifts" );
		$this->db->query( "TRUNCATE TABLE users" );
		$this->db->query( "TRUNCATE TABLE stores" );
		$this->db->query( "TRUNCATE TABLE store_users" );
		$this->db->query( "TRUNCATE TABLE items" );
		$this->db->query( "TRUNCATE TABLE store_inventory" );
		$this->db->query( "TRUNCATE TABLE transactions" );
		$this->db->query( "TRUNCATE TABLE adjustments" );
		$this->db->query( "TRUNCATE TABLE transfers" );
		$this->db->query( "TRUNCATE TABLE transfer_items" );
        $this->db->query( "TRUNCATE TABLE conversion_table" );
        $this->db->query( "TRUNCATE TABLE conversions" );
		$this->db->query( "TRUNCATE TABLE allocations" );
		$this->db->query( "TRUNCATE TABLE allocation_items" );
		$this->db->query( "TRUNCATE TABLE mopping" );
		$this->db->query( "TRUNCATE TABLE mopping_items" );
		$this->db->query( "TRUNCATE TABLE item_categories" );

		$this->db->query( "SET FOREIGN_KEY_CHECKS = OFF" );

		$this->create_default_data( $this->input->get() );
		//$this->create_test_data();

		$this->db->trans_complete();
		echo heading( 'Database has been reset..', 3 );
        flush();
	}

	public function drop_database()
	{
		heading( 'Dropping database...', 3 );
        flush();
		$this->db->query( "DROP DATABASE frogims" );
	}

}