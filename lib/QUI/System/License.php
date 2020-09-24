<?php

namespace QUI\System;

use QUI;
use QUI\QDOM;
use QUI\Utils\System\File;
use QUI\Config;
use QUI\Security\Encryption;

/**
 * Class License
 *
 * Manages QUIQQER license related stuff.
 */
class License
{
    /**
     * Register a license from a license file
     *
     * @param QDOM $File
     * @return void
     *
     * @throws QUI\Exception
     */
    public static function registerLicenseFile($File)
    {
        $content = \file_get_contents($File->getAttribute('filepath'));
        $content = \json_decode(hex2bin($content), true);

        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new QUI\Exception('JSON Error in license data: '.\json_last_error_msg());
        }

        $keys = [
            'id',
            'created',
            'licenseHash',
            'licenseServer',
            'validUntil',
            'name'
        ];

        foreach ($keys as $key) {
            if (!isset($content[$key])) {
                throw new QUI\Exception('Missing key "'.$key.'" in license data.');
            }

            if (empty($content[$key])) {
                throw new QUI\Exception('Empty key "'.$key.'" in license data.');
            }

            if (!is_string($content[$key])) {
                throw new QUI\Exception('Non-string key "'.$key.'" in license data.');
            }
        }

        // put license data in config
        $licenseConfigFile = CMS_DIR.'etc/license.ini.php';
        File::mkfile($licenseConfigFile);

        if (!\file_exists($licenseConfigFile)) {
            throw new QUI\Exception('Could not create license config file "'.$licenseConfigFile.'"');
        }

        $LicenseConfig = new Config($licenseConfigFile);

        $LicenseConfig->set('license', 'id', $content['id']);
        $LicenseConfig->set('license', 'created', $content['created']);
        $LicenseConfig->set('license', 'name', $content['name']);
        $LicenseConfig->set('license', 'validUntil', $content['validUntil']);
        $LicenseConfig->set(
            'license',
            'licenseHash',
            \bin2hex(Encryption::encrypt(\hex2bin($content['licenseHash'])))
        );

        $LicenseConfig->save($licenseConfigFile);

        // set license server
        $Config = new QUI\Config(ETC_DIR.'conf.ini.php');
        $Config->set('license', 'url', $content['licenseServer']);
        $Config->save();

        // re-create composer.json
        QUI::getPackageManager()->refreshServerList();

        // clear license cache
        \QUI\Cache\Manager::clear('quiqqer_licenses');
    }

    /**
     * Activate this QUIQQER system for the currently registered license.
     *
     * @return array - Request response
     * @throws QUI\Exception
     */
    public static function activateSystem()
    {
        $licenseServerUrl = self::getLicenseServerUrl().'api/license/activate?';
        $licenseData      = self::getLicenseData();

        $licenseServerUrl .= \http_build_query([
            'licenseid'   => $licenseData['id'],
            'licensehash' => $licenseData['licenseHash'],
            'systemid'    => self::getSystemId(),
            'systemhash'  => self::getSystemDataHash(),
            'systemdata'  => \bin2hex(\json_encode(self::getSystemData()))
        ]);

        $Curl = \curl_init();

        \curl_setopt_array($Curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $licenseServerUrl,
            CURLOPT_USERAGENT      => 'QUIQQER'
        ]);

        $response = \curl_exec($Curl);

        \curl_close($Curl);

        if (empty($response)) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.License.connection_error'
            ]);
        }

        $response = \json_decode($response, true);

        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.License.connection_error'
            ]);
        }

        if (!empty($response['error'])) {
            throw new QUI\Exception($response['msg'], $response['errorCode']);
        }

        return $response;
    }

    /**
     * Deactivate this QUIQQER system for the currently registered license.
     *
     * @return array - Request response
     * @throws QUI\Exception
     */
    public static function deactivateSystem()
    {
        $licenseServerUrl = self::getLicenseServerUrl().'api/license/deactivate?';
        $licenseData      = self::getLicenseData();

        $licenseServerUrl .= \http_build_query([
            'licenseid'   => $licenseData['id'],
            'licensehash' => $licenseData['licenseHash'],
            'systemid'    => self::getSystemId(),
            'systemdata'  => \bin2hex(\json_encode(self::getSystemData()))
        ]);

        $Curl = \curl_init();

        \curl_setopt_array($Curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $licenseServerUrl,
            CURLOPT_USERAGENT      => 'QUIQQER'
        ]);

        $response = \curl_exec($Curl);

        \curl_close($Curl);

        if (empty($response)) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.License.connection_error'
            ]);
        }

        $response = \json_decode($response, true);

        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.License.connection_error'
            ]);
        }

        if (!empty($response['error'])) {
            throw new QUI\Exception($response['msg'], $response['errorCode']);
        }

        return $response;
    }

    /**
     * Get status of this QUIQQER system regarding the currently registered license
     *
     * @return array
     * @throws QUI\Exception
     */
    public static function getStatus()
    {
        $licenseServerUrl = self::getLicenseServerUrl().'api/license/status?';
        $licenseData      = self::getLicenseData();

        $licenseServerUrl .= \http_build_query([
            'licenseid'   => $licenseData['id'],
            'licensehash' => $licenseData['licenseHash'],
            'systemid'    => self::getSystemId(),
            'systemhash'  => self::getSystemDataHash()
        ]);

        $Curl = \curl_init();

        \curl_setopt_array($Curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $licenseServerUrl,
            CURLOPT_USERAGENT      => 'QUIQQER'
        ]);

        $response = \curl_exec($Curl);

        \curl_close($Curl);

        if (empty($response)) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.License.connection_error'
            ]);
        }

        $response = \json_decode($response, true);

        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.License.connection_error'
            ]);
        }

        return $response;
    }

    /**
     * Get data of license that is currently registered in this system.
     *
     * @return array|false - License data or false if no license data available
     */
    public static function getLicenseData()
    {
        $licenseConfigFile = CMS_DIR.'etc/license.ini.php';

        if (!\file_exists($licenseConfigFile)) {
            return false;
        }

        try {
            $LicenseConfig = new Config($licenseConfigFile);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return false;
        }

        $data                = $LicenseConfig->getSection('license');
        $data['licenseHash'] = \bin2hex(Encryption::decrypt(\hex2bin($data['licenseHash'])));

        return $data;
    }

    /**
     * Get license server URL
     *
     * @return string - License server URL (with trailing slash)
     */
    public static function getLicenseServerUrl()
    {
        $licenseServerUrl = QUI::conf('license', 'url');

        if (empty($licenseServerUrl)) {
            return 'https://license.quiqqer.com/';
        }

        return \rtrim($licenseServerUrl, '/').'/';
    }

    /**
     * Get unique QUIQQER system ID
     *
     * @return string
     */
    public static function getSystemId()
    {
        $systemId = QUI::conf('license', 'systemId');

        if (empty($systemId)) {
            $systemId = QUI\Utils\Uuid::get();

            try {
                $Conf = QUI::getConfig('etc/conf.ini.php');
                $Conf->set('license', 'systemId', $systemId);
                $Conf->save();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $systemId;
    }

    /**
     * Get unique hash that represents the current system configuration that is relevant for license purposes
     *
     * @return string
     */
    public static function getSystemDataHash()
    {
        $data   = self::getSystemData();
        $data[] = QUI::conf('globals', 'salt');

        return \hash('sha256', \implode('-', $data));
    }

    /**
     * Get system data for license server requests
     *
     * @return array
     */
    protected static function getSystemData()
    {
        // @todo add additional unique identifiers
        return [
            'cmsDir' => QUI::conf('globals', 'cms_dir'),
            'host'   => QUI::conf('globals', 'host')
        ];
    }
}
