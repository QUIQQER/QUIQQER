<?php

/**
 * This file contains Interface_Users_User
 */

/**
 * Class for handling ini files
 *
 * @author www.pcsg.de (Moritz Scholz)
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 *
 * @copyright  2008 PCSG
 * @since      Class available since Release QUIQQER 0.1
 *
 * @todo translate the docu
 */

class QConfig
{
    /**
     * filename
     * @var String
     */
	private $_iniFilename = '';

	/**
	 * ini entries
	 * @var array
	 */
	private $_iniParsedArray = array();

    /**
     * constructor
     *
     * @param String $filename
     * @return Bool
     */
    public function __construct($filename='')
    {
    	if ( !file_exists( $filename ) ) {
    		return false;
    	}

        $this->_iniFilename = $filename;

        if ( $this->_iniParsedArray = parse_ini_file( $filename, true ) ) {
        	return true;
        }

        return false;
    }

    /**
     * Ini Einträge als Array bekommen
     *
     * @return Array
     */
    public function toArray()
    {
    	return $this->_iniParsedArray;
    }

    /**
     * Return the ini as json decode
     *
     * @return String
     */
    public function toJSON()
    {
    	return json_decode( $this->_iniParsedArray, true );
    }

    /**
     * Gibt eine komplette Sektion zurück
     *
     * @param String $key
     * @return String || Array
     */
    public function getSection($key)
    {
    	if ( !isset( $this->_iniParsedArray[ $key ] ) ) {
			return false;
    	}

        return $this->_iniParsedArray[ $key ];
    }

	/**
	 * Gibt einen Wert aus einer Sektion zurück
	 *
	 * @param String $section
	 * @param String $key
	 * @return String || Array
	 */
	public function getValue($section, $key)
    {
        if ( !isset( $this->_iniParsedArray[ $section ] ) ||
        	 !isset( $this->_iniParsedArray[ $section ][ $key ] ))
        {
        	return false;
        }

        return $this->_iniParsedArray[ $section ][ $key ];
    }

    /**
     * Gibt den Wert einer Sektion  oder die ganze Section zurück
     *
     * @param String $section
     * @param String || NULL $key (optional)
     * @return String || Array
     */
    public function get($section, $key=null)
    {
        if ( is_null( $key ) ) {
        	return $this->getSection( $section );
        }

        return $this->getValue( $section, $key );
    }

    /**
     * Gibt den Dateinamen der Config zurück
     * @return String
     */
    public function getFilename()
    {
        return $this->_iniFilename;
    }

    /**
     * Setzt eine komplette Sektion
     *
     * @param String $section
     * @param Array|String $array
     * @return unknown
     */
    public function setSection($section=false, $array)
    {
        if ( !isset( $array ) || !is_array( $array ) ) {
        	return false;
        }

        if ( $section ) {
        	return $this->_iniParsedArray[ $section ] = $array;
        }

        return $this->_iniParsedArray[] = $array;
    }

    /**
     * Setzt einen neuen Wert in einer Sektion
     *
     * @param String $section
     * @param String $key
     * @param String $value
     * @return Bool
     *
     * @example QConfig->setValue('section', null, 'something');
     * @example QConfig->setValue('section', 'entry', 'something');
     */
    public function setValue($section, $key=null, $value)
    {
		if ( $key == null )
		{
			if ( $this->_iniParsedArray[ $section ] = $value ) {
				return true;
			}
		}

    	if ( $this->_iniParsedArray[ $section ][ $key ] = $value ) {
        	return true;
        }

        return false;
    }

    /**
     * exist the section or value?
     *
     * @param String $section
     * @param String $key
     */
    public function existValue($section, $key=null)
    {
        if ( $key === null ) {
            return isset( $this->_iniParsedArray[ $section ] ) ? true : false;
		}

		if ( !isset( $this->_iniParsedArray[ $section ] ) ) {
            return false;
		}

		return isset( $this->_iniParsedArray[ $section ][ $key ] ) ? true : false;
    }

    /**
     * Setzt einen neuen Wert in einer Sektion oder eine gesamte neue Sektion
     *
     * @param String $section
     * @param String $key
     * @param String $value
     * @return Bool
     */
    public function set($section=false, $key=null, $value=null)
    {
		if ( is_array( $key ) && is_null( $value ) ) {
        	return $this->setSection( $section, $key );
        }

        return $this->setValue( $section, $key, $value );
    }

    /**
     * Löscht eine Sektion oder ein Key in der Sektion
     *
     * @param String $section
     * @param String $key - optional, wenn angegeben wird Key gelöscht ansonsten komplette Sektion
     * @return Bool
     */
    public function del($section, $key=null)
    {
    	if ( !isset( $this->_iniParsedArray[ $section ] ) ) {
    		return true;
    	}

		if ( is_null( $key ) )
		{
			unset( $this->_iniParsedArray[ $section ] );
			return true;
		}

		if ( isset( $this->_iniParsedArray[ $section ][ $key ] ) ) {
			unset( $this->_iniParsedArray[ $section ][ $key ] );
		}

		if ( isset( $this->_iniParsedArray[ $section ][ $key ] ) ) {
			return false;
		}

		return true;
    }

    /**
     * Speichert die Einträge in die INI Datei
     *
     * @param String $filename - Pfad zur Datei
     * @return Bool
     */
	public function save($filename=null)
	{
		if ( $filename == null ) {
			$filename = $this->_iniFilename;
		}

		if ( !is_writeable( $filename ) ) {
    		throw new QException( 'Config is not writable' );
		}

		$SFfdescriptor = fopen( $filename, "w" );

		foreach ( $this->_iniParsedArray as $section => $array )
		{
			if ( is_array( $array ) )
			{
				fwrite( $SFfdescriptor, "[". $section ."]\n" );

				foreach ( $array as $key => $value ) {
					fwrite( $SFfdescriptor, $key .'="'. $this->_clean( $value ) ."\"\n" );
				}

				fwrite( $SFfdescriptor, "\n" );

			} else
			{
				fwrite( $SFfdescriptor, $section .'="'. $this->_clean( $array ) ."\"\n" );
			}
		}

		fclose( $SFfdescriptor );
	}

	/**
	 * Zeilenumbrüche löschen
	 *
	 * @param String $value
	 * @return String
	 */
	protected function _clean($value)
	{
		$value = str_replace( array("\r\n","\n","\r"), '', $value);
		$value = str_replace( '"', '\"', $value );

		return $value;
	}
}
?>