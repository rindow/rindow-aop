<?php
namespace RindowTest\Aop\PointcutManagerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Pointcut\PointcutManager;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\Support\Signature;
use Rindow\Aop\Annotation\Pointcut;

class TestAspect
{
	public function testpointcut(){}
}

class TestTarget
{
	public function test()
	{
	}
}

class TestPointcutManager extends PointcutManager
{
	public $testFlag = false;

	public function getPointcuts()
	{
		$this->testFlag = true;
		return parent::getPointcuts();
	}
}


class Test extends TestCase
{
    public static $backupCacheMode;
    public static function setUpBeforeClass()
    {
        self::$backupCacheMode = \Rindow\Stdlib\Cache\CacheFactory::$notRegister;
    }
    public static function tearDownAfterClass()
    {
        \Rindow\Stdlib\Cache\CacheFactory::$notRegister = self::$backupCacheMode;
    }
    public function setUp()
    {
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
    }

	public function testGenerateFromString()
	{
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$manager = new PointcutManager();
		$pointcut = $manager->generate(
			$signature,
			'execution(**::test())');
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');

		$this->assertEquals($signature,$pointcut->getSignature());
		$this->assertEquals(__NAMESPACE__.'\TestAspect::testpointcut()',$pointcut->getSignatureString());
		$this->assertEquals('execution(**::test())',$pointcut->getPattern());
		$this->assertTrue($pointcut->matches($joinpoint));

		$pointcut = $manager->generate(
			$signature,
			'execution(**::unmatch())');
		$this->assertFalse($pointcut->matches($joinpoint));
	}

	public function testGenerateFromAnnotation()
	{
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$manager = new PointcutManager();
		$annotation = new Pointcut();
		$annotation->value = 'execution(**::test())';
		$pointcut = $manager->generate(
			$signature,
			$annotation);
		$this->assertEquals($annotation,$pointcut);
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');

		$this->assertEquals($signature,$pointcut->getSignature());
		$this->assertEquals(__NAMESPACE__.'\TestAspect::testpointcut()',$pointcut->getSignatureString());
		$this->assertEquals('execution(**::test())',$pointcut->getPattern());
		$this->assertTrue($pointcut->matches($joinpoint));

		$annotation = new Pointcut();
		$annotation->value = 'execution(**::unmatch())';
		$pointcut = $manager->generate(
			$signature,
			$annotation);
		$this->assertEquals($annotation,$pointcut);
		$this->assertFalse($pointcut->matches($joinpoint));
	}

	public function testRegisterAndFind()
	{
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$manager = new PointcutManager();
		$manager->register(
			$manager->generate(
				$signature,
				'execution(**::test())')
		);
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut2'
		);
		$manager->register(
			$manager->generate(
				$signature,
				'execution(**::unmatch())')
		);
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut3'
		);
		$manager->register(
			$manager->generate(
				$signature,
				'execution(**::*())')
		);
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');
		$pointcuts = $manager->find($joinpoint);
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('testpointcut',$pointcuts[0]->getSignature()->getMethod());
		$this->assertEquals('testpointcut3',$pointcuts[1]->getSignature()->getMethod());
	}

	public function testSaveAndLoad()
	{
        $notRegister = \Rindow\Stdlib\Cache\CacheFactory::$notRegister = false;
        \Rindow\Stdlib\Cache\CacheFactory::$notRegister = false;
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$manager = new PointcutManager();
		$manager->register(
			$manager->generate(
				$signature,
				'execution(**::test())')
		);
		$manager->save();

		$manager = new PointcutManager();
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');
		$pointcuts = $manager->find($joinpoint);
		$this->assertEquals(1,count($pointcuts));
        \Rindow\Stdlib\Cache\CacheFactory::$notRegister = $notRegister;
	}

	public function testQueryCache()
	{
        $notRegister = \Rindow\Stdlib\Cache\CacheFactory::$notRegister = false;
        \Rindow\Stdlib\Cache\CacheFactory::$notRegister = false;
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$manager = new TestPointcutManager();
		$manager->register(
			$manager->generate(
				$signature,
				'execution(**::test())')
		);

		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');

		$this->assertFalse($manager->testFlag);
		$pointcuts = $manager->find($joinpoint);
		$this->assertTrue($manager->testFlag);

		$this->assertEquals(1,count($pointcuts));

		$manager = new TestPointcutManager();
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');

		$this->assertFalse($manager->testFlag);
		$pointcuts = $manager->find($joinpoint);
		$this->assertFalse($manager->testFlag);

		$this->assertEquals(1,count($pointcuts));
        \Rindow\Stdlib\Cache\CacheFactory::$notRegister = $notRegister;
	}
}