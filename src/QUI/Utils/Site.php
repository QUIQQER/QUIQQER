<?php

/**
 * This file contains the \QUI\Utils\Site
 */

namespace QUI\Utils;

use QUI;
use QUI\Exception;

use function explode;
use function implode;
use function ltrim;
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
     * Set an attribute recursive from its parents if the attribute is not set
     */
    public static function setRecursiveAttribute(QUI\Interfaces\Projects\Site $Site, string $attribute): void
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
     * Get the child type of given site.
     *
     * @param QUI\Projects\Site $Site The site object to get the child type from.
     *
     * @return string The child type of the site.
     */
    public static function getChildType(QUI\Projects\Site $Site): string
    {
        $siteTypes = QUI::getPackageManager()->getAvailableSiteTypes();
        $currentType = $Site->getAttribute('type');

        foreach ($siteTypes as $module) {
            foreach ($module as $siteType) {
                if (!isset($siteType['type'])) {
                    continue;
                }

                if ($currentType !== $siteType['type']) {
                    continue;
                }

                if (empty($siteType['childrenType'])) {
                    continue;
                }

                return $siteType['childrenType'];
            }
        }

        return 'standard';
    }

    /**
     * Returns the value of the childrenNavHide attribute for the given Site.
     * If the attribute is not set, returns 0.
     *
     * @param QUI\Projects\Site $Site The Site object for which to get the childrenNavHide value
     *
     * @return int The value of the childrenNavHide attribute for the Site
     */
    public static function getChildNaveHide(QUI\Projects\Site $Site): int
    {
        $siteTypes = QUI::getPackageManager()->getAvailableSiteTypes();
        $currentType = $Site->getAttribute('type');

        foreach ($siteTypes as $module) {
            foreach ($module as $siteType) {
                if (!isset($siteType['type'])) {
                    continue;
                }

                if ($currentType !== $siteType['type']) {
                    continue;
                }

                if (!isset($siteType['childrenNavHide'])) {
                    continue;
                }

                return (int)$siteType['childrenNavHide'];
            }
        }

        return 0;
    }

    /**
     * @throws Exception
     */
    public static function getSiteByUrl($url): QUI\Projects\Site
    {
        if (empty($url)) {
            throw new Exception('Site not found', 404);
        }

        $project = '';
        $lang = '';

        $url = ltrim($url, '/');

        $urlParts = explode('/', $url);
        $defaultSuffix = QUI\Rewrite::getDefaultSuffix();

        // fetch project
        if (
            isset($urlParts[0])
            && substr($urlParts[0], 0, 1) === QUI\Rewrite::URL_PROJECT_CHARACTER
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

            // Reset the array index, so that it starts at zero again (TODO: consider using "array_shift" instead of "unset")
            foreach ($urlParts as $elm) {
                $cleanup[] = $elm;
            }

            $urlParts = $cleanup;
        }

        // fetch language
        if (
            isset($urlParts[0])
            && (strlen($urlParts[0]) == 2 || strlen(str_replace($defaultSuffix, '', $urlParts[0])) == 2)
        ) {
            $lang = str_replace($defaultSuffix, '', $urlParts[0]);
            $cleanup = [];

            unset($urlParts[0]);

            // Reset the array index, so that it starts at zero again (TODO: consider using "array_shift" instead of "unset")
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
        } catch (Exception) {
            throw new Exception('Site not found', 404);
        }
    }
}
