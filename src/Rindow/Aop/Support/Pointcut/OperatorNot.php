<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\JoinPointInterface;

class OperatorNot extends AbstractOperator
{
    public function append($pointcut)
    {
        if(count($this->getOperands())!=0)
            throw new Exception\DomainException('OperatorNot must only just one operand.');
        parent::append($pointcut);
    }

    public function prepend($pointcut)
    {
        if(count($this->getOperands())!=0)
            throw new Exception\DomainException('OperatorNot must only just one operand.');
        parent::prepend($pointcut);
    }

    public function matches(JoinPointInterface $joinpoint)
    {
        $matcher = $this->getOperands();
        return !($matcher[0]->matches($joinpoint));
    }
}