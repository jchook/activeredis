# PHP ActiveRedis

ActiveRedis is a PHP library that brings relational model abstraction to [Redis](http://redis.io/).

* Lightweight
* Easily adapts to any Redis interface


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

The following examples do the exact same thing.

```php
<?php

// Create
$human = new Human();
$human->name = 'Wes';
$human->age  = 24;
$human->save();

// Create Alternate
$human = new Human(array(
	'name' => 'Wes',
	'age'  => 24
));
$human->save();

// Create Alternate
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