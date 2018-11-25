<?php
namespace RindowTest\Aop\AspectCollectorTest;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rindow\Container\Container;
use Rindow\Stdlib\Cache\CacheFactory;
use Rindow\Annotation\AnnotationManager;

use Rindow\Aop\AopManager;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\AdviceInterface;
use Rindow\Aop\Annotation\Pointcut;
use Rindow\Aop\Annotation\Aspect;
use Rindow\Aop\Annotation\Before;
use Rindow\Aop\Annotation\AfterReturning;
use Rindow\Aop\Annotation\AfterThrowing;
use Rindow\Aop\Annotation\After;
use Rindow\Aop\Annotation\Around;
use Rindow\Aop\Support\Signature;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;

class TestTarget
{
    public function test()
    {
    }
}
class TestTargetInterceptorDummy
{
    function __construct(
            $container,
            $component,
            $eventManager,
            $lazy)
    {
    }
}

class TestPlainOldPhpObjectAspect
{
	public function foo1($event)
	{
		return __METHOD__;
	}
	public function foo2($event)
	{
		return __METHOD__;
	}
}

class TestAdvisor
{
    public function invoke($event)
    {
        $event->proceed();
        return 'advisor!!';
    }
}

class TestAnnotationAspect
{
	/**
	* @Before("execution(**::test())")
	*/
	public function foo1($event)
	{
		return __METHOD__;
	}
}
class TestEtcAnnotationAspect
{
    /**
    * @Pointcut("execution(**::test())")
    */
    public function pc1() {}

    /**
    * @Before(pointcut="pc1")
    */
    public function foo1($event) {}

    /**
    * @AfterReturning("execution(**::test2())")
    */
    public function foo2($event) {}

    /**
    * @AfterThrowing("execution(**::test3())")
    */
    public function foo3($event) {}

    /**
    * @After("execution(**::test4())")
    */
    public function foo4($event) {}

    /**
    * @Around("execution(**::test5())")
    */
    public function foo5($event) {}
}

class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static $backupCacheMode;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../resources';
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

    public function createTestMock($className,$methods = array(), array $arguments = array())
    {
        $args = func_get_args();
        if(count($args)==0 || count($args)>3)
            throw new \Exception('illegal mock style');
        $builder = $this->getMockBuilder($className);
        $builder->setMethods($methods);
        $builder->setConstructorArgs($arguments);
        return $builder->getMock();
    }

    public function testAddPointcut()
    {
        $container = new Container();
        $aop = new AopManager($container);
        $pointcutManager = $aop->getAdviceManager()->getPointcutManager();

        // signature string
        $aop->addPointcut('execution(**::*())','test1');
        $pointcuts = $pointcutManager->getPointcuts();
        $this->assertInstanceOf('Rindow\Aop\Support\Pointcut\Pointcut',$pointcuts['test1']);
        $this->assertEquals('execution(**::*())',$pointcuts['test1']->getPattern());
        $this->assertEquals('test1',$pointcuts['test1']->getSignatureString());
        $this->assertEquals(SignatureInterface::TYPE_LABEL,$pointcuts['test1']->getSignature()->getType());

        // signature 
        $signature = new Signature(
            SignatureInterface::TYPE_METHOD,
            'test1class',
            'testmethod');
        $aop->addPointcut('execution(**::test())',$signature);
        $pointcuts = $pointcutManager->getPointcuts();
        $this->assertInstanceOf('Rindow\Aop\Support\Pointcut\Pointcut',$pointcuts['test1class::testmethod()']);
        $this->assertEquals('execution(**::test())',$pointcuts['test1class::testmethod()']->getPattern());
        $this->assertEquals('test1class::testmethod()',$pointcuts['test1class::testmethod()']->getSignatureString());
        $this->assertEquals(SignatureInterface::TYPE_METHOD,$pointcuts['test1class::testmethod()']->getSignature()->getType());
    }

    public function testAddAdviceByConfig()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestPlainOldPhpObjectAspect'=>array(
                ),
            ),
        );
    	$container = new Container($config);
    	$aop = new AopManager($container);
    	$aop->addAdviceByConfig(
    		array(
                'type' => AdviceInterface::TYPE_BEFORE,
                'pointcut' => 'execution(**::test())',
    		),
            __NAMESPACE__.'\TestPlainOldPhpObjectAspect',
            'foo1'
    	);
        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $this->assertEquals('execution(**::test())',$pointcuts[0]->getPattern());
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect::foo1()',$pointcuts[0]->getSignatureString());
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_BEFORE,$advices[0]->getType());
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect',$advices[0]->getComponentName());
        $this->assertEquals('foo1',$advices[0]->getMethod());
        $refs = $advices[0]->getPointcutSignatures();
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect::foo1()',$refs[0]);
        $events = $adviceManager->getEventManager($joinPoint);
        $joinPoint->setName(AdviceInterface::TYPE_BEFORE);
        $result = $events->notify($joinPoint,array(),$target);
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect::foo1',$result);
    }

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut "unkownPointcut::pc()" is not found: METADATA::aspects::
     */
    public function testAddAdvicePointcutRefNotfoundByConfig()
    {
        $container = new Container();
        $aop = new AopManager($container);
        $aop->addAdviceByConfig(
            array(
                'type' => AdviceInterface::TYPE_BEFORE,
                'pointcut_ref' => 'unkownPointcut::pc()',
            ),
            __NAMESPACE__.'\TestPlainOldPhpObjectAspect',
            'foo1'
        );
    }

    public function testAddAdviceByAnnotation()
    {
        $annotation = new Before();
        $annotation->value = 'execution(**::test())';
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestPlainOldPhpObjectAspect'=>array(
                ),
            ),
        );
        $container = new Container($config);
        $aop = new AopManager($container);
        $aop->addAdviceByAnnotation(
            $annotation,
            __NAMESPACE__.'\TestPlainOldPhpObjectAspect',
            'foo1'
        );
        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $this->assertEquals('execution(**::test())',$pointcuts[0]->getPattern());
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect::foo1()',$pointcuts[0]->getSignatureString());
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_BEFORE,$advices[0]->getType());
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect',$advices[0]->getComponentName());
        $this->assertEquals('foo1',$advices[0]->getMethod());
        $refs = $advices[0]->getPointcutSignatures();
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect::foo1()',$refs[0]);
        $events = $adviceManager->getEventManager($joinPoint);
        $joinPoint->setName(AdviceInterface::TYPE_BEFORE);
        $result = $events->notify($joinPoint,array(),$target);
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect::foo1',$result);
    }

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut "RindowTest\Aop\AspectCollectorTest\TestPlainOldPhpObjectAspect::pc()" is not found: error-location
     */
    public function testAddAdvicePointcutRefNotfoundByAnnotation()
    {
        $annotation = new Before();
        $annotation->pointcut = 'pc';
        $container = new Container();
        $aop = new AopManager($container);
        $aop->addAdviceByAnnotation(
            $annotation,
            __NAMESPACE__.'\TestPlainOldPhpObjectAspect',
            'foo1',
            'error-location'
        );
    }

    public function testAddAspectByConfig()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestPlainOldPhpObjectAspect'=>array(
                ),
            ),
        );
        $container = new Container($config);
        $aop = new AopManager($container);
        $aop->addAspect(
            array(
                'pointcuts' => array(
                    'testpointcut' => 'execution(**::test())',
                ),
                'advices' => array(
                    array(
                        'type' => 'before',
                        'method' => 'foo1',
                        'pointcut_ref' => 'testpointcut',
                    ),
                ),
            ),
            __NAMESPACE__.'\TestPlainOldPhpObjectAspect'
        );
        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $this->assertEquals('execution(**::test())',$pointcuts[0]->getPattern());
        $this->assertEquals('testpointcut',$pointcuts[0]->getSignatureString());
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_BEFORE,$advices[0]->getType());
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect',$advices[0]->getComponentName());
        $this->assertEquals('foo1',$advices[0]->getMethod());
        $refs = $advices[0]->getPointcutSignatures();
        $this->assertEquals('testpointcut',$refs[0]);
        $events = $adviceManager->getEventManager($joinPoint);
        $joinPoint->setName(AdviceInterface::TYPE_BEFORE);
        $result = $events->notify($joinPoint,array(),$target);
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect::foo1',$result);
    }

    public function testAddAdvisorByConfig()
    {
        $container = new Container(
            array(
                'components' => array(
                    'advisorComponent' => array(
                        'class' => __NAMESPACE__.'\TestAdvisor',
                    ),
                ),
            )
        );
        $aop = new AopManager($container);
        $aop->addAdvisor(
            array(
                'pointcut' => 'execution(**::test())',
            ),
            'advisorComponent'
        );
        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $this->assertEquals('execution(**::test())',$pointcuts[0]->getPattern());
        $this->assertEquals('advisorComponent',$pointcuts[0]->getSignatureString());
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_AROUND,$advices[0]->getType());
        $this->assertEquals('advisorComponent',$advices[0]->getComponentName());
        $this->assertEquals('invoke',$advices[0]->getMethod());
        $refs = $advices[0]->getPointcutSignatures();
        $this->assertEquals('advisorComponent',$refs[0]);
        $events = $adviceManager->getEventManager($joinPoint);
        $joinPoint->setName(AdviceInterface::TYPE_AROUND);
        $result = $events->call($joinPoint,array());
        $this->assertEquals('advisor!!',$result);
    }

    public function testSetConfig()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestPlainOldPhpObjectAspect'=>array(
                ),
            ),
        );
        $container = new Container($config);
        $aop = new AopManager($container);
        $aop->setConfig(array(
            'pointcuts' => array(
                'testpointcut' => 'execution(**::test())',
            ),
            'aspects' => array(
                'aspect1' => array(
                    'component' => __NAMESPACE__.'\TestPlainOldPhpObjectAspect',
                    'advices' => array(
                        'foo1' => array(
                            'type' => 'before',
                            'pointcut_ref' => 'testpointcut',
                        ),
                    ),
                ),
            ),
            'advisors' => array(
                'tx_advice' => array(
                    'pointcut' => 'execution('.__NAMESPACE__.'\**::set*())',
                ),
            ),
        ));
        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $this->assertEquals('execution(**::test())',$pointcuts[0]->getPattern());
        $this->assertEquals('testpointcut',$pointcuts[0]->getSignatureString());
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_BEFORE,$advices[0]->getType());
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect',$advices[0]->getComponentName());
        $this->assertEquals('foo1',$advices[0]->getMethod());
        $refs = $advices[0]->getPointcutSignatures();
        $this->assertEquals('testpointcut',$refs[0]);
        $events = $adviceManager->getEventManager($joinPoint);
        $joinPoint->setName(AdviceInterface::TYPE_BEFORE);
        $result = $events->notify($joinPoint,array(),$target);
        $this->assertEquals(__NAMESPACE__.'\TestPlainOldPhpObjectAspect::foo1',$result);
    }

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage syntax error in pointcuts configuration.
     */
    public function testAspectSyntaxErrorInPointcuts()
    {
        $container = new Container();
        $aop = new AopManager($container);
        $aop->setConfig(array(
            'pointcuts' => 'abc',
        ));
    }

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut pattern must be string in "test".
     */
    public function testAspectSyntaxErrorInPointcutPattern()
    {
        $container = new Container();
        $aop = new AopManager($container);
        $aop->setConfig(array(
            'pointcuts' => array(
                'test' => array('aaa'),
            ),
        ));
    }

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage syntax error in aspects configuration.
     */
    public function testAspectSyntaxErrorInAspects()
    {
        $container = new Container();
        $aop = new AopManager($container);
        $aop->setConfig(array(
            'aspects' => 'abc',
        ));
    }

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage syntax error in aspect "test" in configuration.
     */
    public function testAspectSyntaxErrorInAspectConfig()
    {
        $container = new Container();
        $aop = new AopManager($container);
        $aop->setConfig(array(
            'aspects' => array(
                'test' => 'error',
            ),
        ));
    }

    public function testCollectAspect()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestAnnotationAspect'=>array(
                ),
            ),
        );
        $container = new Container($config);
        $container->setAnnotationManager(new AnnotationManager());
        $aop = new AopManager($container);
        
        $annoName = 'Aspect';
        $className = __NAMESPACE__.'\\TestAnnotationAspect';
        $anno = new Aspect();
        $classRef = new ReflectionClass($className);
        $aop->collectAspect($annoName,$className,$anno,$classRef);
        
        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $this->assertEquals('execution(**::test())',$pointcuts[0]->getPattern());
        $this->assertEquals(__NAMESPACE__.'\\TestAnnotationAspect::foo1()',$pointcuts[0]->getSignatureString());
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_BEFORE,$advices[0]->getType());
        $this->assertEquals(__NAMESPACE__.'\\TestAnnotationAspect',$advices[0]->getComponentName());
        $this->assertEquals('foo1',$advices[0]->getMethod());
        $refs = $advices[0]->getPointcutSignatures();
        $this->assertEquals(__NAMESPACE__.'\\TestAnnotationAspect::foo1()',$refs[0]);
        $events = $adviceManager->getEventManager($joinPoint);
        $joinPoint->setName(AdviceInterface::TYPE_BEFORE);
        $result = $events->notify($joinPoint,array(),$target);
        $this->assertEquals(__NAMESPACE__.'\\TestAnnotationAspect::foo1',$result);
    }

    public function testAddEtcAnnotationAspectAndPointcutRef()
    {
        $container = new Container();
        $container->setAnnotationManager(new AnnotationManager());
        $aop = new AopManager($container);
        
        $annoName = 'Aspect';
        $className = __NAMESPACE__.'\\TestEtcAnnotationAspect';
        $anno = new Aspect();
        $classRef = new ReflectionClass($className);
        $aop->collectAspect($annoName,$className,$anno,$classRef);
        
        // @Pointcut(value="")
        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $this->assertEquals('execution(**::test())',$pointcuts[0]->getPattern());
        $this->assertEquals(__NAMESPACE__.'\\TestEtcAnnotationAspect::pc1()',$pointcuts[0]->getSignatureString());

        // @Before(pointcut="")  --- referrence to @Pointcut()
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_BEFORE,$advices[0]->getType());

        // @AfterReturning(value="")
        $joinPoint = new MethodJoinPoint($target,'test2');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_AFTER_RETURNING,$advices[0]->getType());

        // @AfterThrowing(value="")
        $joinPoint = new MethodJoinPoint($target,'test3');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_AFTER_THROWING,$advices[0]->getType());

        // @After(value="")
        $joinPoint = new MethodJoinPoint($target,'test4');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_AFTER,$advices[0]->getType());

        // @Around(value="")
        $joinPoint = new MethodJoinPoint($target,'test5');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_AROUND,$advices[0]->getType());
    }

    public function testScanAspectWithoutCache()
    {
    	$config = array(
    		//'annotation_manager' => true,
    		'component_paths' => array(
        		self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/Aspect' => true,
        	),
    	);
    	$container = new Container($config);
        $container->setAnnotationManager(new AnnotationManager());
    	$aop = new AopManager($container);
    	$aop->setConfig($config);
    	$container->setProxyManager($aop);
    	$container->scanComponents();

        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_BEFORE,$advices[0]->getType());
        $this->assertEquals('AcmeTest\Aop\Aspect\TestAnnotationAspect',$advices[0]->getComponentName());
    }

    public function testScanAspectWithCache()
    {
        if(!RindowTestCacheIsEnable()) {
            $this->markTestSkipped('there is no cache.');
            return;
        }
        
        $notRegister = \Rindow\Stdlib\Cache\CacheFactory::$notRegister = false;
        \Rindow\Stdlib\Cache\CacheFactory::$notRegister = false;
    	$config = array(
    		//'annotation_manager' => true,
    		'component_paths' => array(
        		self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/Aspect' => true,
        	),
    	);
        $am = new AnnotationManager();
        $anno = $am->getMetaData('');
    	$container = new Container($config);
        $container->setAnnotationManager($am);
    	$aop = new AopManager($container);
    	$aop->setConfig($config);
    	$container->setProxyManager($aop);
    	$container->scanComponents();

        CacheFactory::$caches = array();

    	$container = new Container($config);
    	$aop = new AopManager($container);
    	$aop->setConfig($config);
    	$container->setProxyManager($aop);

        $adviceManager = $aop->getAdviceManager();
        $target = new TestTarget();
        $joinPoint = new MethodJoinPoint($target,'test');
        $pointcuts = $adviceManager->getPointcutManager()->find($joinPoint);
        $this->assertEquals(1,count($pointcuts));
        $advices = $adviceManager->getAdvices($pointcuts[0]);
        $this->assertEquals(1,count($advices));
        $this->assertEquals(AdviceInterface::TYPE_BEFORE,$advices[0]->getType());
        $this->assertEquals('AcmeTest\Aop\Aspect\TestAnnotationAspect',$advices[0]->getComponentName());
        \Rindow\Stdlib\Cache\CacheFactory::$notRegister = $notRegister;
    }

    public function testNewProxyNormal()
    {
        $dummyFile = \Rindow\Stdlib\Cache\CacheFactory::$fileCachePath.'/dummy.php';
        $componentName = __NAMESPACE__.'\TestTarget';
        @unlink($dummyFile);
        $config = array('intercept_to_all'=>true);
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
                ->method('getClassName')
                ->will($this->returnValue($componentName));
        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
                ->method('getAnnotationManager');
        $interceptorBuilder = $this->createTestMock('Rindow\Aop\Support\Intercept\InterceptorBuilder');
        //$interceptorBuilder->expects($this->once())
        //        ->method('getInterceptorFileName')
        //        ->with($this->equalTo($componentName))
        //        ->will($this->returnValue($dummyFile));
        $interceptorBuilder->expects($this->once())
                ->method('buildInterceptor')
                ->with($this->equalTo($componentName),
                    $this->callback(function($mode) use ($dummyFile) {
                        if($mode!=null)
                            return false;
                        file_put_contents($dummyFile,"<?php\n");
                        return true;
                    }));
        $interceptorBuilder->expects($this->once())
                ->method('getInterceptorClassName')
                ->with($this->equalTo($componentName))
                ->will($this->returnValue(__NAMESPACE__.'\TestTargetInterceptorDummy'));
        $pointcutManager = $this->createTestMock('Rindow\Aop\Support\Pointcut\PointcutManager');
        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');
        $aop = new AopManager($container,$pointcutManager,$adviceManager,$interceptorBuilder);
        $aop->setConfig($config);
        $container->setProxyManager($aop);
        $interceptor = $aop->newProxy($container,$component);
        $this->assertEquals(__NAMESPACE__.'\TestTargetInterceptorDummy',get_class($interceptor));
    }

    public function testNewProxyAspect()
    {
        $className = __NAMESPACE__.'\TestPlainOldPhpObjectAspect';
        $componentName = 'TestAspect';
        $config = array (
            'intercept_to_all' => true,
            'aspects' => array(
                $componentName => array(
                    'advices' => array(
                        array(
                            'type' => 'before',
                            'pointcut' => 'execution(*::getArg1())',
                            'method' => 'foo1',
                        ),
                    ),
                ),
            ),
        );
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->any())
                ->method('getClassName')
                ->will($this->returnValue($className));
        $component->expects($this->any())
                ->method('getName')
                ->will($this->returnValue($componentName));
        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
                ->method('getAnnotationManager');
        $container->expects($this->once())
                ->method('instantiate')
                ->with($this->equalTo($component),
                    $this->equalTo($componentName))
                ->will($this->returnValue(new $className()));
        $interceptorBuilder = $this->createTestMock('Rindow\Aop\Support\Intercept\InterceptorBuilder');
        $interceptorBuilder->expects($this->never())
                ->method('getInterceptorFileName');
        $interceptorBuilder->expects($this->never())
                ->method('buildInterceptor');
        $interceptorBuilder->expects($this->never())
                ->method('getInterceptorClassName');

        $aop = new AopManager($container,null,null,$interceptorBuilder);
        $aop->setConfig($config);
        $container->setProxyManager($aop);
        $interceptor = $aop->newProxy($container,$component);
        $this->assertEquals($className,get_class($interceptor));
    }
}