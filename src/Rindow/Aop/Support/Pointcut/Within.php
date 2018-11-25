<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Exception;

class Within extends AbstractDesignator
{
    protected $regex;

    public function setPattern($pattern,$location=null)
    {
        if(!preg_match('/^[a-zA-Z0-9_\\\\\*]+$/', $pattern))
            throw new Exception\DomainException('a "within" pointcut must contain a class with wildcard."'.$pattern.'":'.$location);

        parent::setPattern($pattern);
        $this->regex = '/^' . str_replace(
            array('\\','**','*'), 
            array('\\\\','[a-zA-Z0-9_\\\\]+','[a-zA-Z0-9_]+'),
            $pattern) . '$/';
    }

    public function matches(JoinPointInterface $joinpoint)
    {
        if(preg_match($this->regex, $joinpoint->getSignature()->getClassName()))
            return true;
        else
            return false;
    }
}