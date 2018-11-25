<?php
namespace Rindow\Aop;

use Rindow\Container\ComponentScanner;

interface AopPluginInterface
{
    public function setConfig($config);
    public function attachScanner(ComponentScanner $componentScanner);
}
