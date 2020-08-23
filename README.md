# Hotwire

## Installation

```shell
composer require jaz303/hotwire
```

## Usage

### Basic Example

```php
<?php
require 'AppConfig.php';
require 'UserEditor.php';

// Create a new container
$C = new Hotwire\Container();

$config = new AppConfig;
$config->userEditorConfig = [
    "key" => "value"
];

// Register a singleton instance. The same object will be returned for all
// requests.
$C->registerSingleton('config', $config);

// Register a singleton factory. A single PDO instance will be created the
// first time it's requested.
$C->registerSingleton('pdo', static function() {
    return new PDO("...");
});

// Register a factory function. This registration is not a singleton so
// each request to the container will create a new instance.
$C->register(UserEditor::class, static function($C) {
    // Factory function receives an instance of the container.
    // In here we request the UserEditor dependencies and use
    // them to assemble an instance.
    
    // Retrieve PDO singleton via magic property
    $pdo = $C->pdo;

    // Get the app config via the PSR Container interface
    $config = $C->get('config');

    return new UserEditor($C->pdo, $config->userEditorConfig);
});

// Create a UserEditor instance
$userEditor = $C->get(UserEditor::class);
```

### Lazy Dependencies

```php
<?php
class MyClass {
    private $dep;
    
    // $lazyExpensiveDependency is a callable that will create an
    // on-demand instance of ExpensiveDep. Successive calls will
    // return the same instance.
    public function __construct($lazyExpensiveDependency) {
        $this->dep = $lazyExpensiveDependency;
    }

    public function doSomethingWithDependency() {
        // Create instance of dependency.
        $instance = ($this->dep)();
        $instance->doWork();
    }
}


$C->register(ExpensiveDep::class, static function() {
    $instance = new ExpensiveDep;
    // ... do expensive set up ...
    return $instance;
});

$C->register(MyClass::class, static function($C) {
    return new MyClass($C->lazy(ExpensiveDep::class));
});
```

### Factory Dependencies