<?php
namespace RindowTest\Aop\PointcutTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\MatcherInterface;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Support\Pointcut\Pointcut;
use Rindow\Aop\Support\Signature;

class TestAspect
{
	public function testpointcut(){}
}

class TestMatcher implements MatcherInterface
{
	public $result = true;

	public function matches(JoinPointInterface $joinpoint)
	{
		return $this->result;
	}
}
class TestJoinPoint implements JoinPointInterface
{
    public function getTarget() {}
    public function getParameters() {}
    public function getAction() {}
    public function getSignature() {}
    public function getSignatureString() {}
    public function toString() {}
}

class Test extends TestCase
{
	public function testNormal()
	{
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$matcher = new TestMatcher();
		$pointcut = new Pointcut();
		$pointcut->setSignature($signature);
		$pointcut->setPattern('foo(bar)');
		$pointcut->setMatcher($matcher);

		$joinpoint = new TestJoinPoint();

		$this->assertEquals($signature,$pointcut->getSignature());
		$this->assertEquals(__NAMESPACE__.'\TestAspect::testpointcut()',$pointcut->getSignatureString());
		$this->assertEquals('foo(bar)',$pointcut->getPattern());
		$this->assertTrue($pointcut->matches($joinpoint));
		$matcher->result = false;
		$this->assertFalse($pointcut->matches($joinpoint));
	}
}