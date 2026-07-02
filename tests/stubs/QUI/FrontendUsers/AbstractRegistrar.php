<?php

namespace QUI\FrontendUsers;

use QUI;

if (!class_exists(Exception::class)) {
    class Exception extends QUI\Exception
    {
    }
}

if (!class_exists(AbstractRegistrar::class)) {
    abstract class AbstractRegistrar extends QUI\QDOM
    {
        /**
         * @return array
         */
        abstract public function validate(): array;

        /**
         * @return array
         */
        abstract public function getInvalidFields(): array;

        abstract public function getUsername(): string;

        abstract public function getControl(): QUI\Control;

        abstract public function onRegistered(QUI\Interfaces\Users\User $User): void;

        abstract public function getTitle(null | QUI\Locale $Locale = null): string;

        abstract public function getDescription(null | QUI\Locale $Locale = null): string;

        abstract public function getIcon(): string;

        abstract public function canSendPassword(): bool;

        public function createUser(): QUI\Interfaces\Users\User
        {
            return QUI::getUsers()->createChild(
                $this->getUsername(),
                QUI::getUsers()->getSystemUser()
            );
        }
    }
}
