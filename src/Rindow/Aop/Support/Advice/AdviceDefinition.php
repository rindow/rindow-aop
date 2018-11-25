<?php
namespace Rindow\Aop\Support\Advice;

use Rindow\Aop\SignatureInterface;
use Rindow\Aop\Exception;

class AdviceDefinition
{
    protected $pointcutSignatures;
    protected $name;
    protected $type;
    protected $componentName;
    protected $method;
    protected $attributes;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setPointcutSignature($pointcutSignature)
    {
        $this->pointcutSignatures = $this->transformSignature($pointcutSignature);
    }

    protected function transformSignature($pointcutSignature)
    {
        if($pointcutSignature instanceof SignatureInterface) {
            return array($pointcutSignature->toString());
        } elseif(is_string($pointcutSignature)) {
            return array($pointcutSignature);
        } elseif(is_array($pointcutSignature)) {
            $refs = array();
            foreach ($pointcutSignature as $value) {
                $refs = array_merge($refs,$this->transformSignature($value));
            }
            return $refs;
        } else {
            throw new Exception\InvalidArgumentException('the pointcutSignature must be SignatureInterface or string or array.');
        }
    }

    public function getPointcutSignatures()
    {
        return $this->pointcutSignatures;
    }

    public function setComponentName($componentName)
    {
        $this->componentName = $componentName;
    }

    public function getComponentName()
    {
        return $this->componentName;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setConfig(array $config)
    {
        if(isset($config['type']))
            $this->type = $config['type'];
        if(isset($config['pointcut_ref']))
            $this->setPointcutSignature($config['pointcut_ref']);
        if(isset($config['method']))
            $this->method = $config['method'];
        if(isset($config['component']))
            $this->componentName = $config['component'];
        if(isset($config['name']))
            $this->name = $config['name'];
        if(isset($config['attributes'])) {
            if(!is_array($config['attributes']))
                throw new Exception\DomainException('"attributes" must be array.');
            $this->attributes = $config['attributes'];
        }
    }
}