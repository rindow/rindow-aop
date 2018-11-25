<?php
namespace RindowTest\Aop\PointcutOperatorsTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\MatcherInterface;
use Rindow\Aop\Support\Pointcut\OperatorAnd;
use Rindow\Aop\Support\Pointcut\OperatorOr;
use Rindow\Aop\Support\Pointcut\OperatorNot;
use Rindow\Aop\Support\JoinPoint\MethodJoinPoint;

class TestTarget
{
	public $someone;

	public function something()
	{
		# code...
	}
}

class MatcherTrue implements MatcherInterface
{
	public function matches(JoinPointInterface $joinpoint)
	{
		return true;
	}
}
class MatcherFalse implements MatcherInterface
{
	public function matches(JoinPointInterface $joinpoint)
	{
		return false;
	}
}

class Test extends TestCase
{
	public function testNormalAnd()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');

		$pointcut = new OperatorAnd();
		$pointcut->append(new MatcherTrue());
		$pointcut->append(new MatcherTrue());
		$this->assertEquals(2,count($pointcut->getOperands()));
		$this->assertTrue($pointcut->matches($joinpoint));

		$pointcut = new OperatorAnd();
		$pointcut->append(new MatcherTrue());
		$pointcut->append(new MatcherFalse());
		$this->assertEquals(2,count($pointcut->getOperands()));
		$this->assertFalse($pointcut->matches($joinpoint));

		$pointcut = new OperatorAnd();
		$pointcut->append(new MatcherFalse());
		$pointcut->append(new MatcherTrue());
		$this->assertEquals(2,count($pointcut->getOperands()));
		$this->assertFalse($pointcut->matches($joinpoint));

		$pointcut = new OperatorAnd();
		$pointcut->append(new MatcherFalse());
		$pointcut->append(new MatcherFalse());
		$this->assertEquals(2,count($pointcut->getOperands()));
		$this->assertFalse($pointcut->matches($joinpoint));
	}

	public function testNormalOr()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');

		$pointcut = new OperatorOr();
		$pointcut->append(new MatcherTrue());
		$pointcut->append(new MatcherTrue());
		$this->assertEquals(2,count($pointcut->getOperands()));
		$this->assertTrue($pointcut->matches($joinpoint));

		$pointcut = new OperatorOr();
		$pointcut->append(new MatcherTrue());
		$pointcut->append(new MatcherFalse());
		$this->assertEquals(2,count($pointcut->getOperands()));
		$this->assertTrue($pointcut->matches($joinpoint));

		$pointcut = new OperatorOr();
		$pointcut->append(new MatcherFalse());
		$pointcut->append(new MatcherTrue());
		$this->assertEquals(2,count($pointcut->getOperands()));
		$this->assertTrue($pointcut->matches($joinpoint));

		$pointcut = new OperatorOr();
		$pointcut->append(new MatcherFalse());
		$pointcut->append(new MatcherFalse());
		$this->assertEquals(2,count($pointcut->getOperands()));
		$this->assertFalse($pointcut->matches($joinpoint));
	}

	public function testNormalNot()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');

		$pointcut = new OperatorNot();
		$pointcut->append(new MatcherTrue());
		$this->assertEquals(1,count($pointcut->getOperands()));
		$this->assertFalse($pointcut->matches($joinpoint));

		$pointcut = new OperatorNot();
		$pointcut->append(new MatcherFalse());
		$this->assertEquals(1,count($pointcut->getOperands()));
		$this->assertTrue($pointcut->matches($joinpoint));
	}

	public function testComplex()
	{
		$target = new TestTarget();
		$joinpoint = new MethodJoinPoint($target,'something');

		$not = new OperatorNot();
		$not->append(new MatcherFalse());
		
		$or = new OperatorOr();
		$or->append($not);
		$or->append(new MatcherFalse());

		$and = new OperatorAnd();
		$and->append($or);
		$and->append(new MatcherTrue());

		$this->assertTrue($and->matches($joinpoint));

		$and->append(new MatcherFalse());

		$this->assertFalse($and->matches($joinpoint));
	}
}