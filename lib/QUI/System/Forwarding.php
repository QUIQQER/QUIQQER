<?php

namespace QUI\System;

use QUI;
use DusanKasan\Knapsack\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class Forwarding
 *
 * @package QUI\System
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
    public static function create($from, $target, $httpCode = 301)
    {
        $config = self::getConfg()->toArray();

        if (isset($config[$from])) {
            throw new QUI\Exception(array(
                'quiqqer/quiqqer',
                'exception.forwarding.already.exists'
            ));
        }

        if (empty($httpCode)) {
            $httpCode = 301;
        }

        self::getConfg()->setValue($from, 'target', $target);
        self::getConfg()->setValue($from, 'code', $httpCode);
        self::getConfg()->save();
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
    public static function update($from, $target, $httpCode = 301)
    {
        $config = self::getConfg()->toArray();

        if (!isset($config[$from])) {
            throw new QUI\Exception(
                array(
                    'quiqqer/quiqqer',
                    'exception.forwarding.not.found'
                ),
                404
            );
        }

        if (empty($httpCode)) {
            $httpCode = 301;
        }

        self::getConfg()->setValue($from, 'target', $target);
        self::getConfg()->setValue($from, 'code', $httpCode);
        self::getConfg()->save();
    }

    /**
     * Löscht ein forwarding eintrag
     *
     * @param string|array $from
     */
    public static function delete($from)
    {
        if (is_array($from)) {
            foreach ($from as $f) {
                self::getConfg()->del($f);
            }
        } else {
            self::getConfg()->del($from);
        }

        self::getConfg()->save();
    }

    /**
     * Return the forwarding config
     *
     * @return QUI\Config
     */
    public static function getConfg()
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
    public static function getList()
    {
        return new Collection(self::getConfg()->toArray());
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
        $list = self::getConfg()->toArray();
        $uri  = $Request->getRequestUri();
        $host = $Request->getSchemeAndHttpHost();

        $request = $host . $uri;

        // directly found
        if (isset($list[$request])) {
            self::redirect($list[$request]);
        }

        // search
        foreach ($list as $from => $params) {
            if (!QUI\Utils\StringHelper::match($from, $request)) {
                continue;
            }

            self::redirect($list[$from]);
        }
    }

    /**
     * @param array $data
     */
    protected static function redirect($data)
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
