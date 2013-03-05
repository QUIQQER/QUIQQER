<?php

/**
 * PHP Ajax Schnittstelle
 */

require_once "header.php";
require_once LIB_DIR .'Ajax.php';

if (!isset($_REQUEST['_url'])) {
	$_REQUEST['_url'] = '';
};

/**
 * Sprache
 */
if (isset($_REQUEST['lang']) && strlen($_REQUEST['lang']) === 2) {
    QUI::getLocale()->setCurrent( $_REQUEST['lang'] );
}

$User = QUI::getUserBySession();

// Falls Benutzer eingeloggt ist, dann seine Sprache nehmen
if ($User->getId() && $User->getLang()) {
    QUI::getLocale()->setCurrent( $User->getLang() );
}



/**
 * @var $ajax Ajax
 */
$ajax = new PT_Ajax(array(
	'db_errors' => QUI::conf('error','mysql_ajax_errors_frontend')
));


/**
 * Hello World Ajax Test
 * @return String
 */
function ajax_hello_world()
{
	return 'HELLO';
}
$ajax->register('ajax_hello_world');

/**
 * Hellow World mit Var
 *
 * @param String $myvar
 * @return String
 */
function ajax_hello_world_var($myvar)
{
	return 'HELLO: '. $myvar;
}
$ajax->register('ajax_hello_world_var', array('myvar'));

/**
 * Enter description here...
 *
 * @return Array
 */
function ajax_hello_array()
{
	return array(1, 2, 3, 4, 5);
}
$ajax->register('ajax_hello_array');

/**
 * Gibt die URL in Beziehung zu https
 *
 * @return String
 */
function ajax_get_https_host($project, $lang)
{
    $host = QUI::conf('globals', 'httpshost');

    if (empty($host)) {
        $host = QUI::conf('globals', 'host');
    }

    $Standard = Projects_Manager::getStandard();
    $Project  = Projects_Manager::getProject($project, $lang);

    if ($Standard->getAttribute('name') != $Project->getAttribute('name')) {
        $host = $host .'/'. Rewrite::URL_PROJECT_CHARACTER . $Project->getAttribute('name');
    }

    if ($Standard->getAttribute('lang') != $Project->getAttribute('lang')) {
        $host = $host .'/en/';
    }

    return $host;
}
$ajax->register('ajax_get_https_host', array('project', 'lang'));


// Falls ein Project verwendet wird, Project Ajax File einbinden
if (isset($_REQUEST['project']))
{
	$Project = Projects_Manager::getProject(
	    Utils_Security_Orthos::clear($_REQUEST['project'])
	);

	if (file_exists(USR_DIR .'lib/'. $Project->getAttribute('template') .'/ajax.php'))
	{
		try
		{
			require_once USR_DIR .'lib/'. $Project->getAttribute('template') .'/ajax.php';
		} catch (QException $e)
		{
			echo $ajax->writeException($e);
			exit;
		}
	}
}

// Ajax der Plugins einbinden
if (file_exists(CMS_DIR .'etc/plugins.ini'))
{
    $Plugins = QUI::getPlugins();
	$plugins = $Plugins->get();

	foreach ($plugins as $Plugin)
	{
	    if (!file_exists(OPT_DIR . $Plugin->getAttribute('name') .'/ajax.php')) {
			continue;
		}

		try
		{
			require_once OPT_DIR . $Plugin->getAttribute('name') .'/ajax.php';
		} catch (QException $e)
		{
			echo $ajax->writeException($e);
			exit;
		}
	}
}

/**
 * Ajax Ausgabe
 */
echo $ajax->call();

require_once SYS_DIR .'footer.php';
exit;

?>