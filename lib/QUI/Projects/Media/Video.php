<?php

/**
 * This file contains the \QUI\Projects\Media\Video
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Utils\System\File as FileUtils;

/**
 * A media image
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Video extends Item implements QUI\Interfaces\Projects\Media\File
{
    public function createCache(): string
    {
        $Media = $this->Media;
        $mediaDir = CMS_DIR . $Media->getPath();
        $file = $this->getAttribute('file');

        return $mediaDir . $file;
    }

    public function deleteCache()
    {
        // cache doesn't exist for videos
    }

    /**
     * Return the real height of the image
     *
     * @return integer|false
     * @throws QUI\Exception
     */
    public function getHeight(): bool|int
    {
        if ($this->getAttribute('image_height')) {
            return (int)$this->getAttribute('image_height');
        }

        $data = FileUtils::getInfo($this->getFullPath(), [
            'imagesize' => true
        ]);

        if (isset($data['height'])) {
            return (int)$data['height'];
        }

        return false;
    }

    /**
     * Return the real with of the image
     *
     * @return integer|false
     * @throws QUI\Exception
     */
    public function getWidth(): bool|int
    {
        if ($this->getAttribute('image_width')) {
            return (int)$this->getAttribute('image_width');
        }

        $data = FileUtils::getInfo($this->getFullPath(), [
            'imagesize' => true
        ]);

        if (isset($data['width'])) {
            return (int)$data['width'];
        }

        return false;
    }
}
