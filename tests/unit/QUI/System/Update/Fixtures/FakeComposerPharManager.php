<?php

namespace QUI\System\Update\Fixtures;

use QUI\Composer\Phar\ComposerPharDownloaderInterface;
use QUI\Composer\Phar\ComposerPharManager;

class FakeComposerPharManager extends ComposerPharManager
{
    public int $ensureCalls = 0;

    public int $updateCalls = 0;

    public bool $ensureResult = false;

    public function __construct()
    {
        parent::__construct('/tmp/fake-composer.phar', new class implements ComposerPharDownloaderInterface {
            public function download(string $targetFile): void
            {
            }
        });
    }

    public function ensure(): bool
    {
        $this->ensureCalls++;

        return $this->ensureResult;
    }

    public function update(): bool
    {
        $this->updateCalls++;

        return true;
    }
}
