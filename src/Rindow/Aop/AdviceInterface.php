<?php
namespace Rindow\Aop;

interface AdviceInterface
{
    const TYPE_BEFORE          = 'before';
    const TYPE_AFTER           = 'after';
    const TYPE_AFTER_RETURNING = 'after-returning';
    const TYPE_AFTER_THROWING  = 'after-throwing';
    const TYPE_AROUND          = 'around';
}
