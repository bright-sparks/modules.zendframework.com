<?php

namespace ZfModule\Factory\View\Helper;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfModule\View\Helper\TotalModules;

class TotalModulesFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sm = $serviceLocator->getServiceLocator();
        $moduleMapper = $sm->get('zfmodule_mapper_module');

        return new TotalModules($moduleMapper);
    }
}