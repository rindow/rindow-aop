<?php
namespace Rindow\Aop\Support\JoinPoint;

use Rindow\Aop\SignatureInterface;
use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\Support\Signature;

class MethodJoinPoint extends AbstractJoinPoint
{
    protected $returning;
    protected $throwing;

    public function __construct($target,$method,$className=null)
    {
        if($className==null)
            $className=get_class($target);
        $signature = new Signature(SignatureInterface::TYPE_METHOD,$className,$method);
        parent::__construct(JoinPointInterface::ACTION_EXECUTION,$signature,$target);
        $this->setTarget($target);
    }

    public function getMethod()
    {
        return $this->getSignature()->getMethod();
    }

    public function setReturning($returning=null)
    {
        $this->returning = $returning;
        return $this;
    }

    public function getReturning()
    {
        return $this->returning;
    }

    public function setThrowing(\Exception $throwing)
    {
        $this->throwing = $throwing;
        return $this;
    }

    public function getThrowing()
    {
        return $this->throwing;
    }
}