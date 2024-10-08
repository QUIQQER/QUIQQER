<?php

namespace QUI\System;

use DusanKasan\Knapsack\Collection;
use QUI;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use function file_exists;
use function file_put_contents;
use function is_array;
use function trim;

/**
 * Class Forwarding
 */
class Forwarding
{
    /**
     * Create a forwarding entry
     *
     * @throws QUI\Exception
     */
    public static function create(string $from, string $target, int|string $httpCode = 301): void
    {
        $config = self::getConfig()->toArray();

        if (isset($config[$from])) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.forwarding.already.exists'
            ]);
        }

        if (empty($httpCode)) {
            $httpCode = 301;
        }

        self::getConfig()->setValue($from, 'target', $target);
        self::getConfig()->setValue($from, 'code', $httpCode);
        self::getConfig()->save();
    }

    /**
     * Return the forwarding config
     *
     * @throws QUI\Exception
     */
    public static function getConfig(): QUI\Config
    {
        if (!file_exists(CMS_DIR . 'etc/forwarding.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/forwarding.ini.php', '');
        }

        return QUI::getConfig('etc/forwarding.ini.php');
    }

    /**
     * Update a forwarding entry
     *
     * @throws QUI\Exception
     */
    public static function update(string $from, string $target, int|string $httpCode = 301): void
    {
        $config = self::getConfig()->toArray();

        if (!isset($config[$from])) {
            throw new QUI\Exception(
                [
                    'quiqqer/core',
                    'exception.forwarding.not.found'
                ],
                404
            );
        }

        if (empty($httpCode)) {
            $httpCode = 301;
        }

        self::getConfig()->setValue($from, 'target', $target);
        self::getConfig()->setValue($from, 'code', $httpCode);
        self::getConfig()->save();
    }

    /**
     * Löscht ein forwarding eintrag
     *
     * @throws QUI\Exception
     */
    public static function delete(array|string $from): void
    {
        if (is_array($from)) {
            foreach ($from as $f) {
                self::getConfig()->del($f);
            }
        } else {
            self::getConfig()->del($from);
        }

        self::getConfig()->save();
    }

    /**
     * Return the list
     */
    public static function getList(): Collection
    {
        try {
            return new Collection(self::getConfig()->toArray());
        } catch (QUI\Exception $Exception) {
            Log::writeException($Exception);
        }

        return new Collection([]);
    }

    /**
     * Forward,
     * If the Request matches to one of the global forwarding,
     * it forward the request and cancel the current
     */
    public static function forward(Request $Request): void
    {
        $list = [];

        try {
            $list = self::getConfig()->toArray();
        } catch (QUI\Exception $Exception) {
            Log::writeException($Exception);
        }

        $uri = $Request->getRequestUri();
        $host = $Request->getSchemeAndHttpHost();

        $request = $host . $uri;

        // directly found
        if (isset($list[$request])) {
            self::redirect($list[$request]);
        }

        if (isset($list[trim($request, '/')])) {
            self::redirect($list[trim($request, '/')]);
        }


        // search
        foreach ($list as $from => $params) {
            if (!QUI\Utils\StringHelper::match($from, $request)) {
                continue;
            }

            self::redirect($params);
        }
    }

    /**
     * Execute a redirection
     */
    protected static function redirect(array $data): never
    {
        $target = $data['target'];
        $code = (int)$data['code'];

        if (empty($target)) {
            $target = URL_DIR;
        }

        if (!$code) {
            $code = 301;
        }

        $Redirect = new RedirectResponse($target);
        $Redirect->setStatusCode($code);

        echo $Redirect->getContent();
        $Redirect->send();
        exit;
    }
}
