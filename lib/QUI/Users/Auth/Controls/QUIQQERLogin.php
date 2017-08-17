<?php

/**
 * This file contains
 */

namespace QUI\Users\Auth\Controls;

use QUI;
use QUI\Control;

/**
 * Class QUIQQERLogin
 *
 * @package QUI
 */
class QUIQQERLogin extends Control
{
    /**
     * QUIQQERLogin constructor.
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->addCSSClass('quiqqer-login-auth');
        $this->addCSSFile(dirname(__FILE__).'/QUIQQERLogin.css');
    }

    /**
     * @return string
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign(array(
            'usernameText' => QUI::getLocale()->get('quiqqer/quiqqer', 'username'),
            'passwordText' => QUI::getLocale()->get('quiqqer/quiqqer', 'password'),
        ));

        return $Engine->fetch(dirname(__FILE__).'/QUIQQERLogin.html');
    }
}
