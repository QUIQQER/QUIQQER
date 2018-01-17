<?php

/**
 * This file contains \QUI\Users\Address
 */

namespace QUI\Users;

use QUI;
use QUI\Utils\Security\Orthos as Orthos;

/**
 * User Address
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Address extends QUI\QDOM
{
    /**
     * The user
     *
     * @var QUI\Users\User
     */
    protected $User = null;

    /**
     * Address-ID
     *
     * @var integer
     */
    protected $id = false;

    /**
     * constructor
     *
     * @param QUI\Users\User $User - User
     * @param integer $id - Address id
     *
     * @throws \QUI\Users\Exception
     */
    public function __construct(User $User, $id)
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => Manager::tableAddress(),
            'where' => array(
                'id'  => (int)$id,
                'uid' => $User->getId()
            ),
            'limit' => '1'
        ));

        $this->User = $User;
        $this->id   = (int)$id;

        if (!isset($result[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.address.not.found',
                    array(
                        'addressId' => (int)$id,
                        'userId'    => $User->getId()
                    )
                )
            );
        }

        unset($result[0]['id']);
        unset($result[0]['uid']);

        $this->setAttributes($result[0]);
    }

    /**
     * ID der Addresse
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Telefon Nummer hinzufügen
     *
     * @param array $phone
     *
     * @example addPhone(array(
     *        'no'   => '555 29 29',
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

        if ($phone['type'] != 'tel'
            && $phone['type'] != 'fax'
            && $phone['type'] != 'mobile'
        ) {
            return;
        }

        $list = $this->getPhoneList();

        foreach ($list as $entry) {
            if ($entry['type'] == $phone['type']
                && $entry['no'] == $phone['no']
            ) {
                return;
            }
        }

        $list[] = $phone;

        $this->setAttribute('phone', json_encode($list));
    }

    /**
     * Editier ein bestehenden Eintrag
     *
     * @param integer $index
     * @param string $phone
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
     * @return array
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
     * Add a EMail address
     *
     * @param string $mail - new mail address
     *
     * @throws QUI\Users\Exception
     */
    public function addMail($mail)
    {
        if (Orthos::checkMailSyntax($mail) == false) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'system',
                    'exception.lib.user.address.mail.wrong.syntax'
                )
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
     * Clear mail addresses
     */
    public function clearMail()
    {
        $this->setAttribute('mail', false);
    }

    /**
     * E-Mail Eintrag editieren
     *
     * @param integer $index - index of the mail
     * @param string $mail - E-Mail (eq: my@mail.com)
     *
     * @throws QUI\Users\Exception
     */
    public function editMail($index, $mail)
    {
        if (Orthos::checkMailSyntax($mail) == false) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'system',
                    'exception.lib.user.address.mail.wrong.syntax'
                )
            );
        }

        $index = (int)$index;
        $list  = $this->getMailList();

        $list [$index] = $mail;

        $this->setAttribute('mail', json_encode($list));
    }

    /**
     * E-Mail Liste
     *
     * @return array
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
     * @return QUI\Countries\Country
     * @throws QUI\Users\Exception
     */
    public function getCountry()
    {
        if ($this->getAttribute('country') === false) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'system',
                    'exception.lib.user.address.no.country'
                )
            );
        }

        try {
            return QUI\Countries\Manager::get(
                $this->getAttribute('country')
            );
        } catch (QUI\Exception $Exception) {
        }

        throw new QUI\Users\Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.address.no.country'
            )
        );
    }

    /**
     * Addresse speichern
     */
    public function save()
    {
        $mail  = json_encode($this->getMailList());
        $phone = json_encode($this->getPhoneList());

        QUI::getDataBase()->update(
            Manager::tableAddress(),
            array(
                'salutation' => Orthos::clear($this->getAttribute('salutation')),
                'firstname'  => Orthos::clear($this->getAttribute('firstname')),
                'lastname'   => Orthos::clear($this->getAttribute('lastname')),
                'company'    => Orthos::clear($this->getAttribute('company')),
                'delivery'   => Orthos::clear($this->getAttribute('delivery')),
                'street_no'  => Orthos::clear($this->getAttribute('street_no')),
                'zip'        => Orthos::clear($this->getAttribute('zip')),
                'city'       => Orthos::clear($this->getAttribute('city')),
                'country'    => Orthos::clear($this->getAttribute('country')),
                'mail'       => $mail,
                'phone'      => $phone
            ),
            array(
                'id' => $this->id
            )
        );
    }

    /**
     * Delete the address
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        QUI::getDataBase()->exec(array(
            'delete' => true,
            'from'   => Manager::tableAddress(),
            'where'  => array(
                'id'  => $this->getId(),
                'uid' => $this->User->getId()
            )
        ));
    }

    /**
     * Return the address as HTML display
     *
     * @return string - HTML <address>
     * @throws
     */
    public function getDisplay()
    {
        $Engine = QUI::getTemplateManager()->getEngine(true);

        $Engine->assign(array(
            'User'      => $this->User,
            'Address'   => $this,
            'Countries' => new QUI\Countries\Manager()
        ));

        return $Engine->fetch(SYS_DIR.'template/users/address/display.html');
    }

    /**
     * Alias for getDisplay
     *
     * @return string
     */
    public function render()
    {
        return $this->getDisplay();
    }

    /**
     * @return string
     */
    public function getText()
    {
        $User = $this->User;

        $salutation = $this->getAttribute('salutation');
        $firstName  = $this->getAttribute('firstname');
        $lastName   = $this->getAttribute('lastname');

        $street_no = $this->getAttribute('street_no');
        $zip       = $this->getAttribute('zip');
        $city      = $this->getAttribute('city');
        $country   = $this->getAttribute('country');

        if (empty($firstName)) {
            $firstName = $User->getAttribute('firstname');
        }

        if (!$firstName) {
            $firstName = '';
        }

        if (empty($lastName)) {
            $lastName = $User->getAttribute('lastname');
        }

        if (!$lastName) {
            $lastName = '';
        }


        if (!$salutation) {
            $salutation = '';
        }

        if (!$street_no) {
            $street_no = '';
        }

        if (!$zip) {
            $zip = '';
        }

        if (!$city) {
            $city = '';
        }

        if (!$country) {
            $country = '';
        }

        $result = "{$salutation} {$firstName} {$lastName}; {$street_no}; {$zip} {$city} {$country}";
        $result = preg_replace('/[  ]{2,}/', ' ', $result);

        return $result;
    }

    /**
     * Addresse als JSON string
     *
     * @return string
     */
    public function toJSON()
    {
        $attributes       = $this->getAttributes();
        $attributes['id'] = $this->getId();

        return json_encode($attributes);
    }
}
