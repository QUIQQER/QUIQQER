<?php

/**
 * This file contains \QUI\Controls\Navigation
 */

namespace QUI\Controls;

use QUI;
use QUI\Projects\Site\Utils;

/**
 * Class Navigation
 *
 * @package quiqqer/quiqqer
 * @licence For copyright and license information, please view the /README.md
 */
class Navigation extends QUI\Control
{
    /**
     * constructor
     *
     * @param Array $attributes
     */
    public function __construct($attributes = array())
    {
        // defaults values
        $this->setAttributes(array(
            'startId'  => 1, // id or site link
            'homelink' => false,
            'levels'   => false
        ));

        parent::setAttributes($attributes);

        $this->addCSSFile(
            dirname(__FILE__).'/Navigation.css'
        );

        $this->setAttribute('class', 'quiqqer-navigation grid-100 grid-parent');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Project = $this->_getProject();
        $activeId = false;

        // start
        try {
            $startId = $this->getAttribute('startId');

            if (Utils::isSiteLink($startId)) {
                $Site = Utils::getSiteByLink($startId);

            } else {
                $Site = $Project->get((int)$startId);
            }

        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());

            return '';
        }

        // active site
        $ActiveSite = QUI::getRewrite()->getSite();

        if ($ActiveSite && $ActiveSite->getProject() == $Project) {
            $activeId = $ActiveSite->getId();
        }

        // settings
        $levels = (int)$this->getAttribute('levels');

        if ($levels <= 0 || $this->getAttribute('levels') === false) {
            $levels = false;
        }

        $Engine->assign(array(
            'this'        => $this,
            'Project'     => $this->_getProject(),
            'Site'        => $Site,
            'navTemplate' => dirname(__FILE__).'/Navigation.html',
            'activeId'    => $activeId,
            'levels'      => $levels
        ));

        $html = $Engine->fetch(dirname(__FILE__).'/Navigation.html');
        $html = '<nav>'.$html.'</nav>';

        return $html;
    }
}