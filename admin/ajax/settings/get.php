<?php

/**
 * Return config params from a xml file
 *
 * @param string $file
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_settings_get',
    static function ($file) {
        $files = json_decode($file, true);
        $config = [];

        if (is_string($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (!str_contains($file, CMS_DIR)) {
                $file = CMS_DIR . $file;
            }

            if (!file_exists($file)) {
                continue;
            }

            $Config = QUI\Utils\Text\XML::getConfigFromXml($file, true);

            if ($Config) {
                $config = array_merge_recursive_overwrite($config, $Config->toArray());
            }

            // hidden fields
            // don't show this in the frontend
            if (str_contains($file, 'quiqqer/core/admin/settings/conf.xml')) {
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
                    $nonce = \QUI\Security\Password::generateRandom();

                    $Config->setValue('globals', 'nonce', $nonce);
                    $Config->save();

                    $config['globals']['nonce'] = $nonce;
                }
            }
        }

        return $config;
    },
    ['file'],
    [
        'Permission::checkAdminUser',
        'quiqqer.settings'
    ]
);
