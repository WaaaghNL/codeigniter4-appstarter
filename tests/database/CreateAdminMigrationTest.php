<?php

declare(strict_types=1);

use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class CreateAdminMigrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = ['App', 'CodeIgniter\Shield', 'CodeIgniter\Settings'];

    public function testMigrationsDoNotSeedDemoAdmin(): void
    {
        $userModel = model(UserModel::class);

        $demo = $userModel->where('username', 'admin')->first();

        $this->assertNull($demo);
    }
}
