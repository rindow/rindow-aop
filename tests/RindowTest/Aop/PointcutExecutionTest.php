<?php
namespace RindowTest\Aop\PointcutExecutionTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Signature;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\Support\JoinPoint\PropertyJoinPoint;
use Rindow\Aop\Support\Pointcut\Execution;

class TestTarget
{
	public $someone;

	public function something()
	{
		# code...
	}
}

class TestExecution extends Execution
{
	public function getRegex()
	{
		return $this->regex;
	}
}

class Test extends TestCase
{
	public function testNormal()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');
		$pointcut = new TestExecution();
		$pointcut->setPattern('**::something()');
		$this->assertEquals('**::something()',$pointcut->getPattern());
		$this->assertEquals('/^[a-zA-Z0-9_\\\\]+::something\\(\\)$/',$pointcut->getRegex());
		$this->assertTrue($pointcut->matches($joinpoint));
	}

	public function testMatches()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');

		$pointcut = new TestExecution();
		$pointcut->setPattern('**::*()');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern('**::something()');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern('*::something()');
		$this->assertFalse($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\*::something()');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern('RindowTest\**::something()');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern('RindowTest\*::something()');
		$this->assertFalse($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\TestTarget::something()');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\TestTarget::*()');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\TestTarget::some*()');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\TestTarget::unmatch*()');
		$this->assertFalse($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\TestTarget::(another|something)()');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\TestTarget::(another|other)()');
		$this->assertFalse($pointcut->matches($joinpoint));
		//$pointcut->setPattern(__NAMESPACE__.'\TestTarget::$something');
		//$this->assertFalse($pointcut->matches($joinpoint));
	}

	public function testMatchesWithIllegal()
	{
		$this->markTestIncomplete();
		//$target = new TestTarget();
		//$joinpoint = new PropertyJoinPoint(JoinPointInterface::ACTION_SET,$target,'something');
		//$pointcut = new TestExecution();

		//$pointcut->setPattern(__NAMESPACE__.'\TestTarget::$something');
		//$this->assertFalse($pointcut->matches($joinpoint));
	}
}