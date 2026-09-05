<?php

declare(strict_types=1);

namespace QUI\Projects;

final class ProjectAuthorizationFixtureHarness extends ProjectAuthorizationTestCase
{
    public function open(): void
    {
        parent::setUp();
    }

    public function close(): void
    {
        parent::tearDown();
    }
}
