<?php

/**
 * This file contains the Utils_Text_DocToText
 */

/**
 * Extract content from various file formats to text
 *
 * @uses ZipArchive
 * @requires ZipArchive
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.text
 */

class Utils_Text_DocToText
{
    /**
     * Returns the content from a odx / docx file
     *
     * @param String $file - path to file
     *
     * @throws QException
     * @return String
     */
    static function convert($file)
    {
        if (!file_exists($file)) {
            throw new QException('File could not be read.', 404);
        }

        $Zip = new ZipArchive();

        if ($Zip->open($file) === false) {
            throw new QException('File could not be read.', 404);
        }

        $data = Utils_System_File::getInfo($file, array(
        	'mime_type' => true
        ));

        // doc
        switch ($data['mime_type'])
        {
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                $ln = "word/document.xml";
            break;

            // odt
            case 'application/zip':
            case 'application/x-vnd.oasis.opendocument.text':
            case 'application/vnd.oasis.opendocument.text':
                $ln = "content.xml";
            break;

            default:
                throw new QException('Unbekanntes Format.');
        }

        if (($index = $Zip->locateName($ln)) !== false)
        {
            $str = $Zip->getFromIndex($index);
            $Doc = new DOMDocument();
            $Doc->loadXML($str, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

            $text = strip_tags($Doc->saveXML());

            $Zip->close();

            return $text;
        }

        return '';
    }

    /**
     * Convert a microsoft .doc file to text
     * from: http://blog.folkeraxmann.de/?p=318
     *
     * @param String $filename - Path to filename
     * @return String
     * @throws QException
     */
    static function convertDoc($filename)
    {
        if ( !file_exists($filename) ) {
            throw new QException('File could not be read.', 404);
        }

        if ( !($fh = fopen($filename, 'r')) ) {
            throw new QException('File could not be read.', 404);
        }

        $headers = fread($fh, 0xA00);

        # 1 = (ord(n)*1) ; Document has from 0 to 255 characters
    	$n1 = (ord($headers[0x21C]) - 1);

    	# 1 = ((ord(n)-8)*256) ; Document has from 256 to 63743 characters
    	$n2 = ( (ord($headers[0x21D]) - 8) * 256 );

    	# 1 = ((ord(n)*256)*256) ; Document has from 63744 to 16775423 characters
    	$n3 = ( (ord($headers[0x21E]) * 256) * 256 );

    	# (((ord(n)*256)*256)*256) ; Document has from 16775424 to 4294965504 characters
    	$n4 = ( ( (ord($headers[0x21F]) * 256) * 256 ) * 256 );

    	# Total length of text in the document
        $textLength          = ($n1 + $n2 + $n3 + $n4);
        $extracted_plaintext = fread($fh, $textLength);

        return utf8_encode( nl2br($extracted_plaintext) );
    }
}

?>