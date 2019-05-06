<?php
namespace RindowTest\Aop\PointcutManagerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Pointcut\PointcutManager;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\Support\Signature;
use Rindow\Aop\Annotation\Pointcut;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;

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
    public static function setUpBeforeClass()
    {
    }
    public static function tearDownAfterClass()
    {
    }
    public function setUp()
    {
    }

    public function getConfigCacheFactory()
    {
        $config = array(
                //'fileCachePath'   => __DIR__.'/../cache',
                'configCache' => array(
                    'enableMemCache'  => true,
                    'enableFileCache' => true,
                    'forceFileCache'  => false,
                ),
                //'apcTimeOut'      => 20,
                'memCache' => array(
                    'class' => 'Rindow\Stdlib\Cache\SimpleCache\ArrayCache',
                ),
                'fileCache' => array(
                    'class' => 'Rindow\Stdlib\Cache\SimpleCache\ArrayCache',
                ),
        );
        $configCacheFactory = new ConfigCacheFactory($config);
        return $configCacheFactory;
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
		$configCacheFactory = $this->getConfigCacheFactory();

		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$manager = new PointcutManager($configCacheFactory);
		$manager->register(
			$manager->generate(
				$signature,
				'execution(**::test())')
		);
		$manager->save();

		$manager = new PointcutManager($configCacheFactory);
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');
		$pointcuts = $manager->find($joinpoint);
		$this->assertEquals(1,count($pointcuts));
	}

	public function testQueryCache()
	{
		$configCacheFactory = $this->getConfigCacheFactory();
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			__NAMESPACE__.'\TestAspect',
			'testpointcut'
		);
		$manager = new TestPointcutManager($configCacheFactory);
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

		$manager = new TestPointcutManager($configCacheFactory);
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'test');

		$this->assertFalse($manager->testFlag);
		$pointcuts = $manager->find($joinpoint);
		$this->assertFalse($manager->testFlag);

		$this->assertEquals(1,count($pointcuts));
	}
}