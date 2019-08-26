<?php

/**
 * Return the project panel categories / tabs
 *
 * @param string $project - name of the project
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_project_panel_categories_get',
    function ($project) {
        $Project = QUI::getProjectManager()->decode($project);

        $buttonList  = [];
        $settingsXml = QUI::getProjectManager()->getRelatedSettingsXML($Project);

        // read template config
        foreach ($settingsXml as $file) {
            if (!\file_exists($file)) {
                continue;
            }

            $windows = QUI\Utils\Text\XML::getProjectSettingWindowsFromXml($file);

            foreach ($windows as $Window) {
                $buttons = QUI\Utils\DOM::getButtonsFromWindow($Window);

                foreach ($buttons as $Button) {
                    /* @var $Button QUI\Controls\Buttons\Button */
                    $Button->setAttribute('file', $file);

                    $buttonList[] = $Button->toArray();
                }
            }
        }

        return $buttonList;
    },
    ['project', 'lang'],
    'Permission::checkAdminUser'
);
