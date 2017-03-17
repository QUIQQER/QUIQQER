<?php

namespace QUI\Workspace\Search\Provider;

use QUI;
use QUI\Workspace\Search\ProviderInterface;
use QUI\Workspace\Search\Builder;
use QUI\Utils\Text\XML;
use QUI\Utils\DOM as DOMUtils;

class Projects implements ProviderInterface
{
    const GROUP_WEBSITES         = 'websites';
    const GROUP_PROJECT_SETTINGS = 'project_settings';

    /**
     * Build the cache
     *
     * @return void
     */
    public function buildCache()
    {
        $projects = QUI::getProjectManager()->getProjectList();
        $Builder  = Builder::getInstance();
        $locales  = $Builder->getLocales();

        /** @var QUI\Projects\Project $Project */
        foreach ($projects as $Project) {
            $projectName = $Project->getName();
            $projectLang = $Project->getLang();

            $entry = array(
                'id'          => $projectName,
                'title'       => $projectName . ' (' . $projectLang . ')',
                'icon'        => 'fa fa-globe',
                'groupLabel'  => QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'search.provider.projects.group.label'
                ),
                'group'       => self::GROUP_WEBSITES,
                'filterGroup' => Sites::FILTER_SITES,
                'search'      => $projectName,
                'searchdata'  => array(
                    'require' => 'controls/projects/project/Settings',
                    'params'  => array(
                        'project' => $projectName
                    )
                )
            );

            /** @var QUI\Locale $Locale */
            foreach ($locales as $Locale) {
                $Builder->addEntry($entry, $Locale->getCurrent());
                $settingsEntries = $this->getProjectSettingsSearchTerms($Project, $Locale);

                foreach ($settingsEntries as $settingsEntry) {
                    $Builder->addEntry($settingsEntry, $Locale->getCurrent());
                }
                return;
            }
        }
    }

    /**
     * Execute a search
     *
     * @param string $search
     * @param array $params
     * @return mixed
     */
    public function search($search, $params = array())
    {
    }

    /**
     * Return a search entry
     *
     * @param integer $id
     * @return mixed
     */
    public function getEntry($id)
    {
    }

    /**
     * Get all available search groups of this provider.
     * Search results can be filtered by these search groups.
     *
     * @return array
     */
    public function getFilterGroups()
    {
        return array();
    }

    /**
     * Get all search entries from project settings
     *
     * @param QUI\Projects\Project $Project
     * @param QUI\Locale $Locale
     * @return array - search strings
     */
    protected function getProjectSettingsSearchTerms($Project, $Locale)
    {
        $dataEntries = array();
        $projectName = $Project->getName();
        $description = $Locale->get(
            'quiqqer/quiqqer',
            'search.provider.projects.settings.description',
            array(
                'project' => $projectName
            )
        );

        $group      = 'settings-' . $projectName;
        $groupLabel = $Locale->get(
            'quiqqer/quiqqer',
            'search.provider.projects.settings.group.label',
            array(
                'project' => $projectName
            )
        );

        // parse standard project settings templates (HARDCODED)
        $templateFiles = array(
            SYS_DIR . 'template/project/settings.html',
            SYS_DIR . 'template/project/settingsAdmin.html',
            SYS_DIR . 'template/project/settingsMedia.html'
        );

        // prepare Engine object for parsing
        $Engine = QUI::getTemplateManager()->getEngine(true);
        $Engine->assign(array(
            'QUI'     => new \QUI(),
            'Project' => $Project
        ));

        $Doc = new \DOMDocument();

        foreach ($templateFiles as $template) {
            $html   = $Engine->fetch($template);
            $search = array(); // search terms

            $Doc->loadHTML($html);
            $Path = new \DOMXPath($Doc);

            // table headers
            $titles = $Path->query('//table/thead/tr/th');

            foreach ($titles as $Title) {
                $search[] = utf8_decode(trim(DOMUtils::getTextFromNode($Title)));
            }

            // labels
            $labels = $Path->query('//label');

            /** @var \DOMNode $Label */
            foreach ($labels as $Label) {
                $search[] = utf8_decode(trim(DOMUtils::getTextFromNode($Label)));
            }

            $templateName = basename($template, '.html');

            switch ($templateName) {
                case 'settings':
                    $text     = $Locale->get('quiqqer/system', 'projects.project.panel.settings.btn.settings');
                    $icon     = 'fa fa-gear';
                    $category = 'settings';
                    break;

                case 'settingsAdmin':
                    $text     = $Locale->get('quiqqer/system', 'projects.project.panel.settings.btn.adminSettings');
                    $icon     = 'fa fa-gear';
                    $category = 'adminSettings';
                    break;

                case 'settingsMedia':
                    $text     = $Locale->get('quiqqer/system', 'projects.project.panel.settings.btn.media');
                    $icon     = 'fa fa-picture-o';
                    $category = 'mediaSettings';
                    break;
            }

            $entry = array(
                'title'       => $text,
                'description' => $description,
                'searchdata'  => array(
                    'require' => 'controls/projects/project/Settings',
                    'params'  => array(
                        'project'  => $projectName,
                        'category' => $category
                    )
                ),
                'search'      => implode(' ', $search),
                'icon'        => $icon,
                'group'       => $group,
                'filterGroup' => SettingsCategories::TYPE_SETTINGS_CONTENT,
                'groupLabel'  => $groupLabel
            );

            $dataEntries[] = $entry;
        }

        // parse xml files that extend the standard project settings
        $xmlFiles = QUI::getProjectManager()->getRelatedSettingsXML($Project);

        foreach ($xmlFiles as $xmlFile) {
            if (!file_exists($xmlFile)) {
                QUI\System\Log::addWarning(
                    self::class . ' :: parseSearchStringFromSettingsXml -> XML file ' . $xmlFile . ' does not exist.'
                );

                continue;
            }

            $Dom        = XML::getDomFromXml($xmlFile);
            $Path       = new \DOMXPath($Dom);
            $categories = $Path->query("//settings/window/categories/category");

            /** @var \DOMElement $Category */
            foreach ($categories as $Category) {
                $category = false;

                if ($Category->hasAttribute('name')) {
                    $category = $Category->getAttribute('name');
                }

                $entry = array(
                    'searchdata'  => array(
                        'require' => 'controls/projects/project/Settings',
                        'params'  => array(
                            'project'  => $projectName,
                            'category' => $category
                        )
                    ),
                    'icon'        => '',
                    'group'       => $group,
                    'filterGroup' => SettingsCategories::TYPE_SETTINGS_CONTENT,
                    'groupLabel'  => $groupLabel
                );

                $searchStringParts = array();

                /** @var \DOMNode $Child */
                foreach ($Category->childNodes as $Child) {
                    if ($Child->nodeName == '#text') {
                        continue;
                    }

                    if ($Child->nodeName == 'title' || $Child->nodeName == 'text') {
                        $nodeText             = DOMUtils::getTextFromNode($Child);
                        $entry['title']       = $nodeText;
                        $entry['description'] = $description;
                        $searchStringParts[]  = $nodeText;
                        continue;
                    }

                    if ($Child->nodeName == 'icon') {
                        $entry['icon'] = $Child->nodeValue;
                        continue;
                    }

                    if ($Child->nodeName == 'settings') {
                        /** @var \DOMNode $SettingChild */
                        foreach ($Child->childNodes as $SettingChild) {
                            if ($SettingChild->nodeName == 'title' || $SettingChild->nodeName == 'text') {
                                $searchStringParts[] = DOMUtils::getTextFromNode($SettingChild);
                                continue;
                            }

                            if ($SettingChild->nodeName == 'title' || $SettingChild->nodeName == 'text') {
                                $searchStringParts[] = DOMUtils::getTextFromNode($SettingChild);
                                continue;
                            }

                            if ($SettingChild->hasChildNodes()) {
                                foreach ($SettingChild->childNodes as $SettingInputChild) {
                                    if ($SettingInputChild->nodeName == 'title' || $SettingInputChild->nodeName == 'text') {
                                        $searchStringParts[] = DOMUtils::getTextFromNode($SettingInputChild);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                $entry['search'] = implode(' ', $searchStringParts);
                $dataEntries[]   = $entry;
            }
        }

        return $dataEntries;
    }
}
