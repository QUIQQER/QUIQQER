<?php

declare(strict_types=1);

namespace QUITests\Template;

use PHPUnit\Framework\TestCase;
use QUI\Template;

final class PathSecurityTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/quiqqer-template-test-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/template', 0777, true);
        mkdir($this->root . '/template-parent', 0777, true);
        mkdir($this->root . '/template-evil', 0777, true);
        file_put_contents($this->root . '/template/index.php', '<?php /* harmless test fixture */');
        file_put_contents($this->root . '/template-parent/index.php', '<?php /* harmless test fixture */');
        file_put_contents($this->root . '/template/standard.php', '<?php /* harmless test fixture */');
        file_put_contents($this->root . '/template/blog.php', '<?php /* harmless test fixture */');
        file_put_contents($this->root . '/template-evil/blog.php', '<?php /* harmless test fixture */');
    }

    protected function tearDown(): void
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $files[] = $file->getPathname();
        }
        foreach ($files as $file) {
            is_dir($file) && !is_link($file) ? rmdir($file) : unlink($file);
        }
        rmdir($this->root);
    }

    public function testAllowedFilesAndTemplateFallbackRootsResolve(): void
    {
        $resolve = $this->resolver();

        self::assertSame(
            realpath($this->root . '/template/index.php'),
            $resolve($this->root . '/template', $this->root . '/template/index.php')
        );
        self::assertSame(
            realpath($this->root . '/template-parent/index.php'),
            $resolve($this->root . '/template-parent', $this->root . '/template-parent/index.php')
        );
        self::assertSame(
            realpath($this->root . '/template/standard.php'),
            $resolve($this->root . '/template', $this->root . '/template/standard.php')
        );
        self::assertSame(
            realpath($this->root . '/template/blog.php'),
            $resolve($this->root . '/template', $this->root . '/template/blog.php')
        );
    }

    public function testTraversalPrefixAndSymlinkOutsideRootAreRejected(): void
    {
        $resolve = $this->resolver();
        $root = $this->root . '/template';

        self::assertNull($resolve($root, $root . '/../template-evil/blog.php'));
        self::assertNull($resolve($root, $this->root . '/template-evil/blog.php'));
        self::assertNull($resolve($root, $root . '/../template-evil/../template-evil/blog.php'));

        symlink($this->root . '/template-evil/blog.php', $root . '/outside.php');
        self::assertNull($resolve($root, $root . '/outside.php'));
    }

    public function testMissingOrUnregisteredStylePathsAreRejected(): void
    {
        $resolve = $this->resolver();
        $root = $this->root . '/template';

        self::assertNull($resolve($root, $root . '/does-not-exist.php'));
        self::assertNull($resolve($root, $root . '/template-evil/blog.php'));
    }

    private function resolver(): callable
    {
        $method = new \ReflectionMethod(Template::class, 'getContainedFile');
        $method->setAccessible(true);
        $template = new Template();

        return static fn(string $root, string $file): ?string => $method->invoke($template, $root, $file);
    }
}
