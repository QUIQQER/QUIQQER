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
        parent::__construct(1);
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
     * @param string                    $key   - Attribute name
     * @param string|boolean|Integer|array $value - value
     *
     * @return boolean
     */
    public function setAttribute($key, $value)
    {
        if ($key == 'id') {
            return false;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Returns the Group-ID
     *
     * @return Integer
     */
    public function getId()
    {
        return 1;
    }

    /**
     * saves the group
     * All attributes are set in the database
     */
    public function save()
    {
        $this->_rights = QUI::getPermissionManager()
                            ->getRightParamsFromGroup($this);

        // Felder bekommen
        QUI::getDataBase()->update(
            QUI\Groups\Manager::Table(),
            array(
                'name'    => 'Everyone',
                'toolbar' => $this->getAttribute('toolbar'),
                'admin'   => 0,
                'rights'  => json_encode($this->_rights),
                'active'  => 1
            ),
            array('id' => $this->getId())
        );

        $this->_createCache();
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
     * @param Integer $id       - ID from parent
     * @param boolean    $recursiv - checks recursive or not
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
     * @return Object|Integer|false
     * @throws \QUI\Exception
     */
    public function getParent($obj = true)
    {

    }

    /**
     * Get all parent ids
     *
     * @return array
     */
    public function getParentIds()
    {

    }

    /**
     * Have the group subgroups?
     *
     * @return Integer
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
    public function getChildren($params = array())
    {
        return array();
    }

    /**
     * return the subgroup ids
     *
     * @param boolean $recursiv - recursiv true / false
     * @param      $params   - SQL Params (limit, order)
     *
     * @return array
     */
    public function getChildrenIds($recursiv = false, $params = array())
    {
        return array();
    }

    /**
     * Create a subgroup
     *
     * @param string $name - name of the subgroup
     *
     * @return \QUI\Groups\Manager
     * @throws QUI\Exception
     */
    public function createChild($name)
    {
        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/system',
                'exception.cannot.create.children'
            )
        );
    }
}
