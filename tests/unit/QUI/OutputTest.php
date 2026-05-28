<?php

namespace QUI;

use PHPUnit\Framework\TestCase;

class OutputTest extends TestCase
{
    public function testParseWithAbsoluteUrlsConvertsImageSrcsetUrlsWithoutPicture(): void
    {
        $Output = new Output();
        $Output->setSetting('use-absolute-urls', true);

        $host = trim(HOST, '/');
        $html = '<img src="/media/cache/image.jpg" ' .
            'srcset="/media/cache/image-320.webp 320w, media/cache/image-640.webp 640w, ' .
            'https://cdn.example.com/image-960.webp 960w, //cdn.example.com/image-1280.webp 1280w" ' .
            'alt="Test">';

        $this->assertSame(
            '<img src="' . $host . '/media/cache/image.jpg" ' .
            'srcset="' . $host . '/media/cache/image-320.webp 320w, ' .
            $host . '/media/cache/image-640.webp 640w, ' .
            'https://cdn.example.com/image-960.webp 960w, ' .
            '//cdn.example.com/image-1280.webp 1280w" ' .
            'alt="Test">',
            $Output->parse($html)
        );
    }
}
