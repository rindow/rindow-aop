<?php
namespace Rindow\Aop\Support\JoinPoint;

use Iterator;
/*use Rindow\Container\ServiceLocator;*/
use Rindow\Event\AbstractEventProceeding;
use Rindow\Event\EventInterface;
use Rindow\Aop\ProceedingJoinPointInterface;
use Rindow\Aop\Support\Advice\AdviceEventCollection;

class ProceedingJoinPoint extends AbstractEventProceeding implements ProceedingJoinPointInterface
{
    protected $eventManager;

    public function __construct(
        AdviceEventCollection $eventManager,
        EventInterface $event,
        $terminator,
        Iterator $iterator,
        /*ServiceLocator*/ $serviceLocator=null)
    {
        $this->eventManager = $eventManager;
        parent::__construct($event,$terminator,$iterator,$serviceLocator);
    }
/*
    protected function call($current)
    {
        return call_user_func($current,$this);
    }
*/
    public function getTarget()
    {
        return $this->getEvent()->getTarget();
    }

    public function getParameters()
    {
        return $this->getEvent()->getParameters();
    }

    public function toString()
    {
        return $this->getEvent()->getName();
    }
    
    public function getAction()
    {
        return $this->getEvent()->getAction();
    }

    public function getSignature()
    {
        return $this->getEvent()->getSignature();
    }

    public function getSignatureString()
    {
        return $this->getEvent()->getSignatureString();
    }

    public function getAdvice()
    {
        return $this->getListener()->getAdvice();
    }

    protected function preListener($current,array $arguments)
    {
        if($this->eventManager->getLogger()) {
            $this->eventManager->debug('call advice: '.get_class($current[0]).'::'.$current[1]);
        }
        return array($this);
    }

    protected function postListener($result,$current,array $arguments)
    {
        return $result;
    }

    protected function exceptionListener($exception,$current,array $arguments)
    {
        return $exception;
    }

    public function preTerminator($terminator,array $arguments)
    {
        return $this->getParameters();
    }

    public function postTerminator($result,$terminator,array $arguments)
    {
        return $result;
    }

    public function exceptionTerminator($exception,$terminator,array $arguments)
    {
        return $exception;
    }

    protected function isAopMode()
    {
        return true;
    }

    protected function startTerminator()
    {
        $this->eventManager->setAdvice(false);
    }

    protected function endTerminator()
    {
        $this->eventManager->setAdvice(true);
    }
}
