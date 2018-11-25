<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\JoinPointInterface;

class OperatorAnd extends AbstractOperator
{
    public function matches(JoinPointInterface $joinpoint)
    {
        foreach ($this->getOperands() as $matcher) {
            if(!$matcher->matches($joinpoint))
                return false;
        }
        return true;
    }
}