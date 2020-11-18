<?php

/**
 * This file contains QUI\Projects\Media\ErrorCodes
 */

namespace QUI\Projects\Media;

/**
 * Class ErrorCodes
 */
class ErrorCodes
{
    const ALREADY_DELETED = 1112;
    const NOT_AN_IMAGE_URL = 1116;
    const NOT_AN_ITEM_URL = 1117;
    const NOT_AN_IMAGE = 1118;

    const FOLDER_ALREADY_DELETED = 1100;
    const FOLDER_ALREADY_EXISTS = 1102;
    const FOLDER_HAS_NO_FILES = 1103;
    const FOLDER_HAS_NO_IMAGES = 1104;
    const FOLDER_CACHE_CREATION_MKDIR_ERROR = 1106;
    const FOLDER_ERROR_CREATION = 1107;
    const FOLDER_NAME_INVALID = 1109;
    const FOLDER_ILLEGAL_CHARACTERS = 1119;

    const FILE_NOT_FOUND = 1105;
    const FILE_ALREADY_EXISTS = 1108;
    const FILE_IMAGE_CORRUPT = 1111;
    const FILE_CANT_DELETED = 1113;
    const FILE_CANT_DESTROYED = 1114;

    const ROOT_FOLDER_CANT_DELETED = 1101;
    const ROOT_FOLDER_CANT_RENAMED = 1110;

    const FILE_IN_TRASH_NOT_FOUND = 1115;
}
