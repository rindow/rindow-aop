<?php
namespace Rindow\Aop\Annotation;

use Rindow\Aop\AdviceInterface;

class AfterReturning extends AbstractAdvice
{
    protected $type = AdviceInterface::TYPE_AFTER_RETURNING;
}