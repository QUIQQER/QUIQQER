<?php

/**
 * This file contains QUI\Icons\Handler
 */

namespace QUI\Icons;

use QUI;

/**
 * Class Handler
 * Icon handler for css class icons like font awesome
 *
 * @package QUI\Icons
 */
class Handler
{
    /**
     * @var Handler
     */
    protected static $Instance = null;

    /**
     * @var array
     */
    protected $list = [];

    /**
     * list of needed css files
     *
     * @var array
     */
    protected $files = [];

    /**
     * return the global icon handler
     *
     * @return Handler
     */
    public static function getInstance()
    {
        if (\is_null(self::$Instance)) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }

    /**
     * Handler constructor.
     */
    public function __construct()
    {
        QUI::getEvents()->fireEvent('onIconsInit', [$this]);
    }

    /**
     * File methods
     */

    /**
     * Add a file to the handler
     *
     * @param string $file
     */
    public function addCSSFile($file)
    {
        $this->files[] = $file;
    }

    /**
     * return all css files
     *
     * @return array
     */
    public function getCSSFiles()
    {
        return $this->files;
    }

    /**
     * Icon methods
     */

    /**
     * @param $iconClass
     */
    public function addIcon($iconClass)
    {
        $this->list[] = \trim($iconClass);
    }

    /**
     * @param array $icons
     */
    public function addIcons(array $icons)
    {
        foreach ($icons as $icon) {
            $this->addIcon($icon);
        }
    }

    /**
     * Is the value a icon css class?
     *
     * @param $value
     * @return bool
     */
    public function isIcon($value)
    {
        $classes = \array_flip($this->list);

        return isset($classes[$value]);
    }

    /**
     * helper methods
     */

    /**
     * Return the list as anjson array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->list;
    }

    /**
     * Return the list as anjson array
     *
     * @return string
     */
    public function toJSON()
    {
        return \json_encode($this->toArray());
    }
}
