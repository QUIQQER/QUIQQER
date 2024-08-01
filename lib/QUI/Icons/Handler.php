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
    /**
     * @var ?Handler
     */
    protected static ?Handler $Instance = null;

    /**
     * @var array
     */
    protected array $list = [];

    /**
     * list of needed css files
     *
     * @var array
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
     *
     * @return Handler
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

    /**
     * Add a file to the handler
     *
     * @param string $file
     */
    public function addCSSFile($file): void
    {
        $this->files[] = $file;
    }

    /**
     * return all css files
     *
     * @return array
     */
    public function getCSSFiles(): array
    {
        return $this->files;
    }

    /**
     * Icon methods
     */

    public function clearCssFiles(): void
    {
        $this->files = [];
    }

    /**
     * @param array $icons
     */
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

    /**
     * Is the value a icon css class?
     *
     * @param $value
     * @return bool
     */
    public function isIcon($value): bool
    {
        $classes = array_flip($this->list);

        return isset($classes[$value]);
    }

    public function clearCssIcons(): void
    {
        $this->list = [];
    }

    /**
     * helper methods
     */

    /**
     * Return the list as an json array
     *
     * @return string
     */
    public function toJSON(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Return the list as an json array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->list;
    }
}
