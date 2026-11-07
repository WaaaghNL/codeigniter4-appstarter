<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

final class ShieldTestHelpers
{
    public static function createUser(
        string $username,
        string $email,
        string $password,
        string ...$groups,
    ): User {
        $userModel = model(UserModel::class);

        $user = new User([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
            'active'   => 1,
        ]);

        $userModel->save($user);
        $user = $userModel->findById($userModel->getInsertID());

        if ($groups === []) {
            $userModel->addToDefaultGroup($user);
        } else {
            $user->syncGroups(...$groups);
        }

        return $userModel->findById($user->id);
    }
}
