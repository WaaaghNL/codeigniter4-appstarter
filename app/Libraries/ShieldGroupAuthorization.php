<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Exceptions\PageForbiddenException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use Config\AuthGroups;

/**
 * Server-side rules for Shield admin group and user lifecycle actions.
 */
final class ShieldGroupAuthorization
{
    /**
     * Groups that only a superadmin may assign or remove.
     *
     * @var list<string>
     */
    public const SUPERADMIN_ONLY_GROUPS = ['superadmin'];

    /**
     * Groups considered elevated; non-superadmins cannot assign them.
     *
     * @var list<string>
     */
    public const PRIVILEGED_GROUPS = ['superadmin', 'admin'];

    public function __construct(
        private readonly AuthGroups $authGroups,
        private readonly UserModel $userModel,
    ) {
    }

    /**
     * @return list<string>
     */
    public function validGroupNames(): array
    {
        return array_keys($this->authGroups->groups);
    }

    /**
     * @param list<string>|null $requestedGroups
     *
     * @return list<string>
     */
    public function resolveGroupsForSync(User $actor, User $target, ?array $requestedGroups): array
    {
        $requested = $this->sanitizeRequestedGroups($requestedGroups);
        $resolved  = $this->applyAssignmentPolicy($actor, $target, $requested);

        $this->assertRetainsLastSuperadmin($actor, $target, $resolved);

        return $resolved;
    }

    public function assertCanManageUserGroups(User $actor, User $target): void
    {
        if ($actor->inGroup('superadmin')) {
            return;
        }

        if ($actor->inGroup('admin')) {
            if ($target->inGroup('superadmin') || $target->inGroup('admin')) {
                throw PageForbiddenException::forPageForbidden();
            }

            return;
        }

        throw PageForbiddenException::forPageForbidden();
    }

    public function assertCanDeleteUser(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw PageForbiddenException::forPageForbidden('You cannot delete your own account.');
        }

        if ($target->inGroup('superadmin') && ! $actor->inGroup('superadmin')) {
            throw PageForbiddenException::forPageForbidden();
        }

        if ($target->inGroup('admin') && ! $actor->inGroup('superadmin')) {
            throw PageForbiddenException::forPageForbidden();
        }
    }

    /**
     * @param list<string>|null $requestedGroups
     *
     * @return list<string>
     */
    private function sanitizeRequestedGroups(?array $requestedGroups): array
    {
        if ($requestedGroups === null) {
            return [];
        }

        $valid = $this->validGroupNames();

        $requested = array_values(array_unique(array_filter(
            $requestedGroups,
            static fn (string $group): bool => in_array($group, $valid, true),
        )));

        return $requested;
    }

    /**
     * @param list<string> $requested
     *
     * @return list<string>
     */
    private function applyAssignmentPolicy(User $actor, User $target, array $requested): array
    {
        if ($actor->inGroup('superadmin')) {
            return $requested;
        }

        if (! $actor->inGroup('admin')) {
            throw PageForbiddenException::forPageForbidden();
        }

        if ($target->inGroup('superadmin')) {
            throw PageForbiddenException::forPageForbidden();
        }

        foreach ($requested as $group) {
            if (in_array($group, self::PRIVILEGED_GROUPS, true)) {
                throw PageForbiddenException::forPageForbidden();
            }
        }

        $currentPrivileged = array_intersect($target->getGroups(), self::PRIVILEGED_GROUPS);
        foreach ($currentPrivileged as $group) {
            if (! in_array($group, $requested, true)) {
                throw PageForbiddenException::forPageForbidden();
            }
        }

        if ($actor->id === $target->id) {
            foreach ($requested as $group) {
                if (in_array($group, self::PRIVILEGED_GROUPS, true)) {
                    throw PageForbiddenException::forPageForbidden();
                }
            }
        }

        return $requested;
    }

    /**
     * @param list<string> $resolvedGroups
     */
    private function assertRetainsLastSuperadmin(User $actor, User $target, array $resolvedGroups): void
    {
        if (! $target->inGroup('superadmin')) {
            return;
        }

        if (in_array('superadmin', $resolvedGroups, true)) {
            return;
        }

        if ($this->countUsersInGroup('superadmin') <= 1) {
            throw PageForbiddenException::forPageForbidden(
                'Cannot remove the last superadmin account.',
            );
        }
    }

    private function countUsersInGroup(string $group): int
    {
        $count = 0;

        foreach ($this->userModel->findAll() as $user) {
            if ($user->inGroup($group)) {
                $count++;
            }
        }

        return $count;
    }
}
