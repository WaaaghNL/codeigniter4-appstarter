<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Formerly seeded a fixed demo superadmin. That behavior was removed for security.
 *
 * @see docs/SECURITY_BOOTSTRAP.md
 */
class CreateAdmin extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
    }
}
