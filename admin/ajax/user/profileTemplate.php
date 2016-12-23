<?php

use QUI\Utils\Text\XML;
use QUI\Utils\DOM;

/**
 * User profile template
 *
 * @return String
 */
QUI::$Ajax->registerFunction('ajax_user_profileTemplate', function () {
    $Engine   = QUI::getTemplateManager()->getEngine(true);
    $packages = QUI::getPackageManager()->getInstalled();
    $extend   = '';

    foreach ($packages as $package) {
        $name    = $package['name'];
        $userXml = OPT_DIR . $name . '/user.xml';

        if (!file_exists($userXml)) {
            continue;
        }

        $Document = XML::getDomFromXml($userXml);
        $Path     = new \DOMXPath($Document);

        $tabs = $Path->query("//user/profile/tab");

        /* @var $Tab \DOMElement */
        foreach ($tabs as $Tab) {
            $extend .= DOM::parseCategorieToHTML($Tab);
        }
    }

    $Engine->assign(array(
        'QUI'    => new QUI(),
        'extend' => $extend
    ));

    return $Engine->fetch(SYS_DIR . 'template/users/profile.html');
});
