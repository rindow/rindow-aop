<?php
namespace RindowTest\Aop\PointcutWithinTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Signature;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\Support\JoinPoint\PropertyJoinPoint;
use Rindow\Aop\Support\Pointcut\Within;

class TestTarget
{
	public $someone;

	public function something()
	{
		# code...
	}
}

class TestWithin extends Within
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
		$pointcut = new TestWithin();
		$pointcut->setPattern('**');
		$this->assertEquals('**',$pointcut->getPattern());
		$this->assertEquals('/^[a-zA-Z0-9_\\\\]+$/',$pointcut->getRegex());
		$this->assertTrue($pointcut->matches($joinpoint));
	}

	public function testMatches()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');

		$pointcut = new TestWithin();
		$pointcut->setPattern('**');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern('*');
		$this->assertFalse($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\*');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern('RindowTest\**');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern('RindowTest\*');
		$this->assertFalse($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\TestTarget');
		$this->assertTrue($pointcut->matches($joinpoint));
		//$pointcut->setPattern(__NAMESPACE__.'\TestTarget::$something');
		//$this->assertFalse($pointcut->matches($joinpoint));
	}

	public function testMatchesWithProperty()
	{
		$target = new TestTarget();
		$joinpoint = new PropertyJoinPoint(JoinPointInterface::ACTION_SET,$target,'something');
		$pointcut = new TestWithin();

		$pointcut->setPattern(__NAMESPACE__.'\TestTarget');
		$this->assertTrue($pointcut->matches($joinpoint));
	}
}