<?php

declare(strict_types=1);

namespace QUI\Users;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Countries\Country;
use QUI\ERP\Currency\Currency;
use QUI\ERP\Currency\Handler;
use ReflectionProperty;

final class UserCurrencyTest extends TestCase
{
    private array $previousCurrencies;
    private ?Currency $previousDefault;
    private mixed $previousSessionCurrency;
    private Currency $Default;

    protected function setUp(): void
    {
        $this->previousCurrencies = (new ReflectionProperty(Handler::class, 'currencies'))->getValue();
        $this->previousDefault = (new ReflectionProperty(Handler::class, 'Default'))->getValue();
        $this->previousSessionCurrency = QUI::getSession()->get('currency');

        $currencies = [];

        foreach (['EUR', 'USD', 'CHF'] as $code) {
            $currencies[$code] = ['currency' => $code, 'rate' => 1.0, 'autoupdate' => 0];
        }

        (new ReflectionProperty(Handler::class, 'currencies'))->setValue(null, $currencies);
        $this->Default = new Currency($currencies['CHF']);
        (new ReflectionProperty(Handler::class, 'Default'))->setValue(null, $this->Default);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(Handler::class, 'currencies'))->setValue(null, $this->previousCurrencies);
        (new ReflectionProperty(Handler::class, 'Default'))->setValue(null, $this->previousDefault);
        QUI::getSession()->set('currency', $this->previousSessionCurrency);
    }

    public static function currencies(): iterable
    {
        $cases = [
            'selected currency' => ['USD', 'EUR', 'USD'],
            'country currency' => [null, 'EUR', 'EUR'],
            'default currency' => [null, null, 'CHF'],
            'invalid selection' => ['invalid', 'EUR', 'EUR'],
            'invalid country' => [null, 'invalid', 'CHF']
        ];

        foreach ([User::class, Nobody::class, SystemUser::class] as $class) {
            foreach ($cases as $name => $values) {
                yield $class . ': ' . $name => [$class, ...$values];
            }
        }
    }

    #[DataProvider('currencies')]
    public function testCurrencySelectionReturnsCurrencyObjects(
        string $class,
        ?string $selected,
        ?string $countryCurrency,
        string $expected
    ): void {
        $Country = null;

        if ($countryCurrency !== null) {
            $Country = $this->createMock(Country::class);
            $Country->method('getCurrencyCode')->willReturn($countryCurrency);
        }

        $User = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCountry'])
            ->getMock();
        $User->method('getCountry')->willReturn($Country);
        $User->setAttribute('currency', $selected);
        QUI::getSession()->set('currency', $selected);

        $Currency = $User->getCurrency();

        self::assertInstanceOf(Currency::class, $Currency);
        self::assertSame($expected, $Currency->getCode());

        if ($expected === 'CHF') {
            self::assertSame($this->Default, $Currency);
        }
    }
}
