<?php
namespace RindowTest\Aop\FilesystemCodeStoreTest;

use PHPUnit\Framework\TestCase;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;
use Rindow\Aop\Support\Intercept\CodeStore\Filesystem;

class Test extends TestCase
{
    public function getConfigCacheFactory()
    {
        $config = array(
                'filePath'   => RINDOW_TEST_CACHE,
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
                    //'class' => 'Rindow\Stdlib\Cache\SimpleCache\ArrayCache',
                ),
        );
        $configCacheFactory = new ConfigCacheFactory($config);
        return $configCacheFactory;
    }

    public function testSaveAndHas()
    {
    	$factory = $this->getConfigCacheFactory();
    	$storage = new Filesystem(null,$factory);
        $key = RINDOW_TEST_CACHE.'/Rindow/Aop/Support/Intercept/CodeStore/Filesystem/'.'Foo/ClassName/Foo.php';
        if(file_exists($key))
            unlink($key);
    	$this->assertEquals($key,$storage->getInterceptorStoreKey('Foo\\ClassName\\Foo'));

    	$this->assertFalse($storage->hasCode($key));
    	$storage->saveCode($key,"<?php\n namespace Foo\\ClassName;\nreturn 'Test';");
    	$this->assertTrue($storage->hasCode($key));
    	$cache = $factory->getFileCache();
    	$this->assertEquals("<?php\n namespace Foo\\ClassName;\nreturn 'Test';",file_get_contents($key));
    	$storage->loadCode($key);
    	$this->assertTrue(true);
    }

    public function testGetFileName()
    {
        $factory = $this->getConfigCacheFactory();
        $storage = new Filesystem(null,$factory);
        $className = 'Foo\\ClassName\\Foo';
        $this->assertEquals(
            RINDOW_TEST_CACHE.
            '/Rindow/Aop/Support/Intercept/CodeStore/Filesystem/'.
            'Foo/ClassName/Foo.php',
            $storage->getInterceptorStoreKey($className,'interface'));
    }
}
