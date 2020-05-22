# ServiceManager for PHP

Simple, light, minimum service manager and dependency injection for PHP.


## Installation

### Composer

Execute:

```
composer require rafaeltovar/php-service-manager
```

## Features

- [x] Dependency injection
- [x] Service container
- [x] Providers
- [x] Singleton strategy
- [x] Initialized only if service is called
- [x] Alias

## Documentation

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

Sometimes We need pass some arguments to the provider for initialize the service, like the config or logger, for example. In this case we need implements `\ServiceManager\ServiceProviderArgumentsInterface`.

`MyCustomArguments` will have the arguments it needs.

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
// MyController.php
class MyController
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
use MyController;

//...
$myCtrl = $serviceContainer->build(MyController::class);
$myCtrl->test();
```

```
// result:
Working.
```

### Custom builders

Sometime our controller need other controller or data, not a service. In those cases we can create a custom constructor implements `ControllerBuilderCreateInterface`.

```php
// MyController.php
use ServiceManager\ServiceContainer,
    ServiceManager\ControllerBuilderCreateInterface;

class MyController
implements ControllerBuilderCreateInterface
{
    protected $public;

    public function __construct(string $publicFolder)
    {
        $this->public = $publicFolder;
    }

    public static function create(ServiceContainer $services)
    {
        return new MyController(
            $services->get('config')->get("PUBLIC_FOLDER")
        );
    }
}
```

Now we can build our controller. The service container will call to `create` method if implements `ControllerBuilderCreateInterface`.

```php
use MyController;

//...
$myCtrl = $serviceContainer->build(MyController::class);
```

### Alias

Other times we need to work with interfaces, we can use aliases to obtain the services that implement these interfaces.

```php
// myproject.php
use MyServiceDebugProvider,
    MyServiceAProvider;

use MyCustomArguments;

//...
$serviceContainer = new \ServiceManager\ServiceContainer(
                            [
                                MyLoggerProvider::class,
                            ],
                            [ // aliases
                                // interface => service id
                                \Psr\Log\LoggerInterface::class => "logger"
                            ]
                        );
```

```php
// MyServiceDebugProvider.php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MyLoggerProvider
extends \ServiceManager\ServiceProvider
{
    public function getServiceType() : string
    {
        return Logger::class;
    }

    public function getServiceId(): string
    {
        return "logger";
    }

    public function getService()
    {   
        $log = new Logger('name');
        $log->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));
        return $log;
    }
}
```

Now we can build our controller with interface dependency.

```php
// MyController.php
class MyController
{
    protected $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger; // this logger will be \Monolog\Logger
    }

    // ...
}
```

```php
use MyController;

//...
$myCtrl = $serviceContainer->build(MyController::class);
```
