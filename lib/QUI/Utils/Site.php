<?php

/**
 * This file contains the \QUI\Utils\Site
 */

namespace QUI\Utils;

use QUI;

use function explode;
use function implode;
use function str_replace;
use function strlen;
use function strpos;
use function substr;

/**
 * QUIQQER Site Util class
 *
 * Provides methods for \QUI\Projects\Site manipulation
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Site
{
    /**
     * Set a attribute recursive from its parents if the attribute is not set
     *
     * @param \QUI\Interfaces\Projects\Site $Site
     * @param string $attribute
     */
    public static function setRecursiveAttribute(\QUI\Interfaces\Projects\Site $Site, string $attribute)
    {
        $value = $Site->getAttribute($attribute);

        if (!empty($value)) {
            return;
        }

        $Parent = $Site->getParent();

        while ($Parent) {
            $value = $Parent->getAttribute($attribute);

            if (!empty($value)) {
                $Site->setAttribute($attribute, $value);
                break;
            }

            if (!$Parent->getParentId()) {
                break;
            }

            $Parent = $Parent->getParent();
        }
    }

    /**
     * Alias for setRecursiveAttribute
     *
     * @param \QUI\Interfaces\Projects\Site $Site
     * @param $attribute
     *
     * @deprecated use setRecursiveAttribute
     */
    public static function setRecursivAttribute(\QUI\Interfaces\Projects\Site $Site, $attribute)
    {
        self::setRecursiveAttribute($Site, $attribute);
    }

    /**
     * @param \QUI\Projects\Site $Site
     * @return string
     */
    public static function getChildType(\QUI\Projects\Site $Site): string
    {
        $Project     = $Site->getProject();
        $siteTypes   = QUI::getPackageManager()->getAvailableSiteTypes($Project);
        $currentType = $Site->getAttribute('type');

        foreach ($siteTypes as $module) {
            foreach ($module as $siteType) {
                if (!isset($siteType['type'])) {
                    continue;
                }

                if ($currentType === $siteType['type']) {
                    if (!empty($siteType['childrenType'])) {
                        return $siteType['childrenType'];
                    }
                }
            }
        }

        return 'standard';
    }

    /**
     * Tries to find the matching site based on a URL
     *
     * @param $url
     *
     * @return \QUI\Projects\Site
     * @throws \QUI\Exception
     */
    public static function getSiteByUrl($url): QUI\Projects\Site
    {
        if (empty($url)) {
            throw new QUI\Exception('Site not found', 404);
        }

        $project = '';
        $lang    = '';

        $urlParts      = explode('/', $_REQUEST['_url']);
        $defaultSuffix = QUI\Rewrite::getDefaultSuffix();

        // fetch project
        if (isset($urlParts[0])
            && substr($urlParts[0], 0, 1) == QUI\Rewrite::URL_PROJECT_CHARACTER
        ) {
            $project = str_replace(
                $defaultSuffix,
                '',
                substr($urlParts[0], 1)
            );

            // if a second project_character, it's the template
            if (strpos($project, QUI\Rewrite::URL_PROJECT_CHARACTER)) {
                $split = explode(
                    QUI\Rewrite::URL_PROJECT_CHARACTER,
                    $project
                );

                $project = $split[0];
                //$template = $split[1];
            }

            unset($urlParts[0]);

            $cleanup = [];

            foreach ($urlParts as $elm) {
                $cleanup[] = $elm;
            }

            $urlParts = $cleanup;
        }

        // fetch language
        if (isset($urlParts[0])
            && (strlen($urlParts[0]) == 2 || strlen(str_replace($defaultSuffix, '', $urlParts[0])) == 2)
        ) {
            $lang    = str_replace($defaultSuffix, '', $urlParts[0]);
            $cleanup = [];

            foreach ($urlParts as $elm) {
                $cleanup[] = $elm;
            }

            $urlParts = $cleanup;
        }

        // initialize project
        if (!empty($project) && !empty($lang)) {
            $Project = QUI\Projects\Manager::getProject(
                $project,
                $lang
            );
        } elseif (empty($project) && !empty($lang)) {
            $Default = QUI\Projects\Manager::getStandard();
            $Project = QUI\Projects\Manager::getProject(
                $Default->getName(),
                $lang
            );
        } else {
            $Project = QUI::getRewrite()->getProject();
        }

        $url = implode('/', $urlParts);

        try {
            return QUI\Projects\Site\Utils::getSiteByUrl($Project, $url);
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception('Site not found', 404);
        }
    }
}
