<?php

/**
 *
 */
function ajax_settings_category($file, $category)
{
    $file = SYS_DIR . $file;

    if ( !file_exists( $file ) ) {
        return '';
    }

    $Category = Utils_Xml::getSettingCategoriesFromXml( $file, $category );

    if ( !$Category ) {
        return '';
    }

    return Utils_Dom::parseCategorieToHTML( $Category );
}

\QUI::$Ajax->register(
    'ajax_settings_category',
    array( 'file', 'category' ),
    'Permission::checkAdminUser'
);
