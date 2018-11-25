<?php
namespace Rindow\Aop\Annotation;

use Rindow\Stdlib\Entity\AbstractPropertyAccess;
use Rindow\Aop\AdviceInterface;

/**
* The annotated method will be used as advice.
*
* @Annotation
* @Target({ METHOD })
*/
abstract class AbstractAdvice extends AbstractPropertyAccess
{
    public $value;
    public $pointcut;
    protected $type;

    public function getType()
    {
        return $this->type;
    }
}