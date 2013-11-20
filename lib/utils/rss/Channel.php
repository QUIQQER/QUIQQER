<?php


/**
 * This file contains the Utils_Rss_Channel
 */

/**
 * A feed Item
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Scholz)
 * @package com.pcsg.qui.utils.feed
 *
 * @todo code style
 * @todo documentation in english
 */

class Utils_Rss_Channel extends \QUI\QDOM
{
    /**
     * subitems
     * @var array
     */
    protected $_items = array();

    /**
     * Ein Kind hinzufügen
     *
     * @param Utils_Rss_Item $itm
     */
    public function appendChild(Utils_Rss_Item $itm)
    {
        $this->_items[] = $itm;
    }

    /**
     * Leeert die Kinder
     */
    public function clear()
    {
        $this->_items = array();
    }

    /**
     * Erstellt das XML
     *
     * @return String
     */
    public function create()
    {
        $items = '';

        foreach ($this->_items as $item)
        {
            $item->setAttribute('type', $this->getAttribute('type'));
            $items .= $item->create();
        }

        switch ($this->getAttribute('type'))
        {
            case "Facebook":
            case "ATOM":
                $channel = '<feed xmlns="http://www.w3.org/2005/Atom">'. $items .'</feed>';
            break;

            case "GoogleSitemap":
                $channel = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'. $items .'</urlset>';
            break;

            case "XmlSitemap":
                $channel = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
                                xmlns:n="http://www.google.com/schemas/sitemap-news/0.9">'. $items .'</urlset>';
            break;

            default:
                $link = $this->getAttribute('link');
                $entities     = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
                $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
                $link         = str_replace($entities, $replacements, urlencode($link));

                $channel = '<channel>
                    <title>'. htmlspecialchars($this->getAttribute('title')) .'</title>
                    <link>'. $link .'</link>
                    <description>' . htmlspecialchars($this->getAttribute('description')) . '</description>
                    <language>' . $this->getAttribute('language') . '</language>
                    <copyright>' . $this->getAttribute('copyright') . '</copyright>
                    <atom:link href="'. $link .'" rel="self" type="application/rss+xml" />
                '. $items .'</channel>';
            break;
        }

        return $channel;
    }
}

?>