<?php

/**
 * Get a PHP control
 *
 * @param String $control - Control Name
 * @param String $params - JSON Array
 */
function ajax_controls_get($control, $params=false)
{
    try
    {
        $Control = new $control();
        $params  = json_decode( $params, true );

        if ( $params ) {
            $Control->setAttributes( $params );
        }

    } catch ( \QUI\Exception $Exception )
    {
        throw new \QUI\Exceptions(
            \QUI::getLocale()->get( 'quiqqer/system', 'control.not.found' ),
            404
        );
    }

    if ( !is_subclass_of( $Control, '\QUI\Control' ) )
    {
        throw new \QUI\Exceptions(
            \QUI::getLocale()->get( 'quiqqer/system', 'control.not.found' ),
            404
        );
    }


    return $Control->create();
}

\QUI::$Ajax->register( 'ajax_controls_get', array( 'control', 'params' ) );
