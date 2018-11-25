<?php
namespace RindowTest\Aop\PointcutTargetTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Signature;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\Support\JoinPoint\PropertyJoinPoint;
use Rindow\Aop\Support\Pointcut\Target;

interface TestTargetInterface
{}

class SuperTestTarget
{}

class TestTarget extends SuperTestTarget implements TestTargetInterface
{
	public $someone;

	public function something()
	{
		# code...
	}
}

class Test extends TestCase
{
	public function testNormal()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');
		$pointcut = new Target();
		$pointcut->setPattern(get_class($target));
		$this->assertEquals(get_class($target),$pointcut->getPattern());
		$this->assertTrue($pointcut->matches($joinpoint));
	}

	public function testMatches()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');

		$pointcut = new Target();
		$pointcut->setPattern(__NAMESPACE__.'\SuperTestTarget');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__.'\TestTargetInterface');
		$this->assertTrue($pointcut->matches($joinpoint));
		$pointcut->setPattern(__NAMESPACE__);
		$this->assertFalse($pointcut->matches($joinpoint));
	}

	public function testMatchesWithProperty()
	{
		$target = new TestTarget();
		$joinpoint = new PropertyJoinPoint(JoinPointInterface::ACTION_SET,$target,'something');
		$pointcut = new Target();

		$pointcut->setPattern(__NAMESPACE__.'\TestTarget');
		$this->assertTrue($pointcut->matches($joinpoint));
	}
}