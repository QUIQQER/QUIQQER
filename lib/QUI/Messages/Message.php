<?php

/**
 * This file containes QUI\Messages\Message
 */

namespace QUI\Messages;

/**
 * A message
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Message extends \PT_DOM
{
    /**
     * constructor
     *
     * @param array $params
     */
    public function __construct($params)
    {
        // defaults
        $this->setAttributes(array(
            'message' => '',
            'code'    => '',
            'time'    => time()
        ));

        $this->setAttributes( $params );
    }

    /**
     * Return a the message text
     * @return String
     */
    public function getMessage()
    {
        return $this->getAttribute( 'message' );
    }

    /**
     * Return a the message code
     * @return String
     */
    public function getCode()
    {
        return $this->getAttribute( 'code' );
    }
}
