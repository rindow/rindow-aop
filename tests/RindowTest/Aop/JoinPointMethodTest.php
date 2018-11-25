<?php
namespace RindowTest\Aop\JoinPointMethodTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;

class TestTarget
{
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

		$this->assertEquals(JoinPointInterface::ACTION_EXECUTION,$joinpoint->getAction());
		$this->assertEquals(__NAMESPACE__.'\TestTarget::something()',$joinpoint->getSignatureString());
		$this->assertEquals(__NAMESPACE__.'\TestTarget::something()',$joinpoint->toString());
		$this->assertEquals('Rindow\Aop\Support\Signature',get_class($joinpoint->getSignature()));
		$this->assertEquals(__NAMESPACE__.'\TestTarget',$joinpoint->getSignature()->getClassName());
		$this->assertEquals($target,$joinpoint->getTarget());

		$this->assertEquals('something',$joinpoint->getMethod());
		$joinpoint->setReturning('result');
		$this->assertEquals('result',$joinpoint->getReturning());
		$exception = new \Exception('a');
		$joinpoint->setThrowing($exception);
		$this->assertEquals($exception,$joinpoint->getThrowing());
	}
}