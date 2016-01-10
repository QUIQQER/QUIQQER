<?php

/**
 * This file contains \QUI\Controls\LangSwitch
 */

namespace QUI\Controls;

use QUI;

/**
 * Class LangSwitch
 *
 * @package quiqqer/quiqqer
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class LangSwitch extends QUI\Control
{
    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        // defaults values
        $this->setAttributes(array(
            'Site' => false,
            'showFlags' => true,
            'showText' => true,
            'DropDown' => false
        ));

        parent::__construct($attributes);

        $this->addCSSFile(
            dirname(__FILE__) . '/LangSwitch.css'
        );

        $this->setAttribute('class', 'quiqqer-langSwitch grid-100 grid-parent');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Site   = $this->getSite();

        if (!$Site) {
            return '';
        }

        $Project = $Site->getProject();

        if (count($Project->getAttribute('langs')) < 2) {
            QUI\System\Log::addNotice(
                'The Project "' . $Project->getName() . '" has only one Language.' .
                'The Control (\QUI\Controls\LangSwitch) makes here no sense.'
            );

            return '';
        }

        $Engine->assign(array(
            'Site' => $Site,
            'Project' => $Project,
            'langs' => $Project->getAttribute('langs'),
            'this' => $this
        ));

        return $Engine->fetch(dirname(__FILE__) . '/LangSwitch.html');
    }

    /**
     * Return the Project
     *
     * @return QUI\Projects\Site
     */
    protected function getSite()
    {
        if ($this->getAttribute('Site')) {
            return $this->getAttribute('Site');
        }

        return \QUI::getRewrite()->getSite();
    }
}
