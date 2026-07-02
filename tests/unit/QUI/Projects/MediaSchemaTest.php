<?php

namespace QUI\Projects;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MediaSchemaTest extends TestCase
{
    public function testExternalMediaColumnIsQuotedForMysqlMariaDbAndPostgresql(): void
    {
        foreach ($this->schemaPlatforms() as $Platform) {
            $Table = new Table('QUIQQER_media');
            $this->invokeAddMediaColumns($Table);

            $sql = implode("\n", $Platform->getCreateTableSQL($Table));

            $this->assertStringContainsString(
                $Platform->quoteSingleIdentifier('external') . ' ',
                $sql,
                $Platform::class
            );
            $this->assertStringNotContainsString(' external ', $sql, $Platform::class);
            $this->assertTrue($Table->hasColumn('external'), $Platform::class);
        }
    }

    /**
     * @return list<AbstractPlatform>
     */
    private function schemaPlatforms(): array
    {
        return [
            new MySQLPlatform(),
            new MariaDBPlatform(),
            new PostgreSQLPlatform()
        ];
    }

    private function invokeAddMediaColumns(Table $Table): void
    {
        $Reflection = new ReflectionClass(Media::class);
        $Method = $Reflection->getMethod('addMediaColumns');
        $Method->invoke(null, $Table);
    }
}
