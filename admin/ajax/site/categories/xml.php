<?php

/**
 * Return the tabs / category html
 *
 * @param string $project
 * @param string $id
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_site_categories_xml',
    function ($project, $id, $category) {
        $Project = QUI::getProjectManager()->decode($project);
        $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);
        $type    = $Site->getAttribute('type');

        $cacheName = 'quiqqer/package/quiqqer/quiqqer/admin/site/categories/'.$type.'/'.$category;
        $exception = false;

        try {
            return QUI\Cache\Manager::get($cacheName);
        } catch (QUI\Exception $Exception) {
        }


        $result   = '';
        $Settings = QUI\Utils\XML\Settings::getInstance();

        // site type tabs
        $types = \explode(':', $type);
        $file  = OPT_DIR.$types[0].'/site.xml';

        if (\file_exists($file)) {
            try {
                $Settings->setXMLPath(
                    "//site/types/type[@type='".$types[1]."']/tab[@name='".$category."']"
                );

                $result .= $Settings->getCategoriesHtml($file);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $exception = true;
            }


            try {
                $Settings->setXMLPath(
                    "//site/types/type[@type='".$type."']/tab[@name='".$category."']"
                );

                $result .= $Settings->getCategoriesHtml($file);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $exception = true;
            }
        }

        $packages = QUI::getPackageManager()->getInstalled();
        $files    = [];

        foreach ($packages as $package) {
            // templates would be separated
            if ($package['type'] == 'quiqqer-template') {
                continue;
            }

            if ($package['name'] == $types[0]) {
                continue;
            }

            $file = OPT_DIR.$package['name'].'/site.xml';

            if (\file_exists($file)) {
                $files[] = $file;
            }
        }

        if (\count($files)) {
            try {
                $Settings->setXMLPath("//site/window/tab[@name='".$category."']");

                $result .= $Settings->getCategoriesHtml($files);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $exception = true;
            }

            try {
                $Settings->setXMLPath(
                    "//site/types/type[@type='".$type."']/tab[@name='".$category."']"
                );

                $result .= $Settings->getCategoriesHtml($files);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $exception = true;
            }
        }

        if ($exception === false) {
            QUI\Cache\Manager::set($cacheName, $result);
        }

        return $result;
    },
    ['project', 'id', 'category'],
    'Permission::checkAdminUser'
);
