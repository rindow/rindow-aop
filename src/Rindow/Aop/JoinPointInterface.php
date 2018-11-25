<?php
namespace Rindow\Aop;

interface JoinPointInterface
{
    const ACTION_EXECUTION = 'execution';
    const ACTION_SET       = 'set';
    const ACTION_GET       = 'get';

    public function getTarget();
    public function getParameters();
    public function getAction();
    public function getSignature();
    public function getSignatureString();
    public function toString();
}
