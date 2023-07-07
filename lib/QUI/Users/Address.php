<?php

/**
 * This file contains \QUI\Users\Address
 */

namespace QUI\Users;

use QUI;
use QUI\Interfaces\Users\User as QUIUserInterface;
use QUI\Utils\Security\Orthos as Orthos;

use function current;
use function json_decode;

/**
 * User Address
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * available options
 * - template -> set an own address template
 */
class Address extends QUI\QDOM
{
    /**
     * The user
     *
     * @var QUIUserInterface|null
     */
    protected ?QUIUserInterface $User = null;

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
    protected array $customData = [];

    /**
     * constructor
     *
     * @param QUIUserInterface $User - User
     * @param integer $id - Address id
     *
     * @throws \QUI\Users\Exception
     */
    public function __construct(QUIUserInterface $User, int $id)
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'from' => Manager::tableAddress(),
                'where' => [
                    'id' => $id,
                    'uid' => $User->getId()
                ],
                'limit' => '1'
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            QUI\System\Log::addWarning($Exception->getMessage());

            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.address.not.found',
                    [
                        'addressId' => $id,
                        'userId' => $User->getId()
                    ]
                ),
                404
            );
        }

        $this->User = $User;
        $this->id = $id;

        if (!isset($result[0])) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.user.address.not.found',
                    [
                        'addressId' => $id,
                        'userId' => $User->getId()
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
        if (!\is_array($phone)) {
            return;
        }

        if (!isset($phone['no'])) {
            return;
        }

        if (!isset($phone['type'])) {
            return;
        }

        if (
            $phone['type'] != 'tel'
            && $phone['type'] != 'fax'
            && $phone['type'] != 'mobile'
        ) {
            return;
        }

        $list = $this->getPhoneList();

        foreach ($list as $entry) {
            if ($entry['type'] == $phone['type'] && $entry['no'] == $phone['no']) {
                return;
            }
        }

        $list[] = $phone;

        $this->setAttribute('phone', \json_encode($list));
    }

    //region attributes

    /**
     * Return the complete phone list
     *
     * @return array
     */
    public function getPhoneList(): array
    {
        if (\is_array($this->getAttribute('phone'))) {
            return $this->getAttribute('phone');
        }

        $result = json_decode($this->getAttribute('phone'), true);

        if (\is_array($result)) {
            return $result;
        }

        return [];
    }

    /**
     * @param string $name
     * @return false|mixed|null
     */
    public function getAttribute($name)
    {
        if ($name === 'suffix') {
            return $this->getAddressSuffix();
        }

        return parent::getAttribute($name);
    }

    //endregion

    /**
     * Return the address suffix to the address
     *
     * @return mixed|null
     */
    public function getAddressSuffix()
    {
        if (!empty($this->attributes['suffix'])) {
            return $this->attributes['suffix'];
        }

        return $this->getCustomDataEntry('address-suffix');
    }

    /**
     * Get custom data entry
     *
     * @param string $key
     * @return mixed|null - Null if no entry set
     */
    public function getCustomDataEntry(string $key)
    {
        if (\array_key_exists($key, $this->customData)) {
            return $this->customData[$key];
        }

        return null;
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

        if (!\is_array($phone)) {
            $phone = [
                'no' => Orthos::clear($phone),
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
            'no' => Orthos::clear($phone['no']),
            'type' => Orthos::clear($phone['type'])
        ];

        $this->setAttribute('phone', \json_encode($list));
    }

    /**
     * @param $number
     */
    public function editMobile($number)
    {
        $list = $this->getPhoneList();
        $edited = false;

        foreach ($list as $key => $entry) {
            if ($entry['type'] !== 'mobile') {
                continue;
            }

            $list[$key]['no'] = $number;
            $edited = true;
        }

        if ($edited === false) {
            $list[] = [
                'type' => 'mobile',
                'no' => $number
            ];
        }

        $this->setAttribute('phone', \json_encode($list));
    }

    /**
     * @param $number
     */
    public function editFax($number)
    {
        $list = $this->getPhoneList();
        $edited = false;

        foreach ($list as $key => $entry) {
            if ($entry['type'] !== 'fax') {
                continue;
            }

            $list[$key]['no'] = $number;
            $edited = true;
        }

        if ($edited === false) {
            $list[] = [
                'type' => 'fax',
                'no' => $number
            ];
        }

        $this->setAttribute('phone', \json_encode($list));
    }

    /**
     * Delete the complete phone list
     */
    public function clearPhone()
    {
        $this->setAttribute('phone', []);
    }

    /**
     * Return the first telephone number
     *
     * @return string
     */
    public function getPhone(): string
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
     * Return the first telephone number
     *
     * @return string
     */
    public function getMobile(): string
    {
        $list = $this->getPhoneList();

        if (empty($list)) {
            return '';
        }

        foreach ($list as $entry) {
            if ($entry['type'] !== 'mobile') {
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
    public function getFax(): string
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
                    'quiqqer/quiqqer',
                    'exception.lib.user.address.mail.wrong.syntax'
                )
            );
        }

        $list = $this->getMailList();

        if (\in_array($mail, $list)) {
            return;
        }

        $list[] = $mail;

        $this->setAttribute('mail', \json_encode($list));
    }

    /**
     * Return the Email list
     *
     * @return array
     */
    public function getMailList(): array
    {
        $result = json_decode($this->getAttribute('mail'), true);

        if (\is_array($result)) {
            return $result;
        }

        return [];
    }

    /**
     * Clear mail addresses
     */
    public function clearMail()
    {
        $this->setAttribute('mail', json_encode([]));
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
                    'quiqqer/quiqqer',
                    'exception.lib.user.address.mail.wrong.syntax'
                )
            );
        }

        $index = (int)$index;
        $list = $this->getMailList();

        $list[$index] = $mail;

        $this->setAttribute('mail', \json_encode($list));
    }

    /**
     * Return the address country
     *
     * @return QUI\Countries\Country
     * @throws QUI\Users\Exception
     */
    public function getCountry(): QUI\Countries\Country
    {
        if ($this->getAttribute('country') === false) {
            throw new QUI\Users\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
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
                'quiqqer/quiqqer',
                'exception.lib.user.address.no.country'
            )
        );
    }

    /**
     * Saves the address
     *
     * @param null|QUIUserInterface $PermissionUser
     * @throws QUI\Permissions\Exception
     */
    public function save($PermissionUser = null)
    {
        if (!$this->getUser()) {
            return;
        }

        if (\is_null($PermissionUser)) {
            $PermissionUser = QUI::getUserBySession();
        }


        $User = $this->getUser();
        $User->checkEditPermission($PermissionUser);

        try {
            QUI::getEvents()->fireEvent('userAddressSaveBegin', [$this, $this->getUser()]);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        $mail = \json_encode($this->getMailList());
        $phone = \json_encode($this->getPhoneList());

        $cleanupAttributes = function ($str) {
            $str = Orthos::removeHTML($str);
            $str = Orthos::clearFormRequest($str);
            $str = Orthos::clearPath($str);

            return $str;
        };

        try {
            QUI::getDataBase()->update(
                Manager::tableAddress(),
                [
                    'salutation' => $cleanupAttributes($this->getAttribute('salutation')),
                    'firstname' => $cleanupAttributes($this->getAttribute('firstname')),
                    'lastname' => $cleanupAttributes($this->getAttribute('lastname')),
                    'company' => $cleanupAttributes($this->getAttribute('company')),
                    'delivery' => $cleanupAttributes($this->getAttribute('delivery')),
                    'street_no' => $cleanupAttributes($this->getAttribute('street_no')),
                    'zip' => $cleanupAttributes($this->getAttribute('zip')),
                    'city' => $cleanupAttributes($this->getAttribute('city')),
                    'country' => $cleanupAttributes($this->getAttribute('country')),
                    'mail' => $mail,
                    'phone' => $phone,
                    'custom_data' => \json_encode($this->getCustomData())
                ],
                [
                    'id' => $this->id
                ]
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            QUI\System\Log::writeDebugException($Exception);
        }

        try {
            // update user firstname lastname, if this address is the default address
            if ($User->getStandardAddress()->getId() === $this->getId()) {
                $mailList = $this->getMailList();

                if (\count($mailList)) {
                    $email = \reset($mailList);
                    $User->setAttribute('email', $cleanupAttributes($email));
                }

                $User->setAttribute('firstname', $cleanupAttributes($this->getAttribute('firstname')));
                $User->setAttribute('lastname', $cleanupAttributes($this->getAttribute('lastname')));
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        try {
            QUI::getEvents()->fireEvent('userAddressSave', [$this, $this->getUser()]);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * @return QUIUserInterface
     */
    public function getUser(): ?QUIUserInterface
    {
        return $this->User;
    }

    /**
     * Get all custom data entries
     *
     * @return array
     */
    public function getCustomData(): array
    {
        return $this->customData;
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
     * Delete the address
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        QUI::getDataBase()->exec([
            'delete' => true,
            'from' => Manager::tableAddress(),
            'where' => [
                'id' => $this->getId(),
                'uid' => $this->User->getId()
            ]
        ]);
    }

    /**
     * Alias for getDisplay
     *
     * @param array $options - options
     * @return string
     */
    public function render($options = []): string
    {
        return $this->getDisplay($options);
    }

    /**
     * Return the address as HTML display
     *
     * @param array $options - options ['mail' => true, 'tel' => true]
     * @return string - HTML <address>
     */
    public function getDisplay($options = []): string
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
            'User' => $this->User,
            'Address' => $this,
            'Countries' => new QUI\Countries\Manager(),
            'options' => $options
        ]);

        $template = $this->getAttribute('template');

        if (\file_exists($template)) {
            return $Engine->fetch($template);
        }

        return $Engine->fetch(SYS_DIR . 'template/users/address/display.html');
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        $salutation = $this->getAttribute('salutation');
        $firstName = $this->getAttribute('firstname');
        $lastName = $this->getAttribute('lastname');

        $street_no = $this->getAttribute('street_no');
        $zip = $this->getAttribute('zip');
        $city = $this->getAttribute('city');
        $country = $this->getAttribute('country');

        // build parts
        $part = [0 => [], 1 => [], 2 => []];

        if (!empty($salutation)) {
            $part[0][] = $salutation;
        }

        if (!empty($firstName)) {
            $part[0][] = $firstName;
        }

        if (!empty($lastName)) {
            $part[0][] = $lastName;
        }

        if (!empty($street_no)) {
            $part[1][] = $street_no;
        }

        if (!empty($zip)) {
            $part[2][] = $zip;
        }

        if (!empty($city)) {
            $part[2][] = $city;
        }

        if (!empty($country)) {
            $part[2][] = $country;
        }

        // build parts
        $part[0] = \trim(\implode(' ', $part[0]));
        $part[1] = \trim(\implode(' ', $part[1]));
        $part[2] = \trim(\implode(' ', $part[2]));

        if (empty($part[2])) {
            unset($part[2]);
        }

        if (empty($part[1])) {
            unset($part[1]);
        }

        if (empty($part[0])) {
            unset($part[0]);
        }

        $address = \implode('; ', $part);
        $company = $this->getAttribute('company');

        if (!empty($company)) {
            return $company . '; ' . $address;
        }

        return $address;
    }

    /**
     * Return the main name of the address
     *
     * @return string
     */
    public function getName(): string
    {
        $User = $this->User;

        $salutation = $this->getAttribute('salutation');
        $firstName = $this->getAttribute('firstname');
        $lastName = $this->getAttribute('lastname');

        if (empty($firstName) && $User) {
            $firstName = $User->getAttribute('firstname');
        }

        if (!$firstName) {
            $firstName = '';
        }

        if (empty($lastName) && $User) {
            $lastName = $User->getAttribute('lastname');
        }

        if (!$lastName) {
            $lastName = '';
        }

        if (!$salutation) {
            $salutation = '';
        }

        $result = "{$salutation} {$firstName} {$lastName}";
        $result = \preg_replace('/[  ]{2,}/', ' ', $result);

        return \trim($result);
    }

    /**
     * Return the address as json
     *
     * @return string
     */
    public function toJSON(): string
    {
        $attributes = $this->getAttributes();
        $attributes['id'] = $this->getId();

        return \json_encode($attributes);
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['suffix'] = $this->getAddressSuffix();

        return $attributes;
    }

    /**
     * Check if this address equals another address
     *
     * @param Address $Address
     * @param bool $compareCustomData (optional) - Consider custom data on comparison [default: false]
     * @return bool
     */
    public function equals(Address $Address, $compareCustomData = false): bool
    {
        if ($this->getId() === $Address->getId()) {
            return true;
        }

        $dataThis = $this->getAttributes();
        $dataOther = $Address->getAttributes();

        // always ignore internal custom_data attribute
        if (\array_key_exists('custom_data', $dataThis)) {
            unset($dataThis['custom_data']);
        }

        if (\array_key_exists('custom_data', $dataOther)) {
            unset($dataOther['custom_data']);
        }

        // consider actual custom data
        if (!$compareCustomData) {
            if (\array_key_exists('customData', $dataThis)) {
                unset($dataThis['customData']);
            }

            if (\array_key_exists('customData', $dataOther)) {
                unset($dataOther['customData']);
            }
        }

        // ignore id
        unset($dataThis['id']);
        unset($dataOther['id']);

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

    //region address suffix - Address suffix

    /**
     * Add a address suffix to the address
     *
     * @param string $suffix
     */
    public function setAddressSuffix(string $suffix)
    {
        $this->setCustomDataEntry('address-suffix', $suffix);
    }

    /**
     * Set custom data entry
     *
     * @param string $key
     * @param integer|float|bool|string $value
     * @return void
     */
    public function setCustomDataEntry(string $key, $value)
    {
        if (\is_object($value)) {
            return;
        }

        if (\is_array($value)) {
            return;
        }

        if (!\is_numeric($value) && !\is_string($value) && !\is_bool($value)) {
            return;
        }

        $this->customData[$key] = $value;
        $this->setAttribute('customData', $this->customData);
    }

    //endregion
}
