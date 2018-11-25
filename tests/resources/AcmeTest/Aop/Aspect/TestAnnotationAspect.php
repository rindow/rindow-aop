<?php
namespace AcmeTest\Aop\Aspect;

use Rindow\Aop\Annotation\Aspect;
use Rindow\Aop\Annotation\Before;

/**
* @Aspect
*/
class TestAnnotationAspect
{
	/**
	* @Before("execution(**::test())")
	*/
	public function foo1($event)
	{
		return __METHOD__;
	}
	/**
	* @Before("execution(**::test2())")
	*/
	public function foo2($event)
	{
		return __METHOD__;
	}
}