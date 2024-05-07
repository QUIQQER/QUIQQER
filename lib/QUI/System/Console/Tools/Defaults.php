<?php

/**
 * \QUI\System\Console\Tools\Defaults
 */

namespace QUI\System\Console\Tools;

use QUI;
use QUI\Exception;

/**
 *
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Defaults extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('quiqqer:defaults')
            ->setDescription('Set the group / user settings & permissions to default settings');
    }

    /**
     * (non-PHPdoc)
     *
     * @throws Exception
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        $Groups = QUI::getGroups();
        $Groups->setup();

        // set all users to everyone
        $users = QUI::getUsers()->getAllUsers();

        foreach ($users as $uid) {
            try {
                $User = QUI::getUsers()->get($uid['id']);
                $User->save();
            } catch (QUI\Exception) {
            }
        }


        // set default root
        $Root = $Groups->firstChild();

        QUI::getPermissionManager()->setPermissions($Root, [
            "quiqqer.admin.groups.edit" => true,
            "quiqqer.admin.groups.view" => true,
            "quiqqer.cron.execute" => true,
            "quiqqer.cron.add" => true,
            "quiqqer.projects.site.set_permissions" => true,
            "quiqqer.projects.edit" => true,
            "quiqqer.projects.destroy" => true,
            "quiqqer.projects.setconfig" => true,
            "quiqqer.projects.editCustomCSS" => true,
            "quiqqer.projects.editCustomJS" => true,
            "quiqqer.cron.activate" => true,
            "quiqqer.admin.users.view" => true,
            "quiqqer.admin.users.edit" => true,
            "quiqqer.cron.delete" => true,
            "quiqqer.cron.edit" => true,
            "quiqqer.system.cache" => true,
            "quiqqer.system.permissions" => true,
            "quiqqer.system.update" => true,
            "quiqqer.admin" => true,
            "quiqqer.su" => true,
            "quiqqer.cron.deactivate" => true,
            "quiqqer.editors.toolbar.delete" => true,
            "quiqqer.projects.create" => true,
            "quiqqer.system.console" => true,
            "quiqqer.watcher.readlog" => true,
            "quiqqer.watcher.clearlog" => true,
            "quiqqer.projects.sites.set_permissions" => true,
            "quiqqer.projects.sites.view" => true,
            "quiqqer.projects.sites.edit" => true,
            "quiqqer.projects.sites.del" => true,
            "quiqqer.projects.sites.new" => true
        ]);


        // default permission for everyone
        try {
            $Everyone = new QUI\Groups\Everyone();

            QUI::getPermissionManager()->setPermissions($Everyone, [
                'quiqqer.projects.sites.view' => true
            ]);

            $this->writeLn('- Permissions for Everyone were set', 'green');
        } catch (QUI\Exception $Exception) {
            $this->writeLn('* ' . $Exception->getMessage(), 'red');
        }


        // default permission for guest
        try {
            $Guest = new QUI\Groups\Guest();

            QUI::getPermissionManager()->setPermissions($Guest, [
                'quiqqer.projects.sites.view' => true
            ]);

            $this->writeLn('- Permissions for Guest were set', 'green');
        } catch (QUI\Exception $Exception) {
            $this->writeLn('* ' . $Exception->getMessage(), 'red');
        }


        $this->resetColor();
        $this->writeLn();

        QUI\Cache\Manager::clearCompleteQuiqqerCache();

        // start a complete setup
        // QUI::setup();
    }
}
