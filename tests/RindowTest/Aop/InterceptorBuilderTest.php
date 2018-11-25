<?php
namespace RindowTest\Aop\InterceptorBuilderTest;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rindow\Aop\Support\Intercept\InterceptorBuilder;
use Rindow\Stdlib\Cache\CacheFactory;
use ArrayObject;

use AcmeTest\Aop\TestArrayCallableInterface;
use AcmeTest\Aop\HaveArrayCallableClass;

interface TestInterface
{
    public function bar(TestInterface $value=null);
}

interface TestInterface2
{}

interface TestSubInterface extends TestInterface
{}

interface TestArrayInterface
{
    public function foo(array $array);
}

class DontHaveInterfeceClass
{
    function __construct($foo = null) {
        $this->foo = $foo;
    }
    public function none(TestInterface $value=null)
    {
        # code...
    }
}

class HaveInterfaceClass implements TestInterface
{
    function __construct($foo = null) {
        $this->foo = $foo;
    }
    public function bar(TestInterface $value=null)
    {
        # code...
    }
    public function none(TestInterface $value=null)
    {
        # code...
    }
}

class HaveInterfaceClass2 implements TestInterface,TestInterface2
{
    function __construct($foo = null) {
        $this->foo = $foo;
    }
    public function bar(TestInterface $value=null)
    {
        # code...
    }
}
class HaveSubInterfaceClass implements TestSubInterface
{
    function __construct($foo = null) {
        $this->foo = $foo;
    }
    public function bar(TestInterface $value=null)
    {
        # code...
    }
}
class HaveArrayClass implements TestArrayInterface
{
    public function foo(array $array)
    {

    }
}

class HaveInterfaceClass3 implements TestInterface2
{
    function __construct(TestInterface $foo = null) {
        $this->foo = $foo;
    }
    public function bar($value='',$value2='',$value3='')
    {
        # code...
    }
}

class NotHaveConstructor
{
    public function bar($value='')
    {
        # code...
    }
}

class HaveProtectedConstructor
{
    protected function __construct(TestInterface $foo = null)
    {
        $this->foo = $foo;
    }
}

interface HaveStaticFunctionInterface
{
    public static function staticFunction(array $foo);
    public function finalFunction(array $foo);
}
class HaveStaticFunction implements HaveStaticFunctionInterface
{
    public static function staticFunction(array $foo)
    {
        return $foo;
    }
    final public function finalFunction(array $foo)
    {
        return $foo;
    }
    protected function protectedFunction(array $foo)
    {
        return $foo;
    }
    private function privateFunction(array $foo)
    {
        return $foo;
    }
}

abstract class HaveAbstractFunction
{
    abstract public function abstractFunction(array $foo);
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
}
class SubInternal extends ArrayObject
{
    public function FunctionName($value='')
    {
        # code...
    }
}

class CacheMode1
{
    public function FunctionName($value='')
    {
        # code...
    }
}

class Test extends TestCase
{
    static $RINDOW_TEST_RESOURCES;
    public static function setUpBeforeClass()
    {
        self::$RINDOW_TEST_RESOURCES = __DIR__.'/../../resources';
    }
    public function testGetClassDeclare()
    {
        $className = __NAMESPACE__.'\DontHaveInterfeceClass';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class DontHaveInterfeceClassIFInterceptor extends Interceptor 
{

    public function none(\\${testnamespace}\TestInterface \$value=NULL)
    {
        return \$this->__call('none',array(\$value));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));

        $className = __NAMESPACE__.'\HaveInterfaceClass';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveInterfaceClassIFInterceptor extends Interceptor implements \\${testnamespace}\TestInterface
{

    public function bar(\\${testnamespace}\TestInterface \$value=NULL)
    {
        return \$this->__call('bar',array(\$value));
    }
    public function none(\\${testnamespace}\TestInterface \$value=NULL)
    {
        return \$this->__call('none',array(\$value));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));

        $className = __NAMESPACE__.'\HaveInterfaceClass2';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveInterfaceClass2IFInterceptor extends Interceptor implements \\${testnamespace}\TestInterface,\\${testnamespace}\TestInterface2
{

    public function bar(\\${testnamespace}\TestInterface \$value=NULL)
    {
        return \$this->__call('bar',array(\$value));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));

        $className = __NAMESPACE__.'\HaveSubInterfaceClass';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveSubInterfaceClassIFInterceptor extends Interceptor implements \\${testnamespace}\TestSubInterface
{

    public function bar(\\${testnamespace}\TestInterface \$value=NULL)
    {
        return \$this->__call('bar',array(\$value));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));

        $className = __NAMESPACE__.'\HaveArrayClass';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveArrayClassIFInterceptor extends Interceptor implements \\${testnamespace}\TestArrayInterface
{

    public function foo(array \$array)
    {
        return \$this->__call('foo',array(\$array));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));

    }

    /**
     * @requires PHP 5.4.0
     */
    public function testGetCallableClassDeclare()
    {
        require_once self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/class_with_callable.php';
        $className = 'AcmeTest\Aop\HaveArrayCallableClass';
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace AcmeTest\Aop;
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveArrayCallableClassIFInterceptor extends Interceptor implements \AcmeTest\Aop\TestArrayCallableInterface
{

    public function foo(array \$array,callable \$callable)
    {
        return \$this->__call('foo',array(\$array,\$callable));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));
    }

    /**
     * @requires PHP 5.6.0
     */
    public function testGetVariadicParameterDeclareOnInterface()
    {
        CacheFactory::clearCache();
        require_once self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/class_on_interface_with_variadic.php';
        $className = 'AcmeTest\Aop\HaveVariadicParameterInterfaceDecleredClass';
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace AcmeTest\Aop;
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveVariadicParameterInterfaceDecleredClassIFInterceptor extends Interceptor implements \AcmeTest\Aop\HaveVariadicParameterInterface
{

    public function foo(&\$name=NULL,&...\$options)
    {
        return \$this->__call('foo',array_merge(array(&\$name),\$options));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));

        $builder->buildInterceptor($className,'interface');
        //$filename = $builder->getInterceptorFileName($className,'interface');
        //include $filename;
        $this->assertTrue(class_exists(
            'AcmeTest\Aop\HaveVariadicParameterInterfaceDecleredClassIFInterceptor'));
    }

    /**
     * @requires PHP 7.0.0
     */
    public function testGetTypeParameterDeclareOnInterface()
    {
        CacheFactory::clearCache();
        require_once self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/class_on_interface_with_type.php';
        $className = 'AcmeTest\Aop\HaveTypeParameterInterfaceDecleredClass';
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace AcmeTest\Aop;
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveTypeParameterInterfaceDecleredClassIFInterceptor extends Interceptor implements \AcmeTest\Aop\HaveTypeParameterInterface
{

    public function foo(string &\$name=NULL,int &...\$options):string
    {
        return \$this->__call('foo',array_merge(array(&\$name),\$options));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));

        $builder->buildInterceptor($className,'interface');
        //$filename = $builder->getInterceptorFileName($className,'interface');
        //include $filename;
        $this->assertTrue(class_exists(
            'AcmeTest\Aop\HaveTypeParameterInterfaceDecleredClassIFInterceptor'));
    }

    /**
     * @requires PHP 7.1.0
     */
    public function testGetAllowsNullParameterDeclareOnInterface()
    {
        CacheFactory::clearCache();
        require_once self::$RINDOW_TEST_RESOURCES.'/AcmeTest/Aop/class_on_interface_with_allows_null.php';
        $className = 'AcmeTest\Aop\HaveAllowsNullParameterInterfaceDecleredClass';
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace AcmeTest\Aop;
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveAllowsNullParameterInterfaceDecleredClassIFInterceptor extends Interceptor implements \AcmeTest\Aop\HaveAllowsNullParameterInterface
{

    public function foo(string &\$name=NULL,int &...\$options):?string
    {
        return \$this->__call('foo',array_merge(array(&\$name),\$options));
    }
}
EOD;
        $this->assertEquals($result,$builder->getInterfaceBasedInterceptorDeclare($className,'interface'));

        $builder->buildInterceptor($className,'interface');
        //$filename = $builder->getInterceptorFileName($className,'interface');
        //include $filename;
        $this->assertTrue(class_exists(
            'AcmeTest\Aop\HaveAllowsNullParameterInterfaceDecleredClassIFInterceptor'));
    }

    public function testGetFileName()
    {
        $fileCachePath = CacheFactory::$fileCachePath;
        $className = __NAMESPACE__.'\DontHaveInterfeceClass';
        $builder = new InterceptorBuilder();
        $this->assertEquals(
            $fileCachePath.
            '/Rindow/Aop/Support/Intercept/InterceptorBuilder/interceptors'.
            '/'.str_replace('\\','/',__NAMESPACE__).'/DontHaveInterfeceClassIFInterceptor.php',
            $builder->getInterceptorFileName($className,'interface'));
        $this->assertEquals(
            $fileCachePath.
            '/Rindow/Aop/Support/Intercept/InterceptorBuilder/interceptors'.
            '/'.str_replace('\\','/',__NAMESPACE__).'/DontHaveInterfeceClassIFInterceptor.php',
            $builder->getInterceptorFileName($className,'interface'));

    }

    public function testBuildAndInclude()
    {
        CacheFactory::clearCache();
        $className = __NAMESPACE__.'\HaveInterfaceClass2';
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($className,'interface');
        //$filename = $builder->getInterceptorFileName($className,'interface');
        //include $filename;
        $this->assertTrue(class_exists(
            __NAMESPACE__.'\HaveInterfaceClass2IFInterceptor'));
    }

    public function testInheritDeclareAndMultiParams()
    {
        $className = __NAMESPACE__.'\HaveInterfaceClass3';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveInterfaceClass3IHInterceptor extends \\${testnamespace}\HaveInterfaceClass3
{
    protected \$__aop_interceptor;
    public function __construct(\$container,\$component,\$adviceManager=null,\$lazy=null,\$logger=null)
    {
        \$this->__aop_interceptor = new Interceptor(
            \$container,\$component,\$adviceManager,true,\$logger,\$this,'__aop_construct');
        if(!\$lazy)
            \$this->__aop_interceptor->__aop_instantiate();
    }

    public function __aop_method___construct(\\${testnamespace}\TestInterface \$foo=NULL)
    {
        return parent::__construct(\$foo);
    }
    public function __aop_construct(\\${testnamespace}\TestInterface \$foo=NULL)
    {
        \$this->__aop_interceptor->__aop_before('__construct',array(\$foo));
        try {
            \$__aop__callback_set = \$this->__aop_interceptor->__aop_getAround('__construct',array(\$foo),'__aop_method___construct',true);
            if(\$__aop__callback_set==null) {
                \$__aop__result = parent::__construct(\$foo);
            } else {
                list(\$__aop__callback,\$__aop__funcArgs) = \$__aop__callback_set;
                \$__aop__result = \$__aop__callback[0]->call(\$__aop__funcArgs[0],null,\$__aop__funcArgs[2],\$__aop__funcArgs[3]);
            }
        } catch(\\Exception \$__aop_e) {
            \$__aop_e = \$this->__aop_interceptor->__aop_afterThrowing('__construct',array(\$foo),\$__aop_e);
            throw \$__aop_e;
        }
        \$this->__aop_interceptor->__aop_afterReturning('__construct',array(\$foo),\$__aop__result);
        return \$__aop__result;
    }
    public function __aop_method_bar(\$value='',\$value2='',\$value3='')
    {
        return parent::bar(\$value,\$value2,\$value3);
    }
    public function bar(\$value='',\$value2='',\$value3='')
    {
        \$this->__aop_interceptor->__aop_before('bar',array(\$value,\$value2,\$value3));
        try {
            \$__aop__callback_set = \$this->__aop_interceptor->__aop_getAround('bar',array(\$value,\$value2,\$value3),'__aop_method_bar',true);
            if(\$__aop__callback_set==null) {
                \$__aop__result = parent::bar(\$value,\$value2,\$value3);
            } else {
                list(\$__aop__callback,\$__aop__funcArgs) = \$__aop__callback_set;
                \$__aop__result = \$__aop__callback[0]->call(\$__aop__funcArgs[0],null,\$__aop__funcArgs[2],\$__aop__funcArgs[3]);
            }
        } catch(\\Exception \$__aop_e) {
            \$__aop_e = \$this->__aop_interceptor->__aop_afterThrowing('bar',array(\$value,\$value2,\$value3),\$__aop_e);
            throw \$__aop_e;
        }
        \$this->__aop_interceptor->__aop_afterReturning('bar',array(\$value,\$value2,\$value3),\$__aop__result);
        return \$__aop__result;
    }
}
EOD;
        //$result = str_replace(array("\r","\n"), array("",""), $result);
        $return = $builder->getInheritanceBasedInterceptorDeclare($className,null);
        //$return = str_replace(array("\r","\n"), array("",""), $return);
        $this->assertEquals($result,$return);
    }

    public function testInheritDeclareNotHaveConstructor()
    {
        $className = __NAMESPACE__.'\NotHaveConstructor';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class NotHaveConstructorIHInterceptor extends \\${testnamespace}\NotHaveConstructor
{
    protected \$__aop_interceptor;
    public function __construct(\$container,\$component,\$adviceManager=null,\$lazy=null,\$logger=null)
    {
        \$this->__aop_interceptor = new Interceptor(
            \$container,\$component,\$adviceManager,true,\$logger,\$this,null);
        if(!\$lazy)
            \$this->__aop_interceptor->__aop_instantiate();
    }

    public function __aop_method_bar(\$value='')
    {
        return parent::bar(\$value);
    }
    public function bar(\$value='')
    {
        \$this->__aop_interceptor->__aop_before('bar',array(\$value));
        try {
            \$__aop__callback_set = \$this->__aop_interceptor->__aop_getAround('bar',array(\$value),'__aop_method_bar',true);
            if(\$__aop__callback_set==null) {
                \$__aop__result = parent::bar(\$value);
            } else {
                list(\$__aop__callback,\$__aop__funcArgs) = \$__aop__callback_set;
                \$__aop__result = \$__aop__callback[0]->call(\$__aop__funcArgs[0],null,\$__aop__funcArgs[2],\$__aop__funcArgs[3]);
            }
        } catch(\\Exception \$__aop_e) {
            \$__aop_e = \$this->__aop_interceptor->__aop_afterThrowing('bar',array(\$value),\$__aop_e);
            throw \$__aop_e;
        }
        \$this->__aop_interceptor->__aop_afterReturning('bar',array(\$value),\$__aop__result);
        return \$__aop__result;
    }
}
EOD;
        //$result = str_replace(array("\r","\n"), array("",""), $result);
        $return = $builder->getInheritanceBasedInterceptorDeclare($className,'inheritance');
        //$return = str_replace(array("\r","\n"), array("",""), $return);
        $this->assertEquals($result,$return);
    }

    public function testInheritDeclareHaveProtectedConstructor()
    {
        $className = __NAMESPACE__.'\HaveProtectedConstructor';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveProtectedConstructorIHInterceptor extends \\${testnamespace}\HaveProtectedConstructor
{
    protected \$__aop_interceptor;
    public function __construct(\$container,\$component,\$adviceManager=null,\$lazy=null,\$logger=null)
    {
        \$this->__aop_interceptor = new Interceptor(
            \$container,\$component,\$adviceManager,true,\$logger,\$this,'__aop_construct');
        if(!\$lazy)
            \$this->__aop_interceptor->__aop_instantiate();
    }

    public function __aop_method___construct(\\${testnamespace}\TestInterface \$foo=NULL)
    {
        return parent::__construct(\$foo);
    }
    public function __aop_construct(\\${testnamespace}\TestInterface \$foo=NULL)
    {
        \$this->__aop_interceptor->__aop_before('__construct',array(\$foo));
        try {
            \$__aop__callback_set = \$this->__aop_interceptor->__aop_getAround('__construct',array(\$foo),'__aop_method___construct',true);
            if(\$__aop__callback_set==null) {
                \$__aop__result = parent::__construct(\$foo);
            } else {
                list(\$__aop__callback,\$__aop__funcArgs) = \$__aop__callback_set;
                \$__aop__result = \$__aop__callback[0]->call(\$__aop__funcArgs[0],null,\$__aop__funcArgs[2],\$__aop__funcArgs[3]);
            }
        } catch(\\Exception \$__aop_e) {
            \$__aop_e = \$this->__aop_interceptor->__aop_afterThrowing('__construct',array(\$foo),\$__aop_e);
            throw \$__aop_e;
        }
        \$this->__aop_interceptor->__aop_afterReturning('__construct',array(\$foo),\$__aop__result);
        return \$__aop__result;
    }
}
EOD;
        //$result = str_replace(array("\r","\n"), array("",""), $result);
        $return = $builder->getInheritanceBasedInterceptorDeclare($className,'inheritance');
        //$return = str_replace(array("\r","\n"), array("",""), $return);
        $this->assertEquals($result,$return);
    }

    public function testBuildAndIncludeInheritDeclare()
    {
        CacheFactory::clearCache();
        $className = __NAMESPACE__.'\HaveInterfaceClass3';
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($className,'inheritance');
        //$filename = $builder->getInterceptorFileName($className,'inheritance');
        //include $filename;
        $this->assertTrue(class_exists(
            __NAMESPACE__.'\HaveInterfaceClass3IHInterceptor'));
    }

    public function testStaticAndFinalWithInterfaceDeclare()
    {
        $className = __NAMESPACE__.'\HaveStaticFunction';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveStaticFunctionIFInterceptor extends Interceptor implements \\${testnamespace}\HaveStaticFunctionInterface
{

    public static function staticFunction(array \$foo)
    {
        return parent::staticFunction(\$foo);
    }
    final public function finalFunction(array \$foo)
    {
        return \$this->__call('finalFunction',array(\$foo));
    }
}
EOD;
        //$result = str_replace(array("\r","\n"), array("",""), $result);
        $return = $builder->getInterfaceBasedInterceptorDeclare($className,'interface');
        //$return = str_replace(array("\r","\n"), array("",""), $return);
        $this->assertEquals($result,$return);        
    }

    public function testStaticAndFinalWithInterfaceInclude()
    {
        CacheFactory::clearCache();
        $a = new HaveStaticFunction();
        
        $className = __NAMESPACE__.'\HaveStaticFunction';
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($className,'interface');
        //$filename = $builder->getInterceptorFileName($className,'interface');
        //include $filename;
        $this->assertTrue(class_exists(
            __NAMESPACE__.'\HaveStaticFunctionIFInterceptor'));
        
    }

    public function testStaticAndFinalWithInheritDeclare()
    {
        $className = __NAMESPACE__.'\HaveStaticFunction';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveStaticFunctionIHInterceptor extends \\${testnamespace}\HaveStaticFunction
{
    protected \$__aop_interceptor;
    public function __construct(\$container,\$component,\$adviceManager=null,\$lazy=null,\$logger=null)
    {
        \$this->__aop_interceptor = new Interceptor(
            \$container,\$component,\$adviceManager,true,\$logger,\$this,null);
        if(!\$lazy)
            \$this->__aop_interceptor->__aop_instantiate();
    }

}
EOD;
        //$result = str_replace(array("\r","\n"), array("",""), $result);
        $return = $builder->getInheritanceBasedInterceptorDeclare($className,'inheritance');
        //$return = str_replace(array("\r","\n"), array("",""), $return);
        $this->assertEquals($result,$return);
    }
    public function testStaticAndFinalWithInheritInclude()
    {
        CacheFactory::clearCache();
        
        $className = __NAMESPACE__.'\HaveStaticFunction';
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($className,'inheritance');
        //$filename = $builder->getInterceptorFileName($className,'inheritance');
        //include $filename;
        $this->assertTrue(class_exists(
            __NAMESPACE__.'\HaveStaticFunctionIHInterceptor'));
        
    }

    public function testAbsractWithInheritDeclare()
    {
        $className = __NAMESPACE__.'\HaveAbstractFunction';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveAbstractFunctionIHInterceptor extends \\${testnamespace}\HaveAbstractFunction
{
    protected \$__aop_interceptor;
    public function __construct(\$container,\$component,\$adviceManager=null,\$lazy=null,\$logger=null)
    {
        \$this->__aop_interceptor = new Interceptor(
            \$container,\$component,\$adviceManager,true,\$logger,\$this,null);
        if(!\$lazy)
            \$this->__aop_interceptor->__aop_instantiate();
    }

}
EOD;
        //$result = str_replace(array("\r","\n"), array("",""), $result);
        $return = $builder->getInheritanceBasedInterceptorDeclare($className,'inheritance');
        //$return = str_replace(array("\r","\n"), array("",""), $return);
        $this->assertEquals($result,$return);
    }

    public function testHaveReferenceParamWithInterfaceDeclare()
    {
        $className = __NAMESPACE__.'\HaveReferenceParam';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class HaveReferenceParamIFInterceptor extends Interceptor implements \\${testnamespace}\HaveReferenceParamInterface
{

    public function func(array &\$foo)
    {
        return \$this->__call('func',array(&\$foo));
    }
}
EOD;
        //$result = str_replace(array("\r","\n"), array("",""), $result);
        $return = $builder->getInterfaceBasedInterceptorDeclare($className,'interface');
        //$return = str_replace(array("\r","\n"), array("",""), $return);
        $this->assertEquals($result,$return);
    }

    public function testHaveReferenceParamWithInterfaceInclude()
    {
        CacheFactory::clearCache();
        
        $className = __NAMESPACE__.'\HaveReferenceParam';
        $builder = new InterceptorBuilder();
        $builder->buildInterceptor($className,'interface');
        //$filename = $builder->getInterceptorFileName($className,'interface');
        //include $filename;
        $this->assertTrue(class_exists(
            __NAMESPACE__.'\HaveReferenceParamIFInterceptor'));
    }
/*
    public function testSubClassOfInternalWithInterfaceDeclare()
    {
        $className = __NAMESPACE__.'\SubInternal';
        $testnamespace = __NAMESPACE__;
        $builder = new InterceptorBuilder();
        $result = <<<EOD
<?php
namespace ${testnamespace};
use Rindow\Aop\Support\Intercept\Interceptor;
class SubInternalIFInterceptor extends Interceptor implements \Countable,\Serializable,\ArrayAccess,\IteratorAggregate
{

    public function FunctionName(\$value='')
    {
        return \$this->__call('FunctionName', array(\$value));
    }
}
EOD;
        //$result = str_replace(array("\r","\n"), array("",""), $result);
        $return = $builder->getInterfaceBasedInterceptorDeclare($className,'interface');
        //$return = str_replace(array("\r","\n"), array("",""), $return);
        $this->assertEquals($result,$return);
    }
*/
    public function testCacheMode1()
    {
        CacheFactory::clearCache();
        $config = array(
            'codeCacheFactory' => 'Rindow\Stdlib\Cache\CacheFactory::getInstance',
        );
        $className = __NAMESPACE__.'\CacheMode1';
        $builder = new InterceptorBuilder();
        $builder->setConfig($config);
        $builder->buildInterceptor($className,'inheritance');
        $this->assertTrue(class_exists(
            __NAMESPACE__.'\CacheMode1IHInterceptor'));
        $filename = $builder->getInterceptorFileName($className,'inheritance');
        $this->assertFalse(file_exists($filename));
    }
}