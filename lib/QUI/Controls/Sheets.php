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
     * GET Params
     *
     * @var array
     */
    protected $_getParams = array();

    /**
     * URL Params
     *
     * @var array
     */
    protected $_urlParams = array();

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

        // get params
        $limit = $this->getAttribute('limit');
        $order = $this->getAttribute('order');
        $sheet = $this->getAttribute('sheet');

        $this->_getParams['sheet'] = $sheet;
        $this->_getParams['order'] = $order;
        $this->_getParams['limit'] = $limit;


        if (!$count || $count == 1) {
            return '';
        }

        $Engine->assign(array(
            'this'       => $this,
            'count'      => $count,
            'start'      => $start,
            'end'        => $end,
            'active'     => $active,
            'pathParams' => $this->_urlParams,
            'getParams'  => $this->_getParams,
            'anchor'     => $anchor,
            'limit'      => $limit,
            'limits'     => $limits,
            'Site'       => $Site,
            'Project'    => $Project
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

        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $limit = (int)$_GET['limit'];
        }

        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        if (isset($_GET['sheet'])) {
            $sheet = $_GET['sheet'];
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

    /**
     * Set GET parameter to the links
     *
     * @param $name
     * @param $value
     */
    public function setGetParams($name, $value)
    {
        $name = QUI\Utils\Security\Orthos::clear($name);
        $value = QUI\Utils\Security\Orthos::clear($value);

        if (empty($value)) {

            if ($this->_getParams[$name]) {
                unset($this->_getParams[$name]);
            }

            return;
        }

        $this->_getParams[$name] = $value;
    }

    /**
     * Set URL parameter to the links
     *
     * @param $name
     * @param $value
     */
    public function setUrlParams($name, $value)
    {
        $name = QUI\Utils\Security\Orthos::clear($name);
        $value = QUI\Utils\Security\Orthos::clear($value);

        if (empty($value)) {

            if ($this->_urlParams[$name]) {
                unset($this->_urlParams[$name]);
            }

            return;
        }

        $this->_urlParams[$name] = $value;
    }
}
