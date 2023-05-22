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
     * @param string $from
     * @param string $target
     * @param string|int $httpCode
     *
     * @throws QUI\Exception
     */
    public static function create(string $from, string $target, $httpCode = 301)
    {
        $config = self::getConfig()->toArray();

        if (isset($config[$from])) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
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
     * Update a forwarding entry
     *
     * @param string $from
     * @param string $target
     * @param string|int $httpCode
     *
     * @throws QUI\Exception
     */
    public static function update(string $from, string $target, $httpCode = 301)
    {
        $config = self::getConfig()->toArray();

        if (!isset($config[$from])) {
            throw new QUI\Exception(
                [
                    'quiqqer/quiqqer',
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
     * @param string|array $from
     * @throws QUI\Exception
     */
    public static function delete($from)
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
     * Return the forwarding config
     *
     * @return QUI\Config
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
     * Return the list
     *
     * @return Collection
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
     *
     * @param Request $Request
     */
    public static function forward(Request $Request)
    {
        $list = [];

        try {
            $list = self::getConfig()->toArray();
        } catch (QUI\Exception $Exception) {
            Log::writeException($Exception);
        }

        $uri  = $Request->getRequestUri();
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
     *
     * @param array $data
     */
    protected static function redirect(array $data)
    {
        $target = $data['target'];
        $code   = (int)$data['code'];

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
