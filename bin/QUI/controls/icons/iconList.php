<?php

if (!isset($_REQUEST['quiid'])) {
    exit;
}

define('QUIQQER_SYSTEM', true);

require dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))) . '/header.php';

$Icons = new QUI\Icons\Handler();

$icons = $Icons->toArray();
$files = $Icons->getCSSFiles();

?>
<html>
<head>

    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-core.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/mootools-more.js"></script>
    <script src="<?php echo URL_OPT_DIR; ?>bin/qui/qui/lib/moofx.js"></script>
    <script>
        var QUI_ID = '<?php echo htmlspecialchars($_REQUEST['quiid']); ?>';
    </script>

    <style>
        * {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }

        .icons {
            width: 100%;
            height: 100%;
        }

        .icons-entry {
            border: 1px solid rgba(0, 0, 0, 0.2);
            float: left;
            height: 80px;
            text-align: center;
            margin: 10px;
            padding: 10px;
            position: relative;
            width: 80px;
        }

        .icon-entry-title {
            background: #666;
            bottom: 0;
            color: #fff;
            left: 0;
            font-size: 12px !important;
            line-height: 14px !important;
            text-align: center;
            overflow: hidden;
            padding: 0 5px;
            position: absolute;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 100%;
        }

        .icons-entry span {
            font-size: 24px;
            line-height: 50px;
        }

        .active,
        .icons-entry:hover {
            background: rgba(0, 0, 0, 0.6);
            cursor: pointer;
            color: #fff;
        }

        .no-results-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            justify-content: center;
            opacity: 0;
            font-family: 'Open Sans', sans-serif;
        }

        .no-results-info .fa {
            margin-top: 20px;
            font-size: 40px;
        }
    </style>

    <?php foreach ($files as $file) { ?>
        <link href="<?php echo htmlspecialchars($file); ?>"
              rel="stylesheet"
              type="text/css"
        />
    <?php } ?>

</head>
<body>

<div class="icons">
    <?php foreach ($icons as $icon) { ?>
        <div class="icons-entry"
             data-icon="<?php echo htmlspecialchars($icon); ?>"
             title="<?php echo htmlspecialchars($icon); ?>"
        >
            <span class="<?php echo htmlspecialchars($icon); ?>"></span>
            <span class="icon-entry-title"><?php echo htmlspecialchars($icon); ?></span>
        </div>
    <?php } ?>
</div>

<script>
    var Confirm = window.parent.QUI.Controls.getById(QUI_ID),
        entries = document.body.getElements('.icons-entry');

    entries.addEvents({
        click   : function (event) {
            var Target = event.target;

            if (!Target.hasClass('icons-entry')) {
                Target = Target.getParent('.icons-entry');
            }

            if (!event.control) {
                entries.removeClass('active');
            }

            Target.addClass('active');
        },
        dblclick: function (event) {
            this.fireEvent('click', [event]);
            Confirm.submit();
        }
    });

    window.getSelected = function () {
        return document.body.getElements('.active').map(function (Node) {
            return Node.get('data-icon');
        });
    }
</script>

</body>
</html>
