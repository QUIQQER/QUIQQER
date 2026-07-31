<?php

declare(strict_types=1);

namespace QUITests\System;

use PHPUnit\Framework\TestCase;
use QUI\System\VhostManager;

class VhostManagerTest extends TestCase
{
    public function testRootLanguageRouteTakesPrecedence(): void
    {
        $config = [
            'www.example.eu' => [
                'project' => 'example',
                'lang' => 'en',
                'path_langs' => 'es'
            ],
            'www.example.es' => [
                'project' => 'example',
                'lang' => 'es',
                'path_langs' => ''
            ]
        ];

        self::assertSame(
            [
                'host' => 'www.example.es',
                'httpshost' => '',
                'path' => '',
                'project' => 'example',
                'lang' => 'es'
            ],
            VhostManager::resolveProjectLanguageRoute($config, 'example', 'es')
        );
    }

    public function testPathLanguageRouteUsesLanguagePrefix(): void
    {
        $config = [
            'www.example.eu' => [
                'project' => 'example',
                'lang' => 'en',
                'path_langs' => 'es, fr',
                'httpshost' => 'secure.example.eu'
            ],
            'www.example.de' => [
                'project' => 'example',
                'lang' => 'de',
                'path_langs' => ''
            ]
        ];

        self::assertSame(
            [
                'host' => 'www.example.eu',
                'httpshost' => 'secure.example.eu',
                'path' => 'es',
                'project' => 'example',
                'lang' => 'es'
            ],
            VhostManager::resolveProjectLanguageRoute($config, 'example', 'es')
        );
    }

    public function testLegacyLanguageHostAssignmentRemainsSupported(): void
    {
        $config = [
            'www.example.de' => [
                'project' => 'example',
                'lang' => 'de',
                'es' => 'https://www.example.eu/es/'
            ]
        ];

        self::assertSame(
            [
                'host' => 'www.example.eu',
                'httpshost' => '',
                'path' => 'es',
                'project' => 'example',
                'lang' => 'es'
            ],
            VhostManager::resolveProjectLanguageRoute($config, 'example', 'es')
        );
    }

    public function testPathLanguagesAreNormalized(): void
    {
        self::assertSame(
            ['es', 'fr'],
            VhostManager::parsePathLanguages(' es,fr,es,invalid, ')
        );
    }

    public function testUnknownLanguageRouteReturnsNull(): void
    {
        self::assertNull(
            VhostManager::resolveProjectLanguageRoute(
                [
                    'www.example.eu' => [
                        'project' => 'example',
                        'lang' => 'en',
                        'path_langs' => ''
                    ]
                ],
                'example',
                'es'
            )
        );
    }
}
