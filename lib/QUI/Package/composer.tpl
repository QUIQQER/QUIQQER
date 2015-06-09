{
    "name" : "quiqqer/quiqqer-system",
    "type" : "composer-installer",
    "description" : "A modular based management system written in JavaScript and PHP",
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

    "minimum-stability": "stable",

    "support" : {
        "email": "support@pcsg.de",
        "url": "http://www.quiqqer.com"
    },

    "repositories": [],
    "require": {},
    "config": {},

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