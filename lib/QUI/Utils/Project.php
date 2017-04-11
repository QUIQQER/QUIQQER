<?php

/**
 * This file contains QUI\Utils\Project
 */

namespace QUI\Utils;

use QUI;

/**
 * Class Project
 *
 * @package QUI\Utils
 */
class Project
{
    /**
     * Create the default structure for a project
     *
     * @param QUI\Projects\Project $Project
     */
    public static function createDefaultStructure(QUI\Projects\Project $Project)
    {
        $languages = $Project->getLanguages();

        foreach ($languages as $language) {
            try {
                self::createDefaultStructureForProjectLanguage(
                    QUI::getProject($Project->getName(), $language)
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeRecursive($Exception);
            }
        }
    }

    /**
     * Create the default structure for a specific project language
     *
     * @param QUI\Projects\Project $Project
     */
    protected static function createDefaultStructureForProjectLanguage(QUI\Projects\Project $Project)
    {
        $First = $Project->firstChild();
        $First = $First->getEdit();

        if (!$First->getAttribute('layout')
            || $First->getAttribute('layout') === ''
        ) {
            $First->setAttribute('layout', 'layout/startpage');
            $First->save();
        }


        // Search
        $searchType = 'quiqqer/sitetypes:types/search';

        try {
            QUI::getPackage('quiqqer/search');
            $searchType = 'quiqqer/sitetypes:types/search';
        } catch (QUI\Exception $Exception) {
        }

        $search = $Project->getSitesIds(array(
            'where' => array(
                'active' => -1,
                'type'   => $searchType
            ),
            'limit' => 1
        ));

        if (empty($search)) {
            try {
                $searchId = $First->createChild(array(
                    'name'  => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.search.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.search.title',
                        $Project
                    )
                ));

                $Search = new QUI\Projects\Site\Edit($Project, $searchId);
                $Search->setAttribute('type', $searchType);
                $Search->save();
                $Search->activate();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }


        // Im print / legalnotes / Impressum
        $legalNotes = $Project->getSitesIds(array(
            'where' => array(
                'active' => -1,
                'type'   => 'quiqqer/sitetypes:types/legalnotes'
            ),
            'limit' => 1
        ));

        if (empty($legalNotes)) {
            try {
                $legalNoteId = $First->createChild(array(
                    'name'  => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.legalnotes.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.legalnotes.name',
                        $Project
                    )
                ));

                $Legal = new QUI\Projects\Site\Edit($Project, $legalNoteId);
                $Legal->setAttribute('type', 'quiqqer/sitetypes:types/legalnotes');
                $Legal->save();
                $Legal->activate();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // AGB / generalTermsAndConditions
        $generalTermsAndConditions = $Project->getSitesIds(array(
            'where' => array(
                'active' => -1,
                'type'   => 'quiqqer/sitetypes:types/generalTermsAndConditions'
            ),
            'limit' => 1
        ));

        if (empty($generalTermsAndConditions)) {
            try {
                $generalTermsAndConditionsId = $First->createChild(array(
                    'name'  => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.generalTermsAndConditions.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.generalTermsAndConditions.name',
                        $Project
                    )
                ));

                $GTC = new QUI\Projects\Site\Edit($Project, $generalTermsAndConditionsId);
                $GTC->setAttribute('type', 'quiqqer/sitetypes:types/generalTermsAndConditions');
                $GTC->save();
                $GTC->activate();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }


        // Datenschutzerklärung / privacypolicy
        $privacyPolicy = $Project->getSitesIds(array(
            'where' => array(
                'active' => -1,
                'type'   => 'quiqqer/sitetypes:types/privacypolicy'
            ),
            'limit' => 1
        ));

        if (empty($privacyPolicy)) {
            try {
                $privacyPolicyId = $First->createChild(array(
                    'name'  => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.privacypolicy.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.privacypolicy.name',
                        $Project
                    )
                ));

                $Legal = new QUI\Projects\Site\Edit($Project, $privacyPolicyId);
                $Legal->setAttribute('type', 'quiqqer/sitetypes:types/privacypolicy');
                $Legal->save();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }
    }

    /**
     * parse a locale string that no url error exists
     *
     * @param string $group
     * @param string $var
     * @param QUI\Projects\Project $Project
     * @return array|string
     */
    protected static function parseForUrl($group, $var, QUI\Projects\Project $Project)
    {
        $str = QUI::getLocale()->getByLang($Project->getLang(), $group, $var);
        $str = QUI\Projects\Site\Utils::clearUrl($str, $Project);

        return $str;
    }
}
