<?php

/**
 * This file contains QUI\Groups\Everyone
 */

namespace QUI\Groups;

use QUI;

/**
 * The Everyone Group
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class Everyone extends \QUI\Groups\Group
{
    /**
     * constructor
     */
    public function __construct()
    {
        parent::__construct( 1 );
    }

    /**
     * Deletes the group and sub-groups
     *
     * @todo alle Beziehungen in den Seiten müssen neu gesetzt werden
     * @return Bool
     * @throws \QUI\Exception
     */
    public function delete()
    {
        // Rootgruppe kann nicht gelöscht werden
        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.everyone.group.cannot.be.deleted'
            )
        );
    }

    /**
     * set a group attribute
     * ID cannot be set
     *
     * @param String $key - Attribute name
     * @param String|Bool|Integer|array $value  - value
     *
     * @return Bool
     */
    public function setAttribute($key, $value)
    {
        if ( $key == 'id' ) {
            return false;
        }

        return parent::setAttribute( $key, $value );
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
        $this->_rights = \QUI::getPermissionManager()->getRightParamsFromGroup( $this );

        // Felder bekommen
        \QUI::getDataBase()->update(
            \QUI\Groups\Manager::Table(),
            array(
                'name'    => $this->getAttribute('name'),
                'toolbar' => $this->getAttribute('toolbar'),
                'admin'   => $this->_rootid == $this->getId() ? 1 : (int)$this->getAttribute('admin'),
                'rights'  => json_encode( $this->_rights )
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
        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.everyone.group.cannot.be.deactivated'
            )
        );
    }

    /**
     * Is the group active?
     * @return Bool
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Checks if the ID is from a parent group
     *
     * @param Integer $id - ID from parent
     * @param Bool $recursiv - checks recursive or not
     *
     * @return Bool
     */
    public function isParent($id, $recursiv=false)
    {
        return false;
    }

    /**
     * return the parent group
     *
     * @param Bool $obj - Parent Objekt (true) oder Parent-ID (false) -> (optional = true)
     * @return Object|Integer|false
     * @throws \QUI\Exception
     */
    public function getParent($obj=true)
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
     * @return Integer
     */
    public function hasChildren()
    {
        return 0;
    }

    /**
     * Returns the sub groups
     *
     * @param Array $params - Where Parameter
     * @return Array
     */
    public function getChildren($params=array())
    {
        return array();
    }

    /**
     * return the subgroup ids
     *
     * @param Bool $recursiv - recursiv true / false
     * @param $params - SQL Params (limit, order)
     *
     * @return Array
     */
    public function getChildrenIds($recursiv=false, $params=array())
    {
        return array();
    }

    /**
     * Create a subgroup
     *
     * @param String $name - name of the subgroup
     * @return \QUI\Groups\Manager
     */
    public function createChild($name)
    {
        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.cannot.create.children'
            )
        );
    }
}
