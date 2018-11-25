<?php
namespace Rindow\Aop;

class Module
{
    public function getConfig()
    {
        return array(
            'module_manager' => array(
                'aop_manager' => 'Rindow\\Aop\\AopManager',
            ),
        );
    }
}
