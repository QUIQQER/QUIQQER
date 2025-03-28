<?php

/**
 * This file contains \QUI\Interfaces\Projects\Media\File
 */

namespace QUI\Interfaces\Projects\Media;

use QUI;
use QUI\Exception;
use QUI\Projects\Media;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Item;
use QUI\Projects\Project;

/**
 * The media file interface
 *
 * it shows the main methods of a media file (file, image, folder)
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
interface File
{
    // region start QDOM

    public function getAttribute(string $name): mixed;

    public function setAttribute(string $name, mixed $value): void;

    public function setAttributes(?array $attributes): void;

    public function getAttributes(): array;

    public function getType(): string;

    // endregion

    public function getId(): int;

    public function getParent(): Item;

    public function getParentId(): int | bool;

    public function getParentIds(): array;

    public function getPath(): string;

    /**
     * Return the URL of the File, relative to the host
     */
    public function getUrl(bool $rewritten = false): string;

    public function getFullPath(): string;

    /**
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     *
     * @throws Exception
     */
    public function activate(null | QUI\Interfaces\Users\User $PermissionUser = null);

    public function isActive(): bool;

    public function isDeleted(): bool;

    public function isHidden(): bool;

    /**
     * Deactivate the file
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws Exception
     */
    public function deactivate(null | QUI\Interfaces\Users\User $PermissionUser = null);

    /**
     * Delete the file, file is in trash
     *
     * @param QUI\Interfaces\Users\User|null $PermissionUser
     * @throws Exception
     */
    public function delete(null | QUI\Interfaces\Users\User $PermissionUser = null): void;

    public function destroy(null | QUI\Interfaces\Users\User $PermissionUser = null): void;

    /**
     * Save the file with all its attributes to the Database
     *
     * @throws Exception
     */
    public function save(): void;

    /**
     * @throws Exception
     */
    public function rename(
        string $newName,
        null | QUI\Interfaces\Users\User $PermissionUser = null
    );

    /**
     * create the file cache
     *
     * @throws Exception
     */
    public function createCache(): bool | string;

    /**
     * delete the file cache
     *
     * @throws Exception
     */
    public function deleteCache(): void;

    public function moveTo(Folder $Folder): void;

    public function copyTo(Folder $Folder): QUI\Interfaces\Projects\Media\File;

    public function getMedia(): Media;

    public function getProject(): Project;

    public function checkPermission(
        string $permission,
        null | QUI\Interfaces\Users\User $User = null
    ): void;
}
