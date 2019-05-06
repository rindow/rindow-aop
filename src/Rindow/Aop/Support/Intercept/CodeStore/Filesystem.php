<?php
namespace Rindow\Aop\Support\Intercept\CodeStore;

class Filesystem implements CodeStore
{
    protected $filePath;
    protected $configCacheFactory;

    public function __construct($filePath,$configCacheFactory)
    {
        $this->filePath = $filePath;
        $this->configCacheFactory = $configCacheFactory;
    }

    protected function getFilePath()
    {
        if($this->filePath==null)
            $this->filePath = $this->configCacheFactory->getFileCache()->getPath();
        return $this->filePath;
    }

    public function getInterceptorStoreKey($interceptorClassName)
    {
        $key = '/' . str_replace('\\', '/', __CLASS__.'\\'.$interceptorClassName) . '.php';
        return $this->getFilePath().$key;
    }

    public function saveCode($key, $code)
    {
        if(!is_dir(dirname($key))) {
            $dirname = dirname($key);
            mkdir(dirname($key),0777,true);
        }
        file_put_contents($key, $code);
    }

    public function loadCode($key)
    {
        require_once $key;
    }

    public function hasCode($key)
    {
        return file_exists($key);
    }
}