<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ActiveRedis\Association\AbstractAssociation;
use ActiveRedis\Database;
use ActiveRedis\Network;
use ActiveRedis\Model;
use ActiveRedis\Table;
use ActiveRedis\Connection;

/**
 * @covers Database
 */
final class DatabaseTest extends TestCase
{
	public function setUp()
	{
		$behavior = [
			'Identify',
			'Timestamp',
		];

		$this->db = $db = new Database([
			'schema' => [
				'namespaces' => ['MyApp'],
				'tables' => [

					'Project' => [
						'indexes' => ['user_id'],
						'associations' => [
							'owner' => 'BelongsTo User',
							'roles' => 'HasMany Role',
						],
					],

					'Role' => [
						'indexes' => ['project_id', 'user_id'],
						'associations' => [
							'project' => 'BelongsTo Project',
							'user' => 'BelongsTo User',
						],
					],

					'User' => [
						'indexes' => ['email'],
						'associations' => [
							'roles' => 'HasMany Role',
						],
					],
				],
			],
		]);

		Network::set('default', $this->db);
	}

	public function testParsesConfig()
	{
		$db = $this->db;
		$projects = $db->getSchema()->getTable(Project::class);
		$this->assertInstanceOf(Table::class, $projects);

		$assocs = $projects->getAssociations();
		foreach ($assocs as $assoc) {
			$this->assertInstanceOf(AbstractAssociation::class, $assoc);
		}
	}

	public function testGetKey()
	{
		$db = $this->db;
		$key = $db->getSchema()->getTable(Project::class)->getKey(['id' => 'test']);
		$this->assertEquals($key, 'db:Project?id=test');

		$key = $db->getSchema()->getTable(Project::class)->getKey(['owner_id' => 'test', 'status' => 'done']);
		$this->assertEquals($key, 'db:Project?owner_id=test&status=done');
	}

	public function testSetsModel()
	{
		$project = new Project();
		$project->id = $id = 'test';
		$project->name = $name = 'Test';

		// Store it
		$project->save();

		// Retrieve it
		$next = $project::table()->getModel($project->getDbKey());

		$this->assertInstanceOf(Project::class, $next);
		$this->assertEquals($next->id, $id);
		$this->assertEquals($next->name, $name);
	}

	// public function testIndexes()
	// {
	// 	// TODO: this doesn't belong here but I just wanna see if indexes work
	// 	$user = new User();
	// 	$user->id = 1;
	// 	$user->save();
	//
	// 	$project = new Project();
	// 	$project->id = 1;
	// 	$project->name = 'Project 1';
	// 	// $project->owner = $user;
	//
	// }
}

class Project extends Model {}

class Role extends Model {}

class User extends Model {}
