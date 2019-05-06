<?php
namespace Rindow\Aop\Support\Pointcut;

use ArrayObject;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\MatcherInterface;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;
use Rindow\Aop\Exception;

class PointcutManager
{
    protected $configCacheFactory;
    protected $pointcutsCache;
    protected $queryCache;
    protected $parser;
    protected $pointcuts;
    protected $logger;

    public function __construct($configCacheFactory=null)
    {
        if($configCacheFactory)
            $this->configCacheFactory = $configCacheFactory;
        else
            $this->configCacheFactory = new ConfigCacheFactory(array('enableCache'=>false));
    }

    public function getParser()
    {
        if(!$this->parser)
            $this->parser = new Parser();
        return $this->parser;
    }

    protected function getPointcutsCache()
    {
        if($this->pointcutsCache==null)
            $this->pointcutsCache = $this->configCacheFactory->create(__CLASS__.'/pointcuts',$forceFileCache=true);
        return $this->pointcutsCache;
    }

    protected function getQueryCache()
    {
        if($this->queryCache==null)
            $this->queryCache = $this->configCacheFactory->create(__CLASS__.'/query');
        return $this->queryCache;
    }

    //public function setEnableCache($enableCache=true)
    //{
    //    $this->configCacheFactory->setEnableCache($enableCache);
    //}

    //public function setCachePath($cachePath)
    //{
    //    $this->configCacheFactory->setCachePath($cachePath);
    //}

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function debug($message)
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

    protected function getPointcutIndex(Pointcut $pointcut)
    {
        return $pointcut->getSignatureString();
    }

    public function register(Pointcut $pointcut)
    {
        $index = $this->getPointcutIndex($pointcut);
        //$this->getPointcuts();
        if(isset($this->pointcuts[$index]))
            throw new Exception\DomainException('Duplicate pointcut signature.:'.$pointcut->getSignatureString());
        $this->pointcuts[$index] = $pointcut;
    }

    public function existsInSignatureString($signature)
    {
        return isset($this->pointcuts[$signature]);
    }

    public function save()
    {
        if($this->pointcuts === null)
            $this->pointcuts = new ArrayObject();
        $cache = $this->getPointcutsCache();
        $cache->set('pointcuts',$this->pointcuts);
    }
/*
    public function load()
    {
        $cache = $this->getPointcutsCache();
        if(isset($cache['pointcuts']))
            $this->pointcuts = $cache['pointcuts'];
        else
            $this->pointcuts = new ArrayObject();
    }
*/
    public function load()
    {
        $cache = $this->getPointcutsCache();
        $pointcuts = $cache->get('pointcuts',false);
        if($pointcuts!==false)
            $this->pointcuts = $pointcuts;
        else
            $this->pointcuts = new ArrayObject();
    }

    public function getPointcuts()
    {
        if(!$this->pointcuts)
            $this->load();
        return $this->pointcuts;
    }
/*
    public function find($joinpoint)
    {
        $cache = $this->getQueryCache();
        $queryIndex = $this->getQueryIndex($joinpoint);
        if(isset($cache[$queryIndex]))
            return $cache[$queryIndex];
        $matched = array();
        $pointcuts = $this->getPointcuts();
        foreach($pointcuts as $pointcut) {
            if($pointcut->matches($joinpoint)) {
                $matched[] = $pointcut;
                if($this->logger)
                    $this->debug('a pointcut is matched '.$pointcut->getPattern().'.: "'.$joinpoint->getSignatureString().'"');
            }
        }
        $cache[$queryIndex] = $matched;
        return $matched;
    }
*/
    public function find($joinpoint)
    {
        $cache = $this->getQueryCache();
        $queryIndex = $this->getQueryIndex($joinpoint);
        $manager = $this;
        $matched = $cache->getEx(
            $queryIndex,
            function ($cacheKey,$args) {
                list($joinpoint,$manager) = $args;
                $matched = array();
                $pointcuts = $manager->getPointcuts();
                foreach($pointcuts as $pointcut) {
                    if($pointcut->matches($joinpoint)) {
                        $matched[] = $pointcut;
                        if($manager->getLogger())
                            $manager->debug('a pointcut is matched '.$pointcut->getPattern().'.: "'.$joinpoint->getSignatureString().'"');
                    }
                }
                return $matched;
            },
            array($joinpoint,$this)
        );
        return $matched;
    }

    public function generate(SignatureInterface $signature,$pattern,$location=null)
    {
        if($pattern instanceof Pointcut) {
            $pointcut = $pattern;
            $pattern = $pointcut->getPattern();
        } else {
            $pointcut = new Pointcut();
            $pointcut->setPattern($pattern);
        }
        $lexer = new Lexer($pattern,$location);
        $parser = $this->getParser();
        $pointcut->setSignature($signature);
        $pointcut->setMatcher($parser->parse($lexer));
        return $pointcut;
    }
}