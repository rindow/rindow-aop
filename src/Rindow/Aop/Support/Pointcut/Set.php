<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Exception;

class Set extends AbstractDesignator
{
    protected $regex;

    public function setPattern($pattern,$location=null)
    {
        if(!preg_match('/^[a-zA-Z0-9_\\\\*]+::\$\\(?[a-zA-Z0-9_\\*|]+\\)?$/', $pattern))
            throw new Exception\DomainException('a "set" pointcut must contain a class and property with wildcard."'.$pattern.'":'.$location);
        parent::setPattern($pattern);
        $this->regex = '/^' . str_replace(
            array('\\','**','*','$'),
            array('\\\\','[a-zA-Z0-9_\\\\]+','[a-zA-Z0-9_]+','\\$'),
            $pattern) . '$/';
    }

    public function matches(JoinPointInterface $joinpoint)
    {
        if($joinpoint->getAction()!=JoinPointInterface::ACTION_SET)
            return false;
        if(preg_match($this->regex, $joinpoint->getSignatureString()))
            return true;
        else
            return false;
    }
}