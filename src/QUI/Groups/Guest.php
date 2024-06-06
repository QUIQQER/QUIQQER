<?php

/**
 * This file contains QUI\Groups\Guest
 */

namespace QUI\Groups;

use QUI;

use function json_encode;

/**
 * The Guest Group
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Guest extends QUI\Groups\Group
{
    public function __construct()
    {
        parent::__construct(Manager::GUEST_ID);
    }

    public function delete(): void
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/core',
                'exception.guest.group.cannot.be.deleted'
            )
        );
    }

    public function setAttribute(string $name, mixed $value): void
    {
        if ($name === 'id') {
            return;
        }

        parent::setAttribute($name, $value);
    }

    public function save(): void
    {
        $this->rights = QUI::getPermissionManager()->getRightParamsFromGroup($this);

        QUI::getDataBase()->update(
            Manager::table(),
            [
                'name' => 'Guest',
                'toolbar' => $this->getAttribute('toolbar'),
                'rights' => json_encode($this->rights),
                'active' => 1
            ],
            ['uuid' => $this->getUUID()]
        );

        $this->createCache();
    }

    public function getId(): int
    {
        return Manager::GUEST_ID;
    }

    public function getUUID(): string
    {
        return (string)Manager::GUEST_ID;
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/core',
                'exception.guest.group.cannot.be.deactivated'
            )
        );
    }

    public function isActive(): bool
    {
        return true;
    }

    public function isParent(int|string $id, bool $recursive = false): bool
    {
        return false;
    }

    public function getParent(bool $obj = true): Guest|Group|Everyone|null
    {
        return null;
    }

    public function getParentIds(): array
    {
        return [];
    }

    public function hasChildren(): int
    {
        return 0;
    }

    public function getChildren(array $params = []): array
    {
        return [];
    }

    public function getChildrenIds(bool $recursive = false, array $params = []): ?array
    {
        return [];
    }

    public function createChild(string $name, ?QUI\Interfaces\Users\User $ParentUser = null): Group
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/core',
                'exception.cannot.create.children'
            )
        );
    }
}
