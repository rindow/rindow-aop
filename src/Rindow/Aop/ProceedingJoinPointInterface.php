<?php
namespace Rindow\Aop;

interface ProceedingJoinPointInterface extends JoinPointInterface
{
    public function proceed();
}
