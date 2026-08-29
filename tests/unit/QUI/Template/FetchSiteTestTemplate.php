<?php

declare(strict_types=1);

namespace QUITests\Template;

use QUI\Interfaces\Projects\Site;
use QUI\Interfaces\Template\EngineInterface;
use QUI\Template;
use QUI\Utils\JsonLd;

final class FetchSiteTestTemplate extends Template
{
    public function __construct(
        private readonly EngineInterface $TestEngine,
        private readonly string $userDirectory
    ) {
        parent::__construct();
    }

    public function getEngine(bool $admin = false): EngineInterface
    {
        return $this->TestEngine;
    }

    protected function createDefaultJsonLd(Site $Site): JsonLd
    {
        return new JsonLd();
    }

    protected function getUserDirectory(): string
    {
        return $this->userDirectory;
    }
}
