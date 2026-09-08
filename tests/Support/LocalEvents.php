<?php

namespace QUITests\Support;

use QUI;

/** Keep unrelated installed applications out of local Core fixtures. */
final class LocalEvents extends QUI\Events\Manager
{
    public function fireEvent(string $event, false|array $args = false, bool $force = false): array
    {
        foreach ($this->getList()[$event] ?? [] as $listener) {
            if ($listener['package'] !== '' && $listener['package'] !== 'quiqqer/core') {
                $this->removeEvent($event, $listener['callable']);
            }
        }
        return parent::fireEvent($event, $args, $force);
    }
}
