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
        $Package = QUI::getPackageManager()->getInstalledPackage($package);

        $composerData = $Package->getComposerData();
        $standardData = array(
            'name'        => $Package->getName(),
            'title'       => $Package->getTitle(),
            'description' => $Package->getDescription(),
            'image'       => $Package->getImage(),
            'preview'     => $Package->getPreviewImages(),
        );

        // require sort
        ksort($composerData['require']);

        return array_merge($composerData, $standardData);
    },
    array('package'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
