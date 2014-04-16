<?php

/**
 * PHP Ajax Schnittstelle
 */

require_once 'header.php';

header( "Content-Type: text/plain" );

// language
if (isset($_REQUEST['lang']) && strlen($_REQUEST['lang']) === 2) {
    QUI::getLocale()->setCurrent( $_REQUEST['lang'] );
}

$User = \QUI::getUserBySession();

// Falls Benutzer eingeloggt ist, dann seine Sprache nehmen
if ( $User->getId() && $User->getLang() ) {
    \QUI::getLocale()->setCurrent( $User->getLang() );
}


/**
 * @var \QUI\Utils\Request\Ajax $ajax
 */

$_rf_files = array();

if ( isset( $_REQUEST['_rf'] ) ) {
    $_rf_files = json_decode( $_REQUEST['_rf'], true );
}


// ajax package loader
if ( isset( $_REQUEST['package'] ) )
{
    $package = $_REQUEST['package'];
    $dir     = CMS_DIR .'packages/';

    foreach ( $_rf_files as $key => $file )
    {
        $firstpart = 'package_'. str_replace( '/', '_', $package );
        $ending    = str_replace( $firstpart, '', $file );

        $_rf_file = $dir . $package . str_replace( '_', '/', $ending ) .'.php';
        $_rf_file = \QUI\Utils\Security\Orthos::clearPath( $_rf_file );

        if ( file_exists( $_rf_file ) ) {
            require_once $_rf_file;
        }
    }
}

// admin ajax
foreach ( $_rf_files as $key => $file )
{
    $_rf_file = CMS_DIR .'admin/'. str_replace( '_', '/', $file ) .'.php';

    if ( file_exists( $_rf_file ) ) {
        require_once $_rf_file;
    }
}


/**
 * Ajax Ausgabe
 */
echo \QUI::$Ajax->call();

exit;
















function ajax_send_support_mail($title, $text, $browser, $url, $mail)
{
    $_mail = new QUI_Mail(array(
        'MAILFromText' => 'Support Mailer'
    ));
    $mail_smarty = \QUI\Template::getEngine();

    $mail_smarty->assign(array(
        'url'     => $url,
        'browser' => $browser,
        'title'   => $title,
        'text'    => $text,
        'mail'	  => $mail
    ));

    $send = array(
        'MailTo'  => 'support@pcsg.de',
        'Subject' => '*** Support Anfrage *** '.$title,
        'IsHTML'  => false
    );

    $template = SYS_DIR .'/template/support_mail_message.html';

    if (!file_exists($template))
    {
        return 'false';
    }

    $send['Body'] = $mail_smarty->fetch( $template );

    try
    {
        $_mail->send($send);
        return 'true';

    } catch (Exception $e)
    {
        return $e->getMessage();
    }
}
QUI::$Ajax->register('ajax_send_support_mail', array('title', 'text', 'browser', 'url', 'mail'));

/**
 * Enter description here...
 *
 */
function ajax_get_robot_txt()
{
    $Users = \QUI::getUsers();
    $User  = $Users->getUserBySession();

    if (!$User->isSU()) {
        throw new \QUI\Exception('Nur SuperUser dürfen die robot.txt bearbeiten');
    }

    $f_robot = CMS_DIR .'robots.txt';

    if (!file_exists($f_robot)) {
        return '';
    }

    return file_get_contents($f_robot);
}
QUI::$Ajax->register('ajax_get_robot_txt');

/**
 * Enter description here...
 *
 * @param unknown_type $text
 * @return unknown
 */
function ajax_set_robot_txt($text)
{
    $Users = \QUI::getUsers();
    $User  = $Users->getUserBySession();

    if (!$User->isSU()) {
        throw new \QUI\Exception('Nur SuperUser dürfen die robot.txt bearbeiten');
    }

    $f_robot = CMS_DIR .'robots.txt';

    if (file_exists($f_robot)) {
        unlink($f_robot);
    }

    return file_put_contents($f_robot, $text);
}
QUI::$Ajax->register('ajax_set_robot_txt', array('text'));

/**
 * Gibt den Status der Wartungsarbeiten zurück
 */
function ajax_get_maintenance_status()
{
    return \QUI::conf('globals','maintenance');
}
QUI::$Ajax->register('ajax_get_maintenance_status');

/**
 * Wartungsarbeiten setzen
 *
 * @param unknown_type $status
 */
function ajax_set_maintenance_status($status)
{
    $Users = \QUI::getUsers();
    $User  = $Users->getUserBySession();

    if (!$User->getId()) {
        return;
    }

    if ($User->isAdmin() == false) {
        return;
    }

    $Config = \QUI::getConfig(CMS_DIR .'etc/conf.ini');
    $Config->setValue('globals','maintenance', (bool)$status);

    $Config->save();
}
QUI::$Ajax->register('ajax_set_maintenance_status', array('status'));

/**
 * Säubert eine URL
 *
 * @param unknown_type $url
 * @return unknown
 */
function ajax_url_clean($project, $url)
{
    $Project = \QUI::getProject($project);

    return \QUI\Projects\Site\Edit::clearUrl($url, $Project);
}
QUI::$Ajax->register('ajax_url_clean', array('project', 'url'));

