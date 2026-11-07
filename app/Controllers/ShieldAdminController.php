<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\ShieldGroupAuthorization;
use App\Exceptions\PageForbiddenException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use Config\AuthGroups;

class ShieldAdminController extends BaseController
{
    protected UserModel $userModel;

    protected ShieldGroupAuthorization $groupAuthorization;

    public function __construct()
    {
        $this->userModel          = auth()->getProvider();
        $this->groupAuthorization = new ShieldGroupAuthorization(
            config(AuthGroups::class),
            $this->userModel,
        );
    }

    private function requireActor(): User
    {
        $user = auth()->user();

        if ($user === null) {
            throw PageForbiddenException::forPageForbidden();
        }

        return $user;
    }

    /**
     * Lijst gebruikers
     */
    public function index()
    {
        $users = $this->userModel->findAll();

        return view('shield/index', [
            'users' => $users,
        ]);
    }

    /**
     * Toon formulier gebruiker toevoegen
     */
    public function create()
    {
        return view('shield/create');
    }

    /**
     * Opslaan nieuwe gebruiker
     */
    public function store(): RedirectResponse
    {
        $user = new User([
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'active'   => 1,
        ]);

        $this->userModel->save($user);

        $user = $this->userModel->findById($this->userModel->getInsertID());

        $this->userModel->addToDefaultGroup($user);

        return redirect()->to('/shield');
    }

    /**
     * Bewerkformulier
     */
    public function edit(int $id)
    {
        $user = $this->userModel->find($id);

        if ($user === null) {
            throw PageForbiddenException::forPageForbidden();
        }

        return view('shield/edit', [
            'user' => $user,
        ]);
    }

    /**
     * Opslaan wijzigingen
     */
    public function update(int $id): RedirectResponse
    {
        $user = $this->userModel->find($id);

        if ($user === null) {
            throw PageForbiddenException::forPageForbidden();
        }

        $user->username = $this->request->getPost('username');
        $user->email    = $this->request->getPost('email');

        if (! empty($this->request->getPost('password'))) {
            $user->password = $this->request->getPost('password');
        }

        $this->userModel->save($user);

        return redirect()->to('/shield');
    }

    /**
     * Verwijderen gebruiker (POST only).
     */
    public function delete(int $id): RedirectResponse
    {
        if (! $this->request->is('post')) {
            throw PageForbiddenException::forPageForbidden();
        }

        $actor = $this->requireActor();
        $user  = $this->userModel->find($id);

        if ($user === null) {
            return redirect()->to('/shield');
        }

        $this->groupAuthorization->assertCanDeleteUser($actor, $user);
        $this->userModel->delete($id);

        return redirect()->to('/shield');
    }

    public function permissions(int $id)
    {
        $actor = $this->requireActor();
        $user  = $this->userModel->find($id);

        if ($user === null) {
            throw PageForbiddenException::forPageForbidden();
        }

        $this->groupAuthorization->assertCanManageUserGroups($actor, $user);

        $authGroups = config(AuthGroups::class);

        return view('shield/permissions', [
            'user'              => $user,
            'availableGroups'   => $this->assignableGroupsForView($actor, $user),
            'authGroups'        => $authGroups,
        ]);
    }

    /**
     * Groepen opslaan
     */
    public function savePermissions(int $id): RedirectResponse
    {
        $actor = $this->requireActor();
        $user  = $this->userModel->find($id);

        if ($user === null) {
            throw PageForbiddenException::forPageForbidden();
        }

        $this->groupAuthorization->assertCanManageUserGroups($actor, $user);

        /** @var list<string>|null $groups */
        $groups = $this->request->getPost('groups');

        $resolved = $this->groupAuthorization->resolveGroupsForSync($actor, $user, $groups);

        $user->syncGroups(...$resolved);

        return redirect()->to('/shield');
    }

    /**
     * Groups shown in the permissions UI (excludes ones the actor may not assign).
     *
     * @return list<string>
     */
    private function assignableGroupsForView(User $actor, User $target): array
    {
        $all = $this->groupAuthorization->validGroupNames();

        if ($actor->inGroup('superadmin')) {
            return $all;
        }

        return array_values(array_filter(
            $all,
            static fn (string $group): bool => ! in_array($group, ShieldGroupAuthorization::PRIVILEGED_GROUPS, true),
        ));
    }
}
