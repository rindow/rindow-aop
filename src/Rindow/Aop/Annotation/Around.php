<?php
namespace Rindow\Aop\Annotation;

use Rindow\Aop\AdviceInterface;

class Around extends AbstractAdvice
{
    protected $type = AdviceInterface::TYPE_AROUND;
}