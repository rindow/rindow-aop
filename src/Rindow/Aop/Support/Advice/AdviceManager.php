<?php
namespace Rindow\Aop\Support\Advice;

use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Support\Pointcut\PointcutManager;
use Rindow\Aop\Support\Pointcut\Pointcut;
use Rindow\Event\EventListener;
use ArrayObject;
use Rindow\Stdlib\Cache\CacheHandlerTemplate;
/*use Rindow\Container\ServiceLocator;*/

class AdviceManager
{
    protected $cacheHandler;
    protected $pointcutManager;
    protected $serviceLocator;
    protected $adviceContextStatus = false;
    protected $adviceEventCollections = array();
    protected $logger;

    public function __construct(
        PointcutManager $pointcutManager = null,
        /*ServiceLocator*/ $serviceLocator=null)
    {
        $this->cacheHandler = new CacheHandlerTemplate(__CLASS__);
        if($pointcutManager)
            $this->setPointcutManager($pointcutManager);
        if($serviceLocator)
            $this->setServiceLocator($serviceLocator);
    }

    public function setPointcutManager(PointcutManager $pointcutManager=null)
    {
        $this->pointcutManager = $pointcutManager;
    }

    public function getPointcutManager()
    {
        return $this->pointcutManager;
    }

    public function setServiceLocator(/*ServiceLocator*/ $serviceLocator=null)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    protected function getQueryCache()
    {
        return $this->cacheHandler->getCache('query');
    }

    public function getRepository()
    {
        return $this->cacheHandler->getCache('repository',$forceFileCache=true);
    }

    public function setEnableCache($enableCache=true)
    {
        $this->cacheHandler->setEnableCache($enableCache);
    }

    public function setCachePath($cachePath)
    {
        $this->cacheHandler->setCachePath($cachePath);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        if($this->pointcutManager)
            $this->pointcutManager->setLogger($logger);
    }

    protected function debug($message)
    {
        if($this->logger==null)
            return;
        $message = 'aop: '.$message;
        $this->logger->debug($message);
    }

    protected function getQueryIndex($joinpoint)
    {
        return $joinpoint->getSignatureString();
    }

    protected function getRepositoryIndex(Pointcut $pointcut)
    {
        return $pointcut->getSignature()->toString();
    }

    protected function getRepositoryIndexFromString($pointcutString)
    {
        return $pointcutString;
    }

/*
    public function register(AdviceDefinition $advice)
    {
        $index = $this->getRepositoryIndexFromString($advice->getPointcutSignature());
        $repository = $this->getRepository();
        if(isset($repository[$index])) {
            $advices = $repository[$index];
            $advices[] = $advice;
        } else {
            $advices = array($advice);
        }
        $repository[$index] = $advices;
    }
    public function getAdvices(Pointcut $pointcut)
    {
        $index = $this->getRepositoryIndex($pointcut);
        $repository = $this->getRepository();
        if(!isset($repository[$index]))
            return false;
        return $repository[$index];
    }
*/
    public function register(AdviceDefinition $advice)
    {
        $pointcutSignatures = $advice->getPointcutSignatures();
        foreach ($pointcutSignatures as $pointcutRef) {
            $index = $this->getRepositoryIndexFromString($pointcutRef);
            $repository = $this->getRepository();
            $advices = $repository->get($index,false);
            if($advices===false)
                $advices = array($advice);
            else
                $advices[] = $advice;
            $repository->put($index,$advices);
        }
    }

    public function getAdvices(Pointcut $pointcut)
    {
        $index = $this->getRepositoryIndex($pointcut);
        $repository = $this->getRepository();
        return $repository->get($index,false);
    }

    protected function generateAdviceEventCollection($advices)
    {
        $eventCollection = new AdviceEventCollection();
        if($this->logger)
            $eventCollection->setLogger($this->logger);
        foreach ($advices as $advice) {
            if($this->logger)
                $this->debug('attach advice "'.$advice->getComponentName().'::'.$advice->getMethod().'" to pointcut "'.implode(',',$advice->getPointcutSignatures()).'"');
            //$listener = new EventListener(null,$advice->getComponentName(),$advice->getMethod());
            $listener = new AdviceEventListener($advice);
            $eventCollection->attach(
                $advice->getType(),
                $listener
            );
        }
        $eventCollection->setAdviceManager($this);
        $eventCollection->setServiceLocator($this->serviceLocator);
        return $eventCollection;
    }
/*
    protected function getCachedEventCollection($index)
    {
        if(array_key_exists($index, $this->adviceEventCollections))
            return $this->adviceEventCollections[$index];
        $cache = $this->getQueryCache();
        if(!isset($cache[$index]))
            return false;
        $advices = $cache[$index];
        $eventCollection = $this->generateAdviceEventCollection($advices);
        return $this->adviceEventCollections[$index] = $eventCollection;
    }
*/
    protected function getCachedEventCollection($index)
    {
        if(array_key_exists($index, $this->adviceEventCollections))
            return $this->adviceEventCollections[$index];
        $cache = $this->getQueryCache();
        $advices = $cache->get($index,false);
        if($advices===false)
            return false;
        $eventCollection = $this->generateAdviceEventCollection($advices);
        return $this->adviceEventCollections[$index] = $eventCollection;
    }

    protected function generateAndCachingEventCollection($index,$advices)
    {
        $cache = $this->getQueryCache();
        $cache[$index] = $advices;
        $eventCollection = $this->generateAdviceEventCollection($advices);
        return $this->adviceEventCollections[$index] = $eventCollection;
    }

    public function getEventManager(JoinPointInterface $joinpoint)
    {
        $queryIndex = $this->getQueryIndex($joinpoint);
        if($this->logger)
            $this->debug('query advice for "'.$queryIndex.'"');
        $eventCollection = $this->getCachedEventCollection($queryIndex);
        if($eventCollection) {
            if($this->logger) {
                $this->debug('event names:'.implode(',', $eventCollection->getEventNames()));
            }
            return $eventCollection;
        }

        $pointcuts = $this->getPointcutManager()->find($joinpoint);
        $matchedAdvices = array();
        foreach ($pointcuts as $pointcut) {
            $advices = $this->getAdvices($pointcut);
            if(!$advices)
                continue;
            $matchedAdvices = array_merge($matchedAdvices,$advices);
        }
        $eventCollection = $this->generateAndCachingEventCollection($queryIndex,$matchedAdvices);
        if($this->logger) {
            $this->debug('event names: '.implode(',', $eventCollection->getEventNames()));
        }
        return $eventCollection;
    }

    public function inAdvice()
    {
        return $this->adviceContextStatus;
    }

    public function setAdvice($status)
    {
        $this->adviceContextStatus = $status;
    }
}