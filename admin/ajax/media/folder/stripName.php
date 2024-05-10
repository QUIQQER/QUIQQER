<?php

QUI::$Ajax->registerFunction(
    'ajax_media_folder_stripName',
    static fn($name) => QUI\Projects\Media\Utils::stripFolderName($name),
    ['name']
);
