<?php

if (!isset($_REQUEST['id']) || !isset($_REQUEST['project'])) {
    exit;
}

$dir = str_replace('quiqqer/quiqqer/lib/QUI/Projects/Media/bin', '', dirname(__FILE__));

require_once $dir.'header.php';

$Project = QUI::getProject($_REQUEST['project']);
$Media = $Project->getMedia();

$File = $Media->get((int)$_REQUEST['id']);

if (!QUI\Projects\Media\Utils::isImage($File)) {
    exit;
}

$Image = $Media->getImageManager()->make($File->getFullPath());

if (isset($_REQUEST['greyscale']) && (int)$_REQUEST['greyscale']) {
    $Image->greyscale();
}

if (isset($_REQUEST['brightness']) && is_numeric($_REQUEST['brightness'])) {

    $Image->brightness(intval($_REQUEST['brightness']));
}

if (isset($_REQUEST['blur']) && is_numeric($_REQUEST['blur'])) {
    $Image->blur(intval($_REQUEST['blur']));
}

if (isset($_REQUEST['contrast']) && is_numeric($_REQUEST['contrast'])) {

    $contrast = intval($_REQUEST['contrast']);

    if ($contrast !== 0) {
        $Image->contrast($contrast);
    }
}

if (isset($_REQUEST['watermark'])) {

    $watermark = $_REQUEST['watermark'];

    try {
        $MediaImage = \QUI\Projects\Media\Utils::getImageByUrl($watermark);
        $pos = '';

        if (isset($_REQUEST['watermark_position'])) {
            $pos = $_REQUEST['watermark_position'];
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

        $Image->insert($MediaImage->getFullPath(), $watermarkPosition);

    } catch (QUI\Exception $Exception) {

    }
}


$Image->resize(400, 400, function ($Constraint) {
    $Constraint->aspectRatio();
    $Constraint->upsize();
});

echo $Image->response();
exit;
