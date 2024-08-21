<?php

/**
 * This file contains the \QUI\Projects\Favicon
 */

namespace QUI\Projects;

use QUI;

use const PHP_EOL;

/**
 * The favicon class is used to generate the HTML code for the favicon of a website.
 *
 * A favicon is a small symbol that is displayed in the browser tab bar, in bookmarks and
 * other areas. This class makes it easy to define the favicon for the
 * project and integrate it into the HTML code.
 */
class Favicon
{
    public static function output(Project $Project): string
    {
        $favicon = $Project->getConfig('favicon');

        try {
            $Item = QUI\Projects\Media\Utils::getMediaItemByUrl($favicon);

            if (!($Item instanceof QUI\Projects\Media\Image)) {
                return '';
            }
        } catch (QUI\Exception) {
            return '';
        }

        $sizes = [48, 96, 144];
        $result = '';

        try {
            $result .= '<link rel="icon" href="' . $Item->getSizeCacheUrl() . '"';
            $result .= ' type="' . $Item->getAttribute('mime_type') . '"';
            $result .= '/>';
            $result .= PHP_EOL;
        } catch (QUI\Exception) {
        }

        foreach ($sizes as $size) {
            try {
                $result .= '<link rel="icon" href="' . $Item->getSizeCacheUrl($size, $size) . '"';
                $result .= ' type="' . $Item->getAttribute('mime_type') . '"';
                $result .= ' sizes="' . $size . 'x' . $size . '"';
                $result .= '/>';
                $result .= PHP_EOL;
            } catch (QUI\Exception) {
            }
        }

        return $result;
    }
}
