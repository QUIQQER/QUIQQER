<?php

/**
 * This file contains \QUI\Controls\Sheet
 */

namespace QUI\Controls;

use QUI;

/**
 * Sheetlist
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Sheets extends QUI\Control
{
    /**
     * constructor
     *
     * @param Array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes(array(
            'showLimit' => false,
            'limits'    => '[10,20,50]',
            'limit'     => 10,
            'order'     => false,
            'sheet'     => 1
        ));

        parent::__construct($attributes);

        $this->addCSSFile(
            dirname(__FILE__).'/Sheets.css'
        );

        $this->setAttribute('class', 'quiqqer-sheets grid-100 grid-parent');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Site = $this->getAttribute('Site');
        $Project = $Site->getProject();

        $count = $this->getAttribute('sheets');
        $showmax = $this->getAttribute('showmax');
        $limit = $this->getAttribute('limit');
        $limits = $this->getAttribute('limits');

        if ($this->getAttribute('showLimit') === false) {
            $limit = false;
        }

        if ($limits && is_string($limits)) {
            $limits = json_decode($limits, true);

            if (!is_array($limits)) {
                $limits = false;
            }
        }

        $active = $this->getAttribute('sheet');
        $anchor = '';

        if ($this->getAttribute('anchor')) {
            $anchor = $this->getAttribute('anchor');
        }

        if ($showmax >= $count) {
            $showmax = false;
        }

        if (!$showmax) {
            $showmax = $count * 2;
        }

        $gap = floor($showmax / 2);

        $start = $active - $gap;
        $end = $active + $gap;

        if ($start <= 0) {
            $start = 1;
        }

        if ($end >= $count) {
            $end = $count;
        }

        $attributes = $this->getAttributes();
        $params = array();

        foreach ($attributes as $key => $value) {

            if ($key == 'class') {
                continue;
            }

            if ($key == 'sheets') {
                continue;
            }

            if ($key == 'showmax') {
                continue;
            }

            if ($key == 'limit') {
                continue;
            }

            if ($key == 'limits') {
                continue;
            }

            if ($key == 'anchor') {
                continue;
            }


            if (is_string($value) || is_int($value)) {
                $params[$key] = $value;
            }

            if (is_array($value) && isset($value[0]) && !is_array($value[0])) {
                $params[$key] = implode(
                    QUI\Rewrite::URL_SPACE_CHARACTER,
                    $value
                );
            }
        }

        if (!$count || $count == 1) {
            return '';
        }

        $Engine->assign(array(
            'this'      => $this,
            'count'     => $count,
            'start'     => $start,
            'end'       => $end,
            'active'    => $active,
            'urlParams' => $params,
            'anchor'    => $anchor,
            'limit'     => $limit,
            'limits'    => $limits,
            'Site'      => $Site,
            'Project'   => $Project
        ));

        return $Engine->fetch(dirname(__FILE__).'/Sheets.html');
    }

    /**
     * Load the GET request variables into the sheet
     */
    public function loadFromRequest()
    {
        $limit = $this->getAttribute('limit');
        $order = $this->getAttribute('order');
        $sheet = $this->getAttribute('sheet');

        if (isset($_REQUEST['limit']) && is_numeric($_REQUEST['limit'])) {
            $limit = (int)$_REQUEST['limit'];
        }

        if (isset($_REQUEST['order'])) {
            $order = $_REQUEST['order'];
        }

        if (isset($_REQUEST['sheet'])) {
            $sheet = $_REQUEST['sheet'];
        }

        $this->setAttribute('limit', $limit);
        $this->setAttribute('order', $order);
        $this->setAttribute('sheet', $sheet);
    }

    /**
     * Return SQL params
     *
     * @example $this->getSQLParams() : array(
     *     'limit' => '0,20',
     *     'order' => 'field'
     * )
     *
     * @return array
     */
    public function getSQLParams()
    {
        $limit = false;

        if ($this->getAttribute('limit')) {
            $limit = $this->getStart().','.$this->getAttribute('limit');
        }

        return array(
            'limit' => $limit,
            'order' => $this->getAttribute('order')
        );
    }

    /**
     * Return the start
     *
     * @return integer
     */
    public function getStart()
    {
        $limit = $this->getAttribute('limit');
        $sheet = $this->getAttribute('sheet');

        return ($sheet - 1) * $limit;
    }
}
