<?php
namespace RindowTest\Aop\AdviceEventCollectionTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\Support\Advice\AdviceEventCollection;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\JoinPointInterface;

class TestTarget
{
	public $phpunit;
	public function test($a,$b,$c)
	{
		$this->phpunit->assertEquals('a',$a);
		$this->phpunit->assertEquals('b',$b);
		$this->phpunit->assertEquals('c',$c);
		return 'success';
	}
	public function nonArgs()
	{
		return 'success';
	}
}

class TestAdviceManager
{
    protected $adviceContextStatus = false;
    public function inAdvice()
    {
        return $this->adviceContextStatus;
    }

    public function setAdvice($status)
    {
        $this->adviceContextStatus = $status;
    }
}

class Test extends TestCase
{
	public function testNotify()
	{
		$collection = new AdviceEventCollection();
		$collection->setAdviceManager(new TestAdviceManager());
		$phpunit = $this;
		$advice = function ($joinpoint) use ($phpunit,$collection) {
			$phpunit->assertEquals('Rindow\Aop\Support\JoinPoint\MethodJoinPoint',get_class($joinpoint));
			$phpunit->assertEquals('foo',$joinpoint->getName());
			$phpunit->assertTrue($collection->inAdvice());
		};
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');
		$joinpoint->setName('foo');
		$collection->attach($joinpoint->getName(),$advice);
		$this->assertFalse($collection->inAdvice());
		$collection->notify($joinpoint);
		$this->assertFalse($collection->inAdvice());
	}

	public function testCallNormal()
	{
		$target = new TestTarget();
		$target->phpunit = $this;
		$args = array('a','b','c');
		$collection = new AdviceEventCollection();
		$collection->setAdviceManager(new TestAdviceManager());
		$phpunit = $this;
		$advice = function ($joinpoint) use ($phpunit,$collection,$target,$args) {
			$phpunit->assertEquals('Rindow\Aop\Support\JoinPoint\ProceedingJoinPoint',get_class($joinpoint));
			$phpunit->assertEquals($target,$joinpoint->getTarget());
			$phpunit->assertEquals($args,$joinpoint->getParameters());
			$phpunit->assertEquals('foo',$joinpoint->toString());
			$phpunit->assertEquals(JoinPointInterface::ACTION_EXECUTION,$joinpoint->getAction());
			$phpunit->assertEquals(__NAMESPACE__.'\TestTarget::test()',$joinpoint->getSignatureString());
			$phpunit->assertTrue($collection->inAdvice());
			return $joinpoint->proceed();
		};
		$joinpoint = new MethodJoinPoint($target,'test');
		$callback = array($target,'test');
		$joinpoint->setName('foo');
		$joinpoint->setParameters($args);
		$collection->attach($joinpoint->getName(),$advice);
		$this->assertFalse($collection->inAdvice());
		$result = $collection->call($joinpoint,null,$callback);
		$this->assertFalse($collection->inAdvice());
		$this->assertEquals('success',$result);
	}

	public function testCallNonArgs()
	{
		$target = new TestTarget();
		$target->phpunit = $this;
		$args = array();
		$collection = new AdviceEventCollection();
		$collection->setAdviceManager(new TestAdviceManager());
		$phpunit = $this;
		$advice = function ($joinpoint) use ($phpunit,$collection,$target,$args) {
			$phpunit->assertEquals('Rindow\Aop\Support\JoinPoint\ProceedingJoinPoint',get_class($joinpoint));
			$phpunit->assertEquals($target,$joinpoint->getTarget());
			$phpunit->assertEquals($args,$joinpoint->getParameters());
			$phpunit->assertEquals('foo',$joinpoint->toString());
			$phpunit->assertEquals(JoinPointInterface::ACTION_EXECUTION,$joinpoint->getAction());
			$phpunit->assertEquals(__NAMESPACE__.'\TestTarget::nonArgs()',$joinpoint->getSignatureString());
			$phpunit->assertTrue($collection->inAdvice());
			return $joinpoint->proceed();
		};
		$joinpoint = new MethodJoinPoint($target,'nonArgs');
		$callback = array($target,'nonArgs');
		$joinpoint->setName('foo');
		$joinpoint->setParameters($args);
		$joinpoint->setTarget($target);
		$collection->attach($joinpoint->getName(),$advice);
		$this->assertFalse($collection->inAdvice());
		$result = $collection->call($joinpoint,null,$callback);
		$this->assertFalse($collection->inAdvice());
		$this->assertEquals('success',$result);
	}
}
