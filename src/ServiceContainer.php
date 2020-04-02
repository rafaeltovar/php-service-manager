<?php
namespace ServiceManager;

use Psr\Container\ContainerInterface;

class ServiceContainer
implements ContainerInterface
{
    protected $providerArgs;
    protected $providersServices;
    protected $enabledServices;
    protected $aliases; // Array for mapping services class with service id
    protected $config;
    protected $core;

    public function __construct(array $providers = [], array $aliases = [], ServiceProviderArgumentsInterface $providerArgs = null)
    {
        $this->providersServices   = [];
        $this->enabledServices     = [];
        $this->aliases             = $aliases;
        $this->providerArgs        = $providerArgs;

        foreach($providers as $prv)
            $this->add($prv);
    }

    /**
     * Get provider arguments
     **/
    protected function getProviderArguments()
    {
        return $this->providerArgs;
    }

    /**
     * Add an instance object like service
     **/
    public function addEnabledService($service, string $id)
    {
        $this->enabledServices[$id] = $service;
    }

    /**
     * Add a Service Provider
     **/
    public function add(string $providerClass, string $alias = null, string $id = null)
    {
        // check if class exists
        if(!class_exists($providerClass))
            throw new \Exception(sprintf("Service Provider Class '%s' doesnt exist.", $providerClass));

        $provider = $this->buildProvider($providerClass);
        $alias = isset($alias)? $alias: $provider->getServiceType();
        $id = isset($id)? $id: $provider->getServiceId();

        $this->addProvider($provider, $id);
        $this->addAlias($alias, $id);

        return $this;
    }

    /**
     * Get a Service
     **/
    public function get($id) {
        return $this->getEnabledService($id);
    }

    /**
     * Get a Service by alias
     **/
    public function getByAlias(string $alias)
    {
        if(!array_key_exists($alias, $this->aliases)) {
            $debug = debug_backtrace();
            throw new Exception\ServiceNotFoundException(sprintf("Alias '%s' not exist! [%s]", $alias, json_encode($debug)));
        }

        return $this->get($this->aliases[$alias]);
    }

    /**
     * Get Service Provider
     **/
    public function getProvider(string $id)
    {
        if(!$this->has($id)) {
            $debug = debug_backtrace();
            throw new Exception\ServiceNotFoundException(sprintf("Service Provider '%s' not exist! [%s]", $id, json_encode($debug)));
        }

        return $this->providersServices[$id];
    }

    /**
     * Instance a Class
     **/
    public function build(string $controller)
    {
        $args = func_get_args();
        array_shift($args);

        $builder = new ControllerBuilder($this, $controller, $args);
        return $builder->build();

    }

    protected function addProvider(ServiceProvider $provider, string $id)
    {
        $this->providersServices[$id] = $provider;
    }

    protected function addAlias(string $alias, $id)
    {
        if(!class_exists($alias))
            throw new \Exception(sprintf("Alias Class '%s' doesnt exist.", $alias));

        $this->aliases[$alias] = $id;
    }

    protected function buildProvider(string $providerClass)
    {
        return new $providerClass($this, $this->getProviderArguments());
    }

    protected function getEnabledService(string $id)
    {
        if(!$this->hasEnabledService($id))
        {
            $provider = $this->getProvider($id);

            $alias    = $provider->getServiceType();
            $service  = $provider->getService();

            if(get_class($service) != $alias) {
                $debug = debug_backtrace();
                throw new Exception\ServiceNotFoundException(sprintf("Service '%s' not is equal to alias '%s' [%s]", get_class($service), $alias, json_encode($debug)));
            }

            $this->addEnabledService($service, $id);
        }

        return $this->enabledServices[$id];
    }

    protected function hasEnabledService($id) : bool
    {
        return array_key_exists($id, $this->enabledServices);
    }

    protected function hasProvider($id) : bool
    {
        return array_key_exists($id, $this->providersServices);
    }

    public function has($id)
    {
        return $this->hasProvider($id) || $this->hasEnabledService($id);
    }
}
