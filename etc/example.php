<?php

class Project extends ActiveRedis\Model {
	static $associations = array(
		'BelongsTo User'
	);
}

class User extends ActiveRedis\Model {
	static $indexes = array(
		'name',
	);
	static $associations = array(
		'HasMany Project'
	);
}

$db = ActiveRedis\Database::connect('127.0.0.1:6379');

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

