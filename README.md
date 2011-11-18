# PHP ActiveRedis

[ActiveRedis](https://github.com/jchook/activeredis) is a PHP 5.3+ library that brings relational model abstraction to [Redis](http://redis.io/).

* Simple
* Lightweight
* Easily adapts to any PHP Redis interface, including [Predis](https://github.com/nrk/predis) and [PhpRedis](https://github.com/nicolasff/phpredis)

ActiveRedis synthesizes a new implementation of the [Active record pattern](http://en.wikipedia.org/wiki/Active_record_pattern), borrowing good features from other libraries, but incorporating the simplicity of Redis.

## Development

ActiveRedis is currently in its infancy.

However, it is being actively developed. Feel free to fork & join in the fun. Some planned features:

* Model Associations (nearly finished)
* Indexing (naive version in progress)
** Eventually indexing will be configurable, allowing the programmer to specify case-sensitivity, key filters, whether the indexed value is unique, which property to associate with the index (id by default of course), etc.
* Bubbling events

## Installation

1. Clone activeredis into your project, or add it as a submodule.
1. Add the following code to run once in your project:

```php
<?php

// Initialize
include 'activeredis/ActiveRedis.php';

// Configure Database Object
// In this example we use Predis, but you can use almost any PHP Redis interface.
// If you need to, customize ActiveRedis\Adapter to adapt weird Redis interfaces.
ActiveRedis\Database::adapt(new Predis\Client);

```

## Usage

### Creating New Model Classes

The model classes can be quite simple.

```php
<?php class Human extends ActiveRedis\Model {} ?>
```

### CRUD: Create, Read, Update, Delete

```php
<?php

// Create
$human = Human::create(array(
	'name' => 'Wes',
	'age' => 24,
));

// Retrieve
$human = Human::find(1);

// Update
$human->name = 'Wesley';
$human->save();

// Delete
$human->delete();

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

````

It's easy to build your own types of associations. Most of the association classes are only about 20 lines of PHP. See for yourself in [lib/Associations.php](https://github.com/jchook/activeredis/blob/master/lib/Associations.php "Read Associations.php").


### Behaviors

Behaviors allow you to easily inject functionality into models by attaching custom callbacks to model events. Below is a simple example that sets a custom value before a model is saved.

```php
<?php

class CustomBehavior extends ActiveRedis\Behavior 
{
	function attach($table) 
	{
		$table->bind('beforeSave', 'CustomBehavior::beforeSave');
	}
	
	function beforeSave($model)
	{
		$model->customValue = true;
	}
}
```

If you create your own Model class that extends ActiveRedis\Model, it's easy to trigger your own custom events via ```$this->trigger('eventName', $arguments');```.

### Indexes

Since redis does not provide any way to search, it is easy to index attributes. Indexing an attribute allows you to locate a model from the attribute value.

```php
<?php

class User extends ActiveRedis\Model {
	static $indexes = array('username');
}

// Example find by username
$username = $_POST['username'];
$user = User::find("username:$username");

```