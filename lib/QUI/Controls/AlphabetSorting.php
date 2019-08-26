<?php

/**
 * This file contains \QUI\Controls\AlphabetSorting
 */

namespace QUI\Controls;

use QUI;

/**
 * Alphabet sorting
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class AlphabetSorting extends QUI\Control
{
    /**
     * constructor
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);

        $this->addCSSFile(
            \dirname(__FILE__).'/AlphabetSorting.css'
        );

        $this->setAttribute('class', 'quiqqer-alphabetSorting grid-100 grid-parent');
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine  = QUI::getTemplateManager()->getEngine();
        $Site    = $this->getAttribute('Site');
        $Project = $Site->getProject();

        $active = 'abc';
        $anchor = '';

        if ($this->getAttribute('anchor')) {
            $anchor = $this->getAttribute('anchor');
        }

        if (isset($_REQUEST['list'])) {
            switch ($_REQUEST['list']) {
                case 'abc':
                case 'def':
                case 'ghi':
                case 'jkl':
                case 'mno':
                case 'pqr':
                case 'stu':
                case 'vz':
                    $active = $_REQUEST['list'];
                    break;
            }
        }

        $attributes = $this->getAttributes();
        $params     = [];

        foreach ($attributes as $key => $value) {
            if ($key == 'class') {
                continue;
            }

            if ($key == 'letters') {
                continue;
            }

            if ($key == 'anchor') {
                continue;
            }


            if (\is_string($value) || \is_int($value)) {
                $params[$key] = $value;
            }
        }

        $Engine->assign([
            'active'    => $active,
            'urlParams' => $params,
            'anchor'    => $anchor,
            'Site'      => $Site,
            'Project'   => $Project
        ]);

        return $Engine->fetch(\dirname(__FILE__).'/AlphabetSorting.html');
    }
}
