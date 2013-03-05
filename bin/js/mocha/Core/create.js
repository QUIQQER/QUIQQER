/*
 ---

 script: create.js

 description: core content control creation and plugin loading routines

 copyright: (c) 2011 Contributors in (/AUTHORS.txt).

 license: MIT-style license in (/MIT-LICENSE.txt).

 requires:
 - MochaUI/MUI

 provides: [MUI.create, MUI.load]

 ...
 */

MUI.append({

    load : function(options)
    {
        // convert none hash parameters to hash
        if ( typeOf(options) == 'string' )
        {
            options = {
                control  : options,
                loadOnly : true,
                onload   : (arguments.length > 0) ? arguments[1] : null
            };
        }

        if ( typeOf(options) == 'array' )
        {
            var controls = [];

            for ( var j = 0, len = options.length; j < len; j++ )
            {
                controls.push({
                    control : options[j]
                });
            }

            options = {
                controls : controls,
                onload   : (arguments.length > 1) ? arguments[1] : null,
                loadOnly : true
            };
        }

        //MUI.create(options);
    },

    create : function(options)
    {
        // convert none hash parameters to hash
        if ( typeOf(options) == 'string' )
        {
            options = {
                control : options,
                onload  : (arguments.length > 1) ? arguments[1] : null
            };
        }

        if ( !MUI.initialized ) {
            MUI.initialize(); // initialize mocha if needed
        }

        // convert array of plugin names to controls request
        var controls = options.controls;

        if ( !controls ) {
            controls = [];
        }

        if ( typeOf(options) == 'array' )
        {
            for (var j = 0; j < options.length; j++) {
                controls.push({control:options[j]});
            }

            options = {
                controls : controls,
                onload   : options.onload
            };
        }

        if ( controls.length === 0 ) {
            controls = [options]; // make sure we have an array for list of controls to load
        }

        // better with requiresjs
        var name;
        var needles = [];

        for (var i = 0, len = controls.length; i < len; i++)
        {
            name = controls[i].control.replace(/(^MUI\.)/i, '');
            name = 'mocha/Controls/' + name.toLowerCase() +'/'+ name.toLowerCase();

            needles.push( name );
        }

        require(needles, function()
        {
            this.each(function(control)
            {
                if (control.loadOnly) {
                    return;
                }

                if (control.onload) {
                    control.onload( control );
                }

                var name  = control.control.replace(/(^MUI\.)/i, '');
                var klass = MUI[name];

                if ( !klass ) {
                    return;
                }

                var instance = new klass( control );

                //MUI.set(instance.id, instance);

                if (control.onNew) {
                    control.onNew( instance );
                }

                if ( typeof instance.css !== 'undefined' ) {
                    QUI.css( MUI.replacePaths( instance.css ) );
                }

                if (control.fromHTML && instance.fromHTML) {
                    instance.fromHTML();
                }
            });

        }.bind( controls ));
    }
});
