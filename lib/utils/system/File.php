<?php

/**
 * This file contains Utils_System_File
 */

/**
 * File Objekt
 * Contains methods for file operations
 *
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Moritz Leutz)
 *
 * @package com.pcsg.qui.utils.system
 */

class Utils_System_File
{
    /**
     * Return a array with all available mime types and they endings
     *
     * @return array
     */
    static function getMimeTypes()
    {
        return array(
            '.3dmf'  => 'x-world/x-3dmf',
            '.a' 	 => 'application/octet-stream',
            '.aab'   => 'application/x-authorware-bin',
            '.aam'   => 'application/x-authorware-map',
            '.aas'   => 'application/x-authorware-seg',
            '.abc'   => 'text/vnd.abc',
            '.acgi'  => 'text/html',
            '.afl'   => 'video/animaflex',
            '.ai'    => 'application/postscript',
            '.aif'   => 'audio/aiff',
            '.aif'   => 'audio/x-aiff',
            '.aifc'  => 'audio/aiff',
            '.aiff'  => 'audio/aiff',
            '.aim'   => 'application/x-aim',
            '.aip'   => 'text/x-audiosoft-intra',
            '.ani'   => 'application/x-navi-animation',
            '.aos'   => 'application/x-nokia-9000-communicator-add-on-software',
            '.aps'   => 'application/mime',
            '.arc'   => 'application/octet-stream',
            '.arj'   => 'application/arj',
            '.art'   => 'image/x-jg',
            '.asf'   => 'video/x-ms-asf',
            '.asm'   => 'text/x-asm',
            '.asp'   => 'text/asp',
            '.asx'   => 'video/x-ms-asf',
            '.au'    => 'audio/x-au',
            '.avi'   => 'video/avi',
            '.avs'   => 'video/avs-video',
            '.bcpio' => 'application/x-bcpio',
            '.bin'   => 'application/x-binary',

            '.bmp'   => 'image/bmp',
            '.bm'    => 'image/bmp',

            '.boo'   => 'application/book',
            '.book'  => 'application/book',
            '.boz'   => 'application/x-bzip2',
            '.bsh'   => 'application/x-bsh',
            '.bz'    => 'application/x-bzip',
            '.bz2'   => 'application/x-bzip2',
            '.c'     => 'text/plain',
            '.c++'   => 'text/plain',
            '.cat'   => 'application/vnd.ms-pki.seccat',
            '.cc'    => 'text/plain',
            '.ccad'  => 'application/clariscad',
            '.cco'   => 'application/x-cocoa',
            '.cdf'   => 'application/cdf',
            '.cer'   => 'application/pkix-cert',
            '.cer'   => 'application/x-x509-ca-cert',
            '.cha'   => 'application/x-chat',
            '.chat'  => 'application/x-chat',
            '.class' => 'application/java',
            '.com'   => 'text/plain',
            '.conf'  => 'text/plain',
            '.cpio'  => 'application/x-cpio',
            '.cpp'   => 'text/x-c',
            '.cpt'   => 'application/x-cpt',
            '.crl'   => 'application/pkix-crl',
            '.crt'   => 'application/pkix-cert',
            '.crt'   => 'application/x-x509-user-cert',
            '.csh'   => 'application/x-csh',
            '.css'   => 'text/css',
            '.cxx'   => 'text/plain',
            '.dcr'   => 'application/x-director',
            '.deepv' => 'application/x-deepv',
            '.def'   => 'text/plain',
            '.der'   => 'application/x-x509-ca-cert',
            '.dif'   => 'video/x-dv',
            '.dir'   => 'application/x-director',
            '.dl'    => 'video/dl',
            '.doc'   => 'application/msword',
            '.docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            '.dot'   => 'application/msword',
            '.dp'    => 'application/commonground',
            '.drw'   => 'application/drafting',
            '.dump'  => 'application/octet-stream',
            '.dv'    => 'video/x-dv',
            '.dvi'   => 'application/x-dvi',
            '.dwf'   => 'drawing/x-dwf',
            '.dwg'   => 'image/x-dwg',
            '.dxf'   => 'image/x-dwg',
            '.dxr'   => 'application/x-director',
            '.el'    => 'text/x-script.elisp',
            '.elc'   => 'application/x-elc',
            '.env'   => 'application/x-envoy',
            '.eps'   => 'application/postscript',
            '.es'    => 'application/x-esrehber',
            '.etx'   => 'text/x-setext',
            '.evy'   => 'application/envoy',
            '.exe'   => 'application/octet-stream',
            '.f'     => 'text/plain',
            '.f77'   => 'text/x-fortran',
            '.f90'   => 'text/x-fortran',
            '.fdf'   => 'application/vnd.fdf',
            '.fif'   => 'image/fif',
            '.fli'   => 'video/fli',
            '.flo'   => 'image/florian',
            '.flx'   => 'text/vnd.fmi.flexstor',
            '.fmf'   => 'video/x-atomic3d-feature',
            '.for'   => 'text/x-fortran',
            '.fpx'   => 'image/vnd.fpx',
            '.frl'   => 'application/freeloader',
            '.funk'  => 'audio/make',
            '.g'     => 'text/plain',
            '.g3'    => 'image/g3fax',
            '.gif'   => 'image/gif',
            '.gl'    => 'video/gl',
            '.gl'    => 'video/x-gl',
            '.gsd'   => 'audio/x-gsm',
            '.gsm'   => 'audio/x-gsm',
            '.gsp'   => 'application/x-gsp',
            '.gss'   => 'application/x-gss',
            '.gtar'  => 'application/x-gtar',
            '.gz'    => 'application/x-gzip',
            '.gzip'  => 'application/x-gzip',
            '.h'     => 'text/plain',
            '.hdf'   => 'application/x-hdf',
            '.help'  => 'application/x-helpfile',
            '.hgl'   => 'application/vnd.hp-hpgl',
            '.hh'    => 'text/plain',
            '.hlb'   => 'text/x-script',
            '.hlp'   => 'application/hlp',
            '.hpg'   => 'application/vnd.hp-hpgl',
            '.hpgl'  => 'application/vnd.hp-hpgl',
            '.hqx'   => 'application/binhex',
            '.hta'   => 'application/hta',
            '.htc'   => 'text/x-component',
            '.htm'   => 'text/html',
            '.html'  => 'text/html',
            '.htmls' => 'text/html',
            '.htt'   => 'text/webviewhtml',
            '.htx'   => 'text/html',
            '.ice'   => 'x-conference/x-cooltalk',
            '.ico'   => 'image/x-icon',
            '.idc'   => 'text/plain',
            '.ief'   => 'image/ief',
            '.iefs'  => 'image/ief',
            '.iges'  => 'application/iges',
            '.igs'   => 'application/iges',
            '.ima'   => 'application/x-ima',
            '.imap'  => 'application/x-httpd-imap',
            '.inf'   => 'application/inf',
            '.ins'   => 'application/x-internett-signup',
            '.ip'    => 'application/x-ip2',
            '.isu'   => 'video/x-isvideo',
            '.it'    => 'audio/it',
            '.iv'    => 'application/x-inventor',
            '.ivr'   => 'i-world/i-vrml',
            '.ivy'   => 'application/x-livescreen',
            '.jam'   => 'audio/x-jam',
            '.jav'   => 'text/plain',
            '.java'  => 'text/plain',
            '.jcm'   => 'application/x-java-commerce',

            '.jpg'  => 'image/jpeg',
            '.jpeg' => 'image/jpeg',
            '.jpg'  => 'image/jpeg',
            '.jfif' => 'image/jpeg',
            '.jfif-tbnl' => 'image/jpeg',

            '.jps'    => 'image/x-jps',
            '.js'     => 'application/x-javascript',
            '.jut'    => 'image/jutvision',
            '.kar'    => 'audio/midi',
            '.ksh'    => 'application/x-ksh',
            '.la'     => 'audio/nspaudio',
            '.lam'    => 'audio/x-liveaudio',
            '.latex'  => 'application/x-latex',
            '.lha'    => 'application/lha',
            '.lha'    => 'application/x-lha',
            '.lhx'    => 'application/octet-stream',
            '.list'   => 'text/plain',
            '.lma'    => 'audio/nspaudio',
            '.lma'    => 'audio/x-nspaudio',
            '.log'    => 'text/plain',
            '.lsp'    => 'application/x-lisp',
            '.lsp'    => 'text/x-script.lisp',
            '.lst'    => 'text/plain',
            '.lsx'    => 'text/x-la-asf',
            '.ltx'    => 'application/x-latex',
            '.lzh'    => 'application/x-lzh',
            '.lzx'    => 'application/x-lzx',

            '.m'   => 'text/plain',
            '.m1v' => 'video/mpeg',
            '.m2a' => 'audio/mpeg',
            '.m2v' => 'video/mpeg',
            '.m3u' => 'audio/x-mpequrl',
            '.man' => 'application/x-troff-man',
            '.map' => 'application/x-navimap',
            '.mar' => 'text/plain',
            '.mbd' => 'application/mbedlet',
            '.mcd' => 'application/mcad',
            '.mcf' => 'image/vasa',
            '.mcp' => 'application/netmc',
            '.me'  => 'application/x-troff-me',
            '.mht' => 'message/rfc822',

            '.mhtml' => 'message/rfc822',
            '.mid'   => 'audio/midi',
            '.midi'  => 'audio/midi',
            '.mif'   => 'application/x-mif',
            '.mime'  => 'www/mime',
            '.mjf'   => 'audio/x-vnd.audioexplosion.mjuicemediafile',
            '.mjpg'  => 'video/x-motion-jpeg',
            '.mm'    => 'application/x-meme',
            '.mme'   => 'application/b|ase64',
            '.mod'   => 'audio/mod',
            '.moov'  => 'video/quicktime',
            '.mov'   => 'video/quicktime',
            '.movie' => 'video/x-sgi-movie',
            '.mp2'   => 'video/mpeg',
            '.mp3'   => 'audio/mpeg3',
            '.mpa'   => 'video/mpeg',
            '.mpc'  => 'application/x-project',

            '.mpeg' => 'video/mpeg',
            '.mpe'  => 'video/mpeg',
            '.mpg'  => 'video/mpeg',
            '.mpga' => 'audio/mpeg',

            '.mpp' => 'application/vnd.ms-project',
            '.mpt' => 'application/x-project',
            '.mpv' => 'application/x-project',
            '.mpx' => 'application/x-project',
            '.mrc' => 'application/marc',
            '.ms' => 'application/x-troff-ms',
            '.mv' => 'video/x-sgi-movie',
            '.my' => 'audio/make',
            '.mzz'    => 'application/x-vnd.audioexplosion.mzz',
            '.nap'    => 'image/naplps',
            '.naplps' => 'image/naplps',
            '.nc'   => 'application/x-netcdf',
            '.ncm'  => 'application/vnd.nokia.configuration-message',
            '.nif'  => 'image/x-niff',
            '.niff' => 'image/x-niff',
            '.nix'  => 'application/x-mix-transfer',
            '.nsc'  => 'application/x-conference',
            '.nvd'  => 'application/x-navidoc',
            '.o'    => 'application/octet-stream',
            '.oda'  => 'application/oda',
            '.odt'  => 'application/vnd.oasis.opendocument.text',
            '.omc'  => 'application/x-omc',
            '.omcd' => 'application/x-omcdatamaker',
            '.omcr' => 'application/x-omcregerator',
            '.p'    => 'text/x-pascal',
            '.p10' => 'application/x-pkcs10',
            '.p12' => 'application/x-pkcs12',
            '.p7a' => 'application/x-pkcs7-signature',
            '.p7c' => 'application/x-pkcs7-mime',
            '.p7m' => 'application/x-pkcs7-mime',
            '.p7r' => 'application/x-pkcs7-certreqresp',
            '.p7s' => 'application/pkcs7-signature',
            '.part'  => 'application/pro',
            '.pas'   => 'text/pascal',
            '.pbm'   => 'image/x-portable-bitmap',
            '.pcl'   => 'application/x-pcl',
            '.pct'   => 'image/x-pict',
            '.pcx'   => 'image/x-pcx',
            '.pdb'   => 'chemical/x-pdb',
            '.pdf'   => 'application/pdf',
            '.pfunk' => 'audio/make',
            '.pgm'  => 'image/x-portable-graymap',
            '.pic'  => 'image/pict',
            '.pict' => 'image/pict',
            '.pkg' => 'application/x-newton-compatible-pkg',
            '.pko' => 'application/vnd.ms-pki.pko',
            '.pl'  => 'text/plain',
            '.plx' => 'application/x-pixclscript',
            '.pm'  => 'image/x-xpixmap',
            '.pm4' => 'application/x-pagemaker',
            '.pm5' => 'application/x-pagemaker',
            '.png' => 'image/png',
            '.pnm' => 'application/x-portable-anymap',
            '.pnm' => 'image/x-portable-anymap',
            '.pot' => 'application/mspowerpoint',
            '.pot' => 'application/vnd.ms-powerpoint',
            '.pov' => 'model/x-pov',
            '.ppa' => 'application/vnd.ms-powerpoint',
            '.ppm' => 'image/x-portable-pixmap',
            '.pps' => 'application/mspowerpoint',
            '.ppt' => 'application/powerpoint',
            '.ppz' => 'application/mspowerpoint',
            '.pre' => 'application/x-freelance',
            '.prt' => 'application/pro',
            '.ps'  => 'application/postscript',
            '.psd' => 'application/octet-stream',
            '.pvu' => 'paleovu/x-pv',
            '.pwz' => 'application/vnd.ms-powerpoint',
            '.py'  => 'text/x-script.phyton',
            '.pyc'  => 'applicaiton/x-bytecode.python',
            '.qcp'  => 'audio/vnd.qcelp',
            '.qd3'  => 'x-world/x-3dmf',
            '.qd3d' => 'x-world/x-3dmf',
            '.qif'  => 'image/x-quicktime',
            '.qt'   => 'video/quicktime',
            '.qtc'  => 'video/x-qtc',
            '.qti'  => 'image/x-quicktime',
            '.qtif' => 'image/x-quicktime',
            '.ra'   => 'audio/x-realaudio',
            '.ram'  => 'audio/x-pn-realaudio',
            '.ras'  => 'image/cmu-raster',
            '.rast' => 'image/cmu-raster',
            '.rexx' => 'text/x-script.rexx',
            '.rf'   => 'image/vnd.rn-realflash',
            '.rgb'  => 'image/x-rgb',
            '.rm'   => 'audio/x-pn-realaudio',
            '.rmi'  => 'audio/mid',
            '.rmm'  => 'audio/x-pn-realaudio',
            '.rmp'  => 'audio/x-pn-realaudio',
            '.rng'  => 'application/ringing-tones',
            '.rnx'  => 'application/vnd.rn-realplayer',
            '.roff' => 'application/x-troff',
            '.rp'  => 'image/vnd.rn-realpix',
            '.rpm' => 'audio/x-pn-realaudio-plugin',
            '.rt'  => 'text/richtext',
            '.rtf' => 'application/rtf',
            '.rtx' => 'application/rtf',
            '.rv'     => 'video/vnd.rn-realvideo',
            '.s'      => 'text/x-asm',
            '.s3m'    => 'audio/s3m',
            '.saveme' => 'application/octet-stream',
            '.sbk'  => 'application/x-tbook',
            '.scm'  => 'video/x-scm',
            '.sdml' => 'text/plain',
            '.sdp'  => 'application/sdp',
            '.sdr'  => 'application/sounder',
            '.sea'  => 'application/sea',
            '.set'  => 'application/set',
            '.sgm'  => 'text/sgml',
            '.sgml'  => 'text/sgml',
            '.sh'    => 'application/x-sh',
            '.shar'  => 'application/x-shar',
            '.shtml' => 'text/html',
            '.shtml' => 'text/x-server-parsed-html',
            '.sid' => 'audio/x-psid',
            '.sit' => 'application/x-sit',
            '.sit' => 'application/x-stuffit',
            '.skd' => 'application/x-koan',
            '.skm' => 'application/x-koan',
            '.skp' => 'application/x-koan',
            '.skt' => 'application/x-koan',
            '.sl'     => 'application/x-seelogo',
            '.smi'    => 'application/smil',
            '.smil'   => 'application/smil',
            '.snd'    => 'audio/basic',
            '.snd'    => 'audio/x-adpcm',
            '.sol'    => 'application/solids',
            '.spc'    => 'text/x-speech',
            '.spl'    => 'application/futuresplash',
            '.spr'    => 'application/x-sprite',
            '.sprite' => 'application/x-sprite',
            '.src'  => 'application/x-wais-source',
            '.ssi'  => 'text/x-server-parsed-html',
            '.ssm'  => 'application/streamingmedia',
            '.sst'  => 'application/vnd.ms-pki.certstore',
            '.step' => 'application/step',
            '.stl'  => 'application/sla',
            '.stp'  => 'application/step',
            '.sv4cpio' => 'application/x-sv4cpio',
            '.sv4crc'  => 'application/x-sv4crc',
            '.svf'  => 'image/x-dwg',
            '.swf'  => 'application/x-shockwave-flash',
            '.t'    => 'application/x-troff',
            '.talk' => 'text/x-speech',
            '.tar'  => 'application/x-tar',
            '.tbk'  => 'application/x-tbook',
            '.tcl'  => 'application/x-tcl',
            '.tcsh'    => 'text/x-script.tcsh',
            '.tex'     => 'application/x-tex',
            '.texi'    => 'application/x-texinfo',
            '.texinfo' => 'application/x-texinfo',
            '.text' => 'text/plain',
            '.tgz'  => 'application/x-compressed',
            '.tif'  => 'image/tiff',
            '.tiff' => 'image/tiff',
            '.tr'  => 'application/x-troff',
            '.tsi' => 'audio/tsp-audio',
            '.tsp' => 'application/dsptype',
            '.tsv' => 'text/tab-separated-values',
            '.turbot' => 'image/florian',
            '.txt'    => 'text/plain',
            '.uil'    => 'text/x-uil',
            '.uni'    => 'text/uri-list',
            '.unis'   => 'text/uri-list',
            '.unv'    => 'application/i-deas',
            '.uri'    => 'text/uri-list',
            '.uris'   => 'text/uri-list',
            '.ustar'  => 'application/x-ustar',
            '.uu'   => 'application/octet-stream',
            '.uue'  => 'text/x-uuencode',
            '.vcd'  => 'application/x-cdlink',
            '.vcs'  => 'text/x-vcalendar',
            '.vda'  => 'application/vda',
            '.vdo'  => 'video/vdo',
            '.vew'  => 'application/groupwise',
            '.viv'  => 'video/vivo',
            '.vivo' => 'video/vivo',
            '.vmd'  => 'application/vocaltec-media-desc',
            '.vmf'  => 'application/vocaltec-media-file',
            '.voc'  => 'audio/voc',
            '.vos'  => 'video/vosaic',
            '.vox'  => 'audio/voxware',
            '.vqe'  => 'audio/x-twinvq-plugin',
            '.vqf'  => 'audio/x-twinvq',
            '.vql'  => 'audio/x-twinvq-plugin',
            '.vrml' => 'application/x-vrml',
            '.vrt'  => 'x-world/x-vrt',
            '.vsd'  => 'application/x-visio',
            '.vst'  => 'application/x-visio',
            '.vsw'  => 'application/x-visio',
            '.w60'  => 'application/wordperfect6.0',
            '.w61'  => 'application/wordperfect6.1',
            '.w6w'  => 'application/msword',
            '.wav'  => 'audio/wav',
            '.wb1'  => 'application/x-qpro',
            '.wbmp' => 'image/vnd.wap.wbmp',
            '.web'  => 'application/vnd.xara',
            '.wiz'  => 'application/msword',
            '.wk1'  => 'application/x-123',
            '.wmf'  => 'windows/metafile',
            '.wml'  => 'text/vnd.wap.wml',
            '.wmlc'  => 'application/vnd.wap.wmlc',
            '.wmls'  => 'text/vnd.wap.wmlscript',
            '.wmlsc' => 'application/vnd.wap.wmlscriptc',
            '.word' => 'application/msword',
            '.wp'   => 'application/wordperfect',
            '.wp5'  => 'application/wordperfect',
            '.wp6'  => 'application/wordperfect',
            '.wpd'  => 'application/wordperfect',
            '.wq1'  => 'application/x-lotus',
            '.wri'  => 'application/x-wri',
            '.wrl'  => 'application/x-world',
            '.wrz'  => 'model/vrml',
            '.wsc'  => 'text/scriplet',
            '.wsrc' => 'application/x-wais-source',
            '.wtk' => 'application/x-wintalk',
            '.xbm' => 'image/xbm',
            '.xdr' => 'video/x-amt-demorun',
            '.xgz' => 'xgl/drawing',
            '.xif' => 'image/vnd.xiff',
            '.xl'  => 'application/excel',
            '.xla' => 'application/excel',
            '.xlb' => 'application/excel',
            '.xlc' => 'application/excel',
            '.xld' => 'application/excel',
            '.xlk' => 'application/excel',
            '.xll' => 'application/excel',
            '.xlm' => 'application/excel',
            '.xls' => 'application/excel',
            '.xlt' => 'application/excel',
            '.xlv' => 'application/excel',
            '.xlw' => 'application/excel',
            '.xm'  => 'audio/xm',
            '.xml' => 'text/xml',
            '.xmz' => 'xgl/movie',
            '.xpix'  => 'application/x-vnd.ls-xpix',
            '.xpm'   => 'image/x-xpixmap',
            '.xpm'   => 'image/xpm',
            '.x-png' => 'image/png',
            '.xsr' => 'video/x-amt-showrun',
            '.xwd' => 'image/x-xwd',
            '.xyz' => 'chemical/x-pdb',
            '.z'   => 'application/x-compressed',
            '.zip' => 'application/x-zip-compressed',
            '.zoo' => 'application/octet-stream',
            '.zsh' => 'text/x-script.zsh'
        );
    }

    /**
     * Formats the size of a file into a readable output format and append the ending
     *
     * @param Int $size - number in bytes
     * @param Int $round
     *
     * @return String
     */
    static function formatSize($size, $round=0)
    {
        $sizes = array('B', 'KB', 'MB', 'GB', 'TB');

        for ( $i = 0, $len = count( $sizes ); $i < $len-1 && $size >= 1024; $i++ ) {
            $size /= 1024;
        }

        return round( $size, $round ) .' '. $sizes[ $i ];
    }

    /**
     * Returns the Bytes of a php ini value
     *
     * @param String $val - 129M
     * @return Integer
     */
    static function getBytes($val)
    {
        if ( is_string( $val ) )
        {
            $val  = trim( $val );
            $last = strtolower( mb_substr($val, -1) );
        }

        switch ( $last )
        {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return (int)$val;
    }

    /**
     * Deletes a file or an entire folder
     * Only for QUIQQER use
     *
     * the unlink method unlink the file not really
     * it makes move to the tmp folder, because a move is faster
     *
     * @param String $file - Pfad zur Datei
     * @return Bool
     *
     * @throws QException
     */
    static function unlink($file)
    {
        if ( !file_exists($file) ) {
            return true;
        }

        if ( !is_dir($file) ) {
            return unlink($file);
        }

        // create a var_dir temp folder
        $var_folder = VAR_DIR .'tmp/'. str_replace(array(' ', '.'), '', microtime());

        while ( file_exists($var_folder) ) {
            $var_folder = VAR_DIR .'tmp/'. str_replace(array(' ', '.'), '', microtime());
        }

        // move to var dir, its faster
        return Utils_System_File::move($file, $var_folder);
    }

    /**
     * Move a file
     *
     * @param String $from - original file
     * @param String $to   - target
     */
    static function move($from, $to)
    {
        if ( file_exists($from) && !file_exists($to) )
        {
            rename( $from, $to );
            return true;
        }

        throw new QException( "Can't move File: ". $from .' -> '. $to, 500 );
    }

    /**
     * Copies a file, overwrite no file!
     * so the target may not exist
     *
     * @param String $from
     * @param String $to
     *
     * @throws QException
     * @return Bool
     */
    static function copy($from, $to)
    {
        if ( file_exists($to) ) {
            throw new QException('Can\'t copy File. File exists '. $to, 500);
        }

        if ( !file_exists($from) ) {
            throw new QException('Can\'t copy File. File not exists '. $from, 500);
        }

        return copy($from, $to);
    }

    /**
     * Get information about the file
     *
     * @param String $file  - Path to file
     * @param Array $params - (optional) ->
     *  filesize=Dateigrösse;
     * 	imagesize=Bildgrösse;
     *  mime_type=mime_type
     *
     *  @throws QException
     *  @return array
     */
    static function getInfo($file, $params=false)
    {
        if ( !file_exists($file) )
        {
            throw new QException(
                'Utils_System_File::getInfo()  File "'. $file .'" does not exist', 500
            );
        }

        $info = array();

        if ( isset($params['pathinfo']) || $params == false )
        {
            $p = pathinfo($file);

            $info = array(
                'dirname'   => false,
                'basename'  => false,
                'extension' => false,
                'filename'  => false
            );

            if ( isset($p['dirname']) ) {
                $info['dirname'] = $p['dirname'];
            }

            if ( isset($p['basename']) ) {
                $info['basename'] = $p['basename'];
            }

            if ( isset($p['extension']) ) {
                $info['extension'] = $p['extension'];
            }

            if ( isset($p['filename']) ) {
                $info['filename'] = $p['filename'];
            }
        }

        if ( isset($params['filesize']) || $params == false ) {
            $info['filesize'] = filesize($file);
        }

        if ( isset($params['imagesize']) || $params == false )
        {
            $r = getimagesize($file);

            $info['width']  = $r[0];
            $info['height'] = $r[1];
        }

        if ( isset($params['mime_type']) || $params == false )
        {
            if ( function_exists('mime_content_type') )
            // PHP interne Funktionen
            {
                $info['mime_type'] = mime_content_type($file);
            } elseif ( function_exists('finfo_open') && function_exists('finfo_file') )
            // PECL
            {
                $finfo = finfo_open( FILEINFO_MIME );
                $part  = explode(';', finfo_file($finfo, $file));
                $info['mime_type'] = $part[0];
            }

            // Falls beides nicht vorhanden ist
            // BAD
            if ( !isset($info['mime_type']) || empty($info['mime_type']) )
            {
                $file     = strtolower($file);
                $mimtypes = self::getMimeTypes();

                if ( isset($mimtypes[ strrchr($file, '.') ]) )
                {
                    $info['mime_type'] = $mimtypes[ strrchr($file, '.') ];
                } else
                {
                    $info['mime_type'] = false;
                }
            }
        }

        return $info;
    }

    /**
     * Return the file ending for a mimetype
     *
     * @param String $mime
     * @return String
     */
    static function getEndingByMimeType($mime)
    {
        $mimetypes = self::getMimeTypes();

        foreach ( $mimetypes as $ending => $mimetype )
        {
            if ( $mimetype == $mime ) {
                return $ending;
            }
        }

        return '';
    }

    /**
     * Bildgrösse ändern
     *
     * @param String $original - Pfad zum original Bild
     * @param String $new_image - Pfad zum neuen Bild
     * @param Integer $new_width
     * @param Integer $new_height
     * @return Bool
     * @deprecated Use Utils_Image::resize
     */
    static function resize($original, $new_image, $new_width=0, $new_height=0)
    {
        return \Utils_Image::resize($original, $new_image, $new_width, $new_height);
    }

    /**
     * Legt ein Wasserzeichen auf ein Bild
     *
     * @param String $image - Bild welches verändert werden soll
     * @param String $watermark - Wasserzeichen
     * @param String $newImage
     * @param Integer $top
     * @param Integer $left
     *
     * @deprecated Use Utils_Image::watermark
     */
    static function watermark($image, $watermark, $newImage=false, $top=0, $left=0)
    {
        return Utils_Image::watermark($image, $watermark, $newImage, $top, $left);
    }

    /**
     * Wandelt ein Bild in TrueColor um
     *
     * @param String $image - Path zum Bild
     * @deprecated Use Utils_Image::convertToTrueColor
     */
    static function convertToTrueColor($image)
    {
        return Utils_Image::convertToTrueColor($image);
    }

    /**
     * Dateien rekursiv aus einem Ordner lesen
     *
     * @param String $folder - Pfad zum Ordner
     * @return Array
     */
    public function readDirRecursiv($folder)
    {
        if (substr($folder, strlen($folder)-1) != '/') {
            $folder .= '/';
        }

        $this->_files        = array();
        $this->_start_folder = $folder;

        $this->_readDirRecursiv($folder);

        ksort($this->_files);

        return $this->_files;
    }

    /**
     * Helper Methode für readDirRecursiv
     *
     * @param String $folder
     */
    private function _readDirRecursiv($folder)
    {
        $_f   = $this->readDir($folder);
        $_tmp = str_replace($this->_start_folder, '', $folder);

        foreach ($_f as $f)
        {
            if (substr($folder, strlen($folder)-1) != '/') {
                $folder .= '/';
            }

            $dir = $folder.$f;

            if (is_dir($dir))
            {
                $this->_readDirRecursiv($dir.'/');
            } else
            {
                if ($folder == $this->_start_folder)
                {
                    $this->_files['/'][] = $f;
                } else
                {
                    $this->_files[$_tmp][] = $f;
                }
            }
        }
    }

    /**
     * Dateien eines Ordners auf dem Filesystem lesen
     *
     * @param String $folder       - Ordner welcher ausgelesen werdens oll
     * @param Bool $only_files     - Nur Dateien auslesen
     * @param Bool $order_by_date  - Nach Daum sortiert zurück geben
     * @return Array
     */
    static function readDir($folder, $only_files=false, $order_by_date=false)
    {
        if ( !is_dir( $folder ) ) {
            return array();
        }

        $folder = '/'. trim( $folder, '/' ) .'/';

        $handle = opendir( $folder );
        $files  = array();

        while ( $file = readdir( $handle ) )
        {
            if ( $file == "." || $file == ".." ) {
                continue;
            }

            if ( $only_files == true )
            {
                if ( is_file( $folder.$file ) && $order_by_date == false ) {
                    array_push( $files, $file );
                }

                if ( is_file( $folder.$file ) && $order_by_date == true ) {
                     $files[ filemtime( $folder.$file ) ] = $file;
                }

                continue;
            }

            if ( $order_by_date == true )
            {
                $files[ filemtime( $folder.$file ) ] = $file;
                continue;
            }

            array_push( $files, $file );
        }

        closedir( $handle );

        return $files;
    }

    /**
     * Löscht ein Verzeichnis rekursiv
     *
     * @param unknown_type $dir
     * @return unknown
     */
    static function deleteDir($dir)
    {
        if ( !file_exists( $dir ) ) {
            return true;
        }

        if ( !is_dir( $dir ) ) {
            return unlink( $dir );
        }

        foreach ( scandir( $dir ) as $item )
        {
            if ( $item == '.' || $item == '..' ) {
                continue;
            }

            $dirs = self::deleteDir(
                $dir . DIRECTORY_SEPARATOR . $item
            );

            if ( !$dirs ) {
                return false;
            }
        }

        return rmdir( $dir );
    }

    /**
     * Ladet eine Datei per HTTP herrunter und legt diese an einen bestimmten Ort
     *
     * @param String $host
     * @param String $path
     * @param String $local
     */
    static function download($host, $path, $local)
    {
        if ( file_exists( $local ) ) {
            throw new QException( 'Conflicting Request; Local File exist;', 409 );
        }

        $content = file_get_contents( 'http://'.$host.'/'.$path );
        file_put_contents( $local, $content );

        if ( file_exists( $local ) ) {
            return true;
        }

        throw new QException( $errstr, $errno );
    }

    /**
     * Send a file as download to the browser (maybe limited in speed)
     *
     * @param string $filePath
     * @param int $rate speedlimit in KB/s
     * @return void
     *
     * found on:
     * http://www.phpgangsta.de/dateidownload-via-php-mit-speedlimit-und-resume
     */
    static function send($filePath, $rate=0)
    {
        // Check if file exists
        if (!is_file($filePath)) {
            throw new QException('File not found.');
        }

        // get more information about the file
        $filename = basename($filePath);
        $size     = filesize($filePath);
        $finfo    = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, realpath($filePath));

        finfo_close($finfo);

        // Create file handle
        $fp = fopen($filePath, 'rb');

        $seekStart = 0;
        $seekEnd   = $size;

        // Check if only a specific part should be sent
        if (isset($_SERVER['HTTP_RANGE']))
        {
            // If so, calculate the range to use
            $range     = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
            $seekStart = intval($range[0]);

            if ($range[1] > 0) {
                $seekEnd = intval($range[1]);
            }

            // Seek to the start
            fseek($fp, $seekStart);

            // Set headers incl range info
            header('HTTP/1.1 206 Partial Content');
            header(sprintf('Content-Range: bytes %d-%d/%d', $seekStart, $seekEnd, $size));
        } else
        {
            // Set headers for full file
            header('HTTP/1.1 200 OK');
        }

        // Output some headers
        header('Cache-Control: private');
        header('Content-Type: ' . $mimetype);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header("Content-Description: File Transfer");
        header('Content-Length: ' . ($seekEnd - $seekStart));
        header('Accept-Ranges: bytes');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT');

        $block = 1024;
        // limit download speed
        if ($rate > 0) {
            $block *= $rate;
        }

        // disable timeout before download starts
        set_time_limit(0);

        // Send file until end is reached
        while (!feof($fp))
        {
            $timeStart = microtime(true);
            echo fread($fp, $block);
            flush();
            $wait = (microtime(true) - $timeStart) * 1000000;

            // if speedlimit is defined, make sure to only send specified bytes per second
            if ($rate > 0) {
                usleep(1000000 - $wait);
            }
        }

        // Close handle
        fclose($fp);
    }

    /**
     * Kopiert einen kompletten Ordner mit Unteordner
     *
     * @param String $srcdir
     * @param String $dstdir
     *
     * @return Bool
     */
    static function dircopy($srcdir, $dstdir)
    {
        Utils_System_File::mkdir( $dstdir );

        if ( substr( $dstdir, -1 ) != '/' ) {
            $dstdir = $dstdir .'/';
        }

        $File    = new Utils_System_File();
        $Files   = $File->readDirRecursiv( $srcdir );
        $errors  = array();

        foreach ( $Files as $folder => $file )
        {
            $File->mkdir( $dstdir . $folder );

            // files kopieren
            for ( $i = 0, $len = count( $file ); $i < $len; $i++ )
            {
                $from = $srcdir . $folder . $file[$i];
                $to   = $dstdir . $folder . $file[$i];

                try
                {
                    self::copy( $from, $to );
                } catch ( QException $e )
                {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if ( count( $errors ) ) {
            return $errors;
        }

        return true;
    }

    /**
     * Creates a folder
     * It can be given a complete path
     *
     * @param $path - Path which is to be created
     * @return Bool
     */
    static function mkdir($path)
    {
        // Wenn schon existiert dann schluss -> true
        if ( is_dir( $path ) || file_exists( $path ) ) {
            return true;
        }

        if ( substr( $path, -1, strlen( $path ) ) == '/' ) {
            $path = substr( $path, 0, -1 );
        }

        $p_e   = explode( '/', $path );
        $p_tmp = '';

        for ( $i = 0, $len = count( $p_e ); $i < $len; $i++ )
        {
            $p_tmp .= '/'.$p_e[ $i ];

            if ( $p_tmp == '/' ) {
                continue;
            }

            // windows fix
            if ( strpos( $p_tmp, ':' ) == 2)
            {
                if ( strpos( $p_tmp, '/' ) == 0 ) {
                    $p_tmp = substr( $p_tmp, 1 );
                }
            }

            $p_tmp = Utils_String::replaceDblSlashes( $p_tmp );

            if ( !self::checkOpenBaseDir( $p_tmp ) ) {
                continue;
            }

            if ( !is_dir( $p_tmp ) || !file_exists( $p_tmp ) ) {
                mkdir( $p_tmp );
            }
        }

        if ( is_dir( $path ) && file_exists( $path ) ) {
            return true;
        }

        return false;
    }

    /**
     * Erstellt eine Datei
     *
     * @param unknown_type $file
     * @return Bool
     */
    static function mkfile($file)
    {
        if ( file_exists( $file ) ) {
            return true;
        }

        return file_put_contents( $file, '' );
    }

    /**
     * Returns the content of a file, if file not exist, it returns an empty string
     *
     * @param String $file - path to file
     * @return String
     */
    static function getFileContent($file)
    {
        if ( !file_exists( $file ) ) {
            return '';
        }

        return file_get_contents( $file );
    }

    /**
     * Write the $line to the end of the file
     *
     * @param String $file - Datei
     * @param String $line - String welcher geschrieben werden soll
     */
    static function putLineToFile($file, $line='')
    {
        $fp = fopen( $file, 'a' );

        fwrite( $fp, $line ."\n" );
        fclose( $fp );
    }

    /**
     * Prüft ob die Datei innerhalb von open_basedir ist
     *
     * @param String $path - Pfad der geprüft werden soll
     */
    static function checkOpenBaseDir($path)
    {
        $obd = ini_get( 'open_basedir' );

        if ( empty( $obd ) ) {
            return true;
        }

        $obd = explode( ':', $obd );

        foreach ( $obd as $dir )
        {
            if ( strpos( $path, $dir ) === 0 ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $file
     */
    static function downloadHeader($file)
    {
        if ( !file_exists( $file ) ) {
            throw new QException( 'File not exist '.$file, 404 );
        }

        $finfo = self::getInfo( $file );

        header( 'Expires: Thu, 19 Nov 1981 08:52:00 GMT' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Pragma: no-cache' );
        header( 'Content-type: application/'. $finfo['extension'] );
        header( 'Content-Disposition: attachment; filename="'. basename( $file ) .'"' );

        // Inhalt des gespeicherten Dokuments senden
        readfile( $file );
        exit;
    }

    /**
     * Send a header for the file
     *
     * @param String $file - Path to file
     * @throws QException
     */
    static function fileHeader($file)
    {
        if ( !file_exists( $file ) ) {
            throw new QException( 'File not exist '. $file, 404 );
        }

        $finfo = self::getInfo( $file );

        header("Content-Type: ". $finfo['mime_type']);
        header("Expires: ". gmdate("D, d M Y H:i:s") . " GMT");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: inline; filename=\"". pathinfo($file, PATHINFO_BASENAME) ."\"");
        header("Content-Size: ". $finfo['filesize']);
        header("Content-Length: ". $finfo['filesize']);
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Connection: Keep-Alive");

        $fo_file = fopen( $file, "r" );
        $fr_file = fread( $fo_file, filesize( $file ) );
        fclose( $fo_file );

        echo $fr_file;
        exit;
    }

    /**
     * FileSize einer Datei bekommen (auch über eine URL)
     *
     * @param String $url
     * @return bytes
     */
    static function getFileSize($url)
    {
        if ( substr( $url, 0, 4 ) == 'http' )
        {
            $x = array_change_key_case(
                get_headers( $url, 1 ),
                CASE_LOWER
            );

            if ( !isset( $x['content-length'] ) ) {
                $x['content-length'] = '0';
            }

            if ( strcasecmp( $x[0], 'HTTP/1.1 200 OK' ) != 0 )
            {
                $x = $x['content-length'][1];
            } else
            {
                $x = $x['content-length'];
            }
        } else
        {
            $x = @filesize( $url );
        }

        return $x;
    }
}

?>