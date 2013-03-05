<?php

function ajax_settings_save($file, $params)
{
    $file = SYS_DIR . $file;

    if ( !file_exists( $file ) )
    {
        throw new QException(
            'Could not save the data. the config file was not found'
        );
    }

    Utils_Xml::setConfigFromXml(
        $file,
        json_decode( $params, true )
    );

    QUI::getMessagesHandler()->addSuccess(
    	'Konfiguration erfolgreich gespeichert'
    );
}

QUI::$Ajax->register(
	'ajax_settings_save',
    array( 'file', 'params' ),
    'Permission::checkAdminUser'
);

?>