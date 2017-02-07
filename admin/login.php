<?php

$languages = QUI::availableLanguages();

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

    <style type="text/css">

        * {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #dedede;

            text-align: left;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;

            color: #333;
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

        input[type="submit"] {
            border-color: #538312;
            background: #64991e;
            border-radius: 0;
            color: #fff;
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
    </style>

    <script src="<?php echo URL_OPT_DIR; ?>bin/require.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-core.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-more.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/moofx.js"></script>

    <script type="text/javascript">

        var URL_DIR     = '<?php echo URL_DIR; ?>',
            URL_OPT_DIR = '<?php echo URL_OPT_DIR; ?>',
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

                "Mustache"          : URL_OPT_DIR + 'bin/mustache/mustache.min',
                "URI"               : URL_OPT_DIR + 'bin/urijs/src/URI',
                'IPv6'              : URL_OPT_DIR + 'bin/urijs/src/IPv6',
                'punycode'          : URL_OPT_DIR + 'bin/urijs/src/punycode',
                'SecondLevelDomains': URL_OPT_DIR + 'bin/urijs/src/SecondLevelDomains'
            },
            waitSeconds: 0,
            catchError : true,
            map        : {
                '*': {
                    'css': '<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/css.js'
                }
            }
        });

        function getCurrentLanguage () {
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

        function setLanguage (lang) {
            return new Promise(function (resolve) {
                require([
                    'Locale',
                    'locale/quiqqer/system/' + lang
                ], function (QUILocale) {
                    QUILocale.setCurrent(lang);
                    resolve();
                });
            })
        }

        // init
        require(['qui/QUI', 'controls/users/Login'], function (QUI, Login) {
            QUI.setAttributes({
                'control-loader-type' : 'line-scale',
                'control-loader-color': '#2f8fc8'
            });

            setLanguage(getCurrentLanguage()).then(function () {
                new Login({
                    onsuccess: 'onSuccess'
                }).inject(document.getElement('.login'));
            });
        });


        //        var languages = <?php //echo json_encode($languages); ?>
        //
        //            document.id(window).addEvent('load', function () {
        //                require([
        //                    'qui/controls/buttons/Select'
        //                ], function (QUISelect) {
        //                    var Logo = document.getElement('.logo'),
        //                        Linp = document.getElement('.logininput');
        //
        //                    Logo.addClass('animated');
        //                    Logo.addClass('swing');
        //
        //                    document.id('username').focus();
        //
        //                    window.LangSelect = new QUISelect({
        //                        maxDropDownHeight: 300,
        //                        styles: {
        //                            marginLeft: 10,
        //                            width: 130
        //                        },
        //                        events: {
        //                            onChange: function (val) {
        //                                setLanguage(val);
        //                            }
        //                        }
        //                    }).inject(Linp);
        //
        //                    <?php
        //
        //                    $url_bin_dir = URL_BIN_DIR;
        //
        //                    foreach ($languages as $lang) {
        //                        $langText = '';
        //
        //                        switch ($lang) {
        //                            case 'de':
        //                                $langText = 'Deutsch';
        //                                break;
        //                            case 'en':
        //                                $langText = 'English';
        //                                break;
        //
        //                            default:
        //                                continue 2;
        //                        }
        //
        //                        echo "
        //
        //                            window.LangSelect.appendChild(
        //                                '{$langText}',
        //                                '{$lang}',
        //                                '{$url_bin_dir}16x16/flags/{$lang}.png'
        //                            );
        //
        //                        ";
        //                    }
        //
        //                    ?>
        //
        //                    // browser language
        //                    var lang = 'en';
        //
        //                    if ("language" in navigator) {
        //                        lang = navigator.language;
        //
        //                    } else if ("browserLanguage" in navigator) {
        //                        lang = navigator.browserLanguage;
        //
        //                    } else if ("systemLanguage" in navigator) {
        //                        lang = navigator.systemLanguage;
        //
        //                    } else if ("userLanguage" in navigator) {
        //                        lang = navigator.userLanguage;
        //                    }
        //
        //                    lang = lang.substr(0, 2);
        //
        //                    switch (lang) {
        //                        case 'de':
        //                        case 'en':
        //                            break;
        //
        //                        default:
        //                            lang = 'en';
        //                            break;
        //                    }
        //
        //                    window.setLanguage(lang);
        //
        //
        //                    document.id('username').focus();
        //                });
        //            });
        //
        //        var setLanguage = function (lang) {
        //            switch (lang) {
        //                case 'de':
        //                case 'en':
        //                    break;
        //
        //                default:
        //                    lang = 'en';
        //                    break;
        //            }
        //
        //            if (!languages.contains(lang)) {
        //                window.LangSelect.setValue(
        //                    window.LangSelect.firstChild().getAttribute('value')
        //                );
        //                return;
        //            }
        //
        //            if (window.LangSelect.getValue() != lang) {
        //                window.LangSelect.setValue(lang);
        //                return;
        //            }
        //
        //            require([
        //                'Locale',
        //                'locale/quiqqer/system/' + lang
        //            ], function (QUILocale) {
        //                QUILocale.setCurrent(lang);
        //
        //                document.getElements('[for="username"]').set(
        //                    'html',
        //                    QUILocale.get('quiqqer/system', 'username')
        //                );
        //
        //                document.getElements('[for="password"]').set(
        //                    'html',
        //                    QUILocale.get('quiqqer/system', 'password')
        //                );
        //
        //                document.getElements('[name="login"]').set(
        //                    'value',
        //                    QUILocale.get('quiqqer/system', 'login')
        //                );
        //            });
        //        };

        function onSuccess () {
            moofx(document.getElement('.container')).animate({
                opacity: 0
            }, {
                duration: 200,
                callback: function () {
                    window.location.reload();
                }
            });
        }

    </script>
</head>
<body>

<div class="container">
    <img src="<?php echo URL_BIN_DIR; ?>quiqqer_logo.png"
         alt="QUIQQER Login"
         title="QUIQQER Logo"
         class="logo"
    />

    <div class="login"></div>
</div>

<?php if (defined('LOGIN_FAILED')) { ?>
    <script type="text/javascript">
        require(['qui/QUI'], function () {
            QUI.getMessageHandler().then(function (MH) {
                MH.addError("<?php echo LOGIN_FAILED; ?>");
            });
        });
    </script>
<?php } ?>

</body>
</html>