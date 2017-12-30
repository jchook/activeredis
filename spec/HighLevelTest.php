<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ActiveRedis\Association\AbstractAssociation;
use ActiveRedis\Database;
use ActiveRedis\Network;
use ActiveRedis\Model;
use ActiveRedis\Table\TableInterface;
use ActiveRedisSpec\Support\MockConnection;

// Models for this test
class Project extends Model {}
class Role extends Model {}
class User extends Model {}

/**
 * @covers Database
 */
final class HighLevelTest extends TestCase
{
	public function setUp()
	{
		$behavior = [
			'Identify',
			'Timestamp',
		];

		$this->db = $db = new Database([
			// 'connection' => new MockConnection(),
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

		// Clear the entire DB
		$db->flushdb();
	}

	public function testParsesConfig()
	{
		$db = $this->db;
		$projectTable = $db->getSchema()->getTable(Project::class);
		$this->assertInstanceOf(TableInterface::class, $projectTable);

		$assocs = $projectTable->getAssociations();
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
		$next = $project::table()->getModel($project->getPrimaryKey());

		$this->assertInstanceOf(Project::class, $next);
		$this->assertEquals($next->id, $id);
		$this->assertEquals($next->name, $name);
	}

	public function testIndexes()
	{
		$user = new User();
		$user->id = 1;

		$project = new Project();
		$project->id = 1;
		$project->name = 'Project 1';
		$project->owner = $user;
		$project->save();
		$user->save();

		$this->assertTrue(true);

		$projects = Project::findAllBy(['owner_id' => $user->id]);
		// $nextProject = next($projects);
		// $this->assertEquals($nextProject->id, $project->id);
	}
}
