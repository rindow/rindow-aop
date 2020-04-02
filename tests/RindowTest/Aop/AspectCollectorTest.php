<?php
namespace RindowTest\Aop\AspectCollectorTest;

// There is The Aspect Collector in AopManager.
// So the target of this test is AopManager
//

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rindow\Container\Container;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;
use Rindow\Annotation\AnnotationManager;

use Rindow\Aop\AopManager;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\JoinPointInterface;
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
use Rindow\Aop\Support\Intercept\InterceptorBuilder;
use Rindow\Aop\Support\Pointcut\PointcutManager;
use Rindow\Aop\Support\Pointcut\Pointcut as PointcutObject;
use Rindow\Aop\Support\Advice\AdviceManager;
use Rindow\Aop\Support\Advice\AdviceDefinition;
use Rindow\Container\ComponentDefinition;
use Rindow\Container\ComponentDefinitionManager;
use Rindow\Container\DeclarationManager;
use Rindow\Container\InstanceManager;

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

class TestLogger
{
    public $log = array();
    public function logging($text)
    {
        $this->log[] = $text;
    }
    public function getLog()
    {
        return $this->log;
    }
    public function str($value)
    {
        if($value===null) {
            $strValue = 'null';
        } elseif(is_bool($value)) {
            $strValue = $value ? 'true':'false';
        } elseif(is_object($value)) {
            $strValue = get_class($value);
        } elseif(is_array($value)) {
            $strValue = '['.implode(',',$value).']';
        } else {
            $strValue = $value;
        }
        return $strValue;
    }
}

class TestComponentDefinition extends ComponentDefinition
{
    public $logger;
    public $returnClassName;
    public $returnName;

    public function __construct($classOrConfig=null,$annotationManager=null) {}
    public function export() {}
    public function addPropertyWithReference($name,$ref) {}
    public function addPropertyWithValue($name,$value) {}
    public function addConstructorArgWithReference($name,$ref) {}
    public function addConstructorArgWithValue($name,$value) {}
    public function addMethodDeclaration($methodName) {}
    public function addMethodDeclarationForce($methodName,$paramName,$reference=null) {}

    public function getClassName()
    {
        $this->logger->logging('ComponentDefinition::getClassName()');
        return $this->returnClassName;
    }
    public function getName()
    {
        $this->logger->logging('ComponentDefinition::getName()');
        return $this->returnName;
    }

}
class TestContainer extends Container
{
    public $logger;
    public $returnAnnotationManager;
    public $returnInstantiate;

    public function __construct(
        array $config = null,
        ComponentDefinitionManager $componentManager=null,
        DeclarationManager $declarationManager=null,
        InstanceManager $instanceManager=null,
        $cachePath=null,
        $configCacheFactory=null) {}

    public function setConfig($config) {}
    public function setAnnotationManager($annotationManager) {}
    public function scanComponents() {}
    public function get($componentName) {}
    public function has($componentName) {}
    public function setInstance($componentName,$instance) {}

    public function getAnnotationManager()
    {
        $this->logger->logging('Container::getAnnotationManager()');
        return $this->returnAnnotationManager;
    }

    public function instantiate(ComponentDefinition $component,$componentName=null,
        ComponentDefinition $declaration=null,$instance=null,$alternateConstructor=null)
    {
        $this->logger->logging(
            'Container::instantiate(component='.get_class($component).
                '('.$component->returnClassName.
            '),componentName='.$componentName.
            ',declaration='.$this->logger->str($declaration).
            ',instance='.$this->logger->str($instance).
            ',alternateConstructor='.$this->logger->str($alternateConstructor).')');
        return $this->returnInstantiate;
    }
}
class TestInterceptorBuilder extends InterceptorBuilder
{
    public $dummyFile;
    public $logger;
    public $className;

    public function __construct($filePath=null,$configCacheFactory=null,$config=null) {}
    public function getCodeStore() {}
    public function getInterceptorDeclare($className,$mode=null) {}
    public function getInterfaceBasedInterceptorDeclare($className,$mode) {}
    public function getInterfaceMethod($methodRef) {}
    public function getInterfaceStaticMethod($methodRef) {}
    public function getInheritanceBasedInterceptorDeclare($className,$mode) {}
    public function getMethodDescribe($methodRef) {}

    public function buildInterceptor($className,$mode)
    {
        $this->logger->logging('InterceptorBuilder::buildInterceptor'.
                                '(className='.$className.',mode='.$this->logger->str($mode).')');
        if($mode!=null)
            return;
        file_put_contents($this->dummyFile,"<?php\n");
    }
    public function getInterceptorClassName($className,$mode)
    {
        $this->logger->logging('InterceptorBuilder::getInterceptorClassName'.
                                '(className='.$className.',mode='.$this->logger->str($mode).')');
        return $this->className;
    }
}

class TestPointcutManager extends PointcutManager
{
    public function __construct($configCacheFactory=null) {}
    public function getParser() {}
    public function register(PointcutObject $pointcut) {}
    public function existsInSignatureString($signature) {}
    public function save() {}
    public function load() {}
    public function getPointcuts() {}
    public function find($joinpoint) {}
    public function generate(SignatureInterface $signature,$pattern,$location=null) {}
}

class TestAdviceManager extends AdviceManager
{
    public function __construct(PointcutManager $pointcutManager=null, $serviceLocator=null, $configCacheFactory=null) {}
    public function getRepository() {}
    public function register(AdviceDefinition $advice) {}
    public function getAdvices(PointcutObject $pointcut) {}
    public function getEventManager(JoinPointInterface $joinpoint) {}

}
class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static $backupCacheMode;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../resources';
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
        $configCacheFactory = $this->getConfigCacheFactory();

    	$config = array(
    		//'annotation_manager' => true,
    		'component_paths' => array(
        		self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/Aspect' => true,
        	),
    	);
        $am = new AnnotationManager($configCacheFactory);
        $anno = $am->getMetaData('');
    	$container = new Container($config,null,null,null,null,$configCacheFactory);
        $container->setAnnotationManager($am);
    	$aop = new AopManager($container,null,null,null,$configCacheFactory);
    	$aop->setConfig($config);
    	$container->setProxyManager($aop);
    	$container->scanComponents();

        // ---- cached --------------------------

        $container = new Container($config,null,null,null,null,$configCacheFactory);
        $aop = new AopManager($container,null,null,null,$configCacheFactory);
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
    }

    public function testNewProxyNormal()
    {
        $dummyFile = RINDOW_TEST_CACHE.'/dummy.php';
        $componentName = __NAMESPACE__.'\TestTarget';
        @unlink($dummyFile);
        $config = array('intercept_to_all'=>true);
        $logger = new TestLogger();
        $component = new TestComponentDefinition();
        $component->logger = $logger;
        $component->returnClassName = $componentName;
        $container = new TestContainer();
        $container->logger = $logger;
        $interceptorBuilder = new TestInterceptorBuilder();
        $interceptorBuilder->logger = $logger;
        $interceptorBuilder->dummyFile = $dummyFile;
        $interceptorBuilder->className = __NAMESPACE__.'\TestTargetInterceptorDummy';
        //$interceptorBuilder->expects($this->once())
        //        ->method('getInterceptorFileName')
        //        ->with($this->equalTo($componentName))
        //        ->will($this->returnValue($dummyFile));
        $pointcutManager = new TestPointcutManager();
        $adviceManager = new TestAdviceManager();
        $aop = new AopManager($container,$pointcutManager,$adviceManager,$interceptorBuilder);
        $aop->setConfig($config);
        $container->setProxyManager($aop);
        $interceptor = $aop->newProxy($container,$component);
        $this->assertEquals(__NAMESPACE__.'\TestTargetInterceptorDummy',get_class($interceptor));
        $this->assertEquals(array(
            'Container::getAnnotationManager()',
            'ComponentDefinition::getName()',
            'ComponentDefinition::getClassName()',
            'InterceptorBuilder::buildInterceptor(className='.$componentName.',mode=null)',
            'InterceptorBuilder::getInterceptorClassName(className='.$componentName.',mode=null)',
            'ComponentDefinition::getName()',
        ),$logger->getLog());
    }

    public function testNewProxyAspect()
    {
        $className = __NAMESPACE__.'\TestPlainOldPhpObjectAspect';
        $componentName = 'TestAspect';
        $config = array(
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
        $logger = new TestLogger();
        $component = new TestComponentDefinition();
        $component->logger = $logger;
        $component->returnClassName = $className;
        $component->returnName = $componentName;
        $container = new TestContainer();
        $container->logger = $logger;
        $container->returnInstantiate = new $className();
        //$interceptorBuilder->expects($this->never())
        //        ->method('getInterceptorFileName');
        $interceptorBuilder = new TestInterceptorBuilder();

        $aop = new AopManager($container,null,null,$interceptorBuilder);
        $aop->setConfig($config);
        $container->setProxyManager($aop);
        $interceptor = $aop->newProxy($container,$component);
        $this->assertEquals($className,get_class($interceptor));
        $this->assertEquals(array(
            'Container::getAnnotationManager()',
            'ComponentDefinition::getName()',
            'ComponentDefinition::getName()',
            'Container::instantiate(component='.__NAMESPACE__.'\TestComponentDefinition('.$className.'),'.
            'componentName=TestAspect,declaration=null,instance=null,alternateConstructor=null)',
        ),$logger->getLog());
    }
}
