<?php

/**
 * This file contains the \QUI\Utils\Site
 */

namespace QUI\Utils;

/**
 * QUIQQER Site Util class
 *
 * Provides methods for \QUI\Projects\Site manipulation
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Site
{
    /**
     * Set a attribute recursive from its parents if the attribute is not set
     *
     * @param \QUI\Projects\Site $Site
     * @param String $attribute
     */
    static function setRecursivAttribute(\QUI\Projects\Site $Site, $attribute)
    {
        $value = $Site->getAttribute( $attribute );

        if ( !empty( $value ) ) {
            return;
        }

        $Parent = $Site->getParent();

        while ( $Parent )
        {
            $value = $Parent->getAttribute( $attribute );

            if ( !empty( $value ) )
            {
                $Site->setAttribute( $attribute, $value );
                break;
            }

            if ( !$Parent->getParentId() ) {
                break;
            }

            $Parent = $Parent->getParent();
        }
    }

}