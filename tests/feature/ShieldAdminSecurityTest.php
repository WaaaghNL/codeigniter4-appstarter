<?php

declare(strict_types=1);

use App\Exceptions\PageForbiddenException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Shield\Models\UserModel;
use Tests\Support\ShieldHttpTestCase;
use Tests\Support\ShieldTestHelpers;

/**
 * Endpoint-level regression tests for /shield and auth boundaries (not pure policy unit tests).
 *
 * @internal
 */
final class ShieldAdminSecurityTest extends ShieldHttpTestCase
{
    public function testUnauthenticatedShieldIndexIsRejected(): void
    {
        $result = $this->call('GET', 'shield');

        $this->assertTrue(
            $result->isRedirect() || $result->response()->getStatusCode() === 403,
        );
    }

    public function testUnauthenticatedPostUpdateIsRejected(): void
    {
        $target = ShieldTestHelpers::createUser('target', 'target@example.com', 'Password1!', 'user');

        try {
            $result = $this->postWithCsrf('shield/update/' . $target->id, [
                'username' => 'hacked',
                'email'    => 'hacked@example.com',
            ]);
            $this->assertNotSame(200, $result->response()->getStatusCode());
        } catch (PageForbiddenException|SecurityException) {
            // Route filters or CSRF correctly blocked the request.
        }

        $this->seeInDatabase('users', ['username' => 'target']);
        $this->dontSeeInDatabase('users', ['username' => 'hacked']);
    }

    public function testLoginFormAcceptsValidCsrf(): void
    {
        ShieldTestHelpers::createUser('loginuser', 'loginuser@example.com', 'Password1!', 'user');

        $result = $this->postWithCsrf('login', [
            'email'    => 'loginuser@example.com',
            'password' => 'Password1!',
        ]);

        $result->assertRedirect();
    }

    public function testLoginPostWithoutCsrfIsRejected(): void
    {
        ShieldTestHelpers::createUser('loginuser2', 'loginuser2@example.com', 'Password1!', 'user');

        $this->expectException(SecurityException::class);

        $this->call('POST', 'login', [
            'email'    => 'loginuser2@example.com',
            'password' => 'Password1!',
        ]);
    }

    public function testPostStoreWithoutCsrfIsRejected(): void
    {
        ShieldTestHelpers::createUser('super', 'super4@example.com', 'Password1!', 'superadmin');
        $this->loginWithEmailPassword('super4@example.com', 'Password1!');

        $this->expectException(SecurityException::class);

        $this->call('POST', 'shield/store', [
            'username' => 'nocsrf',
            'email'    => 'nocsrf@example.com',
            'password' => 'Password1!',
        ]);
    }

    public function testPostUpdateWithInvalidCsrfIsRejected(): void
    {
        ShieldTestHelpers::createUser('super', 'super5@example.com', 'Password1!', 'superadmin');
        $target = ShieldTestHelpers::createUser('editme', 'editme@example.com', 'Password1!', 'user');
        $this->loginWithEmailPassword('super5@example.com', 'Password1!');

        $this->expectException(SecurityException::class);

        $this->call('POST', 'shield/update/' . $target->id, [
            'username'       => 'stolen',
            'email'          => 'stolen@example.com',
            'csrf_test_name' => 'not-a-valid-token',
        ]);
    }

    public function testGetDeleteRouteDoesNotMutate(): void
    {
        ShieldTestHelpers::createUser('super', 'super@example.com', 'Password1!', 'superadmin');
        $victim = ShieldTestHelpers::createUser('delme', 'delme@example.com', 'Password1!', 'user');

        $this->loginWithEmailPassword('super@example.com', 'Password1!');

        $this->expectException(PageNotFoundException::class);

        $this->call('GET', 'shield/delete/' . $victim->id);

        $this->seeInDatabase('users', ['username' => 'delme']);
    }

    public function testPostDeleteRemovesUser(): void
    {
        ShieldTestHelpers::createUser('super', 'super2@example.com', 'Password1!', 'superadmin');
        $victim = ShieldTestHelpers::createUser('delme2', 'delme2@example.com', 'Password1!', 'user');

        $this->loginWithEmailPassword('super2@example.com', 'Password1!');

        $result = $this->postWithCsrf('shield/delete/' . $victim->id);

        $result->assertRedirectTo(site_url('shield'));
        $this->assertNull(model(UserModel::class)->find($victim->id));
    }

    public function testAdminPostDeleteSuperadminForbidden(): void
    {
        ShieldTestHelpers::createUser('admin', 'admin-del@example.com', 'Password1!', 'admin');
        $super = ShieldTestHelpers::createUser('super', 'super-del@example.com', 'Password1!', 'superadmin');

        $this->loginWithEmailPassword('admin-del@example.com', 'Password1!');

        $this->expectException(PageForbiddenException::class);

        $this->postWithCsrf('shield/delete/' . $super->id);
    }

    public function testStoreCreatesUser(): void
    {
        ShieldTestHelpers::createUser('super', 'super3@example.com', 'Password1!', 'superadmin');
        $this->loginWithEmailPassword('super3@example.com', 'Password1!');

        $result = $this->postWithCsrf('shield/store', [
            'username' => 'newshop',
            'email'    => 'newshop@example.com',
            'password' => 'Password1!',
        ]);

        $result->assertRedirectTo(site_url('shield'));
        $this->seeInDatabase('users', ['username' => 'newshop']);
    }

    public function testAdminForgedSuperadminGroupAssignmentBlocked(): void
    {
        ShieldTestHelpers::createUser('admin', 'admin9@example.com', 'Password1!', 'admin');
        $target = ShieldTestHelpers::createUser('low', 'low@example.com', 'Password1!', 'user');

        $this->loginWithEmailPassword('admin9@example.com', 'Password1!');

        $this->expectException(PageForbiddenException::class);

        $this->postWithCsrf('shield/permissions/' . $target->id, [
            'groups' => ['user', 'superadmin'],
        ]);
    }

    public function testAdminGetEditSuperadminForbidden(): void
    {
        ShieldTestHelpers::createUser('admin', 'admin-edit@example.com', 'Password1!', 'admin');
        $super = ShieldTestHelpers::createUser('sa', 'sa@example.com', 'Password1!', 'superadmin');

        $this->loginWithEmailPassword('admin-edit@example.com', 'Password1!');

        $this->expectException(PageForbiddenException::class);

        $this->call('GET', 'shield/edit/' . $super->id);
    }

    public function testAdminPostUpdateSuperadminIdentityBlocked(): void
    {
        ShieldTestHelpers::createUser('admin', 'admin-up@example.com', 'Password1!', 'admin');
        $super = ShieldTestHelpers::createUser('sa2', 'sa2@example.com', 'Password1!', 'superadmin');

        $this->loginWithEmailPassword('admin-up@example.com', 'Password1!');

        $this->expectException(PageForbiddenException::class);

        $this->postWithCsrf('shield/update/' . $super->id, [
            'username' => 'pwned',
            'email'    => 'pwned@example.com',
            'password' => 'Password1!',
        ]);
    }

    public function testAdminPostUpdateSuperadminPasswordBlocked(): void
    {
        ShieldTestHelpers::createUser('admin', 'admin-pw@example.com', 'Password1!', 'admin');
        $super = ShieldTestHelpers::createUser('sa3', 'sa3@example.com', 'Password1!', 'superadmin');

        $this->loginWithEmailPassword('admin-pw@example.com', 'Password1!');

        $this->expectException(PageForbiddenException::class);

        $this->postWithCsrf('shield/update/' . $super->id, [
            'username' => $super->username,
            'email'    => $super->email,
            'password' => 'NewPassword9!',
        ]);
    }

    public function testAdminCanUpdateRegularUser(): void
    {
        ShieldTestHelpers::createUser('admin', 'admin-ok@example.com', 'Password1!', 'admin');
        $target = ShieldTestHelpers::createUser('regular', 'regular@example.com', 'Password1!', 'user');

        $this->loginWithEmailPassword('admin-ok@example.com', 'Password1!');

        $result = $this->postWithCsrf('shield/update/' . $target->id, [
            'username' => 'regular2',
            'email'    => 'regular2@example.com',
        ]);

        $result->assertRedirectTo(site_url('shield'));
        $this->seeInDatabase('users', ['username' => 'regular2']);
    }

    public function testSuperadminCanUpdateAdminUser(): void
    {
        ShieldTestHelpers::createUser('super', 'super-up@example.com', 'Password1!', 'superadmin');
        $admin = ShieldTestHelpers::createUser('adm', 'adm@example.com', 'Password1!', 'admin');

        $this->loginWithEmailPassword('super-up@example.com', 'Password1!');

        $result = $this->postWithCsrf('shield/update/' . $admin->id, [
            'username' => 'adm2',
            'email'    => 'adm2@example.com',
        ]);

        $result->assertRedirectTo(site_url('shield'));
        $this->seeInDatabase('users', ['username' => 'adm2']);
    }

    public function testAdminCanUpdateOwnProfile(): void
    {
        ShieldTestHelpers::createUser('admin', 'admin-self@example.com', 'Password1!', 'admin');

        $this->loginWithEmailPassword('admin-self@example.com', 'Password1!');

        $actor = model(UserModel::class)->where('username', 'admin')->first();

        $result = $this->postWithCsrf('shield/update/' . $actor->id, [
            'username' => 'admin-self2',
            'email'    => 'admin-self2@example.com',
        ]);

        $result->assertRedirectTo(site_url('shield'));
        $this->seeInDatabase('users', ['username' => 'admin-self2']);
    }
}
