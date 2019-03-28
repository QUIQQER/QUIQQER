<?php

/**
 * This file containes QUI\Messages\Message
 */

namespace QUI\Messages;

use QUI;

/**
 * A message
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Message extends QUI\QDOM
{
    /**
     * constructor
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        // defaults
        $this->setAttributes([
            'message' => '',
            'code'    => '',
            'time'    => \time(),
            'mtype'   => \get_class($this)
        ]);

        $this->setAttributes($params);
    }

    /**
     * Return a the message text
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getAttribute('message');
    }

    /**
     * Return a the message code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getAttribute('code');
    }

    /**
     * Return the md5 string of the message
     *
     * @return string
     */
    public function getHash()
    {
        return \md5($this->getMessage());
    }
}
