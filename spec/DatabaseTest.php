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
 * @covers Email
 */
final class DatabaseTest extends TestCase
{
	public function setUp()
	{
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
		$project->name = 'Test';
		$project->save();

		$key = $project->getDbKey();

		$next = $this->db->getModel($key);
		$this->assertInstanceOf(Project::class, $next);
		$this->assertEquals($project->name, $next->name);
	}
}

class Project extends Model {}

class Role extends Model {}

class User extends Model {}
