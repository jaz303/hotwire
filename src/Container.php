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
    public function factory() { throw new \Exception("Can't create a factory for a deferred instance"); }
}

class Container implements \Psr\Container\ContainerInterface {
    private $registrations = [];
    
    public function register(string $key, callable $factory) {
        $this->registrations[$key] = new FactoryRegistration($this, $factory);
    }

    public function registerSingleton(string $key, $thing) {
        if (is_callable($thing)) {
            $this->registrations[$key] = new DeferredInstanceRegistration($this, $thing);
        } else {
            $this->registrations[$key] = new InstanceRegistration($thing);
        }
    }

    public function has($key) {
        return isset($this->registrations[$key]);
    }

    public function get($key) {
        return $this->find($key)();
    }

    public function lazy(string $key) {
        return $this->find($key)->lazy();
    }

    public function factory(string $key) {
        return $this->find($key)->factory();
    }

    public function __get(string $key) {
        return $this->get($key);
    }

    private function find($k) {
        if (!isset($this->registrations[$k])) {
            throw new RegistrationNotFoundException();
        }
        return $this->registrations[$k];
    }
}
