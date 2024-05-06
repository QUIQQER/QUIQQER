<?php

namespace QUI\System\Console\Tools;

use QUI;
use QUI\Exception;

/**
 * Update-Server Console Manager
 *
 * @author  www.pcsg.de (Jan Wennrich)
 * @licence For copyright and license information, please view the /README.md
 */
class UpdateServer extends QUI\System\Console\Tool
{
    public function __construct()
    {
        $this->setName('quiqqer:update-server')
            ->setDescription(QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.description'))
            ->addArgument(
                'add',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.add.description'),
                'a',
                true
            )
            ->addArgument(
                'remove',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.remove.description'),
                'r',
                true
            );

        $this->addExample('./console quiqqer:update-server --add=git@dev.quiqqer.com:quiqqer/quiqqer.git --type=vcs');
        $this->addExample('./console quiqqer:update-server --remove=git@dev.quiqqer.com:quiqqer/quiqqer.git');
    }

    public function execute(): void
    {
        if ($this->getArgument('add')) {
            $this->addServer();
            return;
        }

        if ($this->getArgument('remove')) {
            $this->removeServer();
            return;
        }

        $this->showHelp();
    }

    protected function addServer()
    {
        $server = $this->getArgument('add');

        // Equal to one means that the argument was passed but has no value (--add instead of --add=text.example)
        if (!$server || $server == 1) {
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.add.server.missing'),
                'red'
            );

            return;
        }

        $type = $this->getArgument('type');

        if (!$type) {
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.add.type.missing'),
                'yellow'
            );

            $type = 'vcs';
        }

        QUI::getPackageManager()->addServer($server, ['type' => $type]);

        $this->writeLn(
            QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.add.success'),
            'green'
        );
    }

    protected function removeServer()
    {
        $server = $this->getArgument('remove');

        // Equal to one means that the argument was passed but has no value (--add instead of --add=text.example)
        if (!$server || $server == 1) {
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.remove.server.missing'),
                'red'
            );

            return;
        }

        QUI::getPackageManager()->removeServer($server);

        $this->writeLn(
            QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.remove.success'),
            'green'
        );
    }

    protected function showHelp()
    {
        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.updateserver.help'));
    }
}
