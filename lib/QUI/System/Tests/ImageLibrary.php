<?php

/**
 * This class contains \QUI\System\Tests\ImageLibrary
 */

namespace QUI\System\Tests;

use QUI;

use function class_exists;
use function function_exists;
use function implode;

/**
 * JSON Test
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class ImageLibrary extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes([
            'title' => 'Image Libraries',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if json_encode and json_decode available
     *
     * @return int self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        $libraries = [];

        // ImageMagick PHP
        if (class_exists('Imagick')) {
            $libraries[] = 'PHP Image Magick';
        }

        // GD Lib
        if (function_exists('imagecopyresampled')) {
            $libraries[] = 'GD Lib';
        }

        if (empty($libraries)) {
            return self::STATUS_ERROR;
        }

        $this->setAttribute(
            'title',
            'Image Libraries (' . implode(', ', $libraries) . ')'
        );

        return self::STATUS_OK;
    }
}
