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
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        // defaults values
        $this->setAttributes(array(
            'startId'   => 1, // id or site link
            'homeLink'  => false,
            'levels'    => false,
            'homeIcon'  => 'fa-home',
            'listIcon'  => 'fa-angle-right',
            'levelIcon' => 'fa-angle-double-down'
        ));

        parent::__construct($attributes);

        $this->addCSSFile(
            dirname(__FILE__) . '/Navigation.css'
        );

        $this->setAttribute('class', 'quiqqer-navigation grid-100');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine   = QUI::getTemplateManager()->getEngine();
        $Project  = $this->getProject();
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
            'Project'     => $this->getProject(),
            'Site'        => $Site,
            'homeLink'    => $homeLink = $this->getAttribute('homeLink'),
            'activeId'    => $activeId,
            'navTemplate' => dirname(__FILE__) . '/Navigation.html',
            'levels'      => $levels,
            'Rewrite'     => QUI::getRewrite(),
            'homeIcon'    => $homeIcon = $this->getAttribute('homeIcon'),
            'listIcon'    => $listIcon = $this->getAttribute('listIcon'),
            'levelIcon'   => $levelIcon = $this->getAttribute('levelIcon')
        ));

        $html = $Engine->fetch(dirname(__FILE__) . '/Navigation.html');
        $html = '<nav>' . $html . '</nav>';

        return $html;
    }
}
