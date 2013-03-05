<?php

/**
 * This file contains QUI_Messages_Message
 */

/**
 * a message - parent class for all other messages
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.messages
 */

class QUI_Messages_Message extends QDOM
{
    /**
     * constructor
     * @param array $attributes - message parameter
     */
    public function __construct($attributes)
    {
        // defaults
        $this->setAttribute('message', '');
        $this->setAttribute('code', 200);

        $this->setAttributes( $attributes );
    }

    /**
     * return the message as an array
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getAttributes();
        $attributes['type'] = $this->getType();

        return $attributes;
    }
}

?>