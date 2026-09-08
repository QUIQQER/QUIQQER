<?php

declare(strict_types=1);

namespace QUITests\System;

use PHPUnit\Framework\TestCase;
use QUI\Rewrite;
use QUI\System\VhostManager;
use ReflectionProperty;

class VhostManagerTest extends TestCase
{
    public function testHostLookupPrefersExplicitHostsOverAliasesAndWildcards(): void
    {
        $hosts = [
            '*.example.com' => [],
            'other.example.com' => ['httpshost' => 'www.example.com'],
            'example.com' => [],
            'www.example.com' => []
        ];
        self::assertSame('www.example.com', VhostManager::findVhost('WWW.EXAMPLE.COM:8080', $hosts));
        self::assertSame('example.com', VhostManager::findVhost('www.example.com', ['example.com' => []]));
        self::assertSame('www.example.com', VhostManager::findVhost('example.com', ['www.example.com' => []]));
        self::assertSame('*.example.com', VhostManager::findVhost('shop.example.com', $hosts));
        self::assertNull(VhostManager::findVhost('unknown.test', $hosts));
        self::assertNull(VhostManager::findVhost('www.127.0.0.1', ['127.0.0.1' => []]));
        self::assertSame(
            'example.com:8080',
            VhostManager::findVhost('example.com:8080', ['example.com' => [], 'example.com:8080' => []])
        );
    }

    public function testWwwVariantLoadsTheCorrectProjectInsteadOfTheFirstVhost(): void
    {
        $previousHost = $_SERVER['HTTP_HOST'] ?? null;

        try {
            $_SERVER['HTTP_HOST'] = 'www.second.example:8080';
            $Rewrite = new Rewrite();
            (new ReflectionProperty(Rewrite::class, 'vhosts'))->setValue($Rewrite, [
                'first.example' => ['project' => 'first'],
                'second.example' => ['project' => 'second', 'lang' => 'de', 'wwwRedirect' => 'none']
            ]);
            self::assertSame('second', $Rewrite->getCurrentVhostData()['project']);
            self::assertSame('de', $Rewrite->getCurrentVhostData()['lang']);
        } finally {
            if ($previousHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
        }
    }

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
