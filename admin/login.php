<?php

QUI::getEvents()->fireEvent('adminRequest');

$languages = QUI::availableLanguages();
$packages  = QUI::getPackageManager()->getInstalled();

$authPackages = [];

foreach ($packages as $package) {
    try {
        $Package = QUI::getPackage($package['name']);

        if (!$Package->isQuiqqerPackage()) {
            continue;
        }

        $auth = $Package->getProvider('auth');

        if (!empty($auth)) {
            $authPackages[] = $Package->getName();
        }
    } catch (QUI\Exception $Exception) {
    }
}

?>
<!doctype html>
<!--[if lt IE 7 ]>
<html class="ie ie6" lang="de"> <![endif]-->
<!--[if IE 7 ]>
<html class="ie ie7" lang="de"> <![endif]-->
<!--[if IE 8 ]>
<html class="ie ie8" lang="de"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html lang="de"> <!--<![endif]-->
<head>
    <!-- HTML5
          ================================================== -->
    <!--[if lt IE 9]>
    <script src="<?php echo URL_BIN_DIR; ?>js/mvc/html5.js"></script>
    <![endif]-->

    <title>
        QUIQQER Content Management System - <?php echo HOST ?> -
    </title>
    <!-- favicon -->
    <?php
    $favicon = QUI::conf('globals', 'favicon');

    if ($favicon) {
        try {
            $Favicon    = QUI\Projects\Media\Utils::getImageByUrl($favicon);
            $attributes = $Favicon->getAttributes();
            $type       = $attributes['mime_type'];

            ?>
            <link rel="shortcut icon" href="<?php echo $Favicon->getSizeCacheUrl(62, 62); ?>"
                  type="<?php echo $type; ?>">

            <link rel="icon" href="<?php echo $Favicon->getSizeCacheUrl(16, 16); ?>" sizes="16x16"
                  type="<?php echo $type; ?>">
            <link rel="icon" href="<?php echo $Favicon->getSizeCacheUrl(32, 32); ?>" sizes="32x32"
                  type="<?php echo $type; ?>">
            <link rel="icon" href="<?php echo $Favicon->getSizeCacheUrl(48, 48); ?>" sizes="48x48"
                  type="<?php echo $type; ?>">
            <link rel="icon" href="<?php echo $Favicon->getSizeCacheUrl(62, 62); ?>" sizes="62x62"
                  type="<?php echo $type; ?>">
            <?php
        } catch (QUI\Exception $Exception) {
        }
    }
    ?>

    <?php

    /**
     * locale file
     */
    $files = [];

    foreach ($authPackages as $package) {
        foreach ($languages as $lang) {
            $files[] = 'locale/'.$package.'/'.$lang;
        }
    }

    echo '<script type="text/javascript">';
    echo '/* <![CDATA[ */';
    echo 'var QUIQQER_LOCALE = '.\json_encode($files, true).';';
    echo 'var QUIQQER_LANGUAGES = '.\json_encode($languages, true).';';
    echo 'var QUIQQER_IS_ADMIN_LOGIN = true;';
    echo 'var QUIQQER_CONFIG = {globals: {no_ajax_bundler: 1}}';
    echo '/* ]]> */';
    echo '</script>';
    ?>

    <style type="text/css">

        * {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }

        html, body {
            background: #dedede;
            color: #333;
            float: left;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 0;
            text-align: left;
            width: 100%;
        }

        .container {
            background: #fff;
            box-shadow: 2px 0 5px #999;
            padding: 40px 0;
            margin: 50px 0 0;
            min-height: 380px;
        }

        .logo {
            margin: 40px 0 40px -239px;
            left: 50%;
            position: relative;
        }

        .login {
            margin: 0 auto;
            max-width: 300px;
            width: 100%;
        }

        input {
            border: 1px solid #999;
            border-radius: 3px;
            padding: 5px 10px;
        }

        button[type="submit"],
        button.reset-password {
            border-color: #538312;
            background: #64991e;
            border: none;
            border-radius: 0;
            line-height: 30px !important;
            color: #fff;
        }

        button[name="cancel"] {
            line-height: 30px !important;
        }

        /* Animate.css - http://daneden.me/animate */
        .animated {
            -webkit-animation-fill-mode: both;
            -moz-animation-fill-mode: both;
            -ms-animation-fill-mode: both;
            -o-animation-fill-mode: both;
            animation-fill-mode: both;
            -webkit-animation-duration: 2s;
            -moz-animation-duration: 2s;
            -ms-animation-duration: 2s;
            -o-animation-duration: 2s;
            animation-duration: 2s;
        }

        .animated.hinge {
            -webkit-animation-duration: 2s;
            -moz-animation-duration: 2s;
            -ms-animation-duration: 2s;
            -o-animation-duration: 2s;
            animation-duration: 2s;
        }

        @-webkit-keyframes tada {
            0% {
                -webkit-transform: scale(1);
            }
            10%, 20% {
                -webkit-transform: scale(0.9) rotate(-3deg);
            }
            30%, 50%, 70%, 90% {
                -webkit-transform: scale(1.1) rotate(3deg);
            }
            40%, 60%, 80% {
                -webkit-transform: scale(1.1) rotate(-3deg);
            }
            100% {
                -webkit-transform: scale(1) rotate(0);
            }
        }

        @-moz-keyframes tada {
            0% {
                -moz-transform: scale(1);
            }
            10%, 20% {
                -moz-transform: scale(0.9) rotate(-3deg);
            }
            30%, 50%, 70%, 90% {
                -moz-transform: scale(1.1) rotate(3deg);
            }
            40%, 60%, 80% {
                -moz-transform: scale(1.1) rotate(-3deg);
            }
            100% {
                -moz-transform: scale(1) rotate(0);
            }
        }

        @-o-keyframes tada {
            0% {
                -o-transform: scale(1);
            }
            10%, 20% {
                -o-transform: scale(0.9) rotate(-3deg);
            }
            30%, 50%, 70%, 90% {
                -o-transform: scale(1.1) rotate(3deg);
            }
            40%, 60%, 80% {
                -o-transform: scale(1.1) rotate(-3deg);
            }
            100% {
                -o-transform: scale(1) rotate(0);
            }
        }

        @keyframes tada {
            0% {
                transform: scale(1);
            }
            10%, 20% {
                transform: scale(0.9) rotate(-3deg);
            }
            30%, 50%, 70%, 90% {
                transform: scale(1.1) rotate(3deg);
            }
            40%, 60%, 80% {
                transform: scale(1.1) rotate(-3deg);
            }
            100% {
                transform: scale(1) rotate(0);
            }
        }

        .tada {
            -webkit-animation-name: tada;
            -moz-animation-name: tada;
            -o-animation-name: tada;
            animation-name: tada;
        }

        @-webkit-keyframes swing {
            20%, 40%, 60%, 80%, 100% {
                -webkit-transform-origin: top center;
            }
            20% {
                -webkit-transform: rotate(15deg);
            }
            40% {
                -webkit-transform: rotate(-10deg);
            }
            60% {
                -webkit-transform: rotate(5deg);
            }
            80% {
                -webkit-transform: rotate(-5deg);
            }
            100% {
                -webkit-transform: rotate(0deg);
            }
        }

        @-moz-keyframes swing {
            20% {
                -moz-transform: rotate(15deg);
            }
            40% {
                -moz-transform: rotate(-10deg);
            }
            60% {
                -moz-transform: rotate(5deg);
            }
            80% {
                -moz-transform: rotate(-5deg);
            }
            100% {
                -moz-transform: rotate(0deg);
            }
        }

        @-o-keyframes swing {
            20% {
                -o-transform: rotate(15deg);
            }
            40% {
                -o-transform: rotate(-10deg);
            }
            60% {
                -o-transform: rotate(5deg);
            }
            80% {
                -o-transform: rotate(-5deg);
            }
            100% {
                -o-transform: rotate(0deg);
            }
        }

        @keyframes swing {
            20% {
                transform: rotate(15deg);
            }
            40% {
                transform: rotate(-10deg);
            }
            60% {
                transform: rotate(5deg);
            }
            80% {
                transform: rotate(-5deg);
            }
            100% {
                transform: rotate(0deg);
            }
        }

        .swing {
            -webkit-transform-origin: top center;
            -moz-transform-origin: top center;
            -o-transform-origin: top center;
            transform-origin: top center;
            -webkit-animation-name: swing;
            -moz-animation-name: swing;
            -o-animation-name: swing;
            animation-name: swing;
        }

        .quiqqer-language-switch {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>


    <?php

    echo QUI\FontAwesome\EventHandler::fontawesome(false, false);

    ?>

    <script src="<?php echo URL_OPT_DIR; ?>bin/quiqqer-asset/requirejs/requirejs/require.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-core.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-more.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/moofx.js"></script>

    <script type="text/javascript">

        var URL_DIR     = '<?php echo URL_DIR; ?>',
            URL_BIN_DIR = '<?php echo URL_BIN_DIR; ?>',
            URL_OPT_DIR = '<?php echo URL_OPT_DIR; ?>',
            URL_SYS_DIR = '<?php echo URL_SYS_DIR; ?>',
            LANGUAGE    = null;

        // require config
        require.config({
            baseUrl    : '<?php echo URL_BIN_DIR; ?>QUI/',
            paths      : {
                "package"    : "<?php echo URL_OPT_DIR; ?>",
                "qui"        : '<?php echo URL_OPT_DIR; ?>bin/qui/qui',
                "locale"     : '<?php echo URL_VAR_DIR; ?>locale/bin',
                "Ajax"       : '<?php echo URL_BIN_DIR; ?>QUI/Ajax',
                "URL_OPT_DIR": "<?php echo URL_OPT_DIR; ?>",
                "URL_BIN_DIR": "<?php echo URL_BIN_DIR; ?>",

                "Mustache"          : URL_OPT_DIR + 'bin/quiqqer-asset/mustache/mustache/mustache.min',
                "URI"               : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/URI',
                'IPv6'              : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/IPv6',
                'punycode'          : URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/punycode',
                'SecondLevelDomains': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/SecondLevelDomains',
            },
            waitSeconds: 0,
            catchError : true,
            map        : {
                '*': {
                    'css': '<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/css.js'
                }
            }
        });

        function getCurrentLanguage() {
            if (LANGUAGE) {
                return LANGUAGE;
            }

            var lang = 'en';

            if ("language" in navigator) {
                lang = navigator.language;
            } else if ("browserLanguage" in navigator) {
                lang = navigator.browserLanguage;
            } else if ("systemLanguage" in navigator) {
                lang = navigator.systemLanguage;
            } else if ("userLanguage" in navigator) {
                lang = navigator.userLanguage;
            }

            LANGUAGE = lang.substr(0, 2);

            return LANGUAGE;
        }

        function setLanguage(lang) {
            if (!QUIQQER_LANGUAGES.contains(lang)) {
                lang = QUIQQER_LANGUAGES[0];
            }

            return new Promise(function (resolve) {
                require([
                    'qui/QUI',
                    'utils/Session',
                    'Locale',
                    'locale/quiqqer/quiqqer/' + lang
                ], function (QUI, Session, QUILocale) {
                    QUILocale.setCurrent(lang);
                    Session.set('quiqqer-user-language', lang).catch(function (err) {
                        // doesn't matter
                    });

                    var LoginElement = document.getElement('.quiqqer-login');

                    if (!LoginElement) {
                        resolve();
                        return;
                    }

                    QUI.Controls.getById(LoginElement.get('data-quiid')).refresh();

                    resolve();
                });
            });
        }

        // init
        require([
            'qui/QUI',
            'controls/users/Login'
        ].append(QUIQQER_LOCALE || []), function (QUI, Login) {
            QUI.setAttributes({
                'control-loader-type' : 'line-scale',
                'control-loader-color': '#2f8fc8'
            });

            setLanguage(getCurrentLanguage()).then(function () {
                var LogIn = new Login({
                    onSuccess: window.onSuccess
                }).inject(document.getElement('.login'));


                // chrome workaround - because of state saving
                // sometimes, chrome don't load all events on a back up'd tab
                (function () {
                    var Form = LogIn.getElm().getElement('form');

                    if (!Form) {
                        LogIn.refresh();
                        return;
                    }

                    if (!parseInt(Form.getStyle('opacity'))) {
                        LogIn.refresh();
                    }
                }).delay(2000);
            });
        });

        function onSuccess(Login) {
            require([
                'Ajax',
                'Locale'
            ], function (QUIAjax, QUILocale) {
                // check if admin user
                QUIAjax.get('ajax_user_canUseBackend', function (canUseAdmin) {
                    if (canUseAdmin === false) {
                        QUI.getMessageHandler().then(function (MH) {
                            MH.addError(
                                QUILocale.get(
                                    'quiqqer/quiqqer',
                                    'exception.permission.no.admin'
                                )
                            );
                        });

                        Login.Loader.hide();
                        return;
                    }

                    moofx(document.getElement('.container')).animate({
                        opacity: 0
                    }, {
                        duration: 200,
                        callback: function () {
                            window.location.reload();
                        }
                    });
                });
            });
        }

    </script>
</head>
<body>

<div class="container">
    <div class="quiqqer-language-switch"></div>
    <img src="<?php echo URL_BIN_DIR; ?>quiqqer_logo.png"
         alt="QUIQQER Login"
         title="QUIQQER Logo"
         class="logo"
    />

    <div class="login"></div>
</div>

<?php if (\defined('LOGIN_FAILED')) { ?>
    <script type="text/javascript">
        require(['qui/QUI'], function () {
            QUI.getMessageHandler().then(function (MH) {
                MH.addError("<?php echo LOGIN_FAILED; ?>");
            });
        });
    </script>
<?php } ?>

<script>
    var LoginContainer = document.getElement('.quiqqer-language-switch'),
        needle         = ['qui/controls/buttons/Select', 'Locale'];

    for (var i = 0, len = QUIQQER_LANGUAGES.length; i < len; i++) {
        needle.push('locale/quiqqer/quiqqer/' + QUIQQER_LANGUAGES[i]);
    }

    require(needle, function (QUISelect, QUILocale) {
        var Select = new QUISelect({
            events: {
                onChange: function () {
                    setLanguage(Select.getValue());
                }
            }
        }).inject(LoginContainer);

        var i, len, lang;
        var current = QUILocale.getCurrent();

        for (i = 0, len = QUIQQER_LANGUAGES.length; i < len; i++) {
            lang = QUIQQER_LANGUAGES[i];
            QUILocale.setCurrent(lang);

            var text = QUILocale.get('quiqqer/quiqqer', 'language.' + lang);

            if (!QUILocale.exists('quiqqer/quiqqer', 'language.' + lang)) {
                QUILocale.setCurrent('en');
                text = QUILocale.get('quiqqer/quiqqer', 'language.' + lang);
            }

            Select.appendChild(
                text,
                lang,
                URL_BIN_DIR + '16x16/flags/' + lang + '.png'
            );
        }

        QUILocale.setCurrent(current);
        Select.setValue(getCurrentLanguage());
    }, function () {

    });
</script>

</body>
</html>
