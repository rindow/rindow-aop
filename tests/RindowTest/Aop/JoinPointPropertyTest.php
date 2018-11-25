<?php
namespace RindowTest\Aop\JoinPointPropertyTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Signature;
use Rindow\Aop\Support\JoinPoint\PropertyJoinPoint;

class TestTarget
{
	public $something;
}

class Test extends TestCase
{
	public function testNormal()
	{
		$target = new TestTarget();
		$target->something = 'boo';
		$joinpoint = new PropertyJoinPoint(JoinPointInterface::ACTION_SET,$target,'something');
		$joinpoint->setValue('somevalue');

		$this->assertEquals(JoinPointInterface::ACTION_SET,$joinpoint->getAction());
		$this->assertEquals(__NAMESPACE__.'\TestTarget::$something',$joinpoint->getSignatureString());
		$this->assertEquals(__NAMESPACE__.'\TestTarget::$something',$joinpoint->toString());
		$this->assertEquals('Rindow\Aop\Support\Signature',get_class($joinpoint->getSignature()));
		$this->assertEquals(__NAMESPACE__.'\TestTarget',$joinpoint->getSignature()->getClassName());
		$this->assertEquals($target,$joinpoint->getTarget());

		$this->assertEquals('something',$joinpoint->getProperty());
		$this->assertEquals('somevalue',$joinpoint->getValue());
	}
}