<?php

/**
 * Return the composer data of the package
 *
 * @param string $package - Name of the package
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_get',
    function ($package) {
        $Package      = QUI::getPackageManager()->getInstalledPackage($package);
        $composerData = $Package->getComposerData();
        $lockData     = $Package->getLock();

        $hashData = [];

        if (isset($lockData['dist']['reference'])) {
            $hashValue = $lockData['dist']['reference'];
            $hashData  = [
                'hash' => [
                    'full'  => $hashValue,
                    'short' => \substr($hashValue, 0, 8)
                ]
            ];
        }

        $standardData = [
            'name'        => $Package->getName(),
            'title'       => $Package->getTitle(),
            'description' => $Package->getDescription(),
            'image'       => $Package->getImage(),
            'preview'     => $Package->getPreviewImages()
        ];

        // require sort
        if (isset($composerData['require'])) {
            \ksort($composerData['require']);
        }

        return \array_merge($composerData, $standardData, $hashData);
    },
    ['package'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
