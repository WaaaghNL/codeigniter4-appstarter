<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\AuthGroups;

class ShieldAdminController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = auth()->getProvider();
    }

    /**
     * Controle of gebruiker superadmin is
     */
    private function checkAccess(): void
    {
        if (! auth()->user()?->inGroup('superadmin')) {
            throw \CodeIgniter\Exceptions\PageForbiddenException::forPageForbidden();
        }
    }

    /**
     * Maak standaard gebruikers aan
     */
    public function createDefaultUsers(): RedirectResponse
    {
        $usersList = $this->userModel->findAll();
		
		if(count($usersList) === 0){
			
			$user = new User([
				'username' => 'admin',
				'email'    => 'admin@example.com',
				'password' => 'adminpass',
			]);

			$this->userModel->save($user);

			// To get the complete user object with ID, we need to get from the database
			$user = $this->userModel->findById($this->userModel->getInsertID());
			
			// Add to default group and superadmin
			$this->userModel->addToDefaultGroup($user);	
			
			//First user is superadmin
			$user->addGroup('superadmin');
			return redirect()->to('/login');
		}
		else{
			return redirect()->to('/');
		}		
    }

    /**
     * Lijst gebruikers
     */
    public function index()
    {
        $this->checkAccess();

        $users = $this->userModel->findAll();

        return view('shield/index', [
            'users' => $users
        ]);
    }

    /**
     * Toon formulier gebruiker toevoegen
     */
    public function create()
    {
        $this->checkAccess();

        return view('shield/create');
    }

    /**
     * Opslaan nieuwe gebruiker
     */
    public function store(): RedirectResponse
    {
        $this->checkAccess();

        $user = new User([
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'active'   => 1,
        ]);

        $this->userModel->save($user);
		
		// To get the complete user object with ID, we need to get from the database
		$user = $this->userModel->findById($users->getInsertID());

		// Add to default group
		$this->userModel->addToDefaultGroup($user);

        return redirect()->to('/shield');
    }

    /**
     * Bewerkformulier
     */
    public function edit(int $id)
    {
        $this->checkAccess();

        $user = $this->userModel->find($id);

        return view('shield/edit', [
            'user' => $user
        ]);
    }

    /**
     * Opslaan wijzigingen
     */
    public function update(int $id): RedirectResponse
    {
        $this->checkAccess();

        $user = $this->userModel->find($id);

        $user->username = $this->request->getPost('username');
        $user->email    = $this->request->getPost('email');

        if (! empty($this->request->getPost('password'))) {
            $user->password = $this->request->getPost('password');
        }

        $this->userModel->save($user);

        return redirect()->to('/shield');
    }

    /**
     * Verwijderen gebruiker
     */
    public function delete(int $id): RedirectResponse
    {
        $this->checkAccess();

        $user = $this->userModel->find($id);

        if ($user) {
            $this->userModel->delete($id);
        }

        return redirect()->to('/shield');
    }

    /**
     * Alleen groepen aanpassen
     */

	public function permissions(int $id)
	{
		$this->checkAccess();

		$user = $this->userModel->find($id);

		$authGroups = config(AuthGroups::class);

		return view('shield/permissions', [
			'user' => $user,
			'availableGroups' => array_keys($authGroups->groups),
		]);
	}

    /**
     * Groepen opslaan
     */
    public function savePermissions(int $id): RedirectResponse
    {
        $this->checkAccess();

        $user = $this->userModel->find($id);

        $groups = $this->request->getPost('groups');

        $user->syncGroups(...$groups);	

        return redirect()->to('/shield');
    }
}