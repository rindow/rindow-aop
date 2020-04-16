<?php
namespace RindowTest\Aop\AopTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\AspectInterface;
use Rindow\Aop\AopManager;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\ProceedingJoinPointInterface;
use Rindow\Container\Container;
use Rindow\Container\ModuleManager;

use Rindow\Aop\Anotation\Aspect;
use Rindow\Aop\Anotation\Pointcut;
use Rindow\Aop\Anotation\Before;
use Rindow\Aop\Anotation\After;
use Rindow\Aop\Anotation\AfterReturning;
use Rindow\Aop\Anotation\AfterThrowing;
use Rindow\Aop\Anotation\Around;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;

interface Param0Interface
{
}
interface Param1Interface
{
}

class Logger
{
    protected $log;

    public function log($message)
    {
        $this->log[] = $message;
    }
    public function getLog()
    {
        return $this->log;
    }
    public function reset()
    {
        $this->log=null;
    }
}

class Param0 implements Param0Interface
{
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getArg1($argVar)
    {
        $this->log('getArg1@Param0::'.$argVar);
        return $this;
    }
    public function log($message)
    {
        $this->logger->log($message);
    }
    public function getThis()
    {
        return $this;
    }
}

class Exception extends \Exception
{}

class SignalException extends \Exception
{}

class AlternateException extends \Exception
{}

class Param1
{
    public function __construct(Param0Interface $arg1,Logger $logger)
    {
        $this->arg1 = $arg1;
        $this->logger = $logger;
    }

    public function getArg1($argVar)
    {
        $this->log('getArg1::'.$argVar);
        if($argVar=='throw')
            throw new Exception('hoge');
        if($argVar=='signal')
            throw new SignalException('signal');
        return $this->arg1;
    }

    public function getArg2($argVar)
    {
        $this->log('getArg2::'.$argVar);
    }

    public function getParam0Arg1($argVar)
    {
        $this->log('getParam0Arg1::'.$argVar);
        if($argVar=='throw')
            throw new Exception('hoge');
        return $this->arg1->getArg1($argVar);
    }

    public function log($message)
    {
        $this->logger->log($message);
    }
}

class PlainAspect
{
    protected $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function beforeAdvice(JoinPointInterface $joinPoint)
    {
        $args = $joinPoint->getParameters();
        $message = 'Before call MESSAGE!::';
        if(isset($args[0]))
            $message .= '(arg='.$args[0].')';
        $this->logger->log($message);
    }

    public function aroundAdvice(ProceedingJoinPointInterface $invocation)
    {
        $arguments = $invocation->getParameters();
        $message = 'AROUND(frontend) call MESSAGE!::';
        if(isset($arguments[0]))
            $message .= '(arg='.$arguments[0].')';
        $this->logger->log($message);

        $returnValue = $invocation->proceed();

        $message = 'AROUND(backend) call MESSAGE!::';
        if(isset($arguments[0]))
            $message .= '(arg='.$arguments[0].')';
        if(isset($returnValue))
            $message .= '(ret='.get_class($returnValue).')';
        $this->logger->log($message);
        return $returnValue;
    }

    public function afterAdvice(JoinPointInterface $joinPoint)
    {
        $args = $joinPoint->getParameters();
        $message = 'After call MESSAGE!::';
        if(isset($args[0]))
            $message .= '(arg='.$args[0].')';
        if($joinPoint->getReturning())
            $message .= '(ret='.get_class($joinPoint->getReturning()).')';
        $this->logger->log($message);
    }

    public function afterReturningAdvice(JoinPointInterface $joinPoint)
    {
        $args = $joinPoint->getParameters();
        $message = 'After-returning call MESSAGE!::';
        if(isset($args[0]))
            $message .= '(arg='.$args[0].')';
        if($joinPoint->getReturning())
            $message .= '(ret='.get_class($joinPoint->getReturning()).')';
        $this->logger->log($message);
    }

    public function afterThrowingAdvice(JoinPointInterface $joinPoint)
    {
        $args = $joinPoint->getParameters();
        $message = 'After-throwing call MESSAGE!::';
        if(isset($args[0]))
            $message .= '(arg='.$args[0].')';
        if($joinPoint->getThrowing())
            $message .= '(throw='.get_class($joinPoint->getThrowing()).')';
        if($joinPoint->getThrowing() instanceof SignalException) {
            $message .= '(changeException)';
            $e = new AlternateException('catch signal');
            $joinPoint->setThrowing($e);
        }
        $this->logger->log($message);
    }

    public function labelAdvice(JoinPointInterface $joinPoint)
    {
        $args = $joinPoint->getParameters();
        $this->logger->log('Invoke call MESSAGE!::'.$args['arg1']);
    }
    public function aroundLabelAdvice(ProceedingJoinPointInterface $invocation)
    {
        $arguments = $invocation->getParameters();
        $message = 'AROUND(frontend) call MESSAGE!::';
        if(isset($arguments[0]))
            $message .= '(arg='.$arguments[0].')';
        $this->logger->log($message);

        $returnValue = $invocation->proceed();

        $message = 'AROUND(backend) call MESSAGE!::';
        if(isset($arguments[0]))
            $message .= '(arg='.$arguments[0].')';
        if(isset($returnValue))
            $message .= '(ret='.$returnValue.')';
        $this->logger->log($message);
        return $returnValue;
    }
}

class FooFactoryMode
{
    function __construct($logger) {
        $this->logger = $logger;
        $this->logger->log('Live:Foo');
    }
    public function getThis()
    {
        return $this;
    }
}

class FooFactoryModeFactory
{
    public static function factory($sm)
    {
        return new FooFactoryMode($sm->get(__NAMESPACE__.'\Logger'));
    }
}

class FooNeedConfig
{
    protected $config;
    function __construct(array $config) {
        $this->config = $config;
    }
    public function getConfig()
    {
        return $this->config;
    }
}

interface HaveStaticInterface
{
    public static function func($var);
}
class HaveStatic implements HaveStaticInterface
{
    public static function func($var)
    {
        return $var;
    }
}

interface HaveReferenceParamInterface
{
    public function func(array &$foo);
}
class HaveReferenceParam implements HaveReferenceParamInterface
{
    public function func(array &$foo)
    {
        $foo[] = 'foo';
    }

    public function funcOutOfInterface(array &$foo)
    {
        $foo[] = 'foo';
    }
}


class TestInvokeOnInheritance
{
    protected $logger;
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
    public function __invoke($arg1)
    {
        $this->logger->log('invoked');
        return 'return';
    }
}

interface TestInvokeInterface
{
    public function __invoke($arg1);
}

class TestInvokeOnInterface implements TestInvokeInterface
{
    protected $logger;
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
    public function __invoke($arg1)
    {
        $this->logger->log('invoked interfacebased');
        return 'return';
    }
}

class TestAspectTestInvoke
{
    protected $logger;
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
    public function advice(ProceedingJoinPointInterface $joinPoint)
    {
        $this->logger->log('start advice');
        $return = $joinPoint->proceed();
        $this->logger->log('end advice');
        return $return;
    }
}

class TestAspectOptions
{
    protected $logger;
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
    public function method1()
    {
        $this->logger->log('method1!');
    }
    public function method2()
    {
        $this->logger->log('method2!');
    }
}

class TestAspect2
{
    protected $logger;
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
    public function before($joinPoint)
    {
        $this->logger->log('before!');
    }
}

class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../resources';
    }

    public function setUp()
    {
    }

    public function getPatterns($aop)
    {
        $pointcuts = $aop->getAdviceManager()->getPointcutManager()->getPointcuts();
        $patterns = array();
        foreach ($pointcuts as $pointcut) {
            $advices = $aop->getAdviceManager()->getAdvices($pointcut);
            if($advices) {
                foreach ($advices as $advice) {
                    $patterns[] = $advice->getType().'::'.$pointcut->getPattern();
                }
            }
        }
        return $patterns;
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

    public function getPlainAspectConfig()
    {
        return array(
            'pointcuts' => array(
                'pc1' => 'execution(**::getArg1()) || execution(**::getParam0Arg1())',
                'pc2' => 'execution(**::getArg1())',
                'pc3' => 'execution(**::getParam0Arg1())',
                'pc4' => 'execution(testpoint:)',
            ),
            'advices' => array(
                'beforeAdvice' => array(
                    'type' => 'before',
                    'pointcut_ref' => 'pc1',
                ),
                'afterReturningAdvice' => array(
                    'type' => 'after-returning',
                    'pointcut_ref' => 'pc1',
                ),
                'afterThrowingAdvice' => array(
                    'type' => 'after-throwing',
                    'pointcut_ref' => 'pc2',
                ),
                'afterAdvice' => array(
                    'type' => 'after',
                    'pointcut_ref' => 'pc2',
                ),
                'aroundAdvice' => array(
                    'type' => 'around',
                    'pointcut_ref' => 'pc1',
                ),
                'labelAdvice' => array(
                    'type' => 'before',
                    'pointcut_ref' => 'pc4',
                ),
                'aroundLabelAdvice' => array(
                    'type' => 'around',
                    'pointcut_ref' => 'pc4',
                ),
            ),
        );
    }

    public function testExecutionNormal()
    {
        $config = array (
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\PlainAspect' =>
                        $this->getPlainAspectConfig()
                ),
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Param1' => array(
                        'constructor_args' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                        ),
                        //'proxy' => 'interface',
                    ),
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\PlainAspect' => array(
                    ),
                    __NAMESPACE__.'\Param0' => array(
                        'proxy' => 'disable',
                    ),
                ),
            )
        );
        $container = new Container($config['container']);
        $aop = new AopManager($container);
        $aop->setConfig($config['aop']);
        $container->setProxyManager($aop);
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $result = array(
            'before::execution(**::getArg1()) || execution(**::getParam0Arg1())',
            'after-returning::execution(**::getArg1()) || execution(**::getParam0Arg1())',
            'around::execution(**::getArg1()) || execution(**::getParam0Arg1())',
            'after-throwing::execution(**::getArg1())',
            'after::execution(**::getArg1())',
            'before::execution(testpoint:)',
            'around::execution(testpoint:)',
        );

        $patterns = $this->getPatterns($aop);
        $this->assertEquals($result,$patterns);

        $i1 = $container->get(__NAMESPACE__.'\Param1');
        $this->assertNull($logger->getLog());

        $a = $i1->getArg1('A');
        $result = array(
            'Before call MESSAGE!::(arg=A)',
            'AROUND(frontend) call MESSAGE!::(arg=A)',
            'getArg1::A',
            'AROUND(backend) call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
            'After-returning call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
            'After call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testExecutionAroundNest()
    {
        $config = array (
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\PlainAspect' =>
                        $this->getPlainAspectConfig()
                ),
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Param0' => array(
                        'proxy' => 'interface',
                    ),
                    __NAMESPACE__.'\Param1' => array(
                        'constructor_args' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                        ),
                        //'proxy' => 'interface',
                    ),
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\PlainAspect' =>array(
                    ),
                ),
            ),
        );
        $container = new Container($config['container']);
        $aop = new AopManager($container);
        $aop->setConfig($config['aop']);
        $container->setProxyManager($aop);
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $i1 = $container->get(__NAMESPACE__.'\Param1');
        $this->assertNull($logger->getLog());

        $a = $i1->getParam0Arg1('A');
        $result = array(
            'Before call MESSAGE!::(arg=A)',
            'AROUND(frontend) call MESSAGE!::(arg=A)',
            'getParam0Arg1::A',
            'Before call MESSAGE!::(arg=A)',
            'AROUND(frontend) call MESSAGE!::(arg=A)',
            'getArg1@Param0::A',
            'AROUND(backend) call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
            'After-returning call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
            'After call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
            'AROUND(backend) call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
            'After-returning call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testAfterThrowing()
    {
        $config = array (
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\PlainAspect' =>
                        $this->getPlainAspectConfig()
                ),
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Param1' => array(
                        'constructor_args' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                        ),
                        //'proxy' => 'interface',
                    ),
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\PlainAspect' =>array(
                    ),
                    __NAMESPACE__.'\Param0' => array(
                    ),
                ),
            ),
        );
        $container = new Container($config['container']);
        $aop = new AopManager($container);
        $aop->setConfig($config['aop']);
        $container->setProxyManager($aop);
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $i1 = $container->get(__NAMESPACE__.'\Param1');
        $this->assertNull($logger->getLog());

        $cached = false;
        try {
            $a = $i1->getArg1('throw');
        } catch(Exception $e) {
            $this->assertEquals(__NAMESPACE__.'\Exception',get_class($e));
            $cached = true;
        }
        $this->assertTrue($cached);

        $cached = false;
        try {
            $a = $i1->getArg1('signal');
        } catch(AlternateException $e) {
            $this->assertEquals(__NAMESPACE__.'\AlternateException',get_class($e));
            $cached = true;
        }
        $this->assertTrue($cached);

        $result = array(
            'Before call MESSAGE!::(arg=throw)',
            'AROUND(frontend) call MESSAGE!::(arg=throw)',
            'getArg1::throw',
            'After-throwing call MESSAGE!::(arg=throw)(throw='.__NAMESPACE__.'\Exception)',
            'After call MESSAGE!::(arg=throw)',
            'Before call MESSAGE!::(arg=signal)',
            'AROUND(frontend) call MESSAGE!::(arg=signal)',
            'getArg1::signal',
            'After-throwing call MESSAGE!::(arg=signal)(throw='.__NAMESPACE__.'\SignalException)(changeException)',
            'After call MESSAGE!::(arg=signal)',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testNotifyLabel()
    {
        $config = array (
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\PlainAspect' =>
                        $this->getPlainAspectConfig()
                ),
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\PlainAspect' =>array(
                    ),
                ),
            ),
        );
        $container = new Container($config['container']);
        $aop = new AopManager($container);
        $aop->setConfig($config['aop']);
        $container->setProxyManager($aop);
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $args = array('arg1'=>'A');
        $target = new \stdClass();
        $aop->notify('testpoint',$args,$target);

        $result = array(
            'Invoke call MESSAGE!::A',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testCallLabel()
    {
        $config = array (
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\PlainAspect' =>
                        $this->getPlainAspectConfig()
                ),
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\PlainAspect' =>array(
                    ),
                ),
            ),
        );
        $container = new Container($config['container']);
        $aop = new AopManager($container);
        $aop->setConfig($config['aop']);
        $container->setProxyManager($aop);
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $args = array('test');
        $target = new \stdClass();
        $terminator = function ($foo) { return $foo; };
        $this->assertEquals('test',$aop->call('testpoint',$args,$target,$terminator));
        $result = array(
            'AROUND(frontend) call MESSAGE!::(arg=test)',
            'AROUND(backend) call MESSAGE!::(arg=test)(ret=test)',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testWithModuleManager()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'aspects' => array(
                    __NAMESPACE__.'\PlainAspect' =>
                        $this->getPlainAspectConfig()
                ),
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\Param1' => array(
                        'constructor_args' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                        ),
                        //'proxy' => 'interface',
                    ),
                    __NAMESPACE__.'\PlainAspect' =>array(
                    ),
                    __NAMESPACE__.'\Param0' => array(
                        'proxy' => 'disable',
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $i1 = $container->get(__NAMESPACE__.'\Param1');
        $this->assertNull($logger->getLog());

        $a = $i1->getArg1('A');
        $result = array(
            'Before call MESSAGE!::(arg=A)',
            'AROUND(frontend) call MESSAGE!::(arg=A)',
            'getArg1::A',
            'AROUND(backend) call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
            'After-returning call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
            'After call MESSAGE!::(arg=A)(ret='.__NAMESPACE__.'\Param0)',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testAnnotationAspectWithModuleManager()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'annotation_manager' => true,
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
            ),
            'container' => array(
                'component_paths' => array(
                    self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/AopTest' => true,
                ),
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\Param1' => array(
                        'constructor_args' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                        ),
                        //'proxy' => 'interface',
                    ),
                    'AcmeTest\Aop\AopTest\AnnotatedAspect' => array(
                        'constructor_args' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\Logger'),
                        ),
                    ),
                    __NAMESPACE__.'\Param0' => array(
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $aop = $container->getProxyManager();
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $i1 = $container->get(__NAMESPACE__.'\Param1');
        $this->assertNull($logger->getLog());

        $a = $i1->getArg1('A');
        $result = array(
            'Before call MESSAGE!::(arg=A)',
            'Before call MESSAGE! at Advice2::(arg=A)',
            'getArg1::A',
        );
        $this->assertEquals($result,$logger->getLog());

        $logger->reset();
        $a = $i1->getArg2('A');
        $result = array(
            'Before call MESSAGE! at Advice2::(arg=A)',
            'getArg2::A',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testArrayConfigAspectWithModuleManager()
    {
        if(!class_exists('AcmeTest\Aop\AopTest\AnnotatedAspect'))
            include_once self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/AopTest/TestAnnotationAspect.php';
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                //'annotation_manager' => true,
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
                'pointcuts' => array(
                    'pc1' =>
                        'execution(**::getArg1()) || execution(**::getParam0Arg1())',
                    'pc2' =>
                        'execution(**::getArg2())',
                ),
                'aspects' => array(
                    'AcmeTest\Aop\AopTest\AnnotatedAspect' => array(
                        'advices'=>array(
                            'beforeAdvice' => array(
                                'type' => 'before',
                                'pointcut_ref' => 'pc1',
                            ),
                            'beforeAdvice2' => array(
                                'type' => 'before',
                                'pointcut_ref' => array(
                                    'pc1'=>true,
                                    'pc2'=>true,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'container' => array(
                //'component_paths' => array(
                //    self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/AopTest' => true,
                //),
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\Param1' => array(
                        'constructor_args' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                        ),
                        //'proxy' => 'interface',
                    ),
                    'AcmeTest\Aop\AopTest\AnnotatedAspect' => array(
                        'constructor_args' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\Logger'),
                        ),
                    ),
                    __NAMESPACE__.'\Param0' => array(
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $aop = $container->getProxyManager();
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $i1 = $container->get(__NAMESPACE__.'\Param1');
        $this->assertNull($logger->getLog());

        $a = $i1->getArg1('A');
        $result = array(
            'Before call MESSAGE!::(arg=A)',
            'Before call MESSAGE! at Advice2::(arg=A)',
            'getArg1::A',
        );
        $this->assertEquals($result,$logger->getLog());

        $logger->reset();
        $a = $i1->getArg2('A');
        $result = array(
            'Before call MESSAGE! at Advice2::(arg=A)',
            'getArg2::A',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testSwitchFactoryComponentMode()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Param0' => array(
                    ),
                    __NAMESPACE__.'\FooFactoryMode' => array(
                        'class' => __NAMESPACE__.'\FooFactoryMode',
                        'factory' => __NAMESPACE__.'\FooFactoryModeFactory::factory',
                    ),
                    __NAMESPACE__.'\Logger' => array(
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();

        $interceptor = $container->get(__NAMESPACE__.'\Param0');
        $instance = $interceptor->getThis();
        $this->assertEquals(__NAMESPACE__.'\Param0IHInterceptor',get_class($interceptor));
        $this->assertEquals(__NAMESPACE__.'\Param0IHInterceptor',get_class($instance));

        $interceptor = $container->get(__NAMESPACE__.'\FooFactoryMode');
        $instance = $interceptor->getThis();
        $this->assertEquals(__NAMESPACE__.'\FooFactoryModeIFInterceptor',get_class($interceptor));
        $this->assertEquals(__NAMESPACE__.'\FooFactoryMode',get_class($instance));
    }

    public function testSwitchComponentFactoryModeWithLazy()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\FooFactoryMode' => array(
                        'class' => __NAMESPACE__.'\FooFactoryMode',
                        'factory' => __NAMESPACE__.'\FooFactoryModeFactory::factory',
                        'lazy' => true,
                    ),
                    __NAMESPACE__.'\Logger' => array(
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();

        $logger = $container->get(__NAMESPACE__.'\Logger');
        $interceptor = $container->get(__NAMESPACE__.'\FooFactoryMode');
        $this->assertNull($logger->getLog());

        $instance = $interceptor->getThis();
        $this->assertEquals(array('Live:Foo'),$logger->getLog());
    }

    public function testDisableInterceptorEvent()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'disable_interceptor_event' => true,
                'aspects' => array(
                    __NAMESPACE__.'\PlainAspect' =>
                        $this->getPlainAspectConfig()
                ),
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\Param1' => array(
                        'constructor_args' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                        ),
                    ),
                    __NAMESPACE__.'\PlainAspect' =>array(
                    ),
                    __NAMESPACE__.'\Param0' => array(
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $i1 = $container->get(__NAMESPACE__.'\Param1');
        $this->assertNull($logger->getLog());

        $a = $i1->getArg1('A');
        $result = array(
            'getArg1::A',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testAddInterceptTargets()
    {
        $configCacheFactory = $this->getConfigCacheFactory();
        $config = array(
            'aop' => array(
                'intercept_to' => array(
                    'NamespaceFoo1' => true,
                ),
            ),
            'container' => array(
                'components' => array(
                    'NamespaceFoo1\Bar1' => array(
                        'proxy' => 'disable',
                    ),
                ),
            ),
        );
        $container = new Container($config['container'],null,null,null,null,$configCacheFactory);
        $aop = new AopManager($container,null,null,null,$configCacheFactory);
        $aop->setConfig($config['aop']);
        $container->setProxyManager($aop);

        $this->assertEquals(array('NamespaceFoo1'=>true),$aop->getInterceptTargets());
        $this->assertTrue($aop->isInterceptTarget('NamespaceFoo1\Bar1'));
        $aop->addInterceptTarget('NamespaceFoo1\Bar1');
        $this->assertEquals(array('NamespaceFoo1'=>true),$aop->getInterceptTargets());

        $this->assertFalse($aop->isInterceptTarget('NamespaceFoo2\Bar2'));
        $aop->addInterceptTarget('NamespaceFoo2\Bar2');
        $this->assertEquals(array('NamespaceFoo1'=>true,'NamespaceFoo2\Bar2'=>true),$aop->getInterceptTargets());
        $this->assertTrue($aop->isInterceptTarget('NamespaceFoo2\Bar2'));

        $aop->addInterceptTarget('NamespaceFoo2');
        $this->assertEquals(array('NamespaceFoo1'=>true,'NamespaceFoo2\Bar2'=>true,'NamespaceFoo2'=>true),$aop->getInterceptTargets());

        $aop->completedScan();

        // cached data

        $container = new Container($config['container'],null,null,null,null,$configCacheFactory);
        $aop = new AopManager($container,null,null,null,$configCacheFactory);
        $aop->setConfig($config['aop']);
        $container->setProxyManager($aop);
        $this->assertTrue($aop->isInterceptTarget('NamespaceFoo2\Bar2'));
    }

    public function testReduceIntercept()
    {
        $config = array(
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to' => array(
                    __NAMESPACE__.'\Param0' => true,
                ),
                'aspects' => array(
                    __NAMESPACE__.'\PlainAspect' => array(
                        'advices' => array(
                            'beforeAdvice' => array(
                                'type' => 'before',
                                'pointcut' => 'execution(**::get*())',
                            ),
                        ),
                    ),
                ),
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                    ),
                    __NAMESPACE__.'\Param1' => array(
                        'constructor_args' => array(
                            'arg1' => array('ref'=>__NAMESPACE__.'\Param0'),
                        ),
                    ),
                    __NAMESPACE__.'\Param0' => array(
                    ),
                    __NAMESPACE__.'\PlainAspect' =>array(
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $i1 = $container->get(__NAMESPACE__.'\Param1');
        $this->assertNull($logger->getLog());

        $a = $i1->getParam0Arg1('A');
        $result = array(
            'getParam0Arg1::A',
            'Before call MESSAGE!::(arg=A)',
            'getArg1@Param0::A',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testDisableInterceptorEventWithLazy()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'disable_interceptor_event' => true,
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\FooFactoryMode' => array(
                        'class' => __NAMESPACE__.'\FooFactoryMode',
                        'factory' => __NAMESPACE__.'\FooFactoryModeFactory::factory',
                        'lazy' => true,
                    ),
                    __NAMESPACE__.'\Logger' =>array(
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();

        $logger = $container->get(__NAMESPACE__.'\Logger');
        $interceptor = $container->get(__NAMESPACE__.'\FooFactoryMode');
        $this->assertNull($logger->getLog());

        $instance = $interceptor->getThis();
        $this->assertEquals(array('Live:Foo'),$logger->getLog());
    }

    public function testInjectConfigByFactoryComponentMode()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\FooNeedConfig' => array(
                        'constructor_args' => array(
                            'config' => array('ref'=>__NAMESPACE__.'\FooNeedConfig\Config'),
                        ),
                    ),
                    __NAMESPACE__.'\FooNeedConfig\Config' => array(
                        'class' => 'array',
                        'factory' => 'Rindow\Container\ConfigurationFactory::factory',
                        'factory_args' => array('config'=>'something'),
                    ),
                ),
            ),
            'something' => array(
                'foo' => 'bar',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();

        $foo = $container->get(__NAMESPACE__.'\FooNeedConfig');
        $this->assertEquals(__NAMESPACE__.'\FooNeedConfigIHInterceptor',get_class($foo));
        $this->assertEquals(array('foo'=>'bar'),$foo->getConfig());

        $fooConfig = $container->get(__NAMESPACE__.'\FooNeedConfig\Config');
        $this->assertEquals(array('foo'=>'bar'),$fooConfig);
    }

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage class name is not specifed for interceptor in component "RindowTest\Aop\AopTest\FooNeedConfig\Config".
     */
    public function testClassNameIsNotSpecifiedInjectConfigByFactoryComponentMode()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\FooNeedConfig' => array(
                        'constructor_args' => array(
                            'config' => array('ref'=>__NAMESPACE__.'\FooNeedConfig\Config'),
                        ),
                    ),
                    __NAMESPACE__.'\FooNeedConfig\Config' => array(
                        'factory' => 'Rindow\Container\ConfigurationFactory::factory',
                        'factory_args' => array('config'=>'something'),
                    ),
                ),
            ),
            'something' => array(
                'foo' => 'bar',
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();

        $foo = $container->get(__NAMESPACE__.'\FooNeedConfig');
    }

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage static method is not supported to call a interceptor in "RindowTest\Aop\AopTest\HaveStaticIFInterceptor".
     */
    public function testClassHaveStaticFunction()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\HaveStatic' => array(
                        'proxy' => 'interface',
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();

        $i1 = $container->get(__NAMESPACE__.'\HaveStatic');
        $this->assertEquals(__NAMESPACE__.'\HaveStaticIFInterceptor',get_class($i1));
        $className = get_class($i1);
        $className::func('hoge');
    }

    public function testHaveReferenceParamWithInterfaceBasedInterceptor()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\HaveReferenceParam' => array(
                        'proxy' => 'interface',
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $i1 = $container->get(__NAMESPACE__.'\HaveReferenceParam');

        $array = array('A');
        $i1->func($array);
        $this->assertEquals(array('A','foo'),$array);
        $i1->funcOutOfInterface($array);
        $this->assertEquals(array('A','foo','foo'),$array);
    }

    public function testInvokeWithInheritanceBasedInterceptor()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
                'aspects' => array(
                    __NAMESPACE__.'\TestAspectTestInvoke' => array(
                        'pointcuts' => array(
                            'pc1' => 'execution(**::__invoke())',
                        ),
                        'advices' => array(
                            'advice' => array(
                                'type' => 'around',
                                'pointcut_ref' => 'pc1',
                            ),
                        ),
                    ),
                ),
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                    ),
                    __NAMESPACE__.'\TestInvokeOnInheritance' => array(
                        'properties' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\Logger')
                        ),
                        'proxy' => 'inheritance',
                    ),
                    __NAMESPACE__.'\TestAspectTestInvoke' => array(
                        'properties' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\Logger')
                        ),
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $o = $container->get(__NAMESPACE__.'\TestInvokeOnInheritance');
        $this->assertEquals('return',call_user_func($o,'test'));
        $logger = $container->get(__NAMESPACE__.'\Logger');
        $result = array(
            'start advice',
            'invoked',
            'end advice',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testInvokeWithInterfaceBasedInterceptor()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\Aop\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
                'aspects' => array(
                    __NAMESPACE__.'\TestAspectTestInvoke' => array(
                        'pointcuts' => array(
                            'pc1' => 'execution(**::__invoke())',
                        ),
                        'advices' => array(
                            'advice' => array(
                                'type' => 'around',
                                'pointcut_ref' => 'pc1',
                            ),
                        ),
                    ),
                ),
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\Logger' => array(
                    ),
                    __NAMESPACE__.'\TestInvokeOnInterface' => array(
                        'properties' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\Logger')
                        ),
                        'proxy' => 'interface',
                    ),
                    __NAMESPACE__.'\TestAspectTestInvoke' => array(
                        'properties' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\Logger')
                        ),
                    ),
                ),
            ),
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $o = $container->get(__NAMESPACE__.'\TestInvokeOnInterface');
        $this->assertEquals('return',call_user_func($o,'test'));
        $logger = $container->get(__NAMESPACE__.'\Logger');
        $result = array(
            'start advice',
            'invoked interfacebased',
            'end advice',
        );
        $this->assertEquals($result,$logger->getLog());
    }

    public function testAspectOptionsWithAspect()
    {
        $config = array (
            'module_manager' => array(
                'modules' => array(
                    'Rindow\\Aop\\Module' => true,
                ),
                'enableCache' => false,
            ),
            'aop' => array(
                'intercept_to_all' => true,
                'aspects' => array(
                    __NAMESPACE__.'\TestAspect2' => array(
                        'pointcuts' => array(
                            'pc1' => 'execution(**::method1())',
                        ),
                        'advices' => array(
                            'before' => array(
                                'type' => 'before',
                                'pointcut_ref' => array('pc1'=>true),
                            ),
                        ),
                    ),
                ),
                'aspectOptions' => array(
                    __NAMESPACE__.'\TestAspect2' => array(
                        'pointcuts' => array(
                            'pc2' => 'execution(**::method2())',
                        ),
                        'advices' => array(
                            'before' => array(
                                'pointcut_ref' => array('pc2'=>true),
                            ),
                        ),
                    ),
                ),
            ),
            'container' => array(
                'components' => array(
                    __NAMESPACE__.'\TestAspectOptions' => array(
                        'properties' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\Logger'),
                        ),
                    ),
                    __NAMESPACE__.'\TestAspect2' => array(
                        'properties' => array(
                            'logger' => array('ref'=>__NAMESPACE__.'\Logger'),
                        ),
                        'proxy' => 'disable',
                    ),
                    __NAMESPACE__.'\Logger' => array(
                        'proxy' => 'disable',
                    ),
                ),
            )
        );
        $moduleManager = new ModuleManager($config);
        $container = $moduleManager->getServiceLocator();
        $o = $container->get(__NAMESPACE__.'\TestAspectOptions');
        $logger = $container->get(__NAMESPACE__.'\Logger');

        $o->method1();
        $o->method2();

        $result = array(
            'before!',
            'method1!',
            'before!',
            'method2!',
        );
        $this->assertEquals($result,$logger->getLog());
    }
}
