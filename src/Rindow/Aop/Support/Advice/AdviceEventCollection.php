<?php
namespace Rindow\Aop\Support\Advice;

use Iterator;

use Rindow\Event\AbstractEventManager;
use Rindow\Event\EventManagerInterface;
use Rindow\Event\EventInterface;
use Rindow\Aop\Support\JoinPoint\ProceedingJoinPoint;

class AdviceEventCollection extends AbstractEventManager
{
    protected $adviceManager;
    protected $logger;

    public function setAdviceManager($adviceManager)
    {
        $this->adviceManager = $adviceManager;
    }

    public function getAdviceManager()
    {
        return $this->adviceManager;
    }

    public function inAdvice()
    {
        return $this->adviceManager->inAdvice();
    }

    public function setAdvice($status)
    {
        $this->adviceManager->setAdvice($status);
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function debug($message)
    {
        if($this->logger==null)
            return;
        $message = 'aop: '.$message;
        $this->logger->debug($message);
    }

    public function notify(
        $event,
        array $args = null,
        $target = null,
        $previousResult=null)
    {
        try {
            $this->setAdvice(true);
            $result = parent::notify($event,$args,$target,$previousResult);
            $this->setAdvice(false);
            return $result;
        } catch (\Exception $e) {
            $this->setAdvice(false);
            throw $e;
        }
    }

    protected function doNotify($callback,$event,$listener)
    {
        if($this->logger) {
            $this->debug('call advice: '.get_class($callback[0]).'::'.$callback[1]);
        }
        return call_user_func($callback,$event,$listener);
    }

    protected function createProceeding(
        EventInterface $event,
        array $args,
        $terminator,
        Iterator $iterator,
        /*ServiceLocator*/ $serviceLocator=null)
    {
        $proceeding = new ProceedingJoinPoint(
            $this,
            $event,
            $terminator,
            $iterator,
            $serviceLocator);
        //$this->setAdvice(true);
        return array($proceeding,array());
    }

    protected function isAopMode()
    {
        return true;
    }

    protected function startProceeding()
    {
        $this->setAdvice(true);
    }

    protected function endProceeding()
    {
        $this->setAdvice(false);
    }

/*
    protected function postProceeding(
        $result,
        EventInterface $event,
        array $args,
        $terminator,
        $proceeding,
        array $proceedingArgs)
    {
        $this->setAdvice(false);
        return $result;
    }

    protected function exceptionProceeding(
        $exception,
        EventInterface $event,
        array $args,
        $terminator,
        $proceeding,
        array $proceedingArgs)
    {
        $this->setAdvice(false);
        return $exception;
    }
*/
}