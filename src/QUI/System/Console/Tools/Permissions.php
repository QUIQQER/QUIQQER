<?php

/**
 * \QUI\System\Console\Tools\Permissions
 */

namespace QUI\System\Console\Tools;

use Exception;
use League\CLImate\CLImate;
use QUI;

use function count;
use function explode;

/**
 * Permissions console tool
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Permissions extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->systemTool = true;

        $this->setName('quiqqer:permissions')
            ->setDescription('Permission explorer')
            ->addArgument(
                'help',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.permissions.help.description'),
                false,
                true
            )
            ->addArgument(
                'list',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.permissions.list.description'),
                false,
                true
            )
            ->addArgument(
                'user',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.permissions.user.description'),
                false,
                true
            )
            ->addArgument(
                'group',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.permissions.group.description'),
                false,
                true
            );
    }

    /**
     * (non-PHPdoc)
     *
     * @throws Exception
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        if ($this->getArgument('help')) {
            $this->showHelp();
        }

        if ($this->getArgument('list')) {
            $this->showList();

            return;
        }

        if ($this->getArgument('user')) {
            $this->showUser();

            return;
        }

        if ($this->getArgument('group')) {
            $this->showGroup();

            return;
        }

        $this->outputHelp();
    }

    /**
     * Prints the help
     *
     * @throws Exception
     */
    protected function showHelp(): never
    {
        $this->writeLn();

        $Climate = new CLImate();

        $Climate->arguments->add([
            'help' => [
                'longPrefix' => 'help',
                'description' => QUI::getLocale()->get('quiqqer/core', 'console.tool.permissions.help.description'),
                'noValue' => true
            ],
            'list' => [
                'longPrefix' => 'list',
                'description' => QUI::getLocale()->get('quiqqer/core', 'console.tool.permissions.list.description'),
                'noValue' => true
            ],
            'user' => [
                'longPrefix' => 'user',
                'description' => QUI::getLocale()->get('quiqqer/core', 'console.tool.permissions.user.description')
            ],
            'group' => [
                'longPrefix' => 'group',
                'description' => QUI::getLocale()->get('quiqqer/core', 'console.tool.permissions.group.description')
            ]
        ]);

        $Climate->usage([
            'quiqqer.php permissions'
        ]);

        exit;
    }

    /**
     * Shows a complete list from all available permissions
     */
    protected function showList(): void
    {
        $permissions = QUI::getPermissionManager()->getPermissionList();
        $data = [];

        foreach ($permissions as $permission => $perm) {
            if (!isset($perm['defaultValue'])) {
                $perm['defaultValue'] = '';
            }

            $title = explode(' ', $perm['title']);

            if (count($title) === 2) {
                $title = QUI::getLocale()->get($title[0], $title[1]);
            }

            $data[] = [
                'permission' => $permission,
                'type' => $perm['type'],
                'area' => $perm['area'],
                'title' => $title,
                'src' => $perm['src'],
                'defaultValue' => $perm['defaultValue'],
            ];
        }

        $this->writeLn();

        $Climate = new CLImate();
        $Climate->table($data);
    }

    /**
     * Shows a permission list from a specific user
     */
    protected function showUser(): void
    {
        $user = $this->getArgument('user');
        $needle = $this->getArgument('permission');

        try {
            $User = QUI::getUsers()->get($user);
        } catch (QUI\Exception $Exception) {
            $this->writeLn($Exception->getMessage(), 'red');
            $this->writeLn();
            exit;
        }

        $Manager = QUI::getPermissionManager();
        $userPermissions = $Manager->getCompletePermissionList($User);
        $permissions = $Manager->getPermissionList();

        $data = [];
        $groups = $User->getGroups();

        // helper
        $parsePermission = static function ($permission) use (
            $User,
            $Manager,
            $permissions,
            $userPermissions,
            $groups
        ) {
            $value = $userPermissions[$permission];
            $perm = $permissions[$permission];

            $title = explode(' ', $perm['title']);

            if (count($title) === 2) {
                $title = QUI::getLocale()->get($title[0], $title[1]);
            }

            if (!isset($perm['defaultValue'])) {
                $perm['defaultValue'] = '';
            }

            $result = [
                'permission' => $permission,
                'title' => $title,
                'value' => $value,
                'default' => $perm['defaultValue'],
                'used' => QUI\Permissions\Permission::hasPermission($permission, $User),
                'isSu' => $User->isSU()
            ];

            foreach ($groups as $Group) {
                $groupPermissions = $Manager->getPermissions($Group);

                if (isset($groupPermissions[$permission])) {
                    $result[$Group->getName()] = $groupPermissions[$permission];
                }
            }

            return $result;
        };


        $message = QUI::getLocale()->get('quiqqer/core', 'console.permissions.user', [
            'username' => $User->getUsername(),
            'user' => $User->getName(),
        ]);

        $this->writeLn();
        $this->writeLn($message, 'purple');
        $this->writeLn();

        // processing the data
        if (!empty($needle)) {
            $data[] = $parsePermission($needle);
        } else {
            foreach (array_keys($userPermissions) as $permission) {
                $data[] = $parsePermission($permission);
            }
        }

        $this->writeLn();

        $Climate = new CLImate();
        $Climate->table($data);
    }

    /**
     * Shows a permission list from a specific group
     */
    protected function showGroup(): void
    {
        $group = $this->getArgument('group');
        $needle = $this->getArgument('permission');

        try {
            $Group = QUI::getGroups()->get($group);
        } catch (QUI\Exception $Exception) {
            $this->writeLn($Exception->getMessage(), 'red');
            $this->writeLn();
            exit;
        }

        $data = [];
        $Manager = QUI::getPermissionManager();
        $groupPermissions = $Manager->getCompletePermissionList($Group);
        $permissions = $Manager->getPermissionList();

        $parsePermission = static function ($permission) use ($permissions, $groupPermissions): array {
            $value = $groupPermissions[$permission];
            $perm = $permissions[$permission];

            $title = explode(' ', $perm['title']);

            if (count($title) === 2) {
                $title = QUI::getLocale()->get($title[0], $title[1]);
            }

            if (!isset($perm['defaultValue'])) {
                $perm['defaultValue'] = '';
            }

            return [
                'permission' => $permission,
                'title' => $title,
                'value' => $value,
                'default' => $perm['defaultValue']
            ];
        };


        $message = QUI::getLocale()->get('quiqqer/core', 'console.permissions.group', [
            'group' => $Group->getName()
        ]);

        $this->writeLn();
        $this->writeLn($message, 'purple');
        $this->writeLn();

        // processing the data
        if (!empty($needle)) {
            $data[] = $parsePermission($needle);
        } else {
            foreach (array_keys($groupPermissions) as $permission) {
                $data[] = $parsePermission($permission);
            }
        }

        $this->writeLn();

        $Climate = new CLImate();
        $Climate->table($data);
    }
}
