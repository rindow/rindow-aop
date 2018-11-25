<?php
namespace Rindow\Aop\Support;

use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Exception;

class Signature implements SignatureInterface
{
    protected $type;
    protected $className;
    protected $subName;
    protected $string;

    public function __construct($type, $className, $subName)
    {
        $this->type = $type;
        $this->className = $className;
        $this->subName = $subName;
        $this->convertString();
    }

    protected function convertString()
    {
        if($this->type==SignatureInterface::TYPE_METHOD)
            $this->string = $this->className.'::'.$this->subName.'()';
        else if($this->type==SignatureInterface::TYPE_PROPERTY)
            $this->string = $this->className.'::$'.$this->subName;
        else if($this->type==SignatureInterface::TYPE_LABEL)
            $this->string = $this->className;
        else
            throw new Exception\DomainException('Illegal type');
    }

    public function toString()
    {
        return $this->string;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getMethod()
    {
        if($this->type!=SignatureInterface::TYPE_METHOD)
            throw new Exception\DomainException('Illegal type');
        return $this->subName;
    }

    public function getProperty()
    {
        if($this->type!=SignatureInterface::TYPE_PROPERTY)
            throw new Exception\DomainException('Illegal type');
        return $this->subName;
    }
}