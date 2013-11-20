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
 * Ladet den Editor
 *
 * @param String $name
 * @return String
 */
function ajax_load_editor($name)
{
    $editor = new PT_Editor(array());

    $settings = array(
        'frame'        => 'editor',
        'name'	       => '_Editor',
        'content_name' => $name,
        'onload'       => '_Editor.setContent(_Site.getTempAttribute("'. $name .'"));',
        'config'       => $editor->getAttribute('config')
    );

    return $settings;
}
$ajax->register('ajax_load_editor', array('name'));

/**
 * Gibt die entsprechende Toolbar zurück
 *
 * @param String $name - Name des Editors
 * @return Array
 */
function ajax_get_editor_buttons($name)
{
    $Editor = new PT_Editor(array());
    $Editor->setAttribute('buttons', QUI_Wysiwyg::getButtons());

    $result = array(
        'name'    => $name,
        'buttons' => $Editor->getButtons(),
        'files'   => $Editor->getFiles()
    );

    return $result;
}
$ajax->register('ajax_get_editor_buttons', array('name'));

/**
 * Gibt die CSS Dateien des Projektes zurück welche geladen werden sollen
 *
 * @param String $project
 * @return Array
 */
function ajax_editor_conf($project)
{
    $conf = CMS_DIR .'etc/wysiwyg.ini';

    if (!file_exists($conf)) {
        return;
    }

    $Config = QUI::getConfig($conf);
    $conf   = $Config->toArray();

    // defaults
    $cssfiles = array();
    $class    = '';
    $id       = '';

    // CSS Files
    if (isset($conf['css_files']) &&
        isset($conf['css_files'][$project]))
    {
        $files = $conf['css_files'][$project];
        $files = explode(',', $files);

        foreach($files as $file) {
            $cssfiles[] = URL_DIR . $file;
        }
    }

    // Class
    if (isset($conf['editor_class']) &&
        isset($conf['editor_class'][$project]))
    {
        $class = $conf['editor_class'][$project];
    }

    // Id
    if (isset($conf['editor_id']) &&
        isset($conf['editor_id'][$project]))
    {
        $id = $conf['editor_id'][$project];
    }

    return array(
        'cssfiles' => $cssfiles,
        'class'    => $class,
        'id'       => $id
    );
}
$ajax->register('ajax_editor_conf', array('project'));

/**
 * Räumt das HTML auf
 *
 * @param unknown_type $html
 * @return unknown
 */
function ajax_editor_cleanup_html($html)
{
    $Editor = new PT_Editor(array());
    return $Editor->save($html);
}
$ajax->register('ajax_editor_cleanup_html', array('html'));

/**
 * Bereitet HTML für Editor vor
 *
 * @param String $html
 * @return String
 */
function ajax_editor_create_html($html)
{
    $Editor = new PT_Editor(array());
    return $Editor->load($html);
}
$ajax->register('ajax_editor_create_html', array('html'));


/**
 * WYSIWYG Plugins einlesen
 */
$plugin_dir = LIB_DIR .'ptools/editor/plugins/';

if (is_dir($plugin_dir))
{
    if ($plugins = \QUI\Utils\System\File::readDir($plugin_dir))
    {
        foreach ($plugins as $plugin)
        {
            if (is_file($plugin_dir.$plugin.'/ajax.php')) {
                require_once($plugin_dir.$plugin.'/ajax.php');
            }
        }
    }
}
