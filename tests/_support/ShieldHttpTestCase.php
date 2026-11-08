<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Base HTTP integration tests for Shield + /shield routes (migrations, session reset).
 *
 * @internal
 */
abstract class ShieldHttpTestCase extends CIUnitTestCase
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

    protected function loginWithEmailPassword(string $email, string $password): void
    {
        $result = $this->call('POST', 'login', [
            'email'          => $email,
            'password'       => $password,
            'csrf_test_name' => csrf_hash(),
        ]);

        $result->assertRedirect();
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function postWithCsrf(string $path, array $payload = []): \CodeIgniter\Test\TestResponse
    {
        $payload['csrf_test_name'] = csrf_hash();

        return $this->call('POST', $path, $payload);
    }
}
