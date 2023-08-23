<?php

namespace QUI\Event;

use function get_class;

final class EventDispatcher
{
    private array $listeners = [];

    public function dispatch(object $event): object
    {
        $this->callListeners($event);

        return $event;
    }

    private function callListeners(object $event): void
    {
        $isStoppableEvent = $event instanceof StoppableEventInterface;

        $registeredListeners = $this->getListeners(get_class($event));

        foreach ($registeredListeners as $listener) {
            if ($isStoppableEvent && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }
    }

    public function getListeners(string $eventName): array
    {
        if (empty($this->listeners[$eventName])) {
            return [];
        }

        return $this->listeners[$eventName];
    }

    public function addListener(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function removeListener(string $eventName, callable $listenerToRemove): void
    {
        $registeredListeners = $this->getListeners($eventName);

        foreach ($registeredListeners as $key => $registeredListener) {
            if ($registeredListener !== $listenerToRemove) {
                continue;
            }

            unset($this->listeners[$eventName][$key]);
        }
    }
}
