<?php
namespace Hotwire;

class FactoryRegistration {
    private $container;
    private $factory;
    
    public function __construct($container, $factory) {
        $this->container = $container;
        $this->factory = $factory;
    }

    public function __invoke() { return ($this->factory)($this->container); }
    public function lazy() { return new DeferredInstanceRegistration($this->container, $this->factory); }
    public function factory() { return $this; }
}

class InstanceRegistration {
    private $instance;

    public function __construct($instance) {
        $this->instance = $instance;
    }

    public function __invoke() { return $this->instance; }
    public function lazy() { return $this; }
    public function factory() { throw new \Exception("Can't create a factory for an instance"); }
}

class DeferredInstanceRegistration {
    private $container;
    private $factory;
    private $instantiated = false;
    private $instance;

    public function __construct($container, $factory) {
        $this->container = $container;
        $this->factory = $factory;
    }

    public function __invoke() {
        if (!$this->instantiated) {
            $this->instance = ($this->factory)($this->container);
            $this->instantiated = true;
        }
        return $this->instance;
    }

    public function lazy() { return $this; }
    public function factory() { throw new \Exception("Can't create a factory for an instance"); }
}

class Container implements \Psr\Container\ContainerInterface {
    private $registrations = [];
    
    public function register($name, $factory) {
        $this->registrations[$name] = new FactoryRegistration($this, $factory);
    }

    public function registerSingleton($name, $thing) {
        if (is_callable($thing)) {
            $this->registrations[$name] = new DeferredInstanceRegistration($this, $thing);
        } else {
            $this->registrations[$name] = new InstanceRegistration($thing);
        }
    }

    public function has($key) {
        return isset($this->registrations[$key]);
    }

    public function get($key) {
        return $this->find($key)();
    }

    public function lazy($key) {
        return $this->find($key)->lazy();
    }

    public function factory($key) {
        return $this->find($key)->factory();
    }

    public function __get($key) {
        return $this->get($key);
    }

    private function find($k) {
        if (!isset($this->registrations[$k])) {
            throw new RegistrationNotFoundException();
        }
        return $this->registrations[$k];
    }
}
