<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\Exception;

Class Parser
{
    protected $debug;

    public function debug($debug=true)
    {
        $this->debug = $debug;
    }

    public function parse($lexer,$openToken=null)
    {
        if($this->debug) echo '[parse]';
        $matcher = null;
        while ($token=$lexer->get()) {
            if($this->debug) echo '['.$token.']';
            switch ($token) {
                case '(':
                    $lexer->next();
                    $matcher = $this->parse($lexer,'(');
                    $token=$lexer->get();
                    if($token!=')')
                        throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
                    $lexer->next();
                    break;

                case ')':
                    if($openToken==null)
                        throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
                    return $matcher;

                case 'execution':
                case 'target':
                case 'within':
                    if($matcher!=null)
                        throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
                    $designator = $token;
                    $lexer->next();
                    if($designator=='execution') {
                        $matcher = new Execution();
                    } else if($designator=='target'){
                        $matcher = new Target();
                    } else if($designator=='within'){
                        $matcher = new Within();
                    }
                    $arguments = $this->parseArguments($lexer);
                    $token = $lexer->get();
                    if($token!=')')
                        throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
                    $lexer->next();
                    $matcher->setPattern($arguments,$lexer->where());
                    break;

                case '!':
                    if($this->debug) echo '[Operator!]';
                    if($matcher)
                        throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
                    $lexer->next();
                    $nextMatcher = $this->parse($lexer,'!');
                    if($this->debug) echo '[new Not]';
                    $matcher = new OperatorNot();
                    $matcher->append($nextMatcher);
                    break;

                case '&&':
                    if($matcher==null)
                        throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
                    if($openToken=='!' || $openToken=='&&')
                        return $matcher;
                    $lexer->next();
                    $nextMatcher = $this->parse($lexer,'&&');
                    $matcher = $this->mergeOperandAnd($matcher,$nextMatcher);
                    break;

                case '||':
                    if($matcher==null)
                        throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
                    if($openToken=='!' || $openToken=='&&' || $openToken=='||')
                        return $matcher;
                    $lexer->next();
                    $nextMatcher = $this->parse($lexer,'||');
                    if($lexer->get()=='&&') {
                        $lexer->next();
                        $priortyMatcher = $this->parse($lexer,'&&');
                        $nextMatcher = $this->mergeOperandAnd($nextMatcher,$priortyMatcher);
                    }
                    $matcher = $this->mergeOperandOr($matcher,$nextMatcher);
                    break;

                default:
                    throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
            }
        }
        if($this->debug) echo '['.$token.']';
        if($matcher==null)
            throw new Exception\DomainException('pointcut syntax error:matcher is null:'.$lexer->where());
        return $matcher;
    }

    private function mergeOperandAnd($previous,$current)
    {
        if($previous instanceof OperatorAnd) {
            $previous->append($current);
            return $previous;
        } else {
            $matcher = new OperatorAnd();
            $matcher->append($previous);
            $matcher->append($current);
            return $matcher;
        }
    }

    private function mergeOperandOr($previous,$current)
    {
        if($previous instanceof OperatorOr) {
            $previous->append($current);
            return $previous;
        } else {
            $matcher = new OperatorOr();
            $matcher->append($previous);
            $matcher->append($current);
            return $matcher;
        }
    }

    private function parseArguments($lexer)
    {
        $arguments = '';
        $nest = 0;
        $token = $lexer->get();
        if($token!='(')
            throw new Exception\DomainException('pointcut syntax error:unexpected token "'.$token.'":'.$lexer->where());
        $lexer->next();
        while($token=$lexer->get()) {
            switch ($token) {
                case '(':
                    $nest = $nest + 1;
                    break;

                case ')':
                    $nest = $nest - 1;
                    if($nest<0)
                        return $arguments;
                    break;

                default:
                    break;
            }
            $arguments .= $token;
            $lexer->next();
        }
        throw new Exception\DomainException('pointcut syntax error:unexpected EOT :'.$lexer->where());
    }
}
