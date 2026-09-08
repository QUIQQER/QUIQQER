<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\TestCase;
use QUI\MCP\VHost\AbstractVHostTool;
use ReflectionMethod;

class VHostToolTest extends TestCase
{
    public function testWwwOverrideCanExplicitlyReturnToGlobal(): void
    {
        $Method = new ReflectionMethod(AbstractVHostTool::class, 'buildVHostData');
        $result = $Method->invoke(null, ['wwwRedirect' => 'none'], null, null, null, null, null, null, '');
        self::assertSame('', $result['wwwRedirect']);
    }

    public function testBuildVHostDataPreservesOmittedValues(): void
    {
        $buildVHostData = new ReflectionMethod(
            AbstractVHostTool::class,
            'buildVHostData'
        );

        self::assertSame(
            [
                'project' => 'example',
                'lang' => 'en',
                'path_langs' => 'es,fr',
                'template' => 'quiqqer/example',
                'error' => 'example,en,1',
                'httpshost' => 'secure.example.eu',
                'wwwRedirect' => 'none'
            ],
            $buildVHostData->invoke(
                null,
                [
                    'project' => 'example',
                    'lang' => 'en',
                    'path_langs' => 'es,fr',
                    'template' => 'quiqqer/example',
                    'error' => 'example,en,1',
                    'httpshost' => 'secure.example.eu',
                    'wwwRedirect' => 'none'
                ]
            )
        );
    }

    public function testBuildVHostDataNormalizesUpdatesAndCanClearPaths(): void
    {
        $buildVHostData = new ReflectionMethod(
            AbstractVHostTool::class,
            'buildVHostData'
        );

        self::assertSame(
            [
                'project' => 'example',
                'lang' => 'de',
                'path_langs' => '',
                'template' => '',
                'error' => '',
                'httpshost' => '',
                'wwwRedirect' => ''
            ],
            $buildVHostData->invoke(
                null,
                [
                    'project' => 'example',
                    'lang' => 'en',
                    'path_langs' => 'es'
                ],
                null,
                ' DE ',
                []
            )
        );
    }

    public function testParseVHostUsesPublicFieldNames(): void
    {
        $parseVHost = new ReflectionMethod(
            AbstractVHostTool::class,
            'parseVHost'
        );

        self::assertSame(
            [
                'host' => 'www.example.eu',
                'project' => 'example',
                'rootLanguage' => 'en',
                'pathLanguages' => ['es', 'fr'],
                'template' => 'quiqqer/example',
                'error' => '',
                'httpsHost' => 'secure.example.eu',
                'wwwRedirect' => 'www'
            ],
            $parseVHost->invoke(
                null,
                'www.example.eu',
                [
                    'project' => 'example',
                    'lang' => 'en',
                    'path_langs' => 'es,fr',
                    'template' => 'quiqqer/example',
                    'httpshost' => 'secure.example.eu',
                    'wwwRedirect' => 'www'
                ]
            )
        );
    }
}
