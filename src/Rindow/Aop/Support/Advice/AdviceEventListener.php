<?php
namespace Rindow\Aop\Support\Advice;

use Rindow\Event\EventListener;

class AdviceEventListener extends EventListener
{
    protected $advice;
    public function __construct(AdviceDefinition $advice)
    {
        parent::__construct(null,$advice->getComponentName(),$advice->getMethod());
        $this->advice = $advice;
    }

    public function getAdvice()
    {
        return $this->advice;
    }
}
