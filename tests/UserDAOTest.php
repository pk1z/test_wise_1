<?php

use App\DAO\UserDAO;
use App\Exception\UserEmailInvalidException;
use App\Exception\UsernameTooShortException;
use App\User;
use PHPUnit\Framework\TestCase;

class UserDAOTest extends TestCase
{
    private UserDao $userDAO;
    private $connection;

    protected function setUp(): void
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();

        $dbConfig = [
            'host' => $_ENV['MYSQL_HOST'],
            'username' => $_ENV['MYSQL_USER'],
            'password' => $_ENV['MYSQL_PASSWORD'],
            'database' => $_ENV['MYSQL_DATABASE'],
        ];

        $this->connection = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);

        if ($this->connection->connect_error) {
            exit('Connection failed: '.$this->connection->connect_error);
        }

        // Создание тестовой базы данных
        $this->connection->query('CREATE DATABASE IF NOT EXISTS '.$dbConfig['database']);
        $this->connection->select_db($dbConfig['database']);

        // Создание таблицы users
        $this->connection->query('CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT,
            name VARCHAR(64) NOT NULL,
            email VARCHAR(256) NOT NULL,
            created DATETIME NOT NULL,
            deleted DATETIME NULL,
            notes TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE INDEX users_email_uindex (email),
            UNIQUE INDEX users_name_uindex (name)
        )');

        $this->userDAO = new UserDAO($this->connection);
    }

    public function testIsNameValidValidNameReturnsTrue()
    {
        $name = 'johnDoe123';
        $isValid = $this->userDAO->isNameValid($name);
        $this->assertTrue($isValid);
    }

    public function testIsNameValidInvalidNameReturnsFalse()
    {
        $this->expectException(UsernameTooShortException::class);
        $name = 'me';
        $this->userDAO->isNameValid($name);
    }

    public function testIsEmailValidValidEmailReturnsTrue()
    {
        $email = 'johndoe@example.com';
        $isValid = $this->userDAO->isEmailValid($email);
        $this->assertTrue($isValid);
    }

    public function testIsEmailValidInvalidEmailReturnsFalse()
    {
        $this->expectException(UserEmailInvalidException::class);
        $email = 'invalid-email';
        $this->userDAO->isEmailValid($email);
    }

    public function testCreateUser(): void
    {
        $user = new User('John Doe', 'john@example.com');
        $this->userDAO->createUser($user);

        $createdUser = $this->userDAO->getUserById($user->getId());
        $this->assertEquals($user->getName(), $createdUser->getName());
        $this->assertEquals($user->getEmail(), $createdUser->getEmail());
    }

    public function testUpdateUser(): void
    {
        $user = new User('John Doe', 'john@example.com');
        $this->userDAO->createUser($user);

        $user->setName('Jane Smith');
        $user->setEmail('jane@example.com');
        $this->userDAO->updateUser($user);

        $updatedUser = $this->userDAO->getUserById($user->getId());
        $this->assertEquals($user->getName(), $updatedUser->getName());
        $this->assertEquals($user->getEmail(), $updatedUser->getEmail());
    }

    public function testDeleteUser(): void
    {
        $user = new User('John Doe', 'john@example.com');
        $this->userDAO->createUser($user);

        $this->userDAO->deleteUser($user->getId());

        $deletedUser = $this->userDAO->getUserById($user->getId());
        $this->assertNull($deletedUser);
    }

    protected function tearDown(): void
    {
        $this->connection->query('DROP TABLE IF EXISTS users');
        $this->connection->close();
    }

    private function clearTable(): void
    {
        $this->connection->query('TRUNCATE TABLE users');
    }
}
