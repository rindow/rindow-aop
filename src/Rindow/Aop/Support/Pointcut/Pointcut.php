<?php
namespace Rindow\Aop\Support\Pointcut;

use Rindow\Aop\JoinpointInterface;
use Rindow\Aop\MatcherInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Stdlib\Entity\AbstractPropertyAccess;

class Pointcut  extends AbstractPropertyAccess implements MatcherInterface
{
    public $value;
    protected $matcher;
    protected $signature;

    public function setSignature(SignatureInterface $signature)
    {
        $this->signature = $signature;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function getSignatureString()
    {
        return $this->getSignature()->toString();
    }

    public function setPattern($pattern)
    {
        $this->value = $pattern;
    }

    public function getPattern()
    {
        return $this->value;
    }

    public function setMatcher(MatcherInterface $matcher)
    {
        $this->matcher   = $matcher;
    }

    public function matches(JoinpointInterface $joinpoint)
    {
        return $this->matcher->matches($joinpoint);
    }
}