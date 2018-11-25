<?php
namespace AcmeTest\Aop;

interface HaveVariadicParameterInterface
{
    public function foo(&$name=null, &...$options);
}
class HaveVariadicParameterInterfaceDecleredClass implements HaveVariadicParameterInterface
{
    public function foo(&$name=null, &...$options)
    {

    }
}
