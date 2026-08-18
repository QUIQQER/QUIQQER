<?php

QUI::getAjax()->registerFunction(
    'ajax_media_folder_stripName',
    static function ($name): string {
        return QUI\Projects\Media\Utils::stripFolderName($name);
    },
    ['name']
);
