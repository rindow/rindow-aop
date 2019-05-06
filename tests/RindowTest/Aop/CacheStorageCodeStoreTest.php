<?php
namespace RindowTest\Aop\CacheStorageCodeStoreTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;
use Rindow\Aop\Support\Intercept\CodeStore\CacheStorage;

class Test extends TestCase
{
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

    public function testSaveAndHas()
    {
    	$factory = $this->getConfigCacheFactory();
    	$storage = new CacheStorage('path',$factory);
    	$this->assertEquals('Foo\\ClassName\\Foo',$storage->getInterceptorStoreKey('Foo\\ClassName\\Foo'));

    	$this->assertFalse($storage->hasCode('Foo\\ClassName\\Foo'));
    	$storage->saveCode('Foo\\ClassName\\Foo',"<?php\n namespace Foo\\ClassName;\nreturn 'Test';");
    	$this->assertTrue($storage->hasCode('Foo\\ClassName\\Foo'));
    	$cache = $factory->getFileCache();
    	$this->assertEquals("namespace Foo\\ClassName;\nreturn 'Test';",$cache->get('Rindow/Aop/Support/Intercept/CodeStore/CacheStorage/Foo\\ClassName\\Foo'));
    	$storage->loadCode('Foo\\ClassName\\Foo');
    	$this->assertTrue(true);
    }
}
