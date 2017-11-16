# PHP ActiveRedis

[ActiveRedis](https://github.com/jchook/activeredis) is a PHP 5.3+ library that brings relational model abstraction to [Redis](http://redis.io/).

* Simple
* Lightweight
* Extendable

ActiveRedis synthesizes a new, unorthodox implementation of the [Active record pattern](http://en.wikipedia.org/wiki/Active_record_pattern), borrowing good features from other libraries, but incorporating the simplicity of Redis.

## License

ActiveRedis is free to use under the [MIT License](http://www.opensource.org/licenses/MIT).


## Installation

1. Clone activeredis into your project, or add it as a submodule.
1. Add the following code to run once in your project:


```php
<?php

// Initialize
include 'activeredis/ActiveRedis.php';

// Connect to Redis
ActiveRedis\Database::connect('127.0.0.1:6379');

```

## Usage

### Creating New Model Classes

The model classes can be quite simple.

```php
<?php class Human extends ActiveRedis\Model {}
```

### CRUD: Create, Read, Update, Delete

```php
<?php

// Create
$human = Human::create([
	'id' => 1,
	'name' => 'Wes',
	'height' => '200cm',
]);

// Retrieve
$human = Human::find(1);

// Update
$human->name = 'Wesley';
$human->save();

// Delete
$human->delete();

```

### Indexes

Since Redis is NoSQL, you cannot use a WHERE clause to query for data. Instead, ActiveRedis provides Indexes which automatically index data in a customizable way.

```php
<?php

// Once the user is created...
$user = User::create(['name' => 'Wesley']);

// It is possible to find the user by name
$user = User::findBy(['name' => 'Wesley']);

```

### Associations

Associations are a very powerful addition to ActiveRedis that allows you to easily create meaningful relationships between your models. Below is a simple example where a User class is associated with potentially many Project objects. The format is simple, elastic, and easily configured. ActiveRedis is smart about namespaces and will usually guess the correct namespace for both your model & association classes.

```php
<?php

class User extends ActiveRedis\Model {
	static $associations = array(
		'HasMany Projects',
	);
}

class Project extends ActiveRedis\Model {
	static $associations = array(
		array('BelongsTo User', 'name' => 'owner'),
	)
}

// Instantiate models
$user = new User(array('username' => 'test_user', 'password' => 'test'));
$project = new Project(array('name' => 'ActiveRedis'));

// Associate models
$user->projects[] = $project;
$project->owner = $user;

// The "DeepSave" behavior will auto-save associated models if necessary
// The "AutoAssociate" behavior will associate these models if necessary
$user->save();

```

It's easy to build your own types of associations. Most of the association classes are only about 20 lines of PHP. See for yourself in [lib/Associations.php](https://github.com/jchook/activeredis/blob/master/lib/Associations.php "Read Associations.php").


### Behaviors

Behaviors allow you to easily inject functionality into models by attaching custom callbacks to model events. Below is a simple example that sets a custom value before a model is saved.

```php
<?php

class CustomModel extends ActiveRedis\Model {
	static $behaviors = array('CustomBehavior');
}

class CustomBehavior extends ActiveRedis\Behavior {

	function attach($table) {
		$table->bind('beforeSave', 'CustomBehavior::beforeSave');
	}

	function beforeSave($model) {
		$model->customValue = true;
	}
}
```

If you create your own Model class that extends ActiveRedis\Model, it's easy to trigger your own custom events via `$this->trigger('eventName', $arguments');`.
