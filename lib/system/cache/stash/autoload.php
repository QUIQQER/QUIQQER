<?php

/*
 * This file is part of the Stash package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function stash_auto_loader($class)
{
    //$file = __DIR__.'/src/'.strtr($class, '\\', '/').'.php';

    $file = dirname(__FILE__) .'/'. str_replace('Stash/', '', strtr($class, '\\', '/')) .'.php';

    if (file_exists($file))
    {
        require $file;
        return true;
    }

    return false;
};
