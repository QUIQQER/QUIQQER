<?php

/**
 * This file contains \QUI\Package\LOCKClient
 */

namespace QUI\Package;

use QUI;

/**
 * Class LOCKClient
 *
 * @package quiqqer/quiqqer
 */
class LOCKClient extends QUI\QDOM
{
    /**
     * @param array $params
     */
    public function __construct($params = array())
    {
        $this->setAttributes(array(
            'lockServer' => 'https://lock.quiqqer.com'
        ));

        $this->setAttributes($params);
    }

    /**
     * Update the composer.lock file via a composerLOCK server
     *
     * @param string $composerJson - composer.json data
     *
     * @return string
     * @throws QUI\Exception
     */
    public function getComposerLock($composerJson)
    {
        $this->_checkComposerData($composerJson);

        return $this->_send('/v1/update', array(
            CURLOPT_POST       => 1,
            CURLOPT_POSTFIELDS => array(
                'composerJson' => $composerJson
            )
        ));
    }

    /**
     * Ask the lockserver whether updates exist
     *
     * @param String $composerJson - composer.json data
     * @param String|Bool $package - optional, Name of the package
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public function checkUpdates($composerJson, $package = false)
    {
        $this->_checkComposerData($composerJson);

        if ($package) {

            $result = $this->_send('/v1/check', array(
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS => array(
                    'composerJson' => $composerJson,
                    'package'      => $package
                )
            ));

        } else {

            $result = $this->_send('/v1/check', array(
                CURLOPT_POST       => 1,
                CURLOPT_POSTFIELDS => array(
                    'composerJson' => $composerJson
                )
            ));
        }

        return json_decode($result, true);
    }

    /**
     * Check the composer data
     *
     * @param $composerJson
     *
     * @throws QUI\Exception
     */
    protected function _checkComposerData($composerJson)
    {
        $composerData = json_decode($composerJson);

        if (!$composerData) {

            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lock.client.composerJson.invalid'
                ),
                400
            );
        }
    }

    /**
     * Send a requrest to the lockserver
     *
     * @param string $url
     * @param array  $postFields
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    protected function _send($url, array $postFields)
    {
        $lockServer = $this->getAttribute('lockServer');

        if (empty($lockServer)) {

            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lock.client.unknown.lock.server'
                ),
                400
            );
        }


        $Curl = QUI\Utils\Request\Url::Curl($lockServer.$url, array(
            CURLOPT_POST       => 1,
            CURLOPT_POSTFIELDS => $postFields
        ));

        $result = QUI\Utils\Request\Url::exec($Curl);
        $info = curl_getinfo($Curl);

        if ($info['http_code'] == 200) {
            return $result;
        }

        throw new QUI\Exception($result, 400);
    }
}
