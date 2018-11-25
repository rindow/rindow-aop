<?php
namespace RindowTest\Aop\AdviceManagerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\Container;
use Rindow\Aop\AdviceInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Support\Advice\AdviceManager;
use Rindow\Aop\Support\Advice\AdviceDefinition;
use Rindow\Aop\Support\Pointcut\PointcutManager;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\Support\Signature;

class TestAspect
{
	public function testpointcut(){}
	public function testpointcut2(){}

	public function testadvice(JoinPointInterface $joinpoint)
	{
		$joinpoint->getTarget()->setFlag('boom');
	}
}

class TestTarget
{
	protected $flag;

	public function setFlag($flag)
	{
		$this->flag = $flag;
	}

	public function test()
	{
		return $this->flag;
	}

	public function test2()
	{
		return $this->flag;
	}
}

class Test extends TestCase
{
    public function setUp()
    {
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

	public function testNormal()
	{
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$pointcutManager = new PointcutManager();
		$config = array(
			'components' => array(
				__NAMESPACE__.'\TestAspect'=>array(
				),
			),
		);
		$container = new Container($config);
		$adviceManager = new AdviceManager($pointcutManager,$container);

		$pointcut = $pointcutManager->generate(
			$signature,
			'execution(**::test())'
		);
		$pointcutManager->register(
			$pointcut
		);
		$advice = new AdviceDefinition();
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$advice->setPointcutSignature(
			$pointcut->getSignature()
		);
		$advice->setComponentName(__NAMESPACE__.'\TestAspect');
		$advice->setMethod('testadvice');

		$adviceManager->register($advice);

		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');
		$joinpoint->setName(AdviceInterface::TYPE_BEFORE);

		$eventManager = $adviceManager->getEventManager($joinpoint);
		$this->assertEquals($adviceManager,$eventManager->getAdviceManager());
		$this->assertEquals($container,$eventManager->getServiceLocator());
		$eventManager->setServiceLocator($container);
		$eventManager->notify($joinpoint);
		$this->assertEquals('boom',$target->test());
	}

	public function testMultiple()
	{
		$signature1 = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$signature2 = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut2'
		);
		$pointcutManager = new PointcutManager();
		$config = array(
			'components' => array(
				__NAMESPACE__.'\TestAspect'=>array(
				),
			),
		);
		$container = new Container($config);
		$adviceManager = new AdviceManager($pointcutManager,$container);

		$pointcut1 = $pointcutManager->generate(
			$signature1,
			'execution(**::test())'
		);
		$pointcutManager->register(
			$pointcut1
		);
		$pointcut2 = $pointcutManager->generate(
			$signature2,
			'execution(**::test2())'
		);
		$pointcutManager->register(
			$pointcut2
		);
		$advice = new AdviceDefinition();
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$advice->setPointcutSignature(
			array($pointcut1->getSignature(),$pointcut2->getSignature())
		);
		$advice->setComponentName(__NAMESPACE__.'\TestAspect');
		$advice->setMethod('testadvice');

		$adviceManager->register($advice);

		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');
		$joinpoint->setName(AdviceInterface::TYPE_BEFORE);

		$eventManager = $adviceManager->getEventManager($joinpoint);
		$this->assertEquals($adviceManager,$eventManager->getAdviceManager());
		$this->assertEquals($container,$eventManager->getServiceLocator());
		$eventManager->setServiceLocator($container);
		$eventManager->notify($joinpoint);
		$this->assertEquals('boom',$target->test());

		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test2');
		$joinpoint->setName(AdviceInterface::TYPE_BEFORE);

		$eventManager = $adviceManager->getEventManager($joinpoint);
		$this->assertEquals($adviceManager,$eventManager->getAdviceManager());
		$this->assertEquals($container,$eventManager->getServiceLocator());
		$eventManager->setServiceLocator($container);
		$eventManager->notify($joinpoint);
		$this->assertEquals('boom',$target->test2());

	}

	public function testCache()
	{
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$pointcutManager = new PointcutManager();
		$config = array(
			'components' => array(
				__NAMESPACE__.'\TestAspect'=>array(
				),
			),
		);
		$container = new Container($config);
		$adviceManager = new AdviceManager($pointcutManager,$container);

		$pointcut = $pointcutManager->generate(
			$signature,
			'execution(**::test())'
		);
		$pointcutManager->register(
			$pointcut
		);
		$advice = new AdviceDefinition();
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$advice->setPointcutSignature(
			$pointcut->getSignature()
		);
		$advice->setComponentName(__NAMESPACE__.'\TestAspect');
		$advice->setMethod('testadvice');

		$adviceManager->register($advice);

		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');
		$joinpoint->setName(AdviceInterface::TYPE_BEFORE);

		$eventManager = $adviceManager->getEventManager($joinpoint);
		$this->assertEquals($adviceManager,$eventManager->getAdviceManager());
		$this->assertEquals($container,$eventManager->getServiceLocator());
		$eventManager->setServiceLocator($container);
		$eventManager->notify($joinpoint);
		$this->assertEquals('boom',$target->test());

		// ------- cached ---------------

		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$pointcutManager = new PointcutManager();
		$config = array(
			'components' => array(
				__NAMESPACE__.'\TestAspect'=>array(
				),
			),
		);
		$container = new Container($config);
		$adviceManager = new AdviceManager($pointcutManager,$container);

		$pointcut = $pointcutManager->generate(
			$signature,
			'execution(**::test())'
		);
		$pointcutManager->register(
			$pointcut
		);
		$advice = new AdviceDefinition();
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$advice->setPointcutSignature(
			$pointcut->getSignature()
		);
		$advice->setComponentName(__NAMESPACE__.'\TestAspect');
		$advice->setMethod('testadvice');

		$adviceManager->register($advice);

		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');
		$joinpoint->setName(AdviceInterface::TYPE_BEFORE);

		$eventManager = $adviceManager->getEventManager($joinpoint);
		$this->assertEquals($adviceManager,$eventManager->getAdviceManager());
		$this->assertEquals($container,$eventManager->getServiceLocator());
		$eventManager->setServiceLocator($container);
		$eventManager->notify($joinpoint);
		$this->assertEquals('boom',$target->test());
	}
}