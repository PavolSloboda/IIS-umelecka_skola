<?php

/**
 * LoginService class
 * 
 * This service handles user authentication and registration functionality.
 * It implements Nette's Authenticator interface to verify user credentials
 * and manage the creation of new users in the system.
 * 
 * @package App\Core
 */
declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Security\SimpleIdentity;
use Nette\Security\Passwords;
use Nette\Database\Explorer;

final class LoginService implements Nette\Security\Authenticator
{

	/**
     * @var Explorer $database Database explorer for interacting with the users table.
     */
	private $database;
	/**
     * @var Passwords $passwords Passwords service for hashing and verifying passwords.
     */
	private $passwords;

	/**
     * Constructor for the LoginService.
     * 
     * @param Explorer $database Injected database explorer for accessing user data.
     * @param Passwords $passwords Injected passwords service for managing password hashing and verification.
     */
	public function __construct(Explorer $database, Passwords $passwords)
	{
		$this->database = $database;
		$this->passwords = $passwords;
	}

	/**
     * Authenticates a user by verifying their email and password.
     * 
     * @param string $email The email address provided by the user.
     * @param string $password The password provided by the user.
     * @return SimpleIdentity The identity of the authenticated user.
     * @throws Nette\Security\AuthenticationException If the user does not exist or the password is incorrect.
     */
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

	/**
     * Registers a new user with the provided email, password, and username.
     * 
     * @param string $email The email address for the new user.
     * @param string $password The password for the new user.
     * @param string $username The username for the new user.
     * @return void
     * @throws Nette\Security\AuthenticationException If a user with the same email already exists.
     */
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
