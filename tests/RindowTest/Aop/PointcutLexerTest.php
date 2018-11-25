<?php
namespace RindowTest\Aop\PointcutLexerTest;

use PHPUnit\Framework\TestCase;
use Rindow\Aop\Support\Pointcut\Lexer;
	
class Test extends TestCase
{
	public function testWord()
	{
		$str = "execution(test.test1.test_test0::test0()) && within(test) || !target(test)";
		$lexer = new Lexer($str,null);
		$this->assertEquals('execution',$lexer->get());
		$this->assertEquals('execution',$lexer->get());
		$lexer->next();
		$this->assertEquals('(',$lexer->get());
		$this->assertEquals('(',$lexer->get());
		$lexer->next();
		$this->assertEquals('test.test1.test_test0::test0',$lexer->get());
		$lexer->next();
		$this->assertEquals('(',$lexer->get());
		$this->assertEquals('(',$lexer->get());
		$lexer->next();
		$this->assertEquals(')',$lexer->get());
		$this->assertEquals(')',$lexer->get());
		$lexer->next();
		$this->assertEquals(')',$lexer->get());
		$lexer->next();
		$this->assertEquals('&&',$lexer->get());
		$this->assertEquals('&&',$lexer->get());
		$lexer->next();
		$this->assertEquals('within',$lexer->get());
		$lexer->next();
		$this->assertEquals('(',$lexer->get());
		$lexer->next();
		$this->assertEquals('test',$lexer->get());
		$lexer->next();
		$this->assertEquals(')',$lexer->get());
		$lexer->next();
		$this->assertEquals('||',$lexer->get());
		$lexer->next();
		$this->assertEquals('!',$lexer->get());
		$lexer->next();
		$this->assertEquals('target',$lexer->get());
		$lexer->next();
		$this->assertEquals('(',$lexer->get());
		$lexer->next();
		$this->assertEquals('test',$lexer->get());
		$lexer->next();
		$this->assertEquals(')',$lexer->get());
		$lexer->next();
		$this->assertFalse($lexer->get());
	}

	public function testNot()
	{
		$doc = '!execution(test0())';
		$lexer = new Lexer($doc,null);
		$this->assertEquals('!',$lexer->get());
		$lexer->next();
		$this->assertEquals('execution',$lexer->get());
		$lexer->next();
		$this->assertEquals('(',$lexer->get());
		$lexer->next();
		$this->assertEquals('test0',$lexer->get());
		$lexer->next();
		$this->assertEquals('(',$lexer->get());
		$lexer->next();
		$this->assertEquals(')',$lexer->get());
		$lexer->next();
		$this->assertEquals(')',$lexer->get());
	}
}