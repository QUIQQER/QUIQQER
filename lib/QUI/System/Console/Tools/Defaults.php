<?php

/**
 * \QUI\System\Console\Tools\Defaults
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 *
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Defaults extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:defaults')
            ->setDescription('Set the group / user settings & permissions to default settings');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $Groups = QUI::getGroups();

        // Guest
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $Groups->Table(),
            'where' => array(
                'id' => 0
            )
        ));

        if (!isset($result[0])) {
            $this->write('Guest Group does not exist.', 'red');

            QUI::getDataBase()->insert($Groups->Table(), array(
                'id' => 0
            ));

            $this->write(' Guest Group was created.', 'green');
            $this->writeLn();

        } else {
            $this->writeLn('- Guest exists', 'green');
        }


        // Nobody
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $Groups->Table(),
            'where' => array(
                'id' => 1
            )
        ));

        if (!isset($result[0])) {
            $this->write('Nobody Group does not exist...', 'red');

            QUI::getDataBase()->insert($Groups->Table(), array(
                'id' => 1
            ));

            $this->write(' Nobody Group was created.', 'green');
            $this->writeLn();
        } else {
            $this->writeLn('- Nobody exists', 'green');
        }

        $Groups->get(0)->save();
        $Groups->get(1)->save();


        // set all users to everyone
        $users = QUI::getUsers()->getAllUsers();

        foreach ($users as $uid) {
            try {
                $User = QUI::getUsers()->get($uid['id']);
                $User->save();
            } catch (QUI\Exception $Exception) {

            }
        }


        // set default root
        $Root = $Groups->firstChild();

        QUI::getPermissionManager()->setPermissions($Root, array(
            "quiqqer.admin.groups.edit"              => true,
            "quiqqer.admin.groups.view"              => true,
            "quiqqer.cron.execute"                   => true,
            "quiqqer.cron.add"                       => true,
            "quiqqer.projects.site.set_permissions"  => true,
            "quiqqer.projects.edit"                  => true,
            "quiqqer.projects.destroy"               => true,
            "quiqqer.projects.setconfig"             => true,
            "quiqqer.projects.editCustomCSS"         => true,
            "quiqqer.cron.activate"                  => true,
            "quiqqer.admin.users.view"               => true,
            "quiqqer.admin.users.edit"               => true,
            "quiqqer.cron.delete"                    => true,
            "quiqqer.cron.edit"                      => true,
            "quiqqer.system.cache"                   => true,
            "quiqqer.system.permissions"             => true,
            "quiqqer.system.update"                  => true,
            "quiqqer.admin"                          => true,
            "quiqqer.su"                             => true,
            "quiqqer.cron.deactivate"                => true,
            "quiqqer.editors.toolbar.delete"         => true,
            "quiqqer.projects.create"                => true,
            "quiqqer.system.console"                 => true,
            "quiqqer.watcher.readlog"                => true,
            "quiqqer.watcher.clearlog"               => true,
            "quiqqer.projects.sites.set_permissions" => true,
            "quiqqer.projects.sites.view"            => true,
            "quiqqer.projects.sites.edit"            => true,
            "quiqqer.projects.sites.del"             => true,
            "quiqqer.projects.sites.new"             => true
        ));


        // default permission for everyone
        try {
            $Everyone = new QUI\Groups\Everyone();

            QUI::getPermissionManager()->setPermissions($Everyone, array(
                'quiqqer.projects.sites.view' => true
            ));

            $this->writeLn('- Permissions for Everyone were set', 'green');

        } catch (QUI\Exception $Exception) {
            $this->writeLn('* ' . $Exception->getMessage(), 'red');
        }


        // default permission for guest
        try {
            $Guest = new QUI\Groups\Guest();

            QUI::getPermissionManager()->setPermissions($Guest, array(
                'quiqqer.projects.sites.view' => true
            ));

            $this->writeLn('- Permissions for Guest were set', 'green');

        } catch (QUI\Exception $Exception) {
            $this->writeLn('* ' . $Exception->getMessage(), 'red');
        }


        $this->resetColor();
        $this->writeLn('');

        QUI\Cache\Manager::clearAll();

        // start a complete setup
        // QUI::setup();
    }
}
