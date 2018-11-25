<?php
namespace Rindow\Aop\Annotation;

use Rindow\Aop\AdviceInterface;

class Before extends AbstractAdvice
{
    protected $type = AdviceInterface::TYPE_BEFORE;
}
