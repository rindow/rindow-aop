<?php
namespace Rindow\Aop;

interface AdvisorInterface
{
    public function invoke(ProceedingJoinPointInterface $joinPoint);
}
