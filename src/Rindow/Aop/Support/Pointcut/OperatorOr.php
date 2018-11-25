<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\JoinPointInterface;

class OperatorOr extends AbstractOperator
{
    public function matches(JoinPointInterface $joinpoint)
    {
        foreach ($this->getOperands() as $matcher) {
            if($matcher->matches($joinpoint))
                return true;
        }
        return false;
    }
}