<?php

namespace QUI\Events;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI\ExceptionStack;

#[RunTestsInSeparateProcesses]
class EventTest extends TestCase
{
    public static function noArgsHandler(): string
    {
        return 'no-args';
    }

    public static function withArgsHandler(string $value): string
    {
        return 'arg:' . $value;
    }

    public static function lowPriorityHandler(array &$calls): string
    {
        $calls[] = 'low';
        return 'low';
    }

    public static function highPriorityHandler(array &$calls): string
    {
        $calls[] = 'high';
        return 'high';
    }

    public static function throwingQuiException(): void
    {
        throw new \QUI\Exception('qui-failure', 101);
    }

    public static function throwingRuntimeException(): void
    {
        throw new \RuntimeException('runtime-failure', 202);
    }

    public function testAddEventStoresCallableWithMetadata(): void
    {
        $sut = new Event();

        $sut->addEvent('onTest', self::class . '::withArgsHandler', 7, 'pkg/test');
        $list = $sut->getList();

        $this->assertSame(self::class . '::withArgsHandler', $list['onTest'][0]['callable']);
        $this->assertSame(7, $list['onTest'][0]['priority']);
        $this->assertSame('pkg/test', $list['onTest'][0]['package']);
    }

    public function testAddEventSkipsDuplicateCallable(): void
    {
        $sut = new Event();

        $sut->addEvent('onTest', self::class . '::withArgsHandler');
        $sut->addEvent('onTest', self::class . '::withArgsHandler');

        $this->assertCount(1, $sut->getList()['onTest']);
    }

    public function testAddEventSkipsInvalidCallable(): void
    {
        $sut = new Event();

        $sut->addEvent('onTest', 'not_a_valid_callable');

        $this->assertSame([], $sut->getList()['onTest']);
    }

    public function testAddEventsAcceptsPlainAndTupleDefinitions(): void
    {
        $sut = new Event();

        $sut->addEvents([
            'onOne' => self::class . '::noArgsHandler',
            'onTwo' => [self::class . '::withArgsHandler', 5, 'pkg/test']
        ]);

        $list = $sut->getList();

        $this->assertSame(self::class . '::noArgsHandler', $list['onOne'][0]['callable']);
        $this->assertSame(5, $list['onTwo'][0]['priority']);
        $this->assertSame('pkg/test', $list['onTwo'][0]['package']);
    }

    public function testRemoveEventRemovesSpecificCallable(): void
    {
        $sut = new Event();

        $sut->addEvent('onTest', self::class . '::noArgsHandler');
        $sut->addEvent('onTest', self::class . '::withArgsHandler');

        $sut->removeEvent('onTest', self::class . '::noArgsHandler');

        $this->assertCount(1, $sut->getList()['onTest']);
        $this->assertSame(self::class . '::withArgsHandler', $sut->getList()['onTest'][1]['callable']);
    }

    public function testRemoveEventRemovesWholeEvent(): void
    {
        $sut = new Event();

        $sut->addEvent('onTest', self::class . '::noArgsHandler');
        $sut->removeEvent('onTest');

        $this->assertArrayNotHasKey('onTest', $sut->getList());
    }

    public function testRemoveEventsRemovesMultipleDefinitions(): void
    {
        $sut = new Event();

        $sut->addEvent('onOne', self::class . '::noArgsHandler');
        $sut->addEvent('onTwo', self::class . '::withArgsHandler');

        $sut->removeEvents([
            'onOne' => false,
            'onTwo' => self::class . '::withArgsHandler'
        ]);

        $this->assertArrayNotHasKey('onOne', $sut->getList());
        $this->assertArrayNotHasKey('onTwo', $sut->getList());
    }

    public function testFireEventAddsPrefixSortsByPriorityAndReturnsResults(): void
    {
        $sut = new Event();
        $calls = [];

        $sut->addEvent('onSorted', static function () use (&$calls) {
            return self::highPriorityHandler($calls);
        }, 20);
        $sut->addEvent('onSorted', static function () use (&$calls) {
            return self::lowPriorityHandler($calls);
        }, 10);

        $result = $sut->fireEvent('sorted');

        $this->assertSame(['low', 'high'], $calls);
        $this->assertSame([], $result);
    }

    public function testFireEventCallsStringHandlerWithoutArgs(): void
    {
        $sut = new Event();
        $sut->addEvent('onTest', self::class . '::noArgsHandler');

        $result = $sut->fireEvent('onTest');

        $this->assertSame(['QUI\Events\EventTest::noArgsHandler' => 'no-args'], $result);
    }

    public function testFireEventCallsStringHandlerWithArgs(): void
    {
        $sut = new Event();
        $sut->addEvent('onTest', self::class . '::withArgsHandler');

        $result = $sut->fireEvent('onTest', ['value']);

        $this->assertSame(['QUI\Events\EventTest::withArgsHandler' => 'arg:value'], $result);
    }

    public function testFireEventSkipsIgnoredPackage(): void
    {
        $sut = new Event();
        $sut->addEvent('onTest', self::class . '::noArgsHandler', 0, 'pkg/test');
        $sut->ignore('pkg/test');

        $result = $sut->fireEvent('onTest');

        $this->assertSame([], $result);
    }

    public function testClearIgnoreRemovesIgnoreMarkers(): void
    {
        $sut = new Event();
        $sut->addEvent('onTest', self::class . '::noArgsHandler', 0, 'pkg/test');
        $sut->ignore('pkg/test');
        $sut->clearIgnore();

        $result = $sut->fireEvent('onTest');

        $this->assertSame(['QUI\Events\EventTest::noArgsHandler' => 'no-args'], $result);
    }

    public function testFireEventPreventsRecursionWithoutForce(): void
    {
        $sut = new Event();
        $calls = 0;

        $sut->addEvent('onLoop', static function () use (&$calls, $sut): void {
            $calls++;
            $sut->fireEvent('onLoop');
        });

        $sut->fireEvent('onLoop');

        $this->assertSame(1, $calls);
    }

    public function testFireEventAllowsRecursionWithForce(): void
    {
        $sut = new Event();
        $calls = 0;

        $sut->addEvent('onLoop', static function () use (&$calls, $sut): void {
            $calls++;

            if ($calls < 2) {
                $sut->fireEvent('onLoop', false, true);
            }
        });

        $sut->fireEvent('onLoop');

        $this->assertSame(2, $calls);
    }

    public function testFireEventAggregatesQuiAndRuntimeExceptions(): void
    {
        $sut = new Event();
        $sut->addEvent('onFail', self::class . '::throwingQuiException');
        $sut->addEvent('onFail', self::class . '::throwingRuntimeException');

        try {
            $sut->fireEvent('onFail');
            $this->fail('Expected exception stack was not thrown.');
        } catch (ExceptionStack $stack) {
            $exceptions = $stack->getExceptionList();

            $this->assertCount(2, $exceptions);
            $this->assertStringContainsString('qui-failure', $exceptions[0]->getMessage());
            $this->assertStringContainsString('runtime-failure', $exceptions[1]->getMessage());
        }
    }
}
