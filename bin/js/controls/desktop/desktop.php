<?php

    /**
     * QUIQQER Widget Board
     *
     * You can access the Board without the QUIQQER Admin
     */

    if ( !\QUI::getUserBySession()->getId() )
    {

        exit;
    }

    $GRIDSTER_DIR = URL_BIN_DIR .'js/lib/gridster/';

?>
<!doctype html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="de"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="de"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="de"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html lang="de"> <!--<![endif]-->
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <link rel="stylesheet" href="<?php echo $GRIDSTER_DIR ?>normalize.css">
    <link rel="stylesheet" href="<?php echo $GRIDSTER_DIR ?>main.css">

    <link rel="stylesheet" href="<?php echo $GRIDSTER_DIR ?>jquery.gridster.min.css" />

    <title>QUIQQER - Desktop</title>

</head>
<body>

    <div class="gridster">
        <ul style="position: relative; ">
            <li data-row="1" data-col="1" data-sizex="2" data-sizey="2"></li>
            <li data-row="1" data-col="3" data-sizex="1" data-sizey="2"></li>
            <li data-row="1" data-col="4" data-sizex="1" data-sizey="1"></li>
            <li data-row="3" data-col="2" data-sizex="3" data-sizey="1"></li>

            <li data-row="4" data-col="1" data-sizex="1" data-sizey="1"></li>
            <li data-row="3" data-col="1" data-sizex="1" data-sizey="1"></li>
            <li data-row="4" data-col="2" data-sizex="1" data-sizey="1"></li>
            <li data-row="5" data-col="2" data-sizex="1" data-sizey="1"></li>
            <li data-row="4" data-col="4" data-sizex="1" data-sizey="1"></li>

            <li data-row="1" data-col="5" data-sizex="1" data-sizey="3"></li>
        </ul>
    </div>


    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>

    <script src="<?php echo $GRIDSTER_DIR ?>jquery.gridster.min.js"
        type="text/javascript"
        charset="utf-8"
    ></script>

    <script src="<?php echo $GRIDSTER_DIR ?>jquery.gridster.with-extras.min.js"
        type="text/javascript"
        charset="utf-8"
    ></script>

    <script>

        $( document ).ready(function ()
        {
            $('.gridster').width( $( document.body ).width() - 20 );

            $(".gridster ul").gridster({
                widget_margins: [10, 10],
                widget_base_dimensions: [140, 140]
            });
        });

        $( window ).resize(function()
        {
            // var gridster = $('.gridster ul').gridster().data('gridster');
            // console.log( gridster.serialize() );
        });

    </script>

</body>
</html>