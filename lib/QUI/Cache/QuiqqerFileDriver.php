<?php

/**
 * This file contains QUI\Cache\FileDriver
 */

namespace QUI\Cache;

use QUI;

/**
 * Class QuiqqerFileDriver
 *
 * @package QUI\Cache
 */
class QuiqqerFileDriver extends \Stash\Driver\FileSystem
{
    /**
     * @param null $key
     * @return bool
     */
    public function clear($key = null)
    {
        $path = $this->makePath($key);

        if (\is_file($path)) {
            $return = true;
            \unlink($path);
        }

        $extension = $this->getEncoder()->getExtension();

        if (\strpos($path, $extension) !== false) {
            $path = \substr($path, 0, -(\strlen($extension)));
        }

        if (\is_dir($path)) {
            try {
                QUI::getTemp()->moveToTemp($path);
            } catch (QUI\Exception $Exception) {
                return false;
            }

            return true;
        }

        return isset($return);
    }
}
