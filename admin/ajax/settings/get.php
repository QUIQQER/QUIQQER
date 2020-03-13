<?php

/**
 * Return config params from a xml file
 *
 * @param string $file
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_settings_get',
    function ($file) {
        $files  = \json_decode($file, true);
        $config = [];

        if (\is_string($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (!\file_exists($file)) {
                $file = CMS_DIR.$file;
            }

            if (!\file_exists($file)) {
                continue;
            }

            $Config = QUI\Utils\Text\XML::getConfigFromXml($file, true);

            if ($Config) {
                $config = \array_merge_recursive($config, $Config->toArray());
            }

            // hidden fields
            // dont show this in the frontend
            if (\strpos($file, 'quiqqer/quiqqer/admin/settings/conf.xml') !== false) {
                unset($config['db']);
                unset($config['openssl']);
                unset($config['globals']['salt']);
                unset($config['globals']['saltlength']);

                unset($config['globals']['cms_dir']);
                unset($config['globals']['var_dir']);
                unset($config['globals']['usr_dir']);
                unset($config['globals']['opt_dir']);

                unset($config['globals']['rootuser']);
                unset($config['globals']['root']);

                if (empty($config['globals']['nonce'])) {
                    $nonce = \QUI\Security\Password::generateRandom(10);

                    $Config->setValue('globals', 'nonce', $nonce);
                    $Config->save();

                    $config['globals']['nonce'] = $nonce;
                }
            }
        }

        return $config;
    },
    ['file'],
    'Permission::checkSU'
);
