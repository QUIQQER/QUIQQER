<?php

/**
 * This file contains QUI\Projects\Media\ErrorCodes
 */

namespace QUI\Projects\Media;

/**
 * Class ErrorCodes
 *
 * @package QUI\Projects\Media
 */
class ErrorCodes
{
    const FOLDER_ALREADY_DELETED = 1100;
    const FOLDER_ALREADY_EXISTS = 1102;
    const FOLDER_HAS_NO_FILES = 1103;
    const FOLDER_HAS_NO_IMAGES = 1104;
    const FOLDER_CACHE_CREATION_MKDIR_ERROR = 1106;
    const FOLDER_ERROR_CREATION = 1107;
    const FOLDER_NAME_INVALID = 1109;

    const FILE_NOT_FOUND = 1105;
    const FILE_ALREADY_EXISTS = 1108;
    const FILE_IMAGE_CORRUPT = 1111;

    const ROOT_FOLDER_CANT_DELETED = 1101;
    const ROOT_FOLDER_CANT_RENAMED = 1110;
}
