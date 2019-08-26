<?php

if (!\defined('QUIQQER_SYSTEM')) {
    \define('QUIQQER_SYSTEM', true);
}

require_once 'bootstrap.php';

if (!isset($_REQUEST['project']) || !isset($_REQUEST['id'])) {
    \header("HTTP/1.0 404 Not Found");
    exit;
}

use QUI\Projects\Media;
use QUI\Utils\System\File;

/**
 * return mime_type of a file
 *
 * @param string $file
 * @return string
 */
function getMimeType($file)
{
    if (!\file_exists($file)) {
        return '';
    }

    if (\function_exists('mime_content_type')) { // PHP interne Funktionen
        return \mime_content_type($file);
    }

    if (\function_exists('finfo_open') && \function_exists('finfo_file')) { // PECL
        $finfo = \finfo_open(FILEINFO_MIME);
        $part  = \explode(';', \finfo_file($finfo, $file));

        return $part[0];
    }

    return '';
}

try {
    /* @var $project \QUI\Projects\Project */
    $Project = QUI\Projects\Manager::getProject($_REQUEST['project']);
    $Media   = $Project->getMedia();
    $File    = $Media->get((int)$_REQUEST['id']);

    if (Media\Utils::isFolder($File)) {
        QUI\Utils\System\File::send(
            BIN_DIR.'16x16/folder.png'
        );
        exit;
    }

    // Bilder direkt im Browser ausgeben
    $file    = $File->getAttribute('file');
    $image   = false;
    $isAdmin = false;

    if (isset($_SERVER['HTTP_REFERER'])
        && \strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false
        && \strpos($_SERVER['HTTP_REFERER'], URL_SYS_DIR)
    ) {
        $isAdmin = true;
    }

    if (isset($_REQUEST['quiadmin'])) {
        $isAdmin = true;
    }

    // admin image request
    if (!isset($_REQUEST['noresize'])
        && !isset($_REQUEST['maxwidth'])
        && !isset($_REQUEST['maxheight'])
        && $isAdmin
    ) {
        $_REQUEST['maxwidth']  = 500;
        $_REQUEST['maxheight'] = 500;
    }

    // admin output
    if ($isAdmin
        && Media\Utils::isImage($File)
        && QUI::getUsers()->getUserBySession()->canUseBackend()
    ) {
        if (!isset($_REQUEST['maxwidth'])) {
            $_REQUEST['maxwidth'] = null;
        }

        if (!isset($_REQUEST['maxheight'])) {
            $_REQUEST['maxheight'] = null;
        }

        if (!$_REQUEST['maxwidth'] && !$_REQUEST['maxheight']) {
            $_REQUEST['maxwidth']  = 500;
            $_REQUEST['maxheight'] = 500;
        }

        // cache
        $cacheDir = VAR_DIR.'media/cache/admin/'
                    .$Project->getName().'/'
                    .$Project->getLang().'/';

        QUI\Utils\System\File::mkdir($cacheDir);

        // filecache
        $ext = \pathinfo($File->getFullPath(), \PATHINFO_EXTENSION);

        if ($File->getAttribute('mime_type') == 'image/svg+xml') {
            \header('Content-type: image/svg+xml');
            echo \file_get_contents($File->getFullPath());
            exit;
        }

        $cacheFile = $cacheDir.$File->getId()
                     .'__'.$_REQUEST['maxheight'].'x'
                     .$_REQUEST['maxwidth'].'.'.$ext;

        if (getMimeType($cacheFile) == 'image/svg+xml') {
            \header('Content-type: image/svg+xml');
            echo \file_get_contents($cacheFile);
            exit;
        }

        if (\file_exists($cacheFile)) {
            QUI\Utils\System\File::fileHeader($cacheFile);
            exit;
        }

        try {
            $Image = $Media->getImageManager()->make($File->getFullPath());
        } catch (\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());

            QUI\Utils\System\File::fileHeader($File->getFullPath());
            exit;
        }


        if (isset($_REQUEST['noresize'])) {
            $Image->save($cacheFile);
        } else {
            $Image->resize(
                $_REQUEST['maxwidth'],
                $_REQUEST['maxheight'],
                function ($Constraint) {
                    $Constraint->aspectRatio();
                    $Constraint->upsize();
                }
            );

            $Image->save($cacheFile);
        }

        QUI\Utils\System\File::fileHeader($cacheFile);
        exit;
    }


    if (!isset($_REQUEST['noresize'])
        && Media\Utils::isImage($File)
        && (isset($_REQUEST['maxwidth']) || isset($_REQUEST['maxheight']))
    ) {
        $maxwidth  = false;
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
        $image = CMS_DIR.'media/sites/'.$Project->getName().'/'.$file;
    }

    if (!\file_exists($image)) {
        \header("HTTP/1.0 404 Not Found");
        QUI\System\Log::addInfo('File not exist '.$image);
        exit;
    }

    \header("Content-Type: ".$File->getAttribute('mime_type'));
    \header("Expires: ".\gmdate("D, d M Y H:i:s")." GMT");
    \header("Pragma: public");
    \header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    \header("Accept-Ranges: bytes");
    \header("Content-Size: ".\filesize($image));
    \header("Content-Length: ".\filesize($image));
    \header("Last-Modified: ".\gmdate("D, d M Y H:i:s")." GMT");
    \header("Connection: Keep-Alive");
    \header("Content-Disposition: inline; filename=\"".\pathinfo($file, PATHINFO_BASENAME)."\"");

    $fo_image = \fopen($image, "r");
    $fr_image = \fread($fo_image, \filesize($image));
    \fclose($fo_image);

    echo $fr_image;
    exit;
} catch (QUI\Exception $Exception) {
}


// wenn es das Bild nicht mehr gibt
\header("HTTP/1.0 404 Not Found");
exit;
