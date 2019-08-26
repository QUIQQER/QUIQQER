<?php

QUI::$Ajax->registerFunction(
    'ajax_media_folder_stripName',
    function ($name) {
        return QUI\Projects\Media\Utils::stripFolderName($name);
    },
    array('name')
);
