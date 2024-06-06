<?php

/**
 * This file contains QUI\Icons\Handler
 */

namespace QUI\Icons;

use QUI;

use function array_flip;
use function is_null;
use function json_encode;
use function trim;

/**
 * Class Handler
 * Icon handler for css class icons like font awesome
 */
class Handler
{
    protected static ?Handler $Instance = null;

    protected array $list = [];

    /**
     * list of needed css files
     */
    protected array $files = [];

    /**
     * Handler constructor.
     */
    public function __construct()
    {
        QUI::getEvents()->fireEvent('onIconsInit', [$this]);
    }

    /**
     * return the global icon handler
     */
    public static function getInstance(): ?Handler
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }

    /**
     * File methods
     */
    public function addCSSFile(string $file): void
    {
        $this->files[] = $file;
    }

    public function getCSSFiles(): array
    {
        return $this->files;
    }

    public function addIcons(array $icons): void
    {
        foreach ($icons as $icon) {
            $this->addIcon($icon);
        }
    }

    /**
     * @param $iconClass
     */
    public function addIcon($iconClass): void
    {
        $this->list[] = trim($iconClass);
    }

    public function isIcon($value): bool
    {
        $classes = array_flip($this->list);

        return isset($classes[$value]);
    }

    /**
     * helper methods
     */

    public function toJSON(): string
    {
        return json_encode($this->toArray());
    }

    public function toArray(): array
    {
        return $this->list;
    }
}
