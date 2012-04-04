<?php
if (file_exists(dirname(__FILE__).'/version.txt')) {
	define('JETHRO_VERSION', file_get_contents(dirname(__FILE__).'/version.txt'));
} else {
	define('JETHRO_VERSION', 'UNKNOWN');
}
$path_sep = defined('PATH_SEPARATOR') ? PATH_SEPARATOR : ((FALSE === strpos($_ENV['OS'], 'Win')) ? ';' : ':');
ini_set('include_path', ini_get('include_path').$path_sep.JETHRO_ROOT);
ini_set('display_errors', 1);
ini_set('session.gc_maxlifetime', defined('SESSION_TIMEOUT_MINS') ? constant('SESSION_TIMEOUT_MINS')*60 : 90*60);
ini_set('session.gc_probability', 100);

// set error level such that we cope with PHP versions before and after 5.3 when E_DEPRECATED was introduced.
$error_level = defined('E_DEPRECATED') ? (E_ALL & ~constant('E_DEPRECATED')) : E_ALL;
error_reporting($error_level);

// Initial Preparation
if (session_id() == '') {
	session_start();
}
require_once JETHRO_ROOT.'/include/general.php';
strip_all_slashes();

if (php_sapi_name() != 'cli') {
	// Make sure we're at the correct URL
	$do_redirect = FALSE;
	if (REQUIRE_HTTPS && empty($_SERVER['HTTPS'])) {
		$do_redirect = TRUE;
	}
	if (strpos($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], str_replace(Array('http://', 'https://'), '', BASE_URL)) !== 0) {
		$do_redirect = TRUE;
	}
	if ($do_redirect) {
		header('Location: '.build_url(Array()));
		exit();
	}
}

// Set up the DB
if (!@include_once('MDB2.php')) {
	trigger_error('MDB2 Library not found on the server.  See the readme file for how to work around this');
	exit();
}
$GLOBALS['db'] =& MDB2::factory(DSN);
if (MDB2::isError($GLOBALS['db']) || MDB2::isError($GLOBALS['db']->getConnection())) {
	trigger_error('Could not connect to database - please check for mistakes in your DSN in conf.php, and check in MySQL that the database exists and the specified user has been granted access.', E_USER_ERROR);
	exit();
}
$GLOBALS['db']->setOption('portability', $GLOBALS['db']->getOption('portability') & !MDB2_PORTABILITY_EMPTY_TO_NULL);
$GLOBALS['db']->setFetchmode(MDB2_FETCHMODE_ASSOC);

