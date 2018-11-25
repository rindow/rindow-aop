<?php
namespace Rindow\Aop\Support\Intercept;

use ReflectionClass;
use ReflectionException;
use Rindow\Stdlib\Cache\CacheFactory;
use Rindow\Aop\Exception;

class InterceptorBuilder
{
    const MODE_INTERFACE   = 'interface';
    const MODE_INHERITANCE = 'inheritance';

    protected $config;
    protected $strage;

    public function __construct($config = null)
    {
        if($config)
            $this->setConfig($config);
    }

    public function setConfig($config)
    {
        $this->config = $config;
        if(isset($config['codeCacheFactory'])) {
            $factory = $config['codeCacheFactory'];
            if(isset($config['codeCacheFactoryArgs']))
                $factoryArgs = $config['codeCacheFactoryArgs'];
            else
                $factoryArgs = array(__CLASS__);
            $this->strage = call_user_func_array($factory,$factoryArgs);
        }
    }

    public function getInterceptorDeclare($className,$mode=null)
    {
        if($mode==null || $mode===true || $mode===self::MODE_INHERITANCE)
            return $this->getInheritanceBasedInterceptorDeclare($className,$mode);
        else if($mode===self::MODE_INTERFACE)
            return $this->getInterfaceBasedInterceptorDeclare($className,$mode);
        else
            throw new Exception\DomainException('unknown proxy mode "'.$mode.'" to create a proxy for: '.$className);
    }

    protected function getType($ref)
    {
        if(version_compare(PHP_VERSION, '7.0.0')>=0) {
            if(!$ref->hasType())
                return null;
            $type = $ref->getType();
            return ($type->isBuiltin()?'':'\\').strval($type);
        }
        if($ref->isArray()) {
            return 'array';
        }
        if(version_compare(PHP_VERSION, '5.4.0')>=0 && $ref->isCallable()) {
            return 'callable';
        }
        try {
            $classRef = $ref->getClass();
        } catch(\Exception $e) {
            $methodRef = $ref->getDeclaringFunction();
            throw new Exception\DomainException($e->getMessage().': '.$methodRef->getFileName().'('.$methodRef->getStartLine().')',0);
        }
        if($classRef) {
            return '\\'.$classRef->getName();
        }
        return null;
    }

    public function getInterfaceBasedInterceptorDeclare($className,$mode)
    {
        $tmpClassName = str_replace('\\', '/', $this->getInterceptorClassName($className,$mode));
        $namespace  = str_replace('/', '\\',dirname($tmpClassName));
        $classBaseName  = basename($tmpClassName);
        $classRef = new ReflectionClass($className);
        $interfaces = $classRef->getInterfaces();
        $copy = $interfaces;
        foreach ($copy as $interface) {
            foreach($interface->getInterfaces() as $parent) {
                unset($interfaces[$parent->name]);
            }
        }

        $interfacesImplements = '';
        if(count($interfaces)) {
            foreach ($interfaces as $interface) {
                if(empty($interfacesImplements))
                    $interfacesImplements = 'implements \\'.$interface->name;
                else
                    $interfacesImplements .= ',\\'.$interface->name;
            }
        }

        $methodDescribe = '';
        foreach ($classRef->getMethods() as $methodRef) {
            if($methodRef->isStatic())
                $methodDescribe .= $this->getInterfaceStaticMethod($methodRef);
            else if(!$methodRef->isConstructor() && !$methodRef->isAbstract() && !$methodRef->isPrivate() && !$methodRef->isProtected() )
                $methodDescribe .= $this->getInterfaceMethod($methodRef);
        }
        if($namespace=='.')
            $namespaceStatement = '';
        else
            $namespaceStatement = 'namespace '.$namespace.';';
        $classDeclare = <<<EOD
<?php
${namespaceStatement}
use Rindow\\Aop\\Support\\Intercept\\Interceptor;
class ${classBaseName} extends Interceptor ${interfacesImplements}
{
${methodDescribe}
}
EOD;
        return $classDeclare;
    }

    public function getInterfaceMethod($methodRef)
    {
        $calledName = $methodRef->name;
        $methodType = $this->getMethodType($methodRef);
        $paramTypes = $this->getParamTypes($methodRef);
        $paramsDescribe = $this->getCallParams($methodRef,true);
        $returnType = $this->getReturnType($methodRef);
        $describe = <<<EOD

    ${methodType} function ${calledName}(${paramTypes})${returnType}
    {
        return \$this->__call('${calledName}',${paramsDescribe});
    }
EOD;
        return $describe;
    }

    public function getInterfaceStaticMethod($methodRef)
    {
        $calledName = $methodRef->name;
        $methodType = $this->getMethodType($methodRef);
        $paramTypes = $this->getParamTypes($methodRef);
        $parentParamsDescribe = $this->getCallParams($methodRef,false);
        $returnType = $this->getReturnType($methodRef);
        $describe = <<<EOD

    ${methodType} function ${calledName}(${paramTypes})${returnType}
    {
        return parent::${calledName}(${parentParamsDescribe});
    }
EOD;
        return $describe;
    }

    protected function getMethodType($methodRef)
    {
        $methodType = '';
        if($methodRef->isFinal())
            $methodType .= ' final';
        //if($methodRef->isAbstract())
        //    $methodType .= ' abstract';

        if($methodRef->isPublic()||$methodRef->isConstructor())
            $methodType .= ' public';
        else if($methodRef->isProtected())
            $methodType .= ' protected';
        else if($methodRef->isPrivate())
            $methodType .= ' private';

        if($methodRef->isStatic())
            $methodType .= ' static';

        return trim($methodType);
    }

    protected function getParamTypes($methodRef)
    {
        $paramTypes = '';
        foreach ($methodRef->getParameters() as $paramRef) {
            if(!empty($paramTypes))
                $paramTypes .= ',';
            $typeString = $this->getType($paramRef);
            if($typeString) {
                $paramTypes .= ($typeString.' ');
            }
            if(version_compare(PHP_VERSION, '5.6.0')>=0) {
                $isVariadic = $paramRef->isVariadic();
            } else {
                $isVariadic = false;
            }
            if($paramRef->isPassedByReference()){
                $paramTypes .= '&';
            }
            if($isVariadic) {
                $paramTypes .= '...';
            }
            $paramTypes .= '$'.$paramRef->name;
            if(!$isVariadic && $paramRef->isOptional()) {
                $paramTypes .= '='.var_export($paramRef->getDefaultValue(),true);
            }
        }
        return $paramTypes;
    }

    protected function getReturnType($methodRef)
    {
        if(version_compare(PHP_VERSION, '7.0.0')<0)
            return '';
        if(!$methodRef->hasReturnType())
            return '';
        $returnType = $methodRef->getReturnType();
        return ':'.($returnType->allowsNull()?'?':'').
                ($returnType->isBuiltin()?'':'\\').
                strval($returnType);
    }

    protected function getCallParams($methodRef,$array=false)
    {
        $paramsDescribe = array('normal'=>'','variadic'=>'');
        foreach ($methodRef->getParameters() as $paramRef) {
            if(version_compare(PHP_VERSION, '5.6.0')>=0) {
                $isVariadic = $paramRef->isVariadic();
            } else {
                $isVariadic = false;
            }
            if($array && $isVariadic) {
                $variadicType = 'variadic';
            } else {
                $variadicType = 'normal';
            }
            if(!empty($paramsDescribe[$variadicType])) {
                $paramsDescribe[$variadicType] .= ',';
            }
            if($array && !$isVariadic && $paramRef->isPassedByReference()){
                $paramsDescribe[$variadicType] .= '&';
            }
            if($isVariadic && !$array) {
                $paramsDescribe[$variadicType] .= '...';
            }
            $paramsDescribe[$variadicType] .= '$'.$paramRef->name;
        }
        if(!$array) {
            return $paramsDescribe['normal'];
        }
        if(empty($paramsDescribe['variadic'])) {
            $string = 'array('.$paramsDescribe['normal'].')';
        } else {
            $string = 'array_merge(array('.$paramsDescribe['normal'].'),'.$paramsDescribe['variadic'].')';
        }
        return $string;
    }

    public function getInheritanceBasedInterceptorDeclare($className,$mode)
    {
        $tmpClassName = str_replace('\\', '/', $this->getInterceptorClassName($className,$mode));
        $namespace  = str_replace('/', '\\',dirname($tmpClassName));
        $classBaseName  = basename($tmpClassName);
        $classRef = new ReflectionClass($className);
        if($classRef->getConstructor())
            $constructor = '\'__aop_construct\'';
        else
            $constructor = 'null';
        $methodDescribe = '';
        foreach ($classRef->getMethods() as $methodRef) {
            //if(($methodRef->isPublic()||$methodRef->isConstructor()) && !$methodRef->isFinal())
            if($methodRef->isConstructor() || (!$methodRef->isStatic() && !$methodRef->isFinal() && !$methodRef->isAbstract() && !$methodRef->isPrivate() && !$methodRef->isProtected()))
                $methodDescribe .= $this->getMethodDescribe($methodRef);
        }
        if($namespace=='.')
            $namespaceStatement = '';
        else
            $namespaceStatement = 'namespace '.$namespace.';';
        $classDeclare = <<<EOD
<?php
${namespaceStatement}
use Rindow\\Aop\\Support\\Intercept\\Interceptor;
class ${classBaseName} extends \\${className}
{
    protected \$__aop_interceptor;
    public function __construct(\$container,\$component,\$adviceManager=null,\$lazy=null,\$logger=null)
    {
        \$this->__aop_interceptor = new Interceptor(
            \$container,\$component,\$adviceManager,true,\$logger,\$this,${constructor});
        if(!\$lazy)
            \$this->__aop_interceptor->__aop_instantiate();
    }
${methodDescribe}
}
EOD;
        return $classDeclare;
    }

    public function getMethodDescribe($methodRef)
    {
        if($methodRef->isConstructor()) {
            $calledName = '__aop_construct';
            $methodName   = $methodRef->name;
        } else {
            $calledName = $methodRef->name;
            $methodName = $methodRef->name;
        }

        $methodType = $this->getMethodType($methodRef);
        $paramTypes = $this->getParamTypes($methodRef);
        $paramsDescribe = $this->getCallParams($methodRef,true);
        $parentParamsDescribe = $this->getCallParams($methodRef,false);
        $returnType = $this->getReturnType($methodRef);

        $describe = <<<EOD

    public function __aop_method_${methodName}(${paramTypes})${returnType}
    {
        return parent::${methodName}(${parentParamsDescribe});
    }
    ${methodType} function ${calledName}(${paramTypes})
    {
        \$this->__aop_interceptor->__aop_before('${methodName}',${paramsDescribe});
        try {
            \$__aop__callback_set = \$this->__aop_interceptor->__aop_getAround('${methodName}',${paramsDescribe},'__aop_method_${methodName}',true);
            if(\$__aop__callback_set==null) {
                \$__aop__result = parent::${methodName}(${parentParamsDescribe});
            } else {
                list(\$__aop__callback,\$__aop__funcArgs) = \$__aop__callback_set;
                \$__aop__result = \$__aop__callback[0]->call(\$__aop__funcArgs[0],null,\$__aop__funcArgs[2],\$__aop__funcArgs[3]);
            }
        } catch(\\Exception \$__aop_e) {
            \$__aop_e = \$this->__aop_interceptor->__aop_afterThrowing('${methodName}',${paramsDescribe},\$__aop_e);
            throw \$__aop_e;
        }
        \$this->__aop_interceptor->__aop_afterReturning('${methodName}',${paramsDescribe},\$__aop__result);
        return \$__aop__result;
    }
EOD;
        return $describe;
    }

    public function getInterceptorClassName($className,$mode)
    {
        $postfix = '';
        if($mode==null || $mode===true || $mode===self::MODE_INHERITANCE)
            $postfix = 'IH';
        else if($mode===self::MODE_INTERFACE)
            $postfix = 'IF';
        return $className.$postfix.'Interceptor';
    }

    public function buildInterceptor($className,$mode)
    {
        $key = $this->getInterceptorFileName($className,$mode);
        if(!$this->hasCode($key)) {
            $code = $this->getInterceptorDeclare($className,$mode);
            $this->saveCode($key, $code);
        }
        $interceptorClass = $this->getInterceptorClassName($className,$mode);
        if(!class_exists($interceptorClass))
            $this->loadCode($key);
    }

    public function getInterceptorFileName($className,$mode)
    {
        if($this->strage) {
            $key = 'interceptors\\'.$this->getInterceptorClassName($className,$mode);
            return $key;
        }
        $key = '/' . str_replace('\\', '/', __CLASS__.'\\interceptors\\'.$this->getInterceptorClassName($className,$mode)) . '.php';
        return CacheFactory::$fileCachePath . $key;
    }

    public function saveCode($key, $code)
    {
        if($this->strage) {
            if(strpos($code, '<?php')!==0)
                throw new Exception\DomainException('Interceptor code is invalid.');
            $code = ltrim(substr($code,5));
            if(strpos($code, 'namespace ')!==0)
                throw new Exception\DomainException('Interceptor code is invalid.');
            $this->strage->put($key,$code);
            return;
        }
        if(!is_dir(dirname($key))) {
            $dirname = dirname($key);
            mkdir(dirname($key),0777,true);
        }
        file_put_contents($key, $code);
    }

    public function loadCode($key)
    {
        if($this->strage) {
            $code = $this->strage->get($key);
            eval($code);
            return;
        }
        require_once $key;
    }

    public function hasCode($key)
    {
        if($this->strage)
            return $this->strage->containsKey($key);
        return file_exists($key);
    }
}