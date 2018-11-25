<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\MatcherInterface;

abstract class AbstractDesignator implements MatcherInterface
{
    protected $pattern;
    
    public function setPattern($pattern,$location=null)
    {
        $this->pattern = $pattern;
    }

    public function getPattern()
    {
        return $this->pattern;
    }
}