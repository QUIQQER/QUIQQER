<?php

/**
 * This file contains the \QUI\Utils\Grid
 */

namespace QUI\Utils;

use QUI;

/**
 * Helper for the javascript controls/grid/Grid
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Grid extends QUI\QDOM
{
    /**
     * constructor
     *
     * @param array $params - optional
     */
    public function __construct($params = array())
    {
        // defaults
        $this->setAttribute('max', 50);
        $this->setAttribute('page', 1);

        $this->setAttributes($params);
    }

    /**
     * Prepares DB parameters with limits
     *
     * @param array $params
     *
     * @return array
     */
    public function parseDBParams($params = array())
    {
        $_params = array();

        if (isset($params['perPage'])) {
            $this->setAttribute('max', $params['perPage']);
        }

        if (isset($params['page'])) {
            $this->setAttribute('page', $params['page']);
        }

        if ($this->getAttribute('page')) {
            $page  = ($this->getAttribute('page') - 1);
            $start = $page * $this->getAttribute('max');

            $_params['limit'] = $start . ',' . $this->getAttribute('max');
        }

        return $_params;
    }

    /**
     * Prepares the result for the Grid
     *
     * @param array $data
     * @param integer|boolean $count
     *
     * @return array
     */
    public function parseResult($data, $count = false)
    {
        if ($count === false) {
            $count = count($data);
        }

        return array(
            'data'  => $data,
            'page'  => $this->getAttribute('page'),
            'total' => $count
        );
    }

    /**
     * Parse a result array in a grid array
     *
     * @param array $data
     * @param integer $page
     * @param integer $limit
     *
     * @return array
     */
    public static function getResult($data, $page, $limit)
    {
        $count = count($data);
        $end   = $page * $limit;
        $start = $end - $limit;

        return array(
            'data'  => array_slice($data, $start, $limit),
            'page'  => $page,
            'total' => $count
        );
    }
}
