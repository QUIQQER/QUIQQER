<?php
/**
 * Plugin Manager
 */

// Benutzerrechte Prüfung
if (!$User->getId()) {
	exit;
}

if ($User->isAdmin() == false) {
	exit;
}

/**
 * Plugin Manager Template hohlen
 *
 * @return String
 */
function ajax_plugins_getmanager()
{
	$file = SYS_DIR .'template/plugins_manager.html';

	if (!file_exists($file))
	{
		System_Log::write('Cannot find File '. $file, 'error');
		return '';
	}

	$Smarty = QUI_Template::getEngine(true);

	// Plugins einlesen
	$OPT = Utils_System_File::readDir(OPT_DIR);

	$Plugins = array();
	$_tmp    = array();

	foreach ($OPT as $key => $plg)
	{
		if (!is_dir(OPT_DIR . $plg)) {
			continue;
		}

		if (!file_exists(OPT_DIR . $plg .'/base.ini')) {
			continue;
		}

		$Plg_Config = QUI::getConfig(OPT_DIR . $plg .'/base.ini');
		$config     = $Plg_Config->toArray();

		$config['plg'] = $plg;

		// array index
		$name = $config['plg'];

		if (isset($config['name']) &&
		    !empty($config['name']))
        {
            $name = $config['name'];
		}

		$_tmp[ $name ] = $config;
	}

	ksort($_tmp);

	// sortierung
	foreach ($_tmp as $entry) {
	    $Plugins[ $entry['plg'] ] = $entry;
	}


	$plg_ini = CMS_DIR .'etc/plugins.ini';
	$Active  = array();

	if (file_exists($plg_ini))
	{
		$Active = QUI::getConfig($plg_ini);
		$Active = $Active->toArray();
	}

	$JSON_Active = '';

	foreach ($Active as $key => $value) {
		$JSON_Active .= ','. $key;
	}

    // Plugin Download liste bekommen
	$Update = new \QUI\Update();

	try
	{
        $_plugin_list = $Update->pluginGetAvailablePlugins();
	} catch (QException $e)
	{
        $_plugin_list = array();
	}

	$Smarty->assign(array(
		'Plugins'     => $Plugins,
		'NewPlugins'  => $_plugin_list,
		'Active'      => $Active,
		'JSON_Active' => $JSON_Active
	));

	return $Smarty->fetch($file);
}
$ajax->register('ajax_plugins_getmanager');


/**
 * Plugin Remove Manager Template hohlen
 *
 * @return String
 */
function ajax_plugins_get_remove_manager()
{
	$file = SYS_DIR .'/template/plugins_remove_manager.html';

	if (!file_exists($file))
	{
		System_Log::write('Cannot find File '. $file, 'error');
		return '';
	}

	$Smarty = QUI_Template::getEngine(true);

	// Plugins einlesen
	$OPT = Utils_System_File::readDir(OPT_DIR);
	sort($OPT);

	$Plugins = array();

	$plg_ini = CMS_DIR .'etc/plugins.ini';
	$ActivePlugins  = array();

	if (file_exists($plg_ini))
	{
		$Active = QUI::getConfig($plg_ini);
		$Active = $Active->toArray();
	}

	foreach ($Active as $key => $value) {
		$ActivePlugins[] =  $key;
	}

	foreach ($OPT as $key => $plg)
	{
		if (file_exists(OPT_DIR . $plg .'/base.ini'))
		{
			$Plg_Config      = QUI::getConfig(OPT_DIR . $plg .'/base.ini');
			// nur inaktive plugins können gelöscht werden
			if (!in_array($plg, $ActivePlugins)) {
			    $Plugins[ $plg ] = $Plg_Config->toArray();
			}
		}
	}

	$Smarty->assign(array(
		'Plugins' => $Plugins,
	    'count'   => count($Plugins)
	));

	return $Smarty->fetch($file);
}
$ajax->register('ajax_plugins_get_remove_manager');


/**
 * Plugin Update Manager Template hohlen
 *
 * @return String
 */
function ajax_plugins_get_update_manager()
{
	$file = SYS_DIR .'/template/plugins_update_manager.html';

	if (!file_exists($file))
	{
		System_Log::write('Cannot find File '. $file, 'error');
		return '';
	}

	$Smarty = QUI_Template::getEngine(true);

    // Plugin Download liste bekommen
	$Update = new \QUI\Update();

	try
	{
        $_plugin_list = $Update->pluginGetAvailableUpdates();
	} catch (QException $e)
	{
         $_plugin_list = array();
	}

	$Smarty->assign(array(
		'Plugins'  => $_plugin_list,
	    'count'    => count($_plugin_list)
	));

	return $Smarty->fetch($file);
}
$ajax->register('ajax_plugins_get_update_manager');

/**
 * Insttalliert ein Plugins
 *
 * @param String $plugin
 * @param String $server
 */
function ajax_plugins_install($plugin, $server)
{
	$Update = new \QUI\Update();

	$data = array(
	   'name'         => Utils_Security_Orthos::clear($plugin),
	   'updateserver' => Utils_Security_Orthos::clear($server),
	);

	$Update->pluginInstall($data);

	return 'Das Plugin wurde erfolgreich installiert und kann jetzt aktiviert werden.';
}
$ajax->register('ajax_plugins_install', array('plugin', 'server'));

/**
 * führt das setup des übergebenen plugins aus
 *
 * @param String $plugin
 */
function ajax_plugins_setup($plugin)
{
	$Plugins = QUI::getPlugins();
	$Plugin  = $Plugins->get($plugin);

	if (method_exists($Plugin, 'events')) {
		$Plugin->events();
	}

	$Plugin->install();

	return true;
}
$ajax->register('ajax_plugins_setup', array('plugin'));

/**
 * Einstellungs Fenster eines Plugins
 *
 * @param String $plugin
 * @return String
 */
function ajax_plugins_settings_get_window($plugin)
{
    $Plugins = QUI::getPlugins();
    $Plugin  = $Plugins->get($plugin);
    $Window  = $Plugin->getSettingsWindow();

    if (!$Window) {
        return '';
    }

    return $Window->jsObject();
}
$ajax->register('ajax_plugins_settings_get_window', array('plugin'));

/**
 * Einstellungs Fenster eines Plugins
 *
 * @param String $plugin
 * @return String
 */
function ajax_plugins_settings_get_system_window($plugin)
{
    $SystemPlugin = SystemPlugins::get($plugin);
    $Window       = $SystemPlugin->getSettingsWindow();

    if (!$Window) {
        return '';
    }

    return $Window->jsObject();
}
$ajax->register('ajax_plugins_settings_get_system_window', array('plugin'));

/**
 * Gibt eine Kategorie / Button Inhalt zurück
 *
 * @param unknown_type $plugin
 * @param unknown_type $category
 *
 * @return String
 */
function ajax_plugins_settings_get_category($plugin, $category, $params)
{
    $params = json_decode($params, true);

    if (isset($params['system']) && PT_Bool::JSBool($params['system']))
    {
        $Plugin = SystemPlugins::get($plugin);

    } else
    {
        $Plugins = QUI::getPlugins();
        $Plugin  = $Plugins->get($plugin);
    }

    return Utils_Dom::parseCategorieToHTML(
        $Plugin->getSettingsCategory($category)
    );
}
$ajax->register('ajax_plugins_settings_get_category', array('plugin', 'category', 'params'));

/**
 * Speichert die Einstellungen für das Plugin
 *
 * @param String $plugin
 * @param String $config
 */
function ajax_plugins_settings_save($plugin, $config, $system)
{
    $config = json_decode($config, true);
    $system = PT_Bool::JSBool($system);

    if (!$config && !is_array($config)) {
        return;
    }

    if ($system)
    {
        $Plugin = SystemPlugins::get($plugin);
    } else
    {
        $Plugins = QUI::getPlugins();
        $Plugin  = $Plugins->get($plugin);
    }

    foreach ($config as $section => $entry)
    {
        if (!is_array($entry)) {
            continue;
        }

        foreach ($entry as $key => $value) {
            $Plugin->setSettings($section, $key, $value);
        }
    }

    $Plugin->saveSettings();
}
$ajax->register('ajax_plugins_settings_save', array('plugin', 'config', 'system'));


?>