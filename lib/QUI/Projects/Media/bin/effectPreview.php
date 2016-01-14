<?php

/**
 * This file includes the image preview
 */
if (!isset($_REQUEST['id']) || !isset($_REQUEST['project'])) {
    exit;
}

$dir = str_replace('quiqqer/quiqqer/lib/QUI/Projects/Media/bin', '', dirname(__FILE__));
define('QUIQQER_SYSTEM', true);

require_once $dir . '/header.php';

$Project = QUI::getProject($_REQUEST['project']);
$Media   = $Project->getMedia();

$File = $Media->get((int)$_REQUEST['id']);

if (!QUI\Projects\Media\Utils::isImage($File)) {
    exit;
}

$Image = $Media->getImageManager()->make($File->getFullPath());

if (isset($_REQUEST['greyscale']) && (int)$_REQUEST['greyscale']) {
    $Image->greyscale();
}

if (isset($_REQUEST['brightness'])
    && is_numeric($_REQUEST['brightness'])
) {
    $Image->brightness(intval($_REQUEST['brightness']));
}

if (isset($_REQUEST['blur']) && is_numeric($_REQUEST['blur'])) {
    $Image->blur(intval($_REQUEST['blur']));
}

if (isset($_REQUEST['contrast'])
    && is_numeric($_REQUEST['contrast'])
) {
    $contrast = intval($_REQUEST['contrast']);

    if ($contrast !== 0) {
        $Image->contrast($contrast);
    }
}

if (isset($_REQUEST['watermark'])) {
    $watermark = $_REQUEST['watermark'];

    try {
        $MediaImage = \QUI\Projects\Media\Utils::getImageByUrl($watermark);
        $pos        = '';
        $ratio      = false;

        $WatermarkImage = $Media->getImageManager()->make(
            $MediaImage->getFullPath()
        );

        if (isset($_REQUEST['watermark_position'])) {
            $pos = $_REQUEST['watermark_position'];
        }

        if (isset($_REQUEST['watermark_ratio'])) {
            $ratio = (int)$_REQUEST['watermark_ratio'];
        }

        switch ($pos) {
            case "top-left":
            case "top":
            case "top-right":
            case "left":
            case "center":
            case "right":
            case "bottom-left":
            case "bottom":
            case "bottom-right":
                $watermarkPosition = $pos;
                break;

            default:
                $watermarkPosition = 'bottom-right';
                break;
        }

        // ratio calc
        if ($ratio) {
            $imageHeight = $Image->getHeight();
            $imageWidth  = $Image->getWidth();

            $imageHeight = $imageHeight * ($ratio / 100);
            $imageWidth  = $imageWidth * ($ratio / 100);

            $WatermarkImage->resize($imageWidth, $imageHeight, function ($Constraint) {
                $Constraint->aspectRatio();
                $Constraint->upsize();
            });
        }

        $Image->insert($WatermarkImage, $watermarkPosition);

    } catch (QUI\Exception $Exception) {
    }
}


$Image->resize(400, 400, function ($Constraint) {
    $Constraint->aspectRatio();
    $Constraint->upsize();
});

echo $Image->response();
exit;
