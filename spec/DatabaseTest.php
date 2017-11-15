<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ActiveRedis\Association\AbstractAssociation;
use ActiveRedis\Database;
use ActiveRedis\Provider;
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
			'namespaces' => ['MyApp'],
			'host' => '127.0.0.1',
			'tables' => [

				'Project' => [
					'associations' => [
						'owner' => [
							'class' => 'BelongsTo',
							'model' => 'User',
							'foreignKey' => 'user_id', // would be owner_id by default
						],
						'roles' => 'HasMany Role', // shorthand config
					],
					'behavior' => $behavior + [
						'indexes' => [
							'attributes' => ['user_id']
						],
					],
				],

				'Role' => [
					'associations' => [
						'project' => 'BelongsTo Project',
						'user' => 'BelongsTo User',
					],
					'behavior' => $behavior + [
						'indexes' => [
							'attributes' => ['project_id', 'user_id'],
						],
					],
				],

				'User' => [
					'associations' => [
						'roles' => 'HasMany Role',
					],
					'behavior' => $behavior,
				],
			]
		]);

		Provider::setDatabase('default', $this->db);
	}

	public function testParsesConfig()
	{
		$db = $this->db;
		$projects = $db->getTable(Project::class);
		$this->assertInstanceOf(Table::class, $projects);

		$assocs = $projects->getAssociations();
		foreach ($assocs as $assoc) {
			$this->assertInstanceOf(AbstractAssociation::class, $assoc);
		}
	}

	public function testSetsModel()
	{
		$project = new Project();
		$project->id = 'test';
		$project->name = 'Test';
		$project->save();

		$key = $project->getDbKey();

		$next = $this->db->getModel($key);
		$this->assertInstanceOf(Project::class, $next);
		$this->assertEquals($project->name, $next->name);
	}

	// public function testIndexes()
	// {
	//
	// }
}

class Project extends Model {}

class Role extends Model {}

class User extends Model {}
