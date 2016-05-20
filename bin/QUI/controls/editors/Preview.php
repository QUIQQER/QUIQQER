<?php

define('QUIQQER_SYSTEM', true);

$path = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));

require $path . '/header.php';

?>
<html>
<head>
    <base href="<?php echo URL_DIR; ?>" target="_blank">

    <script src="<?php echo URL_OPT_DIR; ?>bin/require.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-core.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-more.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/moofx.js"></script>

    <script>
        // require config
        require.config({
            baseUrl: <?php echo URL_BIN_DIR; ?> +'QUI/',
            paths  : {
                "package"    : <?php echo URL_OPT_DIR; ?>,
                "qui"        : <?php echo URL_OPT_DIR; ?> +'bin/qui/qui',
                "locale"     : <?php echo URL_VAR_DIR; ?> +'locale/bin',
                "URL_OPT_DIR": <?php echo URL_OPT_DIR; ?>,
                "URL_BIN_DIR": <?php echo URL_BIN_DIR; ?>,
                "Mustache"   : <?php echo URL_OPT_DIR; ?> +'bin/mustache/mustache.min'
            },

            waitSeconds: 0,
            locale     : window.parent.USER.lang + "-" + window.parent.USER.lang,
            catchError : true,
            urlArgs    : "d=" + window.parent.QUIQQER_VERSION.replace(/\./g, '_') + '_' + window.parent.QUIQQER.lu,

            map: {
                '*': {
                    'css'  : <?php echo URL_OPT_DIR; ?> +'bin/qui/qui/lib/css.js',
                    'image': <?php echo URL_OPT_DIR; ?> +'bin/qui/qui/lib/image.js',
                    'text' : <?php echo URL_OPT_DIR; ?> +'bin/qui/qui/lib/text.js'
                }
            }
        });
    </script>
</head>
<body>
<script>
    window.addEvent('load', function () {

        require(['qui/utils/String'], function (String) {
            var search = String.getUrlParams(window.location.search);

            if ("cid" in search) {
                var Preview = window.parent.QUI.Controls.getById(search.cid);

                if (Preview) {
                    Preview.$onLoad();
                }
            }
        });

    });
</script>
</body>
</html>