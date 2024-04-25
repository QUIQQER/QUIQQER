<?php

/**
 * This file contains \QUI\Users\SystemUser
 */

namespace QUI\Users;

use QUI;

/**
 * the system user
 * Can change things but can not in the admin and can query any ajax functions
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class SystemUser extends QUI\Users\Nobody implements QUI\Interfaces\Users\User
{
    /**
     * constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttribute('username', 'system');
    }

    /**
     * @return int|string
     */
    public function getUniqueId(): int|string
    {
        return $this->getUUID();
    }

    /**
     * @return string|int
     */
    public function getUUID(): string|int
    {
        return "5";
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('username');
    }

    /**
     * @param bool $array
     * @return array
     */
    public function getGroups(bool $array = true): array
    {
        $Everyone = new QUI\Groups\Everyone();

        if ($array === true) {
            return [$Everyone];
        }

        return [$Everyone->getId()];
    }

    /**
     * @return false|int
     */
    public function getId(): false|int
    {
        return 5;
    }
}
