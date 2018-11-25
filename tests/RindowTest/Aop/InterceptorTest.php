<?php
namespace RindowTest\Aop\InterceptorTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\AdviceInterface;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Support\Advice\AdviceEventCollection;
use Rindow\Aop\Support\Advice\AdviceManager;
use Rindow\Aop\Support\Intercept\InterceptorBuilder;
use Rindow\Aop\Support\Intercept\Interceptor;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\Support\JoinPoint\PropertyJoinPoint;
use Rindow\Container\Container;
use Rindow\Container\Annotation\Proxy;
use Rindow\Container\ComponentDefinition;
use Rindow\Container\ProxyManager;
use Rindow\Aop\Support\Pointcut\PointcutManager;
use Rindow\Annotation\AnnotationManager;

class TestLogger
{
    protected $log = array();
    public function debug($message)
    {
        $this->logging($message);
    }
    public function logging($message)
    {
        $this->log[] = $message;
    }
    public function getLog()
    {
        return $this->log;
    }
    public function clear()
    {
        $this->log = array();
    }
}

class TestContainer extends Container
{
    protected $logger;
    protected $baseClass;
    public function __construct($logger,$baseClass)
    {
        $this->logger = $logger;
        $this->baseClass = $baseClass;
    }
    public function instantiate(ComponentDefinition $component,$componentName=null,ComponentDefinition $declaration=null,$instance=null,$alternateConstructor=null)
    {
        $this->logger->logging('Container::instantiate('.$component->testClassName.','.gettype($componentName).','.gettype($declaration).','.(is_object($instance)?get_class($instance):gettype($instance)).','.(is_string($alternateConstructor)?$alternateConstructor:gettype($alternateConstructor)).')');
        if($instance&&$alternateConstructor) {
            $constructor = array($instance,$alternateConstructor);
            call_user_func($constructor,'foo');
        }
        return $this->baseClass;
    }
}
class TestComponentDefinition extends ComponentDefinition
{
    public $testClassName;
    protected $logger;
    protected $testName;
    public function __construct($logger,$className,$name=null)
    {
        $this->logger = $logger;
        $this->testClassName = $className;
        $this->testName = $name;
    }
    public function getClassName()
    {
        $this->logger->logging('Definition::getClassName');
        return $this->testClassName;
    }
    public function getName()
    {
        return $this->testName;
    }
}

class TestAdviceManager extends AdviceManager
{
    protected $logger;
    protected $eventManager;
    public function __construct($logger,$eventManager)
    {
        $this->logger = $logger;
        $this->eventManager = $eventManager;
    }
    public function getEventManager(JoinPointInterface $joinpoint)
    {
        $this->logger->logging('AdviceManager::getEventManager('.$joinpoint->getName().')');
        $this->logger->logging('    event::$action('.$joinpoint->getAction().')');
        $this->logger->logging('    event::$signature('.$joinpoint->getSignature()->toString().')');
        return $this->eventManager;
    }
    public function inAdvice()
    {
        $this->logger->logging('AdviceManager::inAdvice');
        return false;
    }
}

class TestEventManager
{
    public $notfound;
    protected $logger;
    public function __construct($logger)
    {
        $this->logger = $logger;
    }
    public function notify($joinpoint,$args,$instance)
    {
        $this->logger->logging('EventManager::notify('.$joinpoint->getName().','.(is_array($args)?implode(',',$args):gettype($args)).','.(is_object($instance)?get_class($instance):gettype($instance)).')');
        $params = $joinpoint->getParameters();
        $this->logger->logging('    event::$action('.$joinpoint->getAction().')');
        $this->logger->logging('    event::$signature('.$joinpoint->getSignature()->toString().')');
        if($params)
            $this->logger->logging('    event::$params('.(is_array($params)?implode(',',$params):gettype($params)).')');
    }

    public function prepareCall($joinpoint)
    {
        if($this->notfound)
            return null;
        return array('queue');
    }

    public function call($joinpoint,$args,$callback,$eventQueue=null)
    {
        $this->logger->logging('EventManager::call('.$joinpoint->getName().','.gettype($args).','.get_class($callback[0]).'::'.$callback[1].')');
        $params = $joinpoint->getParameters();
        $this->logger->logging('    event::$action('.$joinpoint->getAction().')');
        $this->logger->logging('    event::$signature('.$joinpoint->getSignature()->toString().')');
        if($params)
            $this->logger->logging('    event::$params('.(is_array($params)?implode(',',$params):gettype($params)).')');
        return call_user_func_array($callback, $params);
    }
}

class TestAopManager implements ProxyManager
{
    protected $builder;
    public function __construct($builder = null)
    {
        $this->builder = $builder;
    }

    public function newProxy(Container $container,ComponentDefinition $component)
    {
        $this->logger->logging('AopManager::newProxy');
    }
}

class TestBaseClass
{
    public $someVariable = 'someValue';
    public $logger;
    public function doSomething($foo)
    {
        $this->logger->logging('BaseClass::doSomething('.$foo.')');
        return 'someResult';
    }
}

class TestBaseClassWithConstructor
{
    public $someVariable = 'someValue';
    public $logger;
    public $foo;
    public function __construct($foo=null)
    {
        // ** No logging
        //if($foo)
        //    $this->logger->logging('BaseClass::__construct('.$foo.')');

        $this->foo = $foo;
    }
    public function doSomething($foo)
    {
        $this->logger->logging('BaseClass::doSomething('.$foo.')');
        return 'someResult';
    }
}

class BaseClass
{
    public $someVariable = 'someValue';

    public function doSomething($foo)
    {
        return 'someResult';
    }
}

class BaseClass2
{
    public $foo;

    public $someVariable = 'someValue';

    public function __construct(BaseClass $foo) {
        $this->foo = $foo;
    }
    public function getFoo()
    {
        return $this->foo;
    }
}

interface BaseClassInterface
{}
/**
* @Proxy('interface')
*/
class BaseClassWithIF implements BaseClassInterface
{
    public $someVariable = 'someValue';

    public function doSomething($foo)
    {
        return 'someResult';
    }
}

$BaseClass3Initialized = false;
class BaseClass3
{
    public $foo;

    public $someVariable = 'someValue';

    public function __construct(BaseClassInterface $foo) {
        global $BaseClass3Initialized;
        $BaseClass3Initialized = true;
        $this->foo = $foo;
    }
    public function getFoo()
    {
        return $this->foo;
    }
}

class Test extends TestCase
{
    public function setUp()
    {
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        \Rindow\Stdlib\Cache\CacheFactory::clearCache();
        usleep( RINDOW_TEST_CLEAR_CACHE_INTERVAL );
        global $BaseClass3Initialized;
        unset($BaseClass3Initialized);
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
/*
    public function testExecutionInterfaceBased1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue($componentName));

    	$instance = $this->createTestMock(__NAMESPACE__ . '\BaseClass');
    	$instance->expects($this->never())
   	        ->method('doSomething')
            ->with( $this->equalTo('foo'))
            ->will( $this->returnValue('someResult'));

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
            ->method('instantiate')
            ->with( $this->equalTo($component))
            ->will( $this->returnValue($instance));

        $events = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceEventCollection');
        $events->expects($this->exactly(3))
            ->method('notify')
            ->with(
                $this->callback(function ($joinpoint) use ($instance) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if($joinpoint->getTarget()!==$instance)
                        return false;
                    if($joinpoint->getMethod()!=='doSomething')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    if($joinpoint->getName()==AdviceInterface::TYPE_AFTER_RETURNING &&
                        $joinpoint->getReturning()!='someResult')
                        return false;
                    return true;
                }),
           	    $this->callback(function($args) {
                    if($args==array('foo'))
                        return true;
                    return false;
                }),
           	    $this->equalTo($instance)
            );
        $events->expects($this->once())
            ->method('call')
            ->with(
                $this->callback( function ($joinpoint) use ($instance) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if($joinpoint->getTarget()!==$instance)
                        return false;
                    if($joinpoint->getMethod()!=='doSomething')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    if($joinpoint->getParameters()!==array('foo'))
                        return false;
                    return true;
                }),
                $this->equalTo(null),
                $this->callback(function($callback) use ($instance) {
                    list($object,$method) = $callback;
                    if($object!==$instance)
                        return false;
                    if($method!=='doSomething')
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue('someResult'));

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');
        $adviceManager->expects($this->atLeastOnce())
            ->method('inAdvice')
            ->will( $this->returnValue(false));
        $adviceManager->expects($this->atLeastOnce())
            ->method('getEventManager')
            ->with(
                $this->callback( function ($joinpoint) use ($instance) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if($joinpoint->getTarget()!==$instance)
                        return false;
                    if($joinpoint->getSignature()->getMethod()!=='doSomething')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==__NAMESPACE__ . '\BaseClass')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue($events));

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $result = $interceptor->doSomething('foo');
        $this->assertEquals('someResult',$result);
    }
*/
    public function testExecutionInterfaceBased2()
    {
        $logger = new TestLogger();
        $baseClass = new TestBaseClass();
        $baseClass->logger = $logger;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClass');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $result = $interceptor->doSomething('foo');
        $this->assertEquals('someResult',$result);
        $result = array(
            'Definition::getClassName',
            'Container::instantiate('.__NAMESPACE__.'\TestBaseClass,NULL,NULL,NULL,NULL)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(before,foo,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(around)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::call(around,NULL,'.__NAMESPACE__.'\TestBaseClass::doSomething)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            '    event::$params(foo)',
            'BaseClass::doSomething(foo)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after-returning)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(after-returning,foo,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'AdviceManager::getEventManager(after)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(after,foo,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testExecutionInterfaceBasedWithoutFoundEvent2()
    {
        $logger = new TestLogger();
        $baseClass = new TestBaseClass();
        $baseClass->logger = $logger;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClass');
        $eventManager = new TestEventManager($logger,$baseClass);
        $eventManager->notfound = true;
        $adviceManager = new TestAdviceManager($logger,$eventManager);

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $result = $interceptor->doSomething('foo');
        $this->assertEquals('someResult',$result);
        $result = array(
            'Definition::getClassName',
            'Container::instantiate('.__NAMESPACE__.'\TestBaseClass,NULL,NULL,NULL,NULL)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(before,foo,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(around)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            // not call EventManager::call
            'BaseClass::doSomething(foo)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after-returning)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(after-returning,foo,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'AdviceManager::getEventManager(after)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(after,foo,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
        );
        $this->assertEquals($result,$logger->getLog());
    }

/*
    public function testGet1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
                ->method('getClassName')
                ->will($this->returnValue($componentName));

        $instance = $this->createTestMock(__NAMESPACE__ . '\BaseClass');

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
                ->method('instantiate')
                ->with( $this->equalTo($component))
                ->will( $this->returnValue($instance));

        $events = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceEventCollection');
        $events->expects($this->exactly(2))
            ->method('notify')
            ->with(
                $this->callback(function ($joinpoint) use ($instance) {
                    if(!($joinpoint instanceof PropertyJoinPoint))
                        return false;
                    if($joinpoint->getTarget()!==$instance)
                        return false;
                    if($joinpoint->getProperty()!=='someVariable')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    return true;
                }),
                $this->equalTo(null),
                $this->equalTo($instance)
            );
        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');
        $adviceManager->expects($this->atLeastOnce())
            ->method('inAdvice')
            ->will( $this->returnValue(false));
        $adviceManager->expects($this->atLeastOnce())
            ->method('getEventManager')
            ->with(
                $this->callback( function ($joinpoint) use ($instance) {
                    if(!($joinpoint instanceof PropertyJoinPoint))
                        return false;
                    if($joinpoint->getTarget()!==$instance)
                        return false;
                    if($joinpoint->getSignature()->getProperty()!=='someVariable')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==__NAMESPACE__ . '\BaseClass')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue($events));

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $result = $interceptor->someVariable;
        $this->assertEquals('someValue',$result);
    }
*/
    public function testGet2()
    {
        $logger = new TestLogger();
        $baseClass = new TestBaseClass();
        $baseClass->logger = $logger;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClass');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $result = $interceptor->someVariable;
        $this->assertEquals('someValue',$result);
        $result = array(
            'Definition::getClassName',
            'Container::instantiate('.__NAMESPACE__.'\TestBaseClass,NULL,NULL,NULL,NULL)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(get)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::$someVariable)',
            'EventManager::notify(before,NULL,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(get)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::$someVariable)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after)',
            '    event::$action(get)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::$someVariable)',
            'EventManager::notify(after,NULL,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(get)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::$someVariable)',
        );
        $this->assertEquals($result,$logger->getLog());
    }
/*
    public function testSet1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
                ->method('getClassName')
                ->will($this->returnValue($componentName));

        $instance = $this->createTestMock(__NAMESPACE__ . '\BaseClass');

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
                ->method('instantiate')
                ->with( $this->equalTo($component))
                ->will( $this->returnValue($instance));

        $events = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceEventCollection');
        $events->expects($this->exactly(2))
            ->method('notify')
            ->with(
                $this->callback(function ($joinpoint) use ($instance) {
                    if(!($joinpoint instanceof PropertyJoinPoint))
                        return false;
                    if($joinpoint->getTarget()!==$instance)
                        return false;
                    if($joinpoint->getProperty()!=='someVariable')
                        return false;
                    if($joinpoint->getValue()!=='newValue')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    return true;
                }),
                $this->equalTo(null),
                $this->equalTo($instance)
            );
        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');
        $adviceManager->expects($this->atLeastOnce())
            ->method('inAdvice')
            ->will( $this->returnValue(false));
        $adviceManager->expects($this->atLeastOnce())
            ->method('getEventManager')
            ->with(
                $this->callback( function ($joinpoint) use ($instance) {
                    if(!($joinpoint instanceof PropertyJoinPoint))
                        return false;
                    if($joinpoint->getTarget()!==$instance)
                        return false;
                    if($joinpoint->getSignature()->getProperty()!=='someVariable')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==__NAMESPACE__ . '\BaseClass')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue($events));

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $this->assertEquals('someValue',$instance->someVariable);
        $interceptor->someVariable = 'newValue';
        $this->assertEquals('newValue',$instance->someVariable);
    }
*/
    public function testSet2()
    {
        $logger = new TestLogger();
        $baseClass = new TestBaseClass();
        $baseClass->logger = $logger;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClass');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $this->assertEquals('someValue',$baseClass->someVariable);
        $interceptor->someVariable = 'newValue';
        $this->assertEquals('newValue',$baseClass->someVariable);

        $result = array(
            'Definition::getClassName',
            'Container::instantiate('.__NAMESPACE__.'\TestBaseClass,NULL,NULL,NULL,NULL)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(set)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::$someVariable)',
            'EventManager::notify(before,NULL,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(set)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::$someVariable)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after)',
            '    event::$action(set)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::$someVariable)',
            'EventManager::notify(after,NULL,'.__NAMESPACE__.'\TestBaseClass)',
            '    event::$action(set)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::$someVariable)',
        );
        $this->assertEquals($result,$logger->getLog());
    }
/*
    public function testIsset1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
                ->method('getClassName')
                ->will($this->returnValue($componentName));

        $instance = $this->createTestMock(__NAMESPACE__ . '\BaseClass');

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
                ->method('instantiate')
                ->with( $this->equalTo($component))
                ->will( $this->returnValue($instance));

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $res = isset($interceptor->someVariable);
        $this->assertTrue($res);
    }
*/
    public function testIsset2()
    {
        $logger = new TestLogger();
        $baseClass = new TestBaseClass();
        $baseClass->logger = $logger;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClass');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $res = isset($interceptor->someVariable);
        $this->assertTrue($res);
        $result = array(
            'Definition::getClassName',
            'Container::instantiate('.__NAMESPACE__.'\TestBaseClass,NULL,NULL,NULL,NULL)',
        );
        $this->assertEquals($result,$logger->getLog());
    }
/*
    public function testUnset1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
                ->method('getClassName')
                ->will($this->returnValue($componentName));

        $instance = $this->createTestMock(__NAMESPACE__ . '\BaseClass');

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
                ->method('instantiate')
                ->with( $this->equalTo($component))
                ->will( $this->returnValue($instance));

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $this->assertTrue(isset($instance->someVariable));
        unset($interceptor->someVariable);
        $this->assertFalse(isset($instance->someVariable));
    }
*/
    public function testUnset2()
    {
        $logger = new TestLogger();
        $baseClass = new TestBaseClass();
        $baseClass->logger = $logger;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClass');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);

        $interceptor = new Interceptor($container,$component,$adviceManager);

        $this->assertTrue(isset($baseClass->someVariable));
        unset($interceptor->someVariable);
        $this->assertFalse(isset($baseClass->someVariable));

        $result = array(
            'Definition::getClassName',
            'Container::instantiate('.__NAMESPACE__.'\TestBaseClass,NULL,NULL,NULL,NULL)',
        );
        $this->assertEquals($result,$logger->getLog());
    }
/*
    public function testExecutionInheritBasedNormal1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
                ->method('getClassName')
                ->will($this->returnValue($componentName));

        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($componentName,'inheritance');
        //exit;
        //include_once $builder->getInterceptorFileName($componentName,'inheritance');

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
                ->method('instantiate')
                ->with( $this->equalTo($component),
                        $this->equalTo(null),
                        $this->equalTo(null),
                        $this->callback(function($instance) {
                            if(get_class($instance)==__NAMESPACE__ . '\BaseClassIHInterceptor')
                                return true;
                            return false;
                        })
                        );

        $events = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceEventCollection');
        $events->expects($this->exactly(3))
            ->method('notify')
            ->with(
                $this->callback(function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClassIHInterceptor')
                        return false;
                    if($joinpoint->getMethod()!=='doSomething')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    if($joinpoint->getName()==AdviceInterface::TYPE_AFTER_RETURNING &&
                        $joinpoint->getReturning()!='someResult')
                        return false;
                    return true;
                }),
                $this->callback(function($args) {
                    if($args==array('foo'))
                        return true;
                    return false;
                }),
                $this->callback(function($target) use ($componentName) {
                    if(get_class($target)!==__NAMESPACE__ . '\BaseClassIHInterceptor')
                        return false;
                    return true;
                })
            );
        $events->expects($this->once())
            ->method('call')
            ->with(
                $this->callback( function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClassIHInterceptor')
                        return false;
                    if($joinpoint->getMethod()!=='doSomething')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    return true;
                }),
                $this->equalTo(null),
                $this->callback(function($callback) use ($componentName) {
                    list($object,$method) = $callback;
                    if(get_class($object)!==__NAMESPACE__ . '\BaseClassIHInterceptor')
                        return false;
                    if($method!=='__aop_method_doSomething')
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue('someResult'));

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');
        $adviceManager->expects($this->atLeastOnce())
            ->method('inAdvice')
            ->will( $this->returnValue(false));
        $adviceManager->expects($this->atLeastOnce())
            ->method('getEventManager')
            ->with(
                $this->callback( function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClassIHInterceptor')
                        return false;
                    if($joinpoint->getSignature()->getMethod()!=='doSomething')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==$componentName)
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue($events));

        $interceptorName = $builder->getInterceptorClassName($componentName,'inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager);

        $result = $interceptor->doSomething('foo');
        $this->assertEquals('someResult',$result);
    }
*/
    public function testExecutionInheritBasedNormal2()
    {
        $logger = new TestLogger();
        //$baseClass = new TestBaseClass($logger);
        $baseClass = null;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClass','testComponent');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor(__NAMESPACE__.'\TestBaseClass','inheritance');

        $interceptorName = $builder->getInterceptorClassName(__NAMESPACE__.'\TestBaseClass','inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager);
        $interceptor->logger = $logger;

        $result = $interceptor->doSomething('foo');
        $this->assertEquals('someResult',$result);
        $result = array(
            'Definition::getClassName',
            'Container::instantiate('.__NAMESPACE__.'\TestBaseClass,NULL,NULL,'.__NAMESPACE__.'\TestBaseClassIHInterceptor,NULL)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(before,foo,'.__NAMESPACE__.'\TestBaseClassIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(around)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::call(around,NULL,'.__NAMESPACE__.'\TestBaseClassIHInterceptor::__aop_method_doSomething)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            '    event::$params(foo)',
            'BaseClass::doSomething(foo)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after-returning)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(after-returning,foo,'.__NAMESPACE__.'\TestBaseClassIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'AdviceManager::getEventManager(after)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
            'EventManager::notify(after,foo,'.__NAMESPACE__.'\TestBaseClassIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClass::doSomething())',
        );
        $this->assertEquals($result,$logger->getLog());
    }

/*
    public function testExecutionInheritBasedWithConstructor1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass2';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
                ->method('getClassName')
                ->will($this->returnValue($componentName));

        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($componentName,'inheritance');
        //exit;
        //include_once $builder->getInterceptorFileName($componentName,'inheritance');
        //echo $builder->getInterceptorDeclare($componentName);

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
            ->method('instantiate')
            ->with(
                $this->equalTo($component),
                $this->equalTo(null),
                $this->equalTo(null),
                $this->callback(function($instance) {
                    if(get_class($instance)==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return true;
                    return false;
                }),
                $this->equalTo('__aop_construct')
            );
        $events = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceEventCollection');
        $events->expects($this->exactly(3))
            ->method('notify')
            ->with(
                $this->callback(function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getMethod()!=='getFoo')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    if($joinpoint->getName()==AdviceInterface::TYPE_AFTER_RETURNING &&
                        $joinpoint->getReturning()!='someResult')
                        return false;
                    return true;
                }),
                $this->callback(function($args) {
                    if($args==array())
                        return true;
                    return false;
                }),
                $this->callback(function($target) use ($componentName) {
                    if(get_class($target)!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    return true;
                })
            );
        $events->expects($this->once())
            ->method('call')
            ->with(
                $this->callback( function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getMethod()!=='getFoo')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    return true;
                }),
                $this->equalTo(null),
                $this->callback(function($callback) use ($componentName) {
                    list($object,$method) = $callback;
                    if(get_class($object)!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($method!=='__aop_method_getFoo')
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue('someResult'));

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');
        $adviceManager->expects($this->atLeastOnce())
            ->method('inAdvice')
            ->will( $this->returnValue(false));
        $adviceManager->expects($this->atLeastOnce())
            ->method('getEventManager')
            ->with(
                $this->callback( function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getSignature()->getMethod()!=='getFoo')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==$componentName)
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue($events));

        $interceptorName = $builder->getInterceptorClassName($componentName,'inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager);
        $interceptor->getFoo();
    }
*/
    public function testExecutionInheritBasedWithConstructor2()
    {
        $logger = new TestLogger();
        //$baseClass = new TestBaseClassWithConstructor($logger);
        $baseClass = null;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClassWithConstructor','testComponent');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor(__NAMESPACE__.'\TestBaseClassWithConstructor','inheritance');

        $interceptorName = $builder->getInterceptorClassName(__NAMESPACE__.'\TestBaseClassWithConstructor','inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager);
        $interceptor->logger = $logger;

        $result = $interceptor->doSomething('foo');
        $this->assertEquals('someResult',$result);
        $result = array(
            'Definition::getClassName',
            'Container::instantiate('.__NAMESPACE__.'\TestBaseClassWithConstructor,NULL,NULL,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor,__aop_construct)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(before,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(around)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::call(around,NULL,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor::__aop_method___construct)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            '    event::$params(foo)',
            ////'BaseClass::__construct(foo)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after-returning)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(after-returning,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'AdviceManager::getEventManager(after)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(after,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',

            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'EventManager::notify(before,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(around)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'EventManager::call(around,NULL,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor::__aop_method_doSomething)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            '    event::$params(foo)',
            'BaseClass::doSomething(foo)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after-returning)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'EventManager::notify(after-returning,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'AdviceManager::getEventManager(after)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'EventManager::notify(after,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testCreateInheritBasedNonLazy()
    {
        $componentName = __NAMESPACE__ . '\BaseClass2';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');
        $component->expects($this->once())
                ->method('getClassName')
                ->will($this->returnValue($componentName));

        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($componentName,'inheritance');
        //exit;
        //include_once $builder->getInterceptorFileName($componentName,'inheritance');
        //echo $builder->getInterceptorDeclare($componentName);

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->once())
                ->method('instantiate')
                ->with( $this->equalTo($component),
                        $this->equalTo(null),
                        $this->equalTo(null),
                        $this->callback(function($instance) {
                            if(get_class($instance)==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                                return true;
                            return false;
                        }),
                        $this->equalTo('__aop_construct')
                        );

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');

        $interceptorName = $builder->getInterceptorClassName($componentName,'inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager);
    }
/*
    public function testCreateInheritBasedLazy1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass2';
        $component = $this->createTestMock('Rindow\Container\ComponentDefinition');

        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($componentName,'inheritance');
        //exit;
        //include_once $builder->getInterceptorFileName($componentName,'inheritance');
        //echo $builder->getInterceptorDeclare($componentName);

        $container = $this->createTestMock('Rindow\Container\Container');
        $container->expects($this->never())
                ->method('instantiate');

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');

        $interceptorName = $builder->getInterceptorClassName($componentName,'inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager,true);
    }
*/
    public function testCreateInheritBasedLazy2()
    {
        $logger = new TestLogger();
        //$baseClass = new TestBaseClass($logger);
        $baseClass = null;
        $container = new TestContainer($logger,$baseClass);
        $component = new TestComponentDefinition($logger,__NAMESPACE__.'\TestBaseClass','testComponent');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor(__NAMESPACE__.'\TestBaseClass','inheritance');

        $interceptorName = $builder->getInterceptorClassName(__NAMESPACE__.'\TestBaseClass','inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager,true);
        $interceptor->logger = $logger;

        $result = array(
            'Definition::getClassName',
        );
        $this->assertEquals($result,$logger->getLog());
    }
/*
    public function testInheritBasedInterceptorWithContainerInstantiate1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass2';
        $config = array(
            'auto_proxy' => 'component',
            'components' => array(
                $componentName => array(
                ),
                __NAMESPACE__.'\BaseClass' => array(
                    'proxy' => 'disable',
                ),
            ),
        );
        $container = new Container($config);
        $aop = $this->createTestMock('Rindow\Aop\AopManager',null,array($container));
        $container->setProxyManager($aop);
        $component = $container->getComponentManager()->newComponent($componentName);

        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($componentName,'inheritance');
        //include_once $builder->getInterceptorFileName($componentName,'inheritance');

        $events = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceEventCollection');
        $events->expects($this->exactly(3))
            ->method('notify')
            ->with(
                $this->callback(function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==__NAMESPACE__.'\BaseClass2')
                        return false;
                    if($joinpoint->getMethod()!=='__construct')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    return true;
                }),
                $this->callback(function($args) {
                    if(count($args)!=1)
                        return false;
                    if(get_class($args[0])!=__NAMESPACE__.'\BaseClass')
                        return false;
                    return true;
                }),
                $this->callback(function($target) use ($componentName) {
                    if(get_class($target)!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    return true;
                })
            );
        $events->expects($this->once())
            ->method('call')
            ->with(
                $this->callback( function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getMethod()!=='__construct')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    $params = $joinpoint->getParameters();
                    if(count($params)!=1)
                        return false;
                    if(get_class($params[0])!=__NAMESPACE__.'\BaseClass')
                        return false;
                    return true;
                }),
                $this->equalTo(null),
                $this->callback(function($callback) use ($componentName) {
                    list($object,$method) = $callback;
                    if(get_class($object)!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($method!=='__aop_method___construct')
                        return false;
                    $object->foo = new BaseClass();
                    return true;
                })
            )
            ->will( $this->returnValue(null));

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');
        $adviceManager->expects($this->atLeastOnce())
            ->method('inAdvice')
            ->will( $this->returnValue(false));
        $adviceManager->expects($this->atLeastOnce())
            ->method('getEventManager')
            ->with(
                $this->callback( function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getSignature()->getMethod()!=='__construct')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==$componentName)
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue($events));

        $interceptorName = $builder->getInterceptorClassName($componentName,'inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager);
        $this->assertEquals(__NAMESPACE__ . '\BaseClass2IHInterceptor',get_class($interceptor));
        $this->assertEquals(__NAMESPACE__ . '\BaseClass',get_class($interceptor->foo));
    }
*/
    public function testInheritBasedInterceptorWithContainerInstantiate2()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestBaseClassWithConstructor' => array(
                    'constructor_args' => array(
                        'foo' =>array('value'=>'foo'),
                    ),
                ),
            ),
        );
        $container = new Container($config);
        $logger = new TestLogger();
        $aop = new TestAopManager($logger);
        $container->setProxyManager($aop);
        $baseClass = null;
        $component = $container->getComponentManager()->getComponent(__NAMESPACE__.'\TestBaseClassWithConstructor');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor(__NAMESPACE__.'\TestBaseClassWithConstructor','inheritance');

        $interceptorName = $builder->getInterceptorClassName(__NAMESPACE__.'\TestBaseClassWithConstructor','inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager);
        $interceptor->logger = $logger;
        $this->assertEquals('foo',$interceptor->foo);
        $result = array(
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(before,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(around)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::call(around,NULL,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor::__aop_method___construct)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            '    event::$params(foo)',

            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after-returning)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(after-returning,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'AdviceManager::getEventManager(after)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(after,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
        );
        $this->assertEquals($result,$logger->getLog());
    }
/*
    public function testLazyInheritBasedInterceptorWithContainerInstantiate1()
    {
        $componentName = __NAMESPACE__ . '\BaseClass2';
        $config = array (
            'auto_proxy' => 'component',
            'components' => array(
                $componentName => array(
                ),
                __NAMESPACE__.'\BaseClass' => array(
                    'proxy' => 'disable',
                ),
            ),
        );
        $container = new Container($config);
        $aop = $this->createTestMock('Rindow\Aop\AopManager',null,array($container));
        $container->setProxyManager($aop);
        $component = $container->getComponentManager()->newComponent($componentName);

        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($componentName,'inheritance');
        //include_once $builder->getInterceptorFileName($componentName,'inheritance');

        $events = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceEventCollection');
        $events->expects($this->exactly(6))
            ->method('notify')
            ->with(
                $this->callback(function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==__NAMESPACE__.'\BaseClass2')
                        return false;
                    if($joinpoint->getMethod()!=='__construct' &&
                        $joinpoint->getMethod()!=='getFoo')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                        $joinpoint->getName()!==AdviceInterface::TYPE_AFTER)
                        return false;
                    return true;
                }),
                $this->callback(function($args) {
                    if(count($args)!=1 && count($args)!=0)
                        return false;
                    if(count($args)==1) {
                        if(get_class($args[0])!=__NAMESPACE__.'\BaseClass')
                            return false;
                    }
                    return true;
                }),
                $this->callback(function($target) use ($componentName) {
                    if(get_class($target)!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    return true;
                })
            );
        $events->expects($this->exactly(2))
            ->method('call')
            ->with(
                $this->callback( function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getMethod()!=='__construct' &&
                        $joinpoint->getMethod()!=='getFoo')
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    $params = $joinpoint->getParameters();
                    if(count($params)!=1 && count($params)!=0)
                        return false;
                    if(count($params)==1) {
                        if(get_class($params[0])!=__NAMESPACE__.'\BaseClass')
                            return false;
                    }
                    return true;
                }),
                $this->equalTo(null),
                $this->callback(function($callback) use ($componentName) {
                    list($object,$method) = $callback;
                    if(get_class($object)!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($method!=='__aop_method___construct' &&
                        $method!=='__aop_method_getFoo' )
                        return false;
                    if($method=='__aop_method___construct')
                        $object->foo = new BaseClass();
                    return true;
                })
            )
            ->will( $this->returnValue(null));

        $adviceManager = $this->createTestMock('Rindow\Aop\Support\Advice\AdviceManager');
        $adviceManager->expects($this->atLeastOnce())
            ->method('inAdvice')
            ->will( $this->returnValue(false));
        $adviceManager->expects($this->atLeastOnce())
            ->method('getEventManager')
            ->with(
                $this->callback( function ($joinpoint) use ($componentName) {
                    if(!($joinpoint instanceof MethodJoinPoint))
                        return false;
                    if(get_class($joinpoint->getTarget())!==__NAMESPACE__ . '\BaseClass2IHInterceptor')
                        return false;
                    if($joinpoint->getSignature()->getMethod()!=='__construct' &&
                        $joinpoint->getSignature()->getMethod()!=='getFoo')
                        return false;
                    if($joinpoint->getSignature()->getClassName()!==$componentName)
                        return false;
                    if($joinpoint->getName()!==AdviceInterface::TYPE_BEFORE &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER_RETURNING &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AFTER &&
                       $joinpoint->getName()!==AdviceInterface::TYPE_AROUND)
                        return false;
                    return true;
                })
            )
            ->will( $this->returnValue($events));

        $interceptorName = $builder->getInterceptorClassName($componentName,'inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager,true);
        $this->assertEquals(__NAMESPACE__ . '\BaseClass2IHInterceptor',get_class($interceptor));
        $this->assertNull($interceptor->foo);
        $interceptor->getFoo();
        $this->assertEquals(__NAMESPACE__ . '\BaseClass',get_class($interceptor->foo));
    }
*/
    public function testLazyInheritBasedInterceptorWithContainerInstantiate2()
    {
        $config = array(
            'components' => array(
                __NAMESPACE__.'\TestBaseClassWithConstructor' => array(
                    'constructor_args' => array(
                        'foo' =>array('value'=>'foo'),
                    ),
                    'mode' => 'lazy',
                ),
            ),
        );
        $container = new Container($config);
        $logger = new TestLogger();
        $aop = new TestAopManager($logger);
        $container->setProxyManager($aop);
        $baseClass = null;
        $component = $container->getComponentManager()->getComponent(__NAMESPACE__.'\TestBaseClassWithConstructor');
        $eventManager = new TestEventManager($logger,$baseClass);
        $adviceManager = new TestAdviceManager($logger,$eventManager);
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor(__NAMESPACE__.'\TestBaseClassWithConstructor','inheritance');

        $interceptorName = $builder->getInterceptorClassName(__NAMESPACE__.'\TestBaseClassWithConstructor','inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager,$lazy=true);
        $interceptor->logger = $logger;
        $this->assertNull($interceptor->foo);
        $logger->logging('==created==');

        $interceptor->doSomething('foo');

        //$this->assertEquals('foo',$interceptor->foo);
        $result = array(
            '==created==',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(before,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(around)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::call(around,NULL,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor::__aop_method___construct)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            '    event::$params(foo)',

            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after-returning)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(after-returning,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'AdviceManager::getEventManager(after)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',
            'EventManager::notify(after,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::__construct())',

            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(before)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'EventManager::notify(before,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(around)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'EventManager::call(around,NULL,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor::__aop_method_doSomething)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            '    event::$params(foo)',
            'BaseClass::doSomething(foo)',
            'AdviceManager::inAdvice',
            'AdviceManager::getEventManager(after-returning)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'EventManager::notify(after-returning,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'AdviceManager::getEventManager(after)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
            'EventManager::notify(after,foo,'.__NAMESPACE__.'\TestBaseClassWithConstructorIHInterceptor)',
            '    event::$action(execution)',
            '    event::$signature('.__NAMESPACE__.'\TestBaseClassWithConstructor::doSomething())',
        );
        $this->assertEquals($result,$logger->getLog());
    }


    public function testInheritBasedInterceptorWithAutoProxy()
    {
        $componentName = __NAMESPACE__ . '\BaseClass2';
        $config = array (
            'auto_proxy' => 'all',
            'components' => array(
                $componentName => array(
                ),
                __NAMESPACE__.'\BaseClass' => array(
                ),
            ),
        );
        $container = new Container($config);
        $aop = $this->createTestMock('Rindow\Aop\AopManager',null,array($container));
        $aop->setConfig(array('intercept_to_all'=>true));
        $container->setProxyManager($aop);
        $component = $container->getComponentManager()->newComponent($componentName);

        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($componentName,'inheritance');
        //include_once $builder->getInterceptorFileName($componentName,'inheritance');

        $adviceManager = new AdviceManager(new PointcutManager(),$container);

        $interceptorName = $builder->getInterceptorClassName($componentName,'inheritance');
        $interceptor = new $interceptorName($container,$component,$adviceManager,true);
        $this->assertEquals(__NAMESPACE__ . '\BaseClass2IHInterceptor',get_class($interceptor));
        $this->assertNull($interceptor->foo);
        $interceptor->getFoo();
        $this->assertEquals(__NAMESPACE__ . '\BaseClassIHInterceptor',get_class($interceptor->foo));
    }

    public function testInterfaceBasedInterceptorWithAutoProxy()
    {
        $componentName = __NAMESPACE__ . '\BaseClass3';
        $config = array (
            //'annotation_manager' => true,
            'auto_proxy' => 'all',
            'components' => array(
                $componentName => array(
                    'constructor_args' => array(
                        'foo' => array('ref'=>__NAMESPACE__ . '\BaseClassWithIF'),
                    ),
                ),
                __NAMESPACE__.'\BaseClassWithIF' => array(
                ),
            ),
        );
        $container = new Container($config);
        $container->setAnnotationManager(new AnnotationManager());
        $aop = $this->createTestMock('Rindow\Aop\AopManager',null,array($container));
        $aop->setConfig(array('intercept_to_all' => true));
        $container->setProxyManager($aop);
        $component = $container->getComponentManager()->getComponent($componentName);

        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($componentName,'interface');
        //include_once $builder->getInterceptorFileName($componentName,'interface');

        $adviceManager = new AdviceManager(new PointcutManager(),$container);

        $interceptorName = $builder->getInterceptorClassName($componentName,'interface');
        $interceptor = new $interceptorName($container,$component,$adviceManager,true);
        $this->assertEquals(__NAMESPACE__ . '\BaseClass3IFInterceptor',get_class($interceptor));
        global $BaseClass3Initialized;
        $this->assertFalse($BaseClass3Initialized);
        $this->assertEquals(__NAMESPACE__ . '\BaseClassWithIFIFInterceptor',get_class($interceptor->foo));
        $this->assertTrue($BaseClass3Initialized);
    }
}