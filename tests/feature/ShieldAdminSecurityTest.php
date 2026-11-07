<?php

declare(strict_types=1);

use App\Exceptions\PageForbiddenException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\ShieldTestHelpers;

/**
 * @internal
 */
final class ShieldAdminSecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = ['App', 'CodeIgniter\Shield', 'CodeIgniter\Settings'];

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        if (auth()->loggedIn()) {
            auth()->logout();
        }
    }

    private function loginAs(string $email, string $password): void
    {
        $result = $this->call('POST', 'login', [
            'email'    => $email,
            'password' => $password,
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirect();
    }

    public function testGetDeleteRouteIsNotRegistered(): void
    {
        ShieldTestHelpers::createUser('super', 'super@example.com', 'Password1!', 'superadmin');
        $victim = ShieldTestHelpers::createUser('delme', 'delme@example.com', 'Password1!', 'user');

        $this->loginAs('super@example.com', 'Password1!');

        $this->expectException(PageNotFoundException::class);

        $this->call('GET', 'shield/delete/' . $victim->id);
    }

    public function testPostDeleteRemovesUser(): void
    {
        ShieldTestHelpers::createUser('super', 'super2@example.com', 'Password1!', 'superadmin');
        $victim = ShieldTestHelpers::createUser('delme2', 'delme2@example.com', 'Password1!', 'user');

        $this->loginAs('super2@example.com', 'Password1!');

        $result = $this->call('POST', 'shield/delete/' . $victim->id, [
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('shield'));

        $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
        $this->assertNull($userModel->find($victim->id));
    }

    public function testStoreCreatesUser(): void
    {
        ShieldTestHelpers::createUser('super', 'super3@example.com', 'Password1!', 'superadmin');

        $this->loginAs('super3@example.com', 'Password1!');

        $result = $this->call('POST', 'shield/store', [
            'username'       => 'newshop',
            'email'          => 'newshop@example.com',
            'password'       => 'Password1!',
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirectTo(site_url('shield'));
        $this->seeInDatabase('users', ['username' => 'newshop']);
    }

    public function testAdminPrivilegeEscalationBlocked(): void
    {
        ShieldTestHelpers::createUser('admin', 'admin9@example.com', 'Password1!', 'admin');
        $target = ShieldTestHelpers::createUser('low', 'low@example.com', 'Password1!', 'user');

        $this->loginAs('admin9@example.com', 'Password1!');

        $this->expectException(PageForbiddenException::class);

        $this->call('POST', 'shield/permissions/' . $target->id, [
            'groups'         => ['user', 'superadmin'],
            'csrf_test_name' => csrf_hash(),
        ]);
    }

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        ShieldTestHelpers::createUser('super', 'super4@example.com', 'Password1!', 'superadmin');
        $this->loginAs('super4@example.com', 'Password1!');

        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);

        $this->call('POST', 'shield/store', [
            'username' => 'nocsrf',
            'email'    => 'nocsrf@example.com',
            'password' => 'Password1!',
        ]);
    }

    public function testShieldRequiresAuthentication(): void
    {
        $result = $this->call('GET', 'shield');

        $this->assertTrue(
            $result->isRedirect() || $result->getStatusCode() === 403,
            'Unauthenticated access must not reach the admin UI.',
        );
    }
}
