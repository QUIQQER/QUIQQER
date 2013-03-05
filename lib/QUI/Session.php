<?php

/**
 * This file contains QUI_Session
 */

/**
 * Session handling for QUIQQER
 *
 * @author www.pcsg.de (Moritz Scholz)
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Session
{
    /**
     * max time life in seconds
     * @var Integer
     */
	private $_max_life_time = 0;

	/**
	 * constructor
	 */
	public function __construct()
	{
		// Einstellungen
		$this->_max_life_time = 1400; // default

		if ( $sess = QUI::conf( 'session', 'max_life_time' ) ) {
			$this->_max_life_time = $sess;
		}

		ini_set( 'session.gc_maxlifetime', $this->_max_life_time );
		ini_set( "session.gc_probability", 100 );

		session_save_path( VAR_DIR .'sessions/' );
	    session_name( 'QUI_SESSION' );

		if ( !isset( $_SESSION ) ) {
			session_start();
		}

		session_regenerate_id();

		if ( !isset( $_SESSION['QUI_START'] ) ) {
			$this->refresh();
		}
	}

	/**
	 * Set a variable to the session
	 *
	 * @param String $var - Name og the variable
	 * @param String $value - value of the variable
	 */
	public function set($var, $value)
	{
		if ( is_object( $value ) ) {
			throw new QException( 'You cannot set an Object to the Session' );
		}

		$_SESSION[ $var ] = $value;
	}

	/**
	 * refresh the session and extend the session time
	 */
	public function refresh()
	{
		$_SESSION['QUI_START'] = time();
	}

	/**
	 * returns a variable from the session
	 *
	 * @param String $var - name of the variable
	 * @return false|Array
	 */
	public function get($var)
	{
		if ( !$this->check() ) {
			return false;
		}

		if ( isset( $_SESSION[ $var ] ) ) {
			return $_SESSION[ $var ];
		}

		return false;
	}

	/**
	 * returns the session-id
	 *
	 * @return String
	 */
	public function getId()
	{
		return session_id();
	}

	/**
	 * Checks the validity of the session
	 *
	 * @return Bool
	 */
	public function check()
	{
		if ( !isset( $_SESSION[ 'QUI_START' ] ) )
		{
		    $this->destroy();
			return false;
		}


		// Max Time prüfen
		if ( time() - $_SESSION['QUI_START'] > $this->_max_life_time )
		{
			$this->destroy();
			return false;
		}

		$_SESSION['QUI_START'] = time();
		return true;
	}

	/**
	 * Delete a session variable
	 *
	 * @param String $var - name of the variable
	 * @return Bool
	 */
	public function del($var)
	{
		if ( isset( $_SESSION[ $var ] ) )
		{
			unset( $_SESSION[ $var ] );
			return true;
		}

		return false;
	}

	/**
	 * Destroy the whole session
	 */
	public function destroy()
	{
		$_SESSION = array();

		if ( $this->getId() == false ) {
			return;
		}

		session_destroy();
	}
}

?>