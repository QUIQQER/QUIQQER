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

// Nur Superuser dürfen Logs lesen / bearbeiten
if ($User->isSU() == false) {
    exit;
}

/**
 * Loglisting
 *
 * @return Array
 */
function ajax_plugins_logs_list()
{
    $logs = \QUI\Utils\System\File::readDir(VAR_DIR .'log');

    sort($logs);

    return array_reverse($logs);
}
$ajax->register('ajax_plugins_logs_list');

/**
 * Log Inhalt bekommen
 *
 * @param String $log
 * @return String
 */
function ajax_plugins_logs_get($log)
{
    $log     = \QUI\Utils\Security\Orthos::clearPath($log);
    $logfile = VAR_DIR .'log/'. $log;

    if (!file_exists($logfile) || filesize($logfile) > 1000000) {
        return 'Die Datei Existiert nicht oder ist zu Groß';
    }

    return file_get_contents($logfile);
}
$ajax->register('ajax_plugins_logs_get', array('log'));

/**
 * Sendet eine Log per Mail
 *
 * @param String $log
 */
function ajax_plugins_logs_send($log)
{
    $Users = \QUI::getUsers();
    $User  = $Users->getUserBySession();

    $email = $User->getAttribute('email');

    if ($email == false) {
        throw new \QUI\Exception('Bitte hinterlegen Sie eine E-Mail Adresse');
    }

    $Mail = new QUI_Mail();
    $log  = \QUI\Utils\Security\Orthos::clearPath($log);

    $logfile = VAR_DIR .'log/'. $log;

    if (!file_exists($logfile)) {
        throw new \QUI\Exception('Log Datei existiert nicht');
    }

    $Mail->send(array(
        'MailTo' 	=> $email,
         'Subject' 	=> 'Log: '. $log,
         'Body' 		=> 'Anhang ('. $log .')',
         'IsHTML' 	=> false,
         'files' 	=> array($logfile)
     ));
}
$ajax->register('ajax_plugins_logs_send', array('log'));

/**
 * Löscht eine Log
 *
 * @param String $log
 */
function ajax_plugins_logs_delete($log)
{
    $log     = \QUI\Utils\Security\Orthos::clearPath($log);
    $logfile = VAR_DIR .'log/'. $log;

    if (!file_exists($logfile)) {
        return;
    }

    unlink($logfile);
}
$ajax->register('ajax_plugins_logs_delete', array('log'));
