<?php

/**
 * This file contains QUI\Output
 */

namespace QUI;

use QUI;
use QUI\Utils\Singleton;

use QUI\Utils\StringHelper as StringUtils;
use QUI\Projects\Media\Utils as MediaUtils;

/**
 * Class Output
 *
 * @package QUI
 */
class Output extends Singleton
{
    /**
     * Current output project
     * @var null|QUI\Projects\Project
     */
    protected $Project = null;

    /**
     * internal lifetime image cache
     * @var array
     */
    protected $imageCache = [];

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
        'use-system-image-paths' => false
    ];

    /**
     * @param $content
     * @return mixed
     */
    public function parse($content)
    {
        // Bilder umschreiben
        $content = preg_replace_callback(
            '#<img([^>]*)>#i',
            [&$this, "images"],
            $content
        );

        // restliche Dateien umschreiben
        $content = preg_replace_callback(
            '#(href|src|value)="(image.php)\?([^"]*)"#',
            [&$this, "files"],
            $content
        );

        //Links umschreiben
        $content = preg_replace_callback(
            '#(href|src|action|value|data\-.*)="(index.php)\?([^"]*)"#',
            [&$this, "links"],
            $content
        );

        return $content;
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

        $output = str_replace('&amp;', '&', $output);   // &amp; fix
        $output = str_replace('〈=', '&lang=', $output); // URL FIX

        $components = $output[3];

        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->linkCache[$components])) {
            return $output[1].'="'.$this->linkCache[$components].'"';
        }

        $parseUrl = parse_url($output[2].'?'.$components);

        if (!isset($parseUrl['query']) || empty($parseUrl['query'])) {
            return $output[0];
        }

        $urlQuery = $parseUrl['query'];

        if (strpos($urlQuery, 'project') === false
            || strpos($urlQuery, 'lang') === false
            || strpos($urlQuery, 'id') === false
        ) {
            // no quiqqer url
            return $output[0];
        }

        // maybe a quiqqer url ?
        parse_str($urlQuery, $urlQueryParams);

        try {
            $url    = $this->getSiteUrl($urlQueryParams);
            $anchor = '';

            if (isset($parseUrl['fragment']) && !empty($parseUrl['fragment'])) {
                $anchor = '#'.$parseUrl['fragment'];
            }

            $this->linkCache[$components] = $url.$anchor;

            return $output[1].'="'.$url.$anchor.'"';
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
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
        } catch (QUI\Exception $Excxeption) {
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

        // Falls in der eigenen Sammlung schon vorhanden
        if (isset($this->imageCache[$img])) {
            return $this->imageCache[$img];
        }

        if (!MediaUtils::isMediaUrl($img)) {
            // is relative url from the system?

            if ($this->settings['use-system-image-paths']
                && strpos($output[0], 'http') === false
            ) {
                // workaround for system paths, not optimal
                $output[0] = str_replace(
                    ' src="',
                    ' src="'.CMS_DIR,
                    $output[0]
                );
            }

            return $output[0];
        }

        $att = StringUtils::getHTMLAttributes($img);

        if (!isset($att['src'])) {
            return $output[0];
        }

        $src = str_replace('&amp;', '&', $att['src']);

        unset($att['src']);

        if (!isset($att['alt']) || !isset($att['title'])) {
            try {
                $Image = MediaUtils::getImageByUrl($src);

                $att['alt']      = $Image->getAttribute('alt') ? $Image->getAttribute('alt') : '';
                $att['title']    = $Image->getAttribute('title') ? $Image->getAttribute('title') : '';
                $att['data-src'] = $Image->getSizeCacheUrl();
            } catch (QUI\Exception $Exception) {
            }
        }

        $html = MediaUtils::getImageHTML($src, $att);

        // workaround
        if ($this->settings['use-system-image-paths']) {
            $html = str_replace(
                ' src="',
                ' src="'.CMS_DIR,
                $html
            );
        }

        $this->imageCache[$img] = $html;

        return $this->imageCache[$img];
    }

    /**
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
        if (isset($params['site']) && is_object($params['site'])) {
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

        // Wenn nicht alles da ist dann wird ein Exception geworfen
        if ($id === false || $project === false) {
            throw new QUI\Exception(
                'Params missing Rewrite::getUrlFromPage'
            );
        }

        if (!isset($lang)) {
            $lang = '';
        }

        QUI\Utils\System\File::mkdir(VAR_DIR.'cache/links');

        $link_cache_dir = VAR_DIR.'cache/links/'.$project.'/';
        QUI\Utils\System\File::mkdir($link_cache_dir);

        $link_cache_file = $link_cache_dir.$id.'_'.$project.'_'.$lang;

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

        // Falls es das Cachefile schon gibt
        if (file_exists($link_cache_file)) {
            $url = file_get_contents($link_cache_file);
            $url = $this->extendUrlWithParams($url, $params);
        } else {
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

            // Link Cache
            file_put_contents(
                $link_cache_file,
                str_replace(
                    '.print',
                    QUI::getRewrite()->getDefaultSuffix(),
                    $Site->getLocation($_params)
                )
            );

            $url = $Site->getLocation($_params);
            $url = $this->extendUrlWithParams($url, $params);
        }

        // Wenn das Output Projekt anders ist, wie das der Seite
        // Dann absoluten Domain Pfad verwenden
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

        // falls host anders ist, dann muss dieser dran gehängt werden
        // damit kein doppelter content entsteht
        if ($_SERVER['HTTP_HOST'] != $Project->getHost() && $Project->getHost() != '') {
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
        $exp    = explode('.', $url);
        $url    = $exp[0];

        foreach ($params as $param => $value) {
            if (is_integer($param)) {
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

        return $url.$suffix.'?'.http_build_query($getParams);
    }
}
