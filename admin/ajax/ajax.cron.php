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

// Nur Superuser dürfen Crons anlegen
if ($User->isSU() == false) {
    exit;
}

/**
 * Cronlisting
 *
 * @return Array
 */
function ajax_cron_list()
{
    $Crons  = System_Cron_Manager::get();
    $result = array();

    foreach ($Crons as $Cron)
    {
        $params = $Cron->getAllAttributes();
        $params['params'] = json_encode($params['params']);

        $result[] = $params;
    }

    return $result;
}
$ajax->register('ajax_cron_list');

/**
 * Löscht einen Cron
 *
 * @param Integer $cid - Cron ID
 */
function ajax_cron_delete($cid)
{
    $Crons = System_Cron_Manager::get(array(
        'where' => array(
            'id' => (int)$cid
        )
    ));

    if (!isset($Crons[0])) {
        return;
    }

    $Crons[0]->delete();
}
$ajax->register('ajax_cron_delete', array('cid'));

/**
 * Cron hinzufügen
 *
 * @param String $params
 */
function ajax_cron_add($params)
{
    $params = json_decode($params, true);

    if (!isset($params['plugin'])) {
        throw new \QUI\Exception('Es wurde kein Plugin angegeben');
    }

    if (strpos($params['plugin'], 'project.') !== false)
    {
        $Object = QUI::getProject(
            str_replace('project.', '', $params['plugin'])
        );
    } else
    {
        $Plugins = QUI::getPlugins();
        $Object  = $Plugins->get($params['plugin']);
    };

    $date    = array();

    if (isset($params['min'])) {
        $date['min'] = $params['min'];
    }

    if (isset($params['hour'])) {
        $date['hour'] = $params['hour'];
    }

    if (isset($params['day'])) {
        $date['day'] = $params['day'];
    }

    if (isset($params['month'])) {
        $date['month'] = $params['month'];
    }

    System_Cron_Manager::add(
        $Object,
        $params['cronname'],
        $date
    );
}
$ajax->register('ajax_cron_add', array('params'));

/**
 * Enter description here...
 *
 * @param unknown_type $cid
 * @param unknown_type $params
 */
function ajax_cron_edit_params($cid, $params)
{
    System_Cron_Manager::edit((int)$cid, array(
        'params' => json_decode($params, true)
    ));
}
$ajax->register('ajax_cron_edit_params', array('cid', 'params'));

/**
 * Template um einen Cron hinzuzufügen
 *
 * @return String
 */
function ajax_cron_add_template()
{
    $Smarty      = QUI_Template::getEngine();
    $CronManager = new System_Cron_Manager();

    $list = $CronManager->getList();

    // sortierung
    usort($list, function($a, $b)
    {
        if ($a['desc'] == $b['desc']) {
            return 0;
        }

        return ($a['desc'] < $b['desc']) ? -1 : 1;
    });

    $Smarty->assign(array(
        'list' => $list
    ));

    return \QUI\Utils\Security\Orthos::removeLineBreaks(
        $Smarty->fetch(SYS_DIR .'template/cron/add.html'), ' '
    );
}
$ajax->register('ajax_cron_add_template');

/**
 * Cron / Dienst / Task ausführen
 *
 * @param unknown_type $cid
 */
function ajax_cron_execute($cid)
{
    $Cron = System_Cron_Manager::getCronByCid($cid);

    $Cron->setAttribute('day', '*');
    $Cron->setAttribute('month', '*');
    $Cron->setAttribute('min', '*');
    $Cron->setAttribute('hour', '*');

    try
    {
        $Cron->exec();
    } catch (\QUI\Exception $e)
    {
        System_Log::writeException($e);
    }

    System_Cron_Manager::log("Cron '". $Cron->getAttribute('cronname') ."' wurde ausgeführt.\nParameter: ". print_r($Cron->getAllAttributes(), true));
}
$ajax->register('ajax_cron_execute', array('cid'));

?>