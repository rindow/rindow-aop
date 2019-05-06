<?php
namespace Rindow\Aop\Support\Intercept\CodeStore;

use Rindow\Aop\Exception;

class CacheStorage implements CodeStore
{
    protected $filePath;
    protected $configCacheFactory;
    protected $cache;

    public function __construct($filePath,$configCacheFactory)
    {
        $this->filePath = $filePath;
        $this->configCacheFactory = $configCacheFactory;
    }

    public function getCache()
    {
        if($this->cache==null) {
            $this->cache = $this->configCacheFactory->create(__CLASS__,$forceFileCache=true);
        }
        return $this->cache;
    }

    public function getInterceptorStoreKey($interceptorClassName)
    {
        return $interceptorClassName;
    }

    public function saveCode($key, $code)
    {
        if(strpos($code, '<?php')!==0)
            throw new Exception\DomainException('Interceptor code is invalid.');
        $code = ltrim(substr($code,5));
        if(strpos($code, 'namespace ')!==0)
            throw new Exception\DomainException('Interceptor code is invalid.');
        $this->getCache()->set($key,$code);
    }

    public function loadCode($key)
    {
        $code = $this->getCache()->get($key);
        if($code==null)
            throw new Exception\DomainException('Interceptor code not found.');
        eval($code);
    }

    public function hasCode($key)
    {
        return $this->getCache()->has($key);
    }
}