<?php

/**
 * This file contains the \QUI\Projects\Media\Utils
 */

namespace QUI\Projects\Media;

use DOMElement;
use DOMXPath;
use Exception;
use ForceUTF8\Encoding;
use QUI;
use QUI\System\Log;
use QUI\Utils\StringHelper as StringUtils;
use QUI\Utils\Text\XML;

use function array_pop;
use function array_shift;
use function ceil;
use function count;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function htmlspecialchars;
use function implode;
use function is_object;
use function is_string;
use function md5;
use function md5_file;
use function method_exists;
use function preg_match;
use function preg_replace;
use function sha1_file;
use function str_replace;
use function strpos;
use function strrpos;
use function substr;
use function substr_count;
use function trim;

use const PHP_EOL;

/**
 * Helper for the Media Center Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Utils
{
    /**
     * Prefix for the cache key where the size of the media folder is stored.
     * Should be followed by the project name when querying the cache.
     */
    const CACHE_KEY_MEDIA_FOLDER_SIZE_PREFIX = "media_folder_size_";


    /**
     * Prefix for the cache key where the timestamp of the media folder size is stored.
     * Should be followed by the project name when querying the cache.
     */
    const CACHE_KEY_TIMESTAMP_MEDIA_FOLDER_SIZE_PREFIX = "timestamp_media_folder_size_";


    /**
     * Prefix for the cache key where the size of the media cache folder is stored.
     * Should be followed by the project name when querying the cache.
     */
    const CACHE_KEY_MEDIA_CACHE_FOLDER_SIZE_PREFIX = "media_cache_folder_size_";


    /**
     * Prefix for the cache key where the timestamp of the media cache folder size is stored.
     * Should be followed by the project name when querying the cache.
     */
    const CACHE_KEY_TIMESTAMP_MEDIA_CACHE_FOLDER_SIZE_PREFIX = "timestamp_media_cache_folder_size_";

    protected static array $urlItemCache = [];

    /**
     * Returns the item array
     * the array is specially adapted for the media center
     */
    public static function parseForMediaCenter(QUI\Interfaces\Projects\Media\File $Item): array
    {
        if ($Item instanceof Folder && $Item->getId() === 1) {
            return [
                'icon' => 'fa fa-home',
                'icon80x80' => URL_BIN_DIR . '80x80/media.png',
                'extension' => '',
                'id' => $Item->getId(),
                'name' => $Item->getAttribute('name'),
                'title' => $Item->getAttribute('title'),
                'short' => $Item->getAttribute('short'),
                'type' => 'folder',
                'hasChildren' => $Item->hasChildren(),
                'hasSubfolders' => $Item->hasSubFolders(),
                'active' => true,
                'e_date' => $Item->getAttribute('e_date'),
                'e_user' => $Item->getAttribute('e_user'),
                'c_date' => $Item->getAttribute('c_date'),
                'c_user' => $Item->getAttribute('c_user'),
                'priority' => $Item->getAttribute('priority'),
                'isHidden' => $Item->isHidden()
            ];
        }

        if ($Item instanceof Folder) {
            return [
                'icon' => 'fa fa-folder-o',
                'icon80x80' => URL_BIN_DIR . '80x80/extensions/folder.png',
                'extension' => '',
                'id' => $Item->getId(),
                'name' => $Item->getAttribute('name'),
                'title' => $Item->getAttribute('title'),
                'short' => $Item->getAttribute('short'),
                'type' => 'folder',
                'hasChildren' => $Item->hasChildren(),
                'hasSubfolders' => $Item->hasSubFolders(),
                'active' => $Item->isActive(),
                'e_date' => $Item->getAttribute('e_date'),
                'e_user' => $Item->getAttribute('e_user'),
                'c_date' => $Item->getAttribute('c_date'),
                'c_user' => $Item->getAttribute('c_user'),
                'priority' => $Item->getAttribute('priority'),
                'isHidden' => $Item->isHidden()
            ];
        }


        $extension = self::getExtension($Item->getAttribute('file'));

        return [
            'icon' => self::getIconByExtension($extension),
            'icon80x80' => self::getIconByExtension($extension, '80x80'),
            'extension' => $extension,
            'id' => $Item->getId(),
            'name' => $Item->getAttribute('name'),
            'title' => $Item->getAttribute('title'),
            'short' => $Item->getAttribute('short'),
            'type' => $Item->getType() === Image::class ? 'image' : 'file',
            'url' => $Item->getUrl(),
            'active' => $Item->isActive(),
            'e_date' => $Item->getAttribute('e_date'),
            'e_user' => $Item->getAttribute('e_user'),
            'c_date' => $Item->getAttribute('c_date'),
            'c_user' => $Item->getAttribute('c_user'),
            'mimetype' => $Item->getAttribute('mime_type'),
            'priority' => $Item->getAttribute('priority'),
            'isHidden' => $Item->isHidden()
        ];
    }

    /**
     * Return the extension of a file
     */
    public static function getExtension(string $filename): string
    {
        $explode = explode('.', $filename);

        return array_pop($explode);
    }

    /**
     * Returns a suitable icon of a certain extension
     *
     * @param string $ext - extenstion
     * @param string $size - 16x16, 80x80 (default = 16x16); optional
     *
     * @return string - Icon url
     *
     * @todo icons in config auslagern, somit einfacher erweiterbar
     */
    public static function getIconByExtension(string $ext, string $size = '16x16'): string
    {
        switch ($size) {
            case '16x16':
            case '80x80':
                break;

            // set default size
            default:
                $size = '16x16';
        }

        $extensions['16x16'] = [
            'folder' => URL_BIN_DIR . '16x16/extensions/folder.png',
            'pdf' => URL_BIN_DIR . '16x16/extensions/pdf.png',
            // Images
            'jpg' => URL_BIN_DIR . '16x16/extensions/image.png',
            'jpeg' => URL_BIN_DIR . '16x16/extensions/image.png',
            'gif' => URL_BIN_DIR . '16x16/extensions/image.png',
            'png' => URL_BIN_DIR . '16x16/extensions/image.png',
            // Movie
            'avi' => URL_BIN_DIR . '16x16/extensions/film.png',
            'mpeg' => URL_BIN_DIR . '16x16/extensions/film.png',
            'mpg' => URL_BIN_DIR . '16x16/extensions/film.png',
            // Archiv
            'tar' => URL_BIN_DIR . '16x16/extensions/archive.png',
            'rar' => URL_BIN_DIR . '16x16/extensions/archive.png',
            'zip' => URL_BIN_DIR . '16x16/extensions/archive.png',
            'gz' => URL_BIN_DIR . '16x16/extensions/archive.png',
            '7z' => URL_BIN_DIR . '16x16/extensions/archive.png',
            //Office

            // Music
            'mp3' => URL_BIN_DIR . '16x16/extensions/sound.png',
            'ogg' => URL_BIN_DIR . '16x16/extensions/sound.png',
        ];

        $extensions['80x80'] = [
            'folder' => URL_BIN_DIR . '80x80/extensions/folder.png',
            'pdf' => URL_BIN_DIR . '80x80/extensions/pdf.png',
            // Images
            'jpg' => URL_BIN_DIR . '80x80/extensions/image.png',
            'jpeg' => URL_BIN_DIR . '80x80/extensions/image.png',
            'gif' => URL_BIN_DIR . '80x80/extensions/image.png',
            'png' => URL_BIN_DIR . '80x80/extensions/image.png',
            // Movie
            'avi' => URL_BIN_DIR . '80x80/extensions/film.png',
            'mpeg' => URL_BIN_DIR . '80x80/extensions/film.png',
            'mpg' => URL_BIN_DIR . '80x80/extensions/film.png',
            // Archiv
            'tar' => URL_BIN_DIR . '80x80/extensions/archive.png',
            'rar' => URL_BIN_DIR . '80x80/extensions/archive.png',
            'zip' => URL_BIN_DIR . '80x80/extensions/archive.png',
            'gz' => URL_BIN_DIR . '80x80/extensions/archive.png',
            '7z' => URL_BIN_DIR . '80x80/extensions/archive.png',
            //Office

            // Music
            'mp3' => URL_BIN_DIR . '80x80/extensions/sound.png',
        ];

        return $extensions[$size][$ext] ?? URL_BIN_DIR . $size . '/extensions/empty.png';
    }

    /**
     * Return the fitting font awesome class
     */
    public static function getFontAwesomeIconByItem(Item $Item): string
    {
        if (self::isImage($Item)) {
            return 'fa-file-photo-o';
        }

        $extension = self::getExtension($Item->getAttribute('file'));

        if ($extension === 'pdf') {
            return 'fa-file-pdf-o';
        }

        return 'fa-file-o';
    }

    /**
     * Is the variable an image object?
     */
    public static function isImage(object|bool|string $Unknown): bool
    {
        if (!is_object($Unknown)) {
            return false;
        }

        if (!method_exists($Unknown, 'getType')) {
            return false;
        }

        if ($Unknown->getType() === Image::class) {
            return true;
        }

        return false;
    }

    /**
     * Return the media type by a file mime type
     *
     * @example \QUI\Projects\Media\Utils::getMediaTypeByMimeType( 'image/jpeg' )
     */
    public static function getMediaTypeByMimeType(string $mime_type): string
    {
        if (str_contains($mime_type, 'image/') && !str_contains($mime_type, 'vnd.adobe')) {
            return 'image';
        }

        if (str_contains($mime_type, 'video/')) {
            return 'video';
        }

        return 'file';
    }

    /**
     * Return <picture><img /></picture> from image attributes
     * considered responsive images, too
     */
    public static function getImageHTML(string $src, array $attributes = [], bool $withHost = false): string
    {
        $src = self::getImageSource($src, $attributes);

        if (empty($src)) {
            return '';
        }

        if (str_contains($src, 'image.php')) {
            return '';
        }

        $parts = explode('/', $src);
        $md5 = md5(
            serialize([
                'attributes' => $attributes,
                'src' => $src,
                'withHost' => $withHost
            ])
        );

        $cacheName = 'quiqqer/projects/' . $parts[3] . '/picture-' . $md5;

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUi\Exception $Exception) {
            Log::addDebug($Exception->getMessage());
        }

        try {
            QUI::getEvents()->fireEvent('mediaCreateImageHtmlBegin', [
                $src,
                $attributes
            ]);
        } catch (QUI\Exception $Exception) {
            Log::addDebug($Exception->getMessage());
        }


        // build picture source sets (refactored)
        $srcset = [];
        $host = '';
        $imgMimeType = '';

        try {
            $originalSrc = urldecode($src);
            $Image = Utils::getElement($originalSrc);

            if (!($Image instanceof Image)) {
                return '';
            }

            if ($withHost) {
                $host = $Image->getMedia()->getProject()->getVHost(true, true);
            }

            $Project = $Image->getMedia()->getProject();
            $imageWidth = (int)$Image->getWidth();
            $maxWidth = false;
            $maxHeight = false;

            if (isset($attributes['width'])) {
                $maxWidth = (int)$attributes['width'];
            }

            if (isset($attributes['height'])) {
                $maxHeight = (int)$attributes['height'];
            }

            if (isset($attributes['style'])) {
                $style = StringUtils::splitStyleAttributes($attributes['style']);

                if (isset($style['width']) && !str_contains($style['width'], '%')) {
                    $maxWidth = (int)$style['width'];
                }

                if (isset($style['height']) && !str_contains($style['height'], '%')) {
                    $maxHeight = (int)$style['height'];
                }
            }

            $imageScale = (int)$Project->getConfig('media_useImageScale') ?: 2;
            $imageScale = min($imageScale, 20);

            if (!$imageScale) {
                $imageScale = 2;
            }

            $imgMimeType = $Image->getAttribute('mime_type');

            if ($imageWidth) {
                $end = $maxWidth && $imageWidth > $maxWidth ? $maxWidth : $imageWidth;
                $batchesCount = (int)$Project->getConfig('media_imageBatchesCount');

                if (!$batchesCount) {
                    $batchesCount = 3;
                }

                $batchSize = ceil($end / $batchesCount) ?: 200;
                $start = 16;
                $duplicate = [];

                for (; $start < $end + $batchSize; $start += $batchSize) {
                    $imageUrl = $Image->getSizeCacheUrl($start, $maxHeight);

                    if (isset($duplicate[$imageUrl])) {
                        continue;
                    }
                    $duplicate[$imageUrl] = true;
                    $srcset[] = htmlspecialchars($host . $imageUrl) . ' ' . $start . 'w';

                    // Retina/HiDPI
                    for ($x = 2; $x <= $imageScale; $x++) {
                        if ($imageWidth > $start * $x) {
                            $src2x = $Image->getSizeCacheUrl($start * $x);
                            $srcset[] = htmlspecialchars($host . $src2x) . ' ' . ($start * $x) . 'w';
                        }
                    }
                }
            }
        } catch (QUI\Exception $Exception) {
            Log::addDebug($Exception->getMessage());
        }

        // image string
        $img = '<img ';

        foreach ($attributes as $key => $value) {
            if (($key === 'width' || $key === 'height') && is_numeric($value)) {
                $img .= htmlspecialchars($key) . '="' . $value . '" ';
                continue;
            }

            if (is_array($value) && $key === 'alt' && isset($Image) && $Image instanceof Item) {
                $value = $Image->getAlt();
            } elseif (!is_string($value)) {
                continue;
            }

            if ($key === 'alt' || $key === 'title') {
                $value = Encoding::toUTF8($value);
            }

            $img .= htmlspecialchars($key) . '="' . $value . '" ';
        }

        $img .= 'src="' . $host . htmlspecialchars($originalSrc) . '"';

        if (!empty($srcset)) {
            $img .= ' srcset="' . implode(', ', $srcset) . '"';
        }

        if (!empty($imgMimeType)) {
            $img .= ' type="' . $imgMimeType . '"';
        }

        $img .= ' />';

        // picture html (nur ein picture, keine mehrfachen sources)
        $picture = '<picture>' . $img . '</picture>';

        try {
            QUI::getEvents()->fireEvent('mediaCreateImageHtml', [&$picture]);
        } catch (QUI\Exception $Exception) {
            Log::addDebug($Exception->getMessage());
        }


        if (!empty($attributes['style'])) {
            $picture = str_replace(
                '<picture>',
                '<picture style="' . $attributes['style'] . '">',
                $picture
            );
        }

        QUI\Cache\Manager::set($cacheName, $picture);

        return $picture;
    }

    /**
     * Return only the source for an <img /> tag from image attributes
     */
    public static function getImageSource($src, array $attributes = []): string
    {
        $width = false;
        $height = false;

        if (isset($attributes['style'])) {
            $style = StringUtils::splitStyleAttributes(
                $attributes['style']
            );

            if (isset($style['width'])) {
                $width = $style['width'];
            }

            if (isset($style['height'])) {
                $height = $style['height'];
            }
        } elseif (isset($attributes['width'])) {
            $width = $attributes['width'];
        } elseif (isset($attributes['height'])) {
            $height = $attributes['height'];
        }

        if (!$width && isset($attributes['width'])) {
            $width = $attributes['width'];
        }

        if (!$height && isset($attributes['height'])) {
            $height = $attributes['height'];
        }

        if (str_contains($width, '%')) {
            $width = false;
        } else {
            $width = (int)$width;
        }

        if (str_contains($height, '%')) {
            $height = false;
        } else {
            $height = (int)$height;
        }

        try {
            $Image = self::getImageByUrl($src);
        } catch (QUI\Exception) {
            return '';
        }

        if (!self::isImage($Image)) {
            return '';
        }

        /* @var $Image Image */
        try {
            $src = $Image->getSizeCacheUrl($width, $height);

            if ($Image->hasViewPermissionSet()) {
                $src = URL_DIR . $Image->getUrl();
            }
        } catch (QUI\Exception $Exception) {
            Log::writeException($Exception);

            return '';
        }

        return $src;
    }

    /**
     * Return the media image
     * If it is no image, its throws an exception
     *
     * @throws QUI\Exception
     */
    public static function getImageByUrl(mixed $url): Image
    {
        if (!is_string($url)) {
            throw new QUI\Exception(
                'The wanted URL is not a QUIQQER item url',
                ErrorCodes::NOT_AN_ITEM_URL
            );
        }

        if (self::isMediaUrl($url) === false) {
            throw new QUI\Exception(
                'Its not a QUIQQER image url',
                ErrorCodes::NOT_AN_IMAGE_URL
            );
        }

        $Obj = self::getMediaItemByUrl($url);

        if (!($Obj instanceof Image)) {
            throw new QUI\Exception(
                'Its not an image',
                ErrorCodes::NOT_AN_IMAGE
            );
        }

        return $Obj;
    }

    public static function isMediaUrl(mixed $url): bool
    {
        if (!is_string($url)) {
            return false;
        }

        if (
            str_contains($url, 'image.php')
            && str_contains($url, 'project=')
            && str_contains($url, 'id=')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Return the media image, file, folder
     *
     * @throws QUI\Exception
     */
    public static function getMediaItemByUrl(mixed $url): QUI\Interfaces\Projects\Media\File
    {
        if (!is_string($url)) {
            throw new QUI\Exception(
                'The wanted URL is not a QUIQQER item url',
                ErrorCodes::NOT_AN_ITEM_URL
            );
        }

        if (self::isMediaUrl($url) === false) {
            throw new QUI\Exception(
                'Its not a QUIQQER item url',
                ErrorCodes::NOT_AN_ITEM_URL
            );
        }

        if (isset(self::$urlItemCache[$url])) {
            return self::$urlItemCache[$url];
        }

        $params = StringUtils::getUrlAttributes($url);
        $Project = QUI::getProject($params['project']);
        $Media = $Project->getMedia();

        self::$urlItemCache[$url] = $Media->get((int)$params['id']);

        return self::$urlItemCache[$url];
    }

    /**
     * Returns a media item by an url
     *
     * @param string $url - cache url, or real path of the file
     *
     * @return QUI\Interfaces\Projects\Media\File
     *
     * @throws QUI\Exception
     */
    public static function getElement(string $url): QUI\Interfaces\Projects\Media\File
    {
        $filePath = self::getRealFileDataFromCacheUrl($url);
        $Project = QUI::getProject($filePath['project']);
        $Media = $Project->getMedia();

        return $Media->getChildByPath($filePath['filePath']);
    }

    /**
     * @throws QUI\Exception
     */
    public static function getRealFileDataFromCacheUrl($url): array
    {
        if (str_contains($url, 'media/cache/')) {
            $parts = explode('media/cache/', $url);
        } elseif (str_contains($url, 'media/sites/')) {
            $parts = explode('media/sites/', $url);
        } else {
            throw new QUI\Exception(
                'File not found',
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        if (!isset($parts[1])) {
            throw new QUI\Exception(
                'File not found',
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        $parts = explode('/', $parts[1]);
        $project = array_shift($parts);
        $ProjectManager = QUI::getProjectManager();

        /*
         * method_exists is checked here because otherwise a fatal error might be thrown
         * during composer update when two different instance of QUIQQER are initialised
         * and "existsProject" does not exist in the calling instance.
         */
        if (method_exists($ProjectManager, 'existsProject') && !$ProjectManager::existsProject($project)) {
            throw new QUI\Exception(
                'File not found',
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        // if the element (image) is resized
        $fileName = array_pop($parts);

        if (str_contains($fileName, '__')) {
            $lastpos_ul = strrpos($fileName, '__') + 2;
            $pos_dot = strpos($fileName, '.', $lastpos_ul);

            $fileName = substr($fileName, 0, ($lastpos_ul - 2)) .
                substr($fileName, $pos_dot);
        }

        $parts[] = $fileName;
        $filePaths = implode('/', $parts);

        return [
            'project' => $project,
            'filePath' => $filePaths
        ];
    }

    /**
     * Return the rewritten url from an image.php? url
     *
     * @throws QUI\Exception
     */
    public static function getRewrittenUrl(string $output, array $size = []): string
    {
        if (self::isMediaUrl($output) === false) {
            return $output;
        }

        // detect parameters
        $params = StringUtils::getUrlAttributes($output);

        $id = $params['id'];
        $project = $params['project'];

        $cache = 'cache/links/' . $project . '/media/' . $id;
        $url = '';

        // exist cache?
        try {
            $url = QUI\Cache\Manager::get($cache);
        } catch (QUI\Cache\Exception $Exception) {
            Log::writeDebugException($Exception);
        }

        if (empty($url)) {
            try {
                $Obj = self::getMediaItemByUrl($output);
                $url = $Obj->getUrl(true);

                if (!($Obj instanceof Image)) {
                    return $url;
                }
            } catch (Exception $Exception) {
                Log::addDebug($Exception->getMessage(), [
                    'url' => $output,
                    'trace' => $Exception->getTrace()
                ]);

                return URL_DIR . $output;
            }
        }


        // sizes
        if (count($size)) {
            $url_explode = explode('.', $url);

            if (!isset($size['height'])) {
                $size['height'] = '';
            }

            if (!isset($size['width'])) {
                $size['width'] = '';
            }

            if (!isset($url_explode[1])) {
                $url_explode[1] = '';
            }

            $url = $url_explode[0] . '__' . $size['width'] . 'x' . $size['height'] . '.' . $url_explode[1];
        }

        if (!file_exists(CMS_DIR . $url)) {
            $Project = QUI::getProject($project);
            $Media = $Project->getMedia();
            $Obj = $Media->get((int)$id);

            if ($Obj instanceof Image) {
                if (!isset($size['width'])) {
                    $size['width'] = false;
                }

                if (!isset($size['height'])) {
                    $size['height'] = false;
                }

                $result = $Obj->createSizeCache($size['width'], $size['height']);

                if ($result) {
                    $url = $result;
                }
            } else {
                $result = $Obj->createCache();

                if ($result) {
                    $url = $result;
                }
            }
        }

        return $url;
    }

    /**
     * is methods
     */
    /**
     * checks if the string can be used for a media folder name
     *
     * @throws QUI\Exception
     */
    public static function checkFolderName(string $str): bool
    {
        if (preg_match('/[^0-9_a-zA-Z \-]/', $str)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.media.check.foldername.allowed.signs',
                    ['foldername' => $str]
                ),
                ErrorCodes::FOLDER_ILLEGAL_CHARACTERS
            );
        }

        if (str_contains($str, '__')) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.media.check.name.allowed.underline'
                ),
                ErrorCodes::FOLDER_ILLEGAL_CHARACTERS
            );
        }

        return true;
    }

    /**
     * Deletes characters which are not allowed for folders
     */
    public static function stripFolderName(string $str): string
    {
        $str = QUI\Utils\Convert::convertRoman($str);
        $str = preg_replace('/[^0-9a-zA-Z\-]/', '_', $str);

        // clean double _
        return preg_replace('/[_]{2,}/', "_", $str);
    }

    /**
     * checks if the string can be used for a media item
     *
     * @param string $filename - the complete filename: my_file.jpg
     *
     * @throws QUI\Exception
     */
    public static function checkMediaName(string $filename): void
    {
        // Prüfung des Namens - Sonderzeichen
        if (preg_match('/[^0-9_a-zA-Z \-.]/', $filename)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.media.check.name.allowed.signs',
                    ['filename' => $filename]
                ),
                ErrorCodes::FOLDER_ILLEGAL_CHARACTERS
            );
        }

        // mehr als zwei punkte
        if (substr_count($filename, '.') > 1) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.media.check.name.dots'
                ),
                ErrorCodes::FOLDER_ILLEGAL_CHARACTERS
            );
        }

        if (str_contains($filename, '__')) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.media.check.name.underline'
                ),
                ErrorCodes::FOLDER_ILLEGAL_CHARACTERS
            );
        }
    }

    /**
     * Deletes characters which are not allowed in the media center
     */
    public static function stripMediaName(string $str): string
    {
        // Umlaute
        $str = str_replace(
            [
                'ä',
                'ö',
                'ü'
            ],
            [
                'ae',
                'oe',
                'ue'
            ],
            $str
        );

        $str = preg_replace('/[^0-9_a-zA-Z\ \.\-]/', '', $str);

        // delete the dots but not the last dot
        $str = str_replace('.', '_', $str);
        $str = StringUtils::replaceLast('_', '.', $str);

        // FIX
        return preg_replace('/[_]{2,}/', "_", $str);
    }

    /**
     * Is the variable a folder object?
     */
    public static function isFolder(mixed $Unknown): bool
    {
        if (!is_object($Unknown)) {
            return false;
        }

        if ($Unknown instanceof Folder) {
            return true;
        }

        return false;
    }

    /**
     * Is the object a media item
     */
    public static function isItem(mixed $Unknown): bool
    {
        if (!is_object($Unknown)) {
            return false;
        }

        if (!method_exists($Unknown, 'getType')) {
            return false;
        }

        return $Unknown instanceof Item;
    }

    /**
     * Check the upload params if a replacement can do
     *
     * @param QUI\Projects\Media $Media
     * @param integer $fileId - The File which will be replaced
     * @param array $uploadParams - Array with file information array('name' => '', 'type' => '')
     *
     * @throws QUI\Exception
     */
    public static function checkReplace(QUI\Projects\Media $Media, int $fileId, array $uploadParams): void
    {
        $result = QUI::getDataBase()->fetch([
            'from' => $Media->getTable(),
            'where' => [
                'id' => $fileId
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.file.not.found',
                    ['file' => $fileId]
                ),
                ErrorCodes::FILE_NOT_FOUND
            );
        }

        $data = $result[0];

        // if the mimetype is the same, no check for renaming
        // so, the check is finish
        if ($data['mime_type'] == $uploadParams['type']) {
            return;
        }

        $File = $Media->get($fileId);

        if ($File->getAttribute('name') == $uploadParams['name']) {
            return;
        }

        $Parent = $File->getParent();

        if (
            method_exists($Parent, 'fileWithNameExists')
            && $Parent->fileWithNameExists($uploadParams['name'])
        ) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.media.file.already.exists',
                    ['filename' => $uploadParams['name']]
                ),
                ErrorCodes::FILE_ALREADY_EXISTS
            );
        }
    }

    /**
     * Generate the MD5 hash of a file object
     */
    public static function generateMD5(Image|File $File): string
    {
        return md5_file($File->getFullPath());
    }

    /**
     * Generate the SHA1 hash of a file object
     */
    public static function generateSHA1(Image|File $File): string
    {
        return sha1_file($File->getFullPath());
    }

    /**
     * Counts and returns the number of folders for a project.
     */
    public static function countFoldersForProject(QUI\Projects\Project $Project): int
    {
        $mediaTable = $Project->getMedia()->getTable();

        try {
            $result = QUI::getDataBase()->fetch([
                'count' => 'id',
                'from' => $mediaTable,
                'where' => [
                    'type' => 'folder'
                ]
            ]);
        } catch (QUI\Exception) {
            return 0;
        }

        if (isset($result[0])) {
            return (int)$result[0]['id'];
        }

        return 0;
    }

    public static function countFilesForProject(QUI\Projects\Project $Project): int
    {
        $mediaTable = $Project->getMedia()->getTable();

        try {
            $result = QUI::getDataBase()->fetch([
                'count' => 'id',
                'from' => $mediaTable,
                'where' => [
                    'type' => [
                        'type' => 'NOT',
                        'value' => 'folder'
                    ]
                ]
            ]);
        } catch (QUI\Exception) {
            return 0;
        }

        if (isset($result[0])) {
            return (int)$result[0]['id'];
        }

        return 0;
    }

    /**
     * Returns the size of the given project's media folder in bytes.
     *
     * By default, the value is returned from cache.
     * If there is no value in cache, null is returned, unless you use the force parameter.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param QUI\Projects\Project $Project
     * @param boolean $force - Force a calculation of the media folder size. Values aren't returned from cache. Expect timeouts.
     *
     * @return int|null
     */
    public static function getMediaFolderSizeForProject(QUI\Projects\Project $Project, bool $force = false): ?int
    {
        return QUI\Utils\System\Folder::getFolderSize($Project->getMedia()->getFullPath(), $force);
    }

    /**
     * Returns the timestamp when to media folder size was stored in cache for the given project.
     * Returns null if there is no data in the cache.
     */
    public static function getMediaFolderSizeTimestampForProject(QUI\Projects\Project $Project): ?int
    {
        return QUI\Utils\System\Folder::getFolderSizeTimestamp($Project->getMedia()->getFullPath());
    }

    /**
     * Returns the size of the given project's media cache folder in bytes.
     *
     * By default, the value is returned from cache.
     * If there is no value in cache, null is returned, unless you use the force parameter.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param QUI\Projects\Project $Project
     * @param boolean $force - Force a calculation of the media folder size. Values aren't returned from cache. Expect timeouts.
     *
     * @return int|null
     */
    public static function getMediaCacheFolderSizeForProject(QUI\Projects\Project $Project, bool $force = false): ?int
    {
        return QUI\Utils\System\Folder::getFolderSize($Project->getMedia()->getFullCachePath(), $force);
    }

    /**
     * Returns the timestamp when to media cache folder size was stored in cache for the given project.
     * Returns null if there is no data in the cache.
     */
    public static function getMediaCacheFolderSizeTimestampForProject(QUI\Projects\Project $Project): ?int
    {
        return QUI\Utils\System\Folder::getFolderSizeTimestamp($Project->getMedia()->getFullCachePath());
    }

    /**
     * Counts and returns all the different types of files for a given project.
     *
     * @param QUI\Projects\Project $Project
     *
     * @return array - the array's keys are the file types and their values are their amounts
     */
    public static function countFiletypesForProject(QUI\Projects\Project $Project): array
    {
        $table = $Project->getMedia()->getTable();

        $query = "
        SELECT
        (case
             /* Count all 'image/%' mimetypes as image */
             WHEN `mime_type` LIKE 'image/%' THEN 'image'
             else `mime_type` END
          ) as mime_type
          , COUNT(id) as count
        FROM `$table`
        WHERE `type` != 'folder'
        GROUP BY
                            (case
             /* Group all 'image/%' mimetypes as image */
             WHEN `mime_type` LIKE 'image/%' THEN 'image'
             else `mime_type` END
          )
        ;
        ";

        try {
            $result = QUI::getDataBase()->fetchSQL($query);
        } catch (QUI\Exception) {
            return [];
        }

        $return = [];

        foreach ($result as $element) {
            $return[$element['mime_type']] = (int)$element['count'];
        }

        return $return;
    }

    public static function getExtraAttributeListForMediaItems(Item $Item): array
    {
        $cache = $Item->getMedia()->getProject()->getCachePath() . '/xml-media-attributes/';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception) {
        }


        // global extra attributes
        $siteXmlList = QUI::getPackageManager()->getPackageMediaXmlList();
        $result = [];

        foreach ($siteXmlList as $package) {
            $file = OPT_DIR . $package . '/media.xml';

            if (!file_exists($file)) {
                continue;
            }

            $Dom = XML::getDomFromXml($file);
            $Path = new DOMXPath($Dom);

            $attributes = $Path->query('//quiqqer/media/attributes/attribute');

            foreach ($attributes as $Attribute) {
                if (!($Attribute instanceof DOMElement)) {
                    continue;
                }

                $result[] = [
                    'attribute' => trim($Attribute->nodeValue),
                    'default' => $Attribute->getAttribute('default')
                ];
            }
        }

        try {
            QUI\Cache\Manager::set($cache, $result);
        } catch (Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        return $result;
    }

    /**
     * Returns the whitelist of file extensions that are exempt from media caching.
     * If the noMediaCache.ini.php file does not exist, a default whitelist is created and saved to the file.
     *
     * @return array - Array of file extensions
     */
    public static function getWhiteListForNoMediaCache(): array
    {
        $file = ETC_DIR . 'noMediaCache.ini.php';

        if (!file_exists($file)) {
            $defaultList = [
                'pdf',
                'txt',
                'xml',
                'doc',
                'pdt',
                'xls',
                'csv',
                'txt',
                'swf',
                'flv',
                'mp3',
                'mp4',
                'ogg',
                'wav',
                'mpeg',
                'avi',
                'mpg',
                'divx',
                'mov',
                'wmv',
                'zip',
                'rar',
                '7z',
                'gzip',
                'tar',
                'tgz',
                'ace',
                'psd'
            ];

            file_put_contents($file, ';<?php exit; ?>' . PHP_EOL . implode(PHP_EOL, $defaultList));
        }

        $extensions = file_get_contents($file);
        $extensions = str_replace(';<?php exit; ?>', '', $extensions);
        $extensions = trim($extensions);
        $extensions = explode(PHP_EOL, $extensions);

        return $extensions;
    }
}
