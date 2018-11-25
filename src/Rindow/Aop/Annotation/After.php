<?php
namespace Rindow\Aop\Annotation;

use Rindow\Aop\AdviceInterface;

class After extends AbstractAdvice
{
    protected $type = AdviceInterface::TYPE_AFTER;
}
