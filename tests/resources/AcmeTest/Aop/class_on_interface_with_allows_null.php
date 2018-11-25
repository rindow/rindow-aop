<?php
namespace AcmeTest\Aop;

interface HaveAllowsNullParameterInterface
{
    public function foo(string &$name=null, int &...$options):?string;
}
class HaveAllowsNullParameterInterfaceDecleredClass implements HaveAllowsNullParameterInterface
{
    public function foo(string &$name=null, int &...$options):?string
    {
    	return 'a';
    }
}
