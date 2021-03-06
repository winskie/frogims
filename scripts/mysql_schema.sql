CREATE DATABASE IF NOT EXISTS frogims;

USE frogims;

-- Date Dimension
CREATE TABLE ints ( i tinyint );
INSERT INTO ints VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9);

CREATE TABLE IF NOT EXISTS date_dim (
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
	locked BOOL DEFAULT FALSE
)
ENGINE=InnoDB;

INSERT INTO date_dim (dt)
SELECT DATE('2015-01-01') + INTERVAL a.i*10000 + b.i*1000 + c.i*100 + d.i*10 + e.i DAY
FROM ints a JOIN ints b JOIN ints c JOIN ints d JOIN ints e
WHERE (a.i*10000 + b.i*1000 + c.i*100 + d.i*10 + e.i) <= 11322
ORDER BY 1;

UPDATE date_dim
SET isWeekday = CASE WHEN dayofweek(dt) IN (1,7) THEN 0 ELSE 1 END,
	isHoliday = 0,
	y = YEAR(dt),
	q = quarter(dt),
	m = MONTH(dt),
	d = dayofmonth(dt),
	dw = dayofweek(dt),
	monthname = monthname(dt),
	dayname = dayname(dt),
	w = week(dt);

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
	shift_num VARCHAR(15) NOT NULL,
	store_type INTEGER NOT NULL,
	description TEXT,
	shift_start_time TIME NOT NULL,
	shift_end_time TIME NOT NULL,
	shift_next_shift_id INTEGER NULL DEFAULT NULL,
	shift_order SMALLINT NOT NULL DEFAULT 1,
	PRIMARY KEY (id)
)
ENGINE=InnoDB;

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
	created_by INTEGER NOT NULL,
	modified_by INTEGER NOT NULL,
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
	store_station_id SMALLINT NULL,
	store_location VARCHAR(100) NOT NULL,
	store_contact_number VARCHAR(25) NULL,
	date_created DATETIME NOT NULL,
	date_modified DATETIME NOT NULL,
	created_by INTEGER NOT NULL,
	modified_by INTEGER NOT NULL,
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
	PRIMARY KEY( id )
)
ENGINE=InnoDB;

-- item_type: 0 - defective, 1 - usable
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
	teller_saleable BOOLEAN NOT NULL DEFAUL 0,
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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS store_inventory
(
	id INTEGER AUTO_INCREMENT NOT NULL,
	store_id INTEGER NOT NULL,
	item_id INTEGER NOT NULL,
	parent_item_id INTEGER NULL DEFAULT NULL,
	quantity DECIMAL(15,2) NOT NULL DEFAULT 0,
	quantity_timestamp DATETIME NOT NULL,
	buffer_level DECIMAL(15,2) NOT NULL DEFAULT 0,
	reserved DECIMAL(15,2) NOT NULL DEFAULT 0,
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
ENGINE=InnoDB;

-- st_status: 1 - open, 2 - close
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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

-- transaction_type: 10 - transfer out, 11 - transfer in, 12 - transfer cancel
--                   20 - allocation, 21 - remittance,
--                   30 - mopping
--                   40 - adjustment
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
		ON DELETE RESTRICT,
	FOREIGN KEY transactions_category_fk (transaction_category_id) REFERENCES categories(id)
		ON UPDATE CASCADE
		ON DELETE SET NULL
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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

-- transfer_status: 1 - pending out, 2 - transfer approved, 3 - received, 4 - cancelled
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
	transfer_tvm_id VARCHAR(50) NULL,
	transfer_init_shift_id INTEGER NOT NULL,
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
	INDEX transfers_main_ndx ( transfer_datetime, transfer_category, origin_id, destination_id ),
	INDEX transfers_date_status_ndx ( transfer_datetime, transfer_status )
)
ENGINE=InnoDB;


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
	transfer_item_allocation_id INTEGER NULL DEFAULT NULL, /* Used in tracking bank deposit */
	transfer_item_allocation_item_id INTEGER NULL DEFAULT NULL, /* Used in tracking shift ticket turnovers */
	transfer_item_transfer_item_id INTEGER NULL DEFAULT NULL, /* Used in tracking blackbox receipts */
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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

-- assignee_type: 1 - station teller, 2 - TVM
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
	created_by INTEGER NOT NULL,
	modified_by INTEGER NOT NULL,
	PRIMARY KEY (id),
	FOREIGN KEY allocations_store_fk (store_id) REFERENCES stores (id)
		ON UPDATE CASCADE
		ON DELETE RESTRICT,
	INDEX allocations_main_ndx (business_date, shift_id, station_id)
)
ENGINE=InnoDB;

-- allocation_item_status: 1 - scheduled, 2 - allocated, 3 - remitted, 4 -
-- allocation_item_type: 1 - allocation, 2 - remittance, 3 - sales
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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

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
	created_by INTEGER NOT NULL,
	modified_by INTEGER NOT NULL,
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
ENGINE=InnoDB;


-- cat_status: 0 - inactive, 1 - in use
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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

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
ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tvm_readings
(
	id INTEGER AUTO_INCREMENT NOT NULL,
	tvmr_store_id INTEGER NOT NULL,
	tvmr_machine_id VARCHAR(50) NOT NULL,
	tvmr_date DATE NOT NULL,
	tvmr_time TIME NOT NULL,
	tvmr_shift_id INTEGER NOT NULL,
	tvmr_cashier_id INTEGER NOT NULL,
	tvmr_cashier_name VARCHAR(100) NOT NULL,
	tvmr_type VARCHAR(100) NOT NULL,
	tvmr_reference_num VARCHAR(15) NULL,
	tvmr_reading DECIMAL(15,2) NOT NULL DEFAULT 0.00,
	tvmr_previous_reading DECIMAL(15,2) NOT NULL DEFAULT 0.00,
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
		ON DELETE RESTRICT,
	INDEX tvm_readings_ndx ( tvmr_store_id, tvmr_date, tvmr_shift_id, tvmr_machine_id, tvmr_type )
)
ENGINE=InnoDB;

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
ENGINE=InnoDB;

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
ENGINE=InnoDB;