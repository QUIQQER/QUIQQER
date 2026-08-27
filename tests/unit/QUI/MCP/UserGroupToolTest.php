<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\TestCase;
use QUI\MCP\Groups\AbstractGroupTool;
use QUI\MCP\Users\AbstractUserTool;
use ReflectionMethod;

class UserGroupToolTest extends TestCase
{
    public function testUserAttributeFilterDoesNotExposeSecurityFields(): void
    {
        $Method = new ReflectionMethod(AbstractUserTool::class, 'filterUserAttributes');
        $result = $Method->invoke(null, [
            'email' => 'user@example.test',
            'firstname' => 'Ada',
            'password' => 'secret',
            'authenticator' => ['unsafe'],
            'groups' => ['group-id']
        ]);

        self::assertSame(
            ['email' => 'user@example.test', 'firstname' => 'Ada'],
            $result['attributes']
        );
        self::assertSame(['password', 'authenticator', 'groups'], $result['ignored']);
    }

    public function testGroupAttributeFilterKeepsRightsAndStateSeparate(): void
    {
        $Method = new ReflectionMethod(AbstractGroupTool::class, 'filterGroupAttributes');
        $result = $Method->invoke(null, [
            'name' => 'Editors',
            'avatar' => 'image-url',
            'rights' => ['quiqqer.admin' => true],
            'active' => true,
            'parent' => 'parent-id'
        ]);

        self::assertSame(
            ['name' => 'Editors', 'avatar' => 'image-url'],
            $result['attributes']
        );
        self::assertSame(['rights', 'active', 'parent'], $result['ignored']);
    }

    public function testIdentifiersAcceptUuidAndLegacyIdOnly(): void
    {
        $UserMethod = new ReflectionMethod(AbstractUserTool::class, 'getUserIdSchema');
        $GroupMethod = new ReflectionMethod(AbstractGroupTool::class, 'getGroupIdSchema');

        $userSchema = $UserMethod->invoke(null);
        $groupSchema = $GroupMethod->invoke(null);

        self::assertSame(['string', 'integer'], array_column($userSchema['oneOf'], 'type'));
        self::assertSame(['string', 'integer'], array_column($groupSchema['oneOf'], 'type'));
    }
}
