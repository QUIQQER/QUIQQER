<?php

/**
 * This file contains Users_Adress
 */

/**
 * User Adress
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.users
 */

class Users_Adress extends QDOM
{
    /**
     * The user
     * @var Users_User
     */
	protected $_User = null;

	/**
	 * Adress ID
	 * @var Integer
	 */
	protected $_id = false;

	/**
	 * constructor
	 *
	 * @param Users_User $User  - User
	 * @param Integer $id 		- Adress id
	 */
	public function __construct(Users_User $User, $id)
	{
		$result = QUI::getDataBase()->fetch(array(
			'from'  => Users_Users::TableAdress(),
			'where' => array(
				'id'  => (int)$id,
				'uid' => $User->getId()
			),
			'limit' => '1'
		));

		$this->_User = $User;
		$this->_id   = (int)$id;

		if ( !isset( $result[0] ) )
		{
			throw new QException(
			    QUI::getLocale()->get(
			    	'system',
			    	'exception.lib.user.adress.not.found'
			    )
		    );
		}

		unset($result[0]['id']);
		unset($result[0]['uid']);

		$this->setAttributes($result[0]);
	}

	/**
	 * ID der Adresse
	 *
	 * @return Integer
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Telefon Nummer hinzufügen
	 *
	 * @param Array $phone
	 *
	 * @example addPhone(array(
	 * 		'no'   => '555 29 29',
	 *      'type' => 'tel'
	 * ))
	 */
	public function addPhone($phone)
	{
		if (!is_array($phone)) {
			return;
		}

		if (!isset($phone['no'])) {
			return;
		}

		if (!isset($phone['type'])) {
			return;
		}

		if ($phone['type'] != 'tel' &&
			$phone['type'] != 'fax' &&
			$phone['type'] != 'mobile')
		{
			return;
		}

		$list = $this->getPhoneList();

		foreach ($list as $entry)
		{
			if ($entry['type'] == $phone['type'] &&
				$entry['no'] == $phone['no'])
			{
				return;
			}
		}

		$list[] = $phone;

		$this->setAttribute('phone', json_encode($list));
	}

	/**
	 * Editier ein bestehenden Eintrag
	 *
	 * @param unknown_type $index
	 * @param unknown_type $phone
	 */
	public function editPhone($index, $phone)
	{
		$index = (int)$index;

		if (!is_array($phone)) {
			return;
		}

		if (!isset($phone['no'])) {
			return;
		}

		if (!isset($phone['type'])) {
			return;
		}

		$list = $this->getPhoneList();

		$list[$index] = $phone;

		$this->setAttribute('phone', json_encode($list));
	}

	/**
	 * Löscht die Phoneliste
	 */
	public function clearPhone()
	{
		$this->setAttribute('phone', array());
	}

	/**
	 * Telefon Liste
	 *
	 * @return Array
	 */
	public function getPhoneList()
	{
		if (is_array($this->getAttribute('phone'))) {
			return $this->getAttribute('phone');
		}

		$result = json_decode($this->getAttribute('phone'), true);

		if (is_array($result)) {
			return $result;
		}

		return array();
	}

	/**
	 * Fügt eine E-Mail Adresse hinzu
	 *
	 * @param String $mail
	 */
	public function addMail($mail)
	{
		if (Utils_Security_Orthos::checkMailSyntax($mail) == false)
		{
			throw new QException(
			    QUI::getLocale()->get('system', 'exception.lib.user.adress.mail.wrong.syntax')
			);
		}

		$list = $this->getMailList();

		if (in_array($mail, $list)) {
			return;
		}

		$list[] = $mail;

		$this->setAttribute('mail', json_encode($list));
	}

	/**
	 * Leert E-Mail Adressen
	 */
	public function clearMail()
	{
		$this->setAttribute('mail', false);
	}

	/**
	 * E-Mail Eintrag editieren
	 *
	 * @param unknown_type $index
	 * @param unknown_type $mail
	 */
	public function editMail($index, $mail)
	{
		if (Utils_Security_Orthos::checkMailSyntax($mail) == false)
		{
			throw new QException(
			    QUI::getLocale()->get('system', 'exception.lib.user.adress.mail.wrong.syntax')
			);
		}

		$index = (int)$index;
		$list  = $this->getMailList();

		$list[$index] = $mail;

		$this->setAttribute('mail', json_encode($list));
	}

	/**
	 * E-Mail Liste
	 *
	 * @return Array
	 */
	public function getMailList()
	{
		$result = json_decode($this->getAttribute('mail'), true);

		if (is_array($result)) {
			return $result;
		}

		return array();
	}

	/**
	 * Länder bekommen
	 *
	 * @return Country
	 */
	public function getCountry()
	{
		if ($this->getAttribute('country') === false)
		{
			throw new QException(
		        QUI::getLocale()->get('system', 'exception.lib.user.adress.no.country')
			);
		}

		try
		{
			return Utils_Countries_Manager::get(
				$this->getAttribute('country')
			);
		} catch (QException $e)
		{

		}

		throw new QException(
	        QUI::getLocale()->get('system', 'exception.lib.user.adress.no.country')
		);
	}

	/**
	 * Adresse speichern
	 */
	public function save()
	{
		$mail  = json_encode($this->getMailList());
		$phone = json_encode($this->getPhoneList());

		QUI::getDataBase()->update(
		    Users_Users::TableAdress(),
		    array(
    			'salutation' => Utils_Security_Orthos::clear( $this->getAttribute('salutation') ),
    			'firstname'  => Utils_Security_Orthos::clear( $this->getAttribute('firstname') ),
    			'lastname'   => Utils_Security_Orthos::clear( $this->getAttribute('lastname') ),
    			'company'    => Utils_Security_Orthos::clear( $this->getAttribute('company') ),
    			'delivery'   => Utils_Security_Orthos::clear( $this->getAttribute('delivery') ),
    			'street_no'  => Utils_Security_Orthos::clear( $this->getAttribute('street_no') ),
    			'zip'        => Utils_Security_Orthos::clear( $this->getAttribute('zip') ),
    			'city'       => Utils_Security_Orthos::clear( $this->getAttribute('city') ),
    			'country'    => Utils_Security_Orthos::clear( $this->getAttribute('country') ),
    			'mail'       => $mail,
    			'phone'      => $phone
    		), array(
    			'id' => $this->_id
    		)
    	);
	}

	/**
	 * Löscht den Eintrag
	 */
	public function delete()
	{
	    QUI::getDataBase()->exec(array(
	        'delete' => true,
	        'from'   => Users_Users::TableAdress(),
	        'where'  => array(
	            'id'  => $this->getId(),
	            'uid' => $this->_User->getId()
	        )
	    ));
	}

	/**
	 * Administrations Template
	 *
	 * @param Bool $active - Setzt den Eintrag auf checked (optional)
	 *
	 * @return String
	 */
	public function getAdminTpl($active=false)
	{
	    $Engine = QUI_Template::getEngine(true);
		$Engine->assign(array(
			'User'   => $this->_User,
			'Adress' => $this,
			'active' => $active
		));

		return $Engine->fetch(SYS_DIR .'template/user_popup_adress.html');
	}

	/**
	 * Adresse als JSON String
	 *
	 * @return String
	 */
	public function toJSON()
	{
		$attributes       = $this->getAllAttributes();
		$attributes['id'] = $this->getId();

		return json_encode($attributes);
	}
}

?>