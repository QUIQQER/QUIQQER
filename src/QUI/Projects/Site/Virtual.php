<?php

/**
 * File contains QUI\Projects\Site\Virtual
 */

namespace QUI\Projects\Site;

use QUI;
use QUI\Exception;
use QUI\Interfaces\Projects\Site;
use QUI\Projects\Project;

use function json_decode;
use function json_encode;

/**
 * Virtual site object
 * not a real site in the database
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Virtual extends QUI\QDOM implements QUI\Interfaces\Projects\Site
{
    protected ?QUI\Projects\Site $Parent = null;

    protected ?QUI\Projects\Project $Project = null;

    /**
     * @throws Exception
     */
    public function __construct(
        array $attributes = [],
        QUI\Projects\Project $Project = null,
        QUI\Projects\Site $Parent = null
    ) {
        $this->Project = $Project;
        $this->Parent = $Parent;

        $this->setAttributes($attributes);

        $needles = ['id', 'title', 'name', 'url'];

        foreach ($needles as $needle) {
            if (!$this->getAttribute($needle)) {
                throw new QUI\Exception('Misisng attribute ' . $needle);
            }
        }
    }

    public function getProject(): QUI\Projects\Project
    {
        return $this->Project;
    }

    /**
     * Lädt die Plugins der Seite
     *
     * @param boolean|string $plugin - Plugin welches geladen werden soll, optional, ansonsten werden alle geladen
     *
     * @return Virtual
     */
    public function load(bool|string $plugin = false): QUI\Interfaces\Projects\Site
    {
        return $this;
    }

    /**
     * Serialisierungsdaten
     */
    public function encode(): string
    {
        return json_encode($this->getAttributes());
    }

    /**
     * Setzt JSON Parameter
     *
     * @param string $params - JSON encoded string
     */
    public function decode(string $params): void
    {
        $this->setAttributes(
            json_decode($params, true)
        );
    }

    /**
     * Hohlt frisch die Daten aus der DB
     */
    public function refresh()
    {
    }

    /**
     * Prüft ob es eine Verknüpfung ist
     */
    public function isLinked(): bool|int
    {
        return false;
    }

    /**
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     */
    public function existLang(string $lang, bool $check_only_active = true): bool
    {
        return false;
    }

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     */
    public function getLangIds(): array
    {
        return [];
    }

    /**
     * Gibt alle Kinder zurück
     *
     * @param array $params - Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     * @param boolean $load - Legt fest ob die Kinder die Plugins laden sollen
     *
     * @return int|array ;
     */
    public function getChildren(array $params = [], bool $load = false): int|array
    {
        return [];
    }

    /**
     * Liefert die nächstfolgende Seite
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function nextSibling(): QUI\Interfaces\Projects\Site
    {
        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/core', 'exception.site.no.next.sibling')
        );
    }

    /**
     * Die nächsten x Kinder
     */
    public function nextSiblings(int $no): array
    {
        return [];
    }

    /**
     * Liefert die vorhergehenden Seite
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function previousSibling(): QUI\Interfaces\Projects\Site
    {
        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/core', 'exception.site.no.previous.sibling')
        );
    }

    /**
     * Die x vorhergehenden Geschwister
     */
    public function previousSiblings(int $no): array
    {
        return [];
    }

    /**
     * Gibt die Kinder zurück achtet aber auf "Nicht in Navigation anzeigen" und Rechte
     */
    public function getNavigation(array $params = []): int|array
    {
        return [];
    }

    /**
     * Gibt ein Kind zurück welches den Namen hat
     *
     * @throws QUI\Exception
     */
    public function getChildIdByName(string $name): int
    {
        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/core', 'exception.site.child.by.name.not.found', [
                'name' => $name
            ]),
            705
        );
    }

    /**
     * Return a children by id
     *
     * @throws QUI\Exception
     */
    public function getChild(int $id): QUI\Interfaces\Projects\Site
    {
        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/core', 'exception.site.child.not.found'),
            705
        );
    }

    /**
     * Gibt die ID's der Kinder zurück
     * Wenn nur die ID's verwendet werden sollte dies vor getChildren verwendet werden
     *
     * @param array $params Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     *
     * @return array
     */
    public function getChildrenIds(array $params = []): array
    {
        return [];
    }

    /**
     * Return ALL children ids under the site
     *
     * @param array $params - db parameter
     *
     * @return array
     */
    public function getChildrenIdsRecursive(array $params = []): array
    {
        return [];
    }

    /**
     * Gibt zurück ob Site Kinder besitzt
     *
     * @param boolean $navhide - if navhide == false, navhide must be 0
     *
     * @return integer - Anzahl der Kinder
     */
    public function hasChildren(bool $navhide = false): int
    {
        return 0;
    }

    /**
     * Setzt das delete Flag
     *
     * @todo move to Site/Edit
     */
    public function delete(): bool
    {
        return false;
    }

    /**
     * Gibt die URL der Seite zurück
     */
    public function getUrl(array $params = [], array $getParams = []): string
    {
        return $this->getAttribute('url');
    }

    public function getUrlRewritten(array $params = []): string
    {
        return $this->getAttribute('url');
    }

    /**
     * Return the Parent id from the site object
     *
     * @throws Exception
     */
    public function getParentId(): int
    {
        if (!$this->Parent) {
            return 1;
        }

        return $this->Parent->getId();
    }

    /**
     * Return the ID of the site,
     * or the ID of the sibling (linked) site of another language
     *
     * @param boolean|string $lang - optional, if it is set, then the language of the wanted to be linked sibling site
     *
     * @return integer
     */
    public function getId(bool|string $lang = false): int
    {
        return $this->getAttribute('id');
    }

    /**
     * Gibt alle direkten Eltern Ids zurück
     *
     * Site
     * ->Parent
     * ->Parent
     * ->Parent
     *
     * @throws Exception
     */
    public function getParentIds(): array
    {
        $parents = $this->getParent()->getParentIds();
        $parents[] = $this->getParent()->getId();

        return $parents;
    }

    /**
     * Gibt das Parent Objekt zurück
     *
     * @throws Exception
     */
    public function getParent(): QUI\Interfaces\Projects\Site
    {
        if (!$this->Parent) {
            return $this->Project->firstChild();
        }

        return $this->Parent;
    }

    /**
     * Gibt das erste Kind der Seite zurück
     */
    public function firstChild(array $params = []): Site|bool
    {
        return false;
    }

    /**
     * Return the Parent ID List
     */
    public function getParentIdTree(): array
    {
        return [];
    }

    /**
     * Gibt alle rekursive Parents als Objekte zurück
     * Site->Parent->ParentParent->ParentParentParent
     *
     * @throws Exception
     */
    public function getParents(): array
    {
        $parents = $this->getParent()->getParents();
        $parents[] = $this->getParent();

        return $parents;
    }

    /**
     * Stellt die Seite wieder her
     *
     * ??? wieso hier? und nicht im trash? O.o
     */
    public function restore()
    {
    }

    /**
     * Zerstört die Seite
     * Die Seite wird komplett aus der DB gelöscht und auch alle Beziehungen
     * Funktioniert nur wenn die Seite gelöscht ist
     */
    public function destroy()
    {
    }

    /**
     * Canonical URL - Um doppelte Inhalt zu vermeiden
     */
    public function getCanonical(): string
    {
        return $this->getAttribute('url');
    }

    /**
     * Löscht den Seitencache
     */
    public function deleteCache()
    {
    }

    /**
     * Löscht den Seitencache
     */
    public function createCache()
    {
    }

    /**
     * Shortcut for QUI\Permissions\Permission::hasSitePermission
     */
    public function hasPermission(string $permission, $User = false): bool|int
    {
        return true;
    }

    /**
     * Shortcut for QUI\Permissions\Permission::checkSitePermission
     */
    public function checkPermission(string $permission, $User = false)
    {
    }
}
