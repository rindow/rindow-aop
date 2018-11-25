<?php
namespace Rindow\Aop\Support\JoinPoint;

use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Signature;

class PropertyJoinPoint extends AbstractJoinPoint
{
    protected $propertyValue;

    public function __construct($action,$target,$property,$className=null)
    {
        if($className==null)
            $className=get_class($target);
        $signature = new Signature(SignatureInterface::TYPE_PROPERTY,$className,$property);
        parent::__construct($action,$signature,$target);
        $this->setTarget($target);
    }

    public function getProperty()
    {
        return $this->getSignature()->getProperty();
    }

    public function getValue()
    {
        return $this->propertyValue;
    }

    public function setValue($propertyValue)
    {
        $this->propertyValue = $propertyValue;
    }
}