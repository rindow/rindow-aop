<?php
namespace AcmeTest\Aop;

interface HaveTypeParameterInterface
{
    public function foo(string &$name=null, int &...$options):string;
}
class HaveTypeParameterInterfaceDecleredClass implements HaveTypeParameterInterface
{
    public function foo(string &$name=null, int &...$options):string
    {
    	return 'a';
    }
}
