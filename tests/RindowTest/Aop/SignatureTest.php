<?php
namespace RindowTest\Aop\SignatureTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Signature;

class Test extends TestCase
{
	public function testNormalMethod()
	{
		$signature = new Signature(SignatureInterface::TYPE_METHOD,'className','method');
		$this->assertEquals(SignatureInterface::TYPE_METHOD,$signature->getType());
		$this->assertEquals('className',$signature->getClassName());
		$this->assertEquals('method',$signature->getMethod());
		$this->assertEquals('className::method()',$signature->toString());
	}

	public function testNormalProperty()
	{
		$signature = new Signature(SignatureInterface::TYPE_PROPERTY,'className','property');
		$this->assertEquals(SignatureInterface::TYPE_PROPERTY,$signature->getType());
		$this->assertEquals('className',$signature->getClassName());
		$this->assertEquals('property',$signature->getProperty());
		$this->assertEquals('className::$property',$signature->toString());
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage Illegal type
     */
	public function testGetPropertyFromMethod()
	{
		$signature = new Signature(SignatureInterface::TYPE_METHOD,'className','method');
		$this->assertEquals(SignatureInterface::TYPE_METHOD,$signature->getType());
		$this->assertEquals('className',$signature->getClassName());
		$this->assertEquals('method',$signature->getProperty());
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage Illegal type
     */
	public function testGetMethodFromProperty()
	{
		$signature = new Signature(SignatureInterface::TYPE_PROPERTY,'className','property');
		$this->assertEquals(SignatureInterface::TYPE_PROPERTY,$signature->getType());
		$this->assertEquals('className',$signature->getClassName());
		$this->assertEquals('property',$signature->getMethod());
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage Illegal type
     */
	public function testCreateIllegalType()
	{
		$signature = new Signature(-1,'className','property');
	}
}
