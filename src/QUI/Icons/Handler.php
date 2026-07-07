<?php

/**
 * This file contains QUI\Icons\Handler
 */

namespace QUI\Icons;

use QUI;

use function array_flip;
use function is_array;
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

    /**
     * @var array<int, string>
     */
    protected array $list = [];

    /**
     * list of needed css files
     *
     * @var array<int, string>
     */
    protected array $files = [];

    /**
     * optional extended icon metadata: label, categories, aliases, searchTerms
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $iconData = [];

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

    /**
     * @return array<int, string>
     */
    public function getCSSFiles(): array
    {
        return $this->files;
    }

    public function clearCssFiles(): void
    {
        $this->files = [];
    }

    /**
     * @param array<int, string> $icons
     */
    public function addIcons(array $icons): void
    {
        foreach ($icons as $icon) {
            $this->addIcon($icon);
        }
    }

    /**
     * @param string $iconClass
     */
    public function addIcon($iconClass): void
    {
        $this->list[] = trim($iconClass);
    }

    /**
     * @param string $value
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
     * Add extended icon metadata. Each entry should provide at least a
     * 'class' key. The class is additionally registered in the flat icon
     * list so isIcon() recognises style variants like
     * 'fa fa-regular fa-comment' that may not be present in the CSS-based
     * class list.
     *
     * Recognised keys per entry: class, label, categories, aliases, searchTerms.
     *
     * @param array<int, mixed> $entries
     */
    public function addIconData(array $entries): void
    {
        foreach ($entries as $entry) {
            if (!is_array($entry) || empty($entry['class'])) {
                continue;
            }

            $this->iconData[] = $entry;
            $this->addIcon($entry['class']);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getIconData(): array
    {
        return $this->iconData;
    }

    public function hasIconData(): bool
    {
        return !empty($this->iconData);
    }

    /**
     * helper methods
     */

    public function toJSON(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @return array<int, string>
     */
    public function toArray(): array
    {
        return $this->list;
    }
}
