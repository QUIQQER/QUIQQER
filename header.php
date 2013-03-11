<?php

/**
 * Hauptheader
 */

header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: public, max-age=". 15 * 60); // 15min public caching für proxys

date_default_timezone_set('Europe/Zurich');

error_reporting(E_ALL);
ini_set('display_errors', true);

require_once 'lib/QUI.php';
QUI::load();

System_Debug::marker('header start');

ini_set('display_errors', false);
ini_set("log_errors", "on");
ini_set("error_log", VAR_DIR .'log/error'. date('-Y-m-d') .'.log');
set_error_handler("exception_error_handler");

if ( DEVELOPMENT == 1 )
{
	error_reporting(E_ALL);
} else
{
	error_reporting(0);
}

if (isset($_SERVER["REQUEST_URI"]) && strpos($_SERVER["REQUEST_URI"], 'header.php') !== false)
{
    header('Location: '. URL_DIR);
    exit;
}

define('GENERATOR', 'QUIQQER /www.pcsg.de');

define('URL_LIB_DIR', URL_DIR . str_replace(CMS_DIR, '', LIB_DIR));
define('URL_BIN_DIR', URL_DIR . str_replace(CMS_DIR, '', BIN_DIR));
define('URL_USR_DIR', URL_DIR . str_replace(CMS_DIR, '', USR_DIR));
define('URL_SYS_DIR', URL_DIR . str_replace(CMS_DIR, '', SYS_DIR));
define('URL_OPT_DIR', URL_DIR . str_replace(CMS_DIR, '', OPT_DIR));
define('URL_VAR_DIR', URL_DIR . str_replace(CMS_DIR, '', VAR_DIR));

define('HOST',         QUI::conf('globals','host'));
define('CACHE',        QUI::conf('globals','cache'));
define('SALT_LENGTH',  QUI::conf('globals','saltlength'));
define('MAIL_PROTECT', QUI::conf('globals','mailprotection'));
define('ADMIN_CACHE',  false);
define('DEBUG_MEMORY', false);

// Cacheflag setzen
System_Cache_Manager::set('pcsg_cache', 1);

try
{
    define('CHECK_CACHE', System_Cache_Manager::get('pcsg_cache'));
} catch (System_Cache_Exception $e)
{
    define('CHECK_CACHE', false);
}

$error_mail = QUI::conf('error','mail');

if (!empty($error_mail))
{
    define('ERROR_SEND', $error_mail);
} else
{
    define('ERROR_SEND', 0);
}

// Datenbankverbindung aufbauen
try
{
	QUI::getDB();
} catch (Exception $e)
{
	echo '<html>
		<body>
			<div style="
				background-color: #FFBFBF;
				border: 1px solid red; padding: 10px; margin: 10px;">
				Die Verbindung zur Datenbank hat leider nicht funktioniert.<br />
				Bitte &uuml;berpr&uuml;fen Sie Ihr Log Verzeichnis vom P.MS um weitere Informationen zu erhalten
			</div>
		</body>
		</html>';

	System_Log::writeException($e);
	exit;
}

/**
 * @var $User User
 */

// User ist Standard Nobody
$User = QUI::getUsers()->getNobody();

if (isset($_POST['username']) &&
	isset($_POST['password']) &&
	isset($_POST['login']))
{
    // Falls ein Login versucht wurde
	try
	{
		$User = QUI::getUsers()->login($_POST['username'], $_POST['password']);
	} catch (QException $e)
	{
		define('LOGIN_FAILED', $e->getMessage());
	}

} elseif (QUI::getSession()->get('id'))
{
	try
	{
		$User = QUI::getUserBySession();
	} catch (QException $e)
	{
		define('LOGIN_FAILED',  $e->getMessage());
	}
}

// Logout
if (isset($_GET['logout']))
{
	$User->logout();
	$User = QUI::getUsers()->getNobody();

	if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'logout=1') !== false)
	{
		header('Location: '. str_replace('logout=1', '', $_SERVER['REQUEST_URI']));
		exit;
	}
}

System_Debug::marker('header end');

?>