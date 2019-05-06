<?php
namespace Rindow\Aop\Support\Intercept\CodeStore;

interface CodeStore
{
    public function getInterceptorStoreKey($interceptorClassName);
    public function saveCode($key, $code);
    public function loadCode($key);
    public function hasCode($key);
}
