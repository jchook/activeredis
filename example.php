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

$user = User::create();
$user->projects[] = Project::create();

?>