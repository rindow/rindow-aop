<?php
namespace Rindow\Aop\Support\JoinPoint;

use Rindow\Aop\JoinPointInterface;
use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Support\Signature;

class LabelJoinPoint extends AbstractJoinPoint
{
    public function __construct($target,$label)
    {
        $signature = new Signature(SignatureInterface::TYPE_LABEL,$label.':',null);
        parent::__construct(JoinPointInterface::ACTION_EXECUTION,$signature,$target);
        $this->setTarget($target);
    }
}