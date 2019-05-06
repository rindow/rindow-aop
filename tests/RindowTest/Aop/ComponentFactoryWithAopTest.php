<?php
namespace RindowTest\Aop\ComponentFactoryWithAopTest;

use PHPUnit\Framework\TestCase;
use Rindow\Container\ModuleManager;

class TestWithConfig
{
	protected $componentName;

	static function factory($serviceLocator,$componentName,$args)
	{
		$instance = new self();
		$instance->setComponentName($componentName);
		return $instance;
	}

	public function setComponentName($componentName)
	{
		$this->componentName = $componentName;
	}

	public function getComponentName()
	{
		return $this->componentName;
	}
}

class Test extends TestCase
{
	public function setup()
	{
	}

	public function getConfig($options)
	{
		$config = array(
			'module_manager' => array(
				'modules' => array(
					'Rindow\Aop\Module' => true,
				),
				'annotation_manager' => true,
				'enableCache' => false,
			),
			'aop' => array(
				'intercept_to' => array(
					__NAMESPACE__ => true,
				),
			),
			'container' => array(
				'components' => array(
					'TestWithConfig' => array(
						'class' => __NAMESPACE__.'\TestWithConfig',
						'factory' => __NAMESPACE__.'\TestWithConfig::factory',
					),
					'TestWithConfigWithProxyDisable' => array(
						'class' => __NAMESPACE__.'\TestWithConfig',
						'factory' => __NAMESPACE__.'\TestWithConfig::factory',
						'proxy' => 'disable',
					),
					'TestArrayWithConfig' => array(
						'class' => 'array',
						'factory' => __NAMESPACE__.'\TestWithConfig::factory',
					),
				),
			),
		);
		$config = array_replace_recursive($config, $options);
		return $config;
	}

	public function testFactoryHasProxyDisableOption()
	{
		$config = array(
		);
		$config = $this->getConfig($config);
		$mm = new ModuleManager($config);
		$instance = $mm->getServiceLocator()->get('TestWithConfigWithProxyDisable');
		$this->assertEquals(__NAMESPACE__.'\TestWithConfig',get_class($instance));
		$this->assertEquals('TestWithConfigWithProxyDisable',$instance->getComponentName());
	}

	public function testFactoryisNotInterceptTarget()
	{
		$config = array(
			'aop' => array(
				'intercept_to' => array(
					__NAMESPACE__ => false,
				),
			),
		);
		$config = $this->getConfig($config);
		$mm = new ModuleManager($config);
		$instance = $mm->getServiceLocator()->get('TestWithConfig');
		$this->assertEquals(__NAMESPACE__.'\TestWithConfig',get_class($instance));
		$this->assertEquals('TestWithConfig',$instance->getComponentName());
	}

	public function testFactoryWithSpecialArrayKeyWord()
	{
		$config = array(
			'aop' => array(
				'intercept_to_all' => true,
			),
		);
		$config = $this->getConfig($config);
		$mm = new ModuleManager($config);
		$instance = $mm->getServiceLocator()->get('TestArrayWithConfig');
		$this->assertEquals(__NAMESPACE__.'\TestWithConfig',get_class($instance));
		$this->assertEquals('TestArrayWithConfig',$instance->getComponentName());
	}

	public function testFactoryisInterceptTarget()
	{
		$config = array(
		);
		$config = $this->getConfig($config);
		$mm = new ModuleManager($config);
		$instance = $mm->getServiceLocator()->get('TestWithConfig');
		$this->assertNotEquals(__NAMESPACE__.'\TestWithConfig',get_class($instance));
		$this->assertEquals('TestWithConfig',$instance->getComponentName());
	}
}
