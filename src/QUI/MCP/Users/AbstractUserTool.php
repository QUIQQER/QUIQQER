<?php

/**
 * This file contains the \QUI\MCP\Users\AbstractUserTool
 */

namespace QUI\MCP\Users;

use QUI;
use QUI\AI\MCP\Server;
use QUI\Groups\Group;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\MCP\AbstractTool;
use QUI\Users\User;

abstract class AbstractUserTool extends AbstractTool
{
    protected const USERS_MCP_PERMISSION = 'quiqqer.core.mcp.users.canUse';

    protected const UPDATE_ATTRIBUTES = [
        'username',
        'email',
        'firstname',
        'lastname',
        'usertitle',
        'company',
        'birthday',
        'lang',
        'avatar'
    ];

    protected static function checkUserPermission(string $permission): void
    {
        self::checkCorePermission();
        self::checkPermission(self::USERS_MCP_PERMISSION);
        self::checkPermission($permission);
    }

    protected static function getUser(int | string $userId): User
    {
        $User = QUI::getUsers()->get($userId);

        if (!$User instanceof User) {
            throw new QUI\Exception('The selected account is not a manageable QUIQQER user.', 400);
        }

        return $User;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseUser(UserInterface $User): array
    {
        return [
            'id' => $User->getId() ?: null,
            'uuid' => $User->getUUID(),
            'username' => $User->getUsername(),
            'displayName' => $User->getName(),
            'email' => $User->getAttribute('email'),
            'firstName' => $User->getAttribute('firstname'),
            'lastName' => $User->getAttribute('lastname'),
            'title' => $User->getAttribute('usertitle'),
            'company' => (bool)$User->getAttribute('company'),
            'birthday' => $User->getAttribute('birthday'),
            'language' => $User->getAttribute('lang'),
            'active' => $User->isActive(),
            'registrationDate' => (int)$User->getAttribute('regdate'),
            'lastVisit' => (int)$User->getAttribute('lastvisit'),
            'groups' => array_map(
                static fn(Group $Group): array => [
                    'uuid' => $Group->getUUID(),
                    'name' => $Group->getName()
                ],
                $User->getGroups()
            )
        ];
    }

    /**
     * @return array{users: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     */
    protected static function findUsers(?string $query, int $limit, int $offset): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $params = [
            'limit' => $limit,
            'start' => $offset,
            'field' => 'username',
            'order' => 'ASC'
        ];
        $Users = QUI::getUsers();

        if ($query !== null && trim($query) !== '') {
            $params['search'] = true;
            $params['searchSettings'] = [
                'userSearchString' => trim($query),
                'fields' => [
                    'uuid' => 1,
                    'email' => 1,
                    'username' => 1,
                    'firstname' => 1,
                    'lastname' => 1
                ]
            ];
        }

        $userRows = $Users->search($params);
        $result = [];

        if (is_array($userRows)) {
            foreach ($userRows as $userRow) {
                if (!isset($userRow['uuid'])) {
                    continue;
                }

                try {
                    $result[] = self::parseUser($Users->get((string)$userRow['uuid']));
                } catch (QUI\Exception) {
                }
            }
        }

        return [
            'users' => $result,
            'total' => $Users->count($params),
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * @param array<array-key, mixed> $attributes
     * @return array{attributes: array<string, mixed>, ignored: array<int, string>}
     */
    protected static function filterUserAttributes(array $attributes): array
    {
        $valid = [];
        $ignored = [];

        foreach ($attributes as $attribute => $value) {
            if (
                !is_string($attribute)
                || !in_array($attribute, self::UPDATE_ATTRIBUTES, true)
                || (!is_scalar($value) && $value !== null)
            ) {
                $ignored[] = is_string($attribute) ? $attribute : (string)$attribute;
                continue;
            }

            $valid[$attribute] = $value;
        }

        return [
            'attributes' => $valid,
            'ignored' => $ignored
        ];
    }

    protected static function saveUser(User $User): void
    {
        $User->save(Server::getRequestUser());
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getUserIdSchema(): array
    {
        return [
            'description' => 'User UUID or legacy numeric ID.',
            'oneOf' => [
                ['type' => 'string', 'minLength' => 1],
                ['type' => 'integer', 'minimum' => 1]
            ]
        ];
    }
}
