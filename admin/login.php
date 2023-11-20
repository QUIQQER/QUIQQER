<?php

// phpcs:ignoreFile

use QUI\Projects\Media\Utils;

QUI::getEvents()->fireEvent('adminRequest');

$languages = QUI::availableLanguages();
$packages = QUI::getPackageManager()->getInstalled();
$defaultLanguage = QUI::conf('globals', 'standardLanguage');

if (!empty($defaultLanguage)) {
    QUI::getLocale()->setCurrent($defaultLanguage);
}

$authPackages = [];
$quiqqerLogo = QUI::getLocale()->get('quiqqer/quiqqer', 'menu.quiqqer.text');

$projectLogo = '';

try {
    $Standard = QUI::getProjectManager()->getStandard();

    $projectLogo = $Standard->getConfig('logo');
    $projectName = $Standard->getName();

    if (Utils::isMediaUrl($Standard->getConfig('logo'))) {
        $ProjectImage = Utils::getImageByUrl($Standard->getConfig('logo'));
        $projectLogo = '<img src="' . $ProjectImage->getSizeCacheUrl() . '" />';
    }
} catch (QUI\Exception $exception) {
}

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
    <script src="<?php
    echo URL_BIN_DIR; ?>js/mvc/html5.js"></script>
    <![endif]-->

    <title>
        QUIQQER Content Management System - <?php
        echo HOST ?> -
    </title>
    <!-- favicon -->
    <?php
    $favicon = QUI::conf('globals', 'favicon');

    if ($favicon) {
        try {
            $Favicon = QUI\Projects\Media\Utils::getImageByUrl($favicon);
            $attributes = $Favicon->getAttributes();
            $type = $attributes['mime_type'];

            ?>
            <link rel="shortcut icon" href="<?php
            echo $Favicon->getSizeCacheUrl(62, 62); ?>"
                  type="<?php
                  echo $type; ?>">

            <link rel="icon" href="<?php
            echo $Favicon->getSizeCacheUrl(16, 16); ?>" sizes="16x16"
                  type="<?php
                  echo $type; ?>">
            <link rel="icon" href="<?php
            echo $Favicon->getSizeCacheUrl(32, 32); ?>" sizes="32x32"
                  type="<?php
                  echo $type; ?>">
            <link rel="icon" href="<?php
            echo $Favicon->getSizeCacheUrl(48, 48); ?>" sizes="48x48"
                  type="<?php
                  echo $type; ?>">
            <link rel="icon" href="<?php
            echo $Favicon->getSizeCacheUrl(62, 62); ?>" sizes="62x62"
                  type="<?php
                  echo $type; ?>">
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
            $files[] = 'locale/' . $package . '/' . $lang;
        }
    }

    echo '<script type="text/javascript">';
    echo '/* <![CDATA[ */';
    echo 'var QUIQQER_LOCALE = ' . json_encode($files, true) . ';';
    echo 'var QUIQQER_LANGUAGES = ' . json_encode($languages, true) . ';';
    echo 'var QUIQQER_IS_ADMIN_LOGIN = true;';
    echo 'var QUIQQER_CONFIG = {globals: {no_ajax_bundler: 1}}';
    echo '/* ]]> */';
    echo '</script>';
    ?>

    <style type="text/css">
        :root {
            --color-primary: #2f8fc6;
            --color-primary-dark: #0e3145;
            --color-primary-accent: #fff;

            --text-body: #1e2021;
            --text-muted: #6d7b86;

            --radius: 1rem;
            --radius-sm: 0.25rem;
        }


        * {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }

        html, body {
            background: #dedede;
            color: var(--text-body);
            float: left;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
            margin: 0;
            padding: 0;
            text-align: left;
            width: 100%;
            line-height: 1.5;
        }

        h1, h2, h3 {
            line-height: 1.1;
        }

        img {
            max-width: 100%;
        }

        .logo img {
            max-width: 100px;
        }

        .container {
            min-height: 100vh;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #fafafa;
            background-image: linear-gradient(45deg, #fafafa, #eee);
        }

        .box {
            position: relative;
            max-width: 1000px;
            width: 100%;
            display: flex;
            background-color: #fff;
            border: 4px solid #fff;
            -webkit-box-shadow: 0 80px 30px -50px rgba(0,0,0,0.1);
            box-shadow: 0 80px 30px -50px rgba(0,0,0,0.1);
            border-radius: var(--radius);
        }

        .box__aside {
            padding: 2rem;
            flex-grow: 1;
            width: 400px;
            display: flex;
            flex-shrink: 0;
            border-radius: var(--radius);
            background-position: 80% 100%, 0 -200px;
            background-repeat: no-repeat, repeat;
            background-size: 170% auto, 100% 150%;

            color: var(--color-primary-accent);
            background-image: url(https://img.michael.pcsg.eu/ecoyn/blog/test.svg?9), linear-gradient(-10deg, var(--color-primary), var(--color-primary-dark));
            background-color: #2f8fc6;


            background-position: 80% 100%, 0 0;
            transition: 1s ease;
        }

        .box__aside-footer {
            position: absolute;
            left: 1rem;
            bottom: 1rem;
            color: var(--text-body);
        }

        .box__aside-footer .logo {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
        }

        .box__main {
            padding: 4rem 2rem;
            flex-grow: 1;
            position: relative;
        }

        .mainContent {
            max-width: 20rem;
            margin-inline: auto;
        }

        .mainContent .slogan__login {
            margin-bottom: 2rem;
            text-align: center;
        }

        .mainContent .logo {
            text-align: center;
            margin-top: 1rem;
            margin-bottom: 3rem;
        }

        /* login control */
        .mainContent .quiqqer-login {
            padding: 0;
        }

        .mainContent .quiqqer-login-auth label span {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .mainContent input {
            line-height: 1.5rem;
            padding: 0.5rem 1.5rem;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: var(--radius-sm);
        }

        button[type="submit"],
        button.reset-password {
            color: var(--color-primary-accent);
            background-color: var(--color-primary);
            border-radius: var(--radius-sm);
            line-height: calc(1.5rem + 2px);
            font-size: 16px;
            border: none;
            margin-top: 1rem;
            padding: 0.5rem 1.5rem;
        }

        button[type="submit"] {
            width: 100%;
        }

        button[name="cancel"] {
        }


        /* lang switch */
        .quiqqer-language-switch {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.875rem;
        }

        .quiqqer-language-switch > .qui-select {
            border: none;
            display: flex;
            justify-content: flex-end;
        }

        .quiqqer-language-switch > .qui-select .text {
            width: auto;
            margin-right: 0.5em;
        }




        .license {
            font-size: 0.75rem;
            margin-top: 2rem;
            max-width: 25rem;
            margin-inline: auto;
            color: var(--text-muted);

        }

    </style>


    <?php

    echo QUI\FontAwesome\EventHandler::fontawesome(false, false);

    ?>

    <script src="<?php
    echo URL_OPT_DIR; ?>bin/quiqqer-asset/requirejs/requirejs/require.js"></script>
    <script src="<?php
    echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-core.js"></script>
    <script src="<?php
    echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-more.js"></script>
    <script src="<?php
    echo URL_OPT_DIR; ?>bin/qui/qui/lib/moofx.js"></script>

    <script type="text/javascript">

        const URL_DIR = '<?php echo URL_DIR; ?>',
            URL_BIN_DIR = '<?php echo URL_BIN_DIR; ?>',
            URL_OPT_DIR = '<?php echo URL_OPT_DIR; ?>',
            URL_SYS_DIR = '<?php echo URL_SYS_DIR; ?>';

        let LANGUAGE = null;

        // require config
        require.config({
            baseUrl: '<?php echo URL_BIN_DIR; ?>QUI/',
            paths: {
                'package': "<?php echo URL_OPT_DIR; ?>",
                'qui': '<?php echo URL_OPT_DIR; ?>bin/qui/qui',
                'locale': '<?php echo URL_VAR_DIR; ?>locale/bin',
                'Ajax': '<?php echo URL_BIN_DIR; ?>QUI/Ajax',
                'URL_OPT_DIR': "<?php echo URL_OPT_DIR; ?>",
                'URL_BIN_DIR': "<?php echo URL_BIN_DIR; ?>",

                'Mustache': URL_OPT_DIR + 'bin/quiqqer-asset/mustache/mustache/mustache.min',
                'URI': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/URI',
                'IPv6': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/IPv6',
                'punycode': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/punycode',
                'SecondLevelDomains': URL_OPT_DIR + 'bin/quiqqer-asset/urijs/urijs/src/SecondLevelDomains'
            },
            waitSeconds: 0,
            catchError: true,
            map: {
                '*': {
                    'css': '<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/css.js'
                }
            }
        });

        function getCurrentLanguage()
        {
            if (LANGUAGE) {
                return LANGUAGE;
            }

            let lang = 'en';

            if ('language' in navigator) {
                lang = navigator.language;
            } else {
                if ('browserLanguage' in navigator) {
                    lang = navigator.browserLanguage;
                } else {
                    if ('systemLanguage' in navigator) {
                        lang = navigator.systemLanguage;
                    } else {
                        if ('userLanguage' in navigator) {
                            lang = navigator.userLanguage;
                        }
                    }
                }
            }

            LANGUAGE = lang.substr(0, 2);

            return LANGUAGE;
        }

        function setLanguage(lang)
        {
            if (!QUIQQER_LANGUAGES.contains(lang)) {
                lang = QUIQQER_LANGUAGES[0];
            }

            return new Promise(function(resolve) {
                require([
                    'qui/QUI',
                    'utils/Session',
                    'Locale',
                    'locale/quiqqer/quiqqer/' + lang
                ], function(QUI, Session, QUILocale) {
                    QUILocale.setCurrent(lang);
                    Session.set('quiqqer-user-language', lang).catch(function(err) {
                        // doesn't matter
                    });

                    const LoginElement = document.getElement('.quiqqer-login');

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
        ].append(QUIQQER_LOCALE || []), function(QUI, Login) {
            QUI.setAttributes({
                'control-loader-type': 'line-scale',
                'control-loader-color': '#2f8fc8'
            });

            setLanguage(getCurrentLanguage()).then(function() {
                document.getElement('.login').set('html', '');

                const LogIn = new Login({
                    onSuccess: window.onSuccess
                }).inject(document.getElement('.login'));


                // chrome workaround - because of state saving
                // sometimes, chrome don't load all events on a back up'd tab
                (function() {
                    const Form = LogIn.getElm().getElement('form');

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

        function onSuccess(Login)
        {
            require([
                'Ajax',
                'Locale'
            ], function(QUIAjax, QUILocale) {
                // check if admin user
                QUIAjax.get('ajax_user_canUseBackend', function(canUseAdmin) {
                    if (canUseAdmin === false) {
                        QUI.getMessageHandler().then(function(MH) {
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
                        callback: function() {
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
    <div class="box">
        <div class="box__aside">
            <div class="slogan__title">
                <h1>Willkomen in deinem Projekt</h1>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Delectus, numquam.</p>
            </div>

            <div class="box__aside-footer">
                <?php
                if (!empty($projectLogo)) {
                    echo '<div class="logo logo--quiqqer"><span class="logo__text">Powered by</span>';
                    echo $quiqqerLogo;
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <div class="box__main">
            <div class="quiqqer-language-switch"></div>

            <div class="mainContent">
                <?php

                if (!empty($projectLogo)) {
                    echo '<div class="logo logo--project">';
                    echo $projectLogo;
                    echo '</div>';
                } else {
                    echo '<div class="logo logo--quiqqer">';
                    echo $quiqqerLogo;
                    echo '</div>';
                }

                ?>

                <p class="slogan__login">Melde dich in dein QUIQQER System an.</p>

                <div class="login"></div>
            </div>

            <div class="license">
                QUIQQER Copyright(C) <?php
                echo date('Y'); ?> PCSG - Computer & Internet Service OHG - www.pcsg.de
                This program comes with ABSOLUTELY NO WARRANTY;
                This is free software, and you are welcome to redistribute it under certain conditions;
                visit www.quiqqer.com for details.
            </div>
        </div>
    </div>
</div>

<?php
if (defined('LOGIN_FAILED')) { ?>
    <script type="text/javascript">
        require(['qui/QUI'], function() {
            QUI.getMessageHandler().then(function(MH) {
                MH.addError("<?php echo LOGIN_FAILED; ?>");
            });
        });
    </script>
    <?php
} ?>

<script>
    var LoginContainer = document.getElement('.quiqqer-language-switch'),
        needle = [
            'qui/controls/buttons/Select',
            'Locale'
        ];

    for (let i = 0, len = QUIQQER_LANGUAGES.length; i < len; i++) {
        needle.push('locale/quiqqer/quiqqer/' + QUIQQER_LANGUAGES[i]);
    }

    require(needle, function(QUISelect, QUILocale) {
        const Select = new QUISelect({
            events: {
                onChange: function() {
                    setLanguage(Select.getValue());
                }
            }
        }).inject(LoginContainer);

        let i, len, lang, text;
        const current = QUILocale.getCurrent();

        for (i = 0, len = QUIQQER_LANGUAGES.length; i < len; i++) {
            lang = QUIQQER_LANGUAGES[i];
            QUILocale.setCurrent(lang);

            text = QUILocale.get('quiqqer/quiqqer', 'language.' + lang);

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
    }, function() {

    });
</script>

</body>
</html>
