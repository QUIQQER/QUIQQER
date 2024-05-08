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

    public function getCode(): string
    {
        return $this->getAttribute('code');
    }

    /**
     * Return the md5 string of the message
     */
    public function getHash(): string
    {
        return md5($this->getMessage());
    }

    public function getMessage(): string
    {
        return $this->getAttribute('message');
    }
}
