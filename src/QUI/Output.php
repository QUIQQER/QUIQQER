<?php

/**
 * This file contains QUI\Output
 */

namespace QUI;

use DOMElement;
use ForceUTF8\Encoding;
use Masterminds\HTML5;
use QUI;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Projects\Project;
use QUI\Projects\Site;
use QUI\Utils\Singleton;
use QUI\Utils\StringHelper as StringUtils;

use function array_map;
use function count;
use function explode;
use function file_exists;
use function html_entity_decode;
use function http_build_query;
use function implode;
use function ini_get;
use function is_array;
use function is_integer;
use function is_object;
use function is_string;
use function iterator_to_array;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function ltrim;
use function md5;
use function method_exists;
use function parse_str;
use function parse_url;
use function preg_replace;
use function preg_replace_callback;
use function set_time_limit;
use function str_replace;
use function strpos;
use function strtolower;
use function trim;
use function urldecode;

use const ENT_HTML5;
use const ENT_NOQUOTES;

/**
 * Class Output
 */
class Output extends Singleton
{
    /**
     * Current output project
     */
    protected ?Project $Project = null;

    /**
     * internal lifetime image cache
     */
    protected array $imageCache = [];

    protected array $imageUrlCache = [];

    /**
     * internal lifetime link cache
     */
    protected array $linkCache = [];

    /**
     * internal lifetime link cache for rewritten urls
     */
    protected array $rewrittenCache = [];

    protected array $settings = [
        'use-system-image-paths' => false,
        'remove-deleted-links' => true,
        'use-absolute-urls' => false,
        'parse-to-picture-elements' => true
    ];

    /**
     * @param $content
     * @return mixed
     *
     * @throws QUI\Exception
     */
    public function parse($content)
    {
        if (empty($content)) {
            return '';
        }

        QUI::getEvents()->fireEvent('outputParseBegin', [&$content]);

        // rewrite image
        $content = preg_replace_callback(
            '#(src|data\-image|data\-href|data\-link|data\-src)="(image.php)\?([^"]*)"#',
            $this->dataImages(...),
            $content
        );

        // rewrite files
        $content = preg_replace_callback(
            '#(href|src|value)="(image.php)\?([^"]*)"#',
            $this->files(...),
            $content
        );

        // rewrite links
        $content = preg_replace_callback(
            '#(data\-href|data\-link)="(index.php)\?([^"]*)"#',
            $this->dataLinks(...),
            $content
        );

        $content = preg_replace_callback(
            '#(href|src|action|value)="(index.php)\?([^"]*)"#',
            $this->links(...),
            $content
        );

        // search empty <a> links
        if ($this->settings['remove-deleted-links']) {
            $content = preg_replace_callback(
                '/<a[ ]*?>(.*?)<\/a>/ims',
                $this->cleanEmptyLinks(...),
                $content
            );
        }

        // search css files
        $content = preg_replace_callback(
            '#<link([^>]*)>#',
            $this->cssLinkHref(...),
            $content
        );

        // search css files
        $content = preg_replace_callback(
            '#<script([^>]*)>#',
            $this->scripts(...),
            $content
        );

        if ($this->settings['use-absolute-urls']) {
            $content = preg_replace_callback(
                '#(href|src)="(.*?)([^"]*)#',
                $this->absoluteUrls(...),
                $content
            );
        }


        if (empty($content)) {
            QUI::getEvents()->fireEvent('outputParseEnd', [&$content]);

            return $content;
        }

        if (!str_contains($content, '<img')) {
            QUI::getEvents()->fireEvent('outputParseEnd', [&$content]);

            return $content;
        }

        $withDocumentOutput = false;

        if (str_contains($content, '<html') && str_contains($content, '<body')) {
            $withDocumentOutput = true;
        }

        // picture elements
        $executionTime = ini_get('max_execution_time');
        set_time_limit(100);

        libxml_use_internal_errors(true);
        $HTML5 = new HTML5();

        if (!str_contains($content, '<body')) {
            $Dom = $HTML5->loadHTML('<html><body>' . $content . '</body></html>');
        } else {
            $Dom = $HTML5->loadHTML($content);
        }

        libxml_clear_errors();


        if ($this->settings['parse-to-picture-elements']) {
            $images = $Dom->getElementsByTagName('img');

            $nodeContent = static function ($n): string {
                /* @var $n DOMElement */
                $HTML5 = new HTML5([
                    'disable_html_ns' => true
                ]);

                $Dom = $HTML5->loadHTML('');
                $b = $Dom->importNode($n->cloneNode(true), true);

                $Dom->appendChild($b);
                $html = $Dom->saveHTML();

                $html = str_replace('<!DOCTYPE html>', '', $html);
                $html = trim($html);

                return $html;
            };

            $getPicture = static function ($html) {
                if (empty($html)) {
                    return null;
                }

                $HTML5 = new HTML5();

                //$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
                $html = htmlentities($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $d = $HTML5->loadHTML($html);
                $p = $d->getElementsByTagName('picture');

                if ($p->length) {
                    return $p[0];
                }

                return null;
            };

            $isInPicture = static function ($Image): bool {
                $Parent = $Image->parentNode;

                while ($Parent) {
                    $parent = $Parent->nodeName;
                    $Parent = $Parent->parentNode;

                    if ($parent === 'body') {
                        return false;
                    }

                    if ($parent === 'picture') {
                        return true;
                    }
                }

                return false;
            };

            foreach ($images as $Image) {
                if ($isInPicture($Image)) {
                    continue;
                }

                $image = $nodeContent($Image);

                $html = preg_replace_callback(
                    '#<img([^>]*)>#i',
                    $this->images(...),
                    $image
                );

                $Picture = $getPicture($html);

                if ($Picture) {
                    $Picture = $Dom->importNode($Picture, true);
                    $Image->parentNode->replaceChild($Picture, $Image);
                }
            }
        }

        if ($withDocumentOutput) {
            $result = $Dom->saveHTML();
        } else {
            $Body = $Dom->getElementsByTagName('body')[0];

            $result = implode(
                '',
                array_map(
                    [$Body->ownerDocument, "saveHTML"],
                    iterator_to_array($Body->childNodes)
                )
            );
        }

        // reset to the normal limit
        set_time_limit($executionTime);

        $result = str_replace(
            ['</img>', '</source>', '</meta>', '</link>', '</input>', '</br>'],
            '',
            $result
        );

        $result = str_replace('<?xml encoding="utf-8" ?>', '', $result);

        QUI::getEvents()->fireEvent('outputParseEnd', [&$result]);

        $result = preg_replace_callback(
            '#title="([^"]*)"#i',
            static function (array $output): string {
                if (empty($output[1])) {
                    return $output[0];
                }

                $title = $output[1];
                $title = html_entity_decode($title, ENT_NOQUOTES | ENT_HTML5);
                $title = QUI\Utils\Security\Orthos::removeHTML($title);

                return 'title="' . $title . '"';
            },
            $result
        );

        $result = preg_replace_callback(
            '#alt="([^"]*)"#i',
            static function (array $output): string {
                if (empty($output[1])) {
                    return $output[0];
                }

                $alt = $output[1];
                $alt = html_entity_decode($alt, ENT_NOQUOTES | ENT_HTML5);
                $alt = QUI\Utils\Security\Orthos::removeHTML($alt);

                return 'alt="' . $alt . '"';
            },
            $result
        );

        $result = preg_replace_callback(
            '#href="([^"]*)"#i',
            static function (array $output): string {
                if (empty($output[1])) {
                    return $output[0];
                }

                $href = $output[1];
                $href = html_entity_decode($href, ENT_NOQUOTES | ENT_HTML5);
                $href = QUI\Utils\Security\Orthos::removeHTML($href);

                return 'href="' . $href . '"';
            },
            $result
        );

        return $result;
    }

    public function setProject(Project $Project): void
    {
        $this->Project = $Project;
    }

    /**
     * @param string|bool|float|integer $value
     */
    public function setSetting(string $setting, $value): void
    {
        $this->settings[$setting] = $value;
    }

    /**
     * Removes an internal rewritten url from the cache, if needed
     * use this with caution
     */
    public function removeRewrittenUrlCache(QUI\Interfaces\Projects\Site $Site): void
    {
        $project = $Site->getProject()->getName();
        $lang = $Site->getProject()->getLang();
        $id = $Site->getId();

        $rewrittenCache = $project . '_' . $lang . '_' . $id;

        if (isset($this->rewrittenCache[$rewrittenCache])) {
            unset($this->rewrittenCache[$rewrittenCache]);
        }
    }

    protected function dataLinks(array $output): string
    {
        if ($output[2] !== 'index.php') {
            return $output[0];
        }

        return $this->links($output);
    }

    /**
     * parse href links
     */
    protected function links(array $output): string
    {
        // no php url
        if ($output[2] !== 'index.php') {
            return $output[0];
        }

        $output = str_replace('&amp;', '&', $output);   // &amp; fix
        $output = str_replace('〈=', '&lang=', $output); // URL FIX

        $components = $output[3];

        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->linkCache[$components])) {
            return $output[1] . '="' . $this->linkCache[$components] . '"';
        }

        $parseUrl = parse_url($output[2] . '?' . $components);

        if (empty($parseUrl['query'])) {
            return $output[0];
        }

        $urlQuery = $parseUrl['query'];

        if (
            !str_contains($urlQuery, 'project')
            || !str_contains($urlQuery, 'lang')
            || !str_contains($urlQuery, 'id')
        ) {
            // no quiqqer url
            return $output[0];
        }

        // maybe a quiqqer url ?
        parse_str($urlQuery, $urlQueryParams);

        try {
            $url = $this->getSiteUrl($urlQueryParams);
            $anchor = '';

            if (isset($parseUrl['fragment']) && !empty($parseUrl['fragment'])) {
                $anchor = '#' . $parseUrl['fragment'];
            }

            if (empty($url)) {
                return '';
            }

            $this->linkCache[$components] = $url . $anchor;

            return $output[1] . '="' . $url . $anchor . '"';
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }
        //return $output[0];
    }

    /**
     * Return a rewritten url from a site
     *
     * @throws Exception
     */
    public function getSiteUrl(array $params = [], array $getParams = []): string
    {
        $project = false;
        $id = false;

        // Falls ein Objekt übergeben wird
        if (isset($params['site']) && is_object($params['site'])) {
            /* @var $Project Project */
            /* @var $Site Site */
            $Site = $params['site'];
            $Project = $Site->getProject();
            $id = $Site->getId();

            $lang = $Project->getLang();
            $project = $Project->getName();

            unset($params['site']);
        } else {
            if (isset($params['id'])) {
                $id = $params['id'];
            }

            if (isset($params['project'])) {
                $project = $params['project'];
            }

            if (isset($params['lang'])) {
                $lang = $params['lang'];
            }

            unset($params['project']);
            unset($params['id']);
            unset($params['lang']);
        }

        if ($id === false || $project === false) {
            throw new QUI\Exception(
                'Params missing Rewrite::getUrlFromPage'
            );
        }

        if (!isset($lang)) {
            $lang = '';
        }

        // get params
        if (!empty($getParams)) {
            $params['_getParams'] = $getParams;
        }

        if (!isset($Project)) {
            try {
                $Project = QUI::getProject($project, $lang);
                /* @var $Project Project */
            } catch (QUI\Exception) {
                return '';
            }
        }

        $rewrittenCache = $project . '_' . $lang . '_' . $id;

        if (isset($this->rewrittenCache[$rewrittenCache])) {
            $url = $this->rewrittenCache[$rewrittenCache];
        } else {
            $linkCachePath = Site::getLinkCachePath($project, $lang, $id);

            try {
                $url = QUI\Cache\Manager::get($linkCachePath);
            } catch (\Exception $Exception) {
                $_params = [];

                if (isset($params['suffix'])) {
                    $_params['suffix'] = $params['suffix'];
                }

                try {
                    /* @var $Site Site */
                    $Site = $Project->get((int)$id);
                } catch (QUI\Exception $Exception) {
                    return '';
                }

                if ($Site->getAttribute('deleted')) {
                    return '';
                }

                // Create cache
                $url = $Site->getLocation($_params);

                try {
                    QUI\Cache\Manager::set($linkCachePath, $url);
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }

            $this->rewrittenCache[$rewrittenCache] = $url;
        }

        $url = $this->extendUrlWithParams($url, $params);
        $vhosts = QUI::vhosts();

        if (!$Project->hasVHost() && !empty($vhosts)) {
            $url = $Project->getLang() . '/' . $url;
        }

        // If the output project is different than the one of the page
        // Then use absolute domain path
        if (
            !$this->Project ||
            $Project->toArray() != $this->Project->toArray()
        ) {
            return $Project->getVHost(true, true) . URL_DIR . $url;
        }

        /**
         * Sprache behandeln
         */

//        if (isset($_SERVER['HTTP_HOST'])
//            && isset($vHosts[$_SERVER['HTTP_HOST']])
//            && isset($vHosts[$_SERVER['HTTP_HOST']][$lang])
//            && !empty($vHosts[$_SERVER['HTTP_HOST']][$lang])
//        ) {
//            $data  = $vHosts[$_SERVER['HTTP_HOST']];
//            $vHost = $vHosts[$_SERVER['HTTP_HOST']][$lang];
//
//            if (// wenn ein Host eingetragen ist
//                $lang != $Project->getAttribute('lang')
//                // falls der jetzige host ein anderer ist als der vom link,
//                // dann den host an den link setzen
//                || $vHost != $_SERVER['HTTP_HOST']
//            ) {
//                $protocol = empty($data['httpshost']) ? 'http://' : 'https://';
//
//                // und die Sprache nicht die vom jetzigen Projekt ist
//                // dann Host davor setzen
//                $url = $vHost . URL_DIR . $url;
//                $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
//                $url = $protocol . $this->project_prefix . $url;
//
//                return $url;
//            }
//
//            $url = $this->project_prefix . $url;
//            $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
//        } elseif ($Project->getAttribute('default_lang') !== $lang) {
//            // Falls kein Host Eintrag gibt
//            // Und nicht die Standardsprache dann das Sprachenflag davor setzen
//            $url = $this->project_prefix . $lang . '/' . $url;
//            $url = QUI\Utils\StringHelper::replaceDblSlashes($url);
//        }

        $url = URL_DIR . $url;

        $projectHost = $Project->getHost();
        $projectHost = str_replace(['https://', 'http://'], '', $projectHost);

        // falls host anders ist, dann muss dieser dran gehängt werden
        // damit kein doppelter content entsteht
        if (
            !isset($_SERVER['HTTP_HOST']) ||
            (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != $projectHost && $projectHost != '')
        ) {
            $url = $Project->getVHost(true, true) . $url;
        }

        return $url;
    }

    /**
     * Erweitert die URL um Params
     */
    protected function extendUrlWithParams(string $url, array $params = []): string
    {
        if (!count($params)) {
            return $url;
        }

        $separator = Rewrite::URL_PARAM_SEPARATOR;
        $getParams = [];

        if (isset($params['_getParams']) && is_string($params['_getParams'])) {
            parse_str($params['_getParams'], $getParams);
            unset($params['_getParams']);
        } elseif (isset($params['_getParams']) && is_array($params['_getParams'])) {
            $getParams = $params['_getParams'];
            unset($params['_getParams']);
        }

        if (isset($params['paramAsSites']) && $params['paramAsSites']) {
            $separator = '/';
            unset($params['paramAsSites']);
        }


        $suffix = '';
        $exp = explode('.', $url);
        $url = $exp[0];

        foreach ($params as $param => $value) {
            if (is_integer($param)) {
                $url .= $separator . $value;
                continue;
            }

            if ($param == 'suffix') {
                continue;
            }

            if ($param === "0") {
                $url .= $separator . $value;
                continue;
            }

            $url .= $separator . $param . $separator . $value;
        }

        if (isset($params['suffix'])) {
            $suffix = '.' . $params['suffix'];
        }

        if (empty($suffix) && isset($exp[1])) {
            $suffix = '.' . $exp[1];
        }

        if (empty($suffix)) {
            $suffix = QUI::getRewrite()->getDefaultSuffix();
        }

        if (empty($getParams)) {
            return $url . $suffix;
        }

        return $url . $suffix . '?' . http_build_query($getParams);
    }

    /**
     * search empty a href links
     * - if link is empty, return = inner html of the link
     */
    protected function cleanEmptyLinks(array $output): string
    {
        if (!str_contains($output[0], 'href=')) {
            return $output[1];
        }

        return $output[0];
    }

    /**
     * parse file links
     */
    protected function files(array $output): string
    {
        try {
            $url = MediaUtils::getRewrittenUrl('image.php?' . $output[3]);
        } catch (QUI\Exception) {
            $url = '';
        }

        return $output[1] . '="' . $url . '"';
    }

    /**
     * parse image links
     */
    protected function images(array $output): string
    {
        $img = $output[0];
        $att = StringUtils::getHTMLAttributes($img);

        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->imageCache[$img])) {
            return $this->imageCache[$img];
        }

        if (!isset($att['src'])) {
            return $output[0];
        }

        if (!MediaUtils::isMediaUrl($att['src']) && !str_contains($att['src'], 'media/cache')) {
            // is relative url from the system?
            if (
                $this->settings['use-system-image-paths']
                && !str_contains($output[0], 'http')
            ) {
                // workaround for system paths, not optimal
                $output[0] = str_replace(
                    ' src="',
                    ' src="' . CMS_DIR,
                    $output[0]
                );
            }

            return $output[0];
        }

        $src = str_replace('&amp;', '&', $att['src']);
        $src = urldecode($src);

        unset($att['src']);

        if (str_contains($src, 'media/cache')) {
            try {
                $Image = MediaUtils::getElement($src);
                $src = $Image->getUrl();
            } catch (QUI\Exception) {
            }
        }

        if (!isset($att['alt']) || !isset($att['title'])) {
            try {
                $Image = MediaUtils::getImageByUrl($src);

                $att['alt'] = $Image->getAlt();
                $att['title'] = $Image->getTitle();
                $att['data-src'] = $Image->getSizeCacheUrl();

                if ($Image->hasViewPermissionSet()) {
                    $src = $Image->getUrl();
                    $att['data-src'] = $Image->getUrl();
                }
            } catch (QUI\Exception) {
            }
        }

        if (isset($att['alt'])) {
            $att['alt'] = Encoding::toUTF8($att['alt']);
        }

        if (isset($att['title'])) {
            $att['title'] = Encoding::toUTF8($att['title']);
        }

        $html = MediaUtils::getImageHTML($src, $att);

        // workaround
        if ($this->settings['use-system-image-paths']) {
            $html = str_replace(
                ' src="',
                ' src="' . CMS_DIR,
                $html
            );
        }

        $this->imageCache[$img] = $html;

        return $this->imageCache[$img];
    }

    protected function dataImages($output): string
    {
        $output = str_replace('&amp;', '&', $output);   // &amp; fix
        $output = str_replace('〈=', '&lang=', $output); // URL FIX

        $components = $output[3];


        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->imageUrlCache[$components])) {
            return $output[1] . '="' . $this->imageUrlCache[$components] . '"';
        }

        $parseUrl = parse_url($output[2] . '?' . $components);

        if (empty($parseUrl['query'])) {
            return $output[0];
        }

        $urlQuery = $parseUrl['query'];

        // check no quiqqer url
        if (!str_contains($urlQuery, 'project') || !str_contains($urlQuery, 'id')) {
            return $output[0];
        }

        try {
            $MediaItem = MediaUtils::getMediaItemByUrl('image.php?' . $components);
        } catch (QUI\Exception) {
            return '';
        }

        if (
            method_exists($MediaItem, 'hasViewPermissionSet')
            && $MediaItem->hasViewPermissionSet()
        ) {
            return $output[1] . '="' . URL_DIR . $MediaItem->getUrl() . '"';
        }

        if (MediaUtils::isImage($MediaItem)) {
            $attributes = StringUtils::getUrlAttributes('?' . $components);

            if (isset($attributes['maxwidth'])) {
                $attributes['width'] = $attributes['maxwidth'];
            }

            if (isset($attributes['maxheight'])) {
                $attributes['height'] = $attributes['maxheight'];
            }

            $source = MediaUtils::getImageSource(
                'image.php?' . $components,
                $attributes
            );
        } else {
            $source = $MediaItem->getUrl(true);
        }

        $this->imageUrlCache[$components] = $source;

        return $output[1] . '="' . $source . '"';
    }

    /**
     * @param $output
     * @return mixed
     */
    protected function checkPictureTag($output)
    {
        $html = $output[0];

        if (str_contains($html, '</picture>')) {
            return $html;
        }

        // find image
        $html = str_replace("\n", ' ', $html);
        $html = preg_replace('!\s+!', ' ', $html);

        $html = preg_replace_callback(
            '#<img([^>]*)>#i',
            $this->images(...),
            $html
        );

        return $html;
    }

    /**
     * Parse `<link` html nodes
     */
    protected function cssLinkHref(array $output): string
    {
        $html = $output[0];
        $att = StringUtils::getHTMLAttributes($html);

        if (!isset($att['href'])) {
            return $html;
        }

        if (!isset($att['rel'])) {
            return $html;
        }

        if (strtolower($att['rel']) !== 'stylesheet') {
            return $html;
        }

        if (str_contains($att['href'], '?lu=')) {
            return $html;
        }

        $lu = md5(QUI::getPackageManager()->getLastUpdateDate());
        $file = CMS_DIR . ltrim($att['href'], '/');

        // check if css file is project custom css
        if (str_contains($att['href'], 'custom.css') && file_exists($file)) {
            $lu = md5(filemtime($file));
        }

        if (!str_contains($att['href'], '?')) {
            $att['href'] .= '?lu=' . $lu;
        } else {
            $att['href'] .= '&lu=' . $lu;
        }

        $result = '<link ';

        foreach ($att as $k => $v) {
            $result .= $k . '="' . $v . '" ';
        }

        $result .= '/>';

        return $result;
    }

    protected function scripts($output): string
    {
        $html = $output[0];
        $att = StringUtils::getHTMLAttributes($html);

        if (!isset($att['src'])) {
            return $html;
        }

        if (str_contains($att['src'], '?lu=')) {
            return $html;
        }

        // external files dont get the lu flag
        if (
            str_contains($att['src'], 'http://')
            || str_contains($att['src'], 'https://')
            || str_starts_with($att['src'], '//')
        ) {
            return $html;
        }

        $lu = md5(QUI::getPackageManager()->getLastUpdateDate());
        $file = CMS_DIR . ltrim($att['src'], '/');

        // check if css file is project custom css
        if (str_contains($att['src'], 'custom.js') && file_exists($file)) {
            $lu = md5(filemtime($file));
        }

        if (!str_contains($att['src'], '?')) {
            $att['src'] .= '?lu=' . $lu;
        } else {
            $att['src'] .= '&lu=' . $lu;
        }

        $result = '<script ';

        foreach ($att as $k => $v) {
            $result .= $k . '="' . $v . '" ';
        }

        $result .= '>';

        return $result;
    }

    /**
     * Set a host to all urls
     */
    protected function absoluteUrls($output): string
    {
        $html = $output[0];

        if (!isset($output[1]) || !isset($output[2]) || !isset($output[3])) {
            return $html;
        }

        $url = $output[3];

        if (str_contains($url, 'https://') || str_contains($url, 'http://')) {
            return $html;
        }

        if (str_contains($url, 'data:') || empty($url)) {
            return $html;
        }

        $host = HOST;

        if ($this->Project) {
            $host = $this->Project->getHost();

            if (!str_contains($host, 'https://') && !str_contains($host, 'http://')) {
                $host = 'https://' . $host;
            }
        }

        $host = trim($host, '/') . '/';
        $url = trim($url, '/');

        return $output[1] . '="' . $host . $url . '"';
    }
}
