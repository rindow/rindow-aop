<?php
namespace Rindow\Aop\Annotation;

use Rindow\Aop\AdviceInterface;

class AfterThrowing extends AbstractAdvice
{
    protected $type = AdviceInterface::TYPE_AFTER_THROWING;
}