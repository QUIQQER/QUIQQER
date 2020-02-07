<?php

/**
 * This file contains QUI\Output
 */

namespace QUI;

use QUI;
use QUI\Utils\Singleton;

use QUI\Utils\StringHelper as StringUtils;
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\Text\XML;

/**
 * Class Output
 *
 * @package QUI
 */
class Output extends Singleton
{
    /**
     * Current output project
     *
     * @var null|QUI\Projects\Project
     */
    protected $Project = null;

    /**
     * internal lifetime image cache
     *
     * @var array
     */
    protected $imageCache = [];

    /**
     * @var array
     */
    protected $imageUrlCache = [];

    /**
     * internal lifetime link cache
     *
     * @var array
     */
    protected $linkCache = [];

    /**
     * @var array
     */
    protected $settings = [
        'use-system-image-paths' => false,
        'remove-deleted-links'   => true
    ];

    /**
     * @param $content
     * @return mixed
     */
    public function parse($content)
    {
        // rewrite image
        $content = \preg_replace_callback(
            '#(src|data\-image|data\-href|data\-link|data\-src)="(image.php)\?([^"]*)"#',
            [&$this, "dataImages"],
            $content
        );

        // rewrite files
        $content = \preg_replace_callback(
            '#(href|src|value)="(image.php)\?([^"]*)"#',
            [&$this, "files"],
            $content
        );

        // rewrite links
        $content = \preg_replace_callback(
            '#(data\-href|data\-link)="(index.php)\?([^"]*)"#',
            [&$this, "dataLinks"],
            $content
        );

        $content = \preg_replace_callback(
            '#(href|src|action|value)="(index.php)\?([^"]*)"#',
            [&$this, "links"],
            $content
        );

        // search empty <a> links
        if ($this->settings['remove-deleted-links']) {
            $content = \preg_replace_callback(
                '/<a[ ]*?>(.*?)<\/a>/ims',
                [&$this, "cleanEmptyLinks"],
                $content
            );
        }

        if (empty($content)) {
            return $content;
        }

        if (\strpos($content, '<img') === false) {
            return $content;
        }

        $withDocumentOutput = false;

        if (\strpos($content, '<html') !== false && \strpos($content, '<body') !== false) {
            $withDocumentOutput = true;
        }

        // picture elements
        \libxml_use_internal_errors(true);
        $Dom = new \DOMDocument();
        $Dom->loadHTML($content);
        \libxml_clear_errors();


        $images = $Dom->getElementsByTagName('img');

        $nodeContent = function ($n) {
            /* @var $n \DOMElement */
            $d = new \DOMDocument();
            $b = $d->importNode($n->cloneNode(true), true);
            $d->appendChild($b);

            return $d->saveHTML();
        };

        $getPicture = function ($html) {
            if (empty($html)) {
                return null;
            }

            $d = new \DOMDocument();
            $d->loadHTML($html);
            $p = $d->getElementsByTagName('picture');

            if ($p->length) {
                return $p[0];
            }

            return null;
        };


        foreach ($images as $Image) {
            /* @var $Parent \DOMElement */
            $Parent = $Image->parentNode;
            $parent = $Parent->nodeName;

            if ($parent === 'picture') {
                continue;
            }

            $image = $nodeContent($Image);

            $html = \preg_replace_callback(
                '#<img([^>]*)>#i',
                [&$this, "images"],
                $image
            );

            $html = $image;

            if (\strpos($html, '<picture') === false) {
                continue;
            }

            $Picture = $getPicture($html);

            if ($Picture) {
                $Picture = $Dom->importNode($Picture, true);
                $Parent->replaceChild($Picture, $Image);
            }
        }


        if ($withDocumentOutput) {
            return $Dom->saveHTML();
        }


        $Body = $Dom->getElementsByTagName('body')[0];

        return \implode(\array_map(
            [$Body->ownerDocument, "saveHTML"],
            \iterator_to_array($Body->childNodes)
        ));
    }

    /**
     * @param Projects\Project $Project
     */
    public function setProject(QUI\Projects\Project $Project)
    {
        $this->Project = $Project;
    }

    /**
     * Set a setting
     *
     * @param string $setting
     * @param string|bool|float|integer $value
     */
    public function setSetting($setting, $value)
    {
        $this->settings[$setting] = $value;
    }

    /**
     * @param $output
     * @return string
     */
    protected function dataLinks($output)
    {
        if ($output[2] !== 'index.php') {
            return $output[0];
        }

        return $this->links($output);
    }

    /**
     * parse href links
     *
     * @param array $output
     * @return string
     */
    protected function links($output)
    {
        // no php url
        if ($output[2] !== 'index.php') {
            return $output[0];
        }

        $output = \str_replace('&amp;', '&', $output);   // &amp; fix
        $output = \str_replace('〈=', '&lang=', $output); // URL FIX

        $components = $output[3];

        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->linkCache[$components])) {
            return $output[1].'="'.$this->linkCache[$components].'"';
        }

        $parseUrl = \parse_url($output[2].'?'.$components);

        if (!isset($parseUrl['query']) || empty($parseUrl['query'])) {
            return $output[0];
        }

        $urlQuery = $parseUrl['query'];

        if (\strpos($urlQuery, 'project') === false
            || \strpos($urlQuery, 'lang') === false
            || \strpos($urlQuery, 'id') === false
        ) {
            // no quiqqer url
            return $output[0];
        }

        // maybe a quiqqer url ?
        \parse_str($urlQuery, $urlQueryParams);

        try {
            $url    = $this->getSiteUrl($urlQueryParams);
            $anchor = '';

            if (isset($parseUrl['fragment']) && !empty($parseUrl['fragment'])) {
                $anchor = '#'.$parseUrl['fragment'];
            }

            if (empty($url)) {
                return '';
            }

            $this->linkCache[$components] = $url.$anchor;

            return $output[1].'="'.$url.$anchor.'"';
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }

        //return $output[0];
    }

    /**
     * search empty a href links
     * - if link is empty, return = inner html of the link
     *
     * @param array $output
     * @return string
     */
    protected function cleanEmptyLinks($output)
    {
        if (\strpos($output[0], 'href=') === false) {
            return $output[1];
        }

        return $output[0];
    }

    /**
     * parse file links
     *
     * @param array $output
     * @return string
     */
    protected function files($output)
    {
        try {
            $url = MediaUtils::getRewrittenUrl('image.php?'.$output[3]);
        } catch (QUI\Exception $Exception) {
            $url = '';
        }

        return $output[1].'="'.$url.'"';
    }

    /**
     * parse image links
     *
     * @param array $output
     * @return string
     */
    protected function images($output)
    {
        $img = $output[0];
        $att = StringUtils::getHTMLAttributes($img);

        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->imageCache[$img])) {
            return $this->imageCache[$img];
        }

        if (!MediaUtils::isMediaUrl($att['src']) && \strpos($att['src'], 'media/cache') === false) {
            // is relative url from the system?
            if ($this->settings['use-system-image-paths']
                && \strpos($output[0], 'http') === false
            ) {
                // workaround for system paths, not optimal
                $output[0] = \str_replace(
                    ' src="',
                    ' src="'.CMS_DIR,
                    $output[0]
                );
            }

            return $output[0];
        }

        if (!isset($att['src'])) {
            return $output[0];
        }

        $src = \str_replace('&amp;', '&', $att['src']);

        unset($att['src']);

        if (\strpos($src, 'media/cache') !== false) {
            try {
                $fileData = MediaUtils::getRealFileDataFromCacheUrl($src);

                $src = QUI\Cache\Manager::get(
                    'media/cache/'.$fileData['project'].'/indexSrcCache/'.md5($fileData['filePath'])
                );
            } catch (QUI\Exception $Exception) {
                try {
                    $Image   = MediaUtils::getElement($src);
                    $src     = $Image->getUrl();
                    $project = $Image->getProject()->getName();

                    QUI\Cache\Manager::set(
                        'media/cache/'.$project.'/indexSrcCache/'.md5($Image->getAttribute('file')),
                        $src
                    );
                } catch (QUI\Exception $Exception) {
                }
            }
        }

        if (!isset($att['alt']) || !isset($att['title'])) {
            try {
                $Image = MediaUtils::getImageByUrl($src);

                $att['alt']      = $Image->getAttribute('alt') ? $Image->getAttribute('alt') : '';
                $att['title']    = $Image->getAttribute('title') ? $Image->getAttribute('title') : '';
                $att['data-src'] = $Image->getSizeCacheUrl();

                if ($Image->hasViewPermissionSet()) {
                    $src             = $Image->getUrl();
                    $att['data-src'] = $Image->getUrl();
                }
            } catch (QUI\Exception $Exception) {
            }
        }

        $html = MediaUtils::getImageHTML($src, $att);

        // workaround
        if ($this->settings['use-system-image-paths']) {
            $html = \str_replace(
                ' src="',
                ' src="'.CMS_DIR,
                $html
            );
        }

        $this->imageCache[$img] = $html;

        return $this->imageCache[$img];
    }

    /**
     * @param $output
     * @return mixed|string
     */
    protected function dataImages($output)
    {
        $output = \str_replace('&amp;', '&', $output);   // &amp; fix
        $output = \str_replace('〈=', '&lang=', $output); // URL FIX

        $components = $output[3];


        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->imageUrlCache[$components])) {
            return $output[1].'="'.$this->imageUrlCache[$components].'"';
        }

        $parseUrl = \parse_url($output[2].'?'.$components);

        if (!isset($parseUrl['query']) || empty($parseUrl['query'])) {
            return $output[0];
        }

        $urlQuery = $parseUrl['query'];

        // check no quiqqer url
        if (\strpos($urlQuery, 'project') === false || \strpos($urlQuery, 'id') === false) {
            return $output[0];
        }

        try {
            $MediaItem = MediaUtils::getMediaItemByUrl('image.php?'.$components);
        } catch (QUI\Exception $Exception) {
            return '';
        }

        if ($MediaItem->hasViewPermissionSet()) {
            return $output[1].'="'.URL_DIR.$MediaItem->getUrl().'"';
        }

        if (MediaUtils::isImage($MediaItem)) {
            $attributes = StringUtils::getUrlAttributes('?'.$components);

            if (isset($attributes['maxwidth'])) {
                $attributes['width'] = $attributes['maxwidth'];
            }

            if (isset($attributes['maxheight'])) {
                $attributes['height'] = $attributes['maxheight'];
            }

            $source = MediaUtils::getImageSource(
                'image.php?'.$components,
                $attributes
            );
        } else {
            $source = $MediaItem->getUrl(true);
        }

        $this->imageUrlCache[$components] = $source;

        return $output[1].'="'.$source.'"';
    }

    /**
     * @param $output
     * @return mixed
     */
    protected function checkPictureTag($output)
    {
        $html = $output[0];

        if (\strpos($html, '</picture>') !== false) {
            return $html;
        }

        // find image
        $html = \str_replace("\n", ' ', $html);
        $html = \preg_replace('!\s+!', ' ', $html);

        $html = \preg_replace_callback(
            '#<img([^>]*)>#i',
            [&$this, "images"],
            $html
        );

        return $html;
    }

    /**
     * Return a rewritten url from a site
     *
     * @param array $params
     * @param array $getParams
     * @return bool|string
     * @throws Exception
     */
    public function getSiteUrl($params = [], $getParams = [])
    {
        $project = false;
        $id      = false;

        // Falls ein Objekt übergeben wird
        if (isset($params['site']) && \is_object($params['site'])) {
            /* @var $Project QUI\Projects\Project */
            /* @var $Site QUI\Projects\Site */
            $Site    = $params['site'];
            $Project = $Site->getProject();
            $id      = $Site->getId();

            $lang    = $Project->getLang();
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
                /* @var $Project \QUI\Projects\Project */
            } catch (QUI\Exception $Exception) {
                return '';
            }
        }

        $linkCachePath = QUI\Projects\Site::getLinkCachePath($project, $lang, $id);

        try {
            $url = QUI\Cache\Manager::get($linkCachePath);
        } catch (\Exception $Exception) {
            $_params = [];

            if (isset($params['suffix'])) {
                $_params['suffix'] = $params['suffix'];
            }

            try {
                /* @var $Site \QUI\Projects\Site */
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

        $url = $this->extendUrlWithParams($url, $params);

        if (!$Project->hasVHost()) {
            $url = $Project->getLang().'/'.$url;
        }

        // If the output project is different than the one of the page
        // Then use absolute domain path
        if (!$this->Project ||
            $Project->toArray() != $this->Project->toArray()
        ) {
            return $Project->getVHost(true, true).URL_DIR.$url;
        }

        $vHosts = QUI::getRewrite()->getVHosts();

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

        $url = URL_DIR.$url;

        $projectHost = $Project->getHost();
        $projectHost = \str_replace(['https://', 'http://'], '', $projectHost);

        // falls host anders ist, dann muss dieser dran gehängt werden
        // damit kein doppelter content entsteht
        if (!isset($_SERVER['HTTP_HOST']) ||
            (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != $projectHost && $projectHost != '')) {
            $url = $Project->getVHost(true, true).$url;
        }

        return $url;
    }

    /**
     * Erweitert die URL um Params
     *
     * @param string $url
     * @param array $params
     *
     * @return string
     */
    protected function extendUrlWithParams($url, $params)
    {
        if (!\count($params)) {
            return $url;
        }

        $separator = Rewrite::URL_PARAM_SEPARATOR;
        $getParams = [];

        if (isset($params['_getParams']) && \is_string($params['_getParams'])) {
            \parse_str($params['_getParams'], $getParams);
            unset($params['_getParams']);
        } elseif (isset($params['_getParams']) && \is_array($params['_getParams'])) {
            $getParams = $params['_getParams'];
            unset($params['_getParams']);
        }

        if (isset($params['paramAsSites']) && $params['paramAsSites']) {
            $separator = '/';
            unset($params['paramAsSites']);
        }


        $suffix = '';
        $exp    = \explode('.', $url);
        $url    = $exp[0];

        foreach ($params as $param => $value) {
            if (\is_integer($param)) {
                $url .= $separator.$value;
                continue;
            }

            if ($param == 'suffix') {
                continue;
            }

            if ($param === "0") {
                $url .= $separator.$value;
                continue;
            }

            $url .= $separator.$param.$separator.$value;
        }

        if (isset($params['suffix'])) {
            $suffix = '.'.$params['suffix'];
        }

        if (empty($suffix) && isset($exp[1])) {
            $suffix = '.'.$exp[1];
        }

        if (empty($suffix)) {
            $suffix = QUI::getRewrite()->getDefaultSuffix();
        }

        if (empty($getParams)) {
            return $url.$suffix;
        }

        return $url.$suffix.'?'.\http_build_query($getParams);
    }
}
