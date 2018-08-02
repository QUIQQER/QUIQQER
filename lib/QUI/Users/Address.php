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
     * Custom address data
     *
     * @var array
     */
    protected $customData = [];

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
        $result = QUI::getDataBase()->fetch([
            'from'  => Manager::tableAddress(),
            'where' => [
                'id'  => (int)$id,
                'uid' => $User->getId()
            ],
            'limit' => '1'
        ]);

        $this->User = $User;
        $this->id   = (int)$id;

        if (!isset($result[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.address.not.found',
                    [
                        'addressId' => (int)$id,
                        'userId'    => $User->getId()
                    ]
                )
            );
        }

        $data = current($result);

        unset($data['id']);
        unset($data['uid']);

        if (!empty($data['custom_data'])) {
            $this->setCustomData(json_decode($data['custom_data'], true));
        }

        $this->setAttributes($data);
    }

    /**
     * Return the ID of the address
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Add an phone number
     *
     * @param array $phone
     *
     * @example addPhone(array(
     *     'no'   => '555 29 29',
     *     'type' => 'tel'
     * ));
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
     * Edit an existing entry
     *
     * @param integer $index
     * @param array|string $phone - [no => '+0049 929292', 'type' => 'fax'] or '+0049 929292'
     */
    public function editPhone($index, $phone)
    {
        $index = (int)$index;

        if (!is_array($phone)) {
            $phone = [
                'no'   => Orthos::clear($phone),
                'type' => 'tel'
            ];
        }

        if (!isset($phone['no'])) {
            return;
        }

        if (!isset($phone['type'])) {
            return;
        }

        $list = $this->getPhoneList();

        $list[$index] = [
            'no'   => Orthos::clear($phone['no']),
            'type' => Orthos::clear($phone['type'])
        ];

        $this->setAttribute('phone', json_encode($list));
    }

    /**
     * Delete the complete phone list
     */
    public function clearPhone()
    {
        $this->setAttribute('phone', []);
    }

    /**
     * Return the complete phone list
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

        return [];
    }

    /**
     * Return the first telephone number
     *
     * @return string
     */
    public function getPhone()
    {
        $list = $this->getPhoneList();

        if (empty($list)) {
            return '';
        }

        foreach ($list as $entry) {
            if ($entry['type'] !== 'tel') {
                continue;
            }

            return $entry['no'];
        }

        return '';
    }

    /**
     * Return the first fax number
     *
     * @return string
     */
    public function getFax()
    {
        $list = $this->getPhoneList();

        if (empty($list)) {
            return '';
        }

        foreach ($list as $entry) {
            if ($entry['type'] !== 'fax') {
                continue;
            }

            return $entry['no'];
        }

        return '';
    }

    /**
     * Add an Email address
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
     * Edit an Email Entry
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
     * Return the Email list
     *
     * @return array
     */
    public function getMailList()
    {
        $result = json_decode($this->getAttribute('mail'), true);

        if (is_array($result)) {
            return $result;
        }

        return [];
    }

    /**
     * Return the address country
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
     * Saves the address
     *
     * @param null|QUI\Interfaces\Users\User $PermissionUser
     * @throws QUI\Permissions\Exception
     */
    public function save($PermissionUser = null)
    {
        if (!$this->getUser()) {
            return;
        }

        if (is_null($PermissionUser)) {
            $PermissionUser = QUI::getUserBySession();
        }

        $this->getUser()->checkEditPermission($PermissionUser);

        try {
            QUI::getEvents()->fireEvent('userAddressSaveBegin', [$this, $this->getUser()]);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        $mail  = json_encode($this->getMailList());
        $phone = json_encode($this->getPhoneList());

        QUI::getDataBase()->update(
            Manager::tableAddress(),
            [
                'salutation'  => Orthos::clear($this->getAttribute('salutation')),
                'firstname'   => Orthos::clear($this->getAttribute('firstname')),
                'lastname'    => Orthos::clear($this->getAttribute('lastname')),
                'company'     => Orthos::clear($this->getAttribute('company')),
                'delivery'    => Orthos::clear($this->getAttribute('delivery')),
                'street_no'   => Orthos::clear($this->getAttribute('street_no')),
                'zip'         => Orthos::clear($this->getAttribute('zip')),
                'city'        => Orthos::clear($this->getAttribute('city')),
                'country'     => Orthos::clear($this->getAttribute('country')),
                'mail'        => $mail,
                'phone'       => $phone,
                'custom_data' => json_encode($this->getCustomData())
            ],
            [
                'id' => $this->id
            ]
        );

        try {
            QUI::getEvents()->fireEvent('userAddressSave', [$this, $this->getUser()]);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Delete the address
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        QUI::getDataBase()->exec([
            'delete' => true,
            'from'   => Manager::tableAddress(),
            'where'  => [
                'id'  => $this->getId(),
                'uid' => $this->User->getId()
            ]
        ]);
    }

    /**
     * Return the address as HTML display
     *
     * @param array $options - options ['mail' => true, 'tel' => true]
     * @return string - HTML <address>
     */
    public function getDisplay($options = [])
    {
        try {
            $Engine = QUI::getTemplateManager()->getEngine(true);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return '';
        }

        // defaults
        if (!isset($options['tel'])) {
            $options['tel'] = true;
        }

        if (!isset($options['mail'])) {
            $options['mail'] = true;
        }


        $Engine->assign([
            'User'      => $this->User,
            'Address'   => $this,
            'Countries' => new QUI\Countries\Manager(),
            'options'   => $options
        ]);

        return $Engine->fetch(SYS_DIR.'template/users/address/display.html');
    }

    /**
     * Alias for getDisplay
     *
     * @param array $options - options
     * @return string
     */
    public function render($options = [])
    {
        return $this->getDisplay($options);
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
     * Return the main name of the address
     *
     * @return string
     */
    public function getName()
    {
        $User = $this->User;

        $salutation = $this->getAttribute('salutation');
        $firstName  = $this->getAttribute('firstname');
        $lastName   = $this->getAttribute('lastname');

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

        $result = "{$salutation} {$firstName} {$lastName}";
        $result = preg_replace('/[  ]{2,}/', ' ', $result);

        return $result;
    }

    /**
     * Set custom data entry
     *
     * @param string $key
     * @param integer|float|double|bool|string $value
     * @return void
     */
    public function setCustomDataEntry($key, $value)
    {
        if (is_object($value)) {
            return;
        }

        if (is_array($value)) {
            return;
        }

        if (!is_numeric($value) && !is_string($value) && !is_bool($value)) {
            return;
        }

        $this->customData[$key] = $value;
        $this->setAttribute('customData', $this->customData);
    }

    /**
     * Get custom data entry
     *
     * @param string $key
     * @return mixed|null - Null if no entry set
     */
    public function getCustomDataEntry($key)
    {
        if (array_key_exists($key, $this->customData)) {
            return $this->customData[$key];
        }

        return null;
    }

    /**
     * Set multiple custom data entries
     *
     * @param array $entries
     * @return void
     */
    public function setCustomData($entries)
    {
        foreach ($entries as $k => $v) {
            $this->setCustomDataEntry($k, $v);
        }
    }

    /**
     * Get all custom data entries
     *
     * @return array
     */
    public function getCustomData()
    {
        return $this->customData;
    }

    /**
     * Return the address as json
     *
     * @return string
     */
    public function toJSON()
    {
        $attributes       = $this->getAttributes();
        $attributes['id'] = $this->getId();

        return json_encode($attributes);
    }

    /**
     * Check if this address equals another address
     *
     * @param Address $Address
     * @param bool $compareCustomData (optional) - Consider custom data on comparison [default: false]
     * @return bool
     */
    public function equals(Address $Address, $compareCustomData = false)
    {
        if ($this->getId() === $Address->getId()) {
            return false;
        }

        $dataThis  = $this->getAttributes();
        $dataOther = $Address->getAttributes();

        // always ignore internal custom_data attribute
        if (array_key_exists('custom_data', $dataThis)) {
            unset($dataThis['custom_data']);
        }

        if (array_key_exists('custom_data', $dataOther)) {
            unset($dataOther['custom_data']);
        }

        // consider actual custom data
        if (!$compareCustomData) {
            if (array_key_exists('customData', $dataThis)) {
                unset($dataThis['customData']);
            }

            if (array_key_exists('customData', $dataOther)) {
                unset($dataOther['customData']);
            }
        }

        // ignore empty fields
        foreach ($dataThis as $k => $v) {
            if (empty($v)) {
                unset($dataThis[$k]);
            }
        }

        foreach ($dataOther as $k => $v) {
            if (empty($v)) {
                unset($dataOther[$k]);
            }
        }

        return $dataThis == $dataOther;
    }
}
