<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\DuplicateNameException;
use League\OAuth2\Client\Provider\GoogleUser;
use Nette;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

/**
 * Users management.
 */
final class UserFacade implements Authenticator
{
	use Nette\SmartObject;

	public const PASSWORD_MIN_LENGTH = 7;

	private const TABLE_NAME = 'users';
	private const COLUMN_ID = 'id';
	private const COLUMN_NAME = 'username';
	private const COLUMN_PASSWORD_HASH = 'password';
	private const COLUMN_EMAIL = 'email';
	private const COLUMN_ROLE = 'role';
	private const COLUMN_GOOGLE_ID = 'google_id';

	private Explorer $database;

	private Passwords $passwords;

	public function __construct(Explorer $database, Passwords $passwords)
	{
		$this->database = $database;
		$this->passwords = $passwords;
	}

	/**
	 * Performs an authentication.
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(string $username, ?string $password = null): SimpleIdentity
	{
		$row = $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_NAME, $username)
			->fetch();

		if (!is_null($password)) {
			if (!$row) {
				throw new AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
			} elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
				throw new AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
			} elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
				$row->update([
					self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
				]);
			}
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new SimpleIdentity(
			$row[self::COLUMN_ID],
			$row[self::COLUMN_ROLE],
			$arr
		);
	}

	/**
	 * Adds new user.
	 * @throws DuplicateNameException
	 */
	public function add(string $username, string $email, string $password, string $googleId = ''): void
	{
		Validators::assert($email, 'email');
		try {
			$this->database->table(self::TABLE_NAME)->insert([
				self::COLUMN_NAME => $username,
				self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
				self::COLUMN_EMAIL => $email,
				self::COLUMN_GOOGLE_ID => $googleId,
			]);
		} catch (UniqueConstraintViolationException $e) {
			throw new DuplicateNameException();
		}
	}

	public function registerFromGoogle(GoogleUser $googleUser): SimpleIdentity
	{
		$id = (string)$googleUser->getId();
		$name = Strings::webalize($googleUser->getName());
		$name = str_replace('-', '.', $name);
		$this->add(
			$name,
			$googleUser->getEmail(),
			Random::generate(10),
			$id
		);
		return $this->findByGoogleId($id);
	}

	public function findByGoogleId(string $id): SimpleIdentity
	{
		$row = $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_GOOGLE_ID, $id)
			->fetch();
		if (!$row) {
			throw new AuthenticationException();
		}
		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new SimpleIdentity(
			$row[self::COLUMN_ID],
			$row[self::COLUMN_ROLE],
			$arr
		);
	}

	public function findByEmail(string $email): SimpleIdentity
	{
		$row = $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_EMAIL, $email)
			->fetch();
		if (!$row) {
			throw new AuthenticationException();
		}
		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new SimpleIdentity(
			$row[self::COLUMN_ID],
			$row[self::COLUMN_ROLE],
			$arr
		);
	}
}
