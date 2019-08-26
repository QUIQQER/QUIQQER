<?php

/**
 * This file contains QUI\Groups\Everyone
 */

namespace QUI\Groups;

use QUI;

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
     * Deletes the group and sub-groups
     *
     * @return boolean
     * @throws \QUI\Exception
     */
    public function delete()
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/system',
                'exception.everyone.group.cannot.be.deleted'
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
     * @return Everyone
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
     * Returns the Group-ID
     *
     * @return integer
     */
    public function getId()
    {
        return Manager::EVERYONE_ID;
    }

    /**
     * saves the group
     * All attributes are set in the database
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public function save()
    {
        $this->rights = QUI::getPermissionManager()->getRightParamsFromGroup($this);

        // check assigned toolbars
        $assignedToolbars = '';
        $toolbar          = '';

        if ($this->getAttribute('assigned_toolbar')) {
            $toolbars = \explode(',', $this->getAttribute('assigned_toolbar'));

            $assignedToolbars = \array_filter($toolbars, function ($toolbar) {
                return QUI\Editor\Manager::existsToolbar($toolbar);
            });

            $assignedToolbars = implode(',', $assignedToolbars);
        }

        if (QUI\Editor\Manager::existsToolbar($this->getAttribute('toolbar'))) {
            $toolbar = $this->getAttribute('toolbar');
        }

        // Felder bekommen
        QUI::getDataBase()->update(
            QUI\Groups\Manager::table(),
            [
                'name'             => 'Everyone',
                'rights'           => \json_encode($this->rights),
                'active'           => 1,
                'assigned_toolbar' => $assignedToolbars,
                'toolbar'          => $toolbar
            ],
            ['id' => $this->getId()]
        );

        $this->createCache();
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
                'quiqqer/system',
                'exception.everyone.group.cannot.be.deactivated'
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
     * @param QUI\Interfaces\Users\User $ParentUser - (optional), Parent User, which create the user
     *
     * @return \QUI\Groups\Manager
     * @throws QUI\Exception
     */
    public function createChild($name, $ParentUser = null)
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/system',
                'exception.cannot.create.children'
            )
        );
    }
}
