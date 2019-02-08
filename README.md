# ServiceManager for PHP

Simple, light, minimum service manager and dependency injection for PHP.


## Installation

### Composer

Add to `composer.json` of your project this lines.

```
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/rafaeltovar/php-service-manager"
        }
    ],
    "require": {
        // OTHER REQUERIMENTS
        "rafaeltovar/php-service-manager": "dev-master"
    }
```

## Features

- [x] Dependency injection
- [x] Service container
- [x] Providers
- [x] Auto initialize the service when is called
- [x] Alias

## Instructions

### Service container

The **service container** is the main service controller for manage the service queue.

```php
// myproject.php
use MyServiceAProvider;

$serviceContainer = new \ServiceManager\ServiceContainer(
                            [MyServiceAProvider::class]
                        );
```

### Service Providers

This is my service.

```php
// MyServiceA.php
class MyServiceA
{
    public function test()
    {
        echo "Working.";
    }
}
```

This is my service **provider**.

```php
// MyServiceAProvider.php
class MyServiceAProvider
extends \ServiceManager\ServiceProvider
{
    /**
     * This method return the service class name (mandatory)
     **/
    public function getServiceType() : string
    {
        return MyServiceA::class;
    }

    /**
     * This method return the identification of the service
     * into Service Container (mandatory)
     **/
    public function getServiceId(): string
    {
        return "my-service-a";
    }

    /**
     * This method return the service (mandatory)
     **/
    public function getService()
    {
        return new MyServiceA();
    }
}
```

#### Passing custom arguments to the provider

We need an `\ServiceManager\ServiceProviderArgumentsInterface`. For example:

```php
// MyCustomArguments.php
class MyCustomArguments
implements \ServiceManager\ServiceProviderArgumentsInterface
{
    protected $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger() : \Psr\Log\LoggerInterface
    {
        return $this->logger;
    }
}
```

My example service with argument:

```php
// MyServiceDebug.php
class MyServiceDebug
{
    protected $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function write(string $text)
    {
        $this->logger->debug($text);
    }
}
```

My example service provider:

```php
// MyServiceDebugProvider.php
class MyServiceDebugProvider
extends \ServiceManager\ServiceProvider
{
    public function getServiceType() : string
    {
        return MyServiceDebug::class;
    }

    public function getServiceId(): string
    {
        return "debug";
    }

    public function getService()
    {   
        $logger = $this->getProviderArguments()->getLogger();
        return new MyServiceDebug($logger);
    }
}
```

Initializing my service container:

```php
// myproject.php
use MyServiceDebugProvider,
    MyServiceAProvider;

use MyCustomArguments;

//...
$serviceContainer = new \ServiceManager\ServiceContainer(
                            [
                                MyServiceAProvider::class,
                                MyServiceDebugProvider::class
                            ],
                            [],
                            new MyCustomArguments($myLogger)
                        );
```

### Getting a service

```php
$serviceContainer->get("my-service-a")->test();
```

```
// result:
Working.
```

## Dependency injection

```php
// MyServiceB.php
class MyServiceB
{
    protected $a;

    public function __construct(MyServiceA $a)
    {
        $this->a = $a;
    }
    public function test()
    {
        $this->a->test();
    }
}
```

```php
use MyServiceB;

//...
$myB = $serviceContainer->build(MyServiceB::class);
$myB->test();
```

```
// result:
Working.
```

### Alias

*Work in progress*
