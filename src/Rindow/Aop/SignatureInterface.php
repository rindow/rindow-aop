<?php
namespace Rindow\Aop;

interface SignatureInterface
{
    const TYPE_METHOD   = 1;
    const TYPE_PROPERTY = 2;
    const TYPE_LABEL    = 3;

    public function toString();
    public function getClassName();
    public function getMethod();
    public function getProperty();
}