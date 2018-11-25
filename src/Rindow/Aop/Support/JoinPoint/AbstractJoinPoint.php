<?php
namespace Rindow\Aop\Support\JoinPoint;

use Rindow\Event\Event;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\SignatureInterface;

abstract class AbstractJoinPoint extends Event implements JoinPointInterface
{
    protected $action;
    protected $signature;
    protected $target;

    public function __construct(
        $action,
        SignatureInterface $signature,
        $target)
    {
        $this->setAction($action);
        $this->setSignature($signature);
        $this->setTarget($target);
    }

    protected function setAction($action)
    {
        $this->action = $action;
    }

    public function getAction()
    {
        return $this->action;
    }

    protected function setSignature(SignatureInterface $signature)
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

    public function toString()
    {
        return $this->getSignatureString();
    }
}