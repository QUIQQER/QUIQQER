<?php

/**
 * This file contains the \QUI\Utils\Site
 */

namespace QUI\Utils;

use QUI;

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
    public static function setRecursiveAttribute(\QUI\Interfaces\Projects\Site $Site, $attribute)
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
}
