<?php

/**
 * This file contains QUI\Groups\Everyone
 */

namespace QUI\Groups;

use QUI;
use QUI\Exception;

use function array_filter;
use function explode;
use function json_encode;

/**
 * The Everyone Group
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Everyone extends QUI\Groups\Group
{
    /**
     * constructor
     */
    public function __construct()
    {
        parent::__construct(Manager::EVERYONE_ID);
    }

    /**
     * Deletes the group and subgroups
     *
     * @throws Exception
     */
    public function delete(): void
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/core',
                'exception.everyone.group.cannot.be.deleted'
            )
        );
    }

    /**
     * set a group attribute
     * ID cannot be set
     *
     * @param string $name - Attribute name
     * @param mixed $value - value
     */
    public function setAttribute(string $name, mixed $value): void
    {
        if ($name === 'id') {
            return;
        }

        parent::setAttribute($name, $value);
    }

    /**
     * saves the group
     * All attributes are set in the database
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public function save(): void
    {
        $this->rights = QUI::getPermissionManager()->getRightParamsFromGroup($this);

        // check assigned toolbars
        $assignedToolbars = '';
        $toolbar = '';

        if ($this->getAttribute('assigned_toolbar')) {
            $toolbars = explode(',', $this->getAttribute('assigned_toolbar'));

            $assignedToolbars = array_filter($toolbars, static fn($toolbar) => QUI\Editor\Manager::existsToolbar($toolbar));

            $assignedToolbars = implode(',', $assignedToolbars);
        }

        if (QUI\Editor\Manager::existsToolbar($this->getAttribute('toolbar'))) {
            $toolbar = $this->getAttribute('toolbar');
        }

        QUI::getDataBase()->update(
            Manager::table(),
            [
                'name' => 'Everyone',
                'rights' => json_encode($this->rights),
                'active' => 1,
                'assigned_toolbar' => $assignedToolbars,
                'toolbar' => $toolbar
            ],
            ['uuid' => $this->getUUID()]
        );

        $this->createCache();
    }

    /**
     * @deprecated
     */
    public function getId(): int
    {
        return Manager::EVERYONE_ID;
    }

    public function getUUID(): string
    {
        return Manager::EVERYONE_ID;
    }

    /**
     * Activate the group
     */
    public function activate(): void
    {
    }

    /**
     * deactivate the group
     * @throws Exception
     */
    public function deactivate(): void
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/core',
                'exception.everyone.group.cannot.be.deactivated'
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

    /**
     * return the parent group
     *
     * @param boolean $obj - Parent Object (true) oder Parent-ID (false) -> (optional = true)
     *
     * @return Everyone|Group|Guest|null
     */
    public function getParent(bool $obj = true): null|Group|Guest|Everyone
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

    /**
     * Returns the subgroups
     *
     * @param array $params - Where Parameter
     *
     * @return array
     */
    public function getChildren(array $params = []): array
    {
        return [];
    }

    /**
     * return the subgroup ids
     *
     * @param bool $recursive
     * @param array $params - SQL Params (limit, order)
     *
     * @return array|null
     */
    public function getChildrenIds(bool $recursive = false, array $params = []): ?array
    {
        return [];
    }

    /**
     * Create a subgroup
     *
     * @param string $name - name of the subgroup
     * @param QUI\Interfaces\Users\User|null $ParentUser - (optional), Parent User, which create the user
     *
     * @return Group
     *
     * @throws QUI\Exception
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
