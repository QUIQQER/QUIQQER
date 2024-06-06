<?php

/**
 * This file contains \QUI\Users\Nobody
 */

namespace QUI\Users;

use QUI;
use QUI\Countries\Country;
use QUI\ERP\Currency\Handler as Currencies;
use QUI\Exception;
use QUI\Groups\Group;
use QUI\Interfaces\Users\User;
use QUI\Interfaces\Users\User as QUIUserInterface;
use QUI\Locale;
use QUI\Projects\Media\Image;

/**
 * The standard user
 * Nobody has no rights
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Nobody extends QUI\QDOM implements User
{
    protected ?Locale $Locale = null;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->refresh();
    }

    /**
     * refresh the nobody object
     * reads the data from the session
     */
    public function refresh(): void
    {
        $attributes = QUI::getSession()->get('attributes');

        if (!empty($attributes)) {
            $this->setAttributes($attributes);
        }
    }

    public function isSU(): bool
    {
        return false;
    }

    public function isInGroup(int|string $groupId): bool
    {
        return in_array($groupId, $this->getGroups(false));
    }

    /**
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     * @return array
     */
    public function getGroups(bool $array = true): array
    {
        $Guest = new QUI\Groups\Guest();
        $Everyone = new QUI\Groups\Everyone();

        if ($array === true) {
            return [$Guest, $Everyone];
        }

        return [$Guest->getUUID(), $Everyone->getUUID()];
    }

    /**
     * @deprecated
     */
    public function getId(): false|int
    {
        return false;
    }

    /**
     * @deprecated
     */
    public function isAdmin(): bool
    {
        return $this->canUseBackend();
    }

    public function canUseBackend(): bool
    {
        return false;
    }

    /**
     * Nobody is no company
     *
     * @return false
     */
    public function isCompany(): bool
    {
        return false;
    }

    public function isDeleted(): bool
    {
        return true;
    }

    public function isActive(): bool
    {
        return false;
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function logout(): void
    {
    }

    /**
     * @param string $code - activation code [optional]
     * @param User|null $PermissionUser
     * @return bool
     */
    public function activate(string $code = '', User $PermissionUser = null): bool
    {
        return false;
    }

    public function deactivate(?User $PermissionUser = null): bool
    {
        return false;
    }

    public function disable(?User $PermissionUser = null): bool
    {
        return false;
    }

    public function save(?User $PermissionUser = null): void
    {
        QUI::getSession()->set('attributes', $this->getAttributes());
    }

    public function delete(?User $PermissionUser = null): bool
    {
        return false;
    }

    /**
     * This method is useless for nobody
     *
     * @throws Exception
     */
    public function addAddress(array $params = [], User $ParentUser = null): ?Address
    {
        throw new Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.add.address'
            )
        );
    }

    /**
     * Return the locale object depending on the user
     */
    public function getLocale(): Locale
    {
        if ($this->Locale) {
            return $this->Locale;
        }

        $this->Locale = new Locale();

        if (QUI::getSession()->get('CURRENT_LANG')) {
            $this->Locale->setCurrent(QUI::getSession()->get('CURRENT_LANG'));
        } else {
            $this->Locale->setCurrent(QUI::getLocale()->getCurrent());
        }

        return $this->Locale;
    }

    /**
     * Nobody can't be added to the group
     *
     * @throws Exception
     */
    public function addToGroup(int $groupId): never
    {
        throw new Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.add.to.group'
            )
        );
    }

    /**
     * Nobody can't be added to the group
     *
     * @throws Exception
     */
    public function removeGroup(Group|int $Group): never
    {
        throw new Exception(
            QUI::getLocale()->get(
                'system',
                'exception.lib.user.nobody.remove.group'
            )
        );
    }

    public function getExtra(string $field): bool
    {
        return false;
    }

    public function getType(): string
    {
        return $this::class;
    }

    /**
     * @deprecated
     */
    public function getUniqueId(): int|string
    {
        return $this->getUUID();
    }

    public function getUUID(): string|int
    {
        return '';
    }

    public function getName(): string
    {
        return QUI::getLocale()->get('quiqqer/core', 'nobody.name');
    }

    public function getUsername(): string
    {
        return QUI::getLocale()->get('quiqqer/core', 'nobody.username');
    }

    public function getLang(): string
    {
        return self::getLocale()->getCurrent();
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have an address
     */
    public function getAddressList(): array
    {
        return [];
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have an address
     *
     * @throws Exception
     */
    public function getAddress(int|string $id): Address
    {
        throw new Exception(
            QUI::getLocale()->get(
                'quiqqer/core',
                'exception.lib.user.nobody.get.address'
            )
        );
    }

    /**
     */
    public function getCurrency()
    {
        if (QUI::getSession()->get('currency')) {
            $currency = QUI::getSession()->get('currency');

            if (Currencies::existCurrency($currency)) {
                return $currency;
            }
        }

        $Country = $this->getCountry();

        if ($Country) {
            $currency = $Country->getCurrencyCode();

            if (Currencies::existCurrency($currency)) {
                return $currency;
            }
        }

        return Currencies::getDefaultCurrency();
    }

    /**
     * Return the Country of nobody
     * use the GEOIP_COUNTRY_CODE from apache, if available
     */
    public function getCountry(): ?Country
    {
        if (QUI::getSession()->get('country')) {
            try {
                return QUI\Countries\Manager::get(
                    QUI::getSession()->get('country')
                );
            } catch (QUI\Exception) {
            }
        }

        // apache
        if (isset($_SERVER["GEOIP_COUNTRY_CODE"])) {
            try {
                QUI::getSession()->set('country', $_SERVER["GEOIP_COUNTRY_CODE"]);

                return QUI\Countries\Manager::get($_SERVER["GEOIP_COUNTRY_CODE"]);
            } catch (QUI\Exception) {
                QUI::getSession()->del('country');
            }
        }

        if (QUI::conf('globals', 'country')) {
            try {
                QUI::getSession()->set('country', QUI::conf('globals', 'country'));

                return QUI\Countries\Manager::get(QUI::conf('globals', 'country'));
            } catch (QUI\Exception) {
                QUI::getSession()->del('country');
            }
        }

        // old
        if (QUI::conf('globals', 'defaultCountry')) {
            try {
                QUI::getSession()->set('country', QUI::conf('globals', 'defaultCountry'));

                return QUI\Countries\Manager::get(
                    QUI::conf('globals', 'defaultCountry')
                );
            } catch (QUI\Exception) {
            }
        }

        return null;
    }

    /**
     * This method is useless for nobody
     * \QUI\Users\Nobody cannot have an address
     *
     * @return null|Address
     * @ignore
     */
    public function getStandardAddress(): null|Address
    {
        return null;
    }

    public function getStatus(): int
    {
        return 1;
    }

    public function setGroups(array|string $groups): bool
    {
        return false;
    }

    public function getAvatar(): Image|null
    {
        $Project = QUI::getProjectManager()->getStandard();
        $Media = $Project->getMedia();

        return $Media->getPlaceholderImage();
    }

    /**
     * Exists the permission in the user permissions
     */
    public function hasPermission(string $permission): bool|string
    {
        $list = QUI::getPermissionManager()->getUserPermissionData($this);

        return $list[$permission] ?? false;
    }

    /**
     * @param string $right
     * @param bool|string|callable $ruleset - optional, you can specify a ruleset, a rules = array with rights
     * @return bool|int|string
     *
     * @throws Exception
     */
    public function getPermission(string $right, callable|bool|string $ruleset = false): bool|int|string
    {
        return QUI::getPermissionManager()->getUserPermission($this, $right, $ruleset);
    }

    /**
     * not usable, nobody is always a company
     */
    public function setCompanyStatus(bool $status = false): void
    {
    }

    /**
     * @param string $new - new password
     * @param \QUI\Users\User|boolean $PermissionUser
     *
     * @return bool
     */
    public function setPassword(string $new, $PermissionUser = false): bool
    {
        return false;
    }

    public function changePassword(string $newPassword, string $oldPassword, QUIUserInterface $ParentUser = null): void
    {
    }

    /**
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     *
     * @return false
     */
    public function checkPassword(string $pass, bool $encrypted = false): bool
    {
        return false;
    }

    //region authenticator
    public function hasAuthenticator(string $authenticator): bool
    {
        return false;
    }

    public function getAuthenticator(string $authenticator): AuthenticatorInterface
    {
        throw new QUI\Users\Exception(
            ['quiqqer/core', 'exception.authenticator.not.found'],
            404
        );
    }

    public function getAuthenticators(): array
    {
        return [];
    }

    public function enableAuthenticator(string $authenticator, QUIUserInterface $ParentUser = null): void
    {
        throw new QUI\Users\Exception(
            ['quiqqer/core', 'exception.authenticator.not.found'],
            404
        );
    }

    public function disableAuthenticator(string $authenticator, QUIUserInterface $ParentUser = null): void
    {
        throw new QUI\Users\Exception(
            ['quiqqer/core', 'exception.authenticator.not.found'],
            404
        );
    }

    //endregion authenticator
}
