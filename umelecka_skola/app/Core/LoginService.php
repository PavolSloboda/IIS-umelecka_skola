<?php

declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Security\SimpleIdentity;
use Nette\Security\Passwords;
use Nette\Database\Explorer;

final class LoginService implements Nette\Security\Authenticator
{

	private $database;
	private $passwords;

	public function __construct(Explorer $database, Passwords $passwords)
	{
		$this->database = $database;
		$this->passwords = $passwords;
	}

	public function authenticate(string $email, string $password) : SimpleIdentity
	{
		//getting a user from the database
		$curr_user =  $this->database->table('users')->where('email', $email)->fetch();

		//checking whether the specified user exists
		if(!$curr_user)
		{
			throw new Nette\Security\AuthenticationException('User with the provided email does not exist, please sign up instead');
		}
		//checking whether the password is correct
		elseif (!$this->passwords->verify($password, $curr_user->password))
		{
			throw new Nette\Security\AuthenticationException('Invalid password, please try again');
		}

		$GLOBALS['user'] = $curr_user->user_id;
		return new SimpleIdentity($curr_user->user_id, ['email' => $curr_user->email]);
	}

	public function signUp(string $email, string $password, string $username): void
	{
		$curr_user =  $this->database->table('users')->where('email', $email)->fetch();
		if($curr_user)
		{
			throw new Nette\Security\AuthenticationException('User already exists, please log in instead');
		}
		$this->database->table('users')->insert(['email' => $email, 'password' => $this->passwords->hash($password), 'name' => $username,]);
	}
}
