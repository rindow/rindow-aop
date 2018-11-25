<?php
namespace Rindow\Aop\Support\Intercept;

use Rindow\Container\Container;
use Rindow\Container\ComponentDefinition;
use Rindow\Container\Definition;
use Rindow\Aop\AdviceInterface;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Support\Advice\AdviceManager;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;
use Rindow\Aop\Support\JoinPoint\PropertyJoinPoint;
use Rindow\Aop\Exception;

class Interceptor
{
    protected $__aop_serviceContainer;
    protected $__aop_component;
    protected $__aop_adviceManager;
    protected $__aop_instance;
    protected $__aop_constructor;
    protected $__aop_className;
    protected $__aop_is_initialized;
    protected $__aop_logger;

    public function __construct(
        Container $container, 
        ComponentDefinition $component,
        AdviceManager $adviceManager=null,
        $lazy=null,
        $logger=null,
        $instance=null,
        $constructor=null)
    {
        $this->__aop_serviceContainer = $container;
        $this->__aop_component = $component;
        $this->__aop_adviceManager = $adviceManager;
        $this->__aop_logger = $logger;
        $this->__aop_instance = $instance;
        $this->__aop_constructor = $constructor;
        $this->__aop_className = $this->__aop_component->getClassName();
        if(!$lazy) {
            $this->__aop_instantiate();
        }
    }

    protected function __aop_debug($message)
    {
        if($this->__aop_logger==null)
            return;
        $message = 'aop: '.$message.' (component='.$this->__aop_component->getName().')';
        $this->__aop_logger->debug($message);
    }

    public function __aop_instantiate()
    {
        if($this->__aop_is_initialized)
            return;
        $this->__aop_is_initialized = true; // CAUTION: this sentence must be here for anti infinity loop.
        $this->__aop_debug('instantiate');
        $instance = $this->__aop_serviceContainer->instantiate($this->__aop_component,null,null,$this->__aop_instance,$this->__aop_constructor);
        if($this->__aop_instance===null)
            $this->__aop_instance = $instance;
    }

    public function __aop_before($methodName, array $arguments)
    {
        if($this->__aop_logger)
            $this->__aop_debug('intercept "before('.$methodName.')"');
        $this->__aop_instantiate();
        if(!$this->__aop_adviceManager)
            return;
        if($this->__aop_adviceManager->inAdvice())
            return;
        $joinpoint = new MethodJoinPoint(
            $this->__aop_instance,
            $methodName,
            $this->__aop_className);
        $joinpoint->setName(AdviceInterface::TYPE_BEFORE);
        $this->__aop_adviceManager->getEventManager($joinpoint)
            ->notify($joinpoint,$arguments,$this->__aop_instance);
    }

    public function __aop_afterThrowing($methodName, array $arguments, \Exception $e)
    {
        if($this->__aop_logger)
            $this->__aop_debug('intercept "afterThrowing('.$methodName.')"');
        if(!$this->__aop_adviceManager)
            return $e;
        if($this->__aop_adviceManager->inAdvice())
            return $e;
        $joinpoint = new MethodJoinPoint(
            $this->__aop_instance,
            $methodName,
            $this->__aop_className);
        $joinpoint->setName(AdviceInterface::TYPE_AFTER_THROWING);
        $joinpoint->setThrowing($e);
        $events = $this->__aop_adviceManager->getEventManager($joinpoint);
        $events->notify($joinpoint,$arguments,$this->__aop_instance);
        $joinpoint->setName(AdviceInterface::TYPE_AFTER);
        $events->notify($joinpoint,$arguments,$this->__aop_instance);
        return $joinpoint->getThrowing();
    }

    public function __aop_afterReturning($methodName, array $arguments, $result)
    {
        if($this->__aop_logger)
            $this->__aop_debug('intercept "afterReturning('.$methodName.')"');
        if(!$this->__aop_adviceManager)
            return;
        if($this->__aop_adviceManager->inAdvice())
            return;
        $joinpoint = new MethodJoinPoint(
            $this->__aop_instance,
            $methodName,
            $this->__aop_className);
        $joinpoint->setName(AdviceInterface::TYPE_AFTER_RETURNING);
        $joinpoint->setReturning($result);
        $events = $this->__aop_adviceManager->getEventManager($joinpoint);
        $events->notify($joinpoint,$arguments,$this->__aop_instance);
        $joinpoint->setName(AdviceInterface::TYPE_AFTER);
        $events = $this->__aop_adviceManager->getEventManager($joinpoint);
        $events->notify($joinpoint,$arguments,$this->__aop_instance);
    }

    public function __aop_getAround($methodName, array $arguments,$instanceMethod=null,$senseDirect=null)
    {
        if($this->__aop_logger)
            $this->__aop_debug('intercept "around('.$methodName.')"');
        if($instanceMethod===null)
            $instanceMethod = $methodName;
        $callback = array($this->__aop_instance,$instanceMethod);

        if($this->__aop_adviceManager===null ||
            $this->__aop_adviceManager->inAdvice())
        {
            if($senseDirect)
                return null;
            return array($callback, $arguments);
        }

        $joinpoint = new MethodJoinPoint(
            $this->__aop_instance,
            $methodName,
            $this->__aop_className);
        $joinpoint->setName(AdviceInterface::TYPE_AROUND);
        $joinpoint->setParameters($arguments);

        $eventManager = $this->__aop_adviceManager->getEventManager($joinpoint);
        $eventQueue = $eventManager->prepareCall($joinpoint);
        if($eventQueue==null) {
            if($senseDirect)
                return null;
            return array($callback, $arguments);
        }
        $aroundFunc = array($eventManager,'call');
        $funcArgs = array($joinpoint,null,$callback,$eventQueue);
        return array($aroundFunc,$funcArgs);
    }

    public function __call($methodName, array $arguments)
    {
        $this->__aop_before($methodName, $arguments);
        try {
            // *** CAUTION ***
            // Saving calling nest depth patch
            // call_user_func_xxx　waste nest depth
            //
            $callback_set = $this->__aop_getAround($methodName,$arguments,null,true);
            if($callback_set==null) {
                $result = call_user_func_array(array($this->__aop_instance,$methodName), $arguments);
            } else {
                list($callback,$funcArgs) = $callback_set;
                $result = $callback[0]->call($funcArgs[0],null,$funcArgs[2],$funcArgs[3]);
            }
        } catch(\Exception $e) {
            $e = $this->__aop_afterThrowing($methodName, $arguments, $e);
            throw $e;
        }
        $this->__aop_afterReturning($methodName, $arguments, $result);
        return $result;
    }

    public function __get($varName)
    {
        if($this->__aop_logger)
            $this->__aop_debug('intercept "__get('.$varName.')"');
        $this->__aop_instantiate();
        if($this->__aop_adviceManager &&
            !$this->__aop_adviceManager->inAdvice()) {
            $joinpoint = new PropertyJoinPoint(
                JoinPointInterface::ACTION_GET,
                $this->__aop_instance,
                $varName,
                $this->__aop_className);
            $joinpoint->setName(AdviceInterface::TYPE_BEFORE);
            $this->__aop_adviceManager->getEventManager($joinpoint)
                ->notify($joinpoint,null,$this->__aop_instance);
        }

        $value = $this->__aop_instance->$varName;

        if($this->__aop_adviceManager &&
            !$this->__aop_adviceManager->inAdvice()) {
            $joinpoint->setName(AdviceInterface::TYPE_AFTER);
            $this->__aop_adviceManager->getEventManager($joinpoint)
                ->notify($joinpoint,null,$this->__aop_instance);
        }
        return $value;
    }

    public function __set($varName, $value)
    {
        if($this->__aop_logger)
            $this->__aop_debug('intercept "__set('.$varName.')"');
        $this->__aop_instantiate();
        if($this->__aop_adviceManager &&
            !$this->__aop_adviceManager->inAdvice()) {
            $joinpoint = new PropertyJoinPoint(
                JoinPointInterface::ACTION_SET,
                $this->__aop_instance,
                $varName,
                $this->__aop_className);
            $joinpoint->setName(AdviceInterface::TYPE_BEFORE);
            $joinpoint->setValue($value);
            $this->__aop_adviceManager->getEventManager($joinpoint)
                ->notify($joinpoint,null,$this->__aop_instance);
        }

        $this->__aop_instance->$varName = $value;

        if($this->__aop_adviceManager &&
            !$this->__aop_adviceManager->inAdvice()) {
            $joinpoint->setName(AdviceInterface::TYPE_AFTER);
            $this->__aop_adviceManager->getEventManager($joinpoint)
                ->notify($joinpoint,null,$this->__aop_instance);
        }
        return $value;
    }

    public function __isset($varName)
    {
        if($this->__aop_logger)
            $this->__aop_debug('intercept "__isset('.$varName.')"');
        $this->__aop_instantiate();
        return isset($this->__aop_instance->$varName);
    }

    public function __unset($varName)
    {
        if($this->__aop_logger)
            $this->__aop_debug('intercept "__unset('.$varName.')"');
        $this->__aop_instantiate();
        unset($this->__aop_instance->$varName);
    }

    public static function __callStatic($methodName, array $arguments)
    {
        throw new Exception\DomainException('static method is not supported to call a interceptor in "'.get_called_class().'".');
    }
}
