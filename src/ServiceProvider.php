<?php
namespace RafaelTovar\ServiceManager;

abstract class ServiceProvider
{
    protected $serviceContainer;
    protected $providerArguments;

    abstract public function getService();
    abstract public function getServiceId(): string;
    abstract public function getServiceType(): string;

    public function __construct(ServiceContainer $serviceContainer, ServiceProviderArgumentsInterface $providerArguments = null)
    {
        $this->serviceContainer = $serviceContainer;
        $this->providerArguments = $providerArguments;
    }

    protected function getServiceContainer(): ServiceContainer
    {
        return $this->serviceContainer;
    }

    protected function getProviderArguments(): ServiceProviderArgumentsInterface
    {
        return $this->providerArguments;
    }
}
