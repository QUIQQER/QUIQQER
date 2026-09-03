<?php

declare(strict_types=1);

namespace QUITests\QUI\System\Console\Tools;

use PHPUnit\Framework\TestCase;
use QUI\System\Console\Tools\Frankenphp;
use QUI\System\Console\Tools\Htaccess;
use QUI\System\Console\Tools\Nginx;
use ReflectionMethod;

use function strpos;

class SvgMediaCacheRoutingTest extends TestCase
{
    public function testApacheRoutesExistingSvgMediaCacheThroughFrontController(): void
    {
        $generateBody = new ReflectionMethod(Htaccess::class, 'generateBody');
        $config = $generateBody->invoke(new Htaccess());

        $svgRoute = strpos($config, 'media/cache/.*\.(svgz?|html?|xhtml|xml)$');
        $existingFileShortcut = strpos($config, 'REQUEST_FILENAME} !-f');

        self::assertIsInt($svgRoute);
        self::assertIsInt($existingFileShortcut);
        self::assertLessThan($existingFileShortcut, $svgRoute);
    }

    public function testNginxRoutesExistingSvgMediaCacheThroughFrontController(): void
    {
        $getConfig = new ReflectionMethod(Nginx::class, 'getConfig');
        $config = $getConfig->invoke(new Nginx());

        $svgRoute = strpos($config, 'try_files $uri.__quiqqer_svg_sanitizer__ @quiqqer_front_controller;');
        $genericSvgRoute = strpos($config, '\.(eot|svg|ttf)$');

        self::assertIsInt($svgRoute);
        self::assertIsInt($genericSvgRoute);
        self::assertLessThan($genericSvgRoute, $svgRoute);
    }

    public function testFrankenphpRoutesExistingSvgMediaCacheThroughFrontController(): void
    {
        $getConfig = new ReflectionMethod(Frankenphp::class, 'getConfig');
        $config = $getConfig->invoke(new Frankenphp());

        $svgRoute = strpos($config, 'handle @quiqqer_media_cache_active_document');
        $genericMediaCacheRoute = strpos($config, 'handle /media/cache/*');

        self::assertIsInt($svgRoute);
        self::assertIsInt($genericMediaCacheRoute);
        self::assertLessThan($genericMediaCacheRoute, $svgRoute);
    }
}
