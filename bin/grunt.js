
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
    var uglifyjs = require('uglify-js');
    var gzip     = require('gzip-js');
    var cleanCSS = require('clean-css');
    var leaky    = require('leaky');

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
        var files = grunt.file.expandFiles(this.file.src);
        var i, len, src, min, new_file;

        for ( i = 0, len = files.length; i < len; i++ )
        {
            if ( files[i].substr( 0, 3 ) != 'js/' ) {
                continue;
            }


            grunt.log.writeln( 'Start "' + files[i] );

            // all others files, no js and css
            if ( !files[i].match('.css') &&
                 !files[i].match('.js') ||
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
            src = grunt.file.read( files[i] );
            min = grunt.helper('uglify', src, grunt.config('uglify'));

            new_file = 'js-min/'+ files[i].substr( 3 );

            grunt.file.write( new_file, min );

            // Fail task if errors were logged.
            if (this.errorCount) {
                return false;
            }

            // Otherwise, print a success message....
            grunt.log.writeln( '\nFile "' + new_file + '" created.' );
            // ...and report some size information.
            grunt.helper( 'min_max_info', min, src );
        }
    });

    // ==========================================================================
    // HELPERS
    // ==========================================================================

    // Minify with UglifyJS.
    // From https://github.com/mishoo/UglifyJS
    grunt.registerHelper('uglify', function(src, options)
    {
        if (!options) { options = {}; }
        var jsp = uglifyjs.parser;
        var pro = uglifyjs.uglify;
        var ast, pos;
        var msg = 'Minifying with UglifyJS...';
        grunt.verbose.write(msg);
        try {
            ast = jsp.parse(src);
            ast = pro.ast_mangle(ast, options.mangle || {});
            ast = pro.ast_squeeze(ast, options.squeeze || {});
            src = pro.gen_code(ast, options.codegen || {});
            // Success!
            grunt.verbose.ok();
            // UglifyJS adds a trailing semicolon only when run as a binary.
            // So we manually add the trailing semicolon when using it as a module.
            // https://github.com/mishoo/UglifyJS/issues/126
            return src + ';';
        } catch(e)
        {
            // Something went wrong.
            grunt.verbose.or.write(msg);
            pos = '['.red + ('L' + e.line).yellow + ':'.red + ('C' + e.col).yellow + ']'.red;
            grunt.log.error().writeln(pos + ' ' + (e.message + ' (position: ' + e.pos + ')').yellow);
            grunt.warn('UglifyJS found errors.', 10);
        }
    });

     // Return gzipped source.
    grunt.registerHelper('gzip', function(src) {
        return src ? gzip.zip(src, {}) : '';
    });

    // Output some size info about a file.
    grunt.registerHelper('min_max_info', function(min, max)
    {
        var gzipSize = String(grunt.helper('gzip', min).length);
        grunt.log.writeln('Uncompressed size: ' + String(max.length).green + ' bytes.');
        grunt.log.writeln('Compressed size: ' + gzipSize.green + ' bytes gzipped (' + String(min.length).green + ' bytes minified).');
    });

    // checks unused variables
    grunt.registerMultiTask('leaky', 'leaky check', function(arg1, arg2)
    {
      //var foo = grunt.helper('pcsgmin');
        var files  = grunt.file.expandFiles( this.file.src ),
            errors = 0;

        var i, len, src, err;

        for ( i = 0, len = files.length; i < len; i++ )
        {
            if ( !files[i].match('.js') ) {
                continue;
            }

            if ( files[i].match('mootools-core') ) {
                continue;
            }

            if ( files[i].match('js/media/') ) {
                continue;
            }

            if ( files[i].substr( 0, 3 ) != 'js/' ) {
                continue;
            }

            grunt.log.write('.');

            // js files
            src = grunt.file.read( files[i] );
            err = leaky( src );

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
    // start all tasks
    // ==========================================================================
    grunt.registerTask('default', 'pcsgmin leaky');

};
