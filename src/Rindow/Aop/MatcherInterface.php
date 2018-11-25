<?php
namespace Rindow\Aop;

interface MatcherInterface
{
    public function matches(JoinPointInterface $joinpoint);
}