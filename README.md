# PHP ActiveRedis

ActiveRedis is a PHP 5.3+ library that brings relational model abstraction to [Redis](http://redis.io/).

* Lightweight
* Easily adapts to any PHP Redis interface


## Development

ActiveRedis is currently in its infancy.

However, it is being actively developed. Feel free to fork & join in the fun. There are many planned features, including:

* Relationships
* Indexing
* Dynamic getters/setters
* Better connection management

## Installation

1. Clone activeredis into your project, or add it as a submodule.
1. Add the following code to run once in your project:

```php
<?php

// Autoloader
include 'activeredis/lib/Autoload.php';
ActiveRedis\Autoload::register();

// Configure Database Object
// In this example we use Predis, but you can use almost any PHP Redis interface.
// If you need to, customize ActiveRedis\Adapter to adapt weird Redis interfaces.
ActiveRedis\Database::adapt(new Predis\Client);

```

## Usage

### Creating New Model Classes

The model classes can be extremely simple.

```php
<?php class Human extends ActiveRedis\Model {} ?>
```

### CRUD: Create, Read, Update, Delete

```php
<?php

// Create
$human = Human::create(array(
	'name' => 'Wes',
	'age'  => 24
));

// Retrieve
$human = Human::find($id);

// Update
$human->name = 'Wesley';
$human->save();

// Delete
$human->delete();

```