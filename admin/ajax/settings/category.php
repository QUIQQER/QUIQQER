<?php

/**
 *
 */
function ajax_settings_category($file, $category)
{
    if ( !file_exists( $file ) ) {
        return '';
    }

    $Category = \QUI\Utils\XML::getSettingCategoriesFromXml( $file, $category );

    if ( !$Category ) {
        return '';
    }

    return \QUI\Utils\DOM::parseCategorieToHTML( $Category );
}

\QUI::$Ajax->register(
    'ajax_settings_category',
    array( 'file', 'category' ),
    'Permission::checkAdminUser'
);
