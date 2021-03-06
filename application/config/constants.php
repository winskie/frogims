<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE')  OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE')   OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE')  OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ')                           OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESCTRUCTIVE') OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE')                   OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS')					OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')						OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')					OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')		OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS') 	OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD')	OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')			OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')				OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')				OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')				OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code

// Date/Time formats
defined('TIMESTAMP_FORMAT')	OR define('TIMESTAMP_FORMAT', 'Y-m-d H:i:s');
defined('DATE_FORMAT')			OR define('DATE_FORMAT', 'Y-m-d');
defined('TIME_FORMAT')			OR define('TIME_FORMAT', 'H:i:s');


// User roles
defined('USER_ROLE_ADMIN')	OR define('USER_ROLE_ADMIN', 1);
defined('USER_ROLE_USER')		OR define('USER_ROLE_USER', 2);

// User status
defined('USER_STATUS_ACTIVE')	OR define('USER_STATUS_ACTIVE', 1);
defined('USER_STATUS_LOCKED')	OR define('USER_STATUS_LOCKED', 2);

// Store Types
defined('STORE_TYPE_GENERAL')			OR define('STORE_TYPE_GENERAL', 1);
defined('STORE_TYPE_PRODUCTION')	OR define('STORE_TYPE_PRODUCTION', 2);
defined('STORE_TYPE_TRANSPORT')		OR define('STORE_TYPE_TRANSPORT', 3);
defined('STORE_TYPE_CASHROOM')		OR define('STORE_TYPE_CASHROOM', 4);

// Transaction types
defined('TRANSACTION_INIT')											OR define('TRANSACTION_INIT', 0 );
defined('TRANSACTION_TRANSFER_OUT')							OR define('TRANSACTION_TRANSFER_OUT', 10);
defined('TRANSACTION_TRANSFER_IN')							OR define('TRANSACTION_TRANSFER_IN', 11);
defined('TRANSACTION_TRANSFER_CANCEL')					OR define('TRANSACTION_TRANSFER_CANCEL', 12);
defined('TRANSACTION_TRANSFER_VOID')						OR define('TRANSACTION_TRANSFER_VOID', 13);
defined('TRANSACTION_ALLOCATION')								OR define('TRANSACTION_ALLOCATION', 20);
defined('TRANSACTION_REMITTANCE')								OR define('TRANSACTION_REMITTANCE', 21);
defined('TRANSACTION_ALLOCATION_VOID')					OR define('TRANSACTION_ALLOCATION_VOID', 22);
defined('TRANSACTION_REMITTANCE_VOID')					OR define('TRANSACTION_REMITTANCE_VOID', 23);
defined('TRANSACTION_MOPPING_COLLECTION')				OR define('TRANSACTION_MOPPING_COLLECTION', 30);
defined('TRANSACTION_MOPPING_COLLECTION_VOID')	OR define('TRANSACTION_MOPPING_COLLECTION_VOID', 31);
defined('TRANSACTION_MOPPING_ISSUANCE')					OR define('TRANSACTION_MOPPING_ISSUANCE', 32);
defined('TRANSACTION_MOPPING_ISSUANCE_VOID')		OR define('TRANSACTION_MOPPING_ISSUANCE_VOID', 33);
defined('TRANSACTION_ADJUSTMENT')								OR define('TRANSACTION_ADJUSTMENT', 40);
defined('TRANSACTION_CONVERSION_FROM')					OR define('TRANSACTION_CONVERSION_FROM', 50);
defined('TRANSACTION_CONVERSION_TO')						OR define('TRANSACTION_CONVERSION_TO', 51);

// Transfer validation status
defined('TRANSFER_VALIDATION_ONGOING')			OR define('TRANSFER_VALIDATION_ONGOING', 1);
defined('TRANSFER_VALIDATION_COMPLETED')		OR define('TRANSFER_VALIDATION_COMPLETED', 2);
defined('TRANSFER_VALIDATION_NOTREQUIRED')	OR define('TRANSFER_VALIDATION_NOTREQUIRED', 3);

// Transfer validation receipt status
defined('TRANSFER_VALIDATION_RECEIPT_VALIDATED')		OR define('TRANSFER_VALIDATION_RECEIPT_VALIDATED', 1);
defined('TRANSFER_VALIDATION_RECEIPT_RETURNED')			OR define('TRANSFER_VALIDATION_RECEIPT_RETURNED', 2);

// Transfer validation transfer status
defined('TRANSFER_VALIDATION_TRANSFER_VALIDATED')	OR define('TRANSFER_VALIDATION_TRANSFER_VALIDATED', 1);
defined('TRANSFER_VALIDATION_TRANSFER_DISPUTED')	OR define('TRANSFER_VALIDATION_TRANSFER_DISPUTED', 2);

// Transfer status
defined('TRANSFER_PENDING')							OR define('TRANSFER_PENDING', 1);
defined('TRANSFER_APPROVED')						OR define('TRANSFER_APPROVED', 2);
defined('TRANSFER_RECEIVED')						OR define('TRANSFER_RECEIVED', 3);
defined('TRANSFER_PENDING_CANCELLED')		OR define('TRANSFER_PENDING_CANCELLED', 4);
defined('TRANSFER_APPROVED_CANCELLED')	OR define('TRANSFER_APPROVED_CANCELLED', 5);

// Transfer item status
defined('TRANSFER_ITEM_SCHEDULED')	OR define('TRANSFER_ITEM_SCHEDULED', 1);
defined('TRANSFER_ITEM_APPROVED')		OR define('TRANSFER_ITEM_APPROVED', 2);
defined('TRANSFER_ITEM_RECEIVED')		OR define('TRANSFER_ITEM_RECEIVED', 3);
defined('TRANSFER_ITEM_CANCELLED')	OR define('TRANSFER_ITEM_CANCELLED', 4);
defined('TRANSFER_ITEM_VOIDED')			OR define('TRANSFER_ITEM_VOIDED', 5);

// Adjustment status
defined('ADJUSTMENT_PENDING')		OR define('ADJUSTMENT_PENDING', 1);
defined('ADJUSTMENT_APPROVED')	OR define('ADJUSTMENT_APPROVED', 2);
defined('ADJUSTMENT_CANCELLED')	OR define('ADJUSTMENT_CANCELLED', 3);

// Mopping item status
defined('MOPPING_ITEM_COLLECTED')	OR define('MOPPING_ITEM_COLLECTED', 1);
defined('MOPPING_ITEM_VOIDED')		OR define('MOPPING_ITEM_VOIDED', 2);

// User status
defined('USER_STATUS_ACTIVE')		OR define('USER_STATUS_ACTIVE', 1);
defined('USER_STATUS_LOCKED')		OR define('USER_STATUS_LOCKED', 2);
defined('USER_STATUS_DELETED')	OR define('USER_STATUS_DELETED', 3);

// Conversion status
defined('CONVERSION_PENDING')		OR define('CONVERSION_PENDING', 1);
defined('CONVERSION_APPROVED')	OR define('CONVERSION_APPROVED', 2);
defined('CONVERSION_CANCELLED')	OR define('CONVERSION_CANCELLED', 3);

// Allocation status
defined('ALLOCATION_SCHEDULED')	OR define('ALLOCATION_SCHEDULED', 1);
defined('ALLOCATION_ALLOCATED')	OR define('ALLOCATION_ALLOCATED', 2);
defined('ALLOCATION_REMITTED')	OR define('ALLOCATION_REMITTED', 3);
defined('ALLOCATION_CANCELLED') OR define('ALLOCATION_CANCELLED', 4);

// Categories
defined('CATEGORY_TRANSFER')		OR define('CATEGORY_ALLOCATION', 10);
defined('CATEGORY_ALLOCATION')	OR define('CATEGORY_ALLOCATION', 20);
defined('CATEGORY_REMITTANCE')	OR define('CATEGORY_REMITTANCE', 30);

// Allocation item status
defined('ALLOCATION_ITEM_SCHEDULED')	OR define('ALLOCATION_ITEM_SCHEDULED', 10);
defined('ALLOCATION_ITEM_ALLOCATED')	OR define('ALLOCATION_ITEM_ALLOCATED', 11);
defined('ALLOCATION_ITEM_CANCELLED')	OR define('ALLOCATION_ITEM_CANCELLED', 12);
defined('ALLOCATION_ITEM_VOIDED')			OR define('ALLOCATION_ITEM_VOIDED', 13);

defined('REMITTANCE_ITEM_PENDING')	OR define('REMITTANCE_ITEM_PENDING', 20);
defined('REMITTANCE_ITEM_REMITTED')	OR define('REMITTANCE_ITEM_REMITTED', 21);
defined('REMITTANCE_ITEM_VOIDED')		OR define('REMITTANCE_ITEM_VOIDED', 22);

defined('TICKET_SALE_ITEM_PENDING')  OR define('TICKET_SALE_ITEM_PENDING', 30);
defined('TICKET_SALE_ITEM_RECORDED') OR define('TICKET_SALE_ITEM_RECORDED', 31);
defined('TICKET_SALE_ITEM_VOIDED')   OR define('TICKET_SALE_ITEM_VOIDED', 32);

// Allocation sales item types
defined('SALES_ITEM_PENDING')		OR define('SALES_ITEM_PENDING', 10);
defined('SALES_ITEM_RECORDED')	OR define('SALES_ITEM_RECORDED', 11);
defined('SALES_ITEM_VOIDED')		OR define('SALES_ITEM_VOIDED', 12);

// Adjustment types
defined('ADJUSTMENT_TYPE_ACTUAL')	OR define('ADJUSTMENT_TYPE_ACTUAL', 1);

// Mopped item status
defined('MOPPED_ITEM_OK')		OR define('MOPPED_ITEM_OK', 1);
defined('MOPPED_ITEM_VOID')	OR define('MOPPED_ITEM_VOID', 2);

// Allocation assignee types
defined('ALLOCATION_ASSIGNEE_TELLER')  OR define('ALLOCATION_ASSIGNEE_TELLER', 1);
defined('ALLOCATION_ASSIGNEE_MACHINE') OR define('ALLOCATION_ASSIGNEE_MACHINE', 2);

// Allocation item types
defined('ALLOCATION_ITEM_TYPE_ALLOCATION')	OR define('ALLOCATION_ITEM_TYPE_ALLOCATION', 1);
defined('ALLOCATION_ITEM_TYPE_REMITTANCE')	OR define('ALLOCATION_ITEM_TYPE_REMITTANCE', 2);
defined('ALLOCATION_ITEM_TYPE_SALES')				OR define('ALLOCATION_ITEM_TYPE_SALES', 3);

// Transfer categories
defined('TRANSFER_CATEGORY_EXTERNAL')            OR define('TRANSFER_CATEGORY_EXTERNAL', 1);
defined('TRANSFER_CATEGORY_INTERNAL')            OR define('TRANSFER_CATEGORY_INTERNAL', 2);
defined('TRANSFER_CATEGORY_TURNOVER')            OR define('TRANSFER_CATEGORY_TURNOVER', 3);
defined('TRANSFER_CATEGORY_REPLENISHMENT')       OR define('TRANSFER_CATEGORY_REPLENISHMENT', 4);
defined('TRANSFER_CATEGORY_BLACKBOX')            OR define('TRANSFER_CATEGORY_BLACKBOX', 5);
defined('TRANSFER_CATEGORY_BILLS_TO_COINS')      OR define('TRANSFER_CATEGORY_BILLS_TO_COINS', 6);
defined('TRANSFER_CATEGORY_CSC_APPLICATION')     OR define('TRANSFER_CATEGORY_CSC_APPLICATION', 7);
defined('TRANSFER_CATEGORY_BANK_DEPOSIT')        OR define('TRANSFER_CATEGORY_BANK_DEPOSIT', 8);
defined('TRANSFER_CATEGORY_ADD_TVMIR')           OR define('TRANSFER_CATEGORY_ADD_TVMIR', 9);
defined('TRANSFER_CATEGORY_ISSUE_TVMIR')         OR define('TRANSFER_CATEGORY_ISSUE_TVMIR', 10);
defined('TRANSFER_CATEGORY_REPLENISH_TVM_CFUND') OR define('TRANSFER_CATEGORY_REPLENISH_TVM_CFUND', 11);

// Shift turnover status
defined('SHIFT_TURNOVER_OPEN')		OR define('SHIFT_TURNOVER_OPEN', 1);
defined('SHIFT_TURNOVER_CLOSED')	OR define('SHIFT_TURNOVER_CLOSED', 2);

// Item types
defined('ITEM_NON_REUSABLE')	OR define('ITEM_NON_REUSABLE', 0);
defined('ITEM_REUSABLE')			OR define('ITEM_REUSABLE', 1);


// Funds
defined('FUND_CHANGE_FUND')   OR define('FUND_CHANGE_FUND', 'Change Fund');
defined('FUND_SALES')         OR define('FUND_SALES', 'Sales');
defined('FUND_IN_TRANSIT')    OR define('FUND_IN_TRANSIT', 'In Transit');
defined('FUND_COIN_ACCEPTOR') OR define('FUND_COIN_ACCEPTOR', 'CA Fund');
defined('FUND_TVM_HOPPER')    OR define('FUND_TVM_HOPPER', 'TVM Hopper');
defined('FUND_CSC_CARD_FEE')  OR define('FUND_CSC_CARD_FEE', 'CSC Card Fee');
defined('FUND_TVMIR')         OR define('FUND_TVMIR', 'TVMIR');