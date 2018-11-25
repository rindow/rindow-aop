<?php
namespace AcmeTest\Aop\AopTest;

use Rindow\Aop\Annotation\Aspect;
use Rindow\Aop\Annotation\Pointcut;
use Rindow\Aop\Annotation\Before;
use Rindow\Aop\JoinPointInterface;

/**
* @Aspect
*/
class AnnotatedAspect
{
    protected $logger;

    public function __construct($logger) {
        $this->logger = $logger;
    }

    /**
    * @Pointcut("execution(**::getArg1()) || execution(**::getParam0Arg1())")
    */
    public function pc1() {}

    /**
    * @Pointcut("execution(**::getArg2())")
    */
    public function pc2() {}

    /**
    * @Before(pointcut="pc1")
    */
    public function beforeAdvice(JoinPointInterface $joinPoint)
    {
        $args = $joinPoint->getParameters();
        $message = 'Before call MESSAGE!::';
        if(isset($args[0]))
            $message .= '(arg='.$args[0].')';
        $this->logger->log($message);
    }

    /**
    * @Before(pointcut={"pc1","pc2"})
    */
    public function beforeAdvice2(JoinPointInterface $joinPoint)
    {
        $args = $joinPoint->getParameters();
        $message = 'Before call MESSAGE! at Advice2::';
        if(isset($args[0]))
            $message .= '(arg='.$args[0].')';
        $this->logger->log($message);
    }
}

