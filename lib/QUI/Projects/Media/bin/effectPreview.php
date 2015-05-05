<?php

$cmsDir = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));

if (!isset($_REQUEST['id']) || !isset($_REQUEST['project'])) {
    exit;
}

require $cmsDir.'/bootstrap.php';

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

$Image->resize(400, 400, function ($Constraint) {
    $Constraint->aspectRatio();
    $Constraint->upsize();
});

echo $Image->response();
exit;
