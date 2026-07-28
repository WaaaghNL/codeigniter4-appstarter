<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;


class CreateAdmin extends Migration
{
    public function up(): void
    {
		helper('auth');
		$this->userModel = auth()->getProvider();

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
    }

    public function down(): void
    {    }

}