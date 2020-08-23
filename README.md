# Hotwire

## Installation

```shell
composer require jaz303/hotwire
```

## Usage

### Basic Example

```php
<?php
require 'UserEditor.php';

// Create a new container
$C = new Hotwire\Container();

// Register a singleton factory. A single PDO instance will be created the
// first time its needed.
$C->registerSingleton('pdo', static function() {
    return new PDO("...");
});

// Register a factory function. This registration is not a singleton so a
// new instance will be created every time it is requested from the
// container.
$C->register(UserEditor::class, static function($C) {
    // Factory function receives an instance of the container.
    // Here the PDO singleton is being retrieved by magic property;
    // $C->get('pdo') is equivalent.
    return new UserEditor($C->pdo);
});

// Create a UserEditor instance
$userEditor = $C->get(UserEditor::class);
```
