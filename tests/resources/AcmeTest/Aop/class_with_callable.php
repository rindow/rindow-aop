<?php
namespace AcmeTest\Aop;

interface TestArrayCallableInterface
{
    public function foo(array $array,callable $callable);
}
class HaveArrayCallableClass implements TestArrayCallableInterface
{
    public function foo(array $array,callable $callable)
    {

    }
}
