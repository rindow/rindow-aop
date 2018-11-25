<?php
namespace Rindow\Aop\Annotation;

use Rindow\Aop\Support\Pointcut\Pointcut as PointcutDefinition;

/**
* The annotated method will be used as pointcut.
*
* @Annotation
* @Target({ METHOD })
*/
class Pointcut extends PointcutDefinition
{
}