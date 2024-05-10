<?php

QUI::$Ajax->registerFunction(
    'ajax_media_folder_stripName',
    static fn($name): string => QUI\Projects\Media\Utils::stripFolderName($name),
    ['name']
);
