<?php

require_once 'bootstrap.php';

if (isset($_REQUEST['project']) && isset($_REQUEST['id'])) {

    try {
        /* @var $project \QUI\Projects\Project */
        $Project = \QUI\Projects\Manager::getProject($_REQUEST['project']);
        $Media = $Project->getMedia();
        $File = $Media->get((int)$_REQUEST['id']);

        // Bilder direkt im Browser ausgeben
        $file = $File->getAttribute('file');
        $image = false;

        /*
        if (isset($_REQUEST['admin']) && $_REQUEST['admin'] == 1 && $Obj->getType() == 'IMAGE')
        {
            if (!isset($_REQUEST['width'])) {
                $_REQUEST['width'] = false;
            }

            if(!isset($_REQUEST['height'])) {
                $_REQUEST['height'] = false;
            }

            $image = $Obj->createAdminCache($_REQUEST['width'], $_REQUEST['height']);
        }
        */

        if ($File->getType() === 'QUI\\Projects\\Media\\Image'
            && (isset($_REQUEST['maxwidth']) || isset($_REQUEST['maxheight']))
        ) {
            $maxwidth = false;
            $maxheight = false;

            if (isset($_REQUEST['maxwidth'])) {
                $maxwidth = (int)$_REQUEST['maxwidth'];
            }

            if (isset($_REQUEST['maxheight'])) {
                $maxheight = (int)$_REQUEST['maxheight'];
            }

            $image = $File->createResizeCache($maxwidth, $maxheight);
        }

        if (!$image) {
            $image
                =
                CMS_DIR.'media/sites/'.$Project->getAttribute('name').'/'.$file;
        }

        if (!file_exists($image)) {
            header("HTTP/1.0 404 Not Found");
            \QUI\System\Log::write('File not exist '.$image, 'error');
            exit;
        }

        header("Content-Type: ".$File->getAttribute('mime_type'));
        header("Expires: ".gmdate("D, d M Y H:i:s")." GMT");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: inline; filename=\"".pathinfo($file,
                PATHINFO_BASENAME)."\"");
        header("Content-Size: ".filesize($image));
        header("Content-Length: ".filesize($image));
        header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
        header("Connection: Keep-Alive");

        $fo_image = fopen($image, "r");
        $fr_image = fread($fo_image, filesize($image));
        fclose($fo_image);

        echo $fr_image;

    } catch (\QUI\Exception $e) {
        // wenn es das Bild nicht mehr gibt
        header("HTTP/1.0 404 Not Found");
        exit;
    }

} else {
    // wenn es das Bild nicht mehr gibt
    header("HTTP/1.0 404 Not Found");
    exit;
}
