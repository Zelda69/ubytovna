<?php

namespace App\Model;

use App\Model\Exception\DuplicateNameException;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\Passwords;

/**
 * Users management.
 */
class UserManager extends BaseManager implements IAuthenticator {

    const TABLE_NAME = 'user';
    const COLUMN_ID = 'id';
    const COLUMN_NAME = 'username', COLUMN_PASSWORD_HASH = 'password', COLUMN_ROLE = 'role';


    /**
     * Přihlásí uživatele do systému.
     *
     * @param array $credentials       jméno/email a heslo uživatele
     * @return Identity identitu přihlášeného uživatele pro další manipulaci
     * @throws AuthenticationException Jestliže došlo k chybě při prihlašování, např. špatné heslo nebo uživatelské
     *                                 jméno.
     */
    public function authenticate(array $credentials) {
        list($username, $password) = $credentials; // Extrahuje potřebné parametry.

        // Vykoná dotaz nad databází a vrátí první řádek výsledku nebo false, pokud uživatel neexistuje.
        $user = $this->database->table(self::TABLE_NAME)
            ->where([self::COLUMN_NAME => $username])
            ->fetch();

        // Ověření uživatele.
        if (!$user) {
            // Vyhodí výjimku, pokud uživatel neexituje.
            throw new AuthenticationException('Účet se zadaným emailem neexistuje!', self::IDENTITY_NOT_FOUND);
        } elseif (!Passwords::verify($password, $user[self::COLUMN_PASSWORD_HASH])) { // Ověří heslo.
            // Vyhodí výjimku, pokud je heslo špatně.
            throw new AuthenticationException('Zadané heslo není správné.', self::INVALID_CREDENTIAL);
        } elseif (Passwords::needsRehash($user[self::COLUMN_PASSWORD_HASH])) { // Zjistí, jestli heslo potřebuje rehashovat.
            // Rehashuje heslo.
            $user->update(array(self::COLUMN_PASSWORD_HASH => Passwords::hash($password)));
        }

        // Příprava uživatelských dat.
        $userData = $user->toArray(); // Extrahuje uživatelská data.
        unset($userData[self::COLUMN_PASSWORD_HASH]); // Odstraní položku hesla z uživatelských dat (kvůli bezpečnosti).

        // Vrátí novou identitu přihlášeného uživatele.
        return new Identity($user[self::COLUMN_ID], $user[self::COLUMN_ROLE], $userData);
    }

    /**
     * Registruje nového uživatele do systému.
     *
     * @param array $data uživatelská data
     * @throws DuplicateNameException Jestliže uživatel s daným emailem již existuje.
     */
    public function register($data) {
        try {
            $data['password'] = Passwords::hash($data['password']);
            // Pokusí se vložit nového uživatele do databáze.
            $this->database->table(self::TABLE_NAME)->insert($data);
        } catch (UniqueConstraintViolationException $e) {
            // Vyhodí výjimku, pokud uživatel s daným jménem již existuje.
            throw new DuplicateNameException;
        }
    }

    public function getPassword($user) {
        return $this->database->table(self::TABLE_NAME)->where('id = ?', $user)->fetch()->password;
    }


    public function changePassword($user, $password) {
        $this->database->table(self::TABLE_NAME)
            ->where('id = ?', $user)
            ->update(['password' => Passwords::hash($password)]);
    }

    public function changeProfil($user, $data) {
        $this->database->table(self::TABLE_NAME)->where('id = ?', $user)->update($data);
    }

}
