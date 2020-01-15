#!/usr/bin/php
<?php
// mysqldbcompare --server1=tim:omu2tbdf@localhost --server2=tim:omu2tbdf@localhost weberpdemo:KwaMoja  --changes-for=server1 --difftype=sql --run-all-tests
/* Copyright (C) 2017 Tim Schofield - All Rights Reserved
 * You may use, distribute and modify this code under the
 * terms of the GPL v2 license.
 *
 * For a copy of the GPL v2 license please write to:
 * tim@weberpafrica.com
 */

/* Get the name of the temporary file that Git will use
 * to contain the commit message
 */

function DB_query($db, $SQL) {
	$Result = mysqli_query($db, $SQL);
	if (mysqli_errno($db) != 0) {
		echo "\n" . 'Database error number ' . mysqli_errno($db);
		echo "\n" . 'Failed SQL is ' . $SQL;
//		echo "\n" . 'Attempting to roll back the transactions......' . "\n";
//		mysqli_query($db, 'ROLLBACK');
		echo "\n\e[0;31m" . mysqli_error($db) . "\e[0m\n";
		echo "\e[0;31mAborting....\e[0m\n";
		exit;
	}
	echo '.';
	return $Result;
}

function recurse_copy($src, $dst) {
	$dir = opendir($src);
	@mkdir($dst);
	while(false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				recurse_copy($src . '/' . $file, $dst . '/' . $file);
			} else {
				copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
	}
	closedir($dir);
	echo '.';
}

function delete_directory($dirname) {
	if (is_dir($dirname)) {
		$dir_handle = opendir($dirname);
	}
	if (!$dir_handle) {
		return false;
	}
	while($file = readdir($dir_handle)) {
		if ($file != "." && $file != "..") {
			if (!is_dir($dirname."/".$file)) {
				unlink($dirname."/".$file);
			} else {
				delete_directory($dirname.'/'.$file);
			}
		}
	}
	closedir($dir_handle);
	rmdir($dirname);
	return true;
}

echo  "\n";
echo '************************************************************************' . "\n";
echo  "\n";
echo 'This script is designed to convert a webERP database into a KwaMoja' . "\n" .
     'database.' . "\n";
echo 'It performs other housework on your new KwaMoja instance to enable it to' . "\n" .
     'run with your webERP data.' . "\n";
echo 'To run this script you will need to have both the webERP and the KwaMoja' . "\n" .
     'code installed on the same machine that you are running this script.' . "\n";
echo 'This script assumes that the database user and password in the config.php' . "\n" .
     'file in your webERP instance has permissions to CREATE and DROP databases.' . "\n" .
     'If this is not so, you should temporarily make it so. Please see the mysql' . "\n" .
     'documentation if you are unsure how to do this.' . "\n";
echo 'This script takes 3 parameters. The first is the name of the webERP' . "\n" .
	 'to be converted. This database will not be altered, and the new database' . "\n" .
	 'will have this name but will have a suffix of _1 attached to it. eg If' . "\n" .
	 'your webERP database is called weberp then the converted database will be' . "\n" .
	 'called weberp_1.' . "\n" .
	 "\e[0;31mWARNING - If a database already exists with this name then it will be deleted.\e[0m\n";
echo 'The second parameter is the path to the webERP code. This can be an' . "\n" .
     'absolute path, or a path relative to the directory that the script is' . "\n" .
     'being run from.' . "\n";
echo 'The third parameter is the path to the KwaMoja code. This can be an' . "\n" .
     'absolute path, or a path relative to the directory that the script is' . "\n" .
     'being run from.' . "\n";
echo 'So running the script in the web root, with a webERP database named weberp' . "\n" .
     'the command would look something like:' . "\n\n";
echo 'ConvertwebERPToKwaMoja.php weberp webERP/ KwaMoja/' . "\n\n";
echo "\e[0;31mWARNING - Please note. Every effort has been made to ensure this script runs safely.\e[0m\n";
echo "\e[0;31mHowever the KwaMoja team accepts no responsibility for any data loss that might occur.\e[0m\n";
echo "\e[0;31mIt is your responsibility to ensure you have backed up all your data correctly.\e[0m\n";
echo  "\n";
echo '************************************************************************' . "\n";
echo  "\n";
echo 'Enter y to continue....';

$Handle = fopen ("php://stdin","r");
$Line = fgets($Handle);
if(trim($Line) != 'y'){
    exit;
}
echo "\n";

$WebERPDB_orig = $argv[1];

$WebERPPath = $argv[2];
$KwaMojaPath = $argv[3];

include($WebERPPath . '/config.php');

/* Firstly copy the weberp database with the suffix of _1 */
echo 'Making a copy of the ' . $WebERPDB_orig . ' database.....';
exec('mysqldump -u' . $DBUser . ' -p' . $DBPassword . ' ' . $WebERPDB_orig . ' > /tmp/' . $WebERPDB_orig . '_1.sql', $Output, $ReturnValue);
if ($ReturnValue == 0) {
	echo "\e[0;32mOK!\e[0m\n";
} else {
	echo "\n" . 'Cannot make a copy of your database.' . "\e[0;31mAborting....\e[0m\n";
	exit;
}

/* Then create the new database that will become the basis of the converted DB */
echo 'Making a new database called ' . $WebERPDB_orig . '_1.....';
exec('mysql -u' . $DBUser . ' -p' . $DBPassword . ' -e "DROP DATABASE IF EXISTS '  . $WebERPDB_orig . '_1 /*\!40100 DEFAULT CHARACTER SET utf8 */;"', $Output, $ReturnValue);
exec('mysql -u' . $DBUser . ' -p' . $DBPassword . ' -e "CREATE DATABASE ' . $WebERPDB_orig . '_1 /*\!40100 DEFAULT CHARACTER SET utf8 */;"', $Output, $ReturnValue);
if ($ReturnValue == 0) {
	echo "\e[0;32mOK!\e[0m\n";
} else {
	echo "\n" . 'Cannot make a new database.' . "\e[0;31mAborting....\e[0m\n";
	exit;
}

/* Then populate the new database with the data backed up earlier */
echo 'Populating the ' . $WebERPDB_orig . '_1 database with the original data....';
exec('mysql -u' . $DBUser . ' -p' . $DBPassword . ' ' . $WebERPDB_orig . '_1 < /tmp/' . $WebERPDB_orig . '_1.sql', $Output, $ReturnValue);
if ($ReturnValue == 0) {
	echo "\e[0;32mOK!\e[0m\n";
} else {
	echo "\n" . 'Cannot make a new database.' . "\e[0;31mAborting....\e[0m\n";
	exit;
}

$WebERPDB = $WebERPDB_orig . '_1';

unlink('/tmp/' . $WebERPDB_orig . '_1.sql');

$db = mysqli_connect('p:' . $host, $DBUser, $DBPassword, $WebERPDB, $mysqlport);
if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	session_unset();
	session_destroy();
	echo '<p>' . _('Click') . ' ' . '<a href="index.php">' . _('here') . '</a>' . ' ' . _('to try logging in again') . '</p>';
	exit();
}

//mysqli_query($db, 'SET autocommit=0');
//mysqli_query($db, 'START TRANSACTION');

$KwaMojaDB = 'temp_kwamoja';

/* Create the empty KwaMoja database */

echo 'Creating an empty KwaMoja database.....';

$SQL = "DROP DATABASE IF EXISTS " . $KwaMojaDB;
$Result = DB_query($db, $SQL);
$SQL = "CREATE DATABASE " . $KwaMojaDB;
$Result = DB_query($db, $SQL);
$SQL = "USE " . $KwaMojaDB;
$Result = DB_query($db, $SQL);

$SQLScriptFile = file($KwaMojaPath . 'install/db/structure.sql');
$ScriptFileEntries = sizeof($SQLScriptFile);
$SQL = '';
$InAFunction = false;
DB_query($db, 'SET FOREIGN_KEY_CHECKS=0');
for ($i = 0;$i < $ScriptFileEntries;$i++) {

	$SQLScriptFile[$i] = trim($SQLScriptFile[$i]);
	//ignore lines that start with -- or USE or /*
	if (mb_substr($SQLScriptFile[$i], 0, 2) != '--' and mb_strstr($SQLScriptFile[$i], '/*') == false and mb_strlen($SQLScriptFile[$i]) > 1) {

		$SQL.= ' ' . $SQLScriptFile[$i];

		if (mb_strpos($SQLScriptFile[$i], ';') > 0 and !$InAFunction) {
			// Database created above with correct name.
			if (strncasecmp($SQL, ' CREATE DATABASE ', 17) and strncasecmp($SQL, ' USE ', 5)) {
				$SQL = mb_substr($SQL, 0, mb_strlen($SQL) - 1);
				$Result = DB_query($db, $SQL);
			}
			$SQL = '';
		}

	} //end if its a valid sql line not a comment

} //end of for loop around the lines of the sql script
echo "\e[0;32mOK!\e[0m\n";

/* End database structure */

$NewTablesToCreate = array('prlemptaxfile',
							'prlloandeduction',
							'stocklongdescriptiontranslations',
							'timesheets',
							'prlemphdmffile',
							'stocktypes',
							'prlemployeemaster',
							'dashboard_users',
							'care_encounter',
							'salescommissionrates',
							'projectbudgetdetails',
							'prlloantable',
							'prlsstable',
							'prlpayrolltrans',
							'prlemploymentstatus',
							'care_person',
							'asteriskdata',
							'menuitems',
							'prlpayperiod',
							'care_encounter_location',
							'prltaxtablerate',
							'prlloanfile',
							'prlempsssfile',
							'prlothincfile',
							'prlhdmftable',
							'unitsofdimension',
							'care_type_location',
							'gltags',
							'mrpsupplies',
							'prlottrans',
							'salescommissions',
							'prlpayrollperiod',
							'abcmethods',
							'pctags',
							'salesorderattachments',
							'suppliergroups',
							'prlovertimetable',
							'prlempphfile',
							'abcstock',
							'projectcharges',
							'projectbudgets',
							'prlphilhealth',
							'container',
							'prlothinctable',
							'projectreqts',
							'care_ward',
							'jobcards',
							'abcgroups',
							'care_room',
							'salescommissiontypes',
							'schedule',
							'custbranchattachments',
							'donors',
							'projects',
							'prltaxstatus',
							'projectbom',
							'modules',
							'telecomrates',
							'stockcosts',
							'prldailytrans',
							'prltaxtablerate2',
							'dashboard_scripts');

echo 'Creating new tables present in KwaMoja but not in webERP.....';
foreach ($NewTablesToCreate as $NewTableName) {
	/* First make sure the tables don't exist already */
	$SQL = "DROP TABLE IF EXISTS " . $WebERPDB . "." . $NewTableName;
	$Result = DB_query($db, $SQL);

	/* Then copy over the structure from the empty KwaMoja database */
	$SQL = "RENAME TABLE " . $KwaMojaDB . "." . $NewTableName . " TO " . $WebERPDB . "." . $NewTableName;
	$Result = DB_query($db, $SQL);
}
echo "\e[0;32mOK!\e[0m\n";

echo 'Populating the new tables with data where the data is available....';
$SQL = "INSERT INTO " . $WebERPDB . ".stocklongdescriptiontranslations (SELECT stockid, language_id, longdescriptiontranslation, needsrevision FROM " . $WebERPDB . ".stockdescriptiontranslations)";
$Result = DB_query($db, $SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".stocktypes VALUES('D', 'Dummy Item - (No Movements)', 0)";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".stocktypes VALUES('F', 'Finished Goods', 1)";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".stocktypes VALUES('L', 'Labour', 0)";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".stocktypes VALUES('M', 'Raw Materials', 1)";
$Result = DB_query($db, $SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".dashboard_users (SELECT NULL, userid, '' FROM " . $WebERPDB . ".www_users)";
$Result = DB_query($db, $SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".container (SELECT loccode, CONCAT('Primary location for warehouse-', loccode), loccode, '', 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0 FROM " . $WebERPDB . ".locations)";
$Result = DB_query($db, $SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (1,'total_dashboard.php',1,'Shows total for sales, purchase and outstanding orders')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (2,'customer_orders.php',3,'Shows latest customer orders that have been placed.')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (3,'unpaid_invoice.php',1,'Shows Outstanding invoices')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (6,'latest_stock_status.php',1,'Shows latest stock status')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (7,'work_orders.php',1,'Shows latest work orders')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (8,'mrp_dashboard.php',1,'Shows latest MRP')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (9,'bank_trans.php',1,'Shows latest bank transactions')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (11,'latest_po.php',2,'Shows latest supplier orders that have been placed.')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (13,'latest_po_auth.php',2,'Shows latest supplier orders awaiting authorisation.')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`dashboard_scripts` VALUES (14,'latest_grns.php',4,'Shows latest goods received into the company')";
$Result = DB_query($db, $SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".`modules` VALUES (0,'AP','ap','Payables',3),
									(0,'AR','ar','Receivables',2),
									(0,'FA','fa','Asset Manager',11),
									(0,'GL','gl','General Ledger',7),
									(0,'HR','hr','Human Resources',9),
									(0,'manuf','man','Manufacturing',6),
									(0,'orders','ord','Sales',1),
									(0,'PC','pc','Petty Cash',12),
									(0,'pjct','pjct','Project Accounting',10),
									(0,'PO','prch','Purchases',4),
									(0,'qa','qa','Quality Assurance',8),
									(0,'stock','inv','Inventory',5),
									(0,'system','sys','Setup',13),
									(0,'Utilities','util','Utilities',14),
									(1,'AP','ap','Payables',4),
									(1,'AR','ar','Receivables',2),
									(1,'FA','fa','Asset Manager',12),
									(1,'GL','gl','General Ledger',7),
									(1,'hospital','hosp','Hospital',9),
									(1,'HR','hr','Human Resources',10),
									(1,'manuf','man','Manufacturing',6),
									(1,'orders','ord','Sales',1),
									(1,'PC','pc','Petty Cash',13),
									(1,'pjct','pjct','Project Accounting',11),
									(1,'PO','prch','Purchases',3),
									(1,'qa','qa','Quality Assurance',8),
									(1,'stock','inv','Inventory',5),
									(1,'system','sys','Setup',14),
									(1,'Utilities','util','Utilities',15),
									(2,'AP','ap','Payables',3),
									(2,'AR','ar','Receivables',2),
									(2,'FA','fa','Asset Manager',12),
									(2,'GL','gl','General Ledger',7),
									(2,'hospital','hosp','Hospital',9),
									(2,'HR','hr','Human Resources',10),
									(2,'manuf','man','Manufacturing',6),
									(2,'orders','ord','Sales',1),
									(2,'PC','pc','Petty Cash',13),
									(2,'pjct','pjct','Project Accounting',11),
									(2,'PO','prch','Purchases',4),
									(2,'qa','qa','Quality Assurance',8),
									(2,'stock','inv','Inventory',5),
									(2,'system','sys','Setup',14),
									(2,'Utilities','util','Utilities',15),
									(3,'AP','ap','Payables',3),
									(3,'AR','ar','Receivables',2),
									(3,'FA','fa','Asset Manager',12),
									(3,'GL','gl','General Ledger',7),
									(3,'hospital','hosp','Hospital',9),
									(3,'HR','hr','Human Resources',10),
									(3,'manuf','man','Manufacturing',6),
									(3,'orders','ord','Sales',1),
									(3,'PC','pc','Petty Cash',13),
									(3,'pjct','pjct','Project Accounting',11),
									(3,'PO','prch','Purchases',4),
									(3,'qa','qa','Quality Assurance',8),
									(3,'stock','inv','Inventory',5),
									(3,'system','sys','Setup',14),
									(3,'Utilities','util','Utilities',15),
									(4,'AP','ap','Payables',3),
									(4,'AR','ar','Receivables',2),
									(4,'FA','fa','Asset Manager',12),
									(4,'GL','gl','General Ledger',7),
									(4,'hospital','hosp','Hospital',9),
									(4,'HR','hr','Human Resources',10),
									(4,'manuf','man','Manufacturing',6),
									(4,'orders','ord','Sales',1),
									(4,'PC','pc','Petty Cash',13),
									(4,'pjct','pjct','Project Accounting',11),
									(4,'PO','prch','Purchases',4),
									(4,'qa','qa','Quality Assurance',8),
									(4,'stock','inv','Inventory',5),
									(4,'system','sys','Setup',14),
									(4,'Utilities','util','Utilities',15),
									(5,'AP','ap','Payables',3),
									(5,'AR','ar','Receivables',2),
									(5,'FA','fa','Asset Manager',12),
									(5,'GL','gl','General Ledger',7),
									(5,'hospital','hosp','Hospital',9),
									(5,'HR','hr','Human Resources',10),
									(5,'manuf','man','Manufacturing',6),
									(5,'orders','ord','Sales',1),
									(5,'PC','pc','Petty Cash',13),
									(5,'pjct','pjct','Project Accounting',11),
									(5,'PO','prch','Purchases',4),
									(5,'qa','qa','Quality Assurance',8),
									(5,'stock','inv','Inventory',5),
									(5,'system','sys','Setup',14),
									(5,'Utilities','util','Utilities',15),
									(6,'AP','ap','Payables',3),
									(6,'AR','ar','Receivables',2),
									(6,'FA','fa','Asset Manager',12),
									(6,'GL','gl','General Ledger',7),
									(6,'hospital','hosp','Hospital',9),
									(6,'HR','hr','Human Resources',10),
									(6,'manuf','man','Manufacturing',6),
									(6,'orders','ord','Sales',1),
									(6,'PC','pc','Petty Cash',13),
									(6,'pjct','pjct','Project Accounting',11),
									(6,'PO','prch','Purchases',4),
									(6,'qa','qa','Quality Assurance',8),
									(6,'stock','inv','Inventory',5),
									(6,'system','sys','Setup',14),
									(6,'Utilities','util','Utilities',15),
									(7,'AP','ap','Payables',3),
									(7,'AR','ar','Receivables',2),
									(7,'FA','fa','Asset Manager',12),
									(7,'GL','gl','General Ledger',7),
									(7,'hospital','hosp','Hospital',9),
									(7,'HR','hr','Human Resources',10),
									(7,'manuf','man','Manufacturing',6),
									(7,'orders','ord','Sales',1),
									(7,'PC','pc','Petty Cash',13),
									(7,'pjct','pjct','Project Accounting',11),
									(7,'PO','prch','Purchases',4),
									(7,'qa','qa','Quality Assurance',8),
									(7,'stock','inv','Inventory',5),
									(7,'system','sys','Setup',14),
									(7,'Utilities','util','Utilities',15),
									(8,'AP','ap','Payables',4),
									(8,'AR','ar','Receivables',2),
									(8,'FA','fa','Asset Manager',12),
									(8,'GL','gl','General Ledger',7),
									(8,'hospital','hosp','Hospital',9),
									(8,'HR','hr','Human Resources',10),
									(8,'manuf','man','Manufacturing',6),
									(8,'orders','ord','Sales',1),
									(8,'PC','pc','Petty Cash',13),
									(8,'pjct','pjct','Project Accounting',11),
									(8,'PO','prch','Purchases',3),
									(8,'qa','qa','Quality Assurance',8),
									(8,'stock','inv','Inventory',5),
									(8,'system','sys','Setup',14),
									(8,'Utilities','util','Utilities',15),
									(9,'AP','ap','Payables',3),
									(9,'AR','ar','Receivables',2),
									(9,'FA','fa','Asset Manager',12),
									(9,'GL','gl','General Ledger',7),
									(9,'hospital','hosp','Hospital',9),
									(9,'HR','hr','Human Resources',10),
									(9,'manuf','man','Manufacturing',6),
									(9,'orders','ord','Sales',1),
									(9,'PC','pc','Petty Cash',13),
									(9,'pjct','pjct','Project Accounting',11),
									(9,'PO','prch','Purchases',4),
									(9,'qa','qa','Quality Assurance',8),
									(9,'stock','inv','Inventory',5),
									(9,'system','sys','Setup',14),
									(9,'Utilities','util','Utilities',15),
									(10,'AP','ap','Payables',3),
									(10,'AR','ar','Receivables',2),
									(10,'FA','fa','Asset Manager',12),
									(10,'GL','gl','General Ledger',7),
									(10,'hospital','hosp','Hospital',9),
									(10,'HR','hr','Human Resources',10),
									(10,'manuf','man','Manufacturing',6),
									(10,'orders','ord','Sales',1),
									(10,'PC','pc','Petty Cash',13),
									(10,'pjct','pjct','Project Accounting',11),
									(10,'PO','prch','Purchases',4),
									(10,'qa','qa','Quality Assurance',8),
									(10,'stock','inv','Inventory',5),
									(10,'system','sys','Setup',14),
									(10,'Utilities','util','Utilities',15),
									(12,'AP','ap','Payables',3),
									(12,'AR','ar','Receivables',2),
									(12,'FA','fa','Asset Manager',11),
									(12,'GL','gl','General Ledger',7),
									(12,'HR','hr','Human Resources',9),
									(12,'manuf','man','Manufacturing',6),
									(12,'orders','ord','Sales',1),
									(12,'PC','pc','Petty Cash',12),
									(12,'pjct','pjct','Project Accounting',10),
									(12,'PO','prch','Purchases',4),
									(12,'qa','qa','Quality Assurance',8),
									(12,'stock','inv','Inventory',5),
									(12,'system','sys','Setup',13),
									(12,'Utilities','util','Utilities',14),
									(13,'AP','ap','Payables',3),
									(13,'AR','ar','Receivables',2),
									(13,'FA','fa','Asset Manager',11),
									(13,'GL','gl','General Ledger',7),
									(13,'HR','hr','Human Resources',9),
									(13,'manuf','man','Manufacturing',6),
									(13,'orders','ord','Sales',1),
									(13,'PC','pc','Petty Cash',12),
									(13,'pjct','pjct','Project Accounting',10),
									(13,'PO','prch','Purchases',4),
									(13,'qa','qa','Quality Assurance',8),
									(13,'stock','inv','Inventory',5),
									(13,'system','sys','Setup',13),
									(13,'Utilities','util','Utilities',14)";
$Result = DB_query($db, $SQL);

$SQL = array();
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (0,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (1,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (2,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (3,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (4,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (5,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (6,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (7,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (8,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (9,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','Maintain journal templates','/GLJournalTemplates.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Bank Account Balances','/BankAccountBalances.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Graph a specific GL code','/GLAccountGraph.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Print GL Report Set','/GLStatements.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'hospital','Maintenance','Maintain Encounter Types','/KCMCMaintainEncounterTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'hospital','Maintenance','Maintain Wards','/KCMCMaintainWards.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'hospital','Reports','Ward Overview','/KCMCWardOverview.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'hospital','Transactions','Admit an inpatient','/KCMCInpatientAdmission.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'hospital','Transactions','Admit an outpatient','/KCMCOutpatientAdmission.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'hospital','Transactions','Register a new patient','/KCMCRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update Cost Center','/prlCostCenter.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update HDMF Table','/prlHDMF.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update Other Income Table','/prlOthIncTable.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update Overtime Table','/prlOvertime.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update PhilHealth Table','/prlPH.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update SSS Table','/prlSSS.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update Tax Status Table','/prlSelectTaxStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Add/Update Tax Table','/prlTax.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Create New Employee Details','/prlEmployeeMaster.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','Bank Transmission','/prlRepBankTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','HDMF MOnthly Remittance','/prlRepHDMFPremium.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','Monthly Alphalist of Payees(MAP)','/prlRepTaxYTD.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','Over the Counter Listing','/prlRepCashTrans.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','Pay Slip','/prlRepPaySlip.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','Payroll Register','/prlRepPayrollRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','Philhealth Monthly Remittance','/prlRepPHPremium.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','SSS Monthly Remittance','/prlRepSSSPremium.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','Tax Monthly Return','/prlRepTax.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Reports','YTD Payroll Register','/prlRepPayrollRegYTD.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Create/Modify/Edit Payroll','/prlSelectPayroll.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Lates and Absents  Data Entry','/prlTardiness.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Other Income Data Entry','/prlOthIncome.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Overtime Data Entry','/prlOTFile.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','Regular Time Data Entry','/prlRegTimeEntry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','View Lates and Absenses','/prlSelectTD.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','View Other Income Data','/prlSelectOthIncome.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','View Overtime','/prlSelectOT.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','View Payroll Deduction','/prlSelectDeduction.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','View Payroll Trans','/prlSelectPayTrans.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','View Regular Time','/prlSelectRT.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'HR','Transactions','View/Edit Employee Loan Data','/prlSelectLoan.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Transactions','Timesheet Entry','/Timesheets.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Sales Commission Reports','/SalesCommissionReports.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Sales Report','/SalesReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Reports','PC Expense General Report','/PcReportExpense.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Report a problem with KwaMoja','https://github.com/KwaMoja/KwaMoja/issues',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Sales Commission Types','/SalesCommissionTypes.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Configure the Dashboard','/DashboardConfig.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Hospital Configuration Options','/KCMCHospitalConfiguration.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Reports','Reset Stock Costs table','/Z_ResetStockCosts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Change a sales person code','/Z_ChangeSalesmanCode.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Ensure systypes table is not corrupted','/Z_UpdateSystypes.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Fully allocate Customer transactions where < 0.01 unallocate','/Z_Fix1cAllocations.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Import Purchase Data','/Z_ImportPurchaseData.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Remove all purchase back orders','/Z_RemovePurchaseBackOrders.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (10,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (12,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Maintenance','Add Supplier','/Suppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Maintenance','Maintain Factor Companies','/Factors.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Maintenance','Select Supplier','/SelectSupplier.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Reports','Aged Supplier Report','/AgedSuppliers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Reports','List Daily Transactions','/PDFSuppTransListing.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Reports','Outstanding GRNs Report','/OutstandingGRNs.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Reports','Payment Run Report','/SuppPaymentRun.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Reports','Remittance Advices','/PDFRemittanceAdvice.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Reports','Supplier Balances At A Prior Month End','/SupplierBalsAtPeriodEnd.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Reports','Supplier Transaction Inquiries','/SupplierTransInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Reports','Where Allocated Inquiry','/SuppWhereAlloc.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Transactions','Select Supplier','/SelectSupplier.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AP','Transactions','Supplier Allocations','/SupplierAllocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Maintenance','Add Customer','/Customers.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Maintenance','Enable Customer Branches','/EnableBranches.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Maintenance','Select Customer','/SelectCustomer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Aged Customer Balances/Overdues Report','/AgedDebtors.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Customer Activity and Balances','/CustomerBalancesMovement.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Customer Listing By Area/Salesperson','/PDFCustomerList.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Customer Transaction Inquiries','/CustomerTransInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Debtor Balances At A Prior Month End','/DebtorsAtPeriodEnd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','List Daily Transactions','/PDFCustTransListing.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Print Invoices or Credit Notes','/PrintCustTrans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Print Statements','/PrintCustStatements.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Re-Print A Deposit Listing','/PDFBankingSummary.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Sales Analysis Reports','/SalesAnalRepts.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Sales Graphs','/SalesGraph.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Reports','Where Allocated Inquiry','/CustWhereAlloc.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Transactions','Allocate Receipts or Credit Notes','/CustomerAllocations.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Transactions','Create A Credit Note','/SelectCreditItems.php?NewCredit=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Transactions','Enter Receipts','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=Customer',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'AR','Transactions','Select Order to Invoice','/SelectSalesOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Maintenance','Add or Maintain Asset Locations','/FixedAssetLocations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Maintenance','Asset Categories Maintenance','/FixedAssetCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Maintenance','Maintenance Tasks','/MaintenanceTasks.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Reports','Asset Register','/FixedAssetRegister.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Reports','Maintenance Reminder Emails','/MaintenanceReminders.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Reports','My Maintenance Schedule','/MaintenanceUserSchedule.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Transactions','Add a new Asset','/FixedAssetItems.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Transactions','Change Asset Location','/FixedAssetTransfer.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Transactions','Depreciation Journal','/FixedAssetDepreciation.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'FA','Transactions','Select an Asset','/SelectAsset.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','Account Groups','/AccountGroups.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','Account Sections','/AccountSections.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','Copy Authority GL Accounts from user A to B','/GLAccountUsersCopyAuthority.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','GL Account','/GLAccounts.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','GL Accounts Authorised Users Maintenance','/GLAccountUsers.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','GL Budgets','/GLBudgets.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','GL Tags','/GLTags.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','Set up a RegularPayment','/RegularPaymentsSetup.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','Setup GL Accounts for Statement of Cash Flows','/GLCashFlowsSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','User Authorised Bank Accounts','/UserBankAccounts.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Maintenance','User Authorised GL Accounts Maintenance','/UserGLAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Account Inquiry','/SelectGLAccount.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Account Listing','/GLAccountReport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Account Listing to CSV File','/GLAccountCSV.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Balance Sheet','/GLBalanceSheet.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Bank Account Reconciliation Statement','/BankReconciliation.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Bank Transactions Inquiry','/DailyBankTransactions.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Cheque Payments Listing','/PDFChequeListing.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','General Ledger Journal Inquiry','/GLJournalInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Horizontal Analysis of Statement of Comprehensive Income','/AnalysisHorizontalIncome.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Horizontal analysis of statement of financial position','/AnalysisHorizontalPosition.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Monthly Bank Inquiry','/MonthlyBankTransactions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Profit and Loss Statement','/GLProfit_Loss.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Statement of Cash Flows','/GLCashFlowsIndirect.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Tag Reports','/GLTagProfit_Loss.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Tax Reports','/Tax.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Reports','Trial Balance','/GLTrialBalance.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Transactions','Bank Account Payments Entry','/Payments.php?NewPayment=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Transactions','Bank Account Payments Matching','/BankMatching.php?Type=Payments',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Transactions','Bank Account Receipts Entry','/CustomerReceipt.php?NewReceipt=Yes&amp;Type=GL',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Transactions','Bank Account Receipts Matching','/BankMatching.php?Type=Receipts',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Transactions','Import Bank Transactions','/ImportBankTrans.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'GL','Transactions','Journal Entry','/GLJournal.php?NewJournal=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Maintenance','Add/Update Employees Record','/prlSelectEmployee.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Maintenance','Add/Update Loan Types','/prlLoanTable.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Maintenance','Maintain Employment Statuses','/prlEmploymentStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Maintenance','Maintain Pay Periods','/prlPayPeriod.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Maintenance','Maintain Tax Status','/prlTaxStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Maintenance','Review Employee Loans','/prlSelectLoan.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Transactions','Add/Update an Employee Loan','/prlALD.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Transactions','Authorise Employee Loans','/prlAuthoriseLoans.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Transactions','Employee Loan Repayments','/prlLoanRepayments.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'HR','Transactions','Issue Employee Loans','/prlLoanPayments.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Maintenance','Auto Create Master Schedule','/MRPCreateDemands.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Maintenance','Bills Of Material','/BOMs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Maintenance','Copy a Bill Of Materials Between Items','/CopyBOM.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Maintenance','Master Schedule','/MRPDemands.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Maintenance','MRP Calculation','/MRP.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Maintenance','Multiple Work Orders Total Cost Inquiry','/CollectiveWorkOrderCost.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Maintenance','Work Centre','/WorkCentres.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','Bill Of Material Listing','/BOMListing.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','Costed Bill Of Material Inquiry','/BOMInquiry.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','Indented Bill Of Material Listing','/BOMIndented.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','Indented Where Used Listing','/BOMIndentedReverse.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','List Components Required','/BOMExtendedQty.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','List Materials Not Used anywhere','/MaterialsNotUsed.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','MRP','/MRPReport.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','MRP Reschedules Required','/MRPReschedules.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','MRP Shortages','/MRPShortages.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','MRP Suggested Purchase Orders','/MRPPlannedPurchaseOrders.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','MRP Suggested Work Orders','/MRPPlannedWorkOrders.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','Select A Work Order','/SelectWorkOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','Where Used Inquiry','/WhereUsedInquiry.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Reports','WO Items ready to produce','/WOCanBeProducedNow.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Transactions','Select A Work Order','/SelectWorkOrder.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'manuf','Transactions','Work Order Entry','/WorkOrderEntry.php?New=True',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Maintenance','Create Contract','/Contracts.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Maintenance','Import Sales Prices From CSV File','/ImportSalesPriceList.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Maintenance','Select Contract','/SelectContract.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Maintenance','Sell Through Support Deals','/SellThroughSupport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Daily Sales Inquiry','/DailySalesInquiry.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Delivery In Full On Time (DIFOT) Report','/PDFDIFOT.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Order Delivery Differences Report','/PDFDeliveryDifferences.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Order Inquiry','/SelectCompletedOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Order Status Report','/PDFOrderStatus.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Orders Invoiced Reports','/PDFOrdersInvoiced.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Print Price Lists','/PDFPriceList.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Sales By Category By Item Inquiry','/StockCategorySalesInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Sales By Category Inquiry','/SalesCategoryPeriodInquiry.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Sales By Sales Type Inquiry','/SalesByTypePeriodInquiry.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Sales Order Detail Or Summary Inquiries','/SalesInquiry.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Sales With Low Gross Profit Report','/PDFLowGP.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Sell Through Support Claims Report','/PDFSellThroughSupportClaim.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Top Customers Inquiry','/SalesTopCustomersInquiry.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Top Sales Items Report','/TopItems.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Top Sellers Inquiry','/SalesTopItemsInquiry.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Reports','Worst Sales Items Report','/NoSalesItems.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Enter An Order or Quotation','/SelectOrderItems.php?NewOrder=Yes',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Enter Counter Returns','/CounterReturns.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Enter Counter Sales','/CounterSales.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Generate/Print Picking Lists','/GeneratePickingList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Import Asterisk Files','/AsteriskImport.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Maintain Picking Lists','/SelectPickingLists.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Outstanding Sales Orders/Quotations','/SelectSalesOrder.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Process Recurring Orders','/RecurringSalesOrdersProcess.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Recurring Order Template','/SelectRecurringSalesOrder.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Special Order','/SpecialOrder.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Synchronise KwaMoja to OpenCart Daily','/OcKwaMojaToOpenCartDaily.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Synchronise KwaMoja to OpenCart Hourly','/OcKwaMojaToOpenCartHourly.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'orders','Transactions','Synchronise OpenCart to KwaMoja','/OcOpenCartToKwaMoja.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Maintenance','Expenses for Type of PC Tab','/PcExpensesTypeTab.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Maintenance','PC Expenses','/PcExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Maintenance','PC Tabs','/PcTabs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Maintenance','Types of PC Tabs','/PcTypeTabs.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Reports','PC Tab General Report','/PcReportTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Transactions','Assign Cash to PC Tab','/PcAssignCashToTab.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Transactions','Cash Authorisation','/PcAuthorizeCheque.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Transactions','Claim Expenses From PC Tab','/PcClaimExpensesFromTab.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PC','Transactions','Expenses Authorisation','/PcAuthorizeExpenses.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'pjct','Maintenance','Donor Maintenance','/Donors.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'pjct','Transactions','Create New Project','/Projects.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'pjct','Transactions','Select a Project','/SelectProject.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Maintenance','Clear Orders with Quantity on Back Orders','/POClearBackOrders.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Maintenance','Maintain Supplier Price Lists','/SupplierPriceList.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Reports','Purchase Order Detail Or Summary Inquiries','/POReport.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Reports','Purchase Order Inquiry','/PO_SelectPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Reports','Purchases from Suppliers','/PurchasesReport.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Reports','Supplier Price List','/SuppPriceList.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Add Purchase Order','/PO_Header.php?NewOrder=Yes',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Create a New Tender','/SupplierTenderCreate.php?New=Yes',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Create a PO based on the preferred supplier','/PurchaseByPrefSupplier.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Edit Existing Tenders','/SupplierTenderCreate.php?Edit=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Orders to Authorise','/PO_AuthoriseMyOrders.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Process Tenders and Offers','/OffersReceived.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Select A Shipment','/Shipt_Select.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'PO','Transactions','Shipment Entry','/SelectSupplier.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'qa','Maintenance','Product Specifications','/ProductSpecs.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'qa','Maintenance','Quality Tests Maintenance','/QATests.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'qa','Reports','Historical QA Test Results','/HistoricalTestResults.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'qa','Reports','Print Certificate of Analysis','/PDFCOA.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'qa','Reports','Print Product Specification','/PDFProdSpec.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'qa','Transactions','QA Samples and Test Results','/SelectQASamples.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','ABC Ranking Groups','/ABCRankingGroups.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','ABC Ranking Methods','/ABCRankingMethods.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Add A New Item','/Stocks.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Add or Update Prices Based On Costs','/PricesBasedOnMarkUp.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Brands Maintenance','/Manufacturers.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Reorder Level By Category/Location','/ReorderLevelLocation.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Run ABC Ranking Analysis','/ABCRunAnalysis.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Sales Category Maintenance','/SalesCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Select An Item','/SelectProduct.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Translated Descriptions Revision','/RevisionTranslations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','Upload new prices from csv file','/UploadPriceList.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Maintenance','View or Update Prices Based On Costs','/PricesByCost.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Aged Controlled Inventory Report','/AgedControlledInventory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','All Inventory Movements By Location/Date','/StockLocMovements.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Compare Counts Vs Stock Check Data','/PDFStockCheckComparison.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Historical Stock Quantity By Location/Category','/StockQuantityByDate.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Internal Stock Request Inquiry','/InternalStockRequestInquiry.php',24)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Inventory Item Movements','/StockMovements.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Inventory Item Status','/StockStatus.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Inventory Item Usage','/StockUsage.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Inventory Planning Based On Preferred Supplier Data','/InventoryPlanningPrefSupplier.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Inventory Planning Report','/InventoryPlanning.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Inventory Quantities','/InventoryQuantities.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Inventory Stock Check Sheets','/StockCheck.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Inventory Valuation Report','/InventoryValuation.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','List Inventory Status By Location/Category','/StockLocStatus.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','List Negative Stocks','/PDFStockNegatives.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Mail Inventory Valuation Report','/MailInventoryValuation.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Make Inventory Quantities CSV','/StockQties_csv.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Period Stock Transaction Listing','/PDFPeriodStockTransListing.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Print Price Labels','/PDFPrintLabel.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Reorder Level','/ReorderLevel.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Reprint GRN','/ReprintGRN.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Serial Item Research Tool','/StockSerialItemResearch.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Stock Dispatch','/StockDispatch.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Reports','Stock Transfer Note','/PDFStockTransfer.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Amend an internal stock request','/InternalStockRequest.php?Edit=Yes',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Authorise Internal Stock Requests','/InternalStockRequestAuthorisation.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Bulk Inventory Transfer - Dispatch','/StockLocTransfer.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Bulk Inventory Transfer - Receive','/StockLocTransferReceive.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Create a New Internal Stock Request','/InternalStockRequest.php?New=Yes',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Enter Stock Counts','/StockCounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Fulfil Internal Stock Requests','/InternalStockRequestFulfill.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Inventory Adjustments','/StockAdjustments.php?NewAdjustment=Yes',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Inventory Location Transfers','/StockTransfers.php?New=Yes',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Receive Purchase Orders','/PO_SelectOSPurchOrder.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'stock','Transactions','Reverse Goods Received','/ReverseGRN.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Automaticall allocate customer receipts and credit notes','/Z_AutoCustomerAllocations.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Bank Account Authorised Users','/BankAccountUsers.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Discount Category Maintenance','/DiscountCategories.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Install a KwaMoja plugin','/PluginInstall.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Inventory Categories Maintenance','/StockCategories.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Inventory Locations Maintenance','/Locations.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Maintain Internal Departments','/Departments.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Maintain Internal Stock Categories to User Roles','/InternalStockCategoriesByRole.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','MRP Available Production Days','/MRPCalendar.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','MRP Demand Types','/MRPDemandTypes.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Rebuild sales analysis Records','/Z_RebuildSalesAnalysis.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Remove a KwaMoja plugin','/PluginUnInstall.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Units of Measure','/UnitsOfMeasure.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Update Item Costs from a CSV file','/Z_UpdateItemCosts.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','Upload a KwaMoja plugin file','/PluginUpload.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','User Authorised Inventory Locations Maintenance','/UserLocations.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Maintenance','User Location Maintenance','/LocationUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','COGS GL Interface Postings','/COGSGLPostings.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Credit Status','/CreditStatus.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Customer Types','/CustomerTypes.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Discount Matrix','/DiscountMatrix.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Freight Costs Maintenance','/FreightCosts.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Mantain prices by quantity break and sales types','/PriceMatrix.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Mantain stock types','/StockTypes.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Payment Methods','/PaymentMethods.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Payment Terms','/PaymentTerms.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Sales Areas','/Areas.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Sales GL Interface Postings','/SalesGLPostings.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Sales People','/SalesPeople.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Sales Types','/SalesTypes.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Set Purchase Order Authorisation levels','/PO_AuthorisationLevels.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Shippers','/Shippers.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Reports','Supplier Types','/SupplierTypes.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Access Permissions Maintenance','/WWW_Access.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Bank Accounts','/BankAccounts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Company Preferences','/CompanyPreferences.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Configuration Settings','/SystemParameters.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Currency Maintenance','/Currencies.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Dispatch Tax Province Maintenance','/TaxProvinces.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Form Layout Editor','/FormDesigner.php',17)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Geocode Setup','/GeocodeSetup.php',16)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Label Templates Maintenance','/Labels.php',18)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','List Periods Defined','/PeriodsInquiry.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Mailing Group Maintenance','/MailingGroupMaintenance.php',20)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Maintain Security Tokens','/SecurityTokens.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Page Security Settings','/PageSecurity.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Report Builder Tool','/reportwriter/admin/ReportCreator.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Schedule tasks to be automatically run','/JobScheduler.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','SMTP Server Details','/SMTPServer.php',19)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Tax Authorities and Rates Maintenance','/TaxAuthorities.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Tax Category Maintenance','/TaxCategories.php',12)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Tax Group Maintenance','/TaxGroups.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Update Module Order','/ModuleEditor.php',22)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','User Maintenance','/WWW_Users.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','View Audit Trail','/AuditTrail.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'system','Transactions','Web-Store Configuration','/ShopParameters.php',21)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Create new company template SQL file and submit to KwaMoja','/Z_CreateCompanyTemplateFile.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Create User Location records','/Z_MakeLocUsers.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Data Export Options','/Z_DataExport.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Import Fixed Assets from .csv file','/Z_ImportFixedAssets.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Import Stock Items from .csv','/Z_ImportStocks.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Maintain Language Files','/Z_poAdmin.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Make New Company','/Z_MakeNewCompany.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Purge all old prices','/Z_DeleteOldPrices.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Re-calculate brought forward amounts in GL','/Z_UpdateChartDetailsBFwd.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Maintenance','Re-Post all GL transactions from a specified period','/Z_RePostGLFromPeriod.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Reports','List of items without picture','/Z_ItemsWithoutPicture.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Reports','Show General Transactions That Do Not Balance','/Z_CheckGLTransBalance.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Reports','Show Local Currency Total Debtor Balances','/Z_CurrencyDebtorsBalances.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Reports','Show Local Currency Total Suppliers Balances','/Z_CurrencySuppliersBalances.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Automatic Translation - Item descriptions','/AutomaticTranslationDescriptions.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Cash Authorisation','/Z_ChangeStockCategory.php',15)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Change A Customer Branch Code','/Z_ChangeBranchCode.php',2)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Change A Customer Code','/Z_ChangeCustomerCode.php',1)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Change A General Ledger Code','/Z_ChangeGLAccountCode.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Change A Location Code','/Z_ChangeLocationCode.php',4)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Change a serial number','/Z_ChangeSerialNumber.php',6)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Change A Supplier Code','/Z_ChangeSupplierCode.php',3)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Change An Inventory Item Code','/Z_ChangeStockCode.php',5)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Delete sales transactions','/Z_DeleteSalesTransActions.php',9)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Import Debtors','/Z_ImportDebtors.php',13)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Import GL Transactions from a csv file','/Z_ImportGLTransactions.php',11)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Import Suppliers','/Z_ImportSuppliers.php',14)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Re-apply costs to Sales Analysis','/Z_ReApplyCostToSA.php',8)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Reverse all supplier payments on a specified date','/Z_ReverseSuppPaymentRun.php',10)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Update costs for all BOM items, from the bottom up','/Z_BottomUpCosts.php',7)";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`menuitems` VALUES (13,'Utilities','Transactions','Update sales analysis with latest customer data','/Z_UpdateSalesAnalysisWithLatestCustomerData.php',12)";

foreach ($SQL as $Query) {
	$Result = DB_query($db, $Query);
}
unset($SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".gltags (SELECT counterindex, tag FROM " . $WebERPDB . ".gltrans)";
$Result = DB_query($db, $SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".pctags (SELECT counterindex, tag FROM " . $WebERPDB . ".pcashdetails)";
$Result = DB_query($db, $SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".stockcosts (SELECT stockid, materialcost, labourcost, overheadcost, CURRENT_DATE, 0 FROM " . $WebERPDB . ".stockmaster)";
$Result = DB_query($db, $SQL);
echo "\e[0;32mOK!\e[0m\n";

echo 'Making alterations on existing tables for KwaMoja compatibility....';
/* Changes to existing tables */

$SQL = "USE " . $WebERPDB;
$Result = DB_query($db, $SQL);
DB_query($db, 'SET FOREIGN_KEY_CHECKS=0');

$SQL = "ALTER TABLE " . $WebERPDB . ".`accountgroups`
  DROP FOREIGN KEY accountgroups_ibfk_1,
  DROP INDEX SequenceInTB,
  DROP INDEX parentgroupname,
  ADD COLUMN language varchar(10) NOT NULL DEFAULT 'en_GB.utf8',
  ADD COLUMN groupcode char(10) NOT NULL AFTER groupname,
  CHANGE COLUMN groupname groupname varchar(150) NOT NULL,
  CHANGE COLUMN parentgroupname parentgroupname varchar(150) NOT NULL AFTER sequenceintb,
  ADD COLUMN parentgroupcode char(10) NOT NULL AFTER parentgroupname,
  CHANGE COLUMN sequenceintb sequenceintb smallint(6) NOT NULL DEFAULT '0' AFTER pandl,
  CHANGE COLUMN pandl pandl tinyint(4) NOT NULL DEFAULT '1' AFTER sectioninaccounts,
  CHANGE COLUMN sectioninaccounts sectioninaccounts int(11) NOT NULL DEFAULT '0'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`chartmaster`
  DROP FOREIGN KEY chartmaster_ibfk_1,
  DROP INDEX AccountName,
  DROP INDEX Group_,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`accountcode`,`language`),
  ADD INDEX (accountname),
  ADD INDEX (groupcode),
  CHANGE COLUMN group_ group_ varchar(150) NOT NULL AFTER accountname,
  CHANGE COLUMN accountname accountname varchar(150) NOT NULL,
  ADD COLUMN language varchar(10) NOT NULL DEFAULT 'en_GB.utf8' AFTER accountcode,
  ADD COLUMN groupcode char(10) NOT NULL AFTER cashflowsactivity,
  CHANGE COLUMN cashflowsactivity cashflowsactivity tinyint(1) NOT NULL DEFAULT '-1' AFTER group_";
$Result = DB_query($db, $SQL);

$i = 1;
$SQL = "SELECT parentgroupname, groupname FROM " . $WebERPDB . ".accountgroups";
$Result = DB_query($db, $SQL);
while ($MyRow = mysqli_fetch_array($Result)) {
	$UpdateCodeSQL = "UPDATE " . $WebERPDB . ".accountgroups SET groupcode='" . ($i * 10) . "' WHERE groupname='" . $MyRow['groupname'] . "'";
	$UpdateCodeResult =DB_query($db, $UpdateCodeSQL);
	if ($MyRow['parentgroupname'] == '') {
		$ParentGroupCode = 0;
	} else {
		$ParentCodeSQL = "SELECT groupcode FROM " . $WebERPDB . ".accountgroups WHERE groupname='" . $MyRow['parentgroupname'] . "'";
		$ParentCodeResult = DB_query($db, $ParentCodeSQL);
		$ParentCodeRow = mysqli_fetch_array($ParentCodeResult);
		$ParentGroupCode = $ParentCodeRow['groupcode'];
	}
	$UpdateParentSQL = "UPDATE " . $WebERPDB . ".accountgroups SET parentgroupcode='" . $ParentGroupCode . "' WHERE groupname='" . $MyRow['groupname'] . "'";
	$UpdateParentResult = DB_query($db, $UpdateParentSQL);
	++$i;
}

$SQL = "ALTER TABLE " . $WebERPDB . ".`accountgroups`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`groupcode`,`language`),
  ADD INDEX SequenceInTB (sequenceintb),
  ADD INDEX parentgroupname (parentgroupname)";
$Result = DB_query($db, $SQL);
$SQL = "UPDATE " . $WebERPDB . ".`accountgroups` SET language='" . $DefaultLanguage . "';";
$Result = DB_query($db, $SQL);

$SQL ="ALTER TABLE " . $WebERPDB . ".`accountsection`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`language`,`sectionid`),
  ADD COLUMN language varchar(10) NOT NULL DEFAULT 'en_GB.utf8' AFTER sectionid,
  CHANGE COLUMN sectionname sectionname text NOT NULL;";
$Result = DB_query($db, $SQL);
$SQL = "UPDATE " . $WebERPDB . ".`accountsection` SET language='" . $DefaultLanguage . "';";
$Result = DB_query($db, $SQL);

$SQL = "SELECT accountcode, group_ FROM " . $WebERPDB . ".chartmaster";
$Result = DB_query($db, $SQL);
while ($MyRow = mysqli_fetch_array($Result)) {
	$UpdateSQL = "UPDATE " . $WebERPDB . ".chartmaster SET groupcode=(SELECT groupcode FROM " . $WebERPDB . ".accountgroups WHERE groupname=group_) WHERE accountcode='" . $MyRow['accountcode'] . "'";
	$UpdateResult = DB_query($db, $UpdateSQL);
}

$SQL ="ALTER TABLE " . $WebERPDB . ".`audittrail`
  DROP FOREIGN KEY audittrail_ibfk_1,
  DROP INDEX transactiondate,
  DROP INDEX transactiondate_3,
  DROP INDEX transactiondate_2,
  ADD INDEX audittrail_transactiondate (transactiondate),
  ADD COLUMN address varchar(15) NOT NULL DEFAULT '0.0.0.0' AFTER userid,
  CHANGE COLUMN querystring querystring text NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`bankaccounts`
  DROP FOREIGN KEY bankaccounts_ibfk_1,
  DROP INDEX currcode,
  DROP INDEX BankAccountName,
  DROP INDEX BankAccountNumber,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`accountcode`),
  ADD INDEX currcode (currcode),
  ADD INDEX BankAccountNumber (bankaccountnumber),
  ADD INDEX BankAccountName (bankaccountname),
  ADD COLUMN pettycash tinyint(1) NOT NULL DEFAULT '0' AFTER bankaddress,
  CHANGE COLUMN importformat importformat varchar(10) NOT NULL DEFAULT ''";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`bankaccountusers`
  CHANGE COLUMN accountcode accountcode varchar(20) NOT NULL DEFAULT '',
  CHANGE COLUMN userid userid varchar(20) NOT NULL DEFAULT ''";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`banktrans`
  DROP FOREIGN KEY banktrans_ibfk_1,
  DROP FOREIGN KEY banktrans_ibfk_2,
  DROP INDEX TransType,
  DROP INDEX Type,
  DROP INDEX BankAct,
  DROP INDEX TransDate,
  DROP PRIMARY KEY,
  DROP INDEX CurrCode,
  ADD PRIMARY KEY(`banktransid`),
  ADD INDEX BankAct (bankact),
  ADD INDEX (type),
  ADD INDEX TransType (banktranstype),
  ADD INDEX (transno),
  ADD INDEX CurrCode (currcode),
  ADD INDEX TransDate (transdate),
  CHANGE COLUMN chequeno chequeno varchar(20) NOT NULL AFTER ref,
  ADD COLUMN userid varchar(20) NOT NULL DEFAULT '' AFTER currcode,
  CHANGE COLUMN transdate transdate date NOT NULL DEFAULT '0000-00-00' AFTER functionalexrate,
  CHANGE COLUMN exrate exrate double NOT NULL DEFAULT '1' AFTER amountcleared,
  CHANGE COLUMN functionalexrate functionalexrate double NOT NULL DEFAULT '1' COMMENT 'Account currency to functional currency' AFTER exrate,
  CHANGE COLUMN currcode currcode char(3) NOT NULL DEFAULT '' AFTER amount,
  CHANGE COLUMN amount amount double NOT NULL DEFAULT '0' AFTER banktranstype,
  CHANGE COLUMN banktranstype banktranstype varchar(30) NOT NULL DEFAULT '' AFTER transdate,
  CHANGE COLUMN amountcleared amountcleared double NOT NULL DEFAULT '0' AFTER chequeno";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`bom`
  DROP PRIMARY KEY,
  DROP INDEX Parent_2,
  DROP INDEX EffectiveAfter,
  DROP INDEX Parent,
  DROP INDEX Component,
  DROP INDEX EffectiveTo,
  DROP INDEX LocCode,
  DROP COLUMN remark,
  DROP COLUMN digitals,
  ADD INDEX (`workcentreadded`,`parent`,`loccode`,`component`),
  ADD PRIMARY KEY(`workcentreadded`,`parent`,`loccode`,`component`),
  ADD INDEX (effectiveto,loccode),
  ADD INDEX (effectiveto),
  ADD INDEX (parent),
  ADD INDEX (loccode),
  ADD INDEX (effectiveafter),
  ADD INDEX (component),
  ADD INDEX (parent,effectiveafter),
  ADD COLUMN comment text NOT NULL AFTER autoissue";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`chartdetails`
  DROP FOREIGN KEY chartdetails_ibfk_1,
  DROP INDEX Period,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`accountcode`,`period`),
  ADD INDEX Period (period)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`cogsglpostings`
  DROP INDEX Area_StkCat,
  DROP PRIMARY KEY,
  DROP INDEX StkCat,
  DROP INDEX GLCode,
  DROP INDEX SalesType,
  DROP INDEX Area,
  ADD PRIMARY KEY(`id`),
  ADD INDEX (area),
  ADD INDEX (glcode),
  ADD INDEX (salestype)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`companies`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`coycode`),
  ADD COLUMN commissionsact varchar(20) NOT NULL DEFAULT '1' AFTER grnact,
  ADD COLUMN npo tinyint(1) NOT NULL DEFAULT '0' AFTER currencydefault,
  CHANGE COLUMN exchangediffact exchangediffact varchar(20) NOT NULL DEFAULT '65000',
  CHANGE COLUMN gllink_stock gllink_stock tinyint(1) NULL DEFAULT '1' AFTER gllink_creditors,
  CHANGE COLUMN creditorsact creditorsact varchar(20) NOT NULL DEFAULT '80000' AFTER pytdiscountact,
  CHANGE COLUMN gllink_creditors gllink_creditors tinyint(1) NULL DEFAULT '1' AFTER gllink_debtors,
  CHANGE COLUMN pytdiscountact pytdiscountact varchar(20) NOT NULL DEFAULT '55000' AFTER debtorsact,
  CHANGE COLUMN retainedearnings retainedearnings varchar(20) NOT NULL DEFAULT '90000' AFTER purchasesexchangediffact,
  CHANGE COLUMN debtorsact debtorsact varchar(20) NOT NULL DEFAULT '70000',
  CHANGE COLUMN freightact freightact varchar(20) NOT NULL DEFAULT '0' AFTER gllink_stock,
  CHANGE COLUMN grnact grnact varchar(20) NOT NULL DEFAULT '72000' AFTER payrollact,
  CHANGE COLUMN payrollact payrollact varchar(20) NOT NULL DEFAULT '84000' AFTER creditorsact,
  CHANGE COLUMN gllink_debtors gllink_debtors tinyint(1) NULL DEFAULT '1' AFTER retainedearnings,
  CHANGE COLUMN purchasesexchangediffact purchasesexchangediffact varchar(20) NOT NULL DEFAULT '0' AFTER exchangediffact";
$Result = DB_query($db, $SQL);

$SQL = array();
$SQL[] = "DELETE FROM " . $WebERPDB . ".`config` WHERE `confname` = 'LastDayofWeek'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`config` WHERE `confname` = 'ShortcutMenu'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`config` WHERE `confname` = 'ShopFreightMethod'";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('ShopFreightModule', 'ShopFreightMethod')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('KwaMojaToOpenCartHourly_LastRun', '0000-00-00 00:00:00')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('ShopShowTopCategoryMenu', '1')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('vtiger_integration', '0')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('DefaultSalesPerson', 'HS')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('DefaultTheme', 'fluid')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('ShopAdditionalStockLocations', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('LastDayOfWeek', '0')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('InsuranceDebtorType', '1')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('AutoPatientNo', '1')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('TermsAndConditions', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('CanAmendBill', '1')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('DefaultArea', 'UK')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('DispenseOnBill', '1')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('OpenCartToKwaMoja_LastRun', '0000-00-00 00:00:00')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('DBUpdateNumber', '214')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('NewBranchesMustBeAuthorised', '0')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('AutoInvenoryNo', '0')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('ShopShowLeftCategoryMenu', '1')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('PeriodProfitAccount', '1010')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('ShopShowLogoAndShopName', '1')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('KwaMojaImagesFromOpenCart', 'data/part_pics/')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('ShopShowInfoLinks', '1')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`config` (`confname`, `confvalue`) VALUES('KwaMojaToOpenCartDaily_LastRun', '0000-00-00 00:00:00')";
$SQL[] = "UPDATE config SET confvalue=CONCAT(SUBSTRING(YEAR(CURDATE()), 3, 2), '.', LPAD(MONTH(CURDATE()), 2 ,'0')) WHERE confname='VersionNumber'";

foreach ($SQL as $Query) {
	$Result = DB_query($db, $Query);
}
unset($SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`contractbom`
  DROP PRIMARY KEY,
  DROP INDEX ContractRef,
  DROP INDEX WorkCentreAdded,
  DROP INDEX Stockid,
  ADD PRIMARY KEY(`contractref`,`stockid`,`workcentreadded`),
  ADD INDEX ContractRef (contractref),
  ADD INDEX WorkCentreAdded (workcentreadded),
  ADD INDEX Stockid (stockid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`contractcharges`
  DROP FOREIGN KEY contractcharges_ibfk_2,
  DROP FOREIGN KEY contractcharges_ibfk_1";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`contractreqts`
  DROP FOREIGN KEY contractreqts_ibfk_1,
  DROP PRIMARY KEY,
  DROP INDEX ContractRef,
  ADD PRIMARY KEY(`contractreqid`),
  ADD INDEX ContractRef (contractref)";
$Result = DB_query($db, $SQL);

$SQL ="ALTER TABLE " . $WebERPDB . ".`contracts`
  DROP FOREIGN KEY contracts_ibfk_1,
  DROP FOREIGN KEY contracts_ibfk_3,
  DROP FOREIGN KEY contracts_ibfk_2";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`currencies`
  ADD COLUMN symbolbefore tinyint(1) NOT NULL DEFAULT '0',
  ADD COLUMN symbol char(3) NOT NULL DEFAULT '$' AFTER hundredsname,
  CHANGE COLUMN rate rate double NOT NULL DEFAULT '1' AFTER decimalplaces,
  CHANGE COLUMN decimalplaces decimalplaces tinyint(3) NOT NULL DEFAULT '2',
  CHANGE COLUMN webcart webcart tinyint(1) NOT NULL DEFAULT '1' AFTER rate";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`custallocns`
  DROP FOREIGN KEY custallocns_ibfk_1,
  DROP FOREIGN KEY custallocns_ibfk_2,
  DROP INDEX TransID_AllocTo,
  DROP INDEX DateAlloc,
  DROP PRIMARY KEY,
  DROP INDEX TransID_AllocFrom,
  ADD PRIMARY KEY(`id`),
  ADD INDEX TransID_AllocTo (transid_allocto),
  ADD INDEX DateAlloc (datealloc),
  ADD INDEX TransID_AllocFrom (transid_allocfrom)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`custbranch`
  DROP FOREIGN KEY custbranch_ibfk_2,
  DROP FOREIGN KEY custbranch_ibfk_6,
  DROP FOREIGN KEY custbranch_ibfk_7,
  DROP FOREIGN KEY custbranch_ibfk_1,
  DROP FOREIGN KEY custbranch_ibfk_4,
  DROP FOREIGN KEY custbranch_ibfk_3,
  DROP INDEX BrName,
  DROP INDEX Salesman,
  DROP PRIMARY KEY,
  DROP INDEX Area,
  DROP INDEX DefaultShipVia,
  DROP INDEX DebtorNo,
  ADD PRIMARY KEY(`branchcode`,`debtorno`),
  ADD INDEX Area (area),
  ADD INDEX DebtorNo (debtorno),
  ADD INDEX BrName (brname),
  ADD INDEX DefaultShipVia (defaultshipvia),
  ADD INDEX Salesman (salesman),
  ADD COLUMN brpostaddr varchar(15) NOT NULL DEFAULT '' AFTER brpostaddr5,
  CHANGE COLUMN custbranchcode custbranchcode varchar(30) NOT NULL DEFAULT '' AFTER specialinstructions,
  CHANGE COLUMN brpostaddr6 brpostaddr6 varchar(40) NOT NULL,
  CHANGE COLUMN brpostaddr4 brpostaddr4 varchar(50) NOT NULL,
  CHANGE COLUMN brpostaddr3 brpostaddr3 varchar(40) NOT NULL,
  CHANGE COLUMN braddress6 braddress6 varchar(40) NOT NULL,
  CHANGE COLUMN specialinstructions specialinstructions text NOT NULL AFTER brpostaddr6";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`custcontacts`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`contid`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`custnotes`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`noteid`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`debtorsmaster`
  DROP FOREIGN KEY debtorsmaster_ibfk_4,
  DROP FOREIGN KEY debtorsmaster_ibfk_2,
  DROP FOREIGN KEY debtorsmaster_ibfk_1,
  DROP FOREIGN KEY debtorsmaster_ibfk_3,
  DROP FOREIGN KEY debtorsmaster_ibfk_5,
  DROP INDEX debtorsmaster_ibfk_5,
  DROP PRIMARY KEY,
  DROP INDEX PaymentTerms,
  DROP INDEX SalesType,
  DROP INDEX Currency,
  DROP INDEX Name,
  ADD PRIMARY KEY(`debtorno`),
  ADD INDEX Currency (currcode),
  ADD INDEX debtorsmaster_ibfk_5 (typeid),
  ADD INDEX Name (name),
  ADD INDEX SalesType (salestype),
  ADD INDEX PaymentTerms (paymentterms),
  CHANGE COLUMN salestype salestype varchar(4) NULL,
  CHANGE COLUMN address6 address6 varchar(40) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`debtortrans`
  DROP FOREIGN KEY debtortrans_ibfk_3,
  DROP FOREIGN KEY debtortrans_ibfk_2,
  DROP INDEX TranDate,
  DROP INDEX DebtorNo,
  DROP INDEX Type_2,
  DROP INDEX Type,
  DROP INDEX Order_,
  DROP PRIMARY KEY,
  DROP INDEX TransNo,
  DROP INDEX Prd,
  ADD PRIMARY KEY(`id`),
  ADD INDEX (branchcode),
  ADD INDEX (type),
  ADD INDEX (transno),
  ADD INDEX (order_),
  ADD INDEX (prd),
  ADD INDEX (debtorno),
  ADD INDEX (trandate),
  CHANGE COLUMN reference reference varchar(50) NOT NULL DEFAULT '\'',
  CHANGE COLUMN salesperson salesperson varchar(4) NOT NULL DEFAULT 'DE',
  CHANGE COLUMN packages packages int(11) NOT NULL DEFAULT '1'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`debtortranstaxes`
  DROP FOREIGN KEY debtortranstaxes_ibfk_2,
  DROP FOREIGN KEY debtortranstaxes_ibfk_1,
  DROP PRIMARY KEY,
  DROP INDEX taxauthid,
  ADD PRIMARY KEY(`taxauthid`,`debtortransid`),
  ADD INDEX taxauthid (taxauthid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`debtortype`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`typeid`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`deliverynotes`
  DROP FOREIGN KEY deliverynotes_ibfk_2,
  DROP FOREIGN KEY deliverynotes_ibfk_1,
  DROP FOREIGN KEY deliverynotes_ibfk_2";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`departments`
  ADD COLUMN medical tinyint(4) NOT NULL DEFAULT '0' AFTER description,
  CHANGE COLUMN authoriser authoriser varchar(20) NOT NULL DEFAULT ''";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`discountmatrix`
  DROP FOREIGN KEY discountmatrix_ibfk_1";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`emailsettings`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`id`),
  CHANGE COLUMN host host varchar(50) NOT NULL DEFAULT '',
  CHANGE COLUMN password password varchar(50) NOT NULL DEFAULT '',
  ADD COLUMN security char(4) NOT NULL DEFAULT '' AFTER auth,
  CHANGE COLUMN username username varchar(50) NOT NULL DEFAULT ''";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`factorcompanies`
  DROP INDEX factor_name,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`id`),
  ADD UNIQUE INDEX factor_name (coyname)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`fixedassetcategories`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`categoryid`),
  ADD COLUMN donationact varchar(20) NOT NULL DEFAULT '1' AFTER costact,
  CHANGE COLUMN accumdepnact accumdepnact varchar(20) NOT NULL DEFAULT '0' AFTER disposalact,
  CHANGE COLUMN depnact depnact varchar(20) NOT NULL DEFAULT '0',
  CHANGE COLUMN defaultdepntype defaultdepntype int(11) NOT NULL DEFAULT '1' AFTER defaultdepnrate,
  CHANGE COLUMN defaultdepnrate defaultdepnrate double NOT NULL DEFAULT '0.2' AFTER accumdepnact,
  CHANGE COLUMN disposalact disposalact varchar(20) NOT NULL DEFAULT '80000' AFTER depnact";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`fixedassets`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`assetid`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`fixedassettasks`
  DROP INDEX userresponsible,
  DROP INDEX assetid,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`taskid`),
  ADD INDEX userresponsible (userresponsible),
  ADD INDEX assetid (assetid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`freightcosts`
  DROP FOREIGN KEY freightcosts_ibfk_2,
  DROP FOREIGN KEY freightcosts_ibfk_1,
  CHANGE COLUMN destinationcountry destinationcountry varchar(40) NOT NULL DEFAULT ''";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`glaccountusers`
  DROP INDEX accountuser,
  DROP INDEX useraccount,
  ADD INDEX (userid),
  ADD INDEX (accountcode),
  ADD INDEX accountuser (userid),
  CHANGE COLUMN accountcode accountcode varchar(20) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`gltrans`
  DROP FOREIGN KEY gltrans_ibfk_3,
  DROP FOREIGN KEY gltrans_ibfk_1,
  DROP FOREIGN KEY gltrans_ibfk_2,
  DROP INDEX ChequeNo,
  DROP INDEX Type_and_Number,
  DROP INDEX TranDate,
  DROP INDEX tag,
  DROP INDEX Account,
  DROP INDEX TypeNo,
  DROP INDEX Posted,
  DROP INDEX PeriodNo,
  DROP PRIMARY KEY,
  DROP COLUMN tag,
  ADD PRIMARY KEY(`counterindex`),
  ADD INDEX Posted (posted),
  ADD INDEX TypeNo (typeno),
  ADD INDEX ChequeNo (chequeno),
  ADD INDEX TranDate (trandate),
  ADD INDEX PeriodNo (periodno),
  ADD INDEX Type (type),
  ADD INDEX Account (account)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`grns`
  DROP FOREIGN KEY grns_ibfk_1,
  DROP FOREIGN KEY grns_ibfk_2,
  DROP INDEX PODetailItem,
  DROP INDEX DeliveryDate,
  DROP INDEX SupplierID,
  DROP INDEX ItemCode,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`grnno`),
  ADD INDEX PODetailItem (podetailitem),
  ADD INDEX DeliveryDate (deliverydate),
  ADD INDEX ItemCode (itemcode),
  ADD INDEX SupplierID (supplierid),
  ADD COLUMN reference varchar(50) NOT NULL DEFAULT '\"\"' AFTER stdcostunit,
  CHANGE COLUMN supplierref supplierref varchar(30) NOT NULL DEFAULT ''";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`internalstockcatrole`
  DROP FOREIGN KEY internalstockcatrole_ibfk_1,
  DROP FOREIGN KEY internalstockcatrole_ibfk_2,
  DROP FOREIGN KEY internalstockcatrole_ibfk_4,
  DROP FOREIGN KEY internalstockcatrole_ibfk_3,
  DROP INDEX internalstockcatrole_ibfk_2,
  DROP INDEX internalstockcatrole_ibfk_1,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`secroleid`,`categoryid`),
  ADD INDEX (secroleid),
  ADD INDEX (categoryid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`jnltmplheader`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`templateid`),
  CHANGE COLUMN templateid templateid int(11) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`labelfields`
  DROP PRIMARY KEY,
  DROP INDEX labelid,
  DROP INDEX vpos,
  ADD PRIMARY KEY(`labelfieldid`),
  ADD INDEX labelid (labelid),
  ADD INDEX vpos (vpos)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`locations`
  DROP FOREIGN KEY locations_ibfk_1,
  CHANGE COLUMN usedforwo usedforwo tinyint(1) NOT NULL DEFAULT '1',
  CHANGE COLUMN cashsalecustomer cashsalecustomer varchar(10) NOT NULL,
  CHANGE COLUMN glaccountcode glaccountcode varchar(20) NOT NULL DEFAULT '',
  CHANGE COLUMN allowinvoicing allowinvoicing tinyint(1) NOT NULL DEFAULT '1'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`locstock`
  DROP FOREIGN KEY locstock_ibfk_1,
  DROP FOREIGN KEY locstock_ibfk_2,
  DROP INDEX bin,
  DROP PRIMARY KEY,
  DROP INDEX StockID,
  ADD PRIMARY KEY(`stockid`),
  ADD INDEX StockID (stockid),
  ADD COLUMN date_updated timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  ADD COLUMN date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER bin";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`loctransfercancellations`
  DROP INDEX refstockid,
  DROP INDEX cancelrefstockid,
COLLATE=utf8_general_ci";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`loctransfers`
  DROP FOREIGN KEY loctransfers_ibfk_1,
  DROP FOREIGN KEY loctransfers_ibfk_3,
  DROP FOREIGN KEY loctransfers_ibfk_2,
  DROP INDEX Reference,
  DROP INDEX RecLoc,
  DROP INDEX ShipLoc,
  DROP INDEX StockID,
  ADD INDEX Reference (stockid,reference),
  ADD INDEX ShipLoc (shiploc),
  ADD INDEX RecLoc (recloc),
  ADD INDEX StockID (stockid),
  ADD COLUMN shiploccontainer varchar(10) NOT NULL DEFAULT '' AFTER shiploc,
  ADD COLUMN recloccontainer varchar(10) NOT NULL DEFAULT '' AFTER recloc,
  CHANGE COLUMN recloc recloc varchar(7) NOT NULL DEFAULT ''";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`mailgroupdetails`
  DROP FOREIGN KEY mailgroupdetails_ibfk_1,
  DROP FOREIGN KEY mailgroupdetails_ibfk_2,
  DROP INDEX groupname,
  DROP INDEX userid,
  ADD INDEX groupname (groupname),
  ADD INDEX userid (userid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`manufacturers`
  DROP INDEX manufacturers_name,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`manufacturers_id`),
  ADD INDEX manufacturers_name (manufacturers_name)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`mrpcalendar`
  DROP PRIMARY KEY,
  DROP INDEX daynumber,
  ADD PRIMARY KEY(`calendardate`),
  ADD INDEX daynumber (daynumber)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`mrpdemands`
  DROP FOREIGN KEY mrpdemands_ibfk_2,
  DROP FOREIGN KEY mrpdemands_ibfk_1,
  DROP PRIMARY KEY,
  DROP INDEX StockID,
  DROP INDEX mrpdemands_ibfk_1,
  ADD PRIMARY KEY(`demandid`),
  ADD INDEX StockID (stockid),
  ADD INDEX mrpdemands_ibfk_1 (mrpdemandtype)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`mrpdemandtypes`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`mrpdemandtype`),
  ADD INDEX mrpdemandtype (mrpdemandtype)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`mrpplannedorders`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`id`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`mrpsupplies`
  DROP INDEX part,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`id`),
  ADD INDEX part (part)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`offers`
  DROP FOREIGN KEY offers_ibfk_1,
  DROP FOREIGN KEY offers_ibfk_2,
  DROP INDEX offers_ibfk_1,
  DROP PRIMARY KEY,
  DROP INDEX offers_ibfk_2,
  ADD PRIMARY KEY(`offerid`),
  ADD INDEX offers_ibfk_2 (stockid),
  ADD INDEX offers_ibfk_1 (supplierid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`orderdeliverydifferenceslog`
  DROP FOREIGN KEY orderdeliverydifferenceslog_ibfk_3,
  DROP FOREIGN KEY orderdeliverydifferenceslog_ibfk_1,
  DROP FOREIGN KEY orderdeliverydifferenceslog_ibfk_2,
  DROP FOREIGN KEY orderdeliverydifferenceslog_ibfk_2,
  DROP INDEX DebtorNo,
  DROP INDEX StockID,
  DROP INDEX OrderNo,
  ADD INDEX StockID (stockid),
  ADD INDEX OrderNo (orderno),
  ADD INDEX DebtorNo (branch,debtorno)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pcashdetails`
  DROP INDEX tabcodedate,
  DROP PRIMARY KEY,
  DROP COLUMN tag,
  DROP COLUMN purpose,
  ADD PRIMARY KEY(`counterindex`),
  ADD INDEX tabcodedate (date,tabcode,counterindex,codeexpense),
  CHANGE COLUMN codeexpense codeexpense varchar(20) NOT NULL AFTER date,
  CHANGE COLUMN notes notes text NOT NULL,
  CHANGE COLUMN receipt receipt text NULL COMMENT 'filename or path to scanned receipt or code of receipt to find physical receipt if tax guys or auditors show up' AFTER notes,
  CHANGE COLUMN amount amount double NOT NULL AFTER codeexpense,
  CHANGE COLUMN authorized authorized date NOT NULL COMMENT 'date cash assigment was revised and authorized by authorizer from tabs table' AFTER amount,
  CHANGE COLUMN date date date NOT NULL,
  CHANGE COLUMN posted posted tinyint(4) NOT NULL COMMENT 'has (or has not) been posted into gltrans' AFTER authorized";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pcashdetailtaxes`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`counterindex`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pcexpenses`
  DROP FOREIGN KEY pcexpenses_ibfk_1,
  DROP INDEX glaccount,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`codeexpense`),
  ADD INDEX glaccount (glaccount)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pcreceipts`
  DROP FOREIGN KEY pcreceipts_ibfk_1,
  DROP INDEX pcreceipts_ibfk_1,
  DROP PRIMARY KEY,
  DROP COLUMN hashfile,
  DROP COLUMN counterindex,
  DROP COLUMN extension,
  ADD PRIMARY KEY(`name`,`pccashdetail`),
  CHANGE COLUMN pccashdetail pccashdetail int(11) NOT NULL,
  CHANGE COLUMN type type varchar(30) NOT NULL,
  CHANGE COLUMN size size int(11) NOT NULL,
  ADD COLUMN content mediumblob NOT NULL AFTER size,
  ADD COLUMN name varchar(30) NOT NULL AFTER pccashdetail";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pctabexpenses`
  DROP FOREIGN KEY pctabexpenses_ibfk_1,
  DROP FOREIGN KEY pctabexpenses_ibfk_2,
  DROP INDEX typetabcode,
  DROP INDEX codeexpense,
  ADD INDEX typetabcode (typetabcode),
  ADD INDEX codeexpense (codeexpense)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pctabs`
  DROP FOREIGN KEY pctabs_ibfk_2,
  DROP FOREIGN KEY pctabs_ibfk_5,
  DROP FOREIGN KEY pctabs_ibfk_1,
  DROP FOREIGN KEY pctabs_ibfk_3,
  DROP INDEX typetabcode,
  DROP INDEX usercode,
  DROP INDEX currency,
  DROP INDEX glaccountassignment,
  DROP INDEX authorizer,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`tabcode`),
  ADD INDEX currency (currency),
  ADD INDEX usercode (usercode),
  ADD INDEX typetabcode (typetabcode),
  ADD INDEX glaccountassignment (glaccountassignment),
  ADD INDEX authorizer (authorizer),
  CHANGE COLUMN authorizer authorizer varchar(20) NOT NULL COMMENT 'code of user from www_users',
  CHANGE COLUMN assigner assigner varchar(20) NOT NULL COMMENT 'Cash assigner for the tab',
  CHANGE COLUMN authorizerexpenses authorizerexpenses varchar(20) NOT NULL DEFAULT '\"\"'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pickinglistdetails`
  DROP FOREIGN KEY pickinglistdetails_ibfk_1,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`pickinglistno`,`pickinglistlineno`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pickinglists`
  DROP FOREIGN KEY pickinglists_ibfk_1,
  DROP PRIMARY KEY,
  DROP INDEX pickinglists_ibfk_1,
  ADD PRIMARY KEY(`pickinglistno`),
  ADD INDEX pickinglists_ibfk_1 (orderno)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pickreq`
  DROP FOREIGN KEY pickreq_ibfk_2,
  DROP FOREIGN KEY pickreq_ibfk_1,
  DROP INDEX loccode,
  DROP INDEX status,
  DROP INDEX closed,
  DROP PRIMARY KEY,
  DROP INDEX orderno,
  DROP INDEX requestdate,
  DROP INDEX shipdate,
  ADD PRIMARY KEY(`prid`),
  ADD INDEX orderno (orderno),
  ADD INDEX shipdate (shipdate),
  ADD INDEX status (status),
  ADD INDEX loccode (loccode),
  ADD INDEX closed (closed),
  ADD INDEX requestdate (requestdate)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pickreqdetails`
  DROP FOREIGN KEY pickreqdetails_ibfk_1,
  DROP FOREIGN KEY pickreqdetails_ibfk_2,
  DROP PRIMARY KEY,
  DROP INDEX stockid,
  DROP INDEX prid,
  ADD PRIMARY KEY(`detailno`),
  ADD INDEX stockid (stockid),
  ADD INDEX prid (prid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pickserialdetails`
  DROP FOREIGN KEY pickserialdetails_ibfk_1,
  DROP FOREIGN KEY pickserialdetails_ibfk_2,
  DROP FOREIGN KEY pickserialdetails_ibfk_2";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`pricematrix`
  CHANGE COLUMN currabrev currabrev char(3) NOT NULL DEFAULT '\"\"'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`prices`
  DROP INDEX StockID,
  DROP INDEX CurrAbrev,
  DROP PRIMARY KEY,
  DROP INDEX DebtorNo,
  DROP INDEX TypeAbbrev,
  ADD INDEX (`startdate`,`typeabbrev`,`currabrev`,`debtorno`,`enddate`,`stockid`,`branchcode`),
  ADD PRIMARY KEY(`startdate`,`typeabbrev`,`currabrev`,`debtorno`,`enddate`,`stockid`,`branchcode`),
  ADD INDEX StockID (stockid),
  ADD INDEX CurrAbrev (currabrev),
  ADD INDEX DebtorNo (debtorno),
  ADD INDEX TypeAbbrev (typeabbrev),
  ADD COLUMN date_updated timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  ADD COLUMN date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER enddate";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`prodspecs`
  DROP INDEX testid,
  ADD INDEX (`testid`,`keyval`),
  ADD INDEX (testid),
  CHANGE COLUMN rangemin rangemin float NOT NULL DEFAULT '0',
  CHANGE COLUMN rangemax rangemax float NOT NULL DEFAULT '0'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`purchdata`
  DROP FOREIGN KEY purchdata_ibfk_1,
  DROP FOREIGN KEY purchdata_ibfk_2,
  DROP INDEX SupplierNo,
  DROP PRIMARY KEY,
  DROP INDEX StockID,
  DROP INDEX Preferred,
  ADD PRIMARY KEY(`qtygreaterthan`,`stockid`,`effectivefrom`,`supplierno`),
  ADD INDEX StockID (stockid),
  ADD INDEX Preferred (preferred),
  ADD INDEX SupplierNo (supplierno),
  ADD COLUMN qtygreaterthan int(11) NOT NULL DEFAULT '0' AFTER price,
  CHANGE COLUMN effectivefrom effectivefrom date NOT NULL AFTER preferred,
  CHANGE COLUMN minorderqty minorderqty int(11) NOT NULL DEFAULT '1' AFTER suppliers_partno,
  CHANGE COLUMN suppliers_partno suppliers_partno varchar(50) NOT NULL DEFAULT '' AFTER effectivefrom,
  CHANGE COLUMN supplierdescription supplierdescription char(50) NOT NULL DEFAULT '' AFTER conversionfactor,
  CHANGE COLUMN preferred preferred tinyint(4) NOT NULL DEFAULT '0' AFTER leadtime,
  CHANGE COLUMN suppliersuom suppliersuom char(50) NOT NULL DEFAULT '',
  CHANGE COLUMN conversionfactor conversionfactor double NOT NULL DEFAULT '1' AFTER suppliersuom,
  CHANGE COLUMN leadtime leadtime smallint(6) NOT NULL DEFAULT '1' AFTER supplierdescription";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`purchorderauth`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`currabrev`),
  CHANGE COLUMN authlevel authlevel int(11) NOT NULL DEFAULT '0'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`purchorderdetails`
  DROP FOREIGN KEY purchorderdetails_ibfk_1,
  DROP INDEX GLCode,
  DROP INDEX OrderNo,
  DROP PRIMARY KEY,
  DROP INDEX ShiptRef,
  DROP INDEX DeliveryDate,
  DROP INDEX ItemCode,
  ADD PRIMARY KEY(`podetailitem`),
  ADD INDEX DeliveryDate (deliverydate),
  ADD INDEX OrderNo (orderno),
  ADD INDEX ShiptRef (shiptref),
  ADD INDEX GLCode (glcode),
  ADD INDEX ItemCode (itemcode)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`purchorders`
  DROP FOREIGN KEY purchorders_ibfk_2,
  DROP FOREIGN KEY purchorders_ibfk_1,
  DROP INDEX OrdDate,
  DROP PRIMARY KEY,
  DROP INDEX SupplierNo,
  DROP INDEX IntoStockLocation,
  ADD PRIMARY KEY(`orderno`),
  ADD INDEX IntoStockLocation (intostocklocation),
  ADD INDEX SupplierNo (supplierno),
  ADD INDEX OrdDate (orddate),
  ADD COLUMN authoriser varchar(20) NOT NULL DEFAULT '' AFTER initiator,
  CHANGE COLUMN tel tel varchar(30) NOT NULL DEFAULT '' AFTER deladd6,
  CHANGE COLUMN intostocklocation intostocklocation varchar(5) NOT NULL DEFAULT '' AFTER requisitionno,
  CHANGE COLUMN realorderno realorderno varchar(16) NOT NULL DEFAULT '' AFTER revised,
  CHANGE COLUMN revised revised date NOT NULL DEFAULT '0000-00-00' AFTER version,
  CHANGE COLUMN stat_comment stat_comment text NOT NULL AFTER status,
  CHANGE COLUMN deladd1 deladd1 varchar(40) NOT NULL DEFAULT '' AFTER intostocklocation,
  CHANGE COLUMN port port varchar(40) NOT NULL DEFAULT '' AFTER paymentterms,
  CHANGE COLUMN version version decimal(3,2) NOT NULL DEFAULT '1.00' AFTER contact,
  CHANGE COLUMN deliverydate deliverydate date NOT NULL DEFAULT '0000-00-00' AFTER deliveryby,
  CHANGE COLUMN status status varchar(12) NOT NULL DEFAULT '' AFTER deliverydate,
  CHANGE COLUMN paymentterms paymentterms char(2) NOT NULL DEFAULT '' AFTER stat_comment,
  CHANGE COLUMN suppdeladdress1 suppdeladdress1 varchar(40) NOT NULL DEFAULT '' AFTER tel,
  CHANGE COLUMN suppdeladdress2 suppdeladdress2 varchar(40) NOT NULL DEFAULT '' AFTER suppdeladdress1,
  CHANGE COLUMN suppdeladdress3 suppdeladdress3 varchar(40) NOT NULL DEFAULT '' AFTER suppdeladdress2,
  CHANGE COLUMN suppdeladdress4 suppdeladdress4 varchar(40) NOT NULL DEFAULT '' AFTER suppdeladdress3,
  CHANGE COLUMN suppdeladdress5 suppdeladdress5 varchar(20) NOT NULL DEFAULT '' AFTER suppdeladdress4,
  CHANGE COLUMN suppdeladdress6 suppdeladdress6 varchar(15) NOT NULL DEFAULT '' AFTER suppdeladdress5,
  CHANGE COLUMN deliveryby deliveryby varchar(100) NOT NULL DEFAULT '' AFTER realorderno,
  CHANGE COLUMN deladd3 deladd3 varchar(40) NOT NULL DEFAULT '' AFTER deladd2,
  CHANGE COLUMN deladd2 deladd2 varchar(40) NOT NULL DEFAULT '' AFTER deladd1,
  CHANGE COLUMN initiator initiator varchar(20) NOT NULL,
  CHANGE COLUMN deladd6 deladd6 varchar(15) NOT NULL DEFAULT '' AFTER deladd5,
  CHANGE COLUMN deladd5 deladd5 varchar(20) NOT NULL DEFAULT '' AFTER deladd4,
  CHANGE COLUMN deladd4 deladd4 varchar(40) NOT NULL DEFAULT '' AFTER deladd3,
  CHANGE COLUMN requisitionno requisitionno varchar(15) NULL,
  CHANGE COLUMN suppliercontact suppliercontact varchar(30) NOT NULL DEFAULT '' AFTER suppdeladdress6,
  CHANGE COLUMN contact contact varchar(30) NOT NULL DEFAULT '' AFTER supptel,
  CHANGE COLUMN supptel supptel varchar(30) NOT NULL DEFAULT '' AFTER suppliercontact";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`qasamples`
  DROP FOREIGN KEY qasamples_ibfk_1,
  DROP INDEX prodspeckey,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`sampleid`),
  ADD INDEX prodspeckey (lotkey,prodspeckey)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`qatests`
  DROP INDEX name,
  DROP INDEX groupname,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`testid`),
  ADD INDEX name (name),
  ADD INDEX groupname (groupby,name)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`recurringsalesorders`
  DROP FOREIGN KEY recurringsalesorders_ibfk_1,
  DROP FOREIGN KEY recurringsalesorders_ibfk_1,
  CHANGE COLUMN deladd2 deladd2 varchar(40) NOT NULL,
  CHANGE COLUMN deladd3 deladd3 varchar(40) NOT NULL,
  CHANGE COLUMN deladd4 deladd4 varchar(40) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`recurrsalesorderdetails`
  DROP FOREIGN KEY recurrsalesorderdetails_ibfk_1,
  DROP FOREIGN KEY recurrsalesorderdetails_ibfk_2,
  DROP INDEX orderno,
  DROP INDEX stkcode,
  ADD INDEX orderno (recurrorderno),
  ADD INDEX stkcode (stkcode)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`relateditems`
  DROP INDEX Related,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`related`,`stockid`),
  ADD UNIQUE INDEX Related (related,stockid),
  ADD COLUMN date_updated timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  ADD COLUMN date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER related,
COLLATE=utf8_general_ci";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`reportcolumns`
  DROP FOREIGN KEY reportcolumns_ibfk_1,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`colno`,`reportid`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`reportfields`
  DROP PRIMARY KEY,
  DROP INDEX reportid,
  ADD PRIMARY KEY(`id`),
  ADD INDEX reportid (reportid),
ENGINE=InnoDB, AUTO_INCREMENT=1811";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`reportheaders`
  DROP INDEX ReportHeading,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`reportid`),
  ADD INDEX ReportHeading (reportheading)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`reports`
  DROP INDEX name,
  ADD INDEX name (groupname,reportname)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`salesanalysis`
  DROP FOREIGN KEY salesanalysis_ibfk_1,
  DROP INDEX CustBranch,
  DROP INDEX StkCategory,
  DROP INDEX PeriodNo,
  DROP INDEX Cust,
  DROP INDEX Salesperson,
  DROP INDEX StockID,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`id`),
  ADD INDEX Salesperson (salesperson),
  ADD INDEX CustBranch (custbranch),
  ADD INDEX Cust (cust),
  ADD INDEX StockID (stockid),
  ADD INDEX StkCategory (stkcategory),
  ADD INDEX PeriodNo (periodno),
  CHANGE COLUMN salesperson salesperson varchar(4) NOT NULL DEFAULT '\''";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`salescat`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`salescatid`),
  CHANGE COLUMN active active int(1) NOT NULL DEFAULT '1',
  ADD COLUMN date_updated timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  ADD COLUMN date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER active,
  CHANGE COLUMN salescatname salescatname varchar(50) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`salescatprod`
  DROP FOREIGN KEY salescatprod_ibfk_2,
  DROP FOREIGN KEY salescatprod_ibfk_1,
  DROP INDEX salescatid,
  DROP INDEX stockid,
  DROP INDEX manufacturer_id,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`salescatid`,`stockid`),
  ADD INDEX stockid (stockid),
  ADD INDEX salescatid (salescatid),
  ADD INDEX manufacturers_id (manufacturers_id),
  CHANGE COLUMN featured featured int(11) NOT NULL DEFAULT '0',
  ADD COLUMN date_updated timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  ADD COLUMN date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER featured,
  CHANGE COLUMN manufacturers_id manufacturers_id int(11) NOT NULL DEFAULT '0'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`salesglpostings`
  DROP PRIMARY KEY,
  DROP INDEX Area_StkCat,
  DROP INDEX Area,
  DROP INDEX StkCat,
  DROP INDEX SalesType,
  ADD PRIMARY KEY(`id`),
  ADD INDEX StkCat (stkcat),
  ADD INDEX SalesType (salestype),
  ADD INDEX Area (area)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`salesman`
  DROP PRIMARY KEY,
  DROP COLUMN commissionrate1,
  DROP COLUMN commissionrate2,
  DROP COLUMN breakpoint,
  ADD PRIMARY KEY(`salesmancode`),
  ADD INDEX fk_salesman_1 (salesarea),
  ADD COLUMN commissionperiod int(1) NOT NULL DEFAULT '0' AFTER current,
  ADD COLUMN salesarea char(3) NOT NULL DEFAULT '' AFTER salesmanname,
  ADD COLUMN glaccount varchar(20) NOT NULL DEFAULT '1',
  ADD COLUMN commissiontypeid tinyint(4) NOT NULL DEFAULT '0',
  ADD COLUMN manager int(1) NOT NULL DEFAULT '0',
  CHANGE COLUMN current current tinyint(4) NOT NULL COMMENT 'Salesman current (1) or not (0)',
  CHANGE COLUMN smantel smantel char(20) NOT NULL DEFAULT '',
  CHANGE COLUMN smanfax smanfax char(20) NOT NULL DEFAULT '' AFTER smantel,
  CHANGE COLUMN salesmancode salesmancode varchar(4) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`salesorderdetails`
  DROP FOREIGN KEY salesorderdetails_ibfk_2,
  DROP FOREIGN KEY salesorderdetails_ibfk_1,
  DROP INDEX OrderNo,
  DROP PRIMARY KEY,
  DROP INDEX StkCode,
  ADD PRIMARY KEY(`orderlineno`,`orderno`),
  ADD INDEX OrderNo (orderno),
  ADD INDEX StkCode (stkcode)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`salesorders`
  DROP FOREIGN KEY salesorders_ibfk_2,
  DROP FOREIGN KEY salesorders_ibfk_1,
  DROP FOREIGN KEY salesorders_ibfk_1,
  DROP FOREIGN KEY salesorders_ibfk_3,
  DROP INDEX BranchCode,
  DROP INDEX DebtorNo,
  DROP INDEX salesperson,
  DROP INDEX poplaced,
  ADD INDEX salesperson (salesperson),
  ADD INDEX poplaced (poplaced),
  ADD INDEX BranchCode (branchcode),
  ADD INDEX DebtorNo (debtorno),
  CHANGE COLUMN contactemail contactemail varchar(40) NOT NULL,
  CHANGE COLUMN deladd2 deladd2 varchar(40) NOT NULL,
  CHANGE COLUMN deladd3 deladd3 varchar(40) NOT NULL,
  CHANGE COLUMN deladd4 deladd4 varchar(40) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`sampleresults`
  DROP FOREIGN KEY sampleresults_ibfk_1,
  DROP INDEX sampleid,
  DROP PRIMARY KEY,
  DROP INDEX testid,
  ADD PRIMARY KEY(`resultid`),
  ADD INDEX sampleid (sampleid),
  ADD INDEX testid (testid),
  CHANGE COLUMN rangemin rangemin float NOT NULL DEFAULT '0',
  CHANGE COLUMN rangemax rangemax float NOT NULL DEFAULT '0'";
$Result = DB_query($db, $SQL);

$SQL = array();
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'PDFGLJournalCN.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'PcTabExpensesList.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'Z_ImportPriceList.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'GoodsReceivedNotInvoiced.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'Z_GLAccountUsersCopyAuthority.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'PcAuthorizeCash.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'PcAssignCashTabToTab.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'OrderEntryDiscountPricing'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'BOMs_SingleLevel.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'PcAuthorizeCheque'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'Z_FixGLTransPeriods'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'Employees.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'PcAnalysis.php'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'CostUpdate'";
$SQL[] = "DELETE FROM " . $WebERPDB . ".`scripts` WHERE `script` = 'InventoryPlanningPrefSupplier_CSV.php'";

$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('JobCards.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectOT.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_ChangeSerialNumber.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlLoanRepayments.php', '10', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCMaintainWards.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCOutpatientAdmission.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectPayroll.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectTaxStatus.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCMaintainEncounterTypes.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('unpaid_invoice.php', '2', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlOthIncome.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('RunScheduledJobs.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('MailSalesReport.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlEmployeeMaster.php', '5', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlLoanFile.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepPayrollRegister.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectDeduction.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepTaxYTD.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('PO_Chk_ShiptRef_JobRef.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepSSSPremium.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ProjectCosting.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepPHPremium.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlDepartments.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepCashTrans.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCInpatientAdmission.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ABCRunAnalysis.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('SelectProject.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepGROSSPAYPremium.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ManualContents.php', '1', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ChangePassword.php', '1', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('work_orders.php', '2', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('MenuManager.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSSS.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('bank_trans.php', '2', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('PrintInvoice.php', '3', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlPositions.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('RetainedEarningsReconciliation.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('OcOpenCartToKwaMoja.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_UpdateSystypes.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlHDMF.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('latest_stock_status.php', '2', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ReportBug.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('BackupDatabase.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ModuleEditor.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectLoan.php', '10', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectEmployee.php', '10', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCHospitalConfiguration.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('StockTypes.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlTaxStatus.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlALD.php', '10', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectTD.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSSC.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_UpgradeDatabase.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('latest_po.php', '2', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('PluginUnInstall.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlTaxAuthority.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepTax.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_ResetStockCosts.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ProjectBOM.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('MonthlyBankTransactions.php', '8', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_ImportPurchaseData.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlEmployers.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlEditPayroll.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlCurrencies.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlEmploymentStatus.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('SalesCommissionRates.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlCostCenter.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('mrp_dashboard.php', '2', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('QuickInvoice.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCWardOverview.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('UploadPriceList.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('total_dashboard.php', '2', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectPayTrans.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ImportSalesPriceList.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_RebuildSalesAnalysis.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('PluginInstall.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSC.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('DefineWarehouse.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('report_runner.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlPH.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlabout.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepPaySlip.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('GLBalanceSheet_new.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRegTimeEntry.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Projects.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ABCRankingGroups.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('AsteriskImport.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCMaintainWardRooms.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlOthIncTable.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('GoodsReceivedButNotInvoiced.php', '2', 'Shows the list of Goods Received Not Yet Invoiced, both in supplier currency and home currency. Total in home curency should match the GL Account for Goods received not invoiced. Any discrepancy is due to multicurrency errors.')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlAuthoriseLoans.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlTaxAuthorityRates.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('scriptsAccess.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlPayPeriod.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('OcKwaMojaToOpenCartDaily.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('UpdateFavourites.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlOvertime.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlPaye.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_ImportCustBranch.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('SalesCommissionTypes.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCAllocatePatientsToBeds.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('PrintCredit.php', '3', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlTardiness.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlReNSSFPremium.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('POClearBackOrders.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('GLAccountUsersCopyAuthority.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectOthIncome.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('latest_grns.php', '4', 'Shows latest goods received into the company')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('latest_po_auth.php', '2', 'Shows latest supplier orders awaiting authorisation.')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ProjectOtherReqts.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('ABCRankingMethods.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('JobScheduler.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('SalesCommissionReports.php', '3', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_ImportSuppliers.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlMsgBox.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('OcKwaMojaToOpenCartHourly.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepBPPremium.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('EDISendInvoices_Reece.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('PluginUpload.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Donors.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepHDMFPremium.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlLoanTable.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlSelectRT.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('EnableBranches.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepBankTrans.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlOTFile.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('KCMCRegister.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlLoanPayments.php', '10', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('customer_orders.php', '2', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('DashboardConfig.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('SearchCustomers.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlRepPayrollRegYTD.php', '12', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('InitialScripts.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('Z_AutoCustomerAllocations.php', '15', '')";
$SQL[] = "INSERT INTO " . $WebERPDB . ".`scripts` (`script`, `pagesecurity`, `description`) VALUES('prlTax.php', '15', '')";
foreach ($SQL as $Query) {
	$Result = DB_query($db, $Query);
}
unset($SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`securitygroups`
  DROP FOREIGN KEY securitygroups_secroleid_fk,
  DROP FOREIGN KEY securitygroups_tokenid_fk";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`sellthroughsupport`
  DROP INDEX effectivefrom,
  DROP PRIMARY KEY,
  DROP INDEX categoryid,
  DROP INDEX effectiveto,
  DROP INDEX debtorno,
  DROP INDEX supplierno,
  DROP INDEX stockid,
  ADD PRIMARY KEY(`id`),
  ADD INDEX supplierno (supplierno),
  ADD INDEX effectivefrom (effectivefrom),
  ADD INDEX debtorno (debtorno),
  ADD INDEX effectiveto (effectiveto),
  ADD INDEX stockid (stockid),
  ADD INDEX categoryid (categoryid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`shipmentcharges`
  DROP FOREIGN KEY shipmentcharges_ibfk_1,
  DROP FOREIGN KEY shipmentcharges_ibfk_2,
  DROP INDEX TransType_2,
  DROP INDEX TransType,
  DROP INDEX ShiptRef,
  DROP INDEX StockID,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`shiptchgid`),
  ADD INDEX (transno,transtype),
  ADD INDEX ShiptRef (shiptref),
  ADD INDEX StockID (stockid),
  ADD INDEX (transtype)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`shipments`
  DROP FOREIGN KEY shipments_ibfk_1,
  DROP PRIMARY KEY,
  DROP INDEX ShipperRef,
  DROP INDEX ETA,
  DROP INDEX SupplierID,
  DROP INDEX Vessel,
  ADD PRIMARY KEY(`shiptref`),
  ADD INDEX ETA (eta),
  ADD INDEX SupplierID (supplierid),
  ADD INDEX Vessel (vessel),
  ADD INDEX ShipperRef (voyageref),
  ADD COLUMN shipmentdate date NOT NULL DEFAULT '1901-01-01' AFTER vessel,
  CHANGE COLUMN supplierid supplierid varchar(10) NOT NULL DEFAULT '' AFTER accumvalue,
  CHANGE COLUMN accumvalue accumvalue double NOT NULL DEFAULT '0' AFTER eta,
  CHANGE COLUMN eta eta datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  CHANGE COLUMN closed closed tinyint(4) NOT NULL DEFAULT '0' AFTER supplierid";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`shippers`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`shipper_id`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockcategory`
  DROP INDEX CategoryDescription,
  DROP PRIMARY KEY,
  DROP INDEX StockType,
  ADD PRIMARY KEY(`categoryid`),
  ADD INDEX StockType (stocktype),
  ADD INDEX CategoryDescription (categorydescription),
  CHANGE COLUMN defaulttaxcatid defaulttaxcatid int(1) NOT NULL DEFAULT '1' AFTER categorydescription,
  ADD COLUMN donationact varchar(20) NOT NULL DEFAULT '1' AFTER wipact,
  CHANGE COLUMN materialuseagevarac materialuseagevarac varchar(20) NOT NULL DEFAULT '80000' AFTER purchpricevaract,
  CHANGE COLUMN issueglact issueglact varchar(20) NOT NULL DEFAULT '0' AFTER adjglact,
  CHANGE COLUMN purchpricevaract purchpricevaract varchar(20) NOT NULL DEFAULT '80000' AFTER issueglact,
  CHANGE COLUMN wipact wipact varchar(20) NOT NULL DEFAULT '0' AFTER materialuseagevarac,
  CHANGE COLUMN stocktype stocktype char(1) NOT NULL DEFAULT 'F' AFTER defaulttaxcatid,
  CHANGE COLUMN stockact stockact varchar(20) NOT NULL DEFAULT '0' AFTER stocktype,
  CHANGE COLUMN adjglact adjglact varchar(20) NOT NULL DEFAULT '0' AFTER stockact";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockcatproperties`
  DROP FOREIGN KEY stockcatproperties_ibfk_1,
  DROP INDEX categoryid,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`stkcatpropid`),
  ADD INDEX categoryid (categoryid)";
$Result = DB_query($db, $SQL);

$SQL ="ALTER TABLE " . $WebERPDB . ".`stockcheckfreeze`
  DROP FOREIGN KEY stockcheckfreeze_ibfk_2,
  DROP FOREIGN KEY stockcheckfreeze_ibfk_1,
  DROP PRIMARY KEY,
  DROP INDEX LocCode,
  ADD PRIMARY KEY(`loccode`,`stockid`),
  ADD INDEX LocCode (loccode)";
$Result = DB_query($db, $SQL);

$SQL ="ALTER TABLE " . $WebERPDB . ".`stockcounts`
  DROP FOREIGN KEY stockcounts_ibfk_2,
  DROP FOREIGN KEY stockcounts_ibfk_1,
  DROP INDEX LocCode,
  ADD INDEX container (loccode,container),
  ADD INDEX LocCode (loccode),
  ADD COLUMN container varchar(10) NOT NULL DEFAULT '' AFTER loccode,
  CHANGE COLUMN qtycounted qtycounted double NOT NULL DEFAULT '0',
  CHANGE COLUMN reference reference varchar(20) NOT NULL DEFAULT '' AFTER qtycounted";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockdescriptiontranslations`
  DROP PRIMARY KEY,
  DROP COLUMN longdescriptiontranslation,
  ADD PRIMARY KEY(`language_id`,`stockid`),
  CHANGE COLUMN descriptiontranslation descriptiontranslation varchar(50) NOT NULL,
  CHANGE COLUMN needsrevision needsrevision tinyint(1) NOT NULL DEFAULT '0'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockitemproperties`
  DROP FOREIGN KEY stockitemproperties_ibfk_4,
  DROP FOREIGN KEY stockitemproperties_ibfk_1,
  DROP FOREIGN KEY stockitemproperties_ibfk_2,
  DROP FOREIGN KEY stockitemproperties_ibfk_5,
  DROP FOREIGN KEY stockitemproperties_ibfk_6,
  DROP FOREIGN KEY stockitemproperties_ibfk_3,
  DROP INDEX value,
  DROP INDEX stockid,
  DROP PRIMARY KEY,
  DROP INDEX stkcatpropid,
  ADD PRIMARY KEY(`stkcatpropid`,`stockid`),
  ADD INDEX value (value),
  ADD INDEX stkcatpropid (stkcatpropid),
  ADD INDEX stockid (stockid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockmaster`
  DROP FOREIGN KEY stockmaster_ibfk_1,
  DROP FOREIGN KEY stockmaster_ibfk_2,
  DROP INDEX StockID,
  DROP INDEX MBflag,
  DROP INDEX Description,
  DROP INDEX CategoryID,
  DROP PRIMARY KEY,
  DROP COLUMN labourcost,
  DROP COLUMN materialcost,
  DROP COLUMN overheadcost,
  ADD PRIMARY KEY(`stockid`),
  ADD INDEX CategoryID (categoryid),
  ADD INDEX MBflag (mbflag),
  ADD INDEX Description (description),
  ADD INDEX StockID (stockid),
  ADD INDEX stockmaster_ibix_1 (taxcatid),
  ADD COLUMN appendfile varchar(40) NOT NULL DEFAULT 'none' AFTER serialised,
  ADD COLUMN date_updated timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  ADD COLUMN height decimal(15,8) NOT NULL DEFAULT '0.00000000',
  ADD COLUMN width decimal(15,8) NOT NULL DEFAULT '0.00000000',
  ADD COLUMN length decimal(15,8) NOT NULL DEFAULT '0.00000000' AFTER netweight,
  ADD COLUMN drawingnumber varchar(50) NOT NULL DEFAULT '' AFTER barcode,
  ADD COLUMN unitsdimension varchar(15) NOT NULL DEFAULT 'mm',
  ADD COLUMN date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER lastcostupdate,
  CHANGE COLUMN lowestlevel lowestlevel smallint(6) NOT NULL DEFAULT '0',
  CHANGE COLUMN shrinkfactor shrinkfactor double NOT NULL DEFAULT '0' AFTER pansize,
  CHANGE COLUMN discountcategory discountcategory char(2) NOT NULL DEFAULT '',
  CHANGE COLUMN serialised serialised tinyint(4) NOT NULL DEFAULT '0' AFTER taxcatid,
  CHANGE COLUMN nextserialno nextserialno bigint(20) NOT NULL DEFAULT '0' AFTER shrinkfactor,
  CHANGE COLUMN grossweight grossweight decimal(20,4) NOT NULL DEFAULT '0.0000' AFTER volume,
  CHANGE COLUMN controlled controlled tinyint(4) NOT NULL DEFAULT '0' AFTER discontinued,
  CHANGE COLUMN perishable perishable tinyint(1) NOT NULL DEFAULT '0',
  CHANGE COLUMN barcode barcode varchar(50) NOT NULL DEFAULT '' AFTER grossweight,
  CHANGE COLUMN volume volume decimal(20,4) NOT NULL DEFAULT '0.0000' AFTER eoq,
  CHANGE COLUMN lastcostupdate lastcostupdate date NOT NULL DEFAULT '0000-00-00',
  CHANGE COLUMN netweight netweight decimal(20,4) NOT NULL DEFAULT '0.0000' AFTER nextserialno,
  CHANGE COLUMN taxcatid taxcatid tinyint(4) NOT NULL DEFAULT '1' AFTER discountcategory,
  CHANGE COLUMN eoq eoq double NOT NULL DEFAULT '0' AFTER controlled,
  CHANGE COLUMN decimalplaces decimalplaces tinyint(4) NOT NULL DEFAULT '0' AFTER perishable,
  CHANGE COLUMN pansize pansize double NOT NULL DEFAULT '0' AFTER decimalplaces,
  CHANGE COLUMN discontinued discontinued tinyint(4) NOT NULL DEFAULT '0' AFTER lowestlevel";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockmoves`
  DROP FOREIGN KEY stockmoves_ibfk_1,
  DROP FOREIGN KEY stockmoves_ibfk_3,
  DROP FOREIGN KEY stockmoves_ibfk_4,
  DROP FOREIGN KEY stockmoves_ibfk_2,
  DROP INDEX StockID_2,
  DROP INDEX Type,
  DROP INDEX TransNo,
  DROP INDEX TranDate,
  DROP INDEX DebtorNo,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`stkmoveno`),
  ADD INDEX TransNo (transno),
  ADD INDEX StockID_2 (stockid),
  ADD INDEX (loccode),
  ADD INDEX TranDate (trandate),
  ADD INDEX DebtorNo (debtorno),
  ADD INDEX Type (type),
  ADD INDEX stockmoves (reference),
  ADD INDEX Container (container),
  CHANGE COLUMN price price decimal(20,4) NOT NULL DEFAULT '0.0000' AFTER branchcode,
  ADD COLUMN container varchar(10) NOT NULL DEFAULT '' AFTER loccode,
  CHANGE COLUMN debtorno debtorno varchar(10) NOT NULL DEFAULT '' AFTER userid,
  CHANGE COLUMN trandate trandate date NOT NULL DEFAULT '0000-00-00',
  CHANGE COLUMN standardcost standardcost double NOT NULL DEFAULT '0' AFTER discountpercent,
  CHANGE COLUMN hidemovt hidemovt tinyint(4) NOT NULL DEFAULT '0' AFTER newqoh,
  CHANGE COLUMN reference reference varchar(100) NOT NULL DEFAULT '' AFTER prd,
  CHANGE COLUMN prd prd smallint(6) NOT NULL DEFAULT '0' AFTER price,
  CHANGE COLUMN discountpercent discountpercent double NOT NULL DEFAULT '0' AFTER qty,
  CHANGE COLUMN userid userid varchar(20) NOT NULL DEFAULT '' AFTER trandate,
  CHANGE COLUMN qty qty double NOT NULL DEFAULT '1' AFTER reference,
  CHANGE COLUMN narrative narrative text NULL AFTER hidemovt,
  CHANGE COLUMN newqoh newqoh double NOT NULL DEFAULT '0' AFTER show_on_inv_crds,
  CHANGE COLUMN show_on_inv_crds show_on_inv_crds tinyint(4) NOT NULL DEFAULT '1' AFTER standardcost,
  CHANGE COLUMN branchcode branchcode varchar(10) NOT NULL DEFAULT '' AFTER debtorno";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockmovestaxes`
  DROP FOREIGN KEY stockmovestaxes_ibfk_1,
  DROP FOREIGN KEY stockmovestaxes_ibfk_3,
  DROP FOREIGN KEY stockmovestaxes_ibfk_4,
  DROP FOREIGN KEY stockmovestaxes_ibfk_2,
  DROP PRIMARY KEY,
  DROP INDEX taxauthid,
  DROP INDEX calculationorder,
  ADD PRIMARY KEY(`taxauthid`,`stkmoveno`),
  ADD INDEX taxauthid (taxauthid),
  ADD INDEX calculationorder (taxcalculationorder)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockrequest`
  DROP FOREIGN KEY stockrequest_ibfk_1,
  DROP FOREIGN KEY stockrequest_ibfk_2,
  DROP PRIMARY KEY,
  DROP INDEX loccode,
  DROP COLUMN initiator,
  ADD PRIMARY KEY(`dispatchid`),
  ADD INDEX loccode (loccode),
  ADD INDEX departmentid_2 (departmentid),
  ADD COLUMN userid varchar(20) NOT NULL DEFAULT '' AFTER dispatchid,
  CHANGE COLUMN authorised authorised tinyint(4) NOT NULL DEFAULT '0' AFTER despatchdate,
  CHANGE COLUMN loccode loccode varchar(5) NOT NULL DEFAULT '',
  CHANGE COLUMN departmentid departmentid int(11) NOT NULL DEFAULT '0' AFTER loccode,
  CHANGE COLUMN closed closed tinyint(4) NOT NULL DEFAULT '0' AFTER authorised,
  CHANGE COLUMN narrative narrative text NOT NULL AFTER closed,
  CHANGE COLUMN despatchdate despatchdate date NOT NULL DEFAULT '0000-00-00' AFTER departmentid";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockrequestitems`
  DROP FOREIGN KEY stockrequestitems_ibfk_1,
  DROP FOREIGN KEY stockrequestitems_ibfk_4,
  DROP FOREIGN KEY stockrequestitems_ibfk_3,
  DROP FOREIGN KEY stockrequestitems_ibfk_2,
  DROP PRIMARY KEY,
  DROP INDEX stockid,
  DROP INDEX dispatchid,
  ADD PRIMARY KEY(`dispatchitemsid`,`dispatchid`),
  ADD INDEX stockid (stockid),
  ADD INDEX dispatchid (dispatchid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockserialitems`
  DROP INDEX LocCode,
  DROP INDEX createdate,
  DROP INDEX StockID,
  DROP INDEX serialno,
  ADD INDEX StockID (stockid),
  ADD INDEX serialno (serialno),
  ADD INDEX CreateDate (createdate),
  ADD INDEX LocCode (loccode),
  CHANGE COLUMN createdate createdate datetime NULL DEFAULT '2017-07-25 13:00:45'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`stockserialmoves`
  DROP PRIMARY KEY,
  DROP INDEX StockMoveNo,
  DROP INDEX StockID_SN,
  DROP INDEX serialno,
  ADD PRIMARY KEY(`stkitmmoveno`),
  ADD INDEX StockID_SN (stockid),
  ADD INDEX StockMoveNo (stockmoveno),
  ADD INDEX serialno (serialno)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`suppallocs`
  DROP PRIMARY KEY,
  DROP INDEX TransID_AllocFrom,
  DROP INDEX DateAlloc,
  DROP INDEX TransID_AllocTo,
  ADD PRIMARY KEY(`id`),
  ADD INDEX TransID_AllocFrom (transid_allocfrom),
  ADD INDEX DateAlloc (datealloc),
  ADD INDEX TransID_AllocTo (transid_allocto)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`suppinvstogrn`
  DROP FOREIGN KEY suppinvstogrn_ibfk_1,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`suppinv`,`grnno`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`suppliercontacts`
  DROP INDEX Contact,
  DROP PRIMARY KEY,
  DROP INDEX SupplierID,
  ADD PRIMARY KEY(`contact`,`supplierid`),
  ADD INDEX SupplierID (supplierid),
  ADD INDEX Contact (contact)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`supplierdiscounts`
  DROP INDEX supplierno,
  DROP INDEX stockid,
  DROP INDEX effectiveto,
  DROP INDEX effectivefrom,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`id`),
  ADD INDEX supplierno (supplierno),
  ADD INDEX effectiveto (effectiveto),
  ADD INDEX stockid (stockid),
  ADD INDEX effectivefrom (effectivefrom)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`suppliers`
  DROP FOREIGN KEY suppliers_ibfk_1,
  DROP FOREIGN KEY suppliers_ibfk_2,
  DROP FOREIGN KEY suppliers_ibfk_3,
  DROP INDEX CurrCode,
  DROP INDEX taxgroupid,
  DROP INDEX PaymentTerms,
  ADD INDEX suppliers_ibfk_4 (factorcompanyid),
  ADD INDEX taxgroupid (taxgroupid),
  ADD INDEX CurrCode (currcode),
  ADD INDEX PaymentTerms (paymentterms),
  CHANGE COLUMN url url varchar(70) NOT NULL DEFAULT '',
  CHANGE COLUMN bankact bankact varchar(40) NOT NULL DEFAULT ' ',
  ADD COLUMN suppliergroupid int(11) NOT NULL DEFAULT '0' AFTER factorcompanyid,
  ADD COLUMN salespersonid varchar(4) NOT NULL DEFAULT '',
  CHANGE COLUMN taxref taxref varchar(20) NOT NULL DEFAULT '',
  CHANGE COLUMN telephone telephone varchar(25) NULL AFTER fax,
  CHANGE COLUMN port port varchar(200) NOT NULL DEFAULT '' AFTER phn,
  CHANGE COLUMN email email varchar(55) NULL AFTER port,
  CHANGE COLUMN fax fax varchar(25) NULL AFTER url,
  CHANGE COLUMN phn phn varchar(50) NOT NULL DEFAULT '' AFTER taxref,
  CHANGE COLUMN address6 address6 varchar(40) NOT NULL,
  CHANGE COLUMN defaultgl defaultgl varchar(20) NOT NULL DEFAULT '0' AFTER defaultshipper,
  CHANGE COLUMN defaultshipper defaultshipper int(11) NOT NULL DEFAULT '0' AFTER telephone";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`suppliertype`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`typeid`),
  ADD COLUMN nextsupplierno varchar(20) NOT NULL DEFAULT '' AFTER typename";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`supptrans`
  DROP FOREIGN KEY supptrans_ibfk_2,
  DROP FOREIGN KEY supptrans_ibfk_1,
  DROP INDEX DueDate,
  DROP INDEX TranDate,
  DROP PRIMARY KEY,
  DROP INDEX TypeTransNo,
  DROP INDEX SupplierNo_2,
  DROP INDEX TransNo,
  DROP INDEX SupplierNo,
  DROP INDEX SuppReference,
  DROP COLUMN chequeno,
  DROP COLUMN void,
  ADD PRIMARY KEY(`id`),
  ADD INDEX TypeTransNo (type),
  ADD INDEX SuppReference (suppreference),
  ADD INDEX SupplierNo (supplierno),
  ADD INDEX DueDate (duedate),
  ADD INDEX TransNo (transno),
  ADD INDEX TranDate (trandate),
  CHANGE COLUMN id id int(11) NOT NULL,
  CHANGE COLUMN duedate duedate date NOT NULL DEFAULT '0000-00-00'";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`supptranstaxes`
  DROP FOREIGN KEY supptranstaxes_ibfk_1,
  DROP FOREIGN KEY supptranstaxes_ibfk_2,
  DROP PRIMARY KEY,
  DROP INDEX taxauthid,
  ADD PRIMARY KEY(`supptransid`,`taxauthid`),
  ADD INDEX taxauthid (taxauthid)";
$Result = DB_query($db, $SQL);

$SQL = "INSERT INTO " . $WebERPDB . ".`systypes` (`typeid`, `typename`, `typeno`) VALUES('700', 'Auto Inventory Number', '5')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`systypes` (`typeid`, `typename`, `typeno`) VALUES('520', 'Auto Patient Number', '7')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`systypes` (`typeid`, `typename`, `typeno`) VALUES('510', 'Auto Donor Number', '1')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`systypes` (`typeid`, `typename`, `typeno`) VALUES('39', 'Sales Commision Accruals', '17')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`systypes` (`typeid`, `typename`, `typeno`) VALUES('60', 'Staff Loans', '0')";
$Result = DB_query($db, $SQL);
$SQL = "INSERT INTO " . $WebERPDB . ".`systypes` (`typeid`, `typename`, `typeno`) VALUES('61', 'Staff Loan Repayments', '0')";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`tags`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`tagref`),
  CHANGE COLUMN tagref tagref int(11) NOT NULL,
  ADD COLUMN department int(11) NOT NULL DEFAULT '0' AFTER tagref,
  CHANGE COLUMN tagdescription tagdescription varchar(50) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`taxauthorities`
  DROP FOREIGN KEY taxauthorities_ibfk_2,
  DROP FOREIGN KEY taxauthorities_ibfk_1,
  CHANGE COLUMN description description varchar(40) NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`taxauthrates`
  DROP FOREIGN KEY taxauthrates_ibfk_3,
  DROP FOREIGN KEY taxauthrates_ibfk_2,
  DROP FOREIGN KEY taxauthrates_ibfk_1";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`taxgroups`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`taxgroupid`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`taxgrouptaxes`
  DROP FOREIGN KEY taxgrouptaxes_ibfk_2,
  DROP FOREIGN KEY taxgrouptaxes_ibfk_1,
  DROP INDEX taxauthid,
  DROP PRIMARY KEY,
  DROP INDEX taxgroupid,
  ADD PRIMARY KEY(`taxauthid`,`taxgroupid`),
  ADD INDEX taxgroupid (taxgroupid),
  ADD INDEX taxauthid (taxauthid)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`taxprovinces`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`taxprovinceid`),
  ADD COLUMN freighttaxcatid tinyint(4) NOT NULL DEFAULT '0' AFTER taxprovincename";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`unitsofmeasure`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`unitid`)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`woitems`
  DROP FOREIGN KEY woitems_ibfk_1,
  DROP FOREIGN KEY woitems_ibfk_2,
  DROP INDEX stockid,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`wo`,`stockid`),
  ADD INDEX stockid (stockid),
  CHANGE COLUMN comments comments text NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`worequirements`
  DROP FOREIGN KEY worequirements_ibfk_3,
  DROP FOREIGN KEY worequirements_ibfk_2,
  DROP FOREIGN KEY worequirements_ibfk_3,
  DROP FOREIGN KEY worequirements_ibfk_1,
  DROP INDEX stockid,
  DROP INDEX worequirements_ibfk_3,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`workcentre`,`parentstockid`,`wo`,`stockid`),
  ADD INDEX worequirements_ibfk_3 (parentstockid),
  ADD INDEX stockid (stockid),
  ADD COLUMN workcentre char(5) NOT NULL DEFAULT '0' AFTER stockid,
  CHANGE COLUMN qtypu qtypu double NOT NULL DEFAULT '1',
  CHANGE COLUMN stdcost stdcost double NOT NULL DEFAULT '0' AFTER qtypu,
  CHANGE COLUMN autoissue autoissue tinyint(4) NOT NULL DEFAULT '0' AFTER stdcost";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`workcentres`
  DROP FOREIGN KEY workcentres_ibfk_1,
  DROP INDEX Description,
  DROP PRIMARY KEY,
  DROP INDEX Location,
  ADD PRIMARY KEY(`code`),
  ADD INDEX Description (description),
  ADD INDEX Location (location)";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`workorders`
  DROP FOREIGN KEY worksorders_ibfk_1,
  DROP INDEX RequiredBy,
  DROP INDEX StartDate,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`wo`),
  ADD INDEX StartDate (startdate),
  ADD INDEX RequiredBy (requiredby),
  CHANGE COLUMN closecomments closecomments text NOT NULL,
  CHANGE COLUMN remark remark text NOT NULL";
$Result = DB_query($db, $SQL);

$SQL = "ALTER TABLE " . $WebERPDB . ".`www_users`
  DROP FOREIGN KEY www_users_ibfk_1,
  DROP INDEX CustomerID,
  DROP INDEX DefaultLocation,
  DROP PRIMARY KEY,
  DROP COLUMN showdashboard,
  ADD PRIMARY KEY(`userid`),
  ADD INDEX CustomerID (customerid),
  ADD INDEX DefaultLocation (defaultlocation),
  CHANGE COLUMN modulesallowed modulesallowed varchar(40) NOT NULL DEFAULT '' AFTER pagesize,
  ADD COLUMN canentertimesheets tinyint(1) NOT NULL DEFAULT '0' AFTER cancreatetender,
  ADD COLUMN defaulttag tinyint(4) NOT NULL DEFAULT '1',
  ADD COLUMN fontsize tinyint(2) NOT NULL DEFAULT '0' AFTER department,
  ADD COLUMN changepassword tinyint(1) NOT NULL DEFAULT '1' AFTER blocked,
  ADD COLUMN restrictlocations tinyint(1) NOT NULL DEFAULT '1' AFTER defaultlocation,
  CHANGE COLUMN lastvisitdate lastvisitdate datetime NULL,
  CHANGE COLUMN showpagehelp showpagehelp tinyint(1) NOT NULL DEFAULT '1',
  CHANGE COLUMN blocked blocked tinyint(4) NOT NULL DEFAULT '0' AFTER modulesallowed,
  CHANGE COLUMN fullaccess fullaccess int(11) NOT NULL DEFAULT '1',
  CHANGE COLUMN cancreatetender cancreatetender tinyint(1) NOT NULL DEFAULT '0' AFTER fullaccess,
  CHANGE COLUMN pagesize pagesize varchar(20) NOT NULL DEFAULT 'A4' AFTER branchcode,
  CHANGE COLUMN branchcode branchcode varchar(10) NOT NULL DEFAULT '' AFTER lastvisitdate,
  CHANGE COLUMN showfieldhelp showfieldhelp tinyint(1) NOT NULL DEFAULT '1' AFTER showpagehelp";
$Result = DB_query($db, $SQL);

$SQL = "UPDATE www_users SET changepassword=0";
$Result = DB_query($db, $SQL);

$SQL = "UPDATE www_users SET modulesallowed='1,1,1,1,1,1,1,1,0,0,0,1,1,1,1,' WHERE userid='admin'";
$Result = DB_query($db, $SQL);

$SQL = "SELECT userid, modulesallowed FROM www_users";
$Result = DB_query($db, $SQL);
while ($MyRow = mysqli_fetch_array($Result)) {
	if (substr($MyRow['modulesallowed'], -1) != ',') {
		$MyRow['modulesallowed'] = $MyRow['modulesallowed'] . ',';
	}
	if (strlen($MyRow['modulesallowed']) < 30) {
		$MyRow['modulesallowed'] = $MyRow['modulesallowed'] . str_repeat('0,', (30 - strlen($MyRow['modulesallowed'])) / 2);
	}
	$SQL = "UPDATE www_users SET modulesallowed='" . $MyRow['modulesallowed'] . "' WHERE userid='" . $MyRow['userid'] . "'";
	$UpdateResult = DB_query($db, $SQL);
}

$SQL = "USE " . $WebERPDB;
$Result = DB_query($db, $SQL);
DB_query($db, 'SET FOREIGN_KEY_CHECKS=1');

$SQL = "DROP DATABASE IF EXISTS " . $KwaMojaDB;
$Result = DB_query($db, $SQL);
echo "\e[0;32mOK!\e[0m\n";

echo 'Creating a new folder in the companies directory.....';
if (file_exists($KwaMojaPath . '/companies/' . $WebERPDB)) {
	delete_directory($KwaMojaPath . '/companies/' . $WebERPDB);
}
recurse_copy($KwaMojaPath . '/companies/default', $KwaMojaPath . '/companies/' . $WebERPDB);
echo "\e[0;32mOK!\e[0m\n";

$CompanyName = $WebERPDB;
for ($i = 0; $i < count($CompanyList); $i++) {
	if ($CompanyList[$i]['database'] == $WebERPDB_orig) {
		$CompanyName = $CompanyList[$i]['company'];
	}
}
echo 'Creating a new Companies.php file in the companies directory.....';
$CompanyFileHandler = fopen($KwaMojaPath . '/companies/' . $WebERPDB . '/Companies.php', 'w');
$Contents = "<?php\n\n";
$Contents.= "\$CompanyName['" . $WebERPDB . "'] = '" . $CompanyName . "';\n";
$Contents.= "?>";

if (!fwrite($CompanyFileHandler, $Contents)) {
	fclose($CompanyFileHandler);
	echo "\n" . 'Cannot write to the Companies.php file.' . "\e[0;31mAborting....\e[0m\n";
} else {
	echo "\e[0;32mOK!\e[0m\n";
}
//close file
fclose($CompanyFileHandler);

?>