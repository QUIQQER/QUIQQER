<?php

/**
 * Return XML Window fromm a xml settings file
 *
 * @param string $file - Path to file, or JSON Array with xml files
 * @return Array
 */
function ajax_settings_window($file)
{
    $cacheName = 'qui/admin/menu/windows/' . md5($file);

    try
    {
        return QUI\Cache\Manager::get($cacheName);

    } catch (QUI\Exception $Exception) {

    }

    $files     = array();
    $jsonFiles = json_decode($file, true);

    if ($jsonFiles) {

        if (is_string($jsonFiles)) {
            $files = array($jsonFiles);
        } else {
            $files = $jsonFiles;
        }
    }

    if (empty($files) || !$jsonFiles) {
        $files = array($file);
    }

    $Window = null;

    foreach ($files as $file) {

        if (!file_exists($file)) {
            continue;
        }

        $Win = \QUI\Utils\DOM::parseDomToWindow(
            \QUI\Utils\XML::getDomFromXml($file)
        );

        if (!$Window) {
            $Window = $Win;
            continue;
        }

        $categories = $Win->getCategories();

        /* @var $Window QUI\Controls\Windows\Window */
        foreach ($categories as $Category) {
            $Window->appendCategory($Category);
        }

        if (!$Window->getAttribute('title')) {
            $Window->setAttribute('title', $Win->getAttribute('title'));
        }

        if (!$Window->getAttribute('icon')) {
            $Window->setAttribute('icon', $Win->getAttribute('icon'));
        }
    }

    if (!$Window) {
        return array();
    }

    // sort categories
    $categories = $Window->getCategories();

    usort($categories, function($CatA, $CatB) {
        $indexA = $CatA->getAttribute('index');
        $indexB = $CatB->getAttribute('index');

        if (!$indexA) {
            $indexA = 1;
        }

        if (!$indexB) {
            $indexB = 1;
        }

        return $indexA > $indexB;
    });

    $Window->clearCategories();

    foreach ($categories as $Category) {
        $Window->appendCategory($Category);
    }

    $result = $Window->toArray();

    QUI\Cache\Manager::set($cacheName, $result);

    return $result;
}

QUI::$Ajax->register(
    'ajax_settings_window',
    array('file'),
    'Permission::checkAdminUser'
);
