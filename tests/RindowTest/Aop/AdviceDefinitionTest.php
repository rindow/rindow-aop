<?php
namespace RindowTest\Aop\AdviceDefinitionTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\AdviceInterface;
use Rindow\Aop\Support\Advice\AdviceDefinition;
use Rindow\Aop\Support\Signature;

class Test extends TestCase
{
	public function testNormal()
	{
		$signature = new Signature(
			SignatureInterface::TYPE_METHOD,
			'componentName',
			'method'
		);
		$advice = new AdviceDefinition();
		$advice->setName('name');
		$this->assertEquals('name',$advice->getName());
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$this->assertEquals(AdviceInterface::TYPE_BEFORE,$advice->getType());
		$advice->setPointcutSignature($signature);
		$pointcutRefs = $advice->getPointcutSignatures();
		$this->assertCount(1,$pointcutRefs);
		$this->assertEquals('componentName::method()',$pointcutRefs[0]);
		$advice->setComponentName('componentName');
		$this->assertEquals('componentName',$advice->getComponentName());
		$advice->setMethod('method');
		$this->assertEquals('method',$advice->getMethod());
	}

	public function testArraySignature()
	{
		$signature1 = new Signature(
			SignatureInterface::TYPE_METHOD,
			'componentName1',
			'method1'
		);
		$signature2 = new Signature(
			SignatureInterface::TYPE_METHOD,
			'componentName2',
			'method2'
		);
		$advice = new AdviceDefinition();
		$advice->setName('name');
		$this->assertEquals('name',$advice->getName());
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$this->assertEquals(AdviceInterface::TYPE_BEFORE,$advice->getType());
		$advice->setPointcutSignature(array($signature1,$signature2));
		$pointcutRefs = $advice->getPointcutSignatures();
		$this->assertCount(2,$pointcutRefs);
		$this->assertEquals('componentName1::method1()',$pointcutRefs[0]);
		$this->assertEquals('componentName2::method2()',$pointcutRefs[1]);
		$advice->setComponentName('componentName');
		$this->assertEquals('componentName',$advice->getComponentName());
		$advice->setMethod('method');
		$this->assertEquals('method',$advice->getMethod());
	}

	public function testRefferenceString()
	{
		$signature = 'componentName::method()';
		$advice = new AdviceDefinition();
		$advice->setName('name');
		$this->assertEquals('name',$advice->getName());
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$this->assertEquals(AdviceInterface::TYPE_BEFORE,$advice->getType());
		$advice->setPointcutSignature($signature);
		$pointcutRefs = $advice->getPointcutSignatures();
		$this->assertCount(1,$pointcutRefs);
		$this->assertEquals('componentName::method()',$pointcutRefs[0]);
		$advice->setComponentName('componentName');
		$this->assertEquals('componentName',$advice->getComponentName());
		$advice->setMethod('method');
		$this->assertEquals('method',$advice->getMethod());
	}

	public function testArrayRefferenceString()
	{
		$signature1 = 'componentName1::method1()';
		$signature2 = 'componentName2::method2()';
		$advice = new AdviceDefinition();
		$advice->setName('name');
		$this->assertEquals('name',$advice->getName());
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$this->assertEquals(AdviceInterface::TYPE_BEFORE,$advice->getType());
		$advice->setPointcutSignature(array($signature1,$signature2));
		$pointcutRefs = $advice->getPointcutSignatures();
		$this->assertCount(2,$pointcutRefs);
		$this->assertEquals('componentName1::method1()',$pointcutRefs[0]);
		$this->assertEquals('componentName2::method2()',$pointcutRefs[1]);
		$advice->setComponentName('componentName');
		$this->assertEquals('componentName',$advice->getComponentName());
		$advice->setMethod('method');
		$this->assertEquals('method',$advice->getMethod());
	}

	public function testConfig()
	{
		$advice = new AdviceDefinition();
		$config = array(
			'name' => 'name',
			'type' => 'before',
			'pointcut_ref' => 'pointcut',
			'component' => 'componentName',
			'method' => 'method',
		);
		$advice->setConfig($config);
		$this->assertEquals('name',$advice->getName());
		$this->assertEquals(AdviceInterface::TYPE_BEFORE,$advice->getType());
		$pointcutRefs = $advice->getPointcutSignatures();
		$this->assertEquals('pointcut',$pointcutRefs[0]);
		$this->assertEquals('componentName',$advice->getComponentName());
		$this->assertEquals('method',$advice->getMethod());
	}

    /**
     * @expectedException        Rindow\Aop\Exception\InvalidArgumentException
     * @expectedExceptionMessage the pointcutSignature must be SignatureInterface or string or array.
     */
	public function testInvalidArgument()
	{
		$signature = null;
		$advice = new AdviceDefinition();
		$advice->setName('name');
		$this->assertEquals('name',$advice->getName());
		$advice->setType(AdviceInterface::TYPE_BEFORE);
		$this->assertEquals(AdviceInterface::TYPE_BEFORE,$advice->getType());
		$advice->setPointcutSignature($signature);
	}

}
