<?php

declare(strict_types=1);

namespace QUITests\Projects\Site;

use PHPUnit\Framework\TestCase;
use QUI\Interfaces\Projects\Site as SiteInterface;
use QUI\Projects\Site\Virtual;

class VirtualUrlTest extends TestCase
{
    public function testRewrittenUrlSupportsQueryParametersThroughInterface(): void
    {
        $Site = $this->createVirtualSite('/tags/example.html');
        $getParams = [
            'checkout' => '1',
            'return' => 'basket overview'
        ];
        $expected = '/tags/example.html?checkout=1&return=basket+overview';

        self::assertSame($expected, $Site->getUrl([], $getParams));
        self::assertSame($expected, $Site->getUrlRewritten([], $getParams));
    }

    public function testQueryParametersAreAppendedBeforeFragment(): void
    {
        $Site = $this->createVirtualSite('/tags/example.html?tag=example#results');

        self::assertSame(
            '/tags/example.html?tag=example&checkout=1#results',
            $Site->getUrlRewritten([], ['checkout' => '1'])
        );
    }

    private function createVirtualSite(string $url): SiteInterface
    {
        return new Virtual([
            'id' => 123,
            'name' => 'example',
            'url' => $url,
            'title' => 'Example'
        ]);
    }
}
