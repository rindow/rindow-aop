<?php
namespace Rindow\Aop;

use ReflectionClass;
use ArrayObject;
use Rindow\Stdlib\Cache\CacheHandlerTemplate;
use Rindow\Container\Container;
use Rindow\Container\ComponentScanner;
use Rindow\Container\ComponentDefinition;
use Rindow\Container\ProxyManager;
use Rindow\Aop\AdviceInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Signature;
use Rindow\Aop\Support\Advice\AdviceManager;
use Rindow\Aop\Support\Advice\AdviceDefinition;
use Rindow\Aop\Support\Intercept\InterceptorBuilder;
use Rindow\Aop\Support\JoinPoint\LabelJoinPoint;
use Rindow\Aop\Support\Pointcut\PointcutManager;
use Rindow\Aop\Annotation\AbstractAdvice;
use Rindow\Aop\Annotation\Pointcut;

class AopManager implements ProxyManager
{
    const ANNOTATION_ASPECT = 'Rindow\\Aop\\Annotation\\Aspect';
    const ANNOTATION_PROXY  = 'Rindow\\Container\\Annotation\\Proxy';
    const CACHE_ASPECTCOMPONENTNAME = 'aspectClassName';
    const CACHE_INTERCEPTTARGET = 'interceptTarget';
    const CACHE_INITIALIZED = '__INITIALIZED__';
    const DEFAULT_LOGGER = 'Logger';

    protected $pointcutManager;
    protected $adviceManager;
    protected $aspects = array();
    protected $cacheHandler;
    protected $annotationManager;
    protected $container;
    protected $aspectComponentNames;
    protected $interceptTargets;
    protected $config;
    protected $joinPoint;
    protected $plugins = array();
    protected $isDebug;
    protected $logger;
    protected $debugAdviceNames = array();
    protected $debugPointcutNames = array();

    public function __construct(
        Container $container,
        PointcutManager $pointcutManager=null,
        AdviceManager $adviceManager=null,
        InterceptorBuilder $interceptorBuilder=null)
    {
        $this->cacheHandler = new CacheHandlerTemplate(__CLASS__);
        $this->container = $container;
        $this->getAspectCache();
        if($pointcutManager)
            $this->pointcutManager = $pointcutManager;
        else
            $this->pointcutManager = new PointcutManager();
        if($adviceManager)
            $this->adviceManager = $adviceManager;
        else
            $this->adviceManager = new AdviceManager($this->pointcutManager,$container);
        if($interceptorBuilder)
            $this->interceptorBuilder = $interceptorBuilder;
        else
            $this->interceptorBuilder = new InterceptorBuilder();
        $this->annotationManager = $container->getAnnotationManager();
    }

    public function getAdviceManager()
    {
        return $this->adviceManager;
    }

    public function setConfig(array $config=null)
    {
        if($config==null)
            return;
        $this->config = $config;
        if(isset($config['debug'])) {
            $this->isDebug = $config['debug'];
        }
        $this->interceptorBuilder->setConfig($this->config);

        if($this->hasScanned())
            return;
        if(isset($config['pointcuts'])) {
            if(!is_array($config['pointcuts']))
                throw new Exception\DomainException('syntax error in pointcuts configuration.');
            foreach($config['pointcuts'] as $signagureString => $pattern) {
                if(!is_string($pattern))
                    throw new Exception\DomainException('pointcut pattern must be string in "'.$signagureString.'".');
                $location = 'METADATA::pointcuts::'.$signagureString;
                $this->addPointcut($pattern,$signagureString,$location);
            }
        }
        if(isset($config['aspects'])) {
            if(!is_array($config['aspects']))
                throw new Exception\DomainException('syntax error in aspects configuration.');
            foreach($config['aspects'] as $aspectName  => $aspect) {
                if(!is_array($aspect))
                    throw new Exception\DomainException('syntax error in aspect "'.$aspectName.'" in configuration.');
                if(isset($config['aspectOptions'][$aspectName])) {
                    $aspectOptions = $config['aspectOptions'][$aspectName];
                    if(!is_array($aspectOptions))
                        throw new Exception\DomainException('syntax error in aspectOptions "'.$aspectName.'" in configuration.');
                    $aspect = array_replace_recursive($aspect, $aspectOptions);
                }
                $this->addAspect($aspect,$aspectName);
            }
        }
        if(isset($config['advisors'])) {
            if(!is_array($config['advisors']))
                throw new Exception\DomainException('syntax error in aspects configuration.');
            foreach($config['advisors'] as $component => $advisor) {
                if(!is_array($advisor))
                    throw new Exception\DomainException('syntax error in advisor "'.$component.'" in configuration.');
                $this->addAdvisor($advisor,$component);
            }
        }
        if(isset($config['plugins'])) {
            if(!is_array($config['plugins']))
                throw new Exception\DomainException('syntax error in the aop plugins configuration.');
            foreach($config['plugins'] as $pluginClass => $switch) {
                if(!$switch)
                    continue;
                if(!class_exists($pluginClass))
                    throw new Exception\DomainException('the class "'.$pluginClass.'" is not found in the aop plugins configuration.');
                $plugin = new $pluginClass($this,$this->container);
                $plugin->setConfig($config);
                $this->plugins[] = $plugin;
            }
        }
        if(isset($config['intercept_to'])) {
            foreach ($config['intercept_to'] as $namespace => $switch) {
                if($switch) {
                    $this->addInterceptTarget($namespace);
                }
            }
        }
    }

    protected function debug($message, array $context = array())
    {
        if(!$this->isDebug)
            return;
        if($this->logger==null) {
            if(isset($this->config['logger']))
                $logService = $this->config['logger'];
            else
                $logService = self::DEFAULT_LOGGER;
            $this->logger = $this->container->get($logService);
            $this->adviceManager->setLogger($this->logger);
        }
        $this->logger->debug($message,$context);
    }

    public function attachScanner(ComponentScanner $componentScanner)
    {
        if($this->hasScanned())
            return;
        $componentScanner->attachCollect(
            self::ANNOTATION_ASPECT,
            array($this,'collectAspect'));
        foreach ($this->plugins as $plugin) {
            if(method_exists($plugin, 'attachScanner'))
                $plugin->attachScanner($componentScanner);
        }
        $componentScanner->attachCompleted(
            self::ANNOTATION_ASPECT,
            array($this,'completedScan'));
    }

    public function newProxy(
        Container $container,
        ComponentDefinition $component,
        array $options=null)
    {
        $mode = isset($options['mode']) ? $options['mode'] : null;
        $lazy = isset($options['lazy']) ? $options['lazy'] : null;
        if($this->isAspectComponentName($component->getName()) || $mode==='disable') {
            return $container->instantiate($component,$component->getName());
        }
        $className = $component->getClassName();

        if(!isset($this->config['intercept_to_all']) || !$this->config['intercept_to_all']) {
            if(!$this->isInterceptTarget($className)) {
                return $container->instantiate($component,$component->getName());
            }
        }

        if($component->hasFactory()) {
            if($className=='array') {
                return $container->instantiate($component,$component->getName());
            }
            $mode='interface';
        }

        if(!class_exists($className))
            throw new Exception\DomainException('class name is not specifed for interceptor in component "'.$component->getName().'".');
        $this->interceptorBuilder->buildInterceptor($className,$mode);

        $interceptorName = $this->interceptorBuilder->getInterceptorClassName($className,$mode);

        if(isset($this->config['disable_interceptor_event']))
            $adviceManager = null;
        else
            $adviceManager = $this->adviceManager;

        $this->debug('aop: create interceptor for "'.$component->getName().'"');

        return new $interceptorName(
            $container,
            $component,
            $adviceManager,
            $lazy,
            $this->logger);
    }

    public function notify(
        $label,
        array $args = null,
        $target = null,
        $previousResult=null)
    {
        $joinpoint = new LabelJoinPoint($target,$label);
        $joinpoint->setName(AdviceInterface::TYPE_BEFORE);
        return $this->getAdviceManager()
            ->getEventManager($joinpoint)
            ->notify($joinpoint,$args,$target,$previousResult);
    }

    public function call(
        $label,
        array $args = null,
        $target = null,
        $terminator=null)
    {
        $joinpoint = new LabelJoinPoint($target,$label);
        $joinpoint->setName(AdviceInterface::TYPE_AROUND);
        $joinpoint->setParameters($args);
        return $this->getAdviceManager()
            ->getEventManager($joinpoint)
            ->call($joinpoint,null,$terminator);
    }

    public function getAspectCache()
    {
        return $this->cacheHandler->getCache('aspects',$forceFileCache=true);
    }

    public function setEnableCache($enableCache=true)
    {
        $this->cacheHandler->setEnableCache($enableCache);
    }

    public function setCachePath($cachePath)
    {
        $this->cacheHandler->setCachePath($cachePath);
    }

    public function hasScanned()
    {
        $cache = $this->getAspectCache();
        return isset($cache[self::CACHE_INITIALIZED]);
    }

    public function completedScan()
    {
        $cache = $this->getAspectCache();
        if($this->aspectComponentNames===null)
            $this->aspectComponentNames = array();
        $cache[self::CACHE_ASPECTCOMPONENTNAME] = $this->aspectComponentNames;
        if($this->interceptTargets===null)
            $this->interceptTargets = array();
        $cache[self::CACHE_INTERCEPTTARGET] = $this->interceptTargets;
        $cache[self::CACHE_INITIALIZED] = true;
        $this->pointcutManager->save();
    }

    public function getAspectComponentNames()
    {
        if($this->aspectComponentNames===null) {
            $cache = $this->getAspectCache();
            $this->aspectComponentNames = $cache->get(self::CACHE_ASPECTCOMPONENTNAME,array());
        }
        return $this->aspectComponentNames;
    }

    public function getInterceptTargets()
    {
        if($this->interceptTargets===null) {
            $cache = $this->getAspectCache();
            $this->interceptTargets = $cache->get(self::CACHE_INTERCEPTTARGET,array());
        }
        return $this->interceptTargets;
    }

    public function addPointcut($pattern,$signature,$location=null)
    {
        if($this->isDebug)
            $this->debugPointcutNames[] = $pattern;
        if(!($signature instanceof Signature)) {
            $signature = new Signature(
                SignatureInterface::TYPE_LABEL,
                $signature,
                null);
        }
        $this->pointcutManager->register(
            $this->pointcutManager->generate($signature,$pattern,$location)
        );
    }

    public function addAdviceByConfig(array $config,$aspectName,$adviceName)
    {
        if(!isset($config['type']))
            throw new Exception\DomainException('advices must contain the "type" in configuration for "'.$adviceName.'"');
        //if(!isset($config['method']))
        //    throw new Exception\DomainException('advices must contain the "method" in configuration for "'.$adviceName.'"');
        if(!isset($config['component']))
            $config['component'] = $aspectName;
        if(!isset($config['method']))
            $config['method'] = $adviceName;
        $location = 'METADATA::aspects::'.$aspectName.'::advices::method('.$config['method'].')::pointcut';
        if(isset($config['pointcut'])) {
            if(isset($config['pointcut_ref']))
                throw new Exception\DomainException('advices must contain either the "pointcut" or "pointcut_ref" in configuration for "'.$adviceName.'"');
            $signature = new Signature(
                SignatureInterface::TYPE_METHOD,
                $aspectName,
                $config['method']);
            $this->addPointcut($config['pointcut'],$signature,$location);
            $config['pointcut_ref'] = $signature->toString();
        } else {
            if(!isset($config['pointcut_ref'])) {
                if($this->isDebug)
                    $this->debug('The advice "'.$adviceName.'" does not have pointcut in the aspect "'.$aspectName.'".');
                return;
                //throw new Exception\DomainException('advices must contain either the "pointcut" or "pointcut_ref" in configuration for "'.$adviceName.'"');
            }
            if(is_array($config['pointcut_ref'])) {
                $pointcutRef = array();
                foreach($config['pointcut_ref'] as $signature => $switch) {
                    if(!$switch)
                        continue;
                    $pointcutRef[] = $signature;
                }
                $config['pointcut_ref'] = $pointcutRef;
            }
        }
        $advice = new AdviceDefinition();
        $advice->setConfig($config);

        $this->assertPointcutExists($advice,$location);
        $this->adviceManager->register($advice);
        if($this->isDebug)
            $this->debugAdviceNames[] = $aspectName.'::'.$adviceName;
    }

    public function addAdviceByAnnotation(AbstractAdvice $anno,$aspectName,$method,$location=null)
    {
        $advice = new AdviceDefinition();
        $advice->setType($anno->getType());
        if($anno->value) {
            if($anno->pointcut)
                throw new Exception\DomainException('advices must contain either the "pointcut" or "value".: '.$location);
            $signature = new Signature(
                SignatureInterface::TYPE_METHOD,
                $aspectName,
                $method);
            $this->addPointcut($anno->value,$signature,$location);
            $advice->setPointcutSignature($signature);
        } else {
            if(!$anno->pointcut)
                throw new Exception\DomainException('advices must contain either the "pointcut" or "value"!.: '.$location);
            $signatures = $this->transformPointcutInAnnotaion($aspectName,$anno->pointcut);
            $advice->setPointcutSignature($signatures);
        }
        $advice->setComponentName($aspectName);
        $advice->setMethod($method);

        $this->assertPointcutExists($advice,$location);
        $this->adviceManager->register($advice);
        if($this->isDebug)
            $this->debugAdviceNames[] = $aspectName.'::'.$method;
    }

    protected function transformPointcutInAnnotaion($aspectName,$pointcut)
    {
        if(is_string($pointcut)) {
            $refs = array($pointcut);
        } elseif(is_array($pointcut)) {
            $refs = $pointcut;
        } else {
            throw new Exception\InvalidArgumentException('pointcut must include string or array of string.');
        }
        $signatures = array();
        foreach ($refs as $value) {
            $signatures[] = new Signature(
                SignatureInterface::TYPE_METHOD,
                $aspectName,
                $value);
        }
        return $signatures;
    }

    public function addAspect($config,$aspectName)
    {
        if(!is_string($aspectName))
            throw new Exception\InvalidArgumentException('must be class name.');
        if(!is_array($config))
            throw new Exception\InvalidArgumentException('must be including aspect configuration.');
        if(isset($config['component']))
            $componentName = $config['component'];
        else
            $componentName = $aspectName;
        if(isset($config['pointcuts'])) {
            foreach($config['pointcuts'] as $signagureString => $pattern) {
                $location = 'METADATA::aspects::'.$aspectName.'::pointcuts::'.$signagureString;
                $this->addPointcut($pattern,$signagureString,$location);
            }
        }
        if(isset($config['advices'])) {
            $this->addAspectComponentName($componentName);
            foreach($config['advices'] as $adviceName => $advice) {
                if(!isset($advice['component']))
                    $advice['component'] = $componentName;
                $this->addAdviceByConfig($advice,$aspectName,$adviceName);
            }
        }
    }

    public function collectAspect($annoName,$className,$anno,ReflectionClass $classRef)
    {
        $this->addAspectComponentName($className);
        if($this->annotationManager==null)
            return;

        foreach($classRef->getMethods() as $methodRef) {
            $annos = $this->annotationManager->getMethodAnnotations($methodRef);
            foreach ($annos as $anno) {
                if($anno instanceof AbstractAdvice) {
                    $location = $className.'::'.$methodRef->getName().'() - '.$methodRef->getFileName().'('.$methodRef->getStartLine().')';
                    $this->addAdviceByAnnotation($anno,$className,$methodRef->getName(),$location);
                } else if($anno instanceof Pointcut) {
                    $location = $className.'::'.$methodRef->getName().'() - '.$methodRef->getFileName().'('.$methodRef->getStartLine().')';
                    $signature = new Signature(
                        SignatureInterface::TYPE_METHOD,
                        $className,
                        $methodRef->getName());
                    $this->addPointcut($anno,$signature,$location);
                }
            }
        }
    }

    public function addAdvisor(array $config,$component)
    {
        $location = 'METADATA::advisors::'.$component.'::pointcut';
        if(isset($config['pointcut'])) {
            if(isset($config['pointcut_ref']))
                throw new Exception\DomainException('advices must contain either the "pointcut" or "pointcut_ref" in configuration for "'.$component.'"');
            $signature = new Signature(
                SignatureInterface::TYPE_LABEL,
                $component,
                null);
            $this->addPointcut($config['pointcut'],$signature,$location);
            $config['pointcut_ref'] = $signature->toString();
        } else {
            if(!isset($config['pointcut_ref']))
                throw new Exception\DomainException('advisors must contain either the "pointcut" or "pointcut_ref" in configuration for "'.$component.'"');
        }
        $adviceConfig['type'] = AdviceInterface::TYPE_AROUND;
        $adviceConfig['component'] = $component;
        $adviceConfig['method'] = 'invoke';
        $adviceConfig['pointcut_ref'] = $config['pointcut_ref'];
        $advice = new AdviceDefinition();
        $advice->setConfig($adviceConfig);

        $this->assertPointcutExists($advice,$location);
        $this->adviceManager->register($advice);
    }

    protected function assertPointcutExists(AdviceDefinition $advice,$location)
    {
        foreach ($advice->getPointcutSignatures() as $pointcutSignatureString) {
            if(!$this->pointcutManager->existsInSignatureString($pointcutSignatureString))
                throw new Exception\DomainException('pointcut "'.implode(',',$advice->getPointcutSignatures()).'" is not found: '.$location);
        }
    }

    protected function addAspectComponentName($componentName)
    {
        $this->getAspectComponentNames();
        $this->aspectComponentNames[$componentName] = true;
    }

    protected function isAspectComponentName($componentName)
    {
        $aspectComponentName = $this->getAspectComponentNames();
        return isset($aspectComponentName[$componentName]);
    }

    public function addInterceptTarget($namespace)
    {
        $interceptTargets = $this->getInterceptTargets();

        if(!empty($interceptTargets) && $this->isInterceptTarget($namespace))
            return;
        $this->interceptTargets[$namespace] = true;
    }

    public function isInterceptTarget($className)
    {
        $interceptTargets = $this->getInterceptTargets();
        //if(empty($interceptTargets))
        //    return true;
        foreach($interceptTargets as $namespace => $switch) {
            if(!$switch)
                continue;
            if(strpos($className, $namespace)===0) {
                return true;
            }
        }
        return false;
    }

    public function dumpDebug()
    {
        if(!$this->isDebug)
            return;
        $this->debug('aop: AopManager is in active.');
        
        $this->debug('aop: plugins:...');
        foreach ($this->plugins as $plugin) {
            $this->debug('aop:     '.get_class($plugin));
        }
        $this->debug('aop: advices:...');
        foreach ($this->debugAdviceNames as $adviceName) {
            $this->debug('aop:     '.$adviceName);
        }
        $this->debug('aop: pointcuts:...');
        foreach ($this->debugPointcutNames as $pattern) {
            $this->debug('aop:     '.$pattern);
        }
        $this->debug('aop: intercept_to:...');
        foreach ($this->getInterceptTargets() as $namespace => $switch) {
            if($switch)
                $this->debug('aop:     '.$namespace);
        }
    }
}
