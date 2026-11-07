<?php

declare(strict_types=1);

use App\Libraries\ShieldGroupAuthorization;
use App\Exceptions\PageForbiddenException;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\AuthGroups;
use Tests\Support\ShieldTestHelpers;

/**
 * @internal
 */
final class ShieldGroupAuthorizationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = ['App', 'CodeIgniter\Shield', 'CodeIgniter\Settings'];

    private function authorization(): ShieldGroupAuthorization
    {
        return new ShieldGroupAuthorization(config(AuthGroups::class), model(UserModel::class));
    }

    public function testAdminCannotAssignSuperadminGroup(): void
    {
        $admin = ShieldTestHelpers::createUser('admin1', 'admin1@example.com', 'Password1!', 'admin');
        $target = ShieldTestHelpers::createUser('victim', 'victim@example.com', 'Password1!', 'user');

        $this->expectException(PageForbiddenException::class);

        $this->authorization()->resolveGroupsForSync($admin, $target, ['user', 'superadmin']);
    }

    public function testAdminCannotEscalateSelf(): void
    {
        $admin = ShieldTestHelpers::createUser('admin2', 'admin2@example.com', 'Password1!', 'admin');

        $this->expectException(PageForbiddenException::class);

        $this->authorization()->resolveGroupsForSync($admin, $admin, ['admin', 'superadmin']);
    }

    public function testSuperadminCanAssignSuperadmin(): void
    {
        $super = ShieldTestHelpers::createUser('super1', 'super1@example.com', 'Password1!', 'superadmin');
        $target = ShieldTestHelpers::createUser('promote', 'promote@example.com', 'Password1!', 'user');

        $resolved = $this->authorization()->resolveGroupsForSync($super, $target, ['user', 'superadmin']);

        $this->assertContains('superadmin', $resolved);
    }

    public function testForgedGroupNamesAreStripped(): void
    {
        $super = ShieldTestHelpers::createUser('super2', 'super2@example.com', 'Password1!', 'superadmin');
        $target = ShieldTestHelpers::createUser('user3', 'user3@example.com', 'Password1!', 'user');

        $resolved = $this->authorization()->resolveGroupsForSync(
            $super,
            $target,
            ['user', 'not-a-real-group'],
        );

        $this->assertSame(['user'], $resolved);
    }

    public function testCannotRemoveLastSuperadmin(): void
    {
        $super = ShieldTestHelpers::createUser('onlysuper', 'only@example.com', 'Password1!', 'superadmin');

        $this->expectException(PageForbiddenException::class);

        $this->authorization()->resolveGroupsForSync($super, $super, ['user']);
    }
}
