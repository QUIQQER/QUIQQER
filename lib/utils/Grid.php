<?php

/**
 * This file contains the Utils_Grid
 */

/**
 * Helper for the javascript QUI.control.grid.Grid
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils
 */

class Utils_Grid extends QDOM
{
    /**
     * constructor
     *
     * @param Array $params - optional
     */
    public function __construct($params=array())
    {
        // defaults
        $this->setAttribute('max', 50);
        $this->setAttribute('page', 1);

        $this->setAttributes($params);
    }

    /**
     * Prepares DB parameters with limits
     *
     * @param Array $params
     * @return Array
     */
    public function parseDBParams($params=array())
    {
        $_params = $params;

    	if ( $this->getAttribute('max') ) {
            $max = (int)$this->getAttribute('max');
    	}

        if ( !$this->getAttribute('page') ) {
            $params['page'] = 1;
        }

    	if ( $this->getAttribute('page') )
    	{
    	    $page  = ($this->getAttribute('page')-1);
            $start = $page * $max;

            $_params['limit'] = $start.','.$max;
    	}

        return $_params;
    }

    /**
     * Prepares the result for the Grid
     *
     * @param Array $data
     * @param Integer $count
     *
     * @return Array
     */
    public function parseResult($data, $count=false)
    {
        if ( $count === false ) {
            $count = count( $data );
        }

        return array(
            'data'  => $data,
            'page'  => $this->getAttribute( 'page' ),
        	'total' => $count
        );
    }

    /**
     * Parse a result array in a grid array
     *
     * @param Array $data
     * @param Integer $page
     * @param Integer $limit
     */
    static function getResult($data, $page, $limit)
    {
        $count = count( $data );
        $end   = $page * $limit;
        $start = $end - $limit;

        return array(
            'data'  => array_slice( $data, $start, $end ),
            'page'  => $page,
        	'total' => $count
        );
    }
}

?>