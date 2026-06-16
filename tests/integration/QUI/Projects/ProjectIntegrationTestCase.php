<?php

namespace QUI\Projects;

use PHPUnit\Framework\TestCase;
use Throwable;

abstract class ProjectIntegrationTestCase extends TestCase
{
    protected static function getTestProject(): Project
    {
        try {
            return ProjectTestHelper::getProject();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER project test fixture is not available: ' . $Exception->getMessage());
        }
    }

    protected static function getTestProjectName(): string
    {
        try {
            return ProjectTestHelper::getProjectName();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER project test fixture is not available: ' . $Exception->getMessage());
        }
    }
}
