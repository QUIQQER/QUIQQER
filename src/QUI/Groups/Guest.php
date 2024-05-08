<?php

/**
 * This file contains QUI\Groups\Guest
 */

namespace QUI\Groups;

use QUI;
use QUI\Exception;

use function json_encode;

/**
 * The Guest Group
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Guest extends QUI\Groups\Group
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct(Manager::GUEST_ID);
    }

    /**
     * @inheritDoc
     */
    public function delete(): void
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/core',
                'exception.guest.group.cannot.be.deleted'
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $name, mixed $val): void
    {
        if ($name === 'id') {
            return;
        }

        parent::setAttribute($name, $val);
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getId(): int
    {
        return Manager::GUEST_ID;
    }

    /**
     * @inheritDoc
     */
    public function getUUID(): string
    {
        return Manager::GUEST_ID;
    }

    /**
     * @inheritDoc
     */
    public function activate(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function deactivate(): void
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/core',
                'exception.guest.group.cannot.be.deactivated'
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isParent(int|string $id, bool $recursive = false): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getParent(bool $obj = true): Guest|Group|Everyone|null
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getParentIds(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function hasChildren(): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(array $params = []): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getChildrenIds(bool $recursive = false, array $params = []): ?array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
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
