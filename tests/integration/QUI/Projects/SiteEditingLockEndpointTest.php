<?php

namespace QUI\Projects;

use QUI;
use QUI\Lock\Locker;
use QUI\Projects\Site\Edit;
use QUI\Security\CsrfToken;

class SiteEditingLockEndpointTest extends ProjectIntegrationTestCase
{
    public function testEditorEndpointsEnforceTheLeaseWhenSavingAndClosing(): void
    {
        $Project = self::getTestProject();
        $previousAjax = QUI::$Ajax;
        $previousSession = QUI::$Session;
        $id = null;
        $token = str_repeat('a', 32);
        $newToken = str_repeat('b', 32);

        try {
            ProjectTestHelper::runAsSystemUser(function () use ($Project, &$id, $token, $newToken): void {
                $id = $Project->firstChild()->getEdit()->createChild([
                    'name' => 'editing-lock-' . bin2hex(random_bytes(6)), 'title' => 'Before'
                ]);
                $Site = new Edit($Project, $id);
                $Root = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));
                $Session = new QUI\System\Console\Session();
                foreach (
                    ['uid' => $Root->getUUID(), 'username' => $Root->getUsername(),
                    'auth' => 1, 'auth-primary' => 1, 'auth-secondary' => 1] as $key => $value
                ) {
                    $Session->set($key, $value);
                }
                QUI::$Session = $Session;
                (new \ReflectionProperty(QUI::getUsers(), 'Session'))->setValue(QUI::getUsers(), $Root);
                QUI\Permissions\Permission::setUser($Root);
                QUI::$Ajax = new QUI\Ajax();
                foreach (['lock', 'refreshLock', 'unlock', 'save', 'isLockedFromOther'] as $action) {
                    require dirname(__DIR__, 4) . '/admin/ajax/site/' . $action . '.php';
                }

                $params = ['project' => json_encode($Project->toArray()), 'id' => $id,
                    'token' => $token, '_csrf' => CsrfToken::get()];
                $request = static fn(string $action, array $extra = []): array =>
                    QUI::getAjax()->callRequestFunction('ajax_site_' . $action, array_merge($params, $extra));
                $locked = $request('lock');
                self::assertTrue($locked['result'], json_encode($locked));
                self::assertTrue($request('lock', ['token' => $newToken])['result']);
                self::assertTrue($request('refreshLock')['result']);
                self::assertTrue($request('refreshLock', ['token' => $newToken])['result']);
                self::assertFalse($request('isLockedFromOther')['result']);

                $result = $request('save', ['attributes' => json_encode(['title' => 'Saved']), 'lockToken' => $token]);
                self::assertArrayNotHasKey('Exception', $result, json_encode($result));
                $Site->refresh();
                self::assertSame('Saved', $Site->getAttribute('title'));

                $request('unlock');
                self::assertTrue($request('refreshLock', ['token' => $newToken])['result']);
                self::assertFalse($request('refreshLock')['result']);
                $result = $request('save', [
                    'attributes' => json_encode(['title' => 'Saved in another tab']), 'lockToken' => $newToken
                ]);
                self::assertArrayNotHasKey('Exception', $result, json_encode($result));
                self::assertTrue($request('lock')['result']);

                $request('unlock', ['force' => 1]);
                self::assertTrue($request('lock', ['token' => $newToken])['result']);
                $request('unlock'); // A late close request from the old tab.
                self::assertTrue($request('refreshLock', ['token' => $newToken])['result']);

                $result = $request('save', ['attributes' => json_encode(['title' => 'Stale']), 'lockToken' => $token]);
                self::assertArrayHasKey('Exception', $result);
                $Site->refresh();
                self::assertSame('Saved in another tab', $Site->getAttribute('title'));

                $result = $request('save', ['attributes' => json_encode(['title' => 'Missing token'])]);
                self::assertArrayHasKey('Exception', $result);
                $request('unlock', ['token' => $newToken]);
                self::assertFalse($request('refreshLock', ['token' => $newToken])['result']);
            });
        } finally {
            ProjectTestHelper::runAsSystemUser(function () use ($Project, $id): void {
                if ($id !== null) {
                    $Site = new Edit($Project, $id);
                    $Site->unlockWithRights();
                    $Site->delete();
                    $Site->destroy();
                }
            });
            QUI::$Ajax = $previousAjax;
            QUI::$Session = $previousSession;
            Locker::setProcessLockStore(null);
        }
    }
}
