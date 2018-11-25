<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Exception;

class Target extends AbstractDesignator
{
    protected $className;

    public function setPattern($pattern,$location=null)
    {
        if(!preg_match('/^[a-zA-Z0-9_\\\\]+$/', $pattern))
            throw new Exception\DomainException('a "target" pointcut must contain a class without wildcard."'.$pattern.'":'.$location);
        parent::setPattern($pattern);
        $this->className = $pattern;
    }

    public function matches(JoinPointInterface $joinpoint)
    {
        if(is_a($joinpoint->getTarget(), $this->className))
            return true;
        else
            return false;
    }
}