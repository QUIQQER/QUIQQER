/**
 * QUIQQER Progress Bar
 *
 * based on dwProgressBar
 * http://davidwalsh.name/progress-bar-animated-mootools
 *
 * @author www.pcsg.de (Henning Leutz)
 * @class QUI.controls.progressbar.Progressbar
 * @package com.pcsg.qui.js.controls.progressbar
 */

define('controls/progressbar/Progressbar', [

    'controls/Control',
    'css!controls/progressbar/Progressbar.css'

], function(QUI_Control)
{
    QUI.namespace( 'controls.progressbar.Progressbar' );

    QUI.controls.progressbar.Progressbar = new Class({

        // implements
        Implements : [QUI_Control],
        Type       : 'QUI.controls.progressbar.Progressbar',

        // options
        options : {
            displayText : false,
            speed       : 10,
            fx          : true,

            boxClass        : 'progressbar',
            percentageClass : 'percantage',
            displayClass    : 'display',
            startPercentage : 0
        },

        // initialization
        initialize : function(options)
        {
            this.init( options );

            this.$Elm = null;
            this.$Perc = null;
            this.$Text = null;

            this.to = 0;
        },

          // creates the box and percentage elements
        create : function()
        {
            this.$Elm = new Element('div', {
                styles : {
                    position: 'relative'
                }
            });

            this.$Box = new Element('div', {
                'class' : this.options.boxClass
            });

            this.$Perc = new Element('div', {
                'class' : this.options.percentageClass,
                styles  : {
                    width : this.calculate( this.options.startPercentage.toInt() )
                }
            });

            this.$Perc.inject( this.$Box );
            this.$Box.inject( this.$Elm );

            if (this.options.displayText)
            {
                  this.$Text = new Element('div', {
                      id : this.options.displayClass
                  });

                  this.$Text.inject( this.$Elm );
            }

            this.width = this.$Box.getSize().x;

            return this.$Elm;
          },

        // calculates width in pixels from percentage
        calculate : function(percentage)
        {
            if (this.width === 0) {
                this.width = this.$Box.getSize().x;
            }

            return (this.width * (percentage / 100)).toInt();
        },

        // animates the change in percentage
        animate : function(to)
        {
            if ( to.toInt() > 100 ) {
                return;
            }

            if ( to.toInt() === 100 )
            {
                this.$Box.addClass('complete');
                this.fireEvent('onComplete', [this]);
            }

            if ( this.options.fx === false )
            {
                this.$Perc.setStyle('width', this.calculate( to.toInt() ));
            } else
            {
                this.$Perc.set('morph', {
                    duration : this.options.speed,
                    link     : 'cancel'
                }).morph({
                    width : this.calculate( to.toInt() )
                });
            }

            if ( this.options.displayText ) {
                this.Text.set('text', to.toInt() +'%');
            }
          },

        // sets the percentage from its current state to desired percentage
        set : function(to)
        {
            this.to = to;
            this.animate( this.to );
        },

        get: function()
        {
            return this.to;
        }
    });

    return QUI.controls.progressbar.Progressbar;
});
