<?php

namespace QUI\Editor;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ManagerTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testAppendWysiwygSettingsFromXmlMergesChildAndParentCss(): void
    {
        $childSettings = $this->createSettingsXml(
            '<wysiwyg id="child-body" class="child-class"><css src="https://example.org/child.css" /></wysiwyg>'
        );
        $parentSettings = $this->createSettingsXml(
            '<wysiwyg id="parent-body" class="parent-class"><css src="https://example.org/parent.css" /></wysiwyg>'
        );

        $css = [];
        $styles = [];
        $bodyId = false;
        $bodyClass = false;

        $method = new ReflectionMethod(Manager::class, 'appendWysiwygSettingsFromXml');
        $method->invokeArgs(null, [$childSettings, &$css, &$styles, &$bodyId, &$bodyClass]);
        $method->invokeArgs(null, [$parentSettings, &$css, &$styles, &$bodyId, &$bodyClass]);

        $this->assertSame([
            'https://example.org/child.css',
            'https://example.org/parent.css'
        ], $css);
        $this->assertSame('child-body', $bodyId);
        $this->assertSame('child-class', $bodyClass);
    }

    private function createSettingsXml(string $wysiwyg): string
    {
        $file = tempnam(sys_get_temp_dir(), 'quiqqer-editor-settings-');
        unlink($file);
        $file .= '.xml';

        file_put_contents($file, <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<quiqqer>
    {$wysiwyg}
</quiqqer>
XML);

        $this->temporaryFiles[] = $file;

        return $file;
    }
}
