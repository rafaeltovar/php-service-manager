<?php
namespace ServiceManager;

class ControllerBuilder {
    const AUTOCREATE_METHOD = "create";

    protected $args;
    protected $container;
    protected $class;

    public function __construct(ServiceContainer $container, string $controller, array $args = [])
    {
        $this->container = $container;
        $this->args = $args;
        if(!class_exists($controller))
            throw new \Exception(sprintf("Controller Class '%s' doesn't exist.", $controller));

        $this->class = new \ReflectionClass($controller);
    }

    public function getConstructArgsTypes()
    {
        $construct = $this->class->getConstructor();
        return isset($construct)? array_map(function($param) { return $param->getType()->__toString(); }, $construct->getParameters()) : [];
    }

    public function build()
    {
        if($this->class->implementsInterface(ControllerBuilderCreateInterface::class))
            return call_user_func_array([$this->class->getName(), self::AUTOCREATE_METHOD], [$this->container]);

        $types = $this->getConstructArgsTypes();
        $parameters = array_map([$this, 'getParameter'], $types);

        return $this->class->newInstanceArgs($parameters);
    }

    protected function getParameter($type)
    {
        $param = $this->getArgByType($type);

        if(isset($param))
            return $param;

        $param = $this->container->getByAlias($type);

        if(!isset($param))
            throw new \Exception(sprintf("Type '%s' is not avalaible for '%s' class", $type, $this->class->getName()));

        return $param;
    }

    protected function getArgByType($type)
    {
        $args = array_filter($this->args, function($arg) use($type) { return get_class($arg) == $type || $arg instanceOf $type;; });

        if(sizeof($args) == 0)
            return null;

        return array_shift($args);
    }
}
