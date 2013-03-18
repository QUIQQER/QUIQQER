<?php

exit;

/**
 * @deprecated
 */

// Benutzerrechte Prüfung
if (!$User->getId()) {
    exit;
}

if ($User->isAdmin() == false) {
    exit;
}

/**
 * Globale Einstellungen
 */
function ajax_config_global()
{
    $Config  = QUI::getConfig('etc/conf.ini');
    $_config = $Config->toArray();

    $_config['standard'] = Projects_Manager::getStandard()->getAttribute('name');

    $VHosts = QUI::getConfig('etc/vhosts.ini');
    $vhosts = $VHosts->toArray();

    if (isset($vhosts[404]))
    {
        $vhosts['error_site'] = $vhosts[404];
        unset($vhosts[404]);
    }

    if (isset($vhosts[301]))
    {
        $vhosts['moved'] = $vhosts[301];
        unset($vhosts[301]);
    }

    $_config['vhosts'] = $vhosts;

    return $_config;
}
$ajax->register('ajax_config_global');


/**
 * Globale Einstellungen speichern
 *
 * @param unknown_type $params
 */
function ajax_config_global_save($params)
{
    $Config = QUI::getConfig('etc/conf.ini');
    $Params = json_decode($params, true);

    if (isset($Params['db']) &&
        isset($Params['db']['password']) &&
        empty($Params['db']['password']))
    {
        // DB Passwort nicht überschreiben wenn es leer ist
        unset($Params['db']['password']);
    }

    if (isset($Params['mail']) &&
        isset($Params['mail']['SMTPPass']) &&
        empty($Params['mail']['SMTPPass']))
    {
        // SMTP Passwort nicht überschreiben wenn es leer ist
        unset($Params['mail']['SMTPPass']);
    }

    if (isset($Params['globals']))
    // Pfade können nicht überschrieben werden, nur der Host kann überschrieben werden
    {
        if (isset($Params['globals']['bin_dir'])) {
            unset($Params['globals']['bin_dir']);
        }

        if (isset($Params['globals']['lib_dir'])) {
            unset($Params['globals']['lib_dir']);
        }

        if (isset($Params['globals']['opt_dir'])) {
            unset($Params['globals']['opt_dir']);
        }

        if (isset($Params['globals']['sys_dir'])) {
            unset($Params['globals']['sys_dir']);
        }

        if (isset($Params['globals']['usr_dir'])) {
            unset($Params['globals']['usr_dir']);
        }

        if (isset($Params['globals']['var_dir'])) {
            unset($Params['globals']['var_dir']);
        }

        if (isset($Params['globals']['url_dir'])) {
            unset($Params['globals']['url_dir']);
        }

        if (isset($Params['globals']['cms_dir'])) {
            unset($Params['globals']['cms_dir']);
        }
    }


    if (isset($Params['standard']))
    // Standard Projekt setzen
    {
        $projects      = Projects_Manager::getProjects();
        $ProjectConfig = Projects_Manager::getConfig();

        foreach ($projects as $project => $values)
        {
            if ($Params['standard'] == $project)
            {
                $ProjectConfig->set($project, 'standard', 1);
                continue;
            }

            $ProjectConfig->set($project, 'standard', 0);
        }

        $ProjectConfig->save();
        unset($Params['standard']);
    }

    if (isset($Params['vhosts']))
    // VHosts Fehlerseite setzen
    {
        if (file_exists(CMS_DIR .'etc/vhosts.ini')) {
            unlink(CMS_DIR .'etc/vhosts.ini');
        }

        // vhosts anlegen
        file_put_contents(CMS_DIR .'etc/vhosts.ini', '');

        $VHosts = QUI::getConfig('etc/vhosts.ini');

        // Fehlerseite
        if (isset($Params['vhosts']['error_site']))
        {
            $VHosts->setValue(404, 'project', $Params['vhosts']['error_site']['project']);
            $VHosts->setValue(404, 'lang', $Params['vhosts']['error_site']['lang']);
            $VHosts->setValue(404, 'id', $Params['vhosts']['error_site']['id']);
        }

        unset($Params['vhosts']['error_site']);

        if (isset($Params['vhosts']['moved']))
        {
            $moved = $Params['vhosts']['moved'];

            foreach($moved as $site => $entry) {
                $VHosts->setValue('301', $site, $entry);
            }

            unset($Params['vhosts']['moved']);
        }

        foreach ($Params['vhosts'] as $host => $values)
        {
            $VHosts->setValue($host, 'project', $values['project']);
            $VHosts->setValue($host, 'lang', $values['lang']);
            $VHosts->setValue($host, 'template', $values['template']);
            $VHosts->setValue($host, 'error', $values['error']);
        }

        $VHosts->save();
        unset($Params['vhosts']);
    }

    foreach ($Params as $section => $entry)
    {
        foreach ($entry as $key => $value) {
            $Config->set($section, $key, $value);
        }
    }

    $Config->save();

    System_Cache_Manager::clear('QUI::config'); // Cache auch leeren

    return true;
}
$ajax->register('ajax_config_global_save', array('params'));

/**
 * Projekt Einstellungen
 *
 * @return array
 */
function ajax_config_projects($project)
{
    $Config  = QUI::getConfig('etc/projects.ini');
    $conf    = $Config->toArray();

    if (isset($conf[$project]))
    {
        $templateFolder = USR_DIR .'/lib/';
        $folders        = scandir($templateFolder);
        $templates      = array();

        foreach ($folders as $file)
        {
            if ($file == "." || $file == "..") {
                continue;
            }

            if (!is_dir($templateFolder.$file)){
                continue;
            }

            $templates[] = $file;
        }

        $conf[$project]['templates'] = $templates;

        return $conf[$project];
    }

    return array();
}
$ajax->register('ajax_config_projects', array('project'));

/**
 * Speichert die Konfiguration der Projekte
 *
 * @param String $project
 * @param String | JSON $params
 * @return Bool
 */
function ajax_config_projects_save($project, $params)
{
    $Config = QUI::getConfig('etc/projects.ini');
    $params = json_decode($params, true);

    foreach ($params as $key => $value)
    {
        switch ($key)
        {
            case 'default_lang':
            case 'langs':
            case 'admin_mail':
            case 'template':
            case 'sitemapDisplayTyp':
            case 'image_text':
            case 'keywords':
            case 'description':
            case 'robots':
            case 'author':
            case 'publisher':
            case 'copyright':
            case 'standard':
            case 'sheets':
            case 'archive':
            case 'rights':

            case 'watermark_image':
            case 'watermark_position':
            case 'watermark_percent':

                $Config->set($project, $key, $value);
            break;

            default:
                // nothing
        }
    }

    $Config->save();
    return true;
}
$ajax->register('ajax_config_projects_save', array('project', 'params'));

?>