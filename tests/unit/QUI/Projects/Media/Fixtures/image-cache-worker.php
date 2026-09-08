<?php

use Intervention\Image\ImageManager;
use QUI\Lock\Locker;
use QUI\Projects\Media;
use QUI\Projects\Media\Image;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Exception\LockConflictedException;

define('QUIQQER_SYSTEM', true);
define('QUIQQER_AJAX', true);
require dirname(__DIR__, 5) . '/Support/DatabaseEnvironment.php';

if (\QUITests\Support\DatabaseEnvironment::usesCiDatabase()) {
    require dirname(__DIR__, 9) . '/bootstrap.php';
} else {
    require dirname(__DIR__, 5) . '/runtime-bootstrap.php';
}

$directory = $argv[1];
$id = $argv[2];
$variant = $id === '3' ? 'independent' : 'shared';
Locker::setProcessLockStore(new class ($directory, $id) extends FlockStore {
    public function __construct(private string $directory, private string $id)
    {
        parent::__construct($directory . '/locks');
    }

    public function save(Key $key): void
    {
        try {
            parent::save($key);
        } catch (LockConflictedException $Exception) {
            file_put_contents($this->directory . '/waiting-' . $this->id, 'waiting');
            throw $Exception;
        }
    }
});

$Media = new class extends Media {
    public function __construct()
    {
    }

    public function getImageManager(): ImageManager
    {
        return ImageManager::gd();
    }
};

$Image = new class ($Media, $directory, $variant) extends Image {
    public function __construct(Media $Media, private string $directory, private string $variant)
    {
        $this->Media = $Media;
    }

    public function getAttribute(string $name): mixed
    {
        return ['active' => 1, 'mime_type' => 'image/png'][$name] ?? null;
    }

    public function checkPermission(string $permission, ?QUI\Interfaces\Users\User $User = null): void
    {
    }

    public function getFullPath(): string
    {
        return $this->directory . '/original.png';
    }

    public function getSizeCachePath(bool | string | int $maxWidth = false, bool | string | int $maxHeight = false): string
    {
        return $this->directory . '/' . $this->variant . '.png';
    }

    public function getEffects(): array
    {
        file_put_contents($this->directory . '/generations', $this->variant . "\n", FILE_APPEND | LOCK_EX);
        file_put_contents($this->directory . '/started-' . $this->variant, 'started');
        $deadline = microtime(true) + 8;

        while ($this->variant === 'shared' && !file_exists($this->directory . '/release')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Image rendering barrier timed out.');
            }

            usleep(10000);
        }

        return ['brightness' => 1];
    }

    public function getWatermark(): Image | false
    {
        return false;
    }
};

file_put_contents($directory . '/ready-' . $id, 'ready');
$deadline = microtime(true) + 15;

while (!file_exists($directory . '/go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Image cache worker barrier timed out.');
    }

    usleep(10000);
}

$result = $Image->createSizeCache();

if ($result !== $directory . '/' . $variant . '.png' || getimagesize($result)[2] !== IMAGETYPE_PNG) {
    throw new RuntimeException('Worker received an invalid cached image.');
}

// A subsequent warm read must not trigger a second render.
$Image->createSizeCache();
file_put_contents($directory . '/done-' . $id, 'done');
