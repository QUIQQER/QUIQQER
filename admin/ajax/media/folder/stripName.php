<?php

QUI::$Ajax->registerFunction(
    'ajax_media_folder_stripName',
    fn($name) => QUI\Projects\Media\Utils::stripFolderName($name),
    ['name']
);
