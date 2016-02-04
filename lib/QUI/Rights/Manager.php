<?php

/**
 * This file contains the \QUI\Rights\Manager
 */

namespace QUI\Rights;

use QUI;
use QUI\Groups\Group;
use QUI\Users\User;
use QUI\Utils\Security\Orthos;

/**
 * Rights management
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.rights
 * @licence For copyright and license information, please view the /README.md
 */
class Manager
{
    const TABLE = 'permissions';

    /**
     * internal right cache
     *
     * @var array
     */
    protected $cache = array();

    /**
     * constructor
     * load the available rights
     */
    public function __construct()
    {
        try {
            $result = QUI::getDataBase()->fetch(array(
                'from' => QUI::getDBTableName(self::TABLE)
            ));

            foreach ($result as $entry) {
                $this->cache[$entry['name']] = $entry;
            }
        } catch (QUI\Exception $Exception) {
        }
    }

    /**
     * Return the area, if the area is an allowed area
     *
     * @param string $area - area string, global, user, groups, site, project, media
     *
     * @return string
     */
    public static function parseArea($area)
    {
        switch ($area) {
            case 'global':
            case 'user':
            case 'groups':
            case 'site':
            case 'project':
            case 'media':
                return $area;
                break;
        }

        return '';
    }

    /**
     * Return the corresponding area of a php class
     *
     * @param string $cls
     *
     * @return string
     */
    public static function classToArea($cls)
    {
        switch ($cls) {
            case 'QUI\\Users\\User':
                return 'user';
                break;

            case 'QUI\\Groups\\Guest':
            case 'QUI\\Groups\\Everyone':
            case 'QUI\\Groups\\Group':
                return 'groups';
                break;

            case 'QUI\\Projects\\Site':
            case 'QUI\\Projects\\Site\\Edit':
            case 'QUI\\Projects\\Site\\OnlyDB':
                return 'site';
                break;

            case 'QUI\\Projects\\Project':
                return 'project';
                break;

            // @todo media file classes
            case 'QUI\\Projects\\Media':
                return 'media';
                break;
        }

        return '__null__';
    }

    /**
     * Return the type if the type is an allowed permission type
     *
     * @param string $type - bool, string, int, group, groups, user, users, users_and_groups
     *
     * @return string
     */
    public static function parseType($type)
    {
        switch ($type) {
            case 'bool':
            case 'string':
            case 'int':
            case 'array':
            case 'group':
            case 'groups':
            case 'user':
            case 'users':
            case 'users_and_groups':
                return $type;
                break;
        }

        return 'bool';
    }

    /**
     * Rechte Setup, legt alle Felder für die Rechte an
     */
    public static function setup()
    {
        $DBTable = QUI::getDataBase()->Table();
        $table   = QUI::getDBTableName(self::TABLE);

        $table2users    = $table . '2users';
        $table2groups   = $table . '2groups';
        $table2sites    = $table . '2sites';
        $table2projects = $table . '2projects';
        $table2media    = $table . '2media';

        // Haupttabelle anlegen
        $DBTable->appendFields($table, array(
            'name' => 'varchar(100) NOT NULL',
            'type' => 'varchar(20)  NOT NULL',
            'area' => 'varchar(20)  NOT NULL',
            'title' => 'varchar(255) NULL',
            'desc' => 'text NULL',
            'src' => 'varchar(200) NULL',
            'defaultvalue' => 'text NULL'
        ));

        $DBTable->setIndex($table, 'name');


        $DBTable->appendFields($table2users, array(
            'user_id' => 'int(11) NOT NULL',
            'permissions' => 'text'
        ));

        $DBTable->appendFields($table2groups, array(
            'group_id' => 'int(11) NOT NULL',
            'permissions' => 'text'
        ));

        $DBTable->appendFields($table2sites, array(
            'project' => 'varchar(200) NOT NULL',
            'lang' => 'varchar(2) NOT NULL',
            'id' => 'bigint(20)',
            'permission' => 'text',
            'value' => 'text'
        ));

        $DBTable->appendFields($table2projects, array(
            'project' => 'varchar(200) NOT NULL',
            'lang' => 'varchar(2) NOT NULL',
            'permission' => 'text',
            'value' => 'text'
        ));

        $DBTable->appendFields($table2media, array(
            'project' => 'varchar(200) NOT NULL',
            'lang' => 'varchar(2)',
            'id' => 'bigint(20)',
            'permission' => 'text',
            'value' => 'text'
        ));
    }

    /**
     * Search all groups and set the default permissions if the permissions not exists
     */
    public static function importPermissionsForGroups()
    {
        $Groups = QUI::getGroups();
        $groups = $Groups->search();

        foreach ($groups as $groupData) {
            try {
                self::importPermissionsForGroup(
                    $Groups->get($groupData['id'])
                );

            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError(
                    '\QUI\Rights\Manager::importPermissionsForGroups() -> ' .
                    $Exception->getMessage()
                );
            }
        }
    }

    /**
     * Set the default permissions for the group
     *
     * @param Group $Group
     *
     * @throws QUI\Exception
     */
    public static function importPermissionsForGroup(Group $Group)
    {
        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getPermissions($Group);

        $Manager->setPermissions($Group, $permissions);
    }

    /**
     * Add a permission
     *
     * @param array $params - Permission params
     *                            array(
     *                            name =>
     *                            desc =>
     *                            area =>
     *                            title => translation.var.var
     *                            type =>
     *                            default =>
     *                            src =>
     *                            )
     *
     * @throws QUI\Exception
     */
    public function addPermission($params)
    {
        $DataBase = QUI::getDataBase();
        $needles  = array(
            'name',
            'title',
            'desc',
            'type',
            'area',
            'src',
            'defaultvalue'
        );

        foreach ($needles as $needle) {
            if (!isset($params[$needle])) {
                $params[$needle] = '';
            }
        }

        if (empty($params['name'])) {
            return;
        }

        // if exist update it
        if (isset($this->cache[$params['name']])) {
            $DataBase->update(
                QUI::getDBTableName(self::TABLE),
                array(
                    'title' => trim($params['title']),
                    'desc' => trim($params['desc']),
                    'type' => self::parseType($params['type']),
                    'area' => self::parseArea($params['area']),
                    'src' => $params['src'],
                    'defaultvalue' => $params['defaultvalue']
                ),
                array(
                    'name' => $params['name']
                )
            );

            return;
        }

        // if not exist, insert it
        $DataBase->insert(
            QUI::getDBTableName(self::TABLE),
            array(
                'name' => $params['name'],
                'title' => trim($params['title']),
                'desc' => trim($params['desc']),
                'type' => self::parseType($params['type']),
                'area' => self::parseArea($params['area']),
                'src' => $params['src'],
                'defaultvalue' => $params['defaultvalue']
            )
        );

        $this->cache[$params['name']] = $params;
    }

    /**
     * Delete a permission
     *
     * @param string $permission - name of the permission
     *
     * @throws QUI\Exception
     */
    public function deletePermission($permission)
    {
        $permissions = $this->getPermissionList();

        if (!isset($permissions[$permission])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.permission.not.found'
                )
            );
        }

        $params = $permissions[$permission];

        if ($params['src'] != 'user') {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.delete.only.user.permissions'
                )
            );
        }

        QUI::getDataBase()->delete(
            QUI::getDBTableName(self::TABLE),
            array(
                'name' => $permission,
                'src' => 'user'
            )
        );
    }

    /**
     * Import a permissions.xml
     *
     * @param string $xmlfile - Path to the file
     * @param string $src - optional, the src from where the rights come from
     *                            eq: system, plugin-name, user
     */
    public function importPermissionsFromXml($xmlfile, $src = '')
    {
        $permissions = QUI\Utils\XML::getPermissionsFromXml($xmlfile);

        if (!count($permissions)) {
            return;
        }

        foreach ($permissions as $permission) {
            $permission['src']          = $src;
            $permission['defaultvalue'] = '';

            if (isset($permission['default'])) {
                $permission['defaultvalue'] = $permission['default'];
            }

            $this->addPermission($permission);
        }
    }

    /**
     * Return all available permissions
     *
     * @param string|boolean $area - optional, specified the area of the permissions
     *
     * @return array
     */
    public function getPermissionList($area = false)
    {
        if (!$area) {
            return $this->cache;
        }

        // if an area is specified
        $result = array();

        foreach ($this->cache as $key => $params) {
            if ($params['area'] == $area) {
                $result[$key] = $params;
                continue;
            }

            if (empty($params['area'])
                && ($area == 'user'
                    || $area == 'groups')
            ) {
                $result[$key] = $params;
            }
        }

        return $result;
    }

    /**
     * Return the permission data
     *
     * @param string $permission - Name of the permission
     *
     * @return false|array
     * @throws QUI\Exception
     */
    public function getPermissionData($permission)
    {
        if (!isset($this->cache[$permission])) {
            throw new QUI\Exception('Permission not found');
        }

        return $this->cache[$permission];
    }

    /**
     * Return all permissions from a group, user, site, project or media
     *
     * @param QUI\Groups\Group|QUI\Users\User|QUI\Projects\Project|QUI\Projects\Site $Obj
     *
     * @return array
     */
    public function getPermissions($Obj)
    {
        $area = $this->classToArea(get_class($Obj));

        switch ($area) {
            case 'project':
                return $this->getProjectPermissions($Obj);
                break;

            case 'site':
                return $this->getSitePermissions($Obj);
                break;
        }

        $permissions = array();

        $data  = $this->getData($Obj);
        $_list = $this->getPermissionList($area);

        foreach ($_list as $permission => $params) {
            if (isset($params['defaultvalue'])) {
                $permissions[$permission] = $params['defaultvalue'];
                continue;
            }

            $permissions[$permission] = false;
        }


        if (!isset($data[0])) {
            return $permissions;
        }

        $obj_permissions = json_decode($data[0]['permissions'], true);

        foreach ($obj_permissions as $obj_permission => $value) {
            // parse var type
            if (isset($permissions[$obj_permission])) {
                $permissions[$obj_permission] = $value;
            }
        }

        return $permissions;
    }

    /**
     * Return the permissions from a site
     *
     * @param QUI\Projects\Project $Project
     *
     * @return array
     */
    public function getProjectPermissions(QUI\Projects\Project $Project)
    {
        $data  = $this->getData($Project);
        $_list = $this->getPermissionList('project');

        $permissions = array();

        foreach ($_list as $permission => $params) {
            $permissions[$permission] = false;
        }

        foreach ($data as $permission => $value) {
            $permissions[$permission] = $value;
        }

        return $permissions;
    }

    /**
     * Return the permissions from a site
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit $Site
     *
     * @return array
     */
    public function getSitePermissions($Site)
    {
        if (QUI\Projects\Site\Utils::isSiteObject($Site) === false) {
            return array();
        }


        $data  = $this->getData($Site);
        $_list = $this->getPermissionList('site');

        $permissions = array();

        foreach ($_list as $permission => $params) {
            $permissions[$permission] = false;
        }

        foreach ($data as $permission => $value) {
            $permissions[$permission] = $value;
        }

        return $permissions;
    }

    /**
     * Set the permissions for an object
     *
     * @param QUI\Users\User|QUI\Groups\Group|
     *                           QUI\Projects\Project|QUI\Projects\Site|QUI\Projects\Site\Edit $Obj
     * @param array $permissions - Array of permissions
     * @param boolean|QUI\Users\User $EditUser - Edit user
     *
     * @throws QUI\Exception
     *
     * @todo  permissions for media
     * @todo  permissions for project
     */
    public function setPermissions($Obj, $permissions, $EditUser = false)
    {
        if (empty($permissions)) {
            throw new QUI\Exception(
                'Permissions are empty'
            );
        }


        $cls = get_class($Obj);

        switch ($cls) {
            case 'QUI\\Users\\User':
            case 'QUI\\Groups\\Group':
            case 'QUI\\Groups\\Everyone':
            case 'QUI\\Groups\\Guest':
                break;

            case 'QUI\\Projects\\Project':
                $this->setProjectPermissions($Obj, $permissions, $EditUser);

                return;
                break;

            case 'QUI\\Projects\\Site':
            case 'QUI\\Projects\\Site\\Edit':
            case 'QUI\\Projects\\Site\\OnlyDB':
                $this->setSitePermissions($Obj, $permissions, $EditUser);

                return;
                break;

            default:
                throw new QUI\Exception(
                    'Cannot set Permissions. Object not allowed'
                );
                break;
        }

        QUI\Rights\Permission::checkPermission(
            'quiqqer.system.permissions',
            $EditUser
        );

        $area  = $this->classToArea($cls);
        $_data = $this->getData($Obj); // old permissions
        $list  = $this->getPermissionList($area);

        $data = array();

        if (isset($_data[0]) && isset($_data[0]['permissions'])) {
            $data = json_decode($_data[0]['permissions'], true);
        }

        foreach ($list as $permission => $params) {
            if (!isset($permissions[$permission])) {
                continue;
            }

            $data[$permission] = $this->cleanValue(
                $params['type'],
                $permissions[$permission]
            );
        }

        $DataBase = QUI::getDataBase();
        $table    = QUI::getDBTableName(self::TABLE);

        $table2users  = $table . '2users';
        $table2groups = $table . '2groups';
        $table2media  = $table . '2media';

        // areas
        switch ($area) {
            case 'user':
                /* @var $Obj User */
                if (!isset($_data[0])) {
                    $DataBase->insert(
                        $table2users,
                        array('user_id' => $Obj->getId())
                    );
                }

                if (isset($data['permissions'])) {
                    unset($data['permissions']);
                }

                $DataBase->update(
                    $table2users,
                    array('permissions' => json_encode($data)),
                    array('user_id' => $Obj->getId())
                );
                break;

            case 'groups':
                /* @var $Obj Group */
                if (!isset($_data[0])) {
                    $DataBase->insert(
                        $table2groups,
                        array('group_id' => $Obj->getId())
                    );
                }

                if (isset($data['permissions'])) {
                    unset($data['permissions']);
                }


                $DataBase->update(
                    $table2groups,
                    array('permissions' => json_encode($data)),
                    array('group_id' => $Obj->getId())
                );
                break;

            case 'media':
                /* @var $Obj \QUI\Interfaces\Projects\Media\File */
                $Project = $Obj->getProject();

                /* @var $Project \QUI\Projects\Project */
                if (!isset($_data[0])) {
                    $DataBase->insert(
                        $table2media,
                        array(
                            'project' => $Project->getName(),
                            'lang' => $Project->getLang(),
                            'id' => $Obj->getId()
                        )
                    );

                    return;
                }

                $DataBase->update(
                    $table2media,
                    array('permissions' => json_encode($data)),
                    array(
                        'project' => $Project->getName(),
                        'lang' => $Project->getLang(),
                        'id' => $Obj->getId()
                    )
                );
                break;
        }
    }

    /**
     * Set the permissions for a site object
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit|QUI\Projects\Site\OnlyDB $Site
     * @param array $permissions - Array of permissions
     * @param boolean|QUI\Users\User $EditUser - Edit user
     */
    public function setSitePermissions($Site, $permissions, $EditUser = false)
    {
        if (QUI\Projects\Site\Utils::isSiteObject($Site) === false) {
            return;
        }

        $Site->checkPermission('quiqqer.projects.sites.set_permissions', $EditUser);
        $Site->checkPermission('quiqqer.project.sites.edit', $EditUser);

        $_data = $this->getData($Site);

        $data = array();
        $list = $this->getPermissionList('site');

        // look at permission list and cleanup the values
        foreach ($list as $permission => $params) {
            if (!isset($permissions[$permission])) {
                continue;
            }

            $Perm = $permissions[$permission];

            if (is_string($Perm)) {
                $permissionValue = $Perm;

            } elseif (is_array($Perm)) {
                $permissionValues = array();

                foreach ($Perm as $PermValue) {
                    if (QUI::getUsers()->isUser($PermValue)) {
                        /* @var $PermValue QUI\Users\User */
                        $permissionValues[] = 'u' . $PermValue->getId();
                        continue;
                    }

                    if (QUI::getGroups()->isGroup($PermValue)) {
                        /* @var $PermValue QUI\Groups\Group */
                        $permissionValues[] = 'g' . $PermValue->getId();
                    }
                }

                $permissionValue = implode(',', $permissionValues);

            } elseif (QUI::getUsers()->isUser($Perm)) {
                /* @var $Perm QUI\Users\User */
                $permissionValue = 'u' . $Perm->getId();

            } elseif (QUI::getGroups()->isGroup($Perm)) {
                /* @var $Perm QUI\Groups\Group */
                $permissionValue = 'g' . $Perm->getId();

            } else {
                continue;
            }


            $data[$permission] = $this->cleanValue(
                $params['type'],
                $permissionValue
            );
        }

        // set add permissions
        foreach ($data as $permission => $value) {
            if (!isset($_data[$permission])) {
                $this->addSitePermission($Site, $permission, $value);
                continue;
            }

            $this->setSitePermission($Site, $permission, $value);
        }
    }

    /**
     * Updates the permission entry for the site
     *
     * @param QUI\Projects\Site $Site
     * @param string $permission
     * @param string|integer $value
     */
    protected function setSitePermission($Site, $permission, $value)
    {
        $Project = $Site->getProject();
        $table   = QUI::getDBTableName(self::TABLE);

        QUI::getDataBase()->update(
            $table . '2sites',
            array('value' => $value),
            array(
                'project' => $Project->getName(),
                'lang' => $Project->getLang(),
                'id' => $Site->getId(),
                'permission' => $permission
            )
        );
    }

    /**
     * Add a new permission entry for site
     *
     * @param QUI\Projects\Site $Site
     * @param string $permission
     * @param string|integer $value
     */
    protected function addSitePermission($Site, $permission, $value)
    {
        $Project = $Site->getProject();
        $table   = QUI::getDBTableName(self::TABLE);

        QUI::getDataBase()->insert(
            $table . '2sites',
            array(
                'project' => $Project->getName(),
                'lang' => $Project->getLang(),
                'id' => $Site->getId(),
                'permission' => $permission,
                'value' => $value
            )
        );
    }

    /**
     * Remove all permissions from the site
     *
     * @param QUI\Projects\Site|QUI\Projects\Site\Edit|QUI\Projects\Site\OnlyDB $Site
     * @param boolean|\QUI\Users\User $EditUser
     */
    public function removeSitePermissions($Site, $EditUser = false)
    {
        $Site->checkPermission('quiqqer.projects.site.edit', $EditUser);


        $Project = $Site->getProject();
        $table   = QUI::getDBTableName(self::TABLE);

        QUI::getDataBase()->delete(
            $table . '2sites',
            array(
                'project' => $Project->getName(),
                'lang' => $Project->getLang(),
                'id' => $Site->getId()
            )
        );
    }

    /**
     * Set the permissions for a project object
     *
     * @param QUI\Projects\Project $Project
     * @param array $permissions
     * @param boolean|\QUI\Users\User $EditUser
     */
    public function setProjectPermissions(
        QUI\Projects\Project $Project,
        $permissions,
        $EditUser = false
    ) {
        $data  = array();
        $_data = $this->getData($Project);
        $list  = $this->getPermissionList('project');

        QUI\Rights\Permission::checkPermission(
            'quiqqer.system.permissions',
            $EditUser
        );

        // look at permission list and cleanup the values
        foreach ($list as $permission => $params) {
            if (!isset($permissions[$permission])) {
                continue;
            }

            $data[$permission] = $this->cleanValue(
                $params['type'],
                $permissions[$permission]
            );
        }


        // set add permissions
        foreach ($data as $permission => $value) {
            if (!isset($_data[$permission])) {
                $this->addProjectPermission($Project, $permission, $value);
                continue;
            }

            $this->setProjectPermission($Project, $permission, $value);
        }
    }

    /**
     * Updates the permission entry for the site
     *
     * @param QUI\Projects\Project $Project
     * @param string $permission
     * @param string|integer $value
     */
    protected function setProjectPermission(
        QUI\Projects\Project $Project,
        $permission,
        $value
    ) {
        $table = QUI::getDBTableName(self::TABLE);

        QUI::getDataBase()->update(
            $table . '2projects',
            array('value' => $value),
            array(
                'project' => $Project->getName(),
                'lang' => $Project->getLang(),
                'permission' => $permission
            )
        );
    }

    /**
     * Add a new permission entry for site
     *
     * @param QUI\Projects\Project $Project
     * @param string $permission
     * @param string|integer $value
     */
    protected function addProjectPermission(
        QUI\Projects\Project $Project,
        $permission,
        $value
    ) {
        $table = QUI::getDBTableName(self::TABLE);

        QUI::getDataBase()->insert(
            $table . '2projects',
            array(
                'project' => $Project->getName(),
                'lang' => $Project->getLang(),
                'permission' => $permission,
                'value' => $value
            )
        );
    }

    /**
     * Return the permissions data of an object
     *
     * @param mixed $Obj
     *
     * @return array
     */
    protected function getData($Obj)
    {
        $DataBase = QUI::getDataBase();

        $table = QUI::getDBTableName(self::TABLE);
        $area  = $this->classToArea(get_class($Obj));


        if ($area === 'user') {
            /* @var $Obj User */
            return $DataBase->fetch(array(
                'from' => $table . '2users',
                'where' => array(
                    'user_id' => $Obj->getId()
                ),
                'limit' => 1
            ));
        }

        if ($area === 'groups') {
            /* @var $Obj Group */
            return $DataBase->fetch(array(
                'from' => $table . '2groups',
                'where' => array(
                    'group_id' => $Obj->getId()
                ),
                'limit' => 1
            ));
        }

        if ($area === 'project') {
            /* @var $Obj QUI\Projects\Project */
            $data = $DataBase->fetch(array(
                'from' => $table . '2projects',
                'where' => array(
                    'project' => $Obj->getName(),
                    'lang' => $Obj->getLang()
                )
            ));

            $result = array();

            foreach ($data as $entry) {
                $result[$entry['permission']] = $entry['value'];
            }

            return $result;
        }

        if ($area === 'site') {
            /* @var $Obj QUI\Projects\Site */
            /* @var $Project QUI\Projects\Project */
            $Project = $Obj->getProject();

            $data = $DataBase->fetch(array(
                'from' => $table . '2sites',
                'where' => array(
                    'project' => $Project->getName(),
                    'lang' => $Project->getLang(),
                    'id' => $Obj->getId()
                )
            ));

            $result = array();

            foreach ($data as $entry) {
                $result[$entry['permission']] = $entry['value'];
            }

            return $result;
        }

        if ($area === 'media') {
            /* @var $Obj QUI\Interfaces\Projects\Media\File */
            /* @var $Project QUI\Projects\Project */
            $Project = $Obj->getProject();

            return $DataBase->fetch(array(
                'from' => $table . '2media',
                'where' => array(
                    'project' => $Project->getName(),
                    'lang' => $Project->getLang(),
                    'id' => $Obj->getId()
                )
            ));
        }

        return array();
    }

    /**
     * Cleanup the value for the type
     *
     * @param string $type
     * @param string|integer $val
     *
     * @return string|integer
     */
    protected function cleanValue($type, $val)
    {
        switch ($type) {
            case 'int':
                $val = (int)$val;
                break;

            case 'users_and_groups':
                // u1234566775 <- user-id
                // g1234566775 <- group-id
                $val = preg_replace('/[^0-9,ug]/', '', $val);
                break;

            case 'users':
            case 'groups':
                $val = preg_replace('/[^0-9,]/', '', $val);
                break;

            case 'user':
            case 'group':
                $val = preg_replace('/[^0-9]/', '', $val);
                break;

            case 'array':
                $val = Orthos::clearArray($val);
                break;

            case 'string':
                $val = Orthos::clearMySQL($val);
                break;

            default:
                $val = (bool)$val;
                break;
        }

        return $val;
    }


    /**
     * ab hier old
     */


    /**
     * Rechte vom Benutzer bekommen
     * Geht besser über User->getPermission('right')
     *
     * @param User $User
     * @param string $permission
     * @param callback|string|boolean $ruleset
     *
     * @return mixed
     *
     * @throws QUI\Exception
     *
     * @example
     * $result = getUserPermission($User, 'name.of.the.permission', function($params)
     * {
     * return $params['result'];
     * });
     *
     * @example
     * $right = $User->getPermission($perm, 'maxInteger');
     */
    public function getUserPermission($User, $permission, $ruleset = false)
    {
        /* @var $User User */
        $usersAndGroups = $User->getGroups();
        $result         = false;

        if (!$User->getId()) {
            return false;
        }

        // user permission check
        if ($ruleset) {
            $usersAndGroups[] = $User;
        } else {
            $userPermissions = $this->getData($User);

            if (isset($userPermissions[0])
                && isset($userPermissions[0]['permissions'])
            ) {
                $userPermissions = json_decode(
                    $userPermissions[0]['permissions'],
                    true
                );

                if (isset($userPermissions[$permission])) {
                    return $userPermissions[$permission];
                }
            }
        }


        // group permissions
        if ($ruleset) {
            if (is_string($ruleset)
                && method_exists('QUI\Rights\PermissionOrder', $ruleset)
            ) {
                $result = QUI\Rights\PermissionOrder::$ruleset($permission, $usersAndGroups);

            } else {
                if (is_callable($ruleset)) {
                } else {
                    throw new QUI\Exception('Unknown ruleset [getUserPermission]');
                }
            }

        } else {
            $result = QUI\Rights\PermissionOrder::permission($permission, $usersAndGroups);
        }

        return $result;
    }

    /**
     * @param $User
     * @return array
     */
    public function getUserPermissionData($User)
    {
        $userPermissions = $this->getData($User);

        if (isset($userPermissions[0])
            && isset($userPermissions[0]['permissions'])
        ) {
            $userPermissions = json_decode(
                $userPermissions[0]['permissions'],
                true
            );

            if (is_array($userPermissions)) {
                return $userPermissions;
            }
        }

        return array();
    }

    /**
     * Rechte Array einer Gruppe aus den Attributen erstellen
     * Wird zum Beispiel zum Speichern einer Gruppe verwendet
     *
     * @todo das muss vielleicht überdacht werden
     *
     * @param QUI\Groups\Group $Group
     *
     * @return array
     */
    public function getRightParamsFromGroup(Group $Group)
    {
        $result = array();
        $rights = QUI::getDataBase()->fetch(array(
            'select' => 'name,type',
            'from' => self::TABLE
        ));

        foreach ($rights as $right) {
            if ($Group->existsRight($right['name']) === false) {
                continue;
            }

            $val = $Group->hasPermission($right['name']);

            // bool, string, int, group, array
            switch ($right['type']) {
                case 'int':
                    $val = (int)$val;
                    break;

                case 'groups':
                    // kommasepariert und zahlen
                    $val = preg_replace('/[^0-9,]/', '', $val);
                    break;

                case 'array':
                    $val = Orthos::clearArray($val);
                    break;

                case 'string':
                    $val = Orthos::clearMySQL($val);
                    break;

                default:
                    $val = (bool)$val;
                    break;
            }

            $result[$right['name']] = $val;
        }

        return $result;
    }
}
