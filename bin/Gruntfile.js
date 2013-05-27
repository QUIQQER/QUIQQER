
/**
 * Needle:
 *
 * cd CMS_DIR/bin
 * sudo npm install -g grunt
 * sudo npm install -g gzip-js
 * sudo npm install -g clean-css
 * sudo npm install -g leaky
 * sudo npm install -g plato
 *
 * Usage:
 * cd CMS_DIR/bin/js
 *
 * 1. grunt
 * 2. grunt leaky
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * plato -r -d /var/www/git/cms/report -t QUIQQER /var/www/git/cms/bin/js/*
 */

module.exports = function(grunt)
{
    "use strict";

    // External libs.
    //var uglifyjs = require('uglify-js');
    var UglifyJS = require( "uglify-js" );
    var gzip     = require( 'gzip-js' );
    var cleanCSS = require( 'clean-css' );
    var leaky    = require( 'leaky' );

    // Project configuration.
    grunt.initConfig({
        pcsgmin : {
            dir: ['js/**/*']
        },
        leaky : {
            dir: ['js/**/*']
        }
    });

    // ==========================================================================
    // PCSG minify - minifies all javascript files and save it to *.min.js
    // ==========================================================================
    grunt.registerMultiTask('pcsgmin', 'pcsg minify', function(arg1, arg2)
    {
        //var foo = grunt.helper('pcsgmin');
        var files = grunt.file.expandFiles( this.file.src );
        var i, len, src, min, map, new_file, new_file_map, result;

        for ( i = 0, len = files.length; i < len; i++ )
        {
            if ( files[i].substr( 0, 3 ) != 'js/' ) {
                continue;
            }


            grunt.log.writeln( 'Start "' + files[i] );

            // all others files, no js and css
            if ( !files[i].match('.css') && !files[i].match('.js') ||
                 files[i].match('.json') )
            {
                new_file = 'js-min/'+ files[i].substr( 3 );

                grunt.log.writeln( '\nCopy "' + new_file + '" created.' );
                grunt.file.copy( files[i], new_file );

                continue;
            }


            // css files
            if ( files[i].match('.css') )
            {
                src = grunt.file.read( files[i] );
                min = cleanCSS.process( src );

                new_file  = 'js-min/'+ files[i].substr( 3 );

                grunt.file.write( new_file, min );

                grunt.log.writeln( '\nFile "' + new_file + '" created.' );
                grunt.helper( 'min_max_info', min, src );
                continue;
            }


            // js files
            src    = grunt.file.read( files[i] );
            result = grunt.helper( 'uglify', src, grunt.config('uglify') );

            min = result.code;
            map = result.map;

            new_file     = 'js-min/'+ files[i].substr( 3 );
            new_file_map = 'js-min/'+ files[i].substr( 3 ) +'.map';

            grunt.file.write( new_file, min );
            grunt.file.write( new_file_map, map );

            // Fail task if errors were logged.
            if ( this.errorCount ) {
                return false;
            }

            // Otherwise, print a success message....
            grunt.log.writeln( '\nFile "' + new_file + '" created.' );
            // ...and report some size information.
            grunt.helper( 'min_max_info', min, src );
        }
    });

    // checks unused variables
    grunt.registerMultiTask('leaky', 'Leaky Cleanup', function(arg1, arg2)
    {
        //console.log( this.files );
        //console.log( this.filesSrc );

        //var foo = grunt.helper('pcsgmin');
        var files  = grunt.file.expand( this.filesSrc ),
            errors = 0;

        var i, len, src, err;

        for ( i = 0, len = files.length; i < len; i++ )
        {
            if ( !files[i].match( '.js' ) ) {
                continue;
            }

            if ( files[i].match( 'mootools-core' ) ) {
                continue;
            }

            if ( files[i].match( 'Prism.js' ) ) {
                continue;
            }

            if ( files[i].match( 'lib/gridster' ) ) {
                continue;
            }

            if ( files[i].substr( 0, 3 ) != 'js/' ) {
                continue;
            }

            grunt.log.write( '.' );

            // js files
            src = grunt.file.read( files[i] );

            try
            {
                err = leaky( src );
            } catch ( e )
            {
                grunt.log.writeln( '==========================================' );
                grunt.log.writeln( '' );
                grunt.log.writeln( 'Error found in "' + files[i] );
                grunt.log.error( e );
                grunt.log.writeln( '' );

                errors++;

                err = false;
            }

            if ( err instanceof leaky.LeakError )
            {
                grunt.log.writeln( '==========================================' );
                grunt.log.writeln( '' );
                grunt.log.writeln( 'Error found in "' + files[i] );
                grunt.log.error( err );
                grunt.log.writeln( '' );

                errors++;
            }
        }

        if ( errors ) {
            grunt.fail.warn( 'found '+ errors +' errors' );
        }
    });
    // ==========================================================================
    // HELPERS
    // ==========================================================================

    // Minify with UglifyJS.
    // From https://github.com/mishoo/UglifyJS
    grunt.registerTask('uglify', function(src, options)
    {
        if ( !options ) {
            options = {};
        }

        //var jsp = uglifyjs.parser;
        //var pro = uglifyjs.uglify;
        var ast, pos, result;
        var msg = 'Minifying with UglifyJS...';

        grunt.verbose.write( msg );

        try
        {
            result = UglifyJS.minify(src, {
                outSourceMap : "out.js.map",
                fromString   : true
            });

            return result;

            /*
            ast = jsp.parse( src );
            ast = pro.ast_mangle(ast, options.mangle || {});
            ast = pro.ast_squeeze(ast, options.squeeze || {});
            src = pro.gen_code(ast, options.codegen || {});

            // Success!
            grunt.verbose.ok();

            // UglifyJS adds a trailing semicolon only when run as a binary.
            // So we manually add the trailing semicolon when using it as a module.
            // https://github.com/mishoo/UglifyJS/issues/126
            return src + ';';
            */
        } catch(e)
        {
            // Something went wrong.
            grunt.verbose.or.write( msg );

            pos = '['.red + ('L' + e.line).yellow + ':'.red + ('C' + e.col).yellow + ']'.red;

            grunt.log.error().writeln(
                pos + ' ' + (e.message + ' (position: ' + e.pos + ')').yellow
            );

            grunt.warn( 'UglifyJS found errors.', 10 );
        }
    });

     // Return gzipped source.
    grunt.registerTask('gzip', function(src) {
        return src ? gzip.zip(src, {}) : '';
    });

    // Output some size info about a file.
    grunt.registerTask('min_max_info', function(min, max)
    {
        var gzipSize = String( grunt.helper( 'gzip', min ).length );
        grunt.log.writeln('Uncompressed size: ' + String(max.length).green + ' bytes.');
        grunt.log.writeln('Compressed size: ' + gzipSize.green + ' bytes gzipped (' + String(min.length).green + ' bytes minified).');
    });


    // ==========================================================================
    // start all tasks
    // ==========================================================================
    //grunt.registerTask('default', 'pcsgmin leaky');

};
