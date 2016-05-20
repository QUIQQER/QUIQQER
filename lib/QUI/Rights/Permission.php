<?php

/**
 * This file contains \QUI\Rights\Permission
 */

namespace QUI\Rights;

use QUI;

/**
 * Provides methods for quick rights checking
 *
 * all methods with check throws Exceptions
 * all methods with is or has return the permission value
 *     it makes a check and capture the exceptions
 *
 * all methods with get return the permission entries
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @deprecated QUI\Permissions\Permission
 */
class Permission extends QUI\Permissions\Permission
{
}
