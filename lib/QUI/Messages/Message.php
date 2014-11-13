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

class Message extends \QUI\QDOM
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
            'time'    => time(),
            'mtype'   => get_class( $this )
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

    /**
     * Return the md5 string of the message
     * @return string
     */
    public function getHash()
    {
        return md5( $this->getMessage() );
    }
}
