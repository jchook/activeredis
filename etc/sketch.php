<?php

namespace MyApp;

use ActiveRecord\Association\HasMany;
use ActiveRecord\Association\HasOne;
use ActiveRecord\Association\BelongsTo;
use ActiveRecord\Behavior\AutoTimestamp;
use ActiveRecord\Database;
use ActiveRecord\Provider;
use ActiveRecord\Table;
use ActiveRecord\Model;
new ActiveRecord\StorageStrategy\AdvancedStrategy;

class Project extends Model {}

class Role extends Model {}

class User extends Model {}

// This is raw creation of the provided database
Provider::provide('default', new Database([
	'host' => '127.0.0.1',
	'tables' => [
		Project::class => new Table([
			'associations' => [
				'roles' => new HasMany(Project::class, Role::class),
				'owner' => new HasMany(Project::class, User::class, [
					'foreignKey' => 'user_id',
				]),
			],
		]),
		Role::class => new Table([
			'associations' => [
				'project' => new BelongsTo(Role::class, Project::class),
				'user' => new BelongsTo(Role::class, User::class),
			],
		]),
		User::class => new Table([
			'behavior' => [new AutoTimestamp()],
			'strategy' => new AdvancedStrategy(),
			'associations' => [
				'roles' => new HasMany(User::class, Role::class),
			],
		]),
	],
]));

// One advantage to NOT doing it this way is that configured components
// can be loaded "just in time" or "as needed" instead of "up-front". This
// could be a huge advantage for larger sites with many dbs and models.

// With the flat config style, we can autoload as needed and store as JSON
$config = [
	'default' => [
		// In case a class is not found, these namespaces are checked
		'namespaces' => [
			'MyApp',
		],
		'class' => 'ActiveRedis\Database',
		'host' => '127.0.0.1',
		'tables' => [
			'Project' => [
				'associations' => [
					'owner' => [
						'class' => 'BelongsTo',
						'model' => 'User',
						'foreignKey' => 'user_id', // would be owner_id by default
					],
					'roles' => 'HasMany User', // shorthand config
				],
			],
			'Role' => [
				'associations' => [
					'project' => 'BelongsTo Project',
					'user' => 'BelongsTo User',
				],
			],
			'User' => [
				'behavior' => ['AutoTimestamp'],
				'associations' => [
					'roles' => 'HasMany Role',
				],
			],
		]
	]
];

// This will set the config property of the Database
Provider::provide('default', new Database($config));


// IDEA: strategies -- coordinated groups of behaviors.
// possibly could just be rolled into a "CoordinatedBehavior" class that would act as a single behavior


$user = new User(array(
	'name' => 'wes',
));
$user->name = 'unit test';
$user->projects[] = Project::create();
$user->save();

$read = User::find(array('name' => 'unit test'));
print_r($read->toArray());

$read->name = 'other name';
$read->save();

$read = User::find(array('name' => 'other name'));
print_r($read->toArray());

