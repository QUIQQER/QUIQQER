{
    "name" : "quiqqer/quiqqer-system",
    "type" : "composer-installer",
    "description" : "A modular based management system written in JavaScript and PHP",
    "version" : "dev-master",
    "license" : "GPL-3.0+",

    "authors" : [{
        "name": "Henning Leutz",
        "email": "leutz@pcsg.de",
        "homepage": "http://www.pcsg.de",
        "role": "Developer"
    }, {
        "name": "Moritz Scholz",
        "email": "scholz@pcsg.de",
        "homepage": "http://www.pcsg.de",
        "role": "Developer"
    }],

    "minimum-stability": "{$stability}",

    "support" : {
        "email": "support@pcsg.de",
        "url": "http://www.quiqqer.com"
    },

    "repositories": {$repositories},

    "require": {$REQUIRE},

    "config": {
        "vendor-dir": "{$PACKAGE_DIR}",
        "cache-dir" : "{$VAR_COMPOSER_DIR}",
        "component-dir" : "{$PACKAGE_DIR}bin"
    },

    "scripts": {
        "post-install-cmd" : [
            "QUI\\Update::onInstall"
        ],

        "post-update-cmd": [
            "QUI\\Update::onUpdate"
        ]
    },

    "autoload": {
        "psr-0" : {
            "QUI" : "{$LIB_DIR}"
        }
    }
}