<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\MatcherInterface;

abstract class AbstractOperator implements MatcherInterface
{
    protected $pointcuts = array();
    
    public function append($pointcut)
    {
        if($pointcut!=null && get_class($this)==get_class($pointcut))
            $this->pointcuts = array_merge($this->pointcuts,$pointcut->getOperands());
        else if($pointcut instanceof MatcherInterface)
            array_push($this->pointcuts, $pointcut);
        else
            throw new Exception('Invalid type of operand.');
    }
    
    public function prepend($pointcut)
    {
        if($pointcut!=null && get_class($this)==get_class($pointcut))
            $this->pointcuts = array_merge($pointcut->getOperands(),$this->pointcuts);
        else if($pointcut instanceof MatcherInterface)
            array_unshift($this->pointcuts, $pointcut);
        else
            throw new Exception('Invalid type of operand.');
    }

    public function getOperands()
    {
        return $this->pointcuts;
    }
}