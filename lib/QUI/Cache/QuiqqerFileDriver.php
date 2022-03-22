<?php

/**
 * This file contains QUI\Cache\FileDriver
 */

namespace QUI\Cache;

use QUI;

use Stash\Driver\FileSystem;

use function is_dir;
use function is_file;
use function strlen;
use function strpos;
use function substr;
use function unlink;

/**
 * Class QuiqqerFileDriver
 */
class QuiqqerFileDriver extends FileSystem
{
    /**
     * @param null $key
     * @return bool
     */
    public function clear($key = null)
    {
        $path = $this->makePath($key);

        if (is_file($path)) {
            $return = true;
            unlink($path);
        }

        $extension = $this->getEncoder()->getExtension();

        if (strpos($path, $extension) !== false) {
            $path = substr($path, 0, -(strlen($extension)));
        }

        if (is_dir($path)) {
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
