CREATE DATABASE IF NOT EXISTS frogims;

USE frogims;

CREATE TABLE IF NOT EXISTS stations
(
    id INTEGER AUTO_INCREMENT NOT NULL,
    station_name VARCHAR(50) NOT NULL,
    station_short_name VARCHAR(10) NOT NULL,
    PRIMARY KEY (id)
)
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shifts
(
    id INTEGER AUTO_INCREMENT NOT NULL,
    shift_num VARCHAR(10) NOT NULL,
    store_type INTEGER NOT NULL,
    description TEXT,
    shift_start_time TIME NOT NULL,
    shift_end_time TIME NOT NULL,
    PRIMARY KEY (id)
)
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS groups
(
	id INTEGER AUTO_INCREMENT NOT NULL,
	group_name VARCHAR(100) NOT NULL,
	group_perm_transaction VARCHAR(4) NOT NULL DEFAULT 'none',
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
	last_modified INTEGER NOT NULL,
	PRIMARY KEY( id ),
	UNIQUE groups_undx ( group_name )
)
ENGINE=InnoDB;

-- user_status: 1 - active, 2 - locked
-- user_role: 1 - administrator, 2 - standard user
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
	last_modified INTEGER NOT NULL,
	PRIMARY KEY (id),
	UNIQUE users_undx (username),
	FOREIGN KEY users_group_fk (group_id) REFERENCES groups (id)
		ON UPDATE CASCADE
		ON DELETE SET NULL
)
ENGINE=InnoDB;

-- store_type: 1 - General, 2 - Production, 3 - TGM, 4 - Cashroom
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
	PRIMARY KEY (id),
	UNIQUE stores_undx (store_code)
)
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS store_users
(
	id INTEGER AUTO_INCREMENT NOT NULL,
	store_id INTEGER NOT NULL,
	user_id INTEGER NOT NULL,
	date_joined DATETIME NOT NULL,
	PRIMARY KEY (id),
	FOREIGN KEY store_users_store_fk (store_id) REFERENCES stores (id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY store_users_user_fk (user_id) REFERENCES users (id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
)
ENGINE=InnoDB;

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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

-- transaction_type: 10 - transfer out, 11 - transfer in, 12 - transfer cancel
--                   20 - allocation, 21 - return loose,
--                   30 - mopping
--                   40 - adjustment
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
ENGINE=InnoDB;

-- adjustment_type: 1 - actual count
-- adjustment_status: 1 - pending, 2 - approved, 3 - cancelled
CREATE TABLE IF NOT EXISTS adjustments
(
	id INTEGER AUTO_INCREMENT NOT NULL,
	store_inventory_id INTEGER NOT NULL,
    adjustment_shift INTEGER NOT NULL,
	adjustment_type SMALLINT NOT NULL,
	adjusted_quantity INTEGER NULL,
	previous_quantity INTEGER NULL,
	reason VARCHAR(255) NOT NULL,
	adjustment_status SMALLINT NOT NULL DEFAULT 1,
	adjustment_timestamp DATETIME NOT NULL,
	user_id INTEGER NOT NULL,
	adj_transaction_type SMALLINT NULL DEFAULT NULL,
	adj_transaction_id INTEGER NULL DEFAULT NULL,
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
ENGINE=InnoDB;

-- transfer_status: 1 - pending out, 2 - transfer approved, 3 - received, 4 - cancelled
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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

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
	transval_category INTEGER NOT NULL DEFAULT 1,
	transval_status SMALLINT NOT NULL DEFAULT 1,
	date_created DATETIME NOT NULL,
	date_modified TIMESTAMP NOT NULL,
	last_modified INTEGER NOT NULL,
	PRIMARY KEY (id),
	FOREIGN KEY transval_items_transfer_fk (transval_transfer_id) REFERENCES transfers (id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
)
ENGINE=InnoDB;

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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

-- assignee_type: 1 - station teller, 2- TVM
-- allocation_status: 1 - pending, 2 - allocated, 3 - remitted, 4 - cancelled
CREATE TABLE IF NOT EXISTS allocations
(
	id INTEGER AUTO_INCREMENT NOT NULL,
	store_id INTEGER NOT NULL,
	business_date DATE NOT NULL,
	station_id SMALLINT NULL,
    shift_id INTEGER NOT NULL,
	assignee VARCHAR(50) NULL,
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
ENGINE=InnoDB;

-- allocation_item_status: 1 - scheduled, 2 - allocated, 3 - remitted, 4 - voided
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
ENGINE=InnoDB;

-- TODO: Foreign keys for shift_id, cashier_shift_id
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
ENGINE=InnoDB;

-- mopping_type: 1 - regular,
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
	last_modified INTEGER NOT NULL,
	PRIMARY KEY (id),
	FOREIGN KEY mopping_items_fk (mopping_id) REFERENCES mopping (id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
	FOREIGN KEY mopping_items_item_fk (mopped_item_id) REFERENCES items (id)
		ON UPDATE CASCADE
		ON DELETE RESTRICT
)
ENGINE=InnoDB;


-- category_type: 1 - allocation, 2 - remittance
-- category_status: 0 - inactive, 1 - in use
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
ENGINE=InnoDB;