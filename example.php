<?php

include 'ActiveRedis.php';


class Project extends ActiveRedis\Model {
	static $associations = array(
		'BelongsTo User'
	);
}

class User extends ActiveRedis\Model {
	static $associations = array(
		'HasMany Project'
	);
}

$user = new User;

// $user->projects = new Project(array('name' => 'CowFight'));
// $user->projects = new Project(array('name' => 'ActiveRedis'));

// $user->test = array('whatever');

$user->save();

?>