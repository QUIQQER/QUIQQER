<?php

/**
 * This file contains \QUI\Permissions\Permission
 */

namespace QUI\Permissions;

use QUI;
use QUI\Projects\Media;
use QUI\Projects\Project;
use QUI\Users\User;
use QUI\Groups\Group;

/**
 * Provides methods for quick rights checking
 *
 * all methods with check throws Exceptions
 * all methods with is or has return the permission value
 *     it makes a check and capture the exceptions
 *
 * all methods with get return the permission entries
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Permission
{
    /**
     * @var null|User
     */
    protected static $User = null;

    /**
     * @return QUI\Interfaces\Users\User
     */
    protected static function getUser()
    {
        if (!\is_null(self::$User)) {
            return self::$User;
        }

        return QUI::getUserBySession();
    }

    /**
     * Set the global user for the permissions
     * You can set the default user for the permission checks,
     * default is the session user
     *
     * @param QUI\Interfaces\Users\User $User
     */
    public static function setUser(QUI\Interfaces\Users\User $User)
    {
        self::$User = $User;
    }

    /**
     * Checks, if the user is an admin user
     *
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @return boolean
     */
    public static function isAdmin($User = false)
    {
        if (!$User) {
            $User = self::getUser();
        }

        try {
            return self::checkPermission('quiqqer.admin', $User);
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Prüft den Benutzer auf SuperUser
     *
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @return boolean
     */
    public static function isSU($User = false)
    {
        if (!$User) {
            $User = self::getUser();
        }

        // old
        if ($User->isSU()) {
            return true;
        }

//        try {
//            return self::checkPermission('quiqqer.su', $User);
//
//        } catch (QUI\Exception $Exception) {
//        }

        return false;
    }

    /**
     * has the User the permission
     *
     * @param string $perm
     * @param \QUI\Users\User|boolean $User
     *
     * @return false|string|permission
     */
    public static function hasPermission($perm, $User = false)
    {
        try {
            return self::checkPermission($perm, $User);
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Prüft ob der Benutzer in den Adminbereich darf
     *
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @throws \QUI\Exception
     */
    public static function checkAdminUser($User = false)
    {
        $UserToCheck = false;

        if (!$User) {
            $UserToCheck = self::getUser();
        }

        if (!$User) {
            self::checkUser();
        } else {
            self::checkUser($UserToCheck);
        }

        if (!self::isAdmin($UserToCheck)) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                440
            );
        }
    }

    /**
     * Checks whether the user has the permission
     *
     * @param string $perm
     * @param \QUI\Users\User|boolean|null $User - optional
     *
     * @return false|string|permission
     *
     * @throws \QUI\Permissions\Exception
     */
    public static function checkPermission($perm, $User = false)
    {
        if (!$User) {
            $User = self::getUser();
        }

        if ($User->isSU()) {
            return true;
        }

        if (QUI::getUsers()->isSystemUser($User)) {
            return true;
        }

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getPermissions($User);

        // first check user permission
        if (isset($permissions[$perm]) && !empty($permissions[$perm])) {
            return $permissions[$perm];
        }

        $groups = $User->getGroups();

        if (!empty($groups)) {
            foreach ($groups as $Group) {
                $permissions = $Manager->getPermissions($Group);

                if (isset($permissions[$perm]) && !empty($permissions[$perm])) {
                    return $permissions[$perm];
                }
            }
        }

        throw new QUI\Permissions\Exception(
            QUI::getLocale()->get('quiqqer/system', 'exception.no.permission'),
            403
        );
    }

    /**
     * Check the permission with a given permission list
     *
     * @param array $permissions - list of permissions
     * @param string $perm
     * @param \QUI\Users\User|boolean $User
     *
     * @return boolean
     *
     * @throws \QUI\Permissions\Exception
     */
    public static function checkPermissionList($permissions, $perm, $User = false)
    {
        if (!isset($permissions[$perm])) {
            QUI\System\Log::addNotice(
                'Permission missing: '.$perm
            );

            return true;
        }

        if (!$User) {
            $User = self::getUser();
        }

        if (empty($permissions[$perm])) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                403,
                [
                    'userid'   => $User->getId(),
                    'username' => $User->getName()
                ]
            );
        }

        // what type
        try {
            $Manager   = QUI::getPermissionManager();
            $perm_data = $Manager->getPermissionData($perm);
        } catch (QUI\Exception $Exception) {
            throw new QUI\Permissions\Exception(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }

        $perm_value = $permissions[$perm];

        $check = false;

        switch ($perm_data['type']) {
            case 'bool':
                if ((bool)$perm_value) {
                    $check = true;
                }
                break;

            case 'group':
                $group_ids = $User->getGroups(false);
                $group_ids = \explode(',', $group_ids);

                if (\strpos($perm_value, 'g') !== false
                    || \strpos($perm_value, 'u') !== false
                ) {
                    $perm_value = (int)\substr($perm_value, 1);
                }

                foreach ($group_ids as $groupId) {
                    if ($groupId == $perm_value) {
                        $check = true;
                    }
                }

                break;

            case 'user':
                if ((int)$perm_value == $User->getId()) {
                    $check = true;
                }
                break;

            case 'users':
                $uids = \explode(',', $perm_value);

                foreach ($uids as $uid) {
                    if ((int)$uid == $User->getId()) {
                        $check = true;
                    }
                }
                break;

            case 'groups':
            case 'users_and_groups':
                // groups ids from the user
                $group_ids = $User->getGroups(false);

                if (!\is_array($group_ids)) {
                    $group_ids = \explode(',', $group_ids);
                }


                $user_group_ids = [];

                foreach ($group_ids as $gid) {
                    $user_group_ids[$gid] = true;
                }

                $ids = \explode(',', $perm_value);

                foreach ($ids as $id) {
                    $real_id = $id;
                    $type    = 'g';

                    if (\strpos($id, 'g') !== false
                        || \strpos($id, 'u') !== false
                    ) {
                        $real_id = (int)\substr($id, 1);
                        $type    = \substr($id, 0, 1);
                    }

                    switch ($type) {
                        case 'u':
                            if ($real_id == $User->getId()) {
                                $check = true;
                            }
                            break;

                        case 'g':
                            if (isset($user_group_ids[$real_id])) {
                                $check = true;
                            }
                            break;
                    }
                }
                break;
        }

        if ($check) {
            return true;
        }

        throw new QUI\Permissions\Exception(
            QUI::getLocale()->get(
                'quiqqer/system',
                'exception.no.permission'
            ),
            403
        );
    }

    /**
     * Prüft ob der Benutzer ein SuperUser ist
     *
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @throws \QUI\Permissions\Exception
     * @throws \QUI\Exception
     */
    public static function checkSU($User = false)
    {
        $UserToCheck = false;

        if (!$User) {
            $UserToCheck = self::getUser();
        }

        if ($UserToCheck) {
            self::checkUser($User);
        } else {
            self::checkUser();
        }

        if (!self::isSU($UserToCheck)) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                403
            );
        }
    }

    /**
     * Prüft ob der Benutzer auch ein Benutzer ist
     *
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @throws \QUI\Permissions\Exception
     * @throws \QUI\Exception
     */
    public static function checkUser($User = false)
    {
        $UserToCheck = $User;

        if (!$User) {
            $UserToCheck = self::getUser();
        }

        if (\get_class($UserToCheck) !== 'QUI\\Users\\User') {
            if (!$User) {
                QUI::getUsers()->checkUserSession();
            }

            // if no exception throws
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permission.session.expired'
                ),
                401
            );
        }
    }

    /**
     * Checks if the permission is set
     *
     * @param string $perm
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @return boolean
     */
    public static function existsPermission($perm, $User = false)
    {
        if (!$User) {
            $User = self::getUser();
        }

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getPermissions($User);

        // first check user permission
        if (isset($permissions[$perm])) {
            return true;
        }

        $groups = $User->getGroups();

        foreach ($groups as $Group) {
            $permissions = $Manager->getPermissions($Group);

            if (isset($permissions[$perm])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sites
     */

    /**
     * Add an user to the permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param string $permission - name of the permission
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @return bool
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public static function addUserToSitePermission(User $User, $Site, $permission, $EditUser = false)
    {
        if (!QUI\Projects\Site\Utils::isSiteObject($Site)) {
            return false;
        }

        /* @var $Site \QUI\Projects\Site */
        $Site->checkPermission('quiqqer.projects.site.edit', $EditUser);

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions($Site);

        if (!isset($permissions[$permission])) {
            return false;
        }

        $permList = [];
        $user     = 'u'.$User->getId();

        if (!empty($permissions[$permission])) {
            $permList = \explode(',', \trim($permissions[$permission], ' ,'));
        }

        $flip = \array_flip($permList);

        // user is in the permissions
        if (isset($flip[$user])) {
            return true;
        }

        $permList[] = $user;

        $Manager->setSitePermissions(
            $Site,
            [$permission => \implode(',', $permList)],
            $EditUser
        );

        return true;
    }

    /**
     * Add a group to the permission
     *
     * @param \QUI\Groups\Group $Group
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param string $permission - name of the permission
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @return bool
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public static function addGroupToSitePermission(Group $Group, $Site, $permission, $EditUser)
    {
        if (!QUI\Projects\Site\Utils::isSiteObject($Site)) {
            return false;
        }

        /* @var $Site \QUI\Projects\Site */
        $Site->checkPermission('quiqqer.projects.site.edit', $EditUser);

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions($Site);

        if (!isset($permissions[$permission])) {
            return false;
        }

        $permList = [];
        $group    = 'g'.$Group->getId();

        if (!empty($permissions[$permission])) {
            $permList = \explode(',', \trim($permissions[$permission], ' ,'));
        }

        $flip = \array_flip($permList);

        // user is in the permissions
        if (isset($flip[$group])) {
            return true;
        }

        $permList[] = $group;


        $Manager->setSitePermissions(
            $Site,
            [$permission => \implode(',', $permList)],
            $EditUser
        );

        return true;
    }

    /**
     * Checks if the User have the permission of the Site
     *
     * @param string $perm
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @return boolean
     *
     * @throws \QUI\Permissions\Exception
     */
    public static function checkSitePermission($perm, $Site, $User = false)
    {
        if (!$User) {
            $User = self::getUser();
        }

        if ($User->isSU()) {
            return true;
        }

        if (QUI::getUsers()->isSystemUser($User)) {
            return true;
        }


        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions($Site);

        // default site rights, view, edit, del, new: has their own special checks
        // with project and site checks
        switch ($perm) {
            case 'quiqqer.projects.site.view':
            case 'quiqqer.projects.sites.view':
                try {
                    return self::checkPermissionList(
                        $permissions,
                        'quiqqer.projects.site.view',
                        $User
                    );
                } catch (QUI\Permissions\Exception $Exception) {
                    // if site permissions are not empty do not check user permissions
                    if (!empty($permissions['quiqqer.projects.site.view'])) {
                        throw $Exception;
                    }

                    return self::checkPermission(
                        'quiqqer.projects.sites.view',
                        $User
                    );
                }
                break;
            case 'quiqqer.projects.site.edit':
            case 'quiqqer.projects.sites.edit':
                try {
                    return self::checkPermissionList(
                        $permissions,
                        'quiqqer.projects.site.edit',
                        $User
                    );
                } catch (QUI\Permissions\Exception $Exception) {
                    // if site permissions are not empty do not check user permissions
                    if (!empty($permissions['quiqqer.projects.site.edit'])) {
                        throw $Exception;
                    }

                    return self::checkPermission(
                        'quiqqer.projects.sites.edit',
                        $User
                    );
                }
                break;
            case 'quiqqer.projects.site.del':
            case 'quiqqer.projects.sites.del':
                try {
                    return self::checkPermissionList(
                        $permissions,
                        'quiqqer.projects.site.del',
                        $User
                    );
                } catch (QUI\Permissions\Exception $Exception) {
                    // if site permissions are not empty do not check user permissions
                    if (!empty($permissions['quiqqer.projects.site.del'])) {
                        throw $Exception;
                    }

                    return self::checkPermission(
                        'quiqqer.projects.sites.del',
                        $User
                    );
                }
                break;
            case 'quiqqer.projects.site.new':
            case 'quiqqer.projects.sites.new':
                try {
                    return self::checkPermissionList(
                        $permissions,
                        'quiqqer.projects.site.new',
                        $User
                    );
                } catch (QUI\Permissions\Exception $Exception) {
                    // if site permissions are not empty do not check user permissions
                    if (!empty($permissions['quiqqer.projects.site.new'])) {
                        throw $Exception;
                    }

                    return self::checkPermission(
                        'quiqqer.projects.sites.new',
                        $User
                    );
                }
        }

        return self::checkPermissionList($permissions, $perm, $User);
    }

    /**
     * Checks if the permission exists in the Site
     *
     * @param string $perm
     * @param \QUI\Projects\Site\|\QUI\Projects\Site\Edit $Site
     *
     * @return bool
     */
    public static function existsSitePermission($perm, $Site)
    {
        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions($Site);

        return isset($permissions[$perm]) ? true : false;
    }

    /**
     * Return the Site Permission
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param string $perm
     *
     * @return mixed|boolean
     */
    public static function getSitePermission($Site, $perm)
    {
        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions($Site);

        return isset($permissions[$perm]) ? $permissions[$perm] : false;
    }

    /**
     * has the User the permission at the site?
     *
     * @param string $perm
     * @param \QUI\Projects\Site $Site
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @return bool
     */
    public static function hasSitePermission($perm, $Site, $User = false)
    {
        try {
            return self::checkSitePermission($perm, $Site, $User);
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Remove a group from the permission
     *
     * @param Group $Group
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param string $permission
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @return bool
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public static function removeGroupFromSitePermission(
        Group $Group,
        $Site,
        $permission,
        $EditUser = false
    ) {
        if (!QUI\Projects\Site\Utils::isSiteObject($Site)) {
            return false;
        }

        /* @var $Site \QUI\Projects\Site */
        $Site->checkPermission('quiqqer.projects.site.edit', $EditUser);

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions($Site);

        if (!isset($permissions[$permission])) {
            return false;
        }

        $permList = [];
        $group    = 'g'.$Group->getId();

        if (!empty($permissions[$permission])) {
            $permList = \explode(',', \trim($permissions[$permission], ' ,'));
        }

        $flip = \array_flip($permList);

        // user is in the permissions, than unset it
        if (isset($flip[$group])) {
            unset($flip[$group]);
        }

        $permList = \array_flip($flip);


        $Manager->setSitePermissions(
            $Site,
            [$permission => \implode(',', $permList)],
            $EditUser
        );

        return true;
    }

    /**
     * Remove an user from the permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param string $permission
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @return bool
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public static function removeUserFromSitePermission(User $User, $Site, $permission, $EditUser = false)
    {
        if (!QUI\Projects\Site\Utils::isSiteObject($Site)) {
            return false;
        }

        /* @var $Site \QUI\Projects\Site */
        $Site->checkPermission('quiqqer.projects.site.edit', $EditUser);

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions($Site);

        if (!isset($permissions[$permission])) {
            return false;
        }

        $permList = [];
        $user     = 'u'.$User->getId();

        if (!empty($permissions[$permission])) {
            $permList = \explode(',', \trim($permissions[$permission], ' ,'));
        }

        $flip = \array_flip($permList);

        // user is in the permissions, than unset it
        if (isset($flip[$user])) {
            unset($flip[$user]);
        }

        $permList = \array_flip($flip);


        $Manager->setSitePermissions(
            $Site,
            [$permission => \implode(',', $permList)],
            $EditUser
        );

        return true;
    }


    /**
     * Projects
     */

    /**
     * Add an user to the Project permission
     *
     * @param Group $Group
     * @param \QUI\Projects\Project $Project
     * @param string $permission
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @return bool
     *
     * @throws QUI\Permissions\Exception
     */
    public static function addGroupToProjectPermission(
        Group $Group,
        Project $Project,
        $permission,
        $EditUser = false
    ) {
        self::checkProjectPermission('quiqqer.projects.edit', $Project, $EditUser);

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions($Project);

        if (!isset($permissions[$permission])) {
            return false;
        }

        $permList = [];
        $groups   = 'g'.$Group->getId();

        if (!empty($permissions[$permission])) {
            $permList = \explode(',', \trim($permissions[$permission], ' ,'));
        }

        $flip = \array_flip($permList);

        // user is in the permissions
        if (isset($flip[$groups])) {
            return true;
        }

        $permList[] = $groups;

        $Manager->setProjectPermissions(
            $Project,
            [$permission => \implode(',', $permList)],
            $EditUser
        );

        return true;
    }

    /**
     * Add an user to the Project permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Project $Project
     * @param string $permission - name of the
     * @param boolean|\QUI\Users\User $EditUser
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public static function addUserToProjectPermission(
        User $User,
        Project $Project,
        $permission,
        $EditUser = false
    ) {
        self::checkProjectPermission('quiqqer.projects.edit', $Project, $EditUser);

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions($Project);

        if (!isset($permissions[$permission])) {
            return false;
        }

        $permList = [];
        $user     = 'u'.$User->getId();

        if (!empty($permissions[$permission])) {
            $permList = \explode(',', \trim($permissions[$permission], ' ,'));
        }

        $flip = \array_flip($permList);

        // user is in the permissions
        if (isset($flip[$user])) {
            return true;
        }

        $permList[] = $user;

        $Manager->setProjectPermissions(
            $Project,
            [$permission => \implode(',', $permList)],
            $EditUser
        );

        return true;
    }

    /**
     * Checks if the User have the permission of the Project
     *
     * @param string $perm
     * @param Project $Project
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @return bool
     *
     * @throws QUI\Permissions\Exception
     */
    public static function checkProjectPermission(
        $perm,
        Project $Project,
        $User = false
    ) {
        if (!$User) {
            $User = self::getUser();
        }

        if ($User->isSU()) {
            return true;
        }

        if (QUI::getUsers()->isSystemUser($User)) {
            return true;
        }

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions($Project);


        // default project rights, edit, destroy, setconfig, editCustomCSS: has their own special checks
        // with project and site checks
        switch ($perm) {
            case 'quiqqer.projects.edit':
            case 'quiqqer.project.edit':
                try {
                    return self::checkPermissionList(
                        $permissions,
                        'quiqqer.project.edit',
                        $User
                    );
                } catch (QUI\Permissions\Exception $Exception) {
                    return self::checkPermission(
                        'quiqqer.projects.edit',
                        $User
                    );
                }
                break;
            case 'quiqqer.projects.destroy':
            case 'quiqqer.project.destroy':
                try {
                    return self::checkPermissionList(
                        $permissions,
                        'quiqqer.project.destroy',
                        $User
                    );
                } catch (QUI\Permissions\Exception $Exception) {
                    return self::checkPermission(
                        'quiqqer.projects.destroy',
                        $User
                    );
                }
                break;
            case 'quiqqer.projects.setconfig':
            case 'quiqqer.project.setconfig':
                try {
                    return self::checkPermissionList(
                        $permissions,
                        'quiqqer.project.setconfig',
                        $User
                    );
                } catch (QUI\Permissions\Exception $Exception) {
                    return self::checkPermission(
                        'quiqqer.projects.setconfig',
                        $User
                    );
                }
                break;
            case 'quiqqer.projects.editCustomCSS':
            case 'quiqqer.project.editCustomCSS':
                try {
                    return self::checkPermissionList(
                        $permissions,
                        'quiqqer.project.editCustomCSS',
                        $User
                    );
                } catch (QUI\Permissions\Exception $Exception) {
                    return self::checkPermission(
                        'quiqqer.projects.editCustomCSS',
                        $User
                    );
                }
        }

        return self::checkPermissionList($permissions, $perm, $User);
    }

    /**
     * Remove an user from the project permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Project $Project
     * @param string $permission - name of the permission
     *
     * @throws QUI\Permissions\Exception
     */
    public static function removeUserFromProjectPermission(
        User $User,
        Project $Project,
        $permission
    ) {
        self::checkProjectPermission('quiqqer.projects.edit', $Project);

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions($Project);

        if (!isset($permissions[$permission])) {
            return;
        }

        $permList = [];
        $user     = 'u'.$User->getId();

        if (!empty($permissions[$permission])) {
            $permList = \explode(',', \trim($permissions[$permission], ' ,'));
        }

        $flip = \array_flip($permList);

        // user is in the permissions, than unset it
        if (isset($flip[$user])) {
            unset($flip[$user]);
        }

        $permList = \array_flip($flip);


        $Manager->setProjectPermissions(
            $Project,
            [$permission => \implode(',', $permList)]
        );
    }

    /**
     * Remove a group from the project permission
     *
     * @param \QUI\Groups\Group $Group
     * @param \QUI\Projects\Project $Project
     * @param string $permission - name of the permission
     *
     * @return bool
     *
     * @throws QUI\Permissions\Exception
     */
    public static function removeGroupFromProjectPermission(
        Group $Group,
        Project $Project,
        $permission
    ) {
        self::checkProjectPermission('quiqqer.projects.edit', $Project);

        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions($Project);

        if (!isset($permissions[$permission])) {
            return false;
        }

        $permList = [];
        $group    = 'g'.$Group->getId();

        if (!empty($permissions[$permission])) {
            $permList = \explode(',', \trim($permissions[$permission], ' ,'));
        }

        $flip = \array_flip($permList);

        // user is in the permissions, than unset it
        if (isset($flip[$group])) {
            unset($flip[$group]);
        }

        $permList = \array_flip($flip);


        $Manager->setProjectPermissions(
            $Project,
            [$permission => \implode(',', $permList)]
        );

        return true;
    }

    //region media permissions

    /**
     * has the User the permission for the media item?
     *
     * @param string $perm
     * @param \QUI\Projects\Media\Item $MediaItem
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @return bool
     */
    public static function hasMediaPermission($perm, $MediaItem, $User = false)
    {
        if (Media::useMediaPermissions() === false) {
            return true;
        }

        try {
            return self::checkMediaPermission($perm, $MediaItem, $User);
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Checks if the User have the permission of the Site
     *
     * @param string $perm
     * @param QUI\Projects\Media\Item $MediaItem
     * @param \QUI\Users\User|boolean $User - optional
     *
     * @return boolean
     *
     * @throws \QUI\Permissions\Exception
     */
    public static function checkMediaPermission($perm, $MediaItem, $User = false)
    {
        if (Media::useMediaPermissions() === false) {
            return true;
        }

        if (!$User) {
            $User = self::getUser();
        }

        if ($User->isSU()) {
            return true;
        }

        if (QUI::getUsers()->isSystemUser($User)) {
            return true;
        }


        $Manager     = QUI::getPermissionManager();
        $permissions = $Manager->getMediaPermissions($MediaItem);

        return self::checkPermissionList($permissions, $perm, $User);
    }
}
