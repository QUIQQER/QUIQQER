<?php

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Class Cleanup
 * @package QUI\System\Console\Tools
 */
class Cleanup extends QUI\System\Console\Tool
{
    /**
     * Cleanup constructor.
     */
    public function __construct()
    {
        $this->systemTool = true;

        $this->setName('cleanup')
            ->setDescription('Cleans the system. No cache files are deleted, only files that are not needed while the system is running are deleted');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn('Start cleanup ...');

        $this->writeLn('- Clear composer ');
        self::clearComposer();
        $this->write('[OK]');

        $this->writeLn('- Clear temp ');
        QUI::getTemp()->clear();
        $this->write('[OK]');

        $this->writeLn('- Purge Cache ');
        QUI\Cache\Manager::purge();
        $this->write('[OK]');

        $this->writeLn('- Purge Sessions ');
        QUI\Cron\QuiqqerCrons::clearSessions();
        $this->write('[OK]');

        $this->writeLn();
        $this->writeLn('The system is now clean 👍');
        $this->writeLn();
    }

    /**
     * Clear the composer
     */
    public static function clearComposer()
    {
        $repoDir = VAR_DIR.'composer/repo/';
        $repos   = QUI\Utils\System\File::readDir($repoDir);

        $time = \time() - 2592000; // older than a month

        foreach ($repos as $repo) {
            $files = QUI\Utils\System\File::readDir($repoDir.$repo);

            foreach ($files as $file) {
                $repoFile = $repoDir.$repo.'/'.$file;

                if (!\is_file($repoFile)) {
                    continue;
                }

                $fTime = filemtime($repoFile);

                if ($time > $fTime) {
                    \unlink($repoFile);
                }
            }
        }
    }
}
