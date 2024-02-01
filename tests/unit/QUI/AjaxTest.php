<?php

namespace QUI;

use PHPUnit\Framework\TestCase;

class AjaxTest extends TestCase
{
    public function testRegisterAndGetFunction()
    {
      $testFunction = function() {};
      $testFunctionName = 'test_function';

      $sut = new Ajax();
      $sut::registerFunction($testFunctionName, $testFunction);

      $this->assertArrayHasKey($testFunctionName, $sut::getRegisteredCallables());
      $this->assertEquals($testFunction, $sut::getRegisteredCallables()[$testFunctionName]['callable']);
    }

    public function testRegisterAndGetCallable()
    {
      $sut = new Ajax();
      $regVars = ['var1', 'var2'];
      $testFunction = 'test_function';

      $sut::register($testFunction, $regVars);

      $this->assertArrayHasKey($testFunction, $sut::getRegisteredFunctions());
      $this->assertEquals($regVars, $sut::getRegisteredFunctions()[$testFunction]);
    }

    public function testWriteExceptionReturnsWellFormedArray()
    {
      $message = 'test';
      $code = 123;
      $testException = new \Exception(message: $message, code: $code);

      $sut = (new Ajax())->writeException($testException);

      $this->assertEquals($message, $sut['Exception']['message']);
      $this->assertEquals($code, $sut['Exception']['code']);
      $this->assertEquals($testException::class, $sut['Exception']['type']);
    }

    public function testWriteExceptionRemovesHtmlFromMessage()
    {
      $messageToTest = '<iframe></iframe><p>myP</p><div>myDiv</div><h1>myH1</h1>';
      $cleanedMessage = '<p>myP</p><div>myDiv</div>myH1';

      $sut = (new Ajax())->writeException(new \Exception(message: $messageToTest));

      $this->assertEquals($cleanedMessage, $sut['Exception']['message']);
    }
}
