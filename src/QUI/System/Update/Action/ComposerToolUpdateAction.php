<?php

namespace QUI\System\Update\Action;

use QUI\Composer\Phar\ComposerPharManager;
use QUI\Composer\Phar\HttpComposerPharDownloader;
use QUI\System\Update\RunActionInterface;
use QUI\System\Update\RunActionResult;
use QUI\System\Update\RunState;

use function class_exists;

use const VAR_DIR;

class ComposerToolUpdateAction implements RunActionInterface
{
    public function __construct(private readonly ?ComposerPharManager $manager = null)
    {
    }

    public function execute(RunState $state): RunActionResult
    {
        $Manager = $this->manager ?? $this->createDefaultManager();

        if ($Manager) {
            if (!$Manager->ensure()) {
                $Manager->update();
            }
        }

        return RunActionResult::restartRequired();
    }

    private function createDefaultManager(): ?ComposerPharManager
    {
        if (
            !class_exists(ComposerPharManager::class)
            || !class_exists(HttpComposerPharDownloader::class)
        ) {
            return null;
        }

        return new ComposerPharManager(
            VAR_DIR . 'composer/composer.phar',
            new HttpComposerPharDownloader()
        );
    }
}
