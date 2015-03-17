<?php

/**
 * Return the project panel categories / tabs
 *
 * @param String $project - name of the project
 * @return Array
 */
function ajax_project_panel_categories_get($project)
{
    $Project = QUI::getProjectManager()->decode( $project );

    $buttonList  = array();
    $settingsXml = QUI::getProjectManager()->getRelatedSettingsXML( $Project );

    // read template config
    foreach ( $settingsXml as $file )
    {
        if ( !file_exists( $file ) ) {
            continue;
        }

        $windows = QUI\Utils\XML::getProjectSettingWindowsFromXml( $file );

        foreach ( $windows as $Window )
        {
            $buttons = QUI\Utils\DOM::getButtonsFromWindow( $Window );

            foreach ( $buttons as $Button )
            {
                $Button->setAttribute( 'file', $file );

                $buttonList[] = $Button->toArray();
            }
        }
    }

    return $buttonList;
}

QUI::$Ajax->register(
    'ajax_project_panel_categories_get',
    array( 'project', 'lang' ),
    'Permission::checkAdminUser'
);
