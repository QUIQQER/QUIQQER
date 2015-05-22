<?php

/**
 * This file contains \QUI\Controls\Sheet
 */

namespace QUI\Controls;

use QUI;

/**
 * Sheetlist
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Sheets extends QUI\Control
{
    /**
     * constructor
     * @param Array $attributes
     */
    public function __construct($attributes=array())
    {
        parent::setAttributes( $attributes );

        $this->addCSSFile(
            dirname( __FILE__ ) .'/Sheets.css'
        );

        $this->setAttribute( 'class', 'quiqqer-sheets grid-100 grid-parent' );
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

        $count   = $this->getAttribute( 'sheets' );
        $showmax = $this->getAttribute( 'showmax' );

        $active = 1;
        $anchor = '';

        if ( $this->getAttribute( 'anchor' ) ) {
            $anchor = $this->getAttribute( 'anchor' );
        }

        if ( $showmax >= $count ) {
            $showmax = false;
        }

        if ( !$showmax ) {
            $showmax = $count * 2;
        }

        if ( isset( $_REQUEST['sheet'] ) && (int)$_REQUEST['sheet'] ) {
            $active = (int)$_REQUEST['sheet'];
        }

        $gap = floor( $showmax / 2 );

        $start = $active - $gap;
        $end   = $active + $gap;

        if ( $start <= 0 ) {
            $start = 1;
        }

        if ( $end >= $count ) {
            $end = $count;
        }

        $attributes = $this->getAttributes();
        $params     = array();

        foreach ( $attributes as $key => $value )
        {
            if ( $key == 'class' ) {
                continue;
            }

            if ( $key == 'sheets' ) {
                continue;
            }

            if ( $key == 'showmax' ) {
                continue;
            }

            if ( $key == 'anchor' ) {
                continue;
            }


            if ( is_string( $value ) || is_int( $value ) ) {
                $params[ $key ] = $value;
            }
        }

        $Engine->assign(array(
            'count'     => $count,
            'start'     => $start,
            'end'       => $end,
            'active'    => $active,
            'urlParams' => $params,
            'anchor'    => $anchor,

            'Site'    => $Site,
            'Project' => $Project
        ));

        return $Engine->fetch( dirname( __FILE__ ) .'/Sheets.html' );
    }
}
