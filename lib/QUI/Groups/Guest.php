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
     * constructor
     */
    public function __construct()
    {
        parent::__construct(Manager::GUEST_ID);
    }

    /**
     * Deletes the group and subgroups
     *
     * @return void
     * @throws Exception
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
     * set a group attribute
     * ID cannot be set
     *
     * @param string $name - Attribute name
     * @param mixed $val - value
     */
    public function setAttribute(string $name, mixed $val): void
    {
        if ($name == 'id') {
            return;
        }

        parent::setAttribute($name, $val);
    }

    /**
     * saves the group
     * All attributes are set in the database
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
     * Returns the Group-ID
     *
     * @return integer
     * @deprecated
     */
    public function getId(): int
    {
        return Manager::GUEST_ID;
    }

    public function getUUID(): string
    {
        return Manager::GUEST_ID;
    }

    /**
     * Activate the group
     */
    public function activate(): void
    {
    }

    /**
     * deactivate the group
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
     * Is the group active?
     *
     * @return boolean
     */
    public function isActive(): bool
    {
        return true;
    }

    /**
     * Checks if the ID is from a parent group
     *
     * @param int|string $id - ID from parent
     * @param boolean $recursive - checks recursive or not
     *
     * @return boolean
     */
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
    public function getParent(bool $obj = true): Guest|Group|Everyone|null
    {
        return null;
    }

    /**
     * Get all parent ids
     *
     * @return array
     */
    public function getParentIds(): array
    {
        return [];
    }

    /**
     * Have the group subgroups?
     *
     * @return integer
     */
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
     * @param boolean $recursive - recursive true / false
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
