<?php

namespace QUI;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

# Tests have to run in separate processes since some properties of Locale are static and thus leak into other tests
#[RunTestsInSeparateProcesses]
class LocaleTest extends TestCase
{

  public function testSetCurrent()
  {
    $sut = new Locale();

    $testLanguage = 'fr';
    $sut->setCurrent($testLanguage);

    $this->assertEquals($testLanguage, $sut->getCurrent());
  }

  public function testResetCurrent()
  {
    $this->markTestSkipped('Test skipped: resetCurrent is bugged, thus testing is worthless (see quiqqer/quiqqer#1333)');

    $sut = new Locale();
    $testLanguage = 'fr';

    $sut->setCurrent($testLanguage);
    $sut->setTemporaryCurrent('it');
    $sut->resetCurrent();

    $this->assertEquals($testLanguage, $sut->getCurrent());
  }

  public function testSetTemporaryCurrent()
  {
    $sut = new Locale();

    $testLanguage = 'fr';
    $sut->setTemporaryCurrent($testLanguage);

    $this->assertEquals($testLanguage, $sut->getCurrent());
  }

  public static function formatNumberDataProvider(): array
  {
    return [
      ['de', 1_234.567, '1.234,567'],
      ['de', 123_456.789, '123.456,789'],
      ['de', 123_456_789.1, '123.456.789,1'],
      ['de', 1.1, '1,1'],
      ['de', 1.987654321, '1,988'],
      ['de', 2, '2'],
      ['en', 1_234.567, '1,234.567'],
      ['en', 123_456.789, '123,456.789'],
      ['en', 123_456_789.1, '123,456,789.1'],
      ['en', 1.1, '1.1'],
      ['en', 1.987654321, '1.988'],
      ['en', 2, '2'],
    ];
  }

  #[DataProvider('formatNumberDataProvider')]
  public function testFormatNumber(string $language, float $numberToFormat, string $expectedFormat)
  {
    $locale = new Locale();
    $locale->setCurrent($language);

    $sut = $locale->formatNumber($numberToFormat, \NumberFormatter::SCIENTIFIC);

    $this->assertEquals($expectedFormat, $sut);
  }

  public function testGetDateFormatterContainsCurrentLanguage()
  {
    $locale = new Locale();
    $expectedLanguage = 'fr';
    $locale->setCurrent($expectedLanguage);

    $sut = $locale->getDateFormatter();

    $this->assertEquals($expectedLanguage, $sut->getLocale());
  }

  public function testExistsReturnsFalseOnRandomLocaleVariable()
  {
    $sut = new Locale();

    $this->assertFalse($sut->exists('abc', '123'));
  }

  public function testExistsLangReturnsFalseOnNonExistingLanguage()
  {
    $sut = new Locale();

    $this->assertFalse($sut->existsLang('abcdefg'));
  }

  public static function isLocaleStringDataProvider(): array
  {
    return [
      ['[quiqqer/quiqqer] this.is.a.test', true],
      ['[quiqqer/quiqqer] hello', true],
      ['[quiqqer/quiqqer] 123', true],
      ['this.is.a.test', false],
      ['this.is.a.test [quiqqer/quiqqer]', false],
      ['', false],
      ['[ ]', false],
    ];
  }

  #[DataProvider('isLocaleStringDataProvider')]
  public function testIsLocaleString(string $localeString, bool $expectedResult)
  {
    $this->markTestSkipped('Test skipped: isLocaleString behaves wrong, thus testing is worthless (see quiqqer/quiqqer#1334)');

    $sut = new Locale();

    $this->assertEquals($expectedResult, $sut->isLocaleString($localeString));
  }

  public static function getPartsOfLocaleStringProvider(): array
  {
    return [
      ['[quiqqer/quiqqer] this.is.a.test', 'quiqqer/quiqqer', 'this.is.a.test'],
      ['[quiqqer/quiqqer] hello', 'quiqqer/quiqqer', 'hello'],
      ['[quiqqer/quiqqer] 123', 'quiqqer/quiqqer', '123'],
      ['this.is.a.test', null, null],
      ['this.is.a.test [quiqqer/quiqqer]', null, null],
      ['', null, null],
      ['[ ]', null, null],
    ];
  }

  #[DataProvider('getPartsOfLocaleStringProvider')]
  public function testGetPartsOfLocaleString(string $localeStringToTest, ?string $expectedGroup, ?string $expectedVariable)
  {
    $this->markTestSkipped('Test skipped: getPartsOfLocaleString behaves odd, thus testing is worthless (see quiqqer/quiqqer#1335)');

    $locale = new Locale();

    $sut = $locale->getPartsOfLocaleString($localeStringToTest);

    $this->assertEquals($expectedGroup, $sut[0]);
    $this->assertEquals($expectedVariable, $sut[1]);
  }
}
