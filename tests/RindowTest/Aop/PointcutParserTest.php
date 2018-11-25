<?php
namespace RindowTest\Aop\PointcutParserTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\Support\Pointcut\Lexer;
use Rindow\Aop\Support\Pointcut\Parser;
	
class Test extends TestCase
{
	public function testExecution()
	{
		$doc = 'execution(test1::test_test())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcut));
		$this->assertEquals('test1::test_test()',$pointcut->getPattern());
	}

	public function testWithin()
	{
		$doc = 'within(test\test\*)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Within',
			get_class($pointcut));
		$this->assertEquals('test\test\*',$pointcut->getPattern());
	}
	
	public function testTarget()
	{
		$doc = 'target(test\test)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Target',
			get_class($pointcut));
		$this->assertEquals('test\test',$pointcut->getPattern());
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut syntax error:unexpected EOT
     */
	public function testUnmatchedBrackets()
	{
		$doc = 'execution(test::test()';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcut));
		$this->assertEquals('test::test()',$pointcut->getPattern());
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut syntax error:unexpected token ")"
     */
	public function testUnmatchedBrackets2()
	{
		$doc = 'execution(test::test()))';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcut));
		$this->assertEquals('test::test()',$pointcut->getPattern());
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut syntax error:unexpected token "unknown"
     */
	public function testUnknownDesignator()
	{
		$doc = 'unknown(test::test())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcut));
		$this->assertEquals('test::test()',$pointcut->getPattern());
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut syntax error:unexpected token ""
     */
	public function testDesignatorWithoutParameter()
	{
		$doc = 'execution';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
	}

	public function testOperatorAndNormal()
	{
		$doc = 'execution(*::test()) && target(test\test)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcuts[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Target',
			get_class($pointcuts[1]));
	}

	public function testOperatorAndTriple()
	{
		$doc = 'execution(*::test()) && target(test\test) && within(test\test\*)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(3,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcuts[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Target',
			get_class($pointcuts[1]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Within',
			get_class($pointcuts[2]));
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut syntax error:unexpected token "&&"
     */
	public function testOperatorAndUnmatch()
	{
		$doc = 'execution(*::test()) && && target(test\test)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut syntax error:unexpected token "&&"
     */
	public function testOperatorAndUnmatch2()
	{
		$doc = '&& target(test\test)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
	}

	public function testOperatorOrNormal()
	{
		$doc = 'execution(*::test()) || target(test\test)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcuts[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Target',
			get_class($pointcuts[1]));
	}

	public function testOperatorOrTriple()
	{
		$doc = 'execution(*::test()) || target(test\test) || within(test\test\*)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(3,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcuts[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Target',
			get_class($pointcuts[1]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Within',
			get_class($pointcuts[2]));
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut syntax error:unexpected token "||"
     */
	public function testOperatorOrUnmatch()
	{
		$doc = 'execution(*::test()) || || target(test\test)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
	}

    /**
     * @expectedException        Rindow\Aop\Exception\DomainException
     * @expectedExceptionMessage pointcut syntax error:unexpected token "||"
     */
	public function testOperatorOrUnmatch2()
	{
		$doc = '|| target(test\test)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
	}

	public function testOperatorAndOr()
	{
		$doc = 'execution(*::test()) && target(test\test) || within(test\test\*)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Within',
			get_class($pointcuts[1]));
		$and = $pointcuts[0]->getOperands();
		$this->assertEquals(2,count($and));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($and[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Target',
			get_class($and[1]));
	}

	public function testOperatorOrAnd()
	{
		$doc = 'execution(*::test()) || target(test\test) && within(test\test\*)';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Execution',
			get_class($pointcuts[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[1]));
		$and = $pointcuts[1]->getOperands();
		$this->assertEquals(2,count($and));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Target',
			get_class($and[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\Within',
			get_class($and[1]));
	}

	public function testOperatorAndAndOr()
	{
		$doc = 'execution(test::test0()) && execution(test::test1()) && execution(test::test2()) || execution(test::test3())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[0]));
		$this->assertEquals('test::test3()',$pointcuts[1]->getPattern());
		$and = $pointcuts[0]->getOperands();
		$this->assertEquals(3,count($and));
		$this->assertEquals('test::test0()',$and[0]->getPattern());
		$this->assertEquals('test::test1()',$and[1]->getPattern());
		$this->assertEquals('test::test2()',$and[2]->getPattern());
	}

	public function testOperatorAndOrAnd()
	{
		$doc = 'execution(test::test0()) && execution(test::test1()) || execution(test::test2()) && execution(test::test3())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[0]));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[1]));
		$and = $pointcuts[0]->getOperands();
		$this->assertEquals(2,count($and));
		$this->assertEquals('test::test0()',$and[0]->getPattern());
		$this->assertEquals('test::test1()',$and[1]->getPattern());
		$and = $pointcuts[1]->getOperands();
		$this->assertEquals(2,count($and));
		$this->assertEquals('test::test2()',$and[0]->getPattern());
		$this->assertEquals('test::test3()',$and[1]->getPattern());
	}

	public function testOperatorAndOrOr()
	{
		$doc = 'execution(test::test0()) && execution(test::test1()) || execution(test::test2()) || execution(test::test3())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(3,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[0]));
		$this->assertEquals('test::test2()',$pointcuts[1]->getPattern());
		$this->assertEquals('test::test3()',$pointcuts[2]->getPattern());
		$and = $pointcuts[0]->getOperands();
		$this->assertEquals(2,count($and));
		$this->assertEquals('test::test0()',$and[0]->getPattern());
		$this->assertEquals('test::test1()',$and[1]->getPattern());
	}

	public function testOperatorOrAndAnd()
	{
		$doc = 'execution(test::test0()) || execution(test::test1()) && execution(test::test2()) && execution(test::test3())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[1]));
		$and = $pointcuts[1]->getOperands();
		$this->assertEquals(3,count($and));
		$this->assertEquals('test::test1()',$and[0]->getPattern());
		$this->assertEquals('test::test2()',$and[1]->getPattern());
		$this->assertEquals('test::test3()',$and[2]->getPattern());
	}

	public function testOperatorOrAndOr()
	{
		$doc = 'execution(test::test0()) || execution(test::test1()) && execution(test::test2()) || execution(test::test3())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(3,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[1]));
		$this->assertEquals('test::test3()',$pointcuts[2]->getPattern());
		$and = $pointcuts[1]->getOperands();
		$this->assertEquals(2,count($and));
		$this->assertEquals('test::test1()',$and[0]->getPattern());
		$this->assertEquals('test::test2()',$and[1]->getPattern());
	}

	public function testOperatorOrOrAnd()
	{
		$doc = 'execution(test::test0()) || execution(test::test1()) || execution(test::test2()) && execution(test::test3())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(3,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
		$this->assertEquals('test::test1()',$pointcuts[1]->getPattern());
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcuts[2]));
		$and = $pointcuts[2]->getOperands();
		$this->assertEquals(2,count($and));
		$this->assertEquals('test::test2()',$and[0]->getPattern());
		$this->assertEquals('test::test3()',$and[1]->getPattern());
	}

	public function testOperatorNot()
	{
		$doc = '!execution(test::test0())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorNot',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(1,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
	}

	public function testOperatorNotAnd()
	{
		$doc = '!execution(test::test0()) && execution(test::test1())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorNot',
			get_class($pointcuts[0]));
		$this->assertEquals('test::test1()',$pointcuts[1]->getPattern());
		$not = $pointcuts[0]->getOperands();
		$this->assertEquals(1,count($not));
		$this->assertEquals('test::test0()',$not[0]->getPattern());
	}

	public function testOperatorAndNot()
	{
		$doc = 'execution(test::test0()) && !execution(test::test1())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorNot',
			get_class($pointcuts[1]));
		$not = $pointcuts[1]->getOperands();
		$this->assertEquals(1,count($not));
		$this->assertEquals('test::test1()',$not[0]->getPattern());
	}

	public function testOperatorAndNotAnd()
	{
		$doc = 'execution(test::test0()) && !execution(test::test1()) && execution(test::test2())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(3,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorNot',
			get_class($pointcuts[1]));
		$this->assertEquals('test::test2()',$pointcuts[2]->getPattern());
		$not = $pointcuts[1]->getOperands();
		$this->assertEquals(1,count($not));
		$this->assertEquals('test::test1()',$not[0]->getPattern());
	}

	public function testOperatorNotOr()
	{
		$doc = '!execution(test::test0()) || execution(test::test1())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorNot',
			get_class($pointcuts[0]));
		$this->assertEquals('test::test1()',$pointcuts[1]->getPattern());
		$not = $pointcuts[0]->getOperands();
		$this->assertEquals(1,count($not));
		$this->assertEquals('test::test0()',$not[0]->getPattern());
	}

	public function testOperatorOrNot()
	{
		$doc = 'execution(test::test0()) || !execution(test::test1())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorNot',
			get_class($pointcuts[1]));
		$not = $pointcuts[1]->getOperands();
		$this->assertEquals(1,count($not));
		$this->assertEquals('test::test1()',$not[0]->getPattern());
	}

	public function testOperatorOrdNotOr()
	{
		$doc = 'execution(test::test0()) || !execution(test::test1()) || execution(test::test2())';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(3,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorNot',
			get_class($pointcuts[1]));
		$this->assertEquals('test::test2()',$pointcuts[2]->getPattern());
		$not = $pointcuts[1]->getOperands();
		$this->assertEquals(1,count($not));
		$this->assertEquals('test::test1()',$not[0]->getPattern());
	}

	public function testBrackets()
	{
		$doc = '(execution(test::test0()))';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('test::test0()',$pointcut->getPattern());
	}

	public function testAndBracketsOr()
	{
		$doc = 'execution(test::test0()) && (execution(test::test1()) || execution(test::test2()) )';
		$lexer = new Lexer($doc,null);
		$parser = new Parser();
		$pointcut = $parser->parse($lexer);
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorAnd',
			get_class($pointcut));
		$pointcuts = $pointcut->getOperands();
		$this->assertEquals(2,count($pointcuts));
		$this->assertEquals('test::test0()',$pointcuts[0]->getPattern());
		$this->assertEquals('Rindow\Aop\Support\Pointcut\OperatorOr',
			get_class($pointcuts[1]));
		$or = $pointcuts[1]->getOperands();
		$this->assertEquals(2,count($or));
		$this->assertEquals('test::test1()',$or[0]->getPattern());
		$this->assertEquals('test::test2()',$or[1]->getPattern());
	}
}