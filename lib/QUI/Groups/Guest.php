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
    /**
     * constructor
     */
    public function __construct()
    {
        parent::__construct(Manager::GUEST_ID);
    }

    /**
     * Deletes the group and sub-groups
     *
     * @return boolean
     * @throws \QUI\Exception
     */
    public function delete()
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'exception.guest.group.cannot.be.deleted'
            )
        );
    }

    /**
     * set a group attribute
     * ID cannot be set
     *
     * @param string $key - Attribute name
     * @param string|boolean|integer|array $value - value
     *
     * @return Guest
     */
    public function setAttribute($key, $value)
    {
        if ($key == 'id') {
            return $this;
        }

        parent::setAttribute($key, $value);
        return $this;
    }

    /**
     * saves the group
     * All attributes are set in the database
     */
    public function save()
    {
        $this->rights = QUI::getPermissionManager()
            ->getRightParamsFromGroup($this);

        // Felder bekommen
        QUI::getDataBase()->update(
            Manager::table(),
            [
                'name' => 'Guest',
                'toolbar' => $this->getAttribute('toolbar'),
                'rights' => json_encode($this->rights),
                'active' => 1
            ],
            ['id' => $this->getId()]
        );

        $this->createCache();
    }

    /**
     * Returns the Group-ID
     *
     * @return integer
     */
    public function getId()
    {
        return Manager::GUEST_ID;
    }

    /**
     * Activate the group
     */
    public function activate()
    {
    }

    /**
     * deactivate the group
     */
    public function deactivate()
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'exception.guest.group.cannot.be.deactivated'
            )
        );
    }

    /**
     * Is the group active?
     *
     * @return boolean
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Checks if the ID is from a parent group
     *
     * @param integer $id - ID from parent
     * @param boolean $recursiv - checks recursive or not
     *
     * @return boolean
     */
    public function isParent($id, $recursiv = false)
    {
        return false;
    }

    /**
     * return the parent group
     *
     * @param boolean $obj - Parent Objekt (true) oder Parent-ID (false) -> (optional = true)
     *
     * @return object|integer|false
     * @throws \QUI\Exception
     */
    public function getParent($obj = true)
    {
        return false;
    }

    /**
     * Get all parent ids
     *
     * @return array
     */
    public function getParentIds()
    {
        return [];
    }

    /**
     * Have the group subgroups?
     *
     * @return integer
     */
    public function hasChildren()
    {
        return 0;
    }

    /**
     * Returns the sub groups
     *
     * @param array $params - Where Parameter
     *
     * @return array
     */
    public function getChildren($params = [])
    {
        return [];
    }

    /**
     * return the subgroup ids
     *
     * @param boolean $recursiv - recursiv true / false
     * @param      $params - SQL Params (limit, order)
     *
     * @return array
     */
    public function getChildrenIds($recursiv = false, $params = [])
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
                'quiqqer/quiqqer',
                'exception.cannot.create.children'
            )
        );
    }
}
