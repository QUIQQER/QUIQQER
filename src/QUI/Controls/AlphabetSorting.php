<?php

/**
 * This file contains \QUI\Controls\AlphabetSorting
 */

namespace QUI\Controls;

use QUI;

use function is_int;
use function is_string;

/**
 * Alphabet sorting
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class AlphabetSorting extends QUI\Control
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->addCSSFile(
            __DIR__ . '/AlphabetSorting.css'
        );

        $this->setAttribute('class', 'quiqqer-alphabetSorting grid-100 grid-parent');
    }

    /**
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Site = $this->getAttribute('Site');
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
        $params = [];

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


            if (is_string($value) || is_int($value)) {
                $params[$key] = $value;
            }
        }

        $Engine->assign([
            'active' => $active,
            'urlParams' => $params,
            'anchor' => $anchor,
            'Site' => $Site,
            'Project' => $Project
        ]);

        return $Engine->fetch(__DIR__ . '/AlphabetSorting.html');
    }
}
