<?php

/**
 * This file contains QUI\Messages\Message
 */

namespace QUI\Messages;

use QUI;

use function md5;
use function time;

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
    public function __construct(array $params = [])
    {
        // defaults
        $this->setAttributes([
            'message' => '',
            'code' => '',
            'time' => time(),
            'mtype' => $this::class
        ]);

        $this->setAttributes($params);
    }

    /**
     * Return the message code
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->getAttribute('code');
    }

    /**
     * Return the md5 string of the message
     *
     * @return string
     */
    public function getHash(): string
    {
        return md5($this->getMessage());
    }

    /**
     * Return the message text
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->getAttribute('message');
    }
}
